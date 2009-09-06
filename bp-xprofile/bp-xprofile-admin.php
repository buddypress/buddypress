<?php


/**************************************************************************
 xprofile_admin()
 
 Handles all actions for the admin area for creating, editing and deleting
 profile groups and fields.
 **************************************************************************/

function xprofile_admin( $message = '', $type = 'error' ) {
	global $bp;

	$type = preg_replace( '|[^a-z]|i', '', $type );

	$groups = BP_XProfile_Group::get_all();

	if ( isset($_GET['mode']) && isset($_GET['group_id']) && 'add_field' == $_GET['mode'] ) {
		xprofile_admin_manage_field($_GET['group_id']);
	} else if ( isset($_GET['mode']) && isset($_GET['group_id']) && isset($_GET['field_id']) && 'edit_field' == $_GET['mode'] ) {
		xprofile_admin_manage_field($_GET['group_id'], $_GET['field_id']);
	} else if ( isset($_GET['mode']) && isset($_GET['field_id']) && 'delete_field' == $_GET['mode'] ) {
		xprofile_admin_delete_field($_GET['field_id'], 'field');
	} else if ( isset($_GET['mode']) && isset($_GET['option_id']) && 'delete_option' == $_GET['mode'] ) {
		xprofile_admin_delete_field($_GET['option_id'], 'option');
	} else if ( isset($_GET['mode']) && 'add_group' == $_GET['mode'] ) {
		xprofile_admin_manage_group();
	} else if ( isset($_GET['mode']) && isset($_GET['group_id']) && 'delete_group' == $_GET['mode'] ) {
		xprofile_admin_delete_group($_GET['group_id']);
	} else if ( isset($_GET['mode']) && isset($_GET['group_id']) && 'edit_group' == $_GET['mode'] ) {
		xprofile_admin_manage_group($_GET['group_id']);
	} else {
?>	
	<div class="wrap">
		
		<h2><?php _e( 'Profile Field Setup', 'buddypress') ?></h2>
		<br />
		<p><?php _e( 'Your users will distinguish themselves through their profile page. 
		   You must give them profile fields that allow them to describe themselves 
			in a way that is relevant to the theme of your social network.', 'buddypress') ?></p>
			
		<p><?php _e('NOTE: Any fields in the first group will appear on the signup page.', 'buddypress'); ?></p>
		
		<form action="" id="profile-field-form" method="post">
			
			<?php wp_nonce_field( 'bp_reorder_fields', '_wpnonce_reorder_fields' ); ?>
					
			<?php
				if ( $message != '' ) {
					$type = ( $type == 'error' ) ? 'error' : 'updated';
			?>
				<div id="message" class="<?php echo $type; ?> fade">
					<p><?php echo wp_specialchars( attribute_escape( $message ) ); ?></p>
				</div>
			<?php }
		
			if ( $groups ) { ?>
				<?php 
				for ( $i = 0; $i < count($groups); $i++ ) { // TODO: foreach
				?>
					<p>
					<table id="group_<?php echo $groups[$i]->id;?>" class="widefat field-group">
						<thead>
						    <tr>
								<th scope="col">&nbsp;</th>
						    	<th scope="col" colspan="<?php if ( $groups[$i]->can_delete ) { ?>3<?php } else { ?>5<?php } ?>"><?php echo attribute_escape( $groups[$i]->name ); ?></th>
								<?php if ( $groups[$i]->can_delete ) { ?>    	
									<th scope="col"><a class="edit" href="admin.php?page=<?php echo BP_PLUGIN_DIR ?>/bp-xprofile.php&amp;mode=edit_group&amp;group_id=<?php echo attribute_escape( $groups[$i]->id ); ?>"><?php _e( 'Edit', 'buddypress' ) ?></a></th>
						    		<th scope="col"><a class="delete" href="admin.php?page=<?php echo BP_PLUGIN_DIR ?>/bp-xprofile.php&amp;mode=delete_group&amp;group_id=<?php echo attribute_escape( $groups[$i]->id ); ?>"><?php _e( 'Delete', 'buddypress' ) ?></a></th>
								<?php } ?>
							</tr>
							<tr class="header">
								<td>&nbsp;</td>
						    	<td><?php _e( 'Field Name', 'buddypress' ) ?></td>
						    	<td width="14%"><?php _e( 'Field Type', 'buddypress' ) ?></td>
						    	<td width="6%"><?php _e( 'Required?', 'buddypress' ) ?></td>
						    	<td colspan="2" width="10%" style="text-align:center;"><?php _e( 'Action', 'buddypress' ) ?></td>
						    </tr>
						</thead>
						<tbody id="the-list">
						
						  <?php if ( $groups[$i]->fields ) { ?>
							
						    	<?php for ( $j = 0; $j < count($groups[$i]->fields); $j++ ) { ?>
						
									<?php if ( 0 == $j % 2 ) { $class = ""; } else { $class = "alternate"; } ?>	    
									<?php $field = new BP_XProfile_Field($groups[$i]->fields[$j]->id); ?>
									<?php if ( !$field->can_delete ) { $class .= ' core'; } ?>
							
									<tr id="field_<?php echo attribute_escape( $field->id ); ?>" class="sortable<?php if ( $class ) { echo ' ' . $class; } ?>">
								    	<td width="10"><img src="<?php echo BP_PLUGIN_URL ?>/bp-xprofile/admin/images/move.gif" alt="<?php _e( 'Drag', 'buddypress' ) ?>" /></td>
										<td><span title="<?php echo $field->desc; ?>"><?php echo attribute_escape( $field->name ); ?> <?php if(!$field->can_delete) { ?>(Core)<?php } ?></span></td>
								    	<td><?php echo attribute_escape( $field->type ); ?></td>
								    	<td style="text-align:center;"><?php if ( $field->is_required ) { echo '<img src="' . BP_PLUGIN_URL . '/bp-xprofile/admin/images/tick.gif" alt="' . __( 'Yes', 'buddypress' ) . '" />'; } else { ?>--<?php } ?></td>
								    	<td style="text-align:center;"><?php if ( !$field->can_delete ) { ?><strike><?php _e( 'Edit', 'buddypress' ) ?></strike><?php } else { ?><a class="edit" href="admin.php?page=<?php echo BP_PLUGIN_DIR ?>/bp-xprofile.php&amp;group_id=<?php echo attribute_escape( $groups[$i]->id ); ?>&amp;field_id=<?php echo attribute_escape( $field->id ); ?>&amp;mode=edit_field"><?php _e( 'Edit', 'buddypress' ) ?></a><?php } ?></td>
								    	<td style="text-align:center;"><?php if ( !$field->can_delete ) { ?><strike><?php _e( 'Delete', 'buddypress' ) ?></strike><?php } else { ?><a class="delete" href="admin.php?page=<?php echo BP_PLUGIN_DIR ?>/bp-xprofile.php&amp;field_id=<?php echo attribute_escape( $field->id ); ?>&amp;mode=delete_field"><?php _e( 'Delete', 'buddypress' ) ?></a><?php } ?></td>
								    </tr>
							
								<?php } ?>
							
							<?php } else { ?>
							
								<tr class="nodrag">
									<td colspan="6"><?php _e( 'There are no fields in this group.', 'buddypress' ) ?></td>
								</tr>
							
							<?php } ?>
					
						</tbody>
					
						<tfoot>
						
								<tr class="nodrag">
									<td colspan="6"><a href="admin.php?page=<?php echo BP_PLUGIN_DIR ?>/bp-xprofile.php&amp;group_id=<?php echo attribute_escape( $groups[$i]->id ); ?>&amp;mode=add_field"><?php _e( 'Add New Field', 'buddypress' ) ?></a></td>
								</tr>
						
						</tfoot>
					
					</table>
					</p>
				
				<?php } /* End For */ ?>
			
					<p>
						<a class="button" href="admin.php?page=<?php echo BP_PLUGIN_DIR ?>/bp-xprofile.php&amp;mode=add_group"><?php _e( 'Add New Field Group', 'buddypress' ) ?></a>
					</p>
				
			<?php } else { ?>
				<div id="message" class="error"><p><?php _e('You have no groups.', 'buddypress' ); ?></p></div>
				<p><a href="admin.php?page=<?php echo BP_PLUGIN_DIR ?>/bp-xprofile.php&amp;mode=add_group"><?php _e( 'Add New Group', 'buddypress' ) ?></a></p>
			<?php } ?>
		
		</form>
		
	</div>
<?php
	}
}


/**************************************************************************
 xprofile_admin_manage_group()
 
 Handles the adding or editing of groups.
 **************************************************************************/

function xprofile_admin_manage_group( $group_id = null ) {
	global $message, $type;

	$group = new BP_XProfile_Group($group_id);

	if ( isset($_POST['saveGroup']) ) {
		if ( BP_XProfile_Group::admin_validate($_POST) ) {
			$group->name = wp_filter_kses( $_POST['group_name'] );
			$group->description = wp_filter_kses( $_POST['group_desc'] );
			
			if ( !$group->save() ) {
				$message = __('There was an error saving the group. Please try again', 'buddypress');
				$type = 'error';
			} else {
				$message = __('The group was saved successfully.', 'buddypress');
				$type = 'success';
				
				do_action( 'xprofile_groups_saved_group', $group );
			}
			
			unset($_GET['mode']);
			xprofile_admin( $message, $type );

		} else {
			$group->render_admin_form($message);
		}
	} else {
		$group->render_admin_form();				
	}
}

/**************************************************************************
 xprofile_admin_delete_group()
 
 Handles the deletion of profile data groups.
 **************************************************************************/

function xprofile_admin_delete_group( $group_id ) {
	global $message, $type;
	
	$group = new BP_XProfile_Group($group_id);
	
	if ( !$group->delete() ) {
		$message = __('There was an error deleting the group. Please try again', 'buddypress');
		$type = 'error';
	} else {
		$message = __('The group was deleted successfully.', 'buddypress');
		$type = 'success';
		
		do_action( 'xprofile_groups_deleted_group', $group );
	}
	
	unset($_GET['mode']); // TODO: wtf?
	xprofile_admin( $message, $type );
}


/**************************************************************************
 xprofile_admin_manage_field()
 
 Handles the adding or editing of profile field data for a user.
 **************************************************************************/

function xprofile_admin_manage_field( $group_id, $field_id = null ) {
	global $message, $groups;
	
	$field = new BP_XProfile_Field($field_id);
	$field->group_id = $group_id;

	if ( isset($_POST['saveField']) ) {
		if ( BP_XProfile_Field::admin_validate($_POST) ) {
			$field->name = wp_filter_kses( $_POST['title'] );
			$field->desc = wp_filter_kses( $_POST['description'] );
			$field->is_required = wp_filter_kses( $_POST['required'] );
			$field->type = wp_filter_kses( $_POST['fieldtype'] );
			$field->order_by = wp_filter_kses( $_POST["sort_order_$field->type"] );
			
			if ( !$field->save() ) {
				$message = __('There was an error saving the field. Please try again', 'buddypress');
				$type = 'error';
				
				unset($_GET['mode']);
				xprofile_admin($message, $type);
			} else {
				$message = __('The field was saved successfully.', 'buddypress');
				$type = 'success';
				
				unset($_GET['mode']);
				
				do_action( 'xprofile_fields_saved_field', $field );
				
				$groups = BP_XProfile_Group::get_all();
				xprofile_admin( $message, $type );
			}
		} else {
			$field->render_admin_form($message);
		}
	} else {
		$field->render_admin_form();				
	}
}

/**************************************************************************
 xprofile_admin_delete_field()
 
 Handles the deletion of a profile field [or option].
**************************************************************************/

function xprofile_admin_delete_field( $field_id, $type = 'field' ) {
	global $message, $type;
	
	if ( 'field' == $type ) {
		$type = __('field', 'buddypress');
	} else {
		$type = __('option', 'buddypress');
	}
	
	$field = new BP_XProfile_Field($field_id);

	if ( !$field->delete() ) {
		$message = sprintf( __('There was an error deleting the %s. Please try again', 'buddypress'), $type);
		$type = 'error';
	} else {
		$message = sprintf( __('The %s was deleted successfully!', 'buddypress'), $type);
		$type = 'success';
		
		do_action( 'xprofile_fields_deleted_field', $field );
	}
	
	unset($_GET['mode']);
	xprofile_admin($message, $type);
}

function xprofile_ajax_reorder_fields() {
	global $bp;
	
	/* Check the nonce */
	check_admin_referer( 'bp_reorder_fields', '_wpnonce_reorder_fields' );
	
	if ( empty( $_POST['field_order'] ) )
		return false;
	
	parse_str($_POST['field_order'], $order );

	foreach ( (array) $order['field'] as $position => $field_id ) {
		xprofile_update_field_position( (int) $field_id, (int) $position );
	}
}
add_action( 'wp_ajax_xprofile_reorder_fields', 'xprofile_ajax_reorder_fields' );
