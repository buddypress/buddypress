<?php
/**
 * BuddyPress Groups Caching.
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyPress
 * @subpackage Groups
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Slurp up metadata for a set of groups.
 *
 * This function is called in two places in the BP_Groups_Group class:
 *   - in the populate() method, when single group objects are populated
 *   - in the get() method, when multiple groups are queried
 *
 * It grabs all groupmeta associated with all of the groups passed in
 * $group_ids and adds it to WP cache. This improves efficiency when using
 * groupmeta within a loop context.
 *
 * @since 1.6.0
 *
 * @param int|string|array|bool $group_ids Accepts a single group_id, or a
 *                                         comma-separated list or array of
 *                                         group ids.
 */
function bp_groups_update_meta_cache( $group_ids = false ) {
	$bp = buddypress();

	$cache_args = array(
		'object_ids' 	   => $group_ids,
		'object_type' 	   => $bp->groups->id,
		'cache_group'      => 'group_meta',
		'object_column'    => 'group_id',
		'meta_table' 	   => $bp->groups->table_name_groupmeta,
		'cache_key_prefix' => 'bp_groups_groupmeta'
	);

	bp_update_meta_cache( $cache_args );
}

/**
 * Clear the cached group count.
 *
 * @since 1.0.0
 *
 * @param int $group_id Not used.
 */
function groups_clear_group_object_cache( $group_id ) {
	wp_cache_delete( 'bp_total_group_count', 'bp' );
}
add_action( 'groups_group_deleted',              'groups_clear_group_object_cache' );
add_action( 'groups_settings_updated',           'groups_clear_group_object_cache' );
add_action( 'groups_details_updated',            'groups_clear_group_object_cache' );
add_action( 'groups_group_avatar_updated',       'groups_clear_group_object_cache' );
add_action( 'groups_create_group_step_complete', 'groups_clear_group_object_cache' );

/**
 * Bust group caches when editing or deleting.
 *
 * @since 1.7.0
 *
 * @param int $group_id The group being edited.
 */
function bp_groups_delete_group_cache( $group_id = 0 ) {
	wp_cache_delete( $group_id, 'bp_groups' );
}
add_action( 'groups_delete_group',     'bp_groups_delete_group_cache' );
add_action( 'groups_update_group',     'bp_groups_delete_group_cache' );
add_action( 'groups_details_updated',  'bp_groups_delete_group_cache' );
add_action( 'groups_settings_updated', 'bp_groups_delete_group_cache' );

/**
 * Bust group cache when modifying metadata.
 *
 * @since 2.0.0
 *
 * @param int $meta_id Meta ID.
 * @param int $group_id Group ID.
 */
function bp_groups_delete_group_cache_on_metadata_change( $meta_id, $group_id ) {
	wp_cache_delete( $group_id, 'bp_groups' );
}
add_action( 'updated_group_meta', 'bp_groups_delete_group_cache_on_metadata_change', 10, 2 );
add_action( 'added_group_meta', 'bp_groups_delete_group_cache_on_metadata_change', 10, 2 );

/**
 * Clear caches for the group creator when a group is created.
 *
 * @since 1.6.0
 *
 * @param int             $group_id  ID of the group.
 * @param BP_Groups_Group $group_obj Group object.
 */
function bp_groups_clear_group_creator_cache( $group_id, $group_obj ) {
	// Clears the 'total groups' for this user.
	groups_clear_group_user_object_cache( $group_obj->id, $group_obj->creator_id );
}
add_action( 'groups_created_group', 'bp_groups_clear_group_creator_cache', 10, 2 );

/**
 * Clears caches for all members in a group when a group is deleted.
 *
 * @since 1.6.0
 *
 * @param BP_Groups_Group $group_obj Group object.
 * @param array           $user_ids  User IDs who were in this group.
 */
function bp_groups_clear_group_members_caches( $group_obj, $user_ids ) {
	// Clears the 'total groups' cache for each member in a group.
	foreach ( (array) $user_ids as $user_id )
		groups_clear_group_user_object_cache( $group_obj->id, $user_id );
}
add_action( 'bp_groups_delete_group', 'bp_groups_clear_group_members_caches', 10, 2 );

/**
 * Clear a user's cached total group invite count.
 *
 * Count is cleared when an invite is accepted, rejected or deleted.
 *
 * @since 2.0.0
 *
 * @param int $user_id The user ID.
 */
function bp_groups_clear_invite_count_for_user( $user_id ) {
	wp_cache_delete( $user_id, 'bp_group_invite_count' );
}
add_action( 'groups_accept_invite', 'bp_groups_clear_invite_count_for_user' );
add_action( 'groups_reject_invite', 'bp_groups_clear_invite_count_for_user' );
add_action( 'groups_delete_invite', 'bp_groups_clear_invite_count_for_user' );

