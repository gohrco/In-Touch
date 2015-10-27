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


/**
 * Provides a means to get the data group from the database
 * @version		@fileVers@
 * @version		2.2.0		We dropped the default of 0 and the check for the groupid of 0 so non-grouped groups can be customized
 * @param		integer		$groupid		The group id we are looking for (or the row id)
 * @param		boolean		$bygroupid		True indicates we are looking for the group by group id, false by row id (default to true)
 * 
 * @return		false|stdClass				False on error or object of result
 * @since		2.1.3
 */
if (! function_exists( 'getGroupData' ) ) {
function getGroupData( $groupid, $bygroupid = true, $decode = false )
{
	// Dropped 2.2.0
	//if ( $groupid === 0 ) return false;
	
	$data	=	array();
	$db		=	dunloader( 'database', true );
	$db->setQuery( "SELECT * FROM `mod_intouch_groups` WHERE `active` = '1'" );
	$result	=	$db->loadObjectList();
	
	foreach ( $result as $row ) {
		if ( $bygroupid ) {
			if ( strpos( $row->group, '|' ) !== false ) {
				$grps		=	explode( '|', $row->group );
				foreach ( $grps as $grp ) {
					$data[$grp]	=	$row;
				}
			}
			else {
				$data[$row->group] = $row;
			}
		}
		else {
			$data[$row->id]	=	$row;
		}
	}
	
	if ( $decode && isset( $data[$groupid] ) ) {
		$params	=	json_decode( $data[$groupid]->params );
		foreach ( $params as $k => $v ) {
			$data[$groupid]->$k = $v;
		}
	}
	
	if (! isset( $data[$groupid] ) ) return false;
	
	return $data[$groupid];
}
}


/**
 * Function to get the short version number
 * @version		@fileVers@
 *
 * @return		string
 * @since		2.2.00
 */
if (! function_exists( 'get_intouch_version' ) ) {
	function get_intouch_version()
	{
		static $version = null;

		if ( $version == null ) {
			$curversion	=	substr( DUN_ENV_VERSION, 0, 3 );

			$path	=	dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR
			.	'templates' . DIRECTORY_SEPARATOR;

			if ( is_dir( $path . $curversion ) ) {
				$version	=	$curversion;
			}
			else {
				$dh		=	opendir( $path );
				$dirs	=	array();
				while ( false !== ( $file = readdir( $dh ) ) ) {
					if ( in_array( $file, array( '.', '..' ) ) ) continue;
					if (! is_dir( $path . DIRECTORY_SEPARATOR . $file ) ) continue;
					$dirs[]	=	$file;
				}
				rsort( $dirs );
				$version	=	array_shift( $dirs );
			}
		}

		return $version;
	}
}


/**
 * Simple function to determine if we are supporting the installed WHMCS version
 * @version		@fileVers@
 *
 * @return		boolean
 * @since		3.1.00
 */
if (! function_exists( 'is_supported_byintouch' ) ) {
	function is_supported_byintouch()
	{
		$curversion	=	substr( DUN_ENV_VERSION, 0, 3 );

		return $curversion == get_intouch_version();
	}
}