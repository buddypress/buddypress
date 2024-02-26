<?php
/**
 * Activity API: BP_Activity_Type class
 *
 * @package BuddyPress
 * @subpackage Activity
 * @since 14.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activity class used for interacting with activity types.
 *
 * @since 14.0.0
 *
 * @see bp_register_activity_type()
 */
final class BP_Activity_Type {
	/**
	 * Activity type key.
	 *
	 * @since 14.0.0
	 * @var string $name
	 */
	public $name;

	/**
	 * Activity type components.
	 *
	 * @since 14.0.0
	 * @var string[] $components
	 */
	public $components;

	/**
	 * The role of the activity type.
	 *
	 * One of 'content', 'log', 'reaction'.
	 *
	 * @since 14.0.0
	 * @var string $role
	 */
	public $role;

	/**
	 * The name of the activity feature.
	 *
	 * Eg: 'comments', 'likes', etc.
	 *
	 * @since 14.0.0
	 * @var string $feature_name
	 */
	public $feature_name;

	/**
	 * Activity type description.
	 *
	 * @since 14.0.0
	 * @var string $name
	 */
	public $description;

	/**
	 * Labels object for this activity type.
	 *
	 * @since 14.0.0
	 * @var stdClass $labels
	 */
	public $labels;

	/**
	 * Activity action fomatting callback.
	 *
	 * @since 14.0.0
	 * @var string $format_callback
	 */
	public $format_callback;

	/**
	 * Activity type streams.
	 *
	 * Possible lists may include 'activity', 'member', 'member_groups', 'group'.
	 *
	 * @since 14.0.0
	 * @var string[] $streams
	 */
	public $streams;

	/**
	 * Activity action position when listed in filter dropdowns.
	 *
	 * @since 14.0.0
	 * @var integer $position
	 */
	public $position;

	/**
	 * Supports object for this activity type.
	 *
	 * @since 14.0.0
	 * @var stdClass $supports
	 */
	public $supports;

	/**
	 * Constructor.
	 *
	 * See the bp_register_activity_type() function for accepted arguments for `$args`.
	 *
	 * Will populate object properties from the provided arguments and assign other
	 * default properties based on that information.
	 *
	 * @since 14.0.0
	 *
	 * @see bp_register_activity_type()
	 *
	 * @param string $type Post type key.
	 * @param array  $args Optional. Array or string of arguments for registering an activity type.
	 *               See bp_register_activity_type() for information on accepted arguments.
	 *               Default empty array.
	 */
	public function __construct( $type, $args = array() ) {
		$this->name = $type;

		$this->set_props( $args );
	}

