<?php defined('DUNAMIS') OR exit('No direct script access allowed');

if (! defined( 'DUN_MOD_INTOUCH' ) ) define( 'DUN_MOD_INTOUCH', "@fileVers@" );

class IntouchClientDunModule extends WhmcsDunModule
{
	protected $action	= 'default';
	protected $task		= 'default';
	protected $type		= 'addon';
	
	
	/**
	 * Method to retrieve the address for quotes or invoices
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		array
	 * @since		2.0.0
	 */
	public function getCustomAddress()
	{
		$params	=	$this->getGroupParams();
	
		// Somehow we don't have what we need...
		if (! $params ) return false;
		
		if ( $this->action == 'invoices' ) {
			$address	= trim( $params->invoiceadd );
		}
		else {
			$address	= trim( $params->quoteadd );
		}
		
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
	public function getGroupId( $id = false )
	{
		// If we don't have an ID...
		if ( $id === false ) {
				
			// Find it...
			if ( ( $id = $this->getMissingId() ) === false ) {
				// Error - we dont have an invoice ID
				return false;
			}
		}
		
		return (int) $id;
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
		if ( $gid === false ) {
			$gid = $this->getGroupId();
			if ( $gid === false ) {
				// Error - we dont have a Group ID
				return false;
			}
		}
		
		return $gid;
	}
	
	
	/**
	 * Method to get the legal footer for pdfs
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing any HTML specified for group
	 * @since		2.1.0
	 */
	public function getLegalFooter()
	{
		$params	=	$this->getGroupParams();
		
		// Somehow we don't have what we need...
		if (! $params ) return null;
		
		if ( $this->action == 'invoices' ) {
			if ( $params->invoiceusefooter == '1' ) return html_entity_decode( $params->invoicelegalfooter );
			else return null;
		}
		else {
			if ( $params->quoteusefooter == '1' ) return html_entity_decode( $params->quotelegalfooter );
			else return null;
		}
	}
	
	
	/**
	 * Method to get the logo path from the parameters
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		string containing path or false on non-existant file
	 * @since		2.0.0
	 */
	public function getLogoPath()
	{
		$params	=	$this->getGroupParams();
	
		// Somehow we don't have what we need...
		if (! $params ) return false;
	
		if ( $this->action == 'invoices' ) {
			$path	=	ROOTDIR . $params->invoicelogo;
		}
		else {
			$path	=	ROOTDIR . $params->quotelogo;
		}
		
		if ( file_exists( $path ) ) return $path;
		else return false;
	}
	
	
	/**
	 * Method to get the logo url from the parameters
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		string containing relative url or false on empty
	 * @since		2.0.0
	 */
	public function getLogoUrl()
	{
		$params	=	$this->getGroupParams();
	
		// Somehow we don't have what we need...
		if (! $params ) return false;
		
		if ( $this->action == 'invoices' ) {
			return trim( $params->invoicelogo, '/' );
		}
		else {
			return trim( $params->quotelogo, '/' );
		}
	}
	
	
	/**
	 * Method for retrieving a missing ID
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		integer containing id or false on none
	 * @since		2.0.0
	 */
	public function getMissingId()
	{
		// Retrieve a missing ID
		if ( $this->action == 'invoices' ) {
			$id = $this->getInvoiceId();
		}
		else {
			$id = $this->getQuoteId();
		}
	
		return $id;
	}
	
	
	/**
	 * Initializes the module
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.0.0
	 */
	public function initialise()
	{
		static $instance = false;
	
		if (! $instance ) {
			dunloader( 'language', true )->loadLanguage( 'intouch' );
			dunloader( 'hooks', true )->attachHooks( 'intouch' );
			dunloader( 'helpers', 'intouch' );
			$instance	= true;
		}
		
		$this->task = dunloader( 'input', true )->getVar( 'task', 'default' );
	}
	
	
	/**
	 * Method to determine if we should even be running for this invoice
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		integer containing group id or false on no customization to be done
	 * @since		2.0.0
	 */
	public function shouldCustomize()
	{
		$config	=	dunloader( 'config', 'intouch' );
		$gid	=	$this->getGroupId();
		
		// Catch empty invoice id
		if ( $gid === false ) {
			return false;
		}
		
		// Check the global enable first
		if ( ( (bool) $config->get( 'enable', false ) ) === false ) {
			return false;
		}
		
		return $gid;
	}
}