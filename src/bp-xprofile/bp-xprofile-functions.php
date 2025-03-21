<?php
/**
 * BuddyPress XProfile Filters.
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyPress
 * @subpackage XProfileFunctions
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*** Field Group Management **************************************************/

/**
 * Fetch a set of field groups, populated with fields and field data.
 *
 * Procedural wrapper for BP_XProfile_Group::get() method.
 *
 * @since 2.1.0
 *
 * @param array $args See {@link BP_XProfile_Group::get()} for description of arguments.
 * @return array $groups
 */
function bp_xprofile_get_groups( $args = array() ) {

	$groups = BP_XProfile_Group::get( $args );

	/**
	 * Filters a set of field groups, populated with fields and field data.
	 *
	 * @since 2.1.0
	 *
	 * @param array $groups Array of field groups and field data.
	 * @param array $args   Array of arguments used to query for groups.
	 */
	return apply_filters( 'bp_xprofile_get_groups', $groups, $args );
}

/**
 * Insert a new profile field group.
 *
 * @since 1.0.0
 *
 * @param array|string $args {
 *    Array of arguments for field group insertion.
 *
 *    @type int|bool    $field_group_id ID of the field group to insert into.
 *    @type string|bool $name           Name of the group.
 *    @type string      $description    Field group description.
 *    @type bool        $can_delete     Whether or not the field group can be deleted.
 * }
 * @return bool
 */
function xprofile_insert_field_group( $args = '' ) {

	// Parse the arguments.
	$r = bp_parse_args(
		$args,
		array(
			'field_group_id' => false,
			'name'           => false,
			'description'    => '',
			'can_delete'     => true,
		),
		'xprofile_insert_field_group'
	);

	// Bail if no group name.
	if ( empty( $r['name'] ) ) {
		return false;
	}

	// Create new field group object, maybe using an existing ID.
	$field_group              = new BP_XProfile_Group( $r['field_group_id'] );
	$field_group->name        = $r['name'];
	$field_group->description = $r['description'];
	$field_group->can_delete  = $r['can_delete'];

	return $field_group->save();
}

/**
 * Get a specific profile field group.
 *
 * @since 1.0.0
 *
 * @param int $field_group_id Field group ID to fetch.
 * @return false|BP_XProfile_Group
 */
function xprofile_get_field_group( $field_group_id = 0 ) {

	// Try to get a specific field group by ID.
	$field_group = new BP_XProfile_Group( $field_group_id );

	// Bail if group was not found.
	if ( empty( $field_group->id ) ) {
		return false;
	}

	// Return field group.
	return $field_group;
}

/**
 * Delete a specific profile field group.
 *
 * @since 1.0.0
 *
 * @param int $field_group_id Field group ID to delete.
 * @return bool
 */
function xprofile_delete_field_group( $field_group_id = 0 ) {

	// Try to get a specific field group by ID.
	$field_group = xprofile_get_field_group( $field_group_id );

	// Bail if group was not found.
	if ( false === $field_group ) {
		return false;
	}

	// Return the results of trying to delete the field group.
	return $field_group->delete();
}

/**
 * Update the position of a specific profile field group.
 *
 * @since 1.0.0
 *
 * @param int $field_group_id Field group ID to update.
 * @param int $position       Field group position to update to.
 * @return bool
 */
function xprofile_update_field_group_position( $field_group_id = 0, $position = 0 ) {
	return BP_XProfile_Group::update_position( $field_group_id, $position );
}

/*** Field Management *********************************************************/

/**
 * Get details of all xprofile field types.
 *
 * @since 2.0.0
 *
 * @return array Key/value pairs (field type => class name).
 */
function bp_xprofile_get_field_types() {
	$fields = array(
		'checkbox'            => 'BP_XProfile_Field_Type_Checkbox',
		'datebox'             => 'BP_XProfile_Field_Type_Datebox',
		'multiselectbox'      => 'BP_XProfile_Field_Type_Multiselectbox',
		'number'              => 'BP_XProfile_Field_Type_Number',
		'url'                 => 'BP_XProfile_Field_Type_URL',
		'radio'               => 'BP_XProfile_Field_Type_Radiobutton',
		'selectbox'           => 'BP_XProfile_Field_Type_Selectbox',
		'textarea'            => 'BP_XProfile_Field_Type_Textarea',
		'textbox'             => 'BP_XProfile_Field_Type_Textbox',
		'telephone'           => 'BP_XProfile_Field_Type_Telephone',
		'wp-biography'        => 'BP_XProfile_Field_Type_WordPress_Biography',
		'wp-textbox'          => 'BP_XProfile_Field_Type_WordPress_Textbox',
		'checkbox_acceptance' => 'BP_XProfile_Field_Type_Checkbox_Acceptance',
	);

	/**
	 * Filters the list of all xprofile field types.
	 *
	 * If you've added a custom field type in a plugin, register it with this filter.
	 *
	 * @since 2.0.0
	 *
	 * @param array $fields Array of field type/class name pairings.
	 */
	return apply_filters( 'bp_xprofile_get_field_types', $fields );
}

/**
 * Creates the specified field type object; used for validation and templating.
 *
 * @since 2.0.0
 *
 * @param string $type Type of profile field to create. See {@link bp_xprofile_get_field_types()} for default core values.
 * @return object $value If field type unknown, returns BP_XProfile_Field_Type_Textarea.
 *                       Otherwise returns an instance of the relevant child class of BP_XProfile_Field_Type.
 */
function bp_xprofile_create_field_type( $type ) {

	$field = bp_xprofile_get_field_types();
	$class = isset( $field[ $type ] ) ? $field[ $type ] : '';

	/**
	 * To handle (missing) field types, fallback to a placeholder field object if a type is unknown.
	 */
	if ( $class && class_exists( $class ) ) {
		return new $class;
	} else {
		return new BP_XProfile_Field_Type_Placeholder;
	}
}

