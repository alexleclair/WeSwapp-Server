<?php
require_once dirname(__FILE__).'/BaseObject.class.php';
class Item extends BaseObject{
	protected $_tableName = 'items';
	protected $_primaryKey = 'id';
	public function __construct($item_id=null){
		$this->_getDb();
		if($item_id){
			$this->_load($item_id);
		}
	}

	public function getAllData($userId=null){
		require_once dirname(__FILE__).'/User.class.php';

		$user = new User($this->get('user_id'));
	    $_item = $this->getData();
	    $_item['user'] = $user->getData();
	    $_item['medias'] = [];
	    $_item['favorited'] = false;
	    foreach($this->getMedias() as $media){
	        if(!$media->is_active){
	            continue;
	        }
	        $_item['medias'][] = $media->getUrl();
	    }

	    if(isset($userId)){
	    	$sql = 'SELECT COUNT(*) cnt FROM user_favorites WHERE item_id=%d AND user_id=%d AND is_active=1';
	    	$sql = sprintf($sql, $this->id, $userId);
	 
			$result = BaseObject::query($sql);
			$row = mysqli_fetch_assoc($result);
			$_item['favorited'] = $row['cnt'] > 0;
	    }

	    return $_item;
	}
	
	public function getMedias(){
		require_once(dirname(__FILE__).'/Media.class.php');
		$media = new Media();
		$medias = $media->getList('item_id='.intval($this->data['id']));
		return $medias;
	}
}
?>