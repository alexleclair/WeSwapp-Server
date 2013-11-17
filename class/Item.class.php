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

	public function getAllData(){
		$user = new User($this->get('user_id'));
	    $_item = $this->getData();
	    $_item['user'] = $user->getData();
	    $_item['medias'] = [];

	    foreach($this->getMedias() as $media){
	        if(!$media->is_active){
	            continue;
	        }
	        $_item['medias'][] = $media->getUrl();
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