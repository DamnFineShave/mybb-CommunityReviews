<?php

trait CommunityReviewsCore
{
    public static $forceUrlFormat = false;
    public static $urlPatterns;

    // permissions
    public static function isUser()
    {
        global $mybb;
        return $mybb->user['uid'] != 0;
    }

    public static function isMod()
    {
        global $mybb;

        $array = self::settingsGetCsv('groups_mod');

        return (
            ($array[0] == -1 || is_member($array)) ||
            (self::settings('supermods') && $mybb->usergroup['issupermod'])
        );
    }

    public static function isModOrAuthor($authorId)
    {
        global $mybb;
        return $mybb->user['uid'] === $authorId || self::isMod();
    }

    // data processing
    public static function categoryArray($escapeHtml = false)
    {
        global $db;

        $array = [];

        $categories = self::getCategories();

        while ($row = $db->fetch_array($categories)) {
            $array[ $row['id'] ] = [
                'id' => $row['id'],
                'name' => $escapeHtml
                    ? htmlspecialchars_uni($row['name'])
                    : $row['name'],
            ];
        }

        return $array;
    }

    public static function fieldsInCategoryArray($categoryId)
    {
        global $db;

        $array = [];

        $fields = self::getFieldsInCategory($categoryId);

        while ($row = $db->fetch_array($fields)) {
            $array[ $row['id'] ] = $row;
        }

        return $array;
    }

    // core
    public static function getTotalRating($ratings)
    {
        if (count($ratings) == 0) {
            return 0;
        } else {
            return round(array_sum($ratings) / count($ratings), 2);
        }
    }

    public static function parseComment($message, $username)
    {
        require_once MYBB_ROOT . 'inc/class_parser.php';

        $parser = new postParser;
        $options = [
            'allow_mycode'    => 1,
            'allow_smilies'   => 1,
            'allow_imgcode'   => 0,
            'filter_badwords' => 1,
            'me_username'     => $username,
        ];

        return $parser->parse_message($message, $options);
    }

    public static function url($resource)
    {
        global $mybb;

        if (!self::$urlPatterns) {
            if ($mybb->seo_support && self::$forceUrlFormat != 'raw') {
                self::$urlPatterns = [
                    'index' => '/reviews',
                    'category' => '/reviews-category-%d-%s',
                    'add_product' => '/reviews-category-%d-%s?add=1',
                    'edit_product' => '/reviews-category-%d-%s?edit=%d',
                    'delete_product' => '/reviews-category-%d-%s?delete=%d',
                    'merge_product' => '/reviews-category-%d-%s?merge=%d',
                    'product' => '/reviews-product-%d-%s',
                    'add_review' => '/reviews-product-%d-%s?add=1',
                    'edit_review' => '/reviews-product-%d-%s?edit=%d',
                    'delete_review' => '/reviews-product-%d-%s?delete=%d',
                    'add_comment' => '/reviews-product-%d-%s?add_comment=1',
                    'edit_comment' => '/reviews-product-%d-%s?edit_comment=%d',
                    'delete_comment' => '/reviews-product-%d-%s?delete_comment=%d',
                    'review' => '/reviews-product-%d-%s?review=%3$d#review%3$d',
                    'comment' => '/reviews-product-%d-%s?comment=%3$d#comment%3$d',
                    'merchant_reviews' => '/reviews-merchant-%d',
                ];
            } else {
                self::$urlPatterns = [
                    'index' => '/index.php?action=reviews',
                    'category' => '/index.php?action=reviews&category=%d',
                    'add_product' => '/index.php?action=reviews&category=%d&add=1',
                    'edit_product' => '/index.php?action=reviews&category=%d&edit=%d',
                    'delete_product' => '/index.php?action=reviews&category=%d&delete=%d',
                    'merge_product' => '/index.php?action=reviews&category=%d&merge=%d',
                    'product' => '/index.php?action=reviews&product=%d',
                    'add_review' => '/index.php?action=reviews&product=%d&add=1',
                    'edit_review' => '/index.php?action=reviews&product=%d&edit=%d',
                    'delete_review' => '/index.php?action=reviews&product=%d&delete=%d',
                    'add_comment' => '/index.php?action=reviews&product=%d&add_comment=1',
                    'edit_comment' => '/index.php?action=reviews&product=%d&edit_comment=%d',
                    'delete_review' => '/index.php?action=reviews&product=%d&delete_comment=%d',
                    'review' => '/index.php?action=reviews&product=%d&review=%3$d#review%3$d',
                    'comment' => '/index.php?action=reviews&product=%d&comment=%3$d#comment%3$d',
                    'merchant_reviews' => '/index.php?action=merchant_reviews&user=%d',
                ];
            }
        }

        if (isset(self::$urlPatterns[$resource])) {
            $url = call_user_func_array('sprintf', array_merge(
                [self::$urlPatterns[$resource]],
                array_slice(func_get_args(), 1)
            ));

            $url = $mybb->settings['bburl'] . $url;

            return $url;
        } else {
            return false;
        }
    }

