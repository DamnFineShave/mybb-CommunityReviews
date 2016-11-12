<html>
<head>
<title>{$title}</title>
{$headerinclude}
</head>
<body>
{$header}

<div class="community-reviews">

<div class="community-reviews__sections">
    <div class="community-reviews__sections__section community-reviews__sections__section--main">
        {$content}
    </div>
</div>

</div>

{$footer}

<script src="{$mybb->asset_url}/jscripts/report.js"></script>
<script src="{$mybb->asset_url}/jscripts/community_reviews.js"></script>
<script>
communityReviews.maxReviewPhotos = {$mybb->settings['community_reviews_max_review_photos']};
communityReviews.authClientId = "{$mybb->settings['community_reviews_photos_auth_client_id']}";

lang.community_reviews_max_photos_exceeded = "{$lang->community_reviews_product_photos}";
</script>
</body>
</html>
