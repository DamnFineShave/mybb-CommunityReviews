<html>
<head>
<title>{$title}</title>
{$headerinclude}
<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/blueimp-gallery/css/blueimp-gallery.min.css" />
</head>
<body>
{$header}

<div class="community-reviews">

<div class="community-reviews__sections">
    <div class="community-reviews__sections__section community-reviews__sections__section--side">
        {$searchForm}
        {$sectionSideContent}
    </div>
    <div class="community-reviews__sections__section community-reviews__sections__section--main">
        {$content}
    </div>
</div>

</div>

{$footer}


<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls">
    <div class="slides"></div>
    <h3 class="title"></h3>
    <a class="prev">‹</a>
    <a class="next">›</a>
    <a class="close">×</a>
    <a class="play-pause"></a>
    <ol class="indicator"></ol>
</div>
<script src="{$mybb->asset_url}/jscripts/blueimp-gallery/js/blueimp-gallery.min.js"></script>
<script>
$('.community-reviews__review .community-reviews__photo a').on('click', function (event) {
    event = event || window.event;
    var target = event.target || event.srcElement,
        link = $(this)[0],
        options = {index: link, event: event},
        links = $(this).parents('.community-reviews__review').find('.community-reviews__photo a');
    blueimp.Gallery(links, options);
});
</script>

<script src="{$mybb->asset_url}/jscripts/report.js"></script>
<script src="{$mybb->asset_url}/jscripts/community_reviews.js"></script>
<script>
communityReviews.maxReviewPhotos = {$mybb->settings['community_reviews_max_review_photos']};
communityReviews.authClientId = "{$mybb->settings['community_reviews_photos_auth_client_id']}";

lang.community_reviews_max_photos_exceeded = "{$lang->community_reviews_product_photos}";
</script>
</body>
</html>
