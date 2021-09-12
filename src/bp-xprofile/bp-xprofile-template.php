<?php
/**
 * BuddyPress XProfile Template Tags.
 *
 * @package BuddyPress
 * @subpackage XProfileTemplate
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Query for XProfile groups and fields.
 *
 * @since 1.0.0
 * @since 2.4.0 Introduced `$member_type` argument.
 * @since 8.0.0 Introduced `$hide_field_types` & `$signup_fields_only` arguments.
 *
 * @global object $profile_template
 * @see BP_XProfile_Group::get() for full description of `$args` array.
 *
 * @param array|string $args {
 *     Array of arguments. See BP_XProfile_Group::get() for full description. Those arguments whose defaults differ
 *     from that method are described here:
 *     @type int          $user_id                Default: ID of the displayed user.
 *     @type string|array $member_type            Default: 'any'.
 *     @type int|bool     $profile_group_id       Default: false.
 *     @type bool         $hide_empty_groups      Default: true.
 *     @type bool         $hide_empty_fields      Defaults to true on the Dashboard, on a user's Edit Profile page,
 *                                                or during registration. Otherwise false.
 *     @type bool         $fetch_fields           Default: true.
 *     @type bool         $fetch_field_data       Default: true.
 *     @type bool         $fetch_visibility_level Defaults to true when an admin is viewing a profile, or when a user is
 *                                                viewing her own profile, or during registration. Otherwise false.
 *     @type int[]|bool   $exclude_groups         Default: false.
 *     @type int[]|bool   $exclude_fields         Default: false.
 *     @type string[]     $hide_field_types       Default: empty array.
 *     @type bool         $signup_fields_only     Default: false.
 *     @type bool         $update_meta_cache      Default: true.
 * }
 *
 * @return bool
 */
function bp_has_profile( $args = '' ) {
	global $profile_template;

	// Only show empty fields if we're on the Dashboard, or we're on a user's
	// profile edit page, or this is a registration page.
	$hide_empty_fields_default = ( ! is_network_admin() && ! is_admin() && ! bp_is_user_profile_edit() && ! bp_is_register_page() );

	// We only need to fetch visibility levels when viewing your own profile.
	if ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) || bp_is_register_page() ) {
		$fetch_visibility_level_default = true;
	} else {
		$fetch_visibility_level_default = false;
	}

	// Parse arguments.
	$r = bp_parse_args(
		$args,
		array(
			'user_id'                => bp_displayed_user_id(),
			'member_type'            => 'any',
			'profile_group_id'       => false,
			'hide_empty_groups'      => true,
			'hide_empty_fields'      => $hide_empty_fields_default,
			'fetch_fields'           => true,
			'fetch_field_data'       => true,
			'fetch_visibility_level' => $fetch_visibility_level_default,
			'exclude_groups'         => false, // Comma-separated list of profile field group IDs to exclude.
			'exclude_fields'         => false, // Comma-separated list of profile field IDs to exclude.
			'hide_field_types'       => array(), // List of field types to hide from profile fields loop.
			'signup_fields_only'     => false, // Whether to only return signup fields.
			'update_meta_cache'      => true,
		),
		'has_profile'
	);

	// Populate the template loop global.
	$profile_template = new BP_XProfile_Data_Template( $r );

	/**
	 * Filters whether or not a group has a profile to display.
	 *
	 * @since 1.1.0
	 * @since 2.6.0 Added the `$r` parameter.
	 *
	 * @param bool   $has_groups       Whether or not there are group profiles to display.
	 * @param string $profile_template Current profile template being used.
	 * @param array  $r                Array of arguments passed into the BP_XProfile_Data_Template class.
	 */
	return apply_filters( 'bp_has_profile', $profile_template->has_groups(), $profile_template, $r );
}

/**
 * Start off the profile groups.
 *
 * @since 1.0.0
 *
 * @return mixed
 */
function bp_profile_groups() {
	global $profile_template;
	return $profile_template->profile_groups();
}

/**
 * Set up the profile groups.
 *
 * @since 1.0.0
 *
 * @return mixed
 */
function bp_the_profile_group() {
	global $profile_template;
	return $profile_template->the_profile_group();
}

/**
 * Whether or not the group has fields to display.
 *
 * @since 1.0.0
 *
 * @return mixed
 */
function bp_profile_group_has_fields() {
	global $profile_template;
	return $profile_template->has_fields();
}

/**
 * Output the class attribute for a field.
 *
 * @since 1.0.0
 *
 * @param mixed $class Extra classes to append to class attribute.
 *                     Pass multiple class names as an array or
 *                     space-delimited string.
 */
