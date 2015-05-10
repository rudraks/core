<?php


include_once ("AbstractHandler.php");
include_once (RUDRA_MODEL . "/Header.php");

class ResourceHandler extends AbstractHandler {
	public function invokeHandler(){

		//VARIABLES TO DETERMINE RESOURCES
		//RELOAD_VERSION
		//RX_ENCRYPT_PATH
		//$const = Config::getSection("CLIENT_CONST");
		//$const['RX_JS_MIN']
		//$const['RX_JS_MERGE']
		
		$hdr = new Header();
		
		$cache_ext  = '.js'; //file extension
		$cache_time     = 3600;  //Cache file expires afere these seconds (1 hour = 3600 sec)
		$cache_folder   = Header::$BUILD_PATH; //folder to store Cache files
		//$ignore_pages   = array('', '');
		$dynamic_url    = $_GET['@']; //$_SERVER['QUERY_STRING']; // requested dynamic page (full url)
		$version    = $_GET['_'];
		//echo "Q==".$_SERVER['QUERY_STRING'];
		$cache_file     = $cache_folder.md5($dynamic_url)."-".$version.$cache_ext; // construct a cache file
		$ignore = false; //(in_array($dynamic_url,$ignore_pages))?true:false; //check if url is in ignore list
		
		Browser::header("RX_MODE_DEBUG".RX_MODE_DEBUG);
		
		if(!RX_MODE_DEBUG){
			//if (!$ignore && file_exists($cache_file) && time() - $cache_time < filemtime($cache_file)) { //check Cache exist and it's not expired.
			if (!$ignore && file_exists($cache_file)) { //check Cache exist and it's not expired.
				ob_start('ob_gzhandler'); //Turn on output buffering, "ob_gzhandler" for the compressed page with gzip.
				echo '/** cached page - '.date('l jS \of F Y h:i:s A', filemtime($cache_file)).',';
				echo "\n * Page : ".$dynamic_url."\n * NewMD5".$cache_file."\n */\n";
				$this->displayResourceFile($cache_file); //read Cache file
				$this->writeHeaders($cache_file);
				//$this->redirectFile($cache_file);
				ob_end_flush(); //Flush and turn off output buffering
				exit(); //no need to proceed further, exit the flow.
			}
		}
		//Turn on output buffering with gzip compression.
		ob_start('ob_gzhandler');
		
		$files = explode(",",$_GET['@']);
		$hdr->printCombinedJs($files);
		
		if (!is_dir($cache_folder)) { //create a new folder if we need to
			mkdir($cache_folder);
		}
		if(!$ignore){
			$fp = fopen($cache_file, 'w');  //open file for writing
			fwrite($fp, ob_get_contents()); //write contents of the output buffer in Cache file
			fclose($fp); //Close file pointer
		}
		$this->writeHeaders($cache_file);
		//Browser::printlogsOnHeader();
		if(RX_MODE_DEBUG) {
			//unlink($cache_file);
			header("X-LOG-DELTED: TRUE");			
		}
		ob_end_flush();
	}
	
	
	// return the browser request header
	// use built in apache ftn when PHP built as module,
	// or query $_SERVER when cgi
	public function getRequestHeaders() {
		if (function_exists("apache_request_headers")) {
			if($headers = apache_request_headers()) {
				return $headers;
	
			}
		}
		$headers = array();
		// Grab the IF_MODIFIED_SINCE header
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			$headers['If-Modified-Since'] = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		}
		return $headers;
	}
	
	
	// Return the requested graphic file to the browser
	// or a 304 code to use the cached browser copy
	
	public function displayResourceFile ($graphicFileName, $fileType='application/javascript; charset: UTF-8') {
		$fileModTime = filemtime($graphicFileName);
		// Getting headers sent by the client.
		$headers = $this->getRequestHeaders();
		// Checking if the client is validating his cache and if it is current.
		if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == $fileModTime)) {
			// Client's cache IS current, so we just respond '304 Not Modified'.
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', $fileModTime).' GMT', true, 304);
			header('HTTP/1.1 304 Not Modified');
			readfile($graphicFileName);
			//$this->redirectFile($graphicFileName);
		} else {
			// Image not cached or cache outdated, we respond '200 OK' and output the image.
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', $fileModTime).' GMT', true, 200);
			header('Content-type: '.$fileType);
			header('Content-transfer-encoding: binary');
			//header('Content-length: '.filesize($graphicFileName));
			header('X-File-Name: '.$graphicFileName);
			header( 'Cache-Control: max-age=2678400' );
	
			readfile($graphicFileName);
		}
		//header('HTTP/1.1 301 Moved Permanently');
		//header('HTTP/1.1 302 Found');
		//header('Location: '.CONTEXT_PATH.$graphicFileName);
	}
	
	public function writeHeaders($fileName, $fileType='application/javascript; charset: UTF-8'){
		//echo "//echo::".$fileType;
		$fileModTime = filemtime($fileName);
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', $fileModTime).' GMT', true, 200);
		header('Content-type: '.$fileType);
		header('Content-transfer-encoding: binary');
		//header('Content-length: '.filesize($fileName));
		header('X-File-Name: '.$fileName);
		header( 'Cache-Control: max-age=2678400' );
	}
	public function redirectFile($fileName){
		//header('HTTP/1.1 301 Moved Permanently');
		header('HTTP/1.1 302 Found');
		header('Location: '.CONTEXT_PATH.$fileName);
	}
}
