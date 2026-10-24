<?php
/**
 * All-time and rolling seven-day view counters for popularity rankings.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kago_is_bot() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
	return '' === $ua || 1 === preg_match( '/bot|crawl|spider|slurp|facebookexternalhit|preview|curl|wget/', $ua );
}

function kago_bump_views( $post_id ) {
	$views = (int) kago_meta( $post_id, 'views', 0 ) + 1;
	update_post_meta( $post_id, '_kago_views', $views );

	$now        = time();
	$hour       = (int) floor( $now / HOUR_IN_SECONDS );
	$cutoff     = (int) floor( ( $now - WEEK_IN_SECONDS ) / HOUR_IN_SECONDS );
	$view_hours = get_post_meta( $post_id, '_kago_view_hours', true );
	$view_hours = is_array( $view_hours ) ? $view_hours : array();
	foreach ( $view_hours as $stored_hour => $count ) {
		if ( (int) $stored_hour < $cutoff ) {
			unset( $view_hours[ $stored_hour ] );
		}
	}
	$view_hours[ $hour ] = (int) ( $view_hours[ $hour ] ?? 0 ) + 1;
	update_post_meta( $post_id, '_kago_view_hours', $view_hours );
	delete_transient( 'kago_weekly_featured_anime_ids' );
}

/** Return anime IDs ranked by views received during the last seven days. */
function kago_weekly_anime_ids( $limit = 5 ) {
	$ranked_ids = get_transient( 'kago_weekly_featured_anime_ids' );
	if ( false === $ranked_ids ) {
		$posts = get_posts(
			array(
				'post_type'              => 'anime',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'all',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
			)
		);
		$now        = time();
		$cutoff     = (int) floor( ( $now - WEEK_IN_SECONDS ) / HOUR_IN_SECONDS );
		$ranked     = array();
		foreach ( $posts as $post ) {
			$hours = get_post_meta( $post->ID, '_kago_view_hours', true );
			if ( ! is_array( $hours ) ) {
				continue;
			}
			$week_views = 0;
			foreach ( $hours as $hour => $count ) {
				if ( (int) $hour >= $cutoff ) {
					$week_views += (int) $count;
				}
			}
			if ( $week_views ) {
				$ranked[] = array(
					'id'         => (int) $post->ID,
					'week_views' => $week_views,
					'all_views'  => (int) kago_meta( $post->ID, 'views', 0 ),
					'post_date'  => $post->post_date,
				);
			}
		}
		usort( $ranked, function ( $a, $b ) {
			return ( $b['week_views'] <=> $a['week_views'] )
				?: ( $b['all_views'] <=> $a['all_views'] )
				?: strcmp( $b['post_date'], $a['post_date'] );
		} );
		$ranked_ids = array_column( $ranked, 'id' );
		set_transient( 'kago_weekly_featured_anime_ids', $ranked_ids, 5 * MINUTE_IN_SECONDS );
	}
	return array_slice( (array) $ranked_ids, 0, max( 1, (int) $limit ) );
}

add_action(
	'template_redirect',
	function () {
		if ( ! is_singular( array( 'anime', 'movie', 'episode' ) ) || is_preview() || kago_is_bot() ) {
			return;
		}
		$post_id = get_queried_object_id();
		$cookie  = 'kago_v_' . $post_id;
		if ( isset( $_COOKIE[ $cookie ] ) || headers_sent() ) {
			return;
		}
		$target = $post_id;
		if ( 'episode' === get_post_type( $post_id ) ) {
			$target = (int) kago_meta( $post_id, 'anime_id', 0 );
		}
		if ( $target ) {
			kago_bump_views( $target );
		}
		setcookie( $cookie, '1', time() + 6 * HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
	}
);
