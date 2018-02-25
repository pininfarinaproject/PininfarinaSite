<?php

/**
 * Resource Custom Post Type Data.
 *
 *
 * @author  TycheSoftwares
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BKAP_Product_Resource class.
 *
 * @since 4.6.0
 */
class BKAP_Product_Resource {

	private $resource;
	private $product_id;
	private $id;

	/**
	 * Constructor
	 */
	public function __construct( $post , $product_id = 0 ) {
		if ( is_numeric( $post ) ) {
			$this->resource   = get_post( $post );
			$this->id         = $post;
		} else {
			$this->resource   = $post;
		}

		$this->product_id = $product_id;
	}	

	/**
	 * __isset function.
	 *
	 * @access public
	 * @param string $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->resource->$key );
	}

	/**
	 * __get function.
	 *
	 * @access public
	 * @param string $key
	 * @return string
	 */
	public function __get( $key ) {
		return $this->resource->$key;
	}

	/**
	 * Return the ID
	 * @return int
	 */
	public function get_id() {
		return $this->resource->ID;
	}

	/**
	 * Return the ID
	 * @return int
	 */
	public function set_id( $id ) {
		$this->resource->ID = $id;
	}

	/**
	 * Get the title of the resource
	 * @return string
	 */
	public function get_title() {
		return $this->resource->post_title;
	}

	/**
	 * Return if we have qty at resource level
	 * @return boolean
	 */
	public function has_qty() {
		return $this->get_qty() !== '';
	}

	/**
	 * Return the quantity set at resource level
	 * @return int
	 */
	public function get_qty() {
		return get_post_meta( $this->get_id(), 'qty', true );
	}

	/**
	 * Return the base cost
	 * @return int|float
	 */
	public function get_base_cost() {
		$costs = get_post_meta( $this->product_id, '_bkap_resource_base_costs', true );
		$cost  = isset( $costs[ $this->get_id() ] ) ? $costs[ $this->get_id() ] : '';

		return (float) $cost;
	}

	/**
	 * Return the block cost
	 * @return int|float
	 */
	public function get_block_cost() {
		$costs = get_post_meta( $this->product_id, '_resource_block_costs', true );
		$cost  = isset( $costs[ $this->get_id() ] ) ? $costs[ $this->get_id() ] : '';

		return (float) $cost;
	}

	/**
	 * Return the availability of resource
	 * @return string|array
	 */

	public function get_resource_availability() {
		
		$bkap_resource_availability = get_post_meta( $this->get_id(), '_bkap_resource_availability', true );
		
		return $bkap_resource_availability;
	}

	/**
	 * Return the quantity of resource
	 * @return string|array
	 */

	public function get_resource_qty() {
		
		$bkap_resource_qty = get_post_meta( $this->get_id(), '_bkap_resource_qty', true );
		
		return $bkap_resource_qty;
	}

	/**
	 * Inserting the post for Resouce
	 * @return int
	 */

	public static function bkap_create_resource( $add_resource_name ) {

		$id = wp_insert_post( array(
			'post_title'   => $add_resource_name,
			'menu_order'   => 0,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_type'    => 'bkap_resource',
		), true );

		if ( $id && ! is_wp_error( $id ) ) {
			
			update_post_meta( $id, '_bkap_resource_qty', 1 );
			update_post_meta( $id, '_bkap_resource_availability', array() );

			return $id;
		}
	}
}