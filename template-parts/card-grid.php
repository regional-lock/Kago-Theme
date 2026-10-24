<?php
/**
 * Grid card used by genre / taxonomy / search results.
 *
 * @package KagoFansub
 */

$kago_names = array_slice( kago_genre_names( get_the_ID() ), 0, 2 );
?>
<div class="card">
	<a class="genre-result-link" href="<?php the_permalink(); ?>"><div class="poster" style="<?php echo esc_attr( kago_poster_style( get_the_ID() ) ); ?>"></div><h3><?php the_title(); ?></h3></a>
	<div class="m"><?php echo esc_html( implode( ', ', $kago_names ) ); ?></div>
</div>
