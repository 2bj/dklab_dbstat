<form method="get" style="padding-bottom:8px">
	<input type="hidden" name="tag" />
	<input type="submit" value="Show" />
	<select name="period" onchange="this.form.submit()">$SELECT_PERIODS</select> data from <input type="text" name="to" size="8" default="now"/>
	<span style="display:none" id="showHideDiv">
		<span>Show archived rows</span>
		<span style="display:none">Hide archived rows</span>
	</span>
    &nbsp;&nbsp;&nbsp;
    Export as CSV:
    <a href="export.php?<?=htmlspecialchars(glueQs($_SERVER['QUERY_STRING'], "period=$period"))?>">this period</a>,
    <a href="export.php?<?=htmlspecialchars(glueQs($_SERVER['QUERY_STRING'], "period="))?>">all periods</a>
</form>


<?=unhtmlspecialchars($htmlTable)?>

<form method="get" action="recalc.php" style="padding-top:8px">
	<input type="button" value="Add an item" onclick="location='item.php'"/>
	<input type="submit" value="Recalc" style="margin-left:4em" />
	from <input type="text" name="to" size="8" default="now"/> back <input type="text" name="back" size="4" default="2"/> periods
</form>
