<?php

function setup_blog_tab() 
{
	global $menu, $submenu, $thirdlevel;

	unset($submenu['post-new.php']);
	unset($submenu['edit.php']);
	unset($submenu['edit-comments.php']);

	$submenu['post-new.php'][20] = $menu[5];
	$submenu['post-new.php'][25] = $menu[10];
	$submenu['post-new.php'][35] = $menu[20];

	unset($menu[5]); // Write
	unset($menu[10]); // Manage
	unset($menu[20]); // Comments

	add_menu_page('Blog', 'Blog', 1, 'post-new.php');

	foreach($menu as $key => $value)
	{
		if($menu[$key][0] == 'Blog')
		{
			$menu[5] = $menu[$key];
		}
	}
	
	ksort($menu);
	array_pop($menu);

	if(strpos($_SERVER['SCRIPT_NAME'],'/post-new.php') ||
	   strpos($_SERVER['SCRIPT_NAME'],'/page-new.php') ||
	   strpos($_SERVER['SCRIPT_NAME'],'/link-add.php')) 
	{
		$thirdlevel = array(
			array('Post', 'post_new', 'post-new.php'),
			array('Page', 'page_new', 'page-new.php'),
			array('Link', 'new_link', 'link-add.php')
		);
	}
	else if(strpos($_SERVER['SCRIPT_NAME'],'/edit.php') ||
			strpos($_SERVER['SCRIPT_NAME'],'/edit-pages.php') ||
			strpos($_SERVER['SCRIPT_NAME'],'/link-manager.php') ||
			strpos($_SERVER['SCRIPT_NAME'],'/categories.php') ||
			strpos($_SERVER['SCRIPT_NAME'],'/edit-tags.php') ||
			strpos($_SERVER['SCRIPT_NAME'],'/upload.php') ||
			strpos($_SERVER['SCRIPT_NAME'],'/import.php') ||
			strpos($_SERVER['SCRIPT_NAME'],'/export.php'))
	{
		$thirdlevel = array(
			array('Posts', 'posts', 'edit.php'),
			array('Pages', 'pages', 'edit-pages.php'),
			array('Links', 'links', 'link-manager.php'),
			array('Categories', 'cats', 'categories.php'),
			array('Tags', 'tags', 'edit-tags.php'),
			array('Media Library', 'media', 'upload.php'),
			array('Import', 'import', 'import.php'),
			array('Export', 'export', 'export.php'),			
		);
	}

	
}
add_action('_admin_menu', 'setup_blog_tab');

function alter_blog_tab_positions()
{
	global $parent_file, $submenu_file;
	
	if(strpos($_SERVER['SCRIPT_NAME'],'/edit.php')) { $parent_file = 'post-new.php'; $submenu_file = 'edit.php'; }
	else if(strpos($_SERVER['SCRIPT_NAME'],'/edit-pages.php')) { $parent_file = 'page-new.php'; $submenu_file = 'edit.php'; }
	else if(strpos($_SERVER['SCRIPT_NAME'],'/link-manager.php')) { $parent_file = 'link-add.php'; $submenu_file = 'edit.php'; }
	else if(strpos($_SERVER['SCRIPT_NAME'],'/categories.php')) { $parent_file = 'post-new.php'; $submenu_file = 'edit.php'; }
	else if(strpos($_SERVER['SCRIPT_NAME'],'/edit-tags.php')) { $parent_file = 'post-new.php'; $submenu_file = 'edit.php'; }
	else if(strpos($_SERVER['SCRIPT_NAME'],'/upload.php')) { $parent_file = 'post-new.php'; $submenu_file = 'edit.php'; }
	else if(strpos($_SERVER['SCRIPT_NAME'],'/import.php')) { $parent_file = 'post-new.php'; $submenu_file = 'edit.php'; }
	else if(strpos($_SERVER['SCRIPT_NAME'],'/export.php')) { $parent_file = 'post-new.php'; $submenu_file = 'edit.php'; }
	else if(strpos($_SERVER['SCRIPT_NAME'],'/edit-comments.php')) { $parent_file = 'post-new.php'; $submenu_file = 'edit-comments.php'; }
	else if(strpos($_SERVER['SCRIPT_NAME'],'/page-new.php')) { $parent_file = 'post-new.php'; $submenu_file = 'post-new.php'; }
	else if(strpos($_SERVER['SCRIPT_NAME'],'/link-add.php')) { $parent_file = 'post-new.php'; $submenu_file = 'post-new.php'; }
	else {
		unset($submenu_file);
	}
}
add_action('admin_head', 'alter_blog_tab_positions');


?>