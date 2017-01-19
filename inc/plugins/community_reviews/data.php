<?php

trait CommunityReviewsData
{
    // categories
    public static function getCategory($id)
    {
        global $db;
        return $db->fetch_array(
            $db->simple_select('community_reviews_categories', '*', 'id=' . (int)$id)
        );
    }

    public static function getCategories($where = '', $options = [])
    {
        global $db;
        return $db->simple_select('community_reviews_categories', '*', $where, $options);
    }

    public static function countCategories($where = false)
    {
        global $db;
        return $db->fetch_field(
            $db->simple_select('community_reviews_categories', 'COUNT(id) as n', $where),
            'n'
        );
    }

    public static function addCategory($data)
    {
        global $db;
        return $db->insert_query('community_reviews_categories', [
            'name' => $db->escape_string($data['name']),
            'order' => (int)$data['order'],
        ]);
    }

    public static function updateCategory($id, $data)
    {
        global $db;
        return $db->update_query('community_reviews_categories', $data, 'id=' . (int)$id);
    }

    public static function deleteCategory($id)
    {
        global $db;
        return $db->delete_query('community_reviews_categories', 'id=' . (int)$id);
    }

    // fields
    public static function getField($id)
    {
        global $db;
        return $db->fetch_array(
            $db->simple_select('community_reviews_fields', '*', 'id=' . (int)$id)
        );
    }

    public static function getFields($where = '', $options = [])
    {
        global $db;
        return $db->simple_select('community_reviews_fields', '*', $where, $options);
    }

    public static function getFieldsInCategory($categoryId)
    {
        return self::getFields('category_id=' . (int)$categoryId, [
            'order_by' => '`order`',
        ]);
    }

