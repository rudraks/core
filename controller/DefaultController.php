<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*
*/
include_once(RUDRA."/core/controller/AbstractController.php");


class DefaultController extends AbstractController {


	/* Default RudraX Plug
	 * 
	 * @RequestMapping(url="combinejs/{mdfile}",type=js)
	 * 
	 */
// 	function resourceHandler(){
// 		include_once (RUDRA . "/core/handler/ResourceHandler.php");
// 		$handler = new ResourceHandler();
// 		$handler->invokeHandler();
// 	}

// 		RudraX::mapRequest("template/{temp}",function($temp="nohandler"){
// 			return RudraX::invokeHandler($temp);
// 		});
// 		RudraX::mapRequest('data/{eventname}',function($eventName="dataHandler"){
// 			$controller = RudraX::getDataController();
// 			$controller->invokeHandler($eventName);
// 		});

// 			RudraX::mapRequest("resources.json",function($cb=""){
// 				require_once(RUDRA.'/core/model/Header.php' );
// 				echo $cb."((".json_encode(Header::getModules()).").bundles)";
// 			});


// 				// Default Plug for default page
// 				RudraX::mapRequest("",function(){
// 					return RudraX::invokeHandler("Index");
// 				});


}
