<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function messages_add_autocomplete_js() {
	global $bp;

	// Include the autocomplete JS for composing a message.
	if ( bp_is_messages_component() && bp_is_current_action( 'compose' ) ) {
		add_action( 'wp_head', 'messages_autocomplete_init_jsblock' );

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			wp_enqueue_script( 'bp-jquery-autocomplete', BP_PLUGIN_URL . '/bp-messages/js/autocomplete/jquery.autocomplete.dev.js', array( 'jquery' ), '20110723' );
			wp_enqueue_script( 'bp-jquery-autocomplete-fb', BP_PLUGIN_URL . '/bp-messages/js/autocomplete/jquery.autocompletefb.dev.js', array(), '20110723' );
			wp_enqueue_script( 'bp-jquery-bgiframe', BP_PLUGIN_URL . '/bp-messages/js/autocomplete/jquery.bgiframe.dev.js', array(), '20110723' );
			wp_enqueue_script( 'bp-jquery-dimensions', BP_PLUGIN_URL . '/bp-messages/js/autocomplete/jquery.dimensions.dev.js', array(), '20110723' );

		} else {
			wp_enqueue_script( 'bp-jquery-autocomplete', BP_PLUGIN_URL . '/bp-messages/js/autocomplete/jquery.autocomplete.js', array( 'jquery' ), '20110723' );
			wp_enqueue_script( 'bp-jquery-autocomplete-fb', BP_PLUGIN_URL . '/bp-messages/js/autocomplete/jquery.autocompletefb.js', array(), '20110723' );
			wp_enqueue_script( 'bp-jquery-bgiframe', BP_PLUGIN_URL . '/bp-messages/js/autocomplete/jquery.bgiframe.js', array(), '20110723' );
			wp_enqueue_script( 'bp-jquery-dimensions', BP_PLUGIN_URL . '/bp-messages/js/autocomplete/jquery.dimensions.js', array(), '20110723' );
		}
	}
}
add_action( 'bp_actions', 'messages_add_autocomplete_js' );

function messages_add_autocomplete_css() {
	global $bp;

	if ( bp_is_messages_component() && bp_is_current_action( 'compose' ) ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			wp_enqueue_style( 'bp-messages-autocomplete', BP_PLUGIN_URL . '/bp-messages/css/autocomplete/jquery.autocompletefb.dev.css', array(), '20110723' );
		else
			wp_enqueue_style( 'bp-messages-autocomplete', BP_PLUGIN_URL . '/bp-messages/css/autocomplete/jquery.autocompletefb.css', array(), '20110723' );

		wp_print_styles();
	}
}
add_action( 'wp_head', 'messages_add_autocomplete_css' );

function messages_autocomplete_init_jsblock() {
?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			var acfb =
			jQuery("ul.first").autoCompletefb({urlLookup:'<?php echo site_url( 'wp-load.php' ) ?>'});

			jQuery('#send_message_form').submit( function() {
				var users = document.getElementById('send-to-usernames').className;
				document.getElementById('send-to-usernames').value = String(users);
			});
		});
	</script>
<?php
}