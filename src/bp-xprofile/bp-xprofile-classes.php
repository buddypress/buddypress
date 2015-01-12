<?php

/**
 * BuddyPress XProfile Classes
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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

		$group = wp_cache_get( 'xprofile_group_' . $this->id, 'bp' );

		if ( false === $group ) {
			$group = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_groups} WHERE id = %d", $id ) );
		}

		if ( empty( $group ) ) {
			return false;
		}

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

		/**
		 * Fires before the current group instance gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyPress (1.0.0)
		 *
		 * @param BP_XProfile_Group Current instance of the group being saved. Passed by reference.
		 */
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

		/**
		 * Fires after the current group instance gets saved.
		 *
		 * @since BuddyPress (1.0.0)
		 *
		 * @param BP_XProfile_Group Current instance of the group being saved. Passed by reference.
		 */
		do_action_ref_array( 'xprofile_group_after_save', array( &$this ) );

		return $this->id;
	}

	public function delete() {
		global $wpdb, $bp;

		if ( empty( $this->can_delete ) )
			return false;

		/**
		 * Fires before the current group instance gets deleted.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param BP_XProfile_Group Current instance of the group being deleted. Passed by reference.
		 */
		do_action_ref_array( 'xprofile_group_before_delete', array( &$this ) );

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

			/**
			 * Fires after the current group instance gets deleted.
			 *
			 * @since BuddyPress (2.0.0)
			 *
			 * @param BP_XProfile_Group Current instance of the group being deleted. Passed by reference.
			 */
			do_action_ref_array( 'xprofile_group_after_delete', array( &$this ) );

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
	 * @param array $args {
	 *	Array of optional arguments:
	 *	@type int $profile_group_id Limit results to a single profile
	 *	      group.
	 *      @type int $user_id Required if you want to load a specific
	 *            user's data. Default: displayed user's ID.
	 *      @type bool $hide_empty_groups True to hide groups that don't
	 *            have any fields. Default: false.
	 *	@type bool $hide_empty_fields True to hide fields where the
	 *	      user has not provided data. Default: false.
	 *      @type bool $fetch_fields Whether to fetch each group's fields.
	 *            Default: false.
	 *      @type bool $fetch_field_data Whether to fetch data for each
	 *            field. Requires a $user_id. Default: false.
	 *      @type array $exclude_groups Comma-separated list or array of
	 *            group IDs to exclude.
	 *      @type array $exclude_fields Comma-separated list or array of
	 *            field IDs to exclude.
	 *      @type bool $update_meta_cache Whether to pre-fetch xprofilemeta
	 *            for all retrieved groups, fields, and data. Default: true.
	 * }
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
			'exclude_fields'         => false,
			'update_meta_cache'      => true,
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		// Keep track of object IDs for cache-priming
		$object_ids = array(
			'group' => array(),
			'field' => array(),
			'data'  => array(),
		);

		$where_sql = '';

		if ( ! empty( $profile_group_id ) ) {
			$where_sql = $wpdb->prepare( 'WHERE g.id = %d', $profile_group_id );
		} elseif ( $exclude_groups ) {
			$exclude_groups = join( ',', wp_parse_id_list( $exclude_groups ) );
			$where_sql = "WHERE g.id NOT IN ({$exclude_groups})";
		}

		if ( ! empty( $hide_empty_groups ) ) {
			$group_ids = $wpdb->get_col( "SELECT DISTINCT g.id FROM {$bp->profile->table_name_groups} g INNER JOIN {$bp->profile->table_name_fields} f ON g.id = f.group_id {$where_sql} ORDER BY g.group_order ASC" );
		} else {
			$group_ids = $wpdb->get_col( "SELECT DISTINCT g.id FROM {$bp->profile->table_name_groups} g {$where_sql} ORDER BY g.group_order ASC" );
		}

		$groups = self::get_group_data( $group_ids );

		if ( empty( $fetch_fields ) )
			return $groups;

		// Get the group ids
		$group_ids = array();
		foreach( (array) $groups as $group ) {
			$group_ids[] = $group->id;
		}

		// Store for meta cache priming
		$object_ids['group'] = $group_ids;

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

		// Store field IDs for meta cache priming
		$object_ids['field'] = wp_list_pluck( $fields, 'id' );

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
				$field_data = BP_XProfile_ProfileData::get_data_for_user( $user_id, $field_ids );
			}

			// Remove data-less fields, if necessary
			if ( !empty( $hide_empty_fields ) && ! empty( $field_ids ) && ! empty( $field_data ) ) {

				// Loop through the results and find the fields that have data.
				foreach( (array) $field_data as $data ) {

					// Empty fields may contain a serialized empty array
					$maybe_value = maybe_unserialize( $data->value );

					// Valid field values of 0 or '0' get caught by empty(), so we have an extra check for these. See #BP5731
					if ( ( ! empty( $maybe_value ) || '0' == $maybe_value ) && false !== $key = array_search( $data->field_id, $field_ids ) ) {

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

					// Loop through the data in each field
					foreach( (array) $field_data as $data ) {

						// Assign correct data value to the field
						if ( $field->id == $data->field_id ) {
							$fields[$field_key]->data        = new stdClass;
							$fields[$field_key]->data->value = $data->value;
							$fields[$field_key]->data->id    = $data->id;
						}

						// Store for meta cache priming
						$object_ids['data'][] = $data->id;
					}
				}
			}
		}

		// Prime the meta cache, if necessary
		if ( $update_meta_cache ) {
			bp_xprofile_update_meta_cache( $object_ids );
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

	/**
	 * Get data about a set of groups, based on IDs.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param array $group_ids Array of IDs.
	 * @return array
	 */
	protected static function get_group_data( $group_ids ) {
		global $wpdb;

		// Bail if no group IDs are passed
		if ( empty( $group_ids ) ) {
			return array();
		}

		$groups        = array();
		$uncached_gids = array();

		foreach ( $group_ids as $group_id ) {

			// If cached data is found, use it
			if ( $group_data = wp_cache_get( 'xprofile_group_' . $group_id, 'bp' ) ) {
				$groups[ $group_id ] = $group_data;

			// Otherwise leave a placeholder so we don't lose the order
			} else {
				$groups[ $group_id ] = '';

				// Add to the list of items to be queried
				$uncached_gids[] = $group_id;
			}
		}

		// Fetch uncached data from the DB if necessary
		if ( ! empty( $uncached_gids ) ) {
			$uncached_gids_sql = implode( ',', wp_parse_id_list( $uncached_gids ) );

			$bp = buddypress();

			// Fetch data, preserving order
			$queried_gdata = $wpdb->get_results( "SELECT * FROM {$bp->profile->table_name_groups} WHERE id IN ({$uncached_gids_sql}) ORDER BY FIELD( id, {$uncached_gids_sql} )");

			// Put queried data into the placeholders created earlier,
			// and add it to the cache
			foreach ( (array) $queried_gdata as $gdata ) {
				$groups[ $gdata->id ] = $gdata;
				wp_cache_set( 'xprofile_group_' . $gdata->id, $gdata, 'bp' );
			}
		}

		// Reset indexes
		$groups = array_values( $groups );

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

		if ( !is_numeric( $position ) ) {
			return false;
		}

		// purge profile field group cache
		wp_cache_delete( 'xprofile_groups_inc_empty', 'bp' );

		return $wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_groups} SET group_order = %d WHERE id = %d", $position, $field_group_id ) );
	}

	/**
	 * Fetch the field visibility level for the fields returned by the query
	 *
	 * @since BuddyPress (1.6.0)
	 *
	 * @param int $user_id The profile owner's user_id
	 * @param array $fields The database results returned by the get() query
	 * @return array $fields The database results, with field_visibility added
	 */
	public static function fetch_visibility_level( $user_id = 0, $fields = array() ) {

		// Get the user's visibility level preferences
		$visibility_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );

		foreach( (array) $fields as $key => $field ) {

			// Does the admin allow this field to be customized?
			$allow_custom = 'disabled' !== bp_xprofile_get_meta( $field->id, 'field', 'allow_custom_visibility' );

			// Look to see if the user has set the visibility for this field
			if ( $allow_custom && isset( $visibility_levels[$field->id] ) ) {
				$field_visibility = $visibility_levels[$field->id];

			// If no admin-set default is saved, fall back on a global default
			} else {
				$fallback_visibility = bp_xprofile_get_meta( $field->id, 'field', 'default_visibility' );

				/**
				 * Filters the XProfile default visibility level for a field.
				 *
				 * @since BuddyPress (1.6.0)
				 *
				 * @param string $value Default visibility value.
				 */
				$field_visibility = ! empty( $fallback_visibility ) ? $fallback_visibility : apply_filters( 'bp_xprofile_default_visibility_level', 'public' );
			}

			$fields[$key]->visibility_level = $field_visibility;
		}

		return $fields;
	}

	/**
	 * Fetch the admin-set preferences for all fields.
	 *
	 * @since BuddyPress (1.6.0)
	 *
	 * @return array $default_visibility_levels An array, keyed by
	 *         field_id, of default visibility level + allow_custom
	 *         (whether the admin allows this field to be set by user)
	 */
	public static function fetch_default_visibility_levels() {
		global $wpdb, $bp;

		$default_visibility_levels = wp_cache_get( 'xprofile_default_visibility_levels', 'bp' );

		if ( false === $default_visibility_levels ) {
			$levels = $wpdb->get_results( "SELECT object_id, meta_key, meta_value FROM {$bp->profile->table_name_meta} WHERE object_type = 'field' AND ( meta_key = 'default_visibility' OR meta_key = 'allow_custom_visibility' )" );

			// Arrange so that the field id is the key and the visibility level the value
			$default_visibility_levels = array();
			foreach ( $levels as $level ) {
				if ( 'default_visibility' == $level->meta_key ) {
					$default_visibility_levels[ $level->object_id ]['default'] = $level->meta_value;
				} elseif ( 'allow_custom_visibility' == $level->meta_key ) {
					$default_visibility_levels[ $level->object_id ]['allow_custom'] = $level->meta_value;
				}
			}

			wp_cache_set( 'xprofile_default_visibility_levels', $default_visibility_levels, 'bp' );
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

			<form id="bp-xprofile-add-field-group" action="<?php echo esc_url( $action ); ?>" method="post">
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">
							<div id="titlediv">
								<div id="titlewrap">
									<label id="title-prompt-text" for="title"><?php esc_html_e( 'Field Group Name', 'buddypress') ?></label>
									<input type="text" name="group_name" id="title" value="<?php echo esc_attr( $this->name ); ?>" autocomplete="off" />
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

								<?php

								/**
								 * Fires before XProfile Group submit metabox.
								 *
								 * @since BuddyPress (2.1.0)
								 *
								 * @param BP_XProfile_Group $this Current XProfile group.
								 */
								do_action( 'xprofile_group_before_submitbox', $this );
								?>

								<div id="submitdiv" class="postbox">
									<div id="handlediv"><h3 class="hndle"><?php _e( 'Save', 'buddypress' ); ?></h3></div>
									<div class="inside">
										<div id="submitcomment" class="submitbox">
											<div id="major-publishing-actions">

												<?php

												/**
												 * Fires at the beginning of the XProfile Group publishing actions section.
												 *
												 * @since BuddyPress (2.1.0)
												 *
												 * @param BP_XProfile_Group $this Current XProfile group.
												 */
												do_action( 'xprofile_group_submitbox_start', $this );
												?>

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

								<?php

								/**
								 * Fires after XProfile Group submit metabox.
								 *
								 * @since BuddyPress (2.1.0)
								 *
								 * @param BP_XProfile_Group $this Current XProfile group.
								 */
								do_action( 'xprofile_group_after_submitbox', $this );
								?>
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

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_fields} WHERE name = %s AND parent_id = 0", $field_name ) );
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
												<input type="submit" value="<?php esc_attr_e( 'Save', 'buddypress' ); ?>" name="saveField" id="saveField" style="font-weight: bold" class="button-primary" />
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

		$cache_key   = "{$user_id}:{$field_id}";
		$profiledata = wp_cache_get( $cache_key, 'bp_xprofile_data' );

		if ( false === $profiledata ) {
			$sql         = $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id = %d", $field_id, $user_id );
			$profiledata = $wpdb->get_row( $sql );

			if ( $profiledata ) {
				wp_cache_set( $cache_key, $profiledata, 'bp_xprofile_data' );
			}
		}

		if ( $profiledata ) {
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

		// Check cache first
		$cache_key = "{$this->user_id}:{$this->field_id}";
		$cached    = wp_cache_get( $cache_key, 'bp_xprofile_data' );

		if ( $cached && ! empty( $cached->id ) ) {
			$retval = true;
		} else {
			$retval = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_data} WHERE user_id = %d AND field_id = %d", $this->user_id, $this->field_id ) );
		}

		/**
		 * Filters whether or not data already exists for the user.
		 *
		 * @since BuddyPress (1.2.7)
		 *
		 * @param bool                    $retval Whether or not data already exists.
		 * @param BP_XProfile_ProfileData $this   Instance of the current BP_XProfile_ProfileData class.
		 */
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

		/**
		 * Filters whether or not data is for a valid field
		 *
		 * @since BuddyPress (1.2.7)
		 *
		 * @param bool                    $retval Whether or not data is valid.
		 * @param BP_XProfile_ProfileData $this   Instance of the current BP_XProfile_ProfileData class.
		 */
		return apply_filters_ref_array( 'xprofile_data_is_valid_field', array( (bool)$retval, $this ) );
	}

	public function save() {
		global $wpdb, $bp;

		$this->user_id      = apply_filters( 'xprofile_data_user_id_before_save',      $this->user_id,         $this->id );
		$this->field_id     = apply_filters( 'xprofile_data_field_id_before_save',     $this->field_id,        $this->id );
		$this->value        = apply_filters( 'xprofile_data_value_before_save',        $this->value,           $this->id, true, $this );
		$this->last_updated = apply_filters( 'xprofile_data_last_updated_before_save', bp_core_current_time(), $this->id );

		/**
		 * Fires before the current profile data instance gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyPress (1.0.0)
		 *
		 * @param BP_XProfile_ProfileData $this Current instance of the profile data being saved.
		 */
		do_action_ref_array( 'xprofile_data_before_save', array( $this ) );

		if ( $this->is_valid_field() ) {
			if ( $this->exists() && strlen( trim( $this->value ) ) ) {
				$result   = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_data} SET value = %s, last_updated = %s WHERE user_id = %d AND field_id = %d", $this->value, $this->last_updated, $this->user_id, $this->field_id ) );

			} elseif ( $this->exists() && empty( $this->value ) ) {
				// Data removed, delete the entry.
				$result   = $this->delete();

			} else {
				$result   = $wpdb->query( $wpdb->prepare("INSERT INTO {$bp->profile->table_name_data} (user_id, field_id, value, last_updated) VALUES (%d, %d, %s, %s)", $this->user_id, $this->field_id, $this->value, $this->last_updated ) );
				$this->id = $wpdb->insert_id;
			}

			if ( false === $result )
				return false;

			/**
			 * Fires after the current profile data instance gets saved.
			 *
			 * @since BuddyPress (1.0.0)
			 *
			 * @param BP_XProfile_ProfileData $this Current instance of the profile data being saved.
			 */
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

		/**
		 * Fires before the current profile data instance gets deleted.
		 *
		 * @since BuddyPress (1.9.0)
		 *
		 * @param BP_XProfile_ProfileData $this Current instance of the profile data being deleted.
		 */
		do_action_ref_array( 'xprofile_data_before_delete', array( $this ) );

		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id = %d", $this->field_id, $this->user_id ) ) )
			return false;

		/**
		 * Fires after the current profile data instance gets deleted.
		 *
		 * @since BuddyPress (1.9.0)
		 *
		 * @param BP_XProfile_ProfileData $this Current instance of the profile data being deleted.
		 */
		do_action_ref_array( 'xprofile_data_after_delete', array( $this ) );

		return true;
	}

	/** Static Methods ********************************************************/

	/**
	 * Get a user's profile data for a set of fields.
	 *
	 * @param int $user_id
	 * @param array $field_ids
	 * @return array
	 */
	public static function get_data_for_user( $user_id, $field_ids ) {
		global $wpdb;

		$data = array();

		$uncached_field_ids = bp_xprofile_get_non_cached_field_ids( $user_id, $field_ids, 'bp_xprofile_data' );

		// Prime the cache
		if ( ! empty( $uncached_field_ids ) ) {
			$bp = buddypress();
			$uncached_field_ids_sql = implode( ',', wp_parse_id_list( $uncached_field_ids ) );
			$uncached_data = $wpdb->get_results( $wpdb->prepare( "SELECT id, user_id, field_id, value, last_updated FROM {$bp->profile->table_name_data} WHERE field_id IN ({$uncached_field_ids_sql}) AND user_id = %d", $user_id ) );

			// Rekey
			$queried_data = array();
			foreach ( $uncached_data as $ud ) {
				$d               = new stdClass;
				$d->id           = $ud->id;
				$d->user_id      = $ud->user_id;
				$d->field_id     = $ud->field_id;
				$d->value        = $ud->value;
				$d->last_updated = $ud->last_updated;

				$queried_data[ $ud->field_id ] = $d;
			}

			// Set caches
			foreach ( $uncached_field_ids as $field_id ) {

				$cache_key = "{$user_id}:{$field_id}";

				// If a value was found, cache it
				if ( isset( $queried_data[ $field_id ] ) ) {
					wp_cache_set( $cache_key, $queried_data[ $field_id ], 'bp_xprofile_data' );

				// If no value was found, cache an empty item
				// to avoid future cache misses
				} else {
					$d               = new stdClass;
					$d->id           = '';
					$d->user_id      = '';
					$d->field_id     = $field_id;
					$d->value        = '';
					$d->last_updated = '';

					wp_cache_set( $cache_key, $d, 'bp_xprofile_data' );
				}
			}
		}

		// Now that all items are cached, fetch them
		foreach ( $field_ids as $field_id ) {
			$cache_key = "{$user_id}:{$field_id}";
			$data[]    = wp_cache_get( $cache_key, 'bp_xprofile_data' );
		}

		return $data;
	}

	/**
	 * Get all of the profile information for a specific user.
	 *
	 * @param int $user_id ID of the user.
	 * @return array
	 */
	public static function get_all_for_user( $user_id ) {

		$groups = bp_xprofile_get_groups( array(
			'user_id'                => $user_id,
			'hide_empty_groups'      => true,
			'hide_empty_fields'      => true,
			'fetch_fields'           => true,
			'fetch_field_data'       => true,
		) );

		$profile_data = array();

		if ( ! empty( $groups ) ) {
			$user = new WP_User( $user_id );

			$profile_data['user_login']    = $user->user_login;
			$profile_data['user_nicename'] = $user->user_nicename;
			$profile_data['user_email']    = $user->user_email;

			foreach ( (array) $groups as $group ) {
				if ( empty( $group->fields ) ) {
					continue;
				}

				foreach ( (array) $group->fields as $field ) {
					$profile_data[ $field->name ] = array(
						'field_group_id'   => $group->id,
						'field_group_name' => $group->name,
						'field_id'         => $field->id,
						'field_type'       => $field->type,
						'field_data'       => $field->data->value,
					);
				}
			}
		}

		return $profile_data;
	}

	/**
	 * Get the user's field data id by the id of the xprofile field.
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

			// Check cache first
			$cache_key = "{$user_id}:{$field_id}";
			$fielddata = wp_cache_get( $cache_key, 'bp_xprofile_data' );
			if ( false === $fielddata || empty( $fielddata->id ) ) {
				$fielddata_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id = %d", $field_id, $user_id ) );
			} else {
				$fielddata_id = $fielddata->id;
			}
		}

		return $fielddata_id;
	}

	/**
	 * Get profile field values by field ID and user IDs.
	 *
	 * Supports multiple user IDs.
	 *
	 * @param int $field_id ID of the field.
	 * @param int|array $user_ids ID or IDs of user(s).
	 * @return string|array Single value if a single user is queried,
	 *         otherwise an array of results.
	 */
	public static function get_value_byid( $field_id, $user_ids = null ) {
		global $wpdb, $bp;

		if ( empty( $user_ids ) ) {
			$user_ids = bp_displayed_user_id();
		}

		$is_single = false;
		if ( ! is_array( $user_ids ) ) {
			$user_ids  = array( $user_ids );
			$is_single = true;
		}

		// Assemble uncached IDs
		$uncached_ids = array();
		foreach ( $user_ids as $user_id ) {
			$cache_key = "{$user_id}:{$field_id}";
			if ( false === wp_cache_get( $cache_key, 'bp_xprofile_data' ) ) {
				$uncached_ids[] = $user_id;
			}
		}

		// Prime caches
		if ( ! empty( $uncached_ids ) ) {
			$uncached_ids_sql = implode( ',', $uncached_ids );
			$queried_data = $wpdb->get_results( $wpdb->prepare( "SELECT id, user_id, field_id, value, last_updated FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id IN ({$uncached_ids_sql})", $field_id ) );

			// Rekey
			$qd = array();
			foreach ( $queried_data as $data ) {
				$qd[ $data->user_id ] = $data;
			}

			foreach ( $uncached_ids as $id ) {
				// The value was successfully fetched
				if ( isset( $qd[ $id ] ) ) {
					$d = $qd[ $id ];

				// No data found for the user, so we fake it to
				// avoid cache misses and PHP notices
				} else {
					$d = new stdClass;
					$d->id           = '';
					$d->user_id      = $id;
					$d->field_id     = '';
					$d->value        = '';
					$d->last_updated = '';
				}

				$cache_key = "{$d->user_id}:{$field_id}";
				wp_cache_set( $cache_key, $d, 'bp_xprofile_data' );
			}
		}

		// Now that the cache is primed with all data, fetch it
		$data = array();
		foreach ( $user_ids as $user_id ) {
			$cache_key = "{$user_id}:{$field_id}";
			$data[]    = wp_cache_get( $cache_key, 'bp_xprofile_data' );
		}

		// If a single ID was passed, just return the value
		if ( $is_single ) {
			return $data[0]->value;

		// Otherwise return the whole array
		} else {
			return $data;
		}
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
					} elseif ( !array_key_exists( $fields[$j], $new_values ) ) {
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

		$exclude_sql = ! empty( $exclude_fullname ) ? ' AND pf.id != 1' : '';

		return $wpdb->get_results( $wpdb->prepare( "SELECT pf.type, pf.name, pd.value FROM {$bp->profile->table_name_data} pd INNER JOIN {$bp->profile->table_name_fields} pf ON pd.field_id = pf.id AND pd.user_id = %d {$exclude_sql} ORDER BY RAND() LIMIT 1", $user_id ) );
	}

	public static function get_fullname( $user_id = 0 ) {

		if ( empty( $user_id ) )
			$user_id = bp_displayed_user_id();

		$data = xprofile_get_field_data( bp_xprofile_fullname_field_id(), $user_id );

		return $data[$field_name];
	}
}

