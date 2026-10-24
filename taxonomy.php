<?php
/**
 * Genre / studio / year / rating / source / type / status archives (card grid).
 *
 * @package KagoFansub
 */

get_header();
$kago_term = get_queried_object();
$kago_tax  = $kago_term ? $kago_term->taxonomy : '';
if ( 'anime_source' === $kago_tax ) {
	$kago_title = sprintf( __( 'Anime based on %s', 'kagofansub' ), $kago_term->name );
} elseif ( 'age_rating' === $kago_tax ) {
	$kago_title = sprintf( __( 'Rated %s', 'kagofansub' ), $kago_term->name );
} else {
	$kago_title = sprintf( __( '%s Anime', 'kagofansub' ), $kago_term ? $kago_term->name : '' );
}
global $wp_query;
?>
<main class="wrap genre-page">
	<h1 id="genre-title"><?php echo esc_html( $kago_title ); ?></h1>
	<p id="genre-description"><?php echo esc_html( $wp_query->found_posts ? ( $kago_term->description ? $kago_term->description : ( 'genre' === $kago_tax ? sprintf( __( 'Anime in the %s genre.', 'kagofansub' ), $kago_term->name ) : sprintf( __( 'Anime in the %s archive.', 'kagofansub' ), $kago_term->name ) ) ) : ( 'genre' === $kago_tax ? sprintf( __( 'No anime found in the %s genre.', 'kagofansub' ), $kago_term->name ) : sprintf( __( 'No anime found in %s.', 'kagofansub' ), $kago_term->name ) ) ); ?></p>
	<div class="row six" id="genre-results">
		<?php
		while ( have_posts() ) {
			the_post();
			get_template_part( 'template-parts/card-grid' );
		}
		?>
	</div>
	<?php kago_pagination( null, 'genre-pagination' ); ?>
</main>
<?php get_footer(); ?>
