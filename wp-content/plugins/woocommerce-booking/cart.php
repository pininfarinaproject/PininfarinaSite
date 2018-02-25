<?php
//if(!class_exists('woocommerce_booking')){
//   die();
//}

include_once( 'bkap-common.php' );

class bkap_cart{
	
	/**********************************************************
	 * This function adjust the extra prices for the product 
	 * with the price calculated from booking plugin.
	*********************************************************/
	public static function bkap_add_cart_item( $cart_item ) {
	
		// Adjust price if addons are set
		global $wpdb;
		
		if ( isset( $cart_item['bkap_booking'] ) ) :
			
			$extra_cost = 0;
				
			foreach ( $cart_item['bkap_booking'] as $addon ) :
		
				if ( isset( $addon['price'] ) && is_numeric( $addon['price'] ) ) $extra_cost += $addon['price'];
	
			endforeach;
			
			$duplicate_of    =   bkap_common::bkap_get_product_id( $cart_item['product_id'] );
			$product         =   wc_get_product( $cart_item['product_id'] );
		
			$product_type    =   $product->get_type();
				
			$variation_id    =   0;
			
			if ( $product_type == 'variable' ) {
				$variation_id = $cart_item['variation_id'];
			}
			
			if ( ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ) {
			    $price       =   bkap_common::bkap_get_price( $cart_item['product_id'], $variation_id, $product_type );
                $extra_cost  =   $extra_cost - $price;
                $cart_item['data']->adjust_price( $extra_cost );
			} else {
			    $cart_item['data']->set_price( $extra_cost );
			}
			
			$cart_item = apply_filters( 'bkap_modify_product_price', $cart_item );
				
		endif;
		
		return $cart_item;
	}
		
	/*************************************************
	 * This function returns the cart_item_meta with 
	 * the booking details of the product when add to 
	 * cart button is clicked.
	*****************************************************/
	public static function bkap_add_cart_item_data( $cart_item_meta, $product_id ){
		global $wpdb;

		$duplicate_of = bkap_common::bkap_get_product_id( $product_id );
	
		$is_bookable = bkap_common::bkap_get_bookable_status( $duplicate_of );

		$allow_bookings = apply_filters( 'bkap_cart_allow_add_bookings', true, $cart_item_meta );

		if ( $is_bookable && ( !array_key_exists( 'bundled_by', $cart_item_meta ) ) && $allow_bookings ) {
    		if ( isset( $_POST['booking_calender'] ) ) {
    			$date_disp = $_POST['booking_calender'];
    		}
    		
    		if ( isset($_POST['time_slot'] ) ) {
    			$time_disp = $_POST['time_slot'];
    		}
    		
    		if ( isset( $_POST['wapbk_hidden_date'] ) ) {
    			$hidden_date = $_POST['wapbk_hidden_date'];
    		}
    		
    		$resource_id = 0;
    		if( isset( $_POST['bkap_front_resource_selection'] ) ){
    		    $resource_id = $_POST['bkap_front_resource_selection'];
    		}
    	
    		$booking_settings     = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );			
    		$product              = wc_get_product( $product_id );	
    		$product_type         = $product->get_type();
    		
    		// Initialize the variables
    		$date_disp_checkout = '';
    		$hidden_date_checkout = '';
    		$diff_days = 1;
    		$block_info = "";

    		if ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) {
    			
    			if( isset( $_POST['block_option'] ) && $_POST['block_option'] != "" ) {
    				$block_info = $_POST['block_option'];
    			}
    		    if ( isset( $_POST['booking_calender_checkout'] ) ) {
    				$date_disp_checkout = $_POST['booking_calender_checkout'];
    			}
    			
    			if ( isset( $_POST['wapbk_hidden_date_checkout'] ) ) {
    				$hidden_date_checkout = $_POST['wapbk_hidden_date_checkout'];
    			}
    			
    			if ( isset( $_POST['wapbk_diff_days'] ) ) {
    				$diff_days = $_POST['wapbk_diff_days'];
    			}
    			
    			$variation_id = 0;
    			
    			if ( $product_type == 'variable' && isset( $_POST['variation_id'] ) ) {
    				$variation_id = $_POST['variation_id'];
    			}
    			
    		} 
	
    		$global_settings  =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
    		
