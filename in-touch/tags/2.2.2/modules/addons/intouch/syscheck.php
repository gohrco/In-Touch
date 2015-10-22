<?php defined('DUNAMIS') OR exit('No direct script access allowed');
/**
 * @projectName@ - System Check Module Base File
 *
 * @package    @projectName@
 * @copyright  @copyWrite@
 * @license    @buildLicense@
 * @version    @fileVers@ ( $Id$ )
 * @author     @buildAuthor@
 * @since      3.1.00
 *
 * @desc       This file handles system check for the product
 *
 */


/**
 * System Check Module Class for Integrator 3
 * @version		@fileVers@
 *
 * @author		Steven
 * @since		3.1.00
 */
class IntouchSyscheckDunModule extends IntouchAdminDunModule
{
	/**
	 * Provide means to check for file integrity
	 * @access		protected
	 * @var			string
	 * @since		3.1.00
	 */
	protected $checkstring	=	"@checkString@";
	
	
	/**
	 * Initialise the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		3.1.00
	 * @see			IntegratorAdminDunModule :: initialise()
	 */
	public function initialise()
	{
		$this->action = 'syscheck';
		parent :: initialise();
	}
	
	/**
	 * Method to execute tasks
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @since		3.1.00
	 */
	public function execute() { }
	
	
	/**
	 * Method to render back the view
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing formatted output
	 * @since		3.1.00
	 */
	public function render( $data = null )
	{
		load_bootstrap( 'intouch' );
		$doc	=	dunloader( 'document', true );
		$doc->addScript( get_baseurl( 'intouch' ) . 'assets/js/syscheck.js' );
		$doc->addScriptDeclaration( 'var ajaxurl = "' . get_baseurl( 'intouch' ) . '";' );
		
		$model	=	$this->getModel();
		$data	=	array();
		
		// Let's build our table
		foreach ( array( 'files', 'whmcs', 'env' ) as $row ) {
			
			$data[]	=	'<table class="table-bordered table-striped">';
			$data[]	=	'	<thead><tr><th colspan="3">' . t( 'intouch.syscheck.tblhdr.' . $row ) . '</th></tr></thead>';
			$data[]	=	'	<tbody>';
			
			if ( $row == 'files' ) {
				$current	=	true;
				foreach ( $model->files as $item ) if (! $item->current ) $current = false;
				
				if (! $current ) {
					$button	=	'<button id="btnfixall" class="btn btn-danger pull-right">' . t( 'intouch.syscheck.general.fixall' ) . '</button>';
					$data[]	=	'<tr><td colspan="2" style="text-align: right; ">'.$button.'</td><td></td></tr>';
				}
			}
			
			foreach ( $model->$row as $item => $value ) {
				// Skip some
				if ( in_array( $item, array( 'files', 'supported', 'templatesupported' ) ) ) continue;
				
				$data[]	=	'	<tr>';
				$data[]	=	'		<td class="span3">' . $this->_getLabel( $item, $model->$row, $row ) . '</td>';
				$data[]	=	'		<td class="span2">' . $this->_getValue( $item, $model->$row, $row ) . '</td>';
				$data[]	=	'		<td class="span7">' . $this->_getHelp( $item, $model->$row, $row ) . '</td>';
				$data[]	=	'	</tr>';
			}
			
			$data[]	=	'</tbody></table><br/><br/>';
		}
		
		return parent :: render( implode( "\r\n", $data ) );
	}
	
	
	public function getItem( $what, $item, $items, $row = 'whmcs' )
	{
		$method	=	'_get' . ucfirst( $what );
		return $this->$method( $item, $items, $row ); 
	}
	
	
	/**
	 * Method to get the model data
	 * @access		public
	 * @version		@fileVers@ ( $id$ )
	 *
	 * @return		object
	 * @since		3.1.00
	 */
	public function getModel()
	{
		// Gather the WHMCS info first
		$wconfig	=	dunloader( 'config', true );
		$tmpl		=	$wconfig->get( 'Template' );
		
		if ( in_array( $tmpl, array( 'portal', 'default','classic' ) ) ) {
			$tsup	=	1;
		}
		else {
			$path	=	get_path( 'templates', 'intouch' ) . get_intouch_version() . DIRECTORY_SEPARATOR;
			
			if ( is_dir( $path . $tmpl ) ) {
				$tsup	=	2;
			}
			else {
				$tsup	=	0;
			}
		}
		
		$whmcs		=	(object) array(
				'version'			=>	DUN_ENV_VERSION,
				'supported'			=>	is_supported_byintouch(),
				'template'			=>	$tmpl,
				'templatesupported'	=>	$tsup,
				);
		
		// Gather the Environmental variables
		$env		=	(object) array(
				'iconv'		=>	function_exists( 'iconv' ),
				'mbdetect'	=>	function_exists( 'mb_detect_encoding' ),
				'phpvers'	=>	phpversion(),
				);
		
		// API Information variables
		$config		=	dunloader( 'config', 'intouch' );
		
		// File Checking Information variables
		$install	=	dunmodule( 'intouch.install' );
		$files		=	(object) $install->checkFiles();
		
		return (object) array(
				'whmcs'	=>	$whmcs,
				'env'	=>	$env,
				'files'	=>	$files
		);
	}
	
	
	/**
	 * Method to assemble and create a help text
	 * @access		private
	 * @version		@fileVers@ ( $id$ )
	 * @param		string		- $item: the name of the item to create / assemble
	 * @param		object		- $items: the set of items being sent for this group
	 *
	 * @return		string
	 * @since		3.1.00
	 */
	private function _getHelp( $item, $items, $row = 'whmcs' )
	{
		$data	=	null;
		
		if ( $row == 'files' ) {
			
			if (! $items->$item->current ) {
				$id		=	'help' . preg_replace( '#[\\\\/\.]*#', '', $item );
				$data	=	'<span id="' . $id . '" class="text-error"><strong>' . t( 'intouch.syscheck.general.attention' ) . '</strong>'
						.	$items->$item->error . '</span>';
			}
			
			return $data;
		}
		
		switch( $item ) :
		case 'version' :
			
			if ( $items->supported ) {
				$data	=	'<span class="label label-success">' . t( 'intouch.syscheck.general.supported.yes' ) . '</span>';
			}
			else {
				$data	=	'<p class="alert alert-warning"><strong>' . t( 'intouch.syscheck.general.supported.no' ) . '</strong>'
						.	t( 'intouch.syscheck.version.help' ) . '</p>';
			}
			
		break;
		
		case 'version' :
				
			if ( version_compare( phpversion(), '5.2', 'ge' ) ) {
				$data	=	'<span class="label label-success">' . t( 'intouch.syscheck.general.supported.yes' ) . '</span>';
			}
			else {
				$data	=	'<p class="alert alert-warning"><strong>' . t( 'intouch.syscheck.general.supported.no' ) . '</strong>'
						.	t( 'intouch.syscheck.phpvers.help' ) . '</p>';
			}

			break;
			
		case 'template' :
			
			if ( $items->templatesupported == 1 ) {
				$data	=	'<span class="label label-success">' . t( 'intouch.syscheck.general.supported.yes' ) . '</span>';
			}
			else if ( $items->templatesupported == 0 ) {
				$data	=	'<p class="alert alert-danger"><strong>' . t( 'intouch.syscheck.general.supported.no' ) . '</strong>'
						.	t( 'intouch.syscheck.template.help' ) . '</p>';
			}
			else {
				$data	=	'<p class="alert alert-info">' . t( 'intouch.syscheck.template.info' ) . '</p>';
			}
			
			break;
		
		case 'apifound' :
		case 'tokenauth' :
			
			if ( $items->$item !== true ) {
				$data	=	t( 'intouch.syscheck.' . $item . '.help', $items->$item );
			}
			
			break;
			
		case 'urlproper' :
		case 'curl' :
		case 'iconv' :
		case 'mbdetect' :
		case 'apiurl' :
		case 'token' :
		
			if (! $items->$item ) {
				$data	=	'<p class="alert alert-danger"><strong>' . t( 'intouch.syscheck.general.attention' ) . '</strong>'
						.	t( 'intouch.syscheck.' . $item . '.help' ) . '</p>';
			}
			
			break;
		case 'sslenabled' :
			
			if ( $items->sslenabled === false ) {
				$data	=	'<p class="alert alert-danger"><strong>' . t( 'intouch.syscheck.general.attention' ) . '</strong>'
						.	t( 'intouch.syscheck.' . $item . '.help' ) . '</p>';
			}
			
			break;
		endswitch;
		
		return $data;
	}
	
	
	/**
	 * Method for getting the label
	 * @access		private
	 * @version		@fileVers@ ( $id$ )
	 * @param		string		- $item: the individual item we are getting
	 * @param		object		- $items: contains values for current subset
	 * @param		string		- $row: indicates which subset we are on
	 *
	 * @return		string
	 * @since		3.1.00
	 */
	private function _getLabel( $item, $items, $row = 'whmcs' )
	{
		$icon	=	null;
		$g		=	'ok';
		$b		=	'remove';
		
		switch ( $item ) :
		case 'version' :
			
			$icon	=	$items->supported ? $g : $b;
			break;
			
		break;
		
		case 'template' :

			$icon	=	in_array( $items->templatesupported, array( 1, 2 ) ) ? $g : $b;	
			break;
		
		case 'phpvers' :
			
			$icon	=	version_compare( phpversion(), '5.2', 'ge' ) ? $g : $b;
			
			break;
		
		case 'urlproper' :
		case 'curl' :
		case 'iconv' :
		case 'mbdetect' :
		case 'apiurl' :
		case 'token' :
		case 'apifound' :
		case 'tokenauth' :
		
			$icon	=	$items->$item === true ? $g : $b;
				
			break;
		case 'sslenabled' :
			$icon	=	in_array( $items->$item, array( true, null ) )? $g : $b;
			break;
		default :
			
			if ( $row == 'files' ) {
				$icon	=	$items->$item->current ? $g : $b;
			}
			
		endswitch;
		
		if ( $icon ) {
			$id		=	'icon' . preg_replace( '#[\\\\/\.]+#', '', $item );
			$icon	=	'<i id="' . $id . '" class="icon icon-' . $icon . ' pull-right"></i> ';
		}
		
		if ( $row == 'files' ) {
			return $icon . $item;
		}
		
		return t( 'intouch.syscheck.tbldata.' . $row . '.' . $item, $icon );
	}
	
	
	/**
	 * Method to assemble and create a value
	 * @access		private
	 * @version		@fileVers@ ( $id$ )
	 * @param		string		- $item: the name of the item to create / assemble
	 * @param		object		- $items: the set of items being sent for this group
	 *
	 * @return		string
	 * @since		3.1.00
	 */
	private function _getValue( $item, $items, $row = 'whmcs' )
	{
		$data	=	null;
		
		switch( $item ) :
		case 'version' :
		case 'phpvers' :
			
			$data	=	'<strong>' . $items->$item . '</strong>';
			break;
		
		case 'template' :
			
			$data	=	'<strong>' . ucfirst( $items->$item ) . '</strong>';
			break;
			
		case 'urlproper' :
		case 'curl' :
		case 'iconv' :
		case 'mbdetect' :
		case 'apiurl' :
		case 'token' :
		case 'apifound' :
		case 'tokenauth' :
			
			$data	=	'<span class="badge badge-' . ( $items->$item === true ? 'inverse' : 'important' ) . '">'
					.	t( 'intouch.syscheck.general.yesno.' . ( $items->$item === true ? 'yes' : 'no' ) )
					.	'</span> ';
			
			break;
		
		case 'sslenabled' :
			
			$data	=	'<span class="badge badge-' . ( in_array( $items->sslenabled, array( true, null ) ) ? 'inverse' : 'important' ) . '">'
					.	t( 'intouch.syscheck.general.yesno.' . ( $items->sslenabled === true ? 'yes' : 'no' ) )
					.	'</span> ';
			
			break;
		default :
			
			if ( $row == 'files' ) {
				$id		=	preg_replace( '#[\\\\/\.]+#', '', $item );
				$jsid	=	preg_replace( '#[\\\\]+#', '_', $item );
				
				$data	=	'<span id="badge' . $id . '" class="badge badge-' . ( $items->$item->current ? 'inverse' : 'important' ) . '">'
						.	t( 'intouch.syscheck.general.yesno.' . ( $items->$item->current ? 'yes' : 'no' ) )
						.	'</span> '
						.	(! $items->$item->current && ( $items->$item->code == 4 || $items->$item->code == 2 )
								? '<button id="btn' . $id . '" class="fixfile btn btn-danger btn-mini pull-right" data-filename="' . $item . '" data-refid="' . $id . '">' . t( 'intouch.syscheck.general.fixit' ) . '</button>'
								: '' );
			}
			
		endswitch;
		
		return $data;
	}
}