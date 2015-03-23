<?php
function is_remote($file_name) {
	return strpos($file_name, '://') > 0 ? 1 : 0;
}
function is_remote_file($file_name) {
	return is_remote($file_name) && preg_match("#\.[a-zA-Z0-9]{1,4}$#", $file_name) ? 1 : 0;
}

function replace_first($search,$replace="",$subject=""){
	$pos = strpos($subject,$search);
	if ($pos !== false) {
		$newstring = substr_replace($subject,$replace,$pos,strlen($search));
	}
	return $newstring;
}

function print_js_comment($str){
	echo "/*  ".$str." */";
}

function resolve_path($str){
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

function removecookie ($key,$context="/"){
	if (isset($_COOKIE[$key])) {
		unset($_COOKIE[$key]);
		setcookie($key, null, -1, $context);
		return true;
	} else {
		return false;
	}
}

function rx_function($callback){
	include_once 'functions/'.$callback.".php";
	return $callback;
}

//ERROR TRACE BACK FUNCTION
function process_error_backtrace($errno, $errstr, $errfile, $errline, $errcontext) {
	if(!(error_reporting() & $errno))
		return;
	switch($errno) {
		case E_WARNING      :
		case E_USER_WARNING :
		case E_STRICT       :
		case E_NOTICE       :
		case E_USER_NOTICE  :
			$type = 'warning';
			$fatal = false;
			break;
		default             :
			$type = 'fatal error';
			$fatal = true;
			break;
	}
	$trace = array_reverse(debug_backtrace());
	array_pop($trace);
	if(php_sapi_name() == 'cli') {
		echo 'Backtrace from ' . $type . ' \'' . $errstr . '\' at ' . $errfile . ' ' . $errline . ':' . "\n";
		foreach($trace as $item)
			echo '  ' . (isset($item['file']) ? $item['file'] : '<unknown file>') . ' ' . (isset($item['line']) ? $item['line'] : '<unknown line>') . ' calling ' . $item['function'] . '()' . "\n";
	} else {
		echo '<p class="error_backtrace">' . "\n";
		echo '  Backtrace from ' . $type . ' \'' . $errstr . '\' at ' . $errfile . ' ' . $errline . ':' . "\n";
		echo '  <ol>' . "\n";
		foreach($trace as $item)
			echo '    <li>' . (isset($item['file']) ? $item['file'] : '<unknown file>') . ' ' . (isset($item['line']) ? $item['line'] : '<unknown line>') . ' calling ' . $item['function'] . '()</li>' . "\n";
		echo '  </ol>' . "\n";
		echo '</p>' . "\n";
	}
	if(ini_get('log_errors')) {
		$items = array();
		foreach($trace as $item)
			$items[] = (isset($item['file']) ? $item['file'] : '<unknown file>') . ' ' . (isset($item['line']) ? $item['line'] : '<unknown line>') . ' calling ' . $item['function'] . '()';
		$message = 'Backtrace from ' . $type . ' \'' . $errstr . '\' at ' . $errfile . ' ' . $errline . ': ' . join(' | ', $items);
		error_log($message);
	}
	if($fatal)
		exit(1);
}

set_error_handler('process_error_backtrace');
//////////