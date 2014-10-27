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

	public function getHandlerParams($params){
		return $params;
	}
	public function _invokeHandler(User $user, $handlerName,$handlerClass){
		if ($handlerClass->hasMethod("invokeHandler" )) {
			$resp =  RudraX::invokeMethodByReflectionClass($handlerClass,$this,'invokeHandler',array(
					'user' => $user
			));
			if(isset($resp)) echo  $resp;
		}
	}
}
abstract class AbstractSmartyHandler extends AbstractHandler {
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
