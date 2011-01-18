<?php

/**
 * Deprecated Functions
 *
 * @package BuddyPress
 * @subpackage Core
 *
 */

/**
 * This function originally let plugins add support for pages in the root of the install.
 * These pages are now handled by actual WordPress pages so this function is deprecated.
 * It now simply facilitates backwards compatibility by adding a WP page if the plugin has not been
 * updated to do so.
 *
 * @deprecated 1.3
 * @deprecated Use wp_insert_post() to create a page
 * @package BuddyPress Core
 * @param $slug str The slug of the component
 * @global $bp BuddyPress global settings
 */
function bp_core_add_root_component( $slug ) {
	global $bp, $bp_pages;

	_deprecated_function( __FUNCTION__, '1.3', 'wp_insert_post()' );
	if ( empty( $bp_pages ) )
		$bp_pages = bp_core_get_page_names();

	$match = false;

	// Check if the slug is registered in the $bp->pages global
	foreach ( (array)$bp_pages as $key => $page ) {
		if ( $key == $slug || $page->slug == $slug )
			$match = true;
	}

	// If there was no match, add a page for this root component
	if ( empty( $match ) ) {
		$bp->add_root[] = $slug;
		add_action( 'init', 'bp_core_create_root_component_page' );
	}
}

function bp_core_create_root_component_page() {
	global $bp;

	$new_page_ids = array();

	foreach ( (array)$bp->add_root as $slug )
		$new_page_ids[$slug] = wp_insert_post( array( 'comment_status' => 'closed', 'ping_status' => 'closed', 'post_title' => ucwords( $slug ), 'post_status' => 'publish', 'post_type' => 'page' ) );

	$page_ids = bp_core_get_page_meta();
	$page_ids = (array) $page_ids;
	$page_ids = array_merge( (array) $new_page_ids, (array) $page_ids );

	bp_core_update_page_meta( $page_ids );
}


?>
