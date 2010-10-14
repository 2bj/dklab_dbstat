<?php
require_once "config.php";
chdir(dirname(__FILE__));
require_once "lib/config.php";
require_once "HTML/FormPersister.php";
require_once "Mail/Simple.php";
require_once "PDO/Simple.php";

// Initialize environment.
if (isCgi() && defined("USE_GZIP")) {
	ob_start("ob_gzhandler");
}
if (isCgi()) {
	ob_start(array('HTML_FormPersister', 'ob_formpersisterhandler'));
}

session_start();
$DB = createDbConnection();

// Check credentials.
if (isCgi() && !defined("NO_AUTH")) {
	if (isset($_POST['auth'])) {
		$cred = $_POST['auth']['login'] . ":" . $_POST['auth']['pass'];
		if (getSetting("loginpass") === $cred) {
			$_SESSION['credentials'] = $cred;
			selfRedirect();
		} else {
			addMessage("Authentication failed.");
		}
	}
	if (strval(@$_SESSION['credentials']) !== getSetting("loginpass", "")) {
		template("login", array("title" => "Authenticate yourself"));
		exit();
	}
}

// Undo damned magic_quotes_gpc().
if (get_magic_quotes_gpc()) {
	foreach (array("_GET", "_POST") as $k) {
		if (isset($GLOBALS[$k])) {
			array_walk_recursive($GLOBALS[$k], create_function('&$a', '$a = stripslashes($a);'));
		}                  
	}
}


/**
 * Initially creates a database connection and applies all migrations
 * if needed.
 *
 * @return PDO
 */
function createDbConnection()
{
	$DB = new PDO_Simple(DB_DSN);
	try {
		$version = $DB->selectCell("SELECT version FROM version");
	} catch (PDOException $e) {
		$version = -1;
	}
	foreach (glob("sql/*.sql") as $f) {
		if (preg_match('/^(\d+)/s', basename($f), $m) && intval($m[1]) > intval($version)) {
			$sql = file_get_contents($f);
			try {
				$DB->exec("BEGIN; " . $sql . "; COMMIT;");
				$DB->update("UPDATE version SET version=?", intval($m[1]));
			} catch (Exception $e) {
				die("Exception: " . $e->getMessage() . "\n" . $sql);
			}
		}
	}
	return $DB;
}


/**
 * Returns information about allowed accounting periods.
 *
 * @return array
 */
function getPeriodsMetadata()
{
	$rows = array(
		array("period" => "day",   "avg_len" => 3600 * 24,      "caption" => "Daily",   "uniq" => "Y-m-d", "fmt" => "D\nM\nd"),
		array("period" => "week",  "avg_len" => 3600 * 24 * 7,  "caption" => "Weekly",  "uniq" => "getNextWeekendDate",   "fmt" => "D\nM\nd"),
		array("period" => "month", "avg_len" => 3600 * 24 * 30, "caption" => "Monthly", "uniq" => "Y-m",   "fmt" => "M\nY"),
		array("period" => "total", "avg_len" => 1e10,           "caption" => "Total",   "uniq" => "Y-m-d", "fmt" => "D\nM\nd"),
	);
	$result = array();
	foreach ($rows as $row) {
		$result[$row['period']] = $row;
	}
	return $result;
}


function getPeriodMetadata($period)
{
	$metadatas = getPeriodsMetadata();
	if (!isset($metadatas[$period])) throw new Exception("No such period: $period");
	return $metadatas[$period];
}


/**
 * Renders a template.
 *
 * @param string $__name
 * @param array $__args
 */
function template($__name, $__args = array(), $noLayout = false, $noQuote = false)
{
	if (!$noQuote) {
		array_walk_recursive($__args, create_function('&$a', '$a = htmlspecialchars($a);'));
	}
	extract($__args);
	$__cwd = getcwd();
	chdir(dirname(__FILE__) . "/tpl");
	if (!$noLayout) require "_header.php";
	require "$__name.php";
	if (!$noLayout) require "_footer.php";
	chdir($__cwd);
}


/**
 * Returns array of time intervals started from $to.
 * First interval is always staredr from $to, other intervals are $period-aligned.
 *
 * @return array   array(array("to" =>, "from" =>, "caption" =>, "complete"=>[true|false]), ...)
 */
