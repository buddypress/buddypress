<?php
/* Load the WP environment */
require_once( preg_replace('%(.*)[/\\\\]wp-content[/\\\\].*%', '\1', dirname( __FILE__ ) ) . '/wp-load.php' ); 
header( 'Content: text/javascript' );
?>

jQuery(document).ready(function() {
	var acfb = 
	jQuery("ul.first").autoCompletefb({urlLookup:'<?php echo BP_PLUGIN_URL . '/bp-messages/autocomplete/bp-messages-autocomplete.php' ?>'});

	jQuery('#send_message_form').submit( function() {
		var users = document.getElementById('send-to-usernames').className;
		document.getElementById('send-to-usernames').value = String(users);
	});

});