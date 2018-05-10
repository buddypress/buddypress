<?php
/**
 * BuddyPress - Members Feedback No Notifications
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 3.0.0
 */

?>
<div id="message" class="info">

	<?php if ( bp_is_current_action( 'unread' ) ) : ?>

		<?php if ( bp_is_my_profile() ) : ?>

			<p><?php _e( 'You have no unread notifications.', 'buddypress' ); ?></p>

		<?php else : ?>

			<p><?php _e( 'This member has no unread notifications.', 'buddypress' ); ?></p>

		<?php endif; ?>

	<?php else : ?>

		<?php if ( bp_is_my_profile() ) : ?>

			<p><?php _e( 'You have no notifications.', 'buddypress' ); ?></p>

		<?php else : ?>

			<p><?php _e( 'This member has no notifications.', 'buddypress' ); ?></p>

		<?php endif; ?>

	<?php endif; ?>

</div>
