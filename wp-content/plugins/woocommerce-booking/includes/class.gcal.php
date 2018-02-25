<?php
/**
 * Class for Google Calendar API for WooCommerce Booking and Appointment Plugin
 * V2.6
 *
 */
if ( !class_exists( 'BKAP_Gcal' ) ) {
    class BKAP_Gcal {
        function __construct() {
            global $wpdb;
            $this->plugin_dir = plugin_dir_path( __FILE__ );
            $this->plugin_url = plugins_url( basename( dirname( __FILE__ ) ) );
            $this->local_time = current_time( 'timestamp' );
          
            $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
            $this->time_format = ( isset( $global_settings->booking_time_format ) ) ? $global_settings->booking_time_format : "H:i";
            
            $this->date_format = ( isset( $global_settings->booking_date_format ) ) ? $global_settings->booking_date_format : "Y-m-d";
            
            $this->datetime_format = $this->date_format . " " . $this->time_format;
            
    		require_once $this->plugin_dir . 'external/google/Client.php';
    		
    		add_action( 'admin_init', array( &$this, 'bkap_init' ), 12 );
    		
    		// Try to start a session. If cannot, log it.
    		if ( !session_id() && !@session_start() ) {
    			$this->log( __( 'Session could not be started. This may indicate a theme issue.', 'woocommerce-booking' ) );
    		}
    		
    		// Prevent exceptions to kill the page
    		if ( ( isset( $_POST[ 'gcal_api_test' ] ) && 1 == $_POST[ 'gcal_api_test' ] )
    			|| ( isset( $_POST['gcal_import_now'] ) && $_POST[ 'gcal_import_now' ] ) ) {
    			set_exception_handler( array( &$this, 'exception_error_handler' ) );
            }
    			
    		// Set log file location
    		$uploads = wp_upload_dir();
    		if ( isset( $uploads[ "basedir" ] ) ) {
    			$this->uploads_dir 	= $uploads[ "basedir" ] . "/";
    		} else {
    			$this->uploads_dir 	= WP_CONTENT_DIR . "/uploads/";
    		}
    		
    		$this->log_file = $this->uploads_dir . "bkap-log.txt";
    		
    		add_action( 'wp_ajax_display_nag', array( &$this, "display_nag" ) );
        }
	   
        /**
	    * Refresh the page with the exception as GET parameter, so that page is not killed
	    */
        function exception_error_handler( $exception ) {
	       // If we don't remove these GETs there will be an infinite loop
	       if ( !headers_sent() ) {
	           wp_redirect( esc_url( add_query_arg( array( 'gcal_api_test_result' => urlencode( $exception ), 'gcal_import_now' => false, 'gcal_api_test' => false, 'gcal_api_pre_test' => false ) ) ) );
	       } else {
	           $this->log( $exception );
	       }
        }
	   
        /**
	    * Displays nag
	    * @param worker_id: Check and display this for the user profile page
	    */
        function display_nag() {
	       $error = false;
	       $message = '';
	       
	       $user_id = $_POST[ 'user_id' ];

	       $product_id = 0;
	       $product_id = $_POST[ 'product_id' ];
	       
	       if ( isset( $_POST[ 'gcal_api_test' ] ) && 1 == $_POST[ 'gcal_api_test' ] ) {
   	           $result = $this->is_not_suitable( $user_id, $product_id );
	           if ( '' != $result ) {           
	               $message .= $result;
	           } else {
	               // Insert a test event
	               $result = $this->insert_event( array(), 0, $user_id, $product_id, true );
	               if ( $result ) {
	                   $message .= __( '<b>Test is successful</b>. Please REFRESH your Google Calendar and check that test appointment has been saved.', 'woocommerce-booking' );
	               } else {
	                   $log_path = $this->uploads_dir . "bkap-log.txt"; 
	                   $message .= __( "<b>Test failed</b>. Please inspect your log located at $log_path for more info.", 'woocommerce-booking' );
	               }
	           }
	       }
	        
	       if ( isset( $_POST[ 'gcal_api_test_result' ] ) && '' != $_POST[ 'gcal_api_test_result' ] ) {
	           $m = stripslashes( urldecode( $_POST[ 'gcal_api_test_result' ] ) );
	           // Get rid of unnecessary information
	           if ( strpos( $m, 'Stack trace' ) !== false ) {
	               $temp = explode( 'Stack trace', $m );
	               $m = $temp[0];
	           }
	           if ( strpos( $this->get_selected_calendar( $user_id, $product_id ), 'group.calendar.google.com' ) === false ) {
	               $add = '<br />'. __( 'Do NOT use your primary Google calendar, but create a new one.', 'woocommerce-booking' );
	           } else {
	               $add = '';
	           }
	           $message = __( 'The following error has been reported by Google Calendar API:<br />', 'woocommerce-booking' ) . $m . '<br />' .
	               __( '<b>Recommendation:</b> Please double check your settings.' . $add, 'woocommerce-booking' );
	       }
	       
	       echo $message;
	       die();
        }
        
        /**
         * Set some default settings related to GCal
         * @since 2.6
         */
        function bkap_init() {
        
            $product_id = 0;
            $user_id = get_current_user_id();
            if ( 'disabled' != $this->get_api_mode( $user_id, $product_id ) && '' != $this->get_api_mode( $user_id, $product_id ) ) {
                // Try to create key file folder if it doesn't exist
                $this->create_key_file_folder( );
                $kff = $this->key_file_folder( );
                 
                // Copy index.php to this folder and to uploads folder
                if ( is_dir( $kff ) && !file_exists( $kff . 'index.php' ) ) {
                    echo "copying index file <br>";
                    @copy( $this->plugin_dir . 'gcal/key/index.php', $kff . 'index.php' );
                }
                if ( is_dir( $this->uploads_dir ) && !file_exists( $this->uploads_dir . 'index.php') ) {
                    @copy( $this->plugin_dir . 'gcal/key/index.php', $this->uploads_dir . 'index.php' );
                }
                 
                // Copy key file to uploads folder
                $kfn = $this->get_key_file( $user_id, $product_id ). '.p12';
                if ( $kfn && is_dir( $kff ) && !file_exists( $kff . $kfn ) && file_exists( $this->plugin_dir . 'gcal/key/' . $kfn ) ) {
                    @copy( $this->plugin_dir . 'gcal/key/' . $kfn, $kff . $kfn );
                }
            }
        }
        
        /**
         * Try to create an encrypted key file folder
         * @return string
         * @since 2.6
         */
         
        function create_key_file_folder( ) {
            if ( !is_dir( $this->uploads_dir . 'bkap_uploads/' ) ) {
                @mkdir( $this->uploads_dir . 'bkap_uploads/' );
            }
        }
        
	   
        /**
	    * Return GCal API mode (none, app2gcal or sync )
	    * @return string
	    */
        function get_api_mode( $user_id, $product_id = 0 ) {
            $integration_mode = 'disabled';
            if ( isset( $product_id ) && 0 != $product_id ) {
                $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
            
                if ( isset( $booking_settings[ 'product_sync_integration_mode' ] ) ) {
                    $integration_mode = $booking_settings[ 'product_sync_integration_mode' ];
                }
            
            }
            // in a scenario where sync is disabled at the product level, the admin/tour operator settings need to be used
            if ( isset( $integration_mode ) && 'disabled' == $integration_mode || ( ! isset( $integration_mode ) ) ) {
            // get the user role
                $user = new WP_User( $user_id );
                if( isset( $user->roles[ 0 ] ) && 'tour_operator' == $user->roles[ 0 ] ) {
                    $integration_mode = get_the_author_meta( 'tours_calendar_sync_integration_mode', $user_id );
                } else {
                    $integration_mode = get_option( 'bkap_calendar_sync_integration_mode' );
                }
            } 
            return $integration_mode;
        }
	   
        /**
    	 * Return GCal service account
    	 * @return string
    	 */
        function get_service_account( $user_id, $product_id ) {
            $gcal_service_account = '';
            
            if ( isset( $product_id ) && 0 != $product_id ) {
                $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
                 
                if ( isset( $booking_settings[ 'product_sync_service_acc_email_addr' ] ) ) {
                    $gcal_service_account = $booking_settings[ 'product_sync_service_acc_email_addr' ];
                }
            } else {
                // get the user role
                $user = new WP_User( $user_id );
                if( isset( $user->roles[ 0 ] ) && 'tour_operator' == $user->roles[ 0 ] ) {
                    $gcal_service_account_arr = get_the_author_meta( 'tours_calendar_details_1', $user_id );
                    if( isset( $gcal_service_account_arr[ 'tours_calendar_service_acc_email_address' ] ) ) {
                        $gcal_service_account = $gcal_service_account_arr[ 'tours_calendar_service_acc_email_address' ];
                    } 
                } else {
                    $gcal_service_account_arr = get_option( 'bkap_calendar_details_1' );
                    if( isset( $gcal_service_account_arr[ 'bkap_calendar_service_acc_email_address' ] ) ) {
                        $gcal_service_account = $gcal_service_account_arr[ 'bkap_calendar_service_acc_email_address' ];
                    } 
                }
            }
            return $gcal_service_account;
    	}
    	
    	/**
    	 * Return GCal key file name without the extension
    	 * @return string
    	 */
    	function get_key_file( $user_id, $product_id ) {
    	    $gcal_key_file = '';
    	    	
    	    if ( isset( $product_id ) && 0 != $product_id ) {
    	        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
    	         
    	        if ( isset( $booking_settings[ 'product_sync_key_file_name' ] ) ) {
    	            $gcal_key_file = $booking_settings[ 'product_sync_key_file_name' ];
    	        }
    	    } else {
                // get the user role
        	    $user = new WP_User( $user_id );
        	    if( isset( $user->roles[ 0 ] ) && 'tour_operator' == $user->roles[ 0 ] ) {
        	        $gcal_key_file_arr = get_the_author_meta( 'tours_calendar_details_1', $user_id );
        	         
        	        if( isset( $gcal_key_file_arr[ 'tours_calendar_key_file_name' ] ) ) {
        	            $gcal_key_file = $gcal_key_file_arr[ 'tours_calendar_key_file_name' ];
        	        } 
        	    } else {
        	        $gcal_key_file_arr = get_option( 'bkap_calendar_details_1' );
        	        if( isset( $gcal_key_file_arr[ 'bkap_calendar_key_file_name' ] ) ) {
        	            $gcal_key_file = $gcal_key_file_arr[ 'bkap_calendar_key_file_name' ];
        	        } 
        	    }
    	    }
    	    	
    	    return $gcal_key_file;
    	}
    	
    	/**
    	 * Return GCal selected calendar ID
    	 * @return string
    	 */
    	function get_selected_calendar( $user_id, $product_id ) {
    	    
    	    $gcal_selected_calendar = '';
    	    if ( isset( $product_id ) && 0 != $product_id ) {
    	         
    	        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
    	         
    	        if ( isset( $booking_settings[ 'product_sync_calendar_id' ] ) ) {
    	            $gcal_selected_calendar = $booking_settings[ 'product_sync_calendar_id' ];
    	        }
    	    } else {
                // get the user role
        	    $user = new WP_User( $user_id );
        	    if( isset( $user->roles[ 0 ] ) && 'tour_operator' == $user->roles[ 0 ] ) {
        	        $gcal_selected_calendar_arr = get_the_author_meta( 'tours_calendar_details_1', $user_id );
        	        if( isset( $gcal_selected_calendar_arr[ 'tours_calendar_id' ] ) ) {
        	            $gcal_selected_calendar = $gcal_selected_calendar_arr[ 'tours_calendar_id' ];
        	        } 
        	    } else {
        	        $gcal_selected_calendar_arr = get_option( 'bkap_calendar_details_1' );
        	        if( isset( $gcal_selected_calendar_arr[ 'bkap_calendar_id' ] ) ) {
        	            $gcal_selected_calendar = $gcal_selected_calendar_arr[ 'bkap_calendar_id' ];
        	        } 
        	    }
    	    }
    	    
    	    return $gcal_selected_calendar;
    	}
    	
    	/**
    	 * Return GCal Summary (name of Event)
    	 * @return string
    	 */
    	function get_summary() {
    	    return get_option( 'bkap_calendar_event_summary' );
    	}
    	
    	/**
    	 * Return GCal description
    	 * @return string
    	 */
    	function get_description() {
    	    return get_option( 'bkap_calendar_event_description' );
    	}
    
    	/**
    	 * Checks if php version and extentions are correct
    	 * @return string (Empty string means suitable)
    	 */
    	function is_not_suitable( $user_id, $product_id ) {
    	    	
    	    if ( version_compare(PHP_VERSION, '5.3.0', '<') ) {
    	        return __( 'Google PHP API Client <b>requires at least PHP 5.3</b>', 'woocommerce-booking' );
    	    }
    	
    	    // Disabled for now
    	    if ( false && memory_get_usage() < 31000000 ) {
    	        return sprintf( __( 'Google PHP API Client <b>requires at least 32 MByte Server RAM</b>. Please check this link how to increase it: %s','appointments'), '<a href="http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP" target="_blank">'.__( 'Increasing_memory_allocated_to_PHP', 'woocommerce-booking' ).'</a>' );
    	    }
    	
    	    if ( !function_exists( 'curl_init' ) ) {
    	        return __( 'Google PHP API Client <b>requires the CURL PHP extension</b>', 'woocommerce-booking' );
    	    }
    	
    	    if ( !function_exists( 'json_decode' ) ) {
    	        return __( 'Google PHP API Client <b>requires the JSON PHP extension</b>', 'woocommerce-booking' );
    	    }
    	
    	    if ( !function_exists( 'http_build_query' ) ) {
    	        return __( 'Google PHP API Client <b>requires http_build_query()</b>', 'woocommerce-booking' );
    	    }
    	
    	    // Dont continue further if this is pre check
    	    if ( isset( $_POST[ 'gcal_api_pre_test' ] ) && 1== $_POST[ 'gcal_api_pre_test' ] ) {
    	        return __( 'Your server installation meets requirements.', 'woocommerce-booking' );
    	    }
    	
    	    if ( !$this->_file_exists( $user_id, $product_id ) ) {
    	        return __( '<b>Key file does not exist</b>', 'woocommerce-booking' );
    	    }
    	
    	    return '';
    	}
    	
    	/**
    	 * Checks if key file exists
    	 * @return bool
    	 */
    	function _file_exists( $user_id, $product_id ) {
            if ( file_exists( $this->key_file_folder(). $this->get_key_file( $user_id, $product_id ) . '.p12' ) ) {
    	        return true;
    	    } else if ( file_exists( $this->plugin_dir . 'gcal/key/'. $this->get_key_file( $user_id, $product_id ) . '.p12' ) ) {
    	        return true;
    	    } else {
    	        return false;
    	    }
    	}
    	
    	/**
    	 * Get contents of the key file
    	 * @return string
    	 */
    	function _file_get_contents( $user_id, $product_id ) {
    	    if ( file_exists( $this->key_file_folder( ). $this->get_key_file( $user_id, $product_id ) . '.p12' ) ) {
    	        return @file_get_contents( $this->key_file_folder(). $this->get_key_file( $user_id, $product_id ) . '.p12' );
    	    } else if ( file_exists( $this->plugin_dir . 'gcal/key/'. $this->get_key_file( $user_id, $product_id ) . '.p12' ) ) {
    	        return @file_get_contents( $this->plugin_dir . 'gcal/key/'. $this->get_key_file( $user_id, $product_id ) . '.p12' );
    	    } else {
    	        return '';
    	    }
    	}
    	
    	/**
    	 * Return key file folder name
    	 * @return string
    	 * @since 1.2.2
    	 */
    	function key_file_folder( ) {
    	    return $this->uploads_dir . 'bkap_uploads/';
    	}
    	
    	/**
    	 * Checks for settings and prerequisites
    	 * @return bool
    	 */
    	function is_active( $user_id,$product_id ) {
    	    // If integration is disabled, nothing to do
    	    if ( 'disabled' == $this->get_api_mode( $user_id, $product_id ) || '' == $this->get_api_mode( $user_id, $product_id ) || !$this->get_api_mode( $user_id, $product_id ) ) {
    	        return false;
    	    }
    	    if ( $this->is_not_suitable( $user_id, $product_id ) ) {
    	        return false;
    	    }
    	
    	    if ( $this->get_key_file( $user_id, $product_id ) &&  $this->get_service_account( $user_id, $product_id ) && $this->get_selected_calendar( $user_id, $product_id ) ) {
    	        return true;
    	    }
    	    // None of the other cases are allowed
    	    return false;
    	}
    	
    	/**
    	 * Connects to GCal API
    	 */
    	function connect( $user_id, $product_id ) {
    	    // Disallow faultly plugins to ruin what we are trying to do here
    	    @ob_start();
    	
    	    if ( !$this->is_active( $user_id,$product_id ) ) {
    	        return false;
    	    }
    	    // Just in case
    	    require_once $this->plugin_dir . 'external/google/Client.php';
    	
    	    $config = new BKAP_Google_BKAPGoogleConfig( apply_filters( 'bkap-gcal-client_parameters', array(
//    	        'cache_class' => 'BKAP_Google_Cache_Null', // For an example
    	    )));
    	
    	    $this->client = new BKAP_Google_Client( $config );
    	    $this->client->setApplicationName( "WooCommerce Booking and Appointment" );;
    	    $key = $this->_file_get_contents( $user_id, $product_id );
    	    $this->client->setAssertionCredentials( new BKAP_Google_Auth_AssertionCredentials(
    	        $this->get_service_account( $user_id, $product_id ),
    	        array( 'https://www.googleapis.com/auth/calendar' ),
    	        $key)
    	    );
    	
    	    $this->service = new BKAP_Google_Service_Calendar( $this->client );
    	
    	    return true;
    	}
    	
    	/**
    	 * Creates a Google Event object and set its parameters
    	 * @param app: Booking object to be set as event
    	 */
    	function set_event_parameters( $app ) {
    	    if ( get_option( 'bkap_calendar_event_location' ) != "" ) {
    	        $location = str_replace( array( 'ADDRESS', 'CITY' ), array( $app->client_address, $app->client_city ), get_option( 'bkap_calendar_event_location' ) );
    	    } else {
    	        $location = get_bloginfo( 'description' );
    	    }
    	
    	    $summary = str_replace( array( 'SITE_NAME', 'CLIENT', 'PRODUCT_NAME', 'PRODUCT_WITH_QTY', 'ORDER_DATE_TIME', 'ORDER_DATE', 'ORDER_NUMBER', 'PRICE', 'PHONE', 'NOTE', 'ADDRESS', 'EMAIL'),
    	        array( get_bloginfo( 'name' ), $app->client_name, $app->product, $app->product_with_qty, $app->order_date_time, $app->order_date, $app->id , $app->order_total, $app->client_phone, $app->order_note, $app->client_address, $app->client_email ), $this->get_summary() );
    	
    	    $description = str_replace( array( 'SITE_NAME', 'CLIENT', 'PRODUCT_NAME', 'PRODUCT_WITH_QTY','ORDER_DATE_TIME', 'ORDER_DATE', 'ORDER_NUMBER', 'PRICE', 'PHONE', 'NOTE', 'ADDRESS', 'EMAIL'),
    	        array( get_bloginfo( 'name' ), $app->client_name, $app->product, $app->product_with_qty, $app->order_date_time, $app->order_date, $app->id , $app->order_total, $app->client_phone, $app->order_note, $app->client_address, $app->client_email ), $this->get_description() );
    	
    	    // Find time difference from Greenwich as GCal asks UTC
    	    if ( !current_time( 'timestamp' ) ) {
    	        $tdif = 0;
    	    } else {
    	        $tdif = current_time( 'timestamp' ) - time();
    	    }
    	
    	    if( $app->start_time == "" && $app->end_time == "" ) {
    	        $start = new BKAP_Google_Service_Calendar_EventDateTime();
    	        $start->setDate( date( "Y-m-d", strtotime( $app->start ) ) );
    	
    	        $end = new BKAP_Google_Service_Calendar_EventDateTime();
    	        $end->setDate( date( "Y-m-d", strtotime( $app->end ) ) );
    	    } else if( $app->end_time == "" ) {
    	        $start = new BKAP_Google_Service_Calendar_EventDateTime();
    	        $start->setDateTime( date( "Y-m-d\TH:i:s\Z", strtotime( $app->start . " " . $app->start_time ) - $tdif ) );
    	
    	        $end = new BKAP_Google_Service_Calendar_EventDateTime();
    	        $end->setDateTime( date( "Y-m-d\TH:i:s\Z", strtotime( '+30 minutes', strtotime( $app->end . " " . $app->start_time ) )  - $tdif ) );
    	    } else {
    	        $start = new BKAP_Google_Service_Calendar_EventDateTime();
    	        $start->setDateTime( date( "Y-m-d\TH:i:s\Z", strtotime( $app->start . " " . $app->start_time ) - $tdif ) );
    	
    	        $end = new BKAP_Google_Service_Calendar_EventDateTime();
    	        $end->setDateTime( date( "Y-m-d\TH:i:s\Z", strtotime( $app->end . " " . $app->end_time ) - $tdif ) );
    	    }
    	
    	    $email = $app->client_email;
    	    $attendee1 = new BKAP_Google_Service_Calendar_EventAttendee();
    	    $attendee1->setEmail( $email );
    	    $attendees = array( $attendee1 );
    	
    	    $this->event = new BKAP_Google_Service_Calendar_Event();
    	    $this->event->setLocation( $location );
    	    $this->event->setStart( $start );
    	    $this->event->setEnd( $end );
    	    $this->event->setSummary( apply_filters(
    	        'bkap-gcal-set_summary',
    	        $summary
    	    ));
    	    $this->event->setDescription( apply_filters(
    	        'bkap-gcal-set_description',
    	        $description
    	    ));
    	}
    	
    	/**
    	 * Delete event from Gcal when an order is cancelled.
    	 * @since 2.6.3
    	 */
    	function delete_event( $item_id, $user_id, $product_id ) {
    	    	
    	    if ( !$this->connect( $user_id, $product_id ) ) {
    	        return false;
    	    }
    	    	
    	    $user = new WP_User( $user_id );
    	
    	    // calendar ID
    	    $calendar_id = $this->get_selected_calendar( $user_id, $product_id );
    	    
    	    // get the event UID
    	    $event_uid = '';
    	    $event_id = '';
    	    	
    	    if ( isset( $product_id ) && $product_id != 0 ) {        
    	        $booking_settings = get_post_meta( $product_id, 'woocommerce_booking_settings', true );
    	        
    	        $event_uids = get_post_meta( $product_id, 'bkap_event_uids_ids', true );
    	    } else if( isset( $user->roles[ 0 ] ) && 'tour_operator' == $user->roles[ 0 ] ) {
    	        $event_uids = get_the_author_meta( 'tours_event_uids_ids', $user_id );
    	    } else {
    	        $event_uids = get_option( 'bkap_event_uids_ids' );
    	    }
    	    	
    	    if ( is_array( $event_uids ) && count( $event_uids ) > 0 ) {
    	        if ( isset( $item_id ) && array_key_exists( $item_id, $event_uids ) ) {
    	           $event_id = $event_uids[ $item_id ];
    	           $event_uid = str_replace( '@google.com', '', $event_id );
    	        } else {
    	            $event_uid = '';
    	        }
    	    }
    	    
			$event = $this->service->events->get( $calendar_id, $event_uid );
			$event_status = $event->status;
			
    	    
    	    if ( $event_uid != '' && $calendar_id != '' && $event_status != "cancelled" ) {
                $deletedEvent = $this->service->events->delete( $calendar_id, $event_uid );
    	    }
	    }
    	     
    	/**
    	 * Inserts a booking to the selected calendar as event
    	 * @test: Insert a test booking
    	 */
    	function insert_event( $event_details, $event_id, $user_id, $product_id = 0, $test = false ) {
    	    if ( !$this->connect( $user_id, $product_id ) ) {
    	        return false;
    	    }
    	    global $wpdb;
    	    
    	    if( isset( $user_id ) ) {
    	        $address_1 = get_user_meta( $user_id, 'shipping_address_1' );
    	        $address_2 = get_user_meta( $user_id, 'shipping_address_2' );
    	        $first_name = get_user_meta( $user_id, 'shipping_first_name' );
    	        $last_name = get_user_meta( $user_id, 'shipping_last_name' );
    	        $phone = get_user_meta( $user_id, 'billing_phone' );
    	        $city = get_user_meta( $user_id, 'shipping_city' );
    	    } else {
    	        $address_1 = "";
    	        $address_2 = "";
    	        $first_name = "";
    	        $last_name = "";
    	        $phone = "";
    	        $city = "";
    	    }
    	    	
    	    if ( $test ) {
    	        $bkap = new stdClass();
    	        $bkap->start = date( 'Y-m-d', $this->local_time );
    	        $bkap->end = date( 'Y-m-d', $this->local_time );
    	        $bkap->start_time = date( "H:i:s", $this->local_time + 600 );
    	        $bkap->end_time = date( 'H:i:s', $this->local_time + 2400 );
    	        $client_email = get_user_meta( $user_id, 'billing_email' );
    	        if ( isset( $client_email[ 0 ] ) ) {
    	            $bkap->client_email = $client_email[ 0 ];
    	        } else {
    	            $bkap->client_email = '';
    	        }
    	        if( isset( $first_name[ 0 ] ) && isset( $last_name[ 0 ] ) ) {
    	            $bkap->client_name = $first_name[ 0 ] . " " . $last_name[ 0 ];
    	        } else {
    	            $bkap->client_name = "";
    	        }
    	        if( isset( $address_1[ 0 ] ) && isset( $address_2[ 0 ] ) ) {
    	            $bkap->client_address = $address_1[ 0 ] . " " . $address_2[ 0 ];
    	        } else {
    	            $bkap->client_address = "";
    	        }
    	
    	        if( isset( $city[ 0 ] ) ) {
    	            $bkap->client_city = __( $city[ 0 ], 'woocommerce-booking');
    	        } else {
    	            $bkap->client_city = "";
    	        }
    	
    	        if( isset( $phone[ 0 ] ) ) {
    	            $bkap->client_phone = $phone[ 0 ];
    	        } else {
    	            $bkap->client_phone = '';
    	        }
    	        $bkap->order_note  = "";
    	        $bkap->order_total  = "";
    	        $bkap->product = "";
    	        $bkap->product_with_qty = "";
    	        $bkap->order_date_time = "";
    	        $bkap->order_date = "";
    	        $bkap->id = "";
    	    } else {
    	        if ( isset( $event_details[ 'hidden_booking_date' ] ) && $event_details[ 'hidden_booking_date' ] != '' ) {
    	            $booking_date = $event_details[ 'hidden_booking_date' ];
    	            
    	            $bkap = new stdClass();
    	            $bkap->start = date( 'Y-m-d', strtotime( $booking_date ) );
    	            
    	            if ( isset( $event_details[ 'hidden_checkout_date' ] ) && $event_details[ 'hidden_checkout_date' ] != '' ) {
                        $checkout_date = $event_details[ 'hidden_checkout_date' ];
    	            } else {
                        $checkout_date = $event_details[ 'hidden_booking_date' ];
    	            }
    	            $bkap->end = date( 'Y-m-d', strtotime( $checkout_date ) );
    	            
                    if( isset( $event_details[ 'time_slot' ] ) && $event_details[ 'time_slot' ] != '' ) {
    	                $timeslot = explode( " - ", $event_details[ 'time_slot' ] );
    	                $from_time = date( "H:i", strtotime( $timeslot[ 0 ] ) );
    	                if( isset( $timeslot[ 1 ] ) && $timeslot[ 1 ] != '' ) {
    	                    $to_time = date( "H:i", strtotime( $timeslot[ 1 ] ) );
    	                    $bkap->end_time = $to_time;
    	                } else {
    	                    $bkap->end_time = '00:00';
    	                    $bkap->end = date( 'Y-m-d', strtotime( $event_details[ 'hidden_booking_date' ] . '+1 day' ) );
    	                }
    	                $bkap->start_time = $from_time;
    	            } else {
    	                $bkap->start_time = "";
    	                $bkap->end_time = "";
    	            }
    	             
    	            $bkap->client_email = $event_details[ 'billing_email' ];
    	            if ( get_option( 'woocommerce_calc_shipping' ) == 'yes' ) {
    	                if ( get_option( 'woocommerce_ship_to_destination' ) == 'shipping' ) {
    	                    if ( ( isset( $event_details[ 'shipping_first_name' ] ) && $event_details[ 'shipping_first_name' ] != '' ) && ( isset( $event_details[ 'shipping_last_name' ] ) && $event_details[ 'shipping_last_name' ] != '' ) ) {
    	                        $bkap->client_name = $event_details[ 'shipping_first_name' ] . " " . $event_details[ 'shipping_last_name' ];
    	                    } else {
    	                        $bkap->client_name = $event_details[ 'billing_first_name' ] . " " . $event_details[ 'billing_last_name' ];
    	                    }
    	                    if ( ( isset( $event_details[ 'shipping_address_1' ] ) && $event_details[ 'shipping_address_1' ] != '' ) && ( isset( $event_details[ 'shipping_address_2' ] ) && $event_details[ 'shipping_address_2' ] != '' ) ) {
    	                        $bkap->client_address = $event_details[ 'shipping_address_1' ] . " " . $event_details[ 'shipping_address_2' ];
    	                    } else {
    	                        $bkap->client_address = $event_details[ 'billing_address_1' ] . " " . $event_details[ 'billing_address_2' ];
    	                    }
    	                    if ( isset( $event_details[ 'shipping_city' ] ) && $event_details[ 'shipping_city' ] != '' ) {
    	                        $bkap->client_city = $event_details[ 'shipping_city' ];
    	                    } else {
    	                        $bkap->client_city = $event_details[ 'billing_city' ];
    	                    }
    	                } else if ( get_option( 'woocommerce_ship_to_destination' ) == 'billing' ) {
    	                    if ( ( isset( $event_details[ 'shipping_first_name' ] ) && $event_details[ 'shipping_first_name' ] != '' ) && ( isset( $event_details[ 'shipping_last_name' ] ) && $event_details[ 'shipping_last_name' ] != '' ) ) {
    	                        $bkap->client_name = $event_details[ 'shipping_first_name' ] . " " . $event_details[ 'shipping_last_name' ];
    	                    } else {
    	                        $bkap->client_name = $event_details[ 'billing_first_name' ] . " " . $event_details[ 'billing_last_name' ];
    	                    }
    	                    if ( ( isset( $event_details[ 'shipping_address_1' ] ) && $event_details[ 'shipping_address_1' ] != '' ) && ( isset( $event_details[ 'shipping_address_2' ] ) && $event_details[ 'shipping_address_2' ] != '' ) ) {
    	                        $bkap->client_address = $event_details[ 'shipping_address_1' ] . " " . $event_details[ 'shipping_address_2' ];
    	                    } else {
    	                        $bkap->client_address = $event_details[ 'billing_address_1' ] . " " . $event_details[ 'billing_address_2' ];
    	                    }
    	                    if ( isset( $event_details[ 'shipping_city' ] ) && $event_details[ 'shipping_city' ] != '' ) {
    	                        $bkap->client_city = $event_details[ 'shipping_city' ];
    	                    } else {
    	                        $bkap->client_city = $event_details[ 'billing_city' ];
    	                    }
    	                } else if ( get_option( 'woocommerce_ship_to_destination' ) == 'billing_only') {
    	                    $bkap->client_name = $event_details[ 'billing_first_name' ] . " " . $event_details[ 'billing_last_name' ];
    	                    $bkap->client_address = $event_details[ 'billing_address_1' ] . " " . $event_details[ 'billing_address_2' ];
    	                    $bkap->client_city = $event_details[ 'billing_city' ];
    	                }
    	            } else {
    	                $bkap->client_name = $event_details[ 'billing_first_name' ] . " " . $event_details[ 'billing_last_name' ];
    	                $bkap->client_address = $event_details[ 'billing_address_1' ] . " " . $event_details[ 'billing_address_2' ];
    	                $bkap->client_city = $event_details[ 'billing_city' ];
    	            }
    	            $bkap->client_phone = $event_details[ 'billing_phone' ];
    	            $bkap->order_note  = $event_details[ 'order_comments' ];
    	            $order = wc_get_order( $event_details[ 'order_id' ] );
    	            
    	            $product = $event_details[ 'product_name' ];
    	            $product_with_qty = $event_details[ 'product_name' ] . '(QTY: ' . $event_details[ 'product_qty' ] . ')';
    	            
    	            $bkap->order_total  = $event_details[ 'product_total' ];
    	            $bkap->product = $product;
    	            $bkap->product_with_qty = $product_with_qty;
    	            
    	            if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
                        $bkap->id   = $order->id;
                        $bkap->order_date_time = $order->post->post_date;
                        $order_date = date( "Y-m-d", strtotime( $order->post->post_date ) );    
                    } else {
                        $bkap->id   = $order->get_id();
                        $order_post = get_post( $event_details[ 'order_id' ] );
                        $post_date  = strtotime ( $order_post->post_date );
                        $bkap->order_date_time = date( 'Y-m-d H:i:s', $post_date );                        
                        $order_date = date( 'Y-m-d', $post_date );                    
                    }                   
                    
                    $bkap->order_date  = $order_date; 
    	            $bkap->item_id     = $event_id;

    	        }
    	    }
    	    	
    	    // Create Event object and set parameters
    	    $this->set_event_parameters( $bkap );
    	    // Insert event
    	    try {
    	        $createdEvent = $this->service->events->insert( $this->get_selected_calendar( $user_id, $product_id ), $this->event );
    	        $uid = $createdEvent->iCalUID;
    	         
    	        if ( 0 != $product_id ) {
    	            $event_orders = get_post_meta( $product_id, 'bkap_event_item_ids', true );
    	            $event_uids = get_post_meta( $product_id, 'bkap_event_uids_ids', true );
    	        } else {
                    // get the user role
        	        $user = new WP_User( $user_id );
        	        if( isset( $user->roles[ 0 ] ) && 'tour_operator' == $user->roles[ 0 ] ) {
        	            $event_orders = get_the_author_meta( 'tours_event_item_ids', $user_id );
        	            $event_uids = get_the_author_meta( 'tours_event_uids_ids', $user_id );
        	        } else {
        	            $event_orders = get_option( 'bkap_event_item_ids' );
        	            $event_uids = get_option( 'bkap_event_uids_ids' );
        	        }
    	        }
    	        
    	        if( $event_orders == '' || $event_orders == '{}' || $event_orders == '[]' || $event_orders == 'null' ) {
    	            $event_orders = array();
    	        }
    	        array_push( $event_orders, $event_id );
    	           	        
    	        if( $event_uids == '' || $event_uids == '{}' || $event_uids == '[]' || $event_uids == 'null' ) {
    	            $event_uids = array();
    	        }
    	        $event_uids[ $event_id ] = $uid;
    	        
    	        if ( 0 != $product_id ) {
    	            update_post_meta( $product_id, 'bkap_event_item_ids', $event_orders );
    	            update_post_meta( $product_id, 'bkap_event_uids_ids', $event_uids );
    	        } else {
                    if( isset( $user->roles[ 0 ] ) && 'tour_operator' == $user->roles[ 0 ] ) {
        	            update_user_meta( $user_id, 'tours_event_item_ids', $event_orders );
        	            update_user_meta( $user_id, 'tours_event_uids_ids', $event_uids );
        	        } else {
        	            update_option( 'bkap_event_item_ids', $event_orders );
        	            update_option( 'bkap_event_uids_ids', $event_uids );
        	        }
    	        }
    	        
    	        return true;
    	    } catch ( Exception $e ) {
    	        $this->log( "Insert went wrong: " . $e->getMessage() );
    	        return false;
    	    }
    	}
    	
    	/**
    	 * 
    	 * @param string $message
    	 */
    	function log( $message = '' ) {
    	    if ( $message ) {
    	        $to_put = '<b>['. date_i18n( $this->datetime_format, $this->local_time ) .']</b> '. $message;
    	        // Prevent multiple messages with same text and same timestamp
    	        if ( !file_exists( $this->log_file ) || strpos( @file_get_contents( $this->log_file ), $to_put ) === false )
    	            @file_put_contents( $this->log_file, $to_put . chr(10). chr(13), FILE_APPEND );
    	    }
    	}
    	
    	/**
    	 * Build GCal url for GCal Button. It requires UTC time.
    	 * @param start: Timestamp of the start of the app
    	 * @param end: Timestamp of the end of the app
    	 * @param php: If this is called for php. If false, called for js
    	 * @param address: Address of the appointment
    	 * @param city: City of the appointment
    	 * @return string
    	 */
    	function gcal( $bkap ) {
    	    // Find time difference from Greenwich as GCal asks UTC
    	    $summary = str_replace( array( 'SITE_NAME', 'CLIENT', 'PRODUCT_NAME', 'PRODUCT_WITH_QTY', 'ORDER_DATE_TIME', 'ORDER_DATE', 'ORDER_NUMBER','PRICE', 'PHONE', 'NOTE', 'ADDRESS', 'EMAIL'), 
    		    array( get_bloginfo( 'name' ), $bkap->client_name, $bkap->product, $bkap->product_with_qty, $bkap->order_date_time, $bkap->order_date, $bkap->id , $bkap->order_total, $bkap->client_phone, $bkap->order_note, $bkap->client_address, $bkap->client_email ), $this->get_summary() );
    		
    		$description = str_replace( array( 'SITE_NAME', 'CLIENT', 'PRODUCT_NAME', 'PRODUCT_WITH_QTY', 'ORDER_DATE_TIME', 'ORDER_DATE', 'ORDER_NUMBER','PRICE', 'PHONE', 'NOTE', 'ADDRESS', 'EMAIL'),
    		    array( get_bloginfo( 'name' ), $bkap->client_name, $bkap->product, $bkap->product_with_qty, $bkap->order_date_time, $bkap->order_date, $bkap->id , $bkap->order_total, $bkap->client_phone, $bkap->order_note, $bkap->client_address, $bkap->client_email ), $this->get_description() );
    	    
    	    if( $bkap->start_time == "" && $bkap->end_time == "" ) {
    	        $start = strtotime( $bkap->start );    	    
    	        $end = strtotime( $bkap->end . "+1 day");
    	        
/**    	        $gmt_start = get_gmt_from_date( date( 'Y-m-d', $start ), "Ymd" );
    	        $gmt_end = get_gmt_from_date( date( 'Y-m-d', $end ), "Ymd" ); **/
    	        
    	        // Using gmdate instead of get_gmt_from_date as the latter is not working correctly with Timezone Strings
    	        $gmt_start = gmdate( 'Ymd', $start );
    	        $gmt_end = gmdate( 'Ymd', $end );
    	        
    	    } else if( $bkap->end_time == "" ) {
    	        $start = strtotime( $bkap->start . " " . $bkap->start_time );
    	        $end = strtotime( $bkap->end . " " . $bkap->start_time );
    	        
    	        $gmt_start = get_gmt_from_date( date( 'Y-m-d H:i:s', $start ), "Ymd\THis\Z" );
    	        $gmt_end = get_gmt_from_date( date( 'Y-m-d H:i:s', $end ), "Ymd\THis\Z" );
    	    } else {
    	        $start = strtotime( $bkap->start . " " . $bkap->start_time );
    	        $end = strtotime( $bkap->end . " " . $bkap->end_time );
    	         
    	        $gmt_start = get_gmt_from_date( date( 'Y-m-d H:i:s', $start ), "Ymd\THis\Z" );
    	        $gmt_end = get_gmt_from_date( date( 'Y-m-d H:i:s', $end ), "Ymd\THis\Z" );
    	    }
    	    
    	    if ( get_option( 'bkap_calendar_event_location' ) != "" ) {
    	        $location = str_replace( array( 'ADDRESS', 'CITY' ), array( $bkap->client_address, $bkap->client_city ), get_option( 'bkap_calendar_event_location' ) );
    	    } else {
    	        $location = get_bloginfo( 'description' );
    	    }
    	    
    	    $param = array(
    	        'action' => 'TEMPLATE',
    	        'text' => $summary,
    	        'dates' => $gmt_start . "/" . $gmt_end,
    	        'location' => $location,
    	        'details' => $description
    	    );

    	    return esc_url( add_query_arg( array( $param, $start, $end ),
    	        'http://www.google.com/calendar/event'
            ) );
    	}
    	
    	function other_cal( $bkap ) {
    	    // Find time difference from Greenwich as GCal asks UTC
    	    $summary = str_replace( array( 'SITE_NAME', 'CLIENT', 'PRODUCT_NAME', 'PRODUCT_WITH_QTY', 'ORDER_DATE_TIME', 'ORDER_DATE', 'ORDER_NUMBER','PRICE', 'PHONE', 'NOTE', 'ADDRESS', 'EMAIL'),
    	        array( get_bloginfo( 'name' ), $bkap->client_name, $bkap->product, $bkap->product_with_qty, $bkap->order_date_time, $bkap->order_date, $bkap->id , $bkap->order_total, $bkap->client_phone, $bkap->order_note, $bkap->client_address, $bkap->client_email ), $this->get_summary() );
    	
    	    $description = str_replace( array( 'SITE_NAME', 'CLIENT', 'PRODUCT_NAME', 'PRODUCT_WITH_QTY', 'ORDER_DATE_TIME', 'ORDER_DATE', 'ORDER_NUMBER','PRICE', 'PHONE', 'NOTE', 'ADDRESS', 'EMAIL'),
    	        array( get_bloginfo( 'name' ), $bkap->client_name, $bkap->product, $bkap->product_with_qty, $bkap->order_date_time, $bkap->order_date, $bkap->id , $bkap->order_total, $bkap->client_phone, $bkap->order_note, $bkap->client_address, $bkap->client_email ), $this->get_description() );
    	
    	    if( $bkap->start_time == "" && $bkap->end_time == "" ) {
    	        $gmt_start = strtotime( $bkap->start );
    	        $gmt_end = strtotime( "+1 day", strtotime( $bkap->end ) );
    	    } else if( $bkap->end_time == "" ) {
    	        $time_start = explode( ":", $bkap->start_time );
    	        $gmt_start = strtotime( $bkap->start ) + $time_start[ 0 ]*60*60 + $time_start[ 1 ]*60 + ( time() - current_time('timestamp') );
    	        $gmt_end = strtotime( $bkap->end ) + $time_start[ 0 ]*60*60 + $time_start[ 1 ]*60 + ( time() - current_time('timestamp') );
    	    } else {
    	        $time_start = explode( ":", $bkap->start_time );
    	        $time_end = explode( ":", $bkap->end_time );
    	        $gmt_start = strtotime( $bkap->start ) + $time_start[ 0 ]*60*60 + $time_start[ 1 ]*60 + ( time() - current_time('timestamp') );
    	        $gmt_end = strtotime( $bkap->end ) + $time_end[ 0 ]*60*60 + $time_end[ 1 ]*60 + ( time() - current_time('timestamp') );
    	    }
    	
    	    if ( get_option( 'bkap_calendar_event_location' ) != "" ) {
    	        $location = str_replace( array( 'ADDRESS', 'CITY' ), array( $bkap->client_address, $bkap->client_city ), get_option( 'bkap_calendar_event_location' ) );
    	    } else {
    	        $location = get_bloginfo( 'description' );
    	    }
    	    	
    	    $current_time = current_time( 'timestamp' );
    	    	
    	    return plugins_url( "woocommerce-booking/includes/ical.php?event_date_start=$gmt_start&amp;event_date_end=$gmt_end&amp;current_time=$current_time&amp;summary=$summary&amp;description=$description&amp;event_location=$location" );
    	}
    	
    	 
    	function outlook_cal( $bkap ) {
    	    // Find time difference from Greenwich as GCal asks UTC
    	    $summary = str_replace( array( 'SITE_NAME', 'CLIENT', 'PRODUCT_NAME', 'PRODUCT_WITH_QTY', 'ORDER_DATE_TIME', 'ORDER_DATE', 'ORDER_NUMBER','PRICE', 'PHONE', 'NOTE', 'ADDRESS', 'EMAIL'),
    	        array( get_bloginfo( 'name' ), $bkap->client_name, $bkap->product, $bkap->product_with_qty, $bkap->order_date_time, $bkap->order_date, $bkap->id , $bkap->order_total, $bkap->client_phone, $bkap->order_note, $bkap->client_address, $bkap->client_email ), $this->get_summary() );
    	
    	    $description = str_replace( array( 'SITE_NAME', 'CLIENT', 'PRODUCT_NAME', 'PRODUCT_WITH_QTY', 'ORDER_DATE_TIME', 'ORDER_DATE', 'ORDER_NUMBER','PRICE', 'PHONE', 'NOTE', 'ADDRESS', 'EMAIL'),
    	        array( get_bloginfo( 'name' ), $bkap->client_name, $bkap->product, $bkap->product_with_qty, $bkap->order_date_time, $bkap->order_date, $bkap->id , $bkap->order_total, $bkap->client_phone, $bkap->order_note, $bkap->client_address, $bkap->client_email ), $this->get_description() );
    	
    	    if( $bkap->start_time == "" && $bkap->end_time == "" ) {
    	        $gmt_start = strtotime( $bkap->start );
    	        $gmt_end = strtotime( "+1 day", strtotime( $bkap->end ) );
    	    } else if( $bkap->end_time == "" ) {
    	        $time_start = explode( ":", $bkap->start_time );
    	        $gmt_start = strtotime( $bkap->start ) + $time_start[ 0 ]*60*60 + $time_start[ 1 ]*60 + ( time() - current_time('timestamp') );
    	        $gmt_end = strtotime( $bkap->end ) + $time_start[ 0 ]*60*60 + $time_start[ 1 ]*60 + ( time() - current_time('timestamp') );
    	    } else {
    	        $time_start = explode( ":", $bkap->start_time );
    	        $time_end = explode( ":", $bkap->end_time );
    	        $gmt_start = strtotime( $bkap->start ) + $time_start[ 0 ]*60*60 + $time_start[ 1 ]*60 + ( time() - current_time('timestamp') );
    	        $gmt_end = strtotime( $bkap->end ) + $time_end[ 0 ]*60*60 + $time_end[ 1 ]*60 + ( time() - current_time('timestamp') );
    	    }
    	
    	    if ( get_option( 'bkap_calendar_event_location' ) != "" ) {
    	        $location = str_replace( array( 'ADDRESS', 'CITY' ), array( $bkap->client_address, $bkap->client_city ), get_option( 'bkap_calendar_event_location' ) );
    	    } else {
    	        $location = get_bloginfo( 'description' );
    	    }
    	    	
    	    $current_time = current_time( 'timestamp' );
    	
    	    $param = array(
    	        'DTSTART   ' => $gmt_start,
    	        'DTEND' => $gmt_end,
    	        'SUMMARY' => $summary,
    	        'LOCATION' => $location,
    	        'DESCRIPTION' => $description
    	    );
    	     
    	    return str_replace( "http://", "webcal://" , plugins_url( "woocommerce-booking/Calendar-event.ics" ) ) ;
    	}
    	
    } // end of class
    
}// if not class exists