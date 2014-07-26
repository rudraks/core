<?php

/*
 * To change this license header, choose License Headers in Project Properties. To change this template file, choose Tools | Templates and open the template in the editor.
*/
include_once (RUDRA . "/core/controller/AbstractNotificationController.php");

class NotificationController extends AbstractNotificationController {

	public function invoke(User $user,$handlerName) {
		global $TunnelDB;
		$this->_db = $TunnelDB;

		$callback = $_REQUEST ["callback"];
			
		$NotClass = new ReflectionClass('NotificationController' );

		if ($NotClass->hasMethod($handlerName )) {
			$reflectionMethod = $NotClass->getMethod($handlerName );
			echo printf('%s(%s);',$callback,$reflectionMethod->invoke($this, $this->user, $handlerName ));
		} else {

		}
		flush();
		gc_collect_cycles();
	}
	public function handshake($user, $handlerName) {
		$this->_db->update("REPLACE INTO" . " token (`sessionId`, `tokenId`) VALUES ('%s', '%s')",
				$_COOKIE ['PHPSESSID'], $this->token );

		return sprintf('{"time" : "%s", cookie : "%s",connectionId : "%s"}', date('d/m H:i:s' ),
				$_COOKIE ['PHPSESSID'], $this->_db->lastInsertId() );
	}
	public function getLPollData($user, $handlerName) {
		set_time_limit (600);
		date_default_timezone_set('Europe/Berlin');
		$counterEnd = (int)$_REQUEST["counterEnd"];
		$counterStart = (int)$_REQUEST["counterStart"];
		$this->expireNotifications($counterStart, $counterEnd);
		$secCount = IDLE_WAIT;
		do {
			sleep(IDLE_TIME);
			$updates = $this->fetchAllNotifications($counterEnd);
		} while (!$updates && ($secCount--)>0);

		if($updates){

		}

		header("HTTP/1.0 200");
		return sprintf ('{"time" : "%s", "counter" : "%d", start : %d, data : %s}'
				, date('d/m H:i:s'), $counterEnd,$counterStart,json_encode($updates));
	}

	public function sendData($user, $handlerName) {
		$data = $_REQUEST["data"];
		$this->push("chat", json_decode($data));
		return "{}";
	}
}
