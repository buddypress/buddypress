<div class="item-list-tabs no-ajax" id="subnav">
	<ul>
		<?php if ( bp_is_my_profile() ) : ?>
			<?php bp_get_options_nav() ?>
		<?php endif; ?>

		<li id="members-order-select" class="last filter">

			<?php _e( 'Order By:', 'buddypress' ) ?>
			<select id="members-all">
				<option value="active"><?php _e( 'Last Active', 'buddypress' ) ?></option>
				<option value="newest"><?php _e( 'Newest Registered', 'buddypress' ) ?></option>
				<option value="alphabetical"><?php _e( 'Alphabetical', 'buddypress' ) ?></option>

				<?php do_action( 'bp_member_blog_order_options' ) ?>
			</select>
		</li>
	</ul>
</div>

<?php if ( 'requests' == bp_current_action() ) : ?>
	<?php locate_template( array( 'members/single/friends/requests.php' ), true ) ?>

<?php else : ?>

	<?php do_action( 'bp_before_member_friends_content' ) ?>

	<div class="members friends">
		<?php // The loop will be loaded here via AJAX on page load to retain selected settings and not waste cycles. ?>
		<noscript><?php locate_template( array( 'members/members-loop.php' ), true ) ?></noscript>
	</div>

	<?php do_action( 'bp_after_member_friends_content' ) ?>

<?php endif; ?>
