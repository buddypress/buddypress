<?php

/**
 * BuddyPress Filters.
 *
 * This file contains the filters that are used throughout BuddyPress. They are
 * consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional filters can currently be found.
 *
 *  - BuddyPress: In {@link BuddyPress::setup_actions()} in buddypress.php
 *  - Component: In {@link BP_Component::setup_actions()} in
 *                bp-core/bp-core-component.php
 *  - Admin: More in {@link BP_Admin::setup_actions()} in
 *            bp-core/bp-core-admin.php
 *
 * @package BuddyPress
 * @subpackage Core
 * @see bp-core-actions.php
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Attach BuddyPress to WordPress.
 *
 * BuddyPress uses its own internal actions to help aid in third-party plugin
 * development, and to limit the amount of potential future code changes when
 * updates to WordPress core occur.
 *
 * These actions exist to create the concept of 'plugin dependencies'. They
 * provide a safe way for plugins to execute code *only* when BuddyPress is
 * installed and activated, without needing to do complicated guesswork.
 *
 * For more information on how this works, see the 'Plugin Dependency' section
 * near the bottom of this file.
 *
 *           v--WordPress Actions       v--BuddyPress Sub-actions
 */
add_filter( 'request',                 'bp_request',             10    );
add_filter( 'template_include',        'bp_template_include',    10    );
add_filter( 'login_redirect',          'bp_login_redirect',      10, 3 );
add_filter( 'map_meta_cap',            'bp_map_meta_caps',       10, 4 );

// Add some filters to feedback messages
add_filter( 'bp_core_render_message_content', 'wptexturize'       );
add_filter( 'bp_core_render_message_content', 'convert_smilies'   );
add_filter( 'bp_core_render_message_content', 'convert_chars'     );
add_filter( 'bp_core_render_message_content', 'wpautop'           );
add_filter( 'bp_core_render_message_content', 'shortcode_unautop' );
add_filter( 'bp_core_render_message_content', 'wp_kses_data', 5   );

/**
 * Template Compatibility.
 *
 * If you want to completely bypass this and manage your own custom BuddyPress
 * template hierarchy, start here by removing this filter, then look at how
 * bp_template_include() works and do something similar. :)
 */
add_filter( 'bp_template_include',   'bp_template_include_theme_supports', 2, 1 );
add_filter( 'bp_template_include',   'bp_template_include_theme_compat',   4, 2 );

// Filter BuddyPress template locations
add_filter( 'bp_get_template_stack', 'bp_add_template_stack_locations'          );

// Turn comments off for BuddyPress pages
add_filter( 'comments_open', 'bp_comments_open', 10, 2 );

/**
 * Prevent specific pages (eg 'Activate') from showing on page listings.
 *
 * @uses bp_is_active() checks if a BuddyPress component is active.
 *
 * @param array $pages List of excluded page IDs, as passed to the
 *        'wp_list_pages_excludes' filter.
 * @return array The exclude list, with BP's pages added.
 */
function bp_core_exclude_pages( $pages = array() ) {

	// Bail if not the root blog
	if ( ! bp_is_root_blog() )
		return $pages;

	$bp = buddypress();

	if ( !empty( $bp->pages->activate ) )
		$pages[] = $bp->pages->activate->id;

	if ( !empty( $bp->pages->register ) )
		$pages[] = $bp->pages->register->id;

	if ( !empty( $bp->pages->forums ) && ( !bp_is_active( 'forums' ) || ( bp_is_active( 'forums' ) && bp_forums_has_directory() && !bp_forums_is_installed_correctly() ) ) )
		$pages[] = $bp->pages->forums->id;

	return apply_filters( 'bp_core_exclude_pages', $pages );
}
add_filter( 'wp_list_pages_excludes', 'bp_core_exclude_pages' );

