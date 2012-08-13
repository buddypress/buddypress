<?php

/**
 * BuddyPress URI catcher
 *
 * Functions for parsing the URI and determining which BuddyPress template file
 * to use on-screen.
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Analyzes the URI structure and breaks it down into parts for use in code.
 * BuddyPress can use complete custom friendly URI's without the user having to
 * add new re-write rules. Custom components are able to use their own custom
 * URI structures with very little work.
 *
 * @package BuddyPress Core
 * @since BuddyPress (1.0)
 *
 * The URI's are broken down as follows:
 *   - http:// domain.com / members / andy / [current_component] / [current_action] / [action_variables] / [action_variables] / ...
 *   - OUTSIDE ROOT: http:// domain.com / sites / buddypress / members / andy / [current_component] / [current_action] / [action_variables] / [action_variables] / ...
 *
 *	Example:
 *    - http://domain.com/members/andy/profile/edit/group/5/
 *    - $bp->current_component: string 'xprofile'
 *    - $bp->current_action: string 'edit'
 *    - $bp->action_variables: array ['group', 5]
 *
 */
function bp_core_set_uri_globals() {
	global $bp, $current_blog, $wp_rewrite;

	// Don't catch URIs on non-root blogs unless multiblog mode is on
	if ( !bp_is_root_blog() && !bp_is_multiblog_mode() )
		return false;

	// Define local variables
	$root_profile = $match   = false;
	$key_slugs    = $matches = $uri_chunks = array();

	// Fetch all the WP page names for each component
	if ( empty( $bp->pages ) )
		$bp->pages = bp_core_get_directory_pages();

	// Ajax or not?
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX || strpos( $_SERVER['REQUEST_URI'], 'wp-load.php' ) )
		$path = bp_core_referrer();
	else
		$path = esc_url( $_SERVER['REQUEST_URI'] );

	// Filter the path
	$path = apply_filters( 'bp_uri', $path );

	// Take GET variables off the URL to avoid problems
	$path = strtok( $path, '?' );

	// Fetch current URI and explode each part separated by '/' into an array
	$bp_uri = explode( '/', $path );

	// Loop and remove empties
	foreach ( (array) $bp_uri as $key => $uri_chunk ) {
		if ( empty( $bp_uri[$key] ) ) {
			unset( $bp_uri[$key] );
		}
	}

	// If running off blog other than root, any subdirectory names must be
	// removed from $bp_uri. This includes two cases:
	//
	//    1. when WP is installed in a subdirectory,
	//    2. when BP is running on secondary blog of a subdirectory
	//       multisite installation. Phew!
	if ( is_multisite() && !is_subdomain_install() && ( bp_is_multiblog_mode() || 1 != bp_get_root_blog_id() ) ) {

		// Blow chunks
		$chunks = explode( '/', $current_blog->path );

		// If chunks exist...
		if ( !empty( $chunks ) ) {

			// ...loop through them...
			foreach( $chunks as $key => $chunk ) {
				$bkey = array_search( $chunk, $bp_uri );

				// ...and unset offending keys
				if ( false !== $bkey ) {
					unset( $bp_uri[$bkey] );
				}

				$bp_uri = array_values( $bp_uri );
			}
		}
	}

	// Get site path items
	$paths = explode( '/', bp_core_get_site_path() );

	// Take empties off the end of path
	if ( empty( $paths[count( $paths ) - 1] ) )
		array_pop( $paths );

	// Take empties off the start of path
	if ( empty( $paths[0] ) )
		array_shift( $paths );

	// Reset indexes
	$bp_uri = array_values( $bp_uri );
	$paths  = array_values( $paths );

	// Unset URI indices if they intersect with the paths
	foreach ( (array) $bp_uri as $key => $uri_chunk ) {
		if ( isset( $paths[$key] ) && $uri_chunk == $paths[$key] ) {
			unset( $bp_uri[$key] );
		}
	}

	// Reset the keys by merging with an empty array
	$bp_uri = array_merge( array(), $bp_uri );

	// If a component is set to the front page, force its name into $bp_uri
	// so that $current_component is populated (unless a specific WP post is being requested
	// via a URL parameter, usually signifying Preview mode)
	if ( 'page' == get_option( 'show_on_front' ) && get_option( 'page_on_front' ) && empty( $bp_uri ) && empty( $_GET['p'] ) && empty( $_GET['page_id'] ) ) {
		$post = get_post( get_option( 'page_on_front' ) );
		if ( !empty( $post ) ) {
			$bp_uri[0] = $post->post_name;
		}
	}

	// Keep the unfiltered URI safe
	$bp->unfiltered_uri = $bp_uri;

	// Get slugs of pages into array
	foreach ( (array) $bp->pages as $page_key => $bp_page )
		$key_slugs[$page_key] = trailingslashit( '/' . $bp_page->slug );

	// Bail if keyslugs are empty, as BP is not setup correct
	if ( empty( $key_slugs ) )
		return;

	// Loop through page slugs and look for exact match to path
	foreach ( $key_slugs as $key => $slug ) {
		if ( $slug == $path ) {
			$match      = $bp->pages->{$key};
			$match->key = $key;
			$matches[]  = 1;
			break;
		}
	}

	// No exact match, so look for partials
	if ( empty( $match ) ) {

		// Loop through each page in the $bp->pages global
		foreach ( (array) $bp->pages as $page_key => $bp_page ) {

			// Look for a match (check members first)
			if ( in_array( $bp_page->name, (array) $bp_uri ) ) {

				// Match found, now match the slug to make sure.
				$uri_chunks = explode( '/', $bp_page->slug );

				// Loop through uri_chunks
				foreach ( (array) $uri_chunks as $key => $uri_chunk ) {

					// Make sure chunk is in the correct position
					if ( !empty( $bp_uri[$key] ) && ( $bp_uri[$key] == $uri_chunk ) ) {
						$matches[] = 1;

					// No match
					} else {
						$matches[] = 0;
					}
				}

				// Have a match
				if ( !in_array( 0, (array) $matches ) ) {
					$match      = $bp_page;
					$match->key = $page_key;
					break;
				};

				// Unset matches
				unset( $matches );
			}

			// Unset uri chunks
			unset( $uri_chunks );
		}
	}

	// URLs with BP_ENABLE_ROOT_PROFILES enabled won't be caught above
	if ( empty( $matches ) && bp_core_enable_root_profiles() ) {

		// Switch field based on compat
		$field = bp_is_username_compatibility_mode() ? 'login' : 'slug';

		// Make sure there's a user corresponding to $bp_uri[0]
		if ( !empty( $bp->pages->members ) && !empty( $bp_uri[0] ) && $root_profile = get_user_by( $field, $bp_uri[0] ) ) {

			// Force BP to recognize that this is a members page
			$matches[]  = 1;
			$match      = $bp->pages->members;
			$match->key = 'members';

			// Without the 'members' URL chunk, WordPress won't know which page to load
			// This filter intercepts the WP query and tells it to load the members page
			add_filter( 'request', create_function( '$query_args', '$query_args["pagename"] = "' . $match->name . '"; return $query_args;' ) );
		}
	}

	// Search doesn't have an associated page, so we check for it separately
	if ( !empty( $bp_uri[0] ) && ( bp_get_search_slug() == $bp_uri[0] ) ) {
		$matches[]   = 1;
		$match       = new stdClass;
		$match->key  = 'search';
		$match->slug = bp_get_search_slug();
	}

	// This is not a BuddyPress page, so just return.
	if ( empty( $matches ) )
		return false;

	$wp_rewrite->use_verbose_page_rules = false;

	// Find the offset. With $root_profile set, we fudge the offset down so later parsing works
	$slug       = !empty ( $match ) ? explode( '/', $match->slug ) : '';
	$uri_offset = empty( $root_profile ) ? 0 : -1;

	// Rejig the offset
	if ( !empty( $slug ) && ( 1 < count( $slug ) ) ) {
		array_pop( $slug );
		$uri_offset = count( $slug );
	}

	// Global the unfiltered offset to use in bp_core_load_template().
	// To avoid PHP warnings in bp_core_load_template(), it must always be >= 0
	$bp->unfiltered_uri_offset = $uri_offset >= 0 ? $uri_offset : 0;

	// We have an exact match
	if ( isset( $match->key ) ) {

		// Set current component to matched key
		$bp->current_component = $match->key;

		// If members component, do more work to find the actual component
		if ( 'members' == $match->key ) {

			// Viewing a specific user
			if ( !empty( $bp_uri[$uri_offset + 1] ) ) {

				// Switch the displayed_user based on compatbility mode
				if ( bp_is_username_compatibility_mode() ) {
					$bp->displayed_user->id = (int) bp_core_get_userid( urldecode( $bp_uri[$uri_offset + 1] ) );
				} else {
					$bp->displayed_user->id = (int) bp_core_get_userid_from_nicename( urldecode( $bp_uri[$uri_offset + 1] ) );
				}

				if ( !bp_displayed_user_id() ) {

					// Prevent components from loading their templates
					$bp->current_component = '';

					bp_do_404();
					return;
				}

				// If the displayed user is marked as a spammer, 404 (unless logged-
				// in user is a super admin)
				if ( bp_displayed_user_id() && bp_is_user_spammer( bp_displayed_user_id() ) ) {
					if ( bp_current_user_can( 'bp_moderate' ) ) {
						bp_core_add_message( __( 'This user has been marked as a spammer. Only site admins can view this profile.', 'buddypress' ), 'warning' );
					} else {
						bp_do_404();
						return;
					}
				}

				// Bump the offset
				if ( isset( $bp_uri[$uri_offset + 2] ) ) {
					$bp_uri                = array_merge( array(), array_slice( $bp_uri, $uri_offset + 2 ) );
					$bp->current_component = $bp_uri[0];

				// No component, so default will be picked later
				} else {
					$bp_uri                = array_merge( array(), array_slice( $bp_uri, $uri_offset + 2 ) );
					$bp->current_component = '';
				}

				// Reset the offset
				$uri_offset = 0;
			}
		}
	}

	// Set the current action
	$bp->current_action = isset( $bp_uri[$uri_offset + 1] ) ? $bp_uri[$uri_offset + 1] : '';

	// Slice the rest of the $bp_uri array and reset offset
	$bp_uri      = array_slice( $bp_uri, $uri_offset + 2 );
	$uri_offset  = 0;

	// Set the entire URI as the action variables, we will unset the current_component and action in a second
	$bp->action_variables = $bp_uri;

	// Reset the keys by merging with an empty array
	$bp->action_variables = array_merge( array(), $bp->action_variables );
}

