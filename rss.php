<?
if(RSS && isset($_GET['rss'])) {

    $url = PROTOCOL.'://'.IP.':'.PORT.'/webapi/DownloadStation/RSSsite.cgi?api=SYNO.'
        .'DownloadStation.RSS.Feed&version=1&method=list&_sid='.$_SESSION['sid'];

    $rss = json_decode(get($url));

    $rssData = array();

    if($rss->success) {
        foreach($rss->data->sites as $site) {
            $url = PROTOCOL.'://'.IP.':'.PORT.'/webapi/DownloadStation/RSSfeed.cgi?api='
                .'SYNO.DownloadStation.RSS.Feed&version=1&method=list&offset=0&limit='.RSS_LIMIT.'&id='
                .$site->id.'&_sid='.$_SESSION['sid'];

            $result = json_decode(get($url));
            $rssData[$site->title] = $result;
        }
    }
?>

<html>
<head>
	<title>Download Station Monitor</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<link type="text/css" rel="stylesheet" href="stylesheet.css"/>
</head>
<body>
<?
    foreach($rssData as $site => $result) {
?>
        <center><h4><?=$site?></h4></center>
        <table id="maintable" class="center">
            <tr>
                <th><i>Név</i></th>
                <th><i>Időpont</i></th>
                <th><i>Letöltés</i></th>
            </tr>
<?
            foreach($result->data->feeds as $feed) {
?>
                <tr>
                    <td><?=$feed->title?></td>
                    <td><?=date('Y-m-d H:i:s', $feed->time)?></td>
                    <td><a href="<?=$feed->external_link?>">Letölt</a></td>
                </tr>
<?
            }
?>
        </table>
<?
    }
?>
</body>
</html>
<?
    die();
}
