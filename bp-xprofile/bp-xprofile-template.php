<?php

/**
 * BuddyPress XProfile Template Tags
 *
 * @package BuddyPress
 * @subpackage XProfileTemplate
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_XProfile_Data_Template {
	var $current_group = -1;
	var $group_count;
	var $groups;
	var $group;

	var $current_field = -1;
	var $field_count;
	var $field_has_data;
	var $field;

	var $in_the_loop;
	var $user_id;

	function __construct( $user_id, $profile_group_id, $hide_empty_groups = false, $fetch_fields = false, $fetch_field_data = false, $exclude_groups = false, $exclude_fields = false, $hide_empty_fields = false, $fetch_visibility_level = false ) {
		$this->groups = BP_XProfile_Group::get( array(
			'profile_group_id'    => $profile_group_id,
			'user_id'             => $user_id,
			'hide_empty_groups'   => $hide_empty_groups,
			'hide_empty_fields'   => $hide_empty_fields,
			'fetch_fields'        => $fetch_fields,
			'fetch_field_data'    => $fetch_field_data,
			'fetch_visibility_level' => $fetch_visibility_level,
			'exclude_groups'      => $exclude_groups,
			'exclude_fields'      => $exclude_fields
		) );

		$this->group_count = count($this->groups);
		$this->user_id = $user_id;
	}

	function has_groups() {
		if ( $this->group_count )
			return true;

		return false;
	}

	function next_group() {
		$this->current_group++;

		$this->group         = $this->groups[$this->current_group];
		$this->group->fields = apply_filters( 'xprofile_group_fields', $this->group->fields, $this->group->id );
		$this->field_count   = count( $this->group->fields );

		return $this->group;
	}

	function rewind_groups() {
		$this->current_group = -1;
		if ( $this->group_count > 0 ) {
			$this->group = $this->groups[0];
		}
	}

	function profile_groups() {
		if ( $this->current_group + 1 < $this->group_count ) {
			return true;
		} elseif ( $this->current_group + 1 == $this->group_count ) {
			do_action('xprofile_template_loop_end');
			// Do some cleaning up after the loop
			$this->rewind_groups();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_profile_group() {
		global $group;

		$this->in_the_loop = true;
		$group = $this->next_group();

		if ( 0 == $this->current_group ) // loop has just started
			do_action('xprofile_template_loop_start');
	}

	/**** FIELDS ****/

	function next_field() {
		$this->current_field++;

		$this->field = $this->group->fields[$this->current_field];
		return $this->field;
	}

	function rewind_fields() {
		$this->current_field = -1;
		if ( $this->field_count > 0 ) {
			$this->field = $this->group->fields[0];
		}
	}

	function has_fields() {
		$has_data = false;

		for ( $i = 0, $count = count( $this->group->fields ); $i < $count; ++$i ) {
			$field = &$this->group->fields[$i];

			if ( !empty( $field->data ) && $field->data->value != null ) {
				$has_data = true;
			}
		}

		if ( $has_data )
			return true;

		return false;
	}

	function profile_fields() {
		if ( $this->current_field + 1 < $this->field_count ) {
			return true;
		} elseif ( $this->current_field + 1 == $this->field_count ) {
			// Do some cleaning up after the loop
			$this->rewind_fields();
		}

		return false;
	}

	function the_profile_field() {
		global $field;

		$field = $this->next_field();

		$value = !empty( $field->data ) && !empty( $field->data->value ) ? maybe_unserialize( $field->data->value ) : false;

		if ( !empty( $value ) ) {
			$this->field_has_data = true;
		} else {
			$this->field_has_data = false;
		}
	}
}

function xprofile_get_profile() {
	locate_template( array( 'profile/profile-loop.php'), true );
}

