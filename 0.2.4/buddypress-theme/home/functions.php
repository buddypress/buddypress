<?php

/*************************************************************
 Check the request URI and forward to the correct custom pages
 *************************************************************/

$uri = explode("/", $_SERVER['REQUEST_URI']);
if($uri[count($uri)-1] == "") { array_pop($uri); }

switch($uri[count($uri)-1]) {
	case "blog":
		require(TEMPLATEPATH . '/blog.php'); die;
	break;
}


/*************************************************************
 Check the request URI and forward to the correct custom pages
 *************************************************************/

if ( function_exists('register_sidebar') )
    register_sidebar(array(
        'before_widget' => '<li id="%1$s" class="widget %2$s">',
        'after_widget' => '</li>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>',
    ));


?>