<?php

/**
 * @group core
 */
class BP_Tests_BP_Button extends BP_UnitTestCase {
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
	}

	public function tear_down() {
		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	public function test_block_self_own_profile() {
		$u = self::factory()->user->create();
		wp_set_current_user( $u );
		$this->set_permalink_structure( '/%postname%/' );

		$this->go_to( bp_members_get_user_url( $u ) );

		$b = new BP_Button( array(
			'id' => 'foo',
			'component' => 'members',
			'block_self' => true,
		) );

		$this->assertSame( '', $b->contents );
	}

	public function test_block_self_others_profile() {
		$u1 = self::factory()->user->create();
		wp_set_current_user( $u1 );
		$this->set_permalink_structure( '/%postname%/' );

		$u2 = self::factory()->user->create();
		$this->go_to( bp_members_get_user_url( $u2 ) );

		$b = new BP_Button( array(
			'id' => 'foo',
			'component' => 'members',
			'block_self' => true,
		) );

		$this->assertNotEmpty( $b->contents );
	}

	public function test_block_self_inside_members_loop() {
		$now = time();
		$u1 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		wp_set_current_user( $u1 );

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

	public function test_block_self_false_inside_members_loop() {
		$now = time();
		$u1 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		wp_set_current_user( $u1 );

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

	public function test_block_self_inside_members_loop_on_my_profile_page() {
		$now = time();
		$u1 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now ),
		) );
		$u2 = self::factory()->user->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		wp_set_current_user( $u1 );
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to( bp_members_get_user_url( $u1 ) );

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
	 * @see https://buddypress.trac.wordpress.org/ticket/7226
	 */
	public function test_bp_button_new_args() {
		$b = new BP_Button( array(
			'id' => 'foo',
			'component' => 'members',
			'block_self' => false,
			'must_be_logged_in' => false,
			'parent_element' => 'section',
			'parent_attr' => array(
				'class' => 'section-class',
				'id' => 'section-id',
				'data-parent' => 'foo',
			),
			'button_element' => 'button',
			'button_attr' => array(
				'autofocus' => 'autofocus',
				'type' => 'submit',
				'name' => 'my-button'
			)
		) );

		$this->assertStringContainsString( '<section ', $b->contents );
		$this->assertStringContainsString( 'class="section-class ', $b->contents );
		$this->assertStringContainsString( 'id="section-id"', $b->contents );
		$this->assertStringContainsString( 'data-parent="foo"', $b->contents );
		$this->assertStringContainsString( '<button ', $b->contents );
		$this->assertStringContainsString( 'autofocus="autofocus"', $b->contents );
		$this->assertStringContainsString( 'type="submit"', $b->contents );
		$this->assertStringContainsString( 'name="my-button"', $b->contents );
	}





	/**
	 * @see https://buddypress.trac.wordpress.org/ticket/7226
	 */
	public function test_bp_button_new_element_attrs_should_not_render_for_empty_attrs() {
		$b = new BP_Button( array(
			'id' => 'foo',
			'component' => 'members',
			'block_self' => false,
			'must_be_logged_in' => false,
			'button_element' => 'button',
			'button_attr' => array(
				'class' => '',
			),
		) );

		$this->assertStringNotContainsString( '<button class=""', $b->contents );
	}
}
