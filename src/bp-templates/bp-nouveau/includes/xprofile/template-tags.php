<?php
/**
 * xProfile Template tags
 *
 * @since 3.0.0
 * @version 3.0.0
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
 */
function bp_nouveau_base_account_has_xprofile() {
	return (bool) bp_has_profile(
		array(
			'profile_group_id' => 1,
			'fetch_field_data' => false,
		)
	);
}
