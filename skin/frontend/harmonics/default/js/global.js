jQuery(document).ready(function() {
	jQuery('#accordion').accordion({
		collapsible: true,
		active: false
	});
	
	jQuery('#slider').flexslider({
		animation: "slide"
	});

	// Using default configuration
	jQuery('.banners p img').first().addClass('margin');
	
	var browser = BrowserDetect.browser;
	var browserOS = BrowserDetect.OS;
	
	if ( browserOS == 'Mac' && browser == 'Firefox' ) {
		jQuery('html').addClass('mac-firefox');
	} else if ( browserOS == 'Mac' && browser == 'Chrome' ) {
		jQuery('html').addClass('mac-chrome');
	} else if ( browserOS == 'Mac' && browser == 'Safari' ) {
		jQuery('html').addClass('mac-safari');
	} else if ( browserOS == 'Mac' && browser == 'Opera' ) {
		jQuery('html').addClass('mac-opera');
	} else if ( browser == 'Firefox' ) {
		jQuery('html').addClass('firefox');
	} else if ( browser == 'Chrome' ) {
		jQuery('html').addClass('chrome');
	} else if ( browser == 'Safari' ) {
		jQuery('html').addClass('safari');
	} else if ( browser == 'Opera' ) {
		jQuery('html').addClass('opera');
	}
	
	jQuery('.fancybox').fancybox();
	
	jQuery('.fancybox-compare').fancybox({
		'width'         : '75%',
		'height'        : '75%',
		'autoScale'     : false,
		'transitionIn'  : 'none',
		'transitionOut' : 'none',
		'type'          : 'iframe'
	});
	
	jQuery('.zoom').easyZoom({
		id: 'imagezoom',
		preload: '<p class="preloader">Loading image...</p>',
		parent: '.product-image'
	});
	
	jQuery('#product-collateral').easytabs();

	jQuery('.item').first().addClass('active');
	jQuery('.carousel-indicators li').first().addClass('active');

	var isBootstrapEvent = false;
    if (window.jQuery) {
        var all = jQuery('.dropdown');
        jQuery.each(['hide.bs.dropdown'], function(index, eventName) {
            all.on(eventName, function( event ) {
                isBootstrapEvent = true;
            });
        });
    }
    var originalHide = Element.hide;
    Element.addMethods({
        hide: function(element) {
            if(isBootstrapEvent) {
                isBootstrapEvent = false;
                return element;
            }
            return originalHide(element);
        }
    });

	// // Mobile Menu
	jQuery('.mobile-menu').click(function(e) {
		e.preventDefault();
		e.stopPropagation();

		jQuery('.mini-basket').fadeToggle('slow', 'linear');
		jQuery('.categories').slideToggle('slow');
	});

	jQuery('.mobile-menu-cms').click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		jQuery('nav.cms').slideToggle('slow');
	});

    jQuery(window).resize(function() {
    	var width = jQuery(window).width();

    	if (width < 767) {
    		jQuery(document).on('click', '.mobile a.level-top', function(e) {
    			e.preventDefault();
    			e.stopImmediatePropagation();

    			jQuery('.navbar a.level-top').not(this).siblings().each(function(index) {
    				if (jQuery(this).is(':visible')) {
    					jQuery(this).slideToggle();
    				}
    			});

    			jQuery(this).siblings().slideToggle('slow');
    		});
    		jQuery(document).on('click', '.mobile .dropdown-submenu.level1>a', function(e) {
    			e.preventDefault();
    			e.stopImmediatePropagation();

    			jQuery('.navbar .dropdown-submenu.level1>a').not(this).siblings().each(function(index) {
    				if (jQuery(this).is(':visible')) {
    					jQuery(this).slideToggle();
    				}
    			});

    			jQuery(this).siblings().slideToggle('slow');
    		});
    		jQuery(document).on('click', '.mobile .dropdown-submenu.level2>a', function(e) {
    			e.preventDefault();
    			e.stopImmediatePropagation();

    			jQuery('.navbar .dropdown-submenu.level2>a').not(this).siblings().each(function(index) {
    				if (jQuery(this).is(':visible')) {
    					jQuery(this).slideToggle();
    				}
    			});

    			jQuery(this).siblings().slideToggle('slow');
    		});
    	}
    });
    
    var width = jQuery(window).width();

    if (width > 767) {
        jQuery(document).on('click', 'html, body', function(e) {
            jQuery('.navbar a.level-top').siblings().each(function(e) {
                if (jQuery(this).is(':visible'))
                {
                    jQuery(this).hide();
                }
            });
        });
    }

    if (width <= 697) {
        jQuery('.navbar-collapse').addClass('in');
    }

});