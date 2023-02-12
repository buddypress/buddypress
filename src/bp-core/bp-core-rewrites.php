<?php
/**
 * Core Rewrite API functions.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 12.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets default URL chunks rewrite information.
 *
 * @since 12.0.0
 *
 * @return array Default URL chunks rewrite information.
 */
function bp_rewrites_get_default_url_chunks() {
	return array(
		'directory'                    => array(
			'regex' => '([1]{1,})',
			'order' => 100,
		),
		'single_item'                  => array(
			'regex' => '([^/]+)',
			'order' => 90,
		),
		'single_item_component'        => array(
			'regex' => '([^/]+)',
			'order' => 80,
		),
		'single_item_action'           => array(
			'regex' => '([^/]+)',
			'order' => 70,
		),
		'single_item_action_variables' => array(
			'regex' => '(.+?)',
			'order' => 60,
		),
	);
}
