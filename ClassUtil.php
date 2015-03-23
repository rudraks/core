<?php


class ClassUtil {

	public static $cache = null;
	public static $allController;

	public static function init(){
		if(self::$cache==null){
			self::$cache = new RxCache("annotation",true);
		}
	}

	public static function scan(){
		self::init();
		call_user_func(rx_function("rx_scan_classes"));
	}

	public static function getControllerArray(){
		self::$allController = self::$cache->get("Controller#ALL",self::$allController);
		return 	self::$allController;
	}
	
	public static function setControllerArray($allController){
		self::$allController = $allController;
		return self::$cache->set("Controller#ALL",$allController);
	}

	public static function save(){
		self::$cache->save();
	}

	public static function getMTime($className){
		return self::$cache->get("##@@###".$className,0);
	}
	public static function setMTime($className,$mtime){
		return self::$cache->set("##@@###".$className,$mtime);
	}
	
	public static function setHandler($name,$info){
		return self::$cache->set("Handler#".$name,$info);
	}
	
	public static function getHandler($name){
		return self::$cache->get("Handler#".$name);
	}
	
	public static function setController($name,$info){
		return self::$cache->set("Controller#".$name,$info);
	}
	
	public static function getController($name){
		return self::$cache->get("Controller#".$name);
	}
	
	public static function setModel($name,$info){
		return self::$cache->set("Model#".$name,$info);
	}
	
	public static function getModel($name){
		return self::$cache->get("Model#".$name);
	}
	
	public static function getSessionUserClass(){
		$UserClassInfo = ClassUtil::getModel("sessionUser");
		if($UserClassInfo != NULL){
			include_once $UserClassInfo["filePath"];
			return $UserClassInfo["className"];
		} else {
			include_once "model/AbstarctUser.php";
			return "DefaultUser";
		}
	}
	
}