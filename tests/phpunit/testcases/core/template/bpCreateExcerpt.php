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

	public function test_should_break_on_prior_word_boundary_when_exact_is_false() {
		$text = 'aaaaa aaaaaa';
		$expected = 'aaaaa';
		$this->assertSame( $expected, bp_create_excerpt( $text, 7, array(
			'exact' => false,
			'ending' => '',
		) ) );
	}

	/**
	 * @ticket BP6254
	 */
	public function test_should_trim_too_long_first_word_to_max_characters_even_when_exact_is_false() {
		$text = 'aaaaaaaaaaa';
		$expected = 'aaa';
		$this->assertSame( $expected, bp_create_excerpt( $text, 3, array(
			'exact' => false,
			'ending' => '',
		) ) );
	}

	/**
	 * @ticket BP6517
	 */
	public function test_string_should_not_be_cut_mid_tag_when_exact_is_false() {
		$text = '<p><span>Foo</span> <a href="http://example.com">Bar</a> Baz.</p><p>Foo Bar Baz</p>';
		$actual = bp_create_excerpt( $text, 7, array(
			'html' => true,
			'ending' => '',
			'exact' => false,
		) );
		$this->assertSame( '<p><span>Foo</span> <a href="http://example.com">Bar</a></p>', $actual );
	}
}
