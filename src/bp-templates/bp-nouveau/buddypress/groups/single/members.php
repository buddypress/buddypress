<?php
/**
 * BuddyPress - Groups Members
 *
 * @since 3.0.0
 * @version 3.0.0
 */
?>


<div class="subnav-filters filters clearfix no-subnav">

	<?php bp_nouveau_search_form(); ?>

	<?php bp_get_template_part( 'common/filters/groups-screens-filters' ); ?>

</div>

<h2 class="bp-screen-title">
	<?php esc_html_e( 'Membership List', 'buddypress' ); ?>
</h2>


<div id="members-group-list" class="group_members dir-list" data-bp-list="group_members">

	<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'group-members-loading' ); ?></div>

</div><!-- .group_members.dir-list -->
