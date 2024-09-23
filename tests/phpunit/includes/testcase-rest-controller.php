<?php
/**
 * Controller testcase for BuddyPress REST API endpoints.
 */
abstract class BP_Test_REST_Controller_Testcase extends WP_Test_REST_Controller_Testcase {

	/**
	 * The endpoint/controller class name.
	 *
	 * @var string
	 */
	protected $controller;

	/**
	 * The endpoint controller.
	 *
	 * @var WP_REST_Controller
	 */
	protected $endpoint;

	/**
	 * The endpoint URL.
	 *
	 * @var string
	 */
	protected $endpoint_url;

	/**
	 * The BP_UnitTestCase instance.
	 *
	 * Allows accessing BuddyPress custom factories.
	 *
	 * @var BP_UnitTestCase
	 */
	protected $bp;

	/**
	 * The WP_REST_Server instance.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * The user.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * The endpoint handle.
	 *
	 * @var string
	 */
	protected $handle;

	public function set_up() {
		parent::set_up();

		if ( $this->controller ) {
			$this->endpoint = new $this->controller();
		}

		if ( ! $this->bp ) {
			$this->bp = new BP_UnitTestCase();
		}

		if ( $this->handle ) {
			$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/' . $this->handle;
		}

		if ( ! $this->server ) {
			$this->server = rest_get_server();
		}

		$admin = static::factory()->user->create_and_get(
			array(
				'role'       => 'administrator',
				'user_email' => 'admin@example.com',
			)
		);

		$this->user = $admin->ID;

		if ( is_multisite() ) {
			grant_super_admin( $this->user );

			$admin->add_cap( 'manage_network_users' );
		}
	}
}
