<?php defined('DUNAMIS') OR exit('No direct script access allowed');



class IntouchClientareapagesDunModule extends WhmcsDunModule
{
	
	
	/**
	 * Called to setup custom css and javascript for the adminarea pages
	 * @access		public
	 * @version		@fileVers@
	 * @param		string		- $file: indicates where we are
	 * @param		string		- $action: indicates which action to perform
	 * 
	 * @since		2.1.0
	 */
	public function display( $file = 'quotes', $action = 'manage' )
	{
		
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
	 * @since		2.1.0
	 */
	public function execute( $file = null, $action = 'default', $vars = array() )
	{
		// If we don't know where we are find out
		if ( $file == null ) {
			$file	= get_filename();
		}
		
		$input	=	dunloader( 'input', true );
		$config	=	dunloader( 'config', 'intouch' );
		$db		=	dunloader( 'database', true );
		
		if ( $action == 'default' ) {
			$action	=	$input->getVar( 'action', $action, 'request', 'string' );
		}
		
		// Grab our intended API User
		if ( ( $apiuser = $config->get( 'apiuser' ) ) === false ) {
			$apiuser	= '1';
		}
		
		// See if we want to customize the front end
		if ( $config->fetoenable == '1' ) {
			// Perform front end template customization now
			$tpl	=	false;
			$useid	=	( isset( $GLOBALS['_SESSION']['uid'] ) ? $GLOBALS['_SESSION']['uid'] : false );
			
			// Lets see if we are on the login and catch those (we dont come back here after login to grab session)
			if ( get_filename() == 'dologin' ) {
				$username	=	$input->getVar( 'username', null );
				$result		=	$db->setQuery( "SELECT `id` FROM `tblclients` WHERE `email` = " . $db->Quote( $username ) );
				$client		=	$db->loadResult();
				$useid		=	$client ? $client : false;
			}
			
			
			// Grab the template
			if ( $useid ) {
				$tpl = $this->_getTemplatevalue( $useid );
			}
			else {
				// See if we passed along a client group id
				$itcg	=	$input->getVar( 'itcg', false, 'request' );
				
				if ( $itcg ) {
					$tpl	=	$this->_getTemplatevalue( $itcg, false );
					
					// Set this to our session so we can pull if they register
					$GLOBALS['_SESSION']['itcg']	=	$itcg;
				}
			}
			
			// Ensure we received a template name back
			if ( $tpl ) {
				global $systpl;
				$systpl = $tpl;
				
				$GLOBALS['_SESSION']['Template']	= $tpl;
				$GLOBALS['CONFIG']['Template']		= $tpl;
			}
		}
		
		return;
	}
	
	
	/**
	 * Method to handle new user signups
	 * @access		public
	 * @version		@fileVers@ ( $id$ )
	 * @param		array		- $vars: array of variables passed to us
	 *
	 * @since		2.1.0
	 */
	public function handlenewuser( $vars = array() )
	{
		$input	=	dunloader( 'input', true );
		$config	=	dunloader( 'config', 'intouch' );
		$db		=	dunloader( 'database', true );
		
		// See if we should do anything
		if ( $config->fetoenable == '1' && isset( $GLOBALS['_SESSION']['itcg'] ) && isset( $vars['relid'] ) ) {
			$itcg	=	$GLOBALS['_SESSION']['itcg'];
		}
		else {
			return;
		}
		
		// Grab our intended API User
		if ( ( $apiuser = $config->get( 'apiuser' ) ) === false ) {
			$apiuser	= '1';
		}
		
		// We are here to update the client
		localAPI( 'UpdateClient', array( 'clientid' => $vars['relid'], 'groupid' => $itcg ), $apiuser );
	}
	
	
	/**
	 * Initializes the module
	 * @desc		Do nothing here - this is controller is loaded in the hooks
	 * 				and the language / hooks setup will be lost on the rest of the module
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.1.0
	 */
	public function initialise() { }
	
	
	/**
	 * Called up to perform execution and display calls
	 * @desc		This is called raw from the hooks inclusion - we must be careful!
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @since		2.1.0
	 */
	public function render()
	{
		
	}
	
	
	/**
	 * Method to get the template value to set
	 * @access		private
	 * @version		@fileVers@
	 * @param		integer		- $clientid: should be the client id from the session variable
	 * 
	 * @return		string containing the selected template to use
	 * @since		2.1.0
	 */
	private function _getTemplatevalue( $clientid, $is_client = true )
	{
		$db	= dunloader( 'database', true );
		
		if ( $is_client ) {
			if ( $clientid !== false ) {
				$db->setQuery( "SELECT `params` FROM `tblclients` c INNER JOIN `mod_intouch_groups` g ON c.groupid = g.group WHERE c.id = " . $db->Quote( $clientid ) . " LIMIT 1" );
			}
			else {
				$db->setQuery( "SELECT `params` FROM `mod_intouch_groups` g WHERE g.group = '0' LIMIT 1" );
			}
		}
		else {
			$db->setQuery( "SELECT `params` FROM `mod_intouch_groups` g WHERE g.group = " . $db->Quote( $clientid ) . " LIMIT 1" );
		}
		
		$params	= $db->loadResult();
		
		if (! $params ) return false;
		else $params = json_decode( $params );
		
		if (! isset( $params->template ) || $params->template == '0'  ) return false;
		else return $params->template;
	}
}