<?php
/**
 * Kago Fansub theme bootstrap.
 *
 * @package KagoFansub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KAGO_VERSION', '1.0.0' );
define( 'KAGO_DIR', get_template_directory() );
define( 'KAGO_URI', get_template_directory_uri() );

$kago_includes = array(
	'helpers',
	'setup',
	'enqueue',
	'post-types',
	'taxonomies',
	'meta-boxes',
	'admin',
	'query',
	'views',
	'seo',
	'customizer',
	'grabber',
	'demo',
);

foreach ( $kago_includes as $kago_file ) {
	require_once KAGO_DIR . '/inc/' . $kago_file . '.php';
}
unset( $kago_includes, $kago_file );
