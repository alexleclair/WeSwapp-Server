<?php
require_once dirname(__FILE__).'/BaseObject.class.php';
require_once dirname(__FILE__).'/Item.class.php';
require_once dirname(__FILE__).'/User.class.php';
class ItemListing{
	public static function getItems($onlyAvailable=false, $userId=null){
		//Sample opts array:

		$where = $onlyAvailable ? 'WHERE status="available"' : '';

		$sql = 'SELECT id FROM `items` '.$where.'  ORDER BY created_at DESC LIMIT 0,200';
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