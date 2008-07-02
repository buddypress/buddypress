<?php

function bp_get_nav() {
	global $bp_nav, $current_component, $current_userid, $loggedin_userid;
	
	for ( $i = 0; $i < count($bp_nav); $i++ ) {
		if ( $current_component == $bp_nav[$i]['id'] && $current_userid == $loggedin_userid ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}
		
		echo '<li' . $selected . '><a id="' . $bp_nav[$i]['id'] . '" href="' . $bp_nav[$i]['link'] . '">' . $bp_nav[$i]['name'] . '</a></li>';
	}
	
	echo '<li><a id="wp-logout" href="http://' . get_usermeta( get_current_user_id(), 'source_domain' ) . '/wp-login.php?action=logout">Log Out</a><li>';
}

function bp_get_options_nav() {
	global $bp_options_nav, $current_component, $current_action;
	global $loggedin_userid, $current_userid, $bp_users_nav;

	if ( $loggedin_userid == $current_userid ) {
		if ( count( $bp_options_nav[$current_component] ) < 1 )
			return false;
	
		foreach ( $bp_options_nav[$current_component] as $slug => $values ) {
			$title = $values['name'];
			$link = $values['link'];

			if ( $slug == $current_action || ( $slug == '' && ( $current_component == 'blog' && bp_is_blog() ) ) ) {
				$selected = ' class="current"';
			} else {
				$selected = '';
			}
		
			echo '<li' . $selected . '><a href="' . $link . '">' . $title . '</a></li>';		
		}
	} else {
		if ( count( $bp_users_nav ) < 1 )
			return false;

		bp_get_user_nav();
	}
}

function bp_get_user_nav() {
	global $bp_users_nav, $current_component;

	for ( $i = 0; $i < count($bp_users_nav); $i++ ) {
		if ( $current_component == $bp_users_nav[$i]['id'] ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}
		
		echo '<li' . $selected . '><a id="' . $bp_users_nav[$i]['id'] . '" href="' . $bp_users_nav[$i]['link'] . '">' . $bp_users_nav[$i]['name'] . '</a></li>';
	}	
}

function bp_has_options_avatar() {
	global $bp_options_avatar;
	
	if ( $bp_options_avatar == '' )
		return false;
	
	return true;
}

function bp_get_options_avatar() {
	global $bp_options_avatar;
	
	if ( $bp_options_avatar == '' )
		return false;
		
	echo $bp_options_avatar;
}

function bp_get_options_title() {
	global $bp_options_title;
	
	if ( $bp_options_title == '' )
		$bp_options_title = __('Options');
	
	echo $bp_options_title;
}

function bp_is_home() {
	global $loggedin_userid, $current_userid;
	
	if ( $loggedin_userid == $current_userid )
		return true;
	
	return false;
}

function bp_comment_author_avatar() {
	global $comment;
	
	if ( function_exists('xprofile_get_avatar') ) {
		echo xprofile_get_avatar( $comment->user_id, 1 );	
	} else if ( function_exists('get_avatar') ) {
		get_avatar();
	}
}

?>