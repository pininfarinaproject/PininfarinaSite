<?php

/**
 * Database update tab. Displayed only when
 * automatic update fails.
 * @since 4.0.0
 */
function bkap_400_update_db_tab() {
    ?>
    <form method="post">
    <h2><?php _e( 'DB Update for Booking & Appointment Plugin', 'woocommerce-booking' ); ?></h2>
    <?php 
    // re-confirm the db update status
    $db_status = get_option( 'bkap_400_update_db_status' );
    
    $db_status_410 = get_option( 'bkap_410_update_db_status' );

    $message = "";
     
    $plugin_version = get_option( 'woocommerce_booking_db_version' );
     
    $valid_status = array( 'fail', 'success' );

    // Add GCAL meta
    $gcal_meta_update = get_option( 'bkap_420_update_gcal_meta' );
    if ( $gcal_meta_update === null ) {
        bkap_420_gcal_meta();
    }

    if ( ! empty( $_POST[ 'bkap_420_1' ] ) || isset( $_GET[ 'loop_next_view' ] ) ) {
        $number_of_batches = bkap_get_db_count();

        if ( isset( $_GET[ 'batch' ] ) && $_GET[ 'batch' ] != 0 ) {
            $loop = $_GET[ 'batch' ];
            $loop = (int)$loop;
        }else{
            $loop = 1;
        }
        
        if ( $loop <= $number_of_batches ){
            $_status = bkap_db_420_1( $loop );

            $status_percent = round( ( ( $loop * 100 ) / $number_of_batches ), 0 );
            // add the progress
            ?>
                <style type="text/css">
                    #bkap_update_progress {
                        width: 100%;
                        background-color: grey;
                    }

                    #bkap_progress_bar {
                        width: <?php echo $status_percent;?>%;
                        height: 30px;
                        background-color: #0085ba;
                        text-align: center;
                        line-height: 30px;
                        color: white; 
                    }
                </style>
                <div id="bkap_update_progress">
                    <div id="bkap_progress_bar"><?php echo $status_percent;?>%</div>
                </div>
            <?php
                
            $loop = $loop + 1;
            // reload the page so the progress can be displayed                
            $args = array( 'post_type' => $_REQUEST[ 'post_type' ],
                        'page' => $_REQUEST[ 'page' ],
                        'action' => $_REQUEST[ 'action' ],
                        'batch' => $loop,
                        'loop_next_view' => 'true',
             );

            $redirect_url = add_query_arg( $args, get_admin_url() . 'edit.php' );

            ?>
                <script type="text/javascript">
                    window.location.href = "<?php echo $redirect_url;?>";
                </script>
            <?php
        }else {
            $bkap_view_status = 'success';
            $bkap_view_update_stat = get_option( 'bkap_420_update_stats' );
            if ( isset( $bkap_view_update_stat ) && count( $bkap_view_update_stat ) > 0 && is_array( $bkap_view_update_stat ) ) {
                foreach ( $bkap_view_update_stat as $stat_key => $stat_value ) {
                    if ( isset( $stat_value['failed_count'] ) && $stat_value['failed_count'] > 0 ) {
                        $bkap_view_status = 'fail';
                        break;
                    }
                }
            }
            if ( $bkap_view_status === 'success' ) {
                update_option( 'bkap_420_update_db_status', 'success' );
            }else if ( $bkap_view_status === 'fail' ) {
                update_option( 'bkap_420_update_db_status', 'fail' );
            }
        }
        
    }

    if ( ! empty( $_POST[ 'bkap_420_2' ] ) || isset( $_GET[ 'loop_next' ] ) ) {
        $number_of_batches = bkap_420_gcal_batch_size();

        if ( isset( $_GET[ 'batch_gcal' ] ) && $_GET[ 'batch_gcal' ] != 0 ) {
            $loop = $_GET[ 'batch_gcal' ];
            $loop = (int)$loop;
        }else{
            $loop = 1;
        }
        
        if ( $loop <= $number_of_batches ){
            $_status = bkap_db_420_2( $loop );
        

            $status_percent = round( ( ( $loop * 100 ) / $number_of_batches ), 0 );
            // add the progress
            ?>
                <style type="text/css">
                    #bkap_update_progress {
                        width: 100%;
                        background-color: grey;
                    }

                    #bkap_progress_bar {
                        width: <?php echo $status_percent;?>%;
                        height: 30px;
                        background-color: #0085ba;
                        text-align: center;
                        line-height: 30px;
                        color: white; 
                    }
                </style>
                <div id="bkap_update_progress">
                    <div id="bkap_progress_bar"><?php echo $status_percent;?>%</div>
                </div>
            <?php
                
            $loop = $loop + 1;
            // reload the page so the progress can be displayed                
            $args = array( 'post_type' => $_REQUEST[ 'post_type' ],
                        'page' => $_REQUEST[ 'page' ],
                        'action' => $_REQUEST[ 'action' ],
                        'batch_gcal' => $loop,
                        'loop_next' => 'true',
             );
            //wp_safe_redirect( add_query_arg( $args, get_admin_url() . 'edit.php' ) );

            $redirect_url = add_query_arg( $args, get_admin_url() . 'edit.php' );

            ?>
                <script type="text/javascript">
                    window.location.href = "<?php echo $redirect_url;?>";
                </script>
            <?php 
        }else {
            $bkap_gcal_status = 'success';
            $bkap_gcal_update_stat = get_option( 'bkap_420_gcal_update_stats' );
            if ( isset( $bkap_gcal_update_stat ) && count( $bkap_gcal_update_stat ) > 0 && is_array( $bkap_gcal_update_stat ) ) {
                foreach ( $bkap_gcal_update_stat as $stat_key => $stat_value ) {
                    if ( isset( $stat_value['failed_count'] ) && $stat_value['failed_count'] > 0 ) {
                        $bkap_gcal_status = 'fail';
                        break;
                    }
                }
            }
            if ( $bkap_gcal_status === 'success' ) {
                update_option( 'bkap_420_update_gcal_status', 'success' );
            }else if ( $bkap_gcal_status === 'fail' ) {
                update_option( 'bkap_420_update_gcal_status', 'fail' );
            }
        }
        
    }    
    
    $db_status_420 = get_option( 'bkap_420_update_db_status' );
    
    $gcal_status_420 = get_option( 'bkap_420_update_gcal_status' );

    if ( isset( $plugin_version ) && '4.1.0' <= $plugin_version &&
        isset( $db_status_420 ) && !in_array( $db_status_420, $valid_status ) &&
        isset( $gcal_status_420 ) && !in_array( $gcal_status_420, $valid_status ) ) { // Both scripts are yet to be run
        
        $message = '
                        <p>To ensure you experience a smooth migration to version 4.2.0, we need to run a DB Update. Please click on the Start button below for Step 1 of 2  to begin.</p>
                        <p><input type="submit" name="bkap_420_1" class="button-primary" value="Start" />
                            </p>';
        
            
    }else if ( isset( $plugin_version ) && '4.1.0' <= $plugin_version &&
        isset( $gcal_status_420 ) && !in_array( $gcal_status_420, $valid_status ) ) {
        $message = '
                        <p>Please click on the Start button below for Step 2 of 2 to begin.</p>
                        <p><input type="submit" name="bkap_420_2" class="button-primary" value="Start" />
                            </p>';
    }
    
    if ( isset( $db_status ) && 'success' === strtolower( $db_status ) && 
         isset( $db_status_410 ) && 'success' === strtolower( $db_status_410 ) &&
         isset( $db_status_420 ) && 'success' === strtolower( $db_status_420 ) &&
         isset( $gcal_status_420 ) && 'success' === strtolower( $gcal_status_420 ) ) {
        $message = 'The database update for Booking & Appointment plugin for WooCommerce was successful.';
    } else if ( ( isset( $db_status ) && 'fail' == strtolower( $db_status ) ) || 
        ( isset( $db_status_410 ) && 'fail' == strtolower( $db_status_410 ) ) || 
        ( isset( $db_status_420 ) && 'fail' == strtolower( $db_status_420 ) ) ||
        isset( $gcal_status_420 ) && 'fail' == strtolower( $gcal_status_420 ) ) {
        
        $manual_update_count = get_option( 'bkap_400_manual_update_count', 'woocommerce-booking' );
        $manual_update_count_410 = get_option( 'bkap_410_manual_update_count', 'woocommerce-booking' );
        $manual_update_count_420 = get_option( 'bkap_420_manual_update_count', 'woocommerce-booking' );
        
        if ( ( isset( $manual_update_count ) && '1' == $manual_update_count ) || 
            ( isset( $manual_update_count_410 ) && '1' == $manual_update_count_410 ) ||
            ( isset( $manual_update_count_420 ) && '1' == $manual_update_count_420 ) ) {
            
            $message = "<p>We have been unsuccessful in updating the database for Booking & Appointment plugin.</p>";
            
            $message .= "<p>Request you to kindly contact ";
			$message .= '<a href="http://feedback.tychesoftwares.com/">support</a>';
			$message .= " at Tyche Softwares.</p>";
				
        } else {
            
            $message = "";
            
            if( $db_status == 'fail' || $db_status_410 == 'fail' || $db_status_420 == 'fail' || $gcal_status_420 == 'fail' ){
                $message = "<p><h3>We need to update your database in order for Booking & Appointment Plugin to run smoothly.<h3></p><p><h3>Unfortunately, the automatic update has failed. Please click below to manually update the database.</h3></p>";
            }
            
            if( $db_status == 'fail' ){
                $message .= '<p>Database update based for changes in v4.0.0. Please <b><a href="javascript:void(0);" id="bkap_update_link" style="color:red;">click here</a></b>.</p>';
            }
            
            if( $db_status_410 == 'fail' ){
                $message .= '<p>Database update based on Fixed Block and Price by ranges changes in v4.1.0. Please <b><a href="javascript:void(0);" id="bkap_update_link_f_p" style="color:red;">click here</a></b>.</p>';
            }

            if( $db_status_420 == 'fail' || $gcal_status_420 == 'fail' ){
                $message .= '<p>Database update for changes in v4.2.0. Please <b><a href="javascript:void(0);" id="bkap_update_link_v420" style="color:red;">click here</a></b>.</p>';
            }
        }
        
    }
    
    _e( $message, 'woocommerce-booking' );
    ?>
    
    <div id="bkap_progress" style="display:none;"></div>
    <div id="bkap_result" style="display:none;"></div>
    <div id="bkap_progress_f_p" style="display:none;"></div>
    <div id="bkap_result_f_p" style="display:none;"></div>
    <br/>
    <br/>
    <br/>
    <br/>
    </form>
    <?php
}

