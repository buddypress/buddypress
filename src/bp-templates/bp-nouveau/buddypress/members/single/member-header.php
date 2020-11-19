<?php
/**
 * BuddyPress - Users Header
 *
 * @since 3.0.0
 * @version 7.0.0
 */
?>

<div id="item-header-avatar">
	<a href="<?php bp_displayed_user_link(); ?>">

		<?php bp_displayed_user_avatar( 'type=full' ); ?>

	</a>
</div><!-- #item-header-avatar -->

<div id="item-header-content">

	<?php if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) : ?>
		<h2 class="user-nicename">@<?php bp_displayed_user_mentionname(); ?></h2>
	<?php endif; ?>

	<?php bp_nouveau_member_hook( 'before', 'header_meta' ); ?>

	<?php if ( bp_nouveau_member_has_meta() ) : ?>
		<div class="item-meta">

			<?php bp_nouveau_member_meta(); ?>

		</div><!-- #item-meta -->
	<?php endif; ?>

	<?php
	bp_member_type_list(
		bp_displayed_user_id(),
		array(
			'label'        => array(
				'plural'   => __( 'Member Types', 'buddypress' ),
				'singular' => __( 'Member Type', 'buddypress' ),
			),
			'list_element' => 'span',
		)
	);
	?>

	<?php bp_nouveau_member_header_buttons( array( 'container_classes' => array( 'member-header-actions' ) ) ); ?>
</div><!-- #item-header-content -->