/**
 * Prevent specific pages (eg 'Activate') from showing in the Pages meta box of the Menu Administration screen.
 *
 * @since BuddyPress (2.0.0)
 *
 * @uses bp_is_root_blog() checks if current blog is root blog.
 * @uses buddypress() gets BuddyPress main instance
 *
 * @param object $object The post type object used in the meta box
 * @return object The $object, with a query argument to remove register and activate pages id.
 */
function bp_core_exclude_pages_from_nav_menu_admin( $object = null ) {

	// Bail if not the root blog
	if ( ! bp_is_root_blog() ) {
		return $object;
	}

	if ( 'page' != $object->name ) {
		return $object;
	}

	$bp = buddypress();
	$pages = array();

	if ( ! empty( $bp->pages->activate ) ) {
		$pages[] = $bp->pages->activate->id;
	}

	if ( ! empty( $bp->pages->register ) ) {
		$pages[] = $bp->pages->register->id;
	}

	if ( ! empty( $pages ) ) {
		$object->_default_query['post__not_in'] = $pages;
	}

	return $object;
}
add_filter( 'nav_menu_meta_box_object', 'bp_core_exclude_pages_from_nav_menu_admin', 11, 1 );

/**
 * Set "From" name in outgoing email to the site name.
 *
 * @uses bp_get_option() fetches the value for a meta_key in the wp_X_options table.
 *
 * @return string The blog name for the root blog.
 */
function bp_core_email_from_name_filter() {
 	return apply_filters( 'bp_core_email_from_name_filter', bp_get_option( 'blogname', 'WordPress' ) );
}
add_filter( 'wp_mail_from_name', 'bp_core_email_from_name_filter' );

/**
 * Filter the blog post comments array and insert BuddyPress URLs for users.
 *
 * @param array $comments The array of comments supplied to the comments template.
 * @param int $post->ID The post ID.
 * @return array $comments The modified comment array.
 */
function bp_core_filter_comments( $comments, $post_id ) {
	global $wpdb;

	foreach( (array) $comments as $comment ) {
		if ( $comment->user_id )
			$user_ids[] = $comment->user_id;
	}

	if ( empty( $user_ids ) )
		return $comments;

	$user_ids = implode( ',', wp_parse_id_list( $user_ids ) );

	if ( !$userdata = $wpdb->get_results( "SELECT ID as user_id, user_login, user_nicename FROM {$wpdb->users} WHERE ID IN ({$user_ids})" ) )
		return $comments;

	foreach( (array) $userdata as $user )
		$users[$user->user_id] = bp_core_get_user_domain( $user->user_id, $user->user_nicename, $user->user_login );

	foreach( (array) $comments as $i => $comment ) {
		if ( !empty( $comment->user_id ) ) {
			if ( !empty( $users[$comment->user_id] ) )
				$comments[$i]->comment_author_url = $users[$comment->user_id];
		}
	}

	return $comments;
}
add_filter( 'comments_array', 'bp_core_filter_comments', 10, 2 );

/**
 * When a user logs in, redirect him in a logical way.
 *
 * @uses apply_filters() Filter 'bp_core_login_redirect' to modify where users
 *       are redirected to on login.
 *
 * @param string $redirect_to The URL to be redirected to, sanitized
 *        in wp-login.php.
 * @param string $redirect_to_raw The unsanitized redirect_to URL ($_REQUEST['redirect_to'])
 * @param WP_User $user The WP_User object corresponding to a successfully
 *        logged-in user. Otherwise a WP_Error object.
 * @return string The redirect URL.
 */
