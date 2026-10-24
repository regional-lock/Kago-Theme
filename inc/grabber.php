<?php
/**
 * MAL Grabber: imports anime metadata through Tenrai (default) or Jikan.
 * Both expose the same Jikan-v4 style JSON, so one mapper serves both.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Settings ---------- */

function kago_gr_opts() {
	$saved_options = get_option( 'kago_grabber', array() );
	if ( is_array( $saved_options ) && array_key_exists( 'tmdb_api_key', $saved_options ) ) {
		unset( $saved_options['tmdb_api_key'] );
		update_option( 'kago_grabber', $saved_options, false );
	}
	$options = wp_parse_args(
		$saved_options,
		array(
			'order'        => 'tenrai,jikan',
			'tenrai_url'   => 'https://api.tenrai.org/v1',
			'jikan_url'    => 'https://api.jikan.moe/v4',
			'server_key'   => '',
			'sfw'          => '1',
			'title_lang'   => 'romaji',
			'image_mode'   => 'download',
			'cast_limit'   => 16,
			'episodes'     => '0',
			'timeout'      => 25,
			'force_ipv4'   => '1',
			'cache_hours'  => 12,
		)
	);
	$options['title_lang'] = 'romaji';
	return $options;
}

function kago_gr_log( $message ) {
	$log   = get_option( 'kago_gr_log', array() );
	$log   = is_array( $log ) ? $log : array();
	$log[] = gmdate( 'Y-m-d H:i:s' ) . ' — ' . $message;
	update_option( 'kago_gr_log', array_slice( $log, -100 ), false );
}

/* ---------- HTTP client ---------- */

function kago_gr_throttle() {
	static $last = 0.0;
	$gap = 0.35;
	$now = microtime( true );
	if ( $last && ( $now - $last ) < $gap ) {
		usleep( (int) ( ( $gap - ( $now - $last ) ) * 1000000 ) );
	}
	$last = microtime( true );
}

function kago_gr_providers() {
	$o     = kago_gr_opts();
	$names = array_filter( array_map( 'trim', explode( ',', $o['order'] ) ) );
	$out   = array();
	foreach ( $names as $name ) {
		if ( 'tenrai' === $name && $o['tenrai_url'] ) {
			$out['tenrai'] = untrailingslashit( $o['tenrai_url'] );
		} elseif ( 'jikan' === $name && $o['jikan_url'] ) {
			$out['jikan'] = untrailingslashit( $o['jikan_url'] );
		}
	}
	return $out;
}

