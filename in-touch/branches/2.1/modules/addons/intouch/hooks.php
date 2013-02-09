<?php

if (! function_exists( 'get_dunamis' ) ) {
	$path	= dirname( dirname( dirname( dirname(__FILE__) ) ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'dunamis.php';
	if ( file_exists( $path ) ) require_once( $path );
}

// Only run this if we know Dunamis is there
if ( function_exists( 'get_dunamis' ) )
{
	// Load Dunamis
	get_dunamis( 'intouch' );
	
	// Run the admin controller
	if ( is_admin() ) {
		dunmodule( 'intouch.adminareapages' )->execute();
	}
}