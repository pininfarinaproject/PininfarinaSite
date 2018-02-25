jQuery( document ).ready( function() {
	
	jQuery( '#product_save_button,#draft_button' ).one( 'click', function( event ) {
		event.preventDefault();
		
		booking_options = bkap_get_general_tab_data();
		settings_data = bkap_get_availability_data();
		gcal_settings = bkap_get_gcal_data();
		fixed_block_data  = bkap_fixed_block_data();
		price_range_data  = bkap_price_range_data();
    
		var fixed_blocks_enable = '';
		var price_ranges_enable = '';
		var block_price         = '';
		var block_price     = jQuery( 'input:radio[name=bkap_enable_block_pricing_type]:checked').val();

		if( block_price != undefined ){
			if ( block_price.length > 0 && block_price == "booking_block_price_enable" ) {
				price_ranges_enable = block_price;
			}
  
			if ( block_price.length > 0 && block_price == "booking_fixed_block_enable" ) {
				fixed_blocks_enable = block_price;
			}
		}
		
		// setup the data
		var data = {
			booking_options: JSON.stringify( booking_options ),
			settings_data: JSON.stringify( settings_data ),
			gcal_data: JSON.stringify( gcal_settings ),
			ranges_enabled: price_ranges_enable,
			blocks_enabled: fixed_blocks_enable,
			fixed_block_data: JSON.stringify( fixed_block_data ),
			price_range_data: price_range_data,
			product_id: bkap_wcv_params.post_id,
			action: 'bkap_save_settings'
		};
              
		jQuery.post( bkap_wcv_params.ajax_url, data, function(response) { 
			jQuery( '#product_save_button,#draft_button' ).trigger( 'click' );
		});
	});
});