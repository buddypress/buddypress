<?php

function messages_add_autocomplete_js() {
	global $bp;

	// Include the autocomplete JS for composing a message.
	if ( $bp->current_component == $bp->messages->slug && 'compose' == $bp->current_action ) {
		add_action( 'wp_head', 'messages_autocomplete_init_jsblock' );

		wp_enqueue_script( 'bp-jquery-autocomplete', BP_PLUGIN_URL . '/bp-messages/js/autocomplete/jquery.autocomplete.js', array( 'jquery' ) );
		wp_enqueue_script( 'bp-jquery-autocomplete-fb', BP_PLUGIN_URL . '/bp-messages/js/autocomplete/jquery.autocompletefb.js' );
		wp_enqueue_script( 'bp-jquery-bgiframe', BP_PLUGIN_URL . '/bp-messages/js/autocomplete/jquery.bgiframe.min.js' );
		wp_enqueue_script( 'bp-jquery-dimensions', BP_PLUGIN_URL . '/bp-messages/js/autocomplete/jquery.dimensions.js' );
	}

}
add_action( 'template_redirect', 'messages_add_autocomplete_js', 1 );

function messages_add_autocomplete_css() {
	global $bp;

	if ( $bp->current_component == $bp->messages->slug && 'compose' == $bp->current_action ) {
		wp_enqueue_style( 'bp-messages-autocomplete', BP_PLUGIN_URL . '/bp-messages/css/autocomplete/jquery.autocompletefb.css' );
		wp_print_styles();
	}
}
add_action( 'wp_head', 'messages_add_autocomplete_css' );

function messages_autocomplete_init_jsblock() {
?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			var acfb =
			jQuery("ul.first").autoCompletefb({urlLookup:'<?php echo $bp->root_domain . str_replace( 'index.php', 'wp-load.php', $_SERVER['SCRIPT_NAME'] ) ?>'});

			jQuery('#send_message_form').submit( function() {
				var users = document.getElementById('send-to-usernames').className;
				document.getElementById('send-to-usernames').value = String(users);
			});
		});
	</script>
<?php
}