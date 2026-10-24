<?php
/**
 * Template helpers.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Meta access ---------- */

function kago_meta( $post_id, $key, $default = '' ) {
	$value = get_post_meta( $post_id, '_kago_' . $key, true );
	return ( '' === $value || null === $value || false === $value ) ? $default : $value;
}

function kago_score( $post_id ) {
	$score = kago_meta( $post_id, 'score', '' );
	return is_numeric( $score ) ? number_format( (float) $score, 2 ) : '';
}

function kago_synopsis( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return '';
	}
	$text = '' !== trim( $post->post_content ) ? $post->post_content : $post->post_excerpt;
	return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( $text ) ) ) );
}

/* ---------- Images ---------- */

function kago_image_from_meta( $post_id, $prefix, $size ) {
	$id = (int) kago_meta( $post_id, $prefix . '_id', 0 );
	if ( $id ) {
		$url = wp_get_attachment_image_url( $id, $size );
		if ( $url ) {
			return $url;
		}
	}
	return '';
}

function kago_poster_url( $post_id = 0, $size = 'kago-poster' ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$url     = kago_image_from_meta( $post_id, 'poster', $size );
	if ( ! $url && has_post_thumbnail( $post_id ) ) {
		$url = get_the_post_thumbnail_url( $post_id, $size );
	}
	if ( ! $url ) {
		$url = kago_meta( $post_id, 'poster_url', '' );
	}
	if ( ! $url ) {
		$url = KAGO_URI . '/assets/images/poster-placeholder.webp';
	}
	return $url;
}

function kago_banner_url( $post_id = 0, $size = 'kago-banner' ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$url     = kago_image_from_meta( $post_id, 'banner', $size );
	if ( ! $url ) {
		$url = kago_meta( $post_id, 'banner_url', '' );
	}
	return $url ? $url : kago_poster_url( $post_id, 'large' );
}

function kago_poster_style( $post_id = 0, $size = 'kago-poster' ) {
	return "background:url('" . esc_url( kago_poster_url( $post_id, $size ) ) . "') center/cover";
}

/* ---------- Terms ---------- */

function kago_term_name( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
}

/** Term label; uses the description when present (e.g. full age rating text). */
function kago_term_label( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	return '' !== trim( $terms[0]->description ) ? $terms[0]->description : $terms[0]->name;
}

function kago_term_names( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	return ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'name' ) : array();
}

function kago_genre_names( $post_id ) {
	$terms = get_the_terms( $post_id, 'genre' );
	return ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'name' ) : array();
}

/** Type pill text: TV / Movie / OVA ... */
function kago_type_label( $post_id ) {
	if ( 'movie' === get_post_type( $post_id ) ) {
		return 'Movie';
	}
	$type = kago_term_name( $post_id, 'anime_type' );
	return $type ? $type : 'TV';
}

/** Genre links as in the static cards. */
function kago_genre_links( $post_id, $limit = 2 ) {
	$terms = get_the_terms( $post_id, 'genre' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	$html = '';
	foreach ( array_slice( $terms, 0, $limit ) as $term ) {
		$html .= '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
	}
	return $html;
}

function kago_year( $post_id ) {
	$year = kago_term_name( $post_id, 'release_year' );
	if ( $year ) {
		return $year;
	}
	$date = kago_meta( $post_id, 'release_date', '' );
	return $date ? gmdate( 'Y', strtotime( $date ) ) : '';
}

function kago_release_date_label( $post_id ) {
	$date = kago_meta( $post_id, 'release_date', '' );
	return $date ? gmdate( 'M, d Y', strtotime( $date ) ) : '';
}

/* ---------- Episodes ---------- */

function kago_ep_label( $number ) {
	return 'Episode ' . str_pad( (string) (int) $number, 2, '0', STR_PAD_LEFT );
}

function kago_get_episodes( $anime_id, $args = array() ) {
	$defaults = array(
		'post_type'      => 'episode',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => '_kago_ep_number',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'no_found_rows'  => true,
		'meta_query'     => array(
			array(
				'key'   => '_kago_anime_id',
				'value' => (int) $anime_id,
				'type'  => 'NUMERIC',
			),
		),
	);
	return get_posts( array_merge( $defaults, $args ) );
}

function kago_latest_episode( $anime_id ) {
	$list = kago_get_episodes( $anime_id, array( 'posts_per_page' => 1 ) );
	return $list ? $list[0] : null;
}

function kago_adjacent_episode( $episode_id, $direction ) {
	$anime_id = (int) kago_meta( $episode_id, 'anime_id', 0 );
	$number   = (int) kago_meta( $episode_id, 'ep_number', 0 );
	if ( ! $anime_id ) {
		return null;
	}
	$list = kago_get_episodes(
		$anime_id,
		array(
			'posts_per_page' => 1,
			'order'          => $direction > 0 ? 'ASC' : 'DESC',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'   => '_kago_anime_id',
					'value' => $anime_id,
					'type'  => 'NUMERIC',
				),
				array(
					'key'     => '_kago_ep_number',
					'value'   => $number,
					'type'    => 'NUMERIC',
					'compare' => $direction > 0 ? '>' : '<',
				),
			),
		)
	);
	return $list ? $list[0] : null;
}

/* ---------- Downloads ---------- */

function kago_download_qualities() {
	return apply_filters( 'kago_download_qualities', array( '1080p', '720p', '480p' ) );
}

