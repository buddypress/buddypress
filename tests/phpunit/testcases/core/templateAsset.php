<?php

/**
 * @group core
 */
class BP_Tests_Template_Asset extends BP_UnitTestCase {
	private $filters = array();
	private $paths   = array();
	private $stacks  = array();

	public function tear_down() {
		foreach ( $this->filters as $filter ) {
			remove_filter( $filter[0], $filter[1] );
		}
		$this->filters = array();

		foreach ( $this->stacks as $stack ) {
			bp_deregister_template_stack( $stack, 1 );
		}
		$this->stacks = array();

		foreach ( array_reverse( $this->paths ) as $path ) {
			if ( is_link( $path ) || is_file( $path ) ) {
				unlink( $path );
			} elseif ( is_dir( $path ) ) {
				rmdir( $path );
			}
		}

		parent::tear_down();
	}

	private function add_asset_filter( $name, $callback, $accepted_args = 1 ) {
		add_filter( $name, $callback, 10, $accepted_args );
		$this->filters[] = array( $name, $callback );
	}

	private function create_directory( $path ) {
		$this->assertTrue( mkdir( $path ) );
		$this->paths[] = $path;
	}

	private function create_file( $path ) {
		$this->assertNotFalse( file_put_contents( $path, '' ) );
		$this->paths[] = $path;
	}

	private function create_root() {
		$root = sys_get_temp_dir() . '/bp-9354-' . wp_generate_uuid4();
		$this->create_directory( $root );
		return $root;
	}

	private function create_link( $target, $link ) {
		if ( ! function_exists( 'symlink' ) || ! @symlink( $target, $link ) ) {
			$this->markTestSkipped( 'The test environment cannot create symlinks.' );
		}
		$this->paths[] = $link;
	}

	/**
	 * @ticket 9354
	 */
	public function test_located_child_and_parent_assets_use_their_filtered_urls() {
		$root   = $this->create_root();
		$child  = $root . '/child';
		$parent = $root . '/parent';
		$this->create_directory( $child );
		$this->create_directory( $parent );
		$this->create_directory( $child . '/buddypress' );
		$this->create_directory( $child . '/buddypress/css' );
		$this->create_directory( $parent . '/buddypress' );
		$this->create_directory( $parent . '/buddypress/css' );
		$this->create_file( $child . '/buddypress/css/child.css' );
		$this->create_file( $parent . '/buddypress/css/parent.css' );

		$this->add_asset_filter(
			'stylesheet_directory',
			function () use ( $child ) {
				return $child;
			}
		);
		$this->add_asset_filter(
			'stylesheet_directory_uri',
			function () {
				return 'https://assets.example.test/child';
			}
		);
		$this->add_asset_filter(
			'template_directory',
			function () use ( $parent ) {
				return $parent;
			}
		);
		$this->add_asset_filter(
			'template_directory_uri',
			function () {
				return 'https://assets.example.test/parent';
			}
		);

		$this->assertSame(
			array(
				'uri'  => 'https://assets.example.test/child/buddypress/css/child.css',
				'file' => $child . '/buddypress/css/child.css',
			),
			bp_locate_template_asset( 'css/child.css' )
		);
		$this->assertSame(
			array(
				'uri'  => 'https://assets.example.test/parent/buddypress/css/parent.css',
				'file' => $parent . '/buddypress/css/parent.css',
			),
			bp_locate_template_asset( 'css/parent.css' )
		);
		$this->assertSame( $child . '-other/asset.css', bp_get_template_asset_uri( $child . '-other/asset.css' ) );
		$this->assertFalse( bp_locate_template_asset( 'css/missing-9354.css' ) );
	}

