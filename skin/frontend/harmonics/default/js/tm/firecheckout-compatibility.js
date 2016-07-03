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

            if(parseInt(SuiteConfig.getConfig('global','token_enabled')) === 1 && ($('remembertoken-sagepayserver') && $('remembertoken-sagepayserver').checked === true) ) {

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

        } else if(this.isDirectPaymentMethod() && parseInt(SuiteConfig.getConfig('global','token_enabled')) === 1
            && ($('remembertoken-sagepaydirectpro') && $('remembertoken-sagepaydirectpro').checked === true)) {

            if(this.isDirectTokenTransaction()) {
                return;
            }

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

    EbizmartsSagePaySuite.Checkout.prototype.getTokensHtml = function() {
        var self = this;
        new Ajax.Updater(('tokencards-payment-' + this.getPaymentMethod()), SuiteConfig.getConfig('global', 'html_paymentmethods_url'), {
            parameters : {
                payment_method : this.getPaymentMethod()
            },
            onComplete : function() {
                if ($$('a.addnew').length > 1) {
                    $$('a.addnew').each(function(el) {
                        if (!el.visible()) {
                            el.remove();
                        }
                    })
                }
                toggleNewCard(2);

                if (/*$('onestepcheckout-form') && */
                this.isServerPaymentMethod()) {
                    toggleNewCard(1);

                    var tokens = $$('div#payment_form_sagepayserver ul li.tokencard-radio input');
                    if (tokens.length) {
                        tokens.each(function(radiob) {
                            radiob.disabled = true;
                            radiob.removeAttribute('checked');
                        });
                        tokens.first().writeAttribute('checked', 'checked');
                        tokens.first().disabled = false;
                        // $('onestepcheckout-form').submit();
                        self.reviewSave({
                            tokenSuccess : true
                        });
                    } else {
                        // this.resetOscLoading();
                    }

                }
            }.bind(this)
        });

    };

    EbizmartsSagePaySuite.Checkout.prototype.reviewSave = function(transport) {
        if((typeof transport) == 'undefined') {
            var transport = {};
        }
        if ((typeof transport.responseText) == 'undefined') { // Firecheckout fix

            if(this.isFormPaymentMethod()) {
                setLocation(SuiteConfig.getConfig('form','url'));
                return;
            }

            if((/*this.isDirectPaymentMethod() || */this.isServerPaymentMethod()) && parseInt(SuiteConfig.getConfig('global','token_enabled')) === 1) {
                if((typeof transport.tokenSuccess) == 'undefined') {
                    this.setPaymentMethod();

                    if (!this.isServerTokenTransaction() && $('remembertoken-sagepayserver') && $('remembertoken-sagepayserver').checked === true) {
                        return;
                    }

                    // if(!this.isDirectTokenTransaction()
                    //      && (($('remembertoken-sagepaydirectpro') && $('remembertoken-sagepaydirectpro').checked === true)
                    //         || ) {

                    //     return;
                    // }
                }
            }

            transport.tokenSuccess = true;

            if((typeof transport.tokenSuccess != 'undefined' && true === transport.tokenSuccess)) {

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
                    return;
                }

            } else {
                return;
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

restoreOscLoad = function(){
    $('firecheckout-form').show();
}

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

        new Ajax.Request(
            checkout.urls.payment_method,
            {
                method:'post',
                parameters: params,
                onComplete: function() {
                    checkout.setLoadWaiting(false);
                    checkout.save('', true);
                }
            }
        );
    }
}
/* phoenix ipayment */

/* Orgone fix */
var accordion = {};
accordion.openSection = function() {}
/* Orgone fix */

/* Payone integration */
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
/* Payone integration */

/* Klarna integration */
document.observe('dom:loaded', function() {
    if (typeof Klarna !== 'undefined') {
        var FirecheckoutToKlarna = function() {
            var mapping = {
                'billing[firstname]': [
                    'payment[invoice_first_name]',
                    'payment[part_first_name]',
                    'payment[klarna_partpayment_firstname]',
                    'payment[klarna_invoice_firstname]'
                ],
                'billing[lastname]': [
                    'payment[invoice_last_name]',
                    'payment[part_last_name]',
                    'payment[klarna_partpayment_lastname]',
                    'payment[klarna_invoice_lastname]'
                ],
                'billing[street][]': [
                    'payment[invoice_street]',
                    'payment[part_street]',
                    'payment[klarna_partpayment_street]',
                    'payment[klarna_invoice_street]'
                ],
                'billing[postcode]': [
                    'payment[invoice_zipcode]',
                    'payment[part_zipcode]',
                    'payment[klarna_partpayment_zipcode]',
                    'payment[klarna_invoice_zipcode]'
                ],
                'billing[region]': [
                    'payment[invoice_city]',
                    'payment[part_city]',
                    'payment[klarna_partpayment_city]',
                    'payment[klarna_invoice_city]'
                ],
                'billing[city]': [
                    'payment[invoice_city]',
                    'payment[part_city]',
                    'payment[klarna_partpayment_city]',
                    'payment[klarna_invoice_city]'
                ],
                'billing[telephone]': [
                    'payment[invoice_phone_number]',
                    'payment[part_phone_number]',
                    'payment[klarna_partpayment_phonenumber]',
                    'payment[klarna_invoice_phonenumber]'
                ],
//                'billing[gender]': [
//                    'payment[invoice_gender]',
//                    'payment[part_gender]',
//                    'payment[klarna_partpayment_gender]',
//                    'payment[klarna_invoice_gender]'
//                ],
                'billing[day]': [
                    'payment[invoice_dob_day]',
                    'payment[part_dob_day]',
                    'payment[klarna_partpayment_dob_day]',
                    'payment[klarna_invoice_dob_day]',
                ],
                'billing[month]': [
                    'payment[invoice_dob_month]',
                    'payment[part_dob_month]',
                    'payment[klarna_partpayment_dob_month]',
                    'payment[klarna_invoice_dob_month]',
                ],
                'billing[year]': [
                    'payment[invoice_dob_year]',
                    'payment[part_dob_year]',
                    'payment[klarna_partpayment_dob_year]',
                    'payment[klarna_invoice_dob_year]'
                ]
            };
            var blocked = [];

            for (var name in mapping) {
                var el = $$('[name="' + name + '"]').first();
                if (!el) {
                    continue;
                }
                el.observe('change', function() {
                    var i = 0,
                        klarnaName;
                    while ((klarnaName = mapping[this.readAttribute('name')][i])) {
                        var klarnaEl = $$('[name="' + klarnaName + '"]').first();
                        i++;
                        if (!klarnaEl) {
                            continue;
                        }
                        klarnaEl.setValue(this.getValue());
                    }
                });
            }
        }();
    }
});
/* Klarna integration */

/* phone field formatting */
//document.observe('dom:loaded', function() {
//    if (typeof txtBoxFormat === 'function') {
//        var ids = ['billing:telephone', 'shipping:telephone'];
//        ids.each(function(id) {
//            var field = $(id);
//            if (field) {
//                field.writeAttribute('size', 30);
//                field.writeAttribute('maxlength', 15);
//                field.observe('keypress', function(e) {
//                    txtBoxFormat(this, '(999) 9999-9999', e);
//                });
//            }
//        });
//    }
//});
/* phone field formatting */

/* Billpay integration */
function billpayGetForm() {
    var form = $('firecheckout-form');
    if (!form) {
        form = $('gcheckout-onepage-form');
    }
    if (!form) {
        form = $('co-payment-form');
    }

    return form;
};
/* Billpay integration */

/* MultiFees */
document.observe('dom:loaded', function() {
    if (typeof MultiFees !== 'undefined') {
        firecheckoutMultifees = function(el) {
            var params = { review: 1 },
                url;
            if ($(el).up('#shipping-method')) {
                params['is_payment_fee'] = 0;
                params['is_shipping_fee'] = 1;
                url = checkout.urls.shipping_method;
            } else {
                params['is_payment_fee'] = 1;
                params['is_shipping_fee'] = 0;
                url = checkout.urls.payment_method;
            }
            checkout.update(url, params);
        };
        MultiFees.labelClick = MultiFees.labelClick.wrap(function(original, el) {
            if (false === original(el)) {
                return;
            }
            firecheckoutMultifees(el);
        });
        MultiFees.showShipping = MultiFees.showShipping.wrap(function(original, code) {
            original(code);
            $('multifees_shipping_'+code) && $('multifees_shipping_'+code)
                .select('input[type="checkbox"]').each(function(el) {
                    el.stopObserving('change');
                    el.observe('change', function(e) {
                        firecheckoutMultifees(this);
                    });
                });
        });
        checkout.update(checkout.urls.shipping_method);
        checkout.update(checkout.urls.payment_method);
    }
});
/* MultiFees */

function hiddenOnChange(input, callback) {
   var oldvalue = input.getValue();
   setInterval(function(){
      if (input.getValue() != oldvalue){
          oldvalue = input.getValue();
          callback();
      }
   }, 100);
}

/* infostrates tnt */
document.observe('inforstrates:shippingMethodTntCompleted', function(response) {
    checkout.save('', true);
});
ShippingMethod.prototype.addObservers = ShippingMethod.prototype.addObservers.wrap(function(original) {
    original();
    var tntField = $('tnt_relais1');
    if (tntField) {
        hiddenOnChange(tntField, function() {
            if (!tntField.getValue().length) {
                return;
            }

            //41 RUE RODIER&&&PEINTURE RODIER COLOR C3071&&&75009&&&PARIS 09
            var parts = tntField.getValue().split('&&&'),
                keys = ['street1', 'company', 'postcode', 'city'],
                input, i;

            if ($('shipping:same_as_billing')) {
                $('shipping:same_as_billing').checked = false;
                shipping.setSameAsBilling(false);
            }

            for (i in parts) {
                input = $('shipping:' + keys[i]) || $('billing:' + keys[i]);
                if (input) {
                    input.setValue(parts[i]);
                }
            }
            reviewInfo && reviewInfo.update('shipping-address');
        });
    }
});
/* infostrates tnt */

/* Webtex Giftcards */
if (typeof OnepageGiftcard !== 'undefined') {
    OnepageGiftcard.prototype.save = OnepageGiftcard.prototype.save.wrap(function(original) {
        checkout.setLoadWaiting(true);
        var request = new Ajax.Request(this.saveUrl, {
            method:'post',
            onComplete: this.onComplete,
            onSuccess: this.onSave,
            onFailure: checkout.ajaxFailure.bind(checkout),
            parameters: Form.serialize(checkout.form)
        });
    });
    OnepageGiftcard.prototype.nextStep = OnepageGiftcard.prototype.nextStep.wrap(function(original, transport) {
        original(transport);
        checkout.update.bind(checkout).defer(checkout.urls.shipping_method, {review: 1});
    });
}
/* Webtex Giftcards */

/* Radweb stripe*/
FireCheckout.prototype.radweb_stripeSave = function() {
    return Stripe.card.createToken({
        address_line1: $('billing:street1').value,
        address_zip  : $('billing:postcode') ? $('billing:postcode').value : '',
        name         : $('radweb_stripe_cc_owner').value,
        number       : $('radweb_stripe_cc_number').value,
        cvc          : $('radweb_stripe_cc_cid').value,
        exp_month    : $('radweb_stripe_expiration').value,
        exp_year     : $('radweb_stripe_expiration_yr').value
    }, FireCheckout.prototype.stripeResponseHandler);
};

FireCheckout.prototype.stripeResponseHandler = function(status, response) {
    var pform = jQuery('#firecheckout-form');
    if (response.error) {
        msg = response.error.message;
        var stripeError = true;
        Validation.add('validate-cc-number', msg, function(v, elm) {
            if (stripeError)
                return false;
            else
                return true;
        });
        Validation.validate('radweb_stripe_cc_number');
        checkout.setLoadWaiting(false);
        $('review-please-wait').hide();
        stripeError = false;
    } else {
        // token contains id, last4, and card type
        var token = response.id;
        // Insert the token into the form so it gets submitted to the server
        if (jQuery('#stripeToken')) {
            jQuery('#stripeToken').remove();
        }
        pform.append(jQuery('<input type="hidden" name="stripeToken" id="stripeToken" />').val(token));
        checkout.save('', true);
    }
};
/* Radweb stripe*/

/* Customweb_PayUnity */
if (typeof Customweb !== 'undefined') {
    Customweb.CheckoutPreloadFlag = true; // disable preload functionality
}
/* Customweb_PayUnity */

/* Bpost_ShippingManager */
if (typeof bpostShippingManagerBase !== 'undefined') {
    bpostShippingManagerBase.prototype.updateShippingAddress = bpostShippingManagerBase.prototype.updateShippingAddress.wrap(function(o, details) {
        o(details);
        checkout.update(checkout.urls.shipping_method);
    });
}
/* Bpost_ShippingManager */

/* Webshopapps_Desttype */
document.observe('dom:loaded', function() {
    $('billing:dest_type') && $('billing:dest_type').observe('change', function() {
        checkout.update(checkout.urls.billing_address, {
            'shipping-method': 1,
            'review'         : 1
        });
    });
    $('shipping:dest_type') && $('shipping:dest_type').observe('change', function() {
        checkout.update(checkout.urls.shipping_address, {
            'shipping-method': 1,
            'review'         : 1
        });
    });
});
/* Webshopapps_Desttype */
