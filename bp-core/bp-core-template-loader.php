<?php

/**
 * BuddyPress Template Functions
 *
 * This file contains functions necessary to mirror the WordPress core template
 * loading process. Many of those functions are not filterable, and even then
 * would not be robust enough to predict where BuddyPress templates might exist.
 *
 * @package BuddyPress
 * @subpackage TemplateFunctions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Adds BuddyPress theme support to any active WordPress theme
 *
 * @since BuddyPress (1.7)
 *
 * @param string $slug
 * @param string $name Optional. Default null
 * @uses bp_locate_template()
 * @uses load_template()
 * @uses get_template_part()
 */
function bp_get_template_part( $slug, $name = null ) {

	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	// Allow template parst to be filtered
	$templates = apply_filters( 'bp_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return bp_locate_template( $templates, true, false );
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
 * inherit from a parent theme can just overload one file. If the template is
 * not found in either of those, it looks in the theme-compat folder last.
 *
 * @since BuddyPress (1.7)
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true.
 *                            Has no effect if $load is false.
 * @return string The template filename if one is located.
 */
function bp_locate_template( $template_names, $load = false, $require_once = true ) {

	// No file found yet
	$located        = false;
	$child_theme    = get_stylesheet_directory();
	$parent_theme   = get_template_directory();
	$fallback_theme = bp_get_theme_compat_dir();

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) )
			continue;

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// Check child theme first
		if ( file_exists( trailingslashit( $child_theme ) . $template_name ) ) {
			$located = trailingslashit( $child_theme ) . $template_name;
			break;

		// Check parent theme next
		} elseif ( file_exists( trailingslashit( $parent_theme ) . $template_name ) ) {
			$located = trailingslashit( $parent_theme ) . $template_name;
			break;

		// Check theme compatibility last
		} elseif ( file_exists( trailingslashit( $fallback_theme ) . $template_name ) ) {
			$located = trailingslashit( $fallback_theme ) . $template_name;
			break;
		}
	}

	if ( ( true == $load ) && !empty( $located ) )
		load_template( $located, $require_once );

	return $located;
}

/**
 * Get a template part in an output buffer, and return it
 *
 * @since BuddyPress (1.7)
 *
 * @param string $slug
 * @param string $name
 * @return string
 */
function bp_buffer_template_part( $slug, $name = null, $echo = true ) {
	ob_start();

	// Remove 'bp_replace_the_content' filter to prevent infinite loops
	remove_filter( 'the_content', 'bp_replace_the_content' );

	bp_get_template_part( $slug, $name );

	// Remove 'bp_replace_the_content' filter to prevent infinite loops
	add_filter( 'the_content', 'bp_replace_the_content' );

	// Get the output buffer contents
	$output = ob_get_contents();

	// Flush the output buffer
	ob_end_clean();

	// Echo or return the output buffer contents
	if ( true === $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Retrieve path to a template
 *
 * Used to quickly retrieve the path of a template without including the file
 * extension. It will also check the parent theme and theme-compat theme with
 * the use of {@link bp_locate_template()}. Allows for more generic template
 * locations without the use of the other get_*_template() functions.
 *
 * @since BuddyPress (1.7)
 *
 * @param string $type Filename without extension.
 * @param array $templates An optional list of template candidates
 * @uses bp_set_theme_compat_templates()
 * @uses bp_locate_template()
 * @uses bp_set_theme_compat_template()
 * @return string Full path to file.
 */
function bp_get_query_template( $type, $templates = array() ) {
	$type = preg_replace( '|[^a-z0-9-]+|', '', $type );

	if ( empty( $templates ) )
		$templates = array( "{$type}.php" );

	// Filter possible templates, try to match one, and set any BuddyPress theme
	// compat properties so they can be cross-checked later.
	$templates = apply_filters( "bp_get_{$type}_template", $templates );
	$templates = bp_set_theme_compat_templates( $templates );
	$template  = bp_locate_template( $templates );
	$template  = bp_set_theme_compat_template( $template );

	return apply_filters( "bp_{$type}_template", $template );
}

/**
 * Get the possible subdirectories to check for templates in
 *
 * @since BuddyPress (1.7)
 * @param array $templates Templates we are looking for
 * @return array Possible subfolders to look in
 */
function bp_get_template_locations( $templates = array() ) {
	$locations = array(
		'buddypress',
		'community',
		''
	);
	return apply_filters( 'bp_get_template_locations', $locations, $templates );
}

/**
 * Add template locations to template files being searched for
 *
 * @since BuddyPress (1.7)
 *
 * @param array $templates
 * @return array() 
 */
function bp_add_template_locations( $templates = array() ) {
	$retval = array();

	// Get alternate locations
	$locations = bp_get_template_locations( $templates );

	// Loop through locations and templates and combine
	foreach ( $locations as $location )
		foreach ( $templates as $template )
			$retval[] = trailingslashit( $location ) . $template;

	return apply_filters( 'bp_add_template_locations', $retval, $templates );
}

/**
 * Add checks for BuddyPress conditions to parse_query action
 *
 * @since BuddyPress (1.7)
 *
 * @param WP_Query $posts_query
 */
function bp_parse_query( $posts_query ) {

	// Bail if $posts_query is not the main loop
	if ( ! $posts_query->is_main_query() )
		return;

	// Bail if filters are suppressed on this query
	if ( true == $posts_query->get( 'suppress_filters' ) )
		return;

	// Bail if in admin
	if ( is_admin() )
		return;

	// Allow BuddyPress components to parse the main query
	do_action_ref_array( 'bp_parse_query', array( &$posts_query ) );
}

/**
 * Possibly intercept the template being loaded
 *
 * Listens to the 'template_include' filter and waits for any BuddyPress specific
 * template condition to be met. If one is met and the template file exists,
 * it will be used; otherwise 
 *
 * Note that the _edit() checks are ahead of their counterparts, to prevent them
 * from being stomped on accident.
 *
 * @since BuddyPress (1.7)
 *
 * @param string $template
 *
 * @return string The path to the template file that is being used
 */
function bp_template_include_theme_supports( $template = '' ) {

	// Look for root BuddyPress template files in parent/child themes
	$new_template = apply_filters( 'bp_get_root_template', false, $template );

	// BuddyPress template file exists
	if ( !empty( $new_template ) ) {

		// Override the WordPress template with a BuddyPress one
		$template = $new_template;

		// @see: bp_template_include_theme_compat()
		buddypress()->theme_compat->found_template = true;
	}

	return apply_filters( 'bp_template_include_theme_supports', $template );
}

/**
 * Attempt to load a custom BuddyPress functions file, similar to each themes
 * functions.php file.
 *
 * @since BuddyPress (1.7)
 *
 * @global string $pagenow
 * @uses bp_locate_template()
 */
function bp_load_theme_functions() {
	global $pagenow;

	// Do not include on BuddyPress deactivation
	if ( bp_is_deactivation() )
		return;

	// Only include if not installing or if activating via wp-activate.php
	if ( ! defined( 'WP_INSTALLING' ) || 'wp-activate.php' === $pagenow ) {
		bp_locate_template( 'buddypress-functions.php', true );
	}
}

/**
 * Get the templates to use as the endpoint for BuddyPress template parts
 *
 * @since BuddyPress (1.7)
 *
 * @uses apply_filters()
 * @return array Of possible root level wrapper template files
 */
function bp_get_theme_compat_templates() {
	$templates = array(
		'plugin-buddypress.php',
		'buddypress.php',
		'community.php',
		'generic.php',
		'page.php',
		'single.php',
		'index.php'
	);
	return bp_get_query_template( 'buddypress', $templates );
}
