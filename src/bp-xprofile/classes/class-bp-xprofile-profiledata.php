<?php
/**
 * BuddyPress XProfile Classes.
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for XProfile Profile Data setup.
 *
 * @since 1.6.0
 */
class BP_XProfile_ProfileData {

	/**
	 * XProfile ID.
	 *
	 * @since 1.6.0
	 * @var int $id
	 */
	public $id;

	/**
	 * User ID.
	 *
	 * @since 1.6.0
	 * @var int $user_id
	 */
	public $user_id;

	/**
	 * XProfile field ID.
	 *
	 * @since 1.6.0
	 * @var int $field_id
	 */
	public $field_id;

	/**
	 * XProfile field value.
	 *
	 * @since 1.6.0
	 * @var string $value
	 */
	public $value;

	/**
	 * XProfile field last updated time.
	 *
	 * @since 1.6.0
	 * @var string $last_updated
	 */
	public $last_updated;

	/**
	 * BP_XProfile_ProfileData constructor.
	 *
	 * @since 1.5.0
	 *
	 * @param int|null $field_id Field ID to instantiate.
	 * @param int|null $user_id  User ID to instantiate for.
	 */
	public function __construct( $field_id = null, $user_id = null ) {
		if ( !empty( $field_id ) ) {
			$this->populate( $field_id, $user_id );
		}
	}

	/**
	 * Populates the XProfile profile data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $field_id Field ID to populate.
	 * @param int $user_id  User ID to populate for.
	 */
	public function populate( $field_id, $user_id ) {
		global $wpdb;

		$cache_key   = "{$user_id}:{$field_id}";
		$profiledata = wp_cache_get( $cache_key, 'bp_xprofile_data' );

		if ( false === $profiledata ) {
			$bp = buddypress();

			$sql         = $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id = %d", $field_id, $user_id );
			$profiledata = $wpdb->get_row( $sql );

			if ( $profiledata ) {
				wp_cache_set( $cache_key, $profiledata, 'bp_xprofile_data' );
			}
		}

		if ( $profiledata ) {
			$this->id           = (int) $profiledata->id;
			$this->user_id      = (int) $profiledata->user_id;
			$this->field_id     = (int) $profiledata->field_id;
			$this->value        = stripslashes( $profiledata->value );
			$this->last_updated = $profiledata->last_updated;

		} else {
			// When no row is found, we'll need to set these properties manually.
			$this->field_id	    = (int) $field_id;
			$this->user_id	    = (int) $user_id;
		}
	}

	/**
	 * Check if there is data already for the user.
	 *
	 * @since 1.0.0
	 *
	 * @global object $wpdb
	 * @global array $bp
	 *
	 * @return bool
	 */
	public function exists() {
		global $wpdb;

		// Check cache first.
		$cache_key = "{$this->user_id}:{$this->field_id}";
		$cached    = wp_cache_get( $cache_key, 'bp_xprofile_data' );

		if ( $cached && ! empty( $cached->id ) ) {
			$retval = true;
		} else {
			$bp     = buddypress();
			$retval = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_data} WHERE user_id = %d AND field_id = %d", $this->user_id, $this->field_id ) );
		}

