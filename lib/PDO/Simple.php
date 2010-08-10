<?php

/**
 * Simplifies PDO usage.
 */
class PDO_Simple extends PDO
{
	public function __construct($dsn)
	{
		$user = $pass = null;
		if (preg_match('/\buser=([^ ;]+)/s', $dsn, $m)) {
			$user = $m[1];
			$dsn = str_replace($m[0], '', $dsn);
		}
		if (preg_match('/\bpassword=([^ ;]*)/s', $dsn, $m)) {
			$pass = $m[1];
			$dsn = str_replace($m[0], '', $dsn);
		}
		$dsn = rtrim(preg_replace('/\s*;[;\s]*/', ';', $dsn), ';');
		parent::__construct($dsn, $user, $pass);
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function selectCell()
	{
		$args = func_get_args();
		$sql = array_shift($args);
		$rs = $this->prepare($sql);
		$rs->execute($args);
		return $rs->fetchColumn(0);
	}

	public function selectRow()
	{
		$args = func_get_args();
		$sql = array_shift($args);
		$rs = $this->prepare($sql);
		$rs->execute($args);
		return $rs->fetch(PDO::FETCH_ASSOC);
	}

	public function select()
	{
		$args = func_get_args();
		$sql = array_shift($args);
		$rs = $this->prepare($sql);
		$rs->execute($args);
		return $rs->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function update()
	{
		$args = func_get_args();
		$sql = array_shift($args);
		$rs = $this->prepare($sql);
		$result = $rs->execute($args);
		return $result;
	}
	
	public function getSeq()
	{
		$seq = $this->selectCell("SELECT id FROM seq");
		$this->update("UPDATE seq SET id = id + 1");
		return $seq;
	}
}
