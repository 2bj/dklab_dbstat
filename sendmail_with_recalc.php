<?php
//
// Command-line usage:
//   php sendmail_with_recalc.php ['reName']
//

chdir(dirname(__FILE__));

$re = @$_SERVER['argv'][1]? $_SERVER['argv'][1] : null;
$time = time();

echo "Recalculating previous day values...\n";
//system("php recalc.php");

echo "Sending daily report...\n";
system("php sendmail.php day " . escapeshellarg($re));

$sentMonthly = false;
if (date('w', $time) == 1) { 
	// Monday morning: send weekly & monthly report
	echo "Sending weekly report...\n";
	system("php sendmail.php week " . escapeshellarg($re));
	echo "Sending monthly report...\n";
	system("php sendmail.php month " . escapeshellarg($re));
	$sentMonthly = true;
}

if (date('d', $time) == 1 && !$sentMonthly) {
	echo "Sending monthly report...\n";
	system("php sendmail.php month " . escapeshellarg($re));
}

if (date('d', $time) == 1) {
	echo "Sending quarterly report...\n";
	system("php sendmail.php quarter " . escapeshellarg($re));
}