/**
 * Will be run once on updating to version 4.0.0 or higher
 * 
 * For each product, it copies the serialized data to
 * individual post meta record for easier access
 * @since 4.0.0
 */
function bkap_400_update_settings( $db_version ) {
    
    global $wpdb;
    
    $all_products = bkap_common::get_woocommerce_product_list( false );
    	
    $product_list = array();
    
    if ( isset( $all_products ) && count( $all_products ) > 0 ) {
        foreach( $all_products as $a_key => $a_value ) {
            $bookable = bkap_common::bkap_get_bookable_status( $a_value[ 1 ] );
            
            // if the product is bookable
            if ( $bookable ) {
                 $product_list[ $a_key ] = $a_value;
            }
        }
    }
    
    $db_version = str_replace( '.', '', $db_version );
    
    if ( isset( $product_list ) && count( $product_list ) > 0 ) {
        // total number of bookable products
        $total_bookable_count = count( $product_list );
        
        foreach( $all_products as $p_key => $p_value ) {
            
            // check if update status record exists
            $update_status = get_post_meta( $p_value[ 1 ], '_bkap_400_update_status', true );
            
            // if yes, skip the product
            if ( isset( $update_status ) && 'completed' == strtolower( $update_status ) ) {
                continue;
            } else { 
                $meta_key = "woocommerce_booking_settings_$db_version";
                
                $backup_data = get_post_meta( $p_value[ 1 ], $meta_key, true );
                
                if ( is_array( $backup_data ) && count( $backup_data ) > 0 ) {
                    // backup has been created.. so do nothing..  simply update       
                } else {
                    
                    $booking_settings = get_post_meta( $p_value[ 1 ], 'woocommerce_booking_settings', true );
                    // create a backup record
                    add_post_meta( $p_value[ 1 ], $meta_key, $booking_settings );
                }

                // copy the special prices into the new postmeta
                bkap_400_update_special( $p_value[ 1 ] );
                
                // convert the product holidays from string to an array
                bkap_400_update_holidays( $p_value[ 1 ] );
                
                // change the specific dates array format
                bkap_400_update_specific( $p_value[ 1 ] );

                // change the fixed range format
                bkap_400_update_ranges( $p_value[ 1] );

                // create recurring lockout
                bkap_400_update_recurring_lockout( $p_value[ 1 ] );

                // create recurring weekdays record and add maximum nights
                bkap_400_recurring_data( $p_value[ 1 ] );

                // update specific dates if needed
                bkap_400_update_enable_specific( $p_value[ 1 ] );
                
                bkap_400_update_enable_week_blocking( $p_value[ 1 ] );
                
                // create individualized booking meta data
                bkap_400_create_meta( $p_value[ 1 ] );
                
                add_post_meta( $p_value[ 1 ], '_bkap_400_update_status', 'completed' );
            }
            
        }
        
        if ( isset( $total_bookable_count ) && $total_bookable_count > 0 ) {
            // get the number of update status count

            $count_query = "SELECT Count( post_id) AS RecordCount FROM `" . $wpdb->prefix. "postmeta`
                            WHERE meta_key = %s";
            
            $get_count = $wpdb->get_results( $wpdb->prepare( $count_query, '_bkap_400_update_status' ) );
            
            $total_records = $get_count[ 0 ]->RecordCount;
            
            if ( $total_records == $total_bookable_count ) {
                $_status = 'success';
            } else {
                $_status = 'fail';
            }
            
            update_option( 'bkap_400_update_db_status', $_status );
        }
        
    }
    
}

/**
 * Will be run once on updating to version 4.1.0 or higher
 *
 * For each product, it copies the serialized data of Fixed Block Booking and Price By Range to 
 * individual post meta record for easier access
 * @since 4.1.0
 */