/** Field Types ***************************************************************/

/**
 * Datebox xprofile field type.
 *
 * @since BuddyPress (2.0.0)
 */
class BP_XProfile_Field_Type_Datebox extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the datebox field type
	 *
	 * @since BuddyPress (2.0.0)
 	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Single Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Date Selector', 'xprofile field type', 'buddypress' );

		$this->set_format( '/^\d{4}-\d{1,2}-\d{1,2} 00:00:00$/', 'replace' ); // "Y-m-d 00:00:00"

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Datebox class.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param BP_XProfile_Field_Type_Datebox $this Current instance of
		 *                                             the field type datebox.
		 */
		do_action( 'bp_xprofile_field_type_datebox', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of {@link http://dev.w3.org/html5/markup/input.html permitted attributes} that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// user_id is a special optional parameter that we pass to
		// {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		} else {
			$user_id = bp_displayed_user_id();
		}

		$day_r = bp_parse_args( $raw_properties, array(
			'id'   => bp_get_the_profile_field_input_name() . '_day',
			'name' => bp_get_the_profile_field_input_name() . '_day'
		) );

		$month_r = bp_parse_args( $raw_properties, array(
			'id'   => bp_get_the_profile_field_input_name() . '_month',
			'name' => bp_get_the_profile_field_input_name() . '_month'
		) );

		$year_r = bp_parse_args( $raw_properties, array(
			'id'   => bp_get_the_profile_field_input_name() . '_year',
			'name' => bp_get_the_profile_field_input_name() . '_year'
		) ); ?>

		<div class="datebox">

			<label for="<?php bp_the_profile_field_input_name(); ?>_day"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php esc_html_e( '(required)', 'buddypress' ); ?><?php endif; ?></label>
			<?php

			/**
			 * Fires after field label and displays associated errors for the field.
			 *
			 * This is a dynamic hook that is dependent on the associated
			 * field ID. The hooks will be similar to `bp_field_12_errors`
			 * where the 12 is the field ID. Simply replace the 12 with
			 * your needed target ID.
			 *
			 * @since BuddyPress (1.8.0)
			 */
			do_action( bp_get_the_profile_field_errors_action() ); ?>

			<select <?php echo $this->get_edit_field_html_elements( $day_r ); ?>>
				<?php bp_the_profile_field_options( array(
					'type'    => 'day',
					'user_id' => $user_id
				) ); ?>
			</select>

			<select <?php echo $this->get_edit_field_html_elements( $month_r ); ?>>
				<?php bp_the_profile_field_options( array(
					'type'    => 'month',
					'user_id' => $user_id
				) ); ?>
			</select>

			<select <?php echo $this->get_edit_field_html_elements( $year_r ); ?>>
				<?php bp_the_profile_field_options( array(
					'type'    => 'year',
					'user_id' => $user_id
				) ); ?>
			</select>

		</div>
	<?php
	}

	/**
	 * Output the edit field options HTML for this field type.
	 *
	 * BuddyPress considers a field's "options" to be, for example, the items in a selectbox.
	 * These are stored separately in the database, and their templating is handled separately.
	 *
	 * This templating is separate from {@link BP_XProfile_Field_Type::edit_field_html()} because
	 * it's also used in the wp-admin screens when creating new fields, and for backwards compatibility.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_options_html( array $args = array() ) {

		$date       = BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] );
		$day        = 0;
		$month      = 0;
		$year       = 0;
		$html       = '';
		$eng_months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );

		// Set day, month, year defaults
		if ( ! empty( $date ) ) {

			// If Unix timestamp
			if ( is_numeric( $date ) ) {
				$day   = date( 'j', $date );
				$month = date( 'F', $date );
				$year  = date( 'Y', $date );

			// If MySQL timestamp
			} else {
				$day   = mysql2date( 'j', $date );
				$month = mysql2date( 'F', $date, false ); // Not localized, so that selected() works below
				$year  = mysql2date( 'Y', $date );
			}
		}

		// Check for updated posted values, and errors preventing them from
		// being saved first time.
		if ( ! empty( $_POST['field_' . $this->field_obj->id . '_day'] ) ) {
			$new_day = (int) $_POST['field_' . $this->field_obj->id . '_day'];
			$day     = ( $day != $new_day ) ? $new_day : $day;
		}

		if ( ! empty( $_POST['field_' . $this->field_obj->id . '_month'] ) ) {
			if ( in_array( $_POST['field_' . $this->field_obj->id . '_month'], $eng_months ) ) {
				$new_month = $_POST['field_' . $this->field_obj->id . '_month'];
			} else {
				$new_month = $month;
			}

			$month = ( $month !== $new_month ) ? $new_month : $month;
		}

		if ( ! empty( $_POST['field_' . $this->field_obj->id . '_year'] ) ) {
			$new_year = (int) $_POST['field_' . $this->field_obj->id . '_year'];
			$year     = ( $year != $new_year ) ? $new_year : $year;
		}

		// $type will be passed by calling function when needed
		switch ( $args['type'] ) {
			case 'day':
				$html = sprintf( '<option value="" %1$s>%2$s</option>', selected( $day, 0, false ), /* translators: no option picked in select box */ __( '----', 'buddypress' ) );

				for ( $i = 1; $i < 32; ++$i ) {
					$html .= sprintf( '<option value="%1$s" %2$s>%3$s</option>', (int) $i, selected( $day, $i, false ), (int) $i );
				}
			break;

			case 'month':
				$months = array(
					__( 'January',   'buddypress' ),
					__( 'February',  'buddypress' ),
					__( 'March',     'buddypress' ),
					__( 'April',     'buddypress' ),
					__( 'May',       'buddypress' ),
					__( 'June',      'buddypress' ),
					__( 'July',      'buddypress' ),
					__( 'August',    'buddypress' ),
					__( 'September', 'buddypress' ),
					__( 'October',   'buddypress' ),
					__( 'November',  'buddypress' ),
					__( 'December',  'buddypress' )
				);

				$html = sprintf( '<option value="" %1$s>%2$s</option>', selected( $month, 0, false ), /* translators: no option picked in select box */ __( '----', 'buddypress' ) );

				for ( $i = 0; $i < 12; ++$i ) {
					$html .= sprintf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $eng_months[$i] ), selected( $month, $eng_months[$i], false ), $months[$i] );
				}
			break;

			case 'year':
				$html = sprintf( '<option value="" %1$s>%2$s</option>', selected( $year, 0, false ), /* translators: no option picked in select box */ __( '----', 'buddypress' ) );

				for ( $i = 2037; $i > 1901; $i-- ) {
					$html .= sprintf( '<option value="%1$s" %2$s>%3$s</option>', (int) $i, selected( $year, $i, false ), (int) $i );
				}
			break;
		}

		/**
		 * Filters the output for the profile field datebox.
		 *
		 * @since BuddyPress (1.1.0)
		 *
		 * @param string $html  HTML output for the field.
		 * @param string $value Which date type is being rendered for.
		 * @param string $day   Date formatted for the current day.
		 * @param string $month Date formatted for the current month.
		 * @param string $year  Date formatted for the current year.
		 * @param int    $id    ID of the field object being rendered.
		 * @param string $date  Current date.
		 */
		echo apply_filters( 'bp_get_the_profile_field_datebox', $html, $args['type'], $day, $month, $year, $this->field_obj->id, $date );
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_field_html( array $raw_properties = array() ) {

		$day_r = bp_parse_args( $raw_properties, array(
			'id'   => bp_get_the_profile_field_input_name() . '_day',
			'name' => bp_get_the_profile_field_input_name() . '_day'
		) );

		$month_r = bp_parse_args( $raw_properties, array(
			'id'   => bp_get_the_profile_field_input_name() . '_month',
			'name' => bp_get_the_profile_field_input_name() . '_month'
		) );

		$year_r = bp_parse_args( $raw_properties, array(
			'id'   => bp_get_the_profile_field_input_name() . '_year',
			'name' => bp_get_the_profile_field_input_name() . '_year'
		) ); ?>

		<select <?php echo $this->get_edit_field_html_elements( $day_r ); ?>>
			<?php bp_the_profile_field_options( array( 'type' => 'day' ) ); ?>
		</select>

		<select <?php echo $this->get_edit_field_html_elements( $month_r ); ?>>
			<?php bp_the_profile_field_options( array( 'type' => 'month' ) ); ?>
		</select>

		<select <?php echo $this->get_edit_field_html_elements( $year_r ); ?>>
			<?php bp_the_profile_field_options( array( 'type' => 'year' ) ); ?>
		</select>

	<?php
	}

	/**
	 * This method usually outputs HTML for this field type's children options on the wp-admin Profile Fields
	 * "Add Field" and "Edit Field" screens, but for this field type, we don't want it, so it's stubbed out.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {}

	/**
	 * Format Date values for display.
	 *
	 * @since BuddyPress (2.1.0)
	 *
	 * @param string $field_value The date value, as saved in the database.
	 *        Typically, this is a MySQL-formatted date string (Y-m-d H:i:s).
	 * @return string Date formatted by bp_format_time().
	 */
	public static function display_filter( $field_value ) {

		// If Unix timestamp
		if ( ! is_numeric( $field_value ) ) {
			$field_value = strtotime( $field_value );
		}

		return bp_format_time( $field_value, true, false );
	}
}

