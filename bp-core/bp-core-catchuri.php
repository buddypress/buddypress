<?php
/*
Contributor: Chris Taylor - http://www.stillbreathing.co.uk/
Modified By: Andy Peatling
*/

/*************************************************************
  Functions for catching and displaying the right template pages
 *************************************************************/

$bp_uri = explode( "/", $_SERVER['REQUEST_URI'] );

if ( $bp_uri[count($bp_uri) - 1] == "" )
	array_pop( $bp_uri );
	
if ( $bp_uri[0] == "" )
	array_shift( $bp_uri );

$bp_uri_count = count( $bp_uri ) - 1;

// catch 'blog'
if ( $bp_uri[$bp_uri_count] == 'blog' )
	bp_catch_uri( 'blog' );

// is the string a guid (lowercase, - instead of spaces, a-z and 0-9 only)
function bp_is_guid( $text ) {
	$safe = trim( strtolower( $text ) );
	$safe = preg_replace( "/[^-0-9a-zA-Z\s]/", '', $safe );
	$safe = preg_replace( "/\s+/", ' ', trim( $safe ) );
	$safe = str_replace( "/-+/", "-", $safe );
	$safe = str_replace( ' ', '-', $safe );
	$safe = preg_replace( "/[-]+/", "-", $safe );
	
	if ( $safe == '' )
		return false;
	
	return true;
}

// takes either a single page name or array of page names and 
// loads the first template file that can be found
function bp_catch_uri( $pages ) {
	global $bp_path;
	
	$bp_path = $pages;
	add_action( "template_redirect", "bp_do_catch_uri", 10, 1 );
}

// loads the first template that can be found
function bp_do_catch_uri() {
	global $bp_path;
	
	$pages = $bp_path;
	if ( is_array( $pages ) ) {
		foreach( $pages as $page ) {
			if ( file_exists( TEMPLATEPATH . "/" . $page . ".php" ) ) {
				require( TEMPLATEPATH . "/" . $page . ".php" ); die;
			}
		}
	} else {
		if ( file_exists( TEMPLATEPATH . "/" . $pages . ".php" ) ) {
			require( TEMPLATEPATH . "/" . $pages . ".php" ); die;
		} else {
			require( TEMPLATEPATH . "/index.php" ); die;
		}
	}
}
?>