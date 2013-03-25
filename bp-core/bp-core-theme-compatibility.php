<?php

/**
 * BuddyPress Core Theme Compatibility
 *
 * @package BuddyPress
 * @subpackage ThemeCompatibility
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Theme Compat **************************************************************/

/**
 * What follows is an attempt at intercepting the natural page load process
 * to replace the_content() with the appropriate BuddyPress content.
 *
 * To do this, BuddyPress does several direct manipulations of global variables
 * and forces them to do what they are not supposed to be doing.
 *
 * Don't try anything you're about to witness here, at home. Ever.
 */

/** Base Class ****************************************************************/

/**
 * Theme Compatibility base class
 *
 * This is only intended to be extended, and is included here as a basic guide
 * for future Theme Packs to use. @link BP_Legacy is a good example of
 * extending this class.
 *
 * @since BuddyPress (1.7)
 * @todo We should probably do something similar to BP_Component::start()
 */
class BP_Theme_Compat {

	/**
	 * Should be like:
	 *
	 * array(
	 *     'id'      => ID of the theme (should be unique)
	 *     'name'    => Name of the theme (should match style.css)
	 *     'version' => Theme version for cache busting scripts and styling
	 *     'dir'     => Path to theme
	 *     'url'     => URL to theme
	 * );
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Pass the $properties to the object on creation.
	 *
	 * @since BuddyPress (1.7)
	 * @param array $properties
	 */
    	public function __construct( Array $properties = array() ) {
		$this->_data = $properties;
	}