function bp_has_profile( $args = '' ) {
	global $profile_template;

	// Only show empty fields if we're on the Dashboard, or we're on a user's profile edit page,
	// or this is a registration page
	$hide_empty_fields_default = ( !is_network_admin() && !is_admin() && !bp_is_user_profile_edit() && !bp_is_register_page() );

	// We only need to fetch visibility levels when viewing your own profile
	if ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) || bp_is_register_page() ) {
		$fetch_visibility_level_default = true;
	} else {
		$fetch_visibility_level_default = false;
	}

	$defaults = array(
		'user_id'             => bp_displayed_user_id(),
		'profile_group_id'    => false,
		'hide_empty_groups'   => true,
		'hide_empty_fields'   => $hide_empty_fields_default,
		'fetch_fields'        => true,
		'fetch_field_data'    => true,
		'fetch_visibility_level' => $fetch_visibility_level_default,
		'exclude_groups'      => false, // Comma-separated list of profile field group IDs to exclude
		'exclude_fields'      => false  // Comma-separated list of profile field IDs to exclude
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$profile_template = new BP_XProfile_Data_Template( $user_id, $profile_group_id, $hide_empty_groups, $fetch_fields, $fetch_field_data, $exclude_groups, $exclude_fields, $hide_empty_fields, $fetch_visibility_level );
	return apply_filters( 'bp_has_profile', $profile_template->has_groups(), $profile_template );
}

function bp_profile_groups() {
	global $profile_template;
	return $profile_template->profile_groups();
}

function bp_the_profile_group() {
	global $profile_template;
	return $profile_template->the_profile_group();
}

function bp_profile_group_has_fields() {
	global $profile_template;
	return $profile_template->has_fields();
}

function bp_field_css_class( $class = false ) {
	echo bp_get_field_css_class( $class );
}
	function bp_get_field_css_class( $class = false ) {
		global $profile_template;

		$css_classes = array();

		if ( $class )
			$css_classes[] = sanitize_title( esc_attr( $class ) );

		// Set a class with the field ID
		$css_classes[] = 'field_' . $profile_template->field->id;

		// Set a class with the field name (sanitized)
		$css_classes[] = 'field_' . sanitize_title( $profile_template->field->name );

		if ( $profile_template->current_field % 2 == 1 )
			$css_classes[] = 'alt';

		$css_classes = apply_filters_ref_array( 'bp_field_css_classes', array( &$css_classes ) );

		return apply_filters( 'bp_get_field_css_class', ' class="' . implode( ' ', $css_classes ) . '"' );
	}

function bp_field_has_data() {
	global $profile_template;
	return $profile_template->field_has_data;
}

function bp_field_has_public_data() {
	global $profile_template;

	if ( $profile_template->field_has_data )
		return true;

	return false;
}

function bp_the_profile_group_id() {
	echo bp_get_the_profile_group_id();
}
	function bp_get_the_profile_group_id() {
		global $group;
		return apply_filters( 'bp_get_the_profile_group_id', $group->id );
	}

function bp_the_profile_group_name() {
	echo bp_get_the_profile_group_name();
}
	function bp_get_the_profile_group_name() {
		global $group;
		return apply_filters( 'bp_get_the_profile_group_name', $group->name );
	}

function bp_the_profile_group_slug() {
	echo bp_get_the_profile_group_slug();
}
	function bp_get_the_profile_group_slug() {
		global $group;
		return apply_filters( 'bp_get_the_profile_group_slug', sanitize_title( $group->name ) );
	}

function bp_the_profile_group_description() {
	echo bp_get_the_profile_group_description();
}
	function bp_get_the_profile_group_description() {
		global $group;
		echo apply_filters( 'bp_get_the_profile_group_description', $group->description );
	}

function bp_the_profile_group_edit_form_action() {
	echo bp_get_the_profile_group_edit_form_action();
}
	function bp_get_the_profile_group_edit_form_action() {
		global $bp, $group;

		return apply_filters( 'bp_get_the_profile_group_edit_form_action', trailingslashit( bp_displayed_user_domain() . $bp->profile->slug . '/edit/group/' . $group->id ) );
	}

function bp_the_profile_group_field_ids() {
	echo bp_get_the_profile_group_field_ids();
}
	function bp_get_the_profile_group_field_ids() {
		global $group;

		$field_ids = '';
		foreach ( (array) $group->fields as $field )
			$field_ids .= $field->id . ',';

		return substr( $field_ids, 0, -1 );
	}

function bp_profile_fields() {
	global $profile_template;
	return $profile_template->profile_fields();
}

function bp_the_profile_field() {
	global $profile_template;
	return $profile_template->the_profile_field();
}

function bp_the_profile_field_id() {
	echo bp_get_the_profile_field_id();
}
	function bp_get_the_profile_field_id() {
		global $field;
		return apply_filters( 'bp_get_the_profile_field_id', $field->id );
	}

function bp_the_profile_field_name() {
	echo bp_get_the_profile_field_name();
}
	function bp_get_the_profile_field_name() {
		global $field;

		return apply_filters( 'bp_get_the_profile_field_name', $field->name );
	}

