<?php
/**
 * BuddyPress - Membership Invitations Loop
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 8.0.0
 */

?>
<form action="" method="post" id="invitations-bulk-management">
	<table class="invitations">
		<thead>
			<tr>
				<th class="bulk-select-all"><input id="select-all-invitations" type="checkbox">
					<label class="bp-screen-reader-text" for="select-all-invitations">
						<?php
						/* translators: accessibility text */
						esc_html_e( 'Select all', 'buddypress' );
						?>
					</label>
				</th>
				<th class="title"><?php esc_html_e( 'Invitee', 'buddypress' ); ?></th>
				<th class="content"><?php esc_html_e( 'Message', 'buddypress' ); ?></th>
				<th class="sent"><?php esc_html_e( 'Sent', 'buddypress' ); ?></th>
				<th class="accepted"><?php esc_html_e( 'Accepted', 'buddypress' ); ?></th>
				<th class="date"><?php esc_html_e( 'Date Modified', 'buddypress' ); ?></th>
				<th class="actions"><?php esc_html_e( 'Actions', 'buddypress' ); ?></th>
			</tr>
		</thead>

		<tbody>

			<?php while ( bp_the_members_invitations() ) : bp_the_members_invitation(); ?>

				<tr>
					<td class="bulk-select-check">
						<label for="<?php bp_the_members_invitation_property( 'id', 'attribute' ); ?>">
							<input id="<?php bp_the_members_invitation_property( 'id', 'attribute' ); ?>" type="checkbox" name="members_invitations[]" value="<?php bp_the_members_invitation_property( 'id', 'attribute' ); ?>" class="invitation-check">
							<span class="bp-screen-reader-text">
								<?php
									/* translators: accessibility text */
									esc_html_e( 'Select this invitation', 'buddypress' );
								?>
							</span>
						</label>
					</td>
					<td class="invitation-invitee"><?php bp_the_members_invitation_property( 'invitee_email' );  ?></td>
					<td class="invitation-content"><?php bp_the_members_invitation_property( 'content' );  ?></td>
					<td class="invitation-sent"><?php bp_the_members_invitation_property( 'invite_sent' );  ?></td>
					<td class="invitation-accepted"><?php bp_the_members_invitation_property( 'accepted' );  ?></td>
					<td class="invitation-date-modified"><?php bp_the_members_invitation_property( 'date_modified' );   ?></td>
					<td class="invitation-actions"><?php bp_the_members_invitation_action_links(); ?></td>
				</tr>

			<?php endwhile; ?>

		</tbody>
	</table>

	<div class="invitations-options-nav">
		<?php bp_members_invitations_bulk_management_dropdown(); ?>
	</div><!-- .invitations-options-nav -->

	<?php wp_nonce_field( 'invitations_bulk_nonce', 'invitations_bulk_nonce' ); ?>
</form>
