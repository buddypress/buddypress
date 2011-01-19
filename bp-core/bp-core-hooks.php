<?php

/** Loaded ********************************************************************/

add_action( 'plugins_loaded', 'bp_loaded',  10 );

add_action( 'bp_loaded',      'bp_include', 2  );


/** Init **********************************************************************/

// Attach bp_init to WordPress init
add_action( 'init',    'bp_init'                     );

// Parse the URI and set globals
add_action( 'bp_init', 'bp_core_set_uri_globals',  2 );

// Setup component globals
add_action( 'bp_init', 'bp_setup_globals',         4 );

// Setup root directories for components
add_action( 'bp_init', 'bp_setup_root_components', 6 );

// Setup the navigation menu
add_action( 'bp_init', 'bp_setup_nav',             8 );

// Setup widgets
add_action( 'bp_init', 'bp_setup_widgets',         8 );
	
// Setup admin bar
add_action( 'bp_init', 'bp_core_load_admin_bar'      );


?>
