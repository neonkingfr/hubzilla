<?php

namespace Zotlabs\Module;

use App;
use URLify;
use Zotlabs\Lib\Config;
use Zotlabs\Lib\IConfig;
use Zotlabs\Lib\Enotify;
use Zotlabs\Web\Controller;
use Zotlabs\Daemon\Master;
use Zotlabs\Lib\Activity;
use Zotlabs\Lib\ActivityStreams;
use Zotlabs\Lib\LDSignatures;
use Zotlabs\Web\HTTPSig;
use Zotlabs\Lib\Libzot;
use Zotlabs\Lib\Libsync;
use Zotlabs\Lib\ThreadListener;
use Zotlabs\Access\PermissionRoles;

require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/security.php');
require_once('include/conversation.php');


/**
 *
 * This is the POST destination for most all locally posted
 * text stuff. This function handles status, wall-to-wall status,
 * local comments, and remote coments that are posted on this site
 * (as opposed to being delivered in a feed).
 * Also processed here are posts and comments coming through the
 * statusnet/twitter API.
 * All of these become an "item" which is our basic unit of
 * information.
 * Posts that originate externally or do not fall into the above
 * posting categories go through item_store() instead of this function.
 *
 */
class Item extends Controller {


	function init() {

		if (Libzot::is_zot_request()) {

			$item_id = argv(1);

			if (!$item_id)
				http_status_exit(404, 'Not found');

			$portable_id = EMPTY_STR;

			$item_normal_extra = sprintf(" and not verb in ('%s', '%s') ",
				dbesc(ACTIVITY_FOLLOW),
				dbesc(ACTIVITY_UNFOLLOW)
			);

			$item_normal = " and item.item_hidden = 0 and item.item_type = 0 and item.item_unpublished = 0 and item.item_delayed = 0 and item.item_blocked = 0 $item_normal_extra ";

			$i = null;

			// do we have the item (at all)?

			$r = q("select parent_mid from item where uuid = '%s' $item_normal limit 1",
				dbesc($item_id)
			);

			if (!$r) {
				http_status_exit(404, 'Not found');
			}

			// process an authenticated fetch

			$sigdata = HTTPSig::verify(($_SERVER['REQUEST_METHOD'] === 'POST') ? file_get_contents('php://input') : EMPTY_STR);
			if ($sigdata['portable_id'] && $sigdata['header_valid']) {
				$portable_id = $sigdata['portable_id'];
				if (!check_channelallowed($portable_id)) {
					http_status_exit(403, 'Permission denied');
				}
				if (!check_siteallowed($sigdata['signer'])) {
					http_status_exit(403, 'Permission denied');
				}
				observer_auth($portable_id);

				$i = q("select id as item_id, uid from item where mid = '%s' $item_normal and owner_xchan = '%s' limit 1",
					dbesc($r[0]['parent_mid']),
					dbesc($portable_id)
				);
			}
			elseif (Config::get('system', 'require_authenticated_fetch', false)) {
				http_status_exit(403, 'Permission denied');
			}

			// if we don't have a parent id belonging to the signer see if we can obtain one as a visitor that we have permission to access
			// with a bias towards those items owned by channels on this site (item_wall = 1)

			$sql_extra = item_permissions_sql(0);

			if (!$i) {
				$i = q("select id as item_id, uid, item_private from item where mid = '%s' $item_normal $sql_extra order by item_wall desc limit 1",
					dbesc($r[0]['parent_mid'])
				);
			}

			if (!$i) {
				http_status_exit(403, 'Forbidden');
			}

			$chan = channelx_by_n($i[0]['uid']);

			if (!$chan) {
				http_status_exit(404, 'Not found');
			}

			if (!perm_is_allowed($chan['channel_id'], get_observer_hash(), 'view_stream')) {
				http_status_exit(403, 'Forbidden');
			}

			$parents_str = ids_to_querystr($i, 'item_id');

			// We won't need to check for privacy mismatches if the verified observer is also owner
			$parent_item_private = ((isset($i[0]['item_private'])) ? " and item_private = " . intval($i[0]['item_private']) . " " : '');

			$total = q("SELECT count(*) AS count FROM item WHERE parent = %d $parent_item_private $item_normal ",
				intval($parents_str)
			);

			App::set_pager_total($total[0]['count']);
			App::set_pager_itemspage(30);

			if (App::$pager['total'] > App::$pager['itemspage']) {
				// let mod conversation handle this request
				App::$query_string = str_replace('item', 'conversation', App::$query_string);
				$i = Activity::paged_collection_init(App::$pager['total'], App::$query_string);
				as_return_and_die($i ,$chan);
			}
			else {
				$items = q("SELECT item.*, item.id AS item_id FROM item WHERE item.parent = %d $parent_item_private $item_normal ORDER BY item.id",
					intval($parents_str)
				);

				xchan_query($items, true);
				$items = fetch_post_tags($items, true);

				$i = Activity::encode_item_collection($items, App::$query_string, 'OrderedCollection', App::$pager['total']);
			}

			if ($portable_id && (!intval($items[0]['item_private']))) {
				$c = q("select abook_id from abook where abook_channel = %d and abook_xchan = '%s'",
					intval($items[0]['uid']),
					dbesc($portable_id)
				);
				if (!$c) {
					ThreadListener::store(z_root() . '/item/' . $item_id, $portable_id);
				}
			}

			as_return_and_die($i ,$chan);
		}

		if (ActivityStreams::is_as_request()) {

			$item_id = argv(1);
			if (!$item_id)
				http_status_exit(404, 'Not found');

			$portable_id = EMPTY_STR;

			$item_normal_extra = sprintf(" and not verb in ('%s', '%s') ",
				dbesc(ACTIVITY_FOLLOW),
				dbesc(ACTIVITY_UNFOLLOW)
			);

			$item_normal = " and item.item_hidden = 0 and item.item_type = 0 and item.item_unpublished = 0 and item.item_delayed = 0 and item.item_blocked = 0 $item_normal_extra ";

			$i = null;

			// do we have the item (at all)?
			// add preferential bias to item owners (item_wall = 1)

			$r = q("select * from item where uuid = '%s' $item_normal order by item_wall desc limit 1",
				dbesc($item_id)
			);

			if (!$r) {
				http_status_exit(404, 'Not found');
			}

			// process an authenticated fetch

			$sigdata = HTTPSig::verify(EMPTY_STR);
			if ($sigdata['portable_id'] && $sigdata['header_valid']) {
				$portable_id = $sigdata['portable_id'];
				if (!check_channelallowed($portable_id)) {
					http_status_exit(403, 'Permission denied');
				}
				if (!check_siteallowed($sigdata['signer'])) {
					http_status_exit(403, 'Permission denied');
				}
				observer_auth($portable_id);

				$i = q("select id as item_id from item where mid = '%s' $item_normal and owner_xchan = '%s' limit 1 ",
					dbesc($r[0]['parent_mid']),
					dbesc($portable_id)
				);
			}
			elseif (Config::get('system', 'require_authenticated_fetch', false)) {
				http_status_exit(403, 'Permission denied');
			}

			// if we don't have a parent id belonging to the signer see if we can obtain one as a visitor that we have permission to access
			// with a bias towards those items owned by channels on this site (item_wall = 1)

			$sql_extra = item_permissions_sql(0);

			if (!$i) {
				$i = q("select id as item_id from item where mid = '%s' $item_normal $sql_extra order by item_wall desc limit 1",
					dbesc($r[0]['parent_mid'])
				);
			}

			$bear = Activity::token_from_request();
			if ($bear) {
				logger('bear: ' . $bear, LOGGER_DEBUG);
				if (!$i) {
					$t = q("select * from iconfig where cat = 'ocap' and k = 'relay' and v = '%s'",
						dbesc($bear)
					);
					if ($t) {
						$i = q("select id as item_id from item where uuid = '%s' and id = %d $item_normal limit 1",
							dbesc($item_id),
							intval($t[0]['iid'])
						);
					}
				}
			}

			if (!$i) {
				http_status_exit(403, 'Forbidden');
			}

			// If we get to this point we have determined we can access the original in $r (fetched much further above), so use it.

			xchan_query($r, true);
			$items = fetch_post_tags($r, false);

			$chan = channelx_by_n($items[0]['uid']);

			if (!$chan)
				http_status_exit(404, 'Not found');

			if (!perm_is_allowed($chan['channel_id'], get_observer_hash(), 'view_stream'))
				http_status_exit(403, 'Forbidden');

			$i = Activity::encode_item($items[0]);

			if (!$i)
				http_status_exit(404, 'Not found');

			if ($portable_id && (!intval($items[0]['item_private']))) {
				$c = q("select abook_id from abook where abook_channel = %d and abook_xchan = '%s'",
					intval($items[0]['uid']),
					dbesc($portable_id)
				);
				if (!$c) {
					ThreadListener::store(z_root() . '/item/' . $item_id, $portable_id);
				}
			}

			as_return_and_die($i ,$chan);

		}


		if (argc() > 1 && argv(1) !== 'drop') {
			$x = q("select uid, item_wall, llink, mid from item where mid = '%s' or mid = '%s' or uuid = '%s'",
				dbesc(z_root() . '/item/' . argv(1)),
				dbesc(z_root() . '/activity/' . argv(1)),
				dbesc(argv(1))
			);
			if ($x) {
				foreach ($x as $xv) {
					if (intval($xv['item_wall'])) {
						$c = channelx_by_n($xv['uid']);
						if ($c) {
							goaway(z_root() . '/channel/' . $c['channel_address'] . '?mid=' . gen_link_id($xv['mid']));
						}
					}
				}
				goaway($x[0]['llink']);
			}
			http_status_exit(404, 'Not found');
		}

	}


