<?php

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
*/
include_once (LIB_PATH . "/rudrax/smarty/Smarty.class.php");
include_once (RUDRA . "/core/controller/AbstractRxController.php");
include_once (RUDRA . "/core/model/Page.php");

class AbstractTemplateController extends AbstractRxController {

	public function getHandlerPath() {
		return "";
	}

	public function getViewPath() {
		return "";
	}

	public function invoke(User $user, $handlerName) {
		
		$user->validate();
		
		include_once(RUDRA . "/core/handler/AbstractHandler.php");
		
		$handleCache = new RxCache("handlers",true);
		
		global $temp;
		$className;
		if($handleCache->hasKey($handlerName)){
			$classObj = $handleCache->get($handlerName);
			include_once $classObj["filePath"];
			$className = $classObj["className"];
		} else {
			$className = ucfirst($handlerName );
			include_once (HANDLER_PATH . "/" . $this->getHandlerPath() . $className . ".php");
		}
		
		$tempClass = new ReflectionClass($className );
		if ($tempClass->isInstantiable()) {
			$temp = $tempClass->newInstance();
		}
		
		if ($temp != NULL) {

			if ($tempClass->hasMethod("invokeHandler" )) {
				$tpl = new Smarty();
				// $tpl->prepare();
				self::setSmartyPaths($tpl);
				// $tpl->testInstall(); exit;
				$tpl->debugging = Config::get('SMARTY_DEBUG');
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
				echo TEMP_DELIMITER;
				if(BROWSER_LOGS){
					Browser::printlogs();
				}
				echo TEMP_DELIMITER.json_encode($page->data->data);
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
