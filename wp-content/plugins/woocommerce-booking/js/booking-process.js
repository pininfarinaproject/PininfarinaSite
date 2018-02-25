var bkap_process_init = function( $, bkap_process_params ){

	var global_settings = JSON.parse( bkap_process_params.global_settings ),
		bkap_settings 	= JSON.parse( bkap_process_params.bkap_settings ),
		settings 		= JSON.parse( bkap_process_params.additional_data ),
		calendar_type	= '',
		checkin_class	= '',
		checkout_class	= '',
		datepicker_options = {};

	window.MODAL_ID = '';
	window.MODAL_FORM_ID = '';
	window.MODAL_DATE_ID = '';
	window.MODAL_END_DATE_ID = '';

	if( $( '#modal-body-'+bkap_process_params.bkap_cart_item_key ).length ){
		MODAL_ID = '#modal-body-'+bkap_process_params.bkap_cart_item_key+' ';
		MODAL_FORM_ID = MODAL_ID + '#bkap-booking-form ';
		MODAL_DATE_ID = MODAL_FORM_ID + '#bkap_start_date ';
		MODAL_END_DATE_ID = MODAL_FORM_ID + '#bkap_end_date ';
	}

	if ( bkap_settings.enable_inline_calendar === 'on') {
		if( jQuery( MODAL_ID + "#wapbk_widget_search" ) !== undefined && 
			jQuery( MODAL_ID + "#wapbk_widget_search" ) == 1 &&
			bkap_settings.booking_fixed_block_enable !== undefined && 
			bkap_settings.booking_fixed_block_enable === 'booking_fixed_block_enable' ) {

			//bkap_functions.test_bkap_init_inline();
		}else if ( bkap_settings.booking_fixed_block_enable !== undefined && 
			bkap_settings.booking_fixed_block_enable === 'booking_fixed_block_enable') {

			//bkap_functions.test_bkap_init_inline();
		}

		if ( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() !== undefined && 
			jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() !== "" && 
			bkap_settings.booking_enable_multiple_day !== 'on' ) {

			//bkap_process_date( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() );
		}
		
		calendar_type = 'inline';
		checkin_class = MODAL_DATE_ID + '#inline_calendar';
		checkout_class = MODAL_END_DATE_ID + '#inline_calendar_checkout';
	}else {
		if( jQuery( MODAL_ID + "#wapbk_widget_search" ) !== undefined && 
			jQuery( MODAL_ID + "#wapbk_widget_search" ) == 1 &&
			bkap_settings.booking_enable_multiple_day === 'on' ) {

			bkap_functions.bkap_init();
		}else if ( jQuery( MODAL_ID + "#wapbk_widget_search" ) !== undefined && 
			jQuery( MODAL_ID + "#wapbk_widget_search" ) == 1 &&
			bkap_settings.booking_enable_multiple_day === 'on' && 
			bkap_settings.booking_fixed_block_enable !== undefined && 
			bkap_settings.booking_fixed_block_enable === 'booking_fixed_block_enable' ) {

			bkap_functions.test_bkap_init();
		}

		calendar_type = 'normal';
		checkin_class = MODAL_DATE_ID + '#booking_calender';
		checkout_class = MODAL_END_DATE_ID + '#booking_calender_checkout';
	}

	$.datepicker._selectDay = function( id, month, year, td ) {

		var inst,
			target = $( id );

		if ( id === '#inline_calendar' || id === '#booking_calender' ) {
			target = $( MODAL_DATE_ID + id );
		}else if ( id === '#inline_calendar_checkout' || id === '#booking_calender_checkout' ) {
			target = $( MODAL_END_DATE_ID + id );
		}

		if ( $( td ).hasClass( this._unselectableClass ) || this._isDisabledDatepicker( target[ 0 ] ) ) {
			return;
		}

		inst = this._getInst( target[ 0 ] );

		inst.selectedDay = inst.currentDay = $( "a", td ).html();
		inst.selectedMonth = inst.currentMonth = month;
		inst.selectedYear = inst.currentYear = year;
		this._selectDate( id, this._formatDate( inst,
			inst.currentDay, inst.currentMonth, inst.currentYear ) );
	};

	// The following functions are default datepicker functions overriden to work on modal popup
	$.datepicker._selectDate = function( id, dateStr ) {

		var onSelect,
			target = $( id );
			
		if ( id === '#inline_calendar' || id === '#booking_calender' ) {
			target = $( MODAL_DATE_ID + id );
		}else if ( id === '#inline_calendar_checkout' || id === '#booking_calender_checkout' ) {
			target = $( MODAL_END_DATE_ID + id );
		}

		var inst = this._getInst( target[ 0 ] );
		dateStr = ( dateStr != null ? dateStr : this._formatDate( inst ) );
		if ( inst.input ) {
			inst.input.val( dateStr );
		}
		this._updateAlternate( inst );

		onSelect = this._get( inst, "onSelect" );
		if ( onSelect ) {
			onSelect.apply( ( inst.input ? inst.input[ 0 ] : null ), [ dateStr, inst ] );  // trigger custom callback
		} else if ( inst.input ) {
			inst.input.trigger( "change" ); // fire the change event
		}

		if ( inst.inline ) {
			this._updateDatepicker( inst );
		} else {
			this._hideDatepicker();
			this._lastInput = inst.input[ 0 ];
			if ( typeof( inst.input[ 0 ] ) !== "object" ) {
				inst.input.trigger( "focus" ); // restore focus
			}
			this._lastInput = null;
		}
		
	}

	/* Adjust one of the date sub-fields. */
	$.datepicker._adjustDate = function( id, offset, period ) {
		var target = $( id );

		if ( id === '#inline_calendar' || id === '#booking_calender' ) {
			target = $( MODAL_DATE_ID + id );
		}else if ( id === '#inline_calendar_checkout' || id === '#booking_calender_checkout' ) {
			target = $( MODAL_END_DATE_ID + id );
		}

		var inst = this._getInst( target[ 0 ] );

		if ( this._isDisabledDatepicker( target[ 0 ] ) ) {
			return;
		}
		this._adjustInstDate( inst, offset, period );
		this._updateDatepicker( inst );
	}
	
	jQuery( MODAL_ID + "#ajax_img" ).hide();
	// recalculate the prices when qty is changed
	jQuery( "form.cart" ).on( "change", "input.qty", function() {
		
		if ( 'on' == bkap_settings.booking_enable_multiple_day ) {
			if ( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() != "" && jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val() != "" ) {
				bkap_calculate_price();
			}
		} else {
			if ( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() != "" ) {
				bkap_single_day_price();
			} else if ( "on" == bkap_settings.booking_purchase_without_date ) {
				bkap_purchase_without_date();
			}
		}

	});
	
	if ( 'bundle' === settings.product_type ) {
		
		if ( jQuery( ".bundled_product_checkbox" ).length > 0 ) {
			jQuery( ".bundled_product_checkbox" ).on( "change", function(){

				if ( 'on' == bkap_settings.booking_enable_multiple_day ) {
					if ( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() != "" && jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val() != "" ) {
						bkap_calculate_price();
					}
				} else {
					if ( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() != "" ) {
						bkap_single_day_price();
					} else if ( "on" == bkap_settings.booking_purchase_without_date ) {
						bkap_purchase_without_date();
					}
				}
			});
		}
	} else if ( 'variable' === settings.product_type ) {
		
		jQuery(document).on("change", "select" + bkap_process_params.on_change_attr_list, function() {
			// hide the bundle price
			if ( jQuery( ".bundle_price" ).length > 0 ) {
				jQuery( ".bundle_price" ).hide();
			}
			// Refresh the datepicker to ensure the correct dates are displayed as available when an attribute is changed
			if ( calendar_type === 'inline' ) {
				jQuery( checkin_class ).datepicker( "refresh" );
				jQuery( checkout_class ).datepicker( "refresh" );
			}
			var attribute_values = "";
			var attribute_selected = "";
			eval( bkap_process_params.attr_value );
			eval( bkap_process_params.attr_selected );
			// @TODO ID
			jQuery( "#wapbk_variation_value" ).val( attribute_selected );
			if ( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() != "" && jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val() != "" )
				bkap_calculate_price();
		});

	}

	/*
	 * This event for changing the fixed bock dropdown
	 * on the front end of the product page.
	 */	
	jQuery( document ).on( 'change', "select#block_option", function () {
		if ( jQuery( "#block_option" ).val() != "" ) {
			
			var passed_id 	= this.value;
			var exploded_id = passed_id.split('&');
			
			jQuery("#block_option_start_day").val(exploded_id[0]);
			jQuery("#block_option_number_of_day").val(exploded_id[1]);
			jQuery("#block_option_price").val(exploded_id[2]);
			jQuery("#wapbk_diff_days").val(parseInt(exploded_id[1]));


			if ( calendar_type === 'inline' ) {
				jQuery( checkin_class ).datepicker( "refresh" );
				jQuery( checkout_class ).datepicker( "refresh" );
			}
			
			if( bkap_settings.enable_inline_calendar != 'on' ) {
				// reset the date fields
				jQuery("#wapbk_hidden_date").val("");
				jQuery("#wapbk_hidden_date_checkout").val("");

				//jQuery("#show_time_slot").html("");
				jQuery("#show_time_slot").html("");
				jQuery( checkin_class ).datepicker("setDate");
			  jQuery( checkout_class ).datepicker("setDate");

				// disable the add to cart and qty buttons
	            jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
				jQuery( ".qty" ).prop( "disabled", true );
			    // hide the price
			    jQuery( "#bkap_price" ).html("");
			}

		    if ( bkap_settings.enable_inline_calendar === 'on' ) {

		    	jQuery("#wapbk_hidden_date").val("");
				jQuery("#wapbk_hidden_date_checkout").val("");

		    	current_Date = new Date();

				first_date = bkap_first_fixed_block_date( current_Date, exploded_id );
				
				wapbk_hidden_date = bkap_functions.bkap_create_date( first_date );
				jQuery("#wapbk_hidden_date").val(wapbk_hidden_date);
				jQuery( checkin_class ).datepicker("setDate", first_date );

				if ( typeof( bkap_functions.test_bkap_init_inline ) != "undefined" && typeof( bkap_functions.test_bkap_init_inline ) == "function" ) { // this function is present only for the inline datepicker
				    bkap_functions.test_bkap_init_inline();
				    
				}
			}
			
		}
	});

	var formats = [ "d.m.y", "d-m-yy", "MM d, yy" ];
	var split = settings.default_date.split( "-" );
	split[1] = split[1] - 1;		
	var default_date = new Date( split[2], split[1], split[0] );

	var variation_id = 0;

	// On some client site the hidden field for the varaition id is not populated using CLASS method. Instead of that it is populating with the NAME.
	// So this fix ensure that if class property does not find then look for the NAME property.
	
	var variation_by_name = document.getElementsByName( MODAL_ID + "variation_id" ).length;
	
	if ( jQuery( MODAL_ID + ".variation_id" ).length > 0 ) {
		if ( jQuery( MODAL_ID + ".variation_id" ).length > 1 ) {
			var variation_id = "";
			jQuery( MODAL_ID + ".variation_id" ).each( function ( i, obj ) {
				if ( jQuery( obj ).val() > 0 ) {
					variation_id += jQuery( obj ).val() + ",";
				}
			});
		} else {
			variation_id = jQuery( MODAL_ID + ".variation_id" ).val();
		}
	}else if( variation_by_name > 0 ){
		variation_id = document.getElementsByName( MODAL_ID + "variation_id" )[0].value; 
	}

	var time_slots_arr = bkap_settings.booking_time_settings,
		time_slot_lockout = "",
		attr_lockout = "";
	//if (jQuery("#wapbk_bookingEnableTime").val() == "on" && jQuery("#wapbk_booking_times").val() == "YES") {
	if ( settings.product_type !== 'bundle' && settings.product_type === 'variable' ) {
		
		var field_name = "#wapbk_timeslot_lockout_" + variation_id;
		var time_slot_lockout = "";
		if ( jQuery( field_name ).length > 0 ) {
			time_slot_lockout = jQuery( field_name ).val();
		}

		var attr_lockout = "";
		if ( settings.wapbk_attribute_list !== undefined ) {
			var attribute_list = settings.wapbk_attribute_list.split( "," );

			for ( i = 0; i < attribute_list.length; i++ ) {

				if ( attribute_list[i] != "" && jQuery( "#" + attribute_list[i] ).val() > 0 ) {

					var field_name = "#wapbk_timeslot_lockout_" + attribute_list[i];
					if ( jQuery( field_name ).length > 0 ) {
						attr_lockout = attr_lockout + attribute_list[i] + "," + jQuery( field_name ).val() + ";";
					}
				}
			}
		}
	}
	//}

	jQuery.extend( jQuery.datepicker, { afterShow: function( event ){
		jQuery.datepicker._getInst( event.target ).dpDiv.css( "z-index", 9999 );
	}});

	var on_select_date, show_day;
	if ( bkap_settings.booking_enable_multiple_day === 'on' ) {
		on_select_date = bkap_set_checkin_date;
		show_day = bkap_functions.bkap_check_booked_dates;
	} else {
		on_select_date = bkap_show_times;
		show_day = bkap_show_book;
	}

	datepicker_options = {
		dateFormat: global_settings.booking_date_format,
		numberOfMonths: parseInt( global_settings.booking_months ),
		firstDay: parseInt( global_settings.booking_calendar_day ),
		defaultDate: default_date,
		beforeShowDay: show_day,
		onSelect: on_select_date
	}

	if ( jQuery( MODAL_ID + "#block_option_enabled" ).val() === "on" ){
		datepicker_options['onClose'] = on_close_fixed_blocks; 
	}

	if ( calendar_type === 'inline' ) {
		var avd_obj = avd();
		datepicker_options['minDate'] = avd_obj.minDate;
		datepicker_options['maxDate'] = avd_obj.maxDate;
		datepicker_options['altField'] = MODAL_DATE_ID + '#booking_calender';
	}else {
		datepicker_options['beforeShow'] = avd;
	}

	jQuery( checkin_class )
		.datepicker(datepicker_options);
		/*.focus( function ( event ){
			jQuery.datepicker.afterShow( event );
		});*/

	/*if ( bkap_settings.booking_enable_multiple_day === 'on' ) {
		if ( checkin_class === '#inline_calendar' ) {
			jQuery( checkin_class ).datepicker( 'option', 'onSelect', function( date, inst ) {
				var monthValue = inst.selectedMonth+1;
				var dayValue = inst.selectedDay;
				var yearValue = inst.selectedYear;
				var current_dt = dayValue + "-" + monthValue + "-" + yearValue;

				checkin_date_process( current_dt, true );
			});
		} else {
			jQuery( checkin_class ).datepicker( 'option', 'onClose', function( date, inst ) {
				var monthValue = inst.selectedMonth+1;
				var dayValue = inst.selectedDay;
				var yearValue = inst.selectedYear;
				var current_dt = dayValue + "-" + monthValue + "-" + yearValue;

				checkin_date_process( current_dt, false );
			});
		}
	}*/

	if( ( global_settings.booking_global_selection == "on" ) || ( jQuery( "#wapbk_widget_search" ).val() == "1" ) || calendar_type === 'inline' ) {

		var other_data = {
			calendar_type: calendar_type,
			checkin_class: checkin_class,
			time_slots_arr: time_slots_arr,
			variation_id: variation_id,
			time_slot_lockout: time_slot_lockout,
			attr_lockout: attr_lockout
		};

		default_display_date( settings, global_settings, bkap_settings, bkap_process_params, other_data );
	}

	if ( calendar_type === 'normal' ) {
		jQuery( "#ui-datepicker-div" ).wrap( "<div class=\"hasDatepicker\"></div>" );
		jQuery( MODAL_DATE_ID + "#checkin_cal" ).click( function() {
			jQuery( checkin_class ).datepicker( "show" ); 
		}); 
	}

	multiple_days_function( bkap_settings, global_settings, settings, checkout_class, checkin_class, calendar_type );

};

