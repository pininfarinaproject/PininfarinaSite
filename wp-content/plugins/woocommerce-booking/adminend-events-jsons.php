<?php

    $url    =   dirname(__FILE__);
    $my_url =   explode('wp-content' , $url);
    $path   =   $my_url[0];

    include_once $path . 'wp-load.php';
    global $wpdb;

    $booking_args = array(
        'posts_per_page'   => -1,
        'offset'           => 0,
        'orderby'          => 'date',
        'order'            => 'DESC',
        'post_type'        => 'bkap_booking',
        'post_status'      => array( 'paid', 'confirmed' ),
        'suppress_filters' => true
    );

    if ( isset( $_GET['vendor_id'] ) ) {
        $booking_args['meta_key']   = '_bkap_vendor_id';
        $booking_args['meta_value'] = $_GET['vendor_id'];
    }

    $bkap_posts_array = get_posts( $booking_args );

    /*$booking_query  =   "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id ORDER BY a2.order_id DESC";
    $results        =   $wpdb->get_results($booking_query);*/
 
    $data = array();

    foreach ( $bkap_posts_array as $key => $value ) {

        $booking = new BKAP_Booking( $value->ID );

        $order = $booking->get_order();
        
        if ( false === $order ) {
            continue;
        }

        $order_status = $order->get_status();
        if( isset( $order_status ) && ( $order_status != 'wc-cancelled' ) && ( $order_status != 'wc-refunded' ) && ( $order_status != 'trash' ) && ( $order_status != '' ) && ( $order_status != 'wc-failed' ) ) {
 	    
	        $product = $booking->get_product();

            if ( false === $product ) {
                continue;
            }

            $product_name = $product_id = "";
            if ( isset( $product ) && $product !== "" && $product !== NULL ) {
                $product_name = html_entity_decode( $product->get_title() , ENT_COMPAT, 'UTF-8' );
                $product_id = $product->get_id();
            }

	        $user      = new WP_User( get_current_user_id() );
	        $add_event = "YES";
            $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
	        if( isset( $user->roles[0] ) && $user->roles[0] == 'tour_operator' ) {
	            
	            $add_event = "NO";
	            if( isset( $booking_settings['booking_tour_operator'] ) &&  $booking_settings['booking_tour_operator'] == get_current_user_id() ){
	                $add_event = "YES";
                }
	        }
            if (isset($add_event) && "YES" == $add_event ){
                
                $resource_title = "";
                if( $booking->get_resource() != "" ){
                    $resource_title = $booking->get_resource_title();
                }

                $value = array(
                    'order_id' => $order->get_id(),
                    'post_id' => $product_id,
                    'start_date' => $booking->get_start_date(),
                    'end_date' => $booking->get_end_date(),
                    'from_time' => $booking->get_start_time(),
                    'to_time' => $booking->get_end_time(),
                    'order_item_id' => $booking->get_item_id(),
                    'resource'      => $resource_title
                );

                if( $booking->get_start_time() != "" && $booking->get_end_time() != "" ){ // this condition is used for adding from and to time slots.
    
                    /*$date_from_time         =  $booking->get_start_date();
        	        $date_from_time        .=  " " . $booking->get_start_time();*/ 
        	        $post_from_timestamp    =  strtotime( $booking->get_start() ); 
        	        $post_from_date         =  date ( 'Y-m-d H:i:s',$post_from_timestamp );
        	         	       
        	        /*$date_to_time           = $booking->get_start_date();
        	        $date_to_time          .= " ".$booking->get_end_time();*/
        	        $post_to_timestamp      = strtotime( $booking->get_end() );
        	        $post_to_date           = date ( 'Y-m-d H:i:s',$post_to_timestamp );
        	        
                    array_push( $data, array(
                        	        'id'       =>  $order->get_id(),
                        	        'title'    =>  $product_name,
                        	        'start'    =>  $post_from_date,
                        	        'end'      =>  $post_to_date,
                        	        'value'    =>  $value
                        	        )
                              );
        	    } else if( $booking->get_start_time() != "" ){ // this condition is used for adding only from time slots.
                    /*$date_from_time      =  $booking->get_start_date();
                    $date_from_time     .=  " " . $booking->get_start_time();*/ 
                    $post_from_timestamp =  strtotime( $booking->get_start() ); 
                    $post_from_date      =  date ( 'Y-m-d H:i:s', $post_from_timestamp );
                    
                    $time                =  strtotime( $booking->get_start() );
                    $endTime             =  date( "H:i", strtotime( '+30 minutes', $time ) );
                    
                    /*$date_to_time        =  $booking->get_start_date();
                    $date_to_time       .=  " " . $endTime;*/
                    $post_to_timestamp   =  strtotime( $endTime );
                    $post_to_date        =  date ( 'Y-m-d H:i:s', $post_to_timestamp );
                    
                    array_push( $data, array(
                                     'id'       =>  $order->get_id(),
                                     'title'    =>  $product_name,
                                     'start'    =>  $post_from_date,
                                     'end'      =>  $post_to_date,
                                     'value'    =>  $value
                                     )
                              );
        	    } else {

                    $start = strtotime( $booking->get_start() );
                    $end   = strtotime( $booking->get_end() );

                    if( isset( $booking_settings['booking_charge_per_day'] ) &&  $booking_settings['booking_charge_per_day'] == 'on' ) {
                        $end += (60 * 60 * 24); 
                    }
                    
            	    array_push( $data, array(
                            	    'id'       =>  $order->get_id(),
                            	    'title'    =>  $product_name,
                            	    'start'    =>  date ( 'Y-m-d', $start ),
                            	    'end'      =>  date ( 'Y-m-d', $end ),
                            	    'value'    =>  $value
                            	    )
            	              );
        	    }
            }
	    }
	}
    echo json_encode( $data );
 
?>