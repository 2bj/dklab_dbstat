<?php 
if (!defined("PATH_SEPARATOR"))
  define("PATH_SEPARATOR", getenv("COMSPEC")? ";" : ":");
ini_set("include_path", dirname(__FILE__).PATH_SEPARATOR.ini_get("include_path"));
