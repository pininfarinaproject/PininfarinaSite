<?php

/**
 * Bookings and Appointment Plugin for WooCommerce
 *
 * Template for Bookings Only Date Setting. This template shall be resued on Cart, Checkout and My Account Pages
 *
 * @author      Tyche Softwares
 * @package     Bookings and Appointment Plugin
 */ 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Set the Session gravity forms option total to 0
$_SESSION['booking_gravity_forms_option_price'] = 0;

if( ( isset( $booking_settings['booking_fixed_block_enable'] ) && 
	$booking_settings['booking_fixed_block_enable']  != "yes" ) || 
	!isset( $booking_settings['booking_fixed_block_enable'] ) ) :
?>
	
	<div id="bkap-booking-form" class="bkap-booking-form">

<?php
endif;

do_action( 'bkap_before_booking_form', $product_id );
do_action( 'bkap_print_hidden_fields', $product_id );

$method_to_show = 'bkap_check_for_time_slot';
$get_method = bkap_common::bkap_ajax_on_select_date( $product_id );

if( isset( $get_method ) && $get_method == 'multiple_time' ) {
	$method_to_show = apply_filters( 'bkap_function_slot', '' );
}

// default global settings
if( $global_settings == '') {
	$global_settings = new stdClass();
	$global_settings->booking_date_format = 'd MM, yy';
	$global_settings->booking_time_format = '12';
	$global_settings->booking_months = '1';
}

// fetch specific booking dates
$booking_dates_arr = array();
if( isset( $booking_settings['booking_specific_date'] ) ){
	$booking_dates_arr = $booking_settings['booking_specific_date'];
}

$booking_dates_str = "";
if( isset( $booking_settings['booking_specific_booking'] ) && 
	$booking_settings['booking_specific_booking'] == "on" ){

	if( !empty( $booking_dates_arr ) ){

		// @since 4.0.0 they are now saved as date (key) and lockout (value)						
		foreach ( $booking_dates_arr as $k => $v ) {
			$booking_dates_str .= '"'.$k.'",';
		}					
	}
	$booking_dates_str = substr( $booking_dates_str, 0, strlen( $booking_dates_str )-1 );		
}

?>
	<input 
		type="hidden" 
		name="wapbk_booking_dates" 
		id="wapbk_booking_dates" 
		value='<?php echo $booking_dates_str; ?>'
	>
<?php

$display_template = true;
if( isset( $booking_settings[ 'booking_enable_time' ] ) && $booking_settings[ 'booking_enable_time' ] == 'on' ) {
    $display_template = false; //assume no time slots are present

    $recurring_date_array = ( isset( $booking_settings[ 'booking_recurring' ] ) ) ? $booking_settings[ 'booking_recurring' ] : array();
    if( is_array( $booking_settings[ 'booking_recurring' ] ) && count( $booking_settings[ 'booking_recurring' ] ) > 0 && $booking_settings[ 'booking_recurring_booking' ] == "on" ) {
        foreach ( $booking_settings[ 'booking_recurring' ] as $wkey => $wval ) {

            // for time slots, enable weekday only if 1 or more time slots are present
            if ( isset ( $wval ) && $wval == 'on' && array_key_exists( $wkey, $booking_settings[ 'booking_time_settings' ] ) && count( $booking_settings[ 'booking_time_settings' ][ $wkey ] ) > 0 ) {
                $display_template = true;
            }

        }
    }

    if (! $display_template) {
        $display_template = bkap_common::bkap_check_specific_date_has_timeslot ( $product_id );
    }

}

// If Multiple Nights is enabled but all the Weekdays are disabled then do not show template.
if( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
    $recurring_dates = get_post_meta( $product_id, '_bkap_recurring_weekdays' );
    if( isset( $recurring_dates[0] ) ) {
        foreach ( $recurring_dates[0] as $recur_key => $recur_value ) {
            if( isset( $recur_value ) && $recur_value != "on" ) {
                $display_template = false;
            }else if ( isset( $recur_value ) && $recur_value == "on" ) {
                $display_template = true;
                break;
            }
        }
    }
}

