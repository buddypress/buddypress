<?php

/**
 * Filters related to the Blogs component.
 *
 * @package BuddyPress
 * @subpackage Blogs
 * @since BuddyPress (1.6.0)
 */

/** Display Filters **********************************************************/

add_filter( 'bp_get_blog_latest_post_title', 'wptexturize'   );
add_filter( 'bp_get_blog_latest_post_title', 'convert_chars' );
add_filter( 'bp_get_blog_latest_post_title', 'trim'          );

add_filter( 'bp_blog_latest_post_content', 'wptexturize'        );
add_filter( 'bp_blog_latest_post_content', 'convert_smilies'    );
add_filter( 'bp_blog_latest_post_content', 'convert_chars'      );
add_filter( 'bp_blog_latest_post_content', 'wpautop'            );
add_filter( 'bp_blog_latest_post_content', 'shortcode_unautop'  );
add_filter( 'bp_blog_latest_post_content', 'prepend_attachment' );

/**
 * Ensure that the 'Create a new site' link at wp-admin/my-sites.php points to the BP blog signup.
 *
 * @since BuddyPress (1.6.0)
 *
 * @uses apply_filters() Filter 'bp_blogs_creation_location' to alter the
 *       returned value.
 *
 * @param string $url The original URL (points to wp-signup.php by default).
 * @return string The new URL.
 */
function bp_blogs_creation_location( $url ) {
	return apply_filters( 'bp_blogs_creation_location', trailingslashit( bp_get_root_domain() . '/' . bp_get_blogs_root_slug() . '/create', $url ) );
}
add_filter( 'wp_signup_location', 'bp_blogs_creation_location' );

/**
 * Only select comments by ID instead of all fields when using get_comments().
 *
 * @since BuddyPress (2.1.0)
 *
 * @see bp_blogs_update_post()
 *
 * @param array Current SQL clauses in array format
 * @return array
 */
function bp_blogs_comments_clauses_select_by_id( $retval ) {
	$retval['fields'] = 'comment_ID';
	
	return $retval;
}