function bkap_410_update_settings( $db_version ) {

    
    global $wpdb;

    $all_products = bkap_common::get_woocommerce_product_list_f_p( false ); // Getting all the product ids where price by range or fixed block booking is enabled.
     
    $product_list = $all_products;

//     if ( isset( $all_products ) && count( $all_products ) > 0 ) {
//         foreach( $all_products as $a_key => $a_value ) {
//             $bookable = bkap_common::bkap_get_bookable_status( $a_value[ 1 ] );

//             // if the product is bookable
//             //if ( $bookable ) {
//                 $product_list[ $a_key ] = $a_value;
//             //}
//         }
//     }

    $db_version = str_replace( '.', '', $db_version );
    
    if ( isset( $product_list ) && count( $product_list ) > 0 ) {
        // total number of bookable products
        $total_bookable_count = count( $product_list );
    
    
        foreach( $all_products as $p_key => $p_value ) {
            
            // check if update status record exists
            $update_status = get_post_meta( $p_value[ 1 ], '_bkap_410_update_status', true );
            
            // if yes, skip the product
            if ( isset( $update_status ) && 'completed' == strtolower( $update_status ) ) {
                continue;
            } else {
                $meta_key = "woocommerce_booking_settings_f_p_$db_version";
            
                $backup_data = get_post_meta( $p_value[ 1 ], $meta_key, true );
            
                if ( is_array( $backup_data ) && count( $backup_data ) > 0 ) {
                    // backup has been created.. so do nothing..  simply update
                } else {
            
                    $booking_settings = get_post_meta( $p_value[ 1 ], 'woocommerce_booking_settings', true );
                    // create a backup record
                    add_post_meta( $p_value[ 1 ], $meta_key, $booking_settings );
                }
            
                // copy the fixed block booking into the new postmeta
                bkap_410_update_fixed_blocks( $p_value[ 1 ] );
                
                // copy the price by range into the new postmeta
                bkap_410_update_price_ranges( $p_value[ 1 ] );
                
                // Previously we were storing Booking Block Pricing and Price By Range as yes or no
                // Now we are storing as id of this options.
                bkap_410_update_block_pricing_option_values_in_new_way( $p_value[ 1 ] );
            
                // create individualized booking meta data for fixed blocks and price ranges
                bkap_410_create_meta( $p_value[ 1 ] );
            
                add_post_meta( $p_value[ 1 ], '_bkap_410_update_status', 'completed' );
            }
            
        }
        
        if ( isset( $total_bookable_count ) && $total_bookable_count > 0 ) {
            // get the number of update status count
        
            $count_query = "SELECT Count( post_id) AS RecordCount FROM `" . $wpdb->prefix. "postmeta`
                            WHERE meta_key = %s";
        
            $get_count = $wpdb->get_results( $wpdb->prepare( $count_query, '_bkap_410_update_status' ) );
        
            $total_records = $get_count[ 0 ]->RecordCount;
        
            if ( $total_records == $total_bookable_count ) {
                $_status = 'success';
            } else {
                $_status = 'fail';
            }
        
            update_option( 'bkap_410_update_db_status', $_status );
        }
    }
    
}

/**
 * For each product, it copies the serialized data of Fixed Block Booking and Price By Range to
 * individual post meta record for easier access
 * @param int $product_id
 * @since 4.1.0
 */

function bkap_410_create_meta( $product_id ) {
    
    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
     
    //$bookable = bkap_common::bkap_get_bookable_status( $product_id );
    
    // if the product is bookable
    //if ( $bookable ) {
        
        // Fixed Blocks
        $bkap_fixed_blocks_data = array();
        if ( isset( $booking_settings[ 'bkap_fixed_blocks_data' ] ) ) {
            $bkap_fixed_blocks_data = $booking_settings[ 'bkap_fixed_blocks_data' ];
        }
        
        // Fixed Blocks
        $bkap_price_range_data = array();
        if ( isset( $booking_settings[ 'bkap_price_range_data' ] ) ) {
            $bkap_price_range_data = $booking_settings[ 'bkap_price_range_data' ];
        }
        
        $meta_args = array(
            '_bkap_fixed_blocks_data'   => $bkap_fixed_blocks_data,
            '_bkap_price_range_data'    => $bkap_price_range_data
        );
        
        // run a foreach and save the data
        foreach ( $meta_args as $key => $value ) {
            update_post_meta( $product_id, $key, $value );
        }
    //}
}

/**
 * Fetch fixed blocks records from booking_fixed_blocks and update data
 * in the woocomemrce_booking_settings post meta.
 * 
 * @param int $product_id
 * @since 4.1.0
 */
function bkap_410_update_fixed_blocks( $product_id ) {
    
    global $wpdb;
    
    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
	 
	//$bookable = bkap_common::bkap_get_bookable_status( $product_id );
	
	$booking_fixed_block_enable = "";
	
	if( isset( $booking_settings['booking_fixed_block_enable'] ) && $booking_settings['booking_fixed_block_enable'] != "" ){
	    $booking_fixed_block_enable = $booking_settings['booking_fixed_block_enable'];
	}
	
	// if the product is bookable
	if ( $booking_fixed_block_enable != "" ) {
	
	    $fixed_query              =  "SELECT * FROM `".$wpdb->prefix."booking_fixed_blocks`
						             WHERE post_id = %d";
	    	
	    $fixed_results            =  $wpdb->get_results( $wpdb->prepare( $fixed_query, $product_id ) );
	    
	    $array_of_all_fixed_block_data = array();
	    
		if( $fixed_results != "" && count($fixed_results) > 0 ){
		    
		    
		    $i=0 ;
		    
		    foreach( $fixed_results as $fixed_results_key => $fixed_results_value ){
		    
		        $array_of_all_fixed_block_data[$i]['block_name']      = $fixed_results_value->block_name;
		        $array_of_all_fixed_block_data[$i]['number_of_days']  = $fixed_results_value->number_of_days;
		        $array_of_all_fixed_block_data[$i]['start_day']       = $fixed_results_value->start_day;
		        $array_of_all_fixed_block_data[$i]['end_day']         = $fixed_results_value->end_day;
		        $array_of_all_fixed_block_data[$i]['price']           = $fixed_results_value->price;
		        $i++;
		    }
		} 
		
		$booking_settings[ 'bkap_fixed_blocks_data' ] = $array_of_all_fixed_block_data;
		update_post_meta( $product_id , 'woocommerce_booking_settings', $booking_settings );
	}
}

/**
 * In version 4.0.0 we were saving Fixed Block and Price by range value as yes no.
 * Now in version 4.1.0 we are storing these options as "booking_fixed_block_enable" and "booking_block_price_enable" 
 *
 * @param int $product_id
 * @since 4.1.0
 */

function bkap_410_update_block_pricing_option_values_in_new_way( $product_id ){
    
    global $wpdb;
    
    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
     
    //$bookable = bkap_common::bkap_get_bookable_status( $product_id );
    
    $booking_block_price_enable = "";
    $booking_fixed_block_enable = "";
    
    // fixed blocks
    if( isset( $booking_settings['booking_fixed_block_enable'] ) && $booking_settings['booking_fixed_block_enable'] != "" ){
        $booking_fixed_block_enable = $booking_settings['booking_fixed_block_enable'];
    }
    
    if( $booking_fixed_block_enable == "yes" ){
        $booking_fixed_block_enable = "booking_fixed_block_enable";
    }
    
    $booking_settings[ 'booking_fixed_block_enable' ] = $booking_fixed_block_enable;
    update_post_meta( $product_id , '_bkap_fixed_blocks', $booking_fixed_block_enable );
    
    // price by range
    if( isset( $booking_settings['booking_block_price_enable'] ) && $booking_settings['booking_block_price_enable'] != "" ){
        $booking_block_price_enable = $booking_settings['booking_block_price_enable'];
    }
    
    if( $booking_block_price_enable == "yes" ){
        $booking_block_price_enable = "booking_block_price_enable";
    }
    
    $booking_settings[ 'booking_block_price_enable' ] = $booking_block_price_enable;
    update_post_meta( $product_id , '_bkap_price_ranges', $booking_block_price_enable );
    
    update_post_meta( $product_id , 'woocommerce_booking_settings', $booking_settings );
    
}

/**
 * Fetch price ranges records from wp_booking_fixed_blocks and wp_booking_block_price_meta and update data
 * in the woocomemrce_booking_settings post meta.
 *
 * @param int $product_id
 * @since 4.1.0
 */

