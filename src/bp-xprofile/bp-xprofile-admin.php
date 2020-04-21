<?php
/**
 * BuddyPress XProfile Admin.
 *
 * @package BuddyPress
 * @subpackage XProfileAdmin
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates the administration interface menus and checks to see if the DB
 * tables are set up.
 *
 * @since 1.0.0
 *
 * @return bool
 */
function xprofile_add_admin_menu() {

	// Bail if current user cannot moderate community.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	add_users_page( _x( 'Profile Fields', 'xProfile admin page title', 'buddypress' ), _x( 'Profile Fields', 'Admin Users menu', 'buddypress' ), 'manage_options', 'bp-profile-setup', 'xprofile_admin' );
}
add_action( bp_core_admin_hook(), 'xprofile_add_admin_menu' );

/**
 * Handles all actions for the admin area for creating, editing and deleting
 * profile groups and fields.
 *
 * @since 1.0.0
 *
 * @param string $message Message to display.
 * @param string $type    Type of action to be displayed.
 */
function xprofile_admin( $message = '', $type = 'error' ) {

	// What mode?
	$mode = ! empty( $_GET['mode'] )
		? sanitize_key( $_GET['mode'] )
		: false;

	// Group ID.
	$group_id = ! empty( $_GET['group_id'] )
		? intval( $_GET['group_id'] )
		: false;

	// Field ID.
	$field_id = ! empty( $_GET['field_id'] )
		? intval( $_GET['field_id'] )
		: false;

	// Option ID.
	$option_id = ! empty( $_GET['option_id'] )
		? intval( $_GET['option_id'] )
		: false;

	// Allowed modes.
	$allowed_modes = array(
		'add_group',
		'edit_group',
		'delete_group',
		'add_field',
		'edit_field',
		'delete_field',
		'delete_option'
	);

	// Is an allowed mode.
	if ( in_array( $mode, $allowed_modes, true ) ) {

		// All group actions.
		if ( false !== $group_id ) {

			// Add field to group.
			if ( 'add_field' == $mode ) {
				xprofile_admin_manage_field( $group_id );

			// Edit field of group.
			} elseif ( ! empty( $field_id ) && 'edit_field' === $mode ) {
				xprofile_admin_manage_field( $group_id, $field_id );

			// Delete group.
			} elseif ( 'delete_group' === $mode ) {
				xprofile_admin_delete_group( $group_id );

			// Edit group.
			} elseif ( 'edit_group' === $mode ) {
				xprofile_admin_manage_group( $group_id );
			}

		// Delete field.
		} elseif ( ( false !== $field_id ) && ( 'delete_field' === $mode ) ) {
			xprofile_admin_delete_field( $field_id, 'field');

		// Delete option.
		} elseif ( ! empty( $option_id ) && 'delete_option' === $mode ) {
			xprofile_admin_delete_field( $option_id, 'option' );

		// Add group.
		} elseif ( 'add_group' == $mode ) {
			xprofile_admin_manage_group();
		}

	} else {
		xprofile_admin_screen( $message, $type );
	}
}

/**
 * Output the main XProfile management screen.
 *
 * @since 2.3.0
 *
 * @param string $message Feedback message.
 * @param string $type    Feedback type.
 *
 * @todo Improve error message output
 */
