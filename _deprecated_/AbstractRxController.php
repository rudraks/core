<?php

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
*/

include_once (RUDRA . "/core/controller/AbstractController.php");

abstract class AbstractRxController {

	public $user;

	public function  __construct(){
		$this->user = new User();
	}

	public function getHandlerName() {
		return $_GET[PAGE_PARAM];
	}

	public function preRequest(AbstractUser $user, $handlerName) {
		return true;
	}

	public function postRequest(AbstractUser $user, $handlerName) {
		return true;
	}

	public function invokeHandler($handlerName) {
		if ($this->preRequest($this->user, $handlerName )) {
			$this->invoke($this->user, $handlerName );
			$this->postRequest($this->user, $handlerName );
		}
		$this->user->save();
	}
	public abstract function invoke(User $user, $handlerName);

}
