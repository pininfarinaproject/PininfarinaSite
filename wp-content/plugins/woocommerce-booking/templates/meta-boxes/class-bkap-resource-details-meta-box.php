<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BKAP_Resource_Details_Meta_Box.
 */
class BKAP_Resource_Details_Meta_Box {

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
	 * Is meta boxes saved once?
	 *
	 * @var boolean
	 */
	private static $saved_meta_box = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id         = 'bkap-resource-data';
		$this->title      = __( 'Resource details', 'woocommerce-booking' );
		$this->context    = 'normal';
		$this->priority   = 'high';
		$this->post_types = array( 'bkap_resource' );

		add_action( 'save_post', array( $this, 'bkap_save_resources' ), 10, 2 );

		wp_enqueue_style( 'bkap-booking', plugins_url( 'woocommerce-booking/css/booking.css' ) , '','1.0', false );
	}

	/**
	 * Show meta box.
	 */
	public function meta_box_inner( $post ) {
		
		$post_id  = $post->ID;
		$resource = new BKAP_Product_Resource( $post_id );

		?>
		<div class="panel-wrap" id="bkap_resource_availability">
			<div class="options_group">
			<?php
				woocommerce_wp_text_input( array(
					'id'                => '_bkap_booking_qty',
					'label'             => __( 'Available Quantity : ', 'woocommerce-booking' ),			
					'value'             => max( $resource->get_resource_qty(), 1 ),			
					'type'              => 'number',
					'custom_attributes' => array(
						'min'           => '0',
						'step' 	        => '1',
					),
					'style'             => 'width: 100px;',
					'title'       		=> __( 'The quantity of this resource available at any given time.', 'woocommerce-booking' )
				) );
			?>
			</div>
		<div class="options_group">
			<table class="widefat">
				<thead>
					<tr>
						<th><b><?php esc_html_e( 'Range type', 'woocommerce-booking' ); ?></b></th>
						<th><b><?php esc_html_e( 'From', 'woocommerce-booking' ); ?></b></th>
						<th></th>
						<th><b><?php esc_html_e( 'To', 'woocommerce-booking' ); ?></b></th>
						<th><b><?php esc_html_e( 'Bookable', 'woocommerce-booking' ); ?></b></th>
						<th><b><?php esc_html_e( 'Priority', 'woocommerce-booking' ); ?></b></th>
						<th class="remove" width="1%">&nbsp;</th>
					</tr>
				</thead>
				
				<tfoot>
					<tr >
						<th colspan="4" style="text-align: left;font-size: 11px;font-style: italic;">
							<?php esc_html_e( 'Rules with lower priority numbers will override rules with a higher priority (e.g. 9 overrides 10 ).', 'woocommerce-booking' ); ?>
						</th>	
						<th colspan="3" style="text-align: right;">
							<a href="#" class="button button-primary bkap_add_row_resource" style="text-align: right;" data-row="<?php
								ob_start();
								include( 'html_resource_availability_table.php' );
								$html = ob_get_clean();
								echo esc_attr( $html );
							?>"><?php esc_html_e( 'Add Range', 'woocommerce-booking' ); ?></a>
						</th>
					</tr>
				</tfoot>
				
				<tbody id="availability_rows">
					<?php
						$values = $resource->get_resource_availability();

						if ( ! empty( $values ) && is_array( $values ) ) {
							foreach ( $values as $availability ) {
								include( 'html_resource_availability_table.php' );
							}
						}
					?>
				</tbody>
			</table>
		</div>
		<?php
	}
}

return new BKAP_Resource_Details_Meta_Box();

?>