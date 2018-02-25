/**
 * JS Helper functions for Booking Template
 * @namespace bkap_functions
 * @since 4.1.0
 */

var bkap_functions = function ( $ ) {

    return {

        /**
         * This function updates the GF prices
         *
         * @function update_GF_prices
         * @memberof bkap_functions
         * @since 4.1.0
         */
        update_GF_prices: function() {

            var options                 = parseFloat( $( ".ginput_container_total" ).find( ".gform_hidden" ).val() ),
                booking_price_charged   = $( "#bkap_price_charged" ).val(),
                booking_price           = 0,
                pricing_obj             = bkap_functions.update_option_prices( 'gf', options );

            $( "#bkap_gf_options_total" ).val( pricing_obj.cart_options_total );

            if ( parseFloat( pricing_obj.total_booking_price ) > parseFloat( booking_price_charged ) ) {
                booking_price = $( "#total_price_calculated" ).val();
            } else {
                booking_price = $( "#bkap_price_charged" ).val() - pricing_obj.options_total; // the subtotal should not include the gf options 
            }

            if ( typeof(wc_gravityforms_params) !== 'undefined' ) {
                if ( $( ".formattedBasePrice" ).length > 0 ) {
                    $( ".formattedBasePrice" ).html( bkap_functions.bkap_format_money( wc_gravityforms_params, parseFloat( booking_price ) ) );
                }

                if ( $( ".formattedVariationTotal" ).length > 0 ) {
                    $( ".formattedVariationTotal" ).html( bkap_functions.bkap_format_money( wc_gravityforms_params, parseFloat( pricing_obj.options_total ) ) );
                }

                if ( $( ".formattedTotalPrice" ).length > 0 ) {
                    var formatted_total = parseFloat( booking_price ) + parseFloat( pricing_obj.options_total );
                    $( ".formattedTotalPrice" ).html( bkap_functions.bkap_format_money( wc_gravityforms_params, formatted_total ) );
                }
            }
        },

        /**
         * Updates WooCommerce Product Addon Prices
         *
         * @function update_wpa_prices
         * @memberof bkap_functions
         * @since 4.2
         */
        update_wpa_prices: function() {

            var options                 = '',
                booking_price_charged   = $( "#bkap_price_charged" ).val(),
                booking_price           = 0,
                pricing_obj             = {},
                $totals                 = $('body').find( '#product-addons-total' ),
                html                    = '',
                formatted_options       = '',
                formatted_total         = '',
                subtotal                = '';

            options = $totals.data('addons-price');
            pricing_obj = bkap_functions.update_option_prices( 'wpa', options );

            booking_price = pricing_obj.options_total + parseFloat($( "#total_price_calculated" ).val());
            
            if ( typeof(woocommerce_addons_params) !== 'undefined' ) {
                formatted_options = bkap_functions.bkap_format_money( woocommerce_addons_params, pricing_obj.options_total );
                formatted_total = bkap_functions.bkap_format_money( woocommerce_addons_params, booking_price );

                if ( woocommerce_addons_params.i18n_grand_total ) {
                    subtotal = woocommerce_addons_params.i18n_grand_total;
                }else if ( woocommerce_addons_params.i18n_sub_total ) {
                    subtotal = woocommerce_addons_params.i18n_sub_total;
                }

                html = '<dl class="product-addon-totals"><dt>' + woocommerce_addons_params.i18n_addon_total + '</dt><dd><strong><span class="amount">' + formatted_options + '</span></strong></dd>';
                html = html + '<dt>' + subtotal + '</dt><dd><strong><span class="amount">' + formatted_total + '</span></strong></dd></dl>';
                $totals.html( html );
            }
        },

        /**
         * Updates WooCommerce Product Addon Prices
         *
         * @function update_option_prices
         * @memberof bkap_functions
         * @param {string} addon_type - Addon type
         * @param {string} options - Options total price
         * @returns {object} Calculated totals
         * @since 4.2
         */
        update_option_prices: function( addon_type, options ) {

            var global_settings         = JSON.parse( bkap_process_params.global_settings ),
                total_booking_price     = parseFloat( $( "#total_price_calculated ").val() ),
                diff_days               = $( "#wapbk_diff_days" ).val(),
                quantity                = $( "input[class=\"input-text qty text\"]" ).prop( "value" ),
                options_total           = 0,
                cart_options_total      = 0,
                bkap_setting            = '';

            if ( options > 0 ) {
                options_total = options;
            }

            if ( addon_type === 'gf' ) {
                bkap_setting = global_settings.woo_gf_product_addon_option_price;
            }else if ( addon_type === 'wpa' ){
                bkap_setting = global_settings.woo_product_addon_price;
            }

            if ( diff_days > 1 && bkap_setting === "on" && options_total > 0 ) {
                options_total = options * diff_days;
                cart_options_total = options_total;
            } else {
                cart_options_total = options;
            }

            if ( typeof quantity == "undefined" ) {
                quantity = 1;
            }
            
            // if cart_options_total is greater than 0, multiply with the qty
            if ( cart_options_total > 0 ) {
                cart_options_total = cart_options_total * quantity;
            }
            
            // if options_total is greater than 0, multiply with the qty
            if ( options_total > 0 ) {
                options_total = options_total * quantity;
            }

            total_booking_price = total_booking_price + options_total;

            /**
             * Indicates that the pop-up is visible now
             * 
             * @event bkap_update_addon_prices
             * @param {string} bkap_cart_item_key - Cart Item Key
             * @param {string} options_total - Addon Options Totals
             * @since 4.2.0
             */
            $( 'body' ).trigger( 'bkap_update_addon_prices', [ bkap_process_params.bkap_cart_item_key, options_total ] );

            return {
                'cart_options_total': cart_options_total,
                'options_total': options_total,
                'total_booking_price': total_booking_price
            };
        },

        /**
         * Format money as per currency selected
         *
         * @function bkap_format_money
         * @memberof bkap_functions
         * @param {object} param_name - Addon Param Name
         * @param {string|float} price - Price to be formatted
         * @returns {float} Formatted Currency
         * @since 4.2
         */ 
        bkap_format_money: function( param_name, price ) {

            return accounting.formatMoney( parseFloat( price ), {
                symbol      : param_name['currency_format_symbol'],
                decimal     : param_name['currency_format_decimal_sep'],
                thousand    : param_name['currency_format_thousand_sep'],
                precision   : param_name['currency_format_num_decimals'],
                format      : param_name['currency_format']
            });
        },

        /**
         * Get selected products for composite products
         *
         * @function bkap_get_composite_selections
         * @memberof bkap_functions
         * @returns {object} Component Selected Data
         * @since 4.7.0
         */ 
        bkap_get_composite_selections: function() {

            var components        = '',
                component_id      = '',
                component_data    = {},
                selected_product  = '',
                selected_quantity = '';

            components = $('.component');
            for( var sub_comp in components ){
                if ( 'object' === typeof( components[sub_comp] ) && $( components[sub_comp] ).data('item_id') ) {
                    component_id = $( components[sub_comp] ).data('item_id');
                    selected_product = $('#component_options_' + component_id ).val();
                    selected_quantity = $('input[name="wccp_component_quantity[' + component_id + ']"').val();

                    component_data[component_id] = {};
                    component_data[component_id]['p_id'] = selected_product;
                    component_data[component_id]['qty'] = selected_quantity;
                }
            }

            return component_data;
        },

        /**
         * Set Checkout date for inline calendar
         *
         * @function test_bkap_init_inline
         * @memberof bkap_functions
         * @since 4.1.0
         */
        test_bkap_init_inline: function() {
            // extra code Pinal
            var checkin_date  = $( "#booking_calender" ).val(),
                days          = $( "#block_option_number_of_day" ).val(),
                data          = {},
                res           = '',
                split         = '',
                checkout_date = '';

            data = {
                current_date: checkin_date,
                add_days    : days,
                action      : 'bkap_get_fixed_block_inline_date'
            };

            $( "#ajax_img" ).show();

            $.post( bkap_process_params.ajax_url, data, function( response ) {
                $( "#ajax_img" ).hide();

                res             = response.substring(0, 10);
                split           = res.split("-");
                checkout_date   = new Date(split[0], split[1] - 1, split[2]);

                //$( "#booking_calendar_checkout" ).datepicker( "setDate" ,checkout_date);
                $( "#inline_calendar_checkout" ).datepicker( "setDate", checkout_date );
                date = bkap_functions.bkap_create_date(checkout_date);
                jQuery("#wapbk_hidden_date_checkout").val(date);

                bkap_calculate_price();
            });
        },
        
        /**
         * Create date in j-n-y format
         *
         * param {Date object} date - Date
         * @function bkap_create_date
         * @memberof bkap_functions
         */

        bkap_create_date: function( date ) {
        	var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
        	today = d + "-" + (m+1) + "-" + y;
        	
        	return today;
        },

		bkap_init: function() {

			var min_date = jQuery( "#wapbk_minimum_seach_date" ).val().split( "-" );

			var checkout_date = new Date( min_date[2], min_date[1], min_date[0] );
			jQuery( "#booking_calender_checkout" ).datepicker( "option", "minDate", checkout_date );
		},

		/**
         * Set Checkout date for Fixed Blocks configuration and calculate prices
         *
         * @function test_bkap_init
         * @memberof bkap_functions
         */
		test_bkap_init: function() { 
			var checkin_date = jQuery( "#booking_calender" ).val();
			var days = jQuery( "#block_option_number_of_day" ).val();
			var data = {
				current_date: checkin_date,
				add_days	: days,
				action: 'bkap_get_fixed_block_inline_date'
			};
			jQuery( "#ajax_img" ).show();
			jQuery.post( bkap_process_params.ajax_url, data, function( response ) {
				jQuery( "#ajax_img" ).hide();
				var res = response.substring(0, 10);
				var split = res.split("-");
				var Checkout_date_test = new Date( split[0], split[1] - 1, split[2] ); 
				jQuery( "#wapbk_hidden_date_checkout" ).val(Checkout_date_test);
				jQuery( "#booking_calender_checkout" ).datepicker( "setDate", Checkout_date_test );
				bkap_calculate_price();
			});
		},

		/**
         * Set Checkout date for inline calendar
         *
         * @function bkap_init_inline_multiple
         * @memberof bkap_functions
         * @param {object} global_settings - Global Settings
         * @param {object} bkap_settings - Product Level Settings
         * @param {object} settings - Additional Data
         * @since 4.1.0
         */
		bkap_init_inline_multiple: function( global_settings, bkap_settings, settings ) {

			// This fix is when the next day is holiday and same day booking is enable. 
		    if( settings.wapbk_same_day === "on") { 
				var checkin_date = jQuery("#inline_calendar").datepicker( "getDate" );
				var date = checkin_date.getDate();
				var month = checkin_date.getMonth() + 1;
				var year = checkin_date.getFullYear();

				var date_selected = date + "-" + month + "-" + year;
				jQuery("#wapbk_hidden_date_checkout").val( date_selected );     
			}else{
				var checkout_date = jQuery("#inline_calendar_checkout").datepicker( "getDate" );
				var date = checkout_date.getDate();
				var month = checkout_date.getMonth() + 1;
				var year = checkout_date.getFullYear();

				var date_selected_checkout = date + "-" + month + "-" + year;
				jQuery("#wapbk_hidden_date_checkout").val( date_selected_checkout );
		    }
		    if ( date_selected != "" && date_selected_checkout != "" ){
				bkap_calculate_price();
		    }
		},

		/**
		 * This function disables the dates in the calendar for holidays, 
		 * global holidays set and for which lockout is reached for Multiple day booking feature.
		 *
		 * @function bkap_check_booked_dates
		 * @memberof bkap_functions
		 * @param {date} date - Date to be checked
		 * @returns {bool} Returns true or false based on date available or not
		 * @since 4.1.0
		 */
		bkap_check_booked_dates: function( date ) {
			
			var settings 			= JSON.parse( bkap_process_params.additional_data );
			var bkap_settings 		= JSON.parse( bkap_process_params.bkap_settings );
			var labels 				= JSON.parse( bkap_process_params.labels );

			var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
			var holidayDates 		= eval( "[" + settings.holidays + "]" );

			//var globalHolidays = eval( "[" + jQuery("#wapbk_booking_global_holidays").val() + "]" );

			var bookedDates 				= JSON.parse( "[" + settings.wapbk_hidden_booked_dates + "]" );
			var bookedDatesCheckout 		= JSON.parse( "[" + settings.wapbk_hidden_booked_dates_checkout + "]" );
			var block_option_start_day		= jQuery( "#block_option_start_day" ).val();
			var block_option_price			= jQuery( "#block_option_price" ).val();

			var disabled_checkin_week_days 	= eval( "[" + settings.wapbk_block_checkin_weekdays + "]" );
			var disabled_checkout_week_days = eval( "[" + settings.wapbk_block_checkout_weekdays + "]" );
			
			var maximum_numbers_of_days 	= parseInt( settings.number_of_dates );

			var id_booking = jQuery(this).attr("id");
			var bkap_rent = eval( "[" + settings.bkap_rent + "]" );
			if ( id_booking == "booking_calender" || id_booking == "inline_calendar" ) {

				for ( iii = 0; iii < bookedDates.length; iii++ ) {
					if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ){
						if( bkap_rent.length > 0 ) { 
							return [false, "", labels.rent_label ];
						} else {
							return [false, "", labels.unavailable_label ];							
						}
					}
				}
				for ( jjj = 0; jjj < disabled_checkin_week_days.length; jjj++ ) {
					if( jQuery.inArray( date.getDay(), disabled_checkin_week_days) != -1 ) {
						return [false, "", labels.blocked_label ];
					}
				}
				
				for ( ii = 0; ii < holidayDates.length; ii++ ) {
					if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
						return [false, "",labels.holiday_label ];
					}
				}
			}
			
			
			if ( id_booking == "booking_calender_checkout" || id_booking == "inline_calendar_checkout" ) {
				
				for ( iii = 0; iii < bookedDatesCheckout.length; iii++ ) {
					if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDatesCheckout) != -1 ) {
						return [false, "", labels.unavailable_label ];
					}
				}

				for ( jjj = 0; jjj < disabled_checkout_week_days.length; jjj++ ) {
					if( jQuery.inArray( date.getDay(), disabled_checkout_week_days) != -1 ) {
						return [false, "", labels.blocked_label ];
					}
				}

				// Allowing to select holidays as checkout dates.
				if ( jQuery("#wapbk_hidden_date").val() != "" ) {
    					            
			        var m1 = d1 = y1 = "";

			        var split_c        = jQuery("#wapbk_hidden_date").val().split("-");
					split_c[1]         = split_c[1] - 1;		
					var  CheckinDate   = new Date( split_c[2], split_c[1], split_c[0] );
			        
			        // Enable check-out date when product level holiday    
				    for ( iii = 1; iii < maximum_numbers_of_days ; iii++ ) {
				    	var res = CheckinDate.getTime() + (iii * 24 * 60 * 60 * 1000);
				    	var date_holidays 	= new Date(res);

			            m1                 = date_holidays.getMonth();
			            d1                 = date_holidays.getDate() ;
			            y1                 = date_holidays.getFullYear();
			           	
				        var k1 = d1 + "-" + ( m1 + 1 ) + "-" + y1;
				    	
				        var f = "false";
						if( jQuery.inArray( k1 ,holidayDates) != -1 ) {
							f = "true";
						}
						
				        if ( f == "true" ){
		    
		                   var index = holidayDates.indexOf(k1);
		                   
		                   if ( index > -1) {
	                            holidayDates.splice(index, 1);
	                            // disabling next date in the ccheckout calendar
	                            var next_date_str 	= date_holidays.getTime() + (1 * 24 * 60 * 60 * 1000); 
	                            next_date 			= new Date(next_date_str);

	                            next_m1                 = next_date.getMonth();
					            next_d1                 = next_date.getDate() ;
					            next_y1                 = next_date.getFullYear();
					            var next_k1 			= next_d1 + "-" + ( next_m1 + 1 ) + "-" + next_y1;
					            holidayDates.push(next_k1);
	                       }
	                      break;
	                    }
					}

		            for ( ii = 0; ii < holidayDates.length; ii++ ){

						if( jQuery.inArray(d + "-" + (m+1) + "-" + y, holidayDates) != -1 ) {
							return [false, "",labels.holiday_label ];
						}
				    }	                
		       } // end if
			}

			/**** Attribute Lockout Start ****/
			if ( settings.wapbk_attribute_list != undefined ) {
				var attribute_list = settings.wapbk_attribute_list.split(",");

				for ( i = 0; i < attribute_list.length; i++ ) {

					if ( attribute_list[i] != "" ) {

						var field_name = "#wapbk_lockout_" + attribute_list[i];

						var lockoutdates = eval("["+jQuery(field_name).val()+"]");

						var dt = new Date();
						var today = dt.getMonth() + "-" + dt.getDate() + "-" + dt.getFullYear();
						if (id_booking == "booking_calender" || id_booking == "inline_calendar") {

							for (iii = 0; iii < lockoutdates.length; iii++) {
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,lockoutdates) != -1 && jQuery( "#" + attribute_list[i] ).val() > 0 ) {
									return [false, "", labels.booked_label ];
								}
							}
						}

						var field_name = "#wapbk_lockout_checkout_" + attribute_list[i];

						var lockoutdates = eval("["+jQuery(field_name).val()+"]");

						var dt = new Date();
						var today = dt.getMonth() + "-" + dt.getDate() + "-" + dt.getFullYear();
						if (id_booking == "booking_calender_checkout" || id_booking == "inline_calendar_checkout") {

							for (iii = 0; iii < lockoutdates.length; iii++) {
								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,lockoutdates) != -1 && jQuery( "#" + attribute_list[i] ).val() > 0 ) {
									return [false, "", labels.booked_label ];
								}
							}
						}
					}
				}
			}

			/****** Variation Lockout start *******/
			var variation_id_selected = 0;

			// On some client site the hidden field for the varaition id is not populated using CLASS method. Instead of that it is populating with the NAME.
			// So this fix ensure that if class property does not find then look for the NAME property.
			
			var variation_by_name = document.getElementsByName( "variation_id" ).length;

			if ( jQuery( ".variation_id" ).length > 0 ) {
				variation_id_selected = jQuery( ".variation_id" ).val();
			}else if( variation_by_name > 0 ){
				variation_id = document.getElementsByName("variation_id")[0].value; 
			}
			var field_name = "#wapbk_lockout_" + variation_id_selected;
			var lockoutdates = eval("["+jQuery(field_name).val()+"]");
			var dt = new Date();
			var today = dt.getMonth() + "-" + dt.getDate() + "-" + dt.getFullYear();
			if (id_booking == "booking_calender" || id_booking == "inline_calendar") {
				for (iii = 0; iii < lockoutdates.length; iii++) {
					if( jQuery.inArray(d + "-" + (m+1) + "-" + y,lockoutdates) != -1 ) {
						return [false, "", labels.booked_label ];
					}
				}
			}

			var field_name = "#wapbk_lockout_checkout_" + variation_id_selected;
			var lockoutdates = eval("["+jQuery(field_name).val()+"]");
			var dt = new Date();
			var today = dt.getMonth() + "-" + dt.getDate() + "-" + dt.getFullYear();
			if (id_booking == "booking_calender_checkout" || id_booking == "inline_calendar_checkout") {
				for (iii = 0; iii < lockoutdates.length; iii++) {
					if( jQuery.inArray(d + "-" + (m+1) + "-" + y,lockoutdates) != -1 ) {
						return [false, "", labels.booked_label ];
					}
				}
			}

			/****** Variations Lockout end ********/
			
			/****** Resource Lockout Etart *******/

			var resource_id_selected = 0;
			
			if( jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").length > 0 ) {
				resource_id_selected 	= jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").val();
						
				wapbk_resource_lockout 	= settings.bkap_booked_resource_data[resource_id_selected]['bkap_locked_dates'];
				wapbk_resource_disaabled_dates 	= settings.resource_disable_dates[resource_id_selected];

				var lockoutdates 		= JSON.parse("[" + wapbk_resource_lockout + "]");
				lockoutdates            = lockoutdates.concat(wapbk_resource_disaabled_dates);
				var dt 					= new Date();
				var today 				= dt.getMonth() + "-" + dt.getDate() + "-" + dt.getFullYear();

				if ( id_booking == "booking_calender" || id_booking == "inline_calendar" || id_booking == "booking_calender_checkout" ) {
					
					for ( iii = 0; iii < lockoutdates.length; iii++ ) {
						if ( jQuery.inArray(d + "-" + (m+1) + "-" + y,lockoutdates) != -1 ) {
							return [ false, "", labels.booked_label ];
						}
					}
				}
			}

			/****** Resource Lockout End *******/
			
			if ( 'on' == bkap_settings.booking_enable_multiple_day ) {
				var bkap_rent = eval( "[" + settings.bkap_rent + "]" );
				for (i = 0; i < bkap_rent.length; i++) {
					if( jQuery.inArray(d + "-" + (m+1) + "-" + y, bkap_rent ) != -1 ) {
						return [ false, "", labels.unavailable_label ];
					}
				}
			}

			// if a fixed date range is enabled, then check if the date lies in the range and enable/disable accordingly
			if ( settings.fixed_ranges !== undefined && settings.fixed_ranges.length > 0 ) {
				var in_range = fixed_range( date, id_booking );

				if ( in_range == true ) {
					//return [true];
				} else {
					return [ false ];
				}
			}
			var block_option_enabled = jQuery( "#block_option_enabled" ).val();
			if ( block_option_enabled =="on" ) {
				if ( id_booking == "booking_calender" || id_booking == "inline_calendar" ) {
					if ( block_option_start_day == date.getDay() || block_option_start_day == "any_days" ) {
						return [ true ];
					} else {
						return [ false ];
					}
				}
				var bcc_date=jQuery( "#booking_calender_checkout" ).datepicker( "getDate" );
				if (bcc_date == null) {
					var bcc_date = jQuery( "#inline_calendar_checkout" ).datepicker( "getDate" );
				}
				if ( bcc_date != null ) {
					var dd = bcc_date.getDate();
					var mm = bcc_date.getMonth()+1; //January is 0!
					var yyyy = bcc_date.getFullYear();
					var checkout = dd + "-" + mm + "-"+ yyyy;
					jQuery( "#wapbk_hidden_date_checkout" ).val( checkout );

					if ( id_booking == "booking_calender_checkout" || id_booking == "inline_calendar_checkout" ) {
						if (Date.parse( bcc_date ) === Date.parse( date ) ) {
							return [ true ];
						} else{
							return [ false ];
						}
					}
				}
			}

			return [ true ];
		}
	};
}( jQuery );