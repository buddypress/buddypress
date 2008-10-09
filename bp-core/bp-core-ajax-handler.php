<?php
define('DOING_AJAX', true);
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

do_action( 'wp_ajax_' . $_POST['action'] );
die('0');
?>