function getTimeSeries($to, $back, $period)
{
	$metadata = getPeriodsMetadata();
	$meta = getPeriodMetadata($period);
	$minDate = @strtotime(getSetting("mindate", "1971-01-01"));
	if (!$minDate) $minDate = 0;
	// Find the minimum allowed grid step.
	$decrement = 100000000;
	foreach ($metadata as $v) {
		$decrement = min($decrement, $v['avg_len']);
	}
	// Generate series.	
	$series = array();
	for ($time = $to, $i = 0; $i < $back; $i++) {
		if ($time < $minDate) break;
		$from = $time;
		$uniq = getUniqForTime($from, $meta);
		while (getUniqForTime($from - $decrement, $meta) == $uniq) {
			$from -= $decrement;
		}
		$from = trunkTime($from);
		$caption = date($meta['fmt'], $time);
		$series[] = array(
			"uniq"          => $uniq,
			"to"            => $time, 
			"from"          => $period != "total"? $from : 0,
			"caption"       => $caption,
			"period"        => $period,
			"periodCaption" => $meta['caption'],
			"is_complete"   => getUniqForTime($time, $meta) != getUniqForTime($time + 1, $meta), // boundary of 2 intervals
			"is_holiday"    => preg_match('/SU|SA/i', $caption),
		);
		$time = $from - 1;
	}
	return $series;
}


/**
 * Truncate time to lower bound of minimum accounting interval (e.g. 1 day).
 *
 * @param int $time
 * @return int
 */
function trunkTime($time)
{
	return strtotime(date('Y-m-d', $time));
}


/**
 * Returns uniq key for an interval which includes $time value,
 *
 * @param int $time
 * @param array $meta
 * @return string
 */
function getUniqForTime($time, $meta)
{
	if (is_callable($meta['uniq'])) {
		return call_user_func($meta['uniq'], $time);
	} else {
		return date($meta['uniq'], $time);
	}
}


/**
 * Helper function: return a date of the next weekend.
 *
 * @param int $time
 * @return int
 */
function getNextWeekendDate($time)
{
	while (date("w", $time) != 0) $time += 3600 * 24;
	return date("Y-m-d", $time);
}


/**
 * Returns array of periods names which could be used to create <SELECT>.
 *
 * @return array
 */
function getPeriods()
{
	$result = array();
	foreach (getPeriodsMetadata() as $period => $info) {
		$result[$period] = $info['caption'];
	}
	return $result;
}


/**
 * Generates a table with stats data.
 *
 * @param int $period
 * @param int $from
 * @param int $to
 * @param int $onlyItemId
 * @return array  Array of groups of rows.
 */
