<?php defined('DUNAMIS') OR exit('No direct script access allowed');



class IntouchEmailsDunModule extends WhmcsDunModule
{
	
	/**
	 * Initializes the module
	 * @desc		Do nothing here - this controller is loaded in the hooks
	 * 				and the language / hooks setup will be lost on the rest of the module
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.0.0
	 */
	public function initialise() { }
	
	
	/**
	 * Method to catch the pre-email send hook and change template out
	 * @access		public
	 * @version		@fileVers@
	 * @param		array		- $vars: contains the vars passed to hook
	 * 
	 * @return		array containing merge_fields or empty for nothing to add
	 * @since		2.0.0
	 */
	public function intercept( $vars = array() )
	{
		$db				=	dunloader( 'database', true );
		$config			=	dunloader( 'config', 'intouch' );
		
		// First see if we have a matching email 
		$db->setQuery( "SELECT * FROM `tblemailtemplates` WHERE `name` = " . $db->Quote( $vars['messagename'] ) );
		$email	=	$db->loadObject();
		
		// No email in database or is admin so cant do anything
		if (! $email || $email->type == 'admin' ) return array();
		
		// Special carve out for support tickets
		if ( $email->type == 'support' ) {
			$result	=	$this->_sendSupportEmail( $email, $vars );
		}
		// Quote Accepted Notification goes to the admin... not a general email 
		else if ( $email->name == 'Quote Accepted Notification' ) {
			return array();
		}
		// If we still find Quote in the name, we have customized the email template itself
		else if ( strpos( $email->name, 'Quote' ) !== false ) {
			$email->type = 'quote';
			$merge_fields	=	$this->_getCustomvars( $email, $vars['relid'], false );
			return $merge_fields;
		}
		else {
			$result	=	$this->_sendEmail( $email, $vars );
		}
		
		if (! $result ) return array();
		
		// We don't want to send the originating email so indicate such
		return array( 'abortsend' => true );
	}
	
	
	/**
	 * Method to verify and customize the mass mail template being used
	 * @access		public
	 * @version		@fileVers@
	 * @param		array		- $vars: contains the vars passed to hook
	 * 
	 * @return		array containing merge_fields or empty for nothing to add
	 * @since		2.0.0
	 */
	public function massmailcheck( $vars = array() )
	{
		$db				=	dunloader( 'database', true );
		$merge_fields	=	array();
		
		$db->setQuery( "SELECT * FROM `tblemailtemplates` WHERE `name` = " . $db->Quote( $vars['messagename'] ) );
		$email	=	$db->loadObject();
		
		// Test message to see if this is our customization or a Mass Email through WHMCS tool
		if ( strpos( $email->message, '{$intouchheader}' ) === false ) {
			// We are HIJACKING the Mass Mail Tool
			global $massmailquery;
			$db->setQuery( $massmailquery );
			$results	= $db->loadObjectList();
			
			// We now have the same results as WHMCS to cycle through
			foreach ( $results as $result ) {
				$this->_sendEmail( $email, array( 'relid' => $result->id ) );
			}
			
			$this->cleanMess();
			
			// Abort the initial mass email
			$merge_fields	= array( 'abortsend' => true );
		}
		else {
			$merge_fields	= $this->_getCustomvars( $email, $vars['relid'], false );
		}
		
		return $merge_fields;
	}
	
	
	/**
	 * When we send a custom email through WHMCS, they dont wipe the mass mail template for some reason
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	public function cleanMess()
	{
		$db		=	dunloader( 'database', true );
		
		if ( version_compare( DUN_ENV_VERSION, '5.1', 'ge' ) ) {
			$db->setQuery( "DELETE FROM `tblemailtemplates` WHERE `name` = 'Mass Mail Template'" );
		}
		else {
			$db->setQuery( "DELETE FROM `tblemailtemplates` WHERE `name` LIKE 'In Touch%'" );
		}
		$db->query();
	}
	
	
	/**
	 * Method to gather the custom variables from In Touch
	 * @access		private
	 * @version		@fileVers@
	 * @param		object		- $email: the retrieved email
	 * @param		integer		- $id: the relid passed along
	 * @param		boolean		- $encode: how do we want them back
	 * 
	 * @return		array or string depending on encode
	 * @since		2.0.0
	 */
	private function _getCustomvars( $email, $id = 0, $encode = true )
	{
		$db		=	dunloader( 'database', true );
		$vars	=	array();
		
		// Find the group id - bail if not found
		$groupid	= $this->_getGroupId( $email->type, $id );
		if ( $groupid === false ) return array();
		
		// Retrieve the group settings from In Touch
		$db->setQuery( "SELECT `params` FROM `mod_intouch_groups` WHERE `group` = " . $db->Quote( $groupid ) . " AND `active` = '1'" );
		
		if (! ( $params	=	$db->loadResult() ) ) {
			return ( $encode ? base64_encode( serialize( $vars ) ) : $vars );
		}
		
		$params	=	json_decode( $params, false );
		
		if (! empty( $params->emailcss ) ) $params->emailcss = '<style>' . $params->emailcss . '</style>';
		
		$vars['intouchstyle']		=	$params->emailcss;
		$vars['intouchheader']		=	html_entity_decode( html_entity_decode( $params->emailheader ) );
		$vars['intouchsignature']	=	html_entity_decode( html_entity_decode( $params->emailsig ) );
		$vars['intouchfooter']		=	html_entity_decode( html_entity_decode( $params->emailfooter ) );
		$vars['intouchlegal']		=	html_entity_decode( html_entity_decode( $params->emaillegal ) );
		
		if ( $encode ) {
			return base64_encode( serialize( $vars ) );
		}
		else {
			return $vars;
		}
		
	}
	
	
	/**
	 * Method for getting the group id dependant upon the type of email
	 * @access		private
	 * @version		@fileVers@
	 * @param		string		- $type: (general|product|support|affiliate|domain|invoice|
	 * @param unknown_type $type
	 * @param unknown_type $id
	 */
	private function _getGroupId( $type = 'general', $id = 0 )
	{
		$db		=	dunloader( 'database', true );
		$userid	=	0;
		switch ( $type ) {
			// General templates are client emails so the ID passed to us is already the client id
			case 'general':
				$userid	= $id;
				break;
			// Product templates contain the tblhosting id.. ?
			case 'product' :
				$db->setQuery( "SELECT `userid` FROM `tblhosting` WHERE `id` = " . $db->Quote( $id ) );
				$userid	= $db->loadResult();
				break;
			case 'support' :
				$db->setQuery( "SELECT `userid` FROM `tbltickets` WHERE `id` = " . $db->Quote( $id ) );
				$userid	= $db->loadResult();
				break;
			case 'invoice' :
				$db->setQuery( "SELECT `userid` FROM `tblinvoices` WHERE `id` = " . $db->Quote( $id ) );
				$userid	= $db->loadResult();
				break;
			case 'domain' :
				$db->setQuery( "SELECT `userid` FROM `tbldomains` WHERE `id` = " . $db->Quote( $id ) );
				$userid	= $db->loadResult();
				break;
			case 'affiliate' :
				$db->setQuery( "SELECT `clientid` FROM `tblaffiliates` WHERE `id` = " . $db->Quote( $id ) );
				$userid	= $db->loadResult();
				break;
			// I made this one up... there is no quote group but we are catching them
			case 'quote' :
				// Our quote id is actually in the input handler
				$id	=	dunloader( 'input', true )->getVar( 'id' );
				
				// See if we have a userid
				$db->setQuery( "SELECT `userid` FROM `tblquotes` WHERE `id` = " . $db->Quote( $id ) );
				$userid	= $db->loadResult();
				
				// See if this is a quote for a non-customer
				if ( $userid == '0' ) {
					$db->setQuery( "SELECT `gid` FROM `mod_intouch_quotexref` WHERE `qid` = " . $db->Quote( $id ) );
					$gid	= $db->loadResult();
					
					return $gid == null ? false : $gid;
				}
				
				break;
			default:
				
				break;
		}
		
		$db->setQuery( "SELECT `groupid` FROM `tblclients` WHERE `id` = " . $db->Quote( $userid ) );
		$group	= $db->loadResult();
		
		return $group == null ? false : $group;
	}
	
	
	/**
	 * Method for sending our email out through their api
	 * @access		private
	 * @version		@fileVers@
	 * @param		object		- $email: contains the retrieved email object from the database
	 * @param		array		- $vars: the variables passed to us by the hook originally
	 * 
	 * @return		boolean result of email call
	 * @since		2.0.0
	 */
	private function _sendEmail( $email, $vars )
	{
		$config		=	dunloader( 'config', 'intouch' );
		
		// Grab our intended API User
		if ( ( $apiuser = $config->get( 'apiuser' ) ) === false ) {
			$apiuser	= '1';
		}
		
		$emailvars = array();
		foreach ( $email as $item => $value ) {
			// No id or name...
			if ( in_array( $item, array( 'id', 'name' ) ) ) continue;
			
			// Dont add empty parts
			if ( empty( $value ) ) continue;
				
			if ( $item == 'message' ) {
				$regex	=	'#{\$signature}#i';
				$value	=	preg_replace( $regex, '{$intouchsignature}', $value );
				$value	=	'{$intouchstyle}{$intouchheader}' . $value . '{$intouchfooter}{$intouchlegal}';
			}
			
			$emailvars['custom' . $item] = $value;
		}
		
		$emailvars['id']			= $vars['relid'];
		
		if ( version_compare( DUN_ENV_VERSION, '5.1', 'l' ) ) {
			$db		=	dunloader( 'database', true );
			$query	=	"INSERT INTO `tblemailtemplates` (`type`, `name`, `subject`, `message`, `attachments`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext` ) VALUES ("
					.	sprintf( '%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s',
							$db->Quote( $email->type ), 
							$db->Quote( 'In Touch ' . $email->name ), 
							$db->Quote( $email->subject ), 
							$db->Quote( $emailvars['custommessage'] ), 
							$db->Quote( $email->attachments ), 
							$db->Quote( $email->fromname ), 
							$db->Quote( $email->fromemail ), 
							$db->Quote( $email->disabled ), 
							$db->Quote( $email->custom ), 
							$db->Quote( $email->language ), 
							$db->Quote( $email->copyto ), 
							$db->Quote( $email->plaintext ) ) 
					.	")";
			$db->setQuery( $query );
			$db->query();
			
			$emailvars['messagename']	=	'In Touch ' . $email->name;
			$emailvars['customvars']	=	$this->_getCustomvars( $email, $vars['relid'] );
		}
		
		$result	= localAPI( 'sendemail', $emailvars, $apiuser );
		
		return $result['result'] == 'success';
	}
	
	
	/**
	 * Method for handling support emails
	 * @desc		Support Replies pass only the ticket ID, so passing it on to the send email results
	 * 				in just the original message being sent
	 * @access		private
	 * @version		@fileVers@
	 * @param		object		- $email: contains the retrieved email object from the database
	 * @param		array		- $vars: the variables passed to us by the hook originally
	 * 
	 * @return		boolean result of email call
	 * @since		2.0.0
	 */
	private function _sendSupportEmail( $email, $vars )
	{
		// For some reason we dont have the ability to send without hijacking like this
		if ( $email->name == 'Support Ticket Reply' ) {
			global $message;
			$regex	=	'#{\$ticket_message}#i';
			$email->message	= preg_replace( $regex, nl2br( $message ), $email->message );
		}
		
		return $this->_sendEmail( $email, $vars );
	}
}	