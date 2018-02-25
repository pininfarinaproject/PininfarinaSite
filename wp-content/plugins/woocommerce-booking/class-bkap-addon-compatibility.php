<?php

/**
 * Bookings and Appointment Plugin for WooCommerce
 * 
 * Class for making Booking and Appointment compatible with WooCommerce Composites plugin
 *
 * @author	Tyche Softwares
 * @package	Bookings and Appointment Plugin
 */

if( ! class_exists( 'bkap_addon_compatibility_class' ) ) {

	/**
	* bkap_addon_compatibility_class
	*/
	class bkap_addon_compatibility_class {
		
		function __construct() {
			
			add_action( 'woocommerce_before_add_to_cart_button', array( &$this, 'bkap_composites_before_cart_button' ) );

			add_action( 'woocommerce_checkout_create_order_line_item', array( &$this, 'bkap_add_wpa_prices' ), 10, 3 );

			add_filter( 'bkap_cart_allow_add_bookings', array( &$this, 'bkap_allow_composite_parent' ), 10, 2 );
			add_filter( 'bkap_cart_modify_meta', array( &$this, 'bkap_add_composite_child_meta' ), 10, 1 );
		}

		/**
		 * Hook woocommerce_before_add_to_cart_form not available for Composite product type. 
		 * Hence hide Buttons and Quantity from here
		 * 
		 * @since 4.2
		 */
		public function bkap_composites_before_cart_button() {

			global $post,$wpdb;
		
			$product_id = bkap_common::bkap_get_product_id( $post->ID );
			$booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
			
			if ( $booking_settings == "" || ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] != "on" ) ) {
				return;
			}
			
			$product = wc_get_product( $product_id );
			$product_type = $product->get_type();

			if( $product_type === 'composite' &&
				$booking_settings != '' && 
				( isset( $booking_settings['booking_enable_date'] ) && 
				$booking_settings['booking_enable_date'] == 'on') && 
				( isset( $booking_settings['booking_purchase_without_date'] ) && 
				$booking_settings['booking_purchase_without_date'] != 'on') ) {
			
				// check the setting
				$global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
				if ( isset( $global_settings->display_disabled_buttons ) && 'on' == $global_settings->display_disabled_buttons ) {
					?>
					<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
							jQuery( ".qty" ).prop( "disabled", true );
						});
						
					</script>
					<?php 
				} else {
				?>
					<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery( ".single_add_to_cart_button" ).hide();
							jQuery( ".qty" ).hide();
						});
					</script>
				<?php 
				}
				?>
					<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery( ".payment_type" ).hide();
							jQuery(".partial_message").hide();
						});
					</script>
				<?php 

				bkap_booking_process::bkap_price_display();
			}
		}

		public function bkap_add_wpa_prices( $item, $cart_item_key, $values ) {
			
			if ( isset( $values['bkap_booking'] ) && isset( $values['addons'] ) && count( $values['addons'] ) > 0 ) {
				$wpa_total = bkap_common::bkap_get_wpa_cart_totals( $values );
				$item->add_meta_data( '_wapbk_wpa_prices', $wpa_total );
			}
		}

		/**
		 * Allow only composite Parent Product to add Booking Details as it is
		 * 
		 * @param bool $add_details Boolean value depending on state to allow or disallow
		 * @param array $cart_item_meta Cart Item Meta
		 * @return bool Boolean on whether to allow or disallow
		 * 
		 * @since 4.7.0
		 */
		public function bkap_allow_composite_parent( $add_details, $cart_item_meta ) {
			
			if ( !array_key_exists( 'composite_parent', $cart_item_meta ) ) {
				return true;
			}else if ( array_key_exists( 'composite_parent', $cart_item_meta ) ) {
				return false;
			}else {
				return true;
			}
		}

		/**
		 * Add Booking Data to cart item meta for composite products
		 * 
		 * @param array $cart_item_meta Cart Item Meta
		 * @return array Cart Item Meta Array with modified data
		 * @since 4.7.0
		 */
		public function bkap_add_composite_child_meta( $cart_item_meta ) {
			
			if ( array_key_exists( 'composite_parent', $cart_item_meta ) && $cart_item_meta['composite_parent'] !== '' ) {
				
				$cart_arr = array();

				if ( isset( WC()->cart->cart_contents[$cart_item_meta['composite_parent']]['bkap_booking'] ) ) {
					$composite_parent_booking = WC()->cart->cart_contents[$cart_item_meta['composite_parent']]['bkap_booking'][0];
				}

				$parent_product = WC()->cart->cart_contents[$cart_item_meta['composite_parent']]['data'];
				$component_data = $parent_product->get_component_data( $cart_item_meta['composite_item'] );

				$composite_data = $cart_item_meta['composite_data'][$cart_item_meta['composite_item']];

				if ( isset( $composite_data['product_id'] ) && $composite_data['product_id'] !== '' ) {
					$composite_product = wc_get_product( $composite_data['product_id'] );
				}

				if ( isset( $component_data['priced_individually'] ) && 'yes' === $component_data['priced_individually'] ) {
					if ( isset( $composite_data['variation_id'] ) && $composite_data['variation_id'] !== '' ) {
						$composite_variation = wc_get_product( $composite_data['variation_id'] );
						$cart_arr['price'] = $composite_variation->get_price();
						if ( isset( $composite_data['discount'] ) && $composite_data['discount'] !== '' ) {
							$cart_arr['price'] = $cart_arr['price'] - ( $cart_arr['price'] * $composite_data['discount']/100 );
						}

						if ( isset( $_POST['wapbk_diff_days'] ) ) {
							$cart_arr['price'] = $cart_arr['price'] * $_POST['wapbk_diff_days'];
						}
					}else{
						if ( isset( $_POST['wapbk_diff_days'] ) && $_POST['wapbk_diff_days'] > 0 ) {
							$cart_arr['price'] = $composite_product->get_price() * $_POST['wapbk_diff_days'];
						}else{
							$cart_arr['price'] = $composite_product->get_price();
						}
					}
				}

				$duplicate_of = bkap_common::bkap_get_product_id( $composite_data['product_id'] );

				$is_bookable = bkap_common::bkap_get_bookable_status( $duplicate_of );

				if ( $is_bookable && isset( $composite_parent_booking ) ) {
					$cart_arr['date'] = $composite_parent_booking['date'];
					$cart_arr['hidden_date'] = $composite_parent_booking['hidden_date'];
					$cart_arr['date_checkout'] = $composite_parent_booking['date_checkout'];
					$cart_arr['hidden_date_checkout'] = $composite_parent_booking['hidden_date_checkout'];

					if ( isset( $composite_parent_booking['time_slot'] ) ) {
						$cart_arr['time_slot'] = $composite_parent_booking['time_slot'];
					}
				}

				if ( isset( $cart_arr['date'] ) || isset( $cart_arr['price'] ) ) {
					$cart_item_meta['bkap_booking'][] = $cart_arr;
				}
			}

			return $cart_item_meta;
		}
	}
}

$bkap_addon_compatibility_class = new bkap_addon_compatibility_class();