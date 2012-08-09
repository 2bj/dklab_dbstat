From: DBStat <?="<$from>\n"?>
Reply-To: <?="<$replyto>\n"?>
To: <?="<" . trim($to) . ">\n"?>
Subject: <?="$title\n"?>
Content-Type: text/html; charset=UTF-8

<html>
<body>
<?=$htmlTable?>

<p>
<a href="<?=htmlspecialchars($url)?>">See the statistics online</a>
</p>
</body>
</html>
