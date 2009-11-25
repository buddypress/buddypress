<?php do_action( 'bp_before_profile_menu' ) ?>

<?php bp_displayed_user_avatar( 'type=full' ) ?>

<div class="button-block">

	<?php if ( function_exists('bp_add_friend_button') ) : ?>

		<?php bp_add_friend_button() ?>

	<?php endif; ?>

	<?php if ( function_exists('bp_send_message_button') ) : ?>

		<?php bp_send_message_button() ?>

	<?php endif; ?>

	<?php do_action( 'bp_before_profile_menu_buttons' ) ?>

			<?php do_action( 'bp_before_profile_random_groups_loop' ) ?>

			<?php /* Random Groups Loop */ ?>
			<?php if ( function_exists( 'bp_has_groups' ) ) : ?>

				<?php do_action( 'bp_before_profile_groups_widget' ) ?>

				<?php if ( bp_has_groups( 'type=random&max=15' ) ) : ?>

					<div class="bp-widget">
						<h4><?php bp_word_or_name( __( "My Groups", 'buddypress' ), __( "%s's Groups", 'buddypress' ) ) ?> (<?php bp_group_total_for_member() ?>) <span><a href="<?php echo bp_displayed_user_domain() . BP_GROUPS_SLUG ?>">&rarr;</a></span></h4>

						<ul class="horiz-gallery">
						<?php while ( bp_groups() ) : bp_the_group(); ?>
							<li>
								<a href="<?php bp_group_permalink() ?>" title="<?php bp_group_name() ?>"><?php bp_group_avatar('width=30&height=30') ?></a>
							</li>
						<?php endwhile; ?>
						</ul>
					</div>

					<?php do_action( 'bp_after_profile_groups_widget' ) ?>

				<?php endif; ?>

			<?php endif; ?>

			<?php do_action( 'bp_after_profile_random_groups_loop' ) ?>
			<?php do_action( 'bp_before_profile_random_friends_loop' ) ?>

			<?php /* Random Friends Loop */ ?>
			<?php if ( function_exists( 'bp_has_friendships' ) ) : ?>

				<?php do_action( 'bp_before_profile_friends_widget' ) ?>

				<?php if ( bp_has_friendships( 'type=random&max=15' ) ) : ?>

					<div class="bp-widget">
						<h4><?php bp_word_or_name( __( "My Friends", 'buddypress' ), __( "%s's Friends", 'buddypress' ) ) ?> (<?php bp_friend_total_for_member() ?>) <span><a href="<?php echo bp_displayed_user_domain() . BP_FRIENDS_SLUG ?>">&rarr;</a></span></h4>

						<ul class="horiz-gallery">
						<?php while ( bp_user_friendships() ) : bp_the_friendship(); ?>
							<li>
								<a href="<?php bp_friend_url() ?>"><?php bp_friend_avatar_thumb('width=30&height=30') ?></a>
							</li>
						<?php endwhile; ?>
						</ul>
					</div>

				<?php endif; ?>

				<?php do_action( 'bp_after_profile_friends_widget' ) ?>

			<?php endif; ?>

			<?php do_action( 'bp_after_profile_random_friends_loop' ) ?>


</div>

<?php do_action( 'bp_after_profile_menu' ); /* Deprecated -> */ bp_custom_profile_sidebar_boxes(); ?>
