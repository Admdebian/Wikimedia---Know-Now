<?php
/*
** License: GPL (http://www.gnu.org/copyleft/gpl.html)
**
** KnowNow Wikimedia 1.17 extension by Alfredo Di Maria - http://www.alfredodimaria.it - linuxloverstaff (at) gmail.com
** This extension lists recent revisions of the current page and of the wiki. Ajax functions update the box every minute.
*/
if ( ! defined( 'MEDIAWIKI' ) ) {
	echo "Not a valid entry point";
	exit( 1 );
}

/* Import files in the header*/
function KnowNow_css( &$out, &$sk ) {
	global $wgExtensionAssetsPath;
	// Add CSS and Javascript
	$out->addStyle( $wgExtensionAssetsPath . "/KnowNow/includes/KnowNow.css" );
	return true;
}
function KnowNow_js( &$out, &$sk ) {
	global $wgExtensionAssetsPath;
	// Add CSS and Javascript
	$out->addScriptFile( $wgExtensionAssetsPath . "/KnowNow/includes/KnowNow.js" );
	return true;
}

/* Functions to check current page revisions*/
function wfAjaxQueryKnowNow( $page_id, $last_touch ) {
	if ( $page_id != 0 ) {
		// We read from Master to avoid latency
		$dbr = wfGetDB( DB_MASTER );
		$res = $dbr->select(
			'page',						// $table
			array( 'page_latest',  'page_touched'),		// $vars (columns of the table)
			'page_id = '.$page_id,				// $conds
			__METHOD__,					// $fname = 'Database::select',
			array( 'ORDER BY' => 'page_touched DESC' )	// $options = array()
			);
		foreach( $res as $row ) {
			if ( $row->page_touched > $last_touch ) {
				$res_2 = $dbr->select(
					'revision',							// $table
					array( 'rev_timestamp', 'rev_comment', 'rev_user_text' ),	// $vars (columns of the table)
					'rev_id = '.$row->page_latest,					// $conds
					__METHOD__,							// $fname = 'Database::select',
					array( 'ORDER BY' => 'rev_timestamp DESC' )			// $options = array()
					);
				$revision_list = NULL;
				foreach( $res_2 as $my_rev ) {
					if ( $my_rev->rev_comment != NULL ) {
						$comment = " - <b>Comment:</b> " . wfTimestamp( TS_RFC2822, $my_rev->rev_comment) . " ";
					} else {
						$comment = NULL;
					}
					$revision_list .= "<p><b>User:</b> " . $my_rev->rev_user_text . $comment . " - <b>" . $my_rev->rev_timestamp . "</b></p>";
					return $revision_list . "|" . $row->page_touched;
				}
			}
		}
	}
	return 0;
}

function KnowNow_recent_revisions( &$parser, &$text ) {
	global $wgKnowNow_1,$wgKnowNow_2,$wgRequest, $wgOut, $wgKnowNow, $wgArticle,$wgTitle;

/* Sorry, but documentation lacks of a $wgTitle alternative. That's because I use this global that will be deprecated in the 1.19 version.
   However you can declare a new object like $wgTitle and initialize it with $wgRequest->getVal( 'title' ) value. */

/* I check if it's the first time the hook runs, if we are working with a standar wikipage in view mode and if the page has a valid history. */
	if ( $wgKnowNow_1 != TRUE && $wgRequest->getVal( 'action' ) != "submit" && $wgRequest->getVal( 'action' ) != "edit" && $wgTitle->getNamespace() == 0  && !$wgTitle->isSingleRevRedirect()) {
/* We find the current revision id and search for previous revisions*/
		$dbs = wfGetDB( DB_SLAVE );
		$res = $dbs->select(
			'page',								// $table
			array( 'page_latest',  'page_touched'),				// $vars (columns of the table)
			'page_id = '.$wgTitle->getArticleID(),				// $conds
			__METHOD__,							// $fname = 'Database::select',
			array( 'ORDER BY' => 'page_touched DESC' )			// $options = array()
			);
// if there is a page entry and we can read the revision id, then we query data about previous revision.
		$revision_list = NULL;
		foreach( $res as $current_revision ) {
			//Ajax need something to store the last timestamp of page_touched
			//$text .= '<script type=\"text/javascript\"> var wgKnowNowTouch = ' . $current_revision->page_touched . '; </script>';
			$text .= "<input id=\"wgKnowNowTouch\" type=\"hidden\" value=\"" . $current_revision->page_touched . "\" />";
			$text .= "<input id=\"wgKnowNowArticleId\" type=\"hidden\" value=\"" . $wgTitle->getArticleID() . "\" />";
			$work_rev_id = $current_revision->page_latest;
			$i = 0;
			while ( $i < $wgKnowNow_2 && $work_rev_id != 0  ) {
				if ( $work_rev_id != 0 ) {
					$res_2 = $dbs->select(
						'revision',					// $table
						array( 'rev_timestamp', 'rev_comment', 'rev_user_text' ),	// $vars (columns of the table)
						'rev_id = '.$work_rev_id,			// $conds
						__METHOD__,					// $fname = 'Database::select',
						array( 'ORDER BY' => 'rev_timestamp DESC' )	// $options = array()
						);
					foreach( $res_2 as $my_rev ) {
						if ( $my_rev->rev_comment != NULL ) {
							$comment = " - <b>" . wfMsg('kncomment') . ":</b> " . $my_rev->rev_comment . " ";
						} else {
							$comment = NULL;
						}
						$revision_list .= "\n<p><b>" . wfMsg('knuser') . ":</b> " . $my_rev->rev_user_text . $comment . " - <b>" . wfTimestamp( TS_RFC2822, $my_rev->rev_timestamp) . "</b></p>";
					}
				}
				$work_rev_id = $wgTitle->getPreviousRevisionID( $work_rev_id );
				$i = $i +1;
			}
		}
		if ( $revision_list != NULL) {
			$text .= "<li><p class=\"KN_littleTitle\">" . wfMsg('pagemodification').":</p>";
			$text .= "<div id=\"KnowNow_recentMod\">" . $revision_list . "</div></li>";
		}
		$wgKnowNow_1 = TRUE;
	}
	return true;
}

