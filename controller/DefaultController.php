<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*
*/
include_once(RUDRA."/core/controller/AbstractController.php");


class DefaultController extends AbstractController {

	/**
	 * @RequestMapping(url="template/{temp}",type=template)
	 */
	public function renderTemplate($temp="index"){
		return $temp;
	}
	
	/**
	 * @RequestMapping(url="data/{action}",type=data)
	 */
	public function dataHandler($action="dataHandler"){
		return $action;
	}
	
	/**
	 * @RequestMapping(url="",type=page)
	 */
	public function sampleDefault ($page="index"){
		echo "DEFAUL CONTROLER IS NOT SET";
		return $page;
	}

}
