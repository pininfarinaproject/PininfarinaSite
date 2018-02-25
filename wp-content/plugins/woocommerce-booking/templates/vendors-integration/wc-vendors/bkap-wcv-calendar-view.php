<?php 
$class = array(
    'list' => '',
    'calendar' => 'active',
    'resources' => '',
);

include_once ( 'bkap-wcv-booking.php' );
?>
<div>
    <?php bkap_load_scripts_class::bkap_load_calendar_styles( '4.6.0' );?>
    <h2><?php _e( 'Calendar View', 'woocommerce-booking' );?></h2>
</div>

<div id="bkap_events_loader" style="font-size: medium;">
			Loading Calendar Events....<img src=<?php echo plugins_url() . "/woocommerce-booking/images/ajax-loader.gif"; ?>>
</div>

<div id='calendar'></div>

<?php
$vendor_id = get_current_user_id();
bkap_load_scripts_class::bkap_load_calendar_scripts( '4.6.0', $vendor_id );
?>