<?php
/**
 * "Recommended For You": same genres first, then most viewed.
 *
 * @package KagoFansub
 */

$kago_current = get_the_ID();
if ( 'episode' === get_post_type() ) {
	$kago_current = (int) kago_meta( $kago_current, 'anime_id', 0 );
}
$kago_reco_args = array(
	'post_type'           => array( 'anime' ),
	'posts_per_page'      => 5,
	'post__not_in'        => array( $kago_current ),
	'no_found_rows'       => true,
	'ignore_sticky_posts' => true,
	'meta_key'            => '_kago_views',
	'orderby'             => 'meta_value_num',
	'order'               => 'DESC',
);
$kago_genre_ids = $kago_current ? wp_get_object_terms( $kago_current, 'genre', array( 'fields' => 'ids' ) ) : array();
if ( $kago_genre_ids && ! is_wp_error( $kago_genre_ids ) ) {
	$kago_reco_args['tax_query'] = array( array( 'taxonomy' => 'genre', 'field' => 'term_id', 'terms' => $kago_genre_ids ) );
}
$kago_reco = new WP_Query( $kago_reco_args );
if ( $kago_reco->post_count < 5 ) {
	/* Top up with the most viewed titles so the row is always full. */
	$kago_have = wp_list_pluck( $kago_reco->posts, 'ID' );
	$kago_fill = $kago_reco_args;
	unset( $kago_fill['tax_query'] );
	$kago_fill['post__not_in']    = array_merge( $kago_reco_args['post__not_in'], $kago_have );
	$kago_fill['posts_per_page']  = 5 - $kago_reco->post_count;
	$kago_more                    = new WP_Query( $kago_fill );
	if ( ! $kago_more->have_posts() ) {
		unset( $kago_fill['meta_key'] );
		$kago_fill['orderby'] = 'date';
		$kago_more            = new WP_Query( $kago_fill );
	}
	$kago_reco->posts      = array_merge( $kago_reco->posts, $kago_more->posts );
	$kago_reco->post_count = count( $kago_reco->posts );
}
if ( $kago_reco->have_posts() ) :
	?>
<section id="recommendation">
	<div class="sh">
		<h2><?php esc_html_e( 'Recommended For You', 'kagofansub' ); ?></h2>
		<a class="more" href="<?php echo esc_url( get_post_type_archive_link( 'anime' ) ); ?>"><?php esc_html_e( 'View All', 'kagofansub' ); ?> <span class="ico ico-chevron-right"></span></a>
	</div>
	<div class="row">
		<?php
		while ( $kago_reco->have_posts() ) {
			$kago_reco->the_post();
			get_template_part( 'template-parts/card-reco' );
		}
		wp_reset_postdata();
		?>
	</div>
</section>
<?php endif; ?>
