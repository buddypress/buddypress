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

	wp_enqueue_script( 'bp-rewrites-ui' );
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
									<span class="title"><?php esc_html_e( 'Directory', 'buddypress' ); ?></span>
									<span class="icon"></span>
								</button>
							</h4>
							<div id="health-check-accordion-block-<?php echo esc_attr( $component_id ); ?>-directory" class="health-check-accordion-panel" hidden="hidden">
								<table class="form-table" role="presentation">
									<tr>
										<th scope="row">
											<label for="<?php echo esc_attr( sprintf( '%s-directory-title', sanitize_key( $component_id ) ) ); ?>">
												<?php esc_html_e( 'Directory title', 'buddypress' ); ?>
											</label>
										</th>
										<td>
											<input type="text" class="code" name="<?php printf( 'components[%d][post_title]', absint( $directory_data->id ) ); ?>" id="<?php echo esc_attr( sprintf( '%s-directory-title', sanitize_key( $component_id ) ) ); ?>" value="<?php echo esc_attr( $directory_data->title ); ?>">
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="<?php echo esc_attr( sprintf( '%s-directory-slug', sanitize_key( $component_id ) ) ); ?>">
												<?php esc_html_e( 'Directory slug', 'buddypress' ); ?>
											</label>
										</th>
										<td>
											<input type="text" class="code" name="<?php printf( 'components[%d][post_name]', absint( $directory_data->id ) ); ?>" id="<?php echo esc_attr( sprintf( '%s-directory-slug', sanitize_key( $component_id ) ) ); ?>" value="<?php echo esc_attr( $directory_data->slug ); ?>">
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
														esc_html( _bp_strip_spans_from_title( $navs['main_nav']['name'] ) )
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
																	esc_html( _bp_strip_spans_from_title( $secondary_nav_item['name'] ) )
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
					</div>
				<?php endforeach; ?>
			</form>
		</div>
	</div>
	<?php
}
