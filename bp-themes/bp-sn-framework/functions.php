<?php

/* Register the widget columns */
register_sidebars( 1, 
	array( 
		'name' => 'first-section',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>'
	) 
);

register_sidebars( 1,
	array( 
		'name' => 'second-section',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>'
	) 
);

register_sidebars( 1,
	array( 
		'name' => 'third-section',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>'
	) 
);

register_sidebars( 1,
	array( 
		'name' => 'blog-sidebar',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>'
	) 
);

/* Load the AJAX functions for the theme */
require_once( TEMPLATEPATH . '/_inc/ajax.php' );

/* Load the javascript for the theme */
wp_enqueue_script( 'jquery-livequery-pack', get_template_directory_uri() . '/_inc/js/jquery-livequery.js', array( 'jquery' ) );
wp_enqueue_script( 'dtheme-ajax-js', get_template_directory_uri() . '/_inc/js/ajax.js', array( 'jquery', 'jquery-livequery-pack' ) );
wp_enqueue_script( 'dtheme-general-js', get_template_directory_uri() . '/_inc/js/general.js', array( 'jquery', 'jquery-livequery-pack' ) );

/* Make sure the blog index page shows under /[HOME_BLOG_SLUG] if enabled */
function bp_dtheme_show_home_blog() {
	global $bp, $query_string, $paged;
	
	if ( $bp->current_component == BP_HOME_BLOG_SLUG && ( !$bp->current_action || 'page' == $bp->current_action ) ) {		
		if ( 'page' == $bp->current_action && $bp->action_variables[0] ) {
			$query_string .= '&paged=' . $bp->action_variables[0];
			$paged = $bp->action_variables[0];
		}

		$query_string = preg_replace( '/category_name=' . BP_HOME_BLOG_SLUG . '/', '', $query_string );
		$query_string = preg_replace( '/pagename=' . BP_HOME_BLOG_SLUG . '/', '', $query_string );
		query_posts($query_string);
		
		bp_core_load_template( 'index', true );
	}
}
add_action( 'wp', 'bp_dtheme_show_home_blog', 2 );

?>