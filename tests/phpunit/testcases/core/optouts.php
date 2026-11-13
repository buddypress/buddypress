<?php
/**
 * @group core
 */
 class BP_Tests_Optouts extends BP_UnitTestCase {

	 /**
	  * @see https://buddypress.trac.wordpress.org/ticket/8552
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

		 $first_query  = wp_list_sort( $first_query, 'id', 'ASC' );
		 $second_query = wp_list_sort( $second_query, 'id', 'ASC' );

		 foreach ( array_merge( $first_query, $second_query ) as $optout ) {
			 $this->assertInstanceOf( 'BP_Optout', $optout );
		 }

		 $this->assertSame(
			 array_map( 'get_object_vars', $first_query ),
			 array_map( 'get_object_vars', $second_query ),
			 'Results of the query are expected to match.'
		 );
	 }

	public function test_self_removing_sql_clauses_filter_with_duplicate_join_at_priority_zero() {
		$allowed = self::factory()->optout->create();
		$other   = self::factory()->optout->create();

		$table   = BP_Optout::get_table_name();

		$calls  = 0;
		$filter = static function( $clauses ) use ( $allowed, $table, &$calls, &$filter ) {
			remove_filter( 'bp_optouts_get_sql_clauses', $filter, 0 );
			++$calls;
			$clauses['from'] = "FROM {$table} o";
			$clauses['join'] .= " INNER JOIN (SELECT {$allowed} AS allowed_id UNION ALL SELECT {$allowed}) access ON access.allowed_id = o.id";
			$clauses['where_conditions']['allowed'] = "access.allowed_id = {$allowed}";

			if ( 'SELECT COUNT(*)' === $clauses['select'] ) {
				$clauses['select'] = 'SELECT COUNT(DISTINCT o.id)';
			}

			return $clauses;
		};

		$args = array( 'fields' => 'ids', 'cache_results' => true, 'per_page' => 1, 'page' => 1 );

		add_filter( 'bp_optouts_get_sql_clauses', $filter, 0 );

		try {
			$found = BP_Optout::get( $args );

			add_filter( 'bp_optouts_get_sql_clauses', $filter, 0 );
			$total = BP_Optout::get_total_count( array() );

			add_filter( 'bp_optouts_get_sql_clauses', $filter, 0 );
			$args['page'] = 2;
			$second_page = BP_Optout::get( $args );
		} finally {
			remove_filter( 'bp_optouts_get_sql_clauses', $filter, 0 );
		}

		$this->assertSame( array( $allowed ), $found );
		$this->assertSame( 1, (int) $total );
		$this->assertSame( array(), $second_page );
		$this->assertSame( 3, $calls );
		$this->assertSameSets( array( $allowed, $other ), BP_Optout::get( array( 'fields' => 'ids', 'cache_results' => true ) ) );
	}

	public function test_get_sql_filters() {
		$optout_id = self::factory()->optout->create(
			array(
				'email_address' => 'one@wp.org',
				'user_id'       => self::factory()->user->create(),
			)
		);
		self::factory()->optout->create(
			array(
				'email_address' => 'two@wp.org',
				'user_id'       => self::factory()->user->create(),
			)
		);

		$query_types = array();
		$clauses_filter = static function ( $sql_clauses ) use ( $optout_id ) {
			$sql_clauses['where_conditions'][] = "id = {$optout_id}";

			return $sql_clauses;
		};

		$sql_filter = static function ( $sql, $query_type ) use ( &$query_types ) {
			$query_types[] = $query_type;

			return $sql;
		};

		add_filter( 'bp_optouts_get_sql_clauses', $clauses_filter );
		add_filter( 'bp_optouts_get_sql', $sql_filter, 10, 2 );

		try {
			$optout_ids = BP_Optout::get(
				array(
					'cache_results' => false,
					'fields'        => 'ids',
				)
			);
			$total = BP_Optout::get_total_count( array() );
		} finally {
			remove_filter( 'bp_optouts_get_sql_clauses', $clauses_filter );
			remove_filter( 'bp_optouts_get_sql', $sql_filter );
		}

		$this->assertSame( array( $optout_id ), $optout_ids );
		$this->assertSame( 1, (int) $total );
		$this->assertSame( array( 'paged', 'count' ), $query_types );
	}



	public function test_bp_optouts_add_optout_vanilla() {

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

	}

	public function test_bp_optouts_add_optout_avoid_duplicates() {

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
		$this->assertSame( $i1, $i2 );

	}

	public function test_bp_optouts_delete_optout() {

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

	}

	public function test_bp_optouts_get_by_search_terms() {

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

	}

	public function test_bp_optouts_get_by_email_address_mismatched_case() {

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

	}

	public function test_bp_optouts_get_by_search_terms_mismatched_case() {

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

	}


	public function test_bp_optouts_get_by_email_address_mismatched_case_after_update() {

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

	}

	public function test_bp_optout_prevents_bp_email_send() {

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

		$this->assertWPError( $email->validate() );
	}
}
