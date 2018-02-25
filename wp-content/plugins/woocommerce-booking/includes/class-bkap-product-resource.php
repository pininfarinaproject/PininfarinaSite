<?php

/**
 * Resource Appearce and Calculations.
 *
 *
 * @author  TycheSoftwares
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class_Bkap_Product_Resource class.
 *
 * @since 4.6.0
 */

class Class_Bkap_Product_Resource {

    /**
     * Holds product id.
     * @var int
     */

	private $product_id;

    /**
     * Constructor. Reference to the Resource.
     *
     * @since 4.6.0
     * @param $product_id int Product ID.
     */

    public function __construct( $product_id = 0 ) {

    	if ( $product_id != 0 ) {
    		$this->$product_id = $product_id;
    	}

        // add the Resource tab in the Booking meta box
        add_action( 'bkap_add_tabs', 				array( &$this, 'bkap_resource_tab' ), 12, 1 );
        
        // add fields in the Resource tab in the Booking meta box
        add_action( 'bkap_after_listing_enabled', 	array( &$this, 'bkap_resource_settings' ), 12, 1 );

        // Ajax 
        add_action( 'admin_init',					array( &$this, 'bkap_load_resource_ajax_admin' ) ); 

        // save the checkbox value in post meta record
        add_filter( 'bkap_save_product_settings', 	array( &$this, 'save_product_settings' ), 10, 2 ); 

        // adding resource option on Booking meta box header 
        add_action( 'bkap_add_resource_section', 	array( &$this, 'bkap_add_resource_section_callback' ), 10, 1 ); 

        // Adding dropdown for resource on front end product page.
        add_action( 'bkap_before_booking_form',    	array( &$this, 'bkap_front_end_resource_field' ), 6, 1 );     
        
        // Adding data in the additional data being passed in the localized script
        add_filter( 'bkap_add_additional_data' , 	array( &$this, 'bkap_add_additional_resource_data' ), 10, 3 );

        // print hidden data for resource on the front end product page
        add_action( 'bkap_add_additional_data', 	array( &$this, 'print_hidden_resource_data' ), 11, 3 );

        add_filter( 'bkap_locked_dates_for_dateandtime', array( &$this, 'bkap_locked_dates_for_dateandtime_callback' ), 10, 4 );

        add_action( 'admin_enqueue_scripts',        array( &$this, 'bkap_resource_css_file' ), 100 );

        add_filter( 'bkap_resource_add_to_cart_validation', array( &$this, 'bkap_resource_add_to_cart_validation_callback' ), 10, 5 );
    }

    /**
     * This function is to validate the availability of resource on add to cart button action
     * 
     * @since 4.7.0
     */

