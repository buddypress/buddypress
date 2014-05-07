<?php

/**
 * The BuddyPress Plugin
 *
 * BuddyPress is social networking software with a twist from the creators of WordPress.
 *
 * @package BuddyPress
 * @subpackage Main
 */

/**
 * Plugin Name: BuddyPress
 * Plugin URI:  http://buddypress.org
 * Description: Social networking in a box. Build a social network for your company, school, sports team or niche community all based on the power and flexibility of WordPress.
 * Author:      The BuddyPress Community
 * Author URI:  http://buddypress.org/community/members/
 * Version:     2.1-alpha
 * Text Domain: buddypress
 * Domain Path: /bp-languages/
 * License:     GPLv2 or later (license.txt)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Assume you want to load from build
$bp_loader = __DIR__ . '/build/bp-loader.php';

// Load from source if no build exists
if ( ! file_exists( $bp_loader ) || defined( 'BP_LOAD_SOURCE' ) ) {
	$bp_loader = __DIR__ . '/src/bp-loader.php';
}

// Include BuddyPress
include( $bp_loader );

// Unset the loader, since it's loaded in global scope
unset( $bp_loader );