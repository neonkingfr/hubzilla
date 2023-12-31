<?php /** @file */

use Zotlabs\Lib\Apps;
use Zotlabs\Lib\Activity;

require_once('include/items.php');


function item_extract_images($body) {

	$saved_image = array();
	$orig_body = $body;
	$new_body = '';

	$cnt = 0;
	$img_start = strpos($orig_body, '[img');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	while(($img_st_close !== false) && ($img_end !== false)) {

		$img_st_close++; // make it point to AFTER the closing bracket
		$img_end += $img_start;

		if(! strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
			// This is an embedded image

			$saved_image[$cnt] = substr($orig_body, $img_start + $img_st_close, $img_end - ($img_start + $img_st_close));
			$new_body = $new_body . substr($orig_body, 0, $img_start) . '[!#saved_image' . $cnt . '#!]';

			$cnt++;
		}
		else
			$new_body = $new_body . substr($orig_body, 0, $img_end + strlen('[/img]'));

		$orig_body = substr($orig_body, $img_end + strlen('[/img]'));

		if($orig_body === false) // in case the body ends on a closing image tag
			$orig_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	}

	$new_body = $new_body . $orig_body;

	return array('body' => $new_body, 'images' => $saved_image);
}


function item_redir_and_replace_images($body, $images, $cid) {

	$origbody = $body;
	$newbody = '';

	$observer = App::get_observer();
	$obhash = (($observer) ? $observer['xchan_hash'] : '');
	$obaddr = (($observer) ? $observer['xchan_addr'] : '');

	for($i = 0; $i < count($images); $i++) {
		$search = '/\[url\=(.*?)\]\[!#saved_image' . $i . '#!\]\[\/url\]' . '/is';
		$replace = '[url=' . magiclink_url($obhash,$obaddr,'$1') . '][!#saved_image' . $i . '#!][/url]' ;

		$img_end = strpos($origbody, '[!#saved_image' . $i . '#!][/url]') + strlen('[!#saved_image' . $i . '#!][/url]');
		$process_part = substr($origbody, 0, $img_end);
		$origbody = substr($origbody, $img_end);

		$process_part = preg_replace($search, $replace, $process_part);
		$newbody = $newbody . $process_part;
	}
	$newbody = $newbody . $origbody;

	$cnt = 0;
	foreach($images as $image) {
		// We're depending on the property of 'foreach' (specified on the PHP website) that
		// it loops over the array starting from the first element and going sequentially
		// to the last element
		$newbody = str_replace('[!#saved_image' . $cnt . '#!]', '[img]' . $image . '[/img]', $newbody);
		$cnt++;
	}

	return $newbody;
}



/**
 * Render actions localized
 */

function localize_item(&$item){

	if (activity_match($item['verb'],ACTIVITY_LIKE) || activity_match($item['verb'],ACTIVITY_DISLIKE)){
		if(! $item['obj'])
			return;

		if(intval($item['item_thread_top']))
			return;

		$obj = json_decode($item['obj'],true);
		if((! $obj) && ($item['obj'])) {
			logger('localize_item: failed to decode object: ' . print_r($item['obj'],true));
		}

		if(isset($obj['author']) && isset($obj['author']['link']))
			$author_link = get_rel_link($obj['author']['link'],'alternate');
		elseif(isset($obj['actor']) && isset($obj['actor']['url']))
			$author_link = ((is_array($obj['actor']['url'])) ? $obj['actor']['url'][0]['href'] : $obj['actor']['url']);
		elseif (isset($obj['actor']) && is_string($obj['actor']))
			$author_link = $obj['actor'];
		elseif (isset($obj['attributedTo']) && is_string($obj['attributedTo']) && $obj['attributedTo'])
			$author_link = $obj['attributedTo'];
		else
			$author_link = '';

		$author_name = $obj['author']['name'] ?? '';

		if(!$author_name)
			$author_name = $obj['actor']['name'] ?? '';

		if(!$author_name && isset($obj['actor']) && is_string($obj['actor'])) {
			$cached_actor = Activity::get_cached_actor($obj['actor']);
			if (is_array($cached_actor)) {
				$author_name = $cached_actor['name'] ?? $cached_actor['preferredUsername'];
			}
		}

		if(!$author_name && isset($obj['attributedTo']) && is_string($obj['attributedTo'])) {
			$cached_actor = Activity::get_cached_actor($obj['attributedTo']);
			if (is_array($cached_actor)) {
				$author_name = $cached_actor['name'] ?? $cached_actor['preferredUsername'];
			}
		}

		$item_url = '';
		if(isset($obj['link']) && is_array($obj['link']))
			$item_url = get_rel_link($obj['link'],'alternate');

		if(!$item_url)
			$item_url = $obj['id'];

		$Bphoto = '';

		switch($obj['type']) {
			case ACTIVITY_OBJ_PHOTO:
			case 'Photo':
				$post_type = t('photo');
				break;
			case ACTIVITY_OBJ_EVENT:
			case 'Event':
				$post_type = t('event');
				break;
			case ACTIVITY_OBJ_PERSON:
				$post_type = t('channel');
				$author_name = $obj['title'];
				if($obj['link']) {
					$author_link  = get_rel_link($obj['link'],'alternate');
					$Bphoto = get_rel_link($obj['link'],'photo');
				}
				break;
			case ACTIVITY_OBJ_THING:
				$post_type = $obj['title'];
				if($obj['owner']) {
					if(array_key_exists('name',$obj['owner']))
						$obj['owner']['name'];
					if(array_key_exists('link',$obj['owner']))
						$author_link = get_rel_link($obj['owner']['link'],'alternate');
				}
				if($obj['link']) {
					$Bphoto = get_rel_link($obj['link'],'photo');
				}
				break;

			case ACTIVITY_OBJ_NOTE:
			case 'Note':
			default:
				$post_type = t('post');
				if(((isset($obj['parent']) && isset($obj['id']) && $obj['id'] != $obj['parent'])) || isset($obj['inReplyTo']))
					$post_type = t('comment');
				break;
		}

		// If we couldn't parse something useful, don't bother translating.
		// We need something better than zid here, probably magic_link(), but it needs writing

		if($author_link && $author_name && $item_url) {
			$author	 = '[zrl=' . chanlink_url($item['author']['xchan_url']) . ']' . $item['author']['xchan_name'] . '[/zrl]';
			$objauthor =  '[zrl=' . chanlink_url($author_link) . ']' . $author_name . '[/zrl]';

			$plink = '[zrl=' . zid($item_url) . ']' . $post_type . '[/zrl]';

			if(activity_match($item['verb'],ACTIVITY_LIKE)) {
				$bodyverb = t('%1$s likes %2$s\'s %3$s');
			}
			elseif(activity_match($item['verb'],ACTIVITY_DISLIKE)) {
				$bodyverb = t('%1$s doesn\'t like %2$s\'s %3$s');
			}

			// short version, in notification strings the author will be displayed separately

			if(activity_match($item['verb'],ACTIVITY_LIKE)) {
				$shortbodyverb = t('likes %1$s\'s %2$s');
			}
			elseif(activity_match($item['verb'],ACTIVITY_DISLIKE)) {
				$shortbodyverb = t('doesn\'t like %1$s\'s %2$s');
			}

			$item['shortlocalize'] = sprintf($shortbodyverb, '[bdi]' . $author_name . '[/bdi]', $post_type);

			$item['body'] = $item['localize'] = sprintf($bodyverb, '[bdi]' . $author . '[/bdi]', '[bdi]' . $objauthor . '[/bdi]', $plink);
			if($Bphoto != "")
				$item['body'] .= "\n\n\n" . '[zrl=' . chanlink_url($author_link) . '][zmg=80x80]' . $Bphoto . '[/zmg][/zrl]';

		}
		else {
			logger('localize_item like failed: link ' . $author_link . ' name ' . $author_name . ' url ' . $item_url);
		}

	}

	if (activity_match($item['verb'],ACTIVITY_FRIEND)) {

		if ($item['obj_type'] == "" || $item['obj_type'] !== ACTIVITY_OBJ_PERSON)
			return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];


		$obj= json_decode($item['obj'],true);

		$Blink = $Bphoto = '';

		if($obj['link']) {
			$Blink  = get_rel_link($obj['link'],'alternate');
			$Bphoto = get_rel_link($obj['link'],'photo');
		}
		$Bname = $obj['title'];


		$A = '[zrl=' . chanlink_url($Alink) . '][bdi]' . $Aname . '[/bdi][/zrl]';
		$B = '[zrl=' . chanlink_url($Blink) . '][bdi]' . $Bname . '[/bdi][/zrl]';
		if ($Bphoto!="") $Bphoto = '[zrl=' . chanlink_url($Blink) . '][zmg=80x80]' . $Bphoto . '[/zmg][/zrl]';

		$item['shortlocalize'] = sprintf( t('%1$s is now connected with %2$s'), '[bdi]' . $Aname . '[/bdi]', '[bdi]' . $Bname . '[/bdi]');

		$item['body'] = $item['localize'] = sprintf( t('%1$s is now connected with %2$s'), $A, $B);
		$item['body'] .= "\n\n\n" . $Bphoto;
	}

	if (stristr($item['verb'], ACTIVITY_POKE)) {

		/** @FIXME for obscured private posts, until then leave untranslated */
		return;

		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if(! $verb)
			return;

		if ($item['obj_type']=="" || $item['obj_type']!== ACTIVITY_OBJ_PERSON) return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];

		$obj= json_decode($item['obj'],true);

		$Blink = $Bphoto = '';

		if($obj['link']) {
			$Blink  = get_rel_link($obj['link'],'alternate');
			$Bphoto = get_rel_link($obj['link'],'photo');
		}
		$Bname = $obj['title'];

		$A = '[zrl=' . chanlink_url($Alink) . '][bdi]' . $Aname . '[/bdi][/zrl]';
		$B = '[zrl=' . chanlink_url($Blink) . '][bdi]' . $Bname . '[/bdi][/zrl]';
		if ($Bphoto!="") $Bphoto = '[zrl=' . chanlink_url($Blink) . '][zmg=80x80]' . $Bphoto . '[/zmg][/zrl]';

		// we can't have a translation string with three positions but no distinguishable text
		// So here is the translate string.

		$txt = t('%1$s poked %2$s');

		// now translate the verb

		$txt = str_replace( t('poked'), t($verb), $txt);

		// then do the sprintf on the translation string

		$item['shortlocalize'] = sprintf($txt, '[bdi]' . $Aname . '[/bdi]', '[bdi]' . $Bname . '[/bdi]');

		$item['body'] = $item['localize'] = sprintf($txt, $A, $B);
		$item['body'] .= "\n\n\n" . $Bphoto;
	}
	if (stristr($item['verb'],ACTIVITY_MOOD)) {
		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if(! $verb)
			return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];

		$A = '[zrl=' . chanlink_url($Alink) . '][bdi]' . $Aname . '[/bdi][/zrl]';

		$txt = t('%1$s is %2$s','mood');

		$item['body'] = sprintf($txt, $A, t($verb));
	}