function bp_core_login_redirect( $redirect_to, $redirect_to_raw, $user ) {

	// Only modify the redirect if we're on the main BP blog
	if ( !bp_is_root_blog() ) {
		return $redirect_to;
	}

	// Only modify the redirect once the user is logged in
	if ( !is_a( $user, 'WP_User' ) ) {
		return $redirect_to;
	}

	// Allow plugins to allow or disallow redirects, as desired
	$maybe_redirect = apply_filters( 'bp_core_login_redirect', false, $redirect_to, $redirect_to_raw, $user );
	if ( false !== $maybe_redirect ) {
		return $maybe_redirect;
	}

	// If a 'redirect_to' parameter has been passed that contains 'wp-admin', verify that the
	// logged-in user has any business to conduct in the Dashboard before allowing the
	// redirect to go through
	if ( !empty( $redirect_to ) && ( false === strpos( $redirect_to, 'wp-admin' ) || user_can( $user, 'edit_posts' ) ) ) {
		return $redirect_to;
	}

	if ( false === strpos( wp_get_referer(), 'wp-login.php' ) && false === strpos( wp_get_referer(), 'activate' ) && empty( $_REQUEST['nr'] ) ) {
		return wp_get_referer();
	}

	return apply_filters( 'bp_core_login_redirect_to', bp_get_root_domain() );
}
add_filter( 'bp_login_redirect', 'bp_core_login_redirect', 10, 3 );

/**
 * Replace the generated password in the welcome email with '[User Set]'.
 *
 * On a standard BP installation, users who register themselves also set their
 * own passwords. Therefore there is no need for the insecure practice of
 * emailing the plaintext password to the user in the welcome email.
 *
 * This filter will not fire when a user is registered by the site admin.
 *
 * @param string $welcome_email Complete email passed through WordPress.
 * @return string Filtered $welcome_email with the password replaced
 *         by '[User Set]'.
 */
function bp_core_filter_user_welcome_email( $welcome_email ) {

	// Don't touch the email when a user is registered by the site admin
	if ( ( is_admin() || is_network_admin() ) && buddypress()->members->admin->signups_page != get_current_screen()->id ) {
		return $welcome_email;
	}

	if ( strpos( bp_get_requested_url(), 'wp-activate.php' ) !== false ) {
		return $welcome_email;
	}

	// Don't touch the email if we don't have a custom registration template
	if ( ! bp_has_custom_signup_page() ) {
		return $welcome_email;
	}

	// [User Set] Replaces 'PASSWORD' in welcome email; Represents value set by user
	return str_replace( 'PASSWORD', __( '[User Set]', 'buddypress' ), $welcome_email );
}
add_filter( 'update_welcome_user_email', 'bp_core_filter_user_welcome_email' );

/**
 * Replace the generated password in the welcome email with '[User Set]'.
 *
 * On a standard BP installation, users who register themselves also set their
 * own passwords. Therefore there is no need for the insecure practice of
 * emailing the plaintext password to the user in the welcome email.
 *
 * This filter will not fire when a user is registered by the site admin.
 *
 * @param string $welcome_email Complete email passed through WordPress.
 * @param int $blog_id ID of the blog user is joining.
 * @param int $user_id ID of the user joining.
 * @param string $password Password of user.
 * @return string Filtered $welcome_email with $password replaced by '[User Set]'.
 */
function bp_core_filter_blog_welcome_email( $welcome_email, $blog_id, $user_id, $password ) {

	// Don't touch the email when a user is registered by the site admin
	if ( ( is_admin() || is_network_admin() ) && buddypress()->members->admin->signups_page != get_current_screen()->id ) {
		return $welcome_email;
	}

	// Don't touch the email if we don't have a custom registration template
	if ( ! bp_has_custom_signup_page() )
		return $welcome_email;

	// [User Set] Replaces $password in welcome email; Represents value set by user
	return str_replace( $password, __( '[User Set]', 'buddypress' ), $welcome_email );
}
add_filter( 'update_welcome_email', 'bp_core_filter_blog_welcome_email', 10, 4 );

/**
 * Notify new users of a successful registration (with blog).
 *
 * This function filter's WP's 'wpmu_signup_blog_notification', and replaces
 * WP's default welcome email with a BuddyPress-specific message.
 *
 * @see wpmu_signup_blog_notification() for a description of parameters.
 *
 * @param string $domain The new blog domain.
 * @param string $path The new blog path.
 * @param string $title The site title.
 * @param string $user The user's login name.
 * @param string $user_email The user's email address.
 * @param string $key The activation key created in wpmu_signup_blog()
 * @param array $meta By default, contains the requested privacy setting and
 *        lang_id.
 * @return bool True on success, false on failure.
 */
