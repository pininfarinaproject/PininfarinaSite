<?php

/**
 * Bookings and Appointment Plugin for WooCommerce
 *
 * Functions file for Vendors
 *
 * @author      Tyche Softwares
 * @package     Bookings and Appointment Plugin
 */

/**
 * Function for getting all the bookings
 * 
 * @param string $paged Page Number
 * @param string $offset Offset
 * @param string $vendor_id Vendor ID
 * @return object Posts Object
 * @since 4.6.0
 */
function bkap_vendors_get_bookings( $paged, $offset, $vendor_id ){

	$args = array(
		'post_type'      => 'bkap_booking',
		'paged'          => $paged,
		'posts_per_page' => $limit,
		'offset'         => $offset,
		'meta_key'       => '_bkap_booking_vendor_id',
		'meta_value'     => $vendor_id,
		);

	$query    = new WP_Query( $args );
	$bookings = $query->posts;
}