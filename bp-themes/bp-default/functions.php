<?php

/* Register the widget columns */
register_sidebars( 1,
	array(
		'name' => 'Sidebar',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widgettitle">',
        'after_title' => '</h3>'
	)
);

/* Load the AJAX functions for the theme */
require_once( TEMPLATEPATH . '/_inc/ajax.php' );

/* Load the javascript for the theme */
wp_enqueue_script( 'dtheme-ajax-js', get_template_directory_uri() . '/_inc/global.js', array( 'jquery' ) );

/* Make sure the blog index page shows under /[HOME_BLOG_SLUG] if enabled */
function bp_dtheme_show_home_blog() {
	global $bp, $query_string, $paged;

	if ( $bp->current_component == BP_HOME_BLOG_SLUG && ( !$bp->current_action || 'page' == $bp->current_action ) ) {
		unset( $query_string );

		if ( ( 'page' == $bp->current_action && $bp->action_variables[0] ) && false === strpos( $query_string, 'paged' ) ) {
			$query_string .= '&paged=' . $bp->action_variables[0];
			$paged = $bp->action_variables[0];
		}

		query_posts($query_string);

		bp_core_load_template( 'index', true );
	}
}
add_action( 'wp', 'bp_dtheme_show_home_blog', 2 );

function bp_dtheme_firstname( $name = false, $echo = false ) {
	global $bp;

	if ( !$name )
		$name = $bp->loggedin_user->fullname;

	$fullname = (array)explode( ' ', $name );

	if ( $echo )
		echo $fullname[0];
	else
		return $fullname[0];
}

function bp_dtheme_remove_redundant() {
	global $bp;

	/* Remove the redundant "My Posts and My Comments" options since we can use filters on the activity stream. */
	bp_core_remove_subnav_item( $bp->blogs->slug, 'recent-posts' );
	bp_core_remove_subnav_item( $bp->blogs->slug, 'recent-comments' );
}
add_action( 'init', 'bp_dtheme_remove_redundant' );

?>