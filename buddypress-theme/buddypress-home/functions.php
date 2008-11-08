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
	global $bp;

	if ( $bp['current_component'] == 'news' && $bp['current_action'] == '' ) {
		query_posts('showposts=15');
		bp_catch_uri('index', true );
	}
}
add_action('wp', 'bp_home_theme_catch_urls', 1 );

function bp_is_page($page) {
	global $bp;

	if ( $page == $bp['current_component'] || $page == 'home' && $bp['current_component'] == $bp['default_component'] )
		return true;
	
	return false;
}


?>