<?php
/**
 * BuddyPress - Sent Membership Invitations
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 8.0.0
 */
?>
<h2 class="bp-screen-reader-text">
	<?php
	/* translators: accessibility text */
	esc_html_e( 'Send Invitations', 'buddypress' );
	?>
</h2>

<?php if ( bp_user_can( bp_displayed_user_id(), 'bp_members_send_invitation' ) ) : ?>

<form class="standard-form members-invitation-form" id="members-invitation-form" method="post">
	<p class="description"><?php esc_html_e( 'Fill out the form below to invite a new user to join this site. Upon submission of the form, an email will be sent to the invitee containing a link to accept your invitation. You may also add a custom message to the email.', 'buddypress' ); ?></p>

	<label for="bp_members_invitation_invitee_email"><?php esc_html_e( 'Email address of new user', 'buddypress' ); ?></label>
	<input id="bp_members_invitation_invitee_email" type="email" name="invitee_email" required="required">

	<label for="bp_members_invitation_message"><?php esc_html_e( 'Add a personalized message to the invitation (optional)', 'buddypress' ); ?></label>
	<textarea id="bp_members_invitation_message" name="content"></textarea>

	<input type="hidden" name="action" value="send-invite">

	<?php wp_nonce_field( 'bp_members_invitation_send_' . bp_displayed_user_id() ) ?>
	<p>
		<input id="submit" type="submit" name="submit" class="submit" value="<?php esc_attr_e( 'Send Invitation', 'buddypress' ) ?>" />
	</p>
</form>

<?php else : ?>

	<p class="bp-feedback error">
		<span class="bp-icon" aria-hidden="true"></span>
		<span class="bp-help-text">
			<?php echo esc_html( apply_filters( 'members_invitations_form_access_restricted', __( 'Sorry, you are not allowed to send invitations.', 'buddypress' ) ) ); ?>
		</span>
	</p>

<?php endif; ?>