function bp_core_activation_signup_blog_notification( $domain, $path, $title, $user, $user_email, $key, $meta ) {

	// Set up activation link
	$activate_url = bp_get_activation_page() ."?key=$key";
	$activate_url = esc_url( $activate_url );

	// Email contents
	$message = sprintf( __( "%1\$s,\n\n\n\nThanks for registering! To complete the activation of your account and blog, please click the following link:\n\n%2\$s\n\n\n\nAfter you activate, you can visit your blog here:\n\n%3\$s", 'buddypress' ), $user, $activate_url, esc_url( "http://{$domain}{$path}" ) );
	$subject = bp_get_email_subject( array( 'text' => sprintf( __( 'Activate %s', 'buddypress' ), 'http://' . $domain . $path ) ) );

	// Email filters
	$to      = apply_filters( 'bp_core_activation_signup_blog_notification_to',   $user_email, $domain, $path, $title, $user, $user_email, $key, $meta );
	$subject = apply_filters( 'bp_core_activation_signup_blog_notification_subject', $subject, $domain, $path, $title, $user, $user_email, $key, $meta );
	$message = apply_filters( 'bp_core_activation_signup_blog_notification_message', $message, $domain, $path, $title, $user, $user_email, $key, $meta );

	// Send the email
	wp_mail( $to, $subject, $message );

	// Set up the $admin_email to pass to the filter
	$admin_email = bp_get_option( 'admin_email' );

	do_action( 'bp_core_sent_blog_signup_email', $admin_email, $subject, $message, $domain, $path, $title, $user, $user_email, $key, $meta );

	// Return false to stop the original WPMU function from continuing
	return false;
}
add_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 7 );

/**
 * Notify new users of a successful registration (without blog).
 *
 * @see wpmu_signup_user_notification() for a full description of params.
 *
 * @param string $user The user's login name.
 * @param string $user_email The user's email address.
 * @param string $key The activation key created in wpmu_signup_user()
 * @param array $meta By default, an empty array.
 * @return bool True on success, false on failure.
 */
function bp_core_activation_signup_user_notification( $user, $user_email, $key, $meta ) {

	if ( is_admin() ) {
		// If the user is created from the WordPress Add User screen, don't send BuddyPress signup notifications
		if( in_array( get_current_screen()->id, array( 'user', 'user-network' ) ) ) {
			// If the Super Admin want to skip confirmation email
			if ( isset( $_POST[ 'noconfirmation' ] ) && is_super_admin() ) {
				return false;

			// WordPress will manage the signup process
			} else {
				return $user;
			}

		/**
		 * There can be a case where the user was created without the skip confirmation
		 * And the super admin goes in pending accounts to resend it. In this case, as the
		 * meta['password'] is not set, the activation url must be WordPress one
		 */
		} else if ( buddypress()->members->admin->signups_page == get_current_screen()->id ) {
			$is_hashpass_in_meta = maybe_unserialize( $meta );

			if ( empty( $is_hashpass_in_meta['password'] ) ) {
				return $user;
			}
		}
	}

	// Set up activation link
	$activate_url = bp_get_activation_page() . "?key=$key";
	$activate_url = esc_url( $activate_url );

	// Email contents
	$message = sprintf( __( "Thanks for registering! To complete the activation of your account please click the following link:\n\n%1\$s\n\n", 'buddypress' ), $activate_url );
	$subject = bp_get_email_subject( array( 'text' => __( 'Activate Your Account', 'buddypress' ) ) );

	// Email filters
	$to      = apply_filters( 'bp_core_activation_signup_user_notification_to',   $user_email, $user, $user_email, $key, $meta );
	$subject = apply_filters( 'bp_core_activation_signup_user_notification_subject', $subject, $user, $user_email, $key, $meta );
	$message = apply_filters( 'bp_core_activation_signup_user_notification_message', $message, $user, $user_email, $key, $meta );

	// Send the email
	wp_mail( $to, $subject, $message );

	// Set up the $admin_email to pass to the filter
	$admin_email = bp_get_option( 'admin_email' );

	do_action( 'bp_core_sent_user_signup_email', $admin_email, $subject, $message, $user, $user_email, $key, $meta );

	// Return false to stop the original WPMU function from continuing
	return false;
}
add_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );

