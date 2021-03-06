<?php

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
*/
include_once (RUDRA . "/core/controller/AbstractRxController.php");

class AbstractDataController extends AbstractRxController {

	public function getHandlerPath() {
		return "";
	}

	public function invoke(User $user, $handlerName) {
		$className = ucfirst($handlerName );
		$user->validate();
		include_once(RUDRA . "/core/handler/AbstractHandler.php");
		include_once (HANDLER_PATH . "/" . $this->getHandlerPath() . $className . ".php");
		$tempClass = new ReflectionClass($className );
		global $temp;
		if ($tempClass->isInstantiable()) {
			$temp = $tempClass->newInstance();
		}

		if ($temp != NULL) {
			if ($tempClass->hasMethod("invokeHandler" )) {
				$resp =  RudraX::invokeMethodByReflectionClass($tempClass,$temp,'invokeHandler',array(
						'user' => $user
				));
				if(isset($resp)) echo  $resp;
			}
		}
	}
}
