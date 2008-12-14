<?php get_header() ?>

<div class="content-header">
	
</div>

<div id="content">	
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
	<div class="left-menu">
		<?php bp_group_avatar() ?>

		<?php bp_group_join_button() ?>
		
		<div class="info-group">
			<h4><?php _e( 'Admins', 'buddypress' ); ?></h4>
			<?php bp_group_list_admins() ?>
		</div>
		
		<?php if ( bp_group_has_moderators() ) : ?>
		<div class="info-group">
			<h4><?php _e( 'Mods', 'buddypress' ); ?></h4>
			<?php bp_group_list_mods() ?>
		</div>
		<?php endif; ?>
	</div>

	<div class="main-column">
		<div class="inner-tube">
			
			<div id="group-name">
				<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
				<p class="status"><?php bp_group_type() ?></p>
			</div>

			<div class="info-group">
				<h4><?php _e( 'Forum', 'buddypress' ); ?> <span><a href="#post-new" title="<?php _e( 'Post New', 'buddypress' ) ?>"><?php _e( 'Post New &raquo;', 'buddypress' ) ?></a></span></h4>
				
				<form action="<?php bp_forum_action() ?>" method="post" id="forum-topic-form">
					<?php if ( bp_has_topics() ) : ?>									
				
						<div id="post-count" class="pag-count">
							<?php bp_forum_pagination_count() ?>
						</div>
					
						<div class="pagination-links" id="topic-pag">
							<?php bp_forum_pagination() ?>
						</div>
					
						<ul id="forum-topic-list" class="item-list">
						<?php while ( bp_topics() ) : bp_the_topic(); ?>
							<li>
								<div class="avatar">
									<?php bp_the_topic_poster_avatar() ?>
								</div>
						
								<a href="<?php bp_the_topic_permalink() ?>" title="<?php bp_the_topic_title() ?> - <?php _e( 'Permalink', 'buddypress' ) ?>"><?php bp_the_topic_title() ?></a> 
								<span class="small">- <?php bp_the_topic_total_post_count() ?> </span>
								<p><span class="activity"><?php echo sprintf( __( 'updated %s ago', 'buddypress' ), bp_the_topic_time_since_last_post( false ) ) ?><span></p>
						
								<div class="latest-post">
									<?php _e( 'Latest by', 'buddypress' ) ?> <?php bp_the_topic_last_poster_name() ?>:
									<?php bp_the_topic_latest_post_excerpt() ?>
								</div>
							</li>
						<?php endwhile; ?>
						</ul>
					<?php else: ?>

						<div id="message" class="info">
							<p><?php _e( 'There are no topics for this group forum.', 'buddypress' ) ?></p>
						</div>

					<?php endif;?>
					
					<?php if ( bp_group_is_member() ) : ?>
				
						<div id="post-new-topic">
							<a name="post-new"></a>
							<p><strong><?php _e( 'Post a New Topic:', 'buddypress' ) ?></strong></p>
							<label><?php _e( 'Title:', 'buddypress' ) ?></label>
							<input type="text" name="topic_title" id="topic_title" value="" />
				
				
							<label><?php _e( 'Content:', 'buddypress' ) ?></label>
							<textarea name="topic_text" id="topic_text"></textarea>
					
							<label><?php _e( 'Tags:', 'buddypress' ) ?></label>
							<input type="text" name="topic_tags" id="topic_tags" value="" />
					
							<input type="submit" name="submit_topic" id="submit_topic" value="Post Topic"/>
						</div>
					
					<?php endif; ?>
				</form>
				
			</div>
		
		</div>
	</div>
	
	<?php endwhile; endif; ?>
</div>

<?php get_footer() ?>