/*
// FIXME store parent item as object or target
// (and update to json storage)

 	if (activity_match($item['verb'],ACTIVITY_TAG)) {
		$r = q("SELECT * from item,contact WHERE
		item.contact-id=contact.id AND item.mid='%s';",
		 dbesc($item['parent_mid']));
		if(count($r)==0) return;
		$obj=$r[0];

		$author	 = '[zrl=' . zid($item['author-link']) . ']' . $item['author-name'] . '[/zrl]';
		$objauthor =  '[zrl=' . zid($obj['author-link']) . ']' . $obj['author-name'] . '[/zrl]';

		switch($obj['verb']){
			case ACTIVITY_POST:
				switch ($obj['obj_type']){
					case ACTIVITY_OBJ_EVENT:
						$post_type = t('event');
						break;
					default:
						$post_type = t('status');
				}
				break;
			default:
				if($obj['resource_id']){
					$post_type = t('photo');
					$m=array(); preg_match("/\[[zu]rl=([^]]*)\]/", $obj['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = t('status');
				}
		}
		$plink = '[zrl=' . $obj['plink'] . ']' . $post_type . '[/zrl]';

//		$parsedobj = parse_xml_string($xmlhead.$item['obj']);

		$tag = sprintf('#[zrl=%s]%s[/zrl]', $parsedobj->id, $parsedobj->content);
		$item['body'] = sprintf( t('%1$s tagged %2$s\'s %3$s with %4$s'), $author, $objauthor, $plink, $tag );

	}

	if (activity_match($item['verb'],ACTIVITY_FAVORITE)){

		if ($item['obj_type']== "")
			return;

		$Aname = $item['author']['xchan_name'];
		$Alink = $item['author']['xchan_url'];

		$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";

//		$obj = parse_xml_string($xmlhead.$item['obj']);
		if(strlen($obj->id)) {
			$r = q("select * from item where mid = '%s' and uid = %d limit 1",
					dbesc($obj->id),
					intval($item['uid'])
			);
			if(count($r) && $r[0]['plink']) {
				$target = $r[0];
				$Bname = $target['author-name'];
				$Blink = $target['author-link'];
				$A = '[zrl=' . zid($Alink) . ']' . $Aname . '[/zrl]';
				$B = '[zrl=' . zid($Blink) . ']' . $Bname . '[/zrl]';
				$P = '[zrl=' . $target['plink'] . ']' . t('post/item') . '[/zrl]';
				$item['body'] = sprintf( t('%1$s marked %2$s\'s %3$s as favorite'), $A, $B, $P)."\n";

			}
		}
	}
*/

/*
	$matches = null;
	if(strpos($item['body'],'[zrl') !== false) {
		if(preg_match_all('/@\[zrl=(.*?)\]/is',$item['body'],$matches,PREG_SET_ORDER)) {
			foreach($matches as $mtch) {
				if(! strpos($mtch[1],'zid='))
					$item['body'] = str_replace($mtch[0],'@[zrl=' . zid($mtch[1]). ']',$item['body']);
			}
		}
	}

	if(strpos($item['body'],'[zmg') !== false) {
		// add zid's to public images
		if(preg_match_all('/\[zrl=(.*?)\/photos\/(.*?)\/image\/(.*?)\]\[zmg(.*?)\]h(.*?)\[\/zmg\]\[\/zrl\]/is',$item['body'],$matches,PREG_SET_ORDER)) {
			foreach($matches as $mtch) {
				$item['body'] = str_replace($mtch[0],'[zrl=' . zid( $mtch[1] . '/photos/' . $mtch[2] . '/image/' . $mtch[3]) . '][zmg' . $mtch[4] . ']h' . $mtch[5]  . '[/zmg][/zrl]',$item['body']);
			}
		}
	}
*/


}

/**
 * @brief Count the total of comments on this item and its desendants.
 *
 * @param array $item an assoziative item-array which provides:
 *  * \e array \b children
 * @return number
 */

function count_descendants($item) {

	$total = count($item['children']);

	if($total > 0) {
		foreach($item['children'] as $child) {
			if(! visible_activity($child))
				$total --;

			$total += count_descendants($child);
		}
	}

	return $total;
}

/**
 * @brief Check if the activity of the item is visible.
 *
 * likes (etc.) can apply to other things besides posts. Check if they are post
 * children, in which case we handle them specially. Activities which are unrecognised
 * as having special meaning and hidden will be treated as posts or comments and visible
 * in the stream.
 *
 * @param array $item
 * @return boolean
 */
