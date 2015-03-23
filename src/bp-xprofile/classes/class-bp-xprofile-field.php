<?php
/**
 * BuddyPress XProfile Classes
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

class BP_XProfile_Field {
	public $id;
	public $group_id;
	public $parent_id;
	public $type;
	public $name;
	public $description;
	public $is_required;
	public $can_delete = '1';
	public $field_order;
	public $option_order;
	public $order_by;
	public $is_default_option;
	public $default_visibility = 'public';
	public $allow_custom_visibility = 'allowed';

	/**
	 * @since BuddyPress (2.0.0)
	 * @var BP_XProfile_Field_Type Field type object used for validation
	 */
	public $type_obj = null;

	public $data;
	public $message = null;
	public $message_type = 'err';

	public function __construct( $id = null, $user_id = null, $get_data = true ) {
		if ( !empty( $id ) ) {
			$this->populate( $id, $user_id, $get_data );

		// Initialise the type obj to prevent fatals when creating new profile fields
		} else {
			$this->type_obj            = bp_xprofile_create_field_type( 'textbox' );
			$this->type_obj->field_obj = $this;
		}
	}

	public function populate( $id, $user_id, $get_data ) {
		global $wpdb, $userdata;

		if ( empty( $user_id ) ) {
			$user_id = isset( $userdata->ID ) ? $userdata->ID : 0;
		}

		$bp  = buddypress();
		$sql = $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE id = %d", $id );

		if ( $field = $wpdb->get_row( $sql ) ) {
			$this->id               = $field->id;
			$this->group_id          = $field->group_id;
			$this->parent_id         = $field->parent_id;
			$this->type              = $field->type;
			$this->name              = stripslashes( $field->name );
			$this->description       = stripslashes( $field->description );
			$this->is_required       = $field->is_required;
			$this->can_delete        = $field->can_delete;
			$this->field_order       = $field->field_order;
			$this->option_order      = $field->option_order;
			$this->order_by          = $field->order_by;
			$this->is_default_option = $field->is_default_option;

			// Create the field type and store a reference back to this object.
			$this->type_obj            = bp_xprofile_create_field_type( $field->type );
			$this->type_obj->field_obj = $this;

			if ( $get_data && $user_id ) {
				$this->data          = $this->get_field_data( $user_id );
			}

			$this->default_visibility = bp_xprofile_get_meta( $id, 'field', 'default_visibility' );

			if ( empty( $this->default_visibility ) ) {
				$this->default_visibility = 'public';
			}

			$this->allow_custom_visibility = 'disabled' == bp_xprofile_get_meta( $id, 'field', 'allow_custom_visibility' ) ? 'disabled' : 'allowed';
		}
	}

	public function delete( $delete_data = false ) {
		global $wpdb;

		// Prevent deletion if no ID is present
		// Prevent deletion by url when can_delete is false.
		// Prevent deletion of option 1 since this invalidates fields with options.
		if ( empty( $this->id ) || empty( $this->can_delete ) || ( $this->parent_id && $this->option_order == 1 ) )
			return false;

		$bp = buddypress();

		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_fields} WHERE id = %d OR parent_id = %d", $this->id, $this->id ) ) )
			return false;

		// delete the data in the DB for this field
		if ( true === $delete_data )
			BP_XProfile_ProfileData::delete_for_field( $this->id );

		return true;
	}

	public function save() {
		global $wpdb;

		$bp = buddypress();

		$this->group_id    = apply_filters( 'xprofile_field_group_id_before_save',    $this->group_id,    $this->id );
		$this->parent_id   = apply_filters( 'xprofile_field_parent_id_before_save',   $this->parent_id,   $this->id );
		$this->type        = apply_filters( 'xprofile_field_type_before_save',        $this->type,        $this->id );
		$this->name        = apply_filters( 'xprofile_field_name_before_save',        $this->name,        $this->id );
		$this->description = apply_filters( 'xprofile_field_description_before_save', $this->description, $this->id );
		$this->is_required = apply_filters( 'xprofile_field_is_required_before_save', $this->is_required, $this->id );
		$this->order_by	   = apply_filters( 'xprofile_field_order_by_before_save',    $this->order_by,    $this->id );
		$this->field_order = apply_filters( 'xprofile_field_field_order_before_save', $this->field_order, $this->id );
		$this->option_order = apply_filters( 'xprofile_field_option_order_before_save', $this->option_order, $this->id );
		$this->can_delete  = apply_filters( 'xprofile_field_can_delete_before_save',  $this->can_delete,  $this->id );
		$this->type_obj    = bp_xprofile_create_field_type( $this->type );

		/**
		 * Fires before the current field instance gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyPress (1.0.0)
		 *
		 * @param BP_XProfile_Field Current instance of the field being saved.
		 */
		do_action_ref_array( 'xprofile_field_before_save', array( $this ) );

		if ( $this->id != null ) {
			$sql = $wpdb->prepare( "UPDATE {$bp->profile->table_name_fields} SET group_id = %d, parent_id = 0, type = %s, name = %s, description = %s, is_required = %d, order_by = %s, field_order = %d, option_order = %d, can_delete = %d WHERE id = %d", $this->group_id, $this->type, $this->name, $this->description, $this->is_required, $this->order_by, $this->field_order, $this->option_order, $this->can_delete, $this->id );
		} else {
			$sql = $wpdb->prepare( "INSERT INTO {$bp->profile->table_name_fields} (group_id, parent_id, type, name, description, is_required, order_by, field_order, option_order, can_delete ) VALUES (%d, %d, %s, %s, %s, %d, %s, %d, %d, %d )", $this->group_id, $this->parent_id, $this->type, $this->name, $this->description, $this->is_required, $this->order_by, $this->field_order, $this->option_order, $this->can_delete );
		}

		/**
		 * Check for null so field options can be changed without changing any other part of the field.
		 * The described situation will return 0 here.
		 */
		if ( $wpdb->query( $sql ) !== null ) {

			if ( !empty( $this->id ) ) {
				$field_id = $this->id;
			} else {
				$field_id = $wpdb->insert_id;
			}

			// Only do this if we are editing an existing field
			if ( $this->id != null ) {

				/**
				 * Remove any radio or dropdown options for this
				 * field. They will be re-added if needed.
				 * This stops orphan options if the user changes a
				 * field from a radio button field to a text box.
				 */
				$this->delete_children();
			}

			/**
			 * Check to see if this is a field with child options.
			 * We need to add the options to the db, if it is.
			 */
			if ( $this->type_obj->supports_options ) {

				if ( !empty( $this->id ) ) {
					$parent_id = $this->id;
				} else {
					$parent_id = $wpdb->insert_id;
				}

				// Allow plugins to filter the field's child options (i.e. the items in a selectbox).
				$post_option  = ! empty( $_POST["{$this->type}_option"] ) ? $_POST["{$this->type}_option"] : '';
				$post_default = ! empty( $_POST["isDefault_{$this->type}_option"] ) ? $_POST["isDefault_{$this->type}_option"] : '';

				/**
				 * Filters the submitted field option value before saved.
				 *
				 * @since BuddyPress (1.5.0)
				 *
				 * @param string            $post_option Submitted option value.
				 * @param BP_XProfile_Field $type        Current field type being saved for.
				 */
				$options      = apply_filters( 'xprofile_field_options_before_save', $post_option,  $this->type );

				/**
				 * Filters the default field option value before saved.
				 *
				 * @since BuddyPress (1.5.0)
				 *
				 * @param string            $post_default Default option value.
				 * @param BP_XProfile_Field $type         Current field type being saved for.
				 */
				$defaults     = apply_filters( 'xprofile_field_default_before_save', $post_default, $this->type );

				$counter = 1;
				if ( !empty( $options ) ) {
					foreach ( (array) $options as $option_key => $option_value ) {
						$is_default = 0;

						if ( is_array( $defaults ) ) {
							if ( isset( $defaults[$option_key] ) )
								$is_default = 1;
						} else {
							if ( (int) $defaults == $option_key )
								$is_default = 1;
						}

						if ( '' != $option_value ) {
							if ( !$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->profile->table_name_fields} (group_id, parent_id, type, name, description, is_required, option_order, is_default_option) VALUES (%d, %d, 'option', %s, '', 0, %d, %d)", $this->group_id, $parent_id, $option_value, $counter, $is_default ) ) ) {
								return false;
							}
						}

						$counter++;
					}
				}
			}

			/**
			 * Fires after the current field instance gets saved.
			 *
			 * @since BuddyPress (1.0.0)
			 *
			 * @param BP_XProfile_Field Current instance of the field being saved.
			 */
			do_action_ref_array( 'xprofile_field_after_save', array( $this ) );

			// Recreate type_obj in case someone changed $this->type via a filter
	 		$this->type_obj            = bp_xprofile_create_field_type( $this->type );
	 		$this->type_obj->field_obj = $this;

			return $field_id;
		} else {
			return false;
		}
	}

	public function get_field_data( $user_id ) {
		return new BP_XProfile_ProfileData( $this->id, $user_id );
	}

	public function get_children( $for_editing = false ) {
		global $wpdb;

		// This is done here so we don't have problems with sql injection
		if ( 'asc' == $this->order_by && empty( $for_editing ) ) {
			$sort_sql = 'ORDER BY name ASC';
		} elseif ( 'desc' == $this->order_by && empty( $for_editing ) ) {
			$sort_sql = 'ORDER BY name DESC';
		} else {
			$sort_sql = 'ORDER BY option_order ASC';
		}

		// This eliminates a problem with getting all fields when there is no id for the object
		if ( empty( $this->id ) ) {
			$parent_id = -1;
		} else {
			$parent_id = $this->id;
		}

		$bp  = buddypress();
		$sql = $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE parent_id = %d AND group_id = %d $sort_sql", $parent_id, $this->group_id );

		$children = $wpdb->get_results( $sql );

		/**
		 * Filters the found children for a field.
		 *
		 * @since BuddyPress (1.2.5)
		 *
		 * @param object $children    Found children for a field.
		 * @param bool   $for_editing Whether or not the field is for editing.
		 */
		return apply_filters( 'bp_xprofile_field_get_children', $children, $for_editing );
	}

	public function delete_children() {
		global $wpdb;

		$bp  = buddypress();
		$sql = $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_fields} WHERE parent_id = %d", $this->id );

		$wpdb->query( $sql );
	}

	/** Static Methods ********************************************************/

	public static function get_type( $field_id ) {
		global $wpdb;

		if ( !empty( $field_id ) ) {
			$bp  = buddypress();
			$sql = $wpdb->prepare( "SELECT type FROM {$bp->profile->table_name_fields} WHERE id = %d", $field_id );

			if ( !$field_type = $wpdb->get_var( $sql ) ) {
				return false;
			}

			return $field_type;
		}

		return false;
	}

	public static function delete_for_group( $group_id ) {
		global $wpdb;

		if ( !empty( $group_id ) ) {
			$bp  = buddypress();
			$sql = $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_fields} WHERE group_id = %d", $group_id );

			if ( $wpdb->get_var( $sql ) === false ) {
				return false;
			}

			return true;
		}

		return false;
	}

	public static function get_id_from_name( $field_name ) {
		global $wpdb;

		$bp = buddypress();

		if ( empty( $bp->profile->table_name_fields ) || !isset( $field_name ) )
			return false;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_fields} WHERE name = %s AND parent_id = 0", $field_name ) );
	}

	public static function update_position( $field_id, $position, $field_group_id ) {
		global $wpdb;

		if ( !is_numeric( $position ) || !is_numeric( $field_group_id ) )
			return false;

		$bp = buddypress();

		// Update $field_id with new $position and $field_group_id
		if ( $parent = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_fields} SET field_order = %d, group_id = %d WHERE id = %d", $position, $field_group_id, $field_id ) ) ) {;

			// Update any children of this $field_id
			$children = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_fields} SET group_id = %d WHERE parent_id = %d", $field_group_id, $field_id ) );

			return $parent;
		}

		return false;
	}

	/**
	 * This function populates the items for radio buttons checkboxes and drop down boxes
	 */
	public function render_admin_form_children() {
		foreach ( array_keys( bp_xprofile_get_field_types() ) as $field_type ) {
			$type_obj = bp_xprofile_create_field_type( $field_type );
			$type_obj->admin_new_field_html( $this );
		}
	}

	public function render_admin_form( $message = '' ) {
		if ( empty( $this->id ) ) {
			$title  = __( 'Add New Field', 'buddypress' );
			$action	= "users.php?page=bp-profile-setup&amp;group_id=" . $this->group_id . "&amp;mode=add_field#tabs-" . $this->group_id;
			$button	= __( 'Save', 'buddypress' );

			if ( !empty( $_POST['saveField'] ) ) {
				$this->name        = $_POST['title'];
				$this->description = $_POST['description'];
				$this->is_required = $_POST['required'];
				$this->type        = $_POST['fieldtype'];
				$this->order_by    = $_POST["sort_order_{$this->type}"];
				$this->field_order = $_POST['field_order'];
			}
		} else {
			$title  = __( 'Edit Field', 'buddypress' );
			$action = "users.php?page=bp-profile-setup&amp;mode=edit_field&amp;group_id=" . $this->group_id . "&amp;field_id=" . $this->id . "#tabs-" . $this->group_id;
			$button	= __( 'Update', 'buddypress' );
		} ?>

		<div class="wrap">

			<?php screen_icon( 'users' ); ?>

			<h2><?php echo esc_html( $title ); ?></h2>

			<?php if ( !empty( $message ) ) : ?>

				<div id="message" class="error fade">
					<p><?php echo esc_html( $message ); ?></p>
				</div>

			<?php endif; ?>

			<form id="bp-xprofile-add-field" action="<?php echo esc_url( $action ); ?>" method="post">
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-<?php echo ( 1 == get_current_screen()->get_columns() ) ? '1' : '2'; ?>">
						<div id="post-body-content">
							<div id="titlediv">
								<div class="titlewrap">
									<label id="title-prompt-text" for="title"><?php echo esc_attr_x( 'Field Name', 'XProfile admin edit field', 'buddypress' ); ?></label>
									<input type="text" name="title" id="title" value="<?php echo esc_attr( $this->name ); ?>" autocomplete="off" />
								</div>
							</div>
							<div class="postbox">
								<h3><?php _e( 'Field Description', 'buddypress' ); ?></h3>
								<div class="inside">
									<textarea name="description" id="description" rows="8" cols="60"><?php echo esc_textarea( $this->description ); ?></textarea>
								</div>
							</div>
						</div><!-- #post-body-content -->

						<div id="postbox-container-1" class="postbox-container">

							<?php

							/**
							 * Fires before XProfile Field submit metabox.
							 *
							 * @since BuddyPress (2.1.0)
							 *
							 * @param BP_XProfile_Field $this Current XProfile field.
							 */
							do_action( 'xprofile_field_before_submitbox', $this );
							?>

							<div id="submitdiv" class="postbox">
								<h3><?php _e( 'Submit', 'buddypress' ); ?></h3>
								<div class="inside">
									<div id="submitcomment" class="submitbox">
										<div id="major-publishing-actions">

											<?php

											/**
											 * Fires at the beginning of the XProfile Field publishing actions section.
											 *
											 * @since BuddyPress (2.1.0)
											 *
											 * @param BP_XProfile_Field $this Current XProfile field.
											 */
											do_action( 'xprofile_field_submitbox_start', $this );
											?>

											<input type="hidden" name="field_order" id="field_order" value="<?php echo esc_attr( $this->field_order ); ?>" />
											<div id="publishing-action">
												<input type="submit" name="saveField" value="<?php echo esc_attr( $button ); ?>" class="button-primary" />
											</div>
											<div id="delete-action">
												<a href="users.php?page=bp-profile-setup" class="deletion"><?php _e( 'Cancel', 'buddypress' ); ?></a>
											</div>
											<?php wp_nonce_field( 'xprofile_delete_option' ); ?>
											<div class="clear"></div>
										</div>
									</div>
								</div>
							</div>

							<?php

							/**
							 * Fires after XProfile Field submit metabox.
							 *
							 * @since BuddyPress (2.1.0)
							 *
							 * @param BP_XProfile_Field $this Current XProfile field.
							 */
							do_action( 'xprofile_field_after_submitbox', $this );
							?>

							<?php /* Field 1 is the fullname field, which cannot have custom visibility */ ?>
							<?php if ( 1 != $this->id ) : ?>

								<div class="postbox">
									<h3><label for="default-visibility"><?php _e( 'Default Visibility', 'buddypress' ); ?></label></h3>
									<div class="inside">
										<ul>

											<?php foreach( bp_xprofile_get_visibility_levels() as $level ) : ?>

												<li>
													<input type="radio" id="default-visibility[<?php echo esc_attr( $level['id'] ) ?>]" name="default-visibility" value="<?php echo esc_attr( $level['id'] ) ?>" <?php checked( $this->default_visibility, $level['id'] ); ?> />
													<label for="default-visibility[<?php echo esc_attr( $level['id'] ) ?>]"><?php echo esc_html( $level['label'] ) ?></label>
												</li>

											<?php endforeach ?>

										</ul>
									</div>
								</div>

								<div class="postbox">
									<h3><label for="allow-custom-visibility"><?php _e( 'Per-Member Visibility', 'buddypress' ); ?></label></h3>
									<div class="inside">
										<ul>
											<li>
												<input type="radio" id="allow-custom-visibility-allowed" name="allow-custom-visibility" value="allowed" <?php checked( $this->allow_custom_visibility, 'allowed' ); ?> />
												<label for="allow-custom-visibility-allowed"><?php _e( "Let members change this field's visibility", 'buddypress' ); ?></label>
											</li>
											<li>
												<input type="radio" id="allow-custom-visibility-disabled" name="allow-custom-visibility" value="disabled" <?php checked( $this->allow_custom_visibility, 'disabled' ); ?> />
												<label for="allow-custom-visibility-disabled"><?php _e( 'Enforce the default visibility for all members', 'buddypress' ); ?></label>
											</li>
										</ul>
									</div>
								</div>

							<?php endif ?>

							<?php

							/**
							 * Fires after XProfile Field sidebar metabox.
							 *
							 * @since BuddyPress (2.2.0)
							 *
							 * @param BP_XProfile_Field $this Current XProfile field.
							 */
							do_action( 'xprofile_field_after_sidebarbox', $this ); ?>
						</div>

						<div id="postbox-container-2" class="postbox-container">

							<?php /* Field 1 is the fullname field, which cannot be altered */ ?>
							<?php if ( 1 != $this->id ) : ?>

								<div class="postbox">
									<h3><label for="required"><?php _e( "Field Requirement", 'buddypress' ); ?></label></h3>
									<div class="inside">
										<select name="required" id="required" style="width: 30%">
											<option value="0"<?php selected( $this->is_required, '0' ); ?>><?php _e( 'Not Required', 'buddypress' ); ?></option>
											<option value="1"<?php selected( $this->is_required, '1' ); ?>><?php _e( 'Required',     'buddypress' ); ?></option>
										</select>
									</div>
								</div>

								<div class="postbox">
									<h3><label for="fieldtype"><?php _e( 'Field Type', 'buddypress'); ?></label></h3>
									<div class="inside">
										<select name="fieldtype" id="fieldtype" onchange="show_options(this.value)" style="width: 30%">
											<?php bp_xprofile_admin_form_field_types( $this->type ); ?>
										</select>

										<?php
										// Deprecated filter, don't use. Go look at {@link BP_XProfile_Field_Type::admin_new_field_html()}.
										do_action( 'xprofile_field_additional_options', $this );

										$this->render_admin_form_children();
										?>
									</div>
								</div>

							<?php else : ?>

								<input type="hidden" name="required"  id="required"  value="1"       />
								<input type="hidden" name="fieldtype" id="fieldtype" value="textbox" />

							<?php endif; ?>

							<?php

							/**
							 * Fires after XProfile Field content metabox.
							 *
							 * @since BuddyPress (2.2.0)
							 *
							 * @param BP_XProfile_Field $this Current XProfile field.
							 */
							do_action( 'xprofile_field_after_contentbox', $this ); ?>
						</div>
					</div><!-- #post-body -->

				</div><!-- #poststuff -->

			</form>
		</div>

<?php
	}

	public static function admin_validate() {
		global $message;

		// Validate Form
		if ( '' == $_POST['title'] || '' == $_POST['required'] || '' == $_POST['fieldtype'] ) {
			$message = __( 'Please make sure you fill out all required fields.', 'buddypress' );
			return false;

		} elseif ( empty( $_POST['field_file'] ) ) {
			$field_type  = bp_xprofile_create_field_type( $_POST['fieldtype'] );
			$option_name = "{$_POST['fieldtype']}_option";

			if ( $field_type->supports_options && isset( $_POST[$option_name] ) && empty( $_POST[$option_name][1] ) ) {
				$message = __( 'This field type require at least one option. Please add options below.', 'buddypress' );
				return false;
			}
		}

		return true;
	}
}
