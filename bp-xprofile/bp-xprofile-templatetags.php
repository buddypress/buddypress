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

	function bp_xprofile_template( $user_id, $profile_group_id ) {
		
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
		$this->field_count = count($this->group->fields);
		
		if ( !$fields = wp_cache_get( 'xprofile_fields_' . $this->group->id . '_' . $this->user_id, 'bp' ) ) {
			for ( $i = 0; $i < $this->field_count; $i++ ) {
				$field = new BP_XProfile_Field( $this->group->fields[$i]->id, $this->user_id );
				$fields[$i] = $field;
			}
			
			wp_cache_set( 'xprofile_fields_' . $this->group->id . '_' . $this->user_id, $fields, 'bp' );
		}
		
		$this->group->fields = $fields;
		
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

		if ( 0 == $this->current_group ) // loop has just started
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
		
		/* Skip the name field */
		if ( 1 == $field->id )
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

function bp_has_profile( $args = '' ) { 
	global $bp, $profile_template;
	
	$defaults = array(
		'user_id' => $bp->displayed_user->id,
		'profile_group_id' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	$profile_template = new BP_XProfile_Template( $user_id, $profile_group_id );
	
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
	
	if ( $profile_template->field_has_data && $profile_template->is_public == 1 )
		return true;
	
	return false;
}

function bp_the_profile_group_name() {
	echo bp_get_the_profile_group_name();
}
	function bp_get_the_profile_group_name() {
		global $group;
		return apply_filters( 'bp_get_the_profile_group_name', $group->name );
	}

function bp_the_profile_group_description() {
	echo bp_get_the_profile_group_description();
}
	function bp_get_the_profile_group_description() {
		global $group;
		echo apply_filters( 'bp_get_the_profile_group_description', $group->description );
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

		echo '<li' . $selected . '><a href="' . $bp->loggedin_user->domain . $bp->profile->slug . '/edit/group/' . $groups[$i]->id . '">' . $groups[$i]->name . '</a></li>';
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

function bp_edit_profile_form() {
	global $bp;

	$group_id = $bp->action_variables[1];

	if ( !is_numeric( $group_id ) )
		$group_id = 1; // 'Basic' group.
	
	xprofile_edit( $group_id, $bp->loggedin_user->domain . $bp->profile->slug . '/edit/group/' . $group_id . '/?mode=save' );
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
	

function bp_edit_profile_button() {
	global $bp;
	
	?>
	<div class="generic-button">
		<a class="edit" title="<?php _e( 'Edit Profile', 'buddypress' ) ?>" href="<?php echo $bp->loggedin_user->domain . $bp->profile->slug ?>/edit"><?php _e( 'Edit Profile', 'buddypress' ) ?></a>
	</div>
	<?php
}

?>
