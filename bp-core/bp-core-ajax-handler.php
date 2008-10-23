<?php
define('DOING_AJAX', true);
require_once( dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php' );

do_action( 'wp_ajax_' . $_POST['action'] );
die('0');
?>