<?php
/**
 * bp_get_nav()
 * TEMPLATE TAG
 *
 * Uses the $bp->bp_nav global to render out the navigation within a BuddyPress install.
 * Each component adds to this navigation array within its own [component_name]_setup_nav() function.
 * 
 * This navigation array is the top level navigation, so it contains items such as:
 *      [Blog, Profile, Messages, Groups, Friends] ...
 *
 * The function will also analyze the current component the user is in, to determine whether
 * or not to highlight a particular nav item.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_get_loggedin_user_nav() {
	global $bp, $current_blog;
	
	/* Loop through each navigation item */
	foreach( (array) $bp->bp_nav as $nav_item ) {
		/* If the current component matches the nav item id, then add a highlight CSS class. */
		if ( $bp->active_components[$bp->current_component] == $nav_item['css_id'] ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}
		
		/* If we are viewing another person (current_userid does not equal loggedin_user->id)
		   then check to see if the two users are friends. if they are, add a highlight CSS class
		   to the friends nav item if it exists. */
		if ( !bp_is_home() && $bp->displayed_user->id ) {
			$selected = '';
			
			if ( function_exists('friends_install') ) {
				if ( $nav_item['css_id'] == $bp->friends->id ) {
					if ( friends_check_friendship( $bp->loggedin_user->id, $bp->displayed_user->id ) )
						$selected = ' class="current"';
				}
			}
		}
		
		/* echo out the final list item */
		echo '<li id="li-nav-' . $nav_item['css_id'] . '" ' . $selected . '><a id="my-' . $nav_item['css_id'] . '" href="' . $nav_item['link'] . '">' . $nav_item['name'] . '</a></li>';
	}
	
	/* Always add a log out list item to the end of the navigation */
	if ( function_exists( 'wp_logout_url' ) ) {
		echo '<li><a id="wp-logout" href="' .  wp_logout_url( site_url() . $_SERVER['REQUEST_URI'] ) . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>';		
	} else {
		echo '<li><a id="wp-logout" href="' . site_url() . '/wp-login.php?action=logout&amp;redirect_to=' . site_url() . $_SERVER['REQUEST_URI'] . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>';
	}
}
	/* DEPRECATED - use bp_get_loggedin_user_nav() */
	function bp_get_nav() { bp_get_loggedin_user_nav(); }

/**
 * bp_get_displayed_user_nav()
 * TEMPLATE TAG
 *
 * Uses the $bp->bp_users_nav global to render out the user navigation when viewing another user other than
 * yourself.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_get_displayed_user_nav() {
	global $bp;

	foreach ( $bp->bp_nav as $user_nav_item ) {
		if ( !$user_nav_item['show_for_displayed_user'] ) 
			continue;
			
		if ( $bp->current_component == $user_nav_item['css_id'] )
			$selected = ' class="current"';
		else
			$selected = '';
		
		if ( $bp->loggedin_user->domain )
			$link = str_replace( $bp->loggedin_user->domain, $bp->displayed_user->domain, $user_nav_item['link'] );
		else
			$link = $bp->displayed_user->domain . $user_nav_item['link'];
		
		echo '<li id="li-subnav-' . $user_nav_item['css_id'] . '" ' . $selected . '><a id="user-' . $user_nav_item['css_id'] . '" href="' . $link . '">' . $user_nav_item['name'] . '</a></li>';
	}	
}
	/* DEPRECATED - use bp_get_displayed_user_nav() */
	function bp_get_user_nav() { bp_get_displayed_user_nav(); }
	
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

	/***
	 * Only render this navigation when the logged in user is looking at one of their own pages, or we are using it to display nav
	 * menus for something like a group, or event.
	 */
	if ( bp_is_home() || $bp->is_single_item ) {
		if ( count( $bp->bp_options_nav[$bp->current_component] ) < 1 )
			return false;
	
		/* Loop through each navigation item */
		foreach ( $bp->bp_options_nav[$bp->current_component] as $subnav_item ) {
			if ( !$subnav_item['user_has_access'] )
				continue;
			
			/* If the current action or an action variable matches the nav item id, then add a highlight CSS class. */
			if ( $subnav_item['slug'] == $bp->current_action ) {
				$selected = ' class="current"';
			} else {
				$selected = '';
			}
			
			/* echo out the final list item */
			echo '<li id="li-subnav-' . $subnav_item['css_id'] . '" ' . $selected . '><a id="' . $subnav_item['css_id'] . '" href="' . $subnav_item['link'] . '">' . $subnav_item['name'] . '</a></li>';		
		}
	} else {
		/* If we get here we are viewing another user, so show the displayed user's nav items */
		bp_get_displayed_user_nav();
	}
}

function bp_get_options_title() {
	global $bp;
	
	if ( empty( $bp->bp_options_title ) )
		$bp->bp_options_title = __( 'Options', 'buddypress' );
	
	echo apply_filters( 'bp_get_options_title', attribute_escape( $bp->bp_options_title ) );
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
	
	if ( function_exists('bp_core_fetch_avatar') ) {
		echo apply_filters( 'bp_comment_author_avatar', bp_core_fetch_avatar( array( 'item_id' => $comment->user_id, 'type' => 'thumb' ) ) );	
	} else if ( function_exists('get_avatar') ) {
		get_avatar();
	}
}

function bp_post_author_avatar() {
	global $post;
	
	if ( function_exists('bp_core_fetch_avatar') ) {
		echo apply_filters( 'bp_post_author_avatar', bp_core_fetch_avatar( array( 'item_id' => $post->post_author, 'type' => 'thumb' ) ) );	
	} else if ( function_exists('get_avatar') ) {
		get_avatar();
	}
}

