<?php

include_once('bkap-common.php');
include_once('lang.php');

class bkap_global_settings {
    public static function bkap_global_settings_section_callback() {}

    public static function booking_language_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        echo '<select id="booking_language" name="woocommerce_booking_global_settings[booking_language]">';
        $language_selected = "";
        if ( isset( $saved_settings->booking_language ) ) {
            $language_selected = $saved_settings->booking_language;
        }
        
        if ( $language_selected == "" ) {
            $language_selected = "en-GB";
        }
        
        $languages = bkap_get_book_arrays('bkap_languages');
        foreach ( $languages as $key => $value ) {
            $sel = "";
            if ( $key == $language_selected ) {
                $sel = " selected ";
            }
            echo "<option value='$key' $sel>$value</option>";
        }
        echo '</select>';
                
        $html = '<label for="booking_language"> ' . $args[ 0 ] . '</label>';
        echo $html; 
    }
    
    public static function booking_date_format_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        echo '<select id="booking_date_format" name="woocommerce_booking_global_settings[booking_date_format]">';
        if ( isset( $saved_settings ) ) { 
            $date_format = $saved_settings->booking_date_format;
        } else {
            $date_format = "";
        }
        $date_formats = bkap_get_book_arrays( 'bkap_date_formats' );
        foreach ( $date_formats as $k => $format ) {
            printf( "<option %s value='%s'>%s</option>\n",
                selected( $k, $date_format, false ),
                esc_attr( $k ),
                date( $format )
            );
        }
        echo '</select>';
        $html = '<label for="booking_date_format"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function booking_time_format_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        echo '<select id="booking_time_format" name="woocommerce_booking_global_settings[booking_time_format]">';
        $time_format = ""; 
        if ( isset( $saved_settings ) ) {
            $time_format = $saved_settings->booking_time_format;
        }
        $time_formats = bkap_get_book_arrays('bkap_time_formats');
        foreach ( $time_formats as $k => $format ) {
            printf( "<option %s value='%s'>%s</option>\n",
                selected( $k, $time_format, false ),
                esc_attr( $k ),
                __( $format, "woocommerce-booking" )
            );
        }
        echo '</select>';
        $html = '<label for="booking_time_format"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function booking_months_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $no_months_1 = "";
        $no_months_2 = "";
        if ( isset( $saved_settings ) ) {
            if ( $saved_settings->booking_months == 1 ) {
                $no_months_1 = "selected";
                $no_months_2 = "";
            } elseif ( $saved_settings->booking_months == 2 ) {
                $no_months_2 = "selected";
                $no_months_1 = "";
            }
        }
        echo '<select id="booking_months" name="woocommerce_booking_global_settings[booking_months]">
            <option ' . $no_months_1 . ' value="1"> 1 </option>
            <option ' . $no_months_2 . ' value="2"> 2 </option>
        </select>';
        
        $html = '<label for="booking_months"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function booking_calendar_day_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        echo '<select id="booking_calendar_day" name="woocommerce_booking_global_settings[booking_calendar_day]">';                                                                              
        $day_selected = "";
        if ( isset( $saved_settings->booking_calendar_day ) ) {
            $day_selected = $saved_settings->booking_calendar_day;
        }

        if ( $day_selected == "" ) {
            $day_selected = get_option('start_of_week');
        }
        $days = bkap_get_book_arrays('bkap_days');
        foreach ( $days as $key => $value ) {
            $sel = "";
            if ( $key == $day_selected ) {
                $sel = " selected ";
            }
            echo "<option value='$key' $sel>" . __( $value, 'woocommerce-booking' ) . "</option>";
        }
        echo '</select>';
        
        $html = '<label for="booking_calendar_day"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }

    public static function booking_export_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $export_ics = "";
        if ( isset( $saved_settings->booking_export ) && $saved_settings->booking_export == 'on' ) {
            $export_ics = 'checked';
        }
        echo '<input type="checkbox" id="booking_export" name="woocommerce_booking_global_settings[booking_export]" ' . $export_ics . '/>';
        $html = '<label for="booking_export"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function booking_attachment_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $email_ics = "";
        if ( isset( $saved_settings->booking_attachment ) && $saved_settings->booking_attachment == 'on' ) {
            $email_ics = 'checked';
        }
        
        echo '<input type="checkbox" id="booking_attachment" name="woocommerce_booking_global_settings[booking_attachment]" ' . $email_ics . '/>';
        $html = '<label for="booking_attachment"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function booking_theme_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        if( isset( $saved_settings ) ) {
            $booking_theme = $saved_settings->booking_themes;   
        }
        
        $global_holidays = "";
        if ( isset( $saved_settings ) ) {
            if ( $saved_settings->booking_global_holidays != "" ) {
                $global_holidays = "addDates: ['" . str_replace( ",", "','", $saved_settings->booking_global_holidays ) . "']";
            }
        }
        
        $language_selected = "";
        if ( isset( $saved_settings->booking_language ) ) {
            $language_selected = $saved_settings->booking_language;
        }
        
        if ( $language_selected == "" ) {
            $language_selected = "en-GB";
        }

        $bkap_get_start_day_of_week = ( ! isset( $saved_settings->booking_calendar_day ) ) ? get_option( "start_of_week" ) : $saved_settings->booking_calendar_day;
        
        echo '<script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery( "#booking_new_switcher" ).themeswitcher({
                    onclose: function() {
                        var cookie_name = this.cookiename;
                        jQuery( "input#booking_themes" ).val( jQuery.cookie( cookie_name ) );
                    },
                    imgpath: "' . plugins_url().'/woocommerce-booking/images/",
                    loadTheme: "smoothness"
                });
                var date = new Date();
                jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "en-GB" ] );
                jQuery( "#booking_switcher" ).multiDatesPicker({
                    dateFormat: "d-m-yy",
                    minDate: "0",
                    altField: "#booking_global_holidays",
                    firstDay: parseInt( '. $bkap_get_start_day_of_week .' ),
                    ' . $global_holidays . '
                });
                jQuery(function() {
                    jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "" ] );
                    jQuery( "#booking_switcher" ).datepicker( jQuery.datepicker.regional[ "en-GB" ] );
                    jQuery( "#booking_new_switcher" ).datepicker( jQuery.datepicker.regional[ "' . $language_selected . '" ] );
                    jQuery( "#booking_language" ).change(function() {
                        jQuery( "#booking_new_switcher" ).datepicker( "option", jQuery.datepicker.regional[ jQuery(this).val() ] );
                    });
                    jQuery(".ui-datepicker-inline").css("font-size","1.4em");
                });
            });
        </script>
        <div id="booking_new_switcher" name="booking_new_switcher"></div>';
        
        echo '<input type="hidden" name="woocommerce_booking_global_settings[booking_themes]" id="booking_themes" value="' . $booking_theme . '">';
        $html = '<label for="booking_theme"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function booking_global_holidays_callback( $args ) {
        echo '<textarea rows="4" cols="80" name="woocommerce_booking_global_settings[booking_global_holidays]" id="booking_global_holidays"></textarea>
        <div id="booking_switcher" name="booking_switcher"></div>';
        
        $html = '<label for="booking_global_holidays"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function booking_global_timeslot_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $global_timeslot = "";
        if ( isset( $saved_settings->booking_global_timeslot ) && $saved_settings->booking_global_timeslot == 'on' ) {
            $global_timeslot = "checked";
        }
        echo '<input type="checkbox" id="booking_global_timeslot" name="woocommerce_booking_global_settings[booking_global_timeslot]" ' . $global_timeslot . '/>';
        $html = '<label for="booking_global_timeslot"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function booking_enable_rounding_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $rounding = "";
        if ( isset( $saved_settings->enable_rounding ) && $saved_settings->enable_rounding == 'on' ) {
            $rounding = 'checked';
        }
        echo '<input type="checkbox" id="enable_rounding" name="woocommerce_booking_global_settings[enable_rounding]" ' . $rounding .'/>';
        $html = '<label for="enable_rounding"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function hide_variation_price_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $price_display = "";
        $price_hide = '';
        if ( isset( $saved_settings->hide_variation_price ) && $saved_settings->hide_variation_price == 'on' ) {
            $price_display = 'checked';
        }
        echo '<input type="checkbox" id="hide_variation_price" name="woocommerce_booking_global_settings[hide_variation_price]" ' . $price_display . '/>';
        $html = '<label for="hide_variation_price"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function hide_booking_price_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
    
        $price_hide = "";
        if ( isset( $saved_settings->hide_booking_price ) && $saved_settings->hide_booking_price == 'on' ) {
            $price_hide = 'checked';
        }
        echo '<input type="checkbox" id="hide_booking_price" name="woocommerce_booking_global_settings[hide_booking_price]" ' . $price_hide . '/>';
        $html = '<label for="hide_booking_price"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function display_disabled_buttons_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $buttons_display = "";
    
        if ( isset( $saved_settings->display_disabled_buttons ) && $saved_settings->display_disabled_buttons == 'on' ) {
            $buttons_display = 'checked';
        }
        echo '<input type="checkbox" id="display_disabled_buttons" name="woocommerce_booking_global_settings[display_disabled_buttons]" ' . $buttons_display . '/>';
        $html = '<label for="display_disabled_buttons"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function booking_global_selection_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $global_selection = "";
        if ( isset( $saved_settings->booking_global_selection ) && $saved_settings->booking_global_selection == 'on' ){
            $global_selection = "checked";
        }
        echo '<input type="checkbox" id="booking_global_selection" name="woocommerce_booking_global_settings[booking_global_selection]" ' . $global_selection . '/>';
        $html = '<label for="booking_global_selection"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function booking_availability_display_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $availability_display = "";
        if ( isset( $saved_settings->booking_availability_display ) && $saved_settings->booking_availability_display == 'on' ) {
            $availability_display = "checked";
        }
        echo '<input type="checkbox" id="booking_availability_display" name="woocommerce_booking_global_settings[booking_availability_display]" ' . $availability_display . '/>';
        $html = '<label for="booking_availability_display"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function resource_price_per_day_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $resource_price_per_day = "";
        if ( isset( $saved_settings->resource_price_per_day ) && $saved_settings->resource_price_per_day == 'on' ){
            $resource_price_per_day = "checked";
        }
        echo '<input type="checkbox" id="resource_price_per_day" name="woocommerce_booking_global_settings[resource_price_per_day]" ' . $resource_price_per_day . '/>';
        $html = '<label for="resource_price_per_day"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function woo_product_addon_price_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $woo_product_addon_price = "";
        if ( isset( $saved_settings->woo_product_addon_price ) && $saved_settings->woo_product_addon_price == 'on' ){
            $woo_product_addon_price = "checked";
        }
        echo '<input type="checkbox" id="woo_product_addon_price" name="woocommerce_booking_global_settings[woo_product_addon_price]" ' . $woo_product_addon_price . '/>';
        $html = '<label for="woo_product_addon_price"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function woo_gf_product_addon_option_price_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $woo_gf_product_addon_option_price = "";
        if ( isset( $saved_settings->woo_gf_product_addon_option_price ) && $saved_settings->woo_gf_product_addon_option_price == 'on' ){
            $woo_gf_product_addon_option_price = "checked";
        }
        echo '<input type="checkbox" id="woo_gf_product_addon_option_price" name="woocommerce_booking_global_settings[woo_gf_product_addon_option_price]" ' . $woo_gf_product_addon_option_price . '/>';
        $html = '<label for="woo_gf_product_addon_option_price"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function minimum_day_booking_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $minimum_booking_checked  = "";
        $minimum_days_div_show    = 'none';
        if ( isset( $saved_settings->minimum_day_booking ) && $saved_settings->minimum_day_booking == 'on' ) {
            $minimum_booking_checked  = 'checked';
        }
        echo '<input type="checkbox" id="minimum_day_booking" name="woocommerce_booking_global_settings[minimum_day_booking]" onClick="minimum_days_method(this)" ' . $minimum_booking_checked .'/>';
        
        echo '<script type="text/javascript">
            function minimum_days_method( chk ) {
                if ( jQuery( "input[id=\'minimum_day_booking\']").attr( "checked" ) ) {
                    jQuery( "#global_booking_minimum_number_days" ).removeAttr( "disabled" );        
            
                }
                
                if ( !jQuery( "input[id=\'minimum_day_booking\' ]" ).attr( "checked" ) ) {
                    jQuery( "#global_booking_minimum_number_days" ).attr( "disabled", "disabled" );        
                }
            }
        </script>';
        $html = '<label for="minimum_day_booking"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function global_booking_minimum_number_days_callback( $args ) {
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $minimum_day = "";
        if ( isset( $saved_settings->global_booking_minimum_number_days ) && $saved_settings->global_booking_minimum_number_days != "" ) {
            $minimum_day = $saved_settings->global_booking_minimum_number_days;
        } else {
            $minimum_day = "0";
        }
        
        if ( isset( $saved_settings->minimum_day_booking ) && $saved_settings->minimum_day_booking == 'on' ) {
            echo '<input type="text" name="woocommerce_booking_global_settings[global_booking_minimum_number_days]" id="global_booking_minimum_number_days" value="' . $minimum_day .'"/>';
        } else {
            echo '<input type="text" name="woocommerce_booking_global_settings[global_booking_minimum_number_days]" id="global_booking_minimum_number_days" value="' . $minimum_day .'" disabled="disabled"/>';
        }
        
        $html = '<label for="global_booking_minimum_number_days"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function woocommerce_booking_global_settings_callback( $input ) {
        $woocommerce_booking_global_settings = json_encode( $input );
        return $woocommerce_booking_global_settings;
    }
    
    public static function bkap_booking_product_page_labels_section_callback() { }
    
    public static function book_date_label_callback( $args ) {
        $book_date_label = "";
        if ( get_option( 'book_date-label' ) != '' ) {
            $book_date_label = get_option( 'book_date-label' );
        }
        echo '<input type="text" name="book_date-label" id="book_date-label" value="' . $book_date_label . '"/>';
        $html = '<label for="book_date-label"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function checkout_date_label_callback( $args ) {
        $checkout_date_label = "";
        if ( get_option( 'checkout_date-label' ) != '' ) {
            $checkout_date_label = get_option( 'checkout_date-label' );
        }
        echo '<input type="text" name="checkout_date-label" id="checkout_date-label" value="' . $checkout_date_label . '"/>';
        $html = '<label for="checkout_date-label"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function bkap_calendar_icon_label_callback( $args ) {
        $calendar_icon_label = "";
        if ( get_option( 'bkap_calendar_icon_file' ) != '' ) {
            $calendar_icon_label = get_option( 'bkap_calendar_icon_file' );
        }
		$calendar_icons          = bkap_get_book_arrays( 'bkap_calendar_icons' );
		
		$html = '';
		foreach( $calendar_icons as $ckey => $cvalue ) {
			if ( 'none' != $cvalue ) {
				if ( ( '' != $calendar_icon_label && $cvalue == $calendar_icon_label ) ||
					 ( '' == $calendar_icon_label && $cvalue == 'calendar1.gif' ) ) {
					$calendar_icon_html = '<img class="bkap_calendar_icon" data-id="' . $cvalue . '" src="' . plugins_url( 'images/' . $cvalue, __FILE__ ) . '" style="margin-right:20px;border:7px solid #0071ff;" height="30" width="30"/>';
				} else {
					$calendar_icon_html = '<img class="bkap_calendar_icon" data-id="' . $cvalue . '" src="' . plugins_url( 'images/' . $cvalue, __FILE__ ) . '" style="margin-right:20px;" height="30" width="30"/>';
				}
			} else {
				if ( '' != $calendar_icon_label && $cvalue == $calendar_icon_label ) {
					$calendar_icon_html = '<a href="javascript:void(0);" class="bkap_calendar_icon" data-id="none" style="margin-right:20px;border:7px solid #0071ff;">' . __( 'Remove Icon', 'woocommerce-booking' ) . '</a>';
				} else {
					$calendar_icon_html = '<a href="javascript:void(0);" class="bkap_calendar_icon" data-id="none">' . __( 'Remove Icon', 'woocommerce-booking' ) . '</a>';
				}
			}
			$html .= $calendar_icon_html;
		}
		$html .= '<input type="radio" name="bkap_calendar_icon_file" id="bkap_calendar_icon_file" value="" style="display:none;"/>';
		echo $html;
    }
        
    public static function book_time_label_callback( $args ) {
        $book_time_label = "";
        if ( get_option( 'book_time-label' ) != '' ) {
            $book_time_label = get_option( 'book_time-label' );
        }
        echo '<input type="text" name="book_time-label" id="book_time-label" value="' . $book_time_label . '"/>';
        $html = '<label for="book_time-label"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function book_time_select_option_callback( $args ) {
        $book_time_select_option = "";
        if ( get_option( 'book_time-select-option' ) != '' ) {
            $book_time_select_option = get_option( 'book_time-select-option' );
        }
        echo '<input type="text" name="book_time-select-option" id="book_time-select-option" value="' . $book_time_select_option . '"/>';
        $html = '<label for="book_time-select-option"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function book_fixed_block_label_callback( $args ) {
        $book_fixed_block_label = "";
        if ( get_option( 'book_fixed-block-label' ) != '' ) {
            $book_fixed_block_label = get_option( 'book_fixed-block-label' );
        }
        echo '<input type="text" name="book_fixed-block-label" id="book_fixed-block-label" value="' . esc_attr( $book_fixed_block_label ) . '"/>';
        $html = '<label for="book_fixed-block-label"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function book_price_label_callback( $args ) {
        $book_price_abel = "";
        if ( get_option( 'book_price-label' ) != '' ) {
            $book_price_abel = get_option( 'book_price-label' );
        }
        echo '<input type="text" name="book_price-label" id="book_price-label" value="' . $book_price_abel . '"/>';
        $html = '<label for="book_price-label"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function bkap_booking_order_received_and_email_labels_section_callback() { }
    
    public static function book_item_meta_date_callback( $args ) {
        $book_item_meta_date = "";
        if ( get_option( 'book_item-meta-date' ) != '' ) {
            $book_item_meta_date = get_option( 'book_item-meta-date' );
        }
        echo '<input type="text" name="book_item-meta-date" id="book_item-meta-date" value="' . $book_item_meta_date . '"/>';
        $html = '<label for="book_item-meta-date"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function checkout_item_meta_date_callback( $args ) {
        $checkout_item_meta_date = "";
        if ( get_option( 'checkout_item-meta-date' ) != '' ) {
            $checkout_item_meta_date = get_option( 'checkout_item-meta-date' );
        }
        echo '<input type="text" name="checkout_item-meta-date" id="checkout_item-meta-date" value="' . $checkout_item_meta_date . '"/>';
        $html = '<label for="checkout_item-meta-date"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function book_item_meta_time_callback( $args ) {
        $book_item_meta_time = "";
        if ( get_option( 'book_item-meta-time' ) != '' ) {
            $book_item_meta_time = get_option( 'book_item-meta-time' );
        }
        echo '<input type="text" name="book_item-meta-time" id="book_item-meta-time" value="' . $book_item_meta_time . '"/>';
        $html = '<label for="book_item-meta-time"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function book_ics_file_name_callback( $args ) {
        $book_ics_file_name = "";
        if ( get_option( 'book_ics-file-name' ) != '' ) {
            $book_ics_file_name = get_option( 'book_ics-file-name' );
        }
        echo '<input type="text" name="book_ics-file-name" id="book_ics-file-name" value="' . $book_ics_file_name . '"/>';
        $html = '<label for="book_ics-file-name"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function bkap_booking_cart_and_checkout_page_labels_section_callback() { }
    
    public static function book_item_cart_date_callback( $args ) {
        $book_item_cart_date = "";
        if ( get_option( 'book_item-cart-date' ) != '' ) {
            $book_item_cart_date = get_option( 'book_item-cart-date' );
        }
        echo '<input type="text" name="book_item-cart-date" id="book_item-cart-date" value="' . $book_item_cart_date . '"/>';
        $html = '<label for="book_item-cart-date"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function checkout_item_cart_date_callback( $args ) {
        $checkout_item_cart_date = "";
        if ( get_option( 'checkout_item-cart-date' ) != '' ) {
            $checkout_item_cart_date = get_option( 'checkout_item-cart-date' );
        }
        echo '<input type="text" name="checkout_item-cart-date" id="checkout_item-cart-date" value="' . $checkout_item_cart_date . '"/>';
        $html = '<label for="checkout_item-cart-date"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function book_item_cart_time_callback( $args ) {
        $book_item_cart_time  = "";
        if ( get_option( 'book_item-cart-time' ) != '' ) {
            $book_item_cart_time = get_option( 'book_item-cart-time' );
        }
        echo '<input type="text" name="book_item-cart-time" id="book_item-cart-time" value="' . $book_item_cart_time . '"/>';
        $html = '<label for="book_item-cart-time"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }

    public static function bkap_add_to_cart_button_labels_section_callback() { }

    public static function bkap_add_to_cart_button_text_callback( $args ) {
        $bkap_add_to_cart  = "";
        if ( get_option( 'bkap_add_to_cart' ) != '' ) {
            $bkap_add_to_cart = get_option( 'bkap_add_to_cart' );
        }
        echo '<input type="text" name="bkap_add_to_cart" id="bkap_add_to_cart" value="' . $bkap_add_to_cart . '"/>';
        $html = '<label for="bkap_add_to_cart"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }

    public static function bkap_check_availability_text_callback( $args ) {
        $bkap_check_availability  = "";
        if ( get_option( 'bkap_check_availability' ) != '' ) {
            $bkap_check_availability = get_option( 'bkap_check_availability' );
        }
        echo '<input type="text" name="bkap_check_availability" id="bkap_check_availability" value="' . $bkap_check_availability . '"/>';
        $html = '<label for="bkap_check_availability"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function bkap_booking_availability_messages_section_callback() {}
    
    public static function book_stock_total_callback( $args ) {
    
        $book_stock_total  = "";
        if ( get_option( 'book_stock-total' ) != '' ) {
            $book_stock_total = get_option( 'book_stock-total' );
        }
        echo '<textarea rows="3" cols="60" name="book_stock-total" id="book_stock-total" style="width:250px;">' . $book_stock_total . '</textarea>';
        $html = '<label for="book_stock-total"> ' . $args[ 0 ] . '</label>';
        echo $html;
    
    }
    
    public static function book_available_stock_date_callback( $args ) {
    
        $book_available_stock_date  = "";
        if ( get_option( 'book_available-stock-date' ) != '' ) {
            $book_available_stock_date = get_option( 'book_available-stock-date' );
        }
        echo '<textarea rows="3" cols="60" name="book_available-stock-date" id="book_available-stock-date" style="width:250px;">' . $book_available_stock_date . '</textarea>';
        $html = '<label for="book_available-stock-date"> ' . $args[ 0 ] . '</label>';
        echo $html;
    
    }
    
    public static function book_available_stock_time_callback( $args ) {
    
        $book_available_stock_time  = "";
        if ( get_option( 'book_available-stock-time' ) != '' ) {
            $book_available_stock_time = get_option( 'book_available-stock-time' );
        }
        echo '<textarea rows="3" cols="60" name="book_available-stock-time" id="book_available-stock-time" style="width:250px;">' . $book_available_stock_time . '</textarea>';
        $html = '<label for="book_available-stock-time"> ' . $args[ 0 ] . '</label>';
        echo $html;
    
    }
    
    public static function book_available_stock_date_attr_callback( $args ) {
    
        $book_available_stock_date_attr  = "";
        if ( get_option( 'book_available-stock-date-attr' ) != '' ) {
            $book_available_stock_date_attr = get_option( 'book_available-stock-date-attr' );
        }
        echo '<textarea rows="3" cols="60" name="book_available-stock-date-attr" id="book_available-stock-date-attr" style="width:250px;">' . $book_available_stock_date_attr . '</textarea>';
        $html = '<label for="book_available-stock-date-attr"> ' . $args[ 0 ] . '</label>';
        echo $html;
    
    }
    
    public static function book_available_stock_time_attr_callback( $args ) {
    
        $book_available_stock_time_attr  = "";
        if ( get_option( 'book_available-stock-time-attr' ) != '' ) {
            $book_available_stock_time_attr = get_option( 'book_available-stock-time-attr' );
        }
        echo '<textarea rows="3" cols="60" name="book_available-stock-time-attr" id="book_available-stock-time-attr" style="width:250px;">' . $book_available_stock_time_attr . '</textarea>';
        $html = '<label for="book_available-stock-time-attr"> ' . $args[ 0 ] . '</label>';
        echo $html;
    
    }

    public static function book_real_time_error_msg_callback( $args ) {
    
        $book_real_time_error_msg  = "";
        if ( get_option( 'book_real-time-error-msg' ) != '' ) {
            $book_real_time_error_msg = get_option( 'book_real-time-error-msg' );
        }
        echo '<textarea rows="3" cols="60" name="book_real-time-error-msg" id="book_real-time-error-msg" style="width:250px;">' . $book_real_time_error_msg . '</textarea>';
        $html = '<label for="book_real-time-error-msg"> ' . $args[ 0 ] . '</label>';
        echo $html;
    
    }
    
    public static function bkap_booking_lockout_messages_section_callback() {}
    
    public static function book_limited_booking_msg_date_callback( $args ) {
    
        $book_limited_booking_msg_date = "";
        if ( get_option( 'book_limited-booking-msg-date' ) != '' ) {
            $book_limited_booking_msg_date = get_option( 'book_limited-booking-msg-date' );
        }
        echo '<textarea rows="3" cols="60" name="book_limited-booking-msg-date" id="book_limited-booking-msg-date" style="width:250px;">' . $book_limited_booking_msg_date . '</textarea>';
        $html = '<label for="book_limited-booking-msg-date"> ' . $args[ 0 ] . '</label>';
        echo $html;
    
    }
    
    public static function book_no_booking_msg_date_callback( $args ) {
    
        $book_no_booking_msg_date = "";
        if ( get_option( 'book_no-booking-msg-date' ) != '' ) {
            $book_no_booking_msg_date = get_option( 'book_no-booking-msg-date' );
        }
        echo '<textarea rows="3" cols="60" name="book_no-booking-msg-date" id="book_no-booking-msg-date" style="width:250px;">' . $book_no_booking_msg_date . '</textarea>';
        $html = '<label for="book_no-booking-msg-date"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function book_limited_booking_msg_time_callback( $args ) {
    
        $book_limited_booking_msg_time = "";
        if ( get_option( 'book_limited-booking-msg-time' ) != '' ) {
            $book_limited_booking_msg_time = get_option( 'book_limited-booking-msg-time' );
        }
        echo '<textarea rows="3" cols="60" name="book_limited-booking-msg-time" id="book_limited-booking-msg-time" style="width:250px;">' . $book_limited_booking_msg_time . '</textarea>';
        $html = '<label for="book_limited-booking-msg-time"> ' . $args[ 0 ] . '</label>';
        echo $html;
    
    }
    
    public static function book_no_booking_msg_time_callback( $args ) {
    
        $book_no_booking_msg_time = "";
        if ( get_option( 'book_no-booking-msg-time' ) != '' ) {
            $book_no_booking_msg_time = get_option( 'book_no-booking-msg-time' );
        }
        echo '<textarea rows="3" cols="60" name="book_no-booking-msg-time" id="book_no-booking-msg-time" style="width:250px;">' . $book_no_booking_msg_time . '</textarea>';
        $html = '<label for="book_no-booking-msg-time"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function book_limited_booking_msg_date_attr_callback( $args ) {
    
        $book_limited_booking_msg_date_attr = "";
        if ( get_option( 'book_limited-booking-msg-date-attr' ) != '' ) {
            $book_limited_booking_msg_date_attr = get_option( 'book_limited-booking-msg-date-attr' );
        }
        echo '<textarea rows="3" cols="60" name="book_limited-booking-msg-date-attr" id="book_limited-booking-msg-date-attr" style="width:250px;">' . $book_limited_booking_msg_date_attr . '</textarea>';
        $html = '<label for="book_limited-booking-msg-date-attr"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
    
    public static function book_limited_booking_msg_time_attr_callback( $args ) {
    
        $book_limited_booking_msg_time_attr = "";
        if ( get_option( 'book_limited-booking-msg-time-attr' ) != '' ) {
            $book_limited_booking_msg_time_attr = get_option( 'book_limited-booking-msg-time-attr' );
        }
        echo '<textarea rows="3" cols="60" name="book_limited-booking-msg-time-attr" id="book_limited-booking-msg-time-attr" style="width:250px;">' . $book_limited_booking_msg_time_attr . '</textarea>';
        $html = '<label for="book_limited-booking-msg-time-attr"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }
}