<?php do_action( 'bp_before_message_thread_content' ) ?>

<div id="message-thread">

	<?php if ( bp_thread_has_messages() ) : ?>

		<h3 id="message-subject"><?php bp_the_thread_subject() ?></h3>

		<p id="message-recipients">
			<span class="highlight">
				<?php printf( __('Sent between %s and %s', 'buddypress'), bp_get_the_thread_recipients(), '<a href="' . bp_get_loggedin_user_link() . '" title="' . bp_get_loggedin_user_fullname() . '">' . bp_get_loggedin_user_fullname() . '</a>' ) ?>
			</span>
		</p>

		<?php do_action( 'bp_before_message_thread_list' ) ?>

		<?php while ( bp_thread_messages() ) : bp_thread_the_message(); ?>

			<div class="message-box">

				<div class="message-metadata">

					<?php do_action( 'bp_before_message_meta' ) ?>

					<?php bp_the_thread_message_sender_avatar( 'type=thumb&width=30&height=30' ) ?>
					<h3><a href="<?php bp_the_thread_message_sender_link() ?>" title="<?php bp_the_thread_message_sender_name() ?>"><?php bp_the_thread_message_sender_name() ?></a> <span class="activity"><?php bp_the_thread_message_time_since() ?></span></h3>

					<?php do_action( 'bp_after_message_meta' ) ?>

				</div>

				<?php do_action( 'bp_before_message_content' ) ?>

				<div class="message-content">

					<?php bp_the_thread_message_content() ?>

				</div>

				<?php do_action( 'bp_after_message_content' ) ?>

				<div class="clear"></div>

			</div>

		<?php endwhile; ?>

		<?php do_action( 'bp_after_message_thread_list' ) ?>

		<?php do_action( 'bp_before_message_thread_reply' ) ?>

		<form id="send-reply" action="<?php bp_messages_form_action() ?>" method="post" class="standard-form">

			<div class="message-box">

				<div class="message-metadata">

					<?php do_action( 'bp_before_message_meta' ) ?>

					<div class="avatar-box">
						<?php bp_loggedin_user_avatar( 'type=thumb&height=30&width=30' ) ?>

						<h3><?php _e( 'Send a Reply', 'buddypress' ) ?></h3>
					</div>

					<?php do_action( 'bp_after_message_meta' ) ?>

				</div>

				<div class="message-content">

					<?php do_action( 'bp_before_message_reply_box' ) ?>

					<textarea name="content" id="message_content" rows="15" cols="40"></textarea>

					<?php do_action( 'bp_after_message_reply_box' ) ?>

					<div class="submit">
						<input type="submit" name="send" value="<?php _e( 'Send Reply', 'buddypress' ) ?> &rarr;" id="send_reply_button"/>
					</div>

					<input type="hidden" id="thread_id" name="thread_id" value="<?php bp_the_thread_id(); ?>" />
					<input type="hidden" name="subject" id="subject" value="<?php _e( 'Re: ', 'buddypress' ); echo str_replace( 'Re: ', '', bp_get_the_thread_subject() ); ?>" />
					<?php wp_nonce_field( 'messages_send_message', 'send_message_nonce' ) ?>

				</div>

			</div>

		</form>

		<?php do_action( 'bp_after_message_thread_reply' ) ?>

	<?php endif; ?>

	<?php do_action( 'bp_after_message_thread_content' ) ?>

</div>