    public static function bkap_resource_add_to_cart_validation_callback( $post_data, $post_id, $booking_settings, $quantity_check_pass, $resource_validation_result ){
            global $woocommerce;

            $item_quantity  = isset( $post_data['quantity'] ) ? $post_data['quantity'] : 1;
        
            $resource_id    = (int)$post_data['bkap_front_resource_selection'];
        
            $resource_booking_data = Class_Bkap_Product_Resource::print_hidden_resource_data( array() , $booking_settings, $post_id );
        
            $resource_bookings_placed = $resource_booking_data['bkap_booked_resource_data'][$resource_id]['bkap_booking_placed'];
        
            $resource_bookings_placed_list_dates    = explode( ",", $resource_bookings_placed );
            $resource_date_array                    = array();
        
            foreach ( $resource_bookings_placed_list_dates as $list_key => $list_value ) {
                // separate the qty for each date & time slot
                $explode_date = explode( '=>', $list_value );
        
                if ( isset( $explode_date[1]) && $explode_date[1] != '' ) {
                    $date = substr( $explode_date[0], 1, -1 );
                    $resource_date_array[ $date ] = (int)$explode_date[ 1 ];
                }
            }
        
            $resource_booked_for_date = 0;
        
            $selected_date = $_POST['wapbk_hidden_date'];
        
            if ( array_key_exists( $selected_date, $resource_date_array ) ) {
                $resource_booked_for_date = $resource_date_array[ $selected_date ];
            }
        
            $bkap_resource_availability = get_post_meta( $resource_id, '_bkap_resource_qty', true );
        
            $resource_booking_available = $bkap_resource_availability - $resource_booked_for_date;       

            if( $item_quantity <= $resource_booking_available ){
                $quantity_check_pass = "yes";
            }

            if ( $quantity_check_pass == "yes" ) {

                $resource_qty = 0;
            
                foreach ( $woocommerce->cart->cart_contents as $cart_check_key => $cart_check_value ) {
            
                    if( isset( $cart_check_value['bkap_booking'][0]['resource_id'] ) ){
            
                        if( $resource_id == $cart_check_value['bkap_booking'][0]['resource_id'] ) {
            
                            // Calculation for resource qty for product parent foreach product is single day.
                            if( isset( $post_data['wapbk_hidden_date_checkout'] ) && $post_data['wapbk_hidden_date_checkout'] == "" ){
            
                                $hidden_date_str = $hidden_date_checkout_str = $val_hidden_date_str = "";
            
                                $hidden_date_str = strtotime( $cart_check_value['bkap_booking'][0]['hidden_date'] );
            
                                if( $cart_check_value['bkap_booking'][0]['hidden_date_checkout'] != "" ){
                                    $hidden_date_checkout_str = strtotime( $cart_check_value['bkap_booking'][0]['hidden_date_checkout'] );
                                }
            
                                $val_hidden_date_str = strtotime( $post_data['wapbk_hidden_date'] );
            
                                if( $hidden_date_checkout_str == "" ){
                                    if( $post_data['wapbk_hidden_date'] == $cart_check_value['bkap_booking'][0]['hidden_date'] ){
                                        $resource_qty += $cart_check_value['quantity'];
                                    }
                                }else{
                                    if( $val_hidden_date_str >= $hidden_date_str && $val_hidden_date_str < $hidden_date_checkout_str ){
                                        $resource_qty += $cart_check_value['quantity'];
                                    }
                                }
                            }else{ // Calculation for resource qty for product parent foreach product is multiple nights.
            
                                $hidden_date_str = $hidden_date_checkout_str = $cart_check_hidden_date_str = "";
            
                                $hidden_date_str = strtotime( $post_data['wapbk_hidden_date'] );
            
                                if( $cart_check_value['bkap_booking'][0]['hidden_date_checkout'] != "" ){
                                    $hidden_date_checkout_str = strtotime( $post_data['wapbk_hidden_date_checkout'] );
                                }
            
                                $cart_check_hidden_date_str    = strtotime( $cart_check_value['bkap_booking'][0]['hidden_date'] );
            
                                if( $cart_check_hidden_date_str >=  $hidden_date_str && $cart_check_hidden_date_str < $hidden_date_checkout_str ){
                                    $resource_qty += $cart_check_value['quantity'];
                                }
                            }
                        }
                    }
                }

                $resource_booking_available = $resource_booking_available - $resource_qty;

                if( $resource_booking_available < $item_quantity ) {                   

                    $quantity_check_pass = "no";
                }
            }

            $resource_validation_result['quantity_check_pass'] = $quantity_check_pass;
            $resource_validation_result['resource_booking_available'] = $resource_booking_available;

        return $resource_validation_result;
        
    }

    /**
     * This function is to dequeue CSS of WooCommerce Bookings to overcome conflict
     * Later this will be removed with appropriate solution
     * 
     * @since 4.6.0
     */

    public static function bkap_resource_css_file(){

        if ( get_post_type() == 'bkap_resource' ) {
            wp_dequeue_style( 'wc_bookings_admin_styles' );
        }
    }

    /**
     * This function is used to alter the lockout date for date and time product
     * 
     * @param $resource_id Int Resource ID
     * @param $product_id Int Product ID
     * @param $booking_settings Array Booking Settings
     * @param $resource_lockout_data Array Lockout Data
     * @since 4.6.0
     */

