<?php
/**
 * BuddyPress URI catcher.
 *
 * Functions for parsing the URI and determining which BuddyPress template file
 * to use on-screen.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sets BuddyPress globals for Ajax requests using the BP Rewrites API.
 *
 * @since 12.0.0
 */
function bp_core_set_ajax_uri_globals() {
	if ( ! wp_doing_ajax() || 'rewrites' !== bp_core_get_query_parser() ) {
		return;
	}

	$action = '';
	if ( isset( $_REQUEST['action'] ) ) {
		$action = wp_unslash( sanitize_text_field( $_REQUEST['action'] ) );
	}

	// Only set BuddyPress URI globals for registered Ajax actions.
	if ( ! bp_ajax_action_is_registered( $action ) ) {
		return;
	}

	if ( 'heartbeat' === $action && empty( $_REQUEST['data']['bp_heartbeat'] ) ) {
		return;
	}

	bp_reset_query( bp_get_referer_path(), $GLOBALS['wp_query'] );
}

/**
 * Are root profiles enabled and allowed?
 *
 * @since 1.6.0
 *
 * @return bool
 */
function bp_core_enable_root_profiles() {

	$retval = false;

	if ( defined( 'BP_ENABLE_ROOT_PROFILES' ) && ( true === BP_ENABLE_ROOT_PROFILES ) ) {
		$retval = true;
	}

	/**
	 * Filters whether or not root profiles are enabled and allowed.
	 *
	 * @since 1.6.0
	 *
	 * @param bool $retval Whether or not root profiles are available.
	 */
	return apply_filters( 'bp_core_enable_root_profiles', $retval );
}

/**
 * Load a specific template file with fallback support.
 *
 * Example:
 *   bp_core_load_template( 'members/index' );
 * Loads:
 *   wp-content/themes/[activated_theme]/members/index.php
 *
 * @since 1.0.0
 * @since 14.0.0 Uses `locate_block_template()` to support BuddyPress Block only Themes.
 *
 * @param array $templates Array of templates to attempt to load.
 */
function bp_core_load_template( $templates ) {
	global $wp_query;

	// Reset the post.
	bp_theme_compat_reset_post(
		array(
			'ID'          => 0,
			'is_404'      => true,
			'post_status' => 'publish',
		)
	);

	// Set theme compat to false since the reset post function automatically sets
	// theme compat to true.
	bp_set_theme_compat_active( false );

	// Fetch each template and add the php suffix.
	$filtered_templates = array();
	foreach ( (array) $templates as $template ) {
		$filtered_templates[] = $template . '.php';
	}

	// Only perform template lookup for bp-default themes.
	if ( ! bp_use_theme_compat_with_current_theme() ) {
		if ( bp_theme_compat_is_block_theme() ) {
			// Prevent BuddyPress components from using the BP Theme Compat feature.
			remove_all_actions( 'bp_setup_theme_compat' );

			$block_templates = array();
			foreach ( (array) $templates as $template ) {
				$block_templates[] = 'buddypress/' . $template;
			}

			$template_type     = 'buddypress';
			$block_templates[] = $template_type;

			$template = locate_block_template( '', $template_type, $block_templates );

		} else {
			$template = locate_template( (array) $filtered_templates, false );
		}

		// Theme compat doesn't require a template lookup.
	} else {
		$template = '';
	}

	/**
	 * Filters the template locations.
	 *
	 * Allows plugins to alter where the template files are located.
	 *
	 * @since 1.1.0
	 *
	 * @param string $template           Located template path.
	 * @param array  $filtered_templates Array of templates to attempt to load.
	 */
	$located_template = apply_filters( 'bp_located_template', $template, $filtered_templates );

	/*
	 * If current page is an embed, wipe out bp-default template.
	 *
	 * Wiping out the bp-default template allows WordPress to use their special
	 * embed template, which is what we want.
	 */
	if ( is_embed() ) {
		$located_template = '';
	}

	if ( ! empty( $located_template ) ) {
		// Template was located, lets set this as a valid page and not a 404.
		status_header( 200 );
		$wp_query->is_page     = true;
		$wp_query->is_singular = true;
		$wp_query->is_404      = false;

		// Check if a BuddyPress component's direcory is set as homepage.
		$wp_query->is_home = bp_is_directory_homepage( bp_current_component() );

		/**
		 * Fires before the loading of a located template file.
		 *
		 * @since 1.6.0
		 *
		 * @param string $located_template Template found to be loaded.
		 */
		do_action( 'bp_core_pre_load_template', $located_template );

		/**
		 * Filters the selected template right before loading.
		 *
		 * @since 1.1.0
		 *
		 * @param string $located_template Template found to be loaded.
		 */
		load_template( apply_filters( 'bp_load_template', $located_template ) );

		/**
		 * Fires after the loading of a located template file.
		 *
		 * @since 1.6.0
		 *
		 * @param string $located_template Template found that was loaded.
		 */
		do_action( 'bp_core_post_load_template', $located_template );

		// Kill any other output after this.
		exit();

		// No template found, so setup theme compatibility.
		// @todo Some other 404 handling if theme compat doesn't kick in.
	} else {

		// We know where we are, so reset important $wp_query bits here early.
		// The rest will be done by bp_theme_compat_reset_post() later.
		if ( is_buddypress() ) {
			status_header( 200 );
			$wp_query->is_page     = true;
			$wp_query->is_singular = true;
			$wp_query->is_404      = false;

			// Check if a BuddyPress component's direcory is set as homepage.
			if ( bp_is_directory_homepage( bp_current_component() ) ) {
				$wp_query->home          = true;
				$wp_query->is_front_page = true;
			}
		}

		/**
		 * Fires if there are no found templates to load and theme compat is needed.
		 *
		 * @since 1.7.0
		 */
		do_action( 'bp_setup_theme_compat' );
	}
}

