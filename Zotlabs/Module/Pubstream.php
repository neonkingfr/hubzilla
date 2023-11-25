<?php
namespace Zotlabs\Module;

use App;
use Zotlabs\Lib\Apps;

require_once('include/conversation.php');
require_once('include/acl_selectors.php');


class Pubstream extends \Zotlabs\Web\Controller {

	function get($update = 0, $load = false) {


		if(local_channel()) {
			if(! Apps::system_app_installed(local_channel(), 'Public Stream')) {
				//Do not display any associated widgets at this point
				App::$pdl = '';
				$papp = Apps::get_papp('Public Stream');
				return Apps::app_render($papp, 'module');
			}
		}

		if((observer_prohibited(true))) {
			return login();
		}

		if(! intval(get_config('system','open_pubstream',1))) {
			if(! get_observer_hash()) {
				return login();
			}
		}

		$net_firehose  = ((get_config('system','disable_discover_tab',1)) ? false : true);

		if(!$net_firehose) {
			return '';
		}

		$site_firehose = ((intval(get_config('system','site_firehose',0))) ? true : false);

		$mid = ((isset($_REQUEST['mid'])) ? unpack_link_id($_REQUEST['mid']) : '');

		if ($mid === false) {
			notice(t('Malformed message id.') . EOL);
			return;
		}

		$hashtags = ((x($_REQUEST,'tag')) ? $_REQUEST['tag'] : '');
		$item_normal = item_normal();
		$item_normal_update = item_normal_update();
		$net = ((array_key_exists('net',$_REQUEST))    ? escape_tags($_REQUEST['net']) : '');

		$title = replace_macros(get_markup_template("section_title.tpl"),array(
			'$title' => (($hashtags) ? '#' . htmlspecialchars($hashtags, ENT_COMPAT,'UTF-8') : '')
		));

		$o = (($hashtags) ? $title : '');

		if(local_channel() && (! $update)) {

			$channel = \App::get_channel();

			$channel_acl = array(
				'allow_cid' => $channel['channel_allow_cid'],
				'allow_gid' => $channel['channel_allow_gid'],
				'deny_cid'  => $channel['channel_deny_cid'],
				'deny_gid'  => $channel['channel_deny_gid']
			);

			$x = array(
				'is_owner'            => true,
				'allow_location'      => ((intval(get_pconfig($channel['channel_id'],'system','use_browser_location'))) ? '1' : ''),
				'default_location'    => $channel['channel_location'],
				'nickname'            => $channel['channel_address'],
				'lockstate'           => (($channel['channel_allow_cid'] || $channel['channel_allow_gid'] || $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 'lock' : 'unlock'),
				'acl'                 => populate_acl($channel_acl,true, \Zotlabs\Lib\PermissionDescription::fromGlobalPermission('view_stream'), get_post_aclDialogDescription(), 'acl_dialog_post'),
				'permissions'         => $channel_acl,
				'bang'                => '',
				'visitor'             => true,
				'profile_uid'         => local_channel(),
				'return_path'         => 'channel/' . $channel['channel_address'],
				'expanded'            => true,
				'editor_autocomplete' => true,
				'bbco_autocomplete'   => 'bbcode',
				'bbcode'              => true,
				'jotnets'             => true,
				'reset'               => t('Reset form')
			);

			$o .= '<div id="jot-popup">';
			$a = '';
			$o .= status_editor($x, false, 'Pubstream');
			$o .= '</div>';
		}

		if(! $update && !$load) {

			nav_set_selected(t('Public Stream'));

			$maxheight = get_config('system','home_divmore_height');
			if(! $maxheight)
				$maxheight = 400;

			$o .= '<div id="live-pubstream"></div>' . "\r\n";
			$o .= "<script> var profile_uid = " . ((intval(local_channel())) ? local_channel() : (-1))
				. "; var profile_page = " . \App::$pager['page']
				. "; divmore_height = " . intval($maxheight) . "; </script>\r\n";

			//if we got a decoded hash we must encode it again before handing to javascript
			$mid = gen_link_id($mid);

			\App::$page['htmlhead'] .= replace_macros(get_markup_template("build_query.tpl"),array(
				'$baseurl' => z_root(),
				'$pgtype'  => 'pubstream',
				'$uid'     => ((local_channel()) ? local_channel() : '0'),
				'$gid'     => '0',
				'$cid'     => '0',
				'$cmin'    => '(-1)',
				'$cmax'    => '(-1)',
				'$star'    => '0',
				'$liked'   => '0',
				'$conv'    => '0',
				'$spam'    => '0',
				'$fh'      => '1',
				'$dm'      => '0',
				'$nouveau' => '0',
				'$wall'    => '0',
				'$list'    => '0',
				'$page'    => ((\App::$pager['page'] != 1) ? \App::$pager['page'] : 1),
				'$search'  => '',
				'$xchan'   => '',
				'$order'   => 'comment',
				'$file'    => '',
				'$cats'    => '',
				'$tags'    => (($hashtags) ? urlencode($hashtags) : ''),
				'$dend'    => '',
				'$mid'     => (($mid) ? urlencode($mid) : ''),
				'$verb'    => '',
				'$net'     => (($net) ? urlencode($net) : ''),
				'$dbegin'  => ''
			));
		}

		if($update && ! $load) {
			// only setup pagination on initial page view
			$pager_sql = '';
		}
		else {
			\App::set_pager_itemspage(10);
			$pager_sql = sprintf(" LIMIT %d OFFSET %d ", intval(\App::$pager['itemspage']), intval(\App::$pager['start']));
		}

		require_once('include/channel.php');
		require_once('include/security.php');

		$sys = get_sys_channel();
		$uids = " and item.uid  = " . intval($sys['channel_id']) . " ";
		$abook_uids = " and abook.abook_channel = " . intval($sys['channel_id']) . " ";
		$sql_extra = item_permissions_sql($sys['channel_id']);
		$sql_extra_order = '';
		$site_firehose_sql = '';
		$thread_top =  " and item.item_thread_top = 1 ";

		if($site_firehose) {
			$site_firehose_sql = " and owner_xchan in (select channel_hash from channel where channel_system = 0 and channel_removed = 0) ";
		}

		if(get_config('system','public_list_mode'))
			$page_mode = 'list';
		else
			$page_mode = 'client';


		if(x($hashtags)) {
			$sql_extra .= protect_sprintf(term_query('item', $hashtags, TERM_HASHTAG, TERM_COMMUNITYTAG));
			$sql_extra_order = " ORDER BY item.created DESC ";
			$thread_top = '';
		}

		$net_query = (($net) ? " left join xchan on xchan_hash = author_xchan " : '');
		$net_query2 = (($net) ? " and xchan_network = '" . protect_sprintf(dbesc($net)) . "' " : '');

		if($update && $_SESSION['loadtime'])
			$simple_update = " AND (( item_unseen = 1 AND item.changed > '" . datetime_convert('UTC','UTC',$_SESSION['loadtime']) . "' )  OR item.changed > '" . datetime_convert('UTC','UTC',$_SESSION['loadtime']) . "' ) ";

		//logger('update: ' . $update . ' load: ' . $load);

		$items = [];

		if($update) {

			$ordering = get_config('system', 'pubstream_ordering', 'commented');

			if($load) {
				if($mid) {
					$r = q("SELECT parent AS item_id FROM item
						left join abook on item.author_xchan = abook.abook_xchan
						$net_query
						WHERE item.mid = '%s' and item.item_private = 0
						$uids $site_firehose_sql
						$item_normal
						and (abook.abook_blocked = 0 or abook.abook_flags is null)
						$sql_extra $net_query2",
						dbesc($mid)
					);
				}
				else {
					// Fetch a page full of parent items for this page
					$r = dbq("SELECT parent AS item_id FROM item
						left join abook on ( item.author_xchan = abook.abook_xchan $abook_uids )
						$net_query
						WHERE item.item_private = 0 $thread_top
						$uids $site_firehose_sql
						$item_normal
						and (abook.abook_blocked = 0 or abook.abook_flags is null)
						$sql_extra $net_query2
						ORDER BY $ordering DESC $pager_sql "
					);
				}
			}
			elseif($update) {
				if($mid) {
					$r = q("SELECT parent AS item_id FROM item
						left join abook on item.author_xchan = abook.abook_xchan
						$net_query
						WHERE item.mid = '%s' and item.item_private = 0
						$uids $site_firehose_sql $item_normal_update $simple_update
						and (abook.abook_blocked = 0 or abook.abook_flags is null)
						$sql_extra $net_query2",
						dbesc($mid)
					);
				}
				else {
					$r = dbq("SELECT parent AS item_id FROM item
						left join abook on item.author_xchan = abook.abook_xchan
						$net_query
						WHERE item.item_private = 0 $thread_top
						$uids $site_firehose_sql $item_normal_update
						$simple_update
						and (abook.abook_blocked = 0 or abook.abook_flags is null)
						$sql_extra $net_query2"
					);
				}
			}

			// Then fetch all the children of the parents that are on this page
			$parents_str = '';

			if($r) {

				$parents_str = ids_to_querystr($r,'item_id');

				$items = dbq("SELECT item.*, item.id AS item_id FROM item
					WHERE true $uids $item_normal
					AND item.parent IN ( $parents_str )
					$sql_extra $sql_extra_order"
				);


				// use effective_uid param of xchan_query to help sort out comment permission
				// for sys_channel owned items.

				xchan_query($items, true, local_channel());
				$items = fetch_post_tags($items,true);

				if (!$hashtags) {
					$items = conv_sort($items, $ordering);
				}


			}

		}

		$mode = (($hashtags) ? 'pubstream-new' : 'pubstream');

		$o .= conversation($items,$mode,$update,$page_mode);

		if($mid)
			$o .= '<div id="content-complete"></div>';

		if(($items) && (! $update))
			$o .= alt_pager(count($items));

		$_SESSION['loadtime'] = datetime_convert();

		return $o;

	}
}
