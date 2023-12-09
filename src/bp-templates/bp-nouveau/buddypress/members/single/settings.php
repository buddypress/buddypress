<?php
/**
 * BuddyPress - Users Settings
 *
 * @since 3.0.0
 * @version 12.0.0
 */

?>

<?php if ( bp_core_can_edit_settings() ) : ?>

	<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Settings menu', 'buddypress' ); ?>">
		<ul id="member-secondary-nav" class="subnav bp-priority-subnav-nav-items">

			<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

		</ul>

		<?php bp_nouveau_member_hook( '', 'secondary_nav' ); ?>
	</nav>

<?php
endif;

switch ( bp_current_action() ) :
	case 'notifications':
		bp_get_template_part( 'members/single/settings/notifications' );
		break;
	case 'capabilities':
		bp_get_template_part( 'members/single/settings/capabilities' );
		break;
	case 'delete-account':
		bp_get_template_part( 'members/single/settings/delete-account' );
		break;
	case 'general':
		bp_get_template_part( 'members/single/settings/general' );
		break;
	case 'profile':
		bp_get_template_part( 'members/single/settings/profile' );
		break;
	case 'invites':
		bp_get_template_part( 'members/single/settings/group-invites' );
		break;
	case 'data':
		bp_get_template_part( 'members/single/settings/data' );
		break;
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
