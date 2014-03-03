<?php

/**
 * BuddyPress XProfile Caching Functions
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyPress
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Slurp up xprofilemeta for a specified set of profile objects.
 *
 * We do not use bp_update_meta_cache() for the xprofile component. This is
 * because the xprofile component has three separate object types (group,
 * field, and data) and three corresponding cache groups. Using the technique
 * in bp_update_meta_cache(), pre-fetching would take three separate database
 * queries. By grouping them together, we can reduce the required queries to
 * one.
 *
 * This function is called within a bp_has_profile() loop.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param array $object_ids Multi-dimensional array of object_ids, keyed by
 *        object type ('group', 'field', 'data')
 */
function bp_xprofile_update_meta_cache( $object_ids = array(), $user_id = 0 ) {
	global $wpdb;

	if ( empty( $object_ids ) ) {
		return false;
	}

	// $object_ids is a multi-dimensional array
	$uncached_object_ids = array(
		'group' => array(),
		'field' => array(),
		'data'   => array(),
	);

	$cache_groups = array(
		'group' => 'xprofile_group_meta',
		'field' => 'xprofile_field_meta',
		'data'  => 'xprofile_data_meta',
	);

	$do_query = false;
	foreach ( $uncached_object_ids as $object_type => $uncached_object_type_ids ) {
		if ( ! empty( $object_ids[ $object_type ] ) ) {
			// Sanitize $object_ids passed to the function
			$object_type_ids = wp_parse_id_list( $object_ids[ $object_type ] );

			// Get non-cached IDs for each object type
			$uncached_object_ids[ $object_type ] = bp_get_non_cached_ids( $object_type_ids, $cache_groups[ $object_type ] );

			// Set the flag to do the meta query
			if ( ! empty( $uncached_object_ids[ $object_type ] ) && ! $do_query ) {
				$do_query = true;
			}
		}
	}

	// If there are uncached items, go ahead with the query
	if ( $do_query ) {
		$where = array();
		foreach ( $uncached_object_ids as $otype => $oids ) {
			if ( empty( $oids ) ) {
				continue;
			}

			$oids_sql = implode( ',', wp_parse_id_list( $oids ) );
			$where[]  = $wpdb->prepare( "( object_type = %s AND object_id IN ({$oids_sql}) )", $otype );
		}
		$where_sql = implode( " OR ", $where );
	}


	$bp = buddypress();
	$meta_list = $wpdb->get_results( "SELECT object_id, object_type, meta_key, meta_value FROM {$bp->profile->table_name_meta} WHERE {$where_sql}" );

	if ( ! empty( $meta_list ) ) {
		$object_type_caches = array(
			'group' => array(),
			'field' => array(),
			'data'  => array(),
		);

		foreach ( $meta_list as $meta ) {
			$oid    = $meta->object_id;
			$otype  = $meta->object_type;
			$okey   = $meta->meta_key;
			$ovalue = $meta->meta_value;

			// Force subkeys to be array type
			if ( ! isset( $cache[ $otype ][ $oid ] ) || ! is_array( $cache[ $otype ][ $oid ] ) ) {
				$cache[ $otype ][ $oid ] = array();
			}

			if ( ! isset( $cache[ $otype ][ $oid ][ $okey ] ) || ! is_array( $cache[ $otype ][ $oid ][ $okey ] ) ) {
				$cache[ $otype ][ $oid ][ $okey ] = array();
			}

			// Add to the cache array
			$cache[ $otype ][ $oid ][ $okey ][] = maybe_unserialize( $ovalue );
		}

		foreach ( $cache as $object_type => $object_caches ) {
			$cache_group = $cache_groups[ $object_type ];
			foreach ( $object_caches as $object_id => $object_cache ) {
				wp_cache_set( $object_id, $object_cache, $cache_group );
			}
		}
	}

	return;
}

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
 * Clear the fullname cache when field 1 is updated.
 *
 * xprofile_clear_profile_data_object_cache() will make this redundant in most
 * cases, except where the field is updated directly with xprofile_set_field_data()
 *
 * @since BuddyPress (2.0.0)
 */
function xprofile_clear_fullname_cache_on_profile_field_edit( $data ) {
	if ( 1 == $data->field_id ) {
		wp_cache_delete( 'bp_user_fullname_' . $data->user_id, 'bp' );
	}
}
add_action( 'xprofile_data_after_save', 'xprofile_clear_fullname_cache_on_profile_field_edit' );

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
 * @param BP_XProfile_ProfileData $data_obj
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
