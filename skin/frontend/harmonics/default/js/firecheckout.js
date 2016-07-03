var FireCheckout = Class.create();
FireCheckout.prototype = {

    initialize: function(form, urls, translations) {
        this._addNumberToTitles();

        this.translations = translations;
        this.urls         = urls;
        this.form         = form;
        this.loadWaiting  = false;
        this.validator    = new Validation(this.form);

        payment.saveUrl = this.urls.payment_method;

        this.sectionsToValidate = [
            payment
        ];
        if (typeof shippingMethod === 'object') {
            this.sectionsToValidate.push(shippingMethod);
        }

        this._addEventListeners();
    },

    _addNumberToTitles: function() {
        var titles = [
            '#billing-address .block-title',
            '#shipping-method .block-title',
            '#payment-method .block-title',
            '#checkout-additional .block-title',
            '#checkout-review .block-title'
        ],
            i = 0,
            j = 1,
            title;

        while ((selector = titles[i])) {
            i++;
            if ((title = $$(selector)[0])) {
                title.insert({
                    top: '<span class="num num' + (j) + '">' + (j) + '</span>'
                });
                j++;
            }
        }
    },

    _addEventListeners: function() {
        var self = this;

        $('firecheckout-login-form') && $('firecheckout-login-form').observe('submit', function(e) {
            if (typeof event != 'undefined') { // ie9 fix
                event.preventDefault ? event.preventDefault() : event.returnValue = false;
            }
            Event.stop(e);

            if (!loginForm.validator.validate()) {
                return false;
            }

            $('login-please-wait').show();
            $('send2').setAttribute('disabled', 'disabled');
            $$('#firecheckout-login-form .buttons-set')[0]
                .addClassName('disabled')
                .setOpacity(0.5);

            new Ajax.Request($('firecheckout-login-form').action, {
                parameters: $('firecheckout-login-form').serialize(),
                onSuccess: function(transport) {
                    FireCheckout.Messenger.clear('firecheckout-login-form');

                    var response = transport.responseText.evalJSON();
                    if (response.error) {
                        FireCheckout.Messenger.add(response.error, 'firecheckout-login-form', 'error');
                        self.updateCaptcha('user_login');
                    }
                    if (response.redirect) {
                        document.location = response.redirect;
                        return;
                    }
                    $('login-please-wait').hide();
                    $('send2').removeAttribute('disabled');
                    $$('#firecheckout-login-form .buttons-set')[0]
                        .removeClassName('disabled')
                        .setOpacity(1);
                }
            });
        });

        $('firecheckout-forgot-password-form') && $('firecheckout-forgot-password-form').observe('submit', function(e) {
            if (typeof event != 'undefined') { // ie9 fix
                event.preventDefault ? event.preventDefault() : event.returnValue = false;
            }
            Event.stop(e);

            if (!forgotForm.validator.validate()) {
                return false;
            }

            $('forgot-please-wait').show();
            $('btn-forgot').setAttribute('disabled', 'disabled');
            $$('#firecheckout-forgot-password-form .buttons-set')[0]
                .addClassName('disabled')
                .setOpacity(0.5);

            new Ajax.Request($('firecheckout-forgot-password-form').action, {
                parameters: $('firecheckout-forgot-password-form').serialize(),
                onSuccess: function(transport) {
                    FireCheckout.Messenger.clear('firecheckout-forgot-password-form');

                    $('forgot-please-wait').hide();
                    $('btn-forgot').removeAttribute('disabled');
                    $$('#firecheckout-forgot-password-form .buttons-set')[0]
                        .removeClassName('disabled')
                        .setOpacity(1);

                    var response = transport.responseText.evalJSON();

                    if (response.error) {
                        FireCheckout.Messenger.add(response.error, 'firecheckout-forgot-password-form', 'error');
                        self.updateCaptcha('user_forgotpassword');
                    } else if (response.message) {
                        FireCheckout.Messenger.clear('firecheckout-login-form');
                        FireCheckout.Messenger.add(response.message, 'firecheckout-login-form', 'success');
                        firecheckoutWindow.activate('login');
                    }
                }
            });
        });
    },

    ajaxFailure: function(){
        location.href = this.urls.failure;
    },

    _disableEnableAll: function(element, isDisabled) {
        var descendants = element.descendants();
        for (var k in descendants) {
            descendants[k].disabled = isDisabled;
        }
        element.disabled = isDisabled;
    },

    setLoadWaiting: function(flag) {
        if (flag && !this.loadWaiting) {
            var //elToHover = $$('.col-main')[0],
                elToHover = $('firecheckout-form').up(),
                size      = elToHover.getDimensions(),
                offset    = elToHover.positionedOffset(),
                overlay   = new Element('div');

            overlay.writeAttribute('id', 'firecheckout-overlay');
            overlay.setStyle({
                width : size.width  + 'px',
                height: size.height + 'px',
                left  : offset.left + 'px',
                top   : offset.top  + 'px'
            });
            elToHover.insert({
                after: overlay
            });

            var spinner      = new Element('div'),
                viewportSize = document.viewport.getDimensions(),
                scrollOffset = document.viewport.getScrollOffsets();

            spinner.writeAttribute('id', 'firecheckout-spinner');
            spinner.insert(this.translations.spinnerText);

            if ('undefined' === typeof viewportSize.height) { // mobile fix
                var left = scrollOffset.left + 200,
                    top  = scrollOffset.top + 200;
            } else {
                var left = offset.left + size.width / 2,
                    top  = scrollOffset.top + viewportSize.height / 2;
            }

            spinner.setStyle({
                //left  : scrollOffset.left + viewportSize.width / 2 + 'px',
                left: left + 'px',
                top : top + 'px'
            });
            overlay.insert({
                after: spinner
            });

            var container = $('review-buttons-container');
            container.addClassName('disabled');
            container.setStyle({opacity:0.5});
            this._disableEnableAll(container, true);
        } else if (this.loadWaiting) {
            var overlay = $('firecheckout-overlay'),
                spinner = $('firecheckout-spinner');
            overlay && overlay.remove();
            spinner && spinner.remove();

            var container = $('review-buttons-container');
            container.removeClassName('disabled');
            container.setStyle({opacity:1});
            this._disableEnableAll(container, false);
        }
        this.loadWaiting = flag;
    },

    save: function(urlSuffix, forceSave) {
        if (this.loadWaiting != false) {
            return;
        }

        var isValid = true;

        if (!this.validator.validate()) {
            isValid = false;
        }

        for (i in this.sectionsToValidate) {
            if (typeof this.sectionsToValidate[i] === 'function') {
                continue;
            }
            if (!this.sectionsToValidate[i].validate()) {
                isValid = false;
            }
        }
        FireCheckout.Messenger.clear('agreements-wrapper');
        $$('.checkout-agreements input[type="checkbox"]').each(function(el) {
            if (!el.checked) {
                FireCheckout.Messenger.add(this.translations.acceptAgreementText, 'agreements-wrapper', 'error');
                isValid = false;
                throw $break;
            }
        }.bind(this));

        // var taxvatField = $('billing:taxvat');
        // if (taxvatField && taxvatField.value.length) {
           // if (!FireCheckout.Taxvat.isValid(taxvatField.value, $('billing:country_id').value)) {
                // taxvatField.addClassName('validation-failed')
                // isValid = false;
           // }
        // }

        if (!isValid) {
            // scroll to error
            var validationMessages = $$('.validation-advice, .messages').findAll(function(el) {
                return el.visible();
            });
            if (!validationMessages.length) {
                return;
            }

            var viewportSize = document.viewport.getDimensions();
            var hiddenMessages = [];
            var needToScroll = true;

            validationMessages.each(function(el) {
                var offset = el.viewportOffset();
                if (offset.top < 0
                    || offset.top > viewportSize.height
                    || offset.left < 0
                    || offset.left > viewportSize.width) {

                    hiddenMessages.push(el);
                } else {
                    needToScroll = false;
                }
            });

            if (needToScroll) {
                Effect.ScrollTo(validationMessages[0], {
                    duration: 1,
                    offset: -20
                });
            }
            return;
        }

        // payone integration
        if (!forceSave && payment.currentMethod.indexOf("payone_") === 0) {
            payment.save();
            return;
        }
        // payone integration

        // phoenix ipayment integration
        if (!forceSave && payment.currentMethod.indexOf("ipayment_") === 0) {
            payment.save();
            return;
        }
        // phoenix ipayment integration

        // orgone integration
        if (!forceSave && payment.currentMethod.indexOf("ops_") === 0) {
            payment.save();
            return;
        }
        // orgone integration

        checkout.setLoadWaiting(true);
        var params = Form.serialize(this.form);
        $('review-please-wait').show();

        // braintree integration
        if ("braintree" === payment.currentMethod) {
            if (!braintree) {
                console.log('Error: braintree instance not found');
            } else {
                $('payment_form_braintree').select('input, select').each(function(element) {
                    if (element.readAttribute('data-encrypt') === 'true') {
                        params[element.readAttribute('name')] = braintree.encrypt(element.value);
                    }
                });
            }
        }
        // braintree integration

        urlSuffix = urlSuffix || '';
        var request = new Ajax.Request(this.urls.save + urlSuffix, {
            method:'post',
            parameters:params,
            onSuccess: this.setResponse.bind(this),
            onFailure: this.ajaxFailure.bind(this)
        });
    },

    update: function(url, params) {
        var parameters = $(this.form).serialize(true);
        var self = this;
        if (typeof url == 'object') {
            params = url;
            url = this.urls.update;
        }
        for (var i in params) {
            parameters[i] = params[i];
            if (!params[i]) {
                continue;
            }
            var el = $('checkout-' + i + '-load');
            if (el) {
                el.setOpacity(0.5)
                    .addClassName('loading');
            }
        }
        checkout.setLoadWaiting(true);
        var request = new Ajax.Request(url, {
            method: 'post',
            onSuccess: this.setResponse.bind(this),
            onFailure: this.ajaxFailure.bind(this),
            parameters: parameters
        });
    },

    updateCaptcha: function(id) {
        var captchaEl = $(id);
        if (captchaEl) {
            captchaEl.captcha.refresh(captchaEl.previous('img.captcha-reload'));
        }
    },

    setResponse: function(response){
        try {
            response = response.responseText.evalJSON();
        } catch (err) {
            alert('An error has been occured during request processing. Try again please');
            checkout.setLoadWaiting(false);
            $('review-please-wait').hide();
            return false;
        }

        if (response.redirect) {
            location.href = response.redirect;
            return true;
        }

        if (response.order_created) {
            window.location = this.urls.success;
            return;
        } else {
            var guestCaptcha = $('guest_checkout');
            if (guestCaptcha && guestCaptcha.up('li').visible()) {
                this.updateCaptcha('guest_checkout');
            } else {
                this.updateCaptcha('register_during_checkout');
            }
            if (response.error_messages) {
                var msg = response.error_messages;
                if (typeof(msg) == 'object') {
                    msg = msg.join("\n");
                }
                alert(msg);
            } else if (response.message) {
                var msg = response.message;
                if (typeof(msg) == 'object') {
                    msg = msg.join("\n");
                }
                alert(msg);
            }
        }

        checkout.setLoadWaiting(false);
        $('review-please-wait').hide();

        if (response.update_section) {
            for (var i in response.update_section) {
                var el = $('checkout-' + i + '-load');
                if (el) {
                    var data = {};
                    el.select('input, select').each(function(input) {
                        if ('radio' == input.type || 'checkbox' == input.type) {
                            data[input.id] = input.checked;
                        } else {
                            data[input.id] = input.getValue();
                        }
                    });

                    el.update(response.update_section[i])
                        .setOpacity(1)
                        .removeClassName('loading');

                    if (i == 'coupon-discount' || i == 'giftcard') {
                        continue;
                    }

                    for (var j in data) {
                        if (!j) {
                            continue;
                        }
                        var input = el.down('#' + j);
                        if (input) {
                            if ('radio' == input.type || 'checkbox' == input.type) {
                                input.checked = data[j];
                            } else {
                                input.setValue(data[j]);
                            }
                        }
                    }
                }

                if (i === 'shipping-method') {
                    shippingMethod.addObservers();
                } else if (i === 'review') {
                    this.addCartObservers();
                }
            }
        }

        if ('centinel' == response.method) {
            this.showCentinel();
        }

        // SagePay Server Integration
//        else if ('sagepayserver' === response.method) {
//            var revertStyles = function(el) {
//                el.setStyle({
//                    height: '500px'
//                });
//            }
//            $('sage-pay-server-iframe').observe('load', function() {
//                $$('.d-sh-tl, .d-sh-tr').each(function(el) {
//                    el.setStyle({
//                        height: 'auto'
//                    });
//                    revertStyles.delay(0.03, el);
//                });
//            });
//            sgps_placeOrder();
//        }
        // End of SagePay Server Integration

        else if ('sagepayserver' === response.method
            || 'sagepayform' === response.method
            || 'sagepaydirectpro' === response.method
            || 'sagepaypaypal' === response.method) {

            var SageServer = new EbizmartsSagePaySuite.Checkout({
                //'checkout'  : checkout
            });
            SageServer.code = response.method;
            SageServer.setPaymentMethod();
            SageServer.reviewSave({'tokenSuccess':true});
        }

        if (response.popup) {
            this.showPopup(response.popup);
        } else if (response.body) {
            $(document.body).insert({
                'bottom': response.body.content
            });
        }

        return false;
    },

    showPopup: function(popup) {
        var id = 'firecheckout-window-' + popup.id,
            cnt = $(id);
        if (!cnt) {
            cnt = new Element('div');
            cnt.writeAttribute('id', id);
            cnt.hide();
        }
        cnt.update(popup.content);


        if (popup.window) {
            var wnd = new FireCheckout.Window(popup.window);
        } else {
            var wnd = firecheckoutWindow;
        }

        var oldContent = wnd.content.down();
        oldContent && $(document.body).insert(oldContent.hide());
        wnd.update(cnt)
            .setModal(popup.modal)
            .show();
    },

    showCentinel: function() {
        var oldContent = firecheckoutWindow.content.down();
        oldContent && $(document.body).insert(oldContent.hide());
        firecheckoutWindow
            .update($('checkout-centinel-iframe-load').show())
            .show();
    },

    addCartObservers: function() {
        fireCart.initialize();
    }
};

