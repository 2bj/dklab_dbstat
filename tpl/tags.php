Tags:&nbsp;&nbsp;
<?foreach ($tags as $tag => $cnt) {?>
	<?$tag = unhtmlspecialchars($tag)?>
	<a 
		style="<?=@$_GET['tag'] == $tag? 'font-weight:bold; text-decoration:none' : ''?>" 
		href="<?=$base?>index.php?tag=<?=urlencode(unhtmlspecialchars($tag))?>&period=<?=urlencode(@$_GET['period'])?>&to=<?=urlencode(@$_GET['to'])?>"
	><?=$tag?></a><sup style="color:gray"><?=$cnt?></sup>&nbsp;&nbsp;
<?}?>
