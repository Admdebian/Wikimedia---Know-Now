/*
** License: GPL (http://www.gnu.org/copyleft/gpl.html)
**
** KnowNow Wikimedia 1.17 extension by Alfredo Di Maria - http://www.alfredodimaria.it - linuxloverstaff (at) gmail.com
** This extension lists recent revisions of the current page and of the wiki. Ajax functions update the box every minute.
*/
/* Let's read the last edit to the page*/
var wgAjaxQueryKnowNow = {};
wgAjaxQueryKnowNow.inprogress = false;

function wgAjaxKnowNow_res( res ) {
	var res_arr = Array();
	res_arr = res.responseText.split('|');
	if ( res_arr[0] != '0' ) {
		document.getElementById( 'wgKnowNowTouch' ).value = res_arr[1];
		if ( document.getElementById( 'KnowNow_recentMod' ) ) {
			document.getElementById( 'KnowNow_recentMod' ).innerHTML = res_arr[0] + document.getElementById( 'KnowNow_recentMod' ).innerHTML;
		}
	}
	return true;
}

function KnowNow_ajax_page_revisions() {
	if ( document.getElementById( 'wgKnowNowTouch' ) && document.getElementById( 'wgKnowNowArticleId') && wgAjaxQueryKnowNow.inprogress == false ) {
		wgAjaxQueryKnowNow.inprogress = true;
		sajax_do_call(
			"wfAjaxQueryKnowNow_2",
			[document.getElementById( 'wgKnowNowArticleId').value, document.getElementById( 'wgKnowNowTouch' ).value ],
			wgAjaxKnowNow_res
			);
	}
	wgAjaxQueryKnowNow.inprogress = false;
	setTimeout( 'KnowNow_ajax_page_revisions()', 60000);
	return true;
}
// Every 60 seconds, we check for revisions
setTimeout( 'KnowNow_ajax_page_revisions()', 60000);

/* Here we control the last edit to the wall wiki*/

var wgAjaxQueryKnowNow_2 = {};
wgAjaxQueryKnowNow_2.inprogress = false;

function wgAjaxKnowNow_glob( res ) {
	var res_arr = Array();
	res_arr = res.responseText.split('|');
	if ( res_arr[0] != '0' && res_arr[1] > 0 ) {
		document.getElementById( 'last_rev_timestamp' ).value = res_arr[1];
		if ( document.getElementById( 'KnowNow_globalMod' ) ) {
			document.getElementById( 'KnowNow_globalMod' ).innerHTML = res_arr[0] + document.getElementById( 'KnowNow_globalMod' ).innerHTML;
		}
	}
	return true;
}

function KnowNow_ajax_glob_revisions() {
	if ( document.getElementById( 'last_rev_timestamp')  && wgAjaxQueryKnowNow_2.inprogress == false ) {
		wgAjaxQueryKnowNow_2.inprogress = true;
		sajax_do_call(
			"wfAjaxQueryKnowNow_2",
			[document.getElementById( 'last_rev_timestamp').value],
			wgAjaxKnowNow_glob
			);
	}
	wgAjaxQueryKnowNow_2.inprogress = false;
	setTimeout( 'KnowNow_ajax_glob_revisions()', 30000);
	return true;
}
// Every 30 seconds, we check for revisions
setTimeout( 'KnowNow_ajax_glob_revisions()', 30000);



