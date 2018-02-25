<?php /* Template Name: Homepage */ ?>

<?php get_header(); ?>

<!--HOMEPAGE-->
<input class="numPagina" type="number" id="numPagina" value="0" />
<!-- Slideshow container -->
<div class="slideshow-container">

  <?php
    $args = array(
      'post_type'              => array( 'slideshow' ),
      'nopaging'               => true,
      'posts_per_page'         => '-1'
    );
    $slides = get_posts( $args );
    $total_slides = count($slides);
  ?>
  <?php for ($i = 0; $i < $total_slides; $i++): $slide = $slides[$i]; ?>
    <div class="mySlides fade">
      <div class="numbertext"><?php printf("%s/%s", $i + 1, $total_slides); ?></div>
      <?php echo get_the_post_thumbnail($slide); ?>
      <div class="info-title">
        <h3><?php echo $slide->post_title; ?></h3>
      </div>
      <div class="slideshow-description">
        <p><?php echo $slide->post_content; ?></p>
      </div>
    </div>
  <?php endfor; ?>

  <!-- Next and previous buttons -->
  <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
  <a class="next" onclick="plusSlides(1)">&#10095;</a>
</div>
<br>

<!-- The dots/circles -->
<div style="text-align:center">
  <?php for ($i = 0; $i < $total_slides; $i++): $slide = $slides[$i]; ?>
    <span class="dot" onclick="<?php printf('currentSlide(%s)', $i + 1);?>"></span>
  <?php endfor; ?>
</div>

  <!-- <div class="slideshow">
    <img src="<?php // echo get_field('hero_background_image')['url'] ?>" alt="studenti">
    <div class="info-title">
      <h3><?php //echo get_field('hero_title'); ?></h3>
    </div>
    <div class="slideshow-description">
      <p><?php // echo get_field('hero_text'); ?></p>
    </div>
  </div> -->

  <div class="container-info">
    <div class="info-text">
      <div class="title"><?php echo get_field('info_title'); ?></div>
      <p><?php echo get_field('info_text'); ?></p>
    </div>
    <div class="info-image">
      <img src="<?php  echo get_field('info_image')['url']; ?>" alt="Ragazzo">
    </div>
    <div class="row">
      <div class="info-card iscrivitiColor">
        <div class="title colored"><?php  echo get_field('box_title_1'); ?></div>
        <p><?php  echo get_field('box_text_1'); ?></p>
      </div>
      <div class="info-card prenotaColor">
        <div class="title colored"><?php  echo get_field('box_title_2'); ?></div>
        <p><?php  echo get_field('box_text_2'); ?></p>
      </div>
      <div class="info-card creaColor">
        <div class="title colored"><?php  echo get_field('box_title_3'); ?></div>
        <p><?php  echo get_field('box_text_3'); ?></p>
      </div>
    </div>
    <div class="row">
      <div class="info-image-left">
        <img src="<?php  echo get_field('lab_image')['url']; ?>" alt="laboratorio"/>
        <div class="info-title-left">
          <?php  echo get_field('lab_image_title'); ?>
        </div>
      </div>
      <div class="info-right">
        <div class="title-right">
          <div class="title colored margin-title-right"><?php  echo get_field('box_title_right'); ?></div>
          <p class="paragraph-right">
            <?php  echo get_field('box_paragraph1_right'); ?>
          </p>
          <p class="paragraph-right">
            <?php  echo get_field('box_paragraph2_right'); ?>
          </p>
          <!-- Come si aggiunge un link su wordpress? -->

          </div>

          <div class="button">
            <div class="buttonBlack"><a href="<?php echo site_url(); ?>/categoria-prodotto/laboratori/">prenota</a></div>
            <div class="triangle"></div>
          </div>

        </div>
      </div>
      <div class="row">
        <div class="container-box">
          <div class="box-title"><?php  echo get_field('box_title_4'); ?></div>
          <div class="info-card2 iscrivitiColor">
            <p><?php  echo get_field('box_text_4'); ?></p>
          </div>
          <div class="button">
            <div class="buttonBlack"><a href="<?php echo site_url(); ?>/categoria-prodotto/laboratori/meccanica/">Maggiori informazioni</a></div>
            <div class="triangle"></div>
          </div>
        </div>
        <div class="container-box">
          <div class="box-title"><?php  echo get_field('box_title_5'); ?></div>
          <div class="info-card2 prenotaColor">
            <p><?php  echo get_field('box_text_5'); ?></p>
          </div>
          <div class="button">
            <div class="buttonBlack"><a href="<?php echo site_url(); ?>/categoria-prodotto/laboratori/informatica/">Maggiori informazioni</a></div>
            <div class="triangle"></div>
          </div>
        </div>
        <div class="container-box">
          <div class="box-title"><?php  echo get_field('box_title_6'); ?></div>
          <div class="info-card2 creaColor">
            <p><?php  echo get_field('box_text_6'); ?></p>
          </div>
          <div class="button">
            <div class="buttonBlack"><a href="<?php echo site_url(); ?>/categoria-prodotto/laboratori/elettronica/">Maggiori informazioni</a></div>
            <div class="triangle"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php get_footer(); ?>