		/**
		 * Filters whether or not data already exists for the user.
		 *
		 * @since 1.2.7
		 *
		 * @param bool                    $retval       Whether or not data already exists.
		 * @param BP_XProfile_ProfileData $profile_data Instance of the current BP_XProfile_ProfileData class.
		 */
		return apply_filters_ref_array( 'xprofile_data_exists', array( (bool)$retval, $this ) );
	}

	/**
	 * Check if this data is for a valid field.
	 *
	 * @since 1.0.0
	 *
	 * @global object $wpdb
	 *
	 * @return bool
	 */
	public function is_valid_field() {
		global $wpdb;

		$bp     = buddypress();
		$retval = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_fields} WHERE id = %d", $this->field_id ) );

		/**
		 * Filters whether or not data is for a valid field.
		 *
		 * @since 1.2.7
		 *
		 * @param bool                    $retval       Whether or not data is valid.
		 * @param BP_XProfile_ProfileData $profile_data Instance of the current BP_XProfile_ProfileData class.
		 */
		return apply_filters_ref_array( 'xprofile_data_is_valid_field', array( (bool)$retval, $this ) );
	}

	/**
	 * Save the data for the XProfile field.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		/**
		 * Filters the data's user ID before saving to the database.
		 *
		 * @since 1.0.0
		 *
		 * @param int $user_id The user ID.
		 * @param int $data_id The field data ID.
		 */
		$this->user_id = apply_filters( 'xprofile_data_user_id_before_save', $this->user_id, $this->id );

		/**
		 * Filters the data's field ID before saving to the database.
		 *
		 * @since 1.0.0
		 *
		 * @param int $field_id The field ID.
		 * @param int $data_id  The field data ID.
		 */
		$this->field_id = apply_filters( 'xprofile_data_field_id_before_save', $this->field_id, $this->id );

		/**
		 * Filters the data's value before saving to the database.
		 *
		 * @since 1.0.0
		 * @since 2.1.0 Added `$reserialize` and `$this` parameters.
		 *
		 * @param string                  $field_value  The field value.
		 * @param int                     $data_id      The field data ID.
		 * @param bool                    $reserialize  Whether to reserialize arrays before returning. Defaults to true.
		 * @param BP_XProfile_ProfileData $profile_data Current instance of the profile data being saved.
		 */
		$this->value = apply_filters( 'xprofile_data_value_before_save', $this->value, $this->id, true, $this );

		/**
		 * Filters the data's last updated timestamp before saving to the database.
		 *
		 * @since 1.0.0
		 *
		 * @param int $last_updated The last updated timestamp.
		 * @param int $data_id      The field data ID.
		 */
		$this->last_updated = apply_filters( 'xprofile_data_last_updated_before_save', bp_core_current_time(), $this->id );

		/**
		 * Fires before the current profile data instance gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_XProfile_ProfileData $profile_data Current instance of the profile data being saved.
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

			if ( false === $result ) {
				return false;
			}

			/**
			 * Fires after the current profile data instance gets saved.
			 *
			 * @since 1.0.0
			 *
			 * @param BP_XProfile_ProfileData $profile_data Current instance of the profile data being saved.
			 */
			do_action_ref_array( 'xprofile_data_after_save', array( $this ) );

			return true;
		}

		return false;
	}

	/**
	 * Delete specific XProfile field data.
	 *
	 * @since 1.0.0
	 *
	 * @global object $wpdb
	 *
	 * @return boolean
	 */
	public function delete() {
		global $wpdb;

		$bp = buddypress();

		/**
		 * Fires before the current profile data instance gets deleted.
		 *
		 * @since 1.9.0
		 *
		 * @param BP_XProfile_ProfileData $profile_data Current instance of the profile data being deleted.
		 */
		do_action_ref_array( 'xprofile_data_before_delete', array( $this ) );

		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id = %d", $this->field_id, $this->user_id ) );
		if ( empty( $deleted ) ) {
			return false;
		}

		/**
		 * Fires after the current profile data instance gets deleted.
		 *
		 * @since 1.9.0
		 *
		 * @param BP_XProfile_ProfileData $profile_data Current instance of the profile data being deleted.
		 */
		do_action_ref_array( 'xprofile_data_after_delete', array( $this ) );

		return true;
	}

	/** Static Methods ********************************************************/

	/**
	 * Get a user's profile data for a set of fields.
	 *
	 * @since 2.0.0
	 * @since 8.0.0 Checks if a null field data is an xProfile WP Field.
	 *              Adds a new parameter `$field_type_objects` to pass the list of field type objects.
	 *
	 * @param int   $user_id            ID of user whose data is being queried.
	 * @param array $field_ids          Array of field IDs to query for.
	 * @param array $field_type_objects Array of field type objects keyed by the queried filed IDs.
	 * @return array
	 */
	public static function get_data_for_user( $user_id, $field_ids, $field_type_objects = array() ) {
		global $wpdb;

		$data = array();

		$uncached_field_ids = bp_xprofile_get_non_cached_field_ids( $user_id, $field_ids );

		// Prime the cache.
		if ( ! empty( $uncached_field_ids ) ) {
			$bp = buddypress();
			$uncached_field_ids_sql = implode( ',', wp_parse_id_list( $uncached_field_ids ) );
			$uncached_data = $wpdb->get_results( $wpdb->prepare( "SELECT id, user_id, field_id, value, last_updated FROM {$bp->profile->table_name_data} WHERE field_id IN ({$uncached_field_ids_sql}) AND user_id = %d", $user_id ) );

			// Rekey.
			$queried_data = array();
			foreach ( $uncached_data as $ud ) {
				$d               = new stdClass;
				$d->id           = $ud->id;
				$d->table_name   = $bp->profile->table_name_data;
				$d->user_id      = $ud->user_id;
				$d->field_id     = $ud->field_id;
				$d->value        = $ud->value;
				$d->last_updated = $ud->last_updated;

				$queried_data[ $ud->field_id ] = $d;
			}

			// Set caches.
			foreach ( $uncached_field_ids as $field_id ) {

				$cache_key = "{$user_id}:{$field_id}";

				// If a value was found, cache it.
				if ( isset( $queried_data[ $field_id ] ) && ! isset( $field_type_objects[ $field_id ]->wp_user_key ) ) {
					wp_cache_set( $cache_key, $queried_data[ $field_id ], 'bp_xprofile_data' );

				// If no value was found, cache an empty item
				// to avoid future cache misses.
				} else {
					$d = new stdClass;

					// Check if it's a WordPress field.
					if ( isset( $field_type_objects[ $field_id ]->wp_user_key ) ) {
						$meta          = $field_type_objects[ $field_id ]->get_field_value( $user_id, $field_id );
						$d->id         = $meta['id'];
						$d->value      = $meta['value'];
						$d->table_name = $meta['table_name'];

					} else {
						$d->id         = '';
						$d->value      = '';
						$d->table_name = '';
					}

					$d->user_id      = $user_id;
					$d->field_id     = $field_id;
					$d->last_updated = '';

					wp_cache_set( $cache_key, $d, 'bp_xprofile_data' );
				}
			}
		}

		// Now that all items are cached, fetch them.
		foreach ( $field_ids as $field_id ) {
			$cache_key = "{$user_id}:{$field_id}";
			$data[]    = wp_cache_get( $cache_key, 'bp_xprofile_data' );
		}

		// Integer casting.
		foreach ( (array) $data as $key => $d ) {
			if ( isset( $data[ $key ]->id ) ) {
				$data[ $key ]->id = (int) $data[ $key ]->id;
			}
			if ( isset( $data[ $key ]->user_id ) ) {
				$data[ $key ]->user_id  = (int) $data[ $key ]->user_id;
			}

			$data[ $key ]->field_id = (int) $data[ $key ]->field_id;
		}

		return $data;
	}

	/**
	 * Get all of the profile information for a specific user.
	 *
	 * @since 1.2.0
	 * @since 8.0.0 Checks if a null field data is an xProfile WP Field.
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
					);

					if ( is_null( $field->data ) ) {
						if ( 1 === $field->id ) {
							$profile_data[ $field->name ]['field_data'] = $user->display_name;
						} elseif ( isset( $field->type_obj ) && $field->type_obj instanceof BP_XProfile_Field_Type && isset( $field->type_obj->wp_user_key ) ) {
							$meta = $field->type_obj->get_field_value( $user->ID, $field->id );

							if ( isset( $meta['value'] ) ) {
								$profile_data[ $field->name ]['field_data'] = $meta['value'];
							}
						} else {
							$profile_data[ $field->name ]['field_data'] = false;
						}
					} else {
						$profile_data[ $field->name ]['field_data'] = $field->data->value;
					}
				}
			}
		}

		return $profile_data;
	}

	/**
	 * Get the user's field data id by the id of the xprofile field.
	 *
	 * @since 1.6.0
	 *
	 * @param int $field_id Field ID being queried for.
	 * @param int $user_id  User ID associated with field.
	 * @return int $fielddata_id
	 */
	public static function get_fielddataid_byid( $field_id, $user_id ) {
		global $wpdb;

		if ( empty( $field_id ) || empty( $user_id ) ) {
			$fielddata_id = 0;
		} else {
			$bp = buddypress();

			// Check cache first.
			$cache_key = "{$user_id}:{$field_id}";
			$fielddata = wp_cache_get( $cache_key, 'bp_xprofile_data' );
			if ( false === $fielddata || empty( $fielddata->id ) ) {
				$fielddata_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id = %d", $field_id, $user_id ) );
			} else {
				$fielddata_id = $fielddata->id;
			}
		}

		return (int) $fielddata_id;
	}

	/**
	 * Get profile field values by field ID and user IDs.
	 *
	 * Supports multiple user IDs.
	 *
	 * @since 1.0.0
	 * @since 8.0.0 Checks if a null field data is an xProfile WP Field.
	 *
	 * @param int            $field_id ID of the field.
	 * @param int|array|null $user_ids ID or IDs of user(s).
	 * @return string|array Single value if a single user is queried,
	 *                      otherwise an array of results.
	 */
	public static function get_value_byid( $field_id, $user_ids = null ) {
		global $wpdb;

		if ( empty( $user_ids ) ) {
			$user_ids = bp_displayed_user_id();
		}

		$return_single_result = false;
		if ( ! is_array( $user_ids ) ) {
			$return_single_result = true;
		}

		$user_ids = wp_parse_id_list( $user_ids );

		// Assemble uncached IDs.
		$uncached_ids = array();
		foreach ( $user_ids as $user_id ) {
			$cache_key = "{$user_id}:{$field_id}";
			if ( false === wp_cache_get( $cache_key, 'bp_xprofile_data' ) ) {
				$uncached_ids[] = $user_id;
			}
		}

		// Prime caches.
		if ( ! empty( $uncached_ids ) ) {
			$bp = buddypress();
			$uncached_ids_sql = implode( ',', $uncached_ids );
			$queried_data = $wpdb->get_results( $wpdb->prepare( "SELECT id, user_id, field_id, value, last_updated FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id IN ({$uncached_ids_sql})", $field_id ) );

			// Rekey.
			$qd = array();
			foreach ( $queried_data as $data ) {
				$qd[ $data->user_id ] = $data;
			}

			foreach ( $uncached_ids as $id ) {
				// The value was successfully fetched.
				if ( isset( $qd[ $id ] ) ) {
					$d = $qd[ $id ];

				// No data found for the user, so we fake it to
				// avoid cache misses and PHP notices.
				} else {
					$d          = new stdClass;
					$field_type = bp_xprofile_get_field_type( $field_id );

					// Check WordPress if it's a WordPress field.
					if ( isset( $field_type->wp_user_key ) ) {
						$meta          = $field_type->get_field_value( $user_id, $field_id );
						$d->id         = $meta['id'];
						$d->value      = $meta['value'];
						$d->table_name = $meta['table_name'];

					} else {
						$d->id    = '';
						$d->value = '';
					}

					$d->table_name   = '';
					$d->user_id      = $id;
					$d->field_id     = $field_id;
					$d->last_updated = '';
				}

				$cache_key = "{$d->user_id}:{$field_id}";
				wp_cache_set( $cache_key, $d, 'bp_xprofile_data' );
			}
		}

		// Now that the cache is primed with all data, fetch it.
		$data = array();
		foreach ( $user_ids as $user_id ) {
			$cache_key = "{$user_id}:{$field_id}";
			$data[]    = wp_cache_get( $cache_key, 'bp_xprofile_data' );
		}

		// Integer casting.
		foreach ( (array) $data as $key => $d ) {
			if ( isset( $data[ $key ]->id ) ) {
				$data[ $key ]->id = (int) $data[ $key ]->id;
			}
			if ( isset( $data[ $key ]->user_id ) ) {
				$data[ $key ]->user_id  = (int) $data[ $key ]->user_id;
			}

			$data[ $key ]->field_id = (int) $data[ $key ]->field_id;
		}

		// If a single ID was passed, just return the value.
		if ( $return_single_result ) {
			return $data[0]->value;

		// Otherwise return the whole array.
		} else {
			return $data;
		}
	}

	/**
	 * Get profile field values by field name and user ID.
	 *
	 * @since 1.0.0
	 * @deprecated 8.0.0 This function is not used anymore.
	 *
	 * @param array|string $fields  Field(s) to get.
	 * @param int|null     $user_id User ID to get field data for.
	 * @return array|bool
	 */
	public static function get_value_byfieldname( $fields, $user_id = null ) {
		_deprecated_function( __FUNCTION__, '8.0.0' );
		global $wpdb;

		if ( empty( $fields ) ) {
			return false;
		}

		$bp = buddypress();

		if ( empty( $user_id ) ) {
			$user_id = bp_displayed_user_id();
		}

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

		$sql    = $wpdb->prepare( "SELECT d.value, f.name FROM {$bp->profile->table_name_data} d, {$bp->profile->table_name_fields} f WHERE d.field_id = f.id AND d.user_id = %d AND f.parent_id = 0 $field_sql", $user_id );
		$values = $wpdb->get_results( $sql );

		if ( empty( $values ) || is_wp_error( $values ) ) {
			return false;
		}

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

	/**
	 * Delete field.
	 *
	 * @since 1.0.0
	 *
	 * @param int $field_id ID of the field to delete.
	 * @return bool
	 */
	public static function delete_for_field( $field_id ) {
		global $wpdb;

		$bp      = buddypress();
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_data} WHERE field_id = %d", $field_id ) );
		if ( empty( $deleted ) || is_wp_error( $deleted ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get time for last XProfile field data update by user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID to get time for.
	 * @return null|string
	 */
	public static function get_last_updated( $user_id ) {
		global $wpdb;

		$bp = buddypress();

		$last_updated = $wpdb->get_var( $wpdb->prepare( "SELECT last_updated FROM {$bp->profile->table_name_data} WHERE user_id = %d ORDER BY last_updated LIMIT 1", $user_id ) );

		return $last_updated;
	}

	/**
	 * Delete all data for provided user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID to remove data for.
	 * @return false|int
	 */
	public static function delete_data_for_user( $user_id ) {
		global $wpdb;

		$bp = buddypress();

		$field_ids = $wpdb->get_col( $wpdb->prepare( "SELECT field_id FROM {$bp->profile->table_name_data} WHERE user_id = %d", $user_id ) );

		if ( ! $field_ids ) {
			return false;
		}

		foreach ( $field_ids as $field_id ) {
			xprofile_delete_field_data( $field_id, $user_id );
		}

		return count( $field_ids );
	}

	/**
	 * Get random field type by user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $user_id          User ID to query for.
	 * @param string $exclude_fullname SQL portion used to exclude by field ID.
	 * @return array|null|object
	 */
	public static function get_random( $user_id, $exclude_fullname ) {
		global $wpdb;

		$exclude_sql = ! empty( $exclude_fullname ) ? ' AND pf.id != 1' : '';

		$bp = buddypress();

		return $wpdb->get_results( $wpdb->prepare( "SELECT pf.type, pf.name, pd.value FROM {$bp->profile->table_name_data} pd INNER JOIN {$bp->profile->table_name_fields} pf ON pd.field_id = pf.id AND pd.user_id = %d {$exclude_sql} ORDER BY RAND() LIMIT 1", $user_id ) );
	}

	/**
	 * Get fullname for provided user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id ID of the user to query.
	 * @return mixed
	 */
	public static function get_fullname( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			$user_id = bp_displayed_user_id();
		}

		return xprofile_get_field_data( bp_xprofile_fullname_field_id(), $user_id );
	}
}