/**
 * Insert or update an xprofile field.
 *
 * @since 1.1.0
 *
 * @param array|string $args {
 *     Array of arguments.
 *     @type int    $field_id          Optional. Pass the ID of an existing field to edit that field.
 *     @type int    $field_group_id    ID of the associated field group.
 *     @type int    $parent_id         Optional. ID of the parent field.
 *     @type string $type              Field type. Checked against a list of allowed field_types.
 *     @type string $name              Name of the new field.
 *     @type string $description       Optional. Descriptive text for the field.
 *     @type bool   $is_required       Optional. Whether users must provide a value for the field. Default: false.
 *     @type bool   $can_delete        Optional. Whether admins can delete this field in the Dashboard interface.
 *                                     Generally this is false only for the Name field, which is required throughout BP.
 *                                     Default: true.
 *     @type string $order_by          Optional. For field types that support options (such as 'radio'), this flag
 *                                     determines whether the sort order of the options will be 'default'
 *                                     (order created) or 'custom'.
 *     @type bool   $is_default_option Optional. For the 'option' field type, setting this value to true means that
 *                                     it'll be the default value for the parent field when the user has not yet
 *                                     overridden. Default: true.
 *     @type int    $option_order      Optional. For the 'option' field type, this determines the order in which the
 *                                     options appear.
 * }
 * @return bool|int False on failure, ID of new field on success.
 */
function xprofile_insert_field( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'field_id'          => null,
			'field_group_id'    => null,
			'parent_id'         => null,
			'type'              => '',
			'name'              => '',
			'description'       => '',
			'is_required'       => false,
			'can_delete'        => true,
			'order_by'          => '',
			'is_default_option' => false,
			'option_order'      => null,
			'field_order'       => null,
		)
	);

	// Field_group_id is required.
	if ( empty( $r['field_group_id'] ) ) {
		return false;
	}

	// Check this is a non-empty, valid field type.
	if ( ! in_array( $r['type'], (array) buddypress()->profile->field_types ) ) {
		return false;
	}

	// Instantiate a new field object.
	if ( ! empty( $r['field_id'] ) ) {
		$field = xprofile_get_field( $r['field_id'], null, false );
	} else {
		$field = new BP_XProfile_Field;
	}

	$field->group_id = $r['field_group_id'];
	$field->type     = $r['type'];

	// The 'name' field cannot be empty.
	if ( ! empty( $r['name'] ) ) {
		$field->name = $r['name'];
	}

	$field->description       = $r['description'];
	$field->order_by          = $r['order_by'];
	$field->parent_id         = (int) $r['parent_id'];
	$field->field_order       = (int) $r['field_order'];
	$field->option_order      = (int) $r['option_order'];
	$field->is_required       = (bool) $r['is_required'];
	$field->can_delete        = (bool) $r['can_delete'];
	$field->is_default_option = (bool) $r['is_default_option'];

	return $field->save();
}

/**
 * Get a profile field object.
 *
 * @since 1.1.0
 * @since 2.8.0 Added `$user_id` and `$get_data` parameters.
 *
 * @param int|object $field    ID of the field or object representing field data.
 * @param int|null   $user_id  Optional. ID of the user associated with the field.
 *                             Ignored if `$get_data` is false. If `$get_data` is
 *                             true, but no `$user_id` is provided, defaults to
 *                             logged-in user ID.
 * @param bool       $get_data Whether to fetch data for the specified `$user_id`.
 * @return BP_XProfile_Field|null Field object if found, otherwise null.
 */
function xprofile_get_field( $field, $user_id = null, $get_data = true ) {
	if ( $field instanceof BP_XProfile_Field ) {
		$_field = $field;
	} elseif ( is_object( $field ) ) {
		$_field = new BP_XProfile_Field();
		$_field->fill_data( $field );
	} else {
		$_field = BP_XProfile_Field::get_instance( $field, $user_id, $get_data );
	}

	if ( ! $_field ) {
		return null;
	}

	return $_field;
}

/**
 * Get a profile Field Type object.
 *
 * @since 8.0.0
 *
 * @param int $field_id ID of the field.
 * @return BP_XProfile_Field_Type|null Field Type object if found, otherwise null.
 */
function bp_xprofile_get_field_type( $field_id ) {
	$field_type = null;
	$field      = xprofile_get_field( $field_id, null, false );

	if ( $field instanceof BP_XProfile_Field ) {
		$field_type = $field->type_obj;
	}

	return $field_type;
}

/**
 * Delete a profile field object.
 *
 * @since 1.1.0
 *
 * @param int|object $field_id ID of the field or object representing field data.
 * @return bool Whether or not the field was deleted.
 */
function xprofile_delete_field( $field_id ) {
	$field = new BP_XProfile_Field( $field_id );
	return $field->delete();
}

/*** Field Data Management *****************************************************/


/**
 * Fetches profile data for a specific field for the user.
 *
 * When the field value is serialized, this function unserializes and filters
 * each item in the array.
 *
 * @since 1.0.0
 *
 * @param mixed  $field        The ID of the field, or the $name of the field.
 * @param int    $user_id      The ID of the user.
 * @param string $multi_format How should array data be returned? 'comma' if you want a
 *                             comma-separated string; 'array' if you want an array.
 * @return mixed The profile field data.
 */
function xprofile_get_field_data( $field, $user_id = 0, $multi_format = 'array' ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	if ( is_numeric( $field ) ) {
		$field_id = $field;
	} else {
		$field_id = xprofile_get_field_id_from_name( $field );
	}

	if ( empty( $field_id ) ) {
		return false;
	}

	$values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $field_id, $user_id ) );

	if ( is_array( $values ) ) {
		$data = array();
		foreach ( (array) $values as $value ) {

			/**
			 * Filters the field data value for a specific field for the user.
			 *
			 * @since 1.0.0
			 *
			 * @param string $value    Value saved for the field.
			 * @param int    $field_id ID of the field being displayed.
			 * @param int    $user_id  ID of the user being displayed.
			 */
			$data[] = apply_filters( 'xprofile_get_field_data', $value, $field_id, $user_id );
		}

		if ( 'comma' == $multi_format ) {
			$data = implode( ', ', $data );
		}
	} else {
		/** This filter is documented in bp-xprofile/bp-xprofile-functions.php */
		$data = apply_filters( 'xprofile_get_field_data', $values, $field_id, $user_id );
	}

	return $data;
}

