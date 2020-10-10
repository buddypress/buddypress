<?php
/**
 * BP Activity Blocks Functions.
 *
 * @package BuddyPress
 * @subpackage ActvityBlocks
 * @since 7.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add BP Activity blocks specific settings to the BP Blocks Editor ones.
 *
 * @since 7.0.0
 *
 * @param array $bp_editor_settings BP blocks editor settings.
 * @return array BP Activity blocks editor settings.
 */
function bp_activity_editor_settings( $bp_editor_settings = array() ) {
	return array_merge(
		$bp_editor_settings,
		array(
			'activity' => array(
				'embedScriptURL' => includes_url( 'js/wp-embed.min.js' ),
			),
		)
	);
}
add_filter( 'bp_blocks_editor_settings', 'bp_activity_editor_settings' );
