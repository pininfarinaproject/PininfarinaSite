<?php

add_action( 'widgets_init', 'bkap_widgets_init' );
// This filter is to calculate the maximum date based on the numbers of dates to choose for Recurring Weekdays
add_filter( 'bkap_max_date', 'calback_bkap_max_date', 10 , 3 );

function calback_bkap_max_date( $m_d, $max_dates, $booking_set ){

    $next_date = $m_d;
    for( $i = 0 ; $i < 1000 ; $i++ ){

        $stt = "";
        $stt = date( "w", strtotime( $next_date ) );
        $stt = "booking_weekday_".$stt;

        if( $max_dates >= 0 ){
            if( $booking_set['booking_recurring'][$stt] == 'on' ){
                if ( isset( $booking_set['booking_date_range'] ) && count( $booking_set['booking_date_range'] ) > 0 ) {
                    foreach ( $booking_set['booking_date_range'] as $range_value ) {
                        if ( strtotime( $range_value['start'] ) < strtotime( $next_date ) && strtotime( $range_value['end'] ) > strtotime( $next_date ) ) {

                            $m_d = $next_date;
                            $max_dates--;
                        }
                    }
                }else{
                    $m_d = $next_date;
                    //$m_d = addDayswithdate( $m_d , 1 );
                    $max_dates--;
                }
            }elseif ( isset( $booking_set['booking_specific_date'] ) && is_array( $booking_set['booking_specific_date'] ) && count( $booking_set['booking_specific_date'] ) > 0 ) {
                if ( in_array( $next_date, array_keys( $booking_set['booking_specific_date'] ) ) ) {
                    $m_d = $next_date;
                }
                $max_dates--;
            }
            $next_date = addDayswithdate( $next_date, 1 );
        }else{
            break;
        }
    }

    return $m_d;
}

function addDayswithdate( $date,$days ){

    if ( is_numeric ( $date ) ) {
        $date = strtotime("+".$days." days", $date);
    }else{
        $date = strtotime("+".$days." days", strtotime($date));
    }
    return  date("j-n-Y", $date);

}
/****************************
 * This function initialize the wideget , and register the same.
 *****************************/
function bkap_widgets_init() {
	include_once( "widget-product-search.php" );
	register_widget( 'Custom_WooCommerce_Widget_Product_Search' );
}

function check_in_range( $start_date, $end_date, $date_from_user ) {
    // Convert to timestamp
    $start_ts   =   strtotime( $start_date );
    $end_ts     =   strtotime( $end_date );
    $user_ts    =   strtotime( $date_from_user );

    // Check that user date is between start & end
    return ( ( $user_ts >= $start_ts ) && ( $user_ts <= $end_ts ) );
}

function check_in_range_abp( $start_date, $end_date, $date_from_user ) {
    // Convert to timestamp
    $start_ts           =   strtotime( $start_date );
    $end_ts             =   strtotime( $end_date );
    $user_ts            =   strtotime($date_from_user);
    $return_value       =   array() ;
    $new_week_days_arr  =   array();
    
    while ( $start_ts <= $end_ts ) {
        $new_week_days_arr []   =   $start_date;
        $start_ts               =   strtotime( '+1 day', $start_ts );
        $start_date             =   date( "j-n-Y", $start_ts );
    }

    foreach ( $new_week_days_arr as $weekday_key => $weekday_value ){

        $week_day_value = strtotime( $weekday_value );

        if ( $week_day_value == $user_ts ){
            $return_value [ $weekday_value ] = true;
        }else if ( $week_day_value >= $user_ts ){
            $return_value [ $weekday_value ] = true;
        }else {
            $return_value [ $weekday_value ] = false;
        }
    }
    return $return_value;
}

function check_in_range_weekdays( $start_date, $end_date, $recurring_selected_weekdays ) {
    
    $start_ts           =   strtotime( $start_date );
    $end_ts             =   strtotime( $end_date );
    $return_value       =   array();
    $new_week_days_arr  =   array();
    
    while ( $start_ts <= $end_ts ) {

        if ( !in_array( date( 'w', $start_ts ), $new_week_days_arr ) ) {
            $new_week_days_arr [] = date( 'w', $start_ts );
        }else if (!in_array( date( 'w',$end_ts ), $new_week_days_arr ) ) {
            $new_week_days_arr [] =  date( 'w',$end_ts );
        }

        $start_ts = strtotime( '+1 day', $start_ts );
    }

    foreach ( $recurring_selected_weekdays as $weekday_key => $weekday_value ){

        $week_day_value = substr( $weekday_key, -1 );
        
        if ( $weekday_value == 'on' && in_array ( $week_day_value, $new_week_days_arr ) ){ 
            $return_value [] = true;
        }else{
            $return_value [] = false;
        }
    }
    return $return_value;
}

function check_in_range_holidays( $start_date, $end_date, $recurring_selected_weekdays ) {

    $start_ts           =   strtotime( $start_date );
    $end_ts             =   strtotime( $end_date );
    $return_value       =   array();
    $new_week_days_arr  =   array();

    while ($start_ts <= $end_ts) {

        $new_week_days_arr []   =   $start_date;
        $start_ts               =   strtotime( '+1 day', $start_ts );
        $start_date             =   date( "j-n-Y", $start_ts );
    }
    
    foreach ( $new_week_days_arr as $weekday_key => $weekday_value ){

        $week_day_value = strtotime( $weekday_value );
        
        if ( is_array( $recurring_selected_weekdays ) && in_array ($weekday_value, $recurring_selected_weekdays ) ){ 
            $return_value [ $weekday_value ] = true;
        }else{

            $return_value [ $weekday_value ] = false;
        }
    }
    return $return_value;
}

// Fixed Block Booking function

function check_in_fixed_block_booking( $start_date, $end_date, $days ) {
    
    $start_ts           =   strtotime( $start_date );
    $end_ts             =   strtotime( $end_date );
    $return_value       =   array();
    $new_week_days_arr  =   array();
    $weekdays_array     =   array(  'Sunday'      => '0',
                                    'Monday'      => '1',
                                    'Tuesday'     => '2',
                                    'Wednesday'   => '3',
                                    'Thursday'    => '4',
                                    'Friday'      => '5',
                                    'Saturday'    => '6'
                                );
    
        
    $flag = "F";
    //$week_day_value     =   strtotime( $weekday_value );
    $min_day            =   date( "l", $start_ts );
    $min_value          =   $weekdays_array[ $min_day ];
    
    if( in_array( $min_value, $days ) || in_array( 'any_days', $days )  ){
        $flag = "T";
    }
    
    if ( $flag == "T" ){
       $return_value [ $start_date ] = true;
    }else{
        $return_value [ $start_date ] = false;
    }
    
    return $return_value;
}

// Custom Range function

/*function bkap_check_in_custom_holiday_range ( $start_date, $end_date, $custom_start_date, $custom_end_date ) {
    
    $start_ts            =   strtotime( $start_date );  
    $end_ts              =   strtotime( $end_date );      
    $new_custom_array    =   array();
    $custom_return_value =   array();

     while ($start_ts <= $end_ts) {

        $new_custom_array []   =   $start_date;
        
        $start_ts               =   strtotime( '+1 day', $start_ts );
        $start_date             =   date( "j-n-Y", $start_ts );
       
    } 
    

    foreach ($new_custom_array as $key => $value) {
       
        $custom_values = strtotime( $value );  

        if ( $custom_values >= strtotime ($custom_start_date) && $custom_values <= strtotime ($custom_end_date ) ) { 
           
            $custom_return_value [$value] = true;
        }else{

            $custom_return_value [$value] = false;
        }

    } 
    
    return $custom_return_value;   


}*/


