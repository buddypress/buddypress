</div> <!-- end #main -->

<div id="footer">
	<p>
		<?php bloginfo('name'); ?> is proudly powered by <a href="http://wordpress.org/">WordPress</a> | <a href="<?php bloginfo('rss2_url'); ?>">Entries (RSS)</a> and <a href="<?php bloginfo('comments_rss2_url'); ?>">Comments (RSS)</a>.
		<!-- <?php echo get_num_queries(); ?> queries.  -->
	</p>

	<?php wp_footer(); ?>
	
	<br />
	<p><code><?php echo get_num_queries(); ?> queries / <?php timer_stop(1); ?> seconds.</code></p>
</div>


</body>

</html>
