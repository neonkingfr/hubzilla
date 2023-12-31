<?php

namespace Zotlabs\Module;

use App;
use Zotlabs\Web\Controller;
use Zotlabs\Lib\Libzotdir;


require_once('include/socgraph.php');
require_once('include/bbcode.php');
require_once('include/html2plain.php');


class Directory extends Controller {

	function init() {
		App::set_pager_itemspage(30);

		if(local_channel() && x($_GET,'ignore')) {
			q("insert into xign ( uid, xchan ) values ( %d, '%s' ) ",
				intval(local_channel()),
				dbesc($_GET['ignore'])
			);
			goaway(z_root() . '/directory?f=&suggest=1');
		}

		if(local_channel())
			App::$profile_uid = local_channel();

		$observer = get_observer_hash();
		$global_changed = false;
		$safe_changed = false;
		$pubforums_changed = false;

		if(array_key_exists('global',$_REQUEST)) {
			$globaldir = intval($_REQUEST['global']);
			$global_changed = true;
		}
		if($global_changed) {
			$_SESSION['globaldir'] = $globaldir;
			if($observer)
				set_xconfig($observer,'directory','globaldir',$globaldir);
		}

		if(array_key_exists('safe',$_REQUEST)) {
			$safemode = intval($_REQUEST['safe']);
			$safe_changed = true;
		}
		if($safe_changed) {
			$_SESSION['safemode'] = $safemode;
			if($observer)
				set_xconfig($observer,'directory','safemode',$safemode);
		}


		if(array_key_exists('pubforums',$_REQUEST)) {
			$pubforums = intval($_REQUEST['pubforums']);
			$pubforums_changed = true;
		}
		if($pubforums_changed) {
			$_SESSION['pubforums'] = $pubforums;
			if($observer)
				set_xconfig($observer,'directory','pubforums',$pubforums);
		}

	}

