<?php

/**
 * BuddyPress Common Functions
 *
 * @package BuddyPress
 * @subpackage Functions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Versions ******************************************************************/

/**
 * Output the BuddyPress version
 *
 * @since BuddyPress (1.6)
 * @uses bp_get_version() To get the BuddyPress version
 */
function bp_version() {
	echo bp_get_version();
}
	/**
	 * Return the BuddyPress version
	 *
	 * @since BuddyPress (1.6)
	 * @return string The BuddyPress version
	 */
	function bp_get_version() {
		return buddypress()->version;
	}

/**
 * Output the BuddyPress database version
 *
 * @since BuddyPress (1.6)
 * @uses bp_get_db_version() To get the BuddyPress version
 */
function bp_db_version() {
	echo bp_get_db_version();
}
	/**
	 * Return the BuddyPress database version
	 *
	 * @since BuddyPress (1.6)
	 * @return string The BuddyPress version
	 */
	function bp_get_db_version() {
		return buddypress()->db_version;
	}

/**
 * Output the BuddyPress database version
 *
 * @since BuddyPress (1.6)
 * @uses bp_get_db_version_raw() To get the current BuddyPress version
 */
function bp_db_version_raw() {
	echo bp_get_db_version_raw();
}
	/**
	 * Return the BuddyPress database version
	 *
	 * @since BuddyPress (1.6)
	 * @return string The BuddyPress version direct from the database
	 */
	function bp_get_db_version_raw() {
		$bp     = buddypress();
		return !empty( $bp->db_version_raw ) ? $bp->db_version_raw : 0;
	}

/** Functions *****************************************************************/

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
 * @package BuddyPress Core
 * @since BuddyPress (1.5)
 */
function bp_core_get_directory_page_ids() {
	$page_ids = bp_get_option( 'bp-pages' );

	// Ensure that empty indexes are unset. Should only matter in edge cases
	if ( !empty( $page_ids ) && is_array( $page_ids ) ) {
		foreach( (array) $page_ids as $component_name => $page_id ) {
			if ( empty( $component_name ) || empty( $page_id ) ) {
				unset( $page_ids[$component_name] );
			}
		}
	}

	return apply_filters( 'bp_core_get_directory_page_ids', $page_ids );
}

/**
 * Stores BP pages in the meta table, depending on setup
 *
 * bp-pages data is stored in site_options (falls back to options on non-MS), in an array keyed by
 * blog_id. This allows you to change your bp_get_root_blog_id() and go through the setup process again.
 *
 * @package BuddyPress Core
 * @since BuddyPress (1.5)
 *
 * @param array $blog_page_ids The IDs of the WP pages corresponding to BP component directories
 */
function bp_core_update_directory_page_ids( $blog_page_ids ) {
	bp_update_option( 'bp-pages', $blog_page_ids );
}

/**
 * Get bp-pages names and slugs
 *
 * @package BuddyPress Core
 * @since BuddyPress (1.5)
 *
 * @return obj $pages Page names, IDs, and slugs
 */
function bp_core_get_directory_pages() {
	global $wpdb;

	// Set pages as standard class
	$pages = new stdClass;

	// Get pages and IDs
	$page_ids = bp_core_get_directory_page_ids();
	if ( !empty( $page_ids ) ) {

		// Always get page data from the root blog, except on multiblog mode, when it comes
		// from the current blog
		$posts_table_name = bp_is_multiblog_mode() ? $wpdb->posts : $wpdb->get_blog_prefix( bp_get_root_blog_id() ) . 'posts';
		$page_ids_sql     = implode( ',', wp_parse_id_list( $page_ids ) );
		$page_names       = $wpdb->get_results( "SELECT ID, post_name, post_parent, post_title FROM {$posts_table_name} WHERE ID IN ({$page_ids_sql}) AND post_status = 'publish' " );

		foreach ( (array) $page_ids as $component_id => $page_id ) {
			foreach ( (array) $page_names as $page_name ) {
				if ( $page_name->ID == $page_id ) {
					if ( !isset( $pages->{$component_id} ) || !is_object( $pages->{$component_id} ) ) {
						$pages->{$component_id} = new stdClass;
					}

					$pages->{$component_id}->name  = $page_name->post_name;
					$pages->{$component_id}->id    = $page_name->ID;
					$pages->{$component_id}->title = $page_name->post_title;
					$slug[]                        = $page_name->post_name;

					// Get the slug
					while ( $page_name->post_parent != 0 ) {
						$parent                 = $wpdb->get_results( $wpdb->prepare( "SELECT post_name, post_parent FROM {$posts_table_name} WHERE ID = %d", $page_name->post_parent ) );
						$slug[]                 = $parent[0]->post_name;
						$page_name->post_parent = $parent[0]->post_parent;
					}

					$pages->{$component_id}->slug = implode( '/', array_reverse( (array) $slug ) );
				}

				unset( $slug );
			}
		}
	}

	return apply_filters( 'bp_core_get_directory_pages', $pages );
}

