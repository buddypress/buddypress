<?php
if ( function_exists('register_sidebar') )
    register_sidebar(array(
        'before_widget' => '<li id="%1$s" class="widget %2$s">',
        'after_widget' => '</li>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>',
    ));

function bp_get_options_class() {
	global $bp, $is_single_group;

	if ( !bp_is_home() && $bp->current_component == $bp->profile->slug || $bp->current_component == $bp->friends->slug  || $bp->current_component == $bp->blogs->slug ) {
		echo ' class="arrow"';
	}
	
	if ( ( $bp->current_component == $bp->groups->slug && $is_single_group ) || ( $bp->current_component == $bp->groups->slug && !bp_is_home() ) )
		echo ' class="arrow"';	
}

function bp_has_icons() {
	global $bp;

	if ( ( !bp_is_home() ) )
		echo ' class="icons"';
}

/* Hook for custom theme functions via plugins */
do_action( 'bp_member_theme_functions' );

?>