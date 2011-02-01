<?php

/**
 * Allow filtering of database prefix. Intended for use in multinetwork installations.
 *
 * @global object $wpdb WordPress database object
 * @return string Filtered database prefix
 */
function bp_core_get_table_prefix() {
	global $wpdb;

	return apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
}

/**
 * Fetches BP pages from the meta table, depending on setup
 *
 * @package BuddyPress Core Core
 */
function bp_core_get_page_meta() {
	if ( !defined( 'BP_ENABLE_MULTIBLOG' ) && is_multisite() )
		$page_ids = get_blog_option( BP_ROOT_BLOG, 'bp-pages' );
	else
		$page_ids = get_option( 'bp-pages' );

	return $page_ids;
}

/**
 * Stores BP pages in the meta table, depending on setup
 *
 * @package BuddyPress Core Core
 */
function bp_core_update_page_meta( $page_ids ) {
	if ( !defined( 'BP_ENABLE_MULTIBLOG' ) && is_multisite() )
		update_blog_option( BP_ROOT_BLOG, 'bp-pages', $page_ids );
	else
		update_option( 'bp-pages', $page_ids );
}

function bp_core_get_page_names() {
	global $wpdb, $bp;

	// Set pages as standard class
	$pages = new stdClass;

	// Get pages and IDs
	if ( $page_ids = bp_core_get_page_meta() ) {

		$posts_table_name = is_multisite() && !defined( 'BP_ENABLE_MULTIBLOG' ) ? $wpdb->get_blog_prefix( BP_ROOT_BLOG ) . 'posts' : $wpdb->posts;
		$page_ids_sql     = implode( ',', (array)$page_ids );
		$page_names       = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_name, post_parent FROM {$posts_table_name} WHERE ID IN ({$page_ids_sql}) " ) );

		foreach ( (array)$page_ids as $key => $page_id ) {
			foreach ( (array)$page_names as $page_name ) {
				if ( $page_name->ID == $page_id ) {
					$pages->{$key}->name = $page_name->post_name;
					$pages->{$key}->id   = $page_name->ID;
					$slug[]              = $page_name->post_name;

					// Get the slug
					while ( $page_name->post_parent != 0 ) {
						$parent                 = $wpdb->get_results( $wpdb->prepare( "SELECT post_name, post_parent FROM {$posts_table_name} WHERE ID = %d", $page_name->post_parent ) );
						$slug[]                 = $parent[0]->post_name;
						$page_name->post_parent = $parent[0]->post_parent;
					}

					$pages->{$key}->slug = implode( '/', array_reverse( (array)$slug ) );
				}

				unset( $slug );
			}
		}
	}

	return apply_filters( 'bp_core_get_page_names', $pages );
}

/**
 * Creates a default component slug from a WP page root_slug
 *
 * Since 1.3, BP components get their root_slug (the slug used immediately
 * following the root domain) from the slug of a corresponding WP page.
 *
 * E.g. if your BP installation at example.com has its members page at
 * example.com/community/people, $bp->members->root_slug will be 'community/people'.
 *
 * By default, this function creates a shorter version of the root_slug for
 * use elsewhere in the URL, by returning the content after the final '/'
 * in the root_slug ('people' in the example above).
 *
 * Filter on 'bp_core_component_slug_from_root_slug' to override this method
 * in general, or define a specific component slug constant (e.g. BP_MEMBERS_SLUG)
 * to override specific component slugs.
 *
 * @package BuddyPress Core
 * @since 1.3
 *
 * @param str $root_slug The root slug, which comes from $bp->pages->[component]->slug
 * @return str $slug The short slug for use in the middle of URLs
 */
function bp_core_component_slug_from_root_slug( $root_slug ) {
	$slug_chunks = explode( '/', $root_slug );
 	$slug        = array_pop( $slug_chunks );

 	return apply_filters( 'bp_core_component_slug_from_root_slug', $slug, $root_slug );
}

/**
 * Initializes the wp-admin area "BuddyPress" menus and sub menus.
 *
 * @package BuddyPress Core
 * @uses is_super_admin() returns true if the current user is a site admin, false if not
 */
