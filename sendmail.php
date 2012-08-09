<?php
//
// Command-line usage:
//   php sendmail.php [period] ['reName']
//

define("NO_AUTH", 1);
require_once "overall.php";

list ($to, $back, $period) = parseToBackPeriod($_GET);
if (isset($_SERVER['argv'][1])) {
	$period = $_SERVER['argv'][1];
}
$onlyReName = null;
if (isset($_SERVER['argv'][2])) {
	$onlyReName  = $_SERVER['argv'][2];
}
$to = trunkTime($to); // mail is always sent for WHOLE periods

$emails = trim(getSetting('emails'));
if ($period == 'month' && trim($emailsMonth = getSetting('emails_month'))) {
    $emails .= ($emails? ", " : "") . $emailsMonth;
}
if (!$emails) die("Please specify E-mails to send stats at Settings page!\n");

// Generate the table data.
$data = generateTableData($to, $back, $period, null, null, $onlyReName); 

// Remove archived rows.
foreach ($data['groups'] as $gName => $gRows) {
    foreach ($gRows as $rName => $rInfo) {
        if ($rInfo['archived']) unset($data['groups'][$gName][$rName]);
    }
    if (!$data['groups'][$gName]) unset($data['groups'][$gName]);
}

// Generate HTML.
$html = generateHtmlTableFromData($data);

$firstCaption = current($data['captions']);
$SELECT_PERIODS = getPeriods();

$name = getSetting("instance");
$replyTo = trim(getSetting("replyto", ""));
$emailFrom = trim(getSetting("email_from"), "");
$emailNoReply = "no-reply@example.com";
$url = getSetting("index_url");

foreach (preg_split('/\s*,\s*/s', $emails) as $email) {
    $email = trim($email);
    if (!$email) continue;
	ob_start();
	template(
		"mail",
		array(
			"title" => ($name? $name . ": " : "") . $SELECT_PERIODS[$period] . " stats: " . preg_replace('/\s+/s', ' ', $firstCaption['caption']) . " [" . date("Y-m-d", $firstCaption['to']) . "]",
			"to" => $email,
			"replyto" => ($replyTo? $replyTo : ($from? $from : $emailNoReply)),
			"from" => ($emailFrom? $emailFrom : ($replyTo? $replyTo : $emailNoReply)),
			"url" => $url . "?to=" . date("Y-m-d", $to) . "&period=" . $period,
			"htmlTable" => $html
		),
		true, true
	);
	$mail = ob_get_clean();
	$mail = preg_replace('{(?=<tr)|(?<=/tr>)}s', "\n", $mail);
	Mail_Simple::mail($mail);
}
