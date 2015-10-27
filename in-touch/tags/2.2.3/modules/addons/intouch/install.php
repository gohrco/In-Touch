<?php defined('DUNAMIS') OR exit('No direct script access allowed');
/**
 * @projectName@
 * In Touch - Installation Module Base File
 *
 * @package    @projectName@
 * @copyright  @copyWrite@
 * @license    @buildLicense@
 * @version    @fileVers@ ( $Id$ )
 * @author     @buildAuthor@
 * @since      2.0.0
 *
 * @desc       This file handles the installation tasks for the product
 *
 */


/**
 * Install Module Class for In Touch
 * @version		@fileVers@
 *
 * @author		Steven
 * @since		2.0.0
 */
class IntouchInstallDunModule extends WhmcsDunModule
{
	
	private $destinationpath;
	private $sourcepath;
	
	
	/**
	 * Performs module activation
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.0.0
	 */
	public function activate()
	{
		// Load the database handler
		$db	= dunloader( 'database', true );
		
		// Load the initial tables
		$db->handleFile( 'sql' . DIRECTORY_SEPARATOR . 'install.sql', 'intouch' );
		
		// Now we need to insert the settings
		$table = $this->_getTablevalues();
		
		foreach ( $table as $key => $value ) {
			$db->setQuery( "SELECT * FROM `mod_intouch_settings` WHERE `key`=" . $db->Quote( $key ) );
			if ( $db->loadResult() ) continue;
			
			$db->setQuery( "INSERT INTO `mod_intouch_settings` ( `key`, `value` ) VALUES (" . $db->Quote( $key ) . ", " . $db->Quote( $value ) . " )" );
			$db->query();
		}
		
		// Template time
		$files			=	$this->_getTemplatefiles();
		
		foreach ( $files as $file ) {
			// If this is an upgrade or we have already copied the files in place for some reason don't do it again
			if ( $this->_isFilesizesame( $file ) ) {
				@unlink( $this->sourcepath . $file );
				continue;
			}
				
			$this->fixFile( $file );
				
			continue;
		}
		
		$this->_customiseEmails();
		
		// Serialize array for storage
		$checksum	= serialize( $checksum );
		$db->setQuery( "UPDATE `mod_intouch_settings` SET `value` = " . $db->Quote( $checksum ) . " WHERE `key` = 'checksum' " );
		$db->query();
	}
	
	
	/**
	 * Method for cycling through files to check for updated / modified files
	 * @access		public
	 * @version		@fileVers@ ( $id$ )
	 *
	 * @return		array of objects
	 * @since		2.2.2
	 */
	public function checkFiles( $tpl = null )
	{
		$files	=	$this->_getTemplatefiles( $tpl );
		$css	=	$this->_getTemplatefiles( $tpl, 'css' );
		$js		=	$this->_getTemplatefiles( $tpl, 'js' );
		$eot	=	$this->_getTemplatefiles( $tpl, 'eot' );
		$svg	=	$this->_getTemplatefiles( $tpl, 'svg' );
		$ttf	=	$this->_getTemplatefiles( $tpl, 'ttf' );
		$woff	=	$this->_getTemplatefiles( $tpl, 'woff' );
		$files	=	array_merge( $files, $css, $js, $eot, $svg, $ttf, $woff );
		sort( $files );
	
		foreach ( $files as $file ) {
				
			$tmp	=	(object) array(
					'current'	=>	false,
					'error'		=>	false,
					'code'		=>	0,
			);
				
			if ( $this->_isFilesizesame( $file ) ) {
				$tmp->current = true;
				$this->files[$file]	=	$tmp;
				continue;
			}
				
			// Read files
			$source	=	file_exists( $this->sourcepath . $file )		? @file( $this->sourcepath . $file ) : false;
			$dest	=	file_exists( $this->destinationpath . $file )	? @file( $this->destinationpath . $file ) : false;
				
			// Catch errors reading
			if (! $source || ! $dest ) {
				$tmp->code			=	(! $source ? 1 : 4 );
				$tmp->error			=	t( 'intouch.install.file.error.read', ( ! $source ? 'source' : 'existing template' ) );
				$this->files[$file]	=	$tmp;
				continue;
			}
				
			// Find versions of files
			$sv	=
			$dv	=	false;
				
			foreach( array( 'sv' => 'source', 'dv' => 'dest' ) as $holder => $item ) {
				foreach ( $$item as $s ) {
					if ( preg_match( '/@version\s+([0-9\.]+)/im', $s, $matches, PREG_OFFSET_CAPTURE ) ) {
						$$holder	=	$matches[1][0];
						break;
					}
				}
			}
				
			// Ensure we found versions
			if (! $dv || ! $sv ) {
				$tmp->code			=	2;
				$tmp->error			=	t( 'intouch.install.file.error.version', ( ! $sv ? 'source' : 'existing template' ) );
				$this->files[$file]	=	$tmp;
				continue;
			}
				
			// Do our comparisons
			if ( version_compare( $dv, $sv, 'lt' ) ) {
				$tmp->code			=	4;
				$tmp->error			=	t( 'intouch.install.file.error.newer', ucfirst( t( 'intouch.install.file.intouch' ) ), t( 'intouch.install.file.template' ) );
			}
			else if ( version_compare( $dv, $sv, 'gt' ) ) {
				$tmp->code			=	8;
				$tmp->error			=	t( 'intouch.install.file.error.newer', ucfirst( t( 'intouch.install.file.template' ) ), t( 'intouch.install.file.intouch' ) );
			}
			else {
				$tmp->current		=	true;
			}
				
			$this->files[$file]	=	$tmp;
		}
	
		return $this->files;
	}
	
	
	/**
	 * Performs module deactivation
	 * @access		public
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	public function deactivate()
	{
		// Be sure to deactivate the database if we need to
		$db		=	dunloader( 'database', true );
		$config	=	dunloader( 'config', 'intouch' );
		
		if (! $config->get( 'preservedb', false ) ) {
			$db->handleFile( 'sql' . DIRECTORY_SEPARATOR . 'uninstall.sql', 'intouch' );
		}
		
		// Template time
		$files			=	$this->_getTemplatefiles( null, 'bak' );
		
		foreach ( $files as $file ) {
				
			// Since we have an array of .bak files, we need to figure the actual filename
			$actualfile	=	str_replace( '.bak', '', $file );
				
			// Use the original 'bak' file as it should be our first one
			$backupfile	=	$this->sourcepath . $file;
				
			// Move the current file to the source position
			@unlink( $this->destinationpath . $actualfile );
		
			// Move the backup file to the current position
			@rename( $backupfile, $this->destinationpath . $actualfile );
		}
		
		$this->_revertEmails();
		
		return true;
	}
	
	
	/**
	 * Method for moving a single file into place
	 * @access		public
	 * @version		@fileVers@ ( $id$ )
	 * @param		string		- $file: subpath of the file / filename to handle
	 *
	 * @return		boolean
	 * @since		2.2.0
	 */
	public function fixFile( $file )
	{
		// Watch for the first backups (original files)
		if ( file_exists( $this->sourcepath . $file . '.bak' ) ) {
			$backupfile	=	$this->sourcepath . $file . '.' . DUN_MOD_JWHMCS;
	
			// Be sure this isn't some sort of issue
			if ( file_exists( $backupfile ) ) {
				@unlink( $backupfile );
			}
		}
		else {
			$backupfile	=	$this->sourcepath . $file . '.bak';
		}
	
		// We may be putting new files in place... so be sure the destination is there before renaming it
		if ( file_exists( $this->destinationpath . $file ) ) {
			// Move the current file into the backup position
			@rename( $this->destinationpath . $file, $backupfile );
		}
			
		// Move the new to current position
		@copy( $this->sourcepath . $file, $this->destinationpath . $file );
	
		return true;
	}
	
	
	/**
	 * Initializes the module
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.0.0
	 */
	public function initialise()
	{
		// Template time
		$this->sourcepath		=	dirname( __FILE__ ) . DIRECTORY_SEPARATOR
								.	'templates' . DIRECTORY_SEPARATOR
								.	get_intouch_version() . DIRECTORY_SEPARATOR;
		$this->destinationpath	=	DUN_ENV_PATH
								.	'templates'	. DIRECTORY_SEPARATOR;
		
	}
	
	
	/**
	 * Method to handle upgrading from the WHMCS addons manager or from our upgrader
	 * @access		public
	 * @version		@fileVers@ ( $id$ )
	 * @param		boolean		- $iswhmcs: indicates we are coming from WHMCS addonmodules page [t|F]
	 * @param		string		- $origvers: contains the originally installed version number
	 *
	 * @return		boolean
	 * @since		2.0.8
	 */
	public function upgrade( $iswhmcs = false, $origvers = null )
	{
		// Handle file manipulation here
		$this->_handleFiles( $origvers );
		
		$db			=	dunloader( 'database', true );
		$files		=	$this->_getUpgradefiles();
		$version	=	$db->Quote( "@fileVers@" );
		
		foreach ( $files as $v => $file ) {
			if ( version_compare( $v, $origvers, 'l' ) ) continue;
			$db->handleFile( $filename, 'intouch' );
		}
		
		if ( $iswhmcs ) return true;
		
		$db->setQuery( "UPDATE `tbladdonmodules` SET `value` = $version WHERE `module` = 'intouch' AND `setting` = 'version'" );
		$db->query();
	
		return true;
	}
	
	
	/**
	 * We must customise the Quotes due to limitation in system
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	private function _customiseEmails()
	{
		$db		=	dunloader( 'database', true );
		$db->setQuery( "SELECT * FROM `tblemailtemplates` WHERE `type` = 'general' AND `name` LIKE '%Quote%' AND `name` NOT LIKE '%Notification%'" );
		$emails	=	$db->loadObjectList();
		
		foreach ( $emails as $email ) {
			$message	=	$email->message;
			$regex		=	'#{\$signature}#i';
			$message	=	preg_replace( $regex, '{$intouchsignature}', $message );
			$message	=	'{$intouchstyle}{$intouchheader}' . $message . '{$intouchfooter}{$intouchlegal}';
			$db->setQuery( "UPDATE `tblemailtemplates` SET `message` = '" . $message . "' WHERE `id` = " . $db->Quote( $email->id ) );
			$db->query();
		}
	}
	
	
	/**
	 * Method to customize the viewinvoice.tpl files in each template folder
	 * @deprecated	2.2.0
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	private function _customiseFiles()
	{
		/*
		$tmpl_root	= ROOTDIR . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
		$tmpl_dirs	= $this->_getTemplateDirs();
		$file_todo	= array( 'viewinvoice', 'invoicepdf' );
		
		// We have the template directories, now lets run through each to pull the viewinvoice.tpl files
		foreach ( $tmpl_dirs as $dir ) {
			$tmpl_path	= $tmpl_root . $dir . DIRECTORY_SEPARATOR;
			
			// File:  viewinvoice.tpl 
			$content	= file_get_contents( $tmpl_path . 'viewinvoice.tpl' );
			if ( strpos( $content, 'In Touch Customization and File Inclusion' ) === false ) {
				rename( $tmpl_path . 'viewinvoice.tpl', $tmpl_path . 'viewinvoice.intouch.bak.tpl' );
				
				$regex	= array(
						"/((<br \/><br \/><br \/><br \/><br \/>)\s+(<\/td>))/i" => "\$2\n{\$legal}\n\$3",	// Portal and Classic Templates
						"#({if \\\$notes}\s+<p>{\\\$LANG.invoicesnotes}: {\\\$notes}<\/p>\s+{\/if}\s+{\/if})\s+(<\/div>)#i" => "\$1\n{\$legal}\n\$2",	// Default Template
				);
				
				
				foreach ( $regex as $f => $r )
					$content	=	preg_replace($f, $r, $content, 1 );
				
				
				$content	= $this->_getCustomCode( 'viewinvoice' ) . $content;
				file_put_contents( $tmpl_path . 'viewinvoice.tpl', $content );
			}
			
			// File:  viewquote.tpl
			$content	= file_get_contents( $tmpl_path . 'viewquote.tpl' );
			if ( strpos( $content, 'In Touch Customization and File Inclusion' ) === false ) {
				rename( $tmpl_path . 'viewquote.tpl', $tmpl_path . 'viewquote.intouch.bak.tpl' );
				
				$regex	= array(
						"/((<br \/><br \/><br \/><br \/><br \/>)\s+(<\/td>))/i" => "\$2\n{\$legal}\n\$3",
				);
				
				foreach ( $regex as $f => $r )
					$content	=	preg_replace($f, $r, $content, 1 );
				
				$content	= $this->_getCustomCode( 'viewquote' ) . $content;
				file_put_contents( $tmpl_path . 'viewquote.tpl', $content );
			}
			
			// File:  invoicepdf.tpl
			$content	= file_get_contents( $tmpl_path . 'invoicepdf.tpl' );
			if ( strpos( $content, 'In Touch Customization and File Inclusion' ) === false ) {
				rename( $tmpl_path . 'invoicepdf.tpl', $tmpl_path . 'invoicepdf.intouch.bak.tpl' );
				
				$legalfooter	=	<<< HTML

# Legal Footer
\$pdf->writeHTML( html_entity_decode( \$legalfooter ), true, false, false, false, '' );

# Generation Date
HTML;
				
				$regex	= array(
						"#(\<\?php)#i" => '',
						"#(.+?/images/logo\.png)#i" => '#${1}',
						"#(.+?/images/logo\.jpg)#i" => '#${1}',
						"#(.+?/images/placeholder\.png)#i" => '#${1}',
						"#(\# Generation Date)#i" => $legalfooter,
				);
				
				foreach ( $regex as $f => $r )
					$content	=	preg_replace($f, $r, $content, 1 );
				
				$content	= $this->_getCustomCode( 'invoicepdf' ) . $content;
				file_put_contents( $tmpl_path . 'invoicepdf.tpl', $content );
			}
			
			// File:  quotepdf.tpl
			$content	= file_get_contents( $tmpl_path . 'quotepdf.tpl' );
			if (! empty( $content ) && strpos( $content, 'In Touch Customization and File Inclusion' ) === false ) {
				rename( $tmpl_path . 'quotepdf.tpl', $tmpl_path . 'quotepdf.intouch.bak.tpl' );
				
				$legalfooter	=	<<< HTML
# Legal Footer
\$pdf->writeHTML( html_entity_decode( \$legalfooter ), true, false, false, false, '' );

HTML;
				
				$regex	= array(
						"#(\<\?php)#i" => '',
						"#(.+?/images/logo\.png)#i" => '#${1}',
						"#(.+?/images/logo\.jpg)#i" => '#${1}',
						"#(.+?/images/placeholder\.png)#i" => '#${1}',
						"#(if \(\\\$notes\) {[.\s\S]+})#i" => "\${1}\n\n{$legalfooter}",
				);
				
				foreach ( $regex as $f => $r )
					$content	=	preg_replace($f, $r, $content, 1 );
				
				$content	= $this->_getCustomCode( 'quotepdf' ) . $content;
				file_put_contents( $tmpl_path . 'quotepdf.tpl', $content );
			}
		}
		
		return true;*/
	}
	
	
	/**
	 * Method for gathering the customized view invoice code
	 * @deprecated	2.2.0
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		string
	 * @since		2.0.0
	 */
	private function _getCustomCode( $type = 'viewinvoice' )
	{
		/*
		// Build the header first
		switch ( $type ) {
			case 'quotepdf' :
				$data	=	"<?php ";
				$path	=	"\$path	=	( isset( \$this->template_dir ) ? rtrim( dirname( \$this->template_dir ), DIRECTORY_SEPARATOR ) : dirname( dirname( __DIR__ ) ) );"
						.	"\$path	.=	DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'dunamis.php';";
				$module	= 'quotes';
				break;
			case 'invoicepdf' :
				$data	= "<?php ";
				$path	=	"\$path	=	( isset( \$this->template_dir ) ? rtrim( dirname( \$this->template_dir ), DIRECTORY_SEPARATOR ) : dirname( dirname( __DIR__ ) ) );"
						.	"\$path	.=	DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'dunamis.php';";
				$module	= 'invoices';
				break;
			case 'viewquote' :
				$data	= "{php} ";
				$path	= "global \$smarty;

\$path	=	dirname( dirname( realpath( \$smarty->template_dir ) ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'dunamis.php';";
				$module	= 'quotes';
				break;
			case 'viewinvoice' :
				$data	= "{php} ";
				$path	= "global \$smarty;

\$path	=	dirname( realpath( \$smarty->template_dir ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'dunamis.php';";
				$module	= 'invoices';
				break;
		} // End Header Build
		
		// Common header
		$data	.= <<< CODE

/**
 *	=========================================
 *	In Touch Customization and File Inclusion
 *	=========================================
 *	The customized code below is required for
 *	In Touch to customize the logo and address
 *	of your invoices.  Settings may be changed
 *	in the backend of WHMCS under the
 *		Addons tab -> In Touch link
 *
 *	Should the Dunamis Framework or In Touch be
 *	removed, this file should function without
 *	causing any errors.
 *\/
		
// We must have the Dunamis Framework so lets build the path
$path
\$logo			= false;
\$legalfooter	= null;

// If the Dunamis Framework file is in place we should be okay to load
if ( file_exists( \$path ) ) {
		
	// Include the file
	include_once( \$path );
		
	// Initialize the invoices controller of the In Touch module
	\$module	= dunmodule( 'intouch.$module' );
		
	// If we got back false then there is a problem
	if ( \$module ) {
		
		// See if we should even be customizing
		if ( \$module->shouldCustomize() ) {

CODE;
		
		switch ( $type ) :
		case 'quotepdf' :
		case 'invoicepdf' :
			$data	.= <<< CODE
			\$logo			= \$module->getLogoPath();
			\$addr			= \$module->getCustomAddress();
			\$legalfooter	= \$module->getLegalFooter();
			
			if ( \$addr ) {
				\$companyaddress = \$addr;
			}
CODE;
			break;
		case 'viewquote' :
		case 'viewinvoice' :
			$data	.= <<< CODE
			// Grab the global Smarty object
			\$sm		= \$GLOBALS['smarty'];
			
			// Assign the custom logo and the custom payto variables to the template
			\$sm->assign( 'logo', \$module->getLogoUrl() );
			if ( \$addr	= \$module->getCustomAddress() ) \$sm->assign( 'payto', implode( "<br/>", \$addr ) );
			if ( \$legal   = \$module->getLegalFooter() ) \$sm->assign( 'legal', htmlspecialchars_decode( \$legal) );
CODE;
			break;
		endswitch;

		// Common Footer
		$data	.= <<< CODE
		
		}
	}
}
/**
 *	=========================================
 *	End of In Touch Customization Section
 *	=========================================
 *\/
CODE;
		
		switch ( $type ) {
			case 'quotepdf' :
			case 'invoicepdf' :
				$data .= <<< CODE
				
# In Touch Logo Customization
if ( \$logo ) \$pdf->Image( \$logo, 20, 25, 75 );
else if (file_exists(ROOTDIR.'/images/logo.png')) \$pdf->Image(ROOTDIR.'/images/logo.png',20,25,75);
elseif (file_exists(ROOTDIR.'/images/logo.jpg')) \$pdf->Image(ROOTDIR.'/images/logo.jpg',20,25,75);
else \$pdf->Image(ROOTDIR.'/images/placeholder.png',20,25,75);
				
CODE;
				break;
			case 'viewquote' :
			case 'viewinvoice' :
				$data .= ' {/php}';
				break;
		} // End Footer build
		return $data;*/
	}
	
	
	/**
	 * Method to get the table values
	 * @access		private
	 * @version		@fileVers@
	 * @param		string		- $config: which table to get for
	 * 
	 * @return		array
	 * @since		2.0.0
	 */
	private function _getTablevalues( $config = 'settings' )
	{
		$data	= array();
		switch ( $config ) :
		case 'settings' :
			$data	= array(
					'license'		=> null,
					'localkey'		=> null,
					'enable'		=> 0,
					'apiuser'		=> null,
					'checksum'		=> null,
					'updates'		=> null,
					'usewysiwyg'	=> 1,
					);
		break;
		endswitch;
		
		return $data;
	}
	
	
	/**
	 * Method to get the template directory names
	 * @deprecated	2.2.0
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		array
	 * @since		2.0.0
	 */
	private function _getTemplateDirs()
	{
		/*
		$tmpl_dirs	= array();
		$tmpl_root	= ROOTDIR . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
		
		//Start by finding all the template directories
		$dh			= opendir( $tmpl_root );
		while ( ( $filename = readdir( $dh ) ) !== false ) {
			if ( in_array( $filename, array( '.', '..', 'orderforms' ) ) ) continue;
			if (! is_dir( $tmpl_root . $filename ) ) continue;
			$tmpl_dirs[]	= $filename;
		}
		
		closedir( $dh );
		
		return $tmpl_dirs;
		*/
	}
	
	
	/**
	 * Method to gather tpl files for moving around
	 * @access		private
	 * @version		@fileVers@ ( $id$ )
	 * @param		string		- $subdir: any recursive subdirs
	 * @param		string		- $type: indicates what we are looking for [tpl|bak]
	 *
	 * @return		array
	 * @since		2.2.0
	 */
	private function _getTemplatefiles( $subdir = null, $type = 'tpl' )
	{
		$files	=	array();
		$path	=	$this->sourcepath
		.	$subdir;
	
		$dh	=	scandir( $path );
	
		foreach ( $dh as $file ) {
			if ( in_array( $file, array( '.', '..', 'custom.css', 'custom.css.new' ) ) ) continue;
			if ( is_dir( $path . $file ) ) {
				$files	=	array_merge( $files, $this->_getTemplatefiles( $subdir . $file . DIRECTORY_SEPARATOR, $type ) );
				continue;
			}
			$info	=	pathinfo( $file );
			if ( $info['extension'] != $type ) continue;
			$files[]	=	$subdir . $file;
		}
	
		return $files;
	}
	
	
	/**
	 * Method to get the upgrade files and ensure they are in order
	 * @access		private
	 * @version		@fileVers@ ( $id$ )
	 *
	 * @return		array
	 * @since		2.0.0
	 */
	private function _getUpgradefiles()
	{
		$dh		=	opendir( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'sql' );
		$files	=	array();
	
		while( ( $file = readdir( $dh ) ) !== false ) {
			if ( in_array( $file, array( '.', '..' ) ) ) continue;
			if (! preg_match( "#upgrade-(.*)\.sql#", $file, $matches ) ) continue;
			$files[$matches[1]] = $file;
		}
		
		return $files;
	}
	
	
	/**
	 * Method for handling file removal / manipulation by version changes
	 * @access		private
	 * @version		@fileVers@ ( $id$ )
	 * @param		string		- $version: contains the currently installed version (not the upgraded version)
	 *
	 * @since		2.0.8
	 */
	private function _handleFiles( $version )
	{
		// --------------------
		// Version 2.0.5 change
		if ( version_compare( $version, '2.0.5', 'le' ) ) {
			$path	=	dirname( __FILE__ ) . 'dunamis' . DIRECTORY_SEPARATOR . 'fields' . DIRECTORY_SEPARATOR;
			if ( file_exists( $path . 'toggleyn.php' ) ) {
				@unlink( $path . 'toggleyn.php' );
			}
		}
		
		// --------------------
		// Future Changes Here
		
	}
	
	
	/**
	 * Method for testing if the file size is the same
	 * @access		private
	 * @version		@fileVers@ ( $id$ )
	 * @param		string		- $file: relative path to file
	 *
	 * @return		bool
	 * @since		2.5.0
	 */
	private function _isFilesizesame( $file = null )
	{
		// If the destination doesnt exist it cant be the same
		if (! file_exists( $this->destinationpath . $file ) ) {
			return false;
		}
		return md5_file( $this->sourcepath . $file ) == md5_file( $this->destinationpath . $file );
	}
	
	
	/**
	 * Method to change these email templates back
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	private function _revertEmails()
	{
		$db		=	dunloader( 'database', true );
		$db->setQuery( "SELECT * FROM `tblemailtemplates` WHERE `type` = 'general' AND `name` LIKE '%Quote%' AND `name` NOT LIKE '%Notification%'" );
		$emails	=	$db->loadObjectList();
		
		foreach ( $emails as $email ) {
			$message	=	$email->message;
			$regex		=	array( '#{\$intouchsignature}#i', '#{\$intouchheader}#i', '#{\$intouchstyle}#i', '#{\$intouchfooter}#i', '#{\$intouchlegal}#i' );
			$replace	=	array( '{$signature}', '', '', '', '' );
			$message	=	preg_replace( $regex, $replace, $message );
			$db->setQuery( "UPDATE `tblemailtemplates` SET `message` = '" . $message . "' WHERE `id` = " . $db->Quote( $email->id ) );
			$db->query();
		}
	}
	
	
	/**
	 * Method to revert the viewinvoices files back
	 * @deprecated	2.2.0
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		boolean
	 * @since		2.0.0
	 */
	private function _revertFiles()
	{
		/*
		$tmpl_root	= ROOTDIR . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
		$tmpl_dirs	= $this->_getTemplateDirs();
		$file_todo	= array( 'viewinvoice', 'invoicepdf', 'viewquote', 'quotepdf' );
		
		// We have the template directories, now lets run through each to pull the viewinvoice.tpl files
		foreach ( $tmpl_dirs as $dir ) {
			$tmpl_path	= $tmpl_root . $dir . DIRECTORY_SEPARATOR;
			
			foreach ( $file_todo as $file ) {
				if (! file_exists( $tmpl_path . $file . '.intouch.bak.tpl' ) || ! file_exists( $tmpl_path . $file . '.tpl' ) ) continue;
				unlink( $tmpl_path . $file . '.tpl' );
				rename( $tmpl_path . $file . '.intouch.bak.tpl', $tmpl_path . $file . '.tpl' );
			}
		}
		
		return true;
		*/
	}
}