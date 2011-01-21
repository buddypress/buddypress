<?php

/**
 * bp_get_options_nav()
 * TEMPLATE TAG
 *
 * Uses the $bp->bp_options_nav global to render out the sub navigation for the current component.
 * Each component adds to its sub navigation array within its own [component_name]_setup_nav() function.
 *
 * This sub navigation array is the secondary level navigation, so for profile it contains:
 *      [Public, Edit Profile, Change Avatar]
 *
 * The function will also analyze the current action for the current component to determine whether
 * or not to highlight a particular sub nav item.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_get_user_nav() Renders the navigation for a profile of a currently viewed user.
 */
function bp_get_options_nav() {
	global $bp;

	// If we are looking at a member profile, then the we can use the current component as an
	// index. Otherwise we need to use the component's root_slug
	$component_index = !empty( $bp->displayed_user ) ? $bp->current_component : bp_get_root_slug( $bp->current_component );

	if ( count( $bp->bp_options_nav[$component_index] ) < 1 )
		return false;

	// Loop through each navigation item
	foreach ( (array)$bp->bp_options_nav[$component_index] as $subnav_item ) {
		if ( !$subnav_item['user_has_access'] )
			continue;

		// If the current action or an action variable matches the nav item id, then add a highlight CSS class.
		if ( $subnav_item['slug'] == $bp->current_action ) {
			$selected = ' class="current selected"';
		} else {
			$selected = '';
		}

		// echo out the final list item
		echo apply_filters( 'bp_get_options_nav_' . $subnav_item['css_id'], '<li id="' . $subnav_item['css_id'] . '-personal-li" ' . $selected . '><a id="' . $subnav_item['css_id'] . '" href="' . $subnav_item['link'] . '">' . $subnav_item['name'] . '</a></li>', $subnav_item );
	}
}

function bp_get_options_title() {
	global $bp;

	if ( empty( $bp->bp_options_title ) )
		$bp->bp_options_title = __( 'Options', 'buddypress' );

	echo apply_filters( 'bp_get_options_title', esc_attr( $bp->bp_options_title ) );
}


/** AVATAR TEMPLATE TAGS *******************************************************/

