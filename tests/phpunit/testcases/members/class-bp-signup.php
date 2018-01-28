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
	 * @group add
	 */
	public function test_add_no_visibility_level_set_should_use_default_visiblity_level() {
		// Update field_1's default visiblity to 'adminsonly'
		bp_xprofile_update_field_meta( 1, 'default_visibility', 'adminsonly' );

		// Add new signup without a custom field visibility set for field_1.
		$signup = BP_Signup::add( array(
			'title' => 'Foo bar',
			'user_login' => 'user1',
			'user_email' => 'user1@example.com',
			'registered' => bp_core_current_time(),
			'activation_key' => '12345',
			'meta' => array(
				'field_1' => 'Foo Bar',
				'meta1' => 'meta2',
				'password' => 'password',

				/*
				 * Ensure we pass the field ID.
				 *
				 * See bp_core_activate_signup() and BP_Signup::add_backcompat().
				 */
				'profile_field_ids' => '1'
			),
		) );

		// Activate the signup.
		$activate = BP_Signup::activate( (array) $signup );

		// Assert that field 1's visibility for the signup is still 'adminsonly'
		$vis = xprofile_get_field_visibility_level( 1, $activate['activated'][0] );
		$this->assertSame( 'adminsonly', $vis );
	}

	/**
	 * @group get
	 */
	public function test_get_with_offset() {
		$s1 = self::factory()->signup->create();
		$s2 = self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'offset' => 1,
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s2 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_number() {
		$s1 = self::factory()->signup->create();
		$s2 = self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'number' => 2,
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s3, $s2 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_usersearch() {
		$s1 = self::factory()->signup->create( array(
			'user_email' => 'fghij@example.com',
		) );
		$s2 = self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'usersearch' => 'ghi',
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s1 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_orderby_email() {
		$s1 = self::factory()->signup->create( array(
			'user_email' => 'fghij@example.com',
		) );
		$s2 = self::factory()->signup->create( array(
			'user_email' => 'abcde@example.com',
		) );
		$s3 = self::factory()->signup->create( array(
			'user_email' => 'zzzzz@example.com',
		) );

		$ss = BP_Signup::get( array(
			'orderby' => 'email',
			'number' => 3,
			'fields' => 'ids',
		) );

		// default order is DESC.
		$this->assertEquals( array( $s3, $s1, $s2 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_orderby_email_asc() {
		$s1 = self::factory()->signup->create( array(
			'user_email' => 'fghij@example.com',
		) );
		$s2 = self::factory()->signup->create( array(
			'user_email' => 'abcde@example.com',
		) );
		$s3 = self::factory()->signup->create( array(
			'user_email' => 'zzzzz@example.com',
		) );

		$ss = BP_Signup::get( array(
			'orderby' => 'email',
			'number' => 3,
			'order' => 'ASC',
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s2, $s1, $s3 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_include() {
		$s1 = self::factory()->signup->create();
		$s2 = self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'include' => array( $s1, $s3 ),
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s1, $s3 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_activation_key() {
		$s1 = self::factory()->signup->create( array(
			'activation_key' => 'foo',
		) );
		$s2 = self::factory()->signup->create( array(
			'activation_key' => 'bar',
		) );
		$s3 = self::factory()->signup->create( array(
			'activation_key' => 'baz',
		) );

		$ss = BP_Signup::get( array(
			'activation_key' => 'bar',
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s2 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_user_login() {
		$s1 = self::factory()->signup->create( array(
			'user_login' => 'aaaafoo',
		) );
		$s2 = self::factory()->signup->create( array(
			'user_login' => 'zzzzfoo',
		) );
		$s3 = self::factory()->signup->create( array(
			'user_login' => 'jjjjfoo',
		) );

		$ss = BP_Signup::get( array(
			'user_login' => 'zzzzfoo',
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s2 ), $ss['signups'] );
	}

	/**
	 * @group activate
	 */
	public function test_activate_user_accounts() {
		$signups = array();

		$signups['accountone'] = self::factory()->signup->create( array(
			'user_login'     => 'accountone',
			'user_email'     => 'accountone@example.com',
			'activation_key' => 'activationkeyone',
		) );

		$signups['accounttwo'] = self::factory()->signup->create( array(
			'user_login'     => 'accounttwo',
			'user_email'     => 'accounttwo@example.com',
			'activation_key' => 'activationkeytwo',
		) );

		$signups['accountthree'] = self::factory()->signup->create( array(
			'user_login'     => 'accountthree',
			'user_email'     => 'accountthree@example.com',
			'activation_key' => 'activationkeythree',
		) );

		$results = BP_Signup::activate( $signups );
		$this->assertNotEmpty( $results['activated'] );

		$users = array();

		foreach ( $signups as $login => $signup_id  ) {
			$users[ $login ] = get_user_by( 'login', $login );
		}

		$this->assertEqualSets( $results['activated'], wp_list_pluck( $users, 'ID' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_signup_ids_only() {
		$s1 = self::factory()->signup->create();
		$s2 = self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'number' => 3,
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s3, $s2, $s1 ), $ss['signups'] );
	}
}
