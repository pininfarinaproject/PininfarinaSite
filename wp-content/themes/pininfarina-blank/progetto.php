<?php /* Template Name: Progetto */ ?>

<?php get_header(); ?>

<!--PROGETTO-->
<input class="numPagina" type="number" id="numPagina" value="1" />
<div class="container-project">
  <div class="d_progetto_mainTitle s_aboutUs_h1Style">
    <?php echo get_field('title_project'); ?>
  </div>

  <div class="project-image">
    <img src="<?php echo get_field('image_project')['url'] ?>" alt="studenti">
  </div>

  <div class="project-box-text">
    <p><?php echo get_field('text_project'); ?></p>
  </div>
</div>

<div class="workshop-activities-title"><?php echo get_field('activities_title'); ?></div>
<div class="container-workshop">
  <div class="workshop-box green1 col-9">
    <div class="workshop-title"><?php echo get_field('workshop_title_1'); ?></div>
    <p><?php echo get_field('workshop_text_1'); ?></p>
  </div>
  <img class="workshop-image col-3" src="<?php echo get_field('workshop_image_1')['url'] ?>" alt="studenti">
</div>
<div class="container-workshop">
  <div class="workshop-box green2 col-9">
    <div class="workshop-title"><?php echo get_field('workshop_title_2'); ?></div>
    <p><?php echo get_field('workshop_text_2'); ?></p>
  </div>
  <img class="workshop-image col-3" src="<?php echo get_field('workshop_image_2')['url'] ?>" alt="studenti">
</div>
<div class="container-workshop">
  <div class="workshop-box green3 col-9">
    <div class="workshop-title"><?php echo get_field('workshop_title_3'); ?></div>
    <p><?php echo get_field('workshop_text_3'); ?></p>
  </div>
  <img class="workshop-image col-3" src="<?php echo get_field('workshop_image_3')['url'] ?>" alt="studenti">
</div>

<?php get_footer(); ?>
