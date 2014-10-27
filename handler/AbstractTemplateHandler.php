<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

include_once (LIB_PATH . "/rudrax/smarty/Smarty.class.php");
include_once (RUDRA . "/core/model/Page.php");

abstract class AbstractTemplateHandler extends AbstractSmartyHandler {
	public function _invokeHandler(User $user, $handlerName,$handlerClass){
		$tpl = new Smarty();
		self::setSmartyPaths($tpl);
		$tpl->debugging = Config::get('SMARTY_DEBUG');
		$page = new Page();
		$view_path;

		$view = RudraX::invokeMethodByReflectionClass($handlerClass,$this,'invokeHandler',
				$this->getHandlerParams(array(
						'tpl' => $tpl,
						'viewModel' => $tpl,
						'user' => $user,
						'page' => $page,
						'dataModel' => $page->data
				)));
		if (! isset($view )) {
			$view = $handlerName;
		}

		$tpl->assign('user',$user);
		$tpl->assign('page',$page);
		$tpl->assign('CONTEXT_PATH',CONTEXT_PATH);
		$tpl->assign('RESOURCE_PATH',Config::get('RESOURCE_PATH'));

		$view_path =  $this->getViewPath() . $view . Config::get('TEMP_EXT');
		if(isset($tpl->repeatData)){
			foreach($tpl->repeatData as $key=>$value){
				$tpl->assign($value['key'],$value['value']);
				$tpl->display($view_path);
			}
		} else {
			$tpl->display($view_path);
		}
		if(BROWSER_LOGS){
			Browser::printlogs();
		}
		if($isTemplate){
			echo "::rx::data::".json_encode($page->data->data);
		}
	}
}