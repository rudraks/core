<?php
/**
 *  Whatever data is returned by handler will be echo'd as it is
 * 
 * @param unknown $user
 * @param unknown $handlerName
 */
function rx_interceptor_data($user, $info, $handlerName, HttpRequest $request) {
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
				$resp = RudraX::invokeMethodByReflectionClass ( $tempClass, $temp, 'invokeHandler', array (
						'user' => $user,
						'data' => new RequestData ( $request->get ( "data" ) ) 
				) );
				if (isset ( $resp ))
					echo $resp;
			}
		}
	}
}