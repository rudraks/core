<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/
/**
 * @author lalittanwar
 * 
 * @Handler(index)
 */
class SampleIndexHandler extends AbstractHandler {

	public function invokeHandler(Smarty $viewModel,Header $header, DataModel $dataModel,
			AbstractUser $user,$view="empty") {
		$header->title("My Website");
		$header->import("default_bundle");
		return $view;
	}

}