	/**
	 * Themes shoud use this method in their constructor.
	 *
	 * In this method, we check all types of conditions where theme compatibility
	 * should *not* run.
	 *
	 * If we pass all conditions, then we setup some additional methods to use.
	 *
	 * @since BuddyPress (1.7)
	 */
	protected function start() {

		// If the theme supports 'buddypress', bail.
		if ( current_theme_supports( 'buddypress' ) ) {
			return;

		// If the theme doesn't support BP, do some additional checks
		} else {
			// Bail if theme is a derivative of bp-default
			if ( in_array( 'bp-default', array( get_template(), get_stylesheet() ) ) ) {
				return;
			}

			// Bruteforce check for a BP template
			// Examples are clones of bp-default
			if ( locate_template( 'members/members-loop.php', false, false ) ) {
				return;
			}
		}

		// Setup methods
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Meant to be extended in your class.
	 *
	 * @since BuddyPress (1.7)
	 */
	protected function setup_globals() {}

	/**
	 * Meant to be extended in your class.
	 *
	 * @since BuddyPress (1.7)
	 */
	protected function setup_actions() {}

	/**
	 * Set a theme's property.
	 *
	 * @since BuddyPress (1.7)
	 * @param string $property
	 * @param mixed $value
	 * @return mixed
	 */
	public function __set( $property, $value ) {
		return $this->_data[$property] = $value;
	}

	/**
	 * Get a theme's property.
	 *
	 * @since BuddyPress (1.7)
	 * @param string $property
	 * @param mixed $value
	 * @return mixed
	 */
	public function __get( $property ) {
		return array_key_exists( $property, $this->_data ) ? $this->_data[$property] : '';
	}
}

/** Functions *****************************************************************/

/**
 * Setup the default theme compat theme
 *
 * @since BuddyPress (1.7)
 * @param BP_Theme_Compat $theme
 */
function bp_setup_theme_compat( $theme = '' ) {
	$bp = buddypress();

	// Make sure theme package is available, set to default if not
	if ( ! isset( $bp->theme_compat->packages[$theme] ) || ! is_a( $bp->theme_compat->packages[$theme], 'BP_Theme_Compat' ) ) {
		$theme = 'legacy';
	}

	// Set the active theme compat theme
	$bp->theme_compat->theme = $bp->theme_compat->packages[$theme];
}

/**
 * Gets the name of the BuddyPress compatable theme used, in the event the
 * currently active WordPress theme does not explicitly support BuddyPress.
 * This can be filtered or set manually. Tricky theme authors can override the
 * default and include their own BuddyPress compatability layers for their themes.
 *
 * @since BuddyPress (1.7)
 * @uses apply_filters()
 * @return string
 */
function bp_get_theme_compat_id() {
	return apply_filters( 'bp_get_theme_compat_id', buddypress()->theme_compat->theme->id );
}

/**
 * Gets the name of the BuddyPress compatable theme used, in the event the
 * currently active WordPress theme does not explicitly support BuddyPress.
 * This can be filtered or set manually. Tricky theme authors can override the
 * default and include their own BuddyPress compatability layers for their themes.
 *
 * @since BuddyPress (1.7)
 * @uses apply_filters()
 * @return string
 */
function bp_get_theme_compat_name() {
	return apply_filters( 'bp_get_theme_compat_name', buddypress()->theme_compat->theme->name );
}

/**
 * Gets the version of the BuddyPress compatable theme used, in the event the
 * currently active WordPress theme does not explicitly support BuddyPress.
 * This can be filtered or set manually. Tricky theme authors can override the
 * default and include their own BuddyPress compatability layers for their themes.
 *
 * @since BuddyPress (1.7)
 * @uses apply_filters()
 * @return string
 */
function bp_get_theme_compat_version() {
	return apply_filters( 'bp_get_theme_compat_version', buddypress()->theme_compat->theme->version );
}

/**
 * Gets the BuddyPress compatable theme used in the event the currently active
 * WordPress theme does not explicitly support BuddyPress. This can be filtered,
 * or set manually. Tricky theme authors can override the default and include
 * their own BuddyPress compatability layers for their themes.
 *
 * @since BuddyPress (1.7)
 * @uses apply_filters()
 * @return string
 */
function bp_get_theme_compat_dir() {
	return apply_filters( 'bp_get_theme_compat_dir', buddypress()->theme_compat->theme->dir );
}

/**
 * Gets the BuddyPress compatable theme used in the event the currently active
 * WordPress theme does not explicitly support BuddyPress. This can be filtered,
 * or set manually. Tricky theme authors can override the default and include
 * their own BuddyPress compatability layers for their themes.
 *
 * @since BuddyPress (1.7)
 * @uses apply_filters()
 * @return string
 */
function bp_get_theme_compat_url() {
	return apply_filters( 'bp_get_theme_compat_url', buddypress()->theme_compat->theme->url );
}

/**
 * Gets true/false if the current, loaded page uses theme compatibility
 *
 * @since BuddyPress (1.7)
 * @return bool
 */
function bp_is_theme_compat_active() {
	$bp = buddypress();

	if ( empty( $bp->theme_compat->active ) )
		return false;

	return $bp->theme_compat->active;
}

/**
 * Sets true/false if page is currently inside theme compatibility
 *
 * @since BuddyPress (1.7)
 * @param bool $set
 * @return bool
 */
function bp_set_theme_compat_active( $set = true ) {
	buddypress()->theme_compat->active = $set;

	return (bool) buddypress()->theme_compat->active;
}

/**
 * Set the theme compat templates global
 *
 * Stash possible template files for the current query. Useful if plugins want
 * to override them, or see what files are being scanned for inclusion.
 *
 * @since BuddyPress (1.7)
 */
function bp_set_theme_compat_templates( $templates = array() ) {
	buddypress()->theme_compat->templates = $templates;

	return buddypress()->theme_compat->templates;
}

/**
 * Set the theme compat template global
 *
 * Stash the template file for the current query. Useful if plugins want
 * to override it, or see what file is being included.
 *
 * @since BuddyPress (1.7)
 */
function bp_set_theme_compat_template( $template = '' ) {
	buddypress()->theme_compat->template = $template;

	return buddypress()->theme_compat->template;
}

/**
 * Set the theme compat original_template global
 *
 * Stash the original template file for the current query. Useful for checking
 * if BuddyPress was able to find a more appropriate template.
 *
 * @since BuddyPress (1.7)
 */
function bp_set_theme_compat_original_template( $template = '' ) {
	buddypress()->theme_compat->original_template = $template;

	return buddypress()->theme_compat->original_template;
}

/**
 * Set the theme compat original_template global
 *
 * Stash the original template file for the current query. Useful for checking
 * if BuddyPress was able to find a more appropriate template.
 *
 * @since BuddyPress (1.7)
 */
function bp_is_theme_compat_original_template( $template = '' ) {
	$bp = buddypress();

	if ( empty( $bp->theme_compat->original_template ) )
		return false;

	return (bool) ( $bp->theme_compat->original_template == $template );
}

/**
 * Register a new BuddyPress theme package to the active theme packages array
 *
 * @since BuddyPress (1.7)
 * @param array $theme
 */
function bp_register_theme_package( $theme = array(), $override = true ) {

	// Create new BP_Theme_Compat object from the $theme array
	if ( is_array( $theme ) )
		$theme = new BP_Theme_Compat( $theme );

	// Bail if $theme isn't a proper object
	if ( ! is_a( $theme, 'BP_Theme_Compat' ) )
		return;

	// Load up BuddyPress
	$bp = buddypress();

	// Only override if the flag is set and not previously registered
	if ( empty( $bp->theme_compat->packages[$theme->id] ) || ( true === $override ) ) {
		$bp->theme_compat->packages[$theme->id] = $theme;
	}
}
/**
 * This fun little function fills up some WordPress globals with dummy data to
 * stop your average page template from complaining about it missing.
 *
 * @since BuddyPress (1.7)
 * @global WP_Query $wp_query
 * @global object $post
 * @param array $args
 */
function bp_theme_compat_reset_post( $args = array() ) {
	global $wp_query, $post;

	// Default arguments
	$defaults = array(
		'ID'                    => -9999,
		'post_status'           => 'publish',
		'post_author'           => 0,
		'post_parent'           => 0,
		'post_type'             => 'page',
		'post_date'             => 0,
		'post_date_gmt'         => 0,
		'post_modified'         => 0,
		'post_modified_gmt'     => 0,
		'post_content'          => '',
		'post_title'            => '',
		'post_category'         => 0,
		'post_excerpt'          => '',
		'post_content_filtered' => '',
		'post_mime_type'        => '',
		'post_password'         => '',
		'post_name'             => '',
		'guid'                  => '',
		'menu_order'            => 0,
		'pinged'                => '',
		'to_ping'               => '',
		'ping_status'           => '',
		'comment_status'        => 'closed',
		'comment_count'         => 0,

		'is_404'          => false,
		'is_page'         => false,
		'is_single'       => false,
		'is_archive'      => false,
		'is_tax'          => false,
	);

	// Switch defaults if post is set
	if ( isset( $wp_query->post ) ) {
		$defaults = array(
			'ID'                    => $wp_query->post->ID,
			'post_status'           => $wp_query->post->post_status,
			'post_author'           => $wp_query->post->post_author,
			'post_parent'           => $wp_query->post->post_parent,
			'post_type'             => $wp_query->post->post_type,
			'post_date'             => $wp_query->post->post_date,
			'post_date_gmt'         => $wp_query->post->post_date_gmt,
			'post_modified'         => $wp_query->post->post_modified,
			'post_modified_gmt'     => $wp_query->post->post_modified_gmt,
			'post_content'          => $wp_query->post->post_content,
			'post_title'            => $wp_query->post->post_title,
			'post_excerpt'          => $wp_query->post->post_excerpt,
			'post_content_filtered' => $wp_query->post->post_content_filtered,
			'post_mime_type'        => $wp_query->post->post_mime_type,
			'post_password'         => $wp_query->post->post_password,
			'post_name'             => $wp_query->post->post_name,
			'guid'                  => $wp_query->post->guid,
			'menu_order'            => $wp_query->post->menu_order,
			'pinged'                => $wp_query->post->pinged,
			'to_ping'               => $wp_query->post->to_ping,
			'ping_status'           => $wp_query->post->ping_status,
			'comment_status'        => $wp_query->post->comment_status,
			'comment_count'         => $wp_query->post->comment_count,

			'is_404'          => false,
			'is_page'         => false,
			'is_single'       => false,
			'is_archive'      => false,
			'is_tax'          => false,
		);
	}
	$dummy = wp_parse_args( $args, $defaults ); //, 'theme_compat_reset_post' );

	// Clear out the post related globals
	unset( $wp_query->posts );
	unset( $wp_query->post  );
	unset( $post            );

	// Setup the dummy post object
	$wp_query->post                        = new stdClass;
	$wp_query->post->ID                    = $dummy['ID'];
	$wp_query->post->post_status           = $dummy['post_status'];
	$wp_query->post->post_author           = $dummy['post_author'];
	$wp_query->post->post_parent           = $dummy['post_parent'];
	$wp_query->post->post_type             = $dummy['post_type'];
	$wp_query->post->post_date             = $dummy['post_date'];
	$wp_query->post->post_date_gmt         = $dummy['post_date_gmt'];
	$wp_query->post->post_modified         = $dummy['post_modified'];
	$wp_query->post->post_modified_gmt     = $dummy['post_modified_gmt'];
	$wp_query->post->post_content          = $dummy['post_content'];
	$wp_query->post->post_title            = $dummy['post_title'];
	$wp_query->post->post_excerpt          = $dummy['post_excerpt'];
	$wp_query->post->post_content_filtered = $dummy['post_content_filtered'];
	$wp_query->post->post_mime_type        = $dummy['post_mime_type'];
	$wp_query->post->post_password         = $dummy['post_password'];
	$wp_query->post->post_name             = $dummy['post_name'];
	$wp_query->post->guid                  = $dummy['guid'];
	$wp_query->post->menu_order            = $dummy['menu_order'];
	$wp_query->post->pinged                = $dummy['pinged'];
	$wp_query->post->to_ping               = $dummy['to_ping'];
	$wp_query->post->ping_status           = $dummy['ping_status'];
	$wp_query->post->comment_status        = $dummy['comment_status'];
	$wp_query->post->comment_count         = $dummy['comment_count'];

	// Set the $post global
	$post = $wp_query->post;

	// Setup the dummy post loop
	$wp_query->posts[0] = $wp_query->post;

	// Prevent comments form from appearing
	$wp_query->post_count = 1;
	$wp_query->is_404     = $dummy['is_404'];
	$wp_query->is_page    = $dummy['is_page'];
	$wp_query->is_single  = $dummy['is_single'];
	$wp_query->is_archive = $dummy['is_archive'];
	$wp_query->is_tax     = $dummy['is_tax'];

	// If we are resetting a post, we are in theme compat
	bp_set_theme_compat_active();
}

/**
 * Reset main query vars and filter 'the_content' to output a BuddyPress
 * template part as needed.
 *
 * @since BuddyPress (1.7)
 *
 * @param string $template
 * @uses bp_is_single_user() To check if page is single user
 * @uses bp_get_single_user_template() To get user template
 * @uses bp_is_single_user_edit() To check if page is single user edit
 * @uses bp_get_single_user_edit_template() To get user edit template
 * @uses bp_is_single_view() To check if page is single view
 * @uses bp_get_single_view_template() To get view template
 * @uses bp_is_forum_edit() To check if page is forum edit
 * @uses bp_get_forum_edit_template() To get forum edit template
 * @uses bp_is_topic_merge() To check if page is topic merge
 * @uses bp_get_topic_merge_template() To get topic merge template
 * @uses bp_is_topic_split() To check if page is topic split
 * @uses bp_get_topic_split_template() To get topic split template
 * @uses bp_is_topic_edit() To check if page is topic edit
 * @uses bp_get_topic_edit_template() To get topic edit template
 * @uses bp_is_reply_edit() To check if page is reply edit
 * @uses bp_get_reply_edit_template() To get reply edit template
 * @uses bp_set_theme_compat_template() To set the global theme compat template
 */
function bp_template_include_theme_compat( $template = '' ) {

	/**
	 * Use this action to execute code that will communicate to BuddyPress's
	 * theme compatibility layer whether or not we're replacing the_content()
	 * with some other template part.
	 */
	do_action( 'bp_template_include_reset_dummy_post_data' );

	// Bail if the template already matches a BuddyPress template
	if ( !empty( buddypress()->theme_compat->found_template ) )
		return $template;

	/**
	 * If we are relying on BuddyPress's built in theme compatibility to load
	 * the proper content, we need to intercept the_content, replace the
	 * output, and display ours instead.
	 *
	 * To do this, we first remove all filters from 'the_content' and hook
	 * our own function into it, which runs a series of checks to determine
	 * the context, and then uses the built in shortcodes to output the
	 * correct results from inside an output buffer.
	 *
	 * Uses bp_get_theme_compat_templates() to provide fall-backs that
	 * should be coded without superfluous mark-up and logic (prev/next
	 * navigation, comments, date/time, etc...)
	 */
	if ( bp_is_theme_compat_active() ) {

		// Remove all filters from the_content
		bp_remove_all_filters( 'the_content' );

		// Add a filter on the_content late, which we will later remove
		if ( ! has_filter( 'the_content', 'bp_replace_the_content' ) ) {
			add_filter( 'the_content', 'bp_replace_the_content' );
		}

		// Add BuddyPress's head action to wp_head
		if ( ! has_action( 'wp_head', 'bp_head' ) ) {
			add_action( 'wp_head', 'bp_head' );
		}

		// Find the appropriate template file
		$template = bp_get_theme_compat_templates();
	}

	return apply_filters( 'bp_template_include_theme_compat', $template );
}

/**
 * Replaces the_content() if the post_type being displayed is one that would
 * normally be handled by BuddyPress, but proper single page templates do not
 * exist in the currently active theme.
 *
 * @since BuddyPress (1.7)
 * @param string $content
 * @return type
 */
function bp_replace_the_content( $content = '' ) {

	if ( ! in_the_loop() )
		return $content;

	$new_content = apply_filters( 'bp_replace_the_content', $content );

	// Juggle the content around and try to prevent unsightly comments
	if ( !empty( $new_content ) && ( $new_content != $content ) ) {

		// Set the content to be the new content
		$content = $new_content;

		// Clean up after ourselves
		unset( $new_content );

		// Reset the $post global
		wp_reset_postdata();
	}

	// Return possibly hi-jacked content
	return $content;
}

/** Filters *******************************************************************/

/**
 * Removes all filters from a WordPress filter, and stashes them in the $bp
 * global in the event they need to be restored later.
 *
 * @since BuddyPress (1.7)
 * @global WP_filter $wp_filter
 * @global array $merged_filters
 * @param string $tag
 * @param int $priority
 * @return bool
 */
function bp_remove_all_filters( $tag, $priority = false ) {
	global $wp_filter, $merged_filters;

	$bp = buddypress();

	// Filters exist
	if ( isset( $wp_filter[$tag] ) ) {

		// Filters exist in this priority
		if ( !empty( $priority ) && isset( $wp_filter[$tag][$priority] ) ) {

			// Store filters in a backup
			$bp->filters->wp_filter[$tag][$priority] = $wp_filter[$tag][$priority];

			// Unset the filters
			unset( $wp_filter[$tag][$priority] );

		// Priority is empty
		} else {

			// Store filters in a backup
			$bp->filters->wp_filter[$tag] = $wp_filter[$tag];

			// Unset the filters
			unset( $wp_filter[$tag] );
		}
	}

	// Check merged filters
	if ( isset( $merged_filters[$tag] ) ) {

		// Store filters in a backup
		$bp->filters->merged_filters[$tag] = $merged_filters[$tag];

		// Unset the filters
		unset( $merged_filters[$tag] );
	}

	return true;
}

/**
 * Restores filters from the $bp global that were removed using
 * bp_remove_all_filters()
 *
 * @since BuddyPress (1.7)
 * @global WP_filter $wp_filter
 * @global array $merged_filters
 * @param string $tag
 * @param int $priority
 * @return bool
 */
function bp_restore_all_filters( $tag, $priority = false ) {
	global $wp_filter, $merged_filters;

	$bp = buddypress();

	// Filters exist
	if ( isset( $bp->filters->wp_filter[$tag] ) ) {

		// Filters exist in this priority
		if ( !empty( $priority ) && isset( $bp->filters->wp_filter[$tag][$priority] ) ) {

			// Store filters in a backup
			$wp_filter[$tag][$priority] = $bp->filters->wp_filter[$tag][$priority];

			// Unset the filters
			unset( $bp->filters->wp_filter[$tag][$priority] );

		// Priority is empty
		} else {

			// Store filters in a backup
			$wp_filter[$tag] = $bp->filters->wp_filter[$tag];

			// Unset the filters
			unset( $bp->filters->wp_filter[$tag] );
		}
	}

	// Check merged filters
	if ( isset( $bp->filters->merged_filters[$tag] ) ) {

		// Store filters in a backup
		$merged_filters[$tag] = $bp->filters->merged_filters[$tag];

		// Unset the filters
		unset( $bp->filters->merged_filters[$tag] );
	}

	return true;
}

/**
 * Force comments_status to 'closed' for BuddyPress post types
 *
 * @since BuddyPress (1.7)
 * @param bool $open True if open, false if closed
 * @param int $post_id ID of the post to check
 * @return bool True if open, false if closed
 */
function bp_comments_open( $open, $post_id = 0 ) {

	$retval = is_buddypress() ? false : $open;

	// Allow override of the override
	return apply_filters( 'bp_force_comment_status', $retval, $open, $post_id );
}
