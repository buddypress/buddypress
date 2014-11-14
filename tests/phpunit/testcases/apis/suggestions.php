<?php
/**
 * Suggestions API tests for authenticated (logged in) users.
 *
 * @group api
 * @group suggestions
 */
class BP_Tests_Suggestions_Authenticated extends BP_UnitTestCase {
	protected static $current_user = null;
	protected static $group_ids    = array();
	protected static $group_slugs  = array();
	protected static $old_user_id  = 0;
	protected static $user_ids     = array();

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		$factory = new BP_UnitTest_Factory();

		self::$old_user_id  = get_current_user_id();
		self::$current_user = $factory->user->create( array(
			'display_name' => 'Katie Parker',
			'user_login'   => 'katie',
			'user_email'   => 'test-katie@example.com',
		) );

		$users = array(
			// user_login, display_name
			array( 'aardvark',    'Bob Smith' ),
			array( 'alpaca red',  'William Quinn' ),
			array( 'cat',         'Lauren Curtis' ),
			array( 'caterpillar', 'Eldon Burrows' ),
			array( 'dog green',   'Reece Thornton' ),
			array( 'pig',         'Joshua Barton' ),
			array( 'rabbit blue', 'Amber Hooper' ),
			array( 'smith',       'Robert Bar' ),
			array( 'snake',       'Eleanor Moore' ),
			array( 'xylo',        'Silver McFadden' ),
			array( 'zoom',        'Lisa Smithy' ),
		);

		// Create some dummy users.
		foreach ( $users as $user_index => $user ) {
			$new_user = $factory->user->create( array(
				'display_name' => $user[1],
				'user_login'   => $user[0],
				'user_email'   => "test-$user_index@example.com",
			) );

			self::$user_ids[ $user[0] ] = $new_user;
		}

		// Create some dummy friendships (but not the corresponding activity items).
		remove_action( 'friends_friendship_accepted', 'bp_friends_friendship_accepted_activity', 10, 4 );
		friends_add_friend( self::$current_user, self::$user_ids['aardvark'], true );
		friends_add_friend( self::$current_user, self::$user_ids['cat'], true );
		friends_add_friend( self::$current_user, self::$user_ids['caterpillar'], true );
		friends_add_friend( self::$current_user, self::$user_ids['pig'], true );
		add_action( 'friends_friendship_accepted', 'bp_friends_friendship_accepted_activity', 10, 4 );

		self::$group_slugs['hidden']  = 'the-maw';
		self::$group_slugs['public']  = 'the-great-journey';
		self::$group_slugs['private'] = 'tsavo-highway';

		// Create dummy groups.
		self::$group_ids['hidden'] = $factory->group->create( array(
			'creator_id' => self::$user_ids['xylo'],
			'slug'       => self::$group_slugs['hidden'],
			'status'     => 'hidden',
		) );
		self::$group_ids['public'] = $factory->group->create( array(
			'creator_id' => self::$user_ids['xylo'],
			'slug'       => self::$group_slugs['public'],
			'status'     => 'public',
		) );
		self::$group_ids['private'] = $factory->group->create( array(
			'creator_id' => self::$user_ids['xylo'],
			'slug'       => self::$group_slugs['private'],
			'status'     => 'private',
		) );

		// Add dummy users to dummy hidden groups.
		groups_join_group( self::$group_ids['hidden'], self::$user_ids['pig'] );
		groups_join_group( self::$group_ids['hidden'], self::$user_ids['alpaca red'] );

		// Add dummy users to dummy public groups.
		groups_join_group( self::$group_ids['public'], self::$current_user );
		groups_join_group( self::$group_ids['public'], self::$user_ids['aardvark'] );
		groups_join_group( self::$group_ids['public'], self::$user_ids['alpaca red'] );
		groups_join_group( self::$group_ids['public'], self::$user_ids['cat'] );
		groups_join_group( self::$group_ids['public'], self::$user_ids['smith'] );

		// Add dummy users to dummy private groups.
		groups_join_group( self::$group_ids['private'], self::$user_ids['cat'] );
		groups_join_group( self::$group_ids['private'], self::$user_ids['caterpillar'] );

