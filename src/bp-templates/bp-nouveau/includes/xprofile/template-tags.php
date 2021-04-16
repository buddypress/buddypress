<?php
/**
 * xProfile Template tags
 *
 * @since 3.0.0
 * @version 8.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fire specific hooks into the single members xprofile templates.
 *
 * @since 3.0.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bp_nouveau_xprofile_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's a xprofile hook
	$hook[] = 'profile';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bp_nouveau_hook( $hook );
}

/**
 * Template tag to output the field visibility markup in edit and signup screens.
 *
 * @since 3.0.0
 */
function bp_nouveau_xprofile_edit_visibilty() {
	/**
	 * Fires before the display of visibility options for the field.
	 *
	 * @since 1.7.0
	 */
	do_action( 'bp_custom_profile_edit_fields_pre_visibility' );

	bp_get_template_part( 'members/single/parts/profile-visibility' );

	/**
	 * Fires after the visibility options for a field.
	 *
	 * @since 3.0.0
	 */
	do_action( 'bp_custom_profile_edit_fields' );
}

/**
 * Return a bool check to see whether the base re group has had extended
 * profile fields added to it for the registration screen.
 *
 * @since 3.0.0
 * @deprecated 8.0.0
 */
function bp_nouveau_base_account_has_xprofile() {
	_deprecated_function( __FUNCTION__, '8.0.0', 'bp_nouveau_has_signup_xprofile_fields()' );
	return bp_nouveau_has_signup_xprofile_fields();
}

/**
 * Checks whether there are signup profile fields to display.
 *
 * @since 8.0.0
 *
 * @param bool Whether to init an xProfile loop.
 * @return bool True if there are signup profile fields to display. False otherwise.
 */
function bp_nouveau_has_signup_xprofile_fields( $do_loop = false ) {
	if ( ! $do_loop ) {
		$signup_fields = (array) bp_xprofile_get_signup_field_ids();
		return 1 <= count( $signup_fields );
	}

	return bp_has_profile( bp_xprofile_signup_args() );
}
