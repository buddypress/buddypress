<?php

$name = bp_user_fullname( $bp['current_userid'], false );


?>

<h2><?php echo $name ?>'s Dashboard</h2>

<div id="rightnow">
	<h3 class="reallynow" style="background: #2093D6">
		<span>Latest Activity</span>
		<br class="clear"/>
	</h3>
	<p class="youhave">
		Activity feed will be rendered here in a coming version of BuddyPress.
	</p>
</div>

<div id="dashboard_primary" class="dashboard-widget-holder widget_rss wp_dashboard_empty">
	<div class="dashboard-widget">
		<h3 class="dashboard-widget-title">
			<span>Latest Blog Posts</span>
			<br class="clear"/>
		</h3>
	
		<div class="dashboard-widget-content">
			<?php if ( bp_has_posts() ) : ?>
				<?php while ( bp_posts() ) : bp_the_post(); ?>
					<div class="post" id="post-<?php bp_post_id(); ?>">
						<h3><a href="<?php bp_post_permalink() ?>" rel="bookmark" title="Permanent Link to <?php bp_post_title(); ?>"><?php bp_post_title(); ?></a></h3>
						<p class="date"><?php bp_post_date('F jS, Y') ?> <em>in <?php bp_post_category(', ') ?> by <?php bp_post_author() ?></em></p>
						<?php bp_post_content('Read the rest of this entry &raquo;'); ?>
						<p class="postmetadata"><?php bp_post_tags('<span class="tags">', ', ', '</span>'); ?>  <span class="comments"><?php bp_post_comments('No Comments', '1 Comment', '% Comments'); ?></span></p>
					</div>
					<?php endwhile; ?>
			<?php else: ?>

				<div id="message" class="info">
					<p><?php bp_you_or_name() ?> <?php _e('made any posts yet!'); ?></p>
				</div>

			<?php endif;?>
		</div>
	</div>
</div>

<div id="dashboard_primary" class="dashboard-widget-holder widget_rss wp_dashboard_empty">
	<div class="dashboard-widget">
		<h3 class="dashboard-widget-title">
			<span>Messages &raquo; Inbox</span>
			<br class="clear"/>
		</h3>
	
		<div class="dashboard-widget-content">
			<?php if ( bp_has_message_threads() ) : ?>

				<table id="message-threads">
				<?php while ( bp_message_threads() ) : bp_message_thread(); ?>
					<tr id="m-<?php bp_message_thread_id() ?>"<?php if ( bp_message_thread_has_unread() ) : ?> class="unread"<?php else: ?> class="read"<?php endif; ?>>
						<td width="1%">
							<span class="unread-count"><?php bp_message_thread_unread_count() ?></span>
						</td>
						<td width="1%"><?php bp_message_thread_avatar() ?></td>
						<td width="100%">
							<p><a href="<?php bp_message_thread_view_link() ?>" title="View Message"><?php bp_message_thread_subject() ?></a></p>
							<p>From: <?php bp_message_thread_from() ?></p>
						</td>
					</tr>
				<?php endwhile; ?>
				</table>

			<?php else: ?>

				<div id="message" class="info">
					<p>You have no messages in your inbox.</p>
				</div>	

			<?php endif;?>
		</div>
	</div>
</div>

</div>
</div>
</div>


