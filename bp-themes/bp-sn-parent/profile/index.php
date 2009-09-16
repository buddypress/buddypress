<?php get_header() ?>

<div class="content-header">
	<?php bp_last_activity() ?>
</div>

<div id="content" class="vcard">

	<?php do_action( 'template_notices' ) // (error/success feedback) ?>

	<?php do_action( 'bp_before_profile_content' ) ?>
	
	<div class="left-menu">
		<!-- Profile Menu (Avatar, Add Friend, Send Message buttons etc) -->
		<?php locate_template( array( 'profile/profile-menu.php' ), true ) ?>
	</div>

	<div class="main-column">
		<div class="inner-tube">
						
			<?php /* Profile Header (Name & Status) */ ?>
			<?php locate_template( array( 'profile/profile-header.php' ), true ) ?>
		
			<?php /* Profile Data Loop */ ?>
			<?php locate_template( array( 'profile/profile-loop.php' ), true ) ?>

			<?php do_action( 'bp_before_profile_activity_loop' ) ?>
			
			<?php /* Latest Activity Loop */ ?>
			<?php if ( function_exists( 'bp_activity_install')) : ?>
				
				<?php do_action( 'bp_before_profile_activity_widget' ) ?>

				<div class="bp-widget">
					<h4><?php echo bp_word_or_name( __( "My Latest Activity", 'buddypress' ), __( "%s's Latest Activity", 'buddypress' ), true, false ) ?> <span><a href="<?php echo bp_displayed_user_domain() . BP_ACTIVITY_SLUG ?>"><?php _e( 'See All', 'buddypress' ) ?> &rarr;</a></span></h4>

					<?php if ( bp_has_activities( 'type=personal&max=5' ) ) : ?>

						<div id="activity-rss">
							<p><a href="<?php bp_activities_member_rss_link() ?>" title="<?php _e( 'RSS Feed', 'buddypress' ) ?>"><?php _e( 'RSS Feed', 'buddypress' ) ?></a></p>
						</div>

						<ul id="activity-list" class="activity-list item-list">
						<?php while ( bp_activities() ) : bp_the_activity(); ?>
							<li class="<?php bp_activity_css_class() ?>">
								<div class="activity-avatar">
									<?php bp_activity_avatar() ?>
								</div>
							
								<?php bp_activity_content() ?>
							</li>
						<?php endwhile; ?>
						</ul>

					<?php else: ?>

						<div id="message" class="info">
							<p><?php echo bp_word_or_name( __( "You haven't done anything recently.", 'buddypress' ), __( "%s hasn't done anything recently.", 'buddypress' ), true, false ) ?></p>
						</div>

					<?php endif;?>
				</div>
	
				<?php do_action( 'bp_after_profile_activity_widget' ) ?>
			
			<?php endif; ?>

			<?php do_action( 'bp_after_profile_activity_loop' ) ?>
			<?php do_action( 'bp_before_profile_random_groups_loop' ) ?>
		
			<?php /* Random Groups Loop */ ?>
			<?php if ( function_exists( 'bp_has_groups' ) ) : ?>

				<?php do_action( 'bp_before_profile_groups_widget' ) ?>

				<?php if ( bp_has_groups( 'type=random&max=5' ) ) : ?>
					
					<div class="bp-widget">
						<h4><?php bp_word_or_name( __( "My Groups", 'buddypress' ), __( "%s's Groups", 'buddypress' ) ) ?> (<?php bp_group_total_for_member() ?>) <span><a href="<?php echo bp_displayed_user_domain() . BP_GROUPS_SLUG ?>"><?php _e( 'See All', 'buddypress' ) ?> &rarr;</a></span></h4>
						
						<ul class="horiz-gallery">
						<?php while ( bp_groups() ) : bp_the_group(); ?>
							<li>
								<a href="<?php bp_group_permalink() ?>"><?php bp_group_avatar_thumb() ?></a>
								<h5><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h5>
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

				<?php if ( bp_has_friendships( 'type=random&max=5' ) ) : ?>
					
					<div class="bp-widget">
						<h4><?php bp_word_or_name( __( "My Friends", 'buddypress' ), __( "%s's Friends", 'buddypress' ) ) ?> (<?php bp_friend_total_for_member() ?>) <span><a href="<?php echo bp_displayed_user_domain() . BP_FRIENDS_SLUG ?>"><?php _e( 'See All', 'buddypress' ) ?> &rarr;</a></span></h4>
						
						<ul class="horiz-gallery">
						<?php while ( bp_user_friendships() ) : bp_the_friendship(); ?>
							<li>
								<a href="<?php bp_friend_url() ?>"><?php bp_friend_avatar_thumb() ?></a>
								<h5><a href="<?php bp_friend_url() ?>"><?php bp_friend_name() ?></a></h5>
							</li>
						<?php endwhile; ?>
						</ul>	
					</div>
					
				<?php endif; ?>
		
				<?php do_action( 'bp_after_profile_friends_widget' ) ?>
			
			<?php endif; ?>
	
			<?php do_action( 'bp_after_profile_random_friends_loop' ) ?>
			<?php do_action( 'bp_before_profile_wire_loop' ); /* Deprecated -> */ do_action( 'bp_custom_profile_boxes' ) ?>

			<?php /* Profile Wire Loop - uses [TEMPLATEPATH]/wire/post-list.php */ ?>
			<?php if ( function_exists('bp_wire_get_post_list') && function_exists( 'xprofile_install' ) ) : ?>

				<?php do_action( 'bp_before_profile_wire_widget' ) ?>

				<?php bp_wire_get_post_list( bp_current_user_id(), bp_word_or_name( __( "My Wire", 'buddypress' ), __( "%s's Wire", 'buddypress' ), true, false ), bp_word_or_name( __( "No one has posted to your wire yet.", 'buddypress' ), __( "No one has posted to %s's wire yet.", 'buddypress' ), true, false ), bp_profile_wire_can_post() ) ?>

				<?php do_action( 'bp_after_profile_wire_widget' ) ?>

			<?php endif; ?>

			<?php do_action( 'bp_after_profile_wire_loop' ) ?>
			
		</div>

	<?php do_action( 'bp_after_profile_content' ) ?>

	</div>

</div>

<?php get_footer() ?>