<?php

/**
 * BuddyPress XProfile Admin
 *
 * @package BuddyPress
 * @subpackage XProfileAdmin
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Creates the administration interface menus and checks to see if the DB
 * tables are set up.
 *
 * @package BuddyPress XProfile
 * @uses bp_current_user_can() returns true if the current user is a site admin, false if not
 * @uses add_users_page() Adds a submenu tab to a top level tab in the admin area
 * @return
 */
function xprofile_add_admin_menu() {

	if ( !bp_current_user_can( 'bp_moderate' ) )
		return false;

	add_users_page( __( 'Profile Fields', 'buddypress' ), __( 'Profile Fields', 'buddypress' ), 'manage_options', 'bp-profile-setup', 'xprofile_admin' );
}
add_action( bp_core_admin_hook(), 'xprofile_add_admin_menu' );

/**
 * Handles all actions for the admin area for creating, editing and deleting
 * profile groups and fields.
 */
function xprofile_admin( $message = '', $type = 'error' ) {

	$type = preg_replace( '|[^a-z]|i', '', $type );

	$groups = BP_XProfile_Group::get( array(
		'fetch_fields' => true
	) );

	if ( isset( $_GET['mode'] ) && isset( $_GET['group_id'] ) && 'add_field' == $_GET['mode'] )
		xprofile_admin_manage_field( $_GET['group_id'] );

	else if ( isset( $_GET['mode'] ) && isset( $_GET['group_id'] ) && isset( $_GET['field_id'] ) && 'edit_field' == $_GET['mode'] )
		xprofile_admin_manage_field( $_GET['group_id'], $_GET['field_id'] );

	else if ( isset( $_GET['mode'] ) && isset( $_GET['field_id'] ) && 'delete_field' == $_GET['mode'] )
		xprofile_admin_delete_field( $_GET['field_id'], 'field');

	else if ( isset( $_GET['mode'] ) && isset( $_GET['option_id'] ) && 'delete_option' == $_GET['mode'] )
		xprofile_admin_delete_field( $_GET['option_id'], 'option' );

	else if ( isset( $_GET['mode'] ) && 'add_group' == $_GET['mode'] )
		xprofile_admin_manage_group();

	else if ( isset( $_GET['mode'] ) && isset( $_GET['group_id'] ) && 'delete_group' == $_GET['mode'] )
		xprofile_admin_delete_group( $_GET['group_id'] );

	else if ( isset( $_GET['mode'] ) && isset( $_GET['group_id'] ) && 'edit_group' == $_GET['mode'] )
		xprofile_admin_manage_group( $_GET['group_id'] );

	else { ?>

	<div class="wrap">

		<?php screen_icon( 'users' ); ?>

		<h2>
			<?php _e( 'Profile Fields', 'buddypress'); ?>
			<a id="add_group" class="add-new-h2" href="users.php?page=bp-profile-setup&amp;mode=add_group"><?php _e( 'Add New Field Group', 'buddypress' ); ?></a>
		</h2>

		<p><?php echo sprintf( __( 'Fields in the "%s" group will appear on the signup page.', 'buddypress' ), esc_html( stripslashes( bp_get_option( 'bp-xprofile-base-group-name' ) ) ) ) ?></p>

		<form action="" id="profile-field-form" method="post">

			<?php

			wp_nonce_field( 'bp_reorder_fields', '_wpnonce_reorder_fields'        );
			wp_nonce_field( 'bp_reorder_groups', '_wpnonce_reorder_groups', false );

			if ( !empty( $message ) ) :
				$type = ( $type == 'error' ) ? 'error' : 'updated'; ?>

				<div id="message" class="<?php echo $type; ?> fade">
					<p><?php echo esc_html( esc_attr( $message ) ); ?></p>
				</div>

			<?php endif; ?>

			<div id="tabs">
				<ul id="field-group-tabs">

					<?php if ( !empty( $groups ) ) : foreach ( $groups as $group ) : ?>

						<li id="group_<?php echo $group->id; ?>"><a href="#tabs-<?php echo $group->id; ?>" class="ui-tab"><?php echo esc_attr( $group->name ); ?><?php if ( !$group->can_delete ) : ?> <?php _e( '(Primary)', 'buddypress'); endif; ?></a></li>

					<?php endforeach; endif; ?>

				</ul>

				<?php if ( !empty( $groups ) ) : foreach ( $groups as $group ) : ?>

					<noscript>
						<h3><?php echo esc_attr( $group->name ); ?></h3>
					</noscript>

					<div id="tabs-<?php echo $group->id; ?>" class="tab-wrapper">
						<div class="tab-toolbar">
							<div class="tab-toolbar-left">
								<a class="button-primary" href="users.php?page=bp-profile-setup&amp;group_id=<?php echo esc_attr( $group->id ); ?>&amp;mode=add_field"><?php _e( 'Add New Field', 'buddypress' ); ?></a>
								<a class="button edit" href="users.php?page=bp-profile-setup&amp;mode=edit_group&amp;group_id=<?php echo esc_attr( $group->id ); ?>"><?php _e( 'Edit Group', 'buddypress' ); ?></a>

								<?php if ( $group->can_delete ) : ?>

									<a class="confirm submitdelete deletion ajax-option-delete" href="users.php?page=bp-profile-setup&amp;mode=delete_group&amp;group_id=<?php echo esc_attr( $group->id ); ?>"><?php _e( 'Delete Group', 'buddypress' ); ?></a>

								<?php endif; ?>

							</div>
						</div>

						<fieldset id="<?php echo $group->id; ?>" class="connectedSortable field-group">

							<?php if ( $group->description ) : ?>

								<legend><?php echo esc_attr( $group->description ) ?></legend>

							<?php endif;

							if ( !empty( $group->fields ) ) :
								foreach ( $group->fields as $field ) {

									// Load the field
									$field = new BP_XProfile_Field( $field->id );

									$class = '';
									if ( !$field->can_delete )
										$class = ' core nodrag';

									/* This function handles the WYSIWYG profile field
									* display for the xprofile admin setup screen
									*/
									xprofile_admin_field( $field, $group, $class );

								} // end for

							else : // !$group->fields ?>

								<p class="nodrag nofields"><?php _e( 'There are no fields in this group.', 'buddypress' ); ?></p>

							<?php endif; // end $group->fields ?>

						</fieldset>
					</div>

				<?php endforeach; else : ?>

					<div id="message" class="error"><p><?php _e( 'You have no groups.', 'buddypress' ); ?></p></div>
					<p><a href="users.php?page=bp-profile-setup&amp;mode=add_group"><?php _e( 'Add New Group', 'buddypress' ); ?></a></p>

				<?php endif; ?>

				<div id="tabs-bottom">&nbsp;</div>
			</div>
		</form>
	</div>

<?php
	}
}

