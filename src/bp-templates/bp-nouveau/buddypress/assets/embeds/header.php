<?php
/**
 * @version 3.0.0
 */
?>

		<div id="bp-embed-header">
			<div class="bp-embed-avatar">
				<a href="<?php bp_displayed_user_link(); ?>">
					<?php bp_displayed_user_avatar( 'type=thumb&width=36&height=36' ); ?>
				</a>
			</div>

			<p class="wp-embed-heading">
				<a href="<?php bp_displayed_user_link(); ?>">
					<?php bp_displayed_user_fullname(); ?>
				</a>
			</p>

			<?php if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) : ?>
				<p class="bp-embed-mentionname">@<?php bp_displayed_user_mentionname(); ?></p>
			<?php endif; ?>
		</div>
