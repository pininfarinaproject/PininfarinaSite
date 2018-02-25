<?php 

class import_bookings {
    
    public static function bkap_woocommerce_import_page() {
        
        global $wpdb;
        
        $plugin_path = plugin_dir_path( __FILE__ );
        include_once( $plugin_path . '/includes/class-import-bookings-table.php' );
        $import_bookings_table = new WAPBK_Import_Bookings_Table();
        $import_bookings_table->bkap_prepare_items();
        
        ?>
        <div class="wrap">
            <h2><?php _e( 'Imported Bookings', 'woocommerce-booking' ); ?></h2>
    		
    		<?php do_action( 'bkap_import_bookings_page_top' ); ?>
                    		
    		<form id="bkap-import-bookings" method="get" action="<?php echo admin_url( 'admin.php?page=woocommerce_import_page' ); ?>">
                <div id="display_notice"></div>
                <p id="bkap_add_order">
        			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bkap_create_booking_page' ) ); ?>" class="button-secondary"><?php _e( 'Create Booking', 'woocommerce-booking' ); ?></a>
                </p>
    
    			<input type="hidden" name="page" value="woocommerce_import_page" />
    			
    			<?php $import_bookings_table->views() ?>
    					
    			<?php $import_bookings_table->advanced_filters(); ?>
    			<?php $import_bookings_table->display() ?>
    				
    		</form>
			<?php do_action( 'bkap_import_bookings_page_bottom' ); ?>
		</div>
		<?php             	
    }
    
//     public function bkap_discard_imported_event() {
//         $option_name = $_POST[ 'ID' ];
        
//         delete_option( $option_name );
       
//         die();
//     }
    

    public static function bkap_map_imported_event() {
    
        // default notices to blanks
        $notice                 = '';
        $message                = '';
        global $bkap_date_formats;
	
        $google_post_id         = $_POST[ 'ID' ]; 
        $option_name            = get_post_meta( $google_post_id, '_bkap_event_option_name', true ); 
        $imported_event_details = json_decode( get_option( $option_name ) );
        
        $product_id             = $_POST[ 'product_id' ];
        
        if( $_POST[ 'type' ] == "by_post" ) {
            
            if ( get_post_type( $google_post_id ) === 'bkap_gcal_event' ) {
                $booking = new BKAP_Gcal_Event( $google_post_id );
            }
            
            $booking_details[ 'product_id' ]    = $product_id;
            $booking_details[ 'start' ]         = $booking->start;
            $booking_details[ 'end' ]           = $booking->end;
            $booking_details[ 'summary' ]       = $booking->summary;
            $booking_details[ 'description' ]   = $booking->description;
            $booking_details[ 'uid' ]           = $booking->uid;
        }
        
        $status = self::bkap_create_order( $booking_details, true );
       
        $backdated_event    = $status[ 'backdated_event' ];
        $validation_check   = $status[ 'validation_check' ];
        $grouped_product    = $status[ 'grouped_product' ];
        
        if ( 0 == $backdated_event && 0 == $validation_check && 0 == $grouped_product ) {

            // finally move the imported event details to item meta and delete the record from wp_options table
            $archive_events = 0; // 0 - archive (move from wp_options to item meta), 1 - delete from wp_options and don't save as item meta
            
                        
            if ( 0 == $archive_events ) {
                
                // save as item meta for future reference
                wc_add_order_item_meta( $status[ 'item_id' ], '_gcal_event_reference', $imported_event_details );
                // delete the data frm wp_options
                delete_option( $option_name );
                
                // Update post and its post meta.
                
                $update_parent_post_id = array(
                    'ID'            => $google_post_id,
                    'post_parent'   => $status[ 'order_id' ],
                );
                
                // Update the parent ID of post into the database
                wp_update_post( $update_parent_post_id );
                
                update_post_meta( $google_post_id, '_bkap_product_id', $status['parent_id'] );
                update_post_meta( $google_post_id, '_bkap_variation_id', $status['variation_id'] );
                
                $booking->update_status( "bkap-mapped" );
                
                
            } else if ( 1 == $archive_events ) {
                
                // delete the data from wp_options
                delete_option( $option_name );
            } 
        }
        
        if ( 1 == $backdated_event ) {
            $message = __( 'Back Dated Events cannot be imported. Please discard them.', 'woocommerce-booking' );
            $notice = '<div class="error"><p>' . sprintf( __( '%s', 'woocommerce-booking' ), $message ) . '</p></div>';
        }
        
        if ( 1 == $validation_check ) {
            
            $message = __( 'The product is not available for the given date for the desired quantity.', 'woocommerce-booking' );

            $notice = '<div class="error"><p>' . sprintf( __( '%s', 'woocommerce-booking' ), $message ) . '</p></div>';
        }
        
        if ( 1 == $grouped_product ) {
            $message = __( 'Imported Events cannot be mapped to grouped products.', 'woocommerce-booking' );
            $notice = '<div class="error"><p>' . sprintf( __( '%s', 'woocommerce-booking' ), $message ) . '</p></div>';
        }
        
        update_post_meta( $google_post_id, '_bkap_reason_of_fail', $message );
        
        echo $notice;
        
        if ( ! isset( $_POST[ 'automated' ] ) || ( isset( $_POST[ 'automated' ] ) && 0 == $_POST[ 'automated' ] ) ) {
            die();
        }

    }
    
