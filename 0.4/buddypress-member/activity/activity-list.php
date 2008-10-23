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
			<p><?php bp_word_or_name( __( "You haven't done anything recently.", 'buddypress' ), __( "%s hasn't done anything recently.", 'buddypress' ) ) ?></p>
		</div>

	<?php endif;?>
</div>