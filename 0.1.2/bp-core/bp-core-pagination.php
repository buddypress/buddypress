<?php

// Currently used by bp-groups, but the following could be replaced
// by the built in WordPress 'paginate_links' function?


// get the start number for pagination
function bp_get_page_start( $p, $num ) {
	$p   = bp_int($p);
	$num = bp_int($num);
	
	if ( $p == "" ) {
		return 0;
	} else {
		return ( $p * $num ) - $num;
	}
}

// get the page number from the $_GET["p"] variable
function bp_get_page() {
	if ( isset( $_GET["p"] ) ) {
		return (int) $_GET["p"]; 		
	}
	else {
		return 1;
	}		
}

// generate page links
function bp_generate_pages_links( $totalRows, $maxPerPage = 25, $linktext = "", $var, $attributes = "" ) {
    // loop all the pages in the result set
    for ( $i = 1; $i <= ceil( $totalRows / $maxPerPage ); $i++ ) {
		// if the current page is different to this link, create the querystring
		$page = bp_int( @$var, true );
		if ($i != $page)
		{
			if ( $linktext == "" ) {
				$link = "?p=" . $i;
			} else {
				$link = str_replace( "%%", $i, $linktext );
			}
			$links["link"][] = $link;
			$links["text"][] = $i;
			$links["attributes"][] = str_replace( "%%", $i, $attributes );
		// otherwise make the link empty
		} else {
			$links["link"][] = "";
			$links["text"][] = $i;
			$links["attributes"][] = str_replace( "%%", $i, $attributes );
		}
    }
    // return the links
    return $links;
}

// generate page link list
function bp_paginate( $links, $currentPage = 1, $firstItem = "", $listclass = "" ) {
	$return = "";
	// check the parameter is an array with more than 1 items in
	if ( is_array($links) && count($links["text"]) > 1 ) {
		// get the total number of links
		$totalPages = count($links["text"]);
		
		// set showstart and showend to false
		$showStart = false;
		$showEnd   = false;
		
		// if the total number of pages is greater than 10
		if ( $totalPages > 10 ) {
			
			// if the current page is less than 5 from the start
			if ( $currentPage <= 5 ) {
				// set the minimum and maximum pages to show
				$minimum = 0;
				$maximum = 9;
				$showEnd = true;
			}
			
			// if the current page is less than 5 from the end
			if ( $currentPage >= ( $totalPages - 5 ) ) {
				// set the minumum and maximum pages to show
				$minimum   = $totalPages - 9;
				$maximum   = $totalPages;
				$showStart = true;
			}
			
			// if the current page is somewhere in the middle
			if ( $currentPage > 5 && $currentPage < ( $totalPages - 5 ) )
			{
				$showEnd   = true;
				$showStart = true;
				$minimum   = $currentPage - 4;
				$maximum   = $currentPage + 4;
			}
			
		} else {
			$minimum = 0;
			$maximum = $totalPages;
		}
		
		// print the start of the list
		$return .= "\n\n<ul class=\"pagelinks";
		
		if ( $listclass != "" )
			$return .= " ".$listclass;
			
		$return .= "\">\n";
		
		// print the first item, it if is set
		if ( $firstItem != "" ) {
			$return .= "<li>" . $firstItem . "</li>\n";
		}
		
		// print the page text
		$return .= "<li>Pages:</li>\n";
		
		// if set, show the start
		if ( $showStart )
			$return .= "<li><a href=\"" . str_replace( "&", "&amp;", $links["link"][0] ) . "\">" . $links["text"][0] . "...</a></li>\n";

		// loop the links
		for ( $i = $minimum; $i < $maximum; $i++ ) {
			if ( $i == ( $currentPage - 1 ) ) {
				$url = "<li class=\"current\">" . $links["text"][$i] . "</li>\n";
			} else {
				if ($links["attributes"][$i] != "")
					$attributes = " " . $links["attributes"][$i];
				else
					$attributes = "";
					
				$url = "<li><a href=\"" . str_replace( "&", "&amp;", $links["link"][$i] ) . "\"" . $attributes . ">" . $links["text"][$i] . "</a></li>\n";
			}
			$return .= $url;
		}
		// if set, show the end
		if ( $showEnd ) {
			$return .= "<li><a href=\"" . str_replace( "&", "&amp;", $links["link"][$totalPages - 1]) . "\">..." . $links["text"][$totalPages-1] . "</a></li>\n";
		}
		$return .= "</ul>\n\n";
	}
	
	return $return;
}


?>