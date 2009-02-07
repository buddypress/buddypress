<?php
/* Load the WP environment */
require_once( preg_replace('%(.*)[/\\\\]wp-content[/\\\\].*%', '\1', $_SERVER['SCRIPT_FILENAME'] ) . '/wp-load.php' ); 

/* Set the content type to CSS */
header('Content-type: text/css'); 

/* Load the base and css. */
if ( file_exists('base.css') )
	echo "@import url(base.css);\n";

/* Load the custom css if there is any. */
if ( file_exists('custom.css') )
	echo "@import url(custom.css);\n";

do_action( 'bp_custom_home_styles' );
?>