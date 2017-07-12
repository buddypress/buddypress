<?php
/**
 * BuddyPress Activity Screens.
 *
 * The functions in this file detect, with each page load, whether an Activity
 * component page is being requested. If so, it parses any necessary data from
 * the URL, and tells BuddyPress to load the appropriate template.
 *
 * @package BuddyPress
 * @subpackage ActivityScreens
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the Activity directory.
 *
 * @since 1.5.0
 *
 */
function bp_activity_screen_index() {
	if ( bp_is_activity_directory() ) {
		bp_update_is_directory( true, 'activity' );

		/**
		 * Fires right before the loading of the Activity directory screen template file.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_activity_screen_index' );

		/**
		 * Filters the template to load for the Activity directory screen.
		 *
		 * @since 1.5.0
		 *
		 * @param string $template Path to the activity template to load.
		 */
		bp_core_load_template( apply_filters( 'bp_activity_screen_index', 'activity/index' ) );
	}
}
add_action( 'bp_screens', 'bp_activity_screen_index' );

/**
 * Load the 'My Activity' page.
 *
 * @since 1.0.0
 *
 */
function bp_activity_screen_my_activity() {

	/**
	 * Fires right before the loading of the "My Activity" screen template file.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_activity_screen_my_activity' );

	/**
	 * Filters the template to load for the "My Activity" screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_my_activity', 'members/single/home' ) );
}

/**
 * Load the 'My Friends' activity page.
 *
 * @since 1.0.0
 *
 */
function bp_activity_screen_friends() {
	if ( !bp_is_active( 'friends' ) )
		return false;

	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );

	/**
	 * Fires right before the loading of the "My Friends" screen template file.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_activity_screen_friends' );

	/**
	 * Filters the template to load for the "My Friends" screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_friends_activity', 'members/single/home' ) );
}

/**
 * Load the 'My Groups' activity page.
 *
 * @since 1.2.0
 *
 */
function bp_activity_screen_groups() {
	if ( !bp_is_active( 'groups' ) )
		return false;

	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );

	/**
	 * Fires right before the loading of the "My Groups" screen template file.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_activity_screen_groups' );

	/**
	 * Filters the template to load for the "My Groups" screen.
	 *
	 * @since 1.2.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_groups_activity', 'members/single/home' ) );
}

/**
 * Load the 'Favorites' activity page.
 *
 * @since 1.2.0
 *
 */
function bp_activity_screen_favorites() {
	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );

	/**
	 * Fires right before the loading of the "Favorites" screen template file.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_activity_screen_favorites' );

	/**
	 * Filters the template to load for the "Favorites" screen.
	 *
	 * @since 1.2.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_favorite_activity', 'members/single/home' ) );
}

/**
 * Load the 'Mentions' activity page.
 *
 * @since 1.2.0
 *
 */
function bp_activity_screen_mentions() {
	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );

	/**
	 * Fires right before the loading of the "Mentions" screen template file.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_activity_screen_mentions' );

	/**
	 * Filters the template to load for the "Mentions" screen.
	 *
	 * @since 1.2.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_mention_activity', 'members/single/home' ) );
}

/**
 * Reset the logged-in user's new mentions data when he visits his mentions screen.
 *
 * @since 1.5.0
 *
 */
function bp_activity_reset_my_new_mentions() {
	if ( bp_is_my_profile() )
		bp_activity_clear_new_mentions( bp_loggedin_user_id() );
}
add_action( 'bp_activity_screen_mentions', 'bp_activity_reset_my_new_mentions' );

/**
 * Load the page for a single activity item.
 *
 * @since 1.2.0
 *
 */
function bp_activity_screen_single_activity_permalink() {
	$bp = buddypress();

	// No displayed user or not viewing activity component.
	if ( !bp_is_activity_component() )
		return false;

	if ( ! bp_current_action() || !is_numeric( bp_current_action() ) )
		return false;

	// Get the activity details.
	$activity = bp_activity_get_specific( array( 'activity_ids' => bp_current_action(), 'show_hidden' => true, 'spam' => 'ham_only', ) );

	// 404 if activity does not exist
	if ( empty( $activity['activities'][0] ) || bp_action_variables() ) {
		bp_do_404();
		return;

	} else {
		$activity = $activity['activities'][0];
	}

	// Default access is true.
	$has_access = true;

	// If activity is from a group, do an extra cap check.
	if ( isset( $bp->groups->id ) && $activity->component == $bp->groups->id ) {

		// Activity is from a group, but groups is currently disabled.
		if ( !bp_is_active( 'groups') ) {
			bp_do_404();
			return;
		}

		// Check to see if the group is not public, if so, check the
		// user has access to see this activity.
		if ( $group = groups_get_group( $activity->item_id ) ) {

			// Group is not public.
			if ( 'public' != $group->status ) {

				// User is not a member of group.
				if ( !groups_is_user_member( bp_loggedin_user_id(), $group->id ) ) {
					$has_access = false;
				}
			}
		}
	}

	// If activity author does not match displayed user, block access.
	if ( true === $has_access && bp_displayed_user_id() !== $activity->user_id ) {
		$has_access = false;
	}

	/**
	 * Filters the access permission for a single activity view.
	 *
	 * @since 1.2.0
	 *
	 * @param array $access Array holding the current $has_access value and current activity item instance.
	 */
	$has_access = apply_filters_ref_array( 'bp_activity_permalink_access', array( $has_access, &$activity ) );

	/**
	 * Fires before the loading of a single activity template file.
	 *
	 * @since 1.2.0
	 *
	 * @param BP_Activity_Activity $activity   Object representing the current activity item being displayed.
	 * @param bool                 $has_access Whether or not the current user has access to view activity.
	 */
	do_action( 'bp_activity_screen_single_activity_permalink', $activity, $has_access );

	// Access is specifically disallowed.
	if ( false === $has_access ) {

		// User feedback.
		bp_core_add_message( __( 'You do not have access to this activity.', 'buddypress' ), 'error' );

		// Redirect based on logged in status.
		if ( is_user_logged_in() ) {
			$url = bp_loggedin_user_domain();

		} else {
			$url = sprintf(
				site_url( 'wp-login.php?redirect_to=%s' ),
				urlencode( esc_url_raw( bp_activity_get_permalink( bp_current_action() ) ) )
			);
		}

		bp_core_redirect( $url );
	}

	/**
	 * Filters the template to load for a single activity screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Path to the activity template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_activity_template_profile_activity_permalink', 'members/single/activity/permalink' ) );
}
add_action( 'bp_screens', 'bp_activity_screen_single_activity_permalink' );

/**
 * Add activity notifications settings to the notifications settings page.
 *
 * @since 1.2.0
 *
 */
function bp_activity_screen_notification_settings() {

	if ( bp_activity_do_mentions() ) {
		if ( ! $mention = bp_get_user_meta( bp_displayed_user_id(), 'notification_activity_new_mention', true ) ) {
			$mention = 'yes';
		}
	}

	if ( ! $reply = bp_get_user_meta( bp_displayed_user_id(), 'notification_activity_new_reply', true ) ) {
		$reply = 'yes';
	}

	?>

	<table class="notification-settings" id="activity-notification-settings">
		<thead>
			<tr>
				<th class="icon">&nbsp;</th>
				<th class="title"><?php _e( 'Activity', 'buddypress' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
				<th class="no"><?php _e( 'No', 'buddypress' )?></th>
			</tr>
		</thead>

		<tbody>
			<?php if ( bp_activity_do_mentions() ) : ?>
				<tr id="activity-notification-settings-mentions">
					<td>&nbsp;</td>
					<td><?php printf( __( 'A member mentions you in an update using "@%s"', 'buddypress' ), bp_core_get_username( bp_displayed_user_id() ) ) ?></td>
					<td class="yes"><input type="radio" name="notifications[notification_activity_new_mention]" id="notification-activity-new-mention-yes" value="yes" <?php checked( $mention, 'yes', true ) ?>/><label for="notification-activity-new-mention-yes" class="bp-screen-reader-text"><?php
						/* translators: accessibility text */
						_e( 'Yes, send email', 'buddypress' );
					?></label></td>
					<td class="no"><input type="radio" name="notifications[notification_activity_new_mention]" id="notification-activity-new-mention-no" value="no" <?php checked( $mention, 'no', true ) ?>/><label for="notification-activity-new-mention-no" class="bp-screen-reader-text"><?php
						/* translators: accessibility text */
						_e( 'No, do not send email', 'buddypress' );
					?></label></td>
				</tr>
			<?php endif; ?>

			<tr id="activity-notification-settings-replies">
				<td>&nbsp;</td>
				<td><?php _e( "A member replies to an update or comment you've posted", 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_activity_new_reply]" id="notification-activity-new-reply-yes" value="yes" <?php checked( $reply, 'yes', true ) ?>/><label for="notification-activity-new-reply-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_activity_new_reply]" id="notification-activity-new-reply-no" value="no" <?php checked( $reply, 'no', true ) ?>/><label for="notification-activity-new-reply-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>

			<?php

			/**
			 * Fires inside the closing </tbody> tag for activity screen notification settings.
			 *
			 * @since 1.2.0
			 */
			do_action( 'bp_activity_screen_notification_settings' ) ?>
		</tbody>
	</table>

<?php
}
add_action( 'bp_notification_settings', 'bp_activity_screen_notification_settings', 1 );

/** Theme Compatibility *******************************************************/

new BP_Activity_Theme_Compat();
