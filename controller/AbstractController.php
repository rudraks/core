<?php
include_once RUDRA . '/core/model/RequestData.php';

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
 */
abstract class AbstractController {
	public static $HEADER_GLUE = ";-$-;";
	public $user;
	private $responseCache;
	private $cacheDuration = 30000;
	public function loadSession() {
		$UserClass = ClassUtil::getSessionUserClass ();
		$this->user = new $UserClass ();
	}
	public function setUser(AbstractUser $user) {
		$this->user = $user;
	}
	public function getUser() {
		return $this->user;
	}
	public function _interpret_($info, $params) {
		$cache = $info ["cache"];
		$perform = true;
		$md5key = null;
		if ($cache) {
			$this->responseCache = new RxCache ( 'responseCache' );
			$this->headerCache = new RxCache ( 'headerCache' );
			
			$this->cacheDuration = 300; // in seconds
			                            // Client is told to cache these results for set duration
			header ( 'Cache-Control: public,max-age=' . $this->cacheDuration . ',must-revalidate' );
			header ( 'Expires: ' . gmdate ( 'D, d M Y H:i:s', ($_SERVER ['REQUEST_TIME'] + $this->cacheDuration) ) . ' GMT' );
			header ( 'Last-modified: ' . gmdate ( 'D, d M Y H:i:s', $_SERVER ['REQUEST_TIME'] ) . ' GMT' );
			// Pragma header removed should the server happen to set it automatically
			// Pragma headers can make browser misbehave and still ask data from server
			header_remove ( 'Pragma' );
			$md5key = md5 ( $_SERVER [REQUEST_URI] );
			
			$response = $this->responseCache->get ( $md5key, FALSE );
			if (! empty ( $response )) {
				$perform = false;
				echo $response;
				$headerstr = $this->headerCache->get ( $md5key );
				$headers = explode ( self::$HEADER_GLUE, $headerstr );
				foreach ( $headers as $header ) {
					header ( $header );
				}
				Browser::header("fromCahce");
				exit ();
			} else {
				// ob_start('ob_gzhandler');
			}
		}
		
		if ($perform) {
			$this->_interceptor_ ( $info, call_method_by_object ( $this, $info ["method"], $params ) );
		}
		
		if ($perform && $cache) {
			$response = ob_get_contents ();
			$this->responseCache->set ( $md5key, $response );
			$this->headerCache->set ( $md5key, implode ( self::$HEADER_GLUE, headers_list () ) );
			// ob_end_flush();
			// echo $response;
		}
	}
	public function _interceptor_($info, $controllerOutput) {
		switch ($info ["type"]) {
			case "page" :
				$this->_pageInterceptor_ ( $info, $controllerOutput );
				break;
			case "template" :
				$this->_templateInterceptor_ ( $info, $controllerOutput );
				break;
			case "json" :
				$this->_jsonInterceptor_ ( $info, $controllerOutput );
				break;
			case "data" :
				$this->_dataInterceptor_ ( $info, $controllerOutput );
				break;
			default :
				break;
		}
	}
	public function _pageInterceptor_($info, $controllerOutput) {
		return call_user_func ( rx_function ( "rx_interceptor_page" ), $this->user, $info, $controllerOutput );
	}
	public function _templateInterceptor_($info, $controllerOutput) {
		return call_user_func ( rx_function ( "rx_interceptor_template" ), $this->user, $info, $controllerOutput );
	}
	public function _jsonInterceptor_($info, $controllerOutput) {
		return call_user_func ( rx_function ( "rx_interceptor_json" ), $this->user, $info, $controllerOutput );
	}
	public function _dataInterceptor_($info, $controllerOutput) {
		return call_user_func ( rx_function ( "rx_interceptor_data" ), $this->user, $info, $controllerOutput );
	}
}
