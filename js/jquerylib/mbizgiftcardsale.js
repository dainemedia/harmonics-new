/**
 * Created by ktree on 4/6/14.
 * Version 105
 */

jQuery(document).ready(function(){
    jQuery.fn.extend({
        live: function( types, data, fn ) {
            jQuery( this.context ).on( types, this.selector, data, fn );
            return this;
        }
    });

jQuery(".mbiz_gift_range").live('click',function($this){
    var amt = jQuery(this).attr('mbizgiftcard_amt');
    var type = jQuery(this).attr('mbizgiftcard_type');
    jQuery(this).closest('ul').find('a').removeClass('active');
    jQuery(this).addClass('active');
    var currencySymbol = jQuery('.current_store_currency_symbol',x).val();
    var x = jQuery('.mbizgiftcard_purchase_block',jQuery(this).closest('.buy-giftcards'));
    jQuery('.mbiz_giftcard_error_msg',x).hide();
    jQuery(x).show(function(){

        if(amt!=''&& amt>0) //fixedamount
        {

            jQuery('.mbiz_sel_amt',x).hide();
            jQuery('.mbiz_sel_amt',x).val(amt);
            //jQuery('.mbiz_sel_amt',x).attr('disabled','disabled');

            jQuery('.fixed_amount_price',x).html(currencySymbol+amt);
            jQuery('.product-shop>.price-box>.regular-price>.price').html(currencySymbol+amt);
            jQuery('.fixed_amount_price',x).show();
            jQuery('.mbiz_sel_type',x).val(type);
        }
        else
        {
            jQuery('.product-shop>.price-box>.regular-price>.price').html(currencySymbol+'0.00');
            jQuery('.fixed_amount_price',x).hide();
            jQuery('.fixed_amount_price',x).html('');
            jQuery('.mbiz_sel_amt',x).val('');
            jQuery('.mbiz_sel_amt',x).show();
            jQuery('.mbiz_sel_amt',x).removeAttr('disabled');
            jQuery('.mbiz_sel_amt',x).focus();
            jQuery('.mbiz_sel_type',x).val(type);
        }
    });
});



jQuery('.mbiz_giftcard_cancel').live('click',function($this){
    $this.preventDefault();
    var x = jQuery('.mbizgiftcard_purchase_block',jQuery(this).closest('.buy-giftcards'));
    jQuery('.mbiz_giftcard_error_msg',x).hide();

    jQuery(x).hide(function(){
        jQuery('.mbiz_sel_amt',x).val('');
        jQuery('.mbiz_sel_amt',x).removeAttr('disabled');
        jQuery('.mbiz_sel_type',x).val('');
        jQuery(this).fadeOut(3000);
        var isMbizCart = jQuery('#is_mbiz_cart').val();
        if(isMbizCart) {
            var x = jQuery('.mbizgiftcard_purchase_block',jQuery(this).closest('.buy-giftcards'));
            var currencySymbol = jQuery('.current_store_currency_symbol',x).val();

            jQuery('.product-shop>.price-box>.regular-price>.price').html(currencySymbol+'0.00');
        }
    });
});


jQuery('.add_mbiz_giftcard_to_cart').live('click',function($this) {
    $this.preventDefault();
    var x = jQuery('.mbizgiftcard_purchase_block',jQuery(this).closest('.buy-giftcards'));

    var giftAmount = jQuery('.mbiz_sel_amt',x).val();
    var cardType = jQuery('.mbiz_sel_type',x).val();
    jQuery('.mbiz_giftcard_error_msg',x).hide();

    if(giftAmount>0)
    {
        var Url = jQuery('.mbiz_post_url',x).val();
        var giftData = "gift_amount="+giftAmount+"&card_type="+cardType;

        jQuery(this).hide();
        jQuery(".load_giftcard_img",x).show();

        jQuery.ajax({
            type : "POST",
            url : Url,
            data : giftData,
            dataType : "json",
            success : function(data){
                var status = data.status;
                if(status=='SUCCESS')
                {
                    var successUrl = jQuery('.mbiz_post_success_url',x).val();
                    window.location.href = successUrl;
                    jQuery(this).show();
                    jQuery(".load_giftcard_img",x).hide();
                }
                else
                {
                    alert(data.status_msg);
                    jQuery('.add_mbiz_giftcard_to_cart',x).show();
                    jQuery(".load_giftcard_img",x).hide();
                }

            },
            error : function(e)
            {
                console.log(e);
                alert("error occurred"+ e)
                jQuery('.add_mbiz_giftcard_to_cart',x).show();
                jQuery(".load_giftcard_img",x).hide();
            }
    });

    }
    else
    {
        jQuery('.mbiz_giftcard_error_msg',x).show();
        jQuery('.mbiz_sel_amt',x).focus();
    }
});

    jQuery('.mbiz_sel_amt').keyup(function($this){
        if (jQuery(this).val() === '0' || jQuery(this).val()=='.')
        {
            jQuery(this).val('');
        }
        else {
            var isMbizCart = jQuery('#is_mbiz_cart').val();
            if(isMbizCart) {
                var x = jQuery('.mbizgiftcard_purchase_block',jQuery(this).closest('.buy-giftcards'));
                var currencySymbol = jQuery('.current_store_currency_symbol',x).val();
                var gifAmount = jQuery('.mbiz_sel_amt',x).val();
                jQuery('.price-box>.regular-price>.price').html(currencySymbol+gifAmount);
            }
        }
    });
    jQuery(".mbiz_sel_amt").keydown(function(event) {
        //alert(event.keyCode);
        // Allow: backspace, delete, tab, escape, and enter
        if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 110 || event.keyCode == 109 || event.keyCode == 9 || event.keyCode == 27 || event.keyCode == 13 ||
            // Allow: Ctrl+A
            (event.keyCode == 65 && event.ctrlKey === true) ||
            // Allow: home, end, left, right
            (event.keyCode >= 35 && event.keyCode <= 39)|| (event.keyCode == 190 || event.keyCode == 110)) {
            // let it happen, don't do anything

            return;
        }
        else {
            // Ensure that it is a number and stop the keypress
            if (event.shiftKey || (event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) {

                event.preventDefault();
            }
        }
    });
});