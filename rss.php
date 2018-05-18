<?php
if (RSS && isset($_GET['rss'])) {

    $url = PROTOCOL . '://' . IP . ':' . PORT . '/webapi/DownloadStation/RSSsite.cgi?api=SYNO.'
        . 'DownloadStation.RSS.Feed&version=1&method=list&_sid=' . $_SESSION['sid'];

    $rss = json_decode(get($url), true);

    if (isset($rss['error'])) {
        displayErrorAndDie(print_r(array($rss, $url), true));
    }

    $rssData = '';
    $rssFeedTemplate = file_get_contents('template/rssFeedTemplate.html');
    $rssFeedItemTemplate = file_get_contents('template/rssFeedItemTemplate.html');

    if ($rss['success']) {
        foreach ($rss['data']['sites'] as $site) {
            $url = PROTOCOL . '://' . IP . ':' . PORT . '/webapi/DownloadStation/RSSfeed.cgi?api='
                . 'SYNO.DownloadStation.RSS.Feed&version=1&method=list&offset=0&limit=' . RSS_LIMIT . '&id='
                . $site['id'] . '&_sid=' . $_SESSION['sid'];

            $result = json_decode(get($url), true);

            if (isset($rss['error'])) {
                displayErrorAndDie(print_r(array($result, $url), true));
            }

            $siteHtml = str_replace('##SITE##', $site['title'], $rssFeedTemplate);
            $items = '';

            foreach ($result['data']['feeds'] as $feed) {
                $item = str_replace('##TITLE##', $feed['title'], $rssFeedItemTemplate);
                $item = str_replace('##TIME##', date('Y-m-d H:i:s', $feed['time']), $item);
                $item = str_replace('##LINK##', $feed['external_link'], $item);

                $items .= $item;
            }

            $feed = str_replace('##ROWS##', $items, $siteHtml);

            $rssData .= $feed;
        }
    }

    $rssTemplate = file_get_contents('template/rss.html');

    $page = str_replace('##FEEDS##', $rssData, $rssTemplate);
    $page = str_replace('##BASEURL##', getBaseUrl(), $page);
    $page = str_replace('##BODY_THEME##', (DARK ? 'bg-dark text-light' : 'bg-light text-dark'), $page);
    $page = str_replace('##TABLE_THEME##', (DARK ? 'table-dark' : 'table-light'), $page);
    $page = str_replace('##VERSION##', VERSION, $page);
    $page = str_replace('##REFRESH##', RSS_UJRATOLTES, $page);
    $page = str_replace('##MS##', round(microtime(true) - $elotte, 2), $page);

    echo $page;
    exit();
}
