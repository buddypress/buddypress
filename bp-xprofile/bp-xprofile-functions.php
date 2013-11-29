<?php

/**
 * BuddyPress XProfile Filters
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyPress
 * @subpackage XProfileFilters
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/*** Field Group Management **************************************************/

function xprofile_insert_field_group( $args = '' ) {
	$defaults = array(
		'field_group_id' => false,
		'name'           => false,
		'description'    => '',
		'can_delete'     => true
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( empty( $name ) )
		return false;

	$field_group              = new BP_XProfile_Group( $field_group_id );
	$field_group->name        = $name;
	$field_group->description = $description;
	$field_group->can_delete  = $can_delete;

	return $field_group->save();
}

function xprofile_get_field_group( $field_group_id ) {
	$field_group = new BP_XProfile_Group( $field_group_id );

	if ( empty( $field_group->id ) )
		return false;

	return $field_group;
}

function xprofile_delete_field_group( $field_group_id ) {
	$field_group = new BP_XProfile_Group( $field_group_id );
	return $field_group->delete();
}

function xprofile_update_field_group_position( $field_group_id, $position ) {
	return BP_XProfile_Group::update_position( $field_group_id, $position );
}


/*** Field Management *********************************************************/

function xprofile_insert_field( $args = '' ) {
	global $bp;

	extract( $args );

	/**
	 * Possible parameters (pass as assoc array):
	 *	'field_id'
	 *	'field_group_id'
	 *	'parent_id'
	 *	'type'
	 *	'name'
	 *	'description'
	 *	'is_required'
	 *	'can_delete'
	 *	'field_order'
	 *	'order_by'
	 *	'is_default_option'
	 *	'option_order'
	 */

	// Check we have the minimum details
	if ( empty( $field_group_id ) )
		return false;

	// Check this is a valid field type
	if ( !in_array( $type, (array) $bp->profile->field_types ) )
		return false;

	// Instantiate a new field object
	if ( !empty( $field_id ) )
		$field = new BP_XProfile_Field( $field_id );
	else
		$field = new BP_XProfile_Field;

	$field->group_id = $field_group_id;

	if ( !empty( $parent_id ) )
		$field->parent_id = $parent_id;

	if ( !empty( $type ) )
		$field->type = $type;

	if ( !empty( $name ) )
		$field->name = $name;

	if ( !empty( $description ) )
		$field->description = $description;

	if ( !empty( $is_required ) )
		$field->is_required = $is_required;

	if ( !empty( $can_delete ) )
		$field->can_delete = $can_delete;

	if ( !empty( $field_order ) )
		$field->field_order = $field_order;

	if ( !empty( $order_by ) )
		$field->order_by = $order_by;

	if ( !empty( $is_default_option ) )
		$field->is_default_option = $is_default_option;

	if ( !empty( $option_order ) )
		$field->option_order = $option_order;

	return $field->save();
}

function xprofile_get_field( $field_id ) {
	return new BP_XProfile_Field( $field_id );
}

function xprofile_delete_field( $field_id ) {
	$field = new BP_XProfile_Field( $field_id );
	return $field->delete();
}


/*** Field Data Management *****************************************************/

/**
 * Fetches profile data for a specific field for the user.
 *
 * When the field value is serialized, this function unserializes and filters each item in the array
 * that results.
 *
 * @package BuddyPress Core
 * @param mixed $field The ID of the field, or the $name of the field.
 * @param int $user_id The ID of the user
 * @param string $multi_format How should array data be returned? 'comma' if you want a
 *   comma-separated string; 'array' if you want an array
 * @uses BP_XProfile_ProfileData::get_value_byid() Fetches the value based on the params passed.
 * @return mixed The profile field data.
 */
function xprofile_get_field_data( $field, $user_id = 0, $multi_format = 'array' ) {

	if ( empty( $user_id ) )
		$user_id = bp_displayed_user_id();

	if ( empty( $user_id ) )
		return false;

	if ( is_numeric( $field ) )
		$field_id = $field;
	else
		$field_id = xprofile_get_field_id_from_name( $field );

	if ( empty( $field_id ) )
		return false;

	$values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $field_id, $user_id ) );

	if ( is_array( $values ) ) {
		$data = array();
		foreach( (array) $values as $value ) {
			$data[] = apply_filters( 'xprofile_get_field_data', $value, $field_id, $user_id );
		}

		if ( 'comma' == $multi_format ) {
			$data = implode( ', ', $data );
		}
	} else {
		$data = apply_filters( 'xprofile_get_field_data', $values, $field_id, $user_id );
	}

	return $data;
}

