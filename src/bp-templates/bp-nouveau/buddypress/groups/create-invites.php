<?php
/**
* Group create step invite friends
*
* This template include provides the standard BP invites step screen content
*
* @since 3.0.0
*/
?>

<?php bp_nouveau_user_feedback( 'create-invite-friends' ); ?>

<?php
bp_new_group_invite_friend_list( array(  'before' => '<ul class="friends-list create-group-invites">', 'after' => '</ul>' ) );
