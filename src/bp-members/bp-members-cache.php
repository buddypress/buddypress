<?php
/**
 * Caching functions specific to BuddyPress Members.
 *
 * @package BuddyPress
 * @subpackage MembersCache
 * @since 2.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Pre-fetch member type data when initializing a Members loop.
 *
 * @since 2.2.0
 *
 * @param BP_User_Query $bp_user_query BP_User_Query object.
 */
function bp_members_prefetch_member_type( BP_User_Query $bp_user_query ) {
	$uncached_member_ids = bp_get_non_cached_ids( $bp_user_query->user_ids, 'bp_member_member_type' );

	$member_types = bp_get_object_terms( $uncached_member_ids, bp_get_member_type_tax_name(), array(
		'fields' => 'all_with_object_id',
	) );

	// Rekey by user ID.
	$keyed_member_types = array();
	foreach ( $member_types as $member_type ) {
		if ( ! isset( $keyed_member_types[ $member_type->object_id ] ) ) {
			$keyed_member_types[ $member_type->object_id ] = array();
		}

		$keyed_member_types[ $member_type->object_id ][] = $member_type->name;
	}

	$cached_member_ids = array();
	foreach ( $keyed_member_types as $user_id => $user_member_types ) {
		wp_cache_set( $user_id, $user_member_types, 'bp_member_member_type' );
		$cached_member_ids[] = $user_id;
	}

	// Cache an empty value for users with no type.
	foreach ( array_diff( $uncached_member_ids, $cached_member_ids ) as $no_type_id ) {
		wp_cache_set( $no_type_id, '', 'bp_member_member_type' );
	}
}
add_action( 'bp_user_query_populate_extras', 'bp_members_prefetch_member_type' );

/**
 * Clear the member_type cache for a user.
 *
 * Called when the user is deleted or marked as spam.
 *
 * @since 2.2.0
 *
 * @param int $user_id ID of the deleted user.
 */
function bp_members_clear_member_type_cache( $user_id ) {
	wp_cache_delete( $user_id, 'bp_member_member_type' );
}
add_action( 'wpmu_delete_user', 'bp_members_clear_member_type_cache' );
add_action( 'delete_user', 'bp_members_clear_member_type_cache' );

/**
 * Invalidate activity caches when a user's last_activity value is changed.
 *
 * @since 2.7.0
 *
 * @return bool True on success, false on failure.
 */
function bp_members_reset_activity_cache_incrementor() {
	return bp_core_reset_incrementor( 'bp_activity_with_last_activity' );
}
add_action( 'bp_core_user_updated_last_activity', 'bp_members_reset_activity_cache_incrementor' );
