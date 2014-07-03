<?php
/**
 * Suggestions API tests specifically for non-authenticated (anonymous) users.
 *
 * @group api
 * @group suggestions
 */
class BP_Tests_Suggestions_Non_Authenticated extends BP_UnitTestCase {
	protected $group_ids    = array();
	protected $group_slugs  = array();
	protected $user_ids     = array();

	public function setUp() {
		parent::setUp();

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
		foreach( $users as $user ) {
			$this->user_ids[ $user[0] ] = $this->create_user( array(
				'display_name' => $user[1],
				'user_login'   => $user[0],
			) );
		}

		$this->group_slugs['hidden']  = 'the-maw';
		$this->group_slugs['public']  = 'the-great-journey';
		$this->group_slugs['private'] = 'tsavo-highway';

		// Create dummy groups.
		$this->group_ids['hidden'] = $this->factory->group->create( array(
			'creator_id' => $this->user_ids['xylo'],
			'slug'       => $this->group_slugs['hidden'],
			'status'     => 'hidden',
		) );
		$this->group_ids['public'] = $this->factory->group->create( array(
			'creator_id' => $this->user_ids['xylo'],
			'slug'       => $this->group_slugs['public'],
			'status'     => 'public',
		) );
		$this->group_ids['private'] = $this->factory->group->create( array(
			'creator_id' => $this->user_ids['xylo'],
			'slug'       => $this->group_slugs['private'],
			'status'     => 'private',
		) );

		// Add dummy users to dummy hidden groups.
		groups_join_group( $this->group_ids['hidden'], $this->user_ids['pig'] );
		groups_join_group( $this->group_ids['hidden'], $this->user_ids['alpaca red'] );

		// Add dummy users to dummy public groups.
		groups_join_group( $this->group_ids['public'], $this->user_ids['aardvark'] );
		groups_join_group( $this->group_ids['public'], $this->user_ids['alpaca red'] );
		groups_join_group( $this->group_ids['public'], $this->user_ids['cat'] );
		groups_join_group( $this->group_ids['public'], $this->user_ids['smith'] );

		// Add dummy users to dummy private groups.
		groups_join_group( $this->group_ids['private'], $this->user_ids['cat'] );
		groups_join_group( $this->group_ids['private'], $this->user_ids['caterpillar'] );
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
			'group_id'     => $this->group_ids['public'],
			'only_friends' => true,
			'type'         => 'members',
			'term'         => 'smith',
		) );

		$this->assertTrue( is_wp_error( $suggestions ) );
	}

	public function test_suggestions_with_type_groupmembers_hidden() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => $this->group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'pig',
		) );

		$this->assertTrue( is_wp_error( $suggestions ) );
	}

	public function test_suggestions_with_type_groupmembers_private() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => $this->group_ids['private'],
			'type'     => 'members',
			'term'     => 'cat',
		) );

		$this->assertTrue( is_wp_error( $suggestions ) );
	}

	public function test_suggestions_with_type_groupmembers_public_and_exclude_group_from_results() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => $this->group_ids['public'],
			'type'     => 'members',
			'term'     => 'smith',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 2, count( $suggestions ) );  // aardvark, smith.

		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -$this->group_ids['public'],
			'type'     => 'members',
			'term'     => 'smith',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // zoom
	}

	public function test_suggestions_with_type_groupmembers_private_and_exclude_group_from_results() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -$this->group_ids['private'],
			'type'     => 'members',
			'term'     => 'cat',
		) );
		$this->assertTrue( is_wp_error( $suggestions ) );  // no access to group.
	}

	public function test_suggestions_with_type_groupmembers_hidden_and_exclude_group_from_results() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => $this->group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'pig',
		) );
		$this->assertTrue( is_wp_error( $suggestions ) );  // no access to group.
	}
}