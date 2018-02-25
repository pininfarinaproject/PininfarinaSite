<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header( 'shop' ); ?>

	<?php
		/**
		 * woocommerce_before_main_content hook.
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20
		 * @hooked WC_Structured_Data::generate_website_data() - 30
		 */
		do_action( 'woocommerce_before_main_content' );
	?>

    <header class="woocommerce-products-header">

		<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>

			<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
		<?php endif; ?>

		<?php
			/**
			 * woocommerce_archive_description hook.
			 *
			 * @hooked woocommerce_taxonomy_archive_description - 10
			 * @hooked woocommerce_product_archive_description - 10
			 */
			do_action( 'woocommerce_archive_description' );
		?>

	</header>

		<?php if ( have_posts() ) : ?>
			
		<!-- Qua ci va la piantina -->

		<?php if ($_SERVER['REQUEST_URI']=='/categoria-prodotto/laboratori/elettronica/' || $_SERVER['REQUEST_URI']=='/categoria-prodotto/laboratori/informatica/' || $_SERVER['REQUEST_URI']=='/categoria-prodotto/laboratori/meccanica/'): ?>

		<div class="planimetria-container">
			<div class="container_image_planimetry">
				<img class="imagePlanimetry" src="<?php  echo get_field('image_planimetry', get_ID_by_page_name('Piantina'))['url']; ?>" alt="Planimetria">
				<!--<img class="imagePlanimetry" src="<?php //the_field('image_planimetry',get_ID_by_page_name('Piantina'))['url']; ?>" alt="Planimetria">-->
				<!--<img class="imagePlanimetry" src="<?php  //echo get_field('image_planimetry')['url']; ?>" alt="Planimetria">-->
			</div>
			<div class="container_testo_titolo_planimetria">
				<div class="title_planimetry"><?php the_field('title_planimetry',get_ID_by_page_name('Piantina'))?></div>
				<div class="testo_planimetria"><?php the_field('descrption_planimetry',get_ID_by_page_name('Piantina')) ?></div>
			</div>

		</div>

		<?php endif ?> 	
		<!-- Qua finisce la piantina -->

			<?php
				/**
				 * woocommerce_before_shop_loop hook.
				 *
				 * @hooked wc_print_notices - 10
				 * @hooked woocommerce_result_count - 20
				 * @hooked woocommerce_catalog_ordering - 30
				 */
				do_action( 'woocommerce_before_shop_loop' );
			?>

				


			<?php woocommerce_product_loop_start(); ?>
					
				<?php new_name_product_subcategories(); ?>
	
				<?php while ( have_posts() ) : the_post(); ?>
					
					<?php
						/**
						 * woocommerce_shop_loop hook.
						 *
						 * @hooked WC_Structured_Data::generate_product_data() - 10
						 */
						do_action( 'woocommerce_shop_loop' );
					?>
					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>

			<?php
				/**
				 * woocommerce_after_shop_loop hook.
				 *
				 * @hooked woocommerce_pagination - 10
				 */
				do_action( 'woocommerce_after_shop_loop' );
			?>

		<?php elseif ( ! woocommerce_product_subcategories( array( 'before' => woocommerce_product_loop_start( false ), 'after' => woocommerce_product_loop_end( false ) ) ) ) : ?>

			<?php
				/**
				 * woocommerce_no_products_found hook.
				 *
				 * @hooked wc_no_products_found - 10
				 */
				do_action( 'woocommerce_no_products_found' );
			?>
	
		<?php endif; ?>

	<?php
		/**
		 * woocommerce_after_main_content hook.
		 *
		 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
		 */
		do_action( 'woocommerce_after_main_content' );
	?>

	<?php
		/**
		 * woocommerce_sidebar hook.
		 *
		 * @hooked woocommerce_get_sidebar - 10
		 */
		do_action( 'woocommerce_sidebar' );
	?>

<input type="number" id="numPagina" value="2" />
	<!-- Qua ci va il tutorial -->
<?php if ($_SERVER['REQUEST_URI']=='/categoria-prodotto/laboratori/'): ?>
	<?php $post_id = get_ID_by_page_name('Tutorial'); ?>
	<div class="s_aboutUs_secondTitle s_aboutUs_h1Style">Tutorial</div>

	<div class="container-tutorial">
		<div class="tutorial-image-container col-6">
			 <img class="col-12 " src="<?php  echo get_field('immagine_tutorial_principiante', $post_id)['url']; ?>" alt="aa">
		</div>
		<div class="tutorial-box col-6">
	    <div class="tutorial-title"><?php the_field('titolo_principiante', $post_id) ?></div>
			<p><?php the_field('tutorial_principiante', $post_id) ?></p>
			<div class="tutorial-subtitle"><?php the_field('sotto_titolo_principiante', $post_id) ?></div>
			<?php $field_object = get_field_object('tutorial_lista_macchinari_principiante', $post_id); ?>
			<ul class="list-machines">
				<?php foreach ($field_object['value'] as $key => $value): ?>
				 	<li><a href="<?php echo $value->guid; ?>"><?php echo $value->post_title; ?></a></li>
				<?php endforeach ?>
			</ul>
		</div>
	</div>
	<div class="container-tutorial">
		<div class="tutorial-image-container col-6">
			 <img class="col-12 " src="<?php  echo get_field('immagine_tutorial_intermedio', $post_id)['url']; ?>" alt="aa">
		</div>
		<div class="tutorial-box col-6">
			<div class="tutorial-title"><?php the_field('titolo_intermedio', $post_id) ?></div>
			<p><?php the_field('tutorial_intermedio', $post_id) ?></p>
			<div class="tutorial-subtitle"><?php the_field('sotto_titolo_intermedio', $post_id) ?></div>
			<?php $field_object = get_field_object('tutorial_lista_macchinari_intermedio', $post_id); ?>
			<ul class="list-machines">
				<?php foreach ($field_object['value'] as $key => $value): ?>
				 	<li><a href="<?php echo $value->guid; ?>"><?php echo $value->post_title; ?></a></li>
				<?php endforeach ?>
			</ul>
		</div>
	</div>
	<div class="container-tutorial">
		<div class="tutorial-image-container col-6">
			 <img class="col-12 " src="<?php  echo get_field('immagine_tutorial_esperto', $post_id)['url']; ?>" alt="aa">
		</div>
		<div class="tutorial-box col-6">
			<div class="tutorial-title"><?php the_field('titolo_esperto', $post_id) ?></div>
			<p><?php the_field('tutorial_esperto', $post_id) ?></p>
			<div class="tutorial-subtitle"><?php the_field('sotto_titolo_esperto', $post_id) ?></div>
			<?php $field_object = get_field_object('tutorial_lista_macchinari_esperto', $post_id); ?>
			<ul class="list-machines">
				<?php foreach ($field_object['value'] as $key => $value): ?>
				 	<li><a href="<?php echo $value->guid; ?>"><?php echo $value->post_title; ?></a></li>
				<?php endforeach ?>
			</ul>
		</div>
	</div>
<?php endif ?>


<?php get_footer( 'shop' ); ?>
