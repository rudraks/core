<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

class AbstractHandler {

	public $tpl;
	public $user;

	public function setUser($user) {
		$this->user = $user;
	}

	public function getUser() {
		return $this->user;
	}

	public function setTemplate($tpl) {
		$this->tpl = $tpl;
	}

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

	public function setValue($key, $value) {
		$this->tpl->assign($key, $value);
	}

	public function selectBlock($blockName) {
		return $this->tpl->newBlock($blockName);
	}

	public function gotoBlock($blockName) {
		return $this->tpl->newBlock($blockName);
	}
}