/***********************************
*Modify current search by adding where clause to cquery fetching posts
************************************/
function bkap_get_custom_posts($where, $query){
	
    global $wpdb;
	
	$booking_table =   $wpdb->prefix . "booking_history";
	$meta_table    =   $wpdb->prefix . "postmeta";
	$post_table    =   $wpdb->prefix . "posts";
	
	if( !empty( $_GET["w_check_in"] )  && $query->is_main_query() ){
		
	    $chkin        =   $_GET["w_checkin"];  
		$chkout       =   $_GET["w_checkout"];
	
		$start_date   =   $chkin;
		$end_date     =   $chkout;
	
		$language_selected    =   '';
		
		if ( defined('ICL_LANGUAGE_CODE') )
		{
			if( constant('ICL_LANGUAGE_CODE') != '' )
			{
				$wpml_current_language = constant('ICL_LANGUAGE_CODE');
				if ( !empty( $wpml_current_language ) ) {
					$language_selected = $wpml_current_language;
				}
			}
		}
		if( $language_selected != '' )
		{
			$icl_table = $wpdb->prefix . "icl_translations";
						
			$get_product_ids =  "Select id From $post_table WHERE post_type = 'product' AND post_status = 'publish' AND $wpdb->posts.ID IN
			                    (SELECT b.post_id FROM $booking_table AS b WHERE ('$start_date' between b.start_date and date_sub(b.end_date,INTERVAL 1 DAY))
			                    or
			                    ('$end_date' between b.start_date and date_sub(b.end_date,INTERVAL 1 DAY))
                    			or
                    			(b.start_date between '$start_date' and '$end_date')
                    			or
                    			b.start_date = '$start_date'
                    			) and $wpdb->posts.ID NOT IN(SELECT post_id from $meta_table
                    			where meta_key = 'woocommerce_booking_settings' and meta_value LIKE '%booking_enable_date\";s:0%') and $wpdb->posts.ID NOT IN
                    			(SELECT a.id FROM $post_table AS a LEFT JOIN $meta_table AS b
                    			ON
                    			a.id = b.post_id
                    			AND
                    			( b.meta_key = 'woocommerce_booking_settings' )
                    			WHERE
                    			b.post_id IS NULL) AND $wpdb->posts.ID IN
                    			(SELECT element_id FROM $icl_table
                    			WHERE language_code = '".ICL_LANGUAGE_CODE."')";
			
			$results_date        =   $wpdb->get_results( $get_product_ids );
			 
			$new_arr_product_id  =   array();
			 
			foreach( $results_date as $product_id_key => $product_id_value ){
			
			$booking_settings    =   get_post_meta( $product_id_value->id, 'woocommerce_booking_settings', true );
			 
			if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
			 
    			$query_date    =   "SELECT DATE_FORMAT(start_date,'%d-%c-%Y') as start_date,DATE_FORMAT(end_date,'%d-%c-%Y') as end_date FROM ".$wpdb->prefix."booking_history
    			                   WHERE (start_date >='".$chkin."' OR end_date <='".$chkout."') AND post_id = '".$product_id_value->id."'";
    			
    	        $results_date  =   $wpdb->get_results( $query_date );
			
    	  
    	        $dates_new     =   array();
    	        $booked_dates  =   array();
	            
	            if ( isset( $results_date ) && count( $results_date ) > 0 && $results_date != false ) {
        			
	                foreach( $results_date as $k => $v ) {
            			$start_date_lockout  =   $v->start_date;
            			$end_date_lockout    =   $v->end_date;
            			$dates               =   bkap_common::bkap_get_betweendays( $start_date_lockout, $end_date_lockout );
            			$dates_new           =   array_merge( $dates, $dates_new );
        			}
        			
			    }
    			
    			$dates_new_arr   =   array_count_values( $dates_new );
    			
    			$lockout         =   0 ;
    			
    			if ( isset( $booking_settings[ 'booking_date_lockout' ] ) ) {
    			     $lockout    =   $booking_settings[ 'booking_date_lockout' ];
    			}
    			
    			foreach( $dates_new_arr as $k => $v ) {
    			    
    			    if( $v >= $lockout && $lockout != 0 ) {
    			     
    			         if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
    			             $new_arr_product_id[] = $product_id_value->id;
    			         }
    			 
    			    }
    			}
			}else {
			    $lockout_query   =   "SELECT DISTINCT start_date FROM `".$wpdb->prefix."booking_history`
                    			     WHERE post_id= %d
                    			     AND total_booking > 0
                    			     AND available_booking <= 0";
			    $results_lockout =   $wpdb->get_results ( $wpdb->prepare( $lockout_query, $product_id_value->id ) );
			
			    $lockout_query   =   "SELECT DISTINCT start_date FROM `".$wpdb->prefix."booking_history`
                    			     WHERE post_id= %d
                    			     AND available_booking > 0";
			    $results_lock    =   $wpdb->get_results ( $wpdb->prepare( $lockout_query, $product_id_value->id ) );
		        
			    $lockout_date    =   '';
			    	
			    foreach ( $results_lockout as $k => $v ) {

			        foreach( $results_lock as $key => $value ) {
			              
			            if ( $v->start_date == $value->start_date ) {
			                 $date_lockout       =   "SELECT COUNT(start_date) FROM `".$wpdb->prefix."booking_history`
                    			                     WHERE post_id= %d
                    							     AND start_date= %s
                    							     AND available_booking = 0";
			                 $results_date_lock  =   $wpdb->get_results( $wpdb->prepare( $date_lockout, $product_id_value->id, $v->start_date ) );
			                 
			                 if ( $booking_settings['booking_date_lockout'] > $results_date_lock[0]->{'COUNT(start_date)'} ) {
			                     unset( $results_lockout[ $k ] );			                     
        	                 }        	                 
			            }
			        }
			     }
			                	
			    foreach ( $results_lockout as $k => $v ) {
			     
			         if ( $v->start_date == $start_date ){

			             if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {			                 
			                 $new_arr_product_id[] = $product_id_value->id;			                 
			             }
			         }
			     }
			 }
		}
			
	    // this query is for spefic dates product when searched out of the specified date range
	            $get_all_product_ids      =   "Select id From $post_table WHERE post_type = 'product' AND post_status = 'publish' AND $wpdb->posts.ID NOT IN(SELECT post_id from $meta_table
                                    		  where meta_key = 'woocommerce_booking_settings' and meta_value LIKE '%booking_enable_date\";s:0%') and $wpdb->posts.ID NOT IN
                                    		  (SELECT a.id FROM $post_table AS a LEFT JOIN $meta_table AS b
                                    		  ON
                                    		  a.id = b.post_id
                                    		  AND
                                    		  ( b.meta_key = 'woocommerce_booking_settings' )
                                    		  WHERE
                                    		  b.post_id IS NULL )";
    		    $results_date             =   $wpdb->get_results( $get_all_product_ids );
    
    		    $global_settings          =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
    		    $book_global_holidays_arr =   array();
    		    
    		    if ( isset( $global_settings->booking_global_holidays ) ) {
    		        $book_global_holidays      =   $global_settings->booking_global_holidays;
    	            $book_global_holidays_arr  =   explode( ",", $book_global_holidays );
                }
		                 
                foreach( $results_date as $product_id_key => $product_id_value ){
                /*******
                * This is for the product which have recurring and specififc both enabled.
                */
                $booking_settings       =   get_post_meta( $product_id_value->id, 'woocommerce_booking_settings', true );
                $specific_dates_check   =   isset( $booking_settings[ 'booking_specific_booking' ] ) ? $booking_settings[ 'booking_specific_booking' ] : "";
                $recurring_check        =   isset( $booking_settings[ 'booking_recurring_booking' ] ) ? $booking_settings[ 'booking_recurring_booking' ] : "";
                $return_value           =   array();
                
                if ( isset( $specific_dates_check ) && $specific_dates_check == 'on' && isset( $recurring_check ) && $recurring_check == 'on' ){

                $selected_specific_dates            =   $booking_settings[ 'booking_specific_date' ];
                $recurring_selected_weekdays        =   $booking_settings[ 'booking_recurring' ];
    
                $specific_advanced_booking_period   =   $booking_settings[ 'booking_minimum_number_days' ];
                $check_advanced_booking_period      =   array();
                $min_date                           =   '';
    
    
                if ( isset( $specific_advanced_booking_period ) && $specific_advanced_booking_period > 0) {
                    
                    $current_time       =   current_time( 'timestamp' );
                    // Convert the advance period to seconds and add it to the current time
                    $advance_seconds    =   $booking_settings['booking_minimum_number_days'] *60 *60;
                    $cut_off_timestamp  =   $current_time + $advance_seconds;
                    $cut_off_date       =   date( "d-m-Y", $cut_off_timestamp );
                    $min_date           =   date( "j-n-Y", strtotime( $cut_off_date ) );
    
                    if ( isset( $booking_settings['booking_maximum_number_days'] ) ) {
                        $days   =   $booking_settings['booking_maximum_number_days'];
                    }
    		                         
                    // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
                    if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
                        $current_date       =   date( 'j-n-Y', $current_time );
                            $last_slot_hrs  =   $current_slot_hrs = $last_slot_min = 0;
                            
                            if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
                                
                                foreach ( $booking_settings['booking_time_settings'][ $min_date ] as $key => $value ) {
                                    $current_slot_hrs = $value['from_slot_hrs'];
                                        
                                        if ( $current_slot_hrs > $last_slot_hrs ) {
                                             $last_slot_hrs = $current_slot_hrs;
                                             $last_slot_min = $value['to_slot_min'];
                                        }
                                }
                                
                            }else {
                            // Get the weekday as it might be a recurring day setup
                            $weekday            =   date( 'w', strtotime( $min_date ) );
                            $booking_weekday    =   'booking_weekday_' . $weekday;
                            
                            if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
                                
                                foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
                                    $current_slot_hrs = $value['from_slot_hrs'];

                                        if ( $current_slot_hrs > $last_slot_hrs ) {
                                            $last_slot_hrs = $current_slot_hrs;
                                            $last_slot_min = $value['to_slot_min'];
                                        }
                                    }
                                }
                            }
                            
                            $last_slot              =   $last_slot_hrs . ':' . $last_slot_min;
                            $advance_booking_hrs    =   0;
                            
                            if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
                                $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
                            }
                            
                            $booking_date2  =   $min_date . $last_slot;
                            $booking_date2  =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
                            $date2          =   new DateTime( $booking_date2 );
                            $booking_date1  =   date( 'Y-m-d G:i', $current_time );
                            $date1          =   new DateTime( $booking_date1 );
                            
                            if ( version_compare( phpversion(), '5.3', '>' ) ) {
                                $difference     =   $date2->diff( $date1 );
                            }else{
                                $difference     =   bkap_common::dateTimeDiff( $date2, $date1 );
                            }
                            
                            if ( $difference->days > 0 ) {
                                $difference->h += $difference->days * 24;
                            }
          
                            if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
                                $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
                            }
                        }
    
                        $check_advanced_booking_period  = check_in_range_abp ( $start_date, $end_date, $min_date );
                            
                            if ( !in_array( true, $check_advanced_booking_period, true ) ){
                                
                                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                    $new_arr_product_id[] = $product_id_value->id;
                                }
                            }
                        }
    
    
                        $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                        $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                            
                            foreach ( $selected_specific_dates as $date_key => $date_value ){
                                $return_value [] = check_in_range( $new_start_date, $new_end_date, $date_value );
                            }
    
                        $return_value_recurring = check_in_range_weekdays ( $start_date, $end_date, $recurring_selected_weekdays );
    
    
                        if ( !in_array( true, $return_value, true ) && !in_array( true, $return_value_recurring, true )){
                            
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                $new_arr_product_id[] = $product_id_value->id;
                            }
    
                        }
    
                        /****
                        * This check if product have any holidays:
                        */
    
                        $product_holidays = isset( $booking_settings[ 'booking_product_holiday' ] ) ? $booking_settings[ 'booking_product_holiday' ] : array();
    
                        if( isset( $product_holidays ) && count( $product_holidays ) > 0 ){
                            $return_value           =   array();
    
                            $new_end_date           =   date( "j-n-Y", strtotime( $end_date ) );
                            $new_start_date         =   date( "j-n-Y", strtotime( $start_date ) );
                            
                                if( !empty( $product_holidays ) ){
                                    $product_holidays = array_flip( $product_holidays );
                                    $return_value = check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays );
    
    	                        }
    
                                if ( !in_array( false, $return_value, true ) ){
    
    	                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
    		                            $new_arr_product_id[] = $product_id_value->id;
    	                            }
                                }
                        }
    
                        /*****
                        * Check for global holidays
                        */
                        if( !empty ( $book_global_holidays_arr ) ){
                            $return_value   =   array();
                            $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                            $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
    
                            $return_value   =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
    
                            if ( !in_array( false, $return_value, true ) ){
    
                                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                    $new_arr_product_id[] = $product_id_value->id;
                                }
                            }
                        }
                  } else if( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == "on" ) {
    
                    $multiple_advanced_booking_period   =   $booking_settings[ 'booking_minimum_number_days' ];
                    $check_advanced_booking_period      =   array();
                    $min_date                           =   '';
    
                    if ( isset( $multiple_advanced_booking_period ) && $multiple_advanced_booking_period > 0) {
                        $current_time = current_time( 'timestamp' );
                        // Convert the advance period to seconds and add it to the current time
                        $advance_seconds    =   $booking_settings[ 'booking_minimum_number_days' ] *60 *60;
                        $cut_off_timestamp  =   $current_time + $advance_seconds;
                        $cut_off_date       =   date( "d-m-Y", $cut_off_timestamp );
                        $min_date           =   date( "j-n-Y", strtotime( $cut_off_date ) );
    
                        if ( isset( $booking_settings['booking_maximum_number_days'] ) ) {
                            $days = $booking_settings['booking_maximum_number_days'];
                        }
    		                                     
                        // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
                        if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
                            $current_date   =   date( 'j-n-Y', $current_time );
                            $last_slot_hrs  =   $current_slot_hrs = $last_slot_min = 0;
                            
                            if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
                                
                                foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {                                    
                                    $current_slot_hrs   =   $value['from_slot_hrs'];

                                        if ( $current_slot_hrs > $last_slot_hrs ) {
                                            $last_slot_hrs  =   $current_slot_hrs;
                                            $last_slot_min  =   $value['to_slot_min'];
                                        }
                                }
                          }else {
                                    // Get the weekday as it might be a recurring day setup
                                    $weekday            =   date( 'w', strtotime( $min_date ) );
                                    $booking_weekday    =   'booking_weekday_' . $weekday;
                                    
                                    if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {

                                        foreach ( $booking_settings['booking_time_settings'][ $booking_weekday ] as $key => $value ) {
                                            $current_slot_hrs = $value['from_slot_hrs'];
                                            
                                            if ( $current_slot_hrs > $last_slot_hrs ) {
                                                $last_slot_hrs  =   $current_slot_hrs;
                                                $last_slot_min  =   $value['to_slot_min'];
                                            }
                                        }
                                    }
                            }
                            
                            $last_slot              =   $last_slot_hrs . ':' . $last_slot_min;
                            $advance_booking_hrs    =   0;
                            
                            if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
                                $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
                            }
                            
                            $booking_date2  =   $min_date . $last_slot;
                            $booking_date2  =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
                            $date2          =   new DateTime( $booking_date2 );
                            $booking_date1  =   date( 'Y-m-d G:i', $current_time );
                            $date1          =   new DateTime( $booking_date1 );
                            
                            if ( version_compare( phpversion(), '5.3', '>' ) ) {
                                $difference     =   $date2->diff( $date1 );
                            }else{
                                $difference     =   bkap_common::dateTimeDiff( $date2, $date1 );
                            }
                
                            if ( $difference->days > 0 ) {
                                $difference->h += $difference->days * 24;
                            }
                		                                                 
                            if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
                                $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
                            }
                        }
                
                        $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                        $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                
                        $check_advanced_booking_period = check_in_range_abp ( $new_start_date, $new_end_date, $min_date );
                
                        if ( in_array( false, $check_advanced_booking_period, true ) ){
                            
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                $new_arr_product_id[] = $product_id_value->id;
                            }
                        }
                    }
            
                    $product_holidays   =   isset( $booking_settings[ 'booking_product_holiday' ] ) ? $booking_settings[ 'booking_product_holiday' ] : array();
            
                    if( isset( $product_holidays ) && count( $product_holidays ) > 0 ){
                    $return_value           =   array();
                
                    $new_end_date           =   date( "j-n-Y", strtotime( $end_date ) );
                    $new_start_date         =   date( "j-n-Y", strtotime( $start_date ) );
                    $product_holidays       =   array_flip( $product_holidays );
                    $return_value           =   check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays );
                    
                     if ( in_array( true, $return_value, true ) ){
                    
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                 $new_arr_product_id[] = $product_id_value->id;
                            }
                        }
                    }
    
                     /*****
                     * Check for global holidays
                     */
                      if( !empty( $book_global_holidays_arr ) ){
                        $return_value   =   array();
                        $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                        $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                        
                        $return_value   =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
                        
                        if ( in_array( true, $return_value, true ) ){
                            
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                $new_arr_product_id[] = $product_id_value->id;
                            }
                        }
                    }
    
                }else{
                    // specific dates only
                    if ( isset( $specific_dates_check ) && $specific_dates_check == 'on' ){
        
                            if( isset( $booking_settings[ 'booking_specific_date' ] ) && !empty( $booking_settings[ 'booking_specific_date' ] ) ){
                                
                                $selected_specific_dates            =   $booking_settings[ 'booking_specific_date' ];
                                
                                $specific_advanced_booking_period   =   $booking_settings[ 'booking_minimum_number_days' ];
                                $check_advanced_booking_period      =   array();
                                $min_date                           =   '';
                                                                
                                if ( isset( $specific_advanced_booking_period ) && $specific_advanced_booking_period > 0) {
                                    $current_time       =   current_time( 'timestamp' );
                                    // Convert the advance period to seconds and add it to the current time
                                    $advance_seconds    =   $booking_settings['booking_minimum_number_days'] *60 *60;
                                    $cut_off_timestamp  =   $current_time + $advance_seconds;
                                    $cut_off_date       =   date( "d-m-Y", $cut_off_timestamp );
                                    $min_date           =   date( "j-n-Y", strtotime( $cut_off_date ) );
                                    
                                    if ( isset($booking_settings['booking_maximum_number_days'])) {
                                        $days = $booking_settings['booking_maximum_number_days'];
                                    }
                                    
                                    // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
                                    if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
                                        $current_date   =   date( 'j-n-Y', $current_time );
                                        $last_slot_hrs  =   $current_slot_hrs = $last_slot_min = 0;
                                        
                                        if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
                                            
                                            foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
                                                $current_slot_hrs = $value['from_slot_hrs'];

                                                if ( $current_slot_hrs > $last_slot_hrs ) {
                                                    $last_slot_hrs  =   $current_slot_hrs;
                                                    $last_slot_min  =   $value['to_slot_min'];
                                                }
                                            }
                                        }else {
                                        // Get the weekday as it might be a recurring day setup
                                        $weekday            =   date( 'w', strtotime( $min_date ) );
                                        $booking_weekday    =   'booking_weekday_' . $weekday;
                                        
                                        if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
                                            
                                            foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
                                                $current_slot_hrs = $value['from_slot_hrs'];

                                                if ( $current_slot_hrs > $last_slot_hrs ) {
                                                    $last_slot_hrs  =   $current_slot_hrs;
                                                    $last_slot_min  =   $value['to_slot_min'];
                                                }
                                            }
                                        }
                                    }
                                    $last_slot              =   $last_slot_hrs . ':' . $last_slot_min;
                                    $advance_booking_hrs    =   0;
                                    
                                    if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
                                        $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
                                    }
                                    
                                    $booking_date2  =   $min_date . $last_slot;
                                    $booking_date2  =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
                                    $date2          =   new DateTime( $booking_date2 );
                                    $booking_date1  =   date( 'Y-m-d G:i', $current_time );
                                    $date1          =   new DateTime( $booking_date1 );
                                    
                                    if ( version_compare( phpversion(), '5.3', '>' ) ) {
                                        $difference     =   $date2->diff( $date1 );
                                    }else{
                                        $difference     =   bkap_common::dateTimeDiff( $date2, $date1 );
                                    }
                                    
                                    if ( $difference->days > 0 ) {
                                        $difference->h += $difference->days * 24;
                                    }
                                    
                                    if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
                                        $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
                                    }
                                }
                
                                $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                                $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                                
                                $check_advanced_booking_period  = check_in_range_abp ( $new_start_date, $new_end_date, $min_date );
                                
                                if ( !in_array( true, $check_advanced_booking_period, true ) ){

                                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                        $new_arr_product_id[] = $product_id_value->id;
                                    }
                                }
                            }
                
                            $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                            $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                            
                            foreach ( $selected_specific_dates as $date_key => $date_value ){
                                $return_value [] = check_in_range( $new_start_date, $new_end_date, $date_value );
                            }
                            
                            if ( !in_array( true, $return_value, true ) ){
                                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                    $new_arr_product_id[] = $product_id_value->id;
                                }
                                
                            }
                        }
    
                         /****
                         * This check if Specific date's product have any holidays:
                         */   
    
                        $product_holidays = $booking_settings[ 'booking_product_holiday' ];
    
                        if( isset( $product_holidays ) && count( $product_holidays ) > 0 ){
                            $return_value           =   array();
                            
                            $new_end_date           =   date( "j-n-Y", strtotime( $end_date ) );
                            $new_start_date         =   date( "j-n-Y", strtotime( $start_date ) );
                            
                            if( !empty( $product_holidays ) ){
                                $product_holidays   =   array_flip( $product_holidays );
                                $return_value       =   check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays_arr );
                            }
                            
                            if ( !in_array( false, $return_value, true ) ){
                                
                                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                    $new_arr_product_id[] = $product_id_value->id;
                                }
                            }
                        }
    
                        /****
                        * Check for global holidays
                        */
                        if( !empty ( $book_global_holidays_arr ) ){
                            $return_value   =   array();
                            $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                            $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                            
                            $return_value   =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
                            
                            if ( !in_array( false, $return_value, true ) ){
                                
                                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                    $new_arr_product_id[] = $product_id_value->id;
                                }
                            }
                        }                                   
                    }else {
                        // recurring days only
                        $recurring_check                    =   $booking_settings[ 'booking_recurring_booking' ];
                        $recurring_advanced_booking_period  =   $booking_settings[ 'booking_minimum_number_days' ];
                        $check_advanced_booking_period      =   array();
                        $min_date                           =   $recurring_selected_weekdays = '';
            
                        if( $recurring_check == 'on' ){
                
                            if ( isset( $recurring_advanced_booking_period ) && $recurring_advanced_booking_period > 0) {
                                
                                $current_time       =   current_time( 'timestamp' );
                                // Convert the advance period to seconds and add it to the current time
                                $advance_seconds    =   $booking_settings['booking_minimum_number_days'] *60 *60;
                                $cut_off_timestamp  =   $current_time + $advance_seconds;
                                $cut_off_date       =    date( "d-m-Y", $cut_off_timestamp );
                                $min_date           =   date( "j-n-Y", strtotime( $cut_off_date ) );
                                
                                if ( isset($booking_settings['booking_maximum_number_days'])) {
                                    $days = $booking_settings['booking_maximum_number_days'];
                                }
                                
                                // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
                                if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
                                    $current_date   =   date( 'j-n-Y', $current_time );
                                    $last_slot_hrs  =   $current_slot_hrs = $last_slot_min = 0;
                                    
                                    if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
                                        
                                        foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
                                            $current_slot_hrs   =   $value['from_slot_hrs'];
                                            
                                            if ( $current_slot_hrs > $last_slot_hrs ) {
                                                $last_slot_hrs  =   $current_slot_hrs;
                                                $last_slot_min  =   $value['to_slot_min'];
                                            }
                                        }
                                    }
                                else {
                                // Get the weekday as it might be a recurring day setup
                                $weekday            =   date( 'w', strtotime( $min_date ) );
                                $booking_weekday    =   'booking_weekday_' . $weekday;
                                
                                if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {

                                    foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
                                        $current_slot_hrs   =   $value['from_slot_hrs'];

                                        if ( $current_slot_hrs > $last_slot_hrs ) {
                                            $last_slot_hrs  =   $current_slot_hrs;
                                            $last_slot_min  =   $value['to_slot_min'];
                                        }
                                    }
                                }
                            }
                            $last_slot              =   $last_slot_hrs . ':' . $last_slot_min;
                            $advance_booking_hrs    =   0;
                            
                            if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
                                $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
                            }
                            
                            $booking_date2  =   $min_date . $last_slot;
                            $booking_date2  =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
                            $date2          =   new DateTime( $booking_date2 );
                            $booking_date1  =   date( 'Y-m-d G:i', $current_time );
                            $date1          =   new DateTime( $booking_date1 );
                            
                            if ( version_compare( phpversion(), '5.3', '>' ) ) {
                                $difference     =   $date2->diff( $date1 );
                            }else{
                                $difference     =   bkap_common::dateTimeDiff( $date2, $date1 );
                            }
                            
                            if ( $difference->days > 0 ) {
                                $difference->h += $difference->days * 24;
                            }
                		                                 
                            if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
                                $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
                            }
                        }
                
                        $new_end_date   =   date( "j-n-Y", strtotime( $end_date ) );
                        $new_start_date =   date( "j-n-Y", strtotime( $start_date ) );
                
                        $check_advanced_booking_period = check_in_range_abp ( $new_start_date, $new_end_date, $min_date );
                
                        if ( !in_array( true, $check_advanced_booking_period, true ) ){
                            
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {                                
                                $new_arr_product_id [] = $product_id_value->id;
                            }
                        }
                    }
    
                    $recurring_selected_weekdays    =   $booking_settings[ 'booking_recurring' ];
                    $return_value                   =   check_in_range_weekdays ( $start_date, $end_date, $recurring_selected_weekdays );
    
    
                    if ( !in_array( true, $return_value, true ) ){
                        
                        if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                            $new_arr_product_id[] = $product_id_value->id;
                        }
                
                    }
                    
                    /****
                    * This check if recurring week days product have any holidays:
                    */
                    $product_holidays = $booking_settings[ 'booking_product_holiday' ];
    
                    if( isset( $product_holidays ) && count( $product_holidays ) > 0 ){
                        $return_value           =   array();
    
                        $new_end_date           =   date( "j-n-Y", strtotime( $end_date ) );
                        $new_start_date         =   date( "j-n-Y", strtotime( $start_date ) );
                        
                        if( !empty( $product_holidays ) ){
                            $product_holidays   = array_flip( $product_holidays );
                            $return_value = check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays );
                        }
    
                        if ( !in_array( false, $return_value, true ) ){
    
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                $new_arr_product_id[] = $product_id_value->id;
                        }
                    }
                 }
    
                  /****
                  * Check for global holidays
                  */
                  if( !empty ( $book_global_holidays_arr ) ){
                      $return_value     =    array();
                      $new_end_date     =    date( "j-n-Y", strtotime( $end_date ) );
                      $new_start_date   =    date( "j-n-Y", strtotime( $start_date ) );
            
                       $return_value    =    check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
            
                        if ( !in_array( false, $return_value, true ) ){
            
                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                                $new_arr_product_id[] = $product_id_value->id;
                            }
                        }
                  }
               }
             }
           }
        }

            $val    =   "'";
            $val    .=  implode( "','", $new_arr_product_id );
            $val    .=  "'";
         
            $where  =   " AND($wpdb->posts.post_type = 'product'and $wpdb->posts.post_status = 'publish') AND $wpdb->posts.ID NOT IN
            			( $val ) AND $wpdb->posts.ID NOT IN( SELECT post_id from $meta_table
            			where meta_key = 'woocommerce_booking_settings' and meta_value LIKE '%booking_enable_date\";s:0%') AND $wpdb->posts.ID NOT IN(SELECT a.id
            			FROM $post_table AS a
            			LEFT JOIN $meta_table AS b ON a.id = b.post_id
            			AND (
            			b.meta_key =  'woocommerce_booking_settings'
            			)
            			WHERE b.post_id IS NULL ) AND $wpdb->posts.ID IN
            	       (SELECT element_id FROM $icl_table
            	       WHERE language_code = '".ICL_LANGUAGE_CODE."') ";
		
		
		}
        /**
         * Else non-wpml part
         */
		else {

            /*$bookable_products = bkap_common::get_woocommerce_product_list();
            echo "<pre>";print_r($bookable_products);echo "</pre>";*/
		        $get_product_ids  =   "Select id From $post_table WHERE post_type = 'product' AND post_status = 'publish' AND $wpdb->posts.ID IN 
                    		          (SELECT b.post_id FROM $booking_table AS b WHERE ('$start_date' between b.start_date and date_sub(b.end_date,INTERVAL 1 DAY)) 
                    		          or 
                    		          ('$end_date' between b.start_date and date_sub(b.end_date,INTERVAL 1 DAY)) 
                    		          or 
                    		          (b.start_date between '$start_date' and '$end_date') 
                    		          or 
                    		          b.start_date = '$start_date' 
                    		          ) and $wpdb->posts.ID NOT IN(SELECT post_id from $meta_table 
                    		          where meta_key = 'woocommerce_booking_settings' and meta_value LIKE '%booking_enable_date\";s:0%') and $wpdb->posts.ID NOT IN
                    	              (SELECT a.id FROM $post_table AS a LEFT JOIN $meta_table AS b 
                    	              ON 
                    	              a.id = b.post_id 
                    	              AND 
                    	              ( b.meta_key = 'woocommerce_booking_settings' ) 
                    	              WHERE 
                    	              b.post_id IS NULL)";
        	    $results_date     =   $wpdb->get_results( $get_product_ids );
                $new_arr_product_id = array();
                
                foreach( $results_date as $product_id_key => $product_id_value ){
//echo "<pre>";print_r($results_date);echo "</pre>";
		        
            	    $booking_settings = get_post_meta($product_id_value->id, 'woocommerce_booking_settings', true);
            	    
            	    if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
            	    
            	        $query_date    =   "SELECT DATE_FORMAT(start_date,'%d-%c-%Y') as start_date,DATE_FORMAT(end_date,'%d-%c-%Y') as end_date FROM ".$wpdb->prefix."booking_history
        						           WHERE (start_date >='".$chkin."' OR end_date <='".$chkout."') AND post_id = '".$product_id_value->id."'";
            	       
            	        $results_date  =   $wpdb->get_results($query_date);
            	       
            	        
            	        $dates_new     =   array();
            	        $booked_dates  =   array();
            	        
            	        if ( isset( $results_date ) && count( $results_date ) > 0 && $results_date != false ) {
            	            
            	            foreach( $results_date as $k => $v ) {
            	                $start_date_lockout    =   $v->start_date;
            	                $end_date_lockout      =   $v->end_date;
            	                $dates                 =   bkap_common::bkap_get_betweendays( $start_date_lockout, $end_date_lockout );
            	                $dates_new             =   array_merge( $dates, $dates_new );
            	                
            	            }
            	        }
            	         
             	        $dates_new_arr     =   array_count_values( $dates_new );
             	        
            	        $lockout           =   0;
             	        
        		        if ( isset( $booking_settings['booking_date_lockout'] ) ) {
         		            $lockout = $booking_settings[ 'booking_date_lockout' ]; 		           
         		        }
        		        
         		        foreach( $dates_new_arr as $k => $v ) {
         		            if( $v >= $lockout && $lockout != 0 ) {
         		                
         		                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
         		                    $new_arr_product_id[] = $product_id_value->id;
         		                }
        	                
         		            }
         		         }
            	    }else {
            	        $lockout_query     =   "SELECT DISTINCT start_date FROM `".$wpdb->prefix."booking_history`
    									       WHERE post_id= %d
    									       AND total_booking > 0
    									       AND available_booking <= 0";
            	        $results_lockout   =   $wpdb->get_results ( $wpdb->prepare($lockout_query,$product_id_value->id) );
            	         
            	        $lockout_query     =   "SELECT DISTINCT start_date FROM `".$wpdb->prefix."booking_history`
    					                       WHERE post_id= %d
    					                       AND available_booking > 0";
            	        $results_lock      =   $wpdb->get_results ( $wpdb->prepare( $lockout_query,$product_id_value->id ) );
            	        
            	        $lockout_date      =   '';
            	        
            	        foreach ( $results_lockout as $k => $v ) {
            	            
            	            foreach( $results_lock as $key => $value ) {

            	                if ( $v->start_date == $value->start_date ) {
            	                       $date_lockout       =   "SELECT COUNT(start_date) FROM `".$wpdb->prefix."booking_history`
    													       WHERE post_id= %d
    													       AND start_date= %s
    													       AND available_booking = 0";
            	                       $results_date_lock  =   $wpdb->get_results($wpdb->prepare($date_lockout,$product_id_value->id,$v->start_date));
            	                       
            	                       if ( $booking_settings['booking_date_lockout'] > $results_date_lock[0]->{'COUNT(start_date)'} ) {
            	                           unset( $results_lockout[ $k ] );
            	                       }
            	                }
            	            }
            	        }
            	        
            	        foreach ( $results_lockout as $k => $v ) {
            	            
            	            if ( $v->start_date == $start_date ){ 
            	                if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
                	                $new_arr_product_id[] = $product_id_value->id;
                	            }
            	            }
            	        }
            	    }
    		    }
		    
		    // this query is for spefic dates product when searched out of the specified date range
		    $get_all_product_ids      =     "Select id From $post_table WHERE post_type = 'product' AND post_status = 'publish' AND $wpdb->posts.ID NOT IN(SELECT post_id from $meta_table
                                		    where meta_key = 'woocommerce_booking_settings' and meta_value LIKE '%booking_enable_date\";s:0%') and $wpdb->posts.ID NOT IN
                                		    (SELECT a.id FROM $post_table AS a LEFT JOIN $meta_table AS b
                                		    ON
                                		    a.id = b.post_id
                                		    AND
                                		    ( b.meta_key = 'woocommerce_booking_settings' )
                                		    WHERE
                                		    b.post_id IS NULL )";
		    $results_date             =     $wpdb->get_results( $get_all_product_ids );
		    
		    $global_settings          =     json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		    $book_global_holidays_arr =     array();
		    
		    if (isset($global_settings->booking_global_holidays)) {
		        $book_global_holidays     =   $global_settings->booking_global_holidays;
		        $book_global_holidays_arr =   explode( ",", $book_global_holidays );
		    }
		     
		    foreach( $results_date as $product_id_key => $product_id_value ){
		         /****
		         * This is for the product which have recurring and specififc both enabled.
		         */
		        $booking_settings     =   get_post_meta( $product_id_value->id, 'woocommerce_booking_settings', true );
		        
		        if ( isset( $booking_settings['booking_date_range_type'] ) && $booking_settings['booking_date_range_type'] == 'fixed_range' ) {
		            $return_value_start = $return_value_end = array();
		        
		            $range_start_date  =  date( "j-n-Y", strtotime( $booking_settings['booking_start_date_range'] ) );
		            $range_end_date    =  date( "j-n-Y", strtotime( $booking_settings[ 'booking_end_date_range' ] ) ) ;

		            if ( isset( $booking_settings[ 'recurring_booking_range' ] ) && 'on' == $booking_settings[ 'recurring_booking_range' ] ) {
		                if ( isset( $booking_settings[ 'booking_range_recurring_years' ] ) && is_numeric( $booking_settings[ 'booking_range_recurring_years' ] ) && $booking_settings[ 'booking_range_recurring_years' ] > 0 ) {
		                    $range_end_date = date( 'j-n-Y', strtotime( '+' . $booking_settings[ 'booking_range_recurring_years' ] . 'years', strtotime( $booking_settings[ 'booking_end_date_range' ] ) ) );
		                }
		            }
		            
		            $search_end_date   =  date( "j-n-Y", strtotime( $end_date ) ); //date( "j-n-Y", strtotime( $end_date ) );
		            $search_start_date =  date( "j-n-Y", strtotime( $start_date ) );
		        
		            $return_value_start [] = check_in_range( $range_start_date, $range_end_date, $search_start_date );
		            $return_value_end   [] = check_in_range( $range_start_date, $range_end_date, $search_end_date );
		        
		            $specific_dates_check =   $booking_settings[ 'booking_specific_booking' ];
		            $recurring_check      =   $booking_settings[ 'booking_recurring_booking' ];
		        
		        
		            if ( ( isset( $specific_dates_check ) && $specific_dates_check == 'on' ) || ( isset( $recurring_check ) && $recurring_check == 'on' ) ){
		        
		                 
		                if ( !in_array( true, $return_value_start, true ) && !in_array( true, $return_value_end, true ) ){
		        
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }else{
		        
		                if ( !in_array( true, $return_value_start, true ) || !in_array( true, $return_value_end, true ) ){
		        
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }
		        }
		        
		        /****
		         * If searched date is greater than the maximum date based on "Number of Dates to choose" option then do not show product in search result.
		         */
		        $numbers_of_days_to_choose = isset( $booking_settings['booking_maximum_number_days'] ) ? $booking_settings['booking_maximum_number_days'] - 1 : "";
		        $custom_ranges = isset( $booking_settings[ 'booking_date_range' ] ) ? $booking_settings[ 'booking_date_range' ] : array();
		        // Wordpress Time
		        $current_time         =   current_time( 'timestamp' );
		       
		        if ( isset( $numbers_of_days_to_choose ) && "" != $numbers_of_days_to_choose && empty( $custom_ranges )  ){
		        
		        if( isset( $booking_settings[ 'booking_recurring_booking' ] ) && $booking_settings[ 'booking_recurring_booking' ] == "on" ){
		                    $new_start_date       =   date( "j-n-Y", strtotime( $start_date ) );
		                    $min_date = bkap_common::bkap_min_date_based_on_AdvanceBookingPeriod( $product_id_value->id, $current_time );  // so min date is today
		                   
		                    if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
		                        
		                        $time_slot_present = bkap_common::bkap_check_timeslot_for_weekday( $product_id_value->id, $start_date );
		                        
		                        if ( ! $time_slot_present ) {
		                            $new_arr_product_id[] = $product_id_value->id;
		                            continue;
		                        }
		                        
		                        $current_date  =   date( 'j-n-Y', $current_time );
		                        $last_slot_hrs =   $current_slot_hrs = $last_slot_min = 0;
		                        	
		                        if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
		                    
		                            foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
		                                $current_slot_hrs = $value['from_slot_hrs'];
		                                	
		                                if ( $current_slot_hrs > $last_slot_hrs ) {
		                                    $last_slot_hrs = $current_slot_hrs;
		                                    $last_slot_min = $value['to_slot_min'];
		                                }
		                            }
		                        }
		                        else {
		                            // Get the weekday as it might be a recurring day setup
		                            $weekday 		= 	date( 'w', strtotime( $min_date ) );
		                            $booking_weekday 	= 	'booking_weekday_' . $weekday;
		                    
		                            if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
		                                	
		                                foreach ( $booking_settings['booking_time_settings'][ $booking_weekday ] as $key => $value ) {
		                                    $current_slot_hrs = $value['from_slot_hrs'];
		                    
		                                    if ( $current_slot_hrs > $last_slot_hrs ) {
		                                        $last_slot_hrs = $current_slot_hrs;
		                                        $last_slot_min = $value['to_slot_min'];
		                                    }
		                                }
		                            }
		                        }
		                        
		                        if($last_slot_hrs == 0 && $last_slot_min == 0 ){
		                        }else{
		                            $last_slot             =   $last_slot_hrs . ':' . $last_slot_min;
		                            $advance_booking_hrs   =   0;
		                             
		                            if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
		                                $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
		                            }
		                             
		                            $booking_date2     =   $min_date . $last_slot;
		                            $booking_date2     =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
		                            $date2             =   new DateTime( $booking_date2 );
		                            $booking_date1     =   date( 'Y-m-d G:i', $current_time );
		                            $date1             =   new DateTime( $booking_date1 );
		                             
		                            if ( version_compare( phpversion(), '5.3', '>' ) ) {
		                                $difference        =   $date2->diff( $date1 );
		                            }else{
		                                $difference        =   bkap_common::dateTimeDiff( $date2, $date1 );
		                            }
		                             
		                            if ( $difference->days > 0 ) {
		                                $days_in_hour = $difference->h + ( $difference->days * 24 ) ;
		                                $difference->h = $days_in_hour;
		                            }
		                            
		                            if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
		                                $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
		                                
		                                $time_slot_present = bkap_common::bkap_check_timeslot_for_weekday( $product_id_value->id, $min_date );
		                                
		                                if ( ! $time_slot_present ) {
		                                    $new_arr_product_id[] = $product_id_value->id;
		                                    continue;
		                                }
		                            }
		                        }	
		                        
		                    }
		                    
		                    $cur_date =   apply_filters( 'bkap_max_date' , $min_date, $numbers_of_days_to_choose, $booking_settings );
		                  
		                if ( strtotime( $new_start_date ) <= strtotime( $cur_date ) ){
		                    
		                }else{
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }else{
		                
		                $new_start_date       =   date( "j-n-Y", strtotime( $start_date ) );
		                $cur_date             =   date( "j-n-Y", strtotime(' + '.$numbers_of_days_to_choose.' days'));
		                
		                if ( strtotime( $new_start_date ) <= strtotime( $cur_date ) ){
		                
		                }else{
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }
		        }
		        
		        $specific_dates_check =   isset( $booking_settings[ 'booking_specific_booking' ] ) ? $booking_settings[ 'booking_specific_booking' ] : "";
		        $recurring_check      =   isset( $booking_settings[ 'booking_recurring_booking' ] ) ? $booking_settings[ 'booking_recurring_booking' ] : "";
		        $return_value         =   array();
		        
		        if ( isset( $specific_dates_check ) && $specific_dates_check == 'on' && isset( $recurring_check ) && $recurring_check == 'on' ){
		            $selected_specific_dates          =   $booking_settings[ 'booking_specific_date' ];
		            $recurring_selected_weekdays      =   $booking_settings[ 'booking_recurring' ];
		            
		            $specific_advanced_booking_period =   $booking_settings[ 'booking_minimum_number_days' ];
		            $check_advanced_booking_period    =   array();
		            $min_date                         =   '';
		            
		            if ( isset( $specific_advanced_booking_period ) && $specific_advanced_booking_period > 0) {
		                $current_time         =   current_time( 'timestamp' );
		                // Convert the advance period to seconds and add it to the current time
		                $advance_seconds      =   $booking_settings['booking_minimum_number_days'] *60 *60;
		                $cut_off_timestamp    =   $current_time + $advance_seconds;
		                $cut_off_date         =   date( "d-m-Y", $cut_off_timestamp );
		                $min_date             =   date( "j-n-Y", strtotime( $cut_off_date ) );
		            
		                if ( isset($booking_settings['booking_maximum_number_days'])) {
		                    $days = $booking_settings['booking_maximum_number_days'];
		                }
		                 
		                // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
		                if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
		                    $current_date     =   date( 'j-n-Y', $current_time );
		                    $last_slot_hrs    =   $current_slot_hrs = $last_slot_min = 0;
		                    
		                    if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
		                        
		                        foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
		                            $current_slot_hrs     =   $value['from_slot_hrs'];
		                            
		                            if ( $current_slot_hrs > $last_slot_hrs ) {
		                                $last_slot_hrs    =   $current_slot_hrs;
		                                $last_slot_min    =   $value['to_slot_min'];
		                            }
		                        }
		                    }
		                    else {
		                        // Get the weekday as it might be a recurring day setup
		                        $weekday          =   date( 'w', strtotime( $min_date ) );
		                        $booking_weekday  =   'booking_weekday_' . $weekday;
		                        
		                        if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
		                            
		                            foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
		                                $current_slot_hrs     =   $value['from_slot_hrs'];
		                                
		                                if ( $current_slot_hrs > $last_slot_hrs ) {
		                                    $last_slot_hrs    =   $current_slot_hrs;
		                                    $last_slot_min    =   $value['to_slot_min'];
		                                }
		                            }
		                        }
		                    }
		                    
		                    $last_slot            =   $last_slot_hrs . ':' . $last_slot_min;
		                    $advance_booking_hrs  =   0;
		                    
		                    if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
		                        $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
		                    }
		                    
		                    $booking_date2    =   $min_date . $last_slot;
		                    $booking_date2    =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
		                    $date2            =   new DateTime( $booking_date2 );
		                    $booking_date1    =   date( 'Y-m-d G:i', $current_time );
		                    $date1            =   new DateTime( $booking_date1 );
		                    
		                    if ( version_compare( phpversion(), '5.3', '>' ) ) {
		                        $difference     =   $date2->diff( $date1 );
		                    }else{
		                        $difference     =   bkap_common::dateTimeDiff( $date2, $date1 );
		                    }
		            
		                    if ( $difference->days > 0 ) {
		                        $difference->h += $difference->days * 24;
		                    }
		                     
		                    if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
		                        $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
		                    }
		                }
		            
		                $check_advanced_booking_period  = check_in_range_abp ( $start_date, $end_date, $min_date );
		                
		                if ( !in_array( true, $check_advanced_booking_period, true ) ){
		                    
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }
		            
		            
		            $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		            $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		            
		            foreach ( $selected_specific_dates as $date_key => $date_value ){
		                $return_value [] = check_in_range( $new_start_date, $new_end_date, $date_value );
		            }
		            
		            $return_value_recurring = check_in_range_weekdays ( $start_date, $end_date, $recurring_selected_weekdays );
		            
		            
                    if ( !in_array( true, $return_value, true ) && !in_array( true, $return_value_recurring, true )){ 
		                
                        if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                    $new_arr_product_id[] = $product_id_value->id;
		                }
		                
		            }
		            
		            /****
		             * This check if product have any holidays:
		             */
		            
		            $product_holidays_arr = $booking_settings[ 'booking_product_holiday' ];
		            
		            if( is_array( $product_holidays_arr ) && count( $product_holidays_arr ) > 0 ){
		                $return_value         =   array();
		            
		                $new_end_date         =   date( "j-n-Y", strtotime( $end_date ) );
		                $new_start_date       =   date( "j-n-Y", strtotime( $start_date ) );
		                
		                if( !empty( $product_holidays_arr ) ){
                            
                            $product_holidays_arr_new   = array();

                            foreach( $product_holidays_arr as $key => $value ){
                                $product_holidays_arr_new[] = $key;
                            }

                            $return_value = check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays_arr_new );                   
                            
                        }

                        
                        if ( in_array( true , $return_value, true ) ){ 
                               
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }


                    /*****
                    * This will check if the product have any holidays set through the custom range
                    */


                    $holiday_ranges = get_post_meta( $product_id_value->id, '_bkap_holiday_ranges', true );
                    
                    if( is_array( $holiday_ranges ) && count( $holiday_ranges ) > 0 ){
                        
                        $custom_return_value  =   array();

                        $new_end_date         =   date( "j-n-Y", strtotime( $end_date ) );
                                                                        
                        $new_start_date       =   date( "j-n-Y", strtotime( $start_date ) );

                      
                        if( !empty( $holiday_ranges ) ){
                            
                            $holiday_ranges_arr_new   = array();
                           
                            foreach( $holiday_ranges as $key => $value ){

                                $custom_return_value        = array();
                                $custom_range_start_date    = $value ['start'];
                                $custom_range_end_date      = $value ['end'];

                                 $custom_return_value = bkap_check_in_custom_holiday_range( $new_start_date, $new_end_date, $custom_range_start_date, $custom_range_end_date );                   
                                
                                if ( in_array( true , $custom_return_value, true ) ){ 
                                                                   
                                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
                                        $new_arr_product_id[] = $product_id_value->id;
                                        continue;
                                    }
                                    
                                }
                            }              
                            
                        }

                        
                    }
		            
		            /****
		             * Check for global holidays
		             */
		            if( !empty ( $book_global_holidays_arr ) ){
		                $return_value     =   array();
		                $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                
		                $return_value     =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
		                
		                if ( !in_array( false, $return_value, true ) ){ 
		            
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }
		            
		        } else if( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == "on" ) {
		            
		            $multiple_advanced_booking_period = ( isset( $booking_settings[ 'booking_minimum_number_days' ] ) ) ? $booking_settings[ 'booking_minimum_number_days' ] : 0;
		            $check_advanced_booking_period    =   array();
		            $min_date                         =   '';
		            
		            if ( isset( $multiple_advanced_booking_period ) && $multiple_advanced_booking_period > 0) {
		                $current_time         =   current_time( 'timestamp' );
		                // Convert the advance period to seconds and add it to the current time
		                $advance_seconds      =   $booking_settings[ 'booking_minimum_number_days' ] *60 *60;
		                $cut_off_timestamp    =   $current_time + $advance_seconds;
		                $cut_off_date         =   date( "d-m-Y", $cut_off_timestamp );
		                $min_date             =   date( "j-n-Y", strtotime( $cut_off_date ) );
		            
		                if ( isset( $booking_settings['booking_maximum_number_days'] ) ) {
		                    $days = $booking_settings['booking_maximum_number_days'];
		                }
		                 
		                // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
		                if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
		                    $current_date     =   date( 'j-n-Y', $current_time );
		                    $last_slot_hrs    =   $current_slot_hrs = $last_slot_min = 0;
		                    
		                    if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
		                        
		                        foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
		                            $current_slot_hrs     =   $value['from_slot_hrs'];
		                            
		                            if ( $current_slot_hrs > $last_slot_hrs ) {
		                                $last_slot_hrs    =   $current_slot_hrs;
		                                $last_slot_min    =   $value['to_slot_min'];
		                            }
		                        }
		                    }
		                    else {
		                        // Get the weekday as it might be a recurring day setup
		                        $weekday          =   date( 'w', strtotime( $min_date ) );
		                        $booking_weekday  =   'booking_weekday_' . $weekday;
		                        
		                        if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
		                            
		                            foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
		                                $current_slot_hrs     =   $value['from_slot_hrs'];
		                                
		                                if ( $current_slot_hrs > $last_slot_hrs ) {
		                                    $last_slot_hrs    =   $current_slot_hrs;
		                                    $last_slot_min    =   $value['to_slot_min'];
		                                }
		                            }
		                        }
		                    }
		                    $last_slot            =   $last_slot_hrs . ':' . $last_slot_min;
		                    $advance_booking_hrs  =   0;
		                    
		                    if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
		                        $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
		                    }
		                    $booking_date2 =  $min_date . $last_slot;
		                    $booking_date2 =  date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
		                    $date2         =  new DateTime( $booking_date2 );
		                    $booking_date1 =  date( 'Y-m-d G:i', $current_time );
		                    $date1         =  new DateTime( $booking_date1 );
		                    
		                    if ( version_compare( phpversion(), '5.3', '>' ) ) {
		                        $difference       =   $date2->diff( $date1 );
		                    }else{
		                        $difference    =  bkap_common::dateTimeDiff( $date2, $date1 );
		                    }
		            
		                    if ( $difference->days > 0 ) {
		                        $difference->h += $difference->days * 24;
		                    }
		                     
		                    if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
		                        $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
		                    }
		                }
		            
		                $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		            
		                $check_advanced_booking_period = check_in_range_abp ( $new_start_date, $new_end_date, $min_date );
		            
		                if ( in_array( false, $check_advanced_booking_period, true ) ){
		                    
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }
		            
		            $product_holidays_arr = isset( $booking_settings[ 'booking_product_holiday' ] ) ? $booking_settings[ 'booking_product_holiday' ] : array();
		            
		            if( is_array( $product_holidays_arr ) && count( $product_holidays_arr ) > 0 ){
		                $return_value         =   array();
		            
		                $new_end_date         =   date( "j-n-Y", strtotime( $end_date ) );
		                $new_start_date       =   date( "j-n-Y", strtotime( $start_date ) );
		                $product_holidays_arr =   array_flip( $product_holidays_arr );
		                $return_value         =   check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays_arr );
		            
		                if ( in_array( true, $return_value, true ) ){ 
		            
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		               }
		            }
		            
		            /****
		             * Check for global holidays
		             */
		            if( !empty ($book_global_holidays_arr) ){
		                $return_value     =   array();
		                $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                
		                $return_value     =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
		                
		                if ( in_array( true, $return_value, true ) ){
		            
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }
		                }
		            }
		            
		            if( isset($booking_settings[ 'booking_fixed_block_enable' ]) && $booking_settings[ 'booking_fixed_block_enable' ] == 'booking_fixed_block_enable' ){
		                
		                $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                $days             =   array();
		            
		                $fix_query      = "SELECT * FROM `".$wpdb->prefix."booking_fixed_blocks` WHERE post_id = %d";
		                $fix_results    = $wpdb->get_results( $wpdb->prepare( $fix_query, $product_id_value->id ) );
		            
		                foreach($fix_results as $fix_key => $fix_value){
		                    $days[] = $fix_value->start_day;
		                }
		                
		                $return_value     =   check_in_fixed_block_booking( $new_start_date, $new_end_date, $days );
		                
		                if ( in_array( true, $return_value, true ) ){
		                }else{
		                    if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                        $new_arr_product_id[] = $product_id_value->id;
		                    }  
		                }
		            }
		            
		        }else{
		            // specific dates only
		            if ( isset( $specific_dates_check ) && $specific_dates_check == 'on' ){
		            
		                if( isset( $booking_settings[ 'booking_specific_date' ] ) && !empty( $booking_settings[ 'booking_specific_date' ] ) ){
		            
		                    $selected_specific_dates          =   $booking_settings[ 'booking_specific_date' ];
		                    
		                    $specific_advanced_booking_period =   $booking_settings[ 'booking_minimum_number_days' ];
		                    $check_advanced_booking_period    =   array();
		                    $min_date                         =   '';		                    
		                    
		                    if ( isset( $specific_advanced_booking_period ) && $specific_advanced_booking_period > 0) {
		                        $current_time         =   current_time( 'timestamp' );
		                        // Convert the advance period to seconds and add it to the current time
		                        $advance_seconds      =   $booking_settings['booking_minimum_number_days'] *60 *60;
		                        $cut_off_timestamp    =   $current_time + $advance_seconds;
		                        $cut_off_date         =   date( "d-m-Y", $cut_off_timestamp );
		                        $min_date             =   date( "j-n-Y", strtotime( $cut_off_date ) );
		                    
		                        if ( isset($booking_settings['booking_maximum_number_days'])) {
		                            $days     =   $booking_settings['booking_maximum_number_days'];
		                        }
		                         
		                        // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
		                        if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
		                            $current_date     =   date( 'j-n-Y', $current_time );
		                            $last_slot_hrs    =   $current_slot_hrs = $last_slot_min = 0;
		                            
		                            if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
		                                
		                                foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
		                                    $current_slot_hrs     =   $value['from_slot_hrs'];
		                                    
		                                    if ( $current_slot_hrs > $last_slot_hrs ) {
		                                        $last_slot_hrs    =   $current_slot_hrs;
		                                        $last_slot_min    =   $value['to_slot_min'];
		                                    }
		                                }
		                            }
		                            else {
		                                // Get the weekday as it might be a recurring day setup
		                                $weekday          =   date( 'w', strtotime( $min_date ) );
		                                $booking_weekday  =   'booking_weekday_' . $weekday;
		                                
		                                if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
		                                    
		                                    foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
		                                        $current_slot_hrs     =   $value['from_slot_hrs'];
		                                        
		                                        if ( $current_slot_hrs > $last_slot_hrs ) {
		                                            $last_slot_hrs    =   $current_slot_hrs;
		                                            $last_slot_min    =   $value['to_slot_min'];
		                                        }
		                                    }
		                                }
		                            }
		                            $last_slot            =   $last_slot_hrs . ':' . $last_slot_min;
		                            $advance_booking_hrs  =   0;
		                            
		                            if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
		                                $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
		                            }
		                            
		                            $booking_date2    =   $min_date . $last_slot;
		                            $booking_date2    =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
		                            $date2            =   new DateTime( $booking_date2 );
		                            $booking_date1    =   date( 'Y-m-d G:i', $current_time );
		                            $date1            =   new DateTime( $booking_date1 );
		                            
		                            if ( version_compare( phpversion(), '5.3', '>' ) ) {
		                                $difference       =   $date2->diff( $date1 );
		                            }else{
		                                $difference    =  bkap_common::dateTimeDiff( $date2, $date1 );
		                            }
		                    
		                            if ( $difference->days > 0 ) {
		                                $difference->h += $difference->days * 24;
		                            }
		                             
		                            if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
		                                $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
		                            }
		                        }
		                    
		                        $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                        $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                    
		                        $check_advanced_booking_period  = check_in_range_abp ( $new_start_date, $new_end_date, $min_date );
		                        
		                        if ( !in_array( true, $check_advanced_booking_period, true ) ){
		                            
		                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                                $new_arr_product_id[] = $product_id_value->id;
		                            }
		                        }
		                    }
		            
		                    $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                    $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                    
		                    foreach ( $selected_specific_dates as $date_key => $date_value ){
		                        $return_value [] = check_in_range( $new_start_date, $new_end_date, $date_key );
		                    }
		                    
		                    if ( !in_array( true, $return_value, true ) ){ 
		                        
		                        if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                            $new_arr_product_id[] = $product_id_value->id;
		                        }
		                        
		                    }
		                }
		            
		                /****
		                * This check if Specific date's product have any holidays:
		                */
		            
		                $product_holidays = $booking_settings[ 'booking_product_holiday' ];
		            
		                if( isset( $product_holidays ) && count( $product_holidays ) > 0 ){
		                    $return_value         =   array();
		            
		                    $new_end_date         =   date( "j-n-Y", strtotime( $end_date ) );
		                    $new_start_date       =   date( "j-n-Y", strtotime( $start_date ) );
		                    
		                    if( !empty( $product_holidays ) ){
		                        $product_holidays   = array_flip( $product_holidays );
		                        $return_value     =   check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays );
		                    }
		            
		                    if ( !in_array( false, $return_value, true ) ){ 
		            
		                        if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) {
		                            $new_arr_product_id[] = $product_id_value->id;
		                        }
		                    }
		                }
		                
		                /****
		                * Check for global holidays
		                */
		                if( !empty ( $book_global_holidays_arr ) ){
		                    $return_value     =   array();
		                    $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                    $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                
		                    $return_value     =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
		                
		                    if ( !in_array( false, $return_value, true ) ){ 
		            
		                        if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                            $new_arr_product_id[] = $product_id_value->id;
		                        }
		                    }
		                }
		            }else {
		                // recurring days only
		                $recurring_check                      =   isset ( $booking_settings[ 'booking_recurring_booking' ] ) ? $booking_settings[ 'booking_recurring_booking' ] : "";
		                $recurring_advanced_booking_period    =   isset ( $booking_settings[ 'booking_minimum_number_days' ] ) ? $booking_settings[ 'booking_minimum_number_days' ] : "";
		                $check_advanced_booking_period        =   array();
		                $min_date                             =   $recurring_selected_weekdays = '';
		                
		                if( $recurring_check == 'on' ){
		                    
		                    if ( isset( $recurring_advanced_booking_period ) && $recurring_advanced_booking_period > 0) {
		                    
		                        $current_time         =   current_time( 'timestamp' );
		                        // Convert the advance period to seconds and add it to the current time
		                        $advance_seconds      =   $booking_settings['booking_minimum_number_days'] *60 *60;
		                        $cut_off_timestamp    =   $current_time + $advance_seconds;
		                        $cut_off_date         =   date( "d-m-Y", $cut_off_timestamp );
		                        $min_date             =   date( "j-n-Y", strtotime( $cut_off_date ) );
		                    
		                        if ( isset($booking_settings['booking_maximum_number_days'])) {
		                            $days = $booking_settings['booking_maximum_number_days'];
		                        }
		                         
		                        // check mindate is today.. if yes, then check if all time slots are past, if yes, then set mindate to tomorrow
		                        if ( isset ( $booking_settings['booking_enable_time'] ) && $booking_settings['booking_enable_time'] == 'on' ) {
		                            $current_date     =   date( 'j-n-Y', $current_time );
		                            $last_slot_hrs    =   $current_slot_hrs = $last_slot_min = 0;
		                            
		                            if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $min_date, $booking_settings['booking_time_settings'] ) ) {
		                                
		                                foreach ( $booking_settings['booking_time_settings'][$min_date] as $key => $value ) {
		                                    $current_slot_hrs     =   $value['from_slot_hrs'];
		                                    
		                                    if ( $current_slot_hrs > $last_slot_hrs ) {
		                                        $last_slot_hrs    =   $current_slot_hrs;
		                                        $last_slot_min    =   $value['to_slot_min'];
		                                    }
		                                }
		                            }
		                            else {
		                                // Get the weekday as it might be a recurring day setup
		                                $weekday          =   date( 'w', strtotime( $min_date ) );
		                                $booking_weekday  =   'booking_weekday_' . $weekday;
		                                
		                                if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $booking_weekday, $booking_settings['booking_time_settings'] ) ) {
		                                    
		                                    foreach ( $booking_settings['booking_time_settings'][$booking_weekday] as $key => $value ) {
		                                        $current_slot_hrs     =   $value['from_slot_hrs'];
		                                        
		                                        if ( $current_slot_hrs > $last_slot_hrs ) {
		                                            $last_slot_hrs    =   $current_slot_hrs;
		                                            $last_slot_min    =   $value['to_slot_min'];
		                                        }
		                                    }
		                                }
		                            }
		                            $last_slot            =   $last_slot_hrs . ':' . $last_slot_min;
		                            $advance_booking_hrs  =   0;
		                            
		                            if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != '' ) {
		                                $advance_booking_hrs = $booking_settings['booking_minimum_number_days'];
		                            }
		                            
		                            $booking_date2    =   $min_date . $last_slot;
		                            $booking_date2    =   date( 'Y-m-d G:i', strtotime( $booking_date2 ) );
		                            $date2            =   new DateTime( $booking_date2 );
		                            $booking_date1    =   date( 'Y-m-d G:i', $current_time );
		                            $date1            =   new DateTime( $booking_date1 );
		                            
		                            if ( version_compare( phpversion(), '5.3', '>' ) ) {
		                                $difference       =   $date2->diff( $date1 );
		                            }else{
		                                $difference    =  bkap_common::dateTimeDiff( $date2, $date1 );
		                            }
		                    
		                            if ( $difference->days > 0 ) {
		                                $difference->h += $difference->days * 24;
		                            }
		                             
		                            if ( $difference->invert == 0 || $difference->h < $advance_booking_hrs ) {
		                                $min_date = date( 'j-n-Y', strtotime( $min_date . '+1 day' ) );
		                            }
		                        }
		                    
		                        $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                        $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                    
		                        $check_advanced_booking_period = check_in_range_abp ( $new_start_date, $new_end_date, $min_date );
		                    
		                        if ( !in_array( true, $check_advanced_booking_period, true ) ){
		                    
		                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                    
		                                $new_arr_product_id [] = $product_id_value->id;
		                            }
		                        }
		                    }
		                    
		                    $recurring_selected_weekdays  =   $booking_settings[ 'booking_recurring' ];
		                    $return_value                 =   check_in_range_weekdays ( $start_date, $end_date, $recurring_selected_weekdays );
		                
		                
		                    if ( !in_array( true, $return_value, true ) ){ 
		                        
		                        if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                            $new_arr_product_id[] = $product_id_value->id;
		                        }
		                        
		                    }
		                     
		                     /****
		                     * This check if recurring week days product have any holidays:
		                     */
		                    $product_holidays = $booking_settings[ 'booking_product_holiday' ];
		                
		                    if( isset( $product_holidays ) && count( $product_holidays ) > 0 ){
		                        $return_value         =   array();
		                
		                        $new_end_date         =   date( "j-n-Y", strtotime( $end_date ) );
		                        $new_start_date       =   date( "j-n-Y", strtotime( $start_date ) );
		                        
		                        $product_holidays     =   array_flip($product_holidays);
		                        
		                        if( !empty($product_holidays)){
		                            $return_value     =   check_in_range_holidays( $new_start_date, $new_end_date, $product_holidays );
		                        }
		                
		                        if ( !in_array( false, $return_value, true ) ){ 
		                
		                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                                $new_arr_product_id[] = $product_id_value->id;
		                            }
		                        }
		                    }
		                    
		                     /****
		                     * Check for global holidays
		                     */
		                    if( !empty ( $book_global_holidays_arr ) ){
		                        $return_value     =   array();
		                        $new_end_date     =   date( "j-n-Y", strtotime( $end_date ) );
		                        $new_start_date   =   date( "j-n-Y", strtotime( $start_date ) );
		                    
		                        $return_value     =   check_in_range_holidays( $new_start_date, $new_end_date, $book_global_holidays_arr );
		                    
		                     if ( !in_array( false, $return_value, true ) ){ 
		                
		                            if ( !in_array( $product_id_value->id, $new_arr_product_id, true ) ) { 
		                                $new_arr_product_id[] = $product_id_value->id;
		                            }
		                        }
		                    }
		                }
		            }
		        }
		    }
		    
		    $val  =  "'";
 		    $val .=  implode( "','", $new_arr_product_id );
 		    $val .=  "'";
 		    
 		    $where   =   " AND($wpdb->posts.post_type = 'product'and $wpdb->posts.post_status = 'publish') AND $wpdb->posts.ID NOT IN
    					( $val ) AND $wpdb->posts.ID NOT IN( SELECT post_id from $meta_table
    					where meta_key = 'woocommerce_booking_settings' and meta_value LIKE '%booking_enable_date\";s:0%') AND $wpdb->posts.ID NOT IN(SELECT a.id
    					FROM $post_table AS a
    					LEFT JOIN $meta_table AS b ON a.id = b.post_id
    					AND (
    					b.meta_key =  'woocommerce_booking_settings'
    					)
    					WHERE b.post_id IS NULL ) ";
		
		}
		
	}
	return $where;

}
//add_filter( 'posts_where','bkap_get_custom_posts', 10, 2 );

