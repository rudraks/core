<?php

include_once RUDRA."/annotations/Annotations.php";
include_once(RUDRA."/core/handler/AbstractHandler.php");
include_once(RUDRA."/core/controller/AbstractController.php");
include_once(RUDRA."/core/ClassUtil.php");

function rx_scan_classes(){


	$annotations = new Alchemy\Component\Annotations\Annotations();

	if(is_dir(LIB_PATH)){
		rx_scan_dir($annotations,LIB_PATH);
	} else {
		echo "Error:Library directory not found on project root.";
	}
	
	if(is_dir(APP_PATH)){
		rx_scan_dir($annotations,APP_PATH);
	}

	ClassUtil::save();
}

function rx_scan_dir ($annotations,$dir){
	
	$allController = ClassUtil::getControllerArray();
	
	$dir_iterator = new RecursiveDirectoryIterator($dir);
	$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
	// could use CHILD_FIRST if you so wish
	
	foreach ($iterator as $filename=>$file) {
		if ($file->isFile()) {
			if(fnmatch("*/handler/*.php",$file->getPathname()) || fnmatch("*\\\handler\\\*.php",$file->getPathname())){
				require_once $file->getPathname();
				$className = str_replace(".php", "", $file->getFilename());
					
				$scan  = true;
				if( ClassUtil::getMTime($className)>=$file->getMTime()){
					$scan = false;
				}
				if($scan && class_exists($className)){
					$result = $annotations->getClassAnnotations($className);
					if(isset($result["Handler"]) && isset($result["Handler"][0]) && !empty($result["Handler"][0])){
						ClassUtil::setHandler($result["Handler"][0], array(
						"className" => $className,
						"filePath" => $file->getPathname(),
						"mtime" => $file->getMTime()
						));
						Browser::warn("HandleScanned::",	ClassUtil::getHandler($result["Handler"][0]));
						ClassUtil::setMTime($className,$file->getMTime());
					}
				}
			} else if(fnmatch("*/controller/*.php",$file->getPathname()) || fnmatch("*\\\controller\\\*.php",$file->getPathname())){
	
				require_once $file->getPathname();
				$className = str_replace(".php", "", $file->getFilename());
					
				$scan  = true;
				if(ClassUtil::getMTime($className)>=$file->getMTime()){
					$scan = false;
				}
				if($scan && class_exists($className)){
					$methods = get_class_methods($className);
					foreach ($methods as $method){
						$result = $annotations->getMethodAnnotations($className,$method);
						Browser::error("ControllerScanned::",$result);
						if(isset($result["RequestMapping"])
						&&	isset($result["RequestMapping"][0])
						&&  isset($result["RequestMapping"][0]["url"])){
							$allController[$result["RequestMapping"][0]["url"]] = array(
									"className" => $className,
									"method" => $method,
									"filePath" => $file->getPathname(),
									"mtime" => $file->getMTime(),
									"mappingUrl" => $result["RequestMapping"][0]["url"],
									"cache" => (isset($result["RequestMapping"][0]["cache"]) ? $result["RequestMapping"][0]["cache"] : FALSE),
									"type" => (isset($result["RequestMapping"][0]["type"]) ? $result["RequestMapping"][0]["type"] : NULL)
							);
						}
					}
					Browser::warn("ControllerScanned::",$allController[$result["RequestMapping"][0]["url"]]);
					ClassUtil::setMTime($className,$file->getMTime());
				}
			} else if(fnmatch("*/model/*.php",$file->getPathname()) || fnmatch("*\\\model\\\*.php",$file->getPathname())){
				require_once $file->getPathname();
				$className = str_replace(".php", "", $file->getFilename());
	
				$scan  = true;
				if( ClassUtil::getMTime($className)>=$file->getMTime()){
					$scan = false;
				}
	
				if($scan && class_exists($className)){
					$result = $annotations->getClassAnnotations($className);
					if(isset($result["Model"]) && isset($result["Model"][0]) && !empty($result["Model"][0])){
						ClassUtil::setModel($result["Model"][0], array(
						"className" => $className,
						"filePath" => $file->getPathname(),
						"mtime" => $file->getMTime(),
						"type" => $result["Model"][0]
						));
						Browser::warn("ModelScanned::",	ClassUtil::getModel($result["Model"][0]));
						ClassUtil::setMTime($className,$file->getMTime());
					}
				}
			}
		}
	}
	
	ClassUtil::setControllerArray($allController);
}