/**
 * Handles the adding or editing of groups.
 */
function xprofile_admin_manage_group( $group_id = null ) {
	global $message, $type;

	$group = new BP_XProfile_Group( $group_id );

	if ( isset( $_POST['save_group'] ) ) {
		if ( BP_XProfile_Group::admin_validate( $_POST ) ) {
			$group->name		= wp_filter_kses( $_POST['group_name'] );
			$group->description	= !empty( $_POST['group_description'] ) ? wp_filter_kses( $_POST['group_description'] ) : '';

			if ( !$group->save() ) {
				$message = __( 'There was an error saving the group. Please try again', 'buddypress' );
				$type    = 'error';
			} else {
				$message = __( 'The group was saved successfully.', 'buddypress' );
				$type    = 'success';

				if ( 1 == $group_id )
					bp_update_option( 'bp-xprofile-base-group-name', $group->name );

				do_action( 'xprofile_groups_saved_group', $group );
			}

			unset( $_GET['mode'] );
			xprofile_admin( $message, $type );

		} else {
			$group->render_admin_form( $message );
		}
	} else {
		$group->render_admin_form();
	}
}

/**
 * Handles the deletion of profile data groups.
 */
function xprofile_admin_delete_group( $group_id ) {
	global $message, $type;

	$group = new BP_XProfile_Group( $group_id );

	if ( !$group->delete() ) {
		$message = __( 'There was an error deleting the group. Please try again', 'buddypress' );
		$type    = 'error';
	} else {
		$message = __( 'The group was deleted successfully.', 'buddypress' );
		$type    = 'success';

		do_action( 'xprofile_groups_deleted_group', $group );
	}

	unset( $_GET['mode'] );
	xprofile_admin( $message, $type );
}