/**
 * Checkbox xprofile field type.
 *
 * @since BuddyPress (2.0.0)
 */
class BP_XProfile_Field_Type_Checkbox extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the checkbox field type
	 *
	 * @since BuddyPress (2.0.0)
 	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Multi Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Checkboxes', 'xprofile field type', 'buddypress' );

		$this->supports_multiple_defaults = true;
		$this->accepts_null_value         = true;
		$this->supports_options           = true;

		$this->set_format( '/^.+$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Checkbox class.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param BP_XProfile_Field_Type_Checkbox $this Current instance of
		 *                                              the field type checkbox.
		 */
		do_action( 'bp_xprofile_field_type_checkbox', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of {@link http://dev.w3.org/html5/markup/input.checkbox.html permitted attributes} that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// user_id is a special optional parameter that we pass to
		// {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		} else {
			$user_id = bp_displayed_user_id();
		} ?>

		<div class="checkbox">
			<label for="<?php bp_the_profile_field_input_name(); ?>">
				<?php bp_the_profile_field_name(); ?>
				<?php if ( bp_get_the_profile_field_is_required() ) : ?>
					<?php esc_html_e( '(required)', 'buddypress' ); ?>
				<?php endif; ?>
			</label>

			<?php

			/** This action is documented in bp-xprofile/bp-xprofile-classes */
			do_action( bp_get_the_profile_field_errors_action() ); ?>

			<?php bp_the_profile_field_options( array(
				'user_id' => $user_id
			) ); ?>

		</div>

		<?php
	}

	/**
	 * Output the edit field options HTML for this field type.
	 *
	 * BuddyPress considers a field's "options" to be, for example, the items in a selectbox.
	 * These are stored separately in the database, and their templating is handled separately.
	 *
	 * This templating is separate from {@link BP_XProfile_Field_Type::edit_field_html()} because
	 * it's also used in the wp-admin screens when creating new fields, and for backwards compatibility.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_options_html( array $args = array() ) {
		$options       = $this->field_obj->get_children();
		$option_values = BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] );
		$option_values = (array) maybe_unserialize( $option_values );

		$html = '';

		// Check for updated posted values, but errors preventing them from
		// being saved first time
		if ( isset( $_POST['field_' . $this->field_obj->id] ) && $option_values != maybe_serialize( $_POST['field_' . $this->field_obj->id] ) ) {
			if ( ! empty( $_POST['field_' . $this->field_obj->id] ) ) {
				$option_values = array_map( 'sanitize_text_field', $_POST['field_' . $this->field_obj->id] );
			}
		}

		for ( $k = 0, $count = count( $options ); $k < $count; ++$k ) {
			$selected = '';

			// First, check to see whether the user's saved values match the option
			for ( $j = 0, $count_values = count( $option_values ); $j < $count_values; ++$j ) {

				// Run the allowed option name through the before_save filter,
				// so we'll be sure to get a match
				$allowed_options = xprofile_sanitize_data_value_before_save( $options[$k]->name, false, false );

				if ( $option_values[$j] === $allowed_options || in_array( $allowed_options, $option_values ) ) {
					$selected = ' checked="checked"';
					break;
				}
			}

			// If the user has not yet supplied a value for this field, check to
			// see whether there is a default value available
			if ( ! is_array( $option_values ) && empty( $option_values ) && empty( $selected ) && ! empty( $options[$k]->is_default_option ) ) {
				$selected = ' checked="checked"';
			}

			$new_html = sprintf( '<label><input %1$s type="checkbox" name="%2$s" id="%3$s" value="%4$s">%5$s</label>',
				$selected,
				esc_attr( "field_{$this->field_obj->id}[]" ),
				esc_attr( "field_{$options[$k]->id}_{$k}" ),
				esc_attr( stripslashes( $options[$k]->name ) ),
				esc_html( stripslashes( $options[$k]->name ) )
			);

			/**
			 * Filters the HTML output for an individual field options checkbox.
			 *
			 * @since BuddyPress (1.1.0)
			 *
			 * @param string $new_html Label and checkbox input field.
			 * @param object $value    Current option being rendered for.
			 * @param int    $id       ID of the field object being rendered.
			 * @param string $selected Current selected value.
			 * @param string $k        Current index in the foreach loop.
			 */
			$html .= apply_filters( 'bp_get_the_profile_field_options_checkbox', $new_html, $options[$k], $this->field_obj->id, $selected, $k );
		}

		echo $html;
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		bp_the_profile_field_options();
	}

	/**
	 * Output HTML for this field type's children options on the wp-admin Profile Fields "Add Field" and "Edit Field" screens.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		parent::admin_new_field_html( $current_field, 'checkbox' );
	}
}

/**
 * Radio button xprofile field type.
 *
 * @since BuddyPress (2.0.0)
 */
