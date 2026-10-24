<?php
/**
 * Taxonomies.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Single-value taxonomies are edited with a dropdown. */
function kago_single_taxonomies() {
	return array( 'age_rating', 'anime_source', 'release_year', 'anime_type', 'anime_status', 'anime_season' );
}

function kago_register_taxonomies() {
	$defs = array(
		'genre'        => array( __( 'Genres', 'kagofansub' ), __( 'Genre', 'kagofansub' ), 'genre', false ),
		'studio'       => array( __( 'Studios', 'kagofansub' ), __( 'Studio', 'kagofansub' ), 'studio', false ),
		'age_rating'   => array( __( 'Ratings', 'kagofansub' ), __( 'Rating', 'kagofansub' ), 'rating', true ),
		'anime_source' => array( __( 'Sources', 'kagofansub' ), __( 'Source', 'kagofansub' ), 'source', true ),
		'release_year' => array( __( 'Years', 'kagofansub' ), __( 'Year', 'kagofansub' ), 'year', true ),
		'anime_season' => array( __( 'Seasons', 'kagofansub' ), __( 'Season', 'kagofansub' ), 'season', true ),
		'anime_type'   => array( __( 'Types', 'kagofansub' ), __( 'Type', 'kagofansub' ), 'type', true ),
		'anime_status' => array( __( 'Statuses', 'kagofansub' ), __( 'Status', 'kagofansub' ), 'status', true ),
	);
	foreach ( $defs as $tax => $d ) {
		$args = array(
			'labels'            => array(
				'name'          => $d[0],
				'singular_name' => $d[1],
				'menu_name'     => $d[0],
				'all_items'     => sprintf( __( 'All %s', 'kagofansub' ), $d[0] ),
				'edit_item'     => sprintf( __( 'Edit %s', 'kagofansub' ), $d[1] ),
				'add_new_item'  => sprintf( __( 'Add new %s', 'kagofansub' ), $d[1] ),
				'search_items'  => sprintf( __( 'Search %s', 'kagofansub' ), $d[0] ),
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_in_rest'      => true,
			'show_admin_column' => in_array( $tax, array( 'genre', 'studio', 'age_rating', 'release_year' ), true ),
			'rewrite'           => array( 'slug' => $d[2], 'with_front' => false ),
		);
		if ( $d[3] ) {
			$args['meta_box_cb'] = 'kago_single_term_metabox';
		}
		register_taxonomy( $tax, array( 'anime', 'movie' ), $args );
	}
}
add_action( 'init', 'kago_register_taxonomies', 5 );

/** Dropdown metabox for single-value taxonomies. */
function kago_single_term_metabox( $post, $box ) {
	$tax   = isset( $box['args']['taxonomy'] ) ? $box['args']['taxonomy'] : '';
	$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'orderby' => 'name' ) );
	$cur   = wp_get_object_terms( $post->ID, $tax, array( 'fields' => 'ids' ) );
	$cur   = ( ! is_wp_error( $cur ) && $cur ) ? (int) $cur[0] : 0;
	wp_nonce_field( 'kago_single_terms', 'kago_single_terms_nonce' );
	echo '<select name="kago_tax[' . esc_attr( $tax ) . ']" style="width:100%">';
	echo '<option value="">' . esc_html__( '— Select —', 'kagofansub' ) . '</option>';
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			printf( '<option value="%d"%s>%s</option>', (int) $term->term_id, selected( $cur, $term->term_id, false ), esc_html( $term->name ) );
		}
	}
	echo '</select>';
	if ( in_array( $tax, array( 'release_year', 'anime_season' ), true ) ) {
		echo '<p class="description">' . esc_html__( 'Left empty, this is filled automatically from the release date.', 'kagofansub' ) . '</p>';
	}
}

add_action(
	'save_post',
	function ( $post_id ) {
		if ( ! isset( $_POST['kago_single_terms_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kago_single_terms_nonce'] ) ), 'kago_single_terms' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$posted = isset( $_POST['kago_tax'] ) && is_array( $_POST['kago_tax'] ) ? wp_unslash( $_POST['kago_tax'] ) : array();
		foreach ( kago_single_taxonomies() as $tax ) {
			$term_id = isset( $posted[ $tax ] ) ? absint( $posted[ $tax ] ) : 0;
			wp_set_object_terms( $post_id, $term_id ? array( $term_id ) : array(), $tax );
		}
	},
	10
);

/** Seed default terms. */
function kago_seed_terms() {
	$sets = array(
		'genre'        => array( 'Action', 'Adventure', 'Comedy', 'Drama', 'Fantasy', 'Isekai', 'Mystery', 'Romance', 'Slice of Life', 'Sports', 'Supernatural' ),
		'anime_source' => array( 'Manga', 'Light Novel', 'Web Novel', 'Original', 'Game', 'Visual Novel', 'Novel', '4-koma Manga', 'Other' ),
		'anime_type'   => array( 'TV', 'Movie', 'OVA', 'ONA', 'Special' ),
		'anime_status' => array( 'Currently Airing', 'Finished Airing', 'Not yet aired' ),
	);
	foreach ( $sets as $tax => $names ) {
		foreach ( $names as $name ) {
			if ( ! term_exists( $name, $tax ) ) {
				wp_insert_term( $name, $tax );
			}
		}
	}
	$ratings = array(
		'G'     => 'G - All Ages',
		'PG'    => 'PG - Children',
		'PG-13' => 'PG-13 - Teens 13 or older',
		'R-17'  => 'R - 17+ (violence & profanity)',
		'R+'    => 'R+ - Mild Nudity',
		'Rx'    => 'Rx - Hentai',
	);
	foreach ( $ratings as $name => $desc ) {
		if ( ! term_exists( $name, 'age_rating' ) ) {
			wp_insert_term( $name, 'age_rating', array( 'description' => $desc ) );
		}
	}
	$year = (int) gmdate( 'Y' );
	foreach ( array( 'winter', 'spring', 'summer', 'fall' ) as $season ) {
		kago_ensure_season_term( $season, $year );
	}
}

function kago_ensure_season_term( $season, $year ) {
	$name = ucfirst( $season ) . ' ' . $year;
	$term = term_exists( $name, 'anime_season' );
	if ( ! $term ) {
		$term = wp_insert_term( $name, 'anime_season', array( 'slug' => $season . '-' . $year ) );
	}
	return is_wp_error( $term ) ? 0 : (int) ( is_array( $term ) ? $term['term_id'] : $term );
}

/** Season name from a Y-m-d date. */
function kago_season_from_date( $date ) {
	$m = (int) gmdate( 'n', strtotime( $date ) );
	if ( $m <= 3 ) {
		return 'winter';
	}
	if ( $m <= 6 ) {
		return 'spring';
	}
	if ( $m <= 9 ) {
		return 'summer';
	}
	return 'fall';
}
