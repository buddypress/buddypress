<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
	<ul>

		<?php bp_get_options_nav(); ?>

	</ul>
</div><!-- .item-list-tabs -->

<?php

	if ( 'compose' == bp_current_action() ) :
		locate_template( array( 'members/single/messages/compose.php' ), true );

	elseif ( 'view' == bp_current_action() ) :
		locate_template( array( 'members/single/messages/single.php' ), true );

	else :
		do_action( 'bp_before_member_messages_content' ); ?>

	<div class="messages" role="main">

		<?php
			if ( 'notices' == bp_current_action() ) :
				locate_template( array( 'members/single/messages/notices-loop.php' ), true );

			else :
				locate_template( array( 'members/single/messages/messages-loop.php' ), true );

			endif;
		?>

	</div><!-- .messages -->

	<?php do_action( 'bp_after_member_messages_content' ); ?>

<?php endif; ?>
