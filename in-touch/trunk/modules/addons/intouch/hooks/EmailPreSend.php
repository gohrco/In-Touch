<?php

global $aInt;

/**
 * Handle the client group id when adding client from quote conversion
 * @desc		This can't be caught at ClientAdd b/c email would already sent w/out groupid being set
 */
if ( $vars['messagename'] == 'Client Signup Email' ) {
	if ( is_object( $aInt ) && isset( $aInt->filename ) && $aInt->filename == 'quotes' ) {
		dunmodule( 'intouch.adminareapages' )->execute( 'quotes', 'default', array( 'userid' => $vars['relid'] ) );
	}
}


// WHMCS 5.1 Method:
// =================
if ( version_compare( DUN_ENV_VERSION, '5.1', 'ge' ) ) {
	
	// See if this is the looped call for our email template
	if ( strpos( $vars['messagename'], 'Mass Mail Template' ) === false ) {
		// We are intercepting the email and sending our own (WHMCS uses the Mass Mail Template for custom emails)
		$response	=	dunmodule( 'intouch.emails' )->intercept( $vars );
		dunmodule( 'intouch.emails' )->cleanMess( $vars );
	}
	else {
		$response	=	dunmodule( 'intouch.emails' )->massmailcheck( $vars );
	}
}
// WHMCS 5.0 Method:
// ================= 
else {
	global $intouch_tag;
	
	if ( $intouch_tag !== true ) {
		$intouch_tag	=	true;
		$response		=	dunmodule( 'intouch.emails' )->intercept( $vars );
	}
	else {
		$intouch_tag	=	false;
		dunmodule( 'intouch.emails' )->cleanMess( $vars );
	}
}