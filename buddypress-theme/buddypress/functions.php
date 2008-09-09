<?php
if ( function_exists('register_sidebar') )
    register_sidebar(array(
        'before_widget' => '<li id="%1$s" class="widget %2$s">',
        'after_widget' => '</li>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>',
    ));

function get_options_class() {
	global $bp, $is_single_group;

	if ( !bp_is_home() && $bp['current_component'] == 'profile' || 
						  $bp['current_component'] == 'blog' || 
						  $bp['current_component'] == 'friends' || 
						  $bp['current_component'] == 'blogs' ) {
		if ( $bp['current_userid'] != $bp['loggedin_userid'] )
			echo ' class="arrow"';
	}
	
	if ( ( $bp['current_component'] == 'groups' && $is_single_group ) || ( $bp['current_component'] == 'groups' && !bp_is_home() ) )
		echo ' class="arrow"';	
}

function has_icons() {
	global $bp;

	if ( ($bp['current_userid'] != $bp['loggedin_userid']) )
		echo ' class="icons"';
}
?>