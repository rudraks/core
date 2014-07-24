<?php

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
*/

include_once (RUDRA . "/core/model/AbstractUser.php");

abstract class AbstractController {

	public $user;

	public function  __construct(){
		$this->user = new User();
	}

	public function getHandlerName() {
		return $_GET[PAGE_PARAM];
	}

	public function preRequest(User $user, $handlerName) {
		return true;
	}

	public function postRequest(User $user, $handlerName) {
		return true;
	}

	public function invokeHandler($handlerName) {
		if ($this->preRequest($this->user, $handlerName )) {
			$this->invoke($this->user, $handlerName );
			$this->postRequest($this->user, $handlerName );
		}
		$this->user->save();
	}

	abstract public function invoke(User $user, $handlerName);

}

abstract class AbstractSmartyController extends AbstractController{

	protected static function setSmartyPaths(Smarty $viewModel){
		$viewModel->setTemplateDir(get_include_path() .Config::get('VIEW_PATH'));
		$viewModel->setConfigDir(get_include_path() . Config::get('CONFIG_PATH'));
		$CACHE_PATH = get_include_path() . Config::get('BUILD_PATH').'/cache';
		if (!file_exists($CACHE_PATH)) {
			if(!mkdir($CACHE_PATH, 0777, true)){
				die('Failed to create folders:'.$CACHE_PATH);
			};
		}
		$viewModel->setCacheDir($CACHE_PATH);
		$TEMP_PATH = get_include_path() . Config::get('BUILD_PATH').'/temp';
		if (!file_exists($TEMP_PATH)) {
			if(!mkdir($TEMP_PATH, 0777, true)){
				die('Failed to create folders:'.$TEMP_PATH);
			};
		}
		$viewModel->setCompileDir($TEMP_PATH);
	}

}
