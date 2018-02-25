<?php
/* if(!class_exists('woocommerce_booking')){
    die();
}*/

include_once( 'bkap-common.php' );
include_once( 'lang.php' );

class bkap_cancel_order{

	/**********************************************************************
	 * This function will add cancel order button on the “MY ACCOUNT”  page. 
     * For cancelling the order.
	**********************************************************************/
			
	public static function bkap_get_add_cancel_button( $order, $action ){
		$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
		
		if ( $myaccount_page_id ) {
			$myaccount_page_url = get_permalink( $myaccount_page_id );
		}
			
		$action_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $action->id : $action->get_id();
		$action_status = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $action->status : $action->get_status();
		
		if ( isset( $_GET['order_id'] ) &&  $_GET['order_id'] == $action_id && $_GET['cancel_order'] == "yes" ) {
			$order_obj = new WC_Order( $action_id );
			$order_obj->update_status( "cancelled" );
			print('<script type="text/javascript">
				    location.href="'.$myaccount_page_url.'";
				   </script>');
		}
		
		if ( $action_status != "cancelled" && $action_status != "completed" && $action_status != "refunded") {
			$order['cancel'] = array( "url"   => apply_filters( 'woocommerce_get_cancel_order_url', add_query_arg( 'order_id', $action_id )."&cancel_order=yes"),
				                      "name"  => __( "Cancel", "woocommerce-booking" ) );
		}
		
		return $order;
	}
	
	/**
	 * This function frees up the booking dates and/or time
	 * for all the items in an order when the order is trashed
	 * without cancelling or refunding it.
	 * 
	 * @param int $post_id
	 */
	public static function bkap_trash_order( $post_id ) {
	    $post_obj = get_post( $post_id );
	     
	    // array of all the  order statuses for which the bookings do not need to be freed up
	    $status = array( 'wc-cancelled', 'wc-refunded' );
	     
	    if ( 'shop_order' == $post_obj->post_type && ( !in_array( $post_obj->post_status, $status ) ) ) {
	        
	        // trash the booking posts as well
	        $order    = new WC_Order( $post_id );
	        foreach ( $order->get_items() as $order_item_id => $item ) {
	            if ( 'line_item' == $item['type'] ) {
	                // get the booking ID for each item
	                $booking_id = bkap_common::get_booking_id( $order_item_id );
	                wp_trash_post( $booking_id );
	            }
	        }
	         
	        bkap_cancel_order::bkap_woocommerce_cancel_order( $post_id );
	    }
	    
	    if ( 'bkap_booking' == $post_obj->post_type ) {
	        $booking_id = $post_obj->ID;
	        woocommerce_booking::bkap_delete_booking( $booking_id );
	    }
	}
	
	public static function bkap_untrash_order( $post_id ) {
	    $post_obj = get_post( $post_id );
	    
	    if ( 'shop_order' == $post_obj->post_type && ( 'wc-cancelled' != $post_obj->post_status || 'wc-refunded' != $post_obj->post_status ) ) {
	        
	        // untrash the booking posts as well
	        $order    = new WC_Order( $post_id );
	        foreach ( $order->get_items() as $order_item_id => $item ) {
	            if ( 'line_item' == $item['type'] ) {
	                // get the booking ID for each item
	                $booking_id = bkap_common::get_booking_id( $order_item_id );
	                wp_untrash_post( $booking_id );
	            }
	        }
	         
	        bkap_cancel_order::bkap_woocommerce_restore_bookings( $post_id, 'trashed', $post_obj->post_status );
	    }
	}
	
	public static function bkap_woocommerce_restore_bookings( $order_id, $old_status, $new_status ) {
	    if( ( $old_status == 'cancelled' && ( $new_status != 'cancelled' || $new_status != 'refunded' ) ) || ( $old_status == 'refunded' && ( $new_status != 'cancelled' || $new_status != 'refunded' ) ) 
	        || ( $old_status == 'trashed' && ( $new_status != 'cancelled' || $new_status != 'refunded' ) ) ) {
	        global $wpdb, $post;
	        $order_obj    =   new WC_order( $order_id );
	        $order_items  =   $order_obj->get_items();
	        foreach( $order_items as $item_key => $item_value ) {
                $product_id      =   bkap_common::bkap_get_product_id( $item_value[ 'product_id' ] );
                $booking_data = array();
                if( isset( $item_value[ get_option( 'book_item-meta-date' ) ] ) ) {
                    $booking_data[ 'date' ] = $item_value[ get_option( 'book_item-meta-date' ) ];
                }
                 
                if( isset( $item_value[ 'wapbk_booking_date' ] ) ) {
                    $booking_data[ 'hidden_date' ] = $item_value[ 'wapbk_booking_date' ];
                }
                
                if( isset( $item_value[ 'wapbk_checkout_date' ] ) ) {
                    $booking_data[ 'hidden_date_checkout' ] = $item_value[ 'wapbk_checkout_date' ];
                }
                
                if( isset( $item_value[ get_option( 'book_item-meta-time' ) ] ) ) {
                    $booking_data[ 'time_slot' ] = $item_value[ get_option( 'book_item-meta-time' ) ];
                }
                
                $_product  =   wc_get_product( $product_id );
                $parent_id = 0;
                if ( is_bool( $_product ) === false ) {
                	$parent_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $_product->get_parent() : bkap_common::bkap_get_parent_id( $product_id );
            	}
                
                $details = bkap_checkout::bkap_update_lockout(  $order_id, $product_id, $parent_id, $item_value[ 'qty' ], $booking_data, 'admin' );
                // update the global time slot lockout
                if( isset( $item_value[ get_option( 'book_item-meta-time' ) ] ) && $item_value[ get_option( 'book_item-meta-time' ) ] != "" ) {
                    bkap_checkout::bkap_update_global_lockout( $product_id, $item_value[ 'qty' ], $details, $booking_data );
                }
                
                // get booking post ID
                $booking_post = bkap_common::get_booking_id( $item_key );
                // update the booking post status
                if ( $booking_post ) {
                    $new_booking = bkap_checkout::get_bkap_booking( $booking_post );
                    $status = wc_get_order_item_meta( $item_key, '_wapbk_booking_status' );
                    $new_booking->update_status( $status );
                }
                
                // if automated sync is enabled, add the event back to the calendar
                require_once plugin_dir_path( __FILE__ ) . '/includes/class.gcal.php';
                // add event in GCal if sync is set to autmated
                $gcal = new BKAP_Gcal();
                 
                $user_id = get_current_user_id();
                if( $gcal->get_api_mode( $user_id, $item_value[ 'product_id' ] ) == "directly" ) {
                    $event_details = bkap_cancel_order::bkap_create_gcal_object( $order_id, $item_value, $order_obj );
                
                    // if sync is disabled at the product level, set post_id to 0 to ensure admin settings are taken into consideration
                    $booking_settings = get_post_meta( $item_value[ 'product_id' ], 'woocommerce_booking_settings', true );
                    $post_id = $item_value[ 'product_id' ];
                    if ( ( ! isset( $booking_settings[ 'product_sync_integration_mode' ] ) ) || ( isset( $booking_settings[ 'product_sync_integration_mode' ] ) && 'disabled' == $booking_settings[ 'product_sync_integration_mode' ] ) ) {
                        $post_id = 0;
                    }
                
                    $gcal->insert_event( $event_details, $item_key, $user_id, $post_id, false );
                
                    // add an order note, mentioning an event has been created for the item
                    $post_title = $event_details[ 'product_name' ];
                    $order_note = __( "Booking_details for $post_title have been exported to the Google Calendar", 'woocommerce-booking' );
                    $order_obj->add_order_note( $order_note );
                
                }
	        }
	    }
	}
	
	/**
	 * This function creates an event details array
	 * which contains all the details required to insert
	 * an event in Google Calendar
	 * @param int $order_id
	 * @param array $item_details
	 * @param object $order
	 * @return array $event_details
	 * @since 3.5.2
	 */
	function bkap_create_gcal_object( $order_id, $item_details, $order ) {
	     
	    $valid_date = false;
	    if ( isset( $item_details[ 'wapbk_booking_date' ] ) ) {
	        $valid_date = bkap_common::bkap_check_date_set( $item_details[ 'wapbk_booking_date' ] );
	    }
	
	    if ( $valid_date ) {
	        $event_details = array();
	
	        $event_details[ 'hidden_booking_date' ] = $item_details[ 'wapbk_booking_date' ];
	
	        if ( isset( $item_details[ 'wapbk_checkout_date' ] ) && $item_details[ 'wapbk_checkout_date' ] != '' ) {
	            $event_details[ 'hidden_checkout_date' ] = $item_details[ 'wapbk_checkout_date' ];
	        }
	
	        if ( isset( $item_details[ 'wapbk_time_slot' ] ) && $item_details[ 'wapbk_time_slot' ] != '' ) {
	            $event_details[ 'time_slot' ] = $item_details[ 'wapbk_time_slot' ];
	        }
	
	        $event_details[ 'billing_email' ] = $order->get_billing_email();
	        $event_details[ 'billing_first_name' ] = $order->get_billing_first_name();
	        $event_details[ 'billing_last_name' ] = $order->get_billing_last_name();
	        $event_details[ 'billing_address_1' ] = $order->get_billing_address_1();
	        $event_details[ 'billing_address_2' ] = $order->get_billing_address_2();
	        $event_details[ 'billing_city' ] = $order->get_billing_city();
	
	        $event_details[ 'billing_phone' ] = $order->get_billing_phone();
	        $event_details[ 'order_comments' ] = $order->get_customer_note();
	        $event_details[ 'order_id' ] = $order_id;
	
	        $shipping_first_name = $order->get_shipping_first_name();
	        if ( isset( $shipping_first_name ) && $shipping_first_name != '' ) {
	            $event_details[ 'shipping_first_name' ] = $shipping_first_name;
	        }
	         
	        $shipping_last_name = $order->get_shipping_last_name();
	        if ( isset( $shipping_last_name ) && $shipping_last_name != '' ) {
	            $event_details[ 'shipping_last_name' ] = $shipping_last_name;
	        }
	         
	        $shipping_address_1 = $order->get_shipping_address_1();
	        if( isset( $shipping_address_1 ) && $shipping_address_1 != '' ) {
	            $event_details[ 'shipping_address_1' ] = $shipping_address_1;
	        }
	         
	        $shipping_address_2 = $order->get_shipping_address_2();
	        if ( isset( $shipping_address_2 ) && $shipping_address_2 != '' ) {
	            $event_details[ 'shipping_address_2' ] = $shipping_address_2;
	        }
	         
	        $shipping_city = $order->get_shipping_city();
	        if ( isset( $shipping_city ) && $shipping_city != '' ) {
	            $event_details[ 'shipping_city' ] = $shipping_city;
	        }
	         
	        $_product = wc_get_product( $item_details[ 'product_id' ] );
	
	        $post_title = $_product->get_title();
	        $event_details[ 'product_name' ] = $post_title;
	        $event_details[ 'product_qty' ] = $item_details[ 'qty' ];
	
	        $event_details[ 'product_total' ] = $item_details[ 'line_total' ];
	
	        return $event_details;
	    }
	}
	
	/*************************************************************
     * This function deletes booking for the products in order 
     * when the order is cancelled or refunded.
     ************************************************************/
	
	public static function bkap_woocommerce_cancel_order( $order_id ) {
		global $wpdb,$post;
		
		$array        =   array();
		$order_obj    =   new WC_order( $order_id );
		$order_items  =   $order_obj->get_items();
		$select_query =   "SELECT booking_id FROM `".$wpdb->prefix."booking_order_history`
						  WHERE order_id= %d";
		$results      =   $wpdb->get_results ( $wpdb->prepare( $select_query, $order_id ) );
		$item_booking_id = 0;
		$booking_details = array();
		
        foreach ( $results as $key => $value ) {
		    
		    $select_query_post   =   "SELECT post_id,start_date, end_date, from_time, to_time FROM `".$wpdb->prefix."booking_history`
								     WHERE id= %d";
		    	
		    $results_post      =   $wpdb->get_results( $wpdb->prepare( $select_query_post, $value->booking_id ) );
		    
		    $booking_info = array( 'post_id' => $results_post[0]->post_id,
		                           'start_date' => $results_post[0]->start_date,
		                           'end_date' => $results_post[0]->end_date,
		                           'from_time' => $results_post[0]->from_time,
		                           'to_time' => $results_post[0]->to_time 
		                      );
		    $booking_details[ $value->booking_id ] = $booking_info;
		    $item_booking_id = $value->booking_id;
		}
		
		$i = 0;
		
		foreach( $order_items as $item_key => $item_value ) {

			if ( $item_value['_bkap_resch_rem_bal_order_id'] !== '' && $item_value['_bkap_resch_rem_bal_order_id'] !== null ){

				$related_order = wc_get_order( $item_value['_bkap_resch_rem_bal_order_id'] );

		        if ( $order_obj->has_status( 'cancelled' ) ) {
		        	$related_order->update_status( 'cancelled', 'Parent Order Cancelled.', false );
		        }else if ( $order_obj->has_status( 'refunded' ) ) {
		        	$related_order->update_status( 'refunded', 'Parent Order Refunded.', false );
		        }else if ( $order_obj->has_status( 'failed' ) ) {
		        	$related_order->update_status( 'failed', 'Parent Order Failed.', false );
		        }
			}

		    // check the booking status, if the status is cancelled, do not re-allot the item as that has already been done
		    $_status = $item_value[ 'wapbk_booking_status' ];
		    if ( ( isset( $_status ) && $_status != 'cancelled' ) || ! isset( $_status ) ) {
		        $cancelled = false;
		        // find the correct booking ID from the results array and pass the same
		        foreach ( $booking_details as $booking_id => $booking_data ) {
		            if ( $item_value[ 'product_id' ] == $booking_data['post_id'] ) {
		                // cross check the date and time as well as the product can be added to the cart more than once with different booking details
		                if ( $item_value[ 'wapbk_booking_date' ] == $booking_data[ 'start_date' ] ) {
		        
                            if ( isset( $booking_data[ 'to_time' ] ) && '' != $booking_data[ 'to_time' ] ) {
                                $time = date( 'G:i', strtotime( $booking_data[ 'from_time' ] ) ) . ' - ' . date( 'G:i', strtotime( $booking_data[ 'to_time' ] ) );
		                    } else {
		                        $time = date( 'G:i', strtotime( $booking_data[ 'from_time' ] ) );
                            }
		                    
		                    if ( isset( $item_value[ 'wapbk_checkout_date' ] ) ) {
		                        if ( $item_value[ 'wapbk_checkout_date' ] == $booking_data[ 'end_date' ] ) {
    		                        $item_booking_id = $booking_id;
    		                        break;
		                        }
		                    } else if( isset( $item_value[ 'wapbk_time_slot' ] ) ) {
		                        if ( strpos( $item_value[ 'wapbk_time_slot' ] , ',' ) > 0 ) {
		                            $time_slot_list = explode( ',', $item_value[ 'wapbk_time_slot' ] );
		                            foreach( $time_slot_list as $t_key => $t_value ) {
		                                if ( $time == $t_value ) {
		                                  $item_booking_id = $booking_id;
		                                  self::bkap_reallot_item( $item_value, $item_booking_id, $order_id );
		                                  $cancelled = true;
		                                  // gcal sync for multiple time slots should be added here
		                                }
		                            }
		                            
		                        } else if ( $item_value[ 'wapbk_time_slot'] == $time ) {
    		                        $item_booking_id = $booking_id;
    		                        break;
		                        }
		                    } else {
		                        $item_booking_id = $booking_id;
		                        break;
		                    }
		                }
		            }
		        }
		        
		        if ( ! $cancelled ) {
		            
		            // update the booking post status
		            $booking_post = bkap_common::get_booking_id( $item_key );
		            // update the booking post status
		            if ( $booking_post ) {
		                $new_booking = bkap_checkout::get_bkap_booking( $booking_post );
		                $new_booking->update_status( 'cancelled' );

		            }
		            
		            if ( isset ( $new_booking ) && isset( $new_booking->qty ) ) {
		                $bkap_qty = $new_booking->qty;
		            }
		            
		            if ( isset( $bkap_qty ) ) {
		                self::bkap_reallot_item( $item_value, $item_booking_id, $order_id, $bkap_qty );
		            } else {
		                self::bkap_reallot_item( $item_value, $item_booking_id, $order_id );
		            }
		            
    		        $i++;
    		        $product_id      =   bkap_common::bkap_get_product_id( $item_value['product_id'] );
    		     
    		        // check GCal sync
    		        // user ID
    		        $user = get_user_by( 'email', get_option( 'admin_email' ) );
    		        $user_id = 0;
    		        if ( isset( $user->ID ) ) {
    		            $user_id = $user->ID;
    		        } else {
    		            // get the list of administrators
    		            $args = array( 'role' => 'administrator', 'fields' => array( 'ID' ) );
    		            $users = get_users( $args );
    		            if ( isset( $users ) && count( $users ) > 0 ) {
    		                $user_id = $users[ 0 ]->ID;
    		            }
    		        }
    
    		        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
    		        
    		        if ( isset( $booking_settings[ 'product_sync_integration_mode' ] ) && 'directly' == $booking_settings[ 'product_sync_integration_mode' ] ) {
    		            $gcal_product_id = $product_id;
    		        } else {
    		            $gcal_product_id = 0;
    		        }
    		        
    		        // check if tour operators are allowed to setup GCal
    		        if ( 'yes' == get_option( 'bkap_allow_tour_operator_gcal_api' ) ) {
    		            // if tour operator addon is active, pass the tour operator user Id else the admin ID
    		            if ( function_exists( 'is_bkap_tours_active' ) && is_bkap_tours_active() ) {
    		                if( isset( $booking_settings[ 'booking_tour_operator' ] ) &&  $booking_settings[ 'booking_tour_operator' ] != 0 ) {
    		                    $user_id = $booking_settings[ 'booking_tour_operator' ];
    		                }
    		            }
    		        }
    		        
    		        // get the mode for the product settings as well if applicable
    		        $gcal = new BKAP_Gcal();
    		        if( $gcal->get_api_mode( $user_id, $gcal_product_id ) == "directly" ) {
    		            $gcal->delete_event( $item_key, $user_id, $gcal_product_id );
    		        }
		        }
		    }
		}
	}
	
	/**
	 * Re-allots the booking date and/or time for each item in the order
	 * 
	 * @param array $item_value
	 * @param int $booking_id
	 * @param int $order_id
	 * @param int $bkap_qty
	 */
	public static function bkap_reallot_item( $item_value, $booking_id, $order_id, $bkap_qty = null ) {
	    global $wpdb;
	    global $post;
	     
	    $product_id      =   bkap_common::bkap_get_product_id( $item_value['product_id'] );
	     
	    $_product        =   wc_get_product( $product_id );
	    $parent_id       = 0;
	    /**
	     * It will confirm that we have the product object. 
	     * If it is boolean then we will not fetch the parent id.
	     */
	    if ( is_bool( $_product ) === false ) {
	    	$parent_id       = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $_product->get_parent() : bkap_common::bkap_get_parent_id( $product_id );
		}
	
	    $details = array();
	    
	    $variation_id = '';
	    if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) >= 0 ) {
	        $variation_id = $item_value->get_variation_id();
	    } else if ( array_key_exists( "variation_id", $item_value ) ) {
	        $variation_id = $item_value['variation_id'];
	    }
	
