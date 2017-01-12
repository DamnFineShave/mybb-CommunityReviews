<?php

trait CommunityReviewsLogicFrontend
{
    public static function processProductReview($product, &$review, &$reviewFields, &$errors, $validateProductReviewFields = true)
    {
        global $mybb, $db, $lang;

        // validate & save submitted data
        if ($validateProductReviewFields) {
            self::validateProductReviewFields($review, $reviewFields, $errors);
        }

        if (!$errors) {
            if ($review['id']) {
                $reviewId = $review['id'];

                self::updateReview($review['id'], [
                    'price' => $db->escape_string($review['price']),
                    'url' => $db->escape_string($review['url']),
                    'comment' => $db->escape_string($review['comment']),
                ]);

                foreach ($reviewFields as $field) {
                    if ($field['new']) {
                        self::addReviewField([
                            'review_id' => (int)$review['id'],
                            'field_id' => (int)$field['id'],
                            'comment' => $field['comment'],
                            'rating' => $field['rating'],
                        ]);
                    } else {
                        self::updateReviewField($review['fields'][$field['id']]['id'], [
                            'comment' => $db->escape_string($field['comment']),
                            'rating' => $db->escape_string($field['rating']),
                        ]);
                    }
                }

                if (isset($review['delete_photos'])) {
                    foreach ($review['delete_photos'] as $photoId) {
                        self::deletePhoto($photoId);
                    }
                }
            } else {
                $reviewId = self::addReview([
                    'product_id' => (int)$product['id'],
                    'user_id' => (int)$mybb->user['uid'],
                    'ipaddress' => get_ip(),
                    'price' => $review['price'],
                    'url' => $review['url'],
                    'comment' => $review['comment'],
                ]);

                foreach ($reviewFields as $field) {
                    self::addReviewField([
                        'review_id' => (int)$reviewId,
                        'field_id' => (int)$field['id'],
                        'comment' => $field['comment'],
                        'rating' => (int)$field['rating'],
                    ]);
                }
            }

            self::updateProductRating($product['id']);

            if ($review['new_photos']) {
                foreach ($review['new_photos'] as $photo) {
                    self::addPhoto([
                        'review_id' => $reviewId,
                        'url' => $photo['photo_url'],
                        'thumbnail_url' => $photo['thumbnail_url'],
                    ]);
                }
            }

            if (isset($review['removed_merchants'])) {
                foreach ($review['removed_merchants'] as $userId) {
                    self::deleteReviewMerchant($review['id'], $userId);
                }
            }
            if (isset($review['added_merchants'])) {
                foreach ($review['added_merchants'] as $userId) {
                    self::addReviewMerchant([
                        'review_id' => $reviewId,
                        'user_id' => $userId,
                    ]);
                }
                self::sendMerchantTagAlert($product, $reviewId, $review['added_merchants']);
            }

            if ($review['id']) {
                redirect(self::url('review', $product['id'], self::toSlug($product['name']), (int)$reviewId), $lang->community_reviews_review_updated);
            } else {
                redirect(self::url('review', $product['id'], self::toSlug($product['name']), (int)$reviewId), $lang->community_reviews_review_added);
            }
        }
    }

