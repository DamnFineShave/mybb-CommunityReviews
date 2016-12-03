<?php

trait CommunityReviewsSnippets
{
    public static function buildCategoryListing()
    {
        global $mybb, $db, $lang;

        $categoryListing = '';

        $categories = self::categoryArray(true);

        $reviewsInCategories = [];

        $queryResult = self::countProductsInAllCategories();

        while ($row = $db->fetch_array($queryResult)) {
            $reviewsInCategories[$row['category_id']] = $row['n'];
        }

        foreach ($categories as $id => $data) {
            $numReviews = isset($reviewsInCategories[$data['id']]) ? $reviewsInCategories[$data['id']] : 0;
            $labelText = $lang->sprintf($lang->community_reviews_num_products, $numReviews);
            $categoryUrl = self::url('category', $id, self::toSlug($data['name']));
            eval('$categoryListing .= "' . self::tpl('category_listing_category') . '";');
        }

        return $categoryListing;
    }

    public static function buildRating($rating, $showNoRatingText = true)
    {
        global $lang;

        $roundedRating = round($rating, 2);
        $starRating = round($rating * 2) / 2;

        $title = $lang->sprintf(
            $lang->community_reviews_rating_title,
            $roundedRating,
            5
        );

        $stars = '';

        if ($starRating) {
            for ($starNo = 1; $starNo <= 5; $starNo++) {
                $starStatus = $starNo > $starRating
                    ? $starRating - $starNo == -0.5
                        ? 'half-highlighted'
                        : 'default'
                    : 'highlighted'
                ;
                eval('$stars .= "' . self::tpl('rating_star') . '";');
            }
        } elseif ($showNoRatingText) {
            eval('$stars .= "' . self::tpl('rating_stars_empty') . '";');
        }

        eval('$html = "' . self::tpl('rating_stars') . '";');

        return $html;
    }

    public static function buildProductListing($products)
    {
        global $mybb, $lang;

        $html = '';

        foreach ($products as $productId => $product) {
            $html .= self::buildProductCard($product);
        }

        return $html;
    }

    public static function buildReviewListing($products)
    {
        global $mybb, $lang;

        $html = '';

        foreach ($products as $productId => $product) {
            $html .= self::buildProductCard($product, true);
        }

        return $html;
    }

    public static function buildProductCard($product, $isReview = false)
    {
        global $mybb, $lang;

        if (isset($product['username'])) {
            $profileLink = build_profile_link(format_name(htmlspecialchars_uni($product['username']), $product['usergroup'], $product['displaygroup']), $product['user_id']);
            $addedBy = $lang->sprintf($lang->community_reviews_added_by, $profileLink);
        } else {
            $addedBy = '';
        }

        if (isset($product['category_name'])) {
            $categoryLink = '<a href="' . self::url('category', $product['category_id'], self::toSlug($product['category_name'])) . '">' . $product['category_name'] . '</a>';
            $addedIn = $lang->sprintf($lang->community_reviews_added_in, $categoryLink);
        } else {
            $addedIn = '';
        }

        $labels = '';

        if (isset($product['num_reviews'])) {
            $label = $lang->sprintf($lang->community_reviews_num_reviews, $product['num_reviews']);
            eval('$labels .= "' . self::tpl('product_card_label') . '";');
        } else {
            $label = '';
        }

        if (!$isReview) {
            $label = $lang->sprintf($lang->community_reviews_num_views, $product['views']);
            eval('$labels .= "' . self::tpl('product_card_label') . '";');
        }

        $description = '';

        $starRating = self::buildRating($isReview && isset($product['review_rating']) ? $product['review_rating'] : $product['cached_rating']);

        if (mb_strlen($product['name']) + 1 > self::settings('product_name_length_card')) {
            $title = htmlspecialchars_uni(mb_substr($product['name'], 0, self::settings('product_name_length_card'))) . '&hellip;';
        } else {
            $title = htmlspecialchars_uni($product['name']);
        }

        if (!$isReview) {
            $url = self::url('product', $product['id'], self::toSlug($product['name']));
        } else {
            $url = self::url('review', $product['product_id'], self::toSlug($product['name']), $product['id']);
        }

        $product['url'] = $url;

        if (!empty($product['photos'])) {
            $photos = self::buildCardThumbnails($product, $product['photos']);
        } elseif (!empty($product['thumbnail_url'])) {
            $photos = self::buildCardThumbnails($product, [$product['thumbnail_url']]);
        } else {
            $photos = '';
        }

        eval('$html = "' . self::tpl('product_card') . '";');

        return $html;
    }

