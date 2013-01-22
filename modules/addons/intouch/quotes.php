<?php defined('DUNAMIS') OR exit('No direct script access allowed');

include_once( 'client.php' );

class IntouchQuotesDunModule extends IntouchClientDunModule
{
	
	/**
	 * Retrieves the group id based on the invoice id
	 * @access		public
	 * @version		@fileVers@
	 * @param		integer		- $id: should contain the found invoice id
	 * 
	 * @return		integer
	 * @since		2.0.0
	 */
	public function getGroupId( $id = false )
	{
		$id		= parent :: getGroupId( $id );
		$db		= dunloader( 'database', true );
		
		// If we don't have an ID...
		if ( $id === false ) {
			return false;
		}
		
		// Build query
		$query	=	"SELECT c.groupid as 'id' FROM `tblclients` c INNER JOIN `tblquotes` q ON q.userid = c.id WHERE q.id =" . $db->Quote( $id );
		$db->setQuery( $query );
		$gid	=	$db->loadResult();
		
		if ( $gid == null ) {
			$query	= "SELECT `gid` as 'id' FROM `mod_intouch_quotexref` WHERE `qid` = " . $db->Quote( $id );
			$db->setQuery( $query );
			$gid	= $db->loadResult();
			if ( $gid == null ) return false;
		}
		
		return (int) $gid;
	}
	
	
	/**
	 * Method to retrieve the groups parameters
	 * @access		public
	 * @version		@fileVers@
	 * @param		integer		- $gid: if known the group id
	 * 
	 * @return		object
	 * @since		2.0.0
	 */
	public function getGroupParams( $gid = false )
	{
		if ( ( $gid	= parent :: getGroupParams( $gid ) ) === false ) {
			return false;
		}
		
		$db	= dunloader( 'database', true );
		
		$db->setQuery( "SELECT `params` FROM `mod_intouch_groups` WHERE `group`=" . $db->Quote( $gid ) );
		$result	= $db->loadResult();
		$params	= json_decode( $result );
		
		return $params;
	}
	
	
	/**
	 * Method to get an invoice id
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		integer or false on error
	 * @since		2.0.0
	 */
	public function getQuoteId()
	{
		$input	=	dunloader( 'input', true );
		$iid	=	$input->getVar( 'id', 0, 'post', 'int' );
		
		if ( $iid === 0 ) {
			return false;
		}
		return (int) $iid;
	}
	
	
	/**
	 * Initialise the module
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.0.0
	 * @see			IntouchAdminDunModule::initialise()
	 */
	public function initialise()
	{
		$this->action = 'quotes';
		parent :: initialise();
	}
	
	
	/**
	 * Method to determine if we should even be running for this invoice
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		boolean
	 * @since		2.0.0
	 */
	public function shouldCustomize()
	{
		// Perform global checks first
		if ( ( $gid = parent :: shouldCustomize() ) === false ) return false;
		
		$params	= $this->getGroupParams( $gid );
		
		// Check the enable setting for the group
		if ( ( (bool) $params->quoteenabled ) === false ) {
			return false;
		}
		
		return true;
	}
}