/**
 * Filter the page title for BuddyPress pages.
 *
 * @since BuddyPress (1.5.0)
 *
 * @see wp_title()
 * @global object $bp BuddyPress global settings.
 *
 * @param string $title Original page title.
 * @param string $sep How to separate the various items within the page title.
 * @param string $seplocation Direction to display title.
 * @return string New page title.
 */
function bp_modify_page_title( $title, $sep, $seplocation ) {
	global $bp;

	// If this is not a BP page, just return the title produced by WP
	if ( bp_is_blog_page() )
		return $title;

	// If this is a 404, let WordPress handle it
	if ( is_404() ) {
		return $title;
	}

	// If this is the front page of the site, return WP's title
	if ( is_front_page() || is_home() )
		return $title;

	$title = '';

	// Displayed user
	if ( bp_get_displayed_user_fullname() && !is_404() ) {

		// Get the component's ID to try and get it's name
		$component_id = $component_name = bp_current_component();

		// Use the actual component name
		if ( !empty( $bp->{$component_id}->name ) ) {
			$component_name = $bp->{$component_id}->name;

		// Fall back on the component ID (probably same as current_component)
		} elseif ( !empty( $bp->{$component_id}->id ) ) {
			$component_name = $bp->{$component_id}->id;
		}

		// Construct the page title. 1 = user name, 2 = seperator, 3 = component name
		$title = strip_tags( sprintf( _x( '%1$s %3$s %2$s', 'Construct the page title. 1 = user name, 2 = component name, 3 = seperator', 'buddypress' ), bp_get_displayed_user_fullname(), ucwords( $component_name ), $sep ) );

	// A single group
	} elseif ( bp_is_active( 'groups' ) && !empty( $bp->groups->current_group ) && !empty( $bp->bp_options_nav[$bp->groups->current_group->slug] ) ) {
		$subnav = isset( $bp->bp_options_nav[$bp->groups->current_group->slug][bp_current_action()]['name'] ) ? $bp->bp_options_nav[$bp->groups->current_group->slug][bp_current_action()]['name'] : '';
		// translators: "group name | group nav section name"
		$title = sprintf( __( '%1$s | %2$s', 'buddypress' ), $bp->bp_options_title, $subnav );

	// A single item from a component other than groups
	} elseif ( bp_is_single_item() ) {
		// translators: "component item name | component nav section name | root component name"
		$title = sprintf( __( '%1$s | %2$s | %3$s', 'buddypress' ), $bp->bp_options_title, $bp->bp_options_nav[bp_current_item()][bp_current_action()]['name'], bp_get_name_from_root_slug( bp_get_root_slug() ) );

	// An index or directory
	} elseif ( bp_is_directory() ) {

		$current_component = bp_current_component();

		// No current component (when does this happen?)
		if ( empty( $current_component ) ) {
			$title = _x( 'Directory', 'component directory title', 'buddypress' );
		} else {
			$title = bp_get_directory_title( $current_component );
		}

	// Sign up page
	} elseif ( bp_is_register_page() ) {
		$title = __( 'Create an Account', 'buddypress' );

	// Activation page
	} elseif ( bp_is_activation_page() ) {
		$title = __( 'Activate your Account', 'buddypress' );

	// Group creation page
	} elseif ( bp_is_group_create() ) {
		$title = __( 'Create a Group', 'buddypress' );

	// Blog creation page
	} elseif ( bp_is_create_blog() ) {
		$title = __( 'Create a Site', 'buddypress' );
	}

	// Some BP nav items contain item counts. Remove them
	$title = preg_replace( '|<span>[0-9]+</span>|', '', $title );

	return apply_filters( 'bp_modify_page_title', $title . ' ' . $sep . ' ', $title, $sep, $seplocation );
}
add_filter( 'wp_title', 'bp_modify_page_title', 10, 3 );
add_filter( 'bp_modify_page_title', 'wptexturize'     );
add_filter( 'bp_modify_page_title', 'convert_chars'   );
add_filter( 'bp_modify_page_title', 'esc_html'        );

