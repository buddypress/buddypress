<?php
/**
 * BP Nouveau Group's cover image template.
 *
 * @since 1.0.0
 */
?>

<?php if ( bp_is_group_create() ) : ?>

	<h2 class="bp-screen-title <?php if ( bp_is_group_create() ) { echo esc_attr( 'creation-step-name' ); } ?>">
		<?php _e( 'Upload a Cover Image', 'buddypress' ); ?>
	</h2>

	<div id="header-cover-image"></div>

<?php else : ?>

	<h2 class="bp-screen-title"><?php _e( 'Change Cover Image', 'buddypress' ); ?></h2>

<?php endif; ?>

<p><?php _e( 'The Cover Image will be used to customize the header of your group.', 'buddypress' ); ?></p>

<?php bp_attachments_get_template_part( 'cover-images/index' );