/**
 * Redirect away from /profile URIs if XProfile is not enabled.
 *
 * @since 1.0.0
 */
function bp_core_catch_profile_uri() {
	if ( ! bp_is_active( 'xprofile' ) ) {

		$templates = array(
			/**
			 * Filters the path to redirect users to if XProfile is not enabled.
			 *
			 * @since 1.0.0
			 *
			 * @param string $value Path to redirect users to.
			 */
			apply_filters( 'bp_core_template_display_profile', 'members/single/home' ),
			'members/single/index',
		);

		bp_core_load_template( $templates );
	}
}

/**
 * Members user shortlink redirector.
 *
 * Redirects x.com/members/me/* to x.com/members/{LOGGED_IN_USER_SLUG}/*
 *
 * @since 2.6.0
 *
 * @param string $member_slug The current member slug.
 * @return string $member_slug The current member slug.
 */
function bp_core_members_shortlink_redirector( $member_slug ) {

	/**
	 * Shortlink slug to redirect to logged-in user.
	 *
	 * The x.com/members/me/* url will redirect to x.com/members/{LOGGED_IN_USER_SLUG}/*
	 *
	 * @since 2.6.0
	 *
	 * @param string $slug Defaults to 'me'.
	 */
	$me_slug = apply_filters( 'bp_core_members_shortlink_slug', 'me' );

	// Check if we're on our special shortlink slug. If not, bail.
	if ( $me_slug !== $member_slug ) {
		return $member_slug;
	}

	// If logged out, redirect user to login.
	if ( false === is_user_logged_in() ) {
		// Add our login redirector hook.
		add_action( 'template_redirect', 'bp_core_no_access', 0 );

		return $member_slug;
	}

	$user = wp_get_current_user();

	return bp_members_get_user_slug( $user->ID );
}
add_filter( 'bp_core_set_uri_globals_member_slug', 'bp_core_members_shortlink_redirector' );

/**
 * Catch unauthorized access to certain BuddyPress pages and redirect accordingly.
 *
 * @since 1.5.0
 */
function bp_core_catch_no_access() {
	global $wp_query;

	$bp = buddypress();

	// If coming from bp_core_redirect() and $bp_no_status_set is true,
	// we are redirecting to an accessible page so skip this check.
	if ( ! empty( $bp->no_status_set ) ) {
		return false;
	}

	if ( ! isset( $wp_query->queried_object ) && ! bp_is_blog_page() ) {
		bp_do_404();
	}
}
add_action( 'bp_template_redirect', 'bp_core_catch_no_access', 1 );