function bkap_410_update_price_ranges( $product_id ){
    
    global $wpdb;
    
    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
    	
    //$bookable = bkap_common::bkap_get_bookable_status( $product_id );
    
    $booking_block_price_enable = "";
    
    if( isset( $booking_settings['booking_block_price_enable'] ) && $booking_settings['booking_block_price_enable'] != "" ){
        $booking_block_price_enable = $booking_settings['booking_block_price_enable'];
    }
    
    // if the product is bookable
    if ( $booking_block_price_enable != "" ) {
        
        $join_sub_query = "";
        $c1 = "";
        $product        = wc_get_product( $product_id );
        $product_type   = $product->get_type();
        
        $demo_array     = array();
        $bkap_price_range_data = array();
        
        if( $product_type == 'variable' ) {
             
            $product_attributes      = get_post_meta( $product_id, '_product_attributes', true );
            $product_attributes_keys = array_keys( $product_attributes );
            $default_array           = array('min_number', 'max_number', 'per_day_price', 'fixed_price'); // default array
            $demo_array              = array_merge( $product_attributes_keys, $default_array ); // creating a demo array. This will help later to arrange the final price range data.
             
            if( is_array( $product_attributes ) && count( $product_attributes ) > 0 ){
                $join_sub_query = " JOIN `".$wpdb->prefix."booking_block_price_attribute_meta` AS c1 ON c0.id = c1.block_id";
                $c1 = ",c1.attribute_id,c1.meta_value";
            }
        }
        	
        $price_query    = "SELECT c0.id,c0.minimum_number_of_days,c0.maximum_number_of_days,c0.price_per_day,c0.fixed_price".$c1." FROM `".$wpdb->prefix."booking_block_price_meta` AS c0
    					  ".$join_sub_query."
    					  WHERE c0.post_id = %d";
        
        $price_results  = $wpdb->get_results( $wpdb->prepare( $price_query, $product_id ) );
        
        $range_ids = array();
        $array_of_all_price_range_data = array(); // In this array we will store final records.
        $i = 0;
        
        if( $price_results != "" && count($price_results) > 0 ){
            
        
            foreach( $price_results as $price_results_key => $price_results_value ){
            
                $id               = $price_results_value->id;
                $min              = $price_results_value->minimum_number_of_days;
                $max              = $price_results_value->maximum_number_of_days;
                $per_day_price    = $price_results_value->price_per_day;
                $fixed_price      = $price_results_value->fixed_price;
            
                if( $c1 != "" ){
                    $att            = $price_results_value->attribute_id - 1; // we will get key which used to find the attribute name from $product_attributes_keys array
                    $attribute_name = $product_attributes_keys[$att]; // Got arribute name.
                    $att_val        =  $price_results_value->meta_value;
                }
            
                if( !in_array( $id, $range_ids ) ){
                     
                    $range_ids[ $i ] = $id;
            
                    if( $c1 != "" ){
                        $array_of_all_price_range_data[$i][$attribute_name]      = $att_val;
                    }
            
                    $array_of_all_price_range_data[$i]['min_number']      = $min;
                    $array_of_all_price_range_data[$i]['max_number']      = $max;
                    $array_of_all_price_range_data[$i]['per_day_price']   = $per_day_price;
                    $array_of_all_price_range_data[$i]['fixed_price']     = $fixed_price;
            
                     
                }else{
            
                    $j = array_search( $id, $range_ids );
            
                    if( $c1 != "" ){
                        $array_of_all_price_range_data[$j][$attribute_name]      = $att_val;
                    }
                }
                $i++;
            }
            
            
            if( $c1 != "" ){ // If poroduct is variable product then the array which we got is not in proper sequance as we want to store in post meta hence arranging the same here.
                $k = 0;
                foreach( $array_of_all_price_range_data as $array_of_all_price_range_data_key => $array_of_all_price_range_data_value ){
                    $bkap_price_range_data[$k] = array_replace( array_flip( $demo_array ), $array_of_all_price_range_data_value );
                    $k++;
                }
            }else{
                $bkap_price_range_data = $array_of_all_price_range_data;
            }
        }// end if.
        
        $booking_settings[ 'bkap_price_range_data' ] = $bkap_price_range_data;
        update_post_meta( $product_id , 'woocommerce_booking_settings', $booking_settings );
        
    }
}

/**
 * For each product, it copies the serialized data to
 * individual post meta record for easier access
 * @param int $product_id
 * @since 4.0.0
 */
function bkap_400_create_meta( $product_id ) {
    
    // get the original product (applicable for sites which use multiple languages
//    $duplicate_of = bkap_common::bkap_get_product_id( $product_id );
    // get the woocommerce booking settings record
    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
     
    $bookable = bkap_common::bkap_get_bookable_status( $product_id );
    
    // if the product is bookable
    if ( $bookable ) {
        
        // earlier, we did not save the booking type. We checked individual settings to determine the same
        // From now on, we will save the booking type, making it easier to determine the processing needed
        if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && 'on' == $booking_settings[ 'booking_enable_multiple_day' ] ) {
            $booking_type = 'multiple_days';
        } else if ( isset( $booking_settings[ 'booking_enable_time' ] ) && 'on' == $booking_settings[ 'booking_enable_time' ] ) {
            $booking_type = 'date_time';
        } else {
            $booking_type = 'only_day';
        }
         
        // set the default minimum number of days for multiple day booking to 0
        $multiple_min = 0;
        if ( isset( $booking_settings[ 'booking_minimum_number_days_multiple' ] ) && 'on' == $booking_settings[ 'booking_minimum_number_days_multiple' ] ) {
            $multiple_min = $booking_settings[ 'booking_minimum_number_days_multiple' ];
        }
        
        // set the default maximum number of days for multiple day booking to 365
        $multiple_max = 365;
        if ( isset( $booking_settings[ 'booking_maximum_number_days_multiple' ] ) ) {
            $multiple_max = $booking_settings[ 'booking_maximum_number_days_multiple' ];
        }
        
        // recurring lockout
        $recurring_lockout = array();
        if ( isset( $booking_settings[ 'booking_recurring_lockout' ] ) ) {
            $recurring_lockout = $booking_settings[ 'booking_recurring_lockout' ];
        }
        
        // price by range
        $price_ranges = '';
        if ( isset( $booking_settings[ 'booking_block_price_enable' ] ) ) {
            $price_ranges = $booking_settings[ 'booking_block_price_enable' ];
        }

        // fixed blocks
        $fixed_blocks = '';
        if ( isset( $booking_settings[ 'booking_fixed_block_enable' ] ) ) {
            $fixed_blocks = $booking_settings[ 'booking_fixed_block_enable' ];
        }
        
        // gcal automated mapping
        $automated_mapping = '';
        if ( isset( $booking_settings[ 'enable_automated_mapping' ] ) ) {
            $automated_mapping = $booking_settings[ 'enable_automated_mapping' ];
        }
        // gcal default variation
        $default_variation = 0;
        if ( isset( $booking_settings[ 'gcal_default_variation' ] ) ) {
            $default_variation = $booking_settings[ 'gcal_default_variation' ];
        }
        
        // create an array of the meta keys for individual data
        $meta_args = array(
            '_bkap_enable_booking' => $booking_settings[ 'booking_enable_date' ],
            '_bkap_booking_type' => $booking_type,
            '_bkap_enable_specific' => $booking_settings[ 'booking_specific_booking' ],
            '_bkap_enable_recurring' => $booking_settings[ 'booking_recurring_booking' ],
            '_bkap_specific_dates' => $booking_settings[ 'booking_specific_date' ],
            '_bkap_recurring_weekdays' => $booking_settings[ 'booking_recurring' ],
            '_bkap_recurring_lockout' => $recurring_lockout,
            '_bkap_enable_inline' => $booking_settings[ 'enable_inline_calendar' ],
            '_bkap_purchase_wo_date' => $booking_settings[ 'booking_purchase_without_date' ],
            '_bkap_requires_confirmation' => $booking_settings[ 'booking_confirmation' ],
            '_bkap_product_holidays' => $booking_settings[ 'booking_product_holiday' ],
            '_bkap_multiple_day_min' => $multiple_min,
            '_bkap_multiple_day_max' => $multiple_max,
            '_bkap_date_lockout' => $booking_settings[ 'booking_date_lockout' ],
            '_bkap_custom_ranges' => $booking_settings[ 'booking_date_range' ],
            '_bkap_abp' => $booking_settings[ 'booking_minimum_number_days' ],
            '_bkap_max_bookable_days' => $booking_settings[ 'booking_maximum_number_days' ],
            '_bkap_time_settings' => $booking_settings[ 'booking_time_settings' ],
            '_bkap_fixed_blocks' => $fixed_blocks,
            '_bkap_price_ranges' => $price_ranges,
            '_bkap_gcal_integration_mode' => $booking_settings[ 'product_sync_integration_mode' ],
            '_bkap_gcal_key_file_name' => $booking_settings[ 'product_sync_key_file_name' ],
            '_bkap_gcal_service_acc' => $booking_settings[ 'product_sync_service_acc_email_addr' ],
            '_bkap_gcal_calendar_id' => $booking_settings[ 'product_sync_calendar_id' ],
            '_bkap_enable_automated_mapping' => $automated_mapping,
            '_bkap_default_variation' => $default_variation,
            '_bkap_import_url' => $booking_settings[ 'ics_feed_url' ]
        );
        // run a foreach and save the data
        foreach ( $meta_args as $key => $value ) {
            update_post_meta( $product_id, $key, $value );
        }
    }
    
}

