<?php

/*
 * To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/
include_once(RUDRA . "/user.abstract.php");

/**
 * Description of User, it basically extends AbstractUser and implemetns atleast two methods
 *
 * @author Lalit Tanwar
*/
class User extends AbstractUser {

	public function auth($username, $passowrd) {
		//DO SOME THING
		$this->setValid();
	}

	public function unauth() {
		//DO SOME THING
		$this->setInValid();
	}
}
