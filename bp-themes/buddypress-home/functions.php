<?php

/* Register the widget columns */
register_sidebars( 1, 
	array( 
		'name' => 'left-column',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>'
	) 
);

register_sidebars( 1,
	array( 
		'name' => 'center-column',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h2 class="widgettitle">',
        'after_title' => '</h2>'
	) 
);

register_sidebars( 1,
	array( 
		'name' => 'right-column',
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

/* Catch specific URLs */

function bp_show_home_blog() {
	global $bp, $query_string;
	
	if ( $bp->current_component == BP_HOME_BLOG_SLUG  ) {
		$pos = strpos( $query_string, 'pagename=' . BP_HOME_BLOG_SLUG );
		
		if ( $pos !== false )
			$query_string = preg_replace( '/pagename=' . BP_HOME_BLOG_SLUG . '/', '', $query_string );

		query_posts($query_string);
		
		if ( is_single() )
			bp_core_load_template( 'single', true );
		else if ( is_category() || is_search() || is_day() || is_month() || is_year() )
			bp_core_load_template( 'archive', true );
		else
			bp_core_load_template( 'index', true );
	}
}
add_action( 'wp', 'bp_show_home_blog', 2 );

function bp_show_register_page() {
	global $bp, $current_blog;
	
	require ( WPMU_PLUGIN_DIR . '/bp-core/bp-core-signup.php' );
	
	if ( $bp->current_component == BP_REGISTER_SLUG && $bp->current_action == '' ) {
		bp_core_signup_set_headers();
		bp_core_load_template( 'register', true );
	}
}
add_action( 'wp', 'bp_show_register_page', 2 );

function bp_show_activation_page() {
	global $bp, $current_blog;

	require ( WPMU_PLUGIN_DIR . '/bp-core/bp-core-activation.php' );
	
	if ( $bp->current_component == BP_ACTIVATION_SLUG && $bp->current_action == '' ) {
		bp_core_activation_set_headers();
		bp_core_load_template( 'activate', true );
	}
}
add_action( 'wp', 'bp_show_activation_page', 2 );

/* Hook for custom theme functions via plugins */
do_action( 'bp_home_theme_functions' );


?>