/**
 * bp_has_options_avatar()
 * TEMPLATE TAG
 *
 * Check to see if there is an options avatar. An options avatar is an avatar for something
 * like a group, or a friend. Basically an avatar that appears in the sub nav options bar.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_has_options_avatar() {
	global $bp;

	if ( empty( $bp->bp_options_avatar ) )
		return false;

	return true;
}

function bp_get_options_avatar() {
	global $bp;

	echo apply_filters( 'bp_get_options_avatar', $bp->bp_options_avatar );
}

function bp_comment_author_avatar() {
	global $comment;

	if ( function_exists( 'bp_core_fetch_avatar' ) )
		echo apply_filters( 'bp_comment_author_avatar', bp_core_fetch_avatar( array( 'item_id' => $comment->user_id, 'type' => 'thumb' ) ) );
	else if ( function_exists('get_avatar') )
		get_avatar();
}

function bp_post_author_avatar() {
	global $post;

	if ( function_exists( 'bp_core_fetch_avatar' ) )
		echo apply_filters( 'bp_post_author_avatar', bp_core_fetch_avatar( array( 'item_id' => $post->post_author, 'type' => 'thumb' ) ) );
	else if ( function_exists('get_avatar') )
		get_avatar();
}

function bp_avatar_admin_step() {
	echo bp_get_avatar_admin_step();
}
	function bp_get_avatar_admin_step() {
		global $bp;

		if ( isset( $bp->avatar_admin->step ) )
			$step = $bp->avatar_admin->step;
		else
			$step = '';

		return apply_filters( 'bp_get_avatar_admin_step', $step );
	}

function bp_avatar_to_crop() {
	echo bp_get_avatar_to_crop();
}
	function bp_get_avatar_to_crop() {
		global $bp;

		if ( isset( $bp->avatar_admin->image->url ) )
			$url = $bp->avatar_admin->image->url;
		else
			$url = '';

		return apply_filters( 'bp_get_avatar_to_crop', $url );
	}

function bp_avatar_to_crop_src() {
	echo bp_get_avatar_to_crop_src();
}
	function bp_get_avatar_to_crop_src() {
		global $bp;

		return apply_filters( 'bp_get_avatar_to_crop_src', str_replace( WP_CONTENT_DIR, '', $bp->avatar_admin->image->dir ) );
	}

function bp_avatar_cropper() {
	global $bp;

	echo '<img id="avatar-to-crop" class="avatar" src="' . $bp->avatar_admin->image . '" />';
}

function bp_site_name() {
	echo apply_filters( 'bp_site_name', get_bloginfo( 'name', 'display' ) );
}

function bp_core_get_wp_profile() {
	global $bp;

	$ud = get_userdata( $bp->displayed_user->id );
?>

<div class="bp-widget wp-profile">
	<h4><?php _e( 'My Profile' ) ?></h4>

	<table class="wp-profile-fields zebra">
		<?php if ( $ud->display_name ) { ?>
		<tr id="wp_displayname">
			<td class="label">
				<?php _e( 'Name', 'buddypress' ) ?>
			</td>
			<td class="data">
				<?php echo $ud->display_name ?>
			</td>
		</tr>
		<?php } ?>
		<?php if ( $ud->user_description ) { ?>
		<tr id="wp_desc">
			<td class="label">
				<?php _e( 'About Me', 'buddypress' ) ?>
			</td>
			<td class="data">
				<?php echo $ud->user_description ?>
			</td>
		</tr>
		<?php } ?>
		<?php if ( $ud->user_url ) { ?>
		<tr id="wp_website">
			<td class="label">
				<?php _e( 'Website', 'buddypress' ) ?>
			</td>
			<td class="data">
				<?php echo make_clickable( $ud->user_url ) ?>
			</td>
		</tr>
		<?php } ?>
		<?php if ( $ud->jabber ) { ?>
		<tr id="wp_jabber">
			<td class="label">
				<?php _e( 'Jabber', 'buddypress' ) ?>
			</td>
			<td class="data">
				<?php echo $ud->jabber ?>
			</td>
		</tr>
		<?php } ?>
		<?php if ( $ud->aim ) { ?>
		<tr id="wp_aim">
			<td class="label">
				<?php _e( 'AOL Messenger', 'buddypress' ) ?>
			</td>
			<td class="data">
				<?php echo $ud->aim ?>
			</td>
		</tr>
		<?php } ?>
		<?php if ( $ud->yim ) { ?>
		<tr id="wp_yim">
			<td class="label">
				<?php _e( 'Yahoo Messenger', 'buddypress' ) ?>
			</td>
			<td class="data">
				<?php echo $ud->yim ?>
			</td>
		</tr>
		<?php } ?>
	</table>
</div>
<?php
}

function bp_get_profile_header() {
	locate_template( array( '/profile/profile-header.php' ), true );
}

function bp_exists( $component_name ) {
	if ( function_exists( $component_name . '_install' ) )
		return true;

	return false;
}

function bp_format_time( $time, $just_date = false, $localize_time = true ) {
	if ( !isset( $time ) || !is_numeric( $time ) )
		return false;

	// Get GMT offset from root blog
	$root_blog_offset = false;
	if ( $localize_time )
		$root_blog_offset = get_blog_option( BP_ROOT_BLOG, 'gmt_offset' );

	// Calculate offset time
	$time_offset = $time + ( $root_blog_offset * 3600 );

	// Current date (January 1, 2010)
	$date = date_i18n( get_option( 'date_format' ), $time_offset );

	// Should we show the time also?
	if ( !$just_date ) {
		// Current time (9:50pm)
		$time = date_i18n( get_option( 'time_format' ), $time_offset );

		// Return string formatted with date and time
		$date = sprintf( __( '%1$s at %2$s', 'buddypress' ), $date, $time );
	}

	return apply_filters( 'bp_format_time', $date );
}

function bp_word_or_name( $youtext, $nametext, $capitalize = true, $echo = true ) {
	global $bp;

	if ( $capitalize )
		$youtext = bp_core_ucfirst($youtext);

	if ( $bp->displayed_user->id == $bp->loggedin_user->id ) {
		if ( $echo )
			echo apply_filters( 'bp_word_or_name', $youtext );
		else
			return apply_filters( 'bp_word_or_name', $youtext );
	} else {
		$fullname = (array)explode( ' ', $bp->displayed_user->fullname );
		$nametext = sprintf( $nametext, $fullname[0] );
		if ( $echo )
			echo apply_filters( 'bp_word_or_name', $nametext );
		else
			return apply_filters( 'bp_word_or_name', $nametext );
	}
}


function bp_get_plugin_sidebar() {
	locate_template( array( 'plugin-sidebar.php' ), true );
}

function bp_page_title() {
	echo bp_get_page_title();
}

function bp_get_page_title() {
	global $bp, $post, $wp_query, $current_blog;

	if ( is_front_page() || ( is_home() && bp_is_page( 'home' ) ) ) {
		$title = __( 'Home', 'buddypress' );

	} else if ( bp_is_blog_page() ) {
		if ( is_single() ) {
			$title = __( 'Blog &#124; ' . $post->post_title, 'buddypress' );
		} else if ( is_category() ) {
			$title = __( 'Blog &#124; Categories &#124; ' . ucwords( $wp_query->query_vars['category_name'] ), 'buddypress' );
		} else if ( is_tag() ) {
			$title = __( 'Blog &#124; Tags &#124; ' . ucwords( $wp_query->query_vars['tag'] ), 'buddypress' );
		} else if ( is_page() ){
			$title = $post->post_title;
		} else
			$title = __( 'Blog', 'buddypress' );

	} else if ( !empty( $bp->displayed_user->fullname ) ) {
 		$title = strip_tags( $bp->displayed_user->fullname . ' &#124; ' . ucwords( $bp->current_component ) );

	} else if ( !empty( $bp->is_single_item ) ) {
		$title = ucwords( $bp->current_component ) . ' &#124; ' . $bp->bp_options_title . ' &#124; ' . $bp->bp_options_nav[$bp->current_component][$bp->current_action]['name'];

	} else if ( !empty( $bp->is_directory ) ) {
		if ( !$bp->current_component )
			$title = sprintf( __( '%s Directory', 'buddypress' ), ucwords( BP_MEMBERS_SLUG ) );
		else
			$title = sprintf( __( '%s Directory', 'buddypress' ), ucwords( $bp->current_component ) );

	} else if ( bp_is_register_page() ) {
		$title = __( 'Create an Account', 'buddypress' );

	} else if ( bp_is_activation_page() ) {
		$title = __( 'Activate your Account', 'buddypress' );

	} else if ( bp_is_group_create() ) {
		$title = __( 'Create a Group', 'buddypress' );

	} else if ( bp_is_create_blog() ) {
		$title = __( 'Create a Blog', 'buddypress' );
	}

	return apply_filters( 'bp_page_title', esc_attr( get_bloginfo( 'name', 'display' ) . ' &#124; ' . $title ), esc_attr( $title ) );
}

function bp_styles() {
	do_action( 'bp_styles' );
	wp_print_styles();
}

/**
 * bp_search_form_available()
 *
 * Only show the search form if there are available objects to search for.
 *
 * @uses function_exists
 * @uses is_multisite()
 * @return bool Filterable result
 */
