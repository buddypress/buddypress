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
		'do_delete_group',
		'add_field',
		'edit_field',
		'delete_field',
		'do_delete_field',
		'delete_option',
		'do_delete_option',
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
			} elseif ( in_array( $mode, array( 'delete_group', 'do_delete_group' ), true ) ) {
				xprofile_admin_delete_group( $group_id );

			// Edit group.
			} elseif ( 'edit_group' === $mode ) {
				xprofile_admin_manage_group( $group_id );
			}

		// Delete field.
		} elseif ( ( false !== $field_id ) && ( in_array( $mode, array( 'delete_field', 'do_delete_field' ), true ) ) ) {
			$delete_data = false;
			if ( isset( $_GET['delete_data'] ) && $_GET['delete_data'] ) {
				$delete_data = true;
			}

			xprofile_admin_delete_field( $field_id, 'field', $delete_data );

		// Delete option.
		} elseif ( ! empty( $option_id ) && in_array( $mode, array( 'delete_option', 'do_delete_option' ), true ) ) {
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
		'mode' => 'add_group',
	), $url );

	// Validate type.
	$type = preg_replace( '|[^a-z]|i', '', $type );

	// Get all of the profile groups & fields.
	$groups = bp_xprofile_get_groups( array(
		'fetch_fields' => true,
	) ); ?>

	<div class="wrap">
		<h1 class="wp-heading-inline"><?php echo esc_html_x( 'Profile Fields', 'Settings page header', 'buddypress'); ?></h1>

			<a id="add_group" class="page-title-action" href="<?php echo esc_url( $add_group_url ); ?>"><?php esc_html_e( 'Add New Field Group', 'buddypress' ); ?></a>

		<hr class="wp-header-end">

		<form action="" id="profile-field-form" method="post">

			<?php

			wp_nonce_field( 'bp_reorder_fields', '_wpnonce_reorder_fields'        );
			wp_nonce_field( 'bp_reorder_groups', '_wpnonce_reorder_groups', false );

			if ( ! empty( $message ) ) :
				$type = ( $type == 'error' ) ? 'error' : 'updated'; ?>

				<div id="message" class="<?php echo esc_attr( $type ); ?> fade notice is-dismissible">
					<p><?php echo esc_html( $message ); ?></p>
				</div>

			<?php endif; ?>

			<div id="tabs" aria-live="polite" aria-atomic="true" aria-relevant="all">
				<ul id="field-group-tabs">

					<?php if ( ! empty( $groups ) ) : foreach ( $groups as $group ) : ?>

						<li id="group_<?php echo esc_attr( $group->id ); ?>">
							<a href="#tabs-<?php echo esc_attr( $group->id ); ?>" class="ui-tab">
								<?php
								/** This filter is documented in bp-xprofile/bp-xprofile-template.php */
								echo esc_html( apply_filters( 'bp_get_the_profile_group_name', $group->name ) );
								?>

								<?php if ( ! $group->can_delete ) : ?>
									<?php esc_html_e( '(Primary)', 'buddypress'); ?>
								<?php endif; ?>

							</a>
						</li>

					<?php endforeach; endif; ?>

					<li id="signup-group" class="not-sortable last">
						<a href="#tabs-signup-group" class="ui-tab">
							<?php esc_html_e( 'Signup Fields', 'buddypress' ); ?>
						</a>
					</li>

				</ul>

				<?php if ( ! empty( $groups ) ) : foreach ( $groups as $group ) :

					// Add Field to Group URL.
					$add_field_url = add_query_arg( array(
						'page'     => 'bp-profile-setup',
						'mode'     => 'add_field',
						'group_id' => (int) $group->id,
					), $url );

					// Edit Group URL.
					$edit_group_url = add_query_arg( array(
						'page'     => 'bp-profile-setup',
						'mode'     => 'edit_group',
						'group_id' => (int) $group->id,
					), $url );

					// Delete Group URL.
					$delete_group_url = wp_nonce_url( add_query_arg( array(
						'page'     => 'bp-profile-setup',
						'mode'     => 'delete_group',
						'group_id' => (int) $group->id,
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
								<a class="button-primary" href="<?php echo esc_url( $add_field_url ); ?>"><?php esc_html_e( 'Add New Field', 'buddypress' ); ?></a>
								<a class="button edit" href="<?php echo esc_url( $edit_group_url ); ?>"><?php echo esc_html_x( 'Edit Group', 'Edit Profile Fields Group', 'buddypress' ); ?></a>

								<?php if ( $group->can_delete ) : ?>

									<div class="delete-button">
										<a class="confirm submitdelete deletion ajax-option-delete" href="<?php echo esc_url( $delete_group_url ); ?>"><?php echo esc_html_x( 'Delete Group', 'Delete Profile Fields Group', 'buddypress' ); ?></a>
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
							printf( esc_html__( 'Fields for "%s" Group', 'buddypress' ), esc_html( apply_filters( 'bp_get_the_profile_group_name', $group->name ) ) );
							?></legend>

							<?php

							if ( ! empty( $group->fields ) ) :
								foreach ( $group->fields as $field ) {

									// Load the field.
									$field = xprofile_get_field( $field->id, null, false );

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

								<p class="nodrag nofields"><?php esc_html_e( 'There are no fields in this group.', 'buddypress' ); ?></p>

							<?php endif; // End $group->fields. ?>

						</fieldset>

					</div>

				<?php endforeach; else : ?>

					<div id="message" class="error notice is-dismissible"><p><?php echo esc_html_x( 'You have no groups.', 'You have no profile fields groups.', 'buddypress' ); ?></p></div>
					<p><a href="<?php echo esc_url( $add_group_url ); ?>"><?php echo esc_html_x( 'Add New Group', 'Add New Profile Fields Group', 'buddypress' ); ?></a></p>

				<?php endif; ?>

				<?php
				$signup_groups = bp_xprofile_get_groups(
					array(
						'fetch_fields'       => true,
						'signup_fields_only' => true,
					)
				);
				$has_signup_fields   = false;
				$signup_fields       = array();
				$signup_fields_order = bp_xprofile_get_signup_field_ids();
				?>
				<div id="tabs-signup-group" class="tab-wrapper">
					<div class="tab-toolbar">
						<p class="description"><?php esc_html_e( 'Drag fields from other groups and drop them on the above tab to include them into your registration form.', 'buddypress' ); ?></p>
					</div>
					<fieldset id="signup-fields" class="connectedSortable field-group" aria-live="polite" aria-atomic="true" aria-relevant="all">
						<legend class="screen-reader-text">
							<?php esc_html_e( 'Fields to use into the registration form', 'buddypress' );?>
						</legend>

						<?php
						if ( ! empty( $signup_groups ) ) {
							foreach ( $signup_groups as $signup_group ) {
								if ( ! empty( $signup_group->fields ) ) {
									$has_signup_fields = true;

									foreach ( $signup_group->fields as $signup_field ) {
										// Load the field.
										$_signup_field = xprofile_get_field( $signup_field, null, false );

										/**
										 * This function handles the WYSIWYG profile field
										 * display for the xprofile admin setup screen.
										 */
										$signup_fields[ $_signup_field->id ] = bp_xprofile_admin_get_signup_field( $_signup_field, $signup_group, '' );
									}
								}
							}

							// Output signup fields according to their signup position.
							foreach ( $signup_fields_order as $ordered_signup_field_id ) {
								if ( ! isset( $signup_fields[ $ordered_signup_field_id ] ) ) {
									continue;
								}

								// Escaping is done in `xprofile_admin_field()`.
								// phpcs:ignore WordPress.Security.EscapeOutput
								echo $signup_fields[ $ordered_signup_field_id ];
							}
						}

						if ( ! $has_signup_fields ) {
							?>
							<p class="nodrag nofields"><?php esc_html_e( 'There are no registration fields set. The registration form uses the primary group by default.', 'buddypress' ); ?></p>
							<?php
						}
						?>
					</fieldset>

					<?php if ( bp_get_signup_allowed() ) : ?>
						<p><?php esc_html_e( '* Fields in this group appear on the registration page.', 'buddypress' ); ?></p>
					<?php else : ?>
						<p>
							<?php
							// Include a link to edit settings.
							$settings_link = '';

							if ( is_multisite() && current_user_can( 'manage_network_users') ) {
								$settings_link = sprintf(
									' <a href="%1$s">%2$s</a>.',
									esc_url( network_admin_url( 'settings.php' ) ),
									esc_html__( 'Edit settings', 'buddypress' )
								);
							} elseif ( current_user_can( 'manage_options' ) ) {
								$settings_link = sprintf(
									' <a href="%1$s">%2$s</a>.',
									esc_url( bp_get_admin_url( 'options-general.php' ) ),
									esc_html__( 'Edit settings', 'buddypress' )
								);
							}

							printf(
								/* translators: %s is the link to the registration settings. */
								esc_html__( '* Fields in this group will appear on the registration page as soon as users will be able to register to your site.%s', 'buddypress' ),
								// phpcs:ignore WordPress.Security.EscapeOutput
								$settings_link
							);
							?>
						</p>
					<?php endif; ?>
				</div>
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
 * @global string $message The feedback message to show.
 * @global string $type    The type of feedback message to show.
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
 * @global string $message The feedback message to show.
 * @global string $type    The type of feedback message to show.
 *
 * @param int $group_id ID of the group to delete.
 */
function xprofile_admin_delete_group( $group_id ) {
	global $message, $type;

	check_admin_referer( 'bp_xprofile_delete_group' );

	$mode = ! empty( $_GET['mode'] )
		  ? sanitize_key( $_GET['mode'] )
		  : false;

	// Display the group delete confirmation screen.
	if ( 'delete_group' === $mode ) {
		xprofile_admin_delete_group_screen( $group_id );

	// Handle the deletion of group.
	} else {
		$group = new BP_XProfile_Group( $group_id );

		if ( ! $group->delete() ) {
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
}

/**
 * Display the delete confirmation screen of profile data groups.
 *
 * @since 7.0.0
 */
function xprofile_admin_delete_group_screen( $group_id ) {

	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		die( '-1' );
	}

	$group = new BP_XProfile_Group( $group_id );

	$base_url = remove_query_arg( array( 'mode', 'group_id', '_wpnonce' ), $_SERVER['REQUEST_URI'] ); ?>

	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Delete Field Group', 'buddypress' ) ?></h1>
		<hr class="wp-header-end">

		<p><?php esc_html_e( 'You are about to delete the following field group:', 'buddypress' ) ?></p>

		<ul class="bp-xprofile-delete-group-list">
			<li><?php echo esc_html( $group->name ); ?></li>
		</ul>

		<p><strong><?php esc_html_e( 'This action cannot be undone.', 'buddypress' ) ?></strong></p>

		<a class="button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'mode' => 'do_delete_group', 'group_id' => $group_id ), $base_url ), 'bp_xprofile_delete_group' ) ); ?>"><?php esc_html_e( 'Delete Permanently', 'buddypress' ) ?></a>
		<a class="button" href="<?php echo esc_attr( $base_url ); ?>"><?php esc_html_e( 'Cancel', 'buddypress' ) ?></a>
	</div>

	<?php
}

/**
 * Handles the adding or editing of profile field data for a user.
 *
 * @since 1.0.0
 *
 * @global wpdb   $wpdb    WordPress database object.
 * @global string $message The feedback message to show.
 * @global array  $groups  The list of matching xProfile field groups.
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
		$field = xprofile_get_field( $field_id, null, false );
	}

	$field->group_id = $group_id;

	if ( isset( $_POST['saveField'] ) ) {

		// Check nonce.
		check_admin_referer( 'bp_xprofile_admin_field', 'bp_xprofile_admin_field' );

		if ( BP_XProfile_Field::admin_validate() ) {
			$field->is_required = $_POST['required'];
			$field->type        = $_POST['fieldtype'];
			$field->name        = $_POST['title'];

			/*
			 * By default a Textbox field is created. To run field type's feature
			 * checks we need to set it to what it really is early.
			 */
			if ( is_null( $field_id ) ) {
				$field_type = bp_xprofile_create_field_type( $field->type );

				// If it's a placeholder, then the field type is not registered.
				if ( ! $field_type instanceof BP_XProfile_Field_Type_Placeholder ) {
					$field->type_obj = $field_type;
				}
			}

			if ( ! $field->field_type_supports( 'required' ) ) {
				$field->is_required = "0";
			}

			if ( ! empty( $_POST['description'] ) ) {
				$field->description = $_POST['description'];
			} else {
				$field->description = '';
			}

			if ( ! empty( $_POST[ "sort_order_{$field->type}" ] ) ) {
				$field->order_by = $_POST[ "sort_order_{$field->type}" ];
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
					$default_visibility = $_POST['default-visibility'];

					if ( ! $field->field_type_supports( 'allow_custom_visibility' ) ) {
						$default_visibility          = 'public';
						$available_visibility_levels = bp_xprofile_get_visibility_levels();

						if ( isset( $field->type_obj->visibility ) && in_array( $field->type_obj->visibility, array_keys( $available_visibility_levels ), true ) ) {
							$default_visibility = $field->type_obj->visibility;
						}
					}

					bp_xprofile_update_field_meta( $field_id, 'default_visibility', $default_visibility );
				}

				// Validate custom visibility.
				if ( ! empty( $_POST['allow-custom-visibility'] ) && in_array( $_POST['allow-custom-visibility'], array( 'allowed', 'disabled' ) ) ) {
					$allow_custom_visibility = $_POST['allow-custom-visibility'];

					if ( ! $field->field_type_supports( 'allow_custom_visibility' ) ) {
						$allow_custom_visibility = 'disabled';
					}

					bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', $allow_custom_visibility );
				}

				// Validate signup.
				if ( $field->field_type_supports( 'signup_position' ) ) {
					if ( ! empty( $_POST['signup-position'] ) ) {
						bp_xprofile_update_field_meta( $field_id, 'signup_position', (int) $_POST['signup-position'] );
					} else {
						bp_xprofile_delete_meta( $field_id, 'field', 'signup_position' );
					}
				}

				$do_autolink = '';
				if ( $field->field_type_supports( 'do_autolink' ) && isset( $_POST['do_autolink'] ) && $_POST['do_autolink'] ) {
					$do_autolink = wp_unslash( $_POST['do_autolink'] );
				}

				// Save autolink settings.
				if ( 'on' === $do_autolink ) {
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

	$mode = ! empty( $_GET['mode'] ) ? sanitize_key( $_GET['mode'] ) : false;

	// Switch type to 'option' if type is not 'field'.
	// @todo trust this param.
	$field_type  = ( 'field' == $field_type ) ? __( 'field', 'buddypress' ) : __( 'option', 'buddypress' );

	// Display the field/option delete confirmation screen.
	if ( in_array( $mode, array( 'delete_field', 'delete_option' ) ) ) {
		xprofile_admin_delete_field_screen( $field_id, $field_type );

	// Handle the deletion of field
	} else {
		$field = xprofile_get_field( $field_id, null, false );

		if ( ! $field->delete( (bool) $delete_data ) ) {
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
}

/**
 * Display the delete confirmation screen of xprofile field/option.
 *
 * @since 7.0.0
 */
function xprofile_admin_delete_field_screen( $field_id, $field_type ) {
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		die( '-1' );
	}

	$field = xprofile_get_field( $field_id, null, false );

	$base_url = remove_query_arg( array( 'page', 'mode', 'field_id', 'bp_xprofile_delete_field' ), $_SERVER['REQUEST_URI'] ); ?>

	<div class="wrap">
		<h1 class="wp-heading-inline">
			<?php
			printf(
				/* translators: %s is the field type name. */
				esc_html__( 'Delete %s', 'buddypress' ),
				esc_html( $field_type )
			);
			?>
		</h1>

		<hr class="wp-header-end">

		<p>
			<?php
			printf(
				/* translators: 1 is the field type name. 2 is the field name */
				esc_html__( 'You are about to delete the following %1$s: %2$s.', 'buddypress' ),
				esc_html( $field_type ),
				'<strong>' . esc_html( $field->name ) . '</strong>'
			);
			?>
		</p>

		<form action="<?php echo esc_url( $base_url ); ?>" method="get">
			<label>
				<input type="checkbox" name="delete_data" value="1" <?php checked( true ); ?>/>
				<?php esc_html_e( 'Also remove the deleted field corresponding data.', 'buddypress' ); ?>
			</label>
			<p><strong><?php esc_html_e( 'This action cannot be undone.', 'buddypress' ); ?></strong></p>
			<p>
				<input type="hidden" name="page" value="bp-profile-setup" />
				<input type="hidden" name="mode" value="do_delete_field" />
				<input type="hidden" name="field_id" value="<?php echo esc_attr( $field_id ); ?>" />
				<?php
				wp_nonce_field( 'bp_xprofile_delete_field-' . $field_id, 'bp_xprofile_delete_field' );
				submit_button( __( 'Delete Permanently', 'buddypress' ), 'primary', '', false );
				?>
				<a class="button" href="<?php echo esc_attr( $base_url ); ?>"><?php esc_html_e( 'Cancel', 'buddypress' ); ?></a>
			</p>
		</form>
	</div>
	<?php
}



/**
 * Handles the ajax reordering of fields within a group.
 *
 * @since 1.0.0
 * @since 8.0.0 Returns a JSON object.
 */
function xprofile_ajax_reorder_fields() {
	// Check the nonce.
	check_admin_referer( 'bp_reorder_fields', '_wpnonce_reorder_fields' );

	if ( empty( $_POST['field_order'] ) ) {
		return wp_send_json_error();
	}

	$field_group_id = $_POST['field_group_id'];
	$group_tab      = '';

	if ( isset( $_POST['group_tab'] ) && $_POST['group_tab'] ) {
		$group_tab = wp_unslash( $_POST['group_tab'] );
	}

	if ( 'signup-fields' === $field_group_id ) {
		parse_str( $_POST['field_order'], $order );
		$fields = (array) $order['draggable_signup_field'];
		$fields = array_map( 'intval', $fields );

		if ( isset( $_POST['new_signup_field_id'] ) && $_POST['new_signup_field_id'] ) {
			parse_str( $_POST['new_signup_field_id'], $signup_field );
			$signup_fields = (array) $signup_field['draggable_signup_field'];
		}

		// Adding a new field to the registration form.
		if ( 'signup-group' === $group_tab ) {
			$field_id = (int) reset( $signup_fields );

			// Load the field.
			$field = xprofile_get_field( $field_id, null, false );

			if ( $field instanceof BP_XProfile_Field ) {
				// The field doesn't support the feature, stop right away!
				if ( ! $field->field_type_supports( 'signup_position' ) ) {
					wp_send_json_error(
						array(
							'message' => __( 'This field cannot be inserted into the registration form.', 'buddypress' ),
						)
					);
				}

				$signup_position = bp_xprofile_get_meta( $field->id, 'field', 'signup_position' );

				if ( ! $signup_position ) {
					$position = array_search( $field->id, $fields, true );
					if ( false !== $position ) {
						$position += 1;
					} else {
						$position = 1;
					}

					// Set the signup position.
					bp_xprofile_update_field_meta( $field->id, 'signup_position', $position );

					// Get the real Group object.
					$group = xprofile_get_field_group( $field->id );

					// Gets the HTML Output of the signup field.
					$signup_field = bp_xprofile_admin_get_signup_field( $field, $group );

					/**
					 * Fires once a signup field has been inserted.
					 *
					 * @since 8.0.0
					 */
					do_action( 'bp_xprofile_inserted_signup_field' );

					// Send the signup field to output.
					wp_send_json_success(
						array(
							'signup_field' => $signup_field,
							'field_id'     => $field->id,
						)
					);
				} else {
					wp_send_json_error(
						array(
							'message' => __( 'This field has been already added to the registration form.', 'buddypress' ),
						)
					);
				}

			} else {
				wp_send_json_error();
			}
		} else {
			// it's a sort operation.
			foreach ( $fields as $position => $field_id ) {
				bp_xprofile_update_field_meta( (int) $field_id, 'signup_position', (int) $position + 1 );
			}

			/**
			 * Fires once the signup fields have been reordered.
			 *
			 * @since 8.0.0
			 */
			do_action( 'bp_xprofile_reordered_signup_fields' );

			wp_send_json_success();
		}
	} else {
		/**
		 * @todo there's something going wrong here.
		 * moving a field to another tab when there's only the fullname field fails.
		 */
		parse_str( $_POST['field_order'], $order );
		$fields = (array) $order['draggable_field'];

		foreach ( $fields as $position => $field_id ) {
			xprofile_update_field_position( (int) $field_id, (int) $position, (int) $field_group_id );
		}

		wp_send_json_success();
	}
}
add_action( 'wp_ajax_xprofile_reorder_fields', 'xprofile_ajax_reorder_fields' );

/**
 * Removes a field from signup fields.
 *
 * @since 8.0.0
 */
function bp_xprofile_ajax_remove_signup_field() {
	// Check the nonce.
	check_admin_referer( 'bp_reorder_fields', '_wpnonce_reorder_fields' );

	if ( ! isset( $_POST['signup_field_id'] ) || ! $_POST['signup_field_id'] ) {
		return wp_send_json_error();
	}

	$signup_field_id = (int) wp_unslash( $_POST['signup_field_id'] );

	// Validate the field ID.
	$signup_position = bp_xprofile_get_meta( $signup_field_id, 'field', 'signup_position' );

	if ( ! $signup_position ) {
		wp_send_json_error();
	}

	bp_xprofile_delete_meta( $signup_field_id, 'field', 'signup_position' );

	/**
	 * Fires when a signup field is removed from the signup form.
	 *
	 * @since 8.0.0
	 */
	do_action( 'bp_xprofile_removed_signup_field' );

	wp_send_json_success();
}
add_action( 'wp_ajax_xprofile_remove_signup_field', 'bp_xprofile_ajax_remove_signup_field' );

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
 * @since 8.0.0 Adds the `$is_signup` parameter.
 *
 * @global BP_XProfile_Field $field The Admin field.
 *
 * @param BP_XProfile_Field   $admin_field Admin field.
 * @param object $admin_group Admin group object.
 * @param string $class       Classes to append to output.
 * @param bool   $is_signup   Whether the admin field output is made inside the signup group.
 */
function xprofile_admin_field( $admin_field, $admin_group, $class = '', $is_signup = false ) {
	global $field;

	$field       = $admin_field;
	$fieldset_id = sprintf( 'draggable_field_%d', $field->id );

	// Users admin URL.
	$url = bp_get_admin_url( 'users.php' );

	// Edit.
	$field_edit_url = add_query_arg( array(
		'page'     => 'bp-profile-setup',
		'mode'     => 'edit_field',
		'group_id' => (int) $field->group_id,
		'field_id' => (int) $field->id,
	), $url );

	// Delete.
	if ( $field->can_delete ) {
		$field_delete_url = add_query_arg( array(
			'page'     => 'bp-profile-setup',
			'mode'     => 'delete_field',
			'field_id' => (int) $field->id,
		), $url . '#tabs-' . (int) $field->group_id );
	}

	// Avoid duplicate IDs into the signup group.
	if ( $is_signup ) {
		$fieldset_id = sprintf( 'draggable_signup_field_%d', $field->id );
	}
	?>

	<fieldset id="<?php echo esc_attr( $fieldset_id ); ?>" class="sortable<?php echo ' ' . esc_attr( $field->type ); if ( ! empty( $class ) ) echo ' ' . esc_attr( $class ); ?>">
		<legend>
			<span>
				<?php bp_the_profile_field_name(); ?>

				<?php if ( empty( $field->can_delete ) ) : ?><?php esc_html_e( '(Primary)', 'buddypress' ); endif; ?>
				<?php bp_the_profile_field_required_label(); ?>
				<?php if ( $field->get_signup_position() ) : ?>
					<span class="bp-signup-field-label"><?php esc_html_e( '(Sign-up)', 'buddypress' );?></span>
				<?php endif; ?>
				<?php if ( bp_get_member_types() ) : echo wp_kses( $field->get_member_type_label(), array( 'span' => array( 'class' => true ) ) ); endif; ?>

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
				<a class="button edit" href="<?php echo esc_url( $field_edit_url ); ?>"><?php echo esc_html_x( 'Edit', 'Edit field link', 'buddypress' ); ?></a>

				<?php if ( $field->can_delete && ! $is_signup ) : ?>

					<div class="delete-button">
						<a class="confirm submit-delete deletion" href="<?php echo esc_url( wp_nonce_url( $field_delete_url, 'bp_xprofile_delete_field-' . $field->id, 'bp_xprofile_delete_field' ) ); ?>"><?php echo esc_html_x( 'Delete', 'Delete field link', 'buddypress' ); ?></a>
					</div>

				<?php endif; ?>

				<?php if ( $field->can_delete && $is_signup ) : ?>

					<div class="delete-button">
						<a class="submit-delete removal" href="<?php echo esc_attr( sprintf( '#remove_field-%d', $field->id ) ); ?>"><?php echo esc_html_x( 'Remove', 'Remove field link', 'buddypress' ); ?></a>
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
 * Handles the WYSIWYG display of signup profile fields on the edit screen.
 *
 * @since 8.0.0
 *
 * @param BP_XProfile_Field   $signup_field The field to use into the signup form.
 * @param object $field_group The real field group object.
 * @param string $class       Classes to append to output.
 * @param bool   $echo        Whether to return or display the HTML output.
 * @return string The HTML output.
 */
function bp_xprofile_admin_get_signup_field( $signup_field, $field_group = null, $class = '', $echo = false ) {
	add_filter( 'bp_get_the_profile_field_input_name', 'bp_get_the_profile_signup_field_input_name' );

	if ( ! $echo ) {
		// Set up an output buffer.
		ob_start();
		xprofile_admin_field( $signup_field, $field_group, $class, true );
		$output = ob_get_contents();
		ob_end_clean();
	} else {
		xprofile_admin_field( $signup_field, $field_group, $class, true );
	}

	remove_filter( 'bp_get_the_profile_field_input_name', 'bp_get_the_profile_signup_field_input_name' );

	if ( ! $echo ) {
		return $output;
	}
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

	if ( empty( $select_field_type ) ) {
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

		if ( isset( $categories[ $the_category ] ) ) {
			$categories[ $the_category ][] = array( $field_name, $field_type_obj );
		} else {
			$categories[ $the_category ] = array( array( $field_name, $field_type_obj ) );
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