function bp_core_admin_menu_init() {
	if ( !is_super_admin() )
		return false;

	require ( BP_PLUGIN_DIR . '/bp-core/admin/bp-core-admin.php' );
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'bp_core_admin_menu_init' );

/**
 * Adds the "BuddyPress" admin submenu item to the Site Admin tab.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses is_super_admin() returns true if the current user is a site admin, false if not
 * @uses add_submenu_page() WP function to add a submenu item
 */
function bp_core_add_admin_menu() {
	if ( !is_super_admin() )
		return false;

	// Don't add this version of the admin menu if a BP upgrade is in progress
 	// See bp_core_update_add_admin_menu()
	if ( defined( 'BP_IS_UPGRADE' ) && BP_IS_UPGRADE )
 		return false;

	$hooks = array();

	// Add the administration tab under the "Site Admin" tab for site administrators
	$hooks[] = bp_core_add_admin_menu_page( array(
		'menu_title' => __( 'BuddyPress', 'buddypress' ),
		'page_title' => __( 'BuddyPress', 'buddypress' ),
		'capability' => 'manage_options',
		'file'       => 'bp-general-settings',
		'function'   => 'bp_core_admin_component_setup',
		'position'   => 2
	) );

	$hooks[] = add_submenu_page( 'bp-general-settings', __( 'Components', 'buddypress' ), __( 'Components', 'buddypress' ), 'manage_options', 'bp-general-settings', 'bp_core_admin_component_setup'  );
	$hooks[] = add_submenu_page( 'bp-general-settings', __( 'Settings',   'buddypress' ), __( 'Settings',   'buddypress' ), 'manage_options', 'bp-settings',         'bp_core_admin_settings'         );

	// Add a hook for css/js
	foreach( $hooks as $hook )
		add_action( "admin_print_styles-$hook", 'bp_core_add_admin_menu_styles' );

}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'bp_core_add_admin_menu', 9 );

/**
 * Returns the domain for the root blog.
 * eg: http://domain.com/ OR https://domain.com
 *
 * @package BuddyPress Core
 * @uses get_blog_option() WordPress function to fetch blog meta.
 * @return $domain The domain URL for the blog.
 */
function bp_core_get_root_domain() {
	global $current_blog;

	if ( defined( 'BP_ENABLE_MULTIBLOG' ) )
		$domain = get_home_url( $current_blog->blog_id );
	else
		$domain = get_home_url( BP_ROOT_BLOG );

	return apply_filters( 'bp_core_get_root_domain', $domain );
}

/**
 * Get the current GMT time to save into the DB
 *
 * @package BuddyPress Core
 * @since 1.2.6
 */
function bp_core_current_time( $gmt = true ) {
	// Get current time in MYSQL format
	$current_time = current_time( 'mysql', $gmt );

	return apply_filters( 'bp_core_current_time', $current_time );
}

/**
 * Adds a feedback (error/success) message to the WP cookie so it can be
 * displayed after the page reloads.
 *
 * @package BuddyPress Core
 *
 * @global obj $bp
 * @param str $message Feedback to give to user
 * @param str $type updated|success|error|warning
 */
function bp_core_add_message( $message, $type = '' ) {
	global $bp;

	// Success is the default
	if ( empty( $type ) )
		$type = 'success';

	// Send the values to the cookie for page reload display
	@setcookie( 'bp-message',      $message, time() + 60 * 60 * 24, COOKIEPATH );
	@setcookie( 'bp-message-type', $type,    time() + 60 * 60 * 24, COOKIEPATH );

	/***
	 * Send the values to the $bp global so we can still output messages
	 * without a page reload
	 */
	$bp->template_message      = $message;
	$bp->template_message_type = $type;
}

/**
 * Checks if there is a feedback message in the WP cookie, if so, adds a
 * "template_notices" action so that the message can be parsed into the template
 * and displayed to the user.
 *
 * After the message is displayed, it removes the message vars from the cookie
 * so that the message is not shown to the user multiple times.
 *
 * @package BuddyPress Core
 * @global $bp_message The message text
 * @global $bp_message_type The type of message (error/success)
 * @uses setcookie() Sets a cookie value for the user.
 */
