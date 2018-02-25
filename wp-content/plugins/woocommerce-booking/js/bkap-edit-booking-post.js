jQuery( document ).ready( function () {
	jQuery( '#bkap_delete' ).on( 'click', function() {

		var y = confirm( edit_post_param.confirm_msg );
        if( y == true ) {
            
            var data = {
                    booking_id : edit_post_param.post_id,
                    action: 'bkap_delete_booking'
            };

            jQuery.post( edit_post_param.ajax_url, data, function( response ) {
                // reload the order page
                window.location.replace( edit_post_param.order_url );
            });
        }
	});
	
	jQuery( '.bkap_cancel' ).on( 'click', function() {
		
		// reload the order page
        window.location.replace( edit_post_param.order_url );
	});

	jQuery( '#bkap_qty' ).on( 'click', function() {
		
		if ( 'multiple_days' == edit_post_param.booking_type ) {
			if ( jQuery( "#wapbk_hidden_date" ).val() != "" && jQuery( "#wapbk_hidden_date_checkout" ).val() != "" ) {
				bkap_calculate_price();
			}
		} else {
			if ( jQuery( "#wapbk_hidden_date" ).val() != "" ) {
				bkap_single_day_price();
			} 
		}

	});

	jQuery( "#wapbk_hidden_date" ).val( edit_post_param.hidden_date );
	var split = jQuery( "#wapbk_hidden_date" ).val().split( "-" );
	var bookingDate = new Date( split[2], split[1]-1, split[0] );
	jQuery( "#booking_calender" ).datepicker( "setDate", bookingDate );
	
	if ( edit_post_param.booking_type === 'multiple_days' ) {
		
		if ( edit_post_param.pastCheckout === "YES" ) { // if checkout is a past date, set min date to same as checkin to ensure checkout is populated correctly
			jQuery( "#booking_calender_checkout" ).datepicker( "option", "minDate", bookingDate );
		}
		
		jQuery( "#wapbk_hidden_date_checkout" ).val( edit_post_param.hidden_checkout );
		var split = jQuery( "#wapbk_hidden_date_checkout" ).val().split( "-" );
		var checkoutDate = new Date( split[2], split[1]-1, split[0] );
		jQuery( "#booking_calender_checkout" ).datepicker( "setDate", checkoutDate );
		
		if( jQuery( '#block_option' ).length > 0 ) {
			jQuery( '#block_option' ).val( edit_post_param.block_value );
			var block_details = edit_post_param.block_details.split( '&' );
			jQuery( '#block_option_start_day' ).val( block_details[ 0 ] );
			jQuery( '#block_option_number_of_days' ).val( block_details[ 2 ] );
			jQuery( '#block_option_price' ).val( block_details[ 3 ] );
			
		}
		
		bkap_calculate_price();
	} else {
		bkap_process_date( edit_post_param.hidden_date );
	}

});