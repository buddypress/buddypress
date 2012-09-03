<?php

/**
 * BuddyPress - Users Messages
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
	<ul>

		<?php bp_get_options_nav(); ?>

	</ul>
	
	<?php if ( bp_is_messages_inbox() || bp_is_messages_sentbox() ) : ?>

		<div class="message-search"><?php bp_message_search_form(); ?></div>

	<?php endif; ?>

</div><!-- .item-list-tabs -->

<?php

	if ( bp_is_current_action( 'compose' ) ) :
		bp_get_template_part( 'members/single/messages/compose' );

	elseif ( bp_is_current_action( 'view' ) ) :
		bp_get_template_part( 'members/single/messages/single' );

	else :
		do_action( 'bp_before_member_messages_content' ); ?>

	<div class="messages" role="main">

		<?php
			if ( bp_is_current_action( 'notices' ) )
				bp_get_template_part( 'members/single/messages/notices-loop' );
			else
				bp_get_template_part( 'members/single/messages/messages-loop' );
		?>

	</div><!-- .messages -->

	<?php do_action( 'bp_after_member_messages_content' ); ?>

<?php endif; ?>
