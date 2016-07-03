;window.hasOwnProperty = function (obj) {
    return (this[obj]) ? true : false;
};
if (!window.hasOwnProperty('IWD')) {
    IWD = {};
}

IWD.ProductVideoView = {
    flashPlayer: "/js/iwd/productvideo/video-js.swf",
    containerSlider: "",
    containerImage: "",
    urlGetVideo: "",
    uploadedVideos: {},
    inPopup: 0,
    loading: false,

    init: function (image, slider) {
        IWD.ProductVideoView.centeringBlock(jQuery('.video-launcher .play-button'), jQuery('.video-launcher'));

        videojs.options.flash.swf = IWD.ProductVideoView.flashPlayer;

        IWD.ProductVideoView.containerImage = jQuery(image);
        IWD.ProductVideoView.containerSlider = jQuery(slider);
        IWD.ProductVideoView.changeProductImage();

        jQuery('#video_popup_close').bind("click touchstart", function (event) {
            event.preventDefault();
            IWD.ProductVideoView.closePlayerInPopup();
        });
    },

    changeProductImage: function () {
        IWD.ProductVideoView.containerSlider.find('a.iwd_product_image_thumbnail').each(function () {
            jQuery(this).on('click touchstart', function () {
                IWD.ProductVideoView.loading = false;
                IWD.ProductVideoView.closePlayerInImageBlock();
                IWD.ProductVideoView.preLoaderShow(false);
                IWD.Zoom.containerImage.find('img#image').attr('src', jQuery(this).attr('data-image'));
                jQuery('.colorswatch-zoom-box').find('img#image').attr('src', jQuery(this).attr('data-zoom-image'));
                IWD.ProductVideoView.displayImageBlock();
                IWD.ProductVideoView.preLoaderHide(false);
            });
        });

        IWD.ProductVideoView.containerSlider.find('a.iwd_product_video_thumbnail').each(function () {
            jQuery(this).on('click touchstart', function () {
                IWD.ProductVideoView.loading = true;
                IWD.ProductVideoView.preLoaderShow(true);

                if (!IWD.ProductVideoView.inPopup) {
                    IWD.Zoom.containerImage.find('img#image').attr('src', jQuery(this).attr('data-image'));
                }
                IWD.ProductVideoView.loadVideo(jQuery(this).attr('data-video-id'));
            });
        });
    },

    displayImageBlock: function () {
        jQuery("#iwd_product_video_box").css('display', 'none');
        jQuery("#iwd_product_image_box").css('display', 'block');
    },

    preLoaderHide: function (video) {
        if (IWD.ProductVideoView.inPopup && video) {
            jQuery('#iwd_video_popup_pre_loader').css('display', 'none');
        } else {
            jQuery('#iwd_media_pre_loader').css('display', 'none');
        }
    },
    preLoaderShow: function (video) {
        if (IWD.ProductVideoView.inPopup && video) {
            jQuery("#iwd_product_video_popup_overlay").css("display", "block");
            jQuery('#iwd_video_popup_pre_loader').css('display', 'block');
        } else {
            var pre_loader = jQuery('#iwd_media_pre_loader .ajax-loader-gif');
            IWD.ProductVideoView.centeringBlock(pre_loader, pre_loader.parent());
            jQuery('#iwd_media_pre_loader').css('display', 'block');
        }
    },

    centeringBlock: function (block, parent) {
        block.css("top", parent.height() / 2 - block.height() / 2 + "px")
            .css("left", parent.width() / 2 - block.width() / 2 + "px");
    },

    closePlayerInImageBlock: function () {
        jQuery("#iwd_product_video_box .iwd-product-video-wrapper").html("");
    },

    closePlayerInPopup: function () {
        jQuery("#iwd_product_video_popup_overlay").css("display", "none");

        jQuery("#iwd_product_video_popup_overlay h4").html("");
        jQuery('#iwd_product_video_popup_overlay .video-player').html("");
        jQuery('#iwd_product_video_popup_overlay p').html("");
    },

    loadVideo: function (video_id) {
        if (!jQuery.isNumeric(video_id))
            return;

        if (IWD.ProductVideoView.uploadedVideos[video_id] && IWD.ProductVideoView.uploadedVideos[video_id] !== 'loading') {
            IWD.ProductVideoView.loadPlayerTo(IWD.ProductVideoView.uploadedVideos[video_id]);
            return;
        }

        if (IWD.ProductVideoView.uploadedVideos[video_id] === 'loading')
            return;

        IWD.ProductVideoView.uploadedVideos[video_id] = 'loading';

        jQuery.ajax({url: IWD.ProductVideoView.urlGetVideo,
            type: "POST",
            dataType: 'json',
            data: "video_id=" + video_id,
            success: function (result) {
                if (result.status == 1) {
                    IWD.ProductVideoView.uploadedVideos[video_id] = result;
                    IWD.ProductVideoView.loadPlayerTo(result);
                }
            },
            error: function () {
                IWD.ProductVideoView.uploadedVideos[video_id] = null;
                IWD.ProductVideoView.loadPlayerTo(null);
            }
        });
    },

    loadPlayerTo: function (result) {
        if (IWD.ProductVideoView.loading == true && result != null) {
            if (IWD.ProductVideoView.inPopup == 1)
                IWD.ProductVideoView.loadPlayerToPopupBlock(result);
            else
                IWD.ProductVideoView.loadPlayerToImageBlock(result);
        }
    },

    loadPlayerToPopupBlock: function (result) {
        jQuery("#iwd_product_video_popup_overlay h4").html(result.title);
        jQuery('#iwd_product_video_popup_overlay .video-player').html(result.embed_code);
        jQuery('#iwd_product_video_popup_overlay p').html(result.description);

        IWD.ProductVideoView.localVideoPlayerInit();

        IWD.ProductVideoView.preLoaderHide(true);
    },

    loadPlayerToImageBlock: function (result) {
        jQuery("#iwd_product_image_box").css('display', 'none');
        jQuery("#iwd_product_video_box").css('display', 'block');
        jQuery("#iwd_product_video_box .iwd-product-video-wrapper").html(result.embed_code);

        IWD.ProductVideoView.localVideoPlayerInit();

        IWD.ProductVideoView.preLoaderHide(true);
    },

    localVideoPlayerInit: function () {
        if (jQuery('.local-video-player').length > 0) {
            videojs(document.getElementsByClassName('local-video-player')[0]).ready(function(){
            //    this.play();
            });
            IWD.ProductVideoView.centeringBlock(jQuery('.vjs-big-play-button'), jQuery('.local-video-player'));
        }
    }
};