// billing
var Billing = Class.create();
Billing.prototype = {
    initialize: function(){
        var self = this;

        $('billing:country_id') && $('billing:country_id').observe('change', function() {
            if ($('billing:region_id')) {
                function resetRegionId() {
                    $('billing:region_id').value = '';
                    $('billing:region_id')[0].selected = true;
                }
                resetRegionId.delay(0.2);
            }
            var options       = {},
                sameAsBilling = $('shipping:same_as_billing');

            if (!sameAsBilling || (sameAsBilling && sameAsBilling.checked)) {
                options['shipping-method'] = FireCheckout.prototype.ajax.shipping_method_on_country;
                options['review']          = FireCheckout.prototype.ajax.total_on_shipping_country;
            }
            options['payment-method'] = FireCheckout.prototype.ajax.payment_method_on_country;

            for (var i in options) {
                if (options[i]) {
                    checkout.update(checkout.urls.billing_address, options);
                    break;
                }
            }
        });

        $('billing-address-select') && $('billing-address-select').observe('change', function() {
            var options       = {},
                sameAsBilling = $('shipping:same_as_billing');

            if (!sameAsBilling || (sameAsBilling && sameAsBilling.checked)) {
                options['shipping-method'] = (
                    FireCheckout.prototype.ajax.shipping_method_on_country
                    || FireCheckout.prototype.ajax.shipping_method_on_zip
                    || FireCheckout.prototype.ajax.shipping_method_on_region
                );
                options['review'] = (
                    FireCheckout.prototype.ajax.total_on_shipping_country
                    || FireCheckout.prototype.ajax.total_on_shipping_zip
                    || FireCheckout.prototype.ajax.total_on_shipping_region
                );
            }
            options['payment-method'] = FireCheckout.prototype.ajax.payment_method_on_country;

            for (var i in options) {
                if (options[i]) {
                    checkout.update(checkout.urls.billing_address, options);
                    break;
                }
            }
        });

        $('billing:region_id') && $('billing:region_id').observe('change', function() {
            var options       = {},
                sameAsBilling = $('shipping:same_as_billing');

            if (!sameAsBilling || (sameAsBilling && sameAsBilling.checked)) {
                options['shipping-method'] = FireCheckout.prototype.ajax.shipping_method_on_region;
                options['review']          = FireCheckout.prototype.ajax.total_on_shipping_region;
            }

            for (var i in options) {
                if (options[i]) {
                    checkout.update(checkout.urls.billing_address, options);
                    break;
                }
            }
        });

        $('billing:postcode') && $('billing:postcode').observe('change', function() {
            var options       = {},
                sameAsBilling = $('shipping:same_as_billing');

            if (!sameAsBilling || (sameAsBilling && sameAsBilling.checked)) {
                options['shipping-method'] = FireCheckout.prototype.ajax.shipping_method_on_zip;
                options['review']          = FireCheckout.prototype.ajax.total_on_shipping_zip;
            }

            for (var i in options) {
                if (options[i]) {
                    checkout.update(checkout.urls.billing_address, options);
                    break;
                }
            }
        });

        var createAccount = $('billing:register_account');
        this.setCreateAccount(createAccount ? createAccount.checked : 1); // create account if checkbox is missing
    },

    newAddress: function(isNew){
        if (isNew) {
            this.resetSelectedAddress();
            Element.show('billing-new-address-form');
        } else {
            Element.hide('billing-new-address-form');
        }
    },

    resetSelectedAddress: function(){
        var selectElement = $('billing-address-select')
        if (selectElement) {
            selectElement.value='';
        }
    },

    setCreateAccount: function(flag) {
        if (flag) {
            $('register-customer-password') && $('register-customer-password').show();
            $(document.body).fire('login:setMethod', {method : 'register'});
        } else {
            $('register-customer-password') && $('register-customer-password').hide();
            $(document.body).fire('login:setMethod', {method : 'guest'});
        }
    }
};

// shipping
var Shipping = Class.create();
Shipping.prototype = {
    initialize: function(form) {
        this.form = form;
        if ($('shipping:same_as_billing') && $('shipping:same_as_billing').checked) {
            $('billing:use_for_shipping').value = 1;
        }

        $('shipping:country_id') && $('shipping:country_id').observe('change', function() {
            if ($('shipping:region_id')) {
                function resetRegionId() {
                    $('shipping:region_id').value = '';
                    $('shipping:region_id')[0].selected = true;
                }
                resetRegionId.delay(0.2);
            }
            var options = {
                'shipping-method': FireCheckout.prototype.ajax.shipping_method_on_country,
                'review'         : FireCheckout.prototype.ajax.total_on_shipping_country
            };

            for (var i in options) {
                if (options[i]) {
                    checkout.update(checkout.urls.shipping_address, options);
                    break;
                }
            }
        });

        $('shipping-address-select') && $('shipping-address-select').observe('change', function() {
            var options = {
                'shipping-method': (
                    FireCheckout.prototype.ajax.shipping_method_on_country
                    || FireCheckout.prototype.ajax.shipping_method_on_zip
                    || FireCheckout.prototype.ajax.shipping_method_on_region
                ),
                'review': (
                    FireCheckout.prototype.ajax.total_on_shipping_country
                    || FireCheckout.prototype.ajax.total_on_shipping_zip
                    || FireCheckout.prototype.ajax.total_on_shipping_region
                )
            };

            for (var i in options) {
                if (options[i]) {
                    checkout.update(checkout.urls.shipping_address, options);
                    break;
                }
            }
        });

        $('shipping:region_id') && $('shipping:region_id').observe('change', function() {
            var options = {
                'shipping-method': FireCheckout.prototype.ajax.shipping_method_on_region,
                'review'         : FireCheckout.prototype.ajax.total_on_shipping_region
            };

            for (var i in options) {
                if (options[i]) {
                    checkout.update(checkout.urls.shipping_address, options);
                    break;
                }
            }
        });

        $('shipping:postcode') && $('shipping:postcode').observe('change', function() {
            var options = {
                'shipping-method': FireCheckout.prototype.ajax.shipping_method_on_zip,
                'review'         : FireCheckout.prototype.ajax.total_on_shipping_zip
            };

            for (var i in options) {
                if (options[i]) {
                    checkout.update(checkout.urls.shipping_address, options);
                    break;
                }
            }
        });
    },

    newAddress: function(isNew) {
        if (isNew) {
            this.resetSelectedAddress();
            Element.show('shipping-new-address-form');
        } else {
            Element.hide('shipping-new-address-form');
        }
    },

    resetSelectedAddress: function() {
        var selectElement = $('shipping-address-select')
        if (selectElement) {
            selectElement.value = '';
        }
    },

    setSameAsBilling: function(flag) {
        $('shipping:same_as_billing').checked = flag;
        $('billing:use_for_shipping').value = flag ? 1 : 0;
        this.syncWithBilling();

        if (flag) {
            $('shipping-address').hide();

            if (FireCheckout.prototype.ajax.shipping_method_on_country
                || FireCheckout.prototype.ajax.shipping_method_on_zip
                || FireCheckout.prototype.ajax.shipping_method_on_region) {

                checkout.update(checkout.urls.shipping_address, {
                    'shipping-method': 1
                });
            }
        } else {
            $('shipping-address').show();
        }
    },

    syncWithBilling: function () {
        $('billing-address-select') && this.newAddress(!$('billing-address-select').value);
        // $('shipping:same_as_billing').checked = true;
        // $('billing:use_for_shipping').value = 1;
        if (!$('billing-address-select') || !$('billing-address-select').value) {
            arrElements = $('shipping-address').select('input,select');
            for (var elemIndex in arrElements) {
                if (arrElements[elemIndex].id) {
                    var sourceField = $(arrElements[elemIndex].id.replace(/^shipping:/, 'billing:'));
                    if (sourceField){
                        arrElements[elemIndex].value = sourceField.value;
                    }
                }
            }
            //$('shipping:country_id').value = $('billing:country_id').value;
            shippingRegionUpdater.update();
            $('shipping:region_id').value = $('billing:region_id').value;
            $('shipping:region').value = $('billing:region').value;
            //shippingForm.elementChildLoad($('shipping:country_id'), this.setRegionValue.bind(this));
        } else {
            $('shipping-address-select').value = $('billing-address-select').value;
        }
    },

    setRegionValue: function(){
        $('shipping:region').value = $('billing:region').value;
    }
};

