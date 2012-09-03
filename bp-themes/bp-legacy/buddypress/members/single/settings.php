<?php

/**
 * BuddyPress - Users Settings
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
	<ul>
		<?php if ( bp_is_my_profile() ) : ?>
		
			<?php bp_get_options_nav(); ?>
		
		<?php endif; ?>
	</ul>
</div>

<?php

if ( bp_is_current_action( 'notifications' ) ) :
	 bp_get_template_part( 'members/single/settings/notifications' );

elseif ( bp_is_current_action( 'delete-account' ) ) :
	 bp_get_template_part( 'members/single/settings/delete-account' );

elseif ( bp_is_current_action( 'general' ) ) :
	bp_get_template_part( 'members/single/settings/general' );

else :
	bp_get_template_part( 'members/single/plugins' );

endif;

?>
