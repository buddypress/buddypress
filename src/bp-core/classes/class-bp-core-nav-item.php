<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Navigation item.
 *
 * @since 2.6.0
 */
class BP_Core_Nav_Item extends ArrayObject {
	public function __construct( $data ) {
		parent::__construct( $data, ArrayObject::ARRAY_AS_PROPS );
	}
}
