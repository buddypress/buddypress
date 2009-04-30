<?php
/*
 * footer.php
 * Loaded at the end of every page.
 */
?>

</div> <!-- end #content -->

<div id="footer">
    <?php printf( __( '%s is proudly powered by <a href="http://mu.wordpress.org">WordPress MU</a> and <a href="http://buddypress.org">BuddyPress</a>', 'buddypress'  ), bloginfo('name') ); ?>
</div>

<?php wp_footer(); ?>

</body>

</html>