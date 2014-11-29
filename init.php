<?php 

/**
 * @package rudraxframework\core
 */
require_once "Console.php";
include_once ("model/AbstractRequest.php");
global $RDb;
class RudraX {

	public static $websitecache;
	public static $webmodules;
	public static $REQUEST_MAPPED = FALSE;
	public static $browser;

	public static function init(){
		include_once ("model/RxCache.php");
		if(self::$browser==NULL)
			self::$browser = new Browser();
	}
	public static function scanModules(){
		self::$webmodules = self::WebCache()->get('modules');
		if(DEBUG_BUILD || !self::$webmodules){
			self::$webmodules = self::getModuleProperties(get_include_path().LIB_PATH,self::$webmodules);
			self::$webmodules = self::getModuleProperties(get_include_path().RESOURCE_PATH,self::$webmodules);
			self::WebCache()->set('modules',self::$webmodules);
		}	else self::$webmodules =self::WebCache()->get('modules');
	}
	public static function getModules(){
		if(self::$webmodules==null){
			self::scanModules();
		}
		return self::$webmodules['mods'];
	}
	public static function WebCache(){
		if(self::$websitecache ==NULL) self::$websitecache = new RxCache('rudrax');
		return self::$websitecache;
	}

	public static function loadConfig($file,$file2=null){
		ob_start ();
		session_start ();
		$DEFAULT_GLOB = parse_ini_file ("config/_project.properties", TRUE );
		$GLOBALS ['CONFIG']= parse_ini_file ($file, TRUE );

		if($file2!=null && file_exists($file2)){
			$GLOBALS ['CONFIG'] = array_merge($GLOBALS ['CONFIG'],parse_ini_file ($file2, TRUE ));
		}

		$GLOBALS ['CONFIG']['GLOBAL'] = array_merge(
				$DEFAULT_GLOB['GLOBAL'],
				$GLOBALS ['CONFIG']['GLOBAL']
		);
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

	public static function mapRequestInvoke (){
		return self::_mapRequest(self::$url_varmap,self::$url_callback);
	}
	public static function mapRequest ($mapping,$callback){
		if(self::$REQUEST_MAPPED) return;
		$mapper = preg_replace('/\{(.*?)\}/m','(?P<$1>[\w\.]*)', str_replace('/','#',$mapping));
		$mapperArray = explode("#",$mapper);
		$mapperSize = (empty($mapping) ? 0 : count($mapperArray))+1;
		if(self::$url_size < $mapperSize){
			$varmap = array();
			preg_match("/".$mapper."/",str_replace( "/","#",Q),$varmap);
			if(count($varmap)>0){
				self::$url_size = $mapperSize;
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

	public static function resolvePath($str){
		$array = explode( '/', $str);
		$domain = array_shift( $array);
		$parents = array();
		foreach( $array as $dir) {
			switch( $dir) {
				case '.':
					// Don't need to do anything here
					break;
				case '..':
					array_pop( $parents);
					break;
				default:
					$parents[] = $dir;
					break;
			}
		}
		return $domain . '/' . implode( '/', $parents);
	}

	public static function classInfo($path){
		$info = explode("/",$path);
		return array(
				"class_name" =>	end($info),
				"file_path" => $path
		);
	}

	public static function getModuleProperties($dir,$filemodules = array("_" => array(),"mods" => array())){

		if (!is_dir($dir)){
			return $filemodules;
		}
		$d = dir($dir);

		while (false !== ($entry = $d->read())){
			if ($entry != '.' && $entry != '..'){
				if (is_dir($dir.'/'.$entry)){
					$filemodules = self::getModuleProperties($dir.'/'.$entry,$filemodules);
				} else if(strcmp ($entry,"module.properties")==0){
					try{
						$mod_file = $dir.'/'.$entry;
						$mode_time = filemtime($mod_file);
						if(!DEBUG_BUILD && isset($filemodules["_"][$mod_file]) 
							&& $mode_time == $filemodules["_"][$mod_file]){
							Browser::console("from-cache....".$mod_file);
						} else {
							Browser::console("fresh ....",$mod_file);
							$filemodules["_"][$mod_file] = $mode_time;
							$r = parse_ini_file ($dir.'/'.$entry, TRUE );
							//Browser::console($dir.'/'.$entry);
							foreach($r as $mod=>$files){
								$filemodules['mods'][$mod] = array("files"=>array());
								foreach($files as $key=>$file){
									if($key!='@' && !is_remote_file($file)){
										$filemodules['mods'][$mod]["files"][] = $dir.'/'.$file;
										//$filemodules['mods'][$mod][$key] = self::resolvePath($dir.'/'.$file);
									} else $filemodules['mods'][$mod][$key] = $file;
								}
							}
						}
					} catch (Exception $e){
						echo 'Caught exception: ',  $e->getMessage(), "\n";
					}
				}
			}
		}
		$d->close();
		return $filemodules;
	}

	public static function invoke($_conf=array()){
		$conf = array_merge(array(
				'controller' => 'web.php',
				'DEFAULT_DB' => 'DB1'
		),$_conf);
		//Loads all the Constants
		self::loadConfig("../app/config/project.properties","../local/project.properties");
		//Initialze Rudrax
		self::init();
		global $RDb;
		if(isset($conf["DEFAULT_DB"])){
			$RDb = self::getDB($conf["DEFAULT_DB"]);
		}
		// Define Custom Request Plugs
		require_once(APP_PATH."/controller/".$conf["controller"]);

		// Default RudraX Plug
		self::mapRequest("template/{temp}",function($temp="nohandler"){
			return self::invokeHandler($temp);
		});
		self::mapRequest('data/{eventname}',function($eventName="dataHandler"){
			$controller = self::getDataController();
			$controller->invokeHandler($eventName);
		});
		self::mapRequest("resources.json",function($cb=""){
			echo $cb."(".json_encode(self::getModules()).")"; 
		});
		// Default Plug for default page
		self::mapRequest("",function(){
			return self::invokeHandler("Index");
		});
		self::mapRequestInvoke();
		$RDb->close();
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

class Browser {

	private static $console;

	public function  __construct(){
		self::$console = new Console();
	}
	public static function console($msgData){
		return self::$console->browser($msgData,debug_backtrace ());
	}
	public static function log(){
		$args = func_get_args ();
		$msgData = "\"";
		foreach ($args as $key=>$val){
			$msgData = $msgData.",".json_encode($val);
		}
		$msgData = $msgData.",\"";
		return self::$console->browser($msgData,debug_backtrace ());
	}
	public static function printlogs(){
		return self::$console->printlogs();
	}
}

function is_remote_file( $file ){
	if( preg_match( "/(http|https)/", $file ) ){ //It is a remote file
		return true;
	} else { //Local file
		return false;
	}
}
?>