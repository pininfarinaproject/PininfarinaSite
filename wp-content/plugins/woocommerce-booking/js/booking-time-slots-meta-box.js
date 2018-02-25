/**
 * This file is responsible for the loading the timeslots using ajax and its paginations.
 * @since: 4.5.0
 * 
 */

var bkap_time_slots_functions = function ( jQuery ) {  

    return {
        bkap_add_new_record: function () {
            var bkap_data_toolbar = jQuery( '.bkap_date_timeslot_div' ).find( '.bkap_toolbar' );
            var bkap_time_slots   =  bkap_data_toolbar.attr( 'data-time-slots');
            var bkap_time_slots_parsing = JSON.parse( bkap_time_slots );

            if ( Object.keys( bkap_time_slots_parsing ).length > 0 ) {

                var booking_times = {},
                j = bkap_data_toolbar.attr( 'data-bkap-total');
                jQuery( '.bkap_date_timeslot_div' ).find( '.bkap_added' ).each(function (i, row) {

                    if ( jQuery( this ).find( 'select[id^="bkap_dateday_selector_"]' ).val() && 
                         jQuery( this ).find( 'input[id^="bkap_from_time_"]' ).val() ) {

                        for( var bkap_days in jQuery( this ).find( 'select[id^="bkap_dateday_selector_"]' ).val() ){
                            j++;
                            bkap_time_slots_parsing = bkap_time_slots_functions.bkap_record_change( j, bkap_time_slots_parsing, bkap_days, this );
                        }
                    }
                });
                
                bkap_data_toolbar.attr( 'data-time-slots' , JSON.stringify( bkap_time_slots_parsing ) );
                    
            }
        },
        bkap_add_row_updated_class: function ( e ) {

            jQuery(this).closest('tr').addClass('bkap_row_updated');
        },
        bkap_update_row : function () {

            var bkap_data_toolbar = jQuery( '.bkap_date_timeslot_div' ).find( '.bkap_toolbar' );

            var bkap_time_slots   =  bkap_data_toolbar.attr( 'data-time-slots');

            var bkap_time_slots_parsing = JSON.parse( bkap_time_slots );

            if ( Object.keys( bkap_time_slots_parsing ).length > 0 ) {
                
                var booking_times = {},
                id = '',
                j = '';
                jQuery( '.bkap_date_timeslot_div' ).find( '.bkap_row_updated' ).each(function (i, row) {

                    id = jQuery( this ).attr( 'id');
                    j = id.replace( 'bkap_date_time_row_', '' );
                    if ( jQuery( this ).find( 'select[id^="bkap_dateday_selector_"]' ).val() && 
                         jQuery( this ).find( 'input[id^="bkap_from_time_"]' ).val() ) {

                        for( var bkap_days in jQuery( this ).find( 'select[id^="bkap_dateday_selector_"]' ).val() ){
                            
                            bkap_time_slots_functions.bkap_record_change( j, bkap_time_slots_parsing, bkap_days, this );
                        }
                    }
                });
                
                bkap_data_toolbar.attr( 'data-time-slots' , JSON.stringify( bkap_time_slots_parsing ) );
                    
            }
        },
        bkap_record_change: function ( j, bkap_time_slots_parsing, bkap_days, bkap_tr ) {
            bkap_time_slots_parsing[j] = {};
            bkap_time_slots_parsing[j][ 'day' ] = (jQuery( bkap_tr ).find( 'select[id^="bkap_dateday_selector_"]' ).val())[bkap_days];
            
            bkap_time_slots_parsing[j][ 'from_time' ] = jQuery( bkap_tr ).find( 'input[id^="bkap_from_time_"]' ).val();

            bkap_time_slots_parsing[j][ 'to_time' ] = jQuery( bkap_tr ).find( 'input[id^="bkap_to_time_"]' ).val();
            
            bkap_time_slots_parsing[j][ 'lockout_slot' ]= jQuery( bkap_tr ).find( 'input[id^="bkap_lockout_time_"]' ).val();
            
            bkap_time_slots_parsing[j][ 'slot_price' ] = jQuery( bkap_tr ).find( 'input[id^="bkap_price_time_"]' ).val();
            
            var global_check = '';
            if ( jQuery( bkap_tr ).find( 'input[id*="bkap_global_time"]' ).attr( 'checked' ) ) {
                global_check = 'on';
            } 
            bkap_time_slots_parsing[j][ 'global_time_check' ] = global_check;
            
            bkap_time_slots_parsing[j][ 'booking_notes' ] = jQuery( bkap_tr ).find( 'textarea[id^="bkap_note_time_"]' ).val();
            return bkap_time_slots_parsing;
        },
        bkap_reload_page: function ( e, bkap_id ) {
            var bkap_add_page_data_attribute = jQuery( '.bkap_date_timeslot_div' ).find( '.bkap_toolbar' );
            var bkap_current_page = bkap_add_page_data_attribute.attr( 'data-page' );
         
            var bkap_time_slots   =  bkap_add_page_data_attribute.attr( 'data-time-slots');

            var bkap_time_slots_parsing = JSON.parse( bkap_time_slots );
            
            if ( Object.keys( bkap_time_slots_parsing ).length > 0 ) {

                delete bkap_time_slots_parsing[ bkap_id ];
                
                var bkap_upated_count = Object.keys(bkap_time_slots_parsing).length;
                
                bkap_add_page_data_attribute.attr( 'data-time-slots' , JSON.stringify( bkap_time_slots_parsing ) );
                
                bkap_time_slots_functions.bkap_update_total_number_of_record ( bkap_upated_count );
            }
        },
        bkap_update_total_number_of_record: function ( bkap_udated_count ){
            jQuery( '.bkap_display_count_num' ).html(bkap_udated_count);
        }       
    }
}(jQuery);


