<?php


// Default RudraX Plug
RudraX::mapRequest("combinejs/{mdfile}",function(){
	include_once (RUDRA . "/core/handler/ResourceHandler.php");
	$handler = new ResourceHandler();
	$handler->invokeHandler();
});

RudraX::mapRequest("template/{temp}",function($temp="nohandler"){
	return RudraX::invokeHandler($temp);
});
RudraX::mapRequest('data/{eventname}',function($eventName="dataHandler"){
	$controller = RudraX::getDataController();
	$controller->invokeHandler($eventName);
});

RudraX::mapRequest("resources.json",function($cb=""){
	require_once(RUDRA.'/core/model/Header.php' );
	echo $cb."(".json_encode(Header::getModules()).")";
});


// Default Plug for default page
RudraX::mapRequest("",function(){
	return RudraX::invokeHandler("Index");
});