function bp_loggedin_user_avatar( $args = '' ) {
	echo bp_get_loggedin_user_avatar( $args );
}
	function bp_get_loggedin_user_avatar( $args = '' ) {
		global $bp;

		$defaults = array(
			'type' => 'thumb',
			'width' => false,
			'height' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
		
		return apply_filters( 'bp_get_loggedin_user_avatar', bp_core_fetch_avatar( array( 'item_id' => $bp->loggedin_user->id, 'type' => $type, 'width' => $width, 'height' => $height ) ) );
	} 

function bp_displayed_user_avatar( $args = '' ) {
	echo bp_get_displayed_user_avatar( $args );
}
	function bp_get_displayed_user_avatar( $args = '' ) {
		global $bp;

		$defaults = array(
			'type' => 'thumb',
			'width' => false,
			'height' => false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
		
		return apply_filters( 'bp_get_displayed_user_avatar', bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => $type, 'width' => $width, 'height' => $height ) ) );
	} 

function bp_avatar_admin_step() {
	echo bp_get_avatar_admin_step();
}
	function bp_get_avatar_admin_step() {
		global $bp;

		return apply_filters( 'bp_get_avatar_admin_step', $bp->avatar_admin->step );
	}

function bp_avatar_to_crop() {
	echo bp_get_avatar_to_crop();
}
	function bp_get_avatar_to_crop() {
		global $bp;

		return apply_filters( 'bp_get_avatar_to_crop', $bp->avatar_admin->image->url );
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
	echo apply_filters( 'bp_site_name', get_blog_option( BP_ROOT_BLOG, 'blogname' ) );
}

function bp_is_home() {
	global $bp;
	
	if ( is_user_logged_in() && $bp->loggedin_user->id == $bp->displayed_user->id )
		return true;
		
	return false;
}

function bp_last_activity( $user_id = false, $echo = true ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->displayed_user->id;
	
	$last_activity = bp_core_get_last_activity( get_usermeta( $user_id, 'last_activity' ), __('active %s ago', 'buddypress') );

	if ( $echo )
		echo apply_filters( 'bp_last_activity', $last_activity );
	else
		return apply_filters( 'bp_last_activity', $last_activity );
}

function bp_user_link() {
	global $bp;
	
	echo apply_filters( 'bp_the_avatar_thumbnail', $bp->displayed_user->domain );
}

function bp_get_loggedin_user_link() {
	global $bp;
	
	return $bp->loggedin_user->domain;
}

function bp_get_displayed_user_link() {
	global $bp;
	
	return $bp->displayed_user->domain;
}

function bp_core_get_wp_profile() {
	global $bp;
	
	$ud = get_userdata( $bp->displayed_user->id );
?>

<div class="bp-widget wp-profile">
	<h4><?php _e( 'My Profile' ) ?></h4>

	<table class="wp-profile-fields">
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
	if ( function_exists($component_name . '_install') )
		return true;
	
	return false;
}

function bp_format_time( $time, $just_date = false ) {
	$date = date( get_option('date_format'), $time );
	
	if ( !$just_date ) {
		$date .= ' ' . __( 'at', 'buddypress' ) . date( ' ' . get_option('time_format'), $time );
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
		$nametext = sprintf( $nametext, $bp->displayed_user->fullname );
		if ( $echo )
			echo apply_filters( 'bp_word_or_name', $nametext );
		else
			return apply_filters( 'bp_word_or_name', $nametext );
	}
}

function bp_your_or_their( $capitalize = true, $echo = true ) {
	global $bp;
	
	if ( $capitalize )
		$yourtext = bp_core_ucfirst($yourtext);
	
	if ( $bp->displayed_user->id == $bp->loggedin_user->id ) {
		if ( $echo )
			echo apply_filters( 'bp_your_or_their', $yourtext );
		else
			return apply_filters( 'bp_your_or_their', $yourtext );
	} else {
		if ( $echo )
			echo apply_filters( 'bp_your_or_their', $theirtext );
		else
			return apply_filters( 'bp_your_or_their', $theirtext );
	}
}

function bp_loggedinuser_link() {
	global $bp;
	
	if ( $link = bp_core_get_userlink( $bp->loggedin_user->id ) ) {
		echo apply_filters( 'bp_loggedinuser_link', $link );
	}
}

function bp_get_plugin_sidebar() {
	locate_template( array( 'plugin-sidebar.php' ), true );
}

function bp_is_blog_page() {
	global $bp, $is_member_page;

	if ( $bp->current_component == BP_HOME_BLOG_SLUG )
		return true;

	if ( !$is_member_page && !in_array( $bp->current_component, $bp->root_components ) )
		return true;

	return false;
}

function bp_page_title() {
	echo bp_get_page_title();
}

function bp_get_page_title() {
	global $bp, $post, $wp_query, $current_blog;

	if ( is_home() && bp_is_page( 'home' ) ) {
		$title = __( 'Home', 'buddypress' );
	} else if ( bp_is_blog_page() ) {
		if ( is_single() ) {
			$title = __( 'Blog &#8212; ' . $post->post_title, 'buddypress' );
		} else if ( is_category() ) {
			$title = __( 'Blog &#8212; Categories &#8212; ' . ucwords( $wp_query->query_vars['category_name'] ), 'buddypress' );				
		} else if ( is_tag() ) {
			$title = __( 'Blog &#8212; Tags &#8212; ' . ucwords( $wp_query->query_vars['tag'] ), 'buddypress' );				
		} else
			$title = __( 'Blog', 'buddypress' );

	} else if ( !empty( $bp->displayed_user->fullname ) ) {
	 	$title = strip_tags( $bp->displayed_user->fullname . ' &#8212; ' . ucwords( $bp->current_component ) . ' &#8212; ' . $bp->bp_options_nav[$bp->current_component][$bp->current_action]['name'] );
	} else if ( $bp->is_single_item ) {
		$title = ucwords( $bp->current_component ) . ' &#8212; ' . $bp->bp_options_title;
	} else if ( $bp->is_directory ) {
		if ( !$bp->current_component )
			$title = sprintf( __( '%s Directory', 'buddypress' ), ucwords( BP_MEMBERS_SLUG ) );
		else
			$title = sprintf( __( '%s Directory', 'buddypress' ), ucwords( $bp->current_component ) );
	} else {
		global $post;
		$title = get_the_title($post->ID);
	}
	
	if ( defined( 'BP_ENABLE_MULTIBLOG' ) ) {
		$blog_title = get_blog_option( $current_blog->blog_id, 'blogname' );
	} else {
		$blog_title = get_blog_option( BP_ROOT_BLOG, 'blogname' );		
	}
	
	return apply_filters( 'bp_page_title', $blog_title . ' &#8212; ' . attribute_escape( $title ), attribute_escape( $title ) );
}