function visible_activity($item) {
	$hidden_activities = [ ACTIVITY_LIKE, ACTIVITY_DISLIKE, ACTIVITY_AGREE, ACTIVITY_DISAGREE, ACTIVITY_ABSTAIN, ACTIVITY_ATTEND, ACTIVITY_ATTENDNO, ACTIVITY_ATTENDMAYBE, ACTIVITY_POLLRESPONSE ];

	if(intval($item['item_notshown']))
		return false;

	if ($item['obj_type'] === 'Answer') {
		return false;
	}

	foreach($hidden_activities as $act) {
		if((activity_match($item['verb'], $act)) && ($item['mid'] != $item['parent_mid'])) {
			return false;
		}
	}

	// We only need edit activities for other federated protocols
	// which do not support edits natively. While this does federate
	// edits, it presents a number of issues locally - such as #757 and #758.
	// The SQL check for an edit activity would not perform that well so to fix these issues
	// requires an additional item flag (perhaps 'item_edit_activity') that we can add to the
	// query for searches and notifications.

	// For now we'll just forget about trying to make edits work on network protocols that
	// don't support them.

	// if(is_edit_activity($item))
	//	return false;

	return true;
}

/**
 * @brief Check if a given activity is an edit activity
 *
 *
 * @param array $item
 * @return boolean
 */

function is_edit_activity($item) {

	$post_types = [ ACTIVITY_OBJ_NOTE, ACTIVITY_OBJ_COMMENT, basename(ACTIVITY_OBJ_NOTE), basename(ACTIVITY_OBJ_COMMENT)];

	// In order to share edits with networks which have no concept of editing, we'll create
	// separate activities to indicate the edit. Our network will not require them, since our
	// edits are automatically applied and the activity indicated.

	if(($item['verb'] === ACTIVITY_UPDATE) && (in_array($item['obj_type'],$post_types)))
		return true;

	return false;
}

/**
 * @brief "Render" a conversation or list of items for HTML display.
 *
 * There are two major forms of display:
 *  - Sequential or unthreaded ("New Item View" or search results)
 *  - conversation view
 *
 * The $mode parameter decides between the various renderings and also
 * figures out how to determine page owner and other contextual items
 * that are based on unique features of the calling module.
 *
 * @param array $items
 * @param string $mode
 * @param boolean $update
 * @param string $page_mode default traditional
 * @param string $prepared_item
 * @return string
 */