function bp_field_css_class( $class = false ) {
	echo bp_get_field_css_class( $class );
}

	/**
	 * Return the class attribute for a field.
	 *
	 * @since 1.1.0
	 *
	 * @param string|bool $class Extra classes to append to class attribute.
	 * @return string
	 */
	function bp_get_field_css_class( $class = false ) {
		global $profile_template;

		$css_classes = array();

		if ( ! empty( $class ) ) {
			if ( ! is_array( $class ) ) {
				$class = preg_split( '#\s+#', $class );
			}
			$css_classes = array_map( 'sanitize_html_class', $class );
		}

		// Set a class with the field ID.
		$css_classes[] = 'field_' . $profile_template->field->id;

		// Set a class with the field name (sanitized).
		$css_classes[] = 'field_' . sanitize_title( $profile_template->field->name );

		// Set a class indicating whether the field is required or optional.
		if ( ! empty( $profile_template->field->is_required ) ) {
			$css_classes[] = 'required-field';
		} else {
			$css_classes[] = 'optional-field';
		}

		// Add the field visibility level.
		$css_classes[] = 'visibility-' . esc_attr( bp_get_the_profile_field_visibility_level() );

		if ( $profile_template->current_field % 2 == 1 ) {
			$css_classes[] = 'alt';
		}

		$css_classes[] = 'field_type_' . sanitize_title( $profile_template->field->type );

		/**
		 * Filters the field classes to be applied to a field.
		 *
		 * @since 1.1.0
		 *
		 * @param array $css_classes Array of classes to be applied to field. Passed by reference.
		 */
		$css_classes = apply_filters_ref_array( 'bp_field_css_classes', array( &$css_classes ) );

		/**
		 * Filters the class HTML attribute to be used on a field.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value class HTML attribute with imploded classes.
		 */
		return apply_filters( 'bp_get_field_css_class', ' class="' . implode( ' ', $css_classes ) . '"' );
	}

/**
 * Whether or not the XProfile field has data to display.
 *
 * @since 1.0.0
 *
 * @global object $profile_template
 *
 * @return mixed
 */
function bp_field_has_data() {
	global $profile_template;

	/**
	 * Filters whether or not the XProfile field has data to display.
	 *
	 * @since 2.8.0
	 *
	 * @param bool   $value            Whether or not there is data to display.
	 * @param object $profile_template Profile template object.
	 * @param string $value            Profile field being displayed.
	 * @param string $value            Profile field ID being displayed.
	 */
	return apply_filters( 'bp_field_has_data', $profile_template->field_has_data, $profile_template, $profile_template->field, $profile_template->field->id );
}

/**
 * Whether or not the XProfile field has public data to display.
 *
 * @since 1.0.0
 *
 * @global object $profile_template
 *
 * @return bool
 */
function bp_field_has_public_data() {
	global $profile_template;

	/**
	 * Filters whether or not the XProfile field has public data to display.
	 *
	 * @since 2.8.0
	 *
	 * @param bool   $value            Whether or not there is public data to display.
	 * @param object $profile_template Profile template object.
	 * @param string $value            Profile field being displayed.
	 * @param string $value            Profile field ID being displayed.
	 */
	return apply_filters( 'bp_field_has_public_data', ( ! empty( $profile_template->field_has_data ) ), $profile_template, $profile_template->field, $profile_template->field->id );
}

/**
 * Output the XProfile group ID.
 *
 * @since 1.0.0
 */
function bp_the_profile_group_id() {
	echo bp_get_the_profile_group_id();
}

	/**
	 * Return the XProfile group ID.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	function bp_get_the_profile_group_id() {
		global $group;

		/**
		 * Filters the XProfile group ID.
		 *
		 * @since 1.1.0
		 *
		 * @param int $id ID for the profile group.
		 */
		return (int) apply_filters( 'bp_get_the_profile_group_id', $group->id );
	}

/**
 * Output the XProfile group name.
 *
 * @since 1.0.0
 */
function bp_the_profile_group_name() {
	echo bp_get_the_profile_group_name();
}

	/**
	 * Return the XProfile group name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_group_name() {
		global $group;

		/**
		 * Filters the XProfile group name.
		 *
		 * @since 1.0.0
		 *
		 * @param string $name Name for the profile group.
		 */
		return apply_filters( 'bp_get_the_profile_group_name', $group->name );
	}

/**
 * Output the XProfile group slug.
 *
 * @since 1.1.0
 */
function bp_the_profile_group_slug() {
	echo bp_get_the_profile_group_slug();
}

	/**
	 * Return the XProfile group slug.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_group_slug() {
		global $group;

		/**
		 * Filters the XProfile group slug.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Slug for the profile group.
		 */
		return apply_filters( 'bp_get_the_profile_group_slug', sanitize_title( $group->name ) );
	}

/**
 * Output the XProfile group description.
 *
 * @since 1.0.0
 */
