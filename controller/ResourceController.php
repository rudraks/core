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
	
	/** Default RudraX Plug
	 *
	 * @RequestMapping(url="cache.manifest",type=data,cache = false)
	 *
	 */
	function manifest($mdfile){
		header('Content-type: text/cache-manifest');
		header('Cache-Control: no-cache');
		include_once (RUDRA . "/smarty/Smarty.class.php");
		$tpl = new Smarty ();
		call_user_func ( rx_function ( "rx_set_smarty_paths" ), ($tpl) );
		$tpl->display ( get_include_path () . RUDRA . "/core/view/manifest.tpl" );
		//readfile("../".RUDRA."/core/offline/cache.manifest");
	}
	
	/** Default RudraX Plug
	 *
	 * @RequestMapping(url="offline.html",type=data,cache = true)
	 *
	 */
	function offline($mdfile){
		header('Content-type: text/html');
		header('Cache-Control: no-cache');
		readfile("../".RUDRA."/core/offline/offline.html");
	}
}
