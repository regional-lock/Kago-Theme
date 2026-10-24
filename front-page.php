<?php
/**
 * Home page.
 *
 * @package KagoFansub
 */

get_header();

$kago_count = kago_opt_int( 'kago_home_count', 12 );

$kago_hero_count = kago_opt_int( 'kago_hero_count', 5 );
$kago_hero_ids   = kago_weekly_anime_ids( $kago_hero_count );
if ( $kago_hero_ids ) {
	$kago_hero = new WP_Query(
		array(
			'post_type'      => 'anime',
			'post__in'       => $kago_hero_ids,
			'orderby'        => 'post__in',
			'posts_per_page' => count( $kago_hero_ids ),
			'no_found_rows'  => true,
		)
	);
} else {
	$kago_hero = new WP_Query( array( 'post_type' => 'anime', 'posts_per_page' => min( 4, $kago_hero_count ), 'no_found_rows' => true ) );
}

$kago_updates = new WP_Query(
	array(
		'post_type'           => 'anime',
		'posts_per_page'      => $kago_count,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'meta_query'          => array(
			'relation' => 'OR',
			'upd'      => array( 'key' => '_kago_updated', 'type' => 'NUMERIC', 'compare' => 'EXISTS' ),
			'noupd'    => array( 'key' => '_kago_updated', 'compare' => 'NOT EXISTS' ),
		),
		'orderby'             => array( 'upd' => 'DESC', 'date' => 'DESC' ),
	)
);
$kago_series = new WP_Query( array( 'post_type' => 'anime', 'posts_per_page' => $kago_count, 'no_found_rows' => true, 'ignore_sticky_posts' => true ) );
$kago_movies = new WP_Query( array( 'post_type' => 'movie', 'posts_per_page' => $kago_count, 'no_found_rows' => true, 'ignore_sticky_posts' => true ) );
?>

<?php if ( $kago_hero->have_posts() ) : ?>
<div class="hero" id="hero">
	<?php
	$kago_i = 0;
	while ( $kago_hero->have_posts() ) :
		$kago_hero->the_post();
		$kago_bg = "--bgimg:linear-gradient(90deg,#0a0f1e 5%,#0a0f1eb0 45%,transparent 85%),url('" . esc_url( kago_banner_url( get_the_ID() ) ) . "') center/cover no-repeat";
		?>
		<div class="slide<?php echo 0 === $kago_i ? ' on' : ''; ?>" style="<?php echo esc_attr( $kago_bg ); ?>">
			<div class="wrap"><span class="badge"><?php esc_html_e( 'FEATURED ANIME', 'kagofansub' ); ?></span>
				<h1><a class="slide-title-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
				<?php if ( kago_meta( get_the_ID(), 'title_jp' ) ) : ?><div class="jp"><?php echo esc_html( kago_meta( get_the_ID(), 'title_jp' ) ); ?></div><?php endif; ?>
				<div class="tags"><?php echo kago_genre_links( get_the_ID(), 3 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<p><?php echo esc_html( wp_trim_words( kago_synopsis( get_the_ID() ), 32, '…' ) ); ?></p>
				<a class="btn g" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View Details', 'kagofansub' ); ?></a>
			</div>
		</div>
		<?php
		++$kago_i;
	endwhile;
	wp_reset_postdata();
	?>
</div>
<?php endif; ?>

<div class="wrap">
	<div class="main">
		<div>
			<section id="latest">
				<div class="sh">
					<h2><span class="badge-ico"><span class="ico ico-grid"></span></span><?php esc_html_e( 'Latest Updates', 'kagofansub' ); ?></h2>
					<div class="arr"><button id="prev" type="button" aria-label="<?php esc_attr_e( 'Previous', 'kagofansub' ); ?>"><span class="ico ico-chevron-left"></span></button><button id="next" type="button" aria-label="<?php esc_attr_e( 'Next', 'kagofansub' ); ?>"><span class="ico ico-chevron-right"></span></button></div>
				</div>
				<div class="row">
					<?php
					while ( $kago_updates->have_posts() ) {
						$kago_updates->the_post();
						get_template_part( 'template-parts/card-home', null, array( 'episode' => true ) );
					}
					wp_reset_postdata();
					?>
				</div>
				<div class="mobile-carousel-guide" hidden>
					<span><?php esc_html_e( 'Swipe to see more', 'kagofansub' ); ?></span>
					<span class="mobile-carousel-track" role="progressbar" aria-label="<?php esc_attr_e( 'Latest Updates carousel position', 'kagofansub' ); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
						<span class="mobile-carousel-progress"></span>
					</span>
				</div>
			</section>

			<section>
				<div class="sh">
					<h2><span class="badge-ico"><span class="ico ico-star"></span></span><?php esc_html_e( 'Latest Series', 'kagofansub' ); ?></h2>
				</div>
				<div class="row" id="season">
					<?php
					while ( $kago_series->have_posts() ) {
						$kago_series->the_post();
						get_template_part( 'template-parts/card-home', null, array( 'poster_class' => 'season-anime-link', 'title_class' => 'season-anime-link' ) );
					}
					wp_reset_postdata();
					?>
				</div>
				<div class="mobile-carousel-guide" hidden>
					<span><?php esc_html_e( 'Swipe to see more', 'kagofansub' ); ?></span>
					<span class="mobile-carousel-track" role="progressbar" aria-label="<?php esc_attr_e( 'Latest Series carousel position', 'kagofansub' ); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
						<span class="mobile-carousel-progress"></span>
					</span>
				</div>
			</section>

			<section id="movie">
				<div class="sh">
					<h2><span class="badge-ico"><span class="ico ico-movie"></span></span><?php esc_html_e( 'Latest Movies', 'kagofansub' ); ?></h2>
					<a class="more" href="<?php echo esc_url( get_post_type_archive_link( 'movie' ) ); ?>"><?php esc_html_e( 'View All', 'kagofansub' ); ?> <span class="ico ico-chevron-right"></span></a>
				</div>
				<div class="row">
					<?php
					while ( $kago_movies->have_posts() ) {
						$kago_movies->the_post();
						get_template_part( 'template-parts/card-home' );
					}
					wp_reset_postdata();
					?>
				</div>
			</section>
		</div>

		<?php get_sidebar(); ?>
	</div>
</div>

<?php get_footer(); ?>
