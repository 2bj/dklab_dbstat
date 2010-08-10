<?php
//
// Command-line usage:
//   php recalc.php {<only_period>|all}> {<back>}
//
// No HTTP GZIP must be here!
//
define("NO_AUTH", 1);
require_once "overall.php";

// By default recalc only 1 period back.
if (!isset($_GET['back'])) $_GET['back'] = 1;

list ($to, $back, $period) = parseToBackPeriod($_GET, true);
if (is_numeric(@$_SERVER['argv'][2])) $back = $_SERVER['argv'][2];

$fromId = intval(@$_GET['fromid']);
if (!$fromId) $fromId = 0;

$periods = getPeriods();
$onlyPeriod = @$_SERVER['argv'][1];
if (!isset($periods[$onlyPeriod])) $onlyPeriod = null;

if (isCgi()) {
	echo "<body>";
}
writeLogLine(($fromId? "Continuing" : "Starting") . " recalculation.\n");

$items = $DB->select("SELECT * FROM item WHERE id > ? ORDER BY id", $fromId); // ORDER BY id is IMPORTANT!

$hasError = false;
$t0 = microtime(true);
foreach ($items as $item) {
	foreach ($periods as $period => $periodName) {
		if ($onlyPeriod !== null && $period != $onlyPeriod) continue;
		try {
			recalcItemRow($item['id'], $to, $back, $period);
		} catch (Exception $e) {
			// nothing; error is already displayed above
			$hasError = true;
		}
	}
	writeLogLine("\n");
	if (isCgi() && microtime(true) - $t0 > MAX_RECALC_CGI_TIME) {
		writeLogLine("Continuing recalculation in a second...\n");
		$url = preg_replace('/fromid=[^&]*/s', ($fid = 'fromid=' . $item['id']), $_SERVER['REQUEST_URI']);
		if ($url === $_SERVER['REQUEST_URI']) {
			$url .= (false === strpos($url, '?')? '?' : '&') . $fid;
		}
		echo '<meta http-equiv="Refresh" content="1; URL=' . htmlspecialchars($url) . '"/>';
		exit();
	}
}
$t1 = microtime(true);
writeLogLine(sprintf("Finished. Took %.2f s\n", ($t1 - $t0)));

if (isCgi()) {
	$url = 'index.php?to=' . urlencode(@$_GET['to']? $_GET['to'] : "") . '&back=' . urlencode($back);
	if ($hasError) {
		writeLogLine('<a href="' . $url . '">Back to statistics</a>', true);
	} else {
		writeLogLine('<meta http-equiv="refresh" content="1; url=' . $url . '"/>', true);
	}
}
