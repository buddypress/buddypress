<div class="item-list-tabs no-ajax" id="subnav">
	<ul>
		<?php if ( bp_is_my_profile() ) : ?>
			<?php bp_get_options_nav() ?>
		<?php endif; ?>

		<?php if ( 'invites' != bp_current_action() ) : ?>
		<li id="members-order-select" class="last filter">

			<?php _e( 'Order By:', 'buddypress' ) ?>
			<select id="groups-all">
				<option value="active"><?php _e( 'Last Active', 'buddypress' ) ?></option>
				<option value="popular"><?php _e( 'Most Members', 'buddypress' ) ?></option>
				<option value="newest"><?php _e( 'Newly Created', 'buddypress' ) ?></option>
				<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ) ?></option>

				<?php do_action( 'bp_member_group_order_options' ) ?>
			</select>
		</li>
		<?php endif; ?>
	</ul>
</div>

<?php if ( 'invites' == bp_current_action() ) : ?>
	<?php locate_template( array( 'members/single/groups/invites.php' ), true ) ?>

<?php else : ?>

	<?php do_action( 'bp_before_member_groups_content' ) ?>

	<div class="groups mygroups">
		<?php // The loop will be loaded here via AJAX on page load to retain selected settings and not waste cycles. ?>
		<noscript><?php locate_template( array( 'groups/groups-loop.php' ), true ) ?></noscript>
	</div>

	<?php do_action( 'bp_after_member_groups_content' ) ?>

<?php endif; ?>
