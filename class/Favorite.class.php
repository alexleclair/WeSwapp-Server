<?php
require_once dirname(__FILE__).'/BaseObject.class.php';
class Favorite extends BaseObject{
	protected $_tableName = 'user_favorites';
	protected $_primaryKey = 'id';
	public function __construct($favId=null){
		if(!empty($favId)){
			$this->_load($favId);
		}
	}

	public static function getItemIds($userId){

	}

	public static function getId($itemId, $userId){
		$sql = 'SELECT id FROM user_favorites WHERE item_id=%d AND user_id=%d';
    	$sql = sprintf($sql, $itemId, $userId);
    	$result = BaseObject::query($sql);
		if(mysqli_num_rows($result) > 0){
			$row = mysqli_fetch_assoc($result);
			return $row['id'];
		}
		return null;
	}
	public static function getFavorites($userId){
		$userId = intval($userId);
		$items = array();
		require_once dirname(__FILE__).'/Item.class.php';
		$Item = new Item();
		foreach($Item->getList('t.id IN(SELECT f.item_id FROM user_favorites f WHERE f.user_id = '.$userId.' AND is_active=1)', 'id', 'DESC', 150) as $item){
	        $items[] = $item->getAllData($userId);
	    }
	    return $items;
	}
}
?>