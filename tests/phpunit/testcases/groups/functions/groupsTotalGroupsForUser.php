<?php

/**
 * @group groups
 * @covers ::groups_total_groups_for_user
 */
class BP_Tests_Groups_Functions_GroupsTotalGroupsForUser extends BP_UnitTestCase {
	/**
	 * @ticket BP6813
	 */
	public function test_should_return_integer() {
		$this->assertInternalType( 'int', groups_total_groups_for_user( 123 ) );
	}

	/**
	 * @ticket BP6813
	 */
	public function test_should_return_integer_when_fetching_from_cache() {
		/*
		 * Put a string in the cache.
		 * In-memory cache will respect type, but persistent caching engines return all scalars as strings.
		 */
		wp_cache_set( 'bp_total_groups_for_user_123', '321', 'bp' );

		$this->assertInternalType( 'int', groups_total_groups_for_user( 123 ) );
	}
}
