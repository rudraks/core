<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*
*/
include_once(RUDRA."/core/controller/AbstractController.php");

class ResourceController extends AbstractController {

	/** Default RudraX Plug
	 *
	 * @RequestMapping(url="combinejs/{mdfile}",type=js)
	 *
	 */
	function combineJs(){
		include_once (RUDRA . "/core/handler/ResourceHandler.php");
		$handler = new ResourceHandler();
		$handler->invokeHandler();
	}
	
	/** Default RudraX Plug
	 *
	 * @RequestMapping(url="scss/{mdfile}",type=css)
	 *
	 */
	function serveStyle($mdfile){
		include_once (LIB_PATH . "/leafo/scssphp/scss.inc.php");
		$scss = new scssc();
		$scss->setFormatter("scss_formatter_compressed");
		$server = new scss_server(get_include_path(), get_include_path().BUILD_PATH."/scss/", $scss);
		$server->serve();
	}
	
}
