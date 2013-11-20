<?php
require_once dirname(__FILE__).'/BaseObject.class.php';
class Tag extends BaseObject{
	protected $_tableName = 'item_tags';
	protected $_primaryKey = 'NULL';
	public function __construct($item_id=null, $tag=null){
		if(!empty($contact_id)){
			$this->_load($contact_id);
		}
	}

	public static function removeTags($item_id){
		$sql = 'DELETE FROM item_tags WHERE `item_id` = %d';
		BaseObject::query(sprintf($sql, $item_id));
	}

	public static function addTags($item_id, $tags=array()){
		$tags = is_array($tags) ? $tags : array($tags);

		foreach ($tags as $tag) {
			$sql = 'INSERT INTO item_tags(item_id, `value`) VALUES(%d, "%s")';
			BaseObject::query(sprintf($sql, $item_id, BaseObject::escapeString($tag)));
		}
	}
}
?>