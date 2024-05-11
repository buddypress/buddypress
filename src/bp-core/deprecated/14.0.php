<?php
/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 14.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Select the right `block_editor_settings` filter according to WP version.
 *
 * @since 8.0.0
 * @deprecated 14.0.0
 */
function bp_block_init_editor_settings_filter() {
	_deprecated_function( __FUNCTION__, '14.0.0' );
}

/**
 * Select the right `block_categories` filter according to WP version.
 *
 * @since 8.0.0
 * @since 12.0.0 This category is left for third party plugin but not used anymmore.
 * @deprecated 14.0.0
 */
function bp_block_init_category_filter() {
	_deprecated_function( __FUNCTION__, '14.0.0' );
}

/**
 * Should we use the WP Toolbar?
 *
 * The WP Toolbar, introduced in WP 3.1, is fully supported in BuddyPress as
 * of BP 1.5. For BP 1.6, the WP Toolbar is the default.
 *
 * @since 1.5.0
 * @deprecated 14.0.0
 *
 * @return bool Default: true. False when WP Toolbar support is disabled.
 */
function bp_use_wp_admin_bar() {
	_deprecated_function( __FUNCTION__, '14.0.0' );

	// Default to true.
	$use_admin_bar = true;

	if ( defined( 'BP_USE_WP_ADMIN_BAR' ) ) {
		_doing_it_wrong( 'BP_USE_WP_ADMIN_BAR', esc_html__( 'The BP_USE_WP_ADMIN_BAR constant is deprecated.', 'buddypress' ), 'BuddyPress 14.0.0' );
	}

	/**
	 * Filters whether or not to use the admin bar.
	 *
	 * @since 1.5.0
	 * @deprecated 14.0.0
	 *
	 * @param bool $use_admin_bar Whether or not to use the admin bar.
	 */
	return apply_filters_deprecated( 'bp_use_wp_admin_bar', array( $use_admin_bar ), '14.0.0' );
}

/**
 * In emails editor, add notice linking to token documentation on Codex.
 *
 * @since 2.5.0
 * @deprecated 14.0.0
 */
function bp_admin_email_add_codex_notice() {
	_deprecated_function( __FUNCTION__, '14.0.0' );

	if ( get_current_screen()->post_type !== bp_get_email_post_type() ) {
		return;
	}

	bp_core_add_admin_notice(
		sprintf(
			// Translators: %s is the url to the BuddyPress codex page about BP Email tokens.
			__( 'Phrases wrapped in braces <code>{{ }}</code> are email tokens. <a href="%s">Learn about tokens on the BuddyPress Codex</a>.', 'buddypress' ),
			esc_url( 'https://codex.buddypress.org/emails/email-tokens/' )
		),
		'error'
	);
}

/**
 * Is the current page the Notices screen?
 *
 * Eg http://example.com/members/joe/messages/notices/.
 *
 * @since 1.1.0
 * @deprecated 14.0.0
 *
 * @return bool True if the current page is the Notices screen.
 */
function bp_is_notices() {
	_deprecated_function( __FUNCTION__, '14.0.0' );
	return false;
}

/**
 * Handle editing of sitewide notices.
 *
 * @since 2.4.0 This function was split from messages_screen_notices(). See #6505.
 * @deprecated 14.0.0
 *
 * @return void
 */
function bp_messages_action_edit_notice() {
	_deprecated_function( __FUNCTION__, '14.0.0' );
}

/**
 * Handle user dismissal of sitewide notices.
 *
 * @since 9.0.0
 * @deprecated 14.0.0
 *
 * @return bool False on failure.
 */
function bp_messages_action_dismiss_notice() {
	_deprecated_function( __FUNCTION__, '14.0.0' );
}

/**
 * Load the Messages > Notices screen.
 *
 * @since 1.0.0
 * @deprecated 14.0.0
 *
 * @return void
 */
function messages_screen_notices() {
	_deprecated_function( __FUNCTION__, '14.0.0' );
}

/**
 * Send a notice.
 *
 * @since 1.0.0
 * @deprecated 14.0.0
 *
 * @param string $subject Subject of the notice.
 * @param string $message Content of the notice.
 * @return bool True on success, false on failure.
 */
function messages_send_notice( $subject, $message ) {
	_deprecated_function( __FUNCTION__, '14.0.0' );
	$notice_id = bp_members_send_notice(
		array(
			'title'   => $subject,
			'content' => $message
		)
	);

	if ( ! is_wp_error( $notice_id )  ) {
		$notice = new BP_Members_Notice( $notice_id );
		$retval = true;
	} else {
		$notice = $notice_id;
		$retval = false;
	}

	/**
	 * Fires after a notice has been successfully sent.
	 *
	 * @since 1.0.0
	 * @deprecated 14.0.0
	 *
	 * @param string                     $subject Subject of the notice.
	 * @param string                     $message Content of the notice.
	 * @param BP_Members_Notice|WP_Error $notice  Notice object sent. A WP Error if something went wrong.
	 */
	do_action_deprecated( 'messages_send_notice', array( $subject, $message, $notice ), '14.0.0' );

	return $retval;
}
