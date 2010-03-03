<?php

/**************************************************************************
 xprofile_admin()

 Handles all actions for the admin area for creating, editing and deleting
 profile groups and fields.
 **************************************************************************/

function xprofile_admin( $message = '', $type = 'error' ) {
	global $bp;

	$type = preg_replace( '|[^a-z]|i', '', $type );

	$groups = BP_XProfile_Group::get( array(
		'fetch_fields' => true
	));

	if ( isset( $_GET['mode'] ) && isset( $_GET['group_id'] ) && 'add_field' == $_GET['mode'] )
		xprofile_admin_manage_field( $_GET['group_id'] );

	else if ( isset( $_GET['mode'] ) && isset( $_GET['group_id'] ) && isset( $_GET['field_id'] ) && 'edit_field' == $_GET['mode'] )
		xprofile_admin_manage_field($_GET['group_id'], $_GET['field_id'] );

	else if ( isset( $_GET['mode'] ) && isset( $_GET['field_id'] ) && 'delete_field' == $_GET['mode'] )
		xprofile_admin_delete_field($_GET['field_id'], 'field');

	else if ( isset( $_GET['mode'] ) && isset( $_GET['option_id'] ) && 'delete_option' == $_GET['mode'] )
		xprofile_admin_delete_field( $_GET['option_id'], 'option' );

	else if ( isset( $_GET['mode'] ) && 'add_group' == $_GET['mode'] )
		xprofile_admin_manage_group();

	else if ( isset( $_GET['mode'] ) && isset( $_GET['group_id'] ) && 'delete_group' == $_GET['mode'] )
		xprofile_admin_delete_group( $_GET['group_id'] );

	else if ( isset( $_GET['mode'] ) && isset( $_GET['group_id'] ) && 'edit_group' == $_GET['mode'] )
		xprofile_admin_manage_group( $_GET['group_id'] );

	else {
?>
	<div class="wrap">
		<h2><?php _e( 'Profile Field Setup', 'buddypress') ?></h2>
		<p><?php _e( 'Your users will distinguish themselves through their profile page. You must give them profile fields that allow them to describe themselves in a way that is relevant to the theme of your social network.', 'buddypress') ?></p>
		<p><?php _e('NOTE: Any fields in the first group will appear on the signup page.', 'buddypress'); ?></p>

		<form action="" id="profile-field-form" method="post">
			<?php wp_nonce_field( 'bp_reorder_fields', '_wpnonce_reorder_fields' ); ?>

			<?php wp_nonce_field( 'bp_reorder_groups', '_wpnonce_reorder_groups', false );

			if ( $message != '' ) :
				$type = ( $type == 'error' ) ? 'error' : 'updated';
?>

				<div id="message" class="<?php echo $type; ?> fade">
					<p><?php echo wp_specialchars( attribute_escape( $message ) ); ?></p>
				</div>
<?php		endif; ?>

			<div id="field-groups">
<?php
			if ( $groups ) :
				foreach ( $groups as $group ) { ?>

						<table id="group_<?php echo $group->id;?>" class="widefat field-group sortable">
							<thead>
								<tr class="grabber">
									<th scope="col" width="10"><img src="<?php echo BP_PLUGIN_URL ?>/bp-xprofile/admin/images/move.gif" alt="<?php _e( 'Drag', 'buddypress' ) ?>" /></th>
									<th scope="col" colspan="3"><?php echo attribute_escape( $group->name ); ?></th>
<?php
									if ( $group->can_delete ) :
?>
										<th scope="col"><a class="edit" href="admin.php?page=bp-profile-setup&amp;mode=edit_group&amp;group_id=<?php echo attribute_escape( $group->id ); ?>"><?php _e( 'Edit', 'buddypress' ) ?></a></th>
							    		<th scope="col"><a class="delete" href="admin.php?page=bp-profile-setup&amp;mode=delete_group&amp;group_id=<?php echo attribute_escape( $group->id ); ?>"><?php _e( 'Delete', 'buddypress' ) ?></a></th>
<?php
									else :
?>
										<th scope="col"><a class="edit" href="admin.php?page=bp-general-settings"><?php _e( 'Edit', 'buddypress' ) ?></a></th>
										<th scope="col">&nbsp;</th>
<?php
									endif;
?>

								</tr>
								<tr class="header">
									<td>&nbsp;</td>
									<td><?php _e( 'Field Name', 'buddypress' ) ?></td>
									<td width="14%"><?php _e( 'Field Type', 'buddypress' ) ?></td>
									<td width="6%"><?php _e( 'Required?', 'buddypress' ) ?></td>
									<td colspan="2" width="10%" style="text-align:center;"><?php _e( 'Action', 'buddypress' ) ?></td>
								</tr>
							</thead>

							<tfoot>
								<tr class="nodrag">
									<td colspan="6"><a href="admin.php?page=bp-profile-setup&amp;group_id=<?php echo attribute_escape( $group->id ); ?>&amp;mode=add_field"><?php _e( 'Add New Field', 'buddypress' ) ?></a></td>
								</tr>
							</tfoot>

							<tbody id="<?php echo $group->id;?>">
 <?php 
								if ( $group->fields ) :
									foreach ( $group->fields as $field ) {
										if ( 0 == $j % 2 )
											$class = '';
										else
											$class = 'alternate';

										$field = new BP_XProfile_Field( $field->id );
										if ( !$field->can_delete )
											$class .= ' core';
?>

										<tr id="field_<?php echo attribute_escape( $field->id ); ?>" class="sortable<?php if ( $class ) { echo ' ' . $class; } ?>">
											<td width="10"><img src="<?php echo BP_PLUGIN_URL ?>/bp-xprofile/admin/images/move.gif" alt="<?php _e( 'Drag', 'buddypress' ) ?>" /></td>
											<td><span title="<?php echo $field->description; ?>"><?php echo attribute_escape( $field->name ); ?> <?php if(!$field->can_delete) { ?> <?php _e( '(Core Field)', 'buddypress' ) ?><?php } ?></span></td>
											<td><?php echo attribute_escape( $field->type ); ?></td>
											<td style="text-align:center;"><?php if ( $field->is_required ) { echo '<img src="' . BP_PLUGIN_URL . '/bp-xprofile/admin/images/tick.gif" alt="' . __( 'Yes', 'buddypress' ) . '" />'; } else { ?>--<?php } ?></td>
											<td style="text-align:center;"><a class="edit" href="<?php if ( !$field->can_delete ) { ?>admin.php?page=bp-general-settings<?php } else { ?>admin.php?page=bp-profile-setup&amp;group_id=<?php echo attribute_escape( $group->id ); ?>&amp;field_id=<?php echo attribute_escape( $field->id ); ?>&amp;mode=edit_field<?php } ?>"><?php _e( 'Edit', 'buddypress' ) ?></a></td>
											<td style="text-align:center;"><?php if ( !$field->can_delete ) { ?>&nbsp;<?php } else { ?><a class="delete" href="admin.php?page=bp-profile-setup&amp;field_id=<?php echo attribute_escape( $field->id ); ?>&amp;mode=delete_field"><?php _e( 'Delete', 'buddypress' ) ?></a><?php } ?></td>
										</tr>
<?php
									} /* end for */

								else : /* !$group->fields */
?>

									<tr class="nodrag">
										<td colspan="6"><?php _e( 'There are no fields in this group.', 'buddypress' ) ?></td>
									</tr>
<?php
								endif; /* end $group->fields */
?>

							</tbody>
						</table>
<?php
				} /* End For */ ?>
					</div>
					<p>
						<a class="button" href="admin.php?page=bp-profile-setup&amp;mode=add_group"><?php _e( 'Add New Field Group', 'buddypress' ) ?></a>
					</p>
<?php 
				else :
?>

				<div id="message" class="error"><p><?php _e('You have no groups.', 'buddypress' ); ?></p></div>
				<p><a href="admin.php?page=bp-profile-setup&amp;mode=add_group"><?php _e( 'Add New Group', 'buddypress' ) ?></a></p>
<?php
				endif;
?>

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

	$group = new BP_XProfile_Group( $group_id );

	if ( !$group->delete() ) {
		$message = __( 'There was an error deleting the group. Please try again', 'buddypress' );
		$type = 'error';
	} else {
		$message = __( 'The group was deleted successfully.', 'buddypress' );
		$type = 'success';

		do_action( 'xprofile_groups_deleted_group', $group );
	}

	unset( $_GET['mode'] ); /* TODO: wtf? */
	xprofile_admin( $message, $type );
}


/**************************************************************************
 xprofile_admin_manage_field()

 Handles the adding or editing of profile field data for a user.
 **************************************************************************/

function xprofile_admin_manage_field( $group_id, $field_id = null ) {
	global $bp, $wpdb, $message, $groups;

	$field = new BP_XProfile_Field($field_id);
	$field->group_id = $group_id;

	if ( isset($_POST['saveField']) ) {
		if ( BP_XProfile_Field::admin_validate() ) {
			$field->name = wp_filter_kses( $_POST['title'] );
			$field->description = wp_filter_kses( $_POST['description'] );
			$field->is_required = wp_filter_kses( $_POST['required'] );
			$field->type = wp_filter_kses( $_POST['fieldtype'] );
			$field->order_by = wp_filter_kses( $_POST["sort_order_{$field->type}"] );

			$field->field_order = $wpdb->get_var( $wpdb->prepare( "SELECT field_order FROM {$bp->profile->table_name_fields} WHERE id = %d", $field_id ) );

			if ( !$field->field_order ) {
				$field->field_order = (int) $wpdb->get_var( $wpdb->prepare( "SELECT max(field_order) FROM {$bp->profile->table_name_fields} WHERE group_id = %d", $group_id ) );
				$field->field_order++;
			}

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

				$groups = BP_XProfile_Group::get();
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

	parse_str( $_POST['field_order'], $order );
	$field_group_id = $_POST['field_group_id'];

	foreach ( (array) $order['field'] as $position => $field_id )
		xprofile_update_field_position( (int) $field_id, (int) $position, (int) $field_group_id );

}
add_action( 'wp_ajax_xprofile_reorder_fields', 'xprofile_ajax_reorder_fields' );

function xprofile_ajax_reorder_field_groups() {
	global $bp;

	/* Check the nonce */
	check_admin_referer( 'bp_reorder_groups', '_wpnonce_reorder_groups' );

	if ( empty( $_POST['group_order'] ) )
		return false;

	parse_str( $_POST['group_order'], $order );

	foreach ( (array) $order['group'] as $position => $field_group_id )
		xprofile_update_field_group_position( (int) $field_group_id, (int) $position );

}
add_action( 'wp_ajax_xprofile_reorder_groups', 'xprofile_ajax_reorder_field_groups' );
