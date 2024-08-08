<?php
/**
 * BuddyPress - Member’s Notices
 *
 * @since 15.0.0
 * @version 15.0.0
 */
?>

<?php if ( bp_is_current_component( 'notices' ) ) : ?>
	<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Community Notices menu', 'buddypress' ); ?>">
		<ul id="member-secondary-nav" class="subnav bp-priority-subnav-nav-items">

			<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

		</ul>

		<?php bp_nouveau_member_hook( '', 'secondary_nav' ); ?>
	</nav><!-- .item-list-tabs -->
<?php endif; ?>

<div class="notices">

	<?php bp_output_notices(); ?>

</div><!-- .notices -->
