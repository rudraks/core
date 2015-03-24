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
			User $user,$view="empty") {
		$header->title("Sample Handler Test");
		return $view;
	}

}
