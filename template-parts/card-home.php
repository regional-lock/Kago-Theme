<?php
/**
 * Home card (.card with poster, pills, title, year, genres).
 *
 * Args: episode (bool), poster_class, title_class.
 *
 * @package KagoFansub
 */

$kago_args    = wp_parse_args( isset( $args ) ? $args : array(), array( 'episode' => false, 'poster_class' => 'index-poster-link', 'title_class' => 'index-title-link' ) );
$kago_id      = get_the_ID();
$kago_poster_link = get_permalink();
$kago_ep_label = '';
if ( $kago_args['episode'] ) {
	$kago_latest = kago_latest_episode( $kago_id );
	if ( $kago_latest ) {
		$kago_poster_link = get_permalink( $kago_latest );
		$kago_ep_label    = kago_ep_label( kago_meta( $kago_latest->ID, 'ep_number', 0 ) );
	}
}
$kago_quality = 'movie' === get_post_type() ? kago_meta( $kago_id, 'quality', '' ) : '';
?>
<div class="card">
	<a class="<?php echo esc_attr( $kago_args['poster_class'] ); ?>" href="<?php echo esc_url( $kago_poster_link ); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>"><div class="poster" style="<?php echo esc_attr( kago_poster_style( $kago_id ) ); ?>">
		<div class="pills"><?php if ( kago_score( $kago_id ) ) : ?><em class="rate"><?php echo esc_html( kago_score( $kago_id ) ); ?></em><?php endif; ?><em class="kind"><?php echo esc_html( kago_type_label( $kago_id ) ); ?></em><?php if ( $kago_ep_label ) : ?><em><?php echo esc_html( $kago_ep_label ); ?></em><?php elseif ( $kago_quality ) : ?><em><?php echo esc_html( $kago_quality ); ?></em><?php endif; ?></div>
	</div></a>
<h3>
	<a class="<?php echo esc_attr( $kago_args['title_class'] ); ?>"
	   href="<?php the_permalink(); ?>">
		<?php echo esc_html( rtrim( get_the_title() ) ); ?>
	</a>
</h3>
	<div class="m"><?php echo esc_html( kago_year( $kago_id ) ); ?></div>
	<div class="g"><?php echo kago_genre_links( $kago_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
</div>
