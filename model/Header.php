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
	public $scripts_bundle = array();
	public $css = array();
	public $dones = array();
	public $files_done =  array();
	public $minified;
	public $const = array("HAS");
	
	public static $cache;
	public static $modules =  array();
	public static $webmodules = null;
	public static $modulefiles = null;
	public static $BUILD_PATH;
	
	
	public static function init(){
		Browser::info("init");
		self::$cache = new RxCache("header",true);
		self::$webmodules = self::$cache->get('webmodules');
		self::$modulefiles = self::$cache->get('modulefiles');
	
		if(self::$webmodules==null || (RX_MODE_DEBUG || self::$cache->isEmpty())){
			Browser::warn("Scanning All Web Modules");
			//READ MODULES
			self::$webmodules = self::getModuleProperties(get_include_path().LIB_PATH,self::$webmodules);
			self::$webmodules = self::getModuleProperties(get_include_path().RESOURCE_PATH,self::$webmodules);
			
			self::$cache->set('webmodules',self::$webmodules);
			
			//CREATE MODULE FILES
			self::$modulefiles = array();
			$header = new Header();
			foreach(self::$webmodules['bundles'] as $module=>$moduleObject){
				$header->_import($module);
			}
			$header->minify();
			self::$cache->set('modulefiles',self::$modulefiles);
			self::$cache->save();
			
			FileUtil::write("resources/bundle.json", json_encode(Header::getModules()));
			
			Browser::info(self::$webmodules,self::$modulefiles);
		}
	}
	public static function getModule($moduleName) {
		return isset(self::$webmodules['bundles'][$moduleName]) ? self::$webmodules['bundles'][$moduleName] : FALSE;
	}
	public static function getModules() {
		return isset(self::$webmodules) ? self::$webmodules : FALSE;
	}

	public function  __construct(){
		$this->minified = new Minifier( array(
				'echo' => false,
				'encode' => false,
				//'timer' => true,
				//'closure' => true,
				'gzip' => false
		));
		self::$BUILD_PATH = get_include_path(). BUILD_PATH.'/';
		$this->const = Config::getSection("CLIENT_CONST");
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
		$this->combine();
	}

	private function _import($module)	{
		if(self::getModule($module) && !isset($this->dones[$module])){
			$this->dones[$module] = $module;
			$this->add($module,self::getModule($module));
		} else {
			$moduleSplit = explode('/',$module);
			$size = count($moduleSplit);
			if($size>1){
				$last = $moduleSplit[$size-1];
				$super_module_list = array_splice($moduleSplit,$size-1,1);
				$super_module_name = implode('/',$moduleSplit);
				$super_module = self::getModule($super_module_name);
				if($super_module && isset($super_module[$last])
				&& !isset($this->dones[$super_module_name])){
					$this->addFile($super_module_name,$moduleSplit[$size-2],
							$super_module[$last]);
				}
			}
		}
	}

	private function add($module,$list){

		if(isset($list['@'])){
			$modules = $list['@'];
			foreach($modules as $key=>$value){
				$this->_import($value);
			}
		}
		$files = $list["files"];
		if($files!=null){
			foreach($files as $key=>$value){
				$this->addFile($module,$key,$value);
			}
		}
	}

	public function getKey($module,$key, $value){
		//return $module.".".$key;
		return $value;
	}
	
	public function getFileObj($filePath,$ext="js",$isRemote=false){
		$file = ($isRemote == true) ? $filePath : $filePath; //(RESOURCE_PATH."/".$filePath);
		$link = ($isRemote == true) ? $file : (CONTEXT_PATH.$file);
		return array(
			"remote" => $isRemote,
			"file" => resolve_path($file),
			"script" => ($ext=='js'),
			"ext" => $ext,
			"link" => $link
		);
	}
	
	public function addFile($module,$file_name,$file_path){
		$file_key = $this->getKey($module,$file_name,$file_path);
		$fileObj = isset(self::$modulefiles[$file_key]) ? self::$modulefiles[$file_key] : NULL;
		if($fileObj == NULL){
			$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
			$isRemote = is_remote_file($file_path);
			$fileObj = self::getFileObj($file_path,$ext,$isRemote);
			self::$modulefiles[$file_key] = $fileObj;
		}
		
		if($fileObj['script']){
			$this->scripts[$file_key] = $fileObj;
		} else {
			$this->css[$file_key] = $fileObj;
		}
	}
	
	public function combine(){
		$count = count($this->scripts);
		$this->scripts_bundle = array();
		for($i=0;$i<$count;$i+=5){
			$slice = (array_slice($this->scripts,$i,5));
			$param = "";
			foreach ($slice as $fileObj){
				if($fileObj["remote"]){
					$this->makeMD5Entry($param);
					$param = "";
					$this->scripts_bundle[$fileObj['link']] = $fileObj['link'];
				} else {
					$param= $param.$fileObj['link'].",";
				}
			}
			$this->makeMD5Entry($param);
		}
	}
	
	public function makeMD5Entry($param){
		$fileName = md5($param).".js";
		$this->scripts_bundle[$fileName] = CONTEXT_PATH."combinejs/".$fileName."?@=".$param;
	}
	
	public function minify(){
		if(RX_MODE_DEBUG || self::$cache->isEmpty()){
			foreach($this->scripts as $key=>$value){
				if(RX_JS_MIN && !$this->scripts[$key]["remote"]){
					Browser::warn("minifying...",$value["file"]);
					$newName = self::$BUILD_PATH.$value["file"];
					$this->scripts[$key]["exists"] = file_exists(get_include_path().$value["file"]);
					$this->scripts[$key]["build_path"] = $newName;
					$this->scripts[$key]["minified"] = $this->minified->minify(get_include_path().$value["file"],$newName);
					$this->scripts[$key]["link"] = CONTEXT_PATH.str_replace(self::$BUILD_PATH,"",
							$this->scripts[$key]["minified"]
					);
				}
			}
			
			foreach($this->css as $key=>$value){
				if(RX_JS_MIN && !$this->css[$key]["remote"]){
					Browser::warn("minifying...",$this->css[$key]["minified"],file_exists(get_include_path().$value["file"]),$value["file"]);
					$newName = self::$BUILD_PATH.$value["file"];
					$this->css[$key]["exists"] = file_exists($value["file"]);
					$this->css[$key]["build_path"] = $newName;
					$this->css[$key]["minified"] = $this->minified->minify(get_include_path().$value["file"],$newName);
					$this->css[$key]["link"] = CONTEXT_PATH.str_replace(self::$BUILD_PATH,"",
							$this->css[$key]["minified"]
					);
				}
			}
		}
	}
	
	public function printMinifiedJs ($files){
		$newfiles = array();
		foreach($files as $key=>$file){
			$new_file = str_replace(CONTEXT_PATH, "", $file);
			$newfiles[] = $filFile;
			if(RX_JS_MIN){
				$filFile = $this->minified->minify(get_include_path().$new_file,self::$BUILD_PATH.$new_file);
				//print_js_comment("-----".get_include_path().$new_file."---".$file."---".$filFile);
				readfile ($filFile);
			} else {
				readfile(get_include_path().$new_file);
			}
		}
		return $newfiles;
	}
	
	public static function getModuleProperties($dir,$filemodules = array("_" => array(),"bundles" => array())){
		if (!is_dir($dir)){
			return $filemodules;
		}
		$d = dir($dir);
		//Browser::warn("Scanning Resource Folder",$dir);
		while (false !== ($entry = $d->read())){
			if ($entry != '.' && $entry != '..'){
				if (is_dir($dir.'/'.$entry)){
					$filemodules = self::getModuleProperties($dir.'/'.$entry,$filemodules);
				} else if(strcmp ($entry,"module.properties")==0){
					try{
						$mod_file = $dir.'/'.$entry;
						$mode_time = filemtime($mod_file);
						if(!RX_MODE_DEBUG && isset($filemodules["_"][$mod_file])
						&& $mode_time == $filemodules["_"][$mod_file]){
							//Browser::log("from-cache....",$mod_file);
						} else {
							//if(RX_MODE_DEBUG) Browser::log("fresh ....",$dir);
							$filemodules["_"][$mod_file] = $mode_time;
							$r = parse_ini_file ($dir.'/'.$entry, TRUE );
							//Browser::console($dir.'/'.$entry);
							foreach($r as $mod=>$files){
								$filemodules['bundles'][$mod] = array("files"=>array());
								foreach($files as $key=>$file){
									if($key=='@'){
										$filemodules['bundles'][$mod][$key] = explode(',',$file);
									} else if($key!='@' && !is_remote_file($file)){
										//Browser::log("****",resolve_path($dir."/".$file),"***");
										$filemodules['bundles'][$mod]["files"][] = replace_first(get_include_path(), "", $dir.'/'.$file);
										//$filemodules['bundles'][$mod]["files"][] = self::resolve_path("/resou/".$dir.'/'.$file);
									} else $filemodules['bundles'][$mod]["files"][] = $file;
								}
							}
						}
					} catch (Exception $e){
						echo 'Caught exception: ',  $e->getMessage(), "\n";
					}
				}
			}
		}
		$d->close();
		return $filemodules;
	}
}

Header::init();
