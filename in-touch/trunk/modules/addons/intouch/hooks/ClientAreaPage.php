<?php

if ( get_filename() == 'viewinvoice' ) {
	
	$module	= dunmodule( 'intouch.invoices' );
	
	if ( $module && is_object( $module ) ) {
		$module->customizeInvoice();
	}
	
}