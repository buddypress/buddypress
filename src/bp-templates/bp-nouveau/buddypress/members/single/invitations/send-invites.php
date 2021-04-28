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

<p class="bp-feedback info">
	<span class="bp-icon" aria-hidden="true"></span>
	<span class="bp-help-text">
		<?php esc_html_e( 'Fill out the form below to invite a new user to join this site. Upon submission of the form, an email will be sent to the invitee containing a link to accept your invitation. You may also add a custom message to the email.', 'buddypress' ); ?>
	</span>
</p>

<form class="standard-form network-invitation-form" id="network-invitation-form" method="post">
	<label for="bp_members_invitation_invitee_email">
		<?php esc_html_e( 'Email', 'buddypress' ); ?>
		<span class="bp-required-field-label"><?php esc_html_e( '(required)', 'buddypress' ); ?></span>
	</label>
	<input id="bp_members_invitation_invitee_email" type="email" name="invitee_email" required="required">

	<label for="bp_members_invitation_message">
		<?php esc_html_e( 'Add a personalized message to the invitation (optional)', 'buddypress' ); ?>
	</label>
	<textarea id="bp_members_invitation_message" name="invite_message"></textarea>

	<input type="hidden" name="action" value="send-invite">

	<?php bp_nouveau_submit_button( 'member-send-invite' ); ?>
</form>
