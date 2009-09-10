<?php get_header() ?>

	<div class="content-header">

	</div>
	
	<div id="content">
		
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

			<?php do_action( 'bp_before_group_content' ) ?>
	
			<div class="left-menu">
				<?php locate_template( array( 'groups/single/menu.php' ), true ) ?>
			</div>

			<div class="main-column">
				<div class="inner-tube">

					<?php do_action( 'bp_before_group_name' ) ?>
			
					<div id="group-name">
						<h1><a href="<?php bp_group_permalink() ?>" title="<?php bp_group_name() ?>"><?php bp_group_name() ?></a></h1>
						<p class="status"><?php bp_group_type() ?></p>
					</div>
					
					<?php do_action( 'bp_after_group_name' ) ?>
		
					<?php if ( !bp_group_is_visible() ) : ?>
						
						<?php do_action( 'bp_before_group_status_message' ) ?>
						
						<div id="message" class="info">
							<p><?php bp_group_status_message() ?></p>
						</div>
						
						<?php do_action( 'bp_after_group_status_message' ) ?>
						
					<?php endif; ?>
		
					<?php do_action( 'bp_before_group_description' ) ?>
				
					<div class="bp-widget">
						<h4><?php _e( 'Description', 'buddypress' ); ?></h4>
						<p><?php bp_group_description() ?></p>
					</div>
					
					<?php do_action( 'bp_after_group_description' ) ?>
		
					<?php if ( bp_group_is_visible() && bp_group_has_news() ) : ?>
						
						<?php do_action( 'bp_before_group_news' ) ?>
						
						<div class="bp-widget">
							<h4><?php _e( 'News', 'buddypress' ); ?></h4>
							<p><?php bp_group_news() ?></p>
						</div>
						
						<?php do_action( 'bp_after_group_news' ) ?>
						
					<?php endif; ?>
					
					<?php if ( bp_group_is_visible() ) : ?>
											
						<?php if ( bp_has_activities( 'object=groups&primary_id=' . bp_get_group_id() . '&max=150&per_page=5' ) ) : ?>

							<?php do_action( 'bp_before_group_activity' ) ?>

							<div class="bp-widget">
								<h4><?php _e( 'Group Activity', 'buddypress' ); ?></h4>
								
								<div class="pagination">
									<div class="pag-count" id="activity-count">
										<?php bp_activity_pagination_count() ?>
									</div>
		
									<div class="pagination-links" id="activity-pag">
										&nbsp; <?php bp_activity_pagination_links() ?>
									</div>
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

							</div>
							
							<?php do_action( 'bp_after_group_activity' ) ?>
							
						<?php endif; ?> 
						
					<?php endif; ?>
			
					<?php if ( bp_group_is_visible() && bp_group_is_forum_enabled() && function_exists( 'bp_forums_setup') ) : ?>
						
						<?php do_action( 'bp_before_group_active_topics' ) ?>
						
						<div class="bp-widget">
							<h4><?php _e( 'Recently Active Topics', 'buddypress' ); ?> <span><a href="<?php bp_group_forum_permalink() ?>"><?php _e( 'See All', 'buddypress' ) ?> &rarr;</a></span></h4>
				
							<?php if ( bp_has_topics( 'no_stickies=true&max=5&per_page=5' ) ) : ?>
																	
								<ul id="forum-topic-list" class="item-list">
									<?php while ( bp_topics() ) : bp_the_topic(); ?>
									
										<li>
											<a class="topic-avatar" href="<?php bp_the_topic_permalink() ?>" title="<?php bp_the_topic_title() ?> - <?php _e( 'Permalink', 'buddypress' ) ?>"><?php bp_the_topic_last_poster_avatar( 'width=30&height=30') ?></a>
											<a class="topic-title" href="<?php bp_the_topic_permalink() ?>" title="<?php bp_the_topic_title() ?> - <?php _e( 'Permalink', 'buddypress' ) ?>"><?php bp_the_topic_title() ?></a> 
											<span class="small topic-meta">(<?php bp_the_topic_total_post_count() ?> &rarr; <?php bp_the_topic_time_since_last_post() ?> ago)</span>
											<span class="small latest topic-excerpt"><?php bp_the_topic_latest_post_excerpt() ?></span>
										
											<?php do_action( 'bp_group_active_topics_item' ) ?>
										</li>
									
									<?php endwhile; ?>
								</ul>
								
							<?php else: ?>

								<div id="message" class="info">
									<p><?php _e( 'There are no active forum topics for this group', 'buddypress' ) ?></p>
								</div>

							<?php endif;?>
					
						</div>
						
						<?php do_action( 'bp_after_group_active_topics' ) ?>
						
					<?php endif; ?>
		
					<?php if ( bp_group_is_visible() ) : ?>
						
						<?php do_action( 'bp_before_group_member_widget' ) ?>
						
						<div class="bp-widget">
							<h4><?php printf( __( 'Members (%d)', 'buddypress' ), bp_get_group_total_members() ); ?> <span><a href="<?php bp_group_all_members_permalink() ?>"><?php _e( 'See All', 'buddypress' ) ?> &rarr;</a></h4>

							<?php if ( bp_group_has_members( 'max=5&exclude_admins_mods=0' ) ) : ?>
						
								<ul class="horiz-gallery">
									<?php while ( bp_group_members() ) : bp_group_the_member(); ?>
									
										<li>
											<a href="<?php bp_group_member_url() ?>"><?php bp_group_member_avatar_thumb() ?></a>
											<h5><?php bp_group_member_link() ?></h5>
										</li>
									<?php endwhile; ?>
								</ul>
								
							<?php endif; ?>
							
						</div>
						
						<?php do_action( 'bp_after_group_member_widget' ) ?>
						
					<?php endif; ?>
			
					<?php do_action( 'groups_custom_group_boxes' ) ?>
		
					<?php if ( bp_group_is_visible() && bp_group_is_wire_enabled() ) : ?>
						
						<?php if ( function_exists('bp_wire_get_post_list') ) : ?>
							
							<?php do_action( 'bp_before_group_wire_widget' ) ?>
							
							<?php bp_wire_get_post_list( bp_get_group_id(), __( 'Group Wire', 'buddypress' ), sprintf( __( 'There are no wire posts for %s', 'buddypress' ), bp_get_group_name() ), bp_group_is_member(), true ) ?>
						
							<?php do_action( 'bp_after_group_wire_widget' ) ?>
							
						<?php endif; ?>
					
					<?php endif; ?>
		
				</div>
				
			</div>

			<?php do_action( 'bp_after_group_content' ) ?>

		<?php endwhile; else: ?>
			
			<div id="message" class="error">
				<p><?php _e("Sorry, the group does not exist.", "buddypress"); ?></p>
			</div>
			
		<?php endif;?>

	</div>

<?php get_footer() ?>