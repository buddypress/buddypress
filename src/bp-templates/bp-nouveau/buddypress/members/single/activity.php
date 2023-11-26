<?php
/**
 * BuddyPress - Users Activity
 *
 * @since 3.0.0
 * @version 12.0.0
 */
?>

<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Activity menu', 'buddypress' ); ?>">
	<ul id="member-secondary-nav" class="subnav bp-priority-subnav-nav-items">

		<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

	</ul>

	<?php bp_nouveau_member_hook( '', 'secondary_nav' ); ?>
</nav><!-- .item-list-tabs#subnav -->

<h2 class="bp-screen-title<?php echo ( bp_displayed_user_has_front_template() ) ? ' bp-screen-reader-text' : ''; ?>">
	<?php esc_html_e( 'Member Activities', 'buddypress' ); ?>
</h2>

<?php bp_nouveau_activity_member_post_form(); ?>

<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

<?php bp_nouveau_member_hook( 'before', 'activity_content' ); ?>

<div id="activity-stream" class="activity single-user" data-bp-list="activity">

	<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'member-activity-loading' ); ?></div>

	<ul class="<?php bp_nouveau_loop_classes(); ?>"></ul>

</div><!-- .activity -->

<?php
bp_nouveau_member_hook( 'after', 'activity_content' );
