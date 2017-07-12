<?php
/**
 * BuddyPress - Groups Admin
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>
<div class="item-list-tabs no-ajax" id="subnav" aria-label="<?php esc_attr_e( 'Group secondary navigation', 'buddypress' ); ?>" role="navigation">
	<ul>
		<?php bp_group_admin_tabs(); ?>
	</ul>
</div><!-- .item-list-tabs -->

<?php
/**
 * Fires before the group admin form and content.
 *
 * @since 2.7.0
 */
do_action( 'bp_before_group_admin_form' ); ?>

<form action="<?php bp_group_admin_form_action(); ?>" name="group-settings-form" id="group-settings-form" class="standard-form" method="post" enctype="multipart/form-data">

	<?php
	/**
	 * Fires inside the group admin form and before the content.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_group_admin_content' ); ?>

	<?php /* Fetch the template for the current admin screen being viewed */ ?>

	<?php if ( bp_is_group_admin_screen( bp_action_variable() ) ) : ?>

		<?php bp_get_template_part( 'groups/single/admin/' . bp_action_variable() ); ?>

	<?php endif; ?>

	<?php

	/**
	 * Fires inside the group admin template.
	 *
	 * Allows plugins to add custom group edit screens.
	 *
	 * @since 1.1.0
	 */
	do_action( 'groups_custom_edit_steps' ); ?>

	<?php

	/**
	 * Fires inside the group admin form and after the content.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_after_group_admin_content' ); ?>

</form><!-- #group-settings-form -->

<?php
/**
 * Fires after the group admin form and content.
 *
 * @since 2.7.0
 */
do_action( 'bp_after_group_admin_form' );
