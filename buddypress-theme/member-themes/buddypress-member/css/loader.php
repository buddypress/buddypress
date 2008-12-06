<?php 

/* Load the WP environment */
require_once( preg_replace('/(.*)\/wp-content\/.*/', '\1', dirname( __FILE__ ) ) . '/wp-load.php' );

/* Set the content type to CSS */
header('Content-type: text/css'); 

/* Load the base and settings css as they will always be present */
if ( file_exists('base.css') )
	echo "@import url(base.css);\n";

if ( file_exists('settings.css') )
	echo "@import url(settings.css);\n";
	
/* Load CSS for components that are installed */

/* Activity Streams */
if ( function_exists('bp_activity_user_install') && file_exists('activity.css') )
	echo "@import url(activity.css);\n";
	
/* Blogs */
if ( function_exists('bp_blogs_install') && file_exists('blogs.css') )
	echo "@import url(blogs.css);\n";	

/* Friends */
if ( function_exists('friends_install') && file_exists('friends.css') )
	echo "@import url(friends.css);\n";

/* Groups */
if ( function_exists('groups_install') && file_exists('groups.css') )
	echo "@import url(groups.css);\n";

/* Messages */
if ( function_exists('messages_install') && file_exists('messaging.css') )
	echo "@import url(messaging.css);\n";
	
/* Wire */
if ( function_exists('bp_wire_install') && file_exists('wire.css') )
	echo "@import url(wire.css);\n";

/* Profiles */
if ( function_exists('xprofile_install') && file_exists('profiles.css') )
	echo "@import url(profiles.css);\n";

/* If the root blog is set up for right to left reading, include the rtl.css file */
if ( get_bloginfo( 1, 'text_direction' ) == 'rtl' && file_exists( 'rtl.css' ) )
	echo "@import url(rtl.css);\n";
	
/* If there are any custom component css files inside the /custom/ dir, load them. */
if ( is_dir( './custom-components' ) ) {
	if ( $dh = opendir( './custom-components' ) ) {
		while ( ( $css_file = readdir( $dh ) ) !== false ) {
			if( substr ( $css_file, -4 ) == '.css' ) {
				echo "@import url(custom-components/$css_file);\n";
			}
		}
	}
}

/* Now load the custom styles CSS for custom modifications */
if ( file_exists('custom.css') )
	echo "@import url(custom.css);\n";

?>