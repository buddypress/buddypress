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

function bp_get_options_avatar() {
	global $bp_options_avatar;
	
	if ( $bp_options_avatar == '' )
		return false;
		
	echo 'no avatar function';
}

function bp_get_options_nav() {
	global $bp_options_nav, $current_component, $current_action;

	if ( count( $bp_options_nav[$current_component] ) < 1 )
		return false;
	
	foreach ( $bp_options_nav[$current_component] as $slug => $values ) {
		$title = $values['name'];
		$link = $values['link'];

		if ( $slug == $current_action ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}
		
		echo '<li' . $selected . '><a href="' . $link . '">' . $title . '</a></li>';		
	}
}

?>