function xprofile_admin_screen( $message = '', $type = 'error' ) {

	// Users admin URL.
	$url = bp_get_admin_url( 'users.php' );

	// Add Group.
	$add_group_url = add_query_arg( array(
		'page' => 'bp-profile-setup',
		'mode' => 'add_group'
	), $url );

	// Validate type.
	$type = preg_replace( '|[^a-z]|i', '', $type );

	// Get all of the profile groups & fields.
	$groups = bp_xprofile_get_groups( array(
		'fetch_fields' => true
	) ); ?>

	<div class="wrap">
		<?php if ( version_compare( $GLOBALS['wp_version'], '4.8', '>=' ) ) : ?>

			<h1 class="wp-heading-inline"><?php _ex( 'Profile Fields', 'Settings page header', 'buddypress'); ?></h1>

				<a id="add_group" class="page-title-action" href="<?php echo esc_url( $add_group_url ); ?>"><?php _e( 'Add New Field Group', 'buddypress' ); ?></a>

			<hr class="wp-header-end">

		<?php else : ?>

			<h1>
				<?php _ex( 'Profile Fields', 'Settings page header', 'buddypress'); ?>
				<a id="add_group" class="add-new-h2" href="<?php echo esc_url( $add_group_url ); ?>"><?php _e( 'Add New Field Group', 'buddypress' ); ?></a>
			</h1>

		<?php endif; ?>

		<form action="" id="profile-field-form" method="post">

			<?php

			wp_nonce_field( 'bp_reorder_fields', '_wpnonce_reorder_fields'        );
			wp_nonce_field( 'bp_reorder_groups', '_wpnonce_reorder_groups', false );

			if ( ! empty( $message ) ) :
				$type = ( $type == 'error' ) ? 'error' : 'updated'; ?>

				<div id="message" class="<?php echo $type; ?> fade">
					<p><?php echo esc_html( $message ); ?></p>
				</div>

			<?php endif; ?>

			<div id="tabs" aria-live="polite" aria-atomic="true" aria-relevant="all">
				<ul id="field-group-tabs">

					<?php if ( !empty( $groups ) ) : foreach ( $groups as $group ) : ?>

						<li id="group_<?php echo esc_attr( $group->id ); ?>">
							<a href="#tabs-<?php echo esc_attr( $group->id ); ?>" class="ui-tab">
								<?php
								/** This filter is documented in bp-xprofile/bp-xprofile-template.php */
								echo esc_html( apply_filters( 'bp_get_the_profile_group_name', $group->name ) );
								?>

								<?php if ( !$group->can_delete ) : ?>
									<?php _e( '(Primary)', 'buddypress'); ?>
								<?php endif; ?>

							</a>
						</li>

					<?php endforeach; endif; ?>

				</ul>

				<?php if ( !empty( $groups ) ) : foreach ( $groups as $group ) :

					// Add Field to Group URL.
					$add_field_url = add_query_arg( array(
						'page'     => 'bp-profile-setup',
						'mode'     => 'add_field',
						'group_id' => (int) $group->id
					), $url );

					// Edit Group URL.
					$edit_group_url = add_query_arg( array(
						'page'     => 'bp-profile-setup',
						'mode'     => 'edit_group',
						'group_id' => (int) $group->id
					), $url );

					// Delete Group URL.
					$delete_group_url = wp_nonce_url( add_query_arg( array(
						'page'     => 'bp-profile-setup',
						'mode'     => 'delete_group',
						'group_id' => (int) $group->id
					), $url ), 'bp_xprofile_delete_group' ); ?>

					<noscript>
						<h3><?php
						/** This filter is documented in bp-xprofile/bp-xprofile-template.php */
						echo esc_html( apply_filters( 'bp_get_the_profile_group_name', $group->name ) );
						?></h3>
					</noscript>

					<div id="tabs-<?php echo esc_attr( $group->id ); ?>" class="tab-wrapper">
						<div class="tab-toolbar">
							<div class="tab-toolbar-left">
								<a class="button-primary" href="<?php echo esc_url( $add_field_url ); ?>"><?php _e( 'Add New Field', 'buddypress' ); ?></a>
								<a class="button edit" href="<?php echo esc_url( $edit_group_url ); ?>"><?php _ex( 'Edit Group', 'Edit Profile Fields Group', 'buddypress' ); ?></a>

								<?php if ( $group->can_delete ) : ?>

									<div class="delete-button">
										<a class="confirm submitdelete deletion ajax-option-delete" href="<?php echo esc_url( $delete_group_url ); ?>"><?php _ex( 'Delete Group', 'Delete Profile Fields Group', 'buddypress' ); ?></a>
									</div>

								<?php endif; ?>

								<?php

								/**
								 * Fires at end of action buttons in xprofile management admin.
								 *
								 * @since 2.2.0
								 *
								 * @param BP_XProfile_Group $group BP_XProfile_Group object
								 *                                 for the current group.
								 */
								do_action( 'xprofile_admin_group_action', $group ); ?>

							</div>
						</div>

						<?php if ( ! empty( $group->description ) ) : ?>

							<p><?php
							/** This filter is documented in bp-xprofile/bp-xprofile-template.php */
							echo esc_html( apply_filters( 'bp_get_the_profile_group_description', $group->description ) );
							?></p>

						<?php endif; ?>

						<fieldset id="<?php echo esc_attr( $group->id ); ?>" class="connectedSortable field-group" aria-live="polite" aria-atomic="true" aria-relevant="all">
							<legend class="screen-reader-text"><?php
							/** This filter is documented in bp-xprofile/bp-xprofile-template.php */
							/* translators: accessibility text */
							printf( esc_html__( 'Fields for "%s" Group', 'buddypress' ), apply_filters( 'bp_get_the_profile_group_name', $group->name ) );
							?></legend>

							<?php

							if ( !empty( $group->fields ) ) :
								foreach ( $group->fields as $field ) {

									// Load the field.
									$field = xprofile_get_field( $field->id );

									$class = '';
									if ( empty( $field->can_delete ) ) {
										$class = ' core nodrag';
									}

									/**
									 * This function handles the WYSIWYG profile field
									 * display for the xprofile admin setup screen.
									 */
									xprofile_admin_field( $field, $group, $class );

								} // end for

							else : // !$group->fields ?>

								<p class="nodrag nofields"><?php _e( 'There are no fields in this group.', 'buddypress' ); ?></p>

							<?php endif; // End $group->fields. ?>

						</fieldset>

						<?php if ( empty( $group->can_delete ) ) : ?>

							<p><?php esc_html_e( '* Fields in this group appear on the signup page.', 'buddypress' ); ?></p>

						<?php endif; ?>

					</div>

				<?php endforeach; else : ?>

					<div id="message" class="error"><p><?php _ex( 'You have no groups.', 'You have no profile fields groups.', 'buddypress' ); ?></p></div>
					<p><a href="<?php echo esc_url( $add_group_url ); ?>"><?php _ex( 'Add New Group', 'Add New Profile Fields Group', 'buddypress' ); ?></a></p>

				<?php endif; ?>

			</div>
		</form>
	</div>

<?php
}

