<?php
/**
 * Meta boxes: details (poster, banner, scores), cast, downloads, episode.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'add_meta_boxes',
	function () {
		foreach ( array( 'anime', 'movie' ) as $pt ) {
			add_meta_box( 'kago_details', __( 'Anime Details', 'kagofansub' ), 'kago_box_details', $pt, 'normal', 'high' );
			add_meta_box( 'kago_cast', __( 'Characters & Voice Actors', 'kagofansub' ), 'kago_box_cast', $pt, 'normal', 'default' );
		}
		add_meta_box( 'kago_downloads', __( 'Download / Stream Links', 'kagofansub' ), 'kago_box_downloads', 'movie', 'normal', 'default' );
		add_meta_box( 'kago_downloads', __( 'Download / Stream Links', 'kagofansub' ), 'kago_box_downloads', 'episode', 'normal', 'default' );
		add_meta_box( 'kago_episode', __( 'Episode details', 'kagofansub' ), 'kago_box_episode', 'episode', 'normal', 'high' );
	}
);

function kago_field( $label, $input, $help = '' ) {
	echo '<p class="kago-field"><label><span class="kago-label">' . esc_html( $label ) . '</span>' . $input . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput
	if ( $help ) {
		echo '<span class="description">' . esc_html( $help ) . '</span>';
	}
	echo '</p>';
}

function kago_media_field( $name, $label, $attachment_id ) {
	$img = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'kago-thumb' ) : '';
	echo '<div class="kago-field kago-media" data-name="' . esc_attr( $name ) . '"><span class="kago-label">' . esc_html( $label ) . '</span>';
	echo '<div class="kago-media-preview">' . ( $img ? '<img src="' . esc_url( $img ) . '" alt="">' : '' ) . '</div>';
	echo '<input type="hidden" name="kago[' . esc_attr( $name ) . ']" value="' . esc_attr( $attachment_id ) . '" class="kago-media-id">';
	echo '<button type="button" class="button kago-media-pick">' . esc_html__( 'Choose image', 'kagofansub' ) . '</button> ';
	echo '<button type="button" class="button-link kago-media-clear">' . esc_html__( 'Remove', 'kagofansub' ) . '</button></div>';
}

function kago_box_details( $post ) {
	wp_nonce_field( 'kago_save_meta', 'kago_meta_nonce' );
	$m = function ( $k, $d = '' ) use ( $post ) {
		return kago_meta( $post->ID, $k, $d );
	};
	echo '<div class="kago-grid">';
	kago_media_field( 'poster_id', __( 'Poster', 'kagofansub' ), (int) $m( 'poster_id', 0 ) );
	kago_media_field( 'banner_id', __( 'Banner (hero & header)', 'kagofansub' ), (int) $m( 'banner_id', 0 ) );
	kago_field( __( 'Poster URL (external, optional)', 'kagofansub' ), '<input type="url" class="widefat" name="kago[poster_url]" value="' . esc_attr( $m( 'poster_url' ) ) . '">', __( 'Used only when no poster/featured image is set.', 'kagofansub' ) );
	kago_field( __( 'Banner URL (external, optional)', 'kagofansub' ), '<input type="url" class="widefat" name="kago[banner_url]" value="' . esc_attr( $m( 'banner_url' ) ) . '">' );
	echo '</div>';
	kago_field( __( 'Japanese Title', 'kagofansub' ), '<input type="text" class="widefat" name="kago[title_jp]" value="' . esc_attr( $m( 'title_jp' ) ) . '">' );
	kago_field( __( 'Alternative titles (comma separated)', 'kagofansub' ), '<input type="text" class="widefat" name="kago[aliases]" value="' . esc_attr( $m( 'aliases' ) ) . '">' );
	echo '<div class="kago-grid">';
	kago_field( __( 'Release Date', 'kagofansub' ), '<input type="date" class="widefat" name="kago[release_date]" value="' . esc_attr( $m( 'release_date' ) ) . '">' );
	kago_field( __( 'Score', 'kagofansub' ), '<input type="number" step="0.01" min="0" max="10" class="widefat" name="kago[score]" value="' . esc_attr( $m( 'score' ) ) . '">' );
	kago_field( __( 'Duration', 'kagofansub' ), '<input type="text" class="widefat" name="kago[duration]" value="' . esc_attr( $m( 'duration' ) ) . '" placeholder="24 min. per ep.">' );
	kago_field( __( 'Total Episodes', 'kagofansub' ), '<input type="number" min="0" class="widefat" name="kago[total_episodes]" value="' . esc_attr( $m( 'total_episodes' ) ) . '">' );
	$q = $m( 'quality' );
	$select = '<select name="kago[quality]" class="widefat"><option value="">—</option>';
	foreach ( array( 'BD', 'WEB-DL', 'WEB', 'DVD' ) as $opt ) {
		$select .= '<option value="' . esc_attr( $opt ) . '"' . selected( $q, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
	}
	kago_field( __( 'Release quality label', 'kagofansub' ), $select . '</select>' );
	kago_field( __( 'MyAnimeList ID', 'kagofansub' ), '<input type="number" min="0" class="widefat" name="kago[mal_id]" value="' . esc_attr( $m( 'mal_id' ) ) . '">' );
	echo '</div>';
	echo '<p class="description">' . esc_html__( 'Studio, rating, source, year, season, type and status are set in the boxes on the right. The synopsis is the main editor content.', 'kagofansub' ) . '</p>';
}

function kago_cast_row( $i, $row ) {
	$row = wp_parse_args( (array) $row, array( 'character' => '', 'role' => 'Main', 'character_img' => '', 'voice_actor' => '', 'voice_actor_img' => '' ) );
	$n   = 'kago_cast[' . $i . ']';
	echo '<div class="kago-cast-row"><div class="kago-grid">';
	foreach ( array( 'character' => __( 'Character', 'kagofansub' ), 'role' => __( 'Role (Main/Supporting)', 'kagofansub' ), 'character_img' => __( 'Character image URL', 'kagofansub' ), 'voice_actor' => __( 'Voice actor (Japanese)', 'kagofansub' ), 'voice_actor_img' => __( 'Voice actor image URL', 'kagofansub' ) ) as $key => $label ) {
		echo '<label><span class="kago-label">' . esc_html( $label ) . '</span><input type="text" class="widefat" name="' . esc_attr( $n . '[' . $key . ']' ) . '" value="' . esc_attr( $row[ $key ] ) . '"></label>';
	}
	echo '</div><button type="button" class="button-link kago-cast-remove">' . esc_html__( 'Remove row', 'kagofansub' ) . '</button></div>';
}

function kago_box_cast( $post ) {
	$cast = get_post_meta( $post->ID, '_kago_cast', true );
	$cast = is_array( $cast ) ? array_values( $cast ) : array();
	echo '<div id="kago-cast-rows">';
	foreach ( $cast as $i => $row ) {
		kago_cast_row( $i, $row );
	}
	echo '</div>';
	echo '<template id="kago-cast-template">';
	kago_cast_row( '__i__', array() );
	echo '</template>';
	echo '<p><button type="button" class="button" id="kago-cast-add" data-next="' . esc_attr( count( $cast ) ) . '">' . esc_html__( 'Add character', 'kagofansub' ) . '</button></p>';
}

function kago_box_downloads( $post ) {
	$data = kago_downloads( $post->ID );
	$groups = array();
	if ( metadata_exists( 'post', $post->ID, '_kago_download_groups_v2' ) ) {
		foreach ( kago_download_qualities_for_post( $post->ID ) as $quality ) {
			$groups[] = array(
				'quality' => $quality,
				'hosts'   => isset( $data[ $quality ] ) && is_array( $data[ $quality ] ) ? $data[ $quality ] : array(),
			);
		}
	} elseif ( ! empty( $data ) || metadata_exists( 'post', $post->ID, '_kago_download_qualities' ) || metadata_exists( 'post', $post->ID, '_kago_download_hosts' ) ) {
		$legacy_hosts = kago_download_hosts_for_post( $post->ID );
		foreach ( kago_download_qualities_for_post( $post->ID ) as $quality ) {
			$hosts = isset( $data[ $quality ] ) && is_array( $data[ $quality ] ) ? $data[ $quality ] : array();
			foreach ( array_unique( array_merge( $legacy_hosts, array_keys( $hosts ) ) ) as $host ) {
				$hosts[ $host ] = isset( $hosts[ $host ] ) ? $hosts[ $host ] : '';
			}
			$groups[] = array( 'quality' => $quality, 'hosts' => $hosts );
		}
	}

	echo '<p class="description">' . esc_html__( 'Add a Quality group, then add download/stream hosts under it.', 'kagofansub' ) . '</p>';
	echo '<input type="hidden" name="kago_dl_groups_present" value="1">';
	echo '<div id="kago-download-groups">';
	foreach ( $groups as $group_index => $group ) {
		echo '<div class="kago-dl-group">';
		echo '<div class="kago-dl-quality-row"><label>' . esc_html__( 'Quality', 'kagofansub' ) . '</label>';
		echo '<input type="text" class="widefat" name="' . esc_attr( 'kago_dl_groups[' . $group_index . '][quality]' ) . '" value="' . esc_attr( $group['quality'] ) . '" placeholder="4K, HD, CAM">';
		echo '<button type="button" class="button kago-dl-remove-quality">' . esc_html__( 'Remove Quality', 'kagofansub' ) . '</button></div>';
		echo '<div class="kago-dl-hosts">';
		$host_row_index = 0;
		foreach ( $group['hosts'] as $host_index => $url ) {
			$host = (string) $host_index;
			echo '<div class="kago-dl-host-row"><input type="text" class="regular-text" name="' . esc_attr( 'kago_dl_groups[' . $group_index . '][hosts][' . $host_row_index . '][name]' ) . '" value="' . esc_attr( $host ) . '" placeholder="Custom host name">';
			echo '<input type="url" class="widefat" name="' . esc_attr( 'kago_dl_groups[' . $group_index . '][hosts][' . $host_row_index . '][url]' ) . '" value="' . esc_attr( $url ) . '" placeholder="https://...">';
			echo '<button type="button" class="button kago-dl-remove-host">' . esc_html__( 'Remove', 'kagofansub' ) . '</button></div>';
			++$host_row_index;
		}
		echo '</div><button type="button" class="button kago-dl-add-host" data-group="' . esc_attr( $group_index ) . '" data-next-host="' . esc_attr( count( $group['hosts'] ) ) . '">' . esc_html__( '+ Add Host', 'kagofansub' ) . '</button></div>';
	}
	echo '</div><p><button type="button" class="button button-primary" id="kago-dl-add-quality" data-next-group="' . esc_attr( count( $groups ) ) . '">' . esc_html__( '+ Add Quality', 'kagofansub' ) . '</button></p>';
	echo '<template id="kago-dl-quality-template"><div class="kago-dl-group"><div class="kago-dl-quality-row"><label>' . esc_html__( 'Quality', 'kagofansub' ) . '</label><input type="text" class="widefat" name="kago_dl_groups[__group__][quality]" value="" placeholder="4K, HD, CAM"><button type="button" class="button kago-dl-remove-quality">' . esc_html__( 'Remove Quality', 'kagofansub' ) . '</button></div><div class="kago-dl-hosts"><div class="kago-dl-host-row"><input type="text" class="regular-text" name="kago_dl_groups[__group__][hosts][0][name]" value="" placeholder="Custom host name"><input type="url" class="widefat" name="kago_dl_groups[__group__][hosts][0][url]" value="" placeholder="https://..."><button type="button" class="button kago-dl-remove-host">' . esc_html__( 'Remove', 'kagofansub' ) . '</button></div></div><button type="button" class="button kago-dl-add-host" data-group="__group__" data-next-host="1">' . esc_html__( '+ Add Host', 'kagofansub' ) . '</button></div></template>';
	echo '<template id="kago-dl-host-template"><div class="kago-dl-host-row"><input type="text" class="regular-text" name="kago_dl_groups[__group__][hosts][__host__][name]" value="" placeholder="Custom host name"><input type="url" class="widefat" name="kago_dl_groups[__group__][hosts][__host__][url]" value="" placeholder="https://..."><button type="button" class="button kago-dl-remove-host">' . esc_html__( 'Remove', 'kagofansub' ) . '</button></div></template>';
}

function kago_box_episode( $post ) {
	wp_nonce_field( 'kago_save_meta', 'kago_meta_nonce' );
	$anime_id = (int) kago_meta( $post->ID, 'anime_id', 0 );
	$animes   = get_posts( array( 'post_type' => 'anime', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true ) );
	$select   = '<select name="kago_anime_id" class="widefat"><option value="">' . esc_html__( '— Select anime —', 'kagofansub' ) . '</option>';
	foreach ( $animes as $a ) {
		$select .= '<option value="' . esc_attr( $a->ID ) . '"' . selected( $anime_id, $a->ID, false ) . '>' . esc_html( $a->post_title ) . '</option>';
	}
	echo '<div class="kago-grid">';
	kago_field( __( 'Anime', 'kagofansub' ), $select . '</select>' );
	kago_field( __( 'Episode number', 'kagofansub' ), '<input type="number" min="0" class="widefat" name="kago_ep_number" value="' . esc_attr( kago_meta( $post->ID, 'ep_number' ) ) . '">' );
	kago_field( __( 'Episode title', 'kagofansub' ), '<input type="text" class="widefat" name="kago_ep_title" value="' . esc_attr( kago_meta( $post->ID, 'ep_title' ) ) . '">' );
	kago_field( __( 'Air date', 'kagofansub' ), '<input type="date" class="widefat" name="kago_ep_date" value="' . esc_attr( kago_meta( $post->ID, 'ep_date' ) ) . '">' );
	kago_media_field( 'episode_image_id', __( 'Episode image (optional)', 'kagofansub' ), (int) kago_meta( $post->ID, 'episode_image_id', 0 ) );
	echo '</div><p class="description">' . esc_html__( 'Leave the title empty to generate “Anime Episode 01” automatically.', 'kagofansub' ) . '</p>';
}

/* ---------- Auto title for episodes ---------- */