function bp_the_profile_field_value() {
	echo bp_get_the_profile_field_value();
}
	function bp_get_the_profile_field_value() {
		global $field;

		$field->data->value = bp_unserialize_profile_field( $field->data->value );

		return apply_filters( 'bp_get_the_profile_field_value', $field->data->value, $field->type, $field->id );
	}

function bp_the_profile_field_edit_value() {
	echo bp_get_the_profile_field_edit_value();
}
	function bp_get_the_profile_field_edit_value() {
		global $field;

		/**
		 * Check to see if the posted value is different, if it is re-display this
		 * value as long as it's not empty and a required field.
		 */
		if ( !isset( $field->data ) ) {
			$field->data = new stdClass;
		}

		if ( !isset( $field->data->value ) ) {
			$field->data->value = '';
		}

		if ( isset( $_POST['field_' . $field->id] ) && $field->data->value != $_POST['field_' . $field->id] ) {
			if ( !empty( $_POST['field_' . $field->id] ) )
				$field->data->value = $_POST['field_' . $field->id];
			else
				$field->data->value = '';
		}

		$field_value = isset( $field->data->value ) ? bp_unserialize_profile_field( $field->data->value ) : '';

		return apply_filters( 'bp_get_the_profile_field_edit_value', $field_value, $field->type, $field->id );
	}

function bp_the_profile_field_type() {
	echo bp_get_the_profile_field_type();
}
	function bp_get_the_profile_field_type() {
		global $field;

		return apply_filters( 'bp_the_profile_field_type', $field->type );
	}

function bp_the_profile_field_description() {
	echo bp_get_the_profile_field_description();
}
	function bp_get_the_profile_field_description() {
		global $field;

		return apply_filters( 'bp_get_the_profile_field_description', $field->description );
	}

function bp_the_profile_field_input_name() {
	echo bp_get_the_profile_field_input_name();
}
	function bp_get_the_profile_field_input_name() {
		global $field;

		$array_box = false;
		if ( 'multiselectbox' == $field->type )
			$array_box = '[]';

		return apply_filters( 'bp_get_the_profile_field_input_name', 'field_' . $field->id . $array_box );
	}

/**
 * bp_the_profile_field_options()
 *
 * Displays field options HTML for field types of 'selectbox', 'multiselectbox',
 * 'radio', 'checkbox', and 'datebox'.
 *
 * @package BuddyPress Xprofile
 * @since 1.1
 *
 * @uses bp_get_the_profile_field_options()
 *
 * @param array $args Specify type for datebox. Allowed 'day', 'month', 'year'.
 */
