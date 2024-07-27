<?php
/**
 * BuddyPress - Pending Membership Invitations
 *
 * @since 8.0.0
 * @version 14.0.0
 */
?>

<?php if ( bp_has_members_invitations() ) : ?>

	<h2 class="bp-screen-reader-text">
		<?php
		/* translators: accessibility text */
		esc_html_e( 'Invitations', 'buddypress' );
		?>
	</h2>

	<div id="pag-top" class="bp-pagination no-ajax">
		<div class="pag-count" id="invitations-count-top">
			<?php bp_members_invitations_pagination_count(); ?>
		</div>

		<div class="pagination-links" id="invitations-pag-top">
			<?php bp_members_invitations_pagination_links(); ?>
		</div>
	</div>

	<?php bp_get_template_part( 'members/single/invitations/invitations-loop' ); ?>

	<div id="pag-bottom" class="bp-pagination no-ajax">
		<div class="pag-count" id="invitations-count-bottom">
			<?php bp_members_invitations_pagination_count(); ?>
		</div>

		<div class="pagination-links" id="invitations-pag-bottom">
			<?php bp_members_invitations_pagination_links(); ?>
		</div>
	</div>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'member-invitations-none' ); ?>

<?php endif;
