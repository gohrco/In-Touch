<?php


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