    public static function buildCardThumbnails($product, $urls)
    {
        $html = '';

        foreach ($urls as $url) {
            $thumbnailUrl = htmlspecialchars_uni(self::getResourceUrl($url));
            eval('$html .= "' . self::tpl('product_card_photo') . '";');
        }

        return $html;
    }

    public static function buildProductReview($product, $review, $categoryReviewFields, $photosArray, $reviewsMerchants)
    {
        global $mybb, $lang;

        $html = '';

        // general information
        $profileLink = build_profile_link(format_name(htmlspecialchars_uni($review['username']), $review['usergroup'], $review['displaygroup']), $review['user_id']);
        $date = my_date('relative', $review['date']);
        $reviewUrl = self::url('review', $product['id'], self::toSlug($product['name']), (int)$review['id']);

        if ($fieldRatings = array_column($review['fields'], 'rating')) {
            $ratingStars = self::buildRating(self::getTotalRating($fieldRatings));
        } else {
            $ratingStars = '';
        }

        if (self::isUser()) {
            $reportLink = '<a href="javascript:communityReviews.reportReview(' . $review['id'] . ')" title="' . $lang->community_reviews_report . '" class="community-reviews__controls__report"></a>';
        } else {
            $reportLink = '';
        }

        if (self::isModOrAuthor($review['user_id'])) {
            $editLink = '<a href="' . self::url('edit_review', $product['id'], self::toSlug($product['name']), (int)$review['id']) . '" title="' . $lang->community_reviews_edit . '" class="community-reviews__controls__edit"></a>';
            $deleteLink = '<a href="' . self::url('delete_review', $product['id'], self::toSlug($product['name']), (int)$review['id']) . '" title="' . $lang->community_reviews_delete . '" class="community-reviews__controls__delete"></a>';
        }

        // properties
        $reviewProperties = '';

        if ($review['price']) {
            $name = $lang->community_reviews_product_price;
            $value = htmlspecialchars_uni($review['price']);
            eval('$reviewProperties .= "' . self::tpl('review_property') . '";');
        }

        if ($review['url']) {
            $url = htmlspecialchars_uni($review['url']);

            $displayUrl = preg_replace('#^(https?:)?//#', null, $review['url']);
            $displayUrl = mb_strlen($displayUrl) > 30
                ? mb_substr($url, 0, 29) . '&hellip;'
                : $displayUrl
            ;

            $name = $lang->community_reviews_product_url;
            $value = '<a href="' . $url . '" rel="nofollow noopener noreferrer" target="_blank">' . $displayUrl . '</a>';
            eval('$reviewProperties .= "' . self::tpl('review_property') . '";');
        }

        if (isset($reviewsMerchants[ $review['id'] ])) {
            $name = $lang->community_reviews_product_merchants;

            $merchants = [];

            foreach ($reviewsMerchants[ $review['id'] ] as $user) {
                $merchants[] = build_profile_link(format_name(htmlspecialchars_uni($user['username']), $user['usergroup'], $user['displaygroup']), $user['uid']);
            }

            $value = implode(', ', $merchants);

            eval('$reviewProperties .= "' . self::tpl('review_property') . '";');
        }


        // fields
        $reviewFields = self::buildReviewFields($review, $categoryReviewFields);

        // photos
        if (isset($photosArray[ $review['id'] ])) {
            $reviewPhotos = self::buildReviewPhotos($review, $photosArray[ $review['id'] ]);
        } else {
            $reviewPhotos = '';
        }

        if (!empty($review['comment'])) {
            $reviewCommentValue = self::parseComment($review['comment'], $review['username']);
            eval('$reviewComment = "' . self::tpl('review_comment') . '";');
        } else {
            $reviewComment = '';
        }

        eval('$html .= "' . self::tpl('review') . '";');

        return $html;
    }