var bkap_each_time_slot_tr_height = 0;
/**
 *  Ajax for timeslots
 */ 
var bkap_time_slots_meta_box_ajax = {

    bkap_init: function () {
        jQuery( 'li.bkap_availability a' ).on( 'click', this.bkap_initial_load );
        jQuery( document.body ).on( 'change', '.bkap_default', bkap_time_slots_functions.bkap_add_row_updated_class  )
        jQuery( document.body ).on( 'bkap_added', bkap_time_slots_functions.bkap_add_new_record );
        jQuery( document.body ).on( 'bkap_row_updated', bkap_time_slots_functions.bkap_update_row );
        jQuery( document.body ).on( 'bkap_row_deleted', bkap_time_slots_functions.bkap_reload_page );
    },

    bkap_initial_load: function () {
        if ( 1 === jQuery( '#bkap_date_timeslot_table' ).find( '.bkap_replace_response_data' ).length ) {
            bkap_time_slots_meta_box_pagenav.bkap_go_to_page();
        }
    },
    /**
     * Load Time slots via Ajax
     *
     * @param {Int} page (default: 1)
     * @param {Int} per_page (default: 10)
     */
    bkap_load_time_slots: function( bkap_page, bkap_per_page ) {

        // apply Select 2 when displaying records in Weekdays/Dates And It's Timeslots table.
        
        bkap_page     = bkap_page || 1;
        bkap_per_page = bkap_per_page || bkap_time_slots_params.bkap_time_slots_per_page;

        var wrapper = jQuery( '.bkap_replace_response_data' );

        bkap_time_slots_meta_box_ajax.bkap_block();

        jQuery.ajax({
            url: bkap_time_slots_params.ajax_url,
            data: {
                action          : 'bkap_load_time_slots',
                bkap_product_id : bkap_time_slots_params.bkap_product_id,
                bkap_page       : bkap_page,
                bkap_per_page   : bkap_per_page
            },
            type: 'POST',
            success: function( bkap_response ) {
                wrapper.empty().replaceWith( bkap_response );

                if ( jQuery(".bkap_dateday_selector").length > 0 ) {
                  jQuery(".bkap_dateday_selector").select2({
                    allowClear: false,
                    width: '100%',
                  });
                }
                /**
                 * Here, we will change the page number, so when we click next time. We have correct page number.
                 */
                var bkap_add_page_data_attribute = jQuery( '.bkap_date_timeslot_div' ).find( '.bkap_toolbar' );
                bkap_add_page_data_attribute.attr( 'data-page', bkap_page );
                bkap_time_slots_meta_box_ajax.unblock();
            }
        });
    },
    /**
     * Block edit screen
     */
    bkap_block: function() {
        jQuery( '#bkap-tabbed-nav' ).block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
    },

    /**
     * Unblock edit screen
     */
    unblock: function() {
        jQuery( '#bkap-tabbed-nav' ).unblock();
    },
}  

