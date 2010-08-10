<?php
define("USE_GZIP", 1);
require_once "overall.php";

$url = preg_replace('/\?.*/s', '', $_SERVER['REQUEST_URI']);
setSetting("index_url", ($_SERVER['SERVER_PORT'] == 443? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $url);

list ($to, $back, $period) = parseToBackPeriod($_GET);

$data = generateTableData($to, getSetting("cols", 30), $period); 
$html = generateHtmlTableFromData($data);

$SELECT_PERIODS = getPeriods();
$name = getSetting("instance");

template(
	"index", 
	array(
		"title" => ($name? $name . ": " : "") . $SELECT_PERIODS[$period] . " statistics for " . date("Y-m-d", trunkTime($to) - 1),
		"to" => $to,
		"htmlTable" => $html
	)
);
