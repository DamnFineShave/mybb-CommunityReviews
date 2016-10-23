<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder community-reviews__widget">
    <thead>
        <tr>
            <td class="thead">
                <p class="community-reviews__widget__controls">
                    <i class="fa fa-fw fa-chevron-left community-reviews__widget__controls__previous"></i>
                    <i class="fa fa-fw fa-chevron-right community-reviews__widget__controls__next"></i>
                </p>
                <strong>{$lang->community_reviews_recent_reviews}</strong>
            </td>
        </tr>
    </thead>
    <tbody>
        {$entries}
    </tbody>
</table>
<br />

<script src="{$mybb->asset_url}/jscripts/community_reviews.js"></script>
<script>
communityReviews.widgetInit();
communityReviews.widgetPageMax = {$widgetPageMax};
</script>
