<?php
namespace Zotlabs\Module;

use App;
use Zotlabs\Web\Controller;

class Dirsearch extends Controller {

	function init() {
		App::set_pager_itemspage(30);

	}

	function get() {

		$ret = array('success' => false);

		// logger('request: ' . print_r($_REQUEST,true));

		$dirmode = intval(get_config('system','directory_mode'));

		if($dirmode == DIRECTORY_MODE_NORMAL) {
			$ret['message'] = t('This site is not a directory server');
			json_return_and_die($ret);
		}

		$access_token = $_REQUEST['t'] ?? '';

		$token = get_config('system','realm_token');
		if($token && $access_token != $token) {
			$ret['message'] = t('This directory server requires an access token');
			json_return_and_die($ret);
		}


		if(argc() > 1 && argv(1) === 'sites') {
			$ret = $this->list_public_sites();
			json_return_and_die($ret);
		}

		$sql_extra = '';
		$keywords_query = '';
		$hub_query = '';

		$tables = array('name','address','locale','region','postcode','country','gender','marital','sexual','keywords');

		if(isset($_REQUEST['query']) && $_REQUEST['query']) {
			$advanced = $this->dir_parse_query($_REQUEST['query']);
			if($advanced) {
				foreach($advanced as $adv) {
					if(in_array($adv['field'],$tables)) {
						if($adv['field'] === 'name')
							$sql_extra .= $this->dir_query_build($adv['logic'],'xchan_name',$adv['value']);
						elseif($adv['field'] === 'address')
	 						$sql_extra .= $this->dir_query_build($adv['logic'],'xchan_addr',$adv['value']);
						else
							$sql_extra .= $this->dir_query_build($adv['logic'],'xprof_' . $adv['field'],$adv['value']);
					}
				}
			}
		}

		$hash     = $_REQUEST['hash'] ?? '';

		$name     = $_REQUEST['name'] ?? '';
		$hub      = $_REQUEST['hub'] ?? '';
		$address  = $_REQUEST['address'] ?? '';
		$locale   = $_REQUEST['locale'] ?? '';
		$region   = $_REQUEST['region'] ?? '';
		$postcode = $_REQUEST['postcode'] ?? '';
		$country  = $_REQUEST['country'] ?? '';
		$gender   = $_REQUEST['gender'] ?? '';
		$marital  = $_REQUEST['marital'] ?? '';
		$sexual   = $_REQUEST['sexual'] ?? '';
		$keywords = $_REQUEST['keywords'] ?? '';
		$agege    = $_REQUEST['agege'] ?? 0;
		$agele    = $_REQUEST['agele'] ?? 0;
		$kw       = $_REQUEST['kw'] ?? 0;

		$forums   = ((array_key_exists('pubforums',$_REQUEST)) ? intval($_REQUEST['pubforums']) : 0);

		if(get_config('system','disable_directory_keywords'))
			$kw = 0;


		// by default use a safe search
		$safe = $_REQUEST['safe'] ?? 1;

		if(array_key_exists('sync',$_REQUEST)) {
			if($_REQUEST['sync'])
				$sync = datetime_convert('UTC','UTC',$_REQUEST['sync']);
			else
				$sync = datetime_convert('UTC','UTC','2010-01-01 01:01:00');
		}
		else
			$sync = false;

		if(($dirmode == DIRECTORY_MODE_STANDALONE) && (! $hub)) {
			$hub = \App::get_hostname();
		}

		if($hub) {
			$hub_query = " and xchan_hash in (select hubloc_hash from hubloc where hubloc_deleted = 0 and hubloc_host =  '" . protect_sprintf(dbesc($hub)) . "') ";
		}


		$sort_order  = ((x($_REQUEST,'order')) ? $_REQUEST['order'] : '');

		$joiner = ' OR ';
		if(isset($_REQUEST['and']) && $_REQUEST['and'])
			$joiner = ' AND ';

		if($name)
			$sql_extra .= $this->dir_query_build($joiner,'xchan_name',$name);
		if($address)
			$sql_extra .= $this->dir_query_build($joiner,'xchan_addr',$address);
		if($locale)
			$sql_extra .= $this->dir_query_build($joiner,'xprof_locale',$locale);
		if($region)
			$sql_extra .= $this->dir_query_build($joiner,'xprof_region',$region);
		if($postcode)
			$sql_extra .= $this->dir_query_build($joiner,'xprof_postcode',$postcode);
		if($country)
			$sql_extra .= $this->dir_query_build($joiner,'xprof_country',$country);
		if($gender)
			$sql_extra .= $this->dir_query_build($joiner,'xprof_gender',$gender);
		if($marital)
			$sql_extra .= $this->dir_query_build($joiner,'xprof_marital',$marital);
		if($sexual)
			$sql_extra .= $this->dir_query_build($joiner,'xprof_sexual',$sexual);
		if($keywords && $name) {
			// this is a general search
			$sql_extra .= $this->dir_query_build($joiner,'xprof_keywords',$keywords);
		}
		if($keywords && !$name) {
			// this is a search for keywords only
			$keywords_arr = explode(',', $keywords);
			stringify_array_elms($keywords_arr, true);
			$keywords_query = " AND xchan_hash IN (SELECT xtag_hash FROM xtag WHERE xtag_term IN (" . protect_sprintf(implode(',', $keywords_arr)) . ")) ";
		}

		// we only support an age range currently. You must set both agege
		// (greater than or equal) and agele (less than or equal)

		if($agele && $agege) {
			$sql_extra .= " $joiner ( xprof_age <= " . intval($agele) . " ";
			$sql_extra .= " AND  xprof_age >= " . intval($agege) . ") ";
		}


		if($hash) {
			$sql_extra = " AND xchan_hash like '" . dbesc($hash) . protect_sprintf('%') . "' ";
		}


	    $perpage      = $_REQUEST['n'] ?? 60;
	    $page         = ((isset($_REQUEST['p']) && $_REQUEST['p']) ? intval($_REQUEST['p'] - 1) : 0);
	    $startrec     = (($page+1) * $perpage) - $perpage;
		$limit        = $_REQUEST['limit'] ?? 0;
		$return_total = $_REQUEST['return_total'] ?? 0;

		// mtime is not currently working

		$mtime        = ((x($_REQUEST,'mtime'))        ? datetime_convert('UTC','UTC',$_REQUEST['mtime']) : '');

		// ok a separate tag table won't work.
		// merge them into xprof

		$ret['success'] = true;

		// If &limit=n, return at most n entries
		// If &return_total=1, we count matching entries and return that as 'total_items' for use in pagination.
		// By default we return one page (default 80 items maximum) and do not count total entries

		$logic = ((strlen($sql_extra)) ? 'false' : 'true');

		if($hash)
			$logic = 'true';

		if($dirmode == DIRECTORY_MODE_STANDALONE) {
			$sql_extra .= " and xchan_addr like '%%" . \App::get_hostname() . "' ";
		}

		$safesql = '';
		if($safe > 0)
			$safesql = " and xchan_censored = 0 and xchan_selfcensored = 0 ";

		if($safe < 1)
			$safesql = " and xchan_censored < 2 and xchan_selfcensored < 2 ";

		if($safe < 0)
			$safesql = " and xchan_censored < 3 and xchan_selfcensored < 2 ";

		if($forums)
			$safesql .= " and xchan_pubforum = " . ((intval($forums)) ? '1 ' : '0 ');

		if($limit)
			$qlimit = " LIMIT $limit ";
		else {
			$qlimit = " LIMIT " . intval($perpage) . " OFFSET " . intval($startrec);
			if($return_total) {
				$r = q("SELECT COUNT(xchan_hash) AS total FROM xchan left join xprof on xchan_hash = xprof_hash where $logic $sql_extra and xchan_network = 'zot6' and xchan_hidden = 0 and xchan_orphan = 0 and xchan_deleted = 0 $safesql ");
				if($r) {
					$ret['total_items'] = $r[0]['total'];
				}
			}
		}

		if($sort_order == 'normal') {
			$order = " order by xchan_name asc ";

			// Start the alphabetic search at 'A'
			// This will make a handful of channels whose names begin with
			// punctuation un-searchable in this mode

			$safesql .= " and ascii(substring(xchan_name FROM 1 FOR 1)) > 64 ";
		}
		elseif($sort_order == 'reverse')
			$order = " order by xchan_name desc ";
		elseif($sort_order == 'reversedate')
			$order = " order by xchan_name_date asc ";
		else
			$order = " order by xchan_name_date desc ";


		if($sync) {
			$spkt = array('transactions' => array());

			$r = q("SELECT * FROM updates WHERE ud_update = 0 AND ud_last = '%s' AND ud_date >= '%s' ORDER BY ud_date DESC",
				dbesc(NULL_DATE),
				dbesc($sync)
			);

			if($r) {
				foreach($r as $rr) {
					$spkt['transactions'][] = [
						'hash' => $rr['ud_hash'],
						'address' => $rr['ud_addr'],
						'host' => $rr['ud_host'],
						'transaction_id' => $rr['ud_host'], // deprecated 2023-04-12 - can be removed after dir servers at version >= 8.4
						'timestamp' => $rr['ud_date'],
						'flags' => $rr['ud_flags']
					];
				}
			}

			json_return_and_die($spkt);
		}
		else {

			$r = q("SELECT
				xchan.xchan_name as name,
				xchan.xchan_hash as hash,
				xchan.xchan_censored as censored,
				xchan.xchan_selfcensored as selfcensored,
				xchan.xchan_pubforum as public_forum,
				xchan.xchan_url as url,
				xchan.xchan_photo_l as photo_l,
				xchan.xchan_photo_m as photo,
				xchan.xchan_addr as address,
				xprof.xprof_desc as description,
				xprof.xprof_locale as locale,
				xprof.xprof_region as region,
				xprof.xprof_postcode as postcode,
				xprof.xprof_country as country,
				xprof.xprof_dob as birthday,
				xprof.xprof_age as age,
				xprof.xprof_gender as gender,
				xprof.xprof_marital as marital,
				xprof.xprof_sexual as sexual,
				xprof.xprof_about as about,
				xprof.xprof_homepage as homepage,
				xprof.xprof_hometown as hometown,
				xprof.xprof_keywords as keywords
				from xchan left join xprof on xchan_hash = xprof_hash left join hubloc on (hubloc_id_url = xchan_url and hubloc_hash = xchan_hash)
				where hubloc_primary = 1 and hubloc_updated > %s - INTERVAL %s and ( $logic $sql_extra ) $hub_query $keywords_query and xchan_network = 'zot6' and xchan_system = 0 and xchan_hidden = 0 and xchan_orphan = 0 and xchan_deleted = 0 and hubloc_deleted = 0
				$safesql $order $qlimit",
				db_utcnow(),
				db_quoteinterval('30 DAY')
			);

		}

		if($r) {
			$ret['results'] = $r;
			$ret['page'] = $page + 1;
			$ret['records'] = count($r);

			if($kw) {
				$k = dir_tagadelic($kw, $hub);
				if($k) {
					$ret['keywords'] = array();
					foreach($k as $kv) {
						$ret['keywords'][] = array('term' => $kv[0],'weight' => $kv[1], 'normalise' => $kv[2]);
					}
				}
			}
		}

		json_return_and_die($ret);
	}

