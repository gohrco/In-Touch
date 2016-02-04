<?php defined('DUNAMIS') OR exit('No direct script access allowed');
/**
 * @projectName@ - System Check Module Base File
 *
 * @package    @projectName@
 * @copyright  @copyWrite@
 * @license    @buildLicense@
 * @version    @fileVers@ ( $Id$ )
 * @author     @buildAuthor@
 * @since      2.2.4
 *
 * @desc       This file handles system check for the product
 *
 */


/**
 * System Check Module Class for Integrator 3
 * @version		@fileVers@
 *
 * @author		Steven
 * @since		2.2.4
 */
class IntouchSyscheckDunModule extends IntouchAdminDunModule
{
	/**
	 * Provide means to check for file integrity
	 * @access		protected
	 * @var			string
	 * @since		2.2.4
	 */
	protected $checkstring	=	"@checkString@";
	
	
	/**
	 * Initialise the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.2.4
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
	 * @since		2.2.4
	 */
	public function execute() { }
	
	
	/**
	 * Method to render back the view
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		string containing formatted output
	 * @since		2.2.4
	 */
	public function render( $data = null )
	{
		load_bootstrap( 'intouch' );
		$doc	=	dunloader( 'document', true );
		$doc->addScript( get_baseurl( 'intouch' ) . 'assets/js/syscheck.js' );
		$doc->addScriptDeclaration( 'var ajaxurl = "' . get_baseurl( 'intouch' ) . '";' );
		
		$views	=	dunloader( 'views', 'intouch' );
		$views->setData( $this->getModel() );
		
		return parent :: render( $views->render( 'syscheck' ) );
		
		
		
		
		
		
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
	 * @since		2.2.4
	 */
	public function getModel()
	{
		
		// Gather the WHMCS info first
		$wconfig	=	dunloader( 'config', true );
		$tmpl		=	$wconfig->get( 'Template' );
		$sysurl		=	$wconfig->get( 'SystemURL' );
		$syssslurl	=	$wconfig->get( 'SystemSSLURL' );
		
		$suri		=	DunUri :: getInstance( $sysurl, true );
		$ssuri		=	DunUri :: getInstance( $syssslurl, true );
		
		$usesssl	=	( $suri->isSSL() || ( ! empty( $syssslurl ) && is_object( $ssuri ) && $ssuri->isSSL() ) ) ? true : false;
		if (! $usesssl && empty( $syssslurl ) ) $usesssl = null;
		$urlmixed	=	(! $ssuri->getHost() || $suri->getHost() == $ssuri->getHost() ) ? true : false;
		
		if ( in_array( $tmpl, array( 'portal', 'default','classic' ) ) ) {
			$tsup	=	1;
		}
		else {
			$path	=	get_path( 'templates', 'intouch' ) . get_version() . DIRECTORY_SEPARATOR;
				
			if ( is_dir( $path . $tmpl ) ) {
				$tsup	=	2;
			}
			else {
				$tsup	=	0;
			}
		}
		
		// File Checking Information variables
		$config		=	dunloader( 'config', 'intouch' );
		$install	=	dunmodule( 'intouch.install' );
		$ourfiles	=	(object) $install->checkFiles();
		
		// Build our objects next
		// ----------------------
		// WHMCS Info first
		$whmcs				=	new stdClass;
		$whmcs->version		=	new IntouchDispClass( 'version', is_supported_byintouch(), DUN_ENV_VERSION );
		$whmcs->template	=	new IntouchDispClass( 'template', $tsup, $tmpl );
		$whmcs->urlproper	=	new IntouchDispClass( 'urlproper', $urlmixed );
		
		// Gather the Environmental variables
		$env				=	new stdClass;
		$env->curl			=	new IntouchDispClass( 'curl', function_exists( 'curl_exec' ) );
		$env->iconv			=	new IntouchDispClass( 'iconv', function_exists( 'iconv' ) );
		$env->mbdetect		=	new IntouchDispClass( 'mbdetect', function_exists( 'mb_detect_encoding' ) );
		$env->phpvers		=	new IntouchDispClass( 'phpvers', version_compare( phpversion(), '5.2', 'ge' ), phpversion() );
		
		// Files variables
		$files				=	new stdClass;
		
		foreach ( $ourfiles as $filename => $fileprops ) {
			$files->$filename	=	new IntouchDispClass( $filename, $fileprops->current, $fileprops->error, true, $fileprops->code );
		}
		
		return (object) array(
				'files'	=>	$files,
				'whmcs'	=>	$whmcs,
				'env'	=>	$env,
		);
		
		
		
		
		
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
	 * @since		2.2.4
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
	 * @since		2.2.4
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
	 * @since		2.2.4
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


/**
 * Display class for our system check objects
 * @version		@fileVers@
 *
 * @author		Steven
 * @since		2.6.07
 */
class IntouchDispClass extends stdClass
{
	public $help	=	array();
	public $id		=	null;
	public $title	=	null;
	public $value	=	array();

	private $_file		=	false;
	private $_filecode	=	0;
	private $_name		=	null;
	private $_text		=	false;
	private $_value		=	null;


	/**
	 * Constructor method
	 * @access		public
	 * @version		@fileVers@
	 * @param		string		: contains the name of our object
	 * @param		mixed		: contains the value of our object
	 * @param		string		: contains the text we want displayed if any
	 * @param		boolean		: indicates this is for a file
	 * @param		integer		: the file code determined by our file checker
	 *
	 * @since		2.6.07
	 */
	public function __construct( $name = null, $value = null, $text = null, $file = false, $filecode = 0 )
	{
		$this->_file		=	$file;
		$this->_filecode	=	$filecode;
		$this->_name		=	$name;
		$this->_value		=	$value;
		$this->_text		=	$text;

		$this->setTitle();
		$this->setId();
		$this->setValue();
		$this->setHelp();
	}


	/**
	 * Method to get the file code of the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		integer
	 * @since		2.6.07
	 */
	public function getFilecode()
	{
		return $this->_filecode;
	}


	/**
	 * Method to get the help value of the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		object
	 * @since		2.6.07
	 */
	public function getHelp()
	{
		return $this->help;
	}


	/**
	 * Method to get the id of the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		string
	 * @since		2.6.07
	 */
	public function getId()
	{
		return $this->id;
	}


	/**
	 * Method to get the name of the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		string
	 * @since		2.6.07
	 */
	public function getName()
	{
		return $this->_name;
	}


	/**
	 * Method to get the title of the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		string
	 * @since		2.6.07
	 */
	public function getTitle()
	{
		return $this->title;
	}


	/**
	 * Method to get the determined value of the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		object
	 * @since		2.6.07
	 */
	public function getValue()
	{
		return $this->value;
	}


	/**
	 * Method to get the raw value
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		mixed
	 * @since		2.6.07
	 */
	public function getValueraw()
	{
		return $this->_value;
	}


	/**
	 * Method to determine if this is a file object or not
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @return		boolean
	 * @since		2.6.07
	 */
	public function isFile()
	{
		return $this->_file ? true : false;
	}


	/**
	 * Method to set the help of the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.6.07
	 */
	public function setHelp()
	{
		$this->help	=	(object) array(
				'text'	=>	$this->_getHelp(),
				'type'	=>	$this->_getHelptype(),
		);
	}


	/**
	 * Method to set the id of the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.6.07
	 */
	public function setId()
	{
		$help	=	( $this->_file ? 'help' : 'id' );
		$id		=	preg_replace( '#[\\\\/\.]*#', '', strtolower( $this->_name ) );

		$this->id	=	$help . $id . rand( 100, 999 );
	}


	/**
	 * Method to set the title of the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.6.07
	 */
	public function setTitle()
	{
		switch ( $this->_name ) :
		case 'sslenabled'	:
		case 'template'		:
		case 'templatesupported' :
		case 'urlproper'	:
		case 'version'		:	$title	=	t( 'intouch.syscheck.tbldata.whmcs.' . $this->_name );	break;
		case 'curl'			:
		case 'iconv'		:
		case 'mbdetect'		:
		case 'phpvers'		:	$title	=	t( 'intouch.syscheck.tbldata.env.' . $this->_name );		break;
		case 'apiurl'		:
		case 'apifound'		:
		case 'token'		:
		case 'tokenauth'	:	$title	=	t( 'intouch.syscheck.tbldata.api.' . $this->_name );		break;
		default : // Assume files
			$title	=	$this->_name;
			endswitch;

			$this->title	=	$title;
	}


	/**
	 * Method to set the value of the object
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.6.07
	 */
	public function setValue()
	{
		$this->value	=	(object) array(
				'text'	=>	$this->_getValue(),
				'type'	=>	$this->_getValuetype(),
		);
	}


	/**
	 * Method to get the help based on our object
	 * @access		private
	 * @version		@fileVers@
	 *
	 * @return		string
	 * @since		2.6.07
	 */
	private function _getHelp()
	{
		switch ( $this->_name ) :
		case 'template'		:	return t( 'intouch.syscheck.' . ( $this->_value == 1 ? 'general.supported.yes' : ( $this->_value == 0 ? 'genera.supported.no' : 'template.info' ) ) );
		case 'templatesupported'		:	return t( 'intouch.syscheck.' . ( $this->_value == 1 ? 'general.supported.yes' : ( $this->_value == 0 ? 'genera.supported.no' : 'templatesupported.info' ) ) );
		case 'version'		:	return t( 'intouch.syscheck.general.supported.' . ( $this->_value === true ? 'yes' : 'no' ) );
		case 'apifound'		:
		case 'tokenauth'	:	return ( $this->_value !== true ? t( 'intouch.syscheck.' . $this->_name . '.help', $this->_value ) : null );
		case 'urlproper'	:
		case 'curl'			:
		case 'iconv'		:
		case 'mbdetect'		:
		case 'apiurl'		:
		case 'sslenabled'	:
		case 'templatesupported' :
		case 'token'		:	return (! $this->_value ? t( 'intouch.syscheck.' . $this->_name . '.help' ) : null );
		endswitch;

		if ( $this->_file && ! $this->_value ) {
			return $this->value->text;
		}
	}


	/**
	 * Method to get the help type based on our object
	 * @access		private
	 * @version		@fileVers@
	 *
	 * @return		string
	 * @since		2.6.07
	 */
	private function _getHelptype()
	{
		switch( $this->_name ) :
		case 'template'		:	return ( $this->_value == 1 ? 'label-success' : ( $this->_value == 0 ? 'alert-danger' : 'alert-info' ) );
		case 'templatesupported' :	return ( $this->_value == 1 ? 'label-success' : ( $this->_value == 0 ? 'alert-danger' : 'alert-info' ) );
		case 'version'		:	return ( $this->_value === true ? 'label-success' : 'alert-warning' );
		case 'urlproper'	:
		case 'curl'			:
		case 'iconv'		:
		case 'mbdetect'		:
		case 'apiurl'		:
		case 'templatesupported' :
		case 'sslenabled'	:
		case 'token'		:	return (! $this->_value ? 'attention' : null );
		endswitch;

		if ( $this->_file && ! $this->_value ) {
			return 'text-error';
		}
	}


	/**
	 * Method to get the value based on our object
	 * @access		private
	 * @version		@fileVers@
	 *
	 * @return		string
	 * @since		2.6.07
	 */
	private function _getValue()
	{
		switch ( $this->_name ) :
		case 'template' :
		case 'templatesupported' :
			return ucfirst( $this->_text );
		case 'urlproper' :
		case 'curl' :
		case 'iconv' :
		case 'mbdetect' :
		case 'apiurl' :
		case 'token' :
		case 'apifound' :
		case 'tokenauth' :
			return t( 'intouch.syscheck.general.yesno.' . ( $this->_value === true ? 'yes' : 'no' ) );
		case 'sslenabled' :
			return t( 'intouch.syscheck.general.yesno.' . ( in_array( $this->_value, array( true, null ) ) ? 'yes' : 'no' ) );
		case 'phpvers' :
		default :
			return $this->_text;
			endswitch;
	}


	/**
	 * Method to get the type of value we are using
	 * @access		private
	 * @version		@fileVers@
	 *
	 * @return		string
	 * @since		2.6.07
	 */
	private function _getValuetype()
	{
		$type	=	'text';

		switch ( $this->_name ) :
		case 'urlproper' :
		case 'curl' :
		case 'iconv' :
		case 'mbdetect' :
		case 'apiurl' :
		case 'token' :
		case 'apifound' :
		case 'tokenauth' :
			$type	=	'badge';
			break;
		case 'sslenabled' :
			$type	=	'sslbadge';
			break;
		endswitch;

			if ( $this->file ) $type	=	'badge';

			return $type;
	}
}