/**
 * Handles the adding or editing of groups.
 *
 * @since 1.0.0
 *
 * @param int|null $group_id Group ID to manage.
 */
function xprofile_admin_manage_group( $group_id = null ) {
	global $message, $type;

	// Get the field group.
	$group = new BP_XProfile_Group( $group_id );

	// Updating.
	if ( isset( $_POST['save_group'] ) ) {

		// Check nonce.
		check_admin_referer( 'bp_xprofile_admin_group', 'bp_xprofile_admin_group' );

		// Validate $_POSTed data.
		if ( BP_XProfile_Group::admin_validate() ) {

			// Set the group name.
			$group->name = $_POST['group_name'];

			// Set the group description.
			if ( ! empty( $_POST['group_description'] ) ) {
				$group->description = $_POST['group_description'];
			} else {
				$group->description = '';
			}

			// Attempt to save the field group.
			if ( false === $group->save() ) {
				$message = __( 'There was an error saving the group. Please try again.', 'buddypress' );
				$type    = 'error';

			// Save successful.
			} else {
				$message = __( 'The group was saved successfully.', 'buddypress' );
				$type    = 'success';

				// @todo remove these old options.
				if ( 1 == $group_id ) {
					bp_update_option( 'bp-xprofile-base-group-name', $group->name );
				}

				/**
				 * Fires at the end of the group adding/saving process, if successful.
				 *
				 * @since 1.0.0
				 *
				 * @param BP_XProfile_Group $group Current BP_XProfile_Group object.
				 */
				do_action( 'xprofile_groups_saved_group', $group );
			}

			xprofile_admin_screen( $message, $type );

		} else {
			$group->render_admin_form( $message );
		}
	} else {
		$group->render_admin_form();
	}
}

/**
 * Handles the deletion of profile data groups.
 *
 * @since 1.0.0
 *
 * @param int $group_id ID of the group to delete.
 */
function xprofile_admin_delete_group( $group_id ) {
	global $message, $type;

	check_admin_referer( 'bp_xprofile_delete_group' );

	$group = new BP_XProfile_Group( $group_id );

	if ( !$group->delete() ) {
		$message = _x( 'There was an error deleting the group. Please try again.', 'Error when deleting profile fields group', 'buddypress' );
		$type    = 'error';
	} else {
		$message = _x( 'The group was deleted successfully.', 'Profile fields group was deleted successfully', 'buddypress' );
		$type    = 'success';

		/**
		 * Fires at the end of group deletion process, if successful.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_XProfile_Group $group Current BP_XProfile_Group object.
		 */
		do_action( 'xprofile_groups_deleted_group', $group );
	}

	xprofile_admin_screen( $message, $type );
}

/**
 * Handles the adding or editing of profile field data for a user.
 *
 * @since 1.0.0
 *
 * @param int      $group_id ID of the group.
 * @param int|null $field_id ID of the field being managed.
 */