    public static function bkap_locked_dates_for_dateandtime_callback( $resource_id, $product_id, $booking_settings, $resource_lockout_data ){

    	$bkap_resource_availability = get_post_meta( $resource_id, '_bkap_resource_qty', true );

    	$total_bookings 		= $resource_lockout_data['bkap_date_time_array'];

    	$bookings_placed = $lockout_reached_time_slots = "";
    	$lockout_reached_dates = "";
    	

    	if ( isset( $total_bookings ) && is_array( $total_bookings ) && count( $total_bookings ) > 0 ) {
                    
	        foreach ( $total_bookings as $date_key => $qty_value ) {
	        	
	            if ( is_array( $qty_value ) && count( $qty_value ) > 0 ) {
	                $time_slot_total_booked = 0;
	                
	                foreach ( $qty_value as $k => $v ) {
	                    
	                    $time_slot_total_booked += $v;
	                    $bookings_placed        .= '"' . $date_key . '"=>' . $k . '=>' . $v . ',';
	                    
	                    if ( $bkap_resource_availability <= $v ) {
	                        // time slot should be blocked once lockout is reached
	                        $lockout_reached_time_slots .= $date_key .'=>' . $k . ',';
	                        // date should be blocked only when all the time slots are fully booked
	                        // run a loop through all the time slots created for that date/day and check if lockout is reached for that variation
	                        if ( isset( $booking_settings['booking_time_settings'] ) && is_array( $booking_settings['booking_time_settings'] ) && count( $booking_settings['booking_time_settings'] ) > 0 ) {
	                            $time_settings = $booking_settings['booking_time_settings'];
	                            
	                            if ( array_key_exists( $date_key, $time_settings ) ) {
	                                
	                                if ( array_key_exists( $date_key, $time_settings) ) {
	                                    $number_of_slots    = count( $time_settings[ $date_key ] );
	                                    // total time slot lockout for the variation is the number of slots * the lockout
	                                    $total_lockout      = $number_of_slots * $bkap_resource_availability;
	                                
	                                }
	                            } else {
	                                // get the recurring weekday
	                                $day_number = date( 'w', strtotime( $date_key ) );
	                                $weekday    = 'booking_weekday_' . $day_number;
	                                
	                                if ( array_key_exists( $weekday, $time_settings) ) {
	                                    $number_of_slots = count( $time_settings[ $weekday ] );
	                                    // total time slot lockout for the variation is the number of slots * the lockout
	                                    $total_lockout = $number_of_slots * $bkap_resource_availability;
	                                    
	                                }
	                            }
	                            //if reached then add it to the list of dates to be locked
	                            if ( isset( $total_lockout ) && ( $total_lockout <= $time_slot_total_booked ) ) {
	                                $lockout_reached_dates .= '"' . $date_key . '",';
	                            }	                            
	                        }	                        
	                    }
	                }
	            }
	        }
	    }

        $lockout_reached_dates        = substr_replace( $lockout_reached_dates, '', -1 );

	    $resource_lockout_data['bkap_locked_dates'] = $lockout_reached_dates;

	    return $resource_lockout_data;
    }


    /**
     * This function is used to add availability and quantity data in 
     * the additional data array being passed in the localized script. resource field.
     *
     * @param $additional_data Array Additional Data
     * @param $booking_settings Array Booking Settings
     * @param $product_id Int Product ID
     * @since 4.6.0
     */
    public static function print_hidden_resource_data( $additional_data, $booking_settings, $product_id ) {
    	
    	global $wpdb, $post;

        if ( get_post_type( $post ) === 'product' ){
            $product_id = $post->ID;
        }
        // get product type
        $product 		= wc_get_product( $product_id );
        $product_type 	= $product->get_type();
        $resource 		= self::bkap_resource( $product_id );
		$resource_ids 	= self::bkap_get_product_resources( $product_id );

        if( $product_type != 'simple' ){
        	return $additional_data;
        }elseif ( $resource == "" ) {
			return $additional_data;
		} elseif ( $resource != "" && !( is_array( $resource_ids ) ) ) {
			return $additional_data;
		}

        // Booking settings
        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );

        // for a variable and bookable product 
        if ( isset( $booking_settings[ 'booking_enable_date' ] ) && 'on' == $booking_settings[ 'booking_enable_date' ] ) {
        	
        	$resource_costs = self::bkap_get_resource_costs( $product_id );
        	
        	foreach ( $resource_costs as $key_resource_id => $value_resource_cost ) {
        		
        		$resource_bookings[$key_resource_id] = bkap_calculate_bookings_for_resource( $key_resource_id );
        		
        		if ( isset( $booking_settings[ 'booking_enable_time' ] ) && 'on' == $booking_settings[ 'booking_enable_time' ] ) {
        			$resource_bookings[$key_resource_id] = apply_filters( 'bkap_locked_dates_for_dateandtime', $key_resource_id, $product_id,$booking_settings, $resource_bookings[$key_resource_id] );
        		}
        		
        	}
        }

        $additional_data['bkap_booked_resource_data'] = $resource_bookings;

