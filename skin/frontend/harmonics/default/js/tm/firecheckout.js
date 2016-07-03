var FireCheckout = Class.create();
FireCheckout.prototype = {

    initialize: function(form, urls, translations) {
        this._addNumberToTitles();

        this.translations = translations;
        this.urls         = urls;
        this.form         = form;
        this.loadCounter  = 0;
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
        if (flag) {
            this.loadCounter++;
        } else if (this.loadCounter > 0) {
            this.loadCounter--;
            if (this.loadCounter > 0) {
                flag = true;
            }
        }

        var spinner   = $('firecheckout-spinner'),
            container = $('review-buttons-container');
        if (flag && !spinner) {
            spinner = new Element('div');
            spinner.writeAttribute('id', 'firecheckout-spinner');
            spinner.insert(this.translations.spinnerText);
            $(document.body).insert({
                bottom: spinner
            });
            container.addClassName('disabled');
            container.setStyle({opacity:0.5});
            this._disableEnableAll(container, true);
        } else if (!flag && spinner) {
            spinner.remove();
            container.removeClassName('disabled');
            container.setStyle({opacity:1});
            this._disableEnableAll(container, false);
        }
        this.loadWaiting = flag;
    },

    validate: function() {
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
                return isValid;
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
        }
        return isValid;
    },

    save: function(urlSuffix, forceSave) {
        if (this.loadWaiting != false) {
            return;
        }

        if (!this.validate()) {
            return;
        }

        if (payment.currentMethod) {
            // radweb_stripe integration
            if (!forceSave && payment.currentMethod.indexOf("radweb_stripe") === 0) {
                checkout.radweb_stripeSave();
                return;
            }
            // radweb_stripe integration

            // stripe integration
            if (!forceSave && payment.currentMethod.indexOf("stripe") === 0) {
                if ('undefined' !== typeof createStripeToken) {
                    createStripeToken(); // Stripe
                } else {
                    payment.save(); // TemplateTag_Stripe
                }
                return;
            }
            // stripe integration

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

            // paymill integration
            if (!forceSave && payment.currentMethod.indexOf("paymill") === 0) {
                if (typeof paymill_onestep_cc !== 'undefined') {
                    paymill_onestep_cc(function() {
                        checkout.save('', true);
                    });
                    return;
                }
            }
            // paymill integration
        }

        // infostrates tnt
        if (!forceSave && (typeof shippingMethod === 'object')
            && shippingMethod.getCurrentMethod().indexOf("tnt_") === 0) {

            shippingMethodTnt(shippingMethodTntUrl);
            return;
        }
        // infostrates tnt

        checkout.setLoadWaiting(true);
        var params = Form.serialize(this.form, true);
        $('review-please-wait').show();

        // braintree integration
        if (payment.currentMethod && "braintree" === payment.currentMethod) {
            if (typeof braintree !== 'undefined') {
                $('payment_form_braintree').select('input, select').each(function(element) {
                    if (element.readAttribute('data-encrypt') === 'true') {
                        params[element.readAttribute('name')] = braintree.encrypt(element.value);
                    } else if (element.readAttribute('data-encrypted-name')) {
                        params[element.readAttribute('data-encrypted-name')] = braintree.encrypt(element.value);
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
            // try to focus input element:
            var inputEl = $('captcha_' + id);
            if (inputEl) {
                inputEl.focus();
            }
        }
    },

    setResponse: function(response){
        try {
            response = response.responseText.evalJSON();
        } catch (err) {
            alert('An error has occured during request processing. Try again please');
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
            if (response.captcha) {
               this.updateCaptcha(response.captcha);
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

        if (response.method) {
            if ('centinel' == response.method) {
                this.showCentinel();
            } else if (0 === response.method.indexOf('billsafe')) {
                lpg.open();
                var form = $('firecheckout-form');
                form.action = BILLSAFE_FORM_ACTION;
                form.submit();
            }

            // SagePay Server Integration
//            else if ('sagepayserver' === response.method) {
//                var revertStyles = function(el) {
//                    el.setStyle({
//                        height: '500px'
//                    });
//                }
//                $('sage-pay-server-iframe').observe('load', function() {
//                    $$('.d-sh-tl, .d-sh-tr').each(function(el) {
//                        el.setStyle({
//                            height: 'auto'
//                        });
//                        revertStyles.delay(0.03, el);
//                    });
//                });
//                sgps_placeOrder();
//            }
            // End of SagePay Server Integration

            else if ('sagepayserver' === response.method
                || 'sagepayform' === response.method
                || 'sagepaydirectpro' === response.method
                || 'sagepaypaypal' === response.method) {

                var SageServer = new EbizmartsSagePaySuite.Checkout({
                    //'checkout'  : checkout
                });
                SageServer.code = response.method;
                SageServer.reviewSave();
                // SageServer.setPaymentMethod();
                // SageServer.reviewSave({'tokenSuccess':true});
            }
        }

        if (response.popup) {
            this.showPopup(response.popup);
        } else if (response.body) {
            $(document.body).insert({
                'bottom': response.body.content
            });
        }

        // ogone fix
        if (payment.toggleOpsCcInputs) {
            payment.toggleOpsCcInputs();
        }
        // ogone fix

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
    },

    gotoSection: function(){},
    reloadProgressBlock: function(){}
};

// billing
var Billing = Class.create();
Billing.prototype = {
    initialize: function(){
        var self = this;

        var functions = {
            'billing:country_id': function() {
                if (!$('billing:region_id')) {
                    return;
                }
                var resetRegionId = function() {
                    $('billing:region_id').value = '';
                    $('billing:region_id')[0].selected = true;
                };
                resetRegionId.delay(0.2);
            }
        };
        var fields = FireCheckout.Ajax.getSaveTriggers('billing', 'shipping');
        fields.concat(['billing-address-select']).each(function(selector) {
            selector = selector.replace(/shipping/g, 'billing');
            if (selector[0].match(/[a-zA-Z0-9_]/)) {
                field = $(selector);
                if (field) {
                    field = [field];
                } else {
                    field = [];
                }
            } else {
                field = $$(selector);
            }
            if (field.length) {
                field.each(function(_field) {
                    _field.observe('change', function() {
                        var sections,
                            sameAsBilling = $('shipping:same_as_billing');

                        if (!sameAsBilling || (sameAsBilling && sameAsBilling.checked)) {
                            sections = FireCheckout.Ajax.getSectionsToUpdate('billing', 'shipping');
                        } else {
                            sections = FireCheckout.Ajax.getSectionsToUpdate('billing');
                        }

                        if (functions[_field.id]) {
                            functions[_field.id]();
                        }
                        if (sections.length) {
                            checkout.update(
                                checkout.urls.billing_address,
                                FireCheckout.Ajax.arrayToJson(sections)
                            );
                        }
                    });
                });
            }
        });

        // housenumber integration
        if ($('virtual:billing:street2')) {
            $('virtual:billing:street2').observe('change', function() {
                var sections,
                    sameAsBilling = $('shipping:same_as_billing');

                if (!sameAsBilling || (sameAsBilling && sameAsBilling.checked)) {
                    sections = FireCheckout.Ajax.getSectionsToUpdate('billing', 'shipping');
                } else {
                    sections = FireCheckout.Ajax.getSectionsToUpdate('billing');
                }

                if (sections.length) {
                    checkout.update.bind(checkout).delay(
                        0.1,
                        checkout.urls.billing_address,
                        FireCheckout.Ajax.arrayToJson(sections)
                    );
                }
            });
        }

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
        var form = $('billing-new-address-form');
        if (form) {
            form.select('input[type="text"], select, textarea').each(function(el) {
                el.setValue('');
            });
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

        var functions = {
            'shipping:country_id': function() {
                if ($('shipping:region_id')) {
                    var resetRegionId = function() {
                        $('shipping:region_id').value = '';
                        $('shipping:region_id')[0].selected = true;
                    };
                    resetRegionId.delay(0.2);
                }
            }
        };
        var fields = FireCheckout.Ajax.getSaveTriggers('shipping');
        fields.concat(['shipping-address-select']).each(function(selector) {
            if (selector[0].match(/[a-zA-Z0-9_]/)) {
                field = $(selector);
                if (field) {
                    field = [field];
                } else {
                    field = [];
                }
                // if (relatedFields[selector]) {
                //     var relatedField = $(relatedFields[selector]);
                //     if (relatedField) {
                //         field.push(relatedField);
                //     }
                // }
            } else {
                field = $$(selector);
            }
            if (field.length) {
                field.each(function(_field) {
                    _field.observe('change', function() {
                        if (functions[_field.id]) {
                            functions[_field.id]();
                        }
                        var sections = FireCheckout.Ajax.getSectionsToUpdate('shipping');
                        if (sections.length) {
                            checkout.update(
                                checkout.urls.shipping_address,
                                FireCheckout.Ajax.arrayToJson(sections)
                            );
                        }
                    });
                });
            }
        });

        // housenumber extension
        if ($('virtual:shipping:street2')) {
            $('virtual:shipping:street2').observe('change', function() {
                var sections = FireCheckout.Ajax.getSectionsToUpdate('shipping');
                if (sections.length) {
                    checkout.update.bind(checkout).delay(
                        0.1,
                        checkout.urls.shipping_address,
                        FireCheckout.Ajax.arrayToJson(sections)
                    );
                }
            });
        }
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
        var selectElement = $('shipping-address-select');
        if (selectElement) {
            selectElement.value = '';
        }
        var form = $('shipping-new-address-form');
        if (form) {
            form.select('input[type="text"], select, textarea').each(function(el) {
                el.setValue('');
            });
        }
    },

    setSameAsBilling: function(flag) {
        $('shipping:same_as_billing').checked = flag;
        $('billing:use_for_shipping').value = flag ? 1 : 0;
        // this.syncWithBilling();

        if (FireCheckout.Ajax.getSaveTriggers('shipping')) {
            var url = flag ? checkout.urls.billing_address : checkout.urls.shipping_address,
                sections = FireCheckout.Ajax.getSectionsToUpdate('shipping');

            if (sections.length) {
                checkout.update(url, FireCheckout.Ajax.arrayToJson(sections));
            }
        }

        if (flag) {
            $('shipping-address').hide();
        } else {
            $('shipping-address').show();

            // crafty clicks fix
            if (typeof _cp_instances !== 'undefined') {
                var el = $('shipping:country_id');
                if (el) {
                    if (document.createEvent) {
                        var oEvent = document.createEvent("HTMLEvents");
                        oEvent.initEvent('change', true, true);
                        el.dispatchEvent(oEvent);
                    } else {
                        var oEvent = document.createEventObject();
                        el.fireEvent('onchange', oEvent);
                    }
                }
            }
            // crafty clicks fix
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

    save: function() {
        // infostrates tnt dummy
    },

    addObservers: function() {
        var self = this;

        this.setCheckedRadios(); // fix for "hide other shipping methods if free is in the list"

        $$('input[name="shipping_method"]').each(function(el) {
            el.observe('click', function() {
                var sections = FireCheckout.Ajax.getSectionsToUpdate('shipping-method');
                /* SmartPost */
                var smartpostSelect = $('smartpost_select_point');
                if (smartpostSelect) {
                    if (el.id !== 's_method_itellaSmartPost') {
                        smartpostSelect.setValue('itellaSmartPost');
                        if (sections.length) {
                            checkout.update(
                                checkout.urls.shipping_method,
                                FireCheckout.Ajax.arrayToJson(sections)
                            );
                        }
                    } else {
                        var availableOptions = smartpostSelect.select('option');
                        if (availableOptions.length >= 2) {
                            smartpostSelect.setValue(availableOptions[1].value);
                            updatePointValue();
                            if (sections.length) {
                                checkout.update(
                                    checkout.urls.shipping_method,
                                    FireCheckout.Ajax.arrayToJson(sections)
                                );
                            }
                        }
                    }
                }
                /* SmartPost */

                else if (FireCheckout.Ajax.getSectionsToUpdate('shipping-method').length) {
                    if (sections.length) {
                        checkout.update(
                            checkout.urls.shipping_method,
                            FireCheckout.Ajax.arrayToJson(sections)
                        );
                    }
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

                /* Infostrates TNT */
                if (-1 !== this.value.indexOf('tnt_') && typeof radioCheck !== 'undefined') {
                    radioCheck();
                }
                /* Infostrates TNT */
            });
        });

        if ($('shipping-method-reset')) {
            $('shipping-method-reset').stopObserving('click');
            $('shipping-method-reset').observe('click', function() {
                $$('input[name="shipping_method"]').each(function(el) {
                    el.checked = '';
                });
                var sections = FireCheckout.Ajax.getSectionsToUpdate('shipping-method');
                if (sections.length) {
                    sections.push('remove-shipping');
                    checkout.update(
                        checkout.urls.shipping_method,
                        FireCheckout.Ajax.arrayToJson(sections)
                    );
                }
            });
        }

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

        /* uSplitRates Unirgy integration */
        $$('.shipment-methods').each(function(el) {
            el.observe('change', function() {
                var sections = FireCheckout.Ajax.getSectionsToUpdate('shipping-method');
                if (sections.length) {
                    checkout.update(
                        checkout.urls.shipping_method,
                        FireCheckout.Ajax.arrayToJson(sections)
                    );
                }
            });
        });
        /* uSplitRates Unirgy integration */

        /* Delivery Date */
        if (typeof deliveryDate == 'object') {
            deliveryDate.toggleDisplay();
        }
        /* Delivery Date */

        /* MageWorx Multifees */
        $$('.multifees-shipping-fee').each(function(el) {
            el.select('input[type="checkbox"]').each(function(el) {
                el.stopObserving('change');
                el.observe('change', function(e) {
                    firecheckoutMultifees(this);
                });
            });
        });
        /* MageWorx Multifees */

        /* SmartPost */
        var smartpostSelect = $('smartpost_select_point');
        if (smartpostSelect) {
            smartpostSelect.observe('change', function() {
                updatePointValue();
                var sections = FireCheckout.Ajax.getSectionsToUpdate('shipping-method');
                if (sections.length) {
                    checkout.update(
                        checkout.urls.shipping_method,
                        FireCheckout.Ajax.arrayToJson(sections)
                    );
                }
            });
        }
        /* SmartPost */

        /* Aitoc_Aitgiftwrap */
        var giftwrap = $('gift_wrap');
        if (giftwrap) {
            giftwrap.observe('click', function() {
                var sections = FireCheckout.Ajax.getSectionsToUpdate('shipping-method');
                if (sections.length) {
                    checkout.update(
                        checkout.urls.shipping_method,
                        FireCheckout.Ajax.arrayToJson(sections)
                    );
                }
            });
        }
        /* Aitoc_Aitgiftwrap */
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

    getCurrentMethod: function() {
        var input = $$('input[name="shipping_method"]').find(function(el) {
            return el.checked;
        });
        if (input) {
            return input.value;
        }
        return '';
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

        if ($('payment-method-reset')) {
            $('payment-method-reset').stopObserving('click');
            $('payment-method-reset').observe('click', function() {
                $$('input[name="payment[method]"]').each(function(el) {
                    el.checked = '';
                });
                self.switchMethod();
                var sections = FireCheckout.Ajax.getSectionsToUpdate('payment-method');
                if (sections.length) {
                    sections.push('payment[remove]');
                    checkout.update(
                        checkout.urls.payment_method,
                        FireCheckout.Ajax.arrayToJson(sections)
                    );
                }
            });
        }


        $$('input[name="payment[method]"]').each(function(el) {
            el.observe('click', function() {
                var sections = FireCheckout.Ajax.getSectionsToUpdate('payment-method');
                if (sections.length) {
                    checkout.update(
                        checkout.urls.payment_method,
                        FireCheckout.Ajax.arrayToJson(sections)
                    );
                }
                if ('p_method_sagepayserver' != this.id) {
                    $("checkout-sagepay-iframe-load").hide();
                }
            });
        });

        /* MageWorx Multifees */
        $$('.multifees-payment-fee').each(function(el) {
            el.select('input[type="checkbox"]').each(function(el) {
                el.observe('change', function(e) {
                    firecheckoutMultifees(this);
                });
            });
        });
        /* MageWorx Multifees */
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
        var hideOldForm = true;
        if (method === 'customercredit') {
            hideOldForm = false;
            el = $('p_method_customercredit');
            if (!el || !el.checked) {
                method = '';
            }
        }

        var elementTypes = ['input', 'select', 'textarea'];

        if (hideOldForm && this.currentMethod && $('payment_form_'+this.currentMethod + '_preencrypt')) {
            this.changeVisible(this.currentMethod + '_preencrypt', true);
        }

        if (hideOldForm && this.currentMethod && $('payment_form_'+this.currentMethod)) {
            this.changeVisible(this.currentMethod, true);
        }

        if ($('payment_form_'+method) || $('payment_form_' + method + '_preencrypt')) {
            if ($('payment_form_'+method)) {
                this.changeVisible(method, false)
            } else {
                this.changeVisible(method + '_preencrypt', false);
            }
        } else {
            //Event fix for payment methods without form like "Check / Money order"
            $(document.body).fire('payment-method:switched', {method_code : method});
        }

        if (hideOldForm) {
            this.currentMethod = method;
        }

        if (typeof MultiFees !== 'undefined') {
            MultiFees.showPayment();
        }
    },

    changeVisible: function(method, mode) {
        var block = 'payment_form_' + method;
        [block + '_before', block, block + '_after'].each(function(el) {
            element = $(el);
            if (element) {
                element.style.display = (mode) ? 'none' : '';
                element.select('input', 'select', 'textarea', 'button').each(function(field) {
                    field.disabled = mode;
                });
            }
        });
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

    nextStep: function(transport){},

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
                var qtyWrapper = this.up('.qty-wrapper'),
                    field = this.up('.qty-wrapper').down('.qty'),
                    qty = parseFloat(field.value),
                    inc = parseFloat(qtyWrapper.down('.qty-increment').getValue()),
                    inc = inc ? inc : 1,
                    inc = (this.hasClassName('qty-more') ? inc : -inc);

                if (isNaN(qty)) {
                    qty = 0;
                }
                qty += inc;
                self.updateQty(field, qty);
            });
        });

        $$('input.qty').each(function(el) {
            el.observe('change', function() {
                self.updateQty(el);
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

        var options = FireCheckout.Ajax.getSectionsToUpdateAsJson('cart', 'total');
        options.review = 1;
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
                        'gc': code,
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
            markup: '<div class="content"></div><a href="javascript:void(0)" class="close">×</a>'
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

        if (!trigger || !trigger.actionbar || trigger.actionbar.hidden === 1) {
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
            windowSize     = this.window.getDimensions(),
            left, top;

        if ('undefined' === typeof viewportSize.width) { // mobile fix. not sure is this check is good enough.
            top  = viewportOffset.top + 20;
            left = viewportOffset.left;
        } else {
            top = viewportSize.height / 2
                - windowSize.height / 2
                + viewportOffset.top,
            left = viewportSize.width / 2
                - windowSize.width / 2
                + viewportOffset.left;

            if (left < viewportOffset.left || windowSize.width > viewportSize.width) {
                left = viewportOffset.left;
            }
            top = (top < viewportOffset.top  ? (20 + viewportOffset.top) : top);
        }

        this.setPosition(left, top);
        this.centered = true;

        return this;
    },

    setPosition: function(x, y) {
        this.window.setStyle({
            left: x + 'px',
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

        var width        = this.window.getWidth() + 30, /* close btn */
            viewportSize = document.viewport.getDimensions();

        sizeConfig = Object.extend(this.config.size, sizeConfig || {});
        if ('auto' === sizeConfig.width
            && (width > sizeConfig.maxWidth || width > viewportSize.width)) {

            if (width > viewportSize.width && viewportSize.width < (sizeConfig.maxWidth + 30)) {
                width = viewportSize.width - 30; /* right shadow and borders */
            } else {
                width = sizeConfig.maxWidth;
            }
            var paddingHorizontal = parseInt(this.window.getStyle('padding-left')) + parseInt(this.window.getStyle('padding-right'));
            this.content.setStyle({
                width: width - paddingHorizontal + 'px'
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
        this.window.hide()
            .setStyle({
                width     : width + 'px',
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
};

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
        todayOffset       : 0,
        period            : 365
    },

    initialize: function(options) {
        Object.extend(this.config, options);
        var today = new Date();

        // if delivery processing day is over add one more day
        var serverDate = new Date(this.config.serverDate),
            endOfDay   = new Date(this.config.endOfDeliveryDayDate);
        if (serverDate > endOfDay) {
            today.setDate(today.getDate() + 1);
        }

        today.setDate(today.getDate() + this.config.todayOffset);
        today.setHours(0);
        today.setMinutes(0);
        today.setSeconds(0);
        today.setMilliseconds(0);
        this.today = today;

        this.maxDate = new Date(today);
        this.maxDate.setHours(23);
        this.maxDate.setMinutes(59);
        this.maxDate.setSeconds(59);
        this.maxDate.setMilliseconds(59);
        this.maxDate.setDate(this.maxDate.getDate() + this.config.period);

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

                if (self.maxDate < date) {
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
        'billing-address': {
            'crafty_postcode_lookup_result_option1': 1
        },
        'shipping-address': {
            'billing[email]': 1,
            'crafty_postcode_lookup_result_option1': 1,
            'crafty_postcode_lookup_result_option2': 1
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

        $('shipping:same_as_billing') && $('shipping:same_as_billing').observe('click', function() {
            if (this.checked) {
                self.update('billing-address', 'shipping-address');
            } else {
                self.update('shipping-address');
            }
        });

        // craftyclicks postcode lookup
        $(document).observe('dom:loaded', function() {
            if (typeof _cp_instances !== 'undefined') {
                _cp_instances.each(function(instance) {
                    instance.populate_form_fields = instance.populate_form_fields.wrap(function(original, j) {
                        original(j);
                        self.update('billing-address');
                        if ($('shipping-address')) {
                            if ($('shipping:same_as_billing') && $('shipping:same_as_billing').checked) {
                                self.update('billing-address', 'shipping-address');
                            } else {
                                self.update('shipping-address');
                            }
                        }
                    });
                });
            }
        });
        // craftyclicks postcode lookup

        $(document).observe('change', function(e) {
            var el = e.element();
            if (el.name === 'payment[method]') {
                self.update.bind(self).defer('payment-method');
            }
        });

        $('payment-method').select('input:radio').each(function(el) {
            el.observe('click', function() {
                self.update.bind(self).defer('payment-method');
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
                    if (self.skip[to] && (self.skip[to][el.name] || self.skip[to][el.id])) {
                        return;
                    }
                    if (!el.visible() || el.type === 'hidden' || el.type === 'checkbox' || el.type === 'password') {
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

        return '<strong>' + title[title.length - i].innerHTML + '</strong>'
            + ' <a href="javascript:void(0)" onclick="reviewInfo.editBlock(\'' + block + '\')">' + this.options.changeText + '</a>'
            + '<br/>';
    },

    editBlock: function(id) {
        var block = $(id),
            sameAsBilling = $('shipping:same_as_billing');

        if (-1 !== id.indexOf('address') && (!block || (sameAsBilling && sameAsBilling.checked))) {
            block = $('billing-address');
        }

        if (block) {
            block.scrollTo();
            block.highlight();
        }
    }
};

var AddressVerification = Class.create();
AddressVerification.prototype = {
    fields: [
        'street1',
        'street2',
        'city',
        'region_id',
        'postcode'
    ],

    initialize: function() {
        this.container = $('address-verification-window');
        this.addObservers();
        this.initWindow();
        this.initSkipLabels();
    },

    addObservers: function() {
        this.container.down('.address-verification-skip').observe('click', this.skipVerification.bind(this));
        this.container.down('.address-verification-edit').observe('click', this.editAddress.bind(this));
        var self = this;
        this.container.select('.address-verification-radio').each(function(el) {
            el.observe('click', self.updateButtons.bind(self));
        });
    },

    initWindow: function() {
        this.verificationWindow = new FireCheckout.Window({
            triggers: {},
            destroy : 1,
            size    : {
                maxWidth: 500
            }
        });
        this.verificationWindow.update(this.container).show();
    },

    initSkipLabels: function() {
        var labels = {
            'billing' : '.address-verification-skip-billing',
            'shipping': '.address-verification-skip-shipping'
        };

        for (var type in labels) {
            var label = this.container.down(labels[type]);
            if (!label) {
                continue;
            }

            var address = [':'];
            this.fields.each(function(id) {
                var value = $(type + ':' + id).getValue();
                if ('region_id' === id
                    && countryRegions['US']
                    && countryRegions['US'][value]) {

                    value = countryRegions['US'][value]['code'];
                }
                address.push(value);
            });

            label.insert({
                bottom: address.join(' ')
            });
        };
    },

    getWindow: function() {
        return this.verificationWindow;
    },

    /**
     * Radio button was clicked
     */
    updateButtons: function() {
        var buttons = this.container.down('.buttons-set');
        buttons.select('.verification-option').invoke('hide');

        var options = this.getSelectedOptions();
        for (var i in options) {
            if ('edit' === options[i]) {
                buttons.select('.address-verification-edit').invoke('show');
                return;
            }
        }
        buttons.select('.address-verification-skip').invoke('show');
    },

    /**
     * @return object
     *  billing : edit,
     *  shipping: skip
     */
    getSelectedOptions: function() {
        var options = {};
        this.container.select('.address-verification-radio').each(function(el) {
            if (el.checked) {
                var type = el.readAttribute('name');
                type = type.replace('address-verification[', '');
                type = type.replace(']', '');
                options[type] = el.getValue();
            }
        });
        return options;
    },

    getVerifiedAddress: function() {
        var address = {};
        this.container.select('.address-verification-radio').each(function(el) {
            if (el.checked) {
                var type = el.readAttribute('name');
                type = type.replace('address-verification[', '');
                type = type.replace(']', '');
                if (0 === el.id.indexOf('address-verification-verified-')) {
                    address[type] = {};
                    el.up('li').select('.input-verified').each(function(input) {
                        address[type][input.readAttribute('name')] = input.getValue();
                    });
                }
            }
        });
        return address;
    },

    /**
     * "Place order using selected option" was clicked
     */
    skipVerification: function() {
        this.getWindow().hide();

        var address = this.getVerifiedAddress();
        for (var type in address) {
            this.fillAddress(address[type]);
        }

        checkout.save('?skip-address-verification=1', true);
    },

    fillAddress: function(data) {
        for (var i in data) {
            var value = data[i],
                el    = $(i);

            if (!el || (!value && el.hasClassName('required-entry'))) {
                continue;
            }
            el.setValue(value);
        }
    },

    /**
     * "Edit address" was clicked
     */
    editAddress: function() {
        this.getWindow().hide();

        var options = this.getSelectedOptions();
        for (var type in options) {
            if ('edit' !== options[type]) {
                continue;
            }
            var address = $(type + '-address');
            address.scrollTo();
            address.highlight();
        }
    }
};

FireCheckout.Housenumber = Class.create();
FireCheckout.Housenumber.prototype = {
    settings: {
        /**
         * Array of country codes
         * @type {Array}
         */
        optional: [],

        /**
         * Array of country codes
         * @type {Array}
         */
        required: ['*']
    },

    initialize: function(addressType, config) {
        Object.extend(this.settings, config || {});
        this.addressType = addressType;
        this.createField();
        this.updateFieldStatus();
        this.addObservers();
    },

    createField: function() {
        var street1  = $(this.addressType + ':street1'),
            parent   = street1.up('li'),
            housenum = $(this.addressType + ':street2'),
            housenumLi = housenum.up('li'),
            wrapper1 = new Element('div', {'class': 'field street1'}),
            wrapper2 = new Element('div', {'class': 'field housenum'});

        parent.removeClassName('wide');
        parent.addClassName('fields');

        parent.insert({top: wrapper1});
        wrapper1.insert({top: street1.up().previous()});
        wrapper1.insert({bottom: street1.up()});

        parent.insert({bottom: wrapper2});
        wrapper2.insert({top: housenum.up()});
        housenum.insert({
            before: '<label for="' + this.addressType + ':street2">' + this.settings.label + '</label>'
        });
        housenumLi.remove();
    },

    updateFieldStatus: function() {
        var countryEl = $(this.addressType + ':country_id'),
            housenumEl = $(this.addressType + ':street2'),
            label = housenumEl.previous('label');

        if (this.isRequired(countryEl.getValue())) {
            if (housenumEl.hasClassName('required-entry')) {
                return;
            }
            label.addClassName('required');
            label.insert({top: '<em>*</em>'});
            housenumEl.addClassName('required-entry');
        } else {
            label.removeClassName('required');
            label.innerHTML = label.innerHTML.replace('<em>*</em>', '');
            housenumEl.removeClassName('required-entry');
            housenumEl.removeClassName('validation-failed');
            if ($('advice-required-entry-' + this.addressType + ':street2')) {
                $('advice-required-entry-' + this.addressType + ':street2').remove();
            }
        }
    },

    isRequired: function(countryCode) {
        // if country is in required array
        if (-1 !== this.settings.required.indexOf(countryCode)) {
            return true;
        }

        // if country is in optional array
        if (-1 !== this.settings.optional.indexOf(countryCode)) {
            return false;
        }

        // if asterisk is in required array
        if (-1 !== this.settings.required.indexOf('*')) {
            return true;
        }

        // optional if not required
        return false;
    },

    addObservers: function() {
        var countryEl = $(this.addressType + ':country_id');
        if (countryEl) {
            countryEl.observe('change', this.updateFieldStatus.bind(this));
        }
    }
};

FireCheckout.Ajax = {
    rules: {},
    sectionsToReload: false,

    getSectionsToUpdate: function() {
        if (!this.sectionsToReload) {
            this.sectionsToReload = {};
            for (var sectionToReload in this.rules.reload) {
                this.rules.reload[sectionToReload].each(function(section) {
                    if (!this.sectionsToReload[section]) {
                        this.sectionsToReload[section] = [];
                    }
                    this.sectionsToReload[section].push(sectionToReload);
                }.bind(this));
            }
        }

        var sections = [],
            i = 0;

        do {
            if (this.sectionsToReload[arguments[i]]) {
                sections = sections.concat(this.sectionsToReload[arguments[i]]);
            }
            i++;
        } while (i < arguments.length);

        return sections;
    },

    getSectionsToUpdateAsJson: function() {
        return this.arrayToJson(this.getSectionsToUpdate.apply(this, arguments));
    },

    arrayToJson: function(array) {
        var json = {};
        array.each(function(section) {
            json[section] = 1;
        });
        return json;
    },

    getSaveTriggers: function() {
        var triggers = [],
            i = 0;

        do {
            if (this.rules['save'][arguments[i]]) {
                triggers = triggers.concat(this.rules['save'][arguments[i]]);
            }
            i++;
        } while (i < arguments.length);

        return triggers;
    }
};