function bp_core_setup_message() {
	global $bp;

	if ( empty( $bp->template_message ) && isset( $_COOKIE['bp-message'] ) )
		$bp->template_message = $_COOKIE['bp-message'];

	if ( empty( $bp->template_message_type ) && isset( $_COOKIE['bp-message-type'] ) )
		$bp->template_message_type = $_COOKIE['bp-message-type'];

	add_action( 'template_notices', 'bp_core_render_message' );

	@setcookie( 'bp-message',      false, time() - 1000, COOKIEPATH );
	@setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH );
}
add_action( 'bp_actions', 'bp_core_setup_message' );

/**
 * Renders a feedback message (either error or success message) to the theme template.
 * The hook action 'template_notices' is used to call this function, it is not called directly.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_render_message() {
	global $bp;

	if ( isset( $bp->template_message ) && $bp->template_message ) :
		$type = ( 'success' == $bp->template_message_type ) ? 'updated' : 'error'; ?>

		<div id="message" class="<?php echo $type; ?>">
			<p><?php echo stripslashes( esc_attr( $bp->template_message ) ); ?></p>
		</div>

	<?php

		do_action( 'bp_core_render_message' );

	endif;
}

/**
 * Format numbers the BuddyPress way
 *
 * @param str $number
 * @param bool $decimals
 * @return str
 */
function bp_core_number_format( $number, $decimals = false ) {
	// Check we actually have a number first.
	if ( empty( $number ) )
		return $number;

	return apply_filters( 'bp_core_number_format', number_format( $number, $decimals ), $number, $decimals );
}

/**
 * Based on function created by Dunstan Orchard - http://1976design.com
 *
 * This function will return an English representation of the time elapsed
 * since a given date.
 * eg: 2 hours and 50 minutes
 * eg: 4 days
 * eg: 4 weeks and 6 days
 *
 * @package BuddyPress Core
 * @param $older_date int Unix timestamp of date you want to calculate the time since for
 * @param $newer_date int Unix timestamp of date to compare older date to. Default false (current time).
 * @return str The time since.
 */
function bp_core_time_since( $older_date, $newer_date = false ) {

	// array of time period chunks
	$chunks = array(
		array( 60 * 60 * 24 * 365 , __( 'year', 'buddypress' ), __( 'years', 'buddypress' ) ),
		array( 60 * 60 * 24 * 30 , __( 'month', 'buddypress' ), __( 'months', 'buddypress' ) ),
		array( 60 * 60 * 24 * 7, __( 'week', 'buddypress' ), __( 'weeks', 'buddypress' ) ),
		array( 60 * 60 * 24 , __( 'day', 'buddypress' ), __( 'days', 'buddypress' ) ),
		array( 60 * 60 , __( 'hour', 'buddypress' ), __( 'hours', 'buddypress' ) ),
		array( 60 , __( 'minute', 'buddypress' ), __( 'minutes', 'buddypress' ) ),
		array( 1, __( 'second', 'buddypress' ), __( 'seconds', 'buddypress' ) )
	);

	if ( !empty( $older_date ) && !is_numeric( $older_date ) ) {
		$time_chunks = explode( ':', str_replace( ' ', ':', $older_date ) );
		$date_chunks = explode( '-', str_replace( ' ', '-', $older_date ) );
		$older_date  = gmmktime( (int)$time_chunks[1], (int)$time_chunks[2], (int)$time_chunks[3], (int)$date_chunks[1], (int)$date_chunks[2], (int)$date_chunks[0] );
	}

	/**
	 * $newer_date will equal false if we want to know the time elapsed between
	 * a date and the current time. $newer_date will have a value if we want to
	 * work out time elapsed between two known dates.
	 */
	$newer_date = ( !$newer_date ) ? strtotime( bp_core_current_time() ) : $newer_date;

	// Difference in seconds
	$since = $newer_date - $older_date;

	// Something went wrong with date calculation and we ended up with a negative date.
	if ( 0 > $since )
		return __( 'sometime', 'buddypress' );

	/**
	 * We only want to output two chunks of time here, eg:
	 * x years, xx months
	 * x days, xx hours
	 * so there's only two bits of calculation below:
	 */

	// Step one: the first chunk
	for ( $i = 0, $j = count($chunks); $i < $j; $i++) {
		$seconds = $chunks[$i][0];

		// Finding the biggest chunk (if the chunk fits, break)
		if ( ( $count = floor($since / $seconds) ) != 0 )
			break;
	}

	// If $i iterates all the way to $j, then the event happened 0 seconds ago
	if ( !isset( $chunks[$i] ) )
		return '0 ' . __( 'seconds', 'buddypress' );

	// Set output var
	$output = ( 1 == $count ) ? '1 '. $chunks[$i][1] : $count . ' ' . $chunks[$i][2];

	// Step two: the second chunk
	if ( $i + 2 < $j ) {
		$seconds2 = $chunks[$i + 1][0];
		$name2 = $chunks[$i + 1][1];

		if ( ( $count2 = floor( ( $since - ( $seconds * $count ) ) / $seconds2 ) ) != 0 ) {
			// Add to output var
			$output .= ( 1 == $count2 ) ? _x( ',', 'Separator in time since', 'buddypress' ) . ' 1 '. $chunks[$i + 1][1] : _x( ',', 'Separator in time since', 'buddypress' ) . ' ' . $count2 . ' ' . $chunks[$i + 1][2];
		}
	}

	if ( !(int)trim( $output ) )
		$output = '0 ' . __( 'seconds', 'buddypress' );

	return $output;
}

