<?php

/**
 * @package rudraxframework\core
 */
require_once "php_functions.php";
require_once "Console.php";
// include_once ("model/AbstractRequest.php");
include_once ("model/RxCache.php");
include_once ("ClassUtil.php");

class RudraX {
	public static $websitecache;
	public static $REQUEST_MAPPED = FALSE;
	private static $mtime;
	public static $ANNOTATIONS;
	public static function init() {
		Browser::init ();
		ClassUtil::init ();
		self::$ANNOTATIONS = new RxCache ( "annotation", true );
	}
	public static function WebCache() {
		if (self::$websitecache == NULL)
			self::$websitecache = new RxCache ( 'rudrax' );
		return self::$websitecache;
	}
	public static $url_callback = null;
	public static $url_size = 0;
	public static $url_varmap = null;
	public static $url_cache = null;
	public static $url_controller_info = null;
	public static function getMapObject($mapping) {
		$mapObj = self::$url_cache->get ( $mapping );
		
		if ($mapObj == null || RX_MODE_DEBUG) {
			$mapper = preg_replace ( '/\{(.*?)\}/m', '(?P<$1>[\w\.]*)', str_replace ( '/', '#', $mapping ) );
			$mapperKey = preg_replace ( '/\{(.*?)\}/m', '*', $mapping ) . "*";
			$mapperArray = explode ( "#", $mapper );
			$mapperSize = (empty ( $mapping ) ? 0 : count ( $mapperArray )) + 1;
			$mapObj = array (
					"mapper" => $mapper,
					"mapperArray" => $mapperArray,
					"mapperSize" => $mapperSize,
					"mapperKey" => $mapperKey 
			);
			self::$url_cache->set ( $mapping, $mapObj );
		}
		if (self::$url_size < $mapObj ["mapperSize"] & fnmatch ( $mapObj ["mapperKey"], Q )) {
			$varmap = array ();
			preg_match ( "/" . $mapObj ["mapper"] . "/", str_replace ( "/", "#", Q ), $varmap );
			if (count ( $varmap ) > 0) {
				self::$url_size = $mapObj ["mapperSize"];
				self::$url_varmap = $varmap;
				return $mapObj;
			}
		}
		return NULL;
	}
	public static function invokeController() {
		if (self::$url_controller_info != NULL) {
			/*
			 * Url has been match in newer way
			 */
			include_once self::$url_controller_info ["filePath"];
			$controller = new self::$url_controller_info ["className"] ();
			$controller->loadSession ();
			$controller->_interpret_ ( self::$url_controller_info, self::$url_varmap );
		}
	}
	public static function findAndExecuteController() {
		self::$url_cache = new RxCache ( "url", true );
		$allControllers = ClassUtil::getControllerArray ();
		if (! empty ( $allControllers )) {
			foreach ( $allControllers as $mappingUrl => $mappingInfo ) {
				$mapObj = self::getMapObject ( $mappingUrl );
				if ($mapObj != NULL) {
					self::$url_controller_info = $mappingInfo;
				}
			}
		}
		self::$url_cache->save ( true );
	}
	public static function classInfo($path) {
		$info = explode ( "/", $path );
		return array (
				"class_name" => end ( $info ),
				"file_path" => $path 
		);
	}
	public static function invoke($_conf = array()) {
		Browser::time ( "invoked" );
		$global_config = array_merge ( array (
				'CONTROLLER' => 'web.php',
				'DEFAULT_DB' => 'DB1',
				'CONSOLE_FUN' => 'console.log',
				'RX_MODE_DEBUG' => FALSE 
		), $_conf );
		// Loads all the Constants
		ob_start ();
		
		session_start ();
		Config::load ( "../app/meta/project.properties", "../config/project.properties", $global_config );
		// Initialze Rudrax
		self::init ();
		Browser::time ( "After Init" );

		$config = Config::getSection ( "GLOBAL" );
		$db_connect = false;
		Browser::time ( "Before DB Connect" );
		/**
		 * NOTE:- NO need to connect DB automatically, it should be connecte donly when required;
		 */
// 		if (isset ( $config ["DEFAULT_DB"] )) {
// 			$RDb = self::getDB ( $config ["DEFAULT_DB"] );
// 			$db_connect = true;
// 		}
		Browser::time ( "Before-First Reload" );
		// Define Custom Request Plugs
		if (FIRST_RELOAD) {
			ClassUtil::scan ();
		}
		
		self::findAndExecuteController ();
		
		self::invokeController ();
		DBService::close();
		
		Browser::time ( "Before Saving" );
		Config::save ();
		Browser::time ( "After Saving" );
		$clientConfig = Config::get ( "CLIENT_CONST" );
		/*
		 * $RX_ENCRYPT_PATH is applicable only if either MINFY or MERGE, this variable ise used by .htaccess file
		 */
		$RX_ENCRYPT_PATH = (! RX_MODE_DEBUG) && ($clientConfig ["RX_JS_MIN"] || $clientConfig ["RX_JS_MERGE"]);
		Browser::header ( RX_MODE_DEBUG . "." . $clientConfig ["RX_JS_MIN"] . "." . $clientConfig ["RX_JS_MERGE"] );
		if ($RX_ENCRYPT_PATH) {
			setcookie ( 'RX-ENCRYPT-PATH', "TRUE", 0, "/" );
			define ( "RX_ENCRYPT_PATH", true );
		} else {
			removecookie ( 'RX-ENCRYPT-PATH' );
			define ( "RX_ENCRYPT_PATH", false );
		}
		Browser::time ( "Invoked:Ends" );
	}
}

