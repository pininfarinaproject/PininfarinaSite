<?php

/**
 * Bookings and Appointment Plugin for WooCommerce
 *
 * Class for integrating Dokan with Bookings & Appointment Plugin
 *
 * @author      Tyche Softwares
 * @package     Bookings and Appointment Plugin
 */

if( ! class_exists( 'bkap_dokan_class' ) ) {

	/**
	* Class for Integrating Booking & Appointment plugin for WooCommerce with Dokan
	*/
	class bkap_dokan_class {
		
		function __construct() {

			self::bkap_dokan_include_dependencies();

			add_filter( 'dokan_get_dashboard_nav',    array( &$this, 'bkap_dokan_add_booking_nav' ) );
			
			add_filter( 'dokan_query_var_filter',     array( &$this, 'bkap_dokan_query_var_filter' ) );

			add_action( 'dokan_rewrite_rules_loaded', array( $this, 'bkap_add_rewrite_rules' ) );

			add_action( 'dokan_load_custom_template', array( &$this, 'bkap_dokan_include_template' ), 10, 1 );

			add_action( 'admin_init', array( &$this, 'bkap_remove_menu' ) );
		}

		/**
		 * Remove Booking Menu for Vendors Admin Dashboard
		 * 
		 * @since 4.6.0
		 */
		public function bkap_remove_menu() {

			if ( current_user_can( 'seller' ) ) {
				remove_menu_page( 'edit.php?post_type=bkap_booking' );
			}
		}

		/**
		 * Include dependent files
		 * 
		 * @since 4.6.0
		 */
		public static function bkap_dokan_include_dependencies() {

			include_once( BKAP_VENDORS_INCLUDES_PATH . 'dokan/class-bkap-dokan-products.php' );
			include_once( BKAP_VENDORS_INCLUDES_PATH . 'dokan/class-bkap-dokan-orders.php' );
			include_once( BKAP_VENDORS_INCLUDES_PATH . 'dokan/class-bkap-dokan-calendar.php' );
		}

		/**
		 * Add Booking Menu to vendors Dashboard on Frontend
		 * 
		 * @param array $urls Array containing existing Menu URLs
		 * @return array URL Array
		 * @since 4.6.0
		 */
		function bkap_dokan_add_booking_nav( $urls ) {

			$urls['bkap_dokan_booking'] = array(
				'title' => __( 'Booking', 'woocommerce-booking'),
				'icon'  => '<i class="wp-menu-image dashicons-before dashicons-calendar-alt"></i>',
				'url'   => dokan_get_navigation_url( 'bkap_dokan_booking' ),
				'pos'   => '51'
			);

			return $urls;
		}

		/**
		 * Add Booking Query var to the existing query vars
		 * 
		 * @param array $url_array Array of URL
		 * @return array Array of URL after modification
		 * @since 4.6.0
		 */
		public function bkap_dokan_query_var_filter( $url_array ) {
			$url_array[] = 'bkap_dokan_booking';

			return $url_array;
		}

		/**
		 * Add rewrite rules for Booking Links
		 * 
		 * @since 4.6.0
		 */
		public function bkap_add_rewrite_rules() {
			flush_rewrite_rules( true );
		}

		/**
		 * Display the base template for Booking Menu
		 * 
		 * @param array $query_vars Query Vars
		 * @since 4.6.0
		 */
		public function bkap_dokan_include_template( $query_vars ) {

			if ( isset( $query_vars['bkap_dokan_booking'] ) ) {
				wc_get_template( 
					'dokan/bkap-dokan-booking.php', 
					array( ), 
					'woocommerce-booking/', 
					BKAP_VENDORS_TEMPLATE_PATH );
			}
		}
	}
}

return new bkap_dokan_class();