/**
 * Record user activity to the database. Many functions use a "last active" feature to
 * show the length of time since the user was last active.
 * This function will update that time as a usermeta setting for the user every 5 minutes.
 *
 * @package BuddyPress Core
 * @global $userdata WordPress user data for the current logged in user.
 * @uses update_user_meta() WordPress function to update user metadata in the usermeta table.
 */
function bp_core_record_activity() {
	global $bp;

	if ( !is_user_logged_in() )
		return false;

	$activity = get_user_meta( $bp->loggedin_user->id, 'last_activity', true );

	if ( !is_numeric( $activity ) )
		$activity = strtotime( $activity );

	// Get current time
	$current_time = bp_core_current_time();

	if ( empty( $activity ) || strtotime( $current_time ) >= strtotime( '+5 minutes', $activity ) )
		update_user_meta( $bp->loggedin_user->id, 'last_activity', $current_time );
}
add_action( 'wp_head', 'bp_core_record_activity' );

/**
 * Formats last activity based on time since date given.
 *
 * @package BuddyPress Core
 * @param last_activity_date The date of last activity.
 * @param $before The text to prepend to the activity time since figure.
 * @param $after The text to append to the activity time since figure.
 * @uses bp_core_time_since() This function will return an English representation of the time elapsed.
 */
function bp_core_get_last_activity( $last_activity_date, $string ) {
	if ( !$last_activity_date || empty( $last_activity_date ) )
		$last_active = __( 'not recently active', 'buddypress' );
	else
		$last_active = sprintf( $string, bp_core_time_since( $last_activity_date ) );

	return apply_filters( 'bp_core_get_last_activity', $last_active, $last_activity_date, $string );
}

/**
 * Get the path of of the current site.
 *
 * @package BuddyPress Core
 *
 * @global $bp $bp
 * @global object $current_site
 * @return string
 */
function bp_core_get_site_path() {
	global $bp, $current_site;

	if ( is_multisite() )
		$site_path = $current_site->path;
	else {
		$site_path = (array) explode( '/', home_url() );

		if ( count( $site_path ) < 2 )
			$site_path = '/';
		else {
			// Unset the first three segments (http(s)://domain.com part)
			unset( $site_path[0] );
			unset( $site_path[1] );
			unset( $site_path[2] );

			if ( !count( $site_path ) )
				$site_path = '/';
			else
				$site_path = '/' . implode( '/', $site_path ) . '/';
		}
	}

	return apply_filters( 'bp_core_get_site_path', $site_path );
}

/**
 * Performs a status safe wp_redirect() that is compatible with bp_catch_uri()
 *
 * @package BuddyPress Core
 * @global $bp_no_status_set Makes sure that there are no conflicts with status_header() called in bp_core_do_catch_uri()
 * @uses get_themes()
 * @return An array containing all of the themes.
 */
