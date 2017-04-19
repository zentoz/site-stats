<? 
if(!$access) die('Direct access not permitted');

$link = mysqli_connect($options['host'],$options['user'],$options['pass']);
mysqli_select_db($link,$options['db_name']);
mysqli_query($link,"SET NAMES utf8");

if(!empty($statsView)){
	if (!empty($_POST['username']) && !empty($_POST['password'])){	
		if($options['login']==$_POST['username'] && $options['loginpass']==$_POST['password']){
			$_SESSION['statsUser'] = $username;
			setcookie('stats',$_POST['username'],time()+60*60*24*3,"/");
			header("Location: ".$_SERVER['REQUEST_URI']);
		}
	}
	if(!empty($_SESSION['statsUser']) || isset($_COOKIE['stats'])){
		$logged = TRUE;
		if(isset($_COOKIE['stats'])) $_SESSION['statsUser']['username'] = $_COOKIE['stats'];
	} else $logged = FALSE;
}
?>