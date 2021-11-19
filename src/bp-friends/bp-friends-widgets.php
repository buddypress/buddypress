<?php
/**
 * BuddyPress Friends Widgets.
 *
 * @package BuddyPress
 * @subpackage FriendsWidgets
 * @since 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the Friends Legacy Widget.
 *
 * @since 10.0.0
 */
function bp_friends_register_friends_widget() {
	register_widget( 'BP_Core_Friends_Widget' );
}

/**
 * Register the friends widget.
 *
 * @since 1.9.0
 */
function bp_friends_register_widgets() {
	if ( ! bp_is_active( 'friends' ) ) {
		return;
	}

	// The Friends widget works only when looking an a displayed user,
	// and the concept of "displayed user" doesn't exist on non-root blogs,
	// so we don't register the widget there.
	if ( ! bp_is_root_blog() ) {
		return;
	}

	add_action( 'widgets_init', 'bp_friends_register_friends_widget' );
}
add_action( 'bp_register_widgets', 'bp_friends_register_widgets' );

/** Widget AJAX ***************************************************************/

/**
 * Process AJAX pagination or filtering for the Friends widget.
 *
 * @since 1.9.0
 */
function bp_core_ajax_widget_friends() {

	check_ajax_referer( 'bp_core_widget_friends' );

	switch ( $_POST['filter'] ) {
		case 'newest-friends':
			$type = 'newest';
			break;

		case 'recently-active-friends':
			$type = 'active';
			break;

		case 'popular-friends':
			$type = 'popular';
			break;
	}

	$members_args = array(
		'user_id'         => bp_displayed_user_id(),
		'type'            => $type,
		'max'             => absint( $_POST['max-friends'] ),
		'populate_extras' => 1,
	);

	if ( bp_has_members( $members_args ) ) : ?>
		<?php echo '0[[SPLIT]]'; // Return valid result. TODO: remove this. ?>
		<?php while ( bp_members() ) : bp_the_member(); ?>
			<li class="vcard">
				<div class="item-avatar">
					<a href="<?php bp_member_permalink(); ?>"><?php bp_member_avatar(); ?></a>
				</div>

				<div class="item">
					<div class="item-title fn"><a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a></div>
					<?php if ( 'active' == $type ) : ?>
						<div class="item-meta"><span class="activity" data-livestamp="<?php bp_core_iso8601_date( bp_get_member_last_active( array( 'relative' => false ) ) ); ?>"><?php bp_member_last_active(); ?></span></div>
					<?php elseif ( 'newest' == $type ) : ?>
						<div class="item-meta"><span class="activity" data-livestamp="<?php bp_core_iso8601_date( bp_get_member_registered( array( 'relative' => false ) ) ); ?>"><?php bp_member_registered(); ?></span></div>
					<?php elseif ( bp_is_active( 'friends' ) ) : ?>
						<div class="item-meta"><span class="activity"><?php bp_member_total_friend_count(); ?></span></div>
					<?php endif; ?>
				</div>
			</li>
		<?php endwhile; ?>

	<?php else: ?>
		<?php echo "-1[[SPLIT]]<li>"; ?>
		<?php esc_html_e( 'There were no members found, please try another filter.', 'buddypress' ); ?>
		<?php echo "</li>"; ?>
	<?php endif;
}
add_action( 'wp_ajax_widget_friends', 'bp_core_ajax_widget_friends' );
add_action( 'wp_ajax_nopriv_widget_friends', 'bp_core_ajax_widget_friends' );
