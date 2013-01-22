<?php defined('DUNAMIS') OR exit('No direct script access allowed');



class IntouchAdminareapagesDunModule extends WhmcsDunModule
{
	
	
	/**
	 * Called to setup custom css and javascript for the adminarea pages
	 * @access		public
	 * @version		@fileVers@
	 * @param		string		- $file: indicates where we are
	 * @param		string		- $action: indicates which action to perform
	 * 
	 * @since		2.0.0
	 */
	public function display( $file = 'quotes', $action = 'manage' )
	{
		$db		=	dunloader( 'database', true );
		$doc	=	dunloader( 'document', true );
		$input	=	dunloader( 'input', true );
		
		switch ( $file ) {
			// For the quotes area
			case 'quotes' :
				
				// Only bother with this if we are managing the quote
				if ( in_array( $action, array( 'manage' ) ) ) {
					$qid		=	$input->getVar( 'id', '0' );
					$choice		=	$this->_getSelectedClientgroup( $qid );
					$options	=	'<option value="0">No Group</option>';
					foreach( $this->_getClientgroups() as $group ) $options .= '<option value="' . $group->id . '"' . ( $group->id == $choice ? ' selected="selected" ' : '' ) . '>' . $group->groupname . '</option>';
					$select	= '<select name="groupid" >' . $options . '</select>';
					
					$html	=	'<tr>'
							.	'	<td class="fieldlabel">'
							.		t( 'intouch.adminarea.quotes.clientgroup' )
							.	'	</td>'
							.	'	<td class="fieldarea">'
							.		$select
							.	'	</td>'
							.	'	<td colspan="2"></td>'
							.	'</tr>';
							
					$js	= <<< JAVASCRIPT
jQuery('#newclientform > table.form > tbody').append('$html');
JAVASCRIPT;
					$doc->addScriptDeclaration( $js );
				}
				
				break;	// End quotes
		} // End file switch
	}
	
	
	/**
	 * Executes a given task
	 * @desc		This method is executed raw from the initial hooks include - smarty is not yet available
	 * 				This method permits interception at saving prior to redirection to handle data WHMCS doesn't trust us with
	 * @access		public
	 * @version		@fileVers@
	 * @param		string		- $file: if known, we can spec where we want execution
	 * @param		string		- $action: if known will set the action to use
	 * @param		array		- $vars: passed by hook point (if called from)
	 * 
	 * @since		2.0.0
	 */
	public function execute( $file = null, $action = 'default', $vars = array() )
	{
		// If we don't know where we are find out
		if ( $file == null ) {
			$file	= get_filename();
		}
		
		$input	=	dunloader( 'input', true );
		$db		=	dunloader( 'database', true );
		
		if ( $action == 'default' ) {
			$action	=	$input->getVar( 'action', $action, 'request', 'string' );
		}
		
		switch ( $file ) {
			// For the quotes area
			case 'quotes' :
				
				// Switch off on the actions
				switch ( $action ) {
					// We are saving a quote here
					case 'save' :
								
						if ( $action == 'save' ) {
							$gid	= $input->getVar( 'groupid', '0' );
							$qid	= $input->getVar( 'id', false );
							
							// This is a new save
							if (! $qid ) {
								$qid	= $this->_findNextQuoteId();
							}
							
							// We have a qid... lets add it or insert it into the db
							$query	= "SELECT * FROM `mod_intouch_quotexref` WHERE `qid` = " . $db->Quote( $qid );
							$db->setQuery( $query );
							$exists	= $db->loadResult();
							
							// Not yet in the xref 
							if (! $exists ) {
								$query	= "INSERT INTO `mod_intouch_quotexref` ( `gid`, `qid` ) VALUES (" . $db->Quote( $gid ) . ", " . $db->Quote( $qid ) . " )";
							}
							// Exists so update
							else {
								$query	= "UPDATE `mod_intouch_quotexref` SET `gid`=" . $db->Quote( $gid ) . " WHERE `qid` = " . $db->Quote( $qid );
							}
							
							$db->setQuery( $query );
							$db->query();
						}
						
						break; // End save action
						
					// We are converting a quote for a non-client to an invoice and creating the client 
					case 'convert' :
						
						// Can't do nuthin
						if (! is_array( $vars ) || ! isset( $vars['userid'] ) ) break;

						$qid	= $input->getVar( 'id', false );
						
						// Find out if we have an xref in the table for the old quote id
						$query	= "SELECT `gid` FROM `mod_intouch_quotexref` WHERE `qid` = " . $db->Quote( $qid );
						$db->setQuery( $query );
						$gid	= $db->loadResult();
						
						// Don't continue if we aren't in xref
						if (! $gid ) break;
						
						// Update the client table directly here
						$query	= "UPDATE `tblclients` SET `groupid` = " . $db->Quote( $gid ) . " WHERE `id` = " . $db->Quote( $vars['userid'] );
						$db->setQuery( $query );
						$db->query();
						
						break;	// End convert action
						
					case 'duplicate' :
						
						$oqid	=	$input->getVar( 'id', false );
						
						// Find out if we have an xref in the table for the old quote id
						$query	= "SELECT `gid` FROM `mod_intouch_quotexref` WHERE `qid` = " . $db->Quote( $oqid );
						$db->setQuery( $query );
						$gid	= $db->loadResult();
						
						// Don't continue if we aren't in xref
						if (! $gid ) break;
						
						$qid	= $this->_findNextQuoteId();
						$query	= "INSERT INTO `mod_intouch_quotexref` ( `gid`, `qid` ) VALUES (" . $db->Quote( $gid ) . ", " . $db->Quote( $qid ) . " )";
						$db->setQuery( $query );
						$db->query();
						
						break;	// End duplicate action
						
					// Include this to clean up the xref table
					case 'delete' :
						
						$qid	= $input->getVar( 'id', false );
						
						if ( $qid ) {
							$query	= "DELETE FROM `mod_intouch_quotexref` WHERE `qid` = " . $db->Quote( $qid );
							$db->setQuery( $query );
							$db->query();
						}
						
						break;	// End delete action
						
				} // End Action Switch;
				
				break; // End quotes
		} // End file switch
		
		return;
	}
	
	
	/**
	 * Initializes the module
	 * @desc		Do nothing here - this is controller is loaded in the hooks
	 * 				and the language / hooks setup will be lost on the rest of the module
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.0.0
	 */
	public function initialise() { }
	
	
	/**
	 * Called up to perform execution and display calls
	 * @desc		This is called raw from the hooks inclusion - we must be careful!
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	public function render()
	{
		// Check first to see if we are even logged in with an aInt
		global $aInt;
		if (! is_object( $aInt ) || ! isset( $aInt->templatevars ) ) return;
		
		$input	= dunloader( 'input', true );
		
		// See if we are on the quotes page
		if ( $aInt->templatevars['filename'] == 'quotes' ) {
			
			// Execute tasks first (save etc)
			$this->execute( 'quotes', $input->getVar( 'action', 'default', 'post' ) );
			
			// Render back what we need
			$this->display( 'quotes', $input->getVar( 'action', 'default' ) );
		}
	}
	
	
	/**
	 * Method for finding the next Quote ID in the WHMCS table
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		integer
	 * @since		2.0.0
	 */
	private function _findNextQuoteId()
	{
		$db		= dunloader( 'database', true );
		$query	= "SHOW TABLE STATUS WHERE name = 'tblquotes'";
		$db->setQuery( $query );
		$status	= $db->loadObject();
		foreach ( $status as $k => $v ) {
			if ( strtolower( $k ) == 'auto_increment' ) {
				$qid = $v;
				break;
			}
		}
		return $qid;
	} 
	
	
	/**
	 * Method for getting client group array
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		array of objects
	 * @since		2.0.0
	 */
	private function _getClientgroups()
	{
		$db	= dunloader( 'database', true );
		$db->setQuery( "SELECT `id`, `groupname` FROM `tblclientgroups` ORDER BY `groupname`" );
		return $db->loadObjectList();
	}
	
	
	/**
	 * Method for getting the selected client group based on a quote id
	 * @access		private
	 * @version		@fileVers@
	 * @param		integer		- $qid: contains the quote id hopefully
	 * 
	 * @return		integer containing group id
	 * @since		2.0.0
	 */
	private function _getSelectedClientgroup( $qid = false )
	{
		if ( $qid === false ) return 0;
		
		$db		=	dunloader( 'database', true );
		$query	=	"SELECT c.groupid as 'id' FROM `tblclients` c INNER JOIN `tblquotes` q ON q.userid = c.id WHERE q.id =" . $db->Quote( $qid );
		$db->setQuery( $query );
		$gid	=	$db->loadResult();
		
		if ( $gid == null ) {
			$query	= "SELECT `gid` as 'id' FROM `mod_intouch_quotexref` WHERE `qid` = " . $db->Quote( $qid );
			$db->setQuery( $query );
			$gid	= $db->loadResult();
			if ( $gid == null ) return 0;
		}
		
		return (int) $db->loadResult();
	}
}