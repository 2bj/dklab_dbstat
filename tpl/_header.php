<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>
	<title>DBStat: <?=$title?></title>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript" src="static/jquery-1.4.3.min.js"></script>
	<link rel="stylesheet" href="static/styles.css?<?=filemtime('../static/styles.css')?>">
</head>
<body>

<div id="header_background"></div>
<div id="header">
	<div class="logo">
		<a href="index.php"><img src="static/logo.gif" width="108" height="43"/></a>
	</div>
    <div class="menu">
        <?foreach ($menu as $url => $info) {?>
            <?ob_start()?>
				<?if (@$info['submenu']) {?><img src="static/triangle_down.gif" class="triangle_down"/><?}?><a href="<?=$url?>" class="main_menu_link"><?=$info['title']?></a>
			<?$link = ob_get_clean()?>
            
            <div class="item <?=$info['current']? 'current' : ''?> <?=$url == "logout.php"? "right" : ""?>">
				<?if (@$info['submenu']) {?>
	                <div class="inner real submenu">
		                <?=$link?>
	                	<?foreach ($info['submenu'] as $url => $subinfo) {?>
	                		<div class="submenu_item">
	                			<a href="<?=$url?>" class="<?=$subinfo['current']? 'current' : ''?>"><?=$subinfo['title']?><span class="count"><?=$subinfo['count']?></span></a>
	                		</div>
	                	<?}?>
	                </div>
				<?}?>
                <div class="inner real">
					<?=$link?>
                </div>
                <div class="inner shadow current">
                	<?=$link?>
                </div>
            </div>
        <?}?>
    </div>
</div>

<div id="text">

	<h1><?=@$titleHtml? unhtmlspecialchars($titleHtml) : $title?></h1>
		
	<?foreach (getAndRemoveMessages() as $msg) {?>
		<div class="message"><?=htmlspecialchars($msg)?></div>
	<?}?>
