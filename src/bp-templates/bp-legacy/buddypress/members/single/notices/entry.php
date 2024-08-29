<?php
/**
 * BuddyPress - Member’s Notice Entry
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 15.0.0
 */

if ( ! isset( $args['notice'] ) ) {
	return;
}
?>

<article id="notice-<?php bp_notice_id( $args['notice'] ); ?>" class="notice-item <?php bp_notice_item_class( $args['notice'] ); ?>">
	<header class="bp-notice-header">
		<h3><?php bp_notice_title( $args['notice'] ); ?></h2>
	</header>
	<div class="bp-notice-body">
		<div class="bp-notice-content">
			<?php bp_notice_content( $args['notice'] ); ?>
		</div>
	</div>
	<footer class="bp-notice-footer">
		<a href="<?php bp_notice_dismiss_url( $args['notice'] ); ?>" class="button"><?php esc_html_e( 'Dismiss', 'buddypress' ); ?></a>
		<?php if ( bp_notice_has_call_to_action( $args['notice'] ) ) : ?>
			<a href="<?php bp_notice_action_url( $args['notice'] ); ?>" class="button"><?php bp_notice_action_text( $args['notice'] ); ?></a>
		<?php endif; ?>
	</footer>
</article>
