<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/
include_once (RUDRA . "/smarty/Smarty.class.php");
include_once (RUDRA_MODEL . "/Page.php");
include_once (RUDRA_MODEL . "/Header.php");

abstract class AbstractDataHandler extends AbstractHandler {
	public function _invokeHandler(User $user, $handlerName,$handlerClass){

		if ($handlerClass->hasMethod("invokeHandler" )) {
			//$eventRequest = new EventRequest();
			//$eventRequest->setData();
			$resp =  RudraX::invokeMethodByReflectionClass($handlerClass,$this,'invokeHandler',array(
					'user' => $user
			));
			if(isset($resp)) echo  $resp;
		}
	}
}
