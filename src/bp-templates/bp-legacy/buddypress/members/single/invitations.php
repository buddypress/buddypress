<?php
/**
 * BuddyPress - membership invitations
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 8.0.0
 */

?>

<div class="item-list-tabs no-ajax" id="subnav" aria-label="<?php esc_attr_e( 'Member secondary navigation', 'buddypress' ); ?>" role="navigation">
	<ul>
		<?php bp_get_options_nav(); ?>
	</ul>
</div>

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