if ( typeof( bkap_process_params ) !== 'undefined' ) {
	bkap_process_init( jQuery, bkap_process_params );
	bkap_remove_modal_divs( jQuery );
}

function bkap_remove_modal_divs( $ ) {

	$( document ).ready(function(){
		//$( '.bkap-modal' ).remove();
	});
}

/*
* Calculating first available date for fixed block when period is changed.
*/

function bkap_first_fixed_block_date( current_date, fixed_block ) {

	if ( fixed_block[0] == 'any_days' ) {
		
		return current_date;
	} else {

		for ( i=1; i<=7; i++ ) {

			if ( current_date.getDay() == fixed_block[0] ) {
				return current_date;
			}else{
				current_date.setDate(current_date.getDate() + 1);
			}
		}
		return current_date;
	}
}

function multiple_days_function( bkap_settings, global_settings, settings, checkout_class, checkin_class, calendar_type ){

	var min_date_co,
		checkout_datepicker_options = {},
		current_dt, minDate, split, checkinDate;

	current_dt = jQuery( MODAL_ID + "#wapbk_hidden_date" ).val();

	if ( current_dt ) {
		split = current_dt.split( "-" );
		split[1] = split[1] - 1;
		minDate = new Date( split[2], split[1], split[0] );
		checkinDate = new Date( split[2], split[1], split[0] );
	}else{
		minDate = new Date();
		checkinDate = '';
	}

	if( bkap_settings.booking_charge_per_day != undefined && 
		bkap_settings.booking_charge_per_day == 'on' && 
		bkap_settings.booking_same_day !== '' && 
		bkap_settings.booking_same_day == 'on' ) {
		
		min_date_co = minDate;
	} else { 
		minDate.setDate( minDate.getDate() + 1 );
		min_date_co = minDate;
	}

	checkout_datepicker_options = {
		dateFormat: global_settings.booking_date_format,
		numberOfMonths: parseInt( global_settings.booking_months ),
		firstDay: parseInt( global_settings.booking_calendar_day ),
		minDate: min_date_co,
		onSelect: bkap_get_per_night_price,
		beforeShowDay: bkap_functions.bkap_check_booked_dates,
		onClose: function( selectedDate ) {
			jQuery( checkin_class ).datepicker( "option", "maxDate", selectedDate );
		}
	};

	if( bkap_settings.booking_fixed_block_enable !== undefined && 
		bkap_settings.booking_fixed_block_enable !== 'booking_fixed_block_enable' ){

		checkout_datepicker_options['onSelect'] = bkap_get_per_night_price
	}

	if ( calendar_type === 'inline' ) {
		checkout_datepicker_options['altField'] = MODAL_END_DATE_ID + '#booking_calender_checkout';
	}

	jQuery( checkout_class )
		.datepicker( checkout_datepicker_options )
		.focus( function( event ) {
			jQuery.datepicker.afterShow( event );
		});

	if( ( global_settings.booking_global_selection === "on" ) || 
		( jQuery( "#wapbk_widget_search" ).val() == "1" ) ) {

		if ( checkinDate && jQuery( "#block_option_enabled" ).val() && jQuery( "#block_option_enabled" ).val() === "on" ) {
			set_checkout_mindate( checkinDate, settings, bkap_settings, global_settings, calendar_type, checkout_class );
		}
		display_checkout_date( checkout_class );
	}

	if( bkap_settings.booking_enable_multiple_day === 'on' && 
		calendar_type === 'inline' ) {
		
		bkap_functions.bkap_init_inline_multiple( global_settings, bkap_settings, settings );
	}

	if ( calendar_type === 'normal' ) {
		jQuery( MODAL_END_DATE_ID + "#checkout_cal" ).click( function() {
			jQuery( checkout_class ).datepicker( "show" );
		});
	}

	// This section is for showing dates selected for inline calendar when search widget is on
	if ( calendar_type === 'inline') {
		if( jQuery( MODAL_ID + "#wapbk_widget_search" ) !== undefined && 
			jQuery( MODAL_ID + "#wapbk_widget_search" ) == 1 &&
			bkap_settings.booking_fixed_block_enable !== undefined && 
			bkap_settings.booking_fixed_block_enable === 'booking_fixed_block_enable' ) {

			bkap_functions.test_bkap_init_inline();
		}else if ( bkap_settings.booking_fixed_block_enable !== undefined && 
			bkap_settings.booking_fixed_block_enable === 'booking_fixed_block_enable') {

			bkap_functions.test_bkap_init_inline();
		}
	}
}

function default_display_date( settings, global_settings, bkap_settings, bkap_process_params, other_data ){

	var split = jQuery( MODAL_ID + "#wapbk_hidden_date" ).val().split( "-" );
	split[1] = split[1] - 1;		
	var current_dt	= jQuery( MODAL_ID + "#wapbk_hidden_date" ).val();		
	var CheckinDate = new Date( split[2], split[1], split[0] );
	var timestamp = Date.parse( CheckinDate ); 
	if ( isNaN( timestamp ) == false ) { 
		var default_date = new Date( timestamp );
		jQuery( other_data.checkin_class ).datepicker( "setDate", default_date );
		if ( bkap_settings.booking_enable_time == "on" && Object.keys(other_data.time_slots_arr).length > 0 ) {
			if ( !MODAL_ID ) {
				bkap_process_date( current_dt );
			}
			var data = {
				current_date: current_dt,
				post_id: bkap_process_params.product_id, 
				variation_id: other_data.variation_id,
				variation_timeslot_lockout: other_data.time_slot_lockout,
				attribute_timeslot_lockout: other_data.attr_lockout,
				action: settings.method_timeslots,
				booking_post_id: settings.booking_post_id,
				//'.$attribute_fields_str.'
			};
			jQuery.post( bkap_process_params.ajax_url, data, function( response ) {
				jQuery( MODAL_ID + "#ajax_img" ).hide();
				jQuery( "#cadt" ).remove();
				jQuery( MODAL_FORM_ID + "#show_time_slot" ).html(response);
				bkap_time_slot_events( settings, global_settings, bkap_settings );
				if ( typeof(bkap_edit_params) !== 'undefined' ){
					jQuery( MODAL_FORM_ID + "#show_time_slot" + " #time_slot" ).val( bkap_edit_params.bkap_booking_params.time_slot ).change();
				}
				// display the selected time slot
				if( settings.time_selected !== '' ) {
					jQuery( "#time_slot" ).val( settings.time_selected );
				}
			});
		}else{
			bkap_process_date( current_dt );
			setTimeout( function(){
				var fixed_block_selected = jQuery( MODAL_ID + '#wapbk_diff_days' ).val(),
					option_value = '';
				if ( fixed_block_selected && fixed_block_selected !== undefined && fixed_block_selected !== '' ) {
					jQuery( MODAL_FORM_ID + '#block_option option' ).each(function() {
						option_value = jQuery(this).val().split( '&' );
						if ( option_value[1] && option_value[1] == fixed_block_selected ) {
							jQuery( MODAL_FORM_ID + '#block_option' ).val( jQuery(this).val() );
						}
					});
				}
			});
			// @TODO to be removed after testing of v4.4.0
			/*if( bkap_settings.booking_recurring_booking == "on" || bkap_settings.booking_specific_booking == "on" ) {
				var data = {
					id: bkap_process_params.product_id,
					post_id: bkap_process_params.post_id,
					details: jQuery( MODAL_ID + "#wapbk_hidden_date" ).val(),
					variation_id: other_data.variation_id,
					action: "bkap_call_addon_price"

				};
				jQuery.post( bkap_process_params.ajax_url, data, function( amt ) {
					jQuery( MODAL_ID + "#ajax_img" ).hide();
					if( isNaN( parseInt( amt ) ) ) {
						// The price will be echoed directly by the respective functions. Hence we just need to eval the response received.
						eval( amt.replace( '"#bkap_price"' , "'" + MODAL_ID + "#bkap_price'" ) );
						jQuery( 'body' ).trigger( 'bkap_price_updated', bkap_process_params.bkap_cart_item_key );
					} 
					if (eval( amt )){
						jQuery( ".single_add_to_cart_button" ).prop( "disabled", false );
						jQuery( ".qty" ).prop( "disabled", false );
						jQuery( ".single_add_to_cart_button" ).show();
						jQuery( ".qty" ).show();

					}
				});
			}*/
		}
	}
}

function display_checkout_date( checkout_class ) {

	var split = jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val().split( "-" );
	split[1] = split[1] - 1;		
	var CheckoutDate = new Date(split[2],split[1],split[0]);
	var timestamp = Date.parse(CheckoutDate);
	if (isNaN(timestamp) == false) { 
		var default_date = new Date(timestamp);
		jQuery( checkout_class ).datepicker( "setDate", default_date );
		bkap_calculate_price();
	}
	return default_date;
}

//***************************************************
//This function disables the dates in the calendar for holidays, global holidays set and for which lockout is reached for Single day booking	feature.
//***********************************

