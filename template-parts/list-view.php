<?php
/**
 * Alphabetical text list (anime / movie / season archives).
 *
 * Args: heading (string), noun (string), show_year (bool), post_types (array), term (WP_Term|null).
 *
 * @package KagoFansub
 */

$kago_args = wp_parse_args( isset( $args ) ? $args : array(), array( 'heading' => '', 'noun' => 'anime', 'show_year' => false, 'post_types' => array( 'anime' ), 'term' => null ) );
$kago_current_letter = strtoupper( (string) get_query_var( 'letter' ) );
$kago_available      = kago_available_letters( $kago_args['post_types'], $kago_args['term'] );
$kago_base           = $kago_args['term'] ? get_term_link( $kago_args['term'] ) : get_post_type_archive_link( $kago_args['post_types'][0] );
global $wp_query;
$kago_total = (int) $wp_query->found_posts;
$kago_noun  = ( 'movie' === $kago_args['noun'] ) ? ( 1 === $kago_total ? 'movie' : 'movies' ) : 'anime';

$kago_groups = array();
foreach ( $wp_query->posts as $kago_post ) {
	$kago_letter = strtoupper( substr( $kago_post->post_title, 0, 1 ) );
	$kago_groups[ $kago_letter ][] = $kago_post;
}
?>
<main class="wrap anime-list-page">
	<div class="page-heading">
		<h1><?php echo esc_html( $kago_args['heading'] ); ?></h1>
	</div>
	<div class="alphabet-filter" id="alphabet-filter" aria-label="<?php esc_attr_e( 'Filter by letter', 'kagofansub' ); ?>">
		<a class="alphabet-button<?php echo '' === $kago_current_letter ? ' active' : ''; ?>" href="<?php echo esc_url( $kago_base ); ?>"><?php esc_html_e( 'All', 'kagofansub' ); ?></a>
		<?php foreach ( range( 'A', 'Z' ) as $kago_l ) : ?>
			<?php if ( in_array( $kago_l, $kago_available, true ) ) : ?>
				<a class="alphabet-button<?php echo $kago_l === $kago_current_letter ? ' active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'letter', $kago_l, $kago_base ) ); ?>"><?php echo esc_html( $kago_l ); ?></a>
			<?php else : ?>
				<button class="alphabet-button" type="button" disabled><?php echo esc_html( $kago_l ); ?></button>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<div class="anime-list-toolbar">
		<span id="anime-count" aria-live="polite"><?php echo esc_html( $kago_total . ' ' . $kago_noun ); ?></span>
	</div>
	<div class="anime-text-list" id="anime-results">
		<?php foreach ( $kago_groups as $kago_letter => $kago_posts ) : ?>
			<section class="anime-letter-group" aria-labelledby="letter-<?php echo esc_attr( $kago_letter ); ?>">
				<h2 id="letter-<?php echo esc_attr( $kago_letter ); ?>"><?php echo esc_html( $kago_letter ); ?></h2>
				<ul>
					<?php foreach ( $kago_posts as $kago_post ) : ?>
						<?php
						$kago_genres = implode( ', ', kago_genre_names( $kago_post->ID ) );
						if ( $kago_args['show_year'] ) {
							$kago_genres = trim( kago_year( $kago_post->ID ) . ' · ' . $kago_genres, ' ·' );
						}
						?>
						<li>
							<a class="anime-text-link" href="<?php echo esc_url( get_permalink( $kago_post ) ); ?>">
								<span class="anime-text-title"><?php echo esc_html( get_the_title( $kago_post ) ); ?></span>
								<span class="anime-text-genres"><?php echo esc_html( $kago_genres ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>
	</div>
	<?php kago_pagination( null, 'list-pagination' ); ?>
	<?php if ( ! $kago_total ) : ?>
		<p class="anime-list-empty" id="anime-empty"><?php echo esc_html( 'movie' === $kago_args['noun'] ? __( 'No movies found.', 'kagofansub' ) : __( 'No anime found.', 'kagofansub' ) ); ?></p>
	<?php endif; ?>
</main>
