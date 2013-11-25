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
 * @param		integer		$groupid		The group id we are looking for (or the row id)
 * @param		boolean		$bygroupid		True indicates we are looking for the group by group id, false by row id (default to true)
 * 
 * @return		false|stdClass				False on error or object of result
 * @since		2.1.3
 */
if (! function_exists( 'getGroupData' ) ) {
function getGroupData( $groupid = 0, $bygroupid = true )
{
	if ( $groupid === 0 ) return false;
	
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
	
	if (! isset( $data[$groupid] ) ) return false;
	
	return $data[$groupid];
}
}