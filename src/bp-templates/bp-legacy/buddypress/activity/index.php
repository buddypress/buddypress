<?php
/**
 * BuddyPress Activity templates
 *
 * @since 2.3.0
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 12.0.0
 */

/**
 * Fires before the activity directory listing.
 *
 * @since 1.5.0
 */
do_action( 'bp_before_directory_activity' ); ?>

<div id="buddypress">

	<?php

	/**
	 * Fires before the activity directory display content.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_before_directory_activity_content' );
	?>

	<?php if ( is_user_logged_in() ) : ?>

		<?php bp_get_template_part( 'activity/post-form' ); ?>

	<?php endif; ?>

	<div id="template-notices" role="alert" aria-atomic="true">
		<?php

		/**
		 * Fires towards the top of template pages for notice display.
		 *
		 * @since 1.0.0
		 */
		do_action( 'template_notices' );
		?>

	</div>

	<div class="item-list-tabs activity-type-tabs" aria-label="<?php esc_attr_e( 'Sitewide activities navigation', 'buddypress' ); ?>" role="navigation">
		<ul>
			<?php

			/**
			 * Fires before the listing of activity type tabs.
			 *
			 * @since 1.2.0
			 */
			do_action( 'bp_before_activity_type_tab_all' );
			?>

			<li class="selected" id="activity-all">
				<a href="<?php bp_activity_directory_permalink(); ?>">
					<?php
					/* translators: %s: number of members */
					printf( esc_html__( 'All Members %s', 'buddypress' ), '<span>' . esc_html( bp_get_total_member_count() ) . '</span>' );
					?>
				</a>
			</li>

			<?php if ( is_user_logged_in() ) : ?>

				<?php

				/**
				 * Fires before the listing of friends activity type tab.
				 *
				 * @since 1.2.0
				 */
				do_action( 'bp_before_activity_type_tab_friends' );
				?>

				<?php if ( bp_is_active( 'friends' ) ) : ?>

					<?php if ( bp_get_total_friend_count( bp_loggedin_user_id() ) ) : ?>

						<li id="activity-friends">
							<a href="<?php bp_loggedin_user_link( array( bp_get_activity_slug(), bp_get_friends_slug() ) ); ?>">
								<?php
								/* translators: %s: number of friends */
								printf( esc_html__( 'My Friends %s', 'buddypress' ), '<span>' . esc_html( bp_get_total_friend_count( bp_loggedin_user_id() ) ) . '</span>' );
								?>
							</a>
						</li>

					<?php endif; ?>

				<?php endif; ?>

				<?php

				/**
				 * Fires before the listing of groups activity type tab.
				 *
				 * @since 1.2.0
				 */
				do_action( 'bp_before_activity_type_tab_groups' );
				?>

				<?php if ( bp_is_active( 'groups' ) ) : ?>

					<?php if ( bp_get_total_group_count_for_user( bp_loggedin_user_id() ) ) : ?>

						<li id="activity-groups">
							<a href="<?php bp_loggedin_user_link( array( bp_get_activity_slug(), bp_get_groups_slug() ) ); ?>">
								<?php
								/* translators: %s: current user groups count */
								printf( esc_html__( 'My Groups %s', 'buddypress' ), '<span>' . esc_html( bp_get_total_group_count_for_user( bp_loggedin_user_id() ) ) . '</span>' );
								?>
							</a>
						</li>

					<?php endif; ?>

				<?php endif; ?>

				<?php

				/**
				 * Fires before the listing of favorites activity type tab.
				 *
				 * @since 1.2.0
				 */
				do_action( 'bp_before_activity_type_tab_favorites' );
				?>

				<?php if ( bp_get_total_favorite_count_for_user( bp_loggedin_user_id() ) ) : ?>

					<li id="activity-favorites">
						<a href="<?php bp_loggedin_user_link( array( bp_get_activity_slug(), 'favorites' ) ); ?>">
							<?php
							/* translators: %s: number of favorites */
							printf( esc_html__( 'My Favorites %s', 'buddypress' ), '<span>' . esc_html( bp_get_total_favorite_count_for_user( bp_loggedin_user_id() ) ) . '</span>' );
							?>
						</a>
					</li>

				<?php endif; ?>

				<?php if ( bp_activity_do_mentions() ) : ?>

					<?php

					/**
					 * Fires before the listing of mentions activity type tab.
					 *
					 * @since 1.2.0
					 */
					do_action( 'bp_before_activity_type_tab_mentions' );
					?>

					<li id="activity-mentions">
						<a href="<?php bp_loggedin_user_link( array( bp_get_activity_slug(), 'mentions' ) ); ?>">
							<?php esc_html_e( 'Mentions', 'buddypress' ); ?>
							<?php if ( bp_get_total_mention_count_for_user( bp_loggedin_user_id() ) ) : ?>
								&nbsp;
								<strong>
									<span>
										<?php
										$new_mentions_count = bp_get_total_mention_count_for_user( bp_loggedin_user_id() );

										printf(
											esc_html(
												/* translators: %s: new mentions count */
												_nx(
													'%s new',
													'%s new',
													$new_mentions_count,
													'Number of new activity mentions',
													'buddypress'
												)
											),
											esc_html( $new_mentions_count )
										);
										?>
									</span>
								</strong>
							<?php endif; ?>
						</a>
					</li>

				<?php endif; ?>

			<?php endif; ?>

			<?php

			/**
			 * Fires after the listing of activity type tabs.
			 *
			 * @since 1.2.0
			 */
			do_action( 'bp_activity_type_tabs' );
			?>
		</ul>
	</div><!-- .item-list-tabs -->

	<div class="item-list-tabs no-ajax" id="subnav" aria-label="<?php esc_attr_e( 'Activity secondary navigation', 'buddypress' ); ?>" role="navigation">
		<ul>
			<?php if ( bp_activity_is_feed_enable( 'sitewide' ) ) : ?>
				<li class="feed">
					<a href="<?php bp_sitewide_activity_feed_link(); ?>" class="bp-tooltip" data-bp-tooltip="<?php esc_attr_e( 'RSS Feed', 'buddypress' ); ?>" aria-label="<?php esc_attr_e( 'RSS Feed', 'buddypress' ); ?>">
						<?php esc_html_e( 'RSS', 'buddypress' ); ?>
					</a>
				</li>
			<?php endif; ?>

			<?php

			/**
			 * Fires before the display of the activity syndication options.
			 *
			 * @since 1.2.0
			 */
			do_action( 'bp_activity_syndication_options' );
			?>

			<li id="activity-filter-select" class="last">
				<label for="activity-filter-by"><?php esc_html_e( 'Show:', 'buddypress' ); ?></label>
				<select id="activity-filter-by">
					<option value="-1"><?php esc_html_e( '&mdash; Everything &mdash;', 'buddypress' ); ?></option>

					<?php bp_activity_show_filters(); ?>

					<?php

					/**
					 * Fires inside the select input for activity filter by options.
					 *
					 * @since 1.2.0
					 */
					do_action( 'bp_activity_filter_options' );
					?>

				</select>
			</li>
		</ul>
	</div><!-- .item-list-tabs -->

	<?php

	/**
	 * Fires before the display of the activity list.
	 *
	 * @since 1.5.0
	 */
	do_action( 'bp_before_directory_activity_list' );
	?>

	<div class="activity" aria-live="polite" aria-atomic="true" aria-relevant="all">

		<?php bp_get_template_part( 'activity/activity-loop' ); ?>

	</div><!-- .activity -->

	<?php

	/**
	 * Fires after the display of the activity list.
	 *
	 * @since 1.5.0
	 */
	do_action( 'bp_after_directory_activity_list' );
	?>

	<?php

	/**
	 * Fires inside and displays the activity directory display content.
	 */
	do_action( 'bp_directory_activity_content' );
	?>

	<?php

	/**
	 * Fires after the activity directory display content.
	 *
	 * @since 1.2.0
	 */
	do_action( 'bp_after_directory_activity_content' );
	?>

	<?php

	/**
	 * Fires after the activity directory listing.
	 *
	 * @since 1.5.0
	 */
	do_action( 'bp_after_directory_activity' );
	?>

</div>
