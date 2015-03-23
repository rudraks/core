<?php

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
*/
include_once (LIB_PATH . "/rudrax/smarty/Smarty.class.php");
include_once (RUDRA . "/core/controller/AbstractRxController.php");
include_once (RUDRA . "/core/model/Header.php");
include_once (RUDRA . "/core/model/Page.php");

class AbstractPageController extends AbstractRxController {

	public function getHandlerPath() {
		return "";
	}

	public function getViewPath() {
		return "";
	}

	public function invoke(User $user, $handlerName) {
		return call_user_func(rx_function("rx_page_interceptor"),$user, $handler);
	}
}
