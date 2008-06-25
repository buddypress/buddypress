<?php

function messages_add_js() {
	global $bp_messages_image_base;
	global $current_action, $current_component;
	global $bp_messages_slug;
	
	if ( strpos( $_GET['page'], 'messages' ) !== false || ( $current_component == $bp_messages_slug && $current_action == $bp_messages_slug ) || ( $current_component == $bp_messages_slug && $current_action == 'compose') ) { ?>
		<script type="text/javascript">
			var ajaxurl = '<?php echo get_option('siteurl') . "/wp-admin/admin-ajax.php"; ?>';
		
			function checkAll() {
				var checkboxes = document.getElementsByTagName("input");
				for(var i=0; i<checkboxes.length; i++) {
					if(checkboxes[i].type == "checkbox") {
						if($("check_all").checked == "") {
							checkboxes[i].checked = "";
						}
						else {
							checkboxes[i].checked = "checked";
						}
					}
				}
			}
			
			// Set up ajax
			jQuery(document).ready( function() {
				jQuery("input#send_reply_button").click( 
					function() {
						tinyMCE.triggerSave(true, true);
						
						var rand = Math.floor(Math.random()*100001);
						jQuery("form#send-reply").before('<div style="display:none;" class="ajax_reply" id="' + rand + '"><img src="<?php echo $bp_messages_image_base; ?>/loading.gif" alt="Loading" /> &nbsp;Sending Message...</div>');
						jQuery("div#" + rand).fadeIn();
					
						jQuery.post( ajaxurl, {
							action: 'messages_send_reply',
							'cookie': encodeURIComponent(document.cookie),
							'_wpnonce': jQuery("input#_wpnonce").val(),
							
							'content': jQuery("#message_content").val(),
							'send_to': jQuery("input#send_to").val(),
							'subject': jQuery("input#subject").val(),
							'thread_id': jQuery("input#thread_id").val()
						},
						function(response)
						{
							response = response.substr(0, response.length-1);
							var css_class = 'message-box';
							
							setTimeout( function() {
								jQuery("div#" + rand).slideUp();
							}, 500);
							
							setTimeout( function() {
								var err_num = response.split('|');
								if ( err_num[0] == "-1" ) {
									response = err_num[1];
									css_class = 'error-box';
								}
								
								tinyMCE.activeEditor.setContent('')
								jQuery("div#" + rand).html(response).attr('class', css_class).slideDown();
							}, 1250);	
						});
					
						return false;
					}
				);				
			});

			
		</script>
		<script type="text/javascript" src="<?php echo get_option('siteurl') . '/wp-includes/js/tinymce/tiny_mce.js'; ?>"></script>
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
			content_css : "<?php echo get_option('siteurl') . '/wp-includes/js/tinymce/plugins/wordpress/wordpress.css'; ?>",
			mode : "exact",
			elements : "message_content",
			width : "100%",
			height : "250",
			plugins:"safari,inlinepopups,autosave,paste,wordpress,media,fullscreen"
			});
			-->
		</script>
		<?php
	}
}


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

?>