function bp_core_redirect( $location, $status = 302 ) {
	global $bp_no_status_set;

	// Make sure we don't call status_header() in bp_core_do_catch_uri()
	// as this conflicts with wp_redirect()
	$bp_no_status_set = true;

	wp_redirect( $location, $status );
	die;
}

/**
 * Returns the referrer URL without the http(s)://
 *
 * @package BuddyPress Core
 * @return The referrer URL
 */
function bp_core_referrer() {
	$referer = explode( '/', wp_get_referer() );
	unset( $referer[0], $referer[1], $referer[2] );
	return implode( '/', $referer );
}

/**
 * Adds illegal names to WP so that root components will not conflict with
 * blog names on a subdirectory installation.
 *
 * For example, it would stop someone creating a blog with the slug "groups".
 */
function bp_core_add_illegal_names() {
	update_site_option( 'illegal_names', get_site_option( 'illegal_names' ), array() );
}

/**
 * A javascript free implementation of the search functions in BuddyPress
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @param string $slug The slug to redirect to for searching.
 */
function bp_core_action_search_site( $slug = '' ) {
	global $bp;

	if ( BP_SEARCH_SLUG != $bp->current_component )
		return;

	if ( empty( $_POST['search-terms'] ) ) {
		bp_core_redirect( bp_get_root_domain() );
		return;
	}

	$search_terms = stripslashes( $_POST['search-terms'] );
	$search_which = !empty( $_POST['search-which'] ) ? $_POST['search-which'] : '';
	$query_string = '/?s=';

	if ( empty( $slug ) ) {
		switch ( $search_which ) {
			case 'blogs':
				$slug = bp_is_active( 'blogs' )  ? $bp->blogs->root_slug  : '';
				break;

			case 'forums':
				$slug = bp_is_active( 'forums' ) ? $bp->forums->root_slug : '';
				$query_string = '/?fs=';
				break;

			case 'groups':
				$slug = bp_is_active( 'groups' ) ? $bp->groups->root_slug : '';
				break;

			case 'members':
			default:
				$slug = $bp->members->root_slug;
				break;
		}

		if ( empty( $slug ) ) {
			bp_core_redirect( bp_get_root_domain() );
			return;
		}
	}

	bp_core_redirect( apply_filters( 'bp_core_search_site', home_url( $slug . $query_string . urlencode( $search_terms ) ), $search_terms ) );
}
add_action( 'bp_init', 'bp_core_action_search_site', 7 );

/**
 * Prints the generation time in the footer of the site.
 *
 * @package BuddyPress Core
 */
function bp_core_print_generation_time() {
?>

<!-- Generated in <?php timer_stop(1); ?> seconds. (<?php echo get_num_queries(); ?> q) -->

	<?php
}
add_action( 'wp_footer', 'bp_core_print_generation_time' );

/**
 * A better version of add_admin_menu_page() that allows positioning of menus.
 *
 * @package BuddyPress Core
 */
