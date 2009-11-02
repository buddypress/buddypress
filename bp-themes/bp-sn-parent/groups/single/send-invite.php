<?php get_header() ?>

	<div class="content-header">

	</div>

	<div id="content">

		<div class="pagination">

			<div class="pagination-links" id="pag">
				<?php bp_group_pagination() ?>
			</div>

		</div>

		<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

			<h2><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a> &raquo; <?php _e( 'Send Invites', 'buddypress' ); ?></h2>

			<?php do_action( 'bp_before_group_send_invites_content' ) ?>

			<?php if ( bp_has_friends_to_invite() ) : ?>

				<form action="<?php bp_group_send_invite_form_action() ?>" method="post" id="send-invite-form">

					<div class="left-menu">

						<h4><?php _e( 'Select Friends', 'buddypress' ) ?> <span class="ajax-loader"></span></h4>

						<div id="invite-list">
							<ul>
								<?php bp_new_group_invite_friend_list() ?>
							</ul>

							<?php wp_nonce_field( 'groups_invite_uninvite_user', '_wpnonce_invite_uninvite_user' ) ?>
						</div>

					</div>

					<div class="main-column">

						<div id="message" class="info">
							<p><?php _e('Select people to invite from your friends list.', 'buddypress'); ?></p>
						</div>

						<?php do_action( 'bp_before_group_send_invites_list' ) ?>

						<?php /* The ID 'friend-list' is important for AJAX support. */ ?>
						<ul id="friend-list" class="item-list">
						<?php if ( bp_group_has_invites() ) : ?>

							<?php while ( bp_group_invites() ) : bp_group_the_invite(); ?>

								<li id="<?php bp_group_invite_item_id() ?>">
									<?php bp_group_invite_user_avatar() ?>

									<h4><?php bp_group_invite_user_link() ?></h4>
									<span class="activity"><?php bp_group_invite_user_last_active() ?></span>

									<?php do_action( 'bp_group_send_invites_item' ) ?>

									<div class="action">
										<a class="remove" href="<?php bp_group_invite_user_remove_invite_url() ?>" id="<?php bp_group_invite_item_id() ?>"><?php _e( 'Remove Invite', 'buddypress' ) ?></a>

										<?php do_action( 'bp_group_send_invites_item_action' ) ?>
									</div>
								</li>

							<?php endwhile; ?>

						<?php endif; ?>
						</ul>

						<?php do_action( 'bp_after_group_send_invites_list' ) ?>

					</div>

					<div class="clear"></div>

					<p class="clear"><input type="submit" name="submit" id="submit" value="<?php _e( 'Send Invites', 'buddypress' ) ?>" /></p>
					<?php wp_nonce_field( 'groups_send_invites', '_wpnonce_send_invites') ?>

					<!-- Don't leave out this hidden field -->
					<input type="hidden" name="group_id" id="group_id" value="<?php bp_group_id() ?>" />
				</form>

			<?php else : ?>

				<div id="message" class="info">
					<p><?php _e( 'You either need to build up your friends list, or your friends have already been invited or are current members.', 'buddypress' ); ?></p>
				</div>

			<?php endif; ?>

			<?php do_action( 'bp_before_group_send_invites_content' ) ?>

		<?php endwhile; endif; ?>

	</div>

<?php get_footer() ?>