<?php
define('DOING_AJAX', true);
require_once( preg_replace('/(.*)\/wp-content\/.*/', '\1', dirname( __FILE__ ) ) . '/wp-load.php' );

add_filter( 'bp_uri', 'bp_core_referrer' );
wp();

do_action( 'wp_ajax_' . $_POST['action'] );

/* Head shot! */
die('0');
?>