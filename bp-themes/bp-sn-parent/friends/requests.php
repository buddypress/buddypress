<?php get_header() ?>

	<div class="content-header">
	</div>

	<div id="content">

		<h2><?php _e( 'Friendship Requests', 'buddypress' ); ?></h2>
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<?php do_action( 'bp_before_friend_requests_content' ) ?>

		<?php if ( bp_has_members( 'include=' . bp_get_friendship_requests() ) ) : ?>

			<ul id="friend-list" class="item-list">
				<?php while ( bp_members() ) : bp_the_member(); ?>

				<li>
					<div class="item-avatar">
						<a href="<?php bp_member_link() ?>"><?php bp_member_avatar() ?></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php bp_member_link() ?>"><?php bp_member_name() ?></a></div>
						<div class="item-meta"><span class="activity"><?php bp_member_last_active() ?></span></div>
					</div>

					<div class="action">
						<div class="generic-button accept">
							<a href="<?php bp_friend_accept_request_link() ?>"><?php _e( 'Accept', 'buddypress' ); ?></a>
						</div>

						 &nbsp;

						<div class="generic-button reject">
							<a href="<?php bp_friend_reject_request_link() ?>"><?php _e( 'Reject', 'buddypress' ); ?></a>
						</div>

						<?php do_action( 'bp_friend_requests_item_action' ) ?>
					</div>


					<div class="clear"></div>
				</li>

				<?php endwhile; ?>
			</ul>

			<?php do_action( 'bp_friend_requests_content' ) ?>

		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'You have no pending friendship requests.', 'buddypress' ); ?></p>
			</div>

		<?php endif;?>

		<?php do_action( 'bp_after_friend_requests_content' ) ?>

	</div>

<?php get_footer() ?>