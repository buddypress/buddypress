<?php
/**
 * Core component class.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 2.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Nav.
 *
 * This class is used to build each component's navigation.
 *
 * @since 2.6.0
 */
class BP_Core_Nav {

	/**
	 * An associative array containing the nav items for the object ID.
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	protected $nav;

	/**
	 * The current object ID.
	 *
	 * @since 2.6.0
	 *
	 * @var int
	 */
	private $object_id;

	/**
	 * The component ID.
	 *
	 * @since 12.0.0
	 *
	 * @var string
	 */
	private $component_id;

	/**
	 * Initializes the Nav belonging to the specified object.
	 *
	 * @since 2.6.0
	 *
	 * @param int    $object_id The item ID to build the nav for. Default is the displayed user ID.
	 * @param string $component_id Optional. The component ID. Default is 'members'.
	 */
	public function __construct( $object_id = 0, $component_id = 'members' ) {
		if ( bp_is_active( $component_id ) ) {
			$this->component_id = $component_id;
		}

		if ( empty( $object_id ) && 'members' === $this->component_id ) {
			$this->object_id = (int) bp_displayed_user_id();
		} else {
			$this->object_id = (int) $object_id;
		}

		$this->nav[ $this->object_id ] = array();
	}

	/**
	 * Checks whether a nav item is set.
	 *
	 * @since 2.6.0
	 *
	 * @param string $key The requested nav slug.
	 * @return bool True if the nav item is set, false otherwise.
	 */
	public function __isset( $key ) {
		return isset( $this->nav[ $this->object_id ][ $key ] );
	}

	/**
	 * Gets a nav item.
	 *
	 * @since 2.6.0
	 *
	 * @param string $key The requested nav slug.
	 * @return mixed The value corresponding to the requested nav item.
	 */
	public function __get( $key ) {
		if ( ! isset( $this->nav[ $this->object_id ][ $key ] ) ) {
			$this->nav[ $this->object_id ][ $key ] = null;
		}

		return $this->nav[ $this->object_id ][ $key ];
	}

	/**
	 * Sets a nav item.
	 *
	 * @since 2.6.0
	 *
	 * @param string $key   The requested nav slug.
	 * @param mixed  $value The value of the nav item.
	 */
	public function __set( $key, $value ) {
		if ( is_array( $value ) ) {
			$value['primary'] = true;
		}

		$this->nav[ $this->object_id ][ $key ] = new BP_Core_Nav_Item( $value );
	}

	/**
	 * Gets a specific nav item or array of nav items.
	 *
	 * @since 2.6.0
	 *
	 * @param string $key The nav item slug to get. Optional.
	 * @return mixed An array of nav item, a single nav item, or null if none found.
	 */
	public function get( $key = '' ) {
		$return = null;

		// Return the requested nav item.
		if ( ! empty( $key ) ) {
			if ( ! isset( $this->nav[ $this->object_id ][ $key ] ) ) {
				$return = null;
			} else {
				$return = $this->nav[ $this->object_id ][ $key ];
			}

			// Return all nav item items.
		} else {
			$return = $this->nav[ $this->object_id ];
		}

		return $return;
	}

