<?php
/**
 * @group core
 * @group optouts
 */
 class BP_Tests_Optouts extends BP_UnitTestCase {

	 /**
	  * @ticket BP8552
	  */
	 public function test_bp_optouts_query_cache_results() {
		 global $wpdb;

		 self::factory()->optout->create_many( 2 );

		 // Reset.
		 $wpdb->num_queries = 0;

		 $first_query = BP_Optout::get(
			 array( 'cache_results' => true )
		 );

		 $queries_before = get_num_queries();

		 $second_query = BP_Optout::get(
			 array( 'cache_results' => false )
		 );

		 $queries_after = get_num_queries();

		 $this->assertNotSame( $queries_before, $queries_after, 'Assert that queries are run' );
		 $this->assertSame( 3, $queries_after, 'Assert that the uncached query was run' );
		 $this->assertEquals( $first_query, $second_query, 'Results of the query are expected to match.' );
	 }

	public function test_bp_optouts_add_optout_vanilla() {
		$old_current_user = get_current_user_id();

		$u1 = self::factory()->user->create();
		wp_set_current_user( $u1 );

		// Create a couple of optouts.
		$args = array(
			'email_address'     => 'one@wp.org',
			'user_id'           => $u1,
			'email_type'        => 'annoyance'
		);
		$i1 = self::factory()->optout->create( $args );
		$args['email_address'] = 'two@wp.org';
		$i2 = self::factory()->optout->create( $args );

		$get_args = array(
			'user_id'        => $u1,
			'fields'         => 'ids',
		);
		$optouts = bp_get_optouts( $get_args );
		$this->assertEqualSets( array( $i1, $i2 ), $optouts );

		wp_set_current_user( $old_current_user );
	}

	public function test_bp_optouts_add_optout_avoid_duplicates() {
		$old_current_user = get_current_user_id();

		$u1 = self::factory()->user->create();
		wp_set_current_user( $u1 );

		// Create an optouts.
		$args = array(
			'email_address'     => 'one@wp.org',
			'user_id'           => $u1,
			'email_type'        => 'annoyance'
		);
		$i1 = bp_add_optout( $args );
		// Attempt to create a duplicate. Should return existing optout id.
		$i2 = bp_add_optout( $args );
		$this->assertEquals( $i1, $i2 );

		wp_set_current_user( $old_current_user );
	}

	public function test_bp_optouts_delete_optout() {
		$old_current_user = get_current_user_id();

		$u1 = self::factory()->user->create();
		wp_set_current_user( $u1 );

		$args = array(
			'email_address'     => 'one@wp.org',
			'user_id'           => $u1,
			'email_type'        => 'annoyance'
		);
		$i1 = self::factory()->optout->create( $args );
		bp_delete_optout_by_id( $i1 );

		$get_args = array(
			'user_id'        => $u1,
			'fields'         => 'ids',
		);
		$optouts = bp_get_optouts( $get_args );
		$this->assertEmpty( $optouts );

		wp_set_current_user( $old_current_user );
	}

	public function test_bp_optouts_get_by_search_terms() {
		$old_current_user = get_current_user_id();

		$u1 = self::factory()->user->create();
		wp_set_current_user( $u1 );

		// Create a couple of optouts.
		$args = array(
			'email_address'     => 'one@wpfrost.org',
			'user_id'           => $u1,
			'email_type'        => 'annoyance'
		);
		$i1 = self::factory()->optout->create( $args );
		$args['email_address'] = 'two@wp.org';
		self::factory()->optout->create( $args );

		$get_args = array(
			'search_terms'   => 'one@wpfrost.org',
			'fields'         => 'ids',
		);
		$optouts = bp_get_optouts( $get_args );
		$this->assertEqualSets( array( $i1 ), $optouts );

		wp_set_current_user( $old_current_user );
	}

	public function test_bp_optouts_get_by_email_address_mismatched_case() {
		$old_current_user = get_current_user_id();

		$u1 = self::factory()->user->create();
		wp_set_current_user( $u1 );

		// Create a couple of optouts.
		$args = array(
			'email_address'     => 'ONE@wpfrost.org',
			'user_id'           => $u1,
			'email_type'        => 'annoyance'
		);
		$i1 = self::factory()->optout->create( $args );
		$args['email_address'] = 'two@WP.org';
		self::factory()->optout->create( $args );

		$get_args = array(
			'email_address'  => 'one@WPfrost.org',
			'fields'         => 'ids',
		);
		$optouts = bp_get_optouts( $get_args );
		$this->assertEqualSets( array( $i1 ), $optouts );

		wp_set_current_user( $old_current_user );
	}

	public function test_bp_optouts_get_by_search_terms_mismatched_case() {
		$old_current_user = get_current_user_id();

		$u1 = self::factory()->user->create();
		wp_set_current_user( $u1 );

		// Create a couple of optouts.
		$args = array(
			'email_address'     => 'ONE@wpfrost.org',
			'user_id'           => $u1,
			'email_type'        => 'annoyance'
		);
		$i1 = self::factory()->optout->create( $args );
		$args['email_address'] = 'two@WP.org';
		self::factory()->optout->create( $args );

		$get_args = array(
			'search_terms'   => 'one@wpfrost.org',
			'fields'         => 'ids',
		);
		$optouts = bp_get_optouts( $get_args );
		$this->assertEqualSets( array( $i1 ), $optouts );

		wp_set_current_user( $old_current_user );
	}


	public function test_bp_optouts_get_by_email_address_mismatched_case_after_update() {
		$old_current_user = get_current_user_id();

		$u1 = self::factory()->user->create();
		wp_set_current_user( $u1 );

		// Create an opt-out.
		$args = array(
			'email_address'     => 'ONE@wpfrost.org',
			'user_id'           => $u1,
			'email_type'        => 'annoyance'
		);
		$i1 = self::factory()->optout->create( $args );
		// Update it.
		$oo_class                = new BP_Optout( $i1 );
		$oo_class->email_address = 'One@wpFrost.org';
		$oo_class->save();

		$get_args = array(
			'email_address'  => 'one@WPfrost.org',
			'fields'         => 'ids',
		);
		$optouts = bp_get_optouts( $get_args );
		$this->assertEqualSets( array( $i1 ), $optouts );

		wp_set_current_user( $old_current_user );
	}

	public function test_bp_optout_prevents_bp_email_send() {
		$old_current_user = get_current_user_id();

		$u1 = self::factory()->user->create();
		wp_set_current_user( $u1 );
		// Create an opt-out.
		$args = array(
			'email_address'     => 'test2@example.com',
			'user_id'           => $u1,
			'email_type'        => 'annoyance'
		);
		self::factory()->optout->create( $args );
		$email = new BP_Email( 'activity-at-message' );
		$email->set_from( 'test1@example.com' )->set_to( 'test2@example.com' )->set_subject( 'testing' );
		$email->set_content_html( 'testing' )->set_tokens( array( 'poster.name' => 'example' ) );

		$this->assertTrue( is_wp_error( $email->validate() ) );
		wp_set_current_user( $old_current_user );
	}
}
