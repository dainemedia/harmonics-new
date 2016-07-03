<?php

/**
 * Server fail block
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_SagePaySuite
 * @author     Ebizmarts <info@ebizmarts.com>
 */
class TM_FireCheckout_Block_SagePaySuite_Checkout_Serverfail extends Ebizmarts_SagePaySuite_Block_Checkout_Serverfail
{
    protected function _toHtml()
    {
        $message = $this->_getSess()->getFailStatus();

        $alert = '';
        if($message){
            $alert = 'alert("'.$message.'");';
        }

        $html = '<html><body>';
        $html.= '<script type="text/javascript">
                    '.$alert.'

                    window.parent.restoreOscLoad(); // moved here because inChElem.remove(); breaks script

                    var inChElem = window.parent.$(\'sagepaysuite-server-incheckout-iframe\');

                    if(inChElem){ //Iframe below Place Order button
                        var inChElemOsc = window.parent.$(\'onestepcheckout-place-order\');
                        if(inChElemOsc){ //Iframe below Place Order button OSC
                            inChElemOsc.show();
                        }else{
                            window.parent.$(\'checkout-review-submit\').show();
                        }
                        inChElem.remove();
                    }else{
                        try{
                            window.parent.Control.Window.windows.each(
                                function(w){
                                    if(w.container.visible()){
                                        w.close();
                                    }
                                }
                            );
                        }catch(er){}
                    }
                </script>';
        $html.= '</body></html>';

        return $html;
    }
}