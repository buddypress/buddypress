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

	function __construct( $user_id, $profile_group_id, $hide_empty_groups = false, $fetch_fields = false, $fetch_field_data = false, $exclude_groups = false, $exclude_fields = false, $hide_empty_fields = false, $fetch_visibility_level = false, $update_meta_cache = true ) {
		$this->groups = BP_XProfile_Group::get( array(
			'profile_group_id'    => $profile_group_id,
			'user_id'             => $user_id,
			'hide_empty_groups'   => $hide_empty_groups,
			'hide_empty_fields'   => $hide_empty_fields,
			'fetch_fields'        => $fetch_fields,
			'fetch_field_data'    => $fetch_field_data,
			'fetch_visibility_level' => $fetch_visibility_level,
			'exclude_groups'      => $exclude_groups,
			'exclude_fields'      => $exclude_fields,
			'update_meta_cache'   => $update_meta_cache,
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

		$this->group       = $this->groups[$this->current_group];
		$this->field_count = 0;

		if( ! empty( $this->group->fields ) ) {
			$this->group->fields = apply_filters( 'xprofile_group_fields', $this->group->fields, $this->group->id );
			$this->field_count   = count( $this->group->fields );
		}

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
		'exclude_fields'      => false,  // Comma-separated list of profile field IDs to exclude
		'update_meta_cache'   => true,
	);

	$r = bp_parse_args( $args, $defaults, 'has_profile' );
	extract( $r, EXTR_SKIP );

	$profile_template = new BP_XProfile_Data_Template( $user_id, $profile_group_id, $hide_empty_groups, $fetch_fields, $fetch_field_data, $exclude_groups, $exclude_fields, $hide_empty_fields, $fetch_visibility_level, $update_meta_cache );
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

		$css_classes[] = 'field_type_' . sanitize_title( $profile_template->field->type );
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
		return apply_filters( 'bp_get_the_profile_group_description', $group->description );
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

		if ( !empty( $group->fields ) ) {
			foreach ( (array) $group->fields as $field ) {
				$field_ids .= $field->id . ',';
			}
		}

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

		return apply_filters( 'bp_get_the_profile_field_input_name', 'field_' . $field->id );
	}

/**
 * Returns the action name for any signup errors related to this profile field
 *
 * In the registration templates, signup errors are pulled from the global
 * object and rendered at actions that look like 'bp_field_12_errors'. This
 * function allows the action name to be easily concatenated and called in the
 * following fashion:
 *   do_action( bp_get_the_profile_field_errors_action() );
 *
 * @since BuddyPress (1.8)
 * @return string The _errors action name corresponding to this profile field
 */
function bp_get_the_profile_field_errors_action() {
	global $field;
	return 'bp_field_' . $field->id . '_errors';
}

/**
 * bp_the_profile_field_options()
 *
 * Displays field options HTML for field types of 'selectbox', 'multiselectbox',
 * 'radio', 'checkbox', and 'datebox'.
 *
 * @package BuddyPress Xprofile
 * @since BuddyPress (1.1)
 *
 * @uses bp_get_the_profile_field_options()
 *
 * @param array $args Specify type for datebox. Allowed 'day', 'month', 'year'.
 */
function bp_the_profile_field_options( $args = array() ) {
	echo bp_get_the_profile_field_options( $args );
}
	/**
	 * bp_get_the_profile_field_options()
	 *
	 * Retrieves field options HTML for field types of 'selectbox', 'multiselectbox', 'radio', 'checkbox', and 'datebox'.
	 *
	 * @package BuddyPress Xprofile
	 * @since BuddyPress (1.1)
	 *
	 * @uses BP_XProfile_Field::get_children()
	 * @uses BP_XProfile_ProfileData::get_value_byid()
	 *
	 * @param array $args {
	 *     Array of optional arguments.
	 *     @type string|bool $type Type of datebox. False if it's not a
	 *           datebox, otherwise 'day, 'month', or 'year'. Default: false.
	 *     @type int $user_id ID of the user whose profile values should be
	 *           used when rendering options. Default: displayed user.
	 * }
	 */
	function bp_get_the_profile_field_options( $args = array() ) {
		global $field;

		$args = bp_parse_args( $args, array(
			'type'    => false,
			'user_id' => bp_displayed_user_id(),
		), 'get_the_profile_field_options' );

		/**
		 * In some cases, the $field global is not an instantiation of the BP_XProfile_Field class.
		 * However, we have to make sure that all data originally in $field gets merged back in, after reinstantiation.
		 */
		if ( ! method_exists( $field, 'get_children' ) ) {
			$field_obj = new BP_XProfile_Field( $field->id );

			foreach ( $field as $field_prop => $field_prop_value ) {
				if ( ! isset( $field_obj->{$field_prop} ) )
					$field_obj->{$field_prop} = $field_prop_value;
			}

			$field = $field_obj;
		}

		ob_start();
		$field->type_obj->edit_field_options_html( $args );
		$html = ob_get_contents();
		ob_end_clean();

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

		// On the registration page, values stored in POST should take
		// precedence over default visibility, so that submitted values
		// are not lost on failure
		if ( bp_is_register_page() && ! empty( $_POST['field_' . $field->id . '_visibility'] ) ) {
			$retval = esc_attr( $_POST['field_' . $field->id . '_visibility'] );
		} else {
			$retval = ! empty( $field->visibility_level ) ? $field->visibility_level : 'public';
		}

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

		// On the registration page, values stored in POST should take
		// precedence over default visibility, so that submitted values
		// are not lost on failure
		if ( bp_is_register_page() && ! empty( $_POST['field_' . $field->id . '_visibility'] ) ) {
			$level = esc_html( $_POST['field_' . $field->id . '_visibility'] );
		} else {
			$level = ! empty( $field->visibility_level ) ? $field->visibility_level : 'public';
		}

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

/** Visibility ****************************************************************/

/**
 * Echo the field visibility radio buttons
 */
function bp_profile_visibility_radio_buttons( $args = '' ) {
	echo bp_profile_get_visibility_radio_buttons( $args );
}
	/**
	 * Return the field visibility radio buttons
	 */
	function bp_profile_get_visibility_radio_buttons( $args = '' ) {

		// Parse optional arguments
		$r = bp_parse_args( $args, array(
			'field_id'     => bp_get_the_profile_field_id(),
			'before'       => '<ul class="radio">',
			'after'        => '</ul>',
			'before_radio' => '<li>',
			'after_radio'  => '</li>',
			'class'        => 'bp-xprofile-visibility'
		), 'xprofile_visibility_radio_buttons' );

		// Empty return value, filled in below if a valid field ID is found
		$retval = '';

		// Only do-the-do if there's a valid field ID
		if ( ! empty( $r['field_id'] ) ) :

			// Start the output buffer
			ob_start();

			// Output anything before
			echo $r['before']; ?>

			<?php if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>

				<?php foreach( bp_xprofile_get_visibility_levels() as $level ) : ?>

					<?php echo $r['before_radio']; ?>

					<label for="<?php echo esc_attr( 'see-field_' . $r['field_id'] . '_' . $level['id'] ); ?>">
						<input type="radio" id="<?php echo esc_attr( 'see-field_' . $r['field_id'] . '_' . $level['id'] ); ?>" name="<?php echo esc_attr( 'field_' . $r['field_id'] . '_visibility' ); ?>" value="<?php echo esc_attr( $level['id'] ); ?>" <?php checked( $level['id'], bp_get_the_profile_field_visibility_level() ); ?> />
						<span class="field-visibility-text"><?php echo esc_html( $level['label'] ); ?></span>
					</label>

					<?php echo $r['after_radio']; ?>

				<?php endforeach; ?>

			<?php endif;

			// Output anything after
			echo $r['after'];

			// Get the output buffer and empty it
			$retval = ob_get_clean();
		endif;

		return apply_filters( 'bp_profile_get_visibility_radio_buttons', $retval, $r, $args );
	}

/**
 * Output the XProfile field visibility select list for settings
 *
 * @since BuddyPress (2.0.0)
 */
function bp_profile_settings_visibility_select( $args = '' ) {
	echo bp_profile_get_settings_visibility_select( $args );
}
	/**
	 * Return the XProfile field visibility select list for settings
	 *
	 * @since BuddyPress (2.0.0)
	 */
	function bp_profile_get_settings_visibility_select( $args = '' ) {

		// Parse optional arguments
		$r = bp_parse_args( $args, array(
			'field_id' => bp_get_the_profile_field_id(),
			'before'   => '',
			'after'    => '',
			'class'    => 'bp-xprofile-visibility'
		), 'xprofile_settings_visibility_select' );

		// Empty return value, filled in below if a valid field ID is found
		$retval = '';

		// Only do-the-do if there's a valid field ID
		if ( ! empty( $r['field_id'] ) ) :

			// Start the output buffer
			ob_start();

			// Output anything before
			echo $r['before']; ?>

			<?php if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>

				<select class="<?php echo esc_attr( $r['class'] ); ?>" name="<?php echo esc_attr( 'field_' . $r['field_id'] ) ; ?>_visibility">

					<?php foreach ( bp_xprofile_get_visibility_levels() as $level ) : ?>

						<option value="<?php echo esc_attr( $level['id'] ); ?>" <?php selected( $level['id'], bp_get_the_profile_field_visibility_level() ); ?>><?php echo esc_html( $level['label'] ); ?></option>

					<?php endforeach; ?>

				</select>

			<?php else : ?>

				<span class="field-visibility-settings-notoggle" title="<?php esc_attr_e( "This field's visibility cannot be changed.", 'buddypress' ); ?>"><?php bp_the_profile_field_visibility_level_label(); ?></span>

			<?php endif;

			// Output anything after
			echo $r['after'];

			// Get the output buffer and empty it
			$retval = ob_get_clean();
		endif;

		// Output the dropdown list
		return apply_filters( 'bp_profile_settings_visibility_select', $retval, $r, $args );
	}
