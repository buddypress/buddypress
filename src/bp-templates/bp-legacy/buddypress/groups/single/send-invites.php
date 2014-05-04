<?php do_action( 'bp_before_group_send_invites_content' ); ?>

<?php if ( bp_get_total_friend_count( bp_loggedin_user_id() ) ) : ?>

	<?php /* 'send-invite-form' is important for AJAX support */ ?>
	<form action="<?php bp_group_send_invite_form_action(); ?>" method="post" id="send-invite-form" class="standard-form" role="main">

		<div class="invite">

			<?php bp_get_template_part( 'groups/single/invites-loop' ); ?>

		</div>

		<?php /* This is important, don't forget it */ ?>
		<input type="hidden" name="group_id" id="group_id" value="<?php bp_group_id(); ?>" />

	</form><!-- #send-invite-form -->

<?php endif; ?>

<?php do_action( 'bp_after_group_send_invites_content' ); ?>
