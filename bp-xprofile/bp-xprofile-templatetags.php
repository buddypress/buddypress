<?php

/***************************************************************************
 * XProfile Data Display Template Tags
 **/

Class BP_XProfile_Data_Template {
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

	function bp_xprofile_data_template( $user_id, $profile_group_id ) {
		
		if ( !$profile_group_id ) {
			if ( !$this->groups = wp_cache_get( 'xprofile_groups', 'bp' ) ) {
				$this->groups = BP_XProfile_Group::get_all(true);
				wp_cache_set( 'xprofile_groups', $this->groups, 'bp' );
			}
		} else {
			if ( !$this->groups = wp_cache_get( 'xprofile_group_' . $profile_group_id, 'bp' ) ) {
				$this->groups = new BP_XProfile_Group( $profile_group_id );
				wp_cache_set( 'xprofile_group_' . $profile_group_id, 'bp' );
			}
			
			/* We need to put this single group into the same format as multiple group (an array) */
			$this->groups = array( $this->groups );
		}
		
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

		$this->group = $this->groups[$this->current_group];
		
		if ( !$fields = wp_cache_get( 'xprofile_fields_' . $this->group->id . '_' . $this->user_id, 'bp' ) ) {
			for ( $i = 0; $i < count($this->group->fields); $i++ ) {
				$field = new BP_XProfile_Field( $this->group->fields[$i]->id, $this->user_id );
				$fields[$i] = $field;
			}
			
			wp_cache_set( 'xprofile_fields_' . $this->group->id . '_' . $this->user_id, $fields, 'bp' );
		}
		
		$this->group->fields = apply_filters( 'xprofile_group_fields', $fields, $this->group->id );
		$this->field_count = count( $this->group->fields );
		
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
		$just_name = true;

		for ( $i = 0; $i < count( $this->group->fields ); $i++ ) { 
			$field = &$this->group->fields[$i];

			if ( $field->data->value != null ) {
				$has_data = true;
				
				if ( 1 != $field->id )
					$just_name = false;
			}
		}
		
		if ( 1 == $this->group->id && $just_name )
			return false;

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

		if ( $field->data->value != '' ) {
			$this->field_has_data = true;
		}
		else {
			$this->field_has_data = false;
		}
	}
}

function xprofile_get_profile() {
	load_template( TEMPLATEPATH . '/profile/profile-loop.php');
}

