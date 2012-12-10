<?php

/**
 * BuddyPress Activity Screens
 *
 * @package BuddyPress
 * @subpackage ActivityScreens
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Activity screen index
 *
 * @since BuddyPress (1.5)
 *
 * @uses bp_displayed_user_id()
 * @uses bp_is_activity_component()
 * @uses bp_current_action()
 * @uses bp_update_is_directory()
 * @uses do_action() To call the 'bp_activity_screen_index' hook
 * @uses bp_core_load_template()
 * @uses apply_filters() To call the 'bp_activity_screen_index' hook
 */
function bp_activity_screen_index() {
	if ( !bp_displayed_user_id() && bp_is_activity_component() && !bp_current_action() ) {
		bp_update_is_directory( true, 'activity' );

		do_action( 'bp_activity_screen_index' );

		bp_core_load_template( apply_filters( 'bp_activity_screen_index', 'activity/index' ) );
	}
}
add_action( 'bp_screens', 'bp_activity_screen_index' );

/**
 * Activity screen 'my activity' index
 *
 * @since BuddyPress (1.0)
 *
 * @uses do_action() To call the 'bp_activity_screen_my_activity' hook
 * @uses bp_core_load_template()
 * @uses apply_filters() To call the 'bp_activity_template_my_activity' hook
 */
function bp_activity_screen_my_activity() {
	do_action( 'bp_activity_screen_my_activity' );
	bp_core_load_template( apply_filters( 'bp_activity_template_my_activity', 'members/single/home' ) );
}

/**
 * Activity screen 'friends' index
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_is_active()
 * @uses bp_update_is_item_admin()
 * @uses bp_current_user_can()
 * @uses do_action() To call the 'bp_activity_screen_friends' hook
 * @uses bp_core_load_template()
 * @uses apply_filters() To call the 'bp_activity_template_friends_activity' hook
 */
function bp_activity_screen_friends() {
	if ( !bp_is_active( 'friends' ) )
		return false;

	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );
	do_action( 'bp_activity_screen_friends' );
	bp_core_load_template( apply_filters( 'bp_activity_template_friends_activity', 'members/single/home' ) );
}

/**
 * Activity screen 'groups' index
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_is_active()
 * @uses bp_update_is_item_admin()
 * @uses bp_current_user_can()
 * @uses do_action() To call the 'bp_activity_screen_groups' hook
 * @uses bp_core_load_template()
 * @uses apply_filters() To call the 'bp_activity_template_groups_activity' hook
 */
function bp_activity_screen_groups() {
	if ( !bp_is_active( 'groups' ) )
		return false;

	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );
	do_action( 'bp_activity_screen_groups' );
	bp_core_load_template( apply_filters( 'bp_activity_template_groups_activity', 'members/single/home' ) );
}

/**
 * Activity screen 'favorites' index
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_update_is_item_admin()
 * @uses bp_current_user_can()
 * @uses do_action() To call the 'bp_activity_screen_favorites' hook
 * @uses bp_core_load_template()
 * @uses apply_filters() To call the 'bp_activity_template_favorite_activity' hook
 */
function bp_activity_screen_favorites() {
	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );
	do_action( 'bp_activity_screen_favorites' );
	bp_core_load_template( apply_filters( 'bp_activity_template_favorite_activity', 'members/single/home' ) );
}

/**
 * Activity screen 'mentions' index
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_update_is_item_admin()
 * @uses bp_current_user_can()
 * @uses do_action() To call the 'bp_activity_screen_mentions' hook
 * @uses bp_core_load_template()
 * @uses apply_filters() To call the 'bp_activity_template_mention_activity' hook
 */
function bp_activity_screen_mentions() {
	bp_update_is_item_admin( bp_current_user_can( 'bp_moderate' ), 'activity' );
	do_action( 'bp_activity_screen_mentions' );
	bp_core_load_template( apply_filters( 'bp_activity_template_mention_activity', 'members/single/home' ) );
}

/**
 * Removes activity notifications from the notification menu when a user clicks on them and
 * is taken to a specific screen.
 *
 * @since BuddyPress (1.5)
 *
 * @global object $bp BuddyPress global settings
 * @uses bp_core_delete_notifications_by_type()
 */