// shipping method
var ShippingMethod = Class.create();
ShippingMethod.prototype = {
    initialize: function() {
        this.addObservers();
    },

    addObservers: function() {
        var self = this;

        this.setCheckedRadios(); // fix for "hide other shipping methods if free is in the list"

        $$('input[name="shipping_method"]').each(function(el) {
            el.observe('click', function() {
                if (FireCheckout.prototype.ajax.total_on_shipping_method) {
                    checkout.update(checkout.urls.shipping_method, {
                        'review': 1
                    });
                }
                /* Storepickup integration */
                var storepickupBox = $('free-location-box');
                if (storepickupBox) {
                    if ('storepickup_storepickup' == this.value) {
                        storepickupBox.show();
                    } else {
                        storepickupBox.hide();
                    }
                }
                /* Storepickup integration */

                /* Relaypoint integration */
                var relaypointBox = $("relaypoint");
                if (relaypointBox) {
                    if ('relaypoint_relaypoint' == this.value) {
                        relaypointBox.show();
                    } else {
                        relaypointBox.hide();
                    }
                }
                /* Relaypoint integration */

                /* Delivery Date */
                if (typeof deliveryDate == 'object') {
                    deliveryDate.toggleDisplay(this.value);
                }
                /* Delivery Date */
            });
        });

        $('shipping-method-reset').stopObserving('click');
        $('shipping-method-reset').observe('click', function() {
            $$('input[name="shipping_method"]').each(function(el) {
                el.checked = '';
            });
            checkout.update(checkout.urls.shipping_method, {
                'review'         : FireCheckout.prototype.ajax.total_on_shipping_method,
                'remove-shipping': 1
            });
        });

        /* Storepickup integration */
        var storepickupRadio = $('s_method_storepickup_storepickup');
        if (storepickupRadio) {
            if (storepickupRadio.checked) {
                $('free-location-box').show();
            } else {
                $('free-location-box').hide();
            }
        }
        /* Storepickup integration */

        /* Relaypoint integration */
        var relaypointRadio = $('s_method_relaypoint_relaypoint');
        if (relaypointRadio) {
            if (relaypointRadio.checked) {
                $("relaypoint").show();
            } else {
                $("relaypoint").hide();
            }
        }
        /* Relaypoint integration */

        /* Delivery Date */
        if (typeof deliveryDate == 'object') {
            deliveryDate.toggleDisplay();
        }
        /* Delivery Date */
    },

    setCheckedRadios: function() {
        $$('[name="shipping_method"]').each(function(el) {
            if (!el.readAttribute('checked')) {
                el.checked = false;
                return;
            }
            el.checked = 'checked';
            el.writeAttribute('checked', 'checked');
        });
    },

    validate: function() {
        FireCheckout.Messenger.clear('checkout-shipping-method-load');
        var methods = document.getElementsByName('shipping_method');
        if (methods.length==0) {
            FireCheckout.Messenger.add(
                Translator.translate('Your order cannot be completed at this time as there is no shipping methods available for it. Please make necessary changes in your shipping address.'),
                'checkout-shipping-method-load',
                'error'
            );
            return false;
        }

        /* Relaypoint integration */
        if (typeof updateshipping == 'function' && typeof relaypointUpdateShippingUrl != 'undefined') {
            if (false === updateshipping(relaypointUpdateShippingUrl)) {
                return false;
            }
        }
        /* Relaypoint integration */

        for (var i=0; i<methods.length; i++) {
            if (methods[i].checked) {
                return true;
            }
        }
        FireCheckout.Messenger.add(
            Translator.translate('Please specify shipping method.'),
            'checkout-shipping-method-load',
            'error'
        );
        return false;
    }
};

// payment
var Payment = Class.create();
Payment.prototype = {
    beforeInitFunc:$H({}),
    afterInitFunc:$H({}),
    beforeValidateFunc:$H({}),
    afterValidateFunc:$H({}),
    initialize: function(container){
        this.cnt = container;
        this.form = 'firecheckout-form';
    },

    save: function() {
        // dummy for phoenix/ipayment
        checkout.setLoadWaiting(false);
        checkout.save('', true); // do not call for ipayment methods anymore
    },

    onSave: function() {
        checkout.setLoadWaiting(false);
        checkout.save('', true);
    },

    onComplete: function() {
        // dummy for payone
    },

    addObservers: function() {
        var self = this;

        $('payment-method-reset').stopObserving('click');
        $('payment-method-reset').observe('click', function() {
            $$('input[name="payment[method]"]').each(function(el) {
                el.checked = '';
            });
            self.switchMethod();
            checkout.update(checkout.urls.payment_method, {
                'review': FireCheckout.prototype.ajax.total_on_payment_method,
                'payment[remove]': 1
            });
        });


        $$('input[name="payment[method]"]').each(function(el) {
            el.observe('click', function() {
                if (FireCheckout.prototype.ajax.total_on_payment_method) {
                    checkout.update(checkout.urls.payment_method, {
                        'review': 1
                    });
                }
                if ('p_method_sagepayserver' != this.id) {
                    $("checkout-sagepay-iframe-load").hide();
                }
            });
        });
    },

    addBeforeInitFunction : function(code, func) {
        this.beforeInitFunc.set(code, func);
    },

    beforeInit : function() {
        (this.beforeInitFunc).each(function(init){
           (init.value)();
        });
    },

    init : function () {
        this.addObservers();
        this.beforeInit();
        var elements = $(this.cnt).select('input', 'select', 'textarea');
        var method = null;
        for (var i=0; i<elements.length; i++) {
            if (elements[i].name=='payment[method]') {
                if (elements[i].checked/* || i == 0*/) {
                    method = elements[i].value;
                }
            } else {
                elements[i].disabled = true;
            }
            elements[i].setAttribute('autocomplete','off');
        }
        if (method) this.switchMethod(method);
        this.afterInit();

        this.initWhatIsCvvListeners();
    },

    addAfterInitFunction : function(code, func) {
        this.afterInitFunc.set(code, func);
    },

    afterInit : function() {
        (this.afterInitFunc).each(function(init){
            (init.value)();
        });
    },

    switchMethod: function(method){
        var elementTypes = ['input', 'select', 'textarea'];

        if (this.currentMethod && $('payment_form_'+this.currentMethod + '_preencrypt')) {
            var form = $('payment_form_'+this.currentMethod + '_preencrypt');
            form.style.display = 'none';
            var elements = [];
            elementTypes.each(function(type) { //IE9 bugfix
                form.select(type).each(function(el) {
                    el.disabled = true;
                    //elements.push(el);
                });
            });
            //var elements = form.select('input', 'select', 'textarea');
            //for (var i=0; i<elements.length; i++) elements[i].disabled = true;
        }

        if (this.currentMethod && $('payment_form_'+this.currentMethod)) {
            var form = $('payment_form_'+this.currentMethod);
            form.style.display = 'none';
            var elements = [];
            elementTypes.each(function(type) { //IE9 bugfix
                form.select(type).each(function(el) {
                    el.disabled = true;
                    //elements.push(el);
                });
            });
            //var elements = form.select('input', 'select', 'textarea');
            //for (var i=0; i<elements.length; i++) elements[i].disabled = true;
        }

        if ($('payment_form_'+method)
            || $('payment_form_' + method + '_preencrypt')) {

            var form = $('payment_form_' + method + '_preencrypt');
            if (form) {
                form.style.display = '';
                var elements = [];
                elementTypes.each(function(type) { //IE9 bugfix
                    form.select(type).each(function(el) {
                        el.disabled = false;
                        //elements.push(el);
                    });
                });
                //var elements = form.select('input', 'select', 'textarea');
                //for (var i=0; i<elements.length; i++) elements[i].disabled = false;
            }

            var form = $('payment_form_'+method);
            if (form) {
                form.style.display = '';
                var elements = [];
                elementTypes.each(function(type) { //IE9 bugfix
                    form.select(type).each(function(el) {
                        el.disabled = false;
                        //elements.push(el);
                    });
                });
                //var elements = form.select('input', 'select', 'textarea');
                //for (var i=0; i<elements.length; i++) elements[i].disabled = false;
            }
        } else {
            //Event fix for payment methods without form like "Check / Money order"
            $(document.body).fire('payment-method:switched', {method_code : method});
        }
        this.currentMethod = method;
    },

    addBeforeValidateFunction : function(code, func) {
        this.beforeValidateFunc.set(code, func);
    },

    beforeValidate : function() {
        var validateResult = true;
        var hasValidation = false;
        (this.beforeValidateFunc).each(function(validate){
            hasValidation = true;
            if ((validate.value)() == false) {
                validateResult = false;
            }
        }.bind(this));
        if (!hasValidation) {
            validateResult = false;
        }
        return validateResult;
    },

    validate: function() {
        FireCheckout.Messenger.clear('checkout-payment-method-load');
        var result = this.beforeValidate();
        if (result) {
            return true;
        }
        var methods = document.getElementsByName('payment[method]');
        if (methods.length==0) {
            FireCheckout.Messenger.add(
                Translator.translate('Your order cannot be completed at this time as there is no payment methods available for it.'),
                'checkout-payment-method-load',
                'error'
            );
            return false;
        }
        for (var i=0; i<methods.length; i++) {
            if (methods[i].checked) {
                return true;
            }
        }
        result = this.afterValidate();
        if (result) {
            return true;
        }
        FireCheckout.Messenger.add(
            Translator.translate('Please specify payment method.'),
            'checkout-payment-method-load',
            'error'
        );
        return false;
    },

    addAfterValidateFunction : function(code, func) {
        this.afterValidateFunc.set(code, func);
    },

    afterValidate : function() {
        var validateResult = true;
        var hasValidation = false;
        (this.afterValidateFunc).each(function(validate){
            hasValidation = true;
            if ((validate.value)() == false) {
                validateResult = false;
            }
        }.bind(this));
        if (!hasValidation) {
            validateResult = false;
        }
        return validateResult;
    },

    initWhatIsCvvListeners: function(){
        $$('.cvv-what-is-this').each(function(element){
            Event.observe(element, 'click', toggleToolTip);
        });
    }
};

