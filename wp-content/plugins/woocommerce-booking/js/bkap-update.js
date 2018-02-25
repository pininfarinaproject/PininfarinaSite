function bkap_400_db_update() {
	
	// take the user to a new page
	var url = bkap_update_params.settings_url;
	
	url += '&action=bkap-update';
	window.location.href = url;
	
}

// Update for step 1.
jQuery( document ).on( 'click', '#bkap_update_link', function() {
	
	// run an ajax call to start the process
	var data = {
			action: 'bkap_manual_db_update'
	};
	
	// add an In Progress message
	jQuery( '#bkap_progress' ).html( bkap_update_params.progress );
	jQuery( '#bkap_progress' ).css( 'display', 'block' );
	
	jQuery.post( bkap_update_params.ajax_url, data, function( response ) {
		// if the response is successful, display a message
		if ( 'success' == response ) {
			
			// add the message to contact support
			jQuery( '#bkap_result' ).html( bkap_update_params.success_msg );
			jQuery( '#bkap_result' ).css( 'display', 'block' );
			jQuery( '#bkap_result' ).css( 'color', 'green' );
			jQuery( '#bkap_result' ).fadeOut( 5000 );
			// take them to the settings page
		/*	var url = bkap_update_params.settings_url;
			window.location.href = url; */
			
		} else if ( 'fail' == response ) {
			
			// add the message to contact support
			jQuery( '#bkap_result' ).html( bkap_update_params.support_request );
			jQuery( '#bkap_result' ).css( 'display', 'block' );
			jQuery( '#bkap_result' ).fadeOut( 5000 );
		}
	});
	
});

// Update for step 2.

jQuery( document ).on( 'click', '#bkap_update_link_f_p', function() {
	
	// run an ajax call to start the process
	var data = {
			action: 'bkap_manual_db_update_f_p'
	};
	
	// add an In Progress message
	jQuery( '#bkap_progress_f_p' ).html( bkap_update_params.progress_f_p );
	jQuery( '#bkap_progress_f_p' ).css( 'display', 'block' );
	
	jQuery.post( bkap_update_params.ajax_url, data, function( response ) {
		// if the response is successful, display a message
		if ( 'success' == response ) {
			
			// add the message to contact support
			jQuery( '#bkap_result_f_p' ).html( bkap_update_params.success_msg );
			jQuery( '#bkap_result_f_p' ).css( 'display', 'block' );
			jQuery( '#bkap_result_f_p' ).css( 'color', 'green' );
			jQuery( '#bkap_result_f_p' ).fadeOut( 5000 );
			// take them to the settings page
		/*	var url = bkap_update_params.settings_url;
			window.location.href = url; */
			
		} else if ( 'fail' == response ) {
			
			// add the message to contact support
			jQuery( '#bkap_result_f_p' ).html( bkap_update_params.support_request );
			jQuery( '#bkap_result_f_p' ).css( 'display', 'block' );
			jQuery( '#bkap_result_f_p' ).fadeOut( 5000 );
		}
	});
	
});

// Update for v4.2.0.
jQuery( document ).on( 'click', '#bkap_update_link_v420', function() {
	
	// run an ajax call to start the process
	var data = {
			action: 'bkap_manual_db_update_v420'
	};
	
	// add an In Progress message
	jQuery( '#bkap_progress' ).html( bkap_update_params.progress );
	jQuery( '#bkap_progress' ).css( 'display', 'block' );
	
	jQuery.post( bkap_update_params.ajax_url, data, function( response ) {
		// if the response is successful, display a message
		if ( 'success' == response ) {
			
			// add the message to contact support
			jQuery( '#bkap_result' ).html( bkap_update_params.success_msg );
			jQuery( '#bkap_result' ).css( 'display', 'block' );
			jQuery( '#bkap_result' ).css( 'color', 'green' );
			jQuery( '#bkap_result' ).fadeOut( 5000 );
			// take them to the settings page
		/*	var url = bkap_update_params.settings_url;
			window.location.href = url; */
			
		} else if ( 'fail' == response ) {
			
			// add the message to contact support
			jQuery( '#bkap_result' ).html( bkap_update_params.support_request );
			jQuery( '#bkap_result' ).css( 'display', 'block' );
			jQuery( '#bkap_result' ).fadeOut( 5000 );
		}
	});
	
});