function bp_the_profile_field_options( $args = '' ) {
	echo bp_get_the_profile_field_options( $args );
}
	/**
	 * bp_get_the_profile_field_options()
	 *
	 * Retrieves field options HTML for field types of 'selectbox', 'multiselectbox',
	 * 'radio', 'checkbox', and 'datebox'.
	 *
	 * @package BuddyPress Xprofile
	 * @since 1.1
	 *
	 * @uses BP_XProfile_Field::get_children()
	 * @uses BP_XProfile_ProfileData::get_value_byid()
	 *
	 * @param array $args Specify type for datebox. Allowed 'day', 'month', 'year'.
	 */
	function bp_get_the_profile_field_options( $args = '' ) {
		global $field;

		// Generally a required dropdown field will not get a blank value at
		// the top. Set 'null_on_required' to true if you want this blank value
		// even on required fields.
		$defaults = array(
			'type' 		       => false,
			'null_on_required' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		// In some cases, the $field global is not an instantiation of the BP_XProfile_Field
		// class. However, we have to make sure that all data originally in $field gets
		// merged back in, after reinstantiation.
		if ( !method_exists( $field, 'get_children' ) ) {
			$field_obj = new BP_XProfile_Field( $field->id );

			foreach( $field as $field_prop => $field_prop_value ) {
				if ( !isset( $field_obj->{$field_prop} ) ) {
					$field_obj->{$field_prop} = $field_prop_value;
				}
			}

			$field = $field_obj;
		}

		$options = $field->get_children();

		// Setup some defaults
		$html     = '';
		$selected = '';

		switch ( $field->type ) {
			case 'selectbox':

				if ( !$field->is_required || $null_on_required ) {
					$html .= '<option value="">' . /* translators: no option picked in select box */ __( '----', 'buddypress' ) . '</option>';
				}

				$original_option_values = '';
				$original_option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $field->id ) );

				if ( empty( $original_option_values ) && !empty( $_POST['field_' . $field->id] ) ) {
					$original_option_values = $_POST['field_' . $field->id];
				}

				$option_values = (array) $original_option_values;

				for ( $k = 0, $count = count( $options ); $k < $count; ++$k ) {

					// Check for updated posted values, but errors preventing them from being saved first time
					foreach( $option_values as $i => $option_value ) {
						if ( isset( $_POST['field_' . $field->id] ) && $_POST['field_' . $field->id] != $option_value ) {
							if ( !empty( $_POST['field_' . $field->id] ) ) {
								$option_values[$i] = $_POST['field_' . $field->id];
							}
						}
					}

					$selected = '';

					// Run the allowed option name through the before_save filter, so we'll be sure to get a match
					$allowed_options = xprofile_sanitize_data_value_before_save( $options[$k]->name, false, false );

					// First, check to see whether the user-entered value matches
					if ( in_array( $allowed_options, (array) $option_values ) ) {
						$selected = ' selected="selected"';
					}

					// Then, if the user has not provided a value, check for defaults
					if ( !is_array( $original_option_values ) && empty( $option_values ) && $options[$k]->is_default_option ) {
						$selected = ' selected="selected"';
					}

					$html .= apply_filters( 'bp_get_the_profile_field_options_select', '<option' . $selected . ' value="' . esc_attr( stripslashes( $options[$k]->name ) ) . '">' . esc_attr( stripslashes( $options[$k]->name ) ) . '</option>', $options[$k], $field->id, $selected, $k );
				}
				break;

			case 'multiselectbox':
				$original_option_values = '';
				$original_option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $field->id ) );

				if ( empty( $original_option_values ) && !empty( $_POST['field_' . $field->id] ) ) {
					$original_option_values = $_POST['field_' . $field->id];
				}

				$option_values = (array) $original_option_values;

				for ( $k = 0, $count = count( $options ); $k < $count; ++$k ) {

					// Check for updated posted values, but errors preventing them from being saved first time
					foreach( $option_values as $i => $option_value ) {
						if ( isset( $_POST['field_' . $field->id] ) && $_POST['field_' . $field->id][$i] != $option_value ) {
							if ( !empty( $_POST['field_' . $field->id][$i] ) ) {
								$option_values[] = $_POST['field_' . $field->id][$i];
							}
						}
					}
					$selected = '';

					// Run the allowed option name through the before_save filter, so we'll be sure to get a match
					$allowed_options = xprofile_sanitize_data_value_before_save( $options[$k]->name, false, false );

					// First, check to see whether the user-entered value matches
					if ( in_array( $allowed_options, (array) $option_values ) ) {
						$selected = ' selected="selected"';
					}

					// Then, if the user has not provided a value, check for defaults
					if ( !is_array( $original_option_values ) && empty( $option_values ) && !empty( $options[$k]->is_default_option ) ) {
						$selected = ' selected="selected"';
					}

					$html .= apply_filters( 'bp_get_the_profile_field_options_multiselect', '<option' . $selected . ' value="' . esc_attr( stripslashes( $options[$k]->name ) ) . '">' . esc_attr( stripslashes( $options[$k]->name ) ) . '</option>', $options[$k], $field->id, $selected, $k );
				}
				break;

			case 'radio':
				$html .= '<div id="field_' . $field->id . '">';
				$option_value = BP_XProfile_ProfileData::get_value_byid( $field->id );

				for ( $k = 0, $count = count( $options ); $k < $count; ++$k ) {

					// Check for updated posted values, but errors preventing them from being saved first time
					if ( isset( $_POST['field_' . $field->id] ) && $option_value != $_POST['field_' . $field->id] ) {
						if ( !empty( $_POST['field_' . $field->id] ) ) {
							$option_value = $_POST['field_' . $field->id];
						}
					}

					// Run the allowed option name through the before_save
					// filter, so we'll be sure to get a match
					$allowed_options = xprofile_sanitize_data_value_before_save( $options[$k]->name, false, false );
					$selected        = '';

					// @todo $value is never created
					if ( $option_value == $allowed_options || !empty( $value ) && $value == $allowed_options || ( empty( $option_value ) && !empty( $options[$k]->is_default_option ) ) )
						$selected = ' checked="checked"';

					$html .= apply_filters( 'bp_get_the_profile_field_options_radio', '<label><input' . $selected . ' type="radio" name="field_' . $field->id . '" id="option_' . $options[$k]->id . '" value="' . esc_attr( stripslashes( $options[$k]->name ) ) . '"> ' . esc_attr( stripslashes( $options[$k]->name ) ) . '</label>', $options[$k], $field->id, $selected, $k );
				}

				$html .= '</div>';
				break;

			case 'checkbox':
				$option_values = BP_XProfile_ProfileData::get_value_byid( $field->id );
				$option_values = (array) maybe_unserialize( $option_values );

				// Check for updated posted values, but errors preventing them from being saved first time
				if ( isset( $_POST['field_' . $field->id] ) && $option_values != maybe_serialize( $_POST['field_' . $field->id] ) ) {
					if ( !empty( $_POST['field_' . $field->id] ) )
						$option_values = $_POST['field_' . $field->id];
				}

				for ( $k = 0, $count = count( $options ); $k < $count; ++$k ) {
					$selected = '';

					// First, check to see whether the user's saved values
					// match the option
					for ( $j = 0, $count_values = count( $option_values ); $j < $count_values; ++$j ) {

						// Run the allowed option name through the
						// before_save filter, so we'll be sure to get a match
						$allowed_options = xprofile_sanitize_data_value_before_save( $options[$k]->name, false, false );

						// @todo $value is never created
						if ( $option_values[$j] == $allowed_options || @in_array( $allowed_options, $option_values ) ) {
							$selected = ' checked="checked"';
							break;
						}
					}

					// If the user has not yet supplied a value for this field,
					// check to see whether there is a default value available
					if ( !is_array( $option_values ) && empty( $option_values ) && empty( $selected ) && !empty( $options[$k]->is_default_option ) ) {
						$selected = ' checked="checked"';
					}

					$html .= apply_filters( 'bp_get_the_profile_field_options_checkbox', '<label><input' . $selected . ' type="checkbox" name="field_' . $field->id . '[]" id="field_' . $options[$k]->id . '_' . $k . '" value="' . esc_attr( stripslashes( $options[$k]->name ) ) . '"> ' . esc_attr( stripslashes( $options[$k]->name ) ) . '</label>', $options[$k], $field->id, $selected, $k );
				}
				break;

			case 'datebox':
				$date = BP_XProfile_ProfileData::get_value_byid( $field->id );

				// Set day, month, year defaults
				$day   = '';
				$month = '';
				$year  = '';

				if ( !empty( $date ) ) {

					// If Unix timestamp
					if ( is_numeric( $date ) ) {
						$day   = date( 'j', $date );
						$month = date( 'F', $date );
						$year  = date( 'Y', $date );

					// If MySQL timestamp
					} else {
						$day   = mysql2date( 'j', $date );
						$month = mysql2date( 'F', $date, false ); // Not localized, so that selected() works below
						$year  = mysql2date( 'Y', $date );
					}
				}

				// Check for updated posted values, and errors preventing
				// them from being saved first time.
				if ( !empty( $_POST['field_' . $field->id . '_day'] ) ) {
					if ( $day != $_POST['field_' . $field->id . '_day'] ) {
						$day = $_POST['field_' . $field->id . '_day'];
					}
				}

				if ( !empty( $_POST['field_' . $field->id . '_month'] ) ) {
					if ( $month != $_POST['field_' . $field->id . '_month'] ) {
						$month = $_POST['field_' . $field->id . '_month'];
					}
				}

				if ( !empty( $_POST['field_' . $field->id . '_year'] ) ) {
					if ( $year != date( "j", $_POST['field_' . $field->id . '_year'] ) ) {
						$year = $_POST['field_' . $field->id . '_year'];
					}
				}

				// $type will be passed by calling function when needed
				switch ( $type ) {
					case 'day':
						$html .= '<option value=""' . selected( $day, '', false ) . '>--</option>';

						for ( $i = 1; $i < 32; ++$i ) {
							$html .= '<option value="' . $i .'"' . selected( $day, $i, false ) . '>' . $i . '</option>';
						}
						break;

					case 'month':
						$eng_months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );

						$months = array(
							__( 'January', 'buddypress' ),
							__( 'February', 'buddypress' ),
							__( 'March', 'buddypress' ),
							__( 'April', 'buddypress' ),
							__( 'May', 'buddypress' ),
							__( 'June', 'buddypress' ),
							__( 'July', 'buddypress' ),
							__( 'August', 'buddypress' ),
							__( 'September', 'buddypress' ),
							__( 'October', 'buddypress' ),
							__( 'November', 'buddypress' ),
							__( 'December', 'buddypress' )
						);

						$html .= '<option value=""' . selected( $month, '', false ) . '>------</option>';

						for ( $i = 0; $i < 12; ++$i ) {
							$html .= '<option value="' . $eng_months[$i] . '"' . selected( $month, $eng_months[$i], false ) . '>' . $months[$i] . '</option>';
						}
						break;

					case 'year':
						$html .= '<option value=""' . selected( $year, '', false ) . '>----</option>';

						for ( $i = 2037; $i > 1901; $i-- ) {
							$html .= '<option value="' . $i .'"' . selected( $year, $i, false ) . '>' . $i . '</option>';
						}
						break;
				}

				$html = apply_filters( 'bp_get_the_profile_field_datebox', $html, $type, $day, $month, $year, $field->id, $date );

				break;
		}

		return $html;
	}

