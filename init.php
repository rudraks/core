<?php 

/**
 * @package rudraxframework\core
 */
require_once "php_functions.php";
require_once "Console.php";
include_once ("model/AbstractRequest.php");
include_once ("model/RxCache.php");
global $RDb;
class RudraX {

	public static $websitecache;
	public static $REQUEST_MAPPED = FALSE;
	public static $browser;
	private static $mtime;
	private static $single_ton = array();

	public static function init(){
		if(self::$browser==NULL)
			self::$browser = new Browser();
	}

	public static function WebCache(){
		if(self::$websitecache ==NULL) self::$websitecache = new RxCache('rudrax');
		return self::$websitecache;
	}
	
	public function getSingletonInstance ($path){
		if(isset(self::$single_tong[$path])){
			return self::$single_ton[$path];
		}
		$_className = array_pop(explode("/", $path));
		// Retrieve arguments list
		$_args = func_get_args();
		// Delete the first argument which is the class name
		array_shift($_args);
		$_reflection = new ReflectionClass($_className);
		$_className[$_className] = $_reflection->newInstanceArgs($_args);
		return $_className[$_className];
	}

	public static function getTemplateController(){
		self::includeUser();
		include_once("controller/AbstractTemplateController.php");
		if (file_exists(get_include_path() . CONTROLLER_PATH . "/TemplateController.php" )) {
			include_once (CONTROLLER_PATH . "/TemplateController.php");
		} else {
			include_once ("controller/TemplateController.php");
		}
		return new TemplateController();
	}
	public static function getPageController(){
		self::includeUser();
		include_once("controller/AbstractPageController.php");
		if (file_exists(get_include_path() . CONTROLLER_PATH . "/PageController.php" )) {
			include_once (CONTROLLER_PATH . "/PageController.php");
		} else {
			include_once ("controller/PageController.php");
		}
		return new PageController();
	}
	public static function getNotificationController (){
		self::includeUser();
		include_once ("controller/AbstractNotificationController.php");
		if (file_exists(get_include_path() . CONTROLLER_PATH . "/NotificationController.php" )) {
			include_once (CONTROLLER_PATH . "/NotificationController.php");
		} else {
			include_once ("controller/NotificationController.php");
		}
		return new NotificationController();
	}
	public static function getDataController (){
		self::includeUser();
		include_once ("controller/AbstractDataController.php");
		if (file_exists(get_include_path() . CONTROLLER_PATH . "/DataController.php" )) {
			include_once (CONTROLLER_PATH . "/DataController.php");
		} else {
			include_once ("controller/DataController.php");
		}
		return new DataController();
	}

	public static function invokeHandler ($handlerName){
		self::includeUser();
		if (file_exists(get_include_path() . CONTROLLER_PATH . "/NoController.php" )) {
			include_once (CONTROLLER_PATH . "/NoController.php");
		} else {
			include_once ("controller/NoController.php");
		}
		$controller = new NoController();
		return $controller->invokeHandler($handlerName);
	}