	/**
	 * Sets activity type properties.
	 *
	 * See the bp_register_activity_type() function for accepted arguments for `$args`.
	 *
	 * @since 14.0.0
	 *
	 * @param array|string $args Array or string of arguments for registering an activity type.
	 */
	public function set_props( $args ) {
		$default_prop   = array( 'activity' );
		$roles          = array( 'content', 'log', 'reaction' );
		$this->labels   = new stdClass();
		$this->supports = new stdClass();

		$r = bp_parse_args(
			$args,
			array(
				'components'            => $default_prop,
				'role'                  => 'content',
				'feature_name'          => '',
				'description'           => '',
				'labels'                => array(),
				'format_callback'       => '',
				'streams'               => $default_prop,
				'position'              => 0,
				'supports'              => array(),
			),
			'activity_type_props'
		);

		if ( $r['components'] && is_array( $r['components'] ) ) {
			$this->components = array_map( 'sanitize_key', $r['components'] );
		} else {
			$this->components = $default_prop;
		}

		if ( $r['role'] && in_array( $r['role'], $roles, true ) ) {
			$this->role = $r['role'];
		} else {
			$this->role = 'content';
		}

		if ( 'reaction' === $this->role ) {
			if ( ! $r['feature_name'] ) {
				_doing_it_wrong( 'feature_name', __( 'The `feature_name` property of this Activity type (having a reaction role) is required.', 'buddypress' ), 'BuddyPress 14.0.0' );
			} else {
				$this->feature_name = sanitize_key( $r['feature_name'] );
			}
		}

		if ( $r['description'] ) {
			$this->description = esc_html( $r['description'] );
		} else {
			$this->description = sprintf( esc_html__( 'No description provided for the %s activity type.', 'buddypress' ), esc_html( $this->name ) );
		}

		$singular_name = ucfirst( $this->name );
		if ( $r['labels'] && isset( $r['labels']['singular_name'] ) && $r['labels']['singular_name'] ) {
			$singular_name = esc_html( $r['labels']['singular_name'] );
		}
		$this->labels->singular_name = $singular_name;

		$plural_name = ucfirst( $this->name );
		if ( $r['labels'] && isset( $r['labels']['plural_name'] ) && $r['labels']['plural_name'] ) {
			$plural_name = esc_html( $r['labels']['plural_name'] );
		}
		$this->labels->plural_name = $plural_name;

		$front_filter = $singular_name;
		if ( $r['labels'] && isset( $r['labels']['front_filter'] ) && $r['labels']['front_filter'] ) {
			$front_filter = esc_html( $r['labels']['front_filter'] );
		}
		$this->labels->front_filter = $front_filter;

		$admin_filter = $front_filter;
		if ( $r['labels'] && isset( $r['labels']['admin_filter'] ) && $r['labels']['admin_filter'] ) {
			$admin_filter = esc_html( $r['labels']['admin_filter'] );
		}
		$this->labels->admin_filter = $admin_filter;

		$do_action = sprintf(
			__( 'add the %s activity type', 'buddypress' ),
			esc_html( $singular_name )
		);
		if ( $r['labels'] && isset( $r['labels']['do_action'] ) && $r['labels']['do_action'] ) {
			$do_action = esc_html( $r['labels']['do_action'] );
		}
		$this->labels->do_action = $do_action;

		$doing_action = sprintf(
			__( 'adding the %s activity type', 'buddypress' ),
			esc_html( $singular_name )
		);
		if ( $r['labels'] && isset( $r['labels']['doing_action'] ) && $r['labels']['doing_action'] ) {
			$doing_action = esc_html( $r['labels']['doing_action'] );
		}
		$this->labels->doing_action = $doing_action;

		$did_action = sprintf(
			__( 'added the %s activity type', 'buddypress' ),
			esc_html( $singular_name )
		);
		if ( $r['labels'] && isset( $r['labels']['did_action'] ) && $r['labels']['did_action'] ) {
			$did_action = esc_html( $r['labels']['did_action'] );
		}
		$this->labels->did_action = $did_action;

		$undo_action = sprintf(
			__( 'remove the %s activity type', 'buddypress' ),
			esc_html( $singular_name )
		);
		if ( $r['labels'] && isset( $r['labels']['undo_action'] ) && $r['labels']['undo_action'] ) {
			$undo_action = esc_html( $r['labels']['undo_action'] );
		}
		$this->labels->undo_action = $undo_action;

		$undoing_action = sprintf(
			__( 'removing the %s activity type.', 'buddypress' ),
			esc_html( $singular_name )
		);
		if ( $r['labels'] && isset( $r['labels']['undoing_action'] ) && $r['labels']['undoing_action'] ) {
			$undoing_action = esc_html( $r['labels']['undoing_action'] );
		}
		$this->labels->undoing_action = $undoing_action;

		$undid_action = sprintf(
			__( 'removed the %s activity type', 'buddypress' ),
			esc_html( $singular_name )
		);
		if ( $r['labels'] && isset( $r['labels']['undid_action'] ) && $r['labels']['undid_action'] ) {
			$undid_action = esc_html( $r['labels']['undid_action'] );
		}
		$this->labels->undid_action = $undid_action;

		if ( $r['format_callback'] && is_callable( $r['format_callback'] ) ) {
			$this->format_callback = $r['format_callback'];
		} else {
			$this->format_callback = '';
		}

		if ( $r['streams'] && is_array( $r['streams'] ) ) {
			$this->streams = array_map( 'sanitize_key', $r['streams'] );
		} else {
			$this->streams = $default_prop;
		}

		$this->position = (int) $r['position'];

		if ( $r['supports'] && is_array( $r['supports'] ) ) {
			foreach ( $r['supports'] as $key_feature => $feature_args ) {
				if ( is_numeric( $key_feature ) ) {
					$feature = sanitize_key( $feature_args );
					$args    = true;
				} else {
					$feature = sanitize_key( $key_feature );
					$args    = (array) $feature_args;
				}

				$this->add_support( $feature, $args );
			}
		}
	}

	/**
	 * Sets the features support for the post type.
	 *
	 * @since 14.0.0
	 *
	 * @param string     $feature The name key for the feature.
	 * @param bool|array $args    True or an array of properties.
	 */
	public function add_support( $feature, $args ) {
		$this->supports->{$feature} = $args;
	}

	/**
	 * Sets the features support for the post type.
	 *
	 * @since 14.0.0
	 *
	 * @param string $feature The name key for the feature.
	 */
	public function remove_support( $feature ) {
		unset( $this->supports->{$feature} );
	}
}