/**
 * A simple function to set profile data for a specific field for a specific user.
 *
 * @package BuddyPress Core
 * @param int|string $field The ID of the field, or the $name of the field.
 * @param int|$user_id The ID of the user
 * @param mixed The value for the field you want to set for the user.
 * @global BuddyPress $bp The one true BuddyPress instance
 * @uses xprofile_get_field_id_from_name() Gets the ID for the field based on the name.
 * @return bool True on success, false on failure.
 */
function xprofile_set_field_data( $field, $user_id, $value, $is_required = false ) {

	if ( is_numeric( $field ) )
		$field_id = $field;
	else
		$field_id = xprofile_get_field_id_from_name( $field );

	if ( empty( $field_id ) )
		return false;

	if ( $is_required && ( empty( $value ) || !is_array( $value ) && !strlen( trim( $value ) ) ) )
		return false;

	$field = new BP_XProfile_Field( $field_id );

	// If the value is empty, then delete any field data that exists, unless the field is of a
	// type where null values are semantically meaningful
	if ( empty( $value ) && 'checkbox' != $field->type && 'multiselectbox' != $field->type ) {
		xprofile_delete_field_data( $field_id, $user_id );
		return true;
	}

	$possible_values = array();

	// Check the value is an acceptable value
	if ( 'checkbox' == $field->type || 'radio' == $field->type || 'selectbox' == $field->type || 'multiselectbox' == $field->type ) {
		$options = $field->get_children();

		foreach( $options as $option )
			$possible_values[] = $option->name;

		if ( is_array( $value ) ) {
			foreach( $value as $i => $single ) {
				if ( !in_array( $single, $possible_values ) ) {
					unset( $value[$i] );
				}
			}

			// Reset the keys by merging with an empty array
			$value = array_merge( array(), $value );
		} else {
			if ( !in_array( $value, $possible_values ) ) {
				return false;
			}
		}
	}

	$field           = new BP_XProfile_ProfileData();
	$field->field_id = $field_id;
	$field->user_id  = $user_id;
	$field->value    = maybe_serialize( $value );

	return $field->save();
}

/**
 * Set the visibility level for this field
 *
 * @param int $field_id The ID of the xprofile field
 * @param int $user_id The ID of the user to whom the data belongs
 * @param string $visibility_level
 * @return bool True on success
 */
function xprofile_set_field_visibility_level( $field_id = 0, $user_id = 0, $visibility_level = '' ) {
	if ( empty( $field_id ) || empty( $user_id ) || empty( $visibility_level ) ) {
		return false;
	}

	// Check against a whitelist
	$allowed_values = bp_xprofile_get_visibility_levels();
	if ( !array_key_exists( $visibility_level, $allowed_values ) ) {
		return false;
	}

	// Stored in an array in usermeta
	$current_visibility_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );

	if ( !$current_visibility_levels ) {
		$current_visibility_levels = array();
	}

	$current_visibility_levels[$field_id] = $visibility_level;

	return bp_update_user_meta( $user_id, 'bp_xprofile_visibility_levels', $current_visibility_levels );
}

function xprofile_delete_field_data( $field, $user_id ) {
	if ( is_numeric( $field ) )
		$field_id = $field;
	else
		$field_id = xprofile_get_field_id_from_name( $field );

	if ( empty( $field_id ) || empty( $user_id ) )
		return false;

	$field = new BP_XProfile_ProfileData( $field_id, $user_id );
	return $field->delete();
}

function xprofile_check_is_required_field( $field_id ) {
	$field = new BP_Xprofile_Field( $field_id );

	// Define locale variable(s)
	$retval = false;

	// Super admins can skip required check
	if ( bp_current_user_can( 'bp_moderate' ) && !is_admin() )
		$retval = false;

	// All other users will use the field's setting
	elseif ( isset( $field->is_required ) )
		$retval = $field->is_required;

	return (bool) $retval;
}

