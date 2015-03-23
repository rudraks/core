<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

abstract class AbstractHandler {

	public function requestGet($key) {
		if (isset($_GET[$key])) {
			return $_GET[$key];
		} return "";
	}
	public function requestPost($key) {
		if (isset($_POST[$key])) {
			return $_POST[$key];
		} return FALSE;
	}
}