function xprofile_admin_manage_field( $group_id, $field_id = null ) {
	global $wpdb, $message, $groups;

	$bp = buddypress();

	if ( is_null( $field_id ) ) {
		$field = new BP_XProfile_Field();
	} else {
		$field = xprofile_get_field( $field_id );
	}

	$field->group_id = $group_id;

	if ( isset( $_POST['saveField'] ) ) {

		// Check nonce.
		check_admin_referer( 'bp_xprofile_admin_field', 'bp_xprofile_admin_field' );

		if ( BP_XProfile_Field::admin_validate() ) {
			$field->is_required = $_POST['required'];
			$field->type        = $_POST['fieldtype'];
			$field->name        = $_POST['title'];

			if ( ! empty( $_POST['description'] ) ) {
				$field->description = $_POST['description'];
			} else {
				$field->description = '';
			}

			if ( ! empty( $_POST["sort_order_{$field->type}"] ) ) {
				$field->order_by = $_POST["sort_order_{$field->type}"];
			}

			$field->field_order = $wpdb->get_var( $wpdb->prepare( "SELECT field_order FROM {$bp->profile->table_name_fields} WHERE id = %d", $field_id ) );
			if ( ! is_numeric( $field->field_order ) || is_wp_error( $field->field_order ) ) {
				$field->field_order = (int) $wpdb->get_var( $wpdb->prepare( "SELECT max(field_order) FROM {$bp->profile->table_name_fields} WHERE group_id = %d", $group_id ) );
				$field->field_order++;
			}

			// For new profile fields, set the $field_id. For existing profile
			// fields, this will overwrite $field_id with the same value.
			$field_id = $field->save();

			if ( empty( $field_id ) ) {
				$message = __( 'There was an error saving the field. Please try again.', 'buddypress' );
				$type    = 'error';
			} else {
				$message = __( 'The field was saved successfully.', 'buddypress' );
				$type    = 'success';

				// @todo remove these old options.
				if ( 1 == $field_id ) {
					bp_update_option( 'bp-xprofile-fullname-field-name', $field->name );
				}

				// Set member types.
				if ( isset( $_POST['has-member-types'] ) ) {
					$member_types = array();
					if ( isset( $_POST['member-types'] ) ) {
						$member_types = stripslashes_deep( $_POST['member-types'] );
					}

					$field->set_member_types( $member_types );
				}

				// Validate default visibility.
				if ( ! empty( $_POST['default-visibility'] ) && in_array( $_POST['default-visibility'], wp_list_pluck( bp_xprofile_get_visibility_levels(), 'id' ) ) ) {
					bp_xprofile_update_field_meta( $field_id, 'default_visibility', $_POST['default-visibility'] );
				}

				// Validate custom visibility.
				if ( ! empty( $_POST['allow-custom-visibility'] ) && in_array( $_POST['allow-custom-visibility'], array( 'allowed', 'disabled' ) ) ) {
					bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', $_POST['allow-custom-visibility'] );
				}

				// Validate signup.
				if ( ! empty( $_POST['signup-position'] ) ) {
					bp_xprofile_update_field_meta( $field_id, 'signup_position', (int) $_POST['signup-position'] );
				} else {
					bp_xprofile_delete_meta( $field_id, 'field', 'signup_position' );
				}

				// Save autolink settings.
				if ( isset( $_POST['do_autolink'] ) && 'on' === wp_unslash( $_POST['do_autolink'] ) ) {
					bp_xprofile_update_field_meta( $field_id, 'do_autolink', 'on' );
				} else {
					bp_xprofile_update_field_meta( $field_id, 'do_autolink', 'off' );
				}

				if ( $field->type_obj->do_settings_section() ) {
					$settings = isset( $_POST['field-settings'] ) ? wp_unslash( $_POST['field-settings'] ) : array();
					$field->admin_save_settings( $settings );
				}

				/**
				 * Fires at the end of the process to save a field for a user, if successful.
				 *
				 * @since 1.0.0
				 *
				 * @param BP_XProfile_Field $field Current BP_XProfile_Field object.
				 */
				do_action( 'xprofile_fields_saved_field', $field );

				$groups = bp_xprofile_get_groups();
			}

			xprofile_admin_screen( $message, $type );

		} else {
			$field->render_admin_form( $message );
		}
	} else {
		$field->render_admin_form();
	}
}