/**
 * Are root profiles enabled and allowed
 *
 * @since BuddyPress (1.6)
 * @return bool True if yes, false if no
 */
function bp_core_enable_root_profiles() {

	$retval = false;

	if ( defined( 'BP_ENABLE_ROOT_PROFILES' ) && ( true == BP_ENABLE_ROOT_PROFILES ) )
		$retval = true;

	return apply_filters( 'bp_core_enable_root_profiles', $retval );
}

/**
 * bp_core_load_template()
 *
 * Load a specific template file with fallback support.
 *
 * Example:
 *   bp_core_load_template( 'members/index' );
 * Loads:
 *   wp-content/themes/[activated_theme]/members/index.php
 *
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @return false|int The user ID of the matched user, or false.
 */
function bp_core_load_template( $templates ) {
	global $post, $bp, $wp_query, $wpdb;

	// Determine if the root object WP page exists for this request
	// note: get_page_by_path() breaks non-root pages
	if ( !empty( $bp->unfiltered_uri_offset ) ) {
		if ( !$page_exists = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s", $bp->unfiltered_uri[$bp->unfiltered_uri_offset] ) ) ) {
			return false;
		}
	}

	// Set the root object as the current wp_query-ied item
	$object_id = 0;
	foreach ( (array) $bp->pages as $page ) {
		if ( $page->name == $bp->unfiltered_uri[$bp->unfiltered_uri_offset] ) {
			$object_id = $page->id;
		}
	}

	// Make the queried/post object an actual valid page
	if ( !empty( $object_id ) ) {
		$wp_query->queried_object    = &get_post( $object_id );
		$wp_query->queried_object_id = $object_id;
		$post                        = $wp_query->queried_object;
	}

	// Define local variables
	$located_template   = false;
	$filtered_templates = array();

	// Fetch each template and add the php suffix
	foreach ( (array) $templates as $template )
		$filtered_templates[] = $template . '.php';

	// Filter the template locations so that plugins can alter where they are located
	$located_template = apply_filters( 'bp_located_template', locate_template( (array) $filtered_templates, false ), $filtered_templates );
	if ( !empty( $located_template ) ) {

		// Template was located, lets set this as a valid page and not a 404.
		status_header( 200 );
		$wp_query->is_page = $wp_query->is_singular = true;
		$wp_query->is_404  = false;

		do_action( 'bp_core_pre_load_template', $located_template );

		load_template( apply_filters( 'bp_load_template', $located_template ) );

		do_action( 'bp_core_post_load_template', $located_template );
	}

	// Kill any other output after this.
	die;
}

