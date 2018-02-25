jQuery(document).ready(function($) {
	jQuery( ".bkap_calendar_icon" ).click( function( e ) { 
		$( '.bkap_calendar_icon' ).attr( 'style', 'margin-right:20px;' );
		$( this ).attr( 'style', 'margin-right:20px; border:7px solid #0071ff' );
		$( '#bkap_calendar_icon_file' ).val( $( this ).attr( 'data-id' ) );
		$( '#bkap_calendar_icon_file' ).attr( 'checked', true );
		e.preventDefault();
	});
}); 