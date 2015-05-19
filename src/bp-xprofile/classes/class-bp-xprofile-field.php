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

	/**
	 * @since BuddyPress (1.0.0)
	 *
	 * @var int ID of field
	 */
	public $id;

	/**
	 * @since BuddyPress (1.0.0)
	 *
	 * @var int Field group ID for field
	 */
	public $group_id;

	/**
	 * @since BuddyPress (1.0.0)
	 *
	 * @var int Parent ID of field
	 */
	public $parent_id;

	/**
	 * @since BuddyPress (1.0.0)
	 *
	 * @var string Field type
	 */
	public $type;

	/**
	 * @since BuddyPress (1.0.0)
	 *
	 * @var string Field name
	 */
	public $name;

	/**
	 * @since BuddyPress (1.0.0)
	 *
	 * @var string Field description
	 */
	public $description;

	/**
	 * @since BuddyPress (1.0.0)
	 *
	 * @var bool Is field required to be filled out?
	 */
	public $is_required;

	/**
	 * @since BuddyPress (1.0.0)
	 *
	 * @var int Can field be deleted?
	 */
	public $can_delete = '1';

	/**
	 * @since BuddyPress (1.0.0)
	 *
	 * @var int Field position
	 */
	public $field_order;

	/**
	 * @since BuddyPress (1.0.0)
	 *
	 * @var int Option order
	 */
	public $option_order;

	/**
	 * @since BuddyPress (1.0.0)
	 *
	 * @var string Order child fields by
	 */
	public $order_by;

	/**
	 * @since BuddyPress (1.0.0)
	 *
	 * @var bool Is this the default option for this field?
	 */
	public $is_default_option;

	/**
	 * @since BuddyPress (1.9.0)
	 *
	 * @var string Default field data visibility
	 */
	public $default_visibility = 'public';

	/**
	 * @since BuddyPress (2.3.0)
	 *
	 * @var string Members are allowed/disallowed to modify data visibility
	 */
	public $allow_custom_visibility = 'allowed';

	/**
	 * @since BuddyPress (2.0.0)
	 *
	 * @var BP_XProfile_Field_Type Field type object used for validation
	 */
	public $type_obj = null;

	/**
	 * @since BuddyPress (2.0.0)
	 *
	 * @var BP_XProfile_ProfileData Field data for user ID
	 */
	public $data;

	/**
	 * Initialize and/or populate profile field
	 *
	 * @since BuddyPress (1.1.0)
	 *
	 * @param int  $id
	 * @param int  $user_id
	 * @param bool $get_data
	 */
	public function __construct( $id = null, $user_id = null, $get_data = true ) {

		if ( ! empty( $id ) ) {
			$this->populate( $id, $user_id, $get_data );

		// Initialise the type obj to prevent fatals when creating new profile fields
		} else {
			$this->type_obj            = bp_xprofile_create_field_type( 'textbox' );
			$this->type_obj->field_obj = $this;
		}
	}

	/**
	 * Populate a profile field object
	 *
	 * @since BuddyPress (1.1.0)
	 *
	 * @global object $wpdb
	 * @global object $userdata
	 *
	 * @param  int    $id
	 * @param  int    $user_id
	 * @param  bool   $get_data
	 */
	public function populate( $id, $user_id = null, $get_data = true ) {
		global $wpdb, $userdata;

		if ( empty( $user_id ) ) {
			$user_id = isset( $userdata->ID ) ? $userdata->ID : 0;
		}

		$bp    = buddypress();
		$field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE id = %d", $id ) );

		if ( ! empty( $field ) ) {
			$this->id                = $field->id;
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

			if ( ! empty( $get_data ) && ! empty( $user_id ) ) {
				$this->data = $this->get_field_data( $user_id );
			}

			// Get metadata for field
			$default_visibility       = bp_xprofile_get_meta( $id, 'field', 'default_visibility'      );
			$allow_custom_visibility  = bp_xprofile_get_meta( $id, 'field', 'allow_custom_visibility' );

			// Setup default visibility
			$this->default_visibility = ! empty( $default_visibility )
				? $default_visibility
				: 'public';

			// Allow members to customize visibilty
			$this->allow_custom_visibility = ( 'disabled' === $allow_custom_visibility )
				? 'disabled'
				: 'allowed';
		}
	}

	/**
	 * Delete a profile field
	 *
	 * @since BuddyPress (1.1.0)
	 *
	 * @global object  $wpdb
	 * @param  boolean $delete_data
	 * @return boolean
	 */
	public function delete( $delete_data = false ) {
		global $wpdb;

		// Prevent deletion if no ID is present
		// Prevent deletion by url when can_delete is false.
		// Prevent deletion of option 1 since this invalidates fields with options.
		if ( empty( $this->id ) || empty( $this->can_delete ) || ( $this->parent_id && $this->option_order == 1 ) ) {
			return false;
		}

		$bp  = buddypress();
		$sql = $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_fields} WHERE id = %d OR parent_id = %d", $this->id, $this->id );

		if ( ! $wpdb->query( $sql ) ) {
			return false;
		}

		// delete the data in the DB for this field
		if ( true === $delete_data ) {
			BP_XProfile_ProfileData::delete_for_field( $this->id );
		}

		return true;
	}

	/**
	 * Save a profile field
	 *
	 * @since BuddyPress (1.1.0)
	 *
	 * @global object $wpdb
	 *
	 * @return boolean
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		$this->group_id     = apply_filters( 'xprofile_field_group_id_before_save',     $this->group_id,     $this->id );
		$this->parent_id    = apply_filters( 'xprofile_field_parent_id_before_save',    $this->parent_id,    $this->id );
		$this->type         = apply_filters( 'xprofile_field_type_before_save',         $this->type,         $this->id );
		$this->name         = apply_filters( 'xprofile_field_name_before_save',         $this->name,         $this->id );
		$this->description  = apply_filters( 'xprofile_field_description_before_save',  $this->description,  $this->id );
		$this->is_required  = apply_filters( 'xprofile_field_is_required_before_save',  $this->is_required,  $this->id );
		$this->order_by	    = apply_filters( 'xprofile_field_order_by_before_save',     $this->order_by,     $this->id );
		$this->field_order  = apply_filters( 'xprofile_field_field_order_before_save',  $this->field_order,  $this->id );
		$this->option_order = apply_filters( 'xprofile_field_option_order_before_save', $this->option_order, $this->id );
		$this->can_delete   = apply_filters( 'xprofile_field_can_delete_before_save',   $this->can_delete,   $this->id );
		$this->type_obj     = bp_xprofile_create_field_type( $this->type );

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
			$sql = $wpdb->prepare( "UPDATE {$bp->profile->table_name_fields} SET group_id = %d, parent_id = 0, type = %s, name = %s, description = %s, is_required = %d, order_by = %s, field_order = %d, option_order = %d, can_delete = %d, is_default_option = %d WHERE id = %d", $this->group_id, $this->type, $this->name, $this->description, $this->is_required, $this->order_by, $this->field_order, $this->option_order, $this->can_delete, $this->is_default_option, $this->id );
		} else {
			$sql = $wpdb->prepare( "INSERT INTO {$bp->profile->table_name_fields} (group_id, parent_id, type, name, description, is_required, order_by, field_order, option_order, can_delete, is_default_option ) VALUES ( %d, %d, %s, %s, %s, %d, %s, %d, %d, %d, %d )", $this->group_id, $this->parent_id, $this->type, $this->name, $this->description, $this->is_required, $this->order_by, $this->field_order, $this->option_order, $this->can_delete, $this->is_default_option );
		}

		/**
		 * Check for null so field options can be changed without changing any
		 * other part of the field. The described situation will return 0 here.
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
				$post_option  = ! empty( $_POST["{$this->type}_option"]           ) ? $_POST["{$this->type}_option"]           : '';
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
							if ( isset( $defaults[ $option_key ] ) ) {
								$is_default = 1;
							}
						} else {
							if ( (int) $defaults == $option_key ) {
								$is_default = 1;
							}
						}

						if ( '' != $option_value ) {
							$sql = $wpdb->prepare( "INSERT INTO {$bp->profile->table_name_fields} (group_id, parent_id, type, name, description, is_required, option_order, is_default_option) VALUES (%d, %d, 'option', %s, '', 0, %d, %d)", $this->group_id, $parent_id, $option_value, $counter, $is_default );
							if ( ! $wpdb->query( $sql ) ) {
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

	/**
	 * Get field data for a user ID
	 *
	 * @since BuddyPress (1.2.0)
	 *
	 * @param  int $user_id
	 * @return object
	 */
	public function get_field_data( $user_id = 0 ) {
		return new BP_XProfile_ProfileData( $this->id, $user_id );
	}

	/**
	 * Get all child fields for this field ID
	 *
	 * @since BuddyPress (1.2.0)
	 *
	 * @global object $wpdb
	 *
	 * @param  bool  $for_editing
	 * @return array
	 */
	public function get_children( $for_editing = false ) {
		global $wpdb;

		// This is done here so we don't have problems with sql injection
		if ( empty( $for_editing ) && ( 'asc' === $this->order_by ) ) {
			$sort_sql = 'ORDER BY name ASC';
		} elseif ( empty( $for_editing ) && ( 'desc' === $this->order_by ) ) {
			$sort_sql = 'ORDER BY name DESC';
		} else {
			$sort_sql = 'ORDER BY option_order ASC';
		}

		// This eliminates a problem with getting all fields when there is no
		// id for the object
		if ( empty( $this->id ) ) {
			$parent_id = -1;
		} else {
			$parent_id = $this->id;
		}

		$bp  = buddypress();
		$sql = $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE parent_id = %d AND group_id = %d {$sort_sql}", $parent_id, $this->group_id );

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

	/**
	 * Delete all field children for this field
	 *
	 * @since BuddyPress (1.2.0)
	 *
	 * @global object $wpdb
	 */
	public function delete_children() {
		global $wpdb;

		$bp  = buddypress();
		$sql = $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_fields} WHERE parent_id = %d", $this->id );

		$wpdb->query( $sql );
	}

	/** Static Methods ********************************************************/

	public static function get_type( $field_id = 0 ) {
		global $wpdb;

		// Bail if no field ID
		if ( empty( $field_id ) ) {
			return false;
		}

		$bp   = buddypress();
		$sql  = $wpdb->prepare( "SELECT type FROM {$bp->profile->table_name_fields} WHERE id = %d", $field_id );
		$type = $wpdb->get_var( $sql );

		// Return field type
		if ( ! empty( $type ) ) {
			return $type;
		}

		return false;
	}

	/**
	 * Delete all fields in a field group
	 *
	 * @since BuddyPress (1.2.0)
	 *
	 * @global object $wpdb
	 *
	 * @param  int    $group_id
	 *
	 * @return boolean
	 */
	public static function delete_for_group( $group_id = 0 ) {
		global $wpdb;

		// Bail if no group ID
		if ( empty( $group_id ) ) {
			return false;
		}

		$bp      = buddypress();
		$sql     = $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_fields} WHERE group_id = %d", $group_id );
		$deleted = $wpdb->get_var( $sql );

		// Return true if fields were deleted
		if ( false !== $deleted ) {
			return true;
		}

		return false;
	}

	/**
	 * Get field ID from field name
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @global object $wpdb
	 * @param  string $field_name
	 *
	 * @return boolean
	 */
	public static function get_id_from_name( $field_name = '' ) {
		global $wpdb;

		$bp = buddypress();

		if ( empty( $bp->profile->table_name_fields ) || empty( $field_name ) ) {
			return false;
		}

		$sql = $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_fields} WHERE name = %s AND parent_id = 0", $field_name );

		return $wpdb->get_var( $sql );
	}

	/**
	 * Update field position and/or field group when relocating
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @global object $wpdb
	 *
	 * @param  int $field_id
	 * @param  int $position
	 * @param  int $field_group_id
	 *
	 * @return boolean
	 */
	public static function update_position( $field_id, $position = null, $field_group_id = null ) {
		global $wpdb;

		// Bail if invalid position or field group
		if ( ! is_numeric( $position ) || ! is_numeric( $field_group_id ) ) {
			return false;
		}

		// Get table name and field parent
		$table_name = buddypress()->profile->table_name_fields;
		$sql        = $wpdb->prepare( "UPDATE {$table_name} SET field_order = %d, group_id = %d WHERE id = %d", $position, $field_group_id, $field_id );
		$parent     = $wpdb->query( $sql );

		// Update $field_id with new $position and $field_group_id
		if ( ! empty( $parent ) && ! is_wp_error( $parent ) ) {

			// Update any children of this $field_id
			$sql = $wpdb->prepare( "UPDATE {$table_name} SET group_id = %d WHERE parent_id = %d", $field_group_id, $field_id );
			$wpdb->query( $sql );

			return $parent;
		}

		return false;
	}

	/**
	 * Validate form field data on sumbission
	 *
	 * @since BuddyPress (2.2.0)
	 *
	 * @global type $message
	 * @return boolean
	 */
	public static function admin_validate() {
		global $message;

		// Check field name
		if ( ! isset( $_POST['title'] ) || ( '' === $_POST['title'] ) ) {
			$message = esc_html__( 'Profile fields must have a name.', 'buddypress' );
			return false;
		}

		// Check field requirement
		if ( ! isset( $_POST['required'] ) ) {
			$message = esc_html__( 'Profile field requirement is missing.', 'buddypress' );
			return false;
		}

		// Check field type
		if ( empty( $_POST['fieldtype'] ) ) {
			$message = esc_html__( 'Profile field type is missing.', 'buddypress' );
			return false;
		}

		// Check that field is of valid type
		if ( ! in_array( $_POST['fieldtype'], array_keys( bp_xprofile_get_field_types() ), true ) ) {
			$message = sprintf( esc_html__( 'The profile field type %s is not registered.', 'buddypress' ), '<code>' . esc_attr( $_POST['fieldtype'] ) . '</code>' );
			return false;
		}

		// Get field type so we can check for and lavidate any field options
		$field_type = bp_xprofile_create_field_type( $_POST['fieldtype'] );

		// Field type requires options
		if ( true === $field_type->supports_options ) {

			// Build the field option key
			$option_name = sanitize_key( $_POST['fieldtype'] ) . '_option';

			// Check for missing or malformed options
			if ( empty( $_POST[ $option_name ] ) || ! is_array( $_POST[ $option_name ] ) ) {
				$message = esc_html__( 'These field options are invalid.', 'buddypress' );
				return false;
			}

			// Trim out empty field options
			$field_values  = array_values( $_POST[ $option_name ] );
			$field_options = array_map( 'sanitize_text_field', $field_values );
			$field_count   = count( $field_options );

			// Check for missing or malformed options
			if ( 0 === $field_count ) {
				$message = sprintf( esc_html__( '%s require at least one option.', 'buddypress' ), $field_type->name );
				return false;
			}

			// If only one option exists, it cannot be an empty string
			if ( ( 1 === $field_count ) && ( '' === $field_options[0] ) ) {
				$message = sprintf( esc_html__( '%s require at least one option.', 'buddypress' ), $field_type->name );
				return false;
			}
		}

		return true;
	}

	/**
	 * This function populates the items for radio buttons checkboxes and drop
	 * down boxes.
	 */
	public function render_admin_form_children() {
		foreach ( array_keys( bp_xprofile_get_field_types() ) as $field_type ) {
			$type_obj = bp_xprofile_create_field_type( $field_type );
			$type_obj->admin_new_field_html( $this );
		}
	}

	/**
	 * Oupput the admin form for this field
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @param type $message
	 */
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

							<?php

							// Output the name & description fields
							$this->name_and_description(); ?>

						</div><!-- #post-body-content -->

						<div id="postbox-container-1" class="postbox-container">

							<?php

							// Output the sumbit metabox
							$this->submit_metabox( $button );

							// Output the required metabox
							$this->required_metabox();

							// Output the field visibility metaboxes
							$this->visibility_metabox();

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

							<?php

							/**
							 * Fires before XProfile Field content metabox.
							 *
							 * @since BuddyPress (2.3.0)
							 *
							 * @param BP_XProfile_Field $this Current XProfile field.
							 */
							do_action( 'xprofile_field_before_contentbox', $this );

							// Output the field attributes metabox
							$this->type_metabox();

							// Output hidden inputs for default field
							$this->default_field_hidden_inputs();

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

	/**
	 * Private method used to display the submit metabox
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param string $button_text
	 */
	private function submit_metabox( $button_text = '' ) {

		/**
		 * Fires before XProfile Field submit metabox.
		 *
		 * @since BuddyPress (2.1.0)
		 *
		 * @param BP_XProfile_Field $this Current XProfile field.
		 */
		do_action( 'xprofile_field_before_submitbox', $this ); ?>

		<div id="submitdiv" class="postbox">
			<h3><?php esc_html_e( 'Submit', 'buddypress' ); ?></h3>
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
						do_action( 'xprofile_field_submitbox_start', $this ); ?>

						<input type="hidden" name="field_order" id="field_order" value="<?php echo esc_attr( $this->field_order ); ?>" />

						<?php if ( ! empty( $button_text ) ) : ?>

							<div id="publishing-action">
								<input type="submit" name="saveField" value="<?php echo esc_attr( $button_text ); ?>" class="button-primary" />
							</div>

						<?php endif; ?>

						<div id="delete-action">
							<a href="users.php?page=bp-profile-setup" class="deletion"><?php esc_html_e( 'Cancel', 'buddypress' ); ?></a>
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
	}

	/**
	 * Private method used to output field name and description fields
	 *
	 * @since BuddyPress (2.3.0)
	 */
	private function name_and_description() {
	?>

		<div id="titlediv">
			<div class="titlewrap">
				<label id="title-prompt-text" for="title"><?php echo esc_html_x( 'Name', 'XProfile admin edit field', 'buddypress' ); ?></label>
				<input type="text" name="title" id="title" value="<?php echo esc_attr( $this->name ); ?>" autocomplete="off" />
			</div>
		</div>

		<div class="postbox">
			<h3><?php echo esc_html_x( 'Description', 'XProfile admin edit field', 'buddypress' ); ?></h3>
			<div class="inside">
				<textarea name="description" id="description" rows="8" cols="60"><?php echo esc_textarea( $this->description ); ?></textarea>
			</div>
		</div>

	<?php
	}

	/**
	 * Private method used to output field visibility metaboxes
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return if default field id 1
	 */
	private function visibility_metabox() {

		// Default field cannot have custom visibility
		if ( true === $this->is_default_field() ) {
			return;
		} ?>

		<div class="postbox">
			<h3><label for="default-visibility"><?php esc_html_e( 'Visibility', 'buddypress' ); ?></label></h3>
			<div class="inside">
				<div>
					<select name="default-visibility" >

						<?php foreach( bp_xprofile_get_visibility_levels() as $level ) : ?>

							<option value="<?php echo esc_attr( $level['id'] ); ?>" <?php selected( $this->default_visibility, $level['id'] ); ?>>
								<?php echo esc_html( $level['label'] ); ?>
							</option>

						<?php endforeach ?>

					</select>
				</div>

				<div>
					<ul>
						<li>
							<input type="radio" id="allow-custom-visibility-allowed" name="allow-custom-visibility" value="allowed" <?php checked( $this->allow_custom_visibility, 'allowed' ); ?> />
							<label for="allow-custom-visibility-allowed"><?php esc_html_e( 'Allow members to override', 'buddypress' ); ?></label>
						</li>
						<li>
							<input type="radio" id="allow-custom-visibility-disabled" name="allow-custom-visibility" value="disabled" <?php checked( $this->allow_custom_visibility, 'disabled' ); ?> />
							<label for="allow-custom-visibility-disabled"><?php esc_html_e( 'Enforce field visibility', 'buddypress' ); ?></label>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Output the metabox for setting if field is required or not
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return if default field
	 */
	private function required_metabox() {

		// Default field is always required
		if ( true === $this->is_default_field() ) {
			return;
		} ?>

		<div class="postbox">
			<h3><label for="required"><?php esc_html_e( 'Requirement', 'buddypress' ); ?></label></h3>
			<div class="inside">
				<select name="required" id="required">
					<option value="0"<?php selected( $this->is_required, '0' ); ?>><?php esc_html_e( 'Not Required', 'buddypress' ); ?></option>
					<option value="1"<?php selected( $this->is_required, '1' ); ?>><?php esc_html_e( 'Required',     'buddypress' ); ?></option>
				</select>
			</div>
		</div>

	<?php
	}

	/**
	 * Output the metabox for setting what type of field this is
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return if default field
	 */
	private function type_metabox() {

		// Default field cannot change type
		if ( true === $this->is_default_field() ) {
			return;
		} ?>

		<div class="postbox">
			<h3><label for="fieldtype"><?php esc_html_e( 'Type', 'buddypress'); ?></label></h3>
			<div class="inside">
				<select name="fieldtype" id="fieldtype" onchange="show_options(this.value)" style="width: 30%">

					<?php bp_xprofile_admin_form_field_types( $this->type ); ?>

				</select>

				<?php

				// Deprecated filter, don't use. Go look at {@link BP_XProfile_Field_Type::admin_new_field_html()}.
				do_action( 'xprofile_field_additional_options', $this );

				$this->render_admin_form_children(); ?>

			</div>
		</div>

	<?php
	}

	/**
	 * Output hidden fields used by default field
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @return if not default field
	 */
	private function default_field_hidden_inputs() {

		// Field 1 is the fullname field, which cannot have custom visibility
		if ( false === $this->is_default_field() ) {
			return;
		} ?>

		<input type="hidden" name="required"  id="required"  value="1"       />
		<input type="hidden" name="fieldtype" id="fieldtype" value="textbox" />

		<?php
	}

	/**
	 * Return if a field ID is the default field
	 *
	 * @since BuddyPress (2.3.0)
	 *
	 * @param  int $field_id ID of field to check
	 * @return bool
	 */
	private function is_default_field( $field_id = 0 ) {

		// Fallback to current field ID if none passed
		if ( empty( $field_id ) ) {
			$field_id = $this->id;
		}

		// Compare & return
		return (bool) ( 1 === (int) $field_id );
	}
}