/**
 * Add the pages for the component mapping. These are most often used by components with directories (e.g. groups, members).
 *
 * @param array $default_components Optional components to create pages for
 * @param string $existing 'delete' if you want to delete existing page
 *   mappings and replace with new ones. Otherwise existing page mappings
 *   are kept, and the gaps filled in with new pages
 * @since BuddyPress (1.7)
 */
function bp_core_add_page_mappings( $components, $existing = 'keep' ) {

	// Make sure that the pages are created on the root blog no matter which Dashboard the setup is being run on
	if ( ! bp_is_root_blog() )
		switch_to_blog( bp_get_root_blog_id() );

	$pages = bp_core_get_directory_page_ids();

	// Delete any existing pages
	if ( 'delete' == $existing ) {
		foreach ( (array) $pages as $page_id ) {
			wp_delete_post( $page_id, true );
		}

		$pages = array();
	}

	$page_titles = array(
		'activity' => _x( 'Activity', 'Page title for the Activity directory.', 'buddypress' ),
		'groups'   => _x( 'Groups', 'Page title for the Groups directory.', 'buddypress' ),
		'sites'    => _x( 'Sites', 'Page title for the Sites directory.', 'buddypress' ),
		'activate' => _x( 'Activate', 'Page title for the user account activation screen.', 'buddypress' ),
		'members'  => _x( 'Members', 'Page title for the Members directory.', 'buddypress' ),
		'register' => _x( 'Register', 'Page title for the user registration screen.', 'buddypress' ),
	);

	$pages_to_create = array();
	foreach ( array_keys( $components ) as $component_name ) {
		if ( ! isset( $pages[ $component_name ] ) && isset( $page_titles[ $component_name ] ) ) {
			$pages_to_create[ $component_name ] = $page_titles[ $component_name ];
		}
	}

	// Register and Activate are not components, but need pages when
	// registration is enabled
	if ( bp_get_signup_allowed() ) {
		foreach ( array( 'register', 'activate' ) as $slug ) {
			if ( ! isset( $pages[ $slug ] ) ) {
				$pages_to_create[ $slug ] = $page_titles[ $slug ];
			}
		}
	}

	// No need for a Sites directory unless we're on multisite
	if ( ! is_multisite() && isset( $pages_to_create['sites'] ) ) {
		unset( $pages_to_create['sites'] );
	}

	// Members must always have a page, no matter what
	if ( ! isset( $pages['members'] ) && ! isset( $pages_to_create['members'] ) ) {
		$pages_to_create['members'] = $page_titles['members'];
	}

	// Create the pages
	foreach ( $pages_to_create as $component_name => $page_name ) {
		$pages[ $component_name ] = wp_insert_post( array(
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_status'    => 'publish',
			'post_title'     => $page_name,
			'post_type'      => 'page',
		) );
	}

	// Save the page mapping
	bp_update_option( 'bp-pages', $pages );

	// If we had to switch_to_blog, go back to the original site.
	if ( ! bp_is_root_blog() )
		restore_current_blog();
}

/**
 * Creates a default component slug from a WP page root_slug
 *
 * Since 1.5, BP components get their root_slug (the slug used immediately
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
 * @since BuddyPress (1.5)
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
 * Returns the domain for the root blog.
 * eg: http://domain.com/ OR https://domain.com
 *
 * @package BuddyPress Core
 * @uses get_blog_option() WordPress function to fetch blog meta.
 * @return $domain The domain URL for the blog.
 */
function bp_core_get_root_domain() {

	$domain = get_home_url( bp_get_root_blog_id() );

	return apply_filters( 'bp_core_get_root_domain', $domain );
}

/**
 * Get the current GMT time to save into the DB
 *
 * @package BuddyPress Core
 * @since BuddyPress (1.2.6)
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
 * @global BuddyPress $bp The one true BuddyPress instance
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
add_action( 'bp_actions', 'bp_core_setup_message', 5 );

/**
 * Renders a feedback message (either error or success message) to the theme template.
 * The hook action 'template_notices' is used to call this function, it is not called directly.
 *
 * @package BuddyPress Core
 * @global BuddyPress $bp The one true BuddyPress instance
 */