/**
 * A simple function to set profile data for a specific field for a specific user.
 *
 * @since 1.0.0
 *
 * @param int|string $field       The ID of the field, or the $name of the field.
 * @param int        $user_id     The ID of the user.
 * @param mixed      $value       The value for the field you want to set for the user.
 * @param bool       $is_required Whether or not the field is required.
 * @return bool
 */
function xprofile_set_field_data( $field, $user_id, $value, $is_required = false ) {

	if ( is_numeric( $field ) ) {
		$field_id = $field;
	} else {
		$field_id = xprofile_get_field_id_from_name( $field );
	}

	if ( empty( $field_id ) ) {
		return false;
	}

	$field          = xprofile_get_field( $field_id );
	$field_type     = BP_XProfile_Field::get_type( $field_id );
	$field_type_obj = bp_xprofile_create_field_type( $field_type );

	/**
	 * Filter the raw submitted profile field value.
	 *
	 * Use this filter to modify the values submitted by users before
	 * doing field-type-specific validation.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed                  $value          Value passed to xprofile_set_field_data().
	 * @param BP_XProfile_Field      $field          Field object.
	 * @param BP_XProfile_Field_Type $field_type_obj Field type object.
	 */
	$value = apply_filters( 'bp_xprofile_set_field_data_pre_validate', $value, $field, $field_type_obj );

	// Special-case support for integer 0 for the number field type.
	if ( $is_required && ! is_integer( $value ) && $value !== '0' && ( empty( $value ) || ! is_array( $value ) && ! strlen( trim( $value ) ) ) ) {
		return false;
	}

	/**
	 * Certain types of fields (checkboxes, multiselects) may come through empty.
	 * Save as empty array so this isn't overwritten by the default on next edit.
	 *
	 * Special-case support for integer 0 for the number field type
	 */
	if ( empty( $value ) && ! is_integer( $value ) && $value !== '0' && $field_type_obj->accepts_null_value ) {
		$value = array();
	}

	// If the value is empty, then delete any field data that exists, unless the field is of a type
	// where null values are semantically meaningful.
	if ( empty( $value ) && ! is_integer( $value ) && $value !== '0' && ! $field_type_obj->accepts_null_value ) {
		xprofile_delete_field_data( $field_id, $user_id );
		return true;
	}

	// For certain fields, only certain parameters are acceptable, so add them to the list of allowed values.
	if ( $field_type_obj->supports_options ) {
		$field_type_obj->set_allowed_values( wp_list_pluck( $field->get_children(), 'name' ) );
	}

	// Check the value is in an accepted format for this form field.
	if ( ! $field_type_obj->is_valid( $value ) ) {
		return false;
	}

	$field_args = compact( 'field_type_obj', 'field', 'user_id', 'value', 'is_required' );

	/**
	 * Return a WP_Error object or true to use your custom way of saving field values.
	 *
	 * @since 8.0.0
	 *
	 * @param boolean Whether to shortcircuit the $bp->profile->table_name_data table.
	 * @param array $field_args {
	 *     An array of arguments.
	 *
	 *     @type object            $field_type_obj Field type object.
	 *     @type BP_XProfile_Field $field          Field object.
	 *     @type integer           $user_id        The user ID.
	 *     @type mixed             $value          Value passed to xprofile_set_field_data().
	 *     @type boolean           $is_required    Whether or not the field is required.
	 * }
	 */
	$retval = apply_filters( 'bp_xprofile_set_field_data_pre_save', false, $field_args );

	if ( is_wp_error( $retval ) ) {
		return false;
	}

	if ( false === $retval ) {
		$field           = new BP_XProfile_ProfileData();
		$field->field_id = $field_id;
		$field->user_id  = $user_id;

		// Gets un/reserialized via xprofile_sanitize_data_value_before_save().
		$field->value    = maybe_serialize( $value );

		$retval = $field->save();
	}

	return $retval;
}

/**
 * Set the visibility level for this field.
 *
 * @since 1.6.0
 *
 * @param int    $field_id         The ID of the xprofile field.
 * @param int    $user_id          The ID of the user to whom the data belongs.
 * @param string $visibility_level What the visibility setting should be.
 * @return bool
 */
function xprofile_set_field_visibility_level( $field_id = 0, $user_id = 0, $visibility_level = '' ) {
	if ( empty( $field_id ) || empty( $user_id ) || empty( $visibility_level ) ) {
		return false;
	}

	// Check against a list of registered visibility levels.
	$allowed_values = bp_xprofile_get_visibility_levels();
	if ( ! array_key_exists( $visibility_level, $allowed_values ) ) {
		return false;
	}

	// Stored in an array in usermeta.
	$current_visibility_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );

	if ( ! $current_visibility_levels ) {
		$current_visibility_levels = array();
	}

	$current_visibility_levels[ $field_id ] = $visibility_level;

	return bp_update_user_meta( $user_id, 'bp_xprofile_visibility_levels', $current_visibility_levels );
}

/**
 * Get the visibility level for a field.
 *
 * @since 2.0.0
 *
 * @param int $field_id The ID of the xprofile field.
 * @param int $user_id The ID of the user to whom the data belongs.
 * @return string
 */
function xprofile_get_field_visibility_level( $field_id = 0, $user_id = 0 ) {
	$current_level = '';

	if ( empty( $field_id ) || empty( $user_id ) ) {
		return $current_level;
	}

	$current_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );
	$current_level  = isset( $current_levels[ $field_id ] ) ? $current_levels[ $field_id ] : '';

	// Use the user's stored level, unless custom visibility is disabled.
	$field = xprofile_get_field( $field_id, null, false );
	if ( isset( $field->allow_custom_visibility ) && 'disabled' === $field->allow_custom_visibility ) {
		$current_level = $field->default_visibility;
	}

	// If we're still empty, it means that overrides are permitted, but the
	// user has not provided a value. Use the default value.
	if ( empty( $current_level ) ) {
		$current_level = $field->default_visibility;
	}

	return $current_level;
}

