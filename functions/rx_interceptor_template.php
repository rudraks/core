<?php
include_once (RUDRA . "/smarty/Smarty.class.php");

function rx_interceptor_template($user, $info, $handlerName, HttpRequest $request) {
	$user->validate ();
	include_once (RUDRA . "/core/handler/AbstractHandler.php");
	
	$handlerInfo = ClassUtil::getHandler ( $handlerName );
	
	if ($handlerInfo != NULL) {
		global $temp;
		include_once $handlerInfo ["filePath"];
		$className = $handlerInfo ["className"];
		
		$tempClass = new ReflectionClass ( $className );
		if ($tempClass->isInstantiable ()) {
			$temp = $tempClass->newInstance ();
		}
		
		if ($temp != NULL) {
			
			if ($tempClass->hasMethod ( "invokeHandler" )) {
				$tpl = new Smarty ();
				call_user_func ( rx_function ( "rx_set_smarty_paths" ), ($tpl) );
				
				$tpl->debugging = RX_SMARTY_DEBUG;
				$page = new Page ();
				$view = RudraX::invokeMethodByReflectionClass ( $tempClass, $temp, 'invokeHandler', array (
						'tpl' => $tpl,
						'viewModel' => $tpl,
						'user' => $user,
						'page' => $page,
						'dataModel' => $page->data,
						'data' => new RequestData ( $request->get ( "data" ) ) 
				) );
				if (! isset ( $view )) {
					$view = $handlerName;
				}
				
				$tpl->assign ( 'user', $user );
				$tpl->assign ( 'CONTEXT_PATH', CONTEXT_PATH );
				$tpl->assign ( 'RESOURCE_PATH', RESOURCE_PATH );
				
				if (isset ( $tpl->repeatData )) {
					foreach ( $tpl->repeatData as $key => $value ) {
						$tpl->assign ( $value ['key'], $value ['value'] );
						$tpl->display ( $this->getViewPath () . $view . Config::get ( 'TEMP_EXT' ) );
					}
				} else {
					$tpl->display ( $this->getViewPath () . $view . Config::get ( 'TEMP_EXT' ) );
				}
				echo TEMP_DELIMITER;
				Browser::printlogs ();
				echo TEMP_DELIMITER . json_encode ( $page->data->data );
				
				Browser::printlogs ();
			}
		}
	}
}