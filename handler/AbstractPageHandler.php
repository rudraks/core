<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/
include_once (RUDRA . "/smarty/Smarty.class.php");
include_once (RUDRA_MODEL . "/Page.php");
include_once (RUDRA_MODEL . "/Header.php");

abstract class AbstractPageHandler extends AbstractSmartyHandler {
	public function _invokeHandler(User $user, $handlerName,$handlerClass){
		$tpl = new Smarty();
		self::setSmartyPaths($tpl);
		$tpl->debugging = Config::get('SMARTY_DEBUG');
		$page = new Page();

		$header = new Header();
		$view = RudraX::invokeMethodByReflectionClass($handlerClass,$this,'invokeHandler',
				$this->getHandlerParams(array (
						'tpl' => $tpl,
						'viewModel' => $tpl,
						'user' => $user,
						'header' => $header,
						'page' => $page,
						'dataModel' => $page->data
				)));
		if (! isset($view )) {
			$view = $handlerName;
		}
		$header->minify();
		$tpl->assign('METAS',$header->metas);
		$tpl->assign('TITLE',$header->title);
		$tpl->assign('CSS_FILES',$header->css);
		$tpl->assign('SCRIPT_FILES',$header->scripts);
		$tpl->assign('BODY_FILES',$view . Config::get('TEMP_EXT'));
		$tpl->assign('page_json',json_encode($page->data->data));

		$tpl->assign('user',$user);
		$tpl->assign('page',$page);
		$tpl->assign('CONTEXT_PATH',CONTEXT_PATH);
		$tpl->assign('RESOURCE_PATH',Config::get('RESOURCE_PATH'));

		$view_path = get_include_path().RUDRA."/core/view/full.tpl";
		$tpl->display($view_path);

		if(BROWSER_LOGS){
			Browser::printlogs();
		}
	}
}