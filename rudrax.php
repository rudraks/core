<?php 
require_once "Console.php";
include_once ("model/AbstractRequest.php");
class RudraX {
	public static $REQUEST_MAPPED = FALSE;
	public static function loadConfig($file){
		ob_start ();
		session_start ();
		$GLOBALS ['CONFIG'] = parse_ini_file ($file, TRUE );
		set_include_path ($GLOBALS['CONFIG']['GLOBAL']['WORK_DIR']);
		define("BASE_PATH", dirname(__FILE__) );

		foreach($GLOBALS ['CONFIG']['GLOBAL'] as $key=>$value){
			define ( $key, $value);
		}

		define('Q',(isset($_REQUEST['q']) ? $_REQUEST['q'] : NULL));

		$path_info = pathinfo($_SERVER['PHP_SELF']);
		define ( 'CONTEXT_PATH', (
		(Q==NULL) ?
		strstr($_SERVER['PHP_SELF'],$path_info['basename'],TRUE)
		: strstr($_SERVER['REQUEST_URI'],Q,true)
		));
		Console::set(TRUE);
	}
	public static function getTemplateController(){
		self::includeUser();
		include_once(RUDRA."/controller/AbstractTemplateController.php");
		if (file_exists(get_include_path() . CONTROLLER_PATH . "/TemplateController.php" )) {
			include_once (CONTROLLER_PATH . "/TemplateController.php");
		} else {
			include_once (RUDRA . "/controller/TemplateController.php");
		}
		return new TemplateController();
	}
	public static function getPageController(){
		self::includeUser();
		include_once(RUDRA."/controller/AbstractPageController.php");
		if (file_exists(get_include_path() . CONTROLLER_PATH . "/PageController.php" )) {
			include_once (CONTROLLER_PATH . "/PageController.php");
		} else {
			include_once (RUDRA . "/controller/PageController.php");
		}
		return new PageController();
	}
	public static function getNotificationController (){
		self::includeUser();
		if (file_exists(get_include_path() . CONTROLLER_PATH . "/NotificationController.php" )) {
			include_once (CONTROLLER_PATH . "/NotificationController.php");
		} else {
			include_once (RUDRA . "/controller/NotificationController.php");
		}
		return new NotificationController();
	}
	public static function getDataController (){
		self::includeUser();
		if (file_exists(get_include_path() . CONTROLLER_PATH . "/DataController.php" )) {
			include_once (CONTROLLER_PATH . "/DataController.php");
		} else {
			include_once (RUDRA . "/controller/DataController.php");
		}
		return new DataController();
	}
	public static function includeUser(){
		if (file_exists ( get_include_path () . MODEL_PATH . "/User.php" )) {
			include_once (MODEL_PATH . "/User.php");
		} else {
			include_once (RUDRA . "/_model_User.php");
		}
	}
	public static function getDB($configname){
		include_once (RUDRA . "/db/AbstractDb.php");
		return new AbstractDb(Config::get($configname));
	}
	public static function getArgsArray($reflectionMethod,$argArray,AbstractRequest $request = NULL,$skipEmpty=FALSE){
		$arr = array();
		if(!isset($request)){
			$request = HttpRequest::getInstance();
		}
		foreach($reflectionMethod->getParameters() as $key => $val){
			//$value_ = $argArray[$val->getName()];
			//empty($argArray[$val->getName()]) ? $argArray[$val->getName()];
			if (isset($argArray[$val->getName()]) && !($skipEmpty  && empty($argArray[$val->getName()]))){
				//echo "--".$val->getName().'---1<br>';
				$arr[$val->getName()] = $argArray[$val->getName()];
			} else if(!is_null($request->get($val->getName(),$skipEmpty))){
				//echo "--".$val->getName().'---2<br>';
				$arr[$val->getName()] = $request->get($val->getName());
			} else if($val->isDefaultValueAvailable()){
				//echo "--".$val->getName().'---3<br>';
				$arr[$val->getName()] = $val->getDefaultValue();
			} else {
				//echo "--".$val->getName().'---4<br>';
				$arr[$val->getName()] = NULL;
			}
// 			$arr[$val->getName()] = (isset($argArray[$val->getName()]) && !empty($argArray[$val->getName()]))
// 			? $argArray[$val->getName()]
// 			:(($request->get($val->getName()))
// 					? $request->get($val->getName())
// 					:($val->isDefaultValueAvailable()
// 							? $val->getDefaultValue() : NULL
// 					)
// 			);
		}
		return $arr;
	}
	public static function invokeMethodByReflectionClass (ReflectionClass $reflectionClass,$object,$methodName,$argArray){
		$reflectionMethod = $reflectionClass->getMethod($methodName);
		return call_user_func_array(array($object, $methodName), self::getArgsArray($reflectionMethod,$argArray));
	}
	public static function invokeMethod ($object,$methodName,$argArray){
		$reflectionClass = new ReflectionClass(get_class($object));
		$reflectionMethod = $reflectionClass->getMethod($methodName);
		return call_user_func_array(array($object, $methodName), self::getArgsArray($reflectionMethod,$argArray));
	}

	public static function mapRequest ($mapping,$callback){
		if(self::$REQUEST_MAPPED) return;
		$mapper = preg_replace('/\{(.*?)\}/m','(?P<$1>[\w\.]*)', str_replace('/','#',$mapping));
		$varmap = array();
		preg_match("/".$mapper."/",str_replace( array("/"),
		array("#"),Q),$varmap);

		$request =  HttpRequest::getInstance()->loadParams($varmap);
		$argArray = self::getArgsArray(new ReflectionFunction($callback),$varmap,NULL,TRUE);
		$request->loadParams($argArray);
		//print_r($argArray);
		if(count($varmap)>0){
			self::$REQUEST_MAPPED = TRUE;
			return call_user_func_array($callback, $argArray);
		}
	}

}
class DBUtils {
	public static function getDB($configname){
		return RudraX::getDB($configname);
	}
}
class Config {
	public static function get($section,$prop=NULL){
		if(isset($GLOBALS['CONFIG'][$section])){
			return $GLOBALS['CONFIG'][$section];
		} else return constant($section);
	}
}
?>