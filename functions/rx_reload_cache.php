<?php
/**
 *  Whatever data is returned by handler will be echo'd as it is
 * 
 * @param AbstractUser $user
 * @param Array $info
 * @param String $handlerName
 */
function rx_reload_cache() {
	$headerCache =  new RxCache ( "header", true );
	$headerCache->clear();
}