/* Functions to check wall Wiki revisions*/
function KnowNow_global_events( &$parser, &$text ) {
	global $wgKnowNow_3, $wgRequest, $wgTitle;
	if ( $wgKnowNow_3 != TRUE && $wgRequest->getVal( 'action' ) != "submit" && $wgRequest->getVal( 'action' ) != "edit" && $wgTitle->getNamespace() == 0) {
		$dbs = wfGetDB( DB_SLAVE );
		$res = $dbs->select(
			'revision',								// $table
			array( 'rev_page', 'rev_comment', 'rev_user_text', 'rev_timestamp' ),	// $vars (columns of the table)
			'rev_id > 0',								// $conds
			__METHOD__,								// $fname = 'Database::select',
			array( 'ORDER BY' => 'rev_timestamp DESC', 'LIMIT' => '5' )		// $options = array()
			);
		$my_out = NULL;
		$last_time = 0;
		foreach ( $res as $row ) {
			if ( $last_time == 0 ) {
				$last_time = $row->rev_timestamp;
			}
			$res_2 = $dbs->select(
				'page',								// $table
				array( 'page_title' ),						// $vars (columns of the table)
				'page_id = '.$row->rev_page,					// $conds
				__METHOD__,							// $fname = 'Database::select',
				array( 'ORDER BY' => 'page_touched DESC', 'LIMIT' => '5' )	// $options = array()
				);
			foreach ( $res_2 as $row_2 ) {
				$page_name = str_replace( "_", " ", $row_2->page_title);
			}
			if ( $row->rev_comment != NULL ) {
				$comment = " - <b>" . wfMsg('kncomment') . ":</b> " . $row->rev_comment;
			} else {
				$comment = NULL;
			}
			$my_out .=  "<p>" . wfMsg('knpage') . " <b>" . $page_name . "</b> " . wfMsg('knmod') . " <b>" . $row->rev_user_text . "</b>" . $comment . " - <b>" . wfTimestamp( TS_RFC2822, $row->rev_timestamp) . "</b></p>
";
		}
		$text .= "<li><p class=\"KN_littleTitle\">" . wfMsg('globalmodification').":</p>";
		$text .= "<div id=\"KnowNow_globalMod\">" . $my_out . "<input id=\"last_rev_timestamp\" type=\"hidden\" value=\"" . $last_time . "\" /></div></li>";
	}
	$wgKnowNow_3 = TRUE;
	return true;
}

function wfAjaxQueryKnowNow_2( $last_touch ) {
	if( $last_touch > 0 ) {
		// Use Master to avoid latency
		$dbr = wfGetDB( DB_MASTER );
		$res = $dbr->select(
			'revision',								// $table
			array( 'rev_page', 'rev_comment', 'rev_user_text', 'rev_timestamp' ),	// $vars (columns of the table)
			'rev_timestamp > '.$last_touch,						// $conds
			__METHOD__,								// $fname = 'Database::select',
			array( 'ORDER BY' => 'rev_timestamp ASC', 'LIMIT' => '5' )		// $options = array()
			);
		$my_out = NULL;
		foreach ( $res as $row ) {
			$last_time = $row->rev_timestamp;
			$res_2 = $dbr->select(
				'page',								// $table
				array( 'page_title' ),						// $vars (columns of the table)
				'page_id = '.$row->rev_page,					// $conds
				__METHOD__,							// $fname = 'Database::select',
				array( 'ORDER BY' => 'page_touched DESC', 'LIMIT' => '5' )	// $options = array()
				);
			foreach ( $res_2 as $row_2 ) {
				$page_name = str_replace( "_", " ", $row_2->page_title);
			}
			if ( $row->rev_comment != NULL ) {
				$comment = " - <b>" . wfMsg('kncomment') . ":</b> " . $row->rev_comment;
			} else {
				$comment = NULL;
			}
			$my_out .=  "<p>" . wfMsg('knpage') . " <b>" . $page_name . "</b> " . wfMsg('knmod') . " <b>" . $row->rev_user_text . "</b>" . $comment . " - <b>" . wfTimestamp( TS_RFC2822, $row->rev_timestamp) . "</b></p>
	";
		}
		return $my_out . "|" . $last_time;
	}
	return 0;
}

