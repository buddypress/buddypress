<?php if ( function_exists('bp_get_options_class') ) : ?>

	<div id="optionsbar"<?php bp_get_options_class() ?>>

<?php else: ?>
	
	<div id="optionsbar">

<?php endif; ?>	

		<h3><?php bp_get_options_title() ?></h3>

		<?php if ( bp_has_options_avatar() ) : ?>

			<p class="avatar">
				<?php bp_get_options_avatar() ?>
			</p>

		<?php endif; ?>

		<?php if ( function_exists('bp_has_icons') ) : ?>
			
			<ul id="options-nav"<?php bp_has_icons() ?>>
				
		<?php else: ?>
			
			<ul id="options-nav">
				
		<?php endif; ?>
		
				<?php bp_get_options_nav() ?>
			</ul>
		
		<?php do_action( 'bp_options_bar' ) ?>
		
		<div class="clear"></div>
		
	</div>