function bp_has_profile( $args = '' ) { 
	global $bp, $profile_template;
	
	$defaults = array(
		'user_id' => $bp->displayed_user->id,
		'profile_group_id' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	$profile_template = new BP_XProfile_Data_Template( $user_id, $profile_group_id );
	return apply_filters( 'bp_has_profile', $profile_template->has_groups(), &$profile_template );
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
	/* Deprecated: Don't use this as it it too easily confused with site groups */
	function bp_group_has_fields() {
		return bp_profile_group_has_fields();
	}



function bp_field_css_class() {
	global $profile_template;
	
	if ( $profile_template->current_field % 2 )
		echo apply_filters( 'bp_field_css_class', ' class="alt"' );
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
		
		return apply_filters( 'bp_get_the_profile_group_edit_form_action', $bp->displayed_user->domain . BP_XPROFILE_SLUG . '/edit/group/' . $group->id . '/' );
	}	

function bp_the_profile_group_field_ids() {
	echo bp_get_the_profile_group_field_ids();
}
	function bp_get_the_profile_group_field_ids() {
		global $group;

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
	echo bp_get_the_profile_field_name();
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
		if ( isset( $_POST['field_' . $field->id] ) && ( $field->data->value != $_POST['field_' . $field->id] ) ) {
			if ( !empty( $_POST['field_' . $field->id] ) )
				$field->data->value = $_POST['field_' . $field->id];
		}

		$field->data->value = bp_unserialize_profile_field( $field->data->value );

		return apply_filters( 'bp_get_the_profile_field_edit_value', $field->data->value );
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
		
		return apply_filters( 'bp_get_the_profile_field_description', $field->desc );
	}
	
function bp_the_profile_field_input_name() {
	echo bp_get_the_profile_field_input_name();
}
	function bp_get_the_profile_field_input_name() {
		global $field;
	
		return apply_filters( 'bp_get_the_profile_field_input_name', 'field_' . $field->id );
	}

function bp_the_profile_field_options( $args = '' ) {
	echo bp_get_the_profile_field_options( $args );
}
	function bp_get_the_profile_field_options( $args = '' ) {
		global $field;
		
		$defaults = array(
			'type' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
		
		$options = $field->get_children();

		switch ( $field->type ) {
			
			case 'selectbox': case 'multiselectbox':
				if ( 'multiselectbox' != $field->type )
					$html .= '<option value="">--------</option>';
						
				for ( $k = 0; $k < count($options); $k++ ) {
					$option_value = BP_XProfile_ProfileData::get_value_byid($options[$k]->parent_id);
					
					/* Check for updated posted values, but errors preventing them from being saved first time */
					if ( isset( $_POST['field_' . $field->id] ) && $option_value != $_POST['field_' . $field->id] ) {
						if ( !empty( $_POST['field_' . $field->id] ) )
							$option_value = $_POST['field_' . $field->id];
					}
										
					if ( $option_value == $options[$k]->name || $options[$k]->is_default_option ) {
						$selected = ' selected="selected"';
					} else {
						$selected = '';
					}
					
					 
					
					$html .= apply_filters( 'bp_get_the_profile_field_options_select', '<option' . $selected . ' value="' . attribute_escape( $options[$k]->name ) . '">' . attribute_escape( $options[$k]->name ) . '</option>', $options[$k] );
				}
				break;
				
			case 'radio':
				for ( $k = 0; $k < count($options); $k++ ) {				
					$option_value = BP_XProfile_ProfileData::get_value_byid($options[$k]->parent_id);

					/* Check for updated posted values, but errors preventing them from being saved first time */
					if ( isset( $_POST['field_' . $field->id] ) && $option_value != $_POST['field_' . $field->id] ) {
						if ( !empty( $_POST['field_' . $field->id] ) )
							$option_value = $_POST['field_' . $field->id];
					}
								
					if ( $option_value == $options[$k]->name || $value == $options[$k]->name || $options[$k]->is_default_option ) {
						$selected = ' checked="checked"';
					} else {
						$selected = '';
					}
					
					$html .= apply_filters( 'bp_get_the_profile_field_options_radio', '<label><input' . $selected . ' type="radio" name="field_' . $field->id . '" id="option_' . $options[$k]->id . '" value="' . attribute_escape( $options[$k]->name ) . '"> ' . attribute_escape( $options[$k]->name ) . '</label>', $options[$k] );
				}
				break;
				
			case 'checkbox':
				$option_values = BP_XProfile_ProfileData::get_value_byid($options[0]->parent_id);
				
				/* Check for updated posted values, but errors preventing them from being saved first time */
				if ( isset( $_POST['field_' . $field->id] ) && $option_values != maybe_serialize( $_POST['field_' . $field->id] ) ) {
					if ( !empty( $_POST['field_' . $field->id] ) )
						$option_values = $_POST['field_' . $field->id];
				}
		
				$option_values = maybe_unserialize($option_values);

				for ( $k = 0; $k < count($options); $k++ ) {				
					for ( $j = 0; $j < count($option_values); $j++ ) {
						if ( $option_values[$j] == $options[$k]->name || @in_array( $options[$k]->name, $value ) || $options[$k]->is_default_option ) {
							$selected = ' checked="checked"';
							break;
						}
					}
					
					$html .= apply_filters( 'bp_get_the_profile_field_options_checkbox', '<label><input' . $selected . ' type="checkbox" name="field_' . $field->id . '[]" id="field_' . $options[$k]->id . '_' . $k . '" value="' . attribute_escape( $options[$k]->name ) . '"> ' . attribute_escape( $options[$k]->name ) . '</label>', $options[$k] );
					$selected = '';
				}
				break;
			
			case 'datebox':
				
				if ( $field->data->value != '' ) {
					$day = date("j", $field->data->value);
					$month = date("F", $field->data->value);
					$year = date("Y", $field->data->value);
					$default_select = ' selected="selected"';
					
					/* Check for updated posted values, but errors preventing them from being saved first time */
					if ( isset( $_POST['field_' . $field->id . '_day'] ) && $day != $_POST['field_' . $field->id . '_day'] ) {
						if ( !empty( $_POST['field_' . $field->id . '_day'] ) )
							$day = $_POST['field_' . $field->id . '_day'];
					}
					
					if ( isset( $_POST['field_' . $field->id . '_month'] ) && $month != $_POST['field_' . $field->id . '_month'] ) {
						if ( !empty( $_POST['field_' . $field->id . '_month'] ) )
							$month = $_POST['field_' . $field->id . '_month'];
					}
					
					if ( isset( $_POST['field_' . $field->id . '_year'] ) && $year != date("j", $_POST['field_' . $field->id . '_year'] ) ) {
						if ( !empty( $_POST['field_' . $field->id . '_year'] ) )
							$year = $_POST['field_' . $field->id . '_year'];
					}
				}
				
				switch ( $type ) {
					case 'day':
						$html .= '<option value=""' . attribute_escape( $default_select ) . '>--</option>';
				
						for ( $i = 1; $i < 32; $i++ ) {
							if ( $day == $i ) { 
								$selected = ' selected = "selected"'; 
							} else {
								$selected = '';
							}
							$html .= '<option value="' . $i .'"' . $selected . '>' . $i . '</option>';
						}
						break;
					
					case 'month':
						$eng_months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
						
						$months = array( __( 'January', 'buddypress' ), __( 'February', 'buddypress' ), __( 'March', 'buddypress' ), 
								 __( 'April', 'buddypress' ), __( 'May', 'buddypress' ), __( 'June', 'buddypress' ),
								 __( 'July', 'buddypress' ), __( 'August', 'buddypress' ), __( 'September', 'buddypress' ),
								 __( 'October', 'buddypress' ), __( 'November', 'buddypress' ), __( 'December', 'buddypress' )
								);

						$html .= '<option value=""' . attribute_escape( $default_select ) . '>------</option>';
				
						for ( $i = 0; $i < 12; $i++ ) {
							if ( $month == $eng_months[$i] ) {
								$selected = ' selected = "selected"';
							} else {
								$selected = '';
							}
					
							$html .= '<option value="' . $eng_months[$i] . '"' . $selected . '>' . $months[$i] . '</option>';
						}
						break;
						
					case 'year':
						$html .= '<option value=""' . attribute_escape( $default_select ) . '>----</option>';
								
						for ( $i = date( 'Y', time() ); $i > 1899; $i-- ) {
							if ( $year == $i ) {
								$selected = ' selected = "selected"'; 
							} else {
								$selected = '';
							}
				
							$html .= '<option value="' . $i .'"' . $selected . '>' . $i . '</option>';
						}
						break;
				}
				
				apply_filters( 'bp_get_the_profile_field_datebox', $html, $day, $month, $year, $default_select ); 

				break;
		}
		
		return $html;
	}

function bp_the_profile_field_is_required() {
	echo bp_get_the_profile_field_is_required();
}
	function bp_get_the_profile_field_is_required() {
		global $field;
		
		return apply_filters( 'bp_get_the_profile_field_is_required', (int)$field->is_required );
	}
	
function bp_unserialize_profile_field( $value ) {
	if ( is_serialized($value) ) {
		$field_value = maybe_unserialize($value);
		$field_value = implode( ', ', $field_value );
		return $field_value;
	}
	
	return $value;
}

function bp_profile_group_tabs() {
	global $bp, $group_name;
	
	if ( !$groups = wp_cache_get( 'xprofile_groups_inc_empty', 'bp' ) ) {
		$groups = BP_XProfile_Group::get_all();
		wp_cache_set( 'xprofile_groups_inc_empty', $groups, 'bp' );
	}

	if ( empty( $group_name ) )
		$group_name = bp_profile_group_name(false);
	
	for ( $i = 0; $i < count($groups); $i++ ) {
		if ( $group_name == $groups[$i]->name ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}
		
		if ( $groups[$i]->fields )
			echo '<li' . $selected . '><a href="' . $bp->displayed_user->domain . $bp->profile->slug . '/edit/group/' . $groups[$i]->id . '">' . attribute_escape( $groups[$i]->name ) . '</a></li>';
	}
	
	do_action( 'xprofile_profile_group_tabs' );
}

function bp_profile_group_name( $deprecated = true ) {
	global $bp;
	
	$group_id = $bp->action_variables[1];
	
	if ( !is_numeric( $group_id ) )
		$group_id = 1;
	
	if ( !$group = wp_cache_get( 'xprofile_group_' . $group_id, 'bp' ) ) {
		$group = new BP_XProfile_Group($group_id);
		wp_cache_set( 'xprofile_group_' . $group_id, $group, 'bp' );
	}
	
	if ( !$deprecated ) {
		return bp_get_profile_group_name();
	} else {
		echo bp_get_profile_group_name();
	}
}
	function bp_get_profile_group_name() {
		global $bp;

		$group_id = $bp->action_variables[1];

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
	
	if ( !(int)get_site_option( 'bp-disable-avatar-uploads' ) ) 
		bp_core_avatar_admin( null, $bp->loggedin_user->domain . $bp->profile->slug . '/change-avatar/', $bp->loggedin_user->domain . $bp->profile->slug . '/delete-avatar/' );
	else
		_e( 'Avatar uploads are currently disabled. Why not use a <a href="http://gravatar.com" target="_blank">gravatar</a> instead?', 'buddypress' );
}

function bp_profile_last_updated() {
	global $bp;
	
	$last_updated = bp_get_profile_last_updated();

	if ( !$last_updated ) {
		_e( 'Profile not recently updated', 'buddypress' ) . '.';
	} else {
		echo $last_updated;
	}
}
	function bp_get_profile_last_updated() {
		global $bp;

		$last_updated = get_usermeta( $bp->displayed_user->id, 'profile_last_updated' );

		if ( $last_updated )
			return apply_filters( 'bp_get_profile_last_updated', sprintf( __('Profile updated %s ago', 'buddypress'), bp_core_time_since( strtotime( $last_updated ) ) ) ); 
		
		return false;
	}

function bp_current_profile_group_id() {
	echo bp_get_current_profile_group_id();
}
	function bp_get_current_profile_group_id() {
		global $bp;
		
		if ( !$profile_group_id = $bp->action_variables[1] )
			$profile_group_id = 1;
	
		return apply_filters( 'bp_get_current_profile_group_id', $profile_group_id ); // admin/profile/edit/[group-id]
	}

function bp_avatar_delete_link() {
	echo bp_get_avatar_delete_link();
}
	function bp_get_avatar_delete_link() {
		global $bp;
		
		return apply_filters( 'bp_get_avatar_delete_link', wp_nonce_url( $bp->displayed_user->domain . $bp->profile->slug . '/change-avatar/delete-avatar/', 'bp_delete_avatar_link' ) );
	}

function bp_get_user_has_avatar() {
	if ( !bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'no_grav' => true ) ) )
		return false;
	
	return true;
}

function bp_edit_profile_button() {
	global $bp;
	
	?>
	<div class="generic-button">
		<a class="edit" title="<?php _e( 'Edit Profile', 'buddypress' ) ?>" href="<?php echo $bp->displayed_user->domain . $bp->profile->slug ?>/edit"><?php _e( 'Edit Profile', 'buddypress' ) ?></a>
	</div>
	<?php
}


?>
