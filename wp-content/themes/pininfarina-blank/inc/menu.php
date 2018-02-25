<?php

// Registriamo il menu dell'header, in modo che poi possa essere posizionato nell'HTML
function register_custom_menus() {
  register_nav_menus( array(
      'header' 	=> 	'Header Menu',
      'footer1' => 'Footer Left',
      'footer2' => 'Footer Center',
      'footer3' => 'Footer Right',
  ));
}
add_action( 'init', 'register_custom_menus' );

//////////////////////////////
//     FUNZIONI GLOBALI     //
//////////////////////////////

// Le seguenti funzioni non vengono associate a nessun Hook, ma verranno usate nei nostri template

// Questa funzione serve per posizionare il menu header che abbiamo creato nell'hook poco sopra.
// Notare come theme_location porti lo stesso nome identificativo del menu che abbiamo registrato.
function header_menu() {
  wp_nav_menu(
      array(
          'theme_location'  => 'header',
          'menu'            => '',
          'container'       => '',
          'container_class' => '',
          'container_id'    => '',
          'menu_class'      => '',
          'menu_id'         => '',
          'echo'            => true,
          'fallback_cb'     => '',
          'before'          => '',
          'after'           => '',
          'link_before'     => '',
          'link_after'      => '',
          'items_wrap'      => '<ul class="header-menu" id="header-menu">%3$s</ul>',
          'depth'           => 2,
          'walker'          => ''
      )
  );
}

function footer_menu_1() {
  wp_nav_menu(
      array(
          'theme_location'  => 'footer1',
          'menu'            => '',
          'container'       => '',
          'container_class' => '',
          'container_id'    => '',
          'menu_class'      => '',
          'menu_id'         => '',
          'echo'            => true,
          'fallback_cb'     => '',
          'before'          => '',
          'after'           => '',
          'link_before'     => '',
          'link_after'      => '',
          'items_wrap'      => '<ul class="footer-menu-1">%3$s</ul>',
          'depth'           => 2,
          'walker'          => ''
      )
  );
}

function footer_menu_2() {
  wp_nav_menu(
      array(
          'theme_location'  => 'footer2',
          'menu'            => '',
          'container'       => '',
          'container_class' => '',
          'container_id'    => '',
          'menu_class'      => '',
          'menu_id'         => '',
          'echo'            => true,
          'fallback_cb'     => '',
          'before'          => '',
          'after'           => '',
          'link_before'     => '',
          'link_after'      => '',
          'items_wrap'      => '<ul class="footer-menu-2">%3$s</ul>',
          'depth'           => 2,
          'walker'          => ''
      )
  );
}

function footer_menu_3() {
  wp_nav_menu(
      array(
          'theme_location'  => 'footer3',
          'menu'            => '',
          'container'       => '',
          'container_class' => '',
          'container_id'    => '',
          'menu_class'      => '',
          'menu_id'         => '',
          'echo'            => true,
          'fallback_cb'     => '',
          'before'          => '',
          'after'           => '',
          'link_before'     => '',
          'link_after'      => '',
          'items_wrap'      => '<ul class="footer-menu-3">%3$s</ul>',
          'depth'           => 2,
          'walker'          => ''
      )
  );
}


?>
