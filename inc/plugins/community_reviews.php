<?php

// plugin logic
require_once MYBB_ROOT . 'inc/plugins/community_reviews/core.php';
require_once MYBB_ROOT . 'inc/plugins/community_reviews/data.php';
require_once MYBB_ROOT . 'inc/plugins/community_reviews/snippets.php';
require_once MYBB_ROOT . 'inc/plugins/community_reviews/hooks_frontend.php';
require_once MYBB_ROOT . 'inc/plugins/community_reviews/logic_frontend.php';
require_once MYBB_ROOT . 'inc/plugins/community_reviews/sections_frontend.php';
require_once MYBB_ROOT . 'inc/plugins/community_reviews/hooks_acp.php';
require_once MYBB_ROOT . 'inc/plugins/community_reviews/list_manager.php';
require_once MYBB_ROOT . 'inc/plugins/community_reviews/alerts.php';

class CommunityReviews
{
    // bypass MyBB template system and fetch templates from files directly
    const DEVELOPMENT_MODE = false;

    public static $descriptionAppendix = '';

    // core functions
    use CommunityReviewsCore;

    // data handling
    use CommunityReviewsData;

    // hooks
    use CommunityReviewsHooksFrontend;
    use CommunityReviewsHooksACP;

    // output snippets
    use CommunityReviewsSnippets;

    // logic
    use CommunityReviewsLogicFrontend;

    // sections
    use CommunityReviewsSectionsFrontend;

    // alerts
    use CommunityReviewsAlerts;
}

// dependencies setup
if (!defined('PLUGINLIBRARY')) {
    define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');
}

// MyBB plugin functions
function community_reviews_info()
{
    return [
        'name'          => 'Community Reviews',
        'description'   => 'Adds a product review section.' . CommunityReviews::$descriptionAppendix,
        'website'       => 'https://devilshakerz.com/',
        'author'        => 'Tomasz \'Devilshakerz\' Mlynski',
        'authorsite'    => 'https://devilshakerz.com/',
        'version'       => '1.0',
        'codename'      => '',
        'compatibility' => '18*',
    ];
}