var Review = Class.create();
Review.prototype = {
    initialize: function(saveUrl, successUrl, agreementsForm){
        this.saveUrl = saveUrl;
        this.successUrl = successUrl;
        this.agreementsForm = agreementsForm;
        this.onSave = this.nextStep.bindAsEventListener(this);
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    save: function(){
        checkout.save();
    },

    resetLoadWaiting: function(transport){
        checkout.setLoadWaiting(false);
    },

    nextStep: function(transport){
//        if (transport && transport.responseText) {
//            try{
//                response = eval('(' + transport.responseText + ')');
//            }
//            catch (e) {
//                response = {};
//            }
//            if (response.redirect) {
//                this.isSuccess = true;
//                location.href = response.redirect;
//                return;
//            }
//            if (response.success) {
//                this.isSuccess = true;
//                window.location=this.successUrl;
//            }
//            else{
//                var msg = response.error_messages;
//                if (typeof(msg)=='object') {
//                    msg = msg.join("\n");
//                }
//                if (msg) {
//                    alert(msg);
//                }
//            }

//            if (response.update_section) {
//                $('checkout-'+response.update_section.name+'-load').update(response.update_section.html);
//            }

//            if (response.goto_section) {
//                checkout.gotoSection(response.goto_section);
//                checkout.reloadProgressBlock();
//            }
//        }
    },

    isSuccess: false
}

FireCheckout.Messenger = {
    add: function(message, section, type) {
        var section = $(section);
        if (!section) {
            return;
        }
        var ul = section.select('.messages')[0];
        if (!ul) {
            section.insert({
                top: '<ul class="messages"></ul>'
            });
            ul = section.select('.messages')[0]
        }
        var li = $(ul).select('.' + type + '-msg')[0];
        if (!li) {
            $(ul).insert({
                top: '<li class="' + type + '-msg"><ul></ul></li>'
            });
            li = $(ul).select('.' + type + '-msg')[0];
        }
        $(li).select('ul')[0].insert(
            '<li>' + message + '</li>'
        );
    },

    clear: function(section) {
        var section = $(section);
        if (!section) {
            return;
        }
        var ul = section.select('.messages')[0];
        if (ul) {
            ul.remove();
        }
    }
};

FireCheckout.Cart = Class.create();
FireCheckout.Cart.prototype = {
    initialize: function(config) {
        if (config) { // can be called multiple times
            this.config = config;
        }
        this.addQtyObservers();
        this.addDescriptionToggler();
        this.addLinkObservers();
    },

    addQtyObservers: function() {
        var self = this,
            qtyWrappers = $$('.qty-wrapper');

        qtyWrappers.each(function(el) {
            el.observe('mouseover', function(e) {
                qtyWrappers.invoke('removeClassName', 'shown');
                clearTimeout(self.timeout);
                el.addClassName('shown');
            });
            el.observe('mouseout', function(e) {
                self.timeout = setTimeout(Element.removeClassName, 500, el, 'shown');
            });
        });

        $$('.qty-more, .qty-less').each(function(el) {
            el.observe('click', function() {
                var field = this.up('.qty-wrapper').down('.qty'),
                    qty = parseFloat(field.value),
                    inc = (this.hasClassName('qty-more') ? 1 : -1);

                if (isNaN(qty)) {
                    qty = 0;
                }
                qty += inc;
                self.updateQty(field, qty);
            });
        });

        $$('input.qty').each(function(el) {
            el.observe('change', function() {
                self.updateQty(el)
            });
        });
    },

    updateQty: function(field, qty) {
        qty = (qty === undefined ? field.value : qty);
        if (qty <= 0) {
            qty = 0;
            if (!confirm(checkout.translations.productRemoveConfirm)) {
                field.value = field.defaultValue;
                return;
            }
        }

        field.value = qty;

        var options = {'review': 1};
        // collect only changed item to speed up cart update
        options['updated_' + field.name] = field.value;
        options['updated_' + field.previous().name] = field.previous().value;
        checkout.update(checkout.urls.shopping_cart, options);
    },

    addLinkObservers: function() {
        $('checkout-review-table').select('a').each(function(el) {
            if (-1 != el.href.indexOf('giftcard/cart/remove')) {
                el.observe('click', function(e) {
                    if (typeof event != 'undefined') { // ie9 fix
                        event.preventDefault ? event.preventDefault() : event.returnValue = false;
                    }
                    Event.stop(e);
                    var uriParts = this.href.replace(/\/+$/, '').split('/'),
                        code = uriParts[uriParts.length - 1];

                    checkout.update(checkout.urls.giftcard, {
                        'remove_giftcard': 1,
                        'giftcard_code': code,
                        'review': 1
                    });
                    return false;
                });
            } else if (-1 != el.href.indexOf('storecredit/cart/remove')) {
                el.observe('click', function(e) {
                    if (typeof event != 'undefined') { // ie9 fix
                        event.preventDefault ? event.preventDefault() : event.returnValue = false;
                    }
                    Event.stop(e);

                    checkout.update(checkout.urls.paymentdata, {
                        'review': 1,
                        'remove_storecredit': 1
                    });
                    if ($('use_customer_balance')) {
                        $('use_customer_balance').checked = false;
                        var elements = $$('input[name="payment[method]"]');
                        for (var i=0; i<elements.length; i++) {
                            elements[i].disabled = false;
                        }
                        $('checkout-payment-method-load').show();
                        customerBalanceSubstracted = false;
                    }
                    return false;
                });
            } else if (-1 != el.href.indexOf('reward/cart/remove')) {
                el.observe('click', function(e) {
                    if (typeof event != 'undefined') { // ie9 fix
                        event.preventDefault ? event.preventDefault() : event.returnValue = false;
                    }
                    Event.stop(e);

                    checkout.update(checkout.urls.paymentdata, {
                        'review': 1,
                        'remove_rewardpoints': 1
                    });
                    if ($('use_reward_points')) {
                        $('use_reward_points').checked = false;
                        var elements = $$('input[name="payment[method]"]');
                        for (var i=0; i<elements.length; i++) {
                            elements[i].disabled = false;
                        }
                        $('checkout-payment-method-load').show();
                        rewardPointsSubstracted = false;
                    }
                    return false;
                });
            } else if (-1 != el.href.indexOf('ugiftcert/checkout/remove')) {
                el.observe('click', function(e) {
                    if (typeof event != 'undefined') { // ie9 fix
                        event.preventDefault ? event.preventDefault() : event.returnValue = false;
                    }
                    Event.stop(e);
                    var uriParts = this.href.replace(/\/+$/, '').split('/'),
                        code = uriParts[uriParts.length - 1];

                    checkout.update(checkout.urls.coupon, {
                        'remove_ugiftcert': 1,
                        'ugiftcert_code': code,
                        'review': 1
                    });
                    return false;
                });
            }
        });
    },

    addDescriptionToggler: function() {
        var self = this;
        $('checkout-review-table').select('.short-description .std').each(function(el) {
            var description = el.innerHTML,
                i = self.config.descriptionLength,
                letter;

            if (description.length <= i * 1.3) {
                return;
            }

            // we can't place toggler inside these tags
            // if we are inside one of them - try to move the pointer before them
            var tagsToSkip = ['dl', 'ul', 'ol', 'table'],
                begin      = description.substr(0, i),
                openedTags = closedTags = [],
                k = 0,
                tagName;

            while ((tagName = tagsToSkip[k++])) {
                while (begin.length > 10) {
                    openedTags = begin.match(new RegExp('<' + tagName + '.+?>', 'g'));
                    closedTags = begin.match(new RegExp('<' + tagName + '/>', 'g'));

                    if (!openedTags && !closedTags) { // no restricted elements - both regexp doesn't match
                        break;
                    }

                    if (!openedTags || !closedTags // one regexp doesn't match - tag is now closed on this position
                        || openedTags.length != closedTags.length) { // we are inside restricted tags

                        i -= 4;
                        begin = description.substr(0, i);
                    } else {
                        break;
                    }
                }

                if (!openedTags && !closedTags) { // no restricted elements - both regexp doesn't match
                    continue;
                }
                if (!openedTags || !closedTags) { // one regexp doesn't match - tag is now closed on this position
                    return;
                }
                if (openedTags.length != closedTags.length) { // we are inside restricted tags
                    return;
                }
            }

            // make offset to prevent breaking of html tags
            var j = i;
            while ((letter = description[j])) {
                j--;
                if (letter === '<') { // we was inside html tag, need to change the i value
                    i = j + 1;
                    break;
                } else if (letter === '>') { // we was outside html tag
                    break;
                }
            }

            var dots    = '…',
                begin   = description.substr(0, i),
                end     = '<div style="display:none;">' + dots + description.substr(i).replace(/^\s+/, '') + '</div>',
                toggler = '<a href="javascript:" class="description-toggler">' + dots + '</a>';

            el.update(begin + toggler + end);

            el.down('.description-toggler').observe('click', function(e) {
                self.toggleDescription(this);
            });
        });
    },

    toggleDescription: function(toggler) {
        var el = $(toggler).next();
        if (toggler.hasClassName('active')) {
            el.hide();
            $(toggler).removeClassName('active');
        } else {
            el.show();
            $(toggler).addClassName('active');
        }
    }
};

FireCheckout.Window = Class.create();
FireCheckout.Window.prototype = {
    initialize: function(config) {
        this.config = Object.extend({
            triggers: null,
            markup:
                '<div class="d-shadow-wrap">'
                +   '<div class="content"></div>'
                +   '<div class="d-sh-cn d-sh-tl"></div><div class="d-sh-cn d-sh-tr"></div>'
                + '</div>'
                + '<div class="d-sh-cn d-sh-bl"></div><div class="d-sh-cn d-sh-br"></div>'
                + '<a href="javascript:void(0)" class="close"></a>'
        }, config || {});
        this.config.size = Object.extend({
            width    : 'auto',
            height   : 'auto',
            maxWidth : 550,
            maxHeight: 600
        }, this.config.size || {});

        this._prepareMarkup();
        this._attachEventListeners();
    },

    show: function() {
        if (!this.centered) {
            this.center();
        }
        $$('select').invoke('addClassName', 'firecheckout-hidden');

        if (!$('firecheckout-mask')) {
            var mask = new Element('div');
            mask.writeAttribute('id', 'firecheckout-mask');
            var body    = document.body,
                element = document.documentElement,
                height  = Math.max(
                    Math.max(body.scrollHeight, element.scrollHeight),
                    Math.max(body.offsetHeight, element.offsetHeight),
                    Math.max(body.clientHeight, element.clientHeight)
                );
            mask.setStyle({
                height: height + 'px'
            });
            $(document.body).insert(mask);
        }

        if (!window.firecheckoutMaskCounter) {
            window.firecheckoutMaskCounter = 0;
        }
        if (!this.maskCounted) {
            this.maskCounted = 1;
            window.firecheckoutMaskCounter++;
        }

        // set highest z-index
        var zIndex = 999;
        $$('.firecheckout-window').each(function(el) {
            maxIndex = parseInt(el.getStyle('zIndex'));
            if (zIndex < maxIndex) {
                zIndex = maxIndex;
            }
        });
        this.window.setStyle({
            'zIndex': zIndex + 1
        });

        this._onKeyPressBind = this._onKeyPress.bind(this);
        document.observe('keyup', this._onKeyPressBind);
        this.window.show();
    },

    hide: function() {
        if (this.modal || !this.window.visible()) {
            return;
        }

        if (this._onKeyPressBind) {
            document.stopObserving('keyup', this._onKeyPressBind);
        }
        if (this.config.destroy) {
            this.window.remove();
        } else {
            this.window.hide();
        }
        this.maskCounted = 0;
        if (!--window.firecheckoutMaskCounter) {
            $('firecheckout-mask') && $('firecheckout-mask').remove();
            $$('select').invoke('removeClassName', 'firecheckout-hidden');
        }
    },

    setModal: function(flag) {
        this.modal = flag;

        if (flag) {
            this.window.select('.close').invoke('hide');
        } else {
            this.window.select('.close').invoke('show');
        }
        return this;
    },

    update: function(content, size) {
        var oldContent = this.content.down();
        oldContent && $(document.body).insert(oldContent.hide());

        this.content.update(content);
        content.show();
        this.addActionBar();
        this.updateSize(size);
        this.center();
        return this;
    },

    addActionBar: function() {
        this.removeActionBar();

        var agreementId = this.content.down().id.replace('-window', ''),
            trigger     = this.config.triggers[agreementId];

        if (!trigger || !trigger.actionbar) {
            return;
        }

        this.content.insert({
            after: '<div class="actionbar">' + trigger.actionbar.html + '</div>'
        });
        $(trigger.actionbar.el).observe(
            trigger.actionbar.event,
            trigger.actionbar.callback.bindAsEventListener(this, agreementId.replace('firecheckout-', ''))
        );
    },

    removeActionBar: function() {
        var agreementId = this.content.down().id.replace('-window', ''),
            trigger     = this.config.triggers[agreementId];

        if (trigger && trigger.actionbar) {
            var actionbar = $(trigger.actionbar.el);
            if (actionbar) {
                actionbar.stopObserving(trigger.actionbar.event);
            }
        }

        this.window.select('.actionbar').invoke('remove');
    },

    getActionBar: function() {
        return this.window.down('.actionbar');
    },

    center: function() {
        var viewportSize   = document.viewport.getDimensions(),
            viewportOffset = document.viewport.getScrollOffsets(),
            shadowWrap     = this.window.down('.d-shadow-wrap'),
            windowSize     = this.window.getDimensions(),
            left, top;

        if ('undefined' === typeof viewportSize.width) { // mobile fix. not sure is this check is good enough.
            top  = viewportOffset.top + 20;
            left = viewportOffset.left;
        } else {
            top = viewportSize.height / 2
                - windowSize.height / 2
                + viewportOffset.top
                + parseInt(shadowWrap.getStyle('margin-top'))
                + parseInt(shadowWrap.getStyle('padding-top')),
            left = viewportSize.width / 2
                - windowSize.width / 2
                + viewportOffset.left
                + parseInt(shadowWrap.getStyle('margin-left'))
                + parseInt(shadowWrap.getStyle('padding-left'));

            if (left < viewportOffset.left || windowSize.width > viewportSize.width) {
                left = viewportOffset.left;
            } else {
                left -= 20; /* right shadow */
            }
            top = (top < viewportOffset.top  ? (20 + viewportOffset.top) : top)
        }

        this.setPosition(left, top);
        this.centered = true;

        return this;
    },

    setPosition: function(x, y) {
        this.window.setStyle({
            left: x + 17 /* left border */ + 'px',
            top : y + 'px'
        });

        return this;
    },

    activate: function(trigger) {
        var trigger = this.config.triggers[trigger];
        this.update(trigger.window.show(), trigger.size).show();
    },

    updateSize: function(sizeConfig) {
        sizeConfig = sizeConfig || this.config.size;
        // reset previous size
        this.window.setStyle({
            width : 'auto',
            height: 'auto',
            left  : 0, /* thin content box fix while page is scrolled to the right */
            top   : 0
        });
        this.content.setStyle({
            width : isNaN(sizeConfig.width)  ? sizeConfig.width  : sizeConfig.width + 'px',
            height: isNaN(sizeConfig.height) ? sizeConfig.height : sizeConfig.height + 'px'
        });

        this.window.setStyle({
            visibility: 'hidden'
        }).show();

        var width        = this.content.getWidth() + 100, /* right shadow and borders */
            viewportSize = document.viewport.getDimensions();

        sizeConfig = Object.extend(this.config.size, sizeConfig || {});
        if ('auto' === sizeConfig.width
            && (width > sizeConfig.maxWidth || width > viewportSize.width)) {

            if (width > viewportSize.width && viewportSize.width < (sizeConfig.maxWidth + 100)) {
                width = viewportSize.width - 100; /* right shadow and borders */
            } else {
                width = sizeConfig.maxWidth;
            }
            this.content.setStyle({
                width: width + 'px'
            });
        }

        var actionbar       = this.getActionBar(),
            actionbarHeight = actionbar ? actionbar.getHeight() : 0,
            height          = this.content.getHeight() + actionbarHeight + 20 /* top button */;
        if ('auto' === sizeConfig.height
            && (height > sizeConfig.maxHeight || height > viewportSize.height)) {

            if (height > viewportSize.height && viewportSize.height < (sizeConfig.maxHeight + actionbarHeight + 20)) {
                height = viewportSize.height - 60; /* bottom shadow */
            } else {
                height = sizeConfig.maxHeight;
            }
            height -= actionbarHeight;
            this.content.setStyle({
                height: height + 'px'
            });
        }

        // update window size. Fix for all IE browsers
        var paddingHorizontal = parseInt(this.content.getStyle('padding-left')) + parseInt(this.content.getStyle('padding-right')),
            paddingVertical   = parseInt(this.content.getStyle('padding-top')) + parseInt(this.content.getStyle('padding-bottom'));
        this.window.hide()
            .setStyle({
                width     : width + paddingHorizontal + 'px',
//                height    : height + paddingVertical + 'px',
                visibility: 'visible'
            });

        return this;
    },

    _prepareMarkup: function() {
        this.window = new Element('div');
        this.window.addClassName('firecheckout-window');
        this.window.update(this.config.markup).hide();
        this.content = this.window.select('.content')[0];
        this.close   = this.window.select('.close')[0];
        $(document.body).insert(this.window);
    },

    _attachEventListeners: function() {
        // close window
        this.close.observe('click', this.hide.bind(this));
        // show window
        if (this.config.triggers) {
            for (var i in this.config.triggers) {
                var trigger = this.config.triggers[i];
                if (typeof trigger === 'function') {
                    continue;
                }
                trigger.size = trigger.size || {};
                for (var j in this.config.size) {
                    if (trigger.size[j]) {
                        continue;
                    }
                    trigger.size[j] = this.config.size[j];
                }

                trigger.el.each(function(el) {
                    var t = trigger;
                    el.observe(t.event, function(e) {
                        if (typeof event != 'undefined') { // ie9 fix
                            event.preventDefault ? event.preventDefault() : event.returnValue = false;
                        }
                        Event.stop(e);
                        if (!t.window) {
                            return;
                        }
                        this.update(t.window, t.size).show();
                    }.bind(this));
                }.bind(this));
            }
        }
    },

    _onKeyPress: function(e) {
        if (e.keyCode == 27) {
            this.hide();
        }
    }
};

FireCheckout.isIE9 = function() {
    return Prototype.Browser.IE && parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf("MSIE")+5)) == 9;
};