	function get() {

		if(observer_prohibited()) {
			notice( t('Public access denied.') . EOL);
			return;
		}

		if(get_config('system','block_public_directory',false) && (! get_observer_hash())) {
			notice( t('Public access denied.') . EOL);
			return;
		}

		$observer = get_observer_hash();

		$globaldir = Libzotdir::get_directory_setting($observer, 'globaldir');

		// override your personal global search pref if we're doing a navbar search of the directory
		if(isset($_REQUEST['navsearch']) && intval($_REQUEST['navsearch']))
			$globaldir = 1;

		$safe_mode = Libzotdir::get_directory_setting($observer, 'safemode');

		$pubforums = Libzotdir::get_directory_setting($observer, 'pubforums');

		$o = '';
		nav_set_selected('Directory');

		if(x($_POST,'search'))
			$search = notags(trim($_POST['search']));
		else
			$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');

		$advanced = '';
		if(strpos($search,'=') && local_channel() && feature_enabled(local_channel(), 'advanced_dirsearch'))
			$advanced = $search;

		$keywords = $_GET['keywords'] ?? '';

		// Suggest channels if no search terms or keywords are given
		$suggest = (local_channel() && x($_REQUEST,'suggest')) ? $_REQUEST['suggest'] : '';

		$addresses = [];
		$common = [];

		if($suggest) {

			// the directory options have no effect in suggestion mode

			$globaldir = 1;
			$safe_mode = 1;
			$type = 0;

			$r = suggestion_query(local_channel(),get_observer_hash(),0,60);

			if(! $r) {
				notice( t('No default suggestions were found.') . EOL);
				return;
			}

			// Remember in which order the suggestions were

			$index = 0;
			foreach($r as $rr) {
				$common[$rr['xchan_addr']] = ((intval($rr['total']) > 0) ? intval($rr['total']) - 1 : 0);
				$addresses[$rr['xchan_addr']] = $index++;
			}

			// Build query to get info about suggested people

			foreach(array_keys($addresses) as $address) {
				$advanced .= "address=\"$address\" ";
			}
			// Remove last space in the advanced query
			$advanced = rtrim($advanced);

		}

		$tpl = get_markup_template('directory_header.tpl');

		$dirmode = intval(get_config('system','directory_mode'));

		$directory_admin = false;

		$url = '';

		if(in_array($dirmode, [DIRECTORY_MODE_PRIMARY, DIRECTORY_MODE_SECONDARY, DIRECTORY_MODE_STANDALONE])) {
			$url = z_root() . '/dirsearch';
			if (is_site_admin()) {
				$directory_admin = true;
			}
 		}

		if(! $url) {
			$directory = Libzotdir::find_upstream_directory($dirmode);
			if((! $directory) || (! array_key_exists('url',$directory)) || (! $directory['url']))
				logger('CRITICAL: No directory server URL');
			$url = $directory['url'] . '/dirsearch';
		}

		$token = get_config('system','realm_token');


		logger('mod_directory: URL = ' . $url, LOGGER_DEBUG);

		$contacts = array();

		if(local_channel()) {
			$x = q("select abook_xchan from abook where abook_channel = %d",
				intval(local_channel())
			);
			if($x) {
				foreach($x as $xx)
					$contacts[] = $xx['abook_xchan'];
			}
		}

		if($url) {

			$numtags = get_config('system','directorytags');

			$kw = ((intval($numtags) > 0) ? intval($numtags) : 50);

			if(get_config('system','disable_directory_keywords'))
				$kw = 0;

			if (intval($safe_mode) === 0 && $directory_admin)
				$safe_mode = -1;

			$query = $url . '?f=&kw=' . $kw . (($safe_mode < 1) ? '&safe=' . $safe_mode : '');

			if($token)
				$query .= '&t=' . $token;

			if(! $globaldir)
				$query .= '&hub=' . App::get_hostname();

			if($search)
				$query .= '&name=' . urlencode($search) . '&keywords=' . urlencode($search);
			if(strpos($search,'@'))
				$query .= '&address=' . urlencode($search);
			if($keywords)
				$query .= '&keywords=' . urlencode($keywords);
			if($advanced)
				$query .= '&query=' . urlencode($advanced);
			if(! is_null($pubforums))
				$query .= '&pubforums=' . intval($pubforums);

			$directory_sort_order = get_config('system','directory_sort_order');
			if(! $directory_sort_order)
				$directory_sort_order = 'date';

			$sort_order  = ((x($_REQUEST,'order')) ? $_REQUEST['order'] : $directory_sort_order);

			if($sort_order)
				$query .= '&order=' . urlencode($sort_order);

			if(App::$pager['page'] != 1)
				$query .= '&p=' . App::$pager['page'];

			logger('mod_directory: query: ' . $query);

			$x = z_fetch_url($query);
			logger('directory: return from upstream: ' . print_r($x,true), LOGGER_DATA);

			if($x['success']) {
				$t = 0;
				$j = json_decode($x['body'],true);
				if($j) {

					if(isset($j['results']) && $j['results']) {

						$results = $j['results'];
						if($suggest) {
							$results = self::reorder_results($results,$addresses);
						}

						$entries = array();

						$photo = 'thumb';

						foreach($results as $rr) {

							$profile_link = chanlink_url($rr['url']);

							$pdesc = (($rr['description']) ? $rr['description'] . '<br />' : '');
							$connect_link = ((local_channel()) ? z_root() . '/follow?f=&interactive=1&url=' . urlencode($rr['address']) : '');

							// Checking status is disabled ATM until someone checks the performance impact more carefully
							//$online = remote_online_status($rr['address']);
							$online = '';

							if(in_array($rr['hash'],$contacts))
								$connect_link = '';

							$location = '';
							if(isset($rr['locale']))
								$location .= $rr['locale'];
							if(isset($rr['region'])) {
								if($location)
									$location .= ', ';
								$location .= $rr['region'];
							}
							if(isset($rr['country'])) {
								if($location)
									$location .= ', ';
								$location .= $rr['country'];
							}

							$age = '';
							if(isset($rr['birthday'])) {
								if(($years = age($rr['birthday'],'UTC','')) > 0)
									$age = $years;
							}

							$page_type = '';

							$profile = $rr;

					//		if ((x($profile,'locale') == 1)
					//			|| (x($profile,'region') == 1)
					//			|| (x($profile,'postcode') == 1)
					//			|| (x($profile,'country') == 1))

							$gender = ((x($profile,'gender') == 1) ? t('Gender: ') . $profile['gender']: False);

							$marital = ((x($profile,'marital') == 1) ?  t('Status: ') . $profile['marital']: False);

							$homepage = ((x($profile,'homepage') == 1) ?  t('Homepage: ') : False);
							$homepageurl = ((x($profile,'homepage') == 1) ?  html2plain($profile['homepage']) : '');

							$hometown = ((x($profile,'hometown') == 1) ? html2plain($profile['hometown'])  : False);

							$about = ((x($profile,'about') == 1) ? zidify_links(bbcode($profile['about'], ['tryoembed' => false])) : False);
							if ($about && $safe_mode > 0) {
								$about = strip_tags($about, '<br>');
							}


							$keywords = ((x($profile,'keywords')) ? $profile['keywords'] : '');


							$out = '';

							if($keywords) {
								$keywords = str_replace(',',' ', $keywords);
								$keywords = str_replace('  ',' ', $keywords);
								$karr = explode(' ', $keywords);
								$marr = [];
								if($karr) {
									if(local_channel()) {
										$r = q("select keywords from profile where uid = %d and is_default = 1 limit 1",
											intval(local_channel())
										);
										if($r) {
											$keywords = str_replace(',',' ', $r[0]['keywords']);
											$keywords = str_replace('  ',' ', $keywords);
											$marr = explode(' ', $keywords);
										}
									}
									foreach($karr as $k) {
										if(strlen($out))
											$out .= ', ';
										if($marr && in_arrayi($k,$marr))
											$out .= '<a href="' . z_root() . '/directory/f=&keywords=' . urlencode($k)  .'"><strong>' . $k . '</strong></a>';
										else
											$out .= '<a href="' . z_root() . '/directory/f=&keywords=' . urlencode($k)  .'">' . $k . '</a>';
									}
								}

							}

							$entry = array(
								'id' => ++$t,
								'profile_link' => $profile_link,
								'public_forum' => $rr['public_forum'],
								'photo' => $rr['photo'],
								'hash' => $rr['hash'],
								'alttext' => $rr['name'] . ((local_channel() || remote_channel()) ? ' ' . $rr['address'] : ''),
								'name' => $rr['name'],
								'age' => $age,
								'age_label' => t('Age:'),
								'profile' => $profile,
								'address' =>  $rr['address'],
								'nickname' => substr($rr['address'],0,strpos($rr['address'],'@')),
								'location' => $location,
								'location_label' => t('Location:'),
								'gender'   => $gender,
								'pdesc'	=> $pdesc,
								'pdesc_label' => t('Description:'),
								'censor' => (($directory_admin) ? 'dircensor/' . $rr['hash'] . '?severity=' . ((intval($rr['censored']) > 0) ? 0 : 1) : ''),
								'censor_label' => t('Unsafe'),
								'censor_class' => ((intval($rr['censored']) === 1) ? 'active' : ''),
								'censor_2' => (($directory_admin) ? 'dircensor/' . $rr['hash'] . '?severity=' . ((intval($rr['censored']) > 1) ? 0 : 2) : ''),
								'censor_2_label' => t('Spam'),
								'censor_2_class' => ((intval($rr['censored']) > 1) ? 'active' : ''),
								'marital'  => $marital,
								'homepage' => $homepage,
								'homepageurl' => (($safe_mode  > 0) ? $homepageurl : linkify($homepageurl)),
								'hometown' => $hometown,
								'hometown_label' => t('Hometown:'),
								'about' => $about,
								'about_label' => t('About:'),
								'conn_label' => t('Connect'),
								'forum_label' => t('Public Forum:'),
								'connect' => $connect_link,
								'online' => $online,
								'kw' => (($out) ? t('Keywords: ') : ''),
								'keywords' => $out,
								'ignlink' => $suggest ? z_root() . '/directory?ignore=' . $rr['hash'] : '',
								'ignore_label' => t('Don\'t suggest'),
								'common_friends' => $common[$rr['address']] ?? '',
								'common_label' => t('Common connections (estimated):'),
								'common_count' => $common[$rr['address']] ?? '',
								'safe' => $safe_mode
							);

							$arr = array('contact' => $rr, 'entry' => $entry);

							call_hooks('directory_item', $arr);

							unset($profile);
							unset($location);

							if(! $arr['entry']) {
								continue;
							}

							if($sort_order == '' && $suggest) {
								$entries[$addresses[$rr['address']]] = $arr['entry']; // Use the same indexes as originally to get the best suggestion first
							}

							else {
								$entries[] = $arr['entry'];
							}
						}

						ksort($entries); // Sort array by key so that foreach-constructs work as expected

						if(isset($j['keywords']) && $j['keywords']) {
							App::$data['directory_keywords'] = $j['keywords'];
						}

						logger('mod_directory: entries: ' . print_r($entries,true), LOGGER_DATA);

						$aj = $_REQUEST['aj'] ?? '';

						if($aj) {
							if($entries) {
								$o = replace_macros(get_markup_template('directajax.tpl'),array(
									'$entries' => $entries
								));
							}
							else {
								$o = '<div id="content-complete"></div>';
							}
							echo $o;
							killme();
						}
						else {
							$maxheight = 94;

							$dirtitle = (($globaldir) ? t('Global Directory') : t('Local Directory'));

							$o .= "<script> var page_query = '" . escape_tags(urlencode($_GET['q'])) . "'; var extra_args = '" . extra_query_args() . "' ; divmore_height = " . intval($maxheight) . ";  </script>";
							$o .= replace_macros($tpl, array(
								'$search' => $search,
								'$desc' => t('Find'),
								'$finddsc' => t('Finding:'),
								'$safetxt' => htmlspecialchars($search,ENT_QUOTES,'UTF-8'),
								'$entries' => $entries,
								'$dirlbl' => $suggest ? t('Channel Suggestions') : $dirtitle,
								'$submit' => t('Find'),
								'$next' => alt_pager($j['records'], t('next page'), t('previous page')),
								'$sort' => t('Sort options'),
								'$normal' => t('Alphabetic'),
								'$reverse' => t('Reverse Alphabetic'),
								'$date' => t('Newest to Oldest'),
								'$reversedate' => t('Oldest to Newest'),
								'$suggest' => $suggest ? '&suggest=1' : '',
								'$directory_admin' => $directory_admin
							));


						}

					}
					else {
						if(isset($_REQUEST['aj']) && $_REQUEST['aj']) {
							$o = '<div id="content-complete"></div>';
							echo $o;
							killme();
						}
						if(App::$pager['page'] == 1 && (isset($j['records']) && $j['records'] == 0) && strpos($search,'@')) {
							goaway(z_root() . '/chanview/?f=&address=' . $search);
						}
						info( t("No entries (some entries may be hidden).") . EOL);
					}
				}
			}
		}
		return $o;
	}

	static public function reorder_results($results,$suggests) {

		if(! $suggests)
			return $results;

		$out = [];
		foreach($suggests as $k => $v) {
			foreach($results as $rv) {
				if($k == $rv['address']) {
					$out[intval($v)] = $rv;
					break;
				}
			}
		}

		return $out;
	}

}
