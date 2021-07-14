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

/**
 * Gets a Widget Block list of classnames.
 *
 * @since 9.0.0
 *
 * @param string $block_name The Block name.
 * @return array The list of widget classnames for the Block.
 */
function bp_blocks_get_widget_block_classnames( $block_name = '' ) {
	$components         = bp_core_get_active_components( array(), 'objects' );
	$components['core'] = buddypress()->core;
	$classnames         = array();

	foreach ( $components as $component ) {
		if ( isset( $component->block_globals[ $block_name ] ) ) {
			$block_props = $component->block_globals[ $block_name ]->props;

			if ( isset( $block_props['widget_classnames'] ) && $block_props['widget_classnames'] ) {
				$classnames = (array) $block_props['widget_classnames'];
				break;
			}
		}
	}

	return $classnames;
}

/**
 * Make sure the BP Widget Block classnames are included into Widget Blocks.
 *
 * @since 9.0.0
 *
 * @param string $classname The classname to be used in the block widget's container HTML.
 * @param string $block_name The name of the block.
 * @return string The classname to be used in the block widget's container HTML.
 */
function bp_widget_block_dynamic_classname( $classname, $block_name ) {
	$bp_classnames = bp_blocks_get_widget_block_classnames( $block_name );

	if ( $bp_classnames ) {
		$bp_classnames = array_map( 'sanitize_html_class', $bp_classnames );
		$classname    .= ' ' . implode( ' ', $bp_classnames );
	}

	return $classname;
}
add_filter( 'widget_block_dynamic_classname', 'bp_widget_block_dynamic_classname', 10, 2 );
