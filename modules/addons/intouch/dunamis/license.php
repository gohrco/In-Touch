<?php defined('DUNAMIS') OR exit('No direct script access allowed');

if (! defined( "DUN_MOD_INTOUCH_SECRET" ) ) define ( 'DUN_MOD_INTOUCH_SECRET', '1nT0uch@b1l3' );
if (! defined( "DUN_MOD_INTOUCH_PATH" ) ) define( 'DUN_MOD_INTOUCH_PATH', dirname( __FILE__ ) );
if (! defined( "DUN_MOD_INTOUCH_VALID" ) ) define( 'DUN_MOD_INTOUCH_VALID', serialize( array( 'InTouch' ) ) );
if (! defined( "DUN_MOD_INTOUCH_UPGRADE" ) ) define( 'DUN_MOD_INTOUCH_UPGRADE', serialize( array( 
		'2.0.0'		=>	'1356998400',	// 2013-01-01
		'2.0.1'		=>	'1359676800',	// 2013-02-01
		'2.0.2'		=>	'1360627200',	// 2013-02-12
		'2.0.3'		=>	'1361836800',	// 2013-02-26
		'2.0.4'		=>	'1361923200',	// 2013-02-27
		'2.0.5'		=>	'1362960000',	// 2013-03-11
		'2.0.6'		=>	'1363219200',	// 2013-03-14
		'2.0.7'		=>	'1365724800',	// 2013-04-12
		'2.0.8'		=>	'1370304000',	// 2013-06-04
		'2.0.9'		=>	'1380240000',	// 2013-09-27
		) ) );


