<?php
/**
 * Styles and scripts.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'kago-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', array(), null );
		$main   = KAGO_DIR . '/assets/css/main.css';
		$mobile = KAGO_DIR . '/assets/css/mobile.css';
		wp_enqueue_style( 'kago-main', KAGO_URI . '/assets/css/main.css', array( 'kago-font' ), file_exists( $main ) ? filemtime( $main ) : KAGO_VERSION );
		wp_enqueue_style( 'kago-mobile', KAGO_URI . '/assets/css/mobile.css', array( 'kago-main' ), file_exists( $mobile ) ? filemtime( $mobile ) : KAGO_VERSION );

		wp_enqueue_script( 'kago-main', KAGO_URI . '/assets/js/main.js', array(), KAGO_VERSION, true );
		wp_localize_script( 'kago-main', 'kagoData', array( 'homeUrl' => home_url( '/' ) ) );
		wp_enqueue_script( 'kago-mobile', KAGO_URI . '/assets/js/mobile.js', array( 'kago-main' ), KAGO_VERSION, true );
		if ( is_front_page() ) {
			wp_enqueue_script( 'kago-home', KAGO_URI . '/assets/js/home.js', array(), KAGO_VERSION, true );
		}
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
);

add_filter(
	'wp_resource_hints',
	function ( $urls, $relation ) {
		if ( 'preconnect' === $relation ) {
			$urls[] = 'https://fonts.googleapis.com';
			$urls[] = array(
				'href'        => 'https://fonts.gstatic.com',
				'crossorigin' => 'anonymous',
			);
		}
		return $urls;
	},
	10,
	2
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && in_array( $screen->post_type, array( 'anime', 'movie', 'episode' ), true ) ) {
			wp_enqueue_media();
			$admin_css = KAGO_DIR . '/assets/css/admin.css';
			$admin_js  = KAGO_DIR . '/assets/js/admin.js';
			wp_enqueue_style( 'kago-admin', KAGO_URI . '/assets/css/admin.css', array(), file_exists( $admin_css ) ? filemtime( $admin_css ) : KAGO_VERSION );
			wp_enqueue_script( 'kago-admin', KAGO_URI . '/assets/js/admin.js', array( 'jquery' ), file_exists( $admin_js ) ? filemtime( $admin_js ) : KAGO_VERSION, true );
		}
		if ( false !== strpos( $hook, 'kago-grabber' ) ) {
			$admin_css = KAGO_DIR . '/assets/css/admin.css';
			$grabber_js = KAGO_DIR . '/assets/js/grabber.js';
			wp_enqueue_style( 'kago-admin', KAGO_URI . '/assets/css/admin.css', array(), file_exists( $admin_css ) ? filemtime( $admin_css ) : KAGO_VERSION );
			wp_enqueue_script( 'kago-grabber', KAGO_URI . '/assets/js/grabber.js', array( 'jquery' ), file_exists( $grabber_js ) ? filemtime( $grabber_js ) : KAGO_VERSION, true );
			wp_localize_script(
				'kago-grabber',
				'kagoGrabber',
				array(
					'ajax'  => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'kago_grabber' ),
				)
			);
		}
	}
);