    static function bkap_create_order( $booking_details, $gcal = false ) {

        global $bkap_date_formats;
        
        $global_settings        = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        
        $date_format_to_display = $global_settings->booking_date_format;
        $time_format_to_display = $global_settings->booking_time_format;
        
        $order_created          = false;
        $product_id             = $booking_details[ 'product_id' ];
        
        // default the variables
        $booking_date_to_display    = '';
        $checkout_date_to_display   = '';
        $booking_from_time          = '';
        $booking_to_time            = '';        
        
        if ( !current_time( 'timestamp' ) ) {
            $tdif = 0;
        } else {
            $tdif = current_time( 'timestamp' ) - time();
        }
        
        if( $booking_details[ 'end' ] != "" && $booking_details[ 'start' ] != "" ) {
            
            // admin bookings passes time as per WP timezone
            $event_start                = $booking_details[ 'start' ];
            $event_end                  = $booking_details[ 'end' ];
            
            $booking_date_to_display    = date( $bkap_date_formats[ $date_format_to_display ], $event_start );
            $checkout_date_to_display   = date( $bkap_date_formats[ $date_format_to_display ], $event_end );
            
            $event_start_time           = $event_start;
            $event_end_time             = $event_end;
            
            if ( $gcal ) { // GCAL passes UTC
                $event_start_time = $event_start + $tdif;
                $event_end_time   = $event_end + $tdif;
            }
             
            if ( $time_format_to_display == '12' ) {
                $booking_from_time  = date( "h:i A", $event_start_time );
                $booking_to_time    = ( date( "h:i A", $event_end_time ) === '12:00 AM' ) ? '' : date( "h:i A", $event_end_time ); // open ended slot compatibility
            } else {
                $booking_from_time  = date( "H:i", $event_start_time );
                $booking_to_time    = ( date( "H:i", $event_end_time ) === '00:00' ) ? '' : date( "H:i", $event_end_time ); // open ended slot compatibility
            }
        } else if( $booking_details[ 'start' ] != "" && $booking_details[ 'end' ] == "" ) {
        
            $event_start                = $booking_details[ 'start' ];
            
            $booking_date_to_display    = date( $bkap_date_formats[ $date_format_to_display ], $event_start );
            
            $event_start_time           = $event_start;
            
            if ( $gcal ) {
                $event_start_time = $event_start + $tdif;
            }

            if ( $time_format_to_display == '12' ) {
                $booking_from_time = date( "h:i A", $event_start_time );
            } else {
                $booking_from_time = date( "H:i", $event_start_time );
            }
        }

        $resource_id = 0;
        if( $booking_details['bkap_resource_id'] && $booking_details['bkap_resource_id'] != "" ){
            $resource_id = $booking_details['bkap_resource_id'];
        }
        
        $sanity_check[ 'start' ]        = $event_start;
        $sanity_check[ 'end' ]          = ( isset( $event_end ) ) ? $event_end : '';
        $sanity_check[ 'product_id' ]   = $product_id;
        
        $sanity_return                  = self::bkap_sanity_check( $sanity_check, $gcal );
        
        $backdated_event                = ( isset( $sanity_return[ 'backdated_event' ] ) ) ? $sanity_return[ 'backdated_event' ] : 0;
        $validation_check               = ( isset( $sanity_return[ 'validation_check' ] ) ) ? $sanity_return[ 'validation_check' ] : 0;
        $grouped_product                = ( isset( $sanity_check[ 'grouped_event' ] ) ) ? $sanity_check[ 'grouped_event' ] : 0;
        
        $quantity_return                = self::bkap_quantity_setup( $sanity_check, $sanity_return );
        
        
        $quantity                       = $quantity_return[ 'quantity' ];
        $lockout_quantity               = $quantity_return[ 'lockout_qty' ];
        $parent_id                      = $quantity_return[ 'parent_id' ];
        $variation_id                   = $quantity_return[ 'variation_id' ];
        $variationsArray                = $quantity_return[ 'variationsArray' ];
        
        /* Validate the booking details. Check if the product is available in the desired quantity for the given date and time */
        $_product           = wc_get_product( $product_id );
                
        $hidden_date        = date( 'Y-m-d', $event_start );

        $booking_settings   = get_post_meta( $parent_id, 'woocommerce_booking_settings', true );
        
        if( 'variation' == $_product->get_type() ) {
            $product_type = 'variable';
        } else {
            $product_type = $_product->get_type();
        }
        
        $totals = 0;

        if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && 'on' == $booking_settings[ 'booking_enable_multiple_day' ] ) {
            $booking_date           = new DateTime( $hidden_date );
            $hidden_checkout_date   = date( 'Y-m-d', $event_end );
            $checkout_date          = new DateTime( $hidden_checkout_date );
        
            $difference = $checkout_date->diff( $booking_date );
        
            if ( $gcal ) {
                $price  =  bkap_common::bkap_get_price( $parent_id, $variation_id, $product_type );
                $totals = $price * $difference->days;
            } else {
                $totals = $booking_details[ 'price' ];
            }
            $variationsArray[ 'totals' ] = array(
                'subtotal'     => $totals,
                'total'        => $totals,
                'subtotal_tax' => 0,
                'tax'          => 0
            );
        } else if ( isset( $booking_settings[ 'booking_enable_time' ] ) && 'on' == $booking_settings[ 'booking_enable_time' ] ) {
        
            $from_hrs   = date( 'G:i', strtotime( $booking_from_time ) );
            $to_hrs     = date( 'G:i', strtotime( $booking_to_time ) );
        
            if ( 1 != $validation_check ) {
        
                $timeslot = "$from_hrs - $to_hrs";
                 
                $price = ( $gcal ) ? bkap_timeslot_price::get_price( $parent_id, $variation_id, $product_type, $hidden_date, $timeslot, 'product' ) : $booking_details[ 'price' ];
        
                $variationsArray[ 'totals' ] = array(
                    'subtotal'     => $price,
                    'total'        => $price,
                    'subtotal_tax' => 0,
                    'tax'          => 0
                );
            }

            if ( $gcal ) {
                $totals = $price;
            }

        } else {
            // Single Day
            if ( $gcal ) {
                $totals = bkap_common::bkap_get_price( $product_id, $variation_id, $product_type, $hidden_date );
            }
        }

        
        if ( 0 == $backdated_event && 0 == $validation_check && 0 == $grouped_product ) {
            $order_created = true;
            // create an order
            
            if ( $gcal ) {
                
            $args = array( 'status' => 'pending',
                'customer_note' => $booking_details[ 'summary' ],
                'created_via' => 'GCal' );
            
            } else {
                $args = array( 'status' => 'pending',
                    'created_via' => 'manual_booking',
                    'customer_id' => $booking_details[ 'customer_id' ]
                );
                
                // Create the billing address array to be added to the order
                if ( $booking_details[ 'customer_id' ] > 0 ) {
                    $user_meta = array_map( function( $a ){ return $a[0]; }, get_user_meta( $booking_details[ 'customer_id' ] ) );
                
                    $addr_args = array(
                        'first_name'    => ( isset( $user_meta[ 'billing_first_name' ] ) ) ? $user_meta[ 'billing_first_name' ] : '',
                        'last_name'     => ( isset( $user_meta[ 'billing_last_name' ] ) ) ? $user_meta[ 'billing_last_name' ] : '',
                        'email'         => ( isset( $user_meta[ 'billing_email' ] ) ) ? $user_meta[ 'billing_email' ] : '',
                        'phone'         => ( isset( $user_meta[ 'billing_phone' ] ) ) ? $user_meta[ 'billing_phone' ] : '',
                        'address_1'     => ( isset( $user_meta[ 'billing_address_1' ] ) ) ? $user_meta[ 'billing_address_1' ] : '',
                        'address_2'     => ( isset( $user_meta[ 'billing_address_2' ] ) ) ? $user_meta[ 'billing_address_2' ] : '',
                        'city'          => ( isset( $user_meta[ 'billing_city' ] ) ) ? $user_meta[ 'billing_city' ] : '',
                        'state'         => ( isset( $user_meta[ 'billing_state' ] ) ) ? $user_meta[ 'billing_state' ] : '',
                        'postcode'      => ( isset( $user_meta[ 'billing_postcode' ] ) ) ? $user_meta[ 'billing_postcode' ] : '',
                        'country'       => ( isset( $user_meta[ 'billing_country' ] ) ) ? $user_meta[ 'billing_country' ] : '',
                    );
                } else {
                    $addr_args = array(
                        'first_name' => 'Guest',
                    );
                }
                
            }
            
            $order = wc_create_order( $args );
            
            $order_id = $order->get_id();
            
            if ( $gcal ) {
                if ( isset( $booking_details[ 'summary' ] ) && $booking_details[ 'summary' ] != '' ) {
                    $order->add_order_note( $booking_details[ 'summary' ] );
                }
                if ( isset( $booking_details[ 'description' ] ) && $booking_details[ 'description' ] != '' ) {
                    $order->add_order_note( $booking_details[ 'description' ] );
                }
                $order->add_order_note( 'Reserved by GCal' );
            } else {
                $order->add_order_note( 'Manual Booking' );
                // Add the Billing Address
                $order->set_address( $addr_args, 'billing' );
            }
            // add the product to the order
            
            $item_id = $order->add_product( $_product, $quantity, $variationsArray );
            
            if ( $gcal ) {
                // insert records to ensure we're aware the item has been imported
                $event_items = get_option( 'bkap_event_item_ids' );
                if( $event_items == '' || $event_items == '{}' || $event_items == '[]' || $event_items == 'null' ) {
                    $event_items = array();
                }
                array_push( $event_items, $item_id );
                update_option( 'bkap_event_item_ids', $event_items );
            }
            
            // calculate order totals
            $order->calculate_totals();

            if ( isset( $parent_id ) && 0 != $parent_id ) {
                $meta_update_id = $parent_id;
            } else {
                $meta_update_id = $product_id;
            }
            
            // create the booking details array
            $booking[ 'date' ]          = $booking_date_to_display;
            $booking[ 'hidden_date' ]   = date( 'j-n-Y', $event_start );
            $booking[ 'price' ]         = ( ! $gcal ) ? $booking_details[ 'price' ] : $totals; // price is to be passed only if it's an admin booking
            $booking[ 'uid' ]           = isset( $booking_details['uid'] ) ? $booking_details['uid'] : "";
            $booking[ 'resource_id']    = $resource_id;
            
            $hidden_checkout_date       = '';
            
            if ( isset( $checkout_date_to_display ) && '' != $checkout_date_to_display ) {
                $hidden_checkout_date = date( 'j-n-Y', $event_end );
            }
            
            $booking_method = get_post_meta( $meta_update_id, '_bkap_booking_type', true );
            
            if ( 'multiple_days' === $booking_method ) {
                $booking[ 'date_checkout' ] = $checkout_date_to_display;
                $booking[ 'hidden_date_checkout' ] = $hidden_checkout_date;
            }
            if ( isset( $booking_from_time ) && '' != $booking_from_time && $booking_from_time != $booking_to_time ) {
                $time_slot = $booking_from_time;
                if ( isset( $booking_to_time ) && '' != $booking_to_time ) {
                    $time_slot .= ' - ' . $booking_to_time;
                }
                // check the booking method
                if ( 'date_time' === $booking_method ) {
                    $booking[ 'time_slot' ] = $time_slot;
                }
            }
            
            if ( isset( $parent_id ) && 0 != $parent_id ) {
                $meta_update_id = $parent_id;
            } else {
                $meta_update_id = $product_id;
            }
            bkap_common::bkap_update_order_item_meta( $item_id, $meta_update_id, $booking, true );
            
            // adjust lockout
            $product = wc_get_product( $meta_update_id );
            // for grouped products
            $parent_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $product->get_parent() : bkap_common::bkap_get_parent_id( $meta_update_id );
            
            $details = bkap_checkout::bkap_update_lockout( $order_id, $meta_update_id, $parent_id, $lockout_quantity, $booking );
            
            // update the global time slot lockout
            if ( isset( $booking[ 'time_slot' ] ) && '' != $booking[ 'time_slot' ] ) {
                bkap_checkout::bkap_update_global_lockout( $meta_update_id, $lockout_quantity, $details, $booking );
            }
            
            // Add the booking as a post
            $booking[ 'gcal_uid' ] = ( $gcal ) ? $booking_details[ 'uid' ] : false;
            bkap_checkout::bkap_create_booking_post( $item_id, $meta_update_id, $lockout_quantity, $booking, $variation_id );
            
            // update the order status to processing
            $order_obj = wc_get_order( $order_id ); // this needs to be done to ensure the booking details are displayed in Woo emails.
            $order_obj->update_status( 'processing' );

            if ( !$gcal ) {

                $g_cal = new BKAP_Gcal();                

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

                if( $g_cal->get_api_mode( $admin_id, $meta_update_id ) == "directly" ) {
                    

                    $booking_settings = get_post_meta( $meta_update_id, 'woocommerce_booking_settings', true );

                    // check the booking status, if pending confirmation, then do not insert event in the calendar
                    $booking_status = wc_get_order_item_meta( $item_id, '_wapbk_booking_status' );

                    if ( ( isset( $booking_status ) && 'pending-confirmation' != $booking_status ) || ( ! isset( $booking_status ) ) ) {
                        // ensure it's a future dated event
                        $is_date_set = false;

                        if ( isset( $booking[ 'hidden_date' ] ) ) {
                            $day = date( 'Y-m-d', current_time( 'timestamp' ) );
                            
                            if ( strtotime( $booking[ 'hidden_date' ] ) >= strtotime( $day ) ) {
                                $is_date_set = true;
                            } 
                        }


                        if( $is_date_set ) {

                            $event_details = array();

                            $event_details[ 'hidden_booking_date' ] = $booking[ 'hidden_date' ];

                            if ( isset( $booking[ 'hidden_date_checkout' ] ) && $booking[ 'hidden_date_checkout' ] != '' ) {
                                
                                if ( isset( $booking_settings[ 'booking_charge_per_day' ] ) && 'on' == $booking_settings[ 'booking_charge_per_day' ] ) {
                                    $event_details[ 'hidden_checkout_date' ] = date( 'j-n-Y', strtotime( '+1 day', strtotime( $booking[ 'hidden_date_checkout' ] ) ) );
                                } else {
                                    $event_details[ 'hidden_checkout_date' ] = $booking[ 'hidden_date_checkout' ];
                                }
                            }
                            
                            if ( isset( $booking[ 'time_slot' ] ) && $booking[ 'time_slot' ] != '' ) {
                                $event_details[ 'time_slot' ] = $booking[ 'time_slot' ];
                            }
                            
                            $event_details[ 'billing_email' ]       = ( isset( $addr_args['email'] ) ) ? $addr_args['email'] : '';                        
                            $event_details[ 'billing_first_name' ]  = ( isset( $addr_args['first_name'] ) ) ? $addr_args['first_name'] : '';
                            $event_details[ 'billing_last_name' ]   = ( isset( $addr_args['last_name'] ) ) ? $addr_args['last_name'] : '';                    
                            $event_details[ 'billing_address_1' ]   = ( isset( $addr_args['address_1'] ) ) ? $addr_args['address_1'] : '';                    
                            $event_details[ 'billing_address_2' ]   = ( isset( $addr_args['address_2'] ) ) ? $addr_args['address_2'] : '';                    
                            $event_details[ 'billing_city' ]        = ( isset( $addr_args['city'] ) ) ? $addr_args['city'] : '';                   
                            $event_details[ 'billing_phone' ]       = ( isset( $addr_args['phone'] ) ) ? $addr_args['phone'] : '';  
                            $event_details[ 'order_id' ]            = $order_id;
                            $event_details[ 'order_comments' ]      = '';
                            $event_details[ 'product_name' ]        = $_product->get_title();;
                            $event_details[ 'product_qty' ]         = 1;                        
                            $event_details[ 'product_total' ]       = $totals;
                            
                            if ( ( ! isset( $booking_settings[ 'product_sync_integration_mode' ] ) ) || ( isset( $booking_settings[ 'product_sync_integration_mode' ] ) && 'disabled' == $booking_settings[ 'product_sync_integration_mode' ] ) ) {
                                $meta_update_id = 0;
                            }

                            $g_cal->insert_event( $event_details, $item_id, $booking_details[ 'customer_id' ], $meta_update_id , false );
                        }
                    }
                }                
            }   
        }
        