	function post() {

		// This will change. Figure out who the observer is and whether or not
		// they have permission to post here. Else ignore the post.

		if ((!local_channel()) && (!remote_channel()) && (!x($_REQUEST, 'anonname')))
			return;

		$uid = local_channel();
		$token = '';

		$channel  = null;
		$observer = null;
		$datarray = [];

		$item_starred = false;
		$item_uplink = false;
		$item_notshown = false;
		$item_nsfw = false;
		$item_relay = false;
		$item_mentionsme = false;
		$item_verified = false;
		$item_retained = false;
		$item_rss = false;
		$item_deleted = false;
		$item_hidden = false;
		$item_unpublished = false;
		$item_delayed = false;
		$item_pending_remove = false;
		$item_blocked = false;

		/**
		 * Is this a reply to something?
		 */

		$parent     = ((x($_REQUEST, 'parent')) ? intval($_REQUEST['parent']) : 0);
		$parent_mid = ((x($_REQUEST, 'parent_mid')) ? trim($_REQUEST['parent_mid']) : '');
		$mode       = ((isset($_REQUEST['conv_mode']) && $_REQUEST['conv_mode'] === 'channel') ? 'channel' : 'network');

		$remote_xchan = ((x($_REQUEST, 'remote_xchan')) ? trim($_REQUEST['remote_xchan']) : false);
		$r            = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($remote_xchan)
		);
		if ($r)
			$remote_observer = $r[0];
		else
			$remote_xchan = $remote_observer = false;

		$profile_uid = ((x($_REQUEST, 'profile_uid')) ? intval($_REQUEST['profile_uid']) : 0);
		require_once('include/channel.php');

		$sys = get_sys_channel();
		if ($sys && $profile_uid && ($sys['channel_id'] == $profile_uid) && is_site_admin()) {
			$uid      = intval($sys['channel_id']);
			$channel  = $sys;
			$observer = $sys;
		}

		if (x($_REQUEST, 'dropitems')) {
			require_once('include/items.php');
			$arr_drop = explode(',', $_REQUEST['dropitems']);
			drop_items($arr_drop);
			$json = ['success' => 1];
			echo json_encode($json);
			killme();
		}

		call_hooks('post_local_start', $_REQUEST);

		// logger('postvars ' . print_r($_REQUEST,true), LOGGER_DATA);

		$api_source = ((x($_REQUEST, 'api_source') && $_REQUEST['api_source']) ? true : false);

		$consensus = $_REQUEST['consensus'] ?? 0;
		$nocomment = $_REQUEST['nocomment'] ?? 0;

		$is_poll = ((isset($_REQUEST['poll_answers'][0]) && $_REQUEST['poll_answers'][0]) && (isset($_REQUEST['poll_answers'][1]) && $_REQUEST['poll_answers'][1]));

		// 'origin' (if non-zero) indicates that this network is where the message originated,
		// for the purpose of relaying comments to other conversation members.
		// If using the API from a device (leaf node) you must set origin to 1 (default) or leave unset.
		// If the API is used from another network with its own distribution
		// and deliveries, you may wish to set origin to 0 or false and allow the other
		// network to relay comments.

		// If you are unsure, it is prudent (and important) to leave it unset.

		$origin = (($api_source && array_key_exists('origin', $_REQUEST)) ? intval($_REQUEST['origin']) : 1);

		// To represent message-ids on other networks - this will create an iconfig record

		$namespace = (($api_source && array_key_exists('namespace', $_REQUEST)) ? strip_tags($_REQUEST['namespace']) : '');
		$remote_id = (($api_source && array_key_exists('remote_id', $_REQUEST)) ? strip_tags($_REQUEST['remote_id']) : '');

		$owner_hash = null;

		$message_id    = ((x($_REQUEST, 'message_id') && $api_source) ? strip_tags($_REQUEST['message_id']) : null);
		$created       = ((x($_REQUEST, 'created')) ? datetime_convert(date_default_timezone_get(), 'UTC', $_REQUEST['created']) : datetime_convert());
		$post_id       = ((x($_REQUEST, 'post_id')) ? intval($_REQUEST['post_id']) : 0);
		$app           = ((x($_REQUEST, 'source')) ? strip_tags($_REQUEST['source']) : '');
		$return_path   = ((x($_REQUEST, 'return')) ? $_REQUEST['return'] : '');
		$preview       = ((x($_REQUEST, 'preview')) ? intval($_REQUEST['preview']) : 0);
		$categories    = ((x($_REQUEST, 'category')) ? escape_tags($_REQUEST['category']) : '');
		$webpage       = ((x($_REQUEST, 'webpage')) ? intval($_REQUEST['webpage']) : 0);
		$item_obscured = ((x($_REQUEST, 'obscured')) ? intval($_REQUEST['obscured']) : 0);
		$pagetitle     = ((x($_REQUEST, 'pagetitle')) ? escape_tags($_REQUEST['pagetitle']) : '');
		$layout_mid    = ((x($_REQUEST, 'layout_mid')) ? escape_tags($_REQUEST['layout_mid']) : '');
		$plink         = ((x($_REQUEST, 'permalink')) ? escape_tags($_REQUEST['permalink']) : '');
		$obj_type      = ((x($_REQUEST, 'obj_type')) ? escape_tags($_REQUEST['obj_type']) : ACTIVITY_OBJ_NOTE);

