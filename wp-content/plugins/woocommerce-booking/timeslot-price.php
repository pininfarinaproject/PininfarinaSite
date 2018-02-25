<?php
if ( !class_exists( 'bkap_timeslot_price' ) ) {
    class bkap_timeslot_price {
        public function __construct() {
			// Print hidden fields
			add_action( 'bkap_print_hidden_fields',          array( &$this, 'timeslot_hidden_fields' ), 10, 1 );
			// Display updated price on the product page
			add_action( 'bkap_display_updated_addon_price',  array( &$this, 'timeslot_display_updated_price' ), 3, 5 );
		}
		
		function timeslot_hidden_fields( $product_id ) {
			$variable_timeslot_price = bkap_timeslot_price::get_timeslot_variable_price( $product_id );
			print( '<input type="hidden" id="wapbk_hidden_variable_timeslot_price" name="wapbk_hidden_variable_timeslot_price" value="' . $variable_timeslot_price . '">' );
		}
		
		function get_timeslot_variable_price( $product_id ) {
			$slot_price          = array();
			$booking_settings    = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
			if ( isset( $booking_settings[ 'booking_enable_time' ] ) && $booking_settings[ 'booking_enable_time' ] == 'on' ) {
				$time_slot_arr = ( isset( $booking_settings[ 'booking_time_settings' ] ) ) ? $booking_settings[ 'booking_time_settings' ] : array();
				
				if ( is_array( $time_slot_arr ) && count( $time_slot_arr ) > 0 ) {
    				foreach ( $time_slot_arr as $key => $value ) {
    				    foreach ( $value as $k => $v ) {
    				        if ( isset( $v[ 'slot_price' ] ) && $v[ 'slot_price' ] > 0 ) {
    							$slot_price[] = $v[ 'slot_price' ];
    						}
    					}
    				}
				}
			}
			
			$variable_timeslot_price = 'no';
			if( count( $slot_price ) > 0 ) {
				$variable_timeslot_price = 'yes';
			}
			return $variable_timeslot_price;
		}
		
		function timeslot_display_updated_price( $product_id, $booking_date, $variation_id, $gf_options = 0, $resource_id = 0 ) {
			$global_settings     = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			// product type
			$_product            = wc_get_product( $product_id );
			$product_type        = $_product->get_type();
			// Time slot
			$time_slot           = '';
			
			if ( isset( $_POST[ 'timeslot_value' ] ) ) {
				$time_slot = $_POST[ 'timeslot_value' ];
			}
			
			// If resource is attached to the product then calculating resource price and adding it to final price.
			$resource_price = 0;
			if ( $resource_id != 0 ) {
			    $resource 		= new BKAP_Product_Resource( $resource_id, $product_id );
			    $resource_price = $resource->get_base_cost();
			}
			
			if ( $product_type == 'grouped' ) {
				$currency_symbol    = get_woocommerce_currency_symbol();
				$has_children       = $price_str = "";
				$price_arr          = array();
				
				if ( $_product->has_child() ) {
					$has_children = "yes";
					$child_ids = $_product->get_children();
				}
				
				$quantity_grp_str = $_POST[ 'quantity' ];
				$quantity_array = explode( ",", $quantity_grp_str );
				$i              = 0;
				$raw_price_str  = '';
				foreach ( $child_ids as $k => $v ) {
					$child_product         = wc_get_product( $v );
					$product_type_child    = $child_product->get_type();
					$time_slot_price       = $this->get_price( $v, 0, $product_type_child, $booking_date, $time_slot, 'product' );
					$final_price           = $time_slot_price * $quantity_array[ $i ];
					$raw_price             = $final_price;
					
					if ( function_exists( 'icl_object_id' ) ) {
					    global $woocommerce_wpml;
					    // Multi currency is enabled
					    if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
					        $custom_post = bkap_common::bkap_get_custom_post( $v, 0, $product_type );
					        if( $custom_post == 0 ) {
					            $raw_price = apply_filters( 'wcml_raw_price_amount', $final_price );
					            $final_price = $raw_price;
					        }
					    } 
					} 
				    $wc_price_args = bkap_common::get_currency_args();
					$final_price = wc_price( $final_price, $wc_price_args );
					
					$raw_price_str .= $v . ":" . $raw_price . ",";
					$price_str .= $child_product->get_title() . ": " . $final_price . "<br>";
					$i++;
				}
				$time_slot_price = $price_str;
			} else {
			    $time_slot_price = "";
				if( $time_slot != "" ){
					$time_slot_price = $this->get_price( $product_id, $variation_id, $product_type, $booking_date, $time_slot, 'product' );	
				}
			}
			
			if ( ( $time_slot_price == "" || $time_slot_price == 0 ) && ( isset( $_POST[ 'special_booking_price' ] ) && $_POST[ 'special_booking_price' ] != "" ) ){
				$time_slot_price = $_POST[ 'special_booking_price' ];
				$raw_price_str = $_POST[ 'grouped_raw_price' ];
			} elseif ( $time_slot_price == "" || $time_slot_price == 0 ) {
				$time_slot_price = bkap_common::bkap_get_price( $product_id, $variation_id, $product_type );
			}						

			$time_slot_price = $time_slot_price + $resource_price;
			
			if( ( function_exists( 'is_bkap_seasonal_active' ) && is_bkap_seasonal_active() ) || 
				( function_exists( 'is_bkap_deposits_active' ) && is_bkap_deposits_active() ) || 
				( function_exists( 'is_bkap_multi_time_active' ) && is_bkap_multi_time_active() ) ) {

				$_POST[ 'price' ] = $time_slot_price;
			} else {
			    if ( $product_type != 'grouped' ) {
    			    if ( isset( $_POST[ 'quantity' ] ) && $_POST[ 'quantity' ] != 0 ) {
    					$time_slot_price = $time_slot_price * $_POST[ 'quantity' ];
    				}
    				
    				if ( isset( $global_settings->enable_rounding ) && $global_settings->enable_rounding == "on" ) {
    					$time_slot_price = round( $time_slot_price );
    				}
    				
    				// Save the actual Bookable amount, as a raw amount
    				// If Multi currency is enabled, convert the amount before saving it
    				$total_price = $time_slot_price;
    				if ( function_exists( 'icl_object_id' ) ) {
    				    $custom_post = bkap_common::bkap_get_custom_post( $product_id, $variation_id, $product_type );
    				    if( $custom_post == 1 ) {
    				        $total_price = $time_slot_price;
    				    } else if( $custom_post == 0 ) {
    				        $total_price = apply_filters( 'wcml_raw_price_amount', $time_slot_price );
    				    }
    				}
    				
    				print( 'jQuery( "#total_price_calculated" ).val(' . $total_price . ');' );
    				// save the price in a hidden field to be used later
    				print( 'jQuery( "#bkap_price_charged" ).val(' . $total_price. ');' );

    				// if gf options are enable .. commented since we no longer need to display price below Booking Box
    				/*if ( isset( $gf_options ) && $gf_options > 0 ) {
    				    $total_price += $gf_options;
    				}*/
    				
    				// format the price
    				$wc_price_args = bkap_common::get_currency_args();
    				$formatted_price = wc_price( $total_price, $wc_price_args );
    				
			    } else {
			        $formatted_price = $time_slot_price;
			        print( 'jQuery( "#total_price_calculated" ).val("' . addslashes( $raw_price_str ) . '");' );
			        // save the price in a hidden field to be used later
			        print( 'jQuery( "#bkap_price_charged" ).val("' . addslashes( $raw_price_str ) . '");' );
			    } 

			    if ( isset( $total_price ) && 'bundle' == $product_type ) {
				    $bundle_price = bkap_common::get_bundle_price( $total_price, $product_id, $variation_id );

				    $formatted_price = wc_price( $bundle_price, $wc_price_args );
				}

				if ( 'composite' === $product_type ) {
					$composite_price = bkap_common::get_composite_price( $total_price, $product_id, $variation_id );

					$formatted_price = wc_price( $composite_price, $wc_price_args );
				}

				// display the price on the front end product page
				$display_price = get_option( 'book_price-label' ) . ' ' . $formatted_price;
			    print( 'jQuery( "#bkap_price" ).html( "' . addslashes( $display_price ) . '");' );

				die();
			}
		}
		
/*		function add_cart_item( $cart_item ) {
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
				};
				$extra_cost = $extra_cost - $price;
				$cart_item[ 'data' ]->adjust_price( $extra_cost );
			}		
			
			return $cart_item;
		} */
		
		function get_price( $product_id, $variation_id, $product_type, $booking_date, $time_slot, $called_from ) {
			// set the slot price as the product base price
			$time_slot_price = $time_slot_price_total = 0;
			
			if( isset( $_POST[ 'special_booking_price' ] ) && $_POST[ 'special_booking_price' ] != "" && $_POST[ 'special_booking_price' ] != 0 ) {
				$time_slot_price = $_POST[ 'special_booking_price' ];
				// if bundled product then we need to add the individually charged child prices as well
				$_product = wc_get_product( $product_id );
				$prd_type = $_product->get_type();
				if ( isset( $prd_type ) && 'bundle' == $prd_type ) {
				    $bundle_total = bkap_common::bkap_get_price( $product_id, $variation_id, $prd_type );
				    // product price
				    $regular_price = get_post_meta( $product_id, '_regular_price', true );
				    $sale_price = get_post_meta( $product_id, '_sale_price', true );

				    if( !isset( $sale_price ) || $sale_price == '' || $sale_price == 0 ) {
				        $prd_price          =   $regular_price;
				    } else {
				        $prd_price          =   $sale_price;
				    }
				
				    // now subtract the product price and add the special price
				    $bundle_total -= $prd_price;
				    $time_slot_price += $bundle_total;
				
				}
			} else {
				$time_slot_price = bkap_common::bkap_get_price( $product_id, $variation_id, $product_type, $booking_date );
			}

			$booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
			if ( $time_slot != '' ) {
				// Check if multiple time slots are enabled
				$seperator_pos = strpos( $time_slot, "," );
				
				if ( isset( $seperator_pos ) && $seperator_pos != "" ) {
					$time_slot_array = explode( ",", $time_slot );
				} else {
					$time_slot_array   = array();
					$time_slot_array[] = $time_slot;
				}
				
				for ( $i = 0; $i < count( $time_slot_array ); $i++ ) {
					// split the time slot into from and to time
					$timeslot_explode      = explode( '-', $time_slot_array[ $i ] );
					$timeslot_explode[0]   = date( 'G:i', strtotime( $timeslot_explode[0] ) );
					
					if ( isset( $timeslot_explode[1] ) && $timeslot_explode[1] != '' ) {
						$timeslot_explode[1] = date( 'G:i', strtotime( $timeslot_explode[1] ) );
					}
					// split frm hrs in hrs and min
					$from_hrs  = explode( ':', $timeslot_explode[0] );
					// similarly for to time, but first default it to 0:00, so it works for open ended time slots as well
					$to_hrs    = array(
							'0' => '0',
							'1' => '00');
					
					if ( isset( $timeslot_explode[1] ) && $timeslot_explode[1] != '' ) {
						$to_hrs = explode( ':', $timeslot_explode[1] );
					}
					
					if ( isset( $booking_settings[ 'booking_time_settings' ] ) && count( $booking_settings[ 'booking_time_settings' ] ) > 0 ) {						
					    
						// match the booking date as specific overrides recurring
						$booking_date_to_check = date( 'j-n-Y', strtotime( $booking_date ) );
						if ( array_key_exists( $booking_date_to_check, $booking_settings[ 'booking_time_settings' ] ) ) {
						    foreach ($booking_settings[ 'booking_time_settings' ] as $key => $value ) {
						        if ( $key == $booking_date_to_check ) {
						            foreach ( $value as $k => $v ) {
						                $price = 0;
						                // match the time slot
						                if ( ( intval( $from_hrs[0] ) == intval( $v['from_slot_hrs'] ) ) && ( intval( $from_hrs[1] ) == intval( $v['from_slot_min'] ) ) && ( intval( $to_hrs[0] ) == intval( $v['to_slot_hrs'] ) ) && ( intval( $to_hrs[1] ) == intval( $v['to_slot_min'] ) ) ) {
						                    // fetch and save the price
						                    if ( isset( $v[ 'slot_price' ] ) && $v[ 'slot_price' ] != '' ) {
						                        $price = $v[ 'slot_price' ];
						                        if ( isset( $called_from ) && $called_from == 'cart' ) {
						                            $price = apply_filters( 'wcml_raw_price_amount', $v[ 'slot_price' ] );
						                        }
						                        $time_slot_price_total += $price;
						                    } else {
						                        $time_slot_price_total += $time_slot_price;
						                    }
						                }
						            }
						        }
						    }
						} else {
							// Get the weekday
							$weekday            =   date( 'w', strtotime( $booking_date ) );
							$booking_weekday    =   'booking_weekday_' . $weekday;
							foreach ($booking_settings[ 'booking_time_settings' ] as $key => $value ) {
    							//match the weekday
    							if ( $key == $booking_weekday ) {
								    foreach ( $value as $k => $v ) {
										$price = 0;
										// match the time slot
										if ( ( intval( $from_hrs[0] ) == intval( $v[ 'from_slot_hrs' ] ) ) && ( intval( $from_hrs[1] ) == intval( $v[ 'from_slot_min' ] ) ) && ( intval( $to_hrs[0] ) == intval( $v[ 'to_slot_hrs' ] ) ) && ( intval( $to_hrs[1] ) == intval( $v[ 'to_slot_min' ] ) ) ) {
											// fetch and save the price
											if ( isset( $v[ 'slot_price' ] ) && $v[ 'slot_price' ] != '' ) {
												$price = $v['slot_price'];
												if ( isset( $called_from ) && $called_from == 'cart' ) {
													$price = apply_filters( 'wcml_raw_price_amount', $v['slot_price'] );
												}
												$time_slot_price_total += $price;
											} else {
												$time_slot_price_total += $time_slot_price;
											}
										}
									}
								}
							}
						}
					}
				}
				if ( $time_slot_price_total != 0 ) {
					$time_slot_price = $time_slot_price_total;
					$time_slot_price = $time_slot_price / count($time_slot_array);
				}
			}
			return $time_slot_price;
		}
	}
}
$bkap_timeslot_price = new bkap_timeslot_price();