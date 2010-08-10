<?php
if (@is_file($f = dirname(__FILE__) . "/../dbstat_config.php")) {
	require_once($f);
}

@define("DB_DSN", "sqlite:dbstat.sqlite");
@define("MAX_RECALC_CGI_TIME", 30);
