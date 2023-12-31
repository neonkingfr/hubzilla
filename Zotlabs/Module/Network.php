<?php
namespace Zotlabs\Module;

use Zotlabs\Lib\AccessList;
use Zotlabs\Lib\Apps;
use App;

require_once('include/items.php');
require_once('include/contact_widgets.php');
require_once('include/conversation.php');
require_once('include/acl_selectors.php');


class Network extends \Zotlabs\Web\Controller {

	function init() {
		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}

		$search = $_GET['search'] ?? '';

		if(in_array(substr($search, 0, 1), [ '@', '!', '?']) || strpos($search, 'https://') === 0)
			goaway(z_root() . '/search?f=&search=' . $search);

		if(count($_GET) < 2) {
			$network_options = get_pconfig(local_channel(), 'system', 'network_page_default');
			if($network_options)
				goaway(z_root() . '/network?f=&' . $network_options);
		}

		$channel = App::get_channel();
		App::$profile_uid = local_channel();
		head_set_icon($channel['xchan_photo_s']);

	}

	function get($update = 0, $load = false) {

		if(! local_channel()) {
			$_SESSION['return_url'] = App::$query_string;
			return login(false);
		}

		App::$profile_uid = local_channel();

		$o = '';

		$arr = array('query' => App::$query_string);

		call_hooks('network_content_init', $arr);

		$channel = App::get_channel();
		$item_normal = item_normal();
		$item_normal_update = item_normal_update();

		$datequery = $datequery2 = '';

		$group = 0;

		$nouveau    = false;

		$datequery  = ((x($_GET,'dend') && is_a_date_arg($_GET['dend'])) ? notags($_GET['dend']) : '');
		$datequery2 = ((x($_GET,'dbegin') && is_a_date_arg($_GET['dbegin'])) ? notags($_GET['dbegin']) : '');
		$gid        = ((x($_GET,'gid')) ? intval($_GET['gid']) : 0);
		$category   = ((x($_REQUEST,'cat')) ? $_REQUEST['cat'] : '');
		$hashtags   = ((x($_REQUEST,'tag')) ? $_REQUEST['tag'] : '');
		$verb       = ((x($_REQUEST,'verb')) ? $_REQUEST['verb'] : '');
		$dm         = ((x($_REQUEST,'dm')) ? $_REQUEST['dm'] : 0);


		$order = get_pconfig(local_channel(), 'mod_network', 'order', 0);
		switch($order) {
			case 0:
				$order = 'comment';
				break;
			case 1:
				$order = 'post';
				break;
			case 2:
				$nouveau = true;
				break;
		}

		$search = $_GET['search'] ?? '';
		if($search) {
			if(strpos($search, '#') === 0) {
				$hashtags = substr($search,1);
				$search = '';
			}
		}

		if($datequery)
			$order = 'post';


		// filter by collection (e.g. group)

		if($gid) {
			$r = q("SELECT * FROM pgrp WHERE id = %d AND uid = %d LIMIT 1",
				intval($gid),
				intval(local_channel())
			);
			if(! $r) {
				if($update)
					killme();
				notice( t('No such group') . EOL );
				goaway(z_root() . '/network');
				// NOTREACHED
			}

			$group      = $gid;
			$group_hash = $r[0]['hash'];
			$def_acl    = array('allow_gid' => '<' . $r[0]['hash'] . '>');
		}

		$default_cmin = ((Apps::system_app_installed(local_channel(), 'Affinity Tool')) ? get_pconfig(local_channel(), 'affinity', 'cmin', 0) : (-1));
		$default_cmax = ((Apps::system_app_installed(local_channel(), 'Affinity Tool')) ? get_pconfig(local_channel(), 'affinity', 'cmax', 99) : (-1));

		$cid      = ((x($_GET, 'cid'))   ? intval($_GET['cid'])   : 0);
		$star     = ((x($_GET, 'star'))  ? intval($_GET['star'])  : 0);
		$liked    = ((x($_GET, 'liked')) ? intval($_GET['liked']) : 0);
		$conv     = ((x($_GET, 'conv'))  ? intval($_GET['conv'])  : 0);
		$spam     = ((x($_GET, 'spam'))  ? intval($_GET['spam'])  : 0);
		$cmin     = ((array_key_exists('cmin', $_GET))  ? intval($_GET['cmin'])  : $default_cmin);
		$cmax     = ((array_key_exists('cmax', $_GET))  ? intval($_GET['cmax'])  : $default_cmax);
		$file     = ((x($_GET, 'file'))  ? $_GET['file']          : '');
		$xchan    = ((x($_GET, 'xchan')) ? $_GET['xchan']         : '');
		$net      = ((x($_GET, 'net'))   ? $_GET['net']           : '');
		$pf       = ((x($_GET, 'pf'))    ? $_GET['pf']            : '');
		$unseen   = ((x($_GET, 'unseen'))    ? $_GET['unseen']            : '');

		$status_editor = '';


		if (Apps::system_app_installed(local_channel(), 'Affinity Tool')) {
			$affinity_locked = intval(get_pconfig(local_channel(), 'affinity', 'lock', 1));
			if ($affinity_locked) {
				set_pconfig(local_channel(), 'affinity', 'cmin', $cmin);
				set_pconfig(local_channel(), 'affinity', 'cmax', $cmax);
			}
		}

		if(x($_GET, 'search') || $file || (!$pf && $cid) || $hashtags || $verb || $category || $conv || $unseen)
			$nouveau = true;

		$cid_r = [];

		if($cid) {
			$cid_r = q("SELECT abook.abook_xchan, xchan.xchan_addr, xchan.xchan_name, xchan.xchan_url, xchan.xchan_photo_s, xchan.xchan_pubforum from abook left join xchan on abook_xchan = xchan_hash where abook_id = %d and abook_channel = %d and abook_blocked = 0 limit 1",
				intval($cid),
				intval(local_channel())
			);

			if(! $cid_r) {
				if($update) {
					killme();
				}
				notice( t('No such channel') . EOL );
				goaway(z_root() . '/network');
				// NOTREACHED
			}
			$def_acl = [ 'allow_cid' => '<' . $cid_r[0]['abook_xchan'] . '>', 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '' ];
		}

		if(! $update) {

			// search terms header
			if($search || $hashtags) {
				$o .= replace_macros(get_markup_template('section_title.tpl'), array(
					'$title' => t('Search Results For:') . ' ' . (($search) ? htmlspecialchars($search, ENT_COMPAT, 'UTF-8') : '#' . htmlspecialchars($hashtags, ENT_COMPAT,'UTF-8'))
				));
			}

			nav_set_selected('Network');

			$bang = '!';

			if($cid_r) {
				$forums = get_forum_channels($channel['channel_id']);
				if($forums) {
					$forum_xchans = ids_to_array($forums, 'xchan_hash');
					if(in_array($cid_r[0]['abook_xchan'], $forum_xchans))
						$bang = $cid_r[0]['abook_xchan'];
				}
			}

			$channel_acl = array(
				'allow_cid' => $channel['channel_allow_cid'],
				'allow_gid' => $channel['channel_allow_gid'],
				'deny_cid'  => $channel['channel_deny_cid'],
				'deny_gid'  => $channel['channel_deny_gid']
			);

			$private_editing = (($group || $cid) ? true : false);

			$x = array(
				'is_owner'         => true,
				'allow_location'   => ((intval(get_pconfig($channel['channel_id'], 'system', 'use_browser_location'))) ? '1' : ''),
				'default_location' => $channel['channel_location'],
				'nickname'         => $channel['channel_address'],
				'lockstate'        => (($private_editing || $channel['channel_allow_cid'] || $channel['channel_allow_gid'] || $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 'lock' : 'unlock'),
				'acl'              => populate_acl((($private_editing) ? $def_acl : $channel_acl), true, \Zotlabs\Lib\PermissionDescription::fromGlobalPermission('view_stream'), get_post_aclDialogDescription(), 'acl_dialog_post'),
				'permissions'      => (($private_editing) ? $def_acl : $channel_acl),
				'bang'             => (($private_editing) ? $bang : ''),
				'visitor'          => true,
				'profile_uid'      => local_channel(),
				'editor_autocomplete' => true,
				'bbco_autocomplete' => 'bbcode',
				'bbcode' => true,
				'jotnets' => true,
				'reset' => t('Reset form')
			);

			$a = '';
			$status_editor = status_editor($x, false, 'Network');
			$o .= $status_editor;

		}


		// We don't have to deal with ACL's on this page. You're looking at everything
		// that belongs to you, hence you can see all of it. We will filter by group if
		// desired.


		$sql_options  = (($star)
			? ' and item_starred = 1 '
			: '');

		$sql_nets = '';

		$item_thread_top = ' AND item_thread_top = 1 ';

		$sql_extra = '';

		if($group) {

			$contact_str = '';
			$contacts = AccessList::members(local_channel(), $group);
			if($contacts) {
				$contact_str = ids_to_querystr($contacts, 'xchan', true);
			}
			else {
				$contact_str = " '0' ";
				if(! $update) {
					info( t('Privacy group is empty'));
				}
			}
			$item_thread_top = '';
			$sql_extra = " AND item.parent IN ( SELECT DISTINCT parent FROM item WHERE true $sql_options AND (( author_xchan IN ( $contact_str ) OR owner_xchan in ( $contact_str )) or allow_gid like '" . protect_sprintf('%<' . dbesc($group_hash) . '>%') . "' ) and id = parent $item_normal ) ";

			$x = AccessList::by_hash(local_channel(), $group_hash);

			if($x) {
				$title = replace_macros(get_markup_template('section_title.tpl'), array(
					'$title' => t('Privacy group: ') . $x['gname']
				));
			}

			$o = $title;
			$o .= $status_editor;

		}
		elseif($cid_r) {
			$item_thread_top = '';

			if($load || $update) {
				if(!$pf && $nouveau) {
					// This is for nouveau view cid queries (not a public forum)
					$sql_extra = " AND author_xchan = '" . dbesc($cid_r[0]['abook_xchan']) . "' ";
				}
				elseif($pf && $unseen && $nouveau) {

					$vnotify = get_pconfig(local_channel(), 'system', 'vnotify');
					if(! ($vnotify & VNOTIFY_LIKE))
						$likes_sql = " AND verb NOT IN ('" . dbesc(ACTIVITY_LIKE) . "', '" . dbesc(ACTIVITY_DISLIKE) . "') ";

					// This is for nouveau view public forum cid queries (if a forum notification is clicked)
					//$p = q("SELECT oid AS parent FROM term WHERE uid = %d AND ttype = %d AND term = '%s'",
						//intval(local_channel()),
						//intval(TERM_FORUM),
						//dbesc($cid_r[0]['xchan_name'])
					//);

					//$p_str = ids_to_querystr($p, 'parent');

					$p_sql = '';
					//if($p_str)
						//$p_sql = " OR item.parent IN ( $p_str ) ";

					$sql_extra = " AND ( owner_xchan = '" . protect_sprintf(dbesc($cid_r[0]['abook_xchan'])) . "' OR owner_xchan = '" . protect_sprintf(dbesc($cid_r[0]['abook_xchan'])) . "' $p_sql ) AND item_unseen = 1 $likes_sql ";
				}
				else {
					// This is for threaded view cid queries (e.g. if a forum is selected from the forum filter)
					$ttype = (($pf) ? TERM_FORUM : TERM_MENTION);

					$p1 = dbq("SELECT DISTINCT parent FROM item WHERE uid = " . intval(local_channel()) . " AND ( author_xchan = '" . dbesc($cid_r[0]['abook_xchan']) . "' OR owner_xchan = '" . dbesc($cid_r[0]['abook_xchan']) . "' ) $item_normal ");
					$p2 = dbq("SELECT oid AS parent FROM term WHERE uid = " . intval(local_channel()) . " AND ttype = $ttype AND term = '" . dbesc($cid_r[0]['xchan_name']) . "'");

					$p_str = ids_to_querystr(array_merge($p1, $p2), 'parent');
					if(! $p_str)
						killme();

					$sql_extra = " AND item.parent IN ( $p_str ) ";
				}
			}

			$title = replace_macros(get_markup_template('section_title.tpl'), array(
				'$title' => '<a href="' . zid($cid_r[0]['xchan_url']) . '" ><img src="' . zid($cid_r[0]['xchan_photo_s'])  . '" alt="' . urlencode($cid_r[0]['xchan_name']) . '" /></a> <a href="' . zid($cid_r[0]['xchan_url']) . '" >' . $cid_r[0]['xchan_name'] . '</a>'
			));

			$o = $title;
			$o .= $status_editor;
		}
		elseif($xchan) {
			$r = q("select * from xchan where xchan_hash = '%s'",
				dbesc($xchan)
			);
			if($r) {
				$item_thread_top = '';
				$sql_extra = " AND item.parent IN ( SELECT DISTINCT parent FROM item WHERE true $sql_options AND uid = " . intval(local_channel()) . " AND ( author_xchan = '" . dbesc($xchan) . "' or owner_xchan = '" . dbesc($xchan) . "' ) $item_normal ) ";
				$title = replace_macros(get_markup_template("section_title.tpl"), array(
					'$title' => '<a href="' . zid($r[0]['xchan_url']) . '" ><img src="' . zid($r[0]['xchan_photo_s'])  . '" alt="' . urlencode($r[0]['xchan_name']) . '" /></a> <a href="' . zid($r[0]['xchan_url']) . '" >' . $r[0]['xchan_name'] . '</a>'
				));

				$o = $title;
				$o .= $status_editor;

			}
			else {
				notice( t('Invalid channel.') . EOL);
				goaway(z_root() . '/network');
			}

		}

		if(x($category)) {
			$sql_extra .= protect_sprintf(term_query('item', $category, TERM_CATEGORY));
		}
		if(x($hashtags)) {
			$sql_extra .= protect_sprintf(term_query('item', $hashtags, TERM_HASHTAG, TERM_COMMUNITYTAG));
		}

		$sql_extra3 = '';

		if($datequery) {
			$sql_extra3 .= protect_sprintf(sprintf(" AND item.created <= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery))));
		}
		if($datequery2) {
			$sql_extra3 .= protect_sprintf(sprintf(" AND item.created >= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery2))));
		}

		$sql_extra2 = (($nouveau) ? '' : ' AND item.parent = item.id ');
		$sql_extra3 = (($nouveau) ? '' : $sql_extra3);

		if(x($_GET, 'search')) {
			$search = escape_tags($_GET['search']);
			if(strpos($search, '#') === 0) {
				$sql_extra .= term_query('item', substr($search, 1), TERM_HASHTAG, TERM_COMMUNITYTAG);
			}
			else {
				$sql_extra .= sprintf(" AND (item.body like '%s' OR item.title like '%s') ",
					dbesc(protect_sprintf('%' . $search . '%')),
					dbesc(protect_sprintf('%' . $search . '%'))
				);
			}
		}

		if ($verb) {

			// the presence of a leading dot in the verb determines
			// whether to match the type of activity or the child object.
			// The name 'verb' is a holdover from the earlier XML
			// ActivityStreams specification.

			if (substr($verb, 0, 1) === '.') {
				$sql_verb = substr($verb, 1);
				$sql_extra .= sprintf(" AND item.obj_type like '%s' ",
					dbesc(protect_sprintf('%' . $sql_verb . '%'))
				);
			}
			else {
				$sql_extra .= sprintf(" AND item.verb like '%s' ",
					dbesc(protect_sprintf('%' . $verb . '%'))
				);
			}
		}

		if(strlen($file)) {
			$sql_extra .= term_query('item', $file, TERM_FILE);
		}

		if ($dm) {
			$sql_extra .= ' AND item_private = 2 ';
		}
		else {
			$sql_extra .= ' AND item_private IN (0, 1) ';
		}


		if($conv) {
			$item_thread_top = '';
			$sql_extra .= " AND ( author_xchan = '" . dbesc($channel['channel_hash']) . "' OR item_mentionsme = 1 ) ";
		}

		if($update && ! $load) {

			// only setup pagination on initial page view
			$pager_sql = '';

		}
		else {
			$itemspage = get_pconfig(local_channel(), 'system', 'itemspage');
			App::set_pager_itemspage(((intval($itemspage)) ? $itemspage : 10));
			$pager_sql = sprintf(" LIMIT %d OFFSET %d ", intval(App::$pager['itemspage']), intval(App::$pager['start']));
		}

		// cmin and cmax are both -1 when the affinity tool is disabled

		if(($cmin !== (-1)) || ($cmax !== (-1))) {

			// Not everybody who shows up in the network stream will be in your address book.
			// By default those that aren't are assumed to have closeness = 99; but this isn't
			// recorded anywhere. So if cmax is 99, we'll open the search up to anybody in
			// the stream with a NULL address book entry.

			$sql_nets .= ' AND ';

			if($cmax === 99)
				$sql_nets .= ' ( ';

			$sql_nets .= '( abook.abook_closeness >= ' . intval($cmin) . ' ';
			$sql_nets .= ' AND abook.abook_closeness <= ' . intval($cmax) . ' ) ';

			if($cmax === 99)
				$sql_nets .= ' OR abook.abook_closeness IS NULL ) ';

		}

		$net_query = (($net) ? ' left join xchan on xchan_hash = author_xchan ' : '');
		$net_query2 = (($net) ? " and xchan_network = '" . protect_sprintf(dbesc($net)) . "' " : '');

		$abook_uids = ' and abook.abook_channel = ' . local_channel() . ' ';
		$uids = ' and item.uid = ' . local_channel() . ' ';

		if(feature_enabled(local_channel(), 'network_list_mode'))
			$page_mode = 'list';
		else
			$page_mode = 'client';

		$parents_str = '';

		// This fixes a very subtle bug so I'd better explain it. You wake up in the morning or return after a day
		// or three and look at your matrix page - after opening up your browser. The first page loads just as it
		// should. All of a sudden a few seconds later, page 2 will get inserted at the beginning of the page
		// (before the page 1 content). The update code is actually doing just what it's supposed
		// to, it's fetching posts that have the ITEM_UNSEEN bit set. But the reason that page 2 content is being
		// returned in an UPDATE is because you hadn't gotten that far yet - you're still on page 1 and everything
		// that we loaded for page 1 is now marked as seen. But the stuff on page 2 hasn't been. So... it's being
		// treated as "new fresh" content because it is unseen. We need to distinguish it somehow from content
		// which "arrived as you were reading page 1". We're going to do this
		// by storing in your session the current UTC time whenever you LOAD a network page, and only UPDATE items
		// which are both ITEM_UNSEEN and have "changed" since that time. Cross fingers...

		$simple_update = '';
		if($update && $_SESSION['loadtime'])
			$simple_update = " AND (( item_unseen = 1 AND item.changed > '" . datetime_convert('UTC','UTC',$_SESSION['loadtime']) . "' )  OR item.changed > '" . datetime_convert('UTC','UTC',$_SESSION['loadtime']) . "' ) ";

		$items = [];

		if($nouveau && $load) {
			// "New Item View" - show all items unthreaded in reverse created date order
			$items = dbq("SELECT item.*, item.id AS item_id, created FROM item
				left join abook on ( item.owner_xchan = abook.abook_xchan $abook_uids )
				$net_query
				WHERE true $uids $item_normal
				and (abook.abook_blocked = 0 or abook.abook_flags is null)
				$sql_extra $sql_options $sql_nets
				$net_query2
				ORDER BY item.created DESC $pager_sql "
			);

			$parents_str = ids_to_querystr($items, 'item_id');

			require_once('include/items.php');

			xchan_query($items);

			$items = fetch_post_tags($items, true);
		}
		elseif($update) {

			// Normal conversation view

			if($order === 'post')
				$ordering = 'created';
			else
				$ordering = 'commented';

			if($load) {
				// Fetch a page full of parent items for this page
				$r = dbq("SELECT item.parent AS item_id FROM item
					left join abook on ( item.owner_xchan = abook.abook_xchan $abook_uids )
					$net_query
					WHERE true $uids $item_thread_top $item_normal
					AND item.mid = item.parent_mid
					and (abook.abook_blocked = 0 or abook.abook_flags is null)
					$sql_extra3 $sql_extra $sql_options $sql_nets
					$net_query2
					ORDER BY $ordering DESC $pager_sql "
				);
			}
			else {

				// this is an update
				$r = dbq("SELECT item.parent AS item_id FROM item
					left join abook on ( item.owner_xchan = abook.abook_xchan $abook_uids )
					$net_query
					WHERE true $uids $item_normal_update $simple_update
					and (abook.abook_blocked = 0 or abook.abook_flags is null)
					$sql_extra3 $sql_extra $sql_options $sql_nets $net_query2 "
				);
			}

			// Then fetch all the children of the parents that are on this page

			if($r) {
				$parents_str = ids_to_querystr($r, 'item_id');
				$items = dbq("SELECT item.*, item.id AS item_id FROM item
					WHERE true $uids $item_normal
					AND item.parent IN ( $parents_str )
					$sql_extra "
				);

				xchan_query($items, true);
				$items = fetch_post_tags($items, true);
				$items = conv_sort($items, $ordering);
			}
			else {
				$items = array();
			}

		}

		$mode = (($nouveau) ? 'network-new' : 'network');

		if($search)
			$mode = 'search';

		if(! $update) {
			// The special div is needed for liveUpdate to kick in for this page.
			// We only launch liveUpdate if you aren't filtering in some incompatible
			// way and also you aren't writing a comment (discovered in javascript).

			$maxheight = get_pconfig(local_channel(), 'system', 'network_divmore_height');
			if(! $maxheight)
				$maxheight = 400;


			$o .= '<div id="live-network"></div>' . "\r\n";
			$o .= "<script> var profile_uid = " . local_channel()
				. "; var profile_page = " . App::$pager['page']
				. "; divmore_height = " . intval($maxheight) . "; </script>\r\n";

			App::$page['htmlhead'] .= replace_macros(get_markup_template("build_query.tpl"),array(
				'$baseurl' => z_root(),
				'$pgtype'  => 'network',
				'$uid'     => ((local_channel()) ? local_channel() : '0'),
				'$gid'     => (($gid) ? $gid : '0'),
				'$cid'     => (($cid) ? $cid : '0'),
				'$cmin'    => (($cmin) ? $cmin : '(-1)'),
				'$cmax'    => (($cmax) ? $cmax : '(-1)'),
				'$star'    => (($star) ? $star : '0'),
				'$liked'   => (($liked) ? $liked : '0'),
				'$conv'    => (($conv) ? $conv : '0'),
				'$spam'    => (($spam) ? $spam : '0'),
				'$fh'      => '0',
				'$dm'      => (($dm) ? $dm : '0'),
				'$nouveau' => (($nouveau) ? $nouveau : '0'),
				'$wall'    => '0',
				'$list'    => ((x($_REQUEST,'list')) ? intval($_REQUEST['list']) : 0),
				'$page'    => ((App::$pager['page'] != 1) ? App::$pager['page'] : 1),
				'$search'  => (($search) ? urlencode($search) : ''),
				'$xchan'   => (($xchan) ? urlencode($xchan) : ''),
				'$order'   => $order,
				'$file'    => (($file) ? urlencode($file) : ''),
				'$cats'    => (($category) ? urlencode($category) : ''),
				'$tags'    => (($hashtags) ? urlencode($hashtags) : ''),
				'$dend'    => $datequery,
				'$mid'     => '',
				'$verb'    => (($verb) ? urlencode($verb) : ''),
				'$net'     => (($net) ? urlencode($net) : ''),
				'$dbegin'  => $datequery2,
				'$pf'      => (($pf) ? intval($pf) : 0),
				'$unseen'  => (($unseen) ? urlencode($unseen) : ''),
				'$page_mode' => $page_mode
			));
		}

		$o .= conversation($items, $mode, $update, $page_mode);

		if(($items) && (! $update))
			$o .= alt_pager(count($items));

		$_SESSION['loadtime'] = datetime_convert();

		return $o;
	}

}
