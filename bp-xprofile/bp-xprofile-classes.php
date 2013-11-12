<?php

/**
 * BuddyPress XProfile Classes
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_XProfile_Group {
	public $id = null;
	public $name;
	public $description;
	public $can_delete;
	public $group_order;
	public $fields;

	public function __construct( $id = null ) {
		if ( !empty( $id ) )
			$this->populate( $id );
	}

	public function populate( $id ) {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_groups} WHERE id = %d", $id );

		if ( !$group = $wpdb->get_row( $sql ) )
			return false;

		$this->id          = $group->id;
		$this->name        = stripslashes( $group->name );
		$this->description = stripslashes( $group->description );
		$this->can_delete  = $group->can_delete;
		$this->group_order = $group->group_order;
	}

	public function save() {
		global $wpdb, $bp;

		$this->name        = apply_filters( 'xprofile_group_name_before_save',        $this->name,        $this->id );
		$this->description = apply_filters( 'xprofile_group_description_before_save', $this->description, $this->id );

		do_action_ref_array( 'xprofile_group_before_save', array( &$this ) );

		if ( $this->id )
			$sql = $wpdb->prepare( "UPDATE {$bp->profile->table_name_groups} SET name = %s, description = %s WHERE id = %d", $this->name, $this->description, $this->id );
		else
			$sql = $wpdb->prepare( "INSERT INTO {$bp->profile->table_name_groups} (name, description, can_delete) VALUES (%s, %s, 1)", $this->name, $this->description );

		if ( is_wp_error( $wpdb->query( $sql ) ) )
			return false;

		// If not set, update the ID in the group object
		if ( ! $this->id )
			$this->id = $wpdb->insert_id;

		do_action_ref_array( 'xprofile_group_after_save', array( &$this ) );

		return $this->id;
	}

	public function delete() {
		global $wpdb, $bp;

		if ( empty( $this->can_delete ) )
			return false;

		// Delete field group
		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_groups} WHERE id = %d", $this->id ) ) ) {
			return false;
		} else {

			// Remove the group's fields.
			if ( BP_XProfile_Field::delete_for_group( $this->id ) ) {

				// Remove profile data for the groups fields
				for ( $i = 0, $count = count( $this->fields ); $i < $count; ++$i ) {
					BP_XProfile_ProfileData::delete_for_field( $this->fields[$i]->id );
				}
			}

			return true;
		}
	}

	/** Static Methods ********************************************************/

	/**
	 * get()
	 *
	 * Populates the BP_XProfile_Group object with profile field groups, fields, and field data
	 *
	 * @package BuddyPress XProfile
	 *
	 * @global $wpdb WordPress DB access object.
	 * @global BuddyPress $bp The one true BuddyPress instance
	 *
	 * @param array $args Takes an array of parameters:
	 *		'profile_group_id' - Limit results to a single profile group
	 *		'user_id' - Required if you want to load a specific user's data
	 *		'hide_empty_groups' - Hide groups without any fields
	 *		'hide_empty_fields' - Hide fields where the user has not provided data
	 *		'fetch_fields' - Load each group's fields
	 *		'fetch_field_data' - Load each field's data. Requires a user_id
	 *		'exclude_groups' - Comma-separated list of groups to exclude
	 *		'exclude_fields' - Comma-separated list of fields to exclude
	 *
	 * @return array $groups
	 */
	public static function get( $args = array() ) {
		global $wpdb, $bp;

		$defaults = array(
			'profile_group_id'       => false,
			'user_id'                => bp_displayed_user_id(),
			'hide_empty_groups'      => false,
			'hide_empty_fields'      => false,
			'fetch_fields'           => false,
			'fetch_field_data'       => false,
			'fetch_visibility_level' => false,
			'exclude_groups'         => false,
			'exclude_fields'         => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
		$where_sql = '';

		if ( !empty( $profile_group_id ) )
			$where_sql = $wpdb->prepare( 'WHERE g.id = %d', $profile_group_id );
		elseif ( $exclude_groups )
			$where_sql = $wpdb->prepare( "WHERE g.id NOT IN ({$exclude_groups})");

		if ( !empty( $hide_empty_groups ) )
			$groups = $wpdb->get_results( "SELECT DISTINCT g.* FROM {$bp->profile->table_name_groups} g INNER JOIN {$bp->profile->table_name_fields} f ON g.id = f.group_id {$where_sql} ORDER BY g.group_order ASC" );
		else
			$groups = $wpdb->get_results( "SELECT DISTINCT g.* FROM {$bp->profile->table_name_groups} g {$where_sql} ORDER BY g.group_order ASC" );

		if ( empty( $fetch_fields ) )
			return $groups;

		// Get the group ids
		$group_ids = array();
		foreach( (array) $groups as $group ) {
			$group_ids[] = $group->id;
		}

		$group_ids = implode( ',', (array) $group_ids );

		if ( empty( $group_ids ) )
			return $groups;

		// Support arrays and comma-separated strings
		$exclude_fields_cs = wp_parse_id_list( $exclude_fields );

		// Visibility - Handled here so as not to be overridden by sloppy use of the
		// exclude_fields parameter. See bp_xprofile_get_hidden_fields_for_user()
		$exclude_fields_cs = array_merge( $exclude_fields_cs, bp_xprofile_get_hidden_fields_for_user( $user_id ) );
		$exclude_fields_cs = implode( ',', $exclude_fields_cs );

		if ( !empty( $exclude_fields_cs ) ) {
			$exclude_fields_sql = "AND id NOT IN ({$exclude_fields_cs})";
		} else {
			$exclude_fields_sql = '';
		}

		// Fetch the fields
		$fields = $wpdb->get_results( "SELECT id, name, description, type, group_id, is_required FROM {$bp->profile->table_name_fields} WHERE group_id IN ( {$group_ids} ) AND parent_id = 0 {$exclude_fields_sql} ORDER BY field_order" );

		if ( empty( $fields ) )
			return $groups;

		// Maybe fetch field data
		if ( ! empty( $fetch_field_data ) ) {

			// Fetch the field data for the user.
			foreach( (array) $fields as $field ) {
				$field_ids[] = $field->id;
			}

			$field_ids_sql = implode( ',', (array) $field_ids );

			if ( ! empty( $field_ids ) && ! empty( $user_id ) ) {
				$field_data = $wpdb->get_results( $wpdb->prepare( "SELECT id, field_id, value FROM {$bp->profile->table_name_data} WHERE field_id IN ( {$field_ids_sql} ) AND user_id = %d", $user_id ) );
			}

			// Remove data-less fields, if necessary
			if ( !empty( $hide_empty_fields ) && ! empty( $field_ids ) && ! empty( $field_data ) ) {

				// Loop through the results and find the fields that have data.
				foreach( (array) $field_data as $data ) {

					// Empty fields may contain a serialized empty array
					$maybe_value = maybe_unserialize( $data->value );
					if ( !empty( $maybe_value ) && false !== $key = array_search( $data->field_id, $field_ids ) ) {
						// Fields that have data get removed from the list
						unset( $field_ids[$key] );
					}
				}

				// The remaining members of $field_ids are empty. Remove them.
				foreach( $fields as $field_key => $field ) {
					if ( in_array( $field->id, $field_ids ) ) {
						unset( $fields[$field_key] );
					}
				}

				// Reset indexes
				$fields = array_values( $fields );
			}

			// Field data was found
			if ( ! empty( $fields ) && !empty( $field_data ) && !is_wp_error( $field_data ) ) {

				// Loop through fields
				foreach( (array) $fields as $field_key => $field ) {

					// Loop throught the data in each field
					foreach( (array) $field_data as $data ) {

						// Assign correct data value to the field
						if ( $field->id == $data->field_id ) {
							$fields[$field_key]->data        = new stdClass;
							$fields[$field_key]->data->value = $data->value;
							$fields[$field_key]->data->id    = $data->id;
						}
					}
				}
			}
		}

		// Maybe fetch visibility levels
		if ( !empty( $fetch_visibility_level ) ) {
			$fields = self::fetch_visibility_level( $user_id, $fields );
		}

		// Merge the field array back in with the group array
		foreach( (array) $groups as $group ) {

			// Indexes may have been shifted after previous deletions, so we get a
			// fresh one each time through the loop
			$index = array_search( $group, $groups );

			foreach( (array) $fields as $field ) {
				if ( $group->id == $field->group_id ) {
					$groups[$index]->fields[] = $field;
				}
			}

			// When we unset fields above, we may have created empty groups.
			// Remove them, if necessary.
			if ( empty( $group->fields ) && $hide_empty_groups ) {
				unset( $groups[$index] );
			}

			// Reset indexes
			$groups = array_values( $groups );
		}

		return $groups;
	}

	public static function admin_validate() {
		global $message;

		/* Validate Form */
		if ( empty( $_POST['group_name'] ) ) {
			$message = __( 'Please make sure you give the group a name.', 'buddypress' );
			return false;
		} else {
			return true;
		}
	}

	public static function update_position( $field_group_id, $position ) {
		global $wpdb, $bp;

		if ( !is_numeric( $position ) )
			return false;

		return $wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_groups} SET group_order = %d WHERE id = %d", $position, $field_group_id ) );
	}

	/**
	 * Fetch the field visibility level for the fields returned by the query
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @param int $user_id The profile owner's user_id
	 * @param array $fields The database results returned by the get() query
	 * @return array $fields The database results, with field_visibility added
	 */
	public static function fetch_visibility_level( $user_id = 0, $fields = array() ) {

		// Get the user's visibility level preferences
		$visibility_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );

		// Get the admin-set preferences
		$admin_set_levels  = self::fetch_default_visibility_levels();

		foreach( (array) $fields as $key => $field ) {

			// Does the admin allow this field to be customized?
			$allow_custom = empty( $admin_set_levels[$field->id]['allow_custom'] ) || 'allowed' == $admin_set_levels[$field->id]['allow_custom'];

			// Look to see if the user has set the visibility for this field
			if ( $allow_custom && isset( $visibility_levels[$field->id] ) ) {
				$field_visibility = $visibility_levels[$field->id];

			// If no admin-set default is saved, fall back on a global default
			} else {
				$field_visibility = !empty( $admin_set_levels[$field->id]['default'] ) ? $admin_set_levels[$field->id]['default'] : apply_filters( 'bp_xprofile_default_visibility_level', 'public' );
			}

			$fields[$key]->visibility_level = $field_visibility;
		}

		return $fields;
	}

	/**
	 * Fetch the admin-set preferences for all fields
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @return array $default_visibility_levels An array, keyed by field_id, of default
	 *   visibility level + allow_custom (whether the admin allows this field to be set by user)
	 */
	public static function fetch_default_visibility_levels() {
		global $wpdb, $bp;

		$levels = $wpdb->get_results( "SELECT object_id, meta_key, meta_value FROM {$bp->profile->table_name_meta} WHERE object_type = 'field' AND ( meta_key = 'default_visibility' OR meta_key = 'allow_custom_visibility' )" );

		// Arrange so that the field id is the key and the visibility level the value
		$default_visibility_levels = array();
		foreach( $levels as $level ) {
			if ( 'default_visibility' == $level->meta_key ) {
				$default_visibility_levels[$level->object_id]['default'] = $level->meta_value;
			} else if ( 'allow_custom_visibility' == $level->meta_key ) {
				$default_visibility_levels[$level->object_id]['allow_custom'] = $level->meta_value;
			}
		}

		return $default_visibility_levels;
	}

	public function render_admin_form() {
		global $message;

		if ( empty( $this->id ) ) {
			$title	= __( 'Add New Field Group', 'buddypress' );
			$action	= "users.php?page=bp-profile-setup&amp;mode=add_group";
			$button	= __( 'Create Field Group', 'buddypress' );
		} else {
			$title  = __( 'Edit Field Group', 'buddypress' );
			$action = "users.php?page=bp-profile-setup&amp;mode=edit_group&amp;group_id=" . $this->id;
			$button	= __( 'Save Changes', 'buddypress' );
		} ?>

		<div class="wrap">

			<?php screen_icon( 'users' ); ?>

			<h2><?php echo esc_html( $title ); ?></h2>

			<?php if ( !empty( $message ) ) :
					$type = ( 'error' == $type ) ? 'error' : 'updated'; ?>

				<div id="message" class="<?php echo esc_attr( $type ); ?> fade">
					<p><?php echo esc_html( $message ); ?></p>
				</div>

			<?php endif; ?>

			<form action="<?php echo esc_url( $action ); ?>" method="post">
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">
							<div id="titlediv">
								<div id="titlewrap">
									<label class="screen-reader-text" id="title-prompt-text" for=​"title">​<?php _e( 'Field Group Title', 'buddypress') ?></label>
									<input type="text" name="group_name" id="title" value="<?php echo esc_attr( $this->name ); ?>" placeholder="<?php esc_attr_e( 'Field Group Title', 'buddypress' ); ?>" />
								</div>
							</div>

							<div id="postdiv">
								<div class="postbox">
									<div id="titlediv"><h3 class="hndle"><?php _e( 'Group Description', 'buddypress' ); ?></h3></div>
									<div class="inside">
										<textarea name="group_description" id="group_description" rows="8" cols="60"><?php echo esc_textarea( $this->description ); ?></textarea>
									</div>
								</div>
							</div>
						</div>
						<div id="postbox-container-1" class="postbox-container">
							<div id="side-sortables" class="meta-box-sortables ui-sortable">
								<div id="submitdiv" class="postbox">
									<div id="handlediv"><h3 class="hndle"><?php _e( 'Save', 'buddypress' ); ?></h3></div>
									<div class="inside">
										<div id="submitcomment" class="submitbox">
											<div id="major-publishing-actions">
												<div id="delete-action">
													<a href="users.php?page=bp-profile-setup" class="submitdelete deletion"><?php _e( 'Cancel', 'buddypress' ); ?></a>
												</div>
												<div id="publishing-action">
													<input type="submit" name="save_group" value="<?php echo esc_attr( $button ); ?>" class="button-primary"/>
												</div>
												<input type="hidden" name="group_order" id="group_order" value="<?php echo esc_attr( $this->group_order ); ?>" />
												<div class="clear"></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>

<?php
	}
}

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

	public $data;
	public $message = null;
	public $message_type = 'err';

	public function __construct( $id = null, $user_id = null, $get_data = true ) {
		if ( !empty( $id ) )
			$this->populate( $id, $user_id, $get_data );
	}

	public function populate( $id, $user_id, $get_data ) {
		global $wpdb, $userdata, $bp;

		if ( empty( $user_id ) ) {
			$user_id = isset( $userdata->ID ) ? $userdata->ID : 0;
		}

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
		global $wpdb, $bp;

		// Prevent deletion if no ID is present
		// Prevent deletion by url when can_delete is false.
		// Prevent deletion of option 1 since this invalidates fields with options.
		if ( empty( $this->id ) || empty( $this->can_delete ) || ( $this->parent_id && $this->option_order == 1 ) )
			return false;

		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_fields} WHERE id = %d OR parent_id = %d", $this->id, $this->id ) ) )
			return false;

		// delete the data in the DB for this field
		if ( true === $delete_data )
			BP_XProfile_ProfileData::delete_for_field( $this->id );

		return true;
	}

	public function save() {
		global $wpdb, $bp;

		$this->group_id    = apply_filters( 'xprofile_field_group_id_before_save',    $this->group_id,    $this->id );
		$this->parent_id   = apply_filters( 'xprofile_field_parent_id_before_save',   $this->parent_id,   $this->id );
		$this->type        = apply_filters( 'xprofile_field_type_before_save',        $this->type,        $this->id );
		$this->name        = apply_filters( 'xprofile_field_name_before_save',        $this->name,        $this->id );
		$this->description = apply_filters( 'xprofile_field_description_before_save', $this->description, $this->id );
		$this->is_required = apply_filters( 'xprofile_field_is_required_before_save', $this->is_required, $this->id );
		$this->order_by	   = apply_filters( 'xprofile_field_order_by_before_save',    $this->order_by,    $this->id );
		$this->field_order = apply_filters( 'xprofile_field_field_order_before_save', $this->field_order, $this->id );
		$this->can_delete  = apply_filters( 'xprofile_field_can_delete_before_save',  $this->can_delete,  $this->id );

		do_action_ref_array( 'xprofile_field_before_save', array( $this ) );

		if ( $this->id != null ) {
			$sql = $wpdb->prepare( "UPDATE {$bp->profile->table_name_fields} SET group_id = %d, parent_id = 0, type = %s, name = %s, description = %s, is_required = %d, order_by = %s, field_order = %d, can_delete = %d WHERE id = %d", $this->group_id, $this->type, $this->name, $this->description, $this->is_required, $this->order_by, $this->field_order, $this->can_delete, $this->id );
		} else {
			$sql = $wpdb->prepare( "INSERT INTO {$bp->profile->table_name_fields} (group_id, parent_id, type, name, description, is_required, order_by, field_order, can_delete ) VALUES (%d, %d, %s, %s, %s, %d, %s, %d, %d )", $this->group_id, $this->parent_id, $this->type, $this->name, $this->description, $this->is_required, $this->order_by, $this->field_order, $this->can_delete );
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
			if ( 'radio' == $this->type || 'selectbox' == $this->type || 'checkbox' == $this->type || 'multiselectbox' == $this->type ) {

				if ( !empty( $this->id ) ) {
					$parent_id = $this->id;
				} else {
					$parent_id = $wpdb->insert_id;
				}

				if ( 'radio' == $this->type ) {
					$post_option  = !empty( $_POST['radio_option']           ) ? $_POST['radio_option']           : '';
					$post_default = !empty( $_POST['isDefault_radio_option'] ) ? $_POST['isDefault_radio_option'] : '';

					$options	= apply_filters( 'xprofile_field_options_before_save', $post_option,  'radio' );
					$defaults	= apply_filters( 'xprofile_field_default_before_save', $post_default, 'radio' );

				} elseif ( 'selectbox' == $this->type ) {
					$post_option  = !empty( $_POST['selectbox_option']           ) ? $_POST['selectbox_option']           : '';
					$post_default = !empty( $_POST['isDefault_selectbox_option'] ) ? $_POST['isDefault_selectbox_option'] : '';

					$options	= apply_filters( 'xprofile_field_options_before_save', $post_option, 'selectbox' );
					$defaults	= apply_filters( 'xprofile_field_default_before_save', $post_default, 'selectbox' );

				} elseif ( 'multiselectbox' == $this->type ) {
					$post_option  = !empty( $_POST['multiselectbox_option']           ) ? $_POST['multiselectbox_option']           : '';
					$post_default = !empty( $_POST['isDefault_multiselectbox_option'] ) ? $_POST['isDefault_multiselectbox_option'] : '';

					$options	= apply_filters( 'xprofile_field_options_before_save', $post_option, 'multiselectbox' );
					$defaults	= apply_filters( 'xprofile_field_default_before_save', $post_default, 'multiselectbox' );

				} elseif ( 'checkbox' == $this->type ) {
					$post_option  = !empty( $_POST['checkbox_option']           ) ? $_POST['checkbox_option']           : '';
					$post_default = !empty( $_POST['isDefault_checkbox_option'] ) ? $_POST['isDefault_checkbox_option'] : '';

					$options	= apply_filters( 'xprofile_field_options_before_save', $post_option, 'checkbox' );
					$defaults	= apply_filters( 'xprofile_field_default_before_save', $post_default, 'checkbox' );
				}

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

			do_action_ref_array( 'xprofile_field_after_save', array( $this ) );

			return $field_id;
		} else {
			return false;
		}
	}

	public function get_field_data( $user_id ) {
		return new BP_XProfile_ProfileData( $this->id, $user_id );
	}

	public function get_children( $for_editing = false ) {
		global $wpdb, $bp;

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

		$sql = $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE parent_id = %d AND group_id = %d $sort_sql", $parent_id, $this->group_id );

		if ( !$children = $wpdb->get_results( $sql ) )
			return false;

		return apply_filters( 'bp_xprofile_field_get_children', $children );
	}

	public function delete_children() {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_fields} WHERE parent_id = %d", $this->id );

		$wpdb->query( $sql );
	}

	/** Static Methods ********************************************************/

	public static function get_type( $field_id ) {
		global $wpdb, $bp;

		if ( !empty( $field_id ) ) {
			$sql = $wpdb->prepare( "SELECT type FROM {$bp->profile->table_name_fields} WHERE id = %d", $field_id );

			if ( !$field_type = $wpdb->get_var( $sql ) ) {
				return false;
			}

			return $field_type;
		}

		return false;
	}

	public static function delete_for_group( $group_id ) {
		global $wpdb, $bp;

		if ( !empty( $group_id ) ) {
			$sql = $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_fields} WHERE group_id = %d", $group_id );

			if ( $wpdb->get_var( $sql ) === false ) {
				return false;
			}

			return true;
		}

		return false;
	}

	public static function get_id_from_name( $field_name ) {
		global $wpdb, $bp;

		if ( empty( $bp->profile->table_name_fields ) || !isset( $field_name ) )
			return false;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_fields} WHERE name = %s", $field_name ) );
	}

	public static function update_position( $field_id, $position, $field_group_id ) {
		global $wpdb, $bp;

		if ( !is_numeric( $position ) || !is_numeric( $field_group_id ) )
			return false;

		// Update $field_id with new $position and $field_group_id
		if ( $parent = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_fields} SET field_order = %d, group_id = %d WHERE id = %d", $position, $field_group_id, $field_id ) ) ) {;

			// Update any children of this $field_id
			$children = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_fields} SET group_id = %d WHERE parent_id = %d", $field_group_id, $field_id ) );

			return $parent;
		}

		return false;
	}

	/* ADMIN AREA HTML.
	* TODO: Get this out of here and replace with standard template loops
	*/

	/* This function populates the items for radio buttons checkboxes and drop down boxes */
	public function render_admin_form_children() {
		$input_types = array( 'checkbox', 'selectbox', 'multiselectbox', 'radio' );

		foreach ( $input_types as $type ) {
			$default_name = '';

			if ( ( 'multiselectbox' == $type ) || ( 'checkbox' == $type ) ) {
				$default_input = 'checkbox';
			} else {
				$default_input = 'radio';
			}

			$class = $this->type != $type ? 'display: none;' : '';

			if ( empty( $this->default_visibility ) ) {
				$this->default_visibility = 'public';
			}

			?>

			<div id="<?php echo esc_attr( $type ); ?>" class="postbox bp-options-box" style="<?php echo esc_attr( $class ); ?> margin-top: 15px;">
				<h3><?php _e( 'Please enter options for this Field:', 'buddypress' ); ?></h3>
				<div class="inside">
					<p>
						<label for="sort_order_<?php echo esc_attr( $type ); ?>"><?php _e( 'Sort Order:', 'buddypress' ); ?></label>
						<select name="sort_order_<?php echo esc_attr( $type ); ?>" id="sort_order_<?php echo esc_attr( $type ); ?>" >
							<option value="custom" <?php selected( 'custom', $this->order_by ); ?>><?php _e( 'Custom',     'buddypress' ); ?></option>
							<option value="asc"    <?php selected( 'asc',    $this->order_by ); ?>><?php _e( 'Ascending',  'buddypress' ); ?></option>
							<option value="desc"   <?php selected( 'desc',   $this->order_by ); ?>><?php _e( 'Descending', 'buddypress' ); ?></option>
						</select>
					</p>

					<?php if ( !$options = $this->get_children( true ) ) {

						$i = 1;
						while ( isset( $_POST[$type . '_option'][$i] ) ) {
							(array) $options[] = (object) array(
								'id'                => -1,
								'name'              => $_POST[$type . '_option'][$i],
								'is_default_option' => ( ( 'multiselectbox' != $type ) && ( 'checkbox' != $type ) && ( $_POST["isDefault_{$type}_option"] == $i ) ) ? 1 : $_POST["isDefault_{$type}_option"][$i]
							);

							++$i;
						}
					}

					if ( !empty( $options ) ) {
						for ( $i = 0, $count = count( $options ); $i < $count; ++$i ) {
							$j = $i + 1;

							if ( 'multiselectbox' == $type || 'checkbox' == $type )
								$default_name = '[' . $j . ']'; ?>

							<p class="sortable">
								<span>&nbsp;&Xi;&nbsp;</span>
								<input type="text" name="<?php echo esc_attr( $type ); ?>_option[<?php echo esc_attr( $j ); ?>]" id="<?php echo esc_attr( $type ); ?>_option<?php echo esc_attr( $j ); ?>" value="<?php echo stripslashes( esc_attr( $options[$i]->name ) ); ?>" />
								<input type="<?php echo $default_input; ?>" name="isDefault_<?php echo esc_attr( $type ); ?>_option<?php echo esc_attr( $default_name ); ?>" <?php checked( (int) $options[$i]->is_default_option, true ); ?> value="<?php echo esc_attr( $j ); ?>" />
								<span><?php _e( 'Default Value', 'buddypress' ); ?></span>
								<a href="<?php echo esc_url( 'users.php?page=bp-profile-setup&amp;mode=delete_option&amp;option_id=' . $options[$i]->id ); ?>" class="ajax-option-delete" id="delete-<?php echo esc_attr( $options[$i]->id ); ?>">[x]</a>
							</p>

						<?php } /* end for */ ?>

						<input type="hidden" name="<?php echo esc_attr( $type ); ?>_option_number" id="<?php echo esc_attr( $type ); ?>_option_number" value="<?php echo esc_attr( (int) $j + 1 ); ?>" />

					<?php } else {

						if ( 'multiselectbox' == $type || 'checkbox' == $type )
							$default_name = '[1]'; ?>

						<p class="sortable">
							<span>&nbsp;&Xi;&nbsp;</span>
							<input type="text" name="<?php echo esc_attr( $type ); ?>_option[1]" id="<?php echo esc_attr( $type ); ?>_option1" />
							<input type="<?php echo esc_attr( $default_input ); ?>" name="isDefault_<?php echo esc_attr( $type ); ?>_option<?php echo esc_attr( $default_name ); ?>" id="isDefault_<?php echo esc_attr( $type ); ?>_option" value="1" />
							<span><?php _e( 'Default Value', 'buddypress' ); ?></span>
							<input type="hidden" name="<?php echo esc_attr( $type ); ?>_option_number" id="<?php echo esc_attr( $type ); ?>_option_number" value="2" />
						</p>

					<?php } /* end if */ ?>

					<div id="<?php echo esc_attr( $type ); ?>_more"></div>
					<p><a href="javascript:add_option('<?php echo esc_attr( $type ); ?>')"><?php _e( 'Add Another Option', 'buddypress' ); ?></a></p>
				</div>
			</div>

		<?php }
	}

	public function render_admin_form( $message = '' ) {
		if ( empty( $this->id ) ) {
			$title  = __( 'Add Field', 'buddypress' );
			$action	= "users.php?page=bp-profile-setup&amp;group_id=" . $this->group_id . "&amp;mode=add_field#tabs-" . $this->group_id;

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
		} ?>

		<div class="wrap">
			<div id="icon-users" class="icon32"><br /></div>
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
								<input type="text" name="title" id="title" value="<?php echo esc_attr( $this->name ); ?>" />
							</div>
							<div class="postbox">
								<h3><?php _e( 'Field Description', 'buddypress' ); ?></h3>
								<div class="inside">
									<textarea name="description" id="description" rows="8" cols="60"><?php echo esc_attr( $this->description ); ?></textarea>
								</div>
							</div>
						</div><!-- #post-body-content -->

						<div id="postbox-container-1" class="postbox-container">
							<div id="submitdiv" class="postbox">
								<h3><?php _e( 'Submit', 'buddypress' ); ?></h3>
								<div class="inside">
									<div id="submitcomment" class="submitbox">
										<div id="major-publishing-actions">
											<input type="hidden" name="field_order" id="field_order" value="<?php echo esc_attr( $this->field_order ); ?>" />
											<div id="publishing-action">
												<input type="submit" value="<?php _e( 'Save', 'buddypress' ); ?>" name="saveField" id="saveField" style="font-weight: bold" class="button-primary" />
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
											<optgroup label="<?php _e( 'Single Fields', 'buddypress' ); ?>">
												<option value="textbox"        <?php selected( $this->type, 'textbox'        ); ?>><?php _e( 'Text Box',             'buddypress' ); ?></option>
												<option value="textarea"       <?php selected( $this->type, 'textarea'       ); ?>><?php _e( 'Multi-line Text Area', 'buddypress' ); ?></option>
												<option value="datebox"        <?php selected( $this->type, 'datebox'        ); ?>><?php _e( 'Date Selector',        'buddypress' ); ?></option>
											</optgroup>
											<optgroup label="<?php _e( 'Multi Fields', 'buddypress' ); ?>">
												<option value="radio"          <?php selected( $this->type, 'radio'          ); ?>><?php _e( 'Radio Buttons',        'buddypress' ); ?></option>
												<option value="selectbox"      <?php selected( $this->type, 'selectbox'      ); ?>><?php _e( 'Drop Down Select Box', 'buddypress' ); ?></option>
												<option value="multiselectbox" <?php selected( $this->type, 'multiselectbox' ); ?>><?php _e( 'Multi Select Box',     'buddypress' ); ?></option>
												<option value="checkbox"       <?php selected( $this->type, 'checkbox'       ); ?>><?php _e( 'Checkboxes',           'buddypress' ); ?></option>
											</optgroup>
										</select>

										<?php do_action_ref_array( 'xprofile_field_additional_options', array( $this ) ); ?>

										<?php $this->render_admin_form_children(); ?>

									</div>
								</div>

							<?php else : ?>

								<input type="hidden" name="required"  id="required"  value="1"       />
								<input type="hidden" name="fieldtype" id="fieldtype" value="textbox" />

							<?php endif; ?>

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
		} else if ( empty( $_POST['field_file'] ) && $_POST['fieldtype'] == 'radio' && empty( $_POST['radio_option'][1] ) ) {
			$message = __( 'Radio button field types require at least one option. Please add options below.', 'buddypress' );
			return false;
		} else if ( empty( $_POST['field_file'] ) && $_POST['fieldtype'] == 'selectbox' && empty( $_POST['selectbox_option'][1] ) ) {
			$message = __( 'Select box field types require at least one option. Please add options below.', 'buddypress' );
			return false;
		} else if ( empty( $_POST['field_file'] ) && $_POST['fieldtype'] == 'multiselectbox' && empty( $_POST['multiselectbox_option'][1] ) ) {
			$message = __( 'Select box field types require at least one option. Please add options below.', 'buddypress' );
			return false;
		} else if ( empty( $_POST['field_file'] ) && $_POST['fieldtype'] == 'checkbox' && empty( $_POST['checkbox_option'][1] ) ) {
			$message = __( 'Checkbox field types require at least one option. Please add options below.', 'buddypress' );
			return false;
		} else {
			return true;
		}
	}
}


