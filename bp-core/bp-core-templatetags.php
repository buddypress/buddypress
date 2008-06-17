<?php

function bp_get_nav() {
	global $bp_nav, $bp_uri, $bp_uri_count;
	
	for ( $i = 0; $i < count($bp_nav); $i++ ) {
		if ( $bp_uri[$bp_uri_count] == $bp_nav[$i]['id'] ) {
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
		
	echo '<img src="" />';
}

function bp_get_options_nav() {
	global $bp_options_nav, $bp_uri, $bp_uri_count;

	if ( $bp_uri[$bp_uri_count] == 'blog' ) {
		get_sidebar();
	} else {
		echo $bp_options_nav;
	}
}

?>