<?php

class bkap_wc_vendors {
    
    public function __construct() {
        
        // include the files
        add_action( 'init', array( &$this, 'bkap_wcv_include_files' ), 6 );
        
        // add the booking menu in NAV
        add_filter( 'wcv_pro_dashboard_urls', array( &$this, 'bkap_add_menu' ), 10, 1 );
        add_filter( 'wcv_dashboard_pages_nav', array( &$this, 'bkap_modify_menu' ), 10, 1 );

        // add the custom pages
        add_filter( 'wcv_dashboard_custom_pages', array( &$this, 'bkap_booking_menu' ), 10, 1 );
        
        // View Bookings Data Export
        add_action( 'wp', array( &$this, 'bkap_download_booking_files' ) );

        add_filter( 'bkap_display_multiple_modals', array( &$this, 'bkap_wc_vendors_enable_modals' ) );

        add_action( 'bkap_wc_vendors_booking_list', array( &$this, 'bkap_wc_vendors_load_modals' ), 10, 2 );
        
        add_action( 'admin_init', array( &$this, 'bkap_remove_menus' ) );
        
    } // construct
    
    /**
     * Include files as needed
     * @since 4.6.0
     */
    function bkap_wcv_include_files() {
        // product page Booking tab file
        include_once( 'product.php' );
    }
    
    /**
     * Add the Booking menu to the Vendor dashboard
     * @param array $pages
     * @return array
     * @since 4.6.0
     */
    function bkap_add_menu( $pages ) {
        
        $pages[ 'bkap-booking' ] = array(
            'label'   => __( 'Bookings', 'woocommerce-booking' ),
            'slug'    => 'bkap-booking', 
            'actions' => array(),
            'custom'  => 'bkap-booking'
        );
        
        return $pages;
    }
    
    /**
     * Add the Booking menu to the Vendor dashboard
     * @param array $pages
     * @return array
     */
    function bkap_modify_menu( $pages ) {
        
        $pages[ 'bkap-booking' ] = array(
            'label'   => __( 'Bookings', 'woocommerce-booking' ),
            'slug'    => 'bkap-booking?custom=bkap-booking', 
            'actions' => array(),
            'custom'  => 'bkap-booking'
        );
        
        return $pages;
    }

    /**
     * Add the templates
     * @since 4.6.0
     */
    function bkap_booking_menu( $menu ) {
        $menu[ 'bkap-booking' ] = array(
            'slug'			=> 'bkap-booking?custom=bkap-booking',
            'label'			=> __('Bookings', 'woocommerce-booking' ),
            'template_name' => 'bkap-wcv-view-bookings',
            'base_dir'      => BKAP_BOOKINGS_TEMPLATE_PATH . 'vendors-integration/wc-vendors/',
            'args'          => array(),
            'actions'		=> array(),
            'parent'        => 'bkap-booking'
        );
        
        $menu[ 'calendar-view' ] = array(
            'slug'			=> 'bkap-booking?custom=calendar-view',
            'label'			=> __('Bookings', 'woocommerce-booking' ),
            'template_name' => 'bkap-wcv-calendar-view',
            'base_dir'      => BKAP_BOOKINGS_TEMPLATE_PATH . 'vendors-integration/wc-vendors/',
            'args'          => array(),
            'actions'		=> array(),
        ); 
    
        // Uncomment this when we make Resources compatible with Vendors
/*        $menu[ 'bkap-resources' ] = array(
            'slug'			=> 'bkap-booking?custom=bkap-resources',
            'label'			=> __('Bookings', 'woocommerce-booking' ),
            'template_name' => 'bkap-wcv-resources',
            'base_dir'      => BKAP_BOOKINGS_TEMPLATE_PATH . 'vendors-integration/wc-vendors/',
            'args'          => array(),
            'actions'		=> array(),
        ); */
        
        return $menu;
    }

    /**
     * View Bookings Data Export
     * Print & CSV
     * @since 4.6.0
     */
    function bkap_download_booking_files() {
    
        if ( isset( $_GET[ 'custom' ] ) && ( $_GET[ 'custom' ] == 'bkap-print' || $_GET[ 'custom' ] ) == 'bkap-csv' ) {
    
            $current_page = $_GET[ 'custom' ];
    
            $additional_args = array(
                'meta_key'   => '_bkap_vendor_id',
                'meta_value' => get_current_user_id() );
            $data = bkap_common::bkap_get_bookings( '', $additional_args );
    
            if ( isset( $current_page ) && $current_page === 'bkap-csv' ) {
                BKAP_Bookings_View::bkap_download_csv_file( $data );
            }elseif ( isset( $current_page ) && $current_page === 'bkap-print' ) {
                BKAP_Bookings_View::bkap_download_print_file( $data );
            }
            
        }
    }

    public function bkap_wc_vendors_load_modals( $booking_id, $booking_post ) {

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
        
        if( isset( $booking_post[ 'time_slot' ] ) ) {
            $booking_details[ 'time_slot' ] = $booking_post[ 'time_slot' ];
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
    }

    /**
     * Enable Global Params to be set for Modals to load on View Bookings
     * 
     * @param bool $display Status indicating presence of multiple products for booking
     * @return bool True if multiple products present
     * @since 4.6.0
     */
    public function bkap_wc_vendors_enable_modals( $display ) {

        if ( isset( $_GET['custom'] ) && $_GET['custom'] === 'bkap-booking' ) {
            return $display = true;
        }else {
            return $display;
        }
    }
    
    /**
     * Remove the booking menu from the vendor admin dashboard
     * @since 4.6.0
     */
    function bkap_remove_menus() {
    
        if( current_user_can( 'vendor' ) ) {
            remove_menu_page( 'edit.php?post_type=bkap_booking' );
        }
    }
} // end of class

$bkap_wc_vendors = new bkap_wc_vendors();
?>