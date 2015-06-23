<?php

include_once (RUDRA . "/smarty/Smarty.class.php");

function rx_set_smarty_paths(Smarty $viewModel){
	$viewModel->setTemplateDir(VIEW_PATH);
	$viewModel->setConfigDir(CONFIG_PATH);
	$CACHE_PATH = BUILD_PATH.'smarty_cache';
	if (!file_exists($CACHE_PATH)) {
		if(!mkdir($CACHE_PATH, 0777, true)){
			die('Failed to create folders:'.$CACHE_PATH);
		};
	}
	$viewModel->setCacheDir($CACHE_PATH);
	$TEMP_PATH = BUILD_PATH.'smarty_temp';
	if (!file_exists($TEMP_PATH)) {
		if(!mkdir($TEMP_PATH, 0777, true)){
			die('Failed to create folders:'.$TEMP_PATH);
		};
	}
	$viewModel->setCompileDir($TEMP_PATH);
	$LOCAL_PLUGIN_PATH = APP_PATH."SMARTY_PLUGINS";
	if (file_exists($LOCAL_PLUGIN_PATH)) {
		$viewModel->addPluginsDir($LOCAL_PLUGIN_PATH);
	}
}