function community_reviews_install()
{
    global $mybb, $db, $lang, $PL;

    if (!file_exists(PLUGINLIBRARY)) {
        $lang->load('community_reviews');
        flash_message($lang->community_reviews_pluginlibrary_missing, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    if (!$PL) {
        require_once PLUGINLIBRARY;
    }

    $mybb->binary_fields['community_reviews'] = [
        'ipaddress' => true,
    ];
    $mybb->binary_fields['community_reviews_comments'] = [
        'ipaddress' => true,
    ];

    // database
    $db->write_query("
        CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "community_reviews_categories` (
            `id` int(11) NOT NULL auto_increment,
            `name` varchar(255) NOT NULL,
            `order` int(11) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB " . $db->build_create_table_collation() . "
    ");

    $db->write_query("
        CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "community_reviews_fields` (
            `id` int(11) NOT NULL auto_increment,
            `category_id` int(11) NOT NULL,
            `name` TEXT NOT NULL,
            `order` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`category_id`)
                REFERENCES " . TABLE_PREFIX . "community_reviews_categories(`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB " . $db->build_create_table_collation() . "
    ");

    $db->write_query("
        CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "community_reviews_products` (
            `id` int(11) NOT NULL auto_increment,
            `category_id` int(11) NOT NULL,
            `name` varchar(255) NOT NULL,
            `date` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `views` int(11) NOT NULL,
            `cached_rating` decimal(3,2) NULL,
            FULLTEXT `name` (`name`),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`category_id`)
                REFERENCES " . TABLE_PREFIX . "community_reviews_categories(`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB " . $db->build_create_table_collation() . "
    ");

    $db->write_query("
        CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "community_reviews` (
            `id` int(11) NOT NULL auto_increment,
            `product_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `date` int(11) NOT NULL,
            `ipaddress` varbinary(16) NOT NULL,
            `price` varchar(30) NOT NULL,
            `url` varchar(200) NOT NULL,
            `comment` text NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`product_id`)
                REFERENCES " . TABLE_PREFIX . "community_reviews_products(`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB " . $db->build_create_table_collation() . "
    ");

    $db->write_query("
        CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "community_reviews_photos` (
            `id` int(11) NOT NULL auto_increment,
            `review_id` int(11) NOT NULL,
            `url` text NOT NULL,
            `thumbnail_url` text NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`review_id`)
                REFERENCES " . TABLE_PREFIX . "community_reviews(`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB " . $db->build_create_table_collation() . "
    ");

    $db->write_query("
        CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "community_reviews_review_fields` (
            `id` int(11) NOT NULL auto_increment,
            `review_id` int(11) NOT NULL,
            `field_id` int(11) NOT NULL,
            `comment` text NOT NULL,
            `rating` int(2) NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`review_id`)
                REFERENCES " . TABLE_PREFIX . "community_reviews(`id`)
                ON DELETE CASCADE,
            FOREIGN KEY (`field_id`)
                REFERENCES " . TABLE_PREFIX . "community_reviews_fields(`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB " . $db->build_create_table_collation() . "
    ");

    $db->write_query("
        CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "community_reviews_merchants` (
            `review_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            PRIMARY KEY (`review_id`, `user_id`),
            FOREIGN KEY (`review_id`)
                REFERENCES " . TABLE_PREFIX . "community_reviews(`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB " . $db->build_create_table_collation() . "
    ");

    $db->write_query("
        CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "community_reviews_comments` (
            `id` int(11) NOT NULL auto_increment,
            `product_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `date` int(11) NOT NULL,
            `ipaddress` varbinary(16) NOT NULL,
            `comment` text NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`product_id`)
                REFERENCES " . TABLE_PREFIX . "community_reviews_products(`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB " . $db->build_create_table_collation() . "
    ");

    $db->write_query("
        CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "community_reviews_product_feed` (
            `id` int(11) NOT NULL auto_increment,
            `date` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `review_id` int(11),
            `comment_id` int(11),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`product_id`)
                REFERENCES " . TABLE_PREFIX . "community_reviews_products(`id`)
                ON DELETE CASCADE,
            FOREIGN KEY (`review_id`)
                REFERENCES " . TABLE_PREFIX . "community_reviews(`id`)
                ON DELETE CASCADE,
            FOREIGN KEY (`comment_id`)
                REFERENCES " . TABLE_PREFIX . "community_reviews_comments(`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB " . $db->build_create_table_collation() . "
    ");

    // settings
    $PL->settings(
        'community_reviews',
        'Community Reviews',
        'Settings for Community Reviews.',
        [
            'display_order' => [
                'title'       => 'Display order',
                'description' => 'Determines how product entries should be displayed.',
                'optionscode' => 'select
asc=Oldest to newest
desc=Newest to oldest',
                'value'       => 'desc',
            ],
            'per_page' => [
                'title'       => 'Items per page',
                'description' => 'Items to be shown on single page.',
                'optionscode' => 'numeric',
                'value'       => '12',
            ],
            'widget_items_limit' => [
                'title'       => 'Widget list length',
                'description' => 'Number of items to be displayed in the Reviews widget.',
                'optionscode' => 'numeric',
                'value'       => '6',
            ],
            'recent_items_limit' => [
                'title'       => 'Recent items list length',
                'description' => 'Number of recently active items to be displayed on the Reviews index page.',
                'optionscode' => 'numeric',
                'value'       => '12',
            ],
            'product_name_length_limit' => [
                'title'       => 'Product name length limit',
                'description' => 'Limits the length of Product titles.',
                'optionscode' => 'numeric',
                'value'       => '100',
            ],
            'product_name_length_card' => [
                'title'       => 'Name length limit on product cards',
                'description' => 'Limits the length of titles displayed on Product cards.',
                'optionscode' => 'numeric',
                'value'       => '30',
            ],
            'seo_urls' => [
                'title'       => 'Search engine friendly URLs',
                'description' => 'Use friendly URLs when linking to Community Reviews pages. Requires plugin\'s rewrite rules present in the server configuration.',
                'optionscode' => 'yesno',
                'value'       => '0',
            ],
            'groups_edit_own' => [
                'title'       => 'Group permissions: Deleting own content',
                'description' => 'User groups that can delete own reviews.',
                'optionscode' => 'groupselect',
                'value'       => '',
            ],
            'groups_delete_own' => [
                'title'       => 'Group permissions: Editing own content',
                'description' => 'User groups that can edit own reviews.',
                'optionscode' => 'groupselect',
                'value'       => '',
            ],
            'groups_mod' => [
                'title'       => 'Group permissions: Moderation',
                'description' => 'User groups that can moderate reviews (edit and delete).',
                'optionscode' => 'groupselect',
                'value'       => '',
            ],
            'supermods' => [
                'title'       => 'Super moderators are Reviews moderators',
                'description' => 'Automatically allow forum super moderators to moderate reviews as well.',
                'optionscode' => 'yesno',
                'value'       => '1',
            ],
            'merchant_group' => [
                'title'       => 'Merchants group',
                'description' => 'The user group which product merchants can be selected from.',
                'optionscode' => 'groupselectsingle',
                'value'       => '',
            ],
            'require_review_photos' => [
                'title'       => 'Require review photos',
                'description' => 'At least one photo will be required upon adding a review.',
                'optionscode' => 'yesno',
                'value'       => '0',
            ],
            'max_review_photos' => [
                'title'       => 'Maximum photos per review',
                'description' => 'The maximum number of photos that can be attached to a review.',
                'optionscode' => 'numeric',
                'value'       => '4',
            ],
            'photos_auth_client_id' => [
                'title'       => 'Photo hosting OAuth Client ID',
                'description' => '',
                'optionscode' => 'text',
                'value'       => '',
            ],
            'comment_field_name' => [
                'title'       => 'Review comment field title',
                'description' => '',
                'optionscode' => 'text',
                'value'       => 'General thoughts',
            ],
        ]
    );
}

function community_reviews_uninstall()
{
    global $db, $lang, $PL;

    if (!file_exists(PLUGINLIBRARY)) {
        $lang->load('community_reviews');
        flash_message($lang->community_reviews_pluginlibrary_missing, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    if (!$PL) {
        require_once PLUGINLIBRARY;
    }

    community_reviews_deactivate();

    // database
    $db->write_query('SET foreign_key_checks = 0');

    $db->drop_table('community_reviews_categories');
    $db->drop_table('community_reviews_fields');
    $db->drop_table('community_reviews_products');
    $db->drop_table('community_reviews');
    $db->drop_table('community_reviews_photos');
    $db->drop_table('community_reviews_review_fields');
    $db->drop_table('community_reviews_merchants');
    $db->drop_table('community_reviews_comments');
    $db->drop_table('community_reviews_product_feed');

    $db->write_query('SET foreign_key_checks = 1');

    $db->delete_query('reportedcontent', "type IN ('community_reviews_product', 'community_reviews_review', 'community_reviews_comment')");

    // settings
    $PL->settings_delete('community_reviews', true);

    // 3rd party integration
    CommunityReviews::uninstallMyalertsIntegration();
}

function community_reviews_is_installed()
{
    global $db;
    return $db->table_exists('community_reviews');
}

function community_reviews_activate()
{
    global $lang, $PL;

    if (!file_exists(PLUGINLIBRARY)) {
        $lang->load('community_reviews');
        flash_message($lang->community_reviews_pluginlibrary_missing, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    if (!$PL) {
        require_once PLUGINLIBRARY;
    }

    // templates
    $templates = [];

    $directory = new DirectoryIterator(MYBB_ROOT . 'inc/plugins/community_reviews/templates');
    foreach ($directory as $file) {
        if (!$file->isDot() && !$file->isDir()) {
            $templateName = $file->getPathname();
            $templateName = basename($templateName, '.tpl');
            $templates[$templateName] = file_get_contents($file->getPathname());
        }
    }

    $PL->templates('communityreviews', 'Community Reviews', $templates);

    // stylesheets
    $stylesheets = [
        'community_reviews' => [
            'attached_to' => 'index.php',
        ],
    ];

    foreach ($stylesheets as $stylesheetName => $stylesheet) {
        $PL->stylesheet(
            $stylesheetName,
            file_get_contents(MYBB_ROOT . 'inc/plugins/community_reviews/stylesheets/' . $stylesheetName . '.css'),
            $stylesheet['attached_to']
        );
    }
}

function community_reviews_deactivate()
{
    global $lang, $PL;

    if (!file_exists(PLUGINLIBRARY)) {
        $lang->load('community_reviews');
        flash_message($lang->community_reviews_pluginlibrary_missing, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    if (!$PL) {
        require_once PLUGINLIBRARY;
    }

    // templates
    $PL->templates_delete('communityreviews', true);

    // stylesheets
    $PL->stylesheet_delete('community_reviews', true);
}
