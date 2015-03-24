<?php 

/**
 * @package rudraxframework\core
 */
require_once "php_functions.php";
require_once "Console.php";
include_once ("model/AbstractRequest.php");
include_once ("model/RxCache.php");
include_once ("ClassUtil.php");
global $RDb;
class RudraX {

	public static $websitecache;
	public static $REQUEST_MAPPED = FALSE;
	private static $mtime;
	public static $ANNOTATIONS;

	public static function init(){
		Browser::init();
		ClassUtil::init();
		self::$ANNOTATIONS = new RxCache("annotation",true);
	}

	public static function WebCache(){
		if(self::$websitecache ==NULL) self::$websitecache = new RxCache('rudrax');
		return self::$websitecache;
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
	public static $url_controller_info = null;

	public static function getMapObject($mapping){
		$mapObj  = self::$url_cache->get($mapping);

		if($mapObj == null || RX_MODE_DEBUG){
			$mapper = preg_replace('/\{(.*?)\}/m','(?P<$1>[\w\.]*)', str_replace('/','#',$mapping));
			$mapperKey = preg_replace('/\{(.*?)\}/m','*', $mapping)."*";
			$mapperArray = explode("#",$mapper);
			$mapperSize = (empty($mapping) ? 0 : count($mapperArray))+1;
			$mapObj = array(
					"mapper"=>$mapper,
					"mapperArray"=>$mapperArray,
					"mapperSize"=>$mapperSize,
					"mapperKey" => $mapperKey
			);
			self::$url_cache->set($mapping, $mapObj);
		}
		if(self::$url_size < $mapObj["mapperSize"] & fnmatch($mapObj["mapperKey"],Q)){
			$varmap = array();
			preg_match("/".$mapObj["mapper"]."/",str_replace( "/","#",Q),$varmap);
			if(count($varmap)>0){
				self::$url_size = $mapObj["mapperSize"];
				self::$url_varmap = $varmap;
				return $mapObj;
			}
		}
		return NULL;
	}

	public static function invokeController (){

		if(self::$url_controller_info != NULL) {
			/*
			 * Url has been match in newer way
			*/
			include_once self::$url_controller_info["filePath"];
			$controller = new self::$url_controller_info["className"];
			$controller->loadSession();
			$controller->_interceptor_(
					self::$url_controller_info,
					self::invokeMethod($controller,self::$url_controller_info["method"],self::$url_varmap)
			);
		}
	}

	public static function findAndExecuteController(){
		self::$url_cache = new RxCache("url",true);
		$allControllers = ClassUtil::getControllerArray();
		if(!empty($allControllers)){
			foreach ($allControllers as $mappingUrl=>$mappingInfo){
				$mapObj = self::getMapObject($mappingUrl);
				if($mapObj!=NULL){
					self::$url_controller_info = $mappingInfo;
				}
			}
		}
		self::$url_cache->save(true);
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
				'RX_MODE_DEBUG' => FALSE
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
		if(FIRST_RELOAD){
			ClassUtil::scan();
		}
		
		self::findAndExecuteController();

		self::invokeController();
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
		header("FLAGS:".self::$cache->isEmpty()."-".$_glob_config["RX_MODE_DEBUG"] ."-".$debug);
		if(self::$cache->isEmpty()){
			$reloadCache = TRUE;
		} else {
			$_glob_config = self::$cache->get("GLOBAL");
			if($_glob_config["RX_MODE_DEBUG"] != $debug){
				$reloadCache = TRUE;
			}
		}

		if($debug || $reloadCache){
			define("FIRST_RELOAD", TRUE);
				
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
		} else {
			define("FIRST_RELOAD", FALSE);
		}
		return self::$cache->getArray();;
	}

	public static function load($file,$file2=null,$glob_config = array()){

		$GLOBALS ['CONFIG'] = self::read($glob_config,$file,$file2);

		set_include_path ($GLOBALS['CONFIG']['GLOBAL']['WORK_DIR']);

		define("BASE_PATH", dirname(__FILE__) );

		//print_r($GLOBALS ['CONFIG']['GLOBAL']);
		$header_flags = "FIRST_RELOAD=".FIRST_RELOAD;
		foreach($GLOBALS ['CONFIG']['GLOBAL'] as $key=>$value){
			define ( $key, $value);
			$header_flags.=(";".$key."=".$value);
		}
		header("X-FLAGS: ".$header_flags);

		define('Q',(isset($_REQUEST['q']) ? $_REQUEST['q'] : NULL));
		define('Q',(isset($_REQUEST['q']) ? $_REQUEST['q'] : NULL));
		
		$path_info = pathinfo($_SERVER['PHP_SELF']);
		$CONTEXT_PATH = (
				(Q==NULL) ?
				strstr($_SERVER['PHP_SELF'],$path_info['basename'],TRUE)
				: strstr($_SERVER['REQUEST_URI'],Q,true)
		);

		//echo "CONTEXT_PATH::".Q;
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

	public static function  init(){
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
		if(BROWSER_LOGS){
			return self::$console->printlogs();
		}
	}
	public static function printlogsOnHeader(){
		if(BROWSER_LOGS){
			return self::$console->printlogsOnHeader();
		}
	}
	
}

class FileUtil {
	public static function write($file,$content){
		return file_put_contents("../build/".$file, $content);
	}
}
?>