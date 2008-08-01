<?php

function bp_get_nav() {
	global $bp;

	ksort($bp['bp_nav']);
	
	foreach( $bp['bp_nav'] as $nav_item ) {
		if ( $bp['current_component'] == $nav_item['id'] && $bp['current_userid'] == $bp['loggedin_userid'] ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}
		
		if ( $bp['current_userid'] != $bp['loggedin_userid'] ) {
			if ( function_exists('friends_check_friendship') ) {
				if ( friends_check_friendship($bp['current_userid']) && $nav_item['id'] == $bp['friends']['bp_friends_slug'] ) {
					$selected = ' class="current"';
				} else {
					$selected = '';
				}
			}
		}
		
		echo '<li' . $selected . '><a id="' . $nav_item['id'] . '" href="' . $nav_item['link'] . '">' . $nav_item['name'] . '</a></li>';
	}
	
	echo '<li><a id="wp-logout" href="' . get_option('home') . '/wp-login.php?action=logout">Log Out</a><li>';
}

function bp_get_options_nav() {
	global $bp;

	if ( $bp['loggedin_userid'] == $bp['current_userid'] ) {
		if ( count( $bp['bp_options_nav'][$bp['current_component']] ) < 1 )
			return false;
	
		foreach ( $bp['bp_options_nav'][$bp['current_component']] as $slug => $values ) {
			$title = $values['name'];
			$link = $values['link'];

			if ( $slug == $bp['current_action'] || ( $slug == '' && ( $bp['current_component'] == 'blog' && bp_is_blog() ) ) ) {
				$selected = ' class="current"';
			} else {
				$selected = '';
			}
		
			echo '<li' . $selected . '><a href="' . $link . '">' . $title . '</a></li>';		
		}
	} else {
		if ( count( $bp['bp_users_nav'] ) < 1 )
			return false;

		bp_get_user_nav();
	}
}

function bp_get_user_nav() {
	global $bp;

	foreach ( $bp['bp_users_nav'] as $user_nav_item ) {
		if ( $bp['current_component'] == $user_nav_item['id'] ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}
		
		echo '<li' . $selected . '><a id="' . $user_nav_item['id'] . '" href="' . $user_nav_item['link'] . '">' . $user_nav_item['name'] . '</a></li>';
	}	
}

function bp_has_options_avatar() {
	global $bp;
	
	if ( $bp['bp_options_avatar'] == '' )
		return false;
	
	return true;
}

function bp_get_options_avatar() {
	global $bp;
	
	if ( $bp['bp_options_avatar'] == '' )
		return false;
		
	echo $bp['bp_options_avatar'];
}

function bp_get_options_title() {
	global $bp;
	
	if ( $bp['bp_options_title'] == '' )
		$bp['bp_options_title'] = __('Options');
	
	echo $bp['bp_options_title'];
}

function bp_is_home() {
	global $bp;
	
	if ( $bp['loggedin_userid'] == $bp['current_userid'] )
		return true;
	
	return false;
}

function bp_comment_author_avatar() {
	global $comment;
	
	if ( function_exists('core_get_avatar') ) {
		echo core_get_avatar( $comment->user_id, 1 );	
	} else if ( function_exists('get_avatar') ) {
		get_avatar();
	}
}

function bp_exists($function) {
	if ( function_exists($function) )
		return true;
	
	return false;
}

?>