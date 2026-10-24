<?php
/**
 * Custom post types: anime, movie, episode.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kago_register_post_types() {
	$common = array(
		'public'       => true,
		'show_in_rest' => true,
		'menu_position' => 5,
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments' ),
	);

	register_post_type(
		'anime',
		array_merge(
			$common,
			array(
				'labels'      => kago_pt_labels( __( 'Anime', 'kagofansub' ), __( 'Anime', 'kagofansub' ) ),
				'menu_icon'   => 'dashicons-format-video',
				'has_archive' => 'anime',
				'rewrite'     => array( 'slug' => 'anime', 'with_front' => false ),
			)
		)
	);
	register_post_type(
		'movie',
		array_merge(
			$common,
			array(
				'labels'       => kago_pt_labels( __( 'Movie', 'kagofansub' ), __( 'Movies', 'kagofansub' ) ),
				'menu_icon'    => 'dashicons-video-alt2',
				'menu_position' => 6,
				'has_archive'  => 'movie',
				'rewrite'      => array( 'slug' => 'movie', 'with_front' => false ),
			)
		)
	);
	register_post_type(
		'episode',
		array_merge(
			$common,
			array(
				'labels'        => kago_pt_labels( __( 'Episode', 'kagofansub' ), __( 'Episodes', 'kagofansub' ) ),
				'menu_icon'     => 'dashicons-playlist-video',
				'menu_position' => 7,
				'has_archive'   => false,
				'rewrite'       => array( 'slug' => 'episode', 'with_front' => false ),
				'supports'      => array( 'title', 'editor', 'comments' ),
			)
		)
	);
}
add_action( 'init', 'kago_register_post_types' );

function kago_pt_labels( $singular, $plural ) {
	return array(
		'name'               => $plural,
		'singular_name'      => $singular,
		'add_new_item'       => sprintf( __( 'Add new %s', 'kagofansub' ), $singular ),
		'edit_item'          => sprintf( __( 'Edit %s', 'kagofansub' ), $singular ),
		'new_item'           => sprintf( __( 'New %s', 'kagofansub' ), $singular ),
		'view_item'          => sprintf( __( 'View %s', 'kagofansub' ), $singular ),
		'search_items'       => sprintf( __( 'Search %s', 'kagofansub' ), $plural ),
		'not_found'          => sprintf( __( 'No %s found', 'kagofansub' ), strtolower( $plural ) ),
		'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'kagofansub' ), strtolower( $plural ) ),
		'all_items'          => sprintf( __( 'All %s', 'kagofansub' ), $plural ),
		'menu_name'          => $plural,
	);
}
