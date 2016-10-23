<section>
    <p class="community-reviews__section-title">{$lang->community_reviews_product_summary}</p>
    <div class="community-reviews__header community-reviews__product-summary">
        <div class="community-reviews__title">
            <p>{$productTitle}</p>
            {$starRating}
        </div>
        <div class="community-reviews__controls">
            <p class="community-reviews__controls__moderation">{$reportLink}{$editLink}{$deleteLink}{$mergeLink}</p>
            <p class="community-reviews__controls__id"><a href="{$productUrl}">#{$product['id']}</a></p>
        </div>
    </div>
</section>
<section>
    <p class="community-reviews__section-title">{$lang->community_reviews_product_reviews}</p>
    <div class="community-reviews__content-options">
        <div class="community-reviews__modifiers">
            <form action="{$url}">
                {$reviewsOnlyFields}
                <label><input type="checkbox" name="reviews_only" value="1" onchange="this.form.submit()" {$reviewsOnlyParameters} /> {$lang->community_reviews_reviews_only}</label>
            </form>
        </div>
        <div class="community-reviews__actions">
            <a href="{$addCommentUrl}" class="community-reviews__button button">{$lang->community_reviews_add_comment}</a>
            <a href="{$addReviewUrl}" class="community-reviews__button button">{$lang->community_reviews_add_review}</a>
        </div>
    </div>
    {$feed}
    <div class="community-reviews__pagination">{$multipage}</div>
</section>