function bp_search_form_enabled() {
	if ( bp_is_active( 'profile' )
		 || bp_is_active( 'groups' )
		 || ( bp_is_active( 'blogs' ) && is_multisite() )
		 || ( bp_is_active( 'forums' ) && !bp_forum_directory_is_disabled() )
	) {
		$search_enabled = true;
	} else {
		$search_enabled = false;
	}

	return apply_filters( 'bp_search_form_enabled', $search_enabled );
}

function bp_search_form_action() {
	global $bp;

	return apply_filters( 'bp_search_form_action', $bp->root_domain . '/' . BP_SEARCH_SLUG );
}

/**
 * Generates the basic search form as used in BP-Default's header.
 *
 * @global object $bp BuddyPress global settings
 * @return string HTML <select> element
 * @since 1.0
 */
function bp_search_form_type_select() {
	global $bp;

	$options = array();

	if ( bp_is_active( 'profile' ) )
		$options['members'] = __( 'Members', 'buddypress' );

	if ( bp_is_active( 'groups' ) )
		$options['groups'] = __( 'Groups', 'buddypress' );

	if ( bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() && !bp_forum_directory_is_disabled() )
		$options['forums'] = __( 'Forums', 'buddypress' );

	if ( bp_is_active( 'blogs' ) && is_multisite() )
		$options['blogs'] = __( 'Blogs', 'buddypress' );

	// Eventually this won't be needed and a page will be built to integrate all search results.
	$selection_box = '<select name="search-which" id="search-which" style="width: auto">';

	$options = apply_filters( 'bp_search_form_type_select_options', $options );
	foreach( (array)$options as $option_value => $option_title )
		$selection_box .= sprintf( '<option value="%s">%s</option>', $option_value, $option_title );

	$selection_box .= '</select>';

	return apply_filters( 'bp_search_form_type_select', $selection_box );
}

/**
 * Get the default text for the search box for a given component.
 *
 * @global object $bp BuddyPress global settings
 * @return string
 * @since 1.3
 */
function bp_search_default_text() {
	echo bp_get_search_default_text();
}
	function bp_get_search_default_text( $component = false ) {
 		global $bp;

 	        if ( empty( $component ) )
			$component = $bp->current_component;

		$default_text = __( 'Search anything...', 'buddypress' );

		if ( !empty( $bp->default_search_strings[$component] ) ) {
			// Most of the time, $component will be the actual component name
			$default_text = $bp->default_search_strings[$component];
		} else {
			// When the request comes through AJAX, we need to get the component name
			// out of $bp->pages
			if ( !empty( $bp->pages->{$component}->slug ) ) {
				$key = $bp->pages->{$component}->slug;
				if ( !empty( $bp->default_search_strings[$key] ) )
					$default_text = $bp->default_search_strings[$key];
			}
		}

		return apply_filters( 'bp_search_default_text', $default_text, $component );
	}