/**
 * Redirect a user to log in for BP pages that require access control.
 *
 * Add an error message (if one is provided).
 *
 * If authenticated, redirects user back to requested content by default.
 *
 * @since 1.5.0
 *
 * @param array|string $args {
 *     Optional. Array of arguments for redirecting user when visiting access controlled areas.
 *     @type int    $mode     Specifies the destination of the redirect. 1 will
 *                            direct to the root domain (home page), which assumes you have a
 *                            log-in form there; 2 directs to wp-login.php. Default: 2.
 *     @type string $redirect The URL the user will be redirected to after successfully
 *                            logging in. Default: the URL originally requested.
 *     @type string $root     The root URL of the site, used in case of error or mode 1 redirects.
 *                            Default: the value of {@link bp_get_root_url()}.
 *     @type string $message  An error message to display to the user on the log-in page.
 *                            Default: "You must log in to access the page you requested."
 * }
 */
function bp_core_no_access( $args = '' ) {

	// Build the redirect URL.
	$redirect_url  = is_ssl() ? 'https://' : 'http://';
	$redirect_url .= $_SERVER['HTTP_HOST'];
	$redirect_url .= $_SERVER['REQUEST_URI'];

	$defaults = array(
		'mode'     => 2,                    // 1 = $root, 2 = wp-login.php.
		'redirect' => $redirect_url,        // the URL you get redirected to when a user successfully logs in.
		'root'     => bp_get_root_url(),    // the landing page you get redirected to when a user doesn't have access.
		'message'  => __( 'You must log in to access the page you requested.', 'buddypress' ),
	);

	$r = bp_parse_args(
		$args,
		$defaults
	);

	/**
	 * Filters the arguments used for user redirecting when visiting access controlled areas.
	 *
	 * @since 1.6.0
	 *
	 * @param array $r Array of parsed arguments for redirect determination.
	 */
	$r = apply_filters( 'bp_core_no_access', $r );

	extract( $r, EXTR_SKIP );

	/*
	 * @ignore Ignore these filters and use 'bp_core_no_access' above.
	 */
	$mode     = apply_filters( 'bp_no_access_mode', $mode, $root, $redirect, $message );
	$redirect = apply_filters( 'bp_no_access_redirect', $redirect, $root, $message, $mode );
	$root     = apply_filters( 'bp_no_access_root', $root, $redirect, $message, $mode );
	$message  = apply_filters( 'bp_no_access_message', $message, $root, $redirect, $mode );
	$root     = trailingslashit( $root );

	switch ( $mode ) {

		// Option to redirect to wp-login.php.
		// Error message is displayed with bp_core_no_access_wp_login_error().
		case 2:
			if ( ! empty( $redirect ) ) {
				bp_core_redirect(
					add_query_arg(
						array(
							'bp-auth' => 1,
							'action'  => 'bpnoaccess',
						),
						wp_login_url( $redirect )
					)
				);
			} else {
				bp_core_redirect( $root );
			}

			break;

		// Redirect to root with "redirect_to" parameter.
		// Error message is displayed with bp_core_add_message().
		case 1:
		default:
			$url = $root;
			if ( ! empty( $redirect ) ) {
				$url = add_query_arg( 'redirect_to', urlencode( $redirect ), $root );
			}

			if ( ! empty( $message ) ) {
				bp_core_add_message( $message, 'error' );
			}

			bp_core_redirect( $url );

			break;
	}
}

/**
 * Login redirector.
 *
 * If a link is not publicly available, we can send members from external
 * locations, like following links in an email, through the login screen.
 *
 * If a user clicks on this link and is already logged in, we should attempt
 * to redirect the user to the authorized content instead of forcing the user
 * to re-authenticate.
 *
 * @since 2.9.0
 */
function bp_login_redirector() {
	// Redirect links must include the `redirect_to` and `bp-auth` parameters.
	if ( empty( $_GET['redirect_to'] ) || empty( $_GET['bp-auth'] ) ) {
		return;
	}

	/*
	 * If the user is already logged in,
	 * skip the login form and redirect them to the content.
	 */
	if ( bp_loggedin_user_id() ) {
		wp_safe_redirect( esc_url_raw( $_GET['redirect_to'] ) );
		exit;
	}
}
add_action( 'login_init', 'bp_login_redirector', 1 );

/**
 * Add a custom BuddyPress no access error message to wp-login.php.
 *
 * @since 1.5.0
 * @since 2.7.0 Hook moved to 'wp_login_errors' made available since WP 3.6.0.
 *
 * @param  WP_Error $errors Current error container.
 * @return WP_Error
 */
