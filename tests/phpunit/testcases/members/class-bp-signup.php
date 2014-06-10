<?php

/**
 * @group members
 * @group signups
 * @group BP_Signup
 */
class BP_Tests_BP_Signup extends BP_UnitTestCase {
	protected $signup_allowed;

	public function setUp() {

		if ( is_multisite() ) {
			$this->signup_allowed = get_site_option( 'registration' );
			update_site_option( 'registration', 'all' );
		} else {
			bp_get_option( 'users_can_register' );
			bp_update_option( 'users_can_register', 1 );
		}

		parent::setUp();
	}

	public function tearDown() {
		if ( is_multisite() ) {
			update_site_option( 'registration', $this->signup_allowed );
		} else {
			bp_update_option( 'users_can_register', $this->signup_allowed );
		}

		parent::tearDown();
	}

	/**
	 * @group add
	 */
	public function test_add() {
		$time = bp_core_current_time();
		$args = array(
			'domain' => 'foo',
			'path' => 'bar',
			'title' => 'Foo bar',
			'user_login' => 'user1',
			'user_email' => 'user1@example.com',
			'registered' => $time,
			'activation_key' => '12345',
			'meta' => array(
				'field_1' => 'Foo Bar',
				'meta1' => 'meta2',
			),
		);

		$signup = BP_Signup::add( $args );
		$this->assertNotEmpty( $signup );

		$s = new BP_Signup( $signup );

		// spot check
		$this->assertSame( $signup, $s->id );
		$this->assertSame( 'user1', $s->user_login );
		$this->assertSame( '12345', $s->activation_key );
	}

	/**
	 * @group get
	 */
	public function test_get_with_offset() {
		$s1 = $this->factory->signup->create();
		$s2 = $this->factory->signup->create();
		$s3 = $this->factory->signup->create();

		$ss = BP_Signup::get( array(
			'offset' => 1,
		) );

		$this->assertEquals( array( $s2 ), wp_list_pluck( $ss['signups'], 'signup_id' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_with_number() {
		$s1 = $this->factory->signup->create();
		$s2 = $this->factory->signup->create();
		$s3 = $this->factory->signup->create();

		$ss = BP_Signup::get( array(
			'number' => 2,
		) );

		$this->assertEquals( array( $s3, $s2 ), wp_list_pluck( $ss['signups'], 'signup_id' ) );
	}

	/**
	 * @group get
	 * @expectedDeprecated like_escape
	 */
	public function test_get_with_usersearch() {
		$s1 = $this->factory->signup->create( array(
			'user_email' => 'fghij@example.com',
		) );
		$s2 = $this->factory->signup->create();
		$s3 = $this->factory->signup->create();

		$ss = BP_Signup::get( array(
			'usersearch' => 'ghi',
		) );

		$this->assertEquals( array( $s1 ), wp_list_pluck( $ss['signups'], 'signup_id' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_with_orderby_email() {
		$s1 = $this->factory->signup->create( array(
			'user_email' => 'fghij@example.com',
		) );
		$s2 = $this->factory->signup->create( array(
			'user_email' => 'abcde@example.com',
		) );
		$s3 = $this->factory->signup->create( array(
			'user_email' => 'zzzzz@example.com',
		) );

		$ss = BP_Signup::get( array(
			'orderby' => 'email',
			'number' => 3,
		) );

		// default order is DESC
		$this->assertEquals( array( $s3, $s1, $s2 ), wp_list_pluck( $ss['signups'], 'signup_id' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_with_orderby_email_asc() {
		$s1 = $this->factory->signup->create( array(
			'user_email' => 'fghij@example.com',
		) );
		$s2 = $this->factory->signup->create( array(
			'user_email' => 'abcde@example.com',
		) );
		$s3 = $this->factory->signup->create( array(
			'user_email' => 'zzzzz@example.com',
		) );

		$ss = BP_Signup::get( array(
			'orderby' => 'email',
			'number' => 3,
			'order' => 'ASC',
		) );

		$this->assertEquals( array( $s2, $s1, $s3 ), wp_list_pluck( $ss['signups'], 'signup_id' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_with_include() {
		$s1 = $this->factory->signup->create();
		$s2 = $this->factory->signup->create();
		$s3 = $this->factory->signup->create();

		$ss = BP_Signup::get( array(
			'include' => array( $s1, $s3 ),
		) );

		$this->assertEquals( array( $s1, $s3 ), wp_list_pluck( $ss['signups'], 'signup_id' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_with_activation_key() {
		$s1 = $this->factory->signup->create( array(
			'activation_key' => 'foo',
		) );
		$s2 = $this->factory->signup->create( array(
			'activation_key' => 'bar',
		) );
		$s3 = $this->factory->signup->create( array(
			'activation_key' => 'baz',
		) );

		$ss = BP_Signup::get( array(
			'activation_key' => 'bar',
		) );

		$this->assertEquals( array( $s2 ), wp_list_pluck( $ss['signups'], 'signup_id' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_with_user_login() {
		$s1 = $this->factory->signup->create( array(
			'user_login' => 'aaaafoo',
		) );
		$s2 = $this->factory->signup->create( array(
			'user_login' => 'zzzzfoo',
		) );
		$s3 = $this->factory->signup->create( array(
			'user_login' => 'jjjjfoo',
		) );

		$ss = BP_Signup::get( array(
			'user_login' => 'zzzzfoo',
		) );

		$this->assertEquals( array( $s2 ), wp_list_pluck( $ss['signups'], 'signup_id' ) );
	}
}