/**
 * We're moving the booking_special_price
 * post meta record to _bkap_special_price
 * from 4.0.0
 * @param int $product_id
 * @since 4.0.0
 */
function bkap_400_update_special( $product_id ) {

    $special_prices = get_post_meta( $product_id, 'booking_special_price', true );

    update_post_meta( $product_id, '_bkap_special_price', $special_prices );

    delete_post_meta( $product_id, 'booking_special_price' );
}

/**
 * Up to v3.5.x holidays were saved as a string.
 * From v4.0.0, they will be saved as an array
 * where the date is the key and the number of years to 
 * recur is the value. 
 * This function modifies the old records to the 
 * new format for each bookable product.
 * @param int $product_id
 * @since 4.0.0
 */
function bkap_400_update_holidays( $product_id ) {

    // get the woocommerce booking settings record
    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );

    $holidays_list = array();

    $product_holidays = isset( $booking_settings[ 'booking_product_holiday' ] ) ? $booking_settings[ 'booking_product_holiday' ] : '';

    if ( ! is_array( $product_holidays ) && '' != $product_holidays ) {
        $explode_holidays = explode( ',', $product_holidays );

        if ( is_array( $explode_holidays ) && count( $explode_holidays ) > 0 ) {
            foreach( $explode_holidays as $h_date ) {
                if ( '' != $h_date ) {
                    $holidays_list[ $h_date ] = 0;
                }
            }
        }
    }

    $booking_settings[ 'booking_product_holiday' ] = $holidays_list;
    update_post_meta( $product_id, 'woocommerce_booking_settings', $booking_settings );
}

function bkap_400_update_enable_week_blocking( $product_id ){
    
    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
    
    $wkpbk_block_single_week = $special_booking_start_weekday = $special_booking_end_weekday = '';

    if ( isset( $booking_settings [ 'wkpbk_block_single_week' ] ) ) {
        $wkpbk_block_single_week = $booking_settings [ 'wkpbk_block_single_week' ];
    }

    if ( isset( $booking_settings [ 'special_booking_start_weekday' ] ) ) {
        $special_booking_start_weekday = $booking_settings [ 'special_booking_start_weekday' ];
    }

    if ( isset( $booking_settings [ 'special_booking_end_weekday' ] ) ) {
        $special_booking_end_weekday = $booking_settings [ 'special_booking_end_weekday' ];
    }

    if( isset( $wkpbk_block_single_week ) && 'on' == $wkpbk_block_single_week ) {
        $booking_settings [ 'special_booking_start_weekday' ] = $special_booking_start_weekday;
        $booking_settings [ 'special_booking_end_weekday' ] = $special_booking_end_weekday;
    }

    update_post_meta( $product_id, 'woocommerce_booking_settings', $booking_settings );
}

/**
 * Upto v3.5.x specific dates were stored as array values. 
 * From v4.0.0 the date will be the key in the array and the 
 * lockout value will be stored as the array value.
 * This function will be run once on updating the plugin.
 * It will convert the old array to the new format.
 * @param int $product_id
 * @since 4.0.0
 */
function bkap_400_update_specific( $product_id ) {

    global $wpdb;
    // get the woocommerce booking settings record
    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );

    $specific_dates = array();

    $specific_list = isset( $booking_settings[ 'booking_specific_date' ] ) ? $booking_settings[ 'booking_specific_date' ] : '';

    if ( is_array( $specific_list ) && count( $specific_list ) > 0 ){

        foreach( $specific_list as $s_date ) {
            if ( '' != $s_date ) {
                $specific_date = date( 'Y-m-d', strtotime( $s_date ) );
                // find a record for the given date and product in the booking history table
                $query_specific = "SELECT total_booking FROM `" . $wpdb->prefix . "booking_history`
                                    WHERE post_id = %d
                                    AND start_date = %s
                                    AND weekday = ''";
                $get_specific = $wpdb->get_results( $wpdb->prepare( $query_specific, $product_id, $specific_date ) );

                if ( count( $get_specific ) > 0 ) {
                    $lockout = 0;
                    foreach( $get_specific as $s_lockout ) {
                        $lockout += $s_lockout->total_booking;
                    }
                    $specific_dates[ $s_date ] = $lockout;
                } else {
                    $specific_dates[ $s_date ] = 0;
                }
            }
        }
    }

    $booking_settings[ 'booking_specific_date' ] = $specific_dates;
    update_post_meta( $product_id, 'woocommerce_booking_settings', $booking_settings );

}

/**
 * Upto v3.5.x the plugin allowed only a single fixed range
 * From v4.0.0 the plugin will allow multiple ranges. Hence
 * this function modifies the way the data is stored.
 * lockout value will be stored as the array value.
 *
 * This function will be run once on updating the plugin.
 * It will convert the old data to the new format.
 * @param int $product_id
 * @since 4.0.0
 */
function bkap_400_update_ranges( $product_id ) {

    // get the woocommerce booking settings record
    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );

    // fixed range as custom ranges
    $fixed_range_array = array();

    if ( isset( $booking_settings[ 'booking_start_date_range' ] ) && '' != $booking_settings[ 'booking_start_date_range' ] && isset( $booking_settings[ 'booking_end_date_range' ] ) && '' != $booking_settings[ 'booking_end_date_range' ] ) {
        $fixed_range_array[] = array( 'start' => $booking_settings[ 'booking_start_date_range' ],
            'end' => $booking_settings[ 'booking_end_date_range' ],
            'years_to_recur' => $booking_settings[ 'booking_range_recurring_years' ]
        );

    }

    $booking_settings[ 'booking_date_range' ] = $fixed_range_array;
    unset( $booking_settings[ 'booking_start_date_range' ] );
    unset( $booking_settings[ 'booking_end_date_range' ] );
    unset( $booking_settings[ 'recurring_booking_range' ] );
    unset( $booking_settings[ 'booking_range_recurring_years' ] );
    unset( $booking_settings[ 'booking_date_range_type' ] );

    update_post_meta( $product_id, 'woocommerce_booking_settings', $booking_settings );
}

/**
 * Adds recurring weekday lockout records
 * for only day and date & time bookings.
 * @param int $product_id
 * @since 4.0.0
 */
