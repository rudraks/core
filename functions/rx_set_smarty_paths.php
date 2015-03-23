<?php

include_once (RUDRA . "/smarty/Smarty.class.php");

function rx_set_smarty_paths(Smarty $viewModel){
	$viewModel->setTemplateDir(get_include_path() .Config::get('VIEW_PATH'));
	$viewModel->setConfigDir(get_include_path() . Config::get('CONFIG_PATH'));
	$CACHE_PATH = get_include_path() . Config::get('BUILD_PATH').'/cache';
	if (!file_exists($CACHE_PATH)) {
		if(!mkdir($CACHE_PATH, 0777, true)){
			die('Failed to create folders:'.$CACHE_PATH);
		};
	}
	$viewModel->setCacheDir($CACHE_PATH);
	$TEMP_PATH = get_include_path() . Config::get('BUILD_PATH').'/temp';
	if (!file_exists($TEMP_PATH)) {
		if(!mkdir($TEMP_PATH, 0777, true)){
			die('Failed to create folders:'.$TEMP_PATH);
		};
	}
	$viewModel->setCompileDir($TEMP_PATH);
	$LOCAL_PLUGIN_PATH = get_include_path() . Config::get('LOCAL_PLUGIN_PATH');
	if (file_exists($LOCAL_PLUGIN_PATH)) {
		$viewModel->addPluginsDir($LOCAL_PLUGIN_PATH);
	}
}