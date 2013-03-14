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
	
	// Now we must do backwards compatibility testing
	if (! is_api() ) {
		
		// Intercept password resets so we can figure out what is going on
		//		Contacts in WHMCS don't pass correctly through emailer
		//		so we don't do it
		//if ( version_compare( DUN_ENV_VERSION, '5.1', 'l' ) ) {
			$file	= get_filename();
			
			if ( is_admin() ) {
				if ( $file != 'clientscontacts' ) return;
				global $iscontact;
				$iscontact = true;
				return;
			}
			else if (! is_admin() ) {
				if ( $file != 'pwreset' ) return;
			}
			
			$input	= dunloader( 'input', true );
			$key	= $input->getVar( 'key', false );
			
			if (! $key ) return;
			
			dunmodule( 'intouch.emails' )->findClient( $key );
		//}
	}
}