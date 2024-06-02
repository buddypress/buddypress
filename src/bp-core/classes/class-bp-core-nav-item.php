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
 * Navigation item.
 *
 * @since 2.6.0
 */
class BP_Core_Nav_Item extends ArrayObject {

	/**
	 * Constructor.
	 *
	 * @param array $data Data to populate the nav item.
	 */
	public function __construct( $data ) {
		parent::__construct( $data, ArrayObject::ARRAY_AS_PROPS );
	}
}