	function dir_query_build($joiner,$field,$s) {
		$ret = '';
		if(trim($s))
			$ret .= dbesc($joiner) . " " . dbesc($field) . " like '" . protect_sprintf( '%' . dbesc($s) . '%' ) . "' ";
		return $ret;
	}

	function dir_flag_build($joiner,$field,$bit,$s) {
		return dbesc($joiner) . " ( " . dbesc($field) . " & " . intval($bit) . " ) " . ((intval($s)) ? '>' : '=' ) . " 0 ";
	}


	function dir_parse_query($s) {

		$ret = array();
		$curr = array();
		$all = explode(' ',$s);
		$quoted_string = false;

		if($all) {
			foreach($all as $q) {
				if($quoted_string === false) {
					if($q === 'and') {
						$curr['logic'] = 'and';
						continue;
					}
					if($q === 'or') {
						$curr['logic'] = 'or';
						continue;
					}
					if($q === 'not') {
						$curr['logic'] .= ' not';
						continue;
					}
					if(strpos($q,'=')) {
						if(! isset($curr['logic']))
							$curr['logic'] = 'or';
						$curr['field'] = trim(substr($q,0,strpos($q,'=')));
						$curr['value'] = trim(substr($q,strpos($q,'=')+1));
						if($curr['value'][0] == '"' && $curr['value'][strlen($curr['value'])-1] != '"') {
							$quoted_string = true;
							$curr['value'] = substr($curr['value'],1);
							continue;
						}
						elseif($curr['value'][0] == '"' && $curr['value'][strlen($curr['value'])-1] == '"') {
							$curr['value'] = substr($curr['value'],1,strlen($curr['value'])-2);
							$ret[] = $curr;
							$curr = array();
							continue;
						}
						else {
							$ret[] = $curr;
							$curr = array();
							continue;
						}
					}
				}
				else {
					if($q[strlen($q)-1] == '"') {
						$curr['value'] .= ' ' . str_replace('"','',trim($q));
						$ret[] = $curr;
						$curr = array();
						$quoted_string = false;
					}
					else
						$curr['value'] .= ' ' . trim($q);
				}
			}
		}
		logger('dir_parse_query:' . print_r($ret,true),LOGGER_DATA);
		return $ret;
	}