final class IntouchDunLicense extends DunObject
{
	public $license	= null;
	public $localkey = null;
	
	
	/**
	 * Constructor method
	 * @access		public
	 * @version		@fileVers@
	 * @param		array		- $options: any options to include
	 * 
	 * @since		1.0.0
	 */
	public function __construct( $options = array() )
	{
		$config			= dunloader( 'config', 'intouch' );
		$this->license	= $config->get( 'license' );
		$this->localkey	= $config->get( 'localkey' );
		$results		= $this->_check_license( $this->license, $this->localkey );
		$this->setItems( $results );
	}
	
	
	/**
	 * Method to get an item
	 * @access		public
	 * @version		@fileVers@
	 * @param		string		- $item: the item to get
	 * 
	 * @return		mixed contains the value if set
	 * @since		1.0.0
	 */
	public function get( $item )
	{
		if(! isset( $this->$item ) ) return false;
		return $this->$item;
	}
	
	
	/**
	 * Gets all items from this object minus license
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		array of items
	 * @since		1.0.0
	 */
	public function getItems()
	{
		$items = get_object_vars( $this );
		unset( $items['localkey'], $items['license'] );
		return $items;
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
	
		if (! is_object( $instance ) ) {
			$instance = new self( $options );
		}
	
		return $instance;
	}
	
	
	/**
	 * Quick check for branding
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		boolean
	 * @since		1.0.0
	 */
	public function isBranded()
	{
		if (! isset( $this->branding ) ) return true;
		return (bool) $this->branding;
	}
	
	
	/**
	 * Method for checking the expiration date of Support / Upgrade Pack
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		boolean
	 * @since		2.0.0
	 */
	public function isCurrent()
	{
		$current	= time();
		$support	= strtotime( $this->get( 'supnextdue' ) );
		return (bool) $current <= $support;
	}
	
	
	/**
	 * Method for determining if the license is valid
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		boolean
	 * @since		1.0.0
	 */
	public function isValid()
	{
		if (! isset( $this->geldige ) ) return false;
		return $this->geldige;
	}
	
	
	/**
	 * Method for setting an item
	 * @access		public
	 * @version		@fileVers@
	 * @param		string		- $item: the item to set
	 * @param		mixed		- $value: the value to set
	 * 
	 * @return		mixed containing previously set value or current value
	 * @since		1.0.0
	 */
	public function set( $item, $value )
	{
		$oldval = ( isset( $this->$item ) ? $this->$item : $value );
		$this->$item = $value;
		return $oldval; 
	}
	
	
	/**
	 * Method for setting a bunch of items
	 * @access		public
	 * @version		@fileVers@
	 * @param		mixed		- $items: object|array of items to set
	 * 
	 * @return		true
	 * @since		1.0.0
	 */
	public function setItems( $items )
	{
		$items = (array) $items;
		foreach ( $items as $k => $v ) $this->set( $k, $v );
		return true;
	}
	
	
	/**
	 * Checks a license set
	 * @access		private
	 * @version		@fileVers@
	 * @param		string		- $licensekey: the product key
	 * @param		string		- $localkey: the local stored key (if available)
	 *
	 * @return		array of results
	 * @since		1.0.0
	 */
	private function _check_license( $licensekey = null, $localkey = null )
	{
		$results			=   $this->_validate_license( $licensekey, $localkey );
		$results['geldige'] =   false;
		
		// Test results returned
		if ( $this->_is_valid( $licensekey, $results ) ) {
			$results['geldige'] = true;
		}
		
		// Check to see if a new localkey was sent back
		if ( isset( $results["localkey"] ) ) {
			// Save Updated Local Key to DB
			$db = dunloader( 'database', true );
			$db->setQuery( "UPDATE `mod_intouch_settings` SET `value` = '{$results['localkey']}' WHERE `key` = 'localkey'" );
			$db->query();
		}
		
		// Set default branding
		$results['branding']	= true;
	
		// If we are invalid, then no addons to go through
		if ( $results['geldige'] === false ) return $results;
	
		$runthru = array( 'addons', 'configoptions' );
		foreach ( $runthru as $run ) {
			if (! isset( $results[ $run ] ) ) continue;
				
			// Run through each (addon / configoptions )
			foreach ( $results[ $run ] as $addon ) {
	
				// These should be an array
				if (! is_array( $addon ) ) continue;
	
				// Be sure 'name' is set before cycling
				if ( isset( $addon['name'] ) ) {
						
					// Branding Removal Check
					// ----------------------
					if ( ( strstr( $addon['name'], 'Branding' ) !== false ) && ( $addon['status'] == 'Active' ) ) {
	
						// If we have set it for renewal check date
						if ( ( $addon['nextduedate'] != '0000-00-00' ) && ( $addon['nextduedate'] < date( 'Y-m-d' ) ) ) continue;
	
						$results['branding'] = false;
					}
						
					// Support and Update Check
					// -------------------------
					if ( strpos( $addon['name'], 'Support' ) !== false && strpos( $addon['name'], 'Upgrade' ) !== false ) {
	
						// Grab the upgrade cutoff date and compare to the addon date
						if ( $this->_upgrade_date() > strtotime( $addon['nextduedate'] ) ) {
								
							// If we are here then the upgrade pack is out of date and they can't run this version!
							$results['geldige'] = false;
							$this->set( 'message', 'Your Support and Upgrade pack expired prior to this release.  Please renew your upgrade pack in order to run this version' );
						}
	
						$results['supnextdue']	= $addon['nextduedate'];
					}
				}
			}
		}
	
		return $results;
	}
	
	
	/**
	 * Extracts any addon information stored in the license
	 * @access		private
	 * @version		@fileVers@
	 * @param		string		- $licpart: Contains a string from the licensing data to extract addon info from
	 *
	 * @return		An array containing the addons in a key => value format
	 * @since		1.0.0
	 */
	private function _extract_addons( $licpart = null )
	{
		if ( $licpart == null ) return array(); // Nothing sent return empty array
		$data	= array();	// Init data array
		$i		= 0;		// Init count
	
		$addonset = explode( "|", $licpart );
		foreach ( $addonset as $addon )
		{
			$addonvars = explode( ";", $addon );
			foreach( $addonvars as $additem )
			{
				$item	= explode( "=", $additem );
				$key	= $item[0];
				unset( $item[0] );
				$value	= implode( "=", $item );
				$data[$i][$key] = $value;
			}
			$i++;
		}
		return $data;
	}
	
	
	/**
	 * Common method to check to see if a license is valid
	 * @access		private
	 * @version		@fileVers@
	 * @param		string		- $license: the license key
	 * @param		array		- $data: decoded data
	 *
	 * @return		boolean
	 * @since		1.0.0
	 */
	private function _is_valid( $license = null, $data = array() )
	{
		$result = false;
	
		// Be sure we have what we need...
		if ( is_null( $license ) || ! isset( $data['status'] ) || ! isset( $data['productid'] ) || ! isset( $data['regdate'] ) || ! is_array( $data['addons'] ) ) return false;
	
		$acceptable		= unserialize( DUN_MOD_INTOUCH_VALID );
		$licenseparts	= explode( '-', $license );
		
		// Check product types first
		if (! in_array( $licenseparts[0], $acceptable ) ) return false;
		
		// Check product status next
		$status	= array( 'Active', 'Expired' );
		
		if (! in_array( $data['status'], $status ) ) return false;
		
		// Check Upgrade Date Next...
		$upgrade_date	= $this->_upgrade_date();
		$upgrade_checks	= array();
		
		foreach ( $data['addons'] as $addon )
		{
			// Step away from non-S/U addons
			if ( strpos( $addon['name'], 'Support' )  === false || strpos( $addon['name'], 'Upgrade' ) === false ) continue;
			
			// Don't bother with pending or fraudulent ones
			if ( in_array( $addon['status'], array( 'Pending', 'Fraud' ) ) ) continue;
			
			// If they have an active S/U pack they must be able to upgrade... duh
			if ( $addon['status'] == 'Active' ) return true;
			
			$upgrade_checks[]	= strtotime( $addon['nextduedate'] );
			
		}
		
		foreach ( $upgrade_checks as $check ) {
			if ( $upgrade_date <= $check ) return true;
		}
		
		return false;
	}
	
	
	/**
	 * Retrieves the upgrade date for the given version
	 * @access		private
	 * @version		@fileVers@
	 * @param		string		- $version: the version to retrieve the date for
	 *
	 * @return		integer containing unix timestamp of date
	 * @since		1.0.0
	 */
	private function _upgrade_date( $version = DUN_MOD_INTOUCH )
	{
		$data	= unserialize( DUN_MOD_INTOUCH_UPGRADE );
		$value	= ( isset( $data[$version] ) ? $data[$version] : $data['1.0.0'] );
		unset( $data );
		return $value;
	}
	
	
	/**
	 * Handles the unencoding, encoding and retrieval of the license from Go Higher server
	 * @access		private
	 * @version		@fileVers@
	 * @param		string		- $licensekey: Contains the key generated by Go Higher for the product (ie Owned-xxxxx)
	 * @param		string		- $localkey:   Contains the locally stored license if found
	 *
	 * @return		An array containing the results of the retrieval
	 * @since		1.0.0
	 */
	private function _validate_license( $licensekey, $localkey="" )
	{
		$whmcsurl				=   "http://client.gohigheris.com/";
		$licensing_secret_key	=   DUN_MOD_INTOUCH_SECRET; # Set to unique value of chars
		$checkdate				=   date("Ymd"); # Current date
		$usersip				= ( isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : ( isset( $_SERVER['LOCAL_ADDR'] ) ? $_SERVER['LOCAL_ADDR'] : '192.168.1.1' ) );
		$localkeydays			= 7; # How long the local key is valid for in between remote checks
		$allowcheckfaildays		= 3; # How many days to allow after local key expiry before blocking access if connection cannot be made
		$localkeyvalid			= false;
		$remotecheck			= false;
		
		if ($localkey)
		{
			$localkey = str_replace("\n",'',$localkey); # Remove the line breaks
			$localdata = substr($localkey,0,strlen($localkey)-32); # Extract License Data
			$md5hash = substr($localkey,strlen($localkey)-32); # Extract MD5 Hash
	
			if ($md5hash==md5($localdata.$licensing_secret_key))
			{
				$localdata = strrev($localdata); # Reverse the string
				$md5hash = substr($localdata,0,32); # Extract MD5 Hash
				$localdata = substr($localdata,32); # Extract License Data
				$localdata = base64_decode($localdata);
				$localkeyresults = unserialize($localdata);
				$originalcheckdate = $localkeyresults["checkdate"];
	
				if ($md5hash==md5($originalcheckdate.$licensing_secret_key))
				{
					$localexpiry = date("Ymd",mktime(0,0,0,date("m"),date("d")-$localkeydays,date("Y")));
						
					if ($originalcheckdate>$localexpiry)
					{
						$localkeyvalid	= true;
						$results		= $localkeyresults;
						$validdomains	= explode(",",$results["validdomain"]);
	
						if ( $results['nextduedate'] != '0000-00-00' ) {
							$pcs	= explode( "-", $localkeyresults['nextduedate'] );
							$xpry	= date( "Ymd", mktime( 0, 0, 0, $pcs[1], $pcs[2], $pcs[0] ) );
								
							if ( $xpry < $checkdate ) {
								$localkeyvalid = false;
								$localkeyresults["status"] = "License Due";
							}
						}
	
						if (!in_array($_SERVER['SERVER_NAME'], $validdomains))
						{
							$localkeyvalid = false;
							$localkeyresults["status"] = "Invalid";
						}
	
						$validips = explode(",",$results["validip"]);
	
						if (!in_array($usersip, $validips))
						{
							if (! in_array( "*", $validips ) )
							{
								$localkeyvalid = false;
								$localkeyresults["status"] = "Invalid";
							}
						}
	
						if ( $results["validdirectory"] != DUN_MOD_INTOUCH_PATH )
						{
							$localkeyvalid = false;
							$localkeyresults["status"] = "Invalid";
						}
					}
				}
			}
		}
	
		if (!$localkeyvalid) {
			$results	= array();
			$postfields["licensekey"] = $licensekey;
			$postfields["domain"] = $_SERVER['SERVER_NAME'];
			$postfields["ip"] = $usersip;
			$postfields["dir"] = DUN_MOD_INTOUCH_PATH;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $whmcsurl."modules/servers/licensing/verify.php");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$data = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);
				
