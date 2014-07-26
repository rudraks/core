<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

require_once(RUDRA.'/magicmin/class.magic-min.php' );

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
	public $modules =  array();
	public $minified;
	public static $REPLACE_REGEX;
	public static $BUILD_PATH;

	public function  __construct(Smarty $tpl){
		$this->modules = Rudrax::getModules();
		$this->minified = new Minifier( array(
				'echo' => false,
				'encode' => true,
				//'timer' => true,
				'gzip' => true,
				//'closure' => true
		));
		self::$BUILD_PATH = get_include_path(). BUILD_PATH.'/';
		self::$REPLACE_REGEX = '/('.LIB_PATH.'|'.RESOURCE_PATH.')/';
	}

	public function title($title){
		$this->title = $title;
	}

	public function meta($meta){
		foreach($meta as $key=>$value){
			$this->metas[$key] = $value;
		}
	}

	public function import(){
		foreach(func_get_args() as $module){
			$this->_import($module);
		}
	}

	private function _import($module){
		if(isset($this->modules[$module]) && !isset($this->dones[$module])){
			$this->dones[$module] = $module;
			$this->add($module,$this->modules[$module]);
		}
	}

	private function add($module,$list){

		if(isset($list['@'])){
			$modules = explode(',',$list['@']);
			foreach($modules as $key=>$value){
				$this->import($value);
			}
		}

		$libpath = RESOURCE_PATH;
		foreach($list as $key=>$value){
			if($key!='@'){
				$ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
				if(!is_remote_file($value)){
					if($ext=='js'){
						$this->scripts[$module.".".$key] = $libpath."/".$value;
					} else if($ext=='css'){
						$this->css[$module.".".$key] = $libpath."/".$value;
					}
				} else {
					if($ext=='js'){
						$this->scripts[$module.".".$key] = $value;
					} else if($ext=='css'){
						$this->css[$module.".".$key] = $value;
					} else {
						$this->scripts[$module.".".$key] = $value;
					}
				}
			}
		}
	}
	public function minify(){
		foreach($this->scripts as $key=>$value){
			//$newName = self::$BUILD_PATH.RESOURCE_PATH.preg_replace(self::$REPLACE_REGEX,"",$this->scripts[$key],1);
			if(!is_remote_file($value) && file_exists(get_include_path().$this->scripts[$key])){
				$newName = self::$BUILD_PATH.$this->scripts[$key];
				//echo "[".$value."-->".$newName.":::".self::$BUILD_PATH."]<br>";
				$this->scripts[$key] = CONTEXT_PATH.str_replace(self::$BUILD_PATH,"",
						$this->minified->minify(get_include_path().$value,$newName)
				);
			} //else $this->scripts[$key] = CONTEXT_PATH.$this->scripts[$key];
		}
		foreach($this->css as $key=>$value){
			//$newName = self::$BUILD_PATH.RESOURCE_PATH.preg_replace(self::$REPLACE_REGEX,"",$this->css[$key],1);
			if(!is_remote_file($value) && file_exists(get_include_path().$this->css[$key])){
				$newName = self::$BUILD_PATH.$this->css[$key];
				//echo $key."--".$value."--".$newName."<br>";
				$this->css[$key] =  CONTEXT_PATH.str_replace(self::$BUILD_PATH,"",
						$this->minified->minify(get_include_path().$this->css[$key],$newName)
				);
			}
		}
	}

}
