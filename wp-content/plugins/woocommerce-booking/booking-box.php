<?php 
include_once( 'bkap-common.php' );
include_once( 'includes/product-calendar-sync-settings.php' );

class bkap_booking_box_class {
    
    /**
    * This function updates the booking settings for each product in the wp_postmeta table in the database. 
    * It will be called when update / publish button clicked on admin side.
    **/
    public static function bkap_process_bookings_box( $post_id, $post ) {
        global $wpdb;
        // Save Bookings
        $product_bookings        =   array();
        $duplicate_of            =   bkap_common::bkap_get_product_id( $post_id );
        $booking_settings        =   get_post_meta(  $duplicate_of, 'woocommerce_booking_settings', true );

        $booking_settings = (array) apply_filters( 'bkap_save_product_settings', $booking_settings, $duplicate_of );
        update_post_meta( $duplicate_of, 'woocommerce_booking_settings', $booking_settings );
    }
    
    /**
    *This function adds a meta box for booking settings on product page.
    **/
    public static function bkap_booking_box() {
        
        add_meta_box(   'woocommerce-booking', 
                        __( 'Booking', 'woocommerce-booking' ),
                        array( 'bkap_booking_box_class', 'bkap_meta_box' ),
                        'product',
                        'normal',
                        'core'
        );
    }
       