    public static function getFieldsWithCategories($statements = '')
    {
        global $db;
        return $db->query("
            SELECT
                f.*, c.name AS category_name
            FROM
                " . TABLE_PREFIX . "community_reviews_fields f
                INNER JOIN " . TABLE_PREFIX . "community_reviews_categories c ON f.category_id=c.id
            {$statements}
        ");
    }

    public static function getDistinctFieldsWithCategories($statements = '')
    {
        global $db;

        $fieldsWithCategories = self::getFieldsWithCategories($statements);

        $distinctFields = [];
        $distinctNames = [];

        while ($field = $db->fetch_array($fieldsWithCategories)) {
            $fieldIndex = array_search($field['name'], $distinctNames);

            if ($fieldIndex === false) {
                $distinctFields[][] = $field;
                $distinctNames[] = $field['name'];
            } else {
                $distinctFields[$fieldIndex][] = $field;
            }
        }

        foreach ($distinctFields as &$distinctField) {
            $categoryFields = [];

            $i = 0;
            foreach (array_column($distinctField, 'id') as $item) {
                $categoryFields[$i++]['id'] = $item;
            }

            $i = 0;
            foreach (array_column($distinctField, 'category_name') as $item) {
                $categoryFields[$i++]['category_name'] = $item;
            }

            $distinctField = [
                'name' => array_values($distinctField)[0]['name'],
                'order' => array_values($distinctField)[0]['order'],
                'category_fields' => array_combine(
                    array_column($distinctField, 'category_id'),
                    $categoryFields
                )
            ];
        }

        return $distinctFields;
    }

    public static function getSiblingFields($id)
    {
        global $db;

        $ids = [];

        $name = $db->fetch_field(
            $db->simple_select('community_reviews_fields', 'name', 'id=' . (int)$id),
            'name'
        );

        if ($name) {
            $query = $db->simple_select('community_reviews_fields', 'id', "name='". $db->escape_string($name) . "'");

            while ($row = $db->fetch_array($query)) {
                $ids[] = $row['id'];
            }
        }

        return $ids;
    }

    public static function countFields($where = false)
    {
        global $db;
        return $db->fetch_field(
            $db->simple_select('community_reviews_fields', 'COUNT(id) as n', $where),
            'n'
        );
    }

    public static function countDistinctFields($where = false)
    {
        global $db;
        return $db->fetch_field(
            $db->simple_select('community_reviews_fields', 'COUNT(id) as n', $where, [
                'group_by' => 'name',
            ]),
            'n'
        );
    }

    public static function addField($data)
    {
        global $db;
        return $db->insert_query('community_reviews_fields', [
            'category_id' => (int)$data['category_id'],
            'name' => $db->escape_string($data['name']),
            'order' => 1,
        ]);
    }

    public static function updateField($id, $data)
    {
        global $db;
        return $db->update_query('community_reviews_fields', $data, 'id=' . (int)$id);
    }

    public static function updateFields($ids, $data)
    {
        global $db;

        if ($ids) {
            array_walk($ids, 'intval');
            return $db->update_query('community_reviews_fields', $data, 'id IN (' . implode(',', $ids) . ')');
        } else {
            return false;
        }
    }

    public static function deleteField($id)
    {
        global $db;
        return $db->delete_query('community_reviews_fields', 'id=' . (int)$id);
    }

    // products
    public static function getProduct($id)
    {
        global $db;
        return $db->fetch_array(
            $db->simple_select('community_reviews_products', '*', 'id=' . (int)$id)
        );
    }

    public static function getProducts($where = '', $options = [])
    {
        global $db;
        return $db->simple_select('community_reviews_products', '*', $where, $options);
    }

    public static function getProductsData($statements)
    {
        global $db;
        return $db->query("
            SELECT
                p.*, u.username, u.usergroup, u.displaygroup, u.avatar
            FROM
                " . TABLE_PREFIX . "community_reviews_products p
                INNER JOIN " . TABLE_PREFIX . "users u ON u.uid=p.user_id
            $statements
        ");
    }

    public static function getProductsInCategory($categoryId, $where = '', $options = [])
    {
        global $db;
        return $db->simple_select('community_reviews_products', '*', "category_id=" . (int)$categoryId . ' ' . $where, $options);
    }

    public static function getProductsDataWithReviewCount($statements = '')
    {
        global $db;
        return $db->query("
            SELECT
                p.*, c.name AS category_name, u.username, u.usergroup, u.displaygroup, u.avatar, COUNT(DISTINCT r.id) AS num_reviews, COUNT(DISTINCT pc.id) AS num_comments
            FROM
                " . TABLE_PREFIX . "community_reviews_products p
                INNER JOIN " . TABLE_PREFIX . "community_reviews_categories c ON c.id=p.category_id
                INNER JOIN " . TABLE_PREFIX . "users u ON u.uid=p.user_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews r ON r.product_id=p.id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews_comments pc ON pc.product_id=p.id
            GROUP BY p.id
            $statements
        ");
    }

    public static function getProductsDataWithReviewCountAndPhotos($where = '', $statements = '')
    {
        global $db;

        if ($where) {
            $whereStatement = 'WHERE ' . $where;
        } else {
            $whereStatement = '';
        }

        $query = $db->query("
            SELECT
                p.*, c.name AS category_name, u.username, u.usergroup, u.displaygroup, u.avatar, COUNT(DISTINCT r.id) AS num_reviews, COUNT(DISTINCT pc.id) AS num_comments
            FROM
                " . TABLE_PREFIX . "community_reviews_products p
                INNER JOIN " . TABLE_PREFIX . "community_reviews_categories c ON c.id=p.category_id
                INNER JOIN " . TABLE_PREFIX . "users u ON u.uid=p.user_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews r ON r.product_id=p.id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews_comments pc ON pc.product_id=p.id
            $whereStatement
            GROUP BY p.id
            $statements
        ");

        $products = [];

        while ($row = $db->fetch_array($query)) {
            $products[ $row['id'] ] = $row;
        }

        $productIds = array_column($products, 'id');

        $productsPhotos = self::getProductsPhotos($productIds);

        foreach ($productsPhotos as $photo) {
            $products[ $photo['product_id'] ]['photos'][] = $photo['thumbnail_url'];
        }

        return $products;
    }

    public static function getProductsWithReviewCountAndPhotosInCategory($categoryId, $statements = '')
    {
        global $db;

        $query = $db->query("
            SELECT
                p.*, COUNT(r.id) AS num_reviews, c.name AS category_name, u.username, u.usergroup, u.displaygroup, u.avatar, COUNT(DISTINCT r.id) AS num_reviews, COUNT(DISTINCT pc.id) AS num_comments
            FROM
                " . TABLE_PREFIX . "community_reviews_products p
                INNER JOIN " . TABLE_PREFIX . "community_reviews_categories c ON c.id=p.category_id
                INNER JOIN " . TABLE_PREFIX . "users u ON u.uid=p.user_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews r ON r.product_id=p.id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews_comments pc ON pc.product_id=p.id
            WHERE category_id=" . (int)$categoryId . "
            GROUP BY p.id
            $statements
        ");

        $products = [];

        while ($row = $db->fetch_array($query)) {
            $products[ $row['id'] ] = $row;
        }

        $productIds = array_column($products, 'id');

        $productsPhotos = self::getProductsPhotos($productIds);

        foreach ($productsPhotos as $photo) {
            $products[ $photo['product_id'] ]['photos'][] = $photo['thumbnail_url'];
        }

        return $products;
    }

    public static function getProductAuthors($productId)
    {
        global $db;

        $userIds = [];

        $query = $db->simple_select('community_reviews', 'DISTINCT user_id', 'product_id=' . $productId);

        while ($row = $db->fetch_array($query)) {
            $userIds[] = $row['user_id'];
        }
        
        $query = $db->simple_select('community_reviews_comments', 'DISTINCT user_id', 'product_id=' . $productId);
        
        while ($row = $db->fetch_array($query)) {
            $userIds[] = $row['user_id'];
        }

        $userIds = array_unique($userIds);

        return $userIds;
    }

    public static function countProducts($where = false)
    {
        global $db;
        return $db->fetch_field(
            $db->simple_select('community_reviews_products', 'COUNT(id) as n', $where),
            'n'
        );
    }

    public static function countProductsByName($name)
    {
        global $db;
        return self::countProducts("name='" . $db->escape_string($name) . "'");
    }

    public static function countProductsInCategory($categoryId)
    {
        return self::countProducts("category_id=" . (int)$categoryId);
    }

    public static function countProductsInAllCategories()
    {
        global $db;
        return $db->query("
            SELECT
                COUNT(p.id) AS n, category_id
            FROM
                " . TABLE_PREFIX . "community_reviews_products p
            GROUP BY category_id
        ");
    }

    public static function countMatchProductNameAgainst($string, $statements = '')
    {
        global $db;

        $string = preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $string);
        $string = preg_replace('/(\w+)/i', '$1*', $string);

        return $db->fetch_field(
            $db->query("
                SELECT
                    COUNT(id) AS n
                    FROM (
                        SELECT
                            p.id
                        FROM
                            " . TABLE_PREFIX . "community_reviews_products p
                            INNER JOIN " . TABLE_PREFIX . "community_reviews_categories c ON c.id=p.category_id
                            INNER JOIN " . TABLE_PREFIX . "users u ON u.uid=p.user_id
                            LEFT JOIN " . TABLE_PREFIX . "community_reviews r ON r.product_id=p.id
                        WHERE MATCH (p.name) AGAINST ('" . $db->escape_string($string) . "' IN BOOLEAN MODE)
                        GROUP BY p.id
                        $statements
                    ) results
            "),
            'n'
        );
    }

    public static function sumProductViews($where = false)
    {
        global $db;
        return $db->fetch_field(
            $db->simple_select('community_reviews_products', 'SUM(views) as n', $where),
            'n'
        );
    }

    public static function matchProductNameAgainst($string, $statements)
    {
        global $db;

        $string = preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $string);
        $string = preg_replace('/(\w+)/i', '$1*', $string);

        return self::getProductsDataWithReviewCountAndPhotos("MATCH (p.name) AGAINST ('" . $db->escape_string($string) . "' IN BOOLEAN MODE)", $statements);
    }

    public static function addProduct($data)
    {
        global $db;
        return $db->insert_query('community_reviews_products', [
            'category_id' => (int)$data['category_id'],
            'name' => $db->escape_string($data['name']),
            'date' => time(),
            'user_id' => (int)$data['user_id'],
            'views' => 0,
        ]);
    }

    public static function updateProduct($id, $data)
    {
        global $db;
        return $db->update_query('community_reviews_products', $data, 'id=' . (int)$id);
    }

    public static function updateProductRating($productId)
    {
        $rating = self::getProductRatingFromFields($productId);
        return self::updateProduct($productId, [
            'cached_rating' => round($rating, 2),
        ]);
    }

    public static function bumpProductViews($id)
    {
        global $db;
        return $db->write_query("
            UPDATE " . TABLE_PREFIX . "community_reviews_products
            SET
                views = views+1
            WHERE id = " . (int)$id . "
        ");
    }

    public static function deleteProduct($id)
    {
        global $db;
        return $db->delete_query('community_reviews_products', 'id=' . (int)$id);
    }

    public static function mergeProduct($id, $targetId)
    {
        global $db;

        $sourceProduct = self::getProduct($id);
        $targetProduct = self::getProduct($targetId);

        if ($sourceProduct && $targetProduct && $sourceProduct['id'] != $targetProduct['id']) {
            self::updateReviewsWhere([
                'product_id' => $targetProduct['id'],
            ], 'product_id=' . (int)$sourceProduct['id']);

            self::updateCommentsWhere([
                'product_id' => $targetProduct['id'],
            ], 'product_id=' . (int)$sourceProduct['id']);

            self::updateProductFeedEntriesWhere([
                'product_id' => $targetProduct['id'],
            ], 'product_id=' . (int)$sourceProduct['id']);

            self::updateProduct($targetId, [
                'views' => $targetProduct['views'] + $sourceProduct['views'],
            ]);

            self::deleteProduct($sourceProduct['id']);

            self::updateProductRating($targetProduct['id']);

            return true;
        } else {
            return false;
        }
    }

    // photos
    public static function getPhoto($id)
    {
        global $db;
        return $db->fetch_array(
            $db->simple_select('community_reviews_photos', '*', 'id=' . (int)$id)
        );
    }

    public static function getPhotos($where = '', $options = [])
    {
        global $db;
        return $db->simple_select('community_reviews_photos', '*', $where, $options);
    }

    public static function getProductsPhotos($productIds, $allPhotos = false)
    {
        global $db;

        $photos = [];

        if ($productIds) {
            array_walk($productIds, 'intval');

            if (!$allPhotos) {
                $columns = 'r.product_id, MIN(ph.thumbnail_url) AS thumbnail_url';
                $groupBy = 'GROUP BY r.product_id, ph.review_id, ph.`order`';
            } else {
                $columns = 'r.product_id, ph.thumbnail_url';
                $groupBy = 'GROUP BY ph.review_id, ph.`order`';
            }

            $query = $db->query("
                SELECT
                    $columns
                FROM
                    " . TABLE_PREFIX . "community_reviews r
                    INNER JOIN " . TABLE_PREFIX . "community_reviews_photos ph ON r.id=ph.review_id
                WHERE r.product_id IN (" . implode(',', $productIds) . ")
                $groupBy
                ORDER BY ph.review_id ASC, ph.`order` ASC
            ");

            $fetchedProductIds = [];

            while ($row = $db->fetch_array($query)) {
                if ($allProducts || !in_array($row['product_id'], $fetchedProductIds)) {
                    $photos[] = $row;
                }

                $fetchedProductIds[] = $row['product_id'];
            }
        }

        return $photos;
    }

    public static function getReviewPhotos($reviewId)
    {
        global $db;

        $photos = [];

        $query = $db->simple_select('community_reviews_photos', '*', 'review_id=' . (int)$reviewId, [
            'order_by' => '`order`',
        ]);

        while ($row = $db->fetch_array($query)) {
            $photos[ $row['id'] ] = $row;
        }

        return $photos;
    }

    public static function getReviewsPhotos($reviewIds)
    {
        global $db;

        if (!$reviewIds) {
            return [];
        }

        $photos = [];

        $reviewIdsFiltered = array_map('intval', $reviewIds);

        $query = $db->simple_select('community_reviews_photos', '*', 'review_id IN (' . implode(',', $reviewIdsFiltered) . ')', [
            'order_by' => '`order`',
        ]);

        while ($row = $db->fetch_array($query)) {
            $photos[ $row['review_id'] ][ $row['id'] ] = $row;
        }

        return $photos;
    }

    public static function countPhotos($where = false)
    {
        global $db;
        return (int)$db->fetch_field(
            $db->simple_select('community_reviews_photos', 'COUNT(id) as n', $where),
            'n'
        );
    }

    public static function countReviewPhotos($reviewId)
    {
        return self::countPhotos('review_id=' . (int)$reviewId);
    }

    public static function addPhoto($data)
    {
        global $db;
        return $db->insert_query('community_reviews_photos', [
            'review_id' => (int)$data['review_id'],
            'url' => $db->escape_string($data['url']),
            'thumbnail_url' => $db->escape_string($data['thumbnail_url']),
        ]);
    }

    public static function updatePhoto($id, $data)
    {
        global $db;
        return $db->update_query('community_reviews_photos', $data, 'id=' . (int)$id);
    }

    public static function setReviewFirstPhoto($reviewId, $photoId)
    {
        global $db;

        $reviewPhotos = self::getReviewPhotos($reviewId);

        if (array_key_exists($photoId, $reviewPhotos)) {
            $order = 2;

            foreach ($reviewPhotos as $photo) {
                self::updatePhoto($photo['id'], [
                    'order' => $photo['id'] == $photoId ? 1 : $order++,
                ]);
            }

            return true;
        }

        return false;
    }

    public static function setReviewFirstPhotoByUrl($reviewId, $photoUrl)
    {
        global $db;

        $reviewPhotos = self::getReviewPhotos($reviewId);

        if (array_search($photoUrl, array_column($reviewPhotos, 'url')) !== false) {
            $order = 2;

            foreach ($reviewPhotos as $photo) {
                self::updatePhoto($photo['id'], [
                    'order' => $photo['url'] == $photoUrl ? 1 : $order++,
                ]);
            }

            return true;
        }

        return false;
    }

    public static function setReviewPhotoOrder($reviewId)
    {
        global $db;

        $reviewPhotos = self::getReviewPhotos($reviewId);

        $order = 1;

        foreach ($reviewPhotos as $photo) {
            self::updatePhoto($photo['id'], [
                'order' => $order++,
            ]);
        }

        return true;
    }

    public static function deletePhoto($id)
    {
        global $db;
        return $db->delete_query('community_reviews_photos', 'id=' . (int)$id);
    }

    // reviews
    public static function getReview($id)
    {
        global $db;
        return $db->fetch_array(
            $db->simple_select('community_reviews', '*', 'id=' . (int)$id)
        );
    }

    public static function getReviews($where = '', $options = [])
    {
        global $db;
        return $db->simple_select('community_reviews', '*', $where, $options);
    }

    public static function getReviewsById($ids, $options = [])
    {
        $idsFiltered = array_map('intval', $ids);

        if (!$idsFiltered) {
            return false;
        }

        return self::getReviews('id IN (' . implode(',', $ids) . ')', $options);
    }

    public static function getReviewData($reviewId)
    {
        global $db;

        $query = $db->query("
            SELECT
                r.*, u.username, u.usergroup, u.displaygroup, u.avatar
            FROM
                " .  TABLE_PREFIX . "community_reviews r
                INNER JOIN " . TABLE_PREFIX . "users u ON r.user_id=u.uid
            WHERE r.id=" . (int)$reviewId . "
        ");

        $review = $db->fetch_array($query);

        $review['fields'] = [];

        // review fields
        if ($review) {
            $query = self::getReviewFields('review_id=' . (int)$reviewId);

            while ($row = $db->fetch_array($query)) {
                $review['fields'][$row['field_id']] = $row;
            }
        }

        return $review;
    }

    public static function getReviewDataMultiple($reviewIds)
    {
        global $db;

        $reviews = [];

        $reviewIdsFiltered = array_map('intval', $reviewIds);

        if (empty($reviewIdsFiltered)) {
            return [];
        }

        $query = $db->query("
            SELECT
                r.*, u.username, u.usergroup, u.displaygroup, u.avatar
            FROM
                " .  TABLE_PREFIX . "community_reviews r
                INNER JOIN " . TABLE_PREFIX . "users u ON r.user_id=u.uid
            WHERE id IN (" . implode(',', $reviewIdsFiltered) . ")
        ");

        while ($row = $db->fetch_array($query)) {
            $reviews[(int)$row['id']] = $row;
            $reviews[(int)$row['id']]['fields'] = [];
        }

        // review fields
        if ($reviews) {
            $query = self::getReviewFields('review_id IN (' . implode(',', $reviewIdsFiltered) . ')');

            while ($row = $db->fetch_array($query)) {
                $reviews[$row['review_id']]['fields'][$row['field_id']] = $row;
            }
        }

        return $reviews;
    }

    public static function getReviewsDataInProduct($productId, $where = '', $statements = '')
    {
        global $db;

        $reviews = [];

        $query = $db->query("
            SELECT
                r.*, u.username, u.usergroup, u.displaygroup, u.avatar
            FROM
                " .  TABLE_PREFIX . "community_reviews r
                INNER JOIN " . TABLE_PREFIX . "users u ON r.user_id=u.uid
            WHERE product_id=" . (int)$productId . " " . ($where ? 'AND ' . $where : '') . "
            " . $statements . "
        ");

        while ($row = $db->fetch_array($query)) {
            $reviews[(int)$row['id']] = $row;
            $reviews[(int)$row['id']]['fields'] = [];
        }

        // review fields
        if ($reviews) {
            $query = self::getReviewFields('review_id IN (' . implode(',', array_keys($reviews)) . ')');

            while ($row = $db->fetch_array($query)) {
                $reviews[$row['review_id']]['fields'][$row['field_id']] = $row;
            }
        }

        return $reviews;
    }

    public static function getReviewsData($statements)
    {
        global $db;
        return $db->query("
            SELECT
                r.*, p.*, u.username, u.usergroup, u.displaygroup, u.avatar
            FROM
                " . TABLE_PREFIX . "community_reviews r
                INNER JOIN " . TABLE_PREFIX . "community_reviews_products p ON p.id=r.product_id
                INNER JOIN " . TABLE_PREFIX . "users u ON u.uid=r.user_id
            $statements
        ");
    }

    public static function getReviewsDataWithReviewCount($statements)
    {
        global $db;
        return $db->query("
            SELECT
                r.*, p.category_id, p.name, p.views, p.cached_rating, c.name AS category_name, u.username, u.usergroup, u.displaygroup, u.avatar, COUNT(pr.id) AS num_reviews, COUNT(pc.id) AS num_comments
            FROM
                " . TABLE_PREFIX . "community_reviews r
                INNER JOIN " . TABLE_PREFIX . "community_reviews_products p ON p.id=r.product_id
                INNER JOIN " . TABLE_PREFIX . "community_reviews_categories c ON c.id=p.category_id
                INNER JOIN " . TABLE_PREFIX . "users u ON u.uid=r.user_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews pr ON pr.product_id=r.product_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews_comments pc ON pc.product_id=r.product_id
            GROUP BY r.id, pr.product_id
            $statements
        ");
    }

    public static function getReviewsWithPhotos($statements)
    {
        global $db;
        return $db->query("
            SELECT
                r.*, p.name, u.username, u.usergroup, u.displaygroup, ph.thumbnail_url
            FROM
                " . TABLE_PREFIX . "community_reviews r
                INNER JOIN " . TABLE_PREFIX . "community_reviews_products p ON p.id=r.product_id
                INNER JOIN " . TABLE_PREFIX . "users u ON u.uid=r.user_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews_photos ph ON ph.review_id=r.id
            WHERE ph.order = 1 OR ph.order IS NULL
            GROUP BY r.id, ph.thumbnail_url
            $statements
        ");
    }

    public static function getReviewsDataWithReviewCountAndPhotos($statements)
    {
        global $db;
        return $db->query("
            SELECT
                r.*, p.category_id, p.name, p.views, p.cached_rating, c.name AS category_name, u.username, u.usergroup, u.displaygroup, u.avatar, COUNT(pr.id) AS num_reviews, COUNT(DISTINCT pc.id) AS num_comments, ph.thumbnail_url
            FROM
                " . TABLE_PREFIX . "community_reviews r
                INNER JOIN " . TABLE_PREFIX . "community_reviews_products p ON p.id=r.product_id
                INNER JOIN " . TABLE_PREFIX . "community_reviews_categories c ON c.id=p.category_id
                INNER JOIN " . TABLE_PREFIX . "users u ON u.uid=r.user_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews pr ON pr.product_id=r.product_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews_comments pc ON pc.product_id=r.product_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews_photos ph ON ph.review_id=r.id
            WHERE ph.order = 1 OR ph.order IS NULL
            GROUP BY r.id, pr.product_id, ph.thumbnail_url
            $statements
        ");
    }

    public static function getReviewsDataWithReviewCountAndPhotosByUser($userId, $statements)
    {
        global $db;
        return $db->query("
            SELECT
                r.*, p.category_id, p.name, p.views, p.cached_rating, AVG(rf.rating) AS review_rating, c.name AS category_name, u.username, u.usergroup, u.displaygroup, u.avatar, COUNT(DISTINCT pr.id) AS num_reviews, COUNT(DISTINCT pc.id) AS num_comments, ph.thumbnail_url
            FROM
                " . TABLE_PREFIX . "community_reviews r
                INNER JOIN " . TABLE_PREFIX . "community_reviews_products p ON p.id=r.product_id
                INNER JOIN " . TABLE_PREFIX . "community_reviews_categories c ON c.id=p.category_id
                INNER JOIN " . TABLE_PREFIX . "users u ON u.uid=r.user_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews pr ON pr.product_id=r.product_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews_comments pc ON pc.product_id=r.product_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews_photos ph ON ph.review_id=r.id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews_review_fields rf ON rf.review_id=r.id
            WHERE r.user_id=" . (int)$userId . " AND (ph.order = 1 OR ph.order IS NULL)
            GROUP BY r.id, pr.product_id, ph.thumbnail_url
            $statements
        ");
    }

    public static function getReviewsDataWithReviewCountAndPhotosByMerchant($userId, $statements = '')
    {
        global $db;
        return $db->query("
            SELECT
                r.*, p.category_id, p.name, p.views, p.cached_rating, c.name AS category_name, u.username, u.usergroup, u.displaygroup, u.avatar, COUNT(DISTINCT pr.id) AS num_reviews, COUNT(DISTINCT pc.id) AS num_comments, ph.thumbnail_url
            FROM
                " .  TABLE_PREFIX . "community_reviews_merchants rm
                INNER JOIN " . TABLE_PREFIX . "community_reviews r ON r.id=rm.review_id
                INNER JOIN " . TABLE_PREFIX . "community_reviews_products p ON p.id=r.product_id
                INNER JOIN " . TABLE_PREFIX . "community_reviews_categories c ON c.id=p.category_id
                INNER JOIN " . TABLE_PREFIX . "users u ON u.uid=r.user_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews pr ON pr.product_id=r.product_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews_comments pc ON pc.product_id=r.product_id
                LEFT JOIN " . TABLE_PREFIX . "community_reviews_photos ph ON ph.review_id=r.id
            WHERE rm.user_id=" . (int)$userId . " AND (ph.order = 1 OR ph.order IS NULL)
            GROUP BY r.id, ph.thumbnail_url
            $statements
        ");
    }

    public static function countReviews($where = false)
    {
        global $db;
        return (int)$db->fetch_field(
            $db->simple_select('community_reviews', 'COUNT(id) as n', $where),
            'n'
        );
    }

    public static function countReviewsInProduct($productId)
    {
        return self::countReviews('product_id=' . (int)$productId);
    }

    public static function countReviewsInCategory($categoryId)
    {
        global $db;
        return (int)$db->fetch_field(
            $db->query("
                SELECT
                    COUNT(r.id) AS n
                FROM
                    " . TABLE_PREFIX . "community_reviews r
                    INNER JOIN " . TABLE_PREFIX . "community_reviews_products p ON p.id=r.product_id
                WHERE p.category_id=" . (int)$categoryId . "
            "),
            'n'
        );
    }

    public static function countReviewsInAllCategories()
    {
        global $db;
        return $db->query("
            SELECT
                COUNT(r.id) AS n, category_id
            FROM
                " . TABLE_PREFIX . "community_reviews r
                INNER JOIN " . TABLE_PREFIX . "community_reviews_products p ON p.id=r.product_id
            GROUP BY category_id
        ");
    }

    public static function addReview($data)
    {
        global $db;

        $id = $db->insert_query('community_reviews', [
            'product_id' => (int)$data['product_id'],
            'user_id' => (int)$data['user_id'],
            'date' => time(),
            'ipaddress' => $db->escape_binary(my_inet_pton($data['ipaddress'])),
            'price' => $db->escape_string($data['price']),
            'url' => $db->escape_string($data['url']),
            'comment' => $db->escape_string($data['comment']),
        ]);

        self::addProductFeedEntry([
            'time' => $time,
            'product_id' => (int)$data['product_id'],
            'review_id' => $id,
        ]);

        return $id;
    }

    public static function updateReview($id, $data)
    {
        global $db;
        return $db->update_query('community_reviews', $data, 'id=' . (int)$id);
    }

    public static function updateReviewsWhere($data, $where)
    {
        global $db;
        return $db->update_query('community_reviews', $data, $where);
    }

    public static function deleteReview($id)
    {
        global $db, $cache;

        $result = $db->delete_query('community_reviews', 'id=' . (int)$id);

        $db->delete_query('reportedcontent', "type = 'community_reviews_review' AND id = " . (int)$id);
        if ($db->affected_rows()) {
            $cache->update_reportedcontent();
        }

        return $result;
    }

    // review fields
    public static function getReviewField($id)
    {
        global $db;
        return $db->fetch_array(
            $db->simple_select('community_reviews_review_fields', '*', 'id=' . (int)$id)
        );
    }

    public static function getProductRatingFromFields($productId)
    {
        global $db;
        return $db->fetch_field(
            $db->query("
                SELECT
                    AVG(CAST(rating AS DECIMAL(3,2))) AS n
                FROM
                    " . TABLE_PREFIX . "community_reviews_review_fields f
                    INNER JOIN " . TABLE_PREFIX . "community_reviews r ON r.id=f.review_id
                WHERE r.product_id=" . (int)$productId . "
            "),
            'n'
        );
    }

    public static function getReviewFields($where = '', $options = [])
    {
        global $db;
        return $db->simple_select('community_reviews_review_fields', '*', $where, $options);
    }

    public static function countReviewField($where = false)
    {
        global $db;
        return $db->fetch_field(
            $db->simple_select('community_reviews_review_fields', 'COUNT(id) as n', $where),
            'n'
        );
    }

    public static function addReviewField($data)
    {
        global $db;
        return $db->insert_query('community_reviews_review_fields', [
            'review_id' => (int)$data['review_id'],
            'field_id' => (int)$data['field_id'],
            'comment' => $db->escape_string($data['comment']),
            'rating' => (int)$data['rating'],
        ]);
    }

    public static function updateReviewField($id, $data)
    {
        global $db;
        return $db->update_query('community_reviews_review_fields', $data, 'id=' . (int)$id);
    }

    public static function deleteReviewField($id)
    {
        global $db;
        return $db->delete_query('community_reviews_review_fields', 'id=' . (int)$id);
    }

    // comments
    public static function getComment($id)
    {
        global $db;
        return $db->fetch_array(
            $db->simple_select('community_reviews_comments', '*', 'id=' . (int)$id)
        );
    }

    public static function getComments($where = '', $options = [])
    {
        global $db;
        return $db->simple_select('community_reviews_comments', '*', $where, $options);
    }

    public static function getCommentsById($ids, $options = [])
    {
        $idsFiltered = array_map('intval', $ids);

        if (!$idsFiltered) {
            return false;
        }

        return self::getComments('id IN (' . implode(',', $ids) . ')', $options);
    }

    public static function getCommentDataInProduct($productId, $where = '', $statements = '')
    {
        global $db;

        if ($where) {
            $where .= ' AND ' . $where;
        }

        $query = $db->query("
            SELECT
                c.*, u.username, u.usergroup, u.displaygroup, u.avatar
            FROM
                " .  TABLE_PREFIX . "community_reviews_comments c
                INNER JOIN " . TABLE_PREFIX . "users u ON c.user_id=u.uid
            WHERE product_id=" . (int)$productId . " $where
            $statements
        ");

        return self::queryResultToArray($query);
    }

    public static function getCommentDataMultiple($commentIds)
    {
        global $db;

        $comments = [];

        $commentIdsFiltered = array_map('intval', $commentIds);

        if (empty($commentIdsFiltered)) {
            return [];
        }

        $query = $db->query("
            SELECT
                c.*, u.username, u.usergroup, u.displaygroup, u.avatar
            FROM
                " .  TABLE_PREFIX . "community_reviews_comments c
                INNER JOIN " . TABLE_PREFIX . "users u ON c.user_id=u.uid
            WHERE id IN (" . implode(',', $commentIdsFiltered) . ")
        ");

        while ($row = $db->fetch_array($query)) {
            $comments[] = $row;
        }

        return $comments;
    }

    public static function countComments($where = false)
    {
        global $db;
        return (int)$db->fetch_field(
            $db->simple_select('community_reviews_comments', 'COUNT(id) as n', $where),
            'n'
        );
    }

    public static function countCommentsInProduct($productId)
    {
        return self::countComments('product_id=' . (int)$productId);
    }

    public static function addComment($data)
    {
        global $db;

        $time = time();

        $insertData = [
            'product_id' => (int)$data['product_id'],
            'user_id' => (int)$data['user_id'],
            'date' => $time,
            'ipaddress' => $db->escape_binary(my_inet_pton($data['ipaddress'])),
            'comment' => $db->escape_string($data['comment']),
        ];

        if ($data['review_id']) {
            $insertData['review_id'] = (int)$data['review_id'];
        }

        $id = $db->insert_query('community_reviews_comments', $insertData);

        self::addProductFeedEntry([
            'time' => $time,
            'product_id' => (int)$data['product_id'],
            'comment_id' => $id,
        ]);

        return $id;
    }

    public static function updateComment($id, $data)
    {
        global $db;
        return $db->update_query('community_reviews_comments', $data, 'id=' . (int)$id);
    }

    public static function updateCommentsWhere($data, $where)
    {
        global $db;
        return $db->update_query('community_reviews_comments', $data, $where);
    }

    public static function deleteComment($id)
    {
        global $db, $cache;

        $result = $db->delete_query('community_reviews_comments', 'id=' . (int)$id);

        $db->delete_query('reportedcontent', "type = 'community_reviews_comment' AND id = " . (int)$id);
        if ($db->affected_rows()) {
            $cache->update_reportedcontent();
        }

        return $result;
    }

    // product feed index table
    public static function getProductFeedEntries($productId, $where = '', $options = [])
    {
        global $db;

        if ($where) {
            $whereClauses = ' AND ' . $where;
        } else {
            $whereClauses = '';
        }

        return $db->simple_select('community_reviews_product_feed', '*', 'product_id=' . (int)$productId . $whereClauses, $options);
    }

    public static function getFeedId($type, $id)
    {
        global $db;

        $feedId = $db->fetch_field(
            $db->simple_select('community_reviews_product_feed', 'id', $type . '_id = ' . (int)$id),
            'id'
        );

        return $feedId;
    }

    public static function addProductFeedEntry($data)
    {
        global $db;

        $insertData = [
            'product_id' => (int)$data['product_id'],
            'date' => time(),
        ];

        if (!empty($data['review_id'])) {
            $insertData['review_id'] = (int)$data['review_id'];
        }

        if (!empty($data['comment_id'])) {
            $insertData['comment_id'] = (int)$data['comment_id'];
        }

        return $db->insert_query('community_reviews_product_feed', $insertData);
    }

    public static function updateProductFeedEntriesWhere($data, $where)
    {
        global $db;
        return $db->update_query('community_reviews_product_feed', $data, $where);
    }

    public static function countProductFeedEntries($productId, $where = '')
    {
        global $db;

        if ($where) {
            $whereClauses = ' AND ' . $where;
        } else {
            $whereClauses = '';
        }

        return (int)$db->fetch_field(
            $db->simple_select('community_reviews_product_feed', 'COUNT(id) as n', 'product_id=' . (int)$productId . $whereClauses),
            'n'
        );
    }

    public static function getFeedEntryLocation($type, $id, $displayOrder = 'DESC', $whereClauses = '')
    {
        global $db;

        if ($type == 'review') {
            $entry = self::getReview($id);
            $perPage = (int)self::settings('reviews_per_page');
        } elseif ($type == 'comment') {
            $entry = self::getComment($id);
            $perPage = (int)self::settings('comments_per_page');
        }

        $feedId = self::getFeedId($type, $id);

        if (!$entry || !$feedId) {
            return false;
        }

        $comparison = $displayOrder == 'DESC'
            ? '>'
            : '<'
        ;

        if ($whereClauses) {
            $where = ' AND ' . $whereClauses;
        } else {
            $where = '';
        }

        $previousEntries = $db->fetch_field(
            $db->simple_select('community_reviews_product_feed', 'COUNT(id) AS n', 'product_id = ' . (int)$entry['product_id'] . ' AND id ' . $comparison . ' ' . (int)$feedId . $where),
            'n'
        );

        if ($perPage < 1) {
            $pageNumber = 1;
        } else {
            $pageNumber = ceil(($previousEntries + 1) / $perPage);
        }

        return [
            'product_id' => $entry['product_id'],
            'pageNumber' => $pageNumber,
        ];
    }

    public static function getEntryLocation($type, $id, $displayOrder = 'DESC', $whereClauses = '')
    {
        global $db;

        if ($type == 'review') {
            $entry = self::getReview($id);
            $table = 'community_reviews';
            $perPage = (int)self::settings('reviews_per_page');
        } elseif ($type == 'comment') {
            $entry = self::getComment($id);
            $table = 'community_reviews_comments';
            $perPage = (int)self::settings('comments_per_page');
        }

        if (!$entry) {
            return false;
        }

        $comparison = $displayOrder == 'DESC'
            ? '>'
            : '<'
        ;

        if ($whereClauses) {
            $where = ' AND ' . $whereClauses;
        } else {
            $where = '';
        }

        $previousEntries = $db->fetch_field(
            $db->simple_select($table, 'COUNT(id) AS n', 'product_id = ' . (int)$entry['product_id'] . ' AND id ' . $comparison . ' ' . (int)$id . $where),
            'n'
        );

        if ($perPage < 1) {
            $pageNumber = 1;
        } else {
            $pageNumber = ceil(($previousEntries + 1) / $perPage);
        }

        return [
            'product_id' => $entry['product_id'],
            'pageNumber' => $pageNumber,
        ];
    }

    // merchants
    public static function getReviewMerchants($reviewId, $options = [])
    {
        global $db;
        return $db->simple_select('community_reviews_merchants', '*', 'review_id=' . (int)$reviewId, $options);
    }

    public static function getReviewsMerchants($reviewIds)
    {
        global $db;

        if (!$reviewIds) {
            return [];
        }

        $merchants = [];

        $reviewIdsFiltered = array_map('intval', $reviewIds);

        $query = $db->simple_select('community_reviews_merchants', '*', 'review_id IN (' . implode(',', $reviewIdsFiltered) . ')');
        $query = $db->query("
            SELECT
                m.review_id, u.uid, u.username, u.usergroup, u.displaygroup
            FROM
                " . TABLE_PREFIX . "community_reviews_merchants m
                LEFT JOIN " . TABLE_PREFIX . "users u ON m.user_id=u.uid
            WHERE m.review_id IN (" . implode(',', $reviewIdsFiltered) . ")
        ");

        while ($row = $db->fetch_array($query)) {
            $merchants[ $row['review_id'] ][ $row['uid'] ] = $row;
        }

        return $merchants;
    }

    public static function getReviewMerchantsData($reviewId)
    {
        global $db;

        $data = [];

        $query = $db->query("
            SELECT
                user_id, review_id, u.username
            FROM
                " . TABLE_PREFIX . "community_reviews_merchants m
                INNER JOIN " . TABLE_PREFIX . "users u ON m.user_id=u.uid
            WHERE review_id=" . (int)$reviewId . "
        ");

        while ($row = $db->fetch_array($query)) {
            $data[] = $row;
        }

        return $data;
    }

    public static function getReviewMerchantsWhere($where = '', $options = [])
    {
        global $db;
        return $db->simple_select('community_reviews_merchants', '*', $where, $options);
    }

    public static function countMerchantReviews($userId)
    {
        global $db;
        return (int)$db->fetch_field(
            $db->simple_select('community_reviews_merchants', 'COUNT(review_id) as n', 'user_id=' . (int)$userId),
            'n'
        );
    }

    public static function addReviewMerchant($data)
    {
        global $db;
        return $db->insert_query('community_reviews_merchants', [
            'review_id' => (int)$data['review_id'],
            'user_id' => (int)$data['user_id'],
        ]);
    }

    public static function updateReviewMerchantsWhere($data, $where)
    {
        global $db;
        return $db->update_query('community_reviews_merchants', $data, $where);
    }

    public static function deleteReviewMerchant($reviewId, $userId)
    {
        global $db;
        return $db->delete_query('community_reviews_merchants', 'review_id=' . (int)$reviewId . ' AND user_id=' . (int)$userId);
    }

    public static function deleteReviewMerchants($reviewId)
    {
        global $db;
        return $db->delete_query('community_reviews_merchants', 'review_id=' . (int)$reviewId);
    }

    public static function setReviewMerchants($reviewId, $merchants)
    {
        self::deleteReviewMerchants($reviewId);

        foreach ($merchants as $merchant) {
            self::addReviewMerchant([
                'review_id' => $reviewId,
                'user_id' => $merchant['uid'],
            ]);
        }
    }

    // common
    public static function queryResultToArray($result, $indexColumn = false)
    {
        global $db;

        $array = [];

        if ($db->num_rows($result)) {
            while ($row = $db->fetch_array($result)) {
                if ($indexColumn) {
                    $array[ $row[$indexColumn] ] = $row;
                } else {
                    $array[] = $row;
                }
            }
        }

        return $array;
    }
}
