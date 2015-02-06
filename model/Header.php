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
	public $files_done =  array();
	public $minified;
	public static $REPLACE_REGEX;
	public static $BUILD_PATH;

	public function  __construct(){
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
		if($this->modules==null){
			$this->modules = Rudrax::getModules();
		}
		foreach(func_get_args() as $module){
			$this->_import($module);
		}
	}

	private function _import($module){
		//Browser::console($module);
		if(isset($this->modules[$module]) && !isset($this->dones[$module])){
			$this->dones[$module] = $module;
			$this->add($module,$this->modules[$module]);
		} else {
			$moduleSplit = explode('/',$module);
			$size = count($moduleSplit);
			if($size>1){
				$last = $moduleSplit[$size-1];
				$super_module_list = array_splice($moduleSplit,$size-1,1);
				$super_module = implode('/',$moduleSplit);
				//Browser::console($super_module);
				if(isset($this->modules[$super_module])
				&& isset($this->modules[$super_module][$last])
				&& !isset($this->dones[$super_module])){
					$this->addFile($super_module,$moduleSplit[$size-2],
							$this->modules[$super_module][$last]);
				}
			}
		}
	}

	private function add($module,$list){

		if(isset($list['@'])){
			$modules = explode(',',$list['@']);
			foreach($modules as $key=>$value){
				$this->import($value);
			}
		}
		$files = $list["files"];
//		echo "<hr/>";
//		echo print_r($files);
		if($files!=null){
			foreach($files as $key=>$value){
	//			echo $module."::".$key."::".$value;
				$this->addFile($module,$key,$value);
			}
		}
	}

	public function getKey($module,$key, $value){
		//return $module.".".$key;
		return $value;
	}
	public function addFile($module,$key,$value){
		$ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
		if(!is_remote_file($value)){
			if($ext=='js'){
				$this->scripts[$this->getKey($module,$key,$value)] = RESOURCE_PATH."/".$value;
			} else if($ext=='css'){
				$this->css[$this->getKey($module,$key,$value)] = RESOURCE_PATH."/".$value;
			}
		} else {
			if($ext=='js'){
				$this->scripts[$this->getKey($module,$key,$value)] = $value;
			} else if($ext=='css'){
				$this->css[$this->getKey($module,$key,$value)] = $value;
			} else {
				$this->scripts[$this->getKey($module,$key,$value)] = $value;
			}
		}
	}

	public function minify(){
		foreach($this->scripts as $key=>$value){
			//$newName = self::$BUILD_PATH.RESOURCE_PATH.preg_replace(self::$REPLACE_REGEX,"",$this->scripts[$key],1);
			if(!MINIFY_FILES){
				$this->scripts[$key] = CONTEXT_PATH.$this->scripts[$key];
			} else if(!is_remote_file($value) && file_exists(get_include_path().$this->scripts[$key])){
				$newName = self::$BUILD_PATH.$this->scripts[$key];
				//echo "[".$value."-->".$newName.":::".self::$BUILD_PATH."]<br>";
				$this->scripts[$key] = CONTEXT_PATH.str_replace(self::$BUILD_PATH,"",
						$this->minified->minify(get_include_path().$value,$newName)
				);
			} //else $this->scripts[$key] = CONTEXT_PATH.$this->scripts[$key];
			//$files_done[$this->scripts[$key]] = 
		}
		foreach($this->css as $key=>$value){
			//$newName = self::$BUILD_PATH.RESOURCE_PATH.preg_replace(self::$REPLACE_REGEX,"",$this->css[$key],1);
			if(!MINIFY_FILES){
				$this->css[$key] = CONTEXT_PATH.$this->css[$key];
			} else if(!is_remote_file($value) && file_exists(get_include_path().$this->css[$key])){
				$newName = self::$BUILD_PATH.$this->css[$key];
				//echo $key."--".$value."--".$newName."<br>";
				$this->css[$key] =  CONTEXT_PATH.str_replace(self::$BUILD_PATH,"",
						$this->minified->minify(get_include_path().$this->css[$key],$newName)
				);
			}
		}
	}

}

