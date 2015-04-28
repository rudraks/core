<?php

/**
 * 
 * @author lt
 *
 */
class RequestData {
	private $map = array ();
	public function __construct($map) {
		$this->map = $map;
	}
	public function get($key, $defaultValue = NULL) {
		if (! isset ( $this->map [$key] )) {
			return $defaultValue;
		} else {
			return $this->map [$key];
		}
	}
	public function set($key, $value) {
		$this->map [$key] = $value;
		return $this;
	}
}
