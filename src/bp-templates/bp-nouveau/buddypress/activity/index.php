<?php
/**
 * BuddyPress Activity templates
 *
 * @since 2.3.0
 */
?>

	<?php bp_nouveau_before_activity_directory_content(); ?>

	<?php if ( is_user_logged_in() ) : ?>

		<?php bp_get_template_part( 'activity/post-form' ); ?>

	<?php endif; ?>

	<?php bp_nouveau_template_notices(); ?>

	<?php if ( ! bp_nouveau_is_object_nav_in_sidebar() ) : ?>

		<?php bp_get_template_part( 'common/nav/directory-nav' ); ?>

	<?php endif; ?>

	<div class="screen-content">

		<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

		<?php bp_nouveau_activity_hook( 'before_directory', 'list' ); ?>

		<div class="activity">

			<ul id="activity-stream" class="activity-list item-list bp-list" data-bp-list="activity">

			 	<li id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-activity-loading' ); ?></li>

			</ul>

		</div><!-- .activity -->

		<?php bp_nouveau_after_activity_directory_content(); ?>
	</div><!-- // .screen-content -->