function bp_search_form() {
	$form = '
		<form action="' . bp_search_form_action() . '" method="post" id="search-form">
			<input type="text" id="search-terms" name="search-terms" value="" />
			' . bp_search_form_type_select() . '

			<input type="submit" name="search-submit" id="search-submit" value="' . __( 'Search', 'buddypress' ) . '" />
			' . wp_nonce_field( 'bp_search_form' ) . '
		</form>
	';

	echo apply_filters( 'bp_search_form', $form );
}

function bp_log_out_link() {
	global $bp;
	if ( function_exists('wp_logout_url') )
		$logout_link = '<a href="' . wp_logout_url( $bp->root_domain ) . '">' . __( 'Log Out', 'buddypress' ) . '</a>';
	else
		$logout_link = '<a href="' . $bp->root_domain . '/wp-login.php?action=logout&amp;redirect_to=' . $bp->root_domain . '">' . __( 'Log Out', 'buddypress' ) . '</a>';

	echo apply_filters( 'bp_logout_link', $logout_link );
}

function bp_custom_profile_boxes() {
	do_action( 'bp_custom_profile_boxes' );
}

function bp_custom_profile_sidebar_boxes() {
	do_action( 'bp_custom_profile_sidebar_boxes' );
}

/**
 * Creates and outputs a button.
 *
 * @param array $args See bp_get_button() for the list of arguments.
 * @see bp_get_button()
 */
function bp_button( $args = '' ) {
	echo bp_get_button( $args );
}
	/**
	 * Creates and returns a button.
	 *
	 * Args:
	 * component: Which component this button is for
	 * must_be_logged_in: Button only appears for logged in users
	 * block_self: Button will not appear when viewing your own profile.
	 * wrapper: div|span|p|li|
	 * wrapper_id: The DOM ID of the button wrapper
	 * wrapper_class: The DOM class of the button wrapper
	 * link_href: The destination link of the button
	 * link_title: Title of the button
	 * link_id: The DOM ID of the button
	 * link_class: The DOM class of the button
	 * link_rel: The DOM rel of the button
	 * link_text: The contents of the button
	 *
	 * @param array $button
	 * @return string
	 * @see bp_add_friend_button()
	 * @see bp_send_public_message_button()
	 * @see bp_send_private_message_button()
	 */
	function bp_get_button( $args = '' ) {
		$button = new BP_Button( $args );
		return apply_filters( 'bp_get_button', $button->contents, $args, $button );
	}

/**
 * bp_create_excerpt()
 *
 * Fakes an excerpt on any content. Will not truncate words.
 *
 * @package BuddyPress Core
 * @param $text str The text to create the excerpt from
 * @uses $excerpt_length The maximum length in characters of the excerpt.
 * @return str The excerpt text
 */
function bp_create_excerpt( $text, $excerpt_length = 225, $filter_shortcodes = true ) { // Fakes an excerpt if needed
	$original_text = $text;
	$text = str_replace(']]>', ']]&gt;', $text);

	if ( $filter_shortcodes )
		$text = preg_replace( '|\[(.+?)\](.+?\[/\\1\])?|s', '', $text );

	preg_match( "%\s*((?:<[^>]+>)+\S*)\s*|\s+%s", $text, $matches, PREG_OFFSET_CAPTURE, $excerpt_length );

	if ( !empty( $matches ) ) {
		$pos = array_pop( array_pop( $matches ) );
		$text = substr( $text, 0, $pos ) . ' [...]';
	}

	return apply_filters( 'bp_create_excerpt', $text, $original_text );
}
add_filter( 'bp_create_excerpt', 'wp_trim_excerpt' );
add_filter( 'bp_create_excerpt', 'stripslashes_deep' );
add_filter( 'bp_create_excerpt', 'force_balance_tags' );

function bp_total_member_count() {
	echo bp_get_total_member_count();
}
	function bp_get_total_member_count() {
		return apply_filters( 'bp_get_total_member_count', bp_core_get_total_member_count() );
	}
	add_filter( 'bp_get_total_member_count', 'bp_core_number_format' );

function bp_blog_signup_allowed() {
	echo bp_get_blog_signup_allowed();
}
	function bp_get_blog_signup_allowed() {
		global $bp;

		if ( !is_multisite() )
			return false;

		$status = $bp->site_options['registration'];
		if ( 'none' != $status && 'user' != $status )
			return true;

		return false;
	}

function bp_account_was_activated() {
	global $bp;

	$activation_complete = !empty( $bp->activation_complete ) ? $bp->activation_complete : false;

	return $activation_complete;
}

function bp_registration_needs_activation() {
	return apply_filters( 'bp_registration_needs_activation', true );
}

function bp_get_option( $option_name ) {
	global $bp;

	if ( !empty( $bp->site_options[$option_name] ) )
		$retval = $bp->site_options[$option_name];
	else
		$retval = false;

	return apply_filters( 'bp_get_option', $retval );
}

