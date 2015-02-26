<?php
/**
 * Suggestions API tests specifically for non-authenticated (anonymous) users.
 *
 * @group api
 * @group suggestions
 */
class BP_Tests_Suggestions_Non_Authenticated extends BP_UnitTestCase {
	protected static $group_ids    = array();
	protected static $group_slugs  = array();
	protected static $user_ids     = array();

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

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

		$factory = new BP_UnitTest_Factory();

		// Create some dummy users.
		foreach( $users as $user_index => $user ) {
			$new_user = $factory->user->create( array(
				'display_name' => $user[1],
				'user_login'   => $user[0],
				'user_email'   => "test-$user_index@example.com",
			) );

			self::$user_ids[ $user[0] ] = $new_user;
		}

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

		foreach ( self::$user_ids as $user_id ) {
			if ( is_multisite() ) {
				wpmu_delete_user( $user_id );
			} else {
				wp_delete_user( $user_id );
			}
		}

		self::commit_transaction();
	}

	/**
	 * Tests below this point are expected to fail.
	 */

	public function test_suggestions_with_type_members_and_only_friends() {
		// only_friends requires authenticated requests
		$suggestions = bp_core_get_suggestions( array(
			'only_friends' => true,
			'type'         => 'members',
			'term'         => 'smith',
		) );

		$this->assertTrue( is_wp_error( $suggestions ) );
	}

	public function test_suggestions_with_type_groupmembers_and_only_friends() {
		// only_friends requires authenticated requests
		$suggestions = bp_core_get_suggestions( array(
			'group_id'     => self::$group_ids['public'],
			'only_friends' => true,
			'type'         => 'members',
			'term'         => 'smith',
		) );

		$this->assertTrue( is_wp_error( $suggestions ) );
	}

	public function test_suggestions_with_type_groupmembers_hidden() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'pig',
		) );

		$this->assertTrue( is_wp_error( $suggestions ) );
	}

	public function test_suggestions_with_type_groupmembers_private() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['private'],
			'type'     => 'members',
			'term'     => 'cat',
		) );

		$this->assertTrue( is_wp_error( $suggestions ) );
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
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -self::$group_ids['private'],
			'type'     => 'members',
			'term'     => 'cat',
		) );
		$this->assertTrue( is_wp_error( $suggestions ) );  // no access to group.
	}

	public function test_suggestions_with_type_groupmembers_hidden_and_exclude_group_from_results() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => self::$group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'pig',
		) );
		$this->assertTrue( is_wp_error( $suggestions ) );  // no access to group.
	}
}
