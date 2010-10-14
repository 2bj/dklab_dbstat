<?php
// Parameters (GET or POST):
// - apikey (required):   API restriction key (must match specified in settings)
// - item_ids (required): list of item IDs or tag names separated by "|"
// - data_names:          list of data names separated by "|" (may use LIKE-syntax)
// - periods:             list of periods to return (day, week, month, year, total)
// - to:                  maximum date to return the data
// - back:                how much columns to return
// Result is JSON-encoded.
// In case of any error its text is returned plainly.
require_once "overall.php";

$params = $_POST + $_GET;

try {
	$apikey = @$params['apikey'];
	$allowedKeys = preg_split('/\s+/s', trim(getSetting("apikeys", "")));
	if (!$apikey || !in_array($apikey, $allowedKeys)) {
		throw new Exception('Invalid "apikey" parameter');
	}
	
	$itemIds = trim(@$params['item_ids']);
	if (!$itemIds) {
		throw new Exception('Parameter "item_ids" must be specified and not empty');
	}

	$dataNames = trim(@$params['data_names']);
	$dataNames = $dataNames? explode(TAGS_SEP, $dataNames) : array();

	$onlyPeriods = trim(@$params['periods']);
	$onlyPeriods = $onlyPeriods? explode(TAGS_SEP, $onlyPeriods) : array();
	
	list ($to, $back) = parseToBackPeriod($params);
		
	$result = array();
	foreach (getPeriods() as $period => $periodName) {
		if ($onlyPeriods && !in_array($period, $onlyPeriods)) continue;
		$result[$period] = generateTableData($to, $back, $period, $itemIds, $dataNames);
	}

//	print_r($result);
	header("Content-Type: application/json");
	echo json_encode($result);

} catch (Exception $e) {
	header("Content-Type: text/plain");
	echo $e->getMessage();
}
