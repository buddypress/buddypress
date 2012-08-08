<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Uses the $bp->bp_options_nav global to render out the sub navigation for the current component.
 * Each component adds to its sub navigation array within its own setup_nav() function.
 *
 * This sub navigation array is the secondary level navigation, so for profile it contains:
 *      [Public, Edit Profile, Change Avatar]
 *
 * The function will also analyze the current action for the current component to determine whether
 * or not to highlight a particular sub nav item.
 *
 * @package BuddyPress Core
 * @global BuddyPress $bp The one true BuddyPress instance
 * @uses bp_get_user_nav() Renders the navigation for a profile of a currently viewed user.
 */
function bp_get_options_nav() {
	global $bp;

	// If we are looking at a member profile, then the we can use the current component as an
	// index. Otherwise we need to use the component's root_slug
	$component_index = !empty( $bp->displayed_user ) ? bp_current_component() : bp_get_root_slug( bp_current_component() );

	if ( !bp_is_single_item() ) {
		if ( !isset( $bp->bp_options_nav[$component_index] ) || count( $bp->bp_options_nav[$component_index] ) < 1 ) {
			return false;
		} else {
			$the_index = $component_index;
		}
	} else {
		if ( !isset( $bp->bp_options_nav[bp_current_item()] ) || count( $bp->bp_options_nav[bp_current_item()] ) < 1 ) {
			return false;
		} else {
			$the_index = bp_current_item();
		}
	}

	// Loop through each navigation item
	foreach ( (array) $bp->bp_options_nav[$the_index] as $subnav_item ) {
		if ( !$subnav_item['user_has_access'] )
			continue;

		// If the current action or an action variable matches the nav item id, then add a highlight CSS class.
		if ( $subnav_item['slug'] == bp_current_action() ) {
			$selected = ' class="current selected"';
		} else {
			$selected = '';
		}

		// List type depends on our current component
		$list_type = bp_is_group() ? 'groups' : 'personal';

		// echo out the final list item
		echo apply_filters( 'bp_get_options_nav_' . $subnav_item['css_id'], '<li id="' . $subnav_item['css_id'] . '-' . $list_type . '-li" ' . $selected . '><a id="' . $subnav_item['css_id'] . '" href="' . $subnav_item['link'] . '">' . $subnav_item['name'] . '</a></li>', $subnav_item );
	}
}

function bp_get_options_title() {
	global $bp;

	if ( empty( $bp->bp_options_title ) )
		$bp->bp_options_title = __( 'Options', 'buddypress' );

	echo apply_filters( 'bp_get_options_title', esc_attr( $bp->bp_options_title ) );
}

/** Avatars *******************************************************************/

/**
 * Check to see if there is an options avatar. An options avatar is an avatar for something
 * like a group, or a friend. Basically an avatar that appears in the sub nav options bar.
 *
 * @package BuddyPress Core
 * @global BuddyPress $bp The one true BuddyPress instance
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
		echo apply_filters( 'bp_comment_author_avatar', bp_core_fetch_avatar( array( 'item_id' => $comment->user_id, 'type' => 'thumb', 'alt' => sprintf( __( 'Avatar of %s', 'buddypress' ), bp_core_get_user_displayname( $comment->user_id ) ) ) ) );
	else if ( function_exists('get_avatar') )
		get_avatar();
}

function bp_post_author_avatar() {
	global $post;

	if ( function_exists( 'bp_core_fetch_avatar' ) )
		echo apply_filters( 'bp_post_author_avatar', bp_core_fetch_avatar( array( 'item_id' => $post->post_author, 'type' => 'thumb', 'alt' => sprintf( __( 'Avatar of %s', 'buddypress' ), bp_core_get_user_displayname( $post->post_author ) ) ) ) );
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
			$step = 'upload-image';

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

/**
 * Echoes bp_get_site_name()
 */
function bp_site_name() {
	echo bp_get_site_name();
}
	/**
	 * Returns the name of the BP site. Used in RSS headers
	 *
	 * @since 1.6
	 */
	function bp_get_site_name() {
		return apply_filters( 'bp_site_name', get_bloginfo( 'name', 'display' ) );
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
		$root_blog_offset = get_blog_option( bp_get_root_blog_id(), 'gmt_offset' );

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

	if ( !empty( $capitalize ) )
		$youtext = bp_core_ucfirst( $youtext );

	if ( bp_displayed_user_id() == bp_loggedin_user_id() ) {
		if ( true == $echo ) {
			echo apply_filters( 'bp_word_or_name', $youtext );
		} else {
			return apply_filters( 'bp_word_or_name', $youtext );
		}
	} else {
		$fullname = bp_get_displayed_user_fullname();
		$fullname = (array) explode( ' ', $fullname );
		$nametext = sprintf( $nametext, $fullname[0] );
		if ( true == $echo ) {
			echo apply_filters( 'bp_word_or_name', $nametext );
		} else {
			return apply_filters( 'bp_word_or_name', $nametext );
		}
	}
}