function bkap_show_book(date){
	
	var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();

	var settings = JSON.parse( bkap_process_params.additional_data );
	var bkap_settings = JSON.parse( bkap_process_params.bkap_settings );
	var labels = JSON.parse( bkap_process_params.labels );
	
	var deliveryDates = eval( "[" + settings.specific_dates + "]" );	
//	var holidayDates = eval( "[" + jQuery( "#wapbk_booking_holidays" ).val() + "]" );
	var holidayDates = JSON.parse( "[" + settings.holidays + "]" );
//	var globalHolidays = eval( "[" + jQuery( "#wapbk_booking_global_holidays" ).val() + "]" );

	var disabled_week_days = eval( "[" + settings.wapbk_block_checkin_weekdays + "]" );

	// NEW:
	var block_selected_week_days = settings.bkap_block_selected_weekdays;
	var res = block_selected_week_days.split(",");
	// NEW:
	
	var bkap_month = m < 10 ? "0" + (m+1) : (m+1);
	var bkap_check_current_date = y+"-"+ bkap_month +"-"+ d;

	// NEW:
	for ( weekday = 0; weekday < res.length; weekday++) {
        if( jQuery.inArray( bkap_check_current_date, res ) != -1 ) {
	        //console.log(y+"-"+(m+1)+"-"+ d);           
			return [ false, "", labels.blocked_label ];
		}
	}


	for ( jjj = 0; jjj < disabled_week_days.length; jjj++) {
		if( jQuery.inArray( date.getDay(), disabled_week_days) != -1 ) {
			return [ false, "", labels.blocked_label ];
		}
	}

	var id_booking = jQuery(this).attr("id");

	//Lockout Dates
	var lockoutdates = eval( "[" + settings.wapbk_lockout_days + "]" );
	var bookedDates = eval( "[" + settings.wapbk_hidden_booked_dates + "]" );
	var dt = new Date();
	var today = dt.getMonth() + "-" + dt.getDate() + "-" + dt.getFullYear();
	for (iii = 0; iii < lockoutdates.length; iii++) {
		if( jQuery.inArray(d + "-" + (m+1) + "-" + y,lockoutdates) != -1 ) {
			return [ false, "", labels.booked_label ];
		}
	}	

	/**** Attribute Lockout Start ****/

	if ( settings.wapbk_attribute_list != undefined ) {
		var attribute_list = settings.wapbk_attribute_list.split( "," );

		for ( i = 0; i < attribute_list.length; i++ ) {

			if ( attribute_list[i] != "" ) {

				var field_name = "#wapbk_lockout_" + attribute_list[i];

				var lockoutdates = eval( "[" + jQuery(field_name).val() + "]" );

				var dt = new Date();
				var today = dt.getMonth() + "-" + dt.getDate() + "-" + dt.getFullYear();
				if ( id_booking == "booking_calender" || id_booking == "inline_calendar" ) {

					for (iii = 0; iii < lockoutdates.length; iii++) {
						if( jQuery.inArray(d + "-" + (m+1) + "-" + y,lockoutdates) != -1 && jQuery( "#" + attribute_list[i] ).val() > 0 ) {
							return [ false, "", labels.booked_label ];
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
		variation_id = document.getElementsByName( "variation_id" )[0].value; 
	}
	var field_name = "#wapbk_lockout_" + variation_id_selected;
	var lockoutdates = eval( "[" + jQuery(field_name).val() + "]" );
	var dt = new Date();
	var today = dt.getMonth() + "-" + dt.getDate() + "-" + dt.getFullYear();
	if ( id_booking == "booking_calender" || id_booking == "inline_calendar" ) {
		for (iii = 0; iii < lockoutdates.length; iii++) {
			if( jQuery.inArray(d + "-" + (m+1) + "-" + y,lockoutdates) != -1 ) {
				return [ false, "", labels.booked_label ];
			}
		}
	}
	
	/****** Resource Lockout Etart *******/

	var resource_id_selected = 0;
	
	if( jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").length > 0 ) {
		resource_id_selected 	= jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").val();

		wapbk_resource_lockout 	= settings.bkap_booked_resource_data[resource_id_selected]['bkap_locked_dates'];
		wapbk_resource_disaabled_dates 	= settings.resource_disable_dates[resource_id_selected];	
			
		var lockoutdates 		= JSON.parse("[" + wapbk_resource_lockout + "]");
		lockoutdates = lockoutdates.concat(wapbk_resource_disaabled_dates);	
		var dt 					= new Date();
		var today 				= dt.getMonth() + "-" + dt.getDate() + "-" + dt.getFullYear();

		if ( id_booking == "booking_calender" || id_booking == "inline_calendar" ) {
			
			for ( iii = 0; iii < lockoutdates.length; iii++ ) {
				if ( jQuery.inArray(d + "-" + (m+1) + "-" + y,lockoutdates) != -1 ) {
					return [ false, "", labels.booked_label ];
				}
			}
		}
	}

	/****** Resource Lockout End *******/

	/****** Variations Lockout end ********/
	/*for (iii = 0; iii < globalHolidays.length; iii++) {
		if( jQuery.inArray(d + "-" + (m+1) + "-" + y,globalHolidays) != -1 ){
			return [ false, "", bkap_process_params.holiday_label ];
		}
	} */

	for (ii = 0; ii < holidayDates.length; ii++) {
		if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
			return [ false, "", labels.holiday_label ];
		}
	}

	for (i = 0; i < bookedDates.length; i++) {
		if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ) {
			return [ false, "", labels.unavailable_label ];
		}
	}

	if ( 'on' == bkap_settings.booking_enable_multiple_day ) {
		var bkap_rent = eval( "[" + settings.bkap_rent + "]" );
		for (i = 0; i < bkap_rent.length; i++) {
			if( jQuery.inArray(d + "-" + (m+1) + "-" + y, bkap_rent ) != -1 ) {
				return [ false, "", labels.rent_label ];
			}
		}
	}

	var in_range = "";
	// if a fixed date range is enabled, then check if the date lies in the range and enable/disable accordingly
	if ( settings.fixed_ranges !== undefined ) {
		in_range = fixed_range( date );
	} else {
		// if fixed bookable range is not enabled, then the variable should be set to true to ensure the date is enabled based on specific dates/recurring weekday settings.
		in_range = true;
	}
	for (i = 0; i < deliveryDates.length; i++) {
		if( jQuery.inArray(d + "-" + (m+1) + "-" + y,deliveryDates) != -1 && true == in_range ){
			return [ true ];
		}
	}
 
	var day = "booking_weekday_" + date.getDay();
	var recurring_array = bkap_settings.booking_recurring;
	
	if ( recurring_array[day] == "on" && true == in_range ){
		return [ true ];
	}
	return [ false ];
}

//********************************************************
//This function calls an ajax when a date is selected which displays the time slots on frontend product page.
//**************************************************

function bkap_show_times( date,inst ) {
	jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
	jQuery( ".qty" ).prop( "disabled", true );

	var monthValue = inst.selectedMonth+1;
	var dayValue = inst.selectedDay;
	var yearValue = inst.selectedYear;

	var current_dt = dayValue + "-" + monthValue + "-" + yearValue;

	jQuery( MODAL_ID + "#wapbk_hidden_date" ).val( current_dt );
	bkap_process_date( current_dt );
}

function bkap_process_date( current_dt ) {
	
	var settings = JSON.parse( bkap_process_params.additional_data );
	var global_settings = JSON.parse( bkap_process_params.global_settings );
	var bkap_settings = JSON.parse( bkap_process_params.bkap_settings );
	
	var sold_individually = settings.sold_individually;
	var quantity = jQuery( "input[class='input-text qty text']" ).prop( "value" );
	if (typeof quantity == "undefined") {
		var quantity = 1;
	}
	
	var variation_id = 0;

	// On some client site the hidden field for the varaition id is not populated using CLASS method. Instead of that it is populating with the NAME.
	// So this fix ensure that if class property does not find then look for the NAME property.
	
	var variation_by_name = document.getElementsByName( MODAL_ID + "variation_id" ).length,
		variation_id_count = 0,
		bookings_placed = "",
		variation_array = [],
		field_name = '';
	
	if ( jQuery( MODAL_ID + ".variation_id" ).length > 0 ) {
		if ( jQuery( MODAL_ID + ".variation_id" ).length > 1 ) {
			variation_id = "";
			jQuery( MODAL_ID + ".variation_id" ).each( function ( i, obj ) {
				variation_id += jQuery( obj ).val() + ",";
				variation_id_count++;
			});
		} else {
			variation_id = jQuery( MODAL_ID + ".variation_id" ).val();;
		}
	}else if( variation_by_name > 0 ){
		variation_id = document.getElementsByName( MODAL_ID + "variation_id")[0].value; 
	}

	if ( variation_id_count > 0 ) {
		variation_array = variation_id.split( ',' );
		for ( var var_sub_id in variation_array ){
			if ( var_sub_id !== '' && var_sub_id !== undefined ) {
				field_name = "#wapbk_bookings_placed_" + var_sub_id;

				if ( jQuery( field_name ).length > 0 ) {
					bookings_placed += jQuery( field_name ).val() + ',';
				}
			}
		}
	}else {
		field_name = "#wapbk_bookings_placed_" + variation_id;

		if ( jQuery( field_name ).length > 0 ) {
			bookings_placed = jQuery( field_name ).val();
		}
	}

	var attr_bookings_placed = "";
	if ( settings.wapbk_attribute_list != undefined ) {
		var attribute_list = settings.wapbk_attribute_list.split(",");

		for ( i = 0; i < attribute_list.length; i++ ) {

			if ( attribute_list[i] != "" && jQuery( "#" + attribute_list[i] ).val() > 0 ) {

				var field_name = "#wapbk_bookings_placed_" + attribute_list[i];
				if ( jQuery( field_name ).length > 0 ) {
					attr_bookings_placed = attr_bookings_placed + attribute_list[i] + "," + jQuery( field_name ).val() + ";";
				}
			}
		}
	}
	
	/*** Resource Calculations Section Start ***/

	var resource_id_selected 			= 0;
	var bkap_resource_booking_placed 	= "";
	var bkap_locked_dates = "";

	if( jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").length > 0 ) {
		resource_id_selected 		 = jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").val();
		bkap_resource_booking_placed = settings.bkap_booked_resource_data[resource_id_selected]['bkap_booking_placed'];
		bkap_locked_dates			 = settings.bkap_booked_resource_data[resource_id_selected]['bkap_time_locked_dates'];	
	}

	/*** Resource Calculations Section End ***/
	
	// Availability Display for the date selected if setting is enabled
	
		var data = {
			checkin_date: jQuery( MODAL_ID + "#wapbk_hidden_date" ).val(),
			post_id: bkap_process_params.product_id,
			variation_id: variation_id,
			bookings_placed: bookings_placed,
			attr_bookings_placed: attr_bookings_placed,
			resource_id: resource_id_selected,
			resource_bookings_placed: bkap_resource_booking_placed,
			date_in_selected_language: jQuery( MODAL_DATE_ID + "#booking_calender" ).val(),
			action: "bkap_get_date_lockout"
		};

		jQuery.post( bkap_process_params.ajax_url, data, function( response ) {
			
			if ( global_settings.booking_availability_display === "on") {
				jQuery( MODAL_FORM_ID + "#show_stock_status" ).html( response.message );
			}
			
			if( response.max_qty != "" && response.max_qty != 0 && response.max_qty != "Unlimited" ){
				var max = parseInt( response.max_qty );
			    var max_availability = jQuery("input[name=\"quantity\"]");
			    max_availability.attr( "max", max );
			}
		});
	
	
	var time_slots = bkap_settings.booking_time_settings;

	if ( bkap_settings.booking_enable_time === "on" && time_slots.length !== null && time_slots !== undefined ) {
		jQuery( MODAL_ID + "#ajax_img" ).show();
		if( global_settings.display_disabled_buttons == "on" ){
			jQuery( MODAL_ID + "#bkap_price" ).hide();
			jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
			jQuery( ".qty" ).prop( "disabled", true );
		} else {
			jQuery( MODAL_ID + "#bkap_price" ).hide();
			jQuery( ".single_add_to_cart_button" ).hide();
			jQuery( ".qty" ).hide();
		}

		var time_slots_arr = time_slots;

		var field_name = "#wapbk_timeslot_lockout_" + variation_id;
		var time_slot_lockout = "";
		if ( jQuery( field_name ).length > 0 ) {
			time_slot_lockout = jQuery( field_name ).val();
		}

		var attr_lockout = "";
		if ( settings.wapbk_attribute_list != undefined ) {
			var attribute_list = settings.wapbk_attribute_list.split( "," );

			for ( i = 0; i < attribute_list.length; i++ ) {

				if ( attribute_list[i] != "" && jQuery( "#" + attribute_list[i] ).val() > 0 ) {

					var field_name = "#wapbk_timeslot_lockout_" + attribute_list[i];
					if ( jQuery( field_name ).length > 0 ) {
						attr_lockout = attr_lockout + attribute_list[i] + "," + jQuery( field_name ).val() + ";";
					}
				}
			}
		}

		var data = {
			current_date: current_dt,
			post_id: bkap_process_params.product_id, 
			variation_id: variation_id,
			variation_timeslot_lockout: time_slot_lockout,
			attribute_timeslot_lockout: attr_lockout,
			resource_id: resource_id_selected,
			resource_lockoutdates: bkap_locked_dates,
			action: settings.method_timeslots,
			booking_post_id: settings.booking_post_id,
			tyche: 1
		};
		jQuery.post( bkap_process_params.ajax_url, data, function(response) {
			jQuery( MODAL_ID + "#ajax_img" ).hide();
			jQuery( "#cadt" ).remove();
			jQuery( MODAL_FORM_ID + "#show_time_slot" ).html(response);
			// display the selected time slot
			if( settings.time_selected !== '' ) {
				jQuery( "#time_slot" ).val( settings.time_selected );
			}
			bkap_time_slot_events( settings, global_settings, bkap_settings );
		});
	} else {
		if( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() != "" ) {
			var data = {
				current_date: current_dt,
				post_id: bkap_process_params.product_id,
				action: "bkap_insert_date",
				tyche: 1
			};
			
			jQuery.post( bkap_process_params.ajax_url, data, function( response ){
				jQuery( ".payment_type" ).show();
				if( sold_individually == "yes" ) {
					jQuery( ".quantity" ).hide();
				} else {
					jQuery( ".quantity" ).show();
				}
			});
		} else if ( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() == "" ) {
			jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
			jQuery( ".qty" ).prop( "disabled", true );

			jQuery( ".payment_type" ).hide()
			jQuery(".partial_message").hide();
		}
	}

	bkap_single_day_price();
}

function bkap_time_slot_events( settings, global_settings, bkap_settings ) {

	/*var settings = JSON.parse( bkap_process_params.additional_settings );
	var global_settings = JSON.parse( bkap_process_params.global_settings );*/
	
	jQuery( MODAL_FORM_ID + "#show_time_slot" + " #time_slot" ).change(function() {
		var time_slot_value = jQuery( "#time_slot" ).val();
		if (typeof time_slot_value == "undefined") {
			var values = new Array();
			jQuery.each(jQuery( "input[name=\"time_slot[]\"]:checked" ), function() {
				values.push( jQuery(this).val() );
			});

			if ( values.length > 0 ) {
				time_slot_value = values.join(","); 
			}
		}
		var sold_individually = settings.sold_individually;
		
		var quantity = jQuery( "input[class=\"input-text qty text\"]" ).prop( "value" );
		if ( typeof quantity == "undefined" ) {
			var quantity = 1;
		}
		// Availability display for the time slot selected if setting is enabled
		
		var variation_id = 0;

			// On some client site the hidden field for the varaition id is not populated using CLASS method. Instead of that it is populating with the NAME.
			// So this fix ensure that if class property does not find then look for the NAME property.
			
			var variation_by_name = document.getElementsByName( "variation_id" ).length;

			if ( jQuery( MODAL_ID + ".variation_id" ).length > 0 ) {
				if ( jQuery( MODAL_ID + ".variation_id" ).length > 1 ) {
					var variation_id = "";
					jQuery( MODAL_ID + ".variation_id" ).each( function ( i, obj ) {
						variation_id += jQuery( obj ).val() + ",";
					});
				} else {
					variation_id = jQuery( MODAL_ID + ".variation_id" ).val();;
				}
			}else if( variation_by_name > 0 ){
				variation_id = document.getElementsByName( "variation_id" )[0].value; 
			}

			var field_name = "#wapbk_bookings_placed_" + variation_id;
			var time_slot_bookings_placed = "";
			if ( jQuery( field_name ).length > 0 ) {
				time_slot_bookings_placed = jQuery( field_name ).val();
			}

			var attr_bookings_placed = "";
			if ( settings.wapbk_attribute_list != undefined ) {
				var attribute_list = settings.wapbk_attribute_list.split(",");

				for ( i = 0; i < attribute_list.length; i++ ) {

					if ( attribute_list[i] != "" && jQuery( "#" + attribute_list[i] ).val() > 0 ) {

						var field_name = "#wapbk_bookings_placed_" + attribute_list[i];
						if ( jQuery( field_name ).length > 0 ) {
							attr_bookings_placed = attr_bookings_placed + attribute_list[i] + "," + jQuery( field_name ).val() + ";";
						}
					}
				}
			}
			
			var resource_id_selected		= 0;
			var bkap_time_booking_placed 	= "";
			var bkap_booking_placed 	 	= "";

			if( jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").length > 0 ) {
				resource_id_selected		= jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").val();
				bkap_time_booking_placed 	= settings.bkap_booked_resource_data[resource_id_selected]['bkap_time_booking_placed'];
				bkap_booking_placed 		= settings.bkap_booked_resource_data[resource_id_selected]['bkap_booking_placed'];				
			}
			
			if ( time_slot_value !== "" ) {
				var data = {
					checkin_date: jQuery( MODAL_ID + "#wapbk_hidden_date" ).val(),
					timeslot_value: time_slot_value,
					post_id: bkap_process_params.product_id, 
					variation_id: variation_id,
					bookings_placed: time_slot_bookings_placed,
					attr_bookings_placed: attr_bookings_placed,
					resource_id: resource_id_selected,
					resource_bookings_placed: bkap_time_booking_placed,					
					date_in_selected_language: jQuery( MODAL_DATE_ID + "#booking_calender" ).val(),
					action: "bkap_get_time_lockout"
				};
				jQuery.post( bkap_process_params.ajax_url, data, function(response) {
					
					if( global_settings.booking_availability_display === "on" && "" != response.message ) {
						jQuery( MODAL_FORM_ID + "#show_stock_status" ).html(response.message);
					}
					
					if( response.max_qty != "" && response.max_qty != 0 && response.max_qty != "Unlimited" ){
						var max = parseInt( response.max_qty );
					    var max_availability = jQuery("input[name=\"quantity\"]");
					    max_availability.attr( "max", max );
					}
					
				});
			} else {
				var data = {
					checkin_date: jQuery( MODAL_ID + "#wapbk_hidden_date" ).val(),
					post_id: bkap_process_params.product_id, 
					variation_id: variation_id,
					bookings_placed: time_slot_bookings_placed,
					attr_bookings_placed: attr_bookings_placed,
					resource_id: resource_id_selected,
					resource_bookings_placed: bkap_booking_placed,
					date_in_selected_language: jQuery( MODAL_DATE_ID + "#booking_calender" ).val(),
					action: "bkap_get_date_lockout"
				};
				jQuery.post( bkap_process_params.ajax_url, data, function( response ) {
					if( global_settings.booking_availability_display === "on" && "" != response.message ) {
						jQuery( MODAL_FORM_ID + "#show_stock_status" ).html( response.message );
					}
					
					if( response.max_qty != "" && response.max_qty != 0 && response.max_qty != "Unlimited" ){
						var max = parseInt( response.max_qty );
					    var max_availability = jQuery("input[name=\"quantity\"]");
					    max_availability.attr( "max", max );
					}
					
				});

			}
			if ( jQuery( "#time_slot" ).val() != "" ) {
				jQuery( ".payment_type" ).show();
				if( sold_individually == "yes" ) {
					jQuery( ".quantity" ).hide();
					jQuery( ".payment_type" ).hide();
					jQuery(".partial_message").hide();
				} else {
					jQuery( ".quantity" ).show();
					jQuery( ".payment_type" ).show();
				}
			} else if ( jQuery( "#time_slot" ).val() == "" ) {
				jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
				jQuery( ".qty" ).prop( "disabled", true );

				jQuery( ".payment_type" ).hide();
				jQuery( ".partial_message" ).hide();
			}
		// This is called to ensure the variable pricing for time slots is displayed
		bkap_single_day_price();
	})
}

/************************************************
This function calculates the price when a 
bookable product is being purchased without a date
************************************************/
function bkap_purchase_without_date() {

	jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
	jQuery( ".qty" ).prop( "disabled", true );

	var variation_id = 0;

	// On some client site the hidden field for the varaition id is not populated using CLASS method. Instead of that it is populating with the NAME.
	// So this fix ensure that if class property does not find then look for the NAME property.
	
	var variation_by_name = document.getElementsByName( "variation_id" ).length;

	if ( jQuery( ".variation_id" ).length > 0 ) {
		if ( jQuery( ".variation_id" ).length > 1 ) {
			var variation_id = "";
			jQuery( ".variation_id" ).each( function ( i, obj ) {
				variation_id += jQuery( obj ).val() + ",";
			});
		} else {
			variation_id = jQuery( ".variation_id" ).val();;
		}
	}else if( variation_by_name > 0 ){
		variation_id = document.getElementsByName( "variation_id" )[0].value; 
	}

	var quantity = jQuery( "input[class=\"input-text qty text\"]" ).prop( "value" );
	if ( typeof quantity == "undefined" ) {
		var quantity = 1;
	}

	var data = {
		post_id: bkap_process_params.product_id,
		quantity: quantity,
		variation_id: variation_id,
		action: "bkap_purchase_wo_date_price"
	};

	jQuery.post( bkap_process_params.ajax_url, data, function(response) {
		eval( response );

		jQuery( ".single_add_to_cart_button" ).prop( "disabled", false );
		jQuery( ".qty" ).prop( "disabled", false );
		jQuery( ".single_add_to_cart_button" ).show();
		jQuery( ".qty" ).show();
	});
}

/*************************************************
This function is used to display the price for 
single day bookings
**************************************************/
function bkap_single_day_price() {
	
	var settings = JSON.parse( bkap_process_params.additional_data );
	var bkap_settings = JSON.parse( bkap_process_params.bkap_settings );
	var bkap_labels = JSON.parse( bkap_process_params.labels );
	var global_settings = JSON.parse( bkap_process_params.global_settings );
	
	var bkap_time_slots = bkap_settings.booking_time_settings;
	
	jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
	jQuery( ".qty" ).prop( "disabled", true );

	var data = {
		booking_date: jQuery( MODAL_ID + "#wapbk_hidden_date" ).val(),
		post_id: bkap_process_params.product_id, 
		addon_data: jQuery( "#wapbk_addon_data" ).val(),
		action: "bkap_js"									
	};
	jQuery.post( bkap_process_params.ajax_url, data, function( response ) {
		eval( response );

		// replacing $addon_price variable
		if ( 'on' != bkap_settings.booking_enable_multiple_day ) {
			var quantity = jQuery( "input[class=\"input-text qty text\"]" ).prop( "value" );
			if ( typeof quantity == "undefined" ) {
				quantity = 1;
			}
			var sold_individually = jQuery( "#wapbk_sold_individually" ).val();

			var time_slot_value = jQuery( "#time_slot" ).val();

			if (typeof time_slot_value == "undefined" ) {
				var values = [];
				jQuery.each(jQuery( "input[name=\"time_slot[]\"]:checked" ), function() {
					values.push(jQuery(this).val());
				});
				
				if (values.length > 0) {
					time_slot_value = values.join(","); 
				}
			}

			// Edit post page issue
			if ( !time_slot_value ) {				
				time_slot_value = settings.time_selected;
			}

			var composite_data = '';

			if ( 'composite' === settings.product_type ) {
				composite_data = bkap_functions.bkap_get_composite_selections();
			}

			jQuery( MODAL_ID + "#ajax_img" ).show();
			if ( 'composite' !== settings.product_type ) {
				var quantity_str = jQuery( "input[class=\"input-text qty text\"]" ).prop( "value" );
				if ( typeof quantity_str == "undefined" ) {
					quantity_str = 1;
				}
			}else if ( 'composite' === settings.product_type ) {
				var quantity_str = jQuery( "input[name='quantity']" ).prop( "value" );
				if ( typeof quantity_str == "undefined" ) {
					quantity_str = 1;
				}
			}

			var qty_list = "NO";
			if ( settings.wapbk_grouped_child_ids != "" && settings.wapbk_grouped_child_ids != undefined ) {
				var quantity_str = "";
				var child_ids = settings.wapbk_grouped_child_ids;
				var child_ids_exploded = child_ids.split( "-" );

				var arrayLength = child_ids_exploded.length;
				var arrayLength = arrayLength - 1;
				for (var i = 0; i < arrayLength; i++) {
					var quantity_grp1 = jQuery( "input[name=\"quantity[" + child_ids_exploded[i] +"]\"]" ).attr( "value" );
					if ( quantity_str != "" )
						quantity_str = quantity_str  + "," + quantity_grp1;
					else
						quantity_str = quantity_grp1;	
					if ( qty_list != "YES" ) {
						if ( quantity_grp1 > 0 ) {
							qty_list = "YES";
						}
					}	
				}
			}
			// for variable products
			var variation_id = 0;
			
			// On some client site the hidden field for the varaition id is not populated using CLASS method. Instead of that it is populating with the NAME.
			// So this fix ensure that if class property does not find then look for the NAME property.
			
			var variation_by_name = document.getElementsByName( MODAL_ID + "variation_id" ).length;

			if ( jQuery( MODAL_ID + ".variation_id" ).length > 0 ) {
				if ( jQuery( MODAL_ID + ".variation_id" ).length > 1 ) {
					var variation_id = "";
					jQuery( MODAL_ID + ".variation_id" ).each( function ( i, obj ) {
						variation_id += jQuery( obj ).val() + ",";
					});
				} else {
					variation_id = jQuery( MODAL_ID + ".variation_id" ).val();;
				}
			}else if( variation_by_name > 0 ){
				variation_id = document.getElementsByName( MODAL_ID + "variation_id" )[0].value; 
			}

			// for bundled products, optional checkbox values need to be passed
			var bundle_optional = [];
			if ( jQuery( ".bundled_product_checkbox" ).length > 0 ) {
				jQuery( ".bundled_product_checkbox" ).each( function ( i, obj ) {
					var bundle_item = jQuery( obj ).attr('name').replace( 'bundle_selected_optional_', '' );
					if ( jQuery( obj ).attr( "checked" ) ) {
						bundle_optional[bundle_item.toString()] = "on";
					} else {
						bundle_optional[bundle_item.toString()] = "off";
					}
				}); 
			}

			// setup the GF options selected
			var gf_options = 0;
			if ( typeof( bkap_functions.update_GF_prices ) === "function" ) {
				var options = parseFloat( jQuery( ".ginput_container_total" ).find( ".gform_hidden" ).val() );
				if ( options > 0 ) {
					gf_options = options;
				}  
			}
			
			var resource_id = 0;
			
			CalculatePrice = "Y";
			
			if( jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").length > 0 ) {
				resource_id = jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").val();

				wapbk_resource_lockout 	= settings.bkap_booked_resource_data[resource_id]['bkap_locked_dates'];
				wapbk_resource_disaabled_dates 	= settings.resource_disable_dates[resource_id];		
				resource_lockoutdates 	= JSON.parse("[" + wapbk_resource_lockout + "]");
				resource_lockoutdates   = resource_lockoutdates.concat(wapbk_resource_disaabled_dates)
				
				if ( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() != "" ){

					if( jQuery.inArray(jQuery( MODAL_ID + "#wapbk_hidden_date" ).val(),resource_lockoutdates) != -1 ) {
						CalculatePrice = "N";
					}
				}				

				if ( "N" == CalculatePrice ) {
					jQuery( MODAL_ID + "#wapbk_hidden_date" ).val( "" );
					jQuery( MODAL_DATE_ID + "#booking_calender" ).val( "" );
					jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
					jQuery( ".qty" ).prop( "disabled", true );

					var data = {
						post_id: bkap_process_params.product_id,
						message: bkap_labels.msg_unavailable,
						notice_type: "error"
					};
					jQuery.post( bkap_process_params.prd_permalink + "?wc-ajax=bkap_add_notice", data, function( response ) {
						if ( !MODAL_FORM_ID ) {
							jQuery( ".woocommerce-breadcrumb" ).prepend( response );
							// Scroll to top
							jQuery( 'html, body' ).animate({
								scrollTop: ( jQuery( '.woocommerce-error' ).offset().top - 100 )
							}, 1000 );
						}else if ( MODAL_FORM_ID ) {
							jQuery( MODAL_FORM_ID ).prepend( response );
						}
					});
				}
			}
			
			var all_fields_set = "YES";
			
			if ( bkap_settings.booking_enable_time == "on" && "on" == global_settings.hide_booking_price ) {
				if ( time_slot_value == "" || typeof time_slot_value == "undefined" ) {
					all_fields_set = "NO";
				}
			} 
			if ( all_fields_set == "YES" && CalculatePrice == "Y" ) {
				var data = {
					id: bkap_process_params.product_id,
					post_id: bkap_process_params.post_id,
					details: jQuery( MODAL_ID + "#wapbk_hidden_date" ).val(),
					timeslots: jQuery( "#wapbk_number_of_timeslots" ).val(),
					timeslot_value: time_slot_value,
					quantity: quantity_str,
					variation_id: variation_id,
					gf_options: gf_options,
					bundle_optional: bundle_optional,
					resource_id: resource_id,
					action: "bkap_call_addon_price",
					tyche: 1
				};

				if ( composite_data ) {
					jQuery.extend( data, { 'composite_data': composite_data } );
				}

				jQuery.post( bkap_process_params.ajax_url, data, function(amt) {
					jQuery( MODAL_ID + "#ajax_img" ).hide();
					if ( isNaN( parseInt( amt ) ) ) {
						// The price will be echoed directly by the respective functions. Hence we just need to eval the response received.
						amt = amt.replace( '"#bkap_price"' , "'" + MODAL_ID + "#bkap_price'" );
						amt = amt.replace( '"#bkap_price_charged"' , "'" + MODAL_ID + "#bkap_price_charged'" );
						amt = amt.replace( '"#total_price_calculated"' , "'" + MODAL_ID + "#total_price_calculated'" );
						eval( amt );
						jQuery( 'body' ).trigger( 'bkap_price_updated', bkap_process_params.bkap_cart_item_key );
					} 
					if( settings.wapbk_grouped_child_ids != "" && settings.wapbk_grouped_child_ids != undefined ) {
						jQuery( ".qty" ).prop( "disabled", false );
						jQuery( ".qty" ).show();
						jQuery( MODAL_ID + "#bkap_price" ).show();
						
						// if time is enabled, then disable the add to cart button unless a time slot has been selected
						if ( bkap_settings.booking_enable_time == "on" && bkap_time_slots !== undefined && bkap_time_slots !== {} ) {
							if ( jQuery( "#time_slot" ).val() != "" && typeof time_slot_value != "undefined" && qty_list == "YES" ) {
								jQuery( ".single_add_to_cart_button" ).prop( "disabled", false );
								jQuery( ".single_add_to_cart_button" ).show();
								jQuery( MODAL_ID + "#bkap_price" ).show();
							} else {
								jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
							}
						} else if ( qty_list == "YES" ) {
							jQuery( ".single_add_to_cart_button" ).prop( "disabled", false );
							jQuery( ".single_add_to_cart_button" ).show();
							jQuery( MODAL_ID + "#bkap_price" ).show();
						} else {
							jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
						}
					} else {
						// Perform the necessary actions. Like enabling/disabling the add to cart buttons etc.
						if ( bkap_settings.booking_enable_time == "on" && bkap_time_slots !== undefined && bkap_time_slots !== {} ) {
							if ( jQuery( "#time_slot" ).val() != "" && typeof( time_slot_value ) != "undefined" && 
								 !isNaN( parseInt( variation_id ) ) && jQuery('#total_price_calculated').val() ) {

								jQuery( ".single_add_to_cart_button" ).prop( "disabled", false );
								jQuery( ".qty" ).prop( "disabled", false );
								jQuery( ".single_add_to_cart_button" ).show();
								jQuery( ".qty" ).show();
								jQuery( MODAL_ID + "#bkap_price" ).show();
							} else {
								jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
								jQuery( ".qty" ).prop( "disabled", true );
								jQuery( MODAL_ID + "#bkap_price" ).show();
							}
						} else {
							if( !isNaN( parseInt( variation_id ) ) && jQuery('#total_price_calculated').val() ){
								jQuery( ".single_add_to_cart_button" ).prop( "disabled", false );
								jQuery( ".qty" ).prop( "disabled", false );
								jQuery( ".single_add_to_cart_button" ).show();
								jQuery( ".qty" ).show();
							}
						}
					}				
					// hide the bundle price
					if ( jQuery( ".bundle_price" ).length > 0 ) {
						jQuery( ".bundle_price" ).hide();
					}

					if ( jQuery( '.composite_price' ).length > 0 ) {
						jQuery( '.composite_price' ).hide();
					}

					var bkap_gf_addon = bkap_wpa_addon = '';

					if( jQuery( ".ginput_container_total" ).length > 0 ){
						bkap_gf_addon = 'active';
					}

					if ( jQuery( "#product-addons-total" ).length > 0 ) {
						bkap_wpa_addon = 'active';
					}

					if( bkap_wpa_addon === 'active' ){
						// Woo Product Addons compatibility
						if ( jQuery( "#product-addons-total" ).length > 0 ) {
							// pass the price for only 1 qty as Woo Product Addons multiplies the amount with the qty
							var price_per_qty = jQuery( "#bkap_price_charged" ).val() / quantity_str ;
							jQuery( "#product-addons-total" ).data( "price", price_per_qty );
						}
						var $cart = jQuery( ".cart" );
						$cart.trigger("woocommerce-product-addons-update");

						bkap_functions.update_wpa_prices();
					}

					// Update the GF product addons total
					if ( typeof( bkap_functions.update_GF_prices ) === "function" && bkap_gf_addon === 'active' ) {
						bkap_functions.update_GF_prices();
					}
				});
			} else {
				jQuery( MODAL_ID + "#ajax_img" ).hide();
			}
		} 
	});

}

/**
 * On Close event for Fixed Blocks
 */
function on_close_fixed_blocks(){

	var current_dt, minDate, split;

	current_dt = jQuery( MODAL_ID + "#wapbk_hidden_date" ).val();

	if ( current_dt !== '' ) {
		split = current_dt.split( "-" );
		split[1] = split[1] - 1;
		minDate = new Date( split[2], split[1], split[0] );
	}

	if ( jQuery( MODAL_ID + "#block_option_enabled" ).val() === "on" && minDate !== '' && minDate !== undefined ) {

		var nod = parseInt( jQuery( MODAL_ID + "#block_option_number_of_day" ).val(), 10 );			
		
		minDate.setDate( minDate.getDate() + nod );

		if ( typeof(checkout_class) !== 'undefined' ) {
			jQuery( checkout_class ).datepicker( "setDate", minDate );
			// Populate the hidden field for checkout
			var dd = minDate.getDate(),
				mm = minDate.getMonth()+1, //January is 0!
				yyyy = minDate.getFullYear(),
				checkout = dd + "-" + mm + "-"+ yyyy;

			jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val( checkout );
		}
	}
}

/**
 *	Set Checkout calendar minDate
 */
function set_checkout_mindate( minDate, settings, bkap_settings, global_settings, calendar_type, checkout_class ) {

	if ( jQuery( MODAL_ID + "#block_option_enabled" ).val() === "on" && minDate !== '' ) {

		var nod = parseInt( jQuery( MODAL_ID + "#block_option_number_of_day" ).val(), 10 );

		minDate.setDate( minDate.getDate() + nod);

		jQuery( checkout_class ).datepicker( "setDate", minDate );
		// Populate the hidden field for checkout
		var dd = minDate.getDate(),
			mm = minDate.getMonth()+1, //January is 0!
			yyyy = minDate.getFullYear(),
			checkout = dd + "-" + mm + "-"+ yyyy;

		jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val( checkout );
		//bkap_calculate_price();
	} else if( bkap_settings.booking_same_day && bkap_settings.booking_same_day === "on" && minDate !== '' ) {
		
		minDate.setDate( minDate.getDate() );
	} else if ( minDate !== '' && minDate !== undefined ) {	

		var enable_minimum = '', 
			minimum_multiple_day = '';

		if ( bkap_settings.enable_minimum_day_booking_multiple === 'on' ) {

			enable_minimum = 'on';
			minimum_multiple_day = bkap_settings.booking_minimum_number_days_multiple;
		} else if( global_settings.minimum_day_booking !== undefined && 
			global_settings.minimum_day_booking === 'on' ) {

			enable_minimum = 'on';
			minimum_multiple_day = global_settings.global_booking_minimum_number_days;
		}
		
		if ( enable_minimum == "on" ) {
			if( minimum_multiple_day == 0 || !minimum_multiple_day ) {
				minimum_multiple_day = 1;
			}
			minDate.setDate( minDate.getDate() + parseInt( minimum_multiple_day ) );
		} else {
			minDate.setDate( minDate.getDate() + 1 );
		}
	}else {
		minDate = new Date();
		minDate.setDate( minDate.getDate() + 1 );
	}

	if ( jQuery( MODAL_ID + "#block_option_enabled" ).val() !== "on" ) {
		jQuery( checkout_class ).datepicker( "option", "minDate", minDate );
	}
	
	if ( calendar_type === 'inline' ) {

		jQuery( checkout_class ).datepicker( "setDate", minDate );
		// Populate the hidden field for checkout

		if ( jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val() === '' ){
			var dd = minDate.getDate(),
				mm = minDate.getMonth()+1, //January is 0!
				yyyy = minDate.getFullYear(),
				checkout = dd + "-" + mm + "-"+ yyyy;

			jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val( checkout );
			//bkap_calculate_price();
		}
	}
}

//******************************************
//This functions checks if the selected date range does not have product holidays or global holidays and sets the hidden date field.
//********************************************

function bkap_set_checkin_date(date,inst){
	var monthValue = inst.selectedMonth+1,
		dayValue = inst.selectedDay,
		yearValue = inst.selectedYear,
		settings = JSON.parse( bkap_process_params.additional_data ),
		bkap_settings = JSON.parse( bkap_process_params.bkap_settings ),
		global_settings = JSON.parse( bkap_process_params.global_settings ),
		bkap_labels = JSON.parse( bkap_process_params.labels ),
		data = {},
		current_dt = '',
		split = [],
		minDate = '',
		variation_id = 0;


	if ( bkap_settings.enable_inline_calendar === 'on') {
		calendar_type = 'inline';
		checkin_class = MODAL_DATE_ID + '#inline_calendar';
		checkout_class = MODAL_END_DATE_ID + '#inline_calendar_checkout';
	}else {
		calendar_type = 'normal';
		checkin_class = MODAL_DATE_ID + '#booking_calender';
		checkout_class = MODAL_END_DATE_ID + '#booking_calender_checkout';
	}
	
	// clear the notices
	data = {
		post_id: bkap_process_params.product_id,
	};
	jQuery.post( bkap_process_params.prd_permalink + "?wc-ajax=bkap_clear_notice", data, function( response ) {
		jQuery( ".woocommerce-error" ).remove();
	});

	current_dt = dayValue + "-" + monthValue + "-" + yearValue;
	jQuery( MODAL_ID + "#wapbk_hidden_date" ).val(current_dt);

	if ( current_dt !== '' ) {
		split = current_dt.split( "-" );
		split[1] = split[1] - 1;
		minDate = new Date( split[2], split[1], split[0] );
	}

	set_checkout_mindate( minDate, settings, bkap_settings, global_settings, calendar_type, checkout_class );

	// check if maxdate needs to be implemented
	if ( bkap_settings.booking_maximum_number_days_multiple !== undefined ) {

		// save the existing date that has been selected
		var date_selected = jQuery( checkout_class ).datepicker( "getDate" );

		var maximum = bkap_settings.booking_maximum_number_days_multiple;

		var maxDate = new Date( split[2], split[1], split[0] );
		maxDate.setDate( maxDate.getDate() + parseInt( maximum ) );

		jQuery( checkout_class ).datepicker( "option", "maxDate", maxDate );

		// now check if the date has been modified
		var new_checkout = jQuery( checkout_class ).datepicker( "getDate" );

		if( date_selected !== null && 
			new_checkout !== null && 
			new_checkout !== date_selected ) {
			
			// if we are in here, it means the checkout date was modified
			// we have to modify the hidden date for checkout
			var dd = new_checkout.getDate();
			var mm = new_checkout.getMonth()+1; //January is 0!
			var yyyy = new_checkout.getFullYear();
			var new_checkout = dd + "-" + mm + "-"+ yyyy;
			jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val( new_checkout );
		}
	}

	// On some client site the hidden field for the varaition id is not populated using CLASS method. Instead of that it is populating with the NAME.
	// So this fix ensure that if class property does not find then look for the NAME property.
	
	var variation_by_name = document.getElementsByName( MODAL_ID + "variation_id" ).length,
		variation_id_count = 0,
		bookings_placed = "",
		variation_array = [],
		field_name = '';
	
	if ( jQuery( MODAL_ID + ".variation_id" ).length > 0 ) {
		if ( jQuery( MODAL_ID + ".variation_id" ).length > 1 ) {
			variation_id = "";
			jQuery( MODAL_ID + ".variation_id" ).each( function ( i, obj ) {
				variation_id += jQuery( obj ).val() + ",";
				variation_id_count++;
			});
		} else {
			variation_id = jQuery( MODAL_ID + ".variation_id" ).val();;
		}
	}else if( variation_by_name > 0 ){
		variation_id = document.getElementsByName( MODAL_ID + "variation_id")[0].value; 
	}

	if ( variation_id_count > 0 ) {
		variation_array = variation_id.split( ',' );
		for ( var var_sub_id in variation_array ){
			if ( var_sub_id !== '' && var_sub_id !== undefined ) {
				field_name = "#wapbk_bookings_placed_" + var_sub_id;

				if ( jQuery( field_name ).length > 0 ) {
					bookings_placed += jQuery( field_name ).val() + ',';
				}
			}
		}
	}else {
		field_name = "#wapbk_bookings_placed_" + variation_id;

		if ( jQuery( field_name ).length > 0 ) {
			bookings_placed = jQuery( field_name ).val();
		}
	}

	var attr_bookings_placed = "";
	if ( settings.wapbk_attribute_list != undefined ) {
		var attribute_list = settings.wapbk_attribute_list.split(",");

		for ( i = 0; i < attribute_list.length; i++ ) {

			if ( attribute_list[i] != "" && jQuery( "#" + attribute_list[i] ).val() > 0 ) {

				var field_name = MODAL_ID + "#wapbk_bookings_placed_" + attribute_list[i];
				if ( jQuery( field_name ).length > 0 ) {
					attr_bookings_placed = attr_bookings_placed + attribute_list[i] + "," + jQuery( field_name ).val() + ";";
				}
			}
		}
	}
	
	/*** Resource Calculations Section Start ***/

	var resource_id_selected 			= 0;
	var bkap_resource_booking_placed 	= "";
	var resource_lockoutdates           = [];

	if( jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").length > 0 ) {
		resource_id_selected 		 = jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").val();
		bkap_resource_booking_placed = settings.bkap_booked_resource_data[resource_id_selected]['bkap_booking_placed'];

		wapbk_resource_lockout 	= settings.bkap_booked_resource_data[resource_id_selected]['bkap_locked_dates'];
		wapbk_resource_disaabled_dates 	= settings.resource_disable_dates[resource_id_selected];

		resource_lockoutdates 	= JSON.parse("[" + wapbk_resource_lockout + "]");
		resource_lockoutdates   = resource_lockoutdates.concat(wapbk_resource_disaabled_dates);

	}

	/*** Resource Calculations Section End ***/

	// Availability Display for the date selected only if setting is enabled
	
		var data = {
			checkin_date: jQuery( MODAL_ID + "#wapbk_hidden_date" ).val(),
			post_id: bkap_process_params.product_id,
			variation_id: variation_id,
			bookings_placed: bookings_placed,
			attr_bookings_placed: attr_bookings_placed,
			resource_id: resource_id_selected,
			resource_bookings_placed: bkap_resource_booking_placed,
			date_in_selected_language: jQuery( MODAL_DATE_ID + "#booking_calender" ).val(), 
			action: "bkap_get_date_lockout"
		};

		jQuery.post( bkap_process_params.ajax_url, data, function( response ) {
			if ( global_settings.booking_availability_display !== undefined && global_settings.booking_availability_display == "on" ) {
				jQuery( MODAL_FORM_ID + "#show_stock_status" ).html( response.message );
			}
				
			if( response.max_qty != "" && response.max_qty != 0 && response.max_qty != "Unlimited" ){
				var max = parseInt( response.max_qty );
			    var max_availability = jQuery("input[name=\"quantity\"]");
			    max_availability.attr( "max", max );
			}
		});
	

	// Check if any date in the selected date range is unavailable
	if (jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() != "" && jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val() != "" ) {
		var CalculatePrice = "Y";
		var split = jQuery( MODAL_ID + "#wapbk_hidden_date" ).val().split( "-" );
		split[1] = split[1] - 1;		
		var CheckinDate = new Date( split[2], split[1], split[0] );
		
		var split = jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val().split( "-" );
		split[1] = split[1] - 1;
		var CheckoutDate = new Date( split[2], split[1], split[0] );
		
		var date = new_end = new Date( CheckinDate );
		var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
		
		var bookedDates = eval( "[" + settings.wapbk_hidden_booked_dates + "]" );
		var holidayDates = eval( "[" + settings.holidays + "]" );
		//var globalHolidays = eval( "[" + jQuery( "#wapbk_booking_global_holidays" ).val() + "]" );

		var count = gd( CheckinDate, CheckoutDate, "days" );
		
		for (var i = 1; i<= count;i++) {
			//Locked Dates
			if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ) {
				CalculatePrice = "N";
				break;
			}

			//Resource Booked date
			if( jQuery.inArray(d + "-" + (m+1) + "-" + y,resource_lockoutdates) != -1 ) {
				CalculatePrice = "N";
				break;
			}

			//Product Holidays
			if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
				CalculatePrice = "N";
				break;
			}
			new_end = new Date(ad(new_end,1));
			var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();													
		}

		if ( "N" == CalculatePrice ) {
			jQuery( MODAL_ID + "#wapbk_hidden_date" ).val( "" );
			jQuery( MODAL_DATE_ID + "#booking_calender" ).val( "" );
			jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
			jQuery( ".qty" ).prop( "disabled", true );

			var data = {
				post_id: bkap_process_params.product_id,
				message: bkap_labels.msg_unavailable,
				notice_type: "error"
			};
			jQuery.post( bkap_process_params.prd_permalink + "?wc-ajax=bkap_add_notice", data, function( response ) {
				if ( !MODAL_FORM_ID ) {
					jQuery( ".woocommerce-breadcrumb" ).prepend( response );
					// Scroll to top
					jQuery( 'html, body' ).animate({
						scrollTop: ( jQuery( '.woocommerce-error' ).offset().top - 100 )
					}, 1000 );
				}else if ( MODAL_FORM_ID ) {
					jQuery( MODAL_FORM_ID ).prepend( response );
				}
			});
		} else {
			bkap_calculate_price();
		}
	}
}

//************************************
//This function sets the hidden checkout date for Multiple day booking feature.
//***********************************

function bkap_get_per_night_price(date,inst){
	var monthValue = inst.selectedMonth+1;
	var dayValue = inst.selectedDay;
	var yearValue = inst.selectedYear;
	var current_dt = dayValue + "-" + monthValue + "-" + yearValue;
	jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val(current_dt);
	bkap_calculate_price();
}

//***********************************
//This function add an ajax call to calculate price and displays the price on the frontend product page for Multiple day booking feature.
//************************************

function bkap_calculate_price(){

	var settings 		= JSON.parse( bkap_process_params.additional_data );
	var bkap_settings 	= JSON.parse( bkap_process_params.bkap_settings );
	var bkap_labels 	= JSON.parse( bkap_process_params.labels );
	
	// Disable the Add to Cart and quantity buttons while the processing is done
	jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
	jQuery( ".qty" ).prop( "disabled", true );

	// clear the notices
	var data = {
		post_id: bkap_process_params.product_id,
	};
	if ( jQuery( ".woocommerce-error" ).length ) {
		jQuery.post( bkap_process_params.prd_permalink + "?wc-ajax=bkap_clear_notice", data, function( response ) {
			jQuery( ".woocommerce-error" ).remove();
		});
	}

	// Check if any date in the selected date range is unavailable
	var CalculatePrice = "Y";				
	var split = jQuery( MODAL_ID + "#wapbk_hidden_date" ).val().split("-");
	
	split[1] = split[1] - 1;		
	var CheckinDate = new Date(split[2],split[1],split[0]);


	var split = jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val().split("-");
	split[1] = split[1] - 1;
	var CheckoutDate = new Date( split[2], split[1], split[0] );

	var date = new_end = new Date( CheckinDate );
	var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();

	var bookedDates = eval( "[" + settings.wapbk_hidden_booked_dates + "]" );
	var holidayDates = eval( "[" + settings.holidays + "]" );
//	var globalHolidays = eval( "[" + jQuery( "#wapbk_booking_global_holidays" ).val() + "]" );

	var count = gd( CheckinDate, CheckoutDate, "days" );
	if( settings.wapbk_same_day == "on" ) {
		count = count + 1;
	}

	var variation_id_selected = 0;

	// On some client site the hidden field for the varaition id is not populated using CLASS method. Instead of that it is populating with the NAME.
	// So this fix ensure that if class property does not find then look for the NAME property.
	
	var variation_by_name = document.getElementsByName( "variation_id" ).length;
	if ( jQuery( ".variation_id" ).length > 0 ) {
		variation_id_selected = jQuery( ".variation_id" ).val();
	}else if( variation_by_name > 0 ){
		variation_id = document.getElementsByName( "variation_id" )[0].value; 
	}
	
	/****** Resource Lockout Etart *******/

	var resource_id_selected = 0;
	
	if( jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").length > 0 ) {
		resource_id_selected 	= jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").val();

		wapbk_resource_lockout 	= settings.bkap_booked_resource_data[resource_id_selected]['bkap_locked_dates'];
		wapbk_resource_disaabled_dates 	= settings.resource_disable_dates[resource_id_selected];		
		var resource_lockoutdates 		= JSON.parse("[" + wapbk_resource_lockout + "]");
		resource_lockoutdates           = resource_lockoutdates.concat(wapbk_resource_disaabled_dates);
	}

	/****** Resource Lockout End *******/

	var field_name = "#wapbk_lockout_" + variation_id_selected;
	var variation_lockoutdates = jQuery( field_name ).val();

	for (var i = 1; i<= count;i++){
		if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ){ // Booked Dates
			CalculatePrice = "N";	
			break;
		} else if( jQuery.inArray(d + "-" + (m+1) + "-" + y,resource_lockoutdates) != -1 ){ // Resource Dates
			CalculatePrice = "N";	
			break;
		} else if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) { // Product Holidays
			CalculatePrice = "N";	
			break;
		}

		if ( typeof variation_lockoutdates != "undefined" ) {
			if ( variation_lockoutdates.indexOf( new_end ) > -1 ) {
				CalculatePrice = "N";	
				break;
			}
		}
		new_end = new Date(ad(new_end,1));
		var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();
	}

	if ( bkap_settings.booking_enable_multiple_day === 'on' ) {
		
		var bkap_rent = eval( "[" + settings.bkap_rent + "]" );

		// Variation Lockout Booked
		var variation_id_selected = 0;

		// On some client site the hidden field for the varaition id is not populated using CLASS method. Instead of that it is populating with the NAME.
		// So this fix ensure that if class property does not find then look for the NAME property.
		
		var variation_by_name = document.getElementsByName( "variation_id" ).length;
		
		if ( jQuery( ".variation_id" ).length > 0 ) {
			variation_id_selected = jQuery( ".variation_id" ).val();
		}else if( variation_by_name > 0 ){
			variation_id = document.getElementsByName( "variation_id" )[0].value; 
		}
		var field_name = "#wapbk_lockout_checkout_" + variation_id_selected;
		var variation_lockoutdates = eval("["+jQuery(field_name).val()+"]");

		var date = new_end = new Date(CheckinDate);
		var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
		for (var i = 1; i<= count;i++) {
			if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bkap_rent) != -1 ) {
				CalculatePrice = "N";
				break;
			}

			if( jQuery.inArray(d + "-" + (m+1) + "-" + y,variation_lockoutdates) != -1 ) {
				CalculatePrice = "N";
				break;
			}

			new_end = new Date(ad(new_end,1));
			var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();
		}
	}

	if ( "N" == CalculatePrice ) {
		jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val("");
		jQuery( MODAL_END_DATE_ID + "#booking_calender_checkout" ).val("");
		jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
		jQuery( ".qty" ).prop( "disabled", true );

		var data = {
			post_id: bkap_process_params.product_id,
			message: bkap_labels.msg_unavailable,
			notice_type: "error"
		};
		setTimeout( function(){
			jQuery.post( bkap_process_params.prd_permalink + "?wc-ajax=bkap_add_notice", data, function( response ) {
				if ( !MODAL_FORM_ID ) {
					jQuery( ".woocommerce-breadcrumb" ).prepend( response );
					// Scroll to top
					jQuery( 'html, body' ).animate({
						scrollTop: ( jQuery( '.woocommerce-error' ).offset().top - 100 )
					}, 1000 );
				}else if ( MODAL_FORM_ID ) {
					jQuery( MODAL_FORM_ID ).prepend( response );
				}
			});
		});
	}

	// Calculate the price	
	if ( CalculatePrice == "Y" && bkap_settings.booking_enable_multiple_day === 'on' ) {
		var oneDay = 24*60*60*1000; // hours*minutes*seconds*milliseconds
		var sold_individually = settings.sold_individually;
		var firstDate = CheckinDate;
		var secondDate = CheckoutDate;

		var value_charge = 0;
		if ( bkap_settings.booking_charge_per_day && bkap_settings.booking_charge_per_day == 'on') {
			value_charge = 1;
		}

		//var diffDays = Math.ceil(Math.abs((firstDate.getTime() - secondDate.getTime())/(oneDay)));
		var firstDate_test	 = Date.UTC( firstDate.getFullYear(), firstDate.getMonth() , firstDate.getDate() );
		var secondDate_test	= Date.UTC( secondDate.getFullYear(), secondDate.getMonth() , secondDate.getDate() );
		var diffDays		   = Math.abs((firstDate_test.valueOf()- secondDate_test.valueOf())/ (oneDay));  // This is the fix for the timezone issue(Berlin).
		diffDays = diffDays + value_charge;
		// set diff days to 1 if it is currently 0, this scenario occurs when user selects date range and then changes the Checkin date same as the checkout date
		if (diffDays == 0) {
			diffDays = 1;
		}
		jQuery( MODAL_ID + "#wapbk_diff_days" ).val( diffDays );

		if ( 'composite' !== settings.product_type ) {
			var quantity_str = jQuery( "input[class=\"input-text qty text\"]" ).prop( "value" );
			if ( typeof quantity_str == "undefined" ) {
				quantity_str = 1;
			}
		}else if ( 'composite' === settings.product_type ) {
			var quantity_str = jQuery( "input[name='quantity']" ).prop( "value" );
			if ( typeof quantity_str == "undefined" ) {
				quantity_str = 1;
			}
		}
		// for grouped products
		var qty_list = "NO";
		if ( settings.wapbk_grouped_child_ids.length > 0 && settings.wapbk_grouped_child_ids != "" ) {
			var quantity_str = "";
			var child_ids = settings.wapbk_grouped_child_ids;
			var child_ids_exploded = child_ids.split( "-" );

			var arrayLength = child_ids_exploded.length;
			var arrayLength = arrayLength - 1;
			for (var i = 0; i < arrayLength; i++) {
				var quantity_grp1 = jQuery( "input[name=\"quantity[" + child_ids_exploded[i] +"]\"]" ).prop( "value" );
				if ( quantity_str != "" )
					quantity_str = quantity_str  + "," + quantity_grp1;
				else
					quantity_str = quantity_grp1;
				if ( qty_list != "YES" ) {
					if ( quantity_grp1 > 0 ) {
						qty_list = "YES";
					}
				}
			}
		}
		// for variable products
		var variation_id = 0;

		// On some client site the hidden field for the varaition id is not populated using CLASS method. Instead of that it is populating with the NAME.
		// So this fix ensure that if class property does not find then look for the NAME property.
		
		var variation_by_name = document.getElementsByName( "variation_id" ).length;
		if ( jQuery( MODAL_ID + ".variation_id" ).length > 0 ) {
			if ( jQuery( MODAL_ID + ".variation_id" ).length > 1 ) {
				var variation_id = "";
				jQuery( MODAL_ID + ".variation_id" ).each( function ( i, obj ) {
					variation_id += jQuery( obj ).val() + ",";
				});
			} else {
				variation_id = jQuery( MODAL_ID + ".variation_id" ).val();;
			}
		}else if( variation_by_name > 0 ){
			variation_id = document.getElementsByName( MODAL_ID + "variation_id" )[0].value; 
		}

		// for bundled products, optional checkbox values need to be passed
		var bundle_optional = [];
		if ( jQuery( ".bundled_product_checkbox" ).length > 0 ) {
			jQuery( ".bundled_product_checkbox" ).each( function ( i, obj ) {
				var bundle_item = jQuery( obj ).attr('name').replace( 'bundle_selected_optional_', '' );
				if ( jQuery( obj ).attr( "checked" ) ) {
					bundle_optional[bundle_item.toString()] = "on";
				} else {
					bundle_optional[bundle_item.toString()] = "off";
				}
			}); 
		}

		var composite_data = '';

		if ( 'composite' === settings.product_type ) {
			composite_data = bkap_functions.bkap_get_composite_selections();
		}

		jQuery( MODAL_ID + "#ajax_img" ).show();
		var data = {
			booking_date: jQuery( MODAL_ID + "#wapbk_hidden_date" ).val(),
			post_id: bkap_process_params.product_id, 
			addon_data: jQuery( "#wapbk_addon_data" ).val(),
			action: "bkap_js"									
		};

		// setup the GF options selected
		var gf_options = 0;
		if ( typeof( bkap_functions.update_GF_prices ) === "function" ) {
			var options = parseFloat( jQuery( ".ginput_container_total" ).find( ".gform_hidden" ).val() );
			if ( options > 0 ) {
				gf_options = options;
			}  
		}
		
		var resource_id = 0;

		if( jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").length > 0 ) {
			resource_id = jQuery( MODAL_FORM_ID + "#bkap_front_resource_selection").val();
		}

		jQuery.post( bkap_process_params.ajax_url, data, function( response ) {
			eval(response);
			
			var attribute_data = bkap_process_params.attr_fields_str;
			
			if( !( Object.getOwnPropertyNames( attribute_data ).length === 0 ) ){
			
				for ( var attribute_data_key in attribute_data ) {
					//attribute_data[ attribute_data_key ] = eval( attribute_data[ attribute_data_key ] );
					attribute_data[ attribute_data_key ] = jQuery( '[name=\"'+attribute_data_key+'\"]').val();
				}
			}
			
			if( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() != "" ){
				var data = {
					current_date: jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val(),
					checkin_date: jQuery( MODAL_ID + "#wapbk_hidden_date" ).val(),
					attribute_selected: jQuery( "#wapbk_variation_value" ).val(),
					currency_selected: jQuery( ".wcml_currency_switcher" ).val(),
					block_option_price: jQuery( "#block_option_price" ).val(),
					post_id: bkap_process_params.product_id,
					diff_days:  jQuery( MODAL_ID + "#wapbk_diff_days" ).val(),
					quantity: quantity_str,  
					variation_id: variation_id, 
					gf_options: gf_options,
					bundle_optional: bundle_optional,
					resource_id: resource_id,
					action: "bkap_get_per_night_price",
					product_type: settings.product_type,
					tyche: 1
					//'.$attribute_fields_str.' 
				};
				
				if( !(Object.getOwnPropertyNames( attribute_data ).length === 0 ) ){
					jQuery.extend( data, attribute_data );
				}

				if ( composite_data ) {
					jQuery.extend( data, { 'composite_data': composite_data } );
				}
				
				jQuery.post( bkap_process_params.ajax_url, data, function(response) {
					jQuery( MODAL_ID + "#ajax_img" ).hide();		
					if ( isNaN( parseInt( response ) ) ) {
						response = response.replace( '"#bkap_price"' , "'" + MODAL_ID + "#bkap_price'" );
						response = response.replace( '"#bkap_price_charged"' , "'" + MODAL_ID + "#bkap_price_charged'" );
						response = response.replace( '"#total_price_calculated"' , "'" + MODAL_ID + "#total_price_calculated'" );
						eval( response );
						jQuery( 'body' ).trigger( 'bkap_price_updated', bkap_process_params.bkap_cart_item_key );
					} 
					if ( settings.wapbk_grouped_child_ids != "" ) {
						jQuery( ".qty" ).prop( "disabled", false );
						jQuery( ".qty" ).show();

						if ( qty_list == "YES" ) {
							jQuery( ".single_add_to_cart_button" ).prop( "disabled", false );
							jQuery( ".single_add_to_cart_button" ).show();
						} else {
							jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
						}
					} else {
						if(! isNaN( parseInt( variation_id ) ) && jQuery('#total_price_calculated').val() ){
							jQuery( ".single_add_to_cart_button" ).prop( "disabled", false );
							jQuery( ".qty" ).prop( "disabled", false );
							jQuery( ".single_add_to_cart_button" ).show();
							jQuery( ".qty" ).show();
						}else if( variation_id === undefined ){
							jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
							jQuery( ".qty" ).prop( "disabled", true );
						}
					}
					jQuery( ".payment_type" ).show();
					if(sold_individually == "yes") {
						jQuery( ".quantity" ).hide();
					}else {
						jQuery( ".quantity" ).show();
					}
					// hide the bundle price
					if ( jQuery( ".bundle_price" ).length > 0 ) {
						jQuery( ".bundle_price" ).hide();
					}

					if ( jQuery( '.composite_price' ).length > 0 ) {
						jQuery( '.composite_price' ).hide();
					}
					
					var bkap_gf_addon = bkap_wpa_addon = '';

					if( jQuery( ".ginput_container_total" ).length > 0 ){
						bkap_gf_addon = 'active';
					}

					if ( jQuery( "#product-addons-total" ).length > 0 ) {
						bkap_wpa_addon = 'active';
					}

					if( bkap_wpa_addon === 'active' ){
						// Woo Product Addons compatibility
						if ( jQuery( "#product-addons-total" ).length > 0 ) {
							// pass the price for only 1 qty as Woo Product Addons multiplies the amount with the qty
							var price_per_qty = jQuery( "#bkap_price_charged" ).val() / quantity_str ;
							jQuery( "#product-addons-total" ).data( "price", price_per_qty );
						}
						var $cart = jQuery( ".cart" );
						$cart.trigger("woocommerce-product-addons-update");

						bkap_functions.update_wpa_prices();
					}

					// Update the GF product addons total
					if ( typeof( bkap_functions.update_GF_prices ) === "function" && bkap_gf_addon === 'active' ) {
						bkap_functions.update_GF_prices();
					}

				});
			}else{
				jQuery( MODAL_ID + "#ajax_img" ).hide();
			}
		});
	}
}