class BP_XProfile_Field_Type_Radiobutton extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the radio button field type
	 *
	 * @since BuddyPress (2.0.0)
 	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Multi Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Radio Buttons', 'xprofile field type', 'buddypress' );

		$this->supports_options = true;

		$this->set_format( '/^.+$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Radiobutton class.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param BP_XProfile_Field_Type_Radiobutton $this Current instance of
		 *                                                 the field type radio button.
		 */
		do_action( 'bp_xprofile_field_type_radiobutton', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of {@link http://dev.w3.org/html5/markup/input.radio.html permitted attributes} that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// user_id is a special optional parameter that we pass to
		// {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		} else {
			$user_id = bp_displayed_user_id();
		} ?>

		<div class="radio">

			<label for="<?php bp_the_profile_field_input_name(); ?>">
				<?php bp_the_profile_field_name(); ?>
				<?php if ( bp_get_the_profile_field_is_required() ) : ?>
					<?php esc_html_e( '(required)', 'buddypress' ); ?>
				<?php endif; ?>
			</label>

			<?php

			/** This action is documented in bp-xprofile/bp-xprofile-classes */
			do_action( bp_get_the_profile_field_errors_action() ); ?>

			<?php bp_the_profile_field_options( array( 'user_id' => $user_id ) );

			if ( ! bp_get_the_profile_field_is_required() ) : ?>

				<a class="clear-value" href="javascript:clear( '<?php echo esc_js( bp_get_the_profile_field_input_name() ); ?>' );">
					<?php esc_html_e( 'Clear', 'buddypress' ); ?>
				</a>

			<?php endif; ?>

		</div>

		<?php
	}

	/**
	 * Output the edit field options HTML for this field type.
	 *
	 * BuddyPress considers a field's "options" to be, for example, the items in a selectbox.
	 * These are stored separately in the database, and their templating is handled separately.
	 *
	 * This templating is separate from {@link BP_XProfile_Field_Type::edit_field_html()} because
	 * it's also used in the wp-admin screens when creating new fields, and for backwards compatibility.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_options_html( array $args = array() ) {
		$option_value = BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] );
		$options      = $this->field_obj->get_children();

		$html = sprintf( '<div id="%s">', esc_attr( 'field_' . $this->field_obj->id ) );

		for ( $k = 0, $count = count( $options ); $k < $count; ++$k ) {

			// Check for updated posted values, but errors preventing them from
			// being saved first time
			if ( isset( $_POST['field_' . $this->field_obj->id] ) && $option_value != $_POST['field_' . $this->field_obj->id] ) {
				if ( ! empty( $_POST['field_' . $this->field_obj->id] ) ) {
					$option_value = sanitize_text_field( $_POST['field_' . $this->field_obj->id] );
				}
			}

			// Run the allowed option name through the before_save filter, so
			// we'll be sure to get a match
			$allowed_options = xprofile_sanitize_data_value_before_save( $options[$k]->name, false, false );
			$selected        = '';

			if ( $option_value === $allowed_options || ( empty( $option_value ) && ! empty( $options[$k]->is_default_option ) ) ) {
				$selected = ' checked="checked"';
			}

			$new_html = sprintf( '<label><input %1$s type="radio" name="%2$s" id="%3$s" value="%4$s">%5$s</label>',
				$selected,
				esc_attr( "field_{$this->field_obj->id}" ),
				esc_attr( "option_{$options[$k]->id}" ),
				esc_attr( stripslashes( $options[$k]->name ) ),
				esc_html( stripslashes( $options[$k]->name ) )
			);

			/**
			 * Filters the HTML output for an individual field options radio button.
			 *
			 * @since BuddyPress (1.1.0)
			 *
			 * @param string $new_html Label and radio input field.
			 * @param object $value    Current option being rendered for.
			 * @param int    $id       ID of the field object being rendered.
			 * @param string $selected Current selected value.
			 * @param string $k        Current index in the foreach loop.
			 */
			$html .= apply_filters( 'bp_get_the_profile_field_options_radio', $new_html, $options[$k], $this->field_obj->id, $selected, $k );
		}

		echo $html . '</div>';
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		bp_the_profile_field_options();

		if ( bp_get_the_profile_field_is_required() ) {
			return;
		} ?>

		<a class="clear-value" href="javascript:clear( '<?php echo esc_js( bp_get_the_profile_field_input_name() ); ?>' );">
			<?php esc_html_e( 'Clear', 'buddypress' ); ?>
		</a>

		<?php
	}

	/**
	 * Output HTML for this field type's children options on the wp-admin Profile Fields "Add Field" and "Edit Field" screens.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		parent::admin_new_field_html( $current_field, 'radio' );
	}
}

/**
 * Multi-selectbox xprofile field type.
 *
 * @since BuddyPress (2.0.0)
 */
class BP_XProfile_Field_Type_Multiselectbox extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the multi-selectbox field type
	 *
	 * @since BuddyPress (2.0.0)
 	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Multi Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Multi Select Box', 'xprofile field type', 'buddypress' );

		$this->supports_multiple_defaults = true;
		$this->accepts_null_value         = true;
		$this->supports_options           = true;

		$this->set_format( '/^.+$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Multiselectbox class.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param BP_XProfile_Field_Type_Multiselectbox $this Current instance of
		 *                                                    the field type multiple select box.
		 */
		do_action( 'bp_xprofile_field_type_multiselectbox', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of {@link http://dev.w3.org/html5/markup/select.html permitted attributes} that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// user_id is a special optional parameter that we pass to
		// {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		} else {
			$user_id = bp_displayed_user_id();
		}

		$r = bp_parse_args( $raw_properties, array(
			'multiple' => 'multiple',
			'id'       => bp_get_the_profile_field_input_name() . '[]',
			'name'     => bp_get_the_profile_field_input_name() . '[]',
		) ); ?>

		<label for="<?php bp_the_profile_field_input_name(); ?>[]"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ); ?><?php endif; ?></label>

		<?php

		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() ); ?>

		<select <?php echo $this->get_edit_field_html_elements( $r ); ?>>
			<?php bp_the_profile_field_options( array(
				'user_id' => $user_id
			) ); ?>
		</select>

		<?php if ( ! bp_get_the_profile_field_is_required() ) : ?>

			<a class="clear-value" href="javascript:clear( '<?php echo esc_js( bp_get_the_profile_field_input_name() ); ?>[]' );">
				<?php esc_html_e( 'Clear', 'buddypress' ); ?>
			</a>

		<?php endif; ?>
	<?php
	}

	/**
	 * Output the edit field options HTML for this field type.
	 *
	 * BuddyPress considers a field's "options" to be, for example, the items in a selectbox.
	 * These are stored separately in the database, and their templating is handled separately.
	 *
	 * This templating is separate from {@link BP_XProfile_Field_Type::edit_field_html()} because
	 * it's also used in the wp-admin screens when creating new fields, and for backwards compatibility.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_options_html( array $args = array() ) {
		$original_option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] ) );

		$options = $this->field_obj->get_children();
		$html    = '';

		if ( empty( $original_option_values ) && ! empty( $_POST['field_' . $this->field_obj->id] ) ) {
			$original_option_values = sanitize_text_field( $_POST['field_' . $this->field_obj->id] );
		}

		$option_values = (array) $original_option_values;
		for ( $k = 0, $count = count( $options ); $k < $count; ++$k ) {
			$selected = '';

			// Check for updated posted values, but errors preventing them from
			// being saved first time
			foreach( $option_values as $i => $option_value ) {
				if ( isset( $_POST['field_' . $this->field_obj->id] ) && $_POST['field_' . $this->field_obj->id][$i] != $option_value ) {
					if ( ! empty( $_POST['field_' . $this->field_obj->id][$i] ) ) {
						$option_values[] = sanitize_text_field( $_POST['field_' . $this->field_obj->id][$i] );
					}
				}
			}

			// Run the allowed option name through the before_save filter, so
			// we'll be sure to get a match
			$allowed_options = xprofile_sanitize_data_value_before_save( $options[$k]->name, false, false );

			// First, check to see whether the user-entered value matches
			if ( in_array( $allowed_options, $option_values ) ) {
				$selected = ' selected="selected"';
			}

			// Then, if the user has not provided a value, check for defaults
			if ( ! is_array( $original_option_values ) && empty( $option_values ) && ! empty( $options[$k]->is_default_option ) ) {
				$selected = ' selected="selected"';
			}

			/**
			 * Filters the HTML output for options in a multiselect input.
			 *
			 * @since BuddyPress (1.5.0)
			 *
			 * @param string $value    Option tag for current value being rendered.
			 * @param object $value    Current option being rendered for.
			 * @param int    $id       ID of the field object being rendered.
			 * @param string $selected Current selected value.
			 * @param string $k        Current index in the foreach loop.
			 */
			$html .= apply_filters( 'bp_get_the_profile_field_options_multiselect', '<option' . $selected . ' value="' . esc_attr( stripslashes( $options[$k]->name ) ) . '">' . esc_html( stripslashes( $options[$k]->name ) ) . '</option>', $options[$k], $this->field_obj->id, $selected, $k );
		}

		echo $html;
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		$r = bp_parse_args( $raw_properties, array(
			'multiple' => 'multiple'
		) ); ?>

		<select <?php echo $this->get_edit_field_html_elements( $r ); ?>>
			<?php bp_the_profile_field_options(); ?>
		</select>

		<?php
	}

	/**
	 * Output HTML for this field type's children options on the wp-admin Profile Fields "Add Field" and "Edit Field" screens.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		parent::admin_new_field_html( $current_field, 'checkbox' );
	}
}

/**
 * Selectbox xprofile field type.
 *
 * @since BuddyPress (2.0.0)
 */