/**
 * Clear a user's cached total group invite count when a user is uninvited.
 *
 * Groan. Our API functions are not consistent.
 *
 * @since 2.0.0
 *
 * @param int $group_id The group ID. Not used in this function.
 * @param int $user_id  The user ID.
 */
function bp_groups_clear_invite_count_on_uninvite( $group_id, $user_id ) {
	bp_groups_clear_invite_count_for_user( $user_id );
}
add_action( 'groups_uninvite_user', 'bp_groups_clear_invite_count_on_uninvite', 10, 2 );

/**
 * Clear a user's cached total group invite count when a new invite is sent.
 *
 * @since 2.0.0
 *
 * @param int   $group_id      The group ID. Not used in this function.
 * @param array $invited_users Array of invited user IDs.
 */
function bp_groups_clear_invite_count_on_send( $group_id, $invited_users ) {
	foreach ( $invited_users as $user_id ) {
		bp_groups_clear_invite_count_for_user( $user_id );
	}
}
add_action( 'groups_send_invites', 'bp_groups_clear_invite_count_on_send', 10, 2 );

/**
 * Clear a user's cached group count.
 *
 * @since 1.2.0
 *
 * @param int $group_id The group ID. Not used in this function.
 * @param int $user_id  The user ID.
 */
function groups_clear_group_user_object_cache( $group_id, $user_id ) {
	wp_cache_delete( 'bp_total_groups_for_user_' . $user_id, 'bp' );
}
add_action( 'groups_join_group',    'groups_clear_group_user_object_cache', 10, 2 );
add_action( 'groups_leave_group',   'groups_clear_group_user_object_cache', 10, 2 );
add_action( 'groups_ban_member',    'groups_clear_group_user_object_cache', 10, 2 );
add_action( 'groups_unban_member',  'groups_clear_group_user_object_cache', 10, 2 );
add_action( 'groups_uninvite_user', 'groups_clear_group_user_object_cache', 10, 2 );
add_action( 'groups_remove_member', 'groups_clear_group_user_object_cache', 10, 2 );

/**
 * Clear group administrator and moderator cache.
 *
 * @since 2.1.0
 *
 * @param int $group_id The group ID.
 */
function groups_clear_group_administrator_cache( $group_id ) {
	wp_cache_delete( $group_id, 'bp_group_admins' );
	wp_cache_delete( $group_id, 'bp_group_mods' );
}
add_action( 'groups_promote_member', 'groups_clear_group_administrator_cache' );
add_action( 'groups_demote_member',  'groups_clear_group_administrator_cache' );
add_action( 'groups_delete_group',   'groups_clear_group_administrator_cache' );

/**
 * Clear group administrator and moderator cache when a group member is saved.
 *
 * This accounts for situations where group admins or mods are added manually
 * using {@link BP_Groups_Member::save()}.  Usually via a plugin.
 *
 * @since 2.1.0
 *
 * @param BP_Groups_Member $member Member object.
 */
function groups_clear_group_administrator_cache_on_member_save( BP_Groups_Member $member ) {
	groups_clear_group_administrator_cache( $member->group_id );
}
add_action( 'groups_member_after_save', 'groups_clear_group_administrator_cache_on_member_save' );

/**
 * Clear the group type cache for a group.
 *
 * Called when group is deleted.
 *
 * @since 2.6.0
 *
 * @param int $group_id The group ID.
 */
function groups_clear_group_type_cache( $group_id = 0 ) {
	wp_cache_delete( $group_id, 'bp_groups_group_type' );
}
add_action( 'groups_delete_group', 'groups_clear_group_type_cache' );

/**
 * Clear caches on membership save.
 *
 * @since 2.6.0
 */
function bp_groups_clear_user_group_cache_on_membership_save( BP_Groups_Member $member ) {
	wp_cache_delete( $member->user_id, 'bp_groups_memberships_for_user' );
	wp_cache_delete( $member->id, 'bp_groups_memberships' );
}
add_action( 'groups_member_before_save', 'bp_groups_clear_user_group_cache_on_membership_save' );
add_action( 'groups_member_before_remove', 'bp_groups_clear_user_group_cache_on_membership_save' );

/**
 * Clear group memberships cache on miscellaneous actions not covered by the 'after_save' hook.
 *
 * @since 2.6.0
 */