	/**
	 * @ticket 9354
	 */
	public function test_symlinked_theme_and_theme_package_assets_use_their_urls() {
		$root = $this->create_root();
		$real = $root . '/release';
		$link = $root . '/linked';
		$this->create_directory( $real );
		$this->create_directory( $real . '/css' );
		$this->create_file( $real . '/css/linked.css' );
		$this->create_link( $real, $link );

		$this->add_asset_filter(
			'stylesheet_directory',
			function () use ( $link ) {
				return $link;
			}
		);
		$this->add_asset_filter(
			'stylesheet_directory_uri',
			function () {
				return 'https://assets.example.test/theme';
			}
		);
		$this->assertSame( 'https://assets.example.test/theme/css/linked.css', bp_get_template_asset_uri( $real . '/css/linked.css' ) );
		$this->assertSame( $link . '/css/linked.css', bp_locate_template_asset( 'css/linked.css' )['file'] );

		$package_real = $root . '/package-release';
		$package_link = $root . '/package-linked';
		$this->create_directory( $package_real );
		$this->create_directory( $package_real . '/css' );
		$this->create_file( $package_real . '/css/package.css' );
		$this->create_link( $package_real, $package_link );

		$this->add_asset_filter(
			'bp_get_theme_compat_dir',
			function () use ( $package_link ) {
				return $package_link;
			}
		);
		$this->add_asset_filter(
			'bp_get_theme_compat_url',
			function () {
				return 'https://assets.example.test/package';
			}
		);
		$this->assertSame( 'https://assets.example.test/package/css/package.css', bp_get_template_asset_uri( $package_real . '/css/package.css' ) );
		$this->assertSame( 'https://assets.example.test/package/css/package.css', bp_locate_template_asset( 'css/package.css' )['uri'] );
	}

	/**
	 * @ticket 9354
	 */
	public function test_custom_template_stack_urls_include_symlinked_custom_directories() {
		$root = $this->create_root();
		$real = $root . '/release';
		$link = $root . '/linked';
		$this->create_directory( $real );
		$this->create_directory( $real . '/buddypress' );
		$this->create_directory( $real . '/buddypress/css' );
		$this->create_file( $real . '/buddypress/css/custom.css' );
		$this->create_link( $real, $link );

		$stack = function () use ( $real ) {
			return $real;
		};
		bp_register_template_stack( $stack, 1 );
		$this->stacks[] = $stack;

		$directories = array( $real => 'https://assets.example.test/custom' );
		$this->add_asset_filter(
			'bp_template_asset_directory_urls',
			function () use ( &$directories ) {
				return $directories;
			}
		);
		$this->assertSame(
			array(
				'uri'  => 'https://assets.example.test/custom/buddypress/css/custom.css',
				'file' => $real . '/buddypress/css/custom.css',
			),
			bp_locate_template_asset( 'css/custom.css' )
		);

		$directories = array( $link => 'https://assets.example.test/linked-custom' );
		$this->assertSame( 'https://assets.example.test/linked-custom/buddypress/css/custom.css', bp_locate_template_asset( 'css/custom.css' )['uri'] );
		$this->assertSame( 'https://assets.example.test/linked-custom/buddypress/css/custom.css', bp_get_template_asset_uri( $link . '/buddypress/css/custom.css' ) );
		$this->assertSame( $real . '-other/asset.css', bp_get_template_asset_uri( $real . '-other/asset.css' ) );
	}