	/**
	 * Adds a new nav item.
	 *
	 * @since 2.6.0
	 *
	 * @param array $args The nav item's arguments.
	 * @return BP_Core_Nav_Item
	 */
	public function add_nav( $args ) {
		if ( empty( $args['slug'] ) ) {
			return false;
		}

		$path_chunks = array(
			'component_id' => $this->component_id,
		);

		// We have a child and the parent exists.
		if ( ! empty( $args['parent_slug'] ) ) {
			$slug                                 = $args['parent_slug'] . '/' . $args['slug'];
			$path_chunks['single_item_component'] = $args['parent_slug'];
			$path_chunks['single_item_action']    = $args['slug'];
			$args['secondary']                    = true;

			// This is a parent.
		} else {
			$slug                                 = $args['slug'];
			$path_chunks['single_item_component'] = $slug;
			$args['primary']                      = true;
		}

		/*
		 * This is where we set links using BP Rewrites.
		 *
		 * @since 12.0.0
		 */
		if ( ! isset( $args['link'] ) || ! $args['link'] ) {
			if ( 'groups' === $this->component_id ) {
				if ( isset( $path_chunks['single_item_component'] ) ) {
					$path_chunks['single_item'] = str_replace( '_manage', '', $path_chunks['single_item_component'] );
				} else {
					$path_chunks['single_item'] = groups_get_slug( $this->object_id );
				}

				$chunk = 'single_item_action';
				if ( $path_chunks['single_item'] . '_manage' === $path_chunks['single_item_component'] ) {
					$chunk                             = 'single_item_action_variables';
					$path_chunks[ $chunk ]             = $path_chunks['single_item_action'];
					$path_chunks['single_item_action'] = bp_rewrites_get_slug( 'groups', 'bp_group_read_admin', 'admin' );
					$group_screens                     = bp_get_group_screens( 'manage' );
				} else {
					$group_screens = bp_get_group_screens( 'read' );
				}

				if ( isset( $group_screens[ $args['slug'] ] ) ) {
					$args['rewrite_id']    = $group_screens[ $args['slug'] ]['rewrite_id'];
					$path_chunks[ $chunk ] = bp_rewrites_get_slug( 'groups', $args['rewrite_id'], $args['slug'] );

					if ( 'single_item_action_variables' === $chunk ) {
						$path_chunks[ $chunk ] = array( $path_chunks[ $chunk ] );
					}
				}

				unset( $path_chunks['single_item_component'] );
				$args['link'] = bp_rewrites_get_url( $path_chunks );
			} else {
				if ( isset( $path_chunks['single_item_component'] ) ) {
					// First try to get custom item action slugs.
					if ( isset( $path_chunks['single_item_action'] ) && ! is_numeric( $path_chunks['single_item_action'] ) ) {
						$path_chunks['single_item_action'] = bp_rewrites_get_slug(
							'members',
							'member_' . $path_chunks['single_item_component'] . '_' . str_replace( '-', '_', $path_chunks['single_item_action'] ),
							$path_chunks['single_item_action']
						);
					}

					// Then only try to get custom item component slug.
					$path_chunks['single_item_component'] = bp_rewrites_get_slug( 'members', 'member_' . str_replace( '-', '_', $path_chunks['single_item_component'] ), $path_chunks['single_item_component'] );
				}

				$args['link'] = bp_members_get_user_url( $this->object_id, $path_chunks );
			}
		}

		// Add to the nav.
		$this->nav[ $this->object_id ][ $slug ] = new BP_Core_Nav_Item( $args );

		return $this->nav[ $this->object_id ][ $slug ];
	}

	/**
	 * Edits a nav item.
	 *
	 * @since 2.6.0
	 *
	 * @param array  $args        The nav item's arguments.
	 * @param string $slug        The slug of the nav item.
	 * @param string $parent_slug The slug of the parent nav item (required to edit a child).
	 * @return BP_Core_Nav_Item
	 */
	public function edit_nav( $args = array(), $slug = '', $parent_slug = '' ) {
		if ( empty( $slug ) ) {
			return false;
		}

		// We're editing a parent!
		if ( empty( $parent_slug ) ) {
			$nav_items = $this->get_primary( array( 'slug' => $slug ), false );

			if ( ! $nav_items ) {
				return false;
			}

			$nav_item                               = reset( $nav_items );
			$this->nav[ $this->object_id ][ $slug ] = new BP_Core_Nav_Item(
				bp_parse_args(
					$args,
					(array) $nav_item
				)
			);

			// Return the edited object.
			return $this->nav[ $this->object_id ][ $slug ];

			// We're editing a child.
		} else {
			$sub_items = $this->get_secondary(
				array(
					'parent_slug' => $parent_slug,
					'slug'        => $slug,
				),
				false
			);

			if ( ! $sub_items ) {
				return false;
			}

			$sub_item = reset( $sub_items );
			$params   = bp_parse_args(
				$args,
				(array) $sub_item
			);

			// When we have parents, it's for life, we can't change them!
			if ( empty( $params['parent_slug'] ) || $parent_slug !== $params['parent_slug'] ) {
				return false;
			}

			$this->nav[ $this->object_id ][ $parent_slug . '/' . $slug ] = new BP_Core_Nav_Item( $params );

			// Return the edited object.
			return $this->nav[ $this->object_id ][ $parent_slug . '/' . $slug ];
		}
	}

