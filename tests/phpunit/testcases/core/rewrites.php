<?php
include_once BP_TESTS_DIR . '/assets/class-bptest-component.php';

/**
 * @group core
 */
class BP_Tests_Core_Rewrites extends BP_UnitTestCase {
	protected $permalink_structure = '';

	public function set_up() {
		$bp = buddypress();
		$this->permalink_structure = get_option( 'permalink_structure', '' );

		$bp->buddies = new BPTest_Component(
			array(
				'id'      => 'buddies',
				'name'    => 'Buddies',
				'globals' => array(
					'has_directory' => true,
					'root_slug'     => 'buddies',
					'rewrite_ids'   => array(
						'directory'                    => 'buddies',
						'single_item'                  => 'buddy',
						'single_item_component'        => 'buddy_component',
						'single_item_action'           => 'buddy_action',
						'single_item_action_variables' => 'buddy_action_variables',
					),
				),
			)
		);

		$bp->buddies->setup_globals();
		$bp->buddies->add_rewrite_tags();
		$bp->buddies->add_rewrite_rules();
		$bp->buddies->add_permastructs();

		parent::set_up();
	}

	public function tear_down() {
		$bp = buddypress();
		unset( $bp->buddies );

		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	/**
	 * @group bp_rewrites_get_url
	 * @group bp_rewrites_get_root_url
	 */
	public function test_bp_rewrites_get_root_url() {
		$root_url = get_home_url( bp_get_root_blog_id() );
		$this->assertEquals( $root_url, bp_rewrites_get_root_url() );
	}

	/**
	 * @group bp_rewrites_get_url
	 */
	public function test_bp_rewrites_get_url_directory_plain() {
		$this->set_permalink_structure( '' );

		$buddies_url = bp_rewrites_get_url(
			array(
				'component_id' => 'buddies',
			)
		);

		$qs = wp_parse_url( $buddies_url, PHP_URL_QUERY );
		$this->assertEquals( 'bp_buddies=1', $qs );
	}

	/**
	 * @group bp_rewrites_get_url
	 */
	public function test_bp_rewrites_get_url_directory_pretty() {
		$this->set_permalink_structure( '/%postname%/' );

		$buddies_url = bp_rewrites_get_url(
			array(
				'component_id' => 'buddies',
			)
		);

		$path = wp_parse_url( $buddies_url, PHP_URL_PATH );
		$this->assertEquals( '/buddies/', $path );
	}

	/**
	 * @group bp_rewrites_get_url
	 */
	public function test_bp_rewrites_get_url_single_item_plain() {
		$this->set_permalink_structure( '' );

		$buddies_url = bp_rewrites_get_url(
			array(
				'component_id' => 'buddies',
				'single_item'  => 'foobar',
			)
		);

		$qs = wp_parse_url( $buddies_url, PHP_URL_QUERY );
		$this->assertEquals( 'bp_buddies=1&bp_buddy=foobar', $qs );
	}

	/**
	 * @group bp_rewrites_get_url
	 */
	public function test_bp_rewrites_get_url_single_item_pretty() {
		$this->set_permalink_structure( '/%postname%/' );

		$buddies_url = bp_rewrites_get_url(
			array(
				'component_id' => 'buddies',
				'single_item'  => 'foobar',
			)
		);

		$path = wp_parse_url( $buddies_url, PHP_URL_PATH );
		$this->assertEquals( '/buddies/foobar/', $path );
	}

	/**
	 * @group bp_rewrites_get_url
	 */
	public function test_bp_rewrites_get_url_single_item_component_plain() {
		$this->set_permalink_structure( '' );

		$buddies_url = bp_rewrites_get_url(
			array(
				'component_id'          => 'buddies',
				'single_item'           => 'foobar',
				'single_item_component' => 'activity',
			)
		);

		$qs = wp_parse_url( $buddies_url, PHP_URL_QUERY );
		$this->assertEquals( 'bp_buddies=1&bp_buddy=foobar&bp_buddy_component=activity', $qs );
	}

	/**
	 * @group bp_rewrites_get_url
	 */
	public function test_bp_rewrites_get_url_single_item_component_pretty() {
		$this->set_permalink_structure( '/%postname%/' );

		$buddies_url = bp_rewrites_get_url(
			array(
				'component_id'          => 'buddies',
				'single_item'           => 'foobar',
				'single_item_component' => 'activity',
			)
		);

		$path = wp_parse_url( $buddies_url, PHP_URL_PATH );
		$this->assertEquals( '/buddies/foobar/activity/', $path );
	}

	/**
	 * @group bp_rewrites_get_url
	 */
	public function test_bp_rewrites_get_url_single_item_action_plain() {
		$this->set_permalink_structure( '' );

		$buddies_url = bp_rewrites_get_url(
			array(
				'component_id'          => 'buddies',
				'single_item'           => 'foobar',
				'single_item_component' => 'activity',
				'single_item_action'    => 'mention',
			)
		);

		$qs = wp_parse_url( $buddies_url, PHP_URL_QUERY );
		$this->assertEquals( 'bp_buddies=1&bp_buddy=foobar&bp_buddy_component=activity&bp_buddy_action=mention', $qs );
	}

	/**
	 * @group bp_rewrites_get_url
	 */
	public function test_bp_rewrites_get_url_single_item_action_pretty() {
		$this->set_permalink_structure( '/%postname%/' );

		$buddies_url = bp_rewrites_get_url(
			array(
				'component_id'          => 'buddies',
				'single_item'           => 'foobar',
				'single_item_component' => 'activity',
				'single_item_action'    => 'mention',
			)
		);

		$path = wp_parse_url( $buddies_url, PHP_URL_PATH );
		$this->assertEquals( '/buddies/foobar/activity/mention/', $path );
	}

	/**
	 * @group bp_rewrites_get_url
	 */
	public function test_bp_rewrites_get_url_single_item_action_variables_plain() {
		$this->set_permalink_structure( '' );
		$expected = array( 'do', 'it', 'again' );

		$buddies_url = bp_rewrites_get_url(
			array(
				'component_id'                 => 'buddies',
				'single_item'                  => 'foobar',
				'single_item_component'        => 'activity',
				'single_item_action'           => 'mention',
				'single_item_action_variables' => $expected,
			)
		);

		$qs        = wp_parse_url( $buddies_url, PHP_URL_QUERY );
		$parsed_qs = bp_parse_args( $qs, array() );

		$this->assertSame( $expected, $parsed_qs['bp_buddy_action_variables'] );
	}

	/**
	 * @group bp_rewrites_get_url
	 */
	public function test_bp_rewrites_get_url_single_item_action_variables_pretty() {
		$this->set_permalink_structure( '/%postname%/' );

		$buddies_url = bp_rewrites_get_url(
			array(
				'component_id'                 => 'buddies',
				'single_item'                  => 'foobar',
				'single_item_component'        => 'activity',
				'single_item_action'           => 'mention',
				'single_item_action_variables' => array( 'do', 'it', 'again' ),
			)
		);

		$path = wp_parse_url( $buddies_url, PHP_URL_PATH );
		$this->assertEquals( '/buddies/foobar/activity/mention/do/it/again/', $path );
	}
}
