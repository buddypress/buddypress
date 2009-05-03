<?php

function add_blog_settings_tab() {
	global $submenu, $thirdlevel;
	
	if ( strpos( $_SERVER['SCRIPT_NAME'], '/options-' ) ) {
		if ( !isset($_GET['page']) ) {
			$thirdlevel = $submenu['options-general.php'];
		}
		unset($submenu['options-general.php']);
	}
}
add_action( '_admin_menu', 'add_blog_settings_tab' );

function move_settings_submenu() {
	global $submenu, $thirdlevel; 
	
	$submenu['options-general.php'][0][0] = 'Blog';
	$submenu['options-general.php'][1][0] = 'Delete Account';
}
add_action( 'admin_menu', 'move_settings_submenu' );

function alter_settings_tab_positions() {
	global $parent_file, $submenu_file;
	
	if ( strpos( $_SERVER['SCRIPT_NAME'], '/options-general.php' ) && !isset($_GET['page']) ) {
		$submenu_file = 'options-general.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/options-writing.php' ) ) {
		$submenu_file = 'options-general.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/options-reading.php' ) ) {
		$submenu_file = 'options-general.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/options-discussion.php' ) ) {
		$submenu_file = 'options-general.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/options-privacy.php' ) ) {
		$submenu_file = 'options-general.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/options-permalink.php' ) ) {
		$submenu_file = 'options-general.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/options-misc.php' ) ) {
		$submenu_file = 'options-general.php';
	} else {
		unset($submenu_file);
	}
}
add_action( 'admin_head', 'alter_settings_tab_positions' );

?>