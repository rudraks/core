<?php 

define("BUILD_PATH", PROJECT_ROOT_DIR."build/");

define("LIB_PATH", PROJECT_ROOT_DIR."lib/");
define("RUDRA", LIB_PATH."rudrax/");
define("RUDRA_CORE", RUDRA."core/");
define("RUDRA_MODEL", RUDRA_CORE."model/");
define("RUDRA_HANDLER", RUDRA_CORE."handler/");

define("APP_PATH", PROJECT_ROOT_DIR."app/");
define("VIEW_PATH", APP_PATH."view/");
define("CONFIG_PATH", APP_PATH."config/");

define("RESOURCE_PATH", PROJECT_ROOT_DIR."resources/");

function define_globals ($globals){

	set_include_path ( $globals ['APP_ROOT_PATH'] );

	define ( "BASE_PATH", dirname ( __FILE__ ) );

	foreach ( $globals as $key=>$value ) {
		define ( $key, $value );
	}
	
}

?>