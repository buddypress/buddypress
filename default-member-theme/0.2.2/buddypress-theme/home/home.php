<?php get_header(); ?>

<div id="content" class="widecolumn">

<p>This is a <a href="http://mu.wordpress.org/">WordPress Mu</a> + <a href="http://buddypress.org">BuddyPress</a> powered site.</p>
<p>You can: 
	<ul>
		<li> <h4><a href="wp-login.php">Login</a></h4> </li>
		<li> <h4><a href="signup.php">Register</a></h4></li>
	</ul>
</p>
<hr />
<h4>Site News:</h4>
<ul>
<?php 
query_posts('showposts=7');
if (have_posts()) : ?><?php while (have_posts()) : the_post(); ?>
<li><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_title();?> </a></li>
<?php endwhile; ?><?php endif; ?>
</ul>

</div>

<?php get_footer(); ?>
