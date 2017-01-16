<?php

$plugins->add_hook('global_start', ['CommunityReviews', 'global_start']);
$plugins->add_hook('global_end', ['CommunityReviews', 'global_end']);
$plugins->add_hook('xmlhttp', ['CommunityReviews', 'xmlhttp']);
$plugins->add_hook('index_end', ['CommunityReviews', 'index_end']);
$plugins->add_hook('member_profile_end', ['CommunityReviews', 'member_profile_end']);
$plugins->add_hook('report_type', ['CommunityReviews', 'report_type']);
$plugins->add_hook('modcp_start', ['CommunityReviews', 'modcp_start']);
$plugins->add_hook('modcp_reports_intermediate', ['CommunityReviews', 'modcp_reports_intermediate']);
$plugins->add_hook('modcp_allreports_start', ['CommunityReviews', 'modcp_allreports_start']);
$plugins->add_hook('modcp_reports_report', ['CommunityReviews', 'modcp_reports_report']);
$plugins->add_hook('modcp_allreports_report', ['CommunityReviews', 'modcp_reports_report']);
$plugins->add_hook('fetch_wol_activity_end', ['CommunityReviews', 'activity']);
$plugins->add_hook('build_friendly_wol_location_end', ['CommunityReviews', 'activity_translate']);

$plugins->add_hook('myalerts_load_lang', ['CommunityReviews', 'myalerts_load_lang']);

trait CommunityReviewsHooksFrontend
{
    // core hooks
    public static function global_start()
    {
        global $mybb, $lang;

        $mybb->binary_fields['community_reviews'] = [
            'ipaddress' => true,
        ];
        $mybb->binary_fields['community_reviews_comments'] = [
            'ipaddress' => true,
        ];

        if (defined('THIS_SCRIPT') && THIS_SCRIPT == 'index.php') {
            if ($mybb->get_input('action') == '') {
                self::loadTemplates([
                    'widget',
                    'widget_entry',
                    'widget_entry_photo',
                ], 'community_reviews_');
            }
            if ($mybb->get_input('action') == 'reviews') {
                self::loadTemplates([
                    'multipage',
                    'multipage_page',
                    'multipage_page_current',
                    'multipage_prevpage',
                    'multipage_nextpage',
                    'multipage_start',
                    'multipage_end',
                    'multipage_jump_page',
                ]);
                self::loadTemplates([
                    'add_comment',
                    'add_product',
                    'add_review_data',
                    'add_review_data2',
                    'add_review_field',
                    'add_review',
                    'category_listing_category',
                    'category_listing',
                    'category',
                    'comment',
                    'confirm',
                    'form_rating_star',
                    'index',
                    'merchant_reviews',
                    'merge_product',
                    'message',
                    'page',
                    'product_card_label',
                    'product_card_photo',
                    'product_card',
                    'product',
                    'profile_page',
                    'rating_star',
                    'rating_stars_empty',
                    'rating_stars',
                    'review_comment',
                    'review_field',
                    'review_photo',
                    'review_property',
                    'review',
                    'search_form',
                    'search_results',
                    'sorting_options',
                    'statistics',
                ], 'community_reviews_');
            }
        } elseif (defined('THIS_SCRIPT') && THIS_SCRIPT == 'member.php') {
            if ($mybb->get_input('action') == '') {
                self::loadTemplates([
                    'merchant_widget',
                    'user_widget',
                ], 'community_reviews_');
            }
        }

        // register MyAlerts formatters
        if (CommunityReviewsMyalertsIntegrable()) {
            if ($mybb->user['uid']) {
                $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

                $class = 'MybbStuff_MyAlerts_Formatter_CommunityReviewsMerchantTagFormatter';
                $formatterManager->registerFormatter(new $class($mybb, $lang, 'community_reviews_merchant_tag'));

                $class = 'MybbStuff_MyAlerts_Formatter_CommunityReviewsSameProductReviewFormatter';
                $formatterManager->registerFormatter(new $class($mybb, $lang, 'community_reviews_same_product_review'));

                $class = 'MybbStuff_MyAlerts_Formatter_CommunityReviewsSameProductCommentFormatter';
                $formatterManager->registerFormatter(new $class($mybb, $lang, 'community_reviews_same_product_comment'));
            }
        }

    }

    public static function global_end()
    {
        global $mybb;

        if ($mybb->get_input('action') == 'reviews') {
            return self::showReviewsPage();
        }
    }