FireCheckout.Taxvat = {

    patterns: {
        'AT': /^U[0-9]{8}$/,
        'BE': /^0?[0-9]{*}$/,
        'CZ': /^[0-9]{8,10}$/,
        'DE': /^[0-9]{9}$/,
        'CY': /^[0-9]{8}[A-Z]$/,
        'DK': /^[0-9]{8}$/,
        'EE': /^[0-9]{9}$/,
        'GR': /^[0-9]{9}$/,
        'ES': /^[0-9A-Z][0-9]{7}[0-9A-Z]$/,
        'FI': /^[0-9]{8}$/,
        'FR': /^[0-9A-Z]{2}[0-9]{9}$/,
        'GB': /^([0-9]{9}|[0-9]{12})~(GD|HA)[0-9]{3}$/,
        'UK': /^([0-9]{9}|[0-9]{12})~(GD|HA)[0-9]{3}$/,
        'HU': /^[0-9]{8}$/,
        'IE': /^[0-9][A-Z0-9\\+\\*][0-9]{5}[A-Z]$/,
        'IT': /^[0-9]{11}$/,
        'LT': /^([0-9]{9}|[0-9]{12})$/,
        'LU': /^[0-9]{8}$/,
        'LV': /^[0-9]{11}$/,
        'MT': /^[0-9]{8}$/,
        'NL': /^[0-9]{9}B[0-9]{2}$/,
        'PL': /^[0-9]{10}$/,
        'PT': /^[0-9]{9}$/,
        'SE': /^[0-9]{12}$/,
        'SI': /^[0-9]{8}$/,
        'SK': /^[0-9]{10}$/
    },

    isValid: function(number, countryCode) {
        var pattern = this.patterns[countryCode];
        if (!pattern) {
            // this.message = 'The provided CountryCode is invalid for the VAT number';
            return false;
        }

        number = number.replace(countryCode, '');
        if (!number.match(pattern)) {
            // this.message = 'Invalid VAT number';
            return false;
        }

        return true;
    }
};

Function.prototype.firecheckoutInterceptor = function(fcn, scope) {
    var method = this;
    return (typeof fcn !== 'function') ?
        this :
        function() {
            var me   = this,
                args = arguments;
            fcn.target = me;
            fcn.method = method;
            return (fcn.apply(scope || me || window, args) !== false) ?
                method.apply(me || window, args) :
                null;
        };
}