	/**
	 * @ticket 9354
	 */
	public function test_plugin_theme_root_and_content_urls_keep_wordpress_filters() {
		$this->add_asset_filter(
			'plugins_url',
			function ( $url ) {
				return str_replace( '://', '://plugin-filtered.', $url );
			}
		);
		$this->add_asset_filter(
			'theme_root_uri',
			function () {
				return 'https://assets.example.test/themes';
			}
		);
		$this->add_asset_filter(
			'content_url',
			function ( $url ) {
				return str_replace( '://', '://content-filtered.', $url );
			}
		);

		$plugin_file = trailingslashit( WP_PLUGIN_DIR ) . 'another-plugin/assets/probe.css';
		$mu_file     = trailingslashit( WPMU_PLUGIN_DIR ) . 'another-plugin/assets/probe.css';
		$this->assertSame( plugins_url( basename( $plugin_file ), $plugin_file ), bp_get_template_asset_uri( $plugin_file ) );
		$this->assertSame( plugins_url( basename( $mu_file ), $mu_file ), bp_get_template_asset_uri( $mu_file ) );
		$this->assertSame( 'https://assets.example.test/themes/other-theme/probe.css', bp_get_template_asset_uri( trailingslashit( get_theme_root() ) . 'other-theme/probe.css' ) );
		$this->assertSame( content_url( '/uploads/bp-9354/probe.css' ), bp_get_template_asset_uri( trailingslashit( WP_CONTENT_DIR ) . 'uploads/bp-9354/probe.css' ) );
	}

	/**
	 * @ticket 9354
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_legacy_asset_lookup_uses_the_custom_url_mapping() {
		require_once buddypress()->plugin_dir . 'bp-templates/bp-legacy/buddypress-functions.php';

		$root = $this->create_root();
		$this->create_directory( $root . '/buddypress' );
		$this->create_directory( $root . '/buddypress/css' );
		$this->create_file( $root . '/buddypress/css/buddypress.css' );

		$this->add_asset_filter(
			'template_directory',
			function () use ( $root ) {
				return $root;
			}
		);
		$this->add_asset_filter(
			'template_directory_uri',
			function () {
				return 'https://assets.example.test/parent';
			}
		);
		$custom_directory = $root;
		$this->add_asset_filter(
			'bp_template_asset_directory_urls',
			function () use ( &$custom_directory ) {
				return $custom_directory ? array( $custom_directory => 'https://assets.example.test/custom' ) : array();
			}
		);

		$legacy = ( new ReflectionClass( 'BP_Legacy' ) )->newInstanceWithoutConstructor();
		$method = new ReflectionMethod( 'BP_Legacy', 'locate_asset_in_stack' );
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			$method->setAccessible( true );
		}
		$this->assertSame(
			array(
				'location' => 'https://assets.example.test/custom/buddypress/css/buddypress.css',
				'handle'   => 'bp-parent-css',
			),
			$method->invoke( $legacy, 'buddypress.css', 'css' )
		);

		// A package within the parent theme retains its own public URL.
		$custom_directory = '';
		$package = $root . '/package';
		$this->create_directory( $package );
		$this->create_directory( $package . '/css' );
		$this->create_file( $package . '/css/package.css' );
		$this->add_asset_filter(
			'bp_get_theme_compat_dir',
			function () use ( $package ) {
				return $package;
			}
		);
		$this->add_asset_filter(
			'bp_get_theme_compat_url',
			function () {
				return 'https://assets.example.test/package';
			}
		);
		$this->assertSame(
			array(
				'location' => 'https://assets.example.test/package/css/package.css',
				'handle'   => 'bp-legacy-css',
			),
			$method->invoke( $legacy, 'package.css', 'css' )
		);
	}

	/**
	 * @ticket 9354
	 */
	public function test_wordpress_plugin_realpath_mapping_uses_the_plugin_url() {
		global $wp_plugin_paths;

		$original_paths = $wp_plugin_paths;
		$root           = $this->create_root();
		$real           = $root . '/plugin';
		$logical        = trailingslashit( WP_PLUGIN_DIR ) . 'bp-9354-mapped-plugin';
		$this->create_directory( $real );
		$this->create_directory( $real . '/assets' );
		$this->create_file( $real . '/assets/probe.css' );

		$wp_plugin_paths[ wp_normalize_path( $logical ) ] = wp_normalize_path( $real );
		$this->add_asset_filter(
			'plugins_url',
			function ( $url ) {
				return str_replace( '://', '://plugin-filtered.', $url );
			}
		);
		$this->add_asset_filter(
			'content_url',
			function ( $url ) {
				return str_replace( '://', '://content-filtered.', $url );
			}
		);

		try {
			$this->assertSame(
				plugins_url( 'assets/probe.css', $logical . '/plugin.php' ),
				bp_get_template_asset_uri( $real . '/assets/probe.css' )
			);
		} finally {
			$wp_plugin_paths = $original_paths;
		}
	}