    		if ( isset( $date_disp ) && $date_disp != "" ) {
    			$cart_arr = array();
    			
    			if ( isset( $date_disp ) ) {
    				$cart_arr['date'] = $date_disp;
    			}
    			
    			if ( isset( $time_disp ) ) {
    				$cart_arr['time_slot'] = $time_disp;
    			}
    			
    			if ( isset($hidden_date ) ) {
    				$cart_arr['hidden_date'] = $hidden_date;
    			}
    			
    			$cart_arr['date_checkout'] = $date_disp_checkout;
    			$cart_arr['hidden_date_checkout'] = $hidden_date_checkout;
    			$cart_arr['resource_id'] = $resource_id;

    			$cart_arr['fixed_block'] = $block_info;
    			
    			if ( isset( $_POST['variation_id'] ) ) {
    				$variation_id = $_POST['variation_id'];
    			}
    			else {
    				$variation_id = '0';
    			}
                
    			if ( ! isset( $cart_item_meta[ 'bundled_by' ] ) ) {
        			$price = 0;
        			if ( isset( $_POST[ 'bkap_price_charged' ] ) && is_numeric( $_POST[ 'bkap_price_charged' ] ) ) {
                        $price = $_POST[ 'bkap_price_charged' ];
                    } else { // It's a string when the product is a grouped product
                        	
                        $price_array = explode( ',', $_POST[ 'bkap_price_charged' ] );
                        foreach ($price_array as $array_k => $array_v ) {
                            $per_product_array = explode( ':', $array_v );
                    
                            if ( $per_product_array[ 0 ] == $duplicate_of ) {
                                $price = $per_product_array[ 1 ];
                                $child_product_id = $per_product_array[ 0 ];
                                break;
                            }
                        }
                    }
        			$gf_options_price = 0;
        			$wpa_options_price = 0;
        				
        			// GF Compatibility
        			if ( isset( $_POST[ 'bkap_gf_options_total' ] ) && $_POST[ 'bkap_gf_options_total' ] != 0 ) {
        			    $gf_options_price = $_POST[ 'bkap_gf_options_total' ];
        			}
        				
        			//Woo Product Addons compatibility
        			$wpa_diff = 1;
        			if( isset( $global_settings->woo_product_addon_price ) && $global_settings->woo_product_addon_price == 'on' ) {
        				$wpa_diff = $diff_days;
        			}

        			// Set the price per quantity as Woocommerce multiplies the price set with the qty
        			$product_quantity = 1;
                    if ( isset( $_POST[ 'quantity' ] ) && is_array( $_POST[ 'quantity' ] ) ) {
        			    $product_quantity = $_POST[ 'quantity' ][$product_id];
			            //$final_price = $final_price / $qty_value;
        			} else if ( isset( $_POST[ 'quantity' ] ) && $_POST[ 'quantity' ] > 1 ) {
                        //$final_price = $final_price / $_POST[ 'quantity' ];
                        $product_quantity = $_POST[ 'quantity' ];
                        $cart_arr[ 'qty' ] = $_POST[ 'quantity' ];
        			}

        			$wpa_options_price = bkap_common::woo_product_addons_compatibility_cart( $wpa_diff, $cart_item_meta, $product_quantity );

        			$gf_options_price = apply_filters( 'bkap_modify_cart_gf_prices', $gf_options_price, $product_quantity );

        			$final_price = ( $price + $gf_options_price + $wpa_options_price ) / $product_quantity;

        			$cart_arr[ 'price' ] = $final_price;
    			} else if ( isset( $cart_item_meta[ 'bundled_by' ] ) && isset( $cart_item_meta[ 'bundled_item_id' ] ) ) {

    				$bundled_item_obj = wc_pb_get_bundled_item($cart_item_meta[ 'bundled_item_id' ]);
    				if ( $bundled_item_obj->is_priced_individually() ) {
    					$cart_arr[ 'price' ] = $bundled_item_obj->get_price() * $diff_days;

    				}
    			}
    			$cart_arr  =   (array) apply_filters('bkap_addon_add_cart_item_data', $cart_arr, $product_id, $variation_id,$cart_item_meta );
    			
    			//Added to add the selected currency on the product page from WPML Multi currency dropdown
    			if ( function_exists( 'icl_object_id' ) ) {
    			    global $woocommerce_wpml, $woocommerce;
    			    if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
    			        $client_currency = $woocommerce->session->get( 'client_currency' );
    			        $cart_arr[ 'wcml_currency' ] = $client_currency;
    			    }
    			}
		
