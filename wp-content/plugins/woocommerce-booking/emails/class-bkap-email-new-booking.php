<?php 
/**
 * New Booking Email
 *
 * An email sent to the admin when a new booking is created.
 * 
 * @class       BKAP_Email_New_Booking
 * @extends     WC_Email
 *
 */
class BKAP_Email_New_Booking extends WC_Email {
    
    function __construct() {
        
        $this->id                   = 'bkap_new_booking';
        $this->title                = __( 'New Booking', 'woocommerce-booking' );
        $this->description          = __( 'New booking emails are sent to the admin for a new booking. This email is received when a Pending confirmation booking is created.', 'woocommerce-booking' );
        
        $this->heading              = __( 'New booking', 'woocommerce-booking' );
        $this->heading_confirmation = __( 'Confirm booking', 'woocommerce-booking' );
        $this->subject              = __( '[{blogname}] New booking for {product_title} (Order {order_number}) - {order_date}', 'woocommerce-booking' );
        $this->subject_confirmation = __( '[{blogname}] A new booking for {product_title} (Order {order_number}) is awaiting your approval - {order_date}', 'woocommerce-booking' );
        
        $this->template_html    = 'emails/admin-new-booking.php';
        $this->template_plain   = 'emails/plain/admin-new-booking.php';
        
        // Triggers for this email
        add_action( 'bkap_pending_booking_notification', array( $this, 'queue_notification' ) );
        add_action( 'bkap_new_booking_notification', array( $this, 'trigger' ) );
        add_action( 'bkap_admin_new_booking_notification', array( $this, 'trigger' ) );
        
        // Call parent constructor
        parent::__construct();
        
        // Other settings
        $this->template_base = BKAP_BOOKINGS_TEMPLATE_PATH;
        $this->recipient     = $this->get_option( 'recipient', get_option( 'admin_email' ) );
        
    }
    
    public function queue_notification( $order_id ) {
        
        $order = new WC_order( $order_id );
        $items = $order->get_items();
        foreach ( $items as $item_key => $item_value ) {
            do_action('bkap_admin_new_booking_notification', $item_key );
        }
    }
    
    function trigger( $item_id ) {
        
        $send_email = true;
        
        // if the item is a part of a bundle, no email should be sent as one will be sent for the main bundle
        $bundled_by = wc_get_order_item_meta( $item_id, '_bundled_by' );
        
        if ( isset( $bundled_by ) && '' != $bundled_by ) {
            $send_email = false;
        }
        // add a filter using which an addon can modify the email send status
        // setting it to true will send the email
        // setting it to false will make sure that the email is not sent for the given item
        $send_email = apply_filters( 'bkap_send_new_email', $send_email, $item_id );
        
        $enabled = $this->is_enabled();
        
        if ( $item_id && $send_email && $enabled ) {
        
            $this->object = bkap_common::get_bkap_booking( $item_id );
            
            if ( 'pending-confirmation' == $this->object->item_booking_status ) {
            
                $key = array_search( '{product_title}', $this->find );
                if ( false !== $key ) {
                    unset( $this->find[ $key ] );
                    unset( $this->replace[ $key ] );
                }
                
                $this->find[]    = '{product_title}';
                $this->replace[] = $this->object->product_title;
        
                $booking_settings = get_post_meta( $this->object->product_id, 'woocommerce_booking_settings', true );
              
                // if the product has a tour operator assigned, then the approval email needs to be sent to the operator
                if ( function_exists( 'is_bkap_tours_active' ) && is_bkap_tours_active() ) {
                    if ( isset( $booking_settings[ 'booking_tour_operator' ] ) && $booking_settings[ 'booking_tour_operator' ] > 0 ) {
                        $user_info = get_userdata( $booking_settings[ 'booking_tour_operator' ] );
                        $user_email = $user_info->user_email;
                        $this->recipient = $user_email;
                    }
                }
                
                if ( $this->object->order_id ) {
                    
                    $this->find[]    = '{order_date}';
                    $this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
        
                    $this->find[]    = '{order_number}';
                    $this->replace[] = $this->object->order_id;
                } else {
                    
                    $this->find[]    = '{order_date}';
                    $this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->item_hidden_date ) );
        
                    $this->find[]    = '{order_number}';
                    $this->replace[] = __( 'N/A', 'woocommerce-booking' );
                }
    
                
                if ( ! $this->get_recipient() ) {
                    return;
                }
        
                $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
            }
        }
    }
    
    function get_content_html() {
        ob_start();
        wc_get_template( $this->template_html, array(
        'booking'       => $this->object,
        'email_heading' => $this->get_heading()
        ), 'woocommerce-booking/', $this->template_base );
        return ob_get_clean();
    }
    
    function get_content_plain() {
        ob_start();
        wc_get_template( $this->template_plain, array(
            'booking'       => $this->object,
            'email_heading' => $this->get_heading()
            ), 'woocommerce-booking/', $this->template_base );
        return ob_get_clean();
    }
    
    function get_subject() {
        
        $order = new WC_order( $this->object->order_id );
        if ( bkap_common::bkap_order_requires_confirmation( $order ) && $this->object->item_booking_status == 'pending-confirmation' ) {
            return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->get_option( 'subject', $this->subject_confirmation ) ), $this->object );
        } else {
            return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->get_option( 'subject', $this->subject ) ), $this->object );
        }
    }
    
    public function get_heading() {
        
        $order = new WC_order( $this->object->order_id );
        if ( bkap_common::bkap_order_requires_confirmation( $order ) && $this->object->item_booking_status == 'pending-confirmation' ) {
            return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->get_option( 'heading', $this->heading_confirmation  ) ), $this->object );
        } else {
            return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->get_option( 'heading', $this->heading ) ), $this->object );
        }
    }
    
    function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' 		=> __( 'Enable/Disable', 'woocommerce-booking' ),
                'type' 			=> 'checkbox',
                'label' 		=> __( 'Enable this email notification', 'woocommerce-booking' ),
                'default' 		=> 'yes'
            ),
            'recipient' => array(
                'title'         => __( 'Recipient', 'woocommerce-booking' ),
                'type'          => 'text',
                'description'   => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s', 'woocommerce-booking' ), get_option( 'admin_email' ) ),
                'default'       => get_option( 'admin_email' )
            ),
            'subject' => array(
                'title' 		=> __( 'Subject', 'woocommerce-booking' ),
                'type' 			=> 'text',
                'description' 	=> sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce-booking' ), $this->subject ),
                'placeholder' 	=> '',
                'default' 		=> ''
            ),
            'heading' => array(
                'title' 		=> __( 'Email Heading', 'woocommerce-booking' ),
                'type' 			=> 'text',
                'description' 	=> sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce-booking' ), $this->heading ),
                'placeholder' 	=> '',
                'default' 		=> ''
            ),
            'email_type' => array(
                'title' 		=> __( 'Email type', 'woocommerce-booking' ),
                'type' 			=> 'select',
                'description' 	=> __( 'Choose which format of email to send.', 'woocommerce-booking' ),
                'default' 		=> 'html',
                'class'			=> 'email_type',
                'options'		=> array(
                    'plain'		 	=> __( 'Plain text', 'woocommerce-booking' ),
                    'html' 			=> __( 'HTML', 'woocommerce-booking' ),
                    'multipart' 	=> __( 'Multipart', 'woocommerce-booking' ),
                )
            )
        );
    }
    
}
return new BKAP_Email_New_Booking();
?>