<?php
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class bkap_common{
    
    /**
     * Return min date based on the Advance Booking Period.
     * @param $current_time in UNIX TimeStamp
     * @return $min_date date
     */
    public static function bkap_min_date_based_on_AdvanceBookingPeriod( $product_id, $current_time ) {
    
        $bkap_abp = get_post_meta( $product_id, '_bkap_abp', true );        
        $bkap_abp = ( isset( $bkap_abp ) && $bkap_abp != "" ) ? $bkap_abp : 0;
        
        // Convert the advance period to seconds and add it to the current time
        $advance_seconds      =   $bkap_abp *60 *60;
        $cut_off_timestamp    =   $current_time + $advance_seconds;
        $cut_off_date         =   date( "d-m-Y", $cut_off_timestamp );
        $min_date             =   date( "j-n-Y", strtotime( $cut_off_date ) );
    
        return $min_date;
    }
    
    /**
     * Return true/false based on the timeslot available for selected date.
     * @param $product_id and $start_date
     * @return boolean
     */
    
    public static function bkap_check_timeslot_for_weekday( $product_id, $start_date ) {
    
        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
    
        $start_weekday 		     = 	date( 'w', strtotime( $start_date ) );
        $start_booking_weekday   = 	'booking_weekday_' . $start_weekday;

        if ( is_array( $booking_settings['booking_time_settings'] ) && array_key_exists( $start_booking_weekday, $booking_settings['booking_time_settings'] ) ) {
            return true;
        }else if ( is_array( $booking_settings['booking_time_settings'] ) && 
            array_key_exists( date( 'j-n-Y', strtotime( $start_date ) ), $booking_settings['booking_time_settings'] ) ) {
            return true;
        }
    
        return false;
    
    }
    
    /**
     * Return function name to be executed when multiple time slots are enabled.
     * 
     * This function returns the function name to display the timeslots on the 
     * frontend if type of timeslot is Multiple for multiple time slots addon.
     * 
     * @return str
     */
    public static function bkap_ajax_on_select_date( $product_id ) {
        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
        
        if( isset( $booking_settings['booking_enable_multiple_time'] ) && $booking_settings['booking_enable_multiple_time'] == "multiple" && function_exists('is_bkap_multi_time_active') && is_bkap_multi_time_active() ) {
            return 'multiple_time';
        }
    }

    /**
     * Return an array of dates that fall in a date range
     * 
     * This function returns an array of dates that falls
     * in a date range in the d-n-Y format.
     * 
     * @param $StartDate d-n-Y format
     * $EndDate d-n-Y format
     * 
     * @return $Days array
     */
    public static function bkap_get_betweendays( $StartDate, $EndDate, $format = 'd-n-Y'  ) {
        $Days[]                   =   $StartDate;
        $CurrentDate              =   $StartDate;
            
        $CurrentDate_timestamp    =   strtotime($CurrentDate);
        $EndDate_timestamp        =   strtotime($EndDate);
        
        if( $CurrentDate_timestamp != $EndDate_timestamp )
        {
            while( $CurrentDate_timestamp < $EndDate_timestamp )
            {
                $CurrentDate            =   date( $format, strtotime( "+1 day", strtotime( $CurrentDate ) ) );
                $CurrentDate_timestamp  =   $CurrentDate_timestamp + 86400;
                $Days[]                 =   $CurrentDate;
            }
            array_pop( $Days );
        }
        return $Days;
    }
    
    /**
     * Return an array of dates that fall in a date range
     *
     * This function returns an array of dates that falls
     * in a date range in the d-n-Y format including the end date if the flat charge per day is enable.
     *
     * @param $StartDate d-n-Y format
     * $EndDate d-n-Y format
     *
     * @return $Days array
     */
    public static function bkap_get_betweendays_when_flat( $StartDate, $EndDate, $pro_id, $format = 'd-n-Y' ) {
        $Days[]                   =   $StartDate;
        $CurrentDate              =   $StartDate;
        $booking_settings         =   get_post_meta( $pro_id, 'woocommerce_booking_settings', true );
         
         
        $CurrentDate_timestamp    =   strtotime($CurrentDate);
        $EndDate_timestamp        =   strtotime($EndDate);
    
        if( $CurrentDate_timestamp != $EndDate_timestamp )
        {
             
            while( $CurrentDate_timestamp < $EndDate_timestamp )
            {
                $CurrentDate            =   date( $format, strtotime( "+1 day", strtotime( $CurrentDate ) ) );
                $CurrentDate_timestamp  =   $CurrentDate_timestamp + 86400;
                $Days[]                 =   $CurrentDate;
            }
        }
        return $Days;
    }
    
    /**
     * Send the Base language product ID
     * 
     * This function has been written as a part of making the Booking plugin
     * compatible with WPML. It returns the base language Product ID when WPML
     * is enabled. 
     * 
     * @param $product_id int
     * @return $base_product_id int
     */
    public static function bkap_get_product_id( $product_id ) {
        $base_product_id = $product_id;
        // If WPML is enabled, the make sure that the base language product ID is used to calculate the availability
        if ( function_exists( 'icl_object_id' ) ) {
            global $sitepress;
            global $polylang;
             
            if( isset( $polylang ) ){
                $default_lang = pll_current_language();
            }else{
                $default_lang = $sitepress->get_default_language();
            }
            
            $base_product_id = icl_object_id( $product_id, 'product', false, $default_lang );
            // The base product ID is blanks when the product is being created.
            if (! isset( $base_product_id ) || ( isset( $base_product_id ) && $base_product_id == '' ) ) {
                $base_product_id = $product_id;
            }
        } 
        return $base_product_id;
    }

    /**
     * Send the Base language Variation ID
     * 
     * This function has been written as a part of making the Booking plugin
     * compatible with WPML. It returns the base language Variation ID when WPML
     * is enabled. 
     * 
     * @param int $variation_id Variation ID
     * @return int Variation ID
     */
    public static function bkap_get_variation_id( $variation_id ) {
        $base_variation_id = $variation_id;
        // If WPML is enabled, the make sure that the base language product ID is used to calculate the availability
        if ( function_exists( 'icl_object_id' ) ) {
            global $sitepress;
            global $polylang;
             
            if( isset( $polylang ) ){
                $default_lang = pll_current_language();
            }else{
                $default_lang = $sitepress->get_default_language();
            }
            
            $base_variation_id = icl_object_id( $variation_id, 'product-variation', false, $default_lang );
            // The base variation_id is blanks when the variation is being created.
            if (! isset( $base_variation_id ) || ( isset( $base_variation_id ) && $base_variation_id == '' ) ) {
                $base_variation_id = $variation_id;
            }
        } 
        return $base_variation_id;
    }
    
    /**
     * Send return the selected setting of Multicurrency at product level from WPML plugin when it is active
     *
     * This function has been written as a part of making the Booking plugin
     * compatible with WPML. It returns the selected setting of Multicurrency at product level from WPML plugin when it is active
     *
     * @param $product_id int
     * @param $variation_id int
     * @param $product_type string
     * 
     * @return $custom_post int
     */
    public static function bkap_get_custom_post( $product_id, $variation_id, $product_type ) {
        if( $product_type == 'variable' ) {
            $custom_post = get_post_meta( $variation_id, '_wcml_custom_prices_status', true );
        } else if( $product_type == 'simple' || $product_type == 'grouped' ) {
            $custom_post = get_post_meta( $product_id, '_wcml_custom_prices_status', true );
        }
        if ( $custom_post == '' ) { // possible when the setting has been left to it's default value
            $custom_post = 0;
        }
        return $custom_post;
    }
    
    /**
     * Return Woocommerce price
     * 
     * This function returns the Woocommerce price applicable for a product.
     * 
     * @param $product_id int
     * $variation_id int
     * $product_type str
     * 
     * @return $price
     */
    public static function bkap_get_price( $product_id, $variation_id, $product_type, $check_in ='', $check_out = '' ) {
      
        global $wpdb;
        
        $price = 0;
        $wpml_multicurreny_enabled = 'no';
        if ( function_exists( 'icl_object_id' ) ) {
            global $woocommerce_wpml, $woocommerce;
            if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
                if ( $product_type == 'variable' ){
                    $custom_post = bkap_common::bkap_get_custom_post( $product_id, $variation_id, $product_type );
                    if( $custom_post == 1 ) {
                        $client_currency = $woocommerce->session->get( 'client_currency' );
                        if( $client_currency != '' && $client_currency != get_option( 'woocommerce_currency' ) ) {
                            $price = get_post_meta( $variation_id, '_price_' . $client_currency, true );
                            $wpml_multicurreny_enabled = 'yes';
                        } 
                    }
                } else if( $product_type == 'simple' || 'bundle' == $product_type ) {
                    $custom_post = bkap_common::bkap_get_custom_post( $product_id, $variation_id, $product_type );
                    if( $custom_post == 1 ) {
                        $client_currency = $woocommerce->session->get( 'client_currency' );
                        if( $client_currency != '' && $client_currency != get_option( 'woocommerce_currency' ) ) {
                            $price = get_post_meta( $product_id, '_price_' . $client_currency, true );
                            $wpml_multicurreny_enabled = 'yes';
                        }
                    }
                }
            }
        } 
        
        if( $wpml_multicurreny_enabled == 'no' ) {
            
            if ( $product_type == 'variable' ){
                
                $sale_price = get_post_meta( $variation_id, '_sale_price', true );
                
                $sale_price_dates_from = '';
                $sale_price_dates_to   = '';
                               
                if( !isset( $sale_price ) || $sale_price == '' || $sale_price == 0 ) {
                    $regular_price  =   get_post_meta( $variation_id, '_regular_price', true );
                    $price          =   $regular_price;
                } else {
                   
                    $sale_price_dates_from = get_post_meta( $variation_id, '_sale_price_dates_from', true );
                    $sale_price_dates_from_strtotime = ( isset( $sale_price_dates_from ) && "" != $sale_price_dates_from ) ? $sale_price_dates_from : '';
                    $sale_price_dates_to = get_post_meta( $variation_id, '_sale_price_dates_to', true ) ;
                    $sale_price_dates_to_strtotime = ( isset( $sale_price_dates_to ) && "" != $sale_price_dates_to ) ? $sale_price_dates_to : '';
               
               
                   if ( isset ( $sale_price_dates_from_strtotime ) && '' != $sale_price_dates_from_strtotime && isset ( $sale_price_dates_to_strtotime ) && '' != $sale_price_dates_to_strtotime ) {                      

                        if ( 
                            ( strtotime($check_in) >= $sale_price_dates_from_strtotime )
                            && ( strtotime($check_in) <= $sale_price_dates_to_strtotime ) ) 
                        {
                           
                            $price          =   $sale_price;

                        } else {
                            
                            $regular_price  =   get_post_meta( $variation_id, '_regular_price', true );
                            $price          =   $regular_price;
                         } 
                    } else {

                                  $price          =   $sale_price;
                    } 
                } 
            }
            elseif( $product_type == 'simple' || 'bundle' == $product_type ) {
                $product_obj = self::bkap_get_product( $product_id );

                $sale_price = get_post_meta( $product_id, '_sale_price', true );

                if ( $sale_price != "" ) {
                    
                    $sale_price_dates_from = '';
                    $sale_price_dates_to   = '';

                    $sale_price_dates_from = get_post_meta( $product_id, '_sale_price_dates_from', true );
                    $sale_price_dates_from_strtotime = ( isset( $sale_price_dates_from ) && "" != $sale_price_dates_from ) ? $sale_price_dates_from : '';
                           
                    $sale_price_dates_to = get_post_meta( $product_id, '_sale_price_dates_to', true ) ;
                    $sale_price_dates_to_strtotime = ( isset( $sale_price_dates_to ) && "" != $sale_price_dates_to ) ? $sale_price_dates_to : '';
                   
                    if ( isset ( $sale_price_dates_from_strtotime ) && '' != $sale_price_dates_from_strtotime && isset ( $sale_price_dates_to_strtotime ) && '' != $sale_price_dates_to_strtotime ) {
                      
                        if ( ( strtotime( $check_in ) >= $sale_price_dates_from_strtotime )
                            && ( strtotime( $check_in ) <= $sale_price_dates_to_strtotime ) ) {
                                                       
                            $price =   $product_obj->get_sale_price();                            

                        } else {
                            
                            $regular_price  =   get_post_meta( $product_id, '_regular_price', true );
                            $price =   $product_obj->get_regular_price();
                            
                        }
                      
                    } else {
                        $regular_price  =   get_post_meta( $product_id, '_sale_price', true );
                        $price =   $product_obj->get_sale_price();    
                    }
                }else{
                    $price = $product_obj->get_price();
                }

            } else {
                if ( isset( $variation_id ) && $variation_id !== '0' ) {
                    $product_obj = self::bkap_get_product( $variation_id );
                }else {
                    $product_obj = self::bkap_get_product( $product_id );
                }

                $price = $product_obj->get_price();
            }

            // check if any of the products are individually priced
            //if yes then we need to add those to the bundle price
            /*if ( $price > 0 && 'bundle' == $product_type ) {
                $bundle_price = bkap_common::get_bundle_price( $price, $product_id, $variation_id );

                $price = $bundle_price;
            }*/
        }
        return $price;
    }
    
    /**
     * Calculates the Total Bundle Price 
     * 
     * The bundle price + the Individual child 
     * price based on the bundle settings 
     * @param $price
     * @param int $product_id
     * @param int $variation_id
     * @return $price
     */
    static function get_bundle_price( $price, $product_id, $variation_id ) {
         
        global $wpdb;
         
        // get all the IDs for the items in the bundle
        $bundle_items_query = "SELECT bundled_item_id, product_id FROM `" . $wpdb->prefix . "woocommerce_bundled_items`
                                 WHERE bundle_id = %d";
        $get_bundle_items = $wpdb->get_results( $wpdb->prepare( $bundle_items_query, $product_id ) );
    
        if ( isset( $get_bundle_items ) && count( $get_bundle_items ) > 0 ) {

            // fetch the status of optional child products, whether they have been selected on the front end or no
            $explode_optional = array();
            if ( isset( $_POST[ 'bundle_optional' ] ) && '' != $_POST[ 'bundle_optional' ] ) {
                $explode_optional = $_POST[ 'bundle_optional' ];
                //$explode_optional = explode( ',', $bundle_optional );
            }
             
            $child_count = 0;
            foreach( $get_bundle_items as $b_key => $b_value ) {
                $bundled_product_obj = wc_pb_get_bundled_item($b_value->bundled_item_id);

                $quantity_min = $bundled_product_obj->get_quantity();
                $quantity_max = $bundled_product_obj->get_quantity( 'max', true );

                $child_selected = 'off';
                $bundle_item_id = $b_value->bundled_item_id;
                // get the pricing settings for each item
                $price_query = "SELECT meta_key, meta_value FROM `" . $wpdb->prefix  . "woocommerce_bundled_itemmeta`
                                         WHERE bundled_item_id = %d
                                         AND meta_key IN ( 'priced_individually', 'discount', 'optional' )";
                $price_results = $wpdb->get_results( $wpdb->prepare( $price_query, $bundle_item_id ) );
                //
                if( isset( $price_results ) && count( $price_results ) > 0 ) {

                    foreach( $price_results as $key => $value ) {
                        switch( $value->meta_key ) {
                            case 'priced_individually':
                                $price_type = $value->meta_value;
                                break;
                            case 'discount':
                                $price_discount = $value->meta_value;
                                break;
                            case 'optional':
                                $optional = $value->meta_value;
                                break;
                            default:
                                break;
                        }
                    }

                    // if product is optional, see if it has been selected or no
                    // on - selected, off - not selected
                    if ( 'yes' == $optional ) {
                        if ( isset( $explode_optional[ $bundle_item_id ] ) && '' != $explode_optional[ $bundle_item_id ] ) {
                            $child_selected = $explode_optional[ $bundle_item_id ];
                            $child_count++;
                        }
                    }

                    $variation_array = array();
                    // product is individually priced
                    if ( 'yes' == $price_type ) {
                        $bundle_child_id = $b_value->product_id;
                        $product_obj = self::bkap_get_product( $bundle_child_id );
                        $bundle_item_prd_type = $product_obj->get_type();
                        $bundle_item_var = 0;
                        if ( 'variable' == $bundle_item_prd_type ) {
                            $variation_list = $product_obj->get_available_variations();
                            foreach ($variation_list as $var_key => $var_value) {
                                array_push( $variation_array, $var_value['variation_id'] );
                            }
                            $variations_selected = explode( ',', $variation_id );

                            // find the variation selected
                            foreach( $variations_selected as $v_key => $v_val ) {
                                if ( in_array( $v_val, $variation_array ) ) {
                                    $bundle_item_var = $v_val;
                                    break;
                                }
                            }
                        }

                        $child_price = bkap_common::bkap_get_price( $bundle_child_id, $bundle_item_var, $bundle_item_prd_type );

                        if ( '' != $price_discount && $price_discount > 0 ) {
                            // calculate the discounted price
                            $discount = ( $price_discount * $child_price ) / 100;
                            $child_price -= $discount;
                        }

                        if ( $quantity_min === $quantity_max ) {
                            $child_price = $child_price * $quantity_min;
                        }

                        if ( isset( $_POST['quantity'] ) && $_POST['quantity'] > 0 ) {
                            $child_price = $child_price * $_POST['quantity'];
                        }

                        if ( isset( $_POST[ 'diff_days' ] ) && $_POST[ 'diff_days' ] > 0 ) {
                            $child_price = $child_price * $_POST[ 'diff_days' ];
                        }

                        // if the product is optional, the child price should be added only if it's selected
                        if ( 'yes' == $optional ) { // if the child product is optional
                            if ( 'on' == $child_selected ) {
                                $price += $child_price;
                            }
                        } else { // else product is not optional, so always add the child price
                            $price += $child_price;
                        }
                    }
                }
            }
        }

        return $price;
    }

    /**
     * Calculates the Total Composite Price 
     * 
     * The composite price + the Individual child 
     * price based on the product settings 
     * @param $price
     * @param int $product_id
     * @param int $variation_id
     * @return $price
     */
    public static function get_composite_price( $price, $product_id, $variation_id ) {
        
        $product_obj = wc_get_product( $product_id );

        $component_ids = $product_obj->get_component_ids();
        $composite_data = array();
        $child_price = '';

        if ( isset( $_POST['composite_data'] ) ) {
            $composite_data = $_POST['composite_data'];
        }

        if ( !empty( $composite_data ) ) {
            foreach ($composite_data as $c_key => $c_value) {
                if ( isset( $c_value['p_id'] ) && '' !== $c_value['p_id'] ) {

                    $component_data = $product_obj->get_component_data( $c_key );
                    if ( isset( $component_data['priced_individually'] ) && 'yes' === $component_data['priced_individually'] ) {

                        $child_product = wc_get_product( $c_value['p_id'] );
                        $child_type = $child_product->get_type();

                        $child_price = bkap_common::bkap_get_price( $c_value['p_id'], $variation_id, $child_type );

                        $child_discount = $product_obj->get_component_discount( $c_key );
                        if ( isset( $child_discount) && '' !== $child_discount ) {
                            $child_price = $child_price - ( ( $child_price * $child_discount ) / 100 );
                        }

                        if ( isset( $_POST['quantity'] ) && $_POST['quantity'] > 0 ) {
                            $child_price = $child_price * $_POST['quantity'];
                        }

                        if ( isset( $_POST[ 'diff_days' ] ) && $_POST[ 'diff_days' ] > 0 ) {
                            $child_price = $child_price * $_POST[ 'diff_days' ];
                        }

                        if ( isset( $c_value['qty'] ) && '' !== $c_value['qty'] ) {
                            $child_price = $child_price * $c_value['qty'];
                        }

                        $price += $child_price;
                    }
                }
            }
        }

        return $price;
    }
    
    
    /**
     * Return product type
     * 
     * Returns the Product type based on the ID received
     * 
     * @params $product_id int
     * @return $product_type str
     */
    public static function bkap_get_product_type($product_id) {
        $product      =   self::bkap_get_product( $product_id );
        $product_type =   $product->get_type();
        
        return $product_type;
    }
    
    /**
     * Returns the WooCommerce Product Addons Options total
     * 
     * This function returns the WooCommerce Product Addons
     * options total selected by a user for a given product.
     * 
     * @param int $diff_days Number of days between start and end. 1 in case of single days
     * @param array $cart_item_meta Cart Item Meta array
     * @param int $product_quantity Product Quantity
     * 
     * @since 4.5.0 added $product_quantity variable
     * 
     * @return int Total Price after calculations
     */
    public static function woo_product_addons_compatibility_cart( $diff_days, $cart_item_meta, $product_quantity ) {
        $addons_price = 0;
        if( class_exists('WC_Product_Addons') ) {
            $single_addon_price = 0;
            
            if( isset( $cart_item_meta['addons'] ) ) {
                $product_addons = $cart_item_meta['addons'];
                
                foreach( $product_addons as $key => $val ) {
                    $single_addon_price += $val['price'];
                }
                
                if( isset( $diff_days ) && $diff_days > 1 && $single_addon_price > 0 ) {
                    //$diff_days         -=  1;
                    $single_addon_price =  $single_addon_price * $diff_days * $product_quantity;
                    $addons_price      +=  $single_addon_price;
                }else{
                    $addons_price      +=  $single_addon_price * $product_quantity;
                }
                    
            }
        }
        return $addons_price;
    }
    
    /**
     * Checks if the product requires booking confirmation from admin
     *
     * If the Product is a bookable product and requires confirmation,
     * returns true else returns false
     *
     * @param int $product_id
     * @return boolean
     */
    public static function bkap_product_requires_confirmation( $product_id ) {
        $product = self::bkap_get_product( $product_id );
         
        // Booking Settings
        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
         
        if (
            is_object( $product )
            && isset( $booking_settings[ 'booking_enable_date' ] ) && 'on' == $booking_settings[ 'booking_enable_date' ]
            && isset( $booking_settings[ 'booking_confirmation' ] ) && 'on' == $booking_settings[ 'booking_confirmation' ]
        ) {
            return true;
        }
         
        return false;
    }
    
    /**
     * Checks if Cart contains bookable products that require confirmation
     *
     * Returns true if Cart contains any bookable products that require
     * confirmation, else returns false.
     *
     * @return boolean
     */
    public static function bkap_cart_requires_confirmation() {
         
        $requires = false;

        if ( isset( WC()->cart ) ) {
            foreach ( WC()->cart->cart_contents as $item ) {
                 
                $duplicate_of     =   bkap_common::bkap_get_product_id( $item['product_id'] );
                
                $requires_confirmation = bkap_common::bkap_product_requires_confirmation( $duplicate_of );
                 
                if ( $requires_confirmation ) {
                    $requires = true;
                    break;
                }
            }
        } 
        return $requires;
    
    }
    
    /**
     *
     * @param unknown $order
     * @return boolean
     */
    public static function bkap_order_requires_confirmation( $order ) {
        $requires = false;
    
        if ( $order ) {
            foreach ( $order->get_items() as $item ) {
                if ( bkap_common::bkap_product_requires_confirmation( $item['product_id'] ) ) {
                    $requires = true;
                    break;
                }
            }
        }
    
        return $requires;
    }
    
    /**
     *
     * @param unknown $item_id
     * @return stdClass
     */
    public static function get_bkap_booking( $item_id ) {
         
        global $wpdb;
         
        $booking_object = new stdClass();
         
        $start_date_label = ( '' == get_option( 'book_item-meta-date' ) ) ? __( 'Start Date', 'woocommerce-booking' ) : get_option( 'book_item-meta-date' ) ;
        $end_date_label = ( '' == get_option( 'checkout_item-meta-date' ) ) ? __( 'End Date', 'woocommerce-booking' ) : get_option( 'checkout_item-meta-date' );
        $time_label = ( '' == get_option( 'book_item-meta-time' ) ) ? __( 'Booking Time', 'woocommerce-booking' ) : get_option( 'book_item-meta-time' );
    
        // order ID
        $query_order_id = "SELECT order_id FROM `". $wpdb->prefix."woocommerce_order_items`
                            WHERE order_item_id = %d";
        $get_order_id = $wpdb->get_results( $wpdb->prepare( $query_order_id, $item_id ) );
         
        $order_id = 0;
        if ( isset( $get_order_id ) && is_array( $get_order_id ) && count( $get_order_id ) > 0 ) {
            $order_id = $get_order_id[0]->order_id;
        }
        $booking_object->order_id = $order_id;
         
        $order = new WC_order( $order_id );
         
        // order date
        $post_data = get_post( $order_id );
        $booking_object->order_date = $post_data->post_date;
         
        // product ID
        $booking_object->product_id = wc_get_order_item_meta( $item_id, '_product_id' );
         
        // product name
        $_product = self::bkap_get_product( $booking_object->product_id );
        $booking_object->product_title = $_product->get_title();
         
        // get the booking status
        $booking_object->item_booking_status = wc_get_order_item_meta( $item_id, '_wapbk_booking_status' );
         
        // get the hidden booking date and time
        $booking_object->item_hidden_date = wc_get_order_item_meta( $item_id, '_wapbk_booking_date' );
        $booking_object->item_hidden_checkout_date = wc_get_order_item_meta( $item_id, '_wapbk_checkout_date' );
        $booking_object->item_hidden_time = wc_get_order_item_meta( $item_id, '_wapbk_time_slot' );
    
        // get the booking date and time to be displayed
        $booking_object->item_booking_date = wc_get_order_item_meta( $item_id, $start_date_label );
        $booking_object->item_checkout_date = wc_get_order_item_meta( $item_id, $end_date_label );
        $booking_object->item_booking_time = wc_get_order_item_meta( $item_id, $time_label );
         
        // email adress
        $booking_object->billing_email = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_email : $order->get_billing_email();
         
        // customer ID
        $booking_object->customer_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->user_id : $order->get_user_id();
    
        return $booking_object;
    
    }
    
    /**
     * Returns the number of time slots present for a date.
     * The date needs to be passed in the j-n-Y format
     * 
     * @param int $product_id
     * @param str $date_check_in
     * @return number
     */
    public static function bkap_get_number_of_slots( $product_id, $date_check_in ) {
         
        // Booking settings
        $booking_settings =   get_post_meta( $product_id , 'woocommerce_booking_settings' , true );
            
        $number_of_slots = 0;
        // find the number of slots present for this date/day
        if ( is_array( $booking_settings[ 'booking_time_settings' ] ) && count( $booking_settings[ 'booking_time_settings' ] ) > 0 ) {
            if ( array_key_exists( $date_check_in, $booking_settings[ 'booking_time_settings' ] ) ) {
                $number_of_slots = count( $booking_settings[ 'booking_time_settings' ][ $date_check_in ] );
            } else { // it's a recurring weekday
                $weekday            =   date( 'w', strtotime( $date_check_in ) );
                $booking_weekday    =   'booking_weekday_' . $weekday;
                if( array_key_exists( $booking_weekday, $booking_settings[ 'booking_time_settings' ] ) ) {
                    $number_of_slots = count( $booking_settings[ 'booking_time_settings' ][ $booking_weekday ] );
                }
            }
        }
        return $number_of_slots;    
    }
    
    /**
     * Checks whether a product is bookable or no
     * 
     * @param int $product_id
     * @return bool $bookable
     */
    public static function bkap_get_bookable_status( $product_id ) {
         
        $bookable = false;
         
        // Booking settings
        $booking_settings =   get_post_meta( $product_id , 'woocommerce_booking_settings' , true );
         
        if( isset( $booking_settings ) && isset( $booking_settings[ 'booking_enable_date' ] ) && 'on' == $booking_settings[ 'booking_enable_date' ] ) {
            $bookable = true;
        }
         
        return $bookable;
    }
    
    /**
     * Get all products and variations and sort alphbetically, return in array (title, id)
     * 
     * @return array $full_product_list
     */
    public static function get_woocommerce_product_list( $variations = true ) {
        $full_product_list = array();
    
        $select_variation = '';
        if ( $variations ) {
            $select_variation = 'product_variation';
        }
         
        $args       = array( 
            'post_type' => array('product', $select_variation ), 
            'posts_per_page' => -1,
            'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
        );
        $product    = get_posts( $args );
    
        $parent_array = array();
        
        foreach ( $product as $k => $value ) {
            $theid = $value->ID;
            
            if ( 'product_variation' == $value->post_type ) {
                $parent_id = $value->post_parent;
                // ignore orphan variations
                if( 0 == $parent_id ) {
                    continue;
                }
                if ( ! in_array( $parent_id, $parent_array ) ) {
                    $parent_array[] = $parent_id;
                }
                $duplicate_of = bkap_common::bkap_get_product_id( $parent_id );
    
                $is_bookable = bkap_common::bkap_get_bookable_status( $duplicate_of );
            } else {
                $parent_id = 0;
                $duplicate_of = bkap_common::bkap_get_product_id( $theid );
                $is_bookable = bkap_common::bkap_get_bookable_status( $duplicate_of );
            }
    
            if ( $is_bookable ) {
    
                $_product = self::bkap_get_product( $theid );
                $thetitle = $_product->get_formatted_name();

                $product_type = $_product->get_type();

                if( $product_type == 'variable' ){
                    $variations = $_product->get_available_variations();

                    if( empty( $variations ) ) {
                        continue;
                    }
                }
    
                $full_product_list[] = array($thetitle, $theid);
            }
    
        }

        // remove the parent products for variations
        foreach( $full_product_list as $key => $products ) {
            if ( in_array( $products[ 1 ], $parent_array ) ) {
                unset( $full_product_list[ $key ] );
            }
        }
         
        // sort into alphabetical order, by title
        sort($full_product_list);
        return $full_product_list;
    
    }
    
    /**
     * Get all products and sort alphbetically, return in array (title, id, fixed block option, price range option)
     * 
     * @return array $full_product_list
     */
    
    public static function get_woocommerce_product_list_f_p(){
        $full_product_list = array();
        
        $args       = array( 
            'post_type' => 'product', 
            'posts_per_page' => -1,
            'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
        );
        $product    = query_posts( $args );
        
        foreach ( $product as $k => $value ) {
            $theid = $value->ID;
            $duplicate_of = bkap_common::bkap_get_product_id( $theid );
            
            $bkap_fixed_price = false;
            $bkap_range_price = false;
            
            // Booking settings
            $booking_settings =   get_post_meta( $duplicate_of , 'woocommerce_booking_settings' , true );
            
            $bkap_fixed = ( isset( $booking_settings[ 'booking_fixed_block_enable' ] ) && "" != $booking_settings[ 'booking_fixed_block_enable' ] ) ? $booking_settings[ 'booking_fixed_block_enable' ] : "";
            $bkap_range = ( isset( $booking_settings[ 'booking_block_price_enable' ] ) && "" != $booking_settings[ 'booking_block_price_enable' ] ) ? $booking_settings[ 'booking_block_price_enable' ] : "";
            
            if( isset( $booking_settings ) && isset( $bkap_fixed ) && 'yes' == $bkap_fixed ) {
                $bkap_fixed_price = true;
            }
            
            if( isset( $booking_settings ) && isset( $bkap_range ) && 'yes' == $bkap_range ) {
                $bkap_range_price = true;
            }
            
            if( $bkap_fixed_price || $bkap_range_price ){
                
                $_product = self::bkap_get_product( $theid );
                $thetitle = $_product->get_formatted_name();
                
                $full_product_list[] = array( $thetitle, $theid, $bkap_fixed_price, $bkap_range_price);
            }
            
        }
        wp_reset_query();
        // sort into alphabetical order, by title
        sort($full_product_list);
         
        return $full_product_list;
            
    }
    
    
    
    /**
     * Adds item meta for bookable products
     * 
     * @param int $item_id
     * @param int $product_id
     * @param array $booking_data
     * @param bool $gcal_import
     */
    public static function bkap_update_order_item_meta( $item_id, $product_id, $booking_data, $gcal_import = false ) {
    
        $booking_settings  =   get_post_meta( $product_id, 'woocommerce_booking_settings', true );
    
        if ( $gcal_import ) {
            wc_add_order_item_meta( $item_id, '_wapbk_booking_status', 'paid' );
        } else {
            if( isset( WC()->session ) && WC()->session !== null && 
                WC()->session->get( 'chosen_payment_method' ) === 'bkap-booking-gateway' ) {
                wc_add_order_item_meta( $item_id, '_wapbk_booking_status', 'pending-confirmation' );
            } else {
                wc_add_order_item_meta( $item_id, '_wapbk_booking_status', 'confirmed' );
            }
        }
         
        if ( $booking_data['date'] != "" ) {
            $name         =   ( '' == get_option( 'book_item-meta-date' ) ) ? __( 'Start Date', 'woocommerce-booking' ) : get_option( 'book_item-meta-date' ) ;
            $date_select  =   $booking_data['date'];
            
            wc_add_order_item_meta( $item_id, $name, sanitize_text_field( $date_select, true ) );
        }
         
        if ( array_key_exists( 'hidden_date', $booking_data ) && $booking_data['hidden_date'] != "" ) {
            // save the date in Y-m-d format
            $date_booking = date( 'Y-m-d', strtotime( $booking_data['hidden_date'] ) );
            wc_add_order_item_meta( $item_id, '_wapbk_booking_date', sanitize_text_field( $date_booking, true ) );
        }
         
        if ( array_key_exists( 'date_checkout', $booking_data ) && $booking_data['date_checkout'] != "" ) {
    
            if ( $booking_settings['booking_enable_multiple_day'] == 'on' ) {
                $name_checkout           =   ( '' == get_option( 'checkout_item-meta-date' ) ) ? __( 'End Date', 'woocommerce-booking' ) : get_option( 'checkout_item-meta-date' );
                $date_select_checkout    =   $booking_data['date_checkout'];
                
                wc_add_order_item_meta( $item_id, $name_checkout, sanitize_text_field( $date_select_checkout, true ) );
            }
        }
         
        if ( array_key_exists( 'hidden_date_checkout', $booking_data ) && $booking_data['hidden_date_checkout'] != "" ) {
             
            if ( $booking_settings['booking_enable_multiple_day'] == 'on' ) {
                // save the date in Y-m-d format
                $date_booking = date( 'Y-m-d', strtotime( $booking_data['hidden_date_checkout'] ) );
                wc_add_order_item_meta( $item_id, '_wapbk_checkout_date', sanitize_text_field( $date_booking, true ) );
            }
        }
         
        if ( array_key_exists( 'time_slot', $booking_data ) && $booking_data['time_slot'] != "" ) {
            $time_slot_to_display     =   '';
            $time_select              =   $booking_data['time_slot'];
            $time_exploded            =   explode( "-", $time_select );
             
            $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
    
            if ( isset( $saved_settings ) ) {
                $time_format = $saved_settings->booking_time_format;
            }else{
                $time_format = "12";
            }
    
            $time_slot_to_display = '';
            $from_time = trim($time_exploded[0]);
    
            if( isset( $time_exploded[1] ) ){
                $to_time = trim( $time_exploded[1] );
            }else{
                $to_time = '';
            }
    
            if ( $time_format == '12' ) {
                $from_time = date( 'h:i A', strtotime( $time_exploded[0] ) );
                if( isset( $time_exploded[1] ) )$to_time = date( 'h:i A', strtotime( $time_exploded[1] ) );
            }
    
            $query_from_time  =   date( 'G:i', strtotime( $time_exploded[0] ) );
            $meta_data_format =   $query_from_time;
    
            if( isset( $time_exploded[1] ) ){
                $query_to_time       = date( 'G:i', strtotime( $time_exploded[1] ) );
                $meta_data_format   .= ' - ' . $query_to_time;
            }else{
                $query_to_time       = '';
            }
    
            if( $to_time != '' ) {
                $time_slot_to_display = $from_time.' - '.$to_time;
            }else {
                $time_slot_to_display = $from_time;
            }

            $name_time_slot = ( '' == get_option( 'book_item-meta-time' ) ) ? __( 'Booking Time', 'woocommerce-booking' ): get_option( 'book_item-meta-time' ) ;
    
            wc_add_order_item_meta( $item_id, $name_time_slot , $time_slot_to_display, true );
            wc_add_order_item_meta( $item_id,  '_wapbk_time_slot', $meta_data_format, true );
             
        }
        
        if ( array_key_exists( 'resource_id', $booking_data ) && $booking_data['resource_id'] != 0 ) {
             
            wc_add_order_item_meta( $item_id, '_resource_id', $booking_data['resource_id'] );
        }
        
        if ( array_key_exists( 'resource_id', $booking_data ) && $booking_data['resource_id'] != 0 ) {
        
            $resource_label = Class_Bkap_Product_Resource::bkap_get_resource_label( $product_id );
        
            if ( $resource_label == "" )
                $resource_label = __( 'Resource Type', 'wocommerce-booking' );
        
            $resource_title = get_the_title( $booking_data['resource_id'] );
        
            wc_add_order_item_meta( $item_id, $resource_label, $resource_title );
        }
        do_action( 'bkap_update_item_meta', $item_id, $product_id, $booking_data ); 
    }
    
    /**
     * Creates a list of orders that are not yet exported to GCal
     * 
     * @return array $total_orders_to_export
     */
    public static function bkap_get_total_bookings_to_export( $user_id ) {
    
        global $wpdb;
        $total_orders_to_export = array();
        
        $user_id = get_current_user_id();
        
        // get the user role
        $user = new WP_User( $user_id );
        if( 'tour_operator' == $user->roles[ 0 ] ) {
            $event_items = get_the_author_meta( 'tours_event_item_ids', $user_id );
        } else {
            $event_items = get_option( 'bkap_event_item_ids' );
        }
        
        if( $event_items == '' || $event_items == '{}' || $event_items == '[]' || $event_items == 'null' ) {
            $event_items = array();
        }
    
        $current_time = current_time( 'timestamp' );
    
        $bkap_query = "SELECT ID, post_status FROM `" . $wpdb->prefix . "posts` WHERE post_type = 'shop_order'";
        $results = $wpdb->get_results( $bkap_query );
    
        $total_orders_to_export = array();
    
        foreach ( $results as $key => $value ) {
            $order_id = $value->ID;
    
            $order_status = $value->post_status;
            
            if( isset( $order_status ) && ( $order_status != 'wc-cancelled' ) && ( $order_status != 'wc-refunded' ) && ( $order_status != 'trash' ) && ( $order_status != '' ) && ( 'auto-draft' != $order_status ) ) {
        
                $get_items_sql  = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_id, order_item_name FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = %s", $order_id, 'line_item' ) );

                $item_values = array();
                foreach ( $get_items_sql as $i_key => $i_value ) {
                    $get_items = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_id, meta_key, meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key IN ( '_product_id', '_wapbk_booking_status', '_wapbk_booking_date' ) AND order_item_id = %d" , $i_value->order_item_id ) );
                    
                    if ( is_array( $get_items ) && count( $get_items ) > 0 ) {
                        foreach ( $get_items as $get_key => $get_value ) {
                            if ( isset( $get_value->meta_key ) ) {
                                switch ( $get_value->meta_key ) {
                                    case '_product_id':
                                        $item_values[ $get_value->order_item_id ][ 'product_id' ] = $get_value->meta_value;
                                        break;
                                    case '_wapbk_booking_date':
                                        $item_values[ $get_value->order_item_id ][ 'wapbk_booking_date' ] = $get_value->meta_value;
                                        break;
                                    case '_wapbk_booking_status':
                                        $item_values[ $get_value->order_item_id ][ 'wapbk_booking_status' ] = $get_value->meta_value;
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                    }
                }
                $i = 0;
                
                if ( is_array( $item_values ) && count( $item_values ) > 0 ) {
                    foreach ( $item_values as $i_key => $i_values ) {
                            
                        $booking_status = '';
                        $booking_date = '';
        
                        if( !in_array( $i_key, $event_items ) ) {
        
                            $is_bookable = bkap_common::bkap_get_bookable_status( $i_values[ 'product_id' ] );
                            $valid_date = false;
                            if ( isset( $i_values[ 'wapbk_booking_date' ] ) ) { 
                               $valid_date = bkap_common::bkap_check_date_set( $i_values[ 'wapbk_booking_date' ] );
                            }
                            if ( $is_bookable && $valid_date ) {
                                // check if the item belongs to a tour operator
                                $booking_settings = get_post_meta( $i_values[ 'product_id' ], 'woocommerce_booking_settings', true );
                                 
                                // check if the tour operators plugin is active
                                if ( function_exists( 'is_bkap_tours_active' ) && is_bkap_tours_active() ) {
                                     
                                    if ( isset( $booking_settings[ 'booking_tour_operator' ] ) && $booking_settings[ 'booking_tour_operator' ] != '' ) {
                                        // if yes, then if gcal setup is allowed for the tour operator the item should be ignored for the admin
                                        if ( 'yes' == get_option( 'bkap_allow_tour_operator_gcal_api' ) ) {
                                            if ( $booking_settings[ 'booking_tour_operator' ] == $user_id ) {
                                                $add_item = 'YES';
                                            } else {
                                                $add_item = 'NO';
                                            }
                                        } else if ( 'tour_operator' != $user->roles[ 0 ] ) { // if no, then the admin should be allowed to export the item
                                            $add_item = 'YES';
                                        }
                                    } else if ( 'tour_operator' != $user->roles[ 0 ] ) { // as admin should be allowed to export such items
                                        $add_item = 'YES';
                                    }
                                } else {
                                    $add_item = 'YES';
                                }
                            }
                             
                            if ( isset( $add_item ) && 'YES' == $add_item ) {
                                
                                if ( isset( $i_values[ 'wapbk_booking_status' ] ) ) {
                                    $booking_status = $i_values[ 'wapbk_booking_status' ];
                                }
                                if ( isset( $i_values[ 'wapbk_booking_date' ] ) ) {
                                    $booking_date = strtotime( $i_values[ 'wapbk_booking_date' ] );
                                }
                                if ( isset( $booking_status ) && $booking_status != 'pending-confirmation' && isset( $booking_date ) && $booking_date != '' && $booking_date >= $current_time ) {
                                    $total_orders_to_export[ $value->ID ][ $i ] = $i_key;
                                }
                                $i++;
                            }
                        }
                    }
                }
            }
        }
        return $total_orders_to_export;
    }
    

    public static function get_currency_args() {
        if ( function_exists( 'icl_object_id' ) ) {
            global $woocommerce_wpml;
            
            if ( isset( $woocommerce_wpml->settings[ 'enable_multi_currency' ] ) && $woocommerce_wpml->settings[ 'enable_multi_currency' ] == '2' ) {
                if ( WCML_VERSION >= '3.8' ) {
                    $currency = $woocommerce_wpml->multi_currency->get_client_currency();
                } else {
                   $currency = $woocommerce_wpml->multi_currency_support->get_client_currency();
                }
            }else{
                $currency = get_woocommerce_currency();
            }
            $wc_price_args = array(
                'currency'           => $currency,
                'decimal_separator'  => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'decimals'           => wc_get_price_decimals(),
                'price_format'       => get_woocommerce_price_format()
            );
        } else {
            $wc_price_args = array(
                'currency'           => get_woocommerce_currency(),
                'decimal_separator'  => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'decimals'           => wc_get_price_decimals(),
                'price_format'       => get_woocommerce_price_format()
            );
        }
        return $wc_price_args;
    }
    
    /**
     * The below function adds notices to be displayed.
     * It displays the notices as well using print notices function.
     * This helps in displaying notices without having to reload the page.
     */
    public static function bkap_add_notice() {
        $product_id = $_POST[ 'post_id' ];
    
        $message = '';
        if ( isset( $_POST[ 'message' ] ) ) {
            $message = $_POST[ 'message' ];
        }
    
        $notice_type = 'error';
        if ( isset( $_POST[ 'notice_type' ] ) ) {
            $notice_type = $_POST[ 'notice_type' ];
        }
    
        if ( ( isset( $message ) && '' != $message ) ) {
            wc_add_notice( __( $message, 'woocommerce-booking' ), $notice_type );
            wc_print_notices();
        }
        die;
    }
    
    /**
     * This function clears any notices
     * set in the session
     */
    public static function bkap_clear_notice() {
        wc_clear_notices();
        die;
    }
    
    /**
     * This function will return the differance days between two dates.
     */
    
    public static function dateTimeDiff( $date1, $date2 ) {
        
        $one = $date1->format('U');
        $two = $date2->format('U');
        
        $invert = false;
        if ( $one > $two ) {
            list( $one, $two ) = array( $two, $one );
            $invert = true;
        }
        
        $key           = array("y", "m", "d", "h", "i", "s");
        $a             = array_combine( $key, array_map( "intval", explode( " ", date( "Y m d H i s", $one ) ) ) );
        $b             = array_combine( $key, array_map( "intval", explode( " ", date( "Y m d H i s", $two ) ) ) );
        $result        = new stdClass();
        $current_time  = current_time( 'timestamp' );
        $date          = ( date ("d", $current_time) ) - 1 ;
         
         
        $result->y         = $b["y"] - $a["y"];
        $result->m         = $b["m"] - $a["m"];
        $result->d         = $date;
        $result->h         = $b["h"] - $a["h"];
        $result->i         = $b["i"] - $a["i"];
        $result->s         = $b["s"] - $a["s"];
        $result->invert    = $invert ? 1 : 0;
        $result->days      = intval(abs(($one - $two)/86400));
        
         
        if ( $invert ) {
            bkap_common::_date_normalize( $a, $result );
        } else {
            bkap_common::_date_normalize( $b, $result );
        }
        
        return $result;
    }
    
    public static function _date_range_limit( $start, $end, $adj, $a, $b, $result )
    {
        $result = (array)$result;
        if ( $result[ $a ] < $start ) {
            $result[ $b ] -= intval( ( $start - $result[ $a ] - 1 ) / $adj ) + 1;
            $result[ $a ] += $adj * intval( ( $start - $result[ $a ] - 1) / $adj + 1 );
        }
    
        if ( $result[ $a ] >= $end ) {
            $result[ $b ] += intval( $result[ $a ] / $adj );
            $result[ $a ] -= $adj * intval( $result[ $a ] / $adj );
        }
    
        return $result;
    }
    
    public static function _date_range_limit_days( $base, $result )
    {
        $days_in_month_leap    = array( 31, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
        $days_in_month         = array( 31, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
    
        bkap_common::_date_range_limit(1, 13, 12, "m", "y", $base);
    
        $year  = $base["y"];
        $month = $base["m"];
    
        if ( !$result["invert"] ) {
            while ( $result["d"] < 0 ) {
                $month--;
                if ( $month < 1 ) {
                    $month += 12;
                    $year--;
                }
    
                $leapyear  = $year % 400 == 0 || ( $year % 100 != 0 && $year % 4 == 0 );
                $days      = $leapyear ? $days_in_month_leap[ $month ] : $days_in_month[ $month ];
    
                $result["d"] += $days;
                $result["m"]--;
            }
        } else {
            while ( $result["d"] < 0 ) {
                $leapyear  = $year % 400 == 0 || ( $year % 100 != 0 && $year % 4 == 0 );
                $days      = $leapyear ? $days_in_month_leap[ $month ] : $days_in_month[ $month ];
    
                $result["d"] += $days;
                $result["m"]--;
    
                $month++;
                if ( $month > 12 ) {
                    $month -= 12;
                    $year++;
                }
            }
        }
    
        return $result;
    }
    
    public static  function _date_normalize( $base, $result )
    {
        $result = bkap_common::_date_range_limit(0, 60, 60, "s", "i", $result);
        $result = bkap_common::_date_range_limit(0, 60, 60, "i", "h", $result);
        $result = bkap_common::_date_range_limit(0, 24, 24, "h", "d", $result);
        $result = bkap_common::_date_range_limit(0, 12, 12, "m", "y", $result);
    
        $result = bkap_common::_date_range_limit_days( $base, $result );
    
        $result = bkap_common::_date_range_limit( 0, 12, 12, "m", "y", $result );
    
        return $result;
    }
    
    public static function bkap_check_date_set( $date ) {
        $future_date_set = false;
    
        if ( isset( $date ) && '' != $date ) {
    
            if ( strtotime( $date ) > current_time( 'timestamp' ) ) {
                $future_date_set = true;
            }
        }
        return $future_date_set;
    }
    
    public static function bkap_cart_contains_bookable() {
    
        $contains_bookable = false;
    
        $cart_items_count = WC()->cart->cart_contents_count;
    
        if ( $cart_items_count > 0  ) {
            foreach ( WC()->cart->cart_contents as $item ) {
    
                $is_bookable = bkap_common::bkap_get_bookable_status( $item['product_id'] );
    
                if ( $is_bookable ) {
                    $contains_bookable = true;
                    break;
                }
            }
        }
        return $contains_bookable;
    
    }

    /**
     * Create and return an array of Booking statuses
     *
     * @return array booking statuses
     * @since 4.0.0
     */
    static function get_bkap_booking_statuses() {

        return $allowed_status = array( 'pending-confirmation' => 'Pending Confirmation',
	        'confirmed' => 'Confirmed',
	        'paid' => 'Paid',
	        'cancelled' => 'Cancelled'
	    );
	    
	}
	
	/**
	 * Create and return an array of event statuses
	 *
	 * @return array event statuses
	 * @since 4.0.0
	 */
	static function get_bkap_event_statuses() {
	
	    return $allowed_status = array( 'bkap-unmapped' => 'Un-mapped',
	        'bkap-mapped' => 'Mapped',
	        'bkap-deleted' => 'Deleted'
	    );
	     
	}

    /**
     * Fetches the Booking Post ID using the Item ID
     *
     * @param int $item_id
     * @return int booking ID
     * @since 4.0.0
     */
    static function get_booking_id( $item_id ) {
        global $wpdb;

        $query_posts = "SELECT post_id FROM `" . $wpdb->prefix . "postmeta`
                   where meta_key = %s
                   AND meta_value = %d";

        $get_posts = $wpdb->get_results( $wpdb->prepare( $query_posts, '_bkap_order_item_id', $item_id ) );

        if ( count( $get_posts ) > 0 ) {
            return $get_posts[ 0 ]->post_id;
        } else {
            return false;
        }
    }
  
    /**
      * This function Checks if the Specific Date contains time slot or not.
      * @param int $product_id
      * @return $timeslots_present as boolean
      * @since 4.2
      */

    public static function bkap_check_specific_date_has_timeslot ( $product_id ) {

        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
        $booking_specific_dates = ( isset( $booking_settings[ 'booking_specific_date' ] ) ) ? $booking_settings[ 'booking_specific_date' ] : array();

    	  if( "" != $booking_specific_dates && count( $booking_specific_dates ) > 0 ){

          if( isset( $booking_settings['booking_time_settings'] ) && count($booking_settings['booking_time_settings'] ) > 0 ) {
            $booking_time_settings_key = array_keys(  $booking_settings['booking_time_settings'] );

          }

          foreach ( $booking_specific_dates as $booking_specific_dates_key => $booking_specific_dates_value ) {

            if ( is_array ( $booking_time_settings_key ) && count( $booking_time_settings_key ) > 0 ){

              if( in_array( $booking_specific_dates_key, $booking_time_settings_key ) ) {

                  return true;
              }
            }

          }	
        }
		
		  return false;

    }
  
    /**
     * The function checks if the passed product ID
     * is listed as a child in _children postmeta
     * If yes, then it returns the parent product ID
     * else it returns 0
     * @param int $child_id
     * @return int $parent_id
     * @since 3.5.2
     */
    public static function bkap_get_parent_id( $child_id ) {
        $parent_id = '';
         
        global $wpdb;
         
        $query_children = "SELECT post_id, meta_value FROM `" . $wpdb->prefix . "postmeta`
                           WHERE meta_key = %s";
         
        $results_children = $wpdb->get_results( $wpdb->prepare( $query_children, '_children' ) );
         
        if ( is_array( $results_children ) && count( $results_children ) > 0 ) {
             
            foreach( $results_children as $r_value ) {
                // check if the meta value is non blanks
                if ( $r_value->meta_value != '' ) {
                    // unserialize the data, create an array
                    $child_array = maybe_unserialize( $r_value->meta_value );
                    // if child ID is present in the array, we've found the parent
                    if ( ( in_array( $child_id, $child_array ) ) ) {
                        $parent_id = $r_value->post_id;
                        break;
                    }
                }
            }
        }
        return $parent_id;
    }

    /**
     * Get WooCommerce Product object
     * 
     * @param int|string $product_id Product ID
     * @return WC_Product Product Object
     * @since 4.1.1
     */
    public static function bkap_get_product( $product_id ) {
        return wc_get_product( $product_id );
    }

    /**
     * Get Addon Data for pricing purpose from cart item
     * 
     * @param array $cart_item Cart Item Array
     * @return array Addon Pricing array
     * @since 4.2
     */
    public static function bkap_get_cart_item_addon_data( $cart_item ) {

        $addon_pricing_data = array();

        // For compatibility with Gravity Forms
        if ( isset( $cart_item['_gform_total'] ) && $cart_item['_gform_total'] > 0 ) {
            $addon_pricing_data['gf_options'] = $cart_item['_gform_total'];
        }

        // For compatibility with WooCommerce Product Addons
        if ( isset( $cart_item['addons'] ) && count( $cart_item['addons'] ) > 0 ) {
            $addon_pricing_data['wpa_options'] = self::bkap_get_wpa_cart_totals( $cart_item );
        }

        return $addon_pricing_data;
    }

    /**
     * Get Product addons prices
     * 
     * @param array $cart_item Cart Item
     * @return float Addon Total
     * @since 4.2
     */
    public static function bkap_get_wpa_cart_totals( $cart_item ) {

        $wpa_addons_total = 0;
        foreach ( $cart_item['addons'] as $addon_key => $addon_value ) {
            $wpa_addons_total = $wpa_addons_total + $addon_value['price'];
        }

        return $wpa_addons_total;
    }

    /**
     * Get Addon data for pricing purpose from Order Item data
     * 
     * @param WC_Order_Item $order_item Order Item object
     * @return array Addon pricing array
     * @since 4.2
     */
    public static function bkap_get_order_item_addon_data( $order_item ) {

        $addon_pricing_data = array();

        // For compatibility with Gravity Forms
        $currency_symbol = get_woocommerce_currency_symbol();
        if ( isset( $order_item['Total'] ) && $order_item['Total'] !== '' ) {
            $addon_pricing_data['gf_options'] = str_replace( html_entity_decode($currency_symbol), '', $order_item['Total'] );
        }

        // For compatibility with WooCommerce Product Addons
        if ( isset( $order_item['_wapbk_wpa_prices'] ) && $order_item['_wapbk_wpa_prices'] !== '' ) {
            $addon_pricing_data['wpa_options'] = $order_item['_wapbk_wpa_prices'];
        }

        return $addon_pricing_data;
    }

    /**
     * Check if cart item passed is composite in some parent product
     * 
     * @param array $cart_item Cart Item Array
     * @return bool
     * @since 4.7.0
     */
    public static function bkap_is_cartitem_composite( $cart_item ) {
        
        if ( isset( $cart_item['composite_parent'] ) ) {
            return true;
        }
        return false;
    }

    /**
     * Check if order item is composite in some parent product
     * 
     * @param WC_Order_Item $item Order Item object
     * @return bool
     * @since 4.7.0
     */
    public static function bkap_is_orderitem_composite( $item ) {
        
        if ( isset( $item['_composite_parent'] ) ) {
            return true;
        }
        return false;
    }

    /**
     * Check if cart item passed is bundled in some parent product
     * 
     * @param array $cart_item Cart Item Array
     * @return bool
     * @since 4.2
     */
    public static function bkap_is_cartitem_bundled( $cart_item ) {
        
        if ( isset( $cart_item['bundled_by'] ) ) {
            return true;
        }
        return false;
    }

    /**
     * Check if order item is bundled in some parent product
     * 
     * @param WC_Order_Item $item Order Item object
     * @return bool
     * @since 4.2
     */
    public static function bkap_is_orderitem_bundled( $item ) {
        
        if ( isset( $item['_bundled_by'] ) ) {
            return true;
        }
        return false;
    }

    /**
     * Get cart configuration for Bundled Products
     * 
     * @param WC_Product $product Product Object
     * @return array Cart Config
     * @since 4.2
     */
    public static function bkap_bundle_add_to_cart_config( $product ) {

        $posted_config = array();

        if ( is_object( $product ) && 'bundle' === $product->get_type() ) {

            $product_id    = WC_PB_Core_Compatibility::get_id( $product );
            $bundled_items = $product->get_bundled_items();

            if ( ! empty( $bundled_items ) ) {

                $posted_data = $_POST;

                if ( empty( $_POST[ 'add-to-cart' ] ) && ! empty( $_GET[ 'add-to-cart' ] ) ) {
                    $posted_data = $_GET;
                }

                foreach ( $bundled_items as $bundled_item_id => $bundled_item ) {

                    $posted_config[ $bundled_item_id ] = array();

                    $bundled_product_id   = $bundled_item->product_id;
                    $bundled_product_type = $bundled_item->product->get_type();
                    $is_optional          = $bundled_item->is_optional();

                    $bundled_item_quantity_request_key = apply_filters( 'woocommerce_product_bundle_field_prefix', '', $product_id ) . 'bundle_quantity_' . $bundled_item_id;
                    $bundled_product_qty               = isset( $posted_data[ $bundled_item_quantity_request_key ] ) ? absint( $posted_data[ $bundled_item_quantity_request_key ] ) : $bundled_item->get_quantity();

                    $posted_config[ $bundled_item_id ][ 'product_id' ] = $bundled_product_id;

                    if ( $bundled_item->has_title_override() ) {
                        $posted_config[ $bundled_item_id ][ 'title' ] = $bundled_item->get_raw_title();
                    }

                    if ( $is_optional ) {

                        /** Documented in method 'get_posted_bundle_configuration'. */
                        $bundled_item_selected_request_key = apply_filters( 'woocommerce_product_bundle_field_prefix', '', $product_id ) . 'bundle_selected_optional_' . $bundled_item_id;

                        $posted_config[ $bundled_item_id ][ 'optional_selected' ] = isset( $posted_data[ $bundled_item_selected_request_key ] ) ? 'yes' : 'no';

                        if ( 'no' === $posted_config[ $bundled_item_id ][ 'optional_selected' ] ) {
                            $bundled_product_qty = 0;
                        }
                    }

                    $posted_config[ $bundled_item_id ][ 'quantity' ] = $bundled_product_qty;

                    // Store variable product options in stamp to avoid generating the same bundle cart id.
                    if ( 'variable' === $bundled_product_type || 'variable-subscription' === $bundled_product_type ) {

                        $attr_stamp = array();
                        $attributes = $bundled_item->product->get_attributes();

                        foreach ( $attributes as $attribute ) {

                            if ( ! $attribute[ 'is_variation' ] ) {
                                continue;
                            }

                            $taxonomy = WC_PB_Core_Compatibility::wc_variation_attribute_name( $attribute[ 'name' ] );

                            /** Documented in method 'get_posted_bundle_configuration'. */
                            $bundled_item_taxonomy_request_key = apply_filters( 'woocommerce_product_bundle_field_prefix', '', $product_id ) . 'bundle_' . $taxonomy . '_' . $bundled_item_id;

                            if ( isset( $posted_data[ $bundled_item_taxonomy_request_key ] ) ) {

                                // Get value from post data.
                                if ( $attribute[ 'is_taxonomy' ] ) {
                                    $value = sanitize_title( stripslashes( $posted_data[ $bundled_item_taxonomy_request_key ] ) );
                                } else {
                                    $value = wc_clean( stripslashes( $posted_data[ $bundled_item_taxonomy_request_key ] ) );
                                }

                                $attr_stamp[ $taxonomy ] = $value;
                            }
                        }

                        $posted_config[ $bundled_item_id ][ 'attributes' ]   = $attr_stamp;
                        $bundled_item_variation_id_request_key               = apply_filters( 'woocommerce_product_bundle_field_prefix', '', $product_id ) . 'bundle_variation_id_' . $bundled_item_id;
                        $posted_config[ $bundled_item_id ][ 'variation_id' ] = isset( $posted_data[ $bundled_item_variation_id_request_key ] ) ? $posted_data[ $bundled_item_variation_id_request_key ] : '';
                    }
                }
            }
        }

        return $posted_config;
    }

    /**
     * Returns an array of Booking IDs for the order ID sent.
     * @since 4.2.0
     */
    public static function get_booking_ids_from_order_id( $order_id ) {
         
        $booking_ids = array();
        if ( absint( $order_id ) > 0 ) {
    
            global $wpdb;
            if ( false !== get_post_status( $order_id ) ) {
                 
                $order_query = "SELECT ID from `" . $wpdb->prefix . "posts`
	                           WHERE post_parent = %d";
    
                $results = $wpdb->get_results( $wpdb->prepare( $order_query, $order_id ) );
                 
                if ( isset( $results ) && count( $results ) > 0 ) {
                    foreach( $results as $r_value ) {
                        $booking_ids[] = $r_value->ID;
                    }
                }
            }
        }
         
        return $booking_ids;
    }

    public static function bkap_get_bookings( $post_status, $additional_args = '' ) {
        
        $bookings_array = array();
        $search = ( isset( $_GET['s'] ) && '' !== $_GET['s'] ) ? $_GET['s'] : '';

        $args = array(
            'post_status'    => $post_status,
            's'              => $search
        );

        $wp_args = wp_parse_args( $args, array(
            'post_status'    => array( 'draft', 'cancelled', 'confirmed', 'paid', 'pending-confirmation' ),
            'post_type'      => 'bkap_booking',
            'parent'         => null,
            'posts_per_page' => -1,
            'meta_query'     => array(),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'return'         => 'objects',
        ) );

        if ( isset( $additional_args ) && $additional_args !== '' ) {
            $wp_args = wp_parse_args( $wp_args, $additional_args );
        }

        $booking = new WP_Query( $wp_args );

        foreach ( $booking->posts as $posts ) {
            $bookings_array[] = new BKAP_Booking($posts);
        }

        /*echo "<pre>";print_r($bookings_array[0]->get_status(false));echo "</pre>";
        exit();*/
        return $bookings_array;
    }

    public static function get_mapped_status( $status ) {
        
        switch ( $status ) {
            case 'paid':
                return __( 'Paid and Confirmed', 'woocommerce-booking' );
                break;

            case 'confirmed':
                return __( 'Confirmed', 'woocommerce-booking' );
                break;

            case 'pending-confirmation':
                return __( 'Pending Confirmation', 'woocommerce-booking' );
                break;

            case 'cancelled':
                return __( 'Cancelled', 'woocommerce-booking' );
                break;

            case 'draft':
                return __( 'Draft', 'woocommerce-booking' );
                break;
            
            default:
                return __( 'Paid and Confirmed', 'woocommerce-booking' );
                break;
        }
    }
   
}
?>