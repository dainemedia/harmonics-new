jQuery(function($) {
    
    $(".mbiz_install").click(function() {
            loading(); // loading

		loadPopup();

            
    return false;
    });
jQuery(".mbiz_upgrade").click(function() {
	var actionUrl = jQuery('.mbiz_upgrade').attr('action_url');
	if(actionUrl)
	{
		window.location.href = actionUrl;
	}
});
jQuery(".mbiz_uninstall").click(function() {
    if(window.confirm("Do you really want to Un-Install the MicroBiz Connector Plugin"))
    {
        var actionUrl = jQuery('.mbiz_uninstall').attr('action_url');
        if(actionUrl)
        {

            window.location.href = actionUrl;
        }
    }

});
jQuery('#check_mbiz_instance').click(function(){
	var mbizInstallSignInForm = new varienForm('mbiz_install_details_conn');
	var validated=mbizInstallSignInForm.validate();
	if(validated)
	{
		//var actionUrl = jQuery('#mbiz_install_details_conn').attr('action');
		//alert(actionUrl);
        popupStatus =1;
		disablePopup();
//window.location.href = actionUrl;
		mbizInstallSignInForm.submit();
		/*var mbizSiteUrl = jQuery('#mbiz_install_sitename').val();
	mbizSiteUrl = mbizSiteUrl.trim();
	var mbizUname = jQuery('#mbiz_install_uname').val();
	mbizUname.trim();
	var mbizPwd = jQuery('#mbiz_install_pwd').val();
	mbizPwd.trim();
	if(mbizSiteUrl!='' && mbizUname!='' && mbizPwd!='')
	{
		var testConnUrl = jQuery('#test_install_conn').val();
		var actionUrl = jQuery('.mbiz_install').attr('action_url');
		if(testConnUrl)
		{			
			var postDatas = {mbiz_install_siteurl:mbizSiteUrl,mbiz_install_siteuname:mbizUname,mbiz_install_sitepwd:mbizPwd};
			jQuery('#loading-mask').show();
			jQuery.ajax({
			type: 'post',
			dataType:'json', 
		        url: testConnUrl,	
	               data: postDatas,
		        success: function(data){
				  jQuery('#loading-mask').hide();			
				if(data.status=='SUCCESS')
				{
					alert(data.message)
					disablePopup();
					if(actionUrl)
					{
						window.location.href = actionUrl;
					}
				}
				else {
					alert(data.message)
				}		          			
		           
			}
		    });
		}
	}*/

	}	
	
	
});
    
    /* event for close the popup */
    $("div.close").hover(
                    function() {
                        $('span.ecs_tooltip').show();
                    },
                    function () {
                        $('span.ecs_tooltip').hide();
                      }
                );
    
    $("div.close").click(function() {
        disablePopup();  // function close pop up
	$('.validation-advice').remove();
	$('#mbiz_install_sitename').val('');
	$('#mbiz_install_uname').val('');
	$('#mbiz_install_pwd').val('');
	
    });
    
    $(this).keyup(function(event) {
        if (event.which == 27) { // 27 is 'Ecs' in the keyboard
            disablePopup();  // function close pop up
	$('.validation-advice').remove();
	$('#mbiz_install_sitename').val('');
	$('#mbiz_install_uname').val('');
	$('#mbiz_install_pwd').val('');
        }      
    });
    
    $("div#backgroundPopup").click(function() {
        disablePopup();  // function close pop up.
	$('.validation-advice').remove();
	$('#mbiz_install_sitename').val('');
	$('#mbiz_install_uname').val('');
	$('#mbiz_install_pwd').val('');
    });
    
    $('a.livebox').click(function() {
        alert('Hello World!');
    return false;
    });
    

     /************** start: functions. **************/
    function loading() {
        $("div.loader").show();  
    }
    function closeloading() {
        $("div.loader").fadeOut('normal');  
    }
    
    var popupStatus = 0; // set value
    
    function loadPopup() { 
        if(popupStatus == 0) { // if value is 0, show popup
            closeloading(); // fadeout loading
            $("#toPopup").fadeIn(0500); // fadein popup div
            $("#backgroundPopup").css("opacity", "0.7"); // css opacity, supports IE7, IE8
            $("#backgroundPopup").fadeIn(0001); 
            popupStatus = 1; // and set value to 1
        }    
    }
        
    function disablePopup() {
        if(popupStatus == 1) { // if value is 1, close popup
            $("#toPopup").fadeOut("normal");  
            $("#backgroundPopup").fadeOut("normal");  
            popupStatus = 0;  // and set value to 0
        }
    }
    /************** end: functions. **************/

}); // jQuery End

