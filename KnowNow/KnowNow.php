<?php
/*
** License: GPL (http://www.gnu.org/copyleft/gpl.html)
**
** KnowNow Wikimedia 1.17 extension by Alfredo Di Maria - http://www.alfredodimaria.it - linuxloverstaff (at) gmail.com
** This extension lists recent revisions of the current page and of the wiki. Ajax functions update the box every minute.
*/
	// ensure that the script can't be executed outside of MediaWiki
if ( ! defined( 'MEDIAWIKI' ) ) {
	echo "Not a valid entry point";
	exit( 1 );
}
	// Admin have to enable the extension after require_once
	// DEL $wgKnowNowConfigEnabled = TRUE;
	// How many revision in the box? Please write n - 1. If you want 10 revision, write 9.
	$wgKnowNow_2 = 9;
	$wgExtensionCredits['validextensionclass'][] = array(
		'path' => __FILE__,
		'name' => 'KnowKnow',
		'author' =>'Alfredo Di Maria', 
		'url' => 'http://www.mediawiki.org/wiki/User:Admdebian', 
		'description' => 'This extension claims to show user the real time wiki contnent update',
		'version'  => 0.1,
		);
	$wgExtensionMessagesFiles['knownow'] = dirname( __FILE__ ) . '/KnowNow.i18n.php';
/* We don't want to repat this function every time the hook is called. We want to call it one*/
if(!isset($wgKnowNow_1)) {
	$wgKnowNow_1 = FALSE;
}
if(!isset($wgKnowNow_3)) {
	$wgKnowNow_3 = FALSE;
}


$wgHooks['ParserAfterTidy'][] = 'KnowNow_recent_revisions';
$wgHooks['ParserAfterTidy'][] = 'KnowNow_global_events';
$wgHooks['BeforePageDisplay'][] = 'KnowNow_css';
$wgHooks['AjaxAddScript'][] = 'KnowNow_js';
$wgAjaxExportList[] = 'wfAjaxQueryKnowNow';
$wgAjaxExportList[] = 'wfAjaxQueryKnowNow_2';

$dir = dirname( __FILE__ ) . '/';
require_once( $dir . 'KnowNow.body.php' );
