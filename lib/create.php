<?
error_reporting (E_ALL);
error_reporting (0);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

$access = TRUE;
session_start();
require_once ("../options/options.php");
require_once ("connect.php");
require_once ("functions.php");

create_tables();

?>