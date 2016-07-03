;
window.hasOwnProperty = function (obj) {
    return (this[obj]) ? true : false;
};

if (!window.hasOwnProperty('IWD')) {
    IWD = {};
}

IWD.ProductVideo = {
    LOCAL: 'local',
    YOUTUBE: 'youtube',
    VIMEO: 'vimeo',
    updateVideoPositionUrl: '',
    urlGetVideo:'',
    imageNote: {
        local: "Upload thumbnail from you PC.</br>Supported formats: jpg, jpeg, png, gif",
        youtube: "Thumbnail will upload from YouTube, but you can upload custom thumbnail.</br>Supported formats: jpg, jpeg, png, gif",
        vimeo: "Thumbnail will upload from Vimeo, but you can upload custom thumbnail.</br>Supported formats: jpg, jpeg, png, gif"
    },
    uploadedVideos: {},

    init: function () {
        IWD.ProductVideo.autoFillButtonAdd();

        IWD.ProductVideo.videoTypeOption();

        jQuery('#video_type').on("change", function () {
            IWD.ProductVideo.videoTypeOption();
        });

        jQuery("#iwd_productvideo").on("mouseenter", function () {
            if (!IWD.ProductVideo.eventsList(jQuery(".sortable_video")))
                IWD.ProductVideo.sort();
            IWD.ProductVideo.playVideo();
        });

        jQuery('#video_popup_close').bind("click touchstart", function (event) {
            event.preventDefault();
            IWD.ProductVideo.closePlayerInPopup();
        });
    },

    playVideo: function(){
        jQuery('.play-button').on('click touchstart', function () {
            jQuery(this).closest('tr').click();
            IWD.ProductVideo.preLoaderShow();
            IWD.ProductVideo.loadVideo(jQuery(this).attr('data-video-id'));
        });
    },

    preLoaderHide: function () {
        jQuery('#iwd_video_popup_pre_loader').css('display', 'none');
    },

    closePlayerInPopup: function () {
        jQuery("#iwd_product_video_popup_overlay").css("display", "none");

        jQuery("#iwd_product_video_popup_overlay h4").html("");
        jQuery('#iwd_product_video_popup_overlay .video-player').html("");
        jQuery('#iwd_product_video_popup_overlay p').html("");
    },

    preLoaderShow: function () {
        jQuery("#iwd_product_video_popup_overlay").css("display", "block");
        jQuery('#iwd_video_popup_pre_loader').css('display', 'block');
    },

    loadVideo: function (video_id) {
        if (!jQuery.isNumeric(video_id))
            return;

        if (IWD.ProductVideo.uploadedVideos[video_id] && IWD.ProductVideo.uploadedVideos[video_id] !== 'loading') {
            IWD.ProductVideo.loadPlayerTo(IWD.ProductVideo.uploadedVideos[video_id]);
            return;
        }

        if (IWD.ProductVideo.uploadedVideos[video_id] === 'loading')
            return;

        IWD.ProductVideo.uploadedVideos[video_id] = 'loading';

        jQuery.ajax({url: IWD.ProductVideo.urlGetVideo,
            type: "POST",
            dataType: 'json',
            data: "video_id=" + video_id,
            success: function (result) {
                if (result.status == 1) {
                    IWD.ProductVideo.uploadedVideos[video_id] = result;
                    IWD.ProductVideo.loadPlayerTo(result);
                }
            },
            error: function () {
                IWD.ProductVideo.uploadedVideos[video_id] = null;
                IWD.ProductVideo.loadPlayerTo(null);
            }
        });
    },

    loadPlayerTo: function (result) {
        if (result != null) {
            jQuery("#iwd_product_video_popup_overlay h4").html(result.title);
            jQuery('#iwd_product_video_popup_overlay .video-player').html(result.embed_code);
            jQuery('#iwd_product_video_popup_overlay p').html(result.description);

            IWD.ProductVideo.localVideoPlayerInit();

            IWD.ProductVideo.preLoaderHide();
        }
    },

    localVideoPlayerInit: function () {
        if (jQuery('.local-video-player').length > 0) {
            videojs(document.getElementsByClassName('local-video-player')[0], {}, function () {
            });
            IWD.ProductVideoView.centeringBlock(jQuery('.vjs-big-play-button'), jQuery('.local-video-player'));
        }
    },

    eventsList: function (element) {
        //for different version jQuery
        if (jQuery._data(element[0], 'events') !== undefined) return true;
        if (element.data('events') !== undefined) return true;
        if (jQuery.data(element, 'events') !== undefined) return true;
        if (jQuery._data(element, 'events') !== undefined) return true;
        return false;
    },

    sort: function () {
        jQuery(".sortable_video").sortable({
            containment: "parent",
            update: function (event, ui) {
                var sortableArray = jQuery(this).sortable("toArray");
                var videoIDs = sortableArray.map(function (e) {
                    return e.split("_").first();
                });
                var productID = sortableArray.first().split('_').last();

                IWD.ProductVideo.showLoadingMask();

                jQuery.ajax({
                    url: IWD.ProductVideo.updateVideoPositionUrl,
                    type: "POST",
                    dataType: 'json',
                    data: 'video_ids=' + videoIDs + '&product_id=' + productID + '&form_key=' + FORM_KEY,
                    success: function (result) {
                        IWD.ProductVideo.addMessage('Sorted position of the video was successfully changed', 'success');
                    },
                    error: function (result) {
                        IWD.ProductVideo.addMessage('Sorted position of the video was not changed', 'error');
                    }
                });
            }
        });
    },

    addMessage: function (message, type) {
        jQuery('#loading-mask').hide();

        jQuery("#messages")
            .append('<ul class="messages"><li class="' + type + '-msg">' + message + '</li></ul>')
            .show()
            .delay(2000)
            .hide(1000);

        setTimeout(function () {
            jQuery('#messages').empty();
        }, 3000);
    },

    showLoadingMask: function () {
        var width = jQuery("html").width();
        var height = jQuery("html").height();
        jQuery('#loading-mask').width(width).height(height).css('top', 0).css('left', -2).show();
    },

    videoTypeOption: function () {
        IWD.ProductVideo.hideOptions();
        IWD.ProductVideo.showOption();
        IWD.ProductVideo.autoFillButtonShow(jQuery('#video_type').val());
    },

    hideOptions: function () {
        var options = jQuery.map(jQuery("#video_type option"), function (ele) {
            return ele.value;
        });

        jQuery.each(options, function (index, option) {
            var opt = jQuery('#' + option + '_url');
            opt.removeClass('required-entry');
            opt.parents('tr').hide();
            opt.attr('name', option + '_url');
        });
    },

    showOption: function () {
        var value = jQuery('#video_type').val();
        var selectedValue = jQuery('#' + value + '_url');
        selectedValue.parents('tr').show();
        selectedValue.attr('name', 'url');
        selectedValue.addClass('required-entry');
        jQuery('#image').removeClass('required-entry');
        jQuery('label[for="image"] span[class="required"]').hide();


        if (jQuery("#video_id").length != 0 && value == IWD.ProductVideo.LOCAL) {
            jQuery('label[for="local_url"] span[class="required"]').hide();
            selectedValue.removeClass('required-entry');
        }
        if (jQuery("#video_id").length == 0 && value == IWD.ProductVideo.LOCAL) {
            jQuery('label[for="image"] span[class="required"]').css('display', 'inline-block');
            jQuery('#image').addClass('required-entry');
        }

        jQuery("#image_note").html(IWD.ProductVideo.imageNote[value]);
    },

    centeringBlock: function(block, parent){
        block.css("top", parent.height() / 2 - block.height() / 2 + "px")
            .css("left", parent.width() / 2 - block.width() / 2 + "px");
    },

    autoFillButtonAdd: function () {
        jQuery("#title").parents("tr").append('<td id="upload_info"><span class="form-button">Autofill Information</span></td>');

        jQuery('#upload_info').on("click", function () {
            IWD.ProductVideo.showLoadingMask();

            switch (jQuery('#video_type').val()) {
                case IWD.ProductVideo.LOCAL:
                    break;

                case IWD.ProductVideo.YOUTUBE:
                    IWD.ProductVideo.getYouTubeInfo(jQuery('#youtube_url').val());
                    break;

                case IWD.ProductVideo.VIMEO:
                    IWD.ProductVideo.getVimeoInfo(jQuery('#vimeo_url').val());
                    break;
            }
            jQuery('#loading-mask').hide();
        });
    },

    autoFillButtonShow: function (type) {
        jQuery("#upload_info").hide();

        switch (type) {
            case IWD.ProductVideo.YOUTUBE:
            case IWD.ProductVideo.VIMEO:
                jQuery("#upload_info").show();
                break;
        }
    },

    getYouTubeInfo: function (code) {
        if (code == "") {
            jQuery('#youtube_url').focus().addClass("error_input");
            return;
        }

        try {
            jQuery.ajax({
                url: "http://gdata.youtube.com/feeds/api/videos/" + code + "?v=2&alt=json",
                dataType: "jsonp",
                success: function (data) {
                    jQuery("#title").val(data.entry.title.$t);
                    jQuery("#description").val(data.entry.media$group.media$description.$t);
                }
            });
        } catch (er) {
            console.log("ERROR");
        }
    },

    getVimeoInfo: function (code) {
        if (code == "") {
            jQuery('#vimeo_url').focus().addClass("error_input");
            return;
        }

        jQuery.ajax({
            type: 'GET',
            url: 'http://vimeo.com/api/v2/video/' + code + '.json',
            jsonp: 'callback',
            dataType: 'jsonp',
            success: function (data) {
                var video = data[0];
                jQuery("#title").val(video.title);
                jQuery("#description").val(video.description);
            }
        });
    }
};