function conversation($items, $mode, $update, $page_mode = 'traditional', $prepared_item = '') {

	$content_html = '';
	$o = '';

	require_once('bbcode.php');

	$ssl_state = ((local_channel()) ? true : false);

	if (local_channel())
		load_pconfig(local_channel(),'');

	$profile_owner   = 0;
	$page_writeable  = false;
	$live_update_div = '';
	$jsreload        = '';

	$preview = (($page_mode === 'preview') ? true : false);
	$r_preview = (($page_mode === 'r_preview') ? true : false);
	$previewing = (($preview) ? ' preview ' : '');
	$preview_lbl = t('This is an unsaved preview');

	if (in_array($mode, [ 'network', 'pubstream'])) {

		$profile_owner = local_channel();
		$page_writeable = ((local_channel()) ? true : false);

		if (!$update) {
			// The special div is needed for liveUpdate to kick in for this page.
			// We only launch liveUpdate if you aren't filtering in some incompatible
			// way and also you aren't writing a comment (discovered in javascript).

			$live_update_div = '<div id="live-network"></div>' . "\r\n"
				. "<script> var profile_uid = " . $_SESSION['uid']
				. "; var netargs = '" . substr(App::$cmd,8)
				. '?f='
				. ((x($_GET,'cid'))    ? '&cid='    . $_GET['cid']    : '')
				. ((x($_GET,'search')) ? '&search=' . $_GET['search'] : '')
				. ((x($_GET,'star'))   ? '&star='   . $_GET['star']   : '')
				. ((x($_GET,'order'))  ? '&order='  . $_GET['order']  : '')
				. ((x($_GET,'bmark'))  ? '&bmark='  . $_GET['bmark']  : '')
				. ((x($_GET,'liked'))  ? '&liked='  . $_GET['liked']  : '')
				. ((x($_GET,'conv'))   ? '&conv='   . $_GET['conv']   : '')
				. ((x($_GET,'spam'))   ? '&spam='   . $_GET['spam']   : '')
				. ((x($_GET,'nets'))   ? '&nets='   . $_GET['nets']   : '')
				. ((x($_GET,'cmin'))   ? '&cmin='   . $_GET['cmin']   : '')
				. ((x($_GET,'cmax'))   ? '&cmax='   . $_GET['cmax']   : '')
				. ((x($_GET,'file'))   ? '&file='   . $_GET['file']   : '')
				. ((x($_GET,'uri'))    ? '&uri='    . $_GET['uri']   : '')
				. ((x($_GET,'pf'))     ? '&pf='     . $_GET['pf']   : '')
				. "'; var profile_page = " . App::$pager['page'] . "; </script>\r\n";
		}
	}

	elseif ($mode === 'hq') {
		$profile_owner = local_channel();
		$page_writeable = true;
		$live_update_div = '<div id="live-hq"></div>' . "\r\n";
	}

	elseif ($mode === 'channel') {
		$profile_owner = App::$profile['profile_uid'];
		$page_writeable = ($profile_owner == local_channel());

		if (!$update) {
			// This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
			// because browser prefetching might change it on us. We have to deliver it with the page.

			$live_update_div = '<div id="live-channel"></div>' . "\r\n"
				. "<script> var profile_uid = " . App::$profile['profile_uid']
				. "; var netargs = '?f='; var profile_page = " . App::$pager['page'] . "; </script>\r\n";
		}
	}

	elseif ($mode === 'cards') {
		$profile_owner = App::$profile['profile_uid'];
		$page_writeable = ($profile_owner == local_channel());
		$live_update_div = '<div id="live-cards"></div>' . "\r\n"
			. "<script> var profile_uid = " . App::$profile['profile_uid']
			. "; var netargs = '?f='; var profile_page = " . App::$pager['page'] . "; </script>\r\n";
		$jsreload = $_SESSION['return_url'];
	}

	elseif ($mode === 'articles') {
		$profile_owner = App::$profile['profile_uid'];
		$page_writeable = ($profile_owner == local_channel());
		$live_update_div = '<div id="live-articles"></div>' . "\r\n"
			. "<script> var profile_uid = " . App::$profile['profile_uid']
			. "; var netargs = '?f='; var profile_page = " . App::$pager['page'] . "; </script>\r\n";
		$jsreload = $_SESSION['return_url'];
	}


	elseif ($mode === 'display') {
		$profile_owner = local_channel();
		$page_writeable = false;
		$live_update_div = '<div id="live-display"></div>' . "\r\n";
	}

	elseif ($mode === 'page') {
		$profile_owner = App::$profile['uid'];
		$page_writeable = ($profile_owner == local_channel());
		$live_update_div = '<div id="live-page"></div>' . "\r\n";
	}

	elseif ($mode === 'search') {
		$live_update_div = '<div id="live-search"></div>' . "\r\n";
	}

	elseif ($mode === 'moderate') {
		$profile_owner = local_channel();
	}

	elseif ($mode === 'photos') {
		$profile_owner = App::$profile['profile_uid'];
		$page_writeable = ($profile_owner == local_channel());
		$live_update_div = '<div id="live-photos"></div>' . "\r\n";
		// for photos we've already formatted the top-level item (the photo)
		$content_html = App::$data['photo_html'];
	}

	$page_dropping = ((local_channel() && local_channel() == $profile_owner) ? true : false);

	if (! feature_enabled($profile_owner,'multi_delete'))
		$page_dropping = false;

	$uploading = false;

	if(local_channel()) {
		$cur_channel = App::get_channel();
		if($cur_channel['channel_allow_cid'] === '' &&  $cur_channel['channel_allow_gid'] === ''
			&& $cur_channel['channel_deny_cid'] === '' && $cur_channel['channel_deny_gid'] === ''
			&& intval(\Zotlabs\Access\PermissionLimits::Get(local_channel(),'view_storage')) === PERMS_PUBLIC) {
			$uploading = true;
		}
	}

	$channel = App::get_channel();
	$observer = App::get_observer();

	if (!$update) {
		$_SESSION['return_url'] = App::$query_string;
	}

	load_contact_links(local_channel());

	$cb = array('items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => $preview);
	call_hooks('conversation_start',$cb);

	$items = $cb['items'];

	$conv_responses = [
		'like' => ['title' => t('Likes','title')],
		'dislike' => ['title' => t('Dislikes','title')],
		'agree' => ['title' => t('Agree','title')],
		'disagree' => ['title' => t('Disagree','title')],
		'abstain' => ['title' => t('Abstain','title')],
		'attendyes' => ['title' => t('Attending','title')],
		'attendno' => ['title' => t('Not attending','title')],
		'attendmaybe' => ['title' => t('Might attend','title')],
		'answer' => []
	];


	// array with html for each thread (parent+comments)
	$threads = array();
	$threadsid = -1;

	$page_template = get_markup_template("conversation.tpl");

	if($items) {

		if(is_unthreaded($mode)) {

			// "New Item View" on network page or search page results
			// - just loop through the items and format them minimally for display

			$tpl = 'search_item.tpl';

			foreach($items as $item) {

				$x = [
					'mode' => $mode,
					'item' => $item
				];
				call_hooks('stream_item',$x);

				if(isset($x['item']['blocked']) && $x['item']['blocked'])
					continue;

				$item = $x['item'];

				$threadsid++;

				$comment     = '';
				$owner_url   = '';
				$owner_photo = '';
				$owner_name  = '';
				$sparkle     = '';
				$is_new      = false;

				if($mode === 'search' || $mode === 'community') {
					if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE)))
						&& ($item['id'] != $item['parent']))
						continue;
				}

				$sp = false;
				$profile_link = best_link_url($item,$sp);
				if($sp)
					$sparkle = ' sparkle';
				else
					$profile_link = zid($profile_link);

				$profile_name = $item['author']['xchan_name'];
				$profile_link = $item['author']['xchan_url'];
				$profile_avatar = $item['author']['xchan_photo_m'];

				$location = format_location($item);

				localize_item($item);
				if($mode === 'network-new')
					$dropping = true;
				else
					$dropping = false;

				$drop = array(
					'pagedropping' => $page_dropping,
					'dropping' => $dropping,
					'select' => t('Select'),
					'delete' => t('Delete'),
				);

				$star = [];
				if ((local_channel() && local_channel() === intval($item['uid'])) && intval($item['item_thread_top']) && feature_enabled(local_channel(), 'star_posts')) {
					$star = [
						'toggle' => t("Toggle Star Status"),
						'isstarred' => ((intval($item['item_starred'])) ? true : false),
					];
				}

				$lock = (($item['item_private'] || strlen($item['allow_cid']) || strlen($item['allow_gid']) || strlen($item['deny_cid']) || strlen($item['deny_gid']))
					? t('Private Message')
					: false
				);
				$locktype = $item['item_private'];


				$likebuttons = false;
				$shareable = false;

				$verified = (intval($item['item_verified']) ? t('Message signature validated') : '');
				$forged = ((($item['sig']) && (! intval($item['item_verified']))) ? t('Message signature incorrect') : '');

				$unverified = '';

//				$tags=array();
//				$terms = get_terms_oftype($item['term'],array(TERM_HASHTAG,TERM_MENTION,TERM_UNKNOWN,TERM_COMMUNITYTAG));
//				if(count($terms))
//					foreach($terms as $tag)
//						$tags[] = format_term_for_display($tag);

				$body = prepare_body($item,true);

				$has_tags = (($body['tags'] || $body['categories'] || $body['mentions'] || $body['attachments'] || $body['folders']) ? true : false);

				if(strcmp(datetime_convert('UTC','UTC',$item['created']),datetime_convert('UTC','UTC','now - 12 hours')) > 0)
					$is_new = true;

				$conv_link_mid = (($mode == 'moderate') ? gen_link_id($item['parent_mid']) : gen_link_id($item['mid']));

				$conv_link = z_root() . '/display/' . $conv_link_mid;

				if(local_channel()) {
					$conv_link = z_root() . '/hq/' . $conv_link_mid;
				}

				if ($mode === 'pubstream-new') {
					$conv_link = z_root() . '/pubstream?mid=' . $conv_link_mid;
				}

				$contact = [];

				if(App::$contacts && array_key_exists($item['author_xchan'],App::$contacts)) {
					$contact = App::$contacts[$item['author_xchan']];
				}

				$tmp_item = array(
					'template' => $tpl,
					'toplevel' => 'toplevel_item',
					'item_type' => intval($item['item_type']),
					'mode' => $mode,
					'approve' => t('Approve'),
					'delete' => t('Delete'),
					'preview_lbl' => $preview_lbl,
					'id' => (($preview) ? 'P0' : $item['item_id']),
					'mid' => gen_link_id($item['mid']),
					'mids' => json_encode([gen_link_id($item['mid'])]),
					'linktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, $profile_link),
					'author_id' => (($item['author']['xchan_addr']) ? $item['author']['xchan_addr'] : $item['author']['xchan_url']),
					'profile_url' => $profile_link,
					'thread_action_menu' => thread_action_menu($item,$mode),
					'thread_author_menu' => thread_author_menu($item,$mode),
					'name' => $profile_name,
					'sparkle' => $sparkle,
					'lock' => $lock,
					'locktype' => $locktype,
					'thumb' => $profile_avatar,
					'title' => $item['title'],
					'body' => $body['html'],
					'event' => $body['event'],
					'photo' => $body['photo'],
					'tags' => $body['tags'],
					'categories' => $body['categories'],
					'mentions' => $body['mentions'],
					'attachments' => $body['attachments'],
					'folders' => $body['folders'],
					'verified' => $verified,
					'unverified' => $unverified,
					'forged' => $forged,
					'txt_cats' => t('Categories:'),
					'txt_folders' => t('Filed under:'),
					'has_cats' => (($body['categories']) ? 'true' : ''),
					'has_folders' => (($body['folders']) ? 'true' : ''),
					'text' => strip_tags($body['html']),
					'ago' => relative_date($item['created']),
					'app' => $item['app'],
					'str_app' => sprintf( t('from %s'), $item['app']),
					'isotime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'c'),
					'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
					'editedtime' => (($item['edited'] != $item['created']) ? sprintf( t('last edited: %s'), datetime_convert('UTC', date_default_timezone_get(), $item['edited'], 'r')) : ''),
					'expiretime' => (($item['expires'] > NULL_DATE) ? sprintf( t('Expires: %s'), datetime_convert('UTC', date_default_timezone_get(), $item['expires'], 'r')):''),
					'location' => $location,
					'divider' => false,
					'indent' => '',
					'owner_name' => $owner_name,
					'owner_url' => $owner_url,
					'owner_photo' => $owner_photo,
					'plink' => get_plink($item,false),
					'edpost' => false,
					'star' => $star,
					'drop' => $drop,
					'vote' => $likebuttons,
					'like' => '',
					'dislike' => '',
					'comment' => '',
					'conv' => (($preview) ? '' : array('href'=> $conv_link, 'title'=> t('View in context'))),
					'previewing' => $previewing,
					'wait' => t('Please wait'),
					'thread_level' => 1,
					'has_tags' => $has_tags,
					'is_new' => $is_new,
					'contact_id' => (($contact) ? $contact['abook_id'] : '')
				);

				$arr = array('item' => $item, 'output' => $tmp_item);
				call_hooks('display_item', $arr);

//				$threads[$threadsid]['id'] = $item['item_id'];
				$threads[] = $arr['output'];
			}
		}
		else {

			// Normal View
//			logger('conv: items: ' . print_r($items,true));

			$conv = new Zotlabs\Lib\ThreadStream($mode, $preview, $uploading, $prepared_item);

			// In the display mode we don't have a profile owner.

			if($mode === 'display' && $items)
				$conv->set_profile_owner($items[0]['uid']);

			// get all the topmost parents
			// this shouldn't be needed, as we should have only them in our array
			// But for now, this array respects the old style, just in case

			$threads = array();
			foreach($items as $item) {

				// Check for any blocked authors


				$x = [ 'mode' => $mode, 'item' => $item ];
				call_hooks('stream_item',$x);

				if(isset($x['item']['blocked']))
					continue;

				$item = $x['item'];

				builtin_activity_puller($item, $conv_responses);

				if(! visible_activity($item)) {
					continue;
				}



				$item['pagedrop'] = $page_dropping;

				if($item['id'] == $item['parent'] || $r_preview) {

					$item_object = new Zotlabs\Lib\ThreadItem($item);

					$conv->add_thread($item_object);
					if(($page_mode === 'list') || ($page_mode === 'pager_list')) {
						$item_object->set_template('conv_list.tpl');
						$item_object->set_display_mode('list');
					}
					if($mode === 'cards' || $mode === 'articles') {
						$item_object->set_reload($jsreload);
					}

				}
			}

			$threads = $conv->get_template_data($conv_responses);
			if(!$threads) {
				logger('[ERROR] conversation : Failed to get template data.', LOGGER_DEBUG);
				$threads = array();
			}
		}
	}

	if(in_array($page_mode, [ 'traditional', 'preview', 'pager_list'] )) {
		$page_template = get_markup_template("threaded_conversation.tpl");
	}
	elseif($update) {
		$page_template = get_markup_template("convobj.tpl");
	}
	else {
		$page_template = get_markup_template("conv_frame.tpl");
		$threads = null;
	}