	public static function includeUser(){
		include_once ("model/AbstractUser.php");
		if (file_exists ( get_include_path () . MODEL_PATH . "/User.php" )) {
			include_once (MODEL_PATH . "/User.php");
		} else {
			include_once ("model/User.php");
		}
	}
	public static function getDB($configname){
		include_once ("db/AbstractDb.php");
		return new AbstractDb(Config::get($configname));
	}
	public static function getArgsArray($reflectionMethod,$argArray,AbstractRequest $request = NULL,$skipEmpty=FALSE){
		$arr = array();
		if(!isset($request)){
			$request = HttpRequest::getInstance();
		}
		foreach($reflectionMethod->getParameters() as $key => $val){
			if (isset($argArray[$val->getName()]) && !($skipEmpty  && empty($argArray[$val->getName()]))){
				$arr[$val->getName()] = $argArray[$val->getName()];
			} else if(!is_null($request->get($val->getName(),$skipEmpty))){
				$arr[$val->getName()] = $request->get($val->getName());
			} else if($val->isDefaultValueAvailable()){
				$arr[$val->getName()] = $val->getDefaultValue();
			} else {
				$arr[$val->getName()] = NULL;
			}
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

	public static $url_callback = null;
	public static $url_size = 0;
	public static $url_varmap = null;
	public static $url_cache = null;

	public static function mapRequestInvoke (){
		return self::_mapRequest(self::$url_varmap,self::$url_callback);
	}
	public static function mapRequest ($mapping,$callback){
		if(self::$REQUEST_MAPPED) return;
		
		$mapObj  = self::$url_cache->get($mapping);
		
		if($mapObj == null || RX_MODE_DEBUG){
			$mapper = preg_replace('/\{(.*?)\}/m','(?P<$1>[\w\.]*)', str_replace('/','#',$mapping));
			$mapperArray = explode("#",$mapper);
			$mapperSize = (empty($mapping) ? 0 : count($mapperArray))+1;
			$mapObj = array(
					"mapper"=>$mapper,
					"mapperArray"=>$mapperArray,
					"mapperSize"=>$mapperSize
			);
			self::$url_cache->set($mapping, $mapObj);
		}
		
		if(self::$url_size < $mapObj["mapperSize"]){
			$varmap = array();
			preg_match("/".$mapObj["mapper"]."/",str_replace( "/","#",Q),$varmap);
			if(count($varmap)>0){
				self::$url_size = $mapObj["mapperSize"];
				self::$url_callback = $callback;
				self::$url_varmap = $varmap;
			}
		}
	}
	public static function _mapRequest ($varmap,$callback){
		$request =  HttpRequest::getInstance()->loadParams($varmap);
		$argArray = self::getArgsArray(new ReflectionFunction($callback),$varmap,NULL,TRUE);
		$request->loadParams($argArray);
		self::$REQUEST_MAPPED = TRUE;
		return call_user_func_array($callback, $argArray);
	}

	public static function classInfo($path){
		$info = explode("/",$path);
		return array(
			"class_name" =>	end($info),
			"file_path" => $path
		);
	}

	public static function invoke($_conf=array()){
		$global_config = array_merge(array(
				'CONTROLLER' => 'web.php',
				'DEFAULT_DB' => 'DB1',
				'CONSOLE_FUN' => 'console.log',
				'RX_MODE_DEBUG' => FALSE,
				'RX_JS_MERGE' => TRUE
		),$_conf);
		//Loads all the Constants
		ob_start ();
		session_start ();
		Config::load("../app/config/project.properties","../local/project.properties",$global_config);
		//Initialze Rudrax
		self::init();
		global $RDb;
		$config = Config::getSection("GLOBAL");
		$db_connect = false;
		if(isset($config["DEFAULT_DB"])){
			$RDb = self::getDB($config["DEFAULT_DB"]);
			$db_connect = true;
		}
		// Define Custom Request Plugs
		self::$url_cache = new RxCache("url_".$config['CONTROLLER'],true);
		require_once(APP_PATH."/controller/".$config["CONTROLLER"]);

		require_once("controller.php");
		self::$url_cache->save(true);
		
		self::mapRequestInvoke();
		if($db_connect){
			$RDb->close();
		}
		Config::save();
		if(!RX_MODE_DEBUG){
			setcookie('RX-ENCRYPT-PATH',"TRUE",0,"/");
		} else {
			removecookie('RX-ENCRYPT-PATH');
		}
	}
	
	public static function writeBuildFile($file,$content){
		return file_put_contents("../build/".$file, $content);
	}

}
class DBUtils {
	public static function getDB($configname){
		return RudraX::getDB($configname);
	}
}
class Config {
	
	public static $cache;
	
	public static function get($section,$prop=NULL){
		if(isset($GLOBALS['CONFIG'][$section])){
			return $GLOBALS['CONFIG'][$section];
		} else return defined($section) ? constant($section) : FALSE;
	}
	
	//CACHE MAINTAIN
	public static function setValue($key,$value){
		return self::$cache->set($key, $value);
	}
	
	public static function getSection($key){
		return self::$cache->get($key);
	}
	public static function hasValue($key){
		return self::$cache->hasKey($key);
	}
	
	public static function read($glob_config,$file,$file2=null){
		$debug = isset($glob_config["RX_MODE_DEBUG"]) && ($glob_config["RX_MODE_DEBUG"] == TRUE);
		
		self::$cache = new RxCache("config_".$glob_config['CONTROLLER'],true);

		$reloadCache = FALSE;
		if(self::$cache->isEmpty()){
			$reloadCache = TRUE;
		} else {
			$_glob_config = self::$cache->get("GLOBAL");
			if($_glob_config["RX_MODE_DEBUG"] != $debug){
				$reloadCache = TRUE;
			}
		}
		
		if($debug || $reloadCache){
			
			$DEFAULT_CONFIG = parse_ini_file ("_project.properties", TRUE );
			$localConfig = parse_ini_file ($file, TRUE );
			$localConfig = array_replace_recursive($DEFAULT_CONFIG,$localConfig);
				
			if($file2!=null && file_exists($file2)){
				$localConfig  = array_replace_recursive($localConfig ,parse_ini_file ($file2, TRUE ));
			}
			self::$cache->merge($localConfig);
			self::$cache->set('GLOBAL',array_merge(
					$DEFAULT_CONFIG['GLOBAL'],
					$localConfig['GLOBAL'],$glob_config
			));
			
			self::$cache->save();
		}
		return self::$cache->getArray();;
	}
	
	public static function load($file,$file2=null,$glob_config = array()){
	
		$GLOBALS ['CONFIG'] = self::read($glob_config,$file,$file2);
	
		set_include_path ($GLOBALS['CONFIG']['GLOBAL']['WORK_DIR']);
		
		define("BASE_PATH", dirname(__FILE__) );
	
		//print_r($GLOBALS ['CONFIG']['GLOBAL']);
		foreach($GLOBALS ['CONFIG']['GLOBAL'] as $key=>$value){
			define ( $key, $value);
		}
	
		define('Q',(isset($_REQUEST['q']) ? $_REQUEST['q'] : NULL));
	
		$path_info = pathinfo($_SERVER['PHP_SELF']);
		$CONTEXT_PATH = (
				(Q==NULL) ?
				strstr($_SERVER['PHP_SELF'],$path_info['basename'],TRUE)
				: strstr($_SERVER['REQUEST_URI'],Q,true)
		);
		
		define ( 'CONTEXT_PATH', $CONTEXT_PATH);
		define ('APP_CONTEXT',resolve_path(
		$CONTEXT_PATH . (get_include_path())
		));
		Console::set(TRUE);
	}
	
	public static function save(){
		self::$cache->save(TRUE);
	}
}

class Browser {

	private static $console;

	public function  __construct(){
		self::$console = new Console();
	}
	public static function console($msgData){
		if(RX_MODE_DEBUG) return self::$console->browser($msgData,debug_backtrace ());
	}
	public static function log(){
		if(RX_MODE_DEBUG){
			return self::logMessage(func_get_args (), debug_backtrace (), "console.log");	
		}
	}
	public static function info(){
		return self::logMessage(func_get_args (), debug_backtrace (), "console.info");	
	}
	public static function error(){
		return self::logMessage(func_get_args (), debug_backtrace (), "console.error");	
	}
	public static function warn(){
		return self::logMessage(func_get_args (), debug_backtrace (), "console.warn");
	}
	private static function logMessage($args,$trace,$logType){
		$msgData = "";
		$msgArray = array();
		foreach ($args as $key=>$val){
			//$msgData = $msgData.",".json_encode($val);
			$msgArray[] = json_encode($val);
		}
		$msgData = implode(",", $msgArray);
		return self::$console->browser($msgData,$trace,$logType);
	} 
	public static function printlogs(){
		return self::$console->printlogs();
	}
}
?>