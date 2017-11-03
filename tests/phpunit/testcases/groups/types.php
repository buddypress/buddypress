<?php

/**
 * @group groups
 * @group group_types
 */
class BP_Tests_Groups_Types extends BP_UnitTestCase {
	protected static $u1 = null;

	public function setUp() {
		parent::setUp();

		buddypress()->groups->types = array();
	}

	public static function wpSetUpBeforeClass( $f ) {
		self::$u1 = $f->user->create( array(
			'user_email' => 'group-types-tests@example.com',
			'user_login' => 'grouptypestests',
		) );
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$u1 );
	}

	public function test_groups_register_type_should_fail_for_existing_group_type() {
		bp_groups_register_group_type( 'foo' );
		$this->assertWPError( bp_groups_register_group_type( 'foo' ) );
	}

	/**
	 * @dataProvider illegal_names
	 */
	public function test_illegal_names( $name ) {
		$this->assertWPError( bp_groups_register_group_type( $name ) );
	}

	public function illegal_names() {
		return array(
			array( 'any' ),
			array( 'null' ),
			array( '_none' ),
		);
	}

	public function test_groups_register_type_should_sanitize_group_type_key() {
		$key = 'F//oo% -Bar';
		$sanitized_key = 'foo-bar';

		$object = bp_groups_register_group_type( $key );
		$this->assertSame( $sanitized_key, $object->name );
	}

	public function test_groups_register_type_should_store_group_type_string_as_name_property() {
		$object = bp_groups_register_group_type( 'foo' );
		$this->assertSame( 'foo', $object->name );
	}

	public function test_groups_register_type_should_fill_missing_labels_with_ucfirst_group_type() {
		$object = bp_groups_register_group_type( 'foo' );
		foreach ( $object->labels as $label ) {
			$this->assertSame( 'Foo', $label );
		}
	}

	public function test_groups_register_type_should_respect_custom_name_labels() {
		$object = bp_groups_register_group_type( 'foo', array(
			'labels' => array(
				'name' => 'Bar',
			),
		) );

		$this->assertSame( 'Bar', $object->labels['name'] );
		$this->assertSame( 'Bar', $object->labels['singular_name'] );
	}

	public function test_groups_register_type_should_respect_custom_singular_name_labels() {
		$object = bp_groups_register_group_type( 'foo', array(
			'labels' => array(
				'singular_name' => 'Bar',
			),
		) );

		$this->assertSame( 'Foo', $object->labels['name'] );
		$this->assertSame( 'Bar', $object->labels['singular_name'] );
	}

	public function test_groups_get_type_object_should_return_null_for_non_existing_group_type() {
		$this->assertSame( null, bp_groups_get_group_type_object( 'foo' ) );
	}

	public function test_groups_get_type_object_should_return_type_object() {
		bp_groups_register_group_type( 'foo' );
		$this->assertInternalType( 'object', bp_groups_register_group_type( 'foo' ) );
	}

	public function test_groups_set_type_should_return_false_for_invalid_group_type() {
		$this->assertFalse( bp_groups_set_group_type( 1, 'foo' ) );
	}

	public function test_groups_set_type_success() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );

		$this->assertNotEmpty( bp_groups_set_group_type( $g, 'foo' ) );
	}

	public function test_groups_set_type_should_remove_type_when_passing_an_empty_value() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );
		bp_groups_set_group_type( $g, 'foo' );

		// Make sure group type is set.
		$this->assertSame( 'foo', bp_groups_get_group_type( $g ) );

		$this->assertSame( array(), bp_groups_set_group_type( $g, '' ) );
		$this->assertFalse( bp_groups_get_group_type( $g ) );
	}

	public function test_groups_get_type_with_default_value_for_single() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g, 'foo' );
		bp_groups_set_group_type( $g, 'bar', true );

		$this->assertSame( 'foo', bp_groups_get_group_type( $g ) );
	}

	public function test_groups_get_type_with_single_true() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g, 'foo' );
		bp_groups_set_group_type( $g, 'bar', true );

		$this->assertSame( 'foo', bp_groups_get_group_type( $g, true ) );
	}

	public function test_groups_get_type_with_single_false() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g, 'foo' );
		bp_groups_set_group_type( $g, 'bar', true );

		$this->assertEqualSets( array( 'foo', 'bar' ), bp_groups_get_group_type( $g, false ) );
	}

	public function test_groups_get_type_should_return_false_when_no_value_is_found() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );

		$this->assertFalse( bp_groups_get_group_type( $g ) );
	}

	public function test_groups_remove_type_should_return_false_when_group_type_is_empty() {
		$this->assertFalse( bp_groups_remove_group_type( 9, '' ) );
	}

	public function test_groups_remove_type_should_return_false_when_group_type_is_invalid() {
		$this->assertFalse( bp_groups_remove_group_type( 9, 'foo' ) );
	}

	public function test_groups_remove_type_should_return_false_when_group_is_not_of_provided_type() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g, 'foo' );

		$this->assertFalse( bp_groups_remove_group_type( $g, 'bar' ) );
		$this->assertEquals( array( 'foo' ), bp_groups_get_group_type( $g, false ) );
	}

	public function tests_groups_remove_type_should_return_true_on_successful_deletion() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g, 'foo' );
		bp_groups_set_group_type( $g, 'bar', true );

		$this->assertTrue( bp_groups_remove_group_type( $g, 'foo' ) );
		$this->assertEquals( array( 'bar' ), bp_groups_get_group_type( $g, false ) );
	}

	public function test_groups_has_type_should_return_false_when_group_type_is_empty() {
		$this->assertFalse( bp_groups_has_group_type( 9, '' ) );
	}

	public function test_groups_has_type_should_return_false_when_group_type_is_invalid() {
		$this->assertFalse( bp_groups_has_group_type( 9, 'foo' ) );
	}

	public function test_groups_has_type_should_return_false_when_group_id_is_empty() {
		bp_groups_register_group_type( 'foo' );

		$this->assertFalse( bp_groups_has_group_type( '', 'foo' ) );
	}

	public function test_groups_has_type_should_return_false_when_group_is_not_of_provided_type() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g, 'foo' );

		$this->assertFalse( bp_groups_has_group_type( $g, 'bar' ) );
	}

	public function test_groups_has_type_should_return_true_on_success() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g, 'foo' );
		bp_groups_set_group_type( $g, 'bar', true );

		$this->assertTrue( bp_groups_has_group_type( $g, 'foo' ) );
		$this->assertEqualSets( array( 'bar', 'foo' ), bp_groups_get_group_type( $g, false ) );
	}

	/**
	 * @group cache
	 */
	public function test_groups_get_type_should_hit_cache() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );
		bp_groups_set_group_type( $g, 'foo' );

		global $wpdb;

		// Initial query. Should hit DB.
		bp_groups_get_group_type( $g );
		$num_queries = $wpdb->num_queries;

		// Next query should hit cache
		bp_groups_get_group_type( $g );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	public function test_bp_groups_get_group_types_filter() {
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );

		$types = bp_groups_get_group_types( array( 'name' => 'bar' ) );

		$this->assertEqualSets( array( 'bar' ), $types );
	}

	public function test_groups_get_type_should_not_return_unregistered_types() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );
		bp_groups_set_group_type( $g, 'foo' );

		// Directly set a type that hasn't been registered.
		bp_set_object_terms( $g, 'ugh', 'bp_group_type', true );

		$type = bp_groups_get_group_type( $g, false );
		$this->assertEquals( array( 'foo' ), $type );
	}

	public function test_bp_groups_register_group_type_show_in_list_true_when_show_in_create_screen_true() {
		$object = bp_groups_register_group_type( 'foo', array(
			'show_in_create_screen' => true,
		) );

		$this->assertTrue( $object->show_in_list );
	}

	public function test_bp_groups_register_group_type_show_in_list_false_when_show_in_create_screen_false() {
		$object = bp_groups_register_group_type( 'foo', array(
			'show_in_create_screen' => false,
		) );

		$this->assertFalse( $object->show_in_list );
	}

	public function test_bp_groups_register_group_type_show_in_list_false_and_show_in_create_screen_true() {
		$object = bp_groups_register_group_type( 'foo', array(
			'show_in_create_screen' => true,
			'show_in_list' => false,
		) );

		$this->assertFalse( $object->show_in_list );
	}

	public function test_bp_groups_set_group_type_should_remove_types_when_passing_an_empty_value() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );
		bp_groups_set_group_type( $g, 'foo' );

		// Make sure it's set up.
		$this->assertSame( 'foo', bp_groups_get_group_type( $g ) );

		$this->assertSame( array(), bp_groups_set_group_type( $g, '' ) );
		$this->assertFalse( bp_groups_get_group_type( $g ) );
	}

	public function test_bp_groups_set_group_type_should_set_multiple_types_when_passing_array_of_types() {
		$g = self::factory()->group->create( array( 'creator_id' => self::$u1 ) );
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );

		// Set multiple group types.
		$types = array( 'foo', 'bar' );
		bp_groups_set_group_type( $g, $types );

		// Assert!
		$this->assertEqualSets( $types, bp_groups_get_group_type( $g, false ) );
	}
}