		// allow API to bulk load a bunch of imported items with sending out a bunch of posts.
		$nopush = ((x($_REQUEST, 'nopush')) ? intval($_REQUEST['nopush']) : 0);

		/*
		 * Check service class limits
		 */
		if ($uid && !(x($_REQUEST, 'parent')) && !(x($_REQUEST, 'post_id'))) {
			$ret = $this->item_check_service_class($uid, (($_REQUEST['webpage'] == ITEM_TYPE_WEBPAGE) ? true : false));
			if (!$ret['success']) {
				notice(t($ret['message']) . EOL);
				if ($api_source)
					return (['success' => false, 'message' => 'service class exception']);
				if (x($_REQUEST, 'return'))
					goaway(z_root() . "/" . $return_path);
				killme();
			}
		}

		if ($pagetitle) {
			$pagetitle = str_replace('/', '-', strtolower(URLify::transliterate($pagetitle)));
		}


		$expires = NULL_DATE;
		$comments_closed = NULL_DATE;

		$route          = '';
		$parent_item    = null;
		$parent_contact = null;
		$thr_parent     = '';
		$r              = false;

		if ($parent || $parent_mid) {

			if (!x($_REQUEST, 'type'))
				$_REQUEST['type'] = 'net-comment';

			if ($obj_type == ACTIVITY_OBJ_NOTE)
				$obj_type = ACTIVITY_OBJ_COMMENT;

			if ($parent) {
				$r = q("SELECT * FROM item WHERE id = %d LIMIT 1",
					intval($parent)
				);
			}
			elseif ($parent_mid && $uid) {
				// This is coming from an API source, and we are logged in
				$r = q("SELECT * FROM item WHERE mid = '%s' AND uid = %d LIMIT 1",
					dbesc($parent_mid),
					intval($uid)
				);
			}
			// if this isn't the real parent of the conversation, find it
			if ($r) {
				$parid      = $r[0]['parent'];
				$parent_mid = $r[0]['mid'];
				if ($r[0]['id'] != $r[0]['parent']) {
					$r = q("SELECT * FROM item WHERE id = parent AND parent = %d LIMIT 1",
						intval($parid)
					);
				}

				// if interacting with a pubstream item,
				// create a copy of the parent in your stream

				if ($r[0]['uid'] === $sys['channel_id'] && local_channel()) {
					$r = [copy_of_pubitem(App::get_channel(), $r[0]['mid'])];
				}
			}

			if (!$r) {
				notice(t('Unable to locate original post.') . EOL);
				if ($api_source)
					return (['success' => false, 'message' => 'invalid post id']);
				if (x($_REQUEST, 'return'))
					goaway(z_root() . "/" . $return_path);
				killme();
			}

			xchan_query($r, true);

			$parent_item = $r[0];
			$parent      = $r[0]['id'];

			// multi-level threading - preserve the info but re-parent to our single level threading

			$thr_parent = $parent_mid;

			$route = $parent_item['route'];

		}

		$moderated = false;

		if (!$observer) {
			$observer = App::get_observer();
			if (!$observer) {
				$observer = anon_identity_init($_REQUEST);
				if ($observer) {
					$moderated    = true;
					$remote_xchan = $remote_observer = $observer;
				}
			}
		}

		if (!$observer) {
			notice(t('Permission denied.') . EOL);
			if ($api_source)
				return (['success' => false, 'message' => 'permission denied']);
			if (x($_REQUEST, 'return'))
				goaway(z_root() . "/" . $return_path);
			killme();
		}

		if ($parent) {
			logger('mod_item: item_post parent=' . $parent);
			$can_comment = false;

			$can_comment = can_comment_on_post($observer['xchan_hash'], $parent_item);
			if (!$can_comment) {
				if ((array_key_exists('owner', $parent_item)) && intval($parent_item['owner']['abook_self']) == 1)
					$can_comment = perm_is_allowed($profile_uid, $observer['xchan_hash'], 'post_comments');
			}

			if (!$can_comment) {
				notice(t('Permission denied.') . EOL);
				if ($api_source)
					return (['success' => false, 'message' => 'permission denied']);
				if (x($_REQUEST, 'return'))
					goaway(z_root() . "/" . $return_path);
				killme();
			}
		}
		else {
			if (!perm_is_allowed($profile_uid, $observer['xchan_hash'], ($webpage) ? 'write_pages' : 'post_wall')) {
				notice(t('Permission denied.') . EOL);
				if ($api_source)
					return (['success' => false, 'message' => 'permission denied']);
				if (x($_REQUEST, 'return'))
					goaway(z_root() . "/" . $return_path);
				killme();
			}
		}


		// is this an edited post?

		$orig_post = null;

		if ($namespace && $remote_id) {
			// It wasn't an internally generated post - see if we've got an item matching this remote service id
			$i = q("select iid from iconfig where cat = 'system' and k = '%s' and v = '%s' limit 1",
				dbesc($namespace),
				dbesc($remote_id)
			);
			if ($i)
				$post_id = $i[0]['iid'];
		}

		$iconfig = null;

		if ($post_id) {
			$i = q("SELECT * FROM item WHERE uid = %d AND id = %d LIMIT 1",
				intval($profile_uid),
				intval($post_id)
			);
			if (!count($i))
				killme();
			$orig_post = $i[0];
			$iconfig   = q("select * from iconfig where iid = %d",
				intval($post_id)
			);
		}


		if (!$channel) {
			if ($uid && $uid == $profile_uid) {
				$channel = App::get_channel();
			}
			else {
				// posting as yourself but not necessarily to a channel you control
				$r = q("select * from channel left join account on channel_account_id = account_id where channel_id = %d LIMIT 1",
					intval($profile_uid)
				);
				if ($r)
					$channel = $r[0];
			}
		}


		if (!$channel) {
			logger("mod_item: no channel.");
			if ($api_source)
				return (['success' => false, 'message' => 'no channel']);
			if (x($_REQUEST, 'return'))
				goaway(z_root() . "/" . $return_path);
			killme();
		}

		$owner_xchan = null;

		$r = q("select * from xchan where xchan_hash = '%s' limit 1",
			dbesc($channel['channel_hash'])
		);
		if ($r && count($r)) {
			$owner_xchan = $r[0];
		}
		else {
			logger("mod_item: no owner.");
			if ($api_source)
				return (['success' => false, 'message' => 'no owner']);
			if (x($_REQUEST, 'return'))
				goaway(z_root() . "/" . $return_path);
			killme();
		}

