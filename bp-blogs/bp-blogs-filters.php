<?php

/**
 * Filters related to the Blogs component.
 *
 * @package BuddyPress
 * @subpackage Blogs
 * @since 1.6
 */

/**
 * Ensures that the 'Create a new site' link at wp-admin/my-sites.php points to the BP blog signup
 *
 * @since 1.6
 * @uses apply_filters() Filter bp_blogs_creation_location to alter the returned value
 *
 * @param string $url The original URL (points to wp-signup.php by default)
 * @return string The new URL
 */
function bp_blogs_creation_location( $url ) {
     return apply_filters( 'bp_blogs_creation_location', trailingslashit( bp_get_root_domain() . '/' . bp_get_blogs_slug() . '/create', $url ) );
}
add_filter( 'wp_signup_location', 'bp_blogs_creation_location' );


?>