    public static function xmlhttp()
    {
        global $mybb, $db, $lang, $charset;
    
        if ($mybb->get_input('action') == 'community_reviews_product_comments') {
            $lang->load('community_reviews');

            $product = self::getProduct($mybb->get_input('product', MyBB::INPUT_INT));

            if (!$product) {
                exit;
            }

            if ($requestedPage = abs($mybb->get_input('page', MyBB::INPUT_INT))) {
                if ($requestedPage > 1) {
                    $limitStart = ($requestedPage - 1) * (int)self::settings('reviews_per_page');
                }
            } else {
                $limitStart = false;
            }

            $limit = (int)self::settings('reviews_per_page');

            $commentsArray = self::getCommentDataInProduct(
                $product['id'],
                false,
                'ORDER BY c.date ' . self::displayOrder() . ', c.id ' . self::displayOrder() . ' LIMIT ' . ($limitStart ? $limitStart . ', ' : null) . $limit)
            ;
            $comments = self::buildProductComments($product, $commentsArray);

            $commentList = self::buildProductCommentList($comments);

            header('Content-type: application/json; charset=' . $charset);

            echo json_encode([
                'html' => $commentList,
            ]);
            exit;
        } elseif ($mybb->get_input('action') == 'community_reviews_recent') {
            $lang->load('community_reviews');

            if ($requestedPage = abs($mybb->get_input('page', MyBB::INPUT_INT))) {
                if ($requestedPage > 1) {
                    $limitStart = ($requestedPage - 1) * (int)self::settings('widget_items_limit');
                }
            } else {
                $limitStart = false;

            }

            $limit = (int)self::settings('widget_items_limit') + 1;

            $query = self::getReviewsWithPhotos('ORDER BY r.date DESC LIMIT ' . ($limitStart ? $limitStart . ', ' : null) . $limit);

            $entries = self::buildWidgetEntries($query, (int)self::settings('widget_items_limit'));

            header('Content-type: application/json; charset=' . $charset);

            echo json_encode([
                'html' => $entries,
                'next' => $db->num_rows($query) >= $limit,
            ]);
            exit;
        } elseif ($mybb->get_input('action') == 'community_reviews_get_merchants') {
            $query = ltrim($mybb->get_input('query'));

            if(my_strlen($query) < 2) {
                exit;
            }

            if($mybb->get_input('getone', MyBB::INPUT_INT) == 1) {
                $limit = 1;
            } else {
                $limit = 15;
            }

            $query_options = [
                'order_by' => 'username',
                'order_dir' => 'asc',
                'limit_start' => 0,
                'limit' => $limit
            ];

            $groupId = self::settings('merchant_group');

            $query = $db->simple_select(
                'users',
                'uid, username',
                "(
                    usergroup=" . (int)$groupId . " OR
                    additionalgroups=" . (int)$groupId . " OR
                    additionalgroups LIKE '" . (int)$groupId . ",%' OR
                    additionalgroups LIKE '%," . (int)$groupId . "' OR
                    additionalgroups LIKE '%," . (int)$groupId . ",%'
                ) AND username LIKE '" . $db->escape_string_like($mybb->input['query']) . "%'",
                $query_options
            );

            if($limit == 1) {
                $user = $db->fetch_array($query);
                $data = [
                    'id' => $user['username'],
                    'text' => $user['username']
                ];
            } else {
                $data = [];
                while($user = $db->fetch_array($query)) {
                    $data[] = [
                        'id' => $user['username'],
                        'text' => $user['username']
                    ];
                }
            }

            header('Content-type: application/json; charset=' . $charset);

            echo json_encode($data);
            exit;
        }
    }

    public static function index_end()
    {
        global $mybb, $db, $lang, $community_reviews_widget, $theme;

        $lang->load('community_reviews');

        $limit = (int)self::settings('widget_items_limit') + 1;

        $query = self::getReviewsWithPhotos('ORDER BY r.date DESC LIMIT ' . $limit);

        $entries = self::buildWidgetEntries($query, (int)self::settings('widget_items_limit'));

        $widgetPageMax = $db->num_rows($query) < $limit
            ? '1'
            : 'false'
        ;

        eval('$community_reviews_widget = "' . self::tpl('widget') . '";');
    }

    public static function member_profile_end()
    {
        global $mybb, $db, $lang, $memprofile, $community_reviews_user_widget, $community_reviews_merchant_widget, $theme;

        $lang->load('community_reviews');

        // user widget
        $limit = (int)self::settings('widget_items_limit');

        $data = self::getReviewsDataWithReviewCountAndPhotosByUser($memprofile['uid'], 'ORDER BY r.date DESC LIMIT ' . $limit);

        if ($db->num_rows($data)) {
            $entries = self::buildReviewListing($data);
            eval('$community_reviews_user_widget = "' . self::tpl('user_widget') . '";');
        } else {
            $community_reviews_user_widget = '';
        }

        // merchant widget
        if (is_member(self::settings('merchant_group'), $memprofile)) {
            $url = self::url('merchant_reviews', $memprofile['uid']);

            $limit = (int)self::settings('widget_items_limit');

            $data = self::getReviewsDataWithReviewCountAndPhotosByMerchant($memprofile['uid'], 'ORDER BY r.date DESC LIMIT ' . $limit);

            if ($db->num_rows($data)) {
                $entries = self::buildReviewListing($data);
                eval('$community_reviews_merchant_widget = "' . self::tpl('merchant_widget') . '";');
            } else {
                $community_reviews_merchant_widget = '';
            }
        } else {
            $community_reviews_merchant_widget = '';
        }
    }

    public static function report_type()
    {
        global $mybb, $report_type, $report_type_db, $error, $verified, $lang, $id, $id2, $id3;

        if ($report_type == 'community_reviews_product') {
            $item = self::getProduct($mybb->get_input('pid', MyBB::INPUT_INT));

            if (!$item) {
                $error = $lang->error_invalid_report;
            } else {
                $id = $item['id'];
                $id2 = 0;
                $id3 = 0;
                $verified = true;
                $report_type_db = "type = 'community_reviews_product'";
            }
        } elseif ($report_type == 'community_reviews_review') {
            $item = self::getReview($mybb->get_input('pid', MyBB::INPUT_INT));

            if (!$item) {
                $error = $lang->error_invalid_report;
            } else {
                $id = $item['id'];
                $id2 = 0;
                $id3 = 0;
                $verified = true;
                $report_type_db = "type = 'community_reviews_review'";
            }
        } elseif ($report_type == 'community_reviews_comment') {
            $item = self::getComment($mybb->get_input('pid', MyBB::INPUT_INT));

            if (!$item) {
                $error = $lang->error_invalid_report;
            } else {
                $id = $item['id'];
                $id2 = 0;
                $id3 = 0;
                $verified = true;
                $report_type_db = "type = 'community_reviews_comment'";
            }
        }
    }

    public static function modcp_start()
    {
        global $lang;
        $lang->load('community_reviews');
    }

    public static function modcp_reports_intermediate()
    {
        global $reportcache, $communityReviewsCache;

        foreach ($reportcache as $report) {
            if ($report['type'] == 'community_reviews_review') {
                $reviewsToFetch[] = $report['id'];
            } elseif ($report['type'] == 'community_reviews_comment') {
                $commentsToFetch[] = $report['id'];
            }
        }

        if ($reviewsToFetch) {
            $communityReviewsCache['reviews'] = self::queryResultToArray(
                self::getReviewsById($reviewsToFetch),
                'id'
            );
        }

        if ($commentsToFetch) {
            $communityReviewsCache['comments'] = self::queryResultToArray(
                self::getCommentsById($commentsToFetch),
                'id'
            );
        }
    }

    public static function modcp_allreports_start()
    {
        global $db, $wflist_reports, $start, $perpage, $communityReviewsCache;

        $communityReviewsCache = [
            'reviews' => [],
            'comments' => [],
        ];

        $reviewsToFetch = [];
        $commentsToFetch = [];

        $where = ($wflist_reports ? str_replace('WHERE', '', $wflist_reports) . ' AND ' : '') . "type IN ('community_reviews_review', 'community_reviews_comment')";

        $reports = $db->simple_select('reportedcontent', 'id,type', $where, [
            'limit_start' => $start,
            'limit' => $perpage,
            'order_by' => 'dateline',
            'order_dir' => 'DESC',
        ]);

        while ($report = $db->fetch_array($reports)) {
            if ($report['type'] == 'community_reviews_review') {
                $reviewsToFetch[] = $report['id'];
            } elseif ($report['type'] == 'community_reviews_comment') {
                $commentsToFetch[] = $report['id'];
            }
        }

        if ($reviewsToFetch) {
            $communityReviewsCache['reviews'] = self::queryResultToArray(
                self::getReviewsById($reviewsToFetch),
                'id'
            );
        }

        if ($commentsToFetch) {
            $communityReviewsCache['comments'] = self::queryResultToArray(
                self::getCommentsById($commentsToFetch),
                'id'
            );
        }
    }

    public static function modcp_reports_report()
    {
        global $mybb, $lang, $report, $report_data, $communityReviewsCache;

        self::$forceUrlFormat = 'raw';

        if ($report['type'] == 'community_reviews_product') {
            $url = self::url('product', $report['id']);
            $report_data['content'] = $lang->sprintf($lang->community_reviews_pointer_product, $url, $report['id']);
        } elseif ($report['type'] == 'community_reviews_review') {
            $url = self::url('review', $communityReviewsCache['reviews'][ $report['id'] ]['product_id'], '', $report['id']);
            $report_data['content'] = $lang->sprintf($lang->community_reviews_pointer_review, $url, $report['id']);
        } elseif ($report['type'] == 'community_reviews_comment') {
            $url = self::url('comment', $communityReviewsCache['comments'][ $report['id'] ]['product_id'], '', $report['id']);
            $report_data['content'] = $lang->sprintf($lang->community_reviews_pointer_comment, $url, $report['id']);
        }

        self::$forceUrlFormat = false;
    }

    public static function activity(&$user_activity)
    {
        $location = parse_url($user_activity['location']);
        $filename = basename($location['path']);

        parse_str(html_entity_decode($location['query']), $parameters);

        if ($filename == 'index.php' && $parameters['action'] == 'reviews') {
            $user_activity['activity'] = 'community_reviews_page';
        }
    }

    public static function activity_translate(&$data)
    {
        global $lang;

        $lang->load('community_reviews');

        if ($data['user_activity']['activity'] == 'community_reviews_page') {
            $data['location_name'] = sprintf($lang->community_reviews_activity, self::url('index'));
        }
    }

    // 3rd party hooks
    static function myalerts_load_lang()
    {
        global $lang;
        $lang->load('community_reviews');
    }
}
