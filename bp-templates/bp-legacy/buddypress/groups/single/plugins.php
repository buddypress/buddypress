<div id="buddypress">

	<?php do_action( 'bp_before_group_plugin_template' ); ?>

	<div id="item-header">

		<?php bp_get_template_part( 'groups/single/group-header' ); ?>

	</div><!-- #item-header -->

	<div id="item-nav">
		<div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
			<ul>

				<?php bp_get_options_nav(); ?>

				<?php do_action( 'bp_group_plugin_options_nav' ); ?>

			</ul>
		</div>
	</div><!-- #item-nav -->

	<div id="item-body">

		<?php do_action( 'bp_before_group_body' ); ?>

		<?php do_action( 'bp_template_content' ); ?>

		<?php do_action( 'bp_after_group_body' ); ?>

	</div><!-- #item-body -->

	<?php do_action( 'bp_after_group_plugin_template' ); ?>

</div><!-- #buddypress -->