function bp_the_profile_field_is_required() {
	echo bp_get_the_profile_field_is_required();
}
	function bp_get_the_profile_field_is_required() {
		global $field;

		// Define locale variable(s)
		$retval = false;

		// Super admins can skip required check
		if ( bp_current_user_can( 'bp_moderate' ) && !is_admin() )
			$retval = false;

		// All other users will use the field's setting
		elseif ( isset( $field->is_required ) )
			$retval = $field->is_required;

		return apply_filters( 'bp_get_the_profile_field_is_required', (bool) $retval );
	}

/**
 * Echo the visibility level of this field
 */
function bp_the_profile_field_visibility_level() {
	echo bp_get_the_profile_field_visibility_level();
}
	/**
	 * Return the visibility level of this field
	 */
	function bp_get_the_profile_field_visibility_level() {
		global $field;

		$retval = !empty( $field->visibility_level ) ? $field->visibility_level : 'public';

		return apply_filters( 'bp_get_the_profile_field_visibility_level', $retval );
	}

/**
 * Echo the visibility level label of this field
 */
function bp_the_profile_field_visibility_level_label() {
	echo bp_get_the_profile_field_visibility_level_label();
}
	/**
	 * Return the visibility level label of this field
	 */
	function bp_get_the_profile_field_visibility_level_label() {
		global $field;

		$level  = !empty( $field->visibility_level ) ? $field->visibility_level : 'public';
		$fields = bp_xprofile_get_visibility_levels();

		return apply_filters( 'bp_get_the_profile_field_visibility_level_label', $fields[$level]['label'] );
	}


