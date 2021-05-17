<?php
/**
 * BuddyPress - Send a Membership Invitation.
 *
 * @since 8.0.0
 * @version 8.0.0
 */
?>
<h2 class="bp-screen-reader-text">
	<?php
	/* translators: accessibility text */
	esc_html_e( 'Send Invitation', 'buddypress' );
	?>
</h2>

<?php if ( bp_user_can( bp_displayed_user_id(), 'bp_members_send_invitation' ) ) : ?>

	<?php bp_nouveau_user_feedback( 'member-invitations-help' ); ?>

	<form class="standard-form network-invitation-form" id="network-invitation-form" method="post">
		<label for="bp_members_invitation_invitee_email">
			<?php esc_html_e( 'Email', 'buddypress' ); ?>
			<span class="bp-required-field-label"><?php esc_html_e( '(required)', 'buddypress' ); ?></span>
		</label>
		<input id="bp_members_invitation_invitee_email" type="email" name="invitee_email" required="required">

		<label for="bp_members_invitation_message">
			<?php esc_html_e( 'Add a personalized message to the invitation (optional)', 'buddypress' ); ?>
		</label>
		<textarea id="bp_members_invitation_message" name="content"></textarea>

		<input type="hidden" name="action" value="send-invite">

		<?php bp_nouveau_submit_button( 'member-send-invite' ); ?>
	</form>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'member-invitations-not-allowed' ); ?>

<?php endif; ?>
