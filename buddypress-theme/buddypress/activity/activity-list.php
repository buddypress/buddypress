<div class="info-group">
	<h4><?php bp_activities_title() ?></h4>
	
	<?php if ( bp_has_activities() ) : ?>

		<ul id="activity-list">
		<?php while ( bp_activities() ) : bp_the_activity(); ?>
			<li class="<?php bp_activity_css_class() ?>">
				<?php bp_activity_content() ?>
			</li>
		<?php endwhile; ?>
		</ul>

	<?php else: ?>

		<div id="message" class="info">
			<p><?php bp_you_or_name() ?> done anything recently.</p>
		</div>

	<?php endif;?>
</div>