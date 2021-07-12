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
	return bp_is_running_wp( '5.0.0' ) && bp_rest_api_is_available();
}

/**
 * Registers the BP Block components.
 *
 * @since 6.0.0
 * @since 9.0.0 Adds a dependency to `wp-server-side-render` if WP >= 5.3.
 *              Uses a dependency to `wp-editor` otherwise.
 */
function bp_register_block_components() {
	$server_side_renderer_dep = 'wp-server-side-render';
	if ( bp_is_running_wp( '5.3.0', '<' ) ) {
		$server_side_renderer_dep = 'wp-editor';
	}

	wp_register_script(
		'bp-block-components',
		plugins_url( 'js/block-components.js', __FILE__ ),
		array(
			'wp-element',
			'wp-components',
			'wp-i18n',
			'wp-api-fetch',
			'wp-url',
			$server_side_renderer_dep,
		),
		bp_get_version(),
		false
	);

	// Adds BP Block Components to the `bp` global.
	wp_add_inline_script(
		'bp-block-components',
		'window.bp = window.bp || {};
		bp.blockComponents = bpBlock.blockComponents;
		delete bpBlock;',
		'after'
	);
}
add_action( 'bp_blocks_init', 'bp_register_block_components', 1 );

/**
 * Registers the BP Block Assets.
 *
 * @since 9.0.0
 */
function bp_register_block_assets() {
	wp_register_script(
		'bp-block-data',
		plugins_url( 'js/block-data.js', __FILE__ ),
		array(
			'wp-data',
			'wp-api-fetch',
			'lodash',
		),
		bp_get_version(),
		false
	);

	// Adds BP Block Assets to the `bp` global.
	wp_add_inline_script(
		'bp-block-data',
		sprintf(
			'window.bp = window.bp || {};
			bp.blockData = bpBlock.blockData;
			bp.blockData.embedScriptURL = \'%s\';
			delete bpBlock;',
			esc_url_raw( includes_url( 'js/wp-embed.min.js' ) )
		),
		'after'
	);
}
add_action( 'bp_blocks_init', 'bp_register_block_assets', 2 );

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

/**
 * Select the right `block_editor_settings` filter according to WP version.
 *
 * @since 8.0.0
 */
function bp_block_init_editor_settings_filter() {
	if ( function_exists( 'get_block_editor_settings' ) ) {
		add_filter( 'block_editor_settings_all', 'bp_blocks_editor_settings' );
	} else {
		add_filter( 'block_editor_settings', 'bp_blocks_editor_settings' );
	}
}
add_action( 'bp_init', 'bp_block_init_editor_settings_filter' );

/**
 * Preload the Active BuddyPress Components.
 *
 * @since 9.0.0
 *
 * @param string[] $paths The Block Editors preload paths.
 * @return string[] The Block Editors preload paths.
 */
function bp_blocks_preload_paths( $paths = array() ) {
	return array_merge(
		$paths,
		array(
			'/buddypress/v1/components?status=active',
		)
	);
}
add_filter( 'block_editor_rest_api_preload_paths', 'bp_blocks_preload_paths' );

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
