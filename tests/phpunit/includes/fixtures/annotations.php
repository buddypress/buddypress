<?php

/**
 * @expectedDeprecated bp_annotation_trait_deprecated
 * @expectedIncorrectUsage bp_annotation_trait
 */
trait BP_PHPUnit_Annotation_Fixture_Trait {}

/**
 * @expectedDeprecated bp_annotation_class
 */
class BP_PHPUnit_Annotation_Fixture extends BP_UnitTestCase {
	use BP_PHPUnit_Annotation_Fixture_Trait;

	/**
	 * @expectedDeprecated bp_annotation_method
	 * @expectedDeprecated BP_Annotation_Fixture::legacy_method
	 * @expectedIncorrectUsage bp_annotation_incorrect_usage
	 */
	public function annotated_method() {}

	/** @expectedDeprecated bp_annotation_inline */
	public function inline_method() {}

	/**
	 * @expectedDeprecated
	 */
	public function empty_annotation_method() {}

	public function unannotated_method() {}
}

/**
 * @expectedDeprecated bp_annotation_child
 */
class BP_PHPUnit_Annotation_Child_Fixture extends BP_PHPUnit_Annotation_Fixture {
	public function unannotated_method() {}
}