function bkap_400_update_recurring_lockout( $product_id ) {

    global $bkap_weekdays;
    global $wpdb;

    // get the woocommerce booking settings record
    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );

    // nothing needs to be done for multiple day bookings
    if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && 'on' == $booking_settings[ 'booking_enable_multiple_day' ] ) {
        return;
    }

    $recurring_lockout = array();
    // get the weekdays array
    foreach( $bkap_weekdays as $w_key => $name ) {

        // find a record for the given day and product in the booking history table
        $query_recurring = "SELECT total_booking FROM `" . $wpdb->prefix . "booking_history`
                                    WHERE post_id = %d
                                    AND start_date = '0000-00-00'
                                    AND weekday = %s";
        $get_recurring = $wpdb->get_results( $wpdb->prepare( $query_recurring, $product_id, $w_key ) );

        if ( count( $get_recurring ) > 0 ) {
            $lockout = 0;
            foreach( $get_recurring as $r_lockout ) {
                $lockout += $r_lockout->total_booking;
            }
            $recurring_lockout[ $w_key ] = $lockout;
        } else {
            $recurring_lockout[ $w_key ] = '';
        }

    }

    $booking_settings[ 'booking_recurring_lockout' ] = $recurring_lockout;

    update_post_meta( $product_id, 'woocommerce_booking_settings', $booking_settings );

}

/**
 *  Adds recurring weekday records
 *  & maximum nights for multiple day
 *  products.
 * @param int $product_id
 * @since 4.0.0
 */
function bkap_400_recurring_data( $product_id ) {

    global $bkap_weekdays;
    global $wpdb;

    // get the woocommerce booking settings record
    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );

    // nothing needs to be done for multiple day bookings
    if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && 'on' == $booking_settings[ 'booking_enable_multiple_day' ] ) {
        $recurring_weekdays = array();

        // get the weekdays array
        foreach( $bkap_weekdays as $w_key => $name ) {
            $recurring_weekdays[ $w_key ] = 'on';
        }
        $booking_settings[ 'booking_recurring' ] = $recurring_weekdays;

        // add maximum nights as 365 by default
        $booking_settings[ 'booking_maximum_number_days_multiple' ] = 365;
    }

    update_post_meta( $product_id, 'woocommerce_booking_settings', $booking_settings );

}

/**
 * Enables specific date booking if it is off
 * This is needed if the product uses custom
 * ranges or holidays
 * @param int $product_id
 * @since 4.0.0
 */
function bkap_400_update_enable_specific( $product_id ) {

    $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );

    if ( isset( $booking_settings[ 'booking_specific_booking' ] ) && 'on' != $booking_settings[ 'booking_specific_booking' ] ) {

        $update_needed = false;
        // check if holidays have been set
        if ( is_array( $booking_settings[ 'booking_product_holiday' ] ) && count( $booking_settings[ 'booking_product_holiday' ] ) > 0 ) {
            $update_needed = true;
        }

        // check if custom ranges have been set
        if ( is_array( $booking_settings[ 'booking_date_range' ] ) && count( $booking_settings[ 'booking_date_range' ] ) > 0 ) {
            $update_needed = true;
        }

        // check if special prices have been set for a date
        $booking_special_prices = get_post_meta( $product_id, '_bkap_special_price', true );
        
        if ( is_array( $booking_special_prices ) && count( $booking_special_prices ) > 0 ) {
            foreach( $booking_special_prices as $s_key => $s_value ) {
                if ( isset( $s_value[ 'booking_special_date' ] ) && $s_value[ 'booking_special_date' ] != '' ) {
                    $update_needed = true;
                    break;
                }
            }
        }
        
        if ( $update_needed ) {
            $booking_settings[ 'booking_specific_booking' ] = 'on';
            update_post_meta( $product_id, 'woocommerce_booking_settings', $booking_settings );
        }
    }

}

/**
 * This function will fetch the individual booking settings
 * saved in post meta and push them in a single array
 * and return the same.
 * @return array $booking_settings
 * @since 4.0.0
 */
function bkap_get_post_meta( $product_id ) {

    $booking_settings = array();
    
    if ( isset( $product_id ) && $product_id > 0 ) {
        
        // create an array of the meta keys for individual data
        $meta_args = array(
            '_bkap_enable_booking',
            '_bkap_booking_type',
            '_bkap_enable_specific',
            '_bkap_enable_recurring',
            '_bkap_specific_dates',
            '_bkap_recurring_weekdays',
            '_bkap_recurring_lockout',
            '_bkap_enable_inline',
            '_bkap_purchase_wo_date',
            '_bkap_requires_confirmation',
            '_bkap_product_holidays',
            '_bkap_multiple_day_min',
            '_bkap_multiple_day_max',
            '_bkap_date_lockout',
            '_bkap_custom_ranges',
            '_bkap_abp',
            '_bkap_max_bookable_days',
            '_bkap_time_settings',
            '_bkap_fixed_blocks',
            '_bkap_price_ranges',
            '_bkap_gcal_integration_mode',
            '_bkap_gcal_key_file_name',
            '_bkap_gcal_service_acc',
            '_bkap_gcal_calendar_id',
            '_bkap_enable_automated_mapping',
            '_bkap_default_variation',
            '_bkap_import_url' 
        );
        
        // run a foreach and save the data
        foreach ( $meta_args as $key => $value ) {
            $temp = get_post_meta( $product_id, $value, true );
            
            switch( $value ) {
                case '_bkap_enable_booking':
                    $booking_settings[ 'booking_enable_date' ] = $temp;
                    break;
                case '_bkap_booking_type':
                    if ( 'multiple_days' == $temp ) {
                        $booking_settings[ 'booking_enable_multiple_day' ] = 'on';
                        $booking_settings[ 'booking_enable_time' ] = '';
                    } else if ( 'date_time' == $temp ) {
                        $booking_settings[ 'booking_enable_multiple_day' ] = '';
                        $booking_settings[ 'booking_enable_time' ] = 'on';
                    } else if ( 'only_day' == $temp ) {
                        $booking_settings[ 'booking_enable_multiple_day' ] = '';
                        $booking_settings[ 'booking_enable_time' ] = '';
                    }
                    break;
                case '_bkap_enable_specific': 
                    $booking_settings[ 'booking_specific_booking' ] = $temp;
                    break;
                case '_bkap_enable_recurring':
                    $booking_settings[ 'booking_recurring_booking' ] = $temp;
                    break;
                case '_bkap_specific_dates':
                    $booking_settings[ 'booking_specific_date' ] = $temp;
                    break;
                case '_bkap_recurring_weekdays':
                    $booking_settings[ 'booking_recurring' ] = $temp;
                    break;
                case '_bkap_recurring_lockout':
                    $booking_settings[ 'booking_recurring_lockout' ] = $temp;
                    break;
                case '_bkap_enable_inline':
                    $booking_settings[ 'enable_inline_calendar' ] = $temp;
                    break;
                case '_bkap_purchase_wo_date':
                    $booking_settings[ 'booking_purchase_without_date' ] = $temp;
                    break;
                case '_bkap_requires_confirmation':
                    $booking_settings[ 'booking_confirmation' ] = $temp;
                    break;
                case '_bkap_product_holidays':
                    $booking_settings[ 'booking_product_holiday' ] = $temp;
                    break;
                case '_bkap_multiple_day_min':
                    if ( $temp > 0 ) {
                        $booking_settings[ 'enable_minimum_day_booking_multiple' ] = 'on';
                        $booking_settings[ 'booking_minimum_number_days_multiple' ] = $temp;
                    } else {
                        $booking_settings[ 'enable_minimum_day_booking_multiple' ] = '';
                        $booking_settings[ 'booking_minimum_number_days_multiple' ] = 0;
                    }
                    break;
                case '_bkap_multiple_day_max':
                        $booking_settings[ 'booking_maximum_number_days_multiple' ] = $temp;
                    break;
                case '_bkap_date_lockout':
                    $booking_settings[ 'booking_date_lockout' ] = $temp;
                    break;
                case '_bkap_custom_ranges':
                    $booking_settings[ 'booking_date_range' ] = $temp;
                    break;
                case '_bkap_abp':
                    $booking_settings[ 'booking_minimum_number_days' ] = $temp;
                    break;
                case '_bkap_max_bookable_days':
                    $booking_settings[ 'booking_maximum_number_days' ] = $temp;
                    break;
                case '_bkap_time_settings':
                    $booking_settings[ 'booking_time_settings' ] = $temp;
                    break;
                case '_bkap_fixed_blocks':
             //       if ( isset( $temp ) && $temp != '' ) {
                        $booking_settings[ 'booking_fixed_block_enable' ] = $temp;
             //       }
                    break;
                case '_bkap_price_ranges':
              //      if ( isset( $temp ) && $temp != '' ) {
                        $booking_settings[ 'booking_block_price_enable' ] = $temp;
              //      }
                    break;
                case '_bkap_gcal_integration_mode':
                    $booking_settings[ 'product_sync_integration_mode' ] = $temp;
                    break;
                case '_bkap_gcal_key_file_name':
                    $booking_settings[ 'product_sync_key_file_name' ] = $temp;
                    break;
                case '_bkap_gcal_service_acc':
                    $booking_settings[ 'product_sync_service_acc_email_addr' ] = $temp;
                    break;
                case '_bkap_gcal_calendar_id':
                    $booking_settings[ 'product_sync_calendar_id' ] = $temp;
                    break;
                case '_bkap_enable_automated_mapping':
              //      if ( isset( $temp ) && $temp != '' ) {
                        $booking_settings[ 'enable_automated_mapping' ] = $temp;
              //      }
                    break;
                case '_bkap_default_variation':
                //    if ( isset( $temp ) && $temp > 0 ) {
                        $booking_settings[ 'gcal_default_variation' ] = $temp;
                //    }
                    break;
                case '_bkap_import_url':
                    $booking_settings[ 'ics_feed_url' ] = $temp;
                    break;
                default: 
                    break;
            }
        }
    }
    return $booking_settings;
}

