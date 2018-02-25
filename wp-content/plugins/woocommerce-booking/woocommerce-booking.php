<?php 
/**
 * Plugin Name: Booking & Appointment Plugin for WooCommerce
 * Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-booking-plugin
 * Description: This plugin lets you capture the Booking Date & Booking Time for each product thereby allowing your WooCommerce store to effectively function as a Booking system. It allows you to add different time slots for different days, set maximum bookings per time slot, set maximum bookings per day, set global & product specific holidays and much more.
 * Version: 4.7.0
 * Author: Tyche Softwares
 * Author URI: http://www.tychesoftwares.com/
 * Requires PHP: 5.6
 * WC requires at least: 3.0.0
 * WC tested up to: 3.2.0
 */

global $BookUpdateChecker;
$BookUpdateChecker = '4.7.0';

// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
define( 'EDD_SL_STORE_URL_BOOK', 'http://www.tychesoftwares.com/' ); // IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system

// the name of your product. This is the title of your product in EDD and should match the download title in EDD exactly
define( 'EDD_SL_ITEM_NAME_BOOK', 'Booking & Appointment Plugin for WooCommerce' ); // IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system


if ( ! defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_BOOK_Plugin_Updater' ) ) {
	// load our custom updater if it doesn't already exist
	include( dirname( __FILE__ ) . '/plugin-updates/EDD_BOOK_Plugin_Updater.php' );
}

// retrieve our license key from the DB
$license_key = trim( get_option( 'edd_sample_license_key' ) );

// setup the updater
$edd_updater = new EDD_BOOK_Plugin_Updater( EDD_SL_STORE_URL_BOOK, __FILE__, array(
		'version' 	=> '4.7.0', 		// current version number
		'license' 	=> $license_key, 	// license key (used get_option above to retrieve from DB)
		'item_name' => EDD_SL_ITEM_NAME_BOOK, 	// name of this plugin
		'author' 	=> 'Ashok Rane'  // author of this plugin
)
);


include_once( 'bkap-config.php' );
include_once( 'availability-search.php' );
include_once( 'admin-bookings.php' );

function is_booking_active() {
    if ( is_plugin_active( 'woocommerce-booking/woocommerce-booking.php' ) ) {
		return true;
	} else {
		return false;
	}
}

