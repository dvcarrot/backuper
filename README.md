# backuper
Php Mysql Backuper

## example
	use dvcarrot\backuper\Backuper;
    $backuper = new Backuper(array(
    	'database' => 'my_database',
    	'username' => 'my_username',
    	'hostname' => 'my_hostname',
    	'password' => 'my_password',
    ));
    $backuper->execute();