function bp_get_plugin_sidebar() {
	locate_template( array( 'plugin-sidebar.php' ), true );
}

function bp_styles() {
	do_action( 'bp_styles' );
	wp_print_styles();
}

/** Search Form ***************************************************************/

function bp_search_form_action() {
	return apply_filters( 'bp_search_form_action', trailingslashit( bp_get_root_domain() . '/' . bp_get_search_slug() ) );
}

/**
 * Generates the basic search form as used in BP-Default's header.
 *
 * @return string HTML <select> element
 * @since 1.0
 */
function bp_search_form_type_select() {

	$options = array();

	if ( bp_is_active( 'xprofile' ) )
		$options['members'] = __( 'Members', 'buddypress' );

	if ( bp_is_active( 'groups' ) )
		$options['groups']  = __( 'Groups',  'buddypress' );

	if ( bp_is_active( 'blogs' ) && is_multisite() )
		$options['blogs']   = __( 'Blogs',   'buddypress' );

	if ( bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() && bp_forums_has_directory() )
		$options['forums']  = __( 'Forums',  'buddypress' );

	$options['posts'] = __( 'Posts', 'buddypress' );

	// Eventually this won't be needed and a page will be built to integrate all search results.
	$selection_box  = '<label for="search-which" class="accessibly-hidden">' . __( 'Search these:', 'buddypress' ) . '</label>';
	$selection_box .= '<select name="search-which" id="search-which" style="width: auto">';

	$options = apply_filters( 'bp_search_form_type_select_options', $options );
	foreach( (array) $options as $option_value => $option_title )
		$selection_box .= sprintf( '<option value="%s">%s</option>', $option_value, $option_title );

	$selection_box .= '</select>';

	return apply_filters( 'bp_search_form_type_select', $selection_box );
}

/**
 * Get the default text for the search box for a given component.
 *
 * @global object $bp BuddyPress global settings
 * @return string
 * @since BuddyPress (1.5)
 */