function bp_core_no_access_wp_login_error( $errors ) {
	if ( empty( $_GET['action'] ) || 'bpnoaccess' !== $_GET['action'] ) {
		return $errors;
	}

	/**
	 * Filters the error message for wp-login.php when needing to log in before accessing.
	 *
	 * @since 1.5.0
	 *
	 * @param string $value Error message to display.
	 * @param string $value URL to redirect user to after successful login.
	 */
	$message = apply_filters( 'bp_wp_login_error', __( 'You must log in to access the page you requested.', 'buddypress' ), $_REQUEST['redirect_to'] );

	$errors->add( 'bp_no_access', $message );

	return $errors;
}
add_filter( 'wp_login_errors', 'bp_core_no_access_wp_login_error' );

/**
 * Add our custom error code to WP login's shake error codes.
 *
 * @since 2.7.0
 *
 * @param  array $codes Array of WP error codes.
 * @return array
 */
function bp_core_login_filter_shake_codes( $codes ) {
	$codes[] = 'bp_no_access';
	return $codes;
}
add_filter( 'shake_error_codes', 'bp_core_login_filter_shake_codes' );

/**
 * Canonicalize BuddyPress URLs.
 *
 * This function ensures that requests for BuddyPress content are always
 * redirected to their canonical versions. Canonical versions are always
 * trailingslashed, and are typically the most general possible versions of the
 * URL - eg, example.com/groups/mygroup/ instead of
 * example.com/groups/mygroup/home/.
 *
 * @since 1.6.0
 *
 * @see BP_Members_Component::setup_globals() where
 *      $bp->canonical_stack['base_url'] and ['component'] may be set.
 * @see bp_core_new_nav_item() where $bp->canonical_stack['action'] may be set.
 */
function bp_redirect_canonical() {

	/**
	 * Filters whether or not to do canonical redirects on BuddyPress URLs.
	 *
	 * @since 1.6.0
	 *
	 * @param bool $value Whether or not to do canonical redirects. Default true.
	 */
	if ( ! bp_is_blog_page() && apply_filters( 'bp_do_redirect_canonical', true ) ) {
		// If this is a POST request, don't do a canonical redirect.
		// This is for backward compatibility with plugins that submit form requests to
		// non-canonical URLs. Plugin authors should do their best to use canonical URLs in
		// their form actions.
		if ( ! empty( $_POST ) ) {
			return;
		}

		// Build the URL in the address bar.
		$requested_url = bp_get_requested_url();
		$query_args    = '';

		// Stash query args.
		if ( bp_has_pretty_urls() ) {
			$query_args    = wp_parse_url( $requested_url, PHP_URL_QUERY );
			$req_url_clean = str_replace( '?' . $query_args, '', $requested_url );
		} else {
			$req_url_clean = $requested_url;
		}

		$canonical_url = bp_get_canonical_url();

		// Only redirect if we've assembled a URL different from the request.
		if ( esc_url( $canonical_url ) !== esc_url( $req_url_clean ) ) {
			$bp = buddypress();

			// Template messages have been deleted from the cookie by this point, so
			// they must be readded before redirecting.
			if ( isset( $bp->template_message ) ) {
				$message      = stripslashes( $bp->template_message );
				$message_type = isset( $bp->template_message_type ) ? $bp->template_message_type : 'success';

				bp_core_add_message( $message, $message_type );
			}

			if ( ! empty( $query_args ) ) {
				$canonical_url .= '?' . $query_args;
			}

			bp_core_redirect( $canonical_url, 301 );
		}
	}
}

/**
 * Output rel=canonical header tag for BuddyPress content.
 *
 * @since 1.6.0
 */
function bp_rel_canonical() {
	$canonical_url = bp_get_canonical_url();

	// Output rel=canonical tag.
	echo "<link rel='canonical' href='" . esc_attr( $canonical_url ) . "' />\n";
}

/**
 * Get the canonical URL of the current page.
 *
 * @since 1.6.0
 *
 * @param array $args {
 *     Optional array of arguments.
 *     @type bool $include_query_args Whether to include current URL arguments
 *                                    in the canonical URL returned from the function.
 * }
 * @return string Canonical URL for the current page.
 */
