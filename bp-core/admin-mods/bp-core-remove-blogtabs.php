<?php

function bp_core_disable_homebase_blog_tabs() {
	global $menu, $submenu, $bp, $wpdb;
	global $parent_file, $submenu_file;
	
	/* Unset all blog tabs if this is the home base for the user */
	if ( $wpdb->blogid == $bp['current_homebase_id'] ) {
		unset($menu[5]);
		unset($menu[10]);
		unset($menu[15]);
		unset($menu[20]);
		unset($menu[25]);
		unset($menu[30]);
		unset($menu[40]);
		
		/* Disable access to blog tabs */
		if ( $parent_file == 'post-new.php' || 
		     $parent_file == 'edit.php' ||
			 $parent_file == 'themes.php' ||
			 $parent_file == 'edit-comments.php' ||
		 	 $parent_file == 'users.php' ||
		     $parent_file == 'options-general.php')
		{ 
			die('No Access');
		}
	}
	/* Unset the 'Your Profile' tab completely */
	unset($submenu['users.php'][10]);
	
	/* Disable access to the profile tab for all blogs */
	if ( $submenu_file == 'profile.php' ) { die; }
	
	/* Reorder the 'Account' tab so it appears as a small right tab */
	if ( $wpdb->blogid != $bp['current_homebase_id'] ) {
		$menu[27] = $menu[41];
		unset($menu[41]);
	}
}
add_action( 'dashmenu', 'bp_core_disable_homebase_blog_tabs' );

?>