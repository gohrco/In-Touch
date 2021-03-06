<?php
/**
 * @projectName@
 * 
 * @package    @projectName@
 * @copyright  @copyWrite@
 * @license    @buildLicense@
 * @version    @fileVers@ ( $Id$ )
 * @author     @buildAuthor@
 * @since      2.0.0
 * 
 * @desc       This file is the root file loaded by WHMCS upon selection of the "WHMCS Themer" addon
 * 
 */

/*-- Security Protocols --*/
if (!defined("WHMCS")) die("This file cannot be accessed directly");
/*-- Security Protocols --*/

/*-- Dunamis Inclusion --*/
$path	= dirname( dirname( dirname( dirname(__FILE__) ) ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'dunamis.php';
if ( file_exists( $path ) ) include_once( $path );
/*-- Dunamis Inclusion --*/

/**
 * Configuration function called by WHMCS
 * 
 * @return An array of configuration variables
 * @since  2.0.0
 */
function intouch_config()
{
	if (! function_exists( 'dunmodule' ) ) return array( 'name' => 'In Touch', 'description' => 'The Dunamis Framework was not detected!  Be sure it is installed fully!', 'version' => "@fileVers@" );
	return dunmodule( 'intouch' )->getAdminConfig();
}


/**
 * Activation function called by WHMCS
 * 
 * @since  2.0.0
 */
function intouch_activate()
{
	if (! function_exists( 'dunloader' ) ) return;
	$install = dunmodule( 'intouch.install' );
	$install->activate();
}


/**
 * Deactivation function called by WHMCS
 * 
 * @since  2.0.0
 */
function intouch_deactivate()
{
	if (! function_exists( 'dunloader' ) ) return;
	$install = dunmodule( 'intouch.install' );
	$install->deactivate();
}


/**
 * Upgrade function called by WHMCS
 * @param  array		Contains the variables set in the configuration function
 * 
 * @since  2.0.0
 */
function intouch_upgrade($vars)
{
	
	if (! function_exists( 'dunloader' ) ) return;
	$db	= dunloader( 'database', true );
	
	// This is the originally installed version
	if ( isset( $vars['version'] ) ) {
		$version = $vars['version'];
	}
	else
	// But this is what is found in 441 (not that we support it)
	if ( isset( $vars['intouch']['version'] ) ) {
		$version = $vars['intouch']['version'];
	}
	
	// We need the installer
	$install = dunmodule( 'intouch.install' );
	$install->upgrade( true, $version );
}


/**
 * Output function called by WHMCS
 * @param  array		Contains the variables set in the configuration function
 * 
 * @since 2.0.0
 */
function intouch_output($vars)
{	
	if (! function_exists( 'dunmodule' ) ) return;
	echo dunmodule( 'intouch' )->renderAdminOutput();
}


/**
 * Function to generate sidebar menu called by WHMCS
 * @param  array		Contains the variables set in the configuration function
 * 
 * @since  2.0.0
 */
function intouch_sidebar($vars)
{
	
}



?>