class BP_XProfile_Field_Type_Selectbox extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the selectbox field type
	 *
	 * @since BuddyPress (2.0.0)
 	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Multi Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Drop Down Select Box', 'xprofile field type', 'buddypress' );

		$this->supports_options = true;

		$this->set_format( '/^.+$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Selectbox class.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param BP_XProfile_Field_Type_Selectbox $this Current instance of
		 *                                               the field type select box.
		 */
		do_action( 'bp_xprofile_field_type_selectbox', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of {@link http://dev.w3.org/html5/markup/select.html permitted attributes} that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// user_id is a special optional parameter that we pass to
		// {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		} else {
			$user_id = bp_displayed_user_id();
		} ?>

		<label for="<?php bp_the_profile_field_input_name(); ?>">
			<?php bp_the_profile_field_name(); ?>
			<?php if ( bp_get_the_profile_field_is_required() ) : ?>
				<?php esc_html_e( '(required)', 'buddypress' ); ?>
			<?php endif; ?>
		</label>

		<?php

		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() ); ?>

		<select <?php echo $this->get_edit_field_html_elements( $raw_properties ); ?>>
			<?php bp_the_profile_field_options( array( 'user_id' => $user_id ) ); ?>
		</select>

		<?php
	}

	/**
	 * Output the edit field options HTML for this field type.
	 *
	 * BuddyPress considers a field's "options" to be, for example, the items in a selectbox.
	 * These are stored separately in the database, and their templating is handled separately.
	 *
	 * This templating is separate from {@link BP_XProfile_Field_Type::edit_field_html()} because
	 * it's also used in the wp-admin screens when creating new fields, and for backwards compatibility.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_options_html( array $args = array() ) {
		$original_option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] ) );

		$options = $this->field_obj->get_children();
		$html    = '<option value="">' . /* translators: no option picked in select box */ esc_html__( '----', 'buddypress' ) . '</option>';

		if ( empty( $original_option_values ) && !empty( $_POST['field_' . $this->field_obj->id] ) ) {
			$original_option_values = sanitize_text_field(  $_POST['field_' . $this->field_obj->id] );
		}

		$option_values = (array) $original_option_values;
		for ( $k = 0, $count = count( $options ); $k < $count; ++$k ) {
			$selected = '';

			// Check for updated posted values, but errors preventing them from
			// being saved first time
			foreach( $option_values as $i => $option_value ) {
				if ( isset( $_POST['field_' . $this->field_obj->id] ) && $_POST['field_' . $this->field_obj->id] != $option_value ) {
					if ( ! empty( $_POST['field_' . $this->field_obj->id] ) ) {
						$option_values[$i] = sanitize_text_field( $_POST['field_' . $this->field_obj->id] );
					}
				}
			}

			// Run the allowed option name through the before_save filter, so
			// we'll be sure to get a match
			$allowed_options = xprofile_sanitize_data_value_before_save( $options[$k]->name, false, false );

			// First, check to see whether the user-entered value matches
			if ( in_array( $allowed_options, $option_values ) ) {
				$selected = ' selected="selected"';
			}

			// Then, if the user has not provided a value, check for defaults
			if ( ! is_array( $original_option_values ) && empty( $option_values ) && $options[$k]->is_default_option ) {
				$selected = ' selected="selected"';
			}

			/**
			 * Filters the HTML output for options in a select input.
			 *
			 * @since BuddyPress (1.1.0)
			 *
			 * @param string $value    Option tag for current value being rendered.
			 * @param object $value    Current option being rendered for.
			 * @param int    $id       ID of the field object being rendered.
			 * @param string $selected Current selected value.
			 * @param string $k        Current index in the foreach loop.
			 */
			$html .= apply_filters( 'bp_get_the_profile_field_options_select', '<option' . $selected . ' value="' . esc_attr( stripslashes( $options[$k]->name ) ) . '">' . esc_html( stripslashes( $options[$k]->name ) ) . '</option>', $options[$k], $this->field_obj->id, $selected, $k );
		}

		echo $html;
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		?>

		<select <?php echo $this->get_edit_field_html_elements( $raw_properties ); ?>>
			<?php bp_the_profile_field_options(); ?>
		</select>

		<?php
	}

	/**
	 * Output HTML for this field type's children options on the wp-admin Profile Fields "Add Field" and "Edit Field" screens.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		parent::admin_new_field_html( $current_field, 'radio' );
	}
}

/**
 * Textarea xprofile field type.
 *
 * @since BuddyPress (2.0.0)
 */
class BP_XProfile_Field_Type_Textarea extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the textarea field type
	 *
	 * @since BuddyPress (2.0.0)
 	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Single Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Multi-line Text Area', 'xprofile field type', 'buddypress' );

		$this->set_format( '/^.*$/m', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Textarea class.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param BP_XProfile_Field_Type_Textarea $this Current instance of
		 *                                              the field type textarea.
		 */
		do_action( 'bp_xprofile_field_type_textarea', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of {@link http://dev.w3.org/html5/markup/textarea.html permitted attributes} that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// user_id is a special optional parameter that certain other fields
		// types pass to {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			unset( $raw_properties['user_id'] );
		}

		$r = bp_parse_args( $raw_properties, array(
			'cols' => 40,
			'rows' => 5,
		) ); ?>

		<label for="<?php bp_the_profile_field_input_name(); ?>">
			<?php bp_the_profile_field_name(); ?>
			<?php if ( bp_get_the_profile_field_is_required() ) : ?>
				<?php esc_html_e( '(required)', 'buddypress' ); ?>
			<?php endif; ?>
		</label>

		<?php

		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() ); ?>

		<textarea <?php echo $this->get_edit_field_html_elements( $r ); ?>><?php bp_the_profile_field_edit_value(); ?></textarea>

		<?php
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		$r = bp_parse_args( $raw_properties, array(
			'cols' => 40,
			'rows' => 5,
		) ); ?>

		<textarea <?php echo $this->get_edit_field_html_elements( $r ); ?>></textarea>

		<?php
	}

	/**
	 * This method usually outputs HTML for this field type's children options on the wp-admin Profile Fields
	 * "Add Field" and "Edit Field" screens, but for this field type, we don't want it, so it's stubbed out.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {}
}

/**
 * Textbox xprofile field type.
 *
 * @since BuddyPress (2.0.0)
 */
class BP_XProfile_Field_Type_Textbox extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the textbox field type
	 *
	 * @since BuddyPress (2.0.0)
 	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Single Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Text Box', 'xprofile field type', 'buddypress' );

		$this->set_format( '/^.*$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Textbox class.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param BP_XProfile_Field_Type_Textbox $this Current instance of
		 *                                             the field type text box.
		 */
		do_action( 'bp_xprofile_field_type_textbox', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of {@link http://dev.w3.org/html5/markup/input.text.html permitted attributes} that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// user_id is a special optional parameter that certain other fields
		// types pass to {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			unset( $raw_properties['user_id'] );
		}

		$r = bp_parse_args( $raw_properties, array(
			'type'  => 'text',
			'value' => bp_get_the_profile_field_edit_value(),
		) ); ?>

		<label for="<?php bp_the_profile_field_input_name(); ?>">
			<?php bp_the_profile_field_name(); ?>
			<?php if ( bp_get_the_profile_field_is_required() ) : ?>
				<?php esc_html_e( '(required)', 'buddypress' ); ?>
			<?php endif; ?>
		</label>

		<?php

		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() ); ?>

		<input <?php echo $this->get_edit_field_html_elements( $r ); ?>>

		<?php
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_field_html( array $raw_properties = array() ) {

		$r = bp_parse_args( $raw_properties, array(
			'type' => 'text'
		) ); ?>

		<input <?php echo $this->get_edit_field_html_elements( $r ); ?>>

		<?php
	}

	/**
	 * This method usually outputs HTML for this field type's children options on the wp-admin Profile Fields
	 * "Add Field" and "Edit Field" screens, but for this field type, we don't want it, so it's stubbed out.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {}
}

/**
 * Number xprofile field type.
 *
 * @since BuddyPress (2.0.0)
 */
class BP_XProfile_Field_Type_Number extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the number field type
	 *
	 * @since BuddyPress (2.0.0)
 	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Single Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Number', 'xprofile field type', 'buddypress' );

		$this->set_format( '/^\d+|-\d+$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Number class.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param BP_XProfile_Field_Type_Number $this Current instance of
		 *                                            the field type number.
		 */
		do_action( 'bp_xprofile_field_type_number', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of {@link http://dev.w3.org/html5/markup/input.number.html permitted attributes} that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// user_id is a special optional parameter that certain other fields
		// types pass to {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			unset( $raw_properties['user_id'] );
		}

		$r = bp_parse_args( $raw_properties, array(
			'type'  => 'number',
			'value' =>  bp_get_the_profile_field_edit_value()
		) ); ?>

		<label for="<?php bp_the_profile_field_input_name(); ?>">
			<?php bp_the_profile_field_name(); ?>
			<?php if ( bp_get_the_profile_field_is_required() ) : ?>
				<?php esc_html_e( '(required)', 'buddypress' ); ?>
			<?php endif; ?>
		</label>

		<?php

		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() ); ?>

		<input <?php echo $this->get_edit_field_html_elements( $r ); ?>>

		<?php
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		$r = bp_parse_args( $raw_properties, array(
			'type' => 'number'
		) ); ?>

		<input <?php echo $this->get_edit_field_html_elements( $r ); ?>>
	<?php
	}

	/**
	 * This method usually outputs HTML for this field type's children options on the wp-admin Profile Fields
	 * "Add Field" and "Edit Field" screens, but for this field type, we don't want it, so it's stubbed out.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {}
}

/**
 * URL xprofile field type.
 *
 * @since BuddyPress (2.1.0)
 */
class BP_XProfile_Field_Type_URL extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the URL field type
	 *
	 * @since BuddyPress (2.1.0)
	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Single Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'URL', 'xprofile field type', 'buddypress' );

		$this->set_format( '_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$_iuS', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_URL class.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param BP_XProfile_Field_Type_URL $this Current instance of
		 *                                         the field type URL.
		 */
		do_action( 'bp_xprofile_field_type_url', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of
	 *        {@link http://dev.w3.org/html5/markup/input.number.html permitted attributes}
	 *        that you want to add.
	 * @since BuddyPress (2.1.0)
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// `user_id` is a special optional parameter that certain other
		// fields types pass to {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			unset( $raw_properties['user_id'] );
		}

		$r = bp_parse_args( $raw_properties, array(
			'type'      => 'text',
			'inputmode' => 'url',
			'value'     => esc_url( bp_get_the_profile_field_edit_value() ),
		) ); ?>

		<label for="<?php bp_the_profile_field_input_name(); ?>">
			<?php bp_the_profile_field_name(); ?>
			<?php if ( bp_get_the_profile_field_is_required() ) : ?>
				<?php esc_html_e( '(required)', 'buddypress' ); ?>
			<?php endif; ?>
		</label>

		<?php

		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() ); ?>

		<input <?php echo $this->get_edit_field_html_elements( $r ); ?>>

		<?php
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted
	 *        attributes that you want to add.
	 * @since BuddyPress (2.1.0)
	 */
	public function admin_field_html( array $raw_properties = array() ) {

		$r = bp_parse_args( $raw_properties, array(
			'type' => 'url'
		) ); ?>

		<input <?php echo $this->get_edit_field_html_elements( $r ); ?>>

		<?php
	}

	/**
	 * This method usually outputs HTML for this field type's children options
	 * on the wp-admin Profile Fields "Add Field" and "Edit Field" screens, but
	 * for this field type, we don't want it, so it's stubbed out.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress (2.1.0)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {}

	/**
	 * Modify submitted URL values before validation.
	 *
	 * The URL validation regex requires a http(s) protocol, so that all
	 * values saved in the database are fully-formed URLs. However, we
	 * still want to allow users to enter URLs without a protocol, for a
	 * better user experience. So we catch submitted URL values, and if
	 * the protocol is missing, we prepend 'http://' before passing to
	 * is_valid().
	 *
	 * @since BuddyPress (2.1.0)
	 *
	 * @param string $submitted_value Raw value submitted by the user.
	 * @return string
	 */
	public static function pre_validate_filter( $submitted_value = '' ) {

		// Allow empty URL values
		if ( empty( $submitted_value ) ) {
			return '';
		}

		// Run some checks on the submitted value
		if ( false === strpos( $submitted_value, ':'  )
		     && substr( $submitted_value, 0, 1 ) !== '/'
		     && substr( $submitted_value, 0, 1 ) !== '#'
		     && ! preg_match( '/^[a-z0-9-]+?\.php/i', $submitted_value )
		) {
			$submitted_value = 'http://' . $submitted_value;
		}

		return $submitted_value;
	}

	/**
	 * Format URL values for display.
	 *
	 * @since BuddyPress (2.1.0)
	 *
	 * @param string $field_value The URL value, as saved in the database.
	 * @return string URL converted to a link.
	 */
	public static function display_filter( $field_value ) {
		$link      = strip_tags( $field_value );
		$no_scheme = preg_replace( '#^https?://#', '', rtrim( $link, '/' ) );
		$url_text  = str_replace( $link, $no_scheme, $field_value );
		return '<a href="' . esc_url( $field_value ) . '" rel="nofollow">' . esc_html( $url_text ) . '</a>';
	}
}

