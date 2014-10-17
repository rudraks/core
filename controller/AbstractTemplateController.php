<?php

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
*/
include_once (LIB_PATH . "/rudrax/smarty/Smarty.class.php");
include_once (RUDRA . "/core/controller/AbstractController.php");
include_once (RUDRA . "/core/model/Page.php");

class AbstractTemplateController extends AbstractController {

	public function getHandlerPath() {
		return "";
	}

	public function getViewPath() {
		return "";
	}

	public function invoke(User $user, $handlerName) {
		$className = ucfirst($handlerName );
		$user->validate();
		include_once(RUDRA . "/core/handler/AbstractHandler.php");
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
				$temp->setTemplate($tpl);
				$page = new Page();
				$view = RudraX::invokeMethodByReflectionClass($tempClass,$temp,'invokeHandler',array(
						'tpl' => $tpl,
						'viewModel' => $tpl,
						'user' => $user,
						'page' => $page,
						'dataModel' => $page->data
				));
				//$view = $temp->invokeHandler($tpl );
				if (! isset($view )) {
					$view = $handlerName;
				}

				$tpl->assign('user',$user);
				$tpl->assign('CONTEXT_PATH',CONTEXT_PATH);
				$tpl->assign('RESOURCE_PATH',Config::get('RESOURCE_PATH'));
				
				if(isset($tpl->repeatData)){
					foreach($tpl->repeatData as $key=>$value){
						$tpl->assign($value['key'],$value['value']);
						$tpl->display($this->getViewPath() . $view . Config::get('TEMP_EXT'));
					}
				} else {
					$tpl->display($this->getViewPath() . $view . Config::get('TEMP_EXT'));
				}
				if(BROWSER_LOGS){
					Browser::printlogs();
				}
				echo "::rx::data::".json_encode($page->data->data);
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
