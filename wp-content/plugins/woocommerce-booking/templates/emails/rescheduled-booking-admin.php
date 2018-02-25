<?php
/**
 * Admin booking rescheduled email
 */
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<?php $order = new WC_order( $booking->order_id ); ?>

<p><?php printf( __( 'Bookings have been rescheduled for an order from %s. The order is as follows:', 'woocommerce-booking' ), $order->get_formatted_billing_full_name() ); ?></p>

<h2>
	<a class="link" href="<?php echo esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ); ?>">
		<?php printf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ); ?>
	</a> 
	(<?php printf( '<time datetime="%s">%s</time>', $order->get_date_created()->format( 'c' ), wc_format_datetime( $order->get_date_created() ) ); ?>)
</h2>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<tbody>
		<tr>
			<th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Rescheduled Product', 'woocommerce-booking' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->product_title; ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( get_option( 'book_item-meta-date' ), 'woocommerce-booking' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->item_booking_date; ?></td>
		</tr>
		<?php
		if ( isset( $booking->item_checkout_date ) && '' != $booking->item_checkout_date ) { 
    		?>
    		<tr>
    			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( get_option( 'checkout_item-meta-date' ), 'woocommerce-booking' ); ?></th>
    			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->item_checkout_date ?></td>
    		</tr>
    		<?php 
		}
		if ( isset( $booking->item_booking_time ) && '' != $booking->item_booking_time ) {
	    ?>
    		<tr>
    			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( get_option( 'book_item-meta-time' ), 'woocommerce-booking' ); ?></th>
    			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->item_booking_time ?></td>
    		</tr>
		<?php 
		}
		?>
	</tbody>
</table>

<?php do_action( 'woocommerce_email_footer' ); ?>
