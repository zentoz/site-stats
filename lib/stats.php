<? 
if(!$access) die('Direct access not permitted');
require_once ("lib/functions.php");
build_path();
$buildStatistics = new Statistics;
echo $buildStatistics -> build_stats();

?>