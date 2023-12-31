<?php
namespace Zotlabs\Module;

use App;
use Zotlabs\Lib\Permcat;

require_once('include/socgraph.php');
require_once('include/selectors.php');

class Connections extends \Zotlabs\Web\Controller {

	function init() {

		if(! local_channel())
			return;

		App::$profile_uid = local_channel();

		$channel = App::get_channel();
		if($channel)
			head_set_icon($channel['xchan_photo_s']);

	}

	function get() {

		$sort_type = 0;
		$o = '';


		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return login();
		}

		nav_set_selected('Connections');

		$active      = false;
		$blocked     = false;
		$hidden      = false;
		$ignored     = false;
		$archived    = false;
		$unblocked   = false;
		$pending     = false;
		$unconnected = false;
		$all         = false;

		if(!(isset($_REQUEST['aj']) && $_REQUEST['aj']))
			$_SESSION['return_url'] = App::$query_string;

		$search_flags = "";
		$head = '';

		if(argc() == 2) {
			switch(argv(1)) {
				case 'active':
					$search_flags = " and abook_blocked = 0 and abook_ignored = 0 and abook_hidden = 0 and abook_archived = 0 AND abook_not_here = 0 ";
					$head = t('Active');
					$active = true;
					break;
				case 'blocked':
					$search_flags = " and abook_blocked = 1 ";
					$head = t('Blocked');
					$blocked = true;
					break;
				case 'ignored':
					$search_flags = " and abook_ignored = 1 ";
					$head = t('Ignored');
					$ignored = true;
					break;
				case 'hidden':
					$search_flags = " and abook_hidden = 1 ";
					$head = t('Hidden');
					$hidden = true;
					break;
				case 'archived':
					$search_flags = " and ( abook_archived = 1 OR abook_not_here = 1) ";
					$head = t('Archived/Unreachable');
					$archived = true;
					break;
				case 'pending':
					$search_flags = " and abook_pending = 1 ";
					$head = t('New');
					$pending = true;
					break;
				case 'ifpending':
					$r = q("SELECT COUNT(abook.abook_id) AS total FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash where abook_channel = %d and abook_pending = 1 and abook_self = 0 and abook_ignored = 0 and xchan_deleted = 0 and xchan_orphan = 0 ",
						intval(local_channel())
					);
					if($r && $r[0]['total']) {
						$search_flags = " and abook_pending = 1 ";
						$head = t('New');
						$pending = true;
						App::$argv[1] = 'pending';
					}
					else {
						$head = t('All');
						$search_flags = '';
						$all = true;
						App::$argc = 1;
						unset(App::$argv[1]);
					}
					break;
	//			case 'unconnected':
	//				$search_flags = " and abook_unconnected = 1 ";
	//				$head = t('Unconnected');
	//				$unconnected = true;
	//				break;

				case 'all':
					$head = t('All');
					break;
				default:
					$search_flags = " and abook_blocked = 0 and abook_ignored = 0 and abook_hidden = 0 and abook_archived = 0 and abook_not_here = 0 ";
					$active = true;
					$head = t('Active');
					break;

			}

			$sql_extra = $search_flags;
			if(argv(1) === 'pending')
				$sql_extra .= " and abook_ignored = 0 ";

		}
		else {
			$sql_extra = " and abook_blocked = 0 ";
			$unblocked = true;
		}

		$order = $_REQUEST['order'] ?? '';

		switch($order) {
			case 'name_desc':
				$sql_order = 'xchan_name DESC';
				break;
			case 'connected':
				$sql_order = 'abook_created';
				break;
			case 'connected_desc':
				$sql_order = 'abook_created DESC';
				break;
			default:
				$sql_order = 'xchan_name';
		}

		$search = ((x($_REQUEST,'search')) ? notags(trim($_REQUEST['search'])) : '');
		$search_xchan = ((x($_REQUEST,'search_xchan')) ? notags(trim($_REQUEST['search_xchan'])) : '');