function bp_core_render_message() {
	global $bp;

	if ( !empty( $bp->template_message ) ) :
		$type    = ( 'success' == $bp->template_message_type ) ? 'updated' : 'error';
		$content = apply_filters( 'bp_core_render_message_content', $bp->template_message, $type ); ?>

		<div id="message" class="bp-template-notice <?php echo $type; ?>">

			<?php echo $content; ?>

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

	// Force number to 0 if needed
	if ( empty( $number ) )
		$number = 0;

	return apply_filters( 'bp_core_number_format', number_format_i18n( $number, $decimals ), $number, $decimals );
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
 * @uses apply_filters() Filter 'bp_core_time_since_pre' to bypass BP's calculations
 * @uses apply_filters() Filter 'bp_core_time_since' to modify BP's calculations
 * @param $older_date int Unix timestamp of date you want to calculate the time since for
 * @param $newer_date int Unix timestamp of date to compare older date to. Default false (current time).
 * @return str The time since.
 */
function bp_core_time_since( $older_date, $newer_date = false ) {

	// Use this filter to bypass BuddyPress's time_since calculations
	if ( $pre_value = apply_filters( 'bp_core_time_since_pre', false, $older_date, $newer_date ) ) {
		return $pre_value;
	}

	// Setup the strings
	$unknown_text   = apply_filters( 'bp_core_time_since_unknown_text',   __( 'sometime',  'buddypress' ) );
	$right_now_text = apply_filters( 'bp_core_time_since_right_now_text', __( 'right now', 'buddypress' ) );
	$ago_text       = apply_filters( 'bp_core_time_since_ago_text',       __( '%s ago',    'buddypress' ) );

	// array of time period chunks
	$chunks = array(
		array( 60 * 60 * 24 * 365 , __( 'year',   'buddypress' ), __( 'years',   'buddypress' ) ),
		array( 60 * 60 * 24 * 30 ,  __( 'month',  'buddypress' ), __( 'months',  'buddypress' ) ),
		array( 60 * 60 * 24 * 7,    __( 'week',   'buddypress' ), __( 'weeks',   'buddypress' ) ),
		array( 60 * 60 * 24 ,       __( 'day',    'buddypress' ), __( 'days',    'buddypress' ) ),
		array( 60 * 60 ,            __( 'hour',   'buddypress' ), __( 'hours',   'buddypress' ) ),
		array( 60 ,                 __( 'minute', 'buddypress' ), __( 'minutes', 'buddypress' ) ),
		array( 1,                   __( 'second', 'buddypress' ), __( 'seconds', 'buddypress' ) )
	);

	if ( !empty( $older_date ) && !is_numeric( $older_date ) ) {
		$time_chunks = explode( ':', str_replace( ' ', ':', $older_date ) );
		$date_chunks = explode( '-', str_replace( ' ', '-', $older_date ) );
		$older_date  = gmmktime( (int) $time_chunks[1], (int) $time_chunks[2], (int) $time_chunks[3], (int) $date_chunks[1], (int) $date_chunks[2], (int) $date_chunks[0] );
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
	if ( 0 > $since ) {
		$output = $unknown_text;

	/**
	 * We only want to output two chunks of time here, eg:
	 * x years, xx months
	 * x days, xx hours
	 * so there's only two bits of calculation below:
	 */
	} else {

		// Step one: the first chunk
		for ( $i = 0, $j = count( $chunks ); $i < $j; ++$i ) {
			$seconds = $chunks[$i][0];

			// Finding the biggest chunk (if the chunk fits, break)
			$count = floor( $since / $seconds );
			if ( 0 != $count ) {
				break;
			}
		}

		// If $i iterates all the way to $j, then the event happened 0 seconds ago
		if ( !isset( $chunks[$i] ) ) {
			$output = $right_now_text;

		} else {

			// Set output var
			$output = ( 1 == $count ) ? '1 '. $chunks[$i][1] : $count . ' ' . $chunks[$i][2];

			// Step two: the second chunk
			if ( $i + 2 < $j ) {
				$seconds2 = $chunks[$i + 1][0];
				$name2    = $chunks[$i + 1][1];
				$count2   = floor( ( $since - ( $seconds * $count ) ) / $seconds2 );

				// Add to output var
				if ( 0 != $count2 ) {
					$output .= ( 1 == $count2 ) ? _x( ',', 'Separator in time since', 'buddypress' ) . ' 1 '. $name2 : _x( ',', 'Separator in time since', 'buddypress' ) . ' ' . $count2 . ' ' . $chunks[$i + 1][2];
				}
			}

			// No output, so happened right now
			if ( ! (int) trim( $output ) ) {
				$output = $right_now_text;
			}
		}
	}

	// Append 'ago' to the end of time-since if not 'right now'
	if ( $output != $right_now_text ) {
		$output = sprintf( $ago_text, $output );
	}

	return apply_filters( 'bp_core_time_since', $output, $older_date, $newer_date );
}

/**
 * Record user activity to the database. Many functions use a "last active" feature to
 * show the length of time since the user was last active.
 * This function will update that time as a usermeta setting for the user every 5 minutes.
 *
 * @package BuddyPress Core
 * @global $userdata WordPress user data for the current logged in user.
 * @uses bp_update_user_meta() BP function to update user metadata in the usermeta table.
 */
function bp_core_record_activity() {

	if ( !is_user_logged_in() )
		return false;

	$user_id = bp_loggedin_user_id();

	if ( bp_is_user_inactive( $user_id ) )
		return false;

	$activity = bp_get_user_meta( $user_id, 'last_activity', true );

	if ( !is_numeric( $activity ) )
		$activity = strtotime( $activity );

	// Get current time
	$current_time = bp_core_current_time();

	// Use this action to detect the very first activity for a given member
	if ( empty( $activity ) ) {
		do_action( 'bp_first_activity_for_member', $user_id );
	}

	if ( empty( $activity ) || strtotime( $current_time ) >= strtotime( '+5 minutes', $activity ) )
		bp_update_user_meta( $user_id, 'last_activity', $current_time );
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

	if ( empty( $last_activity_date ) )
		$last_active = __( 'Not recently active', 'buddypress' );
	else
		$last_active = sprintf( $string, bp_core_time_since( $last_activity_date ) );

	return apply_filters( 'bp_core_get_last_activity', $last_active, $last_activity_date, $string );
}

/**
 * Get the path of of the current site.
 *
 * @package BuddyPress Core
 *
 * @global object $current_site
 * @return string
 */
function bp_core_get_site_path() {
	global $current_site;

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
 * @uses wp_safe_redirect()
 */
function bp_core_redirect( $location, $status = 302 ) {

	// On some setups, passing the value of wp_get_referer() may result in an
	// empty value for $location, which results in an error. Ensure that we
	// have a valid URL.
	if ( empty( $location ) )
		$location = bp_get_root_domain();

	// Make sure we don't call status_header() in bp_core_do_catch_uri() as this
	// conflicts with wp_redirect() and wp_safe_redirect().
	buddypress()->no_status_set = true;

	wp_safe_redirect( $location, $status );
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
 * @param string $slug The slug to redirect to for searching.
 */
function bp_core_action_search_site( $slug = '' ) {

	if ( !bp_is_current_component( bp_get_search_slug() ) )
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
			case 'posts':
				$slug = '';
				$var  = '/?s=';

				// If posts aren't displayed on the front page, find the post page's slug.
				if ( 'page' == get_option( 'show_on_front' ) ) {
					$page = get_post( get_option( 'page_for_posts' ) );

					if ( !is_wp_error( $page ) && !empty( $page->post_name ) ) {
						$slug = $page->post_name;
						$var  = '?s=';
					}
				}
				break;

			case 'blogs':
				$slug = bp_is_active( 'blogs' )  ? bp_get_blogs_root_slug()  : '';
				break;

			case 'forums':
				$slug = bp_is_active( 'forums' ) ? bp_get_forums_root_slug() : '';
				$query_string = '/?fs=';
				break;

			case 'groups':
				$slug = bp_is_active( 'groups' ) ? bp_get_groups_root_slug() : '';
				break;

			case 'members':
			default:
				$slug = bp_get_members_root_slug();
				break;
		}

		if ( empty( $slug ) && 'posts' != $search_which ) {
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
 * Load the buddypress translation file for current language
 *
 * @package BuddyPress Core
 */
function bp_core_load_buddypress_textdomain() {
	$locale        = apply_filters( 'buddypress_locale', get_locale() );
	$mofile        = sprintf( 'buddypress-%s.mo', $locale );
	$mofile_global = WP_LANG_DIR . '/' . $mofile;
	$mofile_local  = BP_PLUGIN_DIR . 'bp-languages/' . $mofile;

	if ( file_exists( $mofile_global ) )
		return load_textdomain( 'buddypress', $mofile_global );
	elseif ( file_exists( $mofile_local ) )
		return load_textdomain( 'buddypress', $mofile_local );
	else
		return false;
}
add_action ( 'bp_core_loaded', 'bp_core_load_buddypress_textdomain' );

/**
 * Initializes {@link BP_Embed} after everything is loaded.
 *
 * @global object $bp BuddyPress global settings
 * @package BuddyPress Core
 * @since BuddyPress (1.5)
 */
function bp_embed_init() {
	global $bp;

	if ( empty( $bp->embed ) )
		$bp->embed = new BP_Embed();
}
add_action( 'bp_init', 'bp_embed_init', 9 );

/**
 * This function originally let plugins add support for pages in the root of the install.
 * These root level pages are now handled by actual WordPress pages and this function is now
 * a convenience for compatibility with the new method.
 *
 * @global $bp BuddyPress global settings
 * @param $slug str The slug of the component
 */
function bp_core_add_root_component( $slug ) {
	global $bp;

	if ( empty( $bp->pages ) )
		$bp->pages = bp_core_get_directory_pages();

	$match = false;

	// Check if the slug is registered in the $bp->pages global
	foreach ( (array) $bp->pages as $key => $page ) {
		if ( $key == $slug || $page->slug == $slug )
			$match = true;
	}

	// If there was no match, add a page for this root component
	if ( empty( $match ) ) {
		$bp->add_root[] = $slug;
	}

	// Make sure that this component is registered as requiring a top-level directory
	if ( isset( $bp->{$slug} ) ) {
		$bp->loaded_components[$bp->{$slug}->slug] = $bp->{$slug}->id;
		$bp->{$slug}->has_directory = true;
	}
}

function bp_core_create_root_component_page() {
	global $bp;

	$new_page_ids = array();

	foreach ( (array) $bp->add_root as $slug )
		$new_page_ids[$slug] = wp_insert_post( array( 'comment_status' => 'closed', 'ping_status' => 'closed', 'post_title' => ucwords( $slug ), 'post_status' => 'publish', 'post_type' => 'page' ) );

	$page_ids = array_merge( (array) $new_page_ids, (array) bp_core_get_directory_page_ids() );
	bp_core_update_directory_page_ids( $page_ids );
}

/**
 * Is this the root blog ID?
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @param int $blog_id Optional. Defaults to the current blog id.
 * @return bool $is_root_blog Returns true if this is bp_get_root_blog_id().
 */
function bp_is_root_blog( $blog_id = 0 ) {

	// Assume false
	$is_root_blog = false;

	// Use current blog if no ID is passed
	if ( empty( $blog_id ) )
		$blog_id = get_current_blog_id();

	// Compare to root blog ID
	if ( $blog_id == bp_get_root_blog_id() )
		$is_root_blog = true;

	return (bool) apply_filters( 'bp_is_root_blog', (bool) $is_root_blog );
}

/**
 * Is this bp_get_root_blog_id()?
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return int Return the root site ID
 */
function bp_get_root_blog_id() {
	global $bp;

	return (int) apply_filters( 'bp_get_root_blog_id', (int) $bp->root_blog_id );
}

/**
 * Get the meta_key for a given piece of user metadata
 *
 * BuddyPress stores a number of pieces of userdata in the WordPress central usermeta table. In
 * order to allow plugins to enable multiple instances of BuddyPress on a single WP installation,
 * BP's usermeta keys are filtered with this function, so that they can be altered on the fly.
 *
 * Plugin authors should use BP's _user_meta() functions, which bakes in bp_get_user_meta_key().
 *    $last_active = bp_get_user_meta( $user_id, 'last_activity', true );
 * If you have to use WP's _user_meta() functions for some reason, you should use this function, eg
 *    $last_active = get_user_meta( $user_id, bp_get_user_meta_key( 'last_activity' ), true );
 * If using the WP functions, do not not hardcode your meta keys.
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @uses apply_filters() Filter bp_get_user_meta_key to modify keys individually
 * @param str $key
 * @return str $key
 */
function bp_get_user_meta_key( $key = false ) {
	return apply_filters( 'bp_get_user_meta_key', $key );
}

/**
 * Get a piece of usermeta
 *
 * This is a wrapper for get_user_meta() that allows for easy use of bp_get_user_meta_key(), thereby
 * increasing compatibility with non-standard BP setups.
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_user_meta_key() For a filterable version of the meta key
 * @uses get_user_meta() See get_user_meta() docs for more details on parameters
 * @param int $user_id The id of the user whose meta you're fetching
 * @param string $key The meta key to retrieve.
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
function bp_get_user_meta( $user_id, $key, $single = false ) {
	return get_user_meta( $user_id, bp_get_user_meta_key( $key ), $single );
}

/**
 * Update a piece of usermeta
 *
 * This is a wrapper for update_user_meta() that allows for easy use of bp_get_user_meta_key(),
 * thereby increasing compatibility with non-standard BP setups.
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_user_meta_key() For a filterable version of the meta key
 * @uses update_user_meta() See update_user_meta() docs for more details on parameters
 * @param int $user_id The id of the user whose meta you're setting
 * @param string $key The meta key to set.
 * @param mixed $value Metadata value.
 * @param mixed $prev_value Optional. Previous value to check before removing.
 * @return bool False on failure, true if success.
 */
function bp_update_user_meta( $user_id, $key, $value, $prev_value = '' ) {
	return update_user_meta( $user_id, bp_get_user_meta_key( $key ), $value, $prev_value );
}

/**
 * Delete a piece of usermeta
 *
 * This is a wrapper for delete_user_meta() that allows for easy use of bp_get_user_meta_key(),
 * thereby increasing compatibility with non-standard BP setups.
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_user_meta_key() For a filterable version of the meta key
 * @uses delete_user_meta() See delete_user_meta() docs for more details on parameters
 * @param int $user_id The id of the user whose meta you're deleting
 * @param string $key The meta key to delete.
 * @param mixed $value Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function bp_delete_user_meta( $user_id, $key, $value = '' ) {
	return delete_user_meta( $user_id, bp_get_user_meta_key( $key ), $value );
}

/**
 * Are we running username compatibility mode?
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @uses apply_filters() Filter 'bp_is_username_compatibility_mode' to alter
 * @return bool False when compatibility mode is disabled (default); true when enabled
 */
function bp_is_username_compatibility_mode() {
	return apply_filters( 'bp_is_username_compatibility_mode', defined( 'BP_ENABLE_USERNAME_COMPATIBILITY_MODE' ) && BP_ENABLE_USERNAME_COMPATIBILITY_MODE );
}

/**
 * Are we running multiblog mode?
 *
 * Note that BP_ENABLE_MULTIBLOG is different from (but dependent on) WordPress
 * Multisite. "Multiblog" is BuddyPress setup that allows BuddyPress components
 * to be viewed on every blog on the network, each with their own settings.
 *
 * Thus, instead of having all 'boonebgorges' links go to
 *   http://example.com/members/boonebgorges
 * on the root blog, each blog will have its own version of the same content, eg
 *   http://site2.example.com/members/boonebgorges (for subdomains)
 *   http://example.com/site2/members/boonebgorges (for subdirectories)
 *
 * Multiblog mode is disabled by default, meaning that all BuddyPress content
 * must be viewed on the root blog. It's also recommended not to use the
 * BP_ENABLE_MULTIBLOG constant beyond 1.7, as BuddyPress can now be activated
 * on individual sites.
 *
 * Why would you want to use this? Originally it was intended to allow
 * BuddyPress to live in mu-plugins and be visible on mapped domains. This is
 * a very small use-case with large architectural shortcomings, so do not go
 * down this road unless you specifically need to.
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @uses apply_filters() Filter 'bp_is_multiblog_mode' to alter
 * @return bool False when multiblog mode is disabled (default); true when enabled
 */
function bp_is_multiblog_mode() {

	// Setup some default values
	$retval         = false;
	$is_multisite   = is_multisite();
	$network_active = bp_is_network_activated();
	$is_multiblog   = defined( 'BP_ENABLE_MULTIBLOG' ) && BP_ENABLE_MULTIBLOG;

	// Multisite, Network Activated, and Specifically Multiblog
	if ( $is_multisite && $network_active && $is_multiblog ) {
		$retval = true;

	// Multisite, but not network activated
	} elseif ( $is_multisite && ! $network_active ) {
		$retval = true;
	}

	return apply_filters( 'bp_is_multiblog_mode', $retval );
}

/**
 * Should we use the WP Toolbar?
 *
 * The WP Toolbar, introduced in WP 3.1, is fully supported in BuddyPress as of BP 1.5.
 * For BP 1.6, the WP Toolbar is the default.
 *
 * @return bool False when WP Toolbar support is disabled; true when enabled (default)
 * @since BuddyPress (1.5)
 * @uses apply_filters() Filter 'bp_use_wp_admin_bar' to alter
 */
function bp_use_wp_admin_bar() {
	$use_admin_bar = true;

	// Has the WP Toolbar constant been explicity set?
	if ( defined( 'BP_USE_WP_ADMIN_BAR' ) && ! BP_USE_WP_ADMIN_BAR )
		$use_admin_bar = false;

	// Has the admin chosen to use the BuddyBar during an upgrade?
	elseif ( (bool) bp_get_option( '_bp_force_buddybar', false ) )
		$use_admin_bar = false;

	return apply_filters( 'bp_use_wp_admin_bar', $use_admin_bar );
}

/**
 * A utility for parsing individual function arguments into an array.
 *
 * The purpose of this function is to help with backward compatibility in cases where
 *
 *   function foo( $bar = 1, $baz = false, $barry = array(), $blip = false ) { // ...
 *
 * is deprecated in favor of
 *
 *   function foo( $args = array() ) {
 *       $defaults = array(
 *           'bar'  => 1,
 *           'arg2' => false,
 *           'arg3' => array(),
 *           'arg4' => false,
 *       );
 *       $r = wp_parse_args( $args, $defaults ); // ...
 *
 * The first argument, $old_args_keys, is an array that matches the parameter positions (keys) to
 * the new $args keys (values):
 *
 *   $old_args_keys = array(
 *       0 => 'bar', // because $bar was the 0th parameter for foo()
 *       1 => 'baz', // because $baz was the 1st parameter for foo()
 *       2 => 'barry', // etc
 *       3 => 'blip'
 *   );
 *
 * For the second argument, $func_args, you should just pass the value of func_get_args().
 *
 * @since BuddyPress (1.6)
 * @param array $old_args_keys
 * @param array $func_args
 * @return array $new_args
 */
function bp_core_parse_args_array( $old_args_keys, $func_args ) {
	$new_args = array();

	foreach( $old_args_keys as $arg_num => $arg_key ) {
		if ( isset( $func_args[$arg_num] ) ) {
			$new_args[$arg_key] = $func_args[$arg_num];
		}
	}

	return $new_args;
}

/** Embeds ********************************************************************/

/**
 * Are oembeds allowed in activity items?
 *
 * @return bool False when activity embed support is disabled; true when enabled (default)
 * @since BuddyPress (1.5)
 */
function bp_use_embed_in_activity() {
	return apply_filters( 'bp_use_oembed_in_activity', !defined( 'BP_EMBED_DISABLE_ACTIVITY' ) || !BP_EMBED_DISABLE_ACTIVITY );
}

/**
 * Are oembeds allwoed in activity replies?
 *
 * @return bool False when activity replies embed support is disabled; true when enabled (default)
 * @since BuddyPress (1.5)
 */
function bp_use_embed_in_activity_replies() {
	return apply_filters( 'bp_use_embed_in_activity_replies', !defined( 'BP_EMBED_DISABLE_ACTIVITY_REPLIES' ) || !BP_EMBED_DISABLE_ACTIVITY_REPLIES );
}

/**
 * Are oembeds allowed in forum posts?
 *
 * @return bool False when form post embed support is disabled; true when enabled (default)
 * @since BuddyPress (1.5)
 */
function bp_use_embed_in_forum_posts() {
	return apply_filters( 'bp_use_embed_in_forum_posts', !defined( 'BP_EMBED_DISABLE_FORUM_POSTS' ) || !BP_EMBED_DISABLE_FORUM_POSTS );
}

/**
 * Are oembeds allowed in private messages?
 *
 * @return bool False when form post embed support is disabled; true when enabled (default)
 * @since BuddyPress (1.5)
 */
function bp_use_embed_in_private_messages() {
	return apply_filters( 'bp_use_embed_in_private_messages', !defined( 'BP_EMBED_DISABLE_PRIVATE_MESSAGES' ) || !BP_EMBED_DISABLE_PRIVATE_MESSAGES );
}

/** Admin *********************************************************************/

/**
 * Output the correct URL based on BuddyPress and WordPress configuration
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @param string $path
 * @param string $scheme
 *
 * @uses bp_get_admin_url()
 */
function bp_admin_url( $path = '', $scheme = 'admin' ) {
	echo bp_get_admin_url( $path, $scheme );
}
	/**
	 * Return the correct URL based on BuddyPress and WordPress configuration
	 *
	 * @package BuddyPress
	 * @since BuddyPress (1.5)
	 *
	 * @param string $path
	 * @param string $scheme
	 *
	 * @uses bp_core_do_network_admin()
	 * @uses network_admin_url()
	 * @uses admin_url()
	 */
	function bp_get_admin_url( $path = '', $scheme = 'admin' ) {

		// Links belong in network admin
		if ( bp_core_do_network_admin() ) {
			$url = network_admin_url( $path, $scheme );

		// Links belong in site admin
		} else {
			$url = admin_url( $path, $scheme );
		}

		return $url;
	}

/**
 * Should BuddyPress appear in network admin, or site admin?
 *
 * Because BuddyPress can be installed in multiple ways and with multiple
 * configurations, we need to check a few things to be confident about where
 * to hook into certain areas of WordPress's admin.
 *
 * This function defaults to BuddyPress being network activated.
 * @since BuddyPress (1.5)
 *
 * @uses bp_is_network_activated()
 * @uses bp_is_multiblog_mode()
 * @return boolean
 */
function bp_core_do_network_admin() {

	// Default
	$retval = bp_is_network_activated();

	if ( bp_is_multiblog_mode() )
		$retval = false;

	return (bool) apply_filters( 'bp_core_do_network_admin', $retval );
}

function bp_core_admin_hook() {
	$hook = bp_core_do_network_admin() ? 'network_admin_menu' : 'admin_menu';

	return apply_filters( 'bp_core_admin_hook', $hook );
}

/**
 * Is BuddyPress active at the network level for this network?
 *
 * Used to determine admin menu placement, and where settings and options are
 * stored. If you're being *really* clever and manually pulling BuddyPress in
 * with an mu-plugin or some other method, you'll want to
 *
 * @since BuddyPress (1.7)
 * @return boolean
 */
function bp_is_network_activated() {

	// Default to is_multisite()
	$retval  = is_multisite();

	// Check the sitewide plugins array
	$base    = buddypress()->basename;
	$plugins = get_site_option( 'active_sitewide_plugins' );

	// Override is_multisite() if not network activated
	if ( ! is_array( $plugins ) || ! isset( $plugins[$base] ) )
		$retval = false;

	return (bool) apply_filters( 'bp_is_network_activated', $retval );
}

/** Global Manipulators *******************************************************/

/**
 * Set the $bp->is_directory global
 *
 * @global BuddyPress $bp The one true BuddyPress instance
 * @param bool $is_directory
 * @param str $component
 */
function bp_update_is_directory( $is_directory = false, $component = '' ) {
	global $bp;

	if ( empty( $component ) )
		$component = bp_current_component();

	$bp->is_directory = apply_filters( 'bp_update_is_directory', $is_directory, $component );
}

/**
 * Set the $bp->is_item_admin global
 *
 * @global BuddyPress $bp The one true BuddyPress instance
 * @param bool $is_item_admin
 * @param str $component
 */
function bp_update_is_item_admin( $is_item_admin = false, $component = '' ) {
	global $bp;

	if ( empty( $component ) )
		$component = bp_current_component();

	$bp->is_item_admin = apply_filters( 'bp_update_is_item_admin', $is_item_admin, $component );
}

/**
 * Set the $bp->is_item_mod global
 *
 * @global BuddyPress $bp The one true BuddyPress instance
 * @param bool $is_item_mod
 * @param str $component
 */
function bp_update_is_item_mod( $is_item_mod = false, $component = '' ) {
	global $bp;

	if ( empty( $component ) )
		$component = bp_current_component();

	$bp->is_item_mod = apply_filters( 'bp_update_is_item_mod', $is_item_mod, $component );
}

/**
 * Trigger a 404
 *
 * @global BuddyPress $bp The one true BuddyPress instance
 * @global WP_Query $wp_query WordPress query object
 * @param string $redirect If 'remove_canonical_direct', remove WordPress' "helpful" redirect_canonical action.
 * @since BuddyPress (1.5)
 */
function bp_do_404( $redirect = 'remove_canonical_direct' ) {
	global $wp_query;

	do_action( 'bp_do_404', $redirect );

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();

	if ( 'remove_canonical_direct' == $redirect )
		remove_action( 'template_redirect', 'redirect_canonical' );
}

/** Nonces ********************************************************************/

/**
 * Makes sure the user requested an action from another page on this site.
 *
 * To avoid security exploits within the theme.
 *
 * @since BuddyPress (1.6)
 *
 * @uses do_action() Calls 'bp_verify_nonce_request' on $action.
 * @param string $action Action nonce
 * @param string $query_arg where to look for nonce in $_REQUEST
 */
function bp_verify_nonce_request( $action = '', $query_arg = '_wpnonce' ) {

	// Get the home URL
	$home_url = strtolower( home_url() );

	$requested_url = bp_get_requested_url();

	// Check the nonce
	$result = isset( $_REQUEST[$query_arg] ) ? wp_verify_nonce( $_REQUEST[$query_arg], $action ) : false;

	// Nonce check failed
	if ( empty( $result ) || empty( $action ) || ( strpos( $requested_url, $home_url ) !== 0 ) )
		$result = false;

	// Do extra things
	do_action( 'bp_verify_nonce_request', $action, $result );

	return $result;
}
