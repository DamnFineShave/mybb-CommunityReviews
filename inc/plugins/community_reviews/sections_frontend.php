<?php

trait CommunityReviewsSectionsFrontend
{
    private static function showReviewsPage()
    {
        global $mybb, $db, $templates, $lang, $theme, $footer, $headerinclude, $header, $charset;

        $lang->load('community_reviews');

        // main breadcrumb
        add_breadcrumb($lang->community_reviews_location, self::url('index'));

        // main page layout
        $searchUrl = self::url('search');

        eval('$sectionSideContent = "' . self::tpl('search_form') . '";');

        $categoryListing = self::buildCategoryListing();
        eval('$sectionSideContent .= "' . self::tpl('category_listing') . '";');

        // section dispatch
        if ($mybb->get_input('category')) {
            extract(self::frontendSectionCategory(compact([
                'sectionSideContent',
            ])));
        } elseif ($mybb->get_input('product')) {
            extract(self::frontendSectionProduct());
        } elseif ($mybb->get_input('merchant')) {
            extract(self::frontendSectionMerchantReviews());
        } elseif ($mybb->get_input('search')) {
            extract(self::frontendSectionSearch());
        } else {
            extract(self::frontendSectionIndex(compact([
                'sectionSideContent',
            ])));
        }

        if (isset($mainTemplate)) {
            eval('$page = "' . self::tpl($mainTemplate) . '";');
        } else {
            eval('$page = "' . self::tpl('page') . '";');
        }

        output_page($page);

        exit;
    }

    public static function frontendSectionIndex($data)
    {
        global $db, $lang;

        extract($data);

        $title = $lang->community_reviews_location;

        $recentProducts = '';
        $recentReviews = '';

        $products = self::getProductsDataWithReviewCountAndPhotos('', 'ORDER BY date DESC LIMIT ' . (int)self::settings('recent_items_limit'));

        foreach ($products as $product) {
            $recentProducts .= self::buildProductCard($product);
        }

        $query = self::getReviewsDataWithReviewCountAndPhotos('ORDER BY r.date DESC LIMIT ' . (int)self::settings('recent_items_limit'));

        while ($row = $db->fetch_array($query)) {
            $recentReviews .= self::buildProductCard($row, true);
        }

        eval('$content = "' . self::tpl('index') . '";');

        $reviewCount = self::countReviews();
        $commentCount = self::countComments();
        $productViewCount = self::sumProductViews();

        eval('$sectionSideContent .= "' . self::tpl('statistics') . '";');

        return [
            'title' => $title,
            'content' => $content,
            'sectionSideContent' => $sectionSideContent,
        ];
    }

