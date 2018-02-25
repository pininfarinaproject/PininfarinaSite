<?php

/**
 * Bookings and Appointment Plugin for WooCommerce
 *
 * Allow Bookings to be edited from Cart and Checkout Page
 *
 * @author      Tyche Softwares
 * @package     Bookings and Appointment Plugin
 */

if( ! class_exists( 'bkap_edit_bookings_class' ) ) {

    /**
    * Class for allowing Bookings to be edited from Cart and Checkout Page
    */
    class bkap_edit_bookings_class
    {
        
        /**
         * Constructor function
         * 
         * @param array $global_settings Global Settings array
         * @since 4.1.0
         */
        function __construct( $global_settings ) {

            add_action( 'admin_init', array( &$this, 'bkap_add_edit_settings' ) );

            if( isset( $global_settings->bkap_enable_booking_edit ) && 
                $global_settings->bkap_enable_booking_edit === 'on' ){
            
                add_filter( 'woocommerce_cart_item_name', array( &$this, 'bkap_add_edit_link' ), 10, 3 );
            }
            if( isset( $global_settings->bkap_enable_booking_reschedule ) && 
                $global_settings->bkap_enable_booking_reschedule === 'on' ) {
               
                add_action( 'woocommerce_order_item_meta_end', array( &$this, 'bkap_add_reschedule_link' ), 10, 3 );
            }
            add_action( 'wp_ajax_nopriv_bkap_update_edited_bookings', array( &$this, 'bkap_update_edited_bookings' ) );
            add_action( 'wp_ajax_bkap_update_edited_bookings', array( &$this, 'bkap_update_edited_bookings' ) );
        }

        /**
         * Load modal template for booking box
         * 
         * @param array $booking_details Booking Details array
         * @param WC_Product $cart_product Product Object
         * @param int|string $product_id Product ID
         * @param array $localized_array Localized array to be passed to JS
         * @param string $bkap_cart_item_key Cart Item key or Order Item key for unique ID of modal
         * @param int|string $variation_id Variation ID
         * @param int|string $gravity_forms_options Gravity Forms Options totals
         * @since 4.1.0
         */
        public static function bkap_load_template( $booking_details, $cart_product, $product_id, $localized_array, $bkap_cart_item_key, $variation_id, $additional_addon_data = array() ) {

            wc_get_template( 
                'bkap-edit-booking-modal.php', 
                array(
                    'bkap_booking' => $booking_details,
                    'product_obj' => $cart_product,
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'bkap_cart_item_key' => $bkap_cart_item_key,
                    'bkap_addon_data' => $additional_addon_data ), 
                'woocommerce-booking/', 
                BKAP_BOOKINGS_TEMPLATE_PATH );

            $plugin_version_number = get_option( 'woocommerce_booking_db_version' );

            if( isset( $variation_id ) && $variation_id > 0 ) {
                $variation_class = new WC_Product_Variation( $variation_id );
                $get_attributes =   $variation_class->get_variation_attributes();
            
                if( is_array( $get_attributes ) && count( $get_attributes ) > 0 ) {
                    foreach( $get_attributes as $attr_name => $attr_value ) {
                        $attr_value = htmlspecialchars( $attr_value, ENT_QUOTES );
                        // print a hidden field for each of these
                        print( "<input type='hidden' name='$attr_name' value='$attr_value' />" );
                    }
                }
            }
            
            self::bkap_enqueue_edit_bookings_scripts( 
                $bkap_cart_item_key,
                $plugin_version_number, 
                $localized_array
            );

            self::bkap_enqueue_edit_bookings_styles(
                $plugin_version_number
            );
        }

        /**
         * Add Edit Link on Cart and Checkout page
         * 
         * @param string $product_title Product Title to which additional string needs to be appeded
         * @param WC_Product $cart_item Cart Item in WC_Product object form
         * @param string $cart_item_key Cart Item key
         * @return string Product Title with appended data
         * @since 4.1.0
         */
        public function bkap_add_edit_link( $product_title, $cart_item, $cart_item_key ) {

            if ( ( ( is_cart() && !is_ajax() ) || is_checkout() ) && !is_product() && 
                !( is_wc_endpoint_url( 'view-order' ) || is_wc_endpoint_url( 'order-received' ) ) &&
                 isset( $cart_item['bkap_booking'] ) &&
                 !bkap_common::bkap_is_cartitem_bundled( $cart_item ) &&
                 !bkap_common::bkap_is_cartitem_composite( $cart_item ) ) {

                $product_id = $cart_item['product_id'];
                $product_id = bkap_common::bkap_get_product_id( $product_id );

                if ( $cart_item['variation_id'] !== 0 ) {
                    $variation_id = $cart_item['variation_id'];
                }else {
                    $variation_id = 0;
                }

                $product_title .= '<div style="clear:both;"></div>';

                $product_title .= sprintf( '<input type="button" class="bkap_edit_bookings" onclick=bkap_edit_booking_class.bkap_edit_bookings(%d,"%s") value="%s">', $product_id, $cart_item_key, __( 'Edit Bookings', 'woocommerce-booking' ) );

                $page_type = '';
                if ( is_cart() ) {
                    $page_type = 'cart';
                }else if ( is_checkout() ) {
                    $page_type = 'checkout';
                }

                $localized_array = array( 
                    'bkap_booking_params' => $cart_item['bkap_booking'][0],
                    'bkap_cart_item' => $cart_item,
                    'bkap_cart_item_key' => $cart_item_key,
                    'bkap_page_type' => $page_type
                );

                // Additional data for addons
                $additional_addon_data = bkap_common::bkap_get_cart_item_addon_data( $cart_item );

                self::bkap_load_template( 
                    $cart_item['bkap_booking'][0], 
                    $cart_item['data'], 
                    $product_id, 
                    $localized_array,
                    $cart_item_key,
                    $variation_id,
                    $additional_addon_data );

                return $product_title;
            }else {
                return $product_title;
            }
        }

        /**
         * Add Edit Booking link on My Account Page
         * 
         * @param srting $item_id Order Item ID
         * @param WC_Order_Item $item Order Item
         * @param WC_Order $order Order Object
         * @since 4.1.0
         */
        public function bkap_add_reschedule_link( $item_id, $item, $order ) {

            $book_item_meta_date = ( '' == get_option( 'book_item-meta-date' ) ) ? __( 'Start Date', 'woocommerce-booking' ) : get_option( 'book_item-meta-date' ) ;
            $checkout_item_meta_date = ( '' == get_option( 'checkout_item-meta-date' ) ) ? __( 'End Date', 'woocommerce-booking' ) : get_option( 'checkout_item-meta-date' );
            $book_item_meta_time = ( '' == get_option( 'book_item-meta-time' ) ) ? __( 'Booking Time', 'woocommerce-booking' ) : get_option( 'book_item-meta-time' );

            if ( is_wc_endpoint_url( 'view-order' ) ) {

                $order_status = $order->get_status();

                if( isset( $order_status ) && ( $order_status !== 'cancelled' ) && ( $order_status !== 'refunded' ) && ( $order_status !== 'trash' ) && ( $order_status !== '' ) && ( $order_status !== 'failed' ) && ( 'auto-draft' !== $order_status ) && !bkap_common::bkap_is_orderitem_bundled( $item ) && !bkap_common::bkap_is_orderitem_composite( $item ) ) {

                    $booking_details = array(
                        'date' => '',
                        'hidden_date' => '',
                        'date_checkout' => '',
                        'hidden_date_checkout' => '',
                        'price' => '' );

                    foreach ( $item->get_meta_data() as $meta_index => $meta ) {
                        
                        if ( $meta->key === $book_item_meta_date ) {
                            $booking_details['date'] = $meta->value;
                        }elseif ( $meta->key === '_wapbk_booking_date' ) {
                            $hidden_date = explode( '-', $meta->value );
                            $booking_details['hidden_date'] = $hidden_date[2] . '-' . $hidden_date[1] . '-' . $hidden_date[0];
                        }elseif ( $meta->key === $checkout_item_meta_date ) {
                            $booking_details['date_checkout'] = $meta->value;
                        }elseif ( $meta->key === '_wapbk_checkout_date' ) {
                            $hidden_date_checkout = explode( '-', $meta->value );
                            $booking_details['hidden_date_checkout'] = $hidden_date_checkout[2] . '-' . $hidden_date_checkout[1] . '-' . $hidden_date_checkout[0];
                        }elseif ( $meta->key === $book_item_meta_time ){
                            $booking_details['time_slot'] = $meta->value;
                        }elseif( $meta->key == '_resource_id' ) {
                            $booking_details['resource_id'] = $meta->value;
                        }
                    }

                    $diff_days = (strtotime($booking_details['hidden_date']) - current_time('timestamp'))/60/60/24;
                    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );

                    if( isset( $global_settings->bkap_enable_booking_reschedule ) &&
                        isset( $global_settings->bkap_booking_reschedule_days ) &&
                        $diff_days >= $global_settings->bkap_booking_reschedule_days && 
                        $global_settings->bkap_enable_booking_reschedule === 'on' &&
                        $booking_details['date'] !== '' ) {

                        printf( '<input type="button" class="bkap_edit_bookings" onclick="bkap_edit_booking_class.bkap_edit_bookings(%d,%s)" value="%s">', $item->get_product_id( 'view' ), $item_id, __( 'Edit Bookings', 'woocommerce-booking' ) );

                        $localized_array = array( 
                            'bkap_booking_params' => $booking_details,
                            'bkap_cart_item' => $item,
                            'bkap_cart_item_key' => $item_id,
                            'bkap_order_id' => $order->get_id(),
                            'bkap_page_type' => 'view-order'
                        );

                        // Additional Data for addons
                        $additional_addon_data = bkap_common::bkap_get_order_item_addon_data( $item );

                        self::bkap_load_template( 
                            $booking_details, 
                            $item->get_product(), 
                            $item->get_product_id( 'view' ), 
                            $localized_array,
                            $item_id,
                            $item->get_variation_id( 'view' ),
                            $additional_addon_data );
                    }
                }
            }
        }

        /**
         * Enqueue JS files for edit booking
         * 
         * @param string $bkap_cart_item_key Unique ID used for Modal ID
         * @param string $plugin_version_number Plugin Version number
         * @param array $localized_array Localized array to be passed to JS
         * @since 4.1.0
         */
        public static function bkap_enqueue_edit_bookings_scripts( $bkap_cart_item_key, $plugin_version_number, $localized_array ) {
            
            wp_register_script( 
                "bkap-edit-booking", 
                plugins_url( '/js/bkap-edit-booking.js', __FILE__ ), 
                '', 
                $plugin_version_number, 
                true );

            wp_localize_script( "bkap-edit-booking", "bkap_edit_params_$bkap_cart_item_key", $localized_array );

            wp_enqueue_script( "bkap-edit-booking" );
        }

        /**
         * Enqueue CSS files
         * 
         * @param string $plugin_version_number Plugin version number
         * @since 4.1.0
         */
        public static function bkap_enqueue_edit_bookings_styles( $plugin_version_number ) {

            wp_enqueue_style( 
                'bkap-edit-booking-styles', 
                plugins_url( '/css/bkap-edit-booking.css', __FILE__ ) , 
                '', 
                $plugin_version_number, 
                false );
        }

        /**
         * Ajax call back when confirm bookings is clicked on either Cart, Checkout or My Account Page
         * 
         * @since 4.1.0
         */
        public function bkap_update_edited_bookings() {
            
            global $wpdb;

            if ( isset( $_POST['page_type'] ) && $_POST['page_type'] !== 'view-order' ) {

                $session_cart = WC()->session->cart;

                $cart_item_obj = $_POST['cart_item_obj'];

                // Set the per qty price for 'price' in 'bkap_booking'
                $per_qty_price = $cart_item_obj[ 'bkap_booking' ][0][ 'price' ] / $session_cart[$_POST['cart_item_key']][ 'quantity' ];
                $cart_item_obj[ 'bkap_booking' ][0][ 'price' ] = $per_qty_price;
                
                $session_cart[$_POST['cart_item_key']]['bkap_booking']  = $cart_item_obj['bkap_booking'];
                
                $session_cart[$_POST['cart_item_key']]['line_total']    = 0;
                $session_cart[$_POST['cart_item_key']]['line_subtotal'] = 0;
                
                if ( isset( $cart_item_obj['line_total'] ) ) {
                    $session_cart[$_POST['cart_item_key']]['line_total']    = $cart_item_obj['line_total'];
                    $session_cart[$_POST['cart_item_key']]['line_subtotal'] = $cart_item_obj['line_total'];
                }
                if ( isset( $cart_item_obj['bundled_items'] ) ) {
                    $session_cart = self::bkap_update_bundled_cartitems( $session_cart, $cart_item_obj['bundled_items'], $cart_item_obj['bkap_booking'] );
                }

                WC()->session->set( 'cart', $session_cart );
            }elseif ( isset( $_POST['page_type'] ) && $_POST['page_type'] === 'view-order' ) {

                $order_id       = $_POST['order_id'];
                $item_id        = $_POST['item_id'];
                $booking_data   = $_POST['booking_data'];
                $product_id     = $_POST['product_id'];

                $old_bookings   = array();

                $book_item_meta_date        = ( '' == get_option( 'book_item-meta-date' ) ) ? __( 'Start Date', 'woocommerce-booking' ) : get_option( 'book_item-meta-date' ) ;
                
                $checkout_item_meta_date    = ( '' == get_option( 'checkout_item-meta-date' ) ) ? __( 'End Date', 'woocommerce-booking' ) : get_option( 'checkout_item-meta-date' );
                
                $book_item_meta_time        = ( '' == get_option( 'book_item-meta-time' ) ) ? __( 'Booking Time', 'woocommerce-booking' ) : get_option( 'book_item-meta-time' );

                $old_bookings['booking_date']           = wc_get_order_item_meta( $item_id, $book_item_meta_date );
                $old_bookings['booking_date_checkout']  = wc_get_order_item_meta( $item_id, $checkout_item_meta_date );
                $old_bookings['time_slot']              = wc_get_order_item_meta( $item_id, $book_item_meta_time );

                $item_obj       = new WC_Order_Item_Product( $item_id );
                $product_obj    = wc_get_product( $product_id );
                $quantity       = $item_obj->get_quantity();

                $booking_id     = bkap_common::get_booking_id( $item_id );
                $booking        = new BKAP_Booking( $booking_id );
                $old_start      = date( 'Y-m-d', strtotime( $booking->get_start() ) );
                $booking_type   = get_post_meta( $product_id, '_bkap_booking_type', true );

                $old_resource   = $booking->get_resource();

                $old_end = $old_time = '';
                
                if ( 'multiple_days' === $booking_type ) {
                    $old_end = date( 'Y-m-d', strtotime( $booking->get_end() ) );
                } else if ( 'date_time' === $booking_type ) {
                    $old_time = $booking->get_time();
                }

                if ( function_exists( 'wc_pb_is_bundle_container_order_item' ) && 
                     wc_pb_is_bundle_container_order_item( $item_obj ) ) {

                    $order_obj          = wc_get_order( $order_id );
                    $bundled_item_id    = wc_pb_get_bundled_order_items( $item_obj, $order_obj, true );

                    foreach ( $bundled_item_id as $bundle_key ) {
                        
                        self::bkap_update_item_bookings( 
                            $order_id, 
                            $bundle_key, 
                            $old_start, 
                            $old_end, 
                            $old_time, 
                            $product_id, 
                            $booking_data, 
                            $booking_id, 
                            $quantity, 
                            $old_resource );
                    }
                }

                self::bkap_update_item_bookings( 
                    $order_id, 
                    $item_id, 
                    $old_start, 
                    $old_end, 
                    $old_time, 
                    $product_id, 
                    $booking_data, 
                    $booking_id, 
                    $quantity, 
                    $old_resource );

                $difference_amount = $_POST['booking_data']['booking_price'] - $item_obj->get_total( 'view' );
                $additional_note = '';
                if ( $difference_amount > 0 ) {
                    $item = array(
                        'product'   => $product_obj,
                        'qty'       => $quantity,
                        'amount'    => $difference_amount
                        );

                    $new_order_id = bkap_rescheduled_order_class::bkap_rescheduled_create_order( $order_id, $item );
                    wc_update_order_item_meta( $item_id, '_bkap_resch_rem_bal_order_id', $new_order_id, '' );

                    $additional_note = sprintf( __( 'Please pay difference amount via Order #%s', 'woocommerce-booking' ), $new_order_id );
                }else if ( $difference_amount < 0 ) {
                    $additional_note = __( "Please contact shop manager for differences in amount", 'woocommerce-booking' );
                }

                self::bkap_add_reschedule_order_note( $order_id, $old_bookings, $booking_data, $item_obj->get_name('view'), $additional_note );

                // Trigger invoice email for additional order. This needs to be done after adding order notes
                if ( $difference_amount > 0 ) {
                    $invoice_email = new WC_Email_Customer_Invoice();
                    $invoice_email->trigger( $new_order_id );
                }

                //do_action( 'bkap_booking_rescheduled', $item_id );
                do_action( 'bkap_booking_rescheduled_admin', $item_id );
            }
            
            die();
        }

        /**
         * Used for updating the booking details for a particular Item ID
         * 
         * @param string $order_id Order ID
         * @param string $item_id Item ID
         * @param string $old_start Old Start Date
         * @param string $old_end Old End Date
         * @param string $old_time Old Time
         * @param string $product_id Product ID
         * @param array $booking_data Booking Data
         * @param string $booking_id Booking ID
         * @param int $quantity Quantity
         * @since 4.2
         */
        public static function bkap_update_item_bookings( $order_id, $item_id, $old_start, $old_end, $old_time, $product_id, $booking_data, $booking_id, $quantity, $old_resource ) {

            woocommerce_booking::bkap_edit_bookings( 
                $order_id, 
                $item_id, 
                $old_start, 
                $old_end, 
                $old_time, 
                $product_id );

            $date_to_convert = date( 'Y-m-d', strtotime( $booking_data['hidden_date'] ) );
            
            $book_item_meta_date = ( '' == get_option( 'book_item-meta-date' ) ) ? __( 'Start Date', 'woocommerce-booking' ) : get_option( 'book_item-meta-date' ) ;
            $checkout_item_meta_date = ( '' == get_option( 'checkout_item-meta-date' ) ) ? __( 'End Date', 'woocommerce-booking' ) : get_option( 'checkout_item-meta-date' );
            $book_item_meta_time = ( '' == get_option( 'book_item-meta-time' ) ) ? __( 'Booking Time', 'woocommerce-booking' ) : get_option( 'book_item-meta-time' );

            wc_update_order_item_meta( $item_id, $book_item_meta_date, $booking_data['booking_date'], '' );
            wc_update_order_item_meta( $item_id, '_wapbk_booking_date', $date_to_convert, '' );

            $postmeta_start_date = $date_to_convert . '000000';
            $postmeta_end_date = $date_to_convert . '000000';

            if ( isset( $booking_data['hidden_date_checkout'] ) && $booking_data['hidden_date_checkout'] !== '' ) {

                $checkout_date_to_convert = date( 'Y-m-d', strtotime( $booking_data['hidden_date_checkout'] ) );

                wc_update_order_item_meta( $item_id, $checkout_item_meta_date, $booking_data['booking_date_checkout'], '' );
                wc_update_order_item_meta( $item_id, '_wapbk_checkout_date', $checkout_date_to_convert, '' );

                $postmeta_end_date = $checkout_date_to_convert . '000000';
            }

            if ( isset( $booking_data['time_slot'] ) && $booking_data['time_slot'] !== '' ) {

                $timeslot = $booking_data['time_slot'];
                $timeslots = explode( '-', $timeslot );

                $db_timeslot = date( 'G:i', strtotime( $timeslots[ 0 ] ) );
                $postmeta_start_date = $date_to_convert . date( 'His', strtotime( trim( $timeslots[ 0 ] ) ) );

                if ( isset( $timeslots[ 1 ] ) && $timeslots[ 1 ] !== '' ) {
                    $db_timeslot .= date( 'G:i', strtotime( $timeslots[ 1 ] ) );
                    $postmeta_end_date = $date_to_convert . date( 'His', strtotime( trim( $timeslots[ 1 ] ) ) );
                }else {
                    $postmeta_end_date = $date_to_convert . '000000';
                }

                wc_update_order_item_meta( $item_id, $book_item_meta_time, $booking_data['time_slot'], '' );
                wc_update_order_item_meta( $item_id, '_wapbk_time_slot', $db_timeslot, '' );
            }

            if ( isset( $booking_data['resource_id'] ) && $booking_data['resource_id'] != 0 ) {
                
                $r_label = get_post_meta( $product_id, '_bkap_product_resource_lable', true );
                
                wc_update_order_item_meta( $item_id, $r_label, get_the_title( $booking_data['resource_id'] ), '' );
                wc_update_order_item_meta( $item_id, '_resource_id', $booking_data['resource_id'], '' );
                update_post_meta( $booking_id, '_bkap_resource_id', $booking_data['resource_id'] );
            }

            bkap_checkout::bkap_update_lockout( $order_id, $product_id, '', $quantity, $booking_data, '' );

            $postmeta_start_date = str_replace( '-', '', $postmeta_start_date);
            $postmeta_end_date = str_replace( '-', '', $postmeta_end_date);
            update_post_meta( $booking_id, '_bkap_start', $postmeta_start_date );
            update_post_meta( $booking_id, '_bkap_end', $postmeta_end_date );
        }

        /**
         * Add Order Notes when bookings are rescheduled
         * 
         * @param string|int $order_id Order ID
         * @param array $old_bookings Old Booking data array
         * @param array $new_bookings New Booking data array
         * @since 4.2.0
         */
        public function bkap_add_reschedule_order_note( $order_id, $old_bookings, $new_bookings, $item_name, $additional_note ) {

            $order_obj = wc_get_order( $order_id );

            if( isset( $old_bookings['booking_date'] ) && $old_bookings['booking_date'] !== '' &&
                isset( $new_bookings['booking_date'] ) && $new_bookings['booking_date'] !== '' && 
                isset( $old_bookings['booking_date_checkout'] ) && $old_bookings['booking_date_checkout'] !== '' &&
                isset( $new_bookings['booking_date_checkout'] ) && $new_bookings['booking_date_checkout'] !== '' ) {

                $note_details_old = $old_bookings['booking_date'] . ' - ' . $old_bookings['booking_date_checkout'];
                $note_details_new = $new_bookings['booking_date'] . ' - ' . $new_bookings['booking_date_checkout'];
            }else if( isset( $old_bookings['booking_date'] ) && $old_bookings['booking_date'] !== '' &&
                isset( $new_bookings['booking_date'] ) && $new_bookings['booking_date'] !== '' && 
                isset( $old_bookings['time_slot'] ) && $old_bookings['time_slot'] !== '' &&
                isset( $new_bookings['time_slot'] ) && $new_bookings['time_slot'] !== '' ) {

                $note_details_old = $old_bookings['booking_date'] . ' ' . $old_bookings['time_slot'];
                $note_details_new = $new_bookings['booking_date'] . ' ' . $new_bookings['time_slot'];
            }else {

                $note_details_old = $old_bookings['booking_date'];
                $note_details_new = $new_bookings['booking_date'];
            }

            $order_note = sprintf( __( 'Booking has been rescheduled from <strong>%s</strong> to <strong>%s</strong> for <strong>%s</strong>.', 'woocommerce-booking' ), $note_details_old, $note_details_new, $item_name );

            $order_note = $order_note . $additional_note;
            $order_obj->add_order_note( $order_note, 1, false );
        }

        /**
         * Update bundled items added to cart.
         * 
         * @param array $session_cart Cart Session array
         * @param array $bundled_items Bundled items array
         * @param array $booking_details Booking Details
         * @return array Session cart array with updated booking details
         * @since 4.2
         */
        public static function bkap_update_bundled_cartitems( $session_cart, $bundled_items, $booking_details ) {

            foreach ( $bundled_items as $bundlekey ) {
                $session_cart[$bundlekey]['bkap_booking'] = $booking_details;
            }

            return $session_cart;
        }

        /**
         * Register additional settings for Edit Bookings
         * 
         * @since 4.1.0
         */
        public function bkap_add_edit_settings() {
            
            add_settings_field(
                'bkap_enable_booking_edit',
                __( 'Allow Bookings to be editable:', 'woocommerce-booking' ),
                array( &$this, 'bkap_allow_bookings_callback' ),
                'bkap_global_settings_page',
                'bkap_global_settings_section',
                array( __( 'Enabling this option will allow Bookings to be editable from Cart and Checkout page', 'woocommerce-booking' ) )
            );

            add_settings_field(
                'bkap_enable_booking_reschedule',
                __( 'Allow Bookings to be reschedulable:', 'woocommerce-booking' ),
                array( &$this, 'bkap_allow_reschedulable_callback' ),
                'bkap_global_settings_page',
                'bkap_global_settings_section',
                array( __( 'Enabling this option will allow Bookings to be reschedulable from My Account page', 'woocommerce-booking' ) )
            );

            add_settings_field(
                'bkap_booking_reschedule_days',
                __( 'Minimum number of days for rescheduling:', 'woocommerce-booking' ),
                array( &$this, 'bkap_reschedulable_days_callback' ),
                'bkap_global_settings_page',
                'bkap_global_settings_section',
                array( __( 'Minimum number of days before the booking date, after which Booking cannot be rescheduled.', 'woocommerce-booking' ) )
            );
        }

        /**
         * Call back for displaying settings option for Cart/Checkout page
         */
        public function bkap_allow_bookings_callback( $args ) {
            
            $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
            $bkap_enable_booking_option = '';
            if( isset( $saved_settings->bkap_enable_booking_edit ) && 
                $saved_settings->bkap_enable_booking_edit === 'on' ){

                $bkap_enable_booking_option = 'checked';
            }
            
            ?>
                <input 
                    type="checkbox" 
                    id="bkap_enable_booking_edit" 
                    name="woocommerce_booking_global_settings[bkap_enable_booking_edit]"
                    <?php echo $bkap_enable_booking_option; ?>
                />
                <label for="bkap_enable_booking_edit">
                    <?php echo $args[ 0 ]; ?>
                </label>
            <?php
        }

        /**
         * Call back for displaying settings option for My Account page
         */
        public function bkap_allow_reschedulable_callback( $args ) {
            
            $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
            $bkap_enable_booking_reschedule = '';
            if( isset( $saved_settings->bkap_enable_booking_reschedule ) && 
                $saved_settings->bkap_enable_booking_reschedule === 'on' ){

                $bkap_enable_booking_reschedule = 'checked';
            }
            
            ?>
                <input 
                    type="checkbox" 
                    id="bkap_enable_booking_reschedule" 
                    name="woocommerce_booking_global_settings[bkap_enable_booking_reschedule]"
                    <?php echo $bkap_enable_booking_reschedule; ?>
                />
                <label for="bkap_enable_booking_reschedule">
                    <?php echo $args[ 0 ]; ?>
                </label>
            <?php
        }

        /**
         * Call back for displaying settings option for rescheduling period
         */
        public function bkap_reschedulable_days_callback( $args ) {
            
            $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
            $bkap_booking_reschedule_days = 0;
            if( isset( $saved_settings->bkap_booking_reschedule_days ) && 
                $saved_settings->bkap_booking_reschedule_days !== '' ){

                $bkap_booking_reschedule_days = $saved_settings->bkap_booking_reschedule_days;
            }
            
            ?>
                <input 
                    type="text" 
                    id="bkap_booking_reschedule_days" 
                    name="woocommerce_booking_global_settings[bkap_booking_reschedule_days]"
                    value="<?php echo $bkap_booking_reschedule_days; ?>"
                />
                <label for="bkap_booking_reschedule_days">
                    <?php echo $args[ 0 ]; ?>
                </label>
            <?php
        }

    }
}
