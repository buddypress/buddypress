<?php

/**
 * BuddyPress Notifications Navigational Functions.
 *
 * Sets up navigation elements, including BuddyBar functionality, for the
 * Notifications component.
 *
 * @package BuddyPress
 * @subpackage NotificationsBuddyBar
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Create the Notifications menu for the BuddyBar.
 *
 * @since BuddyPress (1.9.0)
 */
function bp_notifications_buddybar_menu() {

	if ( ! is_user_logged_in() ) {
		return false;
	}

	echo '<li id="bp-adminbar-notifications-menu"><a href="' . esc_url( bp_loggedin_user_domain() ) . '">';
	_e( 'Notifications', 'buddypress' );

	if ( $notification_count = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() ) ) : ?>
		<span><?php echo bp_core_number_format( $notification_count ); ?></span>
	<?php
	endif;

	echo '</a>';
	echo '<ul>';

	if ( $notifications = bp_notifications_get_notifications_for_user( bp_loggedin_user_id() ) ) {
		$counter = 0;
		for ( $i = 0, $count = count( $notifications ); $i < $count; ++$i ) {
			$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : ''; ?>

			<li<?php echo $alt ?>><?php echo $notifications[$i] ?></li>

			<?php $counter++;
		}
	} else { ?>

		<li><a href="<?php echo esc_url( bp_loggedin_user_domain() ); ?>"><?php _e( 'No new notifications.', 'buddypress' ); ?></a></li>

	<?php
	}

	echo '</ul>';
	echo '</li>';
}
add_action( 'bp_adminbar_menus', 'bp_adminbar_notifications_menu', 8 );