/**
 * Handles the deletion of a profile field (or field option).
 *
 * @since 1.0.0
 *
 * @global string $message The feedback message to show.
 * @global string $type The type of feedback message to show.
 *
 * @param int    $field_id    The field to delete.
 * @param string $field_type  The type of field being deleted.
 * @param bool   $delete_data Should the field data be deleted too.
 */
function xprofile_admin_delete_field( $field_id, $field_type = 'field', $delete_data = false ) {
	global $message, $type;

	check_admin_referer( 'bp_xprofile_delete_field-' . $field_id, 'bp_xprofile_delete_field' );

	// Switch type to 'option' if type is not 'field'.
	// @todo trust this param.
	$field_type  = ( 'field' == $field_type ) ? __( 'field', 'buddypress' ) : __( 'option', 'buddypress' );
	$field       = xprofile_get_field( $field_id );

	if ( !$field->delete( (bool) $delete_data ) ) {
		/* translators: %s: the field type */
		$message = sprintf( __( 'There was an error deleting the %s. Please try again.', 'buddypress' ), $field_type );
		$type    = 'error';
	} else {
		/* translators: %s: the field type */
		$message = sprintf( __( 'The %s was deleted successfully!', 'buddypress' ), $field_type );
		$type    = 'success';

		/**
		 * Fires at the end of the field deletion process, if successful.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_XProfile_Field $field Current BP_XProfile_Field object.
		 */
		do_action( 'xprofile_fields_deleted_field', $field );
	}

	xprofile_admin_screen( $message, $type );
}

/**
 * Handles the ajax reordering of fields within a group.
 *
 * @since 1.0.0
 */
function xprofile_ajax_reorder_fields() {

	// Check the nonce.
	check_admin_referer( 'bp_reorder_fields', '_wpnonce_reorder_fields' );

	if ( empty( $_POST['field_order'] ) ) {
		return false;
	}

	parse_str( $_POST['field_order'], $order );

	$field_group_id = $_POST['field_group_id'];

	foreach ( (array) $order['draggable_field'] as $position => $field_id ) {
		xprofile_update_field_position( (int) $field_id, (int) $position, (int) $field_group_id );
	}
}
add_action( 'wp_ajax_xprofile_reorder_fields', 'xprofile_ajax_reorder_fields' );

/**
 * Handles the reordering of field groups.
 *
 * @since 1.5.0
 */
function xprofile_ajax_reorder_field_groups() {

	// Check the nonce.
	check_admin_referer( 'bp_reorder_groups', '_wpnonce_reorder_groups' );

	if ( empty( $_POST['group_order'] ) ) {
		return false;
	}

	parse_str( $_POST['group_order'], $order );

	foreach ( (array) $order['group'] as $position => $field_group_id ) {
		xprofile_update_field_group_position( (int) $field_group_id, (int) $position );
	}
}
add_action( 'wp_ajax_xprofile_reorder_groups', 'xprofile_ajax_reorder_field_groups' );

/**
 * Handles the WYSIWYG display of each profile field on the edit screen.
 *
 * @since 1.5.0
 *
 * @param BP_XProfile_Field   $admin_field Admin field.
 * @param object $admin_group Admin group object.
 * @param string $class       Classes to append to output.
 */
