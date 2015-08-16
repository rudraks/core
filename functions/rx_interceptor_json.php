<?php

/**
 *  Whatever Data is returned by Hanlder, should be an array will be rendered as array on response
 * 
 * @param AbstractUser $user
 * @param array $controllerInfo
 * @param String $handlerName
 */
function rx_interceptor_json($user, $controllerInfo, $handlerName) {
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
			try{
				if ($tempClass->hasMethod ( "invokeHandler" )) {
					$resp = call_method_by_class ( $tempClass, $temp, 'invokeHandler', array (
							'user' => $user,
							'data' => new RequestData ( get_request_param ( "data" ) )
					), $handlerInfo ["requestParams"] );
					if (isset ( $resp ))
						echo json_encode ( $resp );
				}	
			} catch (Exception $e){
				echo json_encode (array(
						"error" => $e 
				));
			}
		}
	}
}