        $status[ 'backdated_event' ]    = $backdated_event; 
        $status[ 'validation_check' ]   = $validation_check; 
        $status[ 'grouped_product' ]    = $grouped_product; 
        $status[ 'new_order' ]          = $order_created;
        $status[ 'order_id' ]           = ( $order_created ) ? $order_id : 0;
        $status[ 'item_id' ]            = ( $order_created ) ? $item_id : 0;
        $status[ 'parent_id' ]          = $parent_id;
        $status[ 'variation_id' ]       = $variation_id;
        
        
        return $status;
    }
    
    static function bkap_create_booking( $booking_details, $gcal = false ) {
        
        global $bkap_date_formats;
        
        $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        
        $date_format_to_display = $global_settings->booking_date_format;
        $time_format_to_display = $global_settings->booking_time_format;
        
        $item_added = false;
        $product_id = $booking_details[ 'product_id' ];
        
        // default the variables
        $booking_date_to_display = '';
        $checkout_date_to_display = '';
        $booking_from_time = '';
        $booking_to_time = '';
        
        if ( !current_time( 'timestamp' ) ) {
            $tdif = 0;
        } else {
            $tdif = current_time( 'timestamp' ) - time();
        }
        
        if( $booking_details[ 'end' ] != "" && $booking_details[ 'start' ] != "" ) {
        
            if ( $gcal ) { // GCAL passes UTC
                $event_start = $booking_details[ 'start ' ]+ $tdif;
                $event_end = $booking_details[ 'end' ] + $tdif;
            } else { // admin bookings passes time as per WP timezone
                $event_start = $booking_details[ 'start' ];
                $event_end = $booking_details[ 'end' ];
            }
        
            $booking_date_to_display = date( $bkap_date_formats[ $date_format_to_display ], $event_start );
            $checkout_date_to_display = date( $bkap_date_formats[ $date_format_to_display ], $event_end );
            
            if ( $time_format_to_display == '12' ) {
                $booking_from_time  = date( "h:i A", $event_start );
                $booking_to_time    = ( date( "h:i A", $event_end ) === '12:00 AM' ) ? '' : date( "h:i A", $event_end ); // open ended slot compatibility
            } else {
                $booking_from_time  = date( "H:i", $event_start );
                $booking_to_time    = ( date( "H:i", $event_end ) === '00:00' ) ? '' : date( "H:i", $event_end ); // open ended slot compatibility
            }
             
        } else if( $booking_details[ 'start' ] != "" && $booking_details[ 'end' ] == "" ) {
        
            $event_start = $booking_details[ 'start' ] + $tdif;
            $booking_date_to_display = date( $bkap_date_formats[ $date_format_to_display ], $event_start );
            
            if ( $time_format_to_display == '12' ) {
                $booking_from_time = date( "h:i A", $event_start );
            } else {
                $booking_from_time = date( "H:i", $event_start );
            }
             
        }
        
        $sanity_check[ 'start' ] = $event_start;
        $sanity_check[ 'end' ] = ( isset( $event_end ) ) ? $event_end : '';
        $sanity_check[ 'product_id' ] = $product_id;
        
        $sanity_return = self::bkap_sanity_check( $sanity_check, $gcal );
        
        $backdated_event    = ( isset( $sanity_return[ 'backdated_event' ] ) ) ? $sanity_return[ 'backdated_event' ] : 0;
        $validation_check   = ( isset( $sanity_return[ 'validation_check' ] ) ) ? $sanity_return[ 'validation_check' ] : 0;
        $grouped_product    = ( isset( $sanity_check[ 'grouped_event' ] ) ) ? $sanity_check[ 'grouped_event' ] : 0;
        
        $quantity_return = self::bkap_quantity_setup( $sanity_check, $sanity_return );
        
        $quantity = $quantity_return[ 'quantity' ];
        $lockout_quantity = $quantity_return[ 'lockout_qty' ];
        $parent_id = $quantity_return[ 'parent_id' ];
        $variation_id = $quantity_return[ 'variation_id' ];
        $variationsArray = $quantity_return[ 'variationsArray' ];
        
        if ( 0 == $backdated_event && 0 == $validation_check && 0 == $grouped_product ) {
            $order = new WC_Order( $booking_details[ 'order_id' ] );
            $item_added = true;
            
            $order_id = $booking_details[ 'order_id' ];
            $_product = wc_get_product( $product_id );
            $product_title = $_product->get_name();

            // set the price
            $totals = $booking_details[ 'price' ];
            $variationsArray[ 'totals' ] = array(
                'subtotal'     => $totals,
                'total'        => $totals,
                'subtotal_tax' => 0,
                'tax'          => 0
            );
            
            $order->add_order_note( "Added $product_title manually." );
        
            // add the product to the order
            $item_id = $order->add_product( $_product, $quantity, $variationsArray );
            
            // calculate order totals
            $order->calculate_totals();
            
            if ( isset( $parent_id ) && 0 != $parent_id ) {
                $meta_update_id = $parent_id;
            } else {
                $meta_update_id = $product_id;
            }
            
            // create the booking details array
            $booking[ 'date' ] = $booking_date_to_display;
            $booking[ 'hidden_date' ] = date( 'j-n-Y', $event_start );
            $booking[ 'price' ] = $booking_details[ 'price' ]; 
            $hidden_checkout_date = '';
            
            if ( isset( $checkout_date_to_display ) && '' != $checkout_date_to_display ) {
                $hidden_checkout_date = date( 'j-n-Y', $event_end );
            }
            
            $booking_method = get_post_meta( $meta_update_id, '_bkap_booking_type', true );
            
            if ( 'multiple_days' === $booking_method ) {
                $booking[ 'date_checkout' ] = $checkout_date_to_display;
                $booking[ 'hidden_date_checkout' ] = $hidden_checkout_date;
            }
            
            if ( isset( $booking_from_time ) && '' != $booking_from_time && $booking_from_time != $booking_to_time ) {
                $time_slot = $booking_from_time;
                if ( isset( $booking_to_time ) && '' != $booking_to_time ) {
                    $time_slot .= ' - ' . $booking_to_time;
                }
                if( 'date_time' === $booking_method ) {
                    $booking[ 'time_slot' ] = $time_slot;
                }
            }

            if( $booking_details['bkap_resource_id'] && $booking_details['bkap_resource_id'] != "" ){
                
                $booking[ 'resource_id']    = $booking_details['bkap_resource_id'];
            }
            
            bkap_common::bkap_update_order_item_meta( $item_id, $meta_update_id, $booking, true );
            
            // adjust lockout
            $product = wc_get_product( $meta_update_id );
            // for grouped products
            $parent_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $product->get_parent() : bkap_common::bkap_get_parent_id( $meta_update_id );
            
            $details = bkap_checkout::bkap_update_lockout( $order_id, $meta_update_id, $parent_id, $lockout_quantity, $booking );
            
            // update the global time slot lockout
            if ( isset( $booking[ 'time_slot' ] ) && '' != $booking[ 'time_slot' ] ) {
                bkap_checkout::bkap_update_global_lockout( $meta_update_id, $lockout_quantity, $details, $booking );
            }
            
            // Add the booking as a post
            $booking[ 'gcal_uid' ] = false;
            bkap_checkout::bkap_create_booking_post( $item_id, $meta_update_id, $lockout_quantity, $booking, $variation_id );
            
        }
        
        $status[ 'backdated_event' ] = $backdated_event;
        $status[ 'validation_check' ] = $validation_check;
        $status[ 'grouped_product' ] = $grouped_product;
        $status[ 'item_added' ] = $item_added;
        $status[ 'order_id' ] = $booking_details[ 'order_id' ];
        return $status;
    }
    
    static function bkap_sanity_check( $booking_data, $gcal ) {
        
        global $bkap_date_formats;
        
        $event_start    = $booking_data[ 'start' ];
        $event_end      = $booking_data[ 'end' ];
        $product_id     = $booking_data[ 'product_id' ];
        
        // default  variables
        $backdated_event    = 0; // it's a future event
        $validation_check   = 0; // product is available for desired quantity
        $grouped_product    = 0; // for grouped product
        
        $global_settings    = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        
        $date_format_to_display = $global_settings->booking_date_format;
        $time_format_to_display = $global_settings->booking_time_format;
        
        if ( !current_time( 'timestamp' ) ) {
            $tdif = 0;
        } else {
            $tdif = current_time( 'timestamp' ) - time();
        }
        
        if( ! $gcal ) {
            $tdif = 0;
        }
        
        // lets compare with only date start to ensure current dated bookings can go through
        $current_time = current_time( 'timestamp' );
        $date_start = strtotime( date( 'Ymd', $current_time ) );
        
        if ( $event_start !== '' && $event_end !== '' ) {
            if( $event_end >= $date_start || $event_start >= $date_start ) {
            
                if ( $time_format_to_display == '12' ) {
                    $booking_from_time = date( "h:i A", $event_start + $tdif );
                    $booking_to_time = date( "h:i A", $event_end + $tdif );
                } else {
                    $booking_from_time = date( "H:i", $event_start + $tdif);
                    $booking_to_time = date( "H:i", $event_end + $tdif );
                }
            
                $booking_date_to_display    = date( $bkap_date_formats[ $date_format_to_display ], $event_start );
                $checkout_date_to_display   = date( $bkap_date_formats[ $date_format_to_display ], $event_end );
                
            } else {
                $backdated_event = 1;
            }
        } else {
            if( $event_start >= $date_start ) {
            
                if ( $time_format_to_display == '12' ) {
                    $booking_from_time = date( "h:i A", $event_start + $tdif );
                } else {
                    $booking_from_time = date( "H:i", $event_start + $tdif );
                }
            
                $booking_date_to_display = date( $bkap_date_formats[ $date_format_to_display ], $event_start );
            } else {
                $backdated_event = 1;
            }
            
        }
        
        /* Validate the booking details. Check if the product is available in the desired quantity for the given date and time */
        $_product = wc_get_product( $product_id );
        if ( 'grouped' == $_product->get_type() ) {
            $grouped_product = 1;
        }
        
        $variationsArray = array();
        
        // if the product ID has a parent post Id, then it means it's a variable product
        $variation_id       = 0;
        $lockout_quantity   = 1;
        
        if( $_product->get_type() == "variation" ){
            
            $check_variation = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $_product->variation_id : $_product->get_id();
        }
        
        if ( isset( $check_variation ) && 0 != $check_variation ) {
            
            $parent_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $_product->parent->id : $_product->get_parent_id();
            $variation_id = $product_id;
        
            $parent_product = wc_get_product( $parent_id );
            $variations_list = $parent_product->get_available_variations();
        
            foreach ( $variations_list as $variation ) {
                if ( $variation[ 'variation_id' ] == $product_id ) {
                    $variationsArray[ 'variation' ] = $variation[ 'attributes' ];
                }
            }
        
            // Product Attributes - Booking Settings
            $attribute_booking_data = get_post_meta( $parent_id, '_bkap_attribute_settings', true );
        
            if ( is_array( $attribute_booking_data ) && count( $attribute_booking_data ) > 0 ) {
                $lockout_quantity = 1;
                foreach ( $attribute_booking_data as $attr_name => $attr_settings ) {
                    $attr_name = 'attribute_' . $attr_name;
                    if ( isset( $attr_settings[ 'booking_lockout_as_value' ] ) && 'on' == $attr_settings[ 'booking_lockout_as_value' ] ) {
                        if ( array_key_exists( $attr_name, $variationsArray[ 'variation' ] ) ) {
                            $lockout_quantity += $variationsArray[ 'variation' ][ $attr_name ];
                        }
                    }
                }
            }
        } else {
            $parent_id = $product_id;
        }
        
        $hidden_date        = date( 'Y-m-d', $event_start );
        
        $booking_settings   = get_post_meta( $parent_id, 'woocommerce_booking_settings', true );

        // if the event is not backdated
        if ( $backdated_event != 1 ) {
            // if time is enabled for the product
            if ( isset( $booking_settings[ 'booking_enable_time' ] ) && 'on' == $booking_settings[ 'booking_enable_time' ] ) {
            
                $from_hrs   = date( 'G:i', strtotime( $booking_from_time ) );
                $to_hrs     = date( 'G:i', strtotime( $booking_to_time ) );
                
                if( $to_hrs == '0:00' ){
                    $to_hrs = "";
                }
            
                $availability = bkap_booking_process::bkap_get_time_availability( $parent_id, $hidden_date, $from_hrs, $to_hrs, 'YES' );

                if( $availability == 0 ) {

                    $from_hrss   = date( 'H:i', strtotime( $booking_from_time ) );
                    $to_hrss     = date( 'H:i', strtotime( $booking_to_time ) );

                    if( $to_hrss == '00:00' ){
                        $to_hrss = "";
                    }
                    
                    $availability = bkap_booking_process::bkap_get_time_availability( $parent_id, $hidden_date, $from_hrss, $to_hrss, 'YES' );
                }

                $unlimited_lang = trim ( __( 'Unlimited ', 'woocommerce-booking' ) );
                 
                if ( $availability > 0 || trim( $availability ) == $unlimited_lang ) {
                    if ( $availability > 0 ) {
                        $new_availability = $availability - $lockout_quantity;
                        if ( $new_availability < 0 ) {
                            $validation_check = 1; // product is unavailable for the desired quantity
                        }
                    }
                } else {
                    $validation_check = 1; // product is not available
                }
            
            } else {
            
                $hidden_checkout_date = '';
                if ( isset( $checkout_date_to_display ) && $checkout_date_to_display != '' ) {
                    $hidden_checkout_date = date( 'Y-m-d', $event_end );
                }
                $bookings_placed        = '';
                $attr_bookings_placed   = '';
                                    
                $variation_booked_dates_array = bkap_variations::bkap_get_booked_dates_for_variation( $parent_id, $variation_id );

                $bookings_placed = ( isset( $variation_booked_dates_array['wapbk_bookings_placed_'] ) && $variation_booked_dates_array['wapbk_bookings_placed_'] != '' ) ? $variation_booked_dates_array['wapbk_bookings_placed_'] : '';
                
                $availability = bkap_booking_process::bkap_get_date_availability( $parent_id, $variation_id, $hidden_date, $booking_date_to_display, $bookings_placed, $attr_bookings_placed, $hidden_checkout_date, false );

                $unlimited_lang = trim ( __( 'Unlimited ', 'woocommerce-booking' ) );

            
                if ( $availability > 0 || trim( $availability ) == $unlimited_lang ) {
                    if ( $availability > 0 ) {
                        $new_availability = $availability - $lockout_quantity;
                        if ( $new_availability < 0 ) {
                            $validation_check = 1; // product is unavailable for the desired quantity
                        }
                    }
                } else {
                    $validation_check = 1; // product is not available
                }
            }
        } else {
            $validation_check = 1;
        }
        $sanity_results = array( 'backdated_event'  => $backdated_event,
                                 'validation_check' => $validation_check,
                                 'grouped_event'    => $grouped_product
        );
        
        return $sanity_results;
    }
    
    static function bkap_quantity_setup( $booking_data, $sanity_check ) {
        $quantity           = 1;
        $lockout_quantity   = 1; // this is the qty which will be used to update lockout. Item and lockout qty can be different in case of attribute vaues being used as lockout qty
        
        $product_id         = $booking_data[ 'product_id' ];
        $_product           = wc_get_product( $product_id );
        
        $variationsArray    = array();
        
        // if the product ID has a parent post Id, then it means it's a variable product
        $variation_id = 0;
        
        $parent_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $_product->parent->id : $_product->get_parent_id();
        if ( $parent_id > 0 ) {
            $variation_id = $product_id;
        
            $parent_product = wc_get_product( $parent_id );
            $variations_list = $parent_product->get_available_variations();
        
            foreach ( $variations_list as $variation ) {
                if ( $variation[ 'variation_id' ] == $product_id ) {
                    $variationsArray[ 'variation' ] = $variation[ 'attributes' ];
                }
            }
        
            // Product Attributes - Booking Settings
            $attribute_booking_data = get_post_meta( $parent_id, '_bkap_attribute_settings', true );
        
            if ( is_array( $attribute_booking_data ) && count( $attribute_booking_data ) > 0 ) {
                $lockout_quantity = 1;
                foreach ( $attribute_booking_data as $attr_name => $attr_settings ) {
                    $attr_name = 'attribute_' . $attr_name;
                    if ( isset( $attr_settings[ 'booking_lockout_as_value' ] ) && 'on' == $attr_settings[ 'booking_lockout_as_value' ] ) {
                        if ( array_key_exists( $attr_name, $variationsArray[ 'variation' ] ) ) {
                            $lockout_quantity += $variationsArray[ 'variation' ][ $attr_name ];
                        }
                    }
                }
            }
        } else {
            $parent_id = $product_id;
        }
        
        $quantity_return[ 'quantity' ]          = $quantity;
        $quantity_return[ 'lockout_qty' ]       = $lockout_quantity;
        $quantity_return[ 'parent_id' ]         = $parent_id;
        $quantity_return[ 'variation_id' ]      = $variation_id;
        $quantity_return[ 'variationsArray' ]   = $variationsArray;
        
        return $quantity_return;
    }
    
    
} // end of class