function bp_the_profile_group_description() {
	echo bp_get_the_profile_group_description();
}

	/**
	 * Return the XProfile group description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_group_description() {
		global $group;

		/**
		 * Filters the XProfile group description.
		 *
		 * @since 1.0.0
		 *
		 * @param string $description Description for the profile group.
		 */
		return apply_filters( 'bp_get_the_profile_group_description', $group->description );
	}

/**
 * Output the XProfile group edit form action.
 *
 * @since 1.1.0
 */
function bp_the_profile_group_edit_form_action() {
	echo bp_get_the_profile_group_edit_form_action();
}

	/**
	 * Return the XProfile group edit form action.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_group_edit_form_action() {
		global $group;

		// Build the form action URL.
		$form_action = trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/edit/group/' . $group->id );

		/**
		 * Filters the action for the XProfile group edit form.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value URL for the action attribute on the
		 *                      profile group edit form.
		 */
		return apply_filters( 'bp_get_the_profile_group_edit_form_action', $form_action );
	}

/**
 * Output the XProfile group field IDs.
 *
 * @since 1.1.0
 */
function bp_the_profile_group_field_ids() {
	echo bp_get_the_profile_group_field_ids();
}

	/**
	 * Return the XProfile group field IDs.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_group_field_ids() {
		global $group;

		$field_ids = '';

		if ( !empty( $group->fields ) ) {
			foreach ( (array) $group->fields as $field ) {
				$field_ids .= $field->id . ',';
			}
		}

		return substr( $field_ids, 0, -1 );
	}

/**
 * Output a comma-separated list of field IDs that are to be submitted on profile edit.
 *
 * @since 2.1.0
 */
function bp_the_profile_field_ids() {
	echo bp_get_the_profile_field_ids();
}
	/**
	 * Generate a comma-separated list of field IDs that are to be submitted on profile edit.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_field_ids() {
		global $profile_template;

		$field_ids = array();
		foreach ( $profile_template->groups as $group ) {
			if ( ! empty( $group->fields ) ) {
				$field_ids = array_merge( $field_ids, wp_list_pluck( $group->fields, 'id' ) );
			}
		}

		$field_ids = implode( ',', wp_parse_id_list( $field_ids ) );

		/**
		 * Filters the comma-separated list of field IDs.
		 *
		 * @since 2.1.0
		 *
		 * @param string $field_ids Comma-separated field IDs.
		 */
		return apply_filters( 'bp_get_the_profile_field_ids', $field_ids );
	}

/**
 * Return the XProfile fields.
 *
 * @since 1.0.0
 *
 * @return mixed
 */
function bp_profile_fields() {
	global $profile_template;
	return $profile_template->profile_fields();
}

/**
 * Sets up the XProfile field.
 *
 * @since 1.0.0
 *
 * @return mixed
 */
function bp_the_profile_field() {
	global $profile_template;
	return $profile_template->the_profile_field();
}

/**
 * Output the XProfile field ID.
 *
 * @since 1.1.0
 */
function bp_the_profile_field_id() {
	echo bp_get_the_profile_field_id();
}

	/**
	 * Return the XProfile field ID.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	function bp_get_the_profile_field_id() {
		global $field;

		/**
		 * Filters the XProfile field ID.
		 *
		 * @since 1.1.0
		 *
		 * @param int $id ID for the profile field.
		 */
		return (int) apply_filters( 'bp_get_the_profile_field_id', $field->id );
	}

/**
 * Outputs the XProfile field name.
 *
 * @since 1.0.0
 */
function bp_the_profile_field_name() {
	echo bp_get_the_profile_field_name();
}

	/**
	 * Returns the XProfile field name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_field_name() {
		global $field;

		/**
		 * Filters the XProfile field name.
		 *
		 * @since 1.0.0
		 *
		 * @param string $name Name for the profile field.
		 */
		return apply_filters( 'bp_get_the_profile_field_name', $field->name );
	}

/**
 * Outputs the XProfile field value.
 *
 * @since 1.0.0
 */
function bp_the_profile_field_value() {
	echo bp_get_the_profile_field_value();
}

	/**
	 * Returns the XProfile field value.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_field_value() {
		global $field;

		$field->data->value = bp_unserialize_profile_field( $field->data->value );

		/**
		 * Filters the XProfile field value.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Value for the profile field.
		 * @param string $type  Type for the profile field.
		 * @param int    $id    ID for the profile field.
		 */
		return apply_filters( 'bp_get_the_profile_field_value', $field->data->value, $field->type, $field->id );
	}

/**
 * Outputs the XProfile field edit value.
 *
 * @since 1.1.0
 */
