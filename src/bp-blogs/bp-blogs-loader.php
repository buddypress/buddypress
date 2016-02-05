<?php
/**
 * BuddyPress Blogs Loader
 *
 * The blogs component tracks posts and comments to member activity streams,
 * shows blogs the member can post to in their profiles, and caches useful
 * information from those blogs to make querying blogs in bulk more performant.
 *
 * @package BuddyPress
 * @subpackage BlogsCore
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require dirname( __FILE__ ) . '/classes/class-bp-blogs-component.php';

/**
 * Set up the bp-blogs component.
 */
function bp_setup_blogs() {
	buddypress()->blogs = new BP_Blogs_Component();
}
add_action( 'bp_setup_components', 'bp_setup_blogs', 6 );
