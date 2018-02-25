<?php /* Template Name: About us */ ?>

<?php get_header(); ?>

<!--ABOUT US-->
<input type="number" id="numPagina" value="3" />
<div class="s_aboutUs_mainTitle s_aboutUs_h1Style"><?php echo get_field('about_us_title'); ?></div>
<div class="s_aboutUs_aboutUsDiv">
    <div class="s_aboutUs_titleAndText">
        <div class="s_aboutUs_contentText">
            <?php echo get_field('about_us_text'); ?>
        </div>
    </div>
    <div class="s_aboutUs_image">
        <img src="https://www.hbfuller.com/-/media/images/connecting-what-matters/main-image_istock-848x493.ashx?h=493&la=en&w=848&hash=F021DAAA3058872704F9FCE586B56587FD0F47C9" alt="AboutUs Img" class="s_simpleImg">
    </div>
</div>


<h1 class="s_aboutUs_h1Style s_aboutUs_governanceTitle"><?php echo get_field('title_governance'); ?></h1>
<div class="s_aboutUs_contentText">
    <?php echo get_field('text_governance'); ?>
</div>
<div class="carousel-wrap">
  <div class="owl-carousel">
    <div class="item"><img src="http://placehold.it/150x150/fff"></div>
    <div class="item"><img src="http://placehold.it/150x150/fff"></div>
    <div class="item"><img src="http://placehold.it/150x150/fff"></div>
    <div class="item"><img src="http://placehold.it/150x150/fff"></div>
    <div class="item"><img src="http://placehold.it/150x150/fff"></div>
    <div class="item"><img src="http://placehold.it/150x150/fff"></div>
    <div class="item"><img src="http://placehold.it/150x150/fff"></div>
    <div class="item"><img src="http://placehold.it/150x150/fff"></div>
    <div class="item"><img src="http://placehold.it/150x150/fff"></div>
    <div class="item"><img src="http://placehold.it/150x150/fff"></div>
  </div>
</div>

<h1 class="s_aboutUs_h1Style s_aboutUs_contactsTitle"> <?php echo get_field('contact_title'); ?></h1>
<div class="s_aboutUs_singleContact">Nome Cognome - Tell : <a href="callto:1234567" class="s_aboutUs_contactsColor">0294827194</a> - <a href="mailto:mailprova@gmail.com" class="s_aboutUs_contactsColor">mailprova@gmail.com</a></div>
<div class="s_aboutUs_singleContact">Nome Cognome - Numero di telefono - email</div>



<?php get_footer(); ?>