add_action( 'pre_get_posts', 'bkap_generate_bookable_data', 20 );

function bkap_generate_bookable_data( $query ) {

    if ( !empty( $_GET['w_checkin'] ) ) {
        $_SESSION['start_date'] = $_GET['w_checkin'];
        $start_date = $_GET['w_checkin'];

        if ( !empty( $_GET['w_checkout'] ) ) {
            $_SESSION['end_date'] = $_GET['w_checkout'];
            $end_date = $_GET['w_checkout'];
        }else {
            $_SESSION['end_date'] = $_GET['w_checkin'];
            $end_date = $_GET['w_checkin'];
        }

        if ( !empty( $_GET['w_allow_category'] ) && $_GET['w_allow_category'] == 'on' ) {
            $_SESSION['selected_category'] = $_GET['w_category'];
        }else {
            $_SESSION['selected_category'] = 'disable';
        }
    }

    if( !empty( $start_date ) && 
        !empty( $end_date ) &&
        $query->is_main_query() ){

        $query->set( 'suppress_filters', false );
        
        $filtered_products = array();

        // If widget has only start date then filter out all the products if its an holiday
        if ( $start_date === $end_date ) {
            $is_global_holiday = bkap_check_holiday( $start_date, $end_date );

            if ( $is_global_holiday ) {
                $query->set( 'post__in', array('') );
                return $query;
            }
        }

        if ( !empty( $_GET['select_cat'] ) && $_GET['select_cat'] != 0 ) {

            $tax_query[] = array(
               'taxonomy' => 'product_cat',
               'field' => 'id',
               'terms' => array( $_GET['select_cat'] ),
               'operator' => 'IN'
            );

            $query->set( 'tax_query', $tax_query );
        }

        $bookable_products = bkap_common::get_woocommerce_product_list( false );

        foreach ( $bookable_products as $pro_key => $pro_value ) {

            $product_id = $pro_value['1'];

            $view_product = bkap_check_booking_available( $product_id, $start_date, $end_date );
            if ( $view_product ) {
                array_push( $filtered_products, $product_id );
            }
        }

        if ( count($filtered_products) === 0 ) {
            $filtered_products = array( '' );
        }

        $query->set( 'post__in', $filtered_products );
    }
    return $query;
}

