<?php

/**
 * @group core
 * @group BP_Button
 */
class BP_Tests_BP_Button extends BP_UnitTestCase {
	/**
	 * @group block_self
	 */
	public function test_block_self_own_profile() {
		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		$this->go_to( bp_core_get_user_domain( $u ) );

		$b = new BP_Button( array(
			'id' => 'foo',
			'component' => 'members',
			'block_self' => true,
		) );

		$this->assertEquals( '', $b->contents );
	}

	/**
	 * @group block_self
	 */
	public function test_block_self_others_profile() {
		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$u2 = $this->factory->user->create();
		$this->go_to( bp_core_get_user_domain( $u2 ) );

		$b = new BP_Button( array(
			'id' => 'foo',
			'component' => 'members',
			'block_self' => true,
		) );

		$this->assertNotEmpty( $b->contents );
	}

	/**
	 * @group block_self
	 */
	public function test_block_self_inside_members_loop() {
		$now = time();
		$u1 = $this->factory->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = $this->factory->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		$this->set_current_user( $u1 );

		$found = array();
		if ( bp_has_members() ) {
			while ( bp_members() ) {
				bp_the_member();

				$b = new BP_Button( array(
					'id' => 'foo',
					'component' => 'members',
					'block_self' => true,
				) );

				$found[ bp_get_member_user_id() ] = empty( $b->contents );
			}
		}

		$expected = array(
			$u1 => true,
			$u2 => false,
		);

		$this->assertSame( $expected, $found );

		// clean up
		$GLOBALS['members_template'] = null;
	}

	/**
	 * @group block_self
	 */
	public function test_block_self_false_inside_members_loop() {
		$now = time();
		$u1 = $this->factory->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = $this->factory->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		$this->set_current_user( $u1 );

		$found = array();
		if ( bp_has_members() ) {
			while ( bp_members() ) {
				bp_the_member();

				$b = new BP_Button( array(
					'id' => 'foo',
					'component' => 'members',
					'block_self' => false,
				) );

				$found[ bp_get_member_user_id() ] = empty( $b->contents );
			}
		}

		$expected = array(
			$u1 => false,
			$u2 => false,
		);

		$this->assertSame( $expected, $found );

		// clean up
		$GLOBALS['members_template'] = null;
	}

	/**
	 * @group block_self
	 */
	public function test_block_self_inside_members_loop_on_my_profile_page() {
		$now = time();
		$u1 = $this->factory->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = $this->factory->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		$this->set_current_user( $u1 );
		$this->go_to( bp_core_get_user_domain( $u1 ) );

		$found = array();
		if ( bp_has_members() ) {
			while ( bp_members() ) {
				bp_the_member();

				$b = new BP_Button( array(
					'id' => 'foo',
					'component' => 'members',
					'block_self' => true,
				) );

				$found[ bp_get_member_user_id() ] = empty( $b->contents );
			}
		}

		$expected = array(
			$u1 => true,
			$u2 => false,
		);

		$this->assertSame( $expected, $found );

		// clean up
		$GLOBALS['members_template'] = null;
	}
}
