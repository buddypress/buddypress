<?php

/**
 * @group blogs
 * @group BP_Blogs_Blog
 */
class BP_Tests_BP_Blogs_Blog_TestCases extends BP_UnitTestCase {
	public function test_get_with_search_terms() {
		if ( ! is_multisite() ) {
			return;
		}

		$old_user = get_current_user_id();

		$u = $this->factory->user->create();
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

	/**
	 * @ticket BP5858
	 */
	public function test_get_with_search_terms_should_match_description() {
		if ( ! is_multisite() ) {
			return;
		}

		$old_user = get_current_user_id();

		$u = $this->factory->user->create();
		$this->set_current_user( $u );
		$b = $this->factory->blog->create( array(
			'title' => 'The Foo Bar Blog',
			'domain' => __METHOD__,
			'user_id' => $u,
		) );
		update_blog_option( $b, 'blogdescription', 'Full of foorificness' );
		bp_blogs_record_existing_blogs();

		// make the blog public or it won't turn up in generic results
		update_blog_option( $b, 'blog_public', '1' );

		$blogs = BP_Blogs_Blog::get( 'active', false, false, 0, 'Full' );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );

		$this->assertEquals( array( $b ), $blog_ids );
		$this->assertEquals( 1, $blogs['total'] );
	}

	public function test_search_blogs() {
		if ( ! is_multisite() ) {
			return;
		}

		$old_user = get_current_user_id();

		$u = $this->factory->user->create();
		$this->set_current_user( $u );
		$b = $this->factory->blog->create( array(
			'title' => 'The Foo Bar Blog',
			'user_id' => $u,
			'path' => '/path' . rand() . time() . '/',
		) );
		bp_blogs_record_existing_blogs();

		// make the blog public or it won't turn up in generic results
		update_blog_option( $b, 'blog_public', '1' );

		$blogs = BP_Blogs_Blog::search_blogs( 'Foo' );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );

		$this->assertEquals( array( $b ), $blog_ids );
	}

	/**
	 * @group get_by_letter
	 */
	public function test_get_by_letter() {
		if ( ! is_multisite() ) {
			return;
		}

		$old_user = get_current_user_id();

		$u = $this->factory->user->create();
		$this->set_current_user( $u );
		$b = $this->factory->blog->create( array(
			'title' => 'Foo Bar Blog',
			'user_id' => $u,
			'path' => '/path' . rand() . time() . '/',
		) );
		bp_blogs_record_existing_blogs();

		// make the blog public or it won't turn up in generic results
		update_blog_option( $b, 'blog_public', '1' );

		$blogs = BP_Blogs_Blog::get_by_letter( 'F' );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );

		$this->assertEquals( array( $b ), $blog_ids );
	}

	/**
	 * @group get_order_by
	 */
	public function test_get_order_by() {
		if ( ! is_multisite() ) {
			return;
		}

		$old_user = get_current_user_id();

		$u = $this->factory->user->create();
		$this->set_current_user( $u );
		$bs = array(
			'foobar' => $this->factory->blog->create( array(
				'title' => 'Foo Bar Blog',
				'user_id' => $u,
				'path' => '/path' . rand() . time() . '/',
			) ),
			'barfoo' => $this->factory->blog->create( array(
				'title' => 'Bar foo Blog',
				'user_id' => $u,
				'path' => '/path' . rand() . time() . '/',
			) ),
		);

		bp_blogs_record_existing_blogs();

		// make the blog public or it won't turn up in generic results
		foreach ( $bs as $b ) {
			update_blog_option( $b, 'blog_public', '1' );
		}

		// Used to make sure barfoo is older than foobar
		$b_time = date_i18n( 'Y-m-d H:i:s', strtotime( '-5 minutes' ) );

		/* Alphabetical */
		$blogs = BP_Blogs_Blog::get( 'alphabetical', false, false, $u );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );
		$this->assertEquals( array( $bs['barfoo'], $bs['foobar'] ), $blog_ids );

		/* Newest */
		update_blog_details( $bs['barfoo'], array( 'registered' => $b_time ) );
		$blogs = BP_Blogs_Blog::get( 'newest', false, false, $u );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );
		$this->assertEquals( array( $bs['foobar'], $bs['barfoo'] ), $blog_ids );

		/* Active */
		bp_blogs_update_blogmeta( $bs['barfoo'], 'last_activity', $b_time );
		$blogs = BP_Blogs_Blog::get( 'active', false, false, $u );
		$blog_ids = wp_list_pluck( $blogs['blogs'], 'blog_id' );
		$this->assertEquals( array( $bs['foobar'],$bs['barfoo'] ), $blog_ids );

		/* Random */
		$blogs = BP_Blogs_Blog::get( 'random', false, false, $u );
		$this->assertTrue( 2 == count( $blogs['blogs'] ) );

		$this->set_current_user( $old_user );
	}
}