/**
 * Handles the adding or editing of profile field data for a user.
 */
function xprofile_admin_manage_field( $group_id, $field_id = null ) {
	global $bp, $wpdb, $message, $groups;

	$field           = new BP_XProfile_Field( $field_id );
	$field->group_id = $group_id;

	if ( isset( $_POST['saveField'] ) ) {
		if ( BP_XProfile_Field::admin_validate() ) {
			$field->name        = wp_filter_kses( $_POST['title'] );
			$field->description = !empty( $_POST['description'] ) ? wp_filter_kses( $_POST['description'] ) : '';
			$field->is_required = wp_filter_kses( $_POST['required'] );
			$field->type        = wp_filter_kses( $_POST['fieldtype'] );

			if ( !empty( $_POST["sort_order_{$field->type}"] ) )
				$field->order_by = wp_filter_kses( $_POST["sort_order_{$field->type}"] );

			$field->field_order = $wpdb->get_var( $wpdb->prepare( "SELECT field_order FROM {$bp->profile->table_name_fields} WHERE id = %d", $field_id ) );

			if ( !$field->field_order ) {
				$field->field_order = (int) $wpdb->get_var( $wpdb->prepare( "SELECT max(field_order) FROM {$bp->profile->table_name_fields} WHERE group_id = %d", $group_id ) );
				$field->field_order++;
			}

			// For new profile fields, set the $field_id. For existing profile fields,
			// this will overwrite $field_id with the same value.
			$field_id = $field->save();

			if ( !$field_id ) {
				$message = __( 'There was an error saving the field. Please try again', 'buddypress' );
				$type = 'error';

				unset( $_GET['mode'] );

				xprofile_admin( $message, $type );
			} else {
				$message = __( 'The field was saved successfully.', 'buddypress' );
				$type = 'success';

				if ( 1 == $field_id )
					bp_update_option( 'bp-xprofile-fullname-field-name', $field->name );

				if ( !empty( $_POST['default-visibility'] ) ) {
					bp_xprofile_update_field_meta( $field_id, 'default_visibility', $_POST['default-visibility'] );
				}

				if ( !empty( $_POST['allow-custom-visibility'] ) ) {
					bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', $_POST['allow-custom-visibility'] );
				}

				unset( $_GET['mode'] );

				do_action( 'xprofile_fields_saved_field', $field );

				$groups = BP_XProfile_Group::get();
				xprofile_admin( $message, $type );
			}
		} else {
			$field->render_admin_form( $message );
		}
	} else {
		$field->render_admin_form();
	}
}

/**
 * Handles the deletion of a profile field (or field option)
 *
 * @since BuddyPress (1.0)
 * @global string $message The feedback message to show
 * @global $type The type of feedback message to show
 * @param int $field_id The field to delete
 * @param string $field_type The type of field being deleted
 * @param bool $delete_data Should the field data be deleted too?
 */
function xprofile_admin_delete_field( $field_id, $field_type = 'field', $delete_data = false ) {
	global $message, $type;

	// Switch type to 'option' if type is not 'field'
	// @todo trust this param
	$field_type  = ( 'field' == $field_type ) ? __( 'field', 'buddypress' ) : __( 'option', 'buddypress' );
	$field       = new BP_XProfile_Field( $field_id );

	if ( !$field->delete( (bool) $delete_data ) ) {
		$message = sprintf( __( 'There was an error deleting the %s. Please try again', 'buddypress' ), $field_type );
		$type    = 'error';
	} else {
		$message = sprintf( __( 'The %s was deleted successfully!', 'buddypress' ), $field_type );
		$type    = 'success';

		do_action( 'xprofile_fields_deleted_field', $field );
	}

	unset( $_GET['mode'] );
	xprofile_admin( $message, $type );
}

/**
 * Handles the ajax reordering of fields within a group
 */
function xprofile_ajax_reorder_fields() {

	// Check the nonce
	check_admin_referer( 'bp_reorder_fields', '_wpnonce_reorder_fields' );

	if ( empty( $_POST['field_order'] ) )
		return false;

	parse_str( $_POST['field_order'], $order );

	$field_group_id = $_POST['field_group_id'];

	foreach ( (array) $order['field'] as $position => $field_id ) {
		xprofile_update_field_position( (int) $field_id, (int) $position, (int) $field_group_id );
	}
}
add_action( 'wp_ajax_xprofile_reorder_fields', 'xprofile_ajax_reorder_fields' );

