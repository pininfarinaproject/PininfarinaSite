<?php

/**
 * Bookings and Appointment Plugin for WooCommerce
 *
 * Create related orders for Rescheduled Bookings
 *
 * @author      Tyche Softwares
 * @package     Bookings and Appointment Plugin
 */

if( ! class_exists( 'bkap_rescheduled_order_class' ) ) {

	/**
	* Class for creating related orders for rescheduled bookings
	*/
	class bkap_rescheduled_order_class
	{
		
		function __construct() {

			add_filter( 'woocommerce_hidden_order_itemmeta', array( &$this, 'bkap_rescheduled_hidden_order_itemmeta'), 10, 1 );
			add_action( 'woocommerce_after_order_itemmeta', array( &$this, 'bkap_button_after_order_meta' ), 10, 3 );
		}

		public function bkap_rescheduled_hidden_order_itemmeta( $meta_keys ) {
			
			$meta_keys[] = '_bkap_resch_orig_order_id';
			$meta_keys[] = '_bkap_resch_rem_bal_order_id';

			return $meta_keys;
		}

		/**
		 * Add meta box on order page to display related order
		 * 
		 * @since 4.2.0
		 */
		public static function bkap_rescheduled_create_order( $original_order_id, $item ) {
			
			$original_order = wc_get_order( $original_order_id );
			$new_remaining_order = wc_create_order( array( 
				'status'        => 'wc-pending',
				'customer_id'   => $original_order->get_user_id(),
			));

			$new_remaining_order->set_address( array(
				'first_name'	=> $original_order->get_billing_first_name(),
				'last_name'		=> $original_order->get_billing_last_name(),
				'company'		=> $original_order->get_billing_company(),
				'address_1'		=> $original_order->get_billing_address_1(),
				'address_2'		=> $original_order->get_billing_address_2(),
				'city'			=> $original_order->get_billing_city(),
				'state'			=> $original_order->get_billing_state(),
				'postcode'		=> $original_order->get_billing_postcode(),
				'country'		=> $original_order->get_billing_country(),
				'email'			=> $original_order->get_billing_email(),
				'phone'			=> $original_order->get_billing_phone()
			) );

			$new_remaining_order->set_address( array(
				'first_name'	=> $original_order->get_shipping_first_name(),
				'last_name'		=> $original_order->get_shipping_last_name(),
				'company'		=> $original_order->get_shipping_company(),
				'address_1'		=> $original_order->get_shipping_address_1(),
				'address_2'		=> $original_order->get_shipping_address_2(),
				'city'			=> $original_order->get_shipping_city(),
				'state'			=> $original_order->get_shipping_state(),
				'postcode'		=> $original_order->get_shipping_postcode(),
				'country'		=> $original_order->get_shipping_country(),
			) );

			$item_id = $new_remaining_order->add_product( $item['product'], $item['qty'], array(
				'totals' => array(
					'subtotal'	=> $item['amount'],
					'total'		=> $item['amount']
				)
			));

			wc_update_order_item_meta( $item_id, '_bkap_resch_orig_order_id', $original_order_id, '' );
			wc_update_order_item( $item_id, array( 'order_item_name' => sprintf( __( 'Additional Payment for %s (Order #%d )' ) ,$item['product']->get_title(), $original_order_id ) ) );

			$new_remaining_order->calculate_totals();

			$new_remaining_order_post = array (
				'ID'			=> $new_remaining_order->get_id(),
				'post_date'		=> date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
				'post_parent'	=> $original_order_id
			);

			wp_update_post( $new_remaining_order_post );

			return $new_remaining_order->get_id();
		}

		/**
		 * Add link for related order in admin side for rescheduled orders where there is an additional payment
		 */
		public function bkap_button_after_order_meta( $item_id, $item, $product ){

			if ( $item['_bkap_resch_rem_bal_order_id'] !== '' && $item['_bkap_resch_rem_bal_order_id'] !== null ){
				?>
					<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $item['_bkap_resch_rem_bal_order_id'] . '&action=edit' ) ); ?>" class="button button-small">
						<?php _e( 'Related Order', 'woocommerce-booking' ); ?>
					</a>
				<?php
			}elseif ( $item['_bkap_resch_orig_order_id'] !== '' && $item['_bkap_resch_orig_order_id'] !== null ) {
				?>
					<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $item['_bkap_resch_orig_order_id'] . '&action=edit' ) ); ?>" class="button button-small">
						<?php _e( 'Parent Order', 'woocommerce-booking' ); ?>
					</a>
				<?php
			}
		}
	}
}

$bkap_rescheduled_order_class = new bkap_rescheduled_order_class();