/** One HTTP GET with retries. Returns decoded array or WP_Error. */
function kago_gr_http( $provider, $url ) {
	$o       = kago_gr_opts();
	$headers = array( 'Accept' => 'application/json', 'User-Agent' => 'KagoFansubTheme/' . KAGO_VERSION . '; ' . home_url() );
	if ( 'tenrai' === $provider && $o['server_key'] ) {
		$headers['X-Server-Key'] = $o['server_key'];
	}
	$force_v4 = ! empty( $o['force_ipv4'] );
	$filter   = function ( $handle ) use ( $force_v4, $o ) {
		if ( $force_v4 && defined( 'CURLOPT_IPRESOLVE' ) ) {
			curl_setopt( $handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, min( 15, (int) $o['timeout'] ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	};
	add_action( 'http_api_curl', $filter );
	$result = new WP_Error( 'kago_gr', __( 'Unknown error.', 'kagofansub' ) );
	for ( $attempt = 1; $attempt <= 3; $attempt++ ) {
		kago_gr_throttle();
		$res = wp_remote_get( $url, array( 'timeout' => (int) $o['timeout'], 'headers' => $headers ) );
		if ( is_wp_error( $res ) ) {
			$result = new WP_Error( 'kago_conn', sprintf( __( 'Could not reach %1$s: %2$s. Possible causes: blocked by ISP/firewall, slow connection or the server is down. Try "Force IPv4", a higher timeout, another DNS/VPN, or switch provider.', 'kagofansub' ), $provider, $res->get_error_message() ) );
			sleep( $attempt );
			continue;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( 429 === $code ) {
			$wait = (int) wp_remote_retrieve_header( $res, 'retry-after' );
			$result = new WP_Error( 'kago_429', __( 'Rate limited by the API, retrying.', 'kagofansub' ) );
			sleep( min( 10, max( 1, $wait ) ) );
			continue;
		}
		if ( 403 === $code ) {
			remove_action( 'http_api_curl', $filter );
			return new WP_Error( 'kago_403', __( 'The API blocked this IP temporarily (anti-abuse). Try again in a few hours.', 'kagofansub' ) );
		}
		if ( $code >= 500 ) {
			$result = new WP_Error( 'kago_5xx', sprintf( __( 'The source server returned HTTP %d.', 'kagofansub' ), $code ) );
			sleep( $attempt );
			continue;
		}
		if ( 404 === $code ) {
			remove_action( 'http_api_curl', $filter );
			return new WP_Error( 'kago_404', __( 'Not found on MyAnimeList.', 'kagofansub' ) );
		}
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( $code >= 400 || ! is_array( $data ) ) {
			$result = new WP_Error( 'kago_bad', sprintf( __( 'Unexpected response (HTTP %d).', 'kagofansub' ), $code ) );
			break;
		}
		remove_action( 'http_api_curl', $filter );
		return $data;
	}
	remove_action( 'http_api_curl', $filter );
	return $result;
}

/** GET with provider fallback + transient cache. */
function kago_gr_get( $path, $query = array(), $flags = array() ) {
	$o = kago_gr_opts();
	$key = 'kago_gr_' . md5( $path . wp_json_encode( $query ) . wp_json_encode( $flags ) . $o['sfw'] );
	$hit = get_transient( $key );
	if ( false !== $hit ) {
		return $hit;
	}
	$error = new WP_Error( 'kago_none', __( 'No provider is enabled.', 'kagofansub' ) );
	foreach ( kago_gr_providers() as $provider => $base ) {
		$qs = http_build_query( $query );
		foreach ( $flags as $flag ) {
			if ( 'sfw' === $flag ) {
				if ( empty( $o['sfw'] ) ) {
					continue;
				}
				$qs .= ( $qs ? '&' : '' ) . ( 'tenrai' === $provider ? 'sfw-strict' : 'sfw=true' );
			}
		}
		$data = kago_gr_http( $provider, $base . $path . ( $qs ? '?' . $qs : '' ) );
		if ( ! is_wp_error( $data ) ) {
			set_transient( $key, $data, max( 1, (int) $o['cache_hours'] ) * HOUR_IN_SECONDS );
			return $data;
		}
		kago_gr_log( $provider . ' ' . $path . ': ' . $data->get_error_message() );
		$error = $data;
		if ( 'kago_404' === $data->get_error_code() ) {
			return $data;
		}
	}
	return $error;
}

/* ---------- Mapping helpers ---------- */

function kago_gr_person_name( $name ) {
	$name = trim( (string) $name );
	if ( false !== strpos( $name, ', ' ) ) {
		$parts = explode( ', ', $name, 2 );
		return trim( $parts[1] . ' ' . $parts[0] );
	}
	return $name;
}

function kago_gr_norm_rating( $rating ) {
	$r = (string) $rating;
	$map = array( 'Rx' => 'Rx', 'R+' => 'R+', 'R - 17' => 'R-17', 'PG-13' => 'PG-13', 'PG -' => 'PG', 'G -' => 'G' );
	foreach ( $map as $prefix => $name ) {
		if ( 0 === strpos( $r, $prefix ) ) {
			return array( $name, $r );
		}
	}
	return array( '', '' );
}

function kago_gr_norm_source( $source ) {
	$map = array( 'manga' => 'Manga', 'light novel' => 'Light Novel', 'web novel' => 'Web Novel', 'original' => 'Original', 'game' => 'Game', 'visual novel' => 'Visual Novel', 'novel' => 'Novel', '4-koma manga' => '4-koma Manga' );
	$key = strtolower( trim( (string) $source ) );
	return isset( $map[ $key ] ) ? $map[ $key ] : ( '' === $key ? '' : 'Other' );
}

function kago_gr_norm_type( $type ) {
	$t = strtolower( (string) $type );
	if ( 'tv' === $t ) {
		return 'TV';
	}
	if ( 'movie' === $t ) {
		return 'Movie';
	}
	if ( in_array( $t, array( 'ova', 'ona' ), true ) ) {
		return strtoupper( $t );
	}
	return 'Special';
}

function kago_gr_set_term( $post_id, $name, $taxonomy, $append = false, $description = '' ) {
	if ( '' === trim( (string) $name ) ) {
		return;
	}
	$term = term_exists( (string) $name, $taxonomy );
	if ( ! $term ) {
		$term = wp_insert_term( (string) $name, $taxonomy, $description ? array( 'description' => $description ) : array() );
	}
	if ( is_wp_error( $term ) ) {
		return;
	}
	wp_set_object_terms( $post_id, array( (int) ( is_array( $term ) ? $term['term_id'] : $term ) ), $taxonomy, $append );
}

function kago_gr_sideload( $url, $post_id, $slug, $desc ) {
	if ( ! $url ) {
		return 0;
	}
	$existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_kago_source_url', 'meta_value' => $url, 'posts_per_page' => 1, 'fields' => 'ids' ) );
	if ( $existing ) {
		return (int) $existing[0];
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = download_url( $url, 30 );
	if ( is_wp_error( $tmp ) ) {
		kago_gr_log( 'Image download failed: ' . $tmp->get_error_message() );
		return 0;
	}
	$ext  = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );
	$file = array( 'name' => sanitize_file_name( $slug . '.' . ( $ext ? $ext : 'jpg' ) ), 'tmp_name' => $tmp );
	$id   = media_handle_sideload( $file, $post_id, $desc );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		kago_gr_log( 'Image import failed: ' . $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_kago_source_url', $url );
	update_post_meta( $id, '_wp_attachment_image_alt', $desc );
	return (int) $id;
}

function kago_gr_find_by_mal( $mal_id ) {
	$ids = get_posts( array( 'post_type' => array( 'anime', 'movie' ), 'post_status' => 'any', 'meta_key' => '_kago_mal_id', 'meta_value' => (int) $mal_id, 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true ) );
	return $ids ? (int) $ids[0] : 0;
}

/* ---------- Search & import ---------- */

function kago_gr_search( $q ) {
	$res = kago_gr_get( '/anime', array( 'q' => $q, 'limit' => 12 ), array( 'sfw' ) );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$out = array();
	foreach ( (array) ( $res['data'] ?? array() ) as $a ) {
		$search_title = trim( (string) ( $a['title'] ?? '' ) );
		if ( '' === $search_title ) {
			$search_title = trim( (string) ( $a['title_romanji'] ?? ( $a['title_english'] ?? '' ) ) );
		}
		$out[] = array(
			'mal_id' => (int) $a['mal_id'],
			'title'  => $search_title,
			'type'   => $a['type'] ?? '',
			'year'   => $a['year'] ?? ( isset( $a['aired']['from'] ) ? substr( $a['aired']['from'], 0, 4 ) : '' ),
			'score'  => $a['score'] ?? '',
			'image'  => $a['images']['jpg']['image_url'] ?? '',
			'exists' => kago_gr_find_by_mal( (int) $a['mal_id'] ),
		);
	}
	return $out;
}

function kago_gr_import( $mal_id, $with_cast = true, $with_episodes = false ) {
	$o    = kago_gr_opts();
	$full = kago_gr_get( '/anime/' . (int) $mal_id . '/full' );
	if ( is_wp_error( $full ) ) {
		return $full;
	}
	$a = $full['data'] ?? null;
	if ( ! is_array( $a ) ) {
		return new WP_Error( 'kago_bad', __( 'Empty response.', 'kagofansub' ) );
	}
	$title_en = trim( (string) ( $a['title_english'] ?? '' ) );
	$title    = trim( (string) ( $a['title'] ?? '' ) );
	if ( '' === $title ) {
		$title = trim( (string) ( $a['title_romanji'] ?? ( $title_en ? $title_en : ( $a['title_japanese'] ?? '' ) ) ) );
	}
	$type     = kago_gr_norm_type( $a['type'] ?? 'TV' );
	$pt       = 'Movie' === $type ? 'movie' : 'anime';
	$synopsis = trim( preg_replace( '/\s*\[Written by MAL Rewrite\]\s*/i', '', (string) ( $a['synopsis'] ?? '' ) ) );
	$post_id  = kago_gr_find_by_mal( $mal_id );
	$created  = ! $post_id;

	$postarr = array( 'post_type' => $pt, 'post_title' => $title, 'post_status' => $created ? 'draft' : get_post_status( $post_id ) );
	if ( $created || '' === trim( get_post_field( 'post_content', $post_id ) ) ) {
		$postarr['post_content'] = $synopsis;
	}
	if ( $created ) {
		$post_id = wp_insert_post( $postarr, true );
	} else {
		$postarr['ID'] = $post_id;
		unset( $postarr['post_type'] );
		wp_update_post( $postarr );
	}
	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return is_wp_error( $post_id ) ? $post_id : new WP_Error( 'kago_post', __( 'Could not create the post.', 'kagofansub' ) );
	}

	$from = isset( $a['aired']['from'] ) && $a['aired']['from'] ? substr( $a['aired']['from'], 0, 10 ) : '';
	$meta = array(
		'mal_id'         => (int) $mal_id,
		'title_jp'       => $a['title_japanese'] ?? '',
		'score'          => isset( $a['score'] ) && $a['score'] ? (float) $a['score'] : '',
		'duration'       => $a['duration'] ?? '',
		'total_episodes' => isset( $a['episodes'] ) && $a['episodes'] ? (int) $a['episodes'] : '',
		'release_date'   => $from,
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $post_id, '_kago_' . $k, $v );
	}
	$aliases = array_filter( array_merge( array( $a['title'] ), (array) ( $a['title_synonyms'] ?? array() ), $title_en ? array( $title_en ) : array() ) );
	update_post_meta( $post_id, '_kago_aliases', implode( ', ', array_unique( array_diff( $aliases, array( $title ) ) ) ) );

	/* Taxonomies. */
	kago_gr_set_term( $post_id, $type, 'anime_type' );
	kago_gr_set_term( $post_id, $a['status'] ?? '', 'anime_status' );
	kago_gr_set_term( $post_id, kago_gr_norm_source( $a['source'] ?? '' ), 'anime_source' );
	list( $rate_name, $rate_desc ) = kago_gr_norm_rating( $a['rating'] ?? '' );
	kago_gr_set_term( $post_id, $rate_name, 'age_rating', false, $rate_desc );
	wp_set_object_terms( $post_id, array(), 'studio' );
	foreach ( (array) ( $a['studios'] ?? array() ) as $s ) {
		kago_gr_set_term( $post_id, $s['name'] ?? '', 'studio', true );
	}
	wp_set_object_terms( $post_id, array(), 'genre' );
	foreach ( array_merge( (array) ( $a['genres'] ?? array() ), (array) ( $a['themes'] ?? array() ), (array) ( $a['demographics'] ?? array() ) ) as $g ) {
		kago_gr_set_term( $post_id, $g['name'] ?? '', 'genre', true );
	}
	if ( $from ) {
		kago_auto_terms( $post_id );
	} elseif ( ! empty( $a['year'] ) ) {
		wp_set_object_terms( $post_id, array( kago_ensure_year_term( (int) $a['year'] ) ), 'release_year' );
	}

	$notes = array();

	$poster = $a['images']['jpg']['large_image_url'] ?? ( $a['images']['jpg']['image_url'] ?? '' );

	/* Images. */
	$banner = $a['trailer']['images']['maximum_image_url'] ?? '';
	$slug   = sanitize_title( $title );
	if ( 'download' === $o['image_mode'] ) {
		$pid = kago_gr_sideload( $poster, $post_id, $slug . '-poster', $title );
		if ( $pid ) {
			update_post_meta( $post_id, '_kago_poster_id', $pid );
			set_post_thumbnail( $post_id, $pid );
		} elseif ( $poster ) {
			update_post_meta( $post_id, '_kago_poster_url', esc_url_raw( $poster ) );
		}
		if ( $banner ) {
			$bid = kago_gr_sideload( $banner, $post_id, $slug . '-banner', $title . ' banner' );
			if ( $bid ) {
				update_post_meta( $post_id, '_kago_banner_id', $bid );
			} else {
				update_post_meta( $post_id, '_kago_banner_url', esc_url_raw( $banner ) );
			}
		}
	} else {
		update_post_meta( $post_id, '_kago_poster_url', esc_url_raw( $poster ) );
		if ( $banner ) {
			update_post_meta( $post_id, '_kago_banner_url', esc_url_raw( $banner ) );
		}
	}

	/* Cast. */
	if ( $with_cast && (int) $o['cast_limit'] > 0 ) {
		$chars = kago_gr_get( '/anime/' . (int) $mal_id . '/characters' );
		if ( is_wp_error( $chars ) ) {
			$notes[] = __( 'Cast skipped: ', 'kagofansub' ) . $chars->get_error_message();
		} else {
			$list = (array) ( $chars['data'] ?? array() );
			usort( $list, function ( $x, $y ) {
				return ( 'Main' === ( $y['role'] ?? '' ) ) <=> ( 'Main' === ( $x['role'] ?? '' ) );
			} );
			$cast = array();
			foreach ( array_slice( $list, 0, (int) $o['cast_limit'] ) as $c ) {
				$va = null;
				foreach ( (array) ( $c['voice_actors'] ?? array() ) as $v ) {
					if ( 'Japanese' === ( $v['language'] ?? '' ) ) {
						$va = $v;
						break;
					}
				}
				$cast[] = array(
					'character'       => kago_gr_person_name( $c['character']['name'] ?? '' ),
					'role'            => $c['role'] ?? '',
					'character_img'   => esc_url_raw( $c['character']['images']['jpg']['image_url'] ?? '' ),
					'voice_actor'     => $va ? kago_gr_person_name( $va['person']['name'] ?? '' ) : '',
					'voice_actor_img' => $va ? esc_url_raw( $va['person']['images']['jpg']['image_url'] ?? '' ) : '',
				);
			}
			update_post_meta( $post_id, '_kago_cast', $cast );
			$notes[] = sprintf( __( '%d cast entries', 'kagofansub' ), count( $cast ) );
		}
	}

	/* Draft episodes. */
	if ( $with_episodes && 'anime' === $pt ) {
		$made = 0;
		for ( $page = 1; $page <= 10; $page++ ) {
			$eps = kago_gr_get( '/anime/' . (int) $mal_id . '/episodes', array( 'page' => $page ) );
			if ( is_wp_error( $eps ) ) {
				$notes[] = __( 'Episodes skipped: ', 'kagofansub' ) . $eps->get_error_message();
				break;
			}
			foreach ( (array) ( $eps['data'] ?? array() ) as $e ) {
				$n = (int) ( $e['mal_id'] ?? 0 );
				if ( ! $n ) {
					continue;
				}
				$episode_title = sanitize_text_field( $e['title_romanji'] ?? '' );
				if ( '' === $episode_title ) {
					$episode_title = sanitize_text_field( $e['title'] ?? ( $e['title_japanese'] ?? '' ) );
				}
				$existing_episode = kago_find_episode( $post_id, $n );
				if ( $existing_episode ) {
					$current_title = trim( (string) kago_meta( $existing_episode, 'ep_title', '' ) );
					$source_title  = sanitize_text_field( $e['title'] ?? '' );
					if ( '' !== $episode_title && ( '' === $current_title || $current_title === $source_title ) ) {
						update_post_meta( $existing_episode, '_kago_ep_title', $episode_title );
					}
					continue;
				}
				$ep_id = wp_insert_post( array( 'post_type' => 'episode', 'post_status' => 'draft', 'post_title' => $title . ' ' . kago_ep_label( $n ) ) );
				if ( $ep_id && ! is_wp_error( $ep_id ) ) {
					update_post_meta( $ep_id, '_kago_anime_id', $post_id );
					update_post_meta( $ep_id, '_kago_ep_number', $n );
					update_post_meta( $ep_id, '_kago_ep_title', $episode_title );
					update_post_meta( $ep_id, '_kago_ep_date', isset( $e['aired'] ) && $e['aired'] ? substr( $e['aired'], 0, 10 ) : '' );
					++$made;
				}
			}
			if ( empty( $eps['pagination']['has_next_page'] ) ) {
				break;
			}
		}
		$notes[] = sprintf( __( '%d draft episodes', 'kagofansub' ), $made );
	}

	kago_gr_log( ( $created ? 'Imported ' : 'Updated ' ) . $title . ' (MAL ' . (int) $mal_id . ')' . ( $notes ? ' — ' . implode( ', ', $notes ) : '' ) );
	return array( 'post_id' => $post_id, 'created' => $created, 'title' => $title, 'notes' => $notes, 'edit' => get_edit_post_link( $post_id, 'raw' ) );
}

function kago_find_episode( $anime_id, $number ) {
	$ids = get_posts( array( 'post_type' => 'episode', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => array( array( 'key' => '_kago_anime_id', 'value' => (int) $anime_id ), array( 'key' => '_kago_ep_number', 'value' => (int) $number ) ) ) );
	return $ids ? (int) $ids[0] : 0;
}

/* ---------- AJAX ---------- */

function kago_gr_ajax_guard() {
	check_ajax_referer( 'kago_grabber', 'nonce' );
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'kagofansub' ) ), 403 );
	}
}