function bp_the_profile_field_edit_value() {
	echo bp_get_the_profile_field_edit_value();
}

	/**
	 * Returns the XProfile field edit value.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_field_edit_value() {
		global $field;

		// Make sure field data object exists.
		if ( ! isset( $field->data ) ) {
			$field->data = new stdClass;
		}

		// Default to empty value.
		if ( ! isset( $field->data->value ) ) {
			$field->data->value = '';
		}

		// Was a new value posted? If so, use it instead.
		if ( isset( $_POST['field_' . $field->id] ) ) {

			// This is sanitized via the filter below (based on the field type).
			$field->data->value = $_POST['field_' . $field->id];
		}

		/**
		 * Filters the XProfile field edit value.
		 *
		 * @since 1.1.0
		 *
		 * @param string $field_value Current field edit value.
		 * @param string $type        Type for the profile field.
		 * @param int    $id          ID for the profile field.
		 */
		return apply_filters( 'bp_get_the_profile_field_edit_value', $field->data->value, $field->type, $field->id );
	}

/**
 * Outputs the XProfile field type.
 *
 * @since 1.1.0
 */
function bp_the_profile_field_type() {
	echo bp_get_the_profile_field_type();
}

	/**
	 * Returns the XProfile field type.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_field_type() {
		global $field;

		/**
		 * Filters the XProfile field type.
		 *
		 * @since 1.1.0
		 *
		 * @param string $type Type for the profile field.
		 */
		return apply_filters( 'bp_the_profile_field_type', $field->type );
	}

/**
 * Outputs the XProfile field description.
 *
 * @since 1.1.0
 */
function bp_the_profile_field_description() {
	echo bp_get_the_profile_field_description();
}

	/**
	 * Returns the XProfile field description.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_field_description() {
		global $field;

		/**
		 * Filters the XProfile field description.
		 *
		 * @since 1.1.0
		 *
		 * @param string $description Description for the profile field.
		 */
		return apply_filters( 'bp_get_the_profile_field_description', $field->description );
	}

/**
 * Outputs the XProfile field input name.
 *
 * @since 1.1.0
 */
function bp_the_profile_field_input_name() {
	echo bp_get_the_profile_field_input_name();
}

	/**
	 * Returns the XProfile field input name.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_field_input_name() {
		global $field;

		/**
		 * Filters the profile field input name.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Value used for the name attribute on an input.
		 */
		return apply_filters( 'bp_get_the_profile_field_input_name', 'field_' . $field->id );
	}

/**
 * Returns the action name for any signup errors related to this profile field.
 *
 * In the registration templates, signup errors are pulled from the global
 * object and rendered at actions that look like 'bp_field_12_errors'. This
 * function allows the action name to be easily concatenated and called in the
 * following fashion:
 *   do_action( bp_get_the_profile_field_errors_action() );
 *
 * @since 1.8.0
 *
 * @return string The _errors action name corresponding to this profile field.
 */
function bp_get_the_profile_field_errors_action() {
	global $field;
	return 'bp_field_' . $field->id . '_errors';
}

/**
 * Displays field options HTML for field types of 'selectbox', 'multiselectbox',
 * 'radio', 'checkbox', and 'datebox'.
 *
 * @since 1.1.0
 *
 * @param array $args Specify type for datebox. Allowed 'day', 'month', 'year'.
 */
function bp_the_profile_field_options( $args = array() ) {
	echo bp_get_the_profile_field_options( $args );
}
	/**
	 * Retrieves field options HTML for field types of 'selectbox', 'multiselectbox', 'radio', 'checkbox', and 'datebox'.
	 *
	 * @since 1.1.0
	 *
	 *
	 * @param array $args {
	 *     Array of optional arguments.
	 *     @type string|bool $type    Type of datebox. False if it's not a
	 *                                datebox, otherwise 'day, 'month', or 'year'. Default: false.
	 *     @type int         $user_id ID of the user whose profile values should be
	 *                                used when rendering options. Default: displayed user.
	 * }
	 *
	 * @return string $vaue Field options markup.
	 */
	function bp_get_the_profile_field_options( $args = array() ) {
		global $field;

		$args = bp_parse_args(
			$args,
			array(
				'type'    => false,
				'user_id' => bp_displayed_user_id(),
			),
			'get_the_profile_field_options'
		);

		/**
		 * In some cases, the $field global is not an instantiation of the BP_XProfile_Field class.
		 * However, we have to make sure that all data originally in $field gets merged back in, after reinstantiation.
		 */
		if ( ! method_exists( $field, 'get_children' ) ) {
			$field_obj = xprofile_get_field( $field->id, null, false );

			foreach ( $field as $field_prop => $field_prop_value ) {
				if ( ! isset( $field_obj->{$field_prop} ) ) {
					$field_obj->{$field_prop} = $field_prop_value;
				}
			}

			$field = $field_obj;
		}

		ob_start();
		$field->type_obj->edit_field_options_html( $args );
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

/**
 * Render whether or not a profile field is required.
 *
 * @since 1.1.0
 */
function bp_the_profile_field_is_required() {
	echo bp_get_the_profile_field_is_required();
}

	/**
	 * Return whether or not a profile field is required.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	function bp_get_the_profile_field_is_required() {
		global $field;

		$retval = false;

		if ( isset( $field->is_required ) ) {
			$retval = $field->is_required;
		}

		/**
		 * Filters whether or not a profile field is required.
		 *
		 * @since 1.1.0
		 * @since 2.8.0 Added field ID.
		 *
		 * @param bool   $retval Whether or not the field is required.
		 * @param string $value  Field ID that may be required.
		 */
		return (bool) apply_filters( 'bp_get_the_profile_field_is_required', $retval, $field->id );
	}

