<?php

/**
 * BuddyPress Messages CSS and JS
 *
 * Apply WordPress defined filters to private messages
 *
 * @package BuddyPress
 * @subpackage MessagesScripts
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function messages_add_autocomplete_js() {

	// Include the autocomplete JS for composing a message.
	if ( bp_is_messages_component() && bp_is_current_action( 'compose' ) ) {
		add_action( 'wp_head', 'messages_autocomplete_init_jsblock' );

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			wp_enqueue_script( 'bp-jquery-autocomplete',    BP_PLUGIN_URL . 'bp-messages/js/autocomplete/jquery.autocomplete.dev.js',   array( 'jquery' ), bp_get_version() );
			wp_enqueue_script( 'bp-jquery-autocomplete-fb', BP_PLUGIN_URL . 'bp-messages/js/autocomplete/jquery.autocompletefb.dev.js', array(),           bp_get_version() );
			wp_enqueue_script( 'bp-jquery-bgiframe',        BP_PLUGIN_URL . 'bp-messages/js/autocomplete/jquery.bgiframe.dev.js',       array(),           bp_get_version() );
			wp_enqueue_script( 'bp-jquery-dimensions',      BP_PLUGIN_URL . 'bp-messages/js/autocomplete/jquery.dimensions.dev.js',     array(),           bp_get_version() );

		} else {
			wp_enqueue_script( 'bp-jquery-autocomplete',    BP_PLUGIN_URL . 'bp-messages/js/autocomplete/jquery.autocomplete.js',   array( 'jquery' ), bp_get_version() );
			wp_enqueue_script( 'bp-jquery-autocomplete-fb', BP_PLUGIN_URL . 'bp-messages/js/autocomplete/jquery.autocompletefb.js', array(),           bp_get_version() );
			wp_enqueue_script( 'bp-jquery-bgiframe',        BP_PLUGIN_URL . 'bp-messages/js/autocomplete/jquery.bgiframe.js',       array(),           bp_get_version() );
			wp_enqueue_script( 'bp-jquery-dimensions',      BP_PLUGIN_URL . 'bp-messages/js/autocomplete/jquery.dimensions.js',     array(),           bp_get_version() );
		}
	}
}
add_action( 'bp_actions', 'messages_add_autocomplete_js' );

function messages_add_autocomplete_css() {

	if ( bp_is_messages_component() && bp_is_current_action( 'compose' ) ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			wp_enqueue_style( 'bp-messages-autocomplete', BP_PLUGIN_URL . 'bp-messages/css/autocomplete/jquery.autocompletefb.dev.css', array(), bp_get_version() );
		} else {
			wp_enqueue_style( 'bp-messages-autocomplete', BP_PLUGIN_URL . 'bp-messages/css/autocomplete/jquery.autocompletefb.css',     array(), bp_get_version() );
		}

		wp_print_styles();
	}
}
add_action( 'wp_head', 'messages_add_autocomplete_css' );

function messages_autocomplete_init_jsblock() {
?>

	<script type="text/javascript">
		jQuery(document).ready(function() {
			var acfb = jQuery("ul.first").autoCompletefb({urlLookup: ajaxurl});

			jQuery('#send_message_form').submit( function() {
				var users = document.getElementById('send-to-usernames').className;
				document.getElementById('send-to-usernames').value = String(users);
			});
		});
	</script>

<?php
}

?>