add_action(
	'wp_ajax_kago_gr_search',
	function () {
		kago_gr_ajax_guard();
		$q = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
		if ( preg_match( '#myanimelist\.net/anime/(\d+)#', $q, $m ) ) {
			$q = $m[1];
		}
		if ( ctype_digit( $q ) ) {
			wp_send_json_success( array( 'direct' => (int) $q, 'exists' => kago_gr_find_by_mal( (int) $q ) ) );
		}
		if ( strlen( $q ) < 2 ) {
			wp_send_json_error( array( 'message' => __( 'Type at least 2 characters, a MAL ID or a MAL URL.', 'kagofansub' ) ) );
		}
		$res = kago_gr_search( $q );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}
		wp_send_json_success( array( 'results' => $res ) );
	}
);

add_action(
	'wp_ajax_kago_gr_import',
	function () {
		kago_gr_ajax_guard();
		$id  = isset( $_POST['mal_id'] ) ? absint( $_POST['mal_id'] ) : 0;
		$cast = ! empty( $_POST['cast'] );
		$eps  = ! empty( $_POST['episodes'] );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid MAL ID.', 'kagofansub' ) ) );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 120 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions
		}
		$res = kago_gr_import( $id, $cast, $eps );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}
		wp_send_json_success( $res );
	}
);

