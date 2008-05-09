<?php

/**************************************************************************
 xprofile_admin()
 
 Handles all actions for the admin area for creating, editing and deleting
 profile groups and fields.
 **************************************************************************/

function xprofile_admin( $message = '', $type = 'error' ) {
	global $image_base;
	
	$groups = BP_XProfile_Group::get_all();
	
	if ( isset($_GET['mode']) && isset($_GET['group_id']) && $_GET['mode'] == "add_field" ) {
		xprofile_admin_manage_field($_GET['group_id']); die;
	} else if ( isset($_GET['mode']) && isset($_GET['group_id']) && isset($_GET['field_id']) && $_GET['mode'] == "edit_field" ) {
		xprofile_admin_manage_field($_GET['group_id'], $_GET['field_id']); die;
	} else if ( isset($_GET['mode']) && isset($_GET['field_id']) && $_GET['mode'] == "delete_field" ) {
		xprofile_admin_delete_field($_GET['field_id']); die;
	} else if ( isset($_GET['mode']) && $_GET['mode'] == "add_group" ) {
		xprofile_admin_manage_group(); die;
	} else if ( isset($_GET['mode']) && isset($_GET['group_id']) && $_GET['mode'] == "delete_group" ) {
		xprofile_admin_delete_group($_GET['group_id']); die;
	} else if ( isset($_GET['mode']) && isset($_GET['group_id']) && $_GET['mode'] == "edit_group" ) {
		xprofile_admin_manage_group($_GET['group_id']); die;
	}
	
?>	
	<div class="wrap">
		
		<h2><?php _e("Profile Settings") ?></h2>
		<br />
		<p><?php _e('Your users will distinguish themselves through their profile page. 
		   You must give them profile fields that allow them to describe themselves 
			in a way that is relevant to the theme of your social network.') ?></p>
			
		<p><?php _e('NOTE: Fields in the \'Basic\' group appear on the signup page.'); ?></p>
		
		<?php
			if ( $message != '' ) {
				$type = ( $type == 'error' ) ? 'error' : 'updated';
		?>
			<div id="message" class="<?php echo $type; ?> fade">
				<p><?php echo $message; ?></p>
			</div>
		<?php }
		
		if ( $groups ) {
			for ( $i = 0; $i < count($groups); $i++ ) {
			?>
				<script type="text/javascript" charset="utf-8">
					jQuery(document).ready(function(){
						jQuery('#<?php echo $groups[$i]->name;?>').tableDnD({
							onDrop: function(table, row) {
				      	var order = jQuery.tableDnD.serialize();
					    }
				    });
					});					
				</script>
			 	<p>
				<table id="<?php echo $groups[$i]->name; ?>" class="widefat">
					<thead>
					    <tr class="nodrag">
					    	<th scope="col" colspan="<?php if ( $groups[$i]->can_delete ) { ?>3<?php } else { ?>5<?php } ?>"><?php echo $groups[$i]->name; ?></th>
							<?php if ( $groups[$i]->can_delete ) { ?>    	
								<th scope="col"><a class="edit" href="admin.php?page=xprofile_settings&amp;mode=edit_group&amp;group_id=<?php echo $groups[$i]->id; ?>">Edit</a></th>
					    		<th scope="col"><a class="delete" href="admin.php?page=xprofile_settings&amp;mode=delete_group&amp;group_id=<?php echo $groups[$i]->id; ?>">Delete</a></th>
							<?php } ?>
						</tr>
					</thead>
					<tbody id="the-list">
					   <tr class="header nodrag">
					    	<td>Field Name</td>
					    	<td width="10%">Field Type</td>
					    	<td width="6%">Required?</td>
					    	<td colspan="2" width="15%" style="text-align:center;">Action</td>
					    </tr>

						  <?php if ( $groups[$i]->fields ) { ?>
					    	<?php for ( $j = 0; $j < count($groups[$i]->fields); $j++ ) { ?>
									<?php if ( $j % 2 == 0 ) { $class = ""; } else { $class = "alternate"; } ?>
							    <?php $field = new BP_XProfile_Field($groups[$i]->fields[$j]->id); ?>
							    <?php if ( !$field->can_delete ) { $class .= ' core'; } ?>
							
									<tr<?php echo ' class="' . $class . '"'; ?>>
							    	<td><span title="<?php echo $field->desc; ?>"><?php echo $field->name; ?> <?php if(!$field->can_delete) { ?>(Core)<?php } ?></span></td>
							    	<td><?php echo $field->type; ?></td>
							    	<td style="text-align:center;"><?php if ( $field->is_required ) { echo '<img src="' . $image_base . '/tick.gif" alt="Yes" />'; } else { ?>--<?php } ?></td>
							    	<td style="text-align:center;"><?php if ( !$field->can_delete ) { ?><strike>Edit</strike><?php } else { ?><a class="edit" href="admin.php?page=xprofile_settings&amp;group_id=<?php echo $groups[$i]->id; ?>&amp;field_id=<?php echo $field->id; ?>&amp;mode=edit_field">Edit</a><?php } ?></td>
							    	<td style="text-align:center;"><?php if ( !$field->can_delete ) { ?><strike>Delete</strike><?php } else { ?><a class="delete" href="admin.php?page=xprofile_settings&amp;field_id=<?php echo $field->id; ?>&amp;mode=delete_field">Delete</a><?php } ?></td>
							    </tr>
							
							<?php } ?>
						<?php } else { ?>
							<tr>
								<td colspan="6">There are no fields in this group.</td>
							</tr>
						<?php } ?>
							<tr>
								<td colspan="6"><a href="admin.php?page=xprofile_settings&amp;group_id=<?php echo $groups[$i]->id; ?>&amp;mode=add_field">Add New Field</a></td>
							</tr>
					</tbody>
				</table>
				</p>
				
			<?php } /* End For */ ?>
			
				<p>
				<table class="widefat">
					<thead>
					    <tr>
					    	<th scope="col" colspan="6"><a href="admin.php?page=xprofile_settings&amp;mode=add_group">Add New Group</th>
					    </tr>
					</thead>
				</table>
				</p>
				
		<?php } else { ?>
			<div id="message" class="error"><p>You have no groups.</p></div>
			<p><a href="admin.php?page=xprofile_settings&amp;mode=add_group">Add a Group</a></p>
		<?php } ?>
	</div>
<?php
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
			$group->name = $_POST['group_name'];
			$group->description = $_POST['group_desc'];
			
			if ( !$group->save() ) {
				$message = __('There was an error saving the group. Please try again');
				$type = 'error';
			} else {
				$message = __('The group was saved successfully.');
				$type = 'success';
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
		$message = __('There was an error deleting the group. Please try again');
		$type = 'error';
	} else {
		$message = __('The group was deleted successfully.');
		$type = 'success';
	}
	
	unset($_GET['mode']);
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
			$field->name = $_POST['title'];
			$field->desc = $_POST['description'];
			$field->is_required = $_POST['required'];
			$field->type = $_POST['fieldtype'];
			
			if ( !$field->save() ) {
				$message = __('There was an error saving the field. Please try again');
				$type = 'error';
				
				unset($_GET['mode']);
				xprofile_admin($message, $type);
			} else {
				$message = __('The field was saved successfully.');
				$type = 'success';
				
				unset($_GET['mode']);
				
				$groups = $groups = BP_XProfile_Group::get_all();
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
 
 Handles the deletion of profile field data for a user.
**************************************************************************/

function xprofile_admin_delete_field( $field_id ) {
	global $message, $type;
	
	$field = new BP_XProfile_Field($field_id);
	
	if ( !$field->delete() ) {
		$message = __('There was an error deleting the field. Please try again');
		$type = 'error';
	} else {
		$message = __('The field was deleted successfully.');
		$type = 'success';
	}
	
	unset($_GET['mode']);
	xprofile_admin($message, $type);
}



?>