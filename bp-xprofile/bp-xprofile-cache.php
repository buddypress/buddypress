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
add_action( 'xprofile_group_after_delete', 'xprofile_clear_profile_groups_object_cache' );
add_action( 'xprofile_group_after_save',   'xprofile_clear_profile_groups_object_cache' );

function xprofile_clear_profile_data_object_cache( $group_id ) {
	wp_cache_delete( 'bp_user_fullname_' . bp_loggedin_user_id(), 'bp' );
}
add_action( 'xprofile_updated_profile', 'xprofile_clear_profile_data_object_cache'   );

/**
 * Clear caches when a field object is modified.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param BP_XProfile_Field
 */
function xprofile_clear_profile_field_object_cache( $field_obj ) {
	// Clear default visibility level cache
	wp_cache_delete( 'xprofile_default_visibility_levels', 'bp' );

	// Modified fields can alter parent group status, in particular when
	// the group goes from empty to non-empty. Bust its cache, as well as
	// the global group_inc_empty cache
	wp_cache_delete( 'xprofile_group_' . $field_obj->group_id, 'bp' );
	wp_cache_delete( 'xprofile_groups_inc_empty', 'bp' );
}
add_action( 'xprofile_fields_saved_field', 'xprofile_clear_profile_field_object_cache' );
add_action( 'xprofile_fields_deleted_field', 'xprofile_clear_profile_field_object_cache' );

/**
 * Clear caches when a user's updates a field data object.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param BP_XProfile_ProfileData
 */
function xprofile_clear_profiledata_object_cache( $data_obj ) {
	wp_cache_delete( $data_obj->field_id, 'bp_xprofile_data_' . $data_obj->user_id );
}
add_action( 'xprofile_data_after_save', 'xprofile_clear_profiledata_object_cache' );
add_action( 'xprofile_data_after_delete', 'xprofile_clear_profiledata_object_cache' );

/**
 * Clear fullname_field_id cache when bp-xprofile-fullname-field-name is updated.
 *
 * Note for future developers: Dating from an early version of BuddyPress where
 * the fullname field (field #1) did not have a title that was editable in the
 * normal Profile Fields admin interface, we have the bp-xprofile-fullname-field-name
 * option. In many places throughout BuddyPress, the ID of the fullname field
 * is queried using this setting. However, this is no longer strictly necessary,
 * because we essentially hardcode (in the xprofile admin save routine, as well
 * as the xprofile schema definition) that the fullname field will be 1. The
 * presence of the non-hardcoded versions (and thus this bit of cache
 * invalidation) is thus for backward compatibility only.
 *
 * @since BuddyPress (2.0.0)
 */
function xprofile_clear_fullname_field_id_cache() {
	wp_cache_delete( 'fullname_field_id', 'bp_xprofile' );
}
add_action( 'update_option_bp-xprofile-fullname-field-name', 'xprofile_clear_fullname_field_id_cache' );

// List actions to clear super cached pages on, if super cache is installed
add_action( 'xprofile_updated_profile', 'bp_core_clear_cache' );