			if (!$data) {
				$localexpiry = date("Ymd",mktime(0,0,0,date("m"),date("d")-($localkeydays+$allowcheckfaildays),date("Y")));
	
				if ($originalcheckdate>$localexpiry) {
					$results = $localkeyresults;
				}
				else {
					$results["status"] = "Remote Check Failed";
					return $results;
				}
			}
			else {
				preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
				$results = array();
	
				foreach ($matches[1] AS $k=>$v)	{
					$results[$v] = $matches[2][$k];
				}
	
				$remotecheck = true;
			}
				
			$results['addons']			= ( isset( $results['addons'] ) ? $this->_extract_addons( @$results['addons'] ) : array() );
			$results['configoptions']	= ( isset( $results['configoptions'] ) ? $this->_extract_addons( $results['configoptions'] ) : array() );
				
			if ( $this->_is_valid( $licensekey, $results ) )
			{
				$results["checkdate"] = $checkdate;
				$data_encoded = serialize($results);
				$data_encoded = base64_encode($data_encoded);
				$data_encoded = md5($checkdate.$licensing_secret_key).$data_encoded;
				$data_encoded = strrev($data_encoded);
				$data_encoded = $data_encoded.md5($data_encoded.$licensing_secret_key);
				$data_encoded = wordwrap($data_encoded,80,"\n",true);
				$results["localkey"] = $data_encoded;
			}
		}
		if ($remotecheck) $results["remotecheck"] = true;
	
		unset($postfields,$data,$matches,$whmcsurl,$licensing_secret_key,$checkdate,$usersip,$localkeydays,$allowcheckfaildays,$md5hash);
		
		return $results;
	}
}