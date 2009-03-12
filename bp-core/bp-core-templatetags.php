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
function bp_get_nav() {
	global $bp, $current_blog;
	
	/* Sort the nav by key as the array has been put together in different locations */
	$bp->bp_nav = bp_core_sort_nav_items( $bp->bp_nav );

	/* Loop through each navigation item */
	foreach( (array) $bp->bp_nav as $nav_item ) {
		/* If the current component matches the nav item id, then add a highlight CSS class. */
		if ( $bp->current_component == $nav_item['css_id'] ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}
		
		/* If we are viewing another person (current_userid does not equal loggedin_user->id)
		   then check to see if the two users are friends. if they are, add a highlight CSS class
		   to the friends nav item if it exists. */
		if ( !bp_is_home() && $bp->displayed_user->id ) {
			if ( function_exists('friends_install') ) {
				if ( friends_check_friendship( $bp->loggedin_user->id, $bp->displayed_user->id ) && $nav_item['css_id'] == $bp->friends->slug ) {
					$selected = ' class="current"';
				} else { 
					$selected = '';
				}
			}
		}
		
		/* echo out the final list item */
		echo '<li' . $selected . '><a id="my-' . $nav_item['css_id'] . '" href="' . $nav_item['link'] . '">' . $nav_item['name'] . '</a></li>';
	}
	
	/* Always add a log out list item to the end of the navigation */
	if ( function_exists( 'wp_logout_url' ) ) {
		echo '<li><a id="wp-logout" href="' .  wp_logout_url( site_url() . $_SERVER['REQUEST_URI'] ) . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>';		
	} else {
		echo '<li><a id="wp-logout" href="' . site_url() . '/wp-login.php?action=logout&amp;redirect_to=' . site_url() . $_SERVER['REQUEST_URI'] . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>';
	}
}

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
		foreach ( $bp->bp_options_nav[$bp->current_component] as $slug => $values ) {
			$title = $values['name'];
			$link = $values['link'];
			$css_id = $values['css_id'];
			
			/* If the current action or an action variable matches the nav item id, then add a highlight CSS class. */
			if ( $slug == $bp->current_action || in_array( $slug, $bp->action_variables ) ) {
				$selected = ' class="current"';
			} else {
				$selected = '';
			}
			
			/* echo out the final list item */
			echo '<li' . $selected . '><a id="' . $css_id . '" href="' . $link . '">' . $title . '</a></li>';		
		}
	} else {
		if ( !$bp->bp_users_nav )
			return false;

		bp_get_user_nav();
	}
}

/**
 * bp_get_user_nav()
 * TEMPLATE TAG
 *
 * Uses the $bp->bp_users_nav global to render out the user navigation when viewing another user other than
 * yourself.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_get_user_nav() {
	global $bp;

	/* Sort the nav by key as the array has been put together in different locations */	
	$bp->bp_users_nav = bp_core_sort_nav_items( $bp->bp_users_nav );

	foreach ( $bp->bp_users_nav as $user_nav_item ) {	
		if ( $bp->current_component == $user_nav_item['css_id'] ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}
		
		echo '<li' . $selected . '><a id="user-' . $user_nav_item['css_id'] . '" href="' . $user_nav_item['link'] . '">' . $user_nav_item['name'] . '</a></li>';
	}	
}

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

