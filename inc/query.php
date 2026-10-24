<?php
/**
 * Front-end queries: archives, taxonomy pages, search, random.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KAGO_PER_PAGE = 24;

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'letter';
		return $vars;
	}
);

add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( $query->is_post_type_archive( array( 'anime', 'movie' ) ) || $query->is_tax( 'anime_season' ) ) {
			$query->set( 'posts_per_page', KAGO_PER_PAGE );
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
			if ( $query->is_tax( 'anime_season' ) ) {
				$query->set( 'post_type', 'anime' );
			}
		} elseif ( $query->is_tax() ) {
			$query->set( 'post_type', array( 'anime', 'movie' ) );
			$query->set( 'posts_per_page', KAGO_PER_PAGE );
		} elseif ( $query->is_search() ) {
			$query->set( 'post_type', array( 'anime', 'movie' ) );
			$query->set( 'posts_per_page', KAGO_PER_PAGE );
		}
	}
);

/* A–Z filter for the alphabetical lists. */
add_filter(
	'posts_where',
	function ( $where, $query ) {
		global $wpdb;
		if ( is_admin() || ! $query->is_main_query() ) {
			return $where;
		}
		$letter = strtoupper( (string) $query->get( 'letter' ) );
		if ( 1 === preg_match( '/^[A-Z]$/', $letter ) ) {
			$where .= $wpdb->prepare( " AND UPPER(LEFT({$wpdb->posts}.post_title,1)) = %s", $letter );
		}
		return $where;
	},
	10,
	2
);

/* Search across title, Japanese title, aliases, genres and studios. */
add_filter(
	'posts_search',
	function ( $search, $query ) {
		global $wpdb;
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() || '' === trim( (string) $query->get( 's' ) ) ) {
			return $search;
		}
		$like = '%' . $wpdb->esc_like( trim( (string) $query->get( 's' ) ) ) . '%';
		$sql  = "SELECT p.ID FROM {$wpdb->posts} p
			WHERE p.post_status = 'publish' AND p.post_password = '' AND p.post_type IN ('anime','movie')
			AND ( p.post_title LIKE %s
				OR p.ID IN ( SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_key IN ('_kago_title_jp','_kago_aliases') AND pm.meta_value LIKE %s )
				OR p.ID IN ( SELECT tr.object_id FROM {$wpdb->term_relationships} tr
					INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
					INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
					WHERE tt.taxonomy IN ('genre','studio') AND t.name LIKE %s ) )";
		$ids  = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( $sql, $like, $like, $like ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		return " AND {$wpdb->posts}.ID IN (" . ( $ids ? implode( ',', $ids ) : '0' ) . ') ';
	},
	10,
	2
);

/* "Random anime" button: /?kago_random=1 */
add_action(
	'template_redirect',
	function () {
		if ( ! isset( $_GET['kago_random'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$ids = get_posts(
			array(
				'post_type'      => 'anime',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'rand',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		wp_safe_redirect( $ids ? get_permalink( $ids[0] ) : home_url( '/' ) );
		exit;
	},
	1
);
