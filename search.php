<?php
/**
 * Search results.
 *
 * @package KagoFansub
 */

get_header();
global $wp_query;
$kago_q = get_search_query();
?>
<main class="wrap genre-page">
	<h1 id="search-title"><?php echo $kago_q ? esc_html( sprintf( __( 'Search Results for “%s”', 'kagofansub' ), $kago_q ) ) : esc_html__( 'Search Anime', 'kagofansub' ); ?></h1>
	<p id="search-description">
		<?php
		if ( ! $kago_q ) {
			esc_html_e( 'Enter a title, genre, or studio to search.', 'kagofansub' );
		} elseif ( $wp_query->found_posts ) {
			echo esc_html( sprintf( _n( '%d title found.', '%d titles found.', $wp_query->found_posts, 'kagofansub' ), $wp_query->found_posts ) );
		} else {
			echo esc_html( sprintf( __( 'No anime found for “%s”.', 'kagofansub' ), $kago_q ) );
		}
		?>
	</p>
	<div class="row six" id="search-results">
		<?php
		while ( have_posts() ) {
			the_post();
			get_template_part( 'template-parts/card-grid' );
		}
		?>
	</div>
	<?php kago_pagination( null, 'search-pagination' ); ?>
</main>
<?php get_footer(); ?>