//	if($page_mode === 'preview')
//		logger('preview: ' . print_r($threads,true));

//  Do not un-comment if smarty3 is in use
//	logger('page_template: ' . $page_template);

//	logger('nouveau: ' . print_r($threads,true));

	$o .= replace_macros($page_template, array(
		'$baseurl' => z_root(),
		'$photo_item' => $content_html,
		'$live_update' => $live_update_div,
		'$remove' => t('remove'),
		'$mode' => $mode,
		'$user' => App::$user,
		'$threads' => $threads,
		'$wait' => t('Loading...'),
		'$conversation_tools' => t('Conversation Features'),
		'$dropping' => ($page_dropping?t('Delete Selected Items'):False),
		'$preview' => $preview
	));

	return $o;
}


function best_link_url($item) {
	$best_url = $item['author-link'] ?? $item['url'] ?? '';
	$sparkle  = false;
	$clean_url = isset($item['author-link']) ? normalise_link($item['author-link']) : '';

	if($clean_url  && local_channel() && (local_channel() == $item['uid'])) {
		if(isset(App::$contacts) && x(App::$contacts, $clean_url)) {
			if(App::$contacts[$clean_url]['network'] === NETWORK_DFRN) {
				$best_url = z_root() . '/redir/' . App::$contacts[$clean_url]['id'];
				$sparkle = true;
			}
			else
				$best_url = App::$contacts[$clean_url]['url'];
		}
	}

	return $best_url;
}



function thread_action_menu($item,$mode = '') {

	$menu = [];

	if((local_channel()) && local_channel() == $item['uid']) {
		$menu[] = [
			'menu' => 'view_source',
			'title' => t('View Source'),
			'icon' => 'code',
			'action' => 'viewsrc(' . $item['id'] . '); return false;',
			'href' => '#'
		];

		if(!is_unthreaded($mode)) {
			if($item['parent'] == $item['id'] && (get_observer_hash() != $item['author_xchan'])) {
				$menu[] = [
					'menu' => 'follow_thread',
					'title' => t('Follow Thread'),
					'icon' => 'plus',
					'action' => 'dosubthread(' . $item['id'] . '); return false;',
					'href' => '#'
				];
			}

			$menu[] = [
				'menu' => 'unfollow_thread',
				'title' => t('Unfollow Thread'),
				'icon' => 'minus',
				'action' => 'dounsubthread(' . $item['id'] . '); return false;',
				'href' => '#'
			];
		}

	}




	$args = [ 'item' => $item, 'mode' => $mode, 'menu' => $menu ];
	call_hooks('thread_action_menu', $args);

	return $args['menu'];

}

function author_is_pmable($xchan, $abook) {

	$x = [ 'xchan' => $xchan, 'abook' => $abook, 'result' => 'unset' ];
	call_hooks('author_is_pmable',$x);
	if($x['result'] !== 'unset')
		return $x['result'];

	return false;

}






function thread_author_menu($item, $mode = '') {

	$menu = [];
	$channel = [];
	$local_channel = local_channel();

	if($local_channel) {
		if(! count(App::$contacts))
			load_contact_links($local_channel);

		$channel = App::get_channel();
	}

	$profile_link = chanlink_hash($item['author_xchan']);
	$contact = false;

	$follow_url = '';

	if(isset($channel['channel_hash']) && $channel['channel_hash'] !== $item['author_xchan']) {
		if(App::$contacts && array_key_exists($item['author_xchan'],App::$contacts)) {
			$contact = App::$contacts[$item['author_xchan']];
		}
		else {
			$url = (($item['author']['xchan_addr']) ? $item['author']['xchan_addr'] : $item['author']['xchan_url']);
			if($local_channel && $url && (! in_array($item['author']['xchan_network'],[ 'rss', 'anon','unknown', 'zot', 'token']))) {
				$follow_url = z_root() . '/follow/?f=&url=' . urlencode($url) . '&interactive=0';
			}
		}
	}


	$contact_url = '';
	$posts_link = '';
	$poke_link = '';

	if($contact) {
		$poke_link = ((Apps::system_app_installed($local_channel, 'Poke')) ? z_root() . '/poke/?f=&c=' . $contact['abook_id'] : '');
		if (isset($contact['abook_self']) && !intval($contact['abook_self']))
			$contact_url = z_root() . '/connections#' . $contact['abook_id'];
		$posts_link = z_root() . '/network/?cid=' . $contact['abook_id'];
	}

	if($profile_link) {
		$menu[] = [
			'menu' => 'view_profile',
			'title' => t('View Profile'),
			'icon' => 'fw',
			'action' => '',
			'href' => $profile_link,
			'data' => '',
			'class' => ''
		];
	}

	if($posts_link) {
		$menu[] = [
			'menu' => 'view_posts',
			'title' => t('Recent Activity'),
			'icon' => 'fw',
			'action' => '',
			'href' => $posts_link,
			'data' => '',
			'class' => ''
		];
	}

	if($follow_url) {
		$menu[] = [
			'menu' => 'follow',
			'title' => t('Connect'),
			'icon' => 'fw',
			'action' => 'doFollowAuthor(\'' . $follow_url . '\'); return false;',
			'href' => '#',
			'data' => '',
			'class' => ''
		];
	}

	if($contact_url) {
		$menu[] = [
			'menu' => 'connedit',
			'title' => t('Edit Connection'),
			'icon' => 'fw',
			'action' => '',
			'href' => $contact_url,
			'data' => 'data-id="' . $contact['abook_id'] . '"',
			'class' => 'contact-edit'
		];
	}

	if($poke_link) {
		$menu[] = [
			'menu' => 'poke',
			'title' => t('Poke'),
			'icon' => 'fw',
			'action' => '',
			'href' => $poke_link,
			'data' => '',
			'class' => ''
		];
	}

	$args = [ 'item' => $item, 'mode' => $mode, 'menu' => $menu ];
	call_hooks('thread_author_menu', $args);

	return $args['menu'];

}





/**
 * @brief Checks item to see if it is one of the builtin activities (like/dislike, event attendance, consensus items, etc.)
 *
 * Increments the count of each matching activity and adds a link to the author as needed.
 *
 * @param array $item
 * @param array &$conv_responses (already created with builtin activity structure)
 */
