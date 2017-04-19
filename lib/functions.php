<? 

if(!$access) die('Direct access not permitted');

$searchEngines = array(
				 'google'   => 'q=',
				 'yandex'   => 'text=',
				 'dmoz'     => 'q=',
				 'aol'      => 'q=',
				 'ask'      => 'q=',
				 'bing'     => 'q=',
				 'hotbot'   => 'q=',
				 'teoma'    => 'q=',
				 'wow'      => 'q=',
				 'yahoo'    => 'p=',
				 'altavista'=> 'p=',
				 'duckduckgo'    => 'q=',
				 'searchlock'    => 'q=',
				 'searchincognito'    => 'q=',
				 'lycos'    => 'query=',
				 'kanoodle' => 'query='
);

class Statistics {
	
	var $dateStart;
	var $dateEnd;
		
	function __construct(){
		global $options;
		create_tables();
		if ($options['workWithTrades']) create_tables_trade();
	}
	
	function stats_details($array){
		global $link,$options;
		$output = array(); $process = array(); $where = array(); $group = array();
		
		if (isset($array['substat'])) $sub = true;
		
		$processes = explode(':::',$array['process']);
		foreach ($processes as $key=>$value){
			$data = explode('::',$value);
			//$process[$data[0]] = $data[1];
			$process[$key]['process'] = $data[0];
			$process[$key]['val'] = $data[1];
		}
		
		foreach ($process as $key => $val){
			if ($val['process'] == 'landing'){
				$process[$key]['column'] = 'url';
				//if ($sub) 
				if (!$sub){
					if ($val['val']=="NO-REF") $where[] = "ref = '' AND";
					elseif ($val['val']=="registered") $where[] = "user NOT LIKE '%guest%' AND";
					elseif ($val['val']=="total") $where[] = "ref NOT LIKE '%".$_SERVER['SERVER_NAME']."%' AND";
					else $where[] = "ref LIKE '%".$val['val']."%' AND";
				} else if ($key==0) $where[] = "ref='".$val['val']."' AND";
			}
			if ($val['process'] == 'refs'){
				$process[$key]['column'] = 'ref';
				if (!$sub) $where[] = $process[$key]['column']." LIKE '%".$val['val']."%' AND";
				else if ($key==0) $where[] = "url='".$val['val']."' AND ref LIKE '%".$process[($key+1)]['val']."%' AND";
			}
			if ($val['process'] == 'keyword'){
				$process[$key]['column'] = 'keyword';
				$where[] = $process[$key]['column']." LIKE '%".$val['val']."%' AND";
			}
			if ($val['process'] == 'usernames'){
				$process[$key]['column'] = 'user';
				$where[] = $process[$key]['column']." NOT LIKE '%guest%' AND";
			}
			if ($val['process'] == 'platform'){
				$result = $this->stats_display_platform($val['val']);
				return $result;
			}
			if ($val['process'] == 'country'){
				$result = $this->stats_display_country($val['val']);
				return $result;
			}			
		}
		if (isset($_POST['errors'])) $where[] = "code NOT IN (403,404) AND";
		$where = implode(' ',$where);
		$column = $process[0]['column'];
		
		$dateStart = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m",strtotime($_SESSION['dateStart']))  , date("d",strtotime($_SESSION['dateStart'])), date("Y",strtotime($_SESSION['dateStart']))));
		$dateEnd = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m",strtotime($_SESSION['dateEnd']))  , date("d",strtotime($_SESSION['dateEnd']))+1, date("Y",strtotime($_SESSION['dateEnd']))));	
		
		$q = "SELECT ".$options['table'].".date FROM ".$options['table']." ORDER BY ".$options['table'].".date LIMIT 1";	
		$res = $link->query($q)->fetch_assoc();
		$dateAfterArchive = $res['date'];
		
		if ($dateStart>$dateAfterArchive) $q = "SELECT COUNT(".$column.") as amount,".$column.",code FROM ".$options['table']." WHERE $where ".$options['table'].".date BETWEEN '".$dateStart."' and '".$dateEnd."' GROUP BY ".$column." ORDER BY ".$column;		
		elseif ($dateStart<$dateAfterArchive && $dateEnd>$dateAfterArchive) $q = "SELECT COUNT(".$column.") as amount,".$column.",code FROM ".$options['table']."_history WHERE $where ".$options['table']."_history.date BETWEEN '".$dateStart."' and '".$dateEnd."' UNION ALL SELECT COUNT(".$column.") as amount,".$column.",code FROM ".$options['table']." WHERE $where ".$options['table'].".date BETWEEN '".$dateStart."' and '".$dateEnd."' GROUP BY ".$column." ORDER BY ".$column;		
		elseif ($dateEnd<$dateAfterArchive) $q = "SELECT COUNT(".$column.") as amount,".$column.",code FROM ".$options['table']."_history WHERE $where ".$options['table']."_history.date BETWEEN '".$dateStart."' and '".$dateEnd."' GROUP BY ".$column." ORDER BY ".$column;
		//echo $q;

		$result = $link->query($q);
		while($res = $result->fetch_assoc()){
			$output[$res[$column]]['amount'] = $res['amount'];
			$output[$res[$column]]['code'] = $res['code'];
		}
		arsort($output);
		$print = array();
		foreach ($output as $key => $value){
			if ($process[0]['process']=='keyword' || $process[0]['process']=='usernames'){
				$print[] = '<tr><td>'.$key.'</td><td>'.$value['amount'].'</td></tr>';
			} else if ($process[0]['process']=='landing'){
				$print[] = '<tr id="'.$key.'"><td class="key"><a href="'.$key.'" target="_blank">'.$key.'</a></td><td>'.$value['amount'].'</td><td>'.$value['code'].'</td><td><a data-process="refs">Refs</a></td></tr>';
			} else if ($process[0]['process']=='refs'){
				$print[] = '<tr id="'.$key.'"><td class="key"><a href="'.$key.'" target="_blank">'.$key.'</a></td><td>'.$value['amount'].'</td><td><a data-process="landing">Landings</a></td></tr>';
			}
		}
		//print_r($print);
		
		$result = '<table class="statistics details" data-processes="'.$array['process'].'"><thead><tr><th>'.ucwords($process[0]['process']).' : '.$process[0]['val'].'</th><th>Amount</th></tr></thead>'.implode('',$print).'</table>';
		
		return $result;
	}
	
