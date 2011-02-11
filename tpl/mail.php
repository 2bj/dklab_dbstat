From: DBStat <?="<$replyto>\n"?>
To: <?foreach (explode(",", $to) as $i => $email) echo(($i? ", " : "") . "<" . trim($email) . ">"); echo "\n"?>
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
