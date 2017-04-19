<?
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$access = TRUE;
$statsView = TRUE;
session_start();
require_once ("options/options.php");
require_once ("lib/connect.php");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<script src="https://code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
<script src="ajax/header.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="lib/datepick/jquery.datepick.css"> 
<script type="text/javascript" src="lib/datepick/jquery.plugin.js"></script> 
<script type="text/javascript" src="lib/datepick/jquery.datepick.js"></script>
<link href="style/style.css" rel="stylesheet" type="text/css">

<title>Statistics</title>
</head>
<body>

<div class="wrapper">
	<? if(!$logged):?>
        <h2>Please login</h2>
        
        <div class="logForm calenderForm">
            <form action="" method="post">
                <table><tr><td>
                <input type="text" name="username">
                <input type="password" name="password">
                <input type="submit" value="Enter">
                </td></tr></table>
            </form>
        </div>
    <? else:?>
        <h2>
            <a href="./">Main page</a> 
            <a href="http://<? echo $_SERVER['HTTP_HOST']; ?>" target="_blank">Site</a> 
            <? if ($options['workWithNetwork'] === true) echo '<a data-network="network-build">Network</a> <a data-mailing="mailing">Mailing</a>'; ?>
            <a class="archive">Archive data</a> 
            Hi, <? echo $_SESSION['statsUser']['username']; ?>
        </h2>
        <div class="calenderForm">
            <form>
                <input class="calender" type="text" name="dateStart" value="<? echo date('Y-m-d') ?>">
                <input class="calender" type="text" name="dateEnd" value="<? echo date('Y-m-d') ?>">
                <input type="button" name="rebuild" value="Rebuild">
            </form>
            <a id="statsToday" class="preSet">Today</a> 
            <a id="statsYesterday" class="preSet">Yesterday</a>
            <a id="statsMonth" class="preSet">This month</a>
            <a id="statsYear" class="preSet">This year</a>
            <a id="statsError" class="statsError">Error logs</a>
        </div>
        <? include "lib/stats.php"; ?>
    <? endif; ?>
</div>
</body>
</html>

<? mysqli_close($link); ?>