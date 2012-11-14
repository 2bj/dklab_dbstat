<?$COLORS = array("holiday" => "red", "incomplete" => "#BBBBBB")?>
<?$hideCommonPrefix = getSetting('hide_common_prefix', 0)?>

<table cellpadding="3" cellspacing="1" border="0" bgcolor="#CCCCCC">
<thead bgcolor="#F5F5F5">
	<tr align="center" valign="top">
		<td width="1" align="middle" valign="middle"><b>#</b></td>
		<td width="1" align="left" valign="middle"><b>Name</b></td>
		<td width="1" valign="middle"><b>TOT</b></td>
		<td width="1" valign="middle"><b>AVG</b></td>
		<td><br/></td>
		<?foreach ($table['captions'] as $interval) {?>
			<?
				$styles = array();
				if (!$interval['is_complete']) $styles[] = "color:{$COLORS['incomplete']}";
				else if ($interval['is_holiday']) $styles[] = "color:{$COLORS['holiday']}";
			?>
			<td width="1"<?=$styles? ' style="' . join(";", $styles) . '"' : ''?>>
				<?=nl2br($interval['caption'])?>
			</td>
		<?}?>
	</tr>
</thead>

<tbody class="table_data">
	<?$hasArchived = 0?>
    <?$zebra = array("#FFFFFF", !isCgi()? "FAFAFA" : "#FFFFFF")?>
	<?$i = -1; $n = 0; $caption = ''?>
    <?foreach ($table['groups'] as $groupName => $group) { $i++; ?>
		<?foreach ($group as $rowName => $row) { $n++; ?>
		    <?$prevCaption = $caption?>
		    <?$caption = (strlen($groupName)? $groupName . "/" : "") . (strlen($rowName)? $rowName : "&lt;none&gt;")?>
			<tr 
				id="<?=$row['item_id']?>"
				<?=$row['archived']? 'style="display:none" class="archived id' . $row['item_id'] . '"' : ''?> 
				<?=$row['relative_name']? 'title="Relative to ' . $row['relative_name'] . '"' : ""?> 
				align="center" valign="middle" bgcolor="<?=$zebra[$n % 2]?>">
        		<td><font color="#AAA"><?=$n?></font></td>
				<td nowrap="nowrap" align="left">
					<?if (@$_SERVER['GATEWAY_INTERFACE']) {?>
						<a href="<?=$base?>item.php?clone=<?=$row['item_id']?><?=isCgi()?'&retpath='.urlencode($_SERVER['REQUEST_URI']):''?>" title="Clone this item"><img src="<?=$base?>static/clone.gif" width="10" height="10" border="0" /></a>&nbsp;
					<?}?>
					<b><a style="text-decoration:none" href="<?=$base?>item.php?id=<?=$row['item_id']?><?=isCgi()?'&retpath='.urlencode($_SERVER['REQUEST_URI']):''?>"><?=$hideCommonPrefix? makeCommonPrefixTransparent($prevCaption, $caption, '/', 'color:#f5f5f5') : $caption?></a></b>&nbsp;
				</td>
				<td><?=$row['total']?></td>
				<td><?=$row['average']?></td>
				<td width="1" class="check">
					<?if (isCgi()) {?>
						<input type="checkbox" class="chk" name="chk[<?=$row['item_id']?>]" value="1"/>
					<?}?>
				</td>
				<?foreach ($table['captions'] as $uniq => $interval) {?>
					<?if (is_array($cell = $row["cells"][$uniq])) {?>
						<?
							$styles = array();
							if (strlen($cell['percent'])) $styles[] = "line-height:70%";
							if (!$cell['is_complete']) $styles[] = "color:{$COLORS['incomplete']}";
							else if ($interval['is_holiday']) $styles[] = "color:{$COLORS['holiday']}";
						?>
						<td 
							<?=$styles? 'style="' . join(";", $styles) . '"' : ''?> 
							<?=!$cell['is_complete']? 'class="incomplete" title="Incomplete; till ' . date("Y-m-d H:i:s", $cell['created']) . ' only"' : ""?>
							value="<?=preg_match('/^\d/s', trim($cell['value']))? trim($cell['value']) : ''?>"
						>
							<?=$cell['value']?>
							<?if (strlen($cell['percent'])) {?>
								<font size="-2" color="#A0A0A0"><br/><?=$cell['percent']?>%</font>
							<?}?>
						</td>
					<?} else {?>
						<td class="incomplete"><br/></td>
					<?}?>
				<?}?>
			</tr>
		<?}?>
		<?
			$archivedGroup = 1; 
			foreach ($group as $row) if (!$row['archived']) $archivedGroup = 0; else $hasArchived = 1;
		?>
		<?if ($i < count($table['groups']) - 1) {?>
			<tr 
				<?=$archivedGroup? 'style="display:none" class="archived"' : ''?>
				align="center" valign="middle" bgcolor="#FFFFFF"
			>
				<?for ($n = 0; $n < count($table['captions']) + 4; $n++) {?>
					<td height="10"><span></span></td>
				<?}?>
			</tr>
		<?}?>
	<?}?>
</tbody>
</table>