    public static function frontendSectionCategory($data)
    {
        global $mybb, $lang;

        extract($data);

        if ($category = self::getCategory($mybb->get_input('category', MyBB::INPUT_INT))) {
            add_breadcrumb(htmlspecialchars_uni($category['name']), self::url('category', $category['id'], self::toSlug($category['name'])));

            if ($productId = $mybb->get_input('delete', MyBB::INPUT_INT)) {
                if ($product = self::getProduct($productId)) {
                    $content = self::confirmAction([
                        'permissions' => self::canDeleteUserContent($product['user_id']),
                        'message' => $lang->community_reviews_confirm_delete_product,
                        'action_callback' => [self, 'deleteProduct'],
                        'action_parameters' => [$product['id']],
                        'redirect_url' => self::url('category', $category['id'], self::toSlug($category['name'])),
                        'redirect_message' => $lang->community_reviews_product_deleted,
                    ]);
                } else {
                    self::redirect(self::url('index'));
                }
            } elseif ($productId = $mybb->get_input('merge', MyBB::INPUT_INT)) {
                if ($product = self::getProduct($productId) && self::isMod()) {
                    $sectionTitle = $lang->community_reviews_merge_product;
                    $formActionUrl = self::url('merge_product', $category['id'], self::toSlug($category['name']), $product['id']);
                    $errors = '';
                    $buttonText = $lang->community_reviews_proceed;

                    if ($mybb->request_method == 'post' && $targetProductId = $mybb->get_input('target_product', MyBB::INPUT_INT)) {
                        verify_post_check($mybb->get_input('my_post_key'));

                        if ($targetProduct = self::getProduct($targetProductId)) {
                            if (self::mergeProduct($product['id'], $targetProduct['id'])) {
                                self::redirect(self::url('product', $targetProduct['id'], self::toSlug($targetProduct['name'])));
                            }
                        } else {
                            $errors .= inline_error($lang->community_reviews_product_not_found);
                        }
                    }

                    eval('$content = "' . self::tpl('merge_product') . '";');
                } else {
                    self::redirect(self::url('index'));
                }
            } elseif ($mybb->get_input('add') || $mybb->get_input('edit')) {
                if ($productId = $mybb->get_input('edit', MyBB::INPUT_INT)) {
                    if ($product = self::getProduct($productId)) {
                        if (!self::canEditUserContent($product['user_id'])) {
                            error_no_permission();
                        } else {
                            $title = $lang->community_reviews_update_product . '  - ' . $lang->community_reviews_location;
                            $sectionTitle = $lang->sprintf($lang->community_reviews_update_product_in, htmlspecialchars_uni($product['name']));
                            add_breadcrumb($lang->community_reviews_update_product, self::url('edit_product', $category['id'], self::toSlug($category['name']), (int)$product['id']));
                        }
                    } else {
                        self::redirect(self::url('index'));
                    }
                } else {
                    $title = $lang->community_reviews_add_product . ' - ' . $lang->community_reviews_location;
                    $sectionTitle = $lang->sprintf($lang->community_reviews_add_product_in, htmlspecialchars_uni($category['name']));
                    $formActionUrl = '';
                    add_breadcrumb($lang->community_reviews_add_product, self::url('add_product', $category['id'], self::toSlug($category['name'])));
                }

                if (!self::isUser()) {
                    error_no_permission();
                } else {
                    $errors = '';

                    if ($product) {
                        $nameValue = htmlspecialchars_uni($product['name']);
                        $buttonText = $lang->community_reviews_update_product;
                    } else {
                        $nameValue = '';
                        $buttonText = $lang->community_reviews_add_product;

                        // review fields
                        $categoryReviewFields = self::fieldsInCategoryArray($category['id']);
                        $reviewFields = $categoryReviewFields;

                        // assign field values
                        foreach ($reviewFields as $fieldId => $field) {
                            $reviewFields[$fieldId]['new'] = true;
                            $reviewFields[$fieldId]['comment'] = '';
                            $reviewFields[$fieldId]['rating'] = 0;
                        }
                    }

                    if ($mybb->request_method == 'post' && $mybb->get_input('name')) {
                        verify_post_check($mybb->get_input('my_post_key'));

                        $productsWithSameName = self::countProductsByName($mybb->get_input('name'));

                        if (mb_strlen($mybb->get_input('name')) > self::settings('product_name_length_limit')) {
                            $errors .= inline_error(
                                $lang->sprintf($lang->community_reviews_product_name_too_long, (int)self::settings('product_name_length_limit'))
                            );
                        } elseif ($productsWithSameName != 0) {
                            $errors .= inline_error($lang->community_reviews_product_already_exists);
                        }

                        if (!$product) {
                            $review = [];
                            self::validateProductReviewFields($review, $reviewFields, $errors);
                        }

                        if (!$errors) {
                            if ($product) {
                                $productId = self::updateProduct($product['id'], [
                                    'name' => $mybb->get_input('name'),
                                ]);
                                redirect(self::url('product', $product['id'], self::toSlug($product['name'])), $lang->community_reviews_product_updated);
                            } else {
                                $productId = self::addProduct([
                                    'category_id' => $category['id'],
                                    'name' => $mybb->get_input('name'),
                                    'user_id' => $mybb->user['uid'],
                                ]);
                                // redirect(self::url('product', $productId), $lang->community_reviews_product_added);

                                $product = [
                                    'id' => $productId,
                                    'name' => $mybb->get_input('name'),
                                ];
                                self::processProductReview($product, $review, $reviewFields, $errors, false);
                            }
                        } else {
                            $nameValue = htmlspecialchars_uni($mybb->get_input('name'));
                        }
                    }

                    if ($product) {
                        $reviewFormFieldsHtml = '';
                    } else {
                        $reviewFormFieldsHtml = self::reviewFormFieldsHtml($review, $reviewFields);
                    }

                    eval('$content = "' . self::tpl('add_product') . '";');
                }
            } else {
                $title = htmlspecialchars_uni($category['name']) . '  - ' . $lang->community_reviews_location;
                $sectionTitle = $lang->sprintf($lang->community_reviews_category, htmlspecialchars_uni($category['name']));

                // add link
                $addProductUrl = self::url('add_product', $category['id'], self::toSlug($category['name']));

                $itemsNum = self::countProductsInCategory($category['id']);

                $listManager = new CommunityReviews\ListManager([
                    'mybb'          => $mybb,
                    'baseurl'       => self::url('category', $category['id'], self::toSlug($category['name'])),
                    'order_columns' => ['name', 'rating' => 'cached_rating', 'num_reviews', 'views'],
                    'items_num'     => $itemsNum,
                    'per_page'      => self::settings('per_page'),
                ]);

                // pagination
                $multipage = $listManager->pagination();

                // sorting options
                $sortingOptions .= $listManager->link('name', $lang->community_reviews_sorting_name, $mybb->seo_support);
                $sortingOptions .= $listManager->link('rating', $lang->community_reviews_sorting_rating, $mybb->seo_support);
                $sortingOptions .= $listManager->link('num_reviews', $lang->community_reviews_sorting_reviews, $mybb->seo_support);
                $sortingOptions .= $listManager->link('views', $lang->community_reviews_sorting_views, $mybb->seo_support);

                eval('$sectionSideContent .= "' . self::tpl('sorting_options') . '";');

                // product listing
                $products = self::getProductsWithReviewCountAndPhotosInCategory($category['id'], $listManager->sql());

                $productListing = self::buildProductListing($products);

                eval('$content = "' . self::tpl('category') . '";');
            }

        } else {
            self::redirect(self::url('index'));
        }

        return [
            'title' => $title,
            'content' => $content,
            'sectionSideContent' => $sectionSideContent,
        ];
    }