    			$cart_item_meta['bkap_booking'][] =   $cart_arr;
    		}
		}else if ( array_key_exists( 'bundled_by', $cart_item_meta ) && $cart_item_meta['bundled_by'] !== '' ) {

			$cart_arr = array();

			if ( isset( WC()->cart->cart_contents[$cart_item_meta['bundled_by']]['bkap_booking'] ) ) {
				$bundle_parent_booking = WC()->cart->cart_contents[$cart_item_meta['bundled_by']]['bkap_booking'][0];
			}

			$bundle_stamp = $cart_item_meta['stamp'][$cart_item_meta['bundled_item_id']];
			$bundle_item = wc_pb_get_bundled_item($cart_item_meta['bundled_item_id']);

			if ( $bundle_item->is_priced_individually() ) {
				if ( isset( $bundle_stamp['variation_id'] ) && $bundle_stamp['variation_id'] !== '' ) {
					$bundle_variation = wc_get_product( $bundle_stamp['variation_id'] );
					$cart_arr['price'] = $bundle_variation->get_price();
					if ( isset( $bundle_stamp['discount'] ) && $bundle_stamp['discount'] !== '' ) {
						$cart_arr['price'] = $cart_arr['price'] - ( $cart_arr['price'] * $bundle_stamp['discount']/100 );
					}

					if ( isset( $_POST['wapbk_diff_days'] ) ) {
						$cart_arr['price'] = $cart_arr['price'] * $_POST['wapbk_diff_days'];
					}
				}else{
					if ( isset( $_POST['wapbk_diff_days'] ) && $_POST['wapbk_diff_days'] > 0 ) {
						$cart_arr['price'] = $bundle_item->get_price() * $_POST['wapbk_diff_days'];
					}else{
						$cart_arr['price'] = $bundle_item->get_price();
					}
				}
			}
			if ( $is_bookable && isset( $bundle_parent_booking ) ) {
				$cart_arr['date'] = $bundle_parent_booking['date'];
				$cart_arr['hidden_date'] = $bundle_parent_booking['hidden_date'];
				$cart_arr['date_checkout'] = $bundle_parent_booking['date_checkout'];
				$cart_arr['hidden_date_checkout'] = $bundle_parent_booking['hidden_date_checkout'];

				if ( isset( $bundle_parent_booking['time_slot'] ) ) {
					$cart_arr['time_slot'] = $bundle_parent_booking['time_slot'];
				}
			}

			if ( isset( $cart_arr['date'] ) || isset( $cart_arr['price'] ) ) {
				$cart_item_meta['bkap_booking'][] = $cart_arr;
			}
		}else {

			$cart_item_meta = apply_filters( 'bkap_cart_modify_meta', $cart_item_meta );
		}

		return $cart_item_meta;
	}

	/**********************************************
	 *  This function adjust the prices calculated 
	 *  from the plugin in the cart session.
	************************************************/
	public static function bkap_get_cart_item_from_session( $cart_item, $values ) {
		global $wpdb;

		$duplicate_of = bkap_common::bkap_get_product_id( $cart_item[ 'product_id' ] );
		if ( isset( $values[ 'bkap_booking' ] ) ) :
            // Added to calculate the price for each product in cart based on the selected currency on the product page from WPML Multi currency dropdown
		    if ( function_exists( 'icl_object_id' ) ) {
		        global $woocommerce_wpml, $woocommerce;
		        if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
		            $client_currency = $woocommerce->session->get( 'client_currency' );
		            foreach( $values[ 'bkap_booking' ] as $bkap_key => $bkap_value ) {
		                if( $bkap_value[ 'wcml_currency' ] != $client_currency ) {
                            if( $bkap_value[ 'wcml_currency' ] == get_option( 'woocommerce_currency' ) ) {
                                $final_price = $bkap_value[ 'price' ];
                            } else {
                                if ( WCML_VERSION >= '3.8' ) {
                                    $currencies = $woocommerce_wpml->multi_currency->get_client_currency();
                                } else {
                                    $currencies = $woocommerce_wpml->multi_currency_support->get_client_currency();
                                }
                                $rate = $currencies[ $bkap_value[ 'wcml_currency' ] ][ 'rate' ];
                                $final_price = $bkap_value[ 'price' ]/ $rate;
                            }
                            $raw_price = apply_filters( 'wcml_raw_price_amount', $final_price );
                            $bkap_value[ 'price' ] = $raw_price;
                            $bkap_value[ 'wcml_currency' ] = $client_currency;                        
                            $values[ 'bkap_booking' ][ $bkap_key ] = $bkap_value;
		                }
		            }
		        }
		    }
		    
		    $cart_item[ 'bkap_booking' ] =  $values[ 'bkap_booking' ];
			$booking_settings =  get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
	
			$cart_item = bkap_cart::bkap_add_cart_item( $cart_item );
			
			$cart_item = (array) apply_filters( 'bkap_get_cart_item_from_session', $cart_item, $values );
		endif;
		return $cart_item;
	}
	
	/**************************************
     * This function displays the Booking 
     * details on cart page, checkout page.
    ************************************/
	public static function bkap_get_item_data_booking( $other_data, $cart_item ) {
		global $wpdb;
			
		if ( isset( $cart_item['bkap_booking'] ) ) :
			$duplicate_of = bkap_common::bkap_get_product_id( $cart_item['product_id'] );
			
			foreach ( $cart_item['bkap_booking'] as $booking ) :
			
				$name = __( ( '' !== get_option( 'book_item-cart-date' ) ? get_option( 'book_item-cart-date' ) : 'Start Date' ), "woocommerce-booking" );
			
				if ( isset( $booking['date'] ) && $booking['date'] != "" ) {
					$other_data[] = array( 'name'    => $name,
							               'display' => $booking['date']
					);
				}
				
				if ( isset( $booking['date_checkout'] ) && $booking['date_checkout'] != "" ) {
					$booking_settings = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );

					if ( $booking_settings['booking_enable_multiple_day'] == 'on' ) {
					    $name_checkout    =   __( ( '' !== get_option( 'checkout_item-cart-date' ) ? get_option( 'checkout_item-cart-date' ) : 'End Date' ), "woocommerce-booking" );
						$other_data[]     =   array('name'    => $name_checkout,
								                    'display' => $booking['date_checkout']
						);						
					}
				}
				
				if ( isset( $booking['time_slot'] ) && $booking['time_slot'] != "" ) {
					$saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					
					if ( isset( $saved_settings ) ){
						$time_format = $saved_settings->booking_time_format;
					}else {
						$time_format = "12";
					}
					
					$time_slot_to_display = $booking['time_slot'];
				
					if ( $time_format == '12' ) {
						$time_exploded    =   explode( "-", $time_slot_to_display );
						$from_time        =   date( 'h:i A', strtotime( $time_exploded[0] ) );
						
						if ( isset( $time_exploded[1] ) ) { 
							$to_time = date( 'h:i A', strtotime( $time_exploded[1] ) );
						}
						else {
							$to_time = "";
						}
						
						if ( $to_time != "" ) {
							$time_slot_to_display = $from_time.' - '.$to_time;
						}
						else {
							$time_slot_to_display = $from_time;
						}
					}
					
					$type_of_slot = apply_filters( 'bkap_slot_type', $cart_item['product_id'] );
					
					if( $type_of_slot != 'multiple' ) {
						$name         =   __( ( '' !== get_option( 'book_item-cart-time' ) ? get_option( 'book_item-cart-time' ) : 'Booking Time' ), "woocommerce-booking" );
						$other_data[] =   array('name'    => $name,
							                    'display' => $time_slot_to_display
						);
					}
				}
				
				if ( isset( $booking['resource_id'] ) && $booking['resource_id'] != 0 ) {
				
				    $name 			= 	Class_Bkap_Product_Resource::bkap_get_resource_label( $cart_item['product_id'] ) ;
				
				    $name = ( "" != $name ) ? $name : __( 'Resource Type', 'wocommerce-booking' );
				
				    $other_data[] 	=	array(  'name'    => $name,
				        'display' => get_the_title( $booking['resource_id'] )
				    );
				}
				
				$other_data = apply_filters( 'bkap_get_item_data', $other_data, $cart_item );
			endforeach; 
			
		endif;
		
		return $other_data;
	}

	/**
	 * This function modifies the product price in WooCommerce Cart Widget
	 * 
	 * @param array $fragments WooCommerce Cart fragements that display data
	 * 
	 * @return array Cart Fragments
	 */
	public static function bkap_woo_cart_widget_subtotal( $fragments ) {

		global $woocommerce;

		$price = 0;
		foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

		    if ( isset( $values['bkap_booking'] ) ) {
				$booking = $values['bkap_booking'];
			}

			if ( isset( $booking[0]['price'] ) && $booking[0]['price'] != 0 ) {
				$price += ( $booking[0]['price'] ) * $values['quantity'];
			} else {

			    if ( $values['variation_id'] == '' ) {
					$product_type = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $values['data']->product_type : $values['data']->get_type();
				} else {
					$product_type = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $values['data']->parent->product_type : $values['data']->parent->get_type();
				}

				$variation_id = 0;

				if ( $product_type == 'variable' ) {
					$variation_id = $values['variation_id'];
				}

				$book_price = bkap_common::bkap_get_price( $values['product_id'], $variation_id, $product_type );

				$price += $book_price * $values['quantity'];
			}
		}

		$saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );

		if ( isset( $saved_settings->enable_rounding ) && $saved_settings->enable_rounding == "on" ) {
			$total_price = round( $price );
		} else {
			$total_price = number_format( $price, 2 );
		}

		ob_start();
		$currency_symbol = get_woocommerce_currency_symbol();
		print( '<p class="total"><strong>Subtotal:</strong> <span class="amount">'.$currency_symbol.$total_price.'</span></p>' );

		$fragments['p.total'] = ob_get_clean();

		return $fragments;
	}

} 
?>