		$walltowall         = false;
		$walltowall_comment = false;

		if ($remote_xchan && !$moderated)
			$observer = $remote_observer;

		if ($observer) {
			logger('mod_item: post accepted from ' . $observer['xchan_name'] . ' for ' . $owner_xchan['xchan_name'], LOGGER_DEBUG);

			// wall-to-wall detection.
			// For top-level posts, if the author and owner are different it's a wall-to-wall
			// For comments, We need to additionally look at the parent and see if it's a wall post that originated locally.

			if ($observer['xchan_name'] != $owner_xchan['xchan_name']) {
				if (($parent_item) && ($parent_item['item_wall'] && $parent_item['item_origin'])) {
					$walltowall_comment = true;
					$walltowall         = true;
				}
				if (!$parent) {
					$walltowall = true;
				}
			}
		}

		$acl = new \Zotlabs\Access\AccessList($channel);

		$view_policy    = \Zotlabs\Access\PermissionLimits::Get($channel['channel_id'], 'view_stream');
		$comment_policy = \Zotlabs\Access\PermissionLimits::Get($channel['channel_id'], 'post_comments');

		$public_policy = ((x($_REQUEST, 'public_policy')) ? escape_tags($_REQUEST['public_policy']) : map_scope($view_policy, true));
		if ($webpage)
			$public_policy = '';
		if ($public_policy)
			$private = 1;

		if ($orig_post) {
			$private = 0;
			// webpages are allowed to change ACLs after the fact. Normal conversation items aren't.
			if ($webpage) {
				$acl->set_from_array($_REQUEST);
			}
			else {
				$acl->set($orig_post);
				$public_policy = $orig_post['public_policy'];
				$private       = $orig_post['item_private'];
			}

			if ($public_policy || $acl->is_private()) {
				$private = (($private) ? $private : 1);
			}

			$location            = $orig_post['location'];
			$coord               = $orig_post['coord'];
			$verb                = $orig_post['verb'];
			$app                 = $orig_post['app'];
			$title               = escape_tags(trim($_REQUEST['title']));
			$summary             = trim($_REQUEST['summary']);
			$body                = trim($_REQUEST['body']);
			$item_flags          = $orig_post['item_flags'];
			$item_origin         = $orig_post['item_origin'];
			$item_unseen         = $orig_post['item_unseen'];
			$item_starred        = $orig_post['item_starred'];
			$item_uplink         = $orig_post['item_uplink'];
			$item_consensus      = $orig_post['item_consensus'];
			$item_wall           = $orig_post['item_wall'];
			$item_thread_top     = $orig_post['item_thread_top'];
			$item_notshown       = $orig_post['item_notshown'];
			$item_nsfw           = $orig_post['item_nsfw'];
			$item_relay          = $orig_post['item_relay'];
			$item_mentionsme     = $orig_post['item_mentionsme'];
			$item_nocomment      = $orig_post['item_nocomment'];
			$item_obscured       = $orig_post['item_obscured'];
			$item_verified       = $orig_post['item_verified'];
			$item_retained       = $orig_post['item_retained'];
			$item_rss            = $orig_post['item_rss'];
			$item_deleted        = $orig_post['item_deleted'];
			$item_type           = $orig_post['item_type'];
			$item_hidden         = $orig_post['item_hidden'];
			$item_unpublished    = $orig_post['item_unpublished'];
			$item_delayed        = $orig_post['item_delayed'];
			$item_pending_remove = $orig_post['item_pending_remove'];
			$item_blocked        = $orig_post['item_blocked'];
			$postopts            = $orig_post['postopts'];
			$created             = $orig_post['created'];
			$expires             = $orig_post['expires'];
			$comments_closed     = $orig_post['comments_closed'];
			$mid                 = $orig_post['mid'];
			$uuid                = $orig_post['uuid'];
			$thr_parent          = $orig_post['thr_parent'];
			$parent_mid          = $orig_post['parent_mid'];
			$plink               = $orig_post['plink'];
		}
		else {
			if (!$walltowall) {
				if ((array_key_exists('contact_allow', $_REQUEST))
					|| (array_key_exists('group_allow', $_REQUEST))
					|| (array_key_exists('contact_deny', $_REQUEST))
					|| (array_key_exists('group_deny', $_REQUEST))) {
					$acl->set_from_array($_REQUEST);
				}
				elseif (!$api_source) {

					// if no ACL has been defined and we aren't using the API, the form
					// didn't send us any parameters. This means there's no ACL or it has
					// been reset to the default audience.
					// If $api_source is set and there are no ACL parameters, we default
					// to the channel permissions which were set in the ACL contructor.

					$acl->set(['allow_cid' => '', 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '']);
				}
			}


			$location = ((isset($_REQUEST['location'])) ? notags(trim($_REQUEST['location'])) : '');
			$coord    = ((isset($_REQUEST['coord'])) ? notags(trim($_REQUEST['coord'])) : '');
			$verb     = ((isset($_REQUEST['verb'])) ? notags(trim($_REQUEST['verb'])) : '');
			$title    = ((isset($_REQUEST['title'])) ? escape_tags(trim($_REQUEST['title'])) : '');
			$summary  = ((isset($_REQUEST['summary'])) ? trim($_REQUEST['summary']) : '');
			$body     = ((isset($_REQUEST['body'])) ? trim($_REQUEST['body']) : '');
			$body     .= ((isset($_REQUEST['attachment'])) ? trim($_REQUEST['attachment']) : '');
			$postopts = '';

			$allow_empty = ((array_key_exists('allow_empty', $_REQUEST)) ? intval($_REQUEST['allow_empty']) : 0);

			$private = ((isset($private) && $private) ? $private : intval($acl->is_private() || ($public_policy)));

			// If this is a comment, set the permissions from the parent.

			if ($parent_item) {
				$acl->set($parent_item);
				$private       = intval($parent_item['item_private']);
				$public_policy = $parent_item['public_policy'];
				$owner_hash    = $parent_item['owner_xchan'];
				$webpage       = $parent_item['item_type'];
			}



			if ((!$allow_empty) && (!strlen($body))) {
				if ($preview)
					killme();
				info(t('Empty post discarded.') . EOL);
				if ($api_source)
					return (['success' => false, 'message' => 'no content']);
				if (x($_REQUEST, 'return'))
					goaway(z_root() . "/" . $return_path);
				killme();
			}
		}


		if (feature_enabled($profile_uid, 'content_expire')) {
			if (x($_REQUEST, 'expire')) {
				$expires = datetime_convert(date_default_timezone_get(), 'UTC', $_REQUEST['expire']);
				if ($expires <= datetime_convert())
					$expires = NULL_DATE;
			}
		}


		$mimetype = ((isset($_REQUEST['mimetype'])) ? notags(trim($_REQUEST['mimetype'])) : '');

		if (!$mimetype)
			$mimetype = 'text/bbcode';


		$execflag = ((intval($uid) == intval($profile_uid)
			&& ($channel['channel_pageflags'] & PAGE_ALLOWCODE)) ? true : false);

