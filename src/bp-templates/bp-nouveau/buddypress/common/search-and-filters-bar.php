<?php
/**
 * BP Nouveau Search & filters bar
 *
 * @since 3.0.0
 * @version 8.0.0
 */
?>
<div class="subnav-filters filters no-ajax" id="subnav-filters">

	<?php if ( bp_get_friends_slug() !== bp_current_component() ) : ?>
		<div class="subnav-search clearfix">

			<?php if ( bp_nouveau_is_feed_enable() ) : ?>
				<div id="activity-rss-feed" class="feed">
					<a href="<?php bp_nouveau_activity_rss_link(); ?>" class="bp-tooltip" data-bp-tooltip="<?php bp_nouveau_activity_rss_tooltip(); ?>">
						<span class="bp-screen-reader-text"><?php bp_nouveau_activity_rss_screen_reader_text(); ?></span>
					</a>
				</div>
			<?php endif; ?>

			<?php bp_nouveau_search_form(); ?>

 		</div>
	<?php endif; ?>

	<?php if ( bp_is_user() && ! bp_is_current_action( 'requests' ) ) : ?>
		<?php bp_get_template_part( 'common/filters/user-screens-filters' ); ?>
	<?php elseif ( bp_get_groups_slug() === bp_current_component() ) : ?>
		<?php bp_get_template_part( 'common/filters/groups-screens-filters' ); ?>
	<?php else : ?>
		<?php bp_get_template_part( 'common/filters/directory-filters' ); ?>
	<?php endif; ?>

</div><!-- search & filters -->