function generateTableData($to, $back, $period, $onlyItemId = null)
{
	global $DB;
	$meta = getPeriodMetadata($period);
	$series = getTimeSeries($to, $back, $period);
	$to = $series[0]["to"];
	$from = $series[count($series) - 1]["from"];
	$cells = $DB->select('
			SELECT 
				item.name, item.id AS item_id, item.archived AS archived,
				c.id AS data_id, c.value, c.created,
				t.value AS total, 
				r.value AS relative_value,
				ri.name AS relative_name
			FROM 
				item 
				LEFT JOIN data c ON (
					c.item_id = item.id
					AND ? <= c.created AND c.created <= ?
					AND c.period = ?
				)
				LEFT JOIN data t ON (
					t.item_id = item.id
					AND t.created = c.created
					AND t.period = \'total\'
				)
				LEFT JOIN data r ON (
					r.item_id = item.relative_to
					AND r.created = c.created
					AND r.period = c.period
				)
				LEFT JOIN item ri ON (
					ri.id = item.relative_to
				)
			WHERE 
				1=1
				' . ($onlyItemId? ' AND item.id=' . $onlyItemId : '') . '
			ORDER BY item.name, c.created DESC
		',
		$from, $to, $period
	);

	// For each data cell compute its unique date point.
	$names = array();
	foreach ($cells as $cell) {
		$name = $cell['name'];
		if (!isset($names[$name])) {
			$names[$name] = array();
		}
		if ($cell['data_id']) {
			$uniq = getUniqForTime($cell['created'], $meta);
			if (!isset($names[$name][$uniq])) {
				$cell['percent'] = (is_numeric($cell['relative_value']) && $cell['relative_value']? ($cell['value'] / $cell['relative_value'] * 100) : null);
				$names[$name][$uniq] = $cell;
			}
		} else {
			// Save item_id information.
			$names[$name][""] = $cell;
		}
	}
	
	// Expand multi-place names.
	foreach ($names as $name => $row) {
		$list = preg_split('/\s*;\s*/s', $name);
		if (count($list) > 1) {
			unset($names[$name]);
			foreach ($list as $subName) {
				foreach ($row as $k => $v) {
					$names[$subName][$k] = $v;
				}
			}
		}
	}
	ksort($names);
	
	// Now build resulting table columns.
	$table = array();
	$captions = array();
	$hasFirstColumn = false;
	foreach ($names as $name => $cells) {
		// Create a new row in the table.
		$group = "";
		if (preg_match('{^(.*?)/(.*)}s', $name, $m)) {
			$group = $m[1];
			$name = $m[2];
		}
		$group = preg_replace('/^(\W*)\d+/s', '$1', $group);
		$name = preg_replace('/^(\W*)\d+/s', '$1', $name);
		$cell = current($cells);
		$table[$group][$name] = array(
			"total"         => false,
			"average"       => 0,
			"average_filled"=> 0,
			"relative_name" => null,
			"item_id"       => @$cell['item_id'],
			"archived"      => @$cell['archived'],
			"cells"         => array(),
		);
		$rr =& $table[$group][$name];
				
		// Calculate columns.
		$total = null;
		foreach (array_values($series) as $i => $interval) {
			$uniq = $interval['uniq'];
			if (isset($cells[$uniq])) {
				$cell = $cells[$uniq];
				$cell['is_complete'] = ($interval['is_complete'] && $cell['created'] == $interval['to']);
				if ($rr['total'] === false) {
					$rr['total'] = $cell['total'];
				}
				if ($cell['is_complete'] && strlen($cell['value'])) {
					$rr['average'] += $cell['value'];
					$rr['average_filled']++;
				}
				$rr['relative_name'] = $cell['relative_name'];
				$rr['item_id'] = $cell['item_id'];
				$rr['cells'][$uniq] = $cell;
				if ($i == 0) {
					$hasFirstColumn = true;
				}
			} else {
				$rr['cells'][$uniq] = null;
			}
		}
	}
	
	// Calculate average.
	foreach ($table as $groupName => $group) {
		foreach ($group as $rowName => $row) {
			if ($row['average_filled']) {
				$av = $row['average'] / $row['average_filled'];
				$v = sprintf(($av < 10? "%.2f" : "%.1f"), $av);
				$table[$groupName][$rowName]['average'] = $v > 500? round($v) : $v;
			}
		}
	}
	
	// Build captions.
	$captions = array();
	foreach ($series as $interval) {
		$captions[$interval['uniq']] = $interval;
	}

	// Remove first column if it is empty.
	if (!$hasFirstColumn) {
		foreach ($table as $groupName => $group) {
			foreach ($group as $rowName => &$rr) {
				if ($rr['cells']) {
					reset($rr['cells']);
					unset($rr['cells'][key($rr['cells'])]);
				}
			}
		}
		reset($captions);
		unset($captions[key($captions)]);
	}
		
	return array(
		"captions" => $captions,
		"groups"   => $table
	);
}

/**
 * Generates a HTML representation of the stats data.
 *
 * @param array $table
 * @return string
 */
function generateHtmlTableFromData($table)
{
	$period = null;
	if ($table['captions']) {
		$firstInterval = current($table['captions']);
		$period = $firstInterval['period'];
	}
	$base = preg_replace("{/[^/]*$}s", "/", getSetting("index_url"));
	ob_start();
	template("table", array("table" => $table, "base" => $base, "period" => $period), true);
	$html = ob_get_clean();
	$html = preg_replace('/\s+/s', ' ', $html);
	$html = preg_replace('/\s*>\s*/s', '>', $html);
	$html = preg_replace('/\s*<\s*/s', '<', $html);
	return $html;
}


function selfRedirect($msg = null)
{
	if ($msg) addMessage($msg);
	header("Location: {$_SERVER['REQUEST_URI']}");
	exit();
}


function redirect($url, $msg = null)
{
	if ($msg) addMessage($msg);
	header("Location: {$url}");
	exit();
}


function addMessage($text)
{
	$_SESSION['messages'][] = $text;
}


function getAndRemoveMessages()
{
	$msgs = isset($_SESSION['messages'])? $_SESSION['messages'] : array();
	unset($_SESSION['messages']);
	return $msgs;
}


function validateItem($item)
{
	if (!strlen(trim($item['name']))) {
		throw new Exception('Name must be specified');
	}
	if (!strlen(trim($item['sql']))) {
		throw new Exception('SQL must be specified');
	}
	if (!strlen($item['relative_to'])) {
		$item['relative_to'] = null;
	}
	$item['recalculatable'] = intval(@$item['recalculatable']);
	return $item;
}


function recalcItemRow($itemId, $to, $back, $period)
{
	global $DB;
	$series = getTimeSeries($to, $back, $period);
	$item = $DB->selectRow("SELECT * FROM item WHERE id=?", $itemId);
	foreach ($series as $interval) {
		recalcItemCell($item, $interval);
	}
}


