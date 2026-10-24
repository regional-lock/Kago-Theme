<?php
/**
 * Anime list (alphabetical).
 *
 * @package KagoFansub
 */

get_header();
get_template_part(
	'template-parts/list-view',
	null,
	array(
		'heading'    => __( 'Anime List', 'kagofansub' ),
		'noun'       => 'anime',
		'post_types' => array( 'anime' ),
	)
);
get_footer();
