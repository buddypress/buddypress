<?php

/**
 * @group blogs
 */
class BP_Tests_BP_Blogs_Blog_TestCases extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_get_with_search_terms() {
		if ( ! is_multisite() ) {
			return;
		}

		$u = $this->create_user();
		$this->set_current_user( $u );
		$b = $this->factory->blog->create( array(
			'title' => 'The Foo Bar Blog',
			'user_id' => $u,
		) );
		bp_blogs_record_existing_blogs();

		// make the blog public or it won't turn up in generic results
		update_blog_option( $b, 'blog_public', '1' );

		$blogs = BP_Blogs_Blog::get( 'active', false, false, 0, 'Foo' );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );

		$this->assertEquals( array( $b ), $blog_ids );
	}

	public function test_search_blogs() {
		if ( ! is_multisite() ) {
			return;
		}

		$u = $this->create_user();
		$this->set_current_user( $u );
		$b = $this->factory->blog->create( array(
			'title' => 'The Foo Bar Blog',
			'user_id' => $u,
		) );
		bp_blogs_record_existing_blogs();

		// make the blog public or it won't turn up in generic results
		update_blog_option( $b, 'blog_public', '1' );

		$blogs = BP_Blogs_Blog::search_blogs( 'Foo' );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );

		$this->assertEquals( array( $b ), $blog_ids );
	}

}
