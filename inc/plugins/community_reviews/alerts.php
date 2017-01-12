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

    class MybbStuff_MyAlerts_Formatter_CommunityReviewsSameProductReviewFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            $alertContent = $alert->getExtraDetails();

            return $this->lang->sprintf(
                $this->lang->myalerts_community_reviews_same_product_review_alert,
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

    class MybbStuff_MyAlerts_Formatter_CommunityReviewsSameProductCommentFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            $alertContent = $alert->getExtraDetails();

            return $this->lang->sprintf(
                $this->lang->myalerts_community_reviews_same_product_comment_alert,
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

            $url = CommunityReviews::url('comment', $alertContent['product_id'], CommunityReviews::toSlug($alertContent['product_name']), $alertContent['comment_id']);

            return $url;
        }
    }
}

trait CommunityReviewsAlerts
{
    static function sendAlerts($alertDetails, $alertCode, $userIds, $fromUserId = null)
    {
        global $mybb;

        if ($fromUserId === null) {
            $fromUserId = $mybb->user['uid'];
        }

        if (!CommunityReviewsMyalertsIntegrable()) {
            return false;
        }

        $index = array_search($fromUserId, $userIds);

        if ($index !== false) {
            unset($userIds[$index]);
        }

        $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode($alertCode);

        if ($alertType && $alertType->getEnabled()) {
            $alerts = [];

            foreach ($userIds as $userId) {
                $alert = new MybbStuff_MyAlerts_Entity_Alert();

                $alert
                    ->setType($alertType)
                    ->setUserId($userId)
                    ->setExtraDetails($alertDetails)
                    ->setFromUserId($fromUserId)
                ;

                $alerts[] = $alert;
            }

            if ($alerts) {
                MybbStuff_MyAlerts_AlertManager::getInstance()->addAlerts($alerts);
            }
        }
    }

    static function sendMerchantTagAlert($product, $reviewId, $userIds)
    {
        return self::sendAlerts(
            [
                'product_id' => (int)$product['id'],
                'product_name' => $product['name'],
                'review_id' => (int)$reviewId,
            ],
            'community_reviews_merchant_tag',
            $userIds
        );
    }

    static function sendSameProductReviewAlert($product, $reviewId, $userIds)
    {
        return self::sendAlerts(
            [
                'product_id' => (int)$product['id'],
                'product_name' => $product['name'],
                'review_id' => (int)$reviewId,
            ],
            'community_reviews_same_product_review',
            $userIds
        );
    }

    static function sendSameProductCommentAlert($product, $commentId, $userIds)
    {
        return self::sendAlerts(
            [
                'product_id' => (int)$product['id'],
                'product_name' => $product['name'],
                'comment_id' => (int)$commentId,
            ],
            'community_reviews_same_product_comment',
            $userIds
        );
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

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode('community_reviews_same_product_review');
        $alertTypeManager->add($alertType);

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode('community_reviews_same_product_comment');
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
        $alertTypeManager->deleteByCode('community_reviews_same_product_review');
        $alertTypeManager->deleteByCode('community_reviews_same_product_comment');
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

    if ($pluginsCache && in_array('myalerts', $pluginsCache['active'])) {
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