    public static function frontendSectionProduct()
    {
        global $mybb, $db, $lang;

        if ($product = self::getProduct($mybb->get_input('product', MyBB::INPUT_INT))) {
            $title = htmlspecialchars_uni($product['name']) . '  - ' . $lang->community_reviews_location;

            $productUrl = self::url('product', $product['id'], self::toSlug($product['name']));

            $category = self::getCategory($product['category_id']);

            add_breadcrumb(htmlspecialchars_uni($category['name']), self::url('category', $category['id'], self::toSlug($category['name'])));
            add_breadcrumb(htmlspecialchars_uni($product['name']), self::url('product', $product['id'], self::toSlug($category['name'])));

            $categoryReviewFields = self::fieldsInCategoryArray($category['id']);

            if ($reviewId = $mybb->get_input('delete', MyBB::INPUT_INT)) {
                if ($review = self::getReview($reviewId)) {
                    $content = self::confirmAction([
                        'permissions' => self::canDeleteUserContent($review['user_id']),
                        'message' => $lang->community_reviews_confirm_delete_review,
                        'action_callback' => function () use ($product, $review) {
                            self::deleteReview($review['id']);
                            self::updateProductRating($product['id']);
                        },
                        'action_parameters' => [],
                        'redirect_url' => self::url('product', $product['id'], self::toSlug($product['name'])),
                        'redirect_message' => $lang->community_reviews_review_deleted,
                    ]);
                } else {
                    self::redirect(self::url('index'));
                }
            } elseif ($mybb->get_input('add') || $mybb->get_input('edit')) {
                if ($mybb->get_input('edit', MyBB::INPUT_INT) && $review = self::getReviewData($mybb->get_input('edit', MyBB::INPUT_INT))) {
                    $review['photos'] = self::getReviewPhotos($review['id']);
                    $review['merchants_data'] = self::getReviewMerchantsData($review['id']);

                    if (!self::canEditUserContent($review['user_id'])) {
                        error_no_permission();
                    } else {
                        $title = $lang->community_reviews_update_review . '  - ' . $lang->community_reviews_location;
                        $sectionTitle = $lang->sprintf($lang->community_reviews_edit_review_in, htmlspecialchars_uni($product['name']));
                        add_breadcrumb($lang->community_reviews_update_review, self::url('edit_review', $product['id'], self::toSlug($product['name']), (int)$review['id']));
                    }
                } else {
                    $review = false;
                    $review['merchants_data'] = [];

                    if (!self::isUser()) {
                        error_no_permission();
                    } else {
                        $title = $lang->community_reviews_add_review . '  - ' . $lang->community_reviews_location;
                        $sectionTitle = $lang->sprintf($lang->community_reviews_add_review_in, htmlspecialchars_uni($product['name']));
                        add_breadcrumb($lang->community_reviews_add_review, self::url('add_review', $product['id'], self::toSlug($product['name'])));
                    }
                }

                $errors = '';
                $nameValue = '';

                $reviewFields = $categoryReviewFields;

                // assign field values
                foreach ($reviewFields as $fieldId => $field) {
                    if (isset($review['fields'][$fieldId])) {
                        $reviewFields[$fieldId]['new'] = false;
                        $reviewFields[$fieldId]['comment'] = $review['fields'][$fieldId]['comment'];
                        $reviewFields[$fieldId]['rating'] = $review['fields'][$fieldId]['rating'];
                    } else {
                        $reviewFields[$fieldId]['new'] = true;
                        $reviewFields[$fieldId]['comment'] = '';
                        $reviewFields[$fieldId]['rating'] = 0;
                    }
                }

                if ($mybb->request_method == 'post') {
                    verify_post_check($mybb->get_input('my_post_key'));
                    self::validateProductReviewFields($review, $reviewFields, $errors);
                    self::processProductReview($product, $review, $reviewFields, $errors, false);
                }

                $formActionUrl = $review['id']
                    ? self::url('edit_review', $product['id'], self::toSlug($product['name']), $review['id'])
                    : self::url('add_review', $product['id'], self::toSlug($product['name']))
                ;

                $reviewFormFieldsHtml = self::reviewFormFieldsHtml($review, $reviewFields);

                $buttonText = $review['id']
                    ? $lang->community_reviews_update_review
                    : $lang->community_reviews_add_review
                ;

                eval('$content = "' . self::tpl('add_review') . '";');

            } elseif ($commentId = $mybb->get_input('delete_comment', MyBB::INPUT_INT)) {
                if ($comment = self::getComment($commentId)) {
                    $content = self::confirmAction([
                        'permissions' => self::canDeleteUserContent($comment['user_id']),
                        'message' => $lang->community_reviews_confirm_delete_comment,
                        'action_callback' => [self, 'deleteComment'],
                        'action_parameters' => [$comment['id']],
                        'redirect_url' => self::url('product', $product['id'], self::toSlug($product['name'])),
                        'redirect_message' => $lang->community_reviews_comment_deleted,
                    ]);
                } else {
                    self::redirect(self::url('index'));
                }
            } elseif ($mybb->get_input('add_comment') || $mybb->get_input('edit_comment')) {
                if ($mybb->get_input('edit_comment', MyBB::INPUT_INT) && $comment = self::getComment($mybb->get_input('edit_comment', MyBB::INPUT_INT))) {
                    if (!self::canEditUserContent($comment['user_id'])) {
                        error_no_permission();
                    } else {
                        $title = $lang->community_reviews_update_comment . '  - ' . $lang->community_reviews_location;
                        $sectionTitle = $lang->sprintf($lang->community_reviews_edit_comment_in, htmlspecialchars_uni($product['name']));
                        add_breadcrumb($lang->community_reviews_update_comment, self::url('edit_comment', $product['id'], self::toSlug($product['name']), (int)$comment['id']));
                    }
                } else {
                    $comment = false;

                    if (!self::isUser()) {
                        error_no_permission();
                    } else {
                        $title = $lang->community_reviews_add_comment . '  - ' . $lang->community_reviews_location;
                        $sectionTitle = $lang->sprintf($lang->community_reviews_add_comment_in, htmlspecialchars_uni($product['name']));
                        add_breadcrumb($lang->community_reviews_add_comment, self::url('add_comment', $product['id'], self::toSlug($product['name'])));
                    }
                }

                $errors = '';

                if ($mybb->request_method == 'post') {
                    verify_post_check($mybb->get_input('my_post_key'));
                    self::validateProductComment($comment, $errors);
                    self::processProductComment($product, $comment, $errors, false);
                }

                if (isset($comment['comment'])) {
                    $commentValue = htmlspecialchars_uni($comment['comment']);
                } else {
                    $commentValue = '';
                }

                $formActionUrl = $comment['id']
                    ? self::url('edit_comment', $product['id'], self::toSlug($product['name']), (int)$comment['id'])
                    : self::url('add_comment', $product['id'], self::toSlug($product['name']))
                ;

                $buttonText = $comment['id']
                    ? $lang->community_reviews_update_comment
                    : $lang->community_reviews_add_comment
                ;

                eval('$content = "' . self::tpl('add_comment') . '";');

            } else {
                $productTitle = htmlspecialchars_uni($product['name']);

                if (self::isUser()) {
                    $reportLink = '<a href="javascript:communityReviews.reportProduct(' . $product['id'] . ')" title="' . $lang->community_reviews_report . '" class="community-reviews__controls__report"></a>';
                } else {
                    $reportLink = '';
                }

                if (self::canEditUserContent($product['user_id'])) {
                    $editLink = '<a href="' . self::url('edit_product', $category['id'], self::toSlug($category['name']), (int)$product['id']) . '" title="' . $lang->community_reviews_edit . '" class="community-reviews__controls__edit"></a>';
                } else {
                    $editLink = '';
                }

                if (self::canDeleteUserContent($product['user_id'])) {
                    $deleteLink = '<a href="' . self::url('delete_product', $category['id'], self::toSlug($category['name']), (int)$product['id']) . '" title="' . $lang->community_reviews_delete . '" class="community-reviews__controls__delete"></a>';
                } else {
                    $deleteLink = '';
                }

                if (self::isMod()) {
                    $mergeLink = '<a href="' . self::url('merge_product', $category['id'], self::toSlug($category['name']), (int)$product['id']) . '" title="' . $lang->community_reviews_merge . '" class="community-reviews__controls__merge"></a>';
                } else {
                    $mergeLink = '';
                }

                // links
                $addReviewUrl = self::url('add_review', $product['id'], self::toSlug($product['name']));
                $addCommentUrl = self::url('add_comment', $product['id'], self::toSlug($product['name']));

                if ($product['cached_rating']) {
                    $starRating = self::buildRating($product['cached_rating']);
                } else {
                    $starRating = '';
                }

                $url = self::url('product', $product['id'], self::toSlug($product['name']));

                if (!empty($mybb->input['reviews_only'])) {
                    $whereClauses = 'review_id IS NOT NULL';
                    $reviewsOnlyParameters = 'checked="checked"';
                } else {
                    $whereClauses = '';
                    $reviewsOnlyParameters = '';
                }

                if ($mybb->seo_support) {
                    $reviewsOnlyFields = '';
                } else {
                    $reviewsOnlyFields = '<input type="hidden" name="action" value="reviews" /><input type="hidden" name="product" value="' . $product['id'] .'" />';
                }

                $itemsNum = self::countProductFeedEntries($product['id'], $whereClauses);

                $listManager = new CommunityReviews\ListManager([
                    'mybb'          => $mybb,
                    'baseurl'       => $url . (!empty($mybb->input['reviews_only']) ? '&reviews_only=1' : null),
                    'order_columns' => ['date'],
                    'order_dir'     => self::displayOrder(),
                    'order_extend'  => '`id` ' . self::displayOrder(),
                    'items_num'     => $itemsNum,
                    'per_page'      => self::settings('per_page'),
                ], true);

                if ($mybb->get_input('review', MyBB::INPUT_INT)) {
                    $data = self::getEntryLocation('review', $mybb->get_input('review', MyBB::INPUT_INT), self::displayOrder(), $whereClauses);
                    if ($data && $data['product_id'] == $product['id']) {
                        $listManager->page = $data['pageNumber'];
                    }
                } elseif ($mybb->get_input('comment', MyBB::INPUT_INT)) {
                    $data = self::getEntryLocation('comment', $mybb->get_input('comment', MyBB::INPUT_INT), self::displayOrder(), $whereClauses);
                    if ($data && $data['product_id'] == $product['id']) {
                        $listManager->page = $data['pageNumber'];
                    }
                }

                $listManager->detect();
                $multipage = $listManager->pagination();

                $feedQuery = self::getProductFeedEntries($product['id'], $whereClauses, $listManager->queryOptions());

                $feedEntries = [];
                $reviewIds = [];
                $commentIds = [];

                while ($row = $db->fetch_array($feedQuery)) {
                    $feedEntries[] = $row;

                    if ($row['review_id']) {
                        $reviewIds[] = $row['review_id'];
                    }
                    if ($row['comment_id']) {
                        $commentIds[] = $row['comment_id'];
                    }
                }

                $reviewsArray = self::getReviewDataMultiple($reviewIds);
                $productPhotos = self::getReviewsPhotos($reviewIds);
                $reviewMerchants = self::getReviewsMerchants($reviewIds);
                $reviews = self::buildProductReviews($product, $reviewsArray, $categoryReviewFields, $productPhotos, $reviewMerchants);

                $commentsArray = self::getCommentDataMultiple($commentIds);
                $comments = self::buildProductComments($product, $commentsArray);

                $feed = self::buildSeparatedProductFeed($feedEntries, $reviews, $comments);

                eval('$content = "' . self::tpl('product') . '";');

                self::bumpProductViews($product['id']);
            }
        } else {
            self::redirect(self::url('index'));
        }

        return [
            'title' => $title,
            'content' => $content,
        ];
    }

