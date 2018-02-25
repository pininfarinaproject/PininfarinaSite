jQuery(document).ready(function($){

	$( 'input[name=dokan_update_product]' ).one( 'click', function( event ) {
		event.preventDefault();
	
		var data = bkap_save_product_settings();

		jQuery.post( bkap_settings_params.ajax_url, data, function(response) {  
			jQuery( 'input[name=dokan_update_product]' ).trigger( "click" );
		});
	});
});