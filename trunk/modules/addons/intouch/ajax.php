<?php
/**
 * @projectName@
 * In Touch - Ajax Module Base File
 *
 * @package    @projectName@
 * @copyright  @copyWrite@
 * @license    @buildLicense@
 * @version    @fileVers@ ( $Id$ )
 * @author     @buildAuthor@
 * @since      2.0.8
 *
 * @desc       This file is the ajax controller
 *
 */

// Initialize the WHMCS system
$rootpath	= dirname( dirname( dirname( dirname(__FILE__) ) ) ) . DIRECTORY_SEPARATOR;

// If we still have dbconnect
if ( file_exists( $rootpath . 'dbconnect.php' ) ) {
	require( $rootpath . "dbconnect.php" );
	require( $rootpath . "includes/functions.php" );
	require( $rootpath . "includes/clientareafunctions.php" );
}
// Else we may be in WHMCS v5.2
else {
	require( $rootpath . 'init.php' );
}

/*-- Dunamis Inclusion --*/
$path		= $rootpath . 'includes' . DIRECTORY_SEPARATOR . 'dunamis.php';
if ( file_exists( $path ) ) include_once( $path );
/*-- Dunamis Inclusion --*/

// Initialize Belong and determine the task
$dun		=	get_dunamis( 'intouch' );
$task		=	dunloader( 'input', true )->getVar( 'task', 'ping' );

/**
 * Ajax Module Class for Belong
 * @version		@fileVers@
 * 
 * @author		Steven
 * @since		2.0.0
 */
class IntouchAjaxDunModule extends IntouchAdminDunModule
{
	/**
	 * Method for executing a task
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	public function execute()
	{
		
	}
	
	
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
		$this->action = 'ajax';
		parent :: initialise();
	}
	
	
	/**
	 * Render the response back to the client
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @return		json encoded string
	 * @since		2.0.0
	 * @see			IntouchAdminDunModule :: render()
	 */
	public function render( $data = null )
	{
		$data	=	array();
		
		switch ( $this->task ) {
			// --------------------------------
			// Download update
			case 'updateinstall' :
				$updates	=	dunloader( 'updates', 'intouch' );
				$result		=	$updates->extract();
				$version	=	$updates->getVersion();
				
				$install = dunmodule( 'intouch.install' );
				$install->upgrade( false, "@fileVers@" ); // pass the original version along
				
				$data	=	array(
						'state'		=> 1,
						'title' 	=> t( 'intouch.updates.complete.title' ),
						'subtitle'	=> sprintf( t( 'intouch.updates.complete.subtitle' ), $version ),
				);
				
				break;
			// --------------------------------
			// Download update
			case 'updatedownload' :
				$updates	=	dunloader( 'updates', 'intouch' );
				$result		=	$updates->download();
				$state		=	( $result ? 'download' : 'error' );
				$error		=	( $result ? $updates->getError() : null );
				
				$data	=	array(
						'state'		=> ( $result ? 1 : 0 ),
						'title' 	=> t( 'intouch.updates.' . $state . '.title' ),
						'subtitle'	=> sprintf( t( 'intouch.updates.' . $state . '.subtitle' ), $error ),
				);
				
				break;
			// --------------------------------
			// Initialize update
			case 'updateinit' :
				$updates	=	dunloader( 'updates', 'intouch' );
				
				$data	=	array(
						'title' 	=> t( 'intouch.updates.init.title' ),
						'subtitle'	=> sprintf( t( 'intouch.updates.init.subtitle' ), $updates->getVersion() ),
				);
				break;
			// --------------------------------
			// Update checker
			case 'checkforupdates' :
				$updates	=	dunloader( 'updates', 'intouch', array( 'force' => true ) );
				$insert		=	null;
				
				switch( $updates->exist() ) {
					case true:
						$var	=	'exist';
						$state	=	1;
						break;
					case false:
						$var	=	'none';
						$state	=	0;
						$insert	=	$updates->getVersion();
						break;
					case 'error' :
						$var	=	'error';
						$state	=	-1;
						$insert	=	$updates->getError();
						break;
				}
				
				$data	=	array(
						'state'		=> $state, 
						'title' 	=> t( 'intouch.updates.' . $var . '.title' ),
						'subtitle'	=> sprintf( t( 'intouch.updates.' . $var . '.subtitle' ), $insert ),
						);
				
				break;
			// --------------------------------
			// Update checker
			case 'ping' :
				$data	= array( 'data' => 'pong' );
				break;
		}
		
		return json_encode( $data );
	}
}

/**
 * Here we are actually calling the module up
 */
$module	= new IntouchAjaxDunModule();
$module->initialise();
$module->execute();
echo $module->render();