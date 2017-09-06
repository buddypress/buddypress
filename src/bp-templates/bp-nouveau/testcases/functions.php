<?php
/**
 * @group functions
 */
class BP_Nouveau_Functions extends Next_Template_Packs_TestCase {

	/**
	 * @group customizer
	 */
	public function test_bp_nouveau_customizer_grid_choices() {
		$choices = bp_nouveau_customizer_grid_choices();

		$this->assertTrue( 'Three columns' === $choices[3] );

		$classes = bp_nouveau_customizer_grid_choices( 'classes' );

		$this->assertEmpty( $classes[1] );
		$this->assertTrue( 'four' === $classes[4] );
	}
}