/**
 * bp_core_catch_profile_uri()
 *
 * If the extended profiles component is not installed we still need
 * to catch the /profile URI's and display whatever we have installed.
 *
 */
function bp_core_catch_profile_uri() {
	if ( !bp_is_active( 'xprofile' ) ) {
		bp_core_load_template( apply_filters( 'bp_core_template_display_profile', 'members/single/home' ) );
	}
}

/**
 * Catches invalid access to BuddyPress pages and redirects them accordingly.
 *
 * @package BuddyPress Core
 * @since BuddyPress (1.5)
 */
function bp_core_catch_no_access() {
	global $bp, $wp_query;

	// If coming from bp_core_redirect() and $bp_no_status_set is true,
	// we are redirecting to an accessible page so skip this check.
	if ( !empty( $bp->no_status_set ) )
		return false;

	if ( !isset( $wp_query->queried_object ) && !bp_is_blog_page() ) {
		bp_do_404();
	}
}
add_action( 'bp_template_redirect', 'bp_core_catch_no_access', 1 );

/**
 * Redirects a user to login for BP pages that require access control and adds an error message (if
 * one is provided).
 * If authenticated, redirects user back to requested content by default.
 *
 * @package BuddyPress Core
 * @since BuddyPress (1.5)
 */
function bp_core_no_access( $args = '' ) {

 	// Build the redirect URL
 	$redirect_url  = is_ssl() ? 'https://' : 'http://';
 	$redirect_url .= $_SERVER['HTTP_HOST'];
 	$redirect_url .= $_SERVER['REQUEST_URI'];

	$defaults = array(
		'mode'     => '1',                  // 1 = $root, 2 = wp-login.php
		'redirect' => $redirect_url,        // the URL you get redirected to when a user successfully logs in
		'root'     => bp_get_root_domain(),	// the landing page you get redirected to when a user doesn't have access
		'message'  => __( 'You must log in to access the page you requested.', 'buddypress' )
	);

	$r = wp_parse_args( $args, $defaults );
	$r = apply_filters( 'bp_core_no_access', $r );
	extract( $r, EXTR_SKIP );

	/**
	 * @ignore Ignore these filters and use 'bp_core_no_access' above
	 */
	$mode		= apply_filters( 'bp_no_access_mode',     $mode,     $root,     $redirect, $message );
	$redirect	= apply_filters( 'bp_no_access_redirect', $redirect, $root,     $message,  $mode    );
	$root		= apply_filters( 'bp_no_access_root',     $root,     $redirect, $message,  $mode    );
	$message	= apply_filters( 'bp_no_access_message',  $message,  $root,     $redirect, $mode    );
	$root       = trailingslashit( $root );

	switch ( $mode ) {

		// Option to redirect to wp-login.php
		// Error message is displayed with bp_core_no_access_wp_login_error()
		case 2 :
			if ( !empty( $redirect ) ) {
				bp_core_redirect( add_query_arg( array( 'action' => 'bpnoaccess' ), wp_login_url( $redirect ) ) );
			} else {
				bp_core_redirect( $root );
			}

			break;

		// Redirect to root with "redirect_to" parameter
		// Error message is displayed with bp_core_add_message()
		case 1 :
		default :

			$url = $root;
			if ( !empty( $redirect ) )
				$url = add_query_arg( 'redirect_to', urlencode( $redirect ), $root );

			if ( !empty( $message ) ) {
				bp_core_add_message( $message, 'error' );
			}

			bp_core_redirect( $url );

			break;
	}
}

