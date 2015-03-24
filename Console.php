<?php

/*
 * @category Lib @package Test Suit @copyright 2011, 2012 Dmitry Sheiko (http://dsheiko.com) @license GNU
*/

/**
 * Logger in FireBug fashion
 */
class Console {
	private static $MODE = false;
	private static $LOGS = false;
	private static $LOGS_PATH = false;
	private $messages = array();
	private $mtime;
	public static $log_fun = "console.log";

	public function  __construct(){
		$this->mtime = microtime( true );
	}
	public static function set($set) {
		self::$MODE = $set;
	}
	public static function createLogFiles($set) {
		self::$LOGS = $set;
		if(self::$LOGS){
			self::$LOGS_PATH = get_include_path() . Config::get('BUILD_PATH').'/logs';
			if (!file_exists(self::$LOGS_PATH)) {
				if(!mkdir(self::$LOGS_PATH, 0777, true)){
					die('Failed to create folders:'.self::$LOGS_PATH);
				};
			}
		}
	}

	/**
	 * Decorator for trace info
	 *
	 * @param array $trace
	 *        	- debug_backtrace results
	 * @return string
	 */
	private static function _debugDecorator($trace) {
		return sprintf ( "%s : %d >", str_replace ( BASE_PATH, "", $trace [0] ["file"] ), $trace [0] ["line"] );
	}

	/**
	 * Logger implementation
	 *
	 * @param array $args
	 * @param string $logName
	 */
	private static function _log($args, $logName = "error") {
		if(self::$LOGS && $args=null){
			$logExt = "." . date ( "Y-m-d" ) . ".log";
			foreach ( $args as $data ) {
				file_put_contents ( self::$LOGS_PATH . "/" . $logName . $logExt,
				print_r ( $data, 1 ) . "\n##################################################################\n",
				FILE_APPEND );
			}
		}
		if (self::$MODE && isset($data)) {
			echo $data . "<br/>";
		}
	}

	/**
	 * Generic logger alias
	 * Usage: console::log(mixed, mixed, .
	 * .)
	 */
	public static function log() {
		$trace = debug_backtrace ();
		$args = func_get_args ();
		$args = array_merge ( array (
				self::_debugDecorator ( $trace )
		), $args );
		self::_log ( $args, 'info' );
	}
	public static function error() {
		$trace = debug_backtrace ();
		$args = func_get_args ();
		$args = array_merge ( array (
				self::_debugDecorator ( $trace )
		), $args );
		self::_log ( $args, 'error' );
	}
	public static function exception(Exception $e) {
		$trace = debug_backtrace ();
		$args = func_get_args ();
		$args = array_merge ( array (
				self::_debugDecorator ( $trace )
		), $args );
		self::_log ( $args, 'exception' );
	}

	public function browser($msgData,$trace=null,$logFun="console.log"){
		if($trace==null){
			$trace = debug_backtrace();
		}
		$this->messages[][$trace[0]["file"].'('.$trace[0]["line"].')'] = array( 'msg' => $msgData, 'fun' => $logFun);
	}
	/**
	 * Output any return data to the javascript console/source of page
	 * Usage (assuming minifier is initiated as $minifier):
	 * <?php $console->printlogs(); ?>
	 *
	 * @param none
	 * @return string
	 */
	public function printlogs(){
		//Add the timer the console.log output if desired
		$this->messages[]['Rudrax:done'] = 'Script processed and loaded in '. ( microtime( true ) - $this->mtime ) .' seconds';
		if( !empty( $this->messages ) ){
			echo '<script>';
			foreach( $this->messages as $this->data ){
				foreach( $this->data as $file =>  $msgObj){
					if(is_string($msgObj)){
						echo self::$log_fun.'("'.$file .' : ","'. $msgObj.'");' . PHP_EOL;
					} else {
						echo $msgObj['fun'].'("'.$file .' : ",'. $msgObj['msg'].');' . PHP_EOL;
					}
				}
			}
			echo '</script>';
		} //end !empty( $this-messages )
	} //end logs()
	
	public function printlogsOnHeader(){
		//Add the timer the console.log output if desired
		$this->messages[]['Rudrax:done'] = 'Script processed and loaded in '. ( microtime( true ) - $this->mtime ) .' seconds';
		$logs = "";
		if( !empty( $this->messages ) ){
			foreach( $this->messages as $this->data ){
				foreach( $this->data as $file =>  $msgObj){
					if(is_string($msgObj)){
						$logs.=('["'.$file .' : ","'. $msgObj.'"]');
					} else {
						$logs.=('["'.$file .' : ",'. $msgObj['msg'].'];');
					}
				}
			}
			header("X-BLOGS:".$logs);
		} //end !empty( $this-messages )
	} //end logs()

}
