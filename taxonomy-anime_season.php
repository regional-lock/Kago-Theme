<?php
/**
 * Season archive: same list layout as the anime list.
 *
 * @package KagoFansub
 */

get_header();
$kago_term = get_queried_object();
get_template_part(
	'template-parts/list-view',
	null,
	array(
		'heading'    => sprintf( __( '%s Anime', 'kagofansub' ), $kago_term->name ),
		'noun'       => 'anime',
		'post_types' => array( 'anime' ),
		'term'       => $kago_term,
	)
);
get_footer();