/**
 * Returns the ID for the field based on the field name.
 *
 * @package BuddyPress Core
 * @param string $field_name The name of the field to get the ID for.
 * @return int $field_id on success, false on failure.
 */
function xprofile_get_field_id_from_name( $field_name ) {
	return BP_Xprofile_Field::get_id_from_name( $field_name );
}

/**
 * Fetches a random piece of profile data for the user.
 *
 * @package BuddyPress Core
 * @param int $user_id User ID of the user to get random data for
 * @param bool $exclude_fullname Optional; whether or not to exclude the full name field as random data. Defaults to true.
 * @global BuddyPress $bp The one true BuddyPress instance
 * @global $wpdb WordPress DB access object.
 * @global $current_user WordPress global variable containing current logged in user information
 * @uses xprofile_format_profile_field() Formats profile field data so it is suitable for display.
 * @return string|bool The fetched random data for the user, or false if no data or no match.
 */
function xprofile_get_random_profile_data( $user_id, $exclude_fullname = true ) {
	$field_data = BP_XProfile_ProfileData::get_random( $user_id, $exclude_fullname );

	if ( empty( $field_data ) )
		return false;

	$field_data[0]->value = xprofile_format_profile_field( $field_data[0]->type, $field_data[0]->value );

	if ( empty( $field_data[0]->value ) )
		return false;

	return apply_filters( 'xprofile_get_random_profile_data', $field_data );
}

/**
 * Formats a profile field according to its type. [ TODO: Should really be moved to filters ]
 *
 * @package BuddyPress Core
 * @param string $field_type The type of field: datebox, selectbox, textbox etc
 * @param string $field_value The actual value
 * @uses bp_format_time() Formats a time value based on the WordPress date format setting
 * @return string|bool The formatted value, or false if value is empty
 */
function xprofile_format_profile_field( $field_type, $field_value ) {
	if ( !isset( $field_value ) || empty( $field_value ) )
		return false;

	$field_value = bp_unserialize_profile_field( $field_value );

	if ( 'datebox' == $field_type ) {
		$field_value = bp_format_time( $field_value, true );
	} else {
		$content = $field_value;
		$field_value = str_replace( ']]>', ']]&gt;', $content );
	}

	return stripslashes_deep( $field_value );
}

function xprofile_update_field_position( $field_id, $position, $field_group_id ) {
	return BP_XProfile_Field::update_position( $field_id, $position, $field_group_id );
}

/**
 * Setup the avatar upload directory for a user.
 *
 * @package BuddyPress Core
 * @param string $directory The root directory name. Optional.
 * @param int $user_id The user ID. Optional.
 * @return array() containing the path and URL plus some other settings.
 */
function xprofile_avatar_upload_dir( $directory = false, $user_id = 0 ) {

	if ( empty( $user_id ) )
		$user_id = bp_displayed_user_id();

	if ( empty( $directory ) )
		$directory = 'avatars';

	$path    = bp_core_avatar_upload_path() . '/avatars/' . $user_id;
	$newbdir = $path;

	if ( !file_exists( $path ) )
		@wp_mkdir_p( $path );

	$newurl    = bp_core_avatar_url() . '/avatars/' . $user_id;
	$newburl   = $newurl;
	$newsubdir = '/avatars/' . $user_id;

	return apply_filters( 'xprofile_avatar_upload_dir', array(
		'path'    => $path,
		'url'     => $newurl,
		'subdir'  => $newsubdir,
		'basedir' => $newbdir,
		'baseurl' => $newburl,
		'error'   => false
	) );
}

/**
 * Syncs Xprofile data to the standard built in WordPress profile data.
 *
 * @package BuddyPress Core
 */