		if ($preview) {
			$summary = z_input_filter($summary, $mimetype, $execflag);
			$body    = z_input_filter($body, $mimetype, $execflag);
		}


		$arr = ['profile_uid' => $profile_uid, 'summary' => $summary, 'content' => $body, 'mimetype' => $mimetype];
		call_hooks('post_content', $arr);
		$summary  = $arr['summary'];
		$body     = $arr['content'];
		$mimetype = $arr['mimetype'];


		$gacl              = $acl->get();
		$str_contact_allow = $gacl['allow_cid'];
		$str_group_allow   = $gacl['allow_gid'];
		$str_contact_deny  = $gacl['deny_cid'];
		$str_group_deny    = $gacl['deny_gid'];


		$groupww = false;

		// if this is a wall-to-wall post to a group, turn it into a direct message

		$is_group = get_pconfig($profile_uid, 'system', 'group_actor');

		if ($is_group && $walltowall && !$walltowall_comment && !$webpage) {
			$groupww           = true;
			$str_contact_allow = $owner_xchan['xchan_hash'];
			$str_group_allow   = '';
		}

		$post_tags = [];

		if ($mimetype === 'text/bbcode') {

			require_once('include/text.php');

			// BBCODE alert: the following functions assume bbcode input
			// and will require alternatives for alternative content-types (text/html, text/markdown, text/plain, etc.)
			// we may need virtual or template classes to implement the possible alternatives

			$body = cleanup_bbcode($body);

			// Look for tags and linkify them

			$results = linkify_tags($body, ($uid) ? $uid : $profile_uid);

			if ($results) {

				// Set permissions based on tag replacements
				set_linkified_perms($results, $str_contact_allow, $str_group_allow, $profile_uid, $private, $parent_item);

				foreach ($results as $result) {
					$success = $result['success'];
					if ($success['replaced']) {
						$post_tags[] = [
							'uid'   => $profile_uid,
							'ttype' => $success['termtype'],
							'otype' => TERM_OBJ_POST,
							'term'  => $success['term'],
							'url'   => $success['url']
						];
					}
				}

			}

			if (($str_contact_allow) && (!$str_group_allow)) {
				// direct message - private between individual channels but not groups
				$private = 2;
			}

			if ($private && get_pconfig($profile_uid, 'system', 'ocap_enabled')) {
				// for edited posts, re-use any existing OCAP token (if found).
				// Otherwise generate a new one.

				if ($iconfig) {
					foreach ($iconfig as $cfg) {
						if ($cfg['cat'] === 'ocap' && $cfg['k'] === 'relay') {
							$token = $cfg['v'];
						}
					}
				}
				if (!$token) {
					$token = new_token();
				}
			}

			/**
			 *
			 * When a photo was uploaded into the message using the (profile wall) ajax
			 * uploader, The permissions are initially set to disallow anybody but the
			 * owner from seeing it. This is because the permissions may not yet have been
			 * set for the post. If it's private, the photo permissions should be set
			 * appropriately. But we didn't know the final permissions on the post until
			 * now. So now we'll look for links of uploaded photos and attachments that are in the
			 * post and set them to the same permissions as the post itself.
			 *
			 * If the post was end-to-end encrypted we can't find images and attachments in the body,
			 * use our media_str input instead which only contains these elements - but only do this
			 * when encrypted content exists because the photo/attachment may have been removed from
			 * the post and we should keep it private. If it's encrypted we have no way of knowing
			 * so we'll set the permissions regardless and realise that the media may not be
			 * referenced in the post.
			 *
			 */

			if (!$preview) {
				fix_attached_permissions($profile_uid, ((strpos($body, '[/crypt]')) ? $_POST['media_str'] : $body), $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny, $token);
				//fix_attached_photo_permissions($profile_uid, $owner_xchan['xchan_hash'], ((strpos($body, '[/crypt]')) ? $_POST['media_str'] : $body), $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny, $token);
				//fix_attached_file_permissions($channel, $observer['xchan_hash'], ((strpos($body, '[/crypt]')) ? $_POST['media_str'] : $body), $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny, $token);
			}

			$attachments = '';
			$match       = false;

			if (preg_match_all('/(\[attachment\](.*?)\[\/attachment\])/', $body, $match)) {
				$attachments = [];
				$i           = 0;
				foreach ($match[2] as $mtch) {
					$attach_link = '';
					$hash        = substr($mtch, 0, strpos($mtch, ','));
					$rev         = intval(substr($mtch, strpos($mtch, ',')));
					$r           = attach_by_hash_nodata($hash, $observer['xchan_hash'], $rev);
					if ($r['success']) {
						$attachments[] = [
							'href'     => z_root() . '/attach/' . $r['data']['hash'],
							'length'   => $r['data']['filesize'],
							'type'     => $r['data']['filetype'],
							'title'    => urlencode($r['data']['filename']),
							'revision' => $r['data']['revision']
						];
					}
					$body = str_replace($match[1][$i], $attach_link, $body);
					$i++;
				}
			}

			if (preg_match_all('/(\[share=(.*?)\](.*?)\[\/share\])/', $body, $match)) {

				// process share by id

				$i = 0;
				foreach ($match[2] as $mtch) {
					$reshare = new \Zotlabs\Lib\Share($mtch);
					$body    = str_replace($match[1][$i], $reshare->bbcode(), $body);
					$i++;
				}
			}

			// BBCODE end alert
		}

		if (strlen($categories)) {

			$cats = explode(',', $categories);
			foreach ($cats as $cat) {

				$catlink = $owner_xchan['xchan_url'] . '?f=&cat=' . urlencode(trim($cat));

				$post_tags[] = [
					'uid'   => $profile_uid,
					'ttype' => TERM_CATEGORY,
					'otype' => TERM_OBJ_POST,
					'term'  => trim($cat),
					'url'   => $catlink
				];
			}
		}

		if ($orig_post) {
			// preserve original tags
			$t = q("select * from term where oid = %d and otype = %d and uid = %d and ttype in ( %d, %d, %d )",
				intval($orig_post['id']),
				intval(TERM_OBJ_POST),
				intval($profile_uid),
				intval(TERM_UNKNOWN),
				intval(TERM_FILE),
				intval(TERM_COMMUNITYTAG)
			);
			if ($t) {
				foreach ($t as $t1) {
					$post_tags[] = [
						'uid'   => $profile_uid,
						'ttype' => $t1['ttype'],
						'otype' => TERM_OBJ_POST,
						'term'  => $t1['term'],
						'url'   => $t1['url'],
					];
				}
			}
		}

		$item_unseen    = ((local_channel() != $profile_uid) ? 1 : 0);
		$item_wall      = ((isset($_REQUEST['type']) && ($_REQUEST['type'] === 'wall' || $_REQUEST['type'] === 'wall-comment')) ? 1 : 0);
		$item_origin    = (($origin) ? 1 : 0);
		$item_consensus = (($consensus) ? 1 : 0);
		$item_nocomment = (($nocomment) ? 1 : 0);

		// determine if this is a wall post

		if ($parent) {
			$item_wall = $parent_item['item_wall'];
		}
		else {
			if (!$webpage) {
				$item_wall = 1;
			}
		}


