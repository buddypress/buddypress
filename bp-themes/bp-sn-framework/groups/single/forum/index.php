<?php get_header() ?>

<div class="content-header">
	
</div>

<div id="content">	
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
	<div class="left-menu">
		<?php locate_template( array( '/groups/single/menu.php' ), true ) ?>
	</div>

	<div class="main-column">
		<div class="inner-tube">
			
			<div id="group-name">
				<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
				<p class="status"><?php bp_group_type() ?></p>
			</div>

			<div class="bp-widget">
				<h4><?php _e( 'Forum', 'buddypress' ); ?> <span><a href="#post-new" title="<?php _e( 'Post New', 'buddypress' ) ?>"><?php _e( 'Post New &rarr;', 'buddypress' ) ?></a></span></h4>
				
				<form action="<?php bp_forum_action() ?>" method="post" id="forum-topic-form" class="standard-form">
					<?php if ( bp_has_topics() ) : ?>									
						
						<div class="pagination">
						
							<div id="post-count" class="pag-count">
								<?php bp_forum_pagination_count() ?>
							</div>
					
							<div class="pagination-links" id="topic-pag">
								<?php bp_forum_pagination() ?>
							</div>
						
						</div>
						
						<ul id="forum-topic-list" class="item-list">
						<?php while ( bp_topics() ) : bp_the_topic(); ?>
							<li<?php if ( bp_get_the_topic_css_class() ) : ?> class="<?php bp_the_topic_css_class() ?>"<?php endif; ?>>

								<a class="topic-avatar" href="<?php bp_the_topic_permalink() ?>" title="<?php bp_the_topic_title() ?> - <?php _e( 'Permalink', 'buddypress' ) ?>"><?php bp_the_topic_last_poster_avatar( 'width=30&height=30') ?></a>
								<a class="topic-title" href="<?php bp_the_topic_permalink() ?>" title="<?php bp_the_topic_title() ?> - <?php _e( 'Permalink', 'buddypress' ) ?>"><?php bp_the_topic_title() ?></a> 
								<span class="small topic-meta">(<?php bp_the_topic_total_post_count() ?> &rarr; <?php bp_the_topic_time_since_last_post() ?> ago)</span>
								<span class="small latest topic-excerpt"><?php bp_the_topic_latest_post_excerpt() ?></span>
								
								<?php if ( bp_group_is_admin() || bp_group_is_mod() ) : ?>
									<div class="admin-links"><?php bp_the_topic_admin_links() ?></div>
								<?php endif; ?>
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

							<?php do_action( 'groups_forum_new_topic_before' ) ?>

							<a name="post-new"></a>
							<p><strong><?php _e( 'Post a New Topic:', 'buddypress' ) ?></strong></p>
							
							<label><?php _e( 'Title:', 'buddypress' ) ?></label>
							<input type="text" name="topic_title" id="topic_title" value="" />
				
							<label><?php _e( 'Content:', 'buddypress' ) ?></label>
							<textarea name="topic_text" id="topic_text"></textarea>
					
							<label><?php _e( 'Tags:', 'buddypress' ) ?></label>
							<input type="text" name="topic_tags" id="topic_tags" value="" />

							<?php do_action( 'groups_forum_new_topic_after' ) ?>
					
							<p class="submit"><input type="submit" name="submit_topic" id="submit" value="<?php _e( 'Post Topic', 'buddypress' ) ?>" /></p>
							
							<?php wp_nonce_field( 'bp_forums_new_topic' ) ?>
						</div>
					
					<?php endif; ?>
				</form>
				
			</div>
		
		</div>
	</div>
	
	<?php endwhile; endif; ?>
</div>

<?php get_footer() ?>