function checkin_date_process( current_dt, calendar_id ) {
	
	var settings = JSON.parse( bkap_process_params.additional_data );
	var bkap_settings = JSON.parse( bkap_process_params.bkap_settings );
	var global_settings = JSON.parse( bkap_process_params.global_settings );
	
	var calendar_id;
	if ( calendar_id ) {
		calendar_id = '#inline_calendar_checkout';
	} else {
		calendar_id = '#booking_calender_checkout';
	}

	if( calendar_id && jQuery( calendar_id ).val() != "" ) {
		var checkout;
		if( jQuery( "#wapbk_hidden_date_checkout" ).val() != "" ){
			checkout = jQuery( "#wapbk_hidden_date_checkout" ).val();
		} else { // this is used to set first time when we click the checkin date.
			var dd = minDate.getDate();
		   var mm = minDate.getMonth()+1; //January is 0!
		   var yyyy = minDate.getFullYear();
		   checkout = dd + "-" + mm + "-"+ yyyy;
		}

		jQuery( "#wapbk_hidden_date_checkout" ).val( checkout );
	}
	
	// This is to ensure that the hidden fields are populated and prices recalculated when users switch between date ranges
	if( jQuery( "#wapbk_hidden_date_checkout" ).val() != "" && jQuery( "#wapbk_hidden_date" ).val() != "" ) {
 		
 		var dd = minDate.getDate();
		var mm = minDate.getMonth()+1; //January is 0!
		var yyyy = minDate.getFullYear();
		var checkout = dd + "-" + mm + "-"+ yyyy;
		var new_checkout_date = new Date(yyyy,mm,dd);
		
		var split_hidden = jQuery( "#wapbk_hidden_date_checkout" ).val().split( "-" );
		var existing_hidden_checkout = new Date( split_hidden[2], split_hidden[1], split_hidden[0] );
		
		if ( new_checkout_date > existing_hidden_checkout ) {
			jQuery( "#wapbk_hidden_date_checkout" ).val( checkout );
		}
		bkap_calculate_price();
	}
}