		if ($moderated)
			$item_blocked = ITEM_MODERATED;


		if (!strlen($verb))
			$verb = ACTIVITY_POST;

		$notify_type = (($parent) ? 'comment-new' : 'wall-new');

		$uuid = $uuid ?? $message_id ?? item_message_id();
		$mid = $mid ?? z_root() . '/item/' . $uuid;

		if ($is_poll) {
			$poll = [
				'question'         => $body,
				'answers'          => $_REQUEST['poll_answers'],
				'multiple_answers' => $_REQUEST['poll_multiple_answers'],
				'expire_value'     => $_REQUEST['poll_expire_value'],
				'expire_unit'      => $_REQUEST['poll_expire_unit']
			];
			$obj  = $this->extract_poll_data($poll, ['item_private' => $private, 'allow_cid' => $str_contact_allow, 'allow_gid' => $str_contact_deny]);
		}
		else {
			$obj = $this->extract_bb_poll_data($body, ['item_private' => $private, 'allow_cid' => $str_contact_allow, 'allow_gid' => $str_contact_deny]);
		}

		if ($obj) {
			$obj['url']           = $mid;
			$obj['id']            = $mid;
			$obj['diaspora:guid'] = $uuid;
			$obj['attributedTo']  = channel_url($channel);
			$obj['published']     = $created;
			$obj['name']          = $title;

			$datarray['obj']      = $obj;

			if ($obj['endTime']) {
				$d = datetime_convert('UTC','UTC', $obj['endTime']);
				if ($d > NULL_DATE) {
					$comments_closed = $d;
				}
			}

			$obj_type = 'Question';
		}

		if (!$parent_mid) {
			$parent_mid = $mid;
		}

		if ($parent_item)
			$parent_mid = $parent_item['mid'];


		// Fallback so that we always have a thr_parent

		if (!$thr_parent)
			$thr_parent = $mid;


		$item_thread_top = ((!$parent) ? 1 : 0);

		if ((!$plink) && ($item_thread_top)) {
			$plink = $mid;
		}

		if (isset($datarray['obj']) && $datarray['obj']) {
			$datarray['obj']['id'] = $mid;
		}

		$datarray['aid']                 = $channel['channel_account_id'];
		$datarray['uid']                 = $profile_uid;
		$datarray['uuid']                = $uuid;
		$datarray['owner_xchan']         = (($owner_hash) ? $owner_hash : $owner_xchan['xchan_hash']);
		$datarray['author_xchan']        = $observer['xchan_hash'];
		$datarray['created']             = $created;
		$datarray['edited']              = (($orig_post) ? datetime_convert() : $created);
		$datarray['expires']             = $expires;
		$datarray['comments_closed']     = (($nocomment) ? $created : $comments_closed);
		$datarray['commented']           = (($orig_post) ? datetime_convert() : $created);
		$datarray['received']            = (($orig_post) ? datetime_convert() : $created);
		$datarray['changed']             = (($orig_post) ? datetime_convert() : $created);
		$datarray['mid']                 = $mid;
		$datarray['parent_mid']          = $parent_mid;
		$datarray['mimetype']            = $mimetype;
		$datarray['title']               = $title;
		$datarray['summary']             = $summary;
		$datarray['body']                = $body;
		$datarray['app']                 = $app;
		$datarray['location']            = $location;
		$datarray['coord']               = $coord;
		$datarray['verb']                = $verb;
		$datarray['obj_type']            = $obj_type;
		$datarray['allow_cid']           = $str_contact_allow;
		$datarray['allow_gid']           = $str_group_allow;
		$datarray['deny_cid']            = $str_contact_deny;
		$datarray['deny_gid']            = $str_group_deny;
		$datarray['attach']              = $attachments;
		$datarray['thr_parent']          = $thr_parent;
		$datarray['postopts']            = $postopts;
		$datarray['item_unseen']         = intval($item_unseen);
		$datarray['item_wall']           = intval($item_wall);
		$datarray['item_origin']         = intval($item_origin);
		$datarray['item_type']           = $webpage;
		$datarray['item_private']        = intval($private);
		$datarray['item_thread_top']     = intval($item_thread_top);
		$datarray['item_starred']        = intval($item_starred);
		$datarray['item_uplink']         = intval($item_uplink);
		$datarray['item_consensus']      = intval($item_consensus);
		$datarray['item_notshown']       = intval($item_notshown);
		$datarray['item_nsfw']           = intval($item_nsfw);
		$datarray['item_relay']          = intval($item_relay);
		$datarray['item_mentionsme']     = intval($item_mentionsme);
		$datarray['item_nocomment']      = intval($item_nocomment);
		$datarray['item_obscured']       = intval($item_obscured);
		$datarray['item_verified']       = intval($item_verified);
		$datarray['item_retained']       = intval($item_retained);
		$datarray['item_rss']            = intval($item_rss);
		$datarray['item_deleted']        = intval($item_deleted);
		$datarray['item_hidden']         = intval($item_hidden);
		$datarray['item_unpublished']    = intval($item_unpublished);
		$datarray['item_delayed']        = intval($item_delayed);
		$datarray['item_pending_remove'] = intval($item_pending_remove);
		$datarray['item_blocked']        = intval($item_blocked);
		$datarray['layout_mid']          = $layout_mid;
		$datarray['public_policy']       = $public_policy;
		$datarray['comment_policy']      = map_scope($comment_policy);
		$datarray['term']                = array_unique($post_tags, SORT_REGULAR);
		$datarray['plink']               = $plink;
		$datarray['route']               = $route;

		// A specific ACL over-rides public_policy completely

		if (!empty_acl($datarray))
			$datarray['public_policy'] = '';

		if ($iconfig) {
			$datarray['iconfig'] = $iconfig;
		}

		if ($token) {
			IConfig::set($datarray, 'ocap', 'relay', $token);
		}

		// preview mode - prepare the body for display and send it via json

		if ($preview) {
			require_once('include/conversation.php');

			$datarray['owner']  = $owner_xchan;
			$datarray['author'] = $observer;
			$datarray['attach'] = json_encode($datarray['attach']);
			$o                  = conversation([$datarray], 'search', false, 'preview');
			//		logger('preview: ' . $o, LOGGER_DEBUG);
			echo json_encode(['preview' => $o]);
			killme();
		}
		if ($orig_post)
			$datarray['edit'] = true;

		// suppress duplicates, *unless* you're editing an existing post. This could get picked up
		// as a duplicate if you're editing it very soon after posting it initially and you edited
		// some attribute besides the content, such as title or categories.

		if (feature_enabled($profile_uid, 'suppress_duplicates') && (!$orig_post)) {

			$z = q("select created from item where uid = %d and created > %s - INTERVAL %s and body = '%s' limit 1",
				intval($profile_uid),
				db_utcnow(),
				db_quoteinterval('2 MINUTE'),
				dbesc($body)
			);

			if ($z) {
				$datarray['cancel'] = 1;
				notice(t('Duplicate post suppressed.') . EOL);
				logger('Duplicate post. Faking plugin cancel.');
			}
		}

		call_hooks('post_local', $datarray);

