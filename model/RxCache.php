<?php


//include_once(LIB_PATH . "/Cache.php");
include(LIB_PATH."/rudrax/phpfastcache/phpfastcache.php");

class RxCache {

	public static $cache;
	public $prefix;

	public function  __construct($prefix="GLOBAL"){
		$this->prefix = $prefix."::";
// 		if($this->cache==NULL){
// 			$this->cache = new Memcache;
// 		}
		$this::$cache = new phpFastCache();
	}

	public function set($key,$object,$timeout = 60){
		return ($this::$cache ) ? $this::$cache ->set($this->prefix.$key,$object) : false;
		//return Cache::save ($this->prefix.$key , $object,$timeout);
		return apc_store ($this->prefix.$key , $object,$timeout);
		//return ($this->cache) ? $this->cache->set($key,$object,MEMCACHE_COMPRESSED,$timeout) : false;
	}
	public function get($key){
		return ($this::$cache ) ? $this::$cache ->get($this->prefix.$key) : false;
		//return Cache::get($this->prefix.$key) ;
		return apc_fetch ($this->prefix.$key);
		//return ($this->cache) ? $this->cache->get($key) : false;
	}
}
