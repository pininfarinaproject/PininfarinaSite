<?php 
/**
* Main model class for all bookings, this handles all the data
*/
class BKAP_Booking {

	/** @public int */
	public $id;

	/** @public string */
	public $booking_date;

	/** @public string */
	public $start;

	/** @public string */
	public $end;

	/** @public bool */
	public $all_day;

	/** @public string */
	public $modified_date;

	/** @public object */
	public $post;

	/** @public int */
	public $product_id;

	/** @public object */
	public $product;

	/** @public int */
	public $order_id;

	/** @public object */
	public $order;

	/** @public int */
	public $customer_id;

	/** @public string */
	public $status;

	/** @public string */
	public $gcal_event_uid;
	
	/** @public array - contains all post meta values for this booking */
	public $custom_fields;

	/** @public bool */
	public $populated;

	/** @private array - used to temporarily hold order data for new bookings */
	private $order_data;

	/**
	 * Constructor, possibly sets up with post or id belonging to existing booking
	 * or supplied with an array to construct a new booking
	 * @param int/array/obj $booking_data
	 */
	public function __construct( $booking_data = false ) {
		$populated = false;

		if ( is_array( $booking_data ) ) {
			$this->order_data = $booking_data;
			$populated = false;
		} else if ( is_object( $booking_data ) && isset( $booking_data->ID ) ) {
			$this->post = $booking_data;
			$populated = $this->populate_data( $booking_data->ID );
		} else if ( is_int( intval( $booking_data ) ) && 0 < $booking_data ) {
			$populated = $this->populate_data( $booking_data );
		}

		$this->populated = $populated;
	}
	
	/**
	 * Populate the data with the id of the booking provided
	 * Will query for the post belonging to this booking and store it
	 * @param int $booking_id
	 */
	public function populate_data( $booking_id ) {
	    if ( ! isset( $this->post ) ) {
	        $post = get_post( $booking_id );
	    }else {
	    	$post = $this->post;
	    }
	
	    if ( is_object( $post ) ) {
	        // We have the post object belonging to this booking, now let's populate
	        $this->id            = $post->ID;
	        $this->booking_date  = $post->post_date;
	        $this->modified_date = $post->post_modified;
	        $this->customer_id   = $post->post_author;
	        $this->custom_fields = get_post_meta( $this->id );
	        $this->status        = $post->post_status;
	        $this->order_id      = $post->post_parent;
	
	        // Define the data we're going to load: Key => Default value
	        $load_data = array(
	            'product_id'  => '',
	            'qty'         => 1,
	            'resource_id' => '',
	            'persons'     => array(),
	            'cost'        => '',
	            'start'       => '',
	            'customer_id' => '',
	            'end'         => '',
	            'all_day'     => 0,
	            'parent_id'   => 0,
	            'variation_id'=> 0,
	            'gcal_event_uid' => false,
	        );
	
	        // Load the data from the custom fields (with prefix for this plugin)
	        $meta_prefix = '_bkap_';
	
	        foreach ( $load_data as $key => $default ) {
	            if ( isset( $this->custom_fields[ $meta_prefix . $key ][0] ) && $this->custom_fields[ $meta_prefix . $key ][0] !== '' ) {
	                $this->$key = maybe_unserialize( $this->custom_fields[ $meta_prefix . $key ][0] );
	            } else {
	                $this->$key = $default;
	            }
	        }
	
	        // Start and end date converted to timestamp
	        $this->start = strtotime( $this->start );
	        $this->end   = strtotime( $this->end );
	
	        // Save the post object itself for future reference
	        $this->post = $post;
	        return true;
    	}
	
	    return false;
	}
	
	/**
    * Actual create for the new booking belonging to an order
    * @param string Status for new order
    */
	public function create( $status = 'confirmed' ) {
	    $this->new_booking( $status, $this->order_data );
	}
	
