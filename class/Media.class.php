<?php
require_once dirname(__FILE__).'/BaseObject.class.php';
class Media extends BaseObject{
	protected $_tableName = 'item_medias';
	protected $_primaryKey = 'id';
	public function __construct($media_id=null){
		if(!empty($media_id)){
			$this->_load($media_id);
		}
	}
	public function getUrl(){
		$sizeX = mt_rand(200,500);
		$sizeY = mt_rand(200,500);
		$processors = array('http://www.fillmurray.com/', 'http://www.placecage.com/', 'http://baconmockup.com/');
		$url = $processors[mt_rand(0,count($processors)-1)];
		if(strpos($this->url, 'http')===0){
			return $this->url;
		}
		//return $url.$sizeX.'/'.$sizeY;
		return 'http://'.$_SERVER['HTTP_HOST'].'/uploads/'.$this->url;
	}
	public function processPicture(&$image){
		$config = Config::getConfig();
		$path = $config['upload']['path'];
		$filename = $this->item_id.'_'.uniqid('IMG', true).'.jpeg';
		$type = substr($image, 0,25);
		if(strpos($type, 'image/jpeg') || strpos($type, 'image/jpg')){
			$image = imagecreatefromjpeg(str_replace(' ', '+', $image));
		}
		else{
			$image = imagecreatefrompng(str_replace(' ', '+', $image));
		}
		imagejpeg($image, $path.'/'.$filename);
		unset($image);
		$this->url = $filename;
	}

}
?>