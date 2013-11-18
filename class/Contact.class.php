<?php
require_once dirname(__FILE__).'/BaseObject.class.php';
class Contact extends BaseObject{
	protected $_tableName = 'item_contacts';
	protected $_primaryKey = 'id';
	public function __construct($contact_id=null){
		if(!empty($contact_id)){
			$this->_load($contact_id);
		}
	}
}
?>