function bp_core_add_admin_menu_page( $args = '' ) {
	global $menu, $admin_page_hooks, $_registered_pages;

	$defaults = array(
		'page_title'   => '',
		'menu_title'   => '',
		'capability'   => 'manage_options',
		'file'         => false,
		'function'     => false,
		'icon_url'     => false,
		'position'     => 100
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$file                    = plugin_basename( $file );
	$admin_page_hooks[$file] = sanitize_title( $menu_title );
	$hookname                = get_plugin_page_hookname( $file, '' );

	if (!empty ( $function ) && !empty ( $hookname ))
		add_action( $hookname, $function );

	if ( empty( $icon_url ) )
		$icon_url = 'images/generic.png';
	elseif ( is_ssl() && 0 === strpos( $icon_url, 'http://' ) )
		$icon_url = 'https://' . substr( $icon_url, 7 );

	do {
		$position++;
	} while ( !empty( $menu[$position] ) );

	$menu[$position] = array ( $menu_title, $capability, $file, $page_title, 'menu-top ' . $hookname, $hookname, $icon_url );

	$_registered_pages[$hookname] = true;

	return $hookname;
}

/**
 * Load the buddypress translation file for current language
 *
 * @package BuddyPress Core
 */
function bp_core_load_buddypress_textdomain() {
	$locale        = apply_filters( 'buddypress_locale', get_locale() );
	$mofile        = sprintf( 'buddypress-%s.mo', $locale );
	$mofile_global = WP_LANG_DIR . '/' . $mofile;
	$mofile_local  = BP_PLUGIN_DIR . '/bp-languages/' . $mofile;

	if ( file_exists( $mofile_global ) )
		return load_textdomain( 'buddypress', $mofile_global );
	elseif ( file_exists( $mofile_local ) )
		return load_textdomain( 'buddypress', $mofile_local );
	else
		return false;
}
add_action ( 'bp_init', 'bp_core_load_buddypress_textdomain', 2 );

function bp_core_add_ajax_hook() {
	// Theme only, we already have the wp_ajax_ hook firing in wp-admin
	if ( !defined( 'WP_ADMIN' ) && isset( $_REQUEST['action'] ) )
		do_action( 'wp_ajax_' . $_REQUEST['action'] );
}
add_action( 'bp_init', 'bp_core_add_ajax_hook' );

/**
 * When switching from single to multisite we need to copy blog options to
 * site options.
 *
 * @package BuddyPress Core
 */
function bp_core_activate_site_options( $keys = array() ) {
	global $bp;

	if ( !empty( $keys ) && is_array( $keys ) ) {
		$errors = false;

		foreach ( $keys as $key => $default ) {
			if ( empty( $bp->site_options[ $key ] ) ) {
				$bp->site_options[ $key ] = get_blog_option( BP_ROOT_BLOG, $key, $default );

				if ( !update_site_option( $key, $bp->site_options[ $key ] ) )
					$errors = true;
			}
		}

		if ( empty( $errors ) )
			return true;
	}

	return false;
}

/**
 * BuddyPress uses site options to store configuration settings. Many of these settings are needed
 * at run time. Instead of fetching them all and adding many initial queries to each page load, let's fetch
 * them all in one go.
 *
 * @package BuddyPress Core
 */
function bp_core_get_site_options() {
	global $bp, $wpdb;

	// These options come from the options table in WP single, and sitemeta in MS
	$site_options = apply_filters( 'bp_core_site_options', array(
		'bp-deactivated-components',
		'bp-blogs-first-install',
		'bp-disable-blog-forum-comments',
		'bp-xprofile-base-group-name',
		'bp-xprofile-fullname-field-name',
		'bp-disable-profile-sync',
		'bp-disable-avatar-uploads',
		'bp-disable-account-deletion',
		'bp-disable-forum-directory',
		'bp-disable-blogforum-comments',
		'bb-config-location',
		'hide-loggedout-adminbar',

		// Useful WordPress settings used often
		'tags_blog_id',
		'registration',
		'fileupload_maxk'
	) );

	// These options always come from the options table of BP_ROOT_BLOG
	$root_blog_options = apply_filters( 'bp_core_root_blog_options', array(
		'avatar_default'
	) );

	$meta_keys = "'" . implode( "','", (array)$site_options ) ."'";

	if ( is_multisite() )
		$site_meta = $wpdb->get_results( "SELECT meta_key AS name, meta_value AS value FROM {$wpdb->sitemeta} WHERE meta_key IN ({$meta_keys}) AND site_id = {$wpdb->siteid}" );
	else
		$site_meta = $wpdb->get_results( "SELECT option_name AS name, option_value AS value FROM {$wpdb->options} WHERE option_name IN ({$meta_keys})" );

	$root_blog_meta_keys  = "'" . implode( "','", (array)$root_blog_options ) ."'";
	$root_blog_meta_table = $wpdb->get_blog_prefix( BP_ROOT_BLOG ) . 'options';
	$root_blog_meta       = $wpdb->get_results( $wpdb->prepare( "SELECT option_name AS name, option_value AS value FROM {$root_blog_meta_table} WHERE option_name IN ({$root_blog_meta_keys})" ) );
	$site_options         = array();

	foreach( array( $site_meta, $root_blog_meta ) as $meta ) {
		if ( !empty( $meta ) ) {
			foreach( (array)$meta as $meta_item ) {
				$site_options[$meta_item->name] = $meta_item->value;
			}
		}
	}
	return apply_filters( 'bp_core_get_site_options', $site_options );
}

?>