function builtin_activity_puller($item, &$conv_responses) {

	// if this item is a post or comment there's nothing for us to do here, just return.

	if(activity_match($item['verb'],ACTIVITY_POST) && $item['obj_type'] !== 'Answer')
		return;

	foreach($conv_responses as $mode => $v) {

		$url = '';

		switch($mode) {
			case 'like':
				$verb = ACTIVITY_LIKE;
				break;
			case 'dislike':
				$verb = ACTIVITY_DISLIKE;
				break;
			case 'agree':
				$verb = ACTIVITY_AGREE;
				break;
			case 'disagree':
				$verb = ACTIVITY_DISAGREE;
				break;
			case 'abstain':
				$verb = ACTIVITY_ABSTAIN;
				break;
			case 'attendyes':
				$verb = ACTIVITY_ATTEND;
				break;
			case 'attendno':
				$verb = ACTIVITY_ATTENDNO;
				break;
			case 'attendmaybe':
				$verb = ACTIVITY_ATTENDMAYBE;
				break;
			case 'answer':
				$verb = ACTIVITY_POST;
				break;
			default:
				return;
				break;
		}

		if((activity_match($item['verb'], $verb)) && ($item['id'] != $item['parent'])) {
			$name = (($item['author']['xchan_name']) ? $item['author']['xchan_name'] : t('Unknown'));

			$moderate = ((intval($item['item_blocked']) === ITEM_MODERATED) ? '<a href="moderate/' . $item['id'] . '/approve" onclick="moderate_approve(' . $item['id'] . '); return false;" class="text-success pe-2" title="' . t('Approve this item') . '"><i class="fa fa-check" ></i></a><a href="moderate/' . $item['id'] . '/drop" onclick="moderate_drop(' . $item['id'] . '); return false;" class="text-danger pe-2" title="' . t('Delete this item') . '"><i class="fa fa-trash-o" ></i></a>' : '');

			$url = (($item['author_xchan'] && $item['author']['xchan_photo_s'])
				? '<div class="dropdown-item">' . $moderate . '<a href="' . chanlink_hash($item['author_xchan']) . '" class="text-reset">' . '<img class="menu-img-1" src="' . zid($item['author']['xchan_photo_s'])  . '" alt="' . urlencode($name) . '" /> ' . $name . '</a></div>'
				: '<a class="dropdown-item" href="#" class="disabled">' . $name . '</a>'
			);



			if(! $item['thr_parent'])
				$item['thr_parent'] = $item['parent_mid'];

			$conv_responses[$mode]['mids'][$item['thr_parent']][] = gen_link_id($item['mid']);

			if($item['obj_type'] === 'Answer')
				continue;

			if(! ((isset($conv_responses[$mode][$item['thr_parent'] . '-l']))
				&& (is_array($conv_responses[$mode][$item['thr_parent'] . '-l']))))
				$conv_responses[$mode][$item['thr_parent'] . '-l'] = array();

			// only list each unique author once
			if(in_array($url,$conv_responses[$mode][$item['thr_parent'] . '-l']))
				continue;

			if(! isset($conv_responses[$mode][$item['thr_parent']]))
				$conv_responses[$mode][$item['thr_parent']] = 1;
			else
				$conv_responses[$mode][$item['thr_parent']] ++;

			$conv_responses[$mode][$item['thr_parent'] . '-l'][] = $url;
			if(get_observer_hash() && get_observer_hash() === $item['author_xchan']) {
				$conv_responses[$mode][$item['thr_parent'] . '-m'] = true;
			}

			// there can only be one activity verb per item so if we found anything, we can stop looking
			return;
		}
	}
}


/**
 * @brief Format the like/dislike text for a profile item.
 *
 * @param int $cnt number of people who like/dislike the item
 * @param array $arr array of pre-linked names of likers/dislikers
 * @param string $type one of 'like, 'dislike'
 * @param int $id item id
 * @return string formatted text
 */
function format_like($cnt, $arr, $type, $id) {
	$o = '';
	if ($cnt == 1) {
		$o .= (($type === 'like') ? sprintf( t('%s likes this.'), $arr[0]) : sprintf( t('%s doesn\'t like this.'), $arr[0])) . EOL ;
	} else {
		$spanatts = 'class="fakelink" onclick="openClose(\'' . $type . 'list-' . $id . '\');"';
		$o .= (($type === 'like') ?
					sprintf( tt('<span  %1$s>%2$d people</span> like this.','<span  %1$s>%2$d people</span> like this.',$cnt), $spanatts, $cnt)
					 :
					sprintf( tt('<span  %1$s>%2$d people</span> don\'t like this.','<span  %1$s>%2$d people</span> don\'t like this.',$cnt), $spanatts, $cnt) );
		$o .= EOL;
		$total = count($arr);
		if($total >= MAX_LIKERS)
			$arr = array_slice($arr, 0, MAX_LIKERS - 1);
		if($total < MAX_LIKERS)
			$arr[count($arr)-1] = t('and') . ' ' . $arr[count($arr)-1];
		$str = implode(', ', $arr);
		if($total >= MAX_LIKERS)
			$str .= sprintf( tt(', and %d other people',', and %d other people',$total - MAX_LIKERS), $total - MAX_LIKERS );
		$str = (($type === 'like') ? sprintf( t('%s like this.'), $str) : sprintf( t('%s don\'t like this.'), $str));
		$o .= "\t" . '<div id="' . $type . 'list-' . $id . '" style="display: none;" >' . $str . '</div>';
	}

	return $o;
}


/**
 * Wrapper to allow addons to replace the status editor if desired.
 */
function status_editor($x, $popup = false, $module='') {
	$hook_info = ['editor_html' => '', 'x' => $x, 'popup' => $popup, 'module' => $module];
	call_hooks('status_editor',$hook_info);
	if ($hook_info['editor_html'] == '') {
		return hz_status_editor($x, $popup);
	} else {
		return $hook_info['editor_html'];
	}
}

/**
 * This is our general purpose content editor.
 * It was once nicknamed "jot" and you may see references to "jot" littered throughout the code.
 * They are referring to the content editor or components thereof.
 */

