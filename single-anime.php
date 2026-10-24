<?php
/**
 * Single anime.
 *
 * @package KagoFansub
 */

get_header();
the_post();
$kago_eps = kago_get_episodes( get_the_ID() );
?>
<main class="layout">
	<div class="main-col">
		<div class="page-heading">
			<div class="breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'kagofansub' ); ?></a> /
				<a href="<?php echo esc_url( get_post_type_archive_link( 'anime' ) ); ?>"><?php esc_html_e( 'Anime', 'kagofansub' ); ?></a> /
				<?php the_title(); ?>
			</div>
		</div>

		<?php get_template_part( 'template-parts/detail-main' ); ?>

		<section class="content-card" id="episode-list">
			<div class="section-title">
				<h2><?php esc_html_e( 'Episode List', 'kagofansub' ); ?></h2>
				<button type="button" class="episode-order-button" id="episode-order-toggle" aria-label="<?php esc_attr_e( 'Sort episodes: newest first', 'kagofansub' ); ?>" title="<?php esc_attr_e( 'Newest', 'kagofansub' ); ?>">
					<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M8 2v11m0 0 4-4m-4 4L4 9" /></svg>
					<span><?php esc_html_e( 'Newest', 'kagofansub' ); ?></span>
				</button>
			</div>
			<?php if ( $kago_eps ) : ?>
				<div class="episode-list">
					<?php foreach ( $kago_eps as $kago_ep ) : ?>
						<a class="episode-link" data-episode-number="<?php echo esc_attr( (int) kago_meta( $kago_ep->ID, 'ep_number', 0 ) ); ?>" href="<?php echo esc_url( get_permalink( $kago_ep ) ); ?>">
							<span><?php echo esc_html( kago_ep_label( kago_meta( $kago_ep->ID, 'ep_number', 0 ) ) ); ?></span>
							<?php $kago_episode_title = trim( (string) kago_meta( $kago_ep->ID, 'ep_title', '' ) ); ?>
							<?php if ( '' !== $kago_episode_title ) : ?><small>:&nbsp;&nbsp;<?php echo esc_html( $kago_episode_title ); ?></small><?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No episodes have been released yet.', 'kagofansub' ); ?></p>
			<?php endif; ?>
		</section>
		<script>
		(function () {
			var toggle = document.getElementById('episode-order-toggle');
			var list = document.querySelector('#episode-list .episode-list');
			if (!toggle || !list) return;
			var label = toggle.querySelector('span');
			var icon = toggle.querySelector('svg path');
			var newestFirst = true;
			toggle.addEventListener('click', function () {
				newestFirst = !newestFirst;
				Array.from(list.children).sort(function (a, b) {
					var aNumber = Number(a.getAttribute('data-episode-number')) || 0;
					var bNumber = Number(b.getAttribute('data-episode-number')) || 0;
					return newestFirst ? bNumber - aNumber : aNumber - bNumber;
				}).forEach(function (episode) { list.appendChild(episode); });
				label.textContent = newestFirst ? 'Newest' : 'Oldest';
				icon.setAttribute('d', newestFirst ? 'M8 2v11m0 0 4-4m-4 4L4 9' : 'M8 14V3m0 0 4 4m-4-4L4 7');
				toggle.setAttribute('aria-label', newestFirst ? 'Sort episodes: newest first' : 'Sort episodes: oldest first');
				toggle.title = newestFirst ? 'Newest' : 'Oldest';
			});
		})();
		</script>

		<?php get_template_part( 'template-parts/recommended' ); ?>
		<?php get_template_part( 'template-parts/comments-block' ); ?>
	</div>
	<?php get_sidebar(); ?>
</main>
<?php get_footer(); ?>
