    $j(document).ready(function() {
            		//alert("in document ready function");
                // var formId = 'myFormfororders';		 
                // var myFormfororders = new VarienForm(formId, true);
		//alert(myFormfororders.validator.validate());
		//$j( ".slider-range<?php echo $attrCode; ?>" ).slider({
                 $j('.isProfileSelectedfororder_').click(function() 
                 {
			alert("inside checkbox");                         
			$j('.add_new_profile_fororder').toggle(this.checked);
			var state=$j(this).attr('isproduct_status');
			alert(state);
                         /*if(!$j(this).is(':checked')) 
                        {
                          
                           $j('#profilenamefororder').removeClass(" validation-failed");
                           $j('#profileagefororder').removeClass("validation-failed")
                           $j('.validation-advice').hide();
                          // $j('#myFormfororders')[0].reset();
                                              
                        }   
			else
			{  
				$j(".error_msg").hide();}	*/
                        
                 });
		$j("#test").on("click", function(){ 
			alert("test"); 
		});
		 /*$j("#test").live("click",function(){
			alert("test");
			});*/
			
			$j('#btnorders').click(function(){alert("inside click function");
                             var found=1;
                            var profilename = $j('#profilenamefororder').val();
                            var profileage = $j('#profileagefororder').val();
                           if(!profilename)
                           { 
                                 found=0;
                            }
                           
                            if ( found==1 ) 
                            {
                                        var url="<?php echo Mage::getUrl('babyprofile/index/save') ?>";    
                                        if (myFormfororders.validator.validate()) {
                                        $j.ajax({
                                               type: 'POST',
                                                url:  url,
                                                data:  {profilename:profilename,profileage:profileage},
                                                dataType: "json",   //expect html to be returned                
                                                success: function(data)
                                                {  
                                                     var status=data.status;
                                                      var profilename=data.profilename;
                                                    if(status=="success")
                                                    {
                                                       
                                                       $j('.add_new_profile_fororder').hide();
                                                       
                                                       $j('#isProfileSelectedfororder').prop('checked', false);
                                                      
                                                       $j( "profile_names_fororders_"+state).append(profilename);
                                                      // $j('#myFormfororders')[0].reset();
                                                     }  
						     else
						    {//alert("In else part");
							 $j(".error_msg").show();
						   }
                                                 }
                                             });
                                            }
                          }
                          else
                          {
                                  //  alert("enter the profilename,profileage and submit");
                          }                           
                    });                  
                   /* new Event.observe('myFormfororders', 'submit', function(e)
                    {
                        e.stop();
                        callAjax();
                    });*/
                     
       });
	$j("#Add_new_profile_fororder_multiple").on("click", function(){
			alert("in addnewprofilefororder");
                          var correct=1;
                         // alert(correct);
                         var url="<?php echo Mage::getUrl('babyprofile/index/addtoprofile') ?>";
			 var carturl="<?php echo Mage::getUrl('checkout/cart/addgroup')  ?>";
                        var product_id = $j('#ktree_product_id_fororders').val();
			 alert("product_id:"+product_id);
                         var customer_id= "<?php echo  $customerId; ?>";
                         var profile_id=$j('input[name=profile_names]:checked', '#myFormfororders').val();
                                     alert("profile_id:"+profile_id);
                          if(!profile_id)
                           { 
                                 correct=0;
                            }
                              if ( correct==1 ) 
                            {
                                 $j.ajax({
                                               type: 'POST',
                                                url:  url,
                                                data:  {product_id:product_id,customer_id:customer_id,profile_id:profile_id},
                                                dataType: "json",   //expect html to be returned                
                                                success: function(data)
                                                {  
                                                  alert("success")
                                                  var status=data.status;
                                                  if(status=="success")
                                                    {
                                                       setLocation(carturl);
                                                  }  
                                                 }
                                             });
                           }
                           else
                           {
                            alert("select any profile and enter submit");
                           }                   
                                                       
                     });      