	/**
	 * Unset an item or a subitem of the nav.
	 *
	 * @since 2.6.0
	 *
	 * @param string $slug        The slug of the main item.
	 * @param string $parent_slug The slug of the sub item.
	 * @return false|callable|array False on failure, the screen function(s) on success.
	 */
	public function delete_nav( $slug = '', $parent_slug = '' ) {

		// Bail if slug is empty.
		if ( empty( $slug ) ) {
			return false;
		}

		// We're deleting a child.
		if ( ! empty( $parent_slug ) ) {

			// Validate the subnav.
			$sub_items = $this->get_secondary(
				array(
					'parent_slug' => $parent_slug,
					'slug'        => $slug,
				),
				false
			);

			if ( ! $sub_items ) {
				return false;
			}

			$sub_item = reset( $sub_items );

			if ( empty( $sub_item->slug ) ) {
				return false;
			}

			// Delete the child.
			unset( $this->nav[ $this->object_id ][ $parent_slug . '/' . $slug ] );

			// Return the deleted item's screen function.
			return array( $sub_item->screen_function );

			// We're deleting a parent.
		} else {
			// Validate the nav.
			$nav_items = $this->get_primary( array( 'slug' => $slug ), false );

			if ( ! $nav_items ) {
				return false;
			}

			$nav_item = reset( $nav_items );

			if ( empty( $nav_item->slug ) ) {
				return false;
			}

			$screen_functions = array( $nav_item->screen_function );

			// Life's unfair, children won't survive the parent.
			$sub_items = $this->get_secondary( array( 'parent_slug' => $nav_item->slug ), false );

			if ( ! empty( $sub_items ) ) {
				foreach ( $sub_items as $sub_item ) {
					$screen_functions[] = $sub_item->screen_function;

					// Delete the child.
					unset( $this->nav[ $this->object_id ][ $nav_item->slug . '/' . $sub_item->slug ] );
				}
			}

			// Delete the parent.
			unset( $this->nav[ $this->object_id ][ $nav_item->slug ] );

			// Return the deleted item's screen functions.
			return $screen_functions;
		}
	}

	/**
	 * Sorts a list of nav items.
	 *
	 * @since 2.6.0
	 *
	 * @param array $items The nav items to sort.
	 * @return array
	 */
	public function sort_nav( $items ) {
		$sorted = array();

		foreach ( $items as $item ) {
			// Default position.
			$position = 99;

			if ( isset( $item->position ) ) {
				$position = (int) $item->position;
			}

			// If position is already taken, move to the first next available.
			if ( isset( $sorted[ $position ] ) ) {
				$sorted_keys = array_keys( $sorted );

				do {
					++$position;
				} while ( in_array( $position, $sorted_keys, true ) );
			}

			$sorted[ $position ] = $item;
		}

		ksort( $sorted );

		return $sorted;
	}

	/**
	 * Gets the primary nav items.
	 *
	 * @since 2.6.0
	 *
	 * @param array $args Filters to select the specific primary items. See wp_list_filter().
	 * @param bool  $sort True to sort the nav items. False otherwise.
	 * @return array The list of primary objects nav
	 */
	public function get_primary( $args = array(), $sort = true ) {
		$params = bp_parse_args(
			$args,
			array(
				'primary' => true,
			)
		);

		// This parameter is not overridable.
		if ( empty( $params['primary'] ) ) {
			return false;
		}

		$primary_nav = wp_list_filter( $this->nav[ $this->object_id ], $params );

		if ( ! $primary_nav ) {
			return false;
		}

		if ( true !== $sort ) {
			return $primary_nav;
		}

		return $this->sort_nav( $primary_nav );
	}

	/**
	 * Gets the secondary nav items.
	 *
	 * @since 2.6.0
	 *
	 * @param array $args Filters to select the specific secondary items. See wp_list_filter().
	 * @param bool  $sort True to sort the nav items. False otherwise.
	 * @return bool|array The list of secondary objects nav, or false if none set.
	 */
	public function get_secondary( $args = array(), $sort = true ) {
		$params = bp_parse_args(
			$args,
			array(
				'parent_slug' => '',
			)
		);

		// No need to search children if the parent is not set.
		if ( empty( $params['parent_slug'] ) && empty( $params['secondary'] ) ) {
			return false;
		}

		$secondary_nav = wp_list_filter( $this->nav[ $this->object_id ], $params );

		if ( ! $secondary_nav ) {
			return false;
		}

		if ( true !== $sort ) {
			return $secondary_nav;
		}

		return $this->sort_nav( $secondary_nav );
	}

	/**
	 * Gets a nested list of visible nav items.
	 *
	 * @since 2.6.0
	 *
	 * @return array The list of visible nav items.
	 */
	public function get_item_nav() {
		$primary_nav_items = $this->get_primary( array( 'show_for_displayed_user' => true ) );

		if ( $primary_nav_items ) {
			foreach ( $primary_nav_items as $key_nav => $primary_nav ) {
				// Try to get the children.
				$children = $this->get_secondary(
					array(
						'parent_slug'     => $primary_nav->slug,
						'user_has_access' => true,
					)
				);

				if ( $children ) {
					$primary_nav_items[ $key_nav ]           = clone $primary_nav;
					$primary_nav_items[ $key_nav ]->children = $children;
				}
			}
		}

		return $primary_nav_items;
	}
}
