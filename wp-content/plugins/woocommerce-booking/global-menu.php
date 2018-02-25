<?php 

include_once( 'view-bookings.php' );
include_once( 'license.php' );
include_once( 'includes/bkap-calendar-sync-settings.php' );
include_once( 'import-bookings.php' );
include_once( 'bkap-global-settings.php' );

class global_menu {
    
   /**
    * This function adds the Booking settings  menu in the 
    * sidebar admin woocommerce.
    */
    public static function bkap_woocommerce_booking_admin_menu(){
        global $submenu;

        // Remove the additional Create Booking created on bkap_booking post registrations
        unset($submenu['edit.php?post_type=bkap_booking'][10]);

    	$page = add_submenu_page( null, __( 'View Bookings', 'woocommerce-booking' ), __( 'View Bookings',   'woocommerce-booking' ), 'manage_woocommerce', 'woocommerce_history_page',  array( 'view_bookings', 'bkap_woocommerce_history_page' ) );
    	$page = add_submenu_page( 'edit.php?post_type=bkap_booking', __( 'Create Booking',    'woocommerce-booking' ), __( 'Create Booking',      'woocommerce-booking' ), 'manage_woocommerce', 'bkap_create_booking_page',  array( 'bkap_admin_bookings', 'bkap_create_booking_page' ) );
    	//$page = add_submenu_page( 'edit.php?post_type=bkap_booking', __( 'Import Bookings',   'woocommerce-booking' ), __( 'Import Bookings',     'woocommerce-booking' ), 'manage_woocommerce', 'woocommerce_import_page',   array( 'import_bookings', 'bkap_woocommerce_import_page' ) );
    	$page = add_submenu_page( 'edit.php?post_type=bkap_booking', __( 'Settings',          'woocommerce-booking' ), __( 'Settings',            'woocommerce-booking' ), 'manage_woocommerce', 'woocommerce_booking_page',  array( 'global_menu',   'bkap_woocommerce_booking_page' ) );
    	$page = add_submenu_page( 'edit.php?post_type=bkap_booking', __( 'Activate License',  'woocommerce-booking' ), __( 'Activate License',    'woocommerce-booking' ), 'manage_woocommerce', 'booking_license_page',      array( 'bkap_license',  'bkap_get_edd_sample_license_page' ) );
    	
        do_action( 'bkap_add_submenu' );
    }
    