function bp_get_canonical_url( $args = array() ) {

	// For non-BP content, return the requested url, and let WP do the work.
	if ( bp_is_blog_page() ) {
		return bp_get_requested_url();
	}

	$bp = buddypress();

	$defaults = array(
		'include_query_args' => false, // Include URL arguments, eg ?foo=bar&foo2=bar2.
	);

	$r = bp_parse_args(
		$args,
		$defaults
	);

	// Special case: when a BuddyPress directory (eg example.com/members)
	// is set to be the front page, ensure that the current canonical URL
	// is the home page URL.
	if ( 'page' === get_option( 'show_on_front' ) && $page_on_front = (int) get_option( 'page_on_front' ) ) {
		$front_page_component = array_search( $page_on_front, bp_core_get_directory_page_ids(), true );

		/*
		 * If requesting the front page component directory, canonical
		 * URL is the front page. We detect whether we're detecting a
		 * component *directory* by checking that bp_current_action()
		 * is empty - ie, this not a single item, a feed, or an item
		 * type directory.
		 */
		if ( false !== $front_page_component && bp_is_current_component( $front_page_component ) && ! bp_current_action() && ! bp_get_current_member_type() ) {
			$bp->canonical_stack['canonical_url'] = trailingslashit( bp_get_root_url() );

			// Except when the front page is set to the registration page
			// and the current user is logged in. In this case we send to
			// the members directory to avoid redirect loops.
		} elseif ( bp_is_register_page() && 'register' === $front_page_component && is_user_logged_in() ) {

			/**
			 * Filters the logged in register page redirect URL.
			 *
			 * @since 1.5.1
			 *
			 * @param string $value URL to redirect logged in members to.
			 */
			$bp->canonical_stack['canonical_url'] = apply_filters( 'bp_loggedin_register_page_redirect_to', bp_get_members_directory_permalink() );
		}
	}

	if ( empty( $bp->canonical_stack['canonical_url'] ) ) {
		// Build the URL in the address bar.
		$requested_url = bp_get_requested_url();
		$base_url      = '';
		$path_chunks   = array();
		$component_id  = '';

		// Get query args.
		$query_string = wp_parse_url( $requested_url, PHP_URL_QUERY );
		$query_args   = wp_parse_args( $query_string, array() );

		// Build the canonical URL out of the redirect stack.
		if ( isset( $bp->canonical_stack['base_url'] ) ) {
			$base_url = $bp->canonical_stack['base_url'];
		} else {
			$base_url = $requested_url;

			if ( bp_has_pretty_urls() ) {
				$base_url = str_replace( '?' . $query_string, '', $requested_url );
			}
		}

		// This is a BP Members URL.
		if ( isset( $bp->canonical_stack['component'] ) ) {
			$component_id  = 'members';
			$path_chunks[] = $bp->canonical_stack['component'];

			if ( $query_args ) {
				$query_args = array_diff_key(
					$query_args,
					array_fill_keys(
						array( 'bp_members', 'bp_member', 'bp_member_component' ),
						true
					)
				);
			}
		} else {
			$component_id = 'groups';
		}

		if ( isset( $bp->canonical_stack['action'] ) ) {
			$path_chunks[]        = $bp->canonical_stack['action'];
			$action_key           = 'bp_member_action';
			$action_variables_key = 'bp_member_action_variables';

			if ( 'groups' === $component_id ) {
				$action_key           = 'bp_group_action';
				$action_variables_key = 'bp_group_action_variables';
			}

			if ( ! empty( $bp->canonical_stack['action_variables'] ) ) {
				$path_chunks = array_merge( $path_chunks, (array) $bp->canonical_stack['action_variables'] );
			} elseif ( isset( $query_args[ $action_variables_key ] ) ) {
				unset( $query_args[ $action_variables_key ] );
			}

			if ( $query_args ) {
				$query_args = array_diff_key(
					$query_args,
					array_fill_keys(
						array( $action_key, $action_variables_key ),
						true
					)
				);
			}
		} elseif ( isset( $query_args['bp_member_action'] ) && 'members' === $component_id ) {
			unset( $query_args['bp_member_action'] );
		} elseif ( isset( $query_args['bp_group_action'] ) && 'groups' === $component_id ) {
			unset( $query_args['bp_group_action'] );
		}

		if ( $path_chunks ) {
			if ( 'groups' === $component_id ) {
				$bp->canonical_stack['canonical_url'] = bp_get_group_url(
					groups_get_current_group(),
					bp_groups_get_path_chunks( $path_chunks )
				);
			} else {
				$bp->canonical_stack['canonical_url'] = bp_displayed_user_url( bp_members_get_path_chunks( $path_chunks ) );
			}
		} else {
			$bp->canonical_stack['canonical_url'] = $base_url;
		}
	}

	$canonical_url = $bp->canonical_stack['canonical_url'];

	if ( $r['include_query_args'] && $query_args ) {
		$canonical_url = add_query_arg( $query_args, $canonical_url );
	}

	/**
	 * Filters the canonical url of the current page.
	 *
	 * @since 1.6.0
	 *
	 * @param string $canonical_url Canonical URL of the current page.
	 * @param array  $args          Array of arguments to help determine canonical URL.
	 */
	return apply_filters( 'bp_get_canonical_url', $canonical_url, $args );
}