function bp_search_default_text( $component = '' ) {
	echo bp_get_search_default_text( $component );
}
	function bp_get_search_default_text( $component = '' ) {
		global $bp;

		if ( empty( $component ) )
			$component = bp_current_component();

		$default_text = __( 'Search anything...', 'buddypress' );

		// Most of the time, $component will be the actual component ID
		if ( !empty( $component ) ) {
			if ( !empty( $bp->{$component}->search_string ) ) {
				$default_text = $bp->{$component}->search_string;
			} else {
				// When the request comes through AJAX, we need to get the component
				// name out of $bp->pages
				if ( !empty( $bp->pages->{$component}->slug ) ) {
					$key = $bp->pages->{$component}->slug;
					if ( !empty( $bp->{$key}->search_string ) )
						$default_text = $bp->{$key}->search_string;
				}
			}
		}

		return apply_filters( 'bp_get_search_default_text', $default_text, $component );
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
 * Truncates text.
 *
 * Cuts a string to the length of $length and replaces the last characters
 * with the ending if the text is longer than length.
 *
 * This function is borrowed from CakePHP v2.0, under the MIT license. See
 * http://book.cakephp.org/view/1469/Text#truncate-1625
 *
 * ### Options:
 *
 * - `ending` Will be used as Ending and appended to the trimmed string
 * - `exact` If false, $text will not be cut mid-word
 * - `html` If true, HTML tags would be handled correctly
 * - `filter_shortcodes` If true, shortcodes will be stripped before truncating
 *
 * @package BuddyPress
 *
 * @param string  $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param array $options An array of html attributes and options.
 * @return string Trimmed string.
 */
function bp_create_excerpt( $text, $length = 225, $options = array() ) {
	// Backward compatibility. The third argument used to be a boolean $filter_shortcodes
	$filter_shortcodes_default = is_bool( $options ) ? $options : true;

	$defaults = array(
		'ending'            => __( ' [&hellip;]', 'buddypress' ),
		'exact'             => false,
		'html'              => true,
		'filter_shortcodes' => $filter_shortcodes_default
	);
	$r = wp_parse_args( $options, $defaults );
	extract( $r );

	// Save the original text, to be passed along to the filter
	$original_text = $text;

	// Allow plugins to modify these values globally
	$length = apply_filters( 'bp_excerpt_length', $length );
	$ending = apply_filters( 'bp_excerpt_append_text', $ending );

	// Remove shortcodes if necessary
	if ( !empty( $filter_shortcodes ) )
		$text = strip_shortcodes( $text );

	// When $html is true, the excerpt should be created without including HTML tags in the
	// excerpt length
	if ( !empty( $html ) ) {
		// The text is short enough. No need to truncate
		if ( mb_strlen( preg_replace( '/<.*?>/', '', $text ) ) <= $length ) {
			return $text;
		}

		$totalLength = mb_strlen( strip_tags( $ending ) );
		$openTags    = array();
		$truncate    = '';

		// Find all the tags and put them in a stack for later use
		preg_match_all( '/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER );
		foreach ( $tags as $tag ) {
			// Process tags that need to be closed
			if ( !preg_match( '/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s',  $tag[2] ) ) {
				if ( preg_match( '/<[\w]+[^>]*>/s', $tag[0] ) ) {
					array_unshift( $openTags, $tag[2] );
				} else if ( preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag ) ) {
					$pos = array_search( $closeTag[1], $openTags );
					if ( $pos !== false ) {
						array_splice( $openTags, $pos, 1 );
					}
				}
			}
			$truncate .= $tag[1];

			$contentLength = mb_strlen( preg_replace( '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3] ) );
			if ( $contentLength + $totalLength > $length ) {
				$left = $length - $totalLength;
				$entitiesLength = 0;
				if ( preg_match_all( '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE ) ) {
					foreach ( $entities[0] as $entity ) {
						if ( $entity[1] + 1 - $entitiesLength <= $left ) {
							$left--;
							$entitiesLength += mb_strlen( $entity[0] );
						} else {
							break;
						}
					}
				}

				$truncate .= mb_substr( $tag[3], 0 , $left + $entitiesLength );
				break;
			} else {
				$truncate .= $tag[3];
				$totalLength += $contentLength;
			}
			if ( $totalLength >= $length ) {
				break;
			}
		}
	} else {
		if ( mb_strlen( $text ) <= $length ) {
			return $text;
		} else {
			$truncate = mb_substr( $text, 0, $length - mb_strlen( $ending ) );
		}
	}

	// If $exact is false, we can't break on words
	if ( empty( $exact ) ) {
		$spacepos = mb_strrpos( $truncate, ' ' );
		if ( isset( $spacepos ) ) {
			if ( $html ) {
				$bits = mb_substr( $truncate, $spacepos );
				preg_match_all( '/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER );
				if ( !empty( $droppedTags ) ) {
					foreach ( $droppedTags as $closingTag ) {
						if ( !in_array( $closingTag[1], $openTags ) ) {
							array_unshift( $openTags, $closingTag[1] );
						}
					}
				}
			}
			$truncate = mb_substr( $truncate, 0, $spacepos );
		}
	}
	$truncate .= $ending;

	if ( $html ) {
		foreach ( $openTags as $tag ) {
			$truncate .= '</' . $tag . '>';
		}
	}

	return apply_filters( 'bp_create_excerpt', $truncate, $original_text, $length, $options );

}
add_filter( 'bp_create_excerpt', 'stripslashes_deep' );
add_filter( 'bp_create_excerpt', 'force_balance_tags' );

/**
 * Echoes the output of bp_get_total_member_count()
 */
function bp_total_member_count() {
	echo bp_get_total_member_count();
}
	/**
	 * Returns the total member count in your BP instance
	 *
	 * Since BuddyPress 1.6, this function has used bp_core_get_active_member_count(), which
	 * counts non-spam, non-deleted users who have last_activity. This value will correctly
	 * match the total member count number used for pagination on member directories.
	 *
	 * Before BuddyPress 1.6, this function used bp_core_get_total_member_count(), which did
	 * not take into account last_activity, and thus often resulted in higher counts than
	 * shown by member directory pagination.
	 */
	function bp_get_total_member_count() {
		return apply_filters( 'bp_get_total_member_count', bp_core_get_active_member_count() );
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
	$current_component = !empty( $bp->current_component ) ? $bp->current_component : false;
	return apply_filters( 'bp_current_component', $current_component );
}

function bp_current_action() {
	global $bp;
	$current_action = !empty( $bp->current_action ) ? $bp->current_action : '';
	return apply_filters( 'bp_current_action', $current_action );
}

function bp_current_item() {
	global $bp;
	$current_item = !empty( $bp->current_item ) ? $bp->current_item : false;
	return apply_filters( 'bp_current_item', $current_item );
}

/**
 * Return the value of $bp->action_variables
 *
 * @package BuddyPress
 *
 * @param mixed $action_variables The action variables array, or false if the array is empty
 */
function bp_action_variables() {
	global $bp;
	$action_variables = !empty( $bp->action_variables ) ? $bp->action_variables : false;
	return apply_filters( 'bp_action_variables', $action_variables );
}

/**
 * Return the value of a given action variable
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @param int $position The key of the action_variables array that you want
 * @return str $action_variable The value of that position in the array
 */
function bp_action_variable( $position = 0 ) {
	$action_variables = bp_action_variables();
	$action_variable  = isset( $action_variables[$position] ) ? $action_variables[$position] : false;

	return apply_filters( 'bp_action_variable', $action_variable, $position );
}

function bp_root_domain() {
	echo bp_get_root_domain();
}
	function bp_get_root_domain() {
		global $bp;

		if ( isset( $bp->root_domain ) && !empty( $bp->root_domain ) ) {
			$domain = $bp->root_domain;
		} else {
			$domain          = bp_core_get_root_domain();
			$bp->root_domain = $domain;
		}

		return apply_filters( 'bp_get_root_domain', $domain );
	}

/**
 * Echoes the output of bp_get_root_slug()
 *
 * @package BuddyPress Core
 * @since BuddyPress (1.5)
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
	 * @since BuddyPress (1.5)
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 * @param string $component Optional. Defaults to the current component
	 * @return string $root_slug The root slug
	 */
	function bp_get_root_slug( $component = '' ) {
		global $bp;

		$root_slug = '';

		// Use current global component if none passed
		if ( empty( $component ) )
			$component = bp_current_component();

		// Component is active
		if ( !empty( $bp->active_components[$component] ) ) {
			// Backward compatibility: in legacy plugins, the canonical component id
			// was stored as an array value in $bp->active_components
			$component_name = '1' == $bp->active_components[$component] ? $component : $bp->active_components[$component];

			// Component has specific root slug
			if ( !empty( $bp->{$component_name}->root_slug ) ) {
				$root_slug = $bp->{$component_name}->root_slug;
			}
		}

		// No specific root slug, so fall back to component slug
		if ( empty( $root_slug ) )
			$root_slug = $component;

		return apply_filters( 'bp_get_root_slug', $root_slug, $component );
	}

/**
 * Return the component name based on the current root slug
 *
 * @since BuddyPress {r3923}
 * @global BuddyPress $bp The one true BuddyPress instance
 * @param str $root_slug Needle to our active component haystack
 * @return mixed False if none found, component name if found
 */
function bp_get_name_from_root_slug( $root_slug = '' ) {
	global $bp;

	// If no slug is passed, look at current_component
	if ( empty( $root_slug ) )
		$root_slug = bp_current_component();

	// No current component or root slug, so flee
	if ( empty( $root_slug ) )
		return false;

	// Loop through active components and look for a match
	foreach ( $bp->active_components as $component => $id ) {
		if ( !empty( $bp->{$component}->root_slug ) && ( $bp->{$component}->root_slug == $root_slug ) ) {
			return $bp->{$component}->name;
		}
	}

	return false;
}

function bp_user_has_access() {
	$has_access = ( bp_current_user_can( 'bp_moderate' ) || bp_is_my_profile() ) ? true : false;

	return apply_filters( 'bp_user_has_access', $has_access );
}

/**
 * Output the search slug
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @uses bp_get_search_slug()
 */
function bp_search_slug() {
	echo bp_get_search_slug();
}
	/**
	 * Return the search slug
	 *
	 * @package BuddyPress
	 * @since BuddyPress (1.5)
	 */
	function bp_get_search_slug() {
		return apply_filters( 'bp_get_search_slug', BP_SEARCH_SLUG );
	}

/**
 * Get the id of the currently displayed user
 *
 * @uses apply_filters() Filter 'bp_displayed_user_id' to change this value
 * @return int
 */
function bp_displayed_user_id() {

	static $id = 0;

	if ( empty( $id ) ) {
		global $bp;
		$id = !empty( $bp->displayed_user->id ) ? $bp->displayed_user->id : 0;
	}

	return apply_filters( 'bp_displayed_user_id', $id );
}

/**
 * Get the id of the currently logged-in user
 *
 * @uses apply_filters() Filter 'bp_loggedin_user_id' to change this value
 * @return int
 */
function bp_loggedin_user_id() {

	static $id = 0;

	if ( empty( $id ) ) {
		global $bp;
		$id = !empty( $bp->loggedin_user->id ) ? $bp->loggedin_user->id : 0;
	}

	return apply_filters( 'bp_loggedin_user_id', $id );
}

/** is_() functions to determine the current page *****************************/

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
 * @since BuddyPress (1.5)
 * @return bool Returns true if the component matches, or else false.
 */
function bp_is_current_component( $component ) {
	global $bp;

	$is_current_component = false;

	// Backward compatibility: 'xprofile' should be read as 'profile'
	if ( 'xprofile' == $component )
		$component = 'profile';

	if ( !empty( $bp->current_component ) ) {

		// First, check to see whether $component_name and the current
		// component are a simple match
		if ( $bp->current_component == $component ) {
			$is_current_component = true;

		// Since the current component is based on the visible URL slug let's
		// check the component being passed and see if its root_slug matches
		} elseif ( isset( $bp->{$component}->root_slug ) && $bp->{$component}->root_slug == $bp->current_component ) {
			$is_current_component = true;

		// Because slugs can differ from root_slugs, we should check them too
		} elseif ( isset( $bp->{$component}->slug ) && $bp->{$component}->slug == $bp->current_component ) {
			$is_current_component = true;

		// Next, check to see whether $component is a canonical,
		// non-translatable component name. If so, we can return its
		// corresponding slug from $bp->active_components.
		} else if ( $key = array_search( $component, $bp->active_components ) ) {
			if ( strstr( $bp->current_component, $key ) ) {
				$is_current_component = true;
			}

		// If we haven't found a match yet, check against the root_slugs
		// created by $bp->pages, as well as the regular slugs
		} else {
			foreach ( $bp->active_components as $id ) {
				// If the $component parameter does not match the current_component,
				// then move along, these are not the droids you are looking for
				if ( empty( $bp->{$id}->root_slug ) || $bp->{$id}->root_slug != $bp->current_component ) {
					continue;
				}

				if ( $id == $component ) {
					$is_current_component = true;
					break;
				}
			}
		}

	// Page template fallback check if $bp->current_component is empty
	} elseif ( !is_admin() && is_page() ) {
		global $wp_query;
		$page          = $wp_query->get_queried_object();
		$custom_fields = get_post_custom_values( '_wp_page_template', $page->ID );
		$page_template = $custom_fields[0];

		// Component name is in the page template name
		if ( !empty( $page_template ) && strstr( strtolower( $page_template ), strtolower( $component ) ) ) {
			$is_current_component = true;
		}
	}

 	return apply_filters( 'bp_is_current_component', $is_current_component, $component );
}

/**
 * Check to see whether the current page matches a given action.
 *
 * Along with bp_is_current_component() and bp_is_action_variable(), this function is mostly used
 * to help determine when to use a given screen function.
 *
 * In BP parlance, the current_action is the URL chunk that comes directly after the
 * current item slug. E.g., in
 *   http://example.com/groups/my-group/members
 * the current_action is 'members'.
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @param str $action The action being tested against
 * @return bool True if the current action matches $action
 */
function bp_is_current_action( $action = '' ) {
	if ( $action == bp_current_action() )
		return true;

	return false;
}

/**
 * Check to see whether the current page matches a given action_variable.
 *
 * Along with bp_is_current_component() and bp_is_current_action(), this function is mostly used
 * to help determine when to use a given screen function.
 *
 * In BP parlance, action_variables are an array made up of the URL chunks appearing after the
 * current_action in a URL. For example,
 *   http://example.com/groups/my-group/admin/group-settings
 * $action_variables[0] is 'group-settings'.
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @param str $action_variable The action_variable being tested against
 * @param int $position The array key you're testing against. If you don't provide a $position,
 *   the function will return true if the $action_variable is found *anywhere* in the action
 *   variables array.
 * @return bool
 */
function bp_is_action_variable( $action_variable = '', $position = false ) {
	$is_action_variable = false;

	if ( false !== $position ) {
		// When a $position is specified, check that slot in the action_variables array
		if ( $action_variable ) {
			$is_action_variable = $action_variable == bp_action_variable( $position );
		} else {
			// If no $action_variable is provided, we are essentially checking to see
			// whether the slot is empty
			$is_action_variable = !bp_action_variable( $position );
		}
	} else {
		// When no $position is specified, check the entire array
		$is_action_variable = in_array( $action_variable, (array)bp_action_variables() );
	}

	return apply_filters( 'bp_is_action_variable', $is_action_variable, $action_variable, $position );
}

function bp_is_current_item( $item = '' ) {
	if ( !empty( $item ) && $item == bp_current_item() )
		return true;

	return false;
}

function bp_is_single_item() {
	global $bp;

	if ( !empty( $bp->is_single_item ) )
		return true;

	return false;
}

function bp_is_item_admin() {
	global $bp;

	if ( !empty( $bp->is_item_admin ) )
		return true;

	return false;
}

function bp_is_item_mod() {
	global $bp;

	if ( !empty( $bp->is_item_mod ) )
		return true;

	return false;
}

function bp_is_directory() {
	global $bp;

	if ( !empty( $bp->is_directory ) )
		return true;

	return false;
}

/**
 * Checks to see if a component's URL should be in the root, not under a
 * member page:
 *
 *   Yes: http://domain.com/groups/the-group
 *   No:  http://domain.com/members/andy/groups/the-group
 *
 * @package BuddyPress Core
 * @return true if root component, else false.
 */
function bp_is_root_component( $component_name ) {
	global $bp;

	if ( !isset( $bp->active_components ) )
		return false;

	foreach ( (array) $bp->active_components as $key => $slug ) {
		if ( $key == $component_name || $slug == $component_name )
			return true;
	}

	return false;
}

/**
 * Checks if the site's front page is set to the specified BuddyPress component
 * page in wp-admin's Settings > Reading screen.
 *
 * @global BuddyPress $bp The one true BuddyPress instance
 * @global $current_blog WordPress global for the current blog
 * @param string $component Optional; Name of the component to check for.
 * @return bool True If the specified component is set to be the site's front page.
 * @since BuddyPress (1.5)
 */
function bp_is_component_front_page( $component = '' ) {
	global $bp, $current_blog;

	if ( !$component && !empty( $bp->current_component ) )
		$component = $bp->current_component;

	$path = is_main_site() ? bp_core_get_site_path() : $current_blog->path;

	if ( 'page' != get_option( 'show_on_front' ) || !$component || empty( $bp->pages->{$component} ) || $_SERVER['REQUEST_URI'] != $path )
		return false;

	return apply_filters( 'bp_is_component_front_page', ( $bp->pages->{$component}->id == get_option( 'page_on_front' ) ), $component );
}

/**
 * Is this a blog page, ie a non-BP page?
 *
 * You can tell if a page is displaying BP content by whether the current_component has been defined
 *
 * @package BuddyPress
 *
 * @return bool True if it's a non-BP page, false otherwise
 */
function bp_is_blog_page() {

	$is_blog_page = false;

	// Generally, we can just check to see that there's no current component. The one exception
	// is single user home tabs, where $bp->current_component is unset. Thus the addition
	// of the bp_is_user() check.
	if ( !bp_current_component() && !bp_is_user() )
		$is_blog_page = true;

	return apply_filters( 'bp_is_blog_page', $is_blog_page );
}

function bp_is_page( $page ) {
	if ( !bp_is_user() && bp_is_current_component( $page )  )
		return true;

	if ( 'home' == $page )
		return is_front_page();

	return false;
}

/** Components ****************************************************************/

function bp_is_active( $component ) {
	global $bp;

	if ( isset( $bp->active_components[$component] ) || 'core' == $component )
		return true;

	return false;
}

function bp_is_members_component() {
	if ( bp_is_current_component( 'members' ) )
		return true;

	return false;
}

function bp_is_profile_component() {
	if ( bp_is_current_component( 'xprofile' ) )
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

function bp_is_forums_component() {
	if ( bp_is_current_component( 'forums' ) )
		return true;

	return false;
}

function bp_is_settings_component() {
	if ( bp_is_current_component( 'settings' ) )
		return true;

	return false;
}

/** Activity ******************************************************************/

function bp_is_single_activity() {
	if ( bp_is_activity_component() && is_numeric( bp_current_action() ) )
		return true;

	return false;
}

/** User **********************************************************************/

function bp_is_my_profile() {
	if ( is_user_logged_in() && bp_loggedin_user_id() == bp_displayed_user_id() )
		$my_profile = true;
	else
		$my_profile = false;

	return apply_filters( 'bp_is_my_profile', $my_profile );
}

function bp_is_user() {
	if ( bp_displayed_user_id() && !is_404() )
		return true;

	return false;
}

function bp_is_user_activity() {
	if ( bp_is_user() && bp_is_activity_component() )
		return true;

	return false;
}

function bp_is_user_friends_activity() {

	if ( !bp_is_active( 'friends' ) )
		return false;

	$slug = bp_get_friends_slug();

	if ( empty( $slug ) )
		$slug = 'friends';

	if ( bp_is_user_activity() && bp_is_current_action( $slug ) )
		return true;

	return false;
}

function bp_is_user_groups_activity() {

	if ( !bp_is_active( 'groups' ) )
		return false;

	$slug = bp_get_groups_slug();

	if ( empty( $slug ) )
		$slug = 'groups';

	if ( bp_is_user_activity() && bp_is_current_action( $slug ) )
		return true;

	return false;
}

function bp_is_user_profile() {
	if ( bp_is_profile_component() || bp_is_current_component( 'profile' ) )
		return true;

	return false;
}

function bp_is_user_profile_edit() {
	if ( bp_is_profile_component() && bp_is_current_action( 'edit' ) )
		return true;

	return false;
}

function bp_is_user_change_avatar() {
	if ( bp_is_profile_component() && bp_is_current_action( 'change-avatar' ) )
		return true;

	return false;
}

/**
 * Is this a user's forums page?
 *
 * @package BuddyPress
 *
 * @return bool
 */
function bp_is_user_forums() {
	if ( bp_is_user() && bp_is_forums_component() )
		return true;

	return false;
}

/**
 * Is this a user's "Topics Started" page?
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return bool
 */
function bp_is_user_forums_started() {
	if ( bp_is_user_forums() && bp_is_current_action( 'topics' ) )
		return true;

	return false;
}

/**
 * Is this a user's "Replied To" page?
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return bool
 */
function bp_is_user_forums_replied_to() {
	if ( bp_is_user_forums() && bp_is_current_action( 'replies' ) )
		return true;

	return false;
}

function bp_is_user_groups() {
	if ( bp_is_user() && bp_is_groups_component() )
		return true;

	return false;
}

function bp_is_user_blogs() {
	if ( bp_is_user() && bp_is_blogs_component() )
		return true;

	return false;
}

function bp_is_user_recent_posts() {
	if ( bp_is_user_blogs() && bp_is_current_action( 'recent-posts' ) )
		return true;

	return false;
}

function bp_is_user_recent_commments() {
	if ( bp_is_user_blogs() && bp_is_current_action( 'recent-comments' ) )
		return true;

	return false;
}

function bp_is_user_friends() {
	if ( bp_is_user() && bp_is_friends_component() )
		return true;

	return false;
}

function bp_is_user_friend_requests() {
	if ( bp_is_user_friends() && bp_is_current_action( 'requests' ) )
		return true;

	return false;
}

/**
 * Is this a user's settings page?
 *
 * @package BuddyPress
 *
 * @return bool
 */
function bp_is_user_settings() {
	if ( bp_is_user() && bp_is_settings_component() )
		return true;

	return false;
}

/**
 * Is this a user's General Settings page?
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return bool
 */
function bp_is_user_settings_general() {
	if ( bp_is_user_settings() && bp_is_current_action( 'general' ) )
		return true;

	return false;
}

/**
 * Is this a user's Notification Settings page?
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return bool
 */
function bp_is_user_settings_notifications() {
	if ( bp_is_user_settings() && bp_is_current_action( 'notifications' ) )
		return true;

	return false;
}

/**
 * Is this a user's Account Deletion page?
 *
 * @package BuddyPress
 * @since BuddyPress (1.5)
 *
 * @return bool
 */
function bp_is_user_settings_account_delete() {
	if ( bp_is_user_settings() && bp_is_current_action( 'delete-account' ) )
		return true;

	return false;
}


/** Groups ******************************************************************/

function bp_is_group() {
	global $bp;

	if ( bp_is_groups_component() && isset( $bp->groups->current_group ) && $bp->groups->current_group )
		return true;

	return false;
}

function bp_is_group_home() {
	if ( bp_is_single_item() && bp_is_groups_component() && ( !bp_current_action() || bp_is_current_action( 'home' ) ) )
		return true;

	return false;
}

function bp_is_group_create() {
	if ( bp_is_groups_component() && bp_is_current_action( 'create' ) )
		return true;

	return false;
}

function bp_is_group_admin_page() {
	if ( bp_is_single_item() && bp_is_groups_component() && bp_is_current_action( 'admin' ) )
		return true;

	return false;
}

function bp_is_group_forum() {
	if ( bp_is_single_item() && bp_is_groups_component() && bp_is_current_action( 'forum' ) )
		return true;

	return false;
}

function bp_is_group_activity() {
	if ( bp_is_single_item() && bp_is_groups_component() && bp_is_current_action( 'activity' ) )
		return true;

	return false;
}

function bp_is_group_forum_topic() {
	if ( bp_is_single_item() && bp_is_groups_component() && bp_is_current_action( 'forum' ) && bp_is_action_variable( 'topic', 0 ) )
		return true;

	return false;
}

function bp_is_group_forum_topic_edit() {
	if ( bp_is_single_item() && bp_is_groups_component() && bp_is_current_action( 'forum' ) && bp_is_action_variable( 'topic', 0 ) && bp_is_action_variable( 'edit', 2 ) )
		return true;

	return false;
}

function bp_is_group_members() {
	if ( bp_is_single_item() && bp_is_groups_component() && bp_is_current_action( 'members' ) )
		return true;

	return false;
}

function bp_is_group_invites() {
	if ( bp_is_groups_component() && bp_is_current_action( 'send-invites' ) )
		return true;

	return false;
}

function bp_is_group_membership_request() {
	if ( bp_is_groups_component() && bp_is_current_action( 'request-membership' ) )
		return true;

	return false;
}

function bp_is_group_leave() {

	if ( bp_is_groups_component() && bp_is_single_item() && bp_is_current_action( 'leave-group' ) )
		return true;

	return false;
}

function bp_is_group_single() {
	if ( bp_is_groups_component() && bp_is_single_item() )
		return true;

	return false;
}

function bp_is_create_blog() {
	if ( bp_is_blogs_component() && bp_is_current_action( 'create' ) )
		return true;

	return false;
}

/** Messages ******************************************************************/

function bp_is_user_messages() {
	if ( bp_is_user() && bp_is_messages_component() )
		return true;

	return false;
}

function bp_is_messages_inbox() {
	if ( bp_is_user_messages() && ( !bp_current_action() || bp_is_current_action( 'inbox' ) ) )
		return true;

	return false;
}

function bp_is_messages_sentbox() {
	if ( bp_is_user_messages() && bp_is_current_action( 'sentbox' ) )
		return true;

	return false;
}

function bp_is_messages_compose_screen() {
	if ( bp_is_user_messages() && bp_is_current_action( 'compose' ) )
		return true;

	return false;
}

function bp_is_notices() {
	if ( bp_is_user_messages() && bp_is_current_action( 'notices' ) )
		return true;

	return false;
}

function bp_is_messages_conversation() {
	if ( bp_is_user_messages() && ( bp_is_current_action( 'view' ) ) )
		return true;

	return false;
}

function bp_is_single( $component, $callback ) {
	if ( bp_is_current_component( $component ) && ( true === call_user_func( $callback ) ) )
		return true;

	return false;
}

/** Registration **************************************************************/

function bp_is_activation_page() {
	if ( bp_is_current_component( 'activate' ) )
		return true;

	return false;
}

function bp_is_register_page() {
	if ( bp_is_current_component( 'register' ) )
		return true;

	return false;
}

/**
 * Use the above is_() functions to output a body class for each scenario
 *
 * @package BuddyPress
 * @subpackage Core Template
 *
 * @param array $wp_classes The body classes coming from WP
 * @param array $custom_classes Classes that were passed to get_body_class()
 * @return array $classes The BP-adjusted body classes
 */
function bp_the_body_class() {
	echo bp_get_the_body_class();
}
	function bp_get_the_body_class( $wp_classes, $custom_classes = false ) {

		$bp_classes = array();

		/** Pages *************************************************************/

		if ( is_front_page() )
			$bp_classes[] = 'home-page';

		if ( bp_is_directory() )
			$bp_classes[] = 'directory';

		if ( bp_is_single_item() )
			$bp_classes[] = 'single-item';

		/** Components ********************************************************/

		if ( !bp_is_blog_page() ) :
			if ( bp_is_user_profile() )
				$bp_classes[] = 'xprofile';

			if ( bp_is_activity_component() )
				$bp_classes[] = 'activity';

			if ( bp_is_blogs_component() )
				$bp_classes[] = 'blogs';

			if ( bp_is_messages_component() )
				$bp_classes[] = 'messages';

			if ( bp_is_friends_component() )
				$bp_classes[] = 'friends';

			if ( bp_is_groups_component() )
				$bp_classes[] = 'groups';

			if ( bp_is_settings_component()  )
				$bp_classes[] = 'settings';
		endif;

		/** User **************************************************************/

		if ( bp_is_user() )
			$bp_classes[] = 'bp-user';

		if ( !bp_is_directory() ) :
			if ( bp_is_user_blogs() )
				$bp_classes[] = 'my-blogs';

			if ( bp_is_user_groups() )
				$bp_classes[] = 'my-groups';

			if ( bp_is_user_activity() )
				$bp_classes[] = 'my-activity';
		endif;

		if ( bp_is_my_profile() )
			$bp_classes[] = 'my-account';

		if ( bp_is_user_profile() )
			$bp_classes[] = 'my-profile';

		if ( bp_is_user_friends() )
			$bp_classes[] = 'my-friends';

		if ( bp_is_user_messages() )
			$bp_classes[] = 'my-messages';

		if ( bp_is_user_recent_commments() )
			$bp_classes[] = 'recent-comments';

		if ( bp_is_user_recent_posts() )
			$bp_classes[] = 'recent-posts';

		if ( bp_is_user_change_avatar() )
			$bp_classes[] = 'change-avatar';

		if ( bp_is_user_profile_edit() )
			$bp_classes[] = 'profile-edit';

		if ( bp_is_user_friends_activity() )
			$bp_classes[] = 'friends-activity';

		if ( bp_is_user_groups_activity() )
			$bp_classes[] = 'groups-activity';

		if ( is_user_logged_in() )
			$bp_classes[] = 'logged-in';

		/** Messages **********************************************************/

		if ( bp_is_messages_inbox() )
			$bp_classes[] = 'inbox';

		if ( bp_is_messages_sentbox() )
			$bp_classes[] = 'sentbox';

		if ( bp_is_messages_compose_screen() )
			$bp_classes[] = 'compose';

		if ( bp_is_notices() )
			$bp_classes[] = 'notices';

		if ( bp_is_user_friend_requests() )
			$bp_classes[] = 'friend-requests';

		if ( bp_is_create_blog() )
			$bp_classes[] = 'create-blog';

		/** Groups ************************************************************/

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

		if ( bp_is_group_admin_page() ) {
			$bp_classes[] = 'group-admin';
			$bp_classes[] = bp_get_group_current_admin_tab();
		}

		if ( bp_is_group_create() ) {
			$bp_classes[] = 'group-create';
			$bp_classes[] = bp_get_groups_current_create_step();
		}

		if ( bp_is_group_home() )
			$bp_classes[] = 'group-home';

		if ( bp_is_single_activity() )
			$bp_classes[] = 'activity-permalink';

		/** Registration ******************************************************/

		if ( bp_is_register_page() )
			$bp_classes[] = 'registration';

		if ( bp_is_activation_page() )
			$bp_classes[] = 'activation';

		/** Current Component & Action ****************************************/

		if ( !bp_is_blog_page() ) {
			$bp_classes[] = bp_current_component();
			$bp_classes[] = bp_current_action();
		}

		/** Clean up***********************************************************/

		// We don't want WordPress blog classes to appear on non-blog pages.
		if ( !bp_is_blog_page() ) {

			// Observe WP custom background body class
			if ( in_array( 'custom-background', (array) $wp_classes ) )
				$bp_classes[] = 'custom-background';

			// Preserve any custom classes already set
			if ( !empty( $custom_classes ) ) {
				$wp_classes = (array) $custom_classes;
			} else {
				$wp_classes = array();
			}
		}

		// Merge WP classes with BP classes
		$classes = array_merge( (array) $bp_classes, (array) $wp_classes );

		// Remove any duplicates
		$classes = array_unique( $classes );

		return apply_filters( 'bp_get_the_body_class', $classes, $bp_classes, $wp_classes, $custom_classes );
	}
	add_filter( 'body_class', 'bp_get_the_body_class', 10, 2 );

?>