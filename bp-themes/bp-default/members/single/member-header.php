<?php bp_displayed_user_avatar( 'type=full' ) ?>

<h2 class="fn"><a href="<?php bp_user_link() ?>"><?php bp_displayed_user_fullname() ?></a> <span class="activity"><?php bp_last_activity( bp_displayed_user_id() ) ?></span></h2>

<div id="item-meta">
	<div id="latest-update">
		<?php bp_activity_latest_update( bp_displayed_user_id() ) ?>
	</div>

	<div id="item-buttons">
		<?php if ( function_exists( 'bp_add_friend_button' ) ) : ?>
			<?php bp_add_friend_button() ?>
		<?php endif; ?>

		<?php if ( is_user_logged_in() && !bp_is_my_profile() && function_exists( 'bp_send_public_message_link' ) ) : ?>
			<div class="generic-button" id="send-public-message">
				<a href="<?php bp_send_public_message_link() ?>" title="<?php _e( 'Send a public message to this user', 'buddypress' ) ?>"><?php _e( 'Send Public Message', 'buddypress' ) ?></a>
			</div>
		<?php endif; ?>

		<?php if ( is_user_logged_in() && !bp_is_my_profile() && function_exists( 'bp_send_private_message_link' ) ) : ?>
			<div class="generic-button" id="send-private-message">
				<a href="<?php bp_send_private_message_link() ?>" title="<?php _e( 'Send a private message to this user', 'buddypress' ) ?>"><?php _e( 'Send Private Message', 'buddypress' ) ?></a>
			</div>
		<?php endif; ?>
	</div>

	<?php
	 /***
	  * If you'd like to show specific profile fields here use:
	  * bp_profile_field_data( 'field=About Me' ); -- Pass the name of the field
	  */
	?>

	<?php do_action( 'bp_profile_header_content' ) ?>

</div>

<?php do_action( 'template_notices' ) ?>