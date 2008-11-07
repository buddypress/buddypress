<?php

class BP_Wire_Post {
	var $table_name;
	
	var $id;
	var $item_id;
	var $user_id;
	var $content;
	var $date_posted;
	
	function bp_wire_post( $table_name, $id = null, $populate = true ) {
		$this->table_name = $table_name;
		
		if ( $id ) {
			$this->id = $id;
			
			if ( $populate )
				$this->populate();
		}
	}
	
	function populate() {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "SELECT * FROM " . $this->table_name . " WHERE id = %d", $this->id );

		$wire_post = $wpdb->get_row($sql);

		if ( $wire_post ) {
			$this->item_id = $wire_post->item_id;
			$this->user_id = $wire_post->user_id;
			$this->content = $wire_post->content;
			$this->date_posted = $wire_post->date_posted;
		}
	}
	
	function save() {
		global $wpdb, $bp;
		
		if ( $this->id ) {
			$sql = $wpdb->prepare( 
				"UPDATE " . $this->table_name . " SET 
					item_id = %d, 
					user_id = %d, 
					content = %s, 
					date_posted = FROM_UNIXTIME(%d)
				WHERE
					id = %d
				",
					$this->item_id, 
					$this->user_id, 
					$this->content, 
					$this->date_posted, 
					$this->id
			);
		} else {
			$sql = $wpdb->prepare( 
				"INSERT INTO " . $this->table_name . " ( 
					item_id,
					user_id,
					content,
					date_posted
				) VALUES (
					%d, %d, %s, FROM_UNIXTIME(%d)
				)",
					$this->item_id, 
					$this->user_id, 
					$this->content, 
					$this->date_posted, 
					$this->id 
			);
		}

		$result = $wpdb->query($sql);
		
		if ( !$this->id )
			$this->id = $wpdb->insert_id;
		
		return $result;
	}
	
	function delete() {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM " . $this->table_name . " WHERE id = %d", $this->id ) );
	}
	
	/* Static Functions */
	
	function get_all_for_item( $item_id, $table_name, $page = false, $limit = false ) {
		global $wpdb, $bp;
		
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		
		$wire_posts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $table_name . " WHERE item_id = %d  ORDER BY date_posted DESC $pag_sql", $item_id ) );
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM " . $table_name . " WHERE item_id = %d", $item_id ) );
		
		return array( 'wire_posts' => $wire_posts, 'count' => $count );
	}
	
	function delete_all_for_item( $item_id, $table_name ) {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE item_id = %d", $item_id ) );
	}
}

?>