/**
 * Output the visibility level of this field.
 *
 * @since 1.6.0
 */
function bp_the_profile_field_visibility_level() {
	echo bp_get_the_profile_field_visibility_level();
}

	/**
	 * Return the visibility level of this field.
	 *
	 * @since 1.6.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_field_visibility_level() {
		global $field;

		// On the registration page, values stored in POST should take
		// precedence over default visibility, so that submitted values
		// are not lost on failure.
		if ( bp_is_register_page() && ! empty( $_POST['field_' . $field->id . '_visibility'] ) ) {
			$retval = esc_attr( $_POST['field_' . $field->id . '_visibility'] );
		} else {
			$retval = ! empty( $field->visibility_level ) ? $field->visibility_level : 'public';
		}

		/**
		 * Filters the profile field visibility level.
		 *
		 * @since 1.6.0
		 *
		 * @param string $retval Field visibility level.
		 */
		return apply_filters( 'bp_get_the_profile_field_visibility_level', $retval );
	}

/**
 * Echo the visibility level label of this field.
 *
 * @since 1.6.0
 */
function bp_the_profile_field_visibility_level_label() {
	echo bp_get_the_profile_field_visibility_level_label();
}

	/**
	 * Return the visibility level label of this field.
	 *
	 * @since 1.6.0
	 *
	 * @return string
	 */
	function bp_get_the_profile_field_visibility_level_label() {
		global $field;

		// On the registration page, values stored in POST should take
		// precedence over default visibility, so that submitted values
		// are not lost on failure.
		if ( bp_is_register_page() && ! empty( $_POST['field_' . $field->id . '_visibility'] ) ) {
			$level = esc_html( $_POST['field_' . $field->id . '_visibility'] );
		} else {
			$level = ! empty( $field->visibility_level ) ? $field->visibility_level : 'public';
		}

		$fields = bp_xprofile_get_visibility_levels();

		/**
		 * Filters the profile field visibility level label.
		 *
		 * @since 1.6.0
		 * @since 2.6.0 Added the `$level` parameter.
		 *
		 * @param string $retval Field visibility level label.
		 * @param string $level  Field visibility level.
		 */
		return apply_filters( 'bp_get_the_profile_field_visibility_level_label', $fields[ $level ]['label'], $level );
	}

/**
 * Return unserialized profile field data, and combine any array items into a
 * comma-separated string.
 *
 * @since 1.0.0
 *
 * @param string $value Content to maybe unserialize.
 * @return string
 */
function bp_unserialize_profile_field( $value ) {
	if ( is_serialized($value) ) {
		$field_value = @unserialize($value);
		$field_value = implode( ', ', $field_value );
		return $field_value;
	}

	return $value;
}

/**
 * Output XProfile field data.
 *
 * @since 1.2.0
 *
 * @param string|array $args Array of arguments for field data. See {@link bp_get_profile_field_data}
 */
function bp_profile_field_data( $args = '' ) {
	echo bp_get_profile_field_data( $args );
}

	/**
	 * Return XProfile field data.
	 *
	 * @since 1.2.0
	 *
	 * @param string|array $args {
	 *    Array of arguments for field data.
	 *
	 *    @type string|int|bool $field   Field identifier.
	 *    @type int             $user_id ID of the user to get field data for.
	 * }
	 * @return mixed
	 */
	function bp_get_profile_field_data( $args = '' ) {

		$r = bp_parse_args(
			$args,
			array(
				'field'   => false, // Field name or ID.
				'user_id' => bp_displayed_user_id(),
			)
		);

		/**
		 * Filters the profile field data.
		 *
		 * @since 1.2.0
		 * @since 2.6.0 Added the `$r` parameter.
		 *
		 * @param mixed $value Profile data for a specific field for the user.
		 * @param array $r     Array of parsed arguments.
		 */
		return apply_filters( 'bp_get_profile_field_data', xprofile_get_field_data( $r['field'], $r['user_id'] ), $r );
	}

/**
 * Get all profile field groups.
 *
 * @since 2.1.0
 *
 * @return array $groups
 */
