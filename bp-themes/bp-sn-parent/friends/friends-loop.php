<?php do_action( 'bp_before_my_friends_loop' ) ?>

<div id="friends-loop">

	<?php if ( bp_has_members( 'user_id=' . bp_displayed_user_id() ) ) : ?>

		<div class="pagination">

			<div class="pag-count" id="member-dir-count">
				<?php bp_members_pagination_count() ?>
			</div>

			<div class="pagination-links" id="member-dir-pag">
				<?php bp_members_pagination_links() ?>
			</div>

		</div>

		<?php do_action( 'bp_before_my_friends_list' ) ?>

		<ul id="friend-list" class="item-list">

			<?php while ( bp_members() ) : bp_the_member(); ?>

				<li>
					<div class="item-avatar">
						<a href="<?php bp_member_link() ?>"><?php bp_member_avatar() ?></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php bp_member_link() ?>"><?php bp_member_name() ?></a></div>
						<div class="item-meta"><span class="activity"><?php bp_member_last_active() ?></span></div>

						<div class="field-data">
							<div class="field-name"><?php bp_member_total_friend_count() ?></div>
							<div class="field-name xprofile-data"><?php bp_member_random_profile_data() ?></div>
						</div>

						<?php do_action( 'bp_directory_members_featured_item' ) ?>
					</div>

					<div class="action">
						<?php bp_add_friend_button() ?>

						<?php do_action( 'bp_my_friends_list_item_action' ) ?>
					</div>
				</li>

			<?php endwhile; ?>

			<?php do_action( 'bp_after_my_friends_list' ) ?>

		</ul>

	<?php else: ?>

		<div id="message" class="info">
			<p><?php _e( "No friends were found.", 'buddypress' ) ?></p>
		</div>

	<?php endif; ?>

</div>

<?php do_action( 'bp_after_my_friends_loop' ) ?>