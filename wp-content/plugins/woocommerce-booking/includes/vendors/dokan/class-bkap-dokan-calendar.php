<?php

/**
 * Bookings and Appointment Plugin for WooCommerce
 *
 * Class for Calendar View for Vendor Bookings
 *
 * @author      Tyche Softwares
 * @package     Bookings and Appointment Plugin
 */

if( ! class_exists( 'bkap_dokan_calendar_class' ) ) {

	/**
	* bkap_dokan_calendar_class
	*/
	class bkap_dokan_calendar_class {
		
		function __construct() {

			$this->plugin_version = get_option( 'woocommerce_booking_db_version' );

			add_action( 'bkap_dokan_booking_content_before', array( &$this, 'bkap_dokan_include_calendar_styles' ) );

			add_action( 'bkap_dokan_booking_calendar_after', array( &$this, 'bkap_dokan_include_calendar_scripts' ) );
		}

		public function bkap_dokan_include_calendar_styles() {
			
			bkap_load_scripts_class::bkap_load_products_css( $this->plugin_version );
			bkap_load_scripts_class::bkap_load_calendar_styles( $this->plugin_version );
		}

		public function bkap_dokan_include_calendar_scripts() {
			
			bkap_load_scripts_class::bkap_load_calendar_scripts( $this->plugin_version, get_current_user_id() );
		}
	}
}

return new bkap_dokan_calendar_class();