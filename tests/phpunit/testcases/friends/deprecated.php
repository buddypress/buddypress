<?php

/**
 * @group friends
 * @group deprecated
 */
class BP_Tests_Friends_Deprecated extends BP_UnitTestCase {

	/**
	 * @group __construct
	 * @expectedDeprecated BP_Friends_Friendship::__construct
	 */
	public function test_deprecated_arg() {
		$friendship = new BP_Friends_Friendship( 123456789, true );
		$this->assertSame( 0, $friendship->id );
	}
}