function hz_status_editor($x, $popup = false) {

	$o = '';

	$c = channelx_by_n($x['profile_uid']);
	if($c && $c['channel_moved'])
		return $o;

	$webpage   = ((x($x,'webpage')) ? $x['webpage'] : '');
	$plaintext = true;

	$feature_nocomment = feature_enabled($x['profile_uid'], 'disable_comments');
	if(x($x, 'disable_comments'))
		$feature_nocomment = false;

	$feature_expire = ((feature_enabled($x['profile_uid'], 'content_expire') && (! $webpage)) ? true : false);
	if(x($x, 'hide_expire'))
		$feature_expire = false;

	$feature_future = ((feature_enabled($x['profile_uid'], 'delayed_posting') && (! $webpage)) ? true : false);
	if(x($x, 'hide_future'))
		$feature_future = false;

	$geotag = ((isset($x['allow_location']) && $x['allow_location']) ? replace_macros(get_markup_template('jot_geotag.tpl'), array()) : '');
	$setloc = t('Set your location');
	$clearloc = ((get_pconfig($x['profile_uid'], 'system', 'use_browser_location')) ? t('Clear browser location') : '');
	if(x($x, 'hide_location'))
		$geotag = $setloc = $clearloc = '';

	$mimetype = ((x($x,'mimetype')) ? $x['mimetype'] : 'text/bbcode');

	$mimeselect = ((x($x,'mimeselect')) ? $x['mimeselect'] : false);
	if($mimeselect)
		$mimeselect = mimetype_select($x['profile_uid'], $mimetype);
	else
		$mimeselect = '<input type="hidden" name="mimetype" value="' . $mimetype . '" />';

	$weblink = (($mimetype === 'text/bbcode') ? t('Insert web link') : false);
	if(x($x, 'hide_weblink'))
		$weblink = false;

	$embedPhotos = t('Embed (existing) photo from your photo albums');

	$writefiles = (($mimetype === 'text/bbcode') ? perm_is_allowed($x['profile_uid'], get_observer_hash(), 'write_storage') : false);
	if(x($x, 'hide_attach'))
		$writefiles = false;

	$layout = ((x($x,'layout')) ? $x['layout'] : '');

	$layoutselect = ((x($x,'layoutselect')) ? $x['layoutselect'] : false);
	if($layoutselect)
		$layoutselect = layout_select($x['profile_uid'], $layout);
	else
		$layoutselect = '<input type="hidden" name="layout_mid" value="' . $layout . '" />';

	if(array_key_exists('channel_select',$x) && $x['channel_select']) {
		require_once('include/channel.php');
		$id_select = identity_selector();
	}
	else
		$id_select = '';

	$reset = ((x($x,'reset')) ? $x['reset'] : '');

	$feature_auto_save_draft = ((feature_enabled($x['profile_uid'], 'auto_save_draft')) ? "true" : "false");

	$tpl = get_markup_template('jot-header.tpl');

	$tplmacros = [
		'$baseurl' => z_root(),
		'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
		'$pretext' => ((x($x,'pretext')) ? $x['pretext'] : ''),
		'$geotag' => $geotag,
		'$nickname' => $x['nickname'],
		'$linkurl' => t('Please enter a link URL:'),
		'$term' => t('Tag term:'),
		'$whereareu' => t('Where are you right now?'),
		'$editor_autocomplete'=> ((x($x,'editor_autocomplete')) ? $x['editor_autocomplete'] : ''),
		'$bbco_autocomplete'=> ((x($x,'bbco_autocomplete')) ? $x['bbco_autocomplete'] : ''),
		'$modalchooseimages' => t('Choose images to embed'),
		'$modalchoosealbum' => t('Choose an album'),
		'$modaldiffalbum' => t('Choose a different album...'),
		'$modalerrorlist' => t('Error getting album list'),
		'$modalerrorlink' => t('Error getting photo link'),
		'$modalerroralbum' => t('Error getting album'),
		'$nocomment_enabled' => t('Comments enabled'),
		'$nocomment_disabled' => t('Comments disabled'),
		'$auto_save_draft' => $feature_auto_save_draft,
		'$reset' => $reset,
		'$popup' => $popup
	];

	call_hooks('jot_header_tpl_filter',$tplmacros);

	if (isset(App::$page['htmlhead'])) {
		App::$page['htmlhead'] .= replace_macros($tpl, $tplmacros);
	}
	else {
		App::$page['htmlhead'] = replace_macros($tpl, $tplmacros);
	}

	$tpl = get_markup_template('jot.tpl');

	$preview = t('Preview');
	if(x($x, 'hide_preview'))
		$preview = '';

	$defexpire = ((($z = get_pconfig($x['profile_uid'], 'system', 'default_post_expire')) && (! $webpage)) ? $z : '');
	if($defexpire)
		$defexpire = datetime_convert('UTC',date_default_timezone_get(),$defexpire,'Y-m-d H:i');

	$defpublish = ((($z = get_pconfig($x['profile_uid'], 'system', 'default_post_publish')) && (! $webpage)) ? $z : '');
	if($defpublish)
		$defpublish = datetime_convert('UTC',date_default_timezone_get(),$defpublish,'Y-m-d H:i');

	$cipher = get_pconfig($x['profile_uid'], 'system', 'default_cipher');
	if(! $cipher)
		$cipher = 'AES-128-CCM';

	if(array_key_exists('catsenabled',$x))
		$catsenabled = $x['catsenabled'];
	else
		$catsenabled = ((feature_enabled($x['profile_uid'], 'categories') && (! $webpage)) ? 'categories' : '');

	// avoid illegal offset errors
	if(! array_key_exists('permissions',$x))
		$x['permissions'] = [ 'allow_cid' => '', 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '' ];

	$jotplugins = '';
	call_hooks('jot_tool', $jotplugins);

	$jotnets = '';
	if(x($x,'jotnets')) {
		call_hooks('jot_networks', $jotnets);
	}

	$sharebutton = (x($x,'button') ? $x['button'] : t('Share'));
	$placeholdtext = (x($x,'content_label') ? $x['content_label'] : $sharebutton);

	$tplmacros = [
		'$return_path' => ((x($x, 'return_path')) ? $x['return_path'] : App::$query_string),
		'$action' =>  z_root() . '/item',
		'$share' => $sharebutton,
		'$placeholdtext' => $placeholdtext,
		'$webpage' => $webpage,
		'$placeholdpagetitle' => ((x($x,'ptlabel')) ? $x['ptlabel'] : t('Page link name')),
		'$pagetitle' => (x($x,'pagetitle') ? $x['pagetitle'] : ''),
		'$id_select' => $id_select,
		'$id_seltext' => t('Post as'),
		'$writefiles' => $writefiles,
		'$bold' => t('Bold'),
		'$italic' => t('Italic'),
		'$underline' => t('Underline'),
		'$quote' => t('Quote'),
		'$code' => t('Code'),
		'$attach' => t('Attach/Upload file'),
		'$weblink' => $weblink,
		'$embedPhotos' => $embedPhotos,
		'$embedPhotosModalTitle' => t('Embed an image from your albums'),
		'$embedPhotosModalCancel' => t('Cancel'),
		'$embedPhotosModalOK' => t('OK'),
		'$setloc' => $setloc,
		'$voting' => t('Toggle voting'),
		'$poll' => t('Toggle poll'),
		'$poll_option_label' => t('Option'),
		'$poll_add_option_label' => t('Add option'),
		'$poll_expire_unit_label' => [t('Minutes'), t('Hours'), t('Days')],
		'$multiple_answers' => ['poll_multiple_answers', t("Allow multiple answers"), '', '', [t('No'), t('Yes')]],
		'$consensus' => ((array_key_exists('item',$x)) ? $x['item']['item_consensus'] : 0),
		'$nocommenttitle' => t('Disable comments'),
		'$nocommenttitlesub' => t('Toggle comments'),
		'$feature_nocomment' => $feature_nocomment,
		'$nocomment' => ((array_key_exists('item',$x)) ? $x['item']['item_nocomment'] : 0),
		'$clearloc' => $clearloc,
		'$title' => ((x($x, 'title')) ? htmlspecialchars($x['title'], ENT_COMPAT,'UTF-8') : ''),
		'$summary' => ((x($x, 'summary')) ? htmlspecialchars($x['summary'], ENT_COMPAT,'UTF-8') : ''),
		'$placeholdertitle' => ((x($x, 'placeholdertitle')) ? $x['placeholdertitle'] : t('Title (optional)')),
		'$placeholdersummary' => ((x($x, 'placeholdersummary')) ? $x['placeholdersummary'] : t('Summary (optional)')),
		'$catsenabled' => $catsenabled,
		'$category' => ((x($x, 'category')) ? $x['category'] : ''),
		'$placeholdercategory' => t('Categories (optional, comma-separated list)'),
		'$permset' => t('Permission settings'),
		'$ptyp' => ((x($x, 'ptyp')) ? $x['ptyp'] : ''),
		'$content' => ((x($x,'body')) ? htmlspecialchars($x['body'], ENT_COMPAT,'UTF-8') : ''),
		'$attachment' => ((x($x, 'attachment')) ? $x['attachment'] : ''),
		'$post_id' => ((x($x, 'post_id')) ? $x['post_id'] : ''),
		'$defloc' => $x['default_location'] ?? '',
		'$visitor' => $x['visitor'] ?? '',
		'$lockstate' => $x['lockstate'] ?? '',
		'$acl' => $x['acl'] ?? '',
		'$allow_cid' => acl2json($x['permissions']['allow_cid']),
		'$allow_gid' => acl2json($x['permissions']['allow_gid']),
		'$deny_cid' => acl2json($x['permissions']['deny_cid']),
		'$deny_gid' => acl2json($x['permissions']['deny_gid']),
		'$mimeselect' => $mimeselect,
		'$layoutselect' => $layoutselect,
		'$showacl' => ((array_key_exists('showacl', $x)) ? $x['showacl'] : true),
		'$bang' => $x['bang'] ?? '',
		'$profile_uid' => $x['profile_uid'],
		'$preview' => $preview,
		'$source' => ((x($x, 'source')) ? $x['source'] : ''),
		'$jotplugins' => $jotplugins,
		'$jotnets' => $jotnets,
		'$jotnets_label' => t('Other networks and post services'),
		'$defexpire' => $defexpire,
		'$feature_expire' => $feature_expire,
		'$expires' => t('Set expiration date'),
		'$defpublish' => $defpublish,
		'$feature_future' => $feature_future,
		'$future_txt' => t('Set publish date'),
		'$feature_encrypt' => ((feature_enabled($x['profile_uid'], 'content_encrypt') && (! $webpage)) ? true : false),
		'$encrypt' => t('Encrypt text'),
		'$cipher' => $cipher,
		'$expiryModalOK' => t('OK'),
		'$expiryModalCANCEL' => t('Cancel'),
		'$expanded' => ((x($x, 'expanded')) ? $x['expanded'] : false),
		'$bbcode' => ((x($x, 'bbcode')) ? $x['bbcode'] : false),
		'$parent' => ((array_key_exists('parent',$x) && $x['parent']) ? $x['parent'] : 0),
		'$reset' => $reset,
		'$is_owner' => ((local_channel() && (local_channel() == $x['profile_uid'])) ? true : false),
		'$customjotheaders' => '',
		'$custommoretoolsdropdown' => '',
		'$custommoretoolsbuttons' => '',
		'$customsubmitright' => []
	];

	call_hooks('jot_tpl_filter',$tplmacros);

	$o .= replace_macros($tpl, $tplmacros);
	if ($popup === true) {
		$o = '<div id="jot-popup" style="display:none">' . $o . '</div>';
	}

	return $o;
}


