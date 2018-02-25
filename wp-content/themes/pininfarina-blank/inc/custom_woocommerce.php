
<?php 
	$public_authority = add_role( 
				'public_authority', 
				__('Public Authority' ),
				array('read' => true)
			);

  	$public_authority = get_role('public_authority');
	$public_authority->add_cap('should_not_pay');

	$role = get_role( 'customer' );
  	$role->remove_cap( 'should_not_pay' );


	function return_custom_price($price, $product) 
	{
	    if (current_user_can('should_not_pay')) 
			return 0;
		else
			return $price;
	}

	function hide_price_html( $price, $product ) 
	{
		if (current_user_can('should_not_pay'))
			$price = "";
		return $price;
	}

	add_filter('woocommerce_get_price', 'return_custom_price', 10, 2);
	add_filter( 'woocommerce_get_price_html', 'hide_price_html', 10, 2);

	function new_name_product_subcategories( $args = array() ) 
	{  
		global $wp_query;

		$defaults = array(
			'before'        => '',
			'after'         => '',
			'force_display' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		// Main query only.
		if ( ! is_main_query() && ! $force_display ) {
			return;
		}

		// Don't show when filtering, searching or when on page > 1 and ensure we're on a product archive.
		if ( is_search() || is_filtered() || is_paged() || ( ! is_product_category() && ! is_shop() ) ) {
			return;
		}

		// Check categories are enabled.
		if ( is_shop() && '' === get_option( 'woocommerce_shop_page_display' ) ) {
			return;
		}

		// Find the category + category parent, if applicable.
		$term 			= get_queried_object();
		$parent_id 		= empty( $term->term_id ) ? 0 : $term->term_id;

		if ( is_product_category() ) {
			$display_type = get_woocommerce_term_meta( $term->term_id, 'display_type', true );

			switch ( $display_type ) {
				case 'products' :
					return;
				break;
				case '' :
					if ( '' === get_option( 'woocommerce_category_archive_display' ) ) {
						return;
					}
				break;
			}
		}

		// NOTE: using child_of instead of parent - this is not ideal but due to a WP bug ( https://core.trac.wordpress.org/ticket/15626 ) pad_counts won't work.
		$product_categories = get_categories( apply_filters( 'woocommerce_product_subcategories_args', array(
			'parent'       => $parent_id,
			'menu_order'   => 'ASC',
			'hide_empty'   => 0,
			'hierarchical' => 1,
			'taxonomy'     => 'product_cat',
			'pad_counts'   => 1,
		) ) );

		if ( apply_filters( 'woocommerce_product_subcategories_hide_empty', true ) ) {
			$product_categories = wp_list_filter( $product_categories, array( 'count' => 0 ), 'NOT' );
		}

		if ( $product_categories ) {
			echo wp_kses_post( $before );

			
			foreach ( $product_categories as $category ) {
				echo "<div class=\"subcategory-container\">";
					wc_get_template( 'content-product_cat.php', array(
						'category' => $category
					) );
				echo "<div class=\"subcategory-link\"><span>Al laboratorio</span><div class=\"triangle\"></div></div></div>";
				
			}

			// If we are hiding products disable the loop and pagination.
			if ( is_product_category() ) {
				$display_type = get_woocommerce_term_meta( $term->term_id, 'display_type', true );

				switch ( $display_type ) {
					case 'subcategories' :
						$wp_query->post_count    = 0;
						$wp_query->max_num_pages = 0;
					break;
					case '' :
						if ( 'subcategories' === get_option( 'woocommerce_category_archive_display' ) ) {
							$wp_query->post_count    = 0;
							$wp_query->max_num_pages = 0;
						}
					break;
				}
			}

			if ( is_shop() && 'subcategories' === get_option( 'woocommerce_shop_page_display' ) ) {
				$wp_query->post_count    = 0;
				$wp_query->max_num_pages = 0;
			}

			echo wp_kses_post( $after );

			return true;
		}
	}
