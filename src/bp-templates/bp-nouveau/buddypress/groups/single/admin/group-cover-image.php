<?php
/**
 * BP Nouveau Group's cover image template.
 *
 * @since 3.0.0
 * @version 7.0.0
 */
?>

<?php if ( bp_is_group_create() ) : ?>

	<h3 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Upload Cover Image', 'buddypress' ); ?>
	</h3>

	<div id="header-cover-image"></div>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Change Cover Image', 'buddypress' ); ?>
	</h2>

<?php endif; ?>

<p><?php esc_html_e( 'The Cover Image will be used to customize the header of your group.', 'buddypress' ); ?></p>

<?php
bp_attachments_get_template_part( 'cover-images/index' );
