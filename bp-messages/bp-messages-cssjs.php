<?php

function messages_add_tinymce() {
	global $bp;
	
	if ( ( $bp['current_component'] == $bp['messages']['slug'] && $bp['current_action'] == 'compose' ) || ( $bp['current_component'] == $bp['messages']['slug'] && $bp['current_action'] == 'view' ) ) {
		echo '
			<script type="text/javascript" src="' . site_url() . '/wp-includes/js/tinymce/tiny_mce.js"></script>
				<script type="text/javascript">
					<!--
					tinyMCE.init({
					theme : "advanced",
					skin : "wp_theme",
					language : "en",
					theme_advanced_toolbar_location : "top",
					theme_advanced_toolbar_align : "left",
					theme_advanced_path_location : "bottom",
					theme_advanced_resizing : true,
					theme_advanced_resize_horizontal : false,
					theme_advanced_buttons1:"bold,italic,strikethrough,|,bullist,numlist,blockquote,|,justifyleft,justifycenter,justifyright,|,link,unlink,image,wp_more,|,fullscreen,wp_adv",theme_advanced_buttons2:"formatselect,underline,justifyfull,forecolor,|,pastetext,pasteword,removeformat,|,media,charmap,|,outdent,indent,|,undo,redo,wp_help",theme_advanced_buttons3:"",
					content_css : "' . site_url() . '/wp-includes/js/tinymce/plugins/wordpress/wordpress.css",
					mode : "exact",
					elements : "message_content",
					width : "100%",
					height : "250",
					plugins:"safari,inlinepopups,autosave,paste,wordpress,media,fullscreen"
					});
					-->
				</script>';
	}
	
}
add_action( 'wp_head', 'messages_add_tinymce' );

function messages_add_js() {
	global $bp;
	
	if ( $bp['current_component'] == $bp['messages']['slug'] )
		wp_enqueue_script( 'bp-messages-js', site_url() . '/wp-content/mu-plugins/bp-messages/js/general.js' );
}
add_action( 'template_redirect', 'messages_add_js' );

function messages_add_structure_css() {
	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-messages-structure', site_url() . '/wp-content/mu-plugins/bp-messages/css/structure.css' );	
}
add_action( 'bp_styles', 'messages_add_structure_css' );


?>