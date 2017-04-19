<?
	if(!$access) die('Direct access not permitted');
    $options = array(
                     'host'   => 'localhost',
                     'user'   => 'db_user',
					 'pass'     => 'db_pass',
                     'db_name'   => 'db_name',
                     'table'      => 'db_table',
                     'login'      => 'script_user',
                     'loginpass'     => 'script_pass',
                     'path'   => 'path/to/html/root/',
                     'folder'    => '/script/folder/',
                     'period'      => 14,
                     'periodProcess'    => 3,
                     'processStatusCodes'=> true,
                     'workWithTrades'    => false,
                     'workWithNetwork'    => true,
                     'errorLog' => 'path/to/error.log'
	);
   
?>
