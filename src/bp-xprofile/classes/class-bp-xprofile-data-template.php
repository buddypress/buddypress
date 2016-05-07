<?php
/**
 * BuddyPress XProfile Template Loop Class.
 *
 * @package BuddyPress
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main profile template loop class.
 *
 * This is responsible for loading profile field, group, and data and displaying it.
 *
 * @since 1.0.0
 */
class BP_XProfile_Data_Template {

	/**
	 * The loop iterator.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	public $current_group = -1;

	/**
	 * The number of groups returned by the paged query.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	public $group_count;

	/**
	 * Array of groups located by the query.
	 *
	 * @since 1.5.0
	 * @var array
	 */
	public $groups;

	/**
	 * The group object currently being iterated on.
	 *
	 * @since 1.5.0
	 * @var object
	 */
	public $group;

	/**
	 * The current field.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	public $current_field = -1;

	/**
	 * The field count.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	public $field_count;

	/**
	 * Field has data.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $field_has_data;

	/**
	 * The field.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	public $field;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * The user ID.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	public $user_id;

	/**
	 * Get activity items, as specified by parameters.
	 *
	 * @see BP_XProfile_Group::get() for more details about parameters.
	 *
	 * @since 1.5.0
	 * @since 2.4.0 Introduced `$member_type` argument.
	 *
	 * @param array|string $args {
	 *     An array of arguments. All items are optional.
	 *
	 *     @type int          $user_id                 Fetch field data for this user ID.
	 *     @type string|array $member_type             Limit results to those matching member type(s).
	 *     @type int          $profile_group_id        Field group to fetch fields & data for.
	 *     @type int|bool     $hide_empty_groups       Should empty field groups be skipped.
	 *     @type int|bool     $fetch_fields            Fetch fields for field group.
	 *     @type int|bool     $fetch_field_data        Fetch field data for fields in group.
	 *     @type array        $exclude_groups          Exclude these field groups.
	 *     @type array        $exclude_fields          Exclude these fields.
	 *     @type int|bool     $hide_empty_fields       Should empty fields be skipped.
	 *     @type int|bool     $fetch_visibility_level  Fetch visibility levels.
	 *     @type int|bool     $update_meta_cache       Should metadata cache be updated.
	 * }
	 */
	public function __construct( $args = '' ) {

		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '2.3.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'user_id',
				1 => 'profile_group_id',
				2 => 'hide_empty_groups',
				3 => 'fetch_fields',
				4 => 'fetch_field_data',
				5 => 'exclude_groups',
				6 => 'exclude_fields',
				7 => 'hide_empty_fields',
				8 => 'fetch_visibility_level',
				9 => 'update_meta_cache'
			);

			$func_args = func_get_args();
			$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$r = wp_parse_args( $args, array(
			'profile_group_id'       => false,
			'user_id'                => false,
			'member_type'            => 'any',
			'hide_empty_groups'      => false,
			'hide_empty_fields'      => false,
			'fetch_fields'           => false,
			'fetch_field_data'       => false,
			'fetch_visibility_level' => false,
			'exclude_groups'         => false,
			'exclude_fields'         => false,
			'update_meta_cache'      => true
		) );

		$this->groups      = bp_xprofile_get_groups( $r );
		$this->group_count = count( $this->groups );
		$this->user_id     = $r['user_id'];
	}

	/**
	 * Whether or not the loop has field groups.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_groups() {
		if ( ! empty( $this->group_count ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Increments to the next group of fields.
	 *
	 * @since 1.0.0
	 *
	 * @return object
	 */
	public function next_group() {
		$this->current_group++;

		$this->group       = $this->groups[ $this->current_group ];
		$this->field_count = 0;

		if ( ! empty( $this->group->fields ) ) {

			/**
			 * Filters the group fields for the next_group method.
			 *
			 * @since 1.1.0
			 *
			 * @param array $fields Array of fields for the group.
			 * @param int   $id     ID of the field group.
			 */
			$this->group->fields = apply_filters( 'xprofile_group_fields', $this->group->fields, $this->group->id );
			$this->field_count   = count( $this->group->fields );
		}

		return $this->group;
	}

	/**
	 * Rewinds to the start of the groups list.
	 *
	 * @since 1.0.0
	 */
	public function rewind_groups() {
		$this->current_group = -1;
		if ( $this->group_count > 0 ) {
			$this->group = $this->groups[0];
		}
	}

	/**
	 * Kicks off the profile groups.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function profile_groups() {
		if ( $this->current_group + 1 < $this->group_count ) {
			return true;
		} elseif ( $this->current_group + 1 == $this->group_count ) {

			/**
			 * Fires right before the rewinding of profile groups.
			 *
			 * @since 1.1.0
			 */
			do_action( 'xprofile_template_loop_end' );

			// Do some cleaning up after the loop.
			$this->rewind_groups();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Sets up the profile group.
	 *
	 * @since 1.0.0
	 */
	public function the_profile_group() {
		global $group;

		$this->in_the_loop = true;
		$group = $this->next_group();

		// Loop has just started.
		if ( 0 === $this->current_group ) {

			/**
			 * Fires if the current group is the first in the loop.
			 *
			 * @since 1.1.0
			 */
			do_action( 'xprofile_template_loop_start' );
		}
	}

	/** Fields ****************************************************************/

	/**
	 * Increments to the next field.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function next_field() {
		$this->current_field++;

		$this->field = $this->group->fields[ $this->current_field ];

		return $this->field;
	}

	/**
	 * Rewinds to the start of the fields.
	 *
	 * @since 1.0.0
	 */
	public function rewind_fields() {
		$this->current_field = -1;
		if ( $this->field_count > 0 ) {
			$this->field = $this->group->fields[0];
		}
	}

	/**
	 * Whether or not the loop has fields.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_fields() {
		$has_data = false;

		for ( $i = 0, $count = count( $this->group->fields ); $i < $count; ++$i ) {
			$field = &$this->group->fields[ $i ];

			if ( ! empty( $field->data ) && ( $field->data->value != null ) ) {
				$has_data = true;
			}
		}

		return $has_data;
	}

	/**
	 * Kick off the profile fields.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function profile_fields() {
		if ( $this->current_field + 1 < $this->field_count ) {
			return true;
		} elseif ( $this->current_field + 1 == $this->field_count ) {
			// Do some cleaning up after the loop.
			$this->rewind_fields();
		}

		return false;
	}

	/**
	 * Set up the profile fields.
	 *
	 * @since 1.0.0
	 */
	public function the_profile_field() {
		global $field;

		$field = $this->next_field();

		// Valid field values of 0 or '0' get caught by empty(), so we have an extra check for these. See #BP5731.
		if ( ! empty( $field->data ) && ( ! empty( $field->data->value ) || ( '0' === $field->data->value ) ) ) {
			$value = maybe_unserialize( $field->data->value );
		} else {
			$value = false;
		}

		if ( ! empty( $value ) || ( '0' === $value ) ) {
			$this->field_has_data = true;
		} else {
			$this->field_has_data = false;
		}
	}
}