		$tabs = array(
			/*
			array(
				'label' => t('Suggestions'),
				'url'   => z_root() . '/suggest',
				'sel'   => '',
				'title' => t('Suggest new connections'),
			),
			*/

			'active' => array(
				'label' => t('Active Connections'),
				'url'   => z_root() . '/connections/active',
				'sel'   => ($active) ? 'active' : '',
				'title' => t('Show active connections'),
			),

			'pending' => array(
				'label' => t('New Connections'),
				'url'   => z_root() . '/connections/pending',
				'sel'   => ($pending) ? 'active' : '',
				'title' => t('Show pending (new) connections'),
			),


			/*
			array(
				'label' => t('Unblocked'),
				'url'   => z_root() . '/connections',
				'sel'   => (($unblocked) && (! $search) && (! $nets)) ? 'active' : '',
				'title' => t('Only show unblocked connections'),
			),
			*/

			'blocked' => array(
				'label' => t('Blocked'),
				'url'   => z_root() . '/connections/blocked',
				'sel'   => ($blocked) ? 'active' : '',
				'title' => t('Only show blocked connections'),
			),

			'ignored' => array(
				'label' => t('Ignored'),
				'url'   => z_root() . '/connections/ignored',
				'sel'   => ($ignored) ? 'active' : '',
				'title' => t('Only show ignored connections'),
			),

			'archived' => array(
				'label' => t('Archived/Unreachable'),
				'url'   => z_root() . '/connections/archived',
				'sel'   => ($archived) ? 'active' : '',
				'title' => t('Only show archived/unreachable connections'),
			),

			'hidden' => array(
				'label' => t('Hidden'),
				'url'   => z_root() . '/connections/hidden',
				'sel'   => ($hidden) ? 'active' : '',
				'title' => t('Only show hidden connections'),
			),

	//		array(
	//			'label' => t('Unconnected'),
	//			'url'   => z_root() . '/connections/unconnected',
	//			'sel'   => ($unconnected) ? 'active' : '',
	//			'title' => t('Only show one-way connections'),
	//		),


			'all' => array(
				'label' => t('All Connections'),
				'url'   => z_root() . '/connections',
				'sel'   => ($all) ? 'active' : '',
				'title' => t('Show all connections'),
			),

		);

		//$tab_tpl = get_markup_template('common_tabs.tpl');
		//$t = replace_macros($tab_tpl, array('$tabs'=>$tabs));

		$searching = false;
		$search_hdr = '';

		if($search) {
			$search_hdr = $search;
			$search_txt = (($search_xchan) ? urldecode($search_xchan) : preg_quote($search));

			if ($search_xchan) {
				$sql_extra .= " AND xchan_hash = '" . protect_sprintf(dbesc($search_txt)) . "' ";
			}
			else {
				$sql_extra .= " AND xchan_name LIKE '%%" . protect_sprintf(dbesc($search_txt)) . "%%' ";
			}
		}

		if(isset($_REQUEST['gid']) && $_REQUEST['gid']) {
			$sql_extra .= " and xchan_hash in ( select xchan from pgrp_member where gid = " . intval($_REQUEST['gid']) . " and uid = " . intval(local_channel()) . " ) ";
		}

		$r = q("SELECT COUNT(abook.abook_id) AS total FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash
			where abook_channel = %d and abook_self = 0 and xchan_deleted = 0 and xchan_orphan = 0 $sql_extra ",
			intval(local_channel())
		);
		if($r) {
			App::set_pager_total($r[0]['total']);
			$total = $r[0]['total'];
		}

		$r = q("SELECT abook.*, xchan.* FROM abook left join xchan on abook.abook_xchan = xchan.xchan_hash
			WHERE abook_channel = %d and abook_self = 0 and xchan_deleted = 0 and xchan_orphan = 0 $sql_extra ORDER BY $sql_order LIMIT %d OFFSET %d ",
			intval(local_channel()),
			intval(App::$pager['itemspage']),
			intval(App::$pager['start'])
		);

		$roles = new Permcat(local_channel());
		$roles_list = $roles->listing();
		$roles_dict = [];

		foreach ($roles_list as $role) {
			$roles_dict[$role['name']] = $role['localname'];
		}

		$contacts = array();

