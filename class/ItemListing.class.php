<?php
require_once dirname(__FILE__).'/BaseObject.class.php';
require_once dirname(__FILE__).'/Item.class.php';
require_once dirname(__FILE__).'/User.class.php';
class ItemListing{
	public static function getItems($onlyAvailable=false, $userId=null, $filters=array()){
		//Sample opts array:
		$where = array();
		if(isset($filters['authToken']) && !empty($filters['friends']) && isset(Shared::$Facebook)){
			$_friends = Shared::$Facebook->api('/me/friends');
			$in = array();
			foreach($_friends['data'] as $friend){
				$in[] = '"'.$friend['id'].'"';
			}
			$where[] = 'i.`user_id`IN('.implode($in, ',').')';
		}

		if($onlyAvailable){
			//$where[] = '`status`="available"';
		}

		if(isset($filters['tag'])){
			$where[] = 'id = (SELECT t.item_id FROM item_tags t WHERE t.item_id=i.id AND t.`value`="'.BaseObject::escapeString($filters['tag']).'")';
		}

		$sql = 'SELECT i.id FROM `items` i WHERE i.user_id='.intval($userId).' OR ('.(count($where) ? implode($where, ' AND ') : '').') ORDER BY i.created_at DESC LIMIT 0,200';
		$result = BaseObject::query($sql);

		$items = array();
		while($row = mysqli_fetch_assoc($result)){
			$Item = new Item($row['id']);
			$item = $Item->getAllData($userId);
			if(!empty($item['medias'])){
			    $items[] = $item;
			}
		}
		return $items;
	}


}
?>