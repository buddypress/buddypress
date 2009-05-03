<?php

function messages_ajax_send_reply() {
	global $bp_messages_image_base;
	
	check_ajax_referer('messages_sendreply');

	$result = messages_send_message($_REQUEST['send_to'], $_REQUEST['subject'], $_REQUEST['content'], $_REQUEST['thread_id'], true); 

	if ( $result['status'] ) { ?>
			<div class="avatar-box">
				<?php if ( function_exists('xprofile_get_avatar') ) 
					echo xprofile_get_avatar($result['reply']->sender_id, 1);
				?>
	
				<h3><?php echo bp_core_get_userlink($result['reply']->sender_id) ?></h3>
				<small><?php echo bp_format_time($result['reply']->date_sent) ?></small>
			</div>
			<?php echo $result['reply']->message; ?>
			<div class="clear"></div>
		<?php
	} else {
		$result['message'] = '<img src="' . $bp_messages_image_base . '/warning.gif" alt="Warning" /> &nbsp;' . $result['message'];
		echo "-1|" . $result['message'];
	}
}
add_action( 'wp_ajax_messages_send_reply', 'messages_ajax_send_reply' );

?>