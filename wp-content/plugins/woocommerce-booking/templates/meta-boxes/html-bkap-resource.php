<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<tr class="bkap_resource_row">
	<td class="bkap_resource_title" style="width:45%">
		<strong><span class="resource_name"><?php echo esc_html( $resource->get_title() ); ?></span> &dash;  #<?php echo esc_html( $resource->get_id() ); ?></strong>
		<input type="hidden" name="resource_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $resource->get_id() ); ?>" />

	</td>

	<td class="bkap_resource_field" style="width:35%">
		<label><?php _e( 'Base Cost', 'woocommerce-booking' ); ?>:</label>
		<input type="number" class="" name="resource_cost[<?php echo $loop; ?>]" title="<?php echo __( 'Base cost will be added to the total booking price.','woocommerce-booking' ); ?>" value="<?php if ( ! empty( $resource_base_cost ) ) echo esc_attr( $resource_base_cost ); ?>" placeholder="0.00" step="0.01" />		
	</td>
	
	<td class="bkap_remove_resource_button" id="bkap_remove_resource_<?php echo esc_attr( absint( $resource->get_id() ) ); ?>">
		<i class="fa fa-trash" aria-hidden="true"></i>
	</td>

	<td class="bkap_resource_edit_link">
		<a href="<?php echo admin_url( 'post.php?post=' . absint( $resource->get_id() ) . '&action=edit' ); ?>" target="_blank" class="bkap_edit_resource">
			<i class="fa fa-external-link" aria-hidden="true"></i>
		</a>
	</td>
</tr>
