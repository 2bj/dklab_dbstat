<?php
define("USE_GZIP", 1);
require_once "overall.php";

//$DB->update("DELETE FROM dsn");
//var_dump($DB->selectCell("select * from seq"));

do {
	if (!empty($_POST['doSave'])) {
		$DB->beginTransaction();
		foreach ($_POST['dsns'] as $id => $dsn) {
			try {
				if (!empty($dsn['delete'])) {
					$count = $DB->selectCell("SELECT COUNT(1) FROM item WHERE dsn_id=?", $dsn['id']);
					if ($count) {
						throw new PDOException("Cannot delete the row: $count item(s) depend on it");
					} else {
						$DB->update("DELETE FROM dsn WHERE id=?", $dsn['id']);
					}
				} else {
					if (strlen(trim($dsn['name'])) && strlen(trim($dsn['value']))) {
						try {
							new PDO_Simple($dsn['value']);
						} catch (PDOException $e) {
							throw new PDOException("Connection validation error: " . $e->getMessage());
						}
						if (!empty($dsn['id'])) {
							$DB->update("UPDATE dsn SET name=?, value=? WHERE id=?", $dsn['name'], $dsn['value'], $dsn['id']);
						} else {
							$DB->update("INSERT INTO dsn(id, name, value) VALUES(?, ?, ?)", $DB->getSeq(), $dsn['name'], $dsn['value']);
						}
					} else {
						if (!empty($dsn['id'])) throw new PDOException("Field(s) cannot be empty here");
					}                           
				}
			} catch (PDOException $e) {
				addMessage("ID=" . ($id? $id : "NEW") . ": " . $e->getMessage());
				break(2);
			}
		}
		$DB->commit();
		selfRedirect("Data is saved.");
	} else {
		$_POST['dsns'] = array();
		foreach ($DB->select("SELECT id, name, value FROM dsn ORDER BY id") as $dsn) {
			$_POST['dsns'][$dsn['id']] = $dsn;
		}
	}
} while (false);

template(
	"dsns", 
	array(
		"title" => "Databases",
	)
);
