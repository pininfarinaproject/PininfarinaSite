/**
 * Edit Booking Class for manipulating data on modal pop-up
 * @namespace bkap_edit_booking_class
 * @since 4.2.0
 */
var bkap_edit_booking_class = (function( $ ){

    /**
     * Event when Price is updated in the template
     *
     * @fires event:bkap_price_updated
     * @since 4.2.0
     */
    $( 'body' ).on( 'bkap_price_updated', function( e, confirm_button_id ){
        var confirm_button = '#confirm_bookings_' + confirm_button_id;

        var time_slot = $( '#modal-body-'+confirm_button_id ).find( '#time_slot' );
        if( time_slot.length > 0 ) {
            if ( $( time_slot ).val() ){
                $( confirm_button ).prop( 'disabled', false );
            }else{
                $( confirm_button ).prop( 'disabled', true );
            }
        }else{
            $( confirm_button ).prop( 'disabled', false );
        }
    });

    /**
     * Event when Addon Price is updated in the template
     *
     * @fires event:bkap_update_addon_prices
     * @since 4.2.0
     */
    $( 'body' ).on( 'bkap_update_addon_prices', function( e, confirm_button_id, options_total ){
        var booking_totals = parseFloat( $( "#total_price_calculated" ).val() ) + parseFloat( options_total );
        $( "#total_price_calculated" ).val( booking_totals );
        $( "#bkap_price_charged" ).val( booking_totals );
    });

    return {

        /** 
         * Variable containing Modal Pop-up instance
         *
         * @var {string}
         * @memberof bkap_edit_booking_class
         * @since 4.2.0
         */
        bkap_edit_modal: document.getElementById('bkap_edit_modal'),

        /**
         * Callback Function when Edit Booking Button is clicked
         *
         * @function bkap_edit_bookings
         * @memberof bkap_edit_booking_class
         * @param {string} product_id - Product ID of the Booking to be edited
         * @param {string} bkap_cart_item_key - Cart Item Key present in cart
         * @since 4.2.0
         */
        bkap_edit_bookings: function( product_id, bkap_cart_item_key ) {

            var bkap_init_params = window['bkap_init_params_' + product_id];
            window['bkap_init_params'] = bkap_init_params;

            var bkap_process_params = window['bkap_process_params_' + product_id];
            bkap_process_params.bkap_cart_item_key = bkap_cart_item_key;
            window['bkap_process_params'] = bkap_process_params;

            var bkap_edit_params = window['bkap_edit_params_' + bkap_cart_item_key];
            window['bkap_edit_params'] = bkap_edit_params;

            var chosen_fixed_block = "";
            if( $( "#modal-body-" + bkap_cart_item_key + " #chosen_fixed_block" ).length > 0 ){
               chosen_fixed_block = $( "#modal-body-" + bkap_cart_item_key + " #chosen_fixed_block" ).val();

               $( "#modal-body-" + bkap_cart_item_key + " #block_option" ).val(chosen_fixed_block);
              
                var exploded_id = chosen_fixed_block.split('&');

                
                jQuery("#modal-body-" + bkap_cart_item_key + " #bkap-booking-form " + "#block_option_start_day").val(exploded_id[0]);
                jQuery("#modal-body-" + bkap_cart_item_key + " #bkap-booking-form "  + "#block_option_number_of_day").val(exploded_id[1]);
                jQuery("#modal-body-" + bkap_cart_item_key + " #bkap-booking-form " + "#block_option_price").val(exploded_id[2]);

            }

            bkap_process_init( $, bkap_process_params);
            // Get the modal
            var modal = document.getElementById('bkap_edit_modal_' + bkap_cart_item_key);

            modal.style.display = "block";

            /**
             * Indicates that the pop-up is visible now
             * 
             * @event bkap_edit_popup_enabled
             * @param {string} product_id - Product ID
             * @param {string} bkap_cart_item_key - Cart Item Key
             * @since 4.2.0
             */
            $( 'body' ).trigger( 'bkap_edit_popup_enabled', [ product_id, bkap_cart_item_key ] );

            var global_settings = JSON.parse( bkap_process_params.global_settings ),
                bkap_settings   = JSON.parse( bkap_process_params.bkap_settings ),
                settings        = JSON.parse( bkap_process_params.additional_data ),
                other_data      = {},
                calendar_type   = '',
                checkin_class   = '',
                checkout_class  = '',
                variation_id    = bkap_edit_params.bkap_cart_item.variation_id,
                time_slot_lockout = '',
                attr_lockout    = '',
                booking_checkin_div = '#modal-body-' + bkap_cart_item_key + ' #bkap-booking-form #bkap_start_date ';
                booking_checkout_div = '#modal-body-' + bkap_cart_item_key + ' #bkap-booking-form #bkap_end_date ';

            if ( bkap_settings.enable_inline_calendar === 'on') {
                calendar_type = 'inline';
                checkin_class = booking_checkin_div + '#inline_calendar';
                checkout_class = booking_checkout_div + '#inline_calendar_checkout';
            }else {
                calendar_type = 'normal';
                checkin_class = booking_checkin_div + '#booking_calender';
                checkout_class = booking_checkout_div + '#booking_calender_checkout';
            }

            var field_name = "#wapbk_timeslot_lockout_" + variation_id;
            if ( $( field_name ).length > 0 ) {
                time_slot_lockout = $( field_name ).val();
            }

            if ( settings.wapbk_attribute_list !== undefined ) {
                var attribute_list = settings.wapbk_attribute_list.split( "," );

                for ( i = 0; i < attribute_list.length; i++ ) {

                    if ( attribute_list[i] != "" && $( "#" + attribute_list[i] ).val() > 0 ) {

                        var field_name = "#wapbk_timeslot_lockout_" + attribute_list[i];
                        if ( $( field_name ).length > 0 ) {
                            attr_lockout = attr_lockout + attribute_list[i] + "," + $( field_name ).val() + ";";
                        }
                    }
                }
            }

            other_data = {
                calendar_type: calendar_type,
                checkin_class: checkin_class,
                time_slots_arr: bkap_settings.booking_time_settings,
                variation_id: variation_id,
                time_slot_lockout: time_slot_lockout,
                attr_lockout: attr_lockout
            }

            default_display_date( settings, global_settings, bkap_settings, bkap_process_params, other_data );

            var datepick_instance = $( checkin_class ).datepicker();

            var split = $( "#modal-body-" + bkap_cart_item_key + " #wapbk_hidden_date" ).val().split( "-" );
            split[1] = split[1] - 1;
            
            var resource_id = 0;
            if( $( "#modal-body-" + bkap_cart_item_key + " #chosen_resource_id" ).length > 0 ){
               resource_id = $( "#modal-body-" + bkap_cart_item_key + " #chosen_resource_id" ).val();

               $( "#modal-body-" + bkap_cart_item_key + " #bkap_front_resource_selection" ).val(resource_id);
            }

            var CheckinDate = new Date( split[2], split[1], split[0] );

            var timestamp = Date.parse( CheckinDate );

            $( checkin_class ).datepicker( 'option', 'defaultDate', CheckinDate );
            $( checkin_class ).datepicker( 'option', 'setDate', CheckinDate );

            default_checkout_date = display_checkout_date( checkout_class );

            setTimeout( function(){
                var confirm_button = '#confirm_bookings_' + bkap_cart_item_key;
                //var confirm_button = ':input[id="'+bkap_cart_item_key+'"]';
                $( confirm_button ).prop( 'disabled', true );
            }, 1500 );
        },

        /**
         * Callback Function when Close Button is clicked
         *
         * @function bkap_close_popup
         * @memberof bkap_edit_booking_class
         * @param {string} product_id - Product ID of the Booking to be edited
         * @param {string} bkap_cart_item_key - Cart Item Key present in cart
         * @since 4.2.0
         */
        bkap_close_popup: function( product_id, bkap_cart_item_key ) {
            
            var modal = document.getElementById('bkap_edit_modal_' + bkap_cart_item_key);

            modal.style.display = "none";
            window['bkap_init_params'] = '';
            window['bkap_process_params'] = '';
        },

        /**
         * Callback Function when Confirm is clicked
         *
         * @function bkap_confirm_booking
         * @memberof bkap_edit_booking_class
         * @param {string} product_id - Product ID of the Booking to be edited
         * @param {string} bkap_cart_item_key - Cart Item Key present in cart
         * @since 4.2.0
         */
        bkap_confirm_booking: function( product_id, bkap_cart_item_key ) {

            var cart_item_obj = {},
                cart_item_key = bkap_edit_params.bkap_cart_item_key,
                data = {},
                booking_data = {};

            if( bkap_edit_params.bkap_page_type !== '' && bkap_edit_params.bkap_page_type !== 'view-order' ){
                
                if ( bkap_edit_params.bkap_cart_item !== undefined && bkap_edit_params.bkap_cart_item !== {} ) {
                    cart_item_obj = bkap_edit_params.bkap_cart_item;
                }else {

                }

                cart_item_obj['bkap_booking'][0]['date'] = $( MODAL_DATE_ID + '#booking_calender' ).val();
                cart_item_obj['bkap_booking'][0]['hidden_date'] = $( MODAL_ID + '#wapbk_hidden_date' ).val();
                cart_item_obj['bkap_booking'][0]['price'] = $( '#total_price_calculated' ).val();

                if( $( MODAL_END_DATE_ID + '#booking_calender_checkout' ).val() !== '' && $( MODAL_END_DATE_ID + '#booking_calender_checkout' ).val() !== undefined &&
                    $( MODAL_ID + '#wapbk_hidden_date_checkout' ).val() !== '' && $( MODAL_ID + '#wapbk_hidden_date_checkout' ).val() !== undefined ) {

                    cart_item_obj['bkap_booking'][0]['date_checkout'] = $( MODAL_END_DATE_ID + '#booking_calender_checkout' ).val();
                    cart_item_obj['bkap_booking'][0]['hidden_date_checkout'] = $( MODAL_ID + '#wapbk_hidden_date_checkout' ).val();
                }

                if( $( MODAL_FORM_ID + '#show_time_slot ' + '#time_slot' ).val() !== '' && $( MODAL_FORM_ID + '#show_time_slot ' + '#time_slot' ).val() !== undefined ){

                    cart_item_obj['bkap_booking'][0]['time_slot'] = $( MODAL_FORM_ID + '#show_time_slot ' + '#time_slot' ).val();
                }
                
                if( $( MODAL_FORM_ID + '#bkap_front_resource_selection').val() !== '' && $( MODAL_FORM_ID + '#bkap_front_resource_selection').val() !== undefined ){

                    cart_item_obj['bkap_booking'][0]['resource_id'] = $( MODAL_FORM_ID + '#bkap_front_resource_selection' ).val();
                }
                
                cart_item_obj['line_subtotal'] = $( '#total_price_calculated' ).val();
                cart_item_obj['line_total'] = $( '#total_price_calculated' ).val();
                
                data = {
                    cart_item_obj: cart_item_obj,
                    cart_item_key: cart_item_key,
                    page_type: bkap_edit_params.bkap_page_type,
                    action: 'bkap_update_edited_bookings'
                }
            }else if ( bkap_edit_params.bkap_page_type !== '' && bkap_edit_params.bkap_page_type === 'view-order' ) {

                booking_data = {
                    booking_date: $( MODAL_DATE_ID + '#booking_calender' ).val(),
                    hidden_date: $( MODAL_ID + '#wapbk_hidden_date' ).val(),
                    booking_date_checkout: $( MODAL_END_DATE_ID + '#booking_calender_checkout' ).val(),
                    hidden_date_checkout: $( MODAL_ID + '#wapbk_hidden_date_checkout' ).val(),
                    booking_price: $( '#total_price_calculated' ).val()
                }

                if( $( MODAL_FORM_ID + '#bkap_front_resource_selection').val() !== '' && $( MODAL_FORM_ID + '#bkap_front_resource_selection').val() !== undefined ){

                    booking_data['resource_id'] = $( MODAL_FORM_ID + '#bkap_front_resource_selection' ).val();
                }

                if ( $( MODAL_FORM_ID + '#show_time_slot ' + '#time_slot' ).val() ) {
                    booking_data['time_slot'] = $( MODAL_FORM_ID + '#show_time_slot ' + '#time_slot' ).val();
                }

                data = {
                    booking_data: booking_data,
                    item_id: cart_item_key,
                    product_id: product_id,
                    order_id: bkap_edit_params.bkap_order_id,
                    page_type: bkap_edit_params.bkap_page_type,
                    action: 'bkap_update_edited_bookings'
                }
                var save_progress = document.getElementById( 'bkap_save' );
                save_progress.style.display = 'block';
            }

            $.post( 
                bkap_process_params.ajax_url, 
                data, 
                function( response ) {
                    
                    /*var modal = document.getElementById('bkap_edit_modal_' + bkap_cart_item_key);
                    modal.style.display = "none";*/
                    bkap_edit_booking_class.bkap_close_popup( product_id, bkap_cart_item_key );

                    if ( bkap_edit_params.bkap_page_type === 'cart' ) {
                        $( document.body ).trigger( 'wc_update_cart' );
                    }else if ( bkap_edit_params.bkap_page_type === 'checkout' ) {
                        $( document.body ).trigger( 'update_checkout' );
                    }else if ( bkap_edit_params.bkap_page_type === 'view-order' ) {
                        save_progress.style.display = 'none';
                        window.location.reload();
                    }
                }
            );

        }

    }

})( jQuery )
