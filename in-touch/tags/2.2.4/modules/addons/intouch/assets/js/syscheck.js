/**
 * Function to check for updates via ajax (backend)
 */
$(document).ready( function() {
	
	$( 'button.fixfile' ).click( function() {
		fixTemplate( $( this ).data( 'filename' ), $( this ).data( 'refid' ) );
	})
	
	$( 'button#btnfixall' ).click( function() {
		$( 'button.fixfile' ).each( function() {
			fixTemplate( $( this ).data( 'filename' ), $( this ).data( 'refid' ) );
		});
		$(this).remove();
	})
});

function fixTemplate( template, refid )
{
	var icon	=	jQuery( '#icon' + refid );
	var span	=	jQuery( '#badge' + refid );
	var help	=	jQuery( '#help' + refid );
	var btn		=	jQuery( '#btn' + refid );
	
	icon.removeClass( 'icon-remove' ).addClass( 'icon-cog' );
	
	// Make the Ajax Call
	var resp	= jQuery.ajax({
					type: "POST",
					url: ajaxurl + '/ajax.php',
					dataType: 'json',
					data: { task: 'fixfile', file: template
					}
				});
	
	// Success
	resp.done( function( msg ) {
		icon.removeClass( 'icon-cog' );
		
		switch ( msg.state ) {
		// File updated
		case 1:
			icon.addClass( 'icon-ok' );
			span.removeClass( 'badge-important' ).addClass( 'badge-inverse' ).html( msg.span );
			btn.remove();
			help.remove();
			break;
		
		// Some error
		case 0:
			icon.addClass( 'icon-remove' );
			break;
		}
	});
	
	// Failure
	resp.fail( function( jqXHR, msg ) {
		alert( msg );
	});
}