<?php
/**
 * BuddyPress - Groups Requests Loop
 *
 * @since 1.0.0
 */
?>

<?php if ( bp_group_has_membership_requests( bp_ajax_querystring( 'membership_requests' ) ) ) : ?>

	<?php bp_nouveau_pagination( 'top' ); ?>

	<ul id="request-list" class="item-list bp-list">
		<?php while ( bp_group_membership_requests() ) : bp_group_the_membership_request(); ?>

			<li>
				<?php bp_group_request_user_avatar_thumb(); ?>
				<h4><?php bp_group_request_user_link(); ?> <span class="comments"><?php bp_group_request_comment(); ?></span></h4>
				<span class="activity"><?php bp_group_request_time_since_requested(); ?></span>

				<?php bp_nouveau_group_hook( '', 'membership_requests_admin_item' ); ?>

				<?php bp_nouveau_groups_request_buttons(); ?>
			</li>

		<?php endwhile; ?>
	</ul>

	<?php bp_nouveau_pagination( 'bottom' ); ?>

	<?php else:

		bp_nouveau_user_feedback( 'group-requests-none' );

	endif; ?>
