<?php


//include_once(LIB_PATH . "/Cache.php");

class RxCache {

	public static $cache;
	public $prefix;
	public $hard;
	public $hard_file;
	public $cache_array;
	public $dirty = false;

	public function  __construct($prefix="GLOBAL",$hard=FALSE,$hard_array = array()){
		$this->hard = $hard;
		if(!$this->hard){
			$this->prefix = $prefix."::";
			// 		if($this->cache==NULL){
			// 			$this->cache = new Memcache;
			// 		}
			include(RUDRA ."/phpfastcache/phpfastcache.php");
			$this::$cache = new phpFastCache();
		} else {
			$this->hard_file = '../build/rx_cache_'.$prefix.'.php';
			if($this->exists()){
				$this->cache_array = include_once $this->hard_file;
				$this->dirty = true;
			} else {
				$this->cache_array = $hard_array;
				$this->dirty = false;
			}
		}
	}

	public function set($key,$object,$timeout = 60){
		if(!$this->hard){
			return ($this::$cache ) ? $this::$cache ->set($this->prefix.$key,$object) : false;
			//return Cache::save ($this->prefix.$key , $object,$timeout);
			return apc_store ($this->prefix.$key , $object,$timeout);
			//return ($this->cache) ? $this->cache->set($key,$object,MEMCACHE_COMPRESSED,$timeout) : false;
		} else {
			$this->cache_array[$key] = $object;
			$this->dirty = true;
		}
	}
	public function get($key){
		if(!$this->hard){
			return ($this::$cache ) ? $this::$cache ->get($this->prefix.$key) : false;
			//return Cache::get($this->prefix.$key) ;
			return apc_fetch ($this->prefix.$key);
			//return ($this->cache) ? $this->cache->get($key) : false;
		} else {
			return $this->hasKey($key) ? $this->cache_array[$key] : NULL;
		}
	}
	
	public function hasKey($key){
		return isset($this->cache_array[$key]);
	}
	
	public function save($check=false){
		if(!$check || $this->dirty){
			file_put_contents($this->hard_file, '<?php return ' . var_export($this->cache_array, true) . ';');
		}
	}
	
	public function merge($cache_array){
		$this->cache_array = array_merge($this->cache_array,$cache_array);
	}
	
	public function getArray(){
		return $this->cache_array;
	}
	
	public function exists(){
		return file_exists($this->hard_file);
	}
	public function isEmpty(){
		return !file_exists($this->hard_file);
	}
}
