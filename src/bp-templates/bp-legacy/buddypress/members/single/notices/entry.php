<?php
/**
 * BuddyPress - Member’s Notice Entry
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 15.0.0
 */

if ( ! isset( $args['context'] ) ) {
	return;
}
?>

<article id="notice-<?php bp_notice_id( $args['context'] ); ?>" class="notice-item <?php bp_notice_item_class( $args['context'] ); ?>">
	<header class="bp-notice-header">
		<h3><?php bp_notice_title( $args['context'] ); ?></h2>
	</header>
	<div class="bp-notice-body">
		<div class="bp-notice-content">
			<?php bp_notice_content( $args['context'] ); ?>
		</div>
	</div>
	<footer class="bp-notice-footer">
		<a href="<?php bp_notice_dismiss_url( $args['context'] ); ?>"><?php esc_html_e( 'Dismiss', 'buddypress' ); ?></a>
		<?php if ( bp_notice_has_call_to_action( $args['context'] ) ) : ?>
			<a href="<?php bp_notice_action_url( $args['context'] ); ?>"><?php bp_notice_action_text( $args['context'] ); ?></a>
		<?php endif; ?>
	</footer>
</article>