		if (x($datarray, 'cancel')) {
			logger('mod_item: post cancelled by plugin or duplicate suppressed.');
			if ($return_path)
				goaway(z_root() . "/" . $return_path);
			if ($api_source)
				return (['success' => false, 'message' => 'operation cancelled']);
			$json           = ['cancel' => 1];
			$json['reload'] = z_root() . '/' . $_REQUEST['jsreload'];
			echo json_encode($json);
			killme();
		}


		if (mb_strlen($datarray['title']) > 191)
			$datarray['title'] = mb_substr($datarray['title'], 0, 191);

		if ($webpage) {
			IConfig::Set($datarray, 'system', webpage_to_namespace($webpage),
				(($pagetitle) ? $pagetitle : basename($datarray['mid'])), true);
		}
		elseif ($namespace) {
			IConfig::Set($datarray, 'system', $namespace,
				(($remote_id) ? $remote_id : basename($datarray['mid'])), true);
		}

		if ($orig_post) {
			$datarray['id'] = $post_id;

			$x = item_store_update($datarray, $execflag);

			if ($x['success']) {
				$this->add_listeners($datarray);
			}

			// We only need edit activities for other federated protocols
			// which do not support edits natively. While this does federate
			// edits, it presents a number of issues locally - such as #757 and #758.
			// The SQL check for an edit activity would not perform that well so to fix these issues
			// requires an additional item flag (perhaps 'item_edit_activity') that we can add to the
			// query for searches and notifications.

			// For now we'll just forget about trying to make edits work on network protocols that
			// don't support them.

			// item_create_edit_activity($x);

			if (!$parent) {
				$r = q("select * from item where id = %d",
					intval($post_id)
				);
				if ($r) {
					xchan_query($r);
					$sync_item = fetch_post_tags($r);
					Libsync::build_sync_packet($profile_uid, ['item' => [encode_item($sync_item[0], true)]]);
				}
			}
			if (!$nopush)
				Master::Summon(['Notifier', 'edit_post', $post_id]);


			if ($api_source)
				return ($x);

			if ((x($_REQUEST, 'return')) && strlen($return_path)) {
				logger('return: ' . $return_path);
				goaway(z_root() . "/" . $return_path);
			}
			killme();
		}

		$post = item_store($datarray, $execflag);

		if ($post['success']) {
			$this->add_listeners($datarray);
		}

		$post_id = $post['item_id'];

		$datarray = $post['item'];

		if ($post_id) {
			logger('mod_item: saved item ' . $post_id);

			if ($parent) {

				// prevent conversations which you are involved from being expired

				if (local_channel())
					retain_item($parent);

				// only send comment notification if this is a wall-to-wall comment,
				// otherwise it will happen during delivery

				if (($datarray['owner_xchan'] != $datarray['author_xchan']) && (intval($parent_item['item_wall']))) {
					Enotify::submit([
						'type'       => NOTIFY_COMMENT,
						'from_xchan' => $datarray['author_xchan'],
						'to_xchan'   => $datarray['owner_xchan'],
						'item'       => $datarray,
						'link'       => z_root() . '/display/' . gen_link_id($datarray['mid']),
						'verb'       => ACTIVITY_POST,
						'otype'      => 'item',
						'parent'     => $parent,
						'parent_mid' => $parent_item['mid']
					]);

				}
			}
			else {
				$parent = $post_id;

				if (($datarray['owner_xchan'] != $datarray['author_xchan']) && ($datarray['item_type'] == ITEM_TYPE_POST)) {
					Enotify::submit([
						'type'       => NOTIFY_WALL,
						'from_xchan' => $datarray['author_xchan'],
						'to_xchan'   => $datarray['owner_xchan'],
						'item'       => $datarray,
						'link'       => z_root() . '/display/' . gen_link_id($datarray['mid']),
						'verb'       => ACTIVITY_POST,
						'otype'      => 'item'
					]);
				}

				if ($uid && $uid == $profile_uid && (is_item_normal($datarray))) {
					q("update channel set channel_lastpost = '%s' where channel_id = %d",
						dbesc(datetime_convert()),
						intval($uid)
					);
				}
			}

			// photo comments turn the corresponding item visible to the profile wall
			// This way we don't see every picture in your new photo album posted to your wall at once.
			// They will show up as people comment on them.

			if ($parent_item && intval($parent_item['item_hidden'])) {
				$r = q("UPDATE item SET item_hidden = 0 WHERE id = %d",
					intval($parent_item['id'])
				);
			}
		}
		else {
			logger('mod_item: unable to retrieve post that was just stored.');
			notice(t('System error. Post not saved.') . EOL);
			if ($return_path)
				goaway(z_root() . "/" . $return_path);
			if ($api_source)
				return (['success' => false, 'message' => 'system error']);
			killme();
		}

		if ($parent || $datarray['item_private'] == 1) {
			$r = q("select * from item where id = %d",
				intval($post_id)
			);
			if ($r) {
				xchan_query($r);
				$sync_item = fetch_post_tags($r);
				Libsync::build_sync_packet($profile_uid, ['item' => [encode_item($sync_item[0], true)]]);
			}
		}

		$datarray['id']    = $post_id;
		$datarray['llink'] = z_root() . '/display/' . gen_link_id($datarray['mid']);

		call_hooks('post_local_end', $datarray);

		if ($groupww) {
			$nopush = false;
		}

		if (!$nopush)
			Master::Summon(['Notifier', $notify_type, $post_id]);

		logger('post_complete');

		if ($moderated) {
			info(t('Your comment is awaiting approval.') . EOL);
		}

		// figure out how to return, depending on from whence we came

		if ($api_source)
			return $post;

		if ($return_path) {
			if ($return_path === 'hq') {
				goaway(z_root() . '/hq/' . gen_link_id($datarray['mid']));
			}

			goaway(z_root() . "/" . $return_path);
		}

		if ($mode === 'channel')
			profile_load($channel['channel_address']);

		$item[]            = $datarray;
		$item[0]['owner']  = $owner_xchan;
		$item[0]['author'] = $observer;
		$item[0]['attach'] = $datarray['attach'];

		$json = [
			'success' => 1,
			'id'      => $post_id,
			'html'    => conversation($item, $mode, true, 'r_preview'),
		];

		if (x($_REQUEST, 'jsreload') && strlen($_REQUEST['jsreload']))
			$json['reload'] = z_root() . '/' . $_REQUEST['jsreload'];

		logger('post_json: ' . print_r($json, true), LOGGER_DEBUG);

