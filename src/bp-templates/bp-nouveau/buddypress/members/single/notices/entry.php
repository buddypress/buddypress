<?php
/**
 * BuddyPress - Member’s Notice Entry
 *
 * @since 15.0.0
 * @version 15.0.0
 */

if ( ! isset( $args['context'] ) ) {
	return;
}
?>

<article id="notice-<?php bp_notice_id( $args['context'] ); ?>">
	<header class="bp-notice-header">
		<h3><?php bp_notice_title( $args['context'] ); ?></h2>
	</header>
	<div class="bp-notice-body">
		<div class="bp-notice-type dashicons <?php echo esc_attr( bp_get_notice_target_icon( $args['context'] ) ); ?>" ></div>
		<div class="bp-notice-content">
			<?php bp_notice_content( $args['context'] ); ?>
		</div>
	</div>
	<footer class="bp-notice-footer"></footer>
</article>