add_action(
	'wp_ajax_kago_gr_test',
	function () {
		kago_gr_ajax_guard();
		$rows = array();
		foreach ( kago_gr_providers() as $name => $base ) {
			$t   = microtime( true );
			$res = kago_gr_http( $name, $base . '/anime/1' );
			$ms  = (int) ( ( microtime( true ) - $t ) * 1000 );
			$rows[] = array( 'provider' => $name, 'ok' => ! is_wp_error( $res ), 'ms' => $ms, 'message' => is_wp_error( $res ) ? $res->get_error_message() : 'OK' );
		}
		wp_send_json_success( array( 'rows' => $rows, 'php' => PHP_VERSION, 'curl' => function_exists( 'curl_version' ) ? curl_version()['version'] : 'n/a' ) );
	}
);

/* ---------- Admin page ---------- */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=anime', __( 'MAL Grabber', 'kagofansub' ), __( 'MAL Grabber', 'kagofansub' ), 'edit_others_posts', 'kago-grabber', 'kago_gr_page' );
	}
);

add_action(
	'admin_post_kago_gr_save',
	function () {
		check_admin_referer( 'kago_gr_save' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'kagofansub' ) );
		}
		$in  = isset( $_POST['kago_gr'] ) && is_array( $_POST['kago_gr'] ) ? wp_unslash( $_POST['kago_gr'] ) : array();
		$old = kago_gr_opts();
		$new = array(
			'order'       => in_array( $in['order'] ?? '', array( 'tenrai,jikan', 'jikan,tenrai', 'tenrai', 'jikan' ), true ) ? $in['order'] : 'tenrai,jikan',
			'tenrai_url'  => esc_url_raw( $in['tenrai_url'] ?? $old['tenrai_url'] ),
			'jikan_url'   => esc_url_raw( $in['jikan_url'] ?? $old['jikan_url'] ),
			'server_key'  => ( isset( $in['server_key'] ) && '' !== trim( $in['server_key'] ) ) ? sanitize_text_field( $in['server_key'] ) : $old['server_key'],
			'sfw'         => ! empty( $in['sfw'] ) ? '1' : '',
			'title_lang'  => 'romaji',
			'image_mode'  => 'external' === ( $in['image_mode'] ?? '' ) ? 'external' : 'download',
			'cast_limit'  => min( 50, absint( $in['cast_limit'] ?? 16 ) ),
			'episodes'    => ! empty( $in['episodes'] ) ? '1' : '',
			'timeout'     => min( 60, max( 5, absint( $in['timeout'] ?? 25 ) ) ),
			'force_ipv4'  => ! empty( $in['force_ipv4'] ) ? '1' : '',
			'cache_hours' => min( 168, max( 1, absint( $in['cache_hours'] ?? 12 ) ) ),
		);
		if ( ! empty( $in['clear_key'] ) ) {
			$new['server_key'] = '';
		}
		update_option( 'kago_grabber', $new, false );
		if ( ! empty( $in['clear_cache'] ) ) {
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_kago\_gr\_%' OR option_name LIKE '\_transient\_timeout\_kago\_gr\_%'" ); // phpcs:ignore WordPress.DB
		}
		wp_safe_redirect( add_query_arg( array( 'post_type' => 'anime', 'page' => 'kago-grabber', 'saved' => 1 ), admin_url( 'edit.php' ) ) );
		exit;
	}
);

