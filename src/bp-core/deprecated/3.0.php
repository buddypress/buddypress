<?php
/**
 * Deprecated functions.
 *
 * @deprecated 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check whether bbPress plugin-powered Group Forums are enabled.
 *
 * @since 1.6.0
 * @since 3.0.0 $default argument's default value changed from true to false.
 * @deprecated 3.0.0 No longer used in core, but supported for third-party code.
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if group forums are active, otherwise false.
 */
function bp_is_group_forums_active( $default = false ) {
	_deprecated_function( __FUNCTION__, '3.0', 'groups_get_group( $id )->enable_forum' );

	$is_active = function_exists( 'bbp_is_group_forums_active' ) ? bbp_is_group_forums_active( $default ) : $default;

	/**
	 * Filters whether or not bbPress plugin-powered Group Forums are enabled.
	 *
	 * @since 1.6.0
	 * @deprecated 3.0.0 No longer used in core, but supported for third-party code.
	 *
	 * @param bool $value Whether or not bbPress plugin-powered Group Forums are enabled.
	 */
	return (bool) apply_filters( 'bp_is_group_forums_active', $is_active );
}

/**
 * Is this a user's forums page?
 *
 * Eg http://example.com/members/joe/forums/ (or a subpage thereof).
 *
 * @since 1.5.0
 * @deprecated 3.0.0 No longer used in core, but supported for third-party code.
 *
 * @return false
 */
function bp_is_user_forums() {
	_deprecated_function( __FUNCTION__, '3.0', 'legacy forum support removed' );
	return false;
}

/**
 * Is the current page a group's (legacy bbPress) forum page?
 *
 * @since 1.1.0
 * @since 3.0.0 Always returns false.
 * @deprecated 3.0.0 No longer used in core, but supported for custom theme templates.
 *
 * @return bool
 */
function bp_is_group_forum() {
	_deprecated_function( __FUNCTION__, '3.0', 'legacy forum support removed' );
	return false;
}


/**
 * Output a 'New Topic' button for a group.
 *
 * @since 1.2.7
 * @deprecated 3.0.0 No longer used in core, but supported for third-party code.
 *
 * @param BP_Groups_Group|bool $group The BP Groups_Group object if passed, boolean false if not passed.
 */
function bp_group_new_topic_button( $group = false ) {
	_deprecated_function( __FUNCTION__, '3.0', 'legacy forum support removed' );
}

	/**
	 * Return a 'New Topic' button for a group.
	 *
	 * @since 1.2.7
	 * @deprecated 3.0.0 No longer used in core, but supported for third-party code.
	 *
	 * @param BP_Groups_Group|bool $group The BP Groups_Group object if passed, boolean false if not passed.
	 *
	 * @return false
	 */
	function bp_get_group_new_topic_button( $group = false ) {
		_deprecated_function( __FUNCTION__, '3.0', 'legacy forum support removed' );
		return false;
	}

/**
 * Catch a "Mark as Spammer/Not Spammer" click from the toolbar.
 *
 * When a site admin selects "Mark as Spammer/Not Spammer" from the admin menu
 * this action will fire and mark or unmark the user and their blogs as spam.
 * Must be a site admin for this function to run.
 *
 * Note: no longer used in the current state. See the Settings component.
 *
 * @since 1.1.0
 * @since 1.6.0 No longer used, unhooked.
 * @since 3.0.0 Formally marked as deprecated.
 *
 * @param int $user_id Optional. User ID to mark as spam. Defaults to displayed user.
 */
function bp_core_action_set_spammer_status( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '3.0' );

	// Only super admins can currently spam users (but they can't spam
	// themselves).
	if ( ! is_super_admin() || bp_is_my_profile() ) {
		return;
	}

	// Use displayed user if it's not yourself.
	if ( empty( $user_id ) )
		$user_id = bp_displayed_user_id();

	if ( bp_is_current_component( 'admin' ) && ( in_array( bp_current_action(), array( 'mark-spammer', 'unmark-spammer' ) ) ) ) {

		// Check the nonce.
		check_admin_referer( 'mark-unmark-spammer' );

		// To spam or not to spam.
		$status = bp_is_current_action( 'mark-spammer' ) ? 'spam' : 'ham';

		// The heavy lifting.
		bp_core_process_spammer_status( $user_id, $status );

		// Add feedback message. @todo - Error reporting.
		if ( 'spam' == $status ) {
			bp_core_add_message( __( 'User marked as spammer. Spam users are visible only to site admins.', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'User removed as spammer.', 'buddypress' ) );
		}

		// Deprecated. Use bp_core_process_spammer_status.
		$is_spam = 'spam' == $status;
		do_action( 'bp_core_action_set_spammer_status', bp_displayed_user_id(), $is_spam );

		// Redirect back to where we came from.
		bp_core_redirect( wp_get_referer() );
	}
}

/**
 * Process user deletion requests.
 *
 * Note: no longer used in the current state. See the Settings component.
 *
 * @since 1.1.0
 * @since 1.6.0 No longer used, unhooked.
 * @since 3.0.0 Formally marked as deprecated.
 */
function bp_core_action_delete_user() {
	_deprecated_function( __FUNCTION__, '3.0' );

	if ( !bp_current_user_can( 'bp_moderate' ) || bp_is_my_profile() || !bp_displayed_user_id() )
		return false;

	if ( bp_is_current_component( 'admin' ) && bp_is_current_action( 'delete-user' ) ) {

		// Check the nonce.
		check_admin_referer( 'delete-user' );

		$errors = false;
		do_action( 'bp_core_before_action_delete_user', $errors );

		if ( bp_core_delete_account( bp_displayed_user_id() ) ) {
			bp_core_add_message(
				/* translators: %s: member name */
				sprintf( _x( '%s has been deleted from the system.', 'deprecated string', 'buddypress' ), bp_get_displayed_user_fullname() )
			);
		} else {
			bp_core_add_message(
				/* translators: %s: member name */
				sprintf( _x( 'There was an error deleting %s from the system. Please try again.', 'deprecated string', 'buddypress' ), bp_get_displayed_user_fullname() ),
				'error'
			);

			$errors = true;
		}

		do_action( 'bp_core_action_delete_user', $errors );

		if ( $errors )
			bp_core_redirect( bp_displayed_user_domain() );
		else
			bp_core_redirect( bp_loggedin_user_domain() );
	}
}
