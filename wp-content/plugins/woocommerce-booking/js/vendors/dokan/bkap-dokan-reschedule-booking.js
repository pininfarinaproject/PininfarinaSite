/**
 * Class for View Bookings (Dokan)
 * @namespace bkap_dokan_class
 * @since 4.6.0
 */
var bkap_dokan_class = (function( $ ){

	return {

		bkap_dokan_view_booking: function( product_id, booking_id ){
			bkap_edit_booking_class.bkap_edit_bookings( product_id, booking_id );
		},

		bkap_dokan_change_status: function( booking_item_id, status ){

			var data = {
				item_id: booking_item_id,
				status : status,
				action : 'bkap_dokan_change_status'
			}

			$.post( 
				dokan.ajaxurl, 
				data, 
				function( response ) {
					window.location.reload();
				}
			).fail(function() {
				window.location.reload();
			});
		}

	};

})( jQuery );