function xprofile_sync_wp_profile( $user_id = 0 ) {

	$bp = buddypress();

	if ( !empty( $bp->site_options['bp-disable-profile-sync'] ) && (int) $bp->site_options['bp-disable-profile-sync'] )
		return true;

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	if ( empty( $user_id ) )
		return false;

	$fullname = xprofile_get_field_data( bp_xprofile_fullname_field_name(), $user_id );
	$space    = strpos( $fullname, ' ' );

	if ( false === $space ) {
		$firstname = $fullname;
		$lastname = '';
	} else {
		$firstname = substr( $fullname, 0, $space );
		$lastname = trim( substr( $fullname, $space, strlen( $fullname ) ) );
	}

	bp_update_user_meta( $user_id, 'nickname',   $fullname  );
	bp_update_user_meta( $user_id, 'first_name', $firstname );
	bp_update_user_meta( $user_id, 'last_name',  $lastname  );

	global $wpdb;

	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET display_name = %s WHERE ID = %d", $fullname, $user_id ) );
}
add_action( 'xprofile_updated_profile', 'xprofile_sync_wp_profile' );
add_action( 'bp_core_signup_user',      'xprofile_sync_wp_profile' );
add_action( 'bp_core_activated_user',   'xprofile_sync_wp_profile' );


/**
 * Syncs the standard built in WordPress profile data to XProfile.
 *
 * @since BuddyPress (1.2.4)
 * @package BuddyPress Core
 */
function xprofile_sync_bp_profile( &$errors, $update, &$user ) {
	global $bp;

	if ( ( !empty( $bp->site_options['bp-disable-profile-sync'] ) && (int) $bp->site_options['bp-disable-profile-sync'] ) || !$update || $errors->get_error_codes() )
		return;

	xprofile_set_field_data( bp_xprofile_fullname_field_name(), $user->ID, $user->display_name );
}
add_action( 'user_profile_update_errors', 'xprofile_sync_bp_profile', 10, 3 );


/**
 * When a user is deleted, we need to clean up the database and remove all the
 * profile data from each table. Also we need to clean anything up in the
 * usermeta table that this component uses.
 *
 * @package BuddyPress XProfile
 * @param int $user_id The ID of the deleted user
 */
function xprofile_remove_data( $user_id ) {
	BP_XProfile_ProfileData::delete_data_for_user( $user_id );
}
add_action( 'wpmu_delete_user',  'xprofile_remove_data' );
add_action( 'delete_user',       'xprofile_remove_data' );
add_action( 'bp_make_spam_user', 'xprofile_remove_data' );

/*** XProfile Meta ****************************************************/

function bp_xprofile_delete_meta( $object_id, $object_type, $meta_key = false, $meta_value = false ) {
	global $wpdb, $bp;

	$object_id = (int) $object_id;

	if ( !$object_id )
		return false;

	if ( !isset( $object_type ) )
		return false;

	if ( !in_array( $object_type, array( 'group', 'field', 'data' ) ) )
		return false;

	$meta_key = preg_replace( '|[^a-z0-9_]|i', '', $meta_key );

	if ( is_array( $meta_value ) || is_object( $meta_value ) ) {
		$meta_value = serialize( $meta_value );
	}

	$meta_value = trim( $meta_value );

	if ( empty( $meta_key ) ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_meta} WHERE object_id = %d AND object_type = %s", $object_id, $object_type ) );
	} elseif ( !empty( $meta_value ) ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_meta} WHERE object_id = %d AND object_type = %s AND meta_key = %s AND meta_value = %s", $object_id, $object_type, $meta_key, $meta_value ) );
	} else {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_meta} WHERE object_id = %d AND object_type = %s AND meta_key = %s", $object_id, $object_type, $meta_key ) );
	}

	// Delete the cached object
	wp_cache_delete( 'bp_xprofile_meta_' . $object_type . '_' . $object_id . '_' . $meta_key, 'bp' );

	return true;
}

function bp_xprofile_get_meta( $object_id, $object_type, $meta_key = '') {
	global $wpdb, $bp;

	$object_id = (int) $object_id;

	if ( !$object_id )
		return false;

	if ( !isset( $object_type ) )
		return false;

	if ( !in_array( $object_type, array( 'group', 'field', 'data' ) ) )
		return false;

	if ( !empty( $meta_key ) ) {
		$meta_key = preg_replace( '|[^a-z0-9_]|i', '', $meta_key );

		if ( !$metas = wp_cache_get( 'bp_xprofile_meta_' . $object_type . '_' . $object_id . '_' . $meta_key, 'bp' ) ) {
			$metas = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$bp->profile->table_name_meta} WHERE object_id = %d AND object_type = %s AND meta_key = %s", $object_id, $object_type, $meta_key ) );
			wp_cache_set( 'bp_xprofile_meta_' . $object_type . '_' . $object_id . '_' . $meta_key, $metas, 'bp' );
		}
	} else {
		$metas = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$bp->profile->table_name_meta} WHERE object_id = %d AND object_type = %s", $object_id, $object_type ) );
	}

	if ( empty( $metas ) ) {
		if ( empty( $meta_key ) ) {
			return array();
		} else {
			return '';
		}
	}

	$metas = array_map( 'maybe_unserialize', (array) $metas );

	if ( 1 == count( $metas ) )
		return $metas[0];
	else
		return $metas;
}