	function list_public_sites() {

		$rand = db_getfunc('rand');
		$realm = get_directory_realm();
		if($realm == DIRECTORY_REALM) {
			$r = q("select * from site where site_access != 0 and site_register !=0 and ( site_realm = '%s' or site_realm = '') and site_type = %d and site_dead = 0 order by $rand",
				dbesc($realm),
				intval(SITE_TYPE_ZOT)
			);
		}
		else {
			$r = q("select * from site where site_access != 0 and site_register !=0 and site_realm = '%s' and site_type = %d and site_dead = 0 order by $rand",
				dbesc($realm),
				intval(SITE_TYPE_ZOT)
			);
		}

		$ret = array('success' => false);

		if($r) {
			$ret['success'] = true;
			$ret['sites'] = array();
			$insecure = array();

			foreach($r as $rr) {

				if($rr['site_access'] == ACCESS_FREE)
					$access = 'free';
				elseif($rr['site_access'] == ACCESS_PAID)
					$access = 'paid';
				elseif($rr['site_access'] == ACCESS_TIERED)
					$access = 'tiered';
				else
					$access = 'private';

				if($rr['site_register'] == REGISTER_OPEN)
					$register = 'open';
				elseif($rr['site_register'] == REGISTER_APPROVE)
					$register = 'approve';
				else
					$register = 'closed';

				if(strpos($rr['site_url'],'https://') !== false)
					$ret['sites'][] = array('url' => $rr['site_url'], 'access' => $access, 'register' => $register, 'sellpage' => $rr['site_sellpage'], 'location' => $rr['site_location'], 'project' => $rr['site_project'], 'version' => $rr['site_version']);
				else
					$insecure[] = array('url' => $rr['site_url'], 'access' => $access, 'register' => $register, 'sellpage' => $rr['site_sellpage'], 'location' => $rr['site_location'], 'project' => $rr['site_project'], 'version' => $rr['site_version']);
			}
			if($insecure) {
				$ret['sites'] = array_merge($ret['sites'],$insecure);
			}
		}
		return $ret;
	}

}