/**
 * Delete XProfile field data.
 *
 * @since 1.1.0
 *
 * @param string $field   Field to delete.
 * @param int    $user_id User ID to delete field from.
 * @return bool Whether or not the field was deleted.
 */
function xprofile_delete_field_data( $field = '', $user_id = 0 ) {

	// Get the field ID.
	if ( is_numeric( $field ) ) {
		$field_id = (int) $field;
	} else {
		$field_id = xprofile_get_field_id_from_name( $field );
	}

	// Bail if field or user ID are empty.
	if ( empty( $field_id ) || empty( $user_id ) ) {
		return false;
	}

	// Get the profile field data to delete.
	$field = new BP_XProfile_ProfileData( $field_id, $user_id );

	// Delete the field data.
	return $field->delete();
}

/**
 * Check if field is a required field.
 *
 * @since 1.1.0
 *
 * @param int $field_id ID of the field to check for.
 * @return bool Whether or not field is required.
 */
function xprofile_check_is_required_field( $field_id ) {
	$field  = new BP_XProfile_Field( $field_id );
	$retval = false;

	if ( isset( $field->is_required ) ) {
		$retval = $field->is_required;
	}

	return (bool) $retval;
}

/**
 * Returns the ID for the field based on the field name.
 *
 * @since 1.0.0
 *
 * @param string $field_name The name of the field to get the ID for.
 * @return int|null $field_id on success, false on failure.
 */
function xprofile_get_field_id_from_name( $field_name ) {
	return BP_XProfile_Field::get_id_from_name( $field_name );
}

/**
 * Fetches a random piece of profile data for the user.
 *
 * @since 1.0.0
 *
 * @param int  $user_id          User ID of the user to get random data for.
 * @param bool $exclude_fullname Optional; whether or not to exclude the full name field as random data.
 *                               Defaults to true.
 * @return string|bool The fetched random data for the user, or false if no data or no match.
 */
function xprofile_get_random_profile_data( $user_id, $exclude_fullname = true ) {
	$field_data = BP_XProfile_ProfileData::get_random( $user_id, $exclude_fullname );

	if ( empty( $field_data ) ) {
		return false;
	}

	$field_data[0]->value = xprofile_format_profile_field( $field_data[0]->type, $field_data[0]->value );

	if ( empty( $field_data[0]->value ) ) {
		return false;
	}

	/**
	 * Filters a random piece of profile data for the user.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field_data Array holding random profile data.
	 */
	return apply_filters( 'xprofile_get_random_profile_data', $field_data );
}

/**
 * Formats a profile field according to its type. [ TODO: Should really be moved to filters ]
 *
 * @since 1.0.0
 *
 * @param string $field_type  The type of field: datebox, selectbox, textbox etc.
 * @param string $field_value The actual value.
 * @return string|bool The formatted value, or false if value is empty.
 */
function xprofile_format_profile_field( $field_type, $field_value ) {

	if ( empty( $field_value ) ) {
		return false;
	}

	$field_value = bp_unserialize_profile_field( $field_value );

	if ( 'datebox' != $field_type ) {
		$content = $field_value;
		$field_value = str_replace( ']]>', ']]&gt;', $content );
	}

	return xprofile_filter_format_field_value_by_type( stripslashes_deep( $field_value ), $field_type );
}

/**
 * Update the field position for a provided field.
 *
 * @since 1.1.0
 *
 * @param int $field_id       ID of the field to update.
 * @param int $position       Position to update the field to.
 * @param int $field_group_id Group ID for group the field is in.
 * @return bool
 */
function xprofile_update_field_position( $field_id, $position, $field_group_id ) {
	return BP_XProfile_Field::update_position( $field_id, $position, $field_group_id );
}

/**
 * Replace the displayed and logged-in users fullnames with the xprofile name, if required.
 *
 * The Members component uses the logged-in user's display_name to set the
 * value of buddypress()->loggedin_user->fullname. However, in cases where
 * profile sync is disabled, display_name may diverge from the xprofile
 * fullname field value, and the xprofile field should take precedence.
 *
 * Runs at bp_setup_globals:100 to ensure that all components have loaded their
 * globals before attempting any overrides.
 *
 * @since 2.0.0
 */
function xprofile_override_user_fullnames() {
	// If sync is enabled, the two names will match. No need to continue.
	if ( ! bp_disable_profile_sync() ) {
		return;
	}

	if ( bp_displayed_user_id() ) {
		buddypress()->displayed_user->fullname = bp_core_get_user_displayname( bp_displayed_user_id() );
	}
}
add_action( 'bp_parse_query', 'xprofile_override_user_fullnames', 100 );

/**
 * When search_terms are passed to BP_User_Query, search against xprofile fields.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb WordPress database object.
 *
 * @param array         $sql   Clauses in the user_id SQL query.
 * @param BP_User_Query $query User query object.
 * @return array
 */
function bp_xprofile_bp_user_query_search( $sql, BP_User_Query $query ) {
	global $wpdb;

	if ( empty( $query->query_vars['search_terms'] ) || empty( $sql['where']['search'] ) ) {
		return $sql;
	}

	$bp = buddypress();

	$search_terms_clean = bp_esc_like( wp_kses_normalize_entities( $query->query_vars['search_terms'] ) );

	if ( $query->query_vars['search_wildcard'] === 'left' ) {
		$search_terms_nospace = '%' . $search_terms_clean;
		$search_terms_space   = '%' . $search_terms_clean . ' %';
	} elseif ( $query->query_vars['search_wildcard'] === 'right' ) {
		$search_terms_nospace = $search_terms_clean . '%';
		$search_terms_space   = '% ' . $search_terms_clean . '%';
	} else {
		$search_terms_nospace = '%' . $search_terms_clean . '%';
		$search_terms_space   = '%' . $search_terms_clean . '%';
	}

	// Combine the core search (against wp_users) into a single OR clause
	// with the xprofile_data search.
	$matched_user_ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT user_id FROM {$bp->profile->table_name_data} WHERE value LIKE %s OR value LIKE %s",
		$search_terms_nospace,
		$search_terms_space
	) );

	if ( ! empty( $matched_user_ids ) ) {
		$search_core     = $sql['where']['search'];
		$search_combined = " ( u.{$query->uid_name} IN (" . implode(',', $matched_user_ids) . ") OR {$search_core} )";
		$sql['where']['search'] = $search_combined;
	}

	return $sql;
}
add_action( 'bp_user_query_uid_clauses', 'bp_xprofile_bp_user_query_search', 10, 2 );

