<?php

/**
 * Bookings and Appointment Plugin for WooCommerce
 *
 * Modal Popup template for allowing to edit Booking
 *
 * @author      Tyche Softwares
 * @package     Bookings and Appointment Plugin
 */ 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div id="bkap_edit_modal_<?php echo $bkap_cart_item_key; ?>" class="bkap-modal">

	<!-- Save Progress Loader -->
	<div id="bkap_save" class="bkap_save"></div>

	<!-- Modal content -->
	<div class="bkap-booking-contents">

		<div class="bkap-booking-header">

			<div class="bkap-header-title">
				<h1 class="product_title entry-title">
					<?php echo $product_obj->get_name() . " - " . __( "Edit Bookings", 'woocommerce-booking' );?>
				</h1>
			</div>
			<div class="bkap-header-close" onclick='bkap_edit_booking_class.bkap_close_popup(<?php echo $product_id; ?>, "<?php echo $bkap_cart_item_key; ?>")'>
			</div>

		</div>

		<div style="clear: both;"></div>

		<div id="modal-body-<?php echo $bkap_cart_item_key; ?>" class="modal-body">
			
			<?php 

				woocommerce_booking::include_frontend_scripts_js( $product_id );
				woocommerce_booking::inlcude_frontend_scripts_css( $product_id );

				$duplicate_of     =   bkap_common::bkap_get_product_id( $product_id );
				$booking_settings = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
				$booking_settings_new = bkap_get_post_meta( $duplicate_of );
				$global_settings  = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
				$product_type = $product_obj->get_type();
		
				$bookable = bkap_common::bkap_get_bookable_status( $duplicate_of );
				
				if ( ! $bookable ) {
					return;
				}

				$hidden_dates = bkap_booking_process::bkap_localize_process_script( $product_id );

				$hidden_dates['hidden_date'] = $bkap_booking['hidden_date'];

				if ( isset( $bkap_booking['hidden_date_checkout'] ) ) {
					$hidden_dates['hidden_checkout'] = $bkap_booking['hidden_date_checkout'];
				}
				
				wc_get_template( 
					'bookings/bkap-bookings-box.php', 
					array(
						'product_id'		=> $duplicate_of,
						'product_obj'		=> $product_obj,
						'booking_settings' 	=> $booking_settings,
						'global_settings'	=> $global_settings,
						'hidden_dates'      => $hidden_dates ), 
					'woocommerce-booking/', 
					BKAP_BOOKINGS_TEMPLATE_PATH );

			?>

			<span id="bkap_price" class="price bkap_modal_price"></span>
			<input type="hidden" class="variation_id" value="<?php echo $variation_id; ?>" />
			
			<!-- When Editing Bookings with Resource -->
			<?php if ( isset( $bkap_booking['resource_id'] ) && $bkap_booking['resource_id'] != 0 ) : ?>

				<div class="resource_id_container">
					<input type="hidden" name="chosen_resource_id" id="chosen_resource_id" class="rform_hidden" value="<?php echo $bkap_booking['resource_id'];?>">
				</div>

			<?php endif; ?>

			<!-- When Editing Bookings with Fixed Blocks -->
			<?php if ( isset( $bkap_booking['fixed_block'] ) && $bkap_booking['fixed_block'] != "" ) : ?>

				<div class="fixed_block_container">
					<input type="hidden" name="chosen_fixed_block" id="chosen_fixed_block" class="rform_hidden" value="<?php echo $bkap_booking['fixed_block'];?>">
				</div>

			<?php endif; ?>

			<!-- When Editing Bookings with Gravity Forms -->
			<?php if ( isset( $bkap_addon_data['gf_options'] ) && $bkap_addon_data['gf_options'] !== '' ) : ?>

				<div class="ginput_container_total">
					<input type="hidden" name="gravity_forms_options" id="gravity_forms_options" class="gform_hidden" value="<?php echo $bkap_addon_data['gf_options'];?>">
				</div>

			<?php endif; ?>

			<!-- When Editing Bookings with Product Addons -->
			<?php if ( isset( $bkap_addon_data['wpa_options'] ) && $bkap_addon_data['wpa_options'] !== '' ) : ?>

				<div id="product-addons-total" data-show-grand-total="1" data-type="simple" data-price="" data-raw-price="" data-addons-price="<?php echo $bkap_addon_data['wpa_options'];?>"></div>

			<?php endif; ?>

		</div>

		<div class="modal-footer">
			
			<input 
				type="button" 
				name="confirm_bookings" 
				id="confirm_bookings_<?php echo $bkap_cart_item_key; ?>"
				onclick='bkap_edit_booking_class.bkap_confirm_booking(<?php echo $product_id; ?>, "<?php echo $bkap_cart_item_key; ?>")'
				value="<?php _e( "Confirm Bookings", 'woocommerce-booking' ); ?>" 
				class="bkap_modal_button_class" 
			/>

			<input 
				type="button" 
				name="cancel_modal" 
				id="cancel_modal"
				onclick='bkap_edit_booking_class.bkap_close_popup(<?php echo $product_id; ?>, "<?php echo $bkap_cart_item_key; ?>")'
				value="<?php _e( "Cancel", 'woocommerce-booking' ); ?>" 
				class="bkap_modal_button_class"
			/>
		</div>

	</div>

</div>