/**
 * Allow templates to pass parameters directly into the template loops via AJAX
 *
 * For the most part this will be filtered in a theme's functions.php for example
 * in the default theme it is filtered via bp_dtheme_ajax_querystring()
 *
 * By using this template tag in the templates it will stop them from showing errors
 * if someone copies the templates from the default theme into another WordPress theme
 * without coping the functions from functions.php.
 */
function bp_ajax_querystring( $object = false ) {
	global $bp;

	if ( !isset( $bp->ajax_querystring ) )
		$bp->ajax_querystring = '';

	return apply_filters( 'bp_ajax_querystring', $bp->ajax_querystring, $object );
}

/** Template Classes and _is functions ****************************************/

function bp_current_component() {
	global $bp;
	return apply_filters( 'bp_current_component', $bp->current_component );
}

function bp_current_action() {
	global $bp;
	return apply_filters( 'bp_current_action', $bp->current_action );
}

function bp_current_item() {
	global $bp;
	return apply_filters( 'bp_current_item', $bp->current_item );
}

function bp_action_variables() {
	global $bp;
	return apply_filters( 'bp_action_variables', $bp->action_variables );
}

function bp_root_domain() {
	echo bp_get_root_domain();
}
	function bp_get_root_domain() {
		global $bp;

		return apply_filters( 'bp_get_root_domain', $bp->root_domain );
	}

/**
 * Echoes the output of bp_get_root_slug()
 *
 * @package BuddyPress Core
 * @since 1.3
 */
function bp_root_slug( $component = '' ) {
	echo bp_get_root_slug( $component );
}
	/**
	 * Gets the root slug for a component slug
	 *
	 * In order to maintain backward compatibility, the following procedure is used:
	 * 1) Use the short slug to get the canonical component name from the
	 *    active component array
	 * 2) Use the component name to get the root slug out of the appropriate part of the $bp
	 *    global
	 * 3) If nothing turns up, it probably means that $component is itself a root slug
	 *
	 * Example: If your groups directory is at /community/companies, this function first uses
	 * the short slug 'companies' (ie the current component) to look up the canonical name
	 * 'groups' in $bp->active_components. Then it uses 'groups' to get the root slug, from
	 * $bp->groups->root_slug.
	 *
	 * @package BuddyPress Core
	 * @since 1.3
	 *
	 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
	 * @param string $component Optional. Defaults to the current component
	 * @return string $root_slug The root slug
	 */
	function bp_get_root_slug( $component = '' ) {
		global $bp;

		$root_slug = '';

		// Use current global component if none passed
		if ( empty( $component ) )
			$component = $bp->current_component;

		// Component is active
		if ( !empty( $bp->active_components[$component] ) ) {
			$component_name = $bp->active_components[$component];

			// Component has specific root slug
			if ( !empty( $bp->{$component_name}->root_slug ) )
				$root_slug = $bp->{$component_name}->root_slug;
		}

		// No specific root slug, so fall back to component slug
		if ( empty( $root_slug ) )
			$root_slug = $component;

		return apply_filters( 'bp_get_root_slug', $root_slug, $component );
	}

/* Template is_() functions to determine the current page */

/**
 * Checks to see whether the current page belongs to the specified component
 *
 * This function is designed to be generous, accepting several different kinds
 * of value for the $component parameter. It checks $component_name against:
 * - the component's root_slug, which matches the page slug in $bp->pages
 * - the component's regular slug
 * - the component's id, or 'canonical' name
 *
 * @package BuddyPress Core
 * @since 1.3
 * @return bool Returns true if the component matches, or else false.
 */
function bp_is_current_component( $component ) {
	global $bp;

	$is_current_component = false;

	if ( !empty( $bp->current_component ) ) {

		// First, check to see whether $component_name and the current
		// component are a simple match
		if ( $bp->current_component == $component ) {
			$is_current_component = true;

		// Next, check to see whether $component is a canonical,
		// non-translatable component name. If so, we can return its
		// corresponding slug from $bp->active_components.
		} else if ( $key = array_search( $component, $bp->active_components ) ) {
			if ( $key === $bp->current_component )
				$is_current_component = true;

		// If we haven't found a match yet, check against the root_slugs
		// created by $bp->pages
		} else {
			foreach ( $bp->active_components as $key => $id ) {
				// If the $component parameter does not match the current_component,
				// then move along, these are not the droids you are looking for
				if ( empty( $bp->{$id}->root_slug ) || $bp->{$id}->root_slug != $bp->current_component )
					continue;

				if ( $key == $component ) {
					$is_current_component = true;
					break;
				}
			}
		}
	}

 	return apply_filters( 'bp_is_current_component', $is_current_component, $component );
}

/**
 * Checks to see if a component's URL should be in the root, not under a member page:
 * eg: http://domain.com/groups/the-group NOT http://domain.com/members/andy/groups/the-group
 *
 * @package BuddyPress Core
 * @return true if root component, else false.
 */