function get_item_children($arr, $parent) {
	$children = array();
	foreach($arr as $item) {
		if($item['id'] != $item['parent']) {
			if(get_config('system','thread_allow')) {
				// Fallback to parent_mid if thr_parent is not set
				$thr_parent = $item['thr_parent'];
				if($thr_parent == '')
					$thr_parent = $item['parent_mid'];

				if($thr_parent == $parent['mid']) {
					$item['children'] = get_item_children($arr, $item);
					$children[] = $item;
				}
			}
			else if($item['parent'] == $parent['id']) {
				$children[] = $item;
			}
		}
	}
	return $children;
}

function sort_item_children($items) {
	$result = $items;
	usort($result,'sort_thr_created_rev');
	foreach($result as $k => $i) {
		if(isset($result[$k]['children'])) {
			$result[$k]['children'] = sort_item_children($result[$k]['children']);
		}
	}
	return $result;
}

function add_children_to_list($children, &$arr) {
	foreach($children as $y) {
		$arr[] = $y;
		if(isset($y['children']))
			add_children_to_list($y['children'], $arr);
	}
}

function conv_sort($arr, $order) {

	if((!(is_array($arr) && count($arr))))
		return array();

	$parents = array();

	foreach($arr as $x)
		if($x['id'] == $x['parent'])
				$parents[] = $x;

	if(stristr($order,'created'))
		usort($parents,'sort_thr_created');
	elseif(stristr($order,'commented'))
		usort($parents,'sort_thr_commented');
	elseif(stristr($order,'updated'))
		usort($parents,'sort_thr_updated');
	elseif(stristr($order,'ascending'))
		usort($parents,'sort_thr_created_rev');


	if(count($parents))
		foreach($parents as $i=>$_x)
			$parents[$i]['children'] = get_item_children($arr, $_x);

	if(count($parents)) {
		foreach($parents as $k => $v) {
			if(count($parents[$k]['children'])) {
				$parents[$k]['children'] = sort_item_children($parents[$k]['children']);
			}
		}
	}

	$ret = array();
	if(count($parents)) {
		foreach($parents as $x) {
			$ret[] = $x;
			if(count($x['children']))
				add_children_to_list($x['children'], $ret);
		}
	}

	return $ret;
}


function sort_thr_created($a,$b) {
	return strcmp($b['created'],$a['created']);
}

function sort_thr_created_rev($a,$b) {
	return strcmp($a['created'],$b['created']);
}

function sort_thr_commented($a,$b) {
	return strcmp($b['commented'],$a['commented']);
}

function sort_thr_updated($a,$b) {
	$indexa = (($a['changed'] > $a['edited']) ? $a['changed'] : $a['edited']);
	$indexb = (($b['changed'] > $b['edited']) ? $b['changed'] : $b['edited']);
	return strcmp($indexb,$indexa);
}

function find_thread_parent_index($arr,$x) {
	foreach($arr as $k => $v)
		if($v['id'] == $x['parent'])
			return $k;

	return false;
}

function format_location($item) {

	if(strpos($item['location'],'#') === 0) {
		$location = substr($item['location'],1);
		$location = ((strpos($location,'[') !== false) ? zidify_links(bbcode($location)) : $location);
	}
	else {
		$locate = array('location' => $item['location'], 'coord' => $item['coord'], 'html' => '');
		call_hooks('render_location',$locate);
		$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_default($locate));
	}
	return $location;
}

function render_location_default($item) {

	$location = $item['location'];
	$coord = $item['coord'];

	if($coord) {
		if($location)
			$location .= '&nbsp;<span class="smalltext">(' . $coord . ')</span>';
		else
			$location = '<span class="smalltext">' . $coord . '</span>';
	}

	return $location;
}


function prepare_page($item) {

	$naked = 1;
//	$naked = ((get_pconfig($item['uid'],'system','nakedpage')) ? 1 : 0);
	$observer = App::get_observer();
	//240 chars is the longest we can have before we start hitting problems with suhosin sites
	$preview = substr(urlencode($item['body']), 0, 240);
	$link = z_root() . '/' . App::$cmd;
	if(array_key_exists('webpage',App::$layout) && array_key_exists('authored',App::$layout['webpage'])) {
		if(App::$layout['webpage']['authored'] === 'none')
			$naked = 1;
		// ... other possible options
	}

	$body = prepare_body($item, true, [ 'newwin' => false ]);
	$edit_link = (($item['uid'] === local_channel()) ? z_root() . '/editwebpage/' . argv(1) . '/' . $item['id'] : '');

	if(App::$page['template'] == 'none') {
		$tpl = 'page_display_empty.tpl';

		return replace_macros(get_markup_template($tpl), array(
			'$body' => $body['html'],
			'$edit_link' => $edit_link
		));

	}

	$tpl = get_pconfig($item['uid'], 'system', 'pagetemplate');
	if (! $tpl)
		$tpl = 'page_display.tpl';

	return replace_macros(get_markup_template($tpl), array(
		'$author' => (($naked) ? '' : $item['author']['xchan_name']),
		'$auth_url' => (($naked) ? '' : zid($item['author']['xchan_url'])),
		'$date' => (($naked) ? '' : datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'Y-m-d H:i')),
		'$title' => zidify_links(smilies(bbcode($item['title']))),
		'$body' => $body['html'],
		'$preview' => $preview,
		'$link' => $link,
		'$edit_link' => $edit_link
	));
}

function get_responses($conv_responses,$response_verbs,$ob,$item) {

	$ret = array();
	foreach($response_verbs as $v) {
		$ret[$v] = [];
		$ret[$v]['count'] = $conv_responses[$v][$item['mid']] ?? 0;
		$ret[$v]['list']  = ((isset($conv_responses[$v][$item['mid']])) ? $conv_responses[$v][$item['mid'] . '-l'] : '');
		$ret[$v]['button'] = get_response_button_text($v, $ret[$v]['count']);
		$ret[$v]['title'] = $conv_responses[$v]['title'] ?? '';
		$ret[$v]['modal'] = (($ret[$v]['count'] > MAX_LIKERS) ? true : false);
	}

	$count = 0;
	foreach ($ret as $key) {
		if ($key['count'] == true)
			$count++;
	}

	$ret['count'] = $count;

//logger('ret: ' . print_r($ret,true));

	return $ret;
}

function get_response_button_text($v,$count) {
	switch($v) {
		case 'like':
			return tt('Like','Likes',$count,'noun');
			break;
		case 'dislike':
			return tt('Dislike','Dislikes',$count,'noun');
			break;
		case 'attendyes':
			return tt('Attending','Attending',$count,'noun');
			break;
		case 'attendno':
			return tt('Not Attending','Not Attending',$count,'noun');
			break;
		case 'attendmaybe':
			return tt('Undecided','Undecided',$count,'noun');
			break;
		case 'agree':
			return tt('Agree','Agrees',$count,'noun');
			break;
		case 'disagree':
			return tt('Disagree','Disagrees',$count,'noun');
			break;
		case 'abstain':
			return tt('Abstain','Abstains',$count,'noun');
			break;
		default:
			return '';
			break;
	}
}

function is_unthreaded($mode) {
	return in_array($mode, [
		'network-new',
		'pubstream-new',
		'search',
		'community',
		'moderate'
	]);
}