    public static function validateProductReviewFields(&$review, &$reviewFields, &$errors)
    {
        global $mybb, $db, $lang;

        // price
        if (!isset($mybb->input['price']) || strlen($mybb->input['price']) > 255) {
            $review['price'] = '';
        } else {
            $review['price'] = $mybb->get_input('price');
            $review['price'] = str_replace(str_split('!@#%^&*()-=_+[]{};\'\:"|/<>?\\'), '', $review['price']);
        }

        // url
        if (empty($mybb->input['url']) || strlen($mybb->input['url']) > 255) {
            $review['url'] = '';
        } else {
            if (!preg_match('_^(?:(?:https?)://)?(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$_iuS', $mybb->get_input('url'))) {
                $errors .= inline_error($lang->community_reviews_url_invalid);
                $review['url'] = $mybb->get_input('url');
            } else {
                $review['url'] = $mybb->get_input('url');

                if (!preg_match('#^https?://#', $review['url'])) {
                    $review['url'] = 'http://' . $review['url'];
                }
            }

        }

        // comment
        if (empty($mybb->input['comment']) || strlen($mybb->get_input('comment')) > 2000) {
            $review['comment'] = '';
        } else {
            $review['comment'] = $mybb->get_input('comment');
        }

        // photos
        $reviewPhotos = self::getReviewPhotos($review['id']);
        $reviewPhotosNum = count($reviewPhotos);

        if (isset($mybb->input['delete_photos']) && is_array($mybb->input['delete_photos'])) {
            foreach ($mybb->input['delete_photos'] as $photoId) {
                if (isset($reviewPhotos[$photoId])) {
                    $review['delete_photos'][] = $photoId;
                    $reviewPhotosNum--;
                }
            }
        }

        if (isset($mybb->input['review_photos']) && is_array($mybb->input['review_photos'])) {
            $numPhotos = min(
                min(
                    self::settings('max_review_photos') - $reviewPhotosNum,
                    count($mybb->input['review_photos'])
                ),
                self::settings('max_review_photos')
            );

            for ($i = 1; $i <= $numPhotos; $i++) {
                $photoUrl = $mybb->input['review_photos'][$i - 1];

                if ($photoUrls = self::getPhotoUrls($photoUrl)) {
                    $data = $photoUrls;
                    $data['new'] = true;
                    $review['new_photos'][] = $data;
                }
            }
        }

        $totalPhotos = $reviewPhotosNum;

        if (isset($review['new_photos'])) {
            $totalPhotos += count($review['new_photos']);
        }

        if (self::settings('require_review_photos') && $totalPhotos == 0) {
            $errors .= inline_error($lang->community_reviews_photo_required);
        }

        // merchants
        $review['merchants'] = [];

        if (!empty($mybb->input['merchants']) || !empty($review['merchants_data'])) {
            if (!empty($review['merchants_data'])) {
                $currentMerchantsUserIds = array_column($review['merchants_data'], 'user_id');
            } else {
                $currentMerchantsUserIds = [];
            }

            if (!empty($mybb->get_input('merchants'))) {
                $newMerchantsUserIds = [];

                $list = explode(',', $mybb->get_input('merchants'));

                // reduce the list to 1 element
                $list = [array_shift($list)];

                $users = self::getUsersByUsername($list, 'uid,username,usergroup,additionalgroups');

                if ($db->num_rows($users)) {
                    while ($user = $db->fetch_array($users)) {
                        if (is_member(self::settings('merchant_group'), $user)) {
                            $newMerchantsUserIds[] = $user['uid'];
                        }
                    }
                }
            } else {
                $newMerchantsUserIds = [];
            }

            $review['removed_merchants'] = array_diff($currentMerchantsUserIds, $newMerchantsUserIds);
            $review['added_merchants'] = array_diff($newMerchantsUserIds, $currentMerchantsUserIds);
        }

        // fields
        $reviewFieldsErrors = false;

        foreach ($reviewFields as $fieldId => $field) {
            $fieldComment = $mybb->get_input($field['id'] . '_comment');
            $fieldRating = $mybb->get_input($field['id'] . '_rating', MyBB::INPUT_INT);

            if ($fieldComment) {
                $reviewFields[$fieldId]['comment'] = $fieldComment;
            } else {
                if (!$reviewFieldsErrors) {
                    $errors .= inline_error($lang->community_reviews_add_review_error);
                    $reviewFieldsErrors = true;
                }
            }
            if ($fieldRating >= 1 && $fieldRating <= 5) {
                $reviewFields[$fieldId]['rating'] = $fieldRating;
            } else {
                if (!$reviewFieldsErrors) {
                    $errors .= inline_error($lang->community_reviews_add_review_error);
                    $reviewFieldsErrors = true;
                }
            }
        }
    }

    public static function processProductComment($product, &$comment, &$errors, $validateProductComment = true)
    {
        global $mybb, $db, $lang;

        // validate & save submitted data
        if ($validateProductComment) {
            self::validateProductComment($comment, $errors);
        }

        if (!$errors) {
            if ($comment['id']) {
                self::updateComment($comment['id'], [
                    'comment' => $db->escape_string($comment['comment']),
                ]);

                redirect(self::url('comment', $product['id'], self::toSlug($product['name']), (int)$comment['id']), $lang->community_reviews_comment_updated);
            } else {
                $commentId = self::addComment([
                    'product_id' => (int)$product['id'],
                    'review_id' => (int)$comment['review_id'],
                    'user_id' => (int)$mybb->user['uid'],
                    'ipaddress' => get_ip(),
                    'comment' => $comment['comment'],
                ]);

                redirect(self::url('comment', $product['id'], self::toSlug($product['name']), (int)$commentId), $lang->community_reviews_comment_added);
            }
        }
    }

    public static function validateProductComment($product, $review, &$comment, &$errors)
    {
        global $mybb, $lang;

        if (!isset($mybb->input['comment']) || strlen($mybb->input['comment']) > 2000) {
            $comment['comment'] = '';
        } else {
            $comment['comment'] = $mybb->get_input('comment');
        }

        if (!isset($review) || $review['product_id'] != $product['id']) {
            $comment['review_id'] = '';
        } else {
            $comment['review_id'] = (int)$review['id'];
        }
    }
}