/*
 * Creates bkap_booking posts for Order Items not having Booking details in post and post_meta
 * 
 * @param string $booking_plugin_version Plugin version
 * @since 4.2.0
 */
function bkap_420_update_settings( $limit_query ) {
    
    global $bkap_date_formats, $wpdb;
    $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
    $booking_obj = array();
    $update_stats = array();

    $order_migration_query = 
        "SELECT P1.ID, P2.booking_id, P3.order_item_id 
        FROM `" . $wpdb->prefix . "posts` AS P1 
        JOIN `" . $wpdb->prefix . "booking_order_history` AS P2 
        JOIN `" . $wpdb->prefix . "woocommerce_order_items` AS P3 
        WHERE P1.ID = P2.order_id AND P1.post_date > %s AND P1.ID = P3.order_id AND P3.order_item_type=%s $limit_query";

    $results_migration =   $wpdb->get_results ( $wpdb->prepare( $order_migration_query, '2017-01-01 00:00:00', 'line_item' ) );

    $item_count = $success_count = 0;
    $failed_items = array();

    foreach ( $results_migration as $results_value) {

        $order_item_id = (int)$results_value->order_item_id;
        $booking_exists = bkap_common::get_booking_id( $order_item_id );

        $product_item = new WC_Order_Item_Product($order_item_id);

        $product_id = $product_item->get_product_id( 'view' );
        $is_bookable = bkap_common::bkap_get_bookable_status( $product_id );
        
        if ( !$booking_exists && $is_bookable && isset( $product_item['_wapbk_booking_date'] ) ) {

            $item_count++;

            // default the variables
            $booking_obj['date'] = '';
            $booking_obj['hidden_date'] = '';
            $booking_obj['date_checkout'] = '';
            $booking_obj['hidden_date_checkout'] = '';
            $booking_obj['time_slot'] = '';
            $booking_obj['price'] = '';
            
            $booking_obj['date'] = date( $bkap_date_formats[$global_settings->booking_date_format], strtotime( $product_item['_wapbk_booking_date'] ) );
            $booking_obj['hidden_date'] = $product_item['_wapbk_booking_date'];
            
            if ( isset( $product_item['_wapbk_checkout_date'] ) ) {
                $booking_obj['date_checkout'] = date( $bkap_date_formats[$global_settings->booking_date_format], strtotime( $product_item['_wapbk_checkout_date'] ) );
                $booking_obj['hidden_date_checkout'] = $product_item['_wapbk_checkout_date'];
            }
            
            if ( isset( $product_item['_wapbk_time_slot'] ) ) {
                $booking_obj['time_slot'] = $product_item['_wapbk_time_slot'];
            }

            $booking_obj['price'] = $product_item->get_total( 'view' );

            $booking_post = bkap_checkout::bkap_create_booking_post( 
                $order_item_id, 
                $product_id, 
                $product_item->get_quantity( 'view' ), 
                $booking_obj, 
                $variation_id = '' );

            if ( $booking_post !== false ) {
                $success_count++;
            }else {
                array_push( $failed_items, $order_item_id );
            }

            $update_stats = array(
                'item_count' => $item_count,
                'post_count' => $success_count,
                'failed_count' => count( $failed_items ),
                'failed_items' => $failed_items
            );
        }
    }

    return $update_stats;
    /*if ( $item_count === $success_count ) {
        $_status = 'success';
    } else {
        $_status = 'fail';
    }

    if ( isset( $update_stats ) && isset( $_status ) ) {
        
        update_option( 'bkap_420_update_stats', $update_stats );
        update_option( 'bkap_420_update_db_status', $_status );
    }*/
    
    /*bkap_420_gcal_meta();
    bkap_420_add_gcal_posts();*/
}

function bkap_manual_db_update_v420() {
    
    $booking_plugin_version = '4.2.0';
    
    bkap_db_420_1();

    $return_status = get_option( 'bkap_420_update_db_status' );
    $gcal_status = get_option( 'bkap_420_update_gcal_status' );
    
    if ( 'success' == $return_status && 'success' === $gcal_status ) {
    

    } else {
        update_option( 'bkap_420_manual_update_count', '1' );
    }

    echo $return_status;

    die();
}

/**
 * This function will add a post meta record  '_bkap_gcal_event_uid' 
 * for all booking posts. This is being done to ensure orders created
 * from imported events are displayed in the new View Bookings page. 
 * @since 4.2.0
 */
function bkap_420_gcal_meta() {

//     $args = array( 'post_type' => 'bkap_booking',
//         'posts_per_page' => -1
//     );

//     $bookings = get_posts( $args );
    
    $post_status = 'all';
    $bookings = bkap_common::bkap_get_bookings( $post_status );

    $gcal_meta = 0;
    
    if( is_array( $bookings ) && count( $bookings ) > 0 ) {
        foreach( $bookings as $booking ) {

            $booking_id = $booking->id;

            $item_id = get_post_meta( $booking_id, '_bkap_order_item_id', true );

            $gcal_event = wc_get_order_item_meta( $item_id, '_gcal_event_reference' );

            if ( isset( $gcal_event ) && count( $gcal_event ) > 1 ) {
                $uid = $gcal_event->uid;
                update_post_meta( $booking_id, '_bkap_gcal_event_uid', $uid );
                $gcal_meta++;
            }
        }
    }
    
    wp_reset_postdata();
    update_option( 'bkap_420_update_gcal_meta', $gcal_meta );
    
}


function bkap_420_gcal_batch_size(){

    global $wpdb;

    $events_query = "SELECT COUNT(*), option_name, option_value FROM `" . $wpdb->prefix . "options`
                        WHERE option_name LIKE %s";

    $events_list = $wpdb->get_var ( $wpdb->prepare( $events_query, 'bkap_imported_events_%' ) );

    $batch_size = ceil( $events_list / 500 );

    return $batch_size;
}

