<?php 

if( ! class_exists( 'BKAP_Vendors' ) ) {
    class BKAP_Vendors {
        
        public function __construct() {
            // Add Vendor ID as booking post meta
            add_action( 'bkap_update_booking_post_meta', array( &$this, 'bkap_update_vendor_id' ), 10, 1 );
            
        }
        
        /**
         * Adds the vendor ID as booking post meta
         */
        function bkap_update_vendor_id( $booking_id ) {
            
            // Booking object
            $booking = new BKAP_booking( $booking_id );
            
            // Product ID
            $product_id = $booking->get_product_id();
            
            // get the post record
            $post = get_post( $product_id );
            
            // get the post author
            $vendor_id = $post->post_author;
            
            update_post_meta( $booking_id, '_bkap_vendor_id', $vendor_id );
        } 
        
        /**
         * Return the count of bookings present for the given vendor
         */
        public static function get_bookings_count( $user_id ) {
            
            $args = array(
                'post_type'         => 'bkap_booking',
	            'numberposts'    => -1,
	            'post_status'      => array( 'all' ),
                'meta_key'          => '_bkap_vendor_id',
                'meta_value'        => $user_id,
            );
            
            $posts_count = count( get_posts( $args ) );
            
            return $posts_count;
            
        }
        
        /**
         * Calculate the number of pages
         */
        function get_number_of_pages( $user_id, $per_page ) {
            
            $total_count = $this->get_bookings_count( $user_id );
            
            $number_of_pages = 0;
            if( $total_count > 0 ) {
                $number_of_pages = ceil( $total_count / $per_page );
            }
            
            return $number_of_pages;
            
        }
        
        /**
         * Return the booking posts for a given vendor per page
         */
        public static function get_booking_data( $user_id, $start, $limit ) {
            
            $args = array(
                'post_type'         => 'bkap_booking',
                'numberposts'       => $limit,
                'post_status'       => array( 'all' ),
                'meta_key'          => '_bkap_vendor_id',
                'meta_value'        => $user_id,
                'paged'             => $start,
            );
            
            $posts_data = get_posts( $args );
            
            $bookings_data = array();
            
            foreach( $posts_data as $k => $value ) {
                
                // Booking ID
                $booking_id = $value->ID;
          //      $bookings_data[ 'id' ] = $booking_id;
                
                // Booking Object
                $booking = new BKAP_Booking( $booking_id );
                
                // Booking Status
                $bookings_data[ $booking_id ][ 'status' ] = $value->post_status;
                
                // Product Booked
                $product = $booking->get_product();
                 
                if ( $product ) {
                    $bookings_data[ $booking_id ][ 'product_id' ] = $product->get_id();
                    $bookings_data[ $booking_id ][ 'product_name' ] = $product->get_title();
                    $bookings_data[ $booking_id ][ 'variation_id' ] = $booking->get_variation_id();
                } else {
                    $bookings_data[ $booking_id ][ 'product_id' ] = '-';
                    $bookings_data[ $booking_id ][ 'product_name' ] = '-';
                    $bookings_data[ $booking_id ][ 'variation_id' ] = '-';
                }
                
                // Qty
                $bookings_data[ $booking_id ][ 'qty' ] = $booking->get_quantity();
                 
                // Customer Name
                $customer = $booking->get_customer();
                 
                if ( $customer->email && $customer->name ) {
                    $bookings_data[ $booking_id ][ 'customer_name' ] = esc_html( $customer->name );
                } else {
                    $bookings_data[ $booking_id ][ 'customer_name' ] = '-';
                }
                 
                // Booking Start Date & Time
                $bookings_data[ $booking_id ][ 'start' ] = $booking->get_start_date() . "<br>" . $booking->get_start_time();
                $bookings_data[ $booking_id ][ 'hidden_start' ] = date( 'd-m-Y', strtotime( $booking->get_start()) );
                
                // Booking End Date & Time
                $bookings_data[ $booking_id ][ 'end' ] = $booking->get_end_date() . "<br>" . $booking->get_end_time();
                $bookings_data[ $booking_id ][ 'hidden_end' ] = date( 'd-m-Y', strtotime( $booking->get_end()) );
                
                // Booking Time
                $time_start = $booking->get_start_time();
                $time_end = $booking->get_end_time();
                if ( $time_start !== '' ) {
                    $bookings_data[ $booking_id ][ 'time_slot' ] = "$time_start - $time_end";
                }
                
                // Order ID & Status
                $order = $booking->get_order();
                if ( $order ) {
                    $bookings_data[ $booking_id ][ 'order_id'] = is_callable( array( $order, 'get_id' ) ) ? $order->get_id() : $order->id;
                    $bookings_data[ $booking_id ][ 'order_status' ] = esc_html( wc_get_order_status_name( $order->get_status() ) );
                    $bookings_data[ $booking_id ][ 'order_item_id' ] = $booking->get_item_id();
                } else {
                    $bookings_data[ $booking_id ][ 'order_id'] = 0;
                    $bookings_data[ $booking_id ][ 'order_status' ] = '-';
                    $bookings_data[ $booking_id ][ 'order_item_id' ] = 0;
                }
                
                // Order Date
                if( $bookings_data[ $booking_id ][ 'order_id' ] > 0 ) {
                    $bookings_data[ $booking_id ][ 'order_date' ] = $booking->get_date_created();
                } else {
                    $bookings_data[ $booking_id ][ 'order_date' ] = '-';
                }
                
                // Amount
                $amount = $booking->get_cost();
                $final_amt = $amount * $booking->get_quantity();
                $order_id = $booking->get_order_id();
                 
                if ( absint( $order_id ) > 0 && false !== get_post_status( $order_id ) ) {
                    $the_order          = wc_get_order( $order_id );
                    $currency           = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $the_order->get_order_currency() : $the_order->get_currency();
                } else {
                    // get default woocommerce currency
                    $currency = get_woocommerce_currency();
                }
                $currency_symbol    = get_woocommerce_currency_symbol( $currency );
                 
                $bookings_data[ $booking_id ][ 'amount' ] = wc_price( $final_amt, array( 'currency' => $currency) );
                
            }
            
            return $bookings_data;
        }
              
    } // end of class
    $bkap_vendors = new BKAP_Vendors();
} 