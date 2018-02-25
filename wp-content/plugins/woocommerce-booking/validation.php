<?php
//if(!class_exists('woocommerce_booking')){
//   die();
//}
include_once( 'bkap-common.php' );
include_once( 'lang.php' );

class bkap_validation{

   /**
    *This functions validates the Availability for the selected date and timeslots.
    */
    
    public static function bkap_get_validate_add_cart_item( $passed, $product_id, $qty ) {
        
        global $wp;
        $product_id = bkap_common::bkap_get_product_id($product_id);
        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
        $product = wc_get_product($product_id);
        $product_type = $product->get_type();

        if ( $booking_settings != '' && ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] == 'on' ) ) {
            
            if ( isset( $booking_settings['booking_purchase_without_date'] ) && $booking_settings['booking_purchase_without_date'] == 'on' ) {
                
                if ( isset( $_POST['wapbk_hidden_date'] ) && $_POST['wapbk_hidden_date'] != "" ) {
                    $quantity = bkap_validation::bkap_get_quantity( $product_id );
                    
                    if ( $quantity == 'yes' ) {
                        $passed = true;
                    }else {
                        $passed = false;
                    }

                    if ( $product_type === 'bundle' ) {
                        $passed = self::bkap_get_bundle_item_validations( $product_id, $product );
                    }
                    
                }else $passed = true;
                
            }else {
                
                if ( isset( $_POST['wapbk_hidden_date'] ) && $_POST['wapbk_hidden_date'] != "" ) {
                    $quantity = bkap_validation::bkap_get_quantity( $product_id );
                    
                    if ( $quantity == 'yes' ) {
                        $passed = true;
                    }else {
                        $passed = false;
                    }

                    if ( $product_type === 'bundle' ) {
                        $passed = self::bkap_get_bundle_item_validations( $product_id, $product );
                    }
                    
                } else if ( isset( $_GET['pay_for_order'] ) && isset( $_GET['key'] ) && isset( $wp->query_vars['order-pay'] ) && isset( $_GET['subscription_renewal'] ) && $_GET['subscription_renewal'] === 'true' ) {
                    $passed = true;
                } else {
                    $passed = false;
                    $message = __( 'Product cannot be added to cart. Please select date to continue.', 'woocommerce-booking' );
                    wc_add_notice( $message, $notice_type = 'error' );
                }
            }
        }else {
            $passed = true;
        }

        return $passed;
    }

    /**
     * Validate Bundle Products
     * 
     * @param string|int $product_id Product ID
     * @param WC_Product $product Product Object
     * @return bool true for available and false for locked out
     * @since 4.2
     */
    public static function bkap_get_bundle_item_validations( $product_id, $product ) {
        
        $cart_configs = bkap_common::bkap_bundle_add_to_cart_config( $product );

        foreach ( $cart_configs as $cart_key => $cart_value ) {
            
            if ( isset( $cart_value['quantity'] ) && $cart_value['quantity'] > 0 ) {

                if ( isset( $cart_value['variation_id'] ) && $cart_value['variation_id'] !== '' ) {
                    $_POST['variation_id'] = $cart_value['variation_id'];
                }

                $quantity = bkap_validation::bkap_get_quantity( $cart_value['product_id'] );

                if ( $quantity !== 'yes' ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * This function checks the overlapping timeslot for the selected date 
     * and timeslots when the product is added to cart.
     * 
     * If the overlapped timeslot is already in cart and availability is less then selected
     * bookinng date and timeslot then it will retun and array which contains the overlapped date
     * and timeslot present in the cart
     *
     * @param $product_id Product ID int, $post $_POST array, $check_in_date Date, $from_time_slot From Time, $to_time_slot To Time
     * @since 4.4.0
     * @return $pass_fail_result_array Array contains overlapped start date and timeslot present in cart. 
     *
     */

    public static function bkap_validate_overlap_time_cart ( $product_id , $post, $check_in_date, $from_time_slot, $to_time_slot ) {
        global $wpdb;

        $qty                        = $post['quantity'];
        $pass_fail_result_array     = array();
        $pass_fail_result           = true;

        $query                      =   "SELECT *  FROM `".$wpdb->prefix."booking_history`
                                        WHERE post_id = '".$product_id."' AND
                                        start_date = '".$check_in_date."' AND
                                        available_booking > 0  AND
                                        status !=  'inactive' ";
        $get_all_time_slots         =   $wpdb->get_results( $query );
        

        if( count( $get_all_time_slots ) == 0 ) {
            return $pass_fail_result_array;
        }

        foreach( $get_all_time_slots as $time_slot_key => $time_slot_value ) {

            $timeslot                           = $from_time_slot." - ".$to_time_slot;          

            $query_from_time_time_stamp         = strtotime( $from_time_slot ); 
            $query_to_time_time_stamp           = strtotime( $to_time_slot ); 

            $time_slot_value_from_time_stamp    = strtotime( $time_slot_value->from_time );
            $time_slot_value_to_time_stamp      = strtotime( $time_slot_value->to_time );

            $db_timeslot                        = $time_slot_value->from_time." - ".$time_slot_value->to_time;

            if ( $query_to_time_time_stamp > $time_slot_value_from_time_stamp && $query_from_time_time_stamp < $time_slot_value_to_time_stamp ){                
                                         
                if ( $time_slot_value_from_time_stamp != $query_from_time_time_stamp || $time_slot_value_to_time_stamp != $query_to_time_time_stamp ) {
                     
                    foreach( WC()->cart->cart_contents as $prod_in_cart_key => $prod_in_cart_value ) {

                        if( isset( $prod_in_cart_value['bkap_booking'] ) && !empty( $prod_in_cart_value['bkap_booking'] ) ){
                            
                            $booking_data = $prod_in_cart_value['bkap_booking'];
                            $product_qty  = $prod_in_cart_value['quantity'];


                            foreach ( $booking_data as $value ) {
                                
                                if ( isset( $value['time_slot'] ) && $value['time_slot'] != "" ) {
                                    
                                    if( $value['time_slot'] == $db_timeslot && $time_slot_value->available_booking > 0 ){
                                            
                                        $compare_qty = $time_slot_value->available_booking - $product_qty;

                                        if( $compare_qty < $qty ){
                                            $pass_fail_result                               = false;

                                            $pass_fail_result_array['pass_fail_result']     = $pass_fail_result;
                                            $pass_fail_result_array['pass_fail_date']       = $check_in_date;
                                            $pass_fail_result_array['pass_fail_timeslot']   = $db_timeslot;
                                        }    
                                    }
                                }
                            }
                        }
                    }  
                }
            }            
        }

        return $pass_fail_result_array;
    }

    /**
     * This function checks the availabilty for the selected date 
     * and timeslots when the product is added to cart.
     * If availability is less then selected it prevents product from 
     * being added to the cart and displays an error message.
     */
    public static function bkap_get_quantity( $post_id ) {
        global $wpdb,$woocommerce;

        global $bkap_date_formats;
        
        $post_id = bkap_common::bkap_get_product_id( $post_id );
        
        $booking_settings = get_post_meta( $post_id , 'woocommerce_booking_settings', true );
        $post_title       = get_post($post_id);
        $date_check       = date( 'Y-m-d', strtotime( $_POST['wapbk_hidden_date'] ) );
        $product          = wc_get_product( $post_id );
        $parent_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $product->get_parent() : bkap_common::bkap_get_parent_id( $post_id );
            
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        
        if ( isset( $saved_settings ) ) {
            $time_format = $saved_settings->booking_time_format;
            $date_format_to_display = $saved_settings->booking_date_format;
        }else {
            $time_format = "12";
            $date_format_to_display = 'mm/dd/y';
        }

        $date_to_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $date_check ) );
        
        $quantity_check_pass = 'yes';
        
        if ( isset( $_POST['variation_id'] ) ) {
            $variation_id = $_POST['variation_id'];
        }else {
            $variation_id = '';
        }
        $_POST[ 'product_id' ] = $post_id;
        
        // Resource validation start here
        
        if ( isset( $_POST['bkap_front_resource_selection'] ) ) {

            $resource_id    = (int)$_POST['bkap_front_resource_selection'];

            $resource_name  = get_the_title( $resource_id );

            $resource_validation_result = array( 'quantity_check_pass' => $quantity_check_pass, 'resource_booking_available' => '' );

            $resource_validation_result = apply_filters( 'bkap_resource_add_to_cart_validation', $_POST, $post_id, $booking_settings, $quantity_check_pass, $resource_validation_result );

            if ( $resource_validation_result['quantity_check_pass'] == "no" ) {
                
                if( isset( $_POST['time_slot'] ) && $_POST['time_slot'] != "" ) {
                    $message = sprintf( 'You have all available spaces for %s on date %s for %s timeslot in your cart. Please visit the <a href="%s">%s</a> to place the order.', $resource_name, $date_to_display, $_POST['time_slot'], esc_url( wc_get_page_permalink( 'cart' ) ), esc_html__( 'cart', 'woocommerce' ) );
                } else {
                    $message = sprintf( 'You have all available spaces for %s on date %s in your cart. Please visit the <a href="%s">%s</a> to place the order.', $resource_name, $date_to_display, esc_url( wc_get_page_permalink( 'cart' ) ), esc_html__( 'cart', 'woocommerce' ) );
                }
                wc_add_notice( $message, $notice_type = 'error' );
            }           

            return $resource_validation_result['quantity_check_pass'];
        }
        
        // before checking lockout validations, confirm that the cart does not contain any conflicting products
        $quantity_check_pass = apply_filters( 'bkap_validate_cart_products', $_POST, $post_id );
        
        if ( /*isset( $booking_settings['booking_enable_time'] ) &&*/ $booking_settings['booking_enable_time'] == 'on' ) {
            $type_of_slot = apply_filters( 'bkap_slot_type', $post_id );
            
            if ( $type_of_slot == 'multiple' ) {
                $quantity_check_pass = apply_filters( 'bkap_validate_add_to_cart', $_POST, $post_id );
            }else {
                do_action( 'bkap_date_time_product_validation' );
                 
                if ( ! isset( $_POST[ 'validated' ] ) || ( isset( $_POST[ 'validated' ] ) && $_POST[ 'validated' ] == 'NO' ) ) {
                    
                    if ( isset( $_POST['quantity'] ) ) $item_quantity = $_POST['quantity'];
                    else $item_quantity = 1;
                    
                    if ( isset( $_POST['time_slot'] ) ) {
                        $time_range    = explode( "-", $_POST['time_slot'] );
                        $from_time     = date( 'G:i', strtotime( $time_range[0] ) );
                        
                        if( isset( $time_range[1] ) ) $to_time = date( 'G:i', strtotime( $time_range[1] ) );
                        else $to_time = '';
                        
                    }else {
                        $to_time = '';
                        $from_time = '';
                    }

                    $overlapping_quantity_check_pass = self::bkap_validate_overlap_time_cart( $post_id, $_POST, $date_check, $from_time, $to_time );
                    
                    if ( ! empty( $overlapping_quantity_check_pass ) ) {
                        

                        $overlap_timeslot = $overlapping_quantity_check_pass['pass_fail_timeslot'];
                        $overlap_time     = explode( "-", $overlap_timeslot );


                        if ( $time_format === '12' ) {
                            $overlap_start_time = date ( 'h:i A', strtotime( $overlap_time[0] ) );
                            $overlap_end_time   = date( 'h:i A', strtotime( $overlap_time[1]) );
                        } else {
                            $overlap_start_time = date ( 'H:i', strtotime( $overlap_time[0] ) );
                            $overlap_end_time   = date( 'H:i', strtotime( $overlap_time[0] ) );
                        }

                        $overlap_date_to_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $overlapping_quantity_check_pass['pass_fail_date'] ) );

                        $original_timeslot = $_POST['time_slot'];


                        $message = __( "$post_title->post_title cannot be booked for $overlap_date_to_display, $original_timeslot as booking has already been added to the cart for $overlap_date_to_display, $overlap_start_time - $overlap_end_time. In case if you wish to book for <$overlap_start_time - $original_timeslot please remove the existing product from the cart and add it or edit the booking details.", 'woocommerce-booking' );

                        wc_add_notice( $message, $notice_type = 'error');

                        $quantity_check_pass = 'no';
                    }
                
                    if ( $to_time != '' ) {
                        $query     =   "SELECT total_booking, available_booking, start_date FROM `".$wpdb->prefix."booking_history`
                                        WHERE post_id = %d
                                        AND start_date = %s
                                        AND from_time = %s
                                        AND to_time = %s
                                AND status !=  'inactive'";
                        $results   =   $wpdb->get_results( $wpdb->prepare( $query, $post_id, $date_check, $from_time, $to_time ) );
                        
                        if( isset( $results ) && count( $results ) == 0 ) {
                            $from_time      = date( 'H:i', strtotime( $from_time ) );
                            $to_time        = date( 'H:i', strtotime( $to_time ) );  
                            
                            $results   =   $wpdb->get_results( $wpdb->prepare( $query, $post_id, $date_check, $from_time, $to_time ) );
                        } 
                        
                        if( isset( $results ) && count( $results ) == 0 ) {
                        
                            $weekday        =   date( 'w', strtotime( $date_check ) );
                            $booking_weekday    =   "booking_weekday_$weekday";
                        
                            $query     =  "SELECT total_booking, available_booking, start_date FROM `".$wpdb->prefix."booking_history`
                                            WHERE post_id = %d
                                            AND weekday = %s
                                            AND from_time = %s
                                            AND to_time = %s
                                            AND status !=  'inactive'";
                            $results   =   $wpdb->get_results( $wpdb->prepare( $query, $post_id, $booking_weekday, $from_time, $to_time ) );
                        }
                        
                    }else {
                        $query     =   "SELECT total_booking, available_booking, start_date FROM `".$wpdb->prefix."booking_history`
                                        WHERE post_id = %d
                                        AND start_date = %s
                                        AND from_time = %s
                                            AND status !=  'inactive'";
                                        
                        $prepare_query = $wpdb->prepare( $query, $post_id, $date_check, $from_time );
                        $results = $wpdb->get_results( $prepare_query );
                    }
                    
                    if ( isset( $results ) && count( $results ) > 0) {
                            
                        if ( isset( $_POST[ 'time_slot' ] ) && $_POST[ 'time_slot' ] != "" ) {
                            // if current format is 12 hour format, then convert the times to 24 hour format to check in database
                            if ( $time_format == '12' ) {
                                $time_exploded   = explode( "-", $_POST['time_slot'] );
                                $from_time       = date( 'h:i A', strtotime( $time_exploded[0] ) );
                                
                                if( isset( $time_exploded[1] ) ) $to_time = date( 'h:i A', strtotime( $time_exploded[1] ) );
                                else $to_time = '';
                                    
                                if( $to_time != '' ) $time_slot_to_display = $from_time.' - '.$to_time;
                                else $time_slot_to_display = $from_time;
                                
                            } else {
                                    
                                if( $to_time != '' ) $time_slot_to_display = $from_time.' - '.$to_time;
                                else $time_slot_to_display = $from_time;
                            }
                            
                            $date_to_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $date_check ) );
                            
                            if ( isset( $parent_id ) && $parent_id != '' && is_array( $item_quantity ) ) {
                                
                                if( $results[0]->available_booking > 0 && $results[0]->available_booking < $item_quantity[$post_id] ) {
            
                                        $msg_text = __( get_option( 'book_limited-booking-msg-time' ), 'woocommerce-booking' );
                                        $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE', 'TIME' ), array( $post_title->post_title, $results[ 0 ]->available_booking, $date_to_display, $time_slot_to_display ), $msg_text );
                                        wc_add_notice( $message, $notice_type = 'error' );
                                        $quantity_check_pass = 'no';
                                } elseif ( $results[0]->total_booking > 0 && $results[0]->available_booking == 0 ) {
    
                                    $msg_text = __( get_option( 'book_no-booking-msg-time' ), 'woocommerce-booking' );
                                    $message = str_replace( array( 'PRODUCT_NAME', 'DATE', 'TIME' ), array( $post_title->post_title, $date_to_display, $time_slot_to_display ), $msg_text );
                                        wc_add_notice( $message, $notice_type = 'error' );
                                        $quantity_check_pass = 'no';
                                }
                                
                            } else {
                                
                                if ( $results[0]->available_booking > 0 && $results[0]->available_booking < $item_quantity ) {
    
                                        $msg_text = __( get_option( 'book_limited-booking-msg-time' ), 'woocommerce-booking' );
                                        $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE', 'TIME' ), array( $post_title->post_title, $results[ 0 ]->available_booking, $date_to_display, $time_slot_to_display ), $msg_text );
                                        wc_add_notice( $message, $notice_type = 'error' );
                                        $quantity_check_pass = 'no';
                                } elseif ( $results[0]->total_booking > 0 && $results[0]->available_booking == 0 ) {
    
                                        $msg_text = __( get_option( 'book_no-booking-msg-time' ), 'woocommerce-booking' );
                                        $message = str_replace( array( 'PRODUCT_NAME', 'DATE', 'TIME' ), array( $post_title->post_title, $date_to_display, $time_slot_to_display ), $msg_text );
                                        wc_add_notice( $message, $notice_type = 'error');
                                        $quantity_check_pass = 'no';
                                }
                            }
                        }
                    } else {
                        $message = __( "This product cannot be added to cart. Please contact Store Manager for further information", 'woocommerce-booking' );
                        wc_add_notice( $message, $notice_type = 'error');
                        $quantity_check_pass = 'no';
                    }
                    //check if the same product has been added to the cart for the same dates
                    if ( $quantity_check_pass == "yes" ) {
                            
                        foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
                            if ( isset( $values[ 'bkap_booking' ] ) ) {
                                $booking      = $values['bkap_booking'];
                                $quantity     = $values['quantity'];
                                $product_id   = $values['product_id'];
                                
                                if ( isset( $booking ) && count( $booking ) > 0 ) {
                                    
                                    if ( $product_id == $post_id && $booking[0]['hidden_date'] == $_POST['wapbk_hidden_date'] && isset( $booking[0]['time_slot'] ) && isset( $_POST['time_slot'] ) && ( $booking[0]['time_slot'] == $_POST['time_slot'] ) ) {
                                        
                                        if ( isset( $parent_id ) && $parent_id != '' && is_array( $item_quantity ) ) {
                                            $total_quantity = $item_quantity[ $post_id ] + $quantity;
                                        }else {
                                            $total_quantity = $item_quantity + $quantity;
                                        }
                                        
                                        if ( isset( $results ) && count( $results ) > 0 ) {
                
                                            if ( $results[0]->available_booking > 0 && $results[0]->available_booking < $total_quantity ) {
                                                    
                                                $msg_text = __( get_option( 'book_limited-booking-msg-time' ), 'woocommerce-booking' );
                                                $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE', 'TIME' ), array( $post_title->post_title, $results[ 0 ]->available_booking, $date_to_display, $time_slot_to_display ), $msg_text );
                                                wc_add_notice( $message, $notice_type = 'error' );
                                                $quantity_check_pass = 'no';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $quantity_check_pass = $_POST[ 'quantity_check_pass' ];
                }
            }
        } 
        elseif ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
            
            do_action( 'bkap_multiple_days_product_validation' );
             
            if ( ! isset( $_POST[ 'validated' ] ) || ( isset( $_POST[ 'validated' ] ) && $_POST[ 'validated' ] == 'NO' ) ) {
                
                $date_checkout   = date( 'd-n-Y', strtotime( $_POST['wapbk_hidden_date_checkout'] ) );
                $date_cheeckin   = date( 'd-n-Y', strtotime( $_POST['wapbk_hidden_date'] ) );
                $order_dates     = bkap_common::bkap_get_betweendays( $date_cheeckin, $date_checkout );
                $todays_date     = date( 'Y-m-d' );
        
                $query_date      = "SELECT DATE_FORMAT(start_date,'%d-%c-%Y') as start_date,DATE_FORMAT(end_date,'%d-%c-%Y') as end_date FROM ".$wpdb->prefix."booking_history
                                   WHERE start_date >='".$todays_date."' AND post_id = '".$post_id."'";
                    
                $results_date    = $wpdb->get_results( $query_date );
                    
                $dates_new       = array();
                    
                foreach ( $results_date as $k => $v ) {
                    $start_date = $v->start_date;
                    $end_date   = $v->end_date;
                    $dates      = bkap_common::bkap_get_betweendays( $start_date, $end_date );
                    $dates_new  = array_merge( $dates,$dates_new );
                }
                $dates_new_arr = array_count_values( $dates_new );
                    
                $lockout = "";
                
                if ( isset( $booking_settings['booking_date_lockout'] ) ) {
                    $lockout = $booking_settings['booking_date_lockout'];
                }
            
                if ( isset( $_POST['quantity'] ) && is_array( $_POST['quantity'] ) ) {
                    $item_quantity = $_POST['quantity'][$post_id];
                }else {
                    
                    if ( isset( $_POST['quantity'] ) ) {
                        $item_quantity = $_POST['quantity'];
                    }else {
                        $item_quantity = 1;
                    }
                    
                }

                $date_availablity = array();
                
                if ( $lockout > 0 ) {
                    foreach ( $order_dates as $k => $v ) {
                        
                        $date_availablity[ $v ] = $lockout;
                        
                        if ( array_key_exists( $v,$dates_new_arr ) ) {
                            
                            if ( $lockout != 0 && $lockout < $dates_new_arr[ $v ] + $item_quantity ) {
                                $available_tickets    = $lockout - $dates_new_arr[ $v ];
                                $date_availablity[ $v ] = $available_tickets;
                                $quantity_check_pass  = 'no';
                            }
                        } else {
                                
                            if ( $lockout != 0 && $lockout < $item_quantity ) {
                                $available_tickets    = $lockout;
                                $date_availablity[ $v ] = $available_tickets;
                                $quantity_check_pass  = 'no';
                            }
                        }
                    }
                    
                    if ( $quantity_check_pass == 'no' ) {
                         
                        if ( is_array( $date_availablity ) && count( $date_availablity ) > 0 ) {
                            $least_availability = '';
                            // find the least availability
                            foreach ( $date_availablity as $date => $available ) {
                                if ( '' == $least_availability ) {
                                    $least_availability = $available;
                                }
                    
                                if ( $least_availability > $available ) {
                                    $least_availability = $available;
                                }
                            }
                            // setup the dates to be displayed
                            $check_in_to_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $_POST[ 'wapbk_hidden_date' ] ) );
                            $check_out_to_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $_POST[ 'wapbk_hidden_date_checkout' ] ) );
                            $date_range = "$check_in_to_display to $check_out_to_display";
                    
                            $msg_text = __( get_option( 'book_limited-booking-msg-date' ), 'woocommerce-booking' );
                            $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE' ), array( $post_title->post_title, $least_availability, $date_range ), $msg_text );
                            wc_add_notice( $message, $notice_type = 'error' );
                        }
                    }
                }
                //check if the same product has been added to the cart for the same dates
                if ( $quantity_check_pass == "yes" ) {

                    $date_availablity = array();
                    $quantity = 0;
                    foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
                        
                        if ( isset( $values['bkap_booking'] ) ) {
                            $booking = $values['bkap_booking'];
                        }
                        
                        
                        $product_id    = $values['product_id'];
        
                        if ( isset( $booking[0]['hidden_date'] ) && isset( $booking[0]['hidden_date_checkout'] ) ) {
                            $hidden_date          = date( 'd-n-Y', strtotime( $booking[0]['hidden_date'] ) );
                            $hidden_date_checkout = date( 'd-n-Y', strtotime( $booking[0]['hidden_date_checkout'] ) );
                            $dates                = bkap_common::bkap_get_betweendays( $hidden_date, $hidden_date_checkout );
                        }
                        if ( $product_id == $post_id ) {
                            $quantity      += $values['quantity'];
                            if ( $lockout > 0 ) {
                                foreach ( $order_dates as $k => $v ) {
                                    
                                    $date_to_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $v ) );
                                    
                                    if ( array_key_exists( $v, $dates_new_arr ) ) {
                                        
                                        if ( isset( $date_availablity[ $v ] ) ) {
                                            $date_availablity[ $v ] += $item_quantity;
                                        } else {
                                            $date_availablity[ $v ] = $dates_new_arr[ $v ] + $item_quantity;
                                        }
                                        if ( in_array( $v, $dates ) ) {
                                            
                                            if ( $lockout != 0 && $lockout < $date_availablity[ $v ] + $quantity ) {
                                                $available_tickets    = $lockout - $dates_new_arr[ $v ];
                                                $date_availablity[ $v ] = $available_tickets;
                                                $quantity_check_pass  = 'no';
                                            }
                                        }else {
                                            
                                            if ( $lockout != 0 && $lockout < $date_availablity[ $v ] ) {
                                                $available_tickets    = $lockout - $dates_new_arr[ $v ];
                                                $date_availablity[ $v ] = $available_tickets;
                                                $quantity_check_pass  = 'no';
                                            }
                                        }
                                    }else {
                                        
                                        if ( isset( $date_availablity[ $v ] ) ) {
                                            $date_availablity[ $v ] += $item_quantity;
                                        } else {
                                            $date_availablity[ $v ] = $item_quantity;
                                        }
                                            
                                        if ( in_array( $v, $dates ) ) {
                                            
                                            if ( $lockout != 0 && $lockout < $date_availablity[ $v ] + $quantity ) {
                                                $available_tickets    = $lockout;
                                                $date_availablity[ $v ] = $available_tickets;
                                                $quantity_check_pass  = 'no';
                                            }
                                        }else {
                                            
                                            if ( $lockout != 0 && $lockout < $item_quantity ) {
                                                $available_tickets    = $lockout;
                                                $date_availablity[ $v ] = $available_tickets;
                                                $quantity_check_pass  = 'no';
                                            }
                                        }
                                    }
                                }
                                
                                if ( $quantity_check_pass == 'no' ) {
                                        
                                    if ( is_array( $date_availablity ) && count( $date_availablity ) > 0 ) {
                                        $least_availability = '';
                                        // find the least availability
                                        foreach ( $date_availablity as $date => $available ) {
                                            if ( '' == $least_availability ) {
                                                $least_availability = $available;
                                            }
                                
                                            if ( $least_availability > $available ) {
                                                $least_availability = $available;
                                            }
                                        }
                                        // setup the dates to be displayed
                                        $check_in_to_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $_POST[ 'wapbk_hidden_date' ] ) );
                                        $check_out_to_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $_POST[ 'wapbk_hidden_date_checkout' ] ) );
                                        $date_range = "$check_in_to_display to $check_out_to_display";
                                
                                        $msg_text = __( get_option( 'book_limited-booking-msg-date' ), 'woocommerce-booking' );
                                        $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE' ), array( $post_title->post_title, $least_availability, $date_range ), $msg_text );
                                        wc_add_notice( $message, $notice_type = 'error' );
                                    }
                                        
                                }
                            }
                        }
                    }
                }
            } else {
                $quantity_check_pass = $_POST[ 'quantity_check_pass' ];
            }
        } 
        else {
            
            do_action( 'bkap_single_days_product_validation' );
             
            if ( ! isset( $_POST[ 'validated' ] ) || ( isset( $_POST[ 'validated' ] ) && $_POST[ 'validated' ] == 'NO' ) ) {
                $query   =   "SELECT total_booking, available_booking, start_date FROM `".$wpdb->prefix."booking_history`
                             WHERE post_id = %d
                             AND start_date = %s 
                             AND status != 'inactive' ";
                $results =  $wpdb->get_results( $wpdb->prepare( $query, $post_id, $date_check ) );
        
                if ( isset( $_POST['quantity'] ) ) {
                    $item_quantity = $_POST['quantity'];
                }else {
                    $item_quantity = 1;
                }
                
                if ( isset( $results ) && count( $results ) > 0 ) {
                            
                    $date_to_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $results[0]->start_date ) );
                    
                    //Validation for parent products page - Grouped Products  , Here $item_array come Array when order place from the Parent page 
                    if ( isset( $parent_id ) && $parent_id != '' && is_array( $item_quantity ) ) {
                        
                        if( $results[0]->available_booking > 0 && $results[0]->available_booking < $item_quantity[$post_id] ) {
                            
                            $msg_text = __( get_option( 'book_limited-booking-msg-date' ), 'woocommerce-booking' );
                            $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE' ), array( $post_title->post_title, $results[ 0 ]->available_booking, $date_to_display ), $msg_text );
                            wc_add_notice( $message, $notice_type = 'error' );
                            $quantity_check_pass   = 'no';
                            
                        }elseif ( $results[0]->total_booking > 0 && $results[0]->available_booking == 0 ) {
                            
                            $msg_text = __( get_option( 'book_no-booking-msg-date' ), 'woocommerce-booking' );
                            $message = str_replace( array( 'PRODUCT_NAME', 'DATE' ), array( $post_title->post_title, $date_to_display ), $msg_text );
                            wc_add_notice( $message, $notice_type = 'error' );
                            $quantity_check_pass   = 'no';
                        }
                        
                    } else {
                        
                        if( $results[0]->available_booking > 0 && $results[0]->available_booking < $item_quantity ) {
                            
                            $msg_text = __( get_option( 'book_limited-booking-msg-date' ), 'woocommerce-booking' );
                            $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE' ), array( $post_title->post_title, $results[ 0 ]->available_booking, $date_to_display ), $msg_text );
                            wc_add_notice( $message, $notice_type = 'error' );
                            $quantity_check_pass    = 'no';
                            
                        }elseif ( $results[0]->total_booking > 0 && $results[0]->available_booking == 0 ) {
                            
                            $msg_text = __( get_option( 'book_no-booking-msg-date' ), 'woocommerce-booking' );
                            $message = str_replace( array( 'PRODUCT_NAME', 'DATE' ), array( $post_title->post_title, $date_to_display ), $msg_text );
                            wc_add_notice( $message, $notice_type = 'error' );
                            $quantity_check_pass    = 'no';
                        }
                    }
                }
                if ( $quantity_check_pass == "yes" ) {
                    
                    foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
                        
                        if( array_key_exists( 'bkap_booking', $values ) ) {
                            $booking = $values['bkap_booking'];
                        }else {
                            $booking = array();
                        }
                        
                        $quantity = $values['quantity'];
                        $product_id = $values['product_id'];
                        
                        if ( $product_id == $post_id && isset( $booking[0]['hidden_date'] ) && isset( $_POST['wapbk_hidden_date'] ) && $booking[0]['hidden_date'] == $_POST['wapbk_hidden_date'] ) {
                            
                            if ( isset( $parent_id ) && $parent_id != '' && is_array( $item_quantity ) ) {
                                $total_quantity = $item_quantity[ $post_id ] + $quantity;
                            } else {
                                $total_quantity = $item_quantity + $quantity;
                            }
                            
                            if ( isset( $results ) && count( $results ) > 0 ) {

                                $date_to_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $results[0]->start_date ) );
                                
                                if ( isset($parent_id) && $parent_id != '' && is_array( $item_quantity ) ) {
                                    if( $results[0]->available_booking > 0 && $results[0]->available_booking < $total_quantity ) {
                                            $msg_text = __( get_option( 'book_limited-booking-msg-date' ), 'woocommerce-booking' );
                                            $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE' ), array( $post_title->post_title, $results[ 0 ]->available_booking, $date_to_display ), $msg_text );
                                            wc_add_notice( $message, $notice_type = 'error');
                                            $quantity_check_pass    = 'no';
                                    }
                                 } else {
                                        if( $results[0]->available_booking > 0 && $results[0]->available_booking < $total_quantity ) {
                                            $msg_text = __( get_option( 'book_limited-booking-msg-date' ), 'woocommerce-booking' );
                                            $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE' ), array( $post_title->post_title, $results[ 0 ]->available_booking, $date_to_display ), $msg_text );
                                            wc_add_notice( $message, $notice_type = 'error');
                                            $quantity_check_pass    = 'no';
                                        }
                                 }
                            }
                        }
                    }
                }
            } else {
                $quantity_check_pass = $_POST[ 'quantity_check_pass' ];
            }
        }
        return $quantity_check_pass;
    }
        
    /**
     * This function checks availability for date and 
     * time slot on the cart page when quantity on 
     * cart page is changed.
     */
    
    public static function bkap_quantity_check(){
        global $woocommerce, $wpdb;
        
        global $bkap_date_formats;
        
        // Get the order ID if an order is already pending
        $order_id = absint( WC()->session->order_awaiting_payment );
        
        // An order was already created, this means the validation has been run once already.
        if ( $order_id > 0 && ( $order = wc_get_order( $order_id ) ) && $order->has_status( array( 'pending', 'failed' ) ) ) {
            // Confirm if data is found in the order history table for the given order, then we need to skip the validation
            $check_data      =   "SELECT * FROM `".$wpdb->prefix."booking_order_history`
                                 WHERE order_id = %s";
            $results_check   =   $wpdb->get_results ( $wpdb->prepare ( $check_data, $order_id ) );
            
            if ( count ( $results_check ) > 0 ) {
                return;
            }
            
        }   
        
        $availability_display = array();
        
        foreach ( $woocommerce->cart->cart_contents as $key => $value ) {
            
            $date_availablity    = array();
            
            $duplicate_of        = bkap_common::bkap_get_product_id( $value['product_id'] );
    
            $booking_settings    = get_post_meta( $duplicate_of , 'woocommerce_booking_settings' , true );
            $post_title          = get_post( $value['product_id'] );
            $date_check          = '';
            $date_checkout       = '';
            if ( isset( $value['bkap_booking'][0]['hidden_date'] ) ){
                $date_check = date( 'Y-m-d', strtotime( $value['bkap_booking'][0]['hidden_date'] ) );
            } else{
                continue;
            }

            if ( isset( $value['bkap_booking'][0]['hidden_date_checkout'] ) ) {
                $date_checkout = date( 'd-n-Y', strtotime( $value['bkap_booking'][0]['hidden_date_checkout'] ) );
            }
                
            $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
            
            if ( isset( $saved_settings ) ){
                $time_format = $saved_settings->booking_time_format;
                $date_format_to_display = $saved_settings->booking_date_format;
            } else {
                $time_format = "12";
                $date_format_to_display = 'mm/dd/y';
            }

            if ( isset( $value['bkap_booking'][0]['date'] ) ) {
                $date_to_display = $value['bkap_booking'][0]['date'] ; 
                
            } else {
                $date_to_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $date_check ) );
            }

            if ( isset( $value['bkap_booking'][0]['date_checkout'] ) ) {
                $check_out_to_display = $value['bkap_booking'][0]['date_checkout']; 
            } else {
                $check_out_to_display = date( $bkap_date_formats[ $date_format_to_display ], strtotime( $date_checkout ) );    
            }

            if( isset( $value['variation_id'] ) )
            {
                $variation_id = $value['variation_id'];
            }else {
                $variation_id = '';
            }
            
            // save the data in $_POST, so it can be accessed in hooks and does not need to be passed each time
            $_POST[ 'product_id' ]      = $duplicate_of;
            $_POST[ 'variation_id' ]    = $variation_id;
            $_POST[ 'booking_date' ]    = $date_check;
            $_POST[ 'quantity' ]        = $value[ 'quantity'];
            $_POST[ 'cart_item_key' ]   = $key;
            
            /* Resource checking start */
            
            if ( isset( $value['bkap_booking'][0]['resource_id'] ) && $value['bkap_booking'][0]['resource_id'] > 0 ) {
            
                $resource_id                = $value['bkap_booking'][0]['resource_id'];
                $bkap_resource_availability = get_post_meta( $resource_id, '_bkap_resource_qty', true );
                $resource_booking_data      = Class_Bkap_Product_Resource::print_hidden_resource_data( array() , $booking_settings, $duplicate_of );
            
                $resource_bookings_placed   = $resource_booking_data['bkap_booked_resource_data'][$resource_id]['bkap_booking_placed'];
            
                $resource_bookings_placed_list_dates    = explode( ",", $resource_bookings_placed );
                $resource_date_array                    = array();
            
                foreach ( $resource_bookings_placed_list_dates as $list_key => $list_value ) {
            
                    $explode_date = explode( '=>', $list_value );
            
                    if ( isset( $explode_date[1]) && $explode_date[1] != '' ) {
                        $date = substr( $explode_date[0], 1, -1 );
                        $resource_date_array[ $date ] = (int)$explode_date[ 1 ];
                    }
                }
            
                $resource_booked_for_date   = 0;
            
                $selected_date              = $value['bkap_booking'][0]['hidden_date'];
            
                if ( array_key_exists( $selected_date, $resource_date_array ) ) {
                    $resource_booked_for_date = $resource_date_array[ $selected_date ];
                }
            
                $bkap_resource_availability = get_post_meta( $resource_id, '_bkap_resource_qty', true );
            
                $resource_booking_available = $bkap_resource_availability - $resource_booked_for_date;
            
                $resource_qty = 0;
            
                foreach ( $woocommerce->cart->cart_contents as $cart_check_key => $cart_check_value ) {
            
                    if( isset( $cart_check_value['bkap_booking'][0]['resource_id'] ) ){
            
                        if( $value['bkap_booking'][0]['resource_id'] == $cart_check_value['bkap_booking'][0]['resource_id'] ) {
            
                            // Calculation for resource qty for product parent foreach product is single day.
                            if( $value['bkap_booking'][0]['hidden_date_checkout'] == "" ){
            
                                $hidden_date_str = $hidden_date_checkout_str = $val_hidden_date_str = "";
            
                                $hidden_date_str = strtotime( $cart_check_value['bkap_booking'][0]['hidden_date'] );
            
                                if( $cart_check_value['bkap_booking'][0]['hidden_date_checkout'] != "" ){
                                    $hidden_date_checkout_str = strtotime( $cart_check_value['bkap_booking'][0]['hidden_date_checkout'] );
                                }
            
                                $val_hidden_date_str = strtotime( $value['bkap_booking'][0]['hidden_date'] );
            
                                if( $hidden_date_checkout_str == "" ){
                                    if( $value['bkap_booking'][0]['hidden_date'] == $cart_check_value['bkap_booking'][0]['hidden_date'] ){
                                        $resource_qty += $cart_check_value['quantity'];
                                    }
                                }else{
                                    if( $val_hidden_date_str >= $hidden_date_str && $val_hidden_date_str < $hidden_date_checkout_str ){
                                        $resource_qty += $cart_check_value['quantity'];
                                    }
                                }
                            }else{ // Calculation for resource qty for product parent foreach product is multiple nights.
            
                                $hidden_date_str = $hidden_date_checkout_str = $cart_check_hidden_date_str = "";
            
                                $hidden_date_str = strtotime( $value['bkap_booking'][0]['hidden_date'] );
            
                                if( $cart_check_value['bkap_booking'][0]['hidden_date_checkout'] != "" ){
                                    $hidden_date_checkout_str = strtotime( $value['bkap_booking'][0]['hidden_date_checkout'] );
                                }
            
                                $cart_check_hidden_date_str    = strtotime( $cart_check_value['bkap_booking'][0]['hidden_date'] );
            
                                if( $cart_check_hidden_date_str >=  $hidden_date_str && $cart_check_hidden_date_str < $hidden_date_checkout_str ){
                                    $resource_qty += $cart_check_value['quantity'];
                                }
                            }
                        }
                    }
                }
            
                if( $resource_qty > $resource_booking_available ) {
            
                    $msg_text = __( get_option( 'book_limited-booking-msg-date' ), 'woocommerce-booking' );
                    $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE' ), array( get_the_title( $resource_id ), $resource_booking_available, $date_to_display ), $msg_text );
                    wc_add_notice( $message, $notice_type = 'error' );
                }
            
                continue;
            }
            
            /* Resource checking end */
            
            if( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
    
                $type_of_slot = apply_filters( 'bkap_slot_type', $duplicate_of );
                
                if( $type_of_slot == 'multiple' ) { 
                    do_action( 'bkap_validate_cart_items', $value );
                } else {
                    
                    if ( isset( $value['bkap_booking'][0]['time_slot'] ) && $value['bkap_booking'][0]['time_slot'] != '' ) {
                        $_POST[ 'time_slot' ] = $value['bkap_booking'][0]['time_slot'];
                        do_action( 'bkap_date_time_cart_validation' );
                        
                        if ( isset( $_POST[ 'validation_status' ] ) ) {
                            $validation_completed = $_POST[ 'validation_status' ];
                        }
                        
                        $qty_check = 0;
                        
                        foreach ( $woocommerce->cart->cart_contents as $cart_check_key => $cart_check_value ) {
                           
                            if( $value['product_id'] == $cart_check_value['product_id']
                                && $value['bkap_booking'][0]['hidden_date'] == $cart_check_value['bkap_booking'][0]['hidden_date']
                                && $value['bkap_booking'][0]['time_slot'] == $cart_check_value['bkap_booking'][0]['time_slot']
                                && $key != $cart_check_key ){
                                 
                                $qty_check = $value['quantity'] + $cart_check_value['quantity'];
                                break;
                            }
                        }
                        
                        if( $qty_check == 0 ){
                            $qty_check = $value['quantity'];
                        }
                   
                        if ( ! isset( $validation_completed ) || ( isset( $validation_completed ) && $validation_completed == 'NO' ) ) {
                        
                            if ( isset( $value['bkap_booking'][0]['time_slot'] ) ) {
                                $time_range = explode( "-", $value['bkap_booking'][0]['time_slot'] );
                                $from_time = date( 'G:i', strtotime( $time_range[0] ) );
                                
                                if( isset( $time_range[1] ) ){
                                    $to_time = date( 'G:i', strtotime( $time_range[1] ) );
                                } else{
                                    $to_time = '';
                                }
                                
                            }else {
                                $to_time      = '';
                                $from_time    = '';
                            }
                            
                            if( $to_time != '' ) {
                                $query    = "SELECT total_booking, available_booking, start_date FROM `".$wpdb->prefix."booking_history`
                                            WHERE post_id = %d
                                            AND start_date = %s
                                            AND from_time = %s
                                            AND to_time = %s
                                    AND status !=  'inactive' ";
                                $results =  $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $date_check, $from_time, $to_time ) );
                            }else {
                                $query    = "SELECT total_booking, available_booking, start_date FROM `".$wpdb->prefix."booking_history`
                                            WHERE post_id = %d
                                            AND start_date = %s
                                            AND from_time = %s
                                    AND status !=  'inactive' ";
                                $results = $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $date_check, $from_time ) );
                            }
                         
                            if ( !$results ) break;
                            else{
                                if ( $value['bkap_booking'][0]['time_slot'] != "" ) {
                                    // if current format is 12 hour format, then convert the times to 24 hour format to check in database
                                    if ( $time_format == '12' ) {
                                        
                                        $time_exploded  = explode( "-", $value['bkap_booking'][0]['time_slot'] );
                                        $from_time      = date( 'h:i A', strtotime( $time_exploded[0] ) );
                                        
                                        if ( isset( $time_range[1] ) ){
                                            $to_time = date( 'h:i A', strtotime( $time_exploded[1] ) );
                                        } else{
                                            $to_time = '';
                                        }
                                        
                                        if ( $to_time != '' ) {
                                            $time_slot_to_display = $from_time.' - '.$to_time;
                                        }else {
                                            $time_slot_to_display = $from_time;
                                        }
                                        
                                    } else {
                                        if ( $to_time != '' ) {
                                            $time_slot_to_display = $from_time.' - '.$to_time;
                                        } else {
                                            $time_slot_to_display = $from_time;
                                        }
                                        
                                    }
                                    
                                    if( $results[0]->available_booking > 0 && $results[0]->available_booking < $qty_check ) {
                                        $msg_text = __( get_option( 'book_limited-booking-msg-time' ), 'woocommerce-booking' );
                                        $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE', 'TIME' ), array( $post_title->post_title, $results[ 0 ]->available_booking, $date_to_display, $time_slot_to_display ), $msg_text );
                                        wc_add_notice( $message, $notice_type = 'error' );
                                    } elseif ( $results[0]->total_booking > 0 && $results[0]->available_booking == 0 ) {
                                        $msg_text = __( get_option( 'book_no-booking-msg-time' ), 'woocommerce-booking' );
                                        $message = str_replace( array( 'PRODUCT_NAME', 'DATE', 'TIME' ), array( $post_title->post_title, $date_to_display, $time_slot_to_display ), $msg_text );
                                        wc_add_notice( $message, $notice_type = 'error' );
                                    }
                                }
                            }
                        }
                    }
                }
            } else if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
            
                $date_cheeckin = '';
                
                if ( isset( $value['bkap_booking'][0]['hidden_date'] ) ) {
                    $date_cheeckin = date( 'd-n-Y', strtotime( $value['bkap_booking'][0]['hidden_date'] ) );
                }
                
                $_POST[ 'booking_date' ] = $date_cheeckin;
                $_POST[ 'booking_checkout' ] = $date_checkout;
                do_action( 'bkap_multiple_days_cart_validation' );
                
                $qty_check = 0;
                foreach ( $woocommerce->cart->cart_contents as $cart_check_key => $cart_check_value ) {
                        
                    if( $value['product_id'] == $cart_check_value['product_id']
                        && $value['bkap_booking'][0]['hidden_date'] == $cart_check_value['bkap_booking'][0]['hidden_date']
                        && $value['bkap_booking'][0]['hidden_date_checkout'] == $cart_check_value['bkap_booking'][0]['hidden_date_checkout']
                        && $key != $cart_check_key ){
                                     
                        $qty_check = $value['quantity'] + $cart_check_value['quantity'];
                        break;
                    }
                }
                
                if( $qty_check == 0 ){
                    $qty_check = $value['quantity'];
                }
                
                if ( isset( $_POST[ 'validation_status' ] ) ) {
                    $validation_completed = $_POST[ 'validation_status' ];
                }
                
                if ( ! isset( $validation_completed ) || ( isset( $validation_completed ) && $validation_completed == 'NO' ) ) {
                    $order_dates = bkap_common::bkap_get_betweendays( $date_cheeckin, $date_checkout );
                    $todays_date = date( 'Y-m-d' );
        
                    $query_date  = "SELECT DATE_FORMAT(start_date,'%d-%c-%Y') as start_date,DATE_FORMAT(end_date,'%d-%c-%Y') as end_date FROM ".$wpdb->prefix."booking_history
                                   WHERE start_date >='".$todays_date."' AND post_id = '".$duplicate_of."'";
                    
                    $results_date = $wpdb->get_results( $query_date );
                    
                    $dates_new    = array();
                        
                    foreach ( $results_date as $k => $v ) {
                        $start_date    = $v->start_date;
                        $end_date      = $v->end_date;
                        $dates         = bkap_common::bkap_get_betweendays( $start_date, $end_date );
                    
                        $dates_new     = array_merge( $dates, $dates_new );
                    }
                    $dates_new_arr     = array_count_values( $dates_new );
                        
                    $lockout           = "";
                    
                    if ( isset( $booking_settings['booking_date_lockout'] ) ) {
                        $lockout = $booking_settings['booking_date_lockout'];
                    }
    
                    $check = 'pass';
                    if ( isset( $lockout ) && $lockout > 0 ) {
                        foreach ( $order_dates as $k => $v ){
                        
                            if ( !isset( $date_availablity[ $v ] ) ) {
                                $date_availablity[ $v ] = $lockout;
                            }
                        
                            if ( array_key_exists( $v, $dates_new_arr ) ) {
                        
                                $date_availablity[ $v ] -= ( $dates_new_arr[$v] + $qty_check );
                                
                                if ( $lockout != 0 && $date_availablity[ $v ] < 0 ){
                                    $date_availablity[ $v ] = 0; // needs to be reset to 0, to ensure negative availability is not displayed to the user
                                    $availability_display[ $v ] = $lockout - $dates_new_arr[ $v ];
                                    $check = 'failed';
                                }
                            }else{
                                $date_availablity[ $v ] -= $qty_check;
                                if ( $lockout != 0 && $date_availablity[ $v ] < 0 ) {
                                    $date_availablity[ $v ] = 0; // needs to be reset to 0, to ensure negative availability is not displayed to the user
                                    $availability_display[ $v ] = $lockout;
                                    $check = 'failed';
                                }
                            }
                        }
                        
                        if ( isset( $check ) && 'failed' == $check ) {
                            if ( is_array( $availability_display ) && count( $availability_display ) > 0 ) {
                                $least_availability = '';
                                // find the least availability
                                foreach ( $availability_display as $date => $available ) {
                                    if ( '' == $least_availability && '0' != $least_availability ) {
                                        $least_availability = $available;
                                    }
                        
                                    if ( $least_availability > $available ) {
                                        $least_availability = $available;
                                    }
                                }
                                // setup the dates to be displayed
                                $date_range = "$date_to_display to $check_out_to_display";
                        
                                $msg_text = __( get_option( 'book_limited-booking-msg-date' ), 'woocommerce-booking' );
                                $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE' ), array( $post_title->post_title, $least_availability, $date_range ), $msg_text );
                                wc_add_notice( $message, $notice_type = 'error' );
                            }
                        
                        }
                    }
                }
            }else {
                do_action( 'bkap_single_days_cart_validation' );
                 
                if ( isset( $_POST[ 'validation_status' ] ) ) {
                    $validation_completed = $_POST[ 'validation_status' ];
                }
                if ( ! isset( $validation_completed ) || ( isset( $validation_completed ) && $validation_completed == 'NO' ) ) {
                    $query  =   "SELECT total_booking,available_booking, start_date FROM `".$wpdb->prefix."booking_history`
                                WHERE post_id = %d
                                AND start_date = %s
                                AND status != 'inactive' ";
                    $results =  $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $date_check ) );
                    
                    $qty_check = 0 ;
                    foreach ( $woocommerce->cart->cart_contents as $cart_check_key => $cart_check_value ) {
                            
                        if( $value['product_id'] == $cart_check_value['product_id']
                            && $value['bkap_booking'][0]['hidden_date'] == $cart_check_value['bkap_booking'][0]['hidden_date']
                            && $value['bkap_booking'][0]['hidden_date_checkout'] == $cart_check_value['bkap_booking'][0]['hidden_date_checkout']
                            && $key != $cart_check_key ){
                                         
                            $qty_check = $value['quantity'] + $cart_check_value['quantity'];
                            break;
                        }
                    }
                    
                    if($qty_check == 0){
                        $qty_check = $value['quantity'];
                    }
        
                    if( !$results ) break;
                    else {


                        if( $results[0]->available_booking > 0 && $results[0]->available_booking < $qty_check ) {
                            $msg_text = __( get_option( 'book_limited-booking-msg-date' ), 'woocommerce-booking' );
                            $message = str_replace( array( 'PRODUCT_NAME', 'AVAILABLE_SPOTS', 'DATE' ), array( $post_title->post_title, $results[ 0 ]->available_booking, $date_to_display ), $msg_text );
                            wc_add_notice( $message, $notice_type = 'error' );
        
                        } elseif ( $results[0]->total_booking > 0 && $results[0]->available_booking == 0 ) {
                            $msg_text = __( get_option( 'book_no-booking-msg-date' ), 'woocommerce-booking' );
                            $message = str_replace( array( 'PRODUCT_NAME', 'DATE' ), array( $post_title->post_title, $date_to_display ), $msg_text );
                            wc_add_notice( $message, $notice_type = 'error' );
        
                        }
                    }
                }
            }
        }
    }
    
    /**
     * This function will remove the product from the cart as per the Advance Booking Period set.
     */
    public static function remove_product_from_cart() {
        global $wpdb;
        
        $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        
        if ( isset( $global_settings->booking_global_holidays ) ) {
            $global_holidays = explode( ',', $global_settings->booking_global_holidays );
        }
         
        // Run only in the Cart or Checkout Page
        if( is_cart() || is_checkout() ) {
             
            foreach( WC()->cart->cart_contents as $prod_in_cart_key => $prod_in_cart_value ) {
                 
                $date_strtotime = $time_strtotime = '';
                 
                // Get the Variation or Product ID
                if( isset( $prod_in_cart_value['product_id'] ) && $prod_in_cart_value['product_id'] != 0 ){
                    $prod_id = $prod_in_cart_value['product_id'];
                }
                
                $duplicate_of     =   bkap_common::bkap_get_product_id( $prod_id );
                $booking_settings =   get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
                 
                if ( isset( $booking_settings['booking_product_holiday'] ) ) {
                    $holiday_array = $booking_settings['booking_product_holiday'];
                }
                
                $holiday_array_keys = array();
                
                if( is_array( $holiday_array ) && count( $holiday_array ) > 0 ){
                    $holiday_array_keys = array_keys( $holiday_array );
                }
                 
                if( isset( $prod_in_cart_value['bkap_booking'] ) && !empty( $prod_in_cart_value['bkap_booking'] ) ){
                    
                    $booking_data = $prod_in_cart_value['bkap_booking'];
    
                    foreach ( $booking_data  as $key => $value ){
    
                        if ( isset( $value[ 'hidden_date' ] ) && $value[ 'hidden_date' ] != "" ) { // can be blanks if the product has been purchased without a date
                            $date = $value['hidden_date'];
                            $date_strtotime = strtotime( $date );
                            
                            // Product is in cart and later store admin set the date as holiday then remove from cart.
                            if ( in_array( $date, $holiday_array_keys ) ) {
                                unset( WC()->cart->cart_contents[$prod_in_cart_key] );
                                continue;
                            }
                            
                            // Product is in cart and later store admin set the date as global holiday then remove from cart.
                            if( is_array( $global_holidays ) && count( $global_holidays ) > 0 ){
                                if ( in_array( $date, $global_holidays ) ) {
                                    unset( WC()->cart->cart_contents[$prod_in_cart_key] );
                                    continue;
                                }
                            }
                            
        
                            // for timeslot
                            if ( isset( $value['time_slot'] ) && $value['time_slot'] != "" ) {
                                $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
                                 
                                if ( isset( $saved_settings ) ){
                                    $time_format = $saved_settings->booking_time_format;
                                }else {
                                    $time_format = "12";
                                }
                                 
                                $time_slot_to_display = $value['time_slot'];
                                 
                                $time_exploded    =   explode( "-", $time_slot_to_display );
                                $from_time        =   strip_tags($time_exploded[0]) ;
                                $time_strtotime   =   strtotime("$date $from_time");
                                 
                                if ( $time_format == '12' ) {
                                    $time_exploded    =   explode( "-", $time_slot_to_display );
                                    $from_time        =   date( 'h:i A', strtotime( strip_tags( $time_exploded[0] ) ) );
                                    $time_strtotime   =   strtotime("$date $from_time");
                                }
                            }
        
                            
                            if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
        
                                $current_time_for_time   =   current_time( 'timestamp' );
                                // Convert the advance period to seconds and add it to the current time
                                $advance_seconds         =   $booking_settings['booking_minimum_number_days'] *60 *60;
                                $cut_off_timestamp       =   $current_time_for_time + $advance_seconds;
                                 
                                $cut_off_date            =   date( "d-m-Y", $cut_off_timestamp );
                                $cut_off_date_strtotime  =   strtotime( $cut_off_date );
        
                            }
                             
                            if( isset( $time_strtotime ) && '' != $time_strtotime ){
                                 
                                if( $time_strtotime < $cut_off_timestamp ){
                                    unset( WC()->cart->cart_contents[$prod_in_cart_key] );
                                }else{
                                    continue;
                                }
                            }else{
                                if( isset( $cut_off_date_strtotime ) && $date_strtotime < $cut_off_date_strtotime ) {
        
                                    unset( WC()->cart->cart_contents[$prod_in_cart_key] );
                                     
                                }
                            }
                        }
                    }
                }
            }
        }
    }


}

?>
