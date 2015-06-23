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
		//echo "Booba";
		$handler = new ResourceHandler();
		$handler->invokeHandler();
	}
	
	/** Default RudraX Plug
	 *
	 * @RequestMapping(url="buildfile/js/{mdfile}")
	 *
	 */
	function buildJSFile($mdfile,$q){
		include_once (RUDRA . "/core/model/Header.php");
		$hdr = new Header(); 
		$version = "-_".$_REQUEST["_"];
		$target = str_replace ("buildfile/js/","", $_GET['q']);
		$source = str_replace ($version,"", $target);
		print_js_comment($target,$source,$version);
		$hdr->printMinifiedJs($source, $target);
	}
	
	
	/** Default RudraX Plug
	 *
	 * @RequestMapping(url="buildfile/css/{mdfile}")
	 *
	 */
	function buildFile($mdfile,$q){
		include_once (RUDRA . "/core/model/Header.php");
		$hdr = new Header();
		$version = "-_".$_REQUEST["_"];
		$target = str_replace ("buildfile/css/","", $_GET['q']);
		$source = str_replace ($version,"", $target);
		print_js_comment($target,$source,$version);
		if(!$hdr->printMinifiedCSS($source, $target) && ENABLE_SCSS_PHP){
			print_js_comment("ENABLE_SCSS_PHP");
			//header('HTTP/1.1 301 Moved Permanently');
			//header('Location: '.CONTEXT_PATH.str_replace(".css", ".scss", $source)."?_=".RELOAD_VERSION);
		}
		
	}
	
	/** Default RudraX Plug
	 *
	 * @RequestMapping(url="buildfile/json/{version}")
	 *
	 */
	function bundleJson($version){
		include_once (RUDRA . "/core/model/Header.php");
		Header::init(true);
		FileUtil::read(Header::get_build_file_path());
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
	
	/** Default RudraX Plug
	 *
	 * @RequestMapping(url="offline.js",type=data,cache = true)
	 *
	 */
	function offlineJS($mdfile){
		header('Content-type: application/javascript');
		header('Cache-Control: no-cache');
		readfile("../".RUDRA."/core/offline/offline.js");
	}
	/** Default RudraX Plug
	 *
	 * @RequestMapping(url="offline.css",type=data,cache = true)
	 *
	 */
	function offlineCSS($mdfile){
		header('Content-type: text/css');
		header('Cache-Control: no-cache');
		readfile("../".RUDRA."/core/offline/offline.css");
	}
}
