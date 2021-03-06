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

            for (var i = 0; i < numFiles; i++) {
                file = files[i];

                if (file.type.match(/image\/*/)) {
                    input.closest('form').find('button').attr('disabled', 'disabled');

                    var formData = new FormData();
                    formData.append('image', file);

                    $.ajax({
                        xhr: function() {
                            var xhr = new window.XMLHttpRequest();

                            xhr.upload.addEventListener('loadstart', function(evt) {
                                this.fileId = Math.floor((Math.random() * 100000));

                                $('<div class="community-reviews__photo-loader" id="photoLoader' + this.fileId + '"><div class="community-reviews__photo-loader__bar"><div></div></div></div>').appendTo('#review_photos_preview');
                            }, false);

                            xhr.upload.addEventListener('progress', function(evt) {
                                if (evt.lengthComputable) {
                                    var percentComplete = parseInt((evt.loaded / evt.total) * 100);
                                    $('#photoLoader' + this.fileId).attr('data-progress', percentComplete).find('.community-reviews__photo-loader__bar div').animate({ width: percentComplete + '%' });
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
                        success: function(response, status, xhr) {
                            $('.community-reviews__photo-loader[data-progress=100]').remove();

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

                            if ($('#review_photos_preview .community-reviews__photo').length) {
                                $('#review_photos_preview .community-reviews__photo:last').after($photo);
                            } else {
                                $('#review_photos_preview').prepend($photo);
                            }
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

    setCommentListPage: function(productId, pageNo, event)
    {
        $('#comment-list').fadeTo('fast', 0.5);

        $.get(rootpath + '/xmlhttp.php', {
            action: 'community_reviews_product_comments',
            product: productId,
            page: pageNo,
        }, function (response) {
            commentsPageNo = pageNo;
            $('#comments-pagination').html(response.paginationHtml);
            $('#comment-list').html(response.html);
            $('#comment-list').fadeTo('fast', 1);
        });
    }
};