        return $additional_data;
    }

    /**
     * This function is used to add availability and quantity data in 
     * the additional data array being passed in the localized script. resource field.
     *
     * @param $additional_data Array Additional Data
     * @param $booking_settings Array Booking Settings
     * @param $product_id Int Product ID
     * @since 4.6.0
     */

    public static function bkap_add_additional_resource_data( $additional_data, $booking_settings, $product_id ) {
    	
    	$resource 		= self::bkap_resource( $product_id );
		$resource_ids 	= self::bkap_get_product_resources( $product_id );
		
		if ( $resource == "" ) {
			return $additional_data;
		} elseif ( $resource != "" && !( is_array( $resource_ids ) ) ) {
			return $additional_data;
		}

    	$resource_additional_data = array();
    	
    	foreach ( $resource_ids as $resource_id ) {
    		if ( get_post_status( $resource_id ) ) {
    			$resource = new BKAP_Product_Resource( $resource_id, $product_id );
				
    			$resource_additional_data[$resource_id]['resource_availability']	= $resource->get_resource_availability();
    			$resource_additional_data[$resource_id]['resource_qty'] 			= $resource->get_resource_qty();
    		}
    	}

    	$additional_data['bkap_resource_data'] = $resource_additional_data;    	

    	return $additional_data; 

    }

    /**
	 * This function is used to create resource field.
	 *
	 * @since 4.6.0
	 */
    public static function bkap_front_end_resource_field( $product_id ) {
    		
		$resource 		= self::bkap_resource( $product_id );
		$resource_ids 	= self::bkap_get_product_resources( $product_id );
		

		if ( $resource == "" ) {
			return;
		} elseif ( $resource != "" && !( is_array( $resource_ids ) ) ) {
			return;
		}

		$resource_costs       = self::bkap_get_resource_costs( $product_id );		
		$label 				  = self::bkap_get_resource_label( $product_id );
		$resource_selection   = self::bkap_product_resource_selection( $product_id );


		if( "bkap_automatic_resource" == $resource_selection ) {

		  ?><input type="hidden" id="bkap_front_resource_selection" name="bkap_front_resource_selection" value="<?php echo $resource_ids[0];?>"><?php	

		}else{
			

			if( $label == "" )
				$label = __( 'Type', 'woocommerce-booking' );
			?>
			<label for="bkap_front_resource_lable"><?php echo $label.":";?></label>				
		    
		    <select id="bkap_front_resource_selection" name="bkap_front_resource_selection" style="width:100%;    height: 35px;">
        		<?php
        		foreach ( $resource_costs as $key => $value ){
        			if ( get_post_status( $key ) ){
        				echo '<option value="' . esc_attr( $key ) . '">'. esc_html( get_the_title( $key ) ) . ' - ( +' . wc_price( $value ) .' ) </option>';
        			}	        			
				}
        		?>
        		
        	</select>
    		
        	<?php
		}
    }

    /**
	 * This function is used to create resource field.
	 *
	 * @since 4.6.0
	 */

    public static function bkap_get_extra_options(){

        return apply_filters( 'bkap_extra_options', array(
            
            'bkap_resource' => array(
                'id'            => '_bkap_resource',
                'wrapper_class' => 'show_if_simple',
                'label'         => __( 'Booking Resource', 'woocommerce-booking' ),
                'description'   => __( 'Booking Resource Description.', 'woocommerce-booking' ),
                'default'       => 'no',
            ),
        ) );
    }

    /**
	 * This function is used to add resource option on Booking meta box header.
	 *
	 * @param $product_id Product ID
	 * @since 4.6.0
	 */

    public static function bkap_add_resource_section_callback( $product_id ) {
    		
    	?>
    	<span class="bkap_type_box" style="margin-left: 15%;">        

        <?php foreach ( self::bkap_get_extra_options() as $key => $option ) :
            if ( metadata_exists( 'post', $product_id, '_' . $key ) ) {
                $bkap_resource_option   = get_post_meta( $product_id, '_bkap_resource', true );

                $selected_value = '';
                if( $bkap_resource_option == "on" ){
                    $selected_value = 'checked';
                }
                
            } else {
                $selected_value = 'yes' === ( isset( $option['default'] ) ? $option['default'] : 'no' );
            }
            
            ?>
            <label for="<?php echo esc_attr( $option['id'] ); ?>" class="<?php echo esc_attr( $option['wrapper_class'] ); ?> tips" data-tip="<?php echo esc_attr( $option['description'] ); ?>">
                <?php echo esc_html( $option['label'] ); ?>:
                <input type="checkbox" name="<?php echo esc_attr( $option['id'] ); ?>" id="<?php echo esc_attr( $option['id'] ); ?>" <?php echo $selected_value; ?> />
            </label>
        <?php endforeach; ?>
        </span>

        <?php
    }

    /**
	 * This function is used to save the resources for added for the product.
	 *
	 * @param $booking_settings Array Booking Setting Array
	 * @param $product_id Int Product ID
	 * @since 4.6.0
	 */

    public static function save_product_settings( $booking_settings, $product_id ) {
        
        $booking_settings[ '_bkap_resource' ] = '';

        
        if ( isset( $_POST[ '_bkap_resource' ] ) && "on" == $_POST[ '_bkap_resource' ] ) {
            $booking_settings[ '_bkap_resource' ] = "on";
            update_post_meta( $product_id, '_bkap_resource', $_POST['_bkap_resource'] );
        }else{
        	update_post_meta( $product_id, '_bkap_resource', '' );
        }
        
        if ( isset( $_POST['bkap_product_resource_lable'] ) ) {
        	update_post_meta( $product_id, '_bkap_product_resource_lable', $_POST['bkap_product_resource_lable'] );	
        }

        if ( isset( $_POST['bkap_product_resource_selection'] ) ) {
			update_post_meta( $product_id, '_bkap_product_resource_selection', $_POST['bkap_product_resource_selection'] );
        }
        
        $resource_id = array();
        
        if ( isset( $_POST['resource_id'] ) ) {

        	$resource_id = $_POST['resource_id'];

        	update_post_meta( $product_id, '_bkap_product_resources', $resource_id );

        }
        
        $resource_cost = array();
        
        if ( isset( $_POST['resource_cost'] ) ) {
        	
        	foreach ( $resource_id as $key => $value ) {
        		$resource_cost[$value] = $_POST['resource_cost'][$key];
        	}

        	update_post_meta( $product_id, '_bkap_resource_base_costs', $resource_cost );
        }

        return $booking_settings;
    }

    /**
	 * This function is used to save the resources for added for the product.
	 *
	 * @since 4.6.0
	 */

    public function bkap_load_resource_ajax_admin() {
        add_action( 'wp_ajax_bkap_add_resource',    array( &$this, 'bkap_add_resource' ) );

        // ajax for deleting a single resource.
        add_action( 'wp_ajax_bkap_delete_resource', array( &$this, 'bkap_delete_resource' ) );
    }

    /**
     *  Deletinng the resource
     */
    public static function bkap_delete_resource() {

        $product_id           = intval( $_POST['post_id'] );
        $resource_id          = intval( $_POST['delete_resource'] );

        $bkap_resource_base_costs   = get_post_meta( $product_id, "_bkap_resource_base_costs", true );
        $bkap_product_resources     = get_post_meta( $product_id, "_bkap_product_resources", true );
            
        if( $bkap_resource_base_costs != "" ){
            
            if ( array_key_exists( $resource_id, $bkap_resource_base_costs ) ) {
                unset( $bkap_resource_base_costs[ $resource_id ] );
            }
        }

        if( $bkap_product_resources != "" ){
            
            if ( in_array( $resource_id, $bkap_product_resources ) ) {                

                $key = array_search( $resource_id, $bkap_product_resources );
                unset( $bkap_product_resources[ $key ] );
                $bkap_product_resources = array_values( $bkap_product_resources );                
            }
        }

        update_post_meta( $product_id, "_bkap_resource_base_costs", $bkap_resource_base_costs );
        update_post_meta( $product_id, "_bkap_product_resources", $bkap_product_resources );
        
        die();
    }

    /**
	 * 
	 */
	public static function bkap_add_resource() {

		$post_id           = intval( $_POST['post_id'] );
		$loop              = intval( $_POST['loop'] );
		$add_resource_id   = intval( $_POST['add_resource_id'] );
		$add_resource_name = wc_clean( $_POST['add_resource_name'] );

		if ( ! $add_resource_id ) {
			
			$add_resource_id = BKAP_Product_Resource::bkap_create_resource( $add_resource_name );
		} else {
			$resource = new BKAP_Product_Resource( $add_resource_id );
		}	

		if ( $add_resource_id ) {
			// $product        = new WC_Product_Booking( $post_id );
			// $resource_ids   = $product->get_resource_ids();

			$resource = new BKAP_Product_Resource( $add_resource_id );
			$resource_ids = array();

			if ( in_array( $add_resource_name, $resource_ids ) ) {
				wp_send_json( array( 'error' => __( 'The resource has already been linked to this product', 'woocommerce-bookings' ) ) );
			}

			$resource_ids[] = $add_resource_id;
			//$product->set_resource_ids( $resource_ids );
			//$product->save();

			// get the post object due to it is used in the included template
			$post = get_post( $post_id );

			ob_start();
			
			include( BKAP_BOOKINGS_TEMPLATE_PATH . 'meta-boxes/html-bkap-resource.php' );
			
			wp_send_json( array( 'html' => ob_get_clean() ) );
		}

		wp_send_json( array( 'error' => __( 'Unable to add resource', 'woocommerce-booking' ) ) );
	}

	

    public static function bkap_resource_tab( $product_id ) {        
        
    	$bkap_resource_option   = get_post_meta( $product_id , '_bkap_resource', true );
        
        $selected_value = 'display:none;';
        if( $bkap_resource_option == "on" ){
            $selected_value = '';
        }

        ?>		
		<li>
			<a id="resource_tab_settings" class="bkap_tab" style="<?php echo $selected_value;?>">
				<i class="fa fa-users" aria-hidden="true"></i>
				<?php _e( 'Resource', 'woocommerce-booking' ); ?>
			</a>
		</li>		
		<?php
    }

    public static function bkap_resource_settings( $product_id ) {
    	
	    $booking_settings   = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
	    
	    ?>
        <div id="bkap_resource_settings_page">
		    
        	<?php
        	 wc_get_template( 
                    'meta-boxes/html-bkap-resources.php', 
                    array( 'product_id'	=> $product_id ), 
                    'woocommerce-booking/', 
                    BKAP_BOOKINGS_TEMPLATE_PATH );
        	?>

            <div id='resource_update_notification' style='display:none;'></div>
		</div>
        <?php

    }

    /**
	 * Get ids of bkap_resource post.
	 *
	 * @return array
	 */
	public static function bkap_get_resource_ids() {
		
		$all_resource_ids 	= array();
		$args 				= array('post_type'  => 'bkap_resource','posts_per_page'=> -1,);
		$resources 			= get_posts( $args );
		
		if( count( $resources ) > 0 ){
			foreach ( $resources as $key => $value ) {
				$all_resource_ids[] = $value->ID;
			}
		}
		return $all_resource_ids;
	}

	/**
	 * Get all resource posts.
	 *
	 * @return array
	 */
	public static function bkap_get_all_resources() {
		
		$args 				= array('post_type'  => 'bkap_resource','posts_per_page'=> -1,);
		$resources 			= get_posts( $args );		
		
		return $resources;
	}

	/**
	 * Get resources added for product.
	 *
	 * @param $product_id Int Product ID
	 * @return array
	 */

	public static function bkap_get_product_resources( $product_id ) {
		$product_resource_ids = get_post_meta ( $product_id, '_bkap_product_resources', true );			
		
		return $product_resource_ids;
	}

	/**
	 * Get resources costs for product.
	 *
	 * @param $product_id Int Product ID
	 * @return array
	 */

	public static function bkap_get_resource_costs( $product_id ) {
		$product_resource_ids = get_post_meta ( $product_id, '_bkap_resource_base_costs', true );			
		
		return $product_resource_ids;
	}
    
	/**
	 * Get resource lable.
	 *
	 * @param $product_id Int Product ID
	 * @return string
	 */

	public static function bkap_get_resource_label( $product_id ) {
		$product_resource_label = get_post_meta ( $product_id, '_bkap_product_resource_lable', true );			
		
		return $product_resource_label;
	}

	/**
	 * Get selected type of resource.
	 *
	 * @param $product_id Int Product ID
	 * @return string
	 */

	public static function bkap_product_resource_selection( $product_id ) {
		$bkap_product_resource_selection = get_post_meta ( $product_id, '_bkap_product_resource_selection', true );			
		
		return $bkap_product_resource_selection;
	}


	/**
	 * Get resource option.
	 *
	 * @param $product_id Int Product ID
	 * @return string
	 */
	public static function bkap_resource( $product_id ) {
		$bkap_resource = get_post_meta ( $product_id, '_bkap_resource', true );			
		
		return $bkap_resource;
	}	
}

$class_bkap_product_resource = new Class_Bkap_Product_Resource;
?>