<?php

/**
 * BuddyPress XProfile Template Tags
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyPress
 * @subpackage XProfileTemplate
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function xprofile_clear_profile_groups_object_cache( $group_obj ) {
	wp_cache_delete( 'xprofile_groups_inc_empty',        'bp' );
	wp_cache_delete( 'xprofile_group_' . $group_obj->id, 'bp' );
}

function xprofile_clear_profile_data_object_cache( $group_id ) {
	wp_cache_delete( 'bp_user_fullname_' . bp_loggedin_user_id(), 'bp' );
}

// List actions to clear object caches on
add_action( 'xprofile_groups_deleted_group', 'xprofile_clear_profile_groups_object_cache' );
add_action( 'xprofile_groups_saved_group',   'xprofile_clear_profile_groups_object_cache' );
add_action( 'xprofile_updated_profile',      'xprofile_clear_profile_data_object_cache'   );

// List actions to clear super cached pages on, if super cache is installed
add_action( 'xprofile_updated_profile', 'bp_core_clear_cache' );

?>
