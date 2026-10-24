<?php
/**
 * Static pages (About, DMCA, Contact...).
 *
 * @package KagoFansub
 */

get_header();
while ( have_posts() ) :
	the_post();
	?>
<main class="wrap genre-page">
	<div class="page-heading"><h1 id="static-page-title"><?php the_title(); ?></h1></div>
	<article class="content-card" id="static-page-content">
		<?php the_content(); ?>
	</article>
</main>
<?php endwhile; ?>
<?php get_footer(); ?>
