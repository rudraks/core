<?php

class EventRequest extends AbstractRequest {
	private $map = array();
	public function get($key){
		if(isset($this->map[$key])){
			return $this->map[$key];
		}
	}
	public function set($key,$value){
		$this->map[$key] = $value;
		return $this;
	}
	public function setData ($temp_map){
		$this->map = $temp_map;
	}
}
