<?php
/**
 * Filters related to the Blogs component.
 *
 * @package BuddyPress
 * @subpackage BlogFilters
 * @since 1.6.0
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
 * @since 1.6.0
 *
 * @param string $url The original URL (points to wp-signup.php by default).
 * @return string The new URL.
 */
function bp_blogs_creation_location( $url ) {
	$bp_url = bp_get_blogs_directory_url(
		array(
			'create_single_item' => 1,
		)
	);

	/**
	 * Filters the 'Create a new site' link URL.
	 *
	 * @since 1.6.0
	 *
	 * @param string $bp_url URL for the 'Create a new site' signup page.
	 * @param string $url    The original URL (points to wp-signup.php by default).
	 */
	return apply_filters( 'bp_blogs_creation_location', $bp_url, $url );
}
add_filter( 'wp_signup_location', 'bp_blogs_creation_location' );

/**
 * Only select comments by ID instead of all fields when using get_comments().
 *
 * @since 2.1.0
 *
 * @see bp_blogs_update_post_activity_meta()
 *
 * @param array $retval Current SQL clauses in array format.
 * @return array
 */
function bp_blogs_comments_clauses_select_by_id( $retval ) {
	$retval['fields'] = 'comment_ID';

	return $retval;
}

/**
 * Check whether the current activity about a post or a comment can be published.
 *
 * Abstracted from the deprecated `bp_blogs_record_post()`.
 *
 * @since 2.2.0
 *
 * @param bool $return  Whether the post should be published.
 * @param int  $blog_id ID of the blog.
 * @param int  $post_id ID of the post.
 * @param int  $user_id ID of the post author.
 * @return bool True to authorize the post to be published, otherwise false.
 */
function bp_blogs_post_pre_publish( $return = true, $blog_id = 0, $post_id = 0, $user_id = 0 ) {

	// If blog is not trackable, do not record the activity.
	if ( ! bp_blogs_is_blog_trackable( $blog_id, $user_id ) ) {
		return false;
	}

	/*
	 * Stop infinite loops with WordPress MU Sitewide Tags.
	 * That plugin changed the way its settings were stored at some point. Thus the dual check.
	 */
	$sitewide_tags_blog_settings = bp_core_get_root_option( 'sitewide_tags_blog' );
	if ( ! empty( $sitewide_tags_blog_settings ) ) {
		$st_options   = maybe_unserialize( $sitewide_tags_blog_settings );
		$tags_blog_id = isset( $st_options['tags_blog_id'] ) ? $st_options['tags_blog_id'] : 0;
	} else {
		$tags_blog_id = bp_core_get_root_option( 'sitewide_tags_blog' );
		$tags_blog_id = intval( $tags_blog_id );
	}

	/**
	 * Filters whether or not BuddyPress should block sitewide tags activity.
	 *
	 * @since 2.2.0
	 *
	 * @param bool $value Current status of the sitewide tags activity.
	 */
	if ( (int) $blog_id === $tags_blog_id && apply_filters( 'bp_blogs_block_sitewide_tags_activity', true ) ) {
		return false;
	}

	/**
	 * Filters whether or not the current blog is public.
	 *
	 * @since 2.2.0
	 *
	 * @param int $value Value from the blog_public option for the current blog.
	 */
	$is_blog_public = apply_filters( 'bp_is_blog_public', (int) get_blog_option( $blog_id, 'blog_public' ) );

	if ( 0 === $is_blog_public && is_multisite() ) {
		return false;
	}

	return $return;
}
add_filter( 'bp_activity_post_pre_publish', 'bp_blogs_post_pre_publish', 10, 4 );
add_filter( 'bp_activity_post_pre_comment', 'bp_blogs_post_pre_publish', 10, 4 );

/**
 * Registers our custom thumb size with WP's Site Icon feature.
 *
 * @since 2.7.0
 *
 * @param  array $sizes Current array of custom site icon sizes.
 * @return array
 */
function bp_blogs_register_custom_site_icon_size( $sizes ) {
	$sizes[] = bp_core_avatar_thumb_width();
	return $sizes;
}
add_filter( 'site_icon_image_sizes', 'bp_blogs_register_custom_site_icon_size' );

/**
 * Use the mystery blog avatar for blogs.
 *
 * @since 7.0.0
 *
 * @param string $avatar Current avatar src.
 * @param array  $params Avatar params.
 * @return string
 */
function bp_blogs_default_avatar( $avatar, $params ) {
	if ( isset( $params['object'] ) && 'blog' === $params['object'] ) {
		if ( isset( $params['type'] ) && 'thumb' === $params['type'] ) {
			$file = 'mystery-blog-50.png';
		} else {
			$file = 'mystery-blog.png';
		}

		$avatar = buddypress()->plugin_url . "bp-core/images/$file";
	}

	return $avatar;
}
add_filter( 'bp_core_default_avatar',       'bp_blogs_default_avatar', 10, 2 );
add_filter( 'bp_core_avatar_default_thumb', 'bp_blogs_default_avatar', 10, 2 );

/**
 * Filters the column name during blog metadata queries.
 *
 * This filters 'sanitize_key', which is used during various core metadata
 * API functions: {@link https://core.trac.wordpress.org/browser/branches/4.9/src/wp-includes/meta.php?lines=47,160,324}.
 * Due to how we are passing our meta type, we need to ensure that the correct
 * DB column is referenced during blogmeta queries.
 *
 * @since 4.0.0
 *
 * @see bp_blogs_delete_blogmeta()
 * @see bp_blogs_get_blogmeta()
 * @see bp_blogs_update_blogmeta()
 * @see bp_blogs_add_blogmeta()
 *
 * @param string $retval
 *
 * @return string
 */
function bp_blogs_filter_meta_column_name( $retval ) {
	if ( 'bp_blog_id' === $retval ) {
		$retval = 'blog_id';
	}
	return $retval;
}
