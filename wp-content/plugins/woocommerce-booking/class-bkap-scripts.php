<?php

/**
 * Bookings and Appointment Plugin for WooCommerce
 *
 * Allow Bookings to be edited from Cart and Checkout Page
 *
 * @author      Tyche Softwares
 * @package     Bookings and Appointment Plugin
 */

if( ! class_exists( 'bkap_load_scripts_class' ) ) {

	/**
	* Load Scripts needed for Plugin
	*/
	class bkap_load_scripts_class {
		
		function __construct() {
			# code...
		}

		public static function bkap_common_admin_scripts_js( $plugin_version_number ) {

			wp_register_script( 
				'multiDatepicker', 
				plugins_url().'/woocommerce-booking/js/jquery-ui.multidatespicker.js', 
				'', 
				$plugin_version_number, 
				true );
			wp_enqueue_script( 'multiDatepicker' );

			wp_register_script( 
				'datepick', 
				plugins_url().'/woocommerce-booking/js/jquery.datepick.js', 
				'', 
				$plugin_version_number, 
				true );
			wp_enqueue_script( 'datepick' );

			wp_enqueue_script( 
				'bkap-tabsjquery', 
				plugins_url().'/woocommerce-booking/js/zozo.tabs.min.js', 
				'', 
				$plugin_version_number, 
				true );
		}

		public static function bkap_load_product_scripts_js( $plugin_version_number, $ajax_url ) {
			
			global $post;

			$post_id = $post->ID;

			wp_register_script( 
				'booking-meta-box', 
				plugins_url().'/woocommerce-booking/js/booking-meta-box.js', 
				'', 
				$plugin_version_number, 
				true );

			wp_localize_script( 
				'booking-meta-box', 
				'bkap_settings_params', 
				array(
					'ajax_url'                   => $ajax_url,
					'post_id'                    => $post_id,
					'specific_label'             => __( 'Specific Dates', 'woocommerce-booking' ),
					'general_update_msg'         => __( 'General Booking settings have been saved.', 'woocommerce-booking' ),
					'availability_update_msg'    => __( 'Booking Availability settings have been saved.', 'woocommerce-booking' ),
					'gcal_update_msg'            => __( 'Google Calendar Sync settings have been saved.', 'woocommerce-booking' ),
					'only_day_text'              => __( 'Use this for full day bookings or bookings spanning multiple nights.' , 'woocommerce-booking' ),
					'date_time_text'             => __( 'Use this if you wish to take bookings for fixed time slots. For e.g. coaching classes, appointments etc.', 'woocommerce-booking' ),
					'single_day_text'            => __( 'Use this to take bookings like single day tours, event, appointments etc.' , 'woocommerce-booking' ),
					'multiple_nights_text'       => __( 'Use this for hotel bookings, rentals, etc. Checkout date is not included in the booking period.', 'woocommerce-booking' ),
					'multiple_nights_price_text' => __( 'Please enter the per night price in the Regular or Sale Price box in the Product meta box as needed. In case if you wish to charge special prices for a weekday, please enter them above.', 'woocommerce-booking' ) 
				) 
			);

			// Messages for Block Pricing
			wp_localize_script( 
				'booking-meta-box', 
				'bkap_block_pricing_params', 
				array(
					'save_fixed_blocks'                 => __( 'Fixed Blocks have been saved.', 'woocommerce-booking' ),
					'delete_fixed_block'                => __( 'Fixed Block have been deleted.', 'woocommerce-booking' ),
					'delete_all_fixed_blocks'           => __( 'All Fixed Blocks have been deleted.', 'woocommerce-booking' ),
					'confirm_delete_fixed_block'        => __( 'Are you sure you want to delete this fixed block?', 'woocommerce-booking' ),
					'confirm_delete_all_fixed_blocks'   => __( 'Are you sure you want to delete all the blocks?', 'woocommerce-booking' ),
					
					'save_price_ranges'                 => __( 'Price ranges have been saved.', 'woocommerce-booking' ),
					'delete_price_range'                => __( 'Price Range have been deleted.', 'woocommerce-booking' ),
					'delete_all_price_ranges'           => __( 'All Price Ranges have been deleted.', 'woocommerce-booking' ),
					'confirm_delete_price_range'        => __( 'Are you sure you want to delete this price range?', 'woocommerce-booking' ),
					'confirm_delete_all_price_ranges'   => __( 'Are you sure you want to delete all the ranges?', 'woocommerce-booking' ), 
				) 
			);
			
			wp_enqueue_script( 'booking-meta-box' );

			wp_enqueue_script( 'jquery' );
			wp_deregister_script( 'jqueryui');

			wp_enqueue_script( 
				'bkap-jqueryui', 
				'//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js', 
				'', 
				$plugin_version_number, 
				true );
			
			wp_register_script( 
				'booking-time-slots-meta-box', 
				plugins_url().'/woocommerce-booking/js/booking-time-slots-meta-box.js', 
				'', 
				$plugin_version_number, 
				true );

			// Messages for loading time slots via ajax
			wp_localize_script( 'booking-time-slots-meta-box', 'bkap_time_slots_params', array(
				'ajax_url'                 => $ajax_url,
				'bkap_product_id'          => $post_id,
				'bkap_time_slots_per_page' => absint( apply_filters( 'bkap_time_slots_per_page', 15 ) ),
			) );

			wp_enqueue_script ( 'booking-time-slots-meta-box' );
		}

		public static function bkap_load_dokan_product_scripts_js( $plugin_version_number, $ajax_url ) {
			
			wp_register_script( 
				'bkap-dokan-product', 
				plugins_url().'/woocommerce-booking/js/vendors/dokan/bkap_dokan_product.js', 
				'', 
				$plugin_version_number, 
				true );

			wp_enqueue_script( 'bkap-dokan-product' );
		}


		/*
		*  Adding JS and CSS files required for Resource
		*/

		public static function bkap_load_resource_scripts_js( $plugin_version_number, $ajax_url ) {

			global $post;

			$post_id = $post->ID;

			$bkap_calendar_img = plugins_url() . "/woocommerce-booking/images/cal.gif";
    				 
			wp_register_script( 'bkap-resource',
								plugins_url().'/woocommerce-booking/js/bkap-resource.js', 
								array( 'jquery', 
										'jquery-ui-sortable', 
										'jquery-ui-datepicker' 
									 ), 
								$plugin_version_number, 
								true 
			);
				
			$args = array(
        				'ajax_url'                   => $ajax_url,
        				'post_id'                    => $post_id,
        				'bkap_calendar'				 => $bkap_calendar_img,
        				'delete_resource_conf'		 => __( 'Are you sure you want to delete this resource?' , 'woocommerce-booking' ),
        				'delete_resource'         	 => __( 'Resource have been deleted.', 'woocommerce-booking' ),
			);
				
			wp_localize_script( 'bkap-resource', 'bkap_resource_params', $args );

			wp_localize_script( 'booking-meta-box', 'bkap_resource_params', $args );

			wp_enqueue_script( 'bkap-resource' );
			
			wp_enqueue_style( 'bkap-resource-css', 
								plugins_url( '/css/bkap-resource-css.css', __FILE__ ),
								'',
								$plugin_version_number,
								false 
			);
		}

		public static function bkap_load_calendar_scripts( $plugin_version_number, $vendor_id = '' ) {

			wp_enqueue_script( 'jquery' );
			wp_deregister_script( 'jqueryui');

			wp_enqueue_script( 
				'bkap-jqueryui', 
				'//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js', 
				'', 
				$plugin_version_number, 
				false );

			wp_register_script( 
				'moment-js', 
				plugins_url( '/js/fullcalendar/lib/moment.min.js', __FILE__ ) );
			wp_register_script( 
				'full-js', 
				plugins_url( '/js/fullcalendar/fullcalendar.min.js', __FILE__ ) );
			wp_register_script( 
				'bkap-images-loaded', 
				plugins_url( '/js/imagesloaded.pkg.min.js', __FILE__ ) );
			wp_register_script( 
				'bkap-qtip', 
				plugins_url( '/js/jquery.qtip.min.js', __FILE__ ), 
				array( 'jquery', 'bkap-images-loaded' ) );

			wp_enqueue_script( 
				'booking-calender-js', 
				plugins_url( '/js/booking-calender.js', __FILE__ ), 
				array( 'jquery', 'bkap-qtip' ,'moment-js', 'full-js', 'bkap-images-loaded', 'jquery-ui-core','jquery-ui-widget','jquery-ui-position', 'jquery-ui-selectmenu' ) );
			
			woocommerce_booking::localize_script( $vendor_id );
		}

		public static function bkap_load_zozo_css( $plugin_version_number ) {

			wp_enqueue_style( 
				'bkap-tabstyle-1', 
				plugins_url('/css/zozo.tabs.min.css', __FILE__), 
				'', 
				$plugin_version_number, 
				false );

			wp_enqueue_style( 
				'bkap-tabstyle-2', 
				plugins_url('/css/style.css', __FILE__), 
				'', 
				$plugin_version_number, 
				false );
		}

		public static function bkap_load_products_css( $plugin_version_number ) {

			wp_enqueue_style( 
				'bkap-booking', 
				plugins_url( '/css/booking.css', __FILE__ ) , 
				'', 
				$plugin_version_number , 
				false );

			// css file for the multi datepicker in admin product pages.
			wp_enqueue_style( 
				'bkap-datepick', 
				plugins_url( '/css/jquery.datepick.css', __FILE__ ), 
				'', 
				$plugin_version_number, 
				false );

			wp_enqueue_style( 
				'bkap-woocommerce_admin_styles', 
				plugins_url() . '/woocommerce/assets/css/admin.css', 
				'', 
				$plugin_version_number, 
				false );

			wp_enqueue_style( 
				'bkap-font-awesome', 
				plugins_url( '/css/font-awesome.css', __FILE__ ), 
				'', 
				$plugin_version_number, 
				false );

			wp_enqueue_style( 
				'bkap-font-awesome-min',
				"https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css", 
				'', 
				$plugin_version_number, 
				false );

			$global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			$calendar_theme = "";

			if ( isset( $global_settings ) ) {
				$calendar_theme = $global_settings->booking_themes;
			}
			if ( $calendar_theme == "" ) $calendar_theme = 'base';
			
			wp_dequeue_style( 'jquery-ui-style' );
			wp_register_style( 
				'bkap-jquery-ui', 
				"//code.jquery.com/ui/1.9.2/themes/$calendar_theme/jquery-ui.css", 
				'', 
				$plugin_version_number, 
				false );

			wp_enqueue_style( 'bkap-jquery-ui' );
		}

		public static function bkap_load_calendar_styles( $plugin_version_number ) {
			wp_enqueue_style( 'bkap-data', plugins_url('/css/view.booking.style.css', __FILE__ ) , '', $plugin_version_number, false );
					
			wp_enqueue_style( 'bkap-fullcalendar-css', plugins_url().'/woocommerce-booking/js/fullcalendar/fullcalendar.css' );
				
			// this is for displying the full calender view.
			wp_enqueue_style( 'full-css', plugins_url( '/js/fullcalendar/fullcalendar.css', __FILE__ ) );
				
			// this is used for displying the hover effect in calendar view.
			wp_enqueue_style( 'bkap-qtip-css', plugins_url( '/css/jquery.qtip.min.css', __FILE__ ), array() );
			
			// javascript for handling clicks of calendar icon changes
			wp_register_script( 'bkap-calendar-change', plugins_url( '/js/global-booking-settings.js', __FILE__ ), '', $plugin_version_number, false );
			wp_enqueue_script( 'bkap-calendar-change' );
		}

		public static function bkap_load_dokan_css( $plugin_version_number ) {

			wp_enqueue_style( 
				'bkap-dokan-css', 
				plugins_url( '/css/bkap-dokan.css', __FILE__ ) , 
				'', 
				$plugin_version_number , 
				false );
		}

		public static function bkap_load_dokan_booking_styles( $plugin_version_number ) {

			wp_enqueue_style( 
				'bkap-dokan-booking-css', 
				plugins_url( '/css/vendors/dokan/bkap-dokan-booking.css', __FILE__ ) , 
				'', 
				$plugin_version_number , 
				false );
		}
		
		/**
		 * Includes CSS files for the WC Vendors Dashboard.
		 * @since 4.6.0
		 * @param $plugin_version Plugin Version Number
		 */
		public static function bkap_wcv_dashboard_css( $plugin_version ) {
		
		    wp_enqueue_style(
    		    'bkap-woo-css',
    		    plugins_url() . '/woocommerce/assets/css/woocommerce.css',
    		    '',
    		    $plugin_version ,
    		    false
		    );
		    
		    wp_enqueue_style(
                'bkap-wcv-css',
                plugins_url( '/css/vendors/wc-vendors/bkap-wcv-bookings.css', __FILE__ ) ,
                '',
                $plugin_version ,
                false
		    );
		}
	}
}