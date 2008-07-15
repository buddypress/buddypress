<?php

function messages_add_js() {
	global $bp_messages_image_base;
	global $current_action, $current_component;
	global $bp_messages_slug;
	
	if ( ( $current_component == $bp_messages_slug && $current_action == 'compose' ) || ( $current_component == $bp_messages_slug && $current_action == 'view' ) ) {
		echo '
			<script type="text/javascript" src="' . get_option('siteurl') . '/wp-includes/js/tinymce/tiny_mce.js"></script>
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
					content_css : "' . get_option('siteurl') . '/wp-includes/js/tinymce/plugins/wordpress/wordpress.css",
					mode : "exact",
					elements : "message_content",
					width : "100%",
					height : "250",
					plugins:"safari,inlinepopups,autosave,paste,wordpress,media,fullscreen"
					});
					-->
				</script>';
	}
	
	if ( strpos( $_GET['page'], 'messages' ) !== false || $current_component == $bp_messages_slug ) {
		echo '
			<script src="' . get_option('siteurl') . '/wp-content/mu-plugins/bp-messages/js/general.js" type="text/javascript"></script>';
	}
}
add_action( 'wp_head', 'messages_add_js' );
add_action( 'admin_menu', 'messages_add_js' );


/**************************************************************************
 add_css()
  
 Inserts the CSS needed to style the messages pages.
 **************************************************************************/	

function messages_add_css()
{
	?>
	<style type="text/css">
		.unread td { 
			font-weight: bold; 
			background: #ffffec;
		}
		
		#send_message_form fieldset input {
			width: 98%;
			font-size: 1.7em;
			padding: 4px 3px;
		}
		
		.message-box {
			border: 1px solid #eee;
			border-top: 3px solid #EAF3FA;
			padding: 15px 10px;
			padding-left: 135px;
		}
			.message-box .avatar-box {
				float: left;
				width: 110px;
				margin: 0 0 0 -125px;
			}
				.message-box .avatar-box h3 {
					margin: 10px 0 5px 0;
				}
		
		#message-list td {
			vertical-align: middle;
		}
			#message-list .is-read {
				width: 1px;
			}
			
			#message-list .avatar {
				width: 50px;
			}
			
				img.avatar {
					padding: 3px;
					border: 1px solid #ddd;
					background: #fff;
				}
			
			#message-list .sender-details {
				
			}
				#message-list .sender-details h3 {
					margin: 0 0 3px 0;
				}
				
			#message-list .sender-details {
				width: 160px;
			}
			
			#message-list .message-details h4 {
				margin: 0 0 3px 0;
			}
		
		div.ajax_reply, div.error-box {
			text-align: center;
			font-size: 13px;
			padding: 15px;
			border: 1px solid #eee;
			border-bottom: none;
			border-top: 3px solid #EAF3FA;
			background: #EAF3FA;
			color: #2583AD;
		}
		
		div.error-box {
			background: #FFFEDB;
			color: #D54E21;
			border-top: none;
		}
			div.div.ajax_reply img, div.error-box img { vertical-align: middle; }

			
		
	</style>
	<?php
}
add_action( 'admin_menu', 'messages_add_css' );

?>