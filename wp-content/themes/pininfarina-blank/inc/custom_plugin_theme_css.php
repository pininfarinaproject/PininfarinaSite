<?php 

	function custom_style_order_profile_builder() 
	{
	    if ( defined('WPPB_PLUGIN_URL') ) 
	    {
	        //dequeue the original plugin stylesheet by its handle
	        wp_dequeue_style( 'wppb_stylesheet' ); 

	        //re-enqueue the stylesheet but with an added dependency (your theme stylesheet)
	        wp_enqueue_style( 
	            'wppb_stylesheet', 
	            WPPB_PLUGIN_URL . '/assets/css/style-front-end.css', 
	            array('pininfarina-blank-style') 
	        );
	    }
	}

	add_action('wp_enqueue_scripts', 'custom_style_order_profile_builder', 100); 
