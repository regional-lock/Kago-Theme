<?php
/**
 * SEO: description, canonical, Open Graph, JSON-LD.
 * Disabled automatically when a dedicated SEO plugin is active.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kago_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' );
}

function kago_seo_description() {
	$site = get_bloginfo( 'name' );
	if ( is_singular( array( 'anime', 'movie' ) ) ) {
		$id     = get_queried_object_id();
		$genres = implode( ', ', kago_genre_names( $id ) );
		$text   = get_the_title( $id ) . ( $genres ? ' — ' . $genres : '' ) . ' with Indonesian subtitles. ' . kago_synopsis( $id );
	} elseif ( is_singular( 'episode' ) ) {
		$anime_id = (int) kago_meta( get_queried_object_id(), 'anime_id', 0 );
		$text     = get_the_title() . ' — watch ' . ( $anime_id ? get_the_title( $anime_id ) : $site ) . ' with Indonesian subtitles.';
	} elseif ( is_singular() ) {
		$text = wp_strip_all_tags( get_the_excerpt() );
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		$text = ( $term && '' !== trim( $term->description ) ) ? $term->description : sprintf( 'Browse %s anime with Indonesian subtitles on %s.', $term ? $term->name : '', $site );
	} elseif ( is_search() ) {
		$text = sprintf( 'Search results for %s on %s.', get_search_query(), $site );
	} elseif ( is_post_type_archive( 'movie' ) ) {
		$text = sprintf( 'Browse the %s anime movie list.', $site );
	} elseif ( is_post_type_archive( 'anime' ) ) {
		$text = sprintf( 'Browse the %s anime list with Indonesian subtitles.', $site );
	} else {
		$text = get_bloginfo( 'description' ) ? $site . ' — ' . get_bloginfo( 'description' ) : $site;
	}
	return wp_html_excerpt( trim( preg_replace( '/\s+/', ' ', $text ) ), 160, '…' );
}

function kago_seo_image() {
	if ( is_singular( array( 'anime', 'movie' ) ) ) {
		return kago_poster_url( get_queried_object_id(), 'large' );
	}
	if ( is_singular( 'episode' ) ) {
		$anime_id = (int) kago_meta( get_queried_object_id(), 'anime_id', 0 );
		return $anime_id ? kago_poster_url( $anime_id, 'large' ) : '';
	}
	if ( is_front_page() ) {
		$ids = kago_weekly_anime_ids( 1 );
		return $ids ? kago_banner_url( $ids[0], 'large' ) : '';
	}
	return '';
}

function kago_seo_breadcrumbs() {
	$crumbs = array( array( __( 'Home', 'kagofansub' ), home_url( '/' ) ) );
	if ( is_singular( 'anime' ) ) {
		$crumbs[] = array( __( 'Anime', 'kagofansub' ), get_post_type_archive_link( 'anime' ) );
		$crumbs[] = array( get_the_title(), get_permalink() );
	} elseif ( is_singular( 'movie' ) ) {
		$crumbs[] = array( __( 'Movies', 'kagofansub' ), get_post_type_archive_link( 'movie' ) );
		$crumbs[] = array( get_the_title(), get_permalink() );
	} elseif ( is_singular( 'episode' ) ) {
		$anime_id = (int) kago_meta( get_queried_object_id(), 'anime_id', 0 );
		$crumbs[] = array( __( 'Anime', 'kagofansub' ), get_post_type_archive_link( 'anime' ) );
		if ( $anime_id ) {
			$crumbs[] = array( get_the_title( $anime_id ), get_permalink( $anime_id ) );
		}
		$crumbs[] = array( get_the_title(), get_permalink() );
	} elseif ( is_tax() ) {
		$crumbs[] = array( single_term_title( '', false ), get_term_link( get_queried_object() ) );
	} elseif ( is_post_type_archive() ) {
		$crumbs[] = array( post_type_archive_title( '', false ), get_post_type_archive_link( get_query_var( 'post_type' ) ) );
	} elseif ( is_singular() ) {
		$crumbs[] = array( get_the_title(), get_permalink() );
	}
	$items = array();
	foreach ( $crumbs as $i => $c ) {
		$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => wp_strip_all_tags( $c[0] ), 'item' => is_wp_error( $c[1] ) ? home_url( '/' ) : $c[1] );
	}
	return array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items );
}

function kago_seo_entity() {
	$id = get_queried_object_id();
	if ( is_singular( 'anime' ) ) {
		$data = array( '@context' => 'https://schema.org', '@type' => 'TVSeries', 'name' => get_the_title( $id ), 'description' => kago_synopsis( $id ), 'image' => kago_poster_url( $id, 'large' ), 'genre' => kago_genre_names( $id ), 'url' => get_permalink( $id ) );
		if ( kago_meta( $id, 'title_jp' ) ) {
			$data['alternateName'] = kago_meta( $id, 'title_jp' );
		}
		if ( kago_year( $id ) ) {
			$data['datePublished'] = kago_year( $id );
		}
		if ( kago_score( $id ) ) {
			$data['aggregateRating'] = array( '@type' => 'AggregateRating', 'ratingValue' => kago_score( $id ), 'bestRating' => 10, 'ratingCount' => max( 1, (int) kago_meta( $id, 'views', 1 ) ) );
		}
		return $data;
	}
	if ( is_singular( 'movie' ) ) {
		$data = array( '@context' => 'https://schema.org', '@type' => 'Movie', 'name' => get_the_title( $id ), 'description' => kago_synopsis( $id ), 'image' => kago_poster_url( $id, 'large' ), 'genre' => kago_genre_names( $id ), 'url' => get_permalink( $id ) );
		if ( kago_meta( $id, 'release_date' ) ) {
			$data['datePublished'] = kago_meta( $id, 'release_date' );
		}
		return $data;
	}
	if ( is_singular( 'episode' ) ) {
		$anime_id = (int) kago_meta( $id, 'anime_id', 0 );
		return array(
			'@context'      => 'https://schema.org',
			'@type'         => 'TVEpisode',
			'name'          => get_the_title( $id ),
			'episodeNumber' => (int) kago_meta( $id, 'ep_number', 0 ),
			'url'           => get_permalink( $id ),
			'image'         => $anime_id ? kago_poster_url( $anime_id, 'large' ) : '',
			'partOfSeries'  => array( '@type' => 'TVSeries', 'name' => $anime_id ? get_the_title( $anime_id ) : '' ),
		);
	}
	return null;
}

add_action(
	'wp_head',
	function () {
		if ( kago_seo_plugin_active() || is_404() ) {
			return;
		}
		$desc  = kago_seo_description();
		$title = wp_get_document_title();
		$image = kago_seo_image();
		$url   = is_singular() ? get_permalink() : ( is_front_page() ? home_url( '/' ) : '' );
		if ( ! $url ) {
			global $wp;
			$url = home_url( add_query_arg( array(), $wp->request ) );
		}
		echo "\n";
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
		if ( ! is_singular() ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
		}
		$og = array( 'og:title' => $title, 'og:description' => $desc, 'og:type' => is_singular() ? 'article' : 'website', 'og:url' => $url, 'og:site_name' => get_bloginfo( 'name' ), 'og:locale' => get_locale() );
		if ( $image ) {
			$og['og:image'] = $image;
		}
		foreach ( $og as $prop => $content ) {
			printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $prop ), esc_attr( $content ) );
		}
		printf( '<meta name="twitter:card" content="%s">' . "\n", $image ? 'summary_large_image' : 'summary' );
		if ( is_search() ) {
			echo '<meta name="robots" content="noindex,follow">' . "\n";
		}
		$graph = array_filter( array( kago_seo_entity(), is_front_page() ? null : kago_seo_breadcrumbs() ) );
		if ( $graph ) {
			echo '<script type="application/ld+json">' . wp_json_encode( array_values( $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	1
);

/* The theme prints its own canonical for non-singular pages; keep core's for singular. */
