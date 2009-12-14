<?php do_action( 'bp_before_friend_requests_content' ) ?>

<?php if ( bp_has_friendships() ) : ?>

	<ul id="friend-list" class="item-list">
		<?php while ( bp_user_friendships() ) : bp_the_friendship(); ?>

			<li>
				<?php bp_friend_avatar_thumb() ?>
				<h4><?php bp_friend_link() ?></h4>
				<span class="activity"><?php bp_friend_time_since_requested() ?></span>

				<?php do_action( 'bp_friend_requests_item' ) ?>

				<div class="action">
					<a class="button accept" href="<?php bp_friend_accept_request_link() ?>"><?php _e( 'Accept', 'buddypress' ); ?></a> &nbsp;
					<a class="button reject confirm" href="<?php bp_friend_reject_request_link() ?>"><?php _e( 'Reject', 'buddypress' ); ?></a>

					<?php do_action( 'bp_friend_requests_item_action' ) ?>
				</div>
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
