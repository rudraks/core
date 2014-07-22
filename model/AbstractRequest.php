<?php


abstract class  AbstractRequest {
	abstract public function get($key);
	abstract public function set($key,$value);
}
class HttpRequest extends AbstractRequest {
	private static $request;
	private $map = array();
	public function get($key,$skipEmpty=FALSE){
		if(isset($this->map[$key]) && !($skipEmpty  && empty($this->map[$key]))){
			return $this->map[$key];
		} else if(isset($_REQUEST[$key]) && !($skipEmpty  && empty($_REQUEST[$key]))){
			return $_REQUEST[$key];
		} else {
			return NULL;
		}
	}
	public function set($key,$value){
		$this->map[$key] = $value;
		return $this;
	}
	public function getAllParams(){
		return $this->map;
	}
	public function loadParams($params){
		$this->map = array_merge($this->map,$params);
		return $this;
	}
	public static function getInstance(){
		if(!isset(self::$request)){
			self::$request = new HttpRequest();
		}
		return self::$request;
	}
}