FireCheckout.DeliveryDate = Class.create();
FireCheckout.DeliveryDate.prototype = {

    config: {
        excludedDates     : [],
        periodicalDates   : [],
        nonPeriodicalDates: [],
        excludeWeekend    : 0,
        useCalendar       : 1,
        weekendDays       : '0,6',
        ifFormat          : '%m/%e/%Y',
        shippingMethods   : [],
        todayOffset       : 0
    },

    initialize: function(options) {
        Object.extend(this.config, options);
        var today = new Date();
        today.setDate(today.getDate() + this.config.todayOffset);
        today.setHours(0);
        today.setMinutes(0);
        today.setSeconds(0);
        today.setMilliseconds(0);
        this.today = today;

        if (this.config.useCalendar) {
            var label = $('shipping_form_delivery_date').down('.delivery-date label'),
                html  = label.innerHTML,
                date = new Date();

            date.setDate(25);
            date.setMonth(10);

            label.update(html.replace('{{date}}', date.print(this.config.ifFormat)));
            this.initCalendar();
        }

        this.toggleDisplay();
    },

    toggleDisplay: function(shippingMethod) {
        if (!this.config.shippingMethods.length) {
            return;
        }

        if (!shippingMethod) {
            $$('input[name="shipping_method"]').each(function(el) {
                if (el.checked || el.readAttribute('checked') === 'checked') {
                    shippingMethod = el.value;
                    return;
                }
            });
        }

        if (-1 === this.config.shippingMethods.indexOf(shippingMethod)) {
            $('shipping_form_delivery_date').hide();
        } else {
            $('shipping_form_delivery_date').show();
        }
    },

    initCalendar: function() {
        var self = this;

        Calendar.prototype._init = Calendar.prototype._init.firecheckoutInterceptor(
            function(firstDayOfWeek, date) {
                if ('delivery_date' === this.params.inputField.id) {
                    return self.shiftToFirstAvailableDate(date);
                }
            }
        );

        Calendar.setup({
            inputField : 'delivery_date',
            ifFormat   : this.config.ifFormat,
            weekNumbers: false,
            button     : 'delivery_date_button',
            // hiliteToday: false,
            showOthers : true,
            range      : [
                self.today.getFullYear(),
                self.today.getFullYear() + 1
            ],
            disableFunc: function(date) {
                if (self.today > date) {
                    return true;
                }

                if (self.config.excludeWeekend && self.config.weekendDays.indexOf(date.getDay()) >= 0) {
                    return true;
                }

                var i     = 0,
                    sDate = self.dateToString(date);
                while (self.config.excludedDates[i]) {
                    if (0 === sDate.indexOf(self.config.excludedDates[i])) {
                        return true;
                    }
                    i++;
                }
                return false;
            }
        });
    },

    shiftToFirstAvailableDate: function(date) {
        var i = 0,
            c = this.config;

        while (-1 != c.nonPeriodicalDates.indexOf(this.dateToString(date))
            || -1 != c.periodicalDates.indexOf(this.dateToString(date).substr(0, 7))
            || (c.excludeWeekend && -1 != c.weekendDays.indexOf(date.getDay()))
            || this.today > date) {

            date.setDate(date.getDate() + 1);
            if (i++ >= 365) {
                // possible continious loop fix
                // alert('There are no avaialable dates for the possible delivery within ' + i + ' days');
                return false;
            }
        }

        return true;
    },

    dateToString: function(date) {
        var year  = date.getFullYear(),
            month = date.getMonth() + 1,
            day   = date.getDate(),
            month = month < 10 ? '0' + month : month,
            day   = day   < 10 ? '0' + day   : day;

        return month + '/' + day + '/' + year;
    }
};

FireCheckout.OrderReview = Class.create();
FireCheckout.OrderReview.prototype = {
    skip: {
        'shipping-address': {
            'billing[email]': 1
        }
    },

    initialize: function(options) {
        var self = this;

        this.options   = options;
        this.container = new Element('div');
        this.container.writeAttribute('id', 'addresses-review');
        this.container.writeAttribute('class', 'col2-set');
        this.container.insert(
            '<div id="billing-address-review" class="col-1"></div>'
            + '<div id="shipping-address-review" class="col-2"></div>'
            + '<div id="payment-method-review" class="col-1"></div>'
            + '<div class="clearer"></div>'
        );
        $('checkout-review-load').insert({
            before: this.container
        });
        this.update('billing-address');
        this.update('shipping-address');
        this.update('payment-method');

        $('billing-address') && $('billing-address').select('input:text, select, textarea').each(function(el) {
            el.observe('change', function() {
                self.update('billing-address');
                if ($('shipping:same_as_billing') && $('shipping:same_as_billing').checked) {
                    self.update('billing-address', 'shipping-address');
                }
            });
        });

        $('shipping-address') && $('shipping-address').select('input:text, select, textarea').each(function(el) {
            el.observe('change', function() {
                self.update('shipping-address');
            });
        });

        $(document).observe('change', function(e) {
            var el = e.element();
            if (el.name !== 'payment[method]') {
                return;
            }
            self.update('payment-method');
        });

        $('payment-method').select('input:radio').each(function(el) {
            el.observe('click', function() {
                self.update('payment-method');
            });
        });
    },

    update: function(from, to) {
        to = to || from;
        var review = $(to + '-review');
        review && review.update(this.getContent(from, to));
    },

    getContent: function(from, to) {
        to = to || from;

        var html = '',
            self = this;

        if ('payment-method' === from) {
            if (payment.currentMethod) {
                var radio = $('p_method_' + payment.currentMethod);
                if (radio) {
                    var title = radio.readAttribute('title');
                    if (!title) {
                        var label = radio.up('dt').down('label');
                        title = label.innerHTML;
                    }
                    html += title;
                }
            }
        } else {
            var from = $(from);
            if (!from) {
                return '';
            }
            var addressSelect = from.down('.address-select');
            if (addressSelect) {
                var option = addressSelect.options[addressSelect.selectedIndex];
                if (option && option.value) {
                    return self.getTitle(to) + option.innerHTML.replace(/, /g, '<br/>');
                }
            }

            from.down('fieldset').select('li').each(function(li) {
                var br = '';
                li.select('input:text, select, textarea').each(function(el) {
                    if (self.skip[to] && self.skip[to][el.name]) {
                        return;
                    }
                    if (!el.visible() || el.type === 'hidden' || el.type === 'checkbox') {
                        return;
                    }
                    if (!el.value.length) {
                        return;
                    }

                    if (el.tagName.toLowerCase() == 'select') {
                        var option = el.options[el.selectedIndex];
                        if (!option.value) {
                            return;
                        }
                        html += option.innerHTML + ' ';
                    } else {
                        html += el.value + ' ';
                    }

                    br = '</br>';
                });
                html += br;
            });
        }
        if (html.length) {
            html = self.getTitle(to) + html;
        }
        return html;
    },

    getTitle: function(block) {
        var title = $(block).select('.block-title span'),
            i     = ('payment-method' === block ? 2 : 1);

        //onclick="$(\'' + block + '\').scrollTo();"
        return '<strong>' + title[title.length - i].innerHTML + '</strong>'
            + ' <a href="#'+block+'">' + this.options.changeText + '</a>'
            + '<br/>';
    }
}


if (typeof CentinelAuthenticate == 'function') {
    CentinelAuthenticate.prototype._hideRelatedBlocks = function() {};
    CentinelAuthenticate.prototype._showRelatedBlocks = function() {};

    CentinelAuthenticate.prototype.success = function()
    {
        if (this._isRelatedBlocksLoaded() && this._isCentinelBlocksLoaded()) {
            this._showRelatedBlocks();
            $(this.centinelBlockId).hide();
            this._isAuthenticationStarted = false;

            firecheckoutWindow.hide();
            checkout.save();
        }
    };

    CentinelAuthenticate.prototype.cancel = function()
    {
        if (this._isAuthenticationStarted) {
            if (this._isRelatedBlocksLoaded()) {
                this._showRelatedBlocks();
                firecheckoutWindow.hide();
            }
            if (this._isCentinelBlocksLoaded()) {
                $(this.centinelBlockId).hide();
                $(this.iframeId).src = '';
            }
            this._isAuthenticationStarted = false;
        }
    };
}

//Varien.DateElement.prototype.validate = function() {
//    // var error = false,
//        // day = parseInt(this.day.value.replace(/^0*/, '')) || 0,
//        // month = parseInt(this.month.value.replace(/^0*/, '')) || 0,
//        // year = parseInt(this.year.value) || 0;
//    var error = false,
//        day = parseInt(this.day.value, 10) || 0,
//        month = parseInt(this.month.value, 10) || 0,
//        year = parseInt(this.year.value, 10) || 0;
//    if (!day && !month && !year) {
//        if (this.required) {
//            error = 'This date is a required value.';
//        } else {
//            this.full.value = '';
//        }
//    } else if (!day || !month || !year) {
//        error = 'Please enter a valid full date.';
//    } else {
//        var date = new Date, countDaysInMonth = 0, errorType = null;
//        date.setYear(year);date.setMonth(month-1);date.setDate(32);
//        countDaysInMonth = 32 - date.getDate();
//        if(!countDaysInMonth || countDaysInMonth>31) countDaysInMonth = 31;

//        if (day<1 || day>countDaysInMonth) {
//            errorType = 'day';
//            error = 'Please enter a valid day (1-%d).';
//        } else if (month<1 || month>12) {
//            errorType = 'month';
//            error = 'Please enter a valid month (1-12).';
//        } else {
//            if(day % 10 == day) this.day.value = '0'+day;
//            if(month % 10 == month) this.month.value = '0'+month;
//            this.full.value = this.format.replace(/%[mb]/i, this.month.value).replace(/%[de]/i, this.day.value).replace(/%y/i, this.year.value);
//            var testFull = this.month.value + '/' + this.day.value + '/'+ this.year.value;
//            var test = new Date(testFull);
//            if (isNaN(test)) {
//                error = 'Please enter a valid date.';
//            } else {
//                this.setFullDate(test);
//            }
//        }
//        var valueError = false;
//        if (!error && !this.validateData()){//(year<1900 || year>curyear) {
//            errorType = this.validateDataErrorType;//'year';
//            valueError = this.validateDataErrorText;//'Please enter a valid year (1900-%d).';
//            error = valueError;
//        }
//    }

//    if (error !== false) {
//        try {
//            error = Translator.translate(error);
//        }
//        catch (e) {}
//        if (!valueError) {
//            this.advice.innerHTML = error.replace('%d', countDaysInMonth);
//        } else {
//            this.advice.innerHTML = this.errorTextModifier(error);
//        }
//        this.advice.show();
//        return false;
//    }

//    // fixing elements class
//    this.day.removeClassName('validation-failed');
//    this.month.removeClassName('validation-failed');
//    this.year.removeClassName('validation-failed');

//    this.advice.hide();
//    return true;
//}

