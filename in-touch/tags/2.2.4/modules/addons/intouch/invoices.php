<?php defined('DUNAMIS') OR exit('No direct script access allowed');

include_once( 'client.php' );

class IntouchInvoicesDunModule extends IntouchClientDunModule
{
	/**
	 * Method for customizing an invoice
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		void
	 * @since		2.2.2
	 */
	public function customizeInvoice()
	{
		global $smarty;
		$logo			= false;
		$legalfooter	= null;
		
		if (! $this->shouldCustomize() ) return;
		
		// Grab the global Smarty object
		$sm		= $GLOBALS['smarty'];
			
		// Assign the custom logo and the custom payto variables to the template
		$sm->assign( 'mylogo', $this->getLogoUrl() );
		
		if ( $addr	=	$this->getCustomAddress() )	$sm->assign( 'mypayto', implode( "<br/>", $addr ) );
		if ( $legal	=	$this->getLegalFooter() )	$sm->assign( 'legal', htmlspecialchars_decode( $legal) );
		
	}
	
	
	/**
	 * Method for retrieving the address from system
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		array containing address
	 * @since		2.0.0
	 * @see			IntouchClientDunModule::getCustomAddress()
	 */
	public function getCustomAddress()
	{
		$params	=	$this->getGroupParams();
		
		// Somehow we don't have what we need...
		if (! $params ) return false;
		
		$address	= trim( $params->invoiceadd );
		
		if ( empty( $address ) ) return false;
		
		$parts	= explode( "||", $address );
		return $parts;
	}
	
	
	/**
	 * Retrieves the group id based on the invoice id
	 * @access		public
	 * @version		@fileVers@
	 * @param		integer		- $id: should contain the found invoice id
	 * 
	 * @return		integer
	 * @since		2.0.0
	 */
	public function getGroupId( $id = 0 )
	{
		if (! $id ) {
			
			// See if we are converting the quote and get the appropriate group id from that
			if ( $this->_checkQuoteConvert() ) {
				$quotes	= dunmodule( 'intouch.quotes' );
				return $quotes->getGroupId();
			}
			
			$id = $this->getInvoiceId();
			if (! $id ) {
				// Error - we dont have an invoice ID
				return false;
			}
		}
		
		$db		= dunloader( 'database', true );
		$query	=	"SELECT c.groupid as 'id' FROM `tblclients` c INNER JOIN `tblinvoices` i ON i.userid = c.id WHERE i.id =" . $db->Quote( $id );
		$db->setQuery( $query );
		
		return (int) $db->loadResult();
	}
	
	
	/**
	 * Method for retrieving and decoding the group parameters
	 * @access		public
	 * @version		@fileVers@
	 * @param		integer		- $gid: contains the group id if known
	 * 
	 * @return		object containing parameters
	 * @since		2.0.0
	 * @see			IntouchClientDunModule::getGroupParams()
	 */
	public function getGroupParams( $gid = false )
	{
		if ( $gid === false ) {
			$gid = $this->getGroupId();
			if ( $gid === false ) {
				// Error - we dont have a Group ID
				return false;
			}
		}
		
		// Get group data
		$result	=	getGroupData( $gid );
		$params	=	json_decode( $result->params );
		
		return $params;
	}
	
	
	/**
	 * Common method for getting the invoice id from input handler
	 * @access		public
	 * @version		@fileVers@
	 * @version		2.0.9		-	Sept 2013: adjustment for invoices generated automatically
	 * 
	 * @return		integer or false on error
	 * @since		2.0.0
	 */
	public function getInvoiceId()
	{
		$input	=	dunloader( 'input', true );
		$iid	=	$input->getVar( 'id', 0, 'post', 'int' );
		
		if ( $iid === 0 ) {
			// ---- Begin: INTOUCH-1
			//		Invoices are not customized on Ordering or Cron
			global $invoiceid;
			
			// Order handling and cron checks
			if (! empty( $invoiceid ) ) {
				$iid	=	$invoiceid;
			}
			else {
				return false;
			}
			// ---- End: INTOUCH-1
		}
		return $iid;
	}
	
	
	/**
	 * Method to get the logo path from the system
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing path to logo or false on error
	 * @since		2.0.0
	 * @see			IntouchClientDunModule::getLogoPath()
	 */
	public function getLogoPath()
	{
		$params	=	$this->getGroupParams();
		
		// Somehow we don't have what we need...
		if (! $params ) return false;
		
		$path	=	rtrim( ROOTDIR, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $params->invoicelogo;
		if ( file_exists( $path ) ) return $path;
		else return false;
	}
	
	
	/**
	 * Method to get the logo URL from the system
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing url to logo or false on error
	 * @since		2.0.0
	 * @see			IntouchClientDunModule::getLogoUrl()
	 */
	public function getLogoUrl()
	{
		$params	=	$this->getGroupParams();
		
		// Somehow we don't have what we need...
		if (! $params ) return false;
		return trim( $params->invoicelogo, '/' );
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
		$this->action = 'invoices';
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
		if ( $this->_checkQuoteConvert() ) {
			$quotes	= dunmodule( 'intouch.quotes' );
			return $quotes->shouldCustomize();
		}
		
		$config	=	dunloader( 'config', 'intouch' );
		$iid	=	$this->getInvoiceId();
		$gid	=	$this->getGroupId( $iid );
		
		// Catch empty invoice id
		if ( $gid === false ) {
			return false;
		}
		
		// Check the global enable first
		if ( ( (bool) $config->get( 'enable', false ) ) === false ) {
			return false;
		}
		
		$params	= $this->getGroupParams( $gid );
		
		// Check the invoices enable setting for the group
		if ( ( (bool) $params->invoiceenabled ) === false ) {
			return false;
		}
		
		return true;
	}
	
	
	/**
	 * Method to see if we are converting a quote
	 * @desc		During quote conversion, the id being sent over to us is the quote id, not the invoice id
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		boolean
	 * @since		2.0.0
	 */
	private function _checkQuoteConvert()
	{
		$input	=	dunloader( 'input', true );
		return ( get_filename() == 'quotes' && $input->getVar( 'action', null ) == 'convert' ) === true;
	}
}