        $booking_settings   =   get_post_meta( $product_id, 'woocommerce_booking_settings', true );
        
        if ( $bkap_qty != null && $bkap_qty >= 0 ) {
            $qty = $bkap_qty;
        } else if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) >= 0 ) {
            $qty = $item_value->get_quantity();
        } else {
            $qty                =   $item_value['qty'];
        }
	         
        if ( isset( $variation_id ) && $variation_id != 0 ) {
            // Product Attributes - Booking Settings
            $attribute_booking_data = get_post_meta( $product_id, '_bkap_attribute_settings', true );
        
            if ( is_array( $attribute_booking_data ) && count( $attribute_booking_data ) > 0 ) {
                $attr_qty = 0;
                
                if ( $bkap_qty != null && $bkap_qty >= 0 ) {
                    $attr_qty = $bkap_qty;
                } else {
                
                    foreach ( $attribute_booking_data as $attr_name => $attr_settings ) {
                     
                    // check if the setting is on
                        if ( isset( $attr_settings[ 'booking_lockout_as_value' ] ) && 'on' == $attr_settings[ 'booking_lockout_as_value' ] ) {
                            
                            if ( array_key_exists( $attr_name, $item_value ) && $item_value[ $attr_name ]  != 0 ) {
                            $attr_qty += $item_value[ $attr_name ];
                            }
                        }
                   }
                }
                if ( isset( $attr_qty ) && $attr_qty > 0 ){
                    $attr_qty = $attr_qty * $item_value['qty'];
                }
            }
        }
	         
        if ( isset( $attr_qty ) && $attr_qty > 0 ){
            $qty = $attr_qty;
        }
         
        $from_time  =   '';
        $to_time    =   '';
        $date_date  =   '';
        $end_date   =   '';
       	$start_date =   '';  
        if( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
             
            if ( isset( $parent_id ) && $parent_id != '' ) {
                 
                // double the qty as we need to delete records for the child product as well as the parent product
                $qty               +=   $qty;
                $booking_id        +=   1;
                $first_record_id    =   $booking_id - $qty;
                $first_record_id   +=   1;
                $select_data_query  =   "DELETE FROM `".$wpdb->prefix."booking_history`
												WHERE ID BETWEEN %d AND %d";
                $results_data       =   $wpdb->query( $wpdb->prepare( $select_data_query, $first_record_id, $booking_id ) );
            }

            // if parent ID is not found, means its a normal product
            else {
                // DELETE the records using the ID in the booking history table.
                // The ID in the order history table, is the last record inserted for the order, so find the first ID by subtracting the qty
                $first_record_id    =   $booking_id - $qty;
                 
                $first_record_id   +=   1;
                 
                $select_data_query  =   "DELETE FROM `".$wpdb->prefix."booking_history`
											WHERE ID BETWEEN %d AND %d";
                $results_data       =   $wpdb->query( $wpdb->prepare( $select_data_query, $first_record_id, $booking_id ) );
                 
                 
            }

        } else if( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
            $type_of_slot = apply_filters( 'bkap_slot_type', $product_id );

            if( $type_of_slot == 'multiple' ) {
                do_action( 'bkap_order_status_cancelled', $order_id, $item_value, $booking_id );
            }else {
                $select_data_query  =   "SELECT * FROM `".$wpdb->prefix."booking_history`
									        WHERE id= %d";
                $results_data       =   $wpdb->get_results ( $wpdb->prepare( $select_data_query, $booking_id ) );
                $j                  =   0;
 
                foreach( $results_data as $k => $v ){
                    $start_date    =   $results_data[ $j ]->start_date;
                    $from_time     =   $results_data[ $j ]->from_time;
                    $to_time       =   $results_data[ $j ]->to_time;

                    if ( $from_time != '' && $to_time != '' || $from_time != '' ){
                        $parent_query = "";
                        if($to_time != ''){
                             
                             
                            //over lapaing time slots free booking product level
                            $query = "SELECT from_time, to_time, available_booking  FROM `".$wpdb->prefix."booking_history`
						                   WHERE post_id = '".$product_id."' AND
						                   start_date = '".$start_date."' AND
						                   status != 'inactive' ";
                            $get_all_time_slots = $wpdb->get_results( $query );

                            foreach( $get_all_time_slots as $time_slot_key => $time_slot_value){
                                 
                                $query_from_time_time_stamp = strtotime($from_time);
                                $query_to_time_time_stamp = strtotime($to_time);
                                $time_slot_value_from_time_stamp = strtotime($time_slot_value->from_time);
                                $time_slot_value_to_time_stamp = strtotime($time_slot_value->to_time);

                                $revised_available_booking = $time_slot_value->available_booking + $qty;
                                 
                                if( $query_to_time_time_stamp > $time_slot_value_from_time_stamp && $query_from_time_time_stamp < $time_slot_value_to_time_stamp ){

                                    if ( $time_slot_value_from_time_stamp != $query_from_time_time_stamp || $time_slot_value_to_time_stamp != $query_to_time_time_stamp ) {
                                        $query = "UPDATE `".$wpdb->prefix."booking_history`
    								                SET available_booking = ".$revised_available_booking."
    								                WHERE post_id = '".$product_id."' AND
    								                start_date = '".$start_date."' AND
    								                from_time = '".$time_slot_value->from_time."' AND
    								                to_time = '".$time_slot_value->to_time."' AND
    								                status != 'inactive' AND
    								                total_booking > 0";

                                        $wpdb->query( $query );
                                    }
                                }
                            }
                            $query = "UPDATE `".$wpdb->prefix."booking_history`
											SET available_booking = available_booking + ".$qty."
											WHERE
											id = '".$booking_id."' AND
    										start_date = '".$start_date."' AND
    										from_time = '".$from_time."' AND
    										to_time = '".$to_time."' AND
    										status != 'inactive' AND
    									    total_booking > 0";
                            //Update records for parent products - Grouped Products
                            if ( isset( $parent_id ) && $parent_id != '' ) {
                                $parent_query   =   "UPDATE `".$wpdb->prefix."booking_history`
												         SET available_booking = available_booking + ".$qty."
												         WHERE
												         post_id = '".$parent_id."' AND
												         start_date = '".$start_date."' AND
												         from_time = '".$from_time."' AND
												         to_time = '".$to_time."' AND
												         status != 'inactive' AND 
											             total_booking > 0";
                                
                                $wpdb->query( $parent_query );
                                
                                $select         =   "SELECT * FROM `".$wpdb->prefix."booking_history`
														WHERE post_id = %d AND
														start_date = %s AND
														from_time = %s AND
														to_time = %s AND
                                                        status != 'inactive'";
														
                                $select_results =   $wpdb->get_results( $wpdb->prepare( $select, $parent_id, $start_date, $from_time, $to_time ) );
                                 
                                foreach( $select_results as $k => $v ) {
                                    $details[ $product_id ] = $v;
                                }
                            }

                            $select          =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    												 WHERE post_id = %d AND
    												 start_date = %s AND
    												 from_time = %s AND
    												 to_time = %s AND
                                                     status != 'inactive' ";
                            $select_results  =   $wpdb->get_results( $wpdb->prepare( $select, $product_id, $start_date, $from_time, $to_time ) );

                            foreach( $select_results as $k => $v ) {
                                $details[ $product_id ] = $v;
                            }

                        } else {
                            $query   =   "UPDATE `".$wpdb->prefix."booking_history`
											  SET available_booking = available_booking + ".$qty."
											  WHERE
											  id = '".$booking_id."' AND
											  start_date = '".$start_date."' AND
											  from_time = '".$from_time."' AND
											  status != 'inactive' AND 
										      total_booking > 0";
                             
                            //Update records for parent products - Grouped Products
                            if ( isset( $parent_id ) && $parent_id != '' ) {
                                $parent_query   =   "UPDATE `".$wpdb->prefix."booking_history`
														SET available_booking = available_booking + ".$qty."
														WHERE
														post_id = '".$parent_id."' AND
														start_date = '".$start_date."' AND
														from_time = '".$from_time."' AND
														status != 'inactive' AND 
													    total_booking > 0";
                                
                                $wpdb->query( $parent_query );
                                
                                $select         =   "SELECT * FROM `".$wpdb->prefix."booking_history`
														WHERE post_id = %d AND
														start_date = %s AND
														from_time = %s AND
                                                        status != 'inactive' ";
                                $select_results =   $wpdb->get_results( $wpdb->prepare( $select, $parent_id, $start_date, $from_time ) );
                                 
                                foreach( $select_results as $k => $v ) {
                                    $details[$product_id] = $v;
                                }
                            }

                            $select          =   "SELECT * FROM `".$wpdb->prefix."booking_history`
													 WHERE post_id = %d AND
													 start_date = %s AND
													 from_time = %s AND 
                                                     status != 'inactive' ";
                            $select_results  =   $wpdb->get_results( $wpdb->prepare($select,$product_id,$start_date,$from_time) );

                            foreach( $select_results as $k => $v ) {
                                $details[ $product_id ] = $v;
                            }
                        }
                        // Run the Update query for the product
                        $wpdb->query( $query );
                    }
                    $j++;
                }
                self::reallot_global_timeslot( $start_date, $from_time, $to_time, $booking_settings, $details, $qty );
            }
        } else {
            $select_data_query   =   "SELECT * FROM `".$wpdb->prefix."booking_history`
								         WHERE id= %d";
            $results_data        =   $wpdb->get_results ( $wpdb->prepare( $select_data_query, $booking_id ) );
            $j                   =   0;

            foreach( $results_data as $k => $v ) {
                $start_date     =   $results_data[$j]->start_date;
                $from_time      =   $results_data[$j]->from_time;
                $to_time        =   $results_data[$j]->to_time;
                $query          =   "UPDATE `".$wpdb->prefix."booking_history`
										SET available_booking = available_booking + ".$qty."
										WHERE
										id = '".$booking_id."' AND
										start_date = '".$start_date."' AND
										from_time = '' AND
										to_time = '' AND
										status != 'inactive' AND 
									    total_booking > 0";
                $wpdb->query( $query );
                 
                //Update records for parent products - Grouped Products
                if ( isset( $parent_id ) && $parent_id != '' ) {
                    $parent_query  =   "UPDATE `".$wpdb->prefix."booking_history`
									        SET available_booking = available_booking + ".$qty."
											WHERE
											post_id = '".$parent_id."' AND
											start_date = '".$start_date."' AND
											from_time = '' AND
											to_time = '' AND
											status != 'inactive' AND 
										    total_booking > 0";
                    $wpdb->query( $parent_query );
                }
            }
            $j++;
        }
     
    }

    public static function reallot_global_timeslot( $start_date, $from_time, $to_time, $booking_settings, $details, $qty ) {
        global $wpdb;
        
	    $book_global_settings    =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	    $global_timeslot_lockout =   '';
	    $label                   =   get_option( "book_item-meta-date" );
	    $hidden_date             =   '';
	
	    if ( isset( $start_date ) && $start_date != '' ) {
	        $hidden_date = date( 'd-n-Y', strtotime( $start_date ) );
	    }
	
	    if ( isset( $booking_settings['booking_time_settings'][ $hidden_date ] ) ){
	        $lockout_settings = $booking_settings['booking_time_settings'][ $hidden_date ];
	    } else {
	        $lockout_settings = array();
	    }
	     
	    if(count($lockout_settings) == 0){
	        $week_day = date('l',strtotime($hidden_date));
	        $weekdays = bkap_get_book_arrays('bkap_weekdays');
	        $weekday = array_search($week_day,$weekdays);
	        if (isset($booking_settings['booking_time_settings'][$weekday])){
	            $lockout_settings = $booking_settings['booking_time_settings'][$weekday];
	        }
	        else {
	            $lockout_settings = array();
	        }
	    }
	     
	    if(count($lockout_settings) > 0) {
	        $week_day = date('l',strtotime($hidden_date));
	        $weekdays = bkap_get_book_arrays('bkap_weekdays');
	        $weekday = array_search($week_day,$weekdays);
	        if (isset($booking_settings['booking_time_settings'][$weekday])){
	            $lockout_settings = $booking_settings['booking_time_settings'][$weekday];
	        } else {
	            $lockout_settings = array();
	        }
	         
	    }
	
	    $from_lockout_time = explode(":",$from_time);
	    if( isset( $from_lockout_time[0] ) ){
	        $from_hours = $from_lockout_time[0];
	    } else {
	        $from_hours = '';
	    }
	     
	    if( isset( $from_lockout_time[1] ) ){
	        $from_minute = $from_lockout_time[1];
	    } else {
	        $from_minute = '';
	    }
	     
	    if( $to_time != '' ) {
	        $to_lockout_time    =   explode( ":", $to_time );
	        $to_hours           =   $to_lockout_time[0];
	        $to_minute          =   $to_lockout_time[1];
	    } else {
	        $to_hours           =   '';
	        $to_minute          =   '';
	    }
	
	    if( count( $lockout_settings ) > 0 ) {
	         
	        foreach( $lockout_settings as $l_key => $l_value ) {
	
	            if( $l_value['from_slot_hrs'] == $from_hours && $l_value['from_slot_min'] == $from_minute && $l_value['to_slot_hrs'] == $to_hours && $l_value['to_slot_min'] == $to_minute ) {
	                 
	                if ( isset($l_value['global_time_check'] ) ){
	                    $global_timeslot_lockout = $l_value['global_time_check'];
	                }else{
	                    $global_timeslot_lockout = '';
	                }
	                 
	            }
	        }
	    }
	
	    if(isset($book_global_settings->booking_global_timeslot) && $book_global_settings->booking_global_timeslot == 'on' || isset($global_timeslot_lockout) && $global_timeslot_lockout == 'on') {
	         
	        $args = array( 'post_type' => 'product', 'posts_per_page' => -1 );
	        $product = query_posts( $args );
	        foreach($product as $k => $v) {
	            $product_ids[] = $v->ID;
	        }
	         
	        foreach( $product_ids as $k => $v ) {
	
	            $duplicate_of      =   bkap_common::bkap_get_product_id( $v );
	
	            $booking_settings  =   get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
	
	            if ( isset( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
	                 
	                if ( isset( $details ) && count( $details ) > 0 ) {
	
	                    if ( !array_key_exists( $duplicate_of, $details ) ) {
	                         
	                        foreach( $details as $key => $val ) {
	                            $start_date    =   $val->start_date;
	                            $from_time     =   $val->from_time;
	                            $to_time       =   $val->to_time;
	                            $revised_available_booking = '';
	                            if($to_time != "") {
	
	                                //over lapaing time slots free booking product level
	                                $query = "SELECT from_time, to_time, available_booking  FROM `".$wpdb->prefix."booking_history`
									                   WHERE post_id = '".$duplicate_of."' AND
									                   start_date = '".$start_date."' AND
									                   status !=  'inactive' ";
	                                $get_all_time_slots = $wpdb->get_results( $query );
	                                 
	                                foreach( $get_all_time_slots as $time_slot_key => $time_slot_value){
	
	                                    $query_from_time_time_stamp = strtotime($from_time);
	                                    $query_to_time_time_stamp = strtotime($to_time);
	                                    $time_slot_value_from_time_stamp = strtotime($time_slot_value->from_time);
	                                    $time_slot_value_to_time_stamp = strtotime($time_slot_value->to_time);
	
	                                    if( $query_to_time_time_stamp > $time_slot_value_from_time_stamp && $query_from_time_time_stamp < $time_slot_value_to_time_stamp ){
	                                         
	                                        if ( $time_slot_value_from_time_stamp != $query_from_time_time_stamp || $time_slot_value_to_time_stamp != $query_to_time_time_stamp ) {
	                                            $query = "UPDATE `".$wpdb->prefix."booking_history`
                								                SET available_booking = available_booking + ".$qty."
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
	
	                                $query = "UPDATE `".$wpdb->prefix."booking_history`
												SET available_booking = available_booking + ".$qty."
												WHERE post_id = '".$duplicate_of."' AND
												start_date = '".$start_date."' AND
												from_time = '".$from_time."' AND
												to_time = '".$to_time."' AND
												status != 'inactive' AND 
											    total_booking > 0";
	                                $wpdb->query($query);
	                            } else {
	                                $query    =   "UPDATE `".$wpdb->prefix."booking_history`
    												  SET available_booking = available_booking + ".$qty."
    												  WHERE post_id = '".$duplicate_of."' AND
    												  start_date = '".$start_date."' AND
    												  from_time = '".$from_time."' AND
    												  status != 'inactive' AND 
												      total_booking > 0";
	                                $wpdb->query( $query );
	                            }
	                        }
	                    }
	                }
	            }
	        }
	    }
	}
	
	/**
	 * This will reallocate the bookings when order status changed from failed to processing,completed and on-hold.
	 *
	 * @param array $args this is the order id.
	 * @version 4.2
	 */
		
	public static function bkap_reallocate_booking_when_order_status_failed_to_processing( $args = array() ) {
	     
	    global $woocommerce;
	     
	    $order   = wc_get_order( $args );
	    $details = array();
	     
	    foreach ( $order->get_items() as $order_item_id => $item ) {
	
	        $booking          = array();
	        $product_bookable = "";
	        $parent_id        = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $_product->get_parent() : bkap_common::bkap_get_parent_id( $item[ 'product_id' ] );
	        $post_id          = bkap_common::bkap_get_product_id( $item['product_id'] );
	        $quantity         = $item['qty'];
	        $product_bookable = bkap_common::bkap_get_bookable_status( $post_id );
	
	        if( $product_bookable ){
	
	            $booking = array( 'date'                   => $item['_wapbk_booking_date'],
	                'hidden_date'            => date("d-m-Y", strtotime($item['_wapbk_booking_date'] ) ),
	                'date_checkout'          => $item['wapbk_checkout_date'],
	                'hidden_date_checkout'   => date("d-m-Y", strtotime($item['_wapbk_checkout_date'] ) ),
	                'price'                  => $item['cost'],
	                'time_slot'              => $item['_wapbk_time_slot']
	            );
	             
	            $details = bkap_checkout::bkap_update_lockout( $order_id, $post_id, $parent_id, $quantity, $booking );
	            // update the global time slot lockout
	            if( isset( $booking['time_slot'] ) && $booking['time_slot'] != "" ) {
	                bkap_checkout::bkap_update_global_lockout( $post_id, $quantity, $details, $booking);
	            }
	        }
	
	    }
	}
}
?>