/* SagePay Suite Integration */
if (typeof EbizmartsSagePaySuite != 'undefined'
    && typeof EbizmartsSagePaySuite.Checkout == 'function') {

    EbizmartsSagePaySuite.Checkout.prototype.setPaymentMethod = function() {

        // Remove Server InCheckout iFrame if exists
        if($('sagepaysuite-server-incheckout-iframe')) {
            $('checkout-review-submit').show();
            $('sagepaysuite-server-incheckout-iframe').remove();
        }

        if(this.isServerPaymentMethod()) {

            if(parseInt(SuiteConfig.getConfig('global','token_enabled')) === 1) {

                $('sagepayserver-dummy-link').writeAttribute('href', SuiteConfig.getConfig('server','new_token_url'));
                if(this.isServerTokenTransaction()) {
                    return;
                }
                var lcontwmt = new Element('div', {
                    className: 'lcontainer'
                });
                var heit = parseInt(SuiteConfig.getConfig('server','token_iframe_height'))+80;
                lcontwmt.setStyle({
                    'height':heit.toString() + 'px'
                });

                var wmt = new Control.Modal('sagepayserver-dummy-link', {
                    className: 'modal',
                    iframe: true,
                    closeOnClick: false,
                    insertRemoteContentAt: lcontwmt,
                    height: SuiteConfig.getConfig('server','token_iframe_height'),
                    width: SuiteConfig.getConfig('server','token_iframe_width'),
                    fade: true,
                    afterClose: function() {
                        this.getTokensHtml();
                    }.bind(this)
                })
                wmt.container.insert(lcontwmt);

                wmt.container.down().insert(this.getServerSecuredImage());
                wmt.container.setStyle({
                    'height':heit.toString() + 'px'
                });
                wmt.open();

                if(this.getConfig('checkout')) {
                    this.getConfig('checkout').accordion.openSection('opc-payment');
                }
                return;
            }

        } else if(this.isDirectPaymentMethod() && parseInt(SuiteConfig.getConfig('global','token_enabled')) === 1) {

            if(this.isDirectTokenTransaction()) {
                return;
            }

            // try {
            //     if(new Validation(this.getConfig('payment').form).validate() === false) {
            //         return;
            //     }
            // } catch(one) {
            // }
            //
            // if(this.getConfig('osc')) {
            //     var valOsc = new VarienForm('onestepcheckout-form').validator.validate();
            //     if(!valOsc) {
            //         return;
            //     }
            // }

            var pmntForm = $('firecheckout-form');
            new Ajax.Request(SuiteConfig.getConfig('direct','sgps_registerdtoken_url'), {
                method:"post",
                parameters: Form.serialize(pmntForm),
                onSuccess: function(f) {

                    try {
                        this.getTokensHtml();

                        var d=f.responseText.evalJSON();

                        if(d.response_status=="INVALID"||d.response_status=="MALFORMED"||d.response_status=="ERROR"||d.response_status=="FAIL") {
                            if(this.getConfig('checkout')) {
                                this.getConfig('checkout').accordion.openSection('opc-payment');
                            }
                            this.growlWarn("An error ocurred with Sage Pay Direct:\n" + d.response_status_detail.toString());

                            // if(this.getConfig('osc')) {
                            //     $('onestepcheckout-place-order').removeClassName('grey').addClassName('orange');
                            //     $$('div.onestepcheckout-place-order-loading').invoke('remove');
                            //     return;
                            // }

                        } else if(d.response_status == 'threed') {
                            $('sagepaydirectpro-dummy-link').writeAttribute('href', d.url);
                        }

                        // this.reviewSave({
                        //     'tokenSuccess':true
                        // });
                        return;

                    } catch(alfaEr) {

                        if(this.getConfig('checkout')) {
                            this.getConfig('checkout').accordion.openSection('opc-payment');
                        }
                        this.growlError(f.responseText.toString());
                    }

                }.bind(this)
            });

        }

    };
    EbizmartsSagePaySuite.Checkout.prototype.reviewSave = function(transport) {
        if((typeof transport) == 'undefined') {
            var transport = {};
        }
        if((typeof transport.responseText) == 'undefined') { // Firecheckout fix

            //Stop form submission
            //Event.stop(transport);

            if(this.isFormPaymentMethod()) {
                setLocation(SuiteConfig.getConfig('form','url'));
                return;
            }

            if((this.isDirectPaymentMethod() || this.isServerPaymentMethod()) && parseInt(SuiteConfig.getConfig('global','token_enabled')) === 1) {
                if((typeof transport.tokenSuccess) == 'undefined') {
                    this.setPaymentMethod();

                    if(!this.isDirectTokenTransaction() && !this.isServerTokenTransaction()) {
                        return;
                    }
                }
            }

            //var already_placing_order = false;

            //var form = new VarienForm('onestepcheckout-form');

            if(false) {
                //Event.stop(transport);
                return;
            } else {

                if(/*parseInt($$('div.onestepcheckout-place-order-loading').length) || */(typeof transport.tokenSuccess != 'undefined' && true === transport.tokenSuccess)) {

                    if(Ajax.activeRequestCount > 1 && (typeof transport.tokenSuccess) == 'undefined') {
                        return;
                    }
                    var slPayM = this.getPaymentMethod();

                    if(slPayM == this.paypalcode) {
                        setLocation(SuiteConfig.getConfig('paypal', 'redirect_url'));
                        return;
                    }

                    var formData = Form.serialize($('firecheckout-form'), true);
                    var params = {};
                    for (var i in formData) {
                        if (i.indexOf('billing') == 0) { // sagepay think that onestepcheckout is active if billing was supplied
                            continue;
                        }
                        params[i] = formData[i];
                    }

                    if(slPayM == this.servercode || slPayM == this.directcode) {
                        checkout.setLoadWaiting(true);
                        $('review-please-wait').show();
                        new Ajax.Request(SuiteConfig.getConfig('global', 'sgps_saveorder_url'), {
                            method:"post",
                            parameters: params,
                            onSuccess: function(f) {
                                checkout.setLoadWaiting(false);
                                $('review-please-wait').hide();
                                this.reviewSave(f);
                            }.bind(this)
                        });
                        return;
                    } else {
                        alert('unknown method');
                        // $('onestepcheckout-form')._submit();
                        return;
                    }

                } else {
                    return;
                }
            }

        } else {
            try {
                var response = this.evalTransport(transport);
            } catch(notv) {
                suiteLogError(notv);
            }
        }

        if((typeof response.response_status != 'undefined') && response.response_status != 'OK' && response.response_status != 'threed') {
            this.growlWarn("An error ocurred with Sage Pay:\n" + response.response_status_detail.toString());
            return;
        }

        if(/*this.getConfig('osc') && */response.success && response.response_status == 'OK' && (typeof response.next_url == 'undefined')) {
            setLocation(SuiteConfig.getConfig('global','onepage_success_url'));
            return;
        }

        if(!response.redirect || !response.success) {
            this.getConfig('review').nextStep(transport);
            return;
        }

        if(this.isServerPaymentMethod()) {

            $('sagepayserver-dummy-link').writeAttribute('href', response.redirect);

            var rbButtons = $('review-buttons-container');

            var lcont = new Element('div', {
                className: 'lcontainer'
            });
            var heit = parseInt(SuiteConfig.getConfig('server','iframe_height'));
            if ('live' != SuiteConfig.getConfig('server','mode')) {
                heit = heit-65;
            }

            var wtype = SuiteConfig.getConfig('server','payment_iframe_position').toString();
            if (wtype == 'modal') {
                var wm = new Control.Modal('sagepayserver-dummy-link', {
                    className: 'modal',
                    iframe: true,
                    closeOnClick: false,
                    insertRemoteContentAt: lcont,
                    height: SuiteConfig.getConfig('server','iframe_height'),
                    width: SuiteConfig.getConfig('server','iframe_width'),
                    fade: true,
                    afterOpen: function() {
                        if(rbButtons) {
                            rbButtons.addClassName('disabled');
                        }
                    },
                    afterClose: function() {
                        if(rbButtons) {
                            rbButtons.removeClassName('disabled');
                        }
                    }
                });
                wm.container.insert(lcont);
                wm.container.down().setStyle({
                    'height':heit.toString() + 'px'
                });
                wm.container.down().insert(this.getServerSecuredImage());
                wm.open();
            } else if (wtype == 'incheckout') {
                var iframeId = 'sagepaysuite-server-incheckout-iframe';
                var paymentIframe = new Element('iframe', {
                    'src': response.redirect,
                    'id': iframeId
                });

                $('firecheckout-form').insert({
                    after: paymentIframe
                });
                $('firecheckout-form').hide();
                $(iframeId).scrollTo();
            }
        } else if (this.isDirectPaymentMethod() && (typeof response.response_status != 'undefined') && response.response_status == 'threed') {

            $('sagepaydirectpro-dummy-link').writeAttribute('href', response.redirect);

            var lcontdtd = new Element('div', {
                className: 'lcontainer'
            });
            var dtd = new Control.Modal('sagepaydirectpro-dummy-link', {
                className: 'modal sagepaymodal',
                closeOnClick: false,
                insertRemoteContentAt: lcontdtd,
                iframe: true,
                height: SuiteConfig.getConfig('direct','threed_iframe_height'),
                width: SuiteConfig.getConfig('direct','threed_iframe_width'),
                fade: true,
                afterOpen: function() {
                    try {
                        var daiv = this.container;

                        if($$('.sagepaymodal').length > 1) {
                            $$('.sagepaymodal').each( function(elem) {
                                if(elem.visible()) {
                                    daiv = elem;
                                    throw $break;
                                }
                            });
                        }

                        daiv.down().down('iframe').insert({
                            before:new Element('div', {
                                'id':'sage-pay-direct-ddada',
                                'style':'background:#FFF'
                            }).update(
                            SuiteConfig.getConfig('direct','threed_after').toString() + SuiteConfig.getConfig('direct','threed_before').toString())
                        });

                    } catch(er) {

                    }

                    if(false === Prototype.Browser.IE) {
                        daiv.down().down('iframe').setStyle({
                            'height':(parseInt(daiv.down().getHeight())-60)+'px'
                        });
                        daiv.setStyle({
                            'height':(parseInt(daiv.down().getHeight())+57)+'px'
                        });
                    } else {
                        daiv.down().down('iframe').setStyle({
                            'height':(parseInt(daiv.down().getHeight())+116)+'px'
                        });
                    }

                },
                afterClose: function() {
                    var sect = checkout.accordion.currentSection;
                    $('sage-pay-direct-ddada').remove();
                    $('sagepaydirectpro-dummy-link').writeAttribute('href', '');
                }
            });
            dtd.container.insert(lcontdtd);
            dtd.open();

        } else if(this.isDirectPaymentMethod()) {
            new Ajax.Request(SuiteConfig.getConfig('direct','sgps_registertrn_url'), {
                onSuccess: function(f) {

                    try {

                        var d=f.responseText.evalJSON();

                        if(d.response_status=="INVALID"||d.response_status=="MALFORMED"||d.response_status=="ERROR"||d.response_status=="FAIL") {
                            this.getConfig('checkout').accordion.openSection('opc-payment');
                            this.growlWarn("An error ocurred with Sage Pay Direct:\n" + d.response_status_detail.toString());
                        } else if(d.response_status == 'threed') {
                            $('sagepaydirectpro-dummy-link').writeAttribute('href', d.url);
                        }

                    } catch(alfaEr) {
                        this.growlError(f.responseText.toString());
                    }

                }.bind(this)
            });
        } else {
            this.getConfig('review').nextStep(transport);
            return;
        }
    };
}
/* SagePay Suite Integration */

/* SagePay Server Integration */
registerTransaction= function() {
    if($("opc-payment")) {
        var a=$("opc-payment").hasClassName("allow")
    } else {
        var a=false
    }
    if(Ajax.activeRequestCount>1||((a==false)&&$("opc-payment"))) {
        return
    }
    var c=$("wait-trn-create");
    var b=$("sage-pay-server-iframe");
    b.writeAttribute({src:""});
    new Ajax.Request(SAGE_PAY_REG_TRN_URL,{
        method:"get",
        onSuccess: function(f) {
            var d=f.responseText.evalJSON();
            var e=d.response_status;
            c.hide();
            if(e=="OK") {
                b.writeAttribute({src:d.next_url}).show();
                if($$(".col-right")[0]) {new Ajax.Updater($$(".col-right")[0],SP_PROGRESS_URL,{method:"get"})
                }
            } else {
                if(e=="INVALID"||e=="MALFORMED"||e=="ERROR"||e=="FAIL") {
                    if((typeof(SAGE_PAY_SERVER_UNIQUE)!="undefined")&&(SAGE_PAY_SERVER_UNIQUE==true)) {
                        b.hide()
                    }
                    if((a||!$("opc-payment"))) {
                        alert(Translator.translate("An error ocurred")+": \n"+d.response_status_detail)
                    }
                }
            }
        },
        onLoading: function() {
            var d=$("advice-required-entry-d-sgps-hi");
            if(d) {
                d.hide()
            }
            if(b.visible()) {
                b.hide()
            }
            c.setStyle({opacity:0.5}).show()
        }
    })
};
function createTrn() {
    registerTransaction()
}

