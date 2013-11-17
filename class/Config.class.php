<?php
class Config{
	private static $config;

	public static function getConfig($forceReload=false){
		if($forceReload || self::$config == null){
			self::$config = include(dirname(__FILE__).'/config.php');
		}
		return self::$config;
	}
}
?>