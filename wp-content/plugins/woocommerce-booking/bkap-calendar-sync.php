<?php 
// Add a new interval 
add_filter( 'cron_schedules', 'woocommerce_bkap_add_cron_schedule' );

function woocommerce_bkap_add_cron_schedule( $schedules ) {
    
    $duration = get_option( 'bkap_cron_time_duration' );
    
    if ( isset( $duration ) && $duration > 0 ) {
        $duration_in_seconds = $duration * 60;
    } else {
        $duration_in_seconds = 86400;
    }
    
    $schedules['bkap_gcal_import'] = array(
        'interval' => $duration_in_seconds,  // 24 hours in seconds
        'display'  => __( 'Booking & Appointment - GCal Import Events' ),
    );
    
    return $schedules;
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'woocommerce_bkap_import_events' ) ) {
    wp_schedule_event( time(), 'bkap_gcal_import', 'woocommerce_bkap_import_events' );
}

// Hook into that action that'll fire once every day
add_action( 'woocommerce_bkap_import_events', 'bkap_import_events_cron' );
function bkap_import_events_cron() {
    $calendar_sync = new bkap_calendar_sync();
    $calendar_sync->bkap_setup_import();
}

class bkap_calendar_sync {
    public function __construct() {
        $this->gcal_api = false;
        $this->email_ID = '';
        
        add_action( 'wp_loaded', array( $this, 'bkap_setup_gcal_sync' ), 10 );
        add_action( 'admin_init', array( $this, 'bkap_setup_gcal_sync' ), 10 );
        
        $this->plugin_dir = plugin_dir_path( __FILE__ );
        $this->plugin_url = plugins_url( basename( dirname( __FILE__ ) ) );
        
        add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'bkap_google_calendar_update_order_meta' ), 11 );
        
        add_action( 'woocommerce_order_item_meta_end', array( &$this, 'bkap_add_to_woo_pages' ), 11, 3 );
        
        // These 2 hooks have been added to figure out which email is being sent, the new order email (which goes to the admin) 
        // or the customer processing order, on hold order or ccompleted order ( which goes to the customer)
        add_filter( 'woocommerce_email_subject_new_order', array( &$this, 'bkap_new_order_email' ), 10, 1 );
        add_filter( 'woocommerce_email_subject_customer_processing_order', array( &$this, 'bkap_customer_email' ), 10, 1 );
        add_filter( 'woocommerce_email_subject_customer_on_hold_order', array( &$this, 'bkap_customer_email' ), 10, 1 );
        add_filter( 'woocommerce_email_subject_customer_completed_order', array( &$this, 'bkap_customer_email' ), 10, 1 );
        
        if( get_option( 'bkap_add_to_calendar_customer_email' ) == 'on' ) {
            add_action( 'woocommerce_order_item_meta_end', array( &$this, 'bkap_add_to_calendar_customer'), 12, 3 );
        }
        
        if( get_option( 'bkap_admin_add_to_calendar_email_notification' ) == 'on' && get_option( 'bkap_calendar_sync_integration_mode' ) == 'manually' ) {
            add_action( 'woocommerce_order_item_meta_end', array( &$this, 'bkap_add_to_calendar_admin'), 13, 3 );
        }
        
        add_action( 'wp_ajax_bkap_save_ics_url_feed', array( &$this, 'bkap_save_ics_url_feed' ) );
        
        add_action( 'wp_ajax_bkap_delete_ics_url_feed', array( &$this, 'bkap_delete_ics_url_feed' ) );
        
        add_action( 'wp_ajax_bkap_import_events', array( &$this, 'bkap_setup_import' ) );
        
        add_action( 'wp_ajax_bkap_admin_booking_calendar_events', array( &$this, 'bkap_admin_booking_calendar_events' ) );
        
        require_once $this->plugin_dir . 'includes/iCal/SG_iCal.php';
    }
    
    function bkap_new_order_email( $subject ) {
        $this->email_ID = 'new_order';
        return $subject;
    }
    
    function bkap_customer_email( $subject ) {
        $this->email_ID = 'customer_order';
        return $subject;
    }
    
    function bkap_setup_gcal_sync () {
        // GCal Integration
        $this->gcal_api = false;
        // Allow forced disabling in case of emergency
        require_once $this->plugin_dir . '/includes/class.gcal.php';
        $this->gcal_api = new BKAP_Gcal();
    }
    
    function bkap_google_calendar_update_order_meta( $order_id ) {
        
        global $wpdb;
        
        $gcal = new BKAP_Gcal();
        
        $user = get_user_by( 'email', get_option( 'admin_email' ) );
        $admin_id = 0;
        if ( isset( $user->ID ) ) {
            $admin_id = $user->ID;
        } else {
            // get the list of administrators
            $args = array( 'role' => 'administrator', 'fields' => array( 'ID' ) );
            $users = get_users( $args );
            if ( isset( $users ) && count( $users ) > 0 ) {
                $admin_id = $users[ 0 ]->ID;
            }
        }
        $order_item_ids   =   array();
        foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
            $user_id = $admin_id;
        
            $post_id = bkap_common::bkap_get_product_id( $values[ 'product_id' ] );
            $is_bookable = bkap_common::bkap_get_bookable_status( $post_id );
        
            // get the mode for the product settings as well if applicable
            if( $gcal->get_api_mode( $admin_id, $post_id ) == "directly" ) {
            
                $sub_query        =   "";
                
                $booking_settings = get_post_meta( $post_id, 'woocommerce_booking_settings', true );
                // check if tour operators are allowed to setup GCal
                if ( 'yes' == get_option( 'bkap_allow_tour_operator_gcal_api' ) ) {
                    // if tour operator addon is active, pass the tour operator user Id else the admin ID
                    if ( function_exists( 'is_bkap_tours_active' ) && is_bkap_tours_active() ) {
                        
                        if ( $is_bookable ) {
                            
                            if( isset( $booking_settings[ 'booking_tour_operator' ] ) &&  $booking_settings[ 'booking_tour_operator' ] != 0 ) {
                                $user_id = $booking_settings[ 'booking_tour_operator' ];
                            }
                        }
                    }
                }
                
                // check if it's for the admin, else the tour operator addon will do the needful
                if ( isset( $values[ 'bkap_booking' ] ) && isset( $user_id ) && $user_id == $admin_id ) {
                    
                    $_data    =   $values[ 'data' ];
                    $_booking    =   $values[ 'bkap_booking' ][0];
                    
                    if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
                        $post_title = $_data->post->post_title;
                    } else {
                        $post_data = $_data->get_data();
                        $post_title = $post_data[ 'name' ];
                    }
                    // Fetch line item
                    if ( count( $order_item_ids ) > 0 ) {
                        $order_item_ids_to_exclude  = implode( ",", $order_item_ids );
                        $sub_query                  = " AND order_item_id NOT IN (".$order_item_ids_to_exclude.")";
                    }
                    
                    $query               =   "SELECT order_item_id,order_id FROM `".$wpdb->prefix."woocommerce_order_items`
						              WHERE order_id = %s AND order_item_name = %s".$sub_query;
                    
                    $results             =   $wpdb->get_results( $wpdb->prepare( $query, $order_id, $post_title ) );
                    
                    if ( isset( $results[0] ) ) {
                        $order_item_ids[]    =   $results[0]->order_item_id;
                        
                        // check the booking status, if pending confirmation, then do not insert event in the calendar
                        $booking_status = wc_get_order_item_meta( $results[0]->order_item_id, '_wapbk_booking_status' );
                        
                        if ( ( isset( $booking_status ) && 'pending-confirmation' != $booking_status ) || ( ! isset( $booking_status )) ) {
    
                            // ensure it's a future dated event
                            $is_date_set = false;
                            if ( isset( $_booking[ 'hidden_date' ] ) ) {
                                $day = date( 'Y-m-d', current_time( 'timestamp' ) );
                                
                                if ( strtotime( $_booking[ 'hidden_date' ] ) >= strtotime( $day ) ) {
                                    $is_date_set = true;
                                } 
                            }
                            if ( $is_date_set ) {
                            
                                $event_details = array();
                                
                                $event_details[ 'hidden_booking_date' ] = $_booking[ 'hidden_date' ];
                                
                                if ( isset( $_booking[ 'hidden_date_checkout' ] ) && $_booking[ 'hidden_date_checkout' ] != '' ) {

                                    if ( isset( $booking_settings[ 'booking_charge_per_day' ] ) && 'on' == $booking_settings[ 'booking_charge_per_day' ] ) {
                                      
                                      $event_details[ 'hidden_checkout_date' ] = date( 'Y-m-d', strtotime( '+1 day', strtotime( $_booking[ 'hidden_date_checkout' ] ) ) );
                                   
                                    } else {

                                    $event_details[ 'hidden_checkout_date' ] = $_booking[ 'hidden_date_checkout' ];
                                    }
                                }
                                
                                if ( isset( $_booking[ 'time_slot' ] ) && $_booking[ 'time_slot' ] != '' ) {
                                    $event_details[ 'time_slot' ] = $_booking[ 'time_slot' ];
                                }
                                
                                $event_details[ 'billing_email' ] = $_POST[ 'billing_email' ];
                                $event_details[ 'billing_first_name' ] = $_POST[ 'billing_first_name' ];
                                $event_details[ 'billing_last_name' ] = $_POST[ 'billing_last_name' ];
                                $event_details[ 'billing_address_1' ] = $_POST[ 'billing_address_1' ];
                                $event_details[ 'billing_address_2' ] = $_POST[ 'billing_address_2' ];
                                $event_details[ 'billing_city' ] = $_POST[ 'billing_city' ];
                            
                                $event_details[ 'billing_phone' ] = $_POST[ 'billing_phone' ];
                                $event_details[ 'order_comments' ] = $_POST[ 'order_comments' ];
                                $event_details[ 'order_id' ] = $order_id;
                                
                                
                                if ( isset( $_POST[ 'shipping_first_name' ] ) && $_POST[ 'shipping_first_name' ] != '' ) {
                                    $event_details[ 'shipping_first_name' ] = $_POST[ 'shipping_first_name' ];
                                }
                                if ( isset( $_POST[ 'shipping_last_name' ] ) && $_POST[ 'shipping_last_name' ] != '' ) {
                                    $event_details[ 'shipping_last_name' ] = $_POST[ 'shipping_last_name' ];
                                }
                                if( isset( $_POST[ 'shipping_address_1' ] ) && $_POST[ 'shipping_address_1' ] != '' ) {
                                    $event_details[ 'shipping_address_1' ] = $_POST[ 'shipping_address_1' ];
                                }
                                if ( isset( $_POST[ 'shipping_address_2' ] ) && $_POST[ 'shipping_address_2' ] != '' ) {
                                    $event_details[ 'shipping_address_2' ] = $_POST[ 'shipping_address_2' ];
                                }
                                if ( isset( $_POST[ 'shipping_city' ] ) && $_POST[ 'shipping_city' ] != '' ) { 
                                    $event_details[ 'shipping_city' ] = $_POST[ 'shipping_city' ];
                                }
                                
                                $event_details[ 'product_name' ] = $post_title;
                                $event_details[ 'product_qty' ] = $values[ 'quantity' ];
                                
                                $event_details[ 'product_total' ] = $values[ 'line_total' ];
                                
                                // if sync is disabled at the product level, set post_id to 0 to ensure admin settings are taken into consideration
                                if ( ( ! isset( $booking_settings[ 'product_sync_integration_mode' ] ) ) || ( isset( $booking_settings[ 'product_sync_integration_mode' ] ) && 'disabled' == $booking_settings[ 'product_sync_integration_mode' ] ) ) {
                                    $post_id = 0;
                                }
                                $gcal->insert_event( $event_details, $results[0]->order_item_id, $user_id, $post_id, false );
                                
                                // add an order note, mentioning an event has been created for the item
                                $order = new WC_Order( $order_id );
                                
                                $order_note = __( "Booking_details for $post_title have been exported to the Google Calendar", 'woocommerce-booking' );
                                $order->add_order_note( $order_note );
                            }
                        }
                    }
                }
            }
     
        }
    }
    
    function bkap_add_to_calendar_customer( $item_id, $item, $order ) {
        if ( ! is_account_page() && ! is_wc_endpoint_url( 'order-received' ) ) {
            
            // check the email ID
            if ( 'customer_order' == $this->email_ID ) {
            
                // check if it's a bookable product
                $bookable = bkap_common::bkap_get_bookable_status( $item[ 'product_id' ] );
                $valid_date = false;
                if ( isset( $item[ 'wapbk_booking_date' ] ) ) {
                    $valid_date = bkap_common::bkap_check_date_set( $item[ 'wapbk_booking_date' ] );
                }
                if ( $bookable  && $valid_date ) {
                    $bkap = $this->bkap_create_gcal_obj( $item_id, $item, $order );
                    $this->bkap_add_buttons_emails( $bkap );
                }
            }
            
        }
    }
    
    function bkap_add_to_calendar_admin( $item_id, $item, $order ) {
        if ( ! is_account_page() && ! is_wc_endpoint_url( 'order-received' ) ) {
            
            if ( 'new_order' == $this->email_ID ) {
                // check if it's a bookable product
                $post_id = bkap_common::bkap_get_product_id( $item[ 'product_id' ] );
                
                $bookable = bkap_common::bkap_get_bookable_status( $post_id );
                
                $valid_date = false;
                if ( isset( $item[ 'wapbk_booking_date' ] ) ) {
                    $valid_date = bkap_common::bkap_check_date_set( $item[ 'wapbk_booking_date' ] );
                }
                if ( $bookable && $valid_date ) {
                    
                    // check if tour operators are allowed to setup GCal
                    if ( 'yes' == get_option( 'bkap_allow_tour_operator_gcal_api' ) ) {
                        // if tour operator addon is active, return if an operator is assigned
                        if ( function_exists( 'is_bkap_tours_active' ) && is_bkap_tours_active() ) {
                    
                            $booking_settings = get_post_meta( $post_id, 'woocommerce_booking_settings', true );
                    
                            if( isset( $booking_settings[ 'booking_tour_operator' ] ) &&  $booking_settings[ 'booking_tour_operator' ] != 0 ) {
                                return;
                            }
                    
                        }
                    }
                    $bkap = $this->bkap_create_gcal_obj( $item_id, $item, $order );
                    $this->bkap_add_buttons_emails( $bkap );
                }
            }
        }
    }
    
    function bkap_add_to_woo_pages( $item_id, $item, $order ) {
        
        if ( is_account_page() && 'on' == get_option( 'bkap_add_to_calendar_my_account_page' ) ) {
            
            // check if it's a bookable product
            $bookable = bkap_common::bkap_get_bookable_status( $item[ 'product_id' ] );
            
            $valid_date = false;
            if ( isset( $item[ 'wapbk_booking_date' ] ) ) {
                $valid_date = bkap_common::bkap_check_date_set( $item[ 'wapbk_booking_date' ] );
            }
            if ( $bookable && $valid_date ) {
                wp_enqueue_style( 'gcal_sync_style', plugins_url( '/css/calendar-sync.css', __FILE__ ) , '', '', false );
                $bkap = $this->bkap_create_gcal_obj( $item_id, $item, $order );
                $this->bkap_add_buttons( $bkap );
            }
            
        }
        if( is_wc_endpoint_url( 'order-received' ) && 'on' == get_option( 'bkap_add_to_calendar_order_received_page' ) ) {
            
            // check if it's a bookable product
            $bookable = bkap_common::bkap_get_bookable_status( $item[ 'product_id' ] );
            
            $valid_date = false;
            if ( isset( $item[ 'wapbk_booking_date' ] ) ) {
                $valid_date = bkap_common::bkap_check_date_set( $item[ 'wapbk_booking_date' ] );
            }
            if ( $bookable && $valid_date ) {
                wp_enqueue_style( 'gcal_sync_style', plugins_url( '/css/calendar-sync.css', __FILE__ ) , '', '', false );
                $bkap = $this->bkap_create_gcal_obj( $item_id, $item, $order );
                $this->bkap_add_buttons( $bkap );
            }
        }
        
    }
    
    function bkap_create_gcal_obj( $item_id, $item, $order_details ) {

        $order_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order_details->id : $order_details->get_id();
        $order_data = get_post_meta( $order_id );
        $order = new WC_Order( $order_id );
        
        $bkap = new stdClass();
         
        $bkap->item_id = $item_id;
        $valid_date = bkap_common::bkap_check_date_set( $item[ 'wapbk_booking_date' ] );
        
        if ( $valid_date ) {
            $bkap->start = $item[ 'wapbk_booking_date' ];
        
            $bkap->client_address = __( $order_data[ '_shipping_address_1' ][ 0 ] . " " . $order_data[ '_shipping_address_2' ][ 0 ] , 'woocommerce-booking' );
            $bkap->client_city = __( $order_data[ '_shipping_city' ][ 0 ], 'woocommerce-booking' );
             
            if ( isset( $item[ 'wapbk_checkout_date' ] ) && $item[ 'wapbk_checkout_date' ] != '' ) {
                $bkap->end = $item[ 'wapbk_checkout_date' ];
            } else {
                $bkap->end = $item[ 'wapbk_booking_date' ];
            }
         
            if( isset( $item[ 'wapbk_time_slot' ] ) && $item[ 'wapbk_time_slot' ] != '' ) {
                $timeslot = explode( " - ", $item[ 'wapbk_time_slot' ] );
                $from_time = date( "H:i", strtotime( $timeslot[ 0 ] ) );
                
                if( isset( $timeslot[ 1 ] ) && $timeslot[ 1 ] != '' ) {
                    $to_time = date( "H:i", strtotime( $timeslot[ 1 ] ) );
                    $bkap->end_time = $to_time;
                    $time_end = explode( ':', $to_time );
                } else {
                    $bkap->end_time = $from_time;
                    $time_end = explode( ':', $from_time );
                }
                
                $bkap->start_time = $from_time;
            } else {
                $bkap->start_time = "";
                $bkap->end_time = "";
            }
            
            $bkap->client_email = $order_data[ '_billing_email' ][ 0 ];
            $bkap->client_name = $order_data[ '_billing_first_name' ][ 0 ] . " " . $order_data[ '_billing_last_name' ][ 0 ];
            $bkap->client_address = $order_data[ '_billing_address_1' ][ 0 ]  . " " . $order_data[ '_billing_address_2' ][ 0 ];
            $bkap->client_phone = $order_data[ '_billing_phone' ][ 0 ];
            $bkap->order_note  = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->customer_note : $order->get_customer_note();
            
            $product = $product_with_qty = "";
            
            $product = $item[ 'name' ];
            $product_with_qty = $item[ 'name' ] . "(QTY: " . $item[ 'qty' ] . ") ";
             
            $bkap->order_total  = $item[ 'line_total' ];
            $bkap->product = $product;
            $bkap->product_with_qty = $product_with_qty;

            if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
                $bkap->order_date_time = $order->post->post_date;
                $order_date = date( "Y-m-d", strtotime( $order->post->post_date ) );    
            } else {
                $order_post = get_post( $order_id );
                $post_date = strtotime ( $order_post->post_date );
                $bkap->order_date_time = date( 'Y-m-d H:i:s', $post_date );
                $order_date = date( 'Y-m-d', $post_date );
            /*    $order_created_obj = $order->get_date_created();
                $bkap->order_date_time = $order_created_obj->format( 'Y-m-d H:i:s' );
                $order_date = $order_created_obj->format( 'Y-m-d' ); */
            }
            
            $bkap->order_date = $order_date;
            $bkap->id = $order_id;
              
            return $bkap;
        }
        
    }
    
    function bkap_add_buttons_emails( $bkap ) {
        
        $gcal = new BKAP_Gcal();
        $href = $gcal->gcal( $bkap );
        $other_calendar_href = $gcal->other_cal( $bkap );
        
        $target = '_blank';
        
        if( get_option( 'bkap_calendar_in_same_window' ) == 'on' ) {
            $target = '_self';
        } else {
            $target = '_blank';
        }
        
        ?>
        <form method="post" action="<?php echo $href; ?>" target= "<?php echo $target; ?>" id="add_to_google_calendar_form">
            <input type="submit" id="add_to_google_calendar" name="add_to_google_calendar" value="<?php _e( 'Add to Google Calendar', 'woocommerce-booking' ); ?>" />
        </form>
        <form method="post" action="<?php echo $other_calendar_href; ?>" target="<?php echo $target; ?>" id="add_to_other_calendar_form">
            <input type="submit" id="download_ics" name="download_ics" value="<?php _e( 'Add to other Calendar', 'woocommerce-booking' ); ?>" />
        </form>
                
        <?php 
    }
    
    function bkap_add_buttons( $bkap ) {
        
        $gcal = new BKAP_Gcal();
        $href = $gcal->gcal( $bkap );
        $other_calendar_href = $gcal->other_cal( $bkap );
        
        $target = '_blank';
        
        if( get_option( 'bkap_calendar_in_same_window' ) == 'on' ) {
            $target = '_self';
        } else {
            $target = '_blank';
        }
                
        ?>
        <div class="add_to_calendar">
            <button onclick="myFunction( <?php echo $bkap->item_id; ?> )" class="dropbtn"><?php _e( "Add To Calendar", 'woocommerce-booking' ); ?><i class="claret"></i></button>
            <div id="add_to_calendar_menu_<?php echo $bkap->item_id; ?>" class="add_to_calendar-content">
                <a href="<?php echo $href; ?>" target= "<?php echo $target; ?>" id="add_to_google_calendar" ><img class="icon" src="<?php echo plugins_url(); ?>/woocommerce-booking/images/google-icon.ico"><?php _e( "Add to Google Calendar", 'woocommerce-booking' ); ?></a>
                <a href="<?php echo $other_calendar_href; ?>" target="<?php echo $target; ?>" id="add_to_other_calendar" ><img class="icon" src="<?php echo plugins_url(); ?>/woocommerce-booking/images/calendar-icon.ico"><?php _e( "Add to other Calendar", 'woocommerce-booking' ); ?></a>
            </div>
        </div>

        <script type="text/javascript">
        /* When the user clicks on the button, 
        toggle between hiding and showing the dropdown content */

        function myFunction( chk ) {
            document.getElementById( "add_to_calendar_menu_"+ chk ).classList.toggle( "show" );
        }
        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            if ( !event.target.matches( '.dropbtn' ) ) {
                var dropdowns = document.getElementsByClassName( "dropdown-add_to_calendar-content" );
        		var i;
        		for ( i = 0; i < dropdowns.length; i++ ) {
        		    var openDropdown = dropdowns[i];
    		    	if ( openDropdown.classList.contains( 'show' ) ) {
    		    	    openDropdown.classList.remove( 'show' );
    		    	}
        		}
        	}
        }

        </script>
        <?php 

    }
        
    function bkap_save_ics_url_feed() {
        $ics_table_content = '';
        if( isset( $_POST[ 'ics_url' ] ) ) {
            $ics_url = $_POST[ 'ics_url' ];
        } else {
            $ics_url = '';
        }
    
        if( $ics_url != '' ) {
            $ics_feed_urls = get_option( 'bkap_ics_feed_urls' );
            if( $ics_feed_urls == '' || $ics_feed_urls == '{}' || $ics_feed_urls == '[]' || $ics_feed_urls == 'null' ) {
                $ics_feed_urls = array();
            }
    
            if( !in_array( $ics_url, $ics_feed_urls ) ) {
                array_push( $ics_feed_urls, $ics_url );
                update_option( 'bkap_ics_feed_urls', $ics_feed_urls );
                $ics_table_content = 'yes';
            }
        }
    
        echo $ics_table_content;
        die();
    }
    
    function bkap_delete_ics_url_feed() {
        $ics_table_content = '';
        if( isset( $_POST[ 'ics_feed_key' ] ) ) {
            $ics_url_key = $_POST[ 'ics_feed_key' ];
        } else {
            $ics_url_key = '';
        }

        $product_id = 0;
        if ( isset( $_POST[ 'product_id' ] ) ) {
            $product_id = $_POST[ 'product_id' ];
        }
        
        if( $ics_url_key != '' ) {
            if ( isset( $product_id ) && $product_id > 0 ) {
            
                $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
            
                if ( is_array( $booking_settings[ 'ics_feed_url' ] ) && count( $booking_settings[ 'ics_feed_url' ] ) > 0 ) {
                    $ics_feed_urls = $booking_settings[ 'ics_feed_url' ];
                    if( $ics_feed_urls == '' || $ics_feed_urls == '{}' || $ics_feed_urls == '[]' || $ics_feed_urls == 'null' ) {
                        $ics_feed_urls = array();
                    }
            
                    unset( $ics_feed_urls[ $ics_url_key ] );
                    $booking_settings[ 'ics_feed_url' ] = $ics_feed_urls;
                    update_post_meta( $product_id, 'woocommerce_booking_settings', $booking_settings );
                    
                    // update the individual settings
                    update_post_meta( $product_id, '_bkap_import_url', $ics_feed_urls );
                    $ics_table_content = 'yes';
                }
            } else {
            
                $ics_feed_urls = get_option( 'bkap_ics_feed_urls' );
                if( $ics_feed_urls == '' || $ics_feed_urls == '{}' || $ics_feed_urls == '[]' || $ics_feed_urls == 'null' ) {
                    $ics_feed_urls = array();
                }
        
                unset( $ics_feed_urls[ $ics_url_key ] );
                update_option( 'bkap_ics_feed_urls', $ics_feed_urls );
                $ics_table_content = 'yes';
            }
        }
    
        echo $ics_table_content;
        die();
    }
    
    public function bkap_setup_import() {
            
        global $wpdb;
        
        if( isset( $_POST[ 'ics_feed_key' ] ) ) {
            $ics_url_key = $_POST[ 'ics_feed_key' ];
        } else {
            $ics_url_key = '';
        }
         
        $product_id = 0;
        if ( isset( $_POST[ 'product_id' ] ) ) {
            $product_id = $_POST[ 'product_id' ];
        }
        
        if ( $product_id == 0 ) {
            $ics_feed_urls = get_option( 'bkap_ics_feed_urls' );
        } else if ( $product_id > 0 ) {
            $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
        
            if ( is_array( $booking_settings[ 'ics_feed_url' ] ) && count( $booking_settings[ 'ics_feed_url' ] ) > 0 ) {
                $ics_feed_urls = $booking_settings[ 'ics_feed_url' ];
            }
        }
        if( $ics_feed_urls == '' || $ics_feed_urls == '{}' || $ics_feed_urls == '[]' || $ics_feed_urls == 'null' ) {
            $ics_feed_urls = array();
        }
        
        if( count( $ics_feed_urls ) > 0 && isset( $ics_feed_urls[ $ics_url_key ] ) ) {
            $ics_feed = $ics_feed_urls[ $ics_url_key ];
            $ics_feed = str_replace( 'https://', '', $ics_feed );
        } else { 
            $ics_feed = '';
        }
        
        if ( $ics_feed == '' && count( $_POST ) <= 0 ) { // it means it was called using cron, so we need to auto import for all the calendars saved
            // run the import for all the calendars saved at the admin level
            if ( isset( $ics_feed_urls ) && count( $ics_feed_urls ) > 0 ) {
                
                foreach ( $ics_feed_urls as $ics_feed ) {
                    $ics_feed = str_replace( 'https://', '', $ics_feed );
                    $ical = new BKAP_iCalReader( $ics_feed );
                    $ical_array = $ical->getEvents();
                    
                    // check if the import is on an AirBNB Cal
                    if ( strpos( $ics_feed, 'airbnb' ) > 0 ) {
                        $airbnb = true;
                    } else {
                        $airbnb = false;
                    }
                    $this->bkap_import_events( $ical_array, 0, $airbnb );
                }
                
            }
            
            // run the import for all the calendars saved at the product level
            $args       = array( 'post_type' => 'product', 'posts_per_page' => -1 );
            $product    = query_posts( $args );
            
            foreach($product as $k => $v){
                $product_ids[] = $v->ID;
            }
            
            foreach( $product_ids as $k => $v ){
                $duplicate_of  = bkap_common::bkap_get_product_id( $v );
            
                $is_bookable = bkap_common::bkap_get_bookable_status( $duplicate_of );
            
                if ( $is_bookable ) {
                    $booking_settings = get_post_meta( $duplicate_of, 'woocommerce_booking_settings' , true );
            
                    if ( isset( $booking_settings[ 'ics_feed_url' ] ) && count( $booking_settings[ 'ics_feed_url' ] ) > 0 ) {
                        foreach ( $booking_settings[ 'ics_feed_url' ] as $key => $value ) {
                            
                            $value = str_replace( 'https://', '', $value );
                            $ical = new BKAP_iCalReader( $value );
                            $ical_array = $ical->getEvents();
                            
                            // check if the import is on an AirBNB Cal
                            if ( strpos( $value, 'airbnb' ) > 0 ) {
                                $airbnb = true;
                            } else {
                                $airbnb = false;
                            }
                            $this->bkap_import_events( $ical_array, $duplicate_of, $airbnb );
                        }
                    }
            
                }
            }
        } else {
            $ical = new BKAP_iCalReader( $ics_feed );
            $ical_array = $ical->getEvents();
            
            // check if the import is on an AirBNB Cal
            if ( strpos( $ics_feed, 'airbnb' ) > 0 ) {
                $airbnb = true;
            } else {
                $airbnb = false;
            }
            $this->bkap_import_events( $ical_array, $product_id, $airbnb );
            
        }
        
        die();
    }
    
    public function bkap_import_events( $ical_array, $product_id = 0, $airbnb = false ) {
        
        global $wpdb;
        
        if ( isset( $product_id ) && 0 == $product_id ) {
            $event_uids = get_option( 'bkap_event_uids_ids' );
        } else {
            $event_uids = get_post_meta( $product_id, 'bkap_event_uids_ids', true );
        }
        
        if( $event_uids == '' || $event_uids == '{}' || $event_uids == '[]' || $event_uids == 'null' ) {
            $event_uids = array();
        }
        
        if( isset( $ical_array ) ) {
        
            // get the last stored event count
            $options_query = "SELECT option_name FROM `" . $wpdb->prefix. "options`
                                        WHERE option_name like 'bkap_imported_events_%'";
        
            $results = $wpdb->get_results( $options_query );
        
            if (isset( $results ) && count( $results ) > 0 ) {
                $last_count = 0;
                foreach ( $results as $results_key => $option_name ) {
                    $explode_array = explode( '_', $option_name->option_name );
                    $current_id = $explode_array[3];
                    
                    if ( $last_count < $current_id ) {
                        $last_count = $current_id;
                    }
                }
                
                $i = $last_count + 1;
                
            } else {
                $i = 0;
            }
            foreach( $ical_array as $key_event => $value_event ) {

                $uid = '';
                if ( $airbnb ) {
                    $summary = $value_event->summary;
                
                    if ( strpos( $summary, '(' ) > 0 ) {
                        $start = strpos( $summary, '(' );
                        $start += 1;
                        $end = strpos( $summary, ')' );
                        $length = $end - $start;
                        $uid = substr( $summary, $start, $length );
                    }
                } else {
                    $uid = $value_event->uid;
                }
                
                if ( $uid != '' ) {
                    //Do stuff with the event $event
                    if( !in_array( $uid, $event_uids ) ) {
                        // gmt time stamp as Google sends the UTC timestamp
                        $current_time = current_time( 'timestamp', 1 );
                        
                        // Import future dated events
                        if ( $value_event->start >= $current_time || $value_event->end >= $current_time ) {
                        
                            $option_name = 'bkap_imported_events_' . $i;        
                            add_option( $option_name, json_encode( $value_event ) );
                            
                            array_push( $event_uids, $uid );
                            if ( isset( $product_id ) && 0 == $product_id ) {
                                update_option( 'bkap_event_uids_ids', $event_uids );
                                
                                
                                $status = "bkap-unmapped";
                                bkap_calendar_sync::bkap_create_gcal_event_post( $value_event, $product_id , $status, $option_name );
                                
                            } else {
                                update_post_meta( $product_id, 'bkap_event_uids_ids', $event_uids );
                            
                                // get the product type
                                $_product = wc_get_product( $product_id );
                                $automated_mapping = 'NO';
                                
                                $status = "bkap-unmapped";
                                $created_event_post = bkap_calendar_sync::bkap_create_gcal_event_post( $value_event, $product_id , $status, $option_name );
                                
                                $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
                                if ( isset ( $booking_settings[ 'enable_automated_mapping' ] ) && 'on' == $booking_settings[ 'enable_automated_mapping' ] ) {
                                    $automated_mapping = 'YES';
                                    $product_id_to_map = $product_id;
                                    
                                    if ( 'variable' == $_product->get_type() ) {
                                        if ( isset( $booking_settings[ 'gcal_default_variation' ] ) && '' != $booking_settings[ 'gcal_default_variation' ] ) {
                                            $product_id_to_map = $booking_settings[ 'gcal_default_variation' ];
                                            $automated_mapping = 'YES';
                                        } else {
                                            $automated_mapping = 'NO';
                                        }
                                        
                                    }
                                }
                            
                                if ( isset( $automated_mapping ) && 'YES' == $automated_mapping ) {
                                    
                                    $_POST[ 'ID' ]          = $created_event_post->id;
                                    $_POST[ 'product_id' ]  = $product_id_to_map;
                                    $_POST[ 'automated' ]   = 1;
                                    $_POST[ 'type' ]        = "by_post";
                                    
                                    // all the events will be mapped to the product.
                                    $import_bookings = new import_bookings();
                                    $import_bookings->bkap_map_imported_event();
                                } else {
                                    $user_id = 0;
                                    // if the tours addon is active, then the tour operator should receive the email
                                    if ( function_exists( 'is_bkap_tours_active' ) && is_bkap_tours_active() ) {
                                        // check if tour operators are allowed to setup GCal
                                        if ( 'yes' == get_option( 'bkap_allow_tour_operator_gcal_api' ) ) {
                                            // fetch teh tour operators ID
                                            if( isset( $booking_settings[ 'booking_tour_operator' ] ) &&  $booking_settings[ 'booking_tour_operator' ] != 0 ) {
                                                $user_id = $booking_settings[ 'booking_tour_operator' ];
                                            }
                                        }
                                    }
                            
                                    do_action( 'bkap_gcal_events_imported', $option_name, $user_id );
                                }
                            }
                        }
                    }    
                }
                $i++;
            }
            echo "All the Events are Imported.";
        }
        
    }
    
    
    /**
     * Creates & returns a booking post meta record
     * array to be inserted in post meta.
     * @param int $item_id
     * @param int $product_id
     * @param array $booking_details
     * @since 4.2
     */
    

    public static function bkap_create_gcal_event_post( $bkap_event, $product_id = 0, $status, $option_name = '', $user_id = 1 ) {
        
        $new_event_data = array();
        
        // Merge booking data
        $defaults = array(
            'user_id'          => $user_id,
            'product_id'       => $product_id,
            'start_date'       => '',
            'end_date'         => '',
            'uid'              => '',
            'summary'          => '',
            'description'      => '',
            'location'         => '',
            'reason_of_fail'   => '',
            'resource_id'      => '',
            'persons'          => array(),
            'qty'              => 1,
            'variation_id'     => 0,
            'event_option_name'=> ''
        );
        
        $new_event_data = wp_parse_args( $new_event_data, $defaults );
        
        $event_value_set = "";
        
        if ( is_object( $bkap_event ) && "" != $bkap_event ) {
           $event_uid           = $bkap_event->uid;
           
           $event_start_str     = $bkap_event->start;
           
           $event_end_str       = $bkap_event->end;
            
           $event_summary       = $bkap_event->summary;
           $event_description   = $bkap_event->description;
           $event_location      = $bkap_event->location;
           $event_value_set     = "value_set";
        }
        
        if ( "" != $event_value_set ) {
            $new_event_data['user_id']          = $user_id;
            $new_event_data['uid']              = $event_uid;
            $new_event_data['start_date']       = $event_start_str;
            $new_event_data['end_date']         = $event_end_str;
            $new_event_data['summary']          = $event_summary;
            $new_event_data['description']      = $event_description;
            $new_event_data['location']         = $event_location;
            $new_event_data['reason_of_fail']   = "";
            $new_event_data['event_option_name']= $option_name;
        }
        
        // Create it
        $new_booking_event = bkap_calendar_sync::get_bkap_booking( $new_event_data ); // jyare BKAP_Gcal_Event class nu constructor call thase tyare event_data ma new_event_data no je aaray che e set thase. 
        $new_booking_event->create( $status );
        
        return $new_booking_event;
    }
    
    /**
     * Creating the instance fo the BKAP_Gcal_Event
     * 
     * @param int $id
     * @return object BKAP_Booking
     * @since 4.2
     */
    static function get_bkap_booking( $id ) {
        return new BKAP_Gcal_Event( $id );
    }
    
    public function bkap_admin_booking_calendar_events() {
        
        global $wpdb;
        $user_id = $_POST[ 'user_id' ];
        
        $total_orders_to_export = bkap_common::bkap_get_total_bookings_to_export( $user_id );
        
        $gcal = new BKAP_Gcal();
        $current_time = current_time( 'timestamp' );
        
        $user = new WP_User( $user_id );
        if( 'tour_operator' == $user->roles[ 0 ] ) {
            $event_item_ids = get_the_author_meta( 'tours_event_item_ids', $user_id );
        } else {
            $event_item_ids = get_option( 'bkap_event_item_ids' );
        }
        
        if( $event_item_ids == '' || $event_item_ids == '{}' || $event_item_ids == '[]' || $event_item_ids == 'null' ) {
            $event_item_ids = array();
        }
         
        if ( isset( $total_orders_to_export ) && count( $total_orders_to_export ) > 0 ) {
            foreach( $total_orders_to_export as $order_id => $value ) {
                $data = get_post_meta( $order_id );
                foreach ( $value as $item_id ) {
                    
                    if ( !in_array( $item_id, $event_item_ids ) ) {
                        $event_details = array();
                        
                        $order = new WC_Order( $order_id );
                        $get_items        = $order->get_items();
                        
			foreach( $get_items as $get_items_key => $get_items_value ) {
                            
                            if( $get_items_key  == $item_id ){
                                
                                $item_data = $get_items_value->get_data(); // Getting Item data.
                                $item_name = $item_data['name'];
                            
                            
                                $item_booking_date = wc_get_order_item_meta( $item_id, '_wapbk_booking_date' );
                                $item_checkout_date = wc_get_order_item_meta( $item_id, '_wapbk_checkout_date' );
                                $item_booking_time = wc_get_order_item_meta( $item_id, '_wapbk_time_slot' );
                                
                                $product_id = wc_get_order_item_meta( $item_id, '_product_id' );
                                $quantity = wc_get_order_item_meta( $item_id, '_qty' );
                                
                                $booking_status = wc_get_order_item_meta( $item_id, '_wapbk_booking_status' );
                                
                                if ( ( isset( $booking_status ) && 'pending-confirmation' != $booking_status ) || ( ! isset( $booking_status )) ) {
                                
                                    if ( isset( $item_booking_date ) && $item_booking_date != '1970-01-01' ) {
                                        $event_details[ 'hidden_booking_date' ] = $item_booking_date;
                                    }
                                
                                
                                    if ( isset( $item_checkout_date ) && $item_checkout_date != '' ) {
                                        $event_details[ 'hidden_checkout_date' ] = $item_checkout_date;
                                    }
                                    
                                    if ( isset( $item_booking_time ) && $item_booking_time != '' ) {
                                        $event_details[ 'time_slot' ] = $item_booking_time;
                                    }
                                
                                    $event_details[ 'billing_email' ] = $data[ '_billing_email' ][ 0 ];
                                    $event_details[ 'billing_address_1' ] = $data[ '_billing_address_1' ][ 0 ];
                                    $event_details[ 'billing_address_2' ] = $data[ '_billing_address_2' ][ 0 ];
                                    $event_details[ 'billing_city' ] = $data[ '_billing_city' ][ 0 ];
                                    $event_details[ 'order_id' ] = $order_id;
                                    
                                    $event_details[ 'shipping_first_name' ] = $data[ '_shipping_first_name' ][ 0 ];
                                    $event_details[ 'shipping_last_name' ] = $data[ '_shipping_last_name' ][ 0 ];
                                    $event_details[ 'shipping_address_1' ] = $data[ '_shipping_address_1' ][ 0 ];
                                    $event_details[ 'shipping_address_2' ] = $data[ '_shipping_address_2' ][ 0 ];
                                    $event_details[ 'billing_phone' ] = $data[ '_billing_phone' ][ 0 ];
                                    $event_details[ 'order_comments' ]  = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->customer_note : $order->get_customer_note();
                                    
                                    $_product = wc_get_product( $product_id );
                                     
                                    $post_title = $_product->get_title();
                                    $event_details[ 'product_name' ] = $item_name;
                                    $event_details[ 'product_qty' ] = $quantity;
                                    $event_details[ 'product_total' ] = wc_get_order_item_meta( $item_id, '_line_total' );
                                    
                                    $post_id = $product_id;
                                    
                                    if ( ( ! isset( $booking_settings[ 'product_sync_integration_mode' ] ) ) || ( isset( $booking_settings[ 'product_sync_integration_mode' ] ) && 'disabled' == $booking_settings[ 'product_sync_integration_mode' ] ) ) {
                                        $post_id = 0;
                                    }
                                    
                                    $gcal->insert_event( $event_details, $item_id, $user_id, $post_id, false );
                                    
                                    // add an order note, mentioning an event has been created for the item
                                    $order_note = __( "Booking_details for $post_title have been exported to the Google Calendar", 'woocommerce-booking' );
                                    $order->add_order_note( $order_note );
                                }
                         }
                      } 
                    }
                }
            }
        }
        die();
    }
}// end of class
$bkap_calendar_sync = new bkap_calendar_sync();