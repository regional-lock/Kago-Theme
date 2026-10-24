<?php
/**
 * Fallback template (blog posts).
 *
 * @package KagoFansub
 */

get_header();
?>
<main class="wrap genre-page">
	<h1><?php echo is_home() && ! is_front_page() ? esc_html( single_post_title( '', false ) ) : esc_html( get_bloginfo( 'name' ) ); ?></h1>
	<div class="row six">
		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				get_template_part( 'template-parts/card-grid' );
			}
		}
		?>
	</div>
	<?php if ( ! have_posts() ) : ?>
		<p><?php esc_html_e( 'Nothing found.', 'kagofansub' ); ?></p>
	<?php endif; ?>
	<?php kago_pagination(); ?>
</main>
<?php get_footer(); ?>
