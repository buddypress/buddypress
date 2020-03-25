<?php
/**
 * Core BP Blocks functions.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 6.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress blocks require WordPress >= 5.0.0 & the BP REST API.
 *
 * @since 6.0.0
 *
 * @return bool True if the current installation supports BP Blocks.
 *              False otherwise.
 */
function bp_support_blocks() {
	return function_exists( 'register_block_type' ) && bp_rest_api_is_available();
}

/**
 * Registers the BP Block components.
 *
 * @since 6.0.0
 */
function bp_register_block_components() {
	wp_register_script(
		'bp-block-components',
		plugins_url( 'js/block-components.js', __FILE__ ),
		array(
			'wp-element',
			'wp-components',
			'wp-i18n',
			'wp-api-fetch',
		),
		bp_get_version()
	);
}
add_action( 'bp_blocks_init', 'bp_register_block_components', 1 );

/**
 * Filters the Block Editor settings to gather BuddyPress ones into a `bp` key.
 *
 * @since 6.0.0
 *
 * @param array $editor_settings Default editor settings.
 * @return array The editor settings including BP blocks specific ones.
 */
function bp_blocks_editor_settings( $editor_settings = array() ) {
	/**
	 * Filter here to include your BP Blocks specific settings.
	 *
	 * @since 6.0.0
	 *
	 * @param array $bp_editor_settings BP blocks specific editor settings.
	 */
	$bp_editor_settings = (array) apply_filters( 'bp_blocks_editor_settings', array() );

	if ( $bp_editor_settings ) {
		$editor_settings['bp'] = $bp_editor_settings;
	}

	return $editor_settings;
}
add_filter( 'block_editor_settings', 'bp_blocks_editor_settings' );

/**
 * Register a BuddyPress block type.
 *
 * @since 6.0.0
 *
 * @param array $args The registration arguments for the block type.
 * @return BP_Block   The BuddyPress block type object.
 */
function bp_register_block( $args = array() ) {
	return new BP_Block( $args );
}