/**
 * Handles the reordering of field groups
 */
function xprofile_ajax_reorder_field_groups() {

	// Check the nonce
	check_admin_referer( 'bp_reorder_groups', '_wpnonce_reorder_groups' );

	if ( empty( $_POST['group_order'] ) )
		return false;

	parse_str( $_POST['group_order'], $order );

	foreach ( (array) $order['group'] as $position => $field_group_id ) {
		xprofile_update_field_group_position( (int) $field_group_id, (int) $position );
	}
}
add_action( 'wp_ajax_xprofile_reorder_groups', 'xprofile_ajax_reorder_field_groups' );

/**
 * Handles the WYSIWYG display of each profile field on the edit screen
 */
function xprofile_admin_field( $admin_field, $admin_group, $class = '' ) {
	global $field;

	$field = $admin_field; ?>

	<fieldset id="field_<?php echo esc_attr( $field->id ); ?>" class="sortable<?php echo ' ' . $field->type; if ( !empty( $class ) ) echo ' ' . $class; ?>">
		<legend><span><?php bp_the_profile_field_name(); ?> <?php if( !$field->can_delete ) : ?> <?php _e( '(Primary)', 'buddypress' ); endif; ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(Required)', 'buddypress' ) ?><?php endif; ?></span></legend>
		<div class="field-wrapper">

			<?php switch ( $field->type ) : case 'textbox' : ?>

				<input type="text" name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>" value="" />

			<?php break; case 'textarea' : ?>

				<textarea rows="5" cols="40" name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>"></textarea>

			<?php break; case 'selectbox' : ?>

				<select name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>">

					<?php bp_the_profile_field_options() ?>

				</select>

			<?php break; case 'multiselectbox' : ?>

				<select name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>" multiple="multiple">

					<?php bp_the_profile_field_options() ?>

				</select>

			<?php break; case 'radio' : ?>

				<?php bp_the_profile_field_options() ?>

				<?php if ( !bp_get_the_profile_field_is_required() ) : ?>

					<a class="clear-value" href="javascript:clear( '<?php bp_the_profile_field_input_name() ?>' );"><?php _e( 'Clear', 'buddypress' ) ?></a>

				<?php endif; ?>

			<?php break; case 'checkbox' : ?>

				<?php bp_the_profile_field_options(); ?>

			<?php break; case 'datebox' : ?>

				<select name="<?php bp_the_profile_field_input_name(); ?>_day" id="<?php bp_the_profile_field_input_name(); ?>_day">

					<?php bp_the_profile_field_options( 'type=day' ); ?>

				</select>

				<select name="<?php bp_the_profile_field_input_name(); ?>_month" id="<?php bp_the_profile_field_input_name(); ?>_month">

					<?php bp_the_profile_field_options( 'type=month' ); ?>

				</select>

				<select name="<?php bp_the_profile_field_input_name(); ?>_year" id="<?php bp_the_profile_field_input_name(); ?>_year">

					<?php bp_the_profile_field_options( 'type=year' ); ?>

				</select>

			<?php break; default : ?>

			<?php do_action( 'xprofile_admin_field', $field, 1 ); ?>

			<?php endswitch; ?>

			<?php if ( $field->description ) : ?>

				<p class="description"><?php echo esc_attr( $field->description ); ?></p>

			<?php endif; ?>

			<div class="actions">
				<a class="button edit" href="users.php?page=bp-profile-setup&amp;group_id=<?php echo esc_attr( $admin_group->id ); ?>&amp;field_id=<?php echo esc_attr( $field->id ); ?>&amp;mode=edit_field"><?php _e( 'Edit', 'buddypress' ); ?></a>

				<?php if ( $field->can_delete ) : ?>

					<a class="confirm submit-delete deletion" href="users.php?page=bp-profile-setup&amp;field_id=<?php echo esc_attr( $field->id ); ?>&amp;mode=delete_field"><?php _e( 'Delete', 'buddypress' ); ?></a>

				<?php endif; ?>
			</div>
		</div>
	</fieldset>

<?php
}
