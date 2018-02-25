<?php
/**
 *  Dokan Dashboard Bookings Template
 *
 *  Load all Tabs and Base Area ti display content
 *
 *  @since 4.6.0
 *
 *  @package woocommerce-booking
 */
?>
<div class="dokan-dashboard-wrap">

	<?php

		/**
		 *  dokan_dashboard_content_before hook
		 *
		 *  @hooked get_dashboard_side_navigation
		 *
		 *  @since 4.6.0
		 */
		do_action( 'dokan_dashboard_content_before' );
		do_action( 'bkap_dokan_booking_content_before' );

		$bkap_url = dokan_get_navigation_url( 'bkap_dokan_booking' );
		$current_page = get_query_var( 'bkap_dokan_booking' );
	?>

	<div class="dokan-dashboard-content dokan-bkap-bookings-content">

		<?php

			/**
			 *  bkap_dokan_booking_inside_before hook
			 *
			 *  @since 4.6.0
			 */
			do_action( 'bkap_dokan_booking_inside_before', $current_page, $bkap_url );
		?>


		<article class="dokan-booking-area">

			<?php

				/**
				 *  dokan_order_inside_content Hook
				 *
				 *  @hooked dokan_order_listing_status_filter
				 *  @hooked dokan_order_main_content
				 *
				 *  @since 4.6.0
				 */
				do_action( 'bkap_dokan_booking_inside_content', $current_page, $bkap_url );

			?>

		</article>


		<?php

			/**
			 *  dokan_order_content_inside_after hook
			 *
			 *  @since 4.6.0
			 */
			do_action( 'bkap_dokan_booking_content_inside_after' );
		?>

	</div> <!-- #primary .content-area -->

	<?php

		/**
		 *  dokan_dashboard_content_after hook
		 *  dokan_order_content_after hook
		 *
		 *  @since 4.6.0
		 */
		do_action( 'dokan_dashboard_content_after' );
		do_action( 'bkap_dokan_booking_content_after' );

	?>

</div><!-- .dokan-dashboard-wrap -->