function DumpObjectIndented(f,c) {
    var b="";
    if(c==null) {
        c=""
    }
    for(var e in f) {
        var d=f[e];
        if(typeof d=="string") {
            d=d
        } else {
            if(typeof d=="object") {
                if(d instanceof Array) {
                    d="[ "+d+" ]"
                } else {
                    var a=DumpObjectIndented(d,c+"  ");
                    d="\n"+c+"{\n"+a+"\n"+c+"}"
                }
            }
        }
        b+=c+"'"+e+"' : "+unescape(d)+",\n"
    }
    return b.replace(/,\n$/,"")
}

checkSagePayServerError= function() {
    var a=$("sage-pay-server-error");
    if(a.innerHTML.replace(/^\s+|\s+$/g,"")) {
        var b=a.innerHTML.evalJSON();
        alert(Translator.translate(b.response_status_detail));
        if(b.Request) {
            alert("REQUEST:\n"+DumpObjectIndented(b.Request));
            alert("RESPONSE:\n"+DumpObjectIndented(b.Response))
        }
        return false
    }
    return true
};
view_iframe= function() {
    if(checkSagePayServerError()===false) {
        return
    }

    var oldContent = firecheckoutWindow.content.down();
    if (oldContent && oldContent.select('#sage-pay-server-iframe')) {
        // oldContent.update('');
    } else if (oldContent) {
        $(document.body).insert(oldContent.hide());
    }
    firecheckoutWindow
        .update($('checkout-sagepay-iframe-load').show())
        .show();

    var iframe = $('sage-pay-server-iframe');
    iframe.src = iframe.name;
    //shPlaceOrderBlock("h")
};
/*_getPlaceBlock= function() {
    return $$("#checkout-step-review .button-set p")
};
shPlaceOrderBlock= function(b) {
    var a=_getPlaceBlock();
    if((typeof(a[0])!="undefined")) {
        if(b=="s") {
            a[0].show();
            if((typeof(a[1])!="undefined")) {
                a[1].show()
            }
        } else {
            a[0].hide();
            if((typeof(a[1])!="undefined")) {
                a[1].hide()
            }
        }
    }
};*/
continueMonitor= function() {
    var a=$$("#payment-buttons-container button");
    if((typeof(a[0])!="undefined")) {
        a[0].stopObserving();
        Event.observe(a[0],"click", function(c) {
            if($("checkout-agreements")) {
                $("checkout-agreements").show()
            }
            var d=$("review-please-wait");
            if($("p_method_sagepayserver").checked===false) {
                d.innerHTML=d.innerHTML.replace(SGPS_vtwo,SGPS_vone)
            } else {
                d.innerHTML=d.innerHTML.replace(SGPS_vone,SGPS_vtwo)
            }
            /*var b=_getPlaceBlock();
            if((typeof(b[0])!="undefined")&&!(b[0].visible())) {
                shPlaceOrderBlock("s");
                if($("review-buttons-container")&&!$("review-buttons-container").visible()) {
                    $("review-buttons-container").show()
                }
            }*/
        })
    }
};
sgps_placeOrder= function() {
    var c=$("review-please-wait");
    var a="";
    var b=$("review-buttons-container");
    //new Ajax.Request(SAGE_PAY_GTSPMC, {
        //onSuccess: function(d) {
            //c.hide();
            //a=d.responseText;
            //if($('p_method_sagepayserver').checked/*a=="sagepayserver"||a=="sagepayserverdot"*/) {
                var h=false;
                if(!SAGE_PAY_SERVER_ASIFRAME) {
                    var g=$$('p.agree input[type="checkbox"]');
                    if(g.length) {
                        g.each( function(k,j) {
                            if(!k.checked) {
                                alert(Translator.translate("Please agree to all Terms and Conditions before placing the order."));
                                h=true;
                                return false
                            }
                        })
                    }
                    if(!b.visible()) {
                        b.show()
                    }
                }
                if(!h) {
                    view_iframe();
                    if($("opc-review")) {
                        $("opc-review").scrollTo();
                        try {
                            var e=$("opc-review").positionedOffset().top;
                            var f=$$("div.header")[0].getStyle("height").replace("px","");
                            if(SP_DESIGN_MODERN) {
                                f=parseInt(f)+parseInt($$("div.toplinks-bar")[0].getStyle("height").replace("px",""))+parseInt($$("div.search-bar")[0].getStyle("height").replace("px",""))+parseInt($$("div.search-bar")[0].getStyle("margin-bottom").replace("px",""));
                                f=parseInt($$("div.middle")[0].getStyle("paddingTop").replace("px",""))+parseInt(f)+48
                            } else {
                                f=parseInt($$("div.middle")[0].getStyle("paddingTop").replace("px",""))+parseInt(f)
                            }
                            e=(e-f)+"px";
                            $$("div.one-page-checkout-progress")[0].setStyle({marginTop:e})
                        } catch(i) {
                        }
                    } else {
                        if($$("div.multi-address-checkout-box div.box")[1]) {
                            $$("div.multi-address-checkout-box div.box")[1].scrollTo()
                        }
                    }
                }
            //} else {
            //    checkout.save();
            //}
        //}
        /*,
        onLoading: function() {
            if(b) {
                b.hide()
            }
            c.setStyle({opacity:0.5}).show()
        }*/
    //});
};
dstrb= function() {
    alert(SAGEPAY_SERVER_NV)
};
sagePayServerAdvance= function() {
    if(SAGE_PAY_SERVER_AAOS) {
        continueMonitor();
        if(!SGPS_ISMS) {
            sgps_placeOrder();
        } else {
            $("multishipping-billing-form").submit()
        }
    }
};
sgps_uniqueHandler= function() {
    var a=$("p_method_sagepayserver").ancestors();
    if(a[0].hasClassName("no-display")) {
        a[0].removeClassName("no-display")
    }
    if((typeof(SAGE_PAY_SERVER_UNIQUE)!="undefined")&&(SAGE_PAY_SERVER_UNIQUE==true)&&(!$("sage-pay-server-iframe").visible()||$("sage-pay-server-iframe").readAttribute("src")=="")) {
        if($("wait-trn-create").visible()) {
            return
        }
        registerTransaction()
    }
};
listenRBSagePay_bind=createTrn.bindAsEventListener();
listenRBSagePayServer_bind=sagePayServerAdvance.bindAsEventListener();
try {
    Event.observe(window,"load", function() {
        if((typeof(SAGE_PAY_VALID_INSTALL)!="undefined")&&(SAGE_PAY_VALID_INSTALL==false)) {
            if(SAGE_PAY_MODE=="live") {new PeriodicalExecuter(dstrb,3)
            } else {
                var a='<ul class="messages"><li class="error-msg">'+SAGEPAY_SERVER_NV+"</li></ul>";
                if($("checkoutSteps")) {new Insertion.Before("checkoutSteps",a)
                } else {
                    var b=$$("div.multi-address-checkout-box");
                    if(b.length>0) {new Insertion.Before(b[0],a)
                    }
                }
            }
        }
    })
} catch(er) {
};
/* SagePay Server Integration */

/* Relaypoint integration */
function updateshipping(url) {
    if ($("s_method_relaypoint_relaypoint") && $("s_method_relaypoint_relaypoint").checked) {
        var radioGrp = $('checkout-shipping-method-load').select('input[name="relay-point"]');
        if (radioGrp) {
            for( i = 0; i < radioGrp.length; i++) {
                if(radioGrp[i].checked == true) {
                    var radioValue = radioGrp[i].value;
                }
            }
        } else {
            if(radioValue == null) {
                FireCheckout.Messenger.add(
                    'Vous devez choisir une adresse de livraison',
                    'checkout-shipping-method-load',
                    'error'
                );
                return false;
            }
        }
        var shippingstring = new Array();
        if (radioValue) {
            shippingstring = radioValue.split("&&&");
        } else {
            FireCheckout.Messenger.add(
                "Vous devez choisir une adresse de livraison",
                'checkout-shipping-method-load',
                'error'
            );
            return false;
        }
//        var street = shippingstring[0];
//        var description = shippingstring[1];
//        var postcode = shippingstring[2];
//        var city = shippingstring[3];
//        new Ajax.Request(url, {
//            method : 'post',
//            parameters : {
//                street : street,
//                description : description,
//                postcode : postcode,
//                city : city
//            }
//        });
    }
}
/* Relaypoint integration */

/* phoenix ipayment */
function processOnepagecheckoutResponse(response) {
    processResponse (response);

    if (response.get('ret_status') == 'SUCCESS') {
        if (response.get('paydata_bank_name'))
            document.getElementById('ipayment_elv_bank_name').value = response.get('paydata_bank_name');


        var formData = Form.serialize($('firecheckout-form'), true);
        var params = {};
        for (var i in formData) {
            if (i.indexOf('payment') == 0) { // sagepay think that onestepcheckout is active if billing was supplied
                params[i] = formData[i];
            }
        }

        checkout.setLoadWaiting(true);

        new Ajax.Request(
            saveSessInfoUrlGlobal,
            {
                method:'post',
                parameters: params,
                onComplete: function() {
                    checkout.setLoadWaiting(false);
                    checkout.save('', true);
                }
            }
        );

//        var request = new Ajax.Request(
//            payment.saveUrl,
//            {
//                method:'post',
//                onComplete: payment.onComplete,
//                onSuccess: payment.onSave,
//                onFailure: checkout.ajaxFailure.bind(checkout),
//                parameters: Form.serialize(payment.form)
//            }
//        );
    }
}
/* phoenix ipayment */

/* Orgone fix */
var accordion = {};
accordion.openSection = function() {}
/* Orgone fix */

/* Payone intergration */
if (window.payone) {
    window.payone.handleResponseCreditcardCheck = function(response) {
        if (response.status != 'VALID') {
            // Failure
            alert(response.customermessage);
            checkout.setLoadWaiting(false);
            return false;
        }
        // Success!
        var pseudocardpan = response.pseudocardpan;
        var truncatedcardpan = response.truncatedcardpan;
        $('payone_pseudocardpan').setValue(pseudocardpan);
        $('payone_truncatedcardpan').setValue(truncatedcardpan);
        // $('payone_creditcard_cc_number').setValue(truncatedcardpan); // validation
        cid = $('payone_creditcard_cc_cid');
        if (cid != undefined) {
            // $('payone_creditcard_cc_cid').setValue('')
        }
        checkout.setLoadWaiting(false);
        checkout.save('', true); // suffix, force
        // Post payment form to Magento:
//    var request = new Ajax.Request(payment.saveUrl, {
//        method : 'post',
//        onComplete : payment.onComplete,
//        onSuccess : payment.onSave,
//        onFailure : checkout.ajaxFailure.bind(checkout),
//        parameters : Form.serialize(payment.form)
//    });
    };
}
/* Payone intergration */
