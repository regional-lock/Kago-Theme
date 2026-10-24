<?php
/**
 * Comments.
 *
 * @package KagoFansub
 */

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="kago-comments">
	<?php if ( have_comments() ) : ?>
		<h2><?php echo esc_html( sprintf( _n( '%d comment', '%d comments', get_comments_number(), 'kagofansub' ), get_comments_number() ) ); ?></h2>
		<ol class="kago-comment-list">
			<?php wp_list_comments( array( 'style' => 'ol', 'avatar_size' => 40 ) ); ?>
		</ol>
		<?php the_comments_pagination(); ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</div>
