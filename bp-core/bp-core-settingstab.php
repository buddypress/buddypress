<?php
/**
 * bp_core_move_blog_settings_tab()
 *
 * By default all settings are under a "Settings" tab. Most of these are blog related.
 * This and the following functions will add a "Blog" option under settings, where blog 
 * settings can be moved to.
 * 
 * The idea is that other settings for profiles, messages etc will have their own options under
 * appropriate headings, rather than being muddled in with blog settings.
 * 
 * @package BuddyPress Core
 * @global $submenu WordPress global variable containing all submenu items.
 * @global $thirdlevel BuddyPress created global containing nav items at the third level
 */
function bp_core_move_blog_settings_tab() {
	global $submenu, $thirdlevel;
	
	if ( strpos( $_SERVER['SCRIPT_NAME'], '/options-' ) ) {
		if ( !isset($_GET['page']) ) {
			$thirdlevel = $submenu['options-general.php'];
		}
		unset($submenu['options-general.php']);
	}
}
add_action( '_admin_menu', 'bp_core_move_blog_settings_tab' );

/**
 * bp_core_add_settings_submenu()
 *
 * Function actually adds "Blog" as a submenu action, as well as renaming "Delete Blog" to
 * "Delete Account". 
 * 
 * @package BuddyPress Core
 * @global $submenu WordPress global variable containing all submenu items.
 * @global $thirdlevel BuddyPress created global containing nav items at the third level
 */
function bp_core_add_settings_submenu() {
	global $submenu, $thirdlevel; 
	
	$submenu['options-general.php'][0][0] = 'Blog';
	$submenu['options-general.php'][1][0] = 'Delete Account';
}
add_action( 'admin_menu', 'bp_core_add_settings_submenu' );

/**
 * bp_core_alter_settings_tab_positions()
 *
 * Alter the positioning of settings options, so that the highlighting of tabs
 * remains correct.
 * 
 * @package BuddyPress Core
 * @global $parent_file WordPress global for the name of the file controlling the parent tab
 * @global $submenu_file WordPress global for the name of the file controlling the sub parent tab
 */
function bp_core_alter_settings_tab_positions() {
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
add_action( 'admin_head', 'bp_core_alter_settings_tab_positions' );

?>