	function stats_display_country($domain){
		arsort($_SESSION['stats'][$domain]['country']);
		$countries = '<table class="statistics"><thead><tr><th>Country</th><th>Amount</th></tr></thead>';
		foreach ($_SESSION['stats'][$domain]['country'] as $key => $value){
			$countries .= '<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
		}
		$countries .= '</table>';
		return $countries;
	}
	
	function stats_display_platform($domain){
		arsort($_SESSION['stats'][$domain]['platform']);
		$countries = '<table class="statistics"><thead><tr><th>Platform</th><th>Amount</th></tr></thead>';
		foreach ($_SESSION['stats'][$domain]['platform'] as $key => $value){
			$countries .= '<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
		}
		$countries .= '</table>';
		return $countries;
	}
			
	function stats_date_range(){
		$this -> stats_to_session($this -> dateStart,$this -> dateEnd);
		$domains = $this -> stats_display();
		return $domains;
	}
		
	function build_stats(){
		$this -> stats_to_session();
		$domains = $this -> stats_display();
		return $domains;
	}
	
	function stats_display(){
		global $link,$searchEngines,$options;
		$registered = ''; $seo = ''; $domains1 = ''; $domains2 = ''; $total = ''; $noRef = ''; $innerTraffic = '';
		arsort($_SESSION['stats']);
		$build = "<table class='statistics'><thead><tr><th>Referer <a data-trade='trade-add-new'>Add trade</a></th><th>".$_SESSION['dateStart']." - ".$_SESSION['dateEnd']." / <input type='checkbox' id='errors'><label for='errors'> Errors in details</label></th><th>Unique</th><th>Raw</th></tr></thead>";
		foreach ($_SESSION['stats'] as $key => $value){
			if (array_key_exists($key, $searchEngines)) $seo .= '<tr id="'.$key.'"><td><strong>'.strtolower($key).'</strong></td><td><a data-process="landing">Landings</a> <a data-process="platform">Platforms</a> <a data-process="country">Countries</a> <a data-process="refs">Reffering urls</a> <a data-process="keyword">Keywords</a></td><td>'.$value['stats']['unique'].'</td><td>'.$value['stats']['raw'].'</td></tr>';
			else if ($key=='registered') $registered .= '<tr id="'.$key.'"><td>'.strtoupper($key).'</td><td><a data-process="landing">Landings</a> <a data-process="platform">Platforms</a> <a data-process="country">Countries</a> <a data-process="usernames">Usernames</a></td><td>'.$value['stats']['unique'].'</td><td>'.$value['stats']['raw'].'</td></tr>';
			else if ($key=='NO-REF') $noRef .= '<tr id="'.$key.'"><td>'.strtoupper($key).'</td><td><a data-process="landing">Landings</a> <a data-process="platform">Platforms</a> <a data-process="country">Countries</a></td><td>'.$value['stats']['unique'].'</td><td>'.$value['stats']['raw'].'</td></tr>';
			else if ($key==domain_host()) $innerTraffic .= '<tr id="'.$key.'"><td>INNER TRAFFIC</td><td><a data-process="landing">Landings</a> <a data-process="platform">Platforms</a> <a data-process="country">Countries</a> <a data-process="refs">Reffering urls</a></td><td>'.$value['stats']['unique'].'</td><td>'.$value['stats']['raw'].'</td></tr>';
			else if ($key=='total') $total .= '<tfoot><tr id="'.$key.'"><td><strong>'.strtoupper($key).'</strong></td><td><a data-process="landing">Landings</a> <a data-process="platform">Platforms</a> <a data-process="country">Countries</a></td><td>'.$value['stats']['unique'].'</td><td>'.$value['stats']['raw'].'</td></tr></tfoot>';
			else {				
				if ($options['workWithTrades']){
					$trade = false;
					$result = mysqli_query($link,"SELECT * FROM ".$options['table']."_trade WHERE domain LIKE '%$key%'");
					if (mysqli_num_rows($result)>0) $trade = true;
					$res = mysqli_fetch_assoc($result);
					if ($trade) $tradeButton = ' <a data-trade="trade-remove">Remove</a> <a data-trade="trade-edit" data-anchor="'.$res['anchor'].'" data-url="'.$res['url'].'">Edit</a>';
					else $tradeButton = ' <a data-trade="trade-add">Add to trades</a>';
					$domains = '<tr id="'.$key.'"><td><a href="http://'.$key.'" target="_blank">'.$key.'</a></td><td><a data-process="landing">Landings</a> <a data-process="platform">Platforms</a> <a data-process="country">Countries</a> <a data-process="refs">Reffering urls</a>'.$tradeButton.'</td><td>'.$value['stats']['unique'].'</td><td>'.$value['stats']['raw'].'</td></tr>';
					if ($trade) $domains1 .= $domains;
					else $domains2 .= $domains;
				} else {
					$domains1 .= '<tr id="'.$key.'"><td><a href="http://'.$key.'" target="_blank">'.$key.'</a></td><td><a data-process="landing">Landings</a> <a data-process="platform">Platforms</a> <a data-process="country">Countries</a> <a data-process="refs">Reffering urls</a></td><td>'.$value['stats']['unique'].'</td><td>'.$value['stats']['raw'].'</td></tr>';
					$domains2 .= '';
				}
			}
		}
		$build .= $registered.$domains1.$domains2.$seo.$innerTraffic.$noRef.$total;
		$build .= "</table>";
		return $build;
	}
	
