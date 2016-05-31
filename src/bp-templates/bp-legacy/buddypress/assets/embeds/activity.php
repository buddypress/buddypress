
		<?php if ( bp_activity_embed_has_activity( bp_current_action() ) ) : ?>

			<?php while ( bp_activities() ) : bp_the_activity(); ?>
				<div class="bp-embed-excerpt"><?php bp_activity_embed_excerpt(); ?></div>
			<?php endwhile; ?>

		<?php endif; ?>

