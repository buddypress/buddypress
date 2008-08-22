<?php

/**
 * bp_core_setup_blog_tab()
 *
 * Combines the "Write", "Manage", and "Comments" admin tabs into one "Blog" tab.
 *
 * @package BuddyPress Core
 * @global $menu WordPress admin navigation array global
 * @global $submenu WordPress admin sub navigation array global
 * @global $thirdlevel BuddyPress admin third level navigation
 * @uses add_menu_page() WordPress function to add a new top level admin navigation tab
 */
function bp_core_setup_blog_tab()  {
	global $menu, $submenu, $thirdlevel;

	/* Unset the default secondary level tabs for the top level nav tabs */
	unset($submenu['post-new.php']);
	unset($submenu['edit.php']);
	unset($submenu['edit-comments.php']);

	/* Move the top level tabs into the sub menu array */
	$submenu['post-new.php'][20] = $menu[5]; // Write
	$submenu['post-new.php'][25] = $menu[10]; // Manage
	$submenu['post-new.php'][35] = $menu[20]; // Comments

	/* Unset the top level tabs */
	unset($menu[5]); // Write
	unset($menu[10]); // Manage
	unset($menu[20]); // Comments

	/* Add a blog tab to the top level nav */
	add_menu_page( 'Blog', 'Blog', 1, 'post-new.php' );

	/* Move the blog tab so it is the first tab in the top level nav */
	foreach ( $menu as $key => $value ) {
		if ( $menu[$key][0] == 'Blog' ) {
			$menu[5] = $menu[$key];
		}
	}
	
	ksort($menu);
	array_pop($menu);

	/* Bump secondary level nav for the old top level tabs down to a new third level navigation */
	if ( strpos( $_SERVER['SCRIPT_NAME'], '/post-new.php' ) ||
	     strpos( $_SERVER['SCRIPT_NAME'], '/page-new.php' ) ||
	     strpos( $_SERVER['SCRIPT_NAME'], '/link-add.php' ) ) 
	{
		$thirdlevel = array(
			array( 'Post', 'post_new', 'post-new.php' ),
			array( 'Page', 'page_new', 'page-new.php' ),
			array( 'Link', 'new_link', 'link-add.php' )
		);
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/edit.php' )         ||
			    strpos( $_SERVER['SCRIPT_NAME'], '/edit-pages.php' )   ||
			    strpos( $_SERVER['SCRIPT_NAME'], '/link-manager.php' ) ||
			    strpos( $_SERVER['SCRIPT_NAME'], '/categories.php' )   ||
			    strpos( $_SERVER['SCRIPT_NAME'], '/edit-tags.php' )    ||
			    strpos( $_SERVER['SCRIPT_NAME'], '/upload.php' )       ||
			    strpos( $_SERVER['SCRIPT_NAME'], '/import.php' )       ||
			    strpos( $_SERVER['SCRIPT_NAME'], '/export.php' ) )
	{
		$thirdlevel = array(
			array( 'Posts', 'posts', 'edit.php' ),
			array( 'Pages', 'pages', 'edit-pages.php' ),
			array( 'Links', 'links', 'link-manager.php' ),
			array( 'Categories', 'cats', 'categories.php' ),
			array( 'Tags', 'tags', 'edit-tags.php' ),
			array( 'Media Library', 'media', 'upload.php' ),
			array( 'Import', 'import', 'import.php' ),
			array(' Export', 'export', 'export.php' ),			
		);
	}

	
}
add_action( '_admin_menu', 'bp_core_setup_blog_tab' );

/**
 * bp_core_alter_blog_tab_positions()
 *
 * Keeps a tab highlighted when selected and under the "Blog" tab.
 *
 * @package BuddyPress Core
 * @global $parent_file WordPress global for the name of the file controlling the parent tab
 * @global $submenu_file WordPress global for the name of the file controlling the sub parent tab
 */
function bp_core_alter_blog_tab_positions() {
	global $parent_file, $submenu_file;
	
	if ( strpos($_SERVER['SCRIPT_NAME'], '/edit.php' ) ) {
		$parent_file = 'post-new.php'; $submenu_file = 'edit.php';
	} else if ( strpos($_SERVER['SCRIPT_NAME'], '/edit-pages.php' ) ) {
		$parent_file = 'page-new.php'; $submenu_file = 'edit.php';
	} else if ( strpos($_SERVER['SCRIPT_NAME'], '/link-manager.php' ) ) {
		$parent_file = 'link-add.php'; $submenu_file = 'edit.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/categories.php' ) ) {
		$parent_file = 'post-new.php'; $submenu_file = 'edit.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/edit-tags.php' ) ) { 
		$parent_file = 'post-new.php'; $submenu_file = 'edit.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/upload.php' ) ) { 
		$parent_file = 'post-new.php'; $submenu_file = 'edit.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/import.php' ) ) { 
		$parent_file = 'post-new.php'; $submenu_file = 'edit.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/export.php' ) ) { 
		$parent_file = 'post-new.php'; $submenu_file = 'edit.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/edit-comments.php' ) ) { 
		$parent_file = 'post-new.php'; $submenu_file = 'edit-comments.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/page-new.php' ) ) { 
		$parent_file = 'post-new.php'; $submenu_file = 'post-new.php';
	} else if ( strpos( $_SERVER['SCRIPT_NAME'], '/link-add.php' ) ) { 
		$parent_file = 'post-new.php'; $submenu_file = 'post-new.php';
	} else {
		unset($submenu_file);
	}
}
add_action('admin_head', 'bp_core_alter_blog_tab_positions');


?>