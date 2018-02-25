function ad(dateObj, numDays)
{
	return dateObj.setDate(dateObj.getDate() + numDays);
}

function gd(date1, date2, interval)
{
	var second = 1000,
	minute = second * 60,
	hour = minute * 60,
	day = hour * 24,
	week = day * 7;
	date1 = new Date(date1).getTime();
	date2 = (date2 == 'now') ? new Date().getTime() : new Date(date2).getTime();
	var timediff = date2 - date1;
	if (isNaN(timediff)) return NaN;
		switch (interval) {
		case "years":
			return date2.getFullYear() - date1.getFullYear();
		case "months":
			return ((date2.getFullYear() * 12 + date2.getMonth()) - (date1.getFullYear() * 12 + date1.getMonth()));
		case "weeks":
			return Math.floor(timediff / week);
		case "days":
			return Math.floor(timediff / day);
		case "hours":
			return Math.floor(timediff / hour);
		case "minutes":
			return Math.floor(timediff / minute);
		case "seconds":
			return Math.floor(timediff / second);
		default:
			return undefined;
	}
}

function nd(date) {
	
	var settings = JSON.parse( bkap_init_params.additional_data );
	var labels = JSON.parse( bkap_init_params.labels );
	
	var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
	var currentdt = m + '-' + d + '-' + y;
	
	var dt = new Date();
	var today = dt.getMonth() + '-' + dt.getDate() + '-' + dt.getFullYear();
	
	var holidayDates = eval( '[' + settings.holidays + ']' );

/*	var globalHolidays = eval('['+jQuery("#booking_global_holidays").val()+']');
	
	for (iii = 0; iii < globalHolidays.length; iii++)
	{
		if( jQuery.inArray(d + '-' + (m+1) + '-' + y,globalHolidays) != -1 )
		{
			return [false,"","Holiday"];
		}
	} */
	for (ii = 0; ii < holidayDates.length; ii++)
	{
		if( jQuery.inArray(d + '-' + (m+1) + '-' + y,holidayDates) != -1 )
		{
			return [false, "", labels.holiday_label ];
		}
}
	return [true];
}

function sp(date) {
	var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
	var currentdt = m + '-' + d + '-' + y;
	
	var dt = new Date();
	var today = dt.getMonth() + '-' + dt.getDate() + '-' + dt.getFullYear();
	
	var settings = JSON.parse( bkap_init_params.additional_data );
	
	var deliveryDates = eval( '[' + settings.specific_dates + ']' );

	for (ii = 0; ii < deliveryDates.length; ii++)
	{
		if( jQuery.inArray(d + '-' + (m+1) + '-' + y,deliveryDates) != -1 )
		{
			return [true];
		}
	}
	return [false];
}