/**
 * bp_get_options_avatar()
 * TEMPLATE TAG
 *
 * Gets the avatar for the current sub nav (eg friends avatar or group avatar).
 * Does not check if there is one - so always use if ( bp_has_options_avatar() )
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_get_options_avatar() {
	global $bp;

	echo apply_filters( 'bp_get_options_avatar', $bp->bp_options_avatar );
}

function bp_get_options_title() {
	global $bp;
	
	if ( empty( $bp->bp_options_title ) )
		$bp->bp_options_title = __( 'Options', 'buddypress' );
	
	echo apply_filters( 'bp_get_options_avatar', $bp->bp_options_title );
}

function bp_comment_author_avatar() {
	global $comment;
	
	if ( function_exists('bp_core_get_avatar') ) {
		echo apply_filters( 'bp_comment_author_avatar', bp_core_get_avatar( $comment->user_id, 1 ) );	
	} else if ( function_exists('get_avatar') ) {
		get_avatar();
	}
}

function bp_post_author_avatar() {
	global $post;
	
	if ( function_exists('bp_core_get_avatar') ) {
		echo apply_filters( 'bp_post_author_avatar', bp_core_get_avatar( $post->post_author, 1 ) );	
	} else if ( function_exists('get_avatar') ) {
		get_avatar();
	}
}

function bp_loggedinuser_avatar( $width = false, $height = false ) {
	global $bp;
	
	if ( $width && $height )
		echo apply_filters( 'bp_loggedinuser_avatar', bp_core_get_avatar( $bp->loggedin_user->id, 2, $width, $height ) );
	else
		echo apply_filters( 'bp_loggedinuser_avatar', bp_core_get_avatar( $bp->loggedin_user->id, 2 ) );
}

function bp_loggedinuser_avatar_thumbnail( $width = false, $height = false ) {
	global $bp;
	
	if ( $width && $height )
		echo apply_filters( 'bp_get_options_avatar', bp_core_get_avatar( $bp->loggedin_user->id, 1, $width, $height ) );
	else
		echo apply_filters( 'bp_get_options_avatar', bp_core_get_avatar( $bp->loggedin_user->id, 1 ) );
}

function bp_site_name() {
	echo apply_filters( 'bp_site_name', get_blog_option( 1, 'blogname' ) );
}

function bp_is_home() {
	global $bp;
	
	if ( is_user_logged_in() && $bp->loggedin_user->id == $bp->displayed_user->id )
		return true;
		
	return false;
}

function bp_fetch_user_fullname( $user_id = false, $echo = true ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->displayed_user->id;
	
	if ( function_exists('xprofile_install') ) {
		$data = bp_get_field_data( BP_XPROFILE_FULLNAME_FIELD_NAME, $user_id );

		if ( empty($data) ) {
			$ud = get_userdata($user_id);
			$data = $ud->display_name;
		} else {
			$data = bp_core_ucfirst($data);
		}
	} else {
		$ud = get_userdata($user_id);
		$data = $ud->display_name;
	}
	
	if ( $echo )
		echo apply_filters( 'bp_fetch_user_fullname', stripslashes( trim( $data ) ) );
	else
		return apply_filters( 'bp_fetch_user_fullname', stripslashes ( trim ( $data ) ) );
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

function bp_the_avatar() {
	global $bp;
	echo apply_filters( 'bp_the_avatar', bp_core_get_avatar( $bp->displayed_user->id, 2 ) );
}

function bp_the_avatar_thumbnail() {
	global $bp;
	echo apply_filters( 'bp_the_avatar_thumbnail', bp_core_get_avatar( $bp->displayed_user->id, 1 ) );
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
	
}

function bp_get_profile_header() {
	load_template( TEMPLATEPATH . '/profile/profile-header.php' );
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
	global $bp, $current_user;
	
	if ( $link = bp_core_get_userlink( $bp->loggedin_user->id ) ) {
		echo apply_filters( 'bp_loggedinuser_link', $link );
	} else {
		$ud = get_userdata($displayed_user->id);
		echo apply_filters( 'bp_loggedinuser_link', $ud->user_login );
	}
}

function bp_get_plugin_sidebar() {
	if ( file_exists(TEMPLATEPATH . '/plugin-sidebar.php') )
		load_template( TEMPLATEPATH . '/plugin-sidebar.php' );
}

function bp_is_blog_page() {
	global $bp, $is_member_page;
	
	if ( $bp->current_component == HOME_BLOG_SLUG )
		return true;

	if ( !$is_member_page && !in_array( $bp->current_component, $bp->root_components ) )
		return true;
		
	return false;
}

function bp_page_title() {
	global $bp;
	
	if ( !empty( $bp->displayed_user->fullname ) ) {
	 	echo apply_filters( 'bp_page_title', strip_tags( $bp->displayed_user->fullname . ' &raquo; ' . ucwords( $bp->current_component ) . ' &raquo; ' . $bp->bp_options_nav[$bp->current_component][$bp->current_action]['name'] ) );
	} else {
		echo apply_filters( 'bp_page_title', strip_tags( ucwords( $bp->current_component ) . ' &raquo; ' . ucwords( $bp->bp_options_title ) . ' &raquo; ' . ucwords( $bp->current_action ) ) );
	}
}

function bp_styles() {
	do_action( 'bp_styles' );
	wp_print_styles();
}

function bp_is_page($page) {
	global $bp;
	
	if ( $bp->displayed_user->id || $bp->is_single_item )
		return false;

	if ( $page == $bp->current_component || $page == 'home' && $bp->current_component == $bp->default_component )
		return true;
	
	return false;
}

function bp_has_custom_signup_page() {
	if ( file_exists( WP_CONTENT_DIR . '/themes/' . get_blog_option( 1, 'template') . '/register.php') )
		return true;
	
	return false;
}

function bp_signup_page( $echo = true ) {
	global $bp;
	
	if ( bp_has_custom_signup_page() ) {
		if ( $echo )
			echo $bp->root_domain . '/' . REGISTER_SLUG;
		else
			return $bp->root_domain . '/' . REGISTER_SLUG;
	} else {
		if ( $echo )
			echo $bp->root_domain . '/wp-signup.php';
		else
			return $bp->root_domain . '/wp-signup.php';
	}
}

function bp_has_custom_activation_page() {
	if ( file_exists( WP_CONTENT_DIR . '/themes/' . get_blog_option( 1, 'template') . '/activate.php') )
		return true;
	
	return false;
}

function bp_activation_page( $echo = true ) {
	if ( bp_has_custom_activation_page() ) {
		if ( $echo )
			echo site_url(ACTIVATION_SLUG);
		else
			return site_url(ACTIVATION_SLUG);
	} else {
		if ( $echo )
			echo site_url('wp-activate.php');
		else
			return site_url('wp-activate.php');
	}
}

function bp_search_form_action() {
	global $bp;
	
	return apply_filters( 'bp_search_form_action', site_url('search') );
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

function bp_login_bar() {
	if ( !is_user_logged_in() ) : ?>
		
		<form name="login-form" id="login-form" action="<?php echo site_url( '/wp-login.php' ) ?>" method="post">
			<input type="text" name="log" id="user_login" value="<?php _e( 'Username', 'buddypress' ) ?>" onfocus="if (this.value == '<?php _e( 'Username', 'buddypress' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php _e( 'Username', 'buddypress' ) ?>';}" />
			<input type="password" name="pwd" id="user_pass" class="input" value="" />
			
			<input type="checkbox" name="rememberme" id="rememberme" value="forever" title="<?php _e( 'Remember Me', 'buddypress' ) ?>" />
			
			<input type="submit" name="wp-submit" id="wp-submit" value="<?php _e( 'Log In', 'buddypress' ) ?>"/>				
			<input type="button" name="signup-submit" id="signup-submit" value="<?php _e( 'Sign Up', 'buddypress' ) ?>" onclick="location.href='<?php echo bp_signup_page() ?>'" />

			<input type="hidden" name="redirect_to" value="http://<?php echo $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] ?>" />
			<input type="hidden" name="testcookie" value="1" />
						
			<?php do_action( 'bp_login_bar_logged_out' ) ?>
		</form>
	
	<?php else : ?>
		
		<div id="logout-link">
			<?php bp_loggedinuser_avatar_thumbnail( 20, 20 ) ?> &nbsp;
			<?php bp_loggedinuser_link() ?>
			<?php 
				if ( function_exists('wp_logout_url') ) {
					$logout_link = '/ <a href="' . wp_logout_url( site_url() ) . '" alt="' . __( 'Log Out', 'buddypress' ) . '">' . __( 'Log Out', 'buddypress' ) . '</a>';
				} else {
					$logout_link = '/ <a href="' . site_url( '/wp-login.php?action=logout&amp;redirect_to=' . site_url() ) . '">' . __( 'Log Out', 'buddypress' ) . '</a>';					
				}			
				
				echo apply_filters( 'bp_logout_link', $logout_link );
			?>
			
			<?php do_action( 'bp_login_bar_logged_in' ) ?>
		</div>
		
	<?php endif;
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
	<li<?php if ( bp_is_page( HOME_BLOG_SLUG ) ) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>/<?php echo HOME_BLOG_SLUG ?>" title="<?php _e( 'Blog', 'buddypress' ) ?>"><?php _e( 'Blog', 'buddypress' ) ?></a></li>
	<li<?php if ( bp_is_page( MEMBERS_SLUG ) ) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>/<?php echo MEMBERS_SLUG ?>" title="<?php _e( 'Members', 'buddypress' ) ?>"><?php _e( 'Members', 'buddypress' ) ?></a></li>
	
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

/* Template functions for fetching globals, without querying the DB again
   also means we dont have to use the $bp variable in the template (looks messy) */

function bp_current_user_id() {
	global $bp;
	return apply_filters( 'bp_current_user_id', $bp->displayed_user->id );
}

function bp_user_fullname() {
	global $bp;
	echo apply_filters( 'bp_user_fullname', $bp->displayed_user->fullname );
}



?>