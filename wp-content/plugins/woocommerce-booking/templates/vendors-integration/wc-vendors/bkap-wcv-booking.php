<?php
// get plugin version number
$plugin_version = get_option( 'woocommerce_booking_db_version' );

// Add CSS File
bkap_load_scripts_class::bkap_wcv_dashboard_css( $plugin_version );

?>
<div class="bkap-wcv-tabs top">
    <ul class="tabs-nav" style="padding:0; margin:0;" >
        <li class="<?php echo $class[ 'list' ];?>" ><a href="?custom=bkap-booking"><?php _e( 'View Bookings', 'woocommerce-booking' );?></a></li>
        <li class="<?php echo $class[ 'calendar' ];?>" ><a href="?custom=calendar-view"><?php _e( 'Calendar View', 'woocommerce-booking' );?></a></li>
  <!--       <li class="<?php // echo $class[ 'resources' ];?>" ><a href="?custom=bkap-resources"><?php // _e( 'Booking Resources', 'woocommerce-booking' );?></a></li>  -->
    </ul>
</div>