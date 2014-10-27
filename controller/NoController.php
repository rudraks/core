<?php

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
*/
include_once (RUDRA . "/core/controller/AbstractController.php");

class NoController extends AbstractController {

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
			if(is_subclass_of($temp, 'AbstractPageHandler')){
				include_once(RUDRA . "/core/handler/AbstractPageHandler.php");
			} else if(is_subclass_of($temp, 'AbstractTemplateHandler')){
				include_once(RUDRA . "/core/handler/AbstractTemplateHandler.php");
			} else if(is_subclass_of($temp, 'AbstractDataHandler')){
				include_once(RUDRA . "/core/handler/AbstractDataHandler.php");
			}
			$temp->_invokeHandler($user,$handlerName,$tempClass);
		}
	}
}