		self::commit_transaction();
	}

	public static function tearDownAfterClass() {
		foreach ( self::$group_ids as $group_id ) {
			groups_delete_group( $group_id );
		}

		foreach ( array_merge( self::$user_ids, array( self::$current_user ) ) as $user_id ) {
			if ( is_multisite() ) {
				wpmu_delete_user( $user_id );
			} else {
				wp_delete_user( $user_id );
			}
		}

		self::commit_transaction();
	}

	public function setUp() {
		parent::setUp();
		$this->set_current_user( self::$current_user );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( self::$old_user_id );
	}

	public function test_suggestions_with_type_members() {
		$suggestions = bp_core_get_suggestions( array(
			'type' => 'members',
			'term' => 'smith',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 3, count( $suggestions ) );  // aardvark, smith, zoom.
	}

	public function test_suggestions_with_type_members_and_limit() {
		$suggestions = bp_core_get_suggestions( array(
			'limit' => 2,
			'type'  => 'members',
			'term'  => 'smith',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 2, count( $suggestions ) );  // two of: aardvark, smith, zoom.
	}

	public function test_suggestions_with_type_members_and_only_friends() {
		$suggestions = bp_core_get_suggestions( array(
			'only_friends' => true,
			'type'         => 'members',
			'term'         => 'smith',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // aardvark.

		$suggestions = bp_core_get_suggestions( array(
			'only_friends' => true,
			'type'         => 'members',
			'term'         => 'cat',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 2, count( $suggestions ) );  // cat, caterpillar.
	}

	public function test_suggestions_with_type_members_and_term_as_displayname() {
		$suggestions = bp_core_get_suggestions( array(
			'type' => 'members',
			'term' => 'aardvark',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // aardvark.
	}

	public function test_suggestions_with_type_members_and_term_as_usernicename() {
		$suggestions = bp_core_get_suggestions( array(
			'type' => 'members',
			'term' => 'eleanor',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // snake.
	}

	public function test_suggestions_with_term_as_current_user() {
		$suggestions = bp_core_get_suggestions( array(
			'type' => 'members',
			'term' => 'katie',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );
		$this->assertSame( 'katie', $suggestions[0]->ID );
	}


	public function test_suggestions_with_type_groupmembers_public() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['public'],
			'type'     => 'members',
			'term'     => 'smith',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 2, count( $suggestions ) );  // aardvark, smith.
	}

	public function test_suggestions_with_type_groupmembers_public_and_limit() {
		$suggestions = bp_core_get_suggestions( array(
			'limit'    => 1,
			'group_id' => self::$group_ids['public'],
			'type'     => 'members',
			'term'     => 'smith',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // one of: aardvark, smith.
	}

	public function test_suggestions_with_type_groupmembers_public_and_only_friends() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id'     => self::$group_ids['public'],
			'only_friends' => true,
			'type'         => 'members',
			'term'         => 'smith',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // aardvark.
	}

	public function test_suggestions_with_type_groupmembers_public_and_term_as_displayname() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['public'],
			'type'     => 'members',
			'term'     => 'aardvark',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // aardvark.
	}

	public function test_suggestions_with_type_groupmembers_public_and_term_as_usernicename() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['public'],
			'type'     => 'members',
			'term'     => 'robert',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // smith.
	}

	public function test_suggestions_with_type_groupmembers_public_as_id() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['public'],
			'type'     => 'members',
			'term'     => 'smith',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 2, count( $suggestions ) );  // aardvark, smith.
	}

	public function test_suggestions_with_type_groupmembers_hidden() {
		// current_user isn't a member of the hidden group
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'pig',
		) );
		$this->assertTrue( is_wp_error( $suggestions ) );

		// "alpaca red" is in the hidden group
		$this->set_current_user( self::$user_ids['alpaca red'] );
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'pig',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // pig
	}

	public function test_suggestions_with_type_groupmembers_private() {
		// current_user isn't a member of the private group.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['private'],
			'type'     => 'members',
			'term'     => 'cat',
		) );
		$this->assertTrue( is_wp_error( $suggestions ) );

		// "caterpillar" is in the private group
		$this->set_current_user( self::$user_ids['caterpillar'] );
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['private'],
			'type'     => 'members',
			'term'     => 'cat',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 2, count( $suggestions ) );  // cat, caterpillar
	}


	public function test_suggestions_with_type_groupmembers_public_and_exclude_group_from_results() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['public'],
			'type'     => 'members',
			'term'     => 'smith',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 2, count( $suggestions ) );  // aardvark, smith.

		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -self::$group_ids['public'],
			'type'     => 'members',
			'term'     => 'smith',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // zoom
	}

	public function test_suggestions_with_type_groupmembers_private_and_exclude_group_from_results() {
		// current_user isn't a member of the private group.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -self::$group_ids['private'],
			'type'     => 'members',
			'term'     => 'cat',
		) );
		$this->assertTrue( is_wp_error( $suggestions ) );


		$this->set_current_user( self::$user_ids['caterpillar'] );

		// "cat" is in the private group, so won't show up here.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -self::$group_ids['private'],
			'type'     => 'members',
			'term'     => 'cat',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEmpty( $suggestions );

		// "zoo" is not the private group, so will show up here.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -self::$group_ids['private'],
			'type'     => 'members',
			'term'     => 'zoo',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // zoo
	}

	public function test_suggestions_with_type_groupmembers_hidden_and_exclude_group_from_results() {
		// current_user isn't a member of the hidden group.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'pig',
		) );
		$this->assertTrue( is_wp_error( $suggestions ) );


		$this->set_current_user( self::$user_ids['alpaca red'] );

		// "alpaca red" is in the hidden group, so won't show up here.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -self::$group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'alpaca red',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEmpty( $suggestions );

		// "zoo" is not the hidden group, so will show up here.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -self::$group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'zoo',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // zoo
	}


	/**
	 * These next tests check the format of the response from the Suggestions API.
	 */

	public function test_suggestions_response_no_matches() {
		$suggestions = bp_core_get_suggestions( array(
			'term' => 'abcdefghijklmnopqrstuvwxyz',
			'type' => 'members',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertInternalType( 'array', $suggestions );
		$this->assertEmpty( $suggestions );
	}

	public function test_suggestions_response_single_match() {
		$suggestion = bp_core_get_suggestions( array(
			'term' => 'zoom',
			'type' => 'members',
		) );

		$this->assertFalse( is_wp_error( $suggestion ) );
		$this->assertInternalType( 'array', $suggestion );
		$this->assertNotEmpty( $suggestion );

		$suggestion = array_shift( $suggestion );

		$this->assertInternalType( 'object', $suggestion );
		$this->assertAttributeNotEmpty( 'image', $suggestion );
		$this->assertAttributeNotEmpty( 'ID', $suggestion );
		$this->assertAttributeNotEmpty( 'name', $suggestion );
	}

	public function test_suggestions_response_multiple_matches() {
		$suggestions = bp_core_get_suggestions( array(
			'term' => 'cat',
			'type' => 'members',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertInternalType( 'array', $suggestions );
		$this->assertNotEmpty( $suggestions );

		foreach ( $suggestions as $suggestion ) {
			$this->assertInternalType( 'object', $suggestion );
			$this->assertAttributeNotEmpty( 'image', $suggestion );
			$this->assertAttributeNotEmpty( 'ID', $suggestion );
			$this->assertAttributeNotEmpty( 'name', $suggestion );
		}
	}

	public function test_suggestions_term_is_case_insensitive() {
		$lowercase = bp_core_get_suggestions( array(
			'term' => 'lisa',
			'type' => 'members',
		) );
		$this->assertFalse( is_wp_error( $lowercase ) );
		$this->assertEquals( 1, count( $lowercase ) );

		$uppercase = bp_core_get_suggestions( array(
			'term' => 'LISA',
			'type' => 'members',
		) );
		$this->assertFalse( is_wp_error( $uppercase ) );
		$this->assertEquals( 1, count( $uppercase ) );

		$this->assertSame( $lowercase[0]->ID, $uppercase[0]->ID );
		$this->assertSame( 'zoom', $lowercase[0]->ID );
	}

	public function test_suggestions_response_property_types() {
		$suggestion = bp_core_get_suggestions( array(
			'term' => 'zoom',
			'type' => 'members',
		) );

		$this->assertFalse( is_wp_error( $suggestion ) );
		$this->assertInternalType( 'array', $suggestion );
		$this->assertNotEmpty( $suggestion );

		$suggestion = array_shift( $suggestion );

		$this->assertInternalType( 'object', $suggestion );
		$this->assertAttributeInternalType( 'string', 'image', $suggestion );
		$this->assertAttributeInternalType( 'string', 'ID', $suggestion );
		$this->assertAttributeInternalType( 'string', 'name', $suggestion );
	}


	/**
	 * Tests below this point are expected to fail.
	 */

	public function test_suggestions_with_bad_type() {
		$suggestions = bp_core_get_suggestions( array(
			'type' => 'fake_type',
		) );

		$this->assertTrue( is_wp_error( $suggestions ) );
	}

	public function test_suggestions_with_type_groupmembers_and_bad_group_ids() {
		// group_ids can't be a group slug.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => 'fake-group-slug',
			'type'     => 'members',
		) );

		$this->assertTrue( is_wp_error( $suggestions ) );
	}

	public function test_suggestions_with_bad_term() {
		// a non-empty term is mandatory
		$suggestions = bp_core_get_suggestions( array(
			'term' => '',
			'type' => 'members',
		) );

		$this->assertTrue( is_wp_error( $suggestions ) );
	}
}
