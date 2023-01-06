<?php
/**
 * Roles and capabilities logic for the XProfile component.
 *
 * @package BuddyPress
 * @subpackage XPRofileCaps
 * @since 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Maps XProfile caps to built in WordPress caps.
 *
 * @since 1.6.0
 *
 * @param array  $caps    Capabilities for meta capability.
 * @param string $cap     Capability name.
 * @param int    $user_id User id.
 * @param mixed  $args    Arguments.
 *
 * @return array Actual capabilities for meta capability.
 */
function bp_xprofile_map_meta_caps( $caps, $cap, $user_id, $args ) {
	switch ( $cap ) {
		case 'bp_xprofile_change_field_visibility' :
			$caps = array( 'exist' );

			// You may pass args manually: $field_id, $profile_user_id.
			$field_id        = 0;
			$profile_user_id = isset( $args[1] ) ? (int) $args[1] : bp_displayed_user_id();

			if ( ! empty( $args[0] ) ) {
				$field_id = (int) $args[0];
			} elseif ( isset( $GLOBALS['profile_template'] ) && $GLOBALS['profile_template']->in_the_loop ) {
				$field_id = bp_get_the_profile_field_id();
			}

			// Visibility on the fullname field is not editable.
			if ( 1 == $field_id ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// Has the admin disabled visibility modification for this field?
			if ( 'disabled' == bp_xprofile_get_meta( $field_id, 'field', 'allow_custom_visibility' ) ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// Friends don't let friends edit each other's visibility.
			if ( $profile_user_id != bp_displayed_user_id() && ! bp_current_user_can( 'bp_moderate' ) ) {
				$caps[] = 'do_not_allow';
				break;
			}

			break;
	}

	/**
	 * Filters the XProfile caps to built in WordPress caps.
	 *
	 * @since 1.6.0
	 *
	 * @param array  $caps    Capabilities for meta capability.
	 * @param string $cap     Capability name.
	 * @param int    $user_id User ID being mapped.
	 * @param mixed  $args    Capability arguments.
	 */
	return apply_filters( 'bp_xprofile_map_meta_caps', $caps, $cap, $user_id, $args );
}
add_filter( 'bp_map_meta_caps', 'bp_xprofile_map_meta_caps', 10, 4 );

/**
 * Grant the 'bp_xprofile_change_field_visibility' cap to logged-out users.
 *
 * @since 2.7.1
 *
 * @param bool   $user_can
 * @param int    $user_id
 * @param string $capability
 * @return bool
 */
function bp_xprofile_grant_bp_xprofile_change_field_visibility_for_logged_out_users( $user_can, $user_id, $capability ) {
	if ( 'bp_xprofile_change_field_visibility' === $capability && 0 === $user_id ) {
		$field_id = bp_get_the_profile_field_id();
		if ( $field_id && $field = xprofile_get_field( $field_id, null, false ) ) {
			$user_can = 'allowed' === $field->allow_custom_visibility;
		}
	}

	return $user_can;
}
add_filter( 'bp_user_can', 'bp_xprofile_grant_bp_xprofile_change_field_visibility_for_logged_out_users', 10, 3 );
