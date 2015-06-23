<?php

// include_once(LIB_PATH . "/Cache.php");
class RxCache {
	public static $cache;
	public $prefix;
	public $hard;
	public $hard_file;
	public static $cache_array = array ();
	public $dirty = false;
	public $name = "GLOBAL";
	public function __construct($prefix = "GLOBAL", $hard = FALSE, $hard_array = array()) {
		$this->hard = $hard;
		$this->name = $prefix;
		if (! $this->hard) {
			$this->prefix = $prefix . "::";
			// if($this->cache==NULL){
			// $this->cache = new Memcache;
			// }
			include_once (RUDRA . "/phpfastcache/phpfastcache.php");
			$this::$cache = new phpFastCache ();
		} else {
			$this->hard_file = BUILD_PATH.'rc_'.PROJECT_ID."_" .$prefix . '.php';
			if ($this->exists ()) {
				if (! isset ( self::$cache_array [$this->name] )) {
					self::$cache_array [$this->name] = include $this->hard_file;
				}
			} else {
				self::$cache_array [$this->name] = $hard_array;
				$this->dirty = true;
			}
		}
	}
	public function set($key, $object, $timeout = 60) {
		if (! $this->hard) {
			return ($this::$cache) ? $this::$cache->set ( $this->prefix . $key, $object ) : false;
			// return Cache::save ($this->prefix.$key , $object,$timeout);
			return apc_store ( $this->prefix . $key, $object, $timeout );
			// return ($this->cache) ? $this->cache->set($key,$object,MEMCACHE_COMPRESSED,$timeout) : false;
		} else {
			self::$cache_array [$this->name] [$key] = $object;
			$this->dirty = true;
		}
	}
	public function get($key, $default = false) {
		if (! $this->hard) {
			return ($this::$cache) ? $this::$cache->get ( $this->prefix . $key ) : $default;
			// return Cache::get($this->prefix.$key) ;
			return apc_fetch ( $this->prefix . $key );
			// return ($this->cache) ? $this->cache->get($key) : false;
		} else {
			if ($this->hasKey ( $key )) {
				return self::$cache_array [$this->name] [$key];
			} else {
				$this->set ( $key, $default );
				return $default;
			}
		}
	}
	public function hasKey($key) {
		return isset ( self::$cache_array [$this->name] [$key] );
	}
	public function save($check = false) {
		header ( "X-C-" . $this->name . ": FALSE" );
		if (! $check || $this->dirty) {
			header ( "X-C-" . $this->name . ": TRUE" );
			file_put_contents ( $this->hard_file, '<?php return ' . var_export ( self::$cache_array [$this->name], true ) . ';' );
		}
	}
	public function merge($cache_array) {
		self::$cache_array [$this->name] = array_merge ( self::$cache_array [$this->name], $cache_array );
	}
	public function getArray() {
		return self::$cache_array [$this->name];
	}
	public function exists() {
		return file_exists ( $this->hard_file );
	}
	public function isEmpty() {
		return ! file_exists ( $this->hard_file );
	}
	public function clear() {
		if ($this->exists ()) {
			unlink ( $this->hard_file );
			self::$cache_array [$this->name] = null;
		}
	}
}