var bkap_time_slots_meta_box_pagenav = {

    bkap_init: function () {
        jQuery( document.body )
            .on( 'change', '.bkap_toolbar .bkap_page_selector', this.bkap_page_selector )
            .on( 'click', '.bkap_toolbar .bkap_next_page', this.bkap_next_page )
            .on( 'click', '.bkap_toolbar .bkap_prev_page', this.bkap_prev_page )
            .on( 'click', '.bkap_toolbar .bkap_last_page', this.bkap_last_page )
            .on( 'click', '.bkap_toolbar .bkap_first_page', this.bkap_first_page );
    },
    /**
     * Navigate on time slots pages
     *
     * @param {Int} page
     * @param {Int} qty
     */
    bkap_go_to_page: function( bkap_page, bkap_qty ) {
        bkap_page = bkap_page || 1;
        bkap_time_slots_meta_box_pagenav.bkap_set_page( bkap_page );
    },
    /**
     * Set page
     */
    bkap_set_page: function( bkap_page ) {
        jQuery( '.bkap_toolbar .bkap_page_selector' ).val( bkap_page ).first().change();
    },

    /**
     * Paginav pagination selector
     */
    bkap_page_selector: function() {

        wrapper  = jQuery( '.bkap_date_timeslot_div' ).find( '.bkap_toolbar' );

        var bkap_selected = parseInt( jQuery( this ).val(), 10 );
        jQuery( '.bkap_toolbar .bkap_page_selector' ).val( bkap_selected );
        jQuery( '.bkap_date_timeslot_div' ).trigger( 'bkap_added' );
        jQuery( '.bkap_date_timeslot_div' ).trigger( 'bkap_row_updated' );

        /**
         * We will remove old data before we populate next page data.
         */
        jQuery( '#bkap_date_timeslot_table tr[id^="bkap_date_time_row_"]' ).each(function (i, row) {
            bkap_each_time_slot_tr_height = bkap_each_time_slot_tr_height + jQuery(this).height();
            row.remove();
        });
        jQuery( '.bkap_replace_response_data' ).height( ( bkap_each_time_slot_tr_height / 2 ) );
        bkap_time_slots_meta_box_pagenav.bkap_change_classes( bkap_selected, parseInt( wrapper.attr( 'data-total_pages' ), 10 ) );
        bkap_time_slots_meta_box_ajax.bkap_load_time_slots( bkap_selected );
    },

    bkap_next_page : function () {

        if ( bkap_time_slots_meta_box_pagenav.bkap_check_is_enabled( this ) ) {

           
            jQuery( '.bkap_date_timeslot_div' ).trigger( 'bkap_added' );
            jQuery( '.bkap_date_timeslot_div' ).trigger( 'bkap_row_updated' );

            var wrapper     = jQuery( '.bkap_date_timeslot_div' ).find( '.bkap_toolbar' ),
                total_pages = parseInt( wrapper.attr( 'data-total_pages' ), 10 ),
                next_page   = parseInt( wrapper.attr( 'data-page' ), 10 ) + 1,
                new_page    = ( total_pages >= next_page ) ? next_page : total_pages;

            /**
             * We will remove old data before we populate next page data.
             */
            jQuery( '#bkap_date_timeslot_table tr[id^="bkap_date_time_row_"]' ).each(function (i, row) {
                bkap_each_time_slot_tr_height = bkap_each_time_slot_tr_height + jQuery(this).height();
                row.remove();
            });
           jQuery( '.bkap_replace_response_data' ).height( ( bkap_each_time_slot_tr_height / 2 ) );

            bkap_time_slots_meta_box_pagenav.bkap_set_page( new_page );
        }

        return false;
    },
    /**
     * Check button if enabled and if don't have changes
     *
     * @return {Bool}
     */
    bkap_check_is_enabled: function( current ) {
        return ! jQuery( current ).hasClass( 'disabled' );
    },
    bkap_prev_page: function () {
        if ( bkap_time_slots_meta_box_pagenav.bkap_check_is_enabled( this ) ) {
            var wrapper   = jQuery( '.bkap_date_timeslot_div' ).find( '.bkap_toolbar' ),
                prev_page = parseInt( wrapper.attr( 'data-page' ), 10 ) - 1,
                new_page  = ( 0 < prev_page ) ? prev_page : 1;
            

            jQuery( '.bkap_date_timeslot_div' ).trigger( 'bkap_added' );
            jQuery( '.bkap_date_timeslot_div' ).trigger( 'bkap_row_updated' );

            /**
             * We will remove old data before we populate next page data.
             */
            jQuery( '#bkap_date_timeslot_table tr[id^="bkap_date_time_row_"]' ).each(function (i, row) {
                bkap_each_time_slot_tr_height = bkap_each_time_slot_tr_height + jQuery(this).height();
                row.remove();
            });
            jQuery( '.bkap_replace_response_data' ).height( ( bkap_each_time_slot_tr_height / 2 ) );
            bkap_time_slots_meta_box_pagenav.bkap_set_page( new_page );
        }

        return false;
    },
    /**
     * Change "disabled" class on pagenav
     */
    bkap_change_classes:  function ( selected, total ) {
        var first_page = jQuery( '.bkap_toolbar .bkap_first_page' ),
            prev_page  = jQuery( '.bkap_toolbar .bkap_prev_page' ),
            next_page  = jQuery( '.bkap_toolbar .bkap_next_page' ),
            last_page  = jQuery( '.bkap_toolbar .bkap_last_page' );

        if ( 1 === selected ) {
            first_page.addClass( 'disabled' );
            prev_page.addClass( 'disabled' );
        } else {
            first_page.removeClass( 'disabled' );
            prev_page.removeClass( 'disabled' );
        }

        if ( total === selected ) {
            next_page.addClass( 'disabled' );
            last_page.addClass( 'disabled' );
        } else {
            next_page.removeClass( 'disabled' );
            last_page.removeClass( 'disabled' );
        }
    },

    bkap_last_page: function () {
        if ( bkap_time_slots_meta_box_pagenav.bkap_check_is_enabled( this ) ) {
            var last_page = jQuery( '.bkap_date_timeslot_div' ).find( '.bkap_toolbar' ).attr( 'data-total_pages' );
            jQuery( '.bkap_date_timeslot_div' ).trigger( 'bkap_added' );
            jQuery( '.bkap_date_timeslot_div' ).trigger( 'bkap_row_updated' );
            /**
             * We will remove old data before we populate next page data.
             */
            jQuery( '#bkap_date_timeslot_table tr[id^="bkap_date_time_row_"]' ).each(function (i, row) {
                bkap_each_time_slot_tr_height = bkap_each_time_slot_tr_height + jQuery(this).height();
                row.remove();
            });
            jQuery( '.bkap_replace_response_data' ).height( ( bkap_each_time_slot_tr_height / 2 ) );
            bkap_time_slots_meta_box_pagenav.bkap_set_page( last_page );
        }

        return false;
    },

    bkap_first_page: function () {
        if ( bkap_time_slots_meta_box_pagenav.bkap_check_is_enabled( this ) ) {

            jQuery( '.bkap_date_timeslot_div' ).trigger( 'bkap_added' );
            jQuery( '.bkap_date_timeslot_div' ).trigger( 'bkap_row_updated' );
            /**
             * We will remove old data before we populate next page data.
             */
            jQuery( '#bkap_date_timeslot_table tr[id^="bkap_date_time_row_"]' ).each(function (i, row) {
                bkap_each_time_slot_tr_height = bkap_each_time_slot_tr_height + jQuery(this).height();
                row.remove();
            });
            jQuery( '.bkap_replace_response_data' ).height( ( bkap_each_time_slot_tr_height / 2 ) );
            bkap_time_slots_meta_box_pagenav.bkap_set_page( 1 );
        }

        return false;
    },
}

bkap_time_slots_meta_box_ajax.bkap_init();
bkap_time_slots_meta_box_pagenav.bkap_init();
