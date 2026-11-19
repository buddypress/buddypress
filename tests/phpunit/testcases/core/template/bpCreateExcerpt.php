<?php

/**
 * @group core
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
	 * @link https://buddypress.trac.wordpress.org/ticket/3680
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
	 * @link https://buddypress.trac.wordpress.org/ticket/3680
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
	 * @link https://buddypress.trac.wordpress.org/ticket/6517
	 */
	public function test_exact_false_should_properly_account_for_accented_characters() {
		$text = 'Toutes les connaissances que les hommes avaient mis sur Internet lui étaient accessible. Les grandes bibliothèques du monde entier n’avaient plus de secret pour lui. Il pouvait apprendre très vite, beaucoup plus vite que n’importe quel humain.
Il avait appris toutes les connaissances du monde entier, visiter tout les pays. C’est lui qui avait fait en sorte qu’Internet se déploie ainsi.';
		$expected = 'Toutes les connaissances que les hommes avaient mis sur Internet lui étaient accessible. Les';
		$this->assertSame( $expected, bp_create_excerpt( $text, 98, array(
			'ending' => '',
			'exact' => false,
		) ) );
	}

	/**
	 * @link https://buddypress.trac.wordpress.org/ticket/6254
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
	 * @link https://buddypress.trac.wordpress.org/ticket/6517
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

	/**
	 * Multibyte text must not move a word boundary into an HTML attribute.
	 */
	public function test_multibyte_prefix_should_not_cut_mid_tag_when_exact_is_false() {
		$tag = ' x<img src="/missing.png" alt="a http://example.com/path ' . str_repeat( 'b', 60 ) . '">';

		foreach ( array( 'z', 'é', '€', '😀' ) as $character ) {
			$prefix = str_repeat( $character, 40 );
			$text   = $prefix . $tag . str_repeat( 'c', 420 );

			$this->assertSame( $prefix, bp_create_excerpt( $text, 225, array( 'ending' => '' ) ) );
		}
	}

	public function test_multibyte_prefix_should_not_split_html_entity() {
		$options = array(
			'ending' => '',
			'exact'  => true,
		);

		$this->assertSame( 'a&amp;', bp_create_excerpt( 'a&amp;BBBBBBBB', 2, $options ) );
		foreach ( array( 'é', '€', '😀' ) as $character ) {
			$this->assertSame( $character . '&amp;', bp_create_excerpt( $character . '&amp;BBBBBBBB', 2, $options ) );
		}
		$this->assertSame( 'é&amp;é&lt;', bp_create_excerpt( 'é&amp;é&lt;BBBBBBBB', 4, $options ) );
		$this->assertSame( 'é', bp_create_excerpt( 'é&amp;BBBBBBBB', 1, $options ) );
	}

	public function test_default_excerpt_should_not_split_html_entity_after_multibyte_prefix() {
		$prefix = str_repeat( 'A', 213 );
		$text   = $prefix . 'é&amp;' . str_repeat( 'B', 100 );

		$this->assertSame( $prefix . 'é&amp; [&hellip;]', bp_create_excerpt( $text ) );
	}

	public function test_uppercase_tag_should_not_be_cut_at_attribute_space() {
		$options = array(
			'ending' => '',
			'exact'  => false,
		);

		$this->assertSame(
			'<img alt="first second third fourth" src="x"/>onewo',
			bp_create_excerpt( '<IMG alt="first second third fourth" src="x">onewordlong', 5, $options )
		);
		$this->assertSame(
			'<a title="first second third fourth" href="#">onewo</a>',
			bp_create_excerpt( '<A title="first second third fourth" href="#">onewordlong</A>', 5, $options )
		);
	}
}
