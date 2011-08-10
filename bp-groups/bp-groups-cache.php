<?php
/**
 * BuddyPress Groups Caching
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyPress
 * @subpackage Groups
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function groups_clear_group_object_cache( $group_id ) {
	wp_cache_delete( 'bp_total_group_count', 'bp' );
}
add_action( 'groups_group_deleted',              'groups_clear_group_object_cache' );
add_action( 'groups_settings_updated',           'groups_clear_group_object_cache' );
add_action( 'groups_details_updated',            'groups_clear_group_object_cache' );
add_action( 'groups_group_avatar_updated',       'groups_clear_group_object_cache' );
add_action( 'groups_create_group_step_complete', 'groups_clear_group_object_cache' );

function groups_clear_group_user_object_cache( $group_id, $user_id ) {
	wp_cache_delete( 'bp_total_groups_for_user_' . $user_id );
}
add_action( 'groups_join_group',   'groups_clear_group_user_object_cache', 10, 2 );
add_action( 'groups_leave_group',  'groups_clear_group_user_object_cache', 10, 2 );
add_action( 'groups_ban_member',   'groups_clear_group_user_object_cache', 10, 2 );
add_action( 'groups_unban_member', 'groups_clear_group_user_object_cache', 10, 2 );

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
add_action( 'groups_premote_member',             'bp_core_clear_cache' );
add_action( 'groups_membership_rejected',        'bp_core_clear_cache' );
add_action( 'groups_membership_accepted',        'bp_core_clear_cache' );
add_action( 'groups_membership_requested',       'bp_core_clear_cache' );
add_action( 'groups_create_group_step_complete', 'bp_core_clear_cache' );
add_action( 'groups_created_group',              'bp_core_clear_cache' );
add_action( 'groups_group_avatar_updated',       'bp_core_clear_cache' );

?>