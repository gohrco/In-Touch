<?php defined('DUNAMIS') OR exit('No direct script access allowed');


class IntouchDunUpdates extends DunObject
{
	public	$lastrun		=	null;
	public	$updates		=	array();
	
	private $_cacert		=	null;
	private $_error			=	false;
	private $_updatesite	=	'https://www.gohigheris.com/updates/whmcsmodules/intouch';
	private	$_version		=	'@fileVers@';
	
	/**
	 * Constructor method
	 * @access		public
	 * @version		@fileVers@
	 * @param		array		- $options: can pass options on
	 * 
	 * @since		2.0.0
	 */
	public function __construct( $options = array() )
	{
		$force		= isset( $options['force'] ) ? (int) $options['force'] : false;
		$docheck	= isset( $options['check'] ) ? (int) $options['check'] : true;
		
		// Initialize
		$this->init_object();
		
		// CLEAR if requested to (force == true)
		if ( $force ) $this->_wipe_store();
		
		// If we don't want to do the check then return
		if (! $docheck ) return;
		
		// STEP 2:  Read the store
		$this->read_store();
		
		// STEP 3:  Find Updates online
		$this->find_updates();
		
		// STEP 4:  Compare to current
		$this->compare_versions();
		
		// STEP 5:  write to store
		$this->write_store();
	}
	
	
	/**
	 * Method to compare versions of existing cnxns to most updated type
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		3.0.1 (0.1)
	 */
	public function compare_versions()
	{
		$updates	= $this->updates;
		
		if ( empty( $updates ) ) return;
		
		if ( version_compare( $updates->version, $this->_version, 'le' ) ) {
			$this->updates	= array();
		}
	}
	
	
	/**
	 * Finds the updates from Go Higher
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		3.0.1 (0.1)
	 */
	public function find_updates()
	{
		// If we dont need to run this then bypass
		if ( $this->_check_last_run() ) return;
	
		// Extract the download URLs
		$result		= $this->_get_updates();
		$version	= null;
		$latest		= null;
		
		// If we dont have anything bail
		if (! $result ) return;
		
		if ( isset( $result['update'] ) ) foreach ( $result['update'] as $res ) {
			if ( version_compare( $res['version'], $latest, 'g' ) ) {
				$latest		= $res['version'];
				$version	= $res;
			}
		}
		
		$this->lastrun	= date( "Y-m-d" );
		$this->updates	= (object) $version;
	}
	
	
	/**
	 * Singleton
	 * @access		public
	 * @static
	 * @version		@fileVers@
	 * @param		array		- $options: contains an array of arguments
	 *
	 * @return		object
	 * @since		1.0.0
	 */
	public static function getInstance( $options = array() )
	{
		static $instance = null;
		
		$force		= isset( $options['force'] ) ? (int) $options['force'] : false;
		
		if (! is_object( $instance ) || $force ) {
			$instance = new self( $options );
		}
	
		return $instance;
	}
	
	
	public function hasError()
	{
		if ( empty( $this->_error ) ) return false;
		else return $this->_error;
	}
	
	
	/**
	 * Build and populate the object
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	public function init_object()
	{
		// Set the CA Certificate path
		$this->_cacert	=	DUN_ENV_PATH . 'includes' . DIRECTORY_SEPARATOR . 'whmcs' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'cacert.pem';
	}
	
	
	/**
	 * Method to read the stored data from the file
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		array of data or false on error
	 * @since		2.0.0
	 */
	public function read_store()
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
		
		// If we have the last run variable
		if ( isset( $data->lastrun ) ) {
			// If the last run wasn't today then delete file and return false
			if (! $this->_check_last_run( $data->lastrun ) ) {
				$this->_wipe_store();
				return false;
			}
		}
		
		// Grab the store and stick it here
		if ( is_object( $data ) ) foreach ( $data as $key => $value ) {
			$this->$key = $value;
		}
		
		return $data;
	}
	
	
	public function updatesExist()
	{
		if ( $this->_error ) return false;
		elseif ( empty( $this->updates ) ) return false;
		else return $this->updates->version;
	}
	
	
	/**
	 * Method to write the data to the file store
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		boolean
	 * @since		3.0.1 (0.1)
	 */
	public function write_store()
	{
		$db		=	dunloader( 'database', true );
		
		if ( $this->_error ) {
			$data	= '{}';
		}
		else {
			$data	=	json_encode( $this );
		}
		
		$db->setQuery( "UPDATE `mod_intouch_settings` SET `value` = " . $db->Quote( $data ) . " WHERE `key` = 'updates'" );
		return (bool) $db->query();
		
	}
	
	
	/**
	 * Common method to check the last time we ran this
	 * @access		private
	 * @version		@fileVers@
	 * @param		string		- $lastrun: contains a date in format Y-m-d or null (defaults to $this->lastrun if null)
	 *
	 * @return		boolean
	 * @since		2.0.0
	 */
	private function _check_last_run( $lastrun = null )
	{
		if ( $lastrun == null ) $lastrun = $this->lastrun;
		return $lastrun == date( "Y-m-d" );
	}
	
	
	/**
	 * Method to retrieve the updates given a url
	 * @access		private
	 * @version		@fileVers@
	 * @param		string		- $url:  the url to retrieve the updates from
	 *
	 * @return		array of updates from site
	 * @since		3.0.1 (0.1)
	 */
	private function _get_updates( $url = null )
	{
		$url		=	$url == null ? $this->_updatesite : $url;
		$curl		=	dunloader( 'curl', false );
		
		$options	= array(	'CAINFO'			=> $this->_cacert,
								'HEADER'			=> false,
								'RETURNTRANSFER'	=> true
		);
		
		$response	= $curl->simple_post( $url, array(), $options );
		
		if ( ( $error = $curl->has_errors() ) ) {
			$this->_error= $error;
			return false;
		}
		
		$xml		= simplexml_load_string( $response );
		
		if ( $xml === false ) return array();
		
		$data		= simpleXMLToArray( $xml );
		
		return $data;
	}
	
	
	/**
	 * Method to clear the stored updates out
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		boolean
	 * @since		2.0.0
	 */
	private function _wipe_store()
	{
		$db	= dunloader( 'database', true );
		$db->setQuery( "UPDATE `mod_intouch_settings` SET `value` = '' WHERE `key` = 'updates'" );
		return (bool) $db->query();
	}
}