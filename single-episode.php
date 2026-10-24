<?php
/**
 * Single episode.
 *
 * @package KagoFansub
 */

get_header();
the_post();

$kago_id       = get_the_ID();
$kago_anime_id = (int) kago_meta( $kago_id, 'anime_id', 0 );
$kago_number   = (int) kago_meta( $kago_id, 'ep_number', 0 );
$kago_anime    = $kago_anime_id ? get_post( $kago_anime_id ) : null;
$kago_ep_name  = kago_ep_label( $kago_number );
$kago_heading  = $kago_anime ? get_the_title( $kago_anime ) . ' — ' . $kago_ep_name : get_the_title();
$kago_prev     = kago_adjacent_episode( $kago_id, -1 );
$kago_next     = kago_adjacent_episode( $kago_id, 1 );
$kago_image    = kago_image_from_meta( $kago_id, 'episode_image', 'large' );
if ( ! $kago_image ) {
	$kago_image = $kago_anime ? kago_poster_url( $kago_anime_id, 'large' ) : kago_poster_url( $kago_id, 'large' );
}
?>
<main class="layout">
	<div class="main-col">
		<div class="page-heading episode-page-heading">
			<div class="breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'kagofansub' ); ?></a> /
				<a href="<?php echo esc_url( get_post_type_archive_link( 'anime' ) ); ?>"><?php esc_html_e( 'Anime', 'kagofansub' ); ?></a> /
				<?php if ( $kago_anime ) : ?><a href="<?php echo esc_url( get_permalink( $kago_anime ) ); ?>"><?php echo esc_html( get_the_title( $kago_anime ) ); ?></a> /<?php endif; ?>
				<?php echo esc_html( $kago_ep_name ); ?>
			</div>
			<h1 data-episode-title><?php echo esc_html( $kago_heading ); ?></h1>
		</div>

		<figure class="player player-image">
			<img data-episode-image src="<?php echo esc_url( $kago_image ); ?>" alt="<?php echo esc_attr( $kago_heading ); ?>" loading="lazy">
		</figure>

		<?php if ( $kago_anime ) : ?>
		<div class="episode-nav detail-series-nav">
			<a data-detail-link2 href="<?php echo esc_url( get_permalink( $kago_anime ) ); ?>"><?php esc_html_e( 'Series Details', 'kagofansub' ); ?></a>
		</div>
		<?php endif; ?>

		<?php if ( '' !== trim( get_the_content() ) ) : ?>
		<section class="content-card"><?php the_content(); ?></section>
		<?php endif; ?>

		<section class="content-card download-box">
			<h2 data-download-title><?php echo esc_html( sprintf( __( 'Download %1$s %2$s Subtitle Indonesia', 'kagofansub' ), $kago_anime ? get_the_title( $kago_anime ) : get_the_title(), $kago_ep_name ) ); ?></h2>
			<?php kago_render_download_rows( $kago_id ); ?>
		</section>

		<section class="content-card">
			<h2><?php esc_html_e( 'More Episodes', 'kagofansub' ); ?></h2>
			<div class="episode-list" data-episode-grid>
				<?php if ( $kago_prev ) : ?>
					<?php $kago_prev_title = trim( (string) kago_meta( $kago_prev->ID, 'ep_title', '' ) ); ?>
					<a class="episode-link" href="<?php echo esc_url( get_permalink( $kago_prev ) ); ?>">
						<span><?php echo esc_html( kago_ep_label( kago_meta( $kago_prev->ID, 'ep_number', 0 ) ) ); ?></span>
						<?php if ( '' !== $kago_prev_title ) : ?><small>:&nbsp;&nbsp;<?php echo esc_html( $kago_prev_title ); ?></small><?php endif; ?>
					</a>
				<?php endif; ?>
				<?php if ( $kago_next ) : ?>
					<?php $kago_next_title = trim( (string) kago_meta( $kago_next->ID, 'ep_title', '' ) ); ?>
					<a class="episode-link" href="<?php echo esc_url( get_permalink( $kago_next ) ); ?>">
						<span><?php echo esc_html( kago_ep_label( kago_meta( $kago_next->ID, 'ep_number', 0 ) ) ); ?></span>
						<?php if ( '' !== $kago_next_title ) : ?><small>:&nbsp;&nbsp;<?php echo esc_html( $kago_next_title ); ?></small><?php endif; ?>
					</a>
				<?php endif; ?>
			</div>
		</section>

		<?php get_template_part( 'template-parts/recommended' ); ?>
		<?php get_template_part( 'template-parts/comments-block' ); ?>
	</div>
	<?php get_sidebar(); ?>
</main>
<?php get_footer(); ?>
