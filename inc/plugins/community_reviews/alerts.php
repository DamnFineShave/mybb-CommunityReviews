<?php

if (CommunityReviewsMyalertsIntegrable()) {
    CommunityReviewsMyalertsInit();
}

if (class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter')) {
    class MybbStuff_MyAlerts_Formatter_CommunityReviewsMerchantTagFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            $alertContent = $alert->getExtraDetails();

            return $this->lang->sprintf(
                $this->lang->myalerts_community_reviews_merchant_tag_alert,
                $outputAlert['from_user'],
                $alertContent['product_name']
            );
        }

        public function init()
        {
            $this->lang->load('community_reviews');
        }

        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
        {
            $alertContent = $alert->getExtraDetails();

            $url = CommunityReviews::url('review', $alertContent['product_id'], CommunityReviews::toSlug($alertContent['product_name']), $alertContent['review_id']);

            return $url;
        }
    }
}

trait CommunityReviewsAlerts
{
    static function sendMerchantTagAlert($product, $reviewId, $userIds)
    {
        global $mybb;

        if (!CommunityReviewsMyalertsIntegrable()) {
            return false;
        }

        $alertDetails = [
            'product_id' => (int)$product['id'],
            'product_name' => $product['name'],
            'review_id' => (int)$reviewId,
        ];

        if ($index = array_search($mybb->user['uid'], $userIds)) {
            unset($userIds[$index]);
        }

        $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('community_reviews_merchant_tag');

        if ($alertType && $alertType->getEnabled()) {
            $alerts = [];

            foreach ($userIds as $userId) {
                $alert = new MybbStuff_MyAlerts_Entity_Alert();

                $alert
                    ->setType($alertType)
                    ->setUserId($userId)
                    ->setExtraDetails($alertDetails)
                    ->setFromUserId($mybb->user['uid'])
                ;

                $alerts[] = $alert;
            }

            if ($alerts) {
                MybbStuff_MyAlerts_AlertManager::getInstance()->addAlerts($alerts);
            }
        }
    }

    static function installMyalertsIntegration()
    {
        global $db, $cache;

        if (!CommunityReviewsMyalertsIntegrable()) {
            return false;
        }

        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode('community_reviews_merchant_tag');

        $alertTypeManager->add($alertType);
    }

    static function uninstallMyalertsIntegration()
    {
        global $db, $cache;

        if (!CommunityReviewsMyalertsIntegrable()) {
            return false;
        }

        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
        $alertTypeManager->deleteByCode('community_reviews_merchant_tag');
    }
}

function CommunityReviewsMyalertsInit()
{
    defined('MYBBSTUFF_CORE_PATH') or define('MYBBSTUFF_CORE_PATH', MYBB_ROOT . 'inc/plugins/MybbStuff/Core/');
    defined('MYALERTS_PLUGIN_PATH') or define('MYALERTS_PLUGIN_PATH', MYBB_ROOT . 'inc/plugins/MybbStuff/MyAlerts');
    defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');
    require_once MYBBSTUFF_CORE_PATH . 'ClassLoader.php';

    $classLoader = new MybbStuff_Core_ClassLoader();
    $classLoader->registerNamespace('MybbStuff_MyAlerts', [MYALERTS_PLUGIN_PATH . '/src']);
    $classLoader->register();
}

function CommunityReviewsMyalertsIntegrable()
{
    global $cache;

    $pluginsCache = $cache->read('plugins');

    if (in_array('myalerts', $pluginsCache['active'])) {
        if ($euantor_plugins = $cache->read('euantor_plugins')) {
            if (isset($euantor_plugins['myalerts']['version'])) {
                $version = explode('.', $euantor_plugins['myalerts']['version']);
                if ($version[0] == '2' && $version[1] == '0') {
                    return true;
                }
            }
        }
    }

    return false;
}
