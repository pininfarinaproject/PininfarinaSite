<?php
    
class view_bookings{
    
	public function __construct() {
		add_action( 'admin_init', array( &$this, 'bkap_data_export' ) );
		
	}
	
   /**
    * This function adds a page on View Bookings submenu which displays the orders with the booking details. 
    * The orders which are cancelled or refunded are not displayed.
    */
   public static function bkap_woocommerce_history_page() {

        if ( isset( $_GET['action'] ) ) {
	        $action = $_GET['action'];
        } else {
            $action = '';
        }
        
        if ( $action == 'history' || $action == '' ) {
            $active_settings = "nav-tab-active";
        }

        if ( $action == 'history' || $action == '' ) {
        	global $wpdb;
        	
        	include_once( 'class-view-bookings-table.php' );
        	$bookings_table = new WAPBK_View_Bookings_Table();
        	$bookings_table->bkap_prepare_items();
        	if ( !isset( $_GET[ 'item_id' ] ) || ( isset( $_GET[ 'item_id' ] ) && $_GET[ 'item_id' ] == 0 ) ) {
        	?>
        	<div class="wrap">
        	<h2><?php _e( 'All Bookings', 'woocommerce-booking' ); ?></h2>
        		<?php do_action( 'bkap_bookings_page_top' ); ?>
        		
        		<form id="bkap-view-bookings" method="get" action="<?php echo admin_url( 'admin.php?page=woocommerce_history_page' ); ?>">
        		
                    <div id="bkap_update_event_message"></div>
                        <?php
                        //get the current logged in user
                        $user_id = get_current_user_id();
                         
                        // get the user role
                        $user = new WP_User( $user_id );
                        if( 'tour_operator' == $user->roles[ 0 ] ) {
                            $url = admin_url( 'edit.php?post_type=bkap_booking' );
                        } else {
                            $url = admin_url( 'edit.php?post_type=bkap_booking' );
                        }
                        ?>
        			<p id="bkap_add_order">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=bkap_create_booking_page' ) ); ?>" class="button-secondary" id="bkap_create_booking" ><?php _e( 'Create Booking', 'woocommerce-booking' ); ?></a>
        			     <a href="
						    <?php echo isset ( $_GET['booking_view'] ) && $_GET['booking_view'] == "booking_calender" ? $url : esc_url( add_query_arg( 'booking_view', 'booking_calender' ) ) ;  ?>" 
    						class="button-secondary">
    						<?php isset ( $_GET['booking_view'] ) && $_GET['booking_view'] == "booking_calender" ? _e( 'View Booking Listing', 'woocommerce-booking' )  : _e ( 'Calendar View', 'woocommerce-booking' );  ?>
						</a>
        			<?php 
        			if ( !isset( $_GET['booking_view'] ) ) {
                        $gcal = new BKAP_Gcal();
        			    $total_bookings_to_export = bkap_common::bkap_get_total_bookings_to_export( $user_id );
        			    
                        if( 'tour_operator' == $user->roles[ 0 ] ) {
        			        $display_button_setting = esc_attr( get_the_author_meta( 'tours_add_to_calendar_view_booking', $user_id ) );
        			    } else {
        			        $display_button_setting =  get_option( 'bkap_admin_add_to_calendar_view_booking' );
        			    }
        			     
        			    if( $gcal->get_api_mode( $user_id ) == "directly" && 'on' == $display_button_setting ) {
        			    ?>
        			    
        			    <input type="button" class="button-secondary" id="bkap_admin_add_to_calendar_booking" style="float:right;" value="<?php _e( 'Add to Google Calendar', 'woocommerce-booking' ); ?>">
                            
                            <script type="text/javascript">
                            jQuery( document ).ready( function(){ 
                                jQuery( "#bkap_admin_add_to_calendar_booking" ).on( 'click', function() {
                                	<?php if ( count( $total_bookings_to_export ) > 0 ) {?>
                                        var orders_to_export = "<?php echo count( $total_bookings_to_export ); ?>";
                                        jQuery( "#bkap_update_event_message" ).html( "Total orders to export " +  orders_to_export + " ... " );
                                        var data = {
                                     		   user_id: <?php echo $user_id; ?>, 
                                     		   action: "bkap_admin_booking_calendar_events"
                            		    };
                                        jQuery.post( "<?php echo get_admin_url(); ?>/admin-ajax.php", data, function( response ) {
                                     	   jQuery( "#bkap_update_event_message" ).html( orders_to_export + " bookings have been exported to your Google Calendar. Please refresh your Google Calendar." );
                                	    });
                            	    <?php } else {?>
                            	    jQuery( "#bkap_update_event_message" ).html( "No pending orders left to be exported." );
                            	    <?php }?>
                                });
                            });
                            </script>
                        <?php 
                        }
                        ?>
        			    <a href="<?php echo esc_url( add_query_arg( 'download', 'data.print' ) ); ?>" target="_blank" style="float:right;" class="button-secondary"><?php _e( 'Print', 'woocommerce-booking' ); ?></a>
						<a href="<?php echo esc_url( add_query_arg( 'download', 'data.csv' ) ); ?>" style="float:right;" class="button-secondary"><?php _e( 'CSV', 'woocommerce-booking' ); ?></a>
						<?php do_action( 'bkap_bookings_print_summary' ); ?>
						<?php }?>
					</p>
		
					<input type="hidden" name="page" value="woocommerce_history_page" />

					<?php if ( isset($_GET['booking_view'] ) && ( $_GET['booking_view'] == 'booking_calender' ) ) {
                        ?>
                            <h2><?php _e( 'Calendar View', 'woocommerce-booking' ); ?></h2>
                            <div id="bkap_events_loader" style="font-size: medium;">Loading Calendar Events....<img src=<?php echo plugins_url() . "/woocommerce-booking/images/ajax-loader.gif"; ?>></div>
                            <div id='calendar'></div>
                        <?php }else{
                     ?>
					<?php $bookings_table->views() ?>
					
					<?php 
					       $bookings_table->advanced_filters();  
						   $bookings_table->advanced_filters_by_date();
						   
						   $bookings_table->bkap_order_status();
						   $bookings_table->bkap_vehicle_filter();
					       
					  ?>
					<?php $bookings_table->display() ?>
				    <?php } ?>
				
					
        			</form>
				<?php do_action( 'bkap_bookings_page_bottom' ); ?>
        	</div>
        	<?php 
        	}
        }
   }
   
    
   	public function bkap_data_export() {	
		global $wpdb;
		
		$tab_status = '';
		
		if ( isset( $_GET['status'] ) ) {
			$tab_status = $_GET['status'];
		}
		
		if (isset( $_GET['duration_select'] ) && $_GET['duration_select'] !='' && !( isset( $_GET['s'] ) ) ){
		    $tab_status = 'custom';
		}
		
		if ( isset( $_GET['download'] ) && ( $_GET['download'] == 'data.csv' ) && 
			isset( $_GET['page'] ) && $_GET['page'] = 'woocommerce_history_page' ) {
			$report  = view_bookings::generate_data( $tab_status );
	   		$csv     = view_bookings::generate_csv( $report );
	   		
	   		header("Content-type: application/x-msdownload");
	        header("Content-Disposition: attachment; filename=data.csv");
	        header("Pragma: no-cache");
	        header("Expires: 0");
	        echo "\xEF\xBB\xBF";
	   		echo $csv;
	   		exit;
		}else if( isset( $_GET['download'] ) && ( $_GET['download'] == 'data.print' ) && 
			isset( $_GET['page'] ) && $_GET['page'] = 'woocommerce_history_page' ) {
			$report              = view_bookings::generate_data($tab_status);
			
			$print_data_columns  = "
                					<tr>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Order ID', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Customer Name', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Product Name', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Check-in Date', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Check-out Date', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Booking Time', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Quantity', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Amount', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Order Date', 'woocommerce-booking' )."</th>
                					</tr>";
			$print_data_row_data =  '';
			
			foreach ( $report as $key => $value ) {
			    // Currency Symbol
			    // The order currency is fetched to ensure the correct currency is displayed if the site uses multi-currencies
			    $the_order          = wc_get_order( $value->order_id );
			    $currency           = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $the_order->get_order_currency() : $the_order->get_currency();
			    $currency_symbol    = get_woocommerce_currency_symbol( $currency );
			     
				$print_data_row_data .= "<tr>
        								<td style='border:1px solid black;padding:5px;'>".$value->order_id."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->customer_name."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->product_name."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->checkin_date."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->checkout_date."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->time."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->quantity."</td>
        								<td style='border:1px solid black;padding:5px;'>".$currency_symbol . $value->amount."</td>
        								<td style='border:1px solid black;padding:5px;'>".$value->order_date."</td>
        								</tr>";
			}
			$print_data_columns  =   apply_filters( 'bkap_view_bookings_print_columns', $print_data_columns );
			$print_data_row_data =   apply_filters( 'bkap_view_bookings_print_rows', $print_data_row_data, $report );
			$print_data          =   "<table style='border:1px solid black;border-collapse:collapse;'>" . $print_data_columns . $print_data_row_data . "</table>";
			$print_data          =   "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body><table style='border:1px solid black;border-collapse:collapse;'>" . $print_data_columns . $print_data_row_data . "</table></body></html>";
			echo $print_data;
			?>
			
			<?php 
			exit;
		} 
		do_action( 'bkap_add_print_summary_data', $tab_status );
   	}
   	
