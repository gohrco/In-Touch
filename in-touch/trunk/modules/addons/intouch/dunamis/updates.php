<?php defined('DUNAMIS') OR exit('No direct script access allowed');
/**
 * In Touch Updates File
 * This is the Updates handler for In Touch
 *
 * @package         @packageName@
 * @version         @fileVers@
 *
 * @author          @buildAuthor@
 * @link            @buildUrl@
 * @copyright       @copyRight@
 * @license         @buildLicense@
 */


/**
 * In Touch Updates class handler
 * @version		@fileVers@
 *
 * @author		Steven
 * @since		2.0.0
 */
class IntouchDunUpdates extends WhmcsDunUpdates
{
	protected $_exceptions	=	array( 'README.txt' );
	protected $_expires		=	86400; // TTL
	protected $_installpath	=	null;
	//protected $_url			=	'http://localhost/updates/belong.xml';
	protected $_url			=	'https://www.gohigheris.com/updates/whmcsmodules/intouch';
	protected $_version		=	'@fileVers@';
	
	/**
	 * Constructor method
	 * @access		public
	 * @version		@fileVers@ ( $id$ )
	 * @param		array		- $options: anything we want to set
	 *
	 * @since		2.0.0
	 */
	public function __construct( $options = array() )
	{
		$options	=	parent :: __construct( $options );
		$options	=	$this->setProperties( array( 'url', 'installpath', 'exceptions' ), $options );
		
		// Target handler
		if (! $this->hasTarget() ) {
			$path	=	get_path( 'tmp', 'intouch' );
			
			if ( $path ) {
				$this->setTarget( $path );
			}
		}
		
		// Install path handler
		if (! $this->hasInstallpath() ) {
			$path	=	dirname( get_path( null, true ) );
			$this->setInstallpath( $path );
		}
		
		// Read / find / write any updates we have from the database
		$this->_updateRead();
		$this->_updateFind();
		$this->_updateWrite();
	}
	
	
	/**
	 * Singleton
	 * @access		public
	 * @static
	 * @version		@fileVers@
	 * @param		array		- $options: contains an array of arguments
	 *
	 * @return		object
	 * @since		2.0.0
	 */
	public static function getInstance( $options = array() )
	{
		static $instance = array();
		
		$serialize	=	serialize( $options );
		
		if (! isset( $instance[$serialize] ) ) {
			$instance[$serialize]	=	new self ( $options );
		}
		
		return $instance[$serialize];
	}
	
	
	/**
	 * Method for reading an update in
	 * @access		protected
	 * @version		@fileVers@ ( $id$ )
	 *
	 * @return		boolean
	 * @since		2.0.0
	 */
	protected function _updateRead()
	{
		$db	= dunloader( 'database', true );
		
		// Grab the store from the db
		$db->setQuery( "SELECT `value` FROM `mod_intouch_settings` WHERE `key` = 'updates'" );
		$data	= $db->loadResult();
		
		// If the store doesn't exist return false
		if( $data == null || empty( $data ) ) {
			return false;
		}
		
		// Decode the data
		$data	= json_decode( $data );
		$this->setLastrun( $data->lastrun );
		$this->setUpdate( $data->update );
		
		return true;
	}
	
	
	/**
	 * Method for getting a download URL for update
	 * @access		protected
	 * @version		@fileVers@ ( $id$ )
	 *
	 * @return		false on empty update
	 * @return		string
	 * @since		2.0.0
	 */
	protected function _updateUrl()
	{
		$config	=	dunloader( 'config', 'intouch' );
		$dlid	=	$config->get( 'dlid', null );
		
		$url	=	parent :: _updateUrl();
		$uri	=	DunUri :: getInstance( $url );
		
		if ( $dlid ) {
			$uri->setVar( 'dlid', $dlid );
		}
		
		return $uri->toString();
	}
	
	
	/**
	 * Method for writing an update out
	 * @access		protected
	 * @version		@fileVers@ ( $id$ )
	 *
	 * @return		boolean
	 * @since		2.0.0
	 */
	protected function _updateWrite()
	{
		$db		=	dunloader( 'database', true );
		
		$update	=	new stdClass();
		$update->lastrun	=	$this->getLastrun();
		$update->update		=	$this->getUpdate();
		
		$data	=	json_encode( $update );
		
		$db->setQuery( "UPDATE `mod_intouch_settings` SET `value` = " . $db->Quote( $data, false ) . " WHERE `key` = 'updates'" );
		return (bool) $db->query();
	}
}