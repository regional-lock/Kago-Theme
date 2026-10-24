<?php
/**
 * Site footer + mobile bottom navigation.
 *
 * @package KagoFansub
 */

$kago_active = kago_bottom_nav_active();
$kago_items  = array(
	'home'   => array( home_url( '/' ), '⌂', __( 'Home', 'kagofansub' ) ),
	'anime'  => array( get_post_type_archive_link( 'anime' ), '▦', __( 'Anime', 'kagofansub' ) ),
	'movies' => array( get_post_type_archive_link( 'movie' ), '▣', __( 'Movie', 'kagofansub' ) ),
);
?>
<footer>
	<div class="copyright"><?php echo wp_kses_post( kago_copyright_text() ); ?></div>
</footer>

<nav class="mobile-bottom-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'kagofansub' ); ?>">
	<?php foreach ( $kago_items as $kago_key => $kago_item ) : ?>
		<a href="<?php echo esc_url( $kago_item[0] ); ?>" class="<?php echo $kago_key === $kago_active ? 'active' : ''; ?>" data-mobile-nav="<?php echo esc_attr( $kago_key ); ?>">
			<span class="nav-icon" aria-hidden="true"><?php echo esc_html( $kago_item[1] ); ?></span><span><?php echo esc_html( $kago_item[2] ); ?></span>
		</a>
	<?php endforeach; ?>
</nav>

<?php wp_footer(); ?>
</body>
</html>