   	function generate_data($tab_status) {
   		global $wpdb;
   		
   		$results = array();
		$current_time = current_time( 'timestamp' );
		$current_date = date( "Y-m-d", $current_time );
		
   	    if ( $tab_status == 'future' ) {
		    
			$booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date >= '".$current_date."' ORDER BY a2.order_id DESC";
			$query_results         = $wpdb->get_results( $booking_query );
		}else if ( $tab_status == 'today_checkin' ) {
		    
			$booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date = '".$current_date."' ORDER BY a2.order_id DESC";
			$query_results         = $wpdb->get_results( $booking_query );
		}else if ( $tab_status == 'today_checkout' ) {
		    
			$booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.end_date = '".$current_date."' ORDER BY a2.order_id DESC";
			$query_results         = $wpdb->get_results( $booking_query );
		}else if ( $tab_status == 'custom' ) {
		    
		    
		    $start_date_ts = strtotime($_GET['bkap_start_date']);
		    $start_date    = date( "Y-m-d",$start_date_ts);
		    	
		    $end_date_ts = strtotime($_GET['bkap_end_date']);
		    $end_date    = date( "Y-m-d",$end_date_ts);
		    
		    if (isset($_GET ['bkap_from_time']) && $_GET ['bkap_from_time'] !=''){
		        $user_selected_from_time = $_GET ['bkap_from_time'];
		    }else{
		        $user_selected_from_time = "00:00";
		    }
		    
		    if ( isset($_GET ['bkap_to_time']) && $_GET ['bkap_to_time'] !='' ){
		        $user_selected_to_time   = $_GET ['bkap_to_time'];
		    }else{
		        $user_selected_to_time = "23:59";
		    }

		    $time_query = '';
		    if ( isset($_GET ['bkap_to_time']) && $_GET ['bkap_to_time'] !='' && isset($_GET ['bkap_from_time']) && $_GET ['bkap_from_time'] !='' ){
		        $user_selected_to_time   = $_GET ['bkap_to_time'];
		        $user_selected_from_time = $_GET ['bkap_from_time'];
		    
		        $time_query = " AND DATE_FORMAT( STR_TO_DATE( a1.`from_time` ,  '%H:%i' ) ,  '%H:%i' ) BETWEEN  '".$user_selected_from_time."' AND  '".$user_selected_to_time."' " ;
		    }
		    
		    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date BETWEEN '".$start_date."' AND '".$end_date ."' ". $time_query . " ORDER BY a2.order_id DESC";
		    
		    $query_results   = $wpdb->get_results( $booking_query );
		    
		}else {
		    
			$booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id ORDER BY a2.order_id DESC";
			$query_results         = $wpdb->get_results( $booking_query );
		}
		
		$results = array();
		
		$_status = $tab_status;
		
		switch ( $_status ) {
		    case 'pending_confirmation':
		        $_status = 'pending-confirmation';
		        break;
		    case 'unpaid':
		        $_status = 'confirmed';
		        break;
		    case 'future':
		        $_status = 'paid';
		        break;
		    case 'today_checkout':
		        $_status = 'paid';
		        break;
		    case 'today_checkin':
		        $_status = 'paid';
		        break;
		    default:
		        $_status = '';
		        break;
		
		}
		
   	    foreach ( $query_results as $key => $value ) {
		    $order_id = $value->order_id;
		
		    $get_items_sql  = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_id, order_item_name FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = %s", $order_id, 'line_item' ) );
		     
		    foreach ( $get_items_sql as $i_key => $i_value ) {
		        $get_items = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key IN ( '_product_id', '_wapbk_booking_status' ) AND order_item_id = %d ORDER BY FIELD( meta_key, '_product_id', '_wapbk_booking_status' )" , $i_value->order_item_id ) );
		    }
		         
		    $include_status   = 'NO';
		
		    for( $i = 0; $i < count( $get_items); $i++ ) {
		        
		        $item_values[ 'product_id' ] = $get_items[$i]->meta_value;
		        if ( isset( $get_items[$i + 1]->meta_value ) ) {
	               $item_values[ 'wapbk_booking_status' ] = $get_items[$i + 1]->meta_value;
		        }
	            $booking_status = '';
		        if ( $value->post_id == $item_values[ 'product_id' ] ) {
		            if ( isset( $item_values[ 'wapbk_booking_status' ] ) )  {
		                $booking_status = $item_values[ 'wapbk_booking_status' ];
		            }
		
		            if ( isset( $booking_status ) ) {
		                if ( $_status == $booking_status ) {
		                    $include_status   = 'YES';
		                } else if ( ( $booking_status != 'confirmed' && $booking_status != 'pending-confirmation' ) && ( 'paid' == $_status ) ) {
		                    $include_status   = 'YES';
		                } else if( $booking_status != 'cancelled' && '' == $_status ) {
		                    $include_status   = 'YES';
		                }
		            }
		        }
		        $i++;
		    }
		    if( $include_status == 'YES' ) {
		        $results[] = $query_results[ $key ];
		    }
		}
		
		// Date formats
		$date_formats     = bkap_get_book_arrays( 'bkap_date_formats' );
		// get the global settings to find the date & time formats
		$global_settings  = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		$date_format_set  = $date_formats[ $global_settings->booking_date_format ];
		//Time format
		$time_format_set  = $global_settings->booking_time_format;
		$report           = array();
		$i                = 0;
		
		foreach ( $results as $key => $value ) {
			$checkout_date   = $time = '';
			$res_order_id = $value->order_id;
			
			if ( false !== get_post_status( $res_order_id ) ) {
			    $order           = new WC_Order( $res_order_id );
			}else{
			    continue;
			}
			
			// check if the order is refunded, trashed or cancelled
			$order_status = $order->get_status();
			$order_status = "wc-$order_status";
			if( isset( $order_status ) && ( $order_status != 'wc-cancelled' ) && ( $order_status != 'wc-refunded' ) && ( $order_status != 'trash' ) && ( $order_status != '' ) && ( $order_status != 'wc-failed' ) && ( 'auto-draft' != $order_status ) ) {
				
			    $report[ $i ]                   = new stdClass();
				// Order ID
				$report[ $i ]->order_id         = $res_order_id;
				// Booking ID
				$report[ $i ]->booking_id       = $value->booking_id;
				// Customer Name
                if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
				    $report[ $i ]->customer_name = $order->billing_first_name . " " . $order->billing_last_name;
				} else {
				    $report[ $i ]->customer_name    = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
				}
				// Product ID
				$report[ $i ]->product_id       = $value->post_id;
				
				// Check-in Date
				$report[ $i ]->checkin_date     = date( $date_format_set, strtotime( $value->start_date ) );
				
				// Checkout Date
				if ( $value->end_date != '1970-01-01' && $value->end_date != '0000-00-00' ) {
					$report[ $i ]->checkout_date = date( $date_format_set, strtotime( $value->end_date ) );
				}else {
					$report[ $i ]->checkout_date = '';
				}
				
				// Booking Time
				$time = '';
				
				if ( $value->from_time != "" ) {
					$time = $value->from_time;
				}
				
				if ( $value->to_time != "" ) {
					$time .=  " - " . $value->to_time;
				}
				
				if ( $time != '' ) {
					
				    if ( $time_format_set == 12 ) {
						$from_time    = date( 'h:i A', strtotime( $value->from_time ) );
						$to_time      = date( 'h:i A', strtotime( $value->to_time ) );
						$time         = $from_time . " - " . $to_time;
					}
				}
				
				$report[$i]->time   = $time;
				// Quantity & amount
                $get_items_sql  = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_id, order_item_name FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = %s", $res_order_id, 'line_item' ) );
			
				foreach ( $get_items_sql as $i_key => $i_value ) {
				    $get_items = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_id, meta_key, meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key IN ( '_product_id', '_qty', '_line_total', '_line_tax' ) AND order_item_id = %d ORDER BY FIELD( meta_key, '_product_id', '_qty', '_line_total', '_line_tax' )" , $i_value->order_item_id ) );
				}
				$get_quantity = array();
		
				foreach ( $get_items as $get_key => $get_value ) {
        		    switch ( $get_value->meta_key ) {
        		        case '_product_id':
        		            $get_quantity[ $get_value->order_item_id ][ 'product_id' ] = $get_value->meta_value;
        		            break;
        		        case '_qty':
        		            $get_quantity[ $get_value->order_item_id ][ 'qty' ] = $get_value->meta_value;
        		            break;
        		        case '_line_total':
        		            $get_quantity[ $get_value->order_item_id ][ 'line_total' ] = $get_value->meta_value;
                            break;
                        case '_line_tax':
        		            $get_quantity[ $get_value->order_item_id ][ 'line_tax' ] = $get_value->meta_value;
        		            break;
        		        default:
        		            break;		            
        		    }
        		    $get_quantity[ $get_value->order_item_id ]['item_meta'] = get_metadata( 'order_item', $get_value->order_item_id );
        		}				

				// The array needs to be reversed as we r displaying the last item first
				$get_quantity       = array_reverse( $get_quantity, true );
				
				foreach( $get_quantity as $k => $v ) {
					$product_exists     =  'NO';
					$duplicate_of       =  bkap_common::bkap_get_product_id( $v[ 'product_id' ] );
					$attributes_array   =  array();
					$var                =  '';
					
					$attributes         =  get_post_meta( $duplicate_of , '_product_attributes' );
					
		            $product            =  wc_get_product( $duplicate_of );
				    $product_type = '';
		            if ( is_object( $product ) ) { // this is to ensure the product exists
			            $product_type       =  $product->get_type();
		            }
				    
				    if ( isset( $product_type ) && $product_type == 'variable' ) {
				    
	    				    foreach ( $attributes[0] as $attributes_key => $attributes_value ){
    				    
	    				        $attribute_name = $attributes_key;
    				        
	    				        if ( array_key_exists ( $attribute_name, $v ) ){
	    				            $attributes_array [$attributes_value['name']] = $v[$attribute_name];
	    				        }
	    				        if ( isset( $attributes_array [ $attributes_value[ 'name' ] ] ) ) {
	    				           $var .= "<br>".$attributes_value['name']." : ".$attributes_array [$attributes_value['name']];
	    				        }
    				        
	    				    }
				    }
					
					if ( $duplicate_of == $value->post_id ) {
						
					    foreach ( $report as $book_key => $book_value ) {
							
					        if ( $book_value->order_id == $res_order_id && $duplicate_of == $book_value->product_id ) {
								
					            if ( isset( $book_value->item_id ) && $k == $book_value->item_id ) {
									$product_exists = 'YES';
								}
							}
						} 
						if ( $product_exists == 'NO' ) {
							
						    $selected_quantity   = $v['qty'];
							$amount              = $v['line_total'] + $v['line_tax'];
							$report[$i]->item_id = $k;
							break;
						}
					}
				}
				
				$report[ $i ]->quantity     = $selected_quantity;
				$report[ $i ]->amount       = $amount;
				
				// Product Name
				$report[ $i ]->product_name     = get_the_title( $value->post_id ).$var;
				// Order Date
                $order_date = '';
				if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
				    $order_date = $order->completed_date;
				} else {
				    $order_post = get_post( $res_order_id );
				    $post_date = strtotime ( $order_post->post_date );
				    $order_date = date( 'Y-m-d H:i:s', $post_date );
				    
                 /*   $order_completed_obj = $order->get_date_completed();
    				$order_created_obj = $order->get_date_created();
    				
    				if ( isset( $order_completed_obj ) && count( $order_completed_obj ) > 0 ) {
    				    $order_date      = $order_completed_obj->format('Y-m-d H:i:s');
    				} else {
    				    $order_date      = $order_created_obj->format('Y-m-d H:i:s');
    				} */
				}
				$report[ $i ]->order_date = $order_date;
				
				$i++;
			}
		}
		
		//sort for order Id
		if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'ID' ) {
    		
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    				usort( $report, array( __CLASS__ , "bkap_order_id_asc" ) );
    		}else {
    				usort( $report, array( __CLASS__ , "bkap_order_id_dsc" ) );
    		}
		}
		// sort for amount
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'amount' ) {
    		
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    				usort( $report, array( __CLASS__ , "bkap_amount_asc" ) );
    		}else {
    				usort( $report, array( __CLASS__ , "bkap_amount_dsc" ) );
    		}
		}
		// sort for qty
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'quantity' ) {
    		
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    				usort( $report, array( __CLASS__ ,"bkap_quantity_asc" ) );
    		}else {
    				usort( $report,  array( __CLASS__ ,"bkap_quantity_dsc" ) );
    		}
		}
		// sort for order date
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'order_date' ) {
    		
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    				usort( $report, array( __CLASS__ , "bkap_order_date_asc" ) );
    		}else {
    				usort( $report, array( __CLASS__ , "bkap_order_date_dsc" ) );
			} 
		}
		// sort for booking/checkin date
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'checkin_date' ) {
		    
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $report, array( __CLASS__ , "bkap_checkin_date_asc" ) );
			}else {
				usort( $report, array( __CLASS__ , "bkap_checkin_date_dsc" ) );
			}
		}
		// sort for check out date
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'checkout_date' ) {
    		
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    				usort( $report, array( __CLASS__ , "bkap_checkout_date_asc" ) );
    		 }else {
    				usort( $report, array( __CLASS__ , "bkap_checkout_date_dsc" ) );
			}
		}
		// sort for customer name
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'name' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $report, array( __CLASS__ , "bkap_name_asc" ) );
			}else {
				usort( $report, array( __CLASS__ , "bkap_name_dsc" ) );
			}
		}
		// sort for product name
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'product_name' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $report, array( __CLASS__ , "bkap_product_name_asc" ) );
			}else {
				usort( $report, array( __CLASS__ , "bkap_product_name_dsc" ) );
			}
		}

		$search_results = array();
		
		if ( isset( $_GET['s'] ) && $_GET['s'] != '' ) {
		
		    $date            = '';
			// strtotime does not support all date formats. hence it is suggested to use the "DateTime date_create_from_format" fn 
			$date_formats    = bkap_get_book_arrays( 'bkap_date_formats' );
			// get the global settings to find the date formats
			$global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
			$date_format_set = $date_formats[ $global_settings->booking_date_format ];
			$date_formatted  = date_create_from_format( $date_format_set, $_GET['s'] );
			
			if ( isset( $date_formatted ) && $date_formatted != '' ) {
				$date = date_format( $date_formatted, 'Y-m-d' );
			}
			
		    $time = $from_time = $to_time = '';
		    
		    if ( strpos( $_GET['s'], '-' ) ) {
		        $time_array = explode( '-', $_GET['s'] );
		        
		        if ( isset( $time_array[0] ) && $time_array[0] != '' ) {
		            $from_time = date( 'G:i', strtotime( trim( $time_array[0] ) ) );
		        }
		        
		        if ( isset( $time_array[1] ) && $time_array[1] !== '' ) {
		            $to_time = date( 'G:i', strtotime( trim( $time_array[1] ) ) );
		        }
		        
		        $time = $from_time . " - " . $to_time;
		    }
		    
		    foreach ( $report as $key => $value ) {
		        
		        if ( is_numeric($_GET['s'] ) ) {
		            
		            if ( $value->order_id == $_GET['s'] ) {
		                $search_results[] = $report[ $key ];
		            }
		        }
		        else {
		            
		            foreach ( $value as $k => $v ) {
		                
		                if ( $k == 'checkin_date' || $k == 'checkout_date' && $date != '' ) {
		                    $date_value_formatted = date_create_from_format( $date_format_set, $v );	
		                    
		                    if ( isset( $date_value_formatted ) && $date_value_formatted != '' ) {
		                        $date_value = date_format( $date_value_formatted, 'Y-m-d' );
		                        
		                        if ( stripos( $date_value, $date ) !== false ) {
		                        	$search_results[] = $report[ $key ];
		                        }
		                    }
		                }else if ( $k == 'booking_time' ) {
		                    if ( isset( $v ) && $v != '' && $time != '' ) {
		                        if ( stripos( $v, $time ) !== false ) {
		                            $search_results[] = $report[ $key ];
		                        }
		                    }
		                }
		                else {
		                    if ( stripos( $v, $_GET['s'] ) !== false ) {
		                        $search_results[] = $report[ $key ];
		                    }
		                }
		            }
		        }
		    }
		    
		    if ( is_array( $search_results ) && count( $search_results ) > 0 ) {
		        $report = $search_results;
		    }
		    else {
		        $report = array();
		    }
		    
		}
		return apply_filters( 'bkap_bookings_export_data', $report );
   	}
   	
   	function bkap_order_id_asc( $value1, $value2 ) {
   	    return $value1->order_id - $value2->order_id;
   	}
   	
   	function bkap_order_id_dsc( $value1, $value2 ) {
   	    return $value2->order_id - $value1->order_id;
   	}
   	
   	function bkap_amount_asc( $value1, $value2 ) {
   	    return $value1->amount - $value2->amount;
   	}
   	
   	function bkap_amount_dsc ( $value1, $value2 ) {
   	    return $value2->amount - $value1->amount;
   	}
   	
   	function bkap_quantity_asc( $value1, $value2 ) {
   	    return $value1->quantity - $value2->quantity;
   	}
   	
   	function bkap_quantity_dsc( $value1, $value2 ) {
   	    return $value2->quantity - $value1->quantity;
   	}
   	
   	function bkap_order_date_asc( $value1, $value2 ) {
   	    return strtotime( $value1->order_date ) - strtotime( $value2->order_date );
   	}
   	 
   	function bkap_order_date_dsc( $value1, $value2 ) {
   	    return strtotime( $value2->order_date ) - strtotime( $value1->order_date );
   	}
   	
   	function bkap_checkin_date_asc( $value1, $value2 ) {
   	    return strtotime( $value1->checkin_date) - strtotime( $value2->checkin_date );
   	}
   	
   	function bkap_checkin_date_dsc( $value1, $value2 ) {
   	    return strtotime( $value2->checkin_date ) - strtotime( $value1->checkin_date );
   	}
   	
   	
   	function bkap_checkout_date_asc( $value1, $value2 ) {
   	    return strtotime( $value1->checkout_date ) - strtotime( $value2->checkout_date );
   	}
   	
   	function bkap_checkout_date_dsc( $value1, $value2 ) {
   	    return strtotime( $value2->checkout_date ) - strtotime( $value1->checkout_date );
   	}
   	
   	function bkap_name_asc( $value1, $value2 ) {
   	    return strcasecmp( $value1->customer_name, $value2->customer_name );
   	}
   	
   	function bkap_name_dsc( $value1, $value2 ) {
   	    return strcasecmp( $value2->customer_name, $value1->customer_name );
   	}
   	
   	function bkap_product_name_asc( $value1, $value2 ) {
   	    return strcasecmp( $value1->product_name, $value2->product_name );
   	}
   	
   	function bkap_product_name_dsc( $value1, $value2 ) {
   	    return strcasecmp( $value2->product_name, $value1->product_name );
   	}
   	
   	function generate_csv( $report ) {
   		
  		// Column Names
   		$csv               = 'Order ID,Customer Name,Product Name,Check-in Date, Check-out Date,Booking Time,Quantity,Amount, Order Date';
   		$csv              .= "\n";
   		
   		foreach ( $report as $key => $value ) {
   			// Order ID
   			$order_id         = $value->order_id;
   			// Customer Name
   			$customer_name    = $value->customer_name;
   			// Product Name
   			$product_name     = $value->product_name;
   			$product_name_array = explode( '<br>', $product_name );
   			$product_name = implode( "\n", $product_name_array );
   			
   			// Check-in Date
   			$checkin_date     = $value->checkin_date;
   			// Checkout Date
   			$checkout_date    = $value->checkout_date;
   			// Booking Time
   			$time             = $value->time;
   			// Quantity & amount
   			$selected_quantity= $value->quantity;
   			$amount           = $value->amount;
   			// Order Date
   			$order_date       = $value->order_date;
   			// Currency Symbol
   			// The order currency is fetched to ensure the correct currency is displayed if the site uses multi-currencies
   			$the_order          = wc_get_order( $value->order_id );
   			$currency           = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $the_order->get_order_currency() : $the_order->get_currency();
   			$currency_symbol    = get_woocommerce_currency_symbol( $currency );
   			
   			// Create the data row
   			$csv             .= $order_id . ',' . $customer_name . ',"' . $product_name . '","' . $checkin_date . '","' . $checkout_date . '","' . $time . '",' . $selected_quantity . ',' . $currency_symbol . $amount . ',' . $order_date;
   			$csv             .= "\n";  
   		}
   		$csv = apply_filters( 'bkap_bookings_csv_data', $csv, $report );
   		return $csv;
   	}
   	
}

$view_bookings = new view_bookings();