/**
 * Check if Booking is not locked out for a particular date
 * 
 * @param string|int $product_id Product ID
 * @param string $start_date Start Date
 * @param string $end_date End Date
 * @return bool True for available else false
 * @since 4.3.0
 */
function bkap_check_booking_available( $product_id, $start_date, $end_date ) {

    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
    $booking_type = get_post_meta( $product_id, '_bkap_booking_type', true );

    // Wordpress Time
    $current_time = current_time( 'timestamp' );

    if ( $start_date === $end_date ) {
        $is_min_date_available = bkap_check_for_min_date( $product_id, $start_date, $current_time );

        if ( !$is_min_date_available ) {
            return false;
        }
    }

    $is_in_max_range = bkap_check_for_max_date( $product_id, $booking_settings, $start_date, $current_time );

    if ( !$is_in_max_range ) {
        return false;
    }

    switch ( $booking_type ) {
        case 'only_day':
            do {
                $availability_result = bkap_check_day_booking_available( $product_id, $start_date );
                $range_has_holiday = bkap_check_holiday( $start_date, $start_date );

                if ( $availability_result && !$range_has_holiday ) {
                    return true;
                }
                $start_date = date( 'Y-m-d', strtotime( $start_date . ' +1 day' ) );
            } while ( strtotime( $start_date ) <= strtotime( $end_date ) );
            return false;
            break;
        case 'multiple_days':
            $range_has_holiday = bkap_check_holiday( $start_date, $end_date );
            if ( $range_has_holiday ) {
                return false;
            }

            if ( isset( $booking_settings['booking_fixed_block_enable'] ) && $booking_settings['booking_fixed_block_enable'] === 'booking_fixed_block_enable' ) {
                
                $block_max_days = 0;
                if ( isset( $booking_settings['bkap_fixed_blocks_data'] ) ) {
                    foreach ( $booking_settings['bkap_fixed_blocks_data'] as $block_key => $block_value ) {
                        if ( isset( $block_value['number_of_days'] ) && $block_value['number_of_days'] > $block_max_days ) {
                            $block_max_days = $block_value['number_of_days'];
                        }
                    }
                }

                if ( $block_max_days > 0 ) {
                    $end_date = date( 'Y-m-d', strtotime( $end_date . " +$block_max_days day" ) );
                }
            }

            do {
                $availability_result = bkap_check_day_booking_available( $product_id, $start_date );
                if ( !$availability_result ) {
                    return false;
                }
                $start_date = date( 'Y-m-d', strtotime( $start_date . ' +1 day' ) );
            } while ( strtotime( $start_date ) < strtotime( $end_date ) );

            return true;
            break;
        case 'date_time':
            do {
                $availability_result = bkap_check_day_booking_available( $product_id, $start_date );
                $range_has_holiday = bkap_check_holiday( $start_date, $start_date );
                $day_has_timeslot = bkap_common::bkap_check_timeslot_for_weekday( $product_id, $start_date );

                if ( $availability_result && !$range_has_holiday ) {
                    $time_slots = explode( '|', bkap_booking_process::get_time_slot( $start_date, $product_id ) );

                    if ( sanitize_key( $time_slots[0] ) !== 'error' && 
                        ( sanitize_key( $time_slots[0] ) !== '' && $day_has_timeslot ) ) {

                        return true;
                    }
                }
                $start_date = date( 'Y-m-d', strtotime( $start_date . ' +1 day' ) );
            } while ( strtotime( $start_date ) <= strtotime( $end_date ) );
            return false;
            break;
        
        default:
            return false;
            break;
    }
}