function bp_groups_clear_user_group_cache_on_other_events( $user_id, $group_id ) {
	wp_cache_delete( $user_id, 'bp_groups_memberships_for_user' );

	$membership = new BP_Groups_Member( $user_id, $group_id );
	wp_cache_delete( $membership->id, 'bp_groups_memberships' );
}
add_action( 'bp_groups_member_before_delete', 'bp_groups_clear_user_group_cache_on_other_events', 10, 2 );
add_action( 'bp_groups_member_before_delete_invite', 'bp_groups_clear_user_group_cache_on_other_events', 10, 2 );
add_action( 'groups_accept_invite', 'bp_groups_clear_user_group_cache_on_other_events', 10, 2 );

/**
 * Reset cache incrementor for the Groups component.
 *
 * This function invalidates all cached results of group queries,
 * whenever one of the following events takes place:
 *   - A group is created or updated.
 *   - A group is deleted.
 *   - A group's metadata is modified.
 *
 * @since 2.7.0
 *
 * @return bool True on success, false on failure.
 */
function bp_groups_reset_cache_incrementor() {
	return bp_core_reset_incrementor( 'bp_groups' );
}
add_action( 'groups_group_after_save', 'bp_groups_reset_cache_incrementor' );
add_action( 'bp_groups_delete_group',  'bp_groups_reset_cache_incrementor' );
add_action( 'updated_group_meta',      'bp_groups_reset_cache_incrementor' );
add_action( 'deleted_group_meta',      'bp_groups_reset_cache_incrementor' );
add_action( 'added_group_meta',        'bp_groups_reset_cache_incrementor' );

/**
 * Reset cache incrementor for Groups component when a group's taxonomy terms change.
 *
 * We infer that a group is being affected by looking at the objects belonging
 * to the taxonomy being affected.
 *
 * @since 2.7.0
 *
 * @param int    $object_id ID of the item whose terms are being modified.
 * @param array  $terms     Array of object terms.
 * @param array  $tt_ids    Array of term taxonomy IDs.
 * @param string $taxonomy  Taxonomy slug.
 * @return bool True on success, false on failure.
 */
function bp_groups_reset_cache_incrementor_on_group_term_change( $object_id, $terms, $tt_ids, $taxonomy ) {
	$tax_object = get_taxonomy( $taxonomy );
	if ( $tax_object && in_array( 'bp_group', $tax_object->object_type, true ) ) {
		return bp_groups_reset_cache_incrementor();
	}

	return false;
}
add_action( 'bp_set_object_terms', 'bp_groups_reset_cache_incrementor_on_group_term_change', 10, 4 );

/**
 * Reset cache incrementor for Groups component when a group's taxonomy terms are removed.
 *
 * We infer that a group is being affected by looking at the objects belonging
 * to the taxonomy being affected.
 *
 * @since 2.7.0
 *
 * @param int    $object_id ID of the item whose terms are being modified.
 * @param array  $terms     Array of object terms.
 * @param string $taxonomy  Taxonomy slug.
 * @return bool True on success, false on failure.
 */
function bp_groups_reset_cache_incrementor_on_group_term_remove( $object_id, $terms, $taxonomy ) {
	$tax_object = get_taxonomy( $taxonomy );
	if ( $tax_object && in_array( 'bp_group', $tax_object->object_type, true ) ) {
		return bp_groups_reset_cache_incrementor();
	}

	return false;
}
add_action( 'bp_remove_object_terms', 'bp_groups_reset_cache_incrementor_on_group_term_remove', 10, 3 );

/* List actions to clear super cached pages on, if super cache is installed */
add_action( 'groups_join_group',                 'bp_core_clear_cache' );
add_action( 'groups_leave_group',                'bp_core_clear_cache' );
add_action( 'groups_accept_invite',              'bp_core_clear_cache' );
add_action( 'groups_reject_invite',              'bp_core_clear_cache' );
add_action( 'groups_invite_user',                'bp_core_clear_cache' );
add_action( 'groups_uninvite_user',              'bp_core_clear_cache' );
add_action( 'groups_details_updated',            'bp_core_clear_cache' );
add_action( 'groups_settings_updated',           'bp_core_clear_cache' );
add_action( 'groups_unban_member',               'bp_core_clear_cache' );
add_action( 'groups_ban_member',                 'bp_core_clear_cache' );
add_action( 'groups_demote_member',              'bp_core_clear_cache' );
add_action( 'groups_promote_member',             'bp_core_clear_cache' );
add_action( 'groups_membership_rejected',        'bp_core_clear_cache' );
add_action( 'groups_membership_accepted',        'bp_core_clear_cache' );
add_action( 'groups_membership_requested',       'bp_core_clear_cache' );
add_action( 'groups_create_group_step_complete', 'bp_core_clear_cache' );
add_action( 'groups_created_group',              'bp_core_clear_cache' );
add_action( 'groups_group_avatar_updated',       'bp_core_clear_cache' );
