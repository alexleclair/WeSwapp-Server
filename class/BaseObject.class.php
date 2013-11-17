<?php
require_once dirname(__FILE__).'/Config.class.php';
class BaseObject{
	protected $_tableName = '';
	protected $_primaryKey = 'id';

	public static $_db;
	private $data = array();
	private $result;

	protected function _load($id){
		$this->_getDb();

		if($id){
			$this->load($id);
		}
	}

	public static function query($sql){
		self::__getDb();
		return mysqli_query(self::$_db, $sql);
	}

	public function load($id){
		$query = 'SELECT * FROM `%s` WHERE `%s` = %d';
		$query = sprintf($query, $this->_tableName, $this->_primaryKey, $id);
		$this->result = mysqli_query(self::$_db, $query) or die(mysqli_error(self::$_db));
		$this->data = mysqli_fetch_assoc($this->result);
	}

	public function __get($key){
		if(isset($this->data[$key])){
			return $this->data[$key];
		}
		else{
			return $this->$key;
		}
	}
	public function __isset($key){
		return isset($this->$key) || isset($this->data[$key]);
	}
	public function __set($key, $value){
		$this->data[$key] = $value;
	}

	public function save(){
		$this->_getDb();

		$isUpdate = !empty($this->data[$this->_primaryKey]);

		if($isUpdate){
			$sql = 'UPDATE `%s` SET %s WHERE %s = %d';
			
			$values = array();
			foreach($this->data as $k=>$v){
				$value = $v==NULL? 'NULL' : '"'.mysqli_real_escape_string(self::$_db, $v).'"';
				$values[] = '`'.mysqli_real_escape_string(self::$_db, $k).'`='.$value;
			}

			$sql = sprintf($sql, $this->_tableName, implode(',',$values), $this->_primaryKey, $this->data[$this->_primaryKey]);
			//die('SQL IS '.$sql);
		}
		else{
			$sql = 'INSERT INTO `%s`(%s) VALUES(%s)';
			$keys = array();
			$values = array();
			foreach($this->data as $k=>$v){
				$value = $v==NULL? 'NULL' : '"'.mysqli_real_escape_string(self::$_db, $v).'"';
				$keys[] = '`'.mysqli_real_escape_string(self::$_db, $k).'`';
				$values[] = $value;

			}

			$sql = sprintf($sql, $this->_tableName, implode(',', $keys), implode(',', $values));
		}
		mysqli_query(self::$_db, $sql);
		$insertId = mysqli_insert_id(self::$_db);
		if($insertId > 0){
			$this->load($insertId);
		}

	}
	protected function _getDb(){
		return self::__getDb();
	}
	protected static function __getDb(){
		$config = Config::getConfig();
		if(self::$_db == null){
			self::$_db = mysqli_connect($config['sql']['host'], $config['sql']['username'], $config['sql']['password'], $config['sql']['database']);
			mysqli_query(self::$_db, 'SET NAMES "utf8"');
		}
		return self::$_db;
	}

	public function getData(){
		return $this->data;
	}

	public function getList($filters='1=1', $orderBy=null, $sortOrder='ASC', $limit=NULL, $page=0){
		$sortOrder = $sortOrder == 'ASC' ? 'ASC' : 'DESC';
		$orderBy = isset($orderBy) ? $orderBy : $this->_primaryKey;

		$orderBy = is_array($orderBy) ? $orderBy : array($orderBy);
		foreach($orderBy as &$_order){
			$_order = '`'.mysqli_real_escape_string(self::$_db, $_order).'`';
		}

		//trusting filters, 'cause fuck it. No time. TODO.

		$sql = 'SELECT * FROM %s t WHERE %s ORDER BY %s %s';
		$sql = sprintf($sql, $this->_tableName, $filters, $_order, $sortOrder);

		if($limit){
			$sql.=' LIMIT '.$page.','.intval($limit);
		}
		$result = mysqli_query(self::$_db, $sql) or die(mysqli_error(self::$_db));
		while($row = mysqli_fetch_assoc($result)){
			$class = get_class($this);
			$obj = new $class;
			foreach($row as $k=>$v){
				$obj->$k = $v;
			}
			yield $obj;
		}

	}

	public function get($key){
		if(isset($this->data[$key]))
			return $this->data[$key];
		else
			return null;
	} 
}