    /**
	 * Makes the new booking belonging to an order
	 * @param string $status The status for this new booking
	 * @param array $order_data Array with all the new order data
	 */
	private function new_booking( $status, $order_data ) {
        global $wpdb;
	
	    $order_data = wp_parse_args( $order_data, array(
	        'user_id'           => 0,
	        'resource_id'       => '',
	        'product_id'        => '',
	        'order_item_id'     => '',
            'persons'           => array(),
	        'cost'              => '',
	        'start_date'        => '',
	        'end_date'          => '',
	        'all_day'           => 0,
	        'parent_id'         => 0,
	        'qty'               => 1,
	        'variation_id'      => 0,
	        'gcal_event_uid'    => false,
	    ) );
	
	    $order_id = $order_data[ 'parent_id' ];
	    
        $booking_data = array(
	        'post_type'   => 'bkap_booking',
	        'post_title'  => sprintf( __( 'Booking &ndash; %s', 'woocommerce-booking' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Booking date parsed by strftime', 'woocommerce-booking' ) ) ),
	        'post_status' => $status,
	        'ping_status' => 'closed',
	        'post_parent' => $order_id
	    );
	
	    $this->id = wp_insert_post( $booking_data );
	
	    // Setup the required data for the current user
	    if ( ! $order_data['user_id'] ) {
	        if ( is_user_logged_in() ) {
	            $order_data['user_id'] = get_current_user_id();
	        } else {
	            $order_data['user_id'] = 0;
	        }
	    }
	
       $meta_args = array(
	        '_bkap_order_item_id' => $order_data['order_item_id'],
	        '_bkap_product_id'    => $order_data['product_id'],
	        '_bkap_resource_id'   => $order_data['resource_id'],
	        '_bkap_persons'       => $order_data['persons'],
	        '_bkap_cost'          => $order_data['cost'],
	        '_bkap_start'         => $order_data['start_date'],
	        '_bkap_end'           => $order_data['end_date'],
	        '_bkap_all_day'       => intval( $order_data['all_day'] ),
	        '_bkap_parent_id'     => $order_data['parent_id'],
	        '_bkap_customer_id'   => $order_data['user_id'],
            '_bkap_qty'           => $order_data[ 'qty' ],
            '_bkap_variation_id'  => $order_data[ 'variation_id' ],
            '_bkap_gcal_event_uid'=> $order_data[ 'gcal_event_uid' ],
	    );

	    foreach ( $meta_args as $key => $value ) {
	        update_post_meta( $this->id, $key, $value );
	    }
	
	    do_action( 'bkap_new_booking', $this->id );
	}
	
    /**
	 * Returns the id of this booking
	 * @return Id of the booking or false if booking is not populated
	 */
	public function get_id() {
	    if ( $this->populated ) {
	        return $this->id;
	    }
	
	    return false;
	}
	
	/**
	 * Returns the status of this booking
	 * @param Bool to ask for pretty status name (if false)
	 * @return String of the booking status
	 */
	public function get_status( $raw = true ) {
	    if ( $this->populated ) {
        if ( $raw ) {
	            return $this->status;
	        } else {
	            $status_object = get_post_status_object( $this->status );
	            return $status_object->label;
	        }
	    }
	
	    return false;
	}
	
	/**
	 * Set the new status for this booking
	 * @param string $status
	 * @return bool
	 */
	public function update_status( $status ) {
        $current_status   = $this->get_status( true );
	    $allowed_statuses = bkap_common::get_bkap_booking_statuses();
	    
	    if ( $this->populated ) {
	        
	        if ( array_key_exists( $status, $allowed_statuses ) ) {
	            
	            wp_update_post( array( 'ID' => $this->id, 'post_status' => $status ) );

                // Trigger actions
	            do_action( 'bkap_post_' . $current_status . '_to_' . $status, $this->id );
	            do_action( 'bkap_post_' . $status, $this->id );
	
	            // Note in the order
	            if ( $order = $this->get_order() ) {
	                $order->add_order_note( sprintf( __( 'Booking #%d status changed from "%s" to "%s"', 'woocommerce-booking' ), $this->id, $current_status, $status ) );
	            }
	
	            return true;
	        }
	    }
	
	    return false;
	}
	
	/**
	 * Returns the object of the order corresponding to this booking
	 * @return Order object or false if booking is not populated
	 */
	public function get_order() {
	    if ( empty( $this->order ) ) {
	        if ( $this->populated && ! empty( $this->order_id ) && 'shop_order' === get_post_type( $this->order_id ) ) {
	            $this->order = wc_get_order( $this->order_id );
	        } else {
	            return false;
	        }
	    }
	
	    return $this->order;
	}
	

	/**
	 * Returns the Customer ID
	 * @return Customer ID
	 * @since 4.1.0
	 */
	public function get_customer_id() {
	
	    if ( ! empty( $this->customer_id ) ) {
	        return $this->customer_id;
	    } else {
	        return false;
	    }
	}
	
	/**
	 * Returns the Order ID
	 * @return Order ID
	 * @since 4.1.0
	 */
	public function get_order_id() {
	
	    if ( empty( $this->order_id ) ) {
	        if ( ! empty( $this->order ) ) {
	            $order_id = $this->order->get_id();
	            return $order_id;
	        }
	    } else {
	        return $this->order_id;
	    }
	}

	/**
	 * Returns the Product ID
	 * @return Product ID
	 * @since 4.1.0
	 */
	public function get_product_id() {
	     
	    if ( empty( $this->product_id ) ) {
	        if ( ! empty( $this->product ) ) {
	            return $this->product->id;
	        }
	    } else {
	        return $this->product_id;
	    }
	}
	
	/**
	 * Returns the Product Object
	 * @return Product Object
	 * @since 4.1.0
	 */
	public function get_product() {
	     
	    if ( empty( $this->product ) ) {
	        if ( ! empty( $this->product_id ) ) {
	            return wc_get_product( $this->product_id );
	        }
	    } else {
	        return $this->product;
	    }
	}
	
	/**
	 * Returns the Order Date
	 * @return Order Date
	 * @since 4.1.0
	 */
	public function get_date_created() {
	     
	    if ( ! empty( $this->order_id ) ) {
	        $order_post = get_post( $this->order_id );
	        if ( $order_post ) {
	        	$post_date = strtotime ( $order_post->post_date );
	        	$order_date = date( 'Y-m-d H:i:s', $post_date );
	        }else {
	        	$order_date = __( 'Order date not available', 'woocommerce-booking' );
	        }

	        return $order_date;
	    }
	}
	
	/**
	 * Returns the Customer Object
	 * @return Customer Object
	 * @since 4.1.0
	 */
	public function get_customer() {
	    $name    = '';
	    $email   = '';
	    $user_id = 0;
	
	    if ( $order = $this->get_order() ) {
	        $first_name = is_callable( array( $order, 'get_billing_first_name' ) ) ? $order->get_billing_first_name() : $order->billing_first_name;
	        $last_name  = is_callable( array( $order, 'get_billing_last_name' ) ) ? $order->get_billing_last_name()   : $order->billing_last_name;
	        $name       = trim( $first_name . ' ' . $last_name );
	        $email      = is_callable( array( $order, 'get_billing_email' ) ) ? $order->get_billing_email()           : $order->billing_email;
	        $user_id    = is_callable( array( $order, 'get_customer_id' ) ) ? $order->get_customer_id()               : $order->customer_user;
	        $name 		= 0 !== absint( $user_id ) ? $name : sprintf( _x( '%s (Guest)', 'Guest string with name from booking order in brackets', 'woocommerce-bookings' ), $name );
	    } elseif ( $this->get_customer_id() ) {
	        $user    = get_user_by( 'id', $this->get_customer_id() );
	        $name    = $user->display_name;
	        $email   = $user->user_email;
	        $user_id = $this->get_customer_id();
	    }
	    return (object) array(
	        'name'    => $name,
	        'email'   => $email,
	        'user_id' => $user_id,
	    );
	}
	
	/**
	 * Returns the Start Date
	 * @return Start Date as YmdHis
	 * @since 4.1.0
	 */
	function get_start() {
	    return $start = get_post_meta( $this->id, '_bkap_start', true );
	}
	
	/**
	 * Returns the End Date
	 * @return End Date as YmdHis
	 * @since 4.1.0
	 */
	function get_end() {
	    return $end = get_post_meta( $this->id, '_bkap_end', true );
	}
	
	/**
	 * Returns the Time Slot
	 * @return Time Slot
	 * @since 4.1.0
	 */
	function get_time() {
	    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	     
	    $time_format = $global_settings->booking_time_format;
	     
	    // Commenting since we need 24 hour format for comparision
	    //$time_format = ( $time_format === '12' ) ? 'h:i A' : 'H:i';
	    $time_format = 'H:i';
	    $start_time = date( $time_format, strtotime( $this->get_start() ) );
	     
	    $end_time = date( $time_format, strtotime( $this->get_end() ) );

	    if ( $end_time === '' || $end_time === '00:00' ) {
	        return $start_time;
	    }
	    return "$start_time - $end_time";
	}
	
	/**
	 * Returns the Start Date
	 * @return Date
	 * @since 4.2.0
	 */
	function get_start_date() {
	     
	    $start = $this->get_start();
	     
	    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	     
	    $date_formats       = bkap_get_book_arrays( 'bkap_date_formats' );
	    // get the global settings to find the date formats
	    $global_settings    = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	    $date_format_set    = $date_formats[ $global_settings->booking_date_format ];
	    return date( $date_format_set, strtotime( $start ) );
	     
	}
	
	/**
	 * Returns the End Date
	 * @return Date
	 * @since 4.2.0
	 */
	function get_end_date() {
	
	    $end_date = '';
	     
	    $start = $this->get_start();
	    $end = $this->get_end();
	     
	    if ( $start !== $end ) {
	         
	        $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	
	        $date_formats       = bkap_get_book_arrays( 'bkap_date_formats' );
	        // get the global settings to find the date formats
	        $global_settings    = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	        $date_format_set    = $date_formats[ $global_settings->booking_date_format ];
	        $end_date = date( $date_format_set, strtotime( $end ) );
	    }
	     
	    return $end_date;
	}
	
	/**
	 * Returns Start Time
	 * @return Start Time in 12/24 hr format based on settings
	 * @since 4.2.0
	 */
	function get_start_time() {
	
	    $start_time = '';
	    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	     
	    $time_format = $global_settings->booking_time_format;
	    $time_format = ( $time_format === '12' ) ? 'h:i A' : 'H:i';
	     
	    if ( '000000' !== substr( $this->get_start(), 8 ) ) {
	        $start_time = date( $time_format, strtotime( $this->get_start() ) );
	    }
	     
	    return $start_time;
	}
	
	/**
	 * Returns Start Time
	 * @return Start Time in 12/24 hr format based on settings
	 * @since 4.2.0
	 */
	function get_end_time() {
	
	    $end_time = '';
	    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	
	    $time_format = $global_settings->booking_time_format;
	    $time_format = ( $time_format === '12' ) ? 'h:i A' : 'H:i';
	     
	    if ( '000000' !== substr( $this->get_end(), 8 ) ) {
	        $end_time = date( $time_format, strtotime( $this->get_end() ) );
	    }
	     
	    return $end_time;
	}
	
	/**
	 * Returns the Booked Quantity
	 * @return int $quantity
	 * @since 4.2.0
	 */
	function get_quantity() {
	    return get_post_meta( $this->id, '_bkap_qty', true );
	}
	
	/**
	 * Returns the Booked Cost
	 * @return int $cost
	 * @since 4.2.0
	 */
	function get_cost() {
	    return get_post_meta( $this->id, '_bkap_cost', true );
	}
	
	/**
	 * Returns the Variation ID
	 * @return int $variation_id
	 * @since 4.4.0
	 */
	function get_variation_id() {
	    return get_post_meta( $this->id, '_bkap_variation_id', true );
	}
	
	/**
	 * Returns the Item ID for the booked product
	 * @return int $item_id
	 * @since 4.4.0
	 */
	function get_item_id() {
	    return get_post_meta( $this->id, '_bkap_order_item_id', true );
	}
	
	/**
	 * Returns resource
	 * @return int $item_id
	 * @since 4.6.0
	 */
	function get_resource() {
	    $resource = get_post_meta( $this->id, '_bkap_resource_id', true );
	
	    return $resource;
	}
	
	/**
	 * Returns resource title
	 * @return int $item_id
	 * @since 4.6.0
	 */
	function get_resource_title() {
	    $resource_id = get_post_meta( $this->id, '_bkap_resource_id', true );
	
	    $resource_title = "";
	    if ( $resource_id != "" ) {
	        $resource_title = get_the_title( $resource_id );
	    }
	
	    return $resource_title;
	}
}
?>