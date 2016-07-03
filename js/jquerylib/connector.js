function connectorPayment() {

    var connectorPaymentForm = new varienForm('edit_form');
    if(connectorPaymentForm.validate()) {


        if(jQuery('#p_method_giftcardpay').is(':checked'))
        {
            var url = jQuery('#processgiftcard').val();
            var gcd_unique_num = jQuery('#giftcardpay_giftcard_no').val();
            var password = jQuery('#giftcardpay_password').val();
            var quoteid = jQuery('#connector_gcd_quoteid').val();
            jQuery('#loading-mask').hide();
            jQuery.ajax({
                url: url,
                dataType: 'json',
                type : 'post',
                data:{ gcd_unique_num: gcd_unique_num,password: password,quoteid: quoteid},
                success: function(data){

                    if(data.status=='SUCCESS') {
                        if(data.gcd_status) {
                            jQuery('#loading-mask').hide();
                            order.submit();
                        }
                        else {
                            jQuery('#loading-mask').hide();
                            alert(data.status_msg);

                        }
                    }
                    else {
                        jQuery('#loading-mask').hide();
                        alert(data.status_msg);

                    }
                }
            });
        }
        else if(jQuery('#p_method_storecreditpay').is(':checked'))
        {
            var url = jQuery('#processstorecredit').val();
            var quoteid = jQuery('#connector_quoteid').val();
            var scr_unique_num = jQuery('#storecreditpay_storecredit_no').val();
            //alert(scr_unique_num); return false;
            jQuery('#loading-mask').show();
            jQuery.ajax({
                url: url,
                dataType: 'json',
                type : 'post',
                data:{scr_unique_num: scr_unique_num,quoteid: quoteid},
                success: function(data){
                    if(data.status=='SUCCESS') {
                        if(data.scr_status) {
                            jQuery('#loading-mask').hide();
                            order.submit();
                        }
                        else {
                            jQuery('#loading-mask').hide();
                            alert(data.status_msg);

                        }
                    }
                    else {
                        jQuery('#loading-mask').hide();
                        alert(data.status_msg);

                    }
                }
            });

        }
        else {
            order.submit()
        }
    }
}
jQuery('#submit_order_top_button').attr('onclick','connectorPayment()');
jQuery('#order-billing_method_form > .payment-methods input').live('click',function(){
    if(jQuery('#p_method_giftcardpay').is(':checked') || jQuery('#p_method_storecreditpay').is(':checked'))
    {
       jQuery('#order-totals  .order-totals-bottom  p  .save').attr('onclick','connectorPayment()');

    }
    else {
        jQuery('#order-totals  .order-totals-bottom  p  .save').attr('onclick','order.submit()');
    }
});