/**
 * Return the URL as requested on the current page load by the user agent.
 *
 * @since 1.6.0
 *
 * @return string Requested URL string.
 */
function bp_get_requested_url() {
	$bp = buddypress();

	if ( empty( $bp->canonical_stack['requested_url'] ) ) {
		$bp->canonical_stack['requested_url']  = is_ssl() ? 'https://' : 'http://';
		$bp->canonical_stack['requested_url'] .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Filters the URL as requested on the current page load by the user agent.
	 *
	 * @since 1.7.0
	 *
	 * @param string $value Requested URL string.
	 */
	return apply_filters( 'bp_get_requested_url', $bp->canonical_stack['requested_url'] );
}

/**
 * Remove WP's canonical redirect when we are trying to load BP-specific content.
 *
 * Avoids issues with WordPress thinking that a BuddyPress URL might actually
 * be a blog post or page.
 *
 * This function should be considered temporary, and may be removed without
 * notice in future versions of BuddyPress.
 *
 * @since 1.6.0
 */
function _bp_maybe_remove_redirect_canonical() {
	if ( ! bp_is_blog_page() ) {
		remove_action( 'template_redirect', 'redirect_canonical' );
	}
}

/**
 * Rehook maybe_redirect_404() to run later than the default.
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
 * @link https://buddypress.trac.wordpress.org/ticket/4329
 * @link https://buddypress.trac.wordpress.org/ticket/4415
 */
function _bp_rehook_maybe_redirect_404() {
	if ( defined( 'NOBLOGREDIRECT' ) && is_multisite() ) {
		remove_action( 'template_redirect', 'maybe_redirect_404' );
		add_action( 'template_redirect', 'maybe_redirect_404', 100 );
	}
}
add_action( 'template_redirect', '_bp_rehook_maybe_redirect_404', 1 );

/**
 * Remove WP's rel=canonical HTML tag if we are trying to load BP-specific content.
 *
 * This function should be considered temporary, and may be removed without
 * notice in future versions of BuddyPress.
 *
 * @since 1.6.0
 */
function _bp_maybe_remove_rel_canonical() {
	if ( ! bp_is_blog_page() && ! is_404() ) {
		remove_action( 'wp_head', 'rel_canonical' );
		add_action( 'bp_head', 'bp_rel_canonical' );
	}
}
add_action( 'wp_head', '_bp_maybe_remove_rel_canonical', 8 );

/**
 * Stop WordPress performing a DB query for its main loop.
 *
 * As of WordPress 4.6, it is possible to bypass the main WP_Query entirely.
 * This saves us one unnecessary database query! :)
 *
 * @since 2.7.0
 *
 * @param  null     $retval Current return value for filter.
 * @param  WP_Query $query  Current WordPress query object.
 * @return null|array
 */
function bp_core_filter_wp_query( $retval, $query ) {
	if ( ! $query->is_main_query() ) {
		return $retval;
	}

	/*
	 * If not on a BP single page, bail.
	 * Too early to use bp_is_single_item(), so use BP conditionals.
	 */
	if ( false === ( bp_is_group() || bp_is_user() || bp_is_single_activity() ) ) {
		return $retval;
	}

	// Set default properties as recommended in the 'posts_pre_query' DocBlock.
	$query->found_posts   = 0;
	$query->max_num_pages = 0;

	// Return something other than a null value to bypass WP_Query.
	return array();
}