    public static function frontendSectionSearch()
    {
        global $mybb, $db, $lang;

        if ($mybb->get_input('keywords') && strlen($mybb->get_input('keywords')) <= 100) {
            $title = $lang->community_reviews_search_results;
            $sectionTitle = $lang->sprintf($lang->community_reviews_search_results_for, htmlspecialchars_uni($mybb->get_input('keywords')));

            add_breadcrumb($title, self::url('search'));

            $itemsNum = self::countMatchProductNameAgainst($mybb->get_input('keywords'));

            $listManager = new CommunityReviews\ListManager([
                'mybb'          => $mybb,
                'baseurl'       => self::url('search_keywords', urlencode($mybb->get_input('keywords'))),
                'items_num'     => $itemsNum,
                'per_page'      => self::settings('per_page'),
            ]);

            // pagination
            $multipage = $listManager->pagination();

            $products = self::matchProductNameAgainst($mybb->get_input('keywords'), $listManager->sql());

            if ($products) {
                $results = '';

                foreach ($products as $product) {
                    $results .= self::buildProductCard($product);
                }
            } else {
                $message = $lang->community_reviews_search_no_results;
                eval('$results .= "' . self::tpl('message') . '";');
            }

            eval('$content = "' . self::tpl('search_results') . '";');

            return [
                'title' => $title,
                'content' => $content,
            ];
        } else {
            self::redirect(self::url('index'));
        }
    }


