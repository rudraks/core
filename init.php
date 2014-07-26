<?php 
require_once "Console.php";
include_once ("model/AbstractRequest.php");

class RudraX {

	public static $websitecache;
	public static $webmodules;
	public static $REQUEST_MAPPED = FALSE;
	public static $browser;

	public static function init(){
		include_once ("model/RxCache.php");
		if(self::$browser==NULL)
			self::$browser = new Browser();
		self::$webmodules = self::WebCache()->get('modules');
		if(DEBUG_BUILD || !self::$webmodules){
			self::$webmodules = self::getModuleProperties(get_include_path().LIB_PATH,self::$webmodules);
			self::$webmodules = self::getModuleProperties(get_include_path().RESOURCE_PATH,self::$webmodules);
			self::WebCache()->set('modules',self::$webmodules);
		}	else self::$webmodules =self::WebCache()->get('modules');
	}


	public static function getModules(){
		return self::$webmodules['mods'];
	}
	public static function WebCache(){
		if(self::$websitecache ==NULL) self::$websitecache = new RxCache('rudrax');
		return self::$websitecache;
	}

	public static function loadConfig($file){
		ob_start ();
		session_start ();
		$DEFAULT_GLOB = parse_ini_file ("config/_project.properties", TRUE );
		$GLOBALS ['CONFIG']= parse_ini_file ($file, TRUE );
		
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
		include_once ("model/AbstractNotificationController.php");
		if (file_exists(get_include_path() . CONTROLLER_PATH . "/NotificationController.php" )) {
			include_once (CONTROLLER_PATH . "/NotificationController.php");
		} else {
			include_once ("controller/NotificationController.php");
		}
		return new NotificationController();
	}
	public static function getDataController (){
		self::includeUser();
		include_once ("model/AbstractDataController.php");
		if (file_exists(get_include_path() . CONTROLLER_PATH . "/DataController.php" )) {
			include_once (CONTROLLER_PATH . "/DataController.php");
		} else {
			include_once ("controller/DataController.php");
		}
		return new DataController();
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
						if(!DEBUG_BUILD && isset($filemodules["_"][$mod_file]) && $mode_time == $filemodules["_"][$mod_file]){
							Browser::console("from cache....");
						} else {
							$filemodules["_"][$mod_file] = $mode_time;
							$r = parse_ini_file ($dir.'/'.$entry, TRUE );
							foreach($r as $mod=>$files){
								$filemodules['mods'][$mod] = array();
								foreach($files as $key=>$file){
									if($key!='@' && !is_remote_file($file))
										$filemodules['mods'][$mod][$key] = $dir.'/'.$file;
									else $filemodules['mods'][$mod][$key] = $file;
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
		return self::$console->browser($msgData);
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