function bp_styles() {
	do_action( 'bp_styles' );
	wp_print_styles();
}

function bp_is_page($page) {
	global $bp;
	
	if ( $bp->displayed_user->id || $bp->is_single_item )
		return false;
	
	if ( $page == $bp->current_component || ( is_home() && $page == 'home' && $bp->current_component == $bp->default_component ) || ( $page == BP_MEMBERS_SLUG && !$bp->current_component ) )
		return true;
	
	return false;
}

function bp_has_custom_signup_page() {
	if ( file_exists( WP_CONTENT_DIR . '/themes/' . get_blog_option( BP_ROOT_BLOG, 'template') . '/register.php') )
		return true;

	if ( file_exists( WP_CONTENT_DIR . '/themes/' . get_blog_option( BP_ROOT_BLOG, 'template') . '/registration/register.php') )
		return true;
		
	return false;
}

function bp_signup_page( $deprecated = true ) {
	if ( !$deprecated )
		return bp_get_signup_page();
	else
		echo bp_get_signup_page();
}
	function bp_get_signup_page() {
		global $bp;
		
		if ( bp_has_custom_signup_page() ) {
				return apply_filters( 'bp_get_signup_page', $bp->root_domain . '/' . BP_REGISTER_SLUG );
		} else {
				return apply_filters( 'bp_get_signup_page', $bp->root_domain . '/wp-signup.php' );
		}
	}

function bp_has_custom_activation_page() {
	if ( file_exists( WP_CONTENT_DIR . '/themes/' . get_blog_option( BP_ROOT_BLOG, 'template') . '/activate.php') )
		return true;

	if ( file_exists( WP_CONTENT_DIR . '/themes/' . get_blog_option( BP_ROOT_BLOG, 'template') . '/registration/activate.php') )
		return true;
	
	return false;
}

function bp_activation_page( $echo = true ) {
	global $bp;
	
	if ( bp_has_custom_activation_page() ) {
		if ( $echo )
			echo $bp->root_domain . '/' . BP_ACTIVATION_SLUG;
		else
			return $bp->root_domain . '/' . BP_ACTIVATION_SLUG;
	} else {
		if ( $echo )
			echo $bp->root_domain . '/wp-activate.php';
		else
			return $bp->root_domain . '/wp-activate.php';
	}
}

function bp_search_form_action() {
	global $bp;
	
	return apply_filters( 'bp_search_form_action', $bp->root_domain . '/search' );
}