function bp_is_root_component( $component_name ) {
	global $bp;

	foreach ( (array) $bp->active_components as $key => $slug ) {
		if ( $key == $component_name || $slug == $component_name )
			return true;
	}

	return false;
}

/**
 * Checks if the site's front page is set to the specified BuddyPress component page in wp-admin's Settings > Reading screen.
 *
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $current_blog WordPress global containing information and settings for the current blog being viewed.
 * @param string $component Optional; name of the component to check for. If not specified, uses $bp->current_component.
 * @return bool True if the specified component is set to be the site's front page.
 * @since 1.3
 */
function bp_is_component_front_page( $component='' ) {
	global $bp, $current_blog;

	if ( !$component && !empty( $bp->current_component ) )
		$component = $bp->current_component;

	if ( is_main_site() )
		$path = bp_core_get_site_path();
	else
		$path = $current_blog->path;

	if ( 'page' != get_option( 'show_on_front' ) || !$component || empty( $bp->pages->{$component} ) || $_SERVER['REQUEST_URI'] != $path )
		return false;

	return apply_filters( 'bp_is_component_front_page', ( $bp->pages->{$component}->id == get_option( 'page_on_front' ) ), $component );
}

function bp_is_blog_page() {
	global $bp, $wp_query;

	if ( $wp_query->is_home && ( !isset( $bp->is_directory ) || !$bp->is_directory ) )
		return true;

	if ( !$bp->displayed_user->id && !$bp->is_single_item && ( !isset( $bp->is_directory ) || !$bp->is_directory ) && !bp_is_root_component( $bp->current_component ) )
		return true;

	return false;
}

function bp_is_my_profile() {
	global $bp;

	if ( is_user_logged_in() && $bp->loggedin_user->id == $bp->displayed_user->id )
		$my_profile = true;
	else
		$my_profile = false;

	return apply_filters( 'bp_is_my_profile', $my_profile );
}
function bp_is_home() { return bp_is_my_profile(); }

/**
 * Is the user on the front page of the site?
 *
 * @deprecated 1.3
 * @deprecated Use is_front_page()
 * @return bool
 */
function bp_is_front_page() {
	_deprecated_function( __FUNCTION__, '1.3', "is_front_page()" );
	return is_front_page();
}

/**
 * Is the front page of the site set to the Activity component?
 *
 * @deprecated 1.3
 * @deprecated Use bp_is_component_front_page( 'activity' )
 * @return bool
 */
function bp_is_activity_front_page() {
	_deprecated_function( __FUNCTION__, '1.3', "bp_is_component_front_page( 'activity' )" );
	return bp_is_component_front_page( 'activity' );
}

function bp_is_directory() {
	global $bp;

	return ( isset( $bp->is_directory ) && $bp->is_directory );
}

function bp_is_page( $page ) {
	global $bp;

	if ( !$bp->displayed_user->id && $bp->current_component == $page )
		return true;

	if ( 'home' == $page )
		return bp_is_front_page();

	return false;
}

function bp_is_active( $component ) {
	global $bp_deactivated;

	if ( !isset( $bp_deactivated[ 'bp-' . $component . '/bp-' . $component . '-loader.php' ] ) )
		return true;

	return false;
}

function bp_is_profile_component() {
	if ( bp_is_current_component( 'profile' ) )
		return true;

	return false;
}

function bp_is_activity_component() {
	if ( bp_is_current_component( 'activity' ) )
		return true;

	return false;
}

function bp_is_blogs_component() {
	if ( is_multisite() && bp_is_current_component( 'blogs' ) )
		return true;

	return false;
}

function bp_is_messages_component() {
	if ( bp_is_current_component( 'messages' ) )
		return true;

	return false;
}

function bp_is_friends_component() {

	if ( bp_is_current_component( 'friends' ) )
		return true;

	return false;
}

function bp_is_groups_component() {
	if ( bp_is_current_component( 'groups' ) )
		return true;

	return false;
}

function bp_is_settings_component() {
	if ( bp_is_current_component( 'settings' ) )
		return true;

	return false;
}

function bp_is_member() {
	global $bp;

	if ( !empty( $bp->displayed_user->id ) )
		return true;

	return false;
}

function bp_is_user_activity() {
	global $bp;

	if ( bp_is_current_component( 'activity' ) )
		return true;

	return false;
}

function bp_is_user_friends_activity() {
	global $bp;

	if ( bp_is_current_component( 'activity' ) && 'my-friends' == $bp->current_action )
		return true;

	return false;
}

function bp_is_activity_permalink() {
	global $bp;

	if ( bp_is_current_component( 'activity' ) && is_numeric( $bp->current_action ) )
		return true;

	return false;
}

function bp_is_user_profile() {
	global $bp;

	if ( bp_is_current_component( 'profile' ) )
		return true;

	return false;
}

