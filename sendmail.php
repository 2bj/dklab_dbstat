<?php
//
// Command-line usage:
//   php sendmail.php {period}

define("NO_AUTH", 1);
require_once "overall.php";

list ($to, $back, $period) = parseToBackPeriod($_GET);
if (isset($_SERVER['argv'][1])) $period = $_SERVER['argv'][1];
$to = trunkTime($to); // mail is always sent for WHOLE periods

$emails = getSetting('emails');
if (!$emails) die("Please specify E-mail at Settings page");

$data = generateTableData($to, $back, $period); 
$html = generateHtmlTableFromData($data);
$firstCaption = current($data['captions']);
$SELECT_PERIODS = getPeriods();

$name = getSetting("instance");
$replyto = getSetting("replyto");
$url = getSetting("index_url");

ob_start();
template(
	"mail", 
	array(
		"title" => ($name? $name . ": " : "") . $SELECT_PERIODS[$period] . " stats: " . preg_replace('/\s+/s', ' ', $firstCaption['caption']) . " [" . date("Y-m-d", $firstCaption['to']) . "]",
		"to" => $emails,
		"replyto" => ($replyto? $replyto : "no-reply@example.com"),
		"url" => $url . "?to=" . date("Y-m-d", $to) . "&period=" . $period,
		"htmlTable" => $html
	),
	true, true
);
$mail = ob_get_clean();
$mail = preg_replace('{(?=<tr)|(?<=/tr>)}s', "\n", $mail);

Mail_Simple::mail($mail);
