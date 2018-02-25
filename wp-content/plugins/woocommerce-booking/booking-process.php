<?php

include_once('bkap-common.php');
include_once('lang.php');

class bkap_booking_process {
	
	/******************************************************
    *  This function will disable the quantity and add to cart button on the frontend,
    *  if the Enable Booking is on from admin product page,and if "Purchase without choosing date" is disable.
    *************************************************************/		
		
	public static function bkap_before_add_to_cart() {
		global $post,$wpdb;

		$duplicate_of     =   bkap_common::bkap_get_product_id( $post->ID );
		$booking_settings =   get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
		
		if ( $booking_settings == "" || ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] != "on" ) ) {
		    return;
		}
		
		$product                =   wc_get_product( $duplicate_of );
		$product_type           =   $product->get_type();
		
		// Adding price div on the front end product page based on WC product Addon / Gravity Form / Partial Deposits Addon settings for product.
		if ( 'variable' == $product_type ) {
		    if ( is_plugin_active( 'bkap-deposits/deposits.php' ) && is_plugin_active( 'woocommerce-product-addons/woocommerce-product-addons.php' ) && is_plugin_active( 'gravityforms/gravityforms.php' )  ) {
		
		        if( is_plugin_active( 'gravityforms/gravityforms.php' ) && isset( $booking_settings['booking_partial_payment_enable'] ) && $booking_settings['booking_partial_payment_enable'] == 'yes' ){
		            add_action( 'woocommerce_single_variation',    array( 'bkap_booking_process', 'bkap_price_display' ), 19 );
		        }else{
		            add_action( 'woocommerce_single_variation',    array( 'bkap_booking_process', 'bkap_price_display' ), 9 );
		        }
		    } else {
		        add_action( 'woocommerce_single_variation',    array( 'bkap_booking_process', 'bkap_price_display' ), 9 );
		    }
		}else{
		    if ( is_plugin_active( 'bkap-deposits/deposits.php' ) && is_plugin_active( 'woocommerce-product-addons/woocommerce-product-addons.php' ) && is_plugin_active( 'gravityforms/gravityforms.php' )  ) {
		
		        if( is_plugin_active( 'gravityforms/gravityforms.php' ) && 
		        	isset( $booking_settings['booking_partial_payment_enable'] ) && $booking_settings['booking_partial_payment_enable'] == 'yes' ){
		            add_action( 'woocommerce_before_add_to_cart_button',    array( 'bkap_booking_process', 'bkap_price_display' ), 9999 );
		        }else{
		            add_action( 'woocommerce_before_add_to_cart_button',    array( 'bkap_booking_process', 'bkap_price_display' ), 9 );
		        }
		    } else {
		        add_action( 'woocommerce_before_add_to_cart_button',    array( 'bkap_booking_process', 'bkap_price_display' ), 9 );
		    }
		}
		
		if ( $booking_settings != '' && ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] == 'on') && ( isset( $booking_settings['booking_purchase_without_date'] ) && $booking_settings['booking_purchase_without_date'] != 'on') ) {
		    
		    // check the product type
		    $_product = wc_get_product( $duplicate_of );
		    if ( 'bundle' == $_product->get_type() ) {
		        ?>
		        <script type="text/javascript">
		        jQuery( document ).ready( function () {
		        	jQuery( ".bundle_price" ).hide();
		        });
		        </script>
		        <?php 
		    }
			// check the setting
		    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		    if ( isset( $global_settings->display_disabled_buttons ) && 'on' == $global_settings->display_disabled_buttons ) {
		        ?>
		        <script type="text/javascript">
    				jQuery(document).ready(function() {
    					jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
    					jQuery( ".qty" ).prop( "disabled", true );
    				});
    				
    			</script>
		        <?php 
		    } else {
    		?>
    			<script type="text/javascript">
    				jQuery(document).ready(function() {
    					jQuery( ".single_add_to_cart_button" ).hide();
    					jQuery( ".qty" ).hide();
    				});
    			</script>
    		<?php 
		    }
		    ?>
		    <script type="text/javascript">
    		    jQuery(document).ready(function() {
        		    jQuery( ".payment_type" ).hide();
        		    jQuery(".partial_message").hide();
    		    });
		</script>
		<?php 
		}
	}

	/**
	 * Adds a span to display the bookable amount on
	 * the Product page
	 * 
	 * @since 2.6.2
	 */
	public static function bkap_price_display() {
	    print( '<br><span id="bkap_price" class="price"></span><br>');
	}	
	
	public static function bkap_localize_process_script( $post_id, $edit = false ) {
	    
	    global $wpdb, $bkap_months;
	    $product_id = bkap_common::bkap_get_product_id( $post_id );
	    // booking settings
	    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );

	    //Default settings
	    $default = ( ( isset( $booking_settings['booking_recurring_booking'] ) && $booking_settings['booking_recurring_booking'] == "on" ) || ( isset( $booking_settings['booking_specific_booking'] ) && $booking_settings['booking_specific_booking'] == "on")) ? 'N' : 'Y';
	    
	    $number_of_days = 0;
	     
	    $check_time = false;
	    $timeslots_present = true;
	    if ( isset( $booking_settings[ 'booking_enable_time' ] ) && 'on' == $booking_settings[ 'booking_enable_time' ] ) {
	        $timeslots_present = false; //assume no time slots are present
	        $check_time = true;
	    }
		 
		/* For Postcode addon */
		$postcode_weekdays = array();
		$postcode_weekdays = apply_filters( 'bkap_change_postcode_weekdays', $postcode_weekdays, $product_id, $default );
		
		if( isset($postcode_weekdays) && is_array($postcode_weekdays) && !empty( $postcode_weekdays ) ) {
			$booking_settings[ 'booking_recurring' ] = $postcode_weekdays;
		}
	     
	    $recurring_date_array = ( isset( $booking_settings[ 'booking_recurring' ] ) ) ? $booking_settings[ 'booking_recurring' ] : array();
		 
		if ( empty( $postcode_weekdays ) ) {			
			foreach ( $recurring_date_array as $wkey => $wval ) {
			
				if ( $default == "Y" ) {
					$booking_settings[ 'booking_recurring' ][ $wkey ] = 'on';
				} else {
			
					if ( $booking_settings[ 'booking_recurring_booking' ] == "on" ){
						// for time slots, enable weekday only if 1 or more time slots are present
						if ( isset ( $wval ) && $wval == 'on' && $check_time && array_key_exists( $wkey, $booking_settings[ 'booking_time_settings' ] ) && count( $booking_settings[ 'booking_time_settings' ][ $wkey ] ) > 0 ) {
							$booking_settings[ 'booking_recurring' ][ $wkey ] = $wval;
							$timeslots_present = true;
						} else if ( ! $check_time ) { // when no time bookings are present, print as is
							$booking_settings[ 'booking_recurring' ][ $wkey ] = $wval;
						} else { // else set weekday to blanks
							$booking_settings[ 'booking_recurring' ][ $wkey ] = '';
						}
						if ( isset ( $wval ) && $wval == 'on' ) {
							$number_of_days++;
						}
					} else {
						$booking_settings[ 'booking_recurring' ][ $wkey ] = '';
					}
					
				}
			}
	    }

        if (! $timeslots_present) {
            $timeslots_present = bkap_common::bkap_check_specific_date_has_timeslot ( $product_id );
        }

        if( ! $timeslots_present ) {
            return;
        }
        
	    $_product = wc_get_product( $product_id );
	    $product_type = $_product->get_type();
	     
	    $global_settings     =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	    if( ! isset( $global_settings->booking_calendar_day ) )  {
	        $global_settings->booking_calendar_day = get_option( "start_of_week" );
	    }
	     
	    //	default global settings
	    if ($global_settings == '') {
	        $global_settings                        =   new stdClass();
	        $global_settings->booking_date_format   =   'd MM, yy';
	        $global_settings->booking_time_format   =   '12';
	        $global_settings->booking_months        =   '1';
	    }

	    // labels
	    $labels = array( 'holiday_label' => __( "Holiday", "woocommerce-booking" ),
	        'unavailable_label'          => __( "Unavailable for Booking", "woocommerce-booking" ),
	        'blocked_label'              => __( "Blocked", "woocommerce-booking" ),
	        'booked_label'               => __( "Booked", "woocommerce-booking" ),
	        'msg_unavailable'            => __( "Some of the dates in the selected range are unavailable. Please try another date range.", 'woocommerce-booking' ),
	        'rent_label'                 => __( 'On Rent', 'woocommerce-booking' ),
	    );
	     
	    // Additional data
	    $additional_data = array();
	    $additional_data[ 'gf_enabled' ] = ( class_exists( 'woocommerce_gravityforms' ) || class_exists( 'WC_GFPA_Main' ) ) ? 'yes' : 'no';
	     
	    $additional_data[ 'sold_individually' ]   =   get_post_meta( $product_id, '_sold_individually', true );

	    $method_to_show  =   'bkap_check_for_time_slot';
	    $get_method      =   bkap_common::bkap_ajax_on_select_date( $product_id );
	    	
	    if( isset( $get_method ) && $get_method == 'multiple_time' ) {
	        $method_to_show = apply_filters( 'bkap_function_slot', '' );
	    }
    	$additional_data[ 'method_timeslots' ] = $method_to_show;
    	
	    $additional_data[ 'product_type' ]           =   $product_type;
	     
	    // Holidays - Global as well as Product level in one string
	    
	    if ( isset( $global_settings->booking_global_holidays ) ) {
	        $book_global_holidays  =   $global_settings->booking_global_holidays;
	        $global_holidays       =   explode( ',', $global_settings->booking_global_holidays );
	        $book_global_holidays  =   substr( $book_global_holidays, 0, strlen( $book_global_holidays ) );
	        $book_global_holidays  =   '"' . str_replace( ',', '","', $book_global_holidays ) . '"';
	    } else {
	        $book_global_holidays  =   "";
	    }

	    // the holidays are now an array @since 4.0.0
	    $individual_holidays = ( isset ( $booking_settings[ 'booking_product_holiday' ] ) && $booking_settings[ 'booking_product_holiday' ] !== '' ) ? $booking_settings[ 'booking_product_holiday' ] : array();
	     
	    // default  final array
	    $holiday_array = array();
	     
	    // array format [date] => years to recur
	    foreach( $individual_holidays as $date => $years ) {
	        // add the date
	        $holiday_array[] = $date;
	    
	        // if recurring is greater than 0
	        if ( $years > 0 ) {
	            for ( $i = 1; $i <= $years; $i++ ) {
	                // add the dates for the future years
	                $holiday_array[] = date( 'j-n-Y', strtotime( '+' . $i . 'years', strtotime( $date ) ) );
	            }
	        }
	    }

        $max_days_in_years = 1;
        if ( isset( $booking_settings[ 'booking_maximum_number_days' ] ) ) {
        	$max_days_in_years = ceil( $booking_settings[ 'booking_maximum_number_days' ] / 365 );
        }

	    // get holiday ranges
	    $holiday_ranges = get_post_meta( $product_id, '_bkap_holiday_ranges', true );
	    if ( is_array( $holiday_ranges ) && count( $holiday_ranges ) > 0 ) {
	        foreach( $holiday_ranges as $ranges ) {
	             
	            // get the data
	            $start_range = $ranges[ 'start' ];
	            $end_range = $ranges[ 'end' ];
	            $recur = $ranges[ 'years_to_recur' ];

                if ( $recur > $max_days_in_years ) {
                    $recur = $max_days_in_years;
                }

	            // get the days in the range, this does not include the end date
	            $days_in_between = bkap_common::bkap_get_betweendays( $start_range, $end_range );
	             
	            // add the end date
	            $days_in_between[] = $end_range;
	            foreach( $days_in_between as $dates ) {
	                 
	                // add each date
	                $holiday_array[] = date( 'j-n-Y', strtotime( $dates ) );
	                 
	                // if recurring years is greater than 0
	                if ( $recur > 0 ) {
	                    for ( $i = 1; $i <= $recur; $i++ ) {
	                        // add the date for the future years
	                        $holiday_array[] = date( 'j-n-Y', strtotime( '+' . $i . 'years', strtotime( $dates ) ) );
	                    }
	                }
	            }
	             
	             
	        }
	    }
	     
	    $booking_holidays_string = '';
	    // create a string from the array
	    foreach( $holiday_array as $dates ) {
	        $booking_holidays_string .= '"' . $dates . '",';
	    }
	     
	    $holiday_list = $booking_holidays_string . $book_global_holidays;
	    $additional_data[ 'holidays' ] = $holiday_list;
	    
	    // fetch specific booking dates
	    $booking_dates_arr = ( isset( $booking_settings[ 'booking_specific_date' ] ) ) ? $booking_settings[ 'booking_specific_date' ] : array();
	     
	    $booking_dates_str = "";
	    
	    if( $booking_dates_arr != "" && count( $booking_dates_arr ) > 0 && count( $holiday_array ) > 0 ){
	        $booking_dates_arr = bkap_booking_process::bkap_check_specificdate_in_global_holiday( $booking_dates_arr, $holiday_array );
	    }

	    $day_date_timeslots    = get_post_meta( $product_id, '_bkap_time_settings', true );
	    $booking_type          = get_post_meta( $product_id, '_bkap_booking_type', true );
	     
	    // When Date and Time is enabled that time removing the date
	    // from the list of bookable date when date is added but no timeslot is created.
	    if( $booking_type == 'date_time' ){
	         
	        $day_date_of_timeslots = array_keys( $day_date_timeslots );
	        if ( !empty( $booking_dates_arr ) ) {
	             
	            foreach ( $booking_dates_arr as $k => $v ) {
	                if ( !in_array( $k , $day_date_of_timeslots ) ){
	                    unset( $booking_dates_arr[ $k ] );
	                }
	            }
	        }
	    }
	     
	    if ( isset( $booking_settings[ 'booking_specific_booking' ] ) && $booking_settings[ 'booking_specific_booking' ] == "on" ) {
	         
	        if( !empty( $booking_dates_arr ) ){
	            // @since 4.0.0 they are now saved as date (key) and lockout (value)
	            foreach ( $booking_dates_arr as $k => $v ) {
	                $booking_dates_str .= '"'.$k.'",';
	            }
	        }
	         
	        $booking_dates_str = substr( $booking_dates_str, 0, strlen( $booking_dates_str )-1 );
	    }
	     
	    $additional_data[ 'specific_dates' ] = $booking_dates_str;
	    
	    
	    // Wordpress Time
	    $current_time         =   current_time( 'timestamp' );
	    
	    $ranges = array();
	    
	    // custom ranges
	    $custom_ranges = isset( $booking_settings[ 'booking_date_range' ] ) ? $booking_settings[ 'booking_date_range' ] : array();
	    
	    if ( is_array( $custom_ranges ) && count( $custom_ranges ) > 0 ) {
	        foreach( $custom_ranges as $range ) {
	            $start = $range[ 'start' ];
	            $end = $range[ 'end' ];
	            $recur = ( isset( $range[ 'years_to_recur' ] ) && $range[ 'years_to_recur' ] > 0 ) ? $range[ 'years_to_recur' ] : 0;
	    
	            for( $i = 0; $i <= $recur; $i++ ) {
	                // get the start & end dates
	                $start_date = date( 'j-n-Y', strtotime( "+$i years", strtotime( $start ) ) );
	                $end_date = date( 'j-n-Y', strtotime( "+$i years", strtotime( $end ) ) );
	                $ranges[] = array( 'start' => $start_date,
	                    'end' => $end_date
	                );
	            }
	    
	        }
	    }
	    
	    
	    // month ranges
	    $month_ranges = get_post_meta( $product_id, '_bkap_month_ranges', true );
	    if ( is_array( $month_ranges ) && count( $month_ranges ) > 0 ) {
	        foreach( $month_ranges as $range ) {
	            $start = $range[ 'start' ];
	            $end = $range[ 'end' ];
	            $recur = ( isset( $range[ 'years_to_recur' ] ) && $range[ 'years_to_recur' ] > 0 ) ? $range[ 'years_to_recur' ] : 0;
	    
	            for( $i = 0; $i <= $recur; $i++ ) {
	                // get the start & end dates
	                $start_date = date( 'j-n-Y', strtotime( "+$i years", strtotime( $start ) ) );
	                $end_date = date( 'j-n-Y', strtotime( "+$i years", strtotime( $end ) ) );
	                $ranges[] = array( 'start' => $start_date,
	                    'end' => $end_date
	                );
	            }
	    
	        }
	    }
	    
	    if ( is_array( $ranges ) && count( $ranges ) > 0 ) {
	        // default the fields
	        $min_date = '';
	        $days = '';
	    
	        $active_dates = array();
	        $loop_count = count( $ranges );
	        for( $i = 0; $i < $loop_count; $i++ ) {
	    
	            $key = '';
	            $first = true;
	            foreach( $ranges as $range_key => $range_data ) {
	    
	                if ( $first ) {
	                    $min_start = $range_data[ 'start' ];
	                    $min_end = $range_data[ 'end' ];
	                    $key = $range_key;
	                    $first = false;
	                }
	    
	                $new_start = strtotime( $range_data[ 'start' ] );
	    
	                if ( $new_start < strtotime( $min_start ) ) {
	                    $min_start = $range_data[ 'start' ];
	                    $min_end = $range_data[ 'end' ];
	                    $key = $range_key;
	    
	                }
	    
	            }
	    
	            // add the minimum data to the new array
	            $active_dates[] = array( 'start' => $min_start,
	                'end' => $min_end
	            );
	    
	            // remove the minimum start & end record
	            unset( $ranges[ $key ] );
	    
	        }
	    
	        // now get the first start date i.e. the min date
	        foreach( $active_dates as $dates ) {
	            // very first active range
	            $start = $dates[ 'start' ];
	    
	            // if it is a past date, check the end date to see if the entire range is past
	            if ( strtotime( $start ) < $current_time ) {
	                $end = $dates[ 'end' ];
	    
	                if ( strtotime( $end ) < $current_time ) {
	                    continue; // range is past, so check the next record
	                } else { // few days left in the range
	                    $min_date = bkap_common::bkap_min_date_based_on_AdvanceBookingPeriod( $product_id, $current_time );  // so min date is today
	                    break;
	                }
	            } else { // this is a future date
	                $min_date = bkap_common::bkap_min_date_based_on_AdvanceBookingPeriod( $product_id, $current_time );
		            if( strtotime( $start ) >= strtotime( $min_date ) ){
	                    $min_date = $dates[ 'start' ];
		            }
                    break;
	            }
	        }
	    
	        // set the max date
	        $active_dates_count = count( $active_dates );
	        $active_dates_count -= 1;
	        $days = $active_dates[ $active_dates_count ][ 'end' ];
	    
	        // if min date is blanks, happens when all ranges are in the past
	        if ( $min_date === '' ) {
	            $min_date = $active_dates[ $active_dates_count ][ 'end' ];
	        }
	    
	        $fixed_date_range = '';
	        // create the fixed date range record
	        foreach( $active_dates as $dates ) {
	            $fixed_date_range .= '"' . $dates[ 'start' ] . '","' . $dates[ 'end' ] . '",';
	        }
	    
	        $additional_data[ 'fixed_ranges' ] = $fixed_date_range;
	        
	    } else { // follow ABP and Number of Dates
	        $min_date = $days = '';
	         
	        $min_date = bkap_common::bkap_min_date_based_on_AdvanceBookingPeriod( $product_id, $current_time );
	    
	        if ( isset( $booking_settings[ 'booking_maximum_number_days' ] ) ) {
	            $days = $booking_settings[ 'booking_maximum_number_days' ];
	        }
	    }
	    // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
	    if ( isset ( $booking_settings[ 'booking_enable_time' ] ) && $booking_settings[ 'booking_enable_time' ] == 'on' ) {
	        $current_date  =   date( 'j-n-Y', $current_time );
	        $last_slot_hrs =   $current_slot_hrs = $last_slot_min = 0;
	         
	        if ( is_array( $booking_settings[ 'booking_time_settings' ] ) && array_key_exists( $min_date, $booking_settings[ 'booking_time_settings' ] ) ) {
	    
	            foreach ( $booking_settings[ 'booking_time_settings' ][ $min_date ] as $key => $value ) {
	                $current_slot_hrs = $value[ 'from_slot_hrs' ];
	                 
	                if ( $current_slot_hrs > $last_slot_hrs ) {
	                    $last_slot_hrs = $current_slot_hrs;
	                    $last_slot_min = $value[ 'to_slot_min' ];
	                }
	            }
	        }
	        else {
	            // Get the weekday as it might be a recurring day setup
	            $weekday 		= 	date( 'w', strtotime( $min_date ) );
	            $booking_weekday 	= 	"booking_weekday_$weekday";
	    
	            if ( is_array( $booking_settings[ 'booking_time_settings' ] ) && array_key_exists( $booking_weekday, $booking_settings[ 'booking_time_settings' ] ) ) {
	                 
	                foreach ( $booking_settings[ 'booking_time_settings' ][ $booking_weekday ] as $key => $value ) {
	                    $current_slot_hrs = $value[ 'from_slot_hrs' ];
	    
	                    if ( $current_slot_hrs > $last_slot_hrs ) {
	                        $last_slot_hrs = $current_slot_hrs;
	                        $last_slot_min = $value[ 'to_slot_min' ];
	                    }
	                }
	            }
	        }
	        
	        if( $last_slot_hrs == 0 && $last_slot_min == 0 ){
	        }else{
	            $last_slot             =   $last_slot_hrs . ':' . $last_slot_min;
	            
	            $advance_booking_hrs = ( isset( $booking_settings[ 'booking_minimum_number_days' ] ) && $booking_settings[ 'booking_minimum_number_days' ] != '' ) ? $booking_settings[ 'booking_minimum_number_days' ] : 0;
	            
	            $booking_date2     =   $min_date . $last_slot;
	            $booking_date2     =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
	            
	            $date2             =   new DateTime( $booking_date2 );
	            $booking_date1     =   date( 'Y-m-d G:i', $current_time );
	            $date1             =   new DateTime( $booking_date1 );
	            
	            $difference = ( version_compare( phpversion(), '5.3', '>' ) ) ? $date2->diff( $date1 ) : bkap_common::dateTimeDiff( $date2, $date1 );
	            
	            if ( $difference->days > 0 ) {
	                $days_in_hour = $difference->h + ( $difference->days * 24 ) ;
	                $difference->h = $days_in_hour;
	            }
	             
	            if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
	                $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
	            }
	        }
	    }
	    
	    // before setting the max date we need to make sure that at least 1 recurring day or a specific date is set.
	    // This is necessary to ensure the datepicker doesnt go into an endless loop
	    if ( $check_time ) { // date & time bookings
	        if ( ! $timeslots_present ) { // no time slots are present
	            $days = 0;
	        }
	    } else { // only day bookings
	    	if( $number_of_days == 0 && !in_array( 'booking_specific_date', $booking_settings ) && 
	    		( in_array( 'booking_specific_date', $booking_settings ) && ( ! is_array( $booking_settings[ 'booking_specific_date' ] ) ) || 
    			in_array( 'booking_specific_date', $booking_settings ) && is_array( $booking_settings[ 'booking_specific_date' ] ) && 
	    			count( $booking_settings[ 'booking_specific_date' ] ) == 0 ) ) {
	            $days = 0;
	        }
	    }

	    $additional_data[ 'min_date' ] = $min_date;
	    $additional_data[ 'number_of_dates' ] = $days;

	    //Lockout Dates
	    $lockout_query   =   "SELECT DISTINCT start_date FROM `" . $wpdb->prefix . "booking_history`
								 WHERE post_id= %d
								 AND total_booking > 0
								 AND available_booking <= 0
								 AND status = ''";
	    $results_lockout =   $wpdb->get_results ( $wpdb->prepare( $lockout_query, $product_id ) );

	    $lockout_query   =   "SELECT DISTINCT start_date FROM `" . $wpdb->prefix . "booking_history`
            					 WHERE post_id= %d
            					 AND available_booking > 0
            					 AND status = ''";
	    $results_lock    =   $wpdb->get_results ( $wpdb->prepare( $lockout_query, $product_id ) );

	    $lockout_date    =   '';
	     
	    $date_lockout_value = isset( $booking_settings[ 'booking_date_lockout' ] ) ? $booking_settings[ 'booking_date_lockout' ] : 0;
	     
	    foreach ( $results_lockout as $k => $v ) {
	    
	        foreach( $results_lock as $key => $value ) {
	             
	            if ( $v->start_date == $value->start_date ) {
	                $date_lockout         =   "SELECT COUNT(start_date) FROM `" . $wpdb->prefix . "booking_history`
												  WHERE post_id= %d
												  AND start_date= %s
												  AND available_booking = 0";
	                $results_date_lock    =   $wpdb->get_results( $wpdb->prepare( $date_lockout, $product_id, $v->start_date ) );
	    
	                if ( $date_lockout_value > $results_date_lock[0]->{'COUNT(start_date)'} || $date_lockout_value == 0 ) {
	                    unset( $results_lockout[ $k ] );
	                }
	            }
	        }
	    }
	     
	    $lockout_dates_str = "";
	    $lockout_dates_str_1 = '';
	     
	    foreach ( $results_lockout as $k => $v ) {
	        $lockout_temp       =   $v->start_date;
	        $lockout            =   explode( "-", $lockout_temp );
	        $lockout_dates_str_1 .=   intval( $lockout[2])."-".intval( $lockout[1] )."-".$lockout[0].",";
	        $lockout_dates_str .=   '"'.intval( $lockout[2])."-".intval( $lockout[1] )."-".$lockout[0].'",';
	        $lockout_temp       =   "";
	    }

	    // if date_time booking method
	    if( isset( $booking_settings[ 'booking_enable_time' ] ) && 'on' === $booking_settings[ 'booking_enable_time' ] ) {
	        $lockout_dates_str_temp 		= "";
	        $lockout_dates_str_temp_1 	= '';
    	    // lockout dates that have a date/day lockout but no time lockout
    	    $locked_dates = bkap_get_lockout( $product_id, $min_date, $days );

    	    if( is_array( $locked_dates ) && count( $locked_dates ) > 0 ) {
    	        foreach ( $locked_dates as $k => $v ) {
    	            $lockout_dates_str_temp_1 .=   "$v,";
    	            $lockout_dates_str_temp .=   '"'.$v.'",';
    	        }
    	    }
    	    
    	    if( isset( $lockout_dates_str ) ) {
                $lockout_dates_str .= $lockout_dates_str_temp;
    	    } else {
    	        $lockout_dates_str = $lockout_dates_str_temp;
    	    }
    	    
    	    if( isset( $lockout_dates_str_1 ) ) {
                $lockout_dates_str_1 .= $lockout_dates_str_temp_1;
    	    } else {
    	        $lockout_dates_str_1 = $lockout_dates_str_temp_1;
    	    }
    	    
	    }
	    
	    $lockout_dates_str   =   substr( $lockout_dates_str, 0, strlen( $lockout_dates_str )-1 );
	    $lockout_dates       =   $lockout_dates_str;

	    $additional_data[ 'wapbk_lockout_days' ] = $lockout_dates;
	     
	    $lockout_dates_array = array();
	     
	    if ( $lockout_dates != '' ) {
	        $lockout_dates_array = explode( ',', $lockout_dates_str_1 );
	    }
	    
	    $todays_date = date('Y-m-d');
	     
	    $query_date      =   "SELECT DATE_FORMAT(start_date,'%d-%c-%Y') as start_date,DATE_FORMAT(end_date,'%d-%c-%Y') as end_date FROM ".$wpdb->prefix."booking_history
							     WHERE (start_date >='" . $todays_date . "' OR end_date >='" . $todays_date . "') AND post_id = '" . $product_id . "'";
	    $results_date    =   $wpdb->get_results( $query_date );
	    $dates_new       =   array();
	    $booked_dates    =   array();
	     
	    if ( isset( $results_date ) && count( $results_date ) > 0 && $results_date != false ) {
	    
	        foreach( $results_date as $k => $v ) {
	            $start_date    =   $v->start_date;
	            $end_date      =   $v->end_date;
	            if( isset( $booking_settings[ 'booking_charge_per_day' ] ) && $booking_settings[ 'booking_charge_per_day' ] == 'on' ){
	                $dates         =   bkap_common::bkap_get_betweendays( $start_date, $end_date );
	                //$dates         =   bkap_common::bkap_get_betweendays_when_flat( $start_date, $end_date, $product_id );
	            }else{
	                $dates         =   bkap_common::bkap_get_betweendays( $start_date, $end_date );
	            }
	            $dates_new     =   array_merge( $dates, $dates_new );
	        }
	    }
	    //Enable the start date for the booking period for checkout
	    if (isset($results_date) && count($results_date) > 0 && $results_date != false) {
	    
	        foreach ( $results_date as $k => $v ) {
	            $start_date    =   $v->start_date;
	            $end_date      =   $v->end_date;
	            $new_start     =   strtotime( "+1 day", strtotime( $start_date ) );
	            $new_start     =   date( "d-m-Y", $new_start );
	            if( isset( $booking_settings[ 'booking_charge_per_day' ] ) && $booking_settings[ 'booking_charge_per_day' ] == 'on' ){
	                $dates         =   bkap_common::bkap_get_betweendays_when_flat( $new_start, $end_date, $product_id );
	            }else{
	                $dates         =   bkap_common::bkap_get_betweendays( $new_start, $end_date );
	            }
	            $booked_dates  =   array_merge( $dates, $booked_dates );
	        }
	    }
	     
	    $dates_new_arr       =   array_count_values( $dates_new );
	    $booked_dates_arr    =   array_count_values( $booked_dates );

	    $lockout = ( isset( $booking_settings[ 'booking_date_lockout' ] ) ) ? $booking_settings[ 'booking_date_lockout' ] : '';

	    $new_arr_str = '';
	     
	    foreach( $dates_new_arr as $k => $v ) {
	    
	        if ( $v >= $lockout && $lockout != 0 ) {
	            $date_temp     =   $k;
	            $date          =   explode( "-", $date_temp );
	            array_push($lockout_dates_array, ( intval( $date[0] )."-".intval( $date[1] )."-".$date[2] ) );
	            $new_arr_str  .=   '"'.intval( $date[0] )."-".intval( $date[1] )."-".$date[2].'",';
	            $date_temp     =   "";
	        }
	    }
	     
	    $new_arr_str = substr( $new_arr_str, 0, strlen( $new_arr_str )-1 );
	    $additional_data[ 'wapbk_hidden_booked_dates' ] = $new_arr_str;

	    //checkout calendar booked dates
	    $blocked_dates       =   array();
	    $booked_dates_str    =   "";
	     
	    foreach ( $booked_dates_arr as $k => $v ) {
	    
	        if ( $v >= $lockout && $lockout != 0 ) {
	            $date_temp                     =   $k;
	            $date                          =   explode( "-", $date_temp );
	            $date_without_zero_prefixed    =   intval( $date[0] )."-".intval( $date[1] )."-".$date[2];
	            $booked_dates_str             .=   '"'.intval( $date[0] )."-".intval( $date[1] )."-".$date[2].'",';
	            $date_temp                     =   "";
	            $blocked_dates[]               =   $date_without_zero_prefixed;
	        }
	    }
	     
	    if ( isset( $booked_dates_str ) ) {
	        $booked_dates_str = substr( $booked_dates_str, 0, strlen( $booked_dates_str )-1 );
	    } else {
	        $booked_dates_str = "";
	    }
	     
        $additional_data[ 'wapbk_hidden_booked_dates_checkout' ] = $booked_dates_str;
	    
        $current_time = strtotime( $min_date );

        $default_date	=   '';
        $fix_min_day	=   date( 'w', strtotime( $min_date ) );
        $default_date	=   bkap_booking_process::bkap_first_available( $product_id, $lockout_dates_array, $min_date );

        // if default date is blanks due to any reason
        $no_default_found = 1;
        if ( $default_date == '' ) {
            $no_default_found = 0;
            $default_date = date( 'j-n-Y', current_time( 'timestamp' ) );
        }
        
        // if fixed date range is used, confirm that the default date falls in the range
        if ( $default_date != '' ) {
        	if ( is_array( $ranges ) && count( $ranges ) > 0 ) {
	            if ( strtotime( $default_date ) > strtotime( $days ) ) {
	                $no_default_found = 0; // this will ensure the hidden date field is not populated and the product cannot be added to the cart
	            }
	        }
        }

        // Resource calculations
        $additional_data[ 'resource_disable_dates' ] = array();
        $resource_array = Class_Bkap_Product_Resource::bkap_add_additional_resource_data( array(), $booking_settings, $product_id );
        
        $resource_holiday_array = array();
        
        if( !empty( $resource_array ) ) {
        
            $resource_ids 	= Class_Bkap_Product_Resource::bkap_get_product_resources( $product_id );

            $availabile_dates_in_calendar = array();

            if ( strpos( $days, '-') == false ) {
            	
            	$start_booking_str 				= strtotime( $default_date );
            	$max_booking_date 				= apply_filters( 'bkap_max_date', $start_booking_str, $days, $booking_settings );
            	$availabile_dates_in_calendar 	= bkap_common::bkap_get_betweendays( $default_date, $max_booking_date, 'j-n-Y' );

            } else {
            	$max_booking_date   = $days;

            	foreach( $active_dates as $key => $value ){

            		$all_custom_dates 				= array();
            		$all_custom_dates 				= bkap_common::bkap_get_betweendays( $value['start'], $value['end'], 'j-n-Y' );
            		$availabile_dates_in_calendar 	= array_merge( $availabile_dates_in_calendar, $all_custom_dates );

            	}
            }
        
            foreach( $resource_ids as $resource_id ) {
                 
                $resource_availability = $resource_array['bkap_resource_data'][$resource_id]['resource_availability'];
                 
                usort( $resource_availability, 'bkapSortByPriority' ); 
        
                $resource_holiday_array[$resource_id] = array();
        
                foreach ( $availabile_dates_in_calendar as $a_key => $a_value ) {
        
                    $holiday_check = false;
        
                    $bkap_availabile_date_str = strtotime( $a_value );
        
                    foreach ( $resource_availability as $key => $value ) {
                        $date_range_start = $date_range_end = "";
        
                        switch ( $value['type'] ) {
                            case 'custom':
        
                                $date_range_start = $value['from'];
                                $date_range_end   = $value['to'];
        
                                break;
                            case 'months':
        
                                $month_range = bkap_get_month_range( $value['from'], $value['to']);
        
                                $date_range_start = $month_range['start'];
                                $date_range_end   = $month_range['end'];
        
                                break;
                            case 'weeks':
        
                                $week_range	= bkap_get_week_range( $value['from'], $value['to'] );
        
                                $date_range_start = $week_range['start'];
                                $date_range_end   = $week_range['end'];
                                break;
        
                            case 'days':
                                $date_status 	= "";
                                $date_day 		= date( 'w', $bkap_availabile_date_str );
        
        
                                $date_status = bkap_get_day_between_Week( $value['from'], $value['to'] );
        
                                if ( strpos( $date_status, $date_day ) !== false) {
        
                                    array_push( $resource_holiday_array[$resource_id], $a_value );
                                    $holiday_check = true;
                                }
                        }
        
                        if ( $bkap_availabile_date_str >= strtotime( $date_range_start ) && $bkap_availabile_date_str <= strtotime( $date_range_end ) ) {
        
                            if ( $value['bookable'] == 0 ) {
                                array_push( $resource_holiday_array[$resource_id], $a_value );
                            }
                            $holiday_check = true;
                        }
        
                        if( $holiday_check ){
                            break;
                        }
                    }
                }
        
                $resource_holiday_array[$resource_id] = array_values( array_unique( $resource_holiday_array[$resource_id] ) );
            }
        }
        
        $additional_data[ 'resource_disable_dates' ] = $resource_holiday_array;
        
        
        $additional_data[ 'default_date' ] = $default_date;

        $admin_booking = ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] === 'bkap_create_booking_page' ) ? true : false;
        
        // setup the hidden date fields
        $hidden_dates_array = array();
        if ( isset( $booking_settings['enable_inline_calendar'] ) && $booking_settings['enable_inline_calendar'] == 'on' ){
             
            // if there are no products in the cart, then the hidden field should be populated with the default date
            // hence defaulting it with the same.
            $hidden_date = ( isset( $no_default_found ) && $no_default_found ) ? $default_date : '';

            $hidden_date_checkout          =   '';
            //$bkap_block_booking = new bkap_block_booking();
            $number_of_fixed_price_blocks  =   bkap_block_booking::bkap_get_fixed_blocks_count( $product_id );
             
            $widget_search = 0;
             
            if ( isset( $_SESSION['start_date'] ) && $_SESSION['start_date'] != '' ) {
                $hidden_date   =   date( 'j-n-Y', strtotime( $_SESSION['start_date'] ) );
                $first_available_date = bkap_booking_process::bkap_first_available( $product_id, $lockout_dates_array, $hidden_date );
                $hidden_date = $first_available_date;
        
                $widget_search =   1;
            }
             
            if ( isset( $_SESSION['end_date'] ) && $_SESSION['end_date'] != '' ) {
                 
                if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && $booking_settings[ 'booking_enable_multiple_day' ] == 'on' ) {
                    $hidden_date_checkout = date( 'j-n-Y', strtotime( $_SESSION[ 'end_date' ] ) );
                }
            }
        
            if ( isset( $global_settings->booking_global_selection ) && $global_settings->booking_global_selection == "on" ) {
        
                if ( ! $admin_booking && ! $edit ) {
                    foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

                        if( array_key_exists( 'bkap_booking', $values ) ) {
                            $booking            =   $values[ 'bkap_booking' ];
                            $duplicate_date     =   $booking[0][ 'hidden_date' ];
                            $hidden_date_arr    =   explode( "-", $duplicate_date );
                            $hidden_time        =   mktime( 0, 0, 0, $hidden_date_arr[1], $hidden_date_arr[0], $hidden_date_arr[2] );
            
                            $hidden_date = ( $hidden_time > $current_time ) ? $booking[0]['hidden_date'] : $default_date;

                            $first_available_date = bkap_booking_process::bkap_first_available( $product_id, $lockout_dates_array, $duplicate_date );
                            if ( $duplicate_date !== $first_available_date ) {
                                $hidden_date = $first_available_date;
                            }
            
                            if( array_key_exists( "hidden_date_checkout", $booking[0] ) ) {
                                $hidden_date_checkout = $booking[0]['hidden_date_checkout'];
                            }
                        }
                        break;
                    }
                }
            }
             
            $booking_date = '' ;
        
            if ( isset( $booking_settings['booking_fixed_block_enable'] ) && $booking_settings['booking_fixed_block_enable']  == "booking_fixed_block_enable" ) {

            	if ( isset( $widget_search ) && 1 == $widget_search ) {
                	bkap_booking_process::bkap_prepopulate_fixed_block( $product_id );
            	}

                $hidden_date = self::set_fixed_block_hidden_date( 
                    $hidden_date, 
                    $product_id, 
                    $holiday_array, 
                    $global_holidays, 
                    $lockout_dates_array );
            }
        } else {
            //$bkap_block_booking = new bkap_block_booking();
            $number_of_fixed_price_blocks  =   bkap_block_booking::bkap_get_fixed_blocks_count( $post_id );
             
            $hidden_date                   =   '';
            $hidden_date_checkout          =   '';
            $widget_search                 =   0;
            	
            if ( isset( $_SESSION['start_date'] ) && $_SESSION['start_date'] != '' ) {
                $hidden_date                = date( 'j-n-Y', strtotime( $_SESSION['start_date'] ) );
                $first_available_date       = bkap_booking_process::bkap_first_available( $product_id, $lockout_dates_array, $hidden_date );
                $numbers_of_days_to_choose  = isset( $booking_settings['booking_maximum_number_days'] ) ? $booking_settings['booking_maximum_number_days'] - 1 : "";
                
                $max_booking_date =   apply_filters( 'bkap_max_date' , $min_date, $numbers_of_days_to_choose, $booking_settings );

                $first_available_date_str   =  strtotime( $first_available_date ) ;
                $max_booking_date_str       =  strtotime( $max_booking_date ) ;
                $hidden_date                =  $first_available_date;

                if ( $first_available_date_str > $max_booking_date_str ) { // see if date is greater then max date then use max date.
                    $hidden_date = $max_booking_date;
                }                

                $widget_search =  1;
            }
        
            if ( isset( $_SESSION['end_date'] ) && $_SESSION['end_date'] != '' ) {
                if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && $booking_settings[ 'booking_enable_multiple_day' ] == 'on' ) {
                    $start_ts =   strtotime( $_SESSION['start_date'] );
                    $end_ts   =   strtotime( $_SESSION['end_date'] );
                    	
                    if ( $start_ts == $end_ts ){
        
                        if ( is_plugin_active( 'bkap-rental/rental.php' ) ) {
        
                            if( isset( $booking_settings[ 'booking_charge_per_day' ] ) && $booking_settings[ 'booking_charge_per_day' ] == 'on' && isset( $booking_settings[ 'booking_same_day' ] ) && $booking_settings[ 'booking_same_day' ] == 'on' ) {
                                $hidden_date_checkout = date( 'j-n-Y', strtotime( $_SESSION[ 'end_date' ] ) );
                            }else {
                                $next_end_date            =   strtotime( '+1 day', strtotime( $_SESSION[ 'end_date' ] ) );
                                $hidden_date_checkout     =   date( 'j-n-Y', $next_end_date );
                            }
                             
                        }else {
                            $next_end_date            =   strtotime( '+1 day', strtotime( $_SESSION[ 'end_date' ] ) );
                            $hidden_date_checkout     =   date( 'j-n-Y', $next_end_date );
                        }
                    } else {
                        	
                        $number_of_days = array();
                        if( isset( $booking_settings['enable_minimum_day_booking_multiple'] ) && "on" == $booking_settings['enable_minimum_day_booking_multiple'] && $booking_settings['booking_minimum_number_days_multiple'] > 0 ){
                            $number_of_days = bkap_common::bkap_get_betweendays ( $_SESSION['start_date'], $_SESSION[ 'end_date' ] );
                            	
                            if( count($number_of_days) >= $booking_settings['booking_minimum_number_days_multiple'] ){
        
                                $hidden_date_checkout = date( 'j-n-Y', strtotime( $_SESSION[ 'end_date' ] ) ) ;
                                $min_search_checkout = $hidden_date_checkout;
                            }else{
                                $minimum_number_of_days	= 	$booking_settings['booking_minimum_number_days_multiple'];
                                $end_ts 		= 	strtotime( "+".$minimum_number_of_days."day", strtotime( $_SESSION[ 'start_date' ] ) );
                                $hidden_date_checkout 	= 	date( 'j-n-Y', $end_ts ) ;
                                $min_search_checkout = $hidden_date_checkout;
                            }
                        } else {
                            $hidden_date_checkout = date( 'j-n-Y', strtotime( $_SESSION[ 'end_date' ] ) );
                        }
                    }
                    if ( isset( $widget_search ) && 1 == $widget_search && isset( $booking_settings['booking_fixed_block_enable'] ) && $booking_settings['booking_fixed_block_enable']  == "booking_fixed_block_enable" ) {	

                        // fix of auto populating wrong dates when fixed block booking is enabled for the product
                        bkap_booking_process::bkap_prepopulate_fixed_block( $product_id );
                        $hidden_date = self::set_fixed_block_hidden_date( 
                            $hidden_date, 
                            $product_id, 
                            $holiday_array, 
                            $global_holidays, 
                            $lockout_dates_array );
                    }
                }
            }
            if ( isset( $global_settings->booking_global_selection ) && $global_settings->booking_global_selection == "on" ) {
        
                if ( ! $admin_booking && ! $edit ) {
                
                    foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
            
                        if( array_key_exists( 'bkap_booking', $values ) ) {
                            $booking            =   $values['bkap_booking'];
                            $duplicate_date     =   $booking[0]['hidden_date']; 
                            $hidden_date_arr    =   explode( "-", $duplicate_date );
                            $hidden_time        =   mktime( 0, 0, 0, $hidden_date_arr[1], $hidden_date_arr[0], $hidden_date_arr[2] );

                            $hidden_date = ( $hidden_time > $current_time ) ? $booking[0]['hidden_date'] : $default_date;

                            $first_available_date = bkap_booking_process::bkap_first_available( $product_id, $lockout_dates_array, $duplicate_date );
                            if ( $duplicate_date !== $first_available_date ) {
                            	$hidden_date = $first_available_date;
                            }

                            $widget_search = 0;
            
                            if( array_key_exists( "hidden_date_checkout", $booking[0] ) ){
            
                                $hidden_date_checkout = $booking[0]['hidden_date_checkout'];
            
                                if( strtotime( $hidden_date_checkout ) == strtotime( $hidden_date ) ){
                                    if( !( isset( $booking_settings[ 'booking_charge_per_day' ] )  && isset( $booking_settings[ 'booking_same_day' ] ) ) ) {
                                        $hidden_date_checkout = date( "j-n-Y", strtotime( $hidden_date_checkout . ' +1 day') );
                                    }
                                }
                            }
                        }
                        break;
                    }
                }
            }
        }
        $hidden_dates_array[ 'widget_search' ] = $widget_search;
        $hidden_dates_array[ 'hidden_date' ] = $hidden_date;
        $hidden_dates_array[ 'hidden_checkout' ] = ( isset( $hidden_date_checkout ) ) ? $hidden_date_checkout : '';
        $hidden_dates_array[ 'min_search_checkout' ] = ( isset( $min_search_checkout ) ) ? $min_search_checkout : '';
        
        $child_ids_str = '';
	    if ( $product_type === 'grouped' ) {
             
            if ( function_exists( 'icl_object_id' ) ) {
                       
                $_parent_obj = wc_get_product( $post_id );
            
            } else {
            
                $_parent_obj = $_product;
         
            }
         
            if ( $_parent_obj->has_child() ) {
             $child_ids = $_parent_obj->get_children();
            }
         
            if ( isset( $child_ids ) && count( $child_ids ) > 0 ) {
         
             foreach ( $child_ids as $k => $v ) {
                 $child_ids_str .= $v . "-";
             }
            
            }
            
        }
        
        $additional_data[ 'wapbk_grouped_child_ids' ] = $child_ids_str;
        
        global $bkap_days;
        
        $disable_week_days    =   array();
        
        // @since 4.0.0 weekdays can be disabled for multiple day booking using the recurring weekday settings
        if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && 'on' == $booking_settings[ 'booking_enable_multiple_day' ] ) {
            $recurring_days = ( isset( $booking_settings[ 'booking_recurring' ] ) ) ? $booking_settings[ 'booking_recurring' ] : array();
        
            if ( is_array( $recurring_days ) && count( $recurring_days ) > 0 ) {
                $checkin_array = array();
                $checkout_array = array();
                foreach( $recurring_days as $day_name => $day_status ) {
        
                    if ( '' == $day_status ) {
                        $day = substr( $day_name, -1 );
                        $checkin_array[] = $bkap_days[ $day ];
                        $checkout_array[] = $bkap_days[ $day ];
                    }
                }
        
                if ( is_array( $checkin_array ) && count( $checkin_array ) > 0 ) {
                    $disable_week_days[ 'checkin' ] = $checkin_array;
                }
        
                if( is_array( $checkout_array ) && count( $checkout_array ) > 0 ) {
                    $disable_week_days[ 'checkout' ] = $checkout_array;
                }
            }
        
            $blocked_dates_hidden_var = '';
            $block_dates    =   array();
             
            $block_dates    = (array) apply_filters( 'bkap_block_dates', $product_id , $blocked_dates );
             
            if ( isset( $block_dates ) && count( $block_dates ) > 0 && $block_dates != false ) {
                $i             =   1;
                $bvalue        =   array();
                $add_day       =   '';
                $same_day      =   '';
                $date_label    =   '';
                 
                foreach ( $block_dates as $bkey => $bvalue ) {
                    $blocked_dates_str = '';
        
                    if ( is_array( $bvalue ) && isset( $bvalue['dates'] ) && count( $bvalue['dates'] ) > 0 ){
                        $blocked_dates_str = '"'.implode('","', $bvalue['dates']).'"';
                    }
        
                    $field_name = $i;
        
                    if ( ( is_array( $bvalue ) && isset( $bvalue['field_name'] ) && $bvalue['field_name'] != '' ) ) {
                        $field_name = $bvalue['field_name'];
                    }
        
                    $i++;
        
                    if( is_array( $bvalue ) && isset( $bvalue['add_days_to_charge_booking'] ) ){
                        $add_day = $bvalue['add_days_to_charge_booking'];
                    }
        
                    if( $add_day == '' ) {
                        $add_day = 0;
                    }
        
                    if( is_array( $bvalue ) && isset( $bvalue['same_day_booking'] ) ) {
                        $same_day = $bvalue['same_day_booking'];
                    } else {
                        $same_day = '';
                    }
                    $additional_data[ 'wapbk_add_day' ] = $add_day;
                    $additional_data[ 'wapbk_same_day' ] = $same_day;
        
                }
                if ( isset($bvalue['date_label'] ) && $bvalue['date_label'] != '' ) {
                    $date_label = $bvalue['date_label'];
                } else {
                    $date_label = 'Unavailable for Booking';
                }
            }
            $additional_data[ 'bkap_rent' ] = $blocked_dates_str;
        }
        $calendar             =   '';
        $disable_week_days    =   apply_filters( 'bkap_block_weekdays', $disable_week_days );
        
        
        if ( isset( $disable_week_days ) && !empty ( $disable_week_days ) ){
        
            foreach ( $disable_week_days as $calender_key => $calender_value ){
                $calendar_name = strtolower( $calender_key );
        
                if( 'checkin' == $calendar_name ){
                    $disable_weekdays_array   =   array_map( 'trim', $calender_value );
                    $disable_weekdays_array   =   array_map( 'strtolower', $calender_value );
                    $week_days_funcion        =   bkap_get_book_arrays( 'bkap_days' );
                    $week_days_numeric_value  =   '';
        
                    foreach ( $week_days_funcion as $week_day_key => $week_day_value ){
        
                        if( in_array( strtolower( $week_day_value ), $disable_weekdays_array ) ){
                            $week_days_numeric_value .= $week_day_key .',';
                        }
                    }
        
                    $week_days_numeric_value = rtrim( $week_days_numeric_value, ",");
                     
                    $additional_data[ 'wapbk_block_checkin_weekdays' ] = $week_days_numeric_value;
        
                }elseif( 'checkout' == $calendar_name ){
                     
                    $disable_weekdays_array   =   array_map( 'trim', $calender_value );
                    $disable_weekdays_array   =   array_map( 'strtolower', $calender_value );
                    $week_days_funcion        =   bkap_get_book_arrays( 'bkap_days' );
                    $week_days_numeric_value  =   '';
        
                    foreach ( $week_days_funcion as $week_day_key => $week_day_value ){
        
                        if( in_array( strtolower( $week_day_value ) ,$disable_weekdays_array ) ){
                            $week_days_numeric_value .= $week_day_key .',';
                        }
                    }
        
                    $week_days_numeric_value = rtrim( $week_days_numeric_value, ",");
        
                    $additional_data[ 'wapbk_block_checkout_weekdays' ] = $week_days_numeric_value;
                }
            }
        
        }
		
		//POS Addon Block Weekdays 
		$recurring_blocked_weekdays = '';
		$recurring_blocked_weekdays = apply_filters ( 'wkpbk_block_recurring_weekdays', $recurring_blocked_weekdays, $product_id );
		$additional_data['bkap_block_selected_weekdays'] = $recurring_blocked_weekdays;
		//
		
        $currency_symbol                   =   get_woocommerce_currency_symbol();
        	
        $additional_data[ 'wapbk_currency' ] = $currency_symbol;
        	
	    $attribute_change_var   =   '';
	    $attribute_fields_str   =   ",\"tyche\": 1";
	    $on_change_attributes_str = '';
	    $attribute_value = '';
	    $attribute_value_selected = '';
	    
	    if ( $product_type == 'variable' ){
	        $variations                =   $_product->get_available_variations();
	        $attributes                =   $_product->get_variation_attributes();
	        $attribute_fields_str      =   "";
	        $attribute_name            =   "";
	        $attribute_fields          =   array();
	        $i                         =   0;
	         
	        // Product attributes - taxonomies and custom, ordered, with visibility and variation attributes set
	        $bkap_attributes           = get_post_meta( $post_id, '_product_attributes', true );
	        $attribute_name_list = '';
	        foreach ( $bkap_attributes as $attr_key => $attr_value ) {
	            $attribute_name_list .= urldecode( $attr_key ) . ',';
	        }
	    
	        $variation_price_list = '';
	         
	        foreach ( $variations as $var_key => $var_val ) {
	    
	            $variation_price_list .= $var_val[ 'variation_id' ] . '=>' . $var_val[ 'display_price' ] . ',';
	            foreach ( $var_val['attributes'] as $a_key => $a_val ) {
	    
	                if ( !in_array( $a_key, $attribute_fields ) ) {
	                    $attribute_fields[]         =   $a_key;
	                    $attribute_fields_str      .=   ",\"$a_key\": jQuery(\"[name='$a_key']\").val() ";
	                    $attribute_value           .=   "attribute_values =  attribute_values + '|' + jQuery(\"[name='$a_key']\").val();";
	                    $attribute_value_selected  .=   "attribute_selected =  attribute_selected + '|' + jQuery(\"[name='$a_key'] :selected\").text();";
	                    $a_key                      =   esc_attr( sanitize_title( $a_key ) );
	                    $on_change_attributes[]     =   "[name='".$a_key."']";
	                }
	                $i++;
	            }
	        }
	         
	        $on_change_attributes_str      = ( is_array( $on_change_attributes ) && count( $on_change_attributes ) > 0 ) ? implode( ',', $on_change_attributes ) : '';
	        $attribute_change_var          =   ''; // moved to process.js
	        $additional_data[ 'wapbk_attribute_list' ] = $attribute_name_list;
	        $additional_data[ 'wapbk_var_price_list' ] = $variation_price_list;
	         
	    } 
	    $ajax_url = get_admin_url() . 'admin-ajax.php';
	    
	    $plugin_version_number = get_option( 'woocommerce_booking_db_version' );

	    $used_for_modal_display = apply_filters( 'bkap_display_multiple_modals', false );
	    
	    wp_register_script( 'bkap-init-datepicker', plugins_url().'/woocommerce-booking/js/initialize-datepicker.js', '', $plugin_version_number, false );

	    $init_datepicker_param = "bkap_init_params";
	    if ( is_cart() || is_checkout() || is_wc_endpoint_url( 'view-order' ) || $used_for_modal_display ) {
	    	$init_datepicker_param = "bkap_init_params_$product_id";
	    }

	    $additional_data[ 'booking_post_id' ] = 0; // default
	    $additional_data[ 'time_selected' ] = ''; // default
	    
	    $additional_data = apply_filters('bkap_add_additional_data', $additional_data, $booking_settings, $product_id );
	    
	    if ( $edit ) { // if a booking post is being edited
	        
	        // set inline calendar to off
	        $booking_settings[ 'enable_inline_calendar' ] = '';
	        
	        $booking_post_id = isset( $_GET[ 'post' ] ) ? $_GET[ 'post' ] : 0;
	        
	        if ( $booking_post_id > 0 ) {
	            // remove the booking date from the locked/holidays list if it matches
	            $start_date = date( 'j-n-Y', strtotime( get_post_meta( $booking_post_id, '_bkap_start', true ) ) );

	            $resource_id = get_post_meta( $booking_post_id, '_bkap_resource_id', true );
	             
	            // check if the date is locked
	            if ( strpos( $additional_data[ 'wapbk_lockout_days' ], $start_date ) > 0 ) {
	                $additional_data[ 'wapbk_lockout_days' ] = str_replace( $start_date, '', $additional_data[ 'wapbk_lockout_days' ] );
	            }
	             
	           if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && $booking_settings[ 'booking_enable_multiple_day' ] == 'on' ) { // multiple days	            
    	            // get the range of dates
    	            $booking_end = date( 'j-n-Y', strtotime( get_post_meta( $booking_post_id, '_bkap_end', true ) ) );
    	            $booking_range = bkap_common::bkap_get_betweendays( $start_date, $booking_end );
    	            // loop through and enable all the locked dates in the range
    	            foreach( $booking_range as $date ) {
    	                $date_to_check = date( 'j-n-Y', strtotime( $date ) );
    	                // remove the date if it exists in the list of blocked dates in the Checkin Calendar
                        if ( strpos( $additional_data[ 'wapbk_hidden_booked_dates' ], $date_to_check ) > 0 ) {
                            $additional_data[ 'wapbk_hidden_booked_dates' ] = str_replace( $date_to_check, '', $additional_data[ 'wapbk_hidden_booked_dates' ] );
                        }
    	                // remove the date if it exists in the list of blocked dates in the Checkout Calendar
                        if ( strpos( $additional_data[ 'wapbk_hidden_booked_dates_checkout' ], $date_to_check ) > 0 ) {
                            $additional_data[ 'wapbk_hidden_booked_dates_checkout' ] = str_replace( $date_to_check, '', $additional_data[ 'wapbk_hidden_booked_dates_checkout' ] );
                        }

                        if( $resource_id != 0 && isset( $additional_data['bkap_booked_resource_data'] ) ) {
                        	if( strpos( $additional_data[ 'bkap_booked_resource_data' ][$resource_id]['bkap_locked_dates'], $date_to_check ) > 0 ){
                        		$additional_data[ 'bkap_booked_resource_data' ][$resource_id]['bkap_locked_dates'] = str_replace( $date_to_check, '', $additional_data[ 'bkap_booked_resource_data' ][$resource_id]['bkap_locked_dates'] );
                        	}
                        	
                        }

                        	
    	            }
	            }
	            // check if the date is a holiday
	            if ( strpos( $additional_data[ 'holidays' ], $start_date ) > 0 ) {
	                $additional_data[ 'holidays' ] = str_replace( $start_date, '', $additional_data[ 'holidays' ] );
	            }
	            // pass the booking post ID
	            $additional_data[ 'booking_post_id' ] = $booking_post_id;
	            
	            // if it's a date_time booking, we need to pass the already set timeslot
	            if( isset( $booking_settings[ 'booking_enable_time' ] ) && 'on' === $booking_settings[ 'booking_enable_time' ] ) {
	                 
	                $time_format = $global_settings->booking_time_format;
	                 
	                // get the time
	                if ( $time_format === '12' ) {
	                    $start_time = date ( 'h:i A', strtotime( get_post_meta( $booking_post_id, '_bkap_start', true ) ) );
	                    $end_time = date( 'h:i A', strtotime( get_post_meta( $booking_post_id, '_bkap_end', true ) ) );
	                } else {
	                    $start_time = date ( 'H:i', strtotime( get_post_meta( $booking_post_id, '_bkap_start', true ) ) );
	                    $end_time = date( 'H:i', strtotime( get_post_meta( $booking_post_id, '_bkap_end', true ) ) );
	                }
	                 
	                $time_slot_selected = $start_time;
	                if ( isset( $end_time ) && ( '' !== $end_time && '12:00 AM' !== $end_time && '00:00' !== $end_time ) ) {
	                    $time_slot_selected .= " - $end_time";
	                }
	                 
	                $additional_data[ 'time_selected' ] = $time_slot_selected;
	            }
	        } 
	    }

	    wp_localize_script( 'bkap-init-datepicker', $init_datepicker_param, array(
			'global_settings'            => wp_json_encode( $global_settings ),
			'bkap_settings'              => wp_json_encode( $booking_settings ),
			'labels'                     => wp_json_encode( $labels ),
			'additional_data'            => wp_json_encode( $additional_data ),
	    ) );

	    wp_enqueue_script( 'bkap-init-datepicker' );
	    
	    wp_register_script( 
	    	'bkap-process-functions', 
	    	plugins_url().'/woocommerce-booking/js/booking-process-functions.js', 
	    	'', 
	    	$plugin_version_number, 
	    	false 
	    );

	    $process_param_name = "bkap_process_params";
	    if ( is_cart() || is_checkout() || is_wc_endpoint_url( 'view-order' ) || $used_for_modal_display ) {
	    	$process_param_name = "bkap_process_params_$product_id";
	    }
	    
	    // Passing attribute of product and its values in $attribute_name_and_values array.
	    $attribute_name_and_values = array();
	    if( $attribute_fields_str != "" ){
	        
	        $attribute_fields_str_array = explode( ",", $attribute_fields_str );
	        foreach ($attribute_fields_str_array as $attribute_fields_str_array_value ){
	            if( $attribute_fields_str_array_value != "" ){
	                list( $k, $v ) = explode( ":", $attribute_fields_str_array_value );
	                $k = str_replace( "\"", "", $k );
	                $attribute_name_and_values[ $k ] = $v;
	            }
	        }
	    }
	    
	    wp_localize_script( 'bkap-process-functions', $process_param_name, array(
            	    'product_id'                 => $product_id,
            	    'post_id'                    => $post_id,
            	    'ajax_url'                   => $ajax_url,
            	    'prd_permalink'              => get_permalink( $post_id ),
            	    'global_settings'            => wp_json_encode( $global_settings ),
            	    'bkap_settings'              => wp_json_encode( $booking_settings ),
            	    'labels'                     => wp_json_encode( $labels ),
            	    'additional_data'            => wp_json_encode( $additional_data ),
            	    'on_change_attr_list'        => $on_change_attributes_str,
            	    'attr_value'                 => $attribute_value,
            	    'attr_selected'              => $attribute_value_selected,
            	    'attr_fields_str'            => $attribute_name_and_values,
            	    
	    ) );

	    wp_localize_script( 'bkap-process-functions', "product_id", array(
			'product_id'            => $product_id
	    ) );

	    wp_enqueue_script( 'bkap-process-functions' );

	    wp_register_script( 'bkap-process', plugins_url().'/woocommerce-booking/js/booking-process.js', '', $plugin_version_number, false );
	    	
	    wp_enqueue_script( 'bkap-process' );

	    return $hidden_dates_array;
	     
	}


    /**
     * This function sets the hidden date variable for Fixed block bookings
     * 
     * @param string $hidden_date Hidden Date variable previously set
     * @param string|int $product_id Product ID
     * @param array $holiday_array Holiday Dates Array
     * @param array $global_holidays Global Holidays Array
     * @param array $lockout_dates_array Lockout Dates Array
     * @return string Hidden Date Variable
     * @since v4.5.0
     */ 
    public static function set_fixed_block_hidden_date( $hidden_date, $product_id, $holiday_array, $global_holidays, $lockout_dates_array ) {

        $results = bkap_block_booking::bkap_get_fixed_blocks( $product_id );

        $fix_min_day = $results[0]['start_day'];

        $min_day = date( 'w', strtotime( $hidden_date ) );

        $date_updated = 'NO';
        if( $fix_min_day != "any_days" ){
             
            for( $i = 0;; $i++ ) {
                 
                if ( in_array( $hidden_date, $holiday_array ) || in_array( $hidden_date, $global_holidays ) || in_array( $hidden_date, $lockout_dates_array ) ) {
                    $hidden_date = date( 'j-n-Y', strtotime( '+1day', strtotime( $hidden_date ) ) );
                    $date_updated = 'YES';
                     
                    $min_day = ( $min_day < 6 ) ? $min_day + 1 : $min_day - $min_day;
                     
                } else {
                     
                    if( $min_day == $fix_min_day){
                        $hidden_date = date( 'j-n-Y',  strtotime( $hidden_date ) );
                        break;
                    }else{
                        $hidden_date = date( 'j-n-Y', strtotime( '+1day', strtotime( $hidden_date ) ) );
                        $date_updated = 'YES';
                         
                        $min_day = ( $min_day < 6 ) ? $min_day + 1 : $min_day - $min_day;
                    }

                    if( $date_updated == 'NO'){
                        break;
                    }
                }
            }
        }

        return $hidden_date;
    }
	

	/**************************************************
	* This function add the Booking fields on the frontend product page as per the settings selected when Enable Booking is enabled.
    *************************************************/
			
	public static function bkap_booking_after_add_to_cart() {
		global $post, $wpdb, $woocommerce;

		/* Postcode Addon view */
		do_action( 'bkap_create_postcode_view' );
		$display_booking_fields = apply_filters ( 'bkap_postcode_display_booking_field', '' );
		
		$duplicate_of     =   bkap_common::bkap_get_product_id( $post->ID );
		$booking_settings = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
		$booking_settings_new = bkap_get_post_meta( $duplicate_of );
		$global_settings  = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		$product          =   wc_get_product( $post->ID );
        $product_type = $product->get_type();
        		
		$bookable = bkap_common::bkap_get_bookable_status( $duplicate_of );
		

		if ( ! $bookable ) {
		    return;
		}

		// Postcode addon: Show delivery postcode to logged in users / modal to logged out users 
		if ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] == 'on'  && ( 'YES' == $display_booking_fields || '' == $display_booking_fields ) ) {
			do_action ( 'bkap_create_postcode_field_before_field' );
		}else{
			do_action ( 'bkap_create_postcode_modal' );
			return;
		}

		//$bkap_booking_process = new bkap_booking_process();
		$hidden_dates = self::bkap_localize_process_script( $post->ID );
		
		wc_get_template( 
			'bookings/bkap-bookings-box.php', 
			array(
				'product_id'		=> $duplicate_of,
				'product_obj'		=> $product,
				'booking_settings' 	=> $booking_settings,
				'global_settings'	=> $global_settings,
				'hidden_dates'      => $hidden_dates ), 
			'woocommerce-booking/', 
			BKAP_BOOKINGS_TEMPLATE_PATH );
	

		// Set the Session gravity forms option total to 0
		$_SESSION['booking_gravity_forms_option_price'] = 0;
		
		if ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] == 'on' ) {
			
				/**
				 * GF and GF Product addons compatibility
				 *
				 * The script code is used to update the GF Totals displayed
				 * on the front end product page
				 *
				 * @since 2.4.4
				 */
				if ( class_exists( 'woocommerce_gravityforms' ) || class_exists( 'WC_GFPA_Main' ) ||
					 class_exists( 'WC_Product_Addons' ) ) {
				    //if it's a simple product then call the above function manually to ensure the prices are displayed correctly
				    // when any of the GF fields are selected/unselected and so on.
				    //				    if ( $product_type == 'simple' ) {
				    if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && $booking_settings[ 'booking_enable_multiple_day' ] == 'on' ) {
				        print( '<script type="text/javascript">
                                        jQuery( document ).on( "change", jQuery( "#1" ), function() {
    					                   if (jQuery("#wapbk_hidden_date").val() != "" && jQuery("#wapbk_hidden_date_checkout").val() != "") bkap_calculate_price();
    					                });
    					                </script>' );
				    } else if ( isset( $booking_settings[ 'booking_enable_date' ] ) && $booking_settings[ 'booking_enable_date' ] == 'on' ) {
				        print( '<script type="text/javascript">
                                        jQuery( document ).on( "change", jQuery( "#1" ), function() {
    					                   if (jQuery("#wapbk_hidden_date").val() != "" ) bkap_single_day_price();
    					                });
    					                </script>' );
				    }
				    //				    }
				}
					//from here
					if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ){
					} else { // for single day bookings & simple products
					    
					    if ( isset( $product_type ) && 'simple' == $product_type ) {
    					    if ( isset( $booking_settings[ 'enable_inline_calendar' ] ) && 'on' == $booking_settings[ 'enable_inline_calendar' ] ) {
    					        if ( isset( $booking_settings[ 'booking_enable_time' ] ) && 'on' == $booking_settings[ 'booking_enable_time' ] ) {
    					            /*print( 'jQuery( document ).ready( function() {
            					                if ( jQuery( "#wapbk_hidden_date" ).val() != "" ) {
            					                   bkap_process_date( jQuery( "#wapbk_hidden_date" ).val() );
            					                }
        					                });' );*/
    					        } else {
    					            /*print( 'jQuery( document ).ready( function() {
            					                if ( jQuery( "#wapbk_hidden_date" ).val() != "" ) {
            					                   bkap_single_day_price();
            					                }
        					                });' );*/
    					        }
    					    }
					    } elseif ( isset( $product_type ) && 'variable' == $product_type ) {
    					    if ( isset( $booking_settings[ 'enable_inline_calendar' ] ) && 'on' == $booking_settings[ 'enable_inline_calendar' ] ) {
    					        if ( isset( $booking_settings[ 'booking_enable_time' ] ) && 'on' == $booking_settings[ 'booking_enable_time' ] ) {
    					            print( '<script>jQuery( document ).ready( function() {
            					                if ( jQuery( "#wapbk_hidden_date" ).val() != "" ) {
            					                   bkap_process_date( jQuery( "#wapbk_hidden_date" ).val() );
            					                }
        					                });</script>' );
    					        } else {
    					            print( '<script>jQuery( document ).ready( function() {
            					                if ( jQuery( "#wapbk_hidden_date" ).val() != "" ) {
    					                
            					                   bkap_single_day_price();
            					                }
        					                });</script> ' );
    					        }
    					    }
					    }
					}
				}
		
	}

	/***********************************************
	* This function displays the prices calculated from other Addons on frontend product page.
    **************************************************/
	public static function bkap_call_addon_price(){
	    $product_id            =  $_POST[ 'id' ];
		$booking_date_format   =  $_POST[ 'details' ];
		$booking_date          =  date( 'Y-m-d', strtotime( $booking_date_format ) );
		$number_of_timeslots   =  0;
		$resource_id		   =  0;
		
		if ( isset( $_POST[ 'timeslots' ] ) ) {
			$number_of_timeslots = $_POST[ 'timeslots' ];
		}
		
		if ( isset( $_POST[ 'resource_id' ] ) && $_POST['resource_id'] != "" ) {
		    $resource_id = (int)$_POST['resource_id'];
		}
		
		$gf_options = 0;
		if ( isset( $_POST[ 'gf_options' ] ) && is_numeric( $_POST[ 'gf_options' ] ) ) {
		    $gf_options = $_POST[ 'gf_options' ];
		}
		
		$product 		    = wc_get_product( $product_id );
		$product_type 		= $product->get_type();
		$variation_id 		= $_POST[ 'variation_id' ];
		$booking_settings 	= get_post_meta( $product_id, 'woocommerce_booking_settings', true );
		
		if( isset( $variation_id ) && ( $variation_id == "" || $variation_id == 0 ) && $product_type == "variable" ){
		    $error_message = __( "Please choose product options&hellip;", "woocommerce" );
		    print( 'jQuery( "#bkap_price" ).html( "' . addslashes( $error_message ) . '");' );
		    die();
		}else{
		    do_action( 'bkap_display_updated_addon_price', $product_id, $booking_date, $variation_id, $gf_options, $resource_id );		    
		}
		
	}
			
	/*************************************************************
	 * This function adds a hook where addons can execute js code
	 ************************************************************/
	
	public static function bkap_js() {
		$booking_date =   $_POST['booking_date'];
		$post_id      =   $_POST['post_id'];
		
		if ( isset( $_POST['addon_data'] ) ) {
			$addon_data = $_POST['addon_data'];
		}
		else {
			$addon_data = '';
		}
		
		do_action( 'bkap_js', $booking_date, $post_id, $addon_data );
		die();
	}
	
	/************************************************************
	 * This function displays the available lockout for a given 
	 * date for all types of bookings
	 ***********************************************************/
	public static function bkap_get_date_lockout() {
		
		$product_id               =   $_POST['post_id'];		
		// Checkin/Booking Date		
		$date_formats             =   bkap_get_book_arrays( 'bkap_date_formats' ); 
		// get the global settings to find the date formats
		$global_settings          =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );    
		$date_format_set          =   $date_formats[ $global_settings->booking_date_format ];
		$date                     =   strtotime( $_POST['checkin_date'] );		
		// Checkin/Booking Date
		$check_in_date            =   date( $date_format_set, $date );    		
		$date_check_in            =   date( 'Y-m-d', $date );
	
		$variation_id             =   $_POST[ 'variation_id' ];
		$bookings_placed          =   $_POST[ 'bookings_placed' ];
		
		$resource_id              =   (int)$_POST[ 'resource_id' ];
		$resource_bookings_placed =   $_POST[ 'resource_bookings_placed' ];
			
		$bkap_booking_process     =   new bkap_booking_process();
		$available_tickets 		  =   $bkap_booking_process->bkap_get_date_availability( 
													$product_id, 
													$variation_id, 
													$date_check_in, 
													$check_in_date, 
													$bookings_placed, 
													$_POST[ 'attr_bookings_placed' ], 
													'', 
													true, 
													$resource_id, 
													$resource_bookings_placed 
												);
		
	    if( $available_tickets != "FALSE" && $available_tickets != "TIME-FALSE" ){
		    if (  ! isset( $message ) || ( isset( $message ) && '' == $message ) ) {
                if ( is_numeric( $available_tickets ) || $available_tickets === 'Unlimited ' ) {
    		        if ( isset( $_POST['checkin_date'] ) && $_POST['checkin_date'] != '' ){
    		            $availability_msg = get_option( 'book_available-stock-date' );
    		            $message = str_replace( array( 'AVAILABLE_SPOTS', 'DATE' ), array( $available_tickets, $_POST['date_in_selected_language'] ), $availability_msg );
    		        }else{
    		            $message    =   __( 'Please select a date.', 'woocommerce-booking' );
    		        }
                }else {
                    $message = $available_tickets;
                    $available_tickets = '';
                }
		    }
		}else if( $available_tickets === "FALSE" ){
		    $message    =   __( "Bookings are full.", 'woocommerce-booking' );
		}elseif( $available_tickets == "TIME-FALSE" ){
		    $message    =   sprintf( 'You have all available spaces for this date in your cart. Please visit the <a href="%s">%s</a> to place the order.', esc_url( wc_get_page_permalink( 'cart' ) ), esc_html__( 'cart', 'woocommerce' ) );
		}
		
		$return = array(
    	    'message'	=> $message,
    	    'max_qty'		=> $available_tickets
		);

		wp_send_json( $return );
	}
	
	public static function bkap_get_date_availability( $product_id, $variation_id, $hidden_date, $check_in_date, $bookings_placed, $attr_bookings_placed, $hidden_checkout_date = '', $cart_check = true, $resource_id = 0, $resource_bookings_placed = '' ) {
	    
	    global $wpdb;
	    
	    // Booking settings
	    $booking_settings         =   get_post_meta( $product_id , 'woocommerce_booking_settings' , true );
	    
	    $available_tickets        =   0;
	    $unlimited                =   'YES';

	    $_product                 =   wc_get_product( $product_id );
	    $product_type             =   $_product->get_type();
	    
	    $selected_date 			  = date( 'j-n-Y', strtotime( $hidden_date ) );
	    
	    // assuming that variation lockout is not set
	    $check_availability = 'YES';
	    
	    // if it's a variable product and bookings placed field passed is non blanks
	    if ( isset( $variation_id ) && $variation_id > 0 && $product_type === 'variable' ) {
	    
	        $variation_lockout = get_post_meta( $variation_id, '_booking_lockout_field', true );
	    
	        if ( isset( $variation_lockout ) && $variation_lockout > 0 ) {
	            $check_availability = 'NO'; //set it to NO, so availability is not re calculated at the product level
	            $variation_lockout_set = 'YES';
	            $available_tickets = $variation_lockout;
	            $hidden_date = date( 'j-n-Y', strtotime( $hidden_date ) );

	            if ( $variation_lockout > 0 ) {
	            	$unlimited = "NO";
	            }
	    
	            // First we will check if lockout is set at the variation level
	            if ( $booking_settings != '' && ( isset( $booking_settings[ 'booking_enable_time' ] ) && 'on' == $booking_settings[ 'booking_enable_time'] ) ) {
	    
	                $number_of_slots = bkap_common::bkap_get_number_of_slots( $product_id, $hidden_date );
	    
	                if ( isset( $number_of_slots ) && $number_of_slots > 0 ) {
	                    $available_tickets *= $number_of_slots;
	                }
	                // create an array of dates for which orders have already been placed and the qty for each date
	                if ( isset( $bookings_placed ) && $bookings_placed != '' ) {
	                    // create an array of the dates
	                    $list_dates = explode( ",", $bookings_placed );
	                    foreach ($list_dates as $list_key => $list_value ) {
	                        // separate the qty for each date & time slot
	                        $explode_date = explode( '=>', $list_value );
	    
	                        if ( isset( $explode_date[2]) && $explode_date[2] != '' ) {
	                            $date = substr( $explode_date[0], 2, -2 );
	                            $date_array[ $date ][ $explode_date[1] ] = $explode_date[ 2 ];
	                        }
	                    }
	                }
	    
	                $orders_placed = 0;
	                if ( isset( $date_array ) && is_array( $date_array ) && count( $date_array ) > 0 ) {
	    
	                    if ( array_key_exists( $hidden_date, $date_array ) ) {
	                        foreach ( $date_array[ $hidden_date ] as $date_key => $date_value ) {
	                            $orders_placed += $date_value;
	                        }
	                        $available_tickets = $available_tickets - $orders_placed;
	                    }
	                }
	    
	            } else {
	    
	                if ( isset( $bookings_placed ) && $bookings_placed != '' ) {
	                    $list_dates = explode( ",", $bookings_placed );
	    
	                    foreach ($list_dates as $list_key => $list_value ) {
	    
	                        $explode_date = explode( '=>', $list_value );
	    
	                        if ( isset( $explode_date[1]) && $explode_date[1] != '' ) {
	                            
	                            if( strpos( $explode_date[0], '\\' ) !== false ) { 
								    $date = substr( $explode_date[0], 2, -2 );
								}else{ // In the import process the string doesn't contain \ character
									$date = substr( $explode_date[0], 1, -1 );
								}

	                            $date_array[$date] = $explode_date[1];
	                        }
	                    }
	                }
	    
	                if ( isset( $date_array ) && is_array( $date_array ) && count( $date_array ) > 0 ) {
	                    if ( array_key_exists( $hidden_date, $date_array ) ) {
	                        $orders_placed = $date_array[ $hidden_date ];
	                        $available_tickets = $available_tickets - $orders_placed;
	                    }
	                }
	            }
	    
	        } else { // if attribute lockout is set

	            $attributes           = get_post_meta( $product_id, '_product_attributes', true );
	            // Product Attributes - Booking Settings
	            $attribute_booking_data = get_post_meta( $product_id, '_bkap_attribute_settings', true );
	            $message = '';
	    
	            if ( is_array( $attribute_booking_data ) && count( $attribute_booking_data ) > 0 ) {

	                foreach ( $attribute_booking_data as $attr_name => $attr_settings ) {
	                    $attr_post_name = 'attribute_' . $attr_name;
	    
	                    if ( isset( $attr_settings[ 'booking_lockout_as_value' ] ) && 'on' == $attr_settings[ 'booking_lockout_as_value' ] && isset( $attr_settings[ 'booking_lockout' ] ) && $attr_settings[ 'booking_lockout' ] > 0 ) {
	                        $attribute_lockout_set = 'YES';
	                        $bookings_placed = $attr_bookings_placed;
	                        $check_availability = 'NO';
	                        $available_tickets = $attr_settings[ 'booking_lockout' ];
	    
	                        $hidden_date = date( 'j-n-Y', strtotime( $hidden_date ) );
	    
	                        $number_of_slots = bkap_common::bkap_get_number_of_slots( $product_id, $hidden_date );
	    
	                        if ( isset( $number_of_slots ) && $number_of_slots > 0 ) {
	                            $available_tickets *= $number_of_slots;
	                        }
	    
	                        if ( isset( $bookings_placed ) && $bookings_placed != '' ) {
	    
	                            $attribute_list = explode( ';', $bookings_placed );
	    
	                            foreach ( $attribute_list as $attr_key => $attr_value ) {
	    
	                                $attr_array = explode( ',', $attr_value );
	    
	                                if ( $attr_name == $attr_array[ 0 ] ) {
	    
	                                    for ( $i = 1; $i < count( $attr_array ); $i++ ) {
	                                        $explode_dates = explode( '=>', $attr_array[ $i] );
	    
	                                        if ( isset( $explode_dates[0] ) && $explode_dates[0] != '' ) {
	    
	                                            $date = substr( $explode_dates[0], 2, -2 );
	    
	                                            if ( isset( $explode_dates[2] ) ) {
	                                                $date_array[ $date ][ $explode_dates[1] ] = $explode_dates[2];
	                                            } else {
	                                                $date_array[ $date ] = $explode_dates[1];
	                                            }
	                                        }
	                                    }
	                                }
	                            }
	                        }
	    
                            // check the availability for this attribute
                            $orders_placed = 0;
                            if ( isset( $date_array ) && is_array( $date_array ) && count( $date_array ) > 0 ) {
    
                                if ( array_key_exists( $hidden_date, $date_array ) ) {
    
                                    if ( is_array( $date_array[ $hidden_date ] ) && count( $date_array[ $hidden_date ] ) > 0 ) {
                                        foreach ( $date_array[ $hidden_date ] as $date_key => $date_value ) {
                                            $orders_placed += $date_value;
                                        }
                                    } else {
                                        $orders_placed = $date_array[ $hidden_date ];
                                    }
    
                                    $available_tickets -= $orders_placed;
                                }
                            }
    
                            $msg_format = get_option( 'book_available-stock-date-attr' );
                            $attr_label = wc_attribute_label( $attr_name, $_product );

                            $availability_msg = str_replace( array( 'AVAILABLE_SPOTS', 'ATTRIBUTE_NAME', 'DATE' ), array( $available_tickets, $attr_label, $check_in_date ), $msg_format );
                            $message .= $availability_msg . "<br>";
	                    }
	                }
	            }
                // This has been done specifically for Variable products with attribute level lockout
                $available_tickets = $message;
	        }
	    }

	    if ( $check_availability == 'YES' ) {
	        
	        // Calculaing availability based on resources.
	        if ( $resource_id != 0 ) {
	            $bkap_resource_availability = get_post_meta( $resource_id, '_bkap_resource_qty', true );
	        
	            // Timeslot availability check
	        
	            // First we will check if lockout is set at the variation level
	            if ( $booking_settings != '' && ( isset( $booking_settings[ 'booking_enable_time' ] ) && 'on' == $booking_settings[ 'booking_enable_time'] ) ) {
	        
	                $number_of_slots 	= bkap_common::bkap_get_number_of_slots( $product_id, $hidden_date );
	        
	                if ( isset( $number_of_slots ) && $number_of_slots > 0 ) {
	                    $bkap_resource_availability *= $number_of_slots;
	                }
	            }
	        
	            if( strlen( $resource_bookings_placed ) > 0 ) {
	        
	                $resource_bookings_placed_list_dates 	= explode( ",", $resource_bookings_placed );
	                $resource_date_array 					= array();
	        
	                foreach ( $resource_bookings_placed_list_dates as $list_key => $list_value ) {
	                    // separate the qty for each date & time slot
	                    $explode_date = explode( '=>', $list_value );
	                     
	                    if ( isset( $explode_date[1]) && $explode_date[1] != '' ) {
	                        $date = substr( $explode_date[0], 2, -2 );
	                        $resource_date_array[ $date ] = (int)$explode_date[ 1 ];
	                    }
	                }
	        
	                $resource_booked_for_date = 0;
	                 
	                if ( array_key_exists( $selected_date, $resource_date_array ) ) {
	                    $resource_booked_for_date = $resource_date_array[ $selected_date ];
	                }
	        
	                $resource_booking_available = $bkap_resource_availability - $resource_booked_for_date;

	            }else{
	                $resource_booking_available = $bkap_resource_availability;
	            }

	            // Check if the same resource is already present in the cart
                if( count( WC()->cart->get_cart() ) > 0 ) {
                	$trc_qty = 0;

                	foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

		    	    	if ( isset( $values['bkap_booking'] ) ) {
		    	    		$cart_booking = $values['bkap_booking'][0];
		    	    		
		    	    		if ( $cart_booking['resource_id'] == $resource_id ) {
		    	    			if ( $values['bkap_booking'][0]['hidden_date_checkout'] != "" ){
		    	    				if( strtotime( $hidden_date ) >= strtotime( $cart_booking['hidden_date'] ) && strtotime( $hidden_date ) < strtotime( $cart_booking['hidden_date_checkout'] ) ){
                                        $trc_qty += $values['quantity'];
                                    }
		    	    			}else {
		    	    				if ( strtotime($hidden_date) == strtotime( $cart_booking['hidden_date'] ) ) {
		    	    					$trc_qty += $values['quantity'];
		    	    				}
		    	    			}
		    	    		}		    	    		
		    	    	}		    	    	
		    	    }

		    	    $resource_booking_available = $resource_booking_available - $trc_qty;
                }

                return $resource_booking_available;
	        }
	    
	        // if multiple day booking is enabled then calculate the availability based on the total lockout in the settings
	        if ($booking_settings != '' && (isset($booking_settings['booking_enable_multiple_day']) && $booking_settings['booking_enable_multiple_day'] == 'on' )) {
	            // Set the default availability to the total lockout value
	            $available_tickets   =   $booking_settings['booking_date_lockout'];
	             
	            if ( $cart_check ) { // if cart_check = true it means this has been called from front end product page
    	            // Now fetch all the records for the product that have a date range which includes the start date
    	            $date_query          =   "SELECT available_booking FROM `".$wpdb->prefix."booking_history`
        							         WHERE post_id = %d
        							         AND start_date <= %s
        							         AND end_date > %s";
    	            $results_date        =   $wpdb->get_results( $wpdb->prepare( $date_query, $product_id, $hidden_date, $hidden_date ) );
    	             
    	            // If records are found then the availability needs to be subtracted from the total lockout value
    	            if ($booking_settings['booking_date_lockout'] > 0) {
    	                $unlimited          =   'NO';
    	                $available_tickets  =   $booking_settings['booking_date_lockout'] - count( $results_date );
    	            }
	            } else if ( false === $cart_check && $hidden_checkout_date != '' ) { // this means it's been called for importing hence we need to check for the entire range
	                
	                // Now fetch all the records for the product that have a date range which includes the start date
	                $date_query          =   "SELECT available_booking FROM `".$wpdb->prefix."booking_history`
        							         WHERE post_id = %d
        							         AND start_date <= %s
        							         AND end_date >= %s";
	                $results_date        =   $wpdb->get_results( $wpdb->prepare( $date_query, $product_id, $hidden_date, $hidden_checkout_date ) );
	                
	                // If records are found then the availability needs to be subtracted from the total lockout value
	                if ($booking_settings['booking_date_lockout'] > 0) {
	                    $unlimited          =   'NO';
	                    $available_tickets  =   $booking_settings['booking_date_lockout'] - count( $results_date );
	                }
	            }
	        } else {
	            // Fetch the record for that date from the Booking history table
	            $date_query      =   "SELECT available_booking,total_booking FROM `".$wpdb->prefix."booking_history`
            						 WHERE post_id = %d
            						 AND start_date = %s
            						 AND status = ''";
	            $results_date    =   $wpdb->get_results( $wpdb->prepare( $date_query, $product_id, $hidden_date ) );
	             
	            if ( isset( $results_date ) && count( $results_date ) > 0 ) {
	    
	                // If records are found then the total available will be the total available for each time slot for that date
	                // If its only day bookings, then only 1 record will be present, so this will work
	                foreach ( $results_date as $key => $value ) {
	                    	
	                    if ( $value->available_booking > 0 && $value->total_booking != 0 ) {
	                        $unlimited            =   'NO';
	                        $available_tickets   +=   $value->available_booking;
	                    }
	                    
	                    if( $value->available_booking > 0 || $value->total_booking != 0 ){
	                        $unlimited            =   'NO';
	                    }
	                }
	            }else { // if no record found and multiple day bookings r not enabled then get the base record for that weekday
	                $weekday            =   date( 'w', strtotime( $hidden_date ) );
	                $booking_weekday    =   'booking_weekday_' . $weekday;
	                $base_query         =   "SELECT available_booking FROM `".$wpdb->prefix."booking_history`
            								WHERE post_id = %d
            								AND weekday = %s
            								AND start_date = '0000-00-00'
            								AND status = ''";
	                $results_base       =   $wpdb->get_results( $wpdb->prepare( $base_query, $product_id, $booking_weekday ) );
	    
	                if ( isset( $results_base ) && count( $results_base ) > 0 ) {
	                    	
	                    foreach ( $results_base as $key => $value ) {
	    
	                        if ( $value->available_booking > 0 ) {
	                            $unlimited           =   'NO';
	                            $available_tickets  +=   $value->available_booking;
	                        }
	                    }
	                } else {
	                    $unlimited = 'NO'; // this will ensure that availability is not displayed as 'Unlimited' when no record is found. This might happen when importing bookings
	                }
	            }
	        }
	    }
	    
	    $booking_time = "";
	    
	    if ( $cart_check ) {
    	    // Check if the same product is already present in the cart
    	    foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
    	        $product_id_cart = $values['product_id'];
    	        	
    	        if ( $product_id_cart == $product_id && isset( $values[ 'bkap_booking' ] ) ) {
    	    
    	            $check_lockout = 'YES';
    	    
    	            if ( isset( $variation_lockout_set ) && 'YES' == $variation_lockout_set ) {
    	    
    	                if ( $variation_id != $values[ 'variation_id' ] ) {
    	                    $check_lockout = 'NO';
    	                }
    	            } else if( isset( $attribute_lockout_set ) && 'YES' == $attribute_lockout_set ) {
    	                $check_lockout = 'NO';
    	            }
    	            if ( 'YES' == $check_lockout ) {
    	                if ( isset( $values[ 'bkap_booking' ] ) ) {
    	                    $booking = $values[ 'bkap_booking' ];
    	                    
    	                    if( isset( $booking[0]['time_slot'] ) && $booking[0]['time_slot'] != "" )
    	                    $booking_time = $booking[0]['time_slot'];
    	                    
    	                }
    	    
    	                $quantity = $values['quantity'];
    	    
    	                $date_formats         =   bkap_get_book_arrays( 'bkap_date_formats' );
    	                // get the global settings to find the date formats
    	                $global_settings      =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
    	                $date_format_set      =   $date_formats[ $global_settings->booking_date_format ];
    	                
    	                if ( strtotime( $booking[0]['hidden_date'] ) ==  strtotime( $hidden_date ) ) {
    	                    
    	                    if ( $available_tickets > 0 ) {
    	                        $unlimited            =   'NO';
    	                        $available_tickets   -=   $quantity;
    	                    }
    	                }
    	            }
    	        }
    	    }
	    }
	    
	    if ( $available_tickets == 0 && $unlimited == 'YES' ) {
	        $available_tickets = __( 'Unlimited ', 'woocommerce-booking' );
	    }
	    
	    if( $available_tickets == 0 && $unlimited == 'NO' && $booking_time != "" ){
	        $available_tickets = "TIME-FALSE";
	    }elseif ( $available_tickets == 0 && $unlimited == 'NO' ) {
	        $available_tickets = "FALSE";
	    }

	    return $available_tickets;
	}
	/********************************************************************
	 * This function displays the available bookings for a give time slot
	 *******************************************************************/
	public static function bkap_get_time_lockout() {
		global $wpdb, $woocommerce;
		
		$product_id           =   $_POST['post_id'];		
		// Booking settings
		$booking_settings     =   get_post_meta( $product_id , 'woocommerce_booking_settings' , true ); 
		// Checkin/Booking Date
		$booking_date_in      =   $_POST['checkin_date']; 
		//$booking_date       =   date( 'Y-m-d', strtotime( $booking_date_in ) );
		$date_formats         =   bkap_get_book_arrays( 'bkap_date_formats' );		
		// get the global settings to find the date formats
		$global_settings      =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );    
		$date_format_set      =   $date_formats[ $global_settings->booking_date_format ];		
		$date                 =   strtotime( $_POST['checkin_date'] );
		
		// Checkin/Booking Date
		$booking_date         =   date( 'Y-m-d', $date ); 
		$booking_date_disply  =   date( $date_format_set, $date );
	
		$available_tickets    =   0;
		$unlimited            =   'YES';
		$timeslots = $message =   '';
		
		$variation_id             =   $_POST[ 'variation_id' ];
		$bookings_placed          =   $_POST[ 'bookings_placed' ];
		
		$resource_id 				= $_POST['resource_id'];
		$resource_bookings_placed 	= $_POST['resource_bookings_placed'];
		
		// assuming that variation lockout is not set
		$check_availability = 'YES';
		
		if ( isset( $_POST['timeslot_value'] ) ) {
			$timeslots = $_POST['timeslot_value'];
		}
		
		$attr_lockout_set = 'NO';
		$booking_in_cart = 'NO';
		
		if ( $timeslots != '' ) {
		    
		    // if it's a variable product and bookings placed field passed is non blanks
		    if ( isset( $variation_id ) && $variation_id > 0 ) {
		    
		        $variation_lockout = get_post_meta( $variation_id, '_booking_lockout_field', true );
		    
		        if ( isset( $variation_lockout ) && $variation_lockout > 0 ) {
		            $check_availability = 'NO'; //set it to NO, so availability is not re calculated at the product level
		            $variation_lockout_set = 'YES';
		    
		            $available_tickets = $variation_lockout;
		            $date_check_in = date( 'j-n-Y', strtotime( $booking_date ) );
		    
		            // create an array of dates for which orders have already been placed and the qty for each date
		            if ( isset( $bookings_placed ) && $bookings_placed != '' ) {
		                // create an array of the dates
		                $list_dates = explode( ",", $bookings_placed );
		                foreach ($list_dates as $list_key => $list_value ) {
		                    // separate the qty for each date & time slot
		                    $explode_date = explode( '=>', $list_value );
		    
		                    if ( isset( $explode_date[2]) && $explode_date[2] != '' ) {
		                        $date = substr( $explode_date[0], 2, -2 );
		                        $date_array[ $date ][ $explode_date[1] ] = $explode_date[ 2 ];
		                    }
		                }
		            }
		        } else {
		    
		            $attributes           = get_post_meta( $product_id, '_product_attributes', true );
		            // Product Attributes - Booking Settings
		            $attribute_booking_data = get_post_meta( $product_id, '_bkap_attribute_settings', true );
		    
		            if ( is_array( $attribute_booking_data ) && count( $attribute_booking_data ) > 0 ) {
		    
		                foreach ( $attribute_booking_data as $attr_name => $attr_settings ) {
		    
		                    if ( isset( $attr_settings[ 'booking_lockout_as_value' ] ) && 'on' == $attr_settings[ 'booking_lockout_as_value' ] && isset( $attr_settings[ 'booking_lockout' ] ) && $attr_settings[ 'booking_lockout' ] > 0 ) {
		                        $attr_lockout_set = 'YES';
		                        $bookings_placed = $_POST[ 'attr_bookings_placed' ];
		                        $check_availability = 'NO';
		                        $available_tickets = $attr_settings[ 'booking_lockout' ];
		    
		                        $date_check_in = date( 'j-n-Y', strtotime( $booking_date ) );
		    
		                        if ( isset( $bookings_placed ) && $bookings_placed != '' ) {
		    
		                            $attribute_list = explode( ';', $bookings_placed );
		    
		                            foreach ( $attribute_list as $attr_key => $attr_value ) {
		    
		                                $attr_array = explode( ',', $attr_value );
		    
		                                if ( $attr_name == $attr_array[ 0 ] ) {
		    
		                                    for ( $i = 1; $i < count( $attr_array ); $i++ ) {
		                                        $explode_dates = explode( '=>', $attr_array[ $i] );
		    
		                                        if ( isset( $explode_dates[0] ) && $explode_dates[0] != '' ) {
		    
		                                            $date = substr( $explode_dates[0], 2, -2 );
		    
		                                            if ( isset( $explode_dates[2] ) ) {
		                                                $date_array[ $attr_name ][ $date ][ $explode_dates[1] ] = $explode_dates[2];
		                                            }
		                                        }
		                                    }
		                                }
		                            }
		    
		                        }
		                    }
		                }
		            }
		        }
		    }
		    
		    $resource_lockout_set = "";
		    // Calculaing availability based on resources.
		    if ( $resource_id != 0 ) {
		    
		        $bkap_resource_availability = get_post_meta( $resource_id, '_bkap_resource_qty', true );
		    
		        $resource_lockout_set = 'YES';
		        $check_availability   = 'NO';
		        $available_tickets = $bkap_resource_availability;
		    
		        $date_check_in = date( 'j-n-Y', strtotime( $booking_date ) );
		    
		        $resource_date_array  = array();
		    
		        if( strlen( $resource_bookings_placed ) > 0 ) {
		    
		            $resource_bookings_placed_list_dates 	= explode( ",", $resource_bookings_placed );
		    
		            foreach ( $resource_bookings_placed_list_dates as $list_key => $list_value ) {
		                // separate the qty for each date & time slot
		                $explode_date = explode( '=>', $list_value );
		                 
		                if ( isset( $explode_date[2]) && $explode_date[2] != '' ) {
		                    $date = substr( $explode_date[0], 2, -2 );
		                    $resource_date_array[ $date ][ $explode_date[1] ] = (int)$explode_date[ 2 ];
		                }
		            }
		        }
		    }
		    
			// Check if multiple time slots are enabled
			$seperator_pos = strpos( $timeslots, "," ); 
			
			if ( isset( $seperator_pos ) && $seperator_pos != "" ) {
				$time_slot_array = explode( ",", $timeslots );
			}
			else {
				$time_slot_array    =   array();
				$time_slot_array[]  =   $timeslots;
			}

			for( $i = 0; $i < count( $time_slot_array ); $i++ ) {
				// split the time slot into from and to time
				$timeslot_explode   =   explode( '-', $time_slot_array[ $i ] );
				$from_hrs           =   date( 'G:i', strtotime( $timeslot_explode[0] ) );
				$to_hrs             =   '';
				
				if ( isset( $timeslot_explode[1] ) ) {
					$to_hrs = date( 'G:i', strtotime( $timeslot_explode[1] ) );
				}
				
				if ( 'YES' == $check_availability ) {
				    $available_tickets = bkap_booking_process::bkap_get_time_availability($product_id, $booking_date, $from_hrs, $to_hrs, 'YES' );
				} else {
				
				    $booking_time = $from_hrs . ' - ' . $to_hrs;
				    if ( $attr_lockout_set =='YES' ) {
				        $orders_placed = 0;
				        if ( is_array( $attribute_booking_data ) && count( $attribute_booking_data ) > 0 ) {
				
				            foreach ( $attribute_booking_data as $attr_name => $attr_settings ) {
				
				                $available_tickets = $attr_settings[ 'booking_lockout' ];
				                if ( isset( $date_array ) && is_array( $date_array ) && count( $date_array ) > 0 ) {
				                    if ( array_key_exists( $date_check_in, $date_array[ $attr_name ] ) ) {
				
				                        if ( array_key_exists( $booking_time, $date_array[ $attr_name ][ $date_check_in ] ) ) {
				                            $orders_placed = $date_array[ $attr_name ][ $date_check_in ][ $booking_time ];
				                        }
				
				                        $available_tickets -= $orders_placed;
				                    }
				                }
				                $msg_format = get_option( 'book_available-stock-time-attr' );
				                $attr_label = wc_attribute_label( $attr_name, $_product );

				                $avaiability_msg = str_replace( array( 'AVAILABLE_SPOTS', 'ATTRIBUTE_NAME', 'DATE', 'TIME' ), array( $available_tickets, $attr_label, $_POST['date_in_selected_language'], $time_slot_array[ $i ] ), $msg_format );
				                $message .= $avaiability_msg . "<br>";
				            }
				        }
				    } elseif ( $variation_lockout_set == 'YES' ) {
				         
				        $orders_placed = 0;
				
				        if ( isset( $date_array ) && is_array( $date_array ) && count( $date_array ) > 0 ) {
				            if ( array_key_exists( $date_check_in, $date_array ) ) {
				                if ( array_key_exists( $booking_time, $date_array[ $date_check_in ] ) ) {
				                    $orders_placed = $date_array[ $date_check_in ][ $booking_time ];
				                }
				                $available_tickets = $variation_lockout - $orders_placed;
				            }
				        }
				    }
				    
				    // Availability for resources.
				    if( $resource_lockout_set == 'YES' ){
				         
				        $resource_orders_placed = 0;
				        if ( isset( $resource_date_array ) && is_array( $resource_date_array ) && count( $resource_date_array ) > 0 ) {
				            if ( array_key_exists( $date_check_in, $resource_date_array ) ) {
				                if ( array_key_exists( $booking_time, $resource_date_array[ $date_check_in ] ) ) {
				                    $resource_orders_placed = $resource_date_array[ $date_check_in ][ $booking_time ];
				                }
				                $available_tickets = $bkap_resource_availability - $resource_orders_placed;
				            }
				        }
				    }
				
				}
				// Check if the same product is already present in the cart
				foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
					$product_id_cart = $values['product_id'];
					
					if ( $product_id_cart == $product_id ) {
						
					    $check_lockout = 'YES';
					    
					    if ( isset( $variation_lockout_set ) && 'YES' == $variation_lockout_set ) {
					    
					        if ( $variation_id != $values[ 'variation_id' ] ) {
					            $check_lockout = 'NO';
					        }
					    } else if( isset( $attr_lockout_set ) && 'YES' == $attr_lockout_set ) {
					        $check_lockout = 'NO';
					    }
					    if ( 'YES' == $check_lockout ) {
					        	
    					    if ( isset( $values['bkap_booking'] ) ) {
    							$booking = $values['bkap_booking'];
    						}
    						
    						$quantity = $values['quantity'];
    						
    						if ( $booking[0]['time_slot'] == $time_slot_array[ $i ] && $booking[0]['hidden_date'] == $booking_date_in ) {
    							
    						    if ( $available_tickets > 0 ) {
    								$unlimited          =   'NO';
    								$available_tickets -=   $quantity;
    								
    								if( $available_tickets == 0 )
    								$booking_in_cart    =   'YES';
    							}
    						} 
					    }
					}
				}
				
				if ( $available_tickets == 0 && $unlimited == 'YES' ) {
					$available_tickets = __( 'Unlimited ', 'woocommerce-booking' );
				}
				if ( $attr_lockout_set !='YES' && $booking_in_cart == 'NO') {
				    
				    $msg_format = get_option( 'book_available-stock-time' );
				    $avaiability_msg = str_replace( array( 'AVAILABLE_SPOTS', 'DATE', 'TIME' ), array( $available_tickets, $_POST['date_in_selected_language'], $time_slot_array[ $i ] ), $msg_format );
				    $message .= $avaiability_msg . "<br>";
				    
				}
				
				if( $booking_in_cart == "YES" && $available_tickets == 0 ){
				    global $woocommerce;
				    $cart_url = $woocommerce->cart->get_cart_url();
				
				    $message = sprintf( 'You have all available spaces for this timeslot in your cart. Please visit the <a href="%s">%s</a> to place the order.', esc_url( wc_get_page_permalink( 'cart' ) ), esc_html__( 'cart', 'woocommerce' ) );
				
				}
				
			}
		}
		$return = array(
		    'message'	=> $message,
		    'max_qty'   => $available_tickets
		);
		
		wp_send_json( $return );
	}
	
	public static function bkap_get_time_availability( $product_id, $booking_date, $from_hrs, $to_hrs, $check_availability ) {
	     
	    global $wpdb;
	     
	    $available_tickets = 0;
	    $unlimited = 'YES';
	     
	    if ( 'YES' == $check_availability ) {

	        $from_hrs = date( 'H:i', strtotime( $from_hrs ) );
	         
	    	if ( $to_hrs == '' ) {
	    		$time_query     =   "SELECT available_booking FROM `".$wpdb->prefix."booking_history`
            								WHERE post_id = %d
            								AND start_date = %s
            								AND TIME_FORMAT( from_time, '%H:%i' ) = %s
            								AND to_time = %s
            								AND status = ''";	        	
	    	} else {
	    	    $to_hrs = date( 'H:i', strtotime( $to_hrs ) );
	    		$time_query     =   "SELECT available_booking FROM `".$wpdb->prefix."booking_history`
            								WHERE post_id = %d
            								AND start_date = %s
            								AND TIME_FORMAT( from_time, '%H:%i' ) = %s
            								AND TIME_FORMAT( to_time, '%H:%i' ) = %s
            								AND status = ''";	        
	    	}

	    	$results_time   =   $wpdb->get_results( $wpdb->prepare( $time_query, $product_id, $booking_date, $from_hrs, $to_hrs ) );	        
	        	
	        // If record is found then simply display the available bookings
	        if ( isset( $results_time ) && count( $results_time ) > 0 ) {
	
	            if ( $results_time[0]->available_booking > 0 ) {
	                $unlimited            =   'NO';
	                $available_tickets    =   $results_time[0]->available_booking;
	            }
	        }
	        // Else get the base record and the availability for that weekday
	        else {
	            $weekday           =   date( 'w', strtotime( $booking_date ) );
	            $booking_weekday   =   'booking_weekday_' . $weekday;

	            if ( $to_hrs == '' ) {
	            	$base_query	=   "SELECT available_booking FROM `".$wpdb->prefix."booking_history`
        									WHERE post_id = %d
        									AND weekday = %s
        									AND TIME_FORMAT( from_time, '%H:%i' ) = %s
        									AND to_time = %s
        									ANd status = ''";
            	} else {
            		$base_query	=   "SELECT available_booking FROM `".$wpdb->prefix."booking_history`
        									WHERE post_id = %d
        									AND weekday = %s
        									AND TIME_FORMAT( from_time, '%H:%i' ) = %s
        									AND TIME_FORMAT( from_time, '%H:%i' ) = %s
        									ANd status = ''";
            	} 
	            
	            $results_base      =   $wpdb->get_results( $wpdb->prepare( $base_query, $product_id, $booking_weekday, $from_hrs,$to_hrs ) );
	
	            if ( isset( $results_base ) && count( $results_base ) > 0 ) {
	                	
	                if ( $results_base[0]->available_booking > 0 ) {
	                    $unlimited           =   'NO';
	                    $available_tickets   =   $results_base[0]->available_booking;
	                }
	            } else {
	                $unlimited = 'NO'; // this will ensure that availability is not displayed as 'Unlimited' when no record is found. This might happen when importing bookings
	            }
	        }
	    }
	     
	    if ( $available_tickets == 0 && $unlimited == 'YES' ) {
	        $available_tickets = "Unlimited ";
	    }
	    return $available_tickets;
	}
	
	/**
	 * Sets up and displays the product price
	 * 
	 * This function setups the bookable price in the hidden fields
	 * & displays the price for single day and/or time bookings when
	 * the purchase without date setting is on and the product is being
	 * purchased without a date.
	 * 
	 * @since 2.8.1
	 */
	public static function bkap_purchase_wo_date_price() {
	    $product_id = $_POST[ 'post_id' ];
	     
	    if ( isset( $_POST[ 'variation_id' ] ) && '' != $_POST[ 'variation_id' ] ) {
	        $variation_id = $_POST[ 'variation_id' ];
	    } else {
	        $variation_id = 0;
	    }
	     
	    if ( isset( $_POST[ 'quantity' ] ) && '' != $_POST[ 'quantity' ] ) {
	        $quantity = $_POST[ 'quantity' ];
	    } else {
	        $quantity = 1;
	    }
	     
	    $_product = wc_get_product( $product_id );
	    $product_type = $_product->get_type();
	     
	    $product_price = bkap_common::bkap_get_price( $product_id, $variation_id, $product_type );
	     
	    $price = $product_price * $quantity;
	     
	    // format the price
	    $wc_price_args = bkap_common::get_currency_args();
	    $formatted_price = wc_price( $price, $wc_price_args );
	     
	    print( 'jQuery( "#total_price_calculated" ).val(' . $price . ');' );
	    // save the price in a hidden field to be used later
	    print( 'jQuery( "#bkap_price_charged" ).val(' . $price . ');' );
	     
	    $display_price = get_option( 'book_price-label' ) . ' ' . $formatted_price;
	    // display the price on the front end product page
	    print( 'jQuery( "#bkap_price" ).html( "' . addslashes( $display_price ) . '");' );
	    die();
	}
	
	public static function bkap_get_fixed_block_inline_date(){
	     
	   $add_days = 0;
	   if ( isset( $_POST[ 'add_days' ] ) ) {
	       $add_days = $_POST[ 'add_days' ];
	   }
	    // strtotime does not support all date formats. hence it is suggested to use the "DateTime date_create_from_format" fn
	    $date_formats    =   bkap_get_book_arrays( 'bkap_date_formats' );
	     
	    // get the global settings to find the date formats
	    $global_settings =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	    $date_format_set =   $date_formats[ $global_settings->booking_date_format ];
	    $date_formatted  =   date_create_from_format( $date_format_set, $_POST['current_date'] );
	    $date = '';
	    if ( isset( $date_formatted ) && $date_formatted != '' ) {
	        $date = date_format( $date_formatted, 'Y-m-d' );
	    }
	    $date = strtotime($date);
	    $date_timestamp_added_days = strtotime("+".$add_days ."day", $date);
	    $date_final_checkout = date( 'Y-m-d', $date_timestamp_added_days );
	    echo $date_final_checkout;
	    
	    die();
	}
	
	
	
	/**********************************
	* This function displays the price calculated on the frontend product page for Multiple day booking feature.
    ******************************************/
			
	public static function bkap_get_per_night_price() {
		global $wpdb,$woocommerce_wpml;
		
		$product_type     =   $_POST['product_type'];
		$product_id       =   $_POST['post_id'];
		$check_in_date    =   $_POST['checkin_date'];
		$check_out_date   =   $_POST['current_date'];
		$diff_days        =   $_POST['diff_days'];
		
		if ( isset( $_POST['quantity'] ) ) {
			$quantity_grp_str = $_POST['quantity'];
		}
		else {
			$quantity_grp_str = 1;
		}
		
		$variation_id_to_fetch = $_POST[ 'variation_id' ];

		if( $variation_id_to_fetch == 0 && $product_type == "variable" ){
		    print( 'jQuery( "#bkap_price" ).html( "Please select an option.");' );
		    die();
		}
		
		
		if ( isset( $_POST[ 'currency_selected' ] ) && $_POST[ 'currency_selected' ] != '' ) {
			$currency_selected = $_POST[ 'currency_selected' ];
		} else {
			$currency_selected = '';
		}
		
		$gf_options = 0;
		if ( isset( $_POST[ 'gf_options' ] ) && is_numeric( $_POST[ 'gf_options' ] ) ) {
		    $gf_options = $_POST[ 'gf_options' ];
		}
		
		$resource_id = 0;
		if ( isset( $_POST[ 'resource_id' ] ) && is_numeric( $_POST[ 'resource_id' ] ) ) {
		    $resource_id = (int)$_POST[ 'resource_id' ];
		}
		
		$checkin_date     =   date( 'Y-m-d', strtotime( $check_in_date ) );
		$checkout_date    =   date( 'Y-m-d', strtotime( $check_out_date ) );
		
		// This condition has been put as fixed,variable blocks and the addons are currently not compatible with grouped products.
		// Please remove this condition once that is fixed.
		if ( $product_type != 'grouped' ) {
			do_action( "bkap_display_multiple_day_updated_price", $product_id, $product_type, $variation_id_to_fetch, $checkin_date, $checkout_date, $gf_options, $resource_id, $currency_selected );
		}
		
		if ( $product_type == 'grouped' ) {
		    $currency_symbol = get_woocommerce_currency_symbol();
		    $has_children = '';
		    $raw_price_str = '';
		    $price_str = '';
		    $price_arr = array();
		    
		    $product = wc_get_product($_POST['post_id']);
		    if ($product->has_child()) {
		        $has_children = "yes";
		        $child_ids = $product->get_children();
		    }
		    $quantity_array = explode(",",$quantity_grp_str);
		    $i = 0;
		    foreach ($child_ids as $k => $v) {
		        $price = get_post_meta( $v, '_sale_price', true);
		        if($price == '') {
		            $price = get_post_meta( $v, '_regular_price',true);
		        }
                // check if it's a bookable product
		        $bookable = bkap_common::bkap_get_bookable_status( $v );
		        if ( $bookable ) {
		            $final_price = $diff_days * $price * $quantity_array[$i];
		        } else {
		            $final_price = $price * $quantity_array[ $i ];
		        }
		        $raw_price = $final_price;
		        
		        $wc_price_args = bkap_common::get_currency_args();
		        $formatted_price = wc_price( $final_price, $wc_price_args );
		        
		        $child_product = wc_get_product($v);
		        if ( function_exists( 'icl_object_id' ) ) {
                    global $woocommerce_wpml;
                    // Multi currency is enabled
                    if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
                        $custom_post = bkap_common::bkap_get_custom_post( $v, 0, $product_type );
                        if( $custom_post == 1 ) {
                            $raw_price = $final_price;
                            $wc_price_args = bkap_common::get_currency_args();
                            $formatted_price = wc_price( $final_price, $wc_price_args );
                        } else if( $custom_post == 0 ) {
                            $raw_price = apply_filters( 'wcml_raw_price_amount', $final_price );
                            $formatted_price = apply_filters( 'wcml_formatted_price', $final_price );
                        }
                    }
		        } 
		        
		        $raw_price_str .= $v . ':' . $raw_price . ',';
		        $price_str .= $child_product->get_title() . ": " . $formatted_price . "<br>";
		        $i++;
		    }
		}

		if ( $raw_price_str > 0 && 'bundle' == $product_type ) {
			$bundle_price = bkap_common::get_bundle_price( $raw_price_str, $product_id, $variation_id_to_fetch );

		    $price_str = wc_price( $bundle_price, $wc_price_args );
		}

		$display_price = get_option( 'book.price-label' ) . ' ' . $price_str;
		
		print( 'jQuery( "#bkap_price_charged" ).val( "'. addslashes( $raw_price_str ) . '");' );
		print( 'jQuery( "#total_price_calculated" ).val( "'. addslashes( $raw_price_str ) . '");' );
		print( 'jQuery( "#bkap_price" ).html( "' . addslashes( $display_price ) . '");' );
		die();
	}
	
	/******************************************************
	* This function adds the booking date selected on the frontend product page for recurring booking method when the date is selected.
    *****************************************************/
	
	public static function bkap_insert_date() {
		global $wpdb;
		
		$current_date     =   $_POST['current_date'];
		$date_to_check    =   date( 'Y-m-d', strtotime( $current_date ) );
		$day_check        =   "booking_weekday_".date( 'w', strtotime( $current_date ) );
		$post_id          =   $_POST['post_id'];
		$product          =   wc_get_product( $post_id );
		$product_type     =   $product->get_type();

		// Grouped products compatibility
		if ($product->has_child()) { 
			$has_children = "yes";
			$child_ids = $product->get_children();
		}
		
		$check_query      =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    						  WHERE start_date= %s
    						  AND post_id= %d
    						  AND status = ''
    						  AND available_booking >= 0";
		$results_check    =   $wpdb->get_results ( $wpdb->prepare( $check_query, $date_to_check, $post_id ) );
		
		if ( !$results_check ) {
			$check_day_query     =  "SELECT * FROM `".$wpdb->prefix."booking_history`
    								 WHERE weekday= %s
    								 AND post_id= %d
    								 AND start_date='0000-00-00'
    								 AND status = ''
    								 AND available_booking > 0";
			$results_day_check   =   $wpdb->get_results ( $wpdb->prepare( $check_day_query, $day_check, $post_id ) );
				
			if ( !$results_day_check ) {
				$check_day_query    =  "SELECT * FROM `".$wpdb->prefix."booking_history`
    									WHERE weekday= %s
    									AND post_id= %d
    									AND start_date='0000-00-00'
    									AND status = ''
    									AND total_booking = 0 
    									AND available_booking = 0";
				$results_day_check  =   $wpdb->get_results ( $wpdb->prepare( $check_day_query, $day_check,$post_id ) );	
			}
			
			foreach ( $results_day_check as $key => $value ) {
				$insert_date        =   "INSERT INTO `".$wpdb->prefix."booking_history`
										(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
										VALUES (
										'".$post_id."',
										'".$day_check."',
										'".$date_to_check."',
										'0000-00-00',
										'',
										'',
										'".$value->total_booking."',
										'".$value->available_booking."' )";
				$wpdb->query( $insert_date );
				
				// Grouped products compatibility
				if ( $product_type == "grouped" ) {
					
				    if ( $has_children == "yes" ) {
						
				        foreach ( $child_ids as $k => $v ) {
				
							$check_day_query     =  "SELECT * FROM `".$wpdb->prefix."booking_history`
    												WHERE weekday= %s
    												AND post_id= %d
    												AND start_date='0000-00-00'
    												AND status = ''
    												AND available_booking > 0";
							$results_day_check   =  $wpdb->get_results ( $wpdb->prepare( $check_day_query, $day_check, $v ) );
				
							if ( !$results_day_check ) {
								$check_day_query    =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    													WHERE weekday= %s
    													AND post_id= %d
    													AND start_date='0000-00-00'
    													AND status = ''
    													AND total_booking = 0
    													AND available_booking = 0";
								$results_day_check  =   $wpdb->get_results ( $wpdb->prepare( $check_day_query, $day_check, $v ) );
							}
				
							$insert_date     =  "INSERT INTO `".$wpdb->prefix."booking_history`
    											(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
    											VALUES (
    											'".$v."',
    											'".$day_check."',
    											'".$date_to_check."',
    											'0000-00-00',
    											'',
    											'',
    											'".$results_day_check[0]->total_booking."',
    											'".$results_day_check[0]->available_booking."' )";
							$wpdb->query( $insert_date );
						}
					}
				}
			}
		}
		die();
	}

	/***********************************************
     * This function displays the timeslots for the selected date on the frontend page when Enable time slot is enabled.
     ************************************************/
			
	public static function bkap_check_for_time_slot() {	

		$current_date         =   $_POST['current_date'];
		$post_id              =   $_POST['post_id'];
		$time_drop_down       =   bkap_booking_process::get_time_slot( $current_date, $post_id );

		$time_drop_down_array =   explode( "|", $time_drop_down );
		
		$time_drop_down_array = apply_filters('bkap_time_slot_filter', $time_drop_down_array);
		
		if( $time_drop_down != "" ) {
    		if ( trim( $time_drop_down_array[ 0 ] ) === 'ERROR' ) {
    		    $drop_down = trim( $time_drop_down_array[ 1 ] );
    		} else {
        		$drop_down = "<label>" . __( ('' !== get_option( 'book_time-label') ? get_option( 'book_time-label'): 'Booking Time' ), 'woocommerce-booking')  . ": </label><br/>";
        		$drop_down .= "<select name='time_slot' id='time_slot' class='time_slot'>";
        
        		if( function_exists('icl_t') ) {
            		$drop_down .= "<option value=''>".icl_t('woocommerce-booking','choose_a_time',get_option('book_time-select-option'))."</option>";
          		} else {
              		$drop_down .= "<option value=''>".__(get_option('book_time-select-option'), 'woocommerce-booking' )."</option>";
          		}
          		
        		foreach ( $time_drop_down_array as $k => $v ) {
        			
        		    if ( $v != "" ) {
        				$drop_down .= "<option value='".$v."'>".$v."</option>";
        			}
        		}
    		}		
		  echo $drop_down;
		}
		die();
	}
	
	/**************************************************************
	 * This function prepares the time slots string to be displayed
	 *************************************************************/
	public static function get_time_slot( $current_date, $post_id ) {
		global $wpdb;
		
		$saved_settings   =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		// Booking settings
		$booking_settings =   get_post_meta( $post_id, 'woocommerce_booking_settings', true ); 

		$advance_booking_hrs = 0;
		if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
			$advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
		}
		
		if ( isset( $saved_settings ) ) {
			$time_format = $saved_settings->booking_time_format;
		} else {
			$time_format = '12';
		}
		
		$time_format_db_value = 'G:i';
		
		if ( $time_format == '12' ) {
			$time_format_to_show = 'h:i A';
		} else {
			$time_format_to_show = 'H:i';
		}
		
		$current_time         =   current_time( 'timestamp' );
		$today                =   date( "Y-m-d G:i", $current_time );
		$date1                =   new DateTime( $today );
		
		$date_to_check        =   date( 'Y-m-d', strtotime( $current_date ) );
		$day_check            =   "booking_weekday_".date( 'w', strtotime( $current_date ) );
		$from_time_db_value   =   '';
		$from_time_show       =   '';
		
		$product              =   wc_get_product( $post_id );
		$product_type         =   $product->get_type();
		
		// Grouped products compatibility
		if ( $product->has_child() ) {
			$has_children    =   "yes";
			$child_ids       =   $product->get_children();
		}
		
        $drop_down = "";
        // check if there's a record available for the given date and time with availability > 0
        $check_query    =   "SELECT * FROM `".$wpdb->prefix."booking_history`
            				WHERE start_date= '".$date_to_check."'
            				AND post_id = '".$post_id."'
            				AND status = ''
            				AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')
        ";
        $results_check  =   $wpdb->get_results ( $check_query );
        
		if ( count( $results_check ) > 0 ) {
			// assume its a recurring weekday record		
			$specific = "N";
			
			foreach ( $results_check as $key => $value ) {
				
				// weekday = '', means its a specific date record
				if ( $value->weekday == "" ) {
					$specific = "Y";
					
					if ( $value->from_time != '' ) {
						$from_time_show       =   date( $time_format_to_show, strtotime( $value->from_time ) );
						$from_time_db_value   =   date( $time_format_db_value, strtotime( $value->from_time ) );
					}
					
					$include       =   'YES';
					$booking_time  =   $current_date . $from_time_db_value;
					$date2         =   new DateTime( $booking_time );

					if ( version_compare( phpversion(), '5.3', '>' ) ) {
					    $difference = $date2->diff( $date1 );
					}else{
					    $difference =  bkap_common::dateTimeDiff( $date2, $date1 );
					}
					
					if ( $difference->days > 0 ) {
						$days_in_hour     =   $difference->h + ( $difference->days * 24 ) ;
						$difference->h    =   $days_in_hour;
					}
					
					if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
						$include = 'NO';
					}
					
					if ( $include == 'YES' ) {
						$to_time_show = $value->to_time;
						
						if( $to_time_show != '' ) {
							$to_time_show        =   date( $time_format_to_show, strtotime( $value->to_time ) );
							$to_time_db_value    =   date( $time_format_db_value, strtotime( $value->to_time ) );
							$drop_down          .=   $from_time_show." - ".$to_time_show."|";
						} else {
							$drop_down .= $from_time_show."|";
						}
						
					}
				}
			}
			if ( $specific == "N" ) {
				
			    foreach ( $results_check as $key => $value ) {
					
			        if ( $value->from_time != '' ) {
						$from_time_show       = date( $time_format_to_show, strtotime( $value->from_time ) );
						$from_time_db_value   = date( $time_format_db_value, strtotime( $value->from_time ) );
					}
					
					$include       =   'YES';
					$booking_time  =   $current_date . $from_time_db_value;
					$date2         =   new DateTime( $booking_time );
					
					if ( version_compare( phpversion(), '5.3', '>' ) ) {
					    $difference    =   $date2->diff( $date1 );
					}else{
					    $difference    =   bkap_common::dateTimeDiff( $date2, $date1 );
					}
					
					if ( $difference->days > 0 ) {
						$days_in_hour     =   $difference->h + ( $difference->days * 24 ) ;
                        $difference->h    =   $days_in_hour;
					}
				
					if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
						$include = 'NO';
					}
					
					if ( $include == 'YES' ) {
						$to_time_show = $value->to_time;
						
						if( $to_time_show != '' ) {
							$to_time_show        =   date( $time_format_to_show, strtotime( $value->to_time ) );
							$to_time_db_value    =   date( $time_format_db_value, strtotime( $value->to_time ) );
							$drop_down          .=   $from_time_show." - ".$to_time_show."|";
						} else {
							
						    if ($value->from_time != '') {
							 $drop_down .= $from_time_show."|";
						    }
						
					   }
				    }	
			     }
			     
			// get all the records using the base record to ensure we include any time slots that might hv been added after the original date record was created
			// This can happen only for recurring weekdays
			$check_day_query     =   "SELECT * FROM `".$wpdb->prefix."booking_history`
									 WHERE weekday= '".$day_check."'
									 AND post_id= '".$post_id."'
									 AND start_date='0000-00-00'
									 AND status = ''
									 AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
			$results_day_check   =   $wpdb->get_results ( $check_day_query );
			
			//remove duplicate time slots that have available booking set to 0
			foreach ( $results_day_check as $k => $v ) {
				$from_time_qry = date( $time_format_db_value, strtotime( $v->from_time ) );
				$from_hi = date( 'H:i', strtotime( $v->from_time ) );
				
				if ( $v->to_time != '' ) {
					$to_time_qry = date( $time_format_db_value, strtotime( $v->to_time ) );
					$to_hi = date( 'H:i', strtotime( $v->to_time ) );
				} else {
					$to_time_qry = "";
					$to_hi   = "";
				}
				
				$time_check_query   =   "SELECT * FROM `".$wpdb->prefix."booking_history`
										WHERE start_date= '".$date_to_check."'
										AND post_id= '".$post_id."'
										AND TIME_FORMAT( from_time, '%H:%i' ) = '".$from_hi."'
										AND TIME_FORMAT( to_time, '%H:%i' )= '".$to_hi."'  
										AND status = '' ORDER BY STR_TO_DATE(from_time,'%H:%i')";
				$results_time_check =   $wpdb->get_results( $time_check_query );
				
				if ( count( $results_time_check ) > 0 ) {
					unset( $results_day_check[ $k ] );
				}
			}
			
			//remove duplicate time slots that have available booking > 0
			foreach ( $results_day_check as $k => $v ) {
				
			    foreach ( $results_check as $key => $value ) {
					
			        if ( $v->from_time != '' && $v->to_time != '' ) {
						$from_time_chk = date( $time_format_db_value, strtotime( $v->from_time ) );
						
						if ( $value->from_time == $from_time_chk ) {
							
						    if ( $v->to_time != '' ){
								$to_time_chk = date( $time_format_db_value, strtotime( $v->to_time ) );
                            }
                            
							if ( $value->to_time == $to_time_chk ){
								unset( $results_day_check[ $k ] );
                            }
                            
						}
					} else {
						
					    if( $v->from_time == $value->from_time ) {
							
					        if ( $v->to_time == $value->to_time ) {
								unset( $results_day_check[ $k ] );
							}
						}
					}
				}
			}
			
			foreach ( $results_day_check as $key => $value ) {
				
			    if ( $value->from_time != '' ) {
					$from_time_show        =   date( $time_format_to_show, strtotime( $value->from_time ) );
					$from_time_db_value    =   date( $time_format_db_value, strtotime( $value->from_time ) );
				}
				
				$include        =   'YES';
				$booking_time   =   $current_date . $from_time_db_value;
				$date2          =   new DateTime( $booking_time );

				if ( version_compare( phpversion(), '5.3', '>' ) ) {
				    $difference     =   $date2->diff( $date1 );
				}else{
				    $difference   =    bkap_common::dateTimeDiff( $date2, $date1 );
				}
				
				if ( $difference->days > 0 ) {
					$days_in_hour  =   $difference->h + ( $difference->days * 24 ) ;
                    $difference->h =    $days_in_hour;
				}
			
				if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
					$include = 'NO';
				}
				
				$to_time_show = $value->to_time;
				
				if ( $to_time_show != '' ) {
					$to_time_show      =   date( $time_format_to_show, strtotime( $value->to_time ) );
					$to_time_db_value  =   date( $time_format_db_value, strtotime( $value->to_time ) );
					
					if ( $include == 'YES' ) {
						$drop_down .= $from_time_show." - ".$to_time_show."|";
					}
					
				} else {
				    
					if ( $value->from_time != '' && $include == 'YES' ) {
						$drop_down .= $from_time_show."|";
					}
					
					$to_time_db_value = '';
				}
				
				$insert_date    =   "INSERT INTO `".$wpdb->prefix."booking_history`
									(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
									VALUES (
									'".$post_id."',
									'".$day_check."',
									'".$date_to_check."',
									'0000-00-00',
									'".$from_time_db_value."',
									'".$to_time_db_value."',
									'".$value->total_booking."',
									'".$value->available_booking."' )";
				$wpdb->query( $insert_date );
					
					// Grouped products compatibility
					if ( $product_type == "grouped" ) {
						
					    if ( $has_children == "yes" ) {
							
					        foreach ( $child_ids as $k => $v ) {
								$check_day_query_child      =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    															WHERE weekday= '".$day_check."'
    															AND post_id= '".$v."'
    															AND start_date='0000-00-00'
    															AND status = ''
    															AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
								$results_day_check_child    =   $wpdb->get_results ($check_day_query_child);
					
								$insert_date                =   "INSERT INTO `".$wpdb->prefix."booking_history`
                												(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                												VALUES (
                												'".$v."',
                												'".$day_check."',
                												'".$date_to_check."',
                												'0000-00-00',
                												'".$from_time_db_value."',
                												'".$to_time_db_value."',
                												'".$results_day_check_child[0]->total_booking."',
                												'".$results_day_check_child[0]->available_booking."' )";
								$wpdb->query( $insert_date );
							}
						}
					}
				}
			}
		} else {
			$check_day_query     =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    								 WHERE weekday= '".$day_check."'
    								 AND post_id= '".$post_id."'
    								 AND start_date='0000-00-00'
    								 AND status = ''
    								 AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
			$results_day_check   =   $wpdb->get_results ( $check_day_query );
			
			// No base record for availability > 0
			if ( !$results_day_check ) {
			// check if there's a record for the date where unlimited bookings are allowed i.e. total and available = 0
				$check_query    =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    								WHERE start_date= '".$date_to_check."'
    								AND post_id= '".$post_id."'
    								AND total_booking = 0
    								AND available_booking = 0
    								AND from_time != ''
    								AND status = '' ORDER BY STR_TO_DATE(from_time,'%H:%i')
    								";
					
				$results_check  =   $wpdb->get_results( $check_query );
				
				// if record found, then create the dropdown
				if ( isset( $results_check ) && count( $results_check ) > 0 ) {
					
				    foreach ( $results_check as $key => $value ) {
						
				        if ( $value->from_time != '' ) {
							$from_time_show      =   date( $time_format_to_show, strtotime( $value->from_time ) );
							$from_time_db_value  =   date( $time_format_db_value, strtotime( $value->from_time ) );
						} else {
							$from_time_show      =   $from_time_db_value = "";
						}
						
						$include      =   'YES';
						$booking_time =   $current_date . $from_time_db_value;
						$date2        =   new DateTime( $booking_time );
				
						if ( version_compare( phpversion(), '5.3', '>' ) ) {
						    $difference   =   $date2->diff( $date1 );
						}else{
						    $difference   =   bkap_common::dateTimeDiff( $date2, $date1 );
						}
						
						if ( $difference->days > 0 ) {
							$days_in_hour    =   $difference->h + ( $difference->days * 24 ) ;
                            $difference->h   =  $days_in_hour;
						}
							
						if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
							$include = 'NO';
						}
						
						if ( $include == 'YES' ) {
							$to_time_show = $value->to_time;
							
							if ( $to_time_show != '' ) {
								$to_time_show       =   date( $time_format_to_show, strtotime( $value->to_time ) );
								$to_time_db_value   =   date( $time_format_db_value, strtotime( $value->to_time ) );
								$drop_down         .=   $from_time_show." - ".$to_time_show."|";
							} else {
								$drop_down         .=   $from_time_show."|";
								$to_time_show       =   $to_time_db_value = "";
							}
							
						}
					}
				} else {
					// else check if there's a base record with unlimited bookings i.e. total and available = 0 
					$check_day_query       =   "SELECT * FROM `".$wpdb->prefix."booking_history`
        										WHERE weekday= '".$day_check."'
        										AND post_id= '".$post_id."'
        										AND start_date='0000-00-00'
        										AND status = ''
        										AND total_booking = 0
        										AND available_booking = 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
					$results_day_check     =   $wpdb->get_results ($check_day_query);
				}
			}
			
			if ( $results_day_check ) {

			    $check_query    =   "SELECT * FROM `" . $wpdb->prefix . "booking_history`
    								WHERE start_date= '" . $date_to_check . "'
    								AND post_id= '" . $post_id . "'
    								AND total_booking > 0
    								AND available_booking = 0
    								AND status = '' ORDER BY STR_TO_DATE(from_time,'%H:%i')";
			    $results_check  =   $wpdb->get_results( $check_query );
			    
			    if( count ( $results_check ) == count( $results_day_check ) ) {
			        $drop_down = 'ERROR | ' . __( get_option( 'book_real-time-error-msg' ), 'woocommerce-booking' );
			        return apply_filters( 'bkap_edit_display_timeslots', $drop_down );
			    } else {
			         
    			    foreach ( $results_day_check as $key => $value ) {
    					
    			        if ( $value->from_time != '' ) {
    						$from_time_show       =   date( $time_format_to_show, strtotime( $value->from_time ) );
    						$from_time_db_value   =   date( $time_format_db_value, strtotime( $value->from_time ) );
    					} else {
    						$from_time_show       =   $from_time_db_value = "";
    					}
    					
    					$include       =   'YES';
    					$booking_time  =   $current_date . $from_time_db_value;
    					$date2         =   new DateTime( $booking_time );
    					
    					if ( version_compare( phpversion(), '5.3', '>' ) ) {
    					    // php version isn't high enough
    					    $difference = $date2->diff( $date1 );
    					}else{
    					    $difference =  bkap_common::dateTimeDiff( $date2, $date1 );
    					}
    					
    					if ( $difference->days > 0 ) {
    						$days_in_hour     =   $difference->h + ( $difference->days * 24 ) ;
                            $difference->h    =   $days_in_hour;
    					}
    				
    					if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
    						$include = 'NO';
    					}
    					
    					$to_time_show = $value->to_time;
    					
    					if ( $to_time_show != '' ) {
    						$to_time_show     =   date( $time_format_to_show, strtotime( $value->to_time ) );
    						$to_time_db_value =   date( $time_format_db_value, strtotime( $value->to_time ) );
    						
    						if ( $include == 'YES' ) {
    							$drop_down .= $from_time_show." - ".$to_time_show."|";
    						}
    						
    					} else  {
    						
    					    if ( $include == 'YES' ) {
    							$drop_down   .=  $from_time_show."|";
    						}
    						
    						$to_time_show     =  $to_time_db_value = "";
    					}
    					
    					$insert_date   =   "INSERT INTO `".$wpdb->prefix."booking_history`
    										(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
    										VALUES (
    										'".$post_id."',
    										'".$day_check."',
    										'".$date_to_check."',
    										'0000-00-00',
    										'".$from_time_db_value."',
    										'".$to_time_db_value."',
    										'".$value->total_booking."',
    										'".$value->available_booking."' )";
    					$wpdb->query( $insert_date );
    					
    					// Grouped products compatibility
    					if ( $product_type == "grouped" ) {
    						
    					    if ( isset( $has_children ) && $has_children == "yes" ) {
    							
    					        foreach ( $child_ids as $k => $v ) {
    								$check_day_query_child      =   "SELECT * FROM `".$wpdb->prefix."booking_history`
        															WHERE weekday= '".$day_check."'
        															AND post_id= '".$v."'
        															AND start_date='0000-00-00'
        															AND status = ''
        															AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
    								$results_day_check_child    =   $wpdb->get_results ($check_day_query_child);
    								
    								if ( isset( $results_day_check_child ) && count( $results_day_check_child ) > 0 ) {
        								$insert_date                =   "INSERT INTO `".$wpdb->prefix."booking_history`
                        												(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                        												VALUES (
                        												'".$v."',
                        												'".$day_check."',
                        												'".$date_to_check."',
                        												'0000-00-00',
                        												'".$from_time_db_value."',
                        												'".$to_time_db_value."',
                        												'".$results_day_check_child[0]->total_booking."',
                        												'".$results_day_check_child[0]->available_booking."' )";
        								$wpdb->query( $insert_date );
    								}
    							}
    					    }
						}
					}
				}
			}
		}
		
		// before returning check if any of the slots are to be blocked at the variation level
		if ( isset( $_POST[ 'variation_id' ] ) && $_POST[ 'variation_id' ] != '' ) {
		
		    if ( isset( $_POST[ 'variation_timeslot_lockout' ] ) && $_POST[ 'variation_timeslot_lockout' ] != '' ) {
		        $dates_array = explode( ',', $_POST[ 'variation_timeslot_lockout' ] );
		        foreach ( $dates_array as $date_key => $date_value ) {
		            $list_dates = explode( '=>', $date_value );
		
		            if ( stripslashes( $list_dates[ 0 ] ) == $_POST[ 'current_date' ] ) {
		
		                // convert the time slot in the format in which it is being displayed
		                $time_slot_explode = explode( '-', $list_dates[1] );
		                $time_slot = date( $time_format_to_show, strtotime( $time_slot_explode[0] ) );
		                $time_slot .= ' - ' . date( $time_format_to_show, strtotime( $time_slot_explode[1] ) );
		
		                if ( strpos( $drop_down, $time_slot ) >= 0 ) {
		                    $pattern_to_be_removed = $time_slot . '|';
		                    $drop_down = str_replace( $pattern_to_be_removed, '', $drop_down );
		                }
		            }
		        }
		    }
		}
		
		// before returning , also check if any slots needs to be blocked at the attribute level
		$attributes = get_post_meta( $post_id, '_product_attributes', true );
		
		if (is_array( $attributes ) && count( $attributes ) > 0 ) {
		    foreach ( $attributes as $attr_name => $attr_value ) {
		        $attr_post_name = 'attribute_' . $attr_name;
		        // check the attribute value
		        if ( isset( $_POST[ $attr_post_name ] ) && $_POST[ $attr_post_name ] > 0 ) {
		            // check if any dates/time slots are set to be locked out
		            if ( isset( $_POST[ 'attribute_timeslot_lockout' ] ) && $_POST[ 'attribute_timeslot_lockout' ] != '' ) {
		
		                $attribute_explode = explode( ';', $_POST[ 'attribute_timeslot_lockout' ] );
		
		                foreach ( $attribute_explode as $attribute_name => $attribute_fields ) {
		
		                    $dates_array = explode( ',', $attribute_fields );
		
		                    foreach ( $dates_array as $date_key => $date_value ) {
		
		                        if ( $date_value != $attr_name ) {
		                            $list_dates = explode( '=>', $date_value );
		
		                            if ( stripslashes( $list_dates[ 0 ] ) == $_POST[ 'current_date' ] ) {
		
		                                // convert the time slot in the format in which it is being displayed
		                                $time_slot_explode = explode( '-', $list_dates[1] );
		                                $time_slot = date( $time_format_to_show, strtotime( $time_slot_explode[0] ) );
		                                $time_slot .= ' - ' . date( $time_format_to_show, strtotime( $time_slot_explode[1] ) );
		                                if ( strpos( $drop_down, $time_slot ) >= 0 ) {
		                                    $pattern_to_be_removed = $time_slot . '|';
		                                    $drop_down = str_replace( $pattern_to_be_removed, '', $drop_down );
		                                }
		                            }
		                        }
		                    }
		                }
		            }
		
		        }
		    }
		}
		
		$resource_id 			= ( isset( $_POST['resource_id'] ) ) ? $_POST['resource_id'] : 0;
		$resource_lockoutdates 	= ( isset( $_POST['resource_lockoutdates'] ) ) ? $_POST['resource_lockoutdates'] : "";
		
		if( $resource_id != 0 ){
		    if ( strlen( $resource_lockoutdates ) > 0 ) {
		
		        $resource_dates_array = explode( ',', $resource_lockoutdates );
		
		        foreach ( $resource_dates_array as $date_key => $date_value ) {
		
		            $list_dates      = explode( '=>', $date_value );
		            $list_dates[ 0 ] = substr( stripslashes( $list_dates[ 0 ] ), 1, -1 );
		
		            if ( $list_dates[ 0 ] == $_POST[ 'current_date' ] ) {
		
		                // convert the time slot in the format in which it is being displayed
		                $time_slot_explode = explode( '-', $list_dates[1] );
		                $time_slot = date( $time_format_to_show, strtotime( $time_slot_explode[0] ) );
		                $time_slot .= ' - ' . date( $time_format_to_show, strtotime( $time_slot_explode[1] ) );
		
		                if ( strpos( $drop_down, $time_slot ) >= 0 ) {
		                    $pattern_to_be_removed = $time_slot . '|';
		                    $drop_down = str_replace( $pattern_to_be_removed, '', $drop_down );
		                }
		            }
		        }
		    }
		}
		
		$drop_down = apply_filters( 'bkap_edit_display_timeslots', $drop_down );
		return $drop_down;
	}
	
	/**
	 * Pre select the fixed block
	 * 
	 * This function is called only when fixed blocks are enabled and 
	 * a search is performed in the search widget. It pre populates the correct block
	 * based on the date selected.
	 * 
	 * @since 2.9
	 */
	public static function bkap_prepopulate_fixed_block( $duplicate_of ) {
	    //$bkap_block_booking = new bkap_block_booking();
	
	    // find the difference in days between the start & end date
	    $date2         =   new DateTime( $_SESSION[ 'end_date' ] );
	    $date1         =   new DateTime( $_SESSION[ 'start_date' ] );
	    $diff_dates_selected = bkap_common::dateTimeDiff( $date2, $date1 );
	    $diff_dates = $diff_dates_selected->days;
	    $day_chosen = date( 'N', strtotime( $_SESSION[ 'start_date' ] ) );
	
	    $block_value = '';
	    $fixed_blocks = bkap_block_booking::bkap_get_fixed_blocks( $duplicate_of );
	    foreach ( $fixed_blocks as $key => $value ) {
	        if ( is_numeric( $value['start_day'] ) ) {
	            if ( $day_chosen == $value['start_day'] ) {
	                $block_value = $value['start_day'] . '&' . $value['number_of_days'] . '&' . $value['price'];
	                $block_start_day = $value['start_day'];
	                $block_days = $value['number_of_days'];
	                $block_price = $value['price'];
	                break;
	            }
	        }
	    }
	
	    if ( $block_value == '' ) { // no exact match found
	        foreach ( $fixed_blocks as $key => $value ) {
	            if ( $value['number_of_days'] == $diff_dates && $value['start_day'] == 'any_days' ) {
	                $block_value = $value['start_day'] . '&' . $value['number_of_days'] . '&' . $value['price'];
	                $block_start_day = $value['start_day'];
	                $block_days = $value['number_of_days'];
	                $block_price = $value['price'];
	                break;
	            }
	        }
	    }
	
	    if ( $block_value == '' ) { // no match found for the number of days either
	        foreach ( $fixed_blocks as $key => $value ) {
	            if ( $value['start_day'] == 'any_days' ) {
	                $block_value = $value['start_day'] . '&' . $value['number_of_days'] . '&' . $value['price'];
	                $block_start_day = $value['start_day'];
	                $block_days = $value['number_of_days'];
	                $block_price = $value['price'];
	                break;
	            }
	        }
	    }
	
	    if ( '' != $block_value ) {
	        ?>
            <script type="text/javascript">
            jQuery("#block_option").val("<?php echo $block_value;?>");
            jQuery("#block_option_start_day").val("<?php echo $block_start_day; ?>");
			jQuery("#block_option_number_of_day").val("<?php echo $block_days; ?>");
			jQuery("#block_option_price").val("<?php echo $block_price; ?>");
            </script>
            <?php 
        }
		        					            
	}
	
	/**
	 * Return the first available date from the given date
	 * 
	 * This function returns the first available date from the given date
	 * @param int $duplicate_of - product ID
	 * @param array $lockout_dates_array - array containing locked dates
	 * @param str $date j-n-Y format
	 * @return str $date j-n-Y format
	 * 
	 * @since 3.0
	 */
	public static function bkap_first_available( $duplicate_of, $lockout_dates_array, $date ) {
	     
	    $global_holidays   = array();
	    $recurring_date    = array();
	    $holiday_array     = array();
	    $booking_dates_arr = array();
	    
	    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	     
	    if ( isset( $global_settings->booking_global_holidays ) ) {
	        $global_holidays = explode( ',', $global_settings->booking_global_holidays );
	    } 
	     
	    $booking_settings = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );

		$custom_holiday_ranges = get_post_meta( $duplicate_of, '_bkap_holiday_ranges', true );

	    if ( isset( $booking_settings['booking_recurring'] ) ) {
	        $recurring_date = $booking_settings['booking_recurring'];
	    } 
	     
        if ( is_array( $booking_settings['booking_product_holiday'] ) && count( $booking_settings['booking_product_holiday'] ) > 0 ) {
	        $holiday_array = array_keys( $booking_settings['booking_product_holiday'] );
	    }

	    // fetch specific booking dates
	    if( isset( $booking_settings['booking_specific_date'] ) ){
	        $booking_dates_arr = $booking_settings['booking_specific_date'];
	    }

	    // Removing specific date if the date is set as holiday at product level
        if( $booking_dates_arr != "" && count( $booking_dates_arr ) > 0 && count( $holiday_array ) > 0 ){
            $booking_dates_arr = bkap_booking_process::bkap_check_specificdate_in_holiday( $booking_dates_arr, $holiday_array );
        }
        
        // Removing specific date if the date is set as holiday at global level
        if( $booking_dates_arr != "" && count( $booking_dates_arr ) > 0 && count( $global_holidays ) > 0 ){
            $booking_dates_arr = bkap_booking_process::bkap_check_specificdate_in_global_holiday( $booking_dates_arr, $global_holidays );
        }

	    $date_updated = '';
	    $min_day = date( 'w', strtotime( $date ) );

        $numbers_of_days_to_choose  = isset( $booking_settings['booking_maximum_number_days'] ) ? $booking_settings['booking_maximum_number_days'] - 1 : "";

        $current_time = current_time( 'timestamp' );
        $max_booking_date =   apply_filters( 'bkap_max_date' , $current_time, $numbers_of_days_to_choose, $booking_settings );

        $date_diff = strtotime( $max_booking_date ) - $current_time;
        $diff_days = absint( floor( $date_diff / (60 * 60 * 24) ) );

	    for( $i = 0; $i<=$diff_days; $i++ ) {

	    	$booking_type = get_post_meta( $duplicate_of, '_bkap_booking_type', true );
	    	$time_slots_missing = false;
	    	if ( $booking_type === 'date_time' ) {
	    		$time_slots_missing = true;

	    		$day_has_timeslot = bkap_common::bkap_check_timeslot_for_weekday( $duplicate_of, $date );
				$time_slots = explode( '|', bkap_booking_process::get_time_slot( $date, $duplicate_of ) );
				if ( $day_has_timeslot && sanitize_key( $time_slots[0] ) !== "" && sanitize_key( $time_slots[0] ) !== 'error' ) {
					$time_slots_missing = false;
				}
	    	}

	    	$custom_holiday_present = false;
	    	if ( is_array( $custom_holiday_ranges ) && count( $custom_holiday_ranges ) > 0 ) {
	    		foreach ( $custom_holiday_ranges as $custom_key => $custom_value ) {
	    			if ( strtotime( $custom_value['start'] ) <= strtotime( $date ) &&
	    				 strtotime( $custom_value['end'] ) >= strtotime( $date ) ) {
	    				$custom_holiday_present = true;
	    				break;
	    			}
	    		}
	    	}
	    	
	        if( isset( $booking_settings['booking_recurring_booking'] ) && "on" == $booking_settings['booking_recurring_booking'] && $booking_type != 'multiple_days' ){
    	        if ( isset( $recurring_date['booking_weekday_'.$min_day] ) && $recurring_date['booking_weekday_'.$min_day] == 'on' ) { 
        	        if ( in_array( $date, $holiday_array ) || in_array( $date, $global_holidays ) || in_array( $date, $lockout_dates_array ) || $custom_holiday_present || $time_slots_missing ) {
        	            $date = date( 'j-n-Y', strtotime( '+1day', strtotime( $date ) ) );
        	            $date_updated = 'YES';
        	             
        	            if ( $min_day < 6 ) {
        	                $min_day = $min_day + 1;
        	            } else {
        	                $min_day = $min_day - $min_day;
        	            }
        	             
        	        } else {
        	            $date_updated = 'NO';
        	        }
    	        }
	        }else if ( is_array( $booking_dates_arr ) && count( $booking_dates_arr ) > 0 ) {
	        	$date_updated = 'NEEDS_UPDATE';
	        }else {

	        	$bkap_fixed_blocks = get_post_meta( $duplicate_of, '_bkap_fixed_blocks', true );
	        	
	        	if( $booking_type == 'multiple_days' && $bkap_fixed_blocks == 'booking_fixed_block_enable' ){
	        		
	        		$bkap_fixed_blocks_data = bkap_block_booking::bkap_get_fixed_blocks( $duplicate_of );

	        		
	        		if ( count($bkap_fixed_blocks_data) > 0 ) {     		
		        		
		        		$first_block = bkap_booking_process::bkap_first_available_date_fixed_block( $bkap_fixed_blocks_data, $min_day );		        		

		        		if ( $first_block['start_day'] == 'any_days') {
		        			//$date 			= '';
		        			$date_updated 	= 'NO';
		        		} else {
		        			$fix_min_day 	= $first_block['start_day'];
		        			$fixed_min_day 	= date( 'w', strtotime( $date ) );

		        			while ( $fix_min_day != $fixed_min_day ) {
		        				
		        				$date 			= date( 'j-n-Y', strtotime( '+1day', strtotime( $date ) ) );
		        				$fixed_min_day 	= date( 'w', strtotime( $date ) );
		        				$date_updated 	= 'NO';
		        			}
		        		}
		        	}	        		
	        	}else{

	        		if ( in_array( $date, $holiday_array ) || in_array( $date, $global_holidays ) || in_array( $date, $lockout_dates_array ) || $custom_holiday_present || $time_slots_missing ) {
        	            $date = date( 'j-n-Y', strtotime( '+1day', strtotime( $date ) ) );
        	            $date_updated = 'YES';
        	             
        	            if ( $min_day < 6 ) {
        	                $min_day = $min_day + 1;
        	            } else {
        	                $min_day = $min_day - $min_day;
        	            }
        	             
        	        } else {
        	            $date_updated = 'NO';
        	        }	        		
	        	}
	        }

	        if ( 'NO' == $date_updated ) {
	            break;
	        } else {
                $date_enabled = 'NO';
	            if ( is_array( $recurring_date ) && count( $recurring_date ) > 0 ) {
	                if ( isset( $recurring_date['booking_weekday_'.$min_day] ) && $recurring_date['booking_weekday_'.$min_day] == 'on' ) {
	                    $date_enabled = 'YES';
	                } else {
	                    $date_enabled = 'NO';
	                }
	            }
	            if ( is_array( $booking_dates_arr ) && count( $booking_dates_arr ) > 0 && 'NO' == $date_enabled ) {
	                // @since 4.0.0 they are now saved as date (key) and lockout (value)
	                if ( array_key_exists( $date, $booking_dates_arr ) ) {
	                    $date_enabled = 'YES';
	                    break;
	                } else {
	                    $date_enabled = 'NO';
	                }
	            }
	            if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && 'on' == $booking_settings[ 'booking_enable_multiple_day' ] ) {
	                $date_enabled = 'YES';
	                break;
	            }
	        }

	        if ( 'NO' == $date_enabled ) {
	            $date = date( 'j-n-Y', strtotime( '+1day', strtotime( $date ) ) );
	            $date_updated = 'YES';
	        
	            if ( $min_day < 6 ) {
	                $min_day = $min_day + 1;
	            } else {
	                $min_day = $min_day - $min_day;
	            }
	        }
	         
	    }
	    return $date;
	}

	public static function bkap_first_available_date_fixed_block( $bkap_fixed_blocks_data, $min_day ) {
		$block_array = array();
			
		foreach ( $bkap_fixed_blocks_data as $key => $value ) {
			
			$f_key = "";
			$block_array[ $key ] = $value['start_day'];
			
			if( $value['start_day'] == $min_day || $value['start_day'] == 'any_days' ) {
				$f_key = $key;
				break;
			}
		}

		if ( $f_key == "" ) {
			return reset( $bkap_fixed_blocks_data );
		} else {
			return $bkap_fixed_blocks_data[$f_key] ;
		}
	}
	
	/**
	 * Return the specific date array excluding product holidays
	 * 
	 * This function returns the specific date array which will not have any holiday dates
	 * @param array $specific_date - array containing specific dates
	 * @param array $holiday_array - array containing product level holidays
	 * @return array $specific_date
	 * 
	 * @since 4.1.3
	 */
	
	public static function bkap_check_specificdate_in_holiday( $specific_date, $holiday_array ){
	
	    $holiday_array_keys = array_keys( $holiday_array );
	    foreach( $specific_date as $specific_date_key => $specific_date_value ){
	        if( in_array( $specific_date_key, $holiday_array_keys ) ){
	            unset( $specific_date[ $specific_date_key ] );
	        }
	    }
	
	    return $specific_date;
	
	}
	
	/**
	 * Return the specific date array excluding global holidays
	 *
	 * This function returns the specific date array which will not have any holiday dates added at global level
	 * @param array $specific_date - array containing specific dates
	 * @param array $global_holidays - array containing global level holidays
	 * @return array $specific_date
	 *
	 * @since 4.1.3
	 */
	
	public static function bkap_check_specificdate_in_global_holiday( $specific_date, $global_holidays ){
	
	    foreach( $specific_date as $specific_date_key => $specific_date_value ){
	        if( in_array( $specific_date_key, $global_holidays ) ){
	            unset( $specific_date[ $specific_date_key ] );
	        }
	    }
	     
	    return $specific_date;
	
	}
	
	/**
	 * Add any unlimited booking slots that might be present
	 * for the date/day
	 * @param str $display_slots
	 * @since 4.4.0
	 */
	public static function bkap_add_unlimited_slots( $dropdown ) {
	     
	    $display = $dropdown;
	
	    // product ID and booking date
	    $product_id = isset( $_POST[ 'post_id' ] ) ? $_POST[ 'post_id' ] : '';
	    $date = isset( $_POST[ 'current_date' ] ) ? $_POST[ 'current_date' ] : '';
	     
	    if( $product_id > 0 && $date !== '' ) {
	        $date_ymd = date( 'Y-m-d', strtotime( $date ) );
	         
	        $times_array = explode( '|', $display ); // setup the times being displayed as an array for easier validation accessibility

	        array_pop( $times_array ); 
	
	        // set up the different time formats
	        $global_settings   =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
	        if ( isset( $global_settings ) ) {
	            $time_format = $global_settings->booking_time_format;
	        } else {
	            $time_format = '12';
	        }
	
	        $time_format_db_value = 'G:i';
	        if ( $time_format == '12' ) {
	            $time_format_to_show = 'h:i A';
	        } else {
	            $time_format_to_show = 'H:i';
	        }
	
	        // Booking settings
	        $booking_settings =   get_post_meta( $product_id, 'woocommerce_booking_settings', true );
	         
	        $advance_booking_hrs = 0;
	        if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
	            $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
	        }
	
	        // get a date object of today's date & time
	        $current_time         =   current_time( 'timestamp' );
	        $date_today           =   date( "Y-m-d G:i", $current_time );
	        $today                =   new DateTime( $date_today );
	
	        global $wpdb;
	
	        $query_unlimited = "SELECT * FROM `" . $wpdb->prefix . "booking_history`
	                           WHERE post_id = %d
	                           AND start_date = %s
	                           AND total_booking = 0
	                           AND available_booking = 0
	                           ORDER BY STR_TO_DATE(from_time,'%H:%i')";
	         
	        $set_unlimited = $wpdb->get_results( $wpdb->prepare( $query_unlimited, $product_id, $date_ymd ) );
	         
	        $weekday = date( 'w', strtotime( $date ) );
	        $weekday = "booking_weekday_$weekday";
	         
	        // check for the base records
	        $base_unlimited = "SELECT * FROM `" . $wpdb->prefix . "booking_history`
                           WHERE post_id = %d
                           AND weekday = %s
	                       AND start_date = '0000-00-00'
                           AND total_booking = 0
                           AND available_booking = 0
                           ORDER BY STR_TO_DATE(from_time,'%H:%i')";
	         
	        $base_set_unlimited = $wpdb->get_results( $wpdb->prepare( $base_unlimited, $product_id, $weekday ) );
	
	        $specific = false;
	        if( is_array( $set_unlimited ) && count( $set_unlimited ) > 0 ) {
	            // check if it's a specific date record, if yes.. then no need to check the base list
	            foreach( $set_unlimited as $value ) {
	                if( $value->weekday === '' ) {
	                    $specific = true;
	                }
	                 
	                if ( $value->from_time != '' ) {
	                    $from_time_show      =   date( $time_format_to_show, strtotime( $value->from_time ) );
	                    $from_time_db_value  =   date( $time_format_db_value, strtotime( $value->from_time ) );
	                } else {
	                    $from_time_show      =   $from_time_db_value = "";
	                }
	                 
	                $include      =   'YES';
	                $booking_time =   $date . $from_time_db_value;
	                $date2        =   new DateTime( $booking_time );
	                 
	                if ( version_compare( phpversion(), '5.3', '>' ) ) {
	                    $difference   =   $date2->diff( $today );
	                }else{
	                    $difference   =   bkap_common::dateTimeDiff( $date2, $today );
	                }
	                 
	                if ( $difference->days > 0 ) {
	                    $days_in_hour    =   $difference->h + ( $difference->days * 24 ) ;
	                    $difference->h   =  $days_in_hour;
	                }
	
	                if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
	                    $include = 'NO';
	                }
	                
	                $to_time_show = '';

                    if( $value->to_time !== '' ) {
                       $to_time_show = date( $time_format_to_show, strtotime( $value->to_time ) );
                    }

                    if ( $to_time_show != '' ) {
                    	$bkap_time_slot = "$from_time_show - $to_time_show";
                    }else{
                    	$bkap_time_slot = "$from_time_show";
                    }

	                
	                if ( $include == 'YES' && ! in_array( trim( $bkap_time_slot ), $times_array ) ) {
	                     
	                    if ( $to_time_show != '' ) {
	                        $to_time_show       =   date( $time_format_to_show, strtotime( $value->to_time ) );
	                        $to_time_db_value   =   date( $time_format_db_value, strtotime( $value->to_time ) );
	                        $display         .=   $from_time_show." - ".$to_time_show."|";
	                        $times_array[]      = "$from_time_show - $to_time_show";
	                    } else {
	                        $display         .=   $from_time_show."|";
	                        $to_time_show       =   $to_time_db_value = "";
	                        $times_array[]      =   $from_time_show;
	                    }
	
	                }
	                 
	            }
	        }
	         
	        // check the recurring base records if specific is false
	        if( ! $specific ) {
	             
	            if( is_array( $base_set_unlimited ) && count( $base_set_unlimited ) > 0 ) {
	                // check if it's a specific date record, if yes.. then no need to check the base list
	                foreach( $base_set_unlimited as $value ) {
	
	                    if ( $value->from_time != '' ) {
	                        $from_time_show      =   date( $time_format_to_show, strtotime( $value->from_time ) );
	                        $from_time_db_value  =   date( $time_format_db_value, strtotime( $value->from_time ) );
	                    } else {
	                        $from_time_show      =   $from_time_db_value = "";
	                    }
	
	                    $include      =   'YES';
	                    $booking_time =   $date . $from_time_db_value;
	                    $date2        =   new DateTime( $booking_time );
	
	                    if ( version_compare( phpversion(), '5.3', '>' ) ) {
	                        $difference   =   $date2->diff( $today );
	                    }else{
	                        $difference   =   bkap_common::dateTimeDiff( $date2, $today );
	                    }
	
	                    if ( $difference->days > 0 ) {
	                        $days_in_hour    =   $difference->h + ( $difference->days * 24 ) ;
	                        $difference->h   =  $days_in_hour;
	                    }
	                     
	                    if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
	                        $include = 'NO';
	                    }

	                    $to_time_show = '';

	                    if( $value->to_time !== '' ) {
	                       $to_time_show = date( $time_format_to_show, strtotime( $value->to_time ) );
	                    }


	                    if ( $to_time_show != '' ) {
	                    	$bkap_time_slot = "$from_time_show - $to_time_show";
	                    }else{
	                    	$bkap_time_slot = "$from_time_show";
	                    }
	                    
	                    if ( $include == 'YES' && ! in_array( trim( $bkap_time_slot ), $times_array ) ) {
	
	                        if ( $to_time_show != '' ) {
	                            $to_time_show       =   date( $time_format_to_show, strtotime( $value->to_time ) );
	                            $to_time_db_value   =   date( $time_format_db_value, strtotime( $value->to_time ) );
	                            $display         .=   $from_time_show." - ".$to_time_show."|";
	                            $times_array[]      =  "$from_time_show - $to_time_show";
	                        } else {
    	                        $display         .=   $from_time_show."|";
    	                        $to_time_show       =   $to_time_db_value = "";
    	                        $times_array[]      =  $from_time_show;
	                        }
	                         
	                        // insert the record for the date in the table
	                        $insert_date   =   "INSERT INTO `".$wpdb->prefix."booking_history`
	                        (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
    										VALUES (
    										'".$product_id."',
	    										'".$weekday."',
	    										'".$date_ymd."',
	    										'0000-00-00',
    										'".$from_time_db_value."',
	    										'".$to_time_db_value."',
	    										'".$value->total_booking."',
	    										'".$value->available_booking."' )";
	    										$wpdb->query( $insert_date );
	    											
	                    }
	
                    }
                }

            }
        }
 
        return $display;
	}
}
?>