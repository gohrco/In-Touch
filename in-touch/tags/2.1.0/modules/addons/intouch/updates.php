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
	public function render()
	{
		load_bootstrap( 'intouch' );
		
		$data	= $this->buildBody();
		
		return parent :: render( $data );
	}
	
	
	/**
	 * Builds the body of the action
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing html formatted output
	 * @since		2.0.0
	 */
	public function buildBody()
	{
		$doc	=	dunloader( 'document', true );
		$doc->addStylesheet( get_baseurl( 'intouch' ) . 'assets/updates.css' );
		$doc->addScript( get_baseurl( 'intouch' ) . 'assets/updates.js' );
		$doc->addScriptDeclaration( "jQuery.ready( checkForUpdates() );" );
		
		$data	=	array();
		$data[]	=	'<div class="span8" style="text-align: center; ">';
		$data[]	=	'<a class="btn" id="btn-updates">';
		$data[]	=	'<div class="ajaxupdate ajaxupdate-init">';
		$data[]	=	'<span id="upd-title"></span>';
		$data[]	=	'<img id="img-updates" class="" />';
		$data[]	=	'<span id="upd-subtitle"></span>';
		$data[]	=	'</div>';
		$data[]	=	'</a>';
		$data[]	=	'</div>';
		$data[]	=	'<input type="hidden" id="btntitle" value="' . t( 'intouch.updates.checking.title' ) . '" />';
		$data[]	=	'<input type="hidden" id="btnsubtitle" value="' . t( 'intouch.updates.checking.subtitle' ) . '" />';
		$data[]	=	'<input type="hidden" id="intouchurl" value="' . get_baseurl( 'intouch' ) . '" />';
		
		return implode( "\r\n", $data );
	}
}