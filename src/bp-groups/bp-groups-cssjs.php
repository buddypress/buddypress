<?php
/**
 * Groups component CSS/JS
 *
 * @package BuddyPress
 * @subpackage GroupsScripts
 * @since 5.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Groups JavaScripts.
 *
 * @since 5.0.0
 */
function bp_groups_register_scripts() {
    wp_register_script(
        'bp-group-manage-members',
        sprintf( '%1$sbp-groups/js/manage-members%2$s.js', buddypress()->plugin_url, bp_core_get_minified_asset_suffix() ),
        array( 'json2', 'wp-backbone', 'wp-api-request' ),
        bp_get_version(),
        true
    );
}
add_action( 'bp_enqueue_scripts',       'bp_groups_register_scripts', 1 );
add_action( 'bp_admin_enqueue_scripts', 'bp_groups_register_scripts', 1 );

/**
 * Get JavaScript data for the Manage Group Members UI.
 *
 * @since 5.0.0
 *
 * @param  integer $group_id Required. The Group ID whose members has to be managed.
 * @return array   The JavaScript data.
 */
function bp_groups_get_group_manage_members_script_data( $group_id = 0 ) {
	if ( ! $group_id ) {
		return array();
	} else {
		$group_id = (int) $group_id;
	}

	$path = sprintf( '/%1$s/%2$s/%3$s/%4$s/members?exclude_admins=false',
		bp_rest_namespace(),
		bp_rest_version(),
		buddypress()->groups->id,
		$group_id
	);

	$preloaded_members = rest_preload_api_request( '', $path );

	return array(
		'path'      => remove_query_arg( 'exclude_admins', $path ),
		'preloaded' => reset( $preloaded_members ),
		'roles'     => bp_groups_get_group_roles(),
		'strings'    => array(
			'allMembers' => _x( 'All members', 'Group Manage Members dropdown default option', 'buddypress' ),
		),
	);
}

/**
 * Registers a new script to manage the dynamic part of the Dynamic groups widget/block.
 *
 * @since 9.0.0
 * @since 12.0.0 Uses the `@wordpress/scripts` `index.asset.php` generated file to get dependencies.
 *
 * @param array $scripts Data about the scripts to register.
 * @return array Data about the scripts to register.
 */
function bp_groups_register_widget_block_scripts( $scripts = array() ) {
	if ( ! bp_support_blocks() ) {
		return $scripts;
	}

	$asset      = array(
		'dependencies' => array(),
		'version'      => ''
	);
	$asset_path = trailingslashit( dirname( __FILE__ ) ) . 'blocks/dynamic-widget/index.asset.php';

	if ( file_exists( $asset_path ) ) {
		$asset = require $asset_path;
	}

	$scripts['bp-dynamic-groups-script'] = array(
		'file'         => plugins_url( 'blocks/dynamic-widget/index.js', __FILE__ ),
		'dependencies' => $asset['dependencies'],
		'footer'       => true,
	);

	return $scripts;
}
add_filter( 'bp_core_register_common_scripts', 'bp_groups_register_widget_block_scripts', 9, 1 );
