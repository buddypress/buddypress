<?php
/**
 * BuddyPress Admin URLs/Rewrites Functions.
 *
 * @package BuddyPress
 * @subpackage Admin
 * @since 12.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles BP Rewrites settings updates & load styles and scripts.
 *
 * @since 12.0.0
 */
function bp_core_admin_rewrites_load() {
	wp_enqueue_style( 'site-health' );
	wp_add_inline_style(
		'site-health',
		'#bp-admin-rewrites-form .form-table { border: none; padding: 0; }
		#bp-admin-rewrites-form .bp-nav-slug { margin-left: 2em; display: inline-block; vertical-align: middle; }
		.site-health-issues-wrapper:first-of-type { margin-top: 0; }
		.site-health-issues-wrapper .health-check-accordion { border-bottom: none; }
		.site-health-issues-wrapper .health-check-accordion:last-of-type { border-bottom: 1px solid #c3c4c7; }'
	);

	// Handle slugs customization.
	if ( isset( $_POST['bp-admin-rewrites-submit'] ) ) {
		check_admin_referer( 'bp-admin-rewrites-setup' );

		$base_url = bp_get_admin_url( add_query_arg( 'page', 'bp-rewrites', 'admin.php' ) );

		if ( ! isset( $_POST['components'] ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'true', $base_url ) );
		}

		$switched_to_root_blog = false;

		// Make sure the current blog is set to the root blog.
		if ( ! bp_is_root_blog() ) {
			switch_to_blog( bp_get_root_blog_id() );
			$switched_to_root_blog = true;
		}

		$directory_pages     = (array) bp_core_get_directory_pages();
		$current_page_slugs  = wp_list_pluck( $directory_pages, 'slug', 'id' );
		$current_page_titles = wp_list_pluck( $directory_pages, 'title', 'id' );
		$reset_rewrites      = false;

		// Data is sanitized inside the foreach loop.
		$components = wp_unslash( $_POST['components'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		foreach ( $components as $page_id => $posted_data ) {
			$postarr = array();

			if ( ! isset( $current_page_slugs[ $page_id ] ) ) {
				continue;
			}

			$postarr['ID'] = $page_id;

			if ( isset( $posted_data['post_title'] ) ) {
				$post_title = sanitize_text_field( $posted_data['post_title'] );

				if ( $current_page_titles[ $page_id ] !== $post_title ) {
					$postarr['post_title'] = $post_title;
				}
			}

			if ( isset( $posted_data['post_name'] ) ) {
				$post_name = sanitize_text_field( $posted_data['post_name'] );

				if ( $current_page_slugs[ $page_id ] !== $post_name ) {
					$reset_rewrites       = true;
					$postarr['post_name'] = $post_name;
				}
			}

			if ( isset( $posted_data['_bp_component_slugs'] ) && is_array( $posted_data['_bp_component_slugs'] ) ) {
				$postarr['meta_input']['_bp_component_slugs'] = array_map( 'sanitize_title', $posted_data['_bp_component_slugs'] );
			}

			if ( isset( $posted_data['_bp_component_slugs']['bp_group_create'] ) ) {
				$new_current_group_create_slug    = sanitize_text_field( $posted_data['_bp_component_slugs']['bp_group_create'] );
				$current_group_create_custom_slug = '';

				if ( isset( $directory_pages->groups->custom_slugs['bp_group_create'] ) ) {
					$current_group_create_custom_slug = $directory_pages->groups->custom_slugs['bp_group_create'];
				}

				if ( $new_current_group_create_slug !== $current_group_create_custom_slug ) {
					$reset_rewrites = true;
				}
			}

			wp_update_post( $postarr );
		}

		// Make sure the WP rewrites will be regenarated at next page load.
		if ( $reset_rewrites ) {
			bp_delete_rewrite_rules();
		}

		if ( $switched_to_root_blog ) {
			restore_current_blog();
		}

		wp_safe_redirect( add_query_arg( 'updated', 'true', $base_url ) );
	}
}

/**
 * Outputs BP Rewrites URLs settings.
 *
 * @since 12.0.0
 */
function bp_core_admin_rewrites_settings() {
	$bp              = buddypress();
	$bp_pages        = $bp->pages;
	$reordered_pages = array();

	wp_enqueue_script( 'bp-rewrites-ui' );

	if ( isset( $bp_pages->register ) ) {
		$reordered_pages['register'] = $bp_pages->register;
		unset( $bp_pages->register );
	}

	if ( isset( $bp_pages->activate ) ) {
		$reordered_pages['activate'] = $bp_pages->activate;
		unset( $bp_pages->activate );
	}

	if ( $reordered_pages ) {
		foreach ( $reordered_pages as $page_key => $reordered_page ) {
			$bp_pages->{$page_key} = $reordered_page;
		}
	}

	// Members component navigations.
	$members_navigation     = bp_get_component_navigations();
	$members_sub_navigation = array();

	// Remove the members component navigation when needed.
	if ( bp_is_active( 'xprofile' ) ) {
		unset( $members_navigation['members'] );
	}

	bp_core_admin_tabbed_screen_header( __( 'BuddyPress Settings', 'buddypress' ), __( 'URLs', 'buddypress' ) );
	?>
	<div class="buddypress-body">
		<div class="health-check-body">
			<form action="" method="post" id="bp-admin-rewrites-form">
				<?php foreach ( $bp->pages as $component_id => $directory_data ) : ?>
					<div class="site-health-issues-wrapper">
						<h2>
							<?php
							if ( isset( $bp->{$component_id}->name ) && $bp->{$component_id}->name ) {
								echo esc_html( $bp->{$component_id}->name );
							} else {
								echo esc_html( $directory_data->title );
							}
							?>
						</h2>
						<div class="health-check-accordion">
							<h4 class="health-check-accordion-heading">
								<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-<?php echo esc_attr( $component_id ); ?>-directory" type="button">
									<span class="title"><?php ( 'activate' === $component_id || 'register' === $component_id ) ? esc_html_e( 'Page', 'buddypress' ) : esc_html_e( 'Directory', 'buddypress' ); ?></span>
									<span class="icon"></span>
								</button>
							</h4>
							<div id="health-check-accordion-block-<?php echo esc_attr( $component_id ); ?>-directory" class="health-check-accordion-panel" hidden="hidden">
								<table class="form-table" role="presentation">
									<tr>
										<th scope="row">
											<label for="<?php echo esc_attr( sprintf( '%s-directory-title', sanitize_key( $component_id ) ) ); ?>">
												<?php ( 'activate' === $component_id || 'register' === $component_id ) ? esc_html_e( 'Page title', 'buddypress' ) : esc_html_e( 'Directory title', 'buddypress' ); ?>
											</label>
										</th>
										<td>
											<input type="text" class="code" name="<?php printf( 'components[%d][post_title]', absint( $directory_data->id ) ); ?>" id="<?php echo esc_attr( sprintf( '%s-directory-title', sanitize_key( $component_id ) ) ); ?>" value="<?php echo esc_attr( $directory_data->title ); ?>">
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="<?php echo esc_attr( sprintf( '%s-directory-slug', sanitize_key( $component_id ) ) ); ?>">
												<?php ( 'activate' === $component_id || 'register' === $component_id ) ? esc_html_e( 'Page slug', 'buddypress' ) : esc_html_e( 'Directory slug', 'buddypress' ); ?>
											</label>
										</th>
										<td>
											<input type="text" class="code" name="<?php printf( 'components[%d][post_name]', absint( $directory_data->id ) ); ?>" id="<?php echo esc_attr( sprintf( '%s-directory-slug', sanitize_key( $component_id ) ) ); ?>" value="<?php echo esc_attr( $directory_data->slug ); ?>">
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="<?php echo esc_attr( sprintf( '%s-directory-url', sanitize_key( $component_id ) ) ); ?>">
												<?php ( 'activate' === $component_id || 'register' === $component_id ) ? esc_html_e( 'Page permalink', 'buddypress' ) : esc_html_e( 'Directory permalink', 'buddypress' ); ?>
											</label>
										</th>
										<td>
											<?php
											$url_args = array(
												'component_id' => $component_id,
											);

											if ( 'activate' === $component_id || 'register' === $component_id ) {
												$url_args = array(
													'component_id'                        => 'members',
													sprintf( 'member_%s', $component_id ) => 1,
												);
											}

											$permalink = bp_rewrites_get_url( $url_args );
											?>
											<input type="text" class="code bp-directory-url" id="<?php echo esc_attr( sprintf( '%s-directory-url', sanitize_key( $component_id ) ) ); ?>" value="<?php echo esc_url( $permalink ); ?>" disabled="disabled">

											<?php if ( 'activate' !== $component_id && 'register' !== $component_id ) : ?>
												<a href="<?php echo esc_url( $permalink ); ?>" class="button-secondary bp-open-permalink" target="_bp">
													<?php esc_html_e( 'View', 'buddypress' ); ?> <span class="dashicons dashicons-external" aria-hidden="true"></span>
													<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'buddypress' ); ?></span>
												</a>
											<?php endif; ?>
										</td>
									</tr>
								</table>
							</div>
						</div>

						<?php if ( 'members' === $component_id ) : ?>
							<div class="health-check-accordion">
								<h4 class="health-check-accordion-heading">
									<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-member-primary-nav" type="button">
										<span class="title"><?php esc_html_e( 'Single Member primary screens slugs', 'buddypress' ); ?></span>
										<span class="icon"></span>
									</button>
								</h4>
								<div id="health-check-accordion-block-member-primary-nav" class="health-check-accordion-panel" hidden="hidden">
									<table class="form-table" role="presentation">
										<?php
										foreach ( $members_navigation as $members_component => $navs ) :
											if ( ! isset( $navs['main_nav']['rewrite_id'] ) || ! $navs['main_nav']['rewrite_id'] ) {
												continue;
											}

											if ( isset( $navs['sub_nav'] ) ) {
												if ( 'profile' === $navs['main_nav']['slug'] ) {
													$edit_subnav = wp_list_filter( $navs['sub_nav'], array( 'slug' => 'edit' ) );
													$position    = key( $edit_subnav );

													if ( $edit_subnav ) {
														$edit_subnav = reset( $edit_subnav );
														array_splice(
															$navs['sub_nav'],
															$position + 1,
															0,
															array(
																array(
																	'name'       => __( 'Field Group', 'buddypress' ),
																	'slug'       => 'group',
																	'rewrite_id' => $edit_subnav['rewrite_id'] . '_group',
																)
															)
														);
													}
												}

												$members_sub_navigation[ $navs['main_nav']['slug'] ] = array(
													'name'    => $navs['main_nav']['name'],
													'sub_nav' => $navs['sub_nav'],
												);
											}
										?>
										<tr>
											<th scope="row">
												<label class="bp-nav-slug" for="<?php echo esc_attr( sprintf( '%s-slug', sanitize_key( $navs['main_nav']['rewrite_id'] ) ) ); ?>">
													<?php
													printf(
														/* translators: %s is the member primary screen name */
														esc_html_x( '"%s" slug', 'member primary screen name URL admin label', 'buddypress' ),
														esc_html( $navs['main_nav']['name'] )
													);
													?>
												</label>
											</th>
											<td>
												<input type="text" class="code" name="<?php printf( 'components[%1$d][_bp_component_slugs][%2$s]', absint( $directory_data->id ), esc_attr( $navs['main_nav']['rewrite_id'] ) ); ?>" id="<?php echo esc_attr( sprintf( '%s-slug', sanitize_key( $navs['main_nav']['rewrite_id'] ) ) ); ?>" value="<?php echo esc_attr( bp_rewrites_get_slug( $component_id, $navs['main_nav']['rewrite_id'],  $navs['main_nav']['slug'] ) ); ?>">
											</td>
										</tr>
										<?php endforeach; ?>
									</table>
								</div>
							</div>
							<?php if ( $members_sub_navigation ) : ?>
								<?php foreach ( $members_sub_navigation as $members_navigation_slug => $members_component_navigations ) : ?>
									<div class="health-check-accordion">
										<h4 class="health-check-accordion-heading">
											<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="<?php echo esc_attr( sprintf( 'health-check-accordion-block-member-%s-secondary-nav', $members_navigation_slug ) ); ?>" type="button">
												<?php /* translators: %s is the BP Component name the secondery views belong to. */ ?>
												<span class="title"><?php echo esc_html( sprintf( __( 'Single Member %s secondary screens slugs', 'buddypress' ), _bp_strip_spans_from_title( $members_component_navigations['name'] ) ) ); ?></span>
												<span class="icon"></span>
											</button>
										</h4>
										<div id="<?php echo esc_attr( sprintf( 'health-check-accordion-block-member-%s-secondary-nav', $members_navigation_slug ) ); ?>" class="health-check-accordion-panel" hidden="hidden">
											<table class="form-table" role="presentation">
												<?php
												foreach ( $members_component_navigations['sub_nav'] as $secondary_nav_item ) :
													if ( ! isset( $secondary_nav_item['rewrite_id'] ) || ! $secondary_nav_item['rewrite_id'] ) {
														continue;
													}
													?>
													<tr>
														<th scope="row">
															<label class="bp-nav-slug" for="<?php echo esc_attr( sprintf( '%s-slug', sanitize_key( $secondary_nav_item['rewrite_id'] ) ) ); ?>">
																<?php
																printf(
																	/* translators: %s is the member secondary view name */
																	esc_html_x( '"%s" slug', 'member secondary screen name URL admin label', 'buddypress' ),
																	esc_html( $secondary_nav_item['name'] )
																);
																?>
															</label>
														</th>
														<td>
															<input type="text" class="code" name="<?php printf( 'components[%1$d][_bp_component_slugs][%2$s]', absint( $directory_data->id ), esc_attr( $secondary_nav_item['rewrite_id'] ) ); ?>" id="<?php echo esc_attr( sprintf( '%s-slug', sanitize_key( $secondary_nav_item['rewrite_id'] ) ) ); ?>" value="<?php echo esc_attr( bp_rewrites_get_slug( $component_id, $secondary_nav_item['rewrite_id'], $secondary_nav_item['slug'] ) ); ?>">
														</td>
													</tr>
												<?php endforeach; ?>
											</table>
										</div>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						<?php endif; ?>

						<?php if ( 'groups' === $component_id ) : ?>

							<?php
							foreach (
								array(
									'create' => __( 'Single Group creation steps slugs', 'buddypress' ),
									'read'   => __( 'Single Group regular screens slugs', 'buddypress' ),
									'manage' => __( 'Single Group management screens slugs', 'buddypress' ),
								) as $screen_type => $screen_type_title ) :
								?>

								<div class="health-check-accordion">
									<h4 class="health-check-accordion-heading">
										<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-group-<?php echo esc_attr( $screen_type ); ?>" type="button">
											<span class="title"><?php echo esc_html( $screen_type_title ); ?></span>
											<span class="icon"></span>
										</button>
									</h4>
									<div id="health-check-accordion-block-group-<?php echo esc_attr( $screen_type ); ?>" class="health-check-accordion-panel" hidden="hidden">
										<table class="form-table" role="presentation">

										<?php
										if ( 'create' === $screen_type ) :
											foreach ( bp_get_group_restricted_screens() as $group_create_restricted_screen ) :
												?>
												<tr>
													<th scope="row">
														<label style="margin-left: 2em; display: inline-block; vertical-align: middle" for="<?php echo esc_attr( sprintf( '%s-slug', sanitize_key( $group_create_restricted_screen['rewrite_id'] ) ) ); ?>">
															<?php echo esc_html( $group_create_restricted_screen['name'] ); ?>
														</label>
													</th>
													<td>
														<input type="text" class="code" name="<?php printf( 'components[%1$d][_bp_component_slugs][%2$s]', absint( $directory_data->id ), esc_attr( $group_create_restricted_screen['rewrite_id'] ) ); ?>" id="<?php echo esc_attr( sprintf( '%s-slug', sanitize_key( $group_create_restricted_screen['rewrite_id'] ) ) ); ?>" value="<?php echo esc_attr( bp_rewrites_get_slug( $component_id, $group_create_restricted_screen['rewrite_id'], $group_create_restricted_screen['slug'] ) ); ?>">
													</td>
												</tr>
												<?php
											endforeach;

										endif;

										foreach ( bp_get_group_screens( $screen_type ) as $group_screen ) :
											if ( ! isset( $group_screen['rewrite_id'] ) || ! $group_screen['rewrite_id'] ) {
												continue;
											}
											?>
												<tr>
													<th scope="row">
														<label style="margin-left: 2em; display: inline-block; vertical-align: middle" for="<?php echo esc_attr( sprintf( '%s-slug', sanitize_key( $group_screen['rewrite_id'] ) ) ); ?>">
															<?php
															printf(
																/* translators: %s is group view name */
																esc_html_x( '"%s" slug', 'group view name URL admin label', 'buddypress' ),
																esc_html( str_replace( ' %s', '', $group_screen['name'] ) )
															);
															?>
														</label>
													</th>
													<td>
														<input type="text" class="code" name="<?php printf( 'components[%1$d][_bp_component_slugs][%2$s]', absint( $directory_data->id ), esc_attr( $group_screen['rewrite_id'] ) ); ?>" id="<?php echo esc_attr( sprintf( '%s-slug', sanitize_key( $group_screen['rewrite_id'] ) ) ); ?>" value="<?php echo esc_attr( bp_rewrites_get_slug( $component_id, $group_screen['rewrite_id'], $group_screen['slug'] ) ); ?>">
													</td>
												</tr>
											<?php endforeach; ?>
										</table>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>

				<p class="submit clear">
					<input class="button-primary" type="submit" name="bp-admin-rewrites-submit" id="bp-admin-rewrites-submit" value="<?php esc_attr_e( 'Save Settings', 'buddypress' ); ?>"/>
				</p>

				<?php wp_nonce_field( 'bp-admin-rewrites-setup' ); ?>

			</form>
		</div>
	</div>
	<?php
}
