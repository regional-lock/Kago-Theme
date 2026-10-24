<?php
/**
 * Detail card + cast, shared by anime and movie singles.
 *
 * @package KagoFansub
 */

$kago_id   = get_the_ID();
$kago_eps  = (int) kago_meta( $kago_id, 'total_episodes', 0 );
$kago_rows = array(
	__( 'Status', 'kagofansub' )       => kago_term_name( $kago_id, 'anime_status' ),
	__( 'Score', 'kagofansub' )        => kago_score( $kago_id ),
	__( 'Episode', 'kagofansub' )      => ( 'anime' === get_post_type() && $kago_eps ) ? $kago_eps . ' Ep.' : '',
	__( 'Studio', 'kagofansub' )       => implode( ', ', kago_term_names( $kago_id, 'studio' ) ),
	__( 'Release date', 'kagofansub' ) => kago_release_date_label( $kago_id ),
	__( 'Rating', 'kagofansub' )       => kago_term_label( $kago_id, 'age_rating' ),
	__( 'Duration', 'kagofansub' )     => kago_meta( $kago_id, 'duration', '' ),
	__( 'Source', 'kagofansub' )       => kago_term_name( $kago_id, 'anime_source' ),
);
$kago_genres = get_the_terms( $kago_id, 'genre' );
$kago_cast   = get_post_meta( $kago_id, '_kago_cast', true );
$kago_syn    = kago_synopsis( $kago_id );
?>
<section class="content-card detail-layout">
	<img class="detail-poster" src="<?php echo esc_url( kago_poster_url( $kago_id, 'large' ) ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Poster %s', 'kagofansub' ), get_the_title() ) ); ?>" loading="lazy">

	<div class="detail-info">
		<h1><?php the_title(); ?></h1>
		<?php if ( kago_meta( $kago_id, 'title_jp' ) ) : ?>
			<p class="sub"><?php echo esc_html( kago_meta( $kago_id, 'title_jp' ) ); ?></p>
		<?php endif; ?>

		<?php if ( $kago_genres && ! is_wp_error( $kago_genres ) ) : ?>
			<div class="tags">
				<?php foreach ( $kago_genres as $kago_g ) : ?>
					<span><?php echo esc_html( $kago_g->name ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="meta-list">
			<?php foreach ( $kago_rows as $kago_label => $kago_value ) : ?>
				<?php if ( '' !== (string) $kago_value ) : ?>
					<div><?php echo esc_html( $kago_label ); ?>: <b><?php echo esc_html( $kago_value ); ?></b></div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>

		<?php if ( $kago_syn ) : ?>
			<p class="description" id="detail-synopsis"><?php echo esc_html( $kago_syn ); ?></p>
			<button class="synopsis-toggle" type="button" aria-expanded="false" aria-controls="detail-synopsis"><?php esc_html_e( 'See more', 'kagofansub' ); ?></button>
		<?php endif; ?>
	</div>
</section>

<?php if ( is_array( $kago_cast ) && $kago_cast ) : ?>
<section class="content-card" id="characters">
	<div class="section-title">
		<h2><?php esc_html_e( 'Characters & Voice Actors', 'kagofansub' ); ?></h2>
		<span class="see"><?php esc_html_e( 'Japanese cast', 'kagofansub' ); ?></span>
	</div>
	<div class="cast-grid">
		<?php foreach ( $kago_cast as $kago_c ) : ?>
			<div class="cast">
				<div class="cast-char">
					<div class="cast-photo"><?php if ( ! empty( $kago_c['character_img'] ) ) : ?><img src="<?php echo esc_url( $kago_c['character_img'] ); ?>" alt="<?php echo esc_attr( $kago_c['character'] ); ?>" loading="lazy" onerror="this.remove()"><?php endif; ?></div>
					<div class="cast-name"><?php echo esc_html( $kago_c['character'] ); ?><small><?php echo esc_html( $kago_c['role'] ); ?></small></div>
				</div>
				<div class="cast-va">
					<div class="cast-photo"><?php if ( ! empty( $kago_c['voice_actor_img'] ) ) : ?><img src="<?php echo esc_url( $kago_c['voice_actor_img'] ); ?>" alt="<?php echo esc_attr( $kago_c['voice_actor'] ); ?>" loading="lazy" onerror="this.remove()"><?php endif; ?></div>
					<div class="cast-name"><?php echo esc_html( $kago_c['voice_actor'] ); ?><small><?php esc_html_e( 'Japanese', 'kagofansub' ); ?></small></div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>
