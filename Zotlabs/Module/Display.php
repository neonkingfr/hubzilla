<?php
namespace Zotlabs\Module;

use App;
use Zotlabs\Web\Controller;

class Display extends Controller {

	function init() {

	}

	function get($update = 0, $load = false) {

		$noscript_content = (get_config('system', 'noscript_content', '1') && (! $update));

		$module_format = 'html';

		if(argc() > 1) {
			$module_format = substr(argv(1),strrpos(argv(1),'.') + 1);
			if(! in_array($module_format,['atom','zot','json']))
				$module_format = 'html';
		}

		if(observer_prohibited()) {
			notice( t('Public access denied.') . EOL);
			return;
		}

		if(argc() > 1) {
			$item_hash = argv(1);
			if($module_format !== 'html') {
				$item_hash = substr($item_hash,0,strrpos($item_hash,'.'));
			}
		}

		if(isset($_REQUEST['mid']) && $_REQUEST['mid']) {
			$item_hash = $_REQUEST['mid'];
		}

		$item_hash = unpack_link_id($item_hash);

		if ($item_hash === false) {
			App::$error = 400;
			notice(t('Malformed message id.') . EOL);
			return;
		}

		if(!$item_hash) {
			App::$error = 404;
			notice( t('Item not found.') . EOL);
			return;
		}

		$observer_is_owner = false;

		$o = '';

		if(local_channel() && (! $update)) {

			$channel = App::get_channel();

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
			$o .= status_editor($x, false, 'Display');
			$o .= '</div>';
		}

		// This page can be viewed by anybody so the query could be complicated
		// First we'll see if there is a copy of the item which is owned by us - if we're logged in locally.
		// If that fails (or we aren't logged in locally),
		// query an item in which the observer (if logged in remotely) has cid or gid rights
		// and if that fails, look for a copy of the post that has no privacy restrictions.
		// If we find the post, but we don't find a copy that we're allowed to look at, this fact needs to be reported.

		// find a copy of the item somewhere

		$target_item = null;

		$r = q("select id, uid, mid, parent, parent_mid, thr_parent, verb, item_type, item_deleted, author_xchan, item_blocked from item where mid = '%s' limit 1",
			dbesc($item_hash)
		);

		if (!$r) {
			notice( t('Item not found.') . EOL);
			return '';
		}

		$target_item = $r[0];

		/* not yet ready for prime time
		$x = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($target_item['author_xchan'])
		);
		if($x) {
			App::$poi = $x[0];
		}
		*/

		//if the item is to be moderated redirect to /moderate
		if($target_item['item_blocked'] == ITEM_MODERATED) {
			goaway(z_root() . '/moderate/' . $target_item['id']);
		}

		$r = null;

		if($target_item['item_type']  == ITEM_TYPE_WEBPAGE) {
			$x = q("select * from channel where channel_id = %d limit 1",
				intval($target_item['uid'])
			);
			$y = q("select * from iconfig left join item on iconfig.iid = item.id
				where item.uid = %d and iconfig.cat = 'system' and iconfig.k = 'WEBPAGE' and item.id = %d limit 1",
				intval($target_item['uid']),
				intval($target_item['parent'])
			);
			if($x && $y) {
				goaway(z_root() . '/page/' . $x[0]['channel_address'] . '/' . $y[0]['v']);
			}
			else {
				notice( t('Page not found.') . EOL);
			 	return '';
			}
		}

		call_hooks('item_custom_display', $target_item);

		$simple_update = '';
		if($update && $_SESSION['loadtime'])
			$simple_update = " AND (( item_unseen = 1 AND item.changed > '" . datetime_convert('UTC','UTC',$_SESSION['loadtime']) . "' )  OR item.changed > '" . datetime_convert('UTC','UTC',$_SESSION['loadtime']) . "' ) ";

		if((! $update) && (! $load)) {

			// if the target item is not a post (eg a like) we want to address its thread parent

			//$mid = ((($target_item['verb'] == ACTIVITY_LIKE) || ($target_item['verb'] == ACTIVITY_DISLIKE)) ? $target_item['thr_parent'] : $target_item['mid']);

			// if we got a decoded hash we must encode it again before handing to javascript
			$mid = gen_link_id($target_item['mid']);

			$o .= '<div id="live-display"></div>' . "\r\n";
			$o .= "<script> var profile_uid = " . ((intval(local_channel())) ? local_channel() : (-1))
				. "; var netargs = '?f='; var profile_page = " . App::$pager['page'] . "; </script>\r\n";

			App::$page['htmlhead'] .= replace_macros(get_markup_template("build_query.tpl"),array(
				'$baseurl' => z_root(),
				'$pgtype'  => 'display',
				'$uid'     => '0',
				'$gid'     => '0',
				'$cid'     => '0',
				'$cmin'    => '(-1)',
				'$cmax'    => '(-1)',
				'$star'    => '0',
				'$liked'   => '0',
				'$conv'    => '0',
				'$spam'    => '0',
				'$fh'      => '0',
				'$dm'      => '0',
				'$nouveau' => '0',
				'$wall'    => '0',
				'$page'    => ((App::$pager['page'] != 1) ? App::$pager['page'] : 1),
				'$list'    => ((x($_REQUEST,'list')) ? intval($_REQUEST['list']) : 0),
				'$search'  => '',
				'$xchan'   => '',
				'$order'   => '',
				'$file'    => '',
				'$cats'    => '',
				'$tags'    => '',
				'$dend'    => '',
				'$dbegin'  => '',
				'$verb'    => '',
				'$net'     => '',
				'$mid'     => (($mid) ? urlencode($mid) : '')
			));

			head_add_link([
				'rel'   => 'alternate',
				'type'  => 'application/json+oembed',
				'href'  => z_root() . '/oep?f=&url=' . urlencode(z_root() . '/' . App::$query_string),
				'title' => 'oembed'
			]);

		}

		$observer_hash = get_observer_hash();
		$item_normal = item_normal();
		$item_normal_update = item_normal_update();
		$sql_extra = '';
		$r = [];

		if($noscript_content || $load) {
			require_once('include/channel.php');
			$sys = get_sys_channel();
			// in case somebody turned off public access to sys channel content using permissions
			// make that content unsearchable by ensuring the owner uid can't match
			$sys_id = perm_is_allowed($sys['channel_id'], $observer_hash, 'view_stream') ? $sys['channel_id'] : 0;

			if(local_channel()) {
				$r = q("SELECT item.id AS item_id FROM item WHERE uid = %d AND mid = '%s' $item_normal LIMIT 1",
					intval(local_channel()),
					dbesc($target_item['parent_mid'])
				);

			}

			if(!$r) {
				$sql_extra = item_permissions_sql(0, $observer_hash);

				$r = q("SELECT item.id AS item_id FROM item
					WHERE ((mid = '%s'
					AND (((( item.allow_cid = '' AND item.allow_gid = '' AND item.deny_cid  = ''
					AND item.deny_gid  = '' AND item_private = 0 )
					AND uid IN ( " . stream_perms_api_uids(($observer_hash) ? (PERMS_NETWORK|PERMS_PUBLIC) : PERMS_PUBLIC) . " ))
					OR uid = %d ))) OR
					(mid = '%s' $sql_extra ))
					$item_normal
					limit 1",
					dbesc($target_item['parent_mid']),
					intval($sys_id),
					dbesc($target_item['parent_mid'])
				);
			}
		}

		elseif($update && !$load) {
			require_once('include/channel.php');
			$sys = get_sys_channel();
			// in case somebody turned off public access to sys channel content using permissions
			// make that content unsearchable by ensuring the owner uid can't match
			$sys_id = perm_is_allowed($sys['channel_id'], $observer_hash, 'view_stream') ? $sys['channel_id'] : 0;

			if(local_channel()) {
				$r = q("SELECT item.parent AS item_id from item
					WHERE uid = %d
					AND parent_mid = '%s'
					$item_normal_update
					$simple_update
					LIMIT 1",
					intval(local_channel()),
					dbesc($target_item['parent_mid'])
				);
			}

			if(!$r) {
				$sql_extra = item_permissions_sql(0, $observer_hash);

				$r = q("SELECT item.id as item_id from item
					WHERE ((parent_mid = '%s'
					AND (((( item.allow_cid = '' AND item.allow_gid = '' AND item.deny_cid  = ''
					AND item.deny_gid  = '' AND item_private = 0 )
					and uid in ( " . stream_perms_api_uids(($observer_hash) ? (PERMS_NETWORK|PERMS_PUBLIC) : PERMS_PUBLIC) . " ))
					OR uid = %d ))) OR
					(parent_mid = '%s' $sql_extra ))
					$item_normal
					limit 1",
					dbesc($target_item['parent_mid']),
					intval($sys_id),
					dbesc($target_item['parent_mid'])
				);
			}
		}

		if($r) {
			$parents_str = ids_to_querystr($r,'item_id');
			if($parents_str) {
				$items = q("SELECT item.*, item.id AS item_id
					FROM item
					WHERE parent in ( %s ) $sql_extra $item_normal ",
					dbesc($parents_str)
				);
				xchan_query($items);
				$items = fetch_post_tags($items,true);
				$items = conv_sort($items,'created');
			}
		}
		else {
			$items = array();
		}

		switch($module_format) {

			case 'html':

				if ($update) {
					$o .= conversation($items, 'display', $update, 'client');
				}
				else {
					$o .= '<noscript>';
					if($noscript_content) {
						$o .= conversation($items, 'display', $update, 'traditional');
					}
					else {
						$o .= '<div class="section-content-warning-wrapper">' . t('You must enable javascript for your browser to be able to view this content.') . '</div>';
					}
					$o .= '</noscript>';

					if (isset($items[0]['title'])) {
						App::$page['title'] = $items[0]['title'] . ' - ' . App::$page['title'];
					}

					$o .= conversation($items, 'display', $update, 'client');
				}

				break;

			case 'atom':

				$atom = replace_macros(get_markup_template('atom_feed.tpl'), array(
					'$version'       => xmlify(\Zotlabs\Lib\System::get_project_version()),
					'$generator'     => xmlify(\Zotlabs\Lib\System::get_platform_name()),
					'$generator_uri' => 'https://hubzilla.org',
					'$feed_id'       => xmlify(App::$cmd),
					'$feed_title'    => xmlify(t('Article')),
					'$feed_updated'  => xmlify(datetime_convert('UTC', 'UTC', 'now', ATOM_TIME)),
					'$author'        => '',
					'$owner'         => '',
					'$profile_page'  => xmlify(z_root() . '/display/' . gen_link_id($target_item['mid'])),
				));

				$x = [ 'xml' => $atom, 'channel' => $channel, 'observer_hash' => $observer_hash, 'params' => $params ];
				call_hooks('atom_feed_top',$x);

				$atom = $x['xml'];

				// a much simpler interface
				call_hooks('atom_feed', $atom);


				if($items) {
					$type = 'html';
					foreach($items as $item) {
						if($item['item_private'])
							continue;
						$atom .= atom_entry($item, $type, null, '', true, '', false);
					}
				}

				call_hooks('atom_feed_end', $atom);

				$atom .= '</feed>' . "\r\n";

				header('Content-type: application/atom+xml');
				echo $atom;
				killme();
		}

		$o .= '<div id="content-complete"></div>';

		if((($update && $load) || $noscript_content) && (! $items)) {

			$r = q("SELECT id, item_deleted FROM item WHERE mid = '%s' LIMIT 1",
				dbesc($item_hash)
			);

			if($r) {
				if(intval($r[0]['item_deleted'])) {
					notice( t('Item has been removed.') . EOL );
				}
				else {
					notice( t('Permission denied.') . EOL );
				}
			}
			else {
				notice( t('Item not found.') . EOL );
			}

		}

		$_SESSION['loadtime'] = datetime_convert();

		return $o;

	}

}
