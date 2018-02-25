<?php 
class product_gcal_settings {

    public function __construct() {
        // add the GCal tab in the Booking meta box
        add_action( 'bkap_add_tabs', array( &$this, 'gcal_tab' ), 11, 1 );
        // add collapse menus tab
        add_action( 'bkap_add_tabs', array( &$this, 'collapse_tab' ), 50, 1 );
        // add fields in the GCal tab in the Booking meta box
        add_action( 'bkap_after_listing_enabled', array( &$this, 'bkap_gcal_show_field_settings' ), 11, 1 );
        // Save the product settings for variable blocks
//        add_filter( 'bkap_save_product_settings', array( &$this, 'bkap_gcal_product_settings_save' ), 11, 2 );
        
    }
    
    function gcal_tab( $product_id ) {        
        ?>
		<li><a id="gcal_sync_settings" class="bkap_tab"><i class="fa fa-refresh" aria-hidden="true"></i><?php _e( 'Google Calendar Sync', 'woocommerce-booking' ); ?> </a></li>
		<?php
    }
    
    /**
     * This function adds a Collapse tabs menu 
     * to the Booking menu on the admin product page.
     * @since 4.0
     */
    function collapse_tab( $product_id ) {
    ?>
        <span id="bkap_collapse"><span class="dashicons dashicons-admin-collapse" style="margin-right: 5px;"></span><?php _e( 'Collapse Tabs', 'woocommerce-booking' ); ?></span>
        <?php 
    }
    function bkap_gcal_show_field_settings( $product_id ) {
        
        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
        
    	$user_id = get_current_user_id();

    	$_product = wc_get_product( $product_id );
    	 
    	$gcal_disabled = '';
    	$gcal_msg = "none";
    	if( 'grouped' === $_product->get_type() ) {
    	    $gcal_disabled = 'disabled';
    	    $gcal_msg = "block";
    	}
    	$post_type = get_post_type( $product_id );
        ?>
        
        <script type="text/javascript">
				// radio button status when the page loads
				jQuery( document ).ready( function() {
	                var isChecked = jQuery( "#product_sync_integration_mode:checked" ).val();
	                if( isChecked == "directly" ) {
                        // enable the fields
                        jQuery( "#product_sync_key_file_name" ).prop( "disabled", false );
                        jQuery( "#product_sync_service_acc_email_addr" ).prop( "disabled", false );
                        jQuery( "#product_sync_calendar_id" ).prop( "disabled", false );
                    } else if ( isChecked == "disabled" ) {
                    	// disable the fields
                        jQuery( "#product_sync_key_file_name" ).prop( "disabled", true );
                        jQuery( "#product_sync_service_acc_email_addr" ).prop( "disabled", true );
                        jQuery( "#product_sync_calendar_id" ).prop( "disabled", true );
                    }

	                // if its a variable product
	                if ( jQuery( '#enable_automated_mapping' ).length > 0 ) {
	                	var isChecked = jQuery( "#enable_automated_mapping:checked" ).val();

	                	if ( isChecked == 'on' ) {
	                		jQuery( '#gcal_default_variation' ).prop( 'disabled', false );
	                	} else {
	                		jQuery( '#gcal_default_variation' ).prop( 'disabled', true );
	                	}
	                }
		             // on change routine for radio button
					jQuery( "input[type=radio][id=product_sync_integration_mode]" ).change( function() {
	                    var isChecked = jQuery( this ).val();
	                    if( isChecked == "directly" ) {
	                        // enable the fields
	                        jQuery( "#product_sync_key_file_name" ).prop( "disabled", false );
	                        jQuery( "#product_sync_service_acc_email_addr" ).prop( "disabled", false );
	                        jQuery( "#product_sync_calendar_id" ).prop( "disabled", false );
	                    } else if ( isChecked == "disabled" ) {
	                    	// disable the fields
	                        jQuery( "#product_sync_key_file_name" ).prop( "disabled", true );
	                        jQuery( "#product_sync_service_acc_email_addr" ).prop( "disabled", true );
	                        jQuery( "#product_sync_calendar_id" ).prop( "disabled", true );
	                    }
					});
				    
			    });

				jQuery( document ).on( 'click', '#test_connection', function( e ) {
                    e.preventDefault();
                    var data = {
                        gcal_api_test_result: '',
                        gcal_api_pre_test: '',
                        gcal_api_test: 1,
                        user_id: <?php echo $user_id; ?>,
                        product_id: <?php echo $product_id; ?>,
                        action: 'display_nag'
                    };
                    jQuery( '#test_connection_ajax_loader' ).show();
                    jQuery.post( '<?php echo get_admin_url(); ?>/admin-ajax.php', data, function( response ) {
                        jQuery( '#test_connection_message' ).html( response );
                        jQuery( '#test_connection_ajax_loader' ).hide();
                    });
            
            
            });

				// add new ics feed
				jQuery( document ).on( 'click', '#add_new_ics_feed', function () {
                    var rowCount = parseInt( jQuery( '#product_ics_feed_list tr:last' ).attr( 'id' ) )  + 1;
                    if ( isNaN( parseInt( rowCount ) ) ) {
                        rowCount = 0;
                    }
                    jQuery( "#product_ics_feed_list" ).append( "<tr id='" + rowCount + "'><th></th><td class='ics_feed_url'><input type='text' id='product_ics_fee_url_" + rowCount + "' name='product_ics_fee_url_" + rowCount + "' size='40' value=''></td></tr>" );				
				});

				// delete an existing feed
				jQuery( document ).on( 'click', 'input[type=\'button\'][name=\'delete_ics_feed\']', function () {
					console.log( 'clicked' );
					var key = jQuery( this ).attr( 'id' );
					console.log( key );
                    var data = {
                        ics_feed_key: key,
                        product_id: <?php echo $product_id; ?>,
                        action: 'bkap_delete_ics_url_feed'
                    };
                    jQuery.post( '<?php echo get_admin_url(); ?>/admin-ajax.php', data, function( response ) {
                        if( response == 'yes' ) {
                            jQuery( 'table#product_ics_feed_list tr#' + key ).remove();
                        } 
                    });
				});

				jQuery( document ).on( 'click', 'input[type=\'button\'][name=\'import_ics\']', function() {
                    jQuery( '#import_event_message' ).show();
                    var key = jQuery( this ).attr( 'id' );
                    var data = {
                        ics_feed_key: key,
                        product_id: <?php echo $product_id; ?>,
                        action: 'bkap_import_events'
                    };
                    jQuery.post( '<?php echo get_admin_url(); ?>/admin-ajax.php', data, function( response ) {
                        jQuery( '#import_event_message' ).hide();
                        jQuery( '#success_message' ).html( response );  
                        jQuery( '#success_message' ).fadeIn();
                        setTimeout( function() {
                            jQuery( '#success_message' ).fadeOut();
                        },3000 );
                    });
                });
                
//				if ( jQuery( '#enable_automated_mapping' ).length > 0 ) {
    	            jQuery( document ).on( 'click', '#enable_automated_mapping', function () {
    
    	            	if ( jQuery( '#enable_automated_mapping' ).attr( 'checked' ) ) {
    		            	console.log( 'if');
    		            	jQuery( '#gcal_default_variation' ).prop( 'disabled', false );
    	            	} else {
    		            	console.log( 'else');
    	            		jQuery( '#gcal_default_variation' ).prop( 'disabled', true );
    	            	}
    	            });
//	            }
	            </script>
				
		<div id="gcal_tab" style="display:none;">
        <h2> <strong><?php _e( "Export Bookings to Google Calendar" , "woocommerce-booking" ); ?>  </strong> </h2>
            <div id="bkap_gcal_msg" class="bkap-gcal-info" style="display:<?php echo $gcal_msg; ?>;" >
                <?php _e( 'Google Calendar Sync cannot be set up for a Grouped Product. Please set up the sync settings for individual child products.', 'woocommerce-booking' ); ?>
            </div>
            <fieldset id="bkap_gcal_fields" <?php echo $gcal_disabled; ?> >
    			<table class='form-table'>
    				<tr style="max-width:20%;">
                    <?php 
                    $sync_directly = '';
                    $sync_disable = 'checked';
                    
                    if ( isset( $booking_settings[ 'product_sync_integration_mode' ] ) && 'directly' == $booking_settings[ 'product_sync_integration_mode' ] ) {
                        $sync_directly = 'checked';
                        $sync_disable = '';
                    }
                    ?>
    				<th>
    				    <label for="product_sync_integration_mode"><?php _e( 'Integration Mode:', 'woocommerce-booking' ); ?></label>
    				</th>
    				<td>
    				    <input type="radio" name="product_sync_integration_mode" id="product_sync_integration_mode" value="directly" <?php echo $sync_directly; ?> /> <?php _e( 'Sync Automatically', 'woocommerce-booking' ); ?> &nbsp;&nbsp;
                        <input type="radio" name="product_sync_integration_mode" id="product_sync_integration_mode" value="disabled" <?php echo $sync_disable; ?> /> <?php _e( 'Disabled', 'woocommerce-booking' ); ?>
                            
    				</td>
    				<td>
    				    <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Select method of integration. Sync Automatically will add the booking events to the Google calendar, which is set in the Calendar to be used field, automatically when a customer places an order.Disabled will disable the integration with Google Calendar.Note: Import of the events will work manually using .ics link.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png"/>
    				</td>
    				</tr>
    				
    				<tr>
    				<?php 
    				$gcal_key_file = '';
                    if ( isset( $booking_settings[ 'product_sync_key_file_name' ] ) && '' != $booking_settings[ 'product_sync_key_file_name' ] ) {
                        $gcal_key_file = $booking_settings[ 'product_sync_key_file_name' ];
                    }
    				?>
    				<th>
    				    <label for="product_sync_key_file_name"><?php _e( 'Key File Name:', 'woocommerce-booking' ); ?></label>
    				</th>
    				<td>
    				    <input id="product_sync_key_file_name" name= "product_sync_key_file_name" value="<?php echo $gcal_key_file; ?>" size="40" type="text" />
                            
    				</td>
    				<td>
    				    <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Enter key file name here without extention, e.g. ab12345678901234567890-privatekey.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png"/>
    				</td>
    				</tr>
    				
    				<tr>
    				<?php 
    				$gcal_service_acc_email_addr = '';
                    if ( isset( $booking_settings[ 'product_sync_service_acc_email_addr' ] ) && $booking_settings[ 'product_sync_service_acc_email_addr' ] ) {
                        $gcal_service_acc_email_addr = $booking_settings[ 'product_sync_service_acc_email_addr' ];
                    }
    				?>
    				<th>
    				    <label for="product_sync_service_acc_email_addr"><?php _e( 'Service Account Email Address:', 'woocommerce-booking' ); ?></label>
    				</th>
    				<td>
    				    <input id="product_sync_service_acc_email_addr" name= "product_sync_service_acc_email_addr" value="<?php echo $gcal_service_acc_email_addr; ?>" size="40" type="text" />
                            
    				</td>
    				<td>
    				    <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Enter Service account email address here, e.g. 1234567890@developer.gserviceaccount.com.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png"/>
    				</td>
    				</tr>
    				
    				<tr>
    				<?php 
    				$gcal_service_calendar_addr = '';
                    if ( isset( $booking_settings[ 'product_sync_calendar_id' ] ) && '' != $booking_settings[ 'product_sync_calendar_id' ] ) {
                        $gcal_service_calendar_addr = $booking_settings[ 'product_sync_calendar_id' ];
                    }
    				?>
    				<th>
    				    <label for="product_sync_calendar_id"><?php _e( 'Calendar to be used:', 'woocommerce-booking' ); ?></label>
    				</th>
    				<td>
    				    <input id="product_sync_calendar_id" name= "product_sync_calendar_id" value="<?php echo $gcal_service_calendar_addr; ?>" size="40" type="text" />
                            
    				</td>
    				<td>
    				    <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Enter the ID of the calendar in which your bookings will be saved, e.g. abcdefg1234567890@group.calendar.google.com.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png"/>
    				</td>
    				</tr>
    				
    				<tr>
                    <th></th>
                    <td>
                    <a href='post.php?post=<?php echo $product_id; ?>&action=edit' id='test_connection'><?php _e( 'Test Connection', 'woocommerce-booking' ) ?></a>
                    <img src='<?php echo plugins_url(); ?>/woocommerce-booking/images/ajax-loader.gif' id='test_connection_ajax_loader'>
                    <div id='test_connection_message'></div>
                    </td>
                  
                    </tr>
                    <tr >
                        <th></th>
                        <td>
                            <?php _e( "You can follow the instructions given in <a href ='https://www.tychesoftwares.com/how-to-send-woocommerce-bookings-to-different-google-calendars-for-each-bookable-product/' target ='_blank'>this</a> post to setup the Google Calendar Sync settings for your product." , "woocommerce-booking") ?>
                        </td> 

                    </tr>
                    </table>

                    <hr>
                    
                    <h2> <strong><?php _e( "Import and Mapping of Events" , "woocommerce-booking" ); ?>  </strong> </h2>
                    
                    <table class='form-table'>
                   
                   <tr>
                        <?php 
                        $enable_mapping = '';
                        if ( isset( $booking_settings[ 'enable_automated_mapping' ] ) && 'on' == $booking_settings[ 'enable_automated_mapping' ] ) {
                            $enable_mapping = 'checked';
                        }
                        ?>
                        <th>
                        <?php _e( 'Enable Automated Mapping for Imported Events:', 'woocommerce-booking' ); ?>
                        </th>
                        <td>
                            <label class="bkap_switch">
                                <input id="enable_automated_mapping" name= "enable_automated_mapping" type="checkbox" <?php echo $enable_mapping;?>/>
                            <div class="bkap_slider round"></div> 
                            
                        </td>
                        <td>
                            <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Enable if you wish to allow for imported events to be automatically mapped to the product.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png"/>
            
                        </td>
          
                    </tr>
                
                    <?php
                    $_product = wc_get_product( $product_id );
                    $product_type = $_product->get_type();
                    
                    if ( isset( $product_type ) && 'variable' == $product_type ) {
                    ?>
                                  
                        <tr>
                        <?php
                        $available_variations = $_product->get_available_variations();
                        ?>
                            <th>
                            <?php _e( 'Default Variation to which Events should be mapped:', 'woocommerce-booking' ); ?>
                            </th>
                            <td>
                            <?php
                            if ( isset( $available_variations ) && count( $available_variations ) > 0 ) { ?>
                                <select id="gcal_default_variation" name= "gcal_default_variation" style="max-width:70%;">
                                <?php 
                                
                                    foreach ( $available_variations as $key => $value ) {
                                        $selected_variation = '';
                                        $variation_id = $value[ 'variation_id' ];
                                        if ( isset( $booking_settings[ 'gcal_default_variation' ] ) && '' != $booking_settings[ 'gcal_default_variation' ] && $variation_id == $booking_settings[  'gcal_default_variation' ] ) {
                                            $selected_variation = 'selected';
                                        }
                                        $variation_product = wc_get_product( $variation_id );
                                        $variation_name = $variation_product->get_formatted_name();
                                        ?>
                                        <option value='<?php echo $variation_id; ?>' <?php echo $selected_variation;?> ><?php echo $variation_name; ?></option>
                                        <?php 
                                    }
                                ?>
                                </select><br>
                                
                                <?php }?>
                            </td>
                            <td>
                                <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Select the default variation to which the product should be mapped. If left blanks, then the first variation shall be chosen.', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png"/>
                
                            </td>
              
                        </tr>
                        <?php 
                    }?>
              
              </table>
              <table class='form-table' id="product_ics_feed_list">
                        <?php
                        
                        $label = '<label for="product_ics_fee_url_0">' . __( '.ics/ICAL Feed URL', 'woocommerce-booking' ) . '</label>';
                        if ( isset( $booking_settings[ 'ics_feed_url' ] ) && count( $booking_settings[ 'ics_feed_url' ] ) > 0 ) {
                            foreach ( $booking_settings[ 'ics_feed_url' ] as $key => $value ) {
                                echo ( "
                                    <tr id='$key'>
                                        <th>$label</th>
                                        <td class='ics_feed_url'>
                                            <input type='text' id='product_ics_fee_url_$key' name='product_ics_fee_url_$key' size='40' value='" . $value. "'><br><br>
                                            <input type='button' class='save_button' id='$key' name='import_ics' value='Import Events'>
                                            <input type='button' class='save_button' id='$key' value='Delete' name='delete_ics_feed'>
                                            <div id='import_event_message' style='display:none;'>
                                                <img src='" . plugins_url() . "/woocommerce-booking/images/ajax-loader.gif'>
                                            </div>
                                            <div id='success_message' ></div>
                                        </td>
                                    </tr>
                                    ");
                            }
                        } else {
                            echo ( "
                                <tr id='0'>
                                    <th>$label</th>
                                    <td class='ics_feed_url'>
                                        <input type='text' id='product_ics_fee_url_0' name='product_ics_fee_url_0' size='40' value=''>
                                    </td>
                                </tr>
                            ");
                        }
                        ?>
    			</table>
    			
    			<table class='form-table'>
    			<tr>
                    <th></th>
                    <td>
                        <input type='button' class='save_button' id='add_new_ics_feed' name='add_new_ics_feed' value='Add New Ics feed url'>
                    </td>
               </tr>
              
    			</table>
    			<hr />
    			<?php 
    			if( isset( $post_type ) && $post_type === 'product' ) {
                    bkap_booking_box_class::bkap_save_button( 'bkap_save_gcal_settings' );
                }
                ?>
                
                <div id='gcal_update_notification' style='display:none;'></div>
            </fieldset>                
		</div>
    	<?php 			
        
    }

    function bkap_gcal_product_settings_save( $booking_settings, $product_id ) {
        
        //get existing settings to ensure calendar details are retained even when sync is disabled
        $bkap_settings = get_post_meta(  $product_id, 'woocommerce_booking_settings', true );
        
        if ( isset( $_POST[ 'product_sync_integration_mode' ] ) ) {
            $booking_settings[ 'product_sync_integration_mode' ] = $_POST[ 'product_sync_integration_mode' ];
        }
        
        $file_name = '';
        if ( isset( $_POST[ 'product_sync_key_file_name' ] ) ) {
            $file_name = $_POST[ 'product_sync_key_file_name' ];
        } else if ( isset( $bkap_settings[ 'product_sync_key_file_name' ] ) && '' != $bkap_settings[ 'product_sync_key_file_name' ] ) {
            $file_name = $bkap_settings[ 'product_sync_key_file_name' ]; 
        }
        $booking_settings[ 'product_sync_key_file_name' ] = $file_name;
        
        $acc_email = '';
        if ( isset( $_POST[ 'product_sync_service_acc_email_addr' ] ) ) {
            $acc_email = $_POST[ 'product_sync_service_acc_email_addr' ];
        } else if ( isset( $bkap_settings[ 'product_sync_service_acc_email_addr' ] ) && '' != $bkap_settings[ 'product_sync_service_acc_email_addr' ] ) {
            $acc_email = $bkap_settings[ 'product_sync_service_acc_email_addr' ];
        }
        $booking_settings[ 'product_sync_service_acc_email_addr' ] = $acc_email;
        
        $calendar_id = '';
        if ( isset( $_POST[ 'product_sync_calendar_id' ] ) ) {
            $calendar_id = $_POST[ 'product_sync_calendar_id' ];
        } else if ( isset( $bkap_settings[ 'product_sync_calendar_id' ] ) && '' != $bkap_settings[ 'product_sync_calendar_id' ] ) {
            $calendar_id = $bkap_settings[ 'product_sync_calendar_id' ];
        }
        $booking_settings[ 'product_sync_calendar_id' ] = $calendar_id;
        
        if ( isset( $_POST[ 'enable_automated_mapping' ] ) ) {
            $booking_settings[ 'enable_automated_mapping' ] = $_POST[ 'enable_automated_mapping' ];
        }
        if ( isset( $_POST[ 'gcal_default_variation' ] ) ) {
            $booking_settings[ 'gcal_default_variation' ] = $_POST[ 'gcal_default_variation' ];
        }
         
        for ( $key = 0; ;$key++ ) {
            $field_name = 'product_ics_fee_url_' . $key;
            if ( isset( $_POST[ $field_name ] ) ) {
                $booking_settings[ 'ics_feed_url' ][ $key ] = $_POST[ $field_name ];
            } else {
                break;
            }
        }
        
        return $booking_settings; 
    }
}
$product_gcal_settings = new product_gcal_settings;
?>