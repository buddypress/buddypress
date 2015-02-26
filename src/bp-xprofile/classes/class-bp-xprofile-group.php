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
		global $wpdb;

		$group = wp_cache_get( 'xprofile_group_' . $this->id, 'bp' );

		if ( false === $group ) {
			$bp = buddypress();
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
		global $wpdb;

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

		$bp = buddypress();

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
		global $wpdb;

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

		$bp = buddypress();

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
		global $wpdb;

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

		$bp = buddypress();

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
		global $wpdb;

		if ( !is_numeric( $position ) ) {
			return false;
		}

		// purge profile field group cache
		wp_cache_delete( 'xprofile_groups_inc_empty', 'bp' );

		$bp = buddypress();

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
		global $wpdb;

		$default_visibility_levels = wp_cache_get( 'xprofile_default_visibility_levels', 'bp' );

		if ( false === $default_visibility_levels ) {
			$bp = buddypress();

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
