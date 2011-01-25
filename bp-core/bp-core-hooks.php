<?php

/** Loaded ********************************************************************/

add_action( 'plugins_loaded', 'bp_loaded',  10 );

add_action( 'bp_loaded',      'bp_include', 2  );

add_action( 'wp',             'bp_actions', 2  );

add_action( 'wp',             'bp_screens', 2  );

/** Init **********************************************************************/

// Attach bp_init to WordPress init
add_action( 'init',       'bp_init'                    );

// Parse the URI and set globals
add_action( 'bp_init',    'bp_core_set_uri_globals', 2 );

// Setup component globals
add_action( 'bp_init',    'bp_setup_globals',        4 );

// Setup the navigation menu
add_action( 'bp_init',    'bp_setup_nav',            8 );

// Setup the title
add_action( 'bp_init',    'bp_setup_title',          8 );

// Setup widgets
add_action( 'bp_init',    'bp_setup_widgets',        8 );
	
// Setup admin bar
add_action( 'bp_loaded',  'bp_core_load_admin_bar'    );

/** The hooks *****************************************************************/

/**
 * Include files on this action
 */
function bp_include() {
	do_action( 'bp_include' );
}

/**
 * Setup global variables and objects
 */
function bp_setup_globals() {
	do_action( 'bp_setup_globals' );
}

/**
 * Set navigation elements
 */
function bp_setup_nav() {
	do_action( 'bp_setup_nav' );
}

/**
 * Set the page title
 */
function bp_setup_title() {
	do_action( 'bp_setup_title' );
}

/**
 * Register widgets
 */
function bp_setup_widgets() {
	do_action( 'bp_register_widgets' );
}

/**
 * Initlialize code
 */
function bp_init() {
	do_action( 'bp_init' );
}

/**
 * Attached to plugins_loaded
 */
function bp_loaded() {
	do_action( 'bp_loaded' );
}

/**
 * Attach potential template actions
 */
function bp_actions() {
	do_action( 'bp_actions' );
}

/**
 * Attach potential template screens
 */
function bp_screens() {
	do_action( 'bp_screens' );
}

?>
