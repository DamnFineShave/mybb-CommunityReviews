var communityReviews = {
    widgetPage: 1,
    widgetPageMax: false,

    handleUpload: function(input)
    {
        input.on('change', function() {
            var files = $(this)[0].files;

            if (!files) {
                return;
            }

            var numFiles = files.length;

            if (numFiles > communityReviews.maxReviewPhotos) {
                alert('max_exceeded');
                return;
            }

            for (i = 0; i < numFiles; i++) {
                file = files[i];

                if (file.type.match(/image\/*/)) {
                    input.closest('form').find('button').attr('disabled', 'disabled');

                    var formData = new FormData();
                    formData.append('image', file);

                    var $loader = $('<div class="community-reviews__photo-loader"><div class="community-reviews__photo-loader__bar"><div></div></div></div>');
                    $loader.appendTo('#review_photos_preview');

                    $.ajax({
                        xhr: function() {
                            var xhr = new window.XMLHttpRequest();
                            xhr.upload.addEventListener('progress', function(evt) {
                                if (evt.lengthComputable) {
                                    var percentComplete = parseInt((evt.loaded / evt.total) * 100);
                                    $loader.find('.community-reviews__photo-loader__bar div').animate({ width: percentComplete + '%' });
                                }
                            }, false);

                            return xhr;
                        },
                        url: 'https://api.imgur.com/3/image',
                        headers: {
                            'Authorization': 'Client-ID ' + communityReviews.authClientId,
                        },
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $loader.remove();

                            input.closest('form').find('button').removeAttr('disabled');

                            $('<input />').attr({
                                'type': 'hidden',
                                'name': 'review_photos[]',
                                'value': response.data.link,
                            }).appendTo(input.closest('form'));

                            var photoUrl = response.data.link.replace(/^http:/i, 'https:');
                            var thumbnailUrl = photoUrl.replace(/(\.[a-z]+)$/i, 'm$1');

                            var $photo = $('<div class="community-reviews__photo"></div>');
                            var $container = $('<div class="community-reviews__photo__image-container"></div>');
                            var $image = $('<div class="community-reviews__photo__image" style="background-image:url(' + thumbnailUrl + ')"></div>');
                            var $options = $('<div class="community-reviews__photo__options"><label><input type="radio" name="first_photo" value="' + photoUrl + '"> ' + lang.community_reviews_first_photo + '</label></div>');

                            $image.appendTo($container);
                            $container.appendTo($photo);
                            $options.appendTo($photo);

                            $('#review_photos_preview').append($photo);
                        },
                    });
                }
            }
        });
    },

    reportProduct: function(id)
	{
		MyBB.popupWindow("/report.php?modal=1&type=community_reviews_product&pid=" + id);
	},

    reportReview: function(id)
	{
		MyBB.popupWindow("/report.php?modal=1&type=community_reviews_review&pid=" + id);
	},

    reportComment: function(id)
	{
		MyBB.popupWindow("/report.php?modal=1&type=community_reviews_comment&pid=" + id);
	},

    widgetInit: function()
    {
        if (communityReviews.widgetPageMax != 1) {
            $('.community-reviews__widget__controls__next').css({ 'visibility': 'visible' });
        }

        $('.community-reviews__widget__controls__previous').on('click', function(){
            if (communityReviews.widgetPage > 1) {
                communityReviews.widgetPage--;
                communityReviews.updateWidget();
            }
        });

        $('.community-reviews__widget__controls__next').on('click', function(){
            if (!communityReviews.widgetPageMax || communityReviews.widgetPage < communityReviews.widgetPageMax) {
                communityReviews.widgetPage++;
                communityReviews.updateWidget();
            }
        });
    },

    updateWidget: function()
    {
        $.get(rootpath + '/xmlhttp.php', {
            action: 'community_reviews_recent',
            page: communityReviews.widgetPage,
        }, function (response) {
            $('.community-reviews__widget tbody tr').remove();
            $('.community-reviews__widget tbody').append(response.html);

            if (!response.next) {
                communityReviews.widgetPageMax = communityReviews.widgetPage;
            }

            if (communityReviews.widgetPage == 1) {
                $('.community-reviews__widget__controls__previous').css({ 'visibility': 'hidden' });
            } else {
                $('.community-reviews__widget__controls__previous').css({ 'visibility': 'visible' });
            }

            if (communityReviews.widgetPage == communityReviews.widgetPageMax) {
                $('.community-reviews__widget__controls__next').css({ 'visibility': 'hidden' });
            } else {
                $('.community-reviews__widget__controls__next').css({ 'visibility': 'visible' });
            }
        });
    },
};
