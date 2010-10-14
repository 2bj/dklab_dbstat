<form method="get">
<input type="submit" value="Show" />
<select name="period" onchange="this.form.submit()">$SELECT_PERIODS</select> data from <input type="text" name="to" size="8" default="now"/>
</form>

<?=htmlspecialchars_decode($htmlTable)?>
<div style="display:none" id="showHideDiv">
	<a class="linkShow" href="#" onclick="$('.archived').show(); $('.linkHide').show(); $(this).hide(); return false">Show archived rows</a>
	<a class="linkHide" href="#" style="display:none" onclick="$('.archived').hide(); $('.linkShow').show(); $(this).hide(); return false">Hide archived rows</a>
</div>
<script>
if ($(".archived")[0]) $('#showHideDiv').show();
if (location.hash.match(/^#(\d+)$/)) $('.id' + RegExp.$1).show();
</script>

<form method="get" action="recalc.php" style="margin-top:1em">
	<input type="button" value="Add an item" onclick="location='item.php'"/>

	<input type="submit" value="Recalc" style="margin-left:4em" />
	from <input type="text" name="to" size="8" default="now"/> back <input type="text" name="back" size="4" default="2"/> periods
</form>
