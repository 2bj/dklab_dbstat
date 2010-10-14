<?$COLORS = array("holiday" => "red", "incomplete" => "#BBBBBB")?>

<table cellpadding="3" cellspacing="1" border="0" bgcolor="#CCCCCC">
<thead bgcolor="#EEEEEE">
	<tr align="center" valign="top">
		<td width="1" align="left"><b>Name</b></td>
		<td width="1"><b>TOT</b></td>
		<td width="1"><b>AVG</b></td>
		<td><br/></td>
		<?foreach ($table['captions'] as $interval) {?>
			<?
				$styles = array();
				if (!$interval['is_complete']) $styles[] = "color:{$COLORS['incomplete']}";
				else if ($interval['is_holiday']) $styles[] = "color:{$COLORS['holiday']}";
			?>
			<td width="1" <?=$styles? 'style="' . join(";", $styles) . '"' : ''?>>
				<?=nl2br($interval['caption'])?>
			</td>
		<?}?>
	</tr>
</thead>

<tbody>
	<?$hasArchived = 0?>
	<?$i = -1; foreach ($table['groups'] as $groupName => $group) { $i++; ?>
		<?foreach ($group as $rowName => $row) {?>
			<tr 
				id="<?=$row['item_id']?>"
				<?=$row['archived']? 'style="display:none" class="archived"' : ''?> 
				<?=$row['relative_name']? 'title="Relative to ' . $row['relative_name'] . '"' : ""?> 
				align="center" valign="middle" bgcolor="#FFFFFF" 
			>
				<td nowrap="nowrap" align="left">
					<?if (@$_SERVER['GATEWAY_INTERFACE']) {?>
						<a href="<?=$base?>item.php?clone=<?=$row['item_id']?>" title="Clone this item"><img src="<?=$base?>static/clone.gif" width="10" height="10" border="0" /></a>&nbsp;
					<?}?>
					<b><a style="text-decoration:none" href="<?=$base?>item.php?id=<?=$row['item_id']?>"><?=strlen($groupName)? $groupName . "/" : ""?><?=strlen($rowName)? $rowName : "&lt;none&gt;"?></a></b>&nbsp;
				</td>
				<td><?=$row['total']?></td>
				<td><?=$row['average']?></td>
				<td width="1"><br/></td>
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
							<?=!$cell['is_complete']? 'title="Incomplete; till ' . date("Y-m-d H:i:s", $cell['created']) . ' only"' : ""?>
						>
							<?=$cell['value']?>
							<?if (strlen($cell['percent'])) {?>
								<font size="-2" color="#A0A0A0"><br/><?=sprintf(($cell['percent'] < 10? '%.1f' : '%d'), $cell['percent'])?>%</font>
							<?}?>
						</td>
					<?} else {?>
						<td><br/></td>
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

