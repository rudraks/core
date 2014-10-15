<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

/**
 * Description of User
 *
 * @author Lalit Tanwar
 */
class AbstractUser {

	public static $usercache;
	public $valid;
	public $uname;
	public $uid;
	private $info;

	public function  __construct(){
		if(self::$usercache ==NULL) self::$usercache = new RxCache('user');
		$this->info = array();
	}

	public function set($key,$value){
		$this->info[$key] = $value;
	}

	public function get($key){
		return isset($this->info[$key]) ? $this->info[$key] : null;
	}
	public function getData(){
		return $this->info;
	}

	public function validate() {
		if (isset($_SESSION['uid']) && trim($_SESSION['uid'])) {
			$info = self::$usercache->get($_SESSION['uid']);
			//Browser::log("info",$info);
			if($info){
				$this->valid = TRUE;
				$this->uid = $_SESSION['uid'];
				//echo print_r(self::$usercache->get($this->uid));
				$this->info = $info;
				return TRUE;
			}
		}
		$this->valid = FALSE;
		$this->uname = "Guest";
		$this->uid = -1;
		return FALSE;
	}

	public function setValid() {
		$this->valid = TRUE;
		session_regenerate_id();
		$_SESSION['uid'] = $this->uid;
		$_SESSION['uname'] = $this->uname;
		$this->info['uid'] = $this->uid; 
		$this->info['uname'] = $this->uname;
		$this->save();
		session_write_close();
	}

	public function setInValid() {
		$this->valid = FALSE;
		self::$usercache->get($this->uid);
		session_destroy();
	}

	public function auth($username, $passowrd) {
		if (strcmp($username, "admin") == 0) {
			setValid();
		}
	}
	public function unauth() {
		$this->setInValid();
	}
	public function isValid() {
		return $this->valid;
	}
	public function save() {
		Browser::log($this->uid,$this->info);
		self::$usercache->set($this->uid,$this->info);
		Browser::log(self::$usercache->get($this->uid));
	}

}