if( typeof( bkap_process_params ) !== 'undefined' && 
	bkap_process_params.on_change_attr_list !== '' ) {

	jQuery(document).on( 
		"change", 
		"select" + bkap_process_params.on_change_attr_list, 
		function() {

			var settings = JSON.parse( bkap_process_params.additional_data );
			var bkap_settings = JSON.parse( bkap_process_params.bkap_settings );
			var global_settings = JSON.parse( bkap_process_params.global_settings );
		
			// Refresh the datepicker to ensure the correct dates are displayed as available when an attribute is changed
			if ( jQuery( "#inline_calendar" ).length > 0 ) {
				jQuery( "#inline_calendar" ).datepicker( "refresh" );
			}
			var variation_id_selected = 0;

			// On some client site the hidden field for the varaition id is not populated using CLASS method. Instead of that it is populating with the NAME.
			// So this fix ensure that if class property does not find then look for the NAME property.
			
			var variation_by_name = document.getElementsByName( "variation_id" ).length;

			if ( jQuery( ".variation_id" ).length > 0 ) {
				variation_id_selected = jQuery( ".variation_id" ).val();
			}else if( variation_by_name > 0 ){
				variation_id = document.getElementsByName( "variation_id" )[0].value; 
			}
			if ( jQuery( "#wapbk_hidden_date" ).val() != "" )  {
				// if variation lockout is set the date fields should be reset if the date selected is blocked for thew new variation selected
				var recalculate = "YES";

				var field_name = "#wapbk_lockout_" + variation_id_selected;
				var variation_lockoutdates = jQuery( field_name ).val();

				var date_booked = jQuery( "#wapbk_hidden_date" ).val();

				if ( typeof variation_lockoutdates != "undefined" ) {
					if ( variation_lockoutdates.indexOf( date_booked ) > -1 ) {
						recalculate = "NO";	 																	   																		  
						jQuery( "#wapbk_hidden_date" ).val( "" );
						jQuery( "#booking_calender" ).val( "" );
						jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
						jQuery( ".qty" ).prop( "disabled", true );
					} 
				}

				if ( "YES" == recalculate ) {	
					bkap_process_date( jQuery( "#wapbk_hidden_date" ).val() ); 
				//	'.$addon_price.'
			} else {
				jQuery( "#show_stock_status" ).html("");
			}

			} else if ( variation_id_selected > 0 ) {
				var variation_list = settings.wapbk_var_price_list.split( "," );
				for( i=0; i < variation_list.length; i++ ) {
					var price_list = variation_list[i].split( "=>" );
					if ( price_list[0] == variation_id_selected ) {
						jQuery( "#total_price_calculated" ).val( price_list[1] );
						jQuery( "#bkap_price_charged" ).val( price_list[1] );
					}
				}
				if ( "on" == bkap_settings.booking_purchase_without_date ) {
					bkap_purchase_without_date();
				}
			}
		}
	);
}

