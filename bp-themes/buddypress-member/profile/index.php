<?php get_header() ?>

<div class="content-header">
	<?php bp_last_activity() ?>
</div>

<div id="content" class="vcard">
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<div class="left-menu">
		<?php bp_the_avatar() ?>
		
		<div class="button-block">
			<?php if ( function_exists('bp_add_friend_button') ) : ?>
				<?php bp_add_friend_button() ?>
			<?php endif; ?>
			
			<?php if ( function_exists('bp_send_message_button') ) : ?>
				<?php bp_send_message_button() ?>
			<?php endif; ?>
		</div>

		<?php bp_custom_profile_sidebar_boxes() ?>
	</div>

	<div class="main-column">
		<div class="inner-tube">
			<?php bp_get_profile_header() ?>
		
			<?php if ( function_exists('xprofile_get_profile') ) : ?>
				<?php xprofile_get_profile() ?>
			<?php else : ?>
				<?php bp_core_get_wp_profile() ?>
			<?php endif; ?>
			
			<?php if ( function_exists( 'bp_activity_install')) : ?>
			<div class="info-group">
				<h4><?php echo bp_word_or_name( __( "My Activity", 'buddypress' ), __( "%s's Activity", 'buddypress' ), true, false ) ?></h4>

				<?php if ( bp_has_activities( 'type=personal&max=5' ) ) : ?>

					<div id="activity-rss">
						<p><a href="<?php bp_activities_member_rss_link() ?>" title="<?php _e( 'RSS Feed', 'buddypress' ) ?>"><?php _e( 'RSS Feed', 'buddypress' ) ?></a></p>
					</div>

					<ul id="activity-list">
					<?php while ( bp_activities() ) : bp_the_activity(); ?>
						<li class="<?php bp_activity_css_class() ?>">
							<?php bp_activity_content() ?>
						</li>
					<?php endwhile; ?>
					</ul>

				<?php else: ?>

					<div id="message" class="info">
						<p><?php echo bp_word_or_name( __( "You haven't done anything yet.", 'buddypress' ), __( "%s hasn't done anything yet.", 'buddypress' ), true, false ) ?></p>
					</div>

				<?php endif;?>
			</div>
			<?php endif; ?>
		
			<?php if ( function_exists('bp_groups_random_groups') ) : ?>
				<?php bp_groups_random_groups() ?>
			<?php endif; ?>
		
			<?php if ( function_exists('bp_friends_random_friends') ) : ?>
				<?php bp_friends_random_friends() ?>
			<?php endif; ?>

			<?php bp_custom_profile_boxes() ?>

			<?php if ( function_exists('bp_wire_get_post_list') ) : ?>
				<?php bp_wire_get_post_list( bp_current_user_id(), bp_word_or_name( __( "My Wire", 'buddypress' ), __( "%s's Wire", 'buddypress' ), true, false ), bp_word_or_name( __( "No one has posted to your wire yet.", 'buddypress' ), __( "No one has posted to %s's wire yet.", 'buddypress' ), true, false), bp_profile_wire_can_post() ) ?>
			<?php endif; ?>
		</div>
	</div>

</div>

<?php get_footer() ?>