/**
 * A placeholder xprofile field type. Doesn't do anything.
 *
 * Used if an existing field has an unknown type (e.g. one provided by a missing third-party plugin).
 *
 * @since BuddyPress (2.0.1)
 */
class BP_XProfile_Field_Type_Placeholder extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the placeholder field type.
	 *
	 * @since BuddyPress (2.0.1)
	 */
	public function __construct() {
		$this->set_format( '/.*/', 'replace' );
	}

	/**
	 * Prevent any HTML being output for this field type.
	 *
	 * @param array $raw_properties Optional key/value array of {@link http://dev.w3.org/html5/markup/input.text.html permitted attributes} that you want to add.
	 * @since BuddyPress (2.0.1)
	 */
	public function edit_field_html( array $raw_properties = array() ) {
	}

	/**
	 * Prevent any HTML being output for this field type.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress (2.0.1)
	 */
	public function admin_field_html( array $raw_properties = array() ) {
	}

	/**
	 * Prevent any HTML being output for this field type.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress (2.0.1)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {}
}

/**
 * Represents a type of XProfile field and holds meta information about the type of value that it accepts.
 *
 * @since BuddyPress (2.0.0)
 */
abstract class BP_XProfile_Field_Type {

	/**
	 * @since BuddyPress (2.0.0)
	 * @var array Field type validation regexes
	 */
	protected $validation_regex = array();

	/**
	 * @since BuddyPress (2.0.0)
	 * @var array Field type whitelisted values
	 */
	protected $validation_whitelist = array();

	/**
	 * @since BuddyPress (2.0.0)
	 * @var string The name of this field type
	 */
	public $name = '';

	/**
	 * The name of the category that this field type should be grouped with. Used on the [Users > Profile Fields] screen in wp-admin.
	 *
	 * @since BuddyPress (2.0.0)
	 * @var string
	 */
	public $category = '';

	/**
	 * @since BuddyPress (2.0.0)
	 * @var bool If this is set, allow BP to store null/empty values for this field type.
	 */
	public $accepts_null_value = false;

	/**
	 * If this is set, BP will set this field type's validation whitelist from the field's options (e.g checkbox, selectbox).
	 *
	 * @since BuddyPress (2.0.0)
	 * @var bool Does this field support options? e.g. selectbox, radio buttons, etc.
	 */
	public $supports_options = false;

	/**
	 * @since BuddyPress (2.0.0)
	 * @var bool Does this field type support multiple options being set as default values? e.g. multiselectbox, checkbox.
	 */
	public $supports_multiple_defaults = false;

	/**
	 * @since BuddyPress (2.0.0)
	 * @var BP_XProfile_Field If this object is created by instantiating a {@link BP_XProfile_Field}, this is a reference back to that object.
	 */
	public $field_obj = null;

	/**
	 * Constructor
	 *
	 * @since BuddyPress (2.0.0)
	 */
	public function __construct() {

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type class.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param BP_XProfile_Field_Type $this Current instance of
		 *                                     the field type class.
		 */
		do_action( 'bp_xprofile_field_type', $this );
	}

	/**
	 * Set a regex that profile data will be asserted against.
	 *
	 * You can call this method multiple times to set multiple formats. When validation is performed,
	 * it's successful as long as the new value matches any one of the registered formats.
	 *
	 * @param string $format Regex string
	 * @param string $replace_format Optional; if 'replace', replaces the format instead of adding to it. Defaults to 'add'.
	 * @return BP_XProfile_Field_Type
	 * @since BuddyPress (2.0.0)
	 */
	public function set_format( $format, $replace_format = 'add' ) {

		/**
		 * Filters the regex format for the field type.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param string                 $format         Regex string.
		 * @param string                 $replace_format Optional replace format If "replace", replaces the
		 *                                               format instead of adding to it. Defaults to "add".
		 * @param BP_XProfile_Field_Type $this           Current instance of the BP_XProfile_Field_Type class.
		 */
		$format = apply_filters( 'bp_xprofile_field_type_set_format', $format, $replace_format, $this );

		if ( 'add' === $replace_format ) {
			$this->validation_regex[] = $format;
		} elseif ( 'replace' === $replace_format ) {
			$this->validation_regex = array( $format );
		}

		return $this;
	}

	/**
	 * Add a value to this type's whitelist that profile data will be asserted against.
	 *
	 * You can call this method multiple times to set multiple formats. When validation is performed,
	 * it's successful as long as the new value matches any one of the registered formats.
	 *
	 * @param string|array $values
	 * @return BP_XProfile_Field_Type
	 * @since BuddyPress (2.0.0)
	 */
	public function set_whitelist_values( $values ) {
		foreach ( (array) $values as $value ) {

			/**
			 * Filters values for field type's whitelist that profile data will be asserted against.
			 *
			 * @since BuddyPress (2.0.0)
			 *
			 * @param string                 $value  Field value.
			 * @param array                  $values Original array of values.
			 * @param BP_XProfile_Field_Type $this   Current instance of the BP_XProfile_Field_Type class.
			 */
			$this->validation_whitelist[] = apply_filters( 'bp_xprofile_field_type_set_whitelist_values', $value, $values, $this );
		}

		return $this;
	}