function bp_search_form_type_select() {
	// Eventually this won't be needed and a page will be built to integrate all search results.
	$selection_box = '<select name="search-which" id="search-which" style="width: auto">';
	
	if ( function_exists( 'xprofile_install' ) ) {
		$selection_box .= '<option value="members">' . __( 'Members', 'buddypress' ) . '</option>';
	}
	
	if ( function_exists( 'groups_install' ) ) {
		$selection_box .= '<option value="groups">' . __( 'Groups', 'buddypress' ) . '</option>';
	}

	if ( function_exists( 'bp_forums_setup' ) && !(int) get_site_option( 'bp-disable-forum-directory' ) ) {
		$selection_box .= '<option value="forums">' . __( 'Forums', 'buddypress' ) . '</option>';
	}
	
	if ( function_exists( 'bp_blogs_install' ) ) {
		$selection_box .= '<option value="blogs">' . __( 'Blogs', 'buddypress' ) . '</option>';
	}
			
	$selection_box .= '</select>';
	
	return apply_filters( 'bp_search_form_type_select', $selection_box );
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

function bp_profile_wire_can_post() {
	global $bp;
	
	if ( bp_is_home() )
		return true;
	
	if ( function_exists('friends_install') ) {
		if ( friends_check_friendship( $bp->loggedin_user->id, $bp->displayed_user->id ) )
			return true;
		else
			return false;
	} 
	
	return true;
}

function bp_nav_items() {
	global $bp;
	// This is deprecated, you should put these navigation items in your template header.php for easy editing.
?>
	<li<?php if ( bp_is_page( 'home' ) ) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>" title="<?php _e( 'Home', 'buddypress' ) ?>"><?php _e( 'Home', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_page( BP_HOME_BLOG_SLUG ) ) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>/<?php echo BP_HOME_BLOG_SLUG ?>" title="<?php _e( 'Blog', 'buddypress' ) ?>"><?php _e( 'Blog', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_page( BP_MEMBERS_SLUG ) ) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>/<?php echo BP_MEMBERS_SLUG ?>" title="<?php _e( 'Members', 'buddypress' ) ?>"><?php _e( 'Members', 'buddypress' ) ?></a></li>
	
	<?php if ( function_exists( 'groups_install' ) ) { ?>
		<li<?php if ( bp_is_page( $bp->groups->slug ) ) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>/<?php echo $bp->groups->slug ?>" title="<?php _e( 'Groups', 'buddypress' ) ?>"><?php _e( 'Groups', 'buddypress' ) ?></a></li>
	<?php } ?>
	
	<?php if ( function_exists( 'bp_blogs_install' ) ) { ?>
		<li<?php if ( bp_is_page( $bp->blogs->slug ) ) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>/<?php echo $bp->blogs->slug ?>" title="<?php _e( 'Blogs', 'buddypress' ) ?>"><?php _e( 'Blogs', 'buddypress' ) ?></a></li>
	<?php } ?>
<?php
	do_action( 'bp_nav_items' );
}

function bp_custom_profile_boxes() {
	do_action( 'bp_custom_profile_boxes' );
}

function bp_custom_profile_sidebar_boxes() {
	do_action( 'bp_custom_profile_sidebar_boxes' );
}

function bp_get_userbar( $hide_on_directory = true ) {
	global $bp;
	
	if ( $hide_on_directory && $bp->is_directory )
		return false;
	
	locate_template( array( 'userbar.php' ), true );
}

function bp_get_optionsbar( $hide_on_directory = true ) {
	global $bp;
	
	if ( $hide_on_directory && $bp->is_directory )
		return false;
		
	locate_template( array( 'optionsbar.php' ), true );
}

function bp_is_directory() {
	global $bp;
	
	return $bp->is_directory;
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
function bp_create_excerpt( $text, $excerpt_length = 55, $filter_shortcodes = true ) { // Fakes an excerpt if needed
	$text = str_replace(']]>', ']]&gt;', $text);

	if ( $filter_shortcodes )
		$text = preg_replace( '|\[(.+?)\](.+?\[/\\1\])?|s', '', $text );

	$words = explode(' ', $text, $excerpt_length + 1);
	if (count($words) > $excerpt_length) {
		array_pop($words);
		array_push($words, '[...]');
		$text = implode(' ', $words);
	}
	
	return apply_filters( 'bp_create_excerpt', $text );
}
add_filter( 'bp_create_excerpt', 'wptexturize' );
add_filter( 'bp_create_excerpt', 'convert_smilies' );
add_filter( 'bp_create_excerpt', 'convert_chars' );
add_filter( 'bp_create_excerpt', 'wpautop' );
add_filter( 'bp_create_excerpt', 'wp_trim_excerpt' );
add_filter( 'bp_create_excerpt', 'force_balance_tags' );

/**
 * bp_is_serialized()
 *
 * Checks to see if the data passed has been serialized.
 * 
 * @package BuddyPress Core
 * @param $data str The data that will be checked
 * @return bool false if the data is not serialized
 * @return bool true if the data is serialized
 */
function bp_is_serialized( $data ) {
   if ( '' == trim($data) ) {
      return false;
   }

   if ( preg_match( "/^(i|s|a|o|d)(.*);/si", $data ) ) {
      return true;
   }

   return false;
}

/*** Signup form template tags **********************/

function bp_signup_username_value() {
	echo bp_get_signup_username_value();
}
	function bp_get_signup_username_value() {
		return apply_filters( 'bp_get_signup_username_value', $_POST['signup_username'] );
	}

function bp_signup_email_value() {
	echo bp_get_signup_email_value();
}
	function bp_get_signup_email_value() {
		return apply_filters( 'bp_get_signup_email_value', $_POST['signup_email'] );
	}

function bp_signup_with_blog_value() {
	echo bp_get_signup_with_blog_value();
}
	function bp_get_signup_with_blog_value() {
		return apply_filters( 'bp_get_signup_with_blog_value', $_POST['signup_with_blog'] );
	}
	
function bp_signup_blog_url_value() {
	echo bp_get_signup_blog_url_value();
}
	function bp_get_signup_blog_url_value() {
		return apply_filters( 'bp_get_signup_blog_url_value', $_POST['signup_blog_url'] );
	}

function bp_signup_blog_title_value() {
	echo bp_get_signup_blog_title_value();
}
	function bp_get_signup_blog_title_value() {
		return apply_filters( 'bp_get_signup_blog_title_value', $_POST['signup_blog_title'] );
	}

function bp_signup_blog_privacy_value() {
	echo bp_get_signup_blog_privacy_value();
}
	function bp_get_signup_blog_privacy_value() {
		return apply_filters( 'bp_get_signup_blog_privacy_value', $_POST['signup_blog_privacy'] );
	}

function bp_signup_avatar_dir_value() {
	echo bp_get_signup_avatar_dir_value();
}
	function bp_get_signup_avatar_dir_value() {
		global $bp;
		
		return apply_filters( 'bp_get_signup_avatar_dir_value', $bp->signup->avatar_dir );
	}

function bp_current_signup_step() {
	echo bp_get_current_signup_step();
}
	function bp_get_current_signup_step() {
		global $bp;
		
		return $bp->signup->step;
	}

function bp_signup_avatar( $args = '' ) {
	echo bp_get_signup_avatar( $args = '' );
}
	function bp_get_signup_avatar( $args = '' ) {
		global $bp;
		
		$defaults = array(
			'size' => BP_AVATAR_FULL_WIDTH,
			'class' => 'avatar',
			'alt' => __( 'Your Avatar', 'buddypress' )			
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
		
		$signup_avatar_dir = ( !empty( $_POST['signup_avatar_dir'] ) ) ? $_POST['signup_avatar_dir'] : $bp->signup->avatar_dir;

		if ( empty( $signup_avatar_dir ) ) {
			if ( empty( $bp->grav_default->user ) ) {
				$default_grav = 'wavatar';
			} else if ( 'mystery' == $bp->grav_default->user ) {
				$default_grav = BP_PLUGIN_URL . '/bp-core/images/mystery-man.jpg';
			} else {
				$default_grav = $bp->grav_default->user;
			}
		
			$gravatar_url = apply_filters( 'bp_gravatar_url', 'http://www.gravatar.com/avatar/' );
			return apply_filters( 'bp_get_signup_avatar', '<img src="' . $gravatar_url . md5( $_POST['signup_email'] ) . '?d=' . $default_grav . '&amp;s=' . $size ) . '" width="' . $size . '" height="' . $size . '" alt="' . $alt . '" class="' . $class . '" />';
		} else {
			return apply_filters( 'bp_get_signup_avatar', bp_core_fetch_avatar( array( 'item_id' => $signup_avatar_dir, 'object' => 'signup', 'avatar_dir' => 'avatars/signups', 'type' => 'full', 'width' => $size, 'height' => $size, 'alt' => $alt, 'class' => $class ) ) );
		}
	}

function bp_signup_allowed() {
	echo bp_get_signup_allowed();
}
	function bp_get_signup_allowed() {
		return get_site_option( 'registration' );
	}

function bp_account_was_activated() {
	global $bp;
	
	return $bp->activation_complete;
}


/*** CUSTOM LOOP TEMPLATE CLASSES *******************/

class BP_Core_Members_Template {
	var $current_member = -1;
	var $member_count;
	var $members;
	var $member;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_member_count;
	
	function bp_core_members_template( $type, $per_page, $max ) {
		global $bp, $bp_the_member_query;

		$this->pag_page = isset( $_REQUEST['upage'] ) ? intval( $_REQUEST['upage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;
				
		if ( isset( $_REQUEST['s'] ) && '' != $_REQUEST['s'] && $type != 'random' ) {
			$this->members = BP_Core_User::search_users( $_REQUEST['s'], $this->pag_num, $this->pag_page );
		} else if ( isset( $_REQUEST['letter'] ) && '' != $_REQUEST['letter'] ) {
			$this->members = BP_Core_User::get_users_by_letter( $_REQUEST['letter'], $this->pag_num, $this->pag_page );
		} else {
			switch ( $type ) {
				case 'random':
					$this->members = BP_Core_User::get_random_users( $this->pag_num, $this->pag_page );
					break;
				
				case 'newest':
					$this->members = BP_Core_User::get_newest_users( $this->pag_num, $this->pag_page );
					break;

				case 'popular':
					$this->members = BP_Core_User::get_popular_users( $this->pag_num, $this->pag_page );
					break;	

				case 'online':
					$this->members = BP_Core_User::get_online_users( $this->pag_num, $this->pag_page );
					break;
					
				case 'alphabetical':
					$this->members = BP_Core_User::get_alphabetical_users( $this->pag_num, $this->pag_page );
					break;
				
				case 'active': default:
					$this->members = BP_Core_User::get_active_users( $this->pag_num, $this->pag_page );
					break;					
			}
		}
		
		if ( !$max || $max >= (int)$this->members['total'] )
			$this->total_member_count = (int)$this->members['total'];
		else
			$this->total_member_count = (int)$max;

		$this->members = $this->members['users'];

		if ( $max ) {
			if ( $max >= count($this->members) )
				$this->member_count = count($this->members);
			else
				$this->member_count = (int)$max;
		} else {
			$this->member_count = count($this->members);
		}
		
		if ( (int) $this->total_member_count && (int) $this->pag_num ) { 
			$this->pag_links = paginate_links( array(
				'base' => add_query_arg( 'upage', '%#%' ),
				'format' => '',
				'total' => ceil( (int) $this->total_member_count / (int) $this->pag_num ),
				'current' => (int) $this->pag_page,
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
				'mid_size' => 1
			));		
		}
	}
	
	function has_members() {
		if ( $this->member_count )
			return true;
		
		return false;
	}
	
	function next_member() {
		$this->current_member++;
		$this->member = $this->members[$this->current_member];
		
		return $this->member;
	}
	
	function rewind_members() {
		$this->current_member = -1;
		if ( $this->member_count > 0 ) {
			$this->member = $this->members[0];
		}
	}
	
	function site_members() { 
		if ( $this->current_member + 1 < $this->member_count ) {
			return true;
		} elseif ( $this->current_member + 1 == $this->member_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_members();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_member() {
		global $member, $bp;

		$this->in_the_loop = true;
		$this->member = $this->next_member();
		$user_id = $this->member->user_id;
		$user_registered = $this->member->user_registered;

		if ( !$this->member = wp_cache_get( 'bp_user_' . $user_id, 'bp' ) ) {
			$this->member = new BP_Core_User( $user_id );
			wp_cache_set( 'bp_user_' . $user_id, $this->member, 'bp' );
		}
		
		if ( $user_registered )
			$this->member->user_registered = $user_registered;
			
		if ( 0 == $this->current_member ) // loop has just started
			do_action('loop_start');
	}
}

function bp_rewind_site_members() {
	global $site_members_template;
	
	return $site_members_template->rewind_members();
}

function bp_has_site_members( $args = '' ) {
	global $bp, $site_members_template;

	$defaults = array(
		'type' => 'active',
		'per_page' => 10,
		'max' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// type: active ( default ) | random | newest | popular | online | alphabetical
	
	if ( $max ) {
		if ( $per_page > $max )
			$per_page = $max;
	}

	$site_members_template = new BP_Core_Members_Template( $type, $per_page, $max );
	
	return $site_members_template->has_members();
}

function bp_the_site_member() {
	global $site_members_template;
	return $site_members_template->the_member();
}

function bp_site_members() {
	global $site_members_template;
	return $site_members_template->site_members();
}

function bp_site_members_pagination_count() {
	global $bp, $site_members_template;
	
	$from_num = intval( ( $site_members_template->pag_page - 1 ) * $site_members_template->pag_num ) + 1;
	$to_num = ( $from_num + ( $site_members_template->pag_num - 1 ) > $site_members_template->total_member_count ) ? $site_members_template->total_member_count : $from_num + ( $site_members_template->pag_num - 1) ;

	echo sprintf( __( 'Viewing member %d to %d (of %d members)', 'buddypress' ), $from_num, $to_num, $site_members_template->total_member_count ); ?> &nbsp;
	<span class="ajax-loader"></span><?php
}

function bp_site_members_pagination_links() {
	echo bp_get_site_members_pagination_links();
}
	function bp_get_site_members_pagination_links() {
		global $site_members_template;
		
		return apply_filters( 'bp_get_site_members_pagination_links', $site_members_template->pag_links );
	}

function bp_the_site_member_user_id() { 
	echo bp_get_the_site_member_user_id(); 
}
	function bp_get_the_site_member_user_id() { 
		global $site_members_template; 

		return apply_filters( 'bp_get_the_site_member_user_id', $site_members_template->member->id ); 
	}

function bp_the_site_member_avatar() {
	echo apply_filters( 'bp_the_site_member_avatar', bp_get_the_site_member_avatar() );
}
	function bp_get_the_site_member_avatar() { 
		global $site_members_template; 

		return apply_filters( 'bp_get_the_site_member_avatar', $site_members_template->member->avatar_thumb );
	}

function bp_the_site_member_link() {
	echo bp_get_the_site_member_link();
}
	function bp_get_the_site_member_link() {
		global $site_members_template;

		echo apply_filters( 'bp_get_the_site_member_link', $site_members_template->member->user_url );
	}

function bp_the_site_member_name() {
	echo apply_filters( 'bp_the_site_member_name', bp_get_the_site_member_name() );
}
	function bp_get_the_site_member_name() {
		global $site_members_template;

		return apply_filters( 'bp_get_the_site_member_name', $site_members_template->member->fullname );
	}
	add_filter( 'bp_get_the_site_member_name', 'wp_filter_kses' );
	add_filter( 'bp_get_the_site_member_name', 'stripslashes' );

function bp_the_site_member_last_active() {
	echo bp_get_the_site_member_last_active();
}
	function bp_get_the_site_member_last_active() {
		global $site_members_template;

		return apply_filters( 'bp_the_site_member_last_active', $site_members_template->member->last_active );
	}

function bp_the_site_member_registered() {
	echo bp_get_the_site_member_registered();
}
	function bp_get_the_site_member_registered() {
		global $site_members_template;

		return apply_filters( 'bp_the_site_member_last_active', strtotime( $site_members_template->member->user_registered ) );
	}
	
function bp_the_site_member_add_friend_button() {
	global $site_members_template;
	
	if ( function_exists( 'bp_add_friend_button' ) ) {
		echo bp_add_friend_button( $site_members_template->member->id );
	}
}

function bp_the_site_member_total_friend_count() {
	global $site_members_template;
	
	if ( !(int) $site_members_template->member->total_friends )
		return false;
	
	echo bp_get_the_site_member_total_friend_count();
}
	function bp_get_the_site_member_total_friend_count() {
		global $site_members_template;

		if ( !(int) $site_members_template->member->total_friends )
			return false;

		if ( 1 == (int) $site_members_template->member->total_friends )
			return apply_filters( 'bp_get_the_site_member_total_friend_count', sprintf( __( '%d friend', 'buddypress' ), (int) $site_members_template->member->total_friends ) );
		else
			return apply_filters( 'bp_get_the_site_member_total_friend_count', sprintf( __( '%d friends', 'buddypress' ), (int) $site_members_template->member->total_friends ) );		
	}
	
function bp_the_site_member_random_profile_data() {
	global $site_members_template;

	if ( function_exists( 'xprofile_get_random_profile_data' ) ) { ?>
		<?php $random_data = xprofile_get_random_profile_data( $site_members_template->member->id, true ); ?>
			<strong><?php echo wp_filter_kses( $random_data[0]->name ) ?></strong>
			<?php echo wp_filter_kses( $random_data[0]->value ) ?>
	<?php }
}

function bp_the_site_member_hidden_fields() {
	if ( isset( $_REQUEST['s'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . attribute_escape( $_REQUEST['s'] ) . '" name="search_terms" />';
	}

	if ( isset( $_REQUEST['letter'] ) ) {
		echo '<input type="hidden" id="selected_letter" value="' . attribute_escape( $_REQUEST['letter'] ) . '" name="selected_letter" />';
	}

	if ( isset( $_REQUEST['members_search'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . attribute_escape( $_REQUEST['members_search'] ) . '" name="search_terms" />';
	}
}

function bp_directory_members_search_form() {
	global $bp; ?>
	<form action="" method="get" id="search-members-form">
		<label><input type="text" name="s" id="members_search" value="<?php if ( isset( $_GET['s'] ) ) { echo attribute_escape( $_GET['s'] ); } else { _e( 'Search anything...', 'buddypress' ); } ?>"  onfocus="if (this.value == '<?php _e( 'Search anything...', 'buddypress' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php _e( 'Search anything...', 'buddypress' ) ?>';}" /></label>
		<input type="submit" id="members_search_submit" name="members_search_submit" value="<?php _e( 'Search', 'buddypress' ) ?>" />
		<?php wp_nonce_field( 'directory_members', '_wpnonce-member-filter' ) ?>
	</form>
<?php
}

function bp_home_blog_url() {
	global $bp, $current_blog;
	
	if ( defined( 'BP_ENABLE_MULTIBLOG' ) ) {
		$blog_id = $current_blog->blog_id;
	} else {
		$blog_id = BP_ROOT_BLOG;
	}

	if ( 'bphome' == get_blog_option( $blog_id, 'template' ) )
		echo $bp->root_domain . '/' . BP_HOME_BLOG_SLUG;
	else
		echo $bp->root_domain;
}


/* Template functions for fetching globals, without querying the DB again
   also means we dont have to use the $bp variable in the template (looks messy) */

function bp_displayed_user_id() {
	global $bp;
	return apply_filters( 'bp_displayed_user_id', $bp->displayed_user->id );
}
	function bp_current_user_id() { return bp_displayed_user_id(); }

function bp_loggedin_user_id() {
	global $bp;
	return apply_filters( 'bp_loggedin_user_id', $bp->loggedin_user->id );
}

function bp_displayed_user_domain() {
	global $bp;
	return apply_filters( 'bp_displayed_user_domain', $bp->displayed_user->domain );
}

function bp_loggedin_user_domain() {
	global $bp;
	return apply_filters( 'bp_loggedin_user_domain', $bp->loggedin_user->domain );
}

function bp_user_fullname() {
	global $bp;
	echo apply_filters( 'bp_user_fullname', $bp->displayed_user->fullname );
}
	function bp_displayed_user_fullname() {
		return bp_user_fullname();
	}

function bp_loggedin_user_fullname() {
	echo bp_get_loggedin_user_fullname();
}
	function bp_get_loggedin_user_fullname() {
		global $bp;
		return apply_filters( 'bp_get_loggedin_user_fullname', $bp->loggedin_user->fullname );	
	}

function bp_current_component() {
	global $bp;
	return apply_filters( 'bp_current_component', $bp->current_component );		
}

function bp_current_action() {
	global $bp;
	return apply_filters( 'bp_current_action', $bp->current_action );		
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
		
		return $bp->root_domain;
	}

/* Template is_() functions to determine the current page */

function bp_is_profile() {
	global $bp;
	
	if ( BP_XPROFILE_SLUG == $bp->current_component )
		return true;
	
	return false;
}

function bp_is_activity() {
	global $bp;
	
	if ( BP_ACTIVITY_SLUG == $bp->current_component )
		return true;
	
	return false;	
}

function bp_is_blogs() {
	global $bp;
	
	if ( BP_BLOGS_SLUG == $bp->current_component )
		return true;
	
	return false;	
}

function bp_is_wire() {
	global $bp;
	
	if ( BP_WIRE_SLUG == $bp->current_action || in_array( BP_WIRE_SLUG, (array)$bp->action_variables ) )
		return true;
	
	return false;	
}

function bp_is_messages() {
	global $bp;
	
	if ( BP_MESSAGES_SLUG == $bp->current_component )
		return true;
	
	return false;	
}

function bp_is_friends() {
	global $bp;
	
	if ( BP_FRIENDS_SLUG == $bp->current_component )
		return true;
	
	return false;	
}

function bp_is_groups() {
	global $bp;
	
	if ( BP_GROUPS_SLUG == $bp->current_component )
		return true;
	
	return false;	
}

function bp_is_settings() {
	global $bp;
	
	if ( BP_SETTINGS_SLUG == $bp->current_component )
		return true;
	
	return false;	
}

function bp_is_my_activity() {
	global $bp;
	
	if ( BP_ACTIVITY_SLUG == $bp->current_component && ( !$bp->current_action || 'my-activity' == $bp->current_action ) )
		return true;
	
	return false;
}	

function bp_is_friends_activity() {
	global $bp;
	
	if ( BP_ACTIVITY_SLUG == $bp->current_component && 'my-friends' == $bp->current_action )
		return true;
	
	return false;	
}

function bp_is_profile_edit() {
	global $bp;
	
	if ( BP_XPROFILE_SLUG == $bp->current_component && 'edit' == $bp->current_action )
		return true;
	
	return false;
}

function bp_is_change_avatar() {
	global $bp;
	
	if ( BP_XPROFILE_SLUG == $bp->current_component && 'change-avatar' == $bp->current_action )
		return true;
	
	return false;	
}

function bp_is_profile_wire() {
	global $bp;
	
	if ( BP_XPROFILE_SLUG == $bp->current_component && 'wire' == $bp->current_action )
		return true;
	
	return false;	
}

function bp_is_my_groups() {
	global $bp;
	
	if ( BP_GROUPS_SLUG == $bp->current_component && ( !$bp->current_action || 'my-groups' == $bp->current_action ) )
		return true;
	
	return false;		
}

function bp_is_group_home() {
	global $bp;
	
	if ( BP_GROUPS_SLUG == $bp->current_component && $bp->is_single_item && ( !$bp->current_action || 'home' == $bp->current_action ) )
		return true;
	
	return false;	
}

function bp_is_group_create() {
	global $bp;
	
	if ( BP_GROUPS_SLUG == $bp->current_component && 'create' == $bp->current_action )
		return true;
	
	return false;
}


function bp_is_group_admin_page() {
	global $bp;
	
	if ( BP_GROUPS_SLUG == $bp->current_component && $bp->is_single_item && 'admin' == $bp->current_action )
		return true;
	
	return false;	
}

function bp_is_group_wire() {
	global $bp;
	
	if ( BP_GROUPS_SLUG == $bp->current_component && $bp->is_single_item && 'wire' == $bp->current_action )
		return true;
	
	return false;		
}

function bp_is_group_forum() {
	global $bp;
	
	if ( BP_GROUPS_SLUG == $bp->current_component && $bp->is_single_item && 'forum' == $bp->current_action )
		return true;
	
	return false;		
}

function bp_is_group_forum_topic() {
	global $bp;
	
	if ( BP_GROUPS_SLUG == $bp->current_component && $bp->is_single_item && 'forum' == $bp->current_action && 'topic' == $bp->action_variables[0] )
		return true;
	
	return false;	
}

function bp_is_group_members() {
	global $bp;
	
	if ( BP_GROUPS_SLUG == $bp->current_component && $bp->is_single_item && 'members' == $bp->current_action )
		return true;
	
	return false;	
}

function bp_is_group_invites() {
	global $bp;
	
	if ( BP_GROUPS_SLUG == $bp->current_component && 'invites' == $bp->current_action )
		return true;
	
	return false;		
}

function bp_is_group_leave() {
	global $bp;
	
	if ( BP_GROUPS_SLUG == $bp->current_component && $bp->is_single_item && 'leave-group' == $bp->current_action )
		return true;
	
	return false;	
}

function bp_is_my_blogs() {
	global $bp;
	
	if ( BP_BLOGS_SLUG == $bp->current_component && ( !$bp->current_action || 'my-blogs' == $bp->current_action ) )
		return true;
	
	return false;		
}

function bp_is_recent_posts() {
	global $bp;
	
	if ( BP_BLOGS_SLUG == $bp->current_component && 'recent-posts' == $bp->current_action )
		return true;
	
	return false;	
}

function bp_is_recent_commments() {
	global $bp;
	
	if ( BP_BLOGS_SLUG == $bp->current_component && 'recent-comments' == $bp->current_action )
		return true;
	
	return false;		
}

function bp_is_create_blog() {
	global $bp;
	
	if ( BP_BLOGS_SLUG == $bp->current_component && 'create-a-blog' == $bp->current_action )
		return true;
	
	return false;	
}

function bp_is_friends_screen() {
	global $bp;
	
	if ( BP_FRIENDS_SLUG == $bp->current_component && ( !$bp->current_action || 'my-friends' == $bp->current_action ) )
		return true;
	
	return false;	
}

function bp_is_friend_requests() {
	global $bp;
	
	if ( BP_FRIENDS_SLUG == $bp->current_component && 'requests' == $bp->current_action )
		return true;
	
	return false;	
}

function bp_is_inbox() {
	global $bp;
	
	if ( BP_MESSAGES_SLUG == $bp->current_component && ( !$bp->current_action || 'inbox' == $bp->current_action ) )
		return true;
	
	return false;	
}

function bp_is_sentbox() {
	global $bp;
	
	if ( BP_MESSAGES_SLUG == $bp->current_component && 'sentbox' == $bp->current_action )
		return true;
	
	return false;		
}


function bp_is_notices() {
	global $bp;
	
	if ( BP_MESSAGES_SLUG == $bp->current_component && 'notices' == $bp->current_action )
		return true;
	
	return false;	
}


function bp_is_compose_screen() {
	global $bp;
	
	if ( BP_MESSAGES_SLUG == $bp->current_component && 'compose' == $bp->current_action )
		return true;
	
	return false;	
}

function bp_is_site_item() {
	global $bp;
	
	if ( $bp->is_single_item )
		return true;
	
	return false;
}

function bp_is_activation_page() {
	global $bp;
	
	if ( BP_ACTIVATION_SLUG == $bp->current_component )
		return true;
	
	return false;	
}

function bp_is_register_page() {
	global $bp;
	
	if ( BP_REGISTER_SLUG == $bp->current_component )
		return true;
	
	return false;	
}

/* Use the above is_() functions to output a body class for each page */

function bp_the_body_class() {
	echo bp_get_the_body_class();
}
	function bp_get_the_body_class( $wp_classes, $custom_classes ) {
		global $bp;

		if ( bp_is_blog_page() && is_front_page() && $bp->current_component != BP_HOME_BLOG_SLUG ) 
			$bp_classes[] = 'home-page'; 
		
		if ( bp_is_blog_page() || bp_is_register_page() || bp_is_activation_page() )
			$bp_classes[] = 'blog-page';
		
		if ( !bp_is_blog_page() && !bp_is_register_page() && !bp_is_activation_page() )
			$bp_classes[] = 'internal-page';
		
		if ( bp_is_directory() )
			$bp_classes[] = 'directory';
			
		if ( bp_is_profile() && !bp_is_blog_page() )
			$bp_classes[] = 'profile';
		
		if ( bp_is_activity() && !bp_is_blog_page()  )
			$bp_classes[] = 'activity';		

		if ( bp_is_blogs() && !bp_is_blog_page()  )
			$bp_classes[] = 'blogs';

		if ( bp_is_wire() && !bp_is_blog_page()  )
			$bp_classes[] = 'wire';

		if ( bp_is_messages() && !bp_is_blog_page()  )
			$bp_classes[] = 'messages';					
		
		if ( bp_is_friends() && !bp_is_blog_page()  )
			$bp_classes[] = 'friends';
			
		if ( bp_is_groups() && !bp_is_blog_page()  )
			$bp_classes[] = 'groups';

		if ( bp_is_settings() && !bp_is_blog_page()  )
			$bp_classes[] = 'settings';
			
		if ( bp_is_site_item() )
			$bp_classes[] = 'single-item';
			
		if ( bp_is_inbox() )
			$bp_classes[] = 'inbox';
			
		if ( bp_is_friend_requests() )
			$bp_classes[] = 'friend-requests';

		if ( bp_is_friends_screen() )
			$bp_classes[] = 'my-friends';
			
		if ( bp_is_create_blog() )
			$bp_classes[] = 'create-blog';
			
		if ( bp_is_recent_commments() )
			$bp_classes[] = 'recent-comments';
			
		if ( bp_is_recent_posts() )
			$bp_classes[] = 'recent-posts';
			
		if ( bp_is_my_blogs() && !bp_is_directory() )
			$bp_classes[] = 'my-blogs';
			
		if ( bp_is_my_groups() && !bp_is_directory() )
			$bp_classes[] = 'my-groups';
			
		if ( bp_is_group_leave() )
			$bp_classes[] = 'leave-group';
			
		if ( bp_is_group_invites() )
			$bp_classes[] = 'group-invites';
			
		if ( bp_is_group_members() )
			$bp_classes[] = 'group-members';
			
		if ( bp_is_group_forum_topic() )
			$bp_classes[] = 'group-forum-topic';
			
		if ( bp_is_group_forum() )
			$bp_classes[] = 'group-forum';
			
		if ( bp_is_group_wire() )
			$bp_classes[] = 'group-wire';
		
		if ( bp_is_group_admin_page() )
			$bp_classes[] = 'group-admin';
			
		if ( bp_is_group_create() )
			$bp_classes[] = 'group-create';
		
		if ( bp_is_group_home() )
			$bp_classes[] = 'group-home';
			
		if ( bp_is_profile_wire() )
			$bp_classes[] = 'profile-wire';
			
		if ( bp_is_change_avatar() )
			$bp_classes[] = 'change-avatar';
			
		if ( bp_is_profile_edit() )
			$bp_classes[] = 'profile-edit';
			
		if ( bp_is_friends_activity() )
			$bp_classes[] = 'friends-activity';
			
		if ( bp_is_my_activity() )
			$bp_classes[] = 'my-activity';
		
		if ( bp_is_register_page() )
			$bp_classes[] = 'registration';
		
		if ( bp_is_activation_page() )
			$bp_classes[] = 'activation';
			
		if ( is_user_logged_in() )
			$bp_classes[] = 'logged-in';

		/* Add the current_component, current_action into the bp classes */
		if ( !bp_is_blog_page() ) {
			if ( !empty( $bp->current_component ) )
				$bp_classes[] = $bp->current_component;
		
			if ( !empty( $bp->current_action ) )
				$bp_classes[] = $bp->current_action;
		}
		
		/* We don't want WordPress blog classes to appear on non-blog pages. */
		if ( !bp_is_blog_page() || is_home() ) {
			/* Preserve any custom classes already set */
			if ( !empty( $custom_classes ) )
				$wp_classes = (array) $custom_classes;
			else
				$wp_classes = array();
		}
 
		/* Merge WP classes with BP classes */
		$classes = array_merge( (array) $bp_classes, (array) $wp_classes );
		
		/* Remove any duplicates */
		$classes = array_unique( $classes );
		
		return apply_filters( 'bp_get_the_body_class', $classes, $bp_classes, $wp_classes, $custom_classes );
	}
	add_filter( 'body_class', 'bp_get_the_body_class', 10, 2 )


?>