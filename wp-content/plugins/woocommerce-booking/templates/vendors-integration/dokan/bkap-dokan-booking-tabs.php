<?php
/**
 *  Dokan Dashboard View Bookings Template
 *
 *  Load Booking Tabs View
 *
 *  @since 4.6.0
 *
 *  @package woocommerce-booking
 */
?>

<ul class="dokan_tabs">
	<?php
	$booking_url = dokan_get_navigation_url( 'bkap_dokan_booking' );
	foreach ( $bkap_menus as $key => $value) {
		$class = ( $current_page == $key ) ? ' class="active"' : '';
		if( $value[ 'tabs' ] !== false){
			printf( '<li%s><a href="%s">%s</a></li>', $class, $booking_url.$key, $value[ 'title' ] );
		}
	}
	?>
</ul>