/**
 * Syncs Xprofile data to the standard built in WordPress profile data.
 *
 * @since 1.0.0
 * @since 9.2.0 Adds the $args arguments to catch hook's additional arguments.
 *
 * @param int   $user_id ID of the user to sync.
 * @param array $args    Hook's additional arguments.
 * @return bool
 */
function xprofile_sync_wp_profile( $user_id = 0, ...$args ) {

	// Bail if profile syncing is disabled.
	if ( bp_disable_profile_sync() ) {
		return true;
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	$fullname_field_id = (int) bp_xprofile_fullname_field_id();
	$usermeta          = array();
	$userdata          = array();

	if ( isset( $args[1]['meta'] ) ) {
		$usermeta = $args[1]['meta'];
	} elseif ( isset( $args[3] ) ) {
		$usermeta = $args[3];
	}

	if ( isset( $usermeta['profile_field_ids'] ) ) {
		$xprofile_fields = wp_parse_id_list( $usermeta['profile_field_ids'] );
		$xprofile_fields = array_diff( $xprofile_fields, array( $fullname_field_id ) );

		foreach ( $xprofile_fields as $xprofile_field_id ) {
			$field_type = bp_xprofile_get_field_type( $xprofile_field_id );

			$field_key = 'field_' . $xprofile_field_id;
			if ( isset( $field_type->wp_user_key ) && isset( $usermeta[ $field_key ] ) && $usermeta[ $field_key ] ) {
				$userdata[ $field_type->wp_user_key ] = $usermeta[ $field_key ];
			}
		}
	}

	$fullname = xprofile_get_field_data( $fullname_field_id, $user_id );
	$space    = strpos( $fullname, ' ' );

	if ( false === $space ) {
		if ( ! isset( $userdata['first_name'] ) ) {
			$userdata['first_name'] = $fullname;
		}

		if ( ! isset( $userdata['last_name'] ) ) {
			$userdata['last_name'] = '';
		}
	} else {
		if ( ! isset( $userdata['first_name'] ) ) {
			$userdata['first_name'] = substr( $fullname, 0, $space );
		}

		if ( ! isset( $userdata['last_name'] ) ) {
			$userdata['last_name'] = trim( substr( $fullname, $space, strlen( $fullname ) ) );
		}
	}

	bp_update_user_meta( $user_id, 'nickname',   $fullname  );
	bp_update_user_meta( $user_id, 'first_name', $userdata['first_name'] );
	bp_update_user_meta( $user_id, 'last_name',  $userdata['last_name']  );

	wp_update_user( array( 'ID' => $user_id, 'display_name' => $fullname ) );
}
add_action( 'bp_core_signup_user', 'xprofile_sync_wp_profile', 10, 5 );
add_action( 'bp_core_activated_user', 'xprofile_sync_wp_profile', 10, 3 );

/**
 * Syncs the standard built in WordPress profile data to XProfile.
 *
 * @since 1.2.4
 *
 * @param object $errors Array of errors. Passed by reference.
 * @param bool   $update Whether or not being updated.
 * @param object $user   User object whose profile is being synced. Passed by reference.
 */
function xprofile_sync_bp_profile( &$errors, $update, &$user ) {

	// Bail if profile syncing is disabled.
	if ( bp_disable_profile_sync() || ! $update || $errors->get_error_codes() ) {
		return;
	}

	xprofile_set_field_data( bp_xprofile_fullname_field_id(), $user->ID, $user->display_name );
}
add_action( 'user_profile_update_errors', 'xprofile_sync_bp_profile', 10, 3 );

/**
 * Update the WP display, last, and first name fields when the xprofile display name field is updated.
 *
 * @since 3.0.0
 *
 * @param BP_XProfile_ProfileData $data Current instance of the profile data being saved.
 */
function xprofile_sync_wp_profile_on_single_field_set( $data ) {

	if ( bp_xprofile_fullname_field_id() !== $data->field_id ) {
		return;
	}

	xprofile_sync_wp_profile( $data->user_id );
}
add_action( 'xprofile_data_after_save', 'xprofile_sync_wp_profile_on_single_field_set' );

/**
 * When a user is deleted, we need to clean up the database and remove all the
 * profile data from each table. Also we need to clean anything up in the
 * usermeta table that this component uses.
 *
 * @since 1.0.0
 *
 * @param int $user_id The ID of the deleted user.
 */
function xprofile_remove_data( $user_id ) {
	BP_XProfile_ProfileData::delete_data_for_user( $user_id );
}
add_action( 'wpmu_delete_user',  'xprofile_remove_data' );
add_action( 'bp_make_spam_user', 'xprofile_remove_data' );

/**
 * Deletes user XProfile data on the 'delete_user' hook.
 *
 * @since 6.0.0
 *
 * @param int $user_id The ID of the deleted user.
 */
function xprofile_remove_data_on_delete_user( $user_id ) {
	if ( ! bp_remove_user_data_on_delete_user_hook( 'xprofile', $user_id ) ) {
		return;
	}

	xprofile_remove_data( $user_id );
}
add_action( 'delete_user', 'xprofile_remove_data_on_delete_user' );

/*** XProfile Meta ****************************************************/

/**
 * Delete a piece of xprofile metadata.
 *
 * @since 1.5.0
 *
 * @global wpdb $wpdb WordPress database object.
 *
 * @param int         $object_id   ID of the object the metadata belongs to.
 * @param string      $object_type Type of object. 'group', 'field', or 'data'.
 * @param string|bool $meta_key    Key of the metadata being deleted. If omitted, all
 *                                 metadata for the object will be deleted.
 * @param mixed       $meta_value  Optional. If provided, only metadata that matches
 *                                 the value will be permitted.
 * @param bool        $delete_all  Optional. If true, delete matching metadata entries
 *                                 for all objects, ignoring the specified object_id. Otherwise, only
 *                                 delete matching metadata entries for the specified object.
 *                                 Default: false.
 * @return bool
 */
function bp_xprofile_delete_meta( $object_id, $object_type, $meta_key = false, $meta_value = false, $delete_all = false ) {
	global $wpdb;

	// Sanitize object type.
	if ( ! in_array( $object_type, array( 'group', 'field', 'data' ) ) ) {
		return false;
	}

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$table_key  = 'xprofile_' . $object_type . 'meta';
		$table_name = $wpdb->{$table_key};
		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$table_name} WHERE object_type = %s AND object_id = %d", $object_type, $object_id ) );

		// Force delete_all to false if deleting all for object.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	add_filter( 'query', 'bp_xprofile_filter_meta_query' );

	$retval = false;
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'xprofile_' . $object_type, $object_id, $key, $meta_value, $delete_all );
	}

	remove_filter( 'query', 'bp_xprofile_filter_meta_query' );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get a piece of xprofile metadata.
 *
 * Note that the default value of $single is true, unlike in the case of the
 * underlying get_metadata() function. This is for backward compatibility.
 *
 * @since 1.5.0
 *
 * @param int    $object_id   ID of the object the metadata belongs to.
 * @param string $object_type Type of object. 'group', 'field', or 'data'.
 * @param string $meta_key    Key of the metadata being fetched. If omitted, all
 *                            metadata for the object will be retrieved.
 * @param bool   $single      Optional. If true, return only the first value of the
 *                            specified meta_key. This parameter has no effect if meta_key is not
 *                            specified. Default: true.
 * @return mixed An array of values if `$single` is false.
 *               The value of the meta field if `$single` is true.
 *               False for an invalid `$object_type` (one of `group`, `field`, `data`).
 *               False for an invalid `$object_id` (non-numeric, zero, or negative value),
 *               or if `$meta_type` is not specified.
 *               An empty string if a valid but non-existing object ID is passed.
 */
