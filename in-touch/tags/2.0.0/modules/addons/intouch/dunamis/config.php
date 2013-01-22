<?php defined('DUNAMIS') OR exit('No direct script access allowed');


class IntouchDunConfig extends WhmcsDunConfig
{
	/**
	 * Constructor method
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	public function __construct()
	{
		$this->load();
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
		static $instance = null;
		
		if (! is_object( $instance ) ) {
			
			$instance = new IntouchDunConfig( $options );
		}
	
		return $instance;
	}
	
	
	/**
	 * Loader method
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	public function load()
	{
		$db = dunloader( 'database', true );
		$db->setQuery( "SELECT * FROM mod_intouch_settings" );
		$items	= $db->loadObjectList();
		
		foreach ( $items as $item ) $this->set( $item->key, $item->value );
	}
}