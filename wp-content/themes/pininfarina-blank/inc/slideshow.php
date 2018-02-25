<?php
function register_slideshow_cpt() {
  $labels = array(
      'name'                => 'Slideshow',
      'menu_name'           => 'Slideshow',
  );
  $args = array(
    'label'               => 'Slideshow',
    'description'         => 'Homepage Slideshow',
    'labels'              => $labels,
    'supports'            => array('title', 'editor', 'thumbnail'),
    'hierarchical'        => false,
    'public'              => false,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'menu_position'       => 8,
    'menu_icon'           => 'dashicons-admin-customizer',
    'show_in_admin_bar'   => true,
    'show_in_nav_menus'   => true,
    'can_export'          => true,
    'has_archive'         => false,
    'exclude_from_search' => false,
    'publicly_queryable'  => true,
    'capability_type'     => 'post',
  );
  register_post_type( 'slideshow',  $args );
}
add_action( 'init', 'register_slideshow_cpt' );

?>
