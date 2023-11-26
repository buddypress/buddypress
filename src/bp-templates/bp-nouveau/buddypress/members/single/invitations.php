<?php
/**
 * BuddyPress - Membership invitations
 *
 * @since 8.0.0
 * @version 12.0.0
 */
?>

<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Groups menu', 'buddypress' ); ?>">
	<ul id="member-secondary-nav" class="subnav bp-priority-subnav-nav-items">
		<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>
	</ul>

	<?php bp_nouveau_member_hook( '', 'secondary_nav' ); ?>
</nav><!-- .bp-navs -->

<?php
switch ( bp_current_action() ) :

	case 'send-invites' :
		bp_get_template_part( 'members/single/invitations/send-invites' );
		break;

	case 'list-invites' :
	default :
		bp_get_template_part( 'members/single/invitations/list-invites' );
		break;

endswitch;

