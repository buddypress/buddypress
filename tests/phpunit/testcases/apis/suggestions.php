<?php
/**
 * Suggestions API tests for authenticated (logged in) users.
 *
 * @group api
 * @group suggestions
 */
class BP_Tests_Suggestions_Authenticated extends BP_UnitTestCase {
	protected $current_user = null;
	protected $group_ids    = array();
	protected $group_slugs  = array();
	protected $old_user_id  = 0;
	protected $user_ids     = array();

	public function setUp() {
		parent::setUp();

		$this->old_user_id  = get_current_user_id();
		$this->current_user = $this->create_user( array(
			'display_name' => 'Katie Parker',
			'user_login'   => 'katie',
		) );

		$this->set_current_user( $this->current_user );

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
		foreach ( $users as $user ) {
			$this->user_ids[ $user[0] ] = $this->create_user( array(
				'display_name' => $user[1],
				'user_login'   => $user[0],
			) );
		}

		// Create some dummy friendships.
		friends_add_friend( $this->current_user, $this->user_ids['aardvark'], true );
		friends_add_friend( $this->current_user, $this->user_ids['cat'], true );
		friends_add_friend( $this->current_user, $this->user_ids['caterpillar'], true );
		friends_add_friend( $this->current_user, $this->user_ids['pig'], true );

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
		groups_join_group( $this->group_ids['public'], $this->current_user );
		groups_join_group( $this->group_ids['public'], $this->user_ids['aardvark'] );
		groups_join_group( $this->group_ids['public'], $this->user_ids['alpaca red'] );
		groups_join_group( $this->group_ids['public'], $this->user_ids['cat'] );
		groups_join_group( $this->group_ids['public'], $this->user_ids['smith'] );

		// Add dummy users to dummy private groups.
		groups_join_group( $this->group_ids['private'], $this->user_ids['cat'] );
		groups_join_group( $this->group_ids['private'], $this->user_ids['caterpillar'] );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_user_id );
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
			'group_id' => $this->group_ids['public'],
			'type'     => 'members',
			'term'     => 'smith',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 2, count( $suggestions ) );  // aardvark, smith.
	}

	public function test_suggestions_with_type_groupmembers_public_and_limit() {
		$suggestions = bp_core_get_suggestions( array(
			'limit'    => 1,
			'group_id' => $this->group_ids['public'],
			'type'     => 'members',
			'term'     => 'smith',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // one of: aardvark, smith.
	}

	public function test_suggestions_with_type_groupmembers_public_and_only_friends() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id'     => $this->group_ids['public'],
			'only_friends' => true,
			'type'         => 'members',
			'term'         => 'smith',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // aardvark.
	}

	public function test_suggestions_with_type_groupmembers_public_and_term_as_displayname() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => $this->group_ids['public'],
			'type'     => 'members',
			'term'     => 'aardvark',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // aardvark.
	}

	public function test_suggestions_with_type_groupmembers_public_and_term_as_usernicename() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => $this->group_ids['public'],
			'type'     => 'members',
			'term'     => 'robert',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // smith.
	}

	public function test_suggestions_with_type_groupmembers_public_as_id() {
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => $this->group_ids['public'],
			'type'     => 'members',
			'term'     => 'smith',
		) );

		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 2, count( $suggestions ) );  // aardvark, smith.
	}

	public function test_suggestions_with_type_groupmembers_hidden() {
		// current_user isn't a member of the hidden group
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => $this->group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'pig',
		) );
		$this->assertTrue( is_wp_error( $suggestions ) );

		// "alpaca red" is in the hidden group
		$this->set_current_user( $this->user_ids['alpaca red'] );
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => $this->group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'pig',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // pig
	}

	public function test_suggestions_with_type_groupmembers_private() {
		// current_user isn't a member of the private group.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => $this->group_ids['private'],
			'type'     => 'members',
			'term'     => 'cat',
		) );
		$this->assertTrue( is_wp_error( $suggestions ) );

		// "caterpillar" is in the private group
		$this->set_current_user( $this->user_ids['caterpillar'] );
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => $this->group_ids['private'],
			'type'     => 'members',
			'term'     => 'cat',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 2, count( $suggestions ) );  // cat, caterpillar
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
		// current_user isn't a member of the private group.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -$this->group_ids['private'],
			'type'     => 'members',
			'term'     => 'cat',
		) );
		$this->assertTrue( is_wp_error( $suggestions ) );


		$this->set_current_user( $this->user_ids['caterpillar'] );

		// "cat" is in the private group, so won't show up here.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -$this->group_ids['private'],
			'type'     => 'members',
			'term'     => 'cat',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEmpty( $suggestions );

		// "zoo" is not the private group, so will show up here.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -$this->group_ids['private'],
			'type'     => 'members',
			'term'     => 'zoo',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEquals( 1, count( $suggestions ) );  // zoo
	}

	public function test_suggestions_with_type_groupmembers_hidden_and_exclude_group_from_results() {
		// current_user isn't a member of the hidden group.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => $this->group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'pig',
		) );
		$this->assertTrue( is_wp_error( $suggestions ) );


		$this->set_current_user( $this->user_ids['alpaca red'] );

		// "alpaca red" is in the hidden group, so won't show up here.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -$this->group_ids['hidden'],
			'type'     => 'members',
			'term'     => 'alpaca red',
		) );
		$this->assertFalse( is_wp_error( $suggestions ) );
		$this->assertEmpty( $suggestions );

		// "zoo" is not the hidden group, so will show up here.
		$suggestions = bp_core_get_suggestions( array(
			'group_id' => -$this->group_ids['hidden'],
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