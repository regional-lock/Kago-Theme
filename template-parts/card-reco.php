<?php
/**
 * Recommendation card (whole card is a link).
 *
 * @package KagoFansub
 */

$kago_id      = get_the_ID();
$kago_quality = kago_meta( $kago_id, 'quality', '' );
$kago_names   = array_slice( kago_genre_names( $kago_id ), 0, 2 );
?>
<a class="card" href="<?php the_permalink(); ?>">
	<div class="poster" style="<?php echo esc_attr( kago_poster_style( $kago_id ) ); ?>">
		<div class="pills"><?php if ( kago_score( $kago_id ) ) : ?><em class="rate"><?php echo esc_html( kago_score( $kago_id ) ); ?></em><?php endif; ?><em class="kind"><?php echo esc_html( kago_type_label( $kago_id ) ); ?></em><?php if ( $kago_quality ) : ?><em><?php echo esc_html( $kago_quality ); ?></em><?php endif; ?></div>
	</div>
	<h3><?php the_title(); ?></h3>
	<div class="m"><?php echo esc_html( kago_year( $kago_id ) ); ?></div>
	<div class="g"><?php foreach ( $kago_names as $kago_n ) : ?><span><?php echo esc_html( $kago_n ); ?></span><?php endforeach; ?></div>
</a>
