<?php

/* 
 * Porpose of this file is to setup the project first time , file shoud be include only first time, not always,
 * functinos you  think you dont need alwayz you can put here, this is for optimization purpose.
 * 
 */

include_once RUDRA."/annotations/Annotations.php";


class Setup {
	
	
	public static function scanhandlers (){
		
		$cache = new RxCache("handlers",true);
		$mtimes = $cache->get("#@#@#",array());
		
		$annotations = new Alchemy\Component\Annotations\Annotations();
		
		$dir_iterator = new RecursiveDirectoryIterator("../");
		$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
		// could use CHILD_FIRST if you so wish
		
		foreach ($iterator as $filename=>$file) {
		    if ($file->isFile()) {
		    	if(fnmatch("*Handler.php",$file->getPathname())){
			        echo $file->getPathname(). "\n";
			        require_once $file->getPathname();
			        echo "<br/>";
			        $className = str_replace(".php", "", $file->getFilename());
			        
			        $scan  = true;
			        if(isset($mtimes[$className]) && $mtimes[$className]>=$file->getMTime()){
			        	$scan = false;
			        }
			        if($scan){
			        	$result = $annotations->getClassAnnotations($className);
			        	if(isset($result["handler"]) && isset($result["handler"][0]) && !empty($result["handler"][0])){
			        		print_r($result);
			        		$cache->set($result["handler"][0], array(
			        				"className" => $className,
			        				"filePath" => $file->getFilename(),
			        				"mtime" => $file->getMTime()
			        		));
			        	}
			        }
		    	}
		        
		    }
		}
		$cache->save();
	}
	
}