function bp_xprofile_update_meta( $object_id, $object_type, $meta_key, $meta_value ) {
	global $wpdb, $bp;

	$object_id = (int) $object_id;

	if ( empty( $object_id ) )
		return false;

	if ( !isset( $object_type ) )
		return false;

	if ( !in_array( $object_type, array( 'group', 'field', 'data' ) ) )
		return false;

	$meta_key = preg_replace( '|[^a-z0-9_]|i', '', $meta_key );

	if ( is_string( $meta_value ) ) {
		$meta_value = stripslashes( $meta_value );
	}

	$meta_value = maybe_serialize( $meta_value );

	if ( empty( $meta_value ) )
		return bp_xprofile_delete_meta( $object_id, $object_type, $meta_key );

	$cur = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_meta} WHERE object_id = %d AND object_type = %s AND meta_key = %s", $object_id, $object_type, $meta_key ) );

	if ( empty( $cur ) )
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->profile->table_name_meta} ( object_id, object_type, meta_key, meta_value ) VALUES ( %d, %s, %s, %s )", $object_id, $object_type,  $meta_key, $meta_value ) );
	else if ( $cur->meta_value != $meta_value )
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_meta} SET meta_value = %s WHERE object_id = %d AND object_type = %s AND meta_key = %s", $meta_value, $object_id, $object_type, $meta_key ) );
	else
		return false;

	// Update the cached object and recache
	wp_cache_set( 'bp_xprofile_meta_' . $object_type . '_' . $object_id . '_' . $meta_key, $meta_value, 'bp' );

	return true;
}

function bp_xprofile_update_fieldgroup_meta( $field_group_id, $meta_key, $meta_value ) {
	return bp_xprofile_update_meta( $field_group_id, 'group', $meta_key, $meta_value );
}

function bp_xprofile_update_field_meta( $field_id, $meta_key, $meta_value ) {
	return bp_xprofile_update_meta( $field_id, 'field', $meta_key, $meta_value );
}

function bp_xprofile_update_fielddata_meta( $field_data_id, $meta_key, $meta_value ) {
	return bp_xprofile_update_meta( $field_data_id, 'data', $meta_key, $meta_value );
}

/**
 * Return the field name for the Full Name xprofile field
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return string The field name
 */
function bp_xprofile_fullname_field_name() {
	return apply_filters( 'bp_xprofile_fullname_field_name', BP_XPROFILE_FULLNAME_FIELD_NAME );
}

/**
 * Get visibility levels out of the $bp global
 *
 * @return array
 */
function bp_xprofile_get_visibility_levels() {
	global $bp;

	return apply_filters( 'bp_xprofile_get_visibility_levels', $bp->profile->visibility_levels );
}

/**
 * Get the ids of fields that are hidden for this displayed/loggedin user pair
 *
 * This is the function primarily responsible for profile field visibility. It works by determining
 * the relationship between the displayed_user (ie the profile owner) and the current_user (ie the
 * profile viewer). Then, based on that relationship, we query for the set of fields that should
 * be excluded from the profile loop.
 *
 * @since BuddyPress (1.6)
 * @see BP_XProfile_Group::get()
 * @uses apply_filters() Filter bp_xprofile_get_hidden_fields_for_user to modify visibility levels,
 *   or if you have added your own custom levels
 *
 * @param int $displayed_user_id The id of the user the profile fields belong to
 * @param int $current_user_id The id of the user viewing the profile
 * @return array An array of field ids that should be excluded from the profile query
 */
