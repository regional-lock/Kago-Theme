<?php
/**
 * Site header.
 *
 * @package KagoFansub
 */
?>
<!doctype html>
<html <?php language_attributes(); ?> data-theme="dark">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="topbar">
	<?php echo kago_brand_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

	<button class="menu-toggle" type="button" data-mobile-menu aria-label="<?php esc_attr_e( 'Open menu', 'kagofansub' ); ?>" aria-expanded="false">☰</button>

	<?php
	wp_nav_menu(
		array(
			'theme_location' => 'primary',
			'container'      => 'nav',
			'container_class' => 'nav',
			'items_wrap'     => '%3$s',
			'depth'          => 1,
			'walker'         => new Kago_Nav_Walker(),
			'fallback_cb'    => 'kago_nav_fallback',
		)
	);
	?>

	<label class="search">
		<span>⌕</span>
		<input type="search" placeholder="<?php esc_attr_e( 'Search anime...', 'kagofansub' ); ?>" aria-label="<?php esc_attr_e( 'Search anime', 'kagofansub' ); ?>" value="<?php echo is_search() ? esc_attr( get_search_query() ) : ''; ?>" data-global-search>
	</label>
</header>