    public static function bkap_print_js() {
        if ( get_post_type() == 'product' ) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function () {
                 jQuery("#bkap-tabbed-nav").zozoTabs({
                       
                       orientation: "vertical",
                       position: "top-left",
                       size: "medium",
                       animation: {
                            easing: "easeInOutExpo",
                            duration: 400,
                            effects: "none"
                       },
                 });
            });
            </script>
            <?php
        }
    }
                   
    /**
    * This function displays the settings for the product in the Booking meta box on the admin product page.
    **/
    public static function bkap_meta_box() {
        
        global $post, $wpdb;
        $duplicate_of = bkap_common::bkap_get_product_id( $post->ID );
        
        $booking_settings   = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );        
        do_action( 'bkap_add_resource_section', $duplicate_of );
        
        $post_type = get_post_type( $duplicate_of );        
        ?>
        <div id='bkap-tabbed-nav'>
            <ul>
                <li class = "bkap_general" >
                    <a id="addnew" class="bkap_tab"><i class="fa fa-cog" aria-hidden="true"></i><?php _e( 'General', 'woocommerce-booking' );?> </a>
                </li>
                <li class = "bkap_availability" >
                    <a id="settings" class="bkap_tab"><i class="fa fa-calendar" aria-hidden="true"></i><?php _e( 'Availability', 'woocommerce-booking' ); ?></a>
                </li>
                <?php
                    do_action( 'bkap_add_tabs', $duplicate_of ); 
                ?>
            </ul>
            <div>
                <!-- General tab starts here -->  
                
                <div id="booking_options">
                    
                    <!-- Enable Booking div starts here -->           
                    
                    <div id="enable_booking_options_section" class="booking_options-flex-main">
                        <?php do_action( 'bkap_before_enable_booking', $duplicate_of );    ?>    
                        <div class="booking_options-flex-child">
                            <label for="booking_enable_date"> <?php _e( 'Enable Booking', 'woocommerce-booking' );?> </label>
                        </div>
                        <?php 
                            $booking_settings     =   get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
                        
                            $enable_date            = '';
                            $only_day               = ''; // the only days radio button
                            $single_days            = '';      // single day radio button
                            $date_time              = ''; // date & time radio button
                            $multiple_days          = ''; // multiple days radio button
                            $display_only_day       = 'display:none;'; // display only days div
                            $multiple_days_setup    = 'style="display:none;"'; // fields in the settings tab for multiple days
                            $purchase_without_date  = '';
                            
                            if ( isset( $booking_settings[ 'booking_enable_date' ] ) && $booking_settings[ 'booking_enable_date' ] == 'on' ) {
                                   
                                   $enable_date         = 'checked';
                                   $only_day            = 'checked'; 
                                   $single_days         = 'checked'; 
                                   $date_time           = ''; 
                                   $multiple_days       = ''; 
                                   $display_only_day    = '';
                                   $specific_date_table = 'display:none;';
                                   
                                   if( isset( $booking_settings[ 'booking_specific_booking' ]) && $booking_settings[ 'booking_specific_booking' ] == 'on' ){
                                       $specific_date_table = '';
                                   }
                                   
                            }
                            if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && 'on' == $booking_settings[ 'booking_enable_multiple_day' ] ) {
                                
                                $only_day               = 'checked'; // the only days radio button
                                $multiple_days          = 'checked'; // multiple days radio button
                                $single_days            = ''; // single day radio button
                                $date_time              = ''; // date & time radio button
                                $purchase_without_date  = 'display:none';
                                $multiple_days_setup    = 'display="block"';
                            
                            } else if ( isset( $booking_settings[ 'booking_enable_time' ] ) && 'on' == $booking_settings[ 'booking_enable_time' ] ) {
                                
                                $only_day           = '';
                                $date_time          = 'checked';
                                $display_only_day   = 'display:none;';
                            }
                        ?>
                        
                        <div class="booking_options-flex-child">
                            <label class="bkap_switch">
                              <input type="checkbox" id="booking_enable_date" name="booking_enable_date" <?php echo $enable_date;?> >
                              <div class="bkap_slider round"></div>
                            </label>
                        </div>
                        
                        <div class="booking_options-flex-child bkap_help_class">
                            <img class="help_tip" width="16" height="16"  data-tip="<?php _e( 'Enable Booking Date on Products Page', 'woocommerce-booking' );?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                        </div>
                        
                    </div>
                    <hr/>                    
                    <!-- Booking Type div starts here -->
                    <?php do_action( 'bkap_before_booking_method_select', $duplicate_of );?>    
                    <div id="enable_booking_types_section" class="booking_types-flex-main">
                        
                        <div class="booking_types-flex-child">
                            <label for="booking_enable_type"> <?php _e( 'Booking Type', 'woocommerce-booking' );?> </label>
                        </div>
                        
                        <div class="booking_types-flex-child"> 
                            <div class="booking_types-flex-child-day">
                                <input type="radio" id="enable_booking_day_type" name="booking_enable_type" class="enable_booking_type" value="booking_enable_only_day" <?php echo $only_day;?>></input>
                                <label for="enable_booking_day_type"> <?php _e( 'Only Day', 'woocommerce-booking' );?> </label>
                            </div>
                            
                            <div class="booking_types-flex-child-day">
                               <input type="radio" id="enable_booking_day_and_time_type" name="booking_enable_type" class="enable_booking_type" value="booking_enable_date_and_time" <?php echo $date_time;?>></input>
                               <label for="enable_booking_day_and_time_type"> <?php _e( 'Date & Time', 'woocommerce-booking' );?> </label>
                            </div>
                        </div>
                        
                        <div class="booking_types-flex-child bkap_help_class">
                            <img class="help_tip" width="16" height="16"  data-tip="<?php _e( 'Choose booking type for your business', 'woocommerce-booking' );?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                        </div>
                         
                    </div>
                    
                    <!-- Div for Single Day and Multiple Days starts here -->
                    <div id="enable_only_day_booking_section" style="margin-top:20px;<?php echo $display_only_day; ?>" class="only_day_booking_section_flex_main" >
                        <div class="only_day_booking_section_flex_child1"></div>
                        <div class="only_day_booking_section_flex_child2">
                            <div class="only_day_booking_section_flex_child21">
                                <input type="radio" id="enable_booking_single" name="booking_enable_only_day" class="enable_only_day" value="booking_enable_single_day" <?php echo $single_days;?>></input>
                                <label for="enable_booking_single"> <?php _e( 'Single Day', 'woocommerce-booking' );?> </label>
                            </div>
                            <div class="only_day_booking_section_flex_child22">
                                <input type="radio" id="enable_booking_multiple_days" name="booking_enable_only_day" class="enable_only_day" value="booking_enable_multiple_days" <?php echo $multiple_days;?>></input>
                                <label for="enable_booking_multiple_days"> <?php _e( 'Multiple Nights', 'woocommerce-booking' );?> </label>
                            </div>
                        </div>
                        <div class="only_day_booking_section_flex_child3 bkap_help_class"></div>
                        
                    </div>
                    
                    <!-- Descrpition of the selected booking method will be display -->
                    <p class="show-booking-day-description"></p>
                    
                    <hr/>
                    <div id="enable_inline_calendar_section" class="booking_options-flex-main" style="margin-top:15px;margin-bottom:15px;">
                                
                        <div class="booking_options-flex-child">
                            <label for="enable_inline_calendar"> <?php _e( 'Enable Inline Calendar', 'woocommerce-booking' );?> </label>
                        </div>
                                
                        <?php 
                        $enable_inline_calendar = '';
                        if( isset( $booking_settings[ 'enable_inline_calendar' ] ) && $booking_settings[ 'enable_inline_calendar' ] == 'on' ) {
                            $enable_inline_calendar = 'checked';
                        }
                        ?>        
                        <div class="booking_options-flex-child">
                            <label class="bkap_switch">
                              <input type="checkbox" id="enable_inline_calendar" name="enable_inline_calendar" <?php echo $enable_inline_calendar;?> >
                              <div class="bkap_slider round"></div>
                            </label>
                        </div>
                        
                        <div class="booking_options-flex-child bkap_help_class">
                            <img class="help_tip" width="16" height="16"  data-tip="<?php _e( 'Enable Inline Calendar on Products Page', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                        </div>
                        
                    </div>
                    <?php 
                        do_action( 'bkap_before_purchase_without_date', $duplicate_of );
                    ?>
                    <div id="purchase_wo_date_section" class="booking_options-flex-main" style="<?php echo $purchase_without_date;?>">
                        
                        <div class="booking_options-flex-child">
                            <label for="booking_purchase_without_date"> <?php _e( 'Purchase without choosing a date', 'woocommerce-booking' ); ?> </label>
                        </div>
                                        
                        <?php 
                            $date_show = '';
                            if( isset( $booking_settings['booking_purchase_without_date'] ) && $booking_settings['booking_purchase_without_date'] == 'on' ) {
                                   $without_date = 'checked';
                            } else {
                                   $without_date = '';
                            }
                        ?>
                        <div class="booking_options-flex-child">
                            <label class="bkap_switch">
                              <input type="checkbox" id="booking_purchase_without_date" name="booking_purchase_without_date" <?php echo $without_date;?> >
                              <div class="bkap_slider round"></div>
                            </label>
                        </div>
                        
                        <div class="booking_options-flex-child bkap_help_class">
                            <img class="help_tip" width="16" height="16"  data-tip="<?php _e( 'Enables your customers to purchase without choosing a date. Select this option if you want the ADD TO CART button always visible on the product page. Cannot be applied to products that require confirmation.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                        </div>
                        
                    </div>
                    <?php 
                        do_action( 'bkap_after_purchase_wo_date', $duplicate_of );
                        do_action( 'bkap_before_product_holidays', $duplicate_of );
                        do_action( 'bkap_after_product_holidays', $duplicate_of );
                    ?>
                    <hr style="margin-top:20px;" />
                    <?php 
                    if( isset( $post_type ) && $post_type === 'product' ) {
                        self::bkap_save_button( 'bkap_save_booking_options' );
                    }
                    ?>
                     <div id='general_update_notification' style='display:none;'></div>          
                </div>
                <!-- Booking Options tab ends here -->
                 
                <div id="booking_settings" style="display:none;">
                              
                    <table class="form-table">
                
                        <?php 
                        do_action( 'bkap_before_minimum_days', $duplicate_of );
                        ?>
                        <tr>
                            <th style="width:50%;">
                                <label for="booking_minimum_number_days"><?php _e( 'Advance Booking Period (in hours)', 'woocommerce-booking' ); ?></label>
                            </th>
                            <td>
                                <?php 
                                $min_days = 0;
                                if ( isset( $booking_settings['booking_minimum_number_days'] ) && $booking_settings['booking_minimum_number_days'] != "" ) {
                                    $min_days = $booking_settings['booking_minimum_number_days'];
                                }
                                ?>
                                <input type="number" style="width:90px;" name="booking_minimum_number_days" id="booking_minimum_number_days" min="0" max="9999" value="<?php echo sanitize_text_field( $min_days, true );?>" >
                            </td>
                            <td>
                                <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Enable Booking after X number of hours from the current time. The customer can select a booking date/time slot that is available only after the minimum hours that are entered here. For example, if you need 12 hours advance notice for a booking, enter 12 here.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                            </td>
                        </tr>
                        <?php 
                        do_action( 'bkap_before_number_of_dates', $duplicate_of );
                        ?>
                        <tr>
                            <th style="width:50%;">
                                <label for="booking_maximum_number_days"><?php _e( 'Number of dates to choose', 'woocommerce-booking' ); ?></label>
                            </th>
                            <td>
                                <?php 
                                $max_date = "";
                                $readonly_no_of_dates_to_choose = "";
                                
                                // if custom range is added then readonly number of dates to choose field. 
                                if( isset( $booking_settings[ 'booking_date_range' ] ) && $booking_settings[ 'booking_date_range' ] != "" && count( $booking_settings[ 'booking_date_range' ]) > 0 ){
                                    $readonly_no_of_dates_to_choose = "readonly";
                                }
                                 
                                if ( isset( $booking_settings[ 'booking_maximum_number_days' ] ) && $booking_settings[ 'booking_maximum_number_days' ] != "" ) {
                                    $max_date = $booking_settings[ 'booking_maximum_number_days' ];
                                } else {
                                    $max_date = "30";
                                }
                                ?>
                                <input type="number" style="width:90px;" name="booking_maximum_number_days" id="booking_maximum_number_days" min="0" max="9999" value="<?php echo sanitize_text_field( $max_date, true );?>" <?php echo $readonly_no_of_dates_to_choose; ?> >
                            </td>
                            <td>
                               <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'The maximum number of booking dates you want to be available for your customers to choose from. For example, if you take only 2 months booking in advance, enter 60 here.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                            </td>
                        </tr>
                        <?php 
                        do_action( 'bkap_after_number_of_dates', $duplicate_of );
                        ?>
                        <tr class="multiple_days_setup" <?php echo $multiple_days_setup; ?>>
                            <th style="width:50%;">
                                <label for="booking_lockout_date"><?php _e( 'Maximum Bookings On Any Date', 'woocommerce-booking' ); ?></label>
                            </th>
                            <td>
                                <?php 
                                $lockout_date = "";
                                if ( isset( $booking_settings['booking_date_lockout'] ) ) {
                                       $lockout_date = $booking_settings['booking_date_lockout'];
                                       //sanitize_text_field( $lockout_date, true )
                                } else {
                                       $lockout_date = "60";
                                }
                                ?>
                                <input type="number" style="width:90px;" name="booking_lockout_date" id="booking_lockout_date" min="0" max="9999" value="<?php echo sanitize_text_field( $lockout_date, true );?>" >
                            </td>
                            <td>
                                <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Set this field if you want to place a limit on maximum bookings on any given date. If you can manage up to 15 bookings in a day, set this value to 15. Once 15 orders have been booked, then that date will not be available for further bookings.', 'woocommerce-booking' );?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                            </td>
                        </tr>
                        <?php
                        do_action( 'bkap_after_lockout_date', $duplicate_of ); 
                        ?>
                        <tr class="multiple_days_setup" <?php echo $multiple_days_setup; ?>>
                            <th style="width:50%;">
                                <label for="booking_minimum_number_days_multiple"><?php _e( 'Minimum number of nights to book', 'woocommerce-booking' ); ?></label>
                            </th>
                            <td>
                                <?php 
                                $minimum_day_multiple = "";
                                if ( isset( $booking_settings[ 'booking_minimum_number_days_multiple' ] ) && $booking_settings[ 'booking_minimum_number_days_multiple' ] != "" ) {
                                    $minimum_day_multiple = $booking_settings[ 'booking_minimum_number_days_multiple' ];
                                } else {
                                    $minimum_day_multiple = "0";
                                }   
                                ?>
                                <input type="number" style="width:90px;" name="booking_minimum_number_days_multiple" id="booking_minimum_number_days_multiple" min="0" max="9999" value="<?php echo $minimum_day_multiple;?>" >
                            </td>
                            <td>
                                <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'The minimum number of booking days you want to book for multiple days booking. For example, if you take minimum 2 days of booking, add 2 in the field here.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                            </td>
                        </tr>
                        <?php
                        do_action( 'bkap_after_minimum_days_multiple', $duplicate_of ); 
                        ?>
                        <tr class="multiple_days_setup" <?php echo $multiple_days_setup; ?>>
                            <th style="width:50%;">
                                <label for="booking_maximum_number_days_multiple"><?php _e( 'Maximum number of nights to book', 'woocommerce-booking' ); ?></label>
                            </th>
                            <td>
                                <?php 
                                $maximum_day_multiple = "";
                                if ( isset( $booking_settings[ 'booking_maximum_number_days_multiple' ] ) && $booking_settings[ 'booking_maximum_number_days_multiple' ] != "" ) {
                                    $maximum_day_multiple = $booking_settings[ 'booking_maximum_number_days_multiple' ];
                                } else {
                                    $maximum_day_multiple = "365";
                                }   
                                ?>
                                <input type="number" style="width:90px;" name="booking_maximum_number_days_multiple" id="booking_maximum_number_days_multiple" min="0" max="9999" value="<?php echo $maximum_day_multiple;?>" >
                            </td>
                            <td>
                                <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'The maximum number of booking days you want to book for multiple days booking. For example, if you take maximum 60 days of booking, add 60 in the field here.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url() ;?>/woocommerce/assets/images/help.png" />
                            </td>
                        </tr>
                        <?php
                        do_action( 'bkap_after_maximum_days_multiple', $duplicate_of ); 
                        ?>
                    </table>
                    
                    <hr/>
                    
                    <?php
                    //call function to display the weekdays and availablility setup
                    //self::bkap_get_weekdays_html( $duplicate_of, true, true, $booking_settings );
                    if ( 'checked' == $multiple_days ) {
                        self::bkap_get_weekdays_html( $duplicate_of, false, true, $booking_settings );
                    } else {
                        self::bkap_get_weekdays_html( $duplicate_of, true, true, $booking_settings );
                    }
                    ?>
                    
                    <!-- Descrpition of the per night price for multiple days -->
                    <p class="show-multiple-day-per-night-price-description"></p>

                    <hr style="clear:both;" />
                    <?php
                    // add specific setup
                    self::bkap_get_specific_html( $duplicate_of, $booking_settings ); 
                    ?>
                    
                    <div style="clear: both;"></div>
                    
                    <?php
                    // add date and time setup
                    self::bkap_get_date_time_html( $duplicate_of, $booking_settings ); 
                    ?>
                    <?php 
                    // These hooks have been moved here to ensure no existing functionality for any client is broken
                    // in case if they hv added custom fields
                    do_action( 'bkap_before_enable_multiple_days', $duplicate_of );
                    do_action( 'bkap_after_lockout_time', $duplicate_of );
                    do_action( 'bkap_before_range_selection_radio', $duplicate_of );
                    do_action( 'bkap_before_booking_start_date_range', $duplicate_of );
                    do_action( 'bkap_before_booking_end_date_range', $duplicate_of );
                    do_action( 'bkap_before_recurring_date_range', $duplicate_of );
                    do_action( 'bkap_after_recurring_date_range', $duplicate_of );
                    do_action( 'bkap_after_recurring_years_range', $duplicate_of );
                    ?>
                    <hr style="margin-top:20px"/>
                    <?php 
                    if( isset( $post_type ) && $post_type === 'product' ) {
                        self::bkap_save_button( 'bkap_save_settings' );
                    }
                    ?>                    
                    <div id='availability_update_notification' style='display:none;'></div>
                    
                </div>
                        
                <?php 
                do_action( 'bkap_after_listing_enabled', $duplicate_of );
                ?>
            </div>
        </div>
        <?php 
    }

    /**
     * This function will print a save button in each of the tabs.
     * It needs the callback JS function as the parameter
     * @param str $save_fn - Name of the callback JS function.
     * @since 4.6.0
     */
    static function bkap_save_button( $save_fn ) {
        $save_fn .= '()';
        ?>
        <div style="width:100%;margin-left: 40%;">
            <button type="button" class="button-primary bkap-primary" onclick="<?php echo $save_fn; ?>" ><i class="fa fa-floppy-o fa-lg"></i>&nbsp;&nbsp;&nbsp;<?php _e( 'Save Changes', 'woocommerce-booking' ); ?></button>
        </div>
        <?php                   
    }
    
    /**
     * The function adds the html for the Weekdays UI
     * which allows the admin to enable/disable weekdays,
     * set lockout and price for the same.
     * @param int $product_id
     * @param boolean $lockout
     * @param boolean $price
     * @param array booking settings
     * @since 4.0.0
     */
    static function bkap_get_weekdays_html( $product_id, $lockout = false, $price = true, $booking_settings = array() ) {

        global $bkap_weekdays;
        
        if ( isset( $booking_settings[ 'booking_enable_date' ] ) && 'on' == $booking_settings[ 'booking_enable_date' ] ) { // bookable product
            $display = '';
            $recurring_weekdays = ( isset( $booking_settings[ 'booking_recurring' ] ) ) ? $booking_settings[ 'booking_recurring' ] : array();
            $recurring_lockout = ( isset( $booking_settings[ 'booking_recurring_lockout' ] ) ? $booking_settings[ 'booking_recurring_lockout' ] : array());
            $booking_special_prices = get_post_meta( $product_id, '_bkap_special_price', true );
            $special_prices = array();
            /** Create a list of the special prices as day and price **/
            if ( is_array( $booking_special_prices ) && count( $booking_special_prices ) > 0 ) {
                
                foreach( $booking_special_prices as $special_key => $special_value ) {
                    $weekday_set = $special_value[ 'booking_special_weekday' ];
                    
                    if ( $weekday_set != "" ) {
                        $special_prices[ $weekday_set ] = $special_value[ 'booking_special_price' ];
                    } 
                }
                
            }
        } else { // non-bookable product
            $display = 'display:none;';
            $recurring_weekdays = array();
            $special_prices = array();
            $recurring_lockout = array();
        }
        ?>
        <div id="set_weekdays" class="weekdays_flex_main" style="margin-bottom:20px;width:100%;float:left; <?php echo $display; ?>" >
            <div class="weekdays_flex_child" >
                <div class="weekdays_flex_child_1 bkap_weekdays_heading" style="max-width:27%;"><b><?php _e( 'Weekday', 'woocommerce-booking' );?></b></div>
                <div class="weekdays_flex_child_2 bkap_weekdays_heading" style="max-width:20%;"><b><?php _e( 'Bookable', 'woocommerce-booking' ); ?></b></div>
                
                <?php 
                $mutiple_display = '';
                
                if ( !$lockout ) {
                    $mutiple_display = 'display:none;';
                }

                ?>
                <div class="weekdays_flex_child_3 bkap_weekdays_heading" style="max-width:26%;<?php echo $mutiple_display;?>"><b><?php _e( 'Maximum bookings', 'woocommerce-booking' );?></b></div>
                
                
                <?php if ( $price ) { 
                    $currency_symbol = get_woocommerce_currency_symbol();
                ?>
                <div class="weekdays_flex_child_4 bkap_weekdays_heading" ><b><?php _e( "Price ($currency_symbol)", 'woocommerce-booking' );?> </b></div>
                <?php }?>
            </div>
                    
            <?php 
            $i = 0;
            foreach( $bkap_weekdays as $w_key => $w_value ) {
            ?>
                <div class="weekdays_flex_child">
                    <div class="weekdays_flex_child_1" style="padding-top:5px; max-width:27%; float:left;"><?php echo $w_value; ?></div>
                    
                    <?php 
                    $weekday_status = 'checked';
                    $fields_status = '';
                    if ( isset( $recurring_weekdays[ $w_key ] ) && '' == $recurring_weekdays[ $w_key ] ) {
                        $weekday_status = '';
                        $fields_status = 'disabled';
                    }   ?>
                    <div class="weekdays_flex_child_2" style="padding-top:5px; max-width:20%; float:left;">
                        <label class="bkap_switch">
                            <input id="<?php echo $w_key;?>" type="checkbox" name="<?php echo $w_value; ?>" <?php echo $weekday_status; ?> >
                            <div class="bkap_slider round"></div> 
                        </label>  
                    
                    </div>
                    
                    <?php
                        $weekday_lockout = isset( $recurring_lockout[ $w_key ] ) ? $recurring_lockout[ $w_key ] : '';
                    ?>
                    <div class="weekdays_flex_child_3" style="padding-top:5px; min-width:26%;<?php echo $mutiple_display; ?>"> <input style="float:left;" type="number" id="weekday_lockout_<?php echo $i;?>" name="day_lockout" min="0" max="9999" placeholder="Max bookings" value="<?php echo $weekday_lockout; ?>" <?php echo $fields_status; ?>/></div>
                    
                    
                    <?php 
                    if ( $price ) {
                        $special_price = '';
                        if ( is_array( $special_prices ) && count( $special_prices ) > 0 && array_key_exists( $w_key, $special_prices ) ) {
                            $special_price = $special_prices[ $w_key ];
                        }        
                    ?>
                    <div class="weekdays_flex_child_4" style="padding-top:5px;"> <input style="width:95px;" type="text" id="weekday_price_<?php echo $i;?>" name="day_price" min="0" placeholder="Special Price" value="<?php echo $special_price;?>"/> </div>
                    <?php }?>
                </div>    
                    
                <?php 
                $i++;
            }
            ?>
        </div>
               
        <?php 
        }
        
        /**
         *
         * Adds the specific dates availability
         * checkbox and the table for the same.
         * @param array $booking_settings
         * @since 4.0.0
         */
        static function bkap_get_specific_html( $product_id, $booking_settings ) {
        
            $specific_date_checkbox = '';
            $specific_date_table    = '';
            
            $display = 'display:block;';
            $specific_date_checkbox = '';
            $specific_date_table = 'display:none;';
             
            if( isset( $booking_settings[ 'booking_specific_booking' ]) && $booking_settings[ 'booking_specific_booking' ] == 'on' ){
                $specific_date_table = 'display:block;';
                $specific_date_checkbox = 'checked';
            }
            
            ?>
            <div style="clear: both;" ></div>
            
            <div class="specific_date_title" style="display:flex;width:100%;margin-top:20px;">
                <div>
                  <b><?php _e( 'Set Availability by Dates/Months', 'woocommerce-booking' ); ?></b>
                </div>
                <div style="margin-left: 10px">
                    <label class="bkap_switch">
                    <input title="Select one of booking type for enable this." type="checkbox" name="specific_date_checkbox" id="specific_date_checkbox"  <?php echo $specific_date_checkbox;?>>
                    <div class="bkap_slider round"></div>
                    </label>
                </div>
            </div>
            
            <div style="clear: both;" ></div>
            <!-- Below is the div to display table for adding specific date range and other ranges -->
            
            <div class="specific_date" style="<?php echo $specific_date_table; ?>">
                <table class="specific">
                    <?php self::bkap_get_specific_heading_html( $product_id ); ?>
                    <?php self::bkap_get_specific_default_row_html( $product_id, $booking_settings ); ?>
                    <?php self::bkap_get_specific_row_to_display_html( $product_id, $booking_settings ); ?>
                    <tr style="padding:5px; border-top:2px solid #eee">
                       <td colspan="4" style="border-right: 0px;"><i><small><?php _e( 'Create custom ranges, holidays and more here.', 'woocommerce-booking' ); ?></small><i></td>
                       <td colspan="2" align="right" style="border-left: none;"><button type="button" class="button-primary bkap_add_new_range"><?php _e( 'Add New Range' , 'woocommerce-booking' );?></button></td>
                    </tr>
                </table>
           </div>
           <?php 
        }
                
        /**
         * The function adds the html for the Specific UI
         * which allows the admin to enable/disable weekdays,
         * set lockout and price for the same.
         * 
         */
        
        static function bkap_get_specific_heading_html($product_id){
            ?>
            
            <tr>
            <th style="width:20%"><?php _e( 'Range Type'                , 'woocommerce-booking' );?></th>
            <th style="width:20%"><?php _e( 'From'                      , 'woocommerce-booking' );?></th>
            <th style="width:20%"><?php _e( 'To'                        , 'woocommerce-booking' );?></th>
            <th style="width:10%"><?php _e( 'Bookable'                  , 'woocommerce-booking' );?></th>
            <th style="border-right:0px;width:25%"><?php _e( 'Max bookings' , 'woocommerce-booking' );?></th>
            <th style="border-left:0px;"></th>
            </tr>
            
            <?php 
        }
        
        /**
         * The function adds the html for the Specific UI
         * which allows the admin to enable/disable weekdays,
         * set lockout and price for the same.
         * 
         */
        
        static function bkap_get_specific_default_row_html( $product_id, $booking_settings ){
            global $bkap_months;
            global $bkap_dates_months_availability;
            
            ?>
            
            <!-- We are fetching below tr when add new range is clicked -->
            <tr class="added_specific_date_range_row" style="display: none;">
               <td>
                    <select style="width:100%;" id="range_dropdown" >
                        <?php 
                        foreach( $bkap_dates_months_availability as $d_value => $d_name ) {
                            printf( "<option value='%s'>%s</option>\n", $d_value, $d_name );
                        }?>
                    </select>
               </td>
               
               
                   <!-- From Custom-->
                   <td class="date_selection_textbox1" style="width:20%;">
                        <div class="fake-input">
                            <input type="text" id="datepicker_textbox1" class="datepicker_start_date date_selection_textbox" style="width:100%;" />
                            <img src="<?php echo plugins_url();?>/woocommerce-booking/images/cal.gif" id="custom_checkin_cal" width="15" height="15" />
                        </div>
                   </td>
                   <!-- To Custom-->
                   <td class="date_selection_textbox2">
                        <div class="fake-input" >
                            <input type="text" id="datepicker_textbox2" class="datepicker_end_date date_selection_textbox" style="width:100%;" />
                            <img src="<?php echo plugins_url();?>/woocommerce-booking/images/cal.gif" id="custom_checkout_cal" width="15" height="15" />
                        </div>
                   </td>
                   
                   <!-- Specific Date Textarea -->
                   <td class="date_selection_textbox3" colspan="2" style="display:none;" >
                         <div class="fake-textarea" >
                             <textarea id="textareamultidate_cal1" class="textareamultidate_cal" rows="1" col="30" style="width:100%;height:auto;"></textarea>
                             <img src="<?php echo plugins_url();?>/woocommerce-booking/images/cal.gif" id="specific_date_multidate_cal" class="bkap_multiple_datepicker_cal_image" width="15" height="15" />
                         </div>
                   </td>
                   
                   <!-- From Month-->
                   <td class="date_selection_textbox4" style="display:none;">
                        <select id="bkap_availability_from_month" style="width:100%;">
                            <?php
                            foreach( $bkap_months as $m_number => $m_name ) {
                                printf( "<option value='%d'>%s</option>\n", $m_number, $m_name );
                            } 
                            ?>
                        </select>
                   
                   
                   <!-- 
                        <div class="fake-input">
                            <input type="text" id="datepicker_textbox3" class="datepicker_start_date date_selection_textbox" style="width:100%;" />
                            <img src="http://localhost/latest/wp-content/plugins/woocommerce-booking/images/cal.gif" id="month_checkin_cal" width="15" height="15" />
                        </div>
                        
                        -->
                   </td>
                   <!-- To Month-->
                   <td class="date_selection_textbox5" style="display:none;">
                        <select id="bkap_availability_to_month" style="width:100%;">
                            <?php
                            foreach( $bkap_months as $m_number => $m_name ) {
                                printf( "<option value='%d'>%s</option>\n", $m_number, $m_name );
                            } 
                            ?>
                        </select>
                        
                        <!--
                        <div class="fake-input" >
                            <input type="text" id="datepicker_textbox4" class="datepicker_end_date date_selection_textbox" style="width:100%;" />
                            <img src="http://localhost/latest/wp-content/plugins/woocommerce-booking/images/cal.gif" id="month_checkout_cal" width="15" height="15" />
                        </div>
                        -->
                   </td>
                   
                   <!-- Holiday Textarea -->
                   <td class="date_selection_textbox6" colspan="2" style="display:none;" >
                         <div class="fake-textarea" >
                             <textarea id="textareamultidate_cal2" class="textareamultidate_cal" rows="1" col="30" style="width:100%;height:auto;"></textarea>
                             <img src="<?php echo plugins_url();?>/woocommerce-booking/images/cal.gif" id="holiday_multidate_cal" class="bkap_multiple_datepicker_cal_image" width="15" height="15" />
                         </div>
                   </td>
                   
                    
               
                   <td style="padding-left:2%;">
                        <div class="bkap_popup">
                        <span class="bkap_popuptext" id="bkap_myPopup"></span>
                        <label class="bkap_switch">
                        
                          <input id="bkap_bookable_nonbookable" type="checkbox" name="bkap_bookable_nonbookable">
                          <div class="bkap_slider round"></div>
                        </label>
                        
                        <div>
                   </td>
                   
                   <td class="bkap_lockout_column_data_1" >
                    <input id="bkap_number_of_year_to_recur_custom_range" title="Please enter number of years you want to recur this custom range" type="number" min="0" style="width:65%;font-size:11px;margin-left: 15%;" placeholder="No. of Years">
                    &nbsp;
                    <i id="bkap_recurring" class="fa fa-refresh" aria-hidden="true" title="Recurring yearly"></i>
                   </td>
                   <td class="bkap_lockout_column_data_2"  style="display:none;">
                        <input id="bkap_number_of_year_to_recur_holiday" title="Please enter number of years you want to recur selected holidays" type="number" min="0" style="width:65%;font-size:11px;margin-left: 15%;" placeholder="No. of Years">
                        &nbsp;
                        <i id="bkap_recurring" class="fa fa-refresh" aria-hidden="true" title="Recurring yearly"></i>
                   </td>
                   <td class="bkap_lockout_column_data_3"  style="display:none;">
                        <input id="bkap_number_of_year_to_recur_month" title="Please enter number of years you want to recur selected month" type="number" min="0" style="width:65%;font-size:11px;margin-left: 15%;" placeholder="No. of Years">
                        &nbsp;
                    <i id="bkap_recurring" class="fa fa-refresh" aria-hidden="true" title="Recurring yearly"></i>
                   </td>    
                   <td class="bkap_lockout_column_data_4" style="display:none;">
                        <input id="bkap_specific_date_lockout" title="This is lockout for selected specific dates." type="number" min="0" style="width:47%;font-size:11px;" placeholder="Max bookings">
                        <input id="bkap_specific_date_price" title="This is price for selected specific dates." type="number" min="0" style="width:45%;float:right;font-size:11px;" placeholder="Price">
                   </td>
                   
                   <td id="bkap_close" style="text-align: center;cursor:pointer;"><i class="fa fa-trash" aria-hidden="true"></i></td>
           </tr>
           <!-- We are fetching above tr when add new range is clicked -->
            
            <?php 
        }
        
        /**
         * Display by default one row in the specific dates table.
         *
         */
        
        static function bkap_get_specific_row_to_display_html( $product_id, $booking_settings ){
            global $bkap_months;
            global $bkap_dates_months_availability;
            
            $booking_type = $booking_custom_ranges = $booking_holiday_ranges = $booking_month_ranges = $booking_specific_dates = $booking_special_prices = $booking_product_holiday = array(); 
            
            // Fetching data from post meta.
            $booking_type           = get_post_meta( $product_id, '_bkap_booking_type', true );
            $booking_custom_ranges  = get_post_meta( $product_id, '_bkap_custom_ranges', true );
            $booking_holiday_ranges = get_post_meta( $product_id, '_bkap_holiday_ranges', true );
            $booking_month_ranges   = get_post_meta( $product_id, '_bkap_month_ranges', true );
            $booking_specific_dates = get_post_meta( $product_id, '_bkap_specific_dates', true );
            $booking_special_prices = get_post_meta( $product_id, '_bkap_special_price', true );
            $booking_product_holiday = isset( $booking_settings['booking_product_holiday'] ) ? $booking_settings['booking_product_holiday'] : "" ;

            // sorting holidays in chronological order.
            if ( is_array( $booking_product_holiday ) && count( $booking_product_holiday ) > 0 ) {
                uksort( $booking_product_holiday, 'bkap_orderby_date_key' );
            }
            
            // Calculating counts for ranges.
            $count_custom_ranges    = $booking_custom_ranges  != "" ? count( $booking_custom_ranges ) : 0;
            $count_holiday_ranges   = $booking_holiday_ranges != "" ? count( $booking_holiday_ranges ) : 0;
            $count_month_ranges     = $booking_month_ranges   != "" ? count( $booking_month_ranges ) : 0;
            $count_specific_dates   = $booking_specific_dates != "" ? count( $booking_specific_dates ) : 0;
            
            $count_special_prices   = $booking_special_prices != "" ? count( $booking_special_prices ) : 0;
            $count_product_holiday   = $booking_product_holiday != "" ? count( $booking_product_holiday ) : 0;
            
            $array_of_all_added_ranges  = array();
            $bkap_range_count           = 0;
            
            $special_prices = array();
            // Modify the special prices array
            if ( isset( $booking_special_prices ) && $count_special_prices > 0 ) {
                foreach( $booking_special_prices as $s_key => $s_value ) {
                    if ( isset( $s_value[ 'booking_special_date' ] ) && $s_value[ 'booking_special_date' ] != '' ) {
                        $s_date = date( 'j-n-Y', strtotime( $s_value[ 'booking_special_date' ] ) );
                        $special_prices[ $s_date ] = $s_value[ 'booking_special_price' ];
                    }
                }
            }
             
            if( isset( $booking_custom_ranges ) && $count_custom_ranges > 0 ){
                for( $bkap_range = 0; $bkap_range < $count_custom_ranges; $bkap_range++ ){
                    $array_of_all_added_ranges[$bkap_range]['bkap_type']            = "custom_range";
                    $array_of_all_added_ranges[$bkap_range]['bkap_start']           = $booking_custom_ranges[$bkap_range]['start'];
                    $array_of_all_added_ranges[$bkap_range]['bkap_end']             = $booking_custom_ranges[$bkap_range]['end'];
                    $array_of_all_added_ranges[$bkap_range]['bkap_years_to_recur']  = $booking_custom_ranges[$bkap_range]['years_to_recur'];
                    $bkap_range_count++;
                }
            }
            
            if( isset( $booking_product_holiday ) && $count_product_holiday > 0 ){
                foreach( $booking_product_holiday as  $booking_product_holiday_keys => $booking_product_holiday_values ){
                    $array_of_all_added_ranges[$bkap_range_count]['bkap_type']            = "holidays";
                    $array_of_all_added_ranges[$bkap_range_count]['bkap_holiday_date']    = $booking_product_holiday_keys;
                    $array_of_all_added_ranges[$bkap_range_count]['bkap_years_to_recur']  = $booking_product_holiday_values;
                    $bkap_range_count++;
                }
            }
            
            if( isset( $booking_month_ranges ) && $count_month_ranges > 0 ){
                for( $bkap_range = 0; $bkap_range < $count_month_ranges; $bkap_range++ ){
                    $array_of_all_added_ranges[$bkap_range_count]['bkap_type']            = "range_of_months";
                    $array_of_all_added_ranges[$bkap_range_count]['bkap_start']           = $booking_month_ranges[$bkap_range]['start'];
                    $array_of_all_added_ranges[$bkap_range_count]['bkap_end']             = $booking_month_ranges[$bkap_range]['end'];
                    $array_of_all_added_ranges[$bkap_range_count]['bkap_years_to_recur']  = $booking_month_ranges[$bkap_range]['years_to_recur'];
                    $bkap_range_count++;
                }
            }
            
            if( isset( $booking_specific_dates ) && $count_specific_dates > 0 ){
                foreach( $booking_specific_dates as  $booking_specific_dates_keys => $booking_specific_dates_values ){
                    $array_of_all_added_ranges[$bkap_range_count]['bkap_type']            = "specific_dates";
                    $array_of_all_added_ranges[$bkap_range_count]['bkap_specific_date']   = $booking_specific_dates_keys;
                    $array_of_all_added_ranges[$bkap_range_count]['bkap_specific_lockout']= $booking_specific_dates_values;
                    // check if that date has a special price set
                    $array_of_all_added_ranges[ $bkap_range_count ][ 'bkap_specific_price' ] = ( isset( $special_prices[ $booking_specific_dates_keys ] ) ) ? $special_prices[ $booking_specific_dates_keys ] : '';
                    $bkap_range_count++;
                }
            }

            // if the booking type is multiple day, then no data is present in specific dates, so loop through the special prices
            if ( 'multiple_days' == $booking_type ) {
                if ( is_array( $special_prices ) && count( $special_prices ) > 0 ) {
                    foreach( $special_prices as $sp_date => $sp_price ) {
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_type']            = "specific_dates";
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_specific_date']   = $sp_date;
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_specific_lockout']= '';
                        $array_of_all_added_ranges[ $bkap_range_count ][ 'bkap_specific_price' ] = $sp_price;
                        $bkap_range_count++;
                    }
                }
            }
            
            if( isset( $booking_holiday_ranges ) && $count_holiday_ranges > 0 ){
                for( $bkap_range = 0; $bkap_range < $count_holiday_ranges; $bkap_range++ ){
            
                    $bkap_holiday_from_month        = date('F',strtotime( $booking_holiday_ranges[$bkap_range]['start'] ) );
                    $bkap_holiday_to_month          = date('F',strtotime( $booking_holiday_ranges[$bkap_range]['end'] ) );
                    $holiday_start_date_of_month    = date('1-n-Y',strtotime( $booking_holiday_ranges[$bkap_range]['start'] ) );
                    $holiday_end_date_of_month      = date('t-n-Y',strtotime( $booking_holiday_ranges[$bkap_range]['end'] ) );
                    
                    // Check if the start date is the start of the month and end date is the end date of the month then range type should be month range.
                    if( $booking_holiday_ranges[ $bkap_range ]['start'] == $holiday_start_date_of_month && $holiday_end_date_of_month == $booking_holiday_ranges[$bkap_range]['end'] ){
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_type']           = ( isset( $booking_holiday_ranges[$bkap_range]['range_type'] ) ) ? $booking_holiday_ranges[$bkap_range]['range_type'] : "range_of_months";
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_start']          = $booking_holiday_ranges[$bkap_range]['start'];
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_end']            = $booking_holiday_ranges[$bkap_range]['end'];
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_years_to_recur'] = $booking_holiday_ranges[$bkap_range]['years_to_recur'];
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_bookable'] = "off";
                    }else{
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_type']           = ( isset( $booking_holiday_ranges[$bkap_range]['range_type'] ) ) ? $booking_holiday_ranges[$bkap_range]['range_type'] : "custom_range";
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_start']          = $booking_holiday_ranges[$bkap_range]['start'];
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_end']            = $booking_holiday_ranges[$bkap_range]['end'];
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_years_to_recur'] = $booking_holiday_ranges[$bkap_range]['years_to_recur'];
                        $array_of_all_added_ranges[$bkap_range_count]['bkap_bookable'] = "off";
                    }
                    $bkap_range_count++;
                }
            }
            
            $i = 0;
              
            while( $i < count( $array_of_all_added_ranges ) ) {
                  
                  $range_type               = $array_of_all_added_ranges[$i]['bkap_type'];
                  $custom_range_disaply     = $holidays_disaply = $range_of_months_disaply = $specific_dates_disaply = "";
                  
                  $bkap_start               = ( isset( $array_of_all_added_ranges[$i]['bkap_start'] )            && !is_null( $array_of_all_added_ranges[$i]['bkap_start'] ) )            ? $array_of_all_added_ranges[$i]['bkap_start']            : "";
                  $bkap_end                 = ( isset( $array_of_all_added_ranges[$i]['bkap_end'] )              && !is_null( $array_of_all_added_ranges[$i]['bkap_end'] ) )              ? $array_of_all_added_ranges[$i]['bkap_end']              : "";
                  $bkap_years_to_recur      = ( isset( $array_of_all_added_ranges[$i]['bkap_years_to_recur'] )   && !is_null( $array_of_all_added_ranges[$i]['bkap_years_to_recur'] ) )   ? $array_of_all_added_ranges[$i]['bkap_years_to_recur']   : "";
                  $bkap_bookable            = 'checked="checked"';
                  $custom_bkap_start        = $custom_bkap_end = $month_bkap_start = $month_bkap_end = $bkap_holiday_date = $custom_bkap_years_to_recur = $holiday_bkap_years_to_recur = $month_bkap_years_to_recur = $bkap_specific_price = $bkap_specific_lockout = $bkap_specific_date = "";
                  
                  switch ( $range_type ) {
                      case "custom_range":
                          
                          $holidays_disaply             = $range_of_months_disaply = $specific_dates_disaply = "display:none;";
                          $custom_bkap_start            = $bkap_start;
                          $custom_bkap_end              = $bkap_end;
                          $custom_bkap_years_to_recur   = $bkap_years_to_recur;
                          if( isset( $array_of_all_added_ranges[$i]['bkap_bookable'] ) && $array_of_all_added_ranges[$i]['bkap_bookable'] == "off" ){
                              $bkap_bookable = "";
                          }
                          
                          break;
                          
                      case "holidays":
                          
                          $custom_range_disaply = $range_of_months_disaply = $specific_dates_disaply = "display:none;";
                          $bkap_holiday_date            = ( isset( $array_of_all_added_ranges[$i]['bkap_holiday_date'] )              && !is_null( $array_of_all_added_ranges[$i]['bkap_holiday_date'] ) )              ? $array_of_all_added_ranges[$i]['bkap_holiday_date']              : "";
                          $holiday_bkap_years_to_recur  = $bkap_years_to_recur;
                          $bkap_bookable                = "";
                          break;
                          
                      case "range_of_months":
                          
                          $custom_range_disaply         = $holidays_disaply = $specific_dates_disaply = "display:none;";
                          $month_bkap_start             = date( "F", strtotime( $bkap_start ) );
                          $month_bkap_end               = date( "F", strtotime( $bkap_end ) );
                          $month_bkap_years_to_recur    = $bkap_years_to_recur;                          
                          if( isset( $array_of_all_added_ranges[$i]['bkap_bookable'] ) && $array_of_all_added_ranges[$i]['bkap_bookable'] == "off" ){
                              $bkap_bookable = "";
                          }   
                          break;
                          
                      case "specific_dates":
                          
                          $custom_range_disaply     = $holidays_disaply = $range_of_months_disaply = "display:none;";
                          $bkap_specific_date       = ( isset( $array_of_all_added_ranges[$i]['bkap_specific_date'] )    && !is_null( $array_of_all_added_ranges[$i]['bkap_specific_date'] ) )    ? $array_of_all_added_ranges[$i]['bkap_specific_date']    : "";
                          $bkap_specific_lockout    = ( isset( $array_of_all_added_ranges[$i]['bkap_specific_lockout'] ) && !is_null( $array_of_all_added_ranges[$i]['bkap_specific_lockout'] ) ) ? $array_of_all_added_ranges[$i]['bkap_specific_lockout'] : "";
                          $bkap_specific_price      = ( isset ( $array_of_all_added_ranges[ $i ][ 'bkap_specific_price' ] ) ) ? $array_of_all_added_ranges[ $i ][ 'bkap_specific_price' ] : '';
                          break;
                          
                      default:
                          break;
                  }
                  
          $bkap_row_toggle = '';
                  $bkap_row_toggle_display = '';
                  if( $i > 4 ){
                      $bkap_row_toggle = "bkap_row_toggle";
                      $bkap_row_toggle_display = 'style="display:none;"'; 
                  }
                  ?>
                  
                  <tr class="added_specific_date_range_row_<?php echo $i;?> <?php echo $bkap_row_toggle;?>" <?php echo $bkap_row_toggle_display;?>>
                  
                      <td style="width:20%;">
                        <select style="width:100%;" id="range_dropdown_<?php echo $i;?>">
                        <?php 
                        foreach( $bkap_dates_months_availability as $d_value => $d_name ) {
                            $bkap_range_selected = '';
                            if( $d_value == $range_type ){
                                $bkap_range_selected = "selected";
                            }
                            printf( "<option value='%s' %s>%s</option>\n", $d_value,$bkap_range_selected, $d_name );
                        }
                        ?>
                        </select>
                      </td>
                      
                      <td class="date_selection_textbox1" style="width:20%;<?php echo $custom_range_disaply;?>">
                           <div class="fake-input">
                               <input type="text" id="datepicker_textbox_<?php echo $i;?>" class="datepicker_start_date date_selection_textbox" style="width:100%;" value="<?php echo $custom_bkap_start;?>"/>
                               <img src="<?php echo plugins_url();?>/woocommerce-booking/images/cal.gif" id="custom_checkin_cal_<?php echo $i;?>" width="15" height="15" />
                           </div>
                      </td>
                           
                      <td class="date_selection_textbox2" style="width:20%;<?php echo $custom_range_disaply;?>">
                           <div class="fake-input" >
                               <input type="text" id="datepicker_textbox__<?php echo $i;?>" class="datepicker_end_date date_selection_textbox" style="width:100%;" value="<?php echo $custom_bkap_end;?>" />
                               <img src="<?php echo plugins_url();?>/woocommerce-booking/images/cal.gif" id="custom_checkout_cal_<?php echo $i;?>" width="15" height="15" />
                           </div>
                      </td>
                      
                      <td class="date_selection_textbox3" colspan="2" style="<?php echo $specific_dates_disaply;?>width:40%;" >
                           <div class="fake-textarea" >
                               <textarea id="specific_dates_multidatepicker_<?php echo $i;?>" class="textareamultidate_cal" rows="1" col="30" style="width:100%;height:auto;"><?php echo $bkap_specific_date;?></textarea>
                               <img src="<?php echo plugins_url();?>/woocommerce-booking/images/cal.gif" id="specific_date_multidate_cal_<?php echo $i;?>" class="bkap_multiple_datepicker_cal_image" width="15" height="15" />
                           </div>
                      </td>
                  
                      <!-- From Month-->
                      <td class="date_selection_textbox4" style="<?php echo $range_of_months_disaply;?>width:20%;">
                           <select id="bkap_availability_from_month_<?php echo $i;?>" style="width:100%;">
                            <?php
                            foreach( $bkap_months as $m_number => $m_name ) {
                                if( $m_name == $month_bkap_start){
                                    $month_bkap_start_selected = "selected";
                                    printf( "<option value='%d' %s>%s</option>\n", $m_number, $month_bkap_start_selected, $m_name );
                                }else{
                                    printf( "<option value='%d'>%s</option>\n", $m_number, $m_name );
                                }
                            } 
                            ?>
                            </select>
                      </td>
                       <!-- To Month-->
                       <td class="date_selection_textbox5" style="<?php echo $range_of_months_disaply;?>width:20%;">
                            <select id="bkap_availability_to_month_<?php echo $i;?>" style="width:100%;">
                                <?php
                                foreach( $bkap_months as $m_number => $m_name ) {
                                    
                                    if( $m_name == $month_bkap_end ){
                                        $month_bkap_end_selected = "selected";
                                        printf( "<option value='%d' %s>%s</option>\n", $m_number, $month_bkap_end_selected, $m_name );
                                    }else{
                                        printf( "<option value='%d'>%s</option>\n", $m_number, $m_name );
                                    }
                                } 
                                ?>
                            </select>
                       </td>
                       
                       <!-- Holiday Textarea -->
                       <td class="date_selection_textbox6" colspan="2" style="<?php echo $holidays_disaply;?>width:40%" >
                            <div class="fake-textarea" >
                                <textarea id="holidays_multidatepicker_<?php echo $i;?>" class="textareamultidate_cal" rows="1" col="30" style="width:100%;height:auto;" style="overflow:hidden" onkeyup="auto_grow(this)"><?php echo $bkap_holiday_date;?></textarea>
                                <img src="<?php echo plugins_url();?>/woocommerce-booking/images/cal.gif" id="holiday_multidate_cal_<?php echo $i;?>" class="bkap_multiple_datepicker_cal_image" width="15" height="15" />
                            </div>
                       </td>
                       
                       <td style="padding-left:2%;width:10%;">
                            <div class="bkap_popup">
                            <span class="bkap_popuptext" id="bkap_myPopup_<?php echo $i;?>"></span>
                            <label class="bkap_switch">
                                 <input id="bkap_bookable_nonbookable_<?php echo $i;?>" type="checkbox" name="bkap_bookable_nonbookable" <?php echo $bkap_bookable;?>>
                                 <div class="bkap_slider round"></div>
                            </label>
                            </div>
                            
                       </td>
                       
                       <td class="bkap_lockout_column_data_1" style="<?php echo $custom_range_disaply;?>">
                            <input id="bkap_number_of_year_to_recur_custom_range_<?php echo $i;?>" value="<?php echo $custom_bkap_years_to_recur;?>" title="Please enter number of years you want to recur this custom range" type="number" min="0" style="width:65%;font-size:11px;margin-left: 15%;" placeholder="No. of Years">

                            &nbsp;
                            <i id="bkap_recurring" class="fa fa-refresh" aria-hidden="true" title="Recurring yearly"></i>
                       </td>
                       
                       <td class="bkap_lockout_column_data_2"  style="<?php echo $holidays_disaply;?>">
                            <input id="bkap_number_of_year_to_recur_holiday_<?php echo $i;?>" value="<?php echo $holiday_bkap_years_to_recur;?>"  title="Please enter number of years you want to recur selected holidays" type="number" min="0" style="width:65%;font-size:11px;margin-left: 15%;" placeholder="No. of Years">
                            &nbsp;
                            <i id="bkap_recurring" class="fa fa-refresh" aria-hidden="true" title="Recurring yearly"></i>
                       </td>
                       
                       <td class="bkap_lockout_column_data_3"  style="<?php echo $range_of_months_disaply;?>">
                            <input id="bkap_number_of_year_to_recur_month_<?php echo $i;?>" value="<?php echo $month_bkap_years_to_recur;?>" title="Please enter number of years you want to recur selected month" type="number" min="0" style="width:65%;font-size:11px;margin-left:15%;" placeholder="No. of Years">
                            &nbsp;
                            <i id="bkap_recurring" class="fa fa-refresh" aria-hidden="true" title="Recurring yearly"></i>
                       </td>    
                       
                       <td class="bkap_lockout_column_data_4" style="<?php echo $specific_dates_disaply;?>">
                            <input id="bkap_specific_date_lockout_<?php echo $i;?>" value="<?php echo $bkap_specific_lockout;?>" title="This is number of maximum bookings for selected specific dates." type="number" min="0"style="width:47%;font-size:11px;" placeholder="Max bookings">
                            <input id="bkap_specific_date_price_<?php echo $i;?>" value="<?php echo $bkap_specific_price; ?>" title="This is price for selected specific dates." type="number" min="0" style="width:45%;float:right;font-size:11px;" placeholder="Price">
                       </td>
                       
                       <td style="width:4%;" id="bkap_close_<?php echo $i;?>" style="text-align: center;cursor:pointer;"><i class="fa fa-trash" aria-hidden="true"></i></td>
                       
                  </tr>
                  <?php 
                  $i++;
              }
              
              if ( count( $array_of_all_added_ranges ) > 5 ){
              ?>
                <tr style="">
                   <td colspan="6">
                   <span class="bkap_expand-close">
                       <a href="#" class="bkap_expand_all"><?php echo __( 'Expand', 'woocommerce-booking' ); ?></a> / <a href="#" class="bkap_close_all"><?php echo __( 'Close', 'woocommerce-booking' ); ?></a>
                   </span>
                   </td>
                </tr>
              <?php
              }
        }

        public static function bkap_load_time_slots () {
            ob_start();

            if ( empty( $_POST['bkap_product_id'] ) ) {
                wp_die( -1 );
            }

            $bkap_loop        = 0;
            $bkap_product_id  = absint( $_POST['bkap_product_id'] );
            
            $bkap_per_page    = ! empty( $_POST['bkap_per_page'] ) ? absint( $_POST['bkap_per_page'] ) : 15;
            $bkap_page        = ! empty( $_POST['bkap_page'] ) ? absint( $_POST['bkap_page'] ) : 1;
            
            $booking_settings =   get_post_meta( $bkap_product_id, 'woocommerce_booking_settings', true );

            /**
             * Set the pagination limits for the  records.
             */
            $bkap_end_record_on     = $bkap_page * $bkap_per_page ;
            $bkap_start_record_from = ( $bkap_page > 1 ) ? ( ( $bkap_page - 1 ) * $bkap_per_page ) + 1  : 1 ;

            if( isset( $booking_settings[ 'booking_time_settings' ] ) && is_array( $booking_settings['booking_time_settings'] ) ) {

                include( 'templates/meta-boxes/html-bkap-time-slots-meta-box.php' );
            }

            wp_die();
        }

        /**
         * It will set the pagination at the end of the time slots div
         */
        public static function bkap_get_pagination_for_time_slots ( $bkap_per_page_time_slots, $bkap_total_time_slots_number, $bkap_total_pages, $bkap_encode_booking_times ) {
            ?>
            <div class="bkap_toolbar"  data-bkap-total="<?php echo $bkap_total_time_slots_number; ?>" data-total_pages="<?php echo $bkap_total_pages; ?>" data-page="1" data-edited="false" data-time-slots = "<?php echo $bkap_encode_booking_times ; ?>" >
                <div class="bkap_time_slots_pagenav">
                    <span class="bkap_displaying_num">
                        <span class="bkap_display_count_num">
                            <?php _e( $bkap_total_time_slots_number, 'woocommerce-booking' ); ?>
                        </span>
                        <?php print( _n( 'time slot', 'time slots', $bkap_total_time_slots_number, 'woocommerce-booking' ) ); ?>
                        
                    </span>
                    <span class="bkap_pagination_links">
                        <a class="bkap_first_page disabled" title="<?php esc_attr_e( 'Go to the first page', 'woocommerce-booking' ); ?>" href="#">&laquo;</a>
                        <a class="bkap_prev_page disabled" title="<?php esc_attr_e( 'Go to the previous page', 'woocommerce-booking' ); ?>" href="#">&lsaquo;</a>
                        <span class="bkap_paging_select">
                            <label for="bkap_current_page_selector_1" class="bkap_screen_reader_text"><?php _e( 'Select Page', 'woocommerce-booking' ); ?></label>
                            <select class="bkap_page_selector" id="bkap_current_page_selector_1" title="<?php esc_attr_e( 'Current page', 'woocommerce-booking' ); ?>">
                                <?php for ( $i = 1; $i <= $bkap_total_pages; $i++ ) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                             <?php _ex( 'of', 'number of pages', 'woocommerce-booking' ); ?> <span class="bkap_total_pages"><?php echo $bkap_total_pages; ?></span>
                        </span>
                        <a class="bkap_next_page" title="<?php esc_attr_e( 'Go to the next page', 'woocommerce-booking' ); ?>" href="#">&rsaquo;</a>
                        <a class="bkap_last_page" title="<?php esc_attr_e( 'Go to the last page', 'woocommerce-booking' ); ?>" href="#">&raquo;</a>
                    </span>
                </div>
                <div class="clear"></div>
            </div>
            <?php
        }

        /**
         * Adds the days/dates and timeslot table in availability tab
         * @param array $booking_settings
         * @since 4.0.0
         */
        
        static function bkap_get_date_time_html( $product_id, $booking_settings = array() ) {
            
           $date_time_table = isset( $booking_settings ['booking_enable_time'] ) && $booking_settings ['booking_enable_time'] == 'on' ? '' : 'display:none;';
            
            $booking_times = array();
            $bkap_encode_booking_times = array();
            $bkap_display_time_slots_pagination = 'display:none;';
            $bkap_total_time_slots_number       = 1;
            $bkap_total_pages                   = 0;
            $bkap_per_page_time_slots           = absint( apply_filters( 'bkap_time_slots_per_page', 15 ) );
            if( isset( $booking_settings[ 'booking_time_settings' ] ) && is_array( $booking_settings['booking_time_settings'] ) ) {
                
                foreach( $booking_settings['booking_time_settings'] as $bkap_weekday_key => $bkap_weekday_value ){
                    foreach ( $bkap_weekday_value as $day_key => $time_data  ) {
                        
                        $bkap_from_hr      = ( isset( $time_data['from_slot_hrs'] ) && !is_null( $time_data['from_slot_hrs'] ) ) ? $time_data['from_slot_hrs'] : "";
                        $bkap_from_min     = ( isset( $time_data['from_slot_min'] ) && !is_null( $time_data['from_slot_min'] ) ) ? $time_data['from_slot_min'] : "";
                        
                        $bkap_from_time    = $bkap_from_hr.":".$bkap_from_min;
                         
                        $bkap_to_hr        = ( isset( $time_data['to_slot_hrs'] ) && !is_null( $time_data['to_slot_hrs'] ) ) ? $time_data['to_slot_hrs'] : "";
                        $bkap_to_min       = ( isset( $time_data['to_slot_min'] ) && !is_null( $time_data['to_slot_min'] ) ) ? $time_data['to_slot_min'] : "";
                        
                        $bkap_to_time      = ( $bkap_to_hr === '0' && $bkap_to_min === '00' ) ? '' : "$bkap_to_hr:$bkap_to_min";
                         
                        $bkap_lockout      = ( isset( $time_data['lockout_slot'] ) && !is_null( $time_data['lockout_slot'] ) ) ? $time_data['lockout_slot'] : "";
                        $bkap_price        = ( isset( $time_data['slot_price'] ) && !is_null( $time_data['slot_price'] ) ) ? $time_data['slot_price'] : "";
                         
                        $bkap_global       = ( isset( $time_data['global_time_check'] ) && !is_null( $time_data['global_time_check'] ) ) ? $time_data['global_time_check'] : "";
                        $bkap_note         = ( isset( $time_data['booking_notes'] ) && !is_null( $time_data['booking_notes'] ) ) ? $time_data['booking_notes'] : "";

                        $booking_times[ $bkap_total_time_slots_number ] = array();
                        $booking_times[ $bkap_total_time_slots_number ] [ 'day' ]               =  $bkap_weekday_key;
                        $booking_times[ $bkap_total_time_slots_number ] [ 'from_time' ]         = $bkap_from_time;
                        $booking_times[ $bkap_total_time_slots_number ] [ 'to_time' ]           = $bkap_to_time;
                        $booking_times[ $bkap_total_time_slots_number ] [ 'lockout_slot' ]      = $bkap_lockout;
                        $booking_times[ $bkap_total_time_slots_number ] [ 'slot_price' ]        = $bkap_price;
                        $booking_times[ $bkap_total_time_slots_number ] [ 'global_time_check' ] = $bkap_global;
                        $booking_times[ $bkap_total_time_slots_number ] [ 'booking_notes' ]     = $bkap_note;

                        $bkap_total_time_slots_number ++;
                    }
                }
                
                if ( $bkap_total_time_slots_number > 1 ) {
                    $bkap_display_time_slots_pagination = '';
                    $bkap_total_time_slots_number--;
                }
                $bkap_total_pages = ceil( $bkap_total_time_slots_number / $bkap_per_page_time_slots );
                
                $bkap_encode_booking_times = htmlspecialchars( json_encode ( $booking_times, JSON_FORCE_OBJECT ) );
            } else {
                /**
                 * When we add a new product we need to pass this array as a string so we are creating a json object string.
                 */

                $bkap_encode_booking_times = htmlspecialchars( json_encode ( $booking_times, JSON_FORCE_OBJECT ) );
            }
           
           ?>
           <!-- Table for adding Date/Day and time table -->
           <div class="bkap_date_timeslot_div" style="<?php echo $date_time_table;?>">
           <?php do_action( 'bkap_before_time_enabled', $product_id );?>
            <div>
                <h4><?php _e( 'Set Weekdays/Dates And It\'s Timeslots :', 'woocommerce-booking' ); ?></h4>
            </div>
            <?php do_action( 'bkap_after_time_enabled', $product_id );?>
            <table id="bkap_date_timeslot_table">
                <?php
                 // add date and time setup.
                self::bkap_get_daydate_and_time_heading( $product_id, $booking_settings, $bkap_display_time_slots_pagination, $bkap_per_page_time_slots, $bkap_total_time_slots_number, $bkap_total_pages, $bkap_encode_booking_times ); 

                ?>
                <?php
                self::bkap_get_daydate_and_time_table_base_data( $product_id, $booking_settings );

                if ( $bkap_total_time_slots_number > 0 ) {

                    /**
                     * This tr is a identifier, when we recive the response from ajax we will remove this tr and replace 
                     * our genrated data.
                     */
                    ?>
                        <tr class="bkap_replace_response_data">
                            
                        </tr>

                    <?php
                }
                ?>

                <tr style="padding:5px; border-top:2px solid #eee; <?php echo $bkap_display_time_slots_pagination; ?> " > 
                    <td colspan="8" align="right" style="border-right: 0px;">
                        <?php
                            /**
                             * Add the  pagination
                             */
                            self::bkap_get_pagination_for_time_slots ( $bkap_per_page_time_slots, $bkap_total_time_slots_number, $bkap_total_pages, $bkap_encode_booking_times );

                        ?>
                    </td> 
                </tr>
                <tr style="padding:5px; border-top:2px solid #eee">
                   <td colspan="5" style="border-right: 0px;">
                   <i>
                       <small><?php _e( 'Create timeslots for the days/dates selected above.', 'woocommerce-booking' ); ?>
                       <br><?php _e( 'Enter time in 24 hours format e.g. 14:00.', 'woocommerce-booking' );?>
                       <br><?php _e( 'Leave "To time" unchanged if you do not wish to create a fixed time duration slot.', 'woocommerce-booking' );?>
                       </small>
                   <i></td>
                   <td colspan="3" align="right" style="border-left: none;"><button type="button" class="button-primary bkap_add_new_date_time_range"><?php _e( 'Add New Timeslot' , 'woocommerce-booking' );?></button></td>
                </tr>
            </table>
            
           </div>
           <?php 
        }
        
        /**
         * Display heading in the days/dates and timeslot table.
         *
         */
        
        static function bkap_get_daydate_and_time_heading( $product_id, $booking_settings, $bkap_display_time_slots_pagination, $bkap_per_page_time_slots, $bkap_total_time_slots_number, $bkap_total_pages, $bkap_encode_booking_times  ){
        
            ?>
            <tr>
                <th width="20%"><?php _e( 'Weekday', 'woocommerce-booking' );?></th>
                <th width="10%"><?php _e( 'From', 'woocommerce-booking' );?></th>
                <th width="10%"><?php _e( 'To', 'woocommerce-booking' );?></th>
                <th width="10%"><?php _e( 'Maximum Bookings', 'woocommerce-booking' );?></th>
                <th width="10%"><?php _e( 'Price', 'woocommerce-booking' );?></th>
                <th width="10%"><?php _e( 'Global', 'woocommerce-booking' );?></th>
                <th width="23%"><?php _e( 'Note', 'woocommerce-booking' );?></th>
                <th width="4%"></th>
            </tr>
            <tr style="padding:5px; border-top:2px solid #eee; <?php echo $bkap_display_time_slots_pagination; ?> " > 
                    <td colspan="8" align="right" style="border-right: 0px;">
                        <?php
                            /**
                             * Add the  pagination
                             */
                            self::bkap_get_pagination_for_time_slots ( $bkap_per_page_time_slots, $bkap_total_time_slots_number, $bkap_total_pages, $bkap_encode_booking_times );

                        ?>
                    </td> 
                </tr>
            <?php 
         }
         
         /**
          * Display row in the days/dates and timeslot table.
          *
          */
          
         static function bkap_get_daydate_and_time_table_base_data( $product_id, $booking_settings ){
             // count integer hase to disaply ni value change block kari nakhishu.
            global $bkap_weekdays;
            
            $recurring_weekdays = array();
            $specific_dates = array();
            
            $bookable = bkap_common::bkap_get_bookable_status( $product_id );
            if ( $bookable && isset( $booking_settings[ 'booking_recurring' ] ) && count( $booking_settings[ 'booking_recurring' ] ) > 0 ) { // bookable product
                $recurring_weekdays = $booking_settings[ 'booking_recurring' ];
            } else if ( ! $bookable ) { // it's a new product
                foreach( $bkap_weekdays as $day_name => $day_value ) {
                    $recurring_weekdays[ $day_name ] = 'on'; // all weekdays are on by default
                }
            }
            
            if ( $bookable && isset( $booking_settings[ 'booking_specific_date' ] ) && count( $booking_settings[ 'booking_specific_date' ] ) > 0 ) {
                $specific_dates = $booking_settings[ 'booking_specific_date' ];
            }
            
            ?>
             <tr id="bkap_default_date_time_row" style="display: none;">
                 <td width="20%" id="select_td">
                     <select id="bkap_dateday_selector" multiple="multiple">
                       <?php 
                        foreach( $bkap_weekdays as $w_value => $w_name ) {
                            if ( isset( $recurring_weekdays[ $w_value ] ) && 'on' == $recurring_weekdays[ $w_value ] ) {
                                printf( "<option value='%s'>%s</option>\n", $w_value, $w_name );
                            }
                        }
                        foreach( $specific_dates as $dates => $lockout ) {
                            printf( "<option value='%s'>%s</option>\n", $dates, $dates );
                        }    
                        ?>
                       <option name="all" value="all"><?php _e( 'All', 'woocommerce-booking' );?></option>
                     </select>
                 </td>
                 <td width="10%"><input id="bkap_from_time" type="text" name="quantity" style="width:100%;" title="Please enter time in 24 hour format e.g 14:00 or 03:00" placeholder="HH:MM" maxlength="5" onkeypress="return bkap_isNumberKey(event)"></td>
                 <td width="10%"><input id="bkap_to_time" type="text" name="quantity" style="width:100%;" title="Please enter time in 24 hour format e.g 14:00 or 03:00" placeholder="HH:MM" maxlength="5" onkeypress="return bkap_isNumberKey(event)"></td>
                 <td width="10%"><input id="bkap_lockout_time" type="number" name="quantity" style="width:100%;" placeholder="Max bookings"></td>
                 <td width="10%"><input id="bkap_price_time"type="text" name="quantity" style="width:100%;" placeholder="Price"></td>
                 <td width="10%" style="text-align:center;">
                     <label class="bkap_switch">
                       <input id="bkap_global_time" type="checkbox" name="bkap_global_timeslot" style="margin-left: 35%;">
                       <div class="bkap_slider round"></div>
                     </label>
                 </td>
                 <td width="23%"><textarea id="bkap_note_time" rows="1" cols="2" style="width:100%;"></textarea></td>
                 <td width="4%" id="bkap_close" style="text-align: center;cursor:pointer;"><i class="fa fa-trash" aria-hidden="true"></i></td>
             </tr>
             <?php 
          }
        
    /**
     * This function saves the data from the tabs.
     * Different save buttons are present in each tab.
     * They all will call this function, which will check
     * the data present and save the same.
     * @since 4.0.0
     */
