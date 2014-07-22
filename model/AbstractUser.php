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
include_once(RUDRA . "/model/RxCache.php");

class AbstractUser {

	public static $usercache;
	public $valid;
	public $uname;
	public $uid;
	private $info = array();

	public function  __construct(){
		if(self::$usercache ==NULL) self::$usercache = new RxCache('user');
	}

	public function set($key,$value){
		$this->info[$key] = $value;
	}

	public function get($key){
		return $this->info[$key];
	}

	public function validate() {
		if (isset($_SESSION['uid']) && trim($_SESSION['uid'])) {
			$info = self::$usercache->get($_SESSION['uid']);
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
		self::$usercache->set($this->uid,$this->info);
	}

}