    public static function buildProductReviews($product, $reviewsArray, $categoryReviewFields, $photosArray, $reviewsMerchants)
    {
        $html = [];

        foreach ($reviewsArray as $review) {
            $html[ $review['id'] ] = self::buildProductReview($product, $review, $categoryReviewFields, $photosArray, $reviewsMerchants);
        }

        return $html;
    }

    public static function buildReviewFields($review, $categoryReviewFields)
    {
        $html = '';

        foreach ($categoryReviewFields as $categoryField) {
            $field = &$review['fields'][$categoryField['id']];

            if ($field) {
                $fieldName = htmlspecialchars_uni($categoryReviewFields[$field['field_id']]['name']);
                $comment = self::parseComment($field['comment'], $review['username']);
                $fieldStars = self::buildRating($field['rating']);

                eval('$html .= "' . self::tpl('review_field') . '";');
            }
        }

        return $html;
    }

    public static function buildReviewPhotos($review, $photos)
    {
        $html = '';

        foreach ($photos as $photo) {
            $url = htmlspecialchars_uni(self::getResourceUrl($photo['url']));
            $thumbnail_url = htmlspecialchars_uni(self::getResourceUrl($photo['thumbnail_url']));
            $options = '';
            eval('$html .= "' . self::tpl('review_photo') . '";');
        }

        return $html;
    }

    public static function buildReviewFormPhotos($photos)
    {
        global $lang;

        $html = '';

        foreach ($photos as $photo) {
            $url = htmlspecialchars_uni(self::getResourceUrl($photo['url']));
            $thumbnail_url = htmlspecialchars_uni(self::getResourceUrl($photo['thumbnail_url']));
            $options = '<label><input type="checkbox" name="delete_photos[]" value="' . $photo['id'] . '" /> ' . $lang->community_reviews_delete . '</label>';
            eval('$html .= "' . self::tpl('review_photo') . '";');
        }

        return $html;
    }

    public static function buildProductComment($product, $comment)
    {
        global $mybb, $lang;

        $html = '';

        // general information
        $profileLink = build_profile_link(format_name(htmlspecialchars_uni($comment['username']), $comment['usergroup'], $comment['displaygroup']), $comment['user_id']);
        $date = my_date('relative', $comment['date']);
        $commentUrl = self::url('comment', $product['id'], self::toSlug($product['name']), $comment['id']);

        if (self::isUser()) {
            $reportLink = '<a href="javascript:communityReviews.reportComment(' . $comment['id'] . ')" title="' . $lang->community_reviews_report . '" class="community-reviews__controls__report"></a>';
        } else {
            $reportLink = '';
        }

        if (self::isModOrAuthor($comment['user_id'])) {
            $editLink = '<a href="' . self::url('edit_comment', $product['id'], self::toSlug($product['name']), (int)$comment['id']) . '" title="' . $lang->community_reviews_edit . '" class="community-reviews__controls__edit"></a>';
            $deleteLink = '<a href="' . self::url('delete_comment', $product['id'], self::toSlug($product['name']), (int)$comment['id']) . '" title="' . $lang->community_reviews_delete . '" class="community-reviews__controls__delete"></a>';
        }

        $commentValue = self::parseComment($comment['comment'], $comment['username']);

        eval('$html .= "' . self::tpl('comment') . '";');

        return $html;
    }

    public static function buildProductComments($product, $commentsArray)
    {
        $html = [];

        foreach ($commentsArray as $comment) {
            $html[ $comment['id'] ] = self::buildProductComment($product, $comment);
        }

        return $html;
    }

    public static function buildProductFeed($feedData, $reviews, $comments)
    {
        $html = '';

        foreach ($feedData as $entry) {
            if ($entry['review_id']) {
                $html .= $reviews[ $entry['review_id'] ];
            } elseif ($entry['comment_id']) {
                $html .= $comments[ $entry['comment_id'] ];
            }
        }

        return $html;
    }

    public static function buildSeparatedProductFeed($feedData, $reviews, $comments)
    {
        $reviewsHtml = '';
        $commentsHtml = '';

        foreach ($feedData as $entry) {
            if ($entry['review_id']) {
                $reviewsHtml .= $reviews[ $entry['review_id'] ];
            } elseif ($entry['comment_id']) {
                $commentsHtml .= $comments[ $entry['comment_id'] ];
            }
        }

        $html = $reviewsHtml . $commentsHtml;

        return $html;
    }