function bp_xprofile_get_meta( $object_id, $object_type, $meta_key = '', $single = true ) {
	// Sanitize object type.
	if ( ! in_array( $object_type, array( 'group', 'field', 'data' ) ) ) {
		return false;
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	add_filter( 'query', 'bp_xprofile_filter_meta_query' );
	$retval = get_metadata( 'xprofile_' . $object_type, $object_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );
	remove_filter( 'query', 'bp_xprofile_filter_meta_query' );

	return $retval;
}

/**
 * Update a piece of xprofile metadata.
 *
 * @since 1.5.0
 *
 * @param int    $object_id   ID of the object the metadata belongs to.
 * @param string $object_type Type of object. 'group', 'field', or 'data'.
 * @param string $meta_key    Key of the metadata being updated.
 * @param string $meta_value  Value of the metadata being updated.
 * @param mixed  $prev_value  Optional. If specified, only update existing
 *                            metadata entries with the specified value.
 *                            Otherwise update all entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata, returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bp_xprofile_update_meta( $object_id, $object_type, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	add_filter( 'query', 'bp_xprofile_filter_meta_query' );
	$retval = update_metadata( 'xprofile_' . $object_type, $object_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_xprofile_filter_meta_query' );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of xprofile metadata.
 *
 * @since 2.0.0
 *
 * @param int    $object_id   ID of the object the metadata belongs to.
 * @param string $object_type Type of object. 'group', 'field', or 'data'.
 * @param string $meta_key    Metadata key.
 * @param mixed  $meta_value  Metadata value.
 * @param bool   $unique      Optional. Whether to enforce a single metadata value
 *                            for the given key. If true, and the object already
 *                            has a value for the key, no change will be made.
 *                            Default false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bp_xprofile_add_meta( $object_id, $object_type, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	add_filter( 'query', 'bp_xprofile_filter_meta_query' );
	$retval = add_metadata( 'xprofile_' . $object_type , $object_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );
	remove_filter( 'query', 'bp_xprofile_filter_meta_query' );

	return $retval;
}

/**
 * Updates the fieldgroup metadata.
 *
 * @since 1.5.0
 *
 * @param int    $field_group_id Group ID for the group field belongs to.
 * @param string $meta_key       Meta key to update.
 * @param string $meta_value     Meta value to update to.
 * @return bool|int
 */
function bp_xprofile_update_fieldgroup_meta( $field_group_id, $meta_key, $meta_value ) {
	return bp_xprofile_update_meta( $field_group_id, 'group', $meta_key, $meta_value );
}

/**
 * Updates the field metadata.
 *
 * @since 1.5.0
 *
 * @param int    $field_id   Field ID to update.
 * @param string $meta_key   Meta key to update.
 * @param string $meta_value Meta value to update to.
 * @return bool|int
 */
function bp_xprofile_update_field_meta( $field_id, $meta_key, $meta_value ) {
	return bp_xprofile_update_meta( $field_id, 'field', $meta_key, $meta_value );
}

/**
 * Updates the fielddata metadata.
 *
 * @since 1.5.0
 *
 * @param int    $field_data_id Field ID to update.
 * @param string $meta_key      Meta key to update.
 * @param string $meta_value    Meta value to update to.
 * @return bool|int
 */
function bp_xprofile_update_fielddata_meta( $field_data_id, $meta_key, $meta_value ) {
	return bp_xprofile_update_meta( $field_data_id, 'data', $meta_key, $meta_value );
}

/**
 * Return the field ID for the Full Name xprofile field.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb WordPress database object.
 *
 * @return int Field ID.
 */
function bp_xprofile_fullname_field_id() {
	$id = wp_cache_get( 'fullname_field_id', 'bp_xprofile' );

	if ( false === $id ) {
		global $wpdb;

		$bp = buddypress();
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_fields} WHERE name = %s", addslashes( bp_xprofile_fullname_field_name() ) ) );

		wp_cache_set( 'fullname_field_id', $id, 'bp_xprofile' );
	}

	return absint( $id );
}

/**
 * Return the field name for the Full Name xprofile field.
 *
 * @since 1.5.0
 *
 * @return string The field name.
 */