/**
 * Adds an error message to wp-login.php.
 * Hooks into the "bpnoaccess" action defined in bp_core_no_access().
 *
 * @package BuddyPress Core
 * @global $error
 * @since BuddyPress (1.5)
 */
function bp_core_no_access_wp_login_error() {
	global $error;

	$error = apply_filters( 'bp_wp_login_error', __( 'You must log in to access the page you requested.', 'buddypress' ), $_REQUEST['redirect_to'] );

	// shake shake shake!
	add_action( 'login_head', 'wp_shake_js', 12 );
}
add_action( 'login_form_bpnoaccess', 'bp_core_no_access_wp_login_error' );

/**
 * Canonicalizes BuddyPress URLs
 *
 * This function ensures that requests for BuddyPress content are always redirected to their
 * canonical versions. Canonical versions are always trailingslashed, and are typically the most
 * general possible versions of the URL - eg, example.com/groups/mygroup/ instead of
 * example.com/groups/mygroup/home/
 *
 * @since 1.6
 * @see BP_Members_Component::setup_globals() where $bp->canonical_stack['base_url'] and
 *   ['component'] may be set
 * @see bp_core_new_nav_item() where $bp->canonical_stack['action'] may be set
 * @uses bp_get_canonical_url()
 * @uses bp_get_requested_url()
 */
