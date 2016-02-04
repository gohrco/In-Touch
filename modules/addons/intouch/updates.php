<?php defined('DUNAMIS') OR exit('No direct script access allowed');
/**
 * @projectName@
 * In Touch - Updates Module Base File
 *
 * @package    @projectName@
 * @copyright  @copyWrite@
 * @license    @buildLicense@
 * @version    @fileVers@ ( $Id$ )
 * @author     @buildAuthor@
 * @since      2.0.8
 *
 * @desc       This file handles the updates for the product
 *
 */


/**
 * Updates Module Class for In Touch
 * @version		@fileVers@
 *
 * @author		Steven
 * @since		2.0.8
 */
class IntouchUpdatesDunModule extends IntouchAdminDunModule
{
	/**
	 * Initialise the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.0.0
	 * @see			IntouchAdminDunModule :: initialise()
	 */
	public function initialise()
	{
		$this->action = 'updates';
		parent :: initialise();
	}
	
	/**
	 * Method to execute tasks
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	public function execute()
	{
		// Check license
		if (! dunloader( 'license', 'intouch' )->isValid() ) {
			$this->setAlert( 'alert.license.invalid', 'block' );
			return;
		}
	}
	
	
	/**
	 * Method to render back the view
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing formatted output
	 * @since		2.0.0
	 */
	public function render( $data = null )
	{
		load_bootstrap( 'intouch' );
		
		$doc	=	dunloader( 'document', true );
		$doc->addStylesheet( get_baseurl( 'intouch' ) . 'assets/updates.css' );
		$doc->addScript( get_baseurl( 'intouch' ) . 'assets/updates.js' );
		$doc->addScriptDeclaration( "jQuery.ready( checkForUpdates() );" );
		
		$views		=	dunloader( 'views', 'intouch' );
		
		return parent :: render( $views->render( 'updates' ) );
	}
}