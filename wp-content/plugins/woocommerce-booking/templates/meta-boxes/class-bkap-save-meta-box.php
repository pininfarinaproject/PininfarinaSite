<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Bookings_Save_Meta_Box.
 */
class BKAP_Save_Meta_Box {

	/**
	 * Meta box ID.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Meta box title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Meta box context.
	 *
	 * @var string
	 */
	public $context;

	/**
	 * Meta box priority.
	 *
	 * @var string
	 */
	public $priority;

	/**
	 * Meta box post types.
	 * @var array
	 */
	public $post_types;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id         = 'bkap-booking-save';
		$this->title      = __( 'Booking actions', 'woocommerce-booking' );
		$this->context    = 'side';
		$this->priority   = 'high';
		$this->post_types = array( 'bkap_booking' );
	}

	/**
	 * Render inner part of meta box.
	 */
	public function meta_box_inner( $post ) {
		wp_nonce_field( 'bkap_save_booking_meta_box', 'bkap_save_booking_meta_box_nonce' );

		?>
<!-- 	Should be uncommented when we stop using the custom plugin tables	
        <div id="delete-action"><a class="submitdelete deletion" href="<?php // echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php // _e( 'Move to trash', 'woocommerce-booking' ); ?></a></div>  -->
        
        <div id="delete-action"><a class="submitdelete deletion" href="javascript:void(0)" id="bkap_delete"><?php _e( 'Move to trash', 'woocommerce-booking' ); ?></a></div>
        <br><br>
        <input type="button" class="button bkap_cancel button-primary tips" name="bkap_cancel" value="<?php _e( 'Cancel', 'woocommerce-booking' ); ?>" data-tip="<?php _e( 'Cancel', 'woocommerce-booking' ); ?>" />
		<input type="submit" style='margin-left:40px;' class="button save_order button-primary tips" name="bkap_save" value="<?php _e( 'Save Booking', 'woocommerce-booking' ); ?>" data-tip="<?php _e( 'Save/update the booking', 'woocommerce-booking' ); ?>" />
		<?php
	}
}
return new BKAP_Save_Meta_Box();