		echo json_encode($json);
		killme();
		// NOTREACHED
	}


	function get() {

		if ((!local_channel()) && (!remote_channel()))
			return;

		if ((argc() == 3) && (argv(1) === 'drop') && intval(argv(2))) {

			require_once('include/items.php');


			$i = q("select id, uid, item_origin, author_xchan, owner_xchan, source_xchan, item_type from item where id = %d limit 1",
				intval(argv(2))
			);

			if ($i) {
				$can_delete   = false;
				$local_delete = false;

				if (local_channel() && local_channel() == $i[0]['uid']) {
					$local_delete = true;
				}

				$ob_hash = get_observer_hash();
				if ($ob_hash && ($ob_hash === $i[0]['author_xchan'] || $ob_hash === $i[0]['owner_xchan'] || $ob_hash === $i[0]['source_xchan'])) {
					$can_delete = true;
				}

				// The site admin can delete any post/item on the site.
				// If the item originated on this site+channel the deletion will propagate downstream.
				// Otherwise just the local copy is removed.

				if (is_site_admin()) {
					$local_delete = true;
					if (intval($i[0]['item_origin']))
						$can_delete = true;
				}


				if (!($can_delete || $local_delete)) {
					notice(t('Permission denied.') . EOL);
					return;
				}

				// if this is a different page type or it's just a local delete
				// but not by the item author or owner, do a simple deletion

				$complex = false;

				if (intval($i[0]['item_type']) || ($local_delete && (!$can_delete))) {
					drop_item($i[0]['id']);
				}
				else {
					// complex deletion that needs to propagate and be performed in phases
					drop_item($i[0]['id'], true, DROPITEM_PHASE1);
					$complex = true;
				}

				$r = q("select * from item where id = %d",
					intval($i[0]['id'])
				);
				if ($r) {
					xchan_query($r);
					$sync_item = fetch_post_tags($r);
					Libsync::build_sync_packet($i[0]['uid'], ['item' => [encode_item($sync_item[0], true)]]);
				}

				if ($complex) {
					tag_deliver($i[0]['uid'], $i[0]['id']);
				}

			}

			killme();

		}
	}


	function item_check_service_class($channel_id, $iswebpage) {
		$ret = ['success' => false, 'message' => ''];

		if ($iswebpage) {
			$r = q("select count(i.id)  as total from item i
				right join channel c on (i.author_xchan=c.channel_hash and i.uid=c.channel_id )
				and i.parent=i.id and i.item_type = %d and i.item_deleted = 0 and i.uid= %d ",
				intval(ITEM_TYPE_WEBPAGE),
				intval($channel_id)
			);
		}
		else {
			$r = q("select count(id) as total from item where parent = id and item_wall = 1 and uid = %d " . item_normal(),
				intval($channel_id)
			);
		}

		if (!$r) {
			$ret['message'] = t('Unable to obtain post information from database.');
			return $ret;
		}

		if (!$iswebpage) {
			$max = engr_units_to_bytes(service_class_fetch($channel_id, 'total_items'));
			if (!service_class_allows($channel_id, 'total_items', $r[0]['total'])) {
				$ret['message'] .= upgrade_message() . sprintf(t('You have reached your limit of %1$.0f top level posts.'), $max);
				return $ret;
			}
		}
		else {
			$max = engr_units_to_bytes(service_class_fetch($channel_id, 'total_pages'));
			if (!service_class_allows($channel_id, 'total_pages', $r[0]['total'])) {
				$ret['message'] .= upgrade_message() . sprintf(t('You have reached your limit of %1$.0f webpages.'), $max);
				return $ret;
			}
		}

		$ret['success'] = true;
		return $ret;
	}

	function extract_bb_poll_data(&$body, $item) {

		$multiple = false;

		if (strpos($body, '[/question]') === false && strpos($body, '[/answer]') === false) {
			return false;
		}
		if (strpos($body, '[nobb]') !== false) {
			return false;
		}


		$obj         = [];
		$ptr         = [];
		$matches     = null;
		$obj['type'] = 'Question';

		if (preg_match_all('/\[answer\](.*?)\[\/answer\]/ism', $body, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$answer = escape_tags(trim($match[1]));
				$ptr[] = ['name' => $answer, 'type' => 'Note', 'replies' => ['type' => 'Collection', 'totalItems' => 0]];
				$body  = str_replace('[answer]' . $answer . '[/answer]', EMPTY_STR, $body);
			}
		}

		$matches = null;

		if (preg_match('/\[question\](.*?)\[\/question\]/ism', $body, $matches)) {
			$obj['content'] = bbcode($matches[1]);
			$body           = str_replace('[question]' . $matches[1] . '[/question]', $matches[1], $body);
			$obj['oneOf']   = $ptr;
		}

		$matches = null;

		if (preg_match('/\[question=multiple\](.*?)\[\/question\]/ism', $body, $matches)) {
			$obj['content'] = bbcode($matches[1]);
			$body           = str_replace('[question=multiple]' . $matches[1] . '[/question]', $matches[1], $body);
			$obj['anyOf']   = $ptr;
		}

		$matches = null;

		if (preg_match('/\[ends\](.*?)\[\/ends\]/ism', $body, $matches)) {
			$obj['endTime'] = datetime_convert(date_default_timezone_get(), 'UTC', $matches[1], ATOM_TIME);
			$body           = str_replace('[ends]' . $matches[1] . '[/ends]', EMPTY_STR, $body);
		}


		if ($item['item_private']) {
			$obj['to'] = Activity::map_acl($item);
		}
		else {
			$obj['to'] = [ACTIVITY_PUBLIC_INBOX];
		}

		return $obj;

	}


	function extract_poll_data($poll, $item) {

		$multiple     = intval($poll['multiple_answers']);
		$expire_value = intval($poll['expire_value']);
		$expire_unit  = $poll['expire_unit'];
		$question     = $poll['question'];
		$answers      = $poll['answers'];

		$obj            = [];
		$ptr            = [];
		$obj['type']    = 'Question';
		$obj['content'] = bbcode($question);

		foreach ($answers as $answer) {
			$answer = escape_tags(trim($answer));
			if ($answer) {
				$ptr[] = ['name' => $answer, 'type' => 'Note', 'replies' => ['type' => 'Collection', 'totalItems' => 0]];
			}
		}

		if ($multiple) {
			$obj['anyOf'] = $ptr;
		}
		else {
			$obj['oneOf'] = $ptr;
		}

		$obj['endTime'] = datetime_convert(date_default_timezone_get(), 'UTC', 'now + ' . $expire_value . ' ' . $expire_unit, ATOM_TIME);

		$obj['directMessage'] = (intval($item['item_private']) === 2);

		if ($item['item_private']) {
			$obj['to'] = Activity::map_acl($item);
		}
		else {
			$obj['to'] = [ACTIVITY_PUBLIC_INBOX];
		}

		return $obj;

	}


	function add_listeners($item) {
		// ONLY public items!
		if ($item['item_thread_top'] && !$item['item_private'] && !empty($item['term'])) {
			foreach($item['term'] as $t) {
				if (empty($t['url']) || $t['ttype'] != TERM_MENTION || $t['otype'] != TERM_OBJ_POST) {
					continue;
				}

				$listener = q("select hubloc_hash, hubloc_network from hubloc where hubloc_id_url = '%s' and hubloc_deleted = 0 order by hubloc_id desc",
					dbesc($t['url'])
				);

				if ($listener) {
					$listener = Libzot::zot_record_preferred($listener);

					$c = q("select abook_id from abook where abook_channel = %d and abook_xchan = '%s'",
						intval($profile_uid),
						dbesc($listener['hubloc_hash'])
					);

					if (!$c) {
						ThreadListener::store($item['mid'], $listener['hubloc_hash']);
					}
				}
			}
		}
	}


}
