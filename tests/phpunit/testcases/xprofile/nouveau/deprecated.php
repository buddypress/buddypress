<?php

/**
 * @group xprofile
 * @group deprecated
 */
class BP_Tests_XProfile_Nouveau_Deprecated extends BP_UnitTestCase {

	/**
	 * @expectedDeprecated bp_nouveau_base_account_has_xprofile
	 */
	public function test_nouveau_base_account_has_xprofile_returns_signup_field_availability() {
		$this->assertSame( bp_nouveau_has_signup_xprofile_fields(), bp_nouveau_base_account_has_xprofile() );
	}
}
