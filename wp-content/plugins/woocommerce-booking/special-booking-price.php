<?php
// 	bkap_get_per_night_price -- multiple days booking
//  bkap_call_addon_price    -- For single day booking
/**
* bkap_special_booking_price class
**/
if ( !class_exists( 'bkap_special_booking_price' ) ) {
    class bkap_special_booking_price {
        public function __construct() {
      		    
			// Display updated price on the product page
			add_action( 'bkap_display_updated_addon_price',         array( &$this, 'special_booking_display_updated_price' ), 2, 3 );
				
			// Display the price div if different prices are enabled for time slots
			add_action( 'bkap_display_price_div',                    array( &$this, 'special_booking_price_div' ), 21, 1 );
				
			// Display the multiple days specia  booking price on the product page
			add_action( 'bkap_display_multiple_day_updated_price',   array( &$this, 'bkap_special_booking_show_multiple_updated_price' ), 7, 8 );
        }		
				
		/**
		* This function is used to add/update special booking
		*/
        function bkap_save_special_booking_price( $post_id, $recurring_prices = array(), $specific_prices = array() ) {
            global $wpdb;
				
            // get the existing record
			$booking_special_prices = get_post_meta( $post_id, '_bkap_special_price', true );
			if( is_array( $booking_special_prices ) && count( $booking_special_prices ) > 0 ) {
			    $cnt = count( $booking_special_prices );
            } else {
				$booking_special_prices = array();
                $cnt = 0; 
            }
            /** this is being done only to make sure the code is compatible with PHP versions lower than 5.3
             * it should be removed when we decide to upgrade everything to 5.3+
             */
            // loop through the existing records, note down the weekday/date and the key
            $special_prices = array();
            if ( is_array( $booking_special_prices ) && count( $booking_special_prices ) > 0 ) {
                foreach( $booking_special_prices as $special_key => $special_value ) {
                    $weekday_set = $special_value[ 'booking_special_weekday' ];
                    $date_set = $special_value[ 'booking_special_date' ];
                    if ( $weekday_set != "" ) {
                        $special_prices[ $weekday_set ] = $special_key;
                    } else if ( $date_set != "" ) {
                        $special_prices[ $date_set ] = $special_key;
                    }
                }
            }
            /*****/ 
            // run a loop through all weekdays
			$max_key_value = 0;

			if ( is_array( $special_prices ) && count( $special_prices ) > 0 ) {
				$max_key_value = max( $special_prices );
			}
			
            if ( is_array( $recurring_prices ) && count( $recurring_prices ) > 0 ) {
    			foreach( $recurring_prices as $w_key => $w_price ) {
    			    // check if record exists for the given weekday
    			    if ( $cnt > 0 ) {
    			        // if record is present, we need the key
    			     /* commented as USE is available only for PHP 5.3+
                        $key = key( array_filter( $booking_special_prices, function( $item ) use( $w_key ) {
    			            return isset( $item[ 'booking_special_weekday' ] ) && $w_key == $item[ 'booking_special_weekday' ];
    			        }) ); */
    			        
    			        if ( array_key_exists( $w_key, $special_prices ) ) {
    			            $key = $special_prices[ $w_key ];
    			        }
    			    }
    			    // if key is found, update the existing record
    			    if ( isset( $key ) && is_numeric( $key ) && $key >= 0 ) {
    			        $booking_special_prices[ $key ][ 'booking_special_weekday' ]  = $w_key;
    			        $booking_special_prices[ $key ][ 'booking_special_date' ]     = '';
    			        $booking_special_prices[ $key ][ 'booking_special_price' ]    = $w_price;
    			        $key = ''; // reset the key
    			    } else { // add a new one
                        $max_key_value++;
    			        $booking_special_prices[ $max_key_value ][ 'booking_special_weekday' ]  = $w_key;
    			        $booking_special_prices[ $max_key_value ][ 'booking_special_date' ]     = '';
    			        $booking_special_prices[ $max_key_value ][ 'booking_special_price' ]    = $w_price;
    			        $cnt++; // increment the count
    			    }
    			}
            }
            
			// loop through all specific dates
			if ( is_array( $specific_prices ) && count( $specific_prices ) > 0 ) {
    			foreach( $specific_prices as $w_key => $w_price ) {
    
    				$w_key = date( 'Y-m-d', strtotime( $w_key ) );
    			    // check if record exists for the given date
    			    if ( $cnt > 0 ) {
    			        // if record is present, we need the key
    			     /* commented as USE is available only for PHP 5.3+
    			        $key = key( array_filter( $booking_special_prices, function( $item ) use( $w_key ) {
    			            return isset( $item[ 'booking_special_date' ] ) && $w_key == $item[ 'booking_special_date' ];
    			        }) ); */
    			        if ( array_key_exists( $w_key, $special_prices ) ) {
    			            $key = $special_prices[ $w_key ];
    			        }
    			    }
    			    // if key found, update existing record
    			    if ( isset( $key ) && is_numeric( $key ) && $key >= 0 ) {
    			        $booking_special_prices[ $key ][ 'booking_special_weekday' ]  = '';
    			        $booking_special_prices[ $key ][ 'booking_special_date' ]     = $w_key;
    			        $booking_special_prices[ $key ][ 'booking_special_price' ]    = $w_price;
    			        $key = ''; // reset the key
    			    } else { // add a new record
    			        $max_key_value++;
    			        $booking_special_prices[ $max_key_value ][ 'booking_special_weekday' ]  = '';
    			        $booking_special_prices[ $max_key_value ][ 'booking_special_date' ]     = $w_key;
    			        $booking_special_prices[ $max_key_value ][ 'booking_special_price' ]    = $w_price;
    			        $cnt++; // increment the count
    			    }
    			}
			}
						
			// unset any records for recurring weekdays where price may have been reset to blanks
			if ( is_array( $special_prices ) && count( $special_prices ) > 0 ) {
    			foreach( $special_prices as $s_key => $s_value ) {
    			     
    			    if ( substr( $s_key, 0, 7 ) == 'booking' ) {
    			        if ( ! array_key_exists( $s_key, $recurring_prices ) ) {
    			            unset( $booking_special_prices[ $s_value ] );
    		            } 
    			    } else { // it's a specific date
    	                $key_check = date( 'j-n-Y', strtotime( $s_key ) );
    	                if ( ! array_key_exists( $key_check, $specific_prices ) ) {
    	                    unset( $booking_special_prices[ $s_value ] );
    	                }
    		        }
    			}
			}
			
            // update the record in the DB
			update_post_meta( $post_id, '_bkap_special_price', $booking_special_prices );
            
        }
			
		/**
		 * This function is used to display price of product 
		 */
		function special_booking_price_div() {
			/*if( has_filter( 'bkap_show_addon_price' ) ) {
				$show_price = apply_filters( 'bkap_show_addon_price', '' );
            } else {
			    $show_price = 'show';
            }
			$print_code = '<div id=\"show_addon_price\" name=\"show_addon_price\" class=\"show_addon_price\" style=\"display:'.$show_price.';\"><\/div>';
			print('<script type="text/javascript">
				if (jQuery("#show_addon_price").length == 0) {
						//document.write("'.$print_code.'");
						document.body.innerHTML += "'.$print_code.'";
				} 
            </script>');*/
		}
			
		/**
		 * This function is used to updated price of product
		 */			
		function special_booking_display_updated_price( $product_id, $booking_date, $variation_id ){ 
            $_product       = wc_get_product( $product_id );
			$product_type   = $_product->get_type();
			if ( $product_type == 'grouped' ) {
                $special_price_present = 'NO';
				$currency_symbol       = get_woocommerce_currency_symbol();
				$has_children          = '';
				$price_str             = '';
				$raw_price_str         = '';
				$price_arr             = array();
					
				if ( $_product->has_child() ) {
					$has_children = "yes";
					$child_ids    = $_product->get_children();
				}
					
				$quantity_grp_str  = $_POST[ 'quantity' ];
				
				$quantity_array    = explode( ",", $quantity_grp_str );
                $i                 = 0;
					
				foreach ( $child_ids as $k => $v ) {
					$final_price              = 0;
					$child_product            = wc_get_product( $v );
					$product_type_child       = $child_product->get_type();
					$product_price            = bkap_common::bkap_get_price( $v, 0, 'simple' );
					$special_booking_price    = $this->get_price( $v, $booking_date );
					
					if ( isset( $special_booking_price ) && $special_booking_price != 0 && $special_booking_price != '' ) {
						$special_price_present   = 'YES';
						$final_price             = $special_booking_price * trim( $quantity_array[ $i ] );
						$raw_price               = $final_price;
						
						if ( function_exists( 'icl_object_id' ) ) {
						    global $woocommerce_wpml;
						    // Multi currency is enabled
						    if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
						        $custom_post = bkap_common::bkap_get_custom_post( $v, 0, $product_type );
						        if( $custom_post == 0 ) {
						            $raw_price = apply_filters( 'wcml_raw_price_amount', $final_price );
						        }
						    } 
						} 
						$wc_price_args = bkap_common::get_currency_args();
					    $final_price = wc_price( $raw_price, $wc_price_args );
						
						$raw_price_str .= $v . ":" . $raw_price . ",";
						$price_str .= $child_product->get_title() . ": " . $final_price . "<br>";
					} else {
						$final_price = $product_price * trim( $quantity_array[$i] );
						$raw_price = $final_price;
                        if ( function_exists( 'icl_object_id' ) ) {
                            global $woocommerce_wpml;
						    // Multi currency is enabled
						    if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
						        $custom_post = bkap_common::bkap_get_custom_post( $v, 0, $product_type );
						        if( $custom_post == 0 ) {
                                    $raw_price = apply_filters( 'wcml_raw_price_amount', $final_price );
						        }
						    } 
						} 
						$wc_price_args = bkap_common::get_currency_args();
					    $final_price = wc_price( $raw_price, $wc_price_args );
							
						$raw_price_str .= $v . ":" . $raw_price . ",";
						$price_str .= $child_product->get_title() . ": " . $final_price . "<br>";
					}
					$i++;
				}
				if ( isset( $price_str ) && $price_str != '' ) {
					$special_booking_price = $price_str;
					if ( $special_price_present == 'YES' ) {
						$_POST[ 'special_booking_price' ] = $special_booking_price;
						$_POST[ 'grouped_raw_price' ] = $raw_price_str;
					}
				}
			} else {
				$special_booking_price = $this->get_price( $product_id, $booking_date );
				if ( isset( $special_booking_price ) && $special_booking_price != '' && $special_booking_price != 0 ) {
					$_POST[ 'special_booking_price' ] = $special_booking_price;
				}
			}		
		}
			
		function get_price( $product_id, $booking_date ) {

			$booking_special_prices = get_post_meta( $product_id, '_bkap_special_price', true );
			$special_booking_price  = 0;
			if ( is_array( $booking_special_prices ) && count( $booking_special_prices ) > 0 ) {
                foreach ( $booking_special_prices as $key => $values ){
			        list( $year, $month, $day ) = explode( "-", $booking_date );
			        	
			        if ( $values[ 'booking_special_date' ] == $booking_date ){
			            $special_booking_price = $values[ 'booking_special_price' ];
			            break;
			        }
			    }
			    
			    if ( $special_booking_price == 0 ) { // specific date price was not found
    			    foreach ( $booking_special_prices as $key => $values ) {
    			        
    					list( $year, $month, $day ) =   explode( "-", $booking_date );
    					$booking_day              =   date( "w", mktime( 0, 0, 0, $month, $day, $year ) );
    					$day_name = "booking_weekday_$booking_day";
    					
    					if ( isset( $values[ 'booking_special_weekday' ] ) && $day_name == $values[ 'booking_special_weekday' ] ){
    						$special_booking_price = $values[ 'booking_special_price' ];
    						break;
    					}
    				}
			    }
			}
			return $special_booking_price;
		}
			
		function add_cart_item( $cart_item ) {
			global $wpdb;
			$product_type = 'simple';
			if ( $cart_item[ 'variation_id' ] != '' ) {
				$product_type = 'variable';
			}
			
			$price = bkap_common::bkap_get_price( $cart_item['product_id'], $cart_item['variation_id'], $product_type );
			// Adjust price if addons are set
			if ( isset( $cart_item['bkap_booking'] ) ) {
				$extra_cost = 0;
				foreach ( $cart_item['bkap_booking'] as $addon ) {
					if ( isset( $addon['price'] ) && $addon['price'] > 0 ) {
						$extra_cost += $addon['price'];
					}
				}
				
				$extra_cost = $extra_cost - $price;
				$cart_item['data']->adjust_price( $extra_cost );
			}
	
			return $cart_item;
		}
			
		/**
		 * This function is used to updated price of product for Multiple dates
		 */
		
		function bkap_special_booking_show_multiple_updated_price( $product_id, $product_type, $variation_id_to_fetch, $checkin_date, $checkout_date, $gf_options = 0, $resource_id, $currency_selected ) {
            $global_settings    = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			$number_of_days     = strtotime( $checkout_date ) - strtotime( $checkin_date );
			$number             = floor( $number_of_days/( 60 * 60 * 24 ) );
				
			if ( $number == 0 ) {
                $number = 1;
            }
			$booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
				
			if ( function_exists( 'is_bkap_rental_active' ) && is_bkap_rental_active() && ( isset( $booking_settings ) && isset( $booking_settings['booking_charge_per_day'] ) &&  $booking_settings['booking_charge_per_day'] == 'on' ) ){
				if ( strtotime( $checkout_date ) > strtotime( $checkin_date) ) {
					$number++;
				}
			}
			
			$booking_special_prices = get_post_meta( $product_id, '_bkap_special_price', true );
					
			if ( isset( $_POST[ 'price' ] ) && ( isset( $booking_settings[ 'booking_fixed_block_enable' ] ) && $booking_settings[ 'booking_fixed_block_enable' ] == 'booking_fixed_block_enable' ) ) {
				$price = $_POST[ 'price' ];
				// Divide the block price by number of nights so per day price is calculated for the block and then added up to form the total
				if( is_array( $booking_special_prices ) && count( $booking_special_prices ) > 0 ) {
					$price = $price / $number;
				}
				$number = 1;
			} else if ( isset( $_POST[ 'price' ] ) && ( isset( $booking_settings[ 'booking_block_price_enable' ] ) && $booking_settings[ 'booking_block_price_enable' ] == 'booking_block_price_enable' ) ) { 
                $str_pos = strpos( $_POST[ 'price' ], '-' );
				if ( isset( $str_pos ) && $str_pos != '' ) {
					$price_type   =  explode( "-", $_POST['price'] );

					$decimal_separator  = wc_get_price_decimal_separator();
					$thousand_separator = wc_get_price_thousand_separator();

					if ( '' != $thousand_separator ) {
						$price_with_thousand_separator_removed = str_replace( $thousand_separator, '', $price_type[0] );
					} else {
						$price_with_thousand_separator_removed = $price_type[0];
					}

					if ( '.' != $decimal_separator ) {
						
						$price_type[0] = str_replace ( $decimal_separator, '.', $price_with_thousand_separator_removed ) ;
					} else {
						$price_type[0] = $price_with_thousand_separator_removed;
					}

					$price         = $price_type[0] / $number;
                } else {
					$price = $_POST['price'];
                }
			} else {
				$price = bkap_common::bkap_get_price( $product_id, $variation_id_to_fetch, $product_type,$checkin_date, $checkout_date );
			}

			$weekdays                           = bkap_get_book_arrays( 'bkap_weekdays' );
			$special_multiple_day_booking_price = 0;
			$startDate                          = $checkin_date;
            $check_special_price_flag           = "FALSE";
            if ( is_array( $booking_special_prices ) && count( $booking_special_prices ) > 0 ){
				// If rental is active, then we have chk price for all selected days
				if ( function_exists( 'is_bkap_rental_active' ) && is_bkap_rental_active() && ( isset( $booking_settings ) && isset( $booking_settings[ 'booking_charge_per_day' ] ) && $booking_settings[ 'booking_charge_per_day' ] == 'on' ) ) { 
					$endDate = strtotime( $checkout_date ) + ( 60 * 24 * 24 );
				} else { 
					$endDate = strtotime( $checkout_date ); 
				}
					
                while ( strtotime( $startDate ) < $endDate ) {
                    
                    $check_special_price_flag  =  "TRUE";
				    $special_price = $price; 
					foreach ( $booking_special_prices as $key => $values ){
						list( $year, $month, $day ) = explode( "-", $startDate );
						$booking_day =  date("w", mktime(0, 0, 0, $month, $day, $year));
						$startDate1 = "booking_weekday_$booking_day";
						
						if ( isset( $values[ 'booking_special_weekday' ] ) &&  $startDate1 == $values[ 'booking_special_weekday' ] ) {
    						$special_price = $values[ 'booking_special_price' ];
						}
					}
					
					foreach( $booking_special_prices as $key => $values ) {
						if( $values[ 'booking_special_date' ] == $startDate ) {
							$special_price = $values[ 'booking_special_price' ];
						}
					}
					$special_multiple_day_booking_price  += $special_price;
					$startDate                            = date ( "Y-m-d", strtotime( "+1 day", strtotime( $startDate ) ) );
                }
                
		if ( $check_special_price_flag == "FALSE" ){
                    $special_multiple_day_booking_price  = $price;
                }
                
				// Don't divide price by no. of days if Fixed block 
				if ( ( isset( $booking_settings[ 'booking_fixed_block_enable' ] ) && $booking_settings[ 'booking_fixed_block_enable' ] == 'booking_fixed_block_enable' ) ) {  
					$special_multiple_day_booking_price = $special_multiple_day_booking_price;
				} else { 
					$special_multiple_day_booking_price = $special_multiple_day_booking_price / $number;
				}
				
				$_SESSION[ 'special_multiple_day_booking_price' ]    = $_POST[ 'special_multiple_day_booking_price' ]  = $special_multiple_day_booking_price;
				$_SESSION[ 'booking_multiple_days_count' ]           = $_POST[ 'booking_multiple_days_count' ]         = $number;
			} else {	
				$special_multiple_day_booking_price                = $price;
				$_SESSION[ 'special_multiple_day_booking_price' ]    = $_SESSION[ 'booking_multiple_days_count' ] = '';
			}

			$resource_price = 0;
			if( $resource_id > 0 ){
			
			    $resource 		= new BKAP_Product_Resource( $resource_id, $product_id );
			    $resource_price = $resource->get_base_cost();
			
			    if ( isset( $global_settings->resource_price_per_day ) && $global_settings->resource_price_per_day == "on" ){
			        $resource_price = $resource_price * $number;
			    }
			    if ( isset( $_POST[ 'quantity' ] ) && $_POST[ 'quantity' ] > 0 ) {
			        $resource_price = $resource_price * $_POST[ 'quantity' ];
			    }
			}		 
					
			if ( function_exists( 'is_bkap_deposits_active' ) && is_bkap_deposits_active() || function_exists( 'is_bkap_seasonal_active' ) && is_bkap_seasonal_active() ) {
			    if ( isset( $special_multiple_day_booking_price ) && $special_multiple_day_booking_price != '' ) {
					$_POST['price'] = $special_multiple_day_booking_price + $resource_price;
				} else {
					$error_message = __( "Please select an option.", "woocommerce-booking" );
					print( 'jQuery( "#bkap_price" ).html( "' . addslashes( $error_message ) . '");' );
					die();
				}
			} else {
				// the filter is applied to make the plugin wpml multi currency compatible
				// as a prt of that, we need to ensure that the final price is sent to the filter
				if ( isset( $number ) && $number > 1 ) {
					$special_multiple_day_booking_price = $special_multiple_day_booking_price * $number;
				}
				if ( isset( $_POST[ 'quantity' ] ) && $_POST[ 'quantity' ] > 0 ) {
					$special_multiple_day_booking_price = $special_multiple_day_booking_price * $_POST[ 'quantity' ];
				}
				if ( isset( $global_settings->enable_rounding ) && $global_settings->enable_rounding == "on" ) {
					$special_multiple_day_booking_price = round( $special_multiple_day_booking_price );
				}
				
				$special_multiple_day_booking_price += $resource_price;
					
				// Save the actual Bookable amount, as a raw amount
				// If Multi currency is enabled, convert the amount before saving it
				$total_price = $special_multiple_day_booking_price;
				if ( function_exists( 'icl_object_id' ) ) {
				    $custom_post = bkap_common::bkap_get_custom_post( $product_id, 0, $product_type );
				    if( $custom_post == 1 ) {
				        $total_price = $special_multiple_day_booking_price;
				    } else if( $custom_post == 0 ) {
				        $total_price = apply_filters( 'wcml_raw_price_amount', $special_multiple_day_booking_price );
				    }
				}

				print( 'jQuery( "#total_price_calculated" ).val(' . $total_price . ');' );
				// save the price in a hidden field to be used later
				print( 'jQuery( "#bkap_price_charged" ).val(' . $total_price . ');' );
					
				// if gf options are enabled, multiply with the number of nights based on the settings
				if ( isset( $gf_options ) && $gf_options > 0 ) {
				    $gf_total = $gf_options;
				    if ( isset( $global_settings->woo_gf_product_addon_option_price ) && 'on' == $global_settings->woo_gf_product_addon_option_price ) {
				        if ( isset( $_POST[ 'diff_days' ] ) && $_POST[ 'diff_days' ] > 1 ) { // the diff days passed in the ajax need to be checked as the variable $diff_days is set to 1 for fixed blocks
				            $gf_total = $gf_options * $_POST[ 'diff_days' ];
				        }
				    }
				    $total_price += $gf_total;
				}
				// format the price	
				$wc_price_args = bkap_common::get_currency_args();
				$formatted_price = wc_price( $total_price, $wc_price_args );

				if ( 'bundle' == $product_type ) {
				    $bundle_price = bkap_common::get_bundle_price( $total_price, $product_id, $variation_id_to_fetch );

				    /*if ( isset( $_POST[ 'diff_days' ] ) && $_POST[ 'diff_days' ] > 1 ) {
			            $bundle_price = $bundle_price * $_POST[ 'diff_days' ];
			        }*/

				    $formatted_price = wc_price( $bundle_price, $wc_price_args );
				}

				if ( 'composite' === $product_type ) {
					$composite_price = bkap_common::get_composite_price( $total_price, $product_id, $variation_id_to_fetch );

					$formatted_price = wc_price( $composite_price, $wc_price_args );
				}
					
				$display_price = get_option( 'book_price-label' ) . ' ' . $formatted_price;
				// display the price on the front end product page
				print( 'jQuery( "#bkap_price" ).html( "' . addslashes( $display_price ) . '");' );
				die();
			}
		}						
	} // EOF Class
} // EOF if class exist
$bkap_special_booking_price = new bkap_special_booking_price();
?>