function bp_xprofile_fullname_field_name() {

	/**
	 * Filters the field name for the Full Name xprofile field.
	 *
	 * @since 1.5.0
	 *
	 * @param string $value BP_XPROFILE_FULLNAME_FIELD_NAME Full name field constant.
	 */
	return apply_filters( 'bp_xprofile_fullname_field_name', BP_XPROFILE_FULLNAME_FIELD_NAME );
}

/**
 * Is rich text enabled for this profile field?
 *
 * By default, rich text is enabled for textarea fields and disabled for all other field types.
 *
 * @since 2.4.0
 *
 * @param int|null $field_id Optional. Default current field ID.
 * @return bool
 */
function bp_xprofile_is_richtext_enabled_for_field( $field_id = null ) {
	if ( ! $field_id ) {
		$field_id = bp_get_the_profile_field_id();
	}

	$field = xprofile_get_field( $field_id, null, false );

	$enabled = false;
	if ( $field instanceof BP_XProfile_Field ) {
		$enabled = (bool) $field->type_obj->supports_richtext;
	}

	/**
	 * Filters whether richtext is enabled for the given field.
	 *
	 * @since 2.4.0
	 *
	 * @param bool $enabled  True if richtext is enabled for the field, otherwise false.
	 * @param int  $field_id ID of the field.
	 */
	return apply_filters( 'bp_xprofile_is_richtext_enabled_for_field', $enabled, $field_id );
}

/**
 * Get visibility levels out of the $bp global.
 *
 * @since 1.6.0
 *
 * @return array
 */
function bp_xprofile_get_visibility_levels() {

	/**
	 * Filters the visibility levels out of the $bp global.
	 *
	 * @since 1.6.0
	 *
	 * @param array $visibility_levels Array of visibility levels.
	 */
	return apply_filters( 'bp_xprofile_get_visibility_levels', buddypress()->profile->visibility_levels );
}

/**
 * Get the ids of fields that are hidden for this displayed/loggedin user pair.
 *
 * This is the function primarily responsible for profile field visibility. It works by determining
 * the relationship between the displayed_user (ie the profile owner) and the current_user (ie the
 * profile viewer). Then, based on that relationship, we query for the set of fields that should
 * be excluded from the profile loop.
 *
 * @since 1.6.0
 *
 * @see BP_XProfile_Group::get()
 *   or if you have added your own custom levels.
 *
 * @param int $displayed_user_id The id of the user the profile fields belong to.
 * @param int $current_user_id   The id of the user viewing the profile.
 * @return array An array of field ids that should be excluded from the profile query
 */
function bp_xprofile_get_hidden_fields_for_user( $displayed_user_id = 0, $current_user_id = 0 ) {
	if ( ! $displayed_user_id ) {
		$displayed_user_id = bp_displayed_user_id();
	}

	if ( ! $displayed_user_id ) {
		return array();
	}

	if ( ! $current_user_id ) {
		$current_user_id = bp_loggedin_user_id();
	}

	// @todo - This is where you'd swap out for current_user_can() checks.
	$hidden_levels = bp_xprofile_get_hidden_field_types_for_user( $displayed_user_id, $current_user_id );
	$hidden_fields = bp_xprofile_get_fields_by_visibility_levels( $displayed_user_id, $hidden_levels );

	/**
	 * Filters the ids of fields that are hidden for this displayed/loggedin user pair.
	 *
	 * @since 1.6.0
	 *
	 * @param array $hidden_fields     Array of hidden fields for the displayed/logged in user.
	 * @param int   $displayed_user_id ID of the displayed user.
	 * @param int   $current_user_id   ID of the current user.
	 */
	return apply_filters( 'bp_xprofile_get_hidden_fields_for_user', $hidden_fields, $displayed_user_id, $current_user_id );
}

/**
 * Get the visibility levels that should be hidden for this user pair.
 *
 * Field visibility is determined based on the relationship between the
 * logged-in user, the displayed user, and the visibility setting for the
 * current field. (See bp_xprofile_get_hidden_fields_for_user().) This
 * utility function speeds up this matching by fetching the visibility levels
 * that should be hidden for the current user pair.
 *
 * @since 1.8.2
 *
 * @see bp_xprofile_get_hidden_fields_for_user()
 *
 * @param int $displayed_user_id The id of the user the profile fields belong to.
 * @param int $current_user_id   The id of the user viewing the profile.
 * @return array An array of visibility levels hidden to the current user.
 */
function bp_xprofile_get_hidden_field_types_for_user( $displayed_user_id = 0, $current_user_id = 0 ) {

	// Current user is logged in.
	if ( ! empty( $current_user_id ) ) {

		// Nothing's private when viewing your own profile, or when the
		// current user is an admin.
		if ( $displayed_user_id == $current_user_id || bp_current_user_can( 'bp_moderate' ) ) {
			$hidden_levels = array();

		// If the current user and displayed user are friends, show all.
		} elseif ( bp_is_active( 'friends' ) && friends_check_friendship( $displayed_user_id, $current_user_id ) ) {
			$hidden_levels = array( 'adminsonly' );

		// Current user is logged in but not friends, so exclude friends-only.
		} else {
			$hidden_levels = array( 'friends', 'adminsonly' );
		}

	// Current user is not logged in, so exclude friends-only, loggedin, and adminsonly.
	} else {
		$hidden_levels = array( 'friends', 'loggedin', 'adminsonly' );
	}

	/**
	 * Filters the visibility levels that should be hidden for this user pair.
	 *
	 * @since 2.0.0
	 *
	 * @param array $hidden_fields     Array of hidden fields for the displayed/logged in user.
	 * @param int   $displayed_user_id ID of the displayed user.
	 * @param int   $current_user_id   ID of the current user.
	 */
	return apply_filters( 'bp_xprofile_get_hidden_field_types_for_user', $hidden_levels, $displayed_user_id, $current_user_id );
}

