<?php

/**
 * @group members
 * @group member_types
 */
class BP_Tests_Members_Types extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		buddypress()->members->types = array();
	}

	public function test_bp_register_member_type_should_fail_for_existing_member_type() {
		bp_register_member_type( 'foo' );
		$this->assertWPError( bp_register_member_type( 'foo' ) );
	}

	public function test_bp_register_member_type_should_return_type_object() {
		$this->assertInternalType( 'object', bp_register_member_type( 'foo' ) );
	}

	/**
	 * @ticket BP6139
	 */
	public function test_bp_register_member_type_should_sanitize_member_type_key() {
		$key = 'F//oo% -Bar';
		$sanitized_key = 'foo-bar';

		$object = bp_register_member_type( $key );
		$this->assertSame( $sanitized_key, $object->name );
	}

	public function test_bp_register_member_type_should_store_member_type_string_as_name_property() {
		$object = bp_register_member_type( 'foo' );
		$this->assertSame( 'foo', $object->name );
	}

	public function test_bp_register_member_type_should_fill_in_missing_labels_with_ucfirst_member_type() {
		$object = bp_register_member_type( 'foo' );
		foreach ( $object->labels as $label ) {
			$this->assertSame( 'Foo', $label );
		}
	}

	/**
	 * @ticket BP6125
	 */
	public function test_bp_register_member_type_should_respect_custom_name_label() {
		$object = bp_register_member_type( 'foo', array(
			'labels' => array(
				'name' => 'Bar',
			),
		) );

		// 'singular_name' falls back on 'name'.
		$this->assertSame( 'Bar', $object->labels['name'] );
		$this->assertSame( 'Bar', $object->labels['singular_name'] );
	}

	/**
	 * @ticket BP6125
	 */
	public function test_bp_register_member_type_should_respect_custom_singular_name_label() {
		$object = bp_register_member_type( 'foo', array(
			'labels' => array(
				'singular_name' => 'Bar',
			),
		) );

		// 'name' is set to upper-case version of member type name.
		$this->assertSame( 'Foo', $object->labels['name'] );
		$this->assertSame( 'Bar', $object->labels['singular_name'] );
	}

	public function test_bp_get_member_type_object_should_return_null_for_non_existent_member_type() {
		$this->assertSame( null, bp_get_member_type_object( 'foo' ) );
	}

	public function test_bp_get_member_type_object_should_return_type_object() {
		bp_register_member_type( 'foo' );
		$this->assertInternalType( 'object', bp_get_member_type_object( 'foo' ) );
	}

	public function test_bp_set_member_type_should_return_false_for_invalid_member_type() {
		$this->assertFalse( bp_set_member_type( 'foo', 1 ) );
	}

	public function test_bp_set_member_type_should_remove_member_type_when_passing_an_empty_value() {
		$u = $this->factory->user->create();
		bp_register_member_type( 'foo' );
		bp_set_member_type( $u, 'foo' );

		// Make sure it's set up.
		$this->assertSame( 'foo', bp_get_member_type( $u ) );

		$this->assertSame( array(), bp_set_member_type( $u, '' ) );
		$this->assertFalse( bp_get_member_type( $u ) );
	}

	public function test_bp_set_member_type_success() {
		$u = $this->factory->user->create();
		bp_register_member_type( 'foo' );

		$this->assertNotEmpty( bp_set_member_type( $u, 'foo' ) );
	}

	public function test_bp_get_member_type_with_default_value_for_single() {
		$u = $this->factory->user->create();
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		bp_set_member_type( $u, 'foo' );
		bp_set_member_type( $u, 'bar', true );

		$this->assertSame( 'foo', bp_get_member_type( $u ) );
	}

	public function test_bp_get_member_type_with_single_true() {
		$u = $this->factory->user->create();
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		bp_set_member_type( $u, 'foo' );
		bp_set_member_type( $u, 'bar', true );

		$this->assertSame( 'foo', bp_get_member_type( $u, true ) );
	}

	public function test_bp_get_member_type_with_single_false() {
		$u = $this->factory->user->create();
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		bp_set_member_type( $u, 'foo' );
		bp_set_member_type( $u, 'bar', true );

		$this->assertEqualSets( array( 'foo', 'bar' ), bp_get_member_type( $u, false ) );
	}

	public function test_bp_get_member_type_should_return_false_when_no_value_is_found() {
		$u = $this->factory->user->create();
		bp_register_member_type( 'foo' );

		$this->assertFalse( bp_get_member_type( $u ) );
	}

	/**
	 * @group cache
	 */
	public function test_bp_get_member_type_should_hit_cache() {
		$u = $this->factory->user->create();
		bp_register_member_type( 'foo' );
		bp_set_member_type( $u, 'foo' );

		global $wpdb;

		// Initial query should hit the database.
		bp_get_member_type( $u );
		$num_queries = $wpdb->num_queries;

		// Next query should not hit the database.
		bp_get_member_type( $u );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group BP6193
	 */
	public function test_bp_members_prefetch_member_type_array_cache_set() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		bp_set_member_type( $u1, 'foo' );
		bp_set_member_type( $u1, 'bar', true );

		// Get users so that the 'bp_user_query_populate_extras' is fired
		// and members type prefetched
		$users = bp_core_get_users( array( 'include' => array( $u1, $u2 ) ) );

		// Get single member type
		$this->assertSame( 'foo', bp_get_member_type( $u1, true ) );
		$this->assertEmpty( bp_get_member_type( $u2, true ) );

		// Get all member types for the user
		$this->assertEqualSets( array( 'foo', 'bar' ), bp_get_member_type( $u1, false ) );
		$this->assertEmpty( bp_get_member_type( $u2, false ) );
	}

	/**
	 * @group cache
	 */
	public function test_bp_get_member_type_should_return_false_for_deleted_user() {
		$u = $this->factory->user->create();
		bp_register_member_type( 'foo' );
		bp_set_member_type( $u, 'foo' );

		// Prime the cache.
		bp_get_member_type( $u );

		if ( is_multisite() ) {
			wpmu_delete_user( $u );
		} else {
			wp_delete_user( $u );
		}

		$this->assertFalse( wp_cache_get( $u, 'bp_member_type' ) );
		$this->assertFalse( bp_get_member_type( $u ) );
	}
}
