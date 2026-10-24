<?php
/**
 * Sidebar: popular posts, genres, seasons.
 *
 * @package KagoFansub
 */

$kago_popular = new WP_Query(
	array(
		'post_type'           => array( 'anime', 'movie' ),
		'posts_per_page'      => kago_opt_int( 'kago_popular_count', 5 ),
		'meta_key'            => '_kago_views',
		'orderby'             => 'meta_value_num',
		'order'               => 'DESC',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	)
);
if ( ! $kago_popular->have_posts() ) {
	$kago_popular = new WP_Query( array( 'post_type' => array( 'anime', 'movie' ), 'posts_per_page' => kago_opt_int( 'kago_popular_count', 5 ), 'no_found_rows' => true ) );
}
$kago_genres  = get_terms( array( 'taxonomy' => 'genre', 'hide_empty' => false, 'orderby' => 'name', 'number' => 40 ) );
$kago_seasons = get_terms( array( 'taxonomy' => 'anime_season', 'hide_empty' => false, 'number' => 0 ) );
if ( ! is_wp_error( $kago_seasons ) ) {
	$kago_order = array( 'winter' => 1, 'spring' => 2, 'summer' => 3, 'fall' => 4 );
	$kago_year  = (string) gmdate( 'Y' );
	$kago_seasons = array_filter( $kago_seasons, function ( $t ) use ( $kago_year ) {
		return substr( $t->slug, -4 ) === $kago_year;
	} );
	usort( $kago_seasons, function ( $a, $b ) use ( $kago_order ) {
		return ( $kago_order[ strtok( $a->slug, '-' ) ] ?? 9 ) <=> ( $kago_order[ strtok( $b->slug, '-' ) ] ?? 9 );
	} );
}
?>
<aside class="sidebar">
	<div class="box">
		<div class="sh" style="margin-bottom:0">
			<h2><span class="badge-ico"><span class="ico ico-post"></span></span><?php esc_html_e( 'Popular Posts', 'kagofansub' ); ?></h2>
		</div>
		<ul class="pp">
			<?php
			$kago_rank = 0;
			while ( $kago_popular->have_posts() ) :
				$kago_popular->the_post();
				++$kago_rank;
				?>
				<li><span class="rank"><?php echo (int) $kago_rank; ?></span>
					<div class="th" style="<?php echo esc_attr( kago_poster_style( get_the_ID(), 'kago-thumb' ) ); ?>"></div>
					<div class="c">
						<div class="tp"><?php echo esc_html( kago_type_label( get_the_ID() ) ); ?></div>
						<h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
						<?php if ( kago_score( get_the_ID() ) ) : ?>
							<div class="rate"><span class="ico ico-star"></span> <?php echo esc_html( kago_score( get_the_ID() ) ); ?></div>
						<?php endif; ?>
						<div class="genre"><?php echo esc_html( implode( ', ', kago_genre_names( get_the_ID() ) ) ); ?></div>
					</div>
				</li>
			<?php endwhile; wp_reset_postdata(); ?>
		</ul>
	</div>

	<?php if ( $kago_genres && ! is_wp_error( $kago_genres ) ) : ?>
	<section class="box genre-box">
		<div class="sh" style="margin-bottom:12px">
			<h2><span class="badge-ico"><span class="ico ico-grid"></span></span><?php esc_html_e( 'Genres', 'kagofansub' ); ?></h2>
		</div>
		<div class="genre-cloud">
			<?php foreach ( $kago_genres as $kago_term ) : ?>
				<a href="<?php echo esc_url( get_term_link( $kago_term ) ); ?>"><?php echo esc_html( $kago_term->name ); ?></a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $kago_seasons ) : ?>
	<section class="box genre-box season-box">
		<div class="sh" style="margin-bottom:12px">
			<h2><span class="badge-ico"><span class="ico ico-grid"></span></span><?php esc_html_e( 'Anime Seasons', 'kagofansub' ); ?></h2>
		</div>
		<div class="genre-cloud season-cloud">
			<?php foreach ( $kago_seasons as $kago_term ) : ?>
				<a href="<?php echo esc_url( get_term_link( $kago_term ) ); ?>"><?php echo esc_html( $kago_term->name ); ?></a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php dynamic_sidebar( 'sidebar-extra' ); ?>
</aside>