/**
 * Fetch an array of the xprofile fields that a given user has marked with certain visibility levels.
 *
 * @since 1.6.0
 *
 * @see bp_xprofile_get_hidden_fields_for_user()
 *
 * @param int   $user_id The id of the profile owner.
 * @param array $levels  An array of visibility levels ('public', 'friends', 'loggedin', 'adminsonly' etc) to be
 *                       checked against.
 * @return array $field_ids The fields that match the requested visibility levels for the given user.
 */
function bp_xprofile_get_fields_by_visibility_levels( $user_id, $levels = array() ) {
	if ( ! is_array( $levels ) ) {
		$levels = (array)$levels;
	}

	$user_visibility_levels = (array) bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );

	// Parse the user-provided visibility levels with the default levels, which may take
	// precedence.
	$default_visibility_levels = BP_XProfile_Group::fetch_default_visibility_levels();

	foreach ( (array) $default_visibility_levels as $d_field_id => $defaults ) {
		// If the admin has forbidden custom visibility levels for this field, replace
		// the user-provided setting with the default specified by the admin.
		if ( isset( $defaults['allow_custom'] ) && isset( $defaults['default'] ) && 'disabled' == $defaults['allow_custom'] ) {
			$user_visibility_levels[ $d_field_id ] = $defaults['default'];
		}
	}

	$field_ids = array();
	foreach ( $user_visibility_levels as $field_id => $field_visibility ) {
		if ( in_array( $field_visibility, $levels ) ) {
			$field_ids[] = $field_id;
		}
	}

	// Never allow the fullname field to be excluded.
	if ( in_array( 1, $field_ids ) ) {
		$key = array_search( 1, $field_ids );
		unset( $field_ids[ $key ] );
	}

	return $field_ids;
}

/**
 * Formats datebox field values passed through a POST request.
 *
 * This function only changes the global $_POST that should contain
 * the datebox data.
 *
 * @since 2.8.0
 *
 * @param int $field_id The id of the current field being looped through.
 */
function bp_xprofile_maybe_format_datebox_post_data( $field_id ) {
	if ( ! isset( $_POST[ 'field_' . $field_id ] ) ) {
		if ( ! empty( $_POST[ 'field_' . $field_id . '_day' ] ) && ! empty( $_POST[ 'field_' . $field_id . '_month' ] ) && ! empty( $_POST[ 'field_' . $field_id . '_year' ] ) ) {
			// Concatenate the values.
			$date_value = $_POST[ 'field_' . $field_id . '_day' ] . ' ' . $_POST[ 'field_' . $field_id . '_month' ] . ' ' . $_POST[ 'field_' . $field_id . '_year' ];

			// Check that the concatenated value can be turned into a timestamp.
			if ( $timestamp = strtotime( $date_value ) ) {
				// Add the timestamp to the global $_POST that should contain the datebox data.
				$_POST[ 'field_' . $field_id ] = date( 'Y-m-d H:i:s', $timestamp );
			}
		}
	}
}

/**
 * Finds and exports personal data associated with an email address from the XProfile tables.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The users email address.
 * @return array An array of personal data.
 */
function bp_xprofile_personal_data_exporter( $email_address ) {
	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$user_data_to_export = array();

	$user_profile_data = BP_XProfile_ProfileData::get_all_for_user( $user->ID );
	foreach ( $user_profile_data as $field_name => $field ) {
		// Skip non-array fields, which don't belong to XProfile.
		if ( ! is_array( $field ) ) {
			continue;
		}

		// Re-pull the data so that BuddyPress formats and sanitizes properly.
		$value = xprofile_get_field_data( $field['field_id'], $user->ID, 'comma' );
		$user_data_to_export[] = array(
			'name'  => $field_name,
			'value' => $value,
		);
	}

	$data_to_export[] = array(
		'group_id'    => 'bp_xprofile',
		'group_label' => __( 'Extended Profile Data', 'buddypress' ),
		'item_id'     => "bp-xprofile-{$user->ID}",
		'data'        => $user_data_to_export,
	);

	return array(
		'data' => $data_to_export,
		'done' => true,
	);
}

/**
 * Returns the list of supported WordPress field meta keys.
 *
 * @since 8.0.0
 *
 * @return string[] List of supported WordPress user keys.
 */
function bp_xprofile_get_wp_user_keys() {
	return array_merge(
		array( 'first_name', 'last_name', 'user_url', 'description' ),
		array_keys( wp_get_user_contact_methods() )
	);
}

/**
 * Returns the signup field IDs.
 *
 * @since 8.0.0
 * @since 15.0.0 The SQL request was adapted to support WP Playground's SQLite DB.
 *
 * @global wpdb $wpdb WordPress database object.
 *
 * @return int[] The signup field IDs.
 */
function bp_xprofile_get_signup_field_ids() {
	$signup_field_ids = wp_cache_get( 'signup_fields', 'bp_xprofile' );

	if ( ! $signup_field_ids ) {
		global $wpdb;
		$bp = buddypress();

		// WP Playground's SQLite DB does not support CONVERT AS SIGNED.
		$results          = $wpdb->get_results( "SELECT object_id, meta_value FROM {$bp->profile->table_name_meta} WHERE object_type = 'field' AND meta_key = 'signup_position'" );
		$signup_field_ids = array();

		foreach ( $results as $result ) {
			$index                      = (int) $result->meta_value;
			$signup_field_ids[ $index ] = (int) $result->object_id;
		}

		// Sort.
		ksort( $signup_field_ids );

		wp_cache_set( 'signup_fields', $signup_field_ids, 'bp_xprofile' );
	}

	return array_values( $signup_field_ids );
}

/**
 * Returns xProfile loop's signup arguments.
 *
 * @since 8.0.0
 *
 * @param array $extra Optional extra arguments.
 * @return array The xProfile loop's signup arguments.
 */
function bp_xprofile_signup_args( $extra = array() ) {
	$signup_fields = (array) bp_xprofile_get_signup_field_ids();
	$default_args  = array(
		'fetch_fields'     => true,
		'fetch_field_data' => false,
	);

	// No signup fields? Let's bring back primary group.
	if ( ! $signup_fields && bp_is_register_page() ) {
		$default_args['profile_group_id'] = 1;
	} else {
		$default_args['signup_fields_only'] = true;
	}

	return array_merge( $default_args, $extra );
}
