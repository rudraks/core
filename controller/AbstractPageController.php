<?php

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
*/
include_once (LIB_PATH . "/smarty/Smarty.class.php");
include_once (RUDRA . "/controller/AbstractController.php");
include_once (RUDRA . "/model/Header.php");
include_once(RUDRA . "/model/Page.php");

class AbstractPageController extends AbstractSmartyController {

	public function getHandlerPath() {
		return "";
	}

	public function getViewPath() {
		return "";
	}

	public function invoke(User $user, $handlerName) {
		$className = ucfirst($handlerName );
		$user->validate();
		include_once(RUDRA . "/handler/AbstractHandler.php");
		include_once (HANDLER_PATH . "/" . $this->getHandlerPath() . $className . ".php");
		$tempClass = new ReflectionClass($className );
		global $temp;
		if ($tempClass->isInstantiable()) {
			$temp = $tempClass->newInstance();
		}
		if ($temp != NULL) {
			$temp->setUser($user );

			if ($tempClass->hasMethod("invokeHandler" )) {
				$tpl = new Smarty();
				// $tpl->prepare();
				self::setSmartyPaths($tpl);
				// $tpl->testInstall(); exit;
				$tpl->debugging = Config::get('SMARTY_DEBUG');
				$temp->setTemplate($tpl );
				$header = new Header($tpl);
				$console = new Console();
				$header->console = $console;
				$page = new Page();
				$view = RudraX::invokeMethodByReflectionClass($tempClass,$temp,'invokeHandler',array(
					'tpl' => $tpl,
					'viewModel' => $tpl,
					'user' => $user,
					'header' => $header,
					'console' => $console,
					'page' => $page,
					'dataModel' => $page->data
				));
				//$view = $temp->invokeHandler($tpl );
				if (! isset($view )) {
					$view = $handlerName;
				}
				$header->minify();
				$tpl->assign('user',$user);
				$tpl->assign('page',$page);
				$tpl->assign('CONTEXT_PATH',CONTEXT_PATH);
				$tpl->assign('RESOURCE_PATH',Config::get('RESOURCE_PATH'));
				$tpl->assign('METAS',$header->metas);
				$tpl->assign('TITLE',$header->title);
				$tpl->assign('CSS_FILES',$header->css);
				$tpl->assign('SCRIPT_FILES',$header->scripts);
				$tpl->assign('BODY_FILES',$view . Config::get('TEMP_EXT'));
				$tpl->assign('page_json',json_encode($page->data->data));
				//echo get_include_path();
				//$tpl->display($this->getViewPath() . $view . Config::get('TEMP_EXT'));
				$tpl->display(get_include_path().RUDRA."/view/full.tpl");
				//$header->minified->logs();
				if(Config::get('JS_LOGS')){
					$console->printlogs();
				}
				
			} else if ($tempClass->hasMethod("invoke" )) {
				$view = $temp->invoke();
				if (! isset($view )) {
					$view = $handlerName;
				}
				include $this->getViewPath() . $view . '.php';
			}
		}
	}
}
