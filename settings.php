<?php
define("USE_GZIP", 1);
require_once "overall.php";

$settings = array();
foreach ($DB->select("SELECT name, value FROM setting") as $row) {
	$settings[$row['name']] = $row['value'];
}

do {
	if (!empty($_POST['doSave'])) {
		$DB->beginTransaction();
		foreach ($_POST['settings'] as $name => $value) {
			try {
				if ($DB->selectCell("SELECT 1 FROM setting WHERE name=?", $name)) {
					$DB->update("UPDATE setting SET value=? WHERE name=?", $value, $name);
				} else {
					$DB->update("INSERT INTO setting(name, value) VALUES(?, ?)", $name, $value);
				}
			} catch (PDOException $e) {
				addMessage("$name: " . $e->getMessage());
				break(2);
			}
		}
		$DB->commit();
		selfRedirect("Data is saved.");
	} else {
		$_POST['settings'] = array();
		foreach ($DB->select("SELECT name, value FROM setting") as $row) {
			$_POST['settings'][$row['name']] = $row['value'];
		}
	}
} while (false);

template(
	"settings", 
	array(
		"title" => "Settings",
	)
);

