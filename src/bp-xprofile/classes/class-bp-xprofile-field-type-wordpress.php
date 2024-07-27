<?php
/**
 * BuddyPress XProfile Classes.
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 * @since 8.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base class for xprofile field types that set/get WordPress profile data from usermeta.
 *
 * @since 8.0.0
 */
abstract class BP_XProfile_Field_Type_WordPress extends BP_XProfile_Field_Type {

	/**
	 * The usermeta key for the WordPress field.
	 *
	 * @since 8.0.0
	 * @var string The meta key name of this WordPress field.
	 */
	public $wp_user_key = '';

	/**
	 * The WordPress supported user keys.
	 *
	 * @since 8.0.0
	 * @var string[] The WordPress supported user keys.
	 */
	public $supported_keys = array();

	/**
	 * WordPress field's visibility setting.
	 *
	 * Defaults to 'public'. This property enforces Field's default visibility.
	 *
	 * @since 8.0.0
	 *
	 * @return string The WordPress field's visibility setting.
	 */
	public $visibility = 'public';

	/**
	 * Supported features for the WordPress field type.
	 *
	 * @since 8.0.0
	 * @var bool[] The WordPress field supported features.
	 */
	public static $supported_features = array(
		'switch_fieldtype'        => false,
		'required'                => false,
		'do_autolink'             => false,
		'allow_custom_visibility' => false,
		'member_types'            => false,
		'signup_position'         => true,
	);

	/**
	 * Constructor for the WordPress field type.
	 *
	 * @since 8.0.0
	 */
	public function __construct() {
		parent::__construct();

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_WordPress class.
		 *
		 * @since 8.0.0
		 *
		 * @param BP_XProfile_Field_Type_WordPress $field_type Current instance of the field type class.
		 */
		do_action( 'bp_xprofile_field_type_wordpress', $this );

		// Use the `$wpdb->usermeta` table instead of the $bp->profile->table_name_data one.
		add_filter( 'bp_xprofile_set_field_data_pre_save', array( $this, 'set_field_value' ), 10, 2 );

		// Set the supported keys.
		$this->supported_keys = bp_xprofile_get_wp_user_keys();
	}

	/**
	 * Sanitize the user field before inserting it into db.
	 *
	 * @since 8.0.0
	 *
	 * @param string $value The user field value.
	 */
	abstract public function sanitize_for_db( $value );

	/**
	 * Sanitize the user field before displaying it as an attribute.
	 *
	 * @since 8.0.0
	 *
	 * @param string $value The user field value.
	 * @param integer $user_id The user ID.
	 */
	abstract public function sanitize_for_output( $value, $user_id = 0 );

	/**
	 * Sets the WordPress field value.
	 *
	 * @since 8.0.0
	 *
	 * @param boolean $retval Whether to shortcircuit the $bp->profile->table_name_data table.
	 *                        Default `false`.
	 * @param array $field_args {
	 *     An array of arguments.
	 *
	 *     @type object            $field_type_obj Field type object.
	 *     @type BP_XProfile_Field $field          Field object.
	 *     @type integer           $user_id        The user ID.
	 *     @type mixed             $value          Value passed to xprofile_set_field_data().
	 *     @type boolean           $is_required    Whether or not the field is required.
	 * }
	 * @return bool Whether to shortcircuit the $bp->profile->table_name_data table.
	 */
	public function set_field_value( $retval = false, $field_args = array() ) {
		// Check the wp_user_key is valid and supported.
		if ( ! isset( $field_args['field']->type_obj->wp_user_key ) || $this->wp_user_key !== $field_args['field']->type_obj->wp_user_key || ! in_array( $field_args['field']->type_obj->wp_user_key, $this->supported_keys, true ) ) {
			return false;
		}

		$wp_user_field_value = $this->sanitize_for_db( $field_args['value'] );
		$bp_displayed_user   = bp_get_displayed_user();

		if ( isset( $bp_displayed_user->updated_keys ) ) {
			$bp_displayed_user->updated_keys[ $this->wp_user_key ] = $wp_user_field_value;
			$retval = true;
		} else {
			$retval = wp_update_user(
				array(
					'ID'               => (int) $field_args['user_id'],
					$this->wp_user_key => $wp_user_field_value,
				)
			);
		}

		if ( ! is_wp_error( $retval ) ) {
			$retval = true;
		}

		return $retval;
	}

	/**
	 * Gets the WordPress field value during an xProfile fields loop.
	 *
	 * This function is used inside `BP_XProfile_ProfileData::get_data_for_user()`
	 * to include the WordPress field value into the xProfile fields loop.
	 *
	 * @since 8.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param integer $user_id The user ID.
	 * @param integer $field_id The xProfile field ID.
	 * @return array An array containing the metadata `id`, `value` and `table_name`.
	 */
	public function get_field_value( $user_id, $field_id = 0 ) {
		global $wpdb;
		$wp_field = array(
			'id'         => 0,
			'value'      => '',
			'table_name' => $wpdb->usermeta,
		);

		if ( 'user_url' === $this->wp_user_key ) {
			if ( bp_displayed_user_id() ) {
				$wp_field['value'] = bp_get_displayed_user()->userdata->{$this->wp_user_key};
			} elseif ( $user_id ) {
				$user = get_user_by( 'id', $user_id );
				$wp_field['value'] = $user->{$this->wp_user_key};
			}

			$wp_field['id']         = $user_id;
			$wp_field['table_name'] = $wpdb->users;
		} else {
			$umeta_key = $this->wp_user_key;
			$user_mid  = wp_cache_get( $user_id, 'bp_user_mid' );
			if ( ! $user_mid ) {
				$user_mid = array();
			}

			if ( ! $user_mid ) {
				$list_values = bp_get_user_meta( $user_id, $umeta_key );

				if ( is_array( $list_values ) ) {
					$wp_field['value'] = reset( $list_values );
					$wp_field['id']    = key( $list_values );

					if ( 0 === $wp_field['id'] ) {
						/*
						* We can't just update the WP User Meta cache to key again meta values with meta_ids because of
						* `return maybe_unserialize( $meta_cache[ $meta_key ][0] );` in `get_metadata_raw()`.
						*/
						$user_meta_cache = wp_cache_get( $user_id, 'user_meta' );

						if ( $user_meta_cache ) {
							$metas = $wpdb->get_results( $wpdb->prepare( "SELECT umeta_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE user_id = %d ORDER BY umeta_id ASC", $user_id ) );

							if ( $metas ) {
								foreach ( $user_meta_cache as $meta_key => $meta_values ) {
									if ( ! in_array( $meta_key, $this->supported_keys, true ) ) {
										continue;
									}

									foreach ( $meta_values as $meta_value ) {
										$meta_object = wp_list_filter( $metas, array( 'meta_key' => $meta_key, 'meta_value' => $meta_value ) );

										if ( 1 === count( $meta_object ) ) {
											$meta_object = reset( $meta_object );
											$user_mid[ $meta_key ][ $meta_object->umeta_id ] = $meta_value;

											// Set the meta_id for the requested field.
											if ( $umeta_key === $meta_key ) {
												$wp_field['id'] = $meta_object->umeta_id;
											}
										}
									}
								}
							}

							// Set the User mid cache.
							wp_cache_set( $user_id, $user_mid, 'bp_user_mid' );
						}
					}
				}
			}

			if ( isset( $user_mid[ $umeta_key ] ) ) {
				$wp_field['value'] = reset( $user_mid[ $umeta_key ] );
				$wp_field['id']    = key( $user_mid[ $umeta_key ] );
			}
		}

		return $wp_field;
	}
}
