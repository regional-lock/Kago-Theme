<?php
/**
 * Optional comments block (Customizer → Kago Fansub).
 *
 * @package KagoFansub
 */

if ( kago_comments_enabled() && comments_open() ) {
	echo '<section class="content-card" id="comments-card">';
	comments_template();
	echo '</section>';
}
