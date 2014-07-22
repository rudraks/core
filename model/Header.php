<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

require_once(LIB_PATH.'/magix/class.magic-min.php' );

//Initialize the class with image encoding, gzip, a timer, and use the google closure API

/**
 * Description of Header
 *
 * @author Lalit Tanwar
*/
class Header {

	public $title;
	public $metas = array();
	public $scripts = array();
	public $css = array();
	public $dones = array();
	public $config =  array();
	public $minified;
	public $console;
	public static $REPLACE_REGEX;
	public static $BUILD_PATH;

	public function  __construct(Smarty $tpl){
		$tpl->configLoad(get_include_path() .LIB_PATH."/lib.compose.conf",'*');
		$rx_array = $tpl->getConfigVars();
		$x = $tpl->configLoad("compose.conf",'*');
		//$tpl->fetch(get_include_path().RUDRA."/view/empty.tpl");
		$this->config = array_merge($rx_array,$tpl->getConfigVars());
		$this->minified = new Minifier( array(
				'echo' => false,
				'encode' => true,
				//'timer' => true,
				'gzip' => true,
				//'closure' => true
		));
		$this->console = new Console();
		self::$BUILD_PATH = get_include_path(). BUILD_PATH.'/';
		self::$REPLACE_REGEX = '/('.LIB_PATH.'|'.RESOURCE_PATH.')/';

		// 		/print_r( $this->config);
	}
	
	public function title($title){
		$this->title = $title;		
	}
	
	public function meta($meta){
		foreach($meta as $key=>$value){
// 			echo $key ."---". $value;
			$this->metas[$key] = $value;
		}
	}

	public function import(){
		foreach(func_get_args() as $module){
			$this->_import($module);
		}
	}

	private function _import($module){
		if(isset($this->config[$module]) && !isset($this->dones[$module])){
			$dones[$module] = $module;
			$this->add($module,$this->config[$module]);
		}
	}

	private function add($module,$list){
		
		if(isset($list['ON'])){
			$modules = explode(',',$list['ON']);
			foreach($modules as $key=>$value){
				$this->import($value);
			}
		}
		
		$libpath = ((isset($list['RX']) && $list['RX']==TRUE)? LIB_PATH : RESOURCE_PATH);

		foreach($list as $key=>$value){
			if($key!='ON'){
				$ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
				$remote_file = $this->remote_file($value);
				if($ext=='js' && !$this->remote_file($value)){
					$this->scripts[$module.".".$key] = $libpath."/".$value;
				} else if($ext=='css' && !$this->remote_file($value)){
					$this->css[$module.".".$key] = $libpath."/".$value;
				} elseif($ext=='js' && $this->remote_file($value)){
					$this->scripts[$module.".".$key] = $value;
				} else if($ext=='css' && $this->remote_file($value)){
					$this->css[$module.".".$key] = $value;
				} else if($this->remote_file($value)) {
					$this->scripts[$module.".".$key] = $value;
				}
			}
		}
	}
	private function remote_file( $file )
	{
		//It is a remote file
		if( preg_match( "/(http|https)/", $file ) )
		{
			return true;
		}
		//Local file
		else
		{
			return false;
		}
	}

	public function minify(){
		foreach($this->scripts as $key=>$value){
			
			//$newName = self::$BUILD_PATH.RESOURCE_PATH.preg_replace(self::$REPLACE_REGEX,"",$this->scripts[$key],1);
			if(!$this->remote_file($value) && file_exists(get_include_path().$this->scripts[$key])){	
			$newName = self::$BUILD_PATH.$this->scripts[$key];
			//echo $key."--".$value."--".$newName."<br>";
			$this->scripts[$key] = CONTEXT_PATH.str_replace(self::$BUILD_PATH,"",
					$this->minified->minify(get_include_path().$this->scripts[$key],$newName)
			);
			}
		}
		foreach($this->css as $key=>$value){
			//$newName = self::$BUILD_PATH.RESOURCE_PATH.preg_replace(self::$REPLACE_REGEX,"",$this->css[$key],1);
			if(!$this->remote_file($value)){
			$newName = self::$BUILD_PATH.$this->css[$key];
			$this->css[$key] =  CONTEXT_PATH.str_replace(self::$BUILD_PATH,"",
					$this->minified->minify(get_include_path().$this->css[$key],$newName)
			);
			}
		}
	}

}
