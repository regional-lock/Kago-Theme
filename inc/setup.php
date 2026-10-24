<?php
/**
 * Theme setup, menus, activation.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	function () {
		load_theme_textdomain( 'kagofansub', KAGO_DIR . '/languages' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'html5', array( 'search-form', 'comment-list', 'comment-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 40,
				'width'       => 180,
				'flex-width'  => true,
				'flex-height' => true,
			)
		);
		register_nav_menus( array( 'primary' => __( 'Primary menu', 'kagofansub' ) ) );
		add_image_size( 'kago-poster', 300, 420, true );
		add_image_size( 'kago-banner', 1280, 400, true );
		add_image_size( 'kago-thumb', 80, 110, true );
	}
);

add_action(
	'widgets_init',
	function () {
		register_sidebar(
			array(
				'name'          => __( 'Sidebar (extra)', 'kagofansub' ),
				'id'            => 'sidebar-extra',
				'description'   => __( 'Shown below the genre and season boxes.', 'kagofansub' ),
				'before_widget' => '<section class="box genre-box">',
				'after_widget'  => '</section>',
				'before_title'  => '<div class="sh" style="margin-bottom:12px"><h2>',
				'after_title'   => '</h2></div>',
			)
		);
	}
);

/* Keep body classes from colliding with design selectors such as .search. */
add_filter(
	'body_class',
	function ( $classes ) {
		$keep = array_intersect( $classes, array( 'admin-bar', 'logged-in' ) );
		if ( is_404() ) {
			$keep[] = 'error-page';
		}
		return array_values( $keep );
	}
);

/* Clean <head>. */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );

add_filter( 'document_title_separator', function () {
	return '|';
} );

/* Primary navigation walker that reproduces the static markup. */
class Kago_Nav_Walker extends Walker_Nav_Menu {
	public function start_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_el( &$output, $item, $depth = 0, $args = null ) {}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes = (array) $item->classes;
		$icon    = '▦';
		$is_home = false;
		if ( in_array( 'icon-home', $classes, true ) ) {
			$icon    = '⌂';
			$is_home = true;
		} elseif ( in_array( 'icon-movie', $classes, true ) ) {
			$icon = '▣';
		}
		$current = ! $is_home && ( in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-ancestor', $classes, true ) || in_array( 'current-menu-parent', $classes, true ) );
		$output .= sprintf(
			'<a%s href="%s"><span class="nav-icon">%s</span><span>%s</span></a>',
			$current ? ' class="on"' : '',
			esc_url( $item->url ),
			esc_html( $icon ),
			esc_html( $item->title )
		);
	}
}

function kago_nav_fallback() {
	$items = array(
		array( home_url( '/' ), '⌂', __( 'Home', 'kagofansub' ), false ),
		array( get_post_type_archive_link( 'anime' ), '▦', __( 'Anime List', 'kagofansub' ), is_post_type_archive( 'anime' ) ),
		array( get_post_type_archive_link( 'movie' ), '▣', __( 'Movie List', 'kagofansub' ), is_post_type_archive( 'movie' ) ),
	);
	echo '<nav class="nav">';
	foreach ( $items as $item ) {
		printf( '<a%s href="%s"><span class="nav-icon">%s</span><span>%s</span></a>', $item[3] ? ' class="on"' : '', esc_url( $item[0] ), esc_html( $item[1] ), esc_html( $item[2] ) );
	}
	echo '</nav>';
}

/* Activation: terms, pages, menu, tagline, rewrite rules. */
function kago_activate() {
	kago_register_post_types();
	kago_register_taxonomies();
	kago_seed_terms();
	kago_seed_pages_and_menu();
	if ( get_option( 'blogdescription' ) === 'Just another WordPress site' ) {
		update_option( 'blogdescription', 'Anime with Indonesian Subtitles' );
	}
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'kago_activate' );

function kago_seed_pages_and_menu() {
	$pages = array(
		'about'   => array( 'About', '<p>Kago Fansub is a fan project sharing anime information and Indonesian subtitle releases.</p>' ),
		'dmca'    => array( 'DMCA', '<p>For copyright concerns or removal requests, contact the site administrator with the page URL and supporting details.</p>' ),
		'contact' => array( 'Contact', '<p>For questions or corrections, contact the Kago Fansub team.</p>' ),
	);
	foreach ( $pages as $slug => $page ) {
		if ( ! get_page_by_path( $slug ) ) {
			wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $page[0],
					'post_name'    => $slug,
					'post_content' => $page[1],
				)
			);
		}
	}

	$locations = get_theme_mod( 'nav_menu_locations' );
	if ( ! empty( $locations['primary'] ) ) {
		return;
	}
	$menu = wp_get_nav_menu_object( 'Primary' );
	$menu_id = $menu ? $menu->term_id : wp_create_nav_menu( 'Primary' );
	if ( is_wp_error( $menu_id ) ) {
		return;
	}
	if ( ! wp_get_nav_menu_items( $menu_id ) ) {
		$entries = array(
			array( __( 'Home', 'kagofansub' ), home_url( '/' ), 'icon-home' ),
			array( __( 'Anime List', 'kagofansub' ), get_post_type_archive_link( 'anime' ), 'icon-anime' ),
			array( __( 'Movie List', 'kagofansub' ), get_post_type_archive_link( 'movie' ), 'icon-movie' ),
		);
		foreach ( $entries as $entry ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'   => $entry[0],
					'menu-item-url'     => $entry[1],
					'menu-item-type'    => 'custom',
					'menu-item-status'  => 'publish',
					'menu-item-classes' => $entry[2],
				)
			);
		}
	}
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}
