<?if (!$GLOBALS['SELECT_DSNS']) {?>
	There are no databases yet configured.<br/>
	<a href="dsns.php">Configure databases</a>
	<?return?>
<?}?>

<form method="post">
<input type="hidden" name="item[id]" />
<table>
	<tr valign="top">
	<td>Database</td>
	<td><select name="item[dsn_id]">SELECT_DSNS</select></td>
	<td></td>
	</tr>
	<tr valign="top">
	<td>Name</td>
	<td><input type="text" name="item[name]" size="50"/></td>
	<td class="comment">Separate aliases with ";".</td>
	</tr>
	<tr valign="top">
	<td>Relative to</td>
	<td><select name="item[relative_to]"><option value="">none</option>SELECT_ITEMS</select></td>
	<td></td>
	</tr>
	<tr valign="top">
	<td>SQL</td>
	<td>
		<textarea name="item[sql]" cols="90" rows="8"></textarea><br>

		<input type="hidden" name="item[recalculatable]" value="0" />
		<input type="checkbox" id="recalculatable" name="item[recalculatable]" value="1" default="1" />
		<label for="recalculatable">Could be recalculated to the past</label><br/>

		<input type="hidden" name="item[archived]" value="0" />
		<input type="checkbox" id="archived" name="item[archived]" value="1" default="0" />
		<label for="archived">Archived (hidden, but calculated)</label><br/>
		
	</td>
	<td class="comment">
		Available marcos are:
		<ul>
		<li><b>$FROM</b>: period start (TIMESTAMP)</li>
		<li><b>$TO</b>: period end (TIMESTAMP)</li>
		<li><b>$DAYS</b>: period length (number of days)</li>
		</ul>
	</td>
	</tr>
	<tr valign="top">
	<td><br/></td>
	<td>
		<input type="submit" name="doSave" value="<?=@$_POST['item']['id']? "Save" : "Add"?>"/>
		<?if (@$_POST['item']['id']) {?>
			<input type="submit" name="doDelete" confirm="Are you sure you want to delete this item?" value="Delete" style="margin-left:1em"/>
		<?}?>
		<div style="float:right">
			<input type="submit" name="doTest" value="Test" /> or
			<input type="submit" name="doRecalc" value="Recalc" />
			from <input type="text" name="to" size="8" default="now"/> back <input type="text" name="back" size="8" default="14"/>
			<select name="period"><option value="">- ALL -</option>SELECT_PERIODS</select> periods
		</div>
	</td>
	<td><br/></td>
	</tr>
</table>
</form>

<?if ($tables) {?>
	<br/>
	<?foreach ($tables as $tableName => $tableHtml) {?>
		<h2><?=$tableName?> period last calculated values</h2>
		<?=htmlspecialchars_decode($tableHtml)?>
	<?}?>
<?}?>
