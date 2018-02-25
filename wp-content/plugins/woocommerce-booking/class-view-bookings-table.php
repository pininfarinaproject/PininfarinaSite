<?php 

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WAPBK_View_Bookings_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * 
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * 
	 */
	public $base_url;

	/**
	 * Total number of bookings
	 *
	 * @var int
	 * 
	 */
	public $total_count;

	/**
	 * Total number of bookings from today onwards
	 *
	 * @var int
	 * 
	 */
	public $future_count;

	/**
	 * Total number of check-ins today
	 *
	 * @var int
	 * 
	 */
	public $today_checkin_count;

	/**
	 * Total number of checkouts today
	 *
	 * @var int
	 * 
	 */
	public $today_checkout_count;

	/**
	 * Total Number of bookings awaiting confirmation
	 * 
	 *  @var int
	 */
	public $pending_confirmation;
	
	/**
	 * Total number of unpaid bookings
	 * 
	 * @var int
	 */
	public $unpaid;
	/**
	* Total number of Bookings Imported from GCal
	*/
	public $gcal_reserved;
	
	var $duration_range_select = array();
	var $start_end_dates = array();
	
	/**
	 * Get things started
	 *
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {

		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
				'ajax'      => false             			// Does this table support ajax?
		) );

		$this->base_url = admin_url( 'admin.php?page=woocommerce_history_page' );
		
		$this->duration_range_select = array(
		    'select_period'     => __( 'Select a period'   , 'woocommerce-booking' ),
		    'yesterday'         => __( 'Yesterday'   , 'woocommerce-booking' ),
		    'today'             => __( 'Today'       , 'woocommerce-booking' ),
		    'last_seven'        => __( 'This Week'   , 'woocommerce-booking' ),
		    'last_fifteen'      => __( 'Fifteen Days' , 'woocommerce-booking' ),
		    'last_thirty'       => __( 'This Month'  , 'woocommerce-booking' ),
		    'last_year_days'    => __( 'This Year'   , 'woocommerce-booking' ),
		    'other'             => __( 'Custom'   , 'woocommerce-booking' ),
		);
		
		
		$current_time = current_time ('timestamp');
		
		$begin_of_month = mktime(0, 0, 0, date("n"), 1);
		$end_of_month   = mktime(23, 59, 0, date("n"), date("t"));
		
		$begin_of_week = strtotime('sunday last week');
		$end_of_week = strtotime("saturday this week");
		
		
		$current_date = date("j") ;
		
		if ( isset( $current_date ) && $current_date <= 15 ){
		    
		    $begin_of_fifteen_days = mktime(0, 0, 0, date("n"), 1);
		    $end_of_fifteen_days   = mktime(23, 59, 0, date("n"), 15);
		}else{
		    
		    $begin_of_fifteen_days = mktime(0, 0, 0, date("n"), 16);
		    $end_of_fifteen_days   = mktime(23, 59, 0, date("n"), date("t"));
		}
		
		$year = date('Y') - 1; // Get current year and subtract 1
		$start_of_year = mktime(0, 0, 0, 1, 1, $year);
		$end_of_year = mktime(0, 0, 0, 12, 31, $year);
		 
		$this->start_end_dates = array(
		    
		    'select_period'     => array( 'start_date' => "", 'end_date' => "" ),
		    
		    'yesterday'     => array( 'start_date' => date( "d M Y", ( current_time('timestamp') - 24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) - 7*24*60*60 ) ) ),
		     
		    'today'         => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),
		     
		    'last_seven'    => array( 'start_date' => date( "d M Y", $begin_of_week ), 'end_date' => date( "d M Y", $end_of_week  ) ),
		     
		    'last_fifteen'  => array( 'start_date' => date( "d M Y", $begin_of_fifteen_days ), 'end_date' => date( "d M Y", $end_of_fifteen_days  ) ),
		     
		    'last_thirty'   => array( 'start_date' => date( "d M Y", $begin_of_month ), 'end_date' => date( "d M Y", $end_of_month  ) ),
		     
		    'last_year_days'=> array( 'start_date' => date( "d M Y", $start_of_year ) , 'end_date' => date( "d M Y", $end_of_year ) ) );
	}
	
	public function bkap_prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();
		$data     = $this->bookings_data();
		$status   = isset( $_GET['status'] ) ? $_GET['status'] : 'any';
		
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		$args = array();
		if( isset( $_GET[ 'user' ] ) ) {
		    $args[ 'user' ] = urldecode( $_GET[ 'user' ] );
		} elseif( isset( $_GET[ 's' ] ) ) {
		    $args[ 's' ] = urldecode( $_GET[ 's' ] );
		}
		
		if ( ! empty( $_GET[ 'start-date' ] ) ) {
		    $args[ 'start-date' ] = urldecode( $_GET[ 'start-date' ] );
		}
		
		if ( ! empty( $_GET[ 'end-date' ] ) ) {
		    $args[ 'end-date' ] = urldecode( $_GET[ 'end-date' ] );
		}
		
		$args[ 'pagination-call' ] = 'yes';
		
		$bookings_count = $this->get_booking_counts( $args );
	switch ( $status ) {
		    case 'future':
		        $total_items = $this->future_count;
		        break;
		    case 'today_checkin':
		        $total_items = $this->today_checkin_count;
		        break;
		    case 'today_checkout':
		        $total_items = $this->today_checkout_count;
		        break;
		    case 'any':
		        $total_items = $this->total_count;
		        break;
		    case 'pending_confirmation':
		        $total_items = $this->pending_confirmation;
		        break;
		    case 'unpaid':
		        $total_items = $this->unpaid;
		        break;
		    case 'gcal_reservations':
		        $total_items = $this->gcal_reservations;
		        break;
		    default:
		        $total_items = $this->total_count;
		}  
		
		$this->items = $data;
		
		$this->set_pagination_args( array(
				'total_items' => $total_items,                  	// WE have to calculate the total number of items
				'per_page'    => $this->per_page,                     	// WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $this->per_page )   // WE have to calculate the total number of pages
		)
		);
	
	}
	
	public function get_views() {
		$current                  = isset( $_GET['status'] ) ? $_GET['status'] : '';
		$total_count              = '&nbsp;<span class="count">(' . $this->total_count    . ')</span>';
		$future_count             = '&nbsp;<span class="count">(' . $this->future_count  . ')</span>';
		$today_checkin_count      = '&nbsp;<span class="count">(' . $this->today_checkin_count . ')</span>';
		$today_checkout_count     = '&nbsp;<span class="count">(' . $this->today_checkout_count   . ')</span>';
		$unpaid                   = '&nbsp;<span class="count">(' . $this->unpaid   . ')</span>';
		$pending_confirmation     = '&nbsp;<span class="count">(' . $this->pending_confirmation   . ')</span>';
		$reserved_by_gcal         = '&nbsp;<span calss="count">(' . $this->gcal_reserved  . ')</span>';
		
		$views = array(
				'all'		=> sprintf( '<a href="%s"%s>%s</a>', remove_query_arg( array( 'status', 'paged', 'duration_select', 'bkap_start_date', 'bkap_end_date', 'bkap_from_time', 'bkap_to_time', '_wp_http_referer', '_wpnonce','s' ) ), $current === 'all' || $current == '' ? ' class="current"' : '', __( 'All', 'woocommerce-booking' ) . $total_count ),
				'future'	=> sprintf( '<a href="%s",%s>%s</a>', add_query_arg( array( 'status' => 'future', 'paged' => FALSE, 'duration_select' => '', 'bkap_start_date' => '', 'bkap_end_date' => ''  ) ), $current === 'future' ? ' class="current"' : '', __( 'Bookings From Today Onwards', 'woocommerce-booking' ) . $future_count ),
				'today_checkin'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'today_checkin', 'paged' => FALSE, 'duration_select' => '', 'bkap_start_date' => '', 'bkap_end_date' => '', 'bkap_from_time' => '', 'bkap_to_time' => '' ) ), $current === 'today_checkin' ? ' class="current"' : '', __( 'Todays Check-ins', 'woocommerce-booking' ) . $today_checkin_count ),
				'today_checkout'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'today_checkout', 'paged' => FALSE, 'duration_select' => '', 'bkap_start_date' => '', 'bkap_end_date' => '', 'bkap_from_time' => '', 'bkap_to_time' => '' ) ), $current === 'today_checkout' ? ' class="current"' : '', __( 'Todays Check-outs', 'woocommerce-booking' ) . $today_checkout_count ),
                'unpaid'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'unpaid', 'paged' => FALSE, 'duration_select' => '', 'bkap_start_date' => '', 'bkap_end_date' => '', 'bkap_from_time' => '', 'bkap_to_time' => '' ) ), $current === 'unpaid' ? ' class="current"' : '', __( 'Unpaid', 'woocommerce-booking' ) . $unpaid ),
                'pending_confirmation'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'pending_confirmation', 'paged' => FALSE, 'duration_select' => '', 'bkap_start_date' => '', 'bkap_end_date' => '', 'bkap_from_time' => '', 'bkap_to_time' => '' ) ), $current === 'pending_confirmation' ? ' class="current"' : '', __( 'Pending Confirmation', 'woocommerce-booking' ) . $pending_confirmation ),
                'gcal_reservations'	=> sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'gcal_reservations', 'paged' => FALSE, 'duration_select' => '', 'bkap_start_date' => '', 'bkap_end_date' => '', 'bkap_from_time' => '', 'bkap_to_time' => '' ) ), $current === 'gcal_reservations' ? ' class="current"' : '', __( 'Reserved By GCal', 'woocommerce-booking' ) . $reserved_by_gcal )
		);
	
		return apply_filters( 'bkap_bookings_table_views', $views );
	}
	
	
	public function get_columns() {
		$columns = array(
				'ID'     		=> __( 'Order ID', 'woocommerce-booking' ),
				'name'  		=> __( 'Customer Name', 'woocommerce-booking' ),
				'product_name'  => __( 'Product Name', 'woocommerce-booking' ),
				'checkin_date'  => __( 'Check-in Date', 'woocommerce-booking' ),
				'checkout_date' => __( 'Check-out Date', 'woocommerce-booking' ),
				'booking_time'  => __( 'Booking Time', 'woocommerce-booking' ),
				'quantity'  	=> __( 'Quantity', 'woocommerce-booking' ),
				'amount'  		=> __( 'Amount', 'woocommerce-booking' ),
				'order_date'  	=> __( 'Order Date', 'woocommerce-booking' ),
				'actions'  		=> __( 'Actions', 'woocommerce-booking' )
		);
		
		return apply_filters( 'bkap_view_bookings_table_columns', $columns );
	}
	
	public function get_sortable_columns() {
		$columns = array(
				'ID' 			=> array( 'ID', true ),
				'amount'		=> array( 'amount',false),
				'quantity'		=> array( 'quantity',false),
				'order_date'	=> array( 'order_date',false),
				'checkin_date'	=> array( 'checkin_date',false),
				'checkout_date'	=> array( 'checkout_date',false),
				'name'			=> array( 'name',false),
				'product_name' 	=> array( 'product_name',false)
		);
		return apply_filters( 'bkap_view_bookings_sortable_columns', $columns );
		
	}
	public function advanced_filters() {
		$search       = isset( $_GET['search'] )  ? sanitize_text_field( $_GET['search'] ) : null;
		$status       = isset( $_GET['status'] )      ? $_GET['status'] : '';
		if ( isset( $_GET['status'] ) ){
		    echo '<input type="hidden" name="status" value="' . esc_attr( $status ) . '" />';
		}
		?>
		<div id="view-bookings-filters">
			<?php $this->search_box( __( 'Search', 'woocommerce-booking' ), 'bkap-bookings' ); ?>
		</div>
	   <?php
	}
	
	public function advanced_filters_by_date() {
	    ?>
        	<div id="view-bookings-date-filters" class = "view-bookings-date-filters">
    			<?php $this->search_by_date( __( 'Filter', 'woocommerce-booking' ), 'bkap-bookings' ); ?>
				<?php do_action('bkap_order_status'); ?>
    		</div>
		<?php
	}
		
	public function search_by_date( $text, $input_id ) {
		
	    $input_id = $input_id . '-search-by-date-input';
	    
	     
	    $duration_range = "select_period";
        if ( isset( $_GET['duration_select'] ) && '' != $_GET['duration_select'] ) {
            $duration_range = $_GET['duration_select'];
        }
	   
	   ?>
        <div class = "main_start_end_date" id = "main_start_end_date">
        
         <br>
         <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
         
         <div class = "filter_date_drop_down" id = "filter_date_drop_down">
         <label class="date_time_filter_label" for="date_time_filter_label" > <strong><?php _e( "Bookings Over Time", "woocommerce-booking"); ?>:</strong></label>
            <select id="duration_select" name="duration_select" >
                    <?php
                    foreach ( $this->duration_range_select as $key => $value ) {
                        $sel = "";
                        
                        if ( $key == $duration_range ) {
                            $sel = __( "selected ", "woocommerce-booking" );
                        } 
                        echo"<option value='" . $key . "' $sel> " . __( $value,'woocommerce-booking' ) . " </option>";
                    }
                    if ( isset( $this->start_end_dates[ $duration_range ] ) ){
                        $date_sett = $this->start_end_dates[ $duration_range ];   
                    }                      
                    ?>
            </select>
          </div>

		  <?php do_action('bkap_filter_by_vehicle'); ?>
	         
         <script type="text/javascript">
          
                    jQuery( document ).ready( function() {
                        var formats = [ "d.m.y", "d M yy", "MM d, yy" ];
                        jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "en-GB" ] );
                        jQuery( "#bkap_start_date" ).datepicker({
                        	onSelect: function(date) {
                                jQuery( '#bkap_end_date' ) . val( date );
                             }, 
                            dateFormat: formats[1] 
                        } );
                    } );
                    
                    jQuery( document ).ready( function() {
                        var formats = [ "d.m.y", "d M yy","MM d, yy" ];
                        jQuery( "#bkap_end_date" ).datepicker( { dateFormat: formats[1] } );

                    } );

                    jQuery( document ).ready( function() {
                        jQuery('#bkap_from_time').click(function() {
                            document.getElementById("bkap_time_drop_down").style.display = "block";
                        });

                        jQuery('#bkap_to_time').click(function() {
                            document.getElementById("bkap_to_drop_down").style.display = "block";
                        });

                        jQuery('ul.bkap_from_time_ul li').click(function() {
                     	    var selected_value = jQuery(this).attr('data-value');
                     	    document.getElementById("bkap_from_time").value = selected_value;
                        });

                        jQuery('ul.bkap_to_time_ul li').click(function() {
                     	    var selected_value = jQuery(this).attr('data-value');
                        	document.getElementById("bkap_to_time").value = selected_value;
                        });
                   } );

                    // Close the dropdown if the user clicks outside of it
                    window.onclick = function(event) {
                        if ( !event.target.matches( '#bkap_from_time' ) && !event.target.matches( '#bkap_time_drop_down' )  ) {
                        	document.getElementById("bkap_time_drop_down").style.display = "none";
                    	}
                        if ( !event.target.matches( '#bkap_to_time' ) && !event.target.matches( '#bkap_to_drop_down' )  ) {
                        	document.getElementById("bkap_to_drop_down").style.display = "none";
                    	}
                    }

                    jQuery( '#duration_select' ).change( function() {
                         
                        if ( jQuery(this).val() == "other") {
                        	document.getElementById("start_end_date_div").style.display = "block";
                        }
                        if ( jQuery(this).val() != "other" ) {
                        	document.getElementById("start_end_date_div").style.display = "none";
                        }
        
        				var group_name  = jQuery( '#duration_select' ).val();
        
        				var today       = new Date();
                        var start_date  = "";
                        var end_date    = "";
            
                        if ( group_name == "yesterday" ) {
                            start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 1 ); 
                            end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 1 );
                        } else if ( group_name == "today" || group_name == "other") {
                            start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                            end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                        } else if ( group_name == "last_seven" ) {

                        	/*
                        	* It will fetch the current week start & end dates.
                        	* It will count from Sunday to Saturday
                        	*/
                        	
                        	var curr = new Date; // get current date
                        	var first = curr.getDate() - curr.getDay(); // First day is the day of the month - the day of the week
                        	var last = first + 6; // last day is the first day + 6

                        	var firstday = new Date(curr.setDate(first));
                        	var lastday = new Date(curr.setDate(last));
                        	
                            start_date = firstday;
                            end_date   = lastday;
                        } else if ( group_name == "last_fifteen" ) {

                        	/*
                        	* This will fetch the current 15 days start & end date according the current date.
                        	* If current date is greater than 15 then start date will be from 16 to the end of the month date.
                        	*/
                        	var curr = new Date, y = curr.getFullYear(), m = curr.getMonth() ; // get current date
                        	var first_fifteen = '';
                        	var last_fifteen = ''; 
                        	
                            var current_date = curr.getDate();
                            if ( current_date <= 15 ){

                            	first_fifteen = new Date(y, m, 1);
                            	last_fifteen = new Date(y, m , 15); 
                            }else{

                            	first_fifteen = new Date(y, m, 16);
                            	last_fifteen = new Date(y, m + 1 , 0 );
                        	}

                            start_date = first_fifteen;
                            end_date   = last_fifteen;
                        } else if ( group_name == "last_thirty" ) {

                        	/*
                        	* This will fetch the current month start & end date
                        	*/
                        	var date = new Date(), y = date.getFullYear(), m = date.getMonth();

                        	var firstDay = new Date(y, m, 1);
                        	var lastDay = new Date(y, m + 1, 0);

                        	start_date = firstDay;
                            end_date   = lastDay;
                        }  else if ( group_name == "last_year_days" ) {
                        	var start_of_year = new Date(new Date().getFullYear(), 0, 1);
                            var end_of_year = new Date(new Date().getFullYear(), 11, 31);

                            start_date = start_of_year;
                            end_date   = end_of_year;
                        }else if ( group_name == "select_period" ) {
                            start_date = "";
                            end_date   = "";
                        }
                        var monthNames       = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];   
                        var start_date_value = start_date.getDate() + " " + monthNames[ start_date.getMonth() ] + " " + start_date.getFullYear();
                        var end_date_value   = end_date.getDate() + " " + monthNames[ end_date.getMonth() ] + " " + end_date.getFullYear();
                        jQuery( '#bkap_start_date' ) . val( start_date_value );
                        jQuery( '#bkap_end_date' ) . val( end_date_value );
                    });
        
            </script>  
            <?php 
                
                if ( isset( $_GET['bkap_start_date'] ) ) $start_date_range = $_GET['bkap_start_date'];
                else $start_date_range = "";
        
                if ( $start_date_range == "" ) {
                    $start_date_range = $date_sett['start_date'];
                }
        
                if ( isset( $_GET['bkap_end_date'] ) ) $end_date_range = $_GET['bkap_end_date'];
                else $end_date_range = "";
                
                if ( $end_date_range == "" ) {
                    $end_date_range = $date_sett['end_date'];
                }
                
                $start_end_date_div_show = 'block';
                if ( !isset($_GET['duration_select']) || $_GET['duration_select'] != 'other' ) {
                    $start_end_date_div_show = 'none';
                }
            ?>
            <div class = "start_end_date_div" id = "start_end_date_div" style="display:<?php echo $start_end_date_div_show; ?>" >
                <label class="start_label" for="start_day"> <?php _e( 'Start Date:', 'woocommerce-booking' ); ?> </label>
                <input type="text" id="bkap_start_date" name="bkap_start_date" readonly="readonly" value="<?php echo $start_date_range; ?>"/>     
                <label class="end_label" for="end_day"> <?php _e( 'End Date:', 'woocommerce-booking' ); ?> </label>
                <input type="text" id="bkap_end_date" name="bkap_end_date" readonly="readonly" value="<?php echo $end_date_range; ?>"/>
                <div class="from_to_time_div" id = "from_to_time_div" >
                    <div class="from_time_div" id = "from_time_div">
                        <label class="from_time_label" for="from_time_label"> <?php _e( 'From Time:', 'woocommerce-booking' ); ?> </label>
                        <input type="text" id="bkap_from_time" class="bkap_from_time" name="bkap_from_time" value="<?php ?>"/>
                    
                        <div class = "bkap_time_drop_down" id = "bkap_time_drop_down">
                            <ul class="bkap_from_time_ul">
                                    <?php
                                        $from_time_array = array();
                                        
                                        $start = "00:00";
                                        $end = "23:59";
                                        
                                        $from_start_ts = strtotime($start);
                                        $to_end_ts = strtotime($end);
                                        $now_ts = $from_start_ts;
                                        
                                        while($now_ts <= $to_end_ts){
                                            $from_time = date("H:i",$now_ts)."\n";
                                            $now_ts = strtotime('+15 minutes',$now_ts);
                                            $from_time_array[] = $from_time;
                                        }
                                        
                                        foreach ( $from_time_array as $key => $value ) {
                                            
                                            echo"<li data-value = '".$value."' style = 'text-align: center;'> " . __( $value,'woocommerce-booking' ) . " </li>";
                                        }
                                                             
                                    ?>
                            </ul>
                        </div>
                    </div>
                    <div class="to_time_div" id = "to_time_div">
                        <label class="to_time_label" for="to_time_label"> <?php _e( 'To Time:', 'woocommerce-booking' ); ?> </label>
                        <input type="text" id="bkap_to_time" class="bkap_to_time" name="bkap_to_time" value="<?php  ?>"/>
                    
                        <div class = "bkap_to_drop_down" id = "bkap_to_drop_down">
                            <ul class="bkap_to_time_ul">
                                    <?php
                    	                $to_time_array = array();
                                        
                                        $start = "00:00";
                                        $end = "23:59";
                                        
                                        $from_start_ts = strtotime($start);
                                        $to_end_ts = strtotime($end);
                                        $now_ts = $from_start_ts;
                                        
                                        while($now_ts <= $to_end_ts){
                                            $to_time = date("H:i",$now_ts)."\n";
                                            $now_ts = strtotime('+15 minutes',$now_ts);
                                            $to_time_array[] = $to_time;
                                        }
                                        
                                        foreach ( $to_time_array as $key => $value ) {
                                            
                                            echo"<li data-value = '".$value."' style = 'text-align: center;'> " . __( $value,'woocommerce-booking' ) . " </li>";
                                        }
                                                             
                                    ?>
                            </ul>
                        </div>
                  </div>
               </div> 
            </div>
            
          <?php submit_button( $text, 'button', false, false, array('ID' => 'search-by-date-submit' ) ); ?>
           
        </div>
       
	    <?php
		    
	}
		
		
		
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && !$this->has_items() ){
			return;
		}
		$input_id = $input_id . '-search-input';
		
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
		?>
		<p class="search-box">
			<?php do_action( 'booking_search' ); ?>
			<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
			<?php submit_button( $text, 'button', false, false, array('ID' => 'search-submit' ) ); ?><br/>
		</p>
		<?php
	}
	
	public function get_booking_counts() {
		$args = array();
		if( isset( $_GET[ 'user' ] ) ) {
			$args[ 'user' ] = urldecode( $_GET[ 'user' ] );
		} elseif( isset( $_GET[ 's' ] ) ) {
			$args[ 's' ] = urldecode( $_GET[ 's' ] );
		}
	
		if ( ! empty( $_GET[ 'start-date' ] ) ) {
			$args[ 'start-date' ] = urldecode( $_GET[ 'start-date' ] );
		}
	
		if ( ! empty( $_GET[ 'end-date' ] ) ) {
			$args[ 'end-date' ] = urldecode( $_GET[ 'end-date' ] );
		}
	
		$bookings_count               = $this->bkap_count_bookings( $args );
		
		$this->total_count            = $bookings_count[ 'total_count' ];
		$this->future_count           = $bookings_count[ 'future_count' ];
		$this->today_checkin_count    = $bookings_count[ 'today_checkin_count' ];
		$this->today_checkout_count   = $bookings_count[ 'today_checkout_count' ];
		$this->unpaid                 = $bookings_count[ 'unpaid' ];
		$this->pending_confirmation   = $bookings_count[ 'pending_confirmation' ];
		$this->gcal_reserved          = $bookings_count[ 'gcal_reservations' ];
        
		$this->total_count = apply_filters( 'bkap_total_count', $this->total_count );
		$this->future_count = apply_filters( 'bkap_total_count', $this->future_count );
		$this->today_checkin_count = apply_filters( 'bkap_total_count', $this->today_checkin_count );
		$this->today_checkout_count = apply_filters( 'bkap_total_count', $this->today_checkout_count );
	}
	
	public function bkap_count_bookings( $args ) {
		global $wpdb;
		$bookings_count = array(
			'total_count' => 0,
			'future_count' => 0,
			'today_checkin_count' => 0,
			'today_checkout_count' => 0,
            'unpaid' => 0,
            'pending_confirmation' => 0,
            'gcal_reservations' => 0
        );
		
		//Today's date
		$current_time = current_time( 'timestamp' );
		$current_date = date( "Y-m-d", $current_time );
		$start_date   = $end_date = '';

		if ( isset( $args[ 'start-date' ] ) ) {
			$start_date = $args[ 'start-date' ];
		}
		
		if ( isset( $args[ 'end-date' ] ) ) {
			$end_date = $args[ 'end-date' ];
		}
		
		if ( isset( $_GET[ 's' ] ) && $_GET[ 's' ] != '' ) {
		    if ( is_numeric( $_GET[ 's' ] ) ) {
		        $order_number = $_GET[ 's' ];
		    } else {
		        // strtotime does not support all date formats. hence it is suggested to use the "DateTime date_create_from_format" fn
		        $date_formats    =   bkap_get_book_arrays( 'bkap_date_formats' );
		        // get the global settings to find the date formats
		        $global_settings =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		        $date_format_set =   $date_formats[ $global_settings->booking_date_format ];
		        $date_formatted  =   date_create_from_format( $date_format_set, $_GET[ 's' ] );
		        if ( isset( $date_formatted ) && $date_formatted != '' ) {
		            $date = date_format( $date_formatted, 'Y-m-d' );
		        }
		         
		        if ( strpos( $_GET[ 's' ], '-' ) && substr_count( $_GET[ 's' ], '-' ) == '1' ) {
		            $time_array = explode( '-', $_GET['s'] );
		            if ( isset( $time_array[0] ) && $time_array[0] != '' ) {
		                $from_time = date( 'G:i', strtotime( trim( $time_array[0] ) ) );
		            }
		            if ( isset( $time_array[1] ) && $time_array[1] !== '' ) {
		                $to_time = date( 'G:i', strtotime( trim( $time_array[1] ) ) );
		            }
		        }
		         
		        $search_result =  $_GET[ 's' ];
		    }
		}
		
		$args_pagination = false;
		if ( isset( $args[ 'pagination-call' ] ) && $args[ 'pagination-call' ] == 'yes' ) {
		    $args_pagination = true;
		}
		
		$get_s = '';
		if ( isset( $_GET[ 's' ] ) ) {
		    $get_s = $_GET[ 's' ];
		}
		
		$get_duration = '';
		if ( isset(  $_GET['duration_select'] ) ) {
		    $get_duration =  $_GET['duration_select'];
		}
		
		$time_query = '';
		if ( isset( $_GET['bkap_start_date'] ) && isset( $_GET['bkap_end_date'] ) ) {
    		$start_date_ts = strtotime( $_GET[ 'bkap_start_date' ] );
    		$start_date    = date( "Y-m-d",$start_date_ts);
    		 
    		$end_date_ts = strtotime( $_GET[ 'bkap_end_date' ] );
    		$end_date    = date( "Y-m-d",$end_date_ts);
    		
    		$user_set_from_time = false;
    		$user_set_to_time = false;
    		
    		if (isset($_GET ['bkap_from_time']) && $_GET ['bkap_from_time'] !=''){
    		    $user_selected_from_time = $_GET ['bkap_from_time'];
    		    $user_set_from_time = true;
    		}else{
    		    $user_selected_from_time = "00:00";
    		}
    		
    		if ( isset($_GET ['bkap_to_time']) && $_GET ['bkap_to_time'] !='' ){
    		    $user_selected_to_time   = $_GET ['bkap_to_time'];
    		    $user_set_to_time = true;
    		}else{
    		    $user_selected_to_time = "23:59";
    		}
    		
    		$time_query = '';
    		if ( $user_set_from_time && $user_set_to_time ){
    		
    		    $time_query = " AND DATE_FORMAT( STR_TO_DATE( a1.`from_time` ,  '%H:%i' ) ,  '%H:%i' ) BETWEEN  '".$user_selected_from_time."' AND  '".$user_selected_to_time."' " ;
    		}
		}	

	    $results_date = array();
	    $limit = '';
	    
	    if ( $start_date != '' && $end_date != '' && $start_date != '1970-01-01' && $end_date != '1970-01-01' ) {
	    } else {
	        $today_query = "SELECT a2.order_id,a1.start_date,a1.end_date,a1.post_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id GROUP BY a2.order_id, a1.post_id $limit";
	        $results_date = $wpdb->get_results ( $today_query );
	        
	        if( $args_pagination ) {
	            if( isset( $order_number ) && $order_number != "" ) {
	                $today_query = "SELECT a2.order_id,a1.start_date,a1.end_date,a1.post_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id = '" . $get_s . "' GROUP BY a2.order_id,a1.post_id $limit";
	                $results_date = $wpdb->get_results ( $today_query );
	            } else if( isset( $date ) && $date != "" ) {
	                $today_query = "SELECT a1.post_id,a1.start_date,a1.end_date,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND ( a1.start_date = '" . $date . "' OR a1.end_date = '" . $date . "' ) GROUP BY a2.order_id,a1.post_id ORDER BY a2.order_id DESC $limit";
	                $results_date = $wpdb->get_results ( $today_query );
	            } else if( isset( $from_time ) && $from_time != "" ) {
	                $today_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.from_time = '" . $from_time . "' AND a1.to_time = '" . $to_time . "' GROUP BY a2.order_id,a1.post_id ORDER BY a2.order_id DESC $limit";
	                $results_date = $wpdb->get_results ( $today_query );
	            } else if ( isset( $get_duration ) && $get_duration != '' && $get_s == ''  ) {
	                
	                $today_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date BETWEEN '".$start_date."' AND '".$end_date ."' ". $time_query . " ORDER BY a2.order_id DESC $limit";
	    
	                $results_date = $wpdb->get_results ( $today_query );
	            } else if( isset( $search_result ) && $search_result != "" ) {
	                $today_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.booking_id IN ( SELECT j1.booking_id FROM `" . $wpdb->prefix . "woocommerce_order_items` AS j2, `" . $wpdb->prefix . "booking_order_history` AS j1 WHERE j2.order_item_type = 'line_item' AND j2.order_item_name LIKE '%" . $search_result . "%' AND j1.order_id = j2.order_id AND j1.booking_id IN ( SELECT k1.id FROM `" . $wpdb->prefix . "booking_history` AS k1, `" . $wpdb->prefix . "posts` AS k2 WHERE k2.post_title LIKE '%" . $search_result . "%' AND k2.ID = k1.post_id ) ) GROUP BY a2.order_id,a1.post_id ORDER BY a2.order_id DESC $limit";
	                $results_date = $wpdb->get_results ( $today_query );
	                if( count( $results_date ) == 0 ) {
	                    $today_query = "SELECT a1.post_id,a1.start_date,a1.end_date,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id IN ( SELECT post_id FROM `" . $wpdb->prefix . "postmeta` AS p1 WHERE ( SELECT GROUP_CONCAT( `meta_value` SEPARATOR ' ' ) FROM `" . $wpdb->prefix . "postmeta` AS p2 WHERE `meta_key` IN ( '_billing_first_name', '_billing_last_name' ) AND p1.post_id = p2.post_id ) LIKE '%" . $search_result . "%' ) GROUP BY a2.order_id,a1.post_id ORDER BY a2.order_id DESC $limit";
	                    $results_date = $wpdb->get_results ( $today_query );
	                }
	                if( count( $results_date ) == 0  ) {
	                    $today_query = "SELECT a1.post_id,a1.start_date,a1.end_date,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id IN ( SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE post_modified LIKE '%" . $search_result . "%' AND post_type = 'shop_order' ) GROUP BY a2.order_id,a1.post_id ORDER BY a2.order_id DESC $limit";
	                    $results_date = $wpdb->get_results ( $today_query );
	                }
	            }
	        }
	    }
	    
	    $bookings_count = self::get_counts( $results_date, $bookings_count );	    
		
		return $bookings_count;
	}

	public function get_counts( $results_array, $bookings_count ) {
	     
	    global $wpdb;
	    //Today's date
	    $current_time = current_time( 'timestamp' );
	    $current_date = date( "Y-m-d", $current_time );
	     
	    foreach ( $results_array as $key => $value ) {
	        $order_id = $value->order_id;
	        $post_id = $value->post_id;
	         
	        $post_data = get_post( $order_id );
	
	        if ( isset( $post_data->post_status ) && $post_data->post_status != 'wc-refunded' && $post_data->post_status != 'trash' && $post_data->post_status != 'wc-cancelled' && $post_data->post_status != '' && $post_data->post_status != 'wc-failed' ) {
	            // Order details
	            $created_via = get_post_meta( $order_id, '_created_via', true );
	
	            $get_items_sql  = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_id, order_item_name FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = %s", $order_id, 'line_item' ) );
	             
	            $item_list = array();
	            foreach ( $get_items_sql as $i_key => $i_value ) {
	                $get_items = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_id, meta_key, meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key IN ( '_product_id', '_wapbk_booking_status' ) AND order_item_id = %d" , $i_value->order_item_id ) );
	                
	                if ( is_array( $get_items ) && count( $get_items ) > 0 ) {
	                    foreach( $get_items as $i_value ) {
	                        switch ( $i_value->meta_key ) {
	                            case '_wapbk_booking_status':
	                                $item_list[ $i_value->order_item_id ][ 'wapbk_booking_status' ] = $i_value->meta_value;
	                                break;
	                            case '_product_id':
	                                $item_list[ $i_value->order_item_id ][ 'product_id' ] = $i_value->meta_value;
	                                break;
	                            default:
	                                break;
	                        }
	                    }
	                }
	                 
	            }
	
	            if ( is_array( $item_list ) && count( $item_list ) > 0 ) {
    	            foreach ( $item_list as $item_key => $item_values ) {
    	                $booking_status = '';
    	                $duplicate_of = bkap_common::bkap_get_product_id( $item_values[ 'product_id' ] );
    	
    	                if ( $post_id == $duplicate_of ) {
    	                    if ( isset( $item_values[ 'wapbk_booking_status' ] ) ) {
    	                        $booking_status = $item_values[ 'wapbk_booking_status' ];
    	                    }
    	                    if ( isset( $booking_status ) ) {
    	                        // if it's not cancelled, add it to the All count
    	                        if ( 'cancelled' != $booking_status ) {
    	                            $bookings_count['total_count'] += 1;
    	                        }
    	                        // Unpaid count
    	                        if ( 'confirmed' == $booking_status ) {
    	                            $bookings_count[ 'unpaid' ] += 1;
    	                        } else if( 'pending-confirmation' == $booking_status ) { // pending confirmation count
    	                            $bookings_count[ 'pending_confirmation' ] += 1;
    	                        } else if ( 'paid' == $booking_status || '' == $booking_status ) {
    	                            if ( $value->start_date >= $current_date ) { // future count
    	                                $bookings_count[ 'future_count' ] += 1;
    	                            }
    	                            if ( $value->start_date == $current_date ) { // today's checkin's
    	                                $bookings_count['today_checkin_count'] += 1;
    	                            }
    	                            if ( $value->end_date == $current_date ) { // today's checkouts
    	                                $bookings_count['today_checkout_count'] += 1;
    	                            }
    	                        }
    	                        if ( isset( $created_via ) && $created_via == 'GCal' ) {
    	                            $bookings_count[ 'gcal_reservations' ] += 1;
    	                        }
    	                    }
    	                }
    	            }
	            }
	             
	        }
	    }
	    return $bookings_count;
	}
	
	public function bookings_data() { 
		global $wpdb;
		
		$return_bookings  = array();
		
		if ( isset( $_GET[ 'paged' ] ) && $_GET[ 'paged' ] > 1 ) {
		    $page_number = $_GET[ 'paged' ] - 1;
		} else {
		    $page_number = 0;
		}
		
		$per_page         = $this->per_page;
		
		$results          = array();
		$current_time     = current_time( 'timestamp' );
		$current_date     = date( "Y-m-d", $current_time );
	    
	$date = '';
		$order_number = '';
		$from_time = '';
		$to_time = '';
		
		$get_s = '';
		if ( isset( $_GET[ 's' ] ) ) {
		    $get_s = $_GET[ 's' ];
		}
		
		$get_duration = '';
		if ( isset(  $_GET['duration_select'] ) ) {
		    $get_duration =  $_GET['duration_select'];
		}
		
		if ( $get_s != '' ) {
		    if ( is_numeric( $get_s ) ) {
		        $order_number = $get_s;
		    } else {
		        // strtotime does not support all date formats. hence it is suggested to use the "DateTime date_create_from_format" fn
		        $date_formats    =   bkap_get_book_arrays( 'bkap_date_formats' );
		        // get the global settings to find the date formats
		        $global_settings =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		        $date_format_set =   $date_formats[ $global_settings->booking_date_format ];
		        $date_formatted  =   date_create_from_format( $date_format_set, $get_s );
		        if ( isset( $date_formatted ) && $date_formatted != '' ) {
		            $date = date_format( $date_formatted, 'Y-m-d' );
		        }
		
		        if ( strpos( $get_s, '-' ) && substr_count( $get_s, '-' ) == '1' ) {
		            $time_array = explode( '-', $get_s );
		            if ( isset( $time_array[0] ) && $time_array[0] != '' ) {
		                $from_time = date( 'G:i', strtotime( trim( $time_array[0] ) ) );
		            }
		            if ( isset( $time_array[1] ) && $time_array[1] !== '' ) {
		                $to_time = date( 'G:i', strtotime( trim( $time_array[1] ) ) );
		            }
		        }
		
		        $search_result =  $get_s;
		    }
		}
		
		if ( isset( $_GET[ 'bkap_start_date' ] ) && isset( $_GET[ 'bkap_end_date' ] ) ) {
    		$start_date_ts = strtotime( $_GET[ 'bkap_start_date' ] );
    		$start_date    = date( "Y-m-d",$start_date_ts);
    			
    		$end_date_ts = strtotime( $_GET[ 'bkap_end_date' ] );
    		$end_date    = date( "Y-m-d",$end_date_ts);
    		
    		$user_set_from_time = false;
    		$user_set_to_time = false;
    		
    		if ( isset( $_GET[ 'bkap_from_time' ] ) && $_GET[ 'bkap_from_time' ] != '' ) {
    		    $user_selected_from_time = $_GET[ 'bkap_from_time' ];
    		    $user_set_from_time = true;
    		}else{
    		    $user_selected_from_time = "00:00";
    		}
    		
    		if ( isset( $_GET[ 'bkap_to_time' ] ) && $_GET[ 'bkap_to_time' ] != '' ) {
    		    $user_selected_to_time   = $_GET[ 'bkap_to_time' ];
    		    $user_set_to_time = true;
    		}else{
    		    $user_selected_to_time = "23:59";
    		}
    		
    		$time_query = '';
    		if ( $user_selected_from_time && $user_set_to_time ) {
    		    //	        $user_selected_to_time   = $_GET ['bkap_to_time'];
    		    //	        $user_selected_from_time = $_GET ['bkap_from_time'];
    		
    		    $time_query = " AND DATE_FORMAT( STR_TO_DATE( a1.`from_time` ,  '%H:%i' ) ,  '%H:%i' ) BETWEEN  '".$user_selected_from_time."' AND  '".$user_selected_to_time."' " ;
    		}
	   }
		if ( isset( $_GET[ 'paged' ] ) && $_GET[ 'paged' ] > 0 ) {
		    $start_record = ( $_GET[ 'paged' ] - 1 )* $per_page;
		    $start_record += 1;
		    $limit = "LIMIT $start_record, $per_page ";
		} else {
		    $limit = "LIMIT $per_page";
		}
		
		
		
        /* 
         * Below is the soring swtich case if the records are being sorted based on Order Id,
         * Check-in Date and Check-out Date then it should consider all the Bookings on website for sorting.
         * 
         */
		$order_ID_sorting = "a2.order_id DESC";
        if ( isset( $_GET['orderby'] ) ) {
    		switch ( $_GET['orderby'] ) {
    		    case "ID":
    		        echo "Your favorite color is red!";
    		        if($_GET['order'] == "asc"){
    		            $order_ID_sorting = "a2.order_id ASC";
    		        }else{
    		            $order_ID_sorting = "a2.order_id DESC";
    		        }
    		        break;
    		    case "checkin_date":
    		        
    		        if ( $_GET['order'] == "asc" ){
    		            $order_ID_sorting = "a1.start_date ASC";
    		        }else{
    		            $order_ID_sorting = "a1.start_date DESC";
    		        }
    		        
    		        break;
    		    case "checkout_date":
    		        
    		        if ( $_GET['order'] == "asc" ){
    		            $order_ID_sorting = "a1.end_date ASC";
    		        }else{
    		            $order_ID_sorting = "a1.end_date DESC";
    		        }
    		        
    		        break;
    		    default:
    		        $order_ID_sorting = "a2.order_id DESC";
    		}
        }
		
		
	if ( isset( $_GET['status'] ) && $_GET['status'] == 'future' ) {
			$booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date >= '".$current_date."' AND a2.order_id IN ( SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE post_type = 'shop_order' AND post_status NOT IN ('wc-cancelled', 'wc-refunded', 'trash', 'wc-failed', '') ) ORDER BY ".$order_ID_sorting." $limit";
			$query_results         = $wpdb->get_results( $booking_query );
			
			if( isset( $order_number ) && $order_number != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date >= '" . $current_date . "' AND a2.order_id = '" . $order_number . "' ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			} else if( isset( $date ) && $date != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date >= '" . $current_date . "' AND ( a1.start_date = '" . $date . "' OR a1.end_date = '" . $date . "' ) ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			} else if( $from_time != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date >= '" . $current_date . "' AND a1.from_time = '" . $from_time . "' AND a1.to_time = '" . $to_time . "' ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			} else if( isset( $search_result ) && $search_result != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date >= '" . $current_date . "' AND a2.booking_id IN ( SELECT j1.booking_id FROM `" . $wpdb->prefix . "woocommerce_order_items` AS j2, `" . $wpdb->prefix . "booking_order_history` AS j1 WHERE j2.order_item_type = 'line_item' AND j2.order_item_name LIKE '%" . $search_result . "%' AND j1.order_id = j2.order_id AND j1.booking_id IN ( SELECT k1.id FROM `" . $wpdb->prefix . "booking_history` AS k1, `" . $wpdb->prefix . "posts` AS k2 WHERE k2.post_title LIKE '%" . $search_result . "%' AND k2.ID = k1.post_id ) ) ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			    if( count( $query_results ) == 0 ) {
			        $booking_query = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date >= '" . $current_date . "' AND a2.order_id IN ( SELECT post_id FROM `" . $wpdb->prefix . "postmeta` AS p1 WHERE ( SELECT GROUP_CONCAT( `meta_value` SEPARATOR ' ' ) FROM `" . $wpdb->prefix . "postmeta` AS p2 WHERE `meta_key` IN ( '_billing_first_name', '_billing_last_name' ) AND p1.post_id = p2.post_id ) LIKE '%" . $search_result . "%' ) ORDER BY ".$order_ID_sorting." $limit";
			        $query_results   = $wpdb->get_results( $booking_query );
			    }
			     
			    if( count( $query_results ) == 0  ) {
			        $booking_query = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date >= '" . $current_date . "' AND a2.order_id IN ( SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE post_modified LIKE '%" . $search_result . "%' AND post_type = 'shop_order' ) ORDER BY ".$order_ID_sorting."";
			        $query_results   = $wpdb->get_results( $booking_query );
			    }
			}
		}
		else if ( isset( $_GET['status'] ) && $_GET['status'] == 'today_checkin' ) {
			$booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date = '".$current_date."' AND a2.order_id IN ( SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE post_type = 'shop_order' AND post_status NOT IN ('wc-cancelled', 'wc-refunded', 'trash', 'wc-failed', '') ) ORDER BY ".$order_ID_sorting." $limit";
			$query_results         = $wpdb->get_results( $booking_query );
			if( isset( $order_number ) && $order_number != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date = '" . $current_date . "' AND a2.order_id = '" . $order_number . "' ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			} else if( isset( $date ) && $date != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date = '" . $current_date . "' AND ( a1.start_date = '" . $date . "' OR a1.end_date = '" . $date . "' ) ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			} else if( $from_time != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date = '" . $current_date . "' AND a1.from_time = '" . $from_time . "' AND a1.to_time = '" . $to_time . "' ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			} else if( isset( $search_result ) && $search_result != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date = '" . $current_date . "' AND a2.booking_id IN ( SELECT j1.booking_id FROM `" . $wpdb->prefix . "woocommerce_order_items` AS j2, `" . $wpdb->prefix . "booking_order_history` AS j1 WHERE j2.order_item_type = 'line_item' AND j2.order_item_name LIKE '%" . $search_result . "%' AND j1.order_id = j2.order_id AND j1.booking_id IN ( SELECT k1.id FROM `" . $wpdb->prefix . "booking_history` AS k1, `" . $wpdb->prefix . "posts` AS k2 WHERE k2.post_title LIKE '%" . $search_result . "%' AND k2.ID = k1.post_id ) ) ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			    if( count( $query_results ) == 0 ) {
			        $booking_query = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date = '" . $current_date . "' AND a2.order_id IN ( SELECT post_id FROM `" . $wpdb->prefix . "postmeta` AS p1 WHERE ( SELECT GROUP_CONCAT( `meta_value` SEPARATOR ' ' ) FROM `" . $wpdb->prefix . "postmeta` AS p2 WHERE `meta_key` IN ( '_billing_first_name', '_billing_last_name' ) AND p1.post_id = p2.post_id ) LIKE '%" . $search_result . "%' ) ORDER BY ".$order_ID_sorting." $limit";
			        $query_results   = $wpdb->get_results( $booking_query );
			    }
			     
			    if( count( $query_results ) == 0  ) {
			        $booking_query = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date = '" . $current_date . "' AND a2.order_id IN ( SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE post_modified LIKE '%" . $search_result . "%' AND post_type = 'shop_order' ) ORDER BY ".$order_ID_sorting." $limit";
			        $query_results   = $wpdb->get_results( $booking_query );
			    }
			}
		}
		else if ( isset( $_GET['status'] ) && $_GET['status'] == 'today_checkout' ) {
			$booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.end_date = '".$current_date."' AND a2.order_id IN ( SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE post_type = 'shop_order' AND post_status NOT IN ('wc-cancelled', 'wc-refunded', 'trash', 'wc-failed', '') ) ORDER BY ".$order_ID_sorting." $limit";
			$query_results         = $wpdb->get_results( $booking_query );
			if( isset( $order_number ) && $order_number != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.end_date = '" . $current_date . "' AND a2.order_id = '" . $order_number . "' ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			} else if( isset( $date ) && $date != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.end_date = '" . $current_date . "' AND ( a1.start_date = '" . $date . "' OR a1.end_date = '" . $date . "' ) ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			} else if( $from_time != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.end_date = '" . $current_date . "' AND a1.from_time = '" . $from_time . "' AND a1.to_time = '" . $to_time . "' ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			} else if( isset( $search_result ) && $search_result != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.end_date = '" . $current_date . "' AND a2.booking_id IN ( SELECT j1.booking_id FROM `" . $wpdb->prefix . "woocommerce_order_items` AS j2, `" . $wpdb->prefix . "booking_order_history` AS j1 WHERE j2.order_item_type = 'line_item' AND j2.order_item_name LIKE '%" . $search_result . "%' AND j1.order_id = j2.order_id AND j1.booking_id IN ( SELECT k1.id FROM `" . $wpdb->prefix . "booking_history` AS k1, `" . $wpdb->prefix . "posts` AS k2 WHERE k2.post_title LIKE '%" . $search_result . "%' AND k2.ID = k1.post_id ) ) ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			    if( count( $query_results ) == 0 ) {
			        $booking_query = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.end_date = '" . $current_date . "' AND a2.order_id IN ( SELECT post_id FROM `" . $wpdb->prefix . "postmeta` AS p1 WHERE ( SELECT GROUP_CONCAT( `meta_value` SEPARATOR ' ' ) FROM `" . $wpdb->prefix . "postmeta` AS p2 WHERE `meta_key` IN ( '_billing_first_name', '_billing_last_name' ) AND p1.post_id = p2.post_id ) LIKE '%" . $search_result . "%' ) ORDER BY ".$order_ID_sorting." $limit";
			        $query_results   = $wpdb->get_results( $booking_query );
			    }
			     
			    if( count( $query_results ) == 0  ) {
			        $booking_query = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.end_date = '" . $current_date . "' AND a2.order_id IN ( SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE post_modified LIKE '%" . $search_result . "%' AND post_type = 'shop_order' ) ORDER BY ".$order_ID_sorting." $limit";
			        $query_results   = $wpdb->get_results( $booking_query );
			    }
			}
		} else if ( isset( $get_duration ) && $get_duration != '' && $get_s == '' ) {
		    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date BETWEEN '".$start_date."' AND '".$end_date ."' ". $time_query . " AND a2.order_id IN ( SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE post_type = 'shop_order' AND post_status NOT IN ('wc-cancelled', 'wc-refunded', 'trash', 'wc-failed', '') ) ORDER BY ".$order_ID_sorting." $limit";
		    
		    $query_results   = $wpdb->get_results( $booking_query );
		}else {
			$booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.order_id,a2.booking_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id IN ( SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE post_type = 'shop_order' AND post_status NOT IN ('wc-cancelled', 'wc-refunded', 'trash', 'wc-failed', '') ) ORDER BY ".$order_ID_sorting." $limit";
			$query_results   = $wpdb->get_results( $booking_query );
			if( isset( $order_number ) && $order_number != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id = '" . $order_number . "'ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			} else if( isset( $date ) && $date != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND ( a1.start_date = '" . $date . "' OR a1.end_date = '" . $date . "' ) ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			} else if( isset( $from_time ) && $from_time != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.from_time = '" . $from_time . "' AND a1.to_time = '" . $to_time . "' ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			} else if( isset( $search_result ) && $search_result != "" ) {
			    $booking_query   = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.booking_id IN ( SELECT j1.booking_id FROM `" . $wpdb->prefix . "woocommerce_order_items` AS j2, `" . $wpdb->prefix . "booking_order_history` AS j1 WHERE j2.order_item_type = 'line_item' AND j2.order_item_name LIKE '%" . $search_result . "%' AND j1.order_id = j2.order_id AND j1.booking_id IN ( SELECT k1.id FROM `" . $wpdb->prefix . "booking_history` AS k1, `" . $wpdb->prefix . "posts` AS k2 WHERE k2.post_title LIKE '%" . $search_result . "%' AND k2.ID = k1.post_id ) ) ORDER BY ".$order_ID_sorting." $limit";
			    $query_results   = $wpdb->get_results( $booking_query );
			    if( count( $query_results ) == 0 ) {
			        $booking_query = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id IN ( SELECT post_id FROM `" . $wpdb->prefix . "postmeta` AS p1 WHERE ( SELECT GROUP_CONCAT( `meta_value` SEPARATOR ' ' ) FROM `" . $wpdb->prefix . "postmeta` AS p2 WHERE `meta_key` IN ( '_billing_first_name', '_billing_last_name' ) AND p1.post_id = p2.post_id ) LIKE '%" . $search_result . "%' ) ORDER BY ".$order_ID_sorting."";
			        $query_results   = $wpdb->get_results( $booking_query );
			    }
			     
			    if( count( $query_results ) == 0  ) {
			        $booking_query = "SELECT a1.post_id,a1.start_date,a1.end_date,a1.from_time,a1.to_time,a2.booking_id,a2.order_id FROM `" . $wpdb->prefix . "booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id IN ( SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE post_modified LIKE '%" . $search_result . "%' AND post_type = 'shop_order' ) ORDER BY ".$order_ID_sorting." $limit";
			        $query_results   = $wpdb->get_results( $booking_query );
			    }
			}
		}

		$addon_query_results = array();
		$addon_query_results = apply_filters( 'bkap_filter_result', $query_results );

		if( isset($addon_query_results) && is_array($addon_query_results) ) {
			$query_results = $addon_query_results;		
		}
	
		$results = array();
		
		$_status = '';
		if ( isset( $_GET[ 'status' ] ) ) {
            $_status = $_GET[ 'status' ];
		}
		
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
	        case 'gcal_reservations':
	            $_status = 'GCal';
	            break;
		    default:
		        $_status = '';
		        break;
		
		}
		
		foreach ( $query_results as $key => $value ) {
		    
		    if ( false !== get_post_status( $value->order_id ) ) {
		        $order            =   new WC_Order( $value->order_id );
		    }else{
		        continue;
		    }
		    
		    $get_items        = $order->get_items();
		    $include_status   = 'NO';
		    
		    $order_status = $order->get_status();
		    $order_status = "wc-$order_status";
		    if( isset( $order_status ) && ( $order_status != '' ) && ( $order_status != 'wc-cancelled' ) && ( $order_status != 'wc-refunded' ) && ( $order_status != 'trash' ) && ( $order_status != 'wc-failed' ) && ( 'auto-draft' != $order_status ) ) {
        	    $created_via = get_post_meta( $value->order_id, '_created_via', true );
        	    
        	    foreach( $get_items as $item_id => $item_values ) {
        	        $booking_status = '';
        	        $duplicate_of = bkap_common::bkap_get_product_id( $item_values[ 'product_id' ] );
        	        if ( $value->post_id == $duplicate_of ) {
        	            if ( isset( $item_values[ 'wapbk_booking_status' ] ) )  {
        	                $booking_status = $item_values[ 'wapbk_booking_status' ];
        	            }
        	
        	            if ( isset( $booking_status ) ) {
        	                if ( $_status == $booking_status ) {
        	                     $include_status = 'YES';
        	                } else if ( ( $booking_status != 'confirmed' && $booking_status != 'pending-confirmation' ) && ( 'paid' == $_status ) ) {
        	                     $include_status = 'YES';
        	                } else if( $booking_status != 'cancelled' && '' == $_status ) {
        	                     $include_status = 'YES';
        	                } else if( 'GCal' == $_status && ( $_status == $created_via ) ) {
        	                     $include_status = 'YES';
        	                }
        	            }
        	        }
        	    }
        	    if( $include_status == 'YES' ) {
        	        $results[] = $query_results[ $key ];
        	    }
		    }
		}
		
		if( count( $results ) > 0 ) {
    		$i = 0;
    		
    		foreach ( $results as $key => $value ) {
    			$time    =   '';
    			// Order details		
    			$order   =   new WC_Order( $value->order_id );
    			// check if the order is refunded, trashed or cancelled
    			$order_status     = $order->get_status();
    			$order_status = "wc-$order_status";
    			if( isset( $order_status ) && ( $order_status != '' ) && ( $order_status != 'wc-cancelled' ) && ( $order_status != 'wc-refunded' ) && ( $order_status != 'trash' ) && ( $order_status != 'wc-failed' ) && ( 'auto-draft' != $order_status ) ) {
    				$return_bookings[$i]        =   new stdClass();
                    if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
    				    $return_bookings[$i]->name  =   $order->billing_first_name . " " . $order->billing_last_name;
    				} else {
    				    $return_bookings[$i]->name  =   $order->get_billing_first_name() . " " . $order->get_billing_last_name();
    				}
    				$get_quantity               =   $order->get_items();
    				
    				// The array needs to be reversed as we r displaying the last item first
    				$get_quantity = array_reverse( $get_quantity, true );
    				
    				foreach( $get_quantity as $k => $v ) {
    					
    				    $attributes_array   =  array();
    				    $var                = '';
    				    $product_exists     = 'NO';
    				    
    				    $duplicate_of       = bkap_common::bkap_get_product_id( $v[ 'product_id' ] );
    				    $attributes         = get_post_meta( $duplicate_of , '_product_attributes' );
    				    
    				    $product            =   wc_get_product( $duplicate_of );
    				    
    				    if ( empty( $product ) ){
    				        break;
    				    }else{
    				        $product_type   =   $product->get_type();
    				    }
    				    
    				    if ( $product_type == 'variable'){
    				    
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
    						
    					    foreach ($return_bookings as $book_key => $book_value ) {
    							
    					        if ( isset ( $book_value->ID ) && $book_value->ID == $value->order_id && $duplicate_of == $book_value->product_id ) {
    								
    					            if ( isset ( $book_value->item_id ) && $k == $book_value->item_id ) {
    									$product_exists = 'YES';
    								}
    							}
    						}
    						
    						if ( $product_exists == 'NO' ) {
    							$selected_quantity                 = $v['qty'];
    							$amount                            = $v['line_total'] + $v['line_tax'];
    							$return_bookings[ $i ]->item_id    = $k;
    							break;
    						}
    					}
    				}
    				
    				$product_name = get_the_title($value->post_id);
    				
    				// Populate the array
    				$return_bookings[ $i ]->ID            = $value->order_id;
    				$return_bookings[ $i ]->booking_id    = $value->booking_id;
    				$return_bookings[ $i ]->product_id    = $value->post_id;
    				$return_bookings[ $i ]->product_name  = $product_name.$var;
    				$return_bookings[ $i ]->checkin_date  = $value->start_date;
    				$return_bookings[ $i ]->checkout_date = $value->end_date;
    				if ( $value->from_time != "" ) {
    					$time = $value->from_time;
    				}
    				if ( $value->to_time != "" ) {
    					$time .=  " - " . $value->to_time;
    				}
    				$return_bookings[ $i ]->booking_time    = $time;
    				$return_bookings[ $i ]->quantity        = $selected_quantity;
    				$return_bookings[ $i ]->amount          = $amount;

    				$order_date = '';
    				if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
    				    $order_date = $order->completed_date;
    				} else {
    				 /*   $order_completed_obj = $order->get_date_completed();
    				    $order_created_obj = $order->get_date_created();
    				
                        if ( isset( $order_completed_obj ) && count( $order_completed_obj ) > 0 ) {
        				    $order_date = $order_completed_obj->format('Y-m-d H:i:s');
        				} else {
        				    $order_date = $order_created_obj->format('Y-m-d H:i:s');
        				} */
    				    $order_post = get_post( $value->order_id );
    				    $post_date = strtotime ( $order_post->post_date );
    				    $order_date = date( 'Y-m-d H:i:s', $post_date );
    				}
    				
    				$return_bookings[ $i ]->order_date = $order_date;
    				$i++;
    			}
    		}
		}
		
		//sort for order Id
		if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'ID' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_order_id_asc" ) );
			}
			else {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_order_id_dsc" ) );
			}
		}
		
		// sort for amount
		else if ( isset($_GET['orderby'] ) && $_GET['orderby'] == 'amount' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
		  		usort( $return_bookings, array( __CLASS__ , "bkap_class_amount_asc" ) ); 
			}
			else {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_amount_dsc" ) );
			}
		}
		
		// sort for qty
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'quantity' ) {
		    if ( isset( $_GET['order' ]) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_quantity_asc" ) ); 
			}
			else {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_quantity_dsc" ) );
			}
		}
		
		// sort for order date
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'order_date' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_order_date_asc" ) ); 
			}
			else {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_order_date_dsc" ) );
			}
		}
		
		// sort for booking/checkin date
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'checkin_date' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_checkin_date_asc" ) );
			}
			else {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_checkin_date_dsc" ) );
			}
		}
		
		// sort for check out date
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'checkout_date' ) {
		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array(__CLASS__ , "bkap_class_checkout_date_asc" ) );
			}
			else {
				usort( $return_bookings, array(__CLASS__ ,"bkap_class_checkout_date_dsc" ) );
			}
		}
		
		// sort for customer name
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'name' ) {
		if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array( __CLASS__ , "bkap_class_name_asc" ) );
			}
			else {
				usort( $return_bookings, array( __CLASS__ ,"bkap_class_name_dsc" ) );
			}
		}
		// sort for product name
		else if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'product_name' ) {
		if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_bookings, array( __CLASS__ ,"bkap_class_product_name_asc" ) );
			}
			else {
				usort( $return_bookings, array( __CLASS__ ,"bkap_class_product_name_dsc" ) );
			}
		}
		
		return apply_filters( 'bkap_bookings_table_data', $return_bookings );
	}
	
	function bkap_class_order_id_asc ( $value1, $value2 ) {
	    return $value1->ID - $value2->ID;
	}
	
	function bkap_class_order_id_dsc( $value1, $value2 ) {
	    return $value2->ID - $value1->ID;
	}
	
	function bkap_class_amount_asc( $value1, $value2 ) {
	    return $value1->amount - $value2->amount;
	}
	
	function bkap_class_amount_dsc( $value1, $value2 ) {
	    return $value2->amount - $value1->amount;
	}
	
	function bkap_class_quantity_asc ( $value1, $value2 ) {
	    return $value1->quantity - $value2->quantity;
	}
	function bkap_class_quantity_dsc( $value1, $value2 ) {
	    return $value2->quantity - $value1->quantity;
	}
	
	function bkap_class_order_date_asc( $value1, $value2 ) {
	    return strtotime( $value1->order_date ) - strtotime( $value2->order_date );
	}
	function bkap_class_order_date_dsc ( $value1, $value2 ) {
	    return strtotime( $value2->order_date ) - strtotime( $value1->order_date );
	}
	
	function bkap_class_checkin_date_asc( $value1, $value2 ) {
	    return strtotime($value1->checkin_date) - strtotime( $value2->checkin_date );
	}
	function bkap_class_checkin_date_dsc( $value1, $value2 ) {
	    return strtotime( $value2->checkin_date ) - strtotime( $value1->checkin_date );
	}
	
	function bkap_class_checkout_date_asc( $value1, $value2 ) {
	    return strtotime( $value1->checkout_date ) - strtotime( $value2->checkout_date );
	}
	
	function bkap_class_checkout_date_dsc( $value1, $value2 ) {
	    return strtotime( $value2->checkout_date ) - strtotime( $value1->checkout_date );
	}
	
	function bkap_class_name_asc( $value1, $value2 ) {
	    return strcasecmp( $value1->name,$value2->name );
	}
	
	function bkap_class_name_dsc ( $value1, $value2 ) {
	    return strcasecmp( $value2->name,$value1->name );
	}
	
	function bkap_class_product_name_asc( $value1, $value2 ) {
	    return strcasecmp( $value1->product_name,$value2->product_name );
	}
	
	function bkap_class_product_name_dsc ( $value1, $value2 ) {
	    return strcasecmp( $value2->product_name,$value1->product_name );
	}
	
	public function column_default( $booking, $column_name ) {
		
	    switch ( $column_name ) {
			
	        case 'ID' :
				$value = '<a href="post.php?post='.$booking->ID.'&action=edit">'.$booking->ID.'</a>';
				break;
			
			case 'checkin_date' :
				$date               = strtotime( $booking->checkin_date );
				$date_formats       = bkap_get_book_arrays( 'bkap_date_formats' );
				// get the global settings to find the date formats
				$global_settings    = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
				$date_format_set    = $date_formats[ $global_settings->booking_date_format ];
				$value              = date( $date_format_set, $date );
				break;
			
			case 'checkout_date' :
				if ( $booking->checkout_date != '0000-00-00' ) {
					$date              = strtotime( $booking->checkout_date );
					$date_formats      = bkap_get_book_arrays( 'bkap_date_formats' );
					// get the global settings to find the date formats
					$global_settings   = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					$date_format_set   = $date_formats[$global_settings->booking_date_format];
					$value             = date( $date_format_set, $date );
				}
				else {
					$value = "";
				}
				break;
			
			case 'booking_time' :
				if ( $booking->booking_time != '' ) {
					// get the global settings to find the date formats
					$global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
					
					if ( $global_settings->booking_time_format == 12 ) {
						$time_array   =   explode( '-', $booking->booking_time );
						$from_time    =   date( 'h:i A', strtotime( $time_array[0] ) );
					    $value        =   $from_time ;
						
						if ( isset( $time_array[1] ) && $time_array[1] != '' ){
						  $to_time    =   date( 'h:i A',strtotime( $time_array[1] ) );
						  $value      =   $from_time . " - " . $to_time;
						}
					}else {
						$value        =   $booking->booking_time;
					}
				}else {
					$value = '';
				}
				break;
			
			case 'amount' :
				$amount             = ! empty( $booking->amount ) ? $booking->amount : 0;
				// The order currency is fetched to ensure the correct currency is displayed if the site uses multi-currencies
                if ( false !== get_post_status( $booking->ID ) ) {
    				$the_order          = wc_get_order( $booking->ID );
    				$currency           = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $the_order->get_order_currency() : $the_order->get_currency();
				} else {
				    // get default woocommerce currency
				    $currency = get_woocommerce_currency();
				}
				$currency_symbol    = get_woocommerce_currency_symbol( $currency );
				$value              = $currency_symbol . number_format( $amount, 2 );
				break;
			
			case 'actions' :
	 			$value = '<a href="post.php?post='.$booking->ID.'&action=edit">View Order</a>';
				break;
			
			default:
				$value = isset( $booking->$column_name ) ? $booking->$column_name : '';
				break;
	
		}
		
		return apply_filters( 'bkap_booking_table_column_default', $value, $booking, $column_name );
	}
	
	
}
?>