jQuery(document).on( "change", "#bkap_front_resource_selection", function() {
	var bkap_settings 	= JSON.parse( bkap_process_params.bkap_settings );

	if ( jQuery( "#inline_calendar" ).length > 0 ) {
		jQuery( "#inline_calendar" ).datepicker( "refresh" );
	}

	if ( jQuery( "#inline_calendar_checkout" ).length > 0 ) {
		jQuery( "#inline_calendar_checkout" ).datepicker( "refresh" );
	}
	
	if ( bkap_settings.booking_recurring_booking == "on" && bkap_settings.booking_enable_multiple_day != "on" ) {

		if ( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() != "" )
			bkap_process_date(jQuery( "#wapbk_hidden_date" ).val());

		
	}else if ( bkap_settings.booking_enable_multiple_day == "on" ) {
		if ( jQuery( MODAL_ID + "#wapbk_hidden_date" ).val() != "" && jQuery( MODAL_ID + "#wapbk_hidden_date_checkout" ).val() != "" ){
			bkap_process_date(jQuery( "#wapbk_hidden_date" ).val());
			bkap_calculate_price();
		}
	}
	
});

// we might hv to add some conditions - this has been copy pasted as is from the comment
// moved the patch 1 document.ready to process.js

jQuery(document).ready(function() {

	// this is patch 2.. again we need to check conditions.. maybe merge the different codes

	/*if ( bkap_settings.enable_inline_calendar == 'on' ) {
		jQuery( document ).ready( function(){

			var settings = JSON.parse( bkap_process_params.additional_data );
			
			var delay_date = jQuery( "#wapbk_hidden_default_date" ).val();
			var split_date = delay_date.split( "-" ); 
			var delay_days = new Date (split_date[1] + "/" + split_date[0] + "/"+ split_date[2]);

			// This will check if Rental Addon Same day booking option not enabled then only increase the date by +1 day.
			if( settings.wapbk_same_day != "on" ) { 
				var delay_days_checkout = delay_days.getDate() + 1; 
				delay_days = new Date ( split_date[1] + "/" + delay_days_checkout + "/"+ split_date[2]); 
			}
			
			// This Will ensure if ay week days are blocked via filter then it will populate correct date to front end.
			var disabled_checkout_week_days = eval( "[" + settings.wapbk_block_checkout_weekdays + "]" ); 
			for ( jjj = 0; jjj < disabled_checkout_week_days.length; jjj++ ) {

				if( jQuery.inArray( delay_days.getDay(), disabled_checkout_week_days) != -1 ) {
					var delay_days_checkout = delay_days.getDate() + 1; // james fix
					delay_days = new Date ( split_date[1] + "/" + delay_days_checkout + "/" + split_date[2] ); 
				}
			}
			// check if check and checkout date are same , if it is then increase checkout date
			//var checkin_date = jQuery( "#booking_calendar" ).datepicker( "getDate" );
			var checkin_date = jQuery( "#inline_calendar" ).datepicker( "getDate" );
			if ( delay_days.getTime() == checkin_date.getTime() && settings.wapbk_same_day != "on" ){

				var delay_days_checkout = delay_days.getDate() + 1; 
				delay_days = new Date ( split_date[1] + "/" + delay_days_checkout + "/"+ split_date[2]); 
			}				

			var min_date_co;
			if ( bkap_settings.booking_charge_per_day.length > 0 && bkap_settings.booking_charge_per_day == 'on' && bkap_settings.booking_same_day.length > 0 && bkap_settings.booking_same_day == 'on' ) {
				min_date_co = 0;
			} else {
				min_date_co = 1;
			}

			jQuery( "#inline_calendar_checkout" ).datepicker({
				dateFormat: global_settings.booking_date_format,
				numberOfMonths: parseInt( global_settings.booking_months ),
				//'.$options_checkout_str.' ,
				minDate: min_date_co,
				onSelect: bkap_get_per_night_price,
				beforeShowDay: bkap_check_booked_dates,
				altField: "#booking_calender_checkout",
				onClose: function( selectedDate ) {
					jQuery( "#inline_calendar" ).datepicker( "option", "maxDate", selectedDate );
				},
				}).focus(function (event){
					jQuery.datepicker.afterShow(event);
				});
		
				
				jQuery( "#inline_calendar_checkout" ).datepicker( "setDate", delay_days );
				jQuery( "#inline_calendar_checkout" ).datepicker( "option", "minDate", delay_days ); 
				
				if( ( global_settings.booking_global_selection == "on" && jQuery( "#block_option_enabled" ).val() != "on" ) || ( jQuery( "#wapbk_widget_search" ).val() == "1" ) ) {
					var split = jQuery( "#wapbk_hidden_date_checkout" ).val().split( "-" );
					split[1] = split[1] - 1;		
					var CheckoutDate = new Date(split[2],split[1],split[0]);
					var timestamp = Date.parse(CheckoutDate);
					if (isNaN(timestamp) == false)  { 
						var default_date = new Date(timestamp);
						jQuery( "#inline_calendar_checkout" ).datepicker( "setDate", default_date );
						bkap_calculate_price();
					}
				}
				jQuery( "#checkout_cal" ).click(function() {
				jQuery( "#inline_calendar_checkout" ).datepicker( "show" );
			});
			var split = jQuery( "#wapbk_hidden_date_checkout" ).val().split( "-" );
			if( split != "" ){
				split[1] = split[1] - 1;		
				var CheckoutDate = new Date(split[2],split[1],split[0]);
				var timestamp = Date.parse(CheckoutDate); 
				if( isNaN( timestamp ) == false ) { 
					var default_date_selection = new Date( timestamp );
					//jQuery( "#booking_calendar_checkout" ).datepicker( "setDate", default_date_selection );
					jQuery( "#inline_calendar_checkout" ).datepicker( "setDate", default_date_selection );
				}
			}

			//var checkin_date = jQuery( "#booking_calendar" ).datepicker( "getDate" );
			var checkin_date = jQuery( "#inline_calendar" ).datepicker( "getDate" );
			var date = checkin_date.getDate();
			var month = checkin_date.getMonth() + 1;
			var year = checkin_date.getFullYear();
			
			var date_selected = date + "-" + month + "-" + year;
		
			jQuery( "#wapbk_hidden_date" ).val( date_selected );
		
			// This fix is when the next day is holiday and same day booking is enable. 
			if( settings.wapbk_same_day == "on" ) { 
				//var checkin_date = jQuery( "#booking_calendar" ).datepicker( "getDate" );
				var checkin_date = jQuery( "#inline_calendar" ).datepicker( "getDate" );
				var date = checkin_date.getDate();
				var month = checkin_date.getMonth() + 1;
				var year = checkin_date.getFullYear();

				var date_selected = date + "-" + month + "-" + year;
				jQuery( "#wapbk_hidden_date_checkout" ).val( date_selected );	 
			}else{
				//var checkout_date = jQuery( "#booking_calendar_checkout" ).datepicker( "getDate" );
				var checkout_date = jQuery( "#inline_calendar_checkout" ).datepicker( "getDate" );
				var date = checkout_date.getDate();
				var month = checkout_date.getMonth() + 1;
				var year = checkout_date.getFullYear();

				var date_selected_checkout = date + "-" + month + "-" + year;
				jQuery( "#wapbk_hidden_date_checkout" ).val( date_selected_checkout );
			}

			if ( date_selected != "" && date_selected_checkout != "" ){
				bkap_calculate_price();
			}
			/*jQuery( "#inline_calendar_checkout" ).datepicker( "setDate", date_selected );
			if( ( jQuery( "#wapbk_global_selection" ).val() == "yes" && jQuery( "#block_option_enabled" ).val() != "on" ) || ( jQuery( "#wapbk_widget_search" ).val() == "1" ) ) {
				var split		  = jQuery( "#wapbk_hidden_date_checkout" ).val().split( "-" );
				split[1]		   = split[1] - 1;		
				var CheckoutDate   = new Date( split[2], split[1], split[0] );
				var checkin_date = jQuery( "#inline_calendar" ).datepicker( "getDate" );
			
				if( (checkin_date.getTime() === CheckoutDate.getTime()) && jQuery( "#wapbk_same_day" ).val() != "on" ){
				   CheckoutDate.setDate( CheckoutDate.getDate() + 1 );
				}
				
				var timestamp	  = Date.parse( CheckoutDate );
				if ( isNaN( timestamp ) == false) { 
						var default_date = new Date( timestamp );
				}
				jQuery( "#inline_calendar_checkout" ).datepicker( "option", "minDate", default_date );
				jQuery( "#inline_calendar_checkout" ).datepicker( "setDate", default_date );
			} */
		/*}); 
	}*/

});



