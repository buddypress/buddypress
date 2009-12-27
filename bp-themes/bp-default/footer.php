		</div> <!-- #container -->

		<?php do_action( 'bp_after_container' ) ?>
		<?php do_action( 'bp_before_footer' ) ?>

		<div id="footer">
			<?php if ( bp_core_is_multiblog_install() ) : ?>
		    	<p><?php printf( __( '%s is proudly powered by <a href="http://mu.wordpress.org">WordPress MU</a> and <a href="http://buddypress.org">BuddyPress</a>', 'buddypress' ), bloginfo('name') ); ?></p>
			<?php else : ?>
		    	<p><?php printf( __( '%s is proudly powered by <a href="http://wordpress.org">WordPress</a> and <a href="http://buddypress.org">BuddyPress</a>', 'buddypress' ), bloginfo('name') ); ?></p>
			<?php endif; ?>

			<?php do_action( 'bp_footer' ) ?>
		</div>

		<?php do_action( 'bp_after_footer' ) ?>

		<?php wp_footer(); ?>

	</body>

</html>