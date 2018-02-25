<?php
/**
 *  Dokan Dashboard View Bookings Template
 *
 *  Load all Bookings template
 *
 *  @since 4.6.0
 *
 *  @package woocommerce-booking
 */
?>
<div class="bkap-view-booking">

	<div class="dokan-bkap-view-content">

		<?php $base_url = dokan_get_navigation_url( 'bkap_dokan_booking' ); ?>

		<?php $paged = isset( $_GET['pagenum'] ) ? $_GET['pagenum'] : 1; ?>

		<header class="dokan-dashboard-header">

			<span class="entry-title bkap-dokan-tab-title"><?php _e( 'View Bookings', 'woocommerce-booking' );?></span>

			<div class="dokan-right">

				<?php

					/**
					 * bkap_dokan_export_bookings
					 * 
					 * @since 4.6.0
					 */

				?>

				<a href="<?php echo $base_url . 'bkap_csv';?>" class="dokan-btn dokan-btn-sm dokan-btn-danger dokan-btn-theme" target="_blank"><?php _e( 'CSV', 'woocommerce-booking' );?></a>
				<a href="<?php echo $base_url . 'bkap_print';?>" class="dokan-btn dokan-btn-sm dokan-btn-danger dokan-btn-theme" target="_blank"><?php _e( 'Print', 'woocommerce-booking' );?></a>

			</div>

		</header>

		<article class="dokan-booking-area">

			<?php $booking_posts = BKAP_Vendors::get_booking_data( get_current_user_id(), $paged, 20 ); ?>

			<?php $num_of_pages = ceil( BKAP_Vendors::get_bookings_count( get_current_user_id() ) / 20 ); ?>

			<?php if( is_array( $booking_posts ) && count( $booking_posts ) > 0 && $booking_posts != false ) : ?>

				<?php if ( $num_of_pages > 1 ) :

					echo '<div class="pagination-wrap dokan-right">';
					$page_links = paginate_links( array(
						'current'   => $paged,
						'total'     => $num_of_pages,
						'base'      => $base_url . '%_%',
						'format'    => '?pagenum=%#%',
						'add_args'  => false,
						'type'      => 'array',
					) );

					echo "<ul class='pagination'>\n\t<li>";
					echo join("</li>\n\t<li>", $page_links);
					echo "</li>\n</ul>\n";
					echo '</div>';

				endif; ?>

				<table class="dokan-table dokan-table-striped dokan-bookings-table">
					<thead>
						<tr>
							<th><?php _e( 'Status', 'woocommerce-booking' ); ?></th>
							<th><?php _e( 'ID', 'woocommerce-booking' ); ?></th>
							<th><?php _e( 'Booked Product', 'woocommerce-booking' ); ?></th>
							<th><?php _e( 'Booked By', 'woocommerce-booking' ); ?></th>
							<th><?php _e( 'Order', 'woocommerce-booking' ); ?></th>
							<th><?php _e( 'Start Date', 'woocommerce-booking' ); ?></th>
							<th><?php _e( 'End Date', 'woocommerce-booking' ); ?></th>
							<th><?php _e( 'Amount', 'woocommerce-booking' ); ?></th>
							<th><?php _e( 'Action', 'woocommerce-booking' ); ?></th>
							<?php

								/**
								 *  bkap_dokan_add_columns_header_booking Hook
								 *
								 *  @since 4.6.0
								 */
								do_action( 'bkap_dokan_add_columns_header_booking' );
							?>
						</tr>
					</thead>
					<tbody>
						<?php foreach( $booking_posts as $booking_id => $post_data ) : ?>
							<tr>
								<td><?php echo apply_filters( 'bkap_dokan_booking_status', $post_data[ 'status' ] );?></td>
								<td><?php printf( __( '<strong>Booking #%s</strong>', 'woocommerce-booking' ), $booking_id );?></td>
								<td><?php echo $post_data[ 'product_name' ] . " x " . $post_data[ 'qty' ];?></td>
								<td><?php echo $post_data[ 'customer_name' ];?></td>
								<td><?php echo "<a href='". dokan_get_navigation_url( 'orders' ) ."?order_id=" . $post_data[ 'order_id' ] . "'><strong>#" . $post_data[ 'order_id' ] . "</strong></a> - " . $post_data[ 'order_status' ] . "<br>" . $post_data[ 'order_date' ];?></td>
								<td><?php echo $post_data[ 'start' ];?></td>
								<td><?php echo $post_data[ 'end' ];?></td>
								<td><?php echo $post_data[ 'amount' ];?></td>
								<td>
									<button 
										class="dokan-btn dokan-btn-default dokan-btn-sm tips bkap-dokan-btn" 
										data-toggle="tooltip" 
										data-placement="top" 
										title="<?php _e( 'View & Edit', 'woocommerce-booking' );?>"
										onclick="bkap_dokan_class.bkap_dokan_view_booking( <?php echo $post_data[ 'product_id' ];?>, <?php echo $post_data['order_item_id']; ?> )"
									>
										<i class="fa fa-eye">&nbsp;</i>
									</button>

									<?php if ( $post_data[ 'status' ] === 'pending-confirmation' || $post_data[ 'status' ] === 'cancelled' ) : ?>

										<button 
											class="dokan-btn dokan-btn-default dokan-btn-sm tips bkap-dokan-btn" 
											data-toggle="tooltip" 
											data-placement="top" 
											title="<?php _e( 'Confirm', 'woocommerce-booking' );?>"
											onclick="bkap_dokan_class.bkap_dokan_change_status( <?php echo $post_data['order_item_id']; ?>, 'confirmed' )"
										>
											<i class="fa fa-check">&nbsp;</i>
										</button>
									<?php endif; ?>

									<?php if ( $post_data[ 'status' ] !== 'cancelled' ) : ?>

										<button 
											class="dokan-btn dokan-btn-default dokan-btn-sm tips bkap-dokan-btn" 
											data-toggle="tooltip" 
											data-placement="top" 
											title="<?php _e( 'Cancel', 'woocommerce-booking' );?>"
											onclick="bkap_dokan_class.bkap_dokan_change_status( <?php echo $post_data['order_item_id']; ?>, 'cancelled' )"
										>
											<i class="fa fa-times">&nbsp;</i>
										</button>
									<?php endif; ?>
								</td>

								<?php

									/**
									 *  bkap_dokan_add_columns_booking Hook
									 *
									 *  @since 4.6.0
									 */
									do_action( 'bkap_dokan_add_columns_booking', $booking_id, $post_data );
								?>
							</tr>

							<?php

								/**
								 *  bkap_dokan_booking_list Hook
								 *
								 *  @since 4.6.0
								 */
								do_action( 'bkap_dokan_booking_list', $booking_id, $post_data );
							?>

						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $num_of_pages > 1 ) :

					echo '<div class="pagination-wrap dokan-right">';
					$page_links = paginate_links( array(
						'current'   => $paged,
						'total'     => $num_of_pages,
						'base'      => $base_url . '%_%',
						'format'    => '?pagenum=%#%',
						'add_args'  => false,
						'type'      => 'array',
					) );

					echo "<ul class='pagination'>\n\t<li>";
					echo join("</li>\n\t<li>", $page_links);
					echo "</li>\n</ul>\n";
					echo '</div>';

				endif; ?>

			<?php else: ?>

				<p class="dokan-info"><?php _e( 'No Bookings found!', 'woocommerce-booking' ); ?></p>

			<?php endif; ?>

		</article>


		<?php

			/**
			 *  dokan_order_content_inside_after hook
			 *
			 *  @since 4.6.0
			 */
			do_action( 'bkap_dokan_booking_list_after' );
		?>

	</div> <!-- #primary .content-area -->

	<?php

		/**
		 *  dokan_dashboard_content_after hook
		 *  dokan_order_content_after hook
		 *
		 *  @since 4.6.0
		 */
		do_action( 'bkap_dokan_booking_table_after' );

	?>

</div><!-- .dokan-dashboard-wrap -->