/**
 * Add BuddyPress-specific items to the wp_nav_menu.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param WP_Post $menu_item The menu item.
 * @return obj The modified WP_Post object.
 */
function bp_setup_nav_menu_item( $menu_item ) {
	if ( is_admin() ) {
		return $menu_item;
	}

	// We use information stored in the CSS class to determine what kind of
	// menu item this is, and how it should be treated
	$css_target = preg_match( '/\sbp-(.*)-nav/', implode( ' ', $menu_item->classes), $matches );

	// If this isn't a BP menu item, we can stop here
	if ( empty( $matches[1] ) ) {
		return $menu_item;
	}

	switch ( $matches[1] ) {
		case 'login' :
			if ( is_user_logged_in() ) {
				$menu_item->_invalid = true;
			} else {
				$menu_item->url = wp_login_url( bp_get_requested_url() );
			}

			break;

		case 'logout' :
			if ( ! is_user_logged_in() ) {
				$menu_item->_invalid = true;
			} else {
				$menu_item->url = wp_logout_url( bp_get_requested_url() );
			}

			break;

		// Don't show the Register link to logged-in users
		case 'register' :
			if ( is_user_logged_in() ) {
				$menu_item->_invalid = true;
			}

			break;

		// All other BP nav items are specific to the logged-in user,
		// and so are not relevant to logged-out users
		default:
			if ( is_user_logged_in() ) {
				$menu_item->url = bp_nav_menu_get_item_url( $matches[1] );
			} else {
				$menu_item->_invalid = true;
			}

			break;
	}

	// If component is deactivated, make sure menu item doesn't render
	if ( empty( $menu_item->url ) ) {
		$menu_item->_invalid = true;

	// Highlight the current page
	} else {
		$current = bp_get_requested_url();
		if ( strpos( $current, $menu_item->url ) !== false ) {
			$menu_item->classes[] = 'current_page_item';
		}
	}

	return $menu_item;
}
add_filter( 'wp_setup_nav_menu_item', 'bp_setup_nav_menu_item', 10, 1 );

/**
 * Filter SQL query strings to swap out the 'meta_id' column.
 *
 * WordPress uses the meta_id column for commentmeta and postmeta, and so
 * hardcodes the column name into its *_metadata() functions. BuddyPress, on
 * the other hand, uses 'id' for the primary column. To make WP's functions
 * usable for BuddyPress, we use this just-in-time filter on 'query' to swap
 * 'meta_id' with 'id.
 *
 * @since BuddyPress (2.0.0)
 *
 * @access private Do not use.
 *
 * @param string $q SQL query.
 * @return string
 */
function bp_filter_metaid_column_name( $q ) {
	return str_replace( 'meta_id', 'id', $q );
}

/**
 * Filter the edit post link to avoid its display in BuddyPress pages
 *
 * @since BuddyPress (2.1.0)
 *
 * @param  string $link    The edit link.
 * @param  int    $post_id Post ID.
 * @return mixed  Will be a boolean (false) if $post_id is 0. Will be a string (the unchanged edit link)
 *                otherwise
 */
function bp_core_filter_edit_post_link( $edit_link = '', $post_id = 0 ) {
	if ( 0 === $post_id ) {
		$edit_link = false;
	}

	return $edit_link;
}