function bp_activity_remove_screen_notifications() {
	global $bp;

	bp_core_delete_notifications_by_type( bp_loggedin_user_id(), $bp->activity->id, 'new_at_mention' );
}
add_action( 'bp_activity_screen_my_activity',               'bp_activity_remove_screen_notifications' );
add_action( 'bp_activity_screen_single_activity_permalink', 'bp_activity_remove_screen_notifications' );
add_action( 'bp_activity_screen_mentions',                  'bp_activity_remove_screen_notifications' );

/**
 * Reset the logged-in user's new mentions data when he visits his mentions screen
 *
 * @since BuddyPress (1.5)
 *
 * @uses bp_is_my_profile()
 * @uses bp_activity_clear_new_mentions()
 * @uses bp_loggedin_user_id()
 */
function bp_activity_reset_my_new_mentions() {
	if ( bp_is_my_profile() )
		bp_activity_clear_new_mentions( bp_loggedin_user_id() );
}
add_action( 'bp_activity_screen_mentions', 'bp_activity_reset_my_new_mentions' );

/**
 * Reset the logged-in user's new mentions data when he visits his mentions screen
 *
 * @since BuddyPress (1.2)
 *
 * @global object $bp BuddyPress global settings
 * @uses bp_is_activity_component()
 * @uses bp_activity_get_specific()
 * @uses bp_current_action()
 * @uses bp_action_variables()
 * @uses bp_do_404()
 * @uses bp_is_active()
 * @uses groups_get_group()
 * @uses groups_is_user_member()
 * @uses apply_filters_ref_array() To call the 'bp_activity_permalink_access' hook
 * @uses do_action() To call the 'bp_activity_screen_single_activity_permalink' hook
 * @uses bp_core_add_message()
 * @uses is_user_logged_in()
 * @uses bp_core_redirect()
 * @uses site_url()
 * @uses esc_url()
 * @uses bp_get_root_domain()
 * @uses bp_get_activity_root_slug()
 * @uses bp_core_load_template()
 * @uses apply_filters() To call the 'bp_activity_template_profile_activity_permalink' hook
 */
