<?php
require_once dirname(__FILE__).'/BaseObject.class.php';
require_once dirname(__FILE__).'/Item.class.php';
require_once dirname(__FILE__).'/User.class.php';
class ItemListing{
	public static function getItems($opts=array()){
		//Sample opts array:
		$opts['friends'] = array('123123','12308123091283');
		$sql = 'SELECT id FROM `items` ORDER BY created_at DESC LIMIT 0,200';
		$result = BaseObject::query($sql);

		$items = array();
		while($row = mysqli_fetch_assoc($result)){
			$Item = new Item($row['id']);
			$item = $Item->getAllData();
		    $items[] = $item;
		}
		return $items;
	}


}
?>