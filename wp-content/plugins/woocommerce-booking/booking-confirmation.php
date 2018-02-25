<?php
if ( ! class_exists( 'bkap_booking_confirmation' ) ) {
    class bkap_booking_confirmation {
    
        public function __construct() {
            
            // Add a function to include required files
            //add_action( 'init', array( &$this, 'bkap_includes' ), 99 );
            
            // add checkbox in admin
            add_action( 'bkap_after_purchase_wo_date', array( &$this, 'confirmation_checkbox' ), 10, 1 );
            
            // save the checkbox value in post meta record
            add_filter( 'bkap_save_product_settings', array( &$this, 'save_product_settings' ), 10, 2 );
            
            // change the button text
            add_filter( 'woocommerce_product_single_add_to_cart_text', array( &$this, 'change_button_text' ),10, 1 );
            
            // add to cart validations
            
            // Check if Cart contains any product that requires confirmation
            add_filter( 'woocommerce_cart_needs_payment', array( &$this, 'bkap_cart_requires_confirmation' ), 10, 2 );
            
            // change the payment gateway at Checkout
            add_filter( 'woocommerce_available_payment_gateways', array( &$this, 'bkap_remove_payment_methods' ), 10, 1 );
            
            // Prevent pending being cancelled
            add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'bkap_prevent_cancel' ), 10, 2 );
            
            // Control the my orders actions.
            add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'bkap_my_orders_actions' ), 10, 2 );
            
            // Add the View Bookings link in Woo->Orders edit orders page
            add_action( 'woocommerce_admin_order_item_headers', array( $this, 'bkap_link_header' ) );
            add_action( 'woocommerce_admin_order_item_values', array( $this, 'bkap_link' ), 10, 3 );
            
            // Re-direct to the View Booking page
