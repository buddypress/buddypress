<?php
function xprofile_ajax_reorder_fields() {
	
	check_ajax_referer('xprofile_reorder_fields');

	// TODO change the order of the fields
	//$stuff = $_REQUEST['group'], $_REQUEST['row'], $_REQUEST['field_ids']; 

}
add_action( 'wp_ajax_xprofile_reorder_fields', 'xprofile_ajax_reorder_fields' );
?>