function bp_redirect_canonical() {
	global $bp;

	if ( !bp_is_blog_page() && apply_filters( 'bp_do_redirect_canonical', true ) ) {
		// If this is a POST request, don't do a canonical redirect.
		// This is for backward compatibility with plugins that submit form requests to
		// non-canonical URLs. Plugin authors should do their best to use canonical URLs in
		// their form actions.
		if ( !empty( $_POST ) ) {
			return;
		}

		// build the URL in the address bar
		$requested_url  = bp_get_requested_url();

		// Stash query args
		$url_stack      = explode( '?', $requested_url );
		$req_url_clean  = $url_stack[0];
		$query_args     = isset( $url_stack[1] ) ? $url_stack[1] : '';

		$canonical_url  = bp_get_canonical_url();

		// Only redirect if we've assembled a URL different from the request
		if ( $canonical_url !== $req_url_clean ) {

			// Template messages have been deleted from the cookie by this point, so
			// they must be readded before redirecting
			if ( isset( $bp->template_message ) ) {
				$message      = stripslashes( $bp->template_message );
				$message_type = isset( $bp->template_message_type ) ? $bp->template_message_type : 'success';

				bp_core_add_message( $message, $message_type );
			}

			if ( !empty( $query_args ) ) {
				$canonical_url .= '?' . $query_args;
			}

			bp_core_redirect( $canonical_url, 301 );
		}
	}
}

/**
 * Output rel=canonical header tag for BuddyPress content
 *
 * @since 1.6
 */
function bp_rel_canonical() {
	$canonical_url = bp_get_canonical_url();

	// Output rel=canonical tag
	echo "<link rel='canonical' href='" . esc_attr( $canonical_url ) . "' />\n";
}

/**
 * Returns the canonical URL of the current page
 *
 * @since BuddyPress (1.6)
 * @uses apply_filters() Filter bp_get_canonical_url to modify return value
 * @param array $args
 * @return string
 */
function bp_get_canonical_url( $args = array() ) {
	global $bp;

	// For non-BP content, return the requested url, and let WP do the work
	if ( bp_is_blog_page() ) {
		return bp_get_requested_url();
	}

	$defaults = array(
		'include_query_args' => false // Include URL arguments, eg ?foo=bar&foo2=bar2
	);
	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	if ( empty( $bp->canonical_stack['canonical_url'] ) ) {
		// Build the URL in the address bar
		$requested_url  = bp_get_requested_url();

		// Stash query args
		$url_stack      = explode( '?', $requested_url );

		// Build the canonical URL out of the redirect stack
		if ( isset( $bp->canonical_stack['base_url'] ) )
			$url_stack[0] = $bp->canonical_stack['base_url'];

		if ( isset( $bp->canonical_stack['component'] ) )
			$url_stack[0] = trailingslashit( $url_stack[0] . $bp->canonical_stack['component'] );

		if ( isset( $bp->canonical_stack['action'] ) )
			$url_stack[0] = trailingslashit( $url_stack[0] . $bp->canonical_stack['action'] );

		if ( !empty( $bp->canonical_stack['action_variables'] ) ) {
			foreach( (array) $bp->canonical_stack['action_variables'] as $av ) {
				$url_stack[0] = trailingslashit( $url_stack[0] . $av );
			}
		}

		// Add trailing slash
		$url_stack[0] = trailingslashit( $url_stack[0] );

		// Stash in the $bp global
		$bp->canonical_stack['canonical_url'] = implode( '?', $url_stack );
	}

	$canonical_url = $bp->canonical_stack['canonical_url'];

	if ( !$include_query_args ) {
		$canonical_url = array_pop( array_reverse( explode( '?', $canonical_url ) ) );
	}

	return apply_filters( 'bp_get_canonical_url', $canonical_url, $args );
}

