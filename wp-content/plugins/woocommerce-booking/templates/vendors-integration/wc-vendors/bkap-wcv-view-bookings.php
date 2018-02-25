<?php 

$class = array(
    'list' => 'active',
    'calendar' => '',
    'resources' => '',
);

include_once ( 'bkap-wcv-booking.php' );

$base_url = $_SERVER[ 'REQUEST_URI' ];

// get the vendor ID
$bkap_vendors = new BKAP_Vendors();
$vendor_id = get_current_user_id();

// get the total number of records
$total_count = $bkap_vendors->get_bookings_count( $vendor_id );

$total_count = 0;

?>

<div class="tabs-content" id="view_bookings">
    <div id="bkap_header">
        <h3><?php _e( 'View Bookings', 'woocommerce-booking' );?></h3>
    </div>
    
    <?php
    if( $total_count > 0 ) {
        $split_url = explode( '/', $base_url );
        array_pop( $split_url );
        $export_url =  implode( '/', $split_url );
    ?>
        <div id="bkap_export" class="align-right">
            <a href="<?php echo $export_url . '?custom=bkap-csv'; ?>" target="_blank" class="wcv-button button"><?php _e( 'CSV', 'woocommerce-booking' ); ?></a>
            <a href="<?php echo $export_url . '?custom=bkap-print'; ?>" target="_blank" class="wcv-button button"><?php _e( 'Print', 'woocommerce-booking' ); ?></a>
        </div>
    <?php 
    }?>
