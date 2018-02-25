<?php 
//if(!class_exists('woocommerce_booking')){
//   die();
//}
include_once( 'bkap-common.php' );
class bkap_checkout{

	/**
	 * Hide the hardcoded item meta records frm being 
	 * displayed on the admin orders page
	 */ 
    
	public static function bkap_hidden_order_itemmeta( $arr ){
		$arr[] = '_wapbk_checkout_date';
		$arr[] = '_wapbk_booking_date';
		$arr[] = '_wapbk_time_slot';
		$arr[] = '_wapbk_booking_status';
		$arr[] = '_gcal_event_reference';
        $arr[] = '_wapbk_wpa_prices';       // This item meta is used for calculating addon prices while rescheduling
        $arr[] = '_resource_id';
		
		return $arr;
	}
	
	/**
	 * Updated the availability/bookings left for a product when an order is placed.
	 * 
	 * @param int $order_id
	 * @param int $post_id
	 * @param int $parent_id
	 * @param int $quantity
	 * @param array $booking_data
	 * @return array $details - an array containing the list of products IDs for whom availability was updated.
	 */
	public static function bkap_update_lockout( $order_id, $post_id, $parent_id, $quantity, $booking_data, $called_from = '' ) {
	    global $wpdb;
	    
	    $details = array();
	    
	    if ( isset( $booking_data[ 'hidden_date' ] ) && '' != $booking_data[ 'hidden_date' ] ) {
    	    $hidden_date   =   $booking_data[ 'hidden_date' ];
    	    $date_query    =   date( 'Y-m-d', strtotime( $hidden_date ) );
    	    
    	    $booking_settings   = get_post_meta( $post_id, 'woocommerce_booking_settings', true );
    	    if( array_key_exists( 'hidden_date_checkout', $booking_data ) ) {
    	        $date_checkout        =   $booking_data[ 'hidden_date_checkout' ];
    	        $date_checkout_query  =   date( 'Y-m-d', strtotime( $date_checkout ) );
    	    }
    	    
    	    if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] )&& $booking_settings[ 'booking_enable_multiple_day' ] == 'on' ) {
    	        for ( $i = 0; $i < $quantity; $i++ ) {
    	            $query = "INSERT INTO `" . $wpdb->prefix."booking_history`
    							                     (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                        							 VALUES (
                        							 '".$post_id."',
                        							 '',
                        							 '".$date_query."',
                        							 '".$date_checkout_query."',
                        							 '',
                        							 '',
                        							 '0',
                        							 '0' )";
    	            $wpdb->query( $query );
    	            $new_booking_id  = $wpdb->insert_id;
    	            //Insert records for parent products - Grouped Products
    	            if ( isset( $parent_id ) && $parent_id != '' ) {
    	                $query_parent   =   "INSERT INTO `".$wpdb->prefix."booking_history`
        												(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
        												VALUES (
        												'".$parent_id."',
        												'',
        												'".$date_query."',
        												'".$date_checkout_query."',
        												'',
        												'',
        												'0',
        												'0' )";
    	                $wpdb->query( $query_parent );
    	            }
    	        }
    	        if( $called_from != 'admin' ) {
    	            bkap_checkout::bkap_update_booking_order_history( $order_id, $new_booking_id );
    	        } else {
    	            bkap_checkout::bkap_update_booking_order_history( $order_id, $new_booking_id, 'update' );
    	        }
    	    } else {
    	        if( isset( $booking_data[ 'time_slot' ] ) && $booking_data[ 'time_slot' ] != "" ) {
    	            $time_select              =   $booking_data[ 'time_slot' ];
    	            $time_exploded            =   explode( "-", $time_select );
    	            $query_from_time  =   date( 'G:i', strtotime( $time_exploded[0] ) );
    	            $from_hi = date( 'H:i', strtotime( $query_from_time ) );
    	            
    	            if( isset( $time_exploded[1] ) ){
    	                $query_to_time       = date( 'G:i', strtotime( $time_exploded[1] ) );
    	                $to_hi = date( 'H:i', strtotime( $query_to_time ) );
    	            }
    	            
    	            if( isset( $query_to_time ) && $query_to_time != "" ) {
    	                // same product time slots over lapping
    	                $booking_settings   =   get_post_meta( $post_id, 'woocommerce_booking_settings' , true );
    	                $query              =   "SELECT from_time, to_time  FROM `".$wpdb->prefix."booking_history`
                        									WHERE post_id = '".$post_id."' AND
                        									start_date = '".$date_query."' AND
                        									status !=  'inactive' ";
    	                $get_all_time_slots = $wpdb->get_results( $query );
    	            
    	                // this is possible when we are trying to create an order while importing events by GCal
    	                if ( ! isset( $get_all_time_slots ) || ( isset( $get_all_time_slots ) && count( $get_all_time_slots ) == 0 ) ) {
    	            
    	                    $weekday = date( 'w', strtotime( $date_query ) );
    	                    $weekday = 'booking_weekday_' . $weekday;
    	            
    	                    $base_query = "SELECT * FROM `" . $wpdb->prefix . "booking_history`
    	                                       WHERE post_id = %d
    	                                       AND weekday = %s
    	                                       AND start_date = '0000-00-00'
    	                                       AND status != 'inactive'";
    	            
    	                    $get_base = $wpdb->get_results( $wpdb->prepare( $base_query, $post_id, $weekday ) );
    	            
    	                    foreach ( $get_base as $key => $value ) {
    	                        $insert_records = "INSERT INTO `".$wpdb->prefix."booking_history`
    									       			(post_id,weekday,start_date,from_time,to_time,total_booking,available_booking)
    													VALUES (
    	                                               '" . $post_id . "',
    	                                               '" . $weekday . "',
                                                       '" . $date_query . "',
                                                       '" . $value->from_time ."',
                                                       '" . $value->to_time . "',
                                                       '" . $value->total_booking . "',
                                                       '" . $value->available_booking . "' ) ";
    	            
    	                        $wpdb->query( $insert_records );
    	                        if ( isset( $parent_id ) && $parent_id != '' ) {
    	                            $insert_parent_records = "INSERT INTO `".$wpdb->prefix."booking_history`
    									       			(post_id,weekday,start_date,from_time,to_time,total_booking,available_booking)
    													VALUES (
    	                                               '" . $parent_id . "',
    	                                               '" . $weekday . "',
                                                       '" . $date_query . "',
                                                       '" . $value->from_time ."',
                                                       '" . $value->to_time . "',
                                                       '" . $value->total_booking . "',
                                                       '" . $value->available_booking . "' ) ";
    	            
    	                            $wpdb->query( $insert_parent_records );
    	                        }
    	                    }
    	                }
    	                
    	                foreach( $get_all_time_slots as $time_slot_key => $time_slot_value ){
    	            
    	                    $query_from_time_time_stamp         = strtotime( $query_from_time );
    	                    $query_to_time_time_stamp           = strtotime( $query_to_time );
    	                    $time_slot_value_from_time_stamp    = strtotime( $time_slot_value->from_time );
    	                    $time_slot_value_to_time_stamp      = strtotime( $time_slot_value->to_time );
    	            
    	                    if( $query_to_time_time_stamp > $time_slot_value_from_time_stamp && $query_from_time_time_stamp < $time_slot_value_to_time_stamp ){
    	            
    	                        if ( $time_slot_value_from_time_stamp != $query_from_time_time_stamp || $time_slot_value_to_time_stamp != $query_to_time_time_stamp ) {
    	                            $query  =   "UPDATE `".$wpdb->prefix."booking_history`
                            								SET available_booking = available_booking - ".$quantity."
                            								WHERE post_id = '".$post_id."' AND
                            								start_date = '".$date_query."' AND
                            								from_time = '".$time_slot_value->from_time."' AND
                            								to_time = '".$time_slot_value->to_time."' AND
                            								status != 'inactive' AND
                            								total_booking > 0";
    	                            $wpdb->query( $query );
    	                        }
    	                    }
    	                }
    	            
    	                $query  =   "UPDATE `".$wpdb->prefix."booking_history`
                								SET available_booking = available_booking - ".$quantity."
                								WHERE post_id = '".$post_id."' AND
                								start_date = '".$date_query."' AND
                                                TIME_FORMAT( from_time, '%H:%i' ) = '".$from_hi."' AND
                								TIME_FORMAT( to_time, '%H:%i' ) = '".$to_hi."' AND
                								status != 'inactive' AND
                								total_booking > 0";
    	                $wpdb->query( $query );
    	            
    	                //Update records for parent products - Grouped Products
    	                if ( isset( $parent_id ) && $parent_id != '' ) {
    	                    $query     =   "UPDATE `".$wpdb->prefix."booking_history`
        												SET available_booking = available_booking - ".$quantity."
        												WHERE post_id = '".$parent_id."' AND
        												start_date = '".$date_query."' AND
        												from_time = '".$query_from_time."' AND
        												to_time = '".$query_to_time."' AND
        												status != 'inactive' AND
        												total_booking > 0";
    	                    $wpdb->query( $query );
    	                }
    	                $select         =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                        								WHERE post_id = %d AND
                        								start_date = %s AND
                        								from_time = %s AND
                        								to_time = %s AND
	                                                    status != 'inactive' ";
    	                $select_results =   $wpdb->get_results( $wpdb->prepare( $select, $post_id, $date_query, $query_from_time, $query_to_time ) );
    	                foreach( $select_results as $k => $v ) {
    	                    $details[ $post_id ] = $v;
    	                }
    	            } else {

                        //
    	                $query  =   "UPDATE `".$wpdb->prefix."booking_history`
                								SET available_booking = available_booking - ".$quantity."
                								WHERE post_id = '".$post_id."' AND
                								start_date = '".$date_query."' AND
                								from_time = '".$query_from_time."' AND
                								status != 'inactive' AND
                								total_booking > 0";
    	                $updated = $wpdb->query( $query );
    	                if ( 0 == $updated ) {
    	                    $weekday = date( 'w', strtotime( $date_query ) );
    	                    $weekday = 'booking_weekday_' . $weekday;
    	                    $base_query = "SELECT * FROM `" . $wpdb->prefix . "booking_history`
    	                                       WHERE post_id = %d
    	                                       AND weekday = %s
    	                                       AND start_date = '0000-00-00'
    	                                       AND status != 'inactive'
    	                                       AND total_booking > 0";
    	            
    	                    $get_base = $wpdb->get_results( $wpdb->prepare( $base_query, $post_id, $weekday ) );
    	            
    	                    foreach ( $get_base as $key => $value ) {
    	                        $insert_records = "INSERT INTO `".$wpdb->prefix."booking_history`
    									       			(post_id,weekday,start_date,from_time,total_booking,available_booking)
    													VALUES (
    	                                               '" . $post_id . "',
    	                                               '" . $weekday . "',
                                                       '" . $date_query . "',
                                                       '" . $value->from_time ."',
                                                       '" . $value->total_booking . "',
                                                       '" . $value->available_booking . "' ) ";
    	            
    	                        $wpdb->query( $insert_records );
    	            
    	                        if ( isset( $parent_id ) && $parent_id != '' ) {
    	            
    	                            $insert_parent_records = "INSERT INTO `".$wpdb->prefix."booking_history`
    									       			(post_id,weekday,start_date,from_time,total_booking,available_booking)
    													VALUES (
    	                                               '" . $parent_id . "',
    	                                               '" . $weekday . "',
                                                       '" . $date_query . "',
                                                       '" . $value->from_time ."',
                                                       '" . $value->total_booking . "',
                                                       '" . $value->available_booking . "' ) ";
    	            
    	                            $wpdb->query( $insert_parent_records );
    	            
    	                        }
    	                    }
                            
                            $query  =   "UPDATE `".$wpdb->prefix."booking_history`
                                                SET available_booking = available_booking - ".$quantity."
                                                WHERE post_id = '".$post_id."' AND
                                                start_date = '".$date_query."' AND
                                                TIME_FORMAT( from_time, '%H:%i' ) = '".$from_hi."' AND                                                
                                                status != 'inactive' AND
                                                total_booking > 0";
                            $wpdb->query( $query );
    	                }
    	                
    	                //Update records for parent products - Grouped Products
    	                if ( isset( $parent_id ) && $parent_id != '' ) {
    	                    $query     =   "UPDATE `".$wpdb->prefix."booking_history`
        												SET available_booking = available_booking - ".$quantity."
        												WHERE post_id = '".$parent_id."' AND
        												start_date = '".$date_query."' AND
        												from_time = '".$query_from_time."' AND
        												status != 'inactive' AND
        												total_booking > 0";
    	                    $wpdb->query( $query );
    	                }
    	                $select         =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                        								WHERE post_id =  %d AND
                        								start_date = %s AND
                        								from_time = %s AND
	                                                    status != 'inactive'";
    	                $select_results =   $wpdb->get_results( $wpdb->prepare( $select, $post_id, $date_query, $query_from_time ) );
    	                foreach( $select_results as $k => $v ) {
    	                    $details[$post_id] = $v;
    	                }
    	            }
    	        } else {
    	            $query   =   "UPDATE `".$wpdb->prefix."booking_history`
    										 SET available_booking = available_booking - ".$quantity."
    										 WHERE post_id = '".$post_id."' AND
    										 start_date = '".$date_query."' AND
    										 status != 'inactive' AND
    										 total_booking > 0";
    	            $updated = $wpdb->query( $query );
    	            if ( $updated == 0 ) {
    	            
    	                $weekday = date( 'w', strtotime( $date_query ) );
    	                $weekday = 'booking_weekday_' . $weekday;
    	            
    	                $base_query = "SELECT * FROM `" . $wpdb->prefix . "booking_history`
    	                                       WHERE post_id = %d
    	                                       AND weekday = %s
    	                                       AND start_date = '0000-00-00'
    	                                       AND status != 'inactive'
    	                                       AND total_booking > 0";
    	            
    	                $get_base = $wpdb->get_results( $wpdb->prepare( $base_query, $post_id, $weekday ) );
    
    	                if ( isset( $get_base ) && count( $get_base ) > 0 ) {
        	                foreach ( $get_base as $key => $value ) {
        	                    $new_availability = $value->available_booking - $quantity;
        	                    $insert_records = "INSERT INTO `".$wpdb->prefix."booking_history`
        									       			(post_id,weekday,start_date,total_booking,available_booking)
        													VALUES (
        	                                               '" . $post_id . "',
        	                                               '" . $weekday . "',
                                                           '" . $date_query . "',
                                                           '" . $value->total_booking . "',
                                                           '" . $new_availability . "' ) ";
        	            
        	                    $wpdb->query( $insert_records );
        	            
        	                    if ( isset( $parent_id ) && $parent_id != '' ) {
        	            
        	                        $insert_parent_records = "INSERT INTO `".$wpdb->prefix."booking_history`
        									       			(post_id,weekday,start_date,total_booking,available_booking)
        													VALUES (
        	                                               '" . $parent_id . "',
        	                                               '" . $weekday . "',
                                                           '" . $date_query . "',
                                                           '" . $value->total_booking . "',
                                                           '" . $new_availability . "' ) ";
        	            
        	                        $wpdb->query( $insert_parent_records );
        	            
        	                    }
        	                }
    	                } else {
    	                    // this might happen when gcal is being used and the date has unlimited booking lockout
    	                     
    	                    $unlimited_query = "SELECT * FROM `" . $wpdb->prefix . "booking_history`
                                       WHERE post_id = %d
                                       AND start_date = %s
                                       AND status != 'inactive'
                                       AND total_booking = 0";
    	                     
    	                    $unlimited_results = $wpdb->get_results( $wpdb->prepare( $unlimited_query, $post_id, $date_query ) );
    	                    
    	                    if ( isset( $unlimited_results ) && count( $unlimited_results ) > 0 ) {
    	                    } else {
    	                    
    	                        $base_query = "SELECT * FROM `" . $wpdb->prefix . "booking_history`
    	                                       WHERE post_id = %d
    	                                       AND weekday = %s
    	                                       AND start_date = '0000-00-00'
    	                                       AND status != 'inactive'
    	                                       AND total_booking = 0";
    	                         
    	                        $get_base = $wpdb->get_results( $wpdb->prepare( $base_query, $post_id, $weekday ) );
    	                         
    	                        if ( isset( $get_base ) && count( $get_base ) > 0 ) {
    	                            foreach( $get_base as $base_key => $value ) {
        	                            $insert_records = "INSERT INTO `".$wpdb->prefix."booking_history`
            									       			(post_id,weekday,start_date,total_booking,available_booking)
            													VALUES (
            	                                               '" . $post_id . "',
            	                                               '" . $weekday . "',
                                                               '" . $date_query . "',
                                                               '" . $value->total_booking . "',
                                                               '" . $value->available_booking . "' ) ";
        	                             
        	                            $wpdb->query( $insert_records );
        	                             
        	                            if ( isset( $parent_id ) && $parent_id != '' ) {
        	                                 
        	                                $insert_parent_records = "INSERT INTO `".$wpdb->prefix."booking_history`
            									       			(post_id,weekday,start_date,total_booking,available_booking)
            													VALUES (
            	                                               '" . $parent_id . "',
            	                                               '" . $weekday . "',
                                                               '" . $date_query . "',
                                                               '" . $value->total_booking . "',
                                                               '" . $value->available_booking . "' ) ";
        	                                 
        	                                $wpdb->query( $insert_parent_records );
        	                                 
        	                            }
    	                            }
    	                        }
    	                    }
    	                }
    	            }
    	            //Update records for parent products - Grouped Products
    	            if ( isset( $parent_id ) && $parent_id != '' ) {
    	                $query  =   "UPDATE `".$wpdb->prefix."booking_history`
    											SET available_booking = available_booking - ".$quantity."
    											WHERE post_id = '".$parent_id."' AND
    											start_date = '".$date_query."' AND
    											status != 'inactive' AND
    											total_booking > 0";
    	                $wpdb->query( $query );
    	                $update_one_time_singe_day_parent = 'true';
    	            }
                }
    	    } 
    	    
    	    if ( isset ( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] != 'on'  ) {
    	        if( $called_from != 'admin' ) {
    	            
        	        if ( isset ( $booking_data['time_slot'] ) && $booking_data['time_slot'] != "" && array_key_exists ( 'date', $booking_data ) ) {
        	            if ( isset ( $query_to_time ) && $query_to_time != '' ) {
        	                $order_select_query     =   "SELECT id FROM `".$wpdb->prefix."booking_history`
            														WHERE post_id = %d AND
            														start_date = %s AND
                                                                    TIME_FORMAT( from_time,'%H:%i' ) = %s AND
            														TIME_FORMAT ( to_time, '%H:%i' ) = %s AND
            														status = ''";
        	                $order_results = $wpdb->get_results ( $wpdb->prepare ( $order_select_query, $post_id, $date_query, $from_hi, $to_hi ) );
        	            }  else {
        	                $order_select_query     =   "SELECT id FROM `".$wpdb->prefix."booking_history`
            														WHERE post_id = %d AND
            														start_date = %s AND
            														TIME_FORMAT( from_time,'%H:%i' ) = %s AND
            														status = ''";
        	                $order_results = $wpdb->get_results ( $wpdb->prepare ( $order_select_query, $post_id, $date_query, $from_hi ) );
        	            }
        	        } else {
        	            $order_select_query  =   "SELECT id FROM `".$wpdb->prefix."booking_history`
        													 WHERE post_id = %d AND
        													 start_date = %s AND
        													 status = ''";
        	            $order_results = $wpdb->get_results ( $wpdb->prepare ( $order_select_query, $post_id, $date_query ) );
        	        }
        	        
        	        $j = 0;
        	    
        	        foreach ( $order_results as $k => $v ) {
        	            $booking_id  =   $order_results[$j]->id;
        	            bkap_checkout::bkap_update_booking_order_history( $order_id, $booking_id );
        	            $j++;
        	        }
                }
    	    }

            do_action( 'bkap_update_lockout', $order_id, $post_id, $parent_id, $quantity, $booking_data );
	    }
	    return $details;
	}
	
	public static function bkap_update_booking_order_history( $order_id, $booking_id, $query = 'insert' ) {
	    global $wpdb;
	    
	    if ( isset( $query ) && 'update' == $query ) {
	        $order_query = "UPDATE `" . $wpdb->prefix. "booking_order_history`
	                       SET booking_id = '" . $booking_id . "'
                           WHERE order_id = '" . $order_id . "'";
	    } else {
            $order_query  =   "INSERT INTO `" . $wpdb->prefix . "booking_order_history`
                        					  (order_id,booking_id)
                        					  VALUES (
                        					  '" . $order_id . "',
                        					  '" . $booking_id . "' )";
	    }
	    $wpdb->query( $order_query );
	}

    /**
    * Creates & returns a booking post meta record
    * array to be inserted in post meta.
    * @param int $item_id
    * @param int $product_id
    * @param array $booking_details
    * @since 4.0.0
    */
    static function bkap_create_booking_post( $item_id, $product_id, $qty, $booking_details, $variation_id = 0, $status = 'confirmed' ) {

        global $wpdb;

	    $new_booking_data = array();

	    // Merge booking data
	    $defaults = array(
            'product_id'       => $product_id, // Booking ID
	        'order_item_id'    => $item_id,
	        'start_date'       => '',
	        'end_date'         => '',
	        'resource_id'      => '',
	        'persons'          => array(),
	        'qty'              => $qty,
	        'variation_id'     => $variation_id,
	        'gcal_event_uid'   => false
	    );

	    $new_booking_data = wp_parse_args( $new_booking_data, $defaults );

	    // order ID
	    $query_order_id = "SELECT order_id FROM `". $wpdb->prefix."woocommerce_order_items`
                            WHERE order_item_id = %d";

	    $get_order_id = $wpdb->get_results( $wpdb->prepare( $query_order_id, $item_id ) );
	    
	    $order_id = 0;
	    if ( isset( $get_order_id ) && is_array( $get_order_id ) && count( $get_order_id ) > 0 ) {
	        $order_id = $get_order_id[0]->order_id;
	    }

	    $all_day = 0;
	    if ( isset( $booking_details[ 'hidden_date_checkout' ] ) && '' != $booking_details[ 'hidden_date_checkout' ] ) { // multiple day

	        $start_date = date( 'Ymd', strtotime( $booking_details[ 'hidden_date' ] ) );
	        $end_date = date( 'Ymd', strtotime( $booking_details[ 'hidden_date_checkout' ] ) );

	        // check if rental addon is enabled and per day booking is allowed
	        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );

	        // for now, we default the start and end times to 000000
	        $start_time = '000000';
	        $end_time = '000000';
	        if ( isset( $booking_settings[ 'booking_charge_per_day' ] ) && 'on' == $booking_settings[ 'booking_charge_per_day' ] ) {
	            // if  rental addon flat charge per day is enabled, then the entire checkout date is considered booked, hence 235959
	            $end_time = '235959';
	        }

	        // when checkin and checkout time will be introduced, they will be taken as is set by the user

	    } elseif ( isset( $booking_details[ 'time_slot' ] ) && '' != $booking_details[ 'time_slot' ] ) { // date & time
	        $start_date = date( 'Ymd', strtotime( $booking_details[ 'hidden_date' ] ) );
	        $end_date = date( 'Ymd', strtotime( $booking_details[ 'hidden_date' ] ) );

	        $explode_time = explode( '-', $booking_details[ 'time_slot' ] );

	        $start_time = date( 'His', strtotime( trim( $explode_time[ 0 ] ) ) );

	        if ( isset( $explode_time[ 1 ] ) && '' != $explode_time[ 1 ] ) {
	            $end_time = date( 'His', strtotime( trim( $explode_time[ 1 ] ) ) );
	        } else {
	            $end_time = '000000';
	        }
	    } else { // only day
	        $all_day = 1;
            if ( isset( $booking_details[ 'hidden_date' ] ) ) {
    	        $start_date = date( 'Ymd', strtotime( $booking_details[ 'hidden_date' ] ) );
    	        $end_date = date( 'Ymd', strtotime( $booking_details[ 'hidden_date' ] ) );
    	        $start_time = '000000';
    	        $end_time = '000000';
            }else {
                $start_date = '000000';
                $end_date = '000000';
                $start_time = '000000';
                $end_time = '000000';
            }

	    }
	    
	    $event_uid = '';
	    
	    if( isset( $booking_details[ 'uid' ] ) && '' != $booking_details[ 'uid' ] ) {
	        $event_uid =  $booking_details[ 'uid' ];
	    }
	    
	    $resource_id = '';
	    if( isset( $booking_details[ 'resource_id' ] ) && '' != $booking_details[ 'resource_id' ] ) {
	        $resource_id =  $booking_details[ 'resource_id' ];
	    }
	    
	    $new_booking_data[ 'start_date' ]      = $start_date . $start_time;
	    $new_booking_data[ 'end_date' ]        = $end_date . $end_time;
	    $new_booking_data[ 'cost' ]            = $booking_details[ 'price' ];
	    $new_booking_data[ 'user_id' ]         = get_post_meta( $order_id, '_customer_user', true );
	    $new_booking_data[ 'all_day' ]         = $all_day;
	    $new_booking_data[ 'parent_id' ]       = $order_id; // order ID
	    $new_booking_data[ 'gcal_event_uid' ]  = $event_uid;
	    $new_booking_data[ 'resource_id' ]     = $resource_id;
	    
	    $status = wc_get_order_item_meta( $item_id, '_wapbk_booking_status' );

        // Create it
	    $new_booking = bkap_checkout::get_bkap_booking( $new_booking_data );
	    $new_booking->create( $status );

	    do_action( 'bkap_update_booking_post_meta', $new_booking->id );
	    
	    return $new_booking;

	}

    /**
    * @param int $id
    * @return object BKAP_Booking
    * @since 4.0.0
    */
    static function get_bkap_booking( $id ) {
	    return new BKAP_Booking( $id );
	}
	
	
   /**
	* This function updates the database for the booking 
	* details and adds booking fields on the Order Received page,
	* Woocommerce->Orders when an order is placed for Woocommerce
	* version breater than 2.0.
	*/
	
	public static function bkap_order_item_meta( $item_meta, $cart_item ) {
		if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) < 0 ) {
			return;
		}
		
		// Add the fields
		global $wpdb;	
		global $woocommerce;
		
		$booking_data_present =   "NO";
		
		// check if data is already present for this order in the booking_order_history table
		// if yes, then set variable to ensure we don't re-create records for the plugin tables.
		$check_data           =   "SELECT * FROM `".$wpdb->prefix."booking_order_history`
		                          WHERE order_id = %s";
		$results_check        =   $wpdb->get_results ( $wpdb->prepare ( $check_data, $item_meta ) );
		
		if ( count ( $results_check ) > 0 ) {
			$booking_data_present = "YES";
		}
		
		$order_item_ids   =   array();
		$sub_query        =   "";
		$ticket_content   =   array();
		$i                =   0;
		
		foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			$_product    =   $values['data'];
			$parent_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $_product->get_parent() : bkap_common::bkap_get_parent_id( $values[ 'product_id' ] );
			
			$variation_id = ( array_key_exists( "variation_id", $values ) ) ? $values[ 'variation_id' ] : 0;
			
			if ( isset( $values['bkap_booking'] ) ) {
				$booking = $values['bkap_booking'];
			}
			
			$post_id     =   bkap_common::bkap_get_product_id( $values['product_id'] );
			
			$quantity    =   $values['quantity'];
			
			// to accomodate the change where attribute values are taken as quantity
				
			// Product Attributes - Booking Settings
			$attribute_booking_data = get_post_meta( $post_id, '_bkap_attribute_settings', true );
				
			// attribute values in the order
			$order_attr = $values[ 'variation' ];
				
			if ( is_array( $attribute_booking_data ) && count( $attribute_booking_data ) > 0 ) {
			    $attr_qty = 0;
			    foreach ( $attribute_booking_data as $attr_name => $attr_settings ) {
			        $attr_name = 'attribute_' . $attr_name;
			        // check if the setting is on
			        if ( isset( $attr_settings[ 'booking_lockout_as_value' ] ) && 'on' == $attr_settings[ 'booking_lockout_as_value' ] ) {
			            if ( array_key_exists( $attr_name, $order_attr ) && $order_attr[ $attr_name ]  != 0 ) {
			                $attr_qty += $order_attr[ $attr_name ];
			            }
			        }
			    }
			    if ( isset( $attr_qty ) && $attr_qty > 0 ){
			        $attr_qty = $attr_qty * $values[ 'quantity' ];
			    }
			}
				
			if ( isset( $attr_qty ) && $attr_qty > 0 ){
			    $quantity = $attr_qty;
			}
				
			$post_title  =   ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $_product->get_title() : strip_tags( $_product->get_name() );
			$post_title  =   preg_replace( '/\s\s+/', ' ', $post_title );
			// Fetch line item
			if ( count( $order_item_ids ) > 0 ) {
				$order_item_ids_to_exclude  = implode( ",", $order_item_ids );
				$sub_query                  = " AND order_item_id NOT IN (".$order_item_ids_to_exclude.")";
			}
				
			$query               =   "SELECT order_item_id,order_id FROM `".$wpdb->prefix."woocommerce_order_items`
						              WHERE order_id = %s AND order_item_name LIKE %s".$sub_query;

			$results             =   $wpdb->get_results( $wpdb->prepare( $query, $item_meta, trim($post_title," ") . '%' ) );
		
			$order_item_ids[]    =   $results[0]->order_item_id;
			$order_id            =   $results[0]->order_id;
			$order_obj           =   new WC_order( $order_id );
			$details             =   array();
			$product_ids         =   array();
		
			$order_items = $order_obj->get_items();
				
			$type_of_slot = apply_filters( 'bkap_slot_type', $post_id );
			do_action( 'bkap_update_order', $values,$results[0] );
		
			if( $type_of_slot == 'multiple' ) {
				$hidden_date = $booking[0]['hidden_date'];
				do_action( 'bkap_update_booking_history', $values, $results[0] );
			}
			else {
				if ( isset( $values['bkap_booking'] ) && isset( $values['bkap_booking'][0]['date'] ) ) :
					$booking_settings  =   get_post_meta( $post_id, 'woocommerce_booking_settings', true );
					$details           =   array();
					
					// Add booking data as item meta
					bkap_common::bkap_update_order_item_meta( $results[0]->order_item_id, $post_id, $booking[0], false );
					
                    // Add the booking as a post
					$booking_data = $booking[0];
					$booking_data[ 'gcal_uid' ] = false;
					bkap_checkout::bkap_create_booking_post( $results[0]->order_item_id, $post_id, $quantity, $booking_data, $variation_id );

					// if yes, then execute code above for all items. Below code is not needed as it has already been executed once
					if ( $booking_data_present == "YES" ) {
						continue;
					}
					
					// Update the availability for that product
					$details = bkap_checkout::bkap_update_lockout( $order_id, $post_id, $parent_id, $quantity, $booking[0] );
				endif;
			}
			
			// update the global time slot lockout
			if( isset( $booking[0]['time_slot'] ) && $booking[0]['time_slot'] != "" ) {
			    bkap_checkout::bkap_update_global_lockout( $post_id, $quantity, $details, $booking[0] );
			}
			
			$ticket          =   array( apply_filters( 'bkap_send_ticket', $values,$order_obj ) );
			$ticket_content  =   array_merge( $ticket_content, $ticket );
			$i++;
			
			$start_date_label     = get_option( 'book_item-meta-date' );
			$checkout_date_label  = get_option( 'checkout_item-meta-date' );
			$time_slot_lable      = get_option( 'book_item-meta-time' );
				
			// The below code needs to be run only if WooCommerce verison is > 2.5
			if ( version_compare( WOOCOMMERCE_VERSION, "2.5" ) < 0 ) {
			    continue;
			} else {
    			// Code where the Booking dates and time slots dates are not displayed in the customer new order email from WooCommerce version 2.5
    			$cache_key       = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'item_meta_array_' . $results[ 0 ]->order_item_id;
    			$item_meta_array = wp_cache_get( $cache_key, 'orders' );
    			if ( false !== $item_meta_array ) {
    			    $metadata        = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value, meta_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d AND meta_key IN (%s,%s,%s,%s,%s,%s) ORDER BY meta_id", absint( $results[ 0 ]->order_item_id ), $start_date_label, '_wapbk_booking_date',  $checkout_date_label, '_wapbk_checkout_date', $time_slot_lable, '_wapbk_time_slot'  ) );
    			    foreach ( $metadata as $metadata_row ) {
    			        $item_meta_array[ $metadata_row->meta_id ] = (object) array( 'key' => $metadata_row->meta_key, 'value' => $metadata_row->meta_value );
    			    }
    			    wp_cache_set( $cache_key, $item_meta_array, 'orders' );
    			}
			}
			
		}
	
		do_action( 'bkap_send_email', $ticket_content );
	}	 
	
	public static function bkap_update_global_lockout( $post_id, $quantity, $details, $booking_data ) {
	     
	    global $wpdb;
	     
	    $book_global_settings    = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	    $booking_settings        = get_post_meta( $post_id, 'woocommerce_booking_settings' , true );
	
	    $hidden_date   =   $booking_data['hidden_date'];
	    $date_query    =   date( 'Y-m-d', strtotime( $hidden_date ) );
	    
	    $week_day  =   date( 'l', strtotime( $hidden_date ) );
	    $weekdays  =   bkap_get_book_arrays( 'bkap_weekdays' );
	    $weekday   =   array_search( $week_day, $weekdays );
	
	    if( isset( $booking_settings['booking_time_settings'] ) && isset( $hidden_date ) ){
	         
	        if ( isset( $booking_settings['booking_time_settings'][ $hidden_date ] ) ) $lockout_settings = $booking_settings['booking_time_settings'][ $hidden_date ];
	        else $lockout_settings = array();
	         
	        if ( count( $lockout_settings ) == 0 ){
	            
	
	            if ( isset( $booking_settings['booking_time_settings'][$weekday] ) ) $lockout_settings = $booking_settings['booking_time_settings'][ $weekday ];
	            else $lockout_settings = array();
	        }
	         
	        $from_hours = $from_minute = $to_hours = $to_minute = '';
	         
	        if( isset( $booking_data['time_slot'] ) && $booking_data['time_slot'] != "" ) {
	             
	            $time_select              =   $booking_data['time_slot'];
	            $time_exploded            =   explode( "-", $time_select );
	             
	            $query_from_time  =   date( 'G:i', strtotime( $time_exploded[0] ) );
	            if( isset( $time_exploded[1] ) ){
	                $query_to_time       = date( 'G:i', strtotime( $time_exploded[1] ) );
	            }
	        }
	        if ( isset( $query_from_time ) ) {
	            $from_lockout_time =   explode( ":", $query_from_time );
	            $from_hours        =   $from_lockout_time[0];
	            $from_minute       =   $from_lockout_time[1];
	
	            if ( isset( $query_to_time ) && $query_to_time != '' ) {
	                $to_lockout_time  = explode( ":", $query_to_time );
	                $to_hours         = $to_lockout_time[0];
	                $to_minute        = $to_lockout_time[1];
	            }
	        }
	         
	        foreach ( $lockout_settings as $l_key => $l_value ) {
	
	            if ( $l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute && $l_value['to_slot_hrs'] == $to_hours && $l_value['to_slot_min'] == $to_minute ) {
	                 
	                if ( isset( $l_value['global_time_check'] ) ){
	                    $global_timeslot_lockout = $l_value['global_time_check'];
	                } else{
	                    $global_timeslot_lockout = '';
	                }
	                 
	            }
	        }
	    }
	     
	    if ( isset( $book_global_settings->booking_global_timeslot ) && $book_global_settings->booking_global_timeslot == 'on' || isset( $global_timeslot_lockout ) && $global_timeslot_lockout == 'on' ) {
	        $args       = array( 'post_type' => 'product', 'posts_per_page' => -1 );
	        $product    = query_posts( $args );
	         
	        foreach($product as $k => $v){
	            $product_ids[] = $v->ID;
	        }
	         
	        foreach( $product_ids as $k => $v ){
	            $duplicate_of  = bkap_common::bkap_get_product_id( $v );
	
	            $booking_settings = get_post_meta( $v, 'woocommerce_booking_settings' , true );
	
	            if( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
	                 
	                if( !array_key_exists( $duplicate_of,$details ) ) {
	
	                    foreach( $details as $key => $val ){
	                        $booking_settings   = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
	                         
	                        $start_date         = $val->start_date;
	                        $from_time          = $val->from_time;
	                        $to_time            = $val->to_time;

	                        $from_hi = date( 'H:i', strtotime( $from_time ) );
	                        
	                        if( $to_time != "" ){
	                             
	                            // global time slots over lapping
	                            $get_all_time_slots =   array();
	                             
	                            $insert             =   "NO";
	                             
	                            $query              =   "SELECT *  FROM `".$wpdb->prefix."booking_history`
                        									WHERE post_id = '".$duplicate_of."' AND
                        									start_date = '".$start_date."' AND
                        									status !=  'inactive' ";
	                            $get_all_time_slots =   $wpdb->get_results( $query );
	                             
	                            if( !$get_all_time_slots ){
	                                $insert             =   "YES";
	                                $query              =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                    									        WHERE post_id = %d
                    								            AND weekday = %s
                    								            AND start_date = '0000-00-00'
                        									    AND status !=  'inactive'";
	                                $get_all_time_slots =   $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $weekday ) );
	                            }
	                             
	                            foreach( $get_all_time_slots as $time_slot_key => $time_slot_value){
	                                 
	                                if( "YES" == $insert ){
	                                     
	                                    $query_insert   =   "INSERT INTO `".$wpdb->prefix."booking_history`
																(post_id,weekday,start_date,from_time,to_time,total_booking,available_booking)
																VALUES (
																'".$duplicate_of."',
																'".$weekday."',
																'".$start_date."',
																'".$time_slot_value->from_time."',
																'".$time_slot_value->to_time."',
																'".$time_slot_value->total_booking."',
																'".$time_slot_value->available_booking."' ) ";
	                                     
	                                    $wpdb->query( $query_insert );
	                                     
	                                }
	                                 
	                                $query_from_time_time_stamp         = strtotime( $from_time );
	                                $query_to_time_time_stamp           = strtotime( $to_time );
	                                $time_slot_value_from_time_stamp    = strtotime( $time_slot_value->from_time );
	                                $time_slot_value_to_time_stamp      = strtotime( $time_slot_value->to_time );
	                                 
	                                if( $query_to_time_time_stamp > $time_slot_value_from_time_stamp && $query_from_time_time_stamp < $time_slot_value_to_time_stamp ){
	                                     
	                                    if ( $time_slot_value_from_time_stamp != $query_from_time_time_stamp || $time_slot_value_to_time_stamp != $query_to_time_time_stamp ) {
	                                         
	                                        $query  =   "UPDATE `".$wpdb->prefix."booking_history`
                             								SET available_booking = available_booking - ".$quantity."
                             								WHERE post_id = '".$duplicate_of."' AND
                             								start_date = '".$start_date."' AND
                             								from_time = '".$time_slot_value->from_time."' AND
                             								to_time = '".$time_slot_value->to_time."' AND
                             								status != 'inactive' AND
                             								total_booking > 0";
	                                        $wpdb->query( $query );
	                                    }
	                                }
	                            }

	                            $to_hi = date( 'H:i', strtotime( $to_time ) );
	                            
	                            $check_record = "SELECT id FROM `" . $wpdb->prefix . "booking_history`
	                                               WHERE post_id = %d
	                                               AND start_date = %s
	                                               AND TIME_FORMAT( from_time, '%H:%i' ) = %s
	                                               AND TIME_FORMAT( to_time, '%H:%i' ) = %s";
	                            $get_results = $wpdb->get_col( $wpdb->prepare( $check_record, $duplicate_of, $date_query, $from_hi, $to_hi ) );
	                            
	                            $query      =   "UPDATE `".$wpdb->prefix."booking_history`
                									SET available_booking = available_booking - ".$quantity."
                									WHERE post_id = '".$duplicate_of."' AND
                									start_date = '".$date_query."' AND
                									TIME_FORMAT( from_time, '%H:%i' ) = '".$from_hi."' AND
                									TIME_FORMAT( to_time, '%H:%i' ) = '".$to_hi."' AND
            									    total_booking > 0 AND
                									status != 'inactive' ";
	                            $updated   = $wpdb->query( $query );
	
	                            if( isset( $get_results ) && count( $get_results ) == 0 && $updated == 0 ) {
	                                 
	                                if($val->weekday == '') {
	                                    $week_day    =   date( 'l', strtotime( $date_query ) );
	                                    $weekdays    =   bkap_get_book_arrays( 'bkap_weekdays' );
	                                    $weekday     =   array_search( $week_day, $weekdays );
	                                    //echo $weekday;exit;
	                                } else {
	                                    $weekday = $val->weekday;
	                                }
	                                 
	                                $results  =   array();
	                                $query    =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    												  WHERE post_id = %s
    												  AND weekday = %s
    												  AND start_date = '0000-00-00'
	                                                  AND status !=  'inactive' ";
	                                 
	                                $results =    $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $weekday ) );
	                                 
	                                if ( !$results ) break;
	                                else {
	
	                                    foreach( $results as $r_key => $r_val ) {
	                                         
	                                        if( $from_time == $r_val->from_time && $to_time == $r_val->to_time ) {
	                                            $available_booking =   ( $r_val->total_booking > 0 ) ? $r_val->available_booking - $quantity : $r_val->available_booking;
	                                            $query_insert      =   "INSERT INTO `".$wpdb->prefix."booking_history`
                        													(post_id,weekday,start_date,from_time,to_time,total_booking,available_booking)
                        													VALUES (
                        													'".$duplicate_of."',
                        													'".$weekday."',
                        													'".$start_date."',
                        													'".$r_val->from_time."',
                        													'".$r_val->to_time."',
                        													'".$r_val->total_booking."',
                        													'".$available_booking."' )";
	                                             
	                                            $wpdb->query( $query_insert );
	                                             
	                                        } else {
	                                            $from_lockout_time = explode ( ":", $r_val->from_time );
	
	                                            if ( isset( $from_lockout_time[0] ) ){
	                                                $from_hours = $from_lockout_time[0];
	                                            }else{
	                                                $from_hours= " ";
	                                            }
	
	                                            if ( isset( $from_lockout_time[1] ) ){
	                                                $from_minute = $from_lockout_time[1];
	                                            }else{
	                                                $from_minute = " ";
	                                            }
	                                            // default to blanks
	                                            $to_hours = $to_minute = '';
	
	                                            if ( isset( $r_val->to_time ) && $r_val->to_time != '' ) {
	                                                $to_lockout_time = explode( ":", $r_val->to_time );
	                                                 
	                                                if ( isset ( $to_lockout_time[0] ) ) {
	                                                    $to_hours = $to_lockout_time[0];
	                                                }
	                                                 
	                                                if ( isset ( $to_lockout_time[1] ) ) {
	                                                    $to_minute = $to_lockout_time[1];
	                                                }
	                                            }
	                                            foreach ( $lockout_settings as $l_key => $l_value ) {
	                                                 
	                                                if( $l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute && $l_value['to_slot_hrs'] == $to_hours && $l_value['to_slot_min'] == $to_minute ) {
	                                                    $query_insert    =   "INSERT INTO `".$wpdb->prefix."booking_history`
																				 (post_id,weekday,start_date,from_time,to_time,total_booking,available_booking)
																				 VALUES (
																				 '".$duplicate_of."',
																				 '".$weekday."',
																				 '".$start_date."',
																				 '".$r_val->from_time."',
																				 '".$r_val->to_time."',
																				 '".$r_val->total_booking."',
																				 '".$r_val->available_booking."' )";
	                                                    $wpdb->query( $query_insert );
	                                                }
	                                            }
	                                        }
	                                    }
	                                }
	                            }
	                        }else {
	                            
	                            $check_record = "SELECT id FROM `" . $wpdb->prefix . "booking_history`
	                                               WHERE post_id = %d
	                                               AND start_date = %s
	                                               AND TIME_FORMAT( from_time, '%H:%i' ) = %s
	                                               AND to_time = ''";
	                            $get_results = $wpdb->get_col( $wpdb->prepare( $check_record, $duplicate_of, $date_query, $from_hi ) );
	                             
	                            $query     =   "UPDATE `".$wpdb->prefix."booking_history`
    												SET available_booking = available_booking - ".$quantity."
    												WHERE post_id = '".$duplicate_of."' AND
    												start_date = '".$date_query."' AND
    												TIME_FORMAT( from_time, '%H:%i' ) = '".$from_hi."'
    												AND to_time = ''
												    AND total_booking > 0
    												AND status != 'inactive' ";
	
	                            $updated   =   $wpdb->query( $query );
	
	                            if ( isset( $get_results ) && count( $get_results ) == 0 && $updated == 0 ) {
	                                if ( $val->weekday == '' ) {
	                                    $week_day    =   date( 'l', strtotime( $date_query ) );
	                                    $weekdays    =   bkap_get_book_arrays( 'bkap_weekdays' );
	                                    $weekday     =   array_search( $week_day, $weekdays );
	                                     
	                                } else {
	                                    $weekday = $val->weekday;
	                                }
	                                 
	                                $results  =   array();
	                                $query    =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                									  WHERE post_id = %d
                									  AND weekday = %s
                									  AND start_date = '0000-00-00'
                									  AND to_time = '' 
	                                                  AND status !=  'inactive' ";
	                                $results  =   $wpdb->get_results( $wpdb->prepare( $query, $duplicate_of, $weekday ) );
	                                 
	                                if ( !$results ) break;
	                                else {
	                                    foreach( $results as $r_key => $r_val ) {
	                                         
	                                        if ( $from_time == $r_val->from_time ) {
	                                            $available_booking     =   ( $r_val->total_booking > 0 ) ? $r_val->available_booking - $quantity : $r_val->available_booking;
	                                            $query_insert          =   "INSERT INTO `".$wpdb->prefix."booking_history`
                            													(post_id,weekday,start_date,from_time,total_booking,available_booking)
                            													VALUES (
                            													'".$duplicate_of."',
                            													'".$weekday."',
                            													'".$start_date."',
                            													'".$r_val->from_time."',
                            													'".$r_val->total_booking."',
                            													'".$available_booking."' )";
	                                            $wpdb->query( $query_insert );
	                                        }else {
	                                            $from_lockout_time     = explode( ":", $r_val->from_time );
	                                            $from_hours = 0;
	                                            $from_minute = 0;
	                                            if ( isset( $from_lockout_time[0] ) ) {
	                                               $from_hours            = $from_lockout_time[0];
	                                            }
	                                            if ( isset( $from_lockout_time[1] ) ) {
	                                               $from_minute           = $from_lockout_time[1];
	                                            }
	
	                                            foreach( $lockout_settings as $l_key => $l_value ) {
	                                                 
	                                                if( $l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute ) {
	                                                    $query_insert    =   "INSERT INTO `".$wpdb->prefix."booking_history`
                    															(post_id,weekday,start_date,from_time,total_booking,available_booking)
                    															VALUES (
                    															'".$duplicate_of."',
                    															'".$weekday."',
                    															'".$start_date."',
                    															'".$r_val->from_time."',
                    															'".$r_val->available_booking."',
                    															'".$r_val->available_booking."' )";
	                                                    $wpdb->query( $query_insert );
	                                                }
	                                            }
	                                        }
	                                    }
	                                }
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