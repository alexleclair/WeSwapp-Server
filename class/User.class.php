<?php
require_once dirname(__FILE__).'/BaseObject.class.php';
class User extends BaseObject{
	protected $_tableName = 'users';
	protected $_primaryKey = 'id';
	public function __construct($user_id){
		$this->_load($user_id);
	}

	public function getData(){
		$data = parent::getData();
		unset($data['email']);
		return $data;
	}
}
?>