<?php
require_once "overall.php";

list ($to, $back, ) = parseToBackPeriod($_GET);
$period = @$_GET['period'];

$csvs = array();
foreach (getPeriods() as $p => $name) {
    if (!strlen($period) || $p == $period) {
        $data = generateTableDataFromGetArgs($to, PREVIEW_TABLES_COLS, $p);
        $csvs[$p] = generateCsvTableFromData($data);
    }
}
if (!$csvs) die("No such period: $period");

$tmpDir = tempnam('non-existed-' . md5(time()), 'dbstat-');
@unlink($tmpDir);
@mkdir($tmpDir) or die("Cannot create '$tmpDir': " . error_get_last_msg());
foreach ($csvs as $p => $data) {
    file_put_contents("$tmpDir/$p.csv", $data);
}
chdir($tmpDir) or die("Cannot chdir to '$tmpDir': " . error_get_last_msg());
$destFile = "$tmpDir/dbstat_" . date("Y-m-d_H-i") . ".zip";
$cmd = "zip " . escapeshellarg($destFile) . " *.csv";
exec($cmd, $out, $ret);
if (!$ret) {
    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=\"" . basename($destFile) . "\"");
    echo file_get_contents($destFile);
}
foreach (glob("$tmpDir/*") as $f) {
    unlink($f);
}
rmdir($tmpDir);

if ($ret) die("Cannot execute \"$cmd\": error code $ret");
