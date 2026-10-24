<?php
/**
 * Movie list (alphabetical).
 *
 * @package KagoFansub
 */

get_header();
get_template_part(
	'template-parts/list-view',
	null,
	array(
		'heading'    => __( 'Movie List', 'kagofansub' ),
		'noun'       => 'movie',
		'show_year'  => true,
		'post_types' => array( 'movie' ),
	)
);
get_footer();
