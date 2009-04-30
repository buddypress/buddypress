<?php
/*
 * /messages/compose.php
 * Displays the compose message form. Scripts for recipient autocomplete are 
 * loaded automatically. DO NOT change the form field IDs or names as these are important
 * for javascript functionality.
 *
 * Loaded on URL:
 * 'http://example.org/members/[username]/messages/compose/
 */
?>

<?php get_header() ?>

<div id="main">
	
	<h2><?php _e( 'Compose Message', 'buddypress' ); ?></h2>
	
	<?php do_action( 'template_notices' ) ?>

	<form action="<?php bp_messages_form_action('compose') ?>" method="post" id="send-message-form" class="standard-form">

		<?php do_action( 'messages_custom_fields_input_before' ) ?>
			
		<label for="send-to-input"><?php _e("Send To", 'buddypress') ?> &nbsp;<img src="<?php bp_message_loading_image_src() ?>" alt="Loading" id="send-to-loading" style="display: none" height="7" /></label>
		<ul class="first acfb-holder">
			<li>
				<?php bp_message_get_recipient_tabs() ?>
				<input type="text" name="send-to-input" class="send-to-input" id="send-to-input" />
			</li>
		</ul>
		
		<?php if ( is_site_admin() ) : ?>
			
			<input type="checkbox" id="send-notice" name="send-notice" value="1" /> <?php _e( "This is a notice to all users.", "buddypress" ) ?>
			
		<?php endif; ?>

		<label for="subject"><?php _e( 'Subject', 'buddypress') ?></label>
		<input type="text" name="subject" id="subject" value="<?php bp_messages_subject_value() ?>" />

		<label for="content"><?php _e( 'Message', 'buddypress') ?></label>
		<textarea name="content" id="message_content" rows="15" cols="40"><?php bp_messages_content_value() ?></textarea>

		<input type="hidden" name="send_to_usernames" id="send-to-usernames" value="" class="<?php bp_message_get_recipient_usernames() ?>" />
		
		<?php do_action( 'messages_custom_fields_input_after' ) ?>
		
		<p class="submit">
			<input type="submit" value="<?php _e("Send", 'buddypress') ?> &raquo;" name="send" id="send" />
		</p>
		
		<?php wp_nonce_field( 'messages_send_message' ) ?>

	</form>
	
	<script type="text/javascript">
		document.getElementById("send-to-input").focus();
	</script>
	
</div>

<?php get_footer() ?>