function kago_download_qualities_for_post( $post_id ) {
	if ( metadata_exists( 'post', $post_id, '_kago_download_qualities' ) ) {
		$qualities = get_post_meta( $post_id, '_kago_download_qualities', true );
		return is_array( $qualities ) ? $qualities : array();
	}
	return metadata_exists( 'post', $post_id, '_kago_downloads' ) || metadata_exists( 'post', $post_id, '_kago_download_hosts' ) ? kago_download_qualities() : array();
}

function kago_download_hosts() {
	return apply_filters( 'kago_download_hosts', array( 'Acefile', 'KrakenFiles', 'GoFile' ) );
}

function kago_download_hosts_for_post( $post_id ) {
	if ( metadata_exists( 'post', $post_id, '_kago_download_hosts' ) ) {
		$hosts = get_post_meta( $post_id, '_kago_download_hosts', true );
		return is_array( $hosts ) ? $hosts : array();
	}
	return metadata_exists( 'post', $post_id, '_kago_downloads' ) ? kago_download_hosts() : array();
}

function kago_download_hosts_for_quality( $post_id, $quality ) {
	$data = kago_downloads( $post_id );
	if ( metadata_exists( 'post', $post_id, '_kago_download_groups_v2' ) ) {
		return isset( $data[ $quality ] ) && is_array( $data[ $quality ] ) ? array_keys( $data[ $quality ] ) : array();
	}
	return kago_download_hosts_for_post( $post_id );
}

function kago_downloads( $post_id ) {
	$data = get_post_meta( $post_id, '_kago_downloads', true );
	return is_array( $data ) ? $data : array();
}

function kago_render_download_rows( $post_id ) {
	$data = kago_downloads( $post_id );
	echo '<div class="download-options">';
	foreach ( kago_download_qualities_for_post( $post_id ) as $quality ) {
		echo '<div class="download-row"><span class="download-quality">' . esc_html( $quality ) . '</span>';
		foreach ( kago_download_hosts_for_quality( $post_id, $quality ) as $host ) {
			$url = isset( $data[ $quality ][ $host ] ) ? esc_url( $data[ $quality ][ $host ] ) : '';
			printf(
				'<button type="button"%s data-download-url="%s">%s</button>',
				$url ? '' : ' disabled',
				esc_attr( $url ),
				esc_html( $host )
			);
		}
		echo '</div>';
	}
	echo '</div>';
}

/* ---------- Navigation state ---------- */

function kago_bottom_nav_active() {
	if ( is_404() ) {
		return '';
	}
	if ( is_front_page() ) {
		return 'home';
	}
	if ( is_post_type_archive( 'movie' ) || is_singular( 'movie' ) ) {
		return 'movies';
	}
	return 'anime';
}

/* ---------- Pagination (matches the static .pagination markup) ---------- */

function kago_pagination( $query = null, $id = '' ) {
	global $wp_query;
	$query = $query ? $query : $wp_query;
	$total = (int) $query->max_num_pages;
	if ( $total <= 1 ) {
		return;
	}
	$current = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	$out     = '<nav class="pagination"' . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . ' aria-label="' . esc_attr__( 'Pagination', 'kagofansub' ) . '">';
	$last    = 0;
	for ( $i = 1; $i <= $total; $i++ ) {
		if ( 1 !== $i && $total !== $i && abs( $i - $current ) > 2 ) {
			continue;
		}
		if ( $last && $i - $last > 1 ) {
			$out .= '<span class="pagination-gap" aria-hidden="true">…</span>';
		}
		$last = $i;
		$out .= sprintf(
			'<a class="%s" href="%s"%s>%d</a>',
			$i === $current ? 'current' : '',
			esc_url( get_pagenum_link( $i ) ),
			$i === $current ? ' aria-current="page"' : '',
			$i
		);
	}
	echo $out . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/* ---------- Letters used by the alphabetical list ---------- */

function kago_available_letters( $post_types, $term = null ) {
	global $wpdb;
	$post_types = (array) $post_types;
	$ph         = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
	$sql        = "SELECT DISTINCT UPPER(LEFT(p.post_title,1)) FROM {$wpdb->posts} p";
	$params     = array();
	if ( $term instanceof WP_Term ) {
		$sql   .= " INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.term_id = %d";
		$params[] = $term->term_id;
	}
	$sql   .= " WHERE p.post_status = 'publish' AND p.post_type IN ($ph)";
	$params = array_merge( $params, $post_types );
	$rows   = $wpdb->get_col( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	return array_filter( (array) $rows, function ( $l ) {
		return 1 === preg_match( '/^[A-Z]$/', $l );
	} );
}

/* ---------- Brand / footer ---------- */

function kago_brand_html() {
	if ( has_custom_logo() ) {
		return '<a class="brand" href="' . esc_url( home_url( '/' ) ) . '">' . wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'full', false, array( 'alt' => get_bloginfo( 'name' ) ) ) . '</a>';
	}
	$main   = get_theme_mod( 'kago_brand_main', 'KAGO' );
	$accent = get_theme_mod( 'kago_brand_accent', 'FANSUB' );
	return '<a class="brand" href="' . esc_url( home_url( '/' ) ) . '"><strong>' . esc_html( $main ) . '<span>' . esc_html( $accent ) . '</span></strong></a>';
}

function kago_copyright_text() {
	$default = '© {year} Kago Fansub. All rights reserved.';
	$text    = get_theme_mod( 'kago_copyright', $default );
	return str_replace( '{year}', gmdate( 'Y' ), $text );
}

function kago_comments_enabled() {
	return (bool) get_theme_mod( 'kago_comments', false );
}