function bp_activity_screen_single_activity_permalink() {
	global $bp;

	// No displayed user or not viewing activity component
	if ( !bp_is_activity_component() )
		return false;

	if ( ! bp_current_action() || !is_numeric( bp_current_action() ) )
		return false;

	// Get the activity details
	$activity = bp_activity_get_specific( array( 'activity_ids' => bp_current_action(), 'show_hidden' => true, 'spam' => 'ham_only', ) );

	// 404 if activity does not exist
	if ( empty( $activity['activities'][0] ) || bp_action_variables() ) {
		bp_do_404();
		return;

	} else {
		$activity = $activity['activities'][0];
	}

	// Default access is true
	$has_access = true;

	// If activity is from a group, do an extra cap check
	if ( isset( $bp->groups->id ) && $activity->component == $bp->groups->id ) {

		// Activity is from a group, but groups is currently disabled
		if ( !bp_is_active( 'groups') ) {
			bp_do_404();
			return;
		}

		// Check to see if the group is not public, if so, check the
		// user has access to see this activity
		if ( $group = groups_get_group( array( 'group_id' => $activity->item_id ) ) ) {

			// Group is not public
			if ( 'public' != $group->status ) {

				// User is not a member of group
				if ( !groups_is_user_member( bp_loggedin_user_id(), $group->id ) ) {
					$has_access = false;
				}
			}
		}
	}

	// Allow access to be filtered
	$has_access = apply_filters_ref_array( 'bp_activity_permalink_access', array( $has_access, &$activity ) );

	// Allow additional code execution
	do_action( 'bp_activity_screen_single_activity_permalink', $activity, $has_access );

	// Access is specifically disallowed
	if ( false === $has_access ) {

		// User feedback
		bp_core_add_message( __( 'You do not have access to this activity.', 'buddypress' ), 'error' );

		// Redirect based on logged in status
		is_user_logged_in() ?
			bp_core_redirect( bp_loggedin_user_domain() ) :
			bp_core_redirect( site_url( 'wp-login.php?redirect_to=' . esc_url( bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/p/' . bp_current_action() . '/' ) ) );
	}

	bp_core_load_template( apply_filters( 'bp_activity_template_profile_activity_permalink', 'members/single/activity/permalink' ) );
}
add_action( 'bp_screens', 'bp_activity_screen_single_activity_permalink' );

/**
 * Add activity notifications settings to the notifications settings page
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_get_user_meta()
 * @uses bp_core_get_username()
 * @uses do_action() To call the 'bp_activity_screen_notification_settings' hook
 */
function bp_activity_screen_notification_settings() {

	if ( !$mention = bp_get_user_meta( bp_displayed_user_id(), 'notification_activity_new_mention', true ) )
		$mention = 'yes';

	if ( !$reply = bp_get_user_meta( bp_displayed_user_id(), 'notification_activity_new_reply', true ) )
		$reply = 'yes'; ?>

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
			<tr id="activity-notification-settings-mentions">
				<td>&nbsp;</td>
				<td><?php printf( __( 'A member mentions you in an update using "@%s"', 'buddypress' ), bp_core_get_username( bp_displayed_user_id() ) ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_activity_new_mention]" value="yes" <?php checked( $mention, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_activity_new_mention]" value="no" <?php checked( $mention, 'no', true ) ?>/></td>
			</tr>
			<tr id="activity-notification-settings-replies">
				<td>&nbsp;</td>
				<td><?php _e( "A member replies to an update or comment you've posted", 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_activity_new_reply]" value="yes" <?php checked( $reply, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_activity_new_reply]" value="no" <?php checked( $reply, 'no', true ) ?>/></td>
			</tr>

			<?php do_action( 'bp_activity_screen_notification_settings' ) ?>
		</tbody>
	</table>

<?php
}
add_action( 'bp_notification_settings', 'bp_activity_screen_notification_settings', 1 );

/** Theme Compatability *******************************************************/

/**
 * The main theme compat class for BuddyPress Activity
 *
 * This class sets up the necessary theme compatability actions to safely output
 * group template parts to the_title and the_content areas of a theme.
 *
 * @since BuddyPress (1.7)
 */
class BP_Activity_Theme_Compat {

	/**
	 * Setup the activity component theme compatibility
	 *
	 * @since BuddyPress (1.7)
	 */
	public function __construct() {
		add_action( 'bp_setup_theme_compat', array( $this, 'is_activity' ) );
	}

	/**
	 * Are we looking at something that needs activity theme compatability?
	 *
	 * @since BuddyPress (1.7)
	 */
	public function is_activity() {

		// Bail if not looking at a group
		if ( ! bp_is_activity_component() )
			return;

		// Activity Directory
		if ( ! bp_displayed_user_id() && ! bp_current_action() ) {
			bp_update_is_directory( true, 'activity' );

			do_action( 'bp_activity_screen_index' );

			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'directory_content'    ) );

		// Single activity
		} elseif ( bp_is_single_activity() ) {
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'single_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'single_dummy_content'    ) );
		}
	}

	/** Directory *************************************************************/

	/**
	 * Update the global $post with directory data
	 *
	 * @since BuddyPress (1.7)
	 */
	public function directory_dummy_post() {
		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => __( 'Sitewide Activity', 'buddypress' ),
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_activity',
			'post_status'    => 'publish',
			'is_archive'     => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the groups index template part
	 *
	 * @since BuddyPress (1.7)
	 */
	public function directory_content() {
		bp_buffer_template_part( 'activity/index' );
	}

	/** Single ****************************************************************/

	/**
	 * Update the global $post with the displayed user's data
	 *
	 * @since BuddyPress (1.7)
	 */
	public function single_dummy_post() {
		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => __( 'Activity', 'buddypress' ),
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_activity',
			'post_status'    => 'publish',
			'is_archive'     => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the members' activity permalink template part
	 *
	 * @since BuddyPress (1.7)
	 */
	public function single_dummy_content() {
		bp_buffer_template_part( 'activity/single/home' );
	}
}
new BP_Activity_Theme_Compat();
