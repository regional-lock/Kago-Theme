<?php
/**
 * Single movie.
 *
 * @package KagoFansub
 */

get_header();
the_post();
?>
<main class="layout">
	<div class="main-col">
		<div class="page-heading">
			<div class="breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'kagofansub' ); ?></a> /
				<a href="<?php echo esc_url( get_post_type_archive_link( 'movie' ) ); ?>"><?php esc_html_e( 'Movies', 'kagofansub' ); ?></a> /
				<?php the_title(); ?>
			</div>
		</div>

		<?php get_template_part( 'template-parts/detail-main' ); ?>

		<section class="content-card download-box">
			<h2><?php echo esc_html( sprintf( __( 'Download %s with Indonesian Subtitles', 'kagofansub' ), get_the_title() ) ); ?></h2>
			<?php kago_render_download_rows( get_the_ID() ); ?>
		</section>

		<?php get_template_part( 'template-parts/recommended' ); ?>
		<?php get_template_part( 'template-parts/comments-block' ); ?>
	</div>
	<?php get_sidebar(); ?>
</main>
<?php get_footer(); ?>
