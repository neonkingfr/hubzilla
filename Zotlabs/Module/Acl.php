<?php

namespace Zotlabs\Module;

use Zotlabs\Lib\Libzotdir;
use Zotlabs\Lib\AccessList;

require_once 'include/acl_selectors.php';

/**
 * @brief ACL selector json backend.
 *
 * This module provides JSON lists of connections and local/remote channels
 * (xchans) to populate various tools such as the ACL (AccessControlList) popup
 * and various auto-complete functions (such as email recipients, search, and
 * mention targets.
 *
 * There are two primary output structural formats. One for the ACL widget and
 * the other for auto-completion.
 *
 * Many of the behaviour variations are triggered on the use of single character
 * keys however this functionality has grown in an ad-hoc manner and has gotten
 * quite messy over time.
 */
class Acl extends \Zotlabs\Web\Controller {

	function init() {

		// logger('mod_acl: ' . print_r($_GET,true),LOGGER_DATA);

		$start    = (x($_REQUEST,'start')  ? $_REQUEST['start']  : 0);
		$count    = (x($_REQUEST,'count')  ? $_REQUEST['count']  : 500);
		$search   = (x($_REQUEST,'search') ? $_REQUEST['search'] : '');
		$type     = (x($_REQUEST,'type')   ? $_REQUEST['type']   : '');
		$noforums = (x($_REQUEST,'n')      ? $_REQUEST['n']      : false);


		// $type =
		//  ''   =>  standard ACL request
		//  'g'  =>  Groups only ACL request
		//  'f'  =>  forums only ACL request
		//  'c'  =>  Connections only ACL request or editor (textarea) mention request
		// $_REQUEST['search'] contains ACL search text.


		// $type =
		//  'm'  =>  autocomplete private mail recipient (checks post_mail permission and displays only zot, diaspora, friendica-over-diaspora xchan_network xchan's)
		//  'a'  =>  autocomplete connections (mod_connections, mod_poke, mod_sources, mod_photos)
		//  'x'  =>  nav search bar autocomplete (match any xchan)
		// $_REQUEST['query'] contains autocomplete search text.

		// List of channels whose connections to also suggest,
		// e.g. currently viewed channel or channels mentioned in a post

		$extra_channels = (x($_REQUEST,'extra_channels') ? $_REQUEST['extra_channels'] : array());

		// The different autocomplete libraries use different names for the search text
		// parameter. Internally we'll use $search to represent the search text no matter
		// what request variable it was attached to.

		if(array_key_exists('query',$_REQUEST)) {
			$search = $_REQUEST['query'];
		}

		if( (! local_channel()) && (! in_array($type, [ 'x', 'c', 'f' ])))
			killme();

		$permitted = [];
		$sql_extra = '';
		$sql_extra2 = '';
		$sql_extra3 = '';
		$sql_extra2_xchan = '';
		$order_extra2 = '';

		if(in_array($type, [ 'm', 'a', 'c', 'f' ])) {

			// These queries require permission checking. We'll create a simple array of xchan_hash for those with
			// the requisite permissions which we can check against.

			$x = q("select xchan from abconfig where chan = %d and cat = 'their_perms' and k = '%s' and v = '1'",
				intval(local_channel()),
				dbesc(($type === 'm') ? 'post_mail' : 'tag_deliver')
			);

			$permitted = ids_to_array($x,'xchan');

		}

		if($search) {
			$sql_extra = " AND pgrp.gname LIKE " . protect_sprintf( "'%" . dbesc($search) . "%'" ) . " ";
			$sql_extra2 = "AND ( xchan_name LIKE " . protect_sprintf( "'%" . dbesc($search) . "%'" ) . " OR xchan_addr LIKE " . protect_sprintf( "'%" . dbesc(punify($search)) . ((strpos($search,'@') === false) ? "%@%'"  : "%'")) . ") ";
			$sql_extra2_xchan = "AND ( xchan_name LIKE " . protect_sprintf( "'" . dbesc($search) . "%'" ) . " OR xchan_addr LIKE " . protect_sprintf( "'" . dbesc(punify($search)) . ((strpos($search,'@') === false) ? "%@%'"  : "%'")) . ") ";

			// This horrible mess is needed because position also returns 0 if nothing is found.
			// Would be MUCH easier if it instead returned a very large value
			// Otherwise we could just
			// order by LEAST(POSITION($search IN xchan_name),POSITION($search IN xchan_addr)).

			$order_extra2 = "CASE WHEN xchan_name LIKE "
					. protect_sprintf( "'%" . dbesc($search) . "%'" )
					. " then POSITION('" . protect_sprintf(dbesc($search))
					. "' IN xchan_name) else position('" . protect_sprintf(dbesc(punify($search))) . "' IN xchan_addr) end, ";

			$sql_extra3 = "AND ( xchan_addr like " . protect_sprintf( "'%" . dbesc(punify($search)) . "%'" ) . " OR xchan_name like " . protect_sprintf( "'%" . dbesc($search) . "%'" ) . " ) ";

		}

		$groups = array();
		$contacts = array();

		if($type == '' || $type == 'g') {

			// virtual groups based on private profile viewing ability

			$r = q("select id, profile_guid, profile_name from profile where is_default = 0 and uid = %d",
				intval(local_channel())
			);
			if($r) {
				foreach($r as $rv) {
					$groups[] = array(
						"type"  => "g",
						"photo" => "images/twopeople.png",
						"name"  => t('Profile','acl') . ' ' . $rv['profile_name'],
						"id"	=> 'vp' . $rv['id'],
						"xid"   => 'vp.' . $rv['profile_guid'],
						"uids"  => AccessList::profile_members_xchan(local_channel(), $rv['id']),
						"link"  => ''
					);
				}
			}

			// Normal privacy groups

			$r = q("SELECT pgrp.id, pgrp.hash, pgrp.gname
					FROM pgrp, pgrp_member
					WHERE pgrp.deleted = 0 AND pgrp.uid = %d
					AND pgrp_member.gid = pgrp.id
					$sql_extra
					GROUP BY pgrp.id
					ORDER BY pgrp.gname
					LIMIT %d OFFSET %d",
				intval(local_channel()),
				intval($count),
				intval($start)
			);

			if($r) {
				foreach($r as $g){
		//		logger('acl: group: ' . $g['gname'] . ' members: ' . AccessList::members_xchan(local_channel(), $g['id']));
					$groups[] = array(
						"type"  => "g",
						"photo" => "images/twopeople.png",
						"name"  => $g['gname'],
						"id"	=> $g['id'],
						"xid"   => $g['hash'],
						"uids"  => AccessList::members_xchan(local_channel(), $g['id']),
						"link"  => ''
					);
				}
			}
		}

		if($type == '' || $type == 'c' || $type === 'f') {

			$extra_channels_sql  = '';

			// Only include channels who allow the observer to view their connections
			if($extra_channels) {
				foreach($extra_channels as $channel) {
					if(perm_is_allowed(intval($channel), get_observer_hash(),'view_contacts')) {
						if($extra_channels_sql)
							$extra_channels_sql .= ',';
						$extra_channels_sql .= intval($channel);
					}
				}
			}

			// Getting info from the abook is better for local users because it contains info about permissions
			if(local_channel()) {
				if($extra_channels_sql != '')
					$extra_channels_sql = " OR (abook_channel IN ($extra_channels_sql)) and abook_hidden = 0 ";


				// Add atokens belonging to the local channel

				if($search) {
					$sql_extra_atoken = "AND ( atoken_name LIKE " . protect_sprintf( "'%" . dbesc($search) . "%'" ) . ") ";
				}
				else {
					$sql_extra_atoken = '';
				}

				$r2 = null;

				$r1 = q("select * from atoken where atoken_uid = %d $sql_extra_atoken",
					intval(local_channel())
				);

				if($r1) {
					require_once('include/security.php');
					$r2 = array();
					foreach($r1 as $rr) {
						$x = atoken_xchan($rr);
						$r2[] = [
							'id' => 'a' . $rr['atoken_id'] ,
							'hash' => $x['xchan_hash'],
							'name' => $x['xchan_name'],
							'micro' => $x['xchan_photo_m'],
							'url' => z_root(),
							'nick' => $x['xchan_addr'],
							'abook_their_perms' => 0,
							'abook_flags' => 0,
							'abook_self' => 0
						];
					}
				}

				// add connections

				$r = q("SELECT abook_id as id, xchan_hash as hash, xchan_name as name, xchan_network as net, xchan_photo_s as micro, xchan_url as url, xchan_addr as nick, abook_their_perms, xchan_pubforum, abook_flags, abook_self
					FROM abook left join xchan on abook_xchan = xchan_hash
					WHERE (abook_channel = %d $extra_channels_sql) AND abook_blocked = 0 and abook_pending = 0 and xchan_deleted = 0 $sql_extra2 order by $order_extra2 xchan_name asc" ,
					intval(local_channel())
				);

				if($r2)
					$r = array_merge($r2,$r);

			}
			else { // Visitors
				$r = q("SELECT xchan_hash as id, xchan_hash as hash, xchan_name as name, xchan_network as net, xchan_photo_s as micro, xchan_url as url, xchan_addr as nick, 0 as abook_their_perms, 0 as abook_flags, 0 as abook_self
					FROM xchan left join xlink on xlink_link = xchan_hash
					WHERE xlink_xchan  = '%s' AND xchan_deleted = 0 $sql_extra2_xchan order by $order_extra2 xchan_name asc" ,
					dbesc(get_observer_hash())
				);

				// Find contacts of extra channels
				// This is probably more complicated than it needs to be
				if($extra_channels_sql) {
					// Build a list of hashes that we got previously so we don't get them again
					$known_hashes = array("'".get_observer_hash()."'");
					if($r)
						foreach($r as $rr)
							$known_hashes[] = "'".$rr['hash']."'";
					$known_hashes_sql = 'AND xchan_hash not in ('.join(',',$known_hashes).')';

					$r2 = q("SELECT abook_id as id, xchan_hash as hash, xchan_name as name, xchan_network as net, xchan_photo_s as micro, xchan_url as url, xchan_addr as nick, abook_their_perms, abook_flags, abook_self
						FROM abook left join xchan on abook_xchan = xchan_hash
						WHERE abook_channel IN ($extra_channels_sql) $known_hashes_sql AND abook_blocked = 0 and abook_pending = 0 and abook_hidden = 0 and xchan_deleted = 0 $sql_extra2 order by $order_extra2 xchan_name asc");
					if($r2)
						$r = array_merge($r,$r2);

					// Sort accoring to match position, then alphabetically. This could be avoided if the above two SQL queries could be combined into one, and the sorting could be done on the SQl server (like in the case of a local user)
					$matchpos = function($x) use($search) {
						$namepos = strpos($x['name'],$search);
						$nickpos = strpos($x['nick'],$search);
						// Use a large position if not found
						return min($namepos === false ? 9999 : $namepos, $nickpos === false ? 9999 : $nickpos);
					};
					// This could be made simpler if PHP supported stable sorting
					usort($r,function($a,$b) use($matchpos) {
						$pos1 = $matchpos($a);
						$pos2 = $matchpos($b);
						if($pos1 == $pos2) { // Order alphabetically if match position is the same
							if($a['name'] == $b['name'])
								return 0;
							else
								return ($a['name'] < $b['name']) ? -1 : 1;
						}
						return ($pos1 < $pos2) ? -1 : 1;
					});
				}
			}
			if((count($r) < 100) && $type == 'c') {
				$r2 = q("SELECT substr(xchan_hash,1,18) as id, xchan_hash as hash, xchan_name as name, xchan_network as net, xchan_photo_s as micro, xchan_url as url, xchan_addr as nick, 0 as abook_their_perms, 0 as abook_flags, 0 as abook_self
					FROM xchan
					WHERE xchan_deleted = 0 and not xchan_network  in ('rss','anon','unknown') $sql_extra2_xchan order by $order_extra2 xchan_name asc"
				);
				if($r2) {
					$r = array_merge($r,$r2);
					$r = unique_multidim_array($r,'hash');
				}
			}
		}
		elseif($type == 'm') {
			$r = array();
			$z = q("SELECT abook_id as id, xchan_hash as hash, xchan_name as name, xchan_network as net, xchan_addr as nick, xchan_photo_s as micro, xchan_url as url, abook_self
				FROM abook left join xchan on abook_xchan = xchan_hash
				WHERE abook_channel = %d
				and xchan_deleted = 0
				and not xchan_network IN ('rss', 'anon', 'unknown')
				$sql_extra3
				ORDER BY xchan_name ASC ",
				intval(local_channel())
			);
			if($z) {
				foreach($z as $zz) {
					if(in_array($zz['hash'],$permitted)) {
						$r[] = $zz;
					}
				}
			}

		}
		elseif($type == 'a') {

			$r = q("SELECT abook_id as id, xchan_name as name, xchan_network as net, xchan_hash as hash, xchan_addr as nick, xchan_photo_s as micro, xchan_url as url, xchan_addr as attag, abook_their_perms, abook_self FROM abook left join xchan on abook_xchan = xchan_hash
				WHERE abook_channel = %d
				and xchan_deleted = 0
				$sql_extra3
				ORDER BY xchan_name ASC ",
				intval(local_channel())
			);

		}
		elseif($type == 'x') {
			$r = $this->navbar_complete($a);
			$contacts = array();
			if($r) {
				foreach($r as $g) {
					$contacts[] = array(
						"photo"    => $g['photo'],
						"name"     => $g['name'],
						"nick"     => $g['address']
					);
				}
			}

			$o = array(
				'start' => $start,
				'count'	=> $count,
				'items'	=> $contacts,
			);
			echo json_encode($o);
			killme();
		}
		else
			$r = array();

		if($r) {
			$i = count($contacts);
			$x = [];
			foreach($r as $g) {

				if(isset($g['net']) && in_array($g['net'], ['rss','anon','unknown']) && ($type != 'a'))
					continue;

				$g['hash'] = urlencode($g['hash']);

				if(! $g['nick']) {
					$g['nick'] = $g['url'];
				}

				$clink = ($g['nick']) ? $g['nick'] : $g['url'];
				$lkey = md5($clink);
				if (! array_key_exists($lkey, $x))
					$x[$lkey] = $i;

				if(in_array($g['hash'],$permitted) && $type === 'f' && (! $noforums)) {
					$contacts[$i] = array(
						"type"     => "c",
						"photo"    => "images/twopeople.png",
						"name"     => $g['name'],
						"id"	   => urlencode($g['id']),
						"xid"      => $g['hash'],
						"link"     => $clink,
						"nick"     => substr($g['nick'],0,strpos($g['nick'],'@')),
						"self"     => (intval($g['abook_self']) ? 'abook-self' : ''),
						"taggable" => 'taggable',
						"label"    => t('network')
					);
				}
				if($type !== 'f') {
					if (! array_key_exists($x[$lkey], $contacts) || ($contacts[$x[$lkey]]['net'] !== 'zot6' && $g['net'] == 'zot6')) {
						$contacts[$x[$lkey]] = array(
							"type"     => "c",
							"photo"    => $g['micro'],
							"name"     => $g['name'],
							"id"	   => urlencode($g['id']),
							"xid"      => $g['hash'],
							"url"      => $g['url'],
							"link"     => $clink,
							"nick"     => ((strpos($g['nick'],'@')) ? substr($g['nick'],0,strpos($g['nick'],'@')) : $g['nick']),
							"self"     => (intval($g['abook_self']) ? 'abook-self' : ''),
							"taggable" => '',
							"label"    => '',
							"net"      => $g['net'] ?? ''
						);
					}
				}
				$i++;
			}
		}

		$items = array_merge($groups, $contacts);

		$o = array(
			'start' => $start,
			'count'	=> $count,
			'items'	=> $items,
		);

		echo json_encode($o);

		killme();
	}


	function navbar_complete(&$a) {

	//	logger('navbar_complete');

		if(observer_prohibited()) {
			return;
		}

		$dirmode = intval(get_config('system','directory_mode'));
		$search = ((x($_REQUEST,'search')) ? htmlentities($_REQUEST['search'],ENT_COMPAT,'UTF-8',false) : '');
		if(! $search || mb_strlen($search) < 2)
			return array();

		$star = false;
		$address = false;

		if(substr($search,0,1) === '@')
			$search = substr($search,1);

		if(substr($search,0,1) === '*') {
			$star = true;
			$search = substr($search,1);
		}

		if(strpos($search,'@') !== false) {
			$address = true;
		}

		if(($dirmode == DIRECTORY_MODE_PRIMARY) || ($dirmode == DIRECTORY_MODE_STANDALONE)) {
			$url = z_root() . '/dirsearch';
		}

		if(! $url) {
			$directory = Libzotdir::find_upstream_directory($dirmode);
			$url = $directory['url'] . '/dirsearch';
		}

		$token = get_config('system','realm_token');

		$count = (x($_REQUEST,'count') ?  $_REQUEST['count'] : 100);
		if($url) {
			$query = $url . '?f=' . (($token) ? '&t=' . urlencode($token) : '');
			$query .= '&name=' . urlencode($search) . "&limit=$count" . (($address) ? '&address=' . urlencode(punify($search)) : '');

			$x = z_fetch_url($query);
			if($x['success']) {
				$t = 0;
				$j = json_decode($x['body'],true);
				if($j && $j['results']) {
					return $j['results'];
				}
			}
		}
		return array();
	}

}