public static function bkap_save_settings() {
        
        // Product ID
        $post_id = $_POST[ 'product_id' ];
        $product_id = bkap_common::bkap_get_product_id( $post_id );
        
        // Booking Options Tab settings
        $clean_booking_options = '';
        if ( isset( $_POST[ 'booking_options' ] ) ) {
            $post_booking_options = $_POST[ 'booking_options' ];
            $tempData = str_replace( "\\", "", $post_booking_options );
            $clean_booking_options = json_decode($tempData);

        } 
            
        // Settings Tab settings
        $clean_settings_data = '';
        if ( isset( $_POST[ 'settings_data' ] ) ) {
            $post_settings_data = $_POST[ 'settings_data' ];
            $tempData = str_replace( "\\", "", $post_settings_data );
            $clean_settings_data = json_decode($tempData);
            
        }
        
        $ranges_array = array();
        // Fixed Blocks Tab
        if ( isset( $_POST[ 'blocks_enabled' ] ) ) {
            $ranges_array[ 'blocks_enabled' ] = $_POST[ 'blocks_enabled' ];
        }
        
        // Fixed Block Booking table data.
        $clean_fixed_block_data = '';
        if ( isset( $_POST[ 'fixed_block_data' ] ) ) {
            $post_fixed_block_data = $_POST[ 'fixed_block_data' ];
            $tempData = str_replace( "\\", "", $post_fixed_block_data );
            $clean_fixed_block_data = json_decode( $tempData );
        }
        
        // Fixed Block Booking table data.
        $clean_price_range_data = '';
        if ( isset( $_POST[ 'price_range_data' ] ) ) {
            $post_price_range_data = $_POST[ 'price_range_data' ];
            $clean_price_range_data = (object) array( 
                'bkap_price_range_data' => stripslashes( $post_price_range_data ) );
        }
        
        
        // Price Ranges Tab
        if ( isset( $_POST[ 'ranges_enabled' ] ) ) {
            $ranges_array[ 'ranges_enabled' ] = $_POST[ 'ranges_enabled' ];
        }
        
        // GCal Tab
        $clean_gcal_data = '';
        if ( isset( $_POST[ 'gcal_data' ] ) ) {
            $post_gcal_data = $_POST[ 'gcal_data' ];
            $tempData = str_replace( "\\", "", $post_gcal_data );
            $clean_gcal_data = json_decode($tempData);
        }
        
        $booking_box_class = new bkap_booking_box_class();
        $booking_box_class->setup_data( $product_id, $clean_booking_options, $clean_settings_data, $ranges_array, $clean_gcal_data, $clean_fixed_block_data, $clean_price_range_data );
        die();
    }

    /**
     *
     * @param int $product_id
     * @param stdClass $clean_booking_options
     * @param stdClass $clean_settings_data
     * @since 4.0.0
     */
    function setup_data( $product_id, $clean_booking_options, $clean_settings_data, $ranges_array, $clean_gcal_data, $clean_fixed_block_data, $clean_price_range_data ) {

        $final_booking_options = array();
        $settings_data = array();
        $block_ranges = array();
        $gcal_data = array();
        $fixed_block_data = array();
        $price_range_data = array();
        
        if( $clean_booking_options != '' && count( $clean_booking_options ) > 0 ) {
            
            $final_booking_options[ '_bkap_enable_booking' ] = $clean_booking_options->booking_enable_date;
            $final_booking_options[ '_bkap_booking_type' ] = $clean_booking_options->booking_type;
            $final_booking_options[ '_bkap_enable_inline' ] = $clean_booking_options->enable_inline;
            $final_booking_options[ '_bkap_purchase_wo_date' ] = $clean_booking_options->purchase_wo_date;
            $final_booking_options[ '_bkap_requires_confirmation' ] = $clean_booking_options->requires_confirmation;
            
            if( isset($clean_booking_options->wkpbk_block_single_week ) && isset($clean_booking_options->special_booking_start_weekday) && isset($clean_booking_options->special_booking_end_weekday) ) {
                $final_booking_options[ '_bkap_week_blocking'] = $clean_booking_options->wkpbk_block_single_week;
                $final_booking_options[ '_bkap_start_weekday' ] = $clean_booking_options->special_booking_start_weekday;
                $final_booking_options[ '_bkap_end_weekday' ] = $clean_booking_options->special_booking_end_weekday;
            }
        
        }
        if ( $clean_settings_data != '' && count( $clean_settings_data ) > 0 ) {

            // Booking enabled
            if ( isset( $clean_booking_options->booking_enable_date ) && '' != $clean_booking_options->booking_enable_date ) {
                $booking_enabled = $clean_booking_options->booking_enable_date;
            } else {
                $booking_enabled = get_post_meta( $product_id, '_bkap_enable_booking', true );
            }
            
            // Booking Type
            if ( isset( $clean_booking_options->booking_type ) && '' != $clean_booking_options->booking_type ) {
                $booking_type = $clean_booking_options->booking_type;
            } else {
                $booking_type = get_post_meta( $product_id, '_bkap_booking_type', true );
            }
            
            $settings_data[ '_bkap_abp' ] = $clean_settings_data->abp;
            $settings_data[ '_bkap_max_bookable_days' ] = $clean_settings_data->max_bookable;
            
            if ( isset( $clean_settings_data->date_lockout ) ) {
                $settings_data[ '_bkap_date_lockout' ] = $clean_settings_data->date_lockout;
            }
            if ( isset( $clean_settings_data->min_days_multiple ) ) {
                $settings_data[ '_bkap_multiple_day_min' ] = $clean_settings_data->min_days_multiple;
            }
            if ( isset( $clean_settings_data->max_days_multiple ) ) {
                $settings_data[ '_bkap_multiple_day_max' ] = $clean_settings_data->max_days_multiple;
            }
            
            $booking_recurring = array();
            $recurring_lockout = array();
            $recurring_prices = array();
            
            for( $i = 0; $i <= 6; $i++ ) {
                $weekday_name = "booking_weekday_$i";
                $lockout_name = "weekday_lockout_$i";
                $price_name = "weekday_price_$i";
            
                $booking_recurring[ $weekday_name ] = $clean_settings_data->$weekday_name;
                $recurring_lockout[ $weekday_name ] = isset( $clean_settings_data->$lockout_name ) ? $clean_settings_data->$lockout_name : 0;
            
                if ( is_numeric( $clean_settings_data->$price_name ) ) {
                    $recurring_prices[ $weekday_name ] = $clean_settings_data->$price_name;
                }
            }
            
            $enable_recurring = '';
            if ( in_array( 'on', $booking_recurring ) ) {
                $enable_recurring = 'on';
            }
            
            $settings_data[ '_bkap_enable_recurring' ] = $enable_recurring;
            $settings_data[ '_bkap_recurring_weekdays' ] = $booking_recurring;
            $settings_data[ '_bkap_recurring_lockout' ] = $recurring_lockout;
            
            $settings_data[ '_bkap_enable_specific' ] = $clean_settings_data->enable_specific;
            
            $settings_data[ '_bkap_product_holidays' ] = $this->create_date_list( $clean_settings_data->holidays_list );

            //$settings_data[ '_bkap_specific_dates' ] = $this->create_date_list( $clean_settings_data->specific_list );
            if ( isset( $booking_type ) && 'multiple_days' != $booking_type ) {
                $settings_data[ '_bkap_specific_dates' ] = $this->create_date_list( $clean_settings_data->specific_list );
            } else {
                $settings_data[ '_bkap_specific_dates' ] = array();
            }
            
            $specific_prices = $this->create_specific_price_list( $clean_settings_data->specific_list );
            
            // update the special prices
            $special_price_class = new bkap_special_booking_price();
            $special_price_class->bkap_save_special_booking_price( $product_id, $recurring_prices, $specific_prices );
            
            $settings_data[ '_bkap_custom_ranges'] = $this->create_range_data( $clean_settings_data->custom_range );
            $settings_data[ '_bkap_holiday_ranges' ] = $this->create_range_data( $clean_settings_data->holiday_range );
            $settings_data[ '_bkap_month_ranges' ] = $this->create_range_data( $clean_settings_data->month_range );

            // date & time settings
            $booking_time_settings = array();
            $existing_time_settings = get_post_meta( $product_id, '_bkap_time_settings', true );
            if ( isset( $clean_settings_data->booking_times ) && count( $clean_settings_data->booking_times ) > 0 ) {
                foreach( $clean_settings_data->booking_times as $booking_times ) {
                    $record_present = false; // assume no record is present for this date/day and time slot
                    $days = array();
                    if ( is_array( $booking_times->day ) && count( $booking_times->day ) > 0 ) {
                        foreach( $booking_times->day as $day ) {
                            $days[] = $day;
                        }
                    } else {
                        $days[] = $booking_times->day;
                    }

                    // check if any of the values is set to 'ALL' if so, then unset it and insert records for all the values in the dropdown
                    foreach( $days as $d_key => $d_value ) {
                    
                        if ( 'all' == $d_value ) {
                            unset( $days[ $d_key ] );
                            // add records for all the days/dates
                            foreach( $booking_recurring as $b_key => $b_value ) {
                                if ( 'on' == $b_value ) {
                                    $days[] = $b_key;
                                }
                            }
                            // specific dates
                            foreach( $settings_data[ '_bkap_specific_dates' ] as $dates => $lockout ) {
                                $days[] = $dates;
                            }
                        }
                    }
                    
                    // for all the days
                    foreach ( $days as $day_check ) {
            
                        $from_slot_array = explode( ':', $booking_times->from_time );
            
                        $from_slot_hrs = trim( $from_slot_array[ 0 ] );
                        $from_slot_min = trim( $from_slot_array[ 1 ] );

                        $from_slot_hrs = ( $from_slot_hrs != "" ) ? $from_slot_hrs : '00';
                        $from_slot_min = ( $from_slot_min != "" ) ? $from_slot_min : '00';
            
                        $to_slot_hrs = '0';
                        $to_slot_min = '00';
            
                        if ( isset( $booking_times->to_time ) && '' != $booking_times->to_time ) {
                            $to_slot_array = explode( ':', $booking_times->to_time );
            
                            $to_slot_hrs = trim( $to_slot_array[ 0 ] );
                            $to_slot_min = trim( $to_slot_array[ 1 ] );
                        }
            
                        // check if a record exists already
                        if ( is_array( $existing_time_settings ) && count( $existing_time_settings ) > 0 ) {
                            // check if there's a record present for that day/date
                            if ( array_key_exists( $day_check, $existing_time_settings ) ) {
            
                                foreach( $existing_time_settings[ $day_check ] as $key => $existing_record ) {
            
                                    if ( $from_slot_hrs == $existing_record['from_slot_hrs']
                                        && $from_slot_min == $existing_record['from_slot_min']
                                        && $to_slot_hrs == $existing_record['to_slot_hrs']
                                        && $to_slot_min == $existing_record['to_slot_min'] ) {
                                            
                                        $new_key =  $key;
                                        $record_present = true;
                                        break;
                                    }
                                }
                            }
                        }
                        if ( ! $record_present ) {
                            // check if there's a record present for that day/date
                            if ( array_key_exists( $day_check, $booking_time_settings ) ) {
                                $new_key = count( $booking_time_settings[ $day_check ] );
                            } else {
                                $new_key = 0;
                            }
                        }
            
                        $booking_time_settings[ $day_check ][ $new_key ][ 'from_slot_hrs' ] = $from_slot_hrs;
                        $booking_time_settings[ $day_check ][ $new_key ][ 'from_slot_min' ] =  $from_slot_min;
                        $booking_time_settings[ $day_check ][ $new_key ][ 'to_slot_hrs' ] = $to_slot_hrs;
                        $booking_time_settings[ $day_check ][ $new_key ][ 'to_slot_min' ] =  $to_slot_min;
                        $booking_time_settings[ $day_check ][ $new_key ][ 'booking_notes' ] = $booking_times->booking_notes;
                        $booking_time_settings[ $day_check ][ $new_key ][ 'slot_price' ] = $booking_times->slot_price;
                        $booking_time_settings[ $day_check ][ $new_key ][ 'lockout_slot' ] = $booking_times->lockout_slot;
                        $booking_time_settings[ $day_check ][ $new_key ][ 'global_time_check' ] = $booking_times->global_time_check;
                    }
                }
            
                if ( is_array( $booking_time_settings ) ) {
                    $settings_data[ '_bkap_time_settings' ] = $booking_time_settings;
                }
            }
        }
        
        if ( isset( $ranges_array[ 'blocks_enabled' ] ) ) {
            $block_ranges[ '_bkap_fixed_blocks' ] = $ranges_array[ 'blocks_enabled' ];
        }
        
        
        // Fixed Block bookings data.
        if( $clean_fixed_block_data != '' && count( $clean_fixed_block_data ) > 0 ) {
            $block_ranges['_bkap_fixed_blocks_data'] = bkap_block_booking::bkap_updating_fixed_block_data_in_db( $product_id, $clean_fixed_block_data );
            
        }
        
        if ( isset( $ranges_array[ 'ranges_enabled' ] ) ) {
            $block_ranges[ '_bkap_price_ranges' ] = $ranges_array[ 'ranges_enabled' ];
        }

        // Price by range of day data.
        if( $clean_price_range_data != '' && count( $clean_price_range_data ) > 0 ) {
        
            $block_ranges['_bkap_price_range_data'] = bkap_block_booking::bkap_updating_price_range_data_in_db( $product_id, $clean_price_range_data );
        
        }
          
        if ( $clean_gcal_data != '' && count( $clean_gcal_data ) > 0 ) {
        
            $gcal_data[ '_bkap_gcal_integration_mode' ] = $clean_gcal_data->gcal_sync_mode;
            $gcal_data[ '_bkap_gcal_key_file_name' ] = $clean_gcal_data->key_file_name;
            $gcal_data[ '_bkap_gcal_service_acc' ] = $clean_gcal_data->service_acc_email;
            $gcal_data[ '_bkap_gcal_calendar_id' ] = $clean_gcal_data->calendar_id;
        
            if ( isset( $clean_gcal_data->gcal_auto_mapping ) ) {
                $gcal_data[ '_bkap_enable_automated_mapping' ] = $clean_gcal_data->gcal_auto_mapping;
            }
        
            if ( isset( $clean_gcal_data->default_variation ) ) {
                $gcal_data[ '_bkap_default_variation' ] = $clean_gcal_data->default_variation;
            }
        
            $import_feed_url = array();
            for( $i = 0; ; $i++ ) {
                $field_name = "ics_feed_url_$i";
        
                if ( isset( $clean_gcal_data->$field_name ) ) {
                    $import_feed_url[ $i ] = $clean_gcal_data->$field_name;
                } else {
                    break;
                }
            }
            $gcal_data[ '_bkap_import_url' ] = $import_feed_url;
        
        }
        
        // update individual settings
        $this->update_single_post_meta( $product_id, $final_booking_options, $settings_data, $block_ranges, $gcal_data );
        
        // update old post meta record
        $this->update_serialized_post_meta( $product_id, $final_booking_options, $settings_data, $block_ranges, $gcal_data );
        
        // update booking history
        if ( ( isset( $booking_enabled ) && 'on' == $booking_enabled ) && isset( $booking_type ) && 'only_day' == $booking_type ) {
            $this->update_bkap_history_only_days( $product_id, $settings_data );
        } else if ( ( isset( $booking_enabled ) && 'on' == $booking_enabled ) && isset( $booking_type ) && 'date_time' == $booking_type ) {
            $this->update_bkap_history_date_time( $product_id, $settings_data );
        }
        
    }
            
    /**
     * Receives a string which contains a list of dates and
     * the number of recurring years. It splits it into an array
     * where the date is the key and the number of years is the value
     *
     * @param str $dates_string
     * String format is as below:
     * date1,date2+years;date3+years;....
     *
     * @since 4.0.0
     * @return array $dates_array
     */
    function create_date_list( $dates_string ) {
    
        $dates_array = array();
    
        $dates_split = explode( ';', $dates_string );
    
        // if dates have been set up
        if ( is_array( $dates_split ) && count( $dates_split ) > 0 ) {
            foreach( $dates_split as $d_value ) {
                if ( $d_value != '' ) {
    
                    $dates_list = $d_value;
                    $recur_years = 0;
                    // recurring years and prices are added using +
                    $recurring_setup = strpos( $d_value, '+' );
                    // check if recurring years have been setup
                    if ( $recurring_setup !== false ) {
                        $dates_list = substr( $d_value, 0, $recurring_setup );
                        $explode_dates = explode( '+', $d_value );
                        if ( $explode_dates[ 1 ] > 0 ) {
                            $recur_years = $explode_dates[ 1 ];
                        }
                    }
                    // get the dates list, there maybe more than 1 dates comma separated
                    $explode_dates = explode( ',', $dates_list );
                    foreach ( $explode_dates as $single_date ) {
                        if ( '' != $single_date ) {
                            $dates_array[ $single_date ] = $recur_years;
                        }
                    }
    
                }
            }
        }
    
        return $dates_array;
    }
    
    /**
     *
     * @param string $dates_array
     */
    function create_specific_price_list( $dates_string ) {
    
        $dates_array = array();
    
        $dates_split = explode( ';', $dates_string );
    
        // if dates have been set up
        if ( is_array( $dates_split ) && count( $dates_split ) > 0 ) {
            foreach( $dates_split as $d_value ) {
                if ( $d_value != '' ) {
    
                    $dates_list = $d_value;
                    $recur_years = 0;
                    $date_price = '';
    
                    $recurring_setup = strpos( $d_value, '+' );
                    // check if recurring years & price have been setup
                    if ( $recurring_setup !== false ) {
                        $dates_list = substr( $d_value, 0, $recurring_setup );
                        $explode_dates = explode( '+', $d_value );
                        // check if price is set
                        if ( isset( $explode_dates[ 2 ] ) && is_numeric( $explode_dates[ 2 ] ) && $explode_dates[ 2 ] > 0 ) {
                            $date_price = $explode_dates[ 2 ];
                        }
                    }
                    // get the dates list
                    $explode_dates = explode( ',', $dates_list );
                    foreach ( $explode_dates as $single_date ) {
                        if ( '' != $single_date && is_numeric( $date_price ) ) {
                            $dates_array[ $single_date ] = $date_price;
                        }
                    }
    
                }
            }
        }
    
        return $dates_array;
    }
    
    /**
     *
     * @param string $range_string
     * @since 4.0.0
     * @return array $range_array
     */
    function create_range_data( $range_string ) {
        global $bkap_months;
        $range_array = array();
    
        $range_split = explode( ';', $range_string );
    
        $current_year = date( 'Y', current_time( 'timestamp' ) );
        $next_year = date( 'Y', strtotime( '+1 year' ) );
        
        // if ranges have been set up
        if ( is_array( $range_split ) && count( $range_split ) > 0 ) {
            foreach( $range_split as $r_value ) {
                if ( $r_value != '' ) {
                    $range_start = '';
                    $range_end   = '';
                    $range_type  = '';
                    $range_recur = 0;
    
                    $explode_range = explode( '+', $r_value );
    
                    if ( isset( $explode_range[ 0 ] ) ) {
                        $range_start = $explode_range[ 0 ];
                        if ( is_numeric( $range_start ) ) { // it's a month number
                            $month_name = $bkap_months[ $range_start ];
                            $month_to_use = "$month_name $current_year";
                            $range_start = date ( 'j-n-Y', strtotime( $month_to_use ) );
                        } else { // it is a date
                            if ( $range_start == '' ) {
                                continue; // pick the next range
                            } else {
                                $range_start = date ( 'j-n-Y', strtotime( $range_start ) );
                            }
                        }
                    }
                    if ( isset( $explode_range[ 1 ] ) ) {
                        $range_end = $explode_range[ 1 ];
                        if ( is_numeric( $range_end ) ) { // it's a month number
                            $month_name = $bkap_months[ $range_end ];
                            
                            if ( $explode_range[ 0 ] <= $explode_range[ 1 ] ) {
                                $month_to_use = "$month_name $current_year";
                            } else {
                                $month_to_use = "$month_name $next_year";
                            }
                            $month_start = date ( 'j-n-Y', strtotime( $month_to_use ) );
                            
                            $days = date( 't', strtotime( $month_start ) );
                            $days -= 1;
                            $range_end = date ( 'j-n-Y', strtotime( "+$days days", strtotime( $month_start ) ) );
                            
                        } else { // it is a date
                            if ( $range_end == '' ) {
                                continue; // pick the next range
                            } else {
                                $range_end = date( 'j-n-Y', strtotime( $range_end ) );
                            }
                        }
                    }
                    if ( isset( $explode_range[ 2 ] ) ) {
                        $range_recur = $explode_range[ 2 ];
                    }

                    if ( isset( $explode_range[ 3 ] ) ) {
                        $range_type = $explode_range[ 3 ];
                    }
    
                    $range_array[] = array( 'start' => $range_start,
                                            'end' => $range_end,
                                            'years_to_recur' => $range_recur,
                                            'range_type' => $range_type
                                        );
    
                }
            }
        }
        return $range_array;
    }
    /**
     * 
     * @param int $product_id
     * @param array $booking_options
     * @param array $settings_data
     * @since 4.0.0
     */
    function update_single_post_meta( $product_id, $booking_options, $settings_data, $block_ranges, $gcal_data ) {
        
        if ( is_array( $booking_options ) && count( $booking_options ) > 0 ) {
            foreach( $booking_options as $booking_key => $booking_value ) {
                update_post_meta( $product_id, $booking_key, $booking_value );
            }
        }
        
        if ( is_array( $settings_data ) && count( $settings_data ) > 0 ) {
            foreach( $settings_data as $settings_key => $settings_value ) {
                update_post_meta( $product_id, $settings_key, $settings_value );
            }
        }
        
        if ( is_array( $block_ranges ) && count( $block_ranges ) > 0 ) {
            foreach( $block_ranges as $br_keys => $br_values ) {
                update_post_meta( $product_id, $br_keys, $br_values );
            }
        }
        
        if ( is_array( $gcal_data ) && count( $gcal_data ) > 0 ) {
            foreach( $gcal_data as $gcal_key => $gcal_value ) {
                update_post_meta( $product_id, $gcal_key, $gcal_value );
            }
        }
    }
    
    /**
     *
     * @param int $product_id
     * @param array $booking_options
     * @param array $settings_data
     * @since 4.0.0
     */
    function update_serialized_post_meta( $product_id, $booking_options, $settings_data, $block_ranges, $gcal_data ) {
    
        // Save Bookings
        $updated_settings = array();
    
        if ( isset( $booking_options ) && is_array( $booking_options ) && count( $booking_options ) > 0 ) {
    
            if ( isset( $booking_options[ '_bkap_enable_booking' ] ) ) {
                $updated_settings[ 'booking_enable_date' ] = $booking_options[ '_bkap_enable_booking' ];
            } else {
                $updated_settings[ 'booking_enable_date' ] = '';
            }
    
            if ( isset( $booking_options[ '_bkap_booking_type' ] ) && '' != $booking_options[ '_bkap_booking_type' ] ) {
    
                if ( 'date_time' == $booking_options[ '_bkap_booking_type' ] ) {
                    $updated_settings[ 'booking_enable_multiple_day' ] = '';
                    $updated_settings[ 'booking_enable_time' ] = 'on';
                } else if ( 'multiple_days' == $booking_options[ '_bkap_booking_type' ] ) {
                    $updated_settings[ 'booking_enable_multiple_day' ] = 'on';
                    $updated_settings[ 'booking_enable_time' ] = '';
                } else if ( 'only_day' == $booking_options[ '_bkap_booking_type' ] ) {
                    $updated_settings[ 'booking_enable_multiple_day' ] = '';
                    $updated_settings[ 'booking_enable_time' ] = '';
                }
            }
    
            if ( isset( $booking_options[ '_bkap_enable_inline' ] ) ) {
                $updated_settings[ 'enable_inline_calendar' ] = $booking_options[ '_bkap_enable_inline' ];
            } else {
                $updated_settings[ 'enable_inline_calendar' ] = '';
            } 
            
            if ( isset( $booking_options[ '_bkap_purchase_wo_date' ] ) ) {
                $updated_settings[ 'booking_purchase_without_date' ] = $booking_options[ '_bkap_purchase_wo_date' ];
            } else {
                $updated_settings[ 'booking_purchase_without_date' ] = '';
            } 
             
            if ( isset( $booking_options[ '_bkap_requires_confirmation' ] ) ) {
                $updated_settings[ 'booking_confirmation' ] = $booking_options[ '_bkap_requires_confirmation' ];
            } else {
                $updated_settings[ 'booking_confirmation' ] = '';
            } 

            if( isset( $booking_options[ '_bkap_week_blocking' ] ) ) {
                $updated_settings[ 'wkpbk_block_single_week' ] =  $booking_options[ '_bkap_week_blocking' ];
            }else {
                $updated_settings[ 'wkpbk_block_single_week' ] = '';
            }

            if( isset( $booking_options[ '_bkap_start_weekday' ] ) ) {
                $updated_settings[ 'special_booking_start_weekday' ] =  $booking_options[ '_bkap_start_weekday' ];
            }else {
                $updated_settings[ 'special_booking_start_weekday' ] = '';
            }

            if( isset( $booking_options[ '_bkap_end_weekday' ] ) ) {
                $updated_settings[ 'special_booking_end_weekday' ] =  $booking_options[ '_bkap_end_weekday' ];
            }else {
                $updated_settings[ 'special_booking_end_weekday' ] = '';
            }
        }
    
        if ( isset( $settings_data ) && is_array( $settings_data ) && count( $settings_data ) > 0 ) {
        
            //product level - minimum booking for multiple days
            $multiple_min_days = 0;
            if ( isset( $settings_data[ '_bkap_multiple_day_min' ] ) && $settings_data[ '_bkap_multiple_day_min' ] > 0 ) {
                $updated_settings[ 'booking_minimum_number_days_multiple' ] = $settings_data[ '_bkap_multiple_day_min' ];
                $updated_settings[ 'enable_minimum_day_booking_multiple' ] = 'on';
            } else {
                $updated_settings[ 'enable_minimum_day_booking_multiple' ] = '';
                $updated_settings[ 'booking_minimum_number_days_multiple' ] = 0;
            }
    
            $multiple_max_days = 365;
            if ( isset( $settings_data[ '_bkap_multiple_day_max' ] ) && $settings_data[ '_bkap_multiple_day_max' ] > 0 ) {
                $multiple_max_days = $settings_data[ '_bkap_multiple_day_max' ];
            }
            $updated_settings[ 'booking_maximum_number_days_multiple' ] = $multiple_max_days;
            
            if ( isset( $settings_data[ '_bkap_custom_ranges' ] ) ) {
                $updated_settings[ 'booking_date_range' ] = $settings_data[ '_bkap_custom_ranges' ];
            } else {
                $updated_settings[ 'booking_date_range' ] = array();
            } 
            
            if ( isset( $settings_data[ '_bkap_abp' ] ) ) {
                $updated_settings[ 'booking_minimum_number_days' ] = $settings_data[ '_bkap_abp' ];
            } else {
                $updated_settings[ 'booking_minimum_number_days' ] = 0;
            } 
    
            if ( isset( $settings_data[ '_bkap_max_bookable_days' ] ) ) {
                $updated_settings[ 'booking_maximum_number_days' ] = $settings_data[ '_bkap_max_bookable_days' ];
            } else {
                $updated_settings[ 'booking_maximum_number_days' ] = '';
            } 
    
            if ( isset( $settings_data[ '_bkap_date_lockout' ] ) ) {
                $updated_settings[ 'booking_date_lockout' ] = $settings_data[ '_bkap_date_lockout' ];
            } else {
                $updated_settings[ 'booking_date_lockout' ] = '';
            } 
            
            if ( isset( $settings_data[ '_bkap_product_holidays' ] ) ) {
                $updated_settings[ 'booking_product_holiday' ] = $settings_data[ '_bkap_product_holidays' ];
            } else {
                $updated_settings[ 'booking_product_holiday' ] = array();
            } 
            
            if ( isset( $settings_data[ '_bkap_specific_dates' ] ) ) {
                $updated_settings[ 'booking_specific_date' ] = $settings_data[ '_bkap_specific_dates' ];
            } else {
                $updated_settings[ 'booking_specific_date' ] = array();
            } 
            
            if ( isset( $settings_data[ '_bkap_enable_recurring' ] ) ) {
                $updated_settings[ 'booking_recurring_booking' ] = $settings_data[ '_bkap_enable_recurring' ];
            } else {
                $updated_settings[ 'booking_recurring_booking' ] = '';
            } 
            
            if ( isset( $settings_data[ '_bkap_recurring_weekdays' ] ) ) {
                $updated_settings[ 'booking_recurring' ] = $settings_data[ '_bkap_recurring_weekdays' ];
            } else {
                $updated_settings[ 'booking_recurring' ] = array();
            } 
            
            if ( isset( $settings_data[ '_bkap_recurring_lockout' ] ) ) {
                $updated_settings[ 'booking_recurring_lockout' ] = $settings_data[ '_bkap_recurring_lockout' ];
            } else {
                $updated_settings[ 'booking_recurring_lockout' ] = array();
            } 

            if ( isset( $settings_data[ '_bkap_enable_specific' ] ) ) {
                $updated_settings[ 'booking_specific_booking' ] = $settings_data[ '_bkap_enable_specific' ];
            } else {
                $updated_settings[ 'booking_specific_booking' ] = '';
            } 
            
            if ( isset( $settings_data[ '_bkap_time_settings' ] ) ) {
                $updated_settings[ 'booking_time_settings' ] = $settings_data[ '_bkap_time_settings' ];
            } else {
                $updated_settings[ 'booking_time_settings' ] = array();
            } 
        }
    
        if ( isset( $block_ranges ) && is_array( $block_ranges ) && count( $block_ranges ) > 0 ) {
            if( isset( $block_ranges[ '_bkap_fixed_blocks' ] ) ) {
                $updated_settings[ 'booking_fixed_block_enable' ] = $block_ranges[ '_bkap_fixed_blocks' ];
            } 
        
            if ( isset( $block_ranges[ '_bkap_price_ranges' ] ) ) {
                $updated_settings[ 'booking_block_price_enable' ] = $block_ranges[ '_bkap_price_ranges' ];
            }

            if( isset( $block_ranges[ '_bkap_price_range_data' ] ) ){
                $updated_settings[ 'bkap_price_range_data' ] = $block_ranges[ '_bkap_price_range_data' ];
            }
            
            if( isset( $block_ranges[ '_bkap_fixed_blocks_data' ] ) ){
                $updated_settings[ 'bkap_fixed_blocks_data' ] = $block_ranges[ '_bkap_fixed_blocks_data' ];
            }
        }
        
        if ( isset( $gcal_data ) && is_array( $gcal_data ) && count( $gcal_data ) > 0 ) {
        
            if ( isset( $gcal_data[ '_bkap_gcal_integration_mode' ] ) ) {
                $updated_settings[ 'product_sync_integration_mode' ] = $gcal_data[ '_bkap_gcal_integration_mode' ];
            } else {
                $updated_settings[ 'product_sync_integration_mode' ] = '';
            } 
        
            if ( isset( $gcal_data[ '_bkap_gcal_key_file_name' ] ) ) {
                $updated_settings[ 'product_sync_key_file_name' ] = $gcal_data[ '_bkap_gcal_key_file_name' ];
            } else {
                $updated_settings[ 'product_sync_key_file_name' ] = '';
            } 
        
            if ( isset( $gcal_data[ '_bkap_gcal_service_acc' ] ) ) {
                $updated_settings[ 'product_sync_service_acc_email_addr' ] = $gcal_data[ '_bkap_gcal_service_acc' ];
            } else {
                $updated_settings[ 'product_sync_service_acc_email_addr' ] = '';
            } 
        
            if ( isset( $gcal_data[ '_bkap_gcal_calendar_id' ] ) ) {
                $updated_settings[ 'product_sync_calendar_id' ] = $gcal_data[ '_bkap_gcal_calendar_id' ];
            } else {
                $updated_settings[ 'product_sync_calendar_id' ] = '';
            } 
        
            if ( isset( $gcal_data[ '_bkap_enable_automated_mapping' ] ) ) {
                $updated_settings[ 'enable_automated_mapping' ] = $gcal_data[ '_bkap_enable_automated_mapping' ];
            } else {
                $updated_settings[ 'enable_automated_mapping' ] = '';
            } 
        
            if ( isset( $gcal_data[ '_bkap_default_variation' ] ) ) {
                $updated_settings[ 'gcal_default_variation' ] = $gcal_data[ '_bkap_default_variation' ];
            } else {
                $updated_settings[ 'gcal_default_variation' ] = '';
            }
        
            if ( isset( $gcal_data[ '_bkap_import_url' ] ) ) {
                $updated_settings[ 'ics_feed_url' ] = $gcal_data[ '_bkap_import_url' ];
            } else {
                $updated_settings[ 'ics_feed_url' ] = array();
            } 
        
        }
        
        // Fetch the existing settings
        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
        // Merge the existing settings with the updated ones
        $final_settings = ( is_array( $booking_settings ) && count( $booking_settings ) > 0 ) ? array_merge( $booking_settings, $updated_settings ) : $updated_settings;
        // update post meta
        update_post_meta( $product_id, 'woocommerce_booking_settings', $final_settings );
    
    }
    
    /**
     *
     * @param int $product_id
     * @param array $booking_options
     * @since 4.0.0
     */
    function update_bkap_history_only_days( $product_id, $settings_data ) {
    
        if ( count( $settings_data ) > 0 ) {
    
            global $wpdb;
    
            $recurring_array = $settings_data[ '_bkap_recurring_weekdays' ];
            $recurring_lockout = $settings_data[ '_bkap_recurring_lockout' ];
            $specific_array = $settings_data[ '_bkap_specific_dates' ];
            
            // recurring days and lockout update
            if ( count ( $recurring_array ) > 0 && count( $recurring_lockout ) > 0 ) {
    
                foreach( $recurring_array as $weekday => $w_status ) {
    
                    if ( 'on' == $w_status ) { // weekday is enabled
    
                        $insert = true;
                        $available_booking = $recurring_lockout[ $weekday ];
                        $updated_lockout = $recurring_lockout[ $weekday ];
    
                        // check if the weekday is already present
                        $check_weekday_query = "SELECT total_booking, available_booking FROM `" . $wpdb->prefix . "booking_history`
                                                WHERE post_id = %d
                                                AND weekday = %s
                                                AND start_date = '0000-00-00'
                                                AND status = ''";
    
                        $check_weekday = $wpdb->get_results( $wpdb->prepare( $check_weekday_query, $product_id, $weekday ) );
    
                        // if yes, then update the lockout
                        if ( isset( $check_weekday ) && count( $check_weekday ) > 0 ) { // there will be only 1 active record at any given time
                            $insert            =   false;
                            if ( is_numeric($recurring_lockout[ $weekday ] ) && $recurring_lockout[ $weekday ] > 0 ) {
                                $change_in_lockout =   $recurring_lockout[ $weekday ] - $check_weekday[ 0 ]->total_booking;
                            } else if ( $recurring_lockout[ $weekday ] === '' || $recurring_lockout[ $weekday ] == 0 ) { // unlimited bookings
                                $change_in_lockout = 0;
                            }
                            
                        } else {
                            // if not found, check if there's a date record present
                            $existing_lockout = "SELECT total_booking FROM `" . $wpdb->prefix . "booking_history`
                                                                                WHERE post_id = %d
                                                                                AND start_date != '0000-00-00'
                                                                                AND weekday = %s
                                                                                ORDER BY id DESC LIMIT 1";
                            $lockout_results = $wpdb->get_results( $wpdb->prepare( $existing_lockout, $product_id, $weekday ) );
    
                            if ( isset( $lockout_results ) && count( $lockout_results ) > 0 ) {
                                if ( is_numeric( $recurring_lockout[ $weekday] ) && $recurring_lockout[ $weekday] > 0 ) {
                                    $change_in_lockout = $recurring_lockout[ $weekday] - $lockout_results[ 0 ]->total_booking;
                                    $available_booking = $lockout_results[ 0 ]->total_booking + $change_in_lockout;
                                } else if ( $recurring_lockout[ $weekday] === '' || $recurring_lockout[ $weekday] == 0 ) {
                                    $change_in_lockout = 0;
                                    $available_booking = 0;
                                }
                            }
    
                        }
    
                        if ( $insert ) {
                            $query_insert    =  "INSERT INTO `" . $wpdb->prefix . "booking_history`
                                                (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                                                VALUES (
                                                '" . $product_id . "',
                                                '" . $weekday . "',
                                                '0000-00-00',
                                                '0000-00-00',
                                                '',
                                                '',
                                                '" . $updated_lockout . "',
                                                '" . $available_booking . "' )";
                            $wpdb->query( $query_insert );
                        } else if ( isset( $change_in_lockout ) && is_numeric( $change_in_lockout ) ) {
                            
                            // Update the existing record so that lockout is managed and orders do not go missing frm the View bookings page
                            if ( $change_in_lockout == 0 && ( $recurring_lockout[ $weekday ] === '' || $recurring_lockout[ $weekday ] == 0 ) ) { // unlimited bookings
                                
                                $query_update    =  "UPDATE `" . $wpdb->prefix . "booking_history`
                                                    SET total_booking = '" . $updated_lockout . "',
                                                    available_booking = '" . $change_in_lockout . "'
                                                    WHERE post_id = '" . $product_id . "'
                                                    AND weekday = '" . $weekday . "'
                                                    AND start_date = '0000-00-00'
                                                    AND status = ''";
                            } else {
                                $query_update    =  "UPDATE `" . $wpdb->prefix . "booking_history`
                                                    SET total_booking = '" . $updated_lockout . "',
                                                    available_booking = available_booking + '" . $change_in_lockout . "'
                                                    WHERE post_id = '" . $product_id . "'
                                                    AND weekday = '" . $weekday . "'
                                                    AND start_date = '0000-00-00'
                                                    AND status = ''";
                            }
                            $wpdb->query( $query_update );
                        }
    
                        if ( isset( $change_in_lockout ) && is_numeric( $change_in_lockout ) ) {
                            
                            //Update the existing records for the dates
                            if ( $change_in_lockout == 0 && ( $recurring_lockout[ $weekday ] === '' || $recurring_lockout[ $weekday ] == 0 ) ) { // unlimited bookings
                                
                                $query_update        =   "UPDATE `" . $wpdb->prefix . "booking_history`
                                                        SET total_booking = '" . $updated_lockout . "',
                                                        available_booking = '" . $change_in_lockout . "',
                                                        status = ''
                                                        WHERE post_id = '" . $product_id . "'
                                                        AND weekday = '" . $weekday . "'
                                                        AND start_date <> '0000-00-00'";
                                
                            } else {
                                $query_update        =   "UPDATE `" . $wpdb->prefix . "booking_history`
                                                        SET total_booking = '" . $updated_lockout . "',
                                                        available_booking = available_booking + '" . $change_in_lockout . "',
                                                        status = ''
                                                        WHERE post_id = '" . $product_id . "'
                                                        AND weekday = '" . $weekday . "'
                                                        AND start_date <> '0000-00-00'";
                            }
                        
                            $wpdb->query( $query_update );
                        }
                    } else { // weekday is disabled
    
                        // if a record exists in the table, it needs to be deactivated
                        $update_query = "UPDATE `" . $wpdb->prefix . "booking_history`
                                        SET status = 'inactive'
                                        WHERE post_id = %d
                                        AND weekday = %s";
                        $wpdb->query( $wpdb->prepare( $update_query, $product_id, $weekday ) );
    
                        // Delete the base records for the recurring weekdays
                        $delete_base_query  =   "DELETE FROM `" . $wpdb->prefix."booking_history`
                                                    WHERE post_id = '" . $product_id ."'
                                                    AND weekday = '" .  $weekday . "'
                                                    AND start_date = '0000-00-00'";
    
                        $wpdb->query( $delete_base_query );
                    }
    
                }
            }
            
            if ( is_array( $specific_array ) && count( $specific_array ) > 0 ) {
            
                foreach( $specific_array as $specific_date => $specific_lockout ) {

                    $specific_date = date( 'Y-m-d', strtotime( $specific_date ) );
                    
                    $insert = true;
                    $available_booking = $specific_lockout;
                    $updated_lockout = $specific_lockout;

                    $check_date_query1 = "SELECT total_booking, available_booking FROM `" . $wpdb->prefix . "booking_history`
                                                WHERE post_id = %d
                                                AND weekday != ''
                                                AND start_date = %s
                                                AND status = ''";
            
                    $check_date1 = $wpdb->get_results( $wpdb->prepare( $check_date_query1, $product_id, $specific_date ) );

                    if( count( $check_date1 ) > 0 ) {

                        $query_update1  =   "UPDATE `".$wpdb->prefix."booking_history`
                                                SET weekday = '',                                                
                                                status = ''
                                                WHERE post_id = '" . $product_id . "'
                                                AND start_date = '" . $specific_date . "'";

                        $wpdb->query( $query_update1 );
                    }
            
                    // check if the date is already present
                    $check_date_query = "SELECT total_booking, available_booking FROM `" . $wpdb->prefix . "booking_history`
                                                WHERE post_id = %d
                                                AND weekday = ''
                                                AND start_date = %s
                                                AND status = ''";
            
                    $check_date = $wpdb->get_results( $wpdb->prepare( $check_date_query, $product_id, $specific_date ) );
            
                    // if yes, then update the lockout
                    if ( isset( $check_date ) && count( $check_date ) > 0 ) { // there will be only 1 active record at any given time
                        $insert            =   false;
                        if ( is_numeric( $specific_lockout ) && $specific_lockout > 0 ) {
                            $change_in_lockout =   $specific_lockout - $check_date[ 0 ]->total_booking;
                        } else if ( $specific_lockout === '' || $specific_lockout == 0 ) { // unlimited bookings
                            $change_in_lockout = 0;
                        }
                    } else {
                        // if not found, check if there's an inactive date record present
                        $existing_lockout = "SELECT total_booking FROM `" . $wpdb->prefix . "booking_history`
                                                                                WHERE post_id = %d
                                                                                AND start_date = %s
                                                                                AND weekday = ''
                                                                                AND status <> ''";
                        $lockout_results = $wpdb->get_results( $wpdb->prepare( $existing_lockout, $product_id, $specific_date ) );
            
                        if ( isset( $lockout_results ) && count( $lockout_results ) > 0 ) {
                            $insert = false;
                            if ( is_numeric( $specific_lockout ) && $specific_lockout > 0 ) {
                                $change_in_lockout = $specific_lockout - $lockout_results[ 0 ]->total_booking;
                            } else if ( $specific_lockout === '' || $specific_lockout == 0 ) { // unlimited bookings
                                $change_in_lockout = 0;
                            }
                        }
            
                    }
            
                    if ( $insert ) {
                        $query_insert  =   "INSERT INTO `" . $wpdb->prefix . "booking_history`
                                            (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                                            VALUES (
                                            '" . $product_id . "',
                                            '',
                                            '" . $specific_date . "',
                                            '0000-00-00',
                                            '',
                                            '',
                                            '" . $specific_lockout . "',
                                            '" . $available_booking . "' )";
                        $wpdb->query( $query_insert );
                    } else if ( isset( $change_in_lockout ) && is_numeric( $change_in_lockout ) ) {
                        
                        // Update the existing record so that lockout is managed and orders do not go missing frm the View bookings page
                        if ( $change_in_lockout == 0 && ( $specific_lockout === '' || $specific_lockout == 0 ) ) { // unlimited bookings
                            
                            $query_update  =   "UPDATE `".$wpdb->prefix."booking_history`
                                                SET total_booking = '" . $specific_lockout . "',
                                                available_booking = '" . $change_in_lockout . "',
                                                status = ''
                                                WHERE post_id = '" . $product_id . "'
                                                AND start_date = '" . $specific_date . "'";
                            
                        } else {
                            $query_update  =   "UPDATE `".$wpdb->prefix."booking_history`
                                                SET total_booking = '" . $specific_lockout . "',
                                                available_booking = available_booking + '" . $change_in_lockout . "',
                                                status = ''
                                                WHERE post_id = '" . $product_id . "'
                                                AND start_date = '" . $specific_date . "'";
                        }
                        $wpdb->query( $query_update );
                    }
            
            
                }
            }
    
        }
    }
    

    /**
     *
     * @param int $product_id
     * @param array $booking_options
     * @since 4.0.0
     */
    function update_bkap_history_date_time( $product_id, $settings_data ) {
    
        if ( count( $settings_data ) > 0 ) {
    
            global $wpdb;
    
            $booking_time_settings = $settings_data[ '_bkap_time_settings' ];
    
            // recurring days and lockout update
            if ( is_array ( $booking_time_settings ) && count( $booking_time_settings ) > 0 ) {
    
                foreach( $booking_time_settings as $day => $s_data ) {
    
                    if ( 'booking' == substr( $day, 0, 7 ) ) { // recurring weekdays
                         
                        foreach ( $s_data as $time_data ) {
    
                            $insert = true;
                            $available_booking = $time_data[ 'lockout_slot' ];
                            $updated_lockout = $time_data[ 'lockout_slot' ];
    
                            $from_time = $time_data[ 'from_slot_hrs' ] . ':' . $time_data[ 'from_slot_min' ];
                            $to_time = $time_data[ 'to_slot_hrs' ] . ':' . $time_data[ 'to_slot_min' ];
    
                            if ( $to_time == '0:00' ) {
                                $to_time = '';
                            }
                            
                            $from_db = date( 'H:i', strtotime( $from_time ) );
                            $to_db = date( 'H:i', strtotime( $to_time ) );
                            
                            $from_gi = date( 'G:i', strtotime( $from_time ) );
                            $to_gi = date( 'G:i', strtotime( $to_time ) );
                            
                            // check if the weekday is already present
                            // Duplicate records were being inserted when openended timeslot becasue DATE_TIME of blank returns no records.
                // Hence in below if, we are not comparing with DATE_TIME function.
                 
                            if ( $to_time == '' ) {
                                $check_weekday_query = "SELECT total_booking, available_booking FROM `" . $wpdb->prefix . "booking_history`
                                                        WHERE post_id = %d
                                                        AND weekday = %s
                                                        AND start_date = '0000-00-00'
                                                        AND TIME_FORMAT( from_time, '%H:%i' ) = %s
                                                        AND to_time = %s
                                                        AND status = ''";
        
                                $check_weekday = $wpdb->get_results( $wpdb->prepare( $check_weekday_query, $product_id, $day, $from_db, $to_time ) );
                            } else {
                                $check_weekday_query = "SELECT total_booking, available_booking FROM `" . $wpdb->prefix . "booking_history`
                                                        WHERE post_id = %d
                                                        AND weekday = %s
                                                        AND start_date = '0000-00-00'
                                                        AND TIME_FORMAT( from_time, '%H:%i' ) = %s
                                                        AND TIME_FORMAT( to_time, '%H:%i' ) = %s
                                                        AND status = ''";
        
                                $check_weekday = $wpdb->get_results( $wpdb->prepare( $check_weekday_query, $product_id, $day, $from_db, $to_db ) );                                
                            }
                            
    
                            // if yes, then update the lockout
                            if ( isset( $check_weekday ) && count( $check_weekday ) > 0 ) { // there will be only 1 active record at any given time
                                $insert            =   false;
                                if ( is_numeric( $time_data[ 'lockout_slot' ] ) && $time_data[ 'lockout_slot' ] > 0 ) {
                                    $change_in_lockout =   $time_data[ 'lockout_slot' ] - $check_weekday[ 0 ]->total_booking;
                                } else if ( $time_data[ 'lockout_slot' ] === '' || $time_data[ 'lockout_slot' ] == 0 ) { // unlimited bookings
                                    $change_in_lockout = 0;
                                }
                                
                            } else {
                                // if not found, check if there's a date record present
                                $existing_lockout = "SELECT total_booking FROM `" . $wpdb->prefix . "booking_history`
                                                                                WHERE post_id = %d
                                                                                AND start_date != '0000-00-00'
                                                                                AND weekday = %s
                                                                                AND TIME_FORMAT( from_time, '%H:%i' ) = %s
                                                                                AND TIME_FORMAT( to_time, '%H:%i' ) = %s
                                                                                ORDER BY id DESC LIMIT 1";
                                $lockout_results = $wpdb->get_results( $wpdb->prepare( $existing_lockout, $product_id, $day, $from_db, $to_db ) );
    
                                if ( isset( $lockout_results ) && count( $lockout_results ) > 0 ) {

                                    if ( is_numeric( $time_data[ 'lockout_slot' ] ) && $time_data[ 'lockout_slot' ] > 0 ) {
                                        $change_in_lockout =   $time_data[ 'lockout_slot' ] - $lockout_results[ 0 ]->total_booking;
                                        $available_booking = $lockout_results[ 0 ]->total_booking + $change_in_lockout;
                                    } else if ( $time_data[ 'lockout_slot' ] === '' || $time_data[ 'lockout_slot' ] == 0 ) { // unlimited bookings
                                        $change_in_lockout = 0;
                                        $available_booking = 0;
                                    }
                                }
    
                            }
    
                            if ( $insert ) {
                                
                                $current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
                                
                                $query_insert    =  "INSERT INTO `" . $wpdb->prefix . "booking_history`
                                                (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                                                VALUES (
                                                '" . $product_id . "',
                                                '" . $day . "',
                                                '0000-00-00',
                                                '0000-00-00',
                                                '" . $from_time . "',
                                                '" . $to_time . "',
                                                '" . $updated_lockout . "',
                                                '" . $available_booking . "' )";
                                $wpdb->query( $query_insert );
                                
                                // if there are other time slots present for the weekday, add this slot for the date
                                $fetch_dates = "SELECT DISTINCT( start_date ) FROM `" . $wpdb->prefix . "booking_history`
                                                WHERE start_date >= %s
                                                AND post_id = %d
                                                AND weekday = %s";
                                
                                $dates_set = $wpdb->get_col( $wpdb->prepare( $fetch_dates, $current_date, $product_id, $day ) );
                                
                                if( is_array( $dates_set ) && count( $dates_set ) > 0 ) {
                                    
                                    // build an array of dates that already have this slot present
                                    $fetch_dates_present = "SELECT DISTINCT( start_date ) FROM `" . $wpdb->prefix . "booking_history`
                                                WHERE start_date >= %s
                                                AND post_id = %d
                                                AND weekday = %s
                                                AND TIME_FORMAT( from_time, '%H:%i' ) = %s
                                                AND TIME_FORMAT( to_time, '%H:%i' ) = %s";
                                    
                                    $dates_present = $wpdb->get_col( $wpdb->prepare( $fetch_dates_present, $current_date, $product_id, $day, $from_db, $to_db ) );
                                    
                                    foreach( $dates_set as $date ) {
                                        // In a scenario where a future date is locked out, as all the time slot bookings are full, 
                                        // we need to run this insert to ensure the date is unblocked and bookings can be taken for the new slot
                                        if( ! in_array( $date, $dates_present ) ) {

                                            if( $to_time == '' ){
                                                $to_gi = '';
                                            }

                                            $query_insert    =  "INSERT INTO `" . $wpdb->prefix . "booking_history`
                                                    (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                                                    VALUES (
                                                    '" . $product_id . "',
                                                    '" . $day . "',
                                                    '" . $date . "',
                                                    '0000-00-00',
                                                    '" . $from_gi . "',
                                                    '" . $to_gi . "',
                                                    '" . $updated_lockout . "',
                                                    '" . $available_booking . "' )";
                                    
                                            $wpdb->query( $query_insert );
                                        }
                                    }
                                }
                            } else if ( isset( $change_in_lockout ) && is_numeric( $change_in_lockout ) ) {
                                
                                // Update the existing record so that lockout is managed and orders do not go missing frm the View bookings page
                                if ( $change_in_lockout == 0 && ( $time_data[ 'lockout_slot' ] === '' || $time_data[ 'lockout_slot' ] == 0 ) ) { // unlimited bookings
                                    
                                    $query_update    =  "UPDATE `" . $wpdb->prefix . "booking_history`
                                                SET total_booking = '" . $updated_lockout . "',
                                                available_booking = '" . $change_in_lockout . "'
                                                WHERE post_id = '" . $product_id . "'
                                                AND weekday = '" . $day . "'
                                                AND start_date = '0000-00-00'
                                                AND TIME_FORMAT( from_time, '%H:%i' ) = '" . $from_db . "'
                                                AND TIME_FORMAT( to_time, '%H:%i' ) = '" . $to_db . "'
                                                AND status = ''";
                                    
                                } else {
                                       if($to_time == '' ){
                                           
                                            $query_update    =  "UPDATE `" . $wpdb->prefix . "booking_history`
                                            SET total_booking = '" . $updated_lockout . "',
                                            available_booking = available_booking + '" . $change_in_lockout . "'
                                            WHERE post_id = '" . $product_id . "'
                                            AND weekday = '" . $day . "'
                                            AND start_date = '0000-00-00'
                                            AND TIME_FORMAT( from_time, '%H:%i' ) = '" . $from_db . "'
                                            AND to_time = ''
                                            AND status = ''";
                                           
                                           
                                       }else {
                                            $query_update    =  "UPDATE `" . $wpdb->prefix . "booking_history`
                                            SET total_booking = '" . $updated_lockout . "',
                                            available_booking = available_booking + '" . $change_in_lockout . "'
                                            WHERE post_id = '" . $product_id . "'
                                            AND weekday = '" . $day . "'
                                            AND start_date = '0000-00-00'
                                            AND TIME_FORMAT( from_time, '%H:%i' ) = '" . $from_db . "'
                                            AND TIME_FORMAT( to_time, '%H:%i' ) = '" . $to_db . "'
                                            AND status = ''";
                                     
                                       }
                                 }
                                $wpdb->query( $query_update );
                                
                            }
    
                            if ( isset( $change_in_lockout ) && is_numeric( $change_in_lockout ) ) {
                                
                                //Update the existing records for the dates
                                if ( $change_in_lockout == 0 && ( $time_data[ 'lockout_slot' ] === '' || $time_data[ 'lockout_slot' ] == 0 ) ) { // unlimited bookings
                                
                                    $query_update        =   "UPDATE `" . $wpdb->prefix . "booking_history`
                                                    SET total_booking = '" . $updated_lockout . "',
                                                    available_booking = '" . $change_in_lockout . "',
                                                    status = ''
                                                    WHERE post_id = '" . $product_id . "'
                                                    AND weekday = '" . $day . "'
                                                    AND start_date <> '0000-00-00'
                                                    AND TIME_FORMAT( from_time, '%H:%i' ) = '" . $from_db . "'
                                                    AND TIME_FORMAT( to_time, '%H:%i' ) = '" . $to_db . "'";
                                } else {
                                    
                                    if($to_time == ''){
                                        $query_update        =   "UPDATE `" . $wpdb->prefix . "booking_history`
                                        SET total_booking = '" . $updated_lockout . "',
                                        available_booking = available_booking + '" . $change_in_lockout . "',
                                        status = ''
                                        WHERE post_id = '" . $product_id . "'
                                        AND weekday = '" . $day . "'
                                        AND start_date <> '0000-00-00'
                                        AND TIME_FORMAT( from_time, '%H:%i' ) = '" . $from_db . "'
                                        AND to_time = ''";
                                        
                                    } else {
                                        $query_update        =   "UPDATE `" . $wpdb->prefix . "booking_history`
                                        SET total_booking = '" . $updated_lockout . "',
                                        available_booking = available_booking + '" . $change_in_lockout . "',
                                        status = ''
                                        WHERE post_id = '" . $product_id . "'
                                        AND weekday = '" . $day . "'
                                        AND start_date <> '0000-00-00'
                                        AND TIME_FORMAT( from_time, '%H:%i' ) = '" . $from_db . "'
                                        AND TIME_FORMAT( to_time, '%H:%i' ) = '" . $to_db . "'";
                                        
                                    }
                                    
                                }
                                $wpdb->query( $query_update );
                            }
    
                        }
                    } else { // specific dates

                        $date = date( 'Y-m-d', strtotime( $day ) );
                        foreach ( $s_data as $time_data ) {
    
                            $insert = true;
                            $available_booking = $time_data[ 'lockout_slot' ];
                            $updated_lockout = $time_data[ 'lockout_slot' ];
    
                            $from_time = $time_data[ 'from_slot_hrs' ] . ':' . $time_data[ 'from_slot_min' ] ;
                            $to_time = $time_data[ 'to_slot_hrs' ] . ':' . $time_data[ 'to_slot_min' ];
    
                            if ( $to_time == '0:00' ) {
                                $to_time = '';
                            }
    
                            // check if the date is already present
                            $check_date_query = "SELECT total_booking, available_booking FROM `" . $wpdb->prefix . "booking_history`
                                                WHERE post_id = %d
                                                AND weekday = ''
                                                AND start_date = %s
                                                AND from_time = %s
                                                AND to_time = %s
                                                AND status = ''";
    
                            $check_date = $wpdb->get_results( $wpdb->prepare( $check_date_query, $product_id, $date, $from_time, $to_time ) );
    
                            // if yes, then update the lockout
                            if ( isset( $check_date ) && count( $check_date ) > 0 ) { // there will be only 1 active record at any given time
                                $insert            =   false;
                                if ( is_numeric( $time_data[ 'lockout_slot' ] ) && $time_data[ 'lockout_slot' ] > 0 ) {
                                    $change_in_lockout =   $time_data[ 'lockout_slot' ] - $check_date[ 0 ]->total_booking;
                                } else if ( $time_data[ 'lockout_slot' ] === '' || $time_data[ 'lockout_slot' ] == 0 ) { // unlimited bookings
                                    $change_in_lockout = 0;
                                }
                                
                            } else {
                                // if not found, check if there's an inactive date record present
                                $existing_lockout = "SELECT total_booking FROM `" . $wpdb->prefix . "booking_history`
                                                                                WHERE post_id = %d
                                                                                AND start_date = %s
                                                                                AND weekday = ''
                                                                                AND from_time = %s
                                                                                AND to_time = %s
                                                                                AND status <> ''";
                                $lockout_results = $wpdb->get_results( $wpdb->prepare( $existing_lockout, $product_id, $date, $from_time, $to_time ) );
    
                                if ( isset( $lockout_results ) && count( $lockout_results ) > 0 ) {
                                    $insert = false;
                                    if ( is_numeric( $time_data[ 'lockout_slot' ] ) && $time_data[ 'lockout_slot' ] > 0 ) {
                                        $change_in_lockout = $time_data[ 'lockout_slot' ] - $lockout_results[ 0 ]->total_booking;
                                    } else if ( $time_data[ 'lockout_slot' ] === '' || $time_data[ 'lockout_slot' ] == 0 ) { // unlimited bookings
                                        $change_in_lockout = 0;
                                    }
                                }
    
                            }
    
                            if ( $insert ) {
                                $query_insert  =   "INSERT INTO `" . $wpdb->prefix . "booking_history`
                                            (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                                            VALUES (
                                            '" . $product_id . "',
                                            '',
                                            '" . $date . "',
                                            '0000-00-00',
                                            '" . $from_time . "',
                                            '" . $to_time . "',
                                            '" . $time_data[ 'lockout_slot' ] . "',
                                            '" . $available_booking . "' )";
                                $wpdb->query( $query_insert );
                            } else {
                                
                                // Update the existing record so that lockout is managed and orders do not go missing frm the View bookings page
                                if ( $change_in_lockout == 0 && ( $time_data[ 'lockout_slot' ] === '' || $time_data[ 'lockout_slot' ] == 0 ) ) { // unlimited bookings
                                    $query_update  =   "UPDATE `".$wpdb->prefix."booking_history`
                                                SET total_booking = '" . $time_data[ 'lockout_slot' ] . "',
                                                available_booking = '" . $change_in_lockout . "',
                                                status = ''
                                                WHERE post_id = '" . $product_id . "'
                                                AND start_date = '" . $date . "'
                                                AND from_time = '" . $from_time . "'
                                                AND to_time = '" . $to_time . "'";
                                } else {
                                    $query_update  =   "UPDATE `".$wpdb->prefix."booking_history`
                                            SET total_booking = '" . $time_data[ 'lockout_slot' ] . "',
                                            available_booking = available_booking + '" . $change_in_lockout . "',
                                            status = ''
                                            WHERE post_id = '" . $product_id . "'
                                            AND start_date = '" . $date . "'
                                            AND from_time = '" . $from_time . "'
                                            AND to_time = '" . $to_time . "'";
                                }
                                
                                $wpdb->query( $query_update );
                            }
                        }
    
                    }
                }
            }
        }
    }

    /**
     * Deletes a record from the Specific Dates
     * Table in the Availability settings
     * Called via ajax
     * @since 4.0.0
     */
    function bkap_delete_specific_range() {
    
        $product_id = $_POST[ 'product_id' ];
        $record_type = $_POST[ 'record_type' ];
        $start = $_POST[ 'start' ];
        $end = $_POST[ 'end' ];
    
        $booking_box_class = new bkap_booking_box_class();
        $booking_box_class->delete_ranges( $product_id, $record_type, $start, $end );
    
        die();
    }
    
    /**
     *
     * @param int $product_id
     * @param string $record_type
     * @param string $start
     * @param string $end
     */
    function delete_ranges( $product_id, $record_type, $start, $end ) {
    
        if ( '' != $record_type ) {
            switch( $record_type ) {
    
                case 'custom_range':
                    $custom_ranges = get_post_meta( $product_id, '_bkap_custom_ranges', true );
    
                    // get the key for the range
                    $delete_key = $this->get_range_key( $custom_ranges, $start, $end );
                    if ( is_numeric( $delete_key ) ) {
                        $this->delete_serialized_range( $product_id, 'booking_date_range', $delete_key );
                        $this->delete_single_range( $product_id, '_bkap_custom_ranges', $delete_key );
                    }
                    break;
                case 'range_of_months':
                    global $bkap_months;
    
                    $current_year = date( 'Y', current_time( 'timestamp' ) );
                    $next_year = date( 'Y', strtotime( '+1 year' ) );
                    
                    $month_range = get_post_meta( $product_id, '_bkap_month_ranges', true );
    
                    if ( is_numeric( $start ) ) { // it's a month number
                        $month_name = $bkap_months[ $start ];
                        $month_to_use = "$month_name $current_year";
                        $range_start = date ( 'j-n-Y', strtotime( $month_to_use ) );
                    }
    
                    if ( is_numeric( $end ) ) { // it's a month number
                        $month_name = $bkap_months[ $end ];
                        if ( $start < $end ) {
                            $month_to_use = "$month_name $current_year";
                        } else {
                            $month_to_use = "$month_name $next_year";
                        }
                        $month_start = date ( 'j-n-Y', strtotime( $month_to_use ) );
    
                        $days = date( 't', strtotime( $month_start ) );
                        $days -= 1;
                        $range_end = date ( 'j-n-Y', strtotime( "+$days days", strtotime( $month_start ) ) );
                    }
    
                    // get the key for the range
                    $delete_key = $this->get_range_key( $month_range, $range_start, $range_end );
    
                    if ( is_numeric ) {
                        $this->delete_single_range( $product_id, '_bkap_month_ranges', $delete_key );
                    }
                    break;
                case 'specific_dates':
                    // remove the record from serial data
                    $this->delete_serialized_range( $product_id, 'booking_specific_date', $start );
                    // remove from individual data
                    $this->delete_single_range( $product_id, '_bkap_specific_dates', $start );
                    // update booking history
                    $this->delete_specific_date( $product_id, $start );
                    // update the special prices data
                    $this->delete_special_price( $product_id, $start );
                    break;
                case 'holidays':
                    // remove the record from serial data
                    $this->delete_serialized_range( $product_id, 'booking_product_holiday', $start );
                    // remove from individual data
                    $this->delete_single_range( $product_id, '_bkap_product_holidays', $start );
                    break;
                case 'holiday_range':
                    $holiday_range = get_post_meta( $product_id, '_bkap_holiday_ranges', true );
    
                    // get the key for the range
                    $delete_key = $this->get_range_key( $holiday_range, $start, $end );
    
                    if ( is_numeric ) {
                        $this->delete_single_range( $product_id, '_bkap_holiday_ranges', $delete_key );
                    }
                default:
                    break;
            }
        }
    
    }
    
    /**
     * Returns the array key from a given array if a match
     * is found.
     * 
     * @param array $range - array to search
     * @param string $start - start date (j-n-Y)
     * @param string $end - end date (j-n-Y)
     * @return int $key - array key
     * @since 4.0.0
     */
    function get_range_key( $range, $start, $end ) {
    
        $delete_key = '';
        if ( is_array( $range ) && count( $range ) > 0 ) {
            foreach( $range as $range_key => $range_value ) {
                $r_start = $range_value[ 'start' ];
                $r_end = $range_value[ 'end' ];
    
                if ( $r_start == $start && $r_end == $end ) {
                    $delete_key = $range_key;
                    break;
                }
            }
    
        }
    
        return $delete_key;
    
    }
    
    /**
     * Deletes a given array record from the 
     * individual booking settings.
     * 
     * @param unknown $key
     * @param unknown $range
     * @since 4.0.0
     */
    function delete_single_range( $product_id, $range_name, $key ) {
    
        $range_data = get_post_meta( $product_id, $range_name, true );
    
        if ( array_key_exists( $key, $range_data ) ) {
            unset( $range_data[ $key ] );
        }
    
        update_post_meta( $product_id, $range_name, $range_data );
    }
    
    /**
     * Deelets a record from a given array in 
     * the serialized booking settings. 
     *
     * @param int $product_id
     * @param string $name - contains the array key name
     * @param int $key
     * @since 4.0.0
     */
    function delete_serialized_range( $product_id, $name, $key ) {
    
        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
    
        $record_data = $booking_settings[ $name ];
    
        if ( array_key_exists( $key, $record_data ) ) {
            unset( $record_data[ $key ] );
        }
    
        $booking_settings[ $name ] = $record_data;
    
        update_post_meta( $product_id, 'woocommerce_booking_settings', $booking_settings );
    }
    
    /**
     * Updates a specific date record to inactive
     * status in booking history table for a given
     * date. 
     * 
     * @param unknown $product_id
     * @param unknown $date
     * @since 4.0.0
     */
    function delete_specific_date( $product_id, $date ) {
    
        global $wpdb;
    
        $specific_date = date ( 'Y-m-d', strtotime( $date ) );
    
        $update_specific = "UPDATE `" . $wpdb->prefix . "booking_history`
                            SET status = 'inactive'
                            WHERE post_id = '" . $product_id . "'
                            AND start_date = '" . $specific_date . "'
                            AND weekday = ''
                            AND from_time = ''
                            AND to_time = ''";
    
        $wpdb->query( $update_specific );
    }
    
    /**
     * Deletes the special price record
     * from post meta for a given specific
     * date.
     * 
     * @param int $product_id
     * @param string $date
     */
    function delete_special_price( $product_id, $date ) {

        $date = date( 'Y-m-d', strtotime( $date ) );
        
        $special_prices = get_post_meta( $product_id, '_bkap_special_price', true );
    
        if ( is_array( $special_prices ) && count( $special_prices ) > 0 ) {
    
            $updated_special_prices = array();
            foreach( $special_prices as $s_key => $s_price ) {
    
                if ( $s_price[ 'booking_special_date' ] != $date ) {
                    $updated_special_prices[ $s_key ] = $s_price;
                }
            }
    
            update_post_meta( $product_id, '_bkap_special_price', $updated_special_prices );
        }
    }
    
    /**
     * Deletes the Date/Day and Time Slot from the
     * Date & Time table in the Availability settings
     * Called via ajax
     * @since 4.0.0
     */
    static function bkap_delete_date_time() {
    
        $product_id = $_POST[ 'product_id' ];
        $day = $_POST[ 'day' ]; // this will be an array
        $from_time = $_POST[ 'from_time' ];
        $to_time = $_POST[ 'to_time' ];
    
        $booking_box_class = new bkap_booking_box_class();
        if ( is_array( $day ) && count( $day ) > 0 ) {
            foreach( $day as $day_value ) {
                // update post meta serialized
                $booking_box_class->delete_serialized_time_settings( $product_id, $day_value, $from_time, $to_time );
                // update post meta individual
                $booking_box_class->delete_individual_time_settings( $product_id, $day_value, $from_time, $to_time );
                // update booking history
                $booking_box_class->delete_booking_history( $product_id, $day_value, $from_time, $to_time );
            }
        }
        die();
    }
    
    /**
     * Deletes the time slot from the serialized
     * post meta record
     * @param int $product_id
     * @param string $day_value Weekday/Date
     * @param string $from_time
     * @param string $to_time
     * @since 4.0.0
     */
    function delete_serialized_time_settings( $product_id, $day_value, $from_time, $to_time ) {
    
        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
    
        $existing_settings = $booking_settings[ 'booking_time_settings' ];
    
        $updated_time_settings = $this->unset_time_array( $existing_settings, $day_value, $from_time, $to_time );
    
        $booking_settings[ 'booking_time_settings' ] = $updated_time_settings;
    
        update_post_meta( $product_id, 'woocommerce_booking_settings', $booking_settings );
    }
    
    /**
     *
     * @param int $product_id
     * @param string $day_value
     * @param string $from_time
     * @param string $to_time
     * @since 4.0.0
     */
    function delete_individual_time_settings( $product_id, $day_value, $from_time, $to_time ) {
    
        $existing_settings = get_post_meta( $product_id, '_bkap_time_settings', true );
    
        $updated_time_settings = $this->unset_time_array( $existing_settings, $day_value, $from_time, $to_time );
    
        update_post_meta( $product_id, '_bkap_time_settings', $updated_time_settings );
    
    }
    
    /**
     *
     * @param array $existing_settings
     * @param string $day_value (can be aweekday or a date)
     * @param string $from_time
     * @param string $to_time
     * @return array $existing_settings
     * @since 4.0.0
     */
    function unset_time_array( $existing_settings, $day_value, $from_time, $to_time ) {
    
        //split the time into hrs and mins
        $from_time_array = explode( ':', $from_time );
        $from_hrs = $from_time_array[ 0 ];
        $from_mins = $from_time_array[ 1 ];
    
        $to_hrs = '0';
        $to_mins = '00';
        if ( isset( $to_time ) && '' != $to_time ) {
            $to_time_array = explode( ':', $to_time );
            $to_hrs = $to_time_array[ 0 ];
            $to_mins = $to_time_array[ 1 ];
        }
    
        if ( is_array( $existing_settings ) && count( $existing_settings ) > 0 ) {
    
            foreach( $existing_settings as $day => $day_settings ) {
    
                if ( $day == $day_value ) { // matching day/date
    
                    foreach( $day_settings as $time_key => $time_settings ) {
    
                        // Match the time
                        if ( trim( $from_hrs ) == $time_settings[ 'from_slot_hrs' ] && trim( $from_mins ) == $time_settings[ 'from_slot_min' ] && trim( $to_hrs ) == $time_settings[ 'to_slot_hrs' ] && trim( $to_mins ) == $time_settings[ 'to_slot_min' ] ) {
                            $unset_key = $time_key;
                            break;
                        }
    
                    }
                    // unset the array
                    if ( isset( $unset_key ) && is_numeric( $unset_key ) ) {
                        unset( $existing_settings[ $day ][ $unset_key ] );
                        break;
                    }
                }
            }
        }
    
        return $existing_settings;
    }
    
    /**
     *
     * @param int $product_id
     * @param string $day_value (can be a date or a weekday)
     * @param string $from_time (24 hr format)
     * @param string $to_time (24 hr format)
     * @since 4.0.0
     */
    function delete_booking_history( $product_id, $day_value, $from_time = '', $to_time = '' ) {
    
        global $wpdb;
        
        $to_hrs = '';
        $to_mins = '';
        
        if ( isset( $to_time ) && '' != $to_time ) {
            $to_time_array = explode( ':', $to_time );
            $to_hrs = $to_time_array[ 0 ];
            $to_mins = $to_time_array[ 1 ];
        }
        
        if ( $to_hrs == 0 && $to_mins == 0 ){
            $to_time = '';
        }
    
        if ( isset( $day_value ) && substr( $day_value, 0, 7 ) == 'booking' ) { // recurring weekday
    
            // delete the base record
            $delete_base = "DELETE FROM `" . $wpdb->prefix . "booking_history`
                            WHERE post_id = '" . $product_id . "'
                            AND weekday = '" . $day_value . "'
                            AND start_date = '0000-00-00'
                            AND from_time = '" . $from_time . "'
                            AND to_time = '" . $to_time . "'";
            $wpdb->query( $delete_base );
    
            // set all date records to inactive
            $from_db = date( 'H:i', strtotime( $from_time ) );
            $to_db = date( 'H:i', strtotime( $to_time ) );
            
            if ( $to_time == '' ) {
                $update_date_status = "UPDATE `" . $wpdb->prefix . "booking_history`
                                    SET status = 'inactive'
                                    WHERE post_id = '" . $product_id . "'
                                    AND weekday = '" . $day_value . "'
                                    AND start_date <> '0000-00-00'
                                    AND TIME_FORMAT( from_time, '%H:%i' ) = '" . $from_db . "'
                                    AND to_time = '" . $to_time . "'";

            } else{
                $update_date_status = "UPDATE `" . $wpdb->prefix . "booking_history`
                                    SET status = 'inactive'
                                    WHERE post_id = '" . $product_id . "'
                                    AND weekday = '" . $day_value . "'
                                    AND start_date <> '0000-00-00'
                                    AND TIME_FORMAT( from_time, '%H:%i' ) = '" . $from_db . "'
                                    AND TIME_FORMAT( to_time, '%H:%i' ) = '" . $to_db . "'";
            }
            $wpdb->query( $update_date_status );
    
        } else if ( isset( $day_value ) && '' != $day_value ) { // specific date
    
            $date = date( 'Y-m-d', strtotime( $day_value ) );
            // set the date record to inactive
            $update_date_query = "UPDATE `" . $wpdb->prefix . "booking_history`
                                SET status = 'inactive'
                                WHERE post_id = '" . $product_id . "'
                                AND start_date = '" . $date . "'
                                AND from_time = '" . $from_time . "'
                                AND to_time = '" . $to_time . "'";
    
            $wpdb->query( $update_date_query );
        }
    }
}// end of class    
?>