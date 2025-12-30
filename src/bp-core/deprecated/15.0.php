<?php
/**
 * Deprecated Functions.
 *
 * @package BuddyPress
 * @deprecated 15.0.0
 */

/**
 * Output whether signup is allowed.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 */
function bp_signup_allowed() {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_get_signup_allowed()' );

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_signup_allowed();
}

/**
 * Delete an activity item by activity id.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 *
 * @param array|string $args See BP_Activity_Activity::get for a
 *                           description of accepted arguments.
 * @return bool
 */
function bp_activity_delete_by_item_id( $args = '' ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_activity_delete()' );

	$r = bp_parse_args(
		$args,
		array(
			'item_id'           => false,
			'component'         => false,
			'type'              => false,
			'user_id'           => false,
			'secondary_item_id' => false,
		)
	);

	return bp_activity_delete( $r );
}

/**
 * Delete an activity item by its content.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 *
 * @param int    $user_id   The user id.
 * @param string $content   The activity id.
 * @param string $component The activity component.
 * @param string $type      The activity type.
 * @return bool
 */
function bp_activity_delete_by_content( $user_id, $content, $component, $type ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_activity_delete()' );

	return bp_activity_delete(
		array(
			'user_id'   => $user_id,
			'content'   => $content,
			'component' => $component,
			'type'      => $type
		)
	);
}

/**
 * Delete a user's activity for a component.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 *
 * @param int    $user_id   The user id.
 * @param string $component The activity component.
 * @return bool
 */
function bp_activity_delete_for_user_by_component( $user_id, $component ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_activity_delete()' );

	return bp_activity_delete(
		array(
			'user_id'   => $user_id,
			'component' => $component
		)
	);
}

/**
 * Delete an activity item by activity id.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 *
 * @param int $activity_id ID of the activity item to be deleted.
 * @return bool
 */
function bp_activity_delete_by_activity_id( $activity_id ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_activity_delete()' );

	return bp_activity_delete( array( 'id' => $activity_id ) );
}
