<?
$access = true;
require ("stats-path.php");
require ($path."options/options.php");
require ($path."lib/connect.php");
require ($path."lib/functions.php");
$flag = $path."flags/cron.htm";
$myfile = fopen($flag, "r+");
$text = fread($myfile, filesize($flag));	
$text = 'Processed '.date('Y-m-d H:i:s')."\r\n".$text;
rewind($myfile);
fwrite($myfile, $text);
fclose($myfile);
rewrite_stats();
if ($options['workWithNetwork']) rewrite_stats_network();
?>