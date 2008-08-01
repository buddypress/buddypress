<?php

class BP_Core_User {
	var $id;
	var $avatar;
	var $user_url;
	var $user_link;
	
	var $last_active;
	var $profile_last_updated;
	
	var $status;
	var $status_last_updated;
	
	var $content_last_updated;
	
	function bp_core_user( $user_id ) {
		if ( $user_id ) {
			$this->id = $user_id;
			$this->populate( $this->id );
		}
	}
	
	function populate() {
		global $userdata;

		$this->user_url = bp_core_get_userurl( $this->id );
		$this->user_link = bp_core_get_userlink( $this->id );
		$this->last_active = get_usermeta( $this->id, 'last_activity' ); 

		if ( BP_XPROFILE_IS_INSTALLED ) {
			$this->avatar = core_get_avatar( $this->id, 1 );
			$this->profile_last_updated = bp_profile_last_updated_date( $this->id, false );
		}
		
		if ( BP_STATUSES_IS_INSTALLED ) {
			$this->status = null; // TODO: Fetch status updates.
			$this->status_last_updated = null;
		}
	}
}

?>