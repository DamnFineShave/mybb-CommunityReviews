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
        <div class="community-reviews__actions">
            <a href="{$addReviewUrl}" class="community-reviews__button button">{$lang->community_reviews_add_review}</a>
        </div>
    </div>
    {$reviewList}
    <div class="community-reviews__pagination">{$reviewsMultipage}</div>

    <br />

    <p class="community-reviews__section-title">{$lang->community_reviews_product_comments}</p>
    <div class="community-reviews__content-options">
        <div class="community-reviews__actions">
            <a href="{$addCommentUrl}" class="community-reviews__button button">{$lang->community_reviews_add_comment}</a>
        </div>
    </div>
    <div id="comment-list">
        {$commentList}
    </div>
    <div id="comments-pagination" class="community-reviews__pagination">{$commentsMultipage}</div>
</section>

<script>
var productId = {$product['id']};
var commentsPageNo = {$commentsPageNo};
var commentsPagesNo = {$commentsPagesNo};

$('#comments-pagination').on('click', 'a', function (event) {
    event.target = $(event.target).closest('a');

    if ($(event.target).hasClass('pagination_next')) {
        var pageNo = commentsPageNo + 1;
    } else if ($(event.target).hasClass('pagination_previous')) {
        var pageNo = commentsPageNo - 1;
    } else if ($(event.target).hasClass('pagination_first')) {
        var pageNo = 1;
    } else if ($(event.target).hasClass('pagination_last')) {
        var pageNo = commentsPagesNo;
    } else if ($(event.target).hasClass('go_page')) {
        return;
    } else {
        var pageNo = parseInt($(this).text());
    }
    communityReviews.setCommentListPage(productId, pageNo, event);
    return false;
});
$('#comments-pagination').on('submit', '.drop_go_page form', function (event) {
    var pageNo = parseInt($(event.target).find('input[name="page"]').val());

    if (pageNo >= 1 && pageNo <= commentsPagesNo) {
        communityReviews.setCommentListPage(productId, pageNo, event);
    }
    
    return false;
});
</script>