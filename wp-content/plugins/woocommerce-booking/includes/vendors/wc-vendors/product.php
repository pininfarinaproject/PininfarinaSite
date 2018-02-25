<?php

class bkap_wcv_product {
    
    public function __construct() {
        
        // Add the booking tab
        add_filter( 'wcv_product_meta_tabs', array( &$this, 'bkap_add_booking_tab' ) );
        // Add the booking meta box in the Booking tab
        add_action( 'wcv_after_variations_tab', array( &$this, 'bkap_add_tab_data' ), 10, 1 );
        
    } // end of construct
    
    function bkap_add_booking_tab( $tabs_array ) {
    
        $tabs_array[ 'bkap_booking' ] = array(
            'label'  => __( 'Booking', 'woocommerce-booking'),
            'target' => 'bkap_booking',
            'class'  => array( 'show_if_simple', 'show_if_variable', ),
        );
    
        return $tabs_array;
    }
    
    function bkap_add_tab_data( $product_id ){
    
        if( $product_id > 0 ) { // it's an existing product
            $plugin_version_number = get_option( 'woocommerce_booking_db_version' );
                     
            global $post;
            $post = get_post( $product_id, OBJECT );
            $results = setup_postdata( $post );
            
            bkap_load_scripts_class::bkap_load_products_css( $plugin_version_number );
            bkap_load_scripts_class::bkap_load_zozo_css( $plugin_version_number );
        
            ?>
            <div class="wcv_bkap_booking tabs-content" id="bkap_booking">
                <?php  
                bkap_booking_box_class::bkap_meta_box();
                ?>
                
            </div>
            <?php

            $ajax_url = get_admin_url() . 'admin-ajax.php';
            
            bkap_load_scripts_class::bkap_common_admin_scripts_js( $plugin_version_number );
            bkap_load_scripts_class::bkap_load_product_scripts_js( $plugin_version_number, $ajax_url );

            wp_register_script(
                'bkap-wcv',
                plugins_url().'/woocommerce-booking/js/vendors/wc-vendors/product.js',
                '',
                $plugin_version_number,
                true 
            );
            
            wp_localize_script(
                'bkap-wcv',
                'bkap_wcv_params',
                array(
                    'ajax_url'                   => $ajax_url,
                    'post_id'                    => $product_id,
                )
            );
            
            wp_enqueue_script( 'bkap-wcv' );
            
            wp_enqueue_style(
                'bkap-wcv-products',
                plugins_url() . '/woocommerce-booking/css/vendors/wc-vendors/products.css',
                '',
                $plugin_version_number,
                false
            );
            
            ?>
            
            <script type="text/javascript">
                jQuery(document).ready(function () {
                     jQuery("#bkap-tabbed-nav").zozoTabs({
                           
                           orientation: "vertical",
                           position: "top-left",
                           size: "medium",
                           animation: {
                                easing: "easeInOutExpo",
                                duration: 400,
                                effects: "none"
                           },
                     });

                     // Hide the Booking Resource checkbox
                     jQuery( '.bkap_type_box' ).hide();
                });
            </script>
                
            <?php
        } else {
            ?>
            <div class="wcv_bkap_booking tabs-content" id="bkap_booking"><?php _e( 'Please save the product once to make sure you can add the Booking Settings.', 'woocommerce-booking' ); ?></div>
            <?php 
        } 
    }
     
    
} // end of class
$bkap_wcv_product = new bkap_wcv_product();
?>