<?php
/**
 *  Dokan Dashboard Bookings Calendar Template
 *
 *  Load Calendar View template
 *
 *  @since 4.6.0
 *
 *  @package woocommerce-booking
 */
?>
<div class="bkap-calendar-booking">

	<?php

		/**
		 * bkap_dokan_before_calendar_view Hook
		 * 
		 * @since 4.6.0
		 */
		do_action( 'bkap_dokan_before_calendar_view' );

	?>

	<div class="dokan-bkap-view-content">

		<header class="dokan-dashboard-header">

			<span class="entry-title bkap-dokan-tab-title"><?php _e( 'Booking Calendar', 'woocommerce-booking' );?></span>

		</header>

		<article class="dokan-calendar-area">

			<div id="bkap_events_loader" style="font-size: medium;">
				
				<?php _e( 'Loading Calendar Events....', 'woocommerce-booking' );?>
				
				<img src=<?php echo plugins_url() . "/woocommerce-booking/images/ajax-loader.gif"; ?>>
			</div>
			<div id='calendar'></div>

		</article>

		<?php

			/**
			 *  dokan_order_content_inside_after hook
			 *
			 *  @since 4.6.0
			 */
			do_action( 'bkap_dokan_booking_calendar_after' );
		?>

	</div>

	<?php

		/**
		 *  bkap_dokan_calendar_after hook
		 *
		 *  @since 4.6.0
		 */
		do_action( 'bkap_dokan_calendar_after' );
	?>

</div>