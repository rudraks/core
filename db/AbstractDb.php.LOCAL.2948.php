<?php
/*
 * @category Lib
* @package Test Suit
* @copyright 2011, 2012 Dmitry Sheiko (http://dsheiko.com)
* @license GNU
*/

class AbstractDb
{
	private $_link;
	private $_fetchMode;

	/**
	 *
	 * @param array $config DB configuration & path to traverse
	 */
	public function  __construct($config)
	{
		$this->_initDb($config);
		$this->setFetchMode(PDO::FETCH_OBJ);
	}
	/**
	 * Connects to DB
	 * @param string/array $configSrc
	 */
	private function _initDb($configSrc) {
		$configSrc = is_string ( $configSrc ) ? $this->_loadData ( $configSrc ) : ( object ) $configSrc;
		try {
			$this->_link = new PDO ( "mysql:host={$configSrc->host};dbname={$configSrc->dbname}", $configSrc->username, $configSrc->password, array (
					PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
			) );
		} catch ( PDOException $e ) {
			Console::exception($e);
			//die ();
		}
	}
	/**
	 * Load configuration data from the source code
	 *
	 * @param string $path
	 * @return array | string
	 */
	private function _loadData($path)
	{
		$data = array();
		if (file_exists($path)) {
			ob_start();
			$data = include($path);
			ob_end_clean();
		}
		return (object)$data;
	}
	/**
	 * Binds given parameters to the SQL string
	 *
	 * @param array $args
	 * @return string
	 */
	private function _prepare(array $args)
	{
		if (empty ($args)) {
			throw new Exception("Empty query string");
		}
		$sql = array_shift($args);
		$sql = $args ? vsprintf($sql, $args) : $sql;
		return $sql;
	}

	/**
	 * Fethes row adhering DAO interface
	 *
	 * @param string $sql
	 * @param mixed $bindingParam1
	 * @param mixed $bindingParamN
	 * @return stdObject
	 */
	public function fetch($sql)
	{
		$sql = $this->_prepare(func_get_args());
		$sth = $this->_link->prepare($sql);
		$sth->execute();
		$res = array();
		//$sth->setFetchMode($this->_fetchMode);
		return $sth->fetch($this->_fetchMode);
	}

	/**
	 * Fetches row list adhering DAO interface
	 *
	 * @param string $sql
	 * @param mixed $bindingParam1
	 * @param mixed $bindingParamN
	 * @return array
	 */
	public function fetchAll($sql)
	{
		$sql = $this->_prepare(func_get_args());
		$sth = $this->_link->prepare($sql);
		$sth->execute();
		$res = array();
		//$sth->setFetchMode($this->_fetchMode);
		while ($fetch = $sth->fetch($this->_fetchMode)) {
			$res[] = $fetch;
		}
		return $res;
	}
	/**
	 * Updates adhering DAO interface
	 *
	 * @param string $sql
	 * @param mixed $bindingParam1
	 * @param mixed $bindingParamN
	 * @return PDOStatement
	 */
	public function update()
	{
		$sql = $this->_prepare(func_get_args());
		return $this->query($sql);
	}

	/**
	 * Performs a query on the database
	 *
	 * @param string $query
	 * @param mixed $bindingParam1
	 * @param mixed $bindingParamN
	 * @return PDOStatement | false
	 */
	public function query($sql)
	{
		$query = $this->_prepare(func_get_args());
		if (!($res = $this->_link->query($query))) {
			$arr = $this->_link->errorInfo();
			Console::log("SQL Error: " . $sql . "  ({$arr[2]}) ");
		}
		return $res;
	}
	 

	/**
	 * Begins a new transaction
	 *
	 * @return boolean
	 */
	public function beginTransaction()
	{
		return $this->_link->beginTransaction();
	}
	/**
	 * Commits the current transaction
	 *
	 * @return boolean
	 */
	public function commit()
	{
		return $this->_link->commit();
	}
	/**
	 * Rolls back current transaction
	 *
	 * @return boolean
	 */
	public function rollback()
	{
		return $this->_link->rollBack();
	}
	/**
	 * Returns the auto generated id used in the last query
	 *
	 * @return int
	 */
	public function lastInsertId()
	{
		return $this->_link->lastInsertId();
	}
	/**
	 * Closes the PDO Connection
	 *
	 */
	public function close()
	{
		return $this->_link = null;
	}
	
	public function getFetchMode()
	{
		return $this->_fetchMode;
	}
	
	public function setFetchMode($mode)
	{
		//check for PDO extension
		
		switch ($mode) {
			case PDO::FETCH_LAZY:
			case PDO::FETCH_ASSOC:
			case PDO::FETCH_NUM:
			case PDO::FETCH_BOTH:
			case PDO::FETCH_NAMED:
			case PDO::FETCH_OBJ:
				$this->_fetchMode = $mode;
				break;
			default:
				//$this->_fetchMode = PDO::FETCH_OBJ;
				Console::log("Invalid fetch mode ".$mode." specified");
				break;
		}
	}
}
