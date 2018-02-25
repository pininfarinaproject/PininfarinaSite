<?php

/**
 * Bookings and Appointment Plugin for WooCommerce
 *
 * Template for Bookings Box. This template shall be resued on Cart, Checkout and My Account Pages
 *
 * @author      Tyche Softwares
 * @package     Bookings and Appointment Plugin
 */ 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

wc_get_template( 
	'bookings/bkap-bookings-hidden-fields.php', 
	array(
		'product_id'		=> $product_id,
		'product_obj'		=> $product_obj,
		'booking_settings' 	=> $booking_settings,
		'global_settings'	=> $global_settings,
		'hidden_dates'      => $hidden_dates ), 
	'woocommerce-booking/', 
	BKAP_BOOKINGS_TEMPLATE_PATH );

if ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] === "on" ) {

	wc_get_template( 
		'bookings/bkap-bookings-date.php', 
		array(
			'product_id'		=> $product_id,
			'product_obj'		=> $product_obj,
			'booking_settings' 	=> $booking_settings,
			'global_settings'	=> $global_settings ), 
		'woocommerce-booking/', 
		BKAP_BOOKINGS_TEMPLATE_PATH );
}