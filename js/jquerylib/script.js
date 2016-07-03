$j(document).ready(function()
    {
	 
	    $j(".btn-cart").click(function() {
           
	            $j.ajax({
                                               type: 'POST',
                                                url:  "<?php echo Mage::getUrl('babyprofile/index/showprofiles') ?>",
                                                dataType: "html",   //expect html to be returned                
                                                success: function(data)
                                                {   
                                                     alert("hai");
                                                       // $j( ".profile_names" ).append(data);
                                                    //}    
                                                 }
                                             });
                                              loading(); // loading
	            setTimeout(function(){ // then show popup, deley in .5 second
	                loadPopup(); // function show popup
	            }, 500); // .5 second
	  //  return false;
	    });
	 
	    /* event for close the popup */
	    $j("div.close").hover(
	                    function() {
	                        $j('span.ecs_tooltip').show();
	                    },
	                    function () {
	                        $j('span.ecs_tooltip').hide();
	                    }
                );
	 
	    $j("div.close").click(function() {
	        disablePopup();  // function close pop up
	    });
	 
	    $j(this).keyup(function(event) {
	        if (event.which == 27) { // 27 is 'Ecs' in the keyboard
	            disablePopup();  // function close pop up
	        }
	    });
	 
	        $j("div#backgroundPopup").click(function() {
	        disablePopup();  // function close pop up
	    });
	 
	     /************** start: functions. **************/
	    function loading() {
	        $j("div.loader").show();
	    }
	    function closeloading() {
	        $j("div.loader").fadeOut('normal');
	    }
	 
    var popupStatus = 0; // set value
	 
	    function loadPopup() {
	        if(popupStatus == 0) { // if value is 0, show popup
	            closeloading(); // fadeout loading
	            $j("#toPopup").fadeIn(0500); // fadein popup div
	            $j("#backgroundPopup").css("opacity", "0.7"); // css opacity, supports IE7, IE8
	            $j("#backgroundPopup").fadeIn(0001);
	            popupStatus = 1; // and set value to 1
	        }
	    }
	 
	    function disablePopup() {
	        if(popupStatus == 1) { // if value is 1, close popup
            $j("#toPopup").fadeOut("normal");
	            $j("#backgroundPopup").fadeOut("normal");
	            popupStatus = 0;  // and set value to 0
	        }
	    }
	    /************** end: functions. **************/
	}); // jQuery End

