<?php get_header() ?>

<div class="content-header">

</div>

<div id="content">
	<h2><?php _e("Compose Message", "buddypress"); ?></h2>
	
	<?php do_action( 'template_notices' ) ?>

	<form action="<?php bp_messages_form_action('compose') ?>" method="post" id="send_message_form" class="standard-form">
	<div id="poststuff">
		<p>			
		<div id="titlediv">
			<h3><?php _e("Send To", 'buddypress') ?> <small><?php _e("(Use username - autocomplete coming soon)", "buddypress"); ?></small></h3>
			<div id="titlewrap">
				<input type="text" name="send_to" id="send_to" value="<?php bp_messages_username_value() ?>" />
				<?php if ( is_site_admin() ) : ?><br /><input type="checkbox" id="send-notice" name="send-notice" value="1" /> <?php _e("This is a notice to all users.", "buddypress"); ?><?php endif; ?>
			</div>
		</div>
		</p>

		<p>
		<div id="titlediv">
			<h3><?php _e("Subject", 'buddypress') ?></h3>
			<div id="titlewrap">
				<input type="text" name="subject" id="subject" value="<?php bp_messages_subject_value() ?>" />
			</div>
		</div>
		</p>
		
		<p>
			<div id="postdivrich" class="postarea">
				<h3><?php _e("Message", 'buddypress') ?></h3>
				<div id="editorcontainer">
					<textarea name="content" id="message_content" rows="15" cols="40"><?php bp_messages_content_value() ?></textarea>
				</div>
			</div>
		</p>
		
		<p class="submit">
				<input type="submit" value="<?php _e("Send", 'buddypress') ?> &raquo;" name="send" id="send" style="font-weight: bold" />
		</p>
	</div>
	</form>
	<script type="text/javascript">
		document.getElementById("send_to").focus();
	</script>
</div>

<?php get_footer() ?>