//if (is_woocommerce_active())
{
	/**
	 * Localisation
	 **/
	//load_plugin_textdomain('woocommerce-booking', false, dirname( plugin_basename( __FILE__ ) ) . '/');
    // For language translation
    function  bkap_update_po_file(){
        $domain = 'woocommerce-booking';
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
        
        if ( $loaded = load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '-' . $locale . '.mo' ) ) {
            return $loaded;
        } else {
            load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
        }
    }
    
	/**
	 * woocommerce_booking class
	 **/
	if ( !class_exists( 'woocommerce_booking' ) ) {

		class woocommerce_booking {
			
			public function __construct() {
			    
			    add_action( 'admin_init', array( &$this, 'bkap_check_compatibility' ) );
				
			    $this->bkap_define_constants();
			    
			    add_action( 'init', array( &$this, 'bkap_include_files' ), 5 );
			    add_action( 'admin_init', array( &$this, 'bkap_include_files' ) );
			     
				// Initialize settings
				register_activation_hook( __FILE__,                     array( &$this, 'bkap_bookings_activate' ) );
				//Add plugin doc and forum link in description
				add_filter( 'plugin_row_meta',                          array( &$this, 'bkap_plugin_row_meta' ), 10, 2 );

				// Init post type
				add_action( 'init', array( &$this, 'bkap_init_post_types' ) );
				// Change Create Bookings link
				add_filter( 'admin_url', array( &$this, 'bkap_change_create_booking_link' ), 10, 2 );
				// custom post type meta boxes
				add_action( 'add_meta_boxes', array( $this, 'bkap_add_meta_boxes' ), 10, 1 );
				// remove the submit div
				add_action( 'admin_menu', array( &$this, 'bkap_remove_submitdiv' ), 10 );
                // save the changes in booking details meta box - edit booking
				add_filter( 'wp_insert_post_data', array( &$this, 'bkap_meta_box_save' ), 10, 2 );
				// Settings link on plugins page
				add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( &$this, 'bkap_plugin_settings_link' ) );
			
				add_action( 'admin_init',                               array( &$this, 'bkap_bookings_update_db_check' ) );
				add_action( 'admin_notices',                            array( &$this, 'bkap_update_db_notice' ), 10 );
				
				// Ajax calls
				add_action( 'init',                                     array( &$this, 'bkap_book_load_ajax' ) );
				add_action( 'admin_init',                               array( &$this, 'bkap_book_load_ajax_admin' ) );
				// WordPress Administration Menu
				add_action( 'admin_menu',                               array( 'global_menu', 'bkap_woocommerce_booking_admin_menu' ) );				
				// Display Booking Box on Add/Edit Products Page
				add_action( 'add_meta_boxes',                           array( 'bkap_booking_box_class', 'bkap_booking_box' ), 10 );				
				// Processing Bookings
				add_action( 'woocommerce_process_product_meta',         array( 'bkap_booking_box_class', 'bkap_process_bookings_box' ), 1, 2 );				
				// Vertical tabs
				add_action( 'admin_head',                               array( $this, 'bkap_vertical_my_enqueue_scripts_css' ) );
				add_action( 'admin_footer',                             array( 'bkap_booking_box_class', 'bkap_print_js' ) );				
				// Scripts
				add_action( 'admin_enqueue_scripts',                    array( &$this, 'bkap_my_enqueue_scripts_css' ) );
				add_action( 'admin_enqueue_scripts',                    array( &$this, 'bkap_my_enqueue_scripts_js' ) );				
				add_action( 'woocommerce_before_single_product',        array( &$this, 'bkap_front_side_scripts_js' ) );
				add_action( 'woocommerce_before_single_product',        array( &$this, 'bkap_front_side_scripts_css' ) );
				
				//Language Translation
				add_action( 'init', 'bkap_update_po_file' );				
				// Display on Products Page
				add_action( 'woocommerce_before_add_to_cart_form',      array( 'bkap_booking_process', 'bkap_before_add_to_cart' ) );
				add_action( 'woocommerce_before_add_to_cart_button',    array( 'bkap_booking_process', 'bkap_booking_after_add_to_cart' ), 8 );
				
				add_action( 'wp_ajax_bkap_remove_time_slot',            array( &$this, 'bkap_remove_time_slot' ) );
		//		add_action( 'wp_ajax_bkap_remove_day',                  array( &$this, 'bkap_remove_day' ) );
				add_action( 'wp_ajax_bkap_remove_specific',             array( &$this, 'bkap_remove_specific' ) );
				add_action( 'wp_ajax_bkap_remove_recurring',            array( &$this, 'bkap_remove_recurring' ) );				
				add_filter( 'woocommerce_add_cart_item_data',           array( 'bkap_cart', 'bkap_add_cart_item_data' ), 25, 2);
				add_filter( 'woocommerce_get_cart_item_from_session',   array( 'bkap_cart', 'bkap_get_cart_item_from_session' ), 25, 2);
				add_filter( 'woocommerce_get_item_data',                array( 'bkap_cart', 'bkap_get_item_data_booking' ), 25, 2 );
				
				// To validate the product in cart and checkout as per the Advance Booking Period set  
				add_action( 'woocommerce_check_cart_items',             array( 'bkap_validation', 'remove_product_from_cart' ) );
				add_action( 'woocommerce_before_checkout_process',      array( 'bkap_validation', 'remove_product_from_cart' ) );
				
				add_filter( 'woocommerce_add_cart_item',            array( 'bkap_cart', 'bkap_add_cart_item' ), 25, 1 );
				
				add_action( 'woocommerce_checkout_update_order_meta',   array( 'bkap_checkout', 'bkap_order_item_meta' ), 10, 2);
				add_action( 'woocommerce_before_checkout_process',      array( 'bkap_validation', 'bkap_quantity_check' ) );
				add_filter( 'woocommerce_add_to_cart_validation',       array( 'bkap_validation', 'bkap_get_validate_add_cart_item' ), 10, 3 );
				
				// Free up bookings when an order is cancelled or refunded or failed.
				add_action( 'woocommerce_order_status_cancelled' ,      array( 'bkap_cancel_order', 'bkap_woocommerce_cancel_order' ), 10, 1 );
				add_action( 'woocommerce_order_status_refunded' ,       array( 'bkap_cancel_order', 'bkap_woocommerce_cancel_order' ), 10, 1 );
				add_action( 'woocommerce_order_status_failed' ,         array( 'bkap_cancel_order', 'bkap_woocommerce_cancel_order' ), 10, 1 );
				
				add_action( 'woocommerce_order_status_changed', array( 'bkap_cancel_order', 'bkap_woocommerce_restore_bookings' ), 10, 3 );
				
				// Free up the bookings when an order is trashed
				add_action( 'wp_trash_post',                            array( 'bkap_cancel_order', 'bkap_trash_order' ), 10, 1 );
				add_action( 'untrash_post',                             array( 'bkap_cancel_order', 'bkap_untrash_order' ), 10, 1 );
				add_action( 'woocommerce_duplicate_product' ,           array( &$this, 'bkap_product_duplicate' ), 10, 2 );
				add_action( 'woocommerce_check_cart_items',             array( 'bkap_validation', 'bkap_quantity_check' ) );
				
				//Export date to ics file from order received page
				$saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
				
				if ( isset( $saved_settings->booking_export ) && $saved_settings->booking_export == 'on' ) {
					add_filter( 'woocommerce_order_details_after_order_table', array( 'bkap_ics', 'bkap_export_to_ics' ), 10, 3 );
				}
				
				//Add order details as an attachment
				if ( isset( $saved_settings->booking_attachment ) && $saved_settings->booking_attachment == 'on' ) {
					add_filter( 'woocommerce_email_attachments', array( 'bkap_ics', 'bkap_email_attachment' ), 10, 3 );
				}
				
				add_action( 'admin_init',                               array( 'bkap_license', 'bkap_edd_sample_register_option' ) );
				add_action( 'admin_init',                               array( 'bkap_license', 'bkap_edd_sample_deactivate_license' ) );
				add_action( 'admin_init',                               array( 'bkap_license', 'bkap_edd_sample_activate_license' ) );	
				add_filter( 'woocommerce_my_account_my_orders_actions', array( 'bkap_cancel_order', 'bkap_get_add_cancel_button' ), 10, 3 );
				add_filter( 'woocommerce_add_to_cart_fragments',        array( 'bkap_cart', 'bkap_woo_cart_widget_subtotal' ) );
				// Hide the hardcoded item meta records frm being displayed on the admin orders page
				add_filter( 'woocommerce_hidden_order_itemmeta',        array( 'bkap_checkout', 'bkap_hidden_order_itemmeta'), 10, 1 );
				// Translating Block name of Fixed Block booking.
				add_action('admin_init',                                array( &$this, 'bkap_register_fixed_block_string_for_wpml') );
				// Gcal Settings tab
				add_action( 'admin_init',                               array( 'global_menu', 'bkap_gcal_settings' ), 10 );
				// Global Settings
				add_action( 'admin_init',                               array( 'global_menu', 'bkap_global_settings' ), 10 );
				add_action( 'admin_init',                               array( 'global_menu', 'bkap_booking_labels' ), 10 );
				
				// Reallocate booking when changing order status from failed to processing.
				add_action( 'woocommerce_order_status_failed_to_processing', array( 'bkap_cancel_order', 'bkap_reallocate_booking_when_order_status_failed_to_processing' ) , 10, 10 );
				// Reallocate booking when changing order status from failed to completed.
				add_action( 'woocommerce_order_status_failed_to_completed',  array( 'bkap_cancel_order', 'bkap_reallocate_booking_when_order_status_failed_to_processing' ) , 10, 10 );
				// Reallocate booking when changing order status from failed to on-hold.
				add_action( 'woocommerce_order_status_failed_to_on-hold',    array( 'bkap_cancel_order', 'bkap_reallocate_booking_when_order_status_failed_to_processing' ) , 10, 10 );

				// Add unlimited booking slots if any
				add_filter( 'bkap_edit_display_timeslots', array( 'bkap_booking_process', 'bkap_add_unlimited_slots' ), 1, 1 );
				
				// Add locked time slot to dropdown if needed
				add_filter( 'bkap_edit_display_timeslots', array( &$this, 'add_time_slot' ), 10, 1 );
				
				add_action( 'save_post',                   array( $this, 'bkap_meta_box_save1' ), 10, 2 );
				
				$this->bkap_load_edit_bookings_class( $saved_settings );
			}
			
			/**
			 * Saving details of Resource Availability
			 */
			
			public static function bkap_meta_box_save1( $post_id, $post ) {
			
			    if ( "bkap_resource" == get_post_type() ) {
			        	
			        $resource_data = bkap_save_resources( $post_id, $post );
			        	
			        $meta_args = array(
			            '_bkap_resource_qty'   			=> $resource_data['bkap_resource_qty'],
			            '_bkap_resource_availability'   => $resource_data['bkap_resource_availability']
			        );
			         
			        // run a foreach and save the data
			        foreach ( $meta_args as $key => $value ) {
			            update_post_meta( $post_id, $key, $value );
			        }
			    }
			}

			public static function bkap_define_constants(){

				define( 'BKAP_BOOKINGS_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );

				define( 'BKAP_VENDORS_INCLUDES_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/includes/vendors/' );

				define( 'BKAP_VENDORS_TEMPLATE_PATH', BKAP_BOOKINGS_TEMPLATE_PATH . 'vendors-integration/' );
				
			}
			
			/**
			 * Check if WooCommerce is active.
			 */
			public static function bkap_check_woo_installed() {
			
			    if ( class_exists( 'WooCommerce' ) ) {
			        return true;
			    } else {
			        return false;
			    }
			}
				
			/**
			 * Ensure that the booking plugin is deactivated when WooCommerce
			 * is deactivated.
			 */
			public static function bkap_check_compatibility() {
			    	
			    if ( ! self::bkap_check_woo_installed() ) {
			        	
			        if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
			            deactivate_plugins( plugin_basename( __FILE__ ) );
			            	
			            add_action( 'admin_notices', array( 'woocommerce_booking', 'bkap_disabled_notice' ) );
			            if ( isset( $_GET['activate'] ) ) {
			                unset( $_GET['activate'] );
			            }
			            	
			        }
			        	
			    }
			}
				
			/**
			 * Display a notice in the admin Plugins page if the booking plugin is
			 * activated while WooCommerce is deactivated.
			 */
			public static function bkap_disabled_notice() {
			    	
			    $class = 'notice notice-error';
			    $message = __( 'Booking & Appointment Plugin for WooCommerce requires WooCommerce installed and activate.', 'woocommerce-booking' );
			    	
			    printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
			    	
			}
			
			/**
			 * Include the plugin files
			 */
			public static function bkap_include_files() {
			    include_once( 'lang.php' );
			    include_once( 'bkap-common.php' );
                include_once( 'block-pricing.php' );
                include_once( 'special-booking-price.php' );
                include_once( 'validation.php' );
                include_once( 'checkout.php' );
                include_once( 'cart.php' );
                include_once( 'ics.php' );
                include_once( 'cancel-order.php' );
                include_once( 'booking-process.php' );
                include_once( 'global-menu.php' );
                include_once( 'booking-box.php' );
                include_once( 'timeslot-price.php' );
                include_once( 'booking-confirmation.php' );
                include_once( 'class-booking-email-manager.php' );
                include_once( 'variation-lockout.php' );
                include_once( 'attribute-lockout.php' );
                include_once( 'bkap-calendar-sync.php' );
                include_once( 'class-bkap-gateway.php' );
//                include_once( 'class-approve-booking.php' );
                
                include_once( 'includes/class-bkap-booking.php' );
                include_once( 'bkap-functions.php' );
                include_once( 'class-bkap-booking-view-bookings.php' );
                include_once( 'class-bkap-edit-bookings.php' );
                include_once( 'class-bkap-rescheduled-order.php' );
                include_once( 'class-bkap-addon-compatibility.php' );
                include_once( 'includes/class-bkap-gcal-event.php' );
                include_once( 'class-bkap-gcal-event-view.php' );
                include_once( 'process-functions.php' );
                
                // Including files for resources
                include_once( 'includes/class-bkap-resources-cpt.php' );
                include_once( 'includes/class-bkap-product-resource.php' );

                include_once( 'class-bkap-scripts.php' );

                if ( class_exists( 'WeDevs_Dokan' ) ) {
                	include_once( BKAP_VENDORS_INCLUDES_PATH . 'dokan/class-bkap-dokan-integration.php' );
                }

                include_once( BKAP_VENDORS_INCLUDES_PATH . 'vendors-common.php' );

                if( function_exists( 'is_wcvendors_active' ) && is_wcvendors_active() ) {
                    include_once( BKAP_VENDORS_INCLUDES_PATH . 'wc-vendors/wc-vendors.php' );
                }

                if( class_exists( 'PP_One_Page_Checkout' ) ) {
                    include_once( 'class-bkap-onepage-checkout.php' ); 
                }
			}
			
			/**
			 * Show row meta on the plugin screen.
			 *
			 * @param	mixed $links Plugin Row Meta
			 * @param	mixed $file  Plugin Base file
			 * @return	array
			 */
			public static function bkap_plugin_row_meta( $links, $file ) {
			    $plugin_base_name = plugin_basename(__FILE__);
			    
			    if ( $file == $plugin_base_name ) {
			        $row_meta = array(
			            'docs'    => '<a href="' . esc_url( apply_filters( 'woocommerce_booking_and_appointment_docs_url', 'https://www.tychesoftwares.com/woocommerce-booking-plugin-documentation/' ) ) . '" title="' . esc_attr( __( 'View Booking & Appointment Plugin Documentation', 'woocommerce-booking' ) ) . '">' . __( 'Docs', 'woocommerce-booking' ) . '</a>',
			            'support' => '<a href="' . esc_url( apply_filters( 'woocommerce_booking_and_appointment_support_url', 'https://www.tychesoftwares.com/forums/forum/woocommerce-booking-appointment-plugin/' ) ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'woocommerce-booking' ) ) . '">' . __( 'Support Forums', 'woocommerce-booking' ) . '</a>',
			        );
			        	
			        return array_merge( $links, $row_meta );
			    }
			    	
			    return (array) $links;
			}
			
			/**
			 * Settings link on Plugins page
			 *
			 * @access public
			 * @param array $links
			 * @return array
			 */
			public function bkap_plugin_settings_link( $links ) {
			    $setting_link['settings'] = '<a href="'. esc_url( get_admin_url( null, 'admin.php?page=woocommerce_booking_page&action=settings') ) .'">Settings</a>';
			    $links = $setting_link + $links;
			    return $links;
			}
			
		   /**
            * This function is used to load ajax functions required by plugin.
            */
			function bkap_book_load_ajax() {
				
			    if ( !is_user_logged_in() ){
					add_action( 'wp_ajax_nopriv_bkap_get_per_night_price',         array( 'bkap_booking_process', 'bkap_get_per_night_price' ) );
					add_action( 'wp_ajax_nopriv_bkap_check_for_time_slot',         array( 'bkap_booking_process', 'bkap_check_for_time_slot' ) );
					add_action( 'wp_ajax_nopriv_bkap_insert_date',                 array( 'bkap_booking_process', 'bkap_insert_date' ) );
					add_action( 'wp_ajax_nopriv_bkap_call_addon_price',            array( 'bkap_booking_process', 'bkap_call_addon_price' ) );
					add_action( 'wp_ajax_nopriv_bkap_js',                          array( 'bkap_booking_process', 'bkap_js' ) );
					add_action( 'wp_ajax_nopriv_bkap_get_date_lockout',            array( 'bkap_booking_process', 'bkap_get_date_lockout' ) );
					add_action( 'wp_ajax_nopriv_bkap_get_time_lockout',            array( 'bkap_booking_process', 'bkap_get_time_lockout' ) );
					add_action( 'wp_ajax_nopriv_save_widget_dates',                array( 'Custom_WooCommerce_Widget_Product_Search', 'save_widget_dates' ) );
					add_action( 'wp_ajax_nopriv_clear_widget_dates',                array( 'Custom_WooCommerce_Widget_Product_Search', 'clear_widget_dates' ) );
					add_action( 'wp_ajax_nopriv_bkap_booking_calender_content',    array( &$this, 'bkap_booking_calender_content' ) );
					add_action( 'wp_ajax_nopriv_bkap_get_fixed_block_inline_date', array( 'bkap_booking_process', 'bkap_get_fixed_block_inline_date' ) );
					add_action( 'wp_ajax_nopriv_bkap_purchase_wo_date_price',       array( 'bkap_booking_process', 'bkap_purchase_wo_date_price' ) );
				} else{
					add_action( 'wp_ajax_bkap_get_per_night_price',                array( 'bkap_booking_process', 'bkap_get_per_night_price' ) );
					add_action( 'wp_ajax_bkap_check_for_time_slot',                array( 'bkap_booking_process', 'bkap_check_for_time_slot' ) );
					add_action( 'wp_ajax_bkap_insert_date',                        array( 'bkap_booking_process', 'bkap_insert_date' ) );
					add_action( 'wp_ajax_bkap_call_addon_price',                   array( 'bkap_booking_process', 'bkap_call_addon_price' ) );
					add_action( 'wp_ajax_bkap_js',                                 array( 'bkap_booking_process', 'bkap_js' ) );
					add_action( 'wp_ajax_bkap_get_date_lockout',                   array( 'bkap_booking_process', 'bkap_get_date_lockout' ) );
					add_action( 'wp_ajax_bkap_get_time_lockout',                   array( 'bkap_booking_process', 'bkap_get_time_lockout' ) );
					add_action( 'wp_ajax_save_widget_dates',                       array( 'Custom_WooCommerce_Widget_Product_Search', 'save_widget_dates' ) );
					add_action( 'wp_ajax_clear_widget_dates',                       array( 'Custom_WooCommerce_Widget_Product_Search', 'clear_widget_dates' ) );
					add_action( 'wp_ajax_bkap_booking_calender_content',           array( &$this, 'bkap_booking_calender_content' ) );
					add_action( 'wp_ajax_bkap_get_fixed_block_inline_date', array( 'bkap_booking_process', 'bkap_get_fixed_block_inline_date' ) );
					add_action( 'wp_ajax_bkap_purchase_wo_date_price',             array( 'bkap_booking_process', 'bkap_purchase_wo_date_price' ) );
				}
				
				add_action( 'wc_ajax_bkap_add_notice', array( 'bkap_common', 'bkap_add_notice' ) );
				add_action( 'wc_ajax_bkap_clear_notice', array( 'bkap_common', 'bkap_clear_notice' ) );
				
			}
			
			function bkap_book_load_ajax_admin() {
			    add_action( 'wp_ajax_bkap_save_attribute_data',                array( 'bkap_attributes', 'bkap_save_attribute_data' ) );
			    add_action( 'wp_ajax_bkap_discard_imported_event',             array( 'import_bookings', 'bkap_discard_imported_event' ) );
			    add_action( 'wp_ajax_bkap_map_imported_event',                 array( 'import_bookings', 'bkap_map_imported_event' ) );
			    add_action( 'wp_ajax_bkap_save_settings',                      array( 'bkap_booking_box_class', 'bkap_save_settings' ) );
			    add_action( 'wp_ajax_bkap_delete_date_time',                   array( 'bkap_booking_box_class', 'bkap_delete_date_time' ) );
			    add_action( 'wp_ajax_bkap_manual_db_update',                   array( &$this, 'bkap_manual_db_update' ) );
			    add_action( 'wp_ajax_bkap_manual_db_update_f_p',               array( &$this, 'bkap_manual_db_update_f_p' ) );
			    add_action( 'wp_ajax_bkap_manual_db_update_v420',              'bkap_manual_db_update_v420' );
			    add_action( 'wp_ajax_bkap_delete_specific_range',              array( 'bkap_booking_box_class', 'bkap_delete_specific_range' ) );
			    add_action( 'wp_ajax_bkap_delete_booking',                     array( &$this, 'bkap_trash_booking' ) );

			    add_action( 'wp_ajax_bkap_load_time_slots',                    array( 'bkap_booking_box_class', 'bkap_load_time_slots' ) );

			}
                        
            /**
             * This function duplicates the booking settings 
             * of the original product to the new product.
             */ 
            function bkap_product_duplicate( $new_id, $post ) {
				global $wpdb;
				
				$old_id             = $post->ID;
				$duplicate_query    = "SELECT * FROM `".$wpdb->prefix."booking_history` WHERE post_id = %d AND status = '' " ;
				$results_date       = $wpdb->get_results ( $wpdb->prepare( $duplicate_query, $old_id ) );
				
				foreach ( $results_date as $key => $value ) {
					$query_insert  =   "INSERT INTO `".$wpdb->prefix."booking_history`
                    					(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                    					VALUES (
                    					'".$new_id."',
                    					'".$value->weekday."',
                    					'".$value->start_date."',
                    					'".$value->end_date."',
                    					'".$value->from_time."',
                    					'".$value->to_time."',
                    					'".$value->total_booking."',
                    					'".$value->total_booking."' )";
                    					$wpdb->query( $query_insert );
				}
				do_action( 'bkap_product_addon_duplicate', $new_id, $old_id );
			}
			
			/**
             *  This function is executed when the plugin is updated using 
             *  the Automatic Updater. It calls the bookings_activate function 
             *  which will check the table structures for the plugin and 
             *  make any changes if necessary.
             */
			function bkap_bookings_update_db_check() {
			    
			    global $booking_plugin_version, $BookUpdateChecker;
				global $wpdb;
				
				$booking_plugin_version = get_option( 'woocommerce_booking_db_version' );
				if ( $booking_plugin_version != $this->get_booking_version() ) {
				    
				    // Introducing the ability enable/disable the charging of GF options on a per day basis above 2.4.4
				    // Set it to 'ON' by default
				    if ( $booking_plugin_version <= '2.4.4' ) {
				    
				        $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
				        if ( isset( $global_settings ) && ! isset( $global_settings->woo_gf_product_addon_option_price ) ) {
				            $global_settings->woo_gf_product_addon_option_price = 'on';
				            update_option( 'woocommerce_booking_global_settings', json_encode( $global_settings ) );
				        }
				    }
				    
					$table_name        = $wpdb->prefix . "booking_history";
					$check_table_query = "SHOW COLUMNS FROM $table_name LIKE 'status'";
					
					$results = $wpdb->get_results ( $check_table_query );
					
					if ( count( $results ) == 0 ) {
						$alter_table_query    =   "ALTER TABLE $table_name
						                          ADD `status` varchar(20) NOT NULL AFTER  `available_booking`";
						$wpdb->get_results ( $alter_table_query );
					}
					
					update_option( 'woocommerce_booking_db_version', '4.7.0' );
					// Add an option to change the "Choose a Time" text in the time slot dropdown
					add_option( 'book_time-select-option', 'Choose a Time' );
					// Add an option to change ICS file name
					add_option( 'book_ics-file-name', 'Mycal' );
					// add an option to set the label for fixed block drop down
					add_option( 'book_fixed-block-label',   'Select Period' );
					
					// add an option to add a label for the front end price display
					add_option( 'book_price-label', '' );
					// add setting to set WooCommerce Price to be displayed
					if ( $booking_plugin_version <= '2.6.2' ) {
					
					    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					    if ( ! isset( $global_settings->hide_variation_price ) ) {
				            $global_settings->hide_variation_price = '';
					        update_option( 'woocommerce_booking_global_settings', json_encode( $global_settings ) );
					    }
					}
					
					// add setting to set WooCommerce Price to be displayed
					if ( $booking_plugin_version <= '2.9' ) {
					
					    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					    if ( ! isset( $global_settings->display_disabled_buttons ) ) {
					        $global_settings->display_disabled_buttons = '';
					        update_option( 'woocommerce_booking_global_settings', json_encode( $global_settings ) );
					    }
					}
					// add setting to set WooCommerce Price to be displayed
					if ( $booking_plugin_version <= '3.1' ) {
					    	
					    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					    if ( ! isset( $global_settings->hide_booking_price ) ) {
					        $global_settings->hide_booking_price = '';
					        update_option( 'woocommerce_booking_global_settings', json_encode( $global_settings ) );
					    }
					    
					    // update the booking history table
					    // 1. delete all inactive base records for recurring weekdays (with & without time slots)
					    $delete_base_query = "DELETE FROM `" . $wpdb->prefix . "booking_history`
					                           WHERE status = 'inactive'
					                           AND weekday <> ''
					                           AND start_date = '0000-00-00'";
					    $wpdb->query( $delete_base_query );
					    	
					    // 2. delete all inactive specific date records (with & without time slots)
					    	
					    // get a past date 3 months from today
					    $date = date( 'Y-m-d', strtotime( '-3 months' ) );
					    // fetch all inactive specific date records starting from 3 months past today	
					    $select_specific = "SELECT id, post_id, start_date, from_time, to_time FROM `" . $wpdb->prefix . "booking_history`
					                           WHERE status = 'inactive'
					                           AND weekday = ''
					                           AND start_date <> '0000-00-00'
					                           AND end_date = '0000-00-00'
					                           AND start_date >= %d";
					    	
					    $result_specific = $wpdb->get_results( $wpdb->prepare( $select_specific, $date ) );
					    	
					    foreach ( $result_specific as $key => $value ) {
					        $select_active_specific = "SELECT id FROM `" . $wpdb->prefix ."booking_history`
					                                   WHERE status <> 'inactive'
					                                   AND post_id = %d
					                                   AND start_date = %s
					                                   AND end_date = '0000-00-00'
					                                   AND from_time = %s
					                                   AND to_time = %s";
					        $results_active = $wpdb->get_results( $wpdb->prepare( $select_active_specific, $value->post_id, $value->start_date, $value->from_time, $value->to_time ) );
					         
					        if ( isset( $results_active ) && 1 == count( $results_active ) ) {
					    
					            // delete the inactive record if a corresponding active record is found
					            $delete_inactive_specific = "DELETE FROM `" . $wpdb->prefix . "booking_history`
					                                           WHERE ID = '" . $value->id . "'";
					            $wpdb->query( $delete_inactive_specific );
					        }
					    }
					    // delete all inactive specific date records older than 3 months from today	
					    $delete_specific = "DELETE FROM `" . $wpdb->prefix . "booking_history`
					                           WHERE status = 'inactive'
					                           AND weekday = ''
					                           AND start_date <> '0000-00-00'
					                           AND start_date < '" . $date . "'
				                               AND end_date = '0000-00-00'";
					    $wpdb->query( $delete_specific );
					    	
					}
					
					// Get the option setting to check if adbp has been updated to hrs for existing users
					$booking_abp_hrs = get_option( 'woocommerce_booking_abp_hrs' );
						
					if ( $booking_abp_hrs != 'HOURS' ) {
						// For all the existing bookable products, modify the ABP to hours instead of days
						$args     = array( 'post_type' => 'product', 'posts_per_page' => -1 );
						$product  = query_posts( $args );
						
						$product_ids = array();
						foreach( $product as $k => $v ){
							$product_ids[] = $v->ID;
						}
						
						if( is_array( $product_ids ) && count( $product_ids ) > 0 ) {
    						foreach( $product_ids as $k => $v ){
    							$booking_settings = get_post_meta( $v, 'woocommerce_booking_settings' , true );
    							
    							if ( isset( $booking_settings ) && isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] == 'on' ) {
    								
    							    if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] > 0 ) {
    									$advance_period_hrs                                = $booking_settings['booking_minimum_number_days'] * 24;
    									$booking_settings['booking_minimum_number_days']   = $advance_period_hrs;
    									update_post_meta( $v, 'woocommerce_booking_settings', $booking_settings );
    								}
    							}
    						}
    						update_option( 'woocommerce_booking_abp_hrs', 'HOURS' );
						}
					}

					// Get the option setting to check if tables are set to utf8 charset
					$alter_queries = get_option( 'woocommerce_booking_alter_queries' );
						
					if ( $alter_queries != 'yes' ) {
						// For all the existing bookable products, modify the ABP to hours instead of days
						$table_name           = $wpdb->prefix . "booking_history";
						$sql_alter            = "ALTER TABLE $table_name CONVERT TO CHARACTER SET utf8" ;
						$wpdb->get_results ( $sql_alter );
						
						$order_table_name     = $wpdb->prefix . "booking_order_history";
						$order_alter_sql      = "ALTER TABLE $order_table_name CONVERT TO CHARACTER SET utf8" ;
						$wpdb->get_results ( $order_alter_sql );
						
						$table_name_price     = $wpdb->prefix . "booking_block_price_meta";
						$sql_alter_price      = "ALTER TABLE $table_name_price CONVERT TO CHARACTER SET utf8" ;
						$wpdb->get_results ( $sql_alter_price );
				
						$table_name_meta      = $wpdb->prefix . "booking_block_price_attribute_meta";
						$sql_alter_meta       = "ALTER TABLE $table_name_meta CONVERT TO CHARACTER SET utf8" ;
						$wpdb->get_results ( $sql_alter_meta );

						$block_table_name     = $wpdb->prefix . "booking_fixed_blocks";
						$blocks_alter_sql     = "ALTER TABLE $block_table_name CONVERT TO CHARACTER SET utf8" ;
						$wpdb->get_results ( $blocks_alter_sql );
						
						update_option( 'woocommerce_booking_alter_queries', 'yes' );
					}
					
					if( get_option( 'bkap_update_booking_labels_settings' ) != 'yes' && $booking_plugin_version < '2.8' ) {
					    $booking_date_label = get_option( 'book.date-label' );
					    update_option( 'book_date-label', $booking_date_label );
					
					    $booking_checkout_label = get_option( 'checkout.date-label' );
					    update_option( 'checkout_date-label', $booking_checkout_label );
					
					    $bkap_calendar_icon_label = get_option( 'bkap_calendar_icon_file' );
					    update_option( 'bkap_calendar_icon_file', $bkap_calendar_icon_label );
					    					
					    $booking_time_label = get_option( 'book.time-label' );
					    update_option( 'book_time-label', $booking_time_label );
					
					    $booking_time_select_option = get_option( 'book.time-select-option' );
					    update_option( 'book_time-select-option', $booking_time_select_option );
					
					    $booking_fixed_block_label = get_option( 'book.fixed-block-label' );
					    update_option( 'book_fixed-block-label', $booking_fixed_block_label );
					
					    $booking_price = get_option( 'book.price-label' );
					    update_option( 'book_price-label', $booking_price );
					
					    $booking_item_meta_date = get_option( 'book.item-meta-date' );
					    update_option( 'book_item-meta-date', $booking_item_meta_date );
					
					    $booking_item_meta_checkout_date = get_option( 'checkout.item-meta-date' );
					    update_option( 'checkout_item-meta-date', $booking_item_meta_checkout_date );
					    
					    $booking_item_meta_time = get_option( 'book.item-meta-time' );
					    update_option( 'book_item-meta-time', $booking_item_meta_time );
					    
					    $booking_ics_file = get_option( 'book.ics-file-name' );
					    update_option( 'book_ics-file-name', $booking_ics_file );
					    
					    $booking_cart_date = get_option( 'book.item-cart-date' );
					    update_option( 'book_item-cart-date', $booking_cart_date );
					    
					    $booking_cart_checkout_date = get_option( 'checkout.item-cart-date' );
					    update_option( 'checkout_item-cart-date', $booking_cart_checkout_date );
					    
					    $booking_cart_time = get_option( 'book.item-cart-time' );
					    update_option( 'book_item-cart-time', $booking_cart_time );
					
					    // delete the labels from wp_options
					    delete_option( 'book.date-label' );
					    delete_option( 'checkout.date-label' );
					    delete_option( 'book.time-label' );
					    delete_option( 'book.time-select-option' );
					    delete_option( 'book.fixed-block-label' );
					    delete_option( 'book.price-label' );
					    delete_option( 'book.item-meta-date' );
					    delete_option( 'checkout.item-meta-date' );
					    delete_option( 'book.item-meta-time' );
					    delete_option( 'book.ics-file-name' );
					    delete_option( 'book.item-cart-date' );
					    delete_option( 'checkout.item-cart-date' );
					    delete_option( 'book.item-cart-time' );
					    
					    update_option( 'bkap_update_booking_labels_settings', 'yes' );
					}
					
					// add the new messages in the options table
					add_option( 'book_stock-total', 'AVAILABLE_SPOTS stock total' );
					add_option( 'book_available-stock-date', 'AVAILABLE_SPOTS bookings are available on DATE' );
					add_option( 'book_available-stock-time', 'AVAILABLE_SPOTS bookings are available for TIME on DATE' );
					add_option( 'book_available-stock-date-attr', 'AVAILABLE_SPOTS ATTRIBUTE_NAME bookings are available on DATE' );
					add_option( 'book_available-stock-time-attr', 'AVAILABLE_SPOTS ATTRIBUTE_NAME bookings are available for TIME on DATE' );
					
					add_option( 'book_limited-booking-msg-date', 'PRODUCT_NAME has only AVAILABLE_SPOTS tickets available for the date DATE.' );
					add_option( 'book_no-booking-msg-date', 'For PRODUCT_NAME, the date DATE has been fully booked. Please try another date.' );
					add_option( 'book_limited-booking-msg-time', 'PRODUCT_NAME has only AVAILABLE_SPOTS tickets available for TIME on DATE.' );
					add_option( 'book_no-booking-msg-time', 'For PRODUCT_NAME, the time TIME on DATE has been fully booked. Please try another timeslot.' );
					add_option( 'book_limited-booking-msg-date-attr', 'PRODUCT_NAME has only AVAILABLE_SPOTS ATTRIBUTE_NAME tickets available for the date DATE.' );
					add_option( 'book_limited-booking-msg-time-attr', 'PRODUCT_NAME has only AVAILABLE_SPOTS ATTRIBUTE_NAME tickets available for TIME on DATE.' );
					
					add_option( 'book_real-time-error-msg', 'That date just got booked. Please reload the page.' );
					
					// from 4.0.0, we're going to save the booking settings as individual meta fields. So update the post meta for all bookable products
				    if ( $booking_plugin_version < '4.0.0' ) {
					    
					    // call the function which will individualize settings for all the bookable products
					    bkap_400_update_settings( $booking_plugin_version );
					
					}
					
					// from 4.1.0, we're going to save the fixed blocks and price by range as individual meta fields. So update the post meta for tables of fixed booking block and price by range
					if ( $booking_plugin_version < '4.1.0' ) {
					    	
					    // call the function which will individualize settings for all the bookable products
					    bkap_410_update_settings( $booking_plugin_version );
					    	
					}

				}
			}
			
			/**
			 * Adds a notification for the admin to update the DB manually.
			 * This notification is added only if the auto update fails
			 * @since 4.0.0
			 */
			public static function bkap_update_db_notice() {

				global $wpdb;

			    $db_status       = get_option( 'bkap_400_update_db_status' );
			    $db_status_410   = get_option( 'bkap_410_update_db_status' );
			    $db_status_420   = get_option( 'bkap_420_update_db_status' );
			    $gcal_status_420 = get_option( 'bkap_420_update_gcal_status' );
			    
			    $class = 'notice notice-error';
			    
			    // if the version is 4.2.0 and the update has not been run at all.
			    $plugin_version = get_option( 'woocommerce_booking_db_version' );

				// This is done to ensure that for fresh installations no notices are displayed.
				$bookings_query = "SELECT * FROM `" . $wpdb->prefix . "booking_order_history`";
				$bookings_array = $wpdb->get_results( $bookings_query );

				if ( isset( $bookings_array ) && empty( $bookings_array ) ) {
					update_option( 'bkap_420_update_db_status', 'success' );
					update_option( 'bkap_420_update_gcal_status', 'success' );
				}

			    $valid_status = array( 'fail', 'success' );
			    // step 1 has not been run
			    if ( isset( $plugin_version ) && '4.1.0' <= $plugin_version &&
			        isset( $db_status_420 ) && !in_array( $db_status_420, $valid_status ) && 
			        isset( $gcal_status_420 ) && !in_array( $gcal_status_420, $valid_status ) &&
			        isset( $bookings_array ) && !empty( $bookings_array ) ) { 

			        $class .= ' is-dismissible';
			        $message = '
    			    <table width="100%">
                        <tr>
                            <td style="text-align:left;">';
			        	
			        $message .= __( 'We need to run a database update to migrate your bookings and imported Google Calendar events into the new UI screens. Please click on the Update Now button to start the process.', 'woocommerce-booking' );
			        	
			        $message .= '</td>
                            <td style="text-align:right;">
                                <button type="submit" class="button-primary" id="bkap_db_420_update"  onClick="bkap_400_db_update()">';
			        	
			        $message .=  __( 'Update Now', 'woocommerce-booking' );
			        	
			        $message .= '
                                </button>
                            </td>
                        </tr>
    			    </table>';
			        	
			        printf( '<div class="%1$s">%2$s</div>', $class, $message );
			        	
			        
			    }
			    if ( ( isset( $db_status ) && 'fail' == strtolower( $db_status ) ) || 
			    	 ( isset( $db_status_410 ) && 'fail' == strtolower( $db_status_410 ) ) ||
			    	 ( isset( $db_status_420 ) && 'fail' == strtolower( $db_status_420 ) ) ||
			         ( isset( $gcal_status_420 ) && 'fail' == strtolower( $gcal_status_420 ) ) ) {
			         
			        $message = '
    			    <table width="100%">
                        <tr>
                            <td style="text-align:left;">';
			
			        $message .= __( 'The automatic database update for Booking & Appointment plugin for WooCommerce has failed. Please click on the Update button to manually update the database.', 'woocommerce-booking' );
			
			        $message .= '</td>
                            <td style="text-align:right;">
                                <button type="submit" class="button-primary" id="bkap_db_update"  onClick="bkap_400_db_update()">';
			
			        $message .=  __( 'Update', 'woocommerce-booking' );
			
			        $message .= '
                                </button>
                            </td>
                        </tr>
    			    </table>';
			
			        printf( '<div class="%1$s">%2$s</div>', $class, $message );
			    }
			    	
			}
				
			/**
			 * Runs via ajax. Tries to update the DB manually.
			 * @since 4.0.0
			 */
			function bkap_manual_db_update() {

			    global $wpdb;
			    // get the previous version number
			    $query_version = "SELECT meta_key FROM `" . $wpdb->prefix . "postmeta`
			                         WHERE meta_key LIKE %s
			                         ORDER BY post_id DESC LIMIT 1";
			    $result_query = $wpdb->get_results( $wpdb->prepare( $query_version, "woocommerce_booking_settings_%" ) );
			    
			    $db_version = '3.5.4';
			    if ( is_array( $result_query ) && count( $result_query ) > 0 ) {
			        $meta_key = $result_query[ 0 ]->meta_key;
			        $exploded_meta = explode( '_', $meta_key );
			        $db_version = $exploded_meta[ 3 ];
			    } 
			     
    		    bkap_400_update_settings( $db_version );
			     
			    $return_status = get_option( 'bkap_400_update_db_status' );
			
			    if ( 'success' == $return_status ) {
			        // add a dismissable admin notice
			 //       add_action( 'admin_notices', array( 'woocommerce_booking', 'bkap_update_success_notice' ) );
			    } else {
			        update_option( 'bkap_400_manual_update_count', '1' );
			    }
			
			    echo $return_status;
			    die;
			}
			
			/**
			 * Runs via ajax. Tries to update the DB manually.
			 * @since 4.1.0
			 */
			
			function bkap_manual_db_update_f_p(){
			    global $wpdb;
			    
			    // get the previous version number
			    $query_version = "SELECT meta_key FROM `" . $wpdb->prefix . "postmeta`
			                         WHERE meta_key LIKE %s
			                         ORDER BY post_id DESC LIMIT 1";
			    $result_query = $wpdb->get_results( $wpdb->prepare( $query_version, "woocommerce_booking_settings_f_p_%" ) );
			     
			    $db_version = '3.5.4';
			    if ( is_array( $result_query ) && count( $result_query ) > 0 ) {
			        $meta_key = $result_query[ 0 ]->meta_key;
			        $exploded_meta = explode( '_', $meta_key );
			        $db_version = $exploded_meta[ 5 ];
			    }
			    
			    bkap_410_update_settings( $db_version );
			    
			    $return_status = get_option( 'bkap_410_update_db_status' );
			    	
			    if ( 'success' == $return_status ) {
			        // add a dismissable admin notice
			        //       add_action( 'admin_notices', array( 'woocommerce_booking', 'bkap_update_success_notice' ) );
			    } else {
			        update_option( 'bkap_410_manual_update_count', '1' );
			    }
			    	
			    echo $return_status;
			    die;
			    
			}
			
				
			/**
			 * Adds a notice for successful DB Update.
			 * @since 4.0.0
			 */
		/*	function bkap_update_success_notice() {
			     
			    $class = 'notice notice-success is-dismissible';
			
			    $message = __( 'The database was updated successfully for Booking & Appointment plugin 4.0.0', 'woocommerce-booking' );
			     
			    printf( '<div class="%1$s">%2$s</div>', $class, $message );
			     
			} */
				
			/**
			 * This function returns the booking plugin version number
			 */
			function get_booking_version() {
				$plugin_data    = get_plugin_data( __FILE__ );
				$plugin_version = $plugin_data['Version'];
				return $plugin_version;
			}
			
            /**
             * This function detects when the booking plugin is activated 
             * and creates all the tables necessary in database,
             * if they do not exists. 
             */
			function bkap_bookings_activate() {
				
			    if ( ! self::bkap_check_woo_installed() ) {
			        return;
			    }
			    
				global $wpdb;
				
				$table_name         =   $wpdb->prefix . "booking_history";
				
				$sql                =   "CREATE TABLE IF NOT EXISTS $table_name (
                						`id` int(11) NOT NULL AUTO_INCREMENT,
                						`post_id` int(11) NOT NULL,
                  						`weekday` varchar(50) NOT NULL,
                  						`start_date` date NOT NULL,
                  						`end_date` date NOT NULL,
                						`from_time` varchar(50) NOT NULL,
                						`to_time` varchar(50) NOT NULL,
                						`total_booking` int(11) NOT NULL,
                						`available_booking` int(11) NOT NULL,
                						`status` varchar(20) NOT NULL,
                						PRIMARY KEY (`id`)
                				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1" ;
				
				$order_table_name   =   $wpdb->prefix . "booking_order_history";
				$order_sql          =   "CREATE TABLE IF NOT EXISTS $order_table_name (
            							`id` int(11) NOT NULL AUTO_INCREMENT,
            							`order_id` int(11) NOT NULL,
            							`booking_id` int(11) NOT NULL,
            							PRIMARY KEY (`id`)
            				)ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1" ;

				$table_name_price   =   $wpdb->prefix . "booking_block_price_meta";

				$sql_price          =   "CREATE TABLE IF NOT EXISTS ".$table_name_price." (
                        				`id` int(11) NOT NULL AUTO_INCREMENT,
                        				`post_id` int(11) NOT NULL,
                                        `minimum_number_of_days` int(11) NOT NULL,
                        				`maximum_number_of_days` int(11) NOT NULL,
                                        `price_per_day` double NOT NULL,
                        				`fixed_price` double NOT NULL,
                        				 PRIMARY KEY (`id`)
                        				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 " ;
				
				$table_name_meta    =   $wpdb->prefix . "booking_block_price_attribute_meta";
				
				$sql_meta           =   "CREATE TABLE IF NOT EXISTS ".$table_name_meta." (
                    					`id` int(11) NOT NULL AUTO_INCREMENT,
                    					`post_id` int(11) NOT NULL,
                    					`block_id` int(11) NOT NULL,
                    					`attribute_id` varchar(50) NOT NULL,
                    					`meta_value` varchar(500) NOT NULL,
                    					 PRIMARY KEY (`id`)
                    					) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 " ;

				$block_table_name   =   $wpdb->prefix . "booking_fixed_blocks";
				
				$blocks_sql         =   "CREATE TABLE IF NOT EXISTS ".$block_table_name." (
                        				`id` int(11) NOT NULL AUTO_INCREMENT,
                        				`global_id` int(11) NOT NULL,
                        				`post_id` int(11) NOT NULL,
                        				`block_name` varchar(50) NOT NULL,
                        				`number_of_days` int(11) NOT NULL,
                        				`start_day` varchar(50) NOT NULL,
                        				`end_day` varchar(50) NOT NULL,
                        				`price` double NOT NULL,
                        				`block_type` varchar(25) NOT NULL,
                        				PRIMARY KEY (`id`)
                        				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 " ;
				
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				
				dbDelta( $sql );
				dbDelta( $order_sql );
				dbDelta( $sql_price );
				dbDelta( $sql_meta );
				dbDelta( $blocks_sql );
				update_option( 'woocommerce_booking_alter_queries', 'yes' );
				update_option( 'woocommerce_booking_db_version', '4.7.0' );
				update_option( 'woocommerce_booking_abp_hrs', 'HOURS' );
				$check_table_query = "SHOW COLUMNS FROM $table_name LIKE 'end_date'";
				
				$results = $wpdb->get_results ( $check_table_query );
				
				if ( count( $results ) == 0 ) {
					$alter_table_query     =   "ALTER TABLE $table_name
											   ADD `end_date` date AFTER  `start_date`";
					$wpdb->get_results ( $alter_table_query );
				}
				
				$alter_block_table_query   =   "ALTER TABLE `$block_table_name` CHANGE `price` `price` DECIMAL(10,2) NOT NULL;";
				$wpdb->get_results ( $alter_block_table_query );
				

				if( ( get_option( 'book_date-label' ) == false || get_option( 'book_date-label' ) == "" ) ) {
					add_option( 'bkap_add_to_cart',			'Book Now!' );
					add_option( 'bkap_check_availability',	'Check Availability' );
				}

				//Set default labels
				add_option( 'book_date-label',          'Start Date' );
				add_option( 'checkout_date-label',      '<br>End Date' );
				add_option( 'bkap_calendar_icon_file',  'calendar1.gif' );
				add_option( 'book_time-label',          'Booking Time' );
				add_option( 'book_time-select-option',  'Choose a Time' );
				add_option( 'book_fixed-block-label',   'Select Period' );
				add_option( 'book_price-label', 'Total:' );
				
				add_option( 'book_item-meta-date',      'Start Date' );
				add_option( 'checkout_item-meta-date',  'End Date' );
				add_option( 'book_item-meta-time',      'Booking Time' );
				add_option( 'book_ics-file-name',       'Mycal' );
				
				add_option( 'book_item-cart-date',      'Start Date' );
				add_option( 'checkout_item-cart-date',  'End Date' );
				add_option( 'book_item-cart-time',      'Booking Time' );
				
				// add this option to ensure the labels above are retained in the future updates
				add_option( 'bkap_update_booking_labels_settings', 'yes' );
				
				// add the new messages in the options table
				add_option( 'book_stock-total', 'AVAILABLE_SPOTS stock total' );
				add_option( 'book_available-stock-date', 'AVAILABLE_SPOTS bookings are available on DATE' );
				add_option( 'book_available-stock-time', 'AVAILABLE_SPOTS bookings are available for TIME on DATE' );
				add_option( 'book_available-stock-date-attr', 'AVAILABLE_SPOTS ATTRIBUTE_NAME bookings are available on DATE' );
				add_option( 'book_available-stock-time-attr', 'AVAILABLE_SPOTS ATTRIBUTE_NAME bookings are available for TIME on DATE' );
					
				add_option( 'book_limited-booking-msg-date', 'PRODUCT_NAME has only AVAILABLE_SPOTS tickets available for the date DATE.' );
				add_option( 'book_no-booking-msg-date', 'For PRODUCT_NAME, the date DATE has been fully booked. Please try another date.' );
				add_option( 'book_limited-booking-msg-time', 'PRODUCT_NAME has only AVAILABLE_SPOTS tickets available for TIME on DATE.' );
				add_option( 'book_no-booking-msg-time', 'For PRODUCT_NAME, the time TIME on DATE has been fully booked. Please try another timeslot.' );
				add_option( 'book_limited-booking-msg-date-attr', 'PRODUCT_NAME has only AVAILABLE_SPOTS ATTRIBUTE_NAME tickets available for the date DATE.' );
				add_option( 'book_limited-booking-msg-time-attr', 'PRODUCT_NAME has only AVAILABLE_SPOTS ATTRIBUTE_NAME tickets available for TIME on DATE.' );
				
				add_option( 'book_real-time-error-msg', 'That date just got booked. Please reload the page.' );
				
				//Set default global booking settings
				$booking_settings                                       = new stdClass();
				$booking_settings->booking_language                     = 'en-GB';
				$booking_settings->booking_date_format                  = 'mm/dd/y';
				$booking_settings->booking_time_format                  = '12';
				$booking_settings->booking_months                       = $booking_settings->booking_calendar_day = '1';
				$booking_settings->global_booking_minimum_number_days   = '0';
				$booking_settings->booking_availability_display         = $booking_settings->minimum_day_booking = $booking_settings->booking_global_selection = $booking_settings->booking_global_timeslot = '';
				$booking_settings->booking_export                       = $booking_settings->enable_rounding = $booking_settings->woo_product_addon_price = $booking_settings->booking_global_holidays = '';
				$booking_settings->resource_price_per_day 				= '';
				$booking_settings->booking_themes                       = 'smoothness';
				$booking_settings->hide_variation_price                 = 'on';
				$booking_settings->display_disabled_buttons             = 'on';
				$booking_settings->hide_booking_price                   = '';
				
				$booking_global_settings                                = json_encode( $booking_settings );
				add_option( 'woocommerce_booking_global_settings', $booking_global_settings );
				
				// add GCal event summary & description
				add_option( 'bkap_calendar_event_summary', 'SITE_NAME, ORDER_NUMBER' );
				add_option( 'bkap_calendar_event_description', 'PRODUCT_WITH_QTY,&#13;Name: CLIENT,&#13;Contact: EMAIL, PHONE' );
				// add GCal event city
				add_option( 'bkap_calendar_event_location', 'CITY' );
			}
			
			function bkap_vertical_my_enqueue_scripts_css() {
				if ( get_post_type() == 'product' ) {
                    $plugin_version_number = get_option('woocommerce_booking_db_version');

                    bkap_load_scripts_class::bkap_load_zozo_css( $plugin_version_number );
				}
			}
			
            /**
             * This function include css files required for admin side.
             */
			function bkap_my_enqueue_scripts_css() {

			    global $post;
			    
			    $post_id = ( isset( $post->ID ) ) ? $post->ID : 0;
			    
			    $plugin_version_number = get_option( 'woocommerce_booking_db_version' );
			    
				if ( get_post_type() == 'product' ||  get_post_type() == 'bkap_resource' || 
					( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_booking_page' ) || 
					( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_history_page' ) || 
					( isset( $_GET['page'] ) && $_GET['page'] == 'operator_bookings' ) || 
					( isset($_GET['page'] ) && $_GET['page'] == 'woocommerce_availability_page' ) ) {

					bkap_load_scripts_class::bkap_load_products_css( $plugin_version_number );
				}

				if ( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_booking_page' ) {
				    // this is used for displying the settings with new CSS styles
				    wp_enqueue_style( 'bkap-global-settings-css', plugins_url( '/css/global-booking-settings.css', __FILE__ ), array() );
				    add_action( 'bkap_settings_tab_content', array( 'global_menu', 'bkap_add_review_note' ) );
				     
				}
				
				if ( ( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_booking_page' ) ||
						( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_history_page' ) ||
				        ( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_import_page' ) ||
				        ( isset( $_GET['page'] ) && $_GET['page'] == 'operator_bookings' ) ) {
					bkap_load_scripts_class::bkap_load_calendar_styles( $plugin_version_number );
				}
				
				if ( $post_id > 0 && 'bkap_booking' === get_post_type( $post_id ) ) {
				    wp_enqueue_style( 'bkap-edit-bookings', plugins_url( '/css/edit-booking.css', __FILE__ ) );
				}
			}
			
            /**
             * This function includes js files required for admin side.
             */
			function bkap_my_enqueue_scripts_js() {

				$plugin_version_number = get_option( 'woocommerce_booking_db_version' );
				
				wp_register_script( 'bkap-update', plugins_url().'/woocommerce-booking/js/bkap-update.js', '', $plugin_version_number, false );
				
				$ajax_url = get_admin_url() . 'admin-ajax.php';
				$settings_url = get_admin_url() . 'edit.php?post_type=bkap_booking&page=woocommerce_booking_page';
				$support_msg = __( 'The database update has failed. Request you to kindly contact ', 'woocommerce-booking' );
				$support_msg .= '<a href="https://www.tychesoftwares.com/forums/forum/woocommerce-booking-appointment-plugin/">' . __( 'support', 'woocommerce-booking' ) . '</a>';
				$support_msg .= __( ' at Tyche Softwares.', 'woocommerce-booking' );
				
				$success_msg = __( 'The database update was successful.', 'woocommerce-booking' );
				
				$progress_msg = "<p>Updating the database for $plugin_version_number. This may take a while. Please do not refresh the page until further notification.</p>";
				$progress_msg = __( $progress_msg, 'woocommerce-booking' );
				
				$progress_msg_f_p = '<p>Updating the database for v4.1.0. This may take a while. Please do not refresh the page until further notification.</p>';
				$progress_msg_f_p = __( $progress_msg_f_p, 'woocommerce-booking' );
				
				wp_localize_script( 'bkap-update', 'bkap_update_params', array(
					'settings_url'      => $settings_url,
					'ajax_url'          => $ajax_url,
					'support_request'   => $support_msg,
					'success_msg'       => $success_msg,
					'progress'          => $progress_msg,
					'progress_f_p'      => $progress_msg_f_p) );
				
				wp_enqueue_script( 'bkap-update' );
				
            	if ( get_post_type() == 'product'  || get_post_type() == 'bkap_resource' || 
            		( isset ( $_GET['page'] ) && $_GET['page'] == 'woocommerce_booking_page' ) || 
            		( isset ( $_GET['page'] ) && $_GET['page'] == 'woocommerce_availability_page' ) ) {

            	    bkap_load_scripts_class::bkap_common_admin_scripts_js( $plugin_version_number );
				}

				// this file needs to be included only on the admin product page
				if ( get_post_type() == 'product' ) {

					bkap_load_scripts_class::bkap_load_product_scripts_js( $plugin_version_number, $ajax_url );
				}

				// below files are only to be included on booking settings page
				if ( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_booking_page' ) {
					wp_register_script( 'bkap-woocommerce_admin', plugins_url() . '/woocommerce/assets/js/admin/woocommerce_admin.js', array( 'jquery', 'jquery-ui-widget', 'jquery-ui-core' ), $plugin_version_number , false );
					wp_enqueue_script( 'bkap-woocommerce_admin' );
					wp_enqueue_script( 'bkap-themeswitcher', plugins_url( '/js/jquery.themeswitcher.min.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker' ), $plugin_version_number, false );
					wp_enqueue_script( "bkap-lang", plugins_url( "/js/i18n/jquery-ui-i18n.js", __FILE__ ), '', $plugin_version_number, false );
					wp_enqueue_script( 'bkap-jquery-tip', plugins_url( '/js/jquery.tipTip.minified.js', __FILE__ ), '', $plugin_version_number, false );
				}
				
				if( ( isset( $_GET['page'] ) && $_GET['page'] == 'woocommerce_history_page' ) || 
					( isset( $_GET['page'] ) && $_GET['page'] == 'operator_bookings' ) || 
					( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'bkap_booking' ) ) {

					bkap_load_scripts_class::bkap_load_calendar_scripts( $plugin_version_number );

					wp_enqueue_script( 'bkap-view-booking', plugins_url( '/js/bkap-view-booking.js', __FILE__ ), '', '', false );
					$this->bkap_localize_view_booking();
				}

				/*
				 * Including JS & CSS file for Booking Resources.
				 */

				if ( get_post_type() == 'bkap_resource' || get_post_type() == 'product' ) {					

    				bkap_load_scripts_class::bkap_load_resource_scripts_js( $plugin_version_number, $ajax_url );
				}
			}
			
			public static function localize_script( $vendor_id = '' ){

			    $events_json = plugins_url().'/woocommerce-booking/adminend-events-jsons.php';

			    if ( isset( $vendor_id ) && $vendor_id !== '' ) {
			    	$events_json .= '?vendor_id=' . $vendor_id;
			    }

			    $js_vars                 = array();
			    $schema                  = is_ssl() ? 'https':'http';
			    $js_vars['ajaxurl']      = admin_url( 'admin-ajax.php', $schema );
			    $js_vars['pluginurl']    = $events_json;
			    wp_localize_script( 'booking-calender-js', 'bkap', $js_vars );
			}

			public static function bkap_localize_view_booking() {
				
				$bkap_view_booking = array();
				$bkap_view_booking['labels'] = array(
					'print_label' => __( 'Print', 'woocommerce-booking' ),
					'csv_label' => __( 'CSV', 'woocommerce-booking' ),
					'calendar_label' => __( 'Calendar View', 'woocommerce-booking' )
				);

				if ( current_user_can( 'operator_bookings' ) ) {
					$bkap_view_booking['url'] = array(
						'print_url' => esc_url( add_query_arg( array( 'download' => 'data.print' ) ) ),
						'csv_url' => esc_url( add_query_arg( array( 'download' => 'data.csv' ) ) ),
						'calendar_url' => esc_url( get_admin_url( null, 'edit.php?post_type=bkap_booking&page=woocommerce_history_page&booking_view=booking_calender') ),
					);
				}else {
					$bkap_view_booking['url'] = array(
						'print_url' => esc_url( add_query_arg( array( 'download' => 'data.print' ) ) ),
						'csv_url' => esc_url( add_query_arg( array( 'download' => 'data.csv' ) ) ),
						'calendar_url' => esc_url( get_admin_url( null, 'edit.php?post_type=bkap_booking&page=woocommerce_history_page&booking_view=booking_calender') ),
					);
				}
				wp_localize_script( 'bkap-view-booking', 'bkap_view_booking', $bkap_view_booking );
			}
			
			/**
			 * Called during AJAX request for qtip content for a calendar item
			 */
			public static function bkap_booking_calender_content(){
			    $content         = '';
			    $date_formats    = bkap_get_book_arrays( 'bkap_date_formats' );
			    // get the global settings to find the date formats
			    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			    $date_format_set = $date_formats[ $global_settings->booking_date_format ];

			    if( !empty( $_REQUEST['order_id'] ) && ! empty( $_REQUEST[ 'event_value' ] ) ){
                    $order_id                    =   $_REQUEST[ 'order_id' ];
			        $order                       =   new WC_Order( $order_id );
 			        
			        $order_items                 =   $order->get_items();
			        $attribute_name              =   '';
			        $attribute_selected_value    =   '';
			        
			        if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
			            $billing_first_name          =   $order->billing_first_name;
			            $billing_last_name           =   $order->billing_last_name;
			        } else {
			            $billing_first_name          =   $order->get_billing_first_name();
			            $billing_last_name           =   $order->get_billing_last_name();
			        }
			        
			        $value[]                     =   $_REQUEST[ 'event_value' ];

			        $content                     =   "<table>
                    			                     <tr> <td> <strong>Order: </strong></td><td><a href=\"post.php?post=". $order_id ."&action=edit\">#". $order_id ." </a> </td> </tr>
                    			                     <tr> <td> <strong>Product Name:</strong></td><td> ".get_the_title( $value[0]['post_id'] )."</td> </tr>
                    			                     <tr> <td> <strong>Customer Name:</strong></td><td> ".$billing_first_name . " " . $billing_last_name ."</td> </tr>
                    			                     " ;
			        
			        foreach ( $order_items as $item_id => $item ) {
			             
			            if ( $item[ 'variation_id' ] != '' && $value[ 0 ][ 'post_id' ] == $item[ 'product_id' ] && $value[ 0 ][ 'order_item_id' ] == $item_id ){
			                $variation_product               = get_post_meta( $item[ 'product_id' ] );
			                $product_variation_array_string  = $variation_product[ '_product_attributes' ];
			                $product_variation_array         = unserialize( $product_variation_array_string[0] );
			                 
			                foreach ( $product_variation_array as $product_variation_key => $product_variation_value ) {		
			                    if ( isset( $item[ $product_variation_key ] ) && '' !== $item[ $product_variation_key ] ){
							
			                        $attribute_name              = $product_variation_value[ 'name' ];
			                        $attribute_selected_value    = $item [ $product_variation_key ];
			                        $content                    .= " <tr> <td> <strong>".$attribute_name.":</strong></td> <td> ".$attribute_selected_value."</td> </tr> ";
			                    }
			                }
			            }
			                
			            if ( $item[ 'qty' ] != '' && $value[ 0 ][ 'post_id' ] == $item[ 'product_id' ] && $value[ 0 ][ 'order_item_id' ] == $item_id ){
							$content  .= " <tr> <td> <strong>Quantity:</strong></td> <td> ".$item[ 'qty' ]."</td> </tr> ";
						}
			            
			        }	        	
			        if ( isset( $value[ 0 ][ 'start_date' ] ) && $value[ 0 ][ 'start_date' ] != '' ){
			            $value_date  = $value[ 0 ][ 'start_date' ];
			            $content    .= " <tr> <td> <strong>Start Date:</strong></td><td> ".$value_date."</td> </tr>";
			        }
			        	
			        if ( isset( $value[ 0 ][ 'end_date' ] ) && $value[ 0 ][ 'end_date' ] != '' ){
			            $value_end_date  = $value[ 0 ][ 'end_date' ];
			            $content        .= " <tr> <td> <strong>End Date:</strong></td><td> ".$value_end_date."</td> </tr> ";
			        }
			        	
			        // Booking Time
			        $time = '';
			        if ( isset( $value[ 0 ][ 'from_time' ] ) && $value[ 0 ][ 'from_time' ] != "" && isset( $value[ 0 ][ 'to_time' ] ) && $value[0]['to_time'] != "" ) {
			        if ( $global_settings->booking_time_format == 12 ) {
			                $to_time     = '';
			                $from_time   = date( 'h:i A', strtotime( $value[0]['from_time'] ) );
			                $time        = $from_time ;
			                
			                if ( isset( $value[0]['to_time'] ) && $value[0]['to_time'] != '' ){
			                    $to_time = date( 'h:i A', strtotime( $value[0]['to_time'] ) );
			                    $time    = $from_time . " - " . $to_time;
			                }
			                 
			            }else {
			                $time = $time = $value[0]['from_time'] . " - " . $value[0]['to_time'];
			            }
			            
			            $content .= "<tr> <td> <strong>Time:</strong></td><td> ".$time."</td> </tr>";
			            
			        }else if ( isset( $value[ 0 ][ 'from_time' ] ) && $value[ 0 ][ 'from_time' ] != "" ) {
			        if ( $global_settings->booking_time_format == 12 ) {
			                
			                $to_time = '';
			                $from_time = date( 'h:i A', strtotime( $value[0]['from_time'] ) );
			                $time = $from_time. " - Open-end" ;
			            }else {
			                $time = $time = $value[0]['from_time'] ." - Open-end";
			            }
			            $content .= "<tr> <td> <strong>Time:</strong></td><td> ".$time."</td> </tr>";
			        }
			        
			        if ( isset( $value[ 0 ][ 'resource' ] ) && $value[ 0 ][ 'resource' ] != '' ){
			            $value_resource  = $value[ 0 ][ 'resource' ];
			            $content        .= " <tr> <td> <strong>Resource:</strong></td><td> ".$value_resource."</td> </tr> ";
			        }
			        
			        $content .= '</table>';
			        	
			        if ( $value[0]['post_id'] ){
			            $post_image = get_the_post_thumbnail( $value[0]['post_id'], array( 100, 100 ) );
			            
			            if ( !empty( $post_image ) ){
			                $content = '<div style="float:left; margin:0px 5px 5px 0px; ">'.$post_image.'</div>'.$content;
			            }
			        }
			    }

			    echo $content;
			    die();
			}
                        
            /**
             * This function includes js files required for frontend.
             */
			
			function bkap_front_side_scripts_js() {
				global $post;
				if ( is_product() || is_page() ) {
				    
					self::include_frontend_scripts_js( $post->ID );
				}
			}

			static function include_frontend_scripts_js( $product_id ) {

				$duplicate_of     =   bkap_common::bkap_get_product_id( $product_id );
				$booking_settings =   get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
				
				if ( isset( $booking_settings[ 'booking_enable_date' ] ) && $booking_settings[ 'booking_enable_date' ] == 'on' ) {
					$plugin_version_number = get_option( 'woocommerce_booking_db_version' );

					wp_enqueue_script( 'jquery' );
					wp_enqueue_script( 'jquery-ui-datepicker' );
					if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
						if( ICL_LANGUAGE_CODE == 'en' ) {
							$curr_lang = "en-GB";
						} else{
							$curr_lang = ICL_LANGUAGE_CODE;
						}
					} else {
						$current_language = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
						if ( isset( $current_language ) ) {
							$curr_lang = $current_language->booking_language;
						} else {
							$curr_lang = "";
						}
						if ( $curr_lang == "" ) {
							$curr_lang = "en-GB";
						}
					}
					wp_enqueue_script( "$curr_lang", plugins_url( "/js/i18n/jquery.ui.datepicker-$curr_lang.js", __FILE__ ), '', $plugin_version_number, false );
				}
			}
			
            /**
             * This function includes css files required for frontend.
             */
			function bkap_front_side_scripts_css() {
				global $post;
				if ( is_product() || is_page()) {
				    
					self::inlcude_frontend_scripts_css( $post->ID );
				}
			}

			static function inlcude_frontend_scripts_css( $product_id ){

				$duplicate_of     =   bkap_common::bkap_get_product_id( $product_id );
				$booking_settings =   get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
				
				if ( isset( $booking_settings[ 'booking_enable_date' ] ) && $booking_settings[ 'booking_enable_date' ] == 'on' ) {
					$plugin_version_number    = get_option( 'woocommerce_booking_db_version' );
					$calendar_theme           = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					$calendar_theme_sel       = "";
					if ( isset( $calendar_theme ) ) {
						$calendar_theme_sel = $calendar_theme->booking_themes;
					}
					if ( $calendar_theme_sel == "" ) {
						$calendar_theme_sel = 'base';
					}
					wp_register_style( 'bkap-jquery-ui', "//code.jquery.com/ui/1.9.2/themes/$calendar_theme_sel/jquery-ui.css", '', $plugin_version_number, false );

					wp_deregister_style( 'jquery-ui' );
					
					wp_enqueue_style( 'bkap-jquery-ui' );
					wp_enqueue_style( 'bkap-booking', plugins_url( '/css/booking.css', __FILE__ ) , '', $plugin_version_number, false );
				}
			}
			
           /**
            * This function returns the number of bookings done for a date.
            */
			function bkap_get_date_lockout( $start_date ) {
				global $wpdb, $post;
				$duplicate_of       =   bkap_common::bkap_get_product_id( $post->ID );
				
				$date_lockout       =   "SELECT sum(total_booking) - sum(available_booking) AS bookings_done FROM `".$wpdb->prefix."booking_history`
								        WHERE start_date= %s AND post_id= %d";
					
				$results_date_lock  = $wpdb->get_results( $wpdb->prepare( $date_lockout, $start_date, $duplicate_of ) );
					
				$bookings_done      = $results_date_lock[0]->bookings_done;
				return $bookings_done;
			}
                      
		   /**
            * This function updates to "Inactive" a single time slot 
            * from View/Delete Booking date, Timeslots.
            */
			function bkap_remove_time_slot() {
				global $wpdb;
				
				if( isset( $_POST['details'] ) ) {
					$details       = explode( "&", $_POST['details'] );
				
					$date_delete   = $details[2];
					$date_db       = date( 'Y-m-d', strtotime( $date_delete ) );
					$id_delete     = $details[0];
					$book_details  = get_post_meta( $details[1], 'woocommerce_booking_settings', true );
				
					if ( is_array( $book_details[ 'booking_time_settings' ] ) && count( $book_details[ 'booking_time_settings' ] > 0 ) ) {
    					unset( $book_details[ 'booking_time_settings' ][ $date_delete ][ $id_delete ] );
    					
    					if ( count( $book_details[ 'booking_time_settings' ][ $date_delete ] ) == 0 ) {
    						
    					    unset( $book_details[ 'booking_time_settings' ][ $date_delete ] );
    						
    						if ( substr( $date_delete, 0, 7 ) == "booking" ) {
    							$book_details[ 'booking_recurring' ][ $date_delete ] = '';
    						} elseif ( substr( $date_delete, 0, 7 ) != "booking" ) {
    							$key_date = array_search( $date_delete, $book_details[ 'booking_specific_date' ] );
    							unset( $book_details[ 'booking_specific_date' ][ $key_date ] );
    						}
    					}
    					update_post_meta( $details[1], 'woocommerce_booking_settings', $book_details );
					}
				
					if ( substr( $date_delete, 0, 7 ) != "booking" ) {
						if ( $details[4] == "0:00" ) {
							$details[4] = "";
						}
					
						$update_status_query  =   "UPDATE `".$wpdb->prefix."booking_history`
            										SET status = 'inactive'
            										WHERE post_id = '".$details[1]."'
            									 	AND start_date = '".$date_db."'
            									 	AND from_time = '".$details[3]."'
            									 	AND to_time = '".$details[4]."' ";
					
						$wpdb->query( $update_status_query );
						
					}
					elseif ( substr( $date_delete, 0, 7 ) == "booking" ) {
						if ( $details[4] == "0:00" ) {
							$details[4] = "";
						}
				
						$update_status_query  =   "UPDATE `".$wpdb->prefix."booking_history`
            										SET status = 'inactive'
            										WHERE post_id = '".$details[1]."'
            										AND weekday = '".$date_delete."'
            										AND from_time = '".$details[3]."'
            										AND to_time = '".$details[4]."' ";
						$wpdb->query( $update_status_query );
						
						// delete the base record for the recurring weekday
						$delete_base_query  =   "DELETE FROM `".$wpdb->prefix."booking_history`
            										WHERE post_id = '".$details[1]."'
            										AND weekday = '".$date_delete."'
        										    AND start_date = '0000-00-00'
            										AND from_time = '".$details[3]."'
            										AND to_time = '".$details[4]."' ";
						$wpdb->query( $delete_base_query );
					}
            	}	
			}
			
		   /**
            * This function updates to "Inactive" a single day 
            * from View/Delete Booking date, Timeslots.
            */
	/*		function bkap_remove_day() {
			
				global $wpdb;
			
				if ( isset( $_POST['details'] ) ) {
				
				    $details        = explode( "&", $_POST['details'] );
    				$date_delete    = $details[0];
    				$book_details   = get_post_meta( $details[1], 'woocommerce_booking_settings', true );
				
				if ( substr( $date_delete, 0, 7 ) != "booking" ) {
					$date_db = date( 'Y-m-d', strtotime( $date_delete ) );
					
					if ( is_array( $book_details[ 'booking_specific_date' ] ) ) {
    					$key_date = array_search( $date_delete, $book_details[ 'booking_specific_date' ] );
    					unset( $book_details[ 'booking_specific_date' ][ $key_date ] );
    					
    					$update_status_query   =   "UPDATE `".$wpdb->prefix."booking_history`
        											SET status = 'inactive'
        											WHERE post_id = '".$details[1]."'
        											AND start_date = '".$date_db."'";
    					$wpdb->query( $update_status_query );
					}
						
				} elseif ( substr( $date_delete, 0, 7 ) == "booking" ) {
				    if ( is_array( $book_details[ 'booking_recurring' ] ) && isset( $book_details[ 'booking_recurring' ][ $date_delete ] ) ) {
    					$book_details[ 'booking_recurring' ][ $date_delete ] = '';
    					$update_status_query = "UPDATE `".$wpdb->prefix."booking_history`
    											SET status = 'inactive'
    											WHERE post_id = '".$details[1]."'
    											AND weekday = '".$date_delete."'";
    					$wpdb->query( $update_status_query );
    					
    					// Delete the base records for the recurring weekdays
    					$delete_base_query  =   "DELETE FROM `".$wpdb->prefix."booking_history`
            										WHERE post_id = '".$details[1]."'
            										AND weekday = '".$date_delete."'
        										    AND start_date = '0000-00-00'";
    						
    					$wpdb->query( $delete_base_query );
				    }
						
				}
				update_post_meta( $details[1], 'woocommerce_booking_settings', $book_details );
				}
			}
		*/	
	   /**
        * This function updates all dates to "Inactive" from View/Delete Booking date, 
        * Timeslots of specific day method.
        */	
		function bkap_remove_specific() {
				
				global $wpdb;
				
				if( isset( $_POST['details'] ) ) {
				
				    $details        = $_POST['details'];
				    $book_details   = get_post_meta( $details, 'woocommerce_booking_settings', true );
			
    				foreach ( $book_details[ 'booking_specific_date' ] as $key => $value ) {
    					if ( array_key_exists( $value, $book_details[ 'booking_time_settings' ] ) ) unset( $book_details[ 'booking_time_settings' ][ $value ] );
    				}
    				unset( $book_details[ 'booking_specific_date' ] );
    				update_post_meta( $details, 'woocommerce_booking_settings', $book_details );
    
    				$update_status_query    =   "UPDATE `".$wpdb->prefix."booking_history`
        										SET status = 'inactive'
        										WHERE post_id = '".$details."'
        										AND weekday = ''";
    				$wpdb->query( $update_status_query );
				}
			}
		   
		   /**
            * This function updates all days to "Inactive"  from View/Delete Booking 
            * date, Timeslots of recurring day method.
            */
			function bkap_remove_recurring() {		
				global $wpdb;
				
				if( isset( $_POST['details'] ) ) {
				$details        = $_POST['details'];
				$book_details   = get_post_meta( $details, 'woocommerce_booking_settings', true );
				$weekdays       = bkap_get_book_arrays( 'bkap_weekdays' );
				
				foreach ( $weekdays as $n => $day_name ) {
					
				    if ( array_key_exists($n,$book_details[ 'booking_time_settings' ] ) ) {
						unset( $book_details[ 'booking_time_settings' ][ $n ] );
					}
					
					$book_details[ 'booking_recurring' ][ $n ] =   '';
			
					$update_status_query                   =   "UPDATE `".$wpdb->prefix."booking_history`
                    											SET status = 'inactive'
                    											WHERE post_id = '".$details."'
                    											AND weekday = '".$n."'";
					$wpdb->query( $update_status_query );
				}
				
				update_post_meta( $details, 'woocommerce_booking_settings', $book_details );
				}
			
			}
			/*
			 * This function used to register Fixed block booking's Block name string to WPML
			 * Like : Body, subject, Wc header text
			 *
			 * Since : 2.5.3
			 */
			function bkap_register_fixed_block_string_for_wpml() {
			
			    if ( function_exists('icl_register_string') ) {
			
			        global $wpdb;
			        $context             = 'woocommerce-booking';
			        $fixed_block_table   = $wpdb->prefix . 'booking_fixed_blocks';
			        $result              = $wpdb->get_results("SELECT * FROM $fixed_block_table");
			        
			        foreach ($result as $each_block) {
			            
			            $name_msg = 'bkap_fixed_' . $each_block->id . '_block_name';
			            $value_msg = $each_block->block_name;
			            			            
			            icl_register_string( $context, $name_msg, $value_msg ); //for registering message
			

			       }
			    }
			}

			/**
			 * Function to load the Edit Booking Class to perform Bookings Edit on Cart and Checkout Page
			 *
			 * @since 4.1.0
			 */
			public function bkap_load_edit_bookings_class( $global_settings ) {
			
			    include_once( 'class-bkap-edit-bookings.php');
			    $edit_booking_class = new bkap_edit_bookings_class( $global_settings );
			}
			
			/**
			 * @since 4.1.0
			 */
			function bkap_init_post_types() {
			    register_post_type( 'bkap_booking',
				    apply_filters( 'bkap_register_post_type_bkap_booking',
					    array(
						    'label'                      => __( 'Booking', 'woocommerce-booking' ),
						    'labels'                     => array(
							    'name'               => __( 'Booking', 'woocommerce-booking' ),
							    'singular_name'      => __( 'Booking', 'woocommerce-booking' ),
							    'add_new'            => __( 'Create Booking', 'woocommerce-booking' ),
							    'add_new_item'       => __( 'Add New Booking', 'woocommerce-booking' ),
							    'edit'               => __( 'Edit', 'woocommerce-booking' ),
							    'edit_item'          => __( 'Edit Booking', 'woocommerce-booking' ),
							    'new_item'           => __( 'New Booking', 'woocommerce-booking' ),
							    'view'               => __( 'View Booking', 'woocommerce-booking' ),
							    'view_item'          => __( 'View Booking', 'woocommerce-booking' ),
							    'search_items'       => __( 'Search Bookings', 'woocommerce-booking' ),
							    'not_found'          => __( 'No Bookings found', 'woocommerce-booking' ),
							    'not_found_in_trash' => __( 'No Bookings found in trash', 'woocommerce-booking' ),
							    'parent'             => __( 'Parent Bookings', 'woocommerce-booking' ),
							    'menu_name'          => _x( 'Booking', 'Admin menu name', 'woocommerce-booking' ),
							    'all_items'          => __( 'View Bookings', 'woocommerce-booking' ),
						    ),
						    'description'                => __( 'This is where bookings are stored.', 'woocommerce-booking' ),
						    'public'                     => true,
						    'show_ui'                    => true,
						    'capability_type'            => 'post',
						    /*'capabilities' => array(
						  //        'create_posts' => true,
    						    'edit_post' => 'edit_bkap_booking',
    						    'read_post' => 'read_bkap_booking',
    						    'delete_post' => 'delete_bkap_booking',
    						    'edit_posts' => 'edit_bkap_bookings',
    						    'edit_others_posts' => 'edit_others_bkap_bookings',
    						    'publish_posts' => 'publish_bkap_bookings',
    						    'read_private_posts' => 'read_private_bkap_bookings',
    						    'read'                   => "read",
    						    'delete_posts' => 'delete_bkap_bookings',
    						    'delete_private_posts'   => "delete_private_bkap_bookings",
    						    'delete_published_posts' => "delete_published_bkap_bookings",
    						    'delete_others_posts' => 'delete_others_bkap_bookings',
    						    'edit_private_posts'     => "edit_private_bkap_bookings",
    						    'edit_published_posts'   => "edit_published_bkap_bookings",
						        'create_posts' => 'edit_bkap_bookings',
						        'read_others_posts' => 'read_others_bkap_bookings',
						    ),*/
						    'map_meta_cap'               => true,
						    'supports'                   => array( '' ),
						    'menu_icon'                  => 'dashicons-calendar-alt',
						    'show_in_nav_menus'          => true,
						    'publicly_queryable'         => true,
						    'exclude_from_search'        => false,
						    'has_archive'                => true,
						    'query_var'                  => true,
						    'can_export'                 => true,
						    'rewrite'                    => false,
						    'show_in_menu'               => true,
						    'hierarchical'               => false,
						    'show_in_rest'               => true,
					    )
				    )
			    );

			    /**
			     * Post status
			    */
			    register_post_status( 'paid', array(
			    'label'                     => '<span class="status-paid tips" data-tip="' . _x( 'Paid &amp; Confirmed', 'woocommerce-booking', 'woocommerce-booking' ) . '">' . _x( 'Paid &amp; Confirmed', 'woocommerce-booking', 'woocommerce-booking' ) . '</span>',
			    'public'                    => true,
			    'exclude_from_search'       => false,
			    'show_in_admin_all_list'    => true,
			    'show_in_admin_status_list' => true,
			    'label_count'               => _n_noop( 'Paid &amp; Confirmed <span class="count">(%s)</span>', 'Paid &amp; Confirmed <span class="count">(%s)</span>', 'woocommerce-booking' ),
			    ) );
			    register_post_status( 'confirmed', array(
			    'label'                     => '<span class="status-confirmed tips" data-tip="' . _x( 'Confirmed', 'woocommerce-booking', 'woocommerce-booking' ) . '">' . _x( 'Confirmed', 'woocommerce-booking', 'woocommerce-booking' ) . '</span>',
			    'public'                    => true,
			    'exclude_from_search'       => false,
			    'show_in_admin_all_list'    => true,
			    'show_in_admin_status_list' => true,
			    'label_count'               => _n_noop( 'Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>', 'woocommerce-booking' ),
			    ) );
			    
			    register_post_status(    'pending-confirmation', 
			                             array( 'label'                     => '<span class="status-pending tips" data-tip="' . _x( 'Pending Confirmation', 'woocommerce-booking', 'woocommerce-booking' ) . '">' . _x( 'Pending Confirmation', 'woocommerce-booking', 'woocommerce-booking' ) . '</span>',
                                			    'public'                    => true,
                                			    'exclude_from_search'       => false,
                                			    'show_in_admin_all_list'    => true,
                                			    'show_in_admin_status_list' => true,
                                			    'label_count'               => _n_noop( 'Pending Confirmation <span class="count">(%s)</span>', 'Pending Confirmation <span class="count">(%s)</span>', 'woocommerce-booking' ),
			                             )
			    );
			    register_post_status(    'cancelled',
			                             array( 'label'                     => '<span class="status-cancelled tips" data-tip="' . _x( 'Cancelled', 'woocommerce-booking', 'woocommerce-booking' ) . '">' . _x( 'Cancelled', 'woocommerce-booking', 'woocommerce-booking' ) . '</span>',
                                			    'public'                    => true,
                                			    'exclude_from_search'       => false,
                                			    'show_in_admin_all_list'    => true,
                                			    'show_in_admin_status_list' => true,
                                			    'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'woocommerce-booking' ),
			                             )
			    );
			    
			    // Registering post type for Google Calendar Events.
			    
			    register_post_type( 'bkap_gcal_event', 
			        apply_filters( 'bkap_register_post_type_bkap_gcal_event',
			            array(
        			    'label'              => __( 'Import Bookings', 'woocommerce-booking' ),
        			    'labels'             => array(
                                    			    'name'               => __( 'Google Event', 'woocommerce-booking' ),
                                    			    'singular_name'      => __( 'Google Event', 'woocommerce-booking' ),
                                    			    'add_new'            => __( 'Add Google Event', 'woocommerce-booking' ),
                                    			    'add_new_item'       => __( 'Add New Google Event', 'woocommerce-booking' ),
                                    			    'edit'               => __( 'Edit', 'woocommerce-booking' ),
                                    			    'edit_item'          => __( 'Edit Google Event', 'woocommerce-booking' ),
                                    			    'new_item'           => __( 'New Google Event', 'woocommerce-booking' ),
                                    			    'view'               => __( 'Import Bookings', 'woocommerce-booking' ),
                                    			    'view_item'          => __( 'View Google Event', 'woocommerce-booking' ),
                                    			    'search_items'       => __( 'Search Google Event', 'woocommerce-booking' ),
                                    			    'not_found'          => __( 'No Google Event found', 'woocommerce-booking' ),
                                    			    'not_found_in_trash' => __( 'No Google Event found in trash', 'woocommerce-booking' ),
                                    			    'parent'             => __( 'Parent Google Events', 'woocommerce-booking' ),
                                    			    'menu_name'          => _x( 'Google Event', 'Admin menu name', 'woocommerce-booking' ),
                                    			    'all_items'          => __( 'Import Booking', 'woocommerce-booking' ),
                                    			    ),
        			    'description'                => __( 'This is where bookings are stored.', 'woocommerce-booking' ),
        			    'public'                     => false,
        			    'show_ui'                    => true,
        			    'capability_type'            => 'post',
        			    'capabilities' => array( 'create_posts' => 'do_not_allow', // will have to be removed oncce we show the custom post type
                                			    ),
        			    'map_meta_cap'               => true,
        			    'publicly_queryable'         => false,
        			    'exclude_from_search'        => true,
        			    'show_in_menu'               => 'edit.php?post_type=bkap_booking',
        			    'hierarchical'               => false,
        			    'show_in_nav_menus'          => false,
        			    'rewrite'                    => false,
        			    'query_var'                  => false,
        			    'supports'                   => array( '' ),
        			    'has_archive'                => false,
        			    'menu_icon'                  => 'dashicons-calendar-alt',
        			    )
			        )
			    );
			
			    // Registering the status of the Google Calendar Events
			    register_post_status( 'bkap-unmapped',
			                          array( 'label'                     => '<span class="status-un-mapped tips" data-tip="' . _x( 'Un-mapped', 'woocommerce-booking', 'woocommerce-booking' ) . '">' . _x( 'Un-mapped', 'woocommerce-booking', 'woocommerce-booking' ) . '</span>',
                            			     'public'                    => true,
                            			     'exclude_from_search'       => false,
                            			     'show_in_admin_all_list'    => true,
                            			     'show_in_admin_status_list' => true,
                            			     'label_count'               => _n_noop( 'Un-mapped <span class="count">(%s)</span>', 'Un-mapped <span class="count">(%s)</span>', 'woocommerce-booking' ),
			                          )
			    );
			    
			    register_post_status( 'bkap-mapped',
			                          array( 'label'                     => '<span class="status-mapped tips" data-tip="' . _x( 'Mapped', 'woocommerce-booking', 'woocommerce-booking' ) . '">' . _x( 'Mapped', 'woocommerce-booking', 'woocommerce-booking' ) . '</span>',
                            			     'public'                    => true,
                            			     'exclude_from_search'       => false,
                            			     'show_in_admin_all_list'    => true,
                            			     'show_in_admin_status_list' => true,
                            			     'label_count'               => _n_noop( 'Mapped <span class="count">(%s)</span>', 'Mapped <span class="count">(%s)</span>', 'woocommerce-booking' ),
			                          )
			    );
			    
			    register_post_status( 'bkap-deleted',
                        			  array( 'label'                     => '<span class="status-deleted tips" data-tip="' . _x( 'Deleted', 'woocommerce-booking', 'woocommerce-booking' ) . '">' . _x( 'Deleted', 'woocommerce-booking', 'woocommerce-booking' ) . '</span>',
                                			 'public'                    => true,
                                			 'exclude_from_search'       => false,
                                			 'show_in_admin_all_list'    => true,
                                			 'show_in_admin_status_list' => true,
                                			 'label_count'               => _n_noop( 'Deleted <span class="count">(%s)</span>', 'Mapped <span class="count">(%s)</span>', 'woocommerce-booking' ),
                        			  )
			    );
			    
			    /*
			     *  Booking Resources Post Type
			     */
			    
			    register_post_type( 'bkap_resource',
    			    apply_filters( 'bkap_register_post_type_resource',
        			    array(
            			    'label'  => __( 'Booking Resources', 'woocommerce-booking' ),
            			    'labels' => array(
                            			    'name'               => __( 'Bookable resource', 'woocommerce-booking' ),
                            			    'singular_name'      => __( 'Bookable resource', 'woocommerce-booking' ),
                            			    'add_new'            => __( 'Add Resource', 'woocommerce-booking' ),
                            			    'add_new_item'       => __( 'Add New Resource', 'woocommerce-booking' ),
                            			    'edit'               => __( 'Edit', 'woocommerce-booking' ),
                            			    'edit_item'          => __( 'Edit Resource', 'woocommerce-booking' ),
                            			    'new_item'           => __( 'New Resource', 'woocommerce-booking' ),
                            			    'view'               => __( 'View Resource', 'woocommerce-booking' ),
                            			    'view_item'          => __( 'View Resource', 'woocommerce-booking' ),
                            			    'search_items'       => __( 'Search Resource', 'woocommerce-booking' ),
                            			    'not_found'          => __( 'No Resource found', 'woocommerce-booking' ),
                            			    'not_found_in_trash' => __( 'No Resource found in trash', 'woocommerce-booking' ),
                            			    'parent'             => __( 'Parent Resources', 'woocommerce-booking' ),
                            			    'menu_name'          => _x( 'Resources', 'Admin menu name', 'woocommerce-booking' ),
                            			    'all_items'          => __( 'Resources', 'woocommerce-booking' ),
                            			),
            			    'description' 			=> __( 'Bookable resources are bookable within a bookings product.', 'woocommerce-booking' ),
            			    'public' 				=> false,
            			    'show_ui' 				=> true,
            			    'capability_type' 		=> 'product',
            			    'map_meta_cap'			=> true,
            			    'publicly_queryable' 	=> false,
            			    'exclude_from_search' 	=> true,
            			    'show_in_menu' 			=> true,
            			    'hierarchical' 			=> false,
            			    'show_in_nav_menus' 	=> false,
            			    'rewrite' 				=> false,
            			    'query_var' 			=> false,
            			    'supports' 				=> array( 'title' ),
            			    'has_archive' 			=> false,
            			    'show_in_menu' 			=> 'edit.php?post_type=bkap_booking',
        			    )
    			    )
			    );
			    	
			}

			function bkap_change_create_booking_link( $url, $path ) {

				if( $path === 'post-new.php?post_type=bkap_booking' ) {
					$url = esc_url( 'edit.php?post_type=bkap_booking&page=bkap_create_booking_page' );
				}
				return $url;
			}
			
			/**
			 * @since 4.1.0
			 */
			function bkap_add_meta_boxes() {
			
			    $meta_boxes = array(
			        include( 'templates/meta-boxes/class-bkap-customer-meta-box.php' ),
			        include( 'templates/meta-boxes/class-bkap-details-meta-box.php' ),
			        include( 'templates/meta-boxes/class-bkap-save-meta-box.php' ),
			        include( 'templates/meta-boxes/class-bkap-resource-details-meta-box.php' )
			    );
			
			    foreach ( $meta_boxes as $meta_box ) {
			        foreach ( $meta_box->post_types as $post_type ) {
			            add_meta_box(
			            $meta_box->id,
			            $meta_box->title,
			            array( $meta_box, 'meta_box_inner' ),
			            $post_type,
			            $meta_box->context,
			            $meta_box->priority
			            );
			        }
			    }
			    	
			}
				
			public function bkap_remove_submitdiv() {
			    remove_meta_box( 'submitdiv', 'bkap_booking', 'side' );
			}

			/**
			 * Trashes/Deletes the booking and item from the order.
			 * @since 4.1.0
			 */
			function bkap_trash_booking() {
			    global $wpdb;
			    $booking_post_id = $_POST[ 'booking_id' ];
			
			    woocommerce_booking::bkap_delete_booking( $booking_post_id );
			    
			}

			/**
			 * Deletes the booking item from the order
			 * and sets the booking status to cancelled 
			 * @since 4.2.0
			 * @param int $booking_post_id
			 */
			static function bkap_delete_booking( $booking_post_id ) {
			    
			    global $wpdb;
			    
			    $item_id = get_post_meta( $booking_post_id, '_bkap_order_item_id', true );
			    $product_id = get_post_meta( $booking_post_id, '_bkap_product_id', true );
			    $booking_start = get_post_meta( $booking_post_id, '_bkap_start', true );
			    $booking_end = get_post_meta( $booking_post_id, '_bkap_end', true );
			    
			    $booking_type = get_post_meta( $product_id, '_bkap_booking_type', true );
			    // get the order ID
			    $order_query = "SELECT order_id FROM `" . $wpdb->prefix . "woocommerce_order_items`
        		                      WHERE order_item_id = %s";
			    $order_results = $wpdb->get_results( $wpdb->prepare( $order_query, $item_id ) );
			    	
			    $order_id = $order_results[0]->order_id;
			    	
			    if ( $order_id > 0 ) {
			        	
			        $order_obj = new WC_Order( absint( $order_id ) );
			        	
			        $order_items = $order_obj->get_items();
			        foreach( $order_items as $oid => $o_value ) {
			            if ( $oid == $item_id ) {
			                $item_value = $o_value;
			                break;
			            }
			        }
			        	
			        if ( isset( $item_value ) ) {
			            $get_booking_id = "SELECT booking_id FROM `" . $wpdb->prefix . "booking_order_history`
			                                     WHERE order_id = %d";
			            $results_booking = $wpdb->get_results( $wpdb->prepare( $get_booking_id, $order_id ) );
			            	
			            foreach( $results_booking as $id ) {
			                	
			                $get_booking_details = "SELECT post_id, start_date, end_date, from_time, to_time FROM `" . $wpdb->prefix . "booking_history`
                                                        WHERE id = %d";
			                $bkap_details = $wpdb->get_results( $wpdb->prepare( $get_booking_details, $id->booking_id ) );
			    
			                $matched = false;
			                	
			                if ( $bkap_details[ 0 ]->post_id == $product_id ) {
			                    $start_date = substr( $booking_start, 0, 8 );
			                    $start_date = date( 'Y-m-d', strtotime( $start_date ) );
			                    switch( $booking_type ) {
			                        case 'only_day':
			                            if ( $start_date === $bkap_details[ 0 ]->start_date ) {
			                                $booking_id = $id->booking_id;
			                                $matched = true;
			                            }
			                            break;
			                        case 'multiple_days':
			                            $end_date = substr( $booking_end, 0, 8 );
			                            $end_date = date( 'Y-m-d', strtotime( $end_date ) );
			                            if ( $start_date === $bkap_details[ 0 ]->start_date && $end_date === $bkap_details[ 0 ]->end_date ) {
			                                $booking_id = $id->booking_id;
			                                $matched = true;
			                            }
			                            break;
			                        case 'date_time':
			                            $db_time_slot = $bkap_details[ 0 ]->from_time . '-' . $bkap_details[ 0 ]->end_time;
			                            $meta_time = substr( $booking_start, 8, 2 ) . ':' . substr( $booking_start, 10, 2 ) . '-' . substr( $booking_end, 8, 2 ) . ':' . substr( $booking_end, 10, 2 );
			                            if ( $start_date === $bkap_details[ 0 ]->start_date && $meta_time === $db_time_slot ) {
			                                $booking_id = $id->booking_id;
			                                $matched = true;
			                            }
			                            break;
			                    }
			    
			                    if ( $matched ) {
			                        break;
			                    }
			                }
			            }
			    
			            if ( isset( $booking_id ) && $booking_id > 0 ) {
			                	
			                // cancel the booking
			                bkap_cancel_order::bkap_reallot_item( $item_value, $booking_id, $order_id);
			                	
			                // delete the order from booking order history
			                if ( 'multiple_days' !== $booking_type ) {
			                    $delete_order_history = "DELETE FROM `" . $wpdb->prefix . "booking_order_history`
                        		                    WHERE order_id = %d and booking_id = %d";
			                    $wpdb->query( $wpdb->prepare( $delete_order_history, $order_id, $booking_id ) );
			                }
			                	
			                // update the booking post status
		                    $new_booking = bkap_checkout::get_bkap_booking( $booking_post_id );
		                    
		                    do_action('bkap_rental_delete', $new_booking, $booking_post_id);
		                    
		                    $new_booking->update_status( 'cancelled' );
		                
			                // remove the item from the order
			       //         wc_delete_order_item( $item_id );
			                	
			                $_product = wc_get_product( $product_id );
			                $product_title = $_product->get_name();
			    
			                // add note in the order
			                $order_obj->add_order_note( __( "The booking for $product_title has been trashed.", 'woocommerce-booking' ) );
			    
			            }
			    
			        }
			    }
			}
			
			/**
			 * Checks the details of the booking post being
			 * edited. In case of any errors, it returns the
			 * list of errors.
			 * @param array $booking
			 * @return array $results
			 * @since 4.2.0
			 */
			function bkap_sanity_check( $booking ) {
			     
			    $results = array();
			     
			    $product_id = $booking[ 'product_id' ];
			    $start = $booking[ 'hidden_date' ];
			    $end = $booking[ 'hidden_date_checkout' ];
			    $qty = $booking[ 'qty' ];
			    $booking_post_id = $booking[ 'post_id' ];
			     
			    $time_slot = $booking[ 'time_slot' ];
			    $exploded_time  = explode( '-', $time_slot );
			     
			    $time = date( 'G:i', strtotime( $exploded_time[0] ) );
			    if ( isset( $exploded_time[1] ) && '' !== $exploded_time[1] ) {
			        $time .= ' - ' . date( 'G:i', strtotime( $exploded_time[1] ) );
			    }
			     
			    $booking_type = get_post_meta( $product_id, '_bkap_booking_type', true );
			     
			    $qty_check = true;
			    $date_check = true; // date/s and/or time is valid
			     
			    $current_time = current_time( 'timestamp' );
			    switch( $booking_type ) {
			        case 'multiple_days':
			             
			            if ( $start === '' || $end === '' || strtotime( $end ) < $current_time ) {
			                $date_check = false;
			            } else {
			                $bookings = get_bookings_for_range( $product_id, $start, $end );
			                $order_dates     = bkap_common::bkap_get_betweendays( date( 'd-n-Y', strtotime( $start ) ), date( 'd-n-Y', strtotime( $end ) ) );
			                 
			                $least_availability = '';
			                // get the least available bookings for the range
			                foreach( $order_dates as $date ) {
			                    $lockout = get_date_lockout( $product_id, $date );
			                    $new_available = 0;
			                     
			                    $date_ymd = date( 'Ymd', strtotime( $date ) );
			                    $bookings_for_date = ( isset( $bookings[ $date_ymd ] ) ) ? $bookings[ $date_ymd ] : 0;
			                     
			                    if ( absint( $lockout ) > 0 )
			                        $new_available = $lockout - $bookings_for_date;
			                     
			                    if ( $least_availability === '' )
			                        $least_availability = $new_available;
			                     
			                    if ( $least_availability > $new_available )
			                        $least_availability = $new_available;
			
			                }
			                 
			                // change in qty
			                $old_qty = get_post_meta( $booking_post_id, '_bkap_qty', true );
			                $change = $qty - $old_qty; //assume change is always an increase
			
			                if ( $change > 0 ) {
			                    if ( $change > $least_availability ) {
			                        $qty_check = false;
			                    }
			                }
			
			            }
			            break;
			        case 'only_day':
			            if ( $start === '' || strtotime( $start ) < $current_time ) { // Date is blank or past date
			                $date_check = false;
			            } else {
			                // returns an array for all the bookings received for the set date
			                $dates = get_bookings_for_date( $product_id, $start );
			                 
			                // returns an array containing the available bookings and if unlimited bookings are allowed or no
			                $get_availability = get_availability_for_date( $product_id, $start, $dates );
			                $available_tickets = $get_availability[ 'available' ];
			                $unlimited = $get_availability[ 'unlimited' ];
			
			                // change in qty
			                $old_qty = get_post_meta( $booking_post_id, '_bkap_qty', true );
			                $change = $qty - $old_qty; //assume change is always an increase
			                 
			                if ( $change > 0 ) {
			                    if ( $unlimited === 'NO' ) {
			                        if ( $change > $available_tickets ) {
			                            $qty_check = false;
			                        }
			                    }
			                }
			            }
			            break;
			        case 'date_time':
			
			            if ( $start === '' || $time === '0:00' ) {
			                $date_check = false;
			            } else {
			                // returns an array for all the bookings received for the set date
			                $dates = get_bookings_for_date( $product_id, $start );
			                $availability = get_slot_availability( $product_id, $start, $time, $dates );
			
			                if ( $availability[ 'unlimited' ] === 'NO' ) {
			                    // change in qty
			                    $old_qty = get_post_meta( $booking_post_id, '_bkap_qty', true );
			                    $change = $qty - $old_qty; //assume change is always an increase
			                     
			                    if ( $change > $availability[ 'available' ] ) {
			                        $qty_check = false;
			                    }
			                }
			            }
			            break;
			    }
			     
			    if ( ! $qty_check ) {
			        $results[] = __( 'Quantity being set is not available for the desired date.', 'woocommerce-booking' );
			    }
			     
			    if ( ! $date_check ) {
			        $results[] = __( 'The Booking details are incorrect. Please fill them up correctly.', 'woocommerce-booking' );
			    }
			     
			    return $results;
			}
				
			/**
			 * This function saves the booking data for
			 * Edit Booking posts - from Woo->Orders
			 * @since 4.1.0
			 */
			function bkap_meta_box_save( $post_data, $post ) {
			     
			    if ( 'bkap_booking' !== $post[ 'post_type' ] ) {
			        return $post_data;
			    }
			    
			    $post_id = $post[ 'ID' ];
			    
			    // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
			    if ( empty( $post['post_ID'] ) || intval( $post['post_ID'] ) !== $post_id ) {
			        return $post_data;
			    }
			     
			    if ( ! isset( $post['bkap_details_meta_box_nonce'] ) || ! wp_verify_nonce( $post['bkap_details_meta_box_nonce'], 'bkap_details_meta_box' ) ) {
			        return $post_data;
			    }
			
			    global $wpdb;
			
			    global $bkap_date_formats;
			
			    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			
			    $date_format_to_display = $global_settings->booking_date_format;
			    $time_format_to_display = $global_settings->booking_time_format;
			    $time_format_to_display = ( $time_format_to_display === '12' ) ? 'h:i A' : 'H:i';
				
				$book_item_meta_date = ( '' == get_option( 'book_item-meta-date' ) ) ? __( 'Start Date', 'woocommerce-booking' ) : get_option( 'book_item-meta-date' ) ;
				$checkout_item_meta_date = ( '' == get_option( 'checkout_item-meta-date' ) ) ? __( 'End Date', 'woocommerce-booking' ) : get_option( 'checkout_item-meta-date' );
				$book_item_meta_time = ( '' == get_option( 'book_item-meta-time' ) ) ? __( 'Booking Time', 'woocommerce-booking' ) : get_option( 'book_item-meta-time' );

			    // Get booking object.
			    $booking    = new BKAP_Booking( $post_id );
			    $product_id = wc_clean( $post['bkap_product_id'] );
			    $hidden_date = $post[ 'wapbk_hidden_date' ];
			    $booking_data[ 'date' ] = date( 'Y-m-d', strtotime( $hidden_date ) );
			    $booking_data[ 'hidden_date' ] = $hidden_date;
			     
			    $booking_type = get_post_meta( $product_id, '_bkap_booking_type', true );

			    $days = 1;
			    
			    if ( 'multiple_days' === $booking_type ) {
			        
			        $old_end = date( 'Y-m-d', strtotime( $booking->get_end() ) );
			        $hidden_date_checkout = $post[ 'wapbk_hidden_date_checkout' ];
			        $booking_data[ 'date_checkout' ] = date( 'Y-m-d', strtotime( $hidden_date_checkout ) );
			        $booking_data[ 'hidden_date_checkout' ] = $hidden_date_checkout;
			        $days = ceil( ( strtotime( $hidden_date_checkout ) - strtotime( $hidden_date ) ) / 86400 );
			        
			    } else if ( 'date_time' === $booking_type ) {
			        $new_time = wc_clean( $post['time_slot'] );
			        
			        // convert new time to 24 hr
			        $new_time_array = explode( '-', $new_time );
			        $new_time = date ( 'H:i', strtotime( trim( $new_time_array[ 0 ] ) ) );
			        if ( isset( $new_time_array[1] ) && '' != $new_time_array[1] ) {
			            $new_time .= ' - ' . date( 'H:i', strtotime( trim( $new_time_array [ 1 ] ) ) );
			        }
			         
			        $old_time = $booking->get_time();
			        $booking_data[ 'time_slot' ] = $new_time;
			    }
			    $new_qty   = wc_clean( $post['bkap_qty'] );
			    $new_status = wc_clean( $post[ '_bkap_status' ] );
			
			    $product         = wc_get_product( $product_id );
			    $product_title = $product->get_name();
			
			    // get the existing data, so we can figure out what has been modified
			    $old_qty = get_post_meta( $post_id, '_bkap_qty', true );
			    $old_status = $booking->get_status();
			    $old_start = date( 'Y-m-d', strtotime( $booking->get_start() ) );
			    $item_id = get_post_meta( $post_id, '_bkap_order_item_id', true );
			
			    // default the variables
			    $qty_update = false;
			    $date_update = false;
			    $time_update = false;
			    $notes_array = array();
			    	
			    $current_user = wp_get_current_user();
			    $current_user_name = $current_user->display_name;
			     
			    if ( absint( $old_qty ) !== absint( $new_qty ) ) {
			        $qty_update = true;
			        $notes_array[] = "The quantity for $product_title was modified from $old_qty to $new_qty by $current_user_name";
			    }
			
			    if ( $old_status !== $new_status ) {
			        $_POST[ 'item_id' ] = $item_id;
			        $_POST[ 'status' ] = $new_status;
			        bkap_booking_confirmation::bkap_save_booking_status( $item_id, $new_status );
			    }
			
			    if ( strtotime( $old_start ) !== strtotime( $hidden_date ) ) {
			        $date_update = true;
			    }
			
			    if ( 'multiple_days' === $booking_type ) {
			        if ( strtotime( $old_end ) !== strtotime( $hidden_date_checkout ) ) {
			            $date_update = true;
			        }
			    } else if ( 'date_time' === $booking_type ) {
			        if ( $old_time !== $new_time ) {
			            $time_update = true;
			        }
			    }

			    // check if price has been modified
			    $new_price = $post[ 'bkap_price_charged' ];
			    $new_price_per_qty = $new_price / $new_qty;
			     
			    // if Woo Product Addon Options are present, add those
			    $addon_price = wc_get_order_item_meta( $item_id, '_wapbk_wpa_prices' );
			    if( $addon_price && $addon_price > 0 ) {
			        // if per day charges are enabled, it needs to be multiplied with the number of days
			        if ( isset( $global_settings->woo_product_addon_price ) && 'on' === $global_settings->woo_product_addon_price ) {
			            $addon_price = $addon_price * $days;
			        }
			        $new_price_per_qty += $addon_price;
			    }

			    // GF Product Addon options
			    $gf_history = wc_get_order_item_meta( $item_id, '_gravity_forms_history' );
			    if( $gf_history && count( $gf_history ) > 0 ) {
			        $gf_details = isset( $gf_history[ '_gravity_form_lead' ] ) ? $gf_history[ '_gravity_form_lead' ] : array();
			        if( count( $gf_details ) > 0 ) {
			            $addon_price = array_pop( $gf_details );
			            if( isset( $addon_price ) && $addon_price > 0 ) {
			                if ( isset( $global_settings->woo_gf_product_addon_option_price ) && 'on' === $global_settings->woo_gf_product_addon_option_price ) {
			                    $addon_price = $addon_price * $days;
			                }
			                $new_price_per_qty += $addon_price;
			            }
			        }
			    }
			     
			    $new_price = $new_price_per_qty * $new_qty;
			     
			    $old_price = $booking->get_cost() * $booking->get_quantity();
			     
			    if( $old_price !== $new_price ) {
			        $price_update = true;
			    }
			     
			    if ( $qty_update || $date_update || $time_update ) {
			        
			        // gather the data & validate
			        $data[ 'product_id' ] = $product_id;
			        $data[ 'qty' ] = $new_qty;
			        $data[ 'hidden_date' ] = $booking_data[ 'hidden_date' ];
			        $data[ 'hidden_date_checkout' ] = ( isset( $booking_data[ 'hidden_date_checkout' ] ) ) ? $booking_data[ 'hidden_date_checkout' ] : '';
			        $data[ 'time_slot' ] = isset( $booking_data[ 'time_slot' ] ) ? $booking_data[ 'time_slot' ] : '';
			        $data[ 'post_id' ] = $post_id;
			         
			        $sanity_results = $this->bkap_sanity_check( $data );
			        
			        if ( count( $sanity_results ) > 0 ) {
			            update_post_meta( $post_id, '_bkap_update_errors', $sanity_results );
			            return;
			        }
			         
			        // get the order ID
			        $order_query = "SELECT order_id FROM `" . $wpdb->prefix . "woocommerce_order_items`
        		                      WHERE order_item_id = %s";
			        $order_results = $wpdb->get_results( $wpdb->prepare( $order_query, $item_id ) );
			
			        $order_id = $order_results[0]->order_id;
			
			        if ( $order_id > 0 ) {
			
			            $order_obj = new WC_Order( absint( $order_id ) );
			
			            $order_items = $order_obj->get_items();
			            foreach( $order_items as $oid => $o_value ) {
			                if ( $oid == $item_id ) {
			                    $item_value = $o_value;
			                    break;
			                }
			            }
			
			            if ( isset( $item_value ) ) {
			            $get_booking_id = "SELECT booking_id FROM `" . $wpdb->prefix . "booking_order_history`
			                                     WHERE order_id = %d";
			                $results_booking = $wpdb->get_results( $wpdb->prepare( $get_booking_id, $order_id ) );
			                
			                foreach( $results_booking as $id ) {
			                    
                                $get_booking_details = "SELECT post_id, start_date, end_date, from_time, to_time FROM `" . $wpdb->prefix . "booking_history`
                                                        WHERE id = %d";
                                $bkap_details = $wpdb->get_results( $wpdb->prepare( $get_booking_details, $id->booking_id ) );

                                $matched = false;
                           
                                if ( $bkap_details[ 0 ]->post_id == $product_id ) {

                                    switch( $booking_type ) {
                                        case 'only_day':
                                            if ( strtotime( $old_start ) === strtotime( $bkap_details[ 0 ]->start_date ) ) {
                                                $booking_id = $id->booking_id;
                                                $matched = true;
                                            }
                                            break;
                                        case 'multiple_days':
                                            if ( strtotime( $old_start ) === strtotime( $bkap_details[ 0 ]->start_date ) && strtotime( $old_end ) === strtotime( $bkap_details[ 0 ]->end_date ) ) {
                                                $booking_id = $id->booking_id;
                                                $matched = true;
                                            }
                                            break;
                                        case 'date_time':
                                            $time_slot = date( 'H:i', strtotime( $bkap_details[ 0 ]->from_time ) );
                                            if ( $bkap_details[ 0 ]->to_time !== '' ) {
                                                $time_slot .= ' - ' . date( 'H:i', strtotime( $bkap_details[ 0 ]->to_time ) );
                                            }
                                            if ( strtotime( $old_start ) === strtotime( $bkap_details[ 0 ]->start_date ) && $old_time === $time_slot ) {
                                                $booking_id = $id->booking_id;
                                                $matched = true;
                                            }
                                            break;
                                    }

                                    if ( $matched ) {
                                        break;
                                    }
                                }
			                }
			                
			                if ( isset( $booking_id ) && $booking_id > 0 ) {
    			                // cancel the booking
    			                bkap_cancel_order::bkap_reallot_item( $item_value, $booking_id, $order_id);
    			                 
    			                // delete the order from booking order history
    			                if ( 'multiple_days' !== $booking_type ) {
    			                    $delete_order_history = "DELETE FROM `" . $wpdb->prefix . "booking_order_history`
    			                                         WHERE order_id = %d and booking_id = %d";
    			                    $wpdb->query( $wpdb->prepare( $delete_order_history, $order_id, $booking_id ) );
    			                }
    			                
    			                // add a new booking
    			                $details = bkap_checkout::bkap_update_lockout( $order_id, $product_id, 0, $new_qty, $booking_data );
			                }
			            }
			
			            // update item meta
			            $display_start = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $hidden_date ) );
			            wc_update_order_item_meta( $item_id, $book_item_meta_date , $display_start, '' );

                  wc_update_order_item_meta( $item_id, '_wapbk_booking_date', date( 'Y-m-d', strtotime( $hidden_date ) ), '' );
			            $meta_start = date( 'Ymd', strtotime( $hidden_date ) );
			             
			            switch( $booking_type ) {
			                case 'only_day':
			                    $meta_start .= '000000';
			                    $meta_end = $meta_start;
			                    
                                // add order notes if needed
			                    if ( $date_update ) {
			                        $old_start_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $old_start ) );
			                        $notes_array[] = "The booking details have been modified from $old_start_display to $display_start by $current_user_name";
			                    }
			                    break;
			                case 'multiple_days':

			                    $display_end = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $hidden_date_checkout ) );
			                    wc_update_order_item_meta( $item_id, $checkout_item_meta_date, $display_end, '' );

			                    wc_update_order_item_meta( $item_id, '_wapbk_checkout_date', date( 'Y-m-d', strtotime( $hidden_date_checkout ) ), '' );
			            
			                    $meta_start .= '000000';
			                    $meta_end = date( 'Ymd', strtotime( $hidden_date_checkout ) );
			                    $meta_end .= '000000';
			                    
			                    // add order notes if needed
			                    if ( $date_update ) {
			                        $old_start_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $old_start ) );
			                        $old_end_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $old_end ) );
			                        $notes_array[] = "The booking details have been modified from $old_start_display - $old_end_display to $display_start - $display_end by $current_user_name";
			                    }
			                    break;
			                case 'date_time':
			                    $time_array = explode( '-', $new_time );
			                    $display_time = date( $time_format_to_display, strtotime( trim( $time_array[ 0 ] ) ) );
			                    $db_time = date( 'G:i', strtotime( trim( $time_array[ 0 ] ) ) );
			                    $meta_start .= date( 'His', strtotime( trim( $time_array[ 0 ] ) ) );
			            
			                    if ( isset( $time_array[ 1 ] ) && '' !== $time_array[ 1 ] ) {
			                        $display_time .= " - " . date( $time_format_to_display, strtotime( $time_array[ 1 ] ) );
			                        $db_time .= " - " . date( 'G:i', strtotime( $time_array[ 1 ] ) );
			                        $meta_end = date( 'Ymd', strtotime( $hidden_date ) );
			                        $meta_end .= date( 'His', strtotime( trim( $time_array[ 1 ] ) ) );
			                    } else {
			                        $meta_end = date( 'Ymd', strtotime( $hidden_date ) );
			                        $meta_end .= '000000';
			                    }
			                     
			                    wc_update_order_item_meta( $item_id, $book_item_meta_time, $display_time, '' );
			                    wc_update_order_item_meta( $item_id, '_wapbk_time_slot', $db_time, '' );
			                    
			                    // add order notes if needed
			                    if ( $date_update || $time_update ) {
			                        $old_start_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $old_start ) );
			                         
			                        $old_time_array = explode( '-', $old_time );
			                        $old_time_disp = date( $time_format_to_display, strtotime( trim( $old_time_array[ 0 ] ) ) );
			                    
			                        if ( isset( $old_time_array[ 1 ] ) && '' !== $old_time_array[ 1 ] ) {
			                            $old_time_disp .= " - " . date( $time_format_to_display, strtotime( $old_time_array[ 1 ] ) );
			                        }
			                        $notes_array[] = "The booking details have been modified from $old_start_display, $old_time_disp to $display_start, $display_time by $current_user_name";
			                    }
			                     
			                    break;
			            }
			            // if qty has been updated, update the same to be reflected in Woo->Orders
			            if ( $qty_update ) {
			                wc_update_order_item_meta( $item_id, '_qty', $new_qty, '' );
			            }
			
			            // update the post meta for the booking
			            update_post_meta( $post_id, '_bkap_start', $meta_start );
			            update_post_meta( $post_id, '_bkap_end', $meta_end );
			            update_post_meta( $post_id, '_bkap_qty', $new_qty );

			            if( $price_update ) {
			                // update post meta
			                update_post_meta( $post_id, '_bkap_cost', $new_price_per_qty );
			                 
			                // update the price for the item
			                wc_update_order_item_meta( $item_id, '_line_subtotal', $new_price );
			                $line_total = $new_price + wc_get_order_item_meta( $item_id, '_line_subtotal_tax' );
			                wc_update_order_item_meta( $item_id, '_line_total', $line_total );
			                 
			                // update the order total
			                $new_order_obj = wc_get_order( $order_id );
			                $old_total = $new_order_obj->get_total();
			                $new_total = round( $old_total - $old_price + $new_price, 2 );
			                $new_order_obj->set_total( $new_total );
			                 
			                $order_currency = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order_obj->get_order_currency() : $order_obj->get_currency();
			                $currency_symbol    = get_woocommerce_currency_symbol( $order_currency );
			                // add order note
			                $notes_array[] = "The booking price for $product_title has been modified from $currency_symbol$old_price to $currency_symbol$new_price by $current_user_name";
			                 
			                $new_order_obj->save();
			            }
			             
			        }
			        // add order notes
			        if ( is_array( $notes_array ) && count( $notes_array ) > 0 ) {
			            foreach( $notes_array as $msg ) {
			                $order_obj->add_order_note( __( $msg, 'woocommerce-booking' ) );
			            }
			        }
			    }
			     
			}

			/**
			 * If the time slot is locked out, it still needs to be
			 * displayed if the booking is being edited.
			 * So add the time slot to the dropdown list for 
			 * Edit Booking Post page.
			 * @since 4.3.0
			 */
			function add_time_slot( $dropdown ) {
			    
			    $display = $dropdown;
			     
			    $booking_id = isset( $_REQUEST[ 'booking_post_id' ] ) ? $_REQUEST[ 'booking_post_id' ] : 0;
			     
			    if ( $booking_id > 0 && get_post_type( $booking_id ) === 'bkap_booking' ) {
			        $booking = new BKAP_Booking( $booking_id );
			        	
			        $times_selected = explode( '-', $booking->get_time() );
			    
			        $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			        $time_format = $global_settings->booking_time_format;
			        $time_format = ( $time_format === '12' ) ? 'h:i A' : 'H:i';
			         
			        $time_display = date( $time_format, strtotime( trim( $times_selected[ 0 ] ) ) );
			    
			        if ( isset( $times_selected[ 1 ] ) && '23:59' !== trim( $times_selected[ 1 ] ) )
			            $time_display .= ' - ' . date( $time_format, strtotime( trim( $times_selected[ 1 ] ) ) );
			         
			        $time_drop_down_array =   explode( "|", $display );
			        if ( ! in_array( $time_display, $time_drop_down_array ) ) {
			            // check if any error messages are there
			            if ( trim( $time_drop_down_array[0] ) === 'ERROR' ) {
			                $display = '';
			            }
			            
			            // check if the time slot is actually present for that day or no
			            // this should be done only if the date in the datepicker is not the same as the one for which the booking was placed
			            if( $_REQUEST[ 'current_date' ] !== date( 'j-n-Y', strtotime( $booking->get_start() ) ) ) {
			                 
			                $found = false;
			                $booking_date = date( 'j-n-Y', strtotime( $_REQUEST[ 'current_date' ] ) );
			                $booking_times = get_post_meta( $_REQUEST[ 'post_id' ], '_bkap_time_settings', true );
			                 
			                if ( is_array( $booking_times ) && count( $booking_times ) > 0 && array_key_exists( $booking_date, $booking_times ) ) {
			                    $slots_list = $booking_times[ $booking_date ];
			                } else {
			                    // check for the weekday
			                    $weekday = date( 'w', strtotime( $booking_date ) );
			                    $booking_weekday = "booking_weekday_$weekday";
			                     
			                    if ( is_array( $booking_times ) && count( $booking_times ) > 0 && array_key_exists( $booking_weekday, $booking_times ) ) {
			                        $slots_list = $booking_times[ $booking_weekday ];
			                    }
			                }
			                 
			                if( is_array( $slots_list ) && count( $slots_list ) > 0 ) {
			                    foreach( $slots_list as $times ) {
			                         
			                        $from_time_check = date( $time_format, strtotime( $times[ 'from_slot_hrs' ] . ":" . $times[ 'from_slot_min' ] ) );
			                        $to_time_check = date( $time_format, strtotime( $times[ 'to_slot_hrs' ] . ":" . $times[ 'to_slot_min' ] ) );
			                         
			                        if( $to_time_check !== '' && $to_time_check !== '00:00' && $to_time_check !== '12:00 AM' ) {
			                            $time_check = "$from_time_check - $to_time_check";
			                        } else {
			                            $time_check = "$from_time_check";
			                        }
			                         
			                        if ( $time_check === $time_display) {
			                            $found = true;
			                            break;
			                        }
			                    }
			                }
			            } else {
			                $found = true;
			            }
			             
			            if ( $found ) {
                            $display .= $time_display . '|';
			            }
			        }
			    }
			     
			    return $display;
			     
			}
			public static function bkap_edit_bookings( $order_id, $item_id, $old_start, $old_end, $old_time, $product_id ){

				global $wpdb;

				$order_obj = new WC_Order( absint( $order_id ) );
			
	            $order_items = $order_obj->get_items();
	            foreach( $order_items as $oid => $o_value ) {
	                if ( $oid == $item_id ) {
	                    $item_value = $o_value;
	                    break;
	                }
	            }

	            $booking_type = get_post_meta( $product_id, '_bkap_booking_type', true );

	            if ( isset( $item_value ) ) {

	            	$get_booking_id = "SELECT booking_id FROM `" . $wpdb->prefix . "booking_order_history`
	                                     WHERE order_id = %d";

	                $results_booking = $wpdb->get_results( $wpdb->prepare( $get_booking_id, $order_id ) );

	                foreach( $results_booking as $id ) {
	                    
                        $get_booking_details = "SELECT post_id, start_date, end_date, from_time, to_time 
                                                FROM `" . $wpdb->prefix . "booking_history`
                                                WHERE id = %d";
                        $bkap_details = $wpdb->get_results( $wpdb->prepare( $get_booking_details, $id->booking_id ) );

                        $matched = false;

                        if ( $bkap_details[ 0 ]->post_id == $product_id ) {

                            switch( $booking_type ) {
                                
                                case 'only_day':

                                    if ( $old_start === $bkap_details[ 0 ]->start_date ) {
                                        $booking_id = $id->booking_id;
                                        $matched = true;
                                    }
                                    break;
                                case 'multiple_days':
                                
                                    if ( $old_start === $bkap_details[ 0 ]->start_date && $old_end === $bkap_details[ 0 ]->end_date ) {
                                        $booking_id = $id->booking_id;
                                        $matched = true;
                                    }
                                    break;
                                case 'date_time':
                                
                                    $time_slot = $bkap_details[ 0 ]->from_time . ' - ' . $bkap_details[ 0 ]->to_time;
                                    if ( $old_start === $bkap_details[ 0 ]->start_date && $old_time === $time_slot ) {
                                        $booking_id = $id->booking_id;
                                        $matched = true;
                                    }
                                    break;
                            }

                            if ( $matched ) {
                                break;
                            }
                        }
	                }

	                // cancel the booking
	                bkap_cancel_order::bkap_reallot_item( $item_value, $booking_id, $order_id);
	                 
	                // delete the order from booking order history
	                //if ( 'multiple_days' !== $booking_type ) {
	                    $delete_order_history = "DELETE FROM `" . $wpdb->prefix . "booking_order_history`
	                                         WHERE order_id = %d and booking_id = %d";
	                    $wpdb->query( $wpdb->prepare( $delete_order_history, $order_id, $booking_id ) );
	                //}
	                
	                // add a new booking
	                //$details = bkap_checkout::bkap_update_lockout( $order_id, $product_id, 0, $new_qty, $booking_data );
	            }
			}
		}		
	}
	
	$woocommerce_booking = new woocommerce_booking();
	
}