	/**
	 * Check the given string against the registered formats for this field type.
	 *
	 * This method doesn't support chaining.
	 *
	 * @param string|array $values Value to check against the registered formats
	 * @return bool True if the value validates
	 * @since BuddyPress (2.0.0)
	 */
	public function is_valid( $values ) {
		$validated = false;

		// Some types of field (e.g. multi-selectbox) may have multiple values to check
		foreach ( (array) $values as $value ) {

			// Validate the $value against the type's accepted format(s).
			foreach ( $this->validation_regex as $format ) {
				if ( 1 === preg_match( $format, $value ) ) {
					$validated = true;
					continue;

				} else {
					$validated = false;
				}
			}
		}

		// Handle field types with accepts_null_value set if $values is an empty array
		if ( ( false === $validated ) && is_array( $values ) && empty( $values ) && $this->accepts_null_value ) {
			$validated = true;
		}

		// If there's a whitelist set, also check the $value.
		if ( ( true === $validated ) && ! empty( $values ) && ! empty( $this->validation_whitelist ) ) {
			foreach ( (array) $values as $value ) {
				$validated = in_array( $value, $this->validation_whitelist, true );
			}
		}

		/**
		 * Filters whether or not field type is a valid format.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param bool                   $validated Whether or not the field type is valid.
		 * @param string|array           $values    Value to check against the registered formats.
		 * @param BP_XProfile_Field_Type $this      Current instance of the BP_XProfile_Field_Type class.
		 */
		return (bool) apply_filters( 'bp_xprofile_field_type_is_valid', $validated, $values, $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	abstract public function edit_field_html( array $raw_properties = array() );

	/**
	 * Output the edit field options HTML for this field type.
	 *
	 * BuddyPress considers a field's "options" to be, for example, the items in a selectbox.
	 * These are stored separately in the database, and their templating is handled separately.
	 * Populate this method in a child class if it's required. Otherwise, you can leave it out.
	 *
	 * This templating is separate from {@link BP_XProfile_Field_Type::edit_field_html()} because
	 * it's also used in the wp-admin screens when creating new fields, and for backwards compatibility.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 * @since BuddyPress (2.0.0)
	 */
	public function edit_field_options_html( array $args = array() ) {}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress (2.0.0)
	 */
	abstract public function admin_field_html( array $raw_properties = array() );

	/**
	 * Output HTML for this field type's children options on the wp-admin Profile Fields "Add Field" and "Edit Field" screens.
	 *
	 * You don't need to implement this method for all field types. It's used in core by the
	 * selectbox, multi selectbox, checkbox, and radio button fields, to allow the admin to
	 * enter the child option values (e.g. the choices in a select box).
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress (2.0.0)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		$type = array_search( get_class( $this ), bp_xprofile_get_field_types() );
		if ( false === $type ) {
			return;
		}

		$class            = $current_field->type != $type ? 'display: none;' : '';
		$current_type_obj = bp_xprofile_create_field_type( $type );
		?>

		<div id="<?php echo esc_attr( $type ); ?>" class="postbox bp-options-box" style="<?php echo esc_attr( $class ); ?> margin-top: 15px;">
			<h3><?php esc_html_e( 'Please enter options for this Field:', 'buddypress' ); ?></h3>
			<div class="inside">
				<p>
					<label for="sort_order_<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Sort Order:', 'buddypress' ); ?></label>
					<select name="sort_order_<?php echo esc_attr( $type ); ?>" id="sort_order_<?php echo esc_attr( $type ); ?>" >
						<option value="custom" <?php selected( 'custom', $current_field->order_by ); ?>><?php esc_html_e( 'Custom',     'buddypress' ); ?></option>
						<option value="asc"    <?php selected( 'asc',    $current_field->order_by ); ?>><?php esc_html_e( 'Ascending',  'buddypress' ); ?></option>
						<option value="desc"   <?php selected( 'desc',   $current_field->order_by ); ?>><?php esc_html_e( 'Descending', 'buddypress' ); ?></option>
					</select>
				</p>

				<?php

				// Does option have children?
				$options = $current_field->get_children( true );

				// If no children options exists for this field, check in $_POST
				// for a submitted form (e.g. on the "new field" screen).
				if ( empty( $options ) ) {

					$options = array();
					$i       = 1;

					while ( isset( $_POST[$type . '_option'][$i] ) ) {

						// Multiselectbox and checkboxes support MULTIPLE default options; all other core types support only ONE.
						if ( $current_type_obj->supports_options && ! $current_type_obj->supports_multiple_defaults && isset( $_POST["isDefault_{$type}_option"][$i] ) && (int) $_POST["isDefault_{$type}_option"] === $i ) {
							$is_default_option = true;
						} elseif ( isset( $_POST["isDefault_{$type}_option"][$i] ) ) {
							$is_default_option = (bool) $_POST["isDefault_{$type}_option"][$i];
						} else {
							$is_default_option = false;
						}

						// Grab the values from $_POST to use as the form's options
						$options[] = (object) array(
							'id'                => -1,
							'is_default_option' => $is_default_option,
							'name'              => sanitize_text_field( stripslashes( $_POST[$type . '_option'][$i] ) ),
						);

						++$i;
					}

					// If there are still no children options set, this must be the "new field" screen, so add one new/empty option.
					if ( empty( $options ) ) {
						$options[] = (object) array(
							'id'                => -1,
							'is_default_option' => false,
							'name'              => '',
						);
					}
				}

				// Render the markup for the children options
				if ( ! empty( $options ) ) {
					$default_name = '';

					for ( $i = 0, $count = count( $options ); $i < $count; ++$i ) :
						$j = $i + 1;

						// Multiselectbox and checkboxes support MULTIPLE default options; all other core types support only ONE.
						if ( $current_type_obj->supports_options && $current_type_obj->supports_multiple_defaults ) {
							$default_name = '[' . $j . ']';
						}
						?>

						<div id="<?php echo esc_attr( "{$type}_div{$j}" ); ?>" class="bp-option sortable">
							<span class="bp-option-icon grabber"></span>
							<input type="text" name="<?php echo esc_attr( "{$type}_option[{$j}]" ); ?>" id="<?php echo esc_attr( "{$type}_option{$j}" ); ?>" value="<?php echo esc_attr( stripslashes( $options[$i]->name ) ); ?>" />
							<label>
								<input type="<?php echo esc_attr( $control_type ); ?>" name="<?php echo esc_attr( "isDefault_{$type}_option{$default_name}" ); ?>" <?php checked( $options[$i]->is_default_option, true ); ?> value="<?php echo esc_attr( $j ); ?>" />
								<?php _e( 'Default Value', 'buddypress' ); ?>
							</label>

							<?php if ( 1 !== $j ) : ?>
								<div class ="delete-button">
									<a href='javascript:hide("<?php echo esc_attr( "{$type}_div{$j}" ); ?>")' class="delete"><?php esc_html_e( 'Delete', 'buddypress' ); ?></a>
								</div>
							<?php endif; ?>

						</div>

					<?php endfor; ?>

					<input type="hidden" name="<?php echo esc_attr( "{$type}_option_number" ); ?>" id="<?php echo esc_attr( "{$type}_option_number" ); ?>" value="<?php echo esc_attr( $j + 1 ); ?>" />
				<?php } ?>

				<div id="<?php echo esc_attr( "{$type}_more" ); ?>"></div>
				<p><a href="javascript:add_option('<?php echo esc_js( $type ); ?>')"><?php esc_html_e( 'Add Another Option', 'buddypress' ); ?></a></p>
			</div>
		</div>

		<?php
	}

	/**
	 * Allow field types to modify submitted values before they are validated.
	 *
	 * In some cases, it may be appropriate for a field type to catch
	 * submitted values and modify them before they are passed to the
	 * is_valid() method. For example, URL validation requires the
	 * 'http://' scheme (so that the value saved in the database is always
	 * a fully-formed URL), but in order to allow users to enter a URL
	 * without this scheme, BP_XProfile_Field_Type_URL prepends 'http://'
	 * when it's not present.
	 *
	 * By default, this is a pass-through method that does nothing. Only
	 * override in your own field type if you need this kind of pre-
	 * validation filtering.
	 *
	 * @since BuddyPress (2.1.0)
	 *
	 * @param mixed $submitted_value Submitted value.
	 * @return mixed
	 */
	public static function pre_validate_filter( $field_value ) {
		return $field_value;
	}

	/**
	 * Allow field types to modify the appearance of their values.
	 *
	 * By default, this is a pass-through method that does nothing. Only
	 * override in your own field type if you need to provide custom
	 * filtering for output values.
	 *
	 * @since BuddyPress (2.1.0)
	 *
	 * @param mixed $field_value Field value.
	 * @return mixed
	 */
	public static function display_filter( $field_value ) {
		return $field_value;
	}

	/** Protected *************************************************************/

	/**
	 * Get a sanitised and escaped string of the edit field's HTML elements and attributes.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 * This method was intended to be static but couldn't be because php.net/lsb/ requires PHP >= 5.3.
	 *
	 * @param array $properties Optional key/value array of attributes for this edit field.
	 * @return string
	 * @since BuddyPress (2.0.0)
	 */
	protected function get_edit_field_html_elements( array $properties = array() ) {

		$r = bp_parse_args( $properties, array(
			'id'   => bp_get_the_profile_field_input_name(),
			'name' => bp_get_the_profile_field_input_name(),
		) );

		if ( bp_get_the_profile_field_is_required() ) {
			$r['aria-required'] = 'true';
		}

		$html = '';

		/**
		 * Filters the edit html elements and attributes.
		 *
		 * @since BuddyPress (2.0.0)
		 *
		 * @param array  $r     Array of parsed arguments.
		 * @param string $value Class name for the current class instance.
		 */
		$r = (array) apply_filters( 'bp_xprofile_field_edit_html_elements', $r, get_class( $this ) );

		return bp_get_form_field_attributes( sanitize_key( bp_get_the_profile_field_name() ), $r );
	}
}

/**
 * Class for generating SQL clauses to filter a user query by xprofile data.
 *
 * @since BuddyPress (2.2.0)
 */
class BP_XProfile_Query {
	/**
	 * Array of xprofile queries.
	 *
	 * See {@see WP_XProfile_Query::__construct()} for information on parameters.
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access public
	 * @var    array
	 */
	public $queries = array();

	/**
	 * Database table that where the metadata's objects are stored (eg $wpdb->users).
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access public
	 * @var    string
	 */
	public $primary_table;

	/**
	 * Column in primary_table that represents the ID of the object.
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access public
	 * @var    string
	 */
	public $primary_id_column;

	/**
	 * A flat list of table aliases used in JOIN clauses.
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access protected
	 * @var    array
	 */
	protected $table_aliases = array();

	/**
	 * Constructor.
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access public
	 *
	 * @param array $xprofile_query {
	 *     Array of xprofile query clauses.
	 *
	 *     @type string $relation Optional. The MySQL keyword used to join the clauses of the query.
	 *                            Accepts 'AND', or 'OR'. Default 'AND'.
	 *     @type array {
	 *         Optional. An array of first-order clause parameters, or another fully-formed xprofile query.
	 *
	 *         @type string|int $field   XProfile field to filter by. Accepts a field name or ID.
	 *         @type string     $value   XProfile value to filter by.
	 *         @type string     $compare MySQL operator used for comparing the $value. Accepts '=', '!=', '>',
	 *                                   '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN',
	 *                                   'NOT BETWEEN', 'REGEXP', 'NOT REGEXP', or 'RLIKE'. Default is 'IN'
	 *                                   when `$value` is an array, '=' otherwise.
	 *         @type string     $type    MySQL data type that the `value` column will be CAST to for comparisons.
	 *                                   Accepts 'NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL',
	 *                                   'SIGNED', 'TIME', or 'UNSIGNED'. Default is 'CHAR'.
	 *     }
	 * }
	 */
	public function __construct( $xprofile_query ) {
		if ( empty( $xprofile_query ) ) {
			return;
		}

		$this->queries = $this->sanitize_query( $xprofile_query );
	}

	/**
	 * Ensure the `xprofile_query` argument passed to the class constructor is well-formed.
	 *
	 * Eliminates empty items and ensures that a 'relation' is set.
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access public
	 *
	 * @param  array $queries Array of query clauses.
	 * @return array Sanitized array of query clauses.
	 */
	public function sanitize_query( $queries ) {
		$clean_queries = array();

		if ( ! is_array( $queries ) ) {
			return $clean_queries;
		}

		foreach ( $queries as $key => $query ) {
			if ( 'relation' === $key ) {
				$relation = $query;

			} elseif ( ! is_array( $query ) ) {
				continue;

			// First-order clause.
			} elseif ( $this->is_first_order_clause( $query ) ) {
				if ( isset( $query['value'] ) && array() === $query['value'] ) {
					unset( $query['value'] );
				}

				$clean_queries[] = $query;

			// Otherwise, it's a nested query, so we recurse.
			} else {
				$cleaned_query = $this->sanitize_query( $query );

				if ( ! empty( $cleaned_query ) ) {
					$clean_queries[] = $cleaned_query;
				}
			}
		}

		if ( empty( $clean_queries ) ) {
			return $clean_queries;
		}

		// Sanitize the 'relation' key provided in the query.
		if ( isset( $relation ) && 'OR' === strtoupper( $relation ) ) {
			$clean_queries['relation'] = 'OR';

		/*
		 * If there is only a single clause, call the relation 'OR'.
		 * This value will not actually be used to join clauses, but it
		 * simplifies the logic around combining key-only queries.
		 */
		} elseif ( 1 === count( $clean_queries ) ) {
			$clean_queries['relation'] = 'OR';

		// Default to AND.
		} else {
			$clean_queries['relation'] = 'AND';
		}

		return $clean_queries;
	}

	/**
	 * Determine whether a query clause is first-order.
	 *
	 * A first-order query clause is one that has either a 'key' or a 'value' array key.
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access protected
	 *
	 * @param  array $query XProfile query arguments.
	 * @return bool  Whether the query clause is a first-order clause.
	 */
	protected function is_first_order_clause( $query ) {
		return isset( $query['field'] ) || isset( $query['value'] );
	}

	/**
	 * Return the appropriate alias for the given field type if applicable.
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access public
	 *
	 * @param  string $type MySQL type to cast `value`.
	 * @return string MySQL type.
	 */
	public function get_cast_for_type( $type = '' ) {
		if ( empty( $type ) )
			return 'CHAR';

		$meta_type = strtoupper( $type );

		if ( ! preg_match( '/^(?:BINARY|CHAR|DATE|DATETIME|SIGNED|UNSIGNED|TIME|NUMERIC(?:\(\d+(?:,\s?\d+)?\))?|DECIMAL(?:\(\d+(?:,\s?\d+)?\))?)$/', $meta_type ) )
			return 'CHAR';

		if ( 'NUMERIC' == $meta_type )
			$meta_type = 'SIGNED';

		return $meta_type;
	}

	/**
	 * Generate SQL clauses to be appended to a main query.
	 *
	 * Called by the public {@see BP_XProfile_Query::get_sql()}, this method is abstracted out to maintain parity
	 * with WP's Query classes.
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access protected
	 *
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_clauses() {
		/*
		 * $queries are passed by reference to get_sql_for_query() for recursion.
		 * To keep $this->queries unaltered, pass a copy.
		 */
		$queries = $this->queries;
		$sql = $this->get_sql_for_query( $queries );

		if ( ! empty( $sql['where'] ) ) {
			$sql['where'] = ' AND ' . $sql['where'];
		}

		return $sql;
	}

	/**
	 * Generate SQL clauses for a single query array.
	 *
	 * If nested subqueries are found, this method recurses the tree to produce the properly nested SQL.
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access protected
	 *
	 * @param  array $query Query to parse.
	 * @param  int   $depth Optional. Number of tree levels deep we currently are. Used to calculate indentation.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a single query array.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_for_query( &$query, $depth = 0 ) {
		$sql_chunks = array(
			'join'  => array(),
			'where' => array(),
		);

		$sql = array(
			'join'  => '',
			'where' => '',
		);

		$indent = '';
		for ( $i = 0; $i < $depth; $i++ ) {
			$indent .= "  ";
		}

		foreach ( $query as $key => &$clause ) {
			if ( 'relation' === $key ) {
				$relation = $query['relation'];
			} elseif ( is_array( $clause ) ) {

				// This is a first-order clause.
				if ( $this->is_first_order_clause( $clause ) ) {
					$clause_sql = $this->get_sql_for_clause( $clause, $query );

					$where_count = count( $clause_sql['where'] );
					if ( ! $where_count ) {
						$sql_chunks['where'][] = '';
					} elseif ( 1 === $where_count ) {
						$sql_chunks['where'][] = $clause_sql['where'][0];
					} else {
						$sql_chunks['where'][] = '( ' . implode( ' AND ', $clause_sql['where'] ) . ' )';
					}

					$sql_chunks['join'] = array_merge( $sql_chunks['join'], $clause_sql['join'] );
				// This is a subquery, so we recurse.
				} else {
					$clause_sql = $this->get_sql_for_query( $clause, $depth + 1 );

					$sql_chunks['where'][] = $clause_sql['where'];
					$sql_chunks['join'][]  = $clause_sql['join'];
				}
			}
		}

		// Filter to remove empties.
		$sql_chunks['join']  = array_filter( $sql_chunks['join'] );
		$sql_chunks['where'] = array_filter( $sql_chunks['where'] );

		if ( empty( $relation ) ) {
			$relation = 'AND';
		}

		// Filter duplicate JOIN clauses and combine into a single string.
		if ( ! empty( $sql_chunks['join'] ) ) {
			$sql['join'] = implode( ' ', array_unique( $sql_chunks['join'] ) );
		}

		// Generate a single WHERE clause with proper brackets and indentation.
		if ( ! empty( $sql_chunks['where'] ) ) {
			$sql['where'] = '( ' . "\n  " . $indent . implode( ' ' . "\n  " . $indent . $relation . ' ' . "\n  " . $indent, $sql_chunks['where'] ) . "\n" . $indent . ')';
		}

		return $sql;
	}

	/**
	 * Generates SQL clauses to be appended to a main query.
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access public
	 *
	 * @param string $primary_table     Database table where the object being filtered is stored (eg wp_users).
	 * @param string $primary_id_column ID column for the filtered object in $primary_table.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	public function get_sql( $primary_table, $primary_id_column, $context = null ) {
		global $wpdb;

		$this->primary_table     = $primary_table;
		$this->primary_id_column = $primary_id_column;

		$sql = $this->get_sql_clauses();

		/*
		 * If any JOINs are LEFT JOINs (as in the case of NOT EXISTS), then all JOINs should
		 * be LEFT. Otherwise posts with no metadata will be excluded from results.
		 */
		if ( false !== strpos( $sql['join'], 'LEFT JOIN' ) ) {
			$sql['join'] = str_replace( 'INNER JOIN', 'LEFT JOIN', $sql['join'] );
		}

		return $sql;
	}

