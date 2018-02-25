/**
 * JS code for manipulating bookings to be added via OPC
 * @since 4.6.0
 */
jQuery(document).ready(function($){

	/**
	 * Enable popup and show add to cart button and disable confirm booking button
	 *
	 * @fires event:bkap_edit_popup_enabled
	 * @param {object} e - Event
	 * @param {string} modal_id - Modal ID of the product to be added to cart along with Booking
	 * @param {string} product_id - Product ID of the Booking to be added
	 * @since 4.6.0
	 */
	$( 'body' ).on( 'bkap_edit_popup_enabled', function( e, product_id, modal_id ) {

		var modal_object   = $( '#bkap_edit_modal_' + modal_id ),
			confirm_button = modal_object.find( '#confirm_bookings_' + modal_id );
			add_button     = modal_object.find( '#bkap_opc_add_booking_' + modal_id );

		if( add_button.length === 0 ){

			confirm_button.after( 
				'<input '+
					'type="button" ' + 
					'name="bkap_opc_add_booking" ' + 
					'id="bkap_opc_add_booking_' + modal_id +'" ' + 
					'onclick="bkap_opc_add_booking_event( this, event )" ' + 
					'value="Add Booking" ' + 
					'data-add_to_cart="' + modal_id + '"' +
					'class="bkap_modal_button_class bkap_opc_add_booking" disabled="disabled">' );
			confirm_button.hide();
		}
	});

	/**
	 * Event when Price is updated in the template
	 *
	 * @fires event:bkap_price_updated
	 * @since 4.7.0
	 */
	$( 'body' ).on( 'bkap_price_updated', function( e, add_button_id ){

		var add_button = '#bkap_opc_add_booking_' + add_button_id;

		var time_slot = $( '#modal-body-'+add_button_id ).find( '#time_slot' );
		if( time_slot.length > 0 ) {
			if ( $( time_slot ).val() ){
				$( add_button ).prop( 'disabled', false );
			}else{
				$( add_button ).prop( 'disabled', true );
			}
		}else{
			$( add_button ).prop( 'disabled', false );
		}
	});

	/**
	 * Close pop up once booking is added.
	 *
	 * @fires event:after_opc_add_remove_product
	 * @param {object} data - Cart Data
	 * @param {object} response - Response received on addition of Product
	 * @since 4.6.0
	 */
	$( 'body' ).on( 'after_opc_add_remove_product',function( data, response ) {

		if ( response['action'] === 'pp_add_to_cart' ) {
			setTimeout(function(){
				$( '.bkap-modal' ).css({ 'display' : 'none' });
			}, 1000);
		}

		if ( response['action'] === 'pp_update_add_in_cart' && $( '#bkap_edit_modal_' + response['add_to_cart'] ).length > 0 ) {

			bkap_edit_booking_class.bkap_close_popup( response['add_to_cart'], response['add_to_cart'] );
		}
	});
	
});

/**
 * Add products along with booking to cart
 *
 * @param {object} inst - Button instance
 * @param {object} e - Button Click event
 * @since 4.6.0
 */
function bkap_opc_add_booking_event( inst, e ) {

	var input = jQuery(inst),
		selectors = '#opc-product-selection input[type="number"][data-add_to_cart]',
		response_messages = '',
		timeout,
		delay = 1000,
		quantity = '';

	clearTimeout(timeout);

	timeout = setTimeout(function() {

		if( input.hasClass( 'bkap_opc_add_booking' ) ){
			quantity = 1;
		}else {
			quantity = input.val();
		}

		var data = {
			quantity:    quantity,
			add_to_cart: parseInt( input.data( 'add_to_cart' ) ),
			input_data:  input.closest( '.product-quantity' ).find( 'input[name!="product_id"], select, textarea' ).serialize(),
			nonce:       wcopc.wcopc_nonce,
		}

		if ( data['quantity'] == 0 ) {
			data['action'] = 'pp_remove_from_cart';
		} else {
			data['action'] = 'pp_update_add_in_cart';
		}

		input.ajax_add_remove_product( data, e, selectors );

	}, delay );

	e.preventDefault();
}