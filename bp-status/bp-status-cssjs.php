<?php

function bp_status_add_js() {
	wp_enqueue_script( 'bp-status-js', BP_PLUGIN_URL . '/bp-status/js/general.js' );
}
add_action( 'template_redirect', 'bp_status_add_js', 2 );

?>