	/**
	 * This function displays the global settings for the booking products.
	 */
	 public static function bkap_woocommerce_booking_page() {

        if ( isset( $_GET['action'] ) ) {
                $action = $_GET['action'];
        } else {
                $action = '';
        }
        
        $active_settings   = '';
        $active_labels     = '';
        $addon_settings    = '';
        $cal_sync_settings = '';
        
        switch ( $action ) {
            case 'settings':
                $active_settings = "nav-tab-active";
                break;
            case 'labels':
                $active_labels = "nav-tab-active";
                break;
            case 'addon_settings':
                $addon_settings = "nav-tab-active";
                break;
            case 'calendar_sync_settings':
                $cal_sync_settings = "nav-tab-active";
                break;
            case 'bkap-update':
                $update_process = "nav-tab-active";
                break;
            default:
                $active_settings = "nav-tab-active";
                break;
        }
        
        ?>
        <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
            <a href="admin.php?page=woocommerce_booking_page&action=settings" class="nav-tab <?php echo $active_settings; ?>"> <?php _e( 'Global Booking Settings', 'woocommerce-booking' );?> </a>
            <a href="admin.php?page=woocommerce_booking_page&action=labels" class="nav-tab <?php echo $active_labels; ?>"> <?php _e( 'Labels & Messages', 'woocommerce-booking' );?> </a>
            <!-- 	<a href="admin.php?page=woocommerce_booking_page&action=reminders_settings" class="nav-tab <?php // echo $active_reminders_settings; ?>"> <?php // _e( 'Email Reminders', 'woocommerce-booking' );?> </a> -->
            <a href="admin.php?page=woocommerce_booking_page&action=addon_settings" class="nav-tab <?php echo $addon_settings; ?>"> <?php _e( 'Addon Settings', 'woocommerce-booking' );?> </a>
            <a href="admin.php?page=woocommerce_booking_page&action=calendar_sync_settings" class="nav-tab <?php echo $cal_sync_settings; ?>"> <?php _e( 'Google Calendar Sync', 'woocommerce-booking' );?> </a>
            
            <?php 
            $db_status = get_option( 'bkap_400_update_db_status' );
			    
			    if ( isset( $db_status ) && 'fail' == strtolower( $db_status ) ) {
                ?>
                <a href="admin.php?page=woocommerce_booking_page&action=bkap-update" class="nav-tab <?php echo $update_process; ?>"><?php _e( 'Database Update', 'woocommerce-booking' );?></a>
                <?php 
			    }
            do_action( 'bkap_add_global_settings_tab' );
            ?>
        </h2>
        <?php 
        if ( $action == 'addon_settings' ) {
        	// check if any addons are active
            if ( ( function_exists( 'is_bkap_send_friend_active' ) && is_bkap_send_friend_active() ) || ( function_exists( 'is_bkap_tours_active' ) && is_bkap_tours_active() ) || ( function_exists( 'is_bkap_deposits_active' ) && is_bkap_deposits_active() ) || ( function_exists( 'is_bkap_tickets_active' ) && is_bkap_tickets_active() ) ) {
        		?>
           		<p><?php _e( 'Change settings for the addons to the Booking & Appointment Plugin for WooCommerce.', 'woocommerce-booking' ); ?></p>
           		<?php
                settings_errors();
           		do_action( 'bkap_add_addon_settings' );
        	} else {
        		?>
        		<p> <?php _e( 'No addons are currently active for the Booking & Appointment Plugin for WooCommerce.', 'woocommerce-booking' ); ?></p>
        		<?php 
        	}
        }

        if ( 'calendar_sync_settings' ==  $action ) {
            print( '<div id="content">
                <form method="post" action="options.php">' );
                    settings_errors();
                    settings_fields( "bkap_gcal_sync_settings" );
                    do_settings_sections( "bkap_gcal_sync_settings_page" );
                    submit_button ( __( 'Save Settings', 'woocommerce-booking' ), 'primary', 'save', true );
                print('</form>
            </div>');
        }
        
        if( $action == 'labels' ) {
            print( '<div id="content">
                <form method="post" action="options.php">' );
                    settings_errors();
                    settings_fields( "bkap_booking_labels" );
                    do_settings_sections( "bkap_booking_labels_page" );
                    submit_button ( __( 'Save Settings', 'woocommerce-booking' ), 'primary', 'save', true );
                print('</form>
            </div>' );
        }		

        if( $action == 'settings' || $action == '' ) {
            print( '<div id="content">
                <form method="post" action="options.php">' );
                    settings_errors();
                    settings_fields( "bkap_global_settings" );
                    do_settings_sections( "bkap_global_settings_page" );
                    submit_button ( __( 'Save Settings', 'woocommerce-booking' ), 'primary', 'save', true );
                print('</form>
            </div>' );
                
        }
        if ( $action == 'bkap-update' ) {
            bkap_400_update_db_tab();
        }
        do_action( 'bkap_settings_tab_content' );
   }
   
    public static function bkap_booking_labels() {
        add_settings_section(
            'bkap_booking_product_page_labels_section',		// ID used to identify this section and with which to register options
            __( 'Labels on product page', 'woocommerce-booking' ),		// Title to be displayed on the administration page
            array( 'bkap_global_settings', 'bkap_booking_product_page_labels_section_callback' ),		// Callback used to render the description of the section
            'bkap_booking_labels_page'				// Page on which to add this section of options
        );
        
        add_settings_field(
            'book_date-label',
            __( 'Check-in Date:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_date_label_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_product_page_labels_section',
            array ( __( 'Check-in Date label on product page.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'checkout_date-label',
            __( 'Check-out Date:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'checkout_date_label_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_product_page_labels_section',
            array ( __( 'Check-out Date label on product page.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'bkap_calendar_icon_file',
            __( 'Select Calendar Icon:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'bkap_calendar_icon_label_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_product_page_labels_section',
            array ( __( 'Replace or Remove Calendar Icon label on product page.', 'woocommerce-booking' ) )
        );
                
        add_settings_field(
            'book_time-label',
            __( 'Booking Time:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_time_label_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_product_page_labels_section',
            array ( __( 'Booking Time label on product page.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_time-select-option',
            __( 'Choose Time Text:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_time_select_option_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_product_page_labels_section',
            array ( __( 'Text for the 1st option of Time Slot dropdown field that instructs the customer to select a time slot.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_fixed-block-label',
            __( 'Fixed Block Drop Down Label:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_fixed_block_label_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_product_page_labels_section',
            array ( __( 'Fixed Block Drop Down label on product page.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_price-label',
            __( 'Label for Booking Price:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_price_label_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_product_page_labels_section',
            array ( __( 'Label for Booking Price on product page.', 'woocommerce-booking' ) )
        );
        
        add_settings_section( 
            'bkap_booking_order_received_and_email_labels_section',		// ID used to identify this section and with which to register options
            __( 'Labels on order received page and in email notification', 'woocommerce-booking' ),		// Title to be displayed on the administration page
            array( 'bkap_global_settings', 'bkap_booking_order_received_and_email_labels_section_callback' ),		// Callback used to render the description of the section
            'bkap_booking_labels_page'				//
        );
        
        add_settings_field(
            'book_item-meta-date',
            __( 'Check-in Date:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_item_meta_date_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_order_received_and_email_labels_section',
            array ( __( 'Check-in Date label on the order received page and email notification.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'checkout_item-meta-date',
            __( 'Check-out Date:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'checkout_item_meta_date_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_order_received_and_email_labels_section',
            array ( __( 'Check-out Date label on the order received page and email notification.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_item-meta-time',
            __( 'Booking Time:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_item_meta_time_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_order_received_and_email_labels_section',
            array ( __( 'Booking Time label on the order received page and email notification.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_ics-file-name',
            __( 'ICS File Name:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_ics_file_name_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_order_received_and_email_labels_section',
            array ( __( 'ICS File name.', 'woocommerce-booking' ) )
        );
        
        add_settings_section(
            'bkap_booking_cart_and_checkout_page_labels_section',		// ID used to identify this section and with which to register options
            __( 'Labels on Cart & Check-out Page', 'woocommerce-booking' ),		// Title to be displayed on the administration page
            array( 'bkap_global_settings', 'bkap_booking_cart_and_checkout_page_labels_section_callback' ),		// Callback used to render the description of the section
            'bkap_booking_labels_page'				//
        );
        
        add_settings_field(
            'book_item-cart-date',
            __( 'Check-in Date:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_item_cart_date_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_cart_and_checkout_page_labels_section',
            array ( __( 'Check-in Date label on the cart and checkout page.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'checkout_item-cart-date',
            __( 'Check-out Date:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'checkout_item_cart_date_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_cart_and_checkout_page_labels_section',
            array ( __( 'Check-out Date label on the cart and checkout page.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_item-cart-time',
            __( 'Booking Time:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_item_cart_time_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_cart_and_checkout_page_labels_section',
            array ( __( 'Booking Time label on the cart and checkout page.', 'woocommerce-booking' ) )
        );

        add_settings_section(
            'bkap_add_to_cart_button_labels_section',       // ID used to identify this section and with which to register options
            __( 'Text for Add to Cart button', 'woocommerce-booking' ),     // Title to be displayed on the administration page
            array( 'bkap_global_settings', 'bkap_add_to_cart_button_labels_section_callback' ),     // Callback used to render the description of the section
            'bkap_booking_labels_page'              //
        );

        add_settings_field(
            'bkap_add_to_cart',
            __( 'Text for Add to Cart Button:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'bkap_add_to_cart_button_text_callback' ),
            'bkap_booking_labels_page',
            'bkap_add_to_cart_button_labels_section',
            array ( __( 'Change text for Add to Cart button on WooCommerce product page.', 'woocommerce-booking' ) )
        );

        add_settings_field(
            'bkap_check_availability',
            __( 'Text for Check Availability Button:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'bkap_check_availability_text_callback' ),
            'bkap_booking_labels_page',
            'bkap_add_to_cart_button_labels_section',
            array ( __( 'Change text for Check Availability button on WooCommerce product page when product requires confirmation.', 'woocommerce-booking' ) )
        );


        
        
        add_settings_section(
            'bkap_booking_availability_messages_section',		// ID used to identify this section and with which to register options
            __( 'Booking Availability Messages on Product Page', 'woocommerce-booking' ),		// Title to be displayed on the administration page
            array( 'bkap_global_settings', 'bkap_booking_availability_messages_section_callback' ),		// Callback used to render the description of the section
            'bkap_booking_labels_page'				//
        );
        
        add_settings_field(
            'book_stock-total',
            __( 'Total stock display message: ', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_stock_total_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_availability_messages_section',
            array ( __( 'The total stock message to be displayed when the product page loads.<br><i>Note: You can use AVAILABLE_SPOTS placeholder which will be replaced by it\'s real value.</i>', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_available-stock-date',
            __( 'Availability display message for a date: ', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_available_stock_date_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_availability_messages_section',
            array ( __( 'The availability message displayed when a date is selected in the calendar.<br><i>Note: You can use AVAILABLE_SPOTS, DATE placeholders which will be replaced by their real values.</i>', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_available-stock-time',
            __( 'Availability display message for a time slot: ', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_available_stock_time_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_availability_messages_section',
            array ( __( 'The availability message displayed when a time slot is selected for a date.<br><i>Note: You can use AVAILABLE_SPOTS, DATE, TIME placeholders which will be replaced by their real values.</i>', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_available-stock-date-attr',
            __( 'Availability display message for a date when attribute level lockout is set: ', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_available_stock_date_attr_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_availability_messages_section',
            array ( __( 'The availability message displayed when a date is selected and attribute level lockout is set for the product.<br><i>Note: You can use AVAILABLE_SPOTS, DATE, ATTRIBUTE_NAME placeholders which will be replaced by their real values.</i>', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_available-stock-time-attr',
            __( 'Availability display message for a time slot when attribute level lockout is set: ', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_available_stock_time_attr_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_availability_messages_section',
            array ( __( 'The availability message displayed when a time slot is selected for a date and attribute level lockout is set for the product.<br><i>Note: You can use AVAILABLE_SPOTS, DATE, TIME, ATTRIBUTE_NAME placeholders which will be replaced by their real values.</i>', 'woocommerce-booking' ) )
        );

        add_settings_field(
            'book_real-time-error-msg',
            __( 'Message to be displayed when the time slot is blocked in real time: ', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_real_time_error_msg_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_availability_messages_section',
            array ( __( 'The message to be displayed when any time slot for the date selected by the user is fully blocked in real time bookings.', 'woocommerce-booking' ) )
        );
        
        add_settings_section(
            'bkap_booking_lockout_messages_section',		// ID used to identify this section and with which to register options
            __( 'Booking Availability Error Messages on the Product, Cart & Checkout Pages', 'woocommerce-booking' ),		// Title to be displayed on the administration page
            array( 'bkap_global_settings', 'bkap_booking_lockout_messages_section_callback' ),		// Callback used to render the description of the section
            'bkap_booking_labels_page'				//
        );
        
        add_settings_field(
            'book_limited-booking-msg-date',
            __( 'Limited availability error message for a date: ', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_limited_booking_msg_date_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_lockout_messages_section',
            array ( __( 'The error message displayed for a date booking when user tries to book more than the available quantity.<br><i>Note: You can use PRODUCT_NAME, AVAILABLE_SPOTS, DATE placeholders which will be replaced by their real values.</i>', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_no-booking-msg-date',
            __( 'No availability error message for a date: ', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_no_booking_msg_date_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_lockout_messages_section',
            array ( __( 'The error message displayed for a date booking and bookings are no longer available for the selected date.<br><i>Note: You can use PRODUCT_NAME, DATE placeholders which will be replaced by their real values.</i>', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_limited-booking-msg-time',
            __( 'Limited availability error message for a time slot: ', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_limited_booking_msg_time_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_lockout_messages_section',
            array ( __( 'The error message displayed for a date and time booking when user tries to book more than the available quantity.<br><i>Note: You can use PRODUCT_NAME, AVAILABLE_SPOTS, DATE, TIME placeholders which will be replaced by their real values.</i>', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_no-booking-msg-time',
            __( 'No availability error message for a time slot: ', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_no_booking_msg_time_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_lockout_messages_section',
            array ( __( 'The error message displayed for a date and time booking and bookings are no longer available for the selected time slot.<br><i>Note: You can use PRODUCT_NAME, DATE, TIME placeholders which will be replaced by their real values.</i>', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_limited-booking-msg-date-attr',
            __( 'Limited Availability Error Message for a date when attribute level lockout is set:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_limited_booking_msg_date_attr_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_lockout_messages_section',
            array ( __( 'The error message displayed for a date booking when user tries to book more than the available quantity setup at the attribute level.<br><i>Note: You can use PRODUCT_NAME, AVAILABLE_SPOTS, ATTRIBUTE_NAME, DATE placeholders which will be replaced by their real values.</i>', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'book_limited-booking-msg-time-attr',
            __( 'Limited Availability Error Message for a time slot when attribute level lockout is set:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'book_limited_booking_msg_time_attr_callback' ),
            'bkap_booking_labels_page',
            'bkap_booking_lockout_messages_section',
            array ( __( 'The error message displayed for a date and time booking when user tries to book more than the available quantity setup at the attribute level.<br><i>Note: You can use PRODUCT_NAME, AVAILABLE_SPOTS, ATTRIBUTE_NAME, DATE, TIME placeholders which will be replaced by their real values.</i>', 'woocommerce-booking' ) )
        );
        
        register_setting( 
            'bkap_booking_labels',
            'book_date-label'
        );

        register_setting( 
            'bkap_booking_labels',
            'bkap_add_to_cart'
        );

        register_setting( 
            'bkap_booking_labels',
            'bkap_check_availability'
        );
        
        register_setting(
            'bkap_booking_labels',
            'checkout_date-label'
        );
        
        register_setting(
            'bkap_booking_labels',
            'bkap_calendar_icon_file'
        );
                
        register_setting(
            'bkap_booking_labels',
            'book_time-label'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_time-select-option'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_fixed-block-label'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_price-label'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_item-meta-date'
        );
        
        register_setting(
            'bkap_booking_labels',
            'checkout_item-meta-date'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_item-meta-time'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_ics-file-name'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_item-cart-date'
        );
        
        register_setting(
            'bkap_booking_labels',
            'checkout_item-cart-date'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_item-cart-time'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_stock-total'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_available-stock-date'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_available-stock-time'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_available-stock-date-attr'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_available-stock-time-attr'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_real-time-error-msg'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_limited-booking-msg-date'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_no-booking-msg-date'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_limited-booking-msg-time'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_no-booking-msg-time'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_limited-booking-msg-date-attr'
        );
        
        register_setting(
            'bkap_booking_labels',
            'book_limited-booking-msg-time-attr'
        );
   }
   
   public static function bkap_global_settings() {
       
       add_settings_section(
            'bkap_global_settings_section',		// ID used to identify this section and with which to register options
            __( 'General Settings', 'woocommerce-booking' ),		// Title to be displayed on the administration page
            array( 'bkap_global_settings', 'bkap_global_settings_section_callback' ),		// Callback used to render the description of the section
            'bkap_global_settings_page'				// Page on which to add this section of options
       );
       
        add_settings_field(
            'booking_language',
            __( 'Language:', 'woocommerce-booking' ),
           array( 'bkap_global_settings', 'booking_language_callback' ),
           'bkap_global_settings_page',
           'bkap_global_settings_section',
           array ( __( 'Choose the language for your booking calendar. <strong>Note:</strong> This setting <strong>will be deprecated</strong> in the future releases of Booking & Appointment plugin for WooCommerce. We recommend using <strong>WordPress->Settings->General->Site Language</strong> option in future.', 'woocommerce-booking' ) )
       );
        
        add_settings_field(
            'booking_date_format',
            __( 'Date Format:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'booking_date_format_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array ( __( 'The format in which the booking date appears to the customers throughout the order cycle.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'booking_time_format',
            __( 'Time Format:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'booking_time_format_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array ( __( 'The format in which booking time appears to the customers throughout the order cycle.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'booking_months',
            __( 'Number of months to show in calendar:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'booking_months_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array ( __( 'The number of months to be shown on the calendar. If the booking dates spans across 2 months, then dates of 2 months can be shown simultaneously without the need to press Next or Back buttons.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'booking_calendar_day',
            __( 'First Day on Calendar:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'booking_calendar_day_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Choose the first day to display on the booking calendar.' , 'woocommerce-booking' ) ) 
        );
        
        $link  = admin_url() . "admin.php?page=woocommerce_booking_page&action=calendar_sync_settings";
        
        add_settings_field( 
            'booking_export',
            __( 'Show "Add to Calendar" button on Order Received page:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'booking_export_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Shows the \'Add to Calendar\' button on the Order Received page. On clicking the button, an ICS file will be downloaded. <b>Note:</b> This setting <b>will be deprecated</b> in a future release of the plugin. Please use <b>Show Add to Calendar button on Order received page</b> setting found <a href="' . $link . '">here</a> instead.', 'woocommerce-booking' ) )
        );
        
        add_settings_field( 
            'booking_attachment',
            __( 'Send bookings as attachments (ICS files) in email notifications:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'booking_attachment_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Allow customers to export bookings as ICS file after placing an order. Sends ICS files as attachments in email notifications.', 'woocommerce-booking' ) ) 
        );
        
        add_settings_field( 
            'booking_theme',
            __( 'Preview Theme & Language:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'booking_theme_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Select the theme for the calendar. You can choose a theme which blends with the design of your website.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'booking_global_holidays',
            __( 'Select Holidays / Exclude Days / Black-out days:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'booking_global_holidays_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Select dates for which the booking will be completely disabled for all the products in your WooCommerce store. <br> Please click on the date in calendar to add or delete the date from the holiday list.', 'woocommerce-booking' ) )
        );
        
        add_settings_field( 
            'booking_global_timeslot',
            __( 'Global Time Slot Booking:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'booking_global_timeslot_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Please select this checkbox if you want ALL time slots to be unavailable for booking in all products once the lockout for that time slot is reached for any product.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'enable_rounding',
            __( 'Enable Rounding of Prices:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'booking_enable_rounding_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Rounds the Booking Price to the nearest Integer value. <strong>Note:</strong> This setting will be deprecated in the future releases of Booking & Appointment plugin for WooCommerce. We recommend using <strong>WooCommerce->Settings->General->Number of Decimals</strong> option in future.', 'woocommerce-booking' ) )
        );
        
        add_settings_field( 
            'hide_variation_price',
            __( 'Hide Variation Price on Product Page:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'hide_variation_price_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Select whether the WooCommerce Variation Price should be hidden on the front end Product Page.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'hide_booking_price',
            __( 'Hide Booking Price on Product Page:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'hide_booking_price_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( '
Select this if you want to hide the Booking Price on Product page until the time slot is selected for the bookable product with time slot. The prices will be shown only after booking date and timeslot is selected by the customer.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'display_disabled_buttons',
            __( 'Always display the Add to Cart and Quantity buttons:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'display_disabled_buttons_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Select whether the Add to Cart and Quantity buttons should always be displayed on the front end Product page.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'booking_global_selection',
            __( 'Duplicate dates from first product in the cart to other products:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'booking_global_selection_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Please select this checkbox if you want to select the date globally for All products once selected for a product and added to cart.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'booking_availability_display',
            __( 'Enable Availability Display on the Product page:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'booking_availability_display_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Please select this checkbox if you want to display the number of bookings available for a given product on a given date and time.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'resource_price_per_day',
            __( 'Charge Resource cost on a Per Day Basis:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'resource_price_per_day_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Please select this checkbox if you want to multiply the resource price with the number of booking days for Multiple Nights Booking.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'woo_product_addon_price',
            __( 'Charge WooCommerce Product Addons options on a Per Day Basis:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'woo_product_addon_price_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Please select this checkbox if you want to multiply the option price of WooCommerce Product Addons with the number of booking days for Multiple Day Booking.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'woo_gf_product_addon_option_price',
            __( 'Charge WooCommerce Gravity Forms Product Addons options on a Per Day Basis:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'woo_gf_product_addon_option_price_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Please select this checkbox if you want to multiply the option price of WooCommerce Gravity Forms Product Addons with the number of booking days for Multiple Day Booking.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'minimum_day_booking',
            __( 'Minimum Day Booking:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'minimum_day_booking_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'Enter minimum days of booking for Multiple days booking.', 'woocommerce-booking' ) )
        );
        
        add_settings_field(
            'global_booking_minimum_number_days',
            __( 'Minimum number of days to choose:', 'woocommerce-booking' ),
            array( 'bkap_global_settings', 'global_booking_minimum_number_days_callback' ),
            'bkap_global_settings_page',
            'bkap_global_settings_section',
            array( __( 'The minimum number days you want to be booked for multiple day booking. For example, if you require minimum 2 days for booking, enter the value 2 in this field.', 'woocommerce-booking' ) )
        );
        
        register_setting(
            'bkap_global_settings',
            'woocommerce_booking_global_settings',
            array( 'bkap_global_settings', 'woocommerce_booking_global_settings_callback' )
        );
        
        do_action( 'bkap_after_global_holiday_field' );
   }
   
   public static function bkap_gcal_settings() {
        
       // First, we register a section. This is necessary since all future options must belong to one.
       add_settings_section(
       'bkap_gcal_sync_general_settings_section',		// ID used to identify this section and with which to register options
       __( 'General Settings', 'woocommerce-booking' ),		// Title to be displayed on the administration page
       array( 'bkap_gcal_sync_settings', 'bkap_gcal_sync_general_settings_callback' ),		// Callback used to render the description of the section
       'bkap_gcal_sync_settings_page'				// Page on which to add this section of options
       );
   
       add_settings_field(
       'bkap_calendar_event_location',
       __( 'Event Location', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_event_location_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_gcal_sync_general_settings_section',
       array ( __( '<br>Enter the text that will be used as location field in event of the Calendar. If left empty, website description is sent instead. <br><i>Note: You can use ADDRESS and CITY placeholders which will be replaced by their real values.</i>', 'woocommerce-booking' ) )
       );
   
       add_settings_field(
       'bkap_calendar_event_summary',
       __( 'Event summary (name)', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_event_summary_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_gcal_sync_general_settings_section'
           );
   
       add_settings_field(
       'bkap_calendar_event_description',
       __( 'Event Description', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_event_description_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_gcal_sync_general_settings_section',
       array( '<br>For the above 2 fields, you can use the following placeholders which will be replaced by their real values:&nbsp;SITE_NAME, CLIENT, PRODUCT_NAME, PRODUCT_WITH_QTY, ORDER_DATE_TIME, ORDER_DATE, ORDER_NUMBER, PRICE, PHONE, NOTE, ADDRESS, EMAIL (Client\'s email)	', 'woocommerce-booking' )
       );
   
       add_settings_section(
       'bkap_calendar_sync_customer_settings_section',
       __( 'Customer Add to Calendar button Settings', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_sync_customer_settings_callback' ),
       'bkap_gcal_sync_settings_page'
           );
   
       add_settings_field(
       'bkap_add_to_calendar_order_received_page',
       __( 'Show Add to Calendar button on Order received page', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_add_to_calendar_order_received_page_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_customer_settings_section',
       array ( __( 'Show Add to Calendar button on the Order Received page for the customers.', 'woocommerce-booking' ) )
       );
   
       add_settings_field(
       'bkap_add_to_calendar_customer_email',
       __( 'Show Add to Calendar button in the Customer notification email', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_add_to_calendar_customer_email_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_customer_settings_section',
       array ( __( 'Show Add to Calendar button in the Customer notification email.', 'woocommerce-booking' ) )
       );
   
       add_settings_field(
       'bkap_add_to_calendar_my_account_page',
       __( 'Show Add to Calendar button on My account', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_add_to_calendar_my_account_page_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_customer_settings_section',
       array ( __( 'Show Add to Calendar button on My account page for the customers.', 'woocommerce-booking' ) )
       );
   
       add_settings_field(
       'bkap_calendar_in_same_window',
       __( 'Open Calendar in Same Window', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_in_same_window_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_customer_settings_section',
       array ( __( 'As default, the Calendar is opened in a new tab or window. If you check this option, user will be redirected to the Calendar from the same page, without opening a new tab or window.', 'woocommerce-booking' ) )
       );
   
       add_settings_section(
       'bkap_notice_for_use_product_gcalsync',
       "",
       array( 'bkap_gcal_sync_settings', 'bkap_notice_for_use_product_gcalsync_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section'
       );
       
       add_settings_section(
       'bkap_calendar_sync_admin_settings_section',
       __( 'Admin Calendar Sync Settings', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_sync_admin_settings_section_callback' ),
       'bkap_gcal_sync_settings_page'
           );
   
       add_settings_field(
       'bkap_allow_tour_operator_gcal_api',
       __( 'Allow Tour Operators for Google Calendar API Integration', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_allow_tour_operator_gcal_api_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array ( __( '<br>Whether you let your tour operators to integrate with their own Google Calendar account using their profile page. Note: Each of them will need to set up their accounts following the steps as listed in Instructions below (will also be shown in their profile pages) and you will need to upload their key files yourself using FTP.', 'woocommerce-booking' ) )
       );
       
       add_settings_field(
       'bkap_calendar_sync_integration_mode',
       __( 'Integration Mode', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_sync_integration_mode_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array ( __( '<br>Select method of integration.<br>"Sync Automatically" will add the booking events to the Google calendar, which is set in the "Calendar to be used" field, automatically when a customer places an order. A button will be added on the View Booking Calendar page based on the Settings.<br>"Sync Manually" will add an "Add to Calendar" button in emails received by admin on New customer order when "Show Add to Calendar button in New Order email notification" is enabled.<br>"Disabled" will disable the integration with Google Calendar.', 'woocommerce-booking' ) )
       );
   
       add_settings_field(
       'bkap_sync_calendar_instructions',
       __( 'Instructions', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_sync_calendar_instructions_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section'
           );
   
   
       add_settings_field(
       'bkap_calendar_key_file_name',
       __( 'Key file name', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_key_file_name_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array( '<br>Enter key file name here without extention, e.g. ab12345678901234567890-privatekey.', 'woocommerce-booking' )
       );
   
       add_settings_field(
       'bkap_calendar_service_acc_email_address',
       __( 'Service account email address', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_service_acc_email_address_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array( '<br>Enter Service account email address here, e.g. 1234567890@developer.gserviceaccount.com.', 'woocommerce-booking' )
       );
   
       add_settings_field(
       'bkap_calendar_id',
       __( 'Calendar to be used', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_id_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array( '<br>Enter the ID of the calendar in which your bookings will be saved, e.g. abcdefg1234567890@group.calendar.google.com.', 'woocommerce-booking' )
       );
   
       add_settings_field(
       'bkap_calendar_test_connection',
       '',
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_test_connection_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section'
           );
   
       add_settings_field(
       'bkap_admin_add_to_calendar_view_booking',
       __( 'Show Add to Calendar button on View Bookings page', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_admin_add_to_calendar_view_booking_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array( 'Show "Add to Calendar" button on the Booking -> View Bookings page.<br><i>Note: This button can be used to export the already placed orders with future bookings from the current date to the calendar used above.</i>', 'woocommerce-booking' )
       );
   
       add_settings_field(
       'bkap_admin_add_to_calendar_email_notification',
       __( 'Show Add to Calendar button in New Order email notification', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_admin_add_to_calendar_email_notification_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_sync_admin_settings_section',
       array( 'Show "Add to Calendar" button in the New Order email notification.', 'woocommerce-booking' )
       );
   
       add_settings_section(
       'bkap_calendar_import_ics_feeds_section',
       __( 'Import Events', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_calendar_import_ics_feeds_section_callback' ),
       'bkap_gcal_sync_settings_page'
           );
   
       add_settings_field(
       'bkap_cron_time_duration',
       __( 'Run Automated Cron after X minutes', 'woocommerce-booking'  ),
       array( 'bkap_gcal_sync_settings', 'bkap_cron_time_duration_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_import_ics_feeds_section',
       array( '<br>The duration in minutes after which a cron job will be run automatically importing events from all the iCalendar/.ics Feed URLs.<br><i>Note: Setting it to a lower number can affect the site perfomance.</i>', 'woocommerce-booking' )
       );
       
       add_settings_field(
       'bkap_ics_feed_url_instructions',
       __( 'Instructions', 'woocommerce-booking'  ),
       array( 'bkap_gcal_sync_settings', 'bkap_ics_feed_url_instructions_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_import_ics_feeds_section'
           );
   
       add_settings_field(
       'bkap_ics_feed_url',
       __( 'iCalendar/.ics Feed URL', 'woocommerce-booking' ),
       array( 'bkap_gcal_sync_settings', 'bkap_ics_feed_url_callback' ),
       'bkap_gcal_sync_settings_page',
       'bkap_calendar_import_ics_feeds_section'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_calendar_event_location'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_calendar_event_summary',
       array ( 'bkap_gcal_sync_settings', 'bkap_event_summary_validate_callback' )
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_calendar_event_description',
       array( 'bkap_gcal_sync_settings', 'bkap_event_description_validate_callback' )
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_add_to_calendar_order_received_page'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_add_to_calendar_customer_email'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_add_to_calendar_my_account_page'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_calendar_in_same_window'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_allow_tour_operator_gcal_api'
           );
       
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_calendar_sync_integration_mode'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_calendar_details_1'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_admin_add_to_calendar_view_booking'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_admin_add_to_calendar_email_notification'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_cron_time_duration'
           );
       
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_ics_feed_url_instructions'
           );
   
       register_setting(
       'bkap_gcal_sync_settings',
       'bkap_ics_feed_url'
           );
   }
   
   public static function bkap_add_review_note() {
		echo '<div class="tyche-info">
				<p style="margin-bottom: 10px;">' . __( 'Happy with our Booking &amp; Appointment plugin? A review will help us immensely.', 'woocommerce-booking' ) . '</p>
				<p>' . __( 'You can review this plugin at <a href="https://www.facebook.com/TycheSoftwares/reviews/" target="_blank" class="button">Facebook</a>' ) . '</p>
			</div>';
   }   
}// End of the class

?>