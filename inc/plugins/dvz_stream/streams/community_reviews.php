<?php

if (!class_exists('CommunityReviews')) {
    return false;
}

global $lang;
$lang->load('community_reviews');

$stream = new \dvzStream\Stream;

$stream->setName(explode('.', basename(__FILE__))[0]);

$stream->setTitle($lang->dvz_stream_stream_community_reviews);
$stream->setEventTitle($lang->dvz_stream_stream_community_reviews_event);

$stream->setFetchHandler(function (int $limit, int $lastEventId = 0) use ($stream) {
    global $mybb, $db, $cache;

    $streamEvents = [];

    $queryWhere = null;

    $data = $db->query("
        SELECT
            r.*,
            p.name,
            u.username, u.usergroup, u.displaygroup, u.avatar,
            ph.thumbnail_url
        FROM
            " . TABLE_PREFIX . "community_reviews r
            INNER JOIN " . TABLE_PREFIX . "community_reviews_products p ON p.id=r.product_id
            INNER JOIN " . TABLE_PREFIX . "users u ON u.uid=r.user_id
            LEFT JOIN " . TABLE_PREFIX . "community_reviews_photos ph ON ph.review_id=r.id
        WHERE ph.order = 1 OR ph.order IS NULL
        GROUP BY r.id, ph.thumbnail_url
        HAVING id > " . (int)$lastEventId . " ORDER BY r.date DESC LIMIT " . (int)$limit . "
    ");

    while ($row = $db->fetch_array($data)) {
        $streamEvent = new \dvzStream\StreamEvent;

        $streamEvent->setStream($stream);
        $streamEvent->setId($row['id']);
        $streamEvent->setDate($row['date']);
        $streamEvent->setUser([
            'id' => $row['user_id'],
            'username' => $row['username'],
            'usergroup' => $row['usergroup'],
            'displaygroup' => $row['displaygroup'],
            'avatar' => $row['avatar'],
        ]);
        $streamEvent->addData([
            'product_id' => $row['product_id'],
            'name' => $row['name'],
            'thumbnail_url' => $row['thumbnail_url'],
        ]);

        $streamEvents[] = $streamEvent;
    }

    return $streamEvents;
});

$stream->addProcessHandler(function (\dvzStream\StreamEvent $streamEvent) {
    global $mybb;

    $data = $streamEvent->getData();

    $url = CommunityReviews::url('review', (int)$data['product_id'], CommunityReviews::toSlug($data['name']), (int)$streamEvent->getId());
    $item = '<a href="' . $url . '">' . htmlspecialchars_uni($data['name']) . '</a>';

    $streamEvent->setItem($item);
});

$stream->addPostFormatHandler(function (\dvzStream\StreamEvent $streamEvent, array &$event, string &$eventDetails = null, string &$eventAppendix = null) {
    $data = $streamEvent->getData();

    if ($thumbnailUrl = $data['thumbnail_url']) {
        $url = CommunityReviews::url('review', (int)$data['product_id'], CommunityReviews::toSlug($data['name']), (int)$streamEvent->getId());
        $eventAppendix .= '<div class="thumbnail"><a href="' . $url . '"><img alt="' . $lang->community_reviews_product_photo . '" src="' . \htmlspecialchars_uni($thumbnailUrl) . '" /></a></div>';
    }
});

\dvzStream\addStream($stream);