function bp_profile_get_field_groups() {

	$groups = wp_cache_get( 'all', 'bp_xprofile_groups' );
	if ( false === $groups ) {
		$groups = bp_xprofile_get_groups( array( 'fetch_fields' => true ) );
		wp_cache_set( 'all', $groups, 'bp_xprofile_groups' );
	}

	/**
	 * Filters all profile field groups.
	 *
	 * @since 2.1.0
	 *
	 * @param array $groups Array of available profile field groups.
	 */
	return apply_filters( 'bp_profile_get_field_groups', $groups );
}

/**
 * Check if there is more than one group of fields for the profile being edited.
 *
 * @since 2.1.0
 *
 * @return bool True if there is more than one profile field group.
 */
function bp_profile_has_multiple_groups() {
	$has_multiple_groups = count( (array) bp_profile_get_field_groups() ) > 1;

	/**
	 * Filters if there is more than one group of fields for the profile being edited.
	 *
	 * @since 2.1.0
	 *
	 * @param bool $has_multiple_groups Whether or not there are multiple groups.
	 */
	return (bool) apply_filters( 'bp_profile_has_multiple_groups', $has_multiple_groups );
}

/**
 * Output the tabs to switch between profile field groups.
 *
 * @since 1.0.0
 */
function bp_profile_group_tabs() {
	echo bp_get_profile_group_tabs();

	/**
	 * Fires at the end of the tab output for switching between profile field
	 * groups. This action is in a strange place for legacy reasons.
	 *
	 * @since 1.0.0
	 */
	do_action( 'xprofile_profile_group_tabs' );
}

/**
 * Return the XProfile group tabs.
 *
 * @since 2.3.0
 *
 * @return string
 */
function bp_get_profile_group_tabs() {

	// Get field group data.
	$groups     = bp_profile_get_field_groups();
	$group_name = bp_get_profile_group_name();
	$tabs       = array();

	// Loop through field groups and put a tab-lst together.
	for ( $i = 0, $count = count( $groups ); $i < $count; ++$i ) {

		// Setup the selected class.
		$selected = '';
		if ( $group_name === $groups[ $i ]->name ) {
			$selected = ' class="current"';
		}

		// Skip if group has no fields.
		if ( empty( $groups[ $i ]->fields ) ) {
			continue;
		}

		// Build the profile field group link.
		$link   = trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/edit/group/' . $groups[ $i ]->id );

		// Add tab to end of tabs array.
		$tabs[] = sprintf(
			'<li %1$s><a href="%2$s">%3$s</a></li>',
			$selected,
			esc_url( $link ),
			esc_html( apply_filters( 'bp_get_the_profile_group_name', $groups[ $i ]->name ) )
		);
	}

	/**
	 * Filters the tabs to display for profile field groups.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $tabs       Array of tabs to display.
	 * @param array  $groups     Array of profile groups.
	 * @param string $group_name Name of the current group displayed.
	 */
	$tabs = apply_filters( 'xprofile_filter_profile_group_tabs', $tabs, $groups, $group_name );

	return join( '', $tabs );
}

/**
 * Output the XProfile group name.
 *
 * @since 1.0.0
 *
 * @param bool $deprecated Deprecated boolean parameter.
 *
 * @return string|null
 */
function bp_profile_group_name( $deprecated = true ) {
	if ( ! $deprecated ) {
		return bp_get_profile_group_name();
	} else {
		echo bp_get_profile_group_name();
	}
}

	/**
	 * Return the XProfile group name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function bp_get_profile_group_name() {

		// Check action variable.
		$group_id = bp_action_variable( 1 );
		if ( empty( $group_id ) || ! is_numeric( $group_id ) ) {
			$group_id = 1;
		}

		// Check for cached group.
		$group = new BP_XProfile_Group( $group_id );

		/**
		 * Filters the profile group name.
		 *
		 * @since 1.0.0
		 * @since 2.6.0 Added the `$group_id` parameter
		 *
		 * @param string $name     Name of the profile group.
		 * @param int    $group_id ID of the profile group.
		 */
		return apply_filters( 'bp_get_profile_group_name', $group->name, $group_id );
	}

/**
 * Render a formatted string displaying when a profile was last updated.
 *
 * @since 1.0.0
 */
