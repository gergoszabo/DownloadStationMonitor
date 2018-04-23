<?

// kétlépcsős azonosítás esetén előbb otp, majd sid
if(TWOFA) {
	if(isset($_POST['otp'])) {
		$_SESSION['otp'] = $_POST['otp'];
	}

	if(!isset($_SESSION['otp'])) {
?>
<html>
<head>
	<title>Download Station Monitor</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<link type="text/css" rel="stylesheet" href="stylesheet.css"/>
</head>
<body>
	<h2 class="center"><i>Download Station Monitor</i></h2>
	<form method="POST" class="center">
		<label for="otp" value="Két lépcsős azonosításoz szüksége kód:" />
		<input type="text" name="otp" />
		<input type="submit" value="Belépés"/>
	</form>
</body>
</html>
<?
		die(0);
	}

	if(!isset($_SESSION['sid']))
	{
		$loginUrl = PROTOCOL.'://'.IP.':'.PORT.'/webapi/auth.cgi?api=SYNO.API.Auth&version=2&method=login'.
		'&account='.USER.'&passwd='.PASS.'&session=DownloadStation&format=sid&otp_code='.$_SESSION['otp'];

		$decodedlogin = json_decode(get($loginUrl), true);

		if(isset($decodedlogin['data']['sid']))
			$_SESSION['sid'] = $decodedlogin['data']['sid'];
	}
}

if(!isset($_SESSION['sid'])) {
	$loginUrl = PROTOCOL.'://'.IP.':'.PORT.'/webapi/auth.cgi?api=SYNO.API.Auth&version=2&method=login'.
		'&account='.USER.'&passwd='.PASS.'&session=DownloadStation&format=sid';

	$decodedlogin = json_decode(get($loginUrl), true);

	if(isset($decodedlogin['data']['sid']))
		$_SESSION['sid'] = $decodedlogin['data']['sid'];
}