/**
 * This function will add a google calendar import post 
 * for all the existing unmapped events. This is being done 
 * to ensure that the existing unmapped events are 
 * visible on the new Import page after upgrading the plugin.
 * @since 4.2.0
 */
function bkap_420_add_gcal_posts( $limit_query ) {

    if ( strtolower( get_option( 'bkap_420_update_gcal_status' ) ) == 'success' )
        return;
    
    global $wpdb;

    // get all the option recordss
    $events_query = "SELECT option_name, option_value FROM `" . $wpdb->prefix . "options`
                        WHERE option_name LIKE %s $limit_query";

    $events_list = $wpdb->get_results( $wpdb->prepare( $events_query, 'bkap_imported_events_%' ) );

    $event_count = 0;
    $success_count = 0;
    $failed_items = array();
    
    $update_stats = create_posts( $events_list, $failed_items, $event_count, $success_count );
    
    $event_count = $update_stats[ 'item_count' ];
    $success_count = $update_stats[ 'post_count' ];
    $failed_items = $update_stats[ 'failed_items' ];
    
    // check if the tour operators addon is active and there are imported events for the operators
    /*if ( function_exists( 'is_bkap_tours_active' ) && is_bkap_tours_active() ) {
        $tours_query = "SELECT option_name, option_value FROM `" . $wpdb->prefix . "options`
                            WHERE option_name LIKE %s";
    
        $tours_list = $wpdb->get_results( $wpdb->prepare( $tours_query, 'tours_imported_events_%' ) );
    
        if ( is_array( $tours_query ) && count( $tours_list ) > 0 ) {
    
            $update_stats = create_posts( $tours_list, $failed_items, $event_count, $success_count );
    
            $event_count = $update_stats[ 'item_count' ];
            $success_count = $update_stats[ 'post_count' ];
            $failed_items = $update_stats[ 'failed_items' ];
    
        }
    }*/
    
    /*if ( $event_count === $success_count ) {
        $_status = 'success';
    } else {
        $_status = 'fail';
    }*/
    
    return $update_stats;
    /*if ( isset( $update_stats ) && isset( $_status ) ) {
        update_option( 'bkap_420_gcal_update_stats', $update_stats );
        //update_option( 'bkap_420_update_gcal_status', $_status );
    }*/
    
}

function bkap_420_add_gcal_tour_posts( $limit_query ) {

    global $wpdb;

    // check if the tour operators addon is active and there are imported events for the operators
    if ( function_exists( 'is_bkap_tours_active' ) && is_bkap_tours_active() ) {
        $tours_query = "SELECT option_name, option_value FROM `" . $wpdb->prefix . "options`
                            WHERE option_name LIKE %s $limit_query";
    
        $tours_list = $wpdb->get_results( $wpdb->prepare( $tours_query, 'tours_imported_events_%' ) );
    
        if ( is_array( $tours_list ) && count( $tours_list ) > 0 ) {

            $event_count = 0;
            $success_count = 0;
            $failed_items = array();
    
            $update_stats = create_posts( $tours_list, $failed_items, $event_count, $success_count );
    
            $event_count = $update_stats[ 'item_count' ];
            $success_count = $update_stats[ 'post_count' ];
            $failed_items = $update_stats[ 'failed_items' ];
            
            return $update_stats;
        }
    }
}

function create_posts( $events_list, $failed_items, $event_count, $success_count ) {

    global $wpdb;

    $update_stats = array();

    if ( is_array( $events_list ) && count( $events_list ) > 0 ) {
        foreach( $events_list as $event_details ) {
            $event_value = json_decode( $event_details->option_value );

            $check_uid = "SELECT post_id FROM `" . $wpdb->prefix . "postmeta`
                            WHERE meta_key = %s
                            AND meta_value = %s";

            $uid_res = $wpdb->get_col( $wpdb->prepare( $check_uid, '_bkap_uid', $event_value->uid ) );
            // continue only if the event has not been mapped yet
            if ( isset( $uid_res ) && count( $uid_res ) > 0 )
                continue;

            $event_count++;

            $event_name = $event_details->option_name;

            $user_id = 1;
            $exp_option = explode( '_', $event_name );
            
            if ( $exp_option[0] === 'tours' ) {
                $user_id = trim( $exp_option[3] );
            }
            $gcal_event = bkap_calendar_sync::bkap_create_gcal_event_post( $event_value, 0 , 'bkap-unmapped', $event_name, $user_id );

            if ( $gcal_event !== false ) {
                $success_count++;
            }else {
                array_push( $failed_items, $event_details->option_name );
            }

            $update_stats = array(
                'item_count' => $event_count,
                'post_count' => $success_count,
                'failed_count' => count( $failed_items ),
                'failed_items' => $failed_items
            );

        }
    }

    return( $update_stats );

}

function bkap_db_420_1( $count ) {
    
    $new_step = ( ( $count - 1 ) * 500 );

    $db_stats = array();

    // save the existing stats
    $db_stats = get_option( 'bkap_420_update_stats' );
    
    $sql = "LIMIT $new_step, 500";        
    $updated_stats = bkap_420_update_settings( $sql );

    $db_stats[] = $updated_stats;
    
    // get the update status
    $_status = get_option( 'bkap_420_update_db_status' );
    
    update_option( 'bkap_420_update_stats', $db_stats );
    
    return $_status; 
    
}

function bkap_db_420_2( $count ) {
    
    $new_step = ( ( $count - 1 ) * 500 ) + 1;

    $db_stats = array();
    $tour_db_stats = array();

    // save the existing stats
    $db_stats = get_option( 'bkap_420_gcal_update_stats' );
    $tour_db_stats = get_option( 'bkap_420_gcal_update_tour_stats' );
    
    $sql = "LIMIT $new_step, 500";        
    $updated_stats = bkap_420_add_gcal_posts( $sql );

    if ( function_exists( 'is_bkap_tours_active' ) && is_bkap_tours_active() ) {
        $updated_tour_stats = bkap_420_add_gcal_tour_posts( $sql );
    }

    $db_stats[] = $updated_stats;
    $tour_db_stats[] = $updated_tour_stats;

    // get the update status
    $_status = get_option( 'bkap_420_update_gcal_status' );
    
    update_option( 'bkap_420_gcal_update_stats', $db_stats );
    update_option( 'bkap_420_gcal_update_tour_stats', $tour_db_stats );
    
    return $_status; 
    
}

function bkap_get_db_count() {
    
    global $wpdb;
    
    $order_migration_query =
    "SELECT COUNT(*), P1.ID, P2.booking_id, P3.order_item_id
        FROM `" . $wpdb->prefix . "posts` AS P1
        JOIN `" . $wpdb->prefix . "booking_order_history` AS P2
        JOIN `" . $wpdb->prefix . "woocommerce_order_items` AS P3
        WHERE P1.ID = P2.order_id AND P1.post_date > %s AND P1.ID = P3.order_id AND P3.order_item_type=%s";

    $migration_count =   ( $wpdb->get_var ( $wpdb->prepare( $order_migration_query, '2017-01-01 00:00:00', 'line_item' ) ) );
    
    $loops = ceil( $migration_count / 500 );
//    $loops = 4;
    return $loops;
    
}

/**
 * This function will sort the array by ascending order. Key of the array should be date in d-m-Y format
 *
 * @since 4.4.0
 */

function bkap_orderby_date_key( $a1, $b1 ) {
    
    $format = 'd-m-Y';

    $a = strtotime( date_format(DateTime::createFromFormat( $format, $a1 ), 'Y-m-d H:i:s' ) );
    $b = strtotime( date_format(DateTime::createFromFormat( $format, $b1 ), 'Y-m-d H:i:s' ) );
    
    if ( $a == $b ) {
        return 0;
    } else if ( $a > $b ) {
        return 1;
    } else {
        return -1;
    }
}

?>