    public static function toSlug($string, $delimiter = '-')
    {
        setlocale(LC_ALL, 'en_US.UTF8');

        $slug = $string;

        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
    	$slug = preg_replace('/[^a-zA-Z0-9\/_|+ -]/', '', $slug);
    	$slug = strtolower(trim($slug, '-'));
    	$slug = preg_replace("/[\/_|+ -]+/", $delimiter, $slug);

    	return $slug;
    }

    public static function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    public static function getPhotoUrls($url)
    {
        $data = parse_url($url);

        if (!isset($data['host'], $data['path']) || $data['host'] != 'i.imgur.com') {
            return false;
        } else {
            $photoUrl = 'https://' . $data['host'] . $data['path'];
            $thumbnailUrl = substr($photoUrl, 0, strrpos($photoUrl, '.')) . 'm.' . substr($photoUrl, strrpos($photoUrl, '.') + 1);

            return [
                'photo_url' => $photoUrl,
                'thumbnail_url' => $thumbnailUrl,
            ];
        }
    }

    public static function displayOrder()
    {
        return self::settings('display_order') == 'desc'
            ? 'DESC'
            : 'ASC'
        ;
    }

    // library
    public static function settings($name)
    {
        global $mybb;
        return $mybb->settings['community_reviews_' . $name];
    }

    public static function settingsGetCsv($name)
    {
        return array_filter(explode(',', self::settings($name)));
    }

    public static function getUsersByUsername($values, $fields = '*')
    {
        global $db;

        if (!$values) {
            return false;
        }

        foreach ($values as &$value) {
            $value = "'" . $db->escape_string($value) . "'";
        }

        return $db->simple_select('users', $fields, 'username IN(' . implode(',', $values) . ')');
    }

    public static function getResourceUrl($url)
    {
        global $mybb;

        if (mb_strpos($url, 'https:') === 0 || mb_strpos($url, 'http:') === 0) {
            // DVZ Secure Content integration
            if (class_exists('dvz_sc')) {
                if (
                    dvz_sc::settings('proxy_images') == 'all' ||
                    (dvz_sc::settings('proxy_images') == 'insecure' && !dvz_sc::is_secure_url($url))
                ) {
                    $url = dvz_sc::proxy_url($url); 
                }
            }
        } else {
            $url = $mybb->settings['uploadspath'] . '/community_reviews/' . $url;
        }

        return $url;
    }

    public static function loadTemplates($templates, $prefix = false)
    {
        global $templatelist;

        if (!empty($templatelist)) {
            $templatelist .= ',';
        }

        if ($prefix) {
            $templates = preg_filter('/^/', $prefix, $templates);
        }

        $templatelist .= implode(',', $templates);
    }

    public static function tpl($name)
    {
        global $templates;

        if (self::DEVELOPMENT_MODE) {
            return str_replace(
                "\\'",
                "'",
                addslashes(
                    file_get_contents(MYBB_ROOT . 'inc/plugins/community_reviews/templates/' . $name . '.tpl')
                )
            );
        } else {
            return $templates->get('communityreviews_' . $name);
        }
    }
}
