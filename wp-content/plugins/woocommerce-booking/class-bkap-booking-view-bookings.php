<?php 
if ( ! class_exists( 'BKAP_Bookings_View' ) ) {

    /**
     * BKAP_Bookings_View Class.
     */
    class BKAP_Bookings_View {
    
    	/**
    	 * Constructor.
    	 */
    	public function __construct() {
    		$this->type = 'bkap_booking';
	
    		// Post title fields
//    		add_filter( 'enter_title_here', array( $this, 'bkap_title' ), 1, 2 );

    		add_action( 'admin_enqueue_scripts', array( &$this, 'bkap_post_enqueue' ) );
    		// Admin Columns
    		add_filter( 'manage_edit-' . $this->type . '_columns', array( &$this, 'bkap_edit_columns' ) );
    		add_action( 'manage_' . $this->type . '_posts_custom_column', array( &$this, 'bkap_custom_columns' ), 2 );
    		add_filter( 'manage_edit-' . $this->type . '_sortable_columns', array( &$this, 'bkap_custom_columns_sort' ), 1 );
    		add_filter( 'request', array( $this, 'bkap_custom_columns_orderby' ) );
		
		// Setting class name for the primary column for view in mobile
            	add_filter( 'list_table_primary_column', array( $this, 'list_table_primary_column' ), 10, 2 );
		// Altering the actions in the mobile view.
            	add_filter( 'post_row_actions', array( $this, 'row_actions' ), 100, 2 );

    		// Filtering
    		add_action( 'restrict_manage_posts', array( $this, 'bkap_filters' ) );
    		add_filter( 'parse_query', array( $this, 'bkap_filters_query' ) );
    		add_filter( 'get_search_query', array( $this, 'bkap_search_label' ) );
    		
    		// Search
    		add_filter( 'parse_query', array( $this, 'bkap_search_custom_fields' ) );
    		
    		// Actions
    		add_filter( 'bulk_actions-edit-' . $this->type, array( $this, 'bkap_bulk_actions' ), 10, 1 );
    		add_action( 'load-edit.php', array( $this, 'bkap_bulk_action' ) );
    		add_action( 'admin_footer', array( $this, 'bkap_bulk_admin_footer' ), 10 );
    		add_action( 'admin_notices', array( $this, 'bkap_bulk_admin_notices' ) );
    		
    		// Message for old View Bookings page
    		add_action( 'admin_notices', array( $this, 'bkap_old_view' ) );
    		
    		// ajax action for confirming bookings
    		add_action( 'wp_ajax_bkap-booking-confirm', array( $this, 'bkap_booking_confirmed' ) );

			add_action( 'admin_init', array( &$this, 'bkap_export_data' ) );
			add_filter( 'months_dropdown_results', array( &$this, 'bkap_custom_date_dropdown' ));
			add_action( 'pre_get_posts', array( &$this, 'bkap_date_meta_query') );
		}
		
		/* Modify the date filter dropdown to show Booking months & year */
		function bkap_custom_date_dropdown( $months ) {
			global $wpdb;

			$months = $wpdb->get_results( "
						SELECT DISTINCT YEAR( meta_value ) AS year, 
						MONTH(meta_value) AS month FROM {$wpdb->prefix}woocommerce_order_itemmeta
						WHERE meta_key = '_wapbk_booking_date' OR meta_key = '_wapbk_checkout_date' ORDER BY meta_value DESC
					");

			return $months;
		}
		
		/* Modify the date filter query to filter by booking start & end dates */
		function bkap_date_meta_query( $wp_query ) {

			if ( is_admin() && $wp_query->is_main_query() && $wp_query->get( 'post_type' ) === 'bkap_booking' ) {
					$m = $wp_query->get( 'm' );
		
					if ( ! $meta_query = $wp_query->get( 'meta_query' ) ) // Keep meta query if there currently is one
						$meta_query = array();
				
					$meta_query[] = array(
						array( 
							'key' => '_bkap_start',
							'value' => $m,
							'compare' => 'LIKE',
						),
						array(
							'key' => '_bkap_end',
							'value' => $m,
							'compare' => 'LIKE',
						)
					);
	
		
					$wp_query->set( 'meta_query', $meta_query );
					$wp_query->set( 'm', null );
			}
		}

    	function bkap_post_enqueue() {
    	    
    	    $plugin_version_number = get_option( 'woocommerce_booking_db_version' );
    	    wp_enqueue_script( 'bkap-jquery-tip', plugins_url( '/js/jquery.tipTip.minified.js', __FILE__ ), '', $plugin_version_number, false );
    	    wp_enqueue_style( 'bkap-edit-bookings-css', plugins_url() . '/woocommerce-booking/css/edit-booking.css', null, $plugin_version_number );
    	 
    	}
    	/**
    	 * Change title boxes in admin.
    	 *
    	 * @param  string $text
    	 * @param  object $post
    	 * @return string
    	 */
    	public function bkap_title( $text, $post ) {
    	    if ( 'bkap_booking' === $post->post_type ) {
    	        return __( 'Booking Title', 'woocommerce-booking' );
    	    }
    	    return $text;
    	}


        /**
     * Set list table primary column for bookings.
     *  @param  string $default
     * @param  string $screen_id
     *
     * @return string
     */
    public function list_table_primary_column( $default, $screen_id ) {

        if ( 'edit-bkap_booking' === $screen_id ) {
            return 'bkap_id';
        }

        return $default;
    }


    /**
     * Set row actions for booking.
     *
     * @param  array $actions
     * @param  WP_Post $post
     *
     * @return array
     */
    public function row_actions( $actions, $post ) {
        if ( 'bkap_booking' === $post->post_type ) {

            if ( isset( $actions['inline hide-if-no-js'] ) ) {
                unset( $actions['inline hide-if-no-js'] );
            }

            if ( isset( $actions['view'] ) ) {
                unset( $actions['view'] );
            }

            return array_merge( array( 'id' => 'ID: ' . $post->ID ), $actions );
        }

        return $actions;
    }


    	/**
    	 * Change the columns shown in admin.
    	 */
    	public function bkap_edit_columns( $existing_columns ) {
    	    if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
    	        $existing_columns = array();
    	    }
    	
    	    unset( $existing_columns['comments'], $existing_columns['title'], $existing_columns['date'] );
    	
    	    $columns                    = array();
    	    $columns["bkap_status"]     = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'woocommerce-booking' ) . '"></span>';
    	    $columns["bkap_id"]         = __( 'ID', 'woocommerce-booking' );
    	    $columns["bkap_product"]    = __( 'Booked Product', 'woocommerce-booking' );
    	    $columns["bkap_customer"]   = __( 'Booked By', 'woocommerce-booking' );
    	    $columns["bkap_order"]      = __( 'Order', 'woocommerce-booking' );
    	    $columns["bkap_start_date"] = __( 'Start Date', 'woocommerce-booking' );
    	    $columns["bkap_end_date"]   = __( 'End Date', 'woocommerce-booking' );
    	    $columns["bkap_qty"]        = __( 'Quantity', 'woocommerce-booking' );
			$columns["bkap_amt"]        = __( 'Amount', 'woocommerce-booking' );
			$columns["bkap_order_date"] = __( 'Order Date', 'woocommerce-booking' );			
    	    $columns["bkap_actions"]    = __( 'Actions', 'woocommerce-booking' ); 
    	
    	    return array_merge( $existing_columns, $columns );
    	}
    	 
    	/**
    	 * Define our custom columns shown in admin.
    	 *
    	 * @param  string $column
    	 */
    	public function bkap_custom_columns( $column ) {
    	    global $post;
    	
        	if ( get_post_type( $post->ID ) === 'bkap_booking' ) {
    			$booking = new BKAP_Booking( $post->ID );
    		}
		
    	    $product = $booking->get_product();
    	
    	    $status = $booking->get_status();
    	    switch ( $column ) {
    	        case 'bkap_status' :
    	            $booking_statuses = bkap_common::get_bkap_booking_statuses();
    	            $status_label = ( array_key_exists( $status, $booking_statuses ) ) ? $booking_statuses[ $status ] : ucwords( $status );
    	            echo '<span class="status-' . esc_attr( $status ) . ' tips" date-tip="' . esc_attr( $status_label ) . '">' . esc_html( $status_label ) . '</span>';
    	            break;
    	        case 'bkap_id' :
    	            printf( '<a href="%s">' . __( 'Booking #%d', 'woocommerce-booking' ) . '</a>', admin_url( 'post.php?post=' . $post->ID . '&action=edit' ), $post->ID );
    	            break;
    	        case 'bkap_customer':
    	            $customer = $booking->get_customer();
    	
    	            if ( $customer->email && $customer->name ) {
    	                echo esc_html( $customer->name );
    	            } else {
    	                echo '-';
    	            } 
    	            break;
    	        case 'bkap_product':
    	            $product = $booking->get_product();
    	            $resource_id = $booking->get_resource();
    	
    	            if ( $product ) {
    	                echo '<a href="' . admin_url( 'post.php?post=' . ( is_callable( array( $product, 'get_id' ) ) ? $product->get_id() : $product->id ) . '&action=edit' ) . '">' . $product->get_title() . '</a>';
    	                
    	                if( $resource_id != "" ){
    	                    $resource_title = $booking->get_resource_title();
    	                    echo '<br>( <a href="' . admin_url( 'post.php?post=' . $resource_id . '&action=edit' ) . '">' . $resource_title . '</a> )';
    	                }
    	            } else {
    	                echo '-';
    	            } 
    	            break;
    	        case 'bkap_qty' :
    	            $quantity = $booking->get_quantity();
    	            echo "$quantity";
    	            break;
    	        case 'bkap_amt' :
    	            $amount = $booking->get_cost();
    	            $final_amt = $amount * $booking->get_quantity();
    	            $order_id = $booking->get_order_id();
    	            
    	            if ( absint( $order_id ) > 0 && false !== get_post_status( $order_id ) ) {
    	                $the_order          = wc_get_order( $order_id );
    	                $currency           = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $the_order->get_order_currency() : $the_order->get_currency();
    	            } else {
    	                // get default woocommerce currency
    	                $currency = get_woocommerce_currency();
    	            }
    	            $currency_symbol    = get_woocommerce_currency_symbol( $currency );
    	            
    	            echo wc_price( $final_amt, array( 'currency' => $currency) );
    	            break;
    	        case 'bkap_order':
    	            $order = $booking->get_order();
    	            if ( $order ) {
    	                echo '<a href="' . admin_url( 'post.php?post=' . ( is_callable( array( $order, 'get_id' ) ) ? $order->get_id() : $order->id ) . '&action=edit' ) . '">#' . $order->get_order_number() . '</a> - ' . esc_html( wc_get_order_status_name( $order->get_status() ) );
    	            } else {
    	                echo '-';
    	            } 
    	            break;
    	        case 'bkap_start_date' :
    	            echo $booking->get_start_date() . "<br>" . $booking->get_start_time();
    	            break;
    	        case 'bkap_end_date' :
    	            echo $booking->get_end_date() . "<br>" . $booking->get_end_time();
					break;
				case 'bkap_order_date':
					echo $booking->get_date_created();
					break;
    	        case 'bkap_actions' :
    	            echo '<p>';
    	            $actions = array(
    	                'view' => array(
    	                    'url'    => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
    	                    'name'   => __( 'View', 'woocommerce-booking' ),
    	                    'action' => 'view',
    	                ),
    	            );
    	
    	            if ( in_array( $status, array( 'pending-confirmation' ) ) ) {
    	                $actions['confirm'] = array(
    	                    'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=bkap-booking-confirm&booking_id=' . $post->ID ), 'bkap-booking-confirm' ),
    	                    'name'   => __( 'Confirm', 'woocommerce-booking' ),
    	                    'action' => 'confirm',
    	                );
    	            }
    	
    	            $actions = apply_filters( 'bkap_view_bookings_actions', $actions, $booking );
    	
    	            foreach ( $actions as $action ) {
    	                printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
    	            }
    	            echo '</p>';
    	            break;
    	    }
    	}
    	 
    	/**
    	 * Sortable Columns List
    	 */
    	public function bkap_custom_columns_sort( $columns ) {
    	    $custom = array(
    	        'bkap_id'          => 'bkap_id',
    //	        'bkap_product'     => 'bkap_product',
    	        'bkap_status'      => 'bkap_status',
    	        'bkap_start_date'  => 'bkap_start_date',
				'bkap_end_date'    => 'bkap_end_date',
				'bkap_order_date'  => 'bkap_order_date'
    	    );
    	    
    	    return wp_parse_args( $custom, $columns );
    	}
    	 
    	/**
    	 * For Sortable columns
    	 *
    	 * @access public
    	 * @param mixed $vars
    	 * @return array
    	 */
    	public function bkap_custom_columns_orderby( $vars ) {
    	    if ( isset( $vars['orderby'] ) ) {
    	        if ( 'bkap_id' == $vars['orderby'] ) {
    	            $vars = array_merge( $vars, array(
    	                'orderby' => 'ID', // sort using the ID column in posts
    	            ) );
    	        }
    	
    	    /*    if ( 'bkap_product' == $vars['orderby'] ) {
    	            $vars = array_merge( $vars, array(
    	                'meta_key' => '_bkap_product_id',
    	                'orderby'  => 'meta_value_num',
    	            ) );
    	        } */ 
    	
    	        if ( 'status' == $vars['orderby'] ) {
    	            $vars = array_merge( $vars, array(
    	                'orderby' => 'post_status', // sort using the post status
    	            ) );
    	        } 
    	 
    	        if ( 'bkap_start_date' == $vars['orderby'] ) {
    	 
    	            $vars = array_merge( $vars, array(
    	                'meta_key' => '_bkap_start',
    	                'orderby'  => 'meta_value_num',
    	            ) );    	            
    	        } 
    	
    	        if ( 'bkap_end_date' == $vars['orderby'] ) {
    	            $vars = array_merge( $vars, array(
    	                'meta_key' => '_bkap_end',
    	                'orderby'  => 'meta_value_num',
    	            ) );
				}
				
				if ( 'bkap_order_date' == $vars['orderby'] ) {
    	            $vars = array_merge( $vars, array(
    	                'orderby'  => 'post_date',
    	            ) );
    	        }
    	    } 
    	
    	    return $vars;
    	  
    	}

    	/**
    	 * Remove Edit link from the bulk actions
    	 * @param array $actions
    	 * @return array
    	 */
    	function bkap_bulk_actions( $actions ) {
    	    if ( isset( $actions['edit'] ) ) {
    	        unset( $actions['edit'] );
    	    }
    	    return $actions;
    	}
    	
    	/**
    	 * Add filters
    	 */
    	public function bkap_filters() {
    		global $typenow, $wp_query;
    
    		if ( $typenow !== $this->type ) {
    			return;
    		}
    		
    		
    		$filters = array();
    
    		$products = bkap_common::get_woocommerce_product_list( false );
    
    		foreach ( $products as $product ) {
    		    // check if a booking is present for that product
    		    $present = $this->bkap_check_booking_present( $product[1] );
    		    
    		    if ( $present ) {
                    $filters[ $product[1] ] = $product[0];
    		    }
    
    		} 
    
    		$output = '';
    
    		if ( is_array( $filters ) && count( $filters ) > 0 ) {
    			$output .= '<select name="filter_products">';
    			$output .= '<option value="">' . __( 'All Bookable Products', 'woocommerce-booking' ) . '</option>';
    
    			foreach ( $filters as $filter_id => $filter ) {
    				$output .= '<option value="' . absint( $filter_id ) . '" ';
    
    				if ( isset( $_REQUEST['filter_products'] ) ) {
    					$output .= selected( $filter_id, $_REQUEST['filter_products'], false );
    				}
    
    				$output .= '>' . esc_html( $filter ) . '</option>';
    			}
  
    			$output .= '</select>';
    		}
    
    		
    		$views = array( 'today_onwards'  => 'Bookings From Today Onwards',
    		                'today_checkin'  => 'Today Check-ins',
    		                'today_checkout' => 'Today Checkouts',
    		                'gcal'           => 'Imported Bookings',
    		 );
    		
    		$output .= '<select name="filter_views">';
    		$output .= '<option value="">' . __( 'Select Booking Type', 'woocommerce-booking' ) . '</option>';
    		
    		foreach( $views as $v_key => $v_value ) {
    
		        $output .= '<option value="' . $v_key . '" ';
		    
		        if ( isset( $_REQUEST['filter_views'] ) ) {
		            $output .= selected( $v_key, $_REQUEST['filter_views'], false );
		        }
		    
		        $output .= '>' . esc_html( $v_value ) . '</option>';
		    }
    		    
		    $output .= '</select>';
    		    
    		echo $output;
    	}
    	
    	/**
    	 * Custom filter queries
    	 * @param $query
    	 */
    	public function bkap_filters_query( $query ) {
    	    global $typenow, $wp_query;
    	
    	    if ( $typenow === $this->type ) {
    	        
    	        $current_timestamp = current_time( 'timestamp' );
    	        $current_time = date( 'YmdHis', $current_timestamp );
    	        $current_date = date( 'Ymd', $current_timestamp );
    	        
    	        if ( ! empty( $_REQUEST['filter_products'] ) && ! empty( $_REQUEST['filter_views'] ) && empty( $query->query_vars['suppress_filters'] ) ) {
    	             
    	            switch ( $_REQUEST['filter_views'] ) {
    	                case 'today_onwards':
    	                    $query->query_vars['meta_query'] = array(
        	                    array(
            	                    'key'   => '_bkap_start',
            	                    'value' => $current_time,
            	                    'compare' => '>=',
        	                    ),
        	                    array(
            	                    'key'   => '_bkap_product_id',
            	                    'value' => absint( $_REQUEST['filter_products'] ),
        	                    ),
    	                    );
    	                    break;
    	                case 'today_checkin':
    	                    $query->query_vars['meta_query'] = array(
        	                    array(
            	                    'key'   => '_bkap_start',
            	                    'value' => $current_date,
            	                    'compare' => 'LIKE',
        	                    ),
        	                    array(
            	                    'key'   => '_bkap_product_id',
            	                    'value' => absint( $_REQUEST['filter_products'] ),
        	                    ),
    	                    );
    	                    break;
    	                case 'today_checkout':
    	                    $query->query_vars['meta_query'] = array(
        	                    array(
            	                    'key'   => '_bkap_end',
            	                    'value' => $current_date,
            	                    'compare' => 'LIKE',
        	                    ),
        	                    array(
            	                    'key'   => '_bkap_start',
            	                    'value' => $current_date,
            	                    'compare' => 'NOT LIKE',
        	                    ),
        	                    array(
            	                    'key'   => '_bkap_product_id',
            	                    'value' => absint( $_REQUEST['filter_products'] ),
        	                    ),
    	                    );
    	                    break;
    	                case 'gcal':
    	                    $query->query_vars['meta_query'] = array(
        	                    array(
            	                    'key'   => '_bkap_gcal_event_uid',
            	                    'value' => false,
            	                    'compare' => '!=',
        	                    ),
        	                    array(
            	                    'key'   => '_bkap_product_id',
            	                    'value' => absint( $_REQUEST['filter_products'] ),
        	                    ),
    	                    );
    	                    break;
    	            }
    	        } else if ( ! empty( $_REQUEST['filter_products'] ) && empty( $query->query_vars['suppress_filters'] ) ) {
    	            $query->query_vars['meta_query'] = array(
    	                array(
    	                    'key'   => '_bkap_product_id',
    	                    'value' => absint( $_REQUEST['filter_products'] ),
    	                ),
    	            );
    	        } else if ( ! empty( $_REQUEST['filter_views'] ) && empty( $query->query_vars['suppress_filters'] ) ) {
    	             
    	            switch ( $_REQUEST['filter_views'] ) {
    	                case 'today_onwards':
    	                    $query->query_vars['meta_query'] = array(
        	                    array(
            	                    'key'   => '_bkap_start',
            	                    'value' => $current_time,
            	                    'compare' => '>=',
        	                    ),
    	                    );
    	                    break;
    	                case 'today_checkin':
    	                    $query->query_vars['meta_query'] = array(
        	                    array(
            	                    'key'   => '_bkap_start',
            	                    'value' => $current_date,
            	                    'compare' => 'LIKE',
        	                    ),
    	                    );
    	                    break;
    	                case 'today_checkout':
    	                    $query->query_vars['meta_query'] = array(
        	                    array(
            	                    'key'   => '_bkap_end',
            	                    'value' => $current_date,
            	                    'compare' => 'LIKE',
        	                    ),
        	                    array(
            	                    'key'   => '_bkap_start',
            	                    'value' => $current_date,
            	                    'compare' => 'NOT LIKE',
        	                    ),
    	                    );
    	                    break;
    	                case 'gcal':
    	                    $query->query_vars['meta_query'] = array(
        	                    array(
            	                    'key'   => '_bkap_gcal_event_uid',
            	                    'value' => false,
            	                    'compare' => '!=',
        	                    ),
    	                    );
    	                    break;
    	            }
    	        }
    	        
    	    }
    	}
    	
    	/**
    	 *
    	 * @param $query
    	 */
    	public function bkap_search_label( $query ) {
    	    global $pagenow, $typenow;
    	
    	    if ( 'edit.php' !== $pagenow ) {
    	        return $query;
    	    }
    	
    	    if ( $typenow != $this->type ) {
    	        return $query;
    	    }
    	
    	    if ( ! get_query_var( 'booking_search' ) ) {
    	        return $query;
    	    }
    	
    	    return wc_clean( $_GET['s'] );
    	}
    	 
    	/**
    	 * Search custom columns
    	 * @param $wp
    	 */
    	public function bkap_search_custom_fields( $wp ) {
    	    global $pagenow, $wpdb;
    	
    	    if ( 'edit.php' != $pagenow || empty( $wp->query_vars['s'] ) || $wp->query_vars['post_type'] !== $this->type || 'bkap_gcal_event' == $this->type ) {
    	        return $wp;
    	    }
    	
    	    $term = wc_clean( $_GET['s'] );
    	
    	    if ( is_numeric( $term ) ) {
    	        // check if a booking exists by this ID
    	        if ( false !== get_post_status( $term ) && 'bkap_booking' === get_post_type( $term ) )
    	            $booking_ids = array( $term );
    	        else { // else assume the numeric value is an order ID
    	            if ( function_exists( 'wc_order_search' ) ) {
    	                $order_ids = wc_order_search( wc_clean( $_GET['s'] ) );
    	                $booking_ids = $order_ids ? bkap_common::get_booking_ids_from_order_id( $order_ids ) : array( 0 );
    	                 
    	                if ( is_array( $booking_ids ) && count( $booking_ids ) == 0 ) {
    	                    $booking_ids = array( 0 );
    	                }
    	            }
    	        }
    	         
    	    } else {

                $search_string = esc_attr($_GET['s']);
              
                $white_space = strpos( $search_string, ' ' );
                
                if( $white_space > 0 ) { 
                    
                    $search_texts = explode(" ", $search_string );
                   
                    $regex_text = implode( "|", $search_texts );
                } else {
                    $regex_text = $search_string;
                }

    	        $search_fields = array_map( 'wc_clean', array(
    	            '_billing_first_name',
    	            '_billing_last_name',
    	            '_billing_company',
    	            '_billing_address_1',
    	            '_billing_address_2',
    	            '_billing_city',
    	            '_billing_postcode',
    	            '_billing_country',
    	            '_billing_state',
    	            '_billing_email',
    	            '_billing_phone',
    	            '_shipping_first_name',
    	            '_shipping_last_name',
    	            '_shipping_address_1',
    	            '_shipping_address_2',
    	            '_shipping_city',
    	            '_shipping_postcode',
    	            '_shipping_country',
    	            '_shipping_state',
    	        ) );
    	        
    	        // Search orders
    	        $order_ids = $wpdb->get_col("
    	                SELECT post_id
    	                FROM {$wpdb->postmeta}
    	                WHERE meta_key IN ('" . implode( "','", $search_fields ) . "')
    	                AND meta_value REGEXP '". $regex_text ."'"
				);
				
				//Search query for date
				$timestamp = strtotime( $term ); 				
				if( empty( $order_ids ) && false !== $timestamp ) {

					$date = date( 'Y-m-d', $timestamp );
					$order_ids = $wpdb->get_col(
						$wpdb->prepare( "
							SELECT post_id
							FROM {$wpdb->postmeta}
							WHERE meta_key = '_bkap_order_item_id' AND meta_value IN 
							( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta where ( meta_key = '_wapbk_booking_date' OR meta_key = '_wapbk_checkout_date' ) AND meta_value = %s )",
							esc_attr( $date )
						)
					);

					$booking_ids = $order_ids;
				}
				
				//If the search is not for date, search for product name
				if( empty( $order_ids ) ) {
					$order_ids = $wpdb->get_col(
						$wpdb->prepare( "
							SELECT post_id
							FROM {$wpdb->postmeta}
							WHERE ( meta_key = '_bkap_product_id' )
							AND meta_value IN ( SELECT ID from {$wpdb->posts} WHERE post_title LIKE '%%%s%%' ) ",
							esc_attr( $_GET['s'] )
						)
					);
					
					$booking_ids = $order_ids;
				}
				
	           // ensure db query doesn't throw an error due to empty post_parent value
	           $order_ids = empty( $order_ids ) ? array( '-1' ) : $order_ids;
	
				// so we know we're doing this
				if( empty( $booking_ids ) ) {
					$booking_ids = array_merge(
					$wpdb->get_col( "
						SELECT ID FROM {$wpdb->posts}
						WHERE post_parent IN (" . implode( ',', $order_ids ) . ");
						"),
						$wpdb->get_col(
						$wpdb->prepare( "
							SELECT ID
							FROM {$wpdb->posts}
							WHERE post_title LIKE '%%%s%%'
							OR ID = %d
					;",
					esc_attr( $_GET['s'] ),
					absint( $_GET['s'] )
					)
						),
							array( 0 ) // so we don't get back all results for incorrect search
					);
				}
			}
	
			$wp->query_vars['s']              = false;
			$wp->query_vars['post__in']       = $booking_ids;
			$wp->query_vars['booking_search'] = true;

		}

		/**
		 * Add Bulk Actions
		 */
		public function bkap_bulk_admin_footer() {
		    global $post_type;
		
		    if ( $this->type === $post_type ) {
		        ?>
					<script type="text/javascript">
						jQuery( document ).ready( function ( $ ) {
							$( '<option value="confirm_booking"><?php _e( 'Confirm bookings', 'woocommerce-booking' )?></option>' ).appendTo( 'select[name="action"], select[name="action2"]' );
							$( '<option value="cancel_booking"><?php _e( 'Cancel bookings', 'woocommerce-booking' )?></option>' ).appendTo( 'select[name="action"], select[name="action2"]' );
						});
					</script>
				<?php
			}
		}

		/**
		 * Bulk Actions execution
		 */
		public function bkap_bulk_action() {
		    
		    global $post_type;
		    
		    if ( $this->type === $post_type ) {
    		    $wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
    		    $action = $wp_list_table->current_action();
    		
    		    switch ( $action ) {
    		        case 'confirm_booking' :
    		            $new_status = 'confirmed';
    		            $report_action = 'bookings_confirmed';
    		            break;
    		        case 'cancel_booking' :
    		            $new_status = 'cancelled';
    		            $report_action = 'bookings_cancelled';
    		            break;
    	            case 'trash':
    	                $new_status = 'trash';
    	                $report_action = 'bookings_trashed';
    	                break;
    		        default:
    		            return;
    		    }
    		
    		    $changed = 0;
    		
    		    $post_ids = array_map( 'absint', (array) $_REQUEST['post'] );
    		
    		    foreach ( $post_ids as $post_id ) {
    		        
    		        if ( $new_status === 'trash' ) {
    		            woocommerce_booking::bkap_delete_booking( $post_id );
    		        } else {
                        $item_id = get_post_meta( $post_id, '_bkap_order_item_id', true );
                        bkap_booking_confirmation::bkap_save_booking_status( $item_id, $new_status );
    		        }
    		        $changed++;
    		    }
    		
    		    $sendback = add_query_arg( array( 'post_type' => $this->type, $report_action => true, 'changed' => $changed, 'ids' => join( ',', $post_ids ) ), '' );
    		    wp_redirect( $sendback );
    		    exit();
		    }
		}
		
		/**
		 * Bulk Action messages
		 */
		public function bkap_bulk_admin_notices() {
		    global $post_type, $pagenow;
		
		    if ( isset( $_REQUEST['bookings_confirmed'] ) || isset( $_REQUEST['bookings_unconfirmed'] ) || isset( $_REQUEST['bookings_cancelled'] ) ) {
		        $number = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;
		
		        if ( 'edit.php' == $pagenow && $this->type == $post_type ) {
		            $message = sprintf( _n( 'Booking status changed.', '%s booking statuses changed.', $number, 'woocommerce-booking' ), number_format_i18n( $number ) );
		            echo '<div class="updated"><p>' . $message . '</p></div>';
		        }
		    }
		}
		
		/**
		 * Add notice for linking to the old View Bookings page
		 */
		function bkap_old_view() {
		    
		    global $post_type, $pagenow;
		    
		    if ( 'edit.php' == $pagenow && $this->type == $post_type ) {
		        
		          if ( current_user_can( 'operator_bookings' ) ) {
                    
		              $link = 'admin.php?page=operator_bookings';
                    }else {
                    
                      $link = 'admin.php?page=woocommerce_history_page';
                    }
		        
		        $message = 'Think some bookings are missing? If yes, please try checking for them <a href="' . get_admin_url() . $link . '">here</a>';
		        $message = __( $message, 'woocommerce-booking' );
		        echo '<div class="updated"><p>' . $message . '</p></div>';
		    }
		}
		
		/**
		 * Ajax for confirming bookings from Actions column
		 */
		function bkap_booking_confirmed() {
		
		    if ( ! check_admin_referer( 'bkap-booking-confirm' ) ) {
		        wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-booking' ) );
		    }
		    $booking_id = isset( $_GET['booking_id'] ) && (int) $_GET['booking_id'] ? (int) $_GET['booking_id'] : '';
		    if ( ! $booking_id ) {
		        die;
		    }
		
		    $item_id = get_post_meta( $booking_id, '_bkap_order_item_id', true );
		
		    bkap_booking_confirmation::bkap_save_booking_status( $item_id, 'confirmed' );
		
		    wp_safe_redirect( wp_get_referer() );
		
		}
		
		/**
		 * Returns true if at least one booking has been received for the
		 * given product ID
		 * @param int $product_id
		 * @return boolean
		 */
        function bkap_check_booking_present( $product_id ) {
            
            $bookings_present = false; // assume no bookings are present for this product
            global $wpdb;
            
            $query = "SELECT post_id FROM `" . $wpdb->prefix . "postmeta`
                        WHERE meta_key = %s
                        AND meta_value = %d
                        ORDER BY post_id DESC LIMIT 1";
            
            $results_query = $wpdb->get_results( $wpdb->prepare( $query, '_bkap_product_id', $product_id ) );
            
            if ( isset( $results_query ) && count( $results_query ) > 0 ) {
                $bookings_present = true;
            }
            
            return $bookings_present;
        }

        public function bkap_export_data() {
            global $wpdb;
        
            $post_status = '';
        
            if ( isset( $_GET['post_status'] ) ) {
                $post_status = $_GET['post_status'];
            }
            
            /*if ( isset( $_GET['duration_select'] ) && $_GET['duration_select'] !='' && !( isset( $_GET['s'] ) ) ){
                $tab_status = 'custom';
            }*/
            
            if ( isset( $_GET['download'] ) && ( $_GET['download'] == 'data.csv' ) ) {
                $report = self::generate_data( $post_status );
                self::bkap_download_csv_file( $report );
            } else if ( isset( $_GET['download'] ) && ( $_GET['download'] == 'data.print' ) ) {
                $report = self::generate_data($post_status);
                self::bkap_download_print_file( $report );
            }
        }

        public static function bkap_download_csv_file( $report ) {

            $csv = self::generate_csv( $report );
                
            header("Content-type: application/x-msdownload");
            header("Content-Disposition: attachment; filename=data.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            echo "\xEF\xBB\xBF";
            echo $csv;
            exit;
        }

        public static function bkap_download_print_file( $report ){
            
            $print_data_columns  = "
                                    <tr>
                                        <th style='border:1px solid black;padding:5px;'>".__( 'Status', 'woocommerce-booking' )."</th>
                                        <th style='border:1px solid black;padding:5px;'>".__( 'ID', 'woocommerce-booking' )."</th>
                                        <th style='border:1px solid black;padding:5px;'>".__( 'Booked Product', 'woocommerce-booking' )."</th>
                                        <th style='border:1px solid black;padding:5px;'>".__( 'Booked By', 'woocommerce-booking' )."</th>
                                        <th style='border:1px solid black;padding:5px;'>".__( 'Order', 'woocommerce-booking' )."</th>
                                        <th style='border:1px solid black;padding:5px;'>".__( 'Start Date', 'woocommerce-booking' )."</th>
                                        <th style='border:1px solid black;padding:5px;'>".__( 'End Date', 'woocommerce-booking' )."</th>
                                        <th style='border:1px solid black;padding:5px;'>".__( 'Quantity', 'woocommerce-booking' )."</th>
                                        <th style='border:1px solid black;padding:5px;'>".__( 'Order Date', 'woocommerce-booking' )."</th>
                                        <th style='border:1px solid black;padding:5px;'>".__( 'Amount', 'woocommerce-booking' )."</th>
                                    </tr>";
            $print_data_row_data =  '';
            
            foreach ( $report as $key => $value ) {
                
                // Status
                $status_raw = $value->get_status();
                $status = bkap_common::get_mapped_status( $status_raw );

                // Booked Product
                $product = $value->get_product();
                $product_name = "";
                if ( $product !== null && $product != false ) {
                    $product_name = $product->get_title();
                }
                
                $resource_id = $value->get_resource();
                $resource_title = "";
                
                if( $resource_id != "" ){
                    $resource_title = $value->get_resource_title();
                
                    $product_name .= "<br>( ". $resource_title . " )";
                
                }

                // Booked By
                $customer = $value->get_customer();
                $booked_by = $customer->name;

                // Start Date
                $start_date = $value->get_start_date();
                if ( $value->get_start_time() !== '' ) {
                    $start_date .= " - " . $value->get_start_time();
                }

                // End Date
                $end_date = "";
                if ( $value->get_end_date() !== '' ) {
                    $end_date = $value->get_end_date();

                    if ( $value->get_end_time() !== '' ) {
                        $end_date .= " - " . $value->get_end_time();
                    }
                }
                
                // Order Date
                $order_date = $value->get_date_created();

                // Amount
                $amount = $value->get_cost();
                $final_amt = $amount * $value->get_quantity();
                
                if ( absint( $value->order_id ) > 0 && false !== get_post_status( $value->order_id ) ) {
                    $the_order = wc_get_order( $value->order_id );
                    $currency = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $the_order->get_order_currency() : $the_order->get_currency();
                } else {
                    // get default woocommerce currency
                    $currency = get_woocommerce_currency();
                }
                $currency_symbol = get_woocommerce_currency_symbol( $currency );

                $final_amt = wc_price( $final_amt, array( 'currency' => $currency) );
                $print_data_row_data .= "<tr>
                                        <td style='border:1px solid black;padding:5px;'>".$status."</td>
                                        <td style='border:1px solid black;padding:5px;'>".$value->id."</td>
                                        <td style='border:1px solid black;padding:5px;'>".$product_name."</td>
                                        <td style='border:1px solid black;padding:5px;'>".$booked_by."</td>
                                        <td style='border:1px solid black;padding:5px;'>".$value->order_id."</td>
                                        <td style='border:1px solid black;padding:5px;'>".$start_date."</td>
                                        <td style='border:1px solid black;padding:5px;'>".$end_date."</td>
                                        <td style='border:1px solid black;padding:5px;'>".$value->get_quantity()."</td>
                                        <td style='border:1px solid black;padding:5px;'>".$order_date."</td>
                                        <td style='border:1px solid black;padding:5px;'>".$final_amt."</td>
                                        </tr>";
            }
            
            $print_data_title    =   apply_filters( 'bkap_view_bookings_print_title', __("Print Bookings", 'woocommerce-booking') );
            $print_data_columns  =   apply_filters( 'bkap_view_bookings_print_columns', $print_data_columns );
            $print_data_row_data =   apply_filters( 'bkap_view_bookings_print_rows', $print_data_row_data, $report );
            $print_data          =   "<table style='border:1px solid black;border-collapse:collapse;'>" . $print_data_columns . $print_data_row_data . "</table>";
            $print_data          =   "<html><head><title>". $print_data_title. "</title><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body><table style='border:1px solid black;border-collapse:collapse;'>" . $print_data_columns . $print_data_row_data . "</table></body></html>";
            echo $print_data;
            ?>
            
            <?php 
            exit;
        }

        public function generate_data( $post_status ) {
            
            return bkap_common::bkap_get_bookings( $post_status );
        }

        public static function generate_csv( $data ) {
            
            // Column Names
            $csv = 'Status,ID,Booked Product,Booked By,Order ID,Start Date,End Date,Quantity,Order Date,Amount';
            $csv .= "\n";
            
            foreach ( $data as $key => $value ) {
                // Status
                $status_raw = $value->get_status();
                $status = bkap_common::get_mapped_status( $status_raw );

                // ID
                $booking_id = $value->id;

                // Booked Product
                $product = $value->get_product();
                $product_name = "";
                if ( $product !== null && $product !== false ) {
                    $product_name = $product->get_title();
                }
                
                $resource_id = $value->get_resource();
                $resource_title = "";
                
                if( $resource_id != "" ){
                    $resource_title = $value->get_resource_title();
                
                    $product_name .= " ( ". $resource_title . " )";
                
                }

                // Booked By
                $customer = $value->get_customer();
                $booked_by = $customer->name;
                
                // Order ID
                $order_id = $value->order_id;
                
                // Start Date
                $start_date = $value->get_start_date();
                if ( $value->get_start_time() !== '' ) {
                    $start_date .= " - " . $value->get_start_time();
                }

                // End Date
                $end_date = "";
                if ( $value->get_end_date() !== '' ) {
                    $end_date = $value->get_end_date();

                    if ( $value->get_end_time() !== '' ) {
                        $end_date .= " - " . $value->get_end_time();
                    }
				}
				
				//Order date
				$order_date = $value->get_date_created();

                // Quantity
                $quantity= $value->get_quantity();
                
                // Amount
                $amount = $value->get_cost();
                $final_amt = $amount * $quantity;
                
                if ( absint( $order_id ) > 0 && false !== get_post_status( $order_id ) ) {
                    $the_order = wc_get_order( $order_id );
                    $currency = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $the_order->get_order_currency() : $the_order->get_currency();
                } else {
                    // get default woocommerce currency
                    $currency = get_woocommerce_currency();
                }
                $currency_symbol = get_woocommerce_currency_symbol( $currency );

				$final_amt = strip_tags( html_entity_decode( wc_price( $final_amt, array( 'currency' => $currency ) ) ) );
				
				// Create the data row
				$csv .= $status . ',' . $booking_id . ',"' . $product_name . '",' . $booked_by . ',' . $order_id . ',"' . $start_date . '","' . $end_date . '",' . $quantity . ',' . $order_date . ',"' . $final_amt . '"';
                $csv .= "\n";  
            }
            $csv = apply_filters( 'bkap_bookings_csv_data', $csv, $data );
            return $csv;
        }
	
    }// end of class
}
return new BKAP_Bookings_View();