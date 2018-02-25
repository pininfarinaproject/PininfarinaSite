<?php

$bkap_resource_by_customer 	= __( "Customer Assigned", "woocommerce-booking" );
$bkap_resource_automatic   	= __( 'Automatically Assigned', "woocommerce-booking" );

$bkap_resource_are 			= array( "bkap_customer_resource" 	=> $bkap_resource_by_customer, 
									 "bkap_automatic_resource" 	=> $bkap_resource_automatic
							  );

?>

<table class='form-table'>
	<tr>
		<th>
		    <label for="bkap_product_resource_lable">
		    	<?php _e( 'Label:', 'woocommerce-booking' );?>
		    </label>
		</th>
		
		<td>

			<?php
			$resource_label = Class_Bkap_Product_Resource::bkap_get_resource_label( $product_id );
			$resource_selection = Class_Bkap_Product_Resource::bkap_product_resource_selection( $product_id );

			?>

		    <input id="bkap_product_resource_lable" name= "bkap_product_resource_lable" value="<?php echo $resource_label; ?>" size="30" type="text" />
                
		</td>
		<td>
		    <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Enter the name to be appear on the front end for selecting resource', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png"/>
		</td>
	</tr>

	<tr>
		<th>
        	<label for="bkap_product_resource_selection">
        		<?php _e( 'Resources are:', 'woocommerce-booking' ); ?>
        	</label>
        </th>
        
        <td>
        	<select id="bkap_product_resource_selection" name="bkap_product_resource_selection">
        		<?php
        		foreach ( $bkap_resource_are as $key => $value ){
        			$selected = "";

        			if( $key == $resource_selection ) {
        				$selected = "selected";
        			}


        			echo '<option value="' . esc_attr( $key ) . '" '.$selected.'>'. esc_html( $value ) . '</option>';
				}
        		?>
        		
        	</select>
        </td>

        <td>
            <img class="help_tip" width="16" height="16" data-tip="<?php _e( 'Customer selected will allow customer to choose resource on the front end booking form', 'woocommerce-booking' ); ?>" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png"/>
        </td>
	</tr>
</table>
<hr/>

<p style="padding:1%;" class="notice notice-info">
	<i><?php _e( 'Resources are used if you have multiple bookable items, e.g. room types, instructors or ticket types. Availability for resources is global across all bookable products.', 'woocommerce-booking' ); ?></i>
</p>
<div id="bkap_resource_section">
	
	<table class="bkap_resource_info">
		<tr>
			<th><?php echo __('Resouce Title', 'woocommerce-booking'); ?></th>
			<th><?php echo __('Pricing', 'woocommerce-booking'); ?></th>
			<th><i class="fa fa-trash" aria-hidden="true"></i></th>
			<th>
				<a href="<?php echo admin_url( 'edit.php?post_type=bkap_resource' ); ?>" target="_blank">
					<i class="fa fa-external-link" aria-hidden="true"></i>
				</a>
			</th>
		</tr>

	<?php		
		$all_resources 				= Class_Bkap_Product_Resource::bkap_get_all_resources();
		$resources_of_product 		= Class_Bkap_Product_Resource::bkap_get_product_resources( $product_id );
		$resources_cost_of_product 	= Class_Bkap_Product_Resource::bkap_get_resource_costs( $product_id );
		$loop                 		= 0;
		
		if ( is_array($resources_of_product) && count( $resources_of_product ) > 0 ) {
			foreach ( $resources_of_product as $resource_id ) {

				if( get_post_status( $resource_id ) ) {
					$resource            = new BKAP_Product_Resource( $resource_id );
					$resource_base_cost  = isset( $resources_cost_of_product[ $resource_id ] ) ? $resources_cost_of_product[ $resource_id ] : '';
					include( BKAP_BOOKINGS_TEMPLATE_PATH . 'meta-boxes/html-bkap-resource.php' );
					$loop++;
				}
				
			}
		}
	?>
	</table>

	<div class="bkap_resource_add_section">
		
		<a href="<?php echo admin_url( 'edit.php?post_type=bkap_resource' ); ?>" target="_blank"><?php _e( 'All Resources', 'woocommerce-booking' ); ?></a>

		<button type="button" class="button button-primary bkap_add_resource"><?php _e( 'Add/link Resource', 'woocommerce-booking' ); ?></button>
		<select name="add_resource_id" class="bkap_add_resource_id" >
			<option value=""><?php _e( 'New resource', 'woocommerce-booking' ); ?></option>
			<?php
				if ( $all_resources ) {
					foreach ( $all_resources as $resource ) {
						echo '<option value="' . esc_attr( $resource->ID ) . '">#' . absint( $resource->ID ) . ' - ' . esc_html( $resource->post_title ) . '</option>';
					}
				}
			?>
		</select>
		
	</div>
</div>