    public static function reviewFormFieldsHtml($review, $reviewFields)
    {
        global $mybb, $lang;

        $html = '';

        if (isset($review['url'])) {
            $productUrlValue = htmlspecialchars_uni($review['url']);
        } else {
            $productUrlValue = '';
        }

        if (isset($review['price'])) {
            $productPriceValue = htmlspecialchars_uni($review['price']);
        } else {
            $productPriceValue = '';
        }

        if (isset($review['photos'])) {
            $reviewPhotos = self::buildReviewFormPhotos($review['photos']);
        }

        if (isset($review['merchants_data'])) {
            $productMerchantsValue = implode(',', array_column($review['merchants_data'], 'username'));
        }

        eval('$html .= "' . self::tpl('add_review_data') . '";');

        foreach ($reviewFields as $reviewField) {
            $reviewFieldId = (int)$reviewField['id'];
            $reviewFieldName = htmlspecialchars_uni($reviewField['name']);
            $reviewFieldComment = !empty($review['fields'][$reviewFieldId]['comment'])
                ? htmlspecialchars_uni($review['fields'][$reviewFieldId]['comment'])
                : htmlspecialchars_uni($reviewField['comment'])
            ;
            $reviewFieldRatingStars = '';

            for ($ratingStarNo = 1; $ratingStarNo <= 5; $ratingStarNo++) {
                if ($reviewField['rating'] && $reviewField['rating'] == $ratingStarNo || ($reviewField['rating'] == 0 && $ratingStarNo == 3)) {
                    $inputAttributes = 'checked="checked"';
                } else {
                    $inputAttributes = '';
                }
                eval('$reviewFieldRatingStars .= "' . self::tpl('form_rating_star') . '";');
            }

            eval('$html .= "' . self::tpl('add_review_field') . '";');
        }

        $reviewCommentValue = htmlspecialchars_uni($review['comment']);

        eval('$html .= "' . self::tpl('add_review_data2') . '";');

        return $html;
    }

    public static function buildWidgetEntry($row)
    {
        global $lang;

        $url = self::url('review', (int)$row['product_id'], self::toSlug($row['name']), (int)$row['id']);

        if (mb_strlen($row['name']) + 1 > self::settings('product_name_length_card')) {
            $title = htmlspecialchars_uni(mb_substr($row['name'], 0, self::settings('product_name_length_card'))) . '&hellip;';
        } else {
            $title = htmlspecialchars_uni($row['name']);
        }

        if (isset($row['username'])) {
            $profileLink = build_profile_link(format_name(htmlspecialchars_uni($row['username']), $row['usergroup'], $row['displaygroup']), $row['user_id']);
            $addedBy = $lang->sprintf($lang->community_reviews_added_by, $profileLink);
        } else {
            $addedBy = '';
            $addedOn = '';
        }

        $date = my_date('relative', $row['date']);
        $addedOn = $lang->sprintf($lang->community_reviews_added_on, $date);

        if ($row['thumbnail_url']) {
            $thumbnailUrl = htmlspecialchars_uni(self::getResourceUrl($row['thumbnail_url']));
            eval('$photo = "' . self::tpl('widget_entry_photo') . '";');
        } else {
            $photo = '';
        }

        eval('$html = "' . self::tpl('widget_entry') . '";');

        return $html;
    }

    public static function buildWidgetEntries($query, $cutoff = false)
    {
        global $db;

        $html = '';

        $numEntries = 0;

        while ($row = $db->fetch_array($query)) {
            $numEntries++;
            $html .= self::buildWidgetEntry($row);

            if ($cutoff && $numEntries == $cutoff) {
                break;
            }
        }

        return $html;
    }

    public static function confirmAction($data)
    {
        global $mybb, $lang;

        if (!$data['permissions']) {
            error_no_permission();
        } else {
            if ($mybb->request_method == 'post') {
                verify_post_check($mybb->get_input('my_post_key'));
                call_user_func_array($data['action_callback'], $data['action_parameters']);
                redirect($data['redirect_url'], $data['redirect_message']);
            } else {
                $text = $data['message'];
                eval('$content = "' . self::tpl('confirm') . '";');
                return $content;
            }
        }
    }
}
