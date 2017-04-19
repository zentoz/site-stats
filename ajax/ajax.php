<? 
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

ini_set('max_execution_time', 600);

$access = TRUE;
session_start();
require_once ("../options/options.php");
require_once ("../lib/connect.php");
require_once ("../lib/functions.php");

if(isset($_POST['archive'])){
	$info = stats_archive();
	exit(json_encode($info));
}

$buildStatistics = new Statistics;

if(isset($_POST['mailing'])){
	require($options['path']."/cccms/builder/lib/class.builder.php");
	$builder = new Builder;
	$info['result'] = $builder -> mailing($_POST['mailing']);
	exit(json_encode($info));
}
if(isset($_POST['country'])){
	$info['result'] = $buildStatistics -> stats_display_country($_POST['country']);
	exit(json_encode($info));
}
if(isset($_POST['platform'])){
	$info['result'] = $buildStatistics -> stats_display_platform($_POST['platform']);
	exit(json_encode($info));
}
if(isset($_POST['process'])){
	$info['result'] = $buildStatistics -> stats_details($_POST);
	exit(json_encode($info));
}
if(isset($_POST['landing'])){
	$errors = $_POST['errors']?true:false;
	$info['result'] = $buildStatistics -> stats_domain_details('landing',$_POST['landing'],$errors);
	exit(json_encode($info));
}
if(isset($_POST['keyword'])){
	$errors = $_POST['errors']?true:false;
	$info['result'] = $buildStatistics -> stats_domain_details('keyword',$_POST['keyword'],$errors);
	exit(json_encode($info));
}
if(isset($_POST['refs'])){
	$errors = $_POST['errors']?true:false;
	$info['result'] = $buildStatistics -> stats_domain_details('refs',$_POST['refs'],$errors);
	exit(json_encode($info));
}
if(isset($_POST['usernames'])){
	$info['result'] = $buildStatistics -> stats_domain_details('usernames',$_POST['usernames']);
	exit(json_encode($info));
}
if(isset($_POST['statsError'])){
	$info['result'] = $buildStatistics -> stats_404error();
	exit(json_encode($info));
}
if(isset($_POST['trade-add'])){
	$info['result'] = trade_add($_POST['trade-add'],$_POST['anchor'],$_POST['url']);
	exit(json_encode($info));
}
if(isset($_POST['trade-edit'])){
	$info['result'] = trade_edit($_POST['trade-edit'],$_POST['anchor'],$_POST['url']);
	exit(json_encode($info));
}
if(isset($_POST['network-build'])){
	$info['result'] = $buildStatistics -> network_build();
	exit(json_encode($info));
}
if(isset($_POST['network-upload'])){
	$info['result'] = $buildStatistics -> network_upload($_POST['id']);
	exit(json_encode($info));
}
if(isset($_POST['guardian-upload'])){
	$info['result'] = $buildStatistics -> guardian_upload($_POST['id']);
	exit(json_encode($info));
}


if(isset($_POST['build-stats'])){	
	$buildStatistics -> dateStart = $_POST['dateStart'];
	$buildStatistics -> dateEnd = $_POST['dateEnd'];
	if ($_POST['build-stats'] == 'site') $info['result'] = $buildStatistics -> stats_date_range();
	if ($_POST['build-stats'] == 'network') $info['result'] = $buildStatistics -> network_build($_POST['dateStart'],$_POST['dateEnd']);
	exit(json_encode($info));
}

?>