function kago_gr_page() {
	$o = kago_gr_opts();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'MAL Grabber', 'kagofansub' ); ?></h1>
		<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'kagofansub' ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Search MyAnimeList (through Tenrai/Jikan) or paste a MAL ID / URL, then import the anime or movie as a draft. Download links stay manual.', 'kagofansub' ); ?></p>

		<div class="card" style="max-width:900px;padding:16px">
			<p>
				<input type="text" id="kago-gr-q" class="regular-text" placeholder="<?php esc_attr_e( 'Title, MAL ID or MAL URL', 'kagofansub' ); ?>" style="width:360px">
				<button type="button" class="button button-primary" id="kago-gr-search"><?php esc_html_e( 'Search', 'kagofansub' ); ?></button>
			</p>
			<p>
				<label><input type="checkbox" id="kago-gr-cast" checked> <?php esc_html_e( 'Import cast & voice actors', 'kagofansub' ); ?></label>
				&nbsp;
				<label><input type="checkbox" id="kago-gr-eps" <?php checked( $o['episodes'], '1' ); ?>> <?php esc_html_e( 'Create draft episodes', 'kagofansub' ); ?></label>
			</p>
			<div id="kago-gr-results" class="kago-gr-results"></div>
			<div id="kago-gr-log" class="kago-gr-log"><?php esc_html_e( 'Ready.', 'kagofansub' ); ?></div>
		</div>
		<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<h2><?php esc_html_e( 'Settings', 'kagofansub' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:900px">
			<input type="hidden" name="action" value="kago_gr_save">
			<?php wp_nonce_field( 'kago_gr_save' ); ?>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Provider order', 'kagofansub' ); ?></th><td>
					<select name="kago_gr[order]">
						<?php foreach ( array( 'tenrai,jikan' => 'Tenrai → Jikan (fallback)', 'jikan,tenrai' => 'Jikan → Tenrai (fallback)', 'tenrai' => 'Tenrai only', 'jikan' => 'Jikan only' ) as $v => $l ) : ?>
							<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $o['order'], $v ); ?>><?php echo esc_html( $l ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Data comes from MyAnimeList via unofficial APIs. Tenrai: 4 requests/second, 120/minute per IP.', 'kagofansub' ); ?></p></td></tr>
				<tr><th>Tenrai URL</th><td><input type="url" class="regular-text" name="kago_gr[tenrai_url]" value="<?php echo esc_attr( $o['tenrai_url'] ); ?>"></td></tr>
				<tr><th>Jikan URL</th><td><input type="url" class="regular-text" name="kago_gr[jikan_url]" value="<?php echo esc_attr( $o['jikan_url'] ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Tenrai Server Key (optional)', 'kagofansub' ); ?></th><td>
					<input type="password" class="regular-text" name="kago_gr[server_key]" value="" autocomplete="new-password" placeholder="<?php echo $o['server_key'] ? esc_attr( '••••' . substr( $o['server_key'], -4 ) ) : ''; ?>">
					<?php if ( $o['server_key'] ) : ?><label><input type="checkbox" name="kago_gr[clear_key]" value="1"> <?php esc_html_e( 'Remove saved key', 'kagofansub' ); ?></label><?php endif; ?>
					<p class="description"><?php esc_html_e( 'Sent only from this server in the X-Server-Key header. Leave empty to keep the current value.', 'kagofansub' ); ?></p></td></tr>
				<tr><th><?php esc_html_e( 'Safe content filter', 'kagofansub' ); ?></th><td><label><input type="checkbox" name="kago_gr[sfw]" value="1" <?php checked( $o['sfw'], '1' ); ?>> <?php esc_html_e( 'Hide adult titles in search', 'kagofansub' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Imported title language', 'kagofansub' ); ?></th><td><?php esc_html_e( 'Romaji when available', 'kagofansub' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Poster/banner images', 'kagofansub' ); ?></th><td><select name="kago_gr[image_mode]"><option value="download" <?php selected( $o['image_mode'], 'download' ); ?>><?php esc_html_e( 'Download to Media Library (recommended)', 'kagofansub' ); ?></option><option value="external" <?php selected( $o['image_mode'], 'external' ); ?>><?php esc_html_e( 'External URL only', 'kagofansub' ); ?></option></select>
					<p class="description"><?php esc_html_e( 'Make sure you are allowed to use the imported images and text.', 'kagofansub' ); ?></p></td></tr>
				<tr><th><?php esc_html_e( 'Max cast entries', 'kagofansub' ); ?></th><td><input type="number" min="0" max="50" name="kago_gr[cast_limit]" value="<?php echo esc_attr( $o['cast_limit'] ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Create draft episodes by default', 'kagofansub' ); ?></th><td><input type="checkbox" name="kago_gr[episodes]" value="1" <?php checked( $o['episodes'], '1' ); ?>></td></tr>
				<tr><th><?php esc_html_e( 'Request timeout (seconds)', 'kagofansub' ); ?></th><td><input type="number" min="5" max="60" name="kago_gr[timeout]" value="<?php echo esc_attr( $o['timeout'] ); ?>"> <label><input type="checkbox" name="kago_gr[force_ipv4]" value="1" <?php checked( $o['force_ipv4'], '1' ); ?>> <?php esc_html_e( 'Force IPv4', 'kagofansub' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Cache (hours)', 'kagofansub' ); ?></th><td><input type="number" min="1" max="168" name="kago_gr[cache_hours]" value="<?php echo esc_attr( $o['cache_hours'] ); ?>"> <label><input type="checkbox" name="kago_gr[clear_cache]" value="1"> <?php esc_html_e( 'Clear cache on save', 'kagofansub' ); ?></label></td></tr>
			</table>
			<?php submit_button( __( 'Save settings', 'kagofansub' ) ); ?>
		</form>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Connection diagnostics', 'kagofansub' ); ?></h2>
		<p><button type="button" class="button" id="kago-gr-test"><?php esc_html_e( 'Test connection', 'kagofansub' ); ?></button></p>
		<div id="kago-gr-test-out"></div>

		<h2><?php esc_html_e( 'Recent log', 'kagofansub' ); ?></h2>
		<pre class="kago-gr-log"><?php echo esc_html( implode( "\n", array_reverse( array_slice( (array) get_option( 'kago_gr_log', array() ), -20 ) ) ) ); ?></pre>
	</div>
	<?php
}
