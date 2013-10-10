<?php

/**
 * Function for checking compatibility with applications
 * @version		@fileVers@ ( $id )
 * @param		string		- $app: the application we are testing - for future expansion
 *
 * @return		boolean
 * @since		2.1.0
 */
if (! function_exists( 'check_compatible' ) ) {
	function check_compatible( $app = 'dunamis' )
	{
		switch ( $app ) :

		case 'dunamis'		:	return version_compare( DUNAMIS, '1.2.0', 'ge' );

		endswitch;
	}
}

/**
 * Provides consistant checksum creation
 * @version		@fileVers@
 * @param		object		- $item: should be an entire row of data from the email template table
 *
 * @return		string containing checksum to use
 * @since		2.0.0
 */
if (! function_exists( 'createChecksum' ) ) {
function createChecksum( $item )
{
	return md5( serialize( $item ) );
}
}