//            add_action( 'admin_init', array( &$this, 'load_view_booking_page' ) );
            
            // Ajax calls
            add_action( 'admin_init', array( &$this, 'bkap_confirmations_ajax' ) );
            
            // Cart Validations
            add_filter( 'bkap_validate_cart_products', array( &$this, 'bkap_validate_conflicting_products' ), 10, 2 );
            
            // Once the payment is completed, order status changes to any of the below, fire these hooks to ensure the booking status is also updated.
            
            add_action( 'woocommerce_order_status_processing' , array( &$this, 'bkap_update_booking_status' ), 10, 1 );
            add_action( 'woocommerce_order_status_on-hold' , array( &$this, 'bkap_update_booking_status' ), 10, 1 );
            add_action( 'woocommerce_order_status_completed' , array( &$this, 'bkap_update_booking_status' ), 10, 1 );
            	
            // Remove the booking from the order when it's cancelled
            // Happens only if the booking requires confirmation and the order contains multiple bookings
            // which require confirmation
            add_action( 'bkap_booking_pending-confirmation_to_cancelled', array( $this, 'bkap_remove_cancelled_booking' ) );
            
            
        }
        
        /**
         * File Includes
         */
        /*function bkap_includes() {
            include( 'class-bkap-gateway.php' );
            include( 'class-approve-booking.php' );
            
        }*/
        
        /**
         * Ajax Calls
         */
        function bkap_confirmations_ajax() {
            // only logged in users can access the admin side and approve bookings
//            add_action( 'wp_ajax_bkap_save_booking_status', array( &$this, 'bkap_save_booking_status' ) );
        }
        
        /**
         * Add a Requires Confirmation checkbox in the Booking meta box
         * 
         * @param int $product_id
         */
        function confirmation_checkbox( $product_id ) {
            
            $booking_settings = bkap_get_post_meta( $product_id );
            ?>
            <div id="requires_confirmation_section" class="booking_options-flex-main">
                        
                <div class="booking_options-flex-child">
                    <label for="bkap_requires_confirmation"><?php _e( 'Requires Confirmation?', 'woocommerce-booking' ); ?></label>
                </div>
                                
                <?php 
                    $date_show = '';
                    if( isset( $booking_settings[ 'booking_confirmation' ] ) && 'on' == $booking_settings[ 'booking_confirmation' ] ) {
                        $requires_confirmation = 'checked';
                    } else {
                        $requires_confirmation = '';
                    }
                ?>
                <div class="booking_options-flex-child">
                    <label class="bkap_switch">
                        <input type="checkbox" name="bkap_requires_confirmation" id="bkap_requires_confirmation" <?php echo $requires_confirmation; ?>>
                        <div class="bkap_slider round"></div>
                    </label>
                </div>
                
                <div class="booking_options-flex-child bkap_help_class">
                    <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Enable this setting if the booking requires admin approval/confirmation. Payment will not be taken at Checkout', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                </div>
                
            </div>
            
            <script type="text/javascript">
            jQuery(document).ready(function(){
                // when the page loads
                if ( jQuery( "#bkap_requires_confirmation" ).attr( 'checked' ) ) {
                	jQuery( '#booking_purchase_without_date' ).prop( 'disabled', true );
                } else {
                	jQuery( '#booking_purchase_without_date' ).prop( 'disabled', false );
                }
                // when the checkbox is checked/unchecked
                jQuery( "#bkap_requires_confirmation" ).change( function () {
                    if ( jQuery( "#bkap_requires_confirmation" ).attr( 'checked' ) ) {
                    	jQuery( "#booking_purchase_without_date" ).attr( 'checked', false );
                    	jQuery( '#booking_purchase_without_date' ).prop( 'disabled', true );
                    } else {
                    	jQuery( '#booking_purchase_without_date' ).prop( 'disabled', false );
                    }
                });
            });
            </script>
            <?php 
        }
        
        /**
         * Save the Requires Confirmation setting in Booking meta box
         * 
         * @param array $booking_settings
         * @param int $product_id
         * @return array $booking_settings
         */
        function save_product_settings( $booking_settings, $product_id ) {
            
            $booking_settings[ 'booking_confirmation' ] = '';
            
            if( isset( $_POST[ 'bkap_requires_confirmation' ] ) ) {
                $booking_settings[ 'booking_confirmation' ] = $_POST[ 'bkap_requires_confirmation' ];
            }
            
            return $booking_settings;
        }
        
        /**
         * Modify the Add to cart button text for products that require confirmations
         * 
         */
        function change_button_text( $var ) {
            global $post;
            // Product ID
            $product_id = $post->ID;
            
            $requires_confirmation = bkap_common::bkap_product_requires_confirmation( $product_id );
            
            if( $requires_confirmation ) {
                 $bkap_check_availability_text = get_option( 'bkap_check_availability' );

                if( $bkap_check_availability_text == "" ) {
        			return __( 'Check Availability', 'woocommerce-booking' );
        		}else{
        			return __( $bkap_check_availability_text, 'woocommerce-booking' );
        		}

            } else {

                $bkap_add_to_cart_text = get_option( 'bkap_add_to_cart' );

                if( $bkap_add_to_cart_text == "" ){
        			return $var;
        		}else{
        			return __( $bkap_add_to_cart_text, 'woocommerce-booking' );
        		}
                
            }
            
        }
        
        /**
         * Return true if the cart contains a product that requires confirmation
         * 
         * @param int $needs_payment
         * @param object $cart
         * @return boolean
         */
        function bkap_cart_requires_confirmation( $needs_payment, $cart ) {
            
            if ( ! $needs_payment ) {
                foreach ( $cart->cart_contents as $cart_item ) {
                    $requires_confirmation = bkap_common::bkap_product_requires_confirmation( $cart_item['product_id'] );
                    
                    if( $requires_confirmation ) {
                        $needs_payment = true;
                        break;
                    }
                }
            }
            
            return $needs_payment;
            
        }  

        /**
         * Modify Payment Gateways
         * 
         * Remove the existing payment gateways and add the Bookign payment gateway
         * when the Cart contains a product that requires confirmation.
         * 
         * @param array $available_gateways
         * @return multitype:BKAP_Payment_Gateway
         */
        function bkap_remove_payment_methods( $available_gateways ) {
        
            $cart_requires_confirmation = bkap_common::bkap_cart_requires_confirmation();
            
            if ( $cart_requires_confirmation ) {
                unset( $available_gateways );
        
                $available_gateways = array();
                $available_gateways['bkap-booking-gateway'] = new BKAP_Payment_Gateway();
            }
        
            return $available_gateways;
        }

        /**
         * Prevent Order Cancellation
         * 
         * Prevent WooCommerce from cancelling an order if the order contains
         * an item that is awaiting booking confirmation.
         * 
         * @param boolean $return
         * @param object $order
         * @return boolean|unknown
         */
        function bkap_prevent_cancel( $return, $order ) {
            if ( '1' === get_post_meta( $order->get_id(), '_bkap_pending_confirmation', true ) ) {
                return false;
            }
        
            return $return;
        }
        
        /**
         * Hide the Pay button in My Accounts
         * 
         * Hide the Pay button in My Accounts fr orders that contain
         * an item that's still awaiting booking confirmation.
         * 
         * @param array $actions
         * @param object $order
         * @return array $actions
         */
        function bkap_my_orders_actions( $actions, $order ) {
            global $wpdb;
        
            $order_payment_method = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->payment_method : $order->get_payment_method();
            if ( $order->has_status( 'pending' ) && 'bkap-booking-gateway' === $order_payment_method ) {
                
                $status = array();
                foreach ( $order->get_items() as $order_item_id => $item ) {
                    if ( 'line_item' == $item['type'] ) {
                        
                        $_status = $item[ 'wapbk_booking_status' ];
             			$status[] = $_status;
                 
                    }
                }
        
    			if ( in_array( 'pending-confirmation', $status ) && isset( $actions['pay'] ) ) {
    				unset( $actions['pay'] );
    			} else if ( in_array( 'cancelled', $status ) && isset( $actions['pay'] ) || count( $status ) == 0 ) {
    			    unset( $actions['pay'] );
    			}
    		}
        
    		return $actions;
    	}
    	
    	/**
    	 * Create a column in WooCommerce->Orders 
    	 * Edit Orders page for each item
    	 */
        function bkap_link_header() {
	       ?><th class="bkap_edit_header">&nbsp;</th><?php
		}
		
		/**
		 * Display View Booking Link
		 * 
		 *  Add the View Booking Link for a given item in 
		 *  WooCommerce->orders Edit Orders
		 *  
		 * @param object $_product
		 * @param array $item
		 * @param int $item_id
		 */
		function bkap_link( $_product, $item, $item_id ) {
		    
		    global $wpdb;
		    
		    if ( isset( $_product ) && !empty( $_product ) ) {
    		    $product_id = $_product->get_id();

    		    // get the booking settings for the product
    		    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
    		    
    		    // order ID
    		    $query_order_id = "SELECT order_id FROM `". $wpdb->prefix."woocommerce_order_items`
                            WHERE order_item_id = %d";
    		    $get_order_id = $wpdb->get_results( $wpdb->prepare( $query_order_id, $item_id ) );
    		    
    		    $order_id = 0;
    		    if ( isset( $get_order_id ) && is_array( $get_order_id ) && count( $get_order_id ) > 0 ) {
    		        $order_id = $get_order_id[0]->order_id;
    		    }
    		    
    		    // get booking posts for the order
    		    $query_posts = "SELECT ID FROM `" . $wpdb->prefix . "posts`
                                WHERE post_type = %s
                                AND post_parent = %d";
    		    
    		    $get_posts = $wpdb->get_results( $wpdb->prepare( $query_posts, 'bkap_booking', $order_id ) );
    		    
    		    foreach( $get_posts as $loop_post_id ) {
    		    
    		        $get_item_id = get_post_meta( $loop_post_id->ID, '_bkap_order_item_id', true );
    		    
    		        if ( $get_item_id == $item_id ) {
    		            $booking_post_id = $loop_post_id->ID;
    		            break;
    		        }
    		    }
    		    
    		    // default the variables
    		    $item_type = '';
    		    $_status = '';
    		    if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
    		        $_status = ( isset( $item[ 'wapbk_booking_status' ] ) ) ? $item[ 'wapbk_booking_status' ] : ''; // booking status
    		        $item_type = $item[ 'type' ]; // line item type
    		    } else {
    		        if ( $item ) {
            		     // booking status
            		    $meta_data = $item->get_meta_data();
            		    foreach( $meta_data as $m_key => $m_value ) {
            		        if ( isset( $m_value->key ) && '_wapbk_booking_status' == $m_value->key ) {
            		            $_status = $m_value->value;
            		            break;
            		        }
             		    }
             		    // line item type
             		    $item_type = $item->get_type();
    		        }
    		    }
    		    
    		    if ( isset( $booking_post_id ) && $booking_post_id > 0 && ( isset( $item_type ) && 'line_item' == $item_type ) && ( ( isset( $_status ) && '' != $_status ) || ( ! isset( $_status ) ) ) ) {
    		        $args = array( 'post' => $booking_post_id, 'action' => 'edit' );
    		        ?>
    		        <td class="bkap_edit_column">
                        <a href="<?php echo esc_url_raw( add_query_arg( $args, admin_url() . 'post.php' ) ); ?>"><?php _e( 'Edit Booking', 'woocommerce-booking' ); ?></a>
                        <?php do_action( 'bkap_woo_order_item_values', $_product, $item, $item_id )?>
    		        </td>
    		        <?php 
    		    } else {
    		        echo '<td></td>';
    		    }
		    } else {
		        echo '<td></td>';
		    }
		    
		}
		
		/**
		 * Re-direct to the Edit Booking page
		 * 
		 * This funtion re-directs to the Edit Booking page when the
		 * View Boking link on WooCommerce->Orders Edit order page is clicked
		 * for a given item.
		 */
		function load_view_booking_page() {
		    $url = '';
		    
		    if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'woocommerce_history_page' ) {
		        if ( isset( $_GET[ 'item_id' ] ) && $_GET[ 'item_id' ] != 0 ) {
		            
		            ob_start();
		            $templatefilename = 'approve-booking.php';
		            if ( file_exists( dirname( __FILE__ ) . '/' . $templatefilename ) ) {
		                $template = dirname( __FILE__ ) . '/' . $templatefilename;
		                include( $template );
		            }
		            $content = ob_get_contents();
		            ob_end_clean();
		            
		            $args = array( 'slug'    => 'edit-booking',
                                    'title'   => 'Edit Booking',
                                    'content' => $content );
		            $pg = new bkap_approve_booking ( $args );
		        }
		    }
		}
		
		/**
		 * Update Item status
		 * 
		 * This function updates the item booking status. 
		 * It is called from the Edit Booking page Save button click 
		 */
		static function bkap_save_booking_status( $item_id, $_status ) {
		    global $wpdb;
		    
		    wc_update_order_item_meta( $item_id, '_wapbk_booking_status', $_status );
		        
		    // get the booking ID using the item ID
		    $booking_id = bkap_common::get_booking_id( $item_id );

		    // update the booking post status
		    if ( $booking_id ) {
		        $new_booking = bkap_checkout::get_bkap_booking( $booking_id );
		        $new_booking->update_status( $_status );
		    }

	        // get the order ID
	        $order_id = 0;
	        $query_order_id = "SELECT order_id FROM `". $wpdb->prefix."woocommerce_order_items`
                                WHERE order_item_id = %d";
	        $get_order_id = $wpdb->get_results( $wpdb->prepare( $query_order_id, $item_id ) );
	        
	        if ( isset( $get_order_id ) && is_array( $get_order_id ) && count( $get_order_id ) > 0 ) {
	            $order_id = $get_order_id[0]->order_id;
	        }
	        
	        //create order object
	        $order = new WC_Order( $order_id );
	        
	        // order details
	        $order_data = $order->get_items();
	        
	        $item_value = $order_data[ $item_id ];
		        
	        // update the booking history tables and GCal
	        $confirmation_object = new bkap_booking_confirmation();
	        $confirmation_object->update_booking_tables( $_status, $order_id, $item_id, $item_value, $order );
	         
	        // now check if the product is a bundled product
	        // if yes, then we need to update the booking status of all the child products
	        $bundled_items = wc_get_order_item_meta( $item_id, '_bundled_items' );
	        
	        if ( isset( $bundled_items ) && '' != $bundled_items ) {
	            $bundle_cart_key = wc_get_order_item_meta( $item_id, '_bundle_cart_key' );
	            foreach( $order_data as $o_key => $o_value ) {
	                $bundled_by = wc_get_order_item_meta( $o_key, '_bundled_by' );
	                 
	                // check if it is a part of the bundle
	                if ( isset( $bundled_by ) && $bundled_by == $bundle_cart_key ) {
	                    // update the booking status
	                    wc_update_order_item_meta( $o_key, '_wapbk_booking_status', $_status );
	                    // update the booking history tables and GCal
	                    $confirmation_object->update_booking_tables( $_status, $order_id, $o_key, $o_value, $order );
	                     
	                }
	            }
	        }
	         
		    // create an instance of the WC_Emails class , so emails are sent out to customers
            new WC_Emails();
            if ( 'cancelled' == $_status ) {
		        do_action( 'bkap_booking_pending-confirmation_to_cancelled_notification', $item_id );
		        // remove the item from the order
		        do_action( 'bkap_booking_pending-confirmation_to_cancelled', $item_id );
		        
            } else if ( 'confirmed' == $_status ) {// if booking has been approved, send email to user
		        do_action( 'bkap_booking_confirmed_notification', $item_id );
		    }
		    
		}
		
		/**
		 * Update the plugin tables and GCal for booking status
		 * for each Item ID passed
		 * 
		 * @since 3.5
		 * @param int order_id
		 * @param int item_id
		 * @param array item_value
		 * @param obj order
		 */
		function update_booking_tables( $_status, $order_id, $item_id, $item_value, $order ) {
		
		    global $wpdb;
		    // if the booking has been denied, release the bookings for re-allotment
		
		    if ( 'cancelled' == $_status ) {
		
		        $select_query =   "SELECT booking_id FROM `".$wpdb->prefix."booking_order_history`
						  WHERE order_id= %d";
		        $results      =   $wpdb->get_results ( $wpdb->prepare( $select_query, $order_id ) );
		
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
		
		        }
		
		        foreach ( $booking_details as $booking_id => $booking_data ) {
		            if ( $item_value[ 'product_id' ] == $booking_data['post_id'] ) {
		                // cross check the date and time as well as the product can be added to the cart more than once with different booking details
		                if ( $item_value[ 'wapbk_booking_date' ] == $booking_data[ 'start_date' ] ) {
		
		                    $time = $booking_data[ 'from_time' ] . ' - ' . $booking_data[ 'to_time' ];
		                    if ( isset( $item_value[ 'wapbk_checkout_date' ] ) && ( $item_value[ 'wapbk_checkout_date' ] == $booking_data[ 'end_date' ] ) ) {
		                        $item_booking_id = $booking_id;
		                        break;
		                    } else if( isset( $item_value[ 'wapbk_time_slot' ] ) && ( $item_value[ 'wapbk_time_slot'] == $time ) ) {
		                        $item_booking_id = $booking_id;
		                        break;
		                    } else {
		                        $item_booking_id = $booking_id;
		                        break;
		                    }
		                }
		            }
		        }
		
		        bkap_cancel_order::bkap_reallot_item( $item_value, $item_booking_id, $order_id );
		
		    } else if ( 'confirmed' == $_status ) {
		         
		        require_once plugin_dir_path( __FILE__ ) . '/includes/class.gcal.php';
		        // add event in GCal if sync is set to autmated
		        $gcal = new BKAP_Gcal();
		         
		        $user_id = get_current_user_id();
		        if( $gcal->get_api_mode( $user_id, $item_value[ 'product_id' ] ) == "directly" ) {
		             
		            $valid_date = false;
		            if ( isset( $item_value[ 'wapbk_booking_date' ] ) ) {
		                $valid_date = bkap_common::bkap_check_date_set( $item_value[ 'wapbk_booking_date' ] );
		            }
		             
		            if ( $valid_date ) {
		                $event_details = array();
		                 
		                $event_details[ 'hidden_booking_date' ] = $item_value[ 'wapbk_booking_date' ];
		                 
		                if ( isset( $item_value[ 'wapbk_checkout_date' ] ) && $item_value[ 'wapbk_checkout_date' ] != '' ) {
		                    $event_details[ 'hidden_checkout_date' ] = $item_value[ 'wapbk_checkout_date' ];
		                }
		                 
		                if ( isset( $item_value[ 'wapbk_time_slot' ] ) && $item_value[ 'wapbk_time_slot' ] != '' ) {
		                    $event_details[ 'time_slot' ] = $item_value[ 'wapbk_time_slot' ];
		                }
		                 
		            $event_details[ 'billing_email' ] = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_email : $order->get_billing_email();
		                $event_details[ 'billing_first_name' ] = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_first_name : $order->get_billing_first_name();
		                $event_details[ 'billing_last_name' ] = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_last_name : $order->get_billing_last_name();
		                $event_details[ 'billing_address_1' ] = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_address_1 : $order->get_billing_address_1();
		                $event_details[ 'billing_address_2' ] = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_address_2 : $order->get_billing_address_2();
		                $event_details[ 'billing_city' ] = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_city : $order->get_billing_city();
		                 
		                $event_details[ 'billing_phone' ] = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_phone : $order->get_billing_phone();
		                $event_details[ 'order_comments' ] = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->customer_note : $order->get_customer_note();
		                $event_details[ 'order_id' ] = $order_id;
		                 
		                $shipping_first_name = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->shipping_first_name : $order->get_shipping_first_name();
		                if ( isset( $shipping_first_name ) && $shipping_first_name != '' ) {
		                    $event_details[ 'shipping_first_name' ] = $shipping_first_name;
 		                }

 		                $shipping_last_name = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->shipping_last_name : $order->get_shipping_last_name();
		                if ( isset( $shipping_last_name ) && $shipping_last_name != '' ) {
		                    $event_details[ 'shipping_last_name' ] = $shipping_last_name;
 		                }
		                
 		                $shipping_address_1 = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->shipping_address_1 : $order->get_shipping_address_1(); 
		                if( isset( $shipping_address_1 ) && $shipping_address_1 != '' ) {
		                    $event_details[ 'shipping_address_1' ] = $shipping_address_1;
 		                }

		                $shipping_address_2 = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->shipping_address_2 : $order->get_shipping_address_2(); 
		                if ( isset( $shipping_address_2 ) && $shipping_address_2 != '' ) {
		                    $event_details[ 'shipping_address_2' ] = $shipping_address_2;
 		                }
		                
 		                $shipping_city = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->shipping_city : $order->get_shipping_city(); 
		                if ( isset( $shipping_city ) && $shipping_city != '' ) {
		                    $event_details[ 'shipping_city' ] = $shipping_city;
 		                } 
		                
 		                $_product = wc_get_product( $item_value[ 'product_id' ] );
		                 
		                $post_title = $_product->get_title();
		                $event_details[ 'product_name' ] = $post_title;
		                $event_details[ 'product_qty' ] = $item_value[ 'qty' ];
		                 
		                $event_details[ 'product_total' ] = $item_value[ 'line_total' ];
		                 
		                // if sync is disabled at the product level, set post_id to 0 to ensure admin settings are taken into consideration
		                $booking_settings = get_post_meta( $item_value[ 'product_id' ], 'woocommerce_booking_settings', true );
		                $post_id = $item_value[ 'product_id' ];
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
		
		/**
		 * Validate bookable products 
		 * 
		 * This function displays a notice and empties the cart if the cart contains
		 * any products that conflict with the new product being added. 
		 * 
		 * @param array $_POST
		 * @param int $product_id
		 * @return string
		 */
		function bkap_validate_conflicting_products( $POST, $product_id ) {
		    
		    $quantity_check_pass = 'yes';
		    // check if the product being added requires confirmation
		    $product_requires_confirmation = bkap_common::bkap_product_requires_confirmation( $product_id );
		    
		    // check if the cart contains a product that requires confirmation
		    $cart_requires_confirmation = bkap_common::bkap_cart_requires_confirmation();
		    
		    $validation_status = 'warn_modify_yes';
		    
		    switch ( $validation_status ) {
		        case 'warn_modify_yes':
		            $conflict = 'NO';
		            
		            if ( count( WC()->cart->cart_contents ) > 0 ) {
		            // if product requires confirmation and cart contains product that does not
    		            if ( $product_requires_confirmation && ! $cart_requires_confirmation ) {
    		                $conflict = 'YES';
    		            }
    		            // if product does not need confirmation and cart contains a product that does
    		            if ( ! $product_requires_confirmation && $cart_requires_confirmation ) {
    		                $conflict = 'YES';
    		            }
    		            // if conflict
    		            if ( 'YES' == $conflict ) {
                            // remove existing products
    		                WC()->cart->empty_cart();
                            
                            // add a notice
    		                $message = bkap_get_book_t( 'book.conflicting-products' );
    		                wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type = 'notice' );
    		            }
                    }
		            break;
		    }
		    
		    return $quantity_check_pass; 
		}

		/**
		 * Update Booking status to paid
		 * 
		 * Updates the Booking status to paid to ensure they do not remain
		 * in the Unpaid section in Booking->View Bookings
		 * 
		 * @param int $order_id
		 */
		function bkap_update_booking_status ( $order_id ) {
		    
		    $order_obj    =   new WC_order( $order_id );
    		$order_items  =   $order_obj->get_items();
    		foreach( $order_items as $item_key => $item_value ) {

    		    $booking_status = wc_get_order_item_meta( $item_key, '_wapbk_booking_status' );
    		    
    		    if ( isset( $booking_status ) && 'confirmed' == $booking_status ) {
    		        wc_update_order_item_meta( $item_key, '_wapbk_booking_status', 'paid' );
    		        
    		        // get the booking ID using the item ID
    		        $booking_id = bkap_common::get_booking_id( $item_key );

    		        // update the booking post status
    		        if ( $booking_id ) {
    		            $new_booking = bkap_checkout::get_bkap_booking( $booking_id );
    		            $new_booking->update_status( 'paid' );
    		        }
    		    }
    		}
		}
		
		/**
		 * Remove an item frm the other if it has been cancelled by the admin.
		 * 
		 * @param int $item_id
		 */
		function bkap_remove_cancelled_booking( $item_id ) {
		    global $wpdb;
		    
		    $booking  = bkap_common::get_bkap_booking( $item_id );
		    $order = new WC_order( $booking->order_id );
		    $bookings = array();
		    
		    if ( ! empty ( $order ) && is_array( $order->get_items() ) ) {
		        foreach ( $order->get_items() as $order_item_id => $item ) {
		            if ( $order_item_id == $item_id ) {
		                wc_delete_order_item( $order_item_id );
		                $order->calculate_totals();
		                $order->add_order_note( sprintf( __( 'The product %s has been removed from the order because the booking cannot be confirmed.', 'woocommerce-booking' ), $item['name'] ) );
		            }
		        }
		    }
		}
    } 
}
$bkap_booking_confirmation = new bkap_booking_confirmation(); 
?>