<?php

Class BP_XProfile_Template {
	var $current_group = -1;
	var $group_count;
	var $groups;
	var $group;
	
	var $current_field = -1;
	var $field_count;
	var $field_has_data;
	var $field;
	var $is_public;
	
	var $in_the_loop;
	var $user_id;

	function bp_xprofile_template($user_id) {
		$this->groups = BP_XProfile_Group::get_all(true);
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
		$this->field_count = count($this->group->fields);
		
		for ( $i = 0; $i < $this->field_count; $i++ ) {
			$this->group->fields[$i] = new BP_XProfile_Field( $this->group->fields[$i]->id, $this->user_id );	
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
			do_action('loop_end');
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

		if ( $this->current_group == 0 ) // loop has just started
			do_action('loop_start');
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

		if ( count($this->group->fields) > 0 ) {
			for ( $i = 0; $i < count($this->group->fields); $i++ ) { 
				$field = $this->group->fields[$i];

				if ( $field->data->value != null ) {
					$has_data = true;
				}
			}
		}

		if($has_data)
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
		$this->is_public = $field->is_public;	
		if ( $field->data->value != '' ) {
			$this->field_has_data = true;
		}
		else {
			$this->field_has_data = false;
		}
	}
}

// Begin template tags:
function xprofile_get_profile() {
	load_template( TEMPLATEPATH . '/profile/profile-loop.php');
}

function bp_has_profile() { 
	global $bp, $profile_template;

	$profile_template = new BP_XProfile_Template($bp['current_userid']);
	
	return $profile_template->has_groups();
}

function bp_profile_groups() { 
	global $profile_template;
	return $profile_template->profile_groups();
}

function bp_the_profile_group() {
	global $profile_template;
	return $profile_template->the_profile_group();
}

function bp_group_has_fields() {
	global $profile_template;
	return $profile_template->has_fields();
}

function bp_field_css_class() {
	global $profile_template;
	
	if ( $profile_template->current_field % 2 )
		echo ' class="alt"';
}

function bp_field_has_data() {
	global $profile_template;
	return $profile_template->field_has_data;
}

function bp_field_has_public_data() {
	global $profile_template;
	
	if ( $profile_template->field_has_data && $profile_template->is_public == 1 )
		return true;
	
	return false;
}

function bp_the_profile_group_name() {
	global $group;
	echo $group->name;
}

function bp_the_profile_group_description() {
	global $group;
	echo $group->description;
}

function bp_profile_fields() {
	global $profile_template;
	return $profile_template->profile_fields();
}

function bp_the_profile_field() {
	global $profile_template;
	return $profile_template->the_profile_field();
}

function bp_the_profile_field_name() {
	global $field;
	echo stripslashes($field->name);
}

function bp_the_profile_field_value() {
	global $field;
	
	if ( is_serialized($field->data->value) ) {
		$field_value = maybe_unserialize($field->data->value);
		$field_value = implode( ', ', $field_value );
		$field->data->value = $field_value;
	}
	
	if ( $field->type == "datebox" ) {
		$field->data->value = bp_format_time( $field->data->value, true );
	}
	
	if ( BP_FRIENDS_IS_INSTALLED )
		echo stripslashes($field->data->value);
	else
		echo stripslashes($field->data->value);
}

function bp_get_field_data( $field, $user_id = null ) {
	return BP_XProfile_ProfileData::get_value_byfieldname( $field, $user_id );
}

function bp_profile_group_tabs() {
	global $bp, $group_name;
	
	$groups = BP_XProfile_Group::get_all();
	
	if ( $group_name == '' )
		$group_name = bp_profile_group_name(false);
	
	for ( $i = 0; $i < count($groups); $i++ ) {
		if ( $group_name == $groups[$i]->name ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}

		echo '<li' . $selected . '><a href="' . $bp['loggedin_domain'] . $bp['profile']['slug'] . '/edit/group/' . $groups[$i]->id . '">' . $groups[$i]->name . '</a></li>';
	}
}

function bp_profile_group_name( $echo = true ) {
	global $bp;
	
	$group_id = $bp['action_variables'][1];
	
	if ( !is_numeric( $group_id ) )
		$group_id = 1;
	
	$group = new BP_XProfile_Group($group_id);
	
	if ( $echo ) {
		echo $group->name;
	} else {
		return $group->name;
	}
}

function bp_edit_profile_form() {
	global $bp;

	$group_id = $bp['action_variables'][1];

	if ( !is_numeric( $group_id ) )
		$group_id = 1; // 'Basic' group.
	
	xprofile_edit( $group_id, $bp['loggedin_domain'] . $bp['profile']['slug'] . '/edit/group/' . $group_id . '/?mode=save' );
}

function bp_avatar_upload_form() {
	global $bp;
	 
	bp_core_avatar_admin( null, $bp['loggedin_domain'] . $bp['profile']['slug'] . '/change-avatar/', $bp['loggedin_domain'] . $bp['profile']['slug'] . '/delete-avatar/' );
}

function bp_profile_last_updated() {
	global $bp;
	
	$last_updated = get_usermeta( $bp['current_userid'], 'profile_last_updated' );

	if ( !$last_updated ) {
		_e('Profile not recently updated', 'buddypress') . '.';
	} else {
		echo __('Profile updated ', 'buddypress') . bp_core_time_since( strtotime( $last_updated ) ) . __(' ago', 'buddypress'); 
	}
}

function bp_profile_wire_can_post() {
	global $bp;
	
	if ( bp_is_home() )
		return true;
	
	if ( function_exists('friends_install') ) {
		if ( friends_check_friendship( $bp['loggedin_userid'], $bp['current_userid'] ) )
			return true;
		else
			return false;
	} 
	
	return true;
}

?>