function bp_is_profile_edit() {
	global $bp;

	if ( bp_is_current_component( 'profile' ) && 'edit' == $bp->current_action )
		return true;

	return false;
}

function bp_is_change_avatar() {
	global $bp;

	if ( bp_is_current_component( 'profile' ) && 'change-avatar' == $bp->current_action )
		return true;

	return false;
}

function bp_is_user_groups() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) )
		return true;

	return false;
}

function bp_is_group() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && isset( $bp->groups->current_group ) && $bp->groups->current_group )
		return true;

	return false;
}

function bp_is_group_home() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && $bp->is_single_item && ( !$bp->current_action || 'home' == $bp->current_action ) )
		return true;

	return false;
}

function bp_is_group_create() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && 'create' == $bp->current_action )
		return true;

	return false;
}


function bp_is_group_admin_page() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && $bp->is_single_item && 'admin' == $bp->current_action )
		return true;

	return false;
}

function bp_is_group_forum() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && $bp->is_single_item && 'forum' == $bp->current_action )
		return true;

	return false;
}

function bp_is_group_activity() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && $bp->is_single_item && 'activity' == $bp->current_action )
		return true;

	return false;
}

function bp_is_group_forum_topic() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && $bp->is_single_item && 'forum' == $bp->current_action && isset( $bp->action_variables[0] ) && 'topic' == $bp->action_variables[0] )
		return true;

	return false;
}

function bp_is_group_forum_topic_edit() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && $bp->is_single_item && 'forum' == $bp->current_action && isset( $bp->action_variables[0] ) && 'topic' == $bp->action_variables[0] && isset( $bp->action_variables[2] ) && 'edit' == $bp->action_variables[2] )
		return true;

	return false;
}

function bp_is_group_members() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && $bp->is_single_item && 'members' == $bp->current_action )
		return true;

	return false;
}

function bp_is_group_invites() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && 'send-invites' == $bp->current_action )
		return true;

	return false;
}

function bp_is_group_membership_request() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && 'request-membership' == $bp->current_action )
		return true;

	return false;
}

function bp_is_group_leave() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && $bp->is_single_item && 'leave-group' == $bp->current_action )
		return true;

	return false;
}

function bp_is_group_single() {
	global $bp;

	if ( bp_is_current_component( 'groups' ) && $bp->is_single_item )
		return true;

	return false;
}

function bp_is_user_blogs() {
	global $bp;

	if ( is_multisite() && bp_is_current_component( 'blogs' ) )
		return true;

	return false;
}

function bp_is_user_recent_posts() {
	global $bp;

	if ( is_multisite() && bp_is_current_component( 'blogs' ) && 'recent-posts' == $bp->current_action )
		return true;

	return false;
}

function bp_is_user_recent_commments() {
	global $bp;

	if ( is_multisite() && bp_is_current_component( 'blogs' ) && 'recent-comments' == $bp->current_action )
		return true;

	return false;
}

function bp_is_create_blog() {
	global $bp;

	if ( is_multisite() && bp_is_current_component( 'blogs' ) && 'create' == $bp->current_action )
		return true;

	return false;
}

function bp_is_user_friends() {

	if ( bp_is_current_component( 'friends' ) )
		return true;

	return false;
}

function bp_is_friend_requests() {
	global $bp;

	if ( bp_is_current_component( 'friends' ) && 'requests' == $bp->current_action )
		return true;

	return false;
}

function bp_is_user_messages() {

	if ( bp_is_current_component( 'messages' ) )
		return true;

	return false;
}

function bp_is_messages_inbox() {
	global $bp;

	if ( bp_is_current_component( 'messages' ) && ( !$bp->current_action || 'inbox' == $bp->current_action ) )
		return true;

	return false;
}

function bp_is_messages_sentbox() {
	global $bp;

	if ( bp_is_current_component( 'messages' ) && 'sentbox' == $bp->current_action )
		return true;

	return false;
}


function bp_is_notices() {
	global $bp;

	if ( bp_is_current_component( 'messages' ) && 'notices' == $bp->current_action )
		return true;

	return false;
}


function bp_is_messages_compose_screen() {
	global $bp;

	if ( bp_is_current_component( 'messages' ) && 'compose' == $bp->current_action )
		return true;

	return false;
}

function bp_is_single_item() {
	global $bp;

	if ( $bp->is_single_item )
		return true;

	return false;
}

function bp_is_activation_page() {
	if ( bp_is_current_component( 'activation' ) )
		return true;

	return false;
}

function bp_is_register_page() {
	if ( bp_is_current_component( 'register' ) )
		return true;

	return false;
}

/* Use the above is_() functions to output a body class for each page */