/**
 * Returns the URL as requested on the current page load by the user agent
 *
 * @since BuddyPress (1.6)
 * @return string
 */
function bp_get_requested_url() {
	global $bp;

	if ( empty( $bp->canonical_stack['requested_url'] ) ) {
		$bp->canonical_stack['requested_url']  = is_ssl() ? 'https://' : 'http://';
		$bp->canonical_stack['requested_url'] .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	return $bp->canonical_stack['requested_url'];
}

/**
 * Remove WordPress's really awesome canonical redirect if we are trying to load
 * BuddyPress specific content. Avoids issues with WordPress thinking that a
 * BuddyPress URL might actually be a blog post or page.
 *
 * This function should be considered temporary, and may be removed without
 * notice in future versions of BuddyPress.
 *
 * @since BuddyPress (1.6)
 * @uses bp_is_blog_page()
 */
function _bp_maybe_remove_redirect_canonical() {
	if ( ! bp_is_blog_page() )
		remove_action( 'template_redirect', 'redirect_canonical' );
}
add_action( 'bp_init', '_bp_maybe_remove_redirect_canonical' );

/**
 * Rehook maybe_redirect_404() to run later than the default
 *
 * WordPress's maybe_redirect_404() allows admins on a multisite installation
 * to define 'NOBLOGREDIRECT', a URL to which 404 requests will be redirected.
 * maybe_redirect_404() is hooked to template_redirect at priority 10, which
 * creates a race condition with bp_template_redirect(), our piggyback hook.
 * Due to a legacy bug in BuddyPress, internal BP content (such as members and
 * groups) is marked 404 in $wp_query until bp_core_load_template(), when BP
 * manually overrides the automatic 404. However, the race condition with
 * maybe_redirect_404() means that this manual un-404-ing doesn't happen in
 * time, with the results that maybe_redirect_404() thinks that the page is
 * a legitimate 404, and redirects incorrectly to NOBLOGREDIRECT.
 *
 * By switching maybe_redirect_404() to catch at a higher priority, we avoid
 * the race condition. If bp_core_load_template() runs, it dies before reaching
 * maybe_redirect_404(). If bp_core_load_template() does not run, it means that
 * the 404 is legitimate, and maybe_redirect_404() can proceed as expected.
 *
 * This function will be removed in a later version of BuddyPress. Plugins
 * (and plugin authors!) should ignore it.
 *
 * @since 1.6.1
 *
 * @link http://buddypress.trac.wordpress.org/ticket/4329
 * @link http://buddypress.trac.wordpress.org/ticket/4415
 */
function _bp_rehook_maybe_redirect_404() {
	if ( defined( 'NOBLOGREDIRECT' ) ) {
		remove_action( 'template_redirect', 'maybe_redirect_404' );
		add_action( 'template_redirect', 'maybe_redirect_404', 100 );
	}
}
add_action( 'template_redirect', '_bp_rehook_maybe_redirect_404', 1 );

/**
 * Remove WordPress's rel=canonical HTML tag if we are trying to load BuddyPress
 * specific content.
 *
 * This function should be considered temporary, and may be removed without
 * notice in future versions of BuddyPress.
 *
 * @since 1.6
 */
function _bp_maybe_remove_rel_canonical() {
	if ( ! bp_is_blog_page() && ! is_404() ) {
		remove_action( 'wp_head', 'rel_canonical' );
		add_action( 'bp_head', 'bp_rel_canonical' );
	}
}
add_action( 'wp_head', '_bp_maybe_remove_rel_canonical', 8 );
?>