function bp_profile_last_updated() {

	$last_updated = bp_get_profile_last_updated();

	if ( empty( $last_updated ) ) {
		_e( 'Profile not recently updated.', 'buddypress' );
	} else {
		echo $last_updated;
	}
}

	/**
	 * Return a formatted string displaying when a profile was last updated.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|string
	 */
	function bp_get_profile_last_updated() {

		$last_updated = bp_get_user_meta( bp_displayed_user_id(), 'profile_last_updated', true );

		if ( ! empty( $last_updated ) ) {

			/**
			 * Filters the formatted string used to display when a profile was last updated.
			 *
			 * @since 1.0.0
			 *
			 * @param string $value Formatted last updated indicator string.
			 */
			return apply_filters(
				'bp_get_profile_last_updated',
				/* translators: %s: last activity timestamp (e.g. "active 1 hour ago") */
				sprintf( __( 'Profile updated %s', 'buddypress' ), bp_core_time_since( strtotime( $last_updated ) ) )
			);
		}

		return false;
	}

/**
 * Display the current profile group ID.
 *
 * @since 1.1.0
 */
function bp_current_profile_group_id() {
	echo bp_get_current_profile_group_id();
}

	/**
	 * Return the current profile group ID.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	function bp_get_current_profile_group_id() {
		$profile_group_id = bp_action_variable( 1 );
		if ( empty( $profile_group_id ) ) {
			$profile_group_id = 1;
		}

		/**
		 * Filters the current profile group ID.
		 *
		 * Possible values are admin/profile/edit/[group-id].
		 *
		 * @since 1.1.0
		 *
		 * @param int $profile_group_id Current profile group ID.
		 */
		return (int) apply_filters( 'bp_get_current_profile_group_id', $profile_group_id );
	}

/**
 * Render an edit profile button.
 *
 * @since 1.0.0
 */
function bp_edit_profile_button() {
	bp_button( array(
		'id'                => 'edit_profile',
		'component'         => 'xprofile',
		'must_be_logged_in' => true,
		'block_self'        => true,
		'link_href'         => trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/edit' ),
		'link_class'        => 'edit',
		'link_text'         => __( 'Edit Profile', 'buddypress' ),
	) );
}

/** Visibility ****************************************************************/

/**
 * Echo the field visibility radio buttons.
 *
 * @since 1.6.0
 *
 * @param array|string $args Args for the radio buttons. See {@link bp_profile_get_visibility_radio_buttons}
 */
function bp_profile_visibility_radio_buttons( $args = '' ) {
	echo bp_profile_get_visibility_radio_buttons( $args );
}
	/**
	 * Return the field visibility radio buttons.
	 *
	 * @since 1.6.0
	 *
	 * @param array|string $args {
	 *    Args for the radio buttons.
	 *
	 *    @type int    $field_id     ID of the field to render.
	 *    @type string $before       Markup to render before the field.
	 *    @type string $after        Markup to render after the field.
	 *    @type string $before_radio Markup to render before the radio button.
	 *    @type string $after_radio  Markup to render after the radio button.
	 *    @type string $class        Class to apply to the field markup.
	 * }
	 * @return string $retval
	 */
	function bp_profile_get_visibility_radio_buttons( $args = '' ) {

		// Parse optional arguments.
		$r = bp_parse_args(
			$args,
			array(
				'field_id'     => bp_get_the_profile_field_id(),
				'before'       => '<div class="radio">',
				'after'        => '</div>',
				'before_radio' => '',
				'after_radio'  => '',
				'class'        => 'bp-xprofile-visibility',
			),
			'xprofile_visibility_radio_buttons'
		);

		// Empty return value, filled in below if a valid field ID is found.
		$retval = '';

		// Only do-the-do if there's a valid field ID.
		if ( ! empty( $r['field_id'] ) ) :

			// Start the output buffer.
			ob_start();

			// Output anything before.
			echo $r['before']; ?>

			<?php if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>

				<?php foreach( bp_xprofile_get_visibility_levels() as $level ) : ?>

					<?php printf( $r['before_radio'], esc_attr( $level['id'] ) ); ?>

					<label for="<?php echo esc_attr( 'see-field_' . $r['field_id'] . '_' . $level['id'] ); ?>">
						<input type="radio" id="<?php echo esc_attr( 'see-field_' . $r['field_id'] . '_' . $level['id'] ); ?>" name="<?php echo esc_attr( 'field_' . $r['field_id'] . '_visibility' ); ?>" value="<?php echo esc_attr( $level['id'] ); ?>" <?php checked( $level['id'], bp_get_the_profile_field_visibility_level() ); ?> />
						<span class="field-visibility-text"><?php echo esc_html( $level['label'] ); ?></span>
					</label>

					<?php echo $r['after_radio']; ?>

				<?php endforeach; ?>

			<?php endif;

			// Output anything after.
			echo $r['after'];

			// Get the output buffer and empty it.
			$retval = ob_get_clean();
		endif;

		/**
		 * Filters the radio buttons for setting visibility.
		 *
		 * @since 1.6.0
		 *
		 * @param string $retval HTML output for the visibility radio buttons.
		 * @param array  $r      Parsed arguments to be used with display.
		 * @param array  $args   Original passed in arguments to be used with display.
		 */
		return apply_filters( 'bp_profile_get_visibility_radio_buttons', $retval, $r, $args );
	}