function bp_the_body_class() {
	echo bp_get_the_body_class();
}
	function bp_get_the_body_class( $wp_classes, $custom_classes = false ) {
		global $bp;

		$bp_classes = array();

		if ( is_front_page() )
			$bp_classes[] = 'home-page';

		if ( bp_is_directory() )
			$bp_classes[] = 'directory';

		if ( bp_is_user_profile() && !bp_is_blog_page() )
			$bp_classes[] = 'profile';

		if ( bp_is_activity_component() && !bp_is_blog_page() )
			$bp_classes[] = 'activity';

		if ( bp_is_blogs_component() && !bp_is_blog_page() )
			$bp_classes[] = 'blogs';

		if ( bp_is_messages_component() && !bp_is_blog_page() )
			$bp_classes[] = 'messages';

		if ( bp_is_friends_component() && !bp_is_blog_page() )
			$bp_classes[] = 'friends';

		if ( bp_is_groups_component() && !bp_is_blog_page() )
			$bp_classes[] = 'groups';

		if ( bp_is_settings_component() && !bp_is_blog_page()  )
			$bp_classes[] = 'settings';

		if ( bp_is_user_profile() )
			$bp_classes[] = 'my-profile';

		if ( bp_is_my_profile() )
			$bp_classes[] = 'my-account';

		if ( bp_is_single_item() )
			$bp_classes[] = 'single-item';

		if ( bp_is_messages_inbox() )
			$bp_classes[] = 'inbox';

		if ( bp_is_messages_sentbox() )
			$bp_classes[] = 'sentbox';

		if ( bp_is_messages_compose_screen() )
			$bp_classes[] = 'compose';

		if ( bp_is_notices() )
			$bp_classes[] = 'notices';

		if ( bp_is_friend_requests() )
			$bp_classes[] = 'friend-requests';

		if ( bp_is_user_friends() )
			$bp_classes[] = 'my-friends';

		if ( bp_is_user_messages() )
			$bp_classes[] = 'my-messages';

		if ( bp_is_create_blog() )
			$bp_classes[] = 'create-blog';

		if ( bp_is_user_recent_commments() )
			$bp_classes[] = 'recent-comments';

		if ( bp_is_user_recent_posts() )
			$bp_classes[] = 'recent-posts';

		if ( bp_is_user_blogs() && !bp_is_directory() )
			$bp_classes[] = 'my-blogs';

		if ( bp_is_user_groups() && !bp_is_directory() )
			$bp_classes[] = 'my-groups';

		if ( bp_is_group_leave() )
			$bp_classes[] = 'leave-group';

		if ( bp_is_group_invites() )
			$bp_classes[] = 'group-invites';

		if ( bp_is_group_members() )
			$bp_classes[] = 'group-members';

		if ( bp_is_group_forum_topic() )
			$bp_classes[] = 'group-forum-topic';

		if ( bp_is_group_forum_topic_edit() )
			$bp_classes[] = 'group-forum-topic-edit';

		if ( bp_is_group_forum() )
			$bp_classes[] = 'group-forum';

		if ( bp_is_group_admin_page() )
			$bp_classes[] = 'group-admin';

		if ( bp_is_group_create() )
			$bp_classes[] = 'group-create';

		if ( bp_is_group_home() )
			$bp_classes[] = 'group-home';

		if ( bp_is_change_avatar() )
			$bp_classes[] = 'change-avatar';

		if ( bp_is_profile_edit() )
			$bp_classes[] = 'profile-edit';

		if ( bp_is_user_friends_activity() )
			$bp_classes[] = 'friends-activity';

		if ( bp_is_user_activity() && !bp_is_directory() )
			$bp_classes[] = 'my-activity';

		if ( bp_is_activity_permalink() )
			$bp_classes[] = 'activity-permalink';

		if ( bp_is_register_page() )
			$bp_classes[] = 'registration';

		if ( bp_is_activation_page() )
			$bp_classes[] = 'activation';

		if ( is_user_logged_in() )
			$bp_classes[] = 'logged-in';

		// Add the current_component, current_action into the bp classes
		if ( !bp_is_blog_page() ) {
			if ( !empty( $bp->current_component ) )
				$bp_classes[] = $bp->current_component;

			if ( !empty( $bp->current_action ) )
				$bp_classes[] = $bp->current_action;
		}

		// We don't want WordPress blog classes to appear on non-blog pages.
		if ( !bp_is_blog_page() || is_home() ) {
			// Preserve any custom classes already set
			if ( !empty( $custom_classes ) )
				$wp_classes = (array) $custom_classes;
			else
				$wp_classes = array();
		}

		// Merge WP classes with BP classes
		$classes = array_merge( (array) $bp_classes, (array) $wp_classes );

		// Remove any duplicates
		$classes = array_unique( $classes );

		return apply_filters( 'bp_get_the_body_class', $classes, $bp_classes, $wp_classes, $custom_classes );
	}
	add_filter( 'body_class', 'bp_get_the_body_class', 10, 2 )


?>
