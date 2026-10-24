<?php
/**
 * Admin list tables: poster column, filters.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( array( 'anime', 'movie' ) as $kago_pt ) {
	add_filter(
		"manage_{$kago_pt}_posts_columns",
		function ( $cols ) {
			$new = array();
			foreach ( $cols as $key => $label ) {
				$new[ $key ] = $label;
				if ( 'cb' === $key ) {
					$new['kago_poster'] = __( 'Poster', 'kagofansub' );
				}
				if ( 'title' === $key ) {
					$new['kago_score'] = __( 'Score', 'kagofansub' );
				}
			}
			return $new;
		}
	);
	add_action(
		"manage_{$kago_pt}_posts_custom_column",
		function ( $col, $post_id ) {
			if ( 'kago_poster' === $col ) {
				$has = kago_image_from_meta( $post_id, 'poster', 'kago-thumb' ) || has_post_thumbnail( $post_id ) || kago_meta( $post_id, 'poster_url' );
				if ( $has ) {
					echo '<img src="' . esc_url( kago_poster_url( $post_id, 'kago-thumb' ) ) . '" alt="" style="width:40px;height:56px;object-fit:cover;border-radius:3px">';
				} else {
					echo '<span class="kago-no-poster" title="' . esc_attr__( 'No poster', 'kagofansub' ) . '"></span>';
				}
			}
			if ( 'kago_score' === $col ) {
				echo esc_html( kago_score( $post_id ) );
			}
		},
		10,
		2
	);
}
unset( $kago_pt );

add_action(
	'restrict_manage_posts',
	function ( $post_type ) {
		if ( ! in_array( $post_type, array( 'anime', 'movie' ), true ) ) {
			return;
		}
		foreach ( array( 'genre', 'studio', 'age_rating', 'release_year' ) as $tax ) {
			$obj = get_taxonomy( $tax );
			wp_dropdown_categories(
				array(
					'show_option_all' => sprintf( __( 'All %s', 'kagofansub' ), $obj->labels->name ),
					'taxonomy'        => $tax,
					'name'            => $tax,
					'value_field'     => 'slug',
					'selected'        => isset( $_GET[ $tax ] ) ? sanitize_title( wp_unslash( $_GET[ $tax ] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
					'hide_empty'      => false,
					'hierarchical'    => false,
				)
			);
		}
		$on = ! empty( $_GET['kago_noposter'] ); // phpcs:ignore WordPress.Security.NonceVerification
		echo '<label style="margin-left:6px"><input type="checkbox" name="kago_noposter" value="1"' . checked( $on, true, false ) . '> ' . esc_html__( 'No poster', 'kagofansub' ) . '</label>';
	}
);

add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || empty( $_GET['kago_noposter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$query->set(
			'meta_query',
			array(
				'relation' => 'AND',
				array( 'key' => '_kago_poster_id', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_thumbnail_id', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_kago_poster_url', 'compare' => 'NOT EXISTS' ),
			)
		);
	}
);
