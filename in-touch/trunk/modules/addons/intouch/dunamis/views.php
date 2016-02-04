<?php defined('DUNAMIS') OR exit('No direct script access allowed');
/**
 * @projectName@ - View Handler
 *
 * @package    @projectName@
 * @copyright  @copyWrite@
 * @license    @buildLicense@
 * @version    @fileVers@ ( $Id$ )
 * @author     @buildAuthor@
 * @since      2.2.4
 *
 * @desc       This file handles requests to the WHMCS API (localAPI calls)
 *
 */


/**
 * View Handler Class for In Touch
 * @version		@fileVers@
 *
 * @author		Steven
 * @since		2.2.4
 */
class IntouchDunViews extends DunObject
{
	
	/**
	 * Stores data we want to render within the view file
	 * @var array
	 */
	public $_data		=	array();
	
	/**
	 * Stores where our view directory is actually located at
	 * @var string
	 */
	public $_viewdir	=	null;
	
	/**
	 * Constructor method
	 * @access		public
	 * @version		@fileVers@
	 * @param		array		- $options: options to set
	 *
	 * @since		2.2.4
	 */
	public function __construct( $options = array() )
	{
		parent :: __construct( $options );
		
		$this->setViewdir( dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR );
	}
	
	
	/**
	 * Singleton object
	 * @access		public
	 * @version		@fileVers@
	 * @param		array		- $options: array of options
	 *
	 * @return		JwhmcsDunView object
	 * @since		2.2.4
	 */
	public static function getInstance( $options = array() )
	{
		static $instance = null;
	
		if (! is_object( $instance ) ) {
			$instance = new self( (object) $options );
		}
	
		return $instance;
	}
	
	
	/**
	 * Renders a view back
	 * @access		public
	 * @version		@fileVers@
	 * @param		string			- $view: the view we want to render back
	 *
	 * @return		string
	 * @since		2.2.4
	 */
	public function render( $view = null )
	{
		if ( $view == null ) return null;
		
		$data	=	(object) $this->getData();
		
		// Determine if our file exists first
		$path	=	$this->getViewdir() . $view . '.php';
		if (! file_exists( $path ) ) return null;
		
		ob_start();
		include $path;
		$contents	=	ob_get_contents();
		ob_end_clean();
		
		return $contents;
	}
}