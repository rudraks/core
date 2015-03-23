<?php

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
*/

abstract class AbstractController {

	public $user;
	
	public function loadSession(){
		$UserClass = ClassUtil::getSessionUserClass();
		$this->user = new $UserClass;
	}

	public function setUser(AbstractUser $user){
		$this->user = $user;
	}

	public function getUser(){
		return $this->user;
	}

	public function _interceptor_($info,$controllerOutput) {
		switch ($info["type"]) {
			case "page":
				$this->_pageInterceptor_($info,$controllerOutput);
				break;
			case "template":
				$this->_templateInterceptor_($info,$controllerOutput);
				break;
			case "json":
				$this->_jsonInterceptor_($info,$controllerOutput);
				break;
			case "data":
				$this->_dataInterceptor_($info,$controllerOutput);
				break;
			default:
				break;
		}
	}

	public function _pageInterceptor_($info,$controllerOutput){
		return call_user_func(rx_function("rx_interceptor_page"),$this->user, $controllerOutput);
	}
	
	public function _templateInterceptor_($info,$controllerOutput){
		return call_user_func(rx_function("rx_interceptor_template"),$this->user, $controllerOutput);
	}
	
	public function _jsonInterceptor_($info,$controllerOutput){
		return call_user_func(rx_function("rx_interceptor_json"),$this->user, $controllerOutput);
	}
	
	public function _dataInterceptor_($info,$controllerOutput){
		return call_user_func(rx_function("rx_interceptor_data"),$this->user, $controllerOutput);
	}

}
