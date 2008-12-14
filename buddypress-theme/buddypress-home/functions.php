<?php

/* Register the widget columns */
register_sidebars( 1, 
	array( 
		'name' => 'left-column',
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
        'after_widget' => '</li>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>'
	) 
);

register_sidebars( 1,
	array( 
		'name' => 'center-column',
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
        'after_widget' => '</li>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>'
	) 
);

register_sidebars( 1,
	array( 
		'name' => 'right-column',
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
        'after_widget' => '</li>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>'
	) 
);

register_sidebars( 1,
	array( 
		'name' => 'blog-sidebar',
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
        'after_widget' => '</li>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>'
	) 
);

/* Catch specific URLs */

function bp_home_theme_catch_urls() {
	global $bp, $current_blog;
	
	if ( $current_blog->blog_id > 1 )
		return false;

	if ( $bp['current_component'] == NEWS_SLUG && $bp['current_action'] == '' ) {
		query_posts('showposts=15');
		bp_catch_uri( 'index', true );
	}
}
add_action('wp', 'bp_home_theme_catch_urls', 1 );

function bp_show_register_page() {
	global $bp, $current_blog;
	
	if ( $current_blog->blog_id > 1 )
		return false;

	if ( $bp['current_component'] == REGISTER_SLUG && $bp['current_action'] == '' ) {
		bp_core_signup_set_headers();
		bp_catch_uri( 'register', true );
	}
}
add_action( 'wp', 'bp_show_register_page', 2 );

function bp_show_activation_page() {
	global $bp, $current_blog;
	
	if ( $current_blog->blog_id > 1 )
		return false;

	if ( $bp['current_component'] == ACTIVATION_SLUG && $bp['current_action'] == '' ) {
		bp_core_activation_set_headers();
		bp_catch_uri( 'activate', true );
	}
}
add_action( 'wp', 'bp_show_activation_page', 2 );



?>