<?php
/**
 * 404.
 *
 * @package KagoFansub
 */

get_header();
?>
<main class="error-content">
	<section class="error-card" aria-labelledby="error-title">
		<span class="error-kicker"><span aria-hidden="true">✦</span> <?php esc_html_e( 'PAGE NOT FOUND', 'kagofansub' ); ?></span>
		<div class="error-code" aria-hidden="true">404</div>
		<h1 id="error-title"><?php esc_html_e( 'Page not found', 'kagofansub' ); ?></h1>
		<p><?php esc_html_e( 'The page you’re looking for may have moved or is no longer available.', 'kagofansub' ); ?></p>
		<div class="error-actions">
			<a class="btn primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to Home', 'kagofansub' ); ?></a>
			<button class="btn" type="button" onclick="history.back()"><?php esc_html_e( 'Previous Page', 'kagofansub' ); ?></button>
		</div>
	</section>
</main>
<?php get_footer(); ?>
