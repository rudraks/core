<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

include_once(RUDRA . "/core/model/DataModel.php");
/**
 * Description of Header
 *
 * @author Lalit Tanwar
*/

class Page {

	public $data = array();
	public $page_map = array();

	public function  __construct(){
		$this->data = new DataModel();
	}

	public function  set($key,$value){
		return $this->page_map[$key] = $value;
	}
	public function  get($key){
		if(isset($this->page_map[$key]))
			return $this->page_map[$key];
		else return NULL;
	}
}