	/**
	 * @ticket 9354
	 */
	public function test_empty_location_does_not_shadow_other_mappings() {
		$root = $this->create_root();
		$this->create_file( $root . '/probe.css' );
		$url = null;
		$this->add_asset_filter(
			'bp_template_asset_directory_urls',
			function () use ( $root ) {
				return array(
					''    => 'https://assets.example.test/empty',
					$root => '',
				);
			}
		);
		$this->add_asset_filter(
			'bp_get_theme_compat_dir',
			function () use ( $root ) {
				return $root;
			}
		);
		$this->add_asset_filter(
			'bp_get_theme_compat_url',
			function () use ( &$url ) {
				return $url;
			}
		);
		$this->assertSame( $root . '/probe.css', bp_get_template_asset_uri( $root . '/probe.css' ) );
		$url = '';
		$this->assertSame( $root . '/probe.css', bp_get_template_asset_uri( $root . '/probe.css' ) );
		$this->assertFalse( bp_get_template_asset_relative_path( $root . '/probe.css', $root . '/probe.css', '' ) );
	}

	/**
	 * @ticket 9354
	 */
	public function test_symlinked_buddypress_plugin_directory_uses_its_configured_url() {
		$bp           = buddypress();
		$original_dir = $bp->plugin_dir;
		$original_url = $bp->plugin_url;
		$real_dir     = realpath( $original_dir );
		if ( false === $real_dir ) {
			$this->markTestSkipped( 'The BuddyPress plugin directory is unavailable.' );
		}

		$root = $this->create_root();
		$link = $root . '/linked';
		$this->create_link( $real_dir, $link );
		$bp->plugin_dir = trailingslashit( $link );
		$bp->plugin_url = 'https://assets.example.test/buddypress/';

		try {
			$this->assertSame(
				'https://assets.example.test/buddypress/bp-core/bp-core-template-loader.php',
				bp_get_template_asset_uri( $real_dir . '/bp-core/bp-core-template-loader.php' )
			);
		} finally {
			$bp->plugin_dir = $original_dir;
			$bp->plugin_url = $original_url;
		}
	}

	/**
	 * @ticket 9354
	 */
	public function test_plugin_url_fallback_rechecks_a_changed_directory() {
		$bp           = buddypress();
		$original_dir = $bp->plugin_dir;
		$original_url = $bp->plugin_url;
		$root         = $this->create_root();
		$this->create_directory( $root . '/assets' );
		$this->create_file( $root . '/assets/probe.css' );
		$change_directory = false;

		$this->add_asset_filter(
			'theme_root_uri',
			function ( $url, $siteurl, $stylesheet_or_template ) use ( $bp, $root, &$change_directory ) {
				if ( $change_directory && '' === $stylesheet_or_template ) {
					$bp->plugin_dir = $root;
					$bp->plugin_url = 'https://assets.example.test/changed-plugin';
				}
				return $url;
			},
			3
		);

		try {
			$bp->plugin_dir = $root;
			$bp->plugin_url = 'https://assets.example.test/configured-plugin';
			$this->assertSame(
				'https://assets.example.test/configured-plugin/assets/probe.css',
				bp_get_template_asset_uri( $root . '/assets/probe.css' )
			);

			$bp->plugin_dir = '';
			$bp->plugin_url = '';
			$change_directory = true;
			$this->assertSame(
				'https://assets.example.test/changed-plugin/assets/probe.css',
				bp_get_template_asset_uri( $root . '/assets/probe.css' )
			);
		} finally {
			$bp->plugin_dir = $original_dir;
			$bp->plugin_url = $original_url;
		}
	}
}