function bp_xprofile_get_hidden_fields_for_user( $displayed_user_id = 0, $current_user_id = 0 ) {
	if ( !$displayed_user_id ) {
		$displayed_user_id = bp_displayed_user_id();
	}

	if ( !$displayed_user_id ) {
		return array();
	}

	if ( !$current_user_id ) {
		$current_user_id = bp_loggedin_user_id();
	}

	// @todo - This is where you'd swap out for current_user_can() checks
	$hidden_levels = bp_xprofile_get_hidden_field_types_for_user( $displayed_user_id, $current_user_id );
	$hidden_fields = bp_xprofile_get_fields_by_visibility_levels( $displayed_user_id, $hidden_levels );

	return apply_filters( 'bp_xprofile_get_hidden_fields_for_user', $hidden_fields, $displayed_user_id, $current_user_id );
}

/**
 * Get the visibility levels that should be hidden for this user pair
 *
 * Field visibility is determined based on the relationship between the
 * logged-in user, the displayed user, and the visibility setting for the
 * current field. (See bp_xprofile_get_hidden_fields_for_user().) This
 * utility function speeds up this matching by fetching the visibility levels
 * that should be hidden for the current user pair.
 *
 * @since BuddyPress (1.8.2)
 * @see bp_xprofile_get_hidden_fields_for_user()
 *
 * @param int $displayed_user_id The id of the user the profile fields belong to
 * @param int $current_user_id The id of the user viewing the profile
 * @return array An array of visibility levels hidden to the current user
 */
function bp_xprofile_get_hidden_field_types_for_user( $displayed_user_id = 0, $current_user_id = 0 ) {

	// Current user is logged in
	if ( ! empty( $current_user_id ) ) {

		// Nothing's private when viewing your own profile, or when the
		// current user is an admin
		if ( $displayed_user_id == $current_user_id || bp_current_user_can( 'bp_moderate' ) ) {
			$hidden_levels = array();

		// If the current user and displayed user are friends, show all
		} elseif ( bp_is_active( 'friends' ) && friends_check_friendship( $displayed_user_id, $current_user_id ) ) {
			$hidden_levels = array( 'adminsonly', );

		// current user is logged in but not friends, so exclude friends-only
		} else {
			$hidden_levels = array( 'friends', 'adminsonly', );
		}

	// Current user is not logged in, so exclude friends-only, loggedin, and adminsonly.
	} else {
		$hidden_levels = array( 'friends', 'loggedin', 'adminsonly', );
	}

	return $hidden_levels;
}

/**
 * Fetch an array of the xprofile fields that a given user has marked with certain visibility levels
 *
 * @since BuddyPress (1.6)
 * @see bp_xprofile_get_hidden_fields_for_user()
 *
 * @param int $user_id The id of the profile owner
 * @param array $levels An array of visibility levels ('public', 'friends', 'loggedin', 'adminsonly' etc) to be
 *    checked against
 * @return array $field_ids The fields that match the requested visibility levels for the given user
 */
function bp_xprofile_get_fields_by_visibility_levels( $user_id, $levels = array() ) {
	if ( !is_array( $levels ) ) {
		$levels = (array)$levels;
	}

	$user_visibility_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );

	// Parse the user-provided visibility levels with the default levels, which may take
	// precedence
	$default_visibility_levels = BP_XProfile_Group::fetch_default_visibility_levels();

	foreach( (array) $default_visibility_levels as $d_field_id => $defaults ) {
		// If the admin has forbidden custom visibility levels for this field, replace
		// the user-provided setting with the default specified by the admin
		if ( isset( $defaults['allow_custom'] ) && isset( $defaults['default'] ) && 'disabled' == $defaults['allow_custom'] ) {
			$user_visibility_levels[$d_field_id] = $defaults['default'];
		}
	}

	$field_ids = array();
	foreach( (array) $user_visibility_levels as $field_id => $field_visibility ) {
		if ( in_array( $field_visibility, $levels ) ) {
			$field_ids[] = $field_id;
		}
	}

	// Never allow the fullname field to be excluded
	if ( in_array( 1, $field_ids ) ) {
		$key = array_search( 1, $field_ids );
		unset( $field_ids[$key] );
	}

	return $field_ids;
}
