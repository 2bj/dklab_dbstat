<html>
<head>
	<title>DBStat: <?=$title?></title>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript" src="static/jquery-1.4.3.min.js"></script>
	<link rel="stylesheet" href="static/styles.css">
</head>
<body>
	<a href="index.php">Statistics</a> |
	<a href="item.php">Add item</a> |
	<a href="dsns.php">Databases</a> |
	<a href="settings.php">Settings</a> |
	<a href="logout.php">Log out</a>

	<?if ($tags && !@$isGuest) {?>
		&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;
		<?include "tags.php"?>
	<?}?>


	<h1><?=@$titleHtml? unhtmlspecialchars($titleHtml) : $title?></h1>
		
	<?foreach (getAndRemoveMessages() as $msg) {?>
		<div class="message"><?=htmlspecialchars($msg)?></div>
	<?}?>
