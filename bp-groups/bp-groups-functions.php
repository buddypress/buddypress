<?php

// get the IDs of user blogs in a comma-separated list for use in SQL statements 
function bp_get_blog_ids_of_user( $id, $all = false ) { 
        $blogs = get_blogs_of_user( $id, $all ); 
        $blog_ids = ""; 
         
        if ( $blogs && count($blogs) > 0 ){ 
                foreach( $blogs as $blog ) { 
                        $blog_ids .= $blog->blog_id.","; 
                } 
        } 
        $blog_ids = trim( $blog_ids, "," ); 
        return $blog_ids; 
} 
 
// return a tick for a checkbox for a true boolean value 
function bp_boolean_ticked($bool) { 
        if ( $bool ) { 
                return " checked=\"checked\""; 
        } 
        return ""; 
} 
 
// return a tick for a checkbox for a particular value 
function bp_value_ticked( $var, $value ) { 
        if ( $var == $value ) { 
                return " checked=\"checked\""; 
        } 
        return ""; 
} 
 
// return true for a boolean value from a checkbox 
function bp_boolean( $value = 0 ) { 
        if ( $value != "" ) { 
                return 1; 
        } else { 
                return 0; 
        } 
} 
 
// return an integer 
function bp_int( $var, $nullToOne=false ) { 
        if ( @$var == "" ) { 
                if ( $nullToOne ) { 
                        return 1; 
                } else { 
                        return 0; 
                } 
        } else { 
                return (int)$var; 
        } 
} 
 
 
// show a friendly date 
function bp_friendly_date($timestamp) { 
        // set the timestamp to now if it hasn't been given 
        if ( strlen($timestamp) == 0 ) 
                $timestamp = time(); 
         
        // create the date string 
        if ( date( "m", $timestamp ) == date("m") && date( "d", $timestamp ) == date("d") - 1 && date( "Y", $timestamp ) == date("Y") ) { 
                return "yesterday at " . date( "g:i a", $timestamp ); 
        } else if ( date( "m", $timestamp ) == date("m") && date( "d", $timestamp ) == date("d") && date( "Y", $timestamp ) == date("Y") ) { 
                return "at " . date( "g:i a", $timestamp ); 
        } else if ( date( "m", $timestamp) == date("m") && date( "d", $timestamp ) > date("d") - 5 && date( "Y", $timestamp ) == date("Y") ) { 
                return "on " . date( "l", $timestamp ) . " at " . date( "g:i a", $timestamp ); 
        } else if ( date( "Y", $timestamp) == date("Y") ) { 
                return "on " . date( "F jS", $timestamp ); 
        } else { 
                return "on " . date( "F jS Y", $timestamp ); 
        } 
} 
 
// search users 
function bp_search_users( $q, $start = 0, $num = 10 ) { 
        if ( trim($q) != "" ) { 
                global $wpdb; 
                global $current_user; 
                 
                $sql = "SELECT SQL_CALC_FOUND_ROWS id, user_login, display_name, user_nicename 
                                FROM " . $wpdb->base_prefix . "users 
                                WHERE (user_nicename like '%" . $wpdb->escape($q) . "%' 
                                OR user_email like '%" . $wpdb->escape($q) . "%' 
                                OR display_name like '%" . $wpdb->escape($q) . "%') 
                                AND (id <> " . $current_user->ID . " and id > 1) 
                                LIMIT " . $wpdb->escape($start) . ", " . $wpdb->escape($num) . ";"; 
 
                if ( !$users = $wpdb->get_results($sql) ) { 
                        return false; 
                } 
                 
                $rows = $wpdb->get_var( "SELECT found_rows() AS found_rows" ); 
                 
                if ( is_array($users) && count($users) > 0 ) { 
                        for ( $i = 0; $i < count($users); $i++ ) { 
                                $user          = $users[$i]; 
                                $user->siteurl = $user->user_url; 
                                $user->blogs   = ""; 
                                $user->blogs   = get_blogs_of_user($user->id); 
                                $user->rows    = $rows; 
                        } 
                        return $users; 
                } else { 
                        return false; 
                } 
        } else { 
                return false; 
        } 
} 
 
// return a ' if the text ends in an "s", or "'s" otherwise 
function bp_end_with_s( $string ) { 
        if ( substr( strtolower($string), - 1 ) == "s" ) { 
                return $string . "'"; 
        } else { 
                return $string . "'s"; 
        } 
} 
 
// pluralise a string 
function bp_plural( $num, $ifone = "", $ifmore = "s" ) { 
        if ( bp_int($num) != 1 ) { 
                return $ifmore; 
        } else { 
                return $ifone; 
        } 
}
?>