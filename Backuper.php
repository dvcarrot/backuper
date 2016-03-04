<?php
namespace dvcarrot\backuper;

class Backuper
{
	protected $database;
	protected $username;
	protected $hostname;
	protected $password;
	protected $filename;

	public function __construct(array $params = array())
	{
		$available = array('database', 'username', 'hostname', 'password');
		foreach($available as $key) {
			if (!array_key_exists($key, $params))
				$this->error(sprintf('Empty parameter "%s"', $key));
			else
				$this->$key = $params[$key];
		}
		$this->filename = $_SERVER['DOCUMENT_ROOT'] . '/backups/' . date('Y-m-d_H-i-s') . '.sql';
	}
	
	public function execute()
	{
		$this->connect();
		$resultShowTables = $this->showTables();
		while($itemShowTables = $this->fetch($resultShowTables)) {
			$resultCreateTable = $this->showCreateTable($itemShowTables[0]);
			if ($itemCreateTable = $this->fetch($resultCreateTable)) {
				$this->putToFile($itemCreateTable[1]);
				$resultSelectFrom = $this->selectFrom($itemCreateTable[0]);
				while($itemSelectFrom = $this->fetch($resultSelectFrom)) {
					$sqlInsertItem = $this->createSqlInsert($itemCreateTable[0], $itemSelectFrom);
					$this->putToFile($sqlInsertItem);
				}
			}			
		}
	}
	
	protected function createSqlInsert($table, $fields)
	{
		$values = '';
		foreach($fields as $field) {
			if (is_null($field)) $field = 'NULL';
			else $field = "'" . $this->escape($field) . "'";
			$values .= $field . ', ';
		}
		$values = substr($values, 0, -2);
		return sprintf("INSERT INTO `%s` VALUES (%s)", $table, $values);
	}
	
	protected function escape($str)
	{
		return mysql_escape_string($str);
	}

	protected function putToFile($str)
	{
		file_put_contents($this->filename, $str  . ";\r\n", FILE_APPEND);
	}
	
	protected function selectFrom($table)
	{
		return $this->query(sprintf('SELECT * FROM `%s`', $table));
	}
	
	protected function showCreateTable($table)
	{		
		return $this->query(sprintf("SHOW CREATE TABLE %s", $table));
	}

	protected function showTables()
	{
		return $this->query("SHOW TABLES");
	}
	
	protected function fetch($result)
	{
		return mysql_fetch_row($result);
	}
	
	protected function connect()
	{
		mysql_connect($this->hostname, $this->username, $this->password) or $this->error("connect error");
		mysql_select_db($this->database) or $this->error("select db error");		
	}
	
	protected function query($sql)
	{
		$result = mysql_query($sql);
		if (!$result)
			$this->error("query error");
		return $result;
	}
	
	protected function error($message)
	{
		$error = mysql_error();
		if ($error)
			$message .= ': '. $error;
		die($message);
	}
}