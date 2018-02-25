<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * BKAP_Payment_Gateway class
 */
if ( class_exists( 'WC_Payment_Gateway' ) ) { 
    class BKAP_Payment_Gateway extends WC_Payment_Gateway {
    
    	/**
    	 * Constructor for the gateway.
    	 */
    	public function __construct() {
    		$this->id                = 'bkap-booking-gateway';
    		$this->icon              = '';
    		$this->has_fields        = false;
    		$this->method_title      = __( 'Check Booking Availability', 'woocommerce-booking' );
    		$this->title             = $this->method_title;
    		$this->order_button_text = __( 'Request Confirmation', 'woocommerce-booking' );
    
    		// Actions
    		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
    	}
    	
    	/**
    	 * 
    	 */
    	public function admin_options() {
    	    $title = ( ! empty( $this->method_title ) ) ? $this->method_title : __( 'Settings', 'woocommerce-booking' ) ;
    	
    	    echo '<h3>' . $title . '</h3>';
    	
    	    echo '<p>' . __( 'This is fictitious payment method used for bookings that require confirmation.', 'woocommerce-booking' ) . '</p>';
    	    echo '<p>' . __( 'This gateway requires no configuration.', 'woocommerce-booking' ) . '</p>';
    	
    	    // Hides the save button
    	    echo '<style>p.submit input[type="submit"] { display: none }</style>';
    	}
    	
    	/**
    	 * 
    	 * @param unknown $order_id
    	 * @return multitype:string NULL
    	 */
    	public function process_payment( $order_id ) {
    	    $order = new WC_Order( $order_id );
    	
    	    // Add meta
    	    update_post_meta( $order_id, '_bkap_pending_confirmation', '1' );
    	
    	    // Add custom order note.
    	    $order->add_order_note( __( 'This order is awaiting confirmation from the shop manager', 'woocommerce-booking' ) );
    	
    	    // Remove cart
    	    WC()->cart->empty_cart();
    	
    	    // Return thankyou redirect
    	    return array(
    	        'result' 	=> 'success',
    	        'redirect'	=> $this->get_return_url( $order )
    	    );
    	}
    	
    	/**
    	 * 
    	 * @param unknown $order_id
    	 */
    	public function thankyou_page( $order_id ) {
    	    $order = new WC_Order( $order_id );
    	    
    	    if ( 'completed' == $order->get_status() ) {
    	        echo '<p>' . __( 'Your booking has been confirmed. Thank you.', 'woocommerce-booking' ) . '</p>';
    	    } else {
    	        echo '<p>' . __( 'Your booking is awaiting confirmation. You will be notified by email as soon as we\'ve confirmed availability.', 'woocommerce-booking' ) . '</p>';
    	    }
    	}
    	
    }// end of class
}