/**
 * Output the XProfile field visibility select list for settings.
 *
 * @since 2.0.0
 *
 * @param array|string $args Args for the select list. See {@link bp_profile_get_settings_visibility_select}
 */
function bp_profile_settings_visibility_select( $args = '' ) {
	echo bp_profile_get_settings_visibility_select( $args );
}
	/**
	 * Return the XProfile field visibility select list for settings.
	 *
	 * @since 2.0.0
	 *
	 * @param array|string $args {
	 *    Args for the select list.
	 *
	 *    @type int    $field_id ID of the field to render.
	 *    @type string $before   Markup to render before the field.
	 *    @type string $before_controls  markup before form controls.
	 *    @type string $after    Markup to render after the field.
	 *    @type string $after_controls Markup after the form controls.
	 *    @type string $class    Class to apply to the field markup.
	 *    @type string $label_class Class to apply for the label element.
	 *    @type string $notoggle_tag Markup element to use for notoggle tag.
	 *    @type string $notoggle_class Class to apply to the notoggle element.
	 * }
	 * @return string $retval
	 */
	function bp_profile_get_settings_visibility_select( $args = '' ) {

		// Parse optional arguments.
		$r = bp_parse_args(
			$args,
			array(
				'field_id'        => bp_get_the_profile_field_id(),
				'before'          => '',
				'before_controls' => '',
				'after'           => '',
				'after_controls'  => '',
				'class'           => 'bp-xprofile-visibility',
				'label_class'     => 'bp-screen-reader-text',
				'notoggle_tag'    => 'span',
				'notoggle_class'  => 'field-visibility-settings-notoggle',
			),
			'xprofile_settings_visibility_select'
		);

		// Empty return value, filled in below if a valid field ID is found.
		$retval = '';

		// Only do-the-do if there's a valid field ID.
		if ( ! empty( $r['field_id'] ) ) :

			// Start the output buffer.
			ob_start();

			// Output anything before.
			echo $r['before']; ?>

			<?php if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>

			<?php echo $r['before_controls']; ?>

				<label for="<?php echo esc_attr( 'field_' . $r['field_id'] ) ; ?>_visibility" class="<?php echo esc_attr( $r['label_class'] ); ?>"><?php
					/* translators: accessibility text */
					_e( 'Select visibility', 'buddypress' );
				?></label>
				<select class="<?php echo esc_attr( $r['class'] ); ?>" name="<?php echo esc_attr( 'field_' . $r['field_id'] ) ; ?>_visibility" id="<?php echo esc_attr( 'field_' . $r['field_id'] ) ; ?>_visibility">

					<?php foreach ( bp_xprofile_get_visibility_levels() as $level ) : ?>

						<option value="<?php echo esc_attr( $level['id'] ); ?>" <?php selected( $level['id'], bp_get_the_profile_field_visibility_level() ); ?>><?php echo esc_html( $level['label'] ); ?></option>

					<?php endforeach; ?>

				</select>

			<?php echo $r['after_controls']; ?>

			<?php else : ?>

				<<?php echo esc_html( $r['notoggle_tag'] ); ?> class="<?php echo esc_attr( $r['notoggle_class'] ); ?>"><?php bp_the_profile_field_visibility_level_label(); ?></<?php echo esc_html( $r['notoggle_tag'] ); ?>>

			<?php endif;

			// Output anything after.
			echo $r['after'];

			// Get the output buffer and empty it.
			$retval = ob_get_clean();
		endif;

		/**
		 * Filters the dropdown list for setting visibility.
		 *
		 * @since 2.0.0
		 *
		 * @param string $retval HTML output for the visibility dropdown list.
		 * @param array  $r      Parsed arguments to be used with display.
		 * @param array  $args   Original passed in arguments to be used with display.
		 */
		return apply_filters( 'bp_profile_settings_visibility_select', $retval, $r, $args );
	}

/**
 * Output the 'required' markup in extended profile field labels.
 *
 * @since 2.4.0
 */
function bp_the_profile_field_required_label() {
	echo bp_get_the_profile_field_required_label();
}

	/**
	 * Return the 'required' markup in extended profile field labels.
	 *
	 * @since 2.4.0
	 *
	 * @return string HTML for the required label.
	 */
	function bp_get_the_profile_field_required_label() {
		$retval = '';

		if ( bp_get_the_profile_field_is_required() ) {
			$translated_string = __( '(required)', 'buddypress' );

			$retval = ' <span class="bp-required-field-label">';
			$retval .= apply_filters( 'bp_get_the_profile_field_required_label', $translated_string, bp_get_the_profile_field_id() );
			$retval .= '</span>';

		}

		return $retval;
	}