	function stats_to_session($dateStart='',$dateEnd=''){
		global $link,$options;
		$dateCurrent = date('Y-m-d');
		
		if (empty($dateStart)) $dateStart = $dateCurrent;
		else $dateStart = date('Y-m-d', strtotime($dateStart));	
		if (empty($dateEnd)) $dateEnd = $dateCurrent;
		else $dateEnd = date('Y-m-d', strtotime($dateEnd));
	
		if (!isset($_SESSION)) session_start();
		unset($_SESSION['stats']);
		if (empty($_SESSION['stats'])){
			$_SESSION['stats'] = array();
			$q = "SELECT * FROM ".$options['table']."_per_date WHERE ".$options['table']."_per_date.date BETWEEN '".$dateStart."' and '".$dateEnd."' ORDER BY ".$options['table']."_per_date.unique DESC";
			$result = mysqli_query($link,$q);
			while($res=mysqli_fetch_assoc($result)){
				if (empty($_SESSION['stats'][$res['domain']]['stats']['unique'])) $_SESSION['stats'][$res['domain']]['stats']['unique'] = 0;
				$_SESSION['stats'][$res['domain']]['stats']['unique'] += $res['unique'];
				if (empty($_SESSION['stats'][$res['domain']]['stats']['raw'])) $_SESSION['stats'][$res['domain']]['stats']['raw'] = 0;
				$_SESSION['stats'][$res['domain']]['stats']['raw'] += $res['raw'];
				$platforms = explode("|",$res['platform']);
				if (count($platforms)>0){
					foreach($platforms as $key => $value){
						$platform = explode(":",$value);
						if (empty($_SESSION['stats'][$res['domain']]['platform'][$platform[0]])) $_SESSION['stats'][$res['domain']]['platform'][$platform[0]] = 0;
						$_SESSION['stats'][$res['domain']]['platform'][$platform[0]] += $platform[1];
					}
				}
				$countries = explode("|",$res['country']);
				if (count($countries)>0){
					foreach($countries as $key => $value){
						$country = explode(":",$value);
						if (empty($_SESSION['stats'][$res['domain']]['country'][$country[0]])) $_SESSION['stats'][$res['domain']]['country'][$country[0]] = 0;
						$_SESSION['stats'][$res['domain']]['country'][$country[0]] += $country[1];
					}
				}
			}
			$_SESSION['dateStart'] = $dateStart;
			$_SESSION['dateEnd'] = $dateEnd;
		}
	}
	
	function network_build($dateStart='',$dateEnd=''){
		global $link,$options;
		$dateCurrent = date('Y-m-d');
		$output1 = ''; $output2 = '';
		
		if (empty($dateStart)) $dateStart = $dateCurrent;
		else $dateStart = date('Y-m-d', strtotime($dateStart));	
		if (empty($dateEnd)) $dateEnd = $dateCurrent;
		else $dateEnd = date('Y-m-d', strtotime($dateEnd));
		
		$output = "<table class='statistics netsites'><thead><tr><th>Domain</th><th>Path</th><th>DB</th><th>Upload script <a data-network='network-upload-all'>Upload all sites</a></th><th>Unique</th><th>Raw</th></tr></thead><tr><td colspan=6>Pay Sites</td></tr>";
		$folder = $_SERVER["DOCUMENT_ROOT"].$options['folder'].'network';
		$sites = scandir($folder);
		foreach ($sites as $site){
			if ($site!='.' && $site!='..' && $site!=''){
				$myfile = fopen($folder.'/'.$site, "r");
				while (($opts=fgets($myfile)) !== false) {
					$opts = str_replace("\r\n","",$opts);
					$option = explode('=>',$opts);
					$_SESSION['stats']['network'][$site][$option[0]] = $option[1];					
				}
				fclose($myfile);
			}
		}
		unset($_SESSION['stats']['network_unique'],$_SESSION['stats']['network_raw']);
		foreach ($_SESSION['stats']['network'] as $domain => $site){
			
			$q = "SELECT SUM(".$options['table']."_network.unique) as uni,SUM(raw) as raw FROM ".$options['table']."_network WHERE domain='".$domain."' and date BETWEEN '".$dateStart."' and '".$dateEnd."'";
			$res=mysqli_fetch_assoc(mysqli_query($link,$q));
			$_SESSION['stats']['network'][$domain]['unique'] = $res['uni'];
			$_SESSION['stats']['network'][$domain]['raw'] = $res['raw'];
			
			if (empty($_SESSION['stats']['network_unique'])) $_SESSION['stats']['network_unique'] = 0;
			$_SESSION['stats']['network_unique'] += $res['uni'];
			if (empty($_SESSION['stats']['network_raw'])) $_SESSION['stats']['network_raw'] = 0;
			$_SESSION['stats']['network_raw'] += $res['raw'];
			if ($domain!=domain_host()){
				$upload = '<a data-network="network-upload">Upload files</a>';
				if ($domain!='xxxman.club') $upload .= ' <a data-network="guardian-upload">Guardian upload</a>';
			} else $upload = 'Not avalable for same domain';
			$siteOutput =  '<tr id="'.$domain.'"><td><a href="http://www.'.$domain.$site['folder'].'" target="_blank">'.$domain.'</a></td><td>'.$site['path'].'</td><td> '.$site['user'].' / '.$site['table'].'</td><td>'.$upload.'<td>'.fn($res['uni']).'</td><td>'.fn($res['raw']).'</td></td></tr>';
			if ($site['workWithTrades']=='true') $output1 .= $siteOutput;
			else $output2 .= $siteOutput;
		}
		$output .= $output2.'<tr><td colspan=6>Free Sites</td></tr>'.$output1.'<tfoot><tr><td colspan=4>Total</td><td>'.fn($_SESSION['stats']['network_unique']).'</td><td>'.fn($_SESSION['stats']['network_raw']).'</td></tr></tfoot></table>';
		return $output;
	}
	
