<?php 
if ( class_exists( 'WP_Widget' ) ) {
class Custom_WooCommerce_Widget_Product_Search extends WP_Widget {
 
/**
* This function will set the basic information for widget.
*/
public function __construct() {

	/* Widget variable settings. */
	$this->woo_widget_cssclass         = 'Custom_widget_product_search';
	$this->woo_widget_description      = __( 'Allows customers to search all the products based on checkin & checkout dates.', 'woocommerce-booking' );
	$this->woo_widget_idbase           = 'woocommerce_booking_availability_search';
	$this->woo_widget_name             = __( 'Booking & Appointment Availability Search', 'woocommerce-booking' );

	/* Widget settings. */
	$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

	/* Create the widget. */
	parent::__construct( 'custom_product_search', $this->woo_widget_name, $widget_ops );
}

/**
 * This function return the custom page url for widget.
 *
 * @see WP_Widget
 * @access public
 * @param array $args
 * @param array $instance
 * @return void
 */

function get_custom_page_url( $page_name ) {
    global $wpdb;
    
    $page_name_id   = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s  AND post_status = 'publish' AND post_type = 'page' ", $page_name ) );
    $page_permalink = get_permalink( $page_name_id );
    return $page_permalink;
}
/**
 * This function display the widget on the front end.
 */
	function widget( $args, $instance ) {
		extract( $args );

        $start_date = '';
		if ( isset( $instance[ 'start_date_label' ] ) ) {
		    $start_date   = __( $instance[ 'start_date_label' ], 'woocommerce-booking' );
		}
		$end_date = '';
		if ( isset( $instance[ 'end_date_label' ] ) ) {
            $end_date     = __( $instance[ 'end_date_label' ], 'woocommerce-booking' );
		}
		$search_label = '';
		if ( isset( $instance[ 'search_label' ] ) ) {
            $search_label = __( $instance['search_label'], 'woocommerce-booking' );
		}
		$clear_label = __( 'Clear Filter', 'woocommerce-booking' );
		if ( isset( $instance[ 'clear_label' ] ) ) {
            $clear_label = __( $instance['clear_label'], 'woocommerce-booking' );
		}
		$clear_button_css = 'none';
		if ( $clear_label !== '' ) {
			$clear_button_css = 'inline';
		}
		$clear_filter = 'bkap_clear_filter()';
		$text_label = '';
		if ( isset( $instance[ 'text_label' ] ) ) {
            $text_label   = trim( __( $instance['text_label'], 'woocommerce-booking' ) );
		}
		$text_information = '';
		if( isset( $text_label ) && $text_label != '' ){
		    $text_information = "<tr>
	                               <td colspan='2' style= 'text-align: center;'>$text_label</td>
	                            </tr>";
		}
		
		if ( isset( $instance['enable_day_search_label'] ) && $instance['enable_day_search_label'] == 'on' ){
		    $allow_single_day_search  = __( $instance['enable_day_search_label'], 'woocommerce-booking' );
		    $hide_checkout_field      = 'none';
		}else{
		    $allow_single_day_search  = '';
		    $hide_checkout_field      = 'table-row';
		}

		// category dropdown
		$category_label = '';
		if ( isset( $instance[ 'category_title' ] ) ) {
		    $category_label = __( $instance[ 'category_title' ], 'woocommerce-booking' );
		}

		if ( isset( $instance['category'] ) && $instance['category'] == 'on' ){
		    $allow_category_search = __( $instance['category'], 'woocommerce-booking' );
		    $hide_category_filter = 'table-row';
		}else{
		    $allow_category_search = '';
		    $hide_category_filter = 'none';
		}
		
		if ( isset( $instance['title_label'] ) ) {
			$title = __( $instance['title_label'], 'woocommerce-booking' );
		} else {
			$title = '';
		}
		
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $before_widget;
			
			if ( $title )
            echo $before_title . $title . $after_title;
            
            $url            = plugins_url();
            $action         = get_permalink( wc_get_page_id( 'shop' ) );
            $add_anchor_tag = '';
            $add_anchor_tag = apply_filters( "bkap_add_search_widget_id", $add_anchor_tag ) ;
            
            if ( $add_anchor_tag != '' ){
                $action .= "#". $add_anchor_tag;
            }
            
        $calendar_theme = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        
        if ( isset( $calendar_theme ) ) {
        		$calendar_theme_sel   = $calendar_theme->booking_themes;
        		$booking_language     = $calendar_theme->booking_language;
        		$date_format          = $calendar_theme->booking_date_format;
    	} else {        
                $calendar_theme_sel     = "smoothness";
                $booking_language       = "en-GB";
                $date_format            = "yy-mm-dd";
    	}

    	$bkap_language = '';
    	if ( defined('ICL_LANGUAGE_CODE') ){
    		
    	    if ( ICL_LANGUAGE_CODE == 'en' ) {
    			$booking_language = "en-GB";
    		} else{
    			$booking_language = ICL_LANGUAGE_CODE;
    		}

    		if ( isset($_GET['lang']) && $_GET['lang'] != '' ) {
    			$bkap_language = '<input type="hidden" name="lang" id="lang" value="'. $booking_language .'"/>';
    		}

    	}

    	// Ensure that the start and end date are retained in the widget
    	$start_date_value  = $end_date_value = $category_value = '';
    	$date_formats      = bkap_get_book_arrays('bkap_date_formats');
    	$php_date_format   = $date_formats[ $date_format ];
	
    	if ( isset( $_SESSION['start_date'] ) ) {
    		$start_date_value = date($php_date_format,strtotime($_SESSION['start_date']));
    	}
    	if ( isset( $_SESSION['end_date'] ) ) {
    		$end_date_value = date( $php_date_format, strtotime( $_SESSION['end_date'] ) );
    	}
    	
    	$contents = '';
    	// category drop down
    	if ( isset( $_SESSION['selected_category'] ) ) {
    	    $category_value = $_SESSION['selected_category'] ;
    	}

    	if( $category_value != 'disable' ){
    	    $taxonomy     = 'product_cat';
    	    $orderby      = 'name';
    	    $show_count   = 0;      // 1 for yes, 0 for no
    	    $pad_counts   = 0;      // 1 for yes, 0 for no
    	    $hierarchical = 1;      // 1 for yes, 0 for no
    	    $title        = '';
    	    $empty        = 0;
    	
    	    $args = array(
    	        'taxonomy'     => $taxonomy,
    	        'orderby'      => $orderby,
    	        'show_count'   => $show_count,
    	        'pad_counts'   => $pad_counts,
    	        'hierarchical' => $hierarchical,
    	        'title_li'     => $title,
    	        'hide_empty'   => $empty
    	    );
    	    $all_categories = get_categories( $args );
    	    foreach ($all_categories as $cat) {
    	        if($cat->category_parent == 0) {
    	            $category_id = $cat->term_id;
    	            $category [$category_id]  = $cat->name;
    	
    	        }
    	    }

    	    $contents = "<select id='cat' name='select_cat'><option value = '0'> All Categories </option>";
    	    $selected = '';
    	    if(isset($category)){
    	        foreach ($category as $c_key => $c_value){
    	            $selected = '';
    	            if($c_key == $category_value){
    	                $selected = "selected";
    	            }
    	
    	
    	            $contents .= "<option value=".$c_key." ".$selected." >".$c_value."</option>";
    	        }
    	    }
    	    $contents .= "</select> <input type='hidden' id='w_category' name='w_category' value = ".$category_value." >";
    	    	
    	}
	
    	$admin_url     = get_admin_url();
    	$admin_url    .= 'admin-ajax.php';

    	$plugin_version_number = get_option( 'woocommerce_booking_db_version' );
    	
        wp_enqueue_style( 'jquery-ui', "//code.jquery.com/ui/1.9.2/themes/$calendar_theme_sel/jquery-ui.css",'', $plugin_version_number, false );
        wp_enqueue_script( 'jquery-ui' );
        wp_enqueue_style( 'bkap-booking', plugins_url( '/css/booking.css', __FILE__ ) , '', $plugin_version_number , false );
        wp_register_script( 'jquery-ui-datepicker2', "$url/woocommerce-booking/js/i18n/jquery.ui.datepicker-$booking_language.js" );
        wp_enqueue_script( 'jquery-ui-datepicker2' );
        wp_enqueue_script( 'jquery-ui-datepicker' );

$abc =  <<<HTML
<script type="text/javascript">
jQuery(document).ready(function(){	
var today = new Date();
var dd = today.getDate();		
				
	jQuery( "#w_check_in" ).datepicker({
	 	minDate: today,
	 	dateFormat:"$date_format",
	 	altField: "#w_checkin",
	 	altFormat: "yy-mm-dd",
	 	onClose: function( selectedDate ) {
												
			jQuery("#w_check_out" ).datepicker("option", "minDate", selectedDate);

			var selected_cat_value =  jQuery('#cat :selected').val();
			jQuery("#w_category").val(selected_cat_value);

			var check_enable_single_day = "$allow_single_day_search";
			if( check_enable_single_day == "on" ){
			   var set_checkout = jQuery('#w_check_in').val();
			   jQuery("#w_check_out").val(set_checkout);
			   
			   var set_checkout_hidden = jQuery('#w_checkin').val();
			   jQuery("#w_checkout").val(set_checkout_hidden);
			}else {
				jQuery("#w_check_out").datepicker( "show" );
			}
		}
	});
	
	jQuery("#cat").change(function() {
	
		var selected_cat_value =  jQuery('#cat :selected').val();
		jQuery("#w_category").val(selected_cat_value);
	
	});
	
	jQuery("#w_check_out").datepicker({
		minDate:today,
		dateFormat:"$date_format",
		altField: "#w_checkout",
		altFormat: "yy-mm-dd",
		onClose:function (selectedDate) {

			var selected_cat_value =  jQuery('#cat :selected').val();
		    jQuery("#w_category").val(selected_cat_value);			
		}
	});


	jQuery("#w_check_in").datepicker("option",jQuery.datepicker.regional[ "$booking_language" ]);
	jQuery("#w_check_out").datepicker("option",jQuery.datepicker.regional[ "$booking_language" ]);
	
	jQuery("#ui-datepicker-div").wrap("<div class=\"hasDatepicker\"></div>");

	jQuery("#bkap_clear").on("click", function() {
		var clear_data = {
			action: 'clear_widget_dates'
		};
		jQuery.post("$admin_url", clear_data, function(response) {
			jQuery("#w_check_in").val('');
			jQuery("#w_check_out").val('');
			jQuery("#cat").val(0);
		});
	});
});
</script>
<div id="wrapper">
	<form role="search" method="get" id="searchform" action="$action">
	<input type="hidden" id="w_allow_category" name="w_allow_category" value="$allow_category_search">
	$bkap_language
	<table>
		<tr>
			<td>$start_date&nbsp;</td>
			<td><input  id="w_check_in" name="w_check_in" style="width:160px" value="$start_date_value" type="text" readonly/><input type="hidden" id="w_checkin" name="w_checkin"></td>
		</tr>
		<tr style= "display:$hide_checkout_field" >
			<td>$end_date&nbsp;</td>
			<td><input id="w_check_out" name="w_check_out" style="width:160px" value="$end_date_value" type="text"  readonly/><input type="hidden" id="w_checkout" name="w_checkout">
			</td>
		</tr>
		<tr style="display: $hide_category_filter">
			<td>$category_label</td>
			<td>$contents</td>
		</tr>
		<tr>
	        <td colspan="2" style= "text-align: center;">
	        	<input type="submit" id="search" value="$search_label" />
	        	<input type="button" id="bkap_clear" value="$clear_label" style="display: $clear_button_css" />
        	</td>
		</tr>
	$text_information
</table>
</form>
 </div>
HTML;
echo $abc;

		echo $after_widget;
	}

/**
 * This function will chnage the value when save buutton click on admin widgets page..
 *
 * @see WP_Widget->update
 * @access public
 * @param array $new_instance
 * @param array $old_instance
 * @return array
 */
function update( $new_instance, $old_instance ) {
	$instance['start_date_label']          = strip_tags( stripslashes( $new_instance['start_date_label'] ) );
	$instance['end_date_label']            = strip_tags( stripslashes( $new_instance['end_date_label'] ) );
	$instance['search_label']              = strip_tags( stripslashes( $new_instance['search_label'] ) );
	$instance['clear_label']               = strip_tags( stripslashes( $new_instance['clear_label'] ) );
	$instance['text_label']                = stripslashes( $new_instance['text_label'] );
	$instance['title_label']               = stripslashes( $new_instance['title_label'] );
	$instance['category_title']            = strip_tags( stripslashes( $new_instance['category_title'] ) );

	if ( isset( $new_instance['category'] ) ) {
		$instance['category']              = strip_tags( stripslashes( $new_instance['category'] ) );	
	}

	$instance['enable_day_search_label'] = '';
	if ( isset( $new_instance[ 'enable_day_search_label' ] ) ) {
	   $instance['enable_day_search_label']   = stripslashes( $new_instance['enable_day_search_label'] );
	} 
	return $instance;
}

/**
 * This function display the setting field on the admin side.
 *
 * @see WP_Widget->form
 * @access public
 * @param array $instance
 * @return void
 */
function form( $instance ) {
	global $wpdb;
	?>
		<p><label for="<?php echo $this->get_field_id('title_label'); ?>"><?php _e( 'Title Label:', 'woocommerce-booking' ) ?></label>
		<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title_label') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title_label') ); ?>" value="<?php if (isset ( $instance['title_label'])) {echo esc_attr( $instance['title_label'] );} ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('start_date_label'); ?>"><?php _e( 'Start Date Label:', 'woocommerce-booking' ) ?></label>
		<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('start_date_label') ); ?>" name="<?php echo esc_attr( $this->get_field_name('start_date_label') ); ?>" value="<?php if (isset ( $instance['start_date_label'])) {echo esc_attr( $instance['start_date_label'] );} ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('end_date_label'); ?>"><?php _e( 'End Date Label:', 'woocommerce-booking' ) ?></label>
		<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('end_date_label') ); ?>" name="<?php echo esc_attr( $this->get_field_name('end_date_label') ); ?>" value="<?php if (isset ( $instance['end_date_label'])) {echo esc_attr( $instance['end_date_label'] );} ?>" /></p>
		
		<p><label for="<?php echo $this->get_field_id( 'category' ); ?>"><?php _e( 'Search By Category:', 'woocommerce-booking' ) ?></label>
        	<?php
            
	            $search_category = '';
	            if ( isset ( $instance['category'] ) && $instance['category'] == 'on' ) {
	                $search_category = 'checked';
			    }
			?>
		
		<input class="checkbox" type="checkbox" <?php echo $search_category;?> id="<?php echo esc_attr( $this->get_field_id('category') ); ?>" name="<?php echo esc_attr( $this->get_field_name('category') ); ?>" />
		<p><label for="<?php echo $this->get_field_id('category_title'); ?>"><?php _e( 'Category Title:', 'woocommerce-booking' ) ?></label>
		<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('category_title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('category_title') ); ?>" value="<?php if (isset ( $instance['category_title'])) {echo esc_attr( $instance['category_title'] );} ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('search_label'); ?>"><?php _e( 'Search Button Label:', 'woocommerce-booking' ) ?></label>
		<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('search_label') ); ?>" name="<?php echo esc_attr( $this->get_field_name('search_label') ); ?>" value="<?php if (isset ( $instance['search_label'])) {echo esc_attr( $instance['search_label'] );} ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('clear_label'); ?>"><?php _e( 'Clear Button Label:', 'woocommerce-booking' ) ?></label>
		<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('clear_label') ); ?>" name="<?php echo esc_attr( $this->get_field_name('clear_label') ); ?>" value="<?php if (isset ( $instance['clear_label'])) {echo esc_attr( $instance['clear_label'] );} ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('text_label'); ?>"><?php _e( 'Text (appears below Search button)', 'woocommerce-booking' ) ?></label>
		<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id('text_label') ); ?>" name="<?php echo esc_attr( $this->get_field_name('text_label') ); ?>"><?php if (isset ( $instance['text_label'])) {echo esc_attr( $instance['text_label'] );} ?></textarea></p>
		

		<p><label for="<?php echo $this->get_field_id( 'enable_day_search_label' ); ?>"><?php _e( 'Hide the End date field:', 'woocommerce-booking' ) ?></label>
		
        <?php
            
            $enable_single_day_search = '';
            if ( isset ( $instance['enable_day_search_label'] ) && $instance['enable_day_search_label'] == 'on' ) {
                $enable_single_day_search = 'checked';
		    }
		 ?>
		
		<input class="checkbox" type="checkbox" <?php echo $enable_single_day_search;?> id="<?php echo esc_attr( $this->get_field_id('enable_day_search_label') ); ?>" name="<?php echo esc_attr( $this->get_field_name('enable_day_search_label') ); ?>" />

	<?php
}

	public static function clear_widget_dates() {

		session_destroy();
		die();
	}
}
}