	/**
	 * Generate SQL JOIN and WHERE clauses for a first-order query clause.
	 *
	 * "First-order" means that it's an array with a 'field' or 'value'.
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access public
	 *
	 * @param array $clause       Query clause.
	 * @param array $parent_query Parent query array.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a first-order query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	public function get_sql_for_clause( &$clause, $parent_query ) {
		global $wpdb;

		$sql_chunks = array(
			'where' => array(),
			'join' => array(),
		);

		if ( isset( $clause['compare'] ) ) {
			$clause['compare'] = strtoupper( $clause['compare'] );
		} else {
			$clause['compare'] = isset( $clause['value'] ) && is_array( $clause['value'] ) ? 'IN' : '=';
		}

		if ( ! in_array( $clause['compare'], array(
			'=', '!=', '>', '>=', '<', '<=',
			'LIKE', 'NOT LIKE',
			'IN', 'NOT IN',
			'BETWEEN', 'NOT BETWEEN',
			'EXISTS', 'NOT EXISTS',
			'REGEXP', 'NOT REGEXP', 'RLIKE'
		) ) ) {
			$clause['compare'] = '=';
		}

		$field_compare = $clause['compare'];

		// First build the JOIN clause, if one is required.
		$join = '';

		$data_table = buddypress()->profile->table_name_data;

		// We prefer to avoid joins if possible. Look for an existing join compatible with this clause.
		$alias = $this->find_compatible_table_alias( $clause, $parent_query );
		if ( false === $alias ) {
			$i = count( $this->table_aliases );
			$alias = $i ? 'xpq' . $i : $data_table;

			// JOIN clauses for NOT EXISTS have their own syntax.
			if ( 'NOT EXISTS' === $field_compare ) {
				$join .= " LEFT JOIN $data_table";
				$join .= $i ? " AS $alias" : '';
				$join .= $wpdb->prepare( " ON ($this->primary_table.$this->primary_id_column = $alias.user_id AND $alias.field_id = %d )", $clause['field'] );

			// All other JOIN clauses.
			} else {
				$join .= " INNER JOIN $data_table";
				$join .= $i ? " AS $alias" : '';
				$join .= " ON ( $this->primary_table.$this->primary_id_column = $alias.user_id )";
			}

			$this->table_aliases[] = $alias;
			$sql_chunks['join'][] = $join;
		}

		// Save the alias to this clause, for future siblings to find.
		$clause['alias'] = $alias;

		// Next, build the WHERE clause.
		$where = '';

		// field_id.
		if ( array_key_exists( 'field', $clause ) ) {
			// Convert field name to ID if necessary.
			if ( ! is_numeric( $clause['field'] ) ) {
				$clause['field'] = xprofile_get_field_id_from_name( $clause['field'] );
			}

			// NOT EXISTS has its own syntax.
			if ( 'NOT EXISTS' === $field_compare ) {
				$sql_chunks['where'][] = $alias . '.user_id IS NULL';
			} else {
				$sql_chunks['where'][] = $wpdb->prepare( "$alias.field_id = %d", $clause['field'] );
			}
		}

		// value.
		if ( array_key_exists( 'value', $clause ) ) {
			$field_value = $clause['value'];
			$field_type = $this->get_cast_for_type( isset( $clause['type'] ) ? $clause['type'] : '' );

			if ( in_array( $field_compare, array( 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ) ) ) {
				if ( ! is_array( $field_value ) ) {
					$field_value = preg_split( '/[,\s]+/', $field_value );
				}
			} else {
				$field_value = trim( $field_value );
			}

			switch ( $field_compare ) {
				case 'IN' :
				case 'NOT IN' :
					$field_compare_string = '(' . substr( str_repeat( ',%s', count( $field_value ) ), 1 ) . ')';
					$where = $wpdb->prepare( $field_compare_string, $field_value );
					break;

				case 'BETWEEN' :
				case 'NOT BETWEEN' :
					$field_value = array_slice( $field_value, 0, 2 );
					$where = $wpdb->prepare( '%s AND %s', $field_value );
					break;

				case 'LIKE' :
				case 'NOT LIKE' :
					$field_value = '%' . bp_esc_like( $field_value ) . '%';
					$where = $wpdb->prepare( '%s', $field_value );
					break;

				default :
					$where = $wpdb->prepare( '%s', $field_value );
					break;

			}

			if ( $where ) {
				$sql_chunks['where'][] = "CAST($alias.value AS {$field_type}) {$field_compare} {$where}";
			}
		}

		/*
		 * Multiple WHERE clauses (`field` and `value` pairs) should be joined in parentheses.
		 */
		if ( 1 < count( $sql_chunks['where'] ) ) {
			$sql_chunks['where'] = array( '( ' . implode( ' AND ', $sql_chunks['where'] ) . ' )' );
		}

		return $sql_chunks;
	}

	/**
	 * Identify an existing table alias that is compatible with the current query clause.
	 *
	 * We avoid unnecessary table joins by allowing each clause to look for an existing table alias that is
	 * compatible with the query that it needs to perform. An existing alias is compatible if (a) it is a
	 * sibling of $clause (ie, it's under the scope of the same relation), and (b) the combination of
	 * operator and relation between the clauses allows for a shared table join. In the case of BP_XProfile_Query,
	 * this * only applies to IN clauses that are connected by the relation OR.
	 *
	 * @since  BuddyPress (2.2.0)
	 * @access protected
	 *
	 * @param  array       $clause       Query clause.
	 * @param  array       $parent_query Parent query of $clause.
	 * @return string|bool Table alias if found, otherwise false.
	 */
	protected function find_compatible_table_alias( $clause, $parent_query ) {
		$alias = false;

		foreach ( $parent_query as $sibling ) {
			// If the sibling has no alias yet, there's nothing to check.
			if ( empty( $sibling['alias'] ) ) {
				continue;
			}

			// We're only interested in siblings that are first-order clauses.
			if ( ! is_array( $sibling ) || ! $this->is_first_order_clause( $sibling ) ) {
				continue;
			}

			$compatible_compares = array();

			// Clauses connected by OR can share joins as long as they have "positive" operators.
			if ( 'OR' === $parent_query['relation'] ) {
				$compatible_compares = array( '=', 'IN', 'BETWEEN', 'LIKE', 'REGEXP', 'RLIKE', '>', '>=', '<', '<=' );

			// Clauses joined by AND with "negative" operators share a join only if they also share a key.
			} elseif ( isset( $sibling['field_id'] ) && isset( $clause['field_id'] ) && $sibling['field_id'] === $clause['field_id'] ) {
				$compatible_compares = array( '!=', 'NOT IN', 'NOT LIKE' );
			}

			$clause_compare  = strtoupper( $clause['compare'] );
			$sibling_compare = strtoupper( $sibling['compare'] );
			if ( in_array( $clause_compare, $compatible_compares ) && in_array( $sibling_compare, $compatible_compares ) ) {
				$alias = $sibling['alias'];
				break;
			}
		}

		return $alias;
	}
}