// patch 6

/*jQuery(document).ready(function() {
jQuery( "#ajax_img" ).hide();
	//'.$attribute_change_var.' 
	//'.$quantity_change_var.'
	//'.$attribute_change_single_day_var.'
	var formats = ["d.m.y", "d-m-yy","MM d, yy"];
	var split = jQuery("#wapbk_hidden_default_date").val().split("-");
	split[1] = split[1] - 1;		
	var default_date = new Date(split[2],split[1],split[0]);
	var delay_date = jQuery("#wapbk_hidden_default_date").val();
	var split_date = delay_date.split("-");
	var delay_days = new Date (split_date[1] + "/" + split_date[0] + "/"+ split_date[2]);
	// check if the maxdate is a date
	var index = jQuery("#wapbk_number_of_dates").val().indexOf("-");
	if (index > 0) {
		// split the string and create a jQuery date object 
		var split_maxDate = jQuery("#wapbk_number_of_dates").val().split("-");
		var max_date = new Date(split_maxDate[2],split_maxDate[1] - 1,split_maxDate[0]);
	}
	else {
		var max_date = jQuery("#wapbk_number_of_dates").val();
	}


	// This Will ensure if any week days are blocked via filter then it will populate correct date to front end.
	var disabled_checkin_week_days = eval("["+jQuery("#wapbk_block_checkin_weekdays").val()+"]"); 
	for ( jjj = 0; jjj < disabled_checkin_week_days.length; jjj++) {
		
	if( jQuery.inArray( delay_days.getDay(), disabled_checkin_week_days ) != -1 ) {
			var delay_days_checkin = delay_days.getDate() + 1; 
			delay_days = new Date ( split_date[1] + "/" + delay_days_checkin + "/"+ split_date[2]); 
		}
   }

	jQuery.extend(jQuery.datepicker, { afterShow: function(event) {
		jQuery.datepicker._getInst(event.target).dpDiv.css("z-index", 9999);
	}});
	
	jQuery( function() {

		var bkap_settings = JSON.parse( bkap_process_params.bkap_settings );
		var global_settings = JSON.parse( bkap_process_params.global_settings );

		var beforeshow = avd();
		
		if ( 'on' == bkap_settings.booking_enable_multiple_day ) {
			var options_checkin = 'onSelect: bkap_set_checkin_date';
			options_checkin += ',beforeShowDay: bkap_check_booked_dates';
		} else {
			var options_checkin = 'beforeShowDay: bkap_show_book';
			options_checkin += ',onSelect: bkap_show_times';
		}
		
		jQuery("#inline_calendar").datepicker({
			defaultDate: default_date,
			minDate:delay_days,
			maxDate:beforeshow.maxDate,
			altField: "#booking_calender",
			/*dateFormat: "'.$global_settings->booking_date_format.'",
			numberOfMonths: parseInt('.$global_settings->booking_months.'),*/
			/*beforeShowDay: bkap_show_book,
			onSelect: bkap_show_times
			//options_checkin,
			//'.$options_checkin_str.' ,
		}).focus(function (event){
			jQuery.datepicker.afterShow(event);
		});
		if((jQuery("#wapbk_global_selection").val() == "yes" && jQuery("#block_option_enabled").val() != "on") || (jQuery("#wapbk_widget_search").val() == "1")) {
			var split = jQuery("#wapbk_hidden_date").val().split("-");
			split[1] = split[1] - 1;		
			var CheckinDate = new Date(split[2],split[1],split[0]);
			var timestamp = Date.parse(CheckinDate); 
			if (isNaN(timestamp) == false) { 
				var default_date_selection = new Date(timestamp);
				jQuery("#inline_calendar").datepicker("setDate",default_date_selection);
			}
		}
		var split = jQuery("#wapbk_hidden_date").val().split("-");
		if(split != ""){
			split[1] = split[1] - 1;		
			var CheckinDate = new Date(split[2],split[1],split[0]);
			var timestamp = Date.parse(CheckinDate); 
			if (isNaN(timestamp) == false) { 
				var default_date_selection = new Date(timestamp);
				jQuery("#inline_calendar").datepicker("setDate",default_date_selection);
			}
		}
		/*jQuery("#inline_calendar").datepicker("option",jQuery.datepicker.regional[ "'.$curr_lang.'" ]);
		jQuery("#inline_calendar").datepicker("option", "dateFormat","'.$global_settings->booking_date_format.'");
		jQuery("#inline_calendar").datepicker("option", "firstDay","'.$day_selected.'");*/
		//'.$options_checkin_calendar.'
	
		/*jQuery("#inline_calendar").datepicker("option", "onSelect",function(date,inst)  {
			
			jQuery( ".single_add_to_cart_button" ).prop( "disabled", true );
			jQuery( ".qty" ).prop( "disabled", true );
			
			var monthValue = inst.selectedMonth+1;
			var dayValue = inst.selectedDay;
			var yearValue = inst.selectedYear;
			var current_dt = dayValue + "-" + monthValue + "-" + yearValue;

			checkin_date_process( current_dt, true );
						
		});

		if ( bkap_settings.enable_inline_calendar == 'on' ) {
			jQuery( document ).ready( function(){

				var settings = JSON.parse( bkap_process_params.additional_data );
				
				var delay_date = jQuery( "#wapbk_hidden_default_date" ).val();
				var split_date = delay_date.split( "-" ); 
				var delay_days = new Date (split_date[1] + "/" + split_date[0] + "/"+ split_date[2]);

				// This will check if Rental Addon Same day booking option not enabled then only increase the date by +1 day.
				if( settings.wapbk_same_day != "on" ) { 
					var delay_days_checkout = delay_days.getDate() + 1; 
					delay_days = new Date ( split_date[1] + "/" + delay_days_checkout + "/"+ split_date[2]); 
				}
				
				// This Will ensure if ay week days are blocked via filter then it will populate correct date to front end.
				var disabled_checkout_week_days = eval( "[" + settings.wapbk_block_checkout_weekdays + "]" ); 
				for ( jjj = 0; jjj < disabled_checkout_week_days.length; jjj++ ) {

					if( jQuery.inArray( delay_days.getDay(), disabled_checkout_week_days) != -1 ) {
						var delay_days_checkout = delay_days.getDate() + 1; // james fix
						delay_days = new Date ( split_date[1] + "/" + delay_days_checkout + "/" + split_date[2] ); 
					}
				}
				// check if check and checkout date are same , if it is then increase checkout date
				//var checkin_date = jQuery( "#booking_calendar" ).datepicker( "getDate" );
				var checkin_date = jQuery( "#inline_calendar" ).datepicker( "getDate" );
				if ( delay_days.getTime() == checkin_date.getTime() && settings.wapbk_same_day != "on" ){

					var delay_days_checkout = delay_days.getDate() + 1; 
					delay_days = new Date ( split_date[1] + "/" + delay_days_checkout + "/"+ split_date[2]); 
				}				

				var min_date_co;
				if ( bkap_settings.booking_charge_per_day.length > 0 && bkap_settings.booking_charge_per_day == 'on' && bkap_settings.booking_same_day.length > 0 && bkap_settings.booking_same_day == 'on' ) {
					min_date_co = 0;
				} else {
					min_date_co = 1;
				}

				/*jQuery( "#inline_calendar_checkout" ).datepicker({
					dateFormat: global_settings.booking_date_format,
					numberOfMonths: parseInt( global_settings.booking_months ),
					//'.$options_checkout_str.' ,
					minDate: min_date_co,
					onSelect: bkap_get_per_night_price,
					beforeShowDay: bkap_check_booked_dates,
					altField: "#booking_calender_checkout",
					onClose: function( selectedDate ) {
						jQuery( "#inline_calendar" ).datepicker( "option", "maxDate", selectedDate );
					},
					}).focus(function (event){
						jQuery.datepicker.afterShow(event);
					});
			
					
					jQuery( "#inline_calendar_checkout" ).datepicker( "setDate", delay_days );
					jQuery( "#inline_calendar_checkout" ).datepicker( "option", "minDate", delay_days ); 
					
					if( ( global_settings.booking_global_selection == "on" && jQuery( "#block_option_enabled" ).val() != "on" ) || ( jQuery( "#wapbk_widget_search" ).val() == "1" ) ) {
						var split = jQuery( "#wapbk_hidden_date_checkout" ).val().split( "-" );
						split[1] = split[1] - 1;		
						var CheckoutDate = new Date(split[2],split[1],split[0]);
						var timestamp = Date.parse(CheckoutDate);
						if (isNaN(timestamp) == false)  { 
							var default_date = new Date(timestamp);
							jQuery( "#inline_calendar_checkout" ).datepicker( "setDate", default_date );
							bkap_calculate_price();
						}
					}
					jQuery( "#checkout_cal" ).click(function() {
					jQuery( "#inline_calendar_checkout" ).datepicker( "show" );
				});*/
				/*var split = jQuery( "#wapbk_hidden_date_checkout" ).val().split( "-" );
				if( split != "" ){
					split[1] = split[1] - 1;		
					var CheckoutDate = new Date(split[2],split[1],split[0]);
					var timestamp = Date.parse(CheckoutDate); 
					if( isNaN( timestamp ) == false ) { 
						var default_date_selection = new Date( timestamp );
						//jQuery( "#booking_calendar_checkout" ).datepicker( "setDate", default_date_selection );
						jQuery( "#inline_calendar_checkout" ).datepicker( "setDate", default_date_selection );
					}
				}

				//var checkin_date = jQuery( "#booking_calendar" ).datepicker( "getDate" );
				var checkin_date = jQuery( "#inline_calendar" ).datepicker( "getDate" );
				var date = checkin_date.getDate();
				var month = checkin_date.getMonth() + 1;
				var year = checkin_date.getFullYear();
				
				var date_selected = date + "-" + month + "-" + year;
			
				jQuery( "#wapbk_hidden_date" ).val( date_selected );
			
				// This fix is when the next day is holiday and same day booking is enable. 
				if( settings.wapbk_same_day == "on" ) { 
					//var checkin_date = jQuery( "#booking_calendar" ).datepicker( "getDate" );
					var checkin_date = jQuery( "#inline_calendar" ).datepicker( "getDate" );
					var date = checkin_date.getDate();
					var month = checkin_date.getMonth() + 1;
					var year = checkin_date.getFullYear();

					var date_selected = date + "-" + month + "-" + year;
					jQuery( "#wapbk_hidden_date_checkout" ).val( date_selected );	 
				}else{
					//var checkout_date = jQuery( "#booking_calendar_checkout" ).datepicker( "getDate" );
					var checkout_date = jQuery( "#inline_calendar_checkout" ).datepicker( "getDate" );
					var date = checkout_date.getDate();
					var month = checkout_date.getMonth() + 1;
					var year = checkout_date.getFullYear();

					var date_selected_checkout = date + "-" + month + "-" + year;
					jQuery( "#wapbk_hidden_date_checkout" ).val( date_selected_checkout );
				}

				if ( date_selected != "" && date_selected_checkout != "" ){
					bkap_calculate_price();
				}
				/*jQuery( "#inline_calendar_checkout" ).datepicker( "setDate", date_selected );
				if( ( jQuery( "#wapbk_global_selection" ).val() == "yes" && jQuery( "#block_option_enabled" ).val() != "on" ) || ( jQuery( "#wapbk_widget_search" ).val() == "1" ) ) {
					var split		  = jQuery( "#wapbk_hidden_date_checkout" ).val().split( "-" );
					split[1]		   = split[1] - 1;		
					var CheckoutDate   = new Date( split[2], split[1], split[0] );
					var checkin_date = jQuery( "#inline_calendar" ).datepicker( "getDate" );
				
					if( (checkin_date.getTime() === CheckoutDate.getTime()) && jQuery( "#wapbk_same_day" ).val() != "on" ){
					   CheckoutDate.setDate( CheckoutDate.getDate() + 1 );
					}
					
					var timestamp	  = Date.parse( CheckoutDate );
					if ( isNaN( timestamp ) == false) { 
							var default_date = new Date( timestamp );
					}
					jQuery( "#inline_calendar_checkout" ).datepicker( "option", "minDate", default_date );
					jQuery( "#inline_calendar_checkout" ).datepicker( "setDate", default_date );
				} */
			/*}); 
		}
	});
});*/
//jQuery("#ui-datepicker-div").wrap("<div class=\"hasDatepicker\"></div>" );
