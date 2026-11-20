<?php
/**
 * @group core
 * @group phpunit
 */
class BP_Tests_PHPUnit_Annotations extends BP_UnitTestCase {
	public function test_expected_annotations_are_available() {
		require_once BP_TESTS_DIR . '/includes/fixtures/annotations.php';

		$fixture = new BP_PHPUnit_Annotation_Fixture( 'annotated_method' );
		$this->assertTrue( method_exists( $fixture, 'getAnnotations' ) );
	}

	public function test_class_and_trait_annotations() {
		require_once BP_TESTS_DIR . '/includes/fixtures/annotations.php';
		$fixture = new BP_PHPUnit_Annotation_Fixture( 'unannotated_method' );
		$actual  = $fixture->getAnnotations();

		$this->assertSame( array( 'bp_annotation_class' ), $actual['class']['expectedDeprecated'] );
		$this->assertSame( array( 'bp_annotation_trait' ), $actual['class']['expectedIncorrectUsage'] );
		$this->assertSame( array(), $actual['method'] );
	}

	public function test_repeated_method_annotations() {
		require_once BP_TESTS_DIR . '/includes/fixtures/annotations.php';
		$fixture = new BP_PHPUnit_Annotation_Fixture( 'annotated_method' );
		$actual  = $fixture->getAnnotations();

		$this->assertSame( array( 'bp_annotation_method', 'BP_Annotation_Fixture::legacy_method' ), $actual['method']['expectedDeprecated'] );
		$this->assertSame( array( 'bp_annotation_incorrect_usage' ), $actual['method']['expectedIncorrectUsage'] );
	}

	public function test_inline_annotation() {
		require_once BP_TESTS_DIR . '/includes/fixtures/annotations.php';
		$fixture = new BP_PHPUnit_Annotation_Fixture( 'inline_method' );
		$actual  = $fixture->getAnnotations();

		$this->assertSame( array( 'bp_annotation_inline' ), $actual['method']['expectedDeprecated'] );
	}

	public function test_valueless_annotation_is_not_discarded() {
		require_once BP_TESTS_DIR . '/includes/fixtures/annotations.php';
		$fixture = new BP_PHPUnit_Annotation_Fixture( 'empty_annotation_method' );
		$actual  = $fixture->getAnnotations();

		$this->assertSame( array( '' ), $actual['method']['expectedDeprecated'] );
	}

	public function test_inherited_method_but_not_parent_class_annotations() {
		require_once BP_TESTS_DIR . '/includes/fixtures/annotations.php';
		$fixture = new BP_PHPUnit_Annotation_Child_Fixture( 'annotated_method' );
		$actual  = $fixture->getAnnotations();

		$this->assertSame( array( 'bp_annotation_child' ), $actual['class']['expectedDeprecated'] );
		$this->assertArrayNotHasKey( 'expectedIncorrectUsage', $actual['class'] );
		$this->assertSame( array( 'bp_annotation_method', 'BP_Annotation_Fixture::legacy_method' ), $actual['method']['expectedDeprecated'] );
	}

	/** @expectedDeprecated bp_phpunit_expected_deprecation */
	public function test_wordpress_expected_deprecation_lifecycle() {
		_deprecated_function( 'bp_phpunit_expected_deprecation', '15.0.0' );
		$this->assertTrue( true );
	}

	/** @expectedIncorrectUsage bp_phpunit_expected_incorrect_usage */
	public function test_wordpress_expected_incorrect_usage_lifecycle() {
		_doing_it_wrong( 'bp_phpunit_expected_incorrect_usage', 'Expected diagnostic in the compatibility test.', '15.0.0' );
		$this->assertTrue( true );
	}
}
