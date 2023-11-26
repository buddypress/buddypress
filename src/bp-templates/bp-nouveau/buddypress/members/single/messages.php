<?php
/**
 * BuddyPress - Users Messages
 *
 * @since 3.0.0
 * @version 12.0.0
 */
?>

<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Messages menu', 'buddypress' ); ?>">
	<ul id="member-secondary-nav" class="subnav bp-priority-subnav-nav-items">

		<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

	</ul>

	<?php bp_nouveau_member_hook( '', 'secondary_nav' ); ?>
</nav><!-- .bp-navs -->

<?php
if ( ! in_array( bp_current_action(), array( 'inbox', 'sentbox', 'starred', 'view', 'compose', 'notices' ), true ) ) :

	bp_get_template_part( 'members/single/plugins' );

else :

	bp_nouveau_messages_member_interface();

endif;
?>