class BP_XProfile_ProfileData {
	public $id;
	public $user_id;
	public $field_id;
	public $value;
	public $last_updated;

	public function __construct( $field_id = null, $user_id = null ) {
		if ( !empty( $field_id ) ) {
			$this->populate( $field_id, $user_id );
		}
	}

	public function populate( $field_id, $user_id )  {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id = %d", $field_id, $user_id );

		if ( $profiledata = $wpdb->get_row( $sql ) ) {
			$this->id           = $profiledata->id;
			$this->user_id      = $profiledata->user_id;
			$this->field_id     = $profiledata->field_id;
			$this->value        = stripslashes( $profiledata->value );
			$this->last_updated = $profiledata->last_updated;
		} else {
			// When no row is found, we'll need to set these properties manually
			$this->field_id	    = $field_id;
			$this->user_id	    = $user_id;
		}
	}

	/**
	 * Check if there is data already for the user.
	 *
	 * @global object $wpdb
	 * @global array $bp
	 * @return bool
	 */
	public function exists() {
		global $wpdb, $bp;

		$retval = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_data} WHERE user_id = %d AND field_id = %d", $this->user_id, $this->field_id ) );

		return apply_filters_ref_array( 'xprofile_data_exists', array( (bool)$retval, $this ) );
	}

	/**
	 * Check if this data is for a valid field.
	 *
	 * @global object $wpdb
	 * @global array $bp
	 * @return bool
	 */
	public function is_valid_field() {
		global $wpdb, $bp;

		$retval = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_fields} WHERE id = %d", $this->field_id ) );

		return apply_filters_ref_array( 'xprofile_data_is_valid_field', array( (bool)$retval, $this ) );
	}

	public function save() {
		global $wpdb, $bp;

		$this->user_id      = apply_filters( 'xprofile_data_user_id_before_save',      $this->user_id,         $this->id );
		$this->field_id     = apply_filters( 'xprofile_data_field_id_before_save',     $this->field_id,        $this->id );
		$this->value        = apply_filters( 'xprofile_data_value_before_save',        $this->value,           $this->id );
		$this->last_updated = apply_filters( 'xprofile_data_last_updated_before_save', bp_core_current_time(), $this->id );

		do_action_ref_array( 'xprofile_data_before_save', array( $this ) );

		if ( $this->is_valid_field() ) {
			if ( $this->exists() && !empty( $this->value ) && strlen( trim( $this->value ) ) ) {
				$result   = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_data} SET value = %s, last_updated = %s WHERE user_id = %d AND field_id = %d", $this->value, $this->last_updated, $this->user_id, $this->field_id ) );

			} else if ( $this->exists() && empty( $this->value ) ) {
				// Data removed, delete the entry.
				$result   = $this->delete();

			} else {
				$result   = $wpdb->query( $wpdb->prepare("INSERT INTO {$bp->profile->table_name_data} (user_id, field_id, value, last_updated) VALUES (%d, %d, %s, %s)", $this->user_id, $this->field_id, $this->value, $this->last_updated ) );
				$this->id = $wpdb->insert_id;
			}

			if ( false === $result )
				return false;

			do_action_ref_array( 'xprofile_data_after_save', array( $this ) );

			return true;
		}

		return false;
	}

	/**
	 * Delete specific XProfile field data
	 *
	 * @global object $wpdb
	 * @return boolean
	 */
	public function delete() {
		global $wpdb;

		$bp = buddypress();

		do_action_ref_array( 'xprofile_data_before_delete', array( $this ) );

		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id = %d", $this->field_id, $this->user_id ) ) )
			return false;

		do_action_ref_array( 'xprofile_data_after_delete', array( $this ) );

		return true;
	}

	/** Static Methods ********************************************************/

	/**
	 * BP_XProfile_ProfileData::get_all_for_user()
	 *
	 * Get all of the profile information for a specific user.
	 */
	public static function get_all_for_user( $user_id ) {
		global $wpdb, $bp;

		$results      = $wpdb->get_results( $wpdb->prepare( "SELECT g.id as field_group_id, g.name as field_group_name, f.id as field_id, f.name as field_name, f.type as field_type, d.value as field_data, u.user_login, u.user_nicename, u.user_email FROM {$bp->profile->table_name_groups} g LEFT JOIN {$bp->profile->table_name_fields} f ON g.id = f.group_id INNER JOIN {$bp->profile->table_name_data} d ON f.id = d.field_id LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID WHERE d.user_id = %d AND d.value != ''", $user_id ) );
		$profile_data = array();

		if ( !empty( $results ) ) {
			$profile_data['user_login']    = $results[0]->user_login;
			$profile_data['user_nicename'] = $results[0]->user_nicename;
			$profile_data['user_email']    = $results[0]->user_email;

			foreach( (array) $results as $field ) {
				$profile_data[$field->field_name] = array(
					'field_group_id'   => $field->field_group_id,
					'field_group_name' => $field->field_group_name,
					'field_id'         => $field->field_id,
					'field_type'       => $field->field_type,
					'field_data'       => $field->field_data
				);
			}
		}

		return $profile_data;
	}

	/**
	 * Get the user's field data id by the id of the xprofile field
	 *
	 * @param int $field_id
	 * @param int $user_id
	 * @return int $fielddata_id
	 */
	public static function get_fielddataid_byid( $field_id, $user_id ) {
		global $wpdb, $bp;

		if ( empty( $field_id ) || empty( $user_id ) ) {
			$fielddata_id = 0;
		} else {
			$fielddata_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id = %d", $field_id, $user_id ) );
		}

		return $fielddata_id;
	}

	public static function get_value_byid( $field_id, $user_ids = null ) {
		global $wpdb, $bp;

		if ( empty( $user_ids ) )
			$user_ids = bp_displayed_user_id();

		if ( is_array( $user_ids ) ) {
			$user_ids = implode( ',', wp_parse_id_list( $user_ids ) );
			$data = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, value FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id IN ({$user_ids})", $field_id ) );
		} else {
			$data = $wpdb->get_var( $wpdb->prepare( "SELECT value FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id = %d", $field_id, $user_ids ) );
		}

		return $data;
	}

	public static function get_value_byfieldname( $fields, $user_id = null ) {
		global $bp, $wpdb;

		if ( empty( $fields ) )
			return false;

		if ( empty( $user_id ) )
			$user_id = bp_displayed_user_id();

		$field_sql = '';

		if ( is_array( $fields ) ) {
			for ( $i = 0, $count = count( $fields ); $i < $count; ++$i ) {
				if ( $i == 0 ) {
					$field_sql .= $wpdb->prepare( "AND ( f.name = %s ", $fields[$i] );
				} else {
					$field_sql .= $wpdb->prepare( "OR f.name = %s ", $fields[$i] );
				}
			}

			$field_sql .= ')';
		} else {
			$field_sql .= $wpdb->prepare( "AND f.name = %s", $fields );
		}

		$sql = $wpdb->prepare( "SELECT d.value, f.name FROM {$bp->profile->table_name_data} d, {$bp->profile->table_name_fields} f WHERE d.field_id = f.id AND d.user_id = %d AND f.parent_id = 0 $field_sql", $user_id );

		if ( !$values = $wpdb->get_results( $sql ) )
			return false;

		$new_values = array();

		if ( is_array( $fields ) ) {
			for ( $i = 0, $count = count( $values ); $i < $count; ++$i ) {
				for ( $j = 0; $j < count( $fields ); $j++ ) {
					if ( $values[$i]->name == $fields[$j] ) {
						$new_values[$fields[$j]] = $values[$i]->value;
					} else if ( !array_key_exists( $fields[$j], $new_values ) ) {
						$new_values[$fields[$j]] = NULL;
					}
				}
			}
		} else {
			$new_values = $values[0]->value;
		}

		return $new_values;
	}

	public static function delete_for_field( $field_id ) {
		global $wpdb, $bp;

		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_data} WHERE field_id = %d", $field_id ) ) )
			return false;

		return true;
	}

	public static function get_last_updated( $user_id ) {
		global $wpdb, $bp;

		$last_updated = $wpdb->get_var( $wpdb->prepare( "SELECT last_updated FROM {$bp->profile->table_name_data} WHERE user_id = %d ORDER BY last_updated LIMIT 1", $user_id ) );

		return $last_updated;
	}

	public static function delete_data_for_user( $user_id ) {
		global $wpdb, $bp;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_data} WHERE user_id = %d", $user_id ) );
	}

	public static function get_random( $user_id, $exclude_fullname ) {
		global $wpdb, $bp;

		if ( !empty( $exclude_fullname ) )
			$exclude_sql = $wpdb->prepare( " AND pf.id != 1" );

		return $wpdb->get_results( $wpdb->prepare( "SELECT pf.type, pf.name, pd.value FROM {$bp->profile->table_name_data} pd INNER JOIN {$bp->profile->table_name_fields} pf ON pd.field_id = pf.id AND pd.user_id = %d {$exclude_sql} ORDER BY RAND() LIMIT 1", $user_id ) );
	}

	public static function get_fullname( $user_id = 0 ) {

		if ( empty( $user_id ) )
			$user_id = bp_displayed_user_id();

		$field_name = bp_xprofile_fullname_field_name();
		$data       = xprofile_get_field_data( $field_name, $user_id );

		return $data[$field_name];
	}
}
