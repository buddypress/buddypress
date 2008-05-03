<?php

function messages_add_js() {
	if ( strpos( $_GET['page'], 'messages' ) !== false ) { ?>
		<script type="text/javascript">
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

			// jQuery('table#message-list tr').click(function () {
			// 	var the_id = $(this).attr('id');
			// 	alert(the_id);
			// });
		
			
		</script>
		<script type="text/javascript" src="../wp-includes/js/tinymce/tiny_mce.js"></script>
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
			theme_advanced_buttons1 : "bold,italic,strikethrough,separator,bullist,numlist,outdent,indent,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,spellchecker,forecolor,fontsizeselect",
			theme_advanced_buttons2 : "",
			theme_advanced_buttons3 : "",
			content_css : "<?php echo get_option('siteurl') . '/wp-includes/js/tinymce/plugins/wordpress/wordpress.css'; ?>",
			mode : "exact",
			elements : "content",
			width : "99%",
			height : "300"
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

			
		
	</style>
	<?php
}

?>