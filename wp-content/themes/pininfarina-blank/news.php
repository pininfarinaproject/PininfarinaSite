<?php /* Template Name: News */ ?>

<?php get_header(); ?>

<!--NEWS-->
<input type="number" id="numPagina" value="4" />

<div class="s_news_mainTitle s_aboutUs_h1Style">News</div>
<div class="s_news_containerNews">
  <?php
   $args = array( 'category_name' => 'news', 'post_type' =>  'post' );
   $postslist = get_posts( $args );
   /*echo "<pre>";
    print_r($postslist);
   echo "</pre>";*/
   foreach ($postslist as $post) :  setup_postdata($post);
   ?>
   <div class="s_news_singleArticle">
       <div class="s_news_titleArticle"><?php echo get_field('news_title'); ?></div>
       <div class="s_news_subtitleArticle"><?php echo get_field('news_subtitle'); ?></div>
       <img src="<?php  echo get_field('news_image')['url']; ?>" alt="Article Img" class="s_simpleImg">
       <div class="s_news_articleIntroduction"><?php echo get_field('news_article_introduction'); ?></div>
   </div>
 <?php endforeach; ?>
</div>
    <!-- <div class="s_news_singleArticle">
        <div class="s_news_titleArticle">Titolo Articolo</div>
        <div class="s_news_subtitleArticle">sottotitolo articolo</div>
        <img src="https://www.seoclerk.com/pics/518476-1iDFBR1489198900.png" alt="Article Img" class="s_simpleImg">
        <div class="s_news_articleIntroduction">Introduzione all'articolo</div>
    </div>
    <div class="s_news_singleArticle">
        <div class="s_news_titleArticle">Titolo Articolo</div>
        <div class="s_news_subtitleArticle">sottotitolo articolo</div>
        <img src="https://www.seoclerk.com/pics/518476-1iDFBR1489198900.png" alt="Article Img" class="s_simpleImg">
        <div class="s_news_articleIntroduction">Introduzione all'articolo</div>
    </div>
    <div class="s_news_singleArticle">
        <div class="s_news_titleArticle">Titolo Articolo</div>
        <div class="s_news_subtitleArticle">sottotitolo articolo</div>
        <img src="https://www.seoclerk.com/pics/518476-1iDFBR1489198900.png" alt="Article Img" class="s_simpleImg">
        <div class="s_news_articleIntroduction">Introduzione all'articolo</div>
    </div>
    <div class="s_news_singleArticle">
        <div class="s_news_titleArticle">Titolo Articolo</div>
        <div class="s_news_subtitleArticle">sottotitolo articolo</div>
        <img src="https://www.seoclerk.com/pics/518476-1iDFBR1489198900.png" alt="Article Img" class="s_simpleImg">
        <div class="s_news_articleIntroduction">Introduzione all'articolo</div>
    </div>-->


<h1 class="s_news_h1Style s_news_pressReview">Rassegna Stampa</h1>
  <?php
   $args = array( 'category_name' => 'rassegna_stampa', 'post_type' =>  'post' );
   $postslist = get_posts( $args );
   /*echo "<pre>";
    print_r($postslist);
   echo "</pre>";*/
   foreach ($postslist as $post) :  setup_postdata($post);
   ?>
   <div class="s_news_singlePressReview">
       <div class="s_news_datePressReview"><?php echo get_field('data_stampa'); ?></div>
       <div class="s_news_contentPressReviwe">
           <div class="s_news_imagePressReviwe">
               <img src="<?php  echo get_field('img_stampa')['url']; ?>" alt="Content Press Reviwe" class="s_pressReviewImg">
           </div>
           <div class="s_news_titleAndBodyPressReview">
               <div class="s_news_titlePressReview"><?php echo get_field('title_stampa'); ?></div>
               <div class="s_news_bodyPressReview"><?php echo get_field('introduzione_articolo'); ?></div>
           </div>
       </div>
       <div class="s_news_sourcePressReview"><?php echo get_field('fonte_articolo'); ?></div>
   </div>
  <?php endforeach; ?>


<!--<div class="s_news_singlePressReview">
    <div class="s_news_datePressReview">gg/mm/aaa</div>
    <div class="s_news_contentPressReviwe">
        <div class="s_news_imagePressReviwe">
            <img src="https://www.seoclerk.com/pics/518476-1iDFBR1489198900.png" alt="Content Press Reviwe" class="s_pressReviewImg">
        </div>
        <div class="s_news_titleAndBodyPressReview">
            <div class="s_news_titlePressReview">Titolo</div>
            <div class="s_news_bodyPressReview">Body Press Review</div>
        </div>
    </div>
    <div class="s_news_sourcePressReview">Source</div>
</div>-->


<?php get_footer(); ?>