add_filter(
	'wp_insert_post_data',
	function ( $data ) {
		if ( 'episode' !== $data['post_type'] || ! isset( $_POST['kago_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kago_meta_nonce'] ) ), 'kago_save_meta' ) ) {
			return $data;
		}
		$title = trim( $data['post_title'] );
		if ( '' === $title || 'Auto Draft' === $title ) {
			$anime_id = isset( $_POST['kago_anime_id'] ) ? absint( $_POST['kago_anime_id'] ) : 0;
			$number   = isset( $_POST['kago_ep_number'] ) && '' !== $_POST['kago_ep_number'] ? absint( $_POST['kago_ep_number'] ) : null;
			if ( $anime_id && null !== $number ) {
				$data['post_title'] = get_the_title( $anime_id ) . ' ' . kago_ep_label( $number );
			}
		}
		return $data;
	}
);

/* ---------- Save ---------- */

add_action(
	'save_post',
	function ( $post_id, $post ) {
		if ( ! isset( $_POST['kago_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kago_meta_nonce'] ) ), 'kago_save_meta' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( in_array( $post->post_type, array( 'anime', 'movie' ), true ) ) {
			$in = isset( $_POST['kago'] ) && is_array( $_POST['kago'] ) ? wp_unslash( $_POST['kago'] ) : array();
			$text = array( 'title_jp', 'aliases', 'duration', 'quality' );
			foreach ( $text as $k ) {
				update_post_meta( $post_id, '_kago_' . $k, isset( $in[ $k ] ) ? sanitize_text_field( $in[ $k ] ) : '' );
			}
			foreach ( array( 'poster_url', 'banner_url' ) as $k ) {
				update_post_meta( $post_id, '_kago_' . $k, isset( $in[ $k ] ) ? esc_url_raw( $in[ $k ] ) : '' );
			}
			foreach ( array( 'poster_id', 'banner_id', 'total_episodes', 'mal_id' ) as $k ) {
				update_post_meta( $post_id, '_kago_' . $k, isset( $in[ $k ] ) && '' !== $in[ $k ] ? absint( $in[ $k ] ) : '' );
			}
			$score = isset( $in['score'] ) && '' !== $in['score'] ? min( 10, max( 0, (float) $in['score'] ) ) : '';
			update_post_meta( $post_id, '_kago_score', $score );
			$date = isset( $in['release_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $in['release_date'] ) ? $in['release_date'] : '';
			update_post_meta( $post_id, '_kago_release_date', $date );

			/* Keep featured image in sync with the chosen poster. */
			$poster_id = (int) kago_meta( $post_id, 'poster_id', 0 );
			if ( $poster_id && ! has_post_thumbnail( $post_id ) ) {
				set_post_thumbnail( $post_id, $poster_id );
			}

			/* Cast. */
			$cast = array();
			if ( isset( $_POST['kago_cast'] ) && is_array( $_POST['kago_cast'] ) ) {
				foreach ( wp_unslash( $_POST['kago_cast'] ) as $row ) {
					if ( ! is_array( $row ) || '' === trim( (string) ( $row['character'] ?? '' ) ) ) {
						continue;
					}
					$cast[] = array(
						'character'       => sanitize_text_field( $row['character'] ),
						'role'            => sanitize_text_field( $row['role'] ?? '' ),
						'character_img'   => esc_url_raw( $row['character_img'] ?? '' ),
						'voice_actor'     => sanitize_text_field( $row['voice_actor'] ?? '' ),
						'voice_actor_img' => esc_url_raw( $row['voice_actor_img'] ?? '' ),
					);
				}
			}
			update_post_meta( $post_id, '_kago_cast', $cast );
		}

		if ( in_array( $post->post_type, array( 'movie', 'episode' ), true ) ) {
			kago_save_downloads( $post_id );
		}

		if ( 'episode' === $post->post_type ) {
			$in = isset( $_POST['kago'] ) && is_array( $_POST['kago'] ) ? wp_unslash( $_POST['kago'] ) : array();
			update_post_meta( $post_id, '_kago_episode_image_id', isset( $in['episode_image_id'] ) && '' !== $in['episode_image_id'] ? absint( $in['episode_image_id'] ) : '' );
			$anime_id = isset( $_POST['kago_anime_id'] ) ? absint( $_POST['kago_anime_id'] ) : 0;
			update_post_meta( $post_id, '_kago_anime_id', $anime_id );
			$number = isset( $_POST['kago_ep_number'] ) && '' !== $_POST['kago_ep_number'] ? absint( $_POST['kago_ep_number'] ) : '';
			update_post_meta( $post_id, '_kago_ep_number', $number );
			$ep_title = isset( $_POST['kago_ep_title'] ) ? sanitize_text_field( wp_unslash( $_POST['kago_ep_title'] ) ) : '';
			update_post_meta( $post_id, '_kago_ep_title', $ep_title );
			$ep_date = isset( $_POST['kago_ep_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', sanitize_text_field( wp_unslash( $_POST['kago_ep_date'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['kago_ep_date'] ) ) : '';
			update_post_meta( $post_id, '_kago_ep_date', $ep_date );
			if ( $anime_id && 'publish' === $post->post_status ) {
				kago_refresh_anime_from_episodes( $anime_id );
			}
		}
	},
	10,
	2
);

function kago_save_downloads( $post_id ) {
	if ( empty( $_POST['kago_dl_groups_present'] ) ) {
		return;
	}
	$submitted = isset( $_POST['kago_dl_groups'] ) && is_array( $_POST['kago_dl_groups'] ) ? wp_unslash( $_POST['kago_dl_groups'] ) : array();
	$qualities = array();
	$downloads = array();
	foreach ( $submitted as $group ) {
		if ( ! is_array( $group ) ) {
			continue;
		}
		$quality_value = isset( $group['quality'] ) && is_scalar( $group['quality'] ) ? (string) $group['quality'] : '';
		$quality = sanitize_text_field( $quality_value );
		if ( '' === $quality || in_array( $quality, $qualities, true ) ) {
			continue;
		}
		$qualities[] = $quality;
		$downloads[ $quality ] = array();
		$host_rows = isset( $group['hosts'] ) && is_array( $group['hosts'] ) ? $group['hosts'] : array();
		foreach ( $host_rows as $host_row ) {
			if ( ! is_array( $host_row ) ) {
				continue;
			}
			$host_value = isset( $host_row['name'] ) && is_scalar( $host_row['name'] ) ? (string) $host_row['name'] : '';
			$host = sanitize_text_field( $host_value );
			if ( '' === $host ) {
				continue;
			}
			$url_value = isset( $host_row['url'] ) && is_scalar( $host_row['url'] ) ? (string) $host_row['url'] : '';
			$url = esc_url_raw( $url_value );
			$downloads[ $quality ][ $host ] = $url;
		}
	}
	update_post_meta( $post_id, '_kago_download_qualities', $qualities );
	update_post_meta( $post_id, '_kago_downloads', $downloads );
	update_post_meta( $post_id, '_kago_download_groups_v2', 1 );
	delete_post_meta( $post_id, '_kago_download_hosts' );
}

/** Latest episode number + updated time on the parent anime. */
function kago_refresh_anime_from_episodes( $anime_id ) {
	$latest = kago_latest_episode( $anime_id );
	if ( $latest ) {
		update_post_meta( $anime_id, '_kago_latest_episode', (int) kago_meta( $latest->ID, 'ep_number', 0 ) );
		update_post_meta( $anime_id, '_kago_updated', time() );
	}
}

/* ---------- Automatic year & season terms ---------- */

function kago_ensure_year_term( $year ) {
	$year = (string) $year;
	$term = term_exists( $year, 'release_year' );
	if ( ! $term ) {
		$term = wp_insert_term( $year, 'release_year' );
	}
	return is_wp_error( $term ) ? 0 : (int) ( is_array( $term ) ? $term['term_id'] : $term );
}

function kago_auto_terms( $post_id ) {
	$date = kago_meta( $post_id, 'release_date', '' );
	if ( ! $date ) {
		return;
	}
	$map = array(
		'release_year' => kago_ensure_year_term( gmdate( 'Y', strtotime( $date ) ) ),
		'anime_season' => kago_ensure_season_term( kago_season_from_date( $date ), (int) gmdate( 'Y', strtotime( $date ) ) ),
	);
	foreach ( $map as $tax => $term_id ) {
		if ( ! $term_id ) {
			continue;
		}
		$current = wp_get_object_terms( $post_id, $tax, array( 'fields' => 'ids' ) );
		$current = is_wp_error( $current ) ? array() : array_map( 'intval', $current );
		$auto    = (int) get_post_meta( $post_id, '_kago_auto_' . $tax, true );
		if ( ! $current || ( $auto && in_array( $auto, $current, true ) ) ) {
			wp_set_object_terms( $post_id, array( $term_id ), $tax );
			update_post_meta( $post_id, '_kago_auto_' . $tax, $term_id );
		}
	}
}

add_action(
	'save_post',
	function ( $post_id, $post ) {
		if ( in_array( $post->post_type, array( 'anime', 'movie' ), true ) && ! wp_is_post_revision( $post_id ) && ! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			kago_auto_terms( $post_id );
		}
	},
	20,
	2
);