    public static function frontendSectionMerchantReviews()
    {
        global $mybb, $db, $lang;

        if ($user = get_user($mybb->get_input('merchant', MyBB::INPUT_INT))) {
            if (is_member(self::settings('merchant_group'), $user)) {

                add_breadcrumb($lang->sprintf($lang->community_reviews_merchant_reviews, htmlspecialchars_uni($user['username'])), self::url('merchant_reviews', $user['uid']));

                $title = $lang->sprintf($lang->community_reviews_merchant_reviews, htmlspecialchars_uni($user['username']));
                $sectionTitle = $lang->sprintf($lang->community_reviews_merchant_reviews, htmlspecialchars_uni($user['username']));

                $itemsNum = self::countMerchantReviews($user['id']);

                $listManager = new CommunityReviews\ListManager([
                    'mybb'          => $mybb,
                    'baseurl'       => self::url('merchant_reviews', (int)$user['uid']),
                    'order_columns' => ['date'],
                    'order_dir'     => 'DESC',
                    'items_num'     => $itemsNum,
                    'per_page'      => self::settings('per_page'),
                ]);

                // pagination
                $multipage = $listManager->pagination();

                $query = self::getReviewsDataWithReviewCountAndPhotosByMerchant($user['uid'], $listManager->sql());

                $reviews = '';

                while ($row = $db->fetch_array($query)) {
                    $reviews .= self::buildProductCard($row, true);
                }

                eval('$content = "' . self::tpl('merchant_reviews') . '";');

                return [
                    'title' => $title,
                    'content' => $content,
                    'mainTemplate' => 'profile_page',
                ];

            } else {
                self::redirect(self::url('index'));
            }
        } else {
            self::redirect(self::url('index'));
        }
    }
}