/**
 * Check if min booking date is available for booking when compared to start date.
 * Return true if date available else return false
 * 
 * @param string|int $product_id Product ID
 * @param string $start_date Start Date
 * @param string $current_time Current Wordpress Time
 * @return bool True if date available else return false
 * @since 4.3.0
 */
function bkap_check_for_min_date( $product_id, $start_date, $current_time ) {

    $min_date = bkap_common::bkap_min_date_based_on_AdvanceBookingPeriod( $product_id, $current_time );
    if ( strtotime( $min_date ) > strtotime( $start_date ) ) {
        return false;
    }else {
        return true;
    }
}

/**
 * Check if start date is out of the max date range (i.e. maximum number of dates to choose).
 * Return true if in range else return false
 * 
 * @param string|int $product_id Product ID
 * @param array $booking_settings Booking Settings for the product to check
 * @param string $start_date Start Date
 * @return bool true if not in range else return false
 * @since 4.3.0
 */
function bkap_check_for_max_date ( $product_id, $booking_settings, $start_date, $current_time ) {

    $numbers_of_days_to_choose = isset( $booking_settings['booking_maximum_number_days'] ) ? $booking_settings['booking_maximum_number_days'] -1 : "";
    $custom_ranges = isset( $booking_settings[ 'booking_date_range' ] ) ? $booking_settings[ 'booking_date_range' ] : array();

    $min_date = bkap_common::bkap_min_date_based_on_AdvanceBookingPeriod( $product_id, $current_time );

    if ( ( isset( $numbers_of_days_to_choose ) && "" != $numbers_of_days_to_choose && empty( $custom_ranges ) ) ||
         ( isset( $numbers_of_days_to_choose ) && 0 === $numbers_of_days_to_choose ) ){

        if( isset( $booking_settings[ 'booking_recurring_booking' ] ) && $booking_settings[ 'booking_recurring_booking' ] == "on" ){

            $max_date = apply_filters( 'bkap_max_date' , $min_date, $numbers_of_days_to_choose, $booking_settings );

            if ( strtotime( $max_date ) < strtotime( $start_date ) ) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Check if bookings are available for that day for single day bookings
 * 
 * @param string|int $product_id Product ID
 * @param string $start_date Start Date
 * @return bool True if booking available else false
 * @since 4.3.0
 */
function bkap_check_day_booking_available( $product_id, $start_date ) {

    $result = get_bookings_for_date( $product_id, $start_date );
    $res = get_availability_for_date( $product_id, $start_date, $result );

    if ( count( $res ) > 0 && 
        ( $res['unlimited'] === 'YES' || ( $res['unlimited'] === 'NO' && $res['available'] > 0 ) ) ) {
        
        return true;
    }else {
        return false;
    }
}

/**
 * Check if the date passed is a part of global holidays
 * 
 * @param string $start_date Date (start date from widget)
 * @param string $end_date Date (end date from widget)
 * @return bool true if part of global holiday else false
 * @since 4.3.0
 */
function bkap_check_holiday( $start_date, $end_date ) {

    $global_settings      = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
    $global_holidays      = array();
    $formatted_start_date = date( 'j-n-Y', strtotime( $start_date ) );
    $formatted_end_date   = date( 'j-n-Y', strtotime( $end_date ) );

    if ( isset( $global_settings->booking_global_holidays ) ) {
        $global_holidays = explode( ',', $global_settings->booking_global_holidays );
    }

    if ( in_array( $formatted_start_date, $global_holidays ) ) {
        return true;
    }elseif ( $formatted_end_date !== $formatted_start_date ) {
        while ( strtotime( $formatted_start_date ) < strtotime( $formatted_end_date ) ) {
            if ( in_array( $formatted_start_date, $global_holidays ) ) {
                return true;
            }

            $formatted_start_date = date( 'j-n-Y', strtotime( $formatted_start_date . ' +1 day' ) );
        }
    }
    return false;
}

/**
 * Check in custom dates which are non-bookable
 */
function bkap_check_in_custom_holiday_range ( $start_date, $end_date, $custom_start_date, $custom_end_date ) {

    $start_ts            =   strtotime( $start_date );  
    $end_ts              =   strtotime( $end_date );      
    $new_custom_array    =   array();
    $custom_return_value =   array();
     while ($start_ts <= $end_ts) {
        $new_custom_array[] = $start_date;

        $start_ts           = strtotime( '+1 day', $start_ts );
        $start_date         = date( "j-n-Y", $start_ts );
    } 
    
    foreach ($new_custom_array as $key => $value) {

        $custom_values = strtotime( $value );  
        if ( $custom_values >= strtotime ($custom_start_date) && $custom_values <= strtotime ($custom_end_date ) ) {    
            $custom_return_value [$value] = true;
        }else{
            $custom_return_value [$value] = false;
        }
    } 
    
    return $custom_return_value;   
}