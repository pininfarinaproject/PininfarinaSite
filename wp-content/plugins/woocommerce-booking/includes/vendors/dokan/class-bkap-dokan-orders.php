<?php

/**
 * Bookings and Appointment Plugin for WooCommerce
 *
 * Class for integrating Dokan with Bookings & Appointment Plugin
 *
 * @author      Tyche Softwares
 * @package     Bookings and Appointment Plugin
 */

if( ! class_exists( 'bkap_dokan_orders_class' ) ) {

	/**
	* Class for Integrating Orders with Dokan
	*/
	class bkap_dokan_orders_class {
		
		function __construct() {

			add_action( 'bkap_dokan_booking_content_before', array( &$this, 'bkap_dokan_include_booking_styles' ) );

			add_action( 'bkap_dokan_booking_inside_before', array( &$this, 'bkap_booking_menu_tabs' ), 10, 2 );
			add_action( 'bkap_dokan_booking_inside_content', array( &$this, 'bkap_load_related_template' ), 10, 2 );

			add_filter( 'bkap_dokan_booking_status', array( &$this, 'bkap_show_booking_status' ), 10, 1 );

			add_action( 'bkap_dokan_booking_list', array( &$this, 'bkap_load_view_template' ), 10, 2 );

			add_action( 'wp', array( &$this, 'bkap_download_booking_files' ) );

			add_action( 'wp_ajax_nopriv_bkap_dokan_change_status', array( &$this, 'bkap_dokan_change_status' ) );
			add_action( 'wp_ajax_bkap_dokan_change_status', array( &$this, 'bkap_dokan_change_status' ) );

			add_filter( 'bkap_display_multiple_modals', array( &$this, 'bkap_dokan_load_modals' ) );

			add_action( 'dokan_dashboard_content_before', array( &$this, 'bkap_load_order_scripts' ) );
		}

		/**
		 * Load common styles before rendering templates
		 * 
		 * @since 4.6.0
		 */
		public function bkap_dokan_include_booking_styles() {

			$plugin_version = get_option( 'woocommerce_booking_db_version' );
			bkap_load_scripts_class::bkap_load_dokan_booking_styles( $plugin_version );
		}

		/**
		 * Display Booking Menu Tabs
		 * 
		 * @param string $current_page Current Page active
		 * @param string $bkap_url Booking Tab URL
		 * @since 4.6.0
		 */
		public function bkap_booking_menu_tabs( $current_page, $bkap_url ) {

			$bkap_menus = array(
				""               => array(
					'title' => __( 'View Bookings', 'woocommerce-booking' ),
					'tabs'  => true
				),
				"bkap_calendar"  => array(
					'title' => __( 'Calendar View', 'woocommerce-booking' ),
					'tabs'  => true
				),
				"bkap_resources" => array(
					'title' => __( 'Resources', 'woocommerce-booking' ),
					'tabs'  => false
				),
				"bkap_csv"       => array(
					'title' => __( 'CSV', 'woocommerce-booking' ),
					'tabs'  => false
				),
				"bkap_print"     => array(
					'title' => __( 'CSV', 'woocommerce-booking' ),
					'tabs'  => false
				)
			);

			$bkap_menus = apply_filters( 'bkap_dokan_nav_tabs', $bkap_menus );

			wc_get_template( 
				'dokan/bkap-dokan-booking-tabs.php', 
				array(
					'bkap_menus' => $bkap_menus,
					'bkap_url' => $bkap_url,
					'current_page' => $current_page ), 
				'woocommerce-booking/', 
				BKAP_VENDORS_TEMPLATE_PATH );
		}

		/**
		 * Load Template based on the page opened in Bookings Menu
		 * 
		 * @param string $current_page Current Page active
		 * @param string $bkap_url Booking Tab URL
		 * @since 4.6.0
		 */
		public function bkap_load_related_template( $current_page, $bkap_url ) {

			switch ( $current_page ) {
				case '':
					wc_get_template( 
						'dokan/bkap-dokan-view-booking.php', 
						array( ), 
						'woocommerce-booking/', 
						BKAP_VENDORS_TEMPLATE_PATH );
					break;

				case 'bkap_calendar':
					wc_get_template( 
						'dokan/bkap-dokan-view-calendar.php', 
						array( ), 
						'woocommerce-booking/', 
						BKAP_VENDORS_TEMPLATE_PATH );
					break;

				case 'bkap_resources':
					wc_get_template( 
						'dokan/bkap-dokan-view-resources.php', 
						array( ), 
						'woocommerce-booking/', 
						BKAP_VENDORS_TEMPLATE_PATH );
					break;

				default:
					wc_get_template( 
						'dokan/bkap-dokan-view-booking.php', 
						array( ), 
						'woocommerce-booking/', 
						BKAP_VENDORS_TEMPLATE_PATH );
					break;
			}
		}

		/**
		 * Show Booking status as an icon on vendor dashboard
		 * 
		 * @param string $status Status string
		 * @return string HTML formatted string to be displayed in status column
		 * 
		 * @since 4.6.0
		 */
		public function bkap_show_booking_status( $status ) {

			$booking_statuses = bkap_common::get_bkap_booking_statuses();
			$status_label = ( array_key_exists( $status, $booking_statuses ) ) ? $booking_statuses[ $status ] : ucwords( $status );
			return '<span class="bkap_dokan_status status-' . esc_attr( $status ) . ' tips" data-toggle="tooltip" data-placement="top" title="' . esc_attr( $status_label ) . '">' . esc_html( $status_label ) . '</span>';
		}

		/**
		 * Download CSV and Print files
		 * 
		 * @since 4.6.0
		 */
		public function bkap_download_booking_files() {
			
			$current_page = get_query_var( 'bkap_dokan_booking' );
			$additional_args = array(
				'meta_key'   => '_bkap_vendor_id',
				'meta_value' => get_current_user_id() );
			$data = bkap_common::bkap_get_bookings( '', $additional_args );

			if ( isset( $current_page ) && $current_page === 'bkap_csv' ) {
				BKAP_Bookings_View::bkap_download_csv_file( $data );
			}elseif ( isset( $current_page ) && $current_page === 'bkap_print' ) {
				BKAP_Bookings_View::bkap_download_print_file( $data );
			}
		}

		/**
		 * Load Booking Template for Editing Bookings for Vendor
		 * 
		 * @param string|int $booking_id Booking ID
		 * @param array $booking_post Post data containing Booking Information
		 * 
		 * @since 4.6.0
		 */
		public static function bkap_load_view_template( $booking_id, $booking_post ) {

			$product_id = $booking_post['product_id'];

			$variation_id = '';

			$page_type = 'view-order';

			$booking_details = array(
				'date'                 => $booking_post['start'],
				'hidden_date'          => $booking_post['hidden_start'],
				'date_checkout'        => $booking_post['end'],
				'hidden_date_checkout' => $booking_post['hidden_end'],
				'price'                => $booking_post['amount'],
			);

			if( isset( $booking_post['time_slot'] ) ) {
				$booking_details['time_slot'] = $booking_post['time_slot'];
			}

			$localized_array = array( 
				'bkap_booking_params' => $booking_details,
				'bkap_cart_item' => '',
				'bkap_cart_item_key' => $booking_post['order_item_id'],
				'bkap_order_id' => $booking_post['order_id'],
				'bkap_page_type' => $page_type
			);

			// Additional data for addons
			$additional_addon_data = '';//bkap_common::bkap_get_cart_item_addon_data( $cart_item );

			bkap_edit_bookings_class::bkap_load_template( 
				$booking_details, 
				wc_get_product( $product_id ), 
				$product_id, 
				$localized_array,
				$booking_post['order_item_id'],
				$variation_id,//$booking_post['variation_id'],
				$additional_addon_data );

			wp_register_script( 
				'bkap-dokan-reschedule-booking', 
				plugins_url().'/woocommerce-booking/js/vendors/dokan/bkap-dokan-reschedule-booking.js', 
				'', 
				'', 
				true );

			wp_enqueue_script( 'bkap-dokan-reschedule-booking' );
		}

		/**
		 * Change Booking Status from View Bookings for Vendor Dashboard
		 * 
		 * @since 4.6.0
		 */
		public function bkap_dokan_change_status() {
			
			$item_id = $_POST[ 'item_id' ];
			$status = $_POST[ 'status' ];
			bkap_booking_confirmation::bkap_save_booking_status( $item_id, $status );
			die();
		}

		/**
		 * Enable Global Params to be set for Modals to load on View Bookings
		 * 
		 * @param bool $display Status indicating presence of multiple products for booking
		 * @return bool True if multiple products present
		 * @since 4.6.0
		 */
		public function bkap_dokan_load_modals( $display ) {

			if ( function_exists( 'dokan_is_seller_dashboard' ) && dokan_is_seller_dashboard() ) {
				return $display = true;
			}else {
				return $display;
			}
		}

		/**
		 * Load CSS file for Edit Orders Page
		 * 
		 * @since 4.6.0
		 */
		public function bkap_load_order_scripts() {

			if ( dokan_is_seller_dashboard() && isset( $_GET[ 'order_id' ] ) ) {
				wp_enqueue_style( 
					'bkap_dokan_orders', 
					plugins_url().'/woocommerce-booking/css/vendors/dokan/bkap-dokan-view-order.css', 
					'', 
					'', 
					false );
			}
		}
	}
}

return new bkap_dokan_orders_class();