function recalcItemCell($item, $interval)
{
	global $DB;
	try {
		$t0 = microtime(true); // for catch {} block
		writeLogLine("[" . preg_replace('/\s+/s', ' ', $interval['caption']) . "] \"{$item['name']}\" " . sprintf("%-13s", strtolower($interval['periodCaption']) . "..."));
		// Test if we could calculate this item.
		if (!$item['recalculatable']) {
			if (trunkTime(time()) != trunkTime($interval['to']) && trunkTime(trunkTime(time()) - 1) != trunkTime($interval['to'])) {
				writeLogLine("skipped (cannot be recalculated to the past)\n");
				return;
			}
		}
		// Connect to the database with connection pooling.
		$dsn = $DB->selectCell("SELECT value FROM dsn WHERE id=?", $item['dsn_id']);
		static $dbs = array();
		if (!isset($dbs[$dsn])) {
			$dbs[$dsn] = new PDO_Simple($dsn);
		}
		$db = $dbs[$dsn];
		// Run the calculation.
		$t0 = microtime(true); // refresh $t0 excluding connect time
		$sql = $item['sql'];
		$macros = array(
			'TO'    => date("Y-m-d H:i:s", $interval['to']), // we do not trunk $to here
			'FROM'  => date("Y-m-d H:i:s", $interval['from']),
			'DAYS'  => intval(($interval['to'] - $interval['from']) / 3600 / 24),
			'HOURS' => intval(($interval['to'] - $interval['from']) / 3600),
		);
		foreach ($macros as $k => $v) {
			$sql = str_replace('$' . $k, "'$v'", $sql);
		}
		$value = $db->selectCell($sql);
		$DB->update('DELETE FROM data WHERE item_id=? AND period=? AND created=?', $item['id'], $interval['period'], $interval['to']);
		$DB->update(
			'INSERT INTO data(id, item_id, period, created, value) VALUES(?, ?, ?, ?, ?)',
			$DB->getSeq(), $item['id'], $interval['period'], $interval['to'], $value
		);
		$t1 = microtime(true);
		writeLogLine("OK ($value); took " . sprintf("%d ms", ($t1 - $t0) * 1000) . "\n");
	} catch (Exception $e) {
		$t1 = microtime(true);
		writeLogLine("ERROR! " . preg_replace('/[\r\n]+/', ' ', $e->getMessage()) . "; took " . sprintf("%d ms", ($t1 - $t0) * 1000) . "\n");
		throw $e;
	}
}

function writeLogLine($line, $noEscape = false)
{
	if (@$_SERVER['GATEWAY_INTERFACE']) {
		if (!$noEscape) {
			$line = htmlspecialchars($line);
			$line = str_replace(" ", "&nbsp;", $line);
			$line = nl2br($line);
		}
		$line .= '
			<script type="text/javascript">
			if (document.body && !window.sct) {
				window.sct = setTimeout(function() { document.body.scrollTop=100000000; window.sct=null; }, 50);
			}
			</script>
		';
	}
	echo $line;
	if (ob_get_level()) ob_flush();
	flush();
}


function isCgi()
{
	return !empty($_SERVER['GATEWAY_INTERFACE']);
}


function parseToBackPeriod($arr, $wholeIntervalByDefault = false)
{
	if (@$arr['to']) {
		$to = @strtotime($arr['to']);
		if (!$to) throw new Exception("Invalid date format: {$arr['to']}");
		// ATTENTION!
		// If somebody enters "2010-05-02", he means "2010-05-02 23:59:59", not "2010-05-02 00:00:00".
		if ($to == trunkTime($to)) $to = trunkTime($to + 3600 * 24) - 1;
	} else {
		if ($wholeIntervalByDefault) {
			$to = trunkTime(time()) - 1;
		} else {
			$to = time();
		}
	}
	$period = isset($arr['period'])? $arr['period'] : 'day';
	$back = @$arr['back']? $arr['back'] : getSetting("cols", 30);
	return array($to, $back, $period);
}


function getSetting($name, $default = null)
{
	global $DB;
	$v = $DB->selectCell("SELECT value FROM setting WHERE name=?", $name);
	if (!strlen($v)) $v = $default;
	return $v;
}


function setSetting($name, $value)
{
	global $DB;
	if ($DB->selectCell("SELECT 1 FROM setting WHERE name=?", $name)) {
		$DB->update("UPDATE setting SET value=? WHERE name=?", $value, $name);
	} else {
		$DB->update("INSERT INTO setting(name, value) VALUES(?, ?)", $name, $value);
	}
}


