<?php
/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 14.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Select the right `block_editor_settings` filter according to WP version.
 *
 * @since 8.0.0
 * @deprecated 14.0.0
 */
function bp_block_init_editor_settings_filter() {
	_deprecated_function( __FUNCTION__, '14.0.0' );
}

/**
 * Select the right `block_categories` filter according to WP version.
 *
 * @since 8.0.0
 * @since 12.0.0 This category is left for third party plugin but not used anymmore.
 * @deprecated 14.0.0
 */
function bp_block_init_category_filter() {
	_deprecated_function( __FUNCTION__, '14.0.0' );
}
