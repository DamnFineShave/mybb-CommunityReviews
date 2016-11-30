<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder community-reviews__widget">
    <thead>
        <tr>
            <td class="thead">
                <p class="community-reviews__widget__controls">
                    <a class="controls community-reviews__widget__controls__previous"></a>
                    <a class="controls community-reviews__widget__controls__next"></a>
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
