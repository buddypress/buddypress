<?php
/**
 * Deprecated functions.
 *
 * @deprecated 2.9.0
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
 * Check whether Akismet is enabled.
 *
 * @since 1.6.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if Akismet is enabled, otherwise false.
 */
function bp_is_akismet_active( $default = true ) {

	/**
	 * Filters whether or not Akismet is enabled.
	 *
	 * @since 1.6.0
	 *
	 * @param bool $value Whether or not Akismet is enabled.
	 */
	return (bool) apply_filters( 'bp_is_akismet_active', (bool) bp_get_option( '_bp_enable_akismet', $default ) );
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
		return false;
	}
