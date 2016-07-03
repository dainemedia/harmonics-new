/**
 * Created by ktree on 9/6/14.
 * version 106
 */
jQuery(document).ready(function(){
    jQuery.fn.extend({
        live: function( types, data, fn ) {
            jQuery( this.context ).on( types, this.selector, data, fn );
            return this;
        }
    });
    jQuery('.have-giftcard').live('click',function(){
        jQuery('.have-giftcard-div').hide();
        jQuery('#discount-giftcard-form > #giftcard-redem-discount').show();
    });
    jQuery('#mbiz_gift_payment').live('click',function($this){

        if(jQuery(this).is(':checked'))
        {
            jQuery('.mbizGiftCardBlock').show();
        }
        else
        {
            jQuery('.mbizGiftCardBlock').hide();
            jQuery('.mbizvalidate_info').html('');
            jQuery('.gift_no').val('');
            jQuery('.gift_pin').val('');
            jQuery('#credit_amt').val('');
            jQuery('#credit_no').val('');
            jQuery('#current_bal').val('');
            jQuery('#credit_pin').val('');
        }
    });

    jQuery('.mbizgiftcard_validate').live('click',function($this){
        //jQuery('.mbizgiftcard_validate').hide();
        var postUrl = jQuery('.base_url').val();
        postUrl = postUrl+'connector/index/mbizValidateGiftCard';
        var gcdNo = jQuery('.gift_no').val();
        var currencySymbol = jQuery('.current_store_currency_symbol').val();
        if(gcdNo!='' || gcdNo!=0)
        {
            var gcdPin = jQuery('.gift_pin').val();
            var isPayment = 1;

            var data = {gift_no:gcdNo,gift_pin:gcdPin,is_payment:isPayment};
            console.log(data);
            jQuery('.mbizgiftcard_validate').hide();
            jQuery('.mbiz_validategift_load_img').show();
            jQuery.ajax({
                url: postUrl,
                type: "POST",
                dataType: "json",
                data: data,
                success: function(data)
                {
                    console.log(data);
                    var status = data.status;
                    if(status=='SUCCESS')
                    {
                        //alert(data.message);
                        jQuery('.pay-with').hide();
                        jQuery('.apply-with').show();
                        var availableAmt = data.available_amt;
                        jQuery('.mbizvalidate_info').html('');
                        jQuery('.mbizvalidate_info').append('<p style="font-size: 10px;">****'+gcdNo.substr(-4)+' Current Balance : '+currencySymbol+
                            availableAmt+'<a class="mbizcancelgiftcard btn-remove btn-remove2" href="javascript:void(1)" cancel_url="connector/index/mbizCancelCreditPost/id/'+gcdNo+'" >&nbsp;&nbsp;Cancel</a> </p>');
                        jQuery('.mbizvalidate_info').show();
                        jQuery('.mbizvalidateblock').hide();
                        var cartTotal = jQuery('.cart_total').val();
                        if(cartTotal>=availableAmt)
                        {
                            var amount = availableAmt;
                        }
                        else
                        {
                            var amount = cartTotal;
                        }
                        //alert(amount+'$$'+cartTotal+'$$'+availableAmt);
                        jQuery('.mbizgiftcard_validate').show();
                        jQuery('.mbiz_validategift_load_img').hide();
                        jQuery('#credit_amt').val(amount);
                        jQuery('#credit_no').val(gcdNo);
                        jQuery('#credit_pin').val(gcdPin);
                        jQuery('#current_bal').val(availableAmt);
                        jQuery('.mbizapplyblock').show();
                    }
                    else
                    {
                        alert(data.message);
                        jQuery('.mbizgiftcard_validate').show();
                        jQuery('.mbiz_validategift_load_img').hide();
                    }
                    //jQuery('.mbizgiftcard_validate').die('click');
                },
                error: function(e)
                {
                    alert("error Occured"+ e.message)
                    jQuery('.mbizgiftcard_validate').show();
                    jQuery('.mbiz_validategift_load_img').hide();
                    //jQuery('.mbizgiftcard_validate').die('click');
                }
            });
        }
        else {
            alert('Please enter gift card number.')
            jQuery('.gift_no').focus();
        }
    });

    jQuery(".mbizgiftcard_apply").live('click',function($this){
        var applyAmt = jQuery('#credit_amt').val();
        var cartTotal = jQuery('#cart_total').val();
        var creditNo = jQuery('#credit_no').val();
        var currentBal = jQuery('#current_bal').val();
        var creditPin = jQuery('#credit_pin').val();
        var baseUrl = jQuery('.base_url').val();
        var creditType = jQuery('#credit_type').val();
        var applyUrl = baseUrl+'connector/index/mbizUpdateDiscountAmount'
        var data = {credit_no:creditNo,credit_type:creditType,credit_amt:applyAmt,current_bal:currentBal,credit_pin:creditPin,is_payment:1}
        var currencySymbol = jQuery('.current_store_currency_symbol').val();
        if(applyAmt>0)
        {
            console.log(data)
            jQuery('.mbizgiftcard_apply').hide();
            jQuery('.mbiz_applygift_load_img').show();
            jQuery.ajax({
                url: applyUrl,
                type: "POST",
                dataType: "json",
                data: data,
                success: function(data)
                {
                    console.log(data);
                    //alert(data.message);
                    if(data.status=='SUCCESS')
                    {
                        jQuery('.pay-with').show();
                        jQuery('.apply-with').hide();
                        jQuery('.mbizgiftcard_apply').show();
                        jQuery('.cart_total').val(data.total);
                        jQuery('.mbizgifthistoryblock_info').append('<p id="history_'+creditNo+'">Gift Card (****'+creditNo.substr(-4)+') - Amount '+currencySymbol+applyAmt
                            +'<a href="javascript:void(1)" class="mbizremovegiftcard btn-remove btn-remove2" remove_url="connector/index/mbizRemoveCreditPost/id/'+creditNo+'" >&nbsp;&nbsp;Remove</p');
                        jQuery('.mbizapplyblock').hide();
                        jQuery('.mbizvalidateblock').show();
                        jQuery('.mbizvalidate_info').html('')
                        jQuery('.mbizvalidate_info').hide();
                        jQuery('.gift_no').val('');
                        jQuery('.gift_pin').val('');
                        jQuery('#credit_amt').val('');
                        jQuery('#credit_no').val('');
                        jQuery('#current_bal').val('');
                        jQuery('#credit_pin').val('');
                        var isMultiship = jQuery('.is_multiship').val();
                        if(isMultiship==1)
                        {
                            //window.location.href = jQuery('#connector_multishipping_shipping').attr('href');
                            jQuery.ajax({
                                url: baseUrl+'connector/index/getPaymentMethodsList',
                                type:"POST",
                                success : function(data)
                                {
                                    console.log(data);
                                    if(data.status=='SUCCESS')
                                    {
                                        console.log(data);
                                        console.log(data.methods);
                                        jQuery('.sp-methods').html(data.methods);
                                        var quoteTotal = data.quote_total;
                                        var isDiscountApplied = data.is_discount_applied;

                                        if(quoteTotal==0 && isDiscountApplied==1)
                                        {
                                            jQuery('#p_method_free').attr("checked","checked");
                                        }
                                    }
                                    else {
                                        alert("error Occurred Unable to Reload Payments");
                                    }
                                },
                                error: function(e)
                                {
                                    alert("error Occurred"+ e.message)
                                }
                            });
                        }
                        else
                        {
                           var isshippable = jQuery('.is_shippable').val();
                            if(isshippable==1)
                            {
                                shippingMethod.save();
                            }
                            else {
                                billing.save();
                            }
                        }
                        jQuery('.mbiz_applygift_load_img').hide();
                    }
                    else {
                        alert(data.message);
                        jQuery('.mbizgiftcard_apply').show();
                        jQuery('.mbiz_applygift_load_img').hide();

                    }
                   // jQuery('.mbizgiftcard_apply').die('click');

                },
                error: function(e)
                {
                    alert("error Occured "+ e.message);
                    jQuery('.mbizgiftcard_apply').show();
                    jQuery('.mbiz_applygift_load_img').hide();
                    //jQuery('.mbizgiftcard_apply').die('click');
                }
            });
        }
        else{
            alert("Please enter a Valid Amount to Redeem GiftCard.")
        }
    });

    jQuery(".mbizcancelgiftcard").live('click',function($this){
        var postUrl = jQuery(".mbizcancelgiftcard").attr('cancel_url');

        var newPostUrl = postUrl.split('/id/')
        var creditId = newPostUrl[1];
        var baseUrl = jQuery(".base_url").val();
        var cancelUrl = baseUrl+newPostUrl[0];

        if(cancelUrl)
        {
            jQuery('.mbizcancelgiftcard').hide();
            jQuery('.mbiz_cancelgift_load_img').show();
            jQuery.ajax({
                url: cancelUrl+"?id="+creditId+"&is_payment=1",
                type: "GET",
                dataType: "json",
                success: function(data)
                {
                    if(data.status=='SUCCESS')
                    {
                        //alert(data.message);
                        jQuery('.pay-with').show();
                        jQuery('.apply-with').hide();
                        jQuery('.mbiz_cancelgift_load_img').hide();
                        jQuery('.mbizapplyblock').hide();
                        jQuery('.mbizvalidateblock').show();
                        jQuery('.mbizvalidate_info').html('')
                        jQuery('.mbizvalidate_info').hide();
                        jQuery('.gift_no').val('');
                        jQuery('.gift_pin').val('');
                        jQuery('#credit_amt').val('');
                        jQuery('#credit_no').val('');
                        jQuery('#current_bal').val('');
                        jQuery('#credit_pin').val('');
                    }
                    else {
                        jQuery('.mbizcancelgiftcard').show();
                        jQuery('.mbiz_cancelgift_load_img').hide();
                    }
                    //jQuery('.mbizcancelgiftcard').die('click');

                },
                error: function(e)
                {
                    alert("error Occured"+ e.message)
                    jQuery('.mbizcancelgiftcard').show();
                    jQuery('.mbiz_cancelgift_load_img').hide();
                   // jQuery('.mbizcancelgiftcard').die('click');
                }
            })
        }

    });

    jQuery(".mbizremovegiftcard").live('click',function($this){
        //var removeUrl = jQuery(".mbizremovegiftcard").attr('remove_url');
        var removeUrl = jQuery(this).attr('remove_url');
        console.log(removeUrl)

        removeUrl =  removeUrl.split('/id/');
        var baseUrl = jQuery('.base_url').val();
        var postUrl = baseUrl+removeUrl[0]+'?id='+removeUrl[1]+'&is_payment=1';
        var creditId = removeUrl[1];
        jQuery(this).hide();
        jQuery('.mbiz_removegift_load_img').show()
        jQuery.ajax({
            url: postUrl,
            type: "GET",
            dataType: "json",
            success: function(data)
            {
                //alert(data.message)
                if(data.status=='SUCCESS')
                {
                    jQuery('#history_'+creditId).detach();
                    jQuery('.cart_total').val(data.total);
                    var isMultiship = jQuery('.is_multiship').val();
                    if(isMultiship==1)
                    {
                        //window.location.href = jQuery('#connector_multishipping_shipping').attr('href');
                        jQuery.ajax({
                            url: baseUrl+'connector/index/getPaymentMethodsList',
                            type:"POST",
                            success : function(data)
                            {
                                if(data.status=='SUCCESS')
                                {
                                    console.log(data);
                                    console.log(data.methods);
                                    jQuery('.sp-methods').html(data.methods);
                                }
                                else {
                                    alert("error Occurred Unable to Reload Payments");
                                }
                            },
                            error: function(e)
                            {
                                alert("error Occurred"+ e.message)
                            }
                        });
                    }
                    else
                    {
                        var isshippable = jQuery('.is_shippable').val();
                        if(isshippable==1)
                        {
                            shippingMethod.save();
                        }
                        else {
                            billing.save();
                        }
                    }
                    jQuery('.mbiz_removegift_load_img').hide();
                }
                else {
                    alert(data.messsage);

                    jQuery('.mbizremovegiftcard').show();
                    jQuery('.mbiz_removegift_load_img').hide()
                }
               // jQuery('.mbizremovegiftcard').die('click');

            },
            error: function(e)
            {
                alert("error Occured"+e);
                jQuery('.mbizremovegiftcard').show();
                jQuery('.mbiz_removegift_load_img').hide()
                //jQuery('.mbizremovegiftcard').die('click');
            }
        });
    });

    /*store credits js code starts here*/
    jQuery('#mbiz_storecredit_payment').live('click',function($this){

        if(jQuery(this).is(':checked'))
        {
            jQuery('.mbizStoreCreditBlock').show();
        }
        else
        {
            jQuery('.mbizStoreCreditBlock').hide();
            jQuery('.mbizvalidate_store_info').html('');
            jQuery('#storecredit_no').val('');
            jQuery('.credit_amt').val('');
            jQuery('.credit_no').val('');
            jQuery('.current_bal').val('');

        }
    });

    jQuery('.mbizvalidate_storecredit_button').live('click',function($this){
        //jQuery('.mbizgiftcard_validate').hide();
        var postUrl = jQuery('.base_url').val();
        postUrl = postUrl+'connector/index/mbizValidateStoreCredit';
        var scrNo = jQuery('#storecredit_no').val();
        if(scrNo!='' || scrNo!=0)
        {

            var isPayment = 1;
            jQuery('.mbizvalidate_storecredit_button').hide();
            jQuery('.mbiz_validatestore_load_img').show();
            var data = {credit_no:scrNo,is_payment:isPayment};
            console.log(data);
            jQuery.ajax({
                url: postUrl,
                type: "POST",
                dataType: "json",
                data: data,
                success: function(data)
                {
                    console.log(data);
                    var status = data.status;
                    if(status=='SUCCESS')
                    {
                        //alert(data.message);
                        var availableAmt = data.available_amt;
                        jQuery('.mbizvalidate_store_info').html('');
                        jQuery('.mbizvalidate_store_info').append('<p style="font-size: 10px;">'+scrNo+' Current Balance is '+
                            availableAmt+'<a class="mbizcancelstorecredit btn-remove btn-remove2" href="javascript:void(1)" cancel_url="connector/index/mbizCancelCreditPost/id/'+scrNo+'" >&nbsp;&nbsp;Cancel</a> </p>');
                        jQuery('.mbizvalidate_store_info').show();
                        jQuery('.mbizvalidatestoreblock').hide();
                        var cartTotal = jQuery('.cart_total').val();
                        if(cartTotal>=availableAmt)
                        {
                            var amount = availableAmt;
                        }
                        else
                        {
                            var amount = cartTotal;
                        }
                        //alert(amount+'$$'+cartTotal+'$$'+availableAmt);
                        jQuery('.credit_amt').val(amount);
                        jQuery('.credit_no').val(scrNo);
                        jQuery('.current_bal').val(availableAmt);
                        jQuery('.mbizapplystoreblock').show();
                        jQuery('.mbizvalidate_storecredit_button').show();
                        jQuery('.mbiz_validatestore_load_img').hide();

                    }
                    else
                    {
                        alert(data.message);
                        jQuery('.mbizvalidate_storecredit_button').show();
                        jQuery('.mbiz_validatestore_load_img').hide();
                    }
                },
                error: function(e)
                {
                    alert("error Occured"+ e.message)
                    jQuery('.mbizvalidate_storecredit_button').show();
                    jQuery('.mbiz_validatestore_load_img').hide();
                }
            });
        }
        else {
            alert('Please enter Store Credit number.')
            jQuery('#storecredit_no').focus();
        }
    });

    jQuery(".mbizstorecredit_apply").live('click',function($this){
        var applyAmt = jQuery('.credit_amt').val();
        //alert(applyAmt);
        var cartTotal = jQuery('.cart_total').val();
        var creditNo = jQuery('.credit_no').val();
        var currentBal = jQuery('.current_bal').val();
        var baseUrl = jQuery('.base_url').val();
        var creditType = 1;
        var applyUrl = baseUrl+'connector/index/mbizUpdateDiscountAmount'
        var data = {credit_no:creditNo,credit_type:creditType,credit_amt:applyAmt,current_bal:currentBal,is_payment:1}
        if(applyAmt>0)
        {
            jQuery('.mbizstorecredit_apply').hide();
            jQuery('.mbiz_applystore_load_img').show();
            jQuery.ajax({
                url: applyUrl,
                type: "POST",
                dataType: "json",
                data: data,
                success: function(data)
                {
                    if(data.status=='SUCCESS')
                    {
                        //alert(data.message);
                        jQuery('.cart_total').val(data.total);
                        jQuery('.mbizstorehistoryblock_info').append('<p id="history_'+creditNo+'">Store Credit ('+creditNo+') - Amount '+applyAmt
                            +'<a href="javascript:void(1)" class="mbizremovestorecredit btn-remove btn-remove2" remove_url="connector/index/mbizRemoveCreditPost/id/'+creditNo+'" >&nbsp;&nbsp;Remove</p');
                        jQuery('.mbizapplystoreblock').hide();
                        jQuery('.mbizvalidatestoreblock').show();
                        jQuery('.mbizvalidate_store_info').html('')
                        jQuery('.mbizvalidate_store_info').hide();
                        jQuery('#storecredit_no').val('');
                        jQuery('.credit_amt').val('');
                        jQuery('.credit_no').val('');
                        jQuery('.current_bal').val('');
                        jQuery('.mbizstorecredit_apply').show();
                        ;
                        var isMultiship = jQuery('.is_multiship').val();
                        if(isMultiship==1)
                        {
                            //window.location.href = jQuery('#connector_multishipping_shipping').attr('href');
                            jQuery.ajax({
                                url: baseUrl+'connector/index/getPaymentMethodsList',
                                type:"POST",
                                success : function(data)
                                {
                                    if(data.status=='SUCCESS')
                                    {
                                        console.log(data);
                                        console.log(data.methods);
                                        jQuery('.sp-methods').html(data.methods);
                                        var quoteTotal = data.quote_total;
                                        var isDiscountApplied = data.is_discount_applied;

                                        if(quoteTotal==0 && isDiscountApplied==1)
                                        {
                                            jQuery('#p_method_free').attr("checked","checked");
                                        }
                                    }
                                    else {
                                        alert("error Occurred Unable to Reload Payments");
                                    }
                                },
                                error: function(e)
                                {
                                    alert("error Occurred"+ e.message)
                                }
                            });

                        }
                        else
                        {
                            var isshippable = jQuery('.is_shippable').val();
                            if(isshippable==1)
                            {
                                shippingMethod.save();
                            }
                            else {
                                billing.save();
                            }
                        }
                        jQuery('.mbiz_applystore_load_img').hide()
                    }
                    else {
                        alert(data.message);
                        jQuery('.mbizstorecredit_apply').show();
                        jQuery('.mbiz_applystore_load_img').hide();

                    }


                },
                error: function(e)
                {
                    alert("error Occured "+ e.message);
                    jQuery('.mbizstorecredit_apply').show();
                    jQuery('.mbiz_applystore_load_img').hide();
                }
            });
        }
        else{
            alert("Please enter a Valid Amount to Redeem Store Credit.")
        }
    });

    jQuery(".mbizcancelstorecredit").live('click',function($this){
        var postUrl = jQuery(".mbizcancelstorecredit").attr('cancel_url');
        var newPostUrl = postUrl.split('/id/')
        var creditId = newPostUrl[1];
        var baseUrl = jQuery(".base_url").val();
        var cancelUrl = baseUrl+newPostUrl[0];

        if(cancelUrl)
        {
            jQuery('.mbizcancelstorecredit').hide();
            jQuery('.mbiz_cancelstore_load_img').show();
            jQuery.ajax({
                url: cancelUrl+"?id="+creditId+"&is_payment=1",
                type: "GET",
                dataType: "json",
                success: function(data)
                {
                    if(data.status=='SUCCESS')
                    {
                        //alert(data.message);
                        jQuery('.mbizapplystoreblock').hide();
                        jQuery('.mbizvalidatestoreblock').show();
                        jQuery('.mbizvalidate_store_info').html('')
                        jQuery('.mbizvalidate_store_info').hide();
                        jQuery('#storecredit_no').val('');
                        jQuery('.credit_amt').val('');
                        jQuery('.credit_no').val('');
                        jQuery('.current_bal').val('');

                        jQuery('.mbiz_cancelstore_load_img').hide();
                    }
                    else {
                        alert(data.message);
                        jQuery('.mbizcancelstorecredit').show();
                        jQuery('.mbiz_cancelstore_load_img').hide();
                    }


                },
                error: function(e)
                {
                    alert("error Occured"+ e.message)
                    jQuery('.mbizcancelstorecredit').show();
                    jQuery('.mbiz_cancelstore_load_img').hide();
                }
            })
        }

    });
    /*jQuery('.test').click(function(){
     var baseUrl = jQuery('.base_url').val();
     jQuery.ajax({
     url: baseUrl+'connector/index/getPaymentMethodsList',
     type:"POST",
     success : function(data)
     {
     if(data.status=='SUCCESS')
     {
     console.log(data);
     console.log(data.methods);
     jQuery('.update-payments').html(data.methods);
     }
     },
     error: function(e)
     {
     alert("error Occurred"+ e.message)
     }
     });
     });*/
    jQuery(".mbizremovestorecredit").live('click',function($this){
        var removeUrl = jQuery(this).attr('remove_url');
        removeUrl =  removeUrl.split('/id/');
        var baseUrl = jQuery('.base_url').val();
        var postUrl = baseUrl+removeUrl[0]+'?id='+removeUrl[1]+'&is_payment=1';
        var creditId = removeUrl[1];
        jQuery(this).hide();
        jQuery('.mbiz_removestore_load_img').show()
        jQuery.ajax({
            url: postUrl,
            type: "GET",
            dataType: "json",
            success: function(data)
            {
                if(data.status=='SUCCESS')
                {
                    //alert(data.message)

                    jQuery('#history_'+creditId).detach();
                    jQuery('.cart_total').val(data.total);
                    var isMultiship = jQuery('.is_multiship').val();
                    if(isMultiship==1)
                    {
                        //window.location.href = jQuery('#connector_multishipping_shipping').attr('href');
                        jQuery.ajax({
                            url: baseUrl+'connector/index/getPaymentMethodsList',
                            type:"POST",
                            success : function(data)
                            {
                                if(data.status=='SUCCESS')
                                {
                                    console.log(data);
                                    console.log(data.methods);
                                    jQuery('.sp-methods').html(data.methods);
                                }
                                else {
                                    alert("error Occurred Unable to Reload Payments");
                                }
                            },
                            error: function(e)
                            {
                                alert("error Occurred"+ e.message)
                            }
                        });

                    }
                    else
                    {
                        var isshippable = jQuery('.is_shippable').val();
                        if(isshippable==1)
                        {
                            shippingMethod.save();
                        }
                        else {
                            billing.save();
                        }
                    }
                    jQuery('.mbiz_removestore_load_img').hide()
                }
                else {
                    alert(data.message);
                    jQuery('#history_'+creditId).detach();
                    jQuery('.mbizremovestorecredit').show();
                    jQuery('.mbiz_removestore_load_img').hide()

                }
            },
            error: function(e)
            {
                alert("error Occured"+e);
                jQuery('.mbizremovestorecredit').show();
                jQuery('.mbiz_removestore_load_img').hide()
            }
        });
    });
});
