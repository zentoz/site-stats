<?
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

if(!$access) die('Direct access not permitted');

@session_start();
require_once ($_SERVER["DOCUMENT_ROOT"]."/stats/options/options.php");
require_once ($_SERVER["DOCUMENT_ROOT"].$options['folder']."lib/connect.php");
require_once ($_SERVER["DOCUMENT_ROOT"].$options['folder']."lib/functions.php");

// bot or not
if (preg_match('/google|bot|spider|crawl|curl|^$/i', $_SERVER['HTTP_USER_AGENT'])) $bot = TRUE;
else $bot = FALSE;
if (!isset($statsSkip)) write_stats();

// process stats
$gererateNew = date('Y-m-d H:i:s', strtotime('-5 minutes'));
$flag = $_SERVER["DOCUMENT_ROOT"].$options['folder']."flags/flag-stats.htm";
$myfile = fopen($flag, "r");
$gererateLast = fread($myfile, filesize($flag));
fclose($myfile);
if ($gererateNew>$gererateLast || !$myfile){
	$myfile = fopen($flag, "w");
	fwrite($myfile, date('Y-m-d H:i:s'));
	fclose($myfile);
	exec("/usr/bin/php ".$_SERVER["DOCUMENT_ROOT"].$options['folder']."cron.php",$exec_output,$return_code);
}

// process toplists
if ($options['workWithTrades']){
	$gererateNew = date('Y-m-d H:i:s', strtotime('-1 hour'));
	$flag = $_SERVER["DOCUMENT_ROOT"].$options['folder']."flags/flag-toplist.htm";
	$myfile = fopen($flag, "r");
	$gererateLast = fread($myfile, filesize($flag));
	fclose($myfile);
	if ($gererateNew>$gererateLast || !$myfile){
		$myfile = fopen($flag, "w");
		fwrite($myfile, date('Y-m-d H:i:s'));
		fclose($myfile);
		toplist_build();
	}
}

mysqli_close($link);
?>