if( $display_template ) {

    // Display the stock div above the dates
    $availability_display = false;
    if ( isset( $global_settings->booking_availability_display ) && $global_settings->booking_availability_display == 'on' ) {
    	if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && 'on' == $booking_settings[ 'booking_enable_multiple_day' ] ) { 
    		$available_stock = __( 'Unlimited ', 'woocommerce-booking' );
    		
    		if ( isset( $booking_settings['booking_date_lockout'] ) && $booking_settings['booking_date_lockout'] > 0 ) {
    			$available_stock = $booking_settings['booking_date_lockout'];
    		}
    		
    		$total_stock_message = get_option( 'book_stock-total' );
    		$total_stock_message = str_replace( 'AVAILABLE_SPOTS', $available_stock, $total_stock_message );
    	} else {
    		$total_stock_message = __( 'Select a date to view available bookings.', 'woocommerce-booking' );
    		if ( isset( $booking_settings['enable_inline_calendar'] ) && $booking_settings['enable_inline_calendar'] == 'on' ){
    			$total_stock_message = "";
    		}
    	}
    
    	$availability_display = true;
    }
    
    $calendar_icon_file = get_option( 'bkap_calendar_icon_file' );
    if ( $calendar_icon_file != '' && $calendar_icon_file != 'none' ) {
    	$calendar_src = plugins_url().'/woocommerce-booking/images/' . $calendar_icon_file;
    }elseif ( $calendar_icon_file != 'none' ) {
    	$calendar_src = plugins_url().'/woocommerce-booking/images/calendar1.gif';
    }
    $bkap_inline = "";
    if ( isset( $booking_settings['enable_inline_calendar'] ) && "on" == $booking_settings['enable_inline_calendar'] ){
        $bkap_inline = "on";
    }
    
    ?>
    	
    	<?php if ( $availability_display === true ) : ?>	
    		<div id="show_stock_status" name="show_stock_status" class="show_stock_status" >
    			<?php echo __( $total_stock_message, 'woocommerce-booking' ); ?>
    		</div>
    	<?php endif; ?>
    
    	<div class="bkap_start_date" id="bkap_start_date">
    		<label class="book_start_date_label" style="margin-top:1em;">
    			<?php echo __( ( '' !== get_option( "book_date-label" ) ? get_option( "book_date-label" ): 'Start Date' ) , "woocommerce-booking" ); ?>:
    		</label>
    
    		<input 
    			type="text" 
    			id="booking_calender" 
    			name="booking_calender" 
    			class="booking_calender" 
    			style="cursor: text!important;" 
    			readonly
    		/>
    
    		<?php
    		  if ( $bkap_inline == "" ) :
    		      if ( isset( $calendar_src ) ) :
    		?>
    			<img 
    				src="<?php echo $calendar_src; ?>" 
    				width="20" 
    				height="20" 
    				style="cursor:pointer!important;" 
    				id ="checkin_cal"
    			/>
    		<?php
    		      endif;
    		   endif;
    		?>
    	
    		<div id="inline_calendar"></div>
    	</div>
    
    <?php
    
    if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
    
    	?>
    		<div class="bkap_end_date" id="bkap_end_date">
    			<label class ="book_end_date_label">
    				<?php echo __( ( '' !== get_option( "checkout_date-label" )? get_option( "checkout_date-label" ): 'End Date' ) , "woocommerce-booking" ) ?>: 
    			</label>
    
    			<input 
    				type="text" 
    				id="booking_calender_checkout" 
    				name="booking_calender_checkout" 
    				class="booking_calender" 
    				style="cursor: text!important;" 
    				readonly
    			/>
    
    			<?php 
    			if ( $bkap_inline == "" ) :
    			     if ( isset( $calendar_src ) ) :
    			?>
    				<img 
    					src="<?php echo $calendar_src; ?>" 
    					width="20" 
    					height="20" 
    					style="cursor:pointer!important;" 
    					id ="checkout_cal"
    				/>
    			<?php
    			     endif;
    			endif;
    			?>
    
    			<div id="inline_calendar_checkout"></div>
    		</div>
    	<?php
    }
    
    ?>
    	<div id="show_time_slot" name="show_time_slot" class="show_time_slot">
    	    	
    	    <?php 
    	    	if( isset ( $booking_settings[ 'booking_enable_time' ] ) && 
    	    		$booking_settings[ 'booking_enable_time' ] == 'on' && 
    	    		isset( $booking_settings[ 'booking_time_settings' ] ) ) : ?>
                
                	<label> <?php echo __( ( '' !== get_option(' book_time-label' ) ? get_option(' book_time-label' ) : 'Booking Time' ), 'woocommerce-booking' ); ?>: </label><br/>
                
                	<?php 
                		if( isset( $booking_settings['enable_inline_calendar'] ) && 
                			$booking_settings['enable_inline_calendar'] != 'on' ) : ?>
                    	
                    	<div id="cadt"> <?php echo __( 'Choose a date above to see available times.', 'woocommerce-booking' ); ?>
                    	</div>
                	
            		<?php endif; ?>
    
        	<?php endif; ?>
            
        </div>
    
    <?php
    if( !isset( $booking_settings['booking_enable_multiple_day'] ) || 
    	( isset( $booking_settings['booking_enable_multiple_day'] ) && 
    		$booking_settings['booking_enable_multiple_day'] != "on" ) ) {
                
                do_action( 'bkap_display_price_div', $product_id );
    }
    
    do_action( "bkap_before_add_to_cart_button", $booking_settings );
    
    ?>
    	</div>
    
    	<div id="ajax_img" name="ajax_img"> 
    		<img src="<?php echo plugins_url() . '/woocommerce-booking/images/ajax-loader.gif'?>"> 
    	</div>
	<?php 
} else {
    _e( 'The product is currently unavailable for booking. Please try again later.', 'woocommerce-booking' );

    ?>
    </div>
    <?php
}?>