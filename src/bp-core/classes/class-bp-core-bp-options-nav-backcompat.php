<?php
/**
 * Backward compatibility for the $bp->bp_options_nav global.
 *
 * @since 2.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * bp_options_nav backward compatibility class.
 *
 * This class is used to provide backward compatibility for extensions that access and modify
 * the $bp->bp_options_nav global.
 *
 * @since 2.6.0
 */
class BP_Core_BP_Options_Nav_BackCompat extends BP_Core_BP_Nav_BackCompat {
	/**
	 * Parent slug of the current nav item.
	 *
	 * @since 2.6.0
	 * @access protected
	 * @var string
	 */
	protected $parent_slug = '';

	/**
	 * Get a value of the nav array at the specified offset.
	 *
	 * @since 2.6.0
	 *
	 * @param mixed $offset Array offset.
	 * @return BP_Core_BP_Nav_BackCompat
	 */
	public function offsetGet( $offset ) {
		_doing_it_wrong(
			'bp_nav',
			__( 'These globals should not be used directly and are deprecated. Please use the BuddyPress nav functions instead.', 'buddypress' ),
			'2.6.0'
		);

		if ( empty( $this->backcompat_nav[ $offset ] ) ) {
			$nav = $this->get_nav( $offset );
			if ( $nav ) {
				$subnavs = $this->get_component_nav( $offset )->get_secondary( array( 'parent_slug' => $offset ) );
				$subnav_keyed = array();
				if ( $subnavs ) {
					foreach ( $subnavs as $subnav ) {
						$subnav_keyed[ $subnav->slug ] = (array) $subnav;
					}
				}

				$subnav_object = new self( $subnav_keyed );
				$subnav_object->set_component( $this->get_component() );
				$subnav_object->set_parent_slug( $offset );

				$this->backcompat_nav[ $offset ] = $subnav_object;
			}
		}

		if ( isset( $this->backcompat_nav[ $offset ] ) ) {
			return $this->backcompat_nav[ $offset ];
		}

		return false;
	}

	/**
	 * Unset a nav array value at the specified offset.
	 *
	 * @since 2.6.0
	 *
	 * @param mixed $offset Array offset.
	 */
	public function offsetUnset( $offset ) {
		_doing_it_wrong(
			'bp_nav',
			__( 'These globals should not be used directly and are deprecated. Please use the BuddyPress nav functions instead.', 'buddypress' ),
			'2.6.0'
		);

		$this->get_component_nav( $offset )->delete_nav( $offset, $this->get_parent_slug() );

		// Clear the cached nav.
		unset( $this->backcompat_nav[ $offset ] );
	}

	/**
	 * Get the parent slug of the current nav item.
	 *
	 * @since 2.6.0
	 *
	 * @return string
	 */
	public function get_parent_slug() {
		return $this->parent_slug;
	}

	/**
	 * Set the parent slug of the current nav item.
	 *
	 * @since 2.6.0
	 */
	public function set_parent_slug( $slug ) {
		$this->parent_slug = $slug;
	}

	/**
	 * Get the nav object corresponding to the specified offset.
	 *
	 * @since 2.6.0
	 *
	 * @param mixed $offset Array offset.
	 * @return bool|array
	 */
	public function get_nav( $offset ) {
		$nav = parent::get_nav( $offset );

		if ( ! $nav ) {
			$component_nav = $this->get_component_nav( $offset );
			$secondary_nav = $component_nav->get_secondary( array( 'slug' => $offset ), false );

			$nav = array();

			if ( empty( $primary_nav ) ) {
				return $nav;
			}

			foreach ( $primary_nav as $item ) {
				$nav[ $item->slug ] = (array) $item;
			}
		}

		return $nav;
	}
}