		if($r) {

			//vcard_query($r);


			foreach($r as $rr) {
				if($rr['xchan_url']) {

					if((isset($rr['vcard'])) && is_array($rr['vcard']['tels']) && $rr['vcard']['tels'][0]['nr'])
						$phone = $rr['vcard']['tels'][0]['nr'];
					else
						$phone = '';

					$status_str = '';
					$status = array(
						((intval($rr['abook_pending'])) ? t('Pending approval') : ''),
						((intval($rr['abook_archived'])) ? t('Archived') : ''),
						((intval($rr['abook_hidden'])) ? t('Hidden') : ''),
						((intval($rr['abook_ignored'])) ? t('Ignored') : ''),
						((intval($rr['abook_blocked'])) ? t('Blocked') : ''),
						((intval($rr['abook_not_here'])) ? t('Not connected at this location') : '')
					);

					$oneway = false;
					if(! intval(get_abconfig(local_channel(),$rr['xchan_hash'],'their_perms','post_comments'))) {
						$oneway = true;
					}

					$perminfo['connpermcount']=0;
					$perminfo['connperms']=t('Accepts').': ';
					if(intval(get_abconfig(local_channel(),$rr['xchan_hash'],'their_perms','post_comments'))) {
						$perminfo['connpermcount']++;
						$perminfo['connperms'] .= t('Comments');
					}
					if(intval(get_abconfig(local_channel(),$rr['xchan_hash'],'their_perms','send_stream'))) {
						$perminfo['connpermcount']++;
						$perminfo['connperms'] = ($perminfo['connperms']) ? $perminfo['connperms'] . ', ' : $perminfo['connperms'] ;
						$perminfo['connperms'] .= t('Stream items');
					}
					if(intval(get_abconfig(local_channel(),$rr['xchan_hash'],'their_perms','post_wall'))) {
						$perminfo['connpermcount']++;
						$perminfo['connperms'] = ($perminfo['connperms']) ? $perminfo['connperms'] . ', ' : $perminfo['connperms'] ;
						$perminfo['connperms'] .= t('Wall posts');
					}

					if ($perminfo['connpermcount'] == 0) {
						$perminfo['connperms'] .= t('Nothing');
					}


					foreach($status as $str) {
						if(!$str)
							continue;
						$status_str .= $str;
						$status_str .= ', ';
					}
					$status_str = rtrim($status_str, ', ');

					$contacts[] = array(
						'img_hover' => sprintf( t('%1$s [%2$s]'),$rr['xchan_name'],$rr['xchan_url']),
						'edit_hover' => t('Edit connection'),
						'edit' => t('Edit'),
						'delete_hover' => t('Delete connection'),
						'id' => $rr['abook_id'],
						'thumb' => $rr['xchan_photo_m'],
						'name' => $rr['xchan_name'],
						'classes' => ((intval($rr['abook_archived']) || intval($rr['abook_not_here'])) ? 'archived' : ''),
						'url' => chanlink_hash($rr['xchan_hash']),
						'webbie_label' => t('Channel address'),
						'webbie' => $rr['xchan_addr'],
						'network_label' => t('Network'),
						'network' => network_to_name($rr['xchan_network']),
						'public_forum' => ((intval($rr['xchan_pubforum'])) ? true : false),
						'call' => t('Call'),
						'phone' => $phone,
						'status_label' => t('Status'),
						'status' => $status_str,
						'states' => $status,
						'connected_label' => t('Connected'),
						'connected' => datetime_convert('UTC',date_default_timezone_get(),$rr['abook_created'], 'c'),
						'approve_hover' => t('Approve connection'),
						'approve' => (($rr['abook_pending']) ? t('Approve') : false),
						'ignore_hover' => t('Ignore connection'),
						'ignore' => ((! $rr['abook_ignored']) ? t('Ignore') : false),
						'recent_label' => t('Recent activity'),
						'recentlink' => z_root() . '/network/?f=&cid=' . intval($rr['abook_id']) . '&name=' . $rr['xchan_name'],
						'oneway' => $oneway,
						'perminfo' => $perminfo,
						'connect' => (intval($rr['abook_not_here']) ? t('Connect') : ''),
						'follow' => z_root() . '/follow/?f=&url=' . urlencode($rr['xchan_hash']) . '&interactive=1',
						'connect_hover' => t('Connect at this location'),
						'role' => $roles_dict[$rr['abook_role']] ?? '',
						'pending' => intval($rr['abook_pending'])
					);
				}
			}
		}

		$limit = service_class_fetch(local_channel(),'total_channels');
		if($limit !== false) {
			$abook_usage_message = sprintf( t("You have %1$.0f of %2$.0f allowed connections."), $total, $limit);
		}
		else {
			$abook_usage_message = '';
 		}

		if(isset($_REQUEST['aj']) && $_REQUEST['aj']) {
			if($contacts) {
				$o = replace_macros(get_markup_template('contactsajax.tpl'),array(
					'$contacts' => $contacts,
					'$edit' => t('Edit'),
				));
			}
			else {
				$o = '<div id="content-complete"></div>';
			}
			echo $o;
			killme();
		}
		else {
			$o .= "<script> var page_query = '" . escape_tags(urlencode($_GET['q'])) . "'; var extra_args = '" . extra_query_args() . "' ; </script>";
			$o .= replace_macros(get_markup_template('connections.tpl'), [
				'$header' => t('Connections') . (($head) ? ': ' . $head : ''),
				'$tabs' => $tabs,
				'$total' => $total,
				'$search' => $search_hdr,
				'$label' => t('Search'),
				'$role_label' => t('Contact role'),
				'$desc' => $search ?? t('Search your connections'),
				'$finding' => (($searching) ? t('Contact search') . ": '" . $search . "'" : ""),
				'$submit' => t('Find'),
				'$edit' => t('Edit'),
				'$approve' => t('Approve'),
				'$cmd' => App::$cmd,
				'$contacts' => $contacts,
				'$abook_usage_message' => $abook_usage_message,
				'$group_label' => t('This is a group/forum channel')
			]);
		}

		if(! $contacts)
			$o .= '<div id="content-complete"></div>';

		return $o;
	}

}