class Config {
	public static $cache;
	public static function get($section, $prop = NULL) {
		if (isset ( $GLOBALS ['CONFIG'] [$section] )) {
			return $GLOBALS ['CONFIG'] [$section];
		} else {
			return defined ( $section ) ? constant ( $section ) : FALSE;
		}
	}
	
	// CACHE MAINTAIN
	public static function setValue($key, $value) {
		return self::$cache->set ( $key, $value );
	}
	public static function getSection($key) {
		return self::$cache->get ( $key );
	}
	public static function getProperty($section, $property) {
		$sectionData = self::$cache->get ( $section );
		if ($property == null) {
			return $sectionData;
		} else if ($sectionData != null && isset ( $sectionData [$property] )) {
			return $sectionData [$property];
		}
		return null;
	}
	public static function hasValue($key) {
		return self::$cache->hasKey ( $key );
	}
	public static function read($glob_config, $file, $file2 = null) {
		$debug = isset ( $glob_config ["RX_MODE_DEBUG"] ) && ($glob_config ["RX_MODE_DEBUG"] == TRUE);
		
		self::$cache = new RxCache ( "config_" . $glob_config ['CONTROLLER'], true );
		
		$reloadCache = FALSE;
		header ( "FLAGS:" . self::$cache->isEmpty () . "-" . $_glob_config ["RX_MODE_DEBUG"] . "-" . $debug );
		if (self::$cache->isEmpty ()) {
			$reloadCache = TRUE;
		} else {
			$_glob_config = self::$cache->get ( "GLOBAL" );
			if ($_glob_config ["RX_MODE_DEBUG"] != $debug || isset ( $_GET ['ModPagespeed'] )) {
				$reloadCache = TRUE;
			}
		}
		
		$RELOAD_VERSION = self::$cache->get ( "RELOAD_VERSION" );
		
		if ($debug || $reloadCache) {
			FileUtil::checkDirectory ();
			define ( "FIRST_RELOAD", TRUE );
			$RELOAD_VERSION = microtime ( true );
			self::$cache->set ( "RELOAD_VERSION", $RELOAD_VERSION );
			
			$DEFAULT_CONFIG = parse_ini_file ( "_project.properties", TRUE );
			$localConfig = array ();
			if (file_exists ( $file )) {
				$localConfig = parse_ini_file ( $file, TRUE );
			}
			$localConfig = array_replace_recursive ( $DEFAULT_CONFIG, $localConfig );
			
			if ($file2 != null && file_exists ( $file2 )) {
				$localConfig = array_replace_recursive ( $localConfig, parse_ini_file ( $file2, TRUE ) );
			}
			self::$cache->merge ( $localConfig );
			self::$cache->set ( 'GLOBAL', array_merge ( $DEFAULT_CONFIG ['GLOBAL'], $localConfig ['GLOBAL'], $glob_config ) );
			
			$reloadMode = isset($_GET ['ModPagespeed']) ? $_GET ['ModPagespeed'] : NULL;
			
			call_user_func ( rx_function ( "rx_reload_cache" ),$reloadMode);
			
			self::$cache->save ();
		} else {
			define ( "FIRST_RELOAD", FALSE );
		}
		define ( "RELOAD_VERSION", $RELOAD_VERSION );
		return self::$cache->getArray ();
		;
	}
	public static function load($file, $file2 = null, $glob_config = array()) {
		$GLOBALS ['CONFIG'] = self::read ( $glob_config, $file, $file2 );
		
		set_include_path ( $GLOBALS ['CONFIG'] ['GLOBAL'] ['WORK_DIR'] );
		
		define ( "BASE_PATH", dirname ( __FILE__ ) );
		
		// print_r($GLOBALS ['CONFIG']['GLOBAL']);
		foreach ( $GLOBALS ['CONFIG'] ['GLOBAL'] as $key => $value ) {
			define ( $key, $value );
		}
		
		define ( 'Q', (isset ( $_REQUEST ['q'] ) ? $_REQUEST ['q'] : NULL) );
		
		$path_info = pathinfo ( $_SERVER ['PHP_SELF'] );
		$CONTEXT_PATH = ((Q == NULL) ? strstr ( $_SERVER ['PHP_SELF'], $path_info ['basename'], TRUE ) : strstr ( $_SERVER ['REQUEST_URI'], Q, true ));
		/**
		 * TODO:- Fix it wth better solution
		 */
		if ($CONTEXT_PATH == null) {
			$CONTEXT_PATH = str_replace ( $path_info ['basename'], "", $_SERVER ['PHP_SELF'] );
		}
		
		// Browser::header("Q=".$_REQUEST ['q']);
		// Browser::header("CONTEXT_PATH==".$CONTEXT_PATH);
		// Browser::header("PHP_SELF==".$_SERVER ['PHP_SELF']);
		// Browser::header("basename==".$path_info ['basename']);
		// Browser::header("REQUEST_URI==".$_SERVER ['REQUEST_URI']);
		
		// echo "CONTEXT_PATH::".Q;
		define ( 'CONTEXT_PATH', $CONTEXT_PATH );
		define ( 'APP_CONTEXT', resolve_path ( $CONTEXT_PATH . (get_include_path ()) ) );
		Console::set ( TRUE );
	}
	public static function save() {
		self::$cache->save ( TRUE );
	}
}
class Browser {
	private static $console;
	private static $messageCounter = 0;
	private static $timeCounter;
	public static function init() {
		self::$console = new Console ();
	}
	public static function console($msgData) {
		if (RX_MODE_DEBUG)
			return self::$console->browser ( $msgData, debug_backtrace () );
	}
	public static function log() {
		if (RX_MODE_DEBUG) {
			return self::logMessage ( func_get_args (), debug_backtrace (), "console.log" );
		}
	}
	public static function info() {
		return self::logMessage ( func_get_args (), debug_backtrace (), "console.info" );
	}
	public static function error() {
		return self::logMessage ( func_get_args (), debug_backtrace (), "console.error" );
	}
	public static function warn() {
		return self::logMessage ( func_get_args (), debug_backtrace (), "console.warn" );
	}
	public static function header($total_time) {
		header ( "X-LOG-" . (++ self::$messageCounter) . ": " . $total_time );
	}
	public static function time($msg) {
		if (self::$timeCounter == null) {
			self::$timeCounter = microtime ( true );
		}
		header ( "X-LOG-TIME-" . (++ self::$messageCounter) . ": " . "[" . (microtime ( true ) - self::$timeCounter) . "] " . $msg );
		self::$timeCounter = microtime ( true );
	}
	private static function logMessage($args, $trace, $logType) {
		$msgData = "";
		$msgArray = array ();
		foreach ( $args as $key => $val ) {
			// $msgData = $msgData.",".json_encode($val);
			$msgArray [] = json_encode ( $val );
		}
		$msgData = implode ( ",", $msgArray );
		return self::$console->browser ( $msgData, $trace, $logType );
	}
	public static function printlogs() {
		if (BROWSER_LOGS) {
			return self::$console->printlogs ();
		}
	}
	public static function printlogsOnHeader() {
		if (BROWSER_LOGS) {
			return self::$console->printlogsOnHeader ();
		}
	}
}
class FileUtil {
	public static function checkDirectory() {
		try {
			if (! is_dir ( "../build/" )) {
				self::mkdir ( "cache" );
			}
		} catch ( Exception $e ) {
			echo "build directory not found in project root, please create with appropritae permissions and try again";
		}
	}
	public static function write($file, $content) {
		return file_put_contents ( "../build/" . $file, $content );
	}
	public static function read($file) {
		return readfile ( "../build/" . $file );
	}
	public static function mkdir($dirName, $rights = 0777) {
		$dirs = explode ( '/', "../build/" . $dirName );
		$dir = '';
		foreach ( $dirs as $part ) {
			$dir .= $part . '/';
			if (! is_dir ( $dir ) && strlen ( $dir ) > 0) {
				if (! mkdir ( $dir, $rights )) {
					return false;
				}
			}
		}
		return true;
	}
}

class DBService {
	public static $connected = false;
	public static $map = array ();
	public static $defaultDb = null;

	public static function getDB() {
		if (self::$defaultDb == null) {
			self::$defaultDb = self::initDB ( Config::getProperty ( "GLOBAL", "DEFAULT_DB" ) );
		}
		return self::$defaultDb;
	}
	public static function close($configname = NULL) {
		if ($configname == NULL && self::$defaultDb != null) {
			self::$defaultDb->close ();
		}
	}
	public static function initDB($configname) {
		if (! self::$connected) {
			include_once ("db/AbstractDb.php");
			self::$connected = true;
		}
		self::$map [$configname] = new AbstractDb ( Config::getSection ( $configname ) );
		return self::$map [$configname];
	}
}

?>