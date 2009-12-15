<div class="item-list-tabs no-ajax" id="user-subnav">
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

				<?php do_action( 'bp_members_directory_order_options' ) ?>
			</select>
		</li>
	</ul>
</div>

<?php if ( 'requests' == bp_current_action() ) : ?>
	<?php locate_template( array( 'members/single/friends/requests.php' ), true ) ?>

<?php else : ?>

	<div class="members friends">
		<?php // 'members/members-loop.php' loaded here via AJAX. ?>
	</div>

<?php endif; ?>

<?php do_action( 'bp_directory_members_content' ) ?>
