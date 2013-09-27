<?php defined('DUNAMIS') OR exit('No direct script access allowed');


/**
 * In Touch Emails class
 * @version		@fileVers@
 * 
 * @author		Steven
 * @since		2.0.0
 */
class IntouchEmailsDunModule extends WhmcsDunModule
{
	
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
	 * Method for determining if we are dealing with a contact
	 * @desc		WHMCS wipes the reset key prior to getting to us so we must test it in the initialization of the hooks
	 * 				to see if the key belongs to a contact or client
	 * @access		public
	 * @version		@fileVers@
	 * @param		string		- $key: contains the key passed back to us
	 * 
	 * @since		2.0.2
	 */
	public function findClient( $key )
	{
		global $iscontact;
		
		$iscontact = false;
		extract( $this->_findClient( $key, false ) );
	}
	
	
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
	 * @version		2.0.2		- Feb 2013: Password resets dont get processed the same
	 * @param		array		- $vars: contains the vars passed to hook
	 * 
	 * @return		array containing merge_fields or empty for nothing to add
	 * @since		2.0.0
	 */
	public function intercept( $vars = array() )
	{
		$db				=	dunloader( 'database', true );
		$config			=	dunloader( 'config', 'intouch' );
		
		// Check the global enable first - if disabled stop now
		if ( ( (bool) $config->get( 'enable', false ) ) === false ) {
			return array();
		}
		
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
		// # BUG - password reset request catch
		else if ( $email->type == 'general' && $email->name == 'Password Reset Validation' ) {
			$result	=	$this->_sendPasswordEmail( $email, $vars, 'pwreset' );
		}
		// # BUG - password reset catch
		else if ( $email->type == 'general' && is_admin() && $email->name == 'Password Reset Confirmation' ) {
			$result	=	$this->_sendPasswordEmail( $email, $vars, 'passwordbyadmin' );
		}
		// # BUG - password reset catch
		else if ( $email->type == 'general' && is_admin() && $email->name == 'Automated Password Reset' ) {
			$result	=	$this->_sendPasswordEmail( $email, $vars, 'passwordbyadmin' );
		}
		else if ( $email->type == 'general' && $email->name == 'Password Reset Confirmation' ) {
			$result	=	$this->_sendPasswordEmail( $email, $vars, 'password' );
		}
		else if ( $email->type == 'general' && $email->name == 'Order Confirmation' ) {
			$result	=	$this->_sendOrderEmail( $email, $vars, 'orderconfirm' );
		}
		// # BUG - Client Signup Emails send out ***** passwords
		else if ( $email->type == 'general' && $email->name == 'Client Signup Email' ) {
			$result	=	$this->_sendPasswordEmail( $email, $vars, 'clientsignup' );
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
			
			// If we didn't get here by using the massmailquery
			if (! $massmailquery ) {
				$this->_sendEmail( $email, $vars );
			}
			else {
				// If we have the massmailquery globally then we can cycle through each message
				$db->setQuery( $massmailquery );
				$results	= $db->loadObjectList();
				
				// We now have the same results as WHMCS to cycle through
				foreach ( $results as $result ) {
					$this->_sendEmail( $email, array( 'relid' => $result->id ) );
				}
			}
			
			// Clean up
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
	 * Method for finding a client / contact by email
	 * @access		private
	 * @version		@fileVers@
	 * @param		string		- $email: contains the email address to search for
	 * @param		bool		- $isemail: indicates that we are sending an email or the pwresetkey
	 * 
	 * @return		array containing clientid (int) / iscontact (bool)
	 * @since		2.0.2
	 */
	private function _findClient( $email, $isemail = true )
	{
		$db			=	dunloader( 'database', true );
		$clientid	=	false;
		$iscontact	=	false;
		
		$query		=	'SELECT `id`, `email` FROM `tblclients` WHERE ' . ( $isemail ? '`email`' : '`pwresetkey`' ) . ' = ' . $db->Quote( $email );
		$db->setQuery( 'SELECT `id`, `email` FROM `tblclients` WHERE ' . ( $isemail ? '`email`' : '`pwresetkey`' ) . ' = ' . $db->Quote( $email ) );
		$clients	= $db->loadObjectList();
		
		foreach ( $clients as $c ) {
			if (! empty( $c->email ) ) {
				$clientid = $c->id;
				break;
			}
		}
		
		// Nope... try a contact
		if (! $clientid ) {
			$query		= 'SELECT `id`, `email` FROM `tblcontacts` WHERE ' . ( $isemail ? '`email`' : '`pwresetkey`' ) . ' = ' . $db->Quote( $email );
			$db->setQuery( $query );
			$clients	= $db->loadObjectList();
			
			foreach ( $clients as $c ) {
				if (! empty( $c->email ) ) {
					$clientid	= $c->id;
					$iscontact	= true;
					break;
				}
			}
		}
		
		return array( 'clientid' => $clientid, 'iscontact' => $iscontact );
	}
	
	
	/**
	 * Random Generator
	 * @access		private
	 * @version		@fileVers@
	 * @param		integer		- $length: the number of characters in the string
	 * 
	 * @return		string
	 * @since		2.0.2
	 */
	private function _generateRandom( $length = 24 )
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, strlen($characters) - 1)];
		}
		
		return $randomString;
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
	 * 
	 * return		integer | true
	 * @since		2.0.0
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
	 * Method for getting the message to send back for an order
	 * @access		private
	 * @version		@fileVers@ ( $id$ )
	 * @param		string		- $type: the type of item
	 *
	 * @return		string
	 * @since		2.0.8
	 */
	private function _getOrderMessage( $type = 'product' )
	{
		switch ( $type ) :
		case 'domain' :
			$string		=	<<< LANG
Domain Registration: %s<br>
Domain: %s<br>
First Payment Amount: %s<br>
Recurring Amount: %s<br>
Registration Period: %s<br>
LANG;
			break;
		default:
		$string		=	<<< LANG
Product/Service: %s<br>
First Payment Amount: %s<br>
Recurring Amount: %s<br>
Billing Cycle: %s<br>
LANG;
			break;
		endswitch;
		
		return $string;
	}
	
	
	/**
	 * In Admin of WHMCS we may be adding billable items which causes a problem
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @since		2.0.1
	 */
	private function _handleBillableitems()
	{
		$db		= dunloader( 'database', true );
		$input	= dunloader( 'input', true );
		$config = dunloader( 'config', 'intouch' );
		
		// We are wanting to add a billable item and invoice
		if ( $input->getVar( 'billingaction', 0 ) != 3 ) return;
		if ( $input->getVar( 'billingamount', 'Amount' ) == 'Amount' ) return;
		
		// Grab our intended API User
		if ( ( $apiuser = $config->get( 'apiuser' ) ) === false ) {
			$apiuser	= '1';
		}
		
		// Grab the client
		$db->setQuery( "SELECT c.id as `clientid`, c.defaultgateway as `gateway` FROM `tbltickets` t INNER JOIN `tblclients` c ON c.id = t.userid WHERE t.id = " . $db->Quote( $input->getVar( 'id' ) ) );
		$pm = $db->loadObject();
		$date	= date( 'Ymd' );
		
		$vars	= array(
				'userid' => $pm->clientid,
				'date'	=> $date,
				'duedate' => $date,
				'paymentmethod' => $pm->gateway,
				'itemdescription1' => $input->getVar( 'billingdescription' ),
				'itemamount1' => $input->getVar( 'billingamount' ),
				'itemtaxed1' => false,
				'sendinvoice' => true
				);
		
		$result	= localAPI( 'createinvoice', $vars, $apiuser );
		
		$GLOBALS['billingdescription'] = null;
		$GLOBALS['billingamount'] = 'Amount';
		$GLOBALS['billingaction'] = 0;
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
	 * Method for handling order confirmation emails
	 * @desc		Order confirmation emails are being sent out but do not include the order details.
	 * @access		private
	 * @version		@fileVers@
	 * @param		object		- $email: contains the retrieved email object from the database
	 * @param		array		- $vars: the variables passed to us by the hook originally
	 * @param		string		- $type: provided to allow for switching based on email type
	 *
	 * @return		boolean result of email call
	 * @since		2.0.4
	 */
	private function _sendOrderEmail( $email, $vars, $type )
	{
		$config		=	dunloader( 'config', 'intouch' );
		$db			=	dunloader( 'database', true );
		$input		=	dunloader( 'input', true );
		
		// Grab our intended API User
		if ( ( $apiuser = $config->get( 'apiuser' ) ) === false ) {
			$apiuser	= '1';
		}
		
		switch ( $type ) {
			case 'orderconfirm':
				// First lets get the order
				$orders		= (object) localAPI( 'getorders', array( 'userid' => $vars['relid'] ), '1' );
				
				// Be sure we found one and get the very first order
				if ( $orders->result != 'success' ) return false;
				if ( $orders->totalresults == '0' ) return false;
				
				$order		=	(object) $orders->orders['order']['0'];
				$message	=	array();
				
				// Cycle through the order line items to build the array
				foreach ( $order->lineitems['lineitem'] as $item ) {
					
					$item	=	(object) $item;
					
					// We must build the recurring amount for the each product (stupid...)
					if ( $item->type == 'domain' ) {
						$service		= (object) localAPI( 'getclientsdomains', array( 'domainid' => $item->relid ), '1' );
						$service		= (object) $service->domains['domain'][0];
					}
					else {
						$service		= (object) localAPI( 'getclientsproducts', array( 'serviceid' => $item->relid ), '1' );
						$service		= (object) $service->products['product'][0];
					}
					
					$string			=	$this->_getOrderMessage( $item->type );
					$recurring		=	$order->currencyprefix . $service->recurringamount . $order->currencysuffix;
					
					if ( $item->type == 'domain' ) {
						$message[]		=	sprintf( $string, $item->product, $item->domain, $item->amount, $recurring, $item->billingcycle . ' Year/s' );
					}
					else {
						$message[]		=	sprintf( $string, $item->product, $item->amount, $recurring, $item->billingcycle );
					}
					
				}
				
				$string		=	<<< STRING
%s
<br>
Total Due Today: %s%s%s
STRING;
				
				$message	=	sprintf( $string, implode( "<br>", $message ), $order->currencyprefix, $order->amount, $order->currencysuffix );
				
				// Add in the order #
				$regex			=	'#{\$order_number}#i';
				$email->message	=	preg_replace( $regex, $order->ordernum, $email->message );
				
				// Add in the details
				$regex			=	'#{\$order_details}#i';
				$email->message	=	preg_replace( $regex, str_replace( '$', '\$', $message ), $email->message );
				
				$result = $this->_sendEmail( $email, $vars );
				
				return $result;
				break;
		}
		
		
	}
	
	
	/**
	 * Method for handling password reset emails
	 * @desc		Password resets are screwy in WHMCS... the token isn't available to pass along for the pwreset request email
	 * 				and the new password isn't generated until after the preemailsend hook is called
	 * @access		private
	 * @version		@fileVers@
	 * @param		object		- $email: contains the retrieved email object from the database
	 * @param		array		- $vars: the variables passed to us by the hook originally
	 *
	 * @return		boolean result of email call
	 * @since		2.0.2
	 */
	private function _sendPasswordEmail( $email, $vars, $type )
	{
		$config		=	dunloader( 'config', 'intouch' );
		$db			=	dunloader( 'database', true );
		$input		=	dunloader( 'input', true );
		
		// Grab our intended API User
		if ( ( $apiuser = $config->get( 'apiuser' ) ) === false ) {
			$apiuser	= '1';
		}
		
		switch ( $type ) {
			// Client Signup Email catch
			case 'clientsignup' :
				
				// Grab the password
				$passwd	=	$input->getVar( 'password', false );
				
				// If we don't have it for some reason get outta here
				if ( $passwd === false ) {
					return false;
				}
				
				// Change out the password
				$regex			=	'#{\$client_password}#i';
				$email->message	=	preg_replace( $regex, $passwd, $email->message );
				
				return $this->_sendEmail( $email, $vars );
				
				break;
				
			case 'pwreset' :
				$timestamp	= time() + ( 2 * 60 * 60 );
				$key		= $this->_generateRandom();
				$iscontact	= false;
				$clientid	= false;
				
				// Find client first
				extract ( $this->_findClient( $input->getVar( 'email' ) ) );
				
				// Send it back so we at least send something out...
				if (! $clientid ) return false;
				
				// WHMCS does not permit contact sends properly
				if ( $iscontact ) return false;
				
				// Lets create the URL
				$whmcsconf	=	dunloader( 'config', true );
				$url	=	( $whmcsconf->get( 'SystemSSLURL' ) ? $whmcsconf->get( 'SystemSSLURL' ) : $whmcsconf->get( 'SystemURL' ) ) . '/pwreset.php?key=' . $key;
				
				// Change out the URL now...
				$regex			=	'#{\$pw_reset_url}#i';
				$email->message	=	preg_replace( $regex, $url, $email->message );
				
				$result = $this->_sendEmail( $email, $vars );
				
				// NOW we update the database since WHMCS has just done so
				$query	= "UPDATE " . ( $iscontact ? "`tblcontacts`" : "`tblclients`" ) . " SET `pwresetkey` = " . $db->Quote( $key ) . ", `pwresetexpiry` = " . $timestamp . " WHERE id = " . $clientid;
				$db->setQuery( $query );
				$db->query();
				
				return $result;
				
				break;
			case 'password' :
				
				// New password
				$new_password	= $this->_generateRandom( 8 );
				
				global $iscontact;
				
				// WHMCS doesn't handle contacts the same (stupid)
				if ( $iscontact ) return false;
				
				// Find client first
				$clientid	= $vars['relid'];
				
				// Send it back so we at least send something out...
				if (! $clientid ) return false;
				
				// Change out the password now...
				$regex			=	'#{\$client_password}#i';
				$email->message	=	preg_replace( $regex, $new_password, $email->message );
				$result			=	$this->_sendEmail( $email, $vars );
				
				// We have to update the database properly
				$salt	=	$this->_generateRandom( 5 );
				$md5	=	md5( $salt . $new_password ) . ':' . $salt;
				$query	=	"UPDATE " . ( $iscontact ? "`tblcontacts`" : "`tblclients`" ) . " SET `password` = " . $db->Quote( $md5 ) . " WHERE `id` = " . $clientid;
				
				$db->setQuery( $query );
				$db->query();
				
				return $result;
				
				break;
			case 'passwordbyadmin' :
				
				// New password
				$new_password	= $this->_generateRandom( 8 );
				
				$file			=	get_filename();
				$iscontact		=	$file == 'clientssummary' ? false : true;
				$clientid		=	$input->getVar( 'userid' );
				
				// WHMCS v5.0 / 5.1 / 5.2 do not permit contact intercepts
				if ( $iscontact ) return false;
				
				// Change out the password now...
				$regex			=	'#{\$client_password}#i';
				$email->message	=	preg_replace( $regex, $new_password, $email->message );
				$result			=	$this->_sendEmail( $email, $vars );
				
				return $result;
				break;
		} // End Switch
	}
	
	
	/**
	 * Method for handling support emails
	 * @desc		Support Replies pass only the ticket ID, so passing it on to the send email results
	 * 				in just the original message being sent
	 * @access		private
	 * @version		@fileVers@
	 * @version		2.0.3		- cron runs to escalate tickets with replies being made fail to send message because $message isnt set $addreply is
	 * @version		2.0.1		- when creating invoice from support ticket WHMCS tries to reload invoice functionality
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
			global $message, $addreply;
			$regex	=	'#{\$ticket_message}#i';
			
			// If we are running through the cron then message isn't set, addreply is
			$usemsg	=	(! empty( $message ) ? $message : $addreply );
			
			
			if ( empty( $usemsg ) ) {
				$db	=	dunloader( 'database', true );
				$db->setQuery( "SELECT `message` FROM `tblticketreplies` WHERE `tid` = " . $db->Quote( $vars['relid'] ) . " AND `userid` = 0 ORDER BY `id` DESC LIMIT 1" );
				$usemsg	=	addslashes( $db->loadResult() );
			}
			
			$email->message	= preg_replace( $regex, str_replace( '$', '\$', nl2br( $usemsg ) ), $email->message ); // . '<pre>' . print_r( $vars, 1 ) . print_r( $email, 1 ). print_r( $db, 1 ) . '</pre>';
			
			// We have to catch billable items due to poor WHMCS programming
			if ( is_admin() ) {
				$this->_sendEmail( $email, $vars );
				$this->_handleBillableitems();
				return true;
			}
		}
		
		return $this->_sendEmail( $email, $vars );
	}
}	