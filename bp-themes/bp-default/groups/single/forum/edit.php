<?php get_header() ?>

<div class="content-header">
	
</div>

<div id="content">
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
	<div class="left-menu">
		<?php locate_template( array( 'groups/single/menu.php' ), true ) ?>
	</div>

	<div class="main-column">
		<div class="inner-tube">
			
			<div id="group-name">
				<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
				<p class="status"><?php bp_group_type() ?></p>
			</div>

			<div class="bp-widget">
				<?php if ( bp_has_topic_posts() ) : ?>
				<form action="<?php bp_forum_topic_action() ?>" method="post" id="forum-topic-form">
			
					<h4><?php _e( 'Forum', 'buddypress' ); ?></h4>
				
					<ul id="topic-post-list" class="item-list">
						<li id="topic-meta">
							<a href="<?php bp_forum_permalink() ?>"><?php _e( 'Forum', 'buddypress') ?></a> &raquo; 
							<strong><?php bp_the_topic_title() ?> (<?php bp_the_topic_total_post_count() ?>)</strong>
						</li>
					</ul>
					
					<?php if ( bp_group_is_member() ) : ?>
						
						<?php if ( bp_is_edit_topic() ) : ?>
							
							<div id="edit-topic">

								<?php do_action( 'groups_forum_edit_topic_before' ) ?>
							
								<p><strong><?php _e( 'Edit Topic:', 'buddypress' ) ?></strong></p>
							
								<label for="topic_title"><?php _e( 'Title:', 'buddypress' ) ?></label>
								<input type="text" name="topic_title" id="topic_title" value="<?php bp_the_topic_title() ?>" />
				
								<label for="topic_text"><?php _e( 'Content:', 'buddypress' ) ?></label>
								<textarea name="topic_text" id="topic_text"><?php bp_the_topic_text() ?></textarea>
					
								<?php do_action( 'groups_forum_edit_topic_after' ) ?>
					
								<p class="submit"><input type="submit" name="save_changes" id="save_changes" value="<?php _e( 'Save Changes', 'buddypress' ) ?>" /></p>
							
								<?php wp_nonce_field( 'bp_forums_edit_topic' ) ?>
							
							</div>
							
						<?php else : ?>
							
							<div id="edit-post">

								<?php do_action( 'groups_forum_edit_post_before' ) ?>
							
								<p><strong><?php _e( 'Edit Post:', 'buddypress' ) ?></strong></p>

								<textarea name="post_text" id="post_text"><?php bp_the_topic_post_edit_text() ?></textarea>
		
								<?php do_action( 'groups_forum_edit_post_after' ) ?>
					
								<p class="submit"><input type="submit" name="save_changes" id="save_changes" value="<?php _e( 'Save Changes', 'buddypress' ) ?>" /></p>
							
								<?php wp_nonce_field( 'bp_forums_edit_post' ) ?>
							
							</div>
							
						<?php endif; ?>
					
					<?php endif; ?>
					
				</form>	
				<?php else: ?>

					<div id="message" class="info">
						<p><?php _e( 'This topic does not exist.', 'buddypress' ) ?></p>
					</div>

				<?php endif;?>

			</div>
			
		</div>
	</div>

	<?php endwhile; endif; ?>
</div>

<?php get_footer() ?>
