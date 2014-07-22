<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

/**
 * Description of Header
 *
 * @author Lalit Tanwar
 */
class DataModel {

	public $data = array();

	public function assign($key,$value){
		$this->data[$key] = $value;
	}
}