	function network_upload($id){
		global $options;
		$folder = $_SERVER["DOCUMENT_ROOT"].$options['folder'];
		//$copyToFolder = $_SESSION['stats']['network'][$id]['ftpPath'].'/test/';
		$copyToFolder = $_SESSION['stats']['network'][$id]['ftpPath'].$_SESSION['stats']['network'][$id]['folder'];
		$ftp = ftp_connect($_SESSION['stats']['network'][$id]['ftpHost'],21,300);
		ftp_login($ftp,$_SESSION['stats']['network'][$id]['ftpUser'],$_SESSION['stats']['network'][$id]['ftpPass']);
		$this -> recurse_copy_ftp($folder,$copyToFolder,$ftp);
		
    	@ftp_mkdir($ftp,$copyToFolder.'flags');
		@ftp_chmod($ftp,0777,$copyToFolder.'flags');
    	@ftp_mkdir($ftp,$copyToFolder.'options');
		@ftp_chmod($ftp,0777,$copyToFolder.'options');
		$optionsFile = '<? if(!$access) die("Direct access not permitted");
		$options = array(
			';
		foreach ($_SESSION['stats']['network'][$id] as $key => $value){
			if ($value=='true' || $value=='false') $optionsFile .= '"'.$key.'"=>'.$value.',
			';
			else $optionsFile .= '"'.$key.'"=>"'.$value.'",
			';
		}
		$optionsFile .= ');
?>';
		$optionsTemp = $_SERVER["DOCUMENT_ROOT"].$options['folder'].'flags/options.txt';
		file_put_contents ($optionsTemp,$optionsFile);
    	ftp_put($ftp,$copyToFolder.'options/options.php',$optionsTemp, FTP_BINARY);
		return 'Uploaded';
	}

	function recurse_copy_ftp($src,$dst,$ftp) {
		$dir = opendir($src);
		@ftp_mkdir($ftp,$dst);
		@ftp_chmod($ftp,0777,$dst);
		$exist = ftp_nlist($ftp,$dst);
		while(false !== ( $file = readdir($dir)) ) { 
			if (($file!='.') && ($file!='..') && ($file!='options') && ($file!='flags') && ($file!='stats-path.php') && ($file!='network') && ($file!='tpl')){ 
				if (is_dir($src.'/'.$file)){ 
					$this -> recurse_copy_ftp($src.'/'.$file,$dst.'/'.$file,$ftp);
				} else {
					if (in_array($dst.'/'.$file,$exist)) ftp_delete($ftp, $dst.'/'.$file);
					ftp_put($ftp, $dst.'/'.$file, $src.'/'.$file, FTP_BINARY);
					@ftp_chmod($ftp,0666,$dst.'/'.$file);
				} 
			}
		}
		closedir($dir); 
	}
	
	function guardian_upload($id){
		global $options;
		$folder = $_SERVER["DOCUMENT_ROOT"].'/guardian';
		$dirs = scandir($folder);
		$copyToFolder = $_SESSION['stats']['network'][$id]['ftpPath'].'/guardian';
		$ftp = ftp_connect($_SESSION['stats']['network'][$id]['ftpHost'],21,300);
		ftp_login($ftp,$_SESSION['stats']['network'][$id]['ftpUser'],$_SESSION['stats']['network'][$id]['ftpPass']);
		@ftp_mkdir($ftp,$copyToFolder);
		@ftp_chmod($ftp,0777,$copyToFolder);
		
		$this -> recurse_copy_ftp($folder,$copyToFolder,$ftp);
		return 'Uploaded';
	}
	
	function stats_404error(){
		global $options;
		$error;
		unset ($_SESSION['stats']['errors']);
		$handle = fopen($options['errorLog'], "r");
		if ($handle) {
			while (($line = fgets($handle)) !== false) {
				$foundError = stripos($line,'File does not exist:');
				if ($foundError !== false) {
					$errorLine = substr($line,$foundError);
					
					$errorUrl = $this -> get_string_between($errorLine, 'File does not exist: ', ',');
					if (empty($_SESSION['stats']['errors'][$errorUrl])) $_SESSION['stats']['errors'][$errorUrl] = 0;
					$_SESSION['stats']['errors'][$errorUrl] += 1;
				}
			}
			fclose($handle);
		}
		arsort($_SESSION['stats']['errors']);
		//print_r ($_SESSION['stats']['errors']);
		$error = "<table class='statistics'><thead><tr><th>Error page</th><th>Amount</th></thead>";
		foreach ($_SESSION['stats']['errors'] as $key => $value){
			$error .= '<tr><td>'.$key.'</td><td>'.$value.'</td><td><a data-process-error="refs">Reffering urls</a></td></tr>';
		}
		$error .= "</table>";
		return $error;
	}
	
	function get_string_between($string, $start, $end){
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
	}

	function __destruct(){
		global $link;
		//mysqli_close($link);
	}
// end class
}

// process writen stats
$domains = array();
$step = 0;
$step2 = 0;
function rewrite_stats(){
	global $link,$options;
	global $domains;
	global $step;
	global $step2;
	
	$ips = array();
	$user = array();
	
	$q = "SELECT ".$options['table']."_per_date.date FROM ".$options['table']."_per_date ORDER BY ".$options['table']."_per_date.date DESC LIMIT 1";
	$result = mysqli_query($link,$q);
	if (mysqli_num_rows($result)>0) $dateLast = mysqli_fetch_assoc($result);	
	else { 
		$q = "SELECT ".$options['table'].".date FROM ".$options['table']." ORDER BY ".$options['table'].".date LIMIT 1";
		$result = mysqli_query($link,$q);
		$dateLast = mysqli_fetch_assoc($result);
	}
	if ($dateLast['date']==date('Y-m-d')) {
		$dStart = 0;
		$dEnd = 1;
		$step = 1;
		$step2 = 0;
	} else {
		$dStart = 0+max($step,$step2);
		$dEnd = 1+max($step,$step2);
	}
	$dateStart = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m",strtotime($dateLast['date']))  , date("d",strtotime($dateLast['date']))+$dStart, date("Y",strtotime($dateLast['date']))));
	$dateEnd = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m",strtotime($dateLast['date']))  , date("d",strtotime($dateLast['date']))+$dEnd, date("Y",strtotime($dateLast['date']))));
	
	$q = "SELECT ref,ip,platform,country,user FROM ".$options['table']." WHERE ".$options['table'].".code not in (403,404) and ".$options['table'].".date BETWEEN '".$dateStart."' and '".$dateEnd."'";
	$result = mysqli_query($link,$q);

	if (mysqli_num_rows($result)>0){
		while($res=mysqli_fetch_assoc($result)){
			$url = get_domain($res['ref']);
			$seo = seo_domain($url);
			
			// count uni/raw in total //
			if (empty($domains['total']['stats']['unique'])) $domains['total']['stats']['unique'] = 0;
			if(!array_key_exists($res['ip'], $ips)) $domains['total']['stats']['unique'] += 1;
			if (empty($domains['total']['stats']['raw'])) $domains['total']['stats']['raw'] = 0;
			$domains['total']['stats']['raw'] += 1;
			if (empty($ips[$res['ip']])) $ips[$res['ip']] = 0;
			$ips[$res['ip']] += 1;
			
			// count uni/raw per domain //
			if (empty($domains[$seo]['stats']['unique'])) $domains[$seo]['stats']['unique'] = 0;
			if (empty($ips[$seo])) $ips[$seo] = array();
			if(!array_key_exists($res['ip'], $ips[$seo])) $domains[$seo]['stats']['unique'] += 1;
			if (empty($domains[$seo]['stats']['raw'])) $domains[$seo]['stats']['raw'] = 0;
			$domains[$seo]['stats']['raw'] += 1;
			if (empty($ips[$seo][$res['ip']])) $ips[$seo][$res['ip']] = 0;
			$ips[$seo][$res['ip']] += 1;
			
			// registered count //
			if (strripos($res['user'],'guest')===FALSE && !empty($res['user'])){
				if (empty($domains['registered']['stats']['raw'])) $domains['registered']['stats']['raw'] = 0;
				$domains['registered']['stats']['raw'] += 1;
				if (empty($domains['registered']['platform'][$res['platform']])) $domains['registered']['platform'][$res['platform']] = 0;
				$domains['registered']['platform'][$res['platform']] += 1;			
				if(!array_key_exists($res['user'], $user)){
					if (empty($domains['registered']['stats']['unique'])) $domains['registered']['stats']['unique'] = 0;
					$domains['registered']['stats']['unique'] += 1;
					if (empty($domains['registered']['country'][$res['country']])) $domains['registered']['country'][$res['country']] = 0;
					$domains['registered']['country'][$res['country']] += 1;				
				}
			}
			if (empty($user[$res['user']])) $user[$res['user']] = 0;
			$user[$res['user']] += 1;
					
			// countries and platforms //
			if (empty($domains[$seo]['platform'][$res['platform']])) $domains[$seo]['platform'][$res['platform']] = 0;
			$domains[$seo]['platform'][$res['platform']] += 1;
			if (empty($domains['total']['platform'][$res['platform']])) $domains['total']['platform'][$res['platform']] = 0;
			$domains['total']['platform'][$res['platform']] += 1;
			if (empty($domains[$seo]['country'][$res['country']])) $domains[$seo]['country'][$res['country']] = 0;
			$domains[$seo]['country'][$res['country']] += 1;
			if (empty($domains['total']['country'][$res['country']])) $domains['total']['country'][$res['country']] = 0;
			$domains['total']['country'][$res['country']] += 1;
		}
	} else {
		$domains['empty']['stats']['raw'] = 0;
		$domains['empty']['stats']['unique'] = 0;
		$domains['empty']['platform'] = 0;
		$domains['empty']['country'] = 0;
	}
	arsort($domains);
	
	foreach ($domains as $domain => $arr){
		if (date('Y-m-d',strtotime($dateStart))<=date('Y-m-d')) save_stats($dateStart,$domain,$arr);
		unset($domains[$domain]);
	}
	$step = 1-$step;
	if ($step==1 || $step2==1){
		$step2 = 1;
		rewrite_stats();
	}
}

function save_stats($dateStart,$domain,$inArray){
	global $link,$options;
	$new_date = date('Y-m-d', strtotime($dateStart));
	$new_domain = $domain;
	$new_unique = $inArray['stats']['unique'];
	$new_raw = $inArray['stats']['raw'];	
	$new_platform = '';
	foreach ($inArray['platform'] as $key => $value) $new_platform .= $key.':'.$value.'|';
	$new_platform = substr ($new_platform,0,-1);
	$new_country = '';
	foreach ($inArray['country'] as $key => $value) $new_country .= $key.':'.$value.'|';
	$new_country = substr ($new_country,0,-1);	
	
	$q = "SELECT id FROM ".$options['table']."_per_date WHERE ".$options['table']."_per_date.date='$new_date' AND domain='$new_domain'";
	$result = mysqli_query($link,$q);
	
	if (mysqli_num_rows($result)>0) $q = "UPDATE ".$options['table']."_per_date SET ".$options['table']."_per_date.unique='$new_unique',raw='$new_raw',platform='$new_platform',country='$new_country' WHERE ".$options['table']."_per_date.date='$new_date' AND domain='$new_domain'";
	else $q = "INSERT INTO ".$options['table']."_per_date VALUES (NULL,'$new_date','$new_domain','$new_unique','$new_raw','$new_platform','$new_country')";
	$result = mysqli_query($link,$q);
	
	// file for network
	$q = "SELECT * FROM ".$options['table']."_per_date WHERE domain='total' ORDER BY date ASC";
	$result = mysqli_query($link,$q);
	$forNetwork = '';
	while($res=mysqli_fetch_assoc($result)) $forNetwork .= $res['date'].'|'.$res['unique'].'|'.$res['raw']."\r\n";
	$myfile = fopen($options['path'].$options['folder']."flags/network.txt", "w");
	fwrite($myfile, $forNetwork);
	fclose($myfile);
	chmod($file, 0666);
	
}
	
function rewrite_stats_network(){
	global $link,$options;
	$folder = $options['path'].$options['folder'].'network';
	$sites = scandir($folder);
	foreach ($sites as $site){
		if ($site!='.' && $site!='..' && $site!=''){
			$myfile = fopen($folder.'/'.$site, "r");
			while (($opts=fgets($myfile)) !== false) {
				$opts = str_replace("\r\n","",$opts);
				$option = explode('=>',$opts);
				$config[$site][$option[0]] = $option[1];					
			}
			fclose($myfile);	
					
			$url = 'http://www.'.$site.$config[$site]['folder'].'flags/network.txt';
			$urlExists = url_exists($url);
			$urlExists1 = url_exists('http://'.$site.$config[$site]['folder'].'flags/network.txt');
			$urlExists2 = url_exists('https://www.'.$site.$config[$site]['folder'].'flags/network.txt');
			if($urlExists || $urlExists1 || $urlExists2){
				$statsFile = file_get_contents($url);
				$stats = explode("\r\n",$statsFile);
				foreach ($stats as $stat){
					$stat = explode('|',$stat);
					$q = "SELECT * FROM ".$options['table']."_network WHERE date='".$stat[0]."' and domain='".$site."'";
					if (mysqli_num_rows(mysqli_query($link,$q))==0){
						if ($stat[1]!='') $q = "INSERT INTO ".$options['table']."_network (date,domain,".$options['table']."_network.unique,raw) VALUES ('".$stat[0]."','".$site."','".$stat[1]."','".$stat[2]."')";
					} else {
						$q = "UPDATE ".$options['table']."_network SET ".$options['table']."_network.unique='".$stat[1]."',raw='".$stat[2]."' WHERE date='".$stat[0]."' and domain='".$site."'";
					}
					mysqli_query($link,$q);
				}
			}
		}
	}
}
	
function url_exists($url){
	$handle = curl_init($url);
	curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
	$response = curl_exec($handle);
	$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
	curl_close($handle);
	//echo $url." - ".$httpCode."\r\n";
	if ($httpCode == 200) return true;
	else return false;
	
}

function seo_domain($referer){
	global $searchEngines;
	$result = $referer;
	foreach ($searchEngines as $key=>$value){
		if(preg_match("/".$key."/", $referer)==1) $result = $key;
	}
	if (empty($referer)) $result = "NO-REF";
	return $result;
}

function stats_archive(){
	global $link,$options;
	$info = array();
	$startOver = FALSE;
	$q = "SELECT ".$options['table'].".date FROM ".$options['table']." ORDER BY ".$options['table'].".date LIMIT 1";
	$res=mysqli_fetch_assoc(mysqli_query($link,$q));
		
	$dateFirst = $res['date'];
		
	$dateLimit = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m")  , date("d")-$options['period'], date("Y")));
	$dateMax = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m")  , date("d")-$options['period']-$options['periodProcess'], date("Y")));
	if ($dateFirst<$dateMax){
		$startOver = TRUE;
		$dateLimit = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m",strtotime($dateFirst)), date("d",strtotime($dateFirst))+$options['periodProcess'], date("Y",strtotime($dateFirst))));
	}
	$periodReport = date('Y-m-d', mktime(0, 0, 0, date("m",strtotime($dateFirst)) , date("d",strtotime($dateFirst)), date("Y",strtotime($dateFirst))))." - ".date('Y-m-d', mktime(0, 0, 0, date("m",strtotime($dateLimit)) , date("d",strtotime($dateLimit)), date("Y",strtotime($dateLimit))));
	
	$q = "SELECT * FROM ".$options['table']." WHERE ".$options['table'].".date<'$dateLimit'";
	$result = mysqli_query($link,$q);
	
	$restart = TRUE; $rewrite = TRUE; $deleted = TRUE;
	while ($restart){
		if($deleted){
			if($rewrite){
				if($res=mysqli_fetch_assoc($result)){
					//echo $res['id'].' <br>';
					$restart = TRUE;
					$query = '';
					foreach ($res as $key => $value){
						if ($key=='id') $query .= 'NULL';
						elseif ($key=='ref') $query .= ",'".addslashes($value)."'";
						elseif ($key=='keyword') $query .= ",'".addslashes($value)."'";
						else $query .= ",'".$value."'";
					}
					$query = "INSERT INTO ".$options['table']."_history VALUES (".$query.")";
					$rewrite = mysqli_query($link,$query);
					if($rewrite) $deleted = mysqli_query($link,"DELETE FROM ".$options['table']." WHERE id='".$res['id']."'");				
				} else $restart = FALSE;
			} else {
				$rewrite = mysqli_query($link,$query);
				if($rewrite) $deleted = mysqli_query($link,"DELETE FROM ".$options['table']." WHERE id='".$res['id']."'");			
			}
		} else $deleted = mysqli_query($link,"DELETE FROM ".$options['table']." WHERE id='".$res['id']."'");
	}
	if($startOver){
		unset($res);
		$info['startOver'] = TRUE;
	}
	$info['period'] = $periodReport;
	return $info;
}



function create_tables(){
	global $link,$options;
	$q = mysqli_query($link,"SHOW TABLES LIKE '%".$options['table']."%'");
	if (mysqli_num_rows($q)<1){		
		$q = "CREATE  TABLE  ".$options['table']." (id int(32) NOT NULL AUTO_INCREMENT, date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, ip varchar(16) NOT NULL, user varchar(32) NOT NULL, ref varchar(256) NOT NULL, url varchar(128) NOT NULL, set_id smallint(6) NOT NULL, keyword varchar(64) NOT NULL, country varchar(2) NOT NULL, country_name varchar(32) NOT NULL, platform varchar(32) NOT NULL, code varchar(32) NOT NULL, PRIMARY KEY (id)) ENGINE  =  MyISAM  DEFAULT CHARSET = utf8";
		mysqli_query($link,$q);
		$q = "CREATE  TABLE  ".$options['table']."_history (id int(32) NOT NULL AUTO_INCREMENT, date datetime NOT NULL, ip varchar(16) NOT NULL, user varchar(32) NOT NULL, ref varchar(256) NOT NULL, url varchar(128) NOT NULL, set_id smallint(6) NOT NULL, keyword varchar(64) NOT NULL, country varchar(2) NOT NULL, country_name varchar(32) NOT NULL, platform varchar(32) NOT NULL, code varchar(32) NOT NULL, PRIMARY KEY (id)) ENGINE  =  MyISAM  DEFAULT CHARSET = utf8";
		mysqli_query($link,$q);
		$q = "CREATE  TABLE  ".$options['table']."_per_date (id int(32) NOT  NULL  AUTO_INCREMENT, date date NOT NULL, domain tinytext NOT NULL, ".$options['table']."_per_date.unique int(8) NOT NULL, raw int(8) NOT NULL, platform text NOT NULL, country mediumtext NOT NULL, PRIMARY  KEY (id)  ) ENGINE  = InnoDB  DEFAULT CHARSET  = utf8";
		mysqli_query($link,$q);
	}		 
}

function create_tables_trade(){
	global $link,$options;
	$result = mysqli_query($link,"SHOW TABLES LIKE '%".$options['table']."_trade%'");
	if (mysqli_num_rows($result)<1){		
		$q = "CREATE  TABLE  ".$options['table']."_trade (id int(32) NOT NULL AUTO_INCREMENT, domain varchar(64) NOT NULL, anchor varchar(256) NOT NULL, url varchar(256) NOT NULL, PRIMARY KEY (id), UNIQUE KEY (domain)) ENGINE  =  MyISAM  DEFAULT CHARSET = utf8";
		if (mysqli_query($link,$q)) return true;
	}
		 
}

//Detect mobile
require $options['path'].$options['folder']."lib/mobile_detect/Mobile_Detect.php";
function layoutTypes(){return array('classic', 'mobile', 'tablet');}
function initLayoutType(){
    if (!class_exists('Mobile_Detect')) { return 'classic'; }
    $detect = new Mobile_Detect;
    $isMobile = $detect->isMobile();
    $isTablet = $detect->isTablet();
    $layoutTypes = layoutTypes();
    if ( isset($_GET['layoutType']) ) {
        $layoutType = $_GET['layoutType'];
    } else {
        if (empty($_SESSION['layoutType'])) {
            $layoutType = ($isMobile ? ($isTablet ? 'tablet' : 'mobile') : 'classic');
        } else {
            $layoutType =  $_SESSION['layoutType'];
        }
    }
    if ( !in_array($layoutType, $layoutTypes) ) { $layoutType = 'classic'; }
    $_SESSION['layoutType'] = $layoutType;
    return $layoutType;
}
$layoutType = initLayoutType();

function get_keyword($referer){
	global $searchEngines;
    $search_phrase = '';
    $engines = $searchEngines;
    foreach($engines as $engine => $query_param) {
        // Check if the referer is a search engine from our list.
        // Also check if the query parameter is valid.
        if (strpos($referer, $engine.".") !==  false &&
            strpos($referer, $query_param) !==  false) {

            // Grab the keyword from the referer url
            $referer .= "&";
            $pattern = "/[?&]{$query_param}(.*?)&/si";
            preg_match($pattern, $referer, $matches);
            $search_phrase = urldecode($matches[1]);
            return $search_phrase;
        }
    }
    return;
}

function write_stats(){
	global $bot,$link,$layoutType,$options;
	if (!$bot){
		$user = empty($_SESSION['user'])?'Guest':$_SESSION['user'];
		$set = empty($_GET['b'])?'':$_GET['b'];
		$referer = empty($_SERVER['HTTP_REFERER'])?'':$_SERVER['HTTP_REFERER'];
		$key = get_keyword($referer);
		$keyword = $key!=''?$key:NULL;
		
		if ($options['processStatusCodes'] === true){
			$q = "INSERT INTO ".$options['table']." (ip,user,ref,url,set_id,keyword,platform,country,country_name,code) VALUES ('".$_SERVER['REMOTE_ADDR']."','".$user."','".$referer."','".$_SERVER['REQUEST_URI']."','".$set."','".$keyword."','".$layoutType."','".geoip_country_code_by_name($_SERVER['REMOTE_ADDR'])."','".geoip_country_name_by_name($_SERVER['REMOTE_ADDR'])."','".http_response_code()."')";
		} else {
			$q = "INSERT INTO ".$options['table']." (ip,user,ref,url,set_id,keyword,platform,country,country_name) VALUES ('".$_SERVER['REMOTE_ADDR']."','".$user."','".$referer."','".$_SERVER['REQUEST_URI']."','".$set."','".$keyword."','".$layoutType."','".geoip_country_code_by_name($_SERVER['REMOTE_ADDR'])."','".geoip_country_name_by_name($_SERVER['REMOTE_ADDR'])."')";
		}
		mysqli_query($link,$q);
	}
}

function toplist_build($template=1,$days=1){
	global $link,$options;
	if (!file_exists($_SERVER["DOCUMENT_ROOT"].'/toplist')) mkdir($_SERVER["DOCUMENT_ROOT"].'/toplist');
	chmod($_SERVER["DOCUMENT_ROOT"].'/toplist', 0777);
		
	$dateStart = date('Y-m-d', mktime(0, -$days, 0));
	$dateEnd = date('Y-m-d', mktime(0, 0, 0));
	$q = "SELECT ".$options['table']."_trade.*, SUM(".$options['table']."_per_date.unique) as amount FROM ".$options['table']."_trade, ".$options['table']."_per_date WHERE ".$options['table']."_per_date.domain = ".$options['table']."_trade.domain and ".$options['table']."_per_date.date BETWEEN '".$dateStart."' and '".$dateEnd."' GROUP BY ".$options['table']."_per_date.domain ORDER BY amount DESC";
	$result = mysqli_query($link,$q);
	
	$break = ceil(mysqli_num_rows($result)/4);
	$trade = '<table class="toplist"><tr><td valign="top" align="left">';
	$i = 0;
	while($res=mysqli_fetch_assoc($result)){
		$url = empty($res['url'])?'http://www.'.$res['domain']:$res['url'];
		$trade .= '<a class="top40" href="'.$url.'" target="_blank">'.$res['anchor'].' <em>'.$res['amount'].'</em></a>';
		$i++;
		if ($i==$break){
			$trade .= '</td><td valign="top" align="left">';
			$i=0;
		}
		
	}
	$trade .= '</td></tr></table>';
	$myfile = fopen($_SERVER["DOCUMENT_ROOT"]."/toplist/ready_main_top.htm", "w");
	fwrite($myfile, $trade);
	fclose($myfile);
}

function build_path(){
	global $options;
	$folders = array($options['folder']);
	foreach ($folders as $value){
		$file = $_SERVER["DOCUMENT_ROOT"].$value."stats-path.php";
		$myfile = fopen($file, "w");
		fwrite($myfile, '<?PHP $path="'.$_SERVER["DOCUMENT_ROOT"].$options['folder'].'"; ?>');
		fclose($myfile);
		chmod($file, 0666);
	}
}

function trade_add($domain,$anchor,$url){
	global $link,$options;
	$q = "INSERT INTO ".$options['table']."_trade (domain,anchor,url) VALUES ('".$domain."','".$anchor."','".$url."')";
	mysqli_query($link,$q);
	return $domain;
}

function trade_edit($domain,$anchor,$url){
	global $link,$options;
	$q = "UPDATE ".$options['table']."_trade SET anchor='$anchor', url='$url' WHERE domain='$domain'";
	mysqli_query($link,$q);
	return $domain;
}

function get_domain($url){
	if (empty($url)) return false;
	$info = parse_url($url);
	if (!empty($info['host'])){
		$host = $info['host'];
		$host_names = explode(".", $host);
		if ($host_names[count($host_names)-2]=='com' || $host_names[count($host_names)-2]=='co') $bottom_host_name = $host_names[count($host_names)-3].".".$host_names[count($host_names)-2].".".$host_names[count($host_names)-1];
		else $bottom_host_name = $host_names[count($host_names)-2].".".$host_names[count($host_names)-1];
		return $bottom_host_name;
	} else return false;
}

function recurse_copy($src,$dst) {
    $dir = opendir($src); 
    @mkdir($dst); 
	chmod($dst, 0777);
    while(false !== ( $file = readdir($dir)) ) { 
        if (($file!='.') && ($file!='..') && ($file!='options') && ($file!='flags') && ($file!='stats-path.php') && ($file!='network') && ($file!='tpl')){ 
            if (is_dir($src.'/'.$file)){ 
                recurse_copy($src.'/'.$file,$dst.'/'.$file);
            } else {
				//echo $src.'/'.$file.' - '.$dst.'/'.$file.'<br>';
                copy($src.'/'.$file,$dst.'/'.$file);
				chmod($dst.'/'.$file,0666);
            } 
        } 
    } 
    closedir($dir); 
} 

function domain_host() {
	$darray = array_reverse(explode('.', $_SERVER['HTTP_HOST']));
	$domainSelf = $darray[1].'.'.$darray[0];
	return $domainSelf;
}

function fn($cash) {
    if ($cash>1000000000000){ 
		return round(($cash/1000000000000),4).' t';
    }else if($cash>1000000000){ 
		return round(($cash/1000000000),3).' b';
    }else if($cash>1000000){ 
		return round(($cash/1000000),2).' m';
    }else if($cash>100000){ 
		return round(($cash/1000),1).' k';
	}
 
    return number_format($cash);
}

?>
