<?php
/*
 * activity-list.php
 * The activity stream "Loop" in its own file.
 * 
 * Loaded by: 'activity/just-me.php'
 *			  'activity/my-friends.php'
 */
?>
<div class="info-group">
	
	<h4><?php bp_activities_title() ?></h4>
	
	<?php if ( bp_has_activities() ) : ?>
		
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
			<p><?php bp_activities_no_activity() ?></p>
		</div>

	<?php endif;?>
	
</div>