function fixed_range( date, id_booking ) {
	
	var settings = JSON.parse( bkap_init_params.additional_data );
	
	var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
	var currentdt = m + '-' + d + '-' + y;
	
	var dt = new Date();
	var today = dt.getMonth() + '-' + dt.getDate() + '-' + dt.getFullYear();
	
	var FixedRangeDates = eval( '[' + settings.fixed_ranges + ']' );
	var in_range = false;
	for (i = 0; i < FixedRangeDates.length; ) {
		var j = i;
		var range_start = FixedRangeDates[ j ];
		var range_end = FixedRangeDates[ j+1 ];
		
		var split_start_1 = range_start.split( '-' );
		var split_end_1 = range_end.split( '-' );
		
		var start_date1 = new Date ( split_start_1[1] + '/' + split_start_1[0] + '/' + split_start_1[2] );
		start_new = ( start_date1.getMonth() + 1 ) + "/" + start_date1.getDate() + "/" + start_date1.getFullYear();
		
		var end_date1 = new Date ( split_end_1[1] + '/' + split_end_1[0] + '/' + split_end_1[2] );

		end_new = ( end_date1.getMonth() + 1 ) + "/" + end_date1.getDate() + "/" + end_date1.getFullYear();

		var start_range = new Date( start_new );
		
		var end_date = new Date( end_new );

		if( id_booking && ( id_booking == "booking_calender_checkout" || id_booking == "inline_calendar_checkout" ) ){
			end_date.setDate( end_date.getDate() + 1 );
		}
		
		var end_range = new Date(end_date.getTime()); 
		end_range.setDate(end_range.getDate()+1); // Add one day to include the last date of the range
	
		if ( date >= start_range && date < end_range ) {
			in_range = true;
		} 
		i = i+2;
	}
	if ( in_range == true ) {
		return true;
	} else {
		return false;
	}
}
function avd(date) {
	
	var settings = JSON.parse( bkap_init_params.additional_data );
	// if a fixed date range is enabled, calculate the start and end date for each year
	if ( settings.fixed_ranges !== undefined ) {
		
		var start_date = settings.min_date;
		var split_date = start_date.split( '-' );
		var start_day = new Date ( split_date[1] + '/' + split_date[0] + '/' + split_date[2] );
		
		var end_date = settings.number_of_dates;
		var split_date_end = end_date.split( '-' );
		var end_day = new Date( split_date_end[1] + "/" + split_date_end[0] + "/" + split_date_end[2] );
		
		if( isNaN( start_day ) ) {
			start_day = new Date();
		}
		start = ( start_day.getMonth() + 1 ) + "/" + start_day.getDate() + "/" + start_day.getFullYear();
		end = ( end_day.getMonth() + 1 ) + "/" + end_day.getDate() + "/" + end_day.getFullYear();

		return {		
			minDate: new Date( start ),
	        maxDate: new Date( end )
	    };
	} else {
		
		var bkap_settings = JSON.parse( bkap_init_params.bkap_settings );
		
		var delay_date = settings.min_date;
		var split_date = delay_date.split('-');
		var delay_days = new Date (split_date[1] + '/' + split_date[0] + '/' + split_date[2]);
	
		var noOfDaysToFind = parseInt( settings.number_of_dates );
		var recurring_days = bkap_settings.booking_recurring_booking;
		var specific_dates = bkap_settings.booking_specific_booking;
		
		var weekdays = bkap_settings.booking_recurring;
		
		if(isNaN(delay_days))
		{
			delay_days = new Date();
		}
		if(isNaN(noOfDaysToFind))
		{
			noOfDaysToFind = 30;
		} 
		
		var today = new Date();
		if ( delay_days.setHours(0, 0, 0, 0 ) == today.setHours(0, 0, 0, 0 ) ) { 
			// if mindate is today, reduce the number of days by 1 as today is not being taken into consideration
			noOfDaysToFind -= 1;
		}
	
		var minDate = delay_days;
		var date = new Date();
		var t_year = date.getFullYear();
		var t_month = date.getMonth()+1;
		var t_day = date.getDate();
		var t_month_days = new Date(t_year, t_month, 0).getDate();
		
		/**
		 * Fetch First available date
		 */
		var bkap_first_available_date     = settings.default_date;
		var bkap_split_first_date         = bkap_first_available_date.split('-');
		var bkap_first_available_date_obj = new Date ( bkap_split_first_date[1] + '/' + bkap_split_first_date[0] + '/' + bkap_split_first_date[2] );
		
		/**	
		 * Get First date enabled.
		 * It will be set as an mindate for the calendar	
		 */
		start = ( bkap_first_available_date_obj.getMonth()+1 ) + "/" + bkap_first_available_date_obj.getDate() + "/" + bkap_first_available_date_obj.getFullYear();

		var start_month = delay_days.getMonth()+1;

		/**
         * Pass that first enabled date to find the last enabled date. 
         * It will add the 'number of date to chose' to the first available date.
         */ 
		var end_date = new Date( ad( bkap_first_available_date_obj , noOfDaysToFind ) );

		end = ( end_date.getMonth()+1 ) + "/" + end_date.getDate() + "/" + end_date.getFullYear();
		//Calculate the last specific date
		var specific_max_date = start;
		var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
		var currentdt = m + '-' + d + '-' + y;
	
		var dt = new Date();
		var today = dt.getMonth() + '-' + dt.getDate() + '-' + dt.getFullYear();

		if ( settings.specific_dates !== false ) {
			var deliveryDates = eval( '[' + settings.specific_dates + ']' );
			
			for (ii = 0; ii < deliveryDates.length; ii++) {
				var split = deliveryDates[ii].split('-');
				var specific_date = split[1] + '/' + split[0] + '/' + split[2];
				var diff = gd(specific_max_date , specific_date , 'days');
				if (diff >= 0) {
					specific_max_date = specific_date;
				}
			}
		}
		var loopCounter = gd(start , end , 'days');
		var prev = delay_days;
		var new_l_end, is_holiday;
		if ( loopCounter >= 1 ) {
			for(var i=1; i<=loopCounter; i++) {
				var l_start = new Date(start);
				var l_end = new Date(end);
				new_l_end = l_end;
				var new_date = new Date(ad(l_start,i));
		
				var day = "";
				if ( bkap_settings.booking_enable_multiple_day == '')
				{
					day = 'booking_weekday_' + new_date.getDay();
					day_check = weekdays[ day ];
					is_specific = sp(new_date);
				}
				else 
				{
					is_specific = 'true';
					day_check = 'on';
				}
				
				is_holiday = nd(new_date);
		
				if (is_specific == "false" || is_holiday != "true")
				{
					if( day_check != "on" || is_holiday != "true")
					{
						new_l_end = l_end = new Date(ad(l_end,1));
						end = (l_end.getMonth()+1) + "/" + l_end.getDate() + "/" + l_end.getFullYear();
						if (recurring_days != "on" || specific_dates == "on")
						{
							diff = gd(l_end , specific_max_date , 'days');
							if (diff >= 0)
							{
								loopCounter = gd(start , end , 'days');
							}
						}
						else
						{
							loopCounter = gd(start , end , 'days');
						}
					}
				}
			}
		}else if ( loopCounter === 0 ) {
			l_end = new Date(start);
		}
		return {
			minDate: new Date(start),
	        maxDate: l_end
	    };
	}
}