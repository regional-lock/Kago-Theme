<?php
/**
 * Demo content importer (Appearance → Kago Demo).
 * Posters are picked up from assets/images/<file> when present.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kago_demo_data() {
	$anime = array(
		array( 'frieren', 'Sousou no Frieren S2', '葬送のフリーレン', 9.35, array( 'Drama', 'Fantasy', 'Adventure' ), '2026-01-16', 'Madhouse', 'Finished Airing', 'PG-13', 'Manga', 10, 'frieren.webp', 'hero-frieren.webp', 3, 980, true, 'The long journey of a mage after the victory over the Demon King. A story about time, farewells, and the meaning of life.' ),
		array( 'dandadan', 'Dandadan S2', 'ダンダダン', 8.65, array( 'Action', 'Comedy', 'Supernatural' ), '2026-07-03', 'Science SARU', 'Currently Airing', 'R-17', 'Manga', 12, 'dandadan.webp', '', 4, 870, true, 'A girl who believes in ghosts and a boy who believes in aliens meet, and both are dragged into an absurd mess.' ),
		array( 'kusuriya', 'Kusuriya no Hitorigoto S2', '薬屋のひとりごと', 8.82, array( 'Mystery', 'Historical' ), '2026-01-10', 'OLM', 'Currently Airing', 'PG-13', 'Light Novel', 24, 'kusuriya.webp', '', 1, 760, true, 'A young apothecary solves mysteries inside the imperial palace with her knowledge of medicine.' ),
		array( 'sakamoto', 'Sakamoto Days S2', 'サカモトデイズ', 8.35, array( 'Action', 'Comedy' ), '2026-04-05', 'TMS Entertainment', 'Currently Airing', 'R-17', 'Manga', 12, 'sakamoto.webp', '', 1, 640, true, 'A legendary former hitman now lives a quiet life as a convenience store owner.' ),
		array( 'kaoru', 'Kaoru Hana wa Rin to Saku', '薫る花は凛と咲く', 8.41, array( 'Romance', 'Slice of Life' ), '2026-04-12', 'CloverWorks', 'Currently Airing', 'PG-13', 'Manga', 13, 'kaoru.webp', '', 2, 520, false, 'Two students from rival schools slowly grow closer.' ),
		array( 'bleach', 'Bleach', 'ブリーチ', 8.78, array( 'Action', 'Supernatural' ), '2004-10-05', 'Pierrot', 'Finished Airing', 'PG-13', 'Manga', 366, 'bleach.webp', '', 0, 710, false, 'A teenager gains the powers of a Soul Reaper and protects the living from evil spirits.' ),
		array( 'windbreaker', 'Wind Breaker', 'WIND BREAKER', 8.20, array( 'Action', 'School' ), '2026-04-03', 'Cloud Hearts', 'Currently Airing', 'PG-13', 'Manga', 13, 'windbreaker.webp', '', 1, 430, false, 'A loner who only cares about fighting joins a school of delinquents who protect their town.' ),
		array( 'rezero', 'Re:Zero', 'Re:ゼロから始める異世界生活', 8.29, array( 'Drama', 'Fantasy', 'Isekai' ), '2026-10-01', 'White Fox', 'Currently Airing', 'R-17', 'Light Novel', 25, 'rezero.webp', '', 0, 590, false, 'A shut-in is summoned to another world and discovers he can return from death.' ),
	);
	$movies = array(
		array( 'jujutsu-0', 'Jujutsu Kaisen 0', 8.45, 2021, array( 'Action', 'Supernatural' ), 'jujutsu.webp' ),
		array( 'mugen-ressha', 'Kimetsu no Yaiba: Mugen Ressha-hen', 8.53, 2020, array( 'Action', 'Fantasy' ), 'demon-slayer.webp' ),
		array( 'onepiece-red', 'One Piece Film: Red', 8.19, 2022, array( 'Action', 'Adventure' ), 'onepiece.webp' ),
		array( 'blue-lock-nagi', 'Blue Lock: Episode Nagi', 7.63, 2024, array( 'Sports', 'Drama' ), 'blue-lock.webp' ),
		array( 'solo-leveling-reawakening', 'Solo Leveling: ReAwakening', 8.06, 2024, array( 'Action', 'Fantasy' ), 'solo-leveling.jpg' ),
	);
	return array( $anime, $movies );
}

function kago_demo_attach( $filename, $post_id ) {
	$path = KAGO_DIR . '/assets/images/' . $filename;
	if ( ! $filename || ! file_exists( $path ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( $filename );
	copy( $path, $tmp );
	$id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp ), $post_id );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	return (int) $id;
}

function kago_demo_find( $demo_id ) {
	$ids = get_posts( array( 'post_type' => array( 'anime', 'movie' ), 'post_status' => 'any', 'meta_key' => '_kago_demo_id', 'meta_value' => $demo_id, 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true ) );
	return $ids ? (int) $ids[0] : 0;
}

function kago_demo_import() {
	kago_seed_terms();
	list( $anime, $movies ) = kago_demo_data();
	$made = 0;
	foreach ( $anime as $a ) {
		if ( kago_demo_find( $a[0] ) ) {
			continue;
		}
		$id = wp_insert_post( array( 'post_type' => 'anime', 'post_status' => 'publish', 'post_title' => $a[1], 'post_content' => $a[16] ) );
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		update_post_meta( $id, '_kago_demo_id', $a[0] );
		update_post_meta( $id, '_kago_title_jp', $a[2] );
		update_post_meta( $id, '_kago_score', $a[3] );
		update_post_meta( $id, '_kago_release_date', $a[5] );
		update_post_meta( $id, '_kago_duration', '24 min. per ep.' );
		update_post_meta( $id, '_kago_total_episodes', $a[10] );
		update_post_meta( $id, '_kago_views', $a[14] );
		wp_set_object_terms( $id, $a[4], 'genre' );
		kago_gr_set_term( $id, $a[6], 'studio' );
		kago_gr_set_term( $id, $a[7], 'anime_status' );
		kago_gr_set_term( $id, $a[8], 'age_rating' );
		kago_gr_set_term( $id, $a[9], 'anime_source' );
		kago_gr_set_term( $id, 'TV', 'anime_type' );
		kago_auto_terms( $id );
		$poster = kago_demo_attach( $a[11], $id );
		if ( $poster ) {
			update_post_meta( $id, '_kago_poster_id', $poster );
			set_post_thumbnail( $id, $poster );
		}
		$banner = $a[12] ? kago_demo_attach( $a[12], $id ) : 0;
		if ( $banner ) {
			update_post_meta( $id, '_kago_banner_id', $banner );
		}
		for ( $n = 1; $n <= $a[13]; $n++ ) {
			$ep = wp_insert_post( array( 'post_type' => 'episode', 'post_status' => 'publish', 'post_title' => $a[1] . ' ' . kago_ep_label( $n ) ) );
			if ( $ep && ! is_wp_error( $ep ) ) {
				update_post_meta( $ep, '_kago_anime_id', $id );
				update_post_meta( $ep, '_kago_ep_number', $n );
				update_post_meta( $ep, '_kago_demo_ep', 1 );
			}
		}
		if ( $a[13] ) {
			kago_refresh_anime_from_episodes( $id );
		}
		if ( 'frieren' === $a[0] ) {
			update_post_meta(
				$id,
				'_kago_cast',
				array(
					array( 'character' => 'Frieren', 'role' => 'Main', 'character_img' => '', 'voice_actor' => 'Atsumi Tanezaki', 'voice_actor_img' => '' ),
					array( 'character' => 'Fern', 'role' => 'Main', 'character_img' => '', 'voice_actor' => 'Kana Ichinose', 'voice_actor_img' => '' ),
					array( 'character' => 'Stark', 'role' => 'Main', 'character_img' => '', 'voice_actor' => 'Chiaki Kobayashi', 'voice_actor_img' => '' ),
				)
			);
		}
		++$made;
	}
	foreach ( $movies as $m ) {
		if ( kago_demo_find( $m[0] ) ) {
			continue;
		}
		$id = wp_insert_post( array( 'post_type' => 'movie', 'post_status' => 'publish', 'post_title' => $m[1], 'post_content' => 'Demo movie entry.' ) );
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		update_post_meta( $id, '_kago_demo_id', $m[0] );
		update_post_meta( $id, '_kago_score', $m[2] );
		update_post_meta( $id, '_kago_release_date', $m[3] . '-06-01' );
		update_post_meta( $id, '_kago_quality', 'BD' );
		update_post_meta( $id, '_kago_views', 100 + $made );
		wp_set_object_terms( $id, $m[4], 'genre' );
		kago_gr_set_term( $id, 'Movie', 'anime_type' );
		kago_auto_terms( $id );
		$poster = kago_demo_attach( $m[5], $id );
		if ( $poster ) {
			update_post_meta( $id, '_kago_poster_id', $poster );
			set_post_thumbnail( $id, $poster );
		}
		++$made;
	}
	flush_rewrite_rules();
	return $made;
}

add_action(
	'admin_menu',
	function () {
		add_theme_page( __( 'Kago Demo', 'kagofansub' ), __( 'Kago Demo', 'kagofansub' ), 'manage_options', 'kago-demo', function () {
			echo '<div class="wrap"><h1>' . esc_html__( 'Kago Demo Import', 'kagofansub' ) . '</h1>';
			if ( isset( $_GET['imported'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( '%d items created.', 'kagofansub' ), absint( $_GET['imported'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification
			}
			echo '<p>' . esc_html__( 'Creates sample anime, episodes and movies (skips items that already exist). Copy your poster files into the theme folder assets/images/ first (frieren.webp, dandadan.webp …) to have them attached.', 'kagofansub' ) . '</p>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="kago_demo_import">';
			wp_nonce_field( 'kago_demo_import' );
			submit_button( __( 'Import demo content', 'kagofansub' ) );
			echo '</form></div>';
		} );
	}
);

add_action(
	'admin_post_kago_demo_import',
	function () {
		check_admin_referer( 'kago_demo_import' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'kagofansub' ) );
		}
		$n = kago_demo_import();
		wp_safe_redirect( add_query_arg( array( 'page' => 'kago-demo', 'imported' => $n ), admin_url( 'themes.php' ) ) );
		exit;
	}
);
