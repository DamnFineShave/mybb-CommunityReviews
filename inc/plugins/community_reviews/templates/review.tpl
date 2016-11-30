<div class="community-reviews__review" id="review{$review['id']}">
    <div class="community-reviews__review__header">
        <div class="community-reviews__review__header__meta">
            <div class="community-reviews__review__header__meta__rating">{$ratingStars} {$lang->community_reviews_rating_from}</div>
            <p class="community-reviews__review__header__meta__username">{$profileLink}</p> &middot;
            <p class="community-reviews__review__header__meta__date">{$date}</p>
        </div>
        <div class="community-reviews__controls">
            <p class="community-reviews__controls__moderation">{$reportLink}{$editLink}{$deleteLink}</p>
            <p class="community-reviews__controls__id"><a href="{$reviewUrl}" class="community-reviews__controls__link"></a></p>
        </div>
    </div>
    <div class="community-reviews__review__body">
        <div class="community-reviews__review__properties">{$reviewProperties}</div>
        <div class="community-reviews__review__photos">{$reviewPhotos}</div>
        {$reviewFields}
        {$reviewComment}
    </div>
</div>
