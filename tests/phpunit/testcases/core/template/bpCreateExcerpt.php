<?php

/**
 * @group core
 * @group bp_create_excerpt
 */
class BP_Tests_Core_Template_BPCreateExcerpt extends BP_UnitTestCase {
	public function test_should_ignore_html_tag_when_html_true() {
		$text = 'foo <hr> bar baz';
		$expected = 'foo <hr /> bar';
		$this->assertSame( $expected, bp_create_excerpt( $text, 8, array(
			'ending' => '',
			'html' => true,
			'exact' => true,
		) ) );
	}

	/**
	 * @ticket BP3680
	 */
	public function test_should_ignore_single_word_html_comments_when_html_true() {
		$text = 'foo <!--more--> bar baz';
		$expected = 'foo <!--more--> bar';
		$this->assertSame( $expected, bp_create_excerpt( $text, 8, array(
			'ending' => '',
			'html' => true,
			'exact' => true,
		) ) );
	}

	/**
	 * @ticket BP3680
	 */
	public function test_should_ignore_multiple_word_html_comments_when_html_true() {
		$text = 'foo <!--one two three--> bar baz';
		$expected = 'foo <!--one two three--> bar';
		$this->assertSame( $expected, bp_create_excerpt( $text, 8, array(
			'ending' => '',
			'html' => true,
			'exact' => true,
		) ) );
	}
}
