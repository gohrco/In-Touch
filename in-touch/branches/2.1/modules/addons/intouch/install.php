<?php


class IntouchInstallDunModule extends WhmcsDunModule
{
	
	/**
	 * Performs module activation
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.0.0
	 */
	public function activate()
	{
		// Lets customize our files first
		if ( ( $checksum = $this->_customiseFiles() ) === false ) {
			// Problem so dont continue;
			return false;
		}
		
		$this->_customiseEmails();
		
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
		
		// Serialize array for storage
		$checksum	= serialize( $checksum );
		$db->setQuery( "UPDATE `mod_intouch_settings` SET `value` = " . $db->Quote( $checksum ) . " WHERE `key` = 'checksum' " );
		$db->query();
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
		// Lets undo our customized files first
		if (! $this->_revertFiles() ) {
			// Problem so dont continue;
			return false;
		}
		
		$this->_revertEmails();
		
		return true;
	}
	
	
	/**
	 * Initializes the module
	 * @access		public
	 * @version		@fileVers@
	 *
	 * @since		2.0.0
	 */
	public function initialise() { }
	
	
	public function upgrade( $version,	$from = '2.0.0' )
	{
		// Lets handle upgrades from 2.0
		if ( version_compare( $from, '2.1', 'l' ) ) {
			// First revert the files
			$this->_revertFiles();
			
			// Now lets convert to the new files
			$this->_customiseFiles();
		}
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
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @since		2.0.0
	 */
	private function _customiseFiles()
	{
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
				$content	= $this->_getCustomCode( 'viewinvoice' ) . $content;
				file_put_contents( $tmpl_path . 'viewinvoice.tpl', $content );
			}
			
			// File:  viewquote.tpl
			$content	= file_get_contents( $tmpl_path . 'viewquote.tpl' );
			if ( strpos( $content, 'In Touch Customization and File Inclusion' ) === false ) {
				rename( $tmpl_path . 'viewquote.tpl', $tmpl_path . 'viewquote.intouch.bak.tpl' );
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
			if ( strpos( $content, 'In Touch Customization and File Inclusion' ) === false ) {
				rename( $tmpl_path . 'quotepdf.tpl', $tmpl_path . 'quotepdf.intouch.bak.tpl' );
				
				$legalfooter	=	<<< HTML

if (\$notes) {
	\$pdf->Ln(6);
    \$pdf->SetFont('freesans','',8);
	\$pdf->MultiCell(170,5,\$_LANG['invoicesnotes'].": \$notes");
}

# Legal Footer
\$pdf->writeHTML( html_entity_decode( \$legalfooter ), true, false, false, false, '' );

HTML;
				
				$regex	= array(
						"#(\<\?php)#i" => '',
						"#(.+?/images/logo\.png)#i" => '#${1}',
						"#(.+?/images/logo\.jpg)#i" => '#${1}',
						"#(.+?/images/placeholder\.png)#i" => '#${1}',
						"#(if \(\$notes\) \{[.\s\S]+\})#i" => $legalfooter,
				);
			
				foreach ( $regex as $f => $r )
					$content	=	preg_replace($f, $r, $content, 1 );
			
				$content	= $this->_getCustomCode( 'quotepdf' ) . $content;
				file_put_contents( $tmpl_path . 'quotepdf.tpl', $content );
			}
		}
		
		return true;
	}
	
	
	/**
	 * Method for gathering the customized view invoice code
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		string
	 * @since		2.0.0
	 */
	private function _getCustomCode( $type = 'viewinvoice' )
	{
		// Build the header first
		switch ( $type ) {
			case 'quotepdf' :
				$data	= "<?php ";
				$path	= "dirname( dirname( dirname(__FILE__) ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'dunamis.php'";
				$module	= 'quotes';
				break;
			case 'invoicepdf' :
				$data	= "<?php ";
				$path	= "dirname( dirname( dirname(__FILE__) ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'dunamis.php'";
				$module	= 'invoices';
				break;
			case 'viewquote' :
				$data	= "{php} ";
				$path	= "dirname( dirname(__FILE__) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'dunamis.php'";
				$module	= 'quotes';
				break;
			case 'viewinvoice' :
				$data	= "{php} ";
				$path	= "dirname( dirname(__FILE__) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'dunamis.php'";
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
 */
		
// We must have the Dunamis Framework so lets build the path
\$path			= $path;
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
 */
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
		return $data;
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
					'license'	=> null,
					'localkey'	=> null,
					'enable'	=> 0,
					'apiuser'	=> null,
					'checksum'	=> null,
					'updates'	=> null,
					);
		break;
		endswitch;
		
		return $data;
	}
	
	
	/**
	 * Method to get the template directory names
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		array
	 * @since		2.0.0
	 */
	private function _getTemplateDirs()
	{
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
			$regex		=	array( '#{\$intouchsignature}#i', '#{\$intouchheader}#i', '#{\$intouchstyle}#i', '#{\$intouchfooter}#i' );
			$replace	=	array( '{$signature}', '', '', '' );
			$message	=	preg_replace( $regex, $replace, $message );
			$db->setQuery( "UPDATE `tblemailtemplates` SET `message` = '" . $message . "' WHERE `id` = " . $db->Quote( $email->id ) );
			$db->query();
		}
	}
	
	
	/**
	 * Method to revert the viewinvoices files back
	 * @access		private
	 * @version		@fileVers@
	 * 
	 * @return		boolean
	 * @since		2.0.0
	 */
	private function _revertFiles()
	{
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
	}
}