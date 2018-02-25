jQuery( function ( $ ) {

	// Add buttons to product screen.
	var $product_screen = $( '.edit-php.post-type-bkap_booking' ),
		$title_action   = $product_screen.find( '.page-title-action:first' );

	$title_action.after( '<a href="'+ bkap_view_booking.url.print_url +'" class="page-title-action" target="_blank">'+ bkap_view_booking.labels.print_label +'</a>' );
	$title_action.after( '<a href="'+ bkap_view_booking.url.csv_url +'" class="page-title-action" target="_blank">'+ bkap_view_booking.labels.csv_label +'</a>' );
	$title_action.after( '<a href="'+ bkap_view_booking.url.calendar_url +'" class="page-title-action">'+ bkap_view_booking.labels.calendar_label +'</a>' );
});