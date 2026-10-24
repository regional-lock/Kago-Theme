<?php
/**
 * Customizer options.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section( 'kago', array( 'title' => __( 'Kago Theme', 'kagofansub' ), 'priority' => 30 ) );
		$fields = array(
			'kago_brand_main'   => array( 'KAGO', __( 'Brand text (white part)', 'kagofansub' ), 'text', 'sanitize_text_field' ),
			'kago_brand_accent' => array( 'FANSUB', __( 'Brand text (accent part)', 'kagofansub' ), 'text', 'sanitize_text_field' ),
			'kago_copyright'    => array( '© {year} Caduceus Fansub. All rights reserved.', __( 'Footer text ({year} = current year)', 'kagofansub' ), 'text', 'wp_kses_post' ),
			'kago_hero_count'   => array( 5, __( 'Home slider: number of slides', 'kagofansub' ), 'number', 'absint' ),
			'kago_home_count'   => array( 12, __( 'Home sections: items per row', 'kagofansub' ), 'number', 'absint' ),
			'kago_popular_count' => array( 5, __( 'Popular posts: items', 'kagofansub' ), 'number', 'absint' ),
		);
		foreach ( $fields as $id => $f ) {
			$wp_customize->add_setting( $id, array( 'default' => $f[0], 'sanitize_callback' => $f[3] ) );
			$wp_customize->add_control( $id, array( 'section' => 'kago', 'label' => $f[1], 'type' => $f[2] ) );
		}
		$wp_customize->add_setting( 'kago_comments', array( 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
		$wp_customize->add_control( 'kago_comments', array( 'section' => 'kago', 'label' => __( 'Show comments on anime, movie and episode pages', 'kagofansub' ), 'type' => 'checkbox' ) );
	}
);

function kago_opt_int( $key, $default ) {
	$v = (int) get_theme_mod( $key, $default );
	return $v > 0 ? $v : $default;
}
