<?php 

/**
 * bkap_admin_bookings class
 **/
if ( !class_exists( 'bkap_admin_bookings' ) ) {

	class bkap_admin_bookings {


	    /**
	     * Stores errors.
	     *
	     * @var array
	     */
	    private $errors = array();
	     
		public function __construct() {
		    
		}

		
		static function bkap_create_booking_page() {
		    
		    $bookable_product_id = 0;
		    
		    $bkap_admin_bookings = new bkap_admin_bookings();
		    
		    $step = 1;

		    try {
                if ( ! empty( $_POST[ 'bkap_create_booking' ] ) ) {
    		        
                    $customer_id         = isset( $_POST[ 'customer_id' ] ) ? absint( $_POST[ 'customer_id' ] ) : 0;
    				
            $bookable_product_id = absint( $_POST[ 'bkap_product_id' ] );
    				$booking_order       = wc_clean( $_POST[ 'bkap_order' ] );
    
    				if ( ! $bookable_product_id ) {
    					throw new Exception( __( 'Please choose a bookable product', 'woocommerce-booking' ) );
    				}
    
    				if ( 'existing' === $booking_order ) {
    					$order_id      = absint( $_POST[ 'bkap_order_id' ] );
    					$booking_order = $order_id;
    
    					if ( ! $booking_order || get_post_type( $booking_order ) !== 'shop_order' ) {
    						throw new Exception( __( 'Invalid order ID provided', 'woocommerce-booking' ) );
    					}
    				}
    		        
    				$bkap_data[ 'customer_id' ] = $customer_id;
    				$bkap_data[ 'product_id' ] = $bookable_product_id;
    				$bkap_data[ 'order_id' ] = $booking_order;
    				$bkap_data[ 'bkap_order' ] = $_POST[ 'bkap_order' ];
    		        $step++;
    		        
    		    } else if ( ! empty( $_POST[ 'bkap_create_booking_2' ] ) ) {
    		        
    		        $create_order = ( 'new' === $_POST[ 'bkap_order' ] ) ? true : false;
    		    
                    // validate the booking data
    		        $validations = true;
    		        $_product = wc_get_product( $_POST[ 'bkap_product_id' ] );
    		        if ( $_product->post_type === 'product_variation' ) {
    		            $settings_id = $_product->get_parent_id();
    		        } else {
    		            $settings_id = $_POST[ 'bkap_product_id' ];
    		        }
    		        if ( $_POST[ 'wapbk_hidden_date' ] === '' ) {
    		            $validations = false;
    		        }
    		        
    		        $booking_type = get_post_meta( $settings_id, '_bkap_booking_type', true );
    		        
    		        if ( 'multiple_days' === $booking_type ) {
    		            if ( $_POST[ 'wapbk_hidden_date_checkout' ] === '' ) {
    		                $validations = false;
    		            }
    		        } else if ( 'date_time' === $booking_type ) {
    		            if ( $_POST[ 'time_slot' ] === '' ) {
    		                $validations = false;
    		            } 
    		        }
    		        
    		        if ( ! $validations ) {
    		            throw new Exception( __( 'Please select the Booking Details.', 'woocommerce-booking' ) );
    		        }
    		        
    		        // setup the data
    		        $time_slot = ( isset( $_POST[ 'time_slot' ] ) ) ? $_POST[ 'time_slot' ] : '';
    		        $checkout_date = ( isset( $_POST[ 'wapbk_hidden_date_checkout' ] ) && '' != $_POST[ 'wapbk_hidden_date_checkout' ] ) ? $_POST[ 'wapbk_hidden_date_checkout' ] : '';
    		        
    		        $booking_details[ 'product_id' ] = $_POST[ 'bkap_product_id' ];
    		        $booking_details[ 'customer_id' ] = $_POST[ 'bkap_customer_id' ];
    		        
    		        if ( $time_slot !== '' ) {
    		            $times = explode( '-', $time_slot );
    		            $start_time = ( isset( $times[ 0 ] ) && '' !== $times[ 0 ] ) ? date( 'H:i', strtotime( $times[ 0 ] ) ) : '00:00';
    		            $end_time = ( isset( $times[ 1 ] ) && '' !== $times[ 1 ] ) ? date( 'H:i', strtotime( $times[ 1 ] ) ) : '00:00';
    
    		            $booking_details[ 'start' ] = strtotime( $_POST[ 'wapbk_hidden_date' ] . $start_time );
    		            $booking_details[ 'end' ]  = strtotime( $_POST[ 'wapbk_hidden_date' ] . $end_time );
    		             
    		        } else if ( $checkout_date !== '' ) {
    		            $booking_details[ 'start' ] = strtotime( $_POST[ 'wapbk_hidden_date' ] );
    		            $booking_details[ 'end' ] = strtotime( $checkout_date );
    		        } else {
    		            $booking_details[ 'start' ] = strtotime( $_POST[ 'wapbk_hidden_date' ] );
    		            $booking_details[ 'end' ] = strtotime( $_POST[ 'wapbk_hidden_date' ] );
    		        }
    		        $booking_details[ 'price' ] = $_POST[ 'bkap_price_charged' ];

                    
                    if( isset( $_POST['bkap_front_resource_selection'] ) && $_POST['bkap_front_resource_selection'] != "" ){
                        $booking_details[ 'bkap_resource_id' ] = $_POST[ 'bkap_front_resource_selection' ];
                    }
    
    		        if ( 'new' == $_POST[ 'bkap_order' ] ) {
    		            // create a new order
    		            $status = import_bookings::bkap_create_order( $booking_details, false );
    		            // get the new order ID
    		            $order_id = ( absint( $status[ 'order_id' ] ) > 0 ) ? $status[ 'order_id' ] : 0;
    		            
    		        } else {
    		            $order_id = ( isset( $_POST[ 'bkap_order_id' ] ) ) ? $_POST[ 'bkap_order_id' ] : 0;
    		            if ( $order_id > 0 ) {
    		                $booking_details[ 'order_id' ] = $order_id;
    		                $status = import_bookings::bkap_create_booking( $booking_details, false );
    		            } 
    		            
    		        }
    		        
    		        
    		        if ( isset( $status[ 'new_order' ] ) && $status[ 'new_order' ] ) {
                        // redirect to the order
                        wp_safe_redirect( admin_url( 'post.php?post=' . ( $order_id ) . '&action=edit' ) );
    		        } else if ( isset( $status[ 'item_added' ] ) && $status[ 'item_added' ] ) {
    		            // redirect to the order
    		            wp_safe_redirect( admin_url( 'post.php?post=' . ( $order_id ) . '&action=edit' ) );
    		        } else {
    		            if ( 1 == $status[ 'backdated_event' ] ) {
    		                throw new Exception( __( 'Back Dated bookings cannot be created. Please select a future date.', 'woocommerce-booking' ) );
    		            }
    		            
    		            if ( 1 == $status[ 'validation_check' ] ) {
    		                throw new Exception( __( 'The product is not available for the given date for the desired quantity.', 'woocommerce-booking' ) );
    		            }
    		            
    		            if ( 1 == $status[ 'grouped_product' ] ) {
    		                throw new Exception( __( 'Bookings cannot be created for grouped products.', 'woocommerce-booking' ) );
    		            }
    		            
    		        } 
                    
    		    } 
		    } catch ( Exception $e ) {
		        $bkap_admin_bookings = new bkap_admin_bookings();
                $bkap_admin_bookings->errors[] = $e->getMessage();
		    }
		    
		    switch( $step ) {
		        case '1':
		            $bkap_admin_bookings->create_bookings_1();
		            break;
		        case '2':
		            $bkap_admin_bookings->create_bookings_2( $bkap_data );
		            break;
		        default:
		            $bkap_admin_bookings->create_bookings_1();
		            break;
		    }
		     
		}
		
		/**
		 * Output any errors
		 */
		public function show_errors() {
		    foreach ( $this->errors as $error ) {
		        echo '<div class="error"><p>' . esc_html( $error ) . '</p></div>';
		    }
		}
		
		function create_bookings_1() {
            $this->show_errors();
            
            $customers = array();
             
            $args1 = array( 'role' => 'customer',
                'fields' => array( 'id', 'display_name', 'user_email' )
            );
            
            $args2 = array( 'role' => 'administrator',
                'fields' => array( 'id', 'display_name', 'user_email' )
            );
            
            $wp_users = array_merge( get_users( $args1 ), get_users( $args2 ) );
            
            foreach( $wp_users as $users ) {
                $customer_id = $users->id;
                $user_email = $users->user_email;
                $user_name = $users->display_name;
                $customers[ $customer_id ] = "$user_name (#$customer_id - $user_email )";
            }
		    ?>
		    <div class="wrap woocommerce">
		    <h2><?php _e( 'Create Booking', 'woocommerce-booking' ); ?></h2>
		    
		    	<p><?php _e( 'You can create a new booking for a customer here. This form will create a booking for the user, and optionally an associated order. Created orders will be marked as processing.', 'woocommerce-booking' ); ?></p>
		    
		    	<?php
		    	$bkap_admin_bookings = new bkap_admin_bookings();
		    	$bkap_admin_bookings->show_errors(); ?>
		    	<form method="POST">
		    		<table class="form-table">
		    			<tbody>
		    				<tr valign="top">
		    					<th scope="row">
		    						<label for="customer_id"><?php _e( 'Customer', 'woocommerce-booking' ); ?></label>
		    					</th>
		    					<td>
                                    <select id="customer_id" name="customer_id" class="wc-customer-search">
                                        <option value="0"><?php _e( 'Guest', 'woocommerce-booking' ); ?></option> 
                                        <?php
                                        foreach ( $customers as $c_id => $c_data ) {
                                            echo '<option value="' . esc_attr( $c_id ) . '">' . sanitize_text_field( $c_data ) . '</option>';
                                        }
                                        ?>
                                    </select>
		    					</td>
		    				</tr>
		    				<tr valign="top">
		    					<th scope="row">
		    						<label for="bkap_product_id"><?php _e( 'Bookable Product', 'woocommerce-booking' ); ?></label>
		    					</th>
		    					<td>
		    						<select id="bkap_product_id" name="bkap_product_id" class="chosen_select" style="width: 300px">
		    							<option value=""><?php _e( 'Select a bookable product...', 'woocommerce-booking' ); ?></option>
		    							<?php foreach ( bkap_common::get_woocommerce_product_list() as $product ) :
		    							       // Do not add Grouped Products and subscription products to the dropdown.  
                                                $_product = wc_get_product( $product[ 1 ] );
                                                $product_type = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $product->type : $_product->get_type();
                                                    
                                                if ( $product_type === 'subscription' || $product_type === 'grouped' || $product_type === 'composite' || $product_type === 'bundle' ) {
                                                    continue;
                                                }?>
		    								<option value="<?php echo $product[ 1 ]; ?>"><?php echo sprintf( '%s', $product[ 0 ] ); ?></option>
		    							<?php endforeach; ?>
		    						</select>
		    					</td>
		    				</tr>
		    				<tr valign="top">
		    					<th scope="row">
		    						<label for="bkap_create_order"><?php _e( 'Create Order', 'woocommerce-booking' ); ?></label>
		    					</th>
		    					<td>
		    						<p>
		    							<label>
		    								<input type="radio" name="bkap_order" value="new" class="checkbox" />
		    								<?php _e( 'Create a new corresponding order for this new booking. Please note - the booking will not be active until the order is processed/completed.', 'woocommerce-booking' ); ?>
		    							</label>
		    						</p>
		    						<p>
		    							<label>
		    								<input type="radio" name="bkap_order" value="existing" class="checkbox" />
		    								<?php _e( 'Assign this booking to an existing order with this ID:', 'woocommerce-booking' ); ?>
		    								<input type="number" name="bkap_order_id" value="" class="text" size="3" style="width: 80px;" />
		    							</label>
		    						</p>
		    					</td>
		    				</tr>
		    				<?php do_action( 'bkap_after_create_booking_page' ); ?>
		    				<tr valign="top">
		    					<th scope="row">&nbsp;</th>
		    					<td>
		    						<input type="submit" name="bkap_create_booking" class="button-primary" value="<?php _e( 'Next', 'woocommerce-booking' ); ?>" />
		    						<?php wp_nonce_field( 'bkap_create_notification' ); ?>
		    					</td>
		    				</tr>
		    			</tbody>
		    		</table>
		    	</form>
		    </div>
		    		    
		    <?php
		}
		
		function create_bookings_2( $booking_data ) {
		    $this->show_errors();
		    // check if the passed product ID is a variation ID
		    $_product = wc_get_product( $booking_data[ 'product_id' ] );
		    $variation_id = 0;
		    
		    $parent_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $_product->parent->id : $_product->get_parent_id();
		    $product_id = $booking_data[ 'product_id' ];
		    ?>
		    <h2><?php _e( 'Create Booking', 'woocommerce-booking' ); ?></h2>
		    <form method="POST">
	    		<table class="form-table">
	    			<tbody>
	    				<tr valign="top">
	    					<th scope="row" width='10%'>
	    						<label><?php _e( 'Booking Data:', 'woocommerce-booking' ); ?></label>
	    					</th>
	    					<td width='40%'>
                		    <?php 
		                      if ( $parent_id > 0 ) {
                                    $settings_id = $parent_id;
                                } else {
                                    $settings_id = $product_id;
                                }
                    		    $duplicate_of = bkap_common::bkap_get_product_id( $settings_id );
                    		    // CSS scripts
                    		    woocommerce_booking::inlcude_frontend_scripts_css( $settings_id );
                    		    // JS scripts
                    		    woocommerce_booking::include_frontend_scripts_js( $settings_id );
                    		    // localize the scripts
                    		    $hidden_dates = bkap_booking_process::bkap_localize_process_script( $duplicate_of );
                    		    // print the hidden fields
                    		    // print the booking form
                    		    $booking_settings = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
                    		    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
                    		    
                    		    wc_get_template(
                        		    'bookings/bkap-bookings-box.php',
                        		    array(
                            		    'product_id'		=> $duplicate_of,
                            		    'product_obj'		=> $_product,
                            		    'booking_settings' 	=> $booking_settings,
                            		    'global_settings'	=> $global_settings,
                            		    'hidden_dates'      => $hidden_dates ),
                            		    'woocommerce-booking/',
                        		    BKAP_BOOKINGS_TEMPLATE_PATH );
                                
                    		    // price display
                    		    bkap_booking_process::bkap_price_display();
                    		    ?>
                		    </td>
                		    <td>
                		    </td>
            		    </tr>
            		    <tr valign="top">
	    					<th scope="row">&nbsp;</th>
	    					<td>
	    						<input type="submit" name="bkap_create_booking_2" class="button-primary" value="<?php _e( 'Create Booking', 'woocommerce-booking' ); ?>" />
	    						<input type="hidden" name="bkap_customer_id" value="<?php echo esc_attr( $booking_data[ 'customer_id' ] ); ?>" />
        						<input type="hidden" name="bkap_product_id" value="<?php echo esc_attr( $product_id ); ?>" />
        						<input type="hidden" name="bkap_order" value="<?php echo esc_attr( $booking_data[ 'bkap_order' ] ); ?>" />
        						<input type="hidden" name="bkap_order_id" value="<?php echo esc_attr( $booking_data[ 'order_id' ] ); ?>" />
        						<?php if ( $parent_id > 0 ) { ?>
        						<input type="hidden" class="variation_id" value="<?php echo $product_id; ?>" />
        						<?php 
            						$variation_class = new WC_Product_Variation( $product_id );
            						$get_attributes =   $variation_class->get_variation_attributes();
            						
            						if( is_array( $get_attributes ) && count( $get_attributes ) > 0 ) {
            						    foreach( $get_attributes as $attr_name => $attr_value ) {
            						        $attr_value = htmlspecialchars( $attr_value, ENT_QUOTES );
            						        // print a hidden field for each of these
            						        print( "<input type='hidden' name='$attr_name' value='$attr_value' />" );
            						    }
            						}
        						}
        						?>
        						        						
	    						<?php wp_nonce_field( 'bkap_create_booking' ); ?>
	    					</td>
	    					<td></td>
	    				</tr>
        		    </tbody>
    		    </table>
    		 </form>
    		 <?php     
		}
		
	} // end of class
}