</div>
<div>
<?php
    
    // Confirm or cancel bookings if data has been passed
    if( isset( $_GET[ 'action' ] ) ) {
        // check the booking post ID
        if( isset( $_GET[ 'booking_id' ] ) && $_GET[ 'booking_id' ] > 0 ) {
    
            $bkap_id = $_GET[ 'booking_id' ];
            // confirm the post type
            if( get_post_type( $bkap_id ) == 'bkap_booking' ) {
                
                // set the new status
                switch( $_GET[ 'action' ] ) {
    
                    case 'bkap-confirm':
                        $new_status = 'confirmed';
                        break;
                    case 'bkap-cancel':
                        $new_status = 'cancelled';
                        break;
                    default:
                        $new_status = '';
                        break;
                }
                
                // Process the request
                if( $new_status !== '' ) {
                    $item_id = get_post_meta( $bkap_id, '_bkap_order_item_id', true );
                    bkap_booking_confirmation::bkap_save_booking_status( $item_id, $new_status );
                }
            }
        }
    }

    $per_page = 10;
    
    // get the number of pages
    $page_count = $bkap_vendors->get_number_of_pages( $vendor_id, $per_page );
  
    if( isset( $_GET[ 'pagenum' ] ) && $_GET[ 'pagenum' ] > 1 ) {
        $paged = $_GET[ 'pagenum' ];
    } else {
        $paged = 1;
    }

    // setup the links for pagination
    if ( $page_count > 1 ) :
        
        $page_links = paginate_links( array(
            'current'   => $paged,
            'total'     => $page_count,
            'base'      => $base_url . '%_%',
            'format'    => '&pagenum=%#%',
            'add_args'  => false,
            'type'      => 'array',
        ) );

        echo '<div class="pagination-wrap">';
        echo "<ul class='pagination'>\n\t<li>";
        echo join("</li>\n\t<li>", $page_links);
        echo "</li>\n</ul>\n";
        echo '</div>';
    
    endif;

    if( $total_count > 0 ) {
    ?>
    
        <table id="bkap_bookings_data" class="wcvendors-table wcvendors-table-order wcv-table">
            <tr>
                <th><span class="bkap_wcv_status status_head tips" data-tip="<?php esc_attr_e( 'Status', 'woocommerce-booking' ); ?>" ></span></th>
                <th><?php _e( 'ID', 'woocommerce-booking' ); ?></th>
                <th><?php _e( 'Booked Product', 'woocommerce-booking' ); ?></th>
                <th><?php _e( 'Booked by', 'woocommerce-booking' ); ?></th>
                <th><?php _e( 'Order', 'woocommerce-booking' ); ?></th>
                <th><?php _e( 'Start Date', 'woocommerce-booking' ); ?></th>
                <th><?php _e( 'End Date', 'woocommerce-booking' ); ?></th>
                <th><?php _e( 'Amount', 'woocommerce-booking' ); ?></th>
                <th><?php _e( 'Actions', 'woocommerce-booking' ); ?></th>
            <tr>
        
            <?php

            $booking_posts = $bkap_vendors->get_booking_data( $vendor_id, $paged, $per_page );
            if( is_array( $booking_posts ) && count( $booking_posts ) > 0 && $booking_posts != false ) { 
                foreach( $booking_posts as $booking_id => $post_data ) {
                    
                    $status = $post_data[ 'status' ];
                    $active_statuses = bkap_common::get_bkap_booking_statuses();
                    $status_label = array_key_exists( $status, $active_statuses ) ? $active_statuses[  $status ] : ucwords( $status );
                    
                    $can_edit_approved 		= WC_Vendors::$pv_options->get_option( 'can_edit_published_products' );
                     
                    if( $can_edit_approved ) {
                        // try to link to the edit product page in the dashboard
                        $product_name = $post_data[ 'product_name' ];
                    } else {
                        $product_name = $post_data[ 'product_name' ];
                    }
                    
                    $actions = '<button class="bkap-button wcv-tooltip bkap_edit" data-tip-text="Edit Booking" onclick="bkap_edit_booking_class.bkap_edit_bookings( ' . $post_data[ 'product_id' ] .', ' . $post_data['order_item_id'] . ' )"></button>';
                    
                    if( $status == 'pending-confirmation' ) {
                        $actions .= "<a href='?custom=bkap-booking&action=bkap-confirm&booking_id=$booking_id' class='bkap-button wcv-tooltip bkap_confirm' data-tip-text='Confirm Booking'></a>&nbsp;";
                    }
                    
                    $actions .= "<a href='?custom=bkap-booking&action=bkap-cancel&booking_id=$booking_id' class='bkap-button wcv-tooltip bkap_cancel' data-tip-text='Cancel Booking'></a>";
                     
                    ?>
                    <tr>
                        <td><span class="bkap_wcv_status status-<?php esc_attr_e( $status ); ?> wcv-tooltip" data-tip-text="<?php esc_attr_e( $status_label ); ?>" ><?php esc_html__( $status_label ); ?></span></td>
                        <td><?php echo "#" . $booking_id;?></td>
                        <td><strong><?php echo $product_name . " x " . $post_data[ 'qty' ];?></strong></td>
                        <td><?php echo $post_data[ 'customer_name' ];?></td>
                        <td><strong><?php echo "#" . $post_data[ 'order_id' ] . " - " . $post_data[ 'order_status' ] . "</strong><br>" . $post_data[ 'order_date' ];?></td>
                        <td><?php echo $post_data[ 'start' ];?></td>
                        <td><?php echo $post_data[ 'end' ];?></td>
                        <td><?php echo $post_data[ 'amount' ];?></td>
                        <td><?php echo $actions; ?></td>
                    </tr>
                    <?php 

                    do_action( 'bkap_wc_vendors_booking_list', $booking_id, $post_data );
                    
                }
            } else {
                ?><h6><?php _e( 'No Bookings found.', 'woocommerce-booking' );?></h6><?php
            }             
            ?>
        </table>
            <?php
        } else {
            ?><h6><?php _e( 'No Bookings found.', 'woocommerce-booking' );?></h6><?php 
        }
        
        if ( $page_count > 1 ) :
        
            echo '<div class="pagination-wrap">';
            echo "<ul class='pagination'>\n\t<li>";
            echo join("</li>\n\t<li>", $page_links);
            echo "</li>\n</ul>\n";
            echo '</div>';
            
        endif;
        
        ?>
        
</div>