function xprofile_admin_field( $admin_field, $admin_group, $class = '' ) {
	global $field;

	$field = $admin_field;

	// Users admin URL.
	$url = bp_get_admin_url( 'users.php' );

	// Edit.
	$field_edit_url = add_query_arg( array(
		'page'     => 'bp-profile-setup',
		'mode'     => 'edit_field',
		'group_id' => (int) $field->group_id,
		'field_id' => (int) $field->id
	), $url );

	// Delete.
	if ( $field->can_delete ) {
		$field_delete_url = add_query_arg( array(
			'page'     => 'bp-profile-setup',
			'mode'     => 'delete_field',
			'field_id' => (int) $field->id
		), $url . '#tabs-' . (int) $field->group_id );
	} ?>

	<fieldset id="draggable_field_<?php echo esc_attr( $field->id ); ?>" class="sortable<?php echo ' ' . $field->type; if ( !empty( $class ) ) echo ' ' . $class; ?>">
		<legend>
			<span>
				<?php bp_the_profile_field_name(); ?>

				<?php if ( empty( $field->can_delete )                                    ) : ?><?php esc_html_e( '(Primary)', 'buddypress' ); endif; ?>
				<?php bp_the_profile_field_required_label(); ?>
				<?php if ( bp_xprofile_get_meta( $field->id, 'field', 'signup_position' ) ) : ?><?php esc_html_e( '(Sign-up)', 'buddypress' ); endif; ?>
				<?php if ( bp_get_member_types() ) : echo $field->get_member_type_label(); endif; ?>

				<?php

				/**
				 * Fires at end of legend above the name field in base xprofile group.
				 *
				 * @since 2.2.0
				 *
				 * @param BP_XProfile_Field $field Current BP_XProfile_Field
				 *                                 object being rendered.
				 */
				do_action( 'xprofile_admin_field_name_legend', $field ); ?>
			</span>
		</legend>
		<div class="field-wrapper">

			<?php
			if ( in_array( $field->type, array_keys( bp_xprofile_get_field_types() ) ) ) {
				$field_type = bp_xprofile_create_field_type( $field->type );
				$field_type->admin_field_html();
			} else {

				/**
				 * Fires after the input if the current field is not in default field types.
				 *
				 * @since 1.5.0
				 *
				 * @param BP_XProfile_Field $field Current BP_XProfile_Field
				 *                                 object being rendered.
				 * @param int               $value Integer 1.
				 */
				do_action( 'xprofile_admin_field', $field, 1 );
			}
			?>

			<?php if ( $field->description ) : ?>

				<p class="description"><?php echo esc_attr( $field->description ); ?></p>

			<?php endif; ?>

			<div class="actions">
				<a class="button edit" href="<?php echo esc_url( $field_edit_url ); ?>"><?php _ex( 'Edit', 'Edit field link', 'buddypress' ); ?></a>

				<?php if ( $field->can_delete ) : ?>

					<div class="delete-button">
						<a class="confirm submit-delete deletion" href="<?php echo esc_url( wp_nonce_url( $field_delete_url, 'bp_xprofile_delete_field-' . $field->id, 'bp_xprofile_delete_field' ) ); ?>"><?php _ex( 'Delete', 'Delete field link', 'buddypress' ); ?></a>
					</div>

				<?php endif; ?>

				<?php

				/**
				 * Fires at end of field management links in xprofile management admin.
				 *
				 * @since 2.2.0
				 *
				 * @param BP_XProfile_Group $group BP_XProfile_Group object
				 *                                 for the current group.
				 */
				do_action( 'xprofile_admin_field_action', $field ); ?>

			</div>
		</div>
	</fieldset>

<?php
}

/**
 * Print <option> elements containing the xprofile field types.
 *
 * @since 2.0.0
 *
 * @param string $select_field_type The name of the field type that should be selected.
 *                                  Will defaults to "textbox" if NULL is passed.
 */
function bp_xprofile_admin_form_field_types( $select_field_type ) {
	$categories = array();

	if ( is_null( $select_field_type ) ) {
		$select_field_type = 'textbox';
	}

	// Sort each field type into its category.
	foreach ( bp_xprofile_get_field_types() as $field_name => $field_class ) {
		$field_type_obj = new $field_class;
		$the_category   = $field_type_obj->category;

		// Fallback to a catch-all if category not set.
		if ( ! $the_category ) {
			$the_category = _x( 'Other', 'xprofile field type category', 'buddypress' );
		}

		if ( isset( $categories[$the_category] ) ) {
			$categories[$the_category][] = array( $field_name, $field_type_obj );
		} else {
			$categories[$the_category] = array( array( $field_name, $field_type_obj ) );
		}
	}

	// Sort the categories alphabetically. ksort()'s SORT_NATURAL is only in PHP >= 5.4 :((.
	uksort( $categories, 'strnatcmp' );

	// Loop through each category and output form <options>.
	foreach ( $categories as $category => $fields ) {
		printf( '<optgroup label="%1$s">', esc_attr( $category ) );  // Already i18n'd in each profile type class.

		// Sort these fields types alphabetically.
		uasort( $fields, function( $a, $b ) { return strnatcmp( $a[1]->name, $b[1]->name ); } );

		foreach ( $fields as $field_type_obj ) {
			$field_name     = $field_type_obj[0];
			$field_type_obj = $field_type_obj[1];

			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $field_name ), selected( $select_field_type, $field_name, false ), esc_html( $field_type_obj->name ) );
		}

		printf( '</optgroup>' );
	}
}

// Load the xprofile user admin.
add_action( 'bp_init', array( 'BP_XProfile_User_Admin', 'register_xprofile_user_admin' ), 11 );