function bp_unserialize_profile_field( $value ) {
	if ( is_serialized($value) ) {
		$field_value = maybe_unserialize($value);
		$field_value = implode( ', ', $field_value );
		return $field_value;
	}

	return $value;
}

function bp_profile_field_data( $args = '' ) {
	echo bp_get_profile_field_data( $args );
}
	function bp_get_profile_field_data( $args = '' ) {

		$defaults = array(
			'field'   => false, // Field name or ID.
			'user_id' => bp_displayed_user_id()
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_profile_field_data', xprofile_get_field_data( $field, $user_id ) );
	}

function bp_profile_group_tabs() {
	global $bp, $group_name;

	if ( !$groups = wp_cache_get( 'xprofile_groups_inc_empty', 'bp' ) ) {
		$groups = BP_XProfile_Group::get( array( 'fetch_fields' => true ) );
		wp_cache_set( 'xprofile_groups_inc_empty', $groups, 'bp' );
	}

	if ( empty( $group_name ) )
		$group_name = bp_profile_group_name(false);

	$tabs = array();
	for ( $i = 0, $count = count( $groups ); $i < $count; ++$i ) {
		if ( $group_name == $groups[$i]->name )
			$selected = ' class="current"';
		else
			$selected = '';

		if ( !empty( $groups[$i]->fields ) ) {
			$link = trailingslashit( bp_displayed_user_domain() . $bp->profile->slug . '/edit/group/' . $groups[$i]->id );
			$tabs[] = sprintf( '<li %1$s><a href="%2$s">%3$s</a></li>', $selected, $link, esc_html( $groups[$i]->name ) );
		}
	}

	$tabs = apply_filters( 'xprofile_filter_profile_group_tabs', $tabs, $groups, $group_name );
	foreach ( (array) $tabs as $tab )
		echo $tab;

	do_action( 'xprofile_profile_group_tabs' );
}

function bp_profile_group_name( $deprecated = true ) {
	if ( !$deprecated ) {
		return bp_get_profile_group_name();
	} else {
		echo bp_get_profile_group_name();
	}
}
	function bp_get_profile_group_name() {
		if ( !$group_id = bp_action_variable( 1 ) )
			$group_id = 1;

		if ( !is_numeric( $group_id ) )
			$group_id = 1;

		if ( !$group = wp_cache_get( 'xprofile_group_' . $group_id, 'bp' ) ) {
			$group = new BP_XProfile_Group($group_id);
			wp_cache_set( 'xprofile_group_' . $group_id, $group, 'bp' );
		}

		return apply_filters( 'bp_get_profile_group_name', $group->name );
	}

function bp_avatar_upload_form() {
	global $bp;

	if ( !(int) $bp->site_options['bp-disable-avatar-uploads'] )
		bp_core_avatar_admin( null, bp_loggedin_user_domain() . $bp->profile->slug . '/change-avatar/', bp_loggedin_user_domain() . $bp->profile->slug . '/delete-avatar/' );
	else
		_e( 'Avatar uploads are currently disabled. Why not use a <a href="http://gravatar.com" target="_blank">gravatar</a> instead?', 'buddypress' );
}

function bp_profile_last_updated() {

	$last_updated = bp_get_profile_last_updated();

	if ( !$last_updated ) {
		_e( 'Profile not recently updated', 'buddypress' ) . '.';
	} else {
		echo $last_updated;
	}
}
	function bp_get_profile_last_updated() {

		$last_updated = bp_get_user_meta( bp_displayed_user_id(), 'profile_last_updated', true );

		if ( $last_updated )
			return apply_filters( 'bp_get_profile_last_updated', sprintf( __('Profile updated %s', 'buddypress'), bp_core_time_since( strtotime( $last_updated ) ) ) );

		return false;
	}

function bp_current_profile_group_id() {
	echo bp_get_current_profile_group_id();
}
	function bp_get_current_profile_group_id() {
		if ( !$profile_group_id = bp_action_variable( 1 ) )
			$profile_group_id = 1;

		return apply_filters( 'bp_get_current_profile_group_id', $profile_group_id ); // admin/profile/edit/[group-id]
	}

function bp_avatar_delete_link() {
	echo bp_get_avatar_delete_link();
}
	function bp_get_avatar_delete_link() {
		global $bp;

		return apply_filters( 'bp_get_avatar_delete_link', wp_nonce_url( bp_displayed_user_domain() . $bp->profile->slug . '/change-avatar/delete-avatar/', 'bp_delete_avatar_link' ) );
	}

function bp_edit_profile_button() {
	global $bp;

	bp_button( array (
		'id'                => 'edit_profile',
		'component'         => 'xprofile',
		'must_be_logged_in' => true,
		'block_self'        => true,
		'link_href'         => trailingslashit( bp_displayed_user_domain() . $bp->profile->slug . '/edit' ),
		'link_class'        => 'edit',
		'link_text'         => __( 'Edit Profile', 'buddypress' ),
		'link_title'        => __( 'Edit Profile', 'buddypress' ),
	) );
}

/**
 * Echo the field visibility radio buttons
 */
function bp_profile_visibility_radio_buttons() {
	echo bp_profile_get_visibility_radio_buttons();
}
	/**
	 * Return the field visibility radio buttons
	 */
	function bp_profile_get_visibility_radio_buttons() {
		$html = '<ul class="radio">';

		foreach( bp_xprofile_get_visibility_levels() as $level ) {
			$checked = $level['id'] == bp_get_the_profile_field_visibility_level() ? ' checked="checked" ' : '';

			$html .= '<li><label for="see-field_' . esc_attr( $level['id'] ) . '"><input type="radio" id="see-field_' . esc_attr( $level['id'] ) . '" name="field_' . bp_get_the_profile_field_id() . '_visibility" value="' . esc_attr( $level['id'] ) . '"' . $checked . ' /> ' . esc_html( $level['label'] ) . '</label></li>';
		}

		$html .= '</ul>';

		return apply_filters( 'bp_profile_get_visibility_radio_buttons', $html );
	}
?>
