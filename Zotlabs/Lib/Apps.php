<?php

namespace Zotlabs\Lib;

use App;

require_once('include/plugin.php');
require_once('include/channel.php');

/**
 * @brief Apps class.
 *
 */
class Apps {

	static public $available_apps = null;
	static public $installed_apps = null;
	static public $base_apps = null;

	/**
	 * @brief
	 *
	 * @param boolean $translate (optional) default true
	 * @param boolean $sync (optional) default false used if called from sync_sysapps()
	 * @return array
	 */
	static public function get_system_apps($translate = true, $sync = false) {
		$ret = [];

		if(is_dir('apps'))
			$files = glob('apps/*.apd');
		else
			$files = glob('app/*.apd');

		if($files) {
			foreach($files as $f) {
				$x = self::parse_app_description($f, $translate, $sync);
				if($x) {
					$ret[] = $x;
				}
			}
		}
		$files = glob('addon/*/*.apd');
		if($files) {
			foreach($files as $f) {
				$path = explode('/',$f);
				$plugin = trim($path[1]);
				if(plugin_is_installed($plugin)) {
					$x = self::parse_app_description($f, $translate, $sync);
					if($x) {
						$x['plugin'] = $plugin;
						$ret[] = $x;
					}
				}
			}
		}

		/**
		 * @hooks get_system_apps
		 *   Hook to manipulate the system apps array.
		 */
		call_hooks('get_system_apps', $ret);

		return $ret;
	}

	static public function get_base_apps() {
		$x = get_config('system','base_apps',[
			'Connections',
			'Contact Roles',
			'Network',
			'Files',
			'Channel',
			'Photos',
			'Calendar',
			'Directory',
			'Search',
			'Help',
			'HQ',
			'Post'
		]);

		/**
		 * @hooks get_base_apps
		 *   Hook to manipulate the base apps array.
		 */
		call_hooks('get_base_apps', $x);

		return $x;
	}

	static public function import_system_apps() {
		if(! local_channel())
			return;

		self::$base_apps = self::get_base_apps();

		$apps = self::get_system_apps(false);

		self::$available_apps = q("select * from app where app_channel = 0");

		self::$installed_apps = q("select * from app where app_channel = %d",
			intval(local_channel())
		);

		if($apps) {
			foreach($apps as $app) {
				$id = self::check_install_system_app($app);

				// $id will be boolean true or false to install an app, or an integer id to update an existing app
				if($id !== false) {
					$app['uid'] = 0;
					$app['guid'] = hash('whirlpool',$app['name']);
					$app['system'] = 1;
					self::app_install(0,$app);
				}

				$id = self::check_install_personal_app($app);
				// $id will be boolean true or false to install an app, or an integer id to update an existing app
				if($id === false)
					continue;

				if($id !== true) {
					// if we already installed this app, but it changed, preserve any categories we created
					$s = EMPTY_STR;
					$r = q("select term from term where otype = %d and oid = %d",
						intval(TERM_OBJ_APP),
						intval($id)
					);
					if($r) {
						foreach($r as $t) {
							if($s)
								$s .= ',';
							$s .= $t['term'];
						}
						$app['categories'] = $s;
					}
				}
				$app['uid'] = local_channel();
				$app['guid'] = hash('whirlpool',$app['name']);
				$app['system'] = 1;
				self::app_install(local_channel(),$app);
			}
		}
	}

	/**
	 * Install the system app if no system apps have been installed, or if a new system app
	 * is discovered, or if the version of a system app changes.
	 *
	 * @param array $app
	 * @return boolean|int
	 */
	static public function check_install_system_app($app) {
		if((! is_array(self::$available_apps)) || (! count(self::$available_apps))) {
			return true;
		}
		$notfound = true;
		foreach(self::$available_apps as $iapp) {
			if($iapp['app_id'] == hash('whirlpool',$app['name'])) {
				$notfound = false;
				if((isset($app['version']) && $iapp['app_version'] !== $app['version'])
					|| ($app['plugin'] && (! $iapp['app_plugin']))) {
					return intval($iapp['app_id']);
				}

				if(($iapp['app_url'] !== $app['url'])
					|| ($iapp['app_photo'] !== $app['photo'])) {
					return intval($iapp['app_id']);
				}
			}
		}

		return $notfound;
	}

	/**
	 * Install the personal app if no personal apps have been installed, or if a new personal app
	 * is discovered, or if the version of a personal app changes.
	 *
	 * @param array $app
	 * @return boolean|int
	 */
	static public function check_install_personal_app($app) {
		$installed = false;
		foreach(self::$installed_apps as $iapp) {
			if($iapp['app_id'] == hash('whirlpool',$app['name'])) {
				$installed = true;
				if(($iapp['app_version'] != $app['version'])
					|| ($app['plugin'] && (! $iapp['app_plugin']))) {
					return intval($iapp['app_id']);
				}
			}
		}
		if(! $installed && in_array($app['name'],self::$base_apps)) {
			return true;
		}
		return false;
	}


	static public function app_name_compare($a,$b) {
		return strcasecmp($a['name'],$b['name']);
	}

	/**
	 * @brief Parse app description.
	 *
	 * @param string $f filename
	 * @param boolean $translate (optional) default true
	 * @param boolean $sync (optional) default false
	 * @return boolean|array
	 */
	static public function parse_app_description($f, $translate = true, $sync = false) {
		$ret = [];
		$matches = [];

		$baseurl = z_root();
		//$channel = \App::get_channel();
		//$address = (($channel) ? $channel['channel_address'] : '');

		//future expansion

		$observer = \App::get_observer();

		$lines = @file($f);
		if($lines) {
			foreach($lines as $x) {
				if(preg_match('/^([a-zA-Z].*?):(.*?)$/ism',$x,$matches)) {
					$ret[$matches[1]] = trim($matches[2]);
				}
			}
		}

		if(! $ret['photo'])
			$ret['photo'] = $baseurl . '/' . get_default_profile_photo(80);

		$ret['type'] = 'system';
		$ret['plugin'] = '';

		foreach($ret as $k => $v) {
			if(strpos($v,'http') === 0) {
				if(! (local_channel() && strpos($v,z_root()) === 0)) {
					$ret[$k] = zid($v);
				}
			}
		}

		if(array_key_exists('desc',$ret))
			$ret['desc'] = str_replace(array('\'','"'),array('&#39;','&dquot;'),$ret['desc']);

		if(array_key_exists('target',$ret))
			$ret['target'] = str_replace(array('\'','"'),array('&#39;','&dquot;'),$ret['target']);

		if(array_key_exists('version',$ret))
			$ret['version'] = str_replace(array('\'','"'),array('&#39;','&dquot;'),$ret['version']);

		if(array_key_exists('categories',$ret))
			$ret['categories'] = str_replace(array('\'','"'),array('&#39;','&dquot;'),$ret['categories']);

		if(array_key_exists('requires',$ret) && !$sync) {
			$requires = explode(',',$ret['requires']);
			foreach($requires as $require) {
				$require = trim(strtolower($require));
				$config = false;

				if(substr($require, 0, 7) == 'config:') {
					$config = true;
					$require = ltrim($require, 'config:');
					$require = explode('=', $require);
				}

				switch($require) {
					case 'nologin':
						if(local_channel())
							unset($ret);
						break;
					case 'admin':
						if(! is_site_admin())
							unset($ret);
						break;
					case 'local_channel':
						if(! local_channel())
							unset($ret);
						break;
					case 'public_profile':
						if(! is_public_profile())
							unset($ret);
						break;
					case 'public_stream':
						if(! can_view_public_stream())
							unset($ret);
						break;
					case 'custom_role':
						if(get_pconfig(local_channel(),'system','permissions_role') !== 'custom')
							unset($ret);
						break;
					case 'observer':
						if(! $observer)
							unset($ret);
						break;
					default:
						if($config)
							$unset = ((get_config('system', $require[0]) == $require[1]) ? false : true);
						else
							$unset = ((local_channel() && feature_enabled(local_channel(),$require)) ? false : true);
						if($unset)
							unset($ret);
						break;
				}
			}
		}

		if(empty($ret)) {
			return false;
		}

		if($translate) {
			self::translate_system_apps($ret);
		}

		return $ret;
	}


	static public function translate_system_apps(&$arr) {
		$apps = array(
			'Apps' => t('Apps'),
			'Affinity Tool' => t('Affinity Tool'),
			'Articles' => t('Articles'),
			'Cards' => t('Cards'),
			'Admin' => t('Site Admin'),
			'Report Bug' => t('Report Bug'),
			'Bookmarks' => t('Bookmarks'),
			'Chatrooms' => t('Chatrooms'),
			'Content Filter' => t('Content Filter'),
			'Content Import' => t('Content Import'),
			'Connections' => t('Connections'),
			'Remote Diagnostics' => t('Remote Diagnostics'),
			'Suggest Channels' => t('Suggest Channels'),
			'Login' => t('Login'),
			'Channel Manager' => t('Channel Manager'),
			'Network' => t('Stream'),
			'Settings' => t('Settings'),
			'Files' => t('Files'),
			'Webpages' => t('Webpages'),
			'Wiki' => t('Wiki'),
			'Channel' => t('Channel'),
			'View Profile' => t('View Profile'),
			'Photos' => t('Photos'),
			'Calendar' => t('Calendar'),
			'Directory' => t('Directory'),
			'Help' => t('Help'),
			'Mail' => t('Mail'),
			'Mood' => t('Mood'),
			'Poke' => t('Poke'),
			'Chat' => t('Chat'),
			'Search' => t('Search'),
			'Probe' => t('Probe'),
			'Suggest' => t('Suggest'),
			'Random Channel' => t('Random Channel'),
			'Invite' => t('Invite'),
			'Features' => t('Features'),
			'Language' => t('Language'),
			'Post' => t('Post'),
			'Profile Photo' => t('Profile Photo'),
			'Profile' => t('Profile'),
			'Profiles' => t('Profiles'),
			'Privacy Groups' => t('Privacy Groups'),
			'Notifications' => t('Notifications'),
			'Order Apps' => t('Order Apps'),
			'CardDAV' => t('CardDAV'),
			'Channel Sources' => t('Channel Sources'),
			'Guest Access' => t('Guest Access'),
			'Notes' => t('Notes'),
			'OAuth Apps Manager' => t('OAuth Apps Manager'),
			'OAuth2 Apps Manager' => t('OAuth2 Apps Manager'),
			'PDL Editor' => t('PDL Editor'),
			'Contact Roles' => t('Contact Roles'),
			'Public Stream' => t('Public Stream'),
			'My Chatrooms' => t('My Chatrooms'),
			'Channel Export' => t('Channel Export')
		);

		if(array_key_exists('name',$arr)) {
			if(array_key_exists($arr['name'],$apps)) {
				$arr['name'] = $apps[$arr['name']];
			}
		}
		else {
			for($x = 0; $x < count($arr); $x++) {
				if(array_key_exists($arr[$x]['name'],$apps)) {
					$arr[$x]['name'] = $apps[$arr[$x]['name']];
				} else {
					// Try to guess by app name if not in list
					$arr[$x]['name'] = t(trim($arr[$x]['name']));
				}
			}
		}
	}

	/**
	 * @brief
	 *
	 * @param array $papp
	 *  papp is a portable app
	 * @param string $mode (optional) default 'view'
	 *   Render modes:
	 *   * \b view: normal mode for viewing an app via bbcode from a conversation or page
	 *       provides install/update button if you're logged in locally
	 *   * \b install: like view but does not display app-bin options if they are present
	 *   * \b list: normal mode for viewing an app on the app page
	 *       no buttons are shown
	 *   * \b edit: viewing the app page in editing mode provides a delete button
	 *   * \b nav: render apps for app-bin
	 *
	 * @return void|string Parsed HTML
	 */
	static public function app_render($papp, $mode = 'view') {
		$installed = false;

		if(!$papp) {
			return;
		}

		/**
		 * @hooks app_render_before
		 * Hook to manipulate the papp array before rendering
		 */

		$hookinfo = [
			'name' => $papp['name'],
			'photo' => $papp['photo']
		];

		call_hooks('app_render_manipulate_photo', $hookinfo);

		// We will only allow to manipulate the photo
		$papp['photo'] = $hookinfo['photo'];

		if(!$papp['photo']) {
			$papp['photo'] = 'icon:gear';
		}

		self::translate_system_apps($papp);

		if(isset($papp['plugin']) && trim($papp['plugin']) && (! plugin_is_installed(trim($papp['plugin']))))
			return '';

		$papp['papp'] = self::papp_encode($papp);

		// This will catch somebody clicking on a system "available" app that hasn't had the path macros replaced
		// and they are allowed to see the app
		if(strpos($papp['url'],'$baseurl') !== false || strpos($papp['url'],'$nick') !== false || strpos($papp['photo'],'$baseurl') !== false || strpos($papp['photo'],'$nick') !== false) {
			$view_channel = local_channel();
			if(! $view_channel) {

				$sys = get_sys_channel();
				$view_channel = $sys['channel_id'];
			}
			self::app_macros($view_channel,$papp);
		}

		if(strpos($papp['url'], ',')) {
			$urls = explode(',', $papp['url']);
			$papp['url'] = trim($urls[0]);
			$papp['settings_url'] = trim($urls[1]);
		}

		if(! strpos($papp['url'],'://'))
			$papp['url'] = z_root() . ((strpos($papp['url'],'/') === 0) ? '' : '/') . $papp['url'];


		foreach($papp as $k => $v) {
			if(strpos($v,'http') === 0 && $k != 'papp') {
				if(! (local_channel() && strpos($v,z_root()) === 0)) {
					$papp[$k] = zid($v);
				}
			}
			if($k === 'desc')
				$papp['desc'] = str_replace(array('\'','"'),array('&#39;','&dquot;'),$papp['desc']);

			if($k === 'requires') {
				$requires = explode(',',$v);

				foreach($requires as $require) {
					$require = trim(strtolower($require));
					$config = false;

					if(substr($require, 0, 7) == 'config:') {
						$config = true;
						$require = ltrim($require, 'config:');
						$require = explode('=', $require);
					}

					switch($require) {
						case 'nologin':
							if(local_channel())
								return '';
							break;
						case 'admin':
							if(! is_site_admin())
								return '';
							break;
						case 'local_channel':
							if(! local_channel())
								return '';
							break;
						case 'public_profile':
							if(! is_public_profile())
								return '';
							break;
						case 'public_stream':
							if(! can_view_public_stream())
								return '';
							break;
						case 'custom_role':
							if(get_pconfig(local_channel(),'system','permissions_role') != 'custom')
								return '';
							break;
						case 'observer':
							$observer = \App::get_observer();
							if(! $observer)
								return '';
							break;
						default:
							if($config)
								$unset = ((get_config('system', $require[0]) === $require[1]) ? false : true);
							else
								$unset = ((local_channel() && feature_enabled(local_channel(),$require)) ? false : true);
							if($unset)
								return '';
							break;
					}
				}
			}
		}

		$hosturl = '';

		if(local_channel()) {
			if(self::app_installed(local_channel(),$papp)) {
				$installed = true;
			}

			if ($installed && isset($papp['deleted']) && $papp['deleted']) {
				$installed = false;
			}

			$hosturl = z_root() . '/';
		}
		elseif(remote_channel()) {
			$observer = \App::get_observer();
			if($observer && $observer['xchan_network'] === 'zot6') {
				// some folks might have xchan_url redirected offsite, use the connurl
				$x = parse_url($observer['xchan_connurl']);
				if($x) {
					$hosturl = $x['scheme'] . '://' . $x['host'] . '/';
				}
			}
		}

		$install_action = (($installed) ? t('Update') : t('Install'));
		$icon = ((strpos($papp['photo'],'icon:') === 0) ? substr($papp['photo'],5) : '');

		if (!$installed && $mode === 'module') {
			$_SESSION['return_url'] = App::$query_string;
			return replace_macros(get_markup_template('app_install.tpl'), [
				'$papp' => $papp,
				'$install' => $install_action
			]);
		}

		if($mode === 'navbar') {
			return replace_macros(get_markup_template('app_nav_pinned.tpl'),array(
				'$app' => $papp,
				'$icon' => $icon,
			));
		}

		if($mode === 'nav') {
			return replace_macros(get_markup_template('app_nav.tpl'),array(
				'$app' => $papp,
				'$icon' => $icon,
			));
		}

		if($mode === 'inline') {
			return replace_macros(get_markup_template('app_inline.tpl'),array(
				'$app' => $papp,
				'$icon' => $icon,
				'$installed' => $installed,
				'$purchase' => ((isset($papp['page']) && (! $installed)) ? t('Purchase') : ''),
				'$action_label' => $install_action
			));
		}

		if(in_array($mode, ['nav-order', 'nav-order-pinned'])) {
			return replace_macros(get_markup_template('app_order.tpl'),array(
				'$app' => $papp,
				'$icon' => $icon,
				'$hosturl' => $hosturl,
				'$mode' => $mode
			));
		}

		if($mode === 'install') {
			$papp['embed'] = true;
		}

		return replace_macros(get_markup_template('app.tpl'),array(
			'$app' => $papp,
			'$icon' => $icon,
			'$hosturl' => $hosturl,
			'$purchase' => ((isset($papp['page']) && (! $installed)) ? t('Purchase') : ''),
			'$installed' => $installed,
			'$action_label' => (($hosturl && in_array($mode, ['view','install'])) ? $install_action : ''),
			'$edit' => ((local_channel() && $installed && $mode == 'edit') ? t('Edit') : ''),
			'$delete' => ((local_channel() && $mode == 'edit') ? t('Delete') : ''),
			'$undelete' => ((local_channel() && $mode == 'edit') ? t('Undelete') : ''),
			'$settings_url' => ((local_channel() && $installed && $mode == 'list' && isset($papp['settings_url'])) ? $papp['settings_url'] : ''),
			'$deleted' => $papp['deleted'] ?? false,
			'$feature' => ((isset($papp['embed']) || $mode == 'edit') ? false : true),
			'$pin' => ((isset($papp['embed']) || $mode == 'edit') ? false : true),
			'$featured' => ((isset($papp['categories']) && strpos($papp['categories'], 'nav_featured_app') !== false) ? true : false),
			'$pinned' => ((isset($papp['categories']) && strpos($papp['categories'], 'nav_pinned_app') !== false) ? true : false),
			'$mode' => $mode,
			'$add' => t('Add to app-tray'),
			'$remove' => t('Remove from app-tray'),
			'$add_nav' => t('Pin to navbar'),
			'$remove_nav' => t('Unpin from navbar'),
			'$rpath' => z_root() . '/apps'
		));
	}


	static public function app_install($uid,$app) {

		if(! is_array($app)) {
			$r = q("select * from app where app_name = '%s' and app_channel = 0",
				dbesc($app)
			);
			if(! $r)
				return false;

			$app = self::app_encode($r[0]);
		}

		$app['uid'] = $uid;

		if(self::app_installed($uid,$app,true)) {
			$x = self::app_update($app);
		}
		else {
			$x = self::app_store($app);
		}

		if($x['success']) {
			$r = q("select * from app where app_id = '%s' and app_channel = %d limit 1",
				dbesc($x['app_id']),
				intval($uid)
			);
			if($r) {
				if($app['uid']) {
					if((isset($app['categories']) && $app['categories']) && !(isset($app['term']) && $app['term'])) {
						$r[0]['term'] = q("select * from term where otype = %d and oid = %d",
							intval(TERM_OBJ_APP),
							intval($r[0]['id'])
						);
					}
				}
			}
			return $x['app_id'];
		}
		return false;
	}

	/**
	 * @brief
	 *
	 * @param mixed $uid If not set return false, otherwise no influence
	 * @param array $app
	 * @return boolean
	 */
	static public function can_delete($uid, $app) {
		if(! $uid) {
			return false;
		}

		$base_apps = self::get_base_apps();
		if($base_apps) {
			foreach($base_apps as $b) {
				if($app['guid'] === hash('whirlpool', $b)) {
					return false;
				}
			}
		}

		return true;
	}


	static public function app_destroy($uid,$app) {

		if($uid && $app['guid']) {
			$x = q("select * from app where app_id = '%s' and app_channel = %d limit 1",
				dbesc($app['guid']),
				intval($uid)
			);

			if($x && intval($x[0]['app_deleted'])) {
				self::app_undestroy($uid, $app);
				return;
			}

			if(self::can_delete($uid,$app)) {
				q("delete from app where app_id = '%s' and app_channel = %d",
					dbesc($app['guid']),
					intval($uid)
				);

				q("delete from term where otype = %d and oid = %d",
					intval(TERM_OBJ_APP),
					intval($x[0]['id'])
				);

				/**
				 * @hooks app_destroy
				 *  Called after app entry got removed from database
				 *  and provide app array from database.
				 */
				call_hooks('app_destroy', $x[0]);
			}
			else {
				q("update app set app_deleted = 1 where app_id = '%s' and app_channel = %d",
					dbesc($app['guid']),
					intval($uid)
				);
			}
		}
	}

	/**
	 * @brief Undelete a system app.
	 *
	 * @param int $uid
	 * @param array $app
	 */
	static public function app_undestroy($uid, $app) {
		if($uid && $app['guid']) {
			$x = q("select * from app where app_id = '%s' and app_channel = %d limit 1",
				dbesc($app['guid']),
				intval($uid)
			);
			if($x && intval($x[0]['app_deleted']) && $x[0]['app_system']) {
				q("update app set app_deleted = 0 where app_id = '%s' and app_channel = %d",
					dbesc($app['guid']),
					intval($uid)
				);
			}
		}
	}

	/**
	 * @brief
	 *
	 * @param int $uid
	 * @param array $app
	 * @param string $term
	 * @return void
	 */
	static public function app_feature($uid, $app, $term) {
		$r = q("select id from app where app_id = '%s' and app_channel = %d limit 1",
			dbesc($app['guid']),
			intval($uid)
		);

		$x = q("select * from term where otype = %d and oid = %d and term = '%s' limit 1",
			intval(TERM_OBJ_APP),
			intval($r[0]['id']),
			dbesc($term)
		);

		if($x) {
			q("delete from term where otype = %d and oid = %d and term = '%s'",
				intval(TERM_OBJ_APP),
				intval($x[0]['oid']),
				dbesc($term)
			);
		}
		else {
			store_item_tag($uid, $r[0]['id'], TERM_OBJ_APP, TERM_CATEGORY, $term, escape_tags(z_root() . '/apps/?f=&cat=' . $term));
		}
	}

	/**
	 * @brief
	 *
	 * @param int $uid
	 * @param array $app
	 * @param boolean $bypass_filter (optional) default false
	 * @return boolean
	 */
	static public function app_installed($uid, $app, $bypass_filter = false) {

		$r = q("select id from app where app_id = '%s' and app_channel = %d limit 1",
			dbesc((array_key_exists('guid', $app)) ? $app['guid'] : ''),
			intval($uid)
		);
		if(!$bypass_filter) {
			$filter_arr = [
				'uid' => $uid,
				'app' => $app,
				'installed' => $r
			];
			/**
			 * @hooks app_installed_filter
			 *  * \e int \b uid
			 *  * \e array \b app
			 *  * \e mixed \b installed - return value
			 */
			call_hooks('app_installed_filter', $filter_arr);
			$r = $filter_arr['installed'];
		}

		return(($r) ? true : false);
	}


	static public function addon_app_installed($uid,$app,$bypass_filter=false) {

		$r = q("select id from app where app_plugin = '%s' and app_channel = %d limit 1",
			dbesc($app),
			intval($uid)
		);
		if (!$bypass_filter) {
			$filter_arr = [
				'uid'=>$uid,
				'app'=>$app,
				'installed'=>$r
			];
			/**
			 * @hooks addon_app_installed_filter
			 *  * \e int \b uid
			 *  * \e array \b app
			 *  * \e mixed \b installed - return value
			 */
			call_hooks('addon_app_installed_filter', $filter_arr);
			$r = $filter_arr['installed'];
		}

		return(($r) ? true : false);
	}

	static public function system_app_installed($uid,$app,$bypass_filter=false) {

		$r = q("select id from app where app_id = '%s' and app_channel = %d limit 1",
			dbesc(hash('whirlpool',$app)),
			intval($uid)
		);
		if (!$bypass_filter) {
			$filter_arr = [
				'uid'=>$uid,
				'app'=>$app,
				'installed'=>$r
			];
			/**
			 * @hooks system_app_installed_filter
			 *  * \e int \b uid
			 *  * \e array \b app
			 *  * \e mixed \b installed - return value
			 */
			call_hooks('system_app_installed_filter', $filter_arr);
			$r = $filter_arr['installed'];
		}

		return(($r) ? true : false);
	}

	/**
	 * @brief
	 *
	 * @param int $uid
	 * @param boolean $deleted
	 * @param array $cats
	 * @return boolean|array
	 */
	static public function app_list($uid, $deleted = false, $cats = []) {
		if($deleted)
			$sql_extra = '';
		else
			$sql_extra = ' and app_deleted = 0 ';

		if($cats) {
			$cat_sql_extra = ' and ( ';

			foreach($cats as $cat) {
				if(strpos($cat_sql_extra, 'term'))
					$cat_sql_extra .= 'or ';

				$cat_sql_extra .= "term = '" . dbesc($cat) . "' ";
			}

			$cat_sql_extra .=  ") ";

			$r = q("select oid from term where otype = %d $cat_sql_extra",
				intval(TERM_OBJ_APP)
			);
			if(! $r)
				return $r;

			$sql_extra .= ' and app.id in ( ';
			$s = '';
			foreach($r as $rr) {
				if($s)
					$s .= ',';

				$s .= intval($rr['oid']);
			}
			$sql_extra .= $s . ') ';
		}

		$r = q("select * from app where app_channel = %d $sql_extra order by app_name asc",
			intval($uid)
		);

		if($r) {
			$hookinfo = [
					'uid' => $uid,
					'deleted' => $deleted,
					'cats' => $cats,
					'apps' => $r,
			];
			/**
			 * @hooks app_list
			 *  * \e int \b uid
			 *  * \e boolean \b deleted
			 *  * \e array \b cats
			 *  * \e array \b apps - return value
			 */
			call_hooks('app_list', $hookinfo);
			$r = $hookinfo['apps'];

			for($x = 0; $x < count($r); $x++) {
				if(! $r[$x]['app_system'])
					$r[$x]['type'] = 'personal';

				$r[$x]['term'] = q("select * from term where otype = %d and oid = %d",
					intval(TERM_OBJ_APP),
					intval($r[$x]['id'])
				);
			}
		}

		return $r;
	}

	static public function app_order($uid,$apps,$menu) {

		if(! $apps)
			return $apps;

		$conf = (($menu === 'nav_featured_app') ? 'app_order' : 'app_pin_order');

		$x = (($uid) ? get_pconfig($uid,'system',$conf) : get_config('system',$conf));
		if(($x) && (! is_array($x))) {
			$y = explode(',',$x);
			$y = array_map('trim',$y);
			$x = $y;
		}

		if(! (is_array($x) && ($x)))
			return $apps;

		$ret = [];
		foreach($x as $xx) {
			$y = self::find_app_in_array($xx,$apps);
			if($y) {
				$ret[] = $y;
			}
		}
		foreach($apps as $ap) {
			if(! self::find_app_in_array($ap['name'],$ret)) {
				$ret[] = $ap;
			}
		}

		return $ret;
	}

	static function find_app_in_array($name,$arr) {
		if(! $arr)
			return false;

		foreach($arr as $x) {
			if($x['name'] === $name) {
					return $x;
			}
		}
		return false;
	}

	/**
	 * @brief
	 *
	 * @param int $uid
	 * @param int $guid
	 * @param string $menu
	 * @return void
	 */
	static function moveup($uid, $guid, $menu) {
		$syslist = [];

		$conf = (($menu === 'nav_featured_app') ? 'app_order' : 'app_pin_order');

		$list = self::app_list($uid, false, [ $menu ]);
		if($list) {
			foreach($list as $li) {
				$papp = self::app_encode($li);
				$syslist[] = $papp;
			}
		}
		self::translate_system_apps($syslist);

		usort($syslist,'self::app_name_compare');

		$syslist = self::app_order($uid,$syslist,$menu);

		if(! $syslist)
			return;

		foreach($syslist as $k => $li) {
			if($li['guid'] === $guid) {
				$position = $k;
				break;
			}
		}
		if(! $position)
			return;

		$dest_position = $position - 1;
		$saved = $syslist[$dest_position];
		$syslist[$dest_position] = $syslist[$position];
		$syslist[$position] = $saved;

		$narr = [];
		foreach($syslist as $x) {
			$narr[] = $x['name'];
		}

		set_pconfig($uid,'system',$conf,implode(',',$narr));
	}

	/**
	 * @brief
	 *
	 * @param int $uid
	 * @param int $guid
	 * @param string $menu
	 * @return void
	 */
	static function movedown($uid, $guid, $menu) {
		$syslist = [];

		$conf = (($menu === 'nav_featured_app') ? 'app_order' : 'app_pin_order');

		$list = self::app_list($uid, false, [ $menu ]);
		if($list) {
			foreach($list as $li) {
				$papp = self::app_encode($li);
				if($menu !== 'nav_pinned_app' && strpos($papp['categories'],'nav_pinned_app') !== false)
					continue;

				$syslist[] = $papp;
			}
		}
		self::translate_system_apps($syslist);

		usort($syslist,'self::app_name_compare');

		$syslist = self::app_order($uid,$syslist,$menu);

		if(! $syslist)
			return;

		foreach($syslist as $k => $li) {
			if($li['guid'] === $guid) {
				$position = $k;
				break;
			}
		}
		if($position >= count($syslist) - 1)
			return;

		$dest_position = $position + 1;
		$saved = $syslist[$dest_position];
		$syslist[$dest_position] = $syslist[$position];
		$syslist[$position] = $saved;

		$narr = [];
		foreach($syslist as $x) {
			$narr[] = $x['name'];
		}

		set_pconfig($uid,'system',$conf,implode(',',$narr));
	}

	static public function app_decode($s) {
		$x = base64_decode(str_replace(array('<br />',"\r","\n",' '),array('','','',''),$s));
		return json_decode($x,true);
	}

	/**
	 * @brief
	 *
	 * @param int $uid
	 * @param[in,out] array $arr
	 * @return void
	 */
	static public function app_macros($uid, &$arr) {

		if(! intval($uid))
			return;

		$baseurl = z_root();
		$channel = channelx_by_n($uid);
		$address = (($channel) ? $channel['channel_address'] : '');

		//future expansion

		//$observer = \App::get_observer();

		$arr['url'] = str_replace(array('$baseurl','$nick'),array($baseurl,$address),$arr['url']);
		$arr['photo'] = str_replace(array('$baseurl','$nick'),array($baseurl,$address),$arr['photo']);
	}



	static public function app_store($arr) {

		//logger('app_store: ' . print_r($arr,true));

		$darray = array();
		$ret = array('success' => false);

		$sys = get_sys_channel();

		self::app_macros($arr['uid'],$arr);

		$darray['app_url']     = ((x($arr,'url')) ? $arr['url'] : '');
		$darray['app_channel'] = ((x($arr,'uid')) ? $arr['uid'] : 0);

		if(! $darray['app_url'])
			return $ret;

		if((! $arr['uid']) && (! $arr['author'])) {
			$arr['author'] = $sys['channel_hash'];
		}

		if($arr['photo'] && (strpos($arr['photo'],'icon:') === false) && (strpos($arr['photo'],z_root()) !== false)) {
			$x = import_xchan_photo(str_replace('$baseurl',z_root(),$arr['photo']),get_observer_hash(),true);
			$arr['photo'] = $x[1];
		}


		$darray['app_id']       = ((x($arr,'guid'))     ? $arr['guid'] : random_string(). '.' . \App::get_hostname());
		$darray['app_sig']      = ((x($arr,'sig'))      ? $arr['sig'] : '');
		$darray['app_author']   = ((x($arr,'author'))   ? $arr['author'] : get_observer_hash());
		$darray['app_name']     = ((x($arr,'name'))     ? escape_tags($arr['name']) : t('Unknown'));
		$darray['app_desc']     = ((x($arr,'desc'))     ? escape_tags($arr['desc']) : '');
		$darray['app_photo']    = ((x($arr,'photo'))    ? $arr['photo'] : z_root() . '/' . get_default_profile_photo(80));
		$darray['app_version']  = ((x($arr,'version'))  ? escape_tags($arr['version']) : '');
		$darray['app_addr']     = ((x($arr,'addr'))     ? escape_tags($arr['addr']) : '');
		$darray['app_price']    = ((x($arr,'price'))    ? escape_tags($arr['price']) : '');
		$darray['app_page']     = ((x($arr,'page'))     ? escape_tags($arr['page']) : '');
		$darray['app_plugin']   = ((x($arr,'plugin'))   ? escape_tags(trim($arr['plugin'])) : '');
		$darray['app_requires'] = ((x($arr,'requires')) ? escape_tags($arr['requires']) : '');
		$darray['app_system']   = ((x($arr,'system'))   ? intval($arr['system']) : 0);
		$darray['app_deleted']  = ((x($arr,'deleted'))  ? intval($arr['deleted']) : 0);
		$darray['app_options']  = ((x($arr,'options')) ? intval($arr['options']) : 0);

		$created = datetime_convert();

		$r = q("insert into app ( app_id, app_sig, app_author, app_name, app_desc, app_url, app_photo, app_version, app_channel, app_addr, app_price, app_page, app_requires, app_created, app_edited, app_system, app_plugin, app_deleted, app_options ) values ( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', %d, %d )",
			dbesc($darray['app_id']),
			dbesc($darray['app_sig']),
			dbesc($darray['app_author']),
			dbesc($darray['app_name']),
			dbesc($darray['app_desc']),
			dbesc($darray['app_url']),
			dbesc($darray['app_photo']),
			dbesc($darray['app_version']),
			intval($darray['app_channel']),
			dbesc($darray['app_addr']),
			dbesc($darray['app_price']),
			dbesc($darray['app_page']),
			dbesc($darray['app_requires']),
			dbesc($created),
			dbesc($created),
			intval($darray['app_system']),
			dbesc($darray['app_plugin']),
			intval($darray['app_deleted']),
			intval($darray['app_options'])
		);

		if($r) {
			$ret['success'] = true;
			$ret['app_id'] = $darray['app_id'];
		}
		if(isset($arr['categories']) && $arr['categories']) {
			$x = q("select id from app where app_id = '%s' and app_channel = %d limit 1",
				dbesc($darray['app_id']),
				intval($darray['app_channel'])
			);
			$y = explode(',',$arr['categories']);
			if($y) {
				foreach($y as $t) {
					$t = escape_tags(trim($t));
					if($t) {
						store_item_tag($darray['app_channel'], $x[0]['id'], TERM_OBJ_APP, TERM_CATEGORY, $t, z_root() . '/apps/?f=&cat=' . $t);
					}
				}
			}
		}

		return $ret;
	}


	static public function app_update($arr) {

		//logger('app_update: ' . print_r($arr,true));
		$darray = array();
		$ret = array('success' => false);

		self::app_macros($arr['uid'],$arr);


		$darray['app_url']     = ((x($arr,'url')) ? $arr['url'] : '');
		$darray['app_channel'] = ((x($arr,'uid')) ? $arr['uid'] : 0);
		$darray['app_id']      = ((x($arr,'guid')) ? $arr['guid'] : 0);

		if((! $darray['app_url']) || (! $darray['app_id']))
			return $ret;

		if($arr['photo'] && (strpos($arr['photo'],'icon:') === false) && (strpos($arr['photo'],z_root()) !== false)) {
			$x = import_xchan_photo(str_replace('$baseurl',z_root(),$arr['photo']),get_observer_hash(),true);
			$arr['photo'] = $x[1];
		}

		$darray['app_sig']      = ((x($arr,'sig')) ? $arr['sig'] : '');
		$darray['app_author']   = ((x($arr,'author')) ? $arr['author'] : get_observer_hash());
		$darray['app_name']     = ((x($arr,'name')) ? escape_tags($arr['name']) : t('Unknown'));
		$darray['app_desc']     = ((x($arr,'desc')) ? escape_tags($arr['desc']) : '');
		$darray['app_photo']    = ((x($arr,'photo')) ? $arr['photo'] : z_root() . '/' . get_default_profile_photo(80));
		$darray['app_version']  = ((x($arr,'version')) ? escape_tags($arr['version']) : '');
		$darray['app_addr']     = ((x($arr,'addr')) ? escape_tags($arr['addr']) : '');
		$darray['app_price']    = ((x($arr,'price')) ? escape_tags($arr['price']) : '');
		$darray['app_page']     = ((x($arr,'page')) ? escape_tags($arr['page']) : '');
		$darray['app_plugin']   = ((x($arr,'plugin')) ? escape_tags(trim($arr['plugin'])) : '');
		$darray['app_requires'] = ((x($arr,'requires')) ? escape_tags($arr['requires']) : '');
		$darray['app_system']   = ((x($arr,'system'))   ? intval($arr['system']) : 0);
		$darray['app_deleted']  = ((x($arr,'deleted'))  ? intval($arr['deleted']) : 0);
		$darray['app_options']  = ((x($arr,'options')) ? intval($arr['options']) : 0);

		$edited = datetime_convert();

		$r = q("update app set app_sig = '%s', app_author = '%s', app_name = '%s', app_desc = '%s', app_url = '%s', app_photo = '%s', app_version = '%s', app_addr = '%s', app_price = '%s', app_page = '%s', app_requires = '%s', app_edited = '%s', app_system = %d, app_plugin = '%s', app_deleted = %d, app_options = %d where app_id = '%s' and app_channel = %d",
			dbesc($darray['app_sig']),
			dbesc($darray['app_author']),
			dbesc($darray['app_name']),
			dbesc($darray['app_desc']),
			dbesc($darray['app_url']),
			dbesc($darray['app_photo']),
			dbesc($darray['app_version']),
			dbesc($darray['app_addr']),
			dbesc($darray['app_price']),
			dbesc($darray['app_page']),
			dbesc($darray['app_requires']),
			dbesc($edited),
			intval($darray['app_system']),
			dbesc($darray['app_plugin']),
			intval($darray['app_deleted']),
			intval($darray['app_options']),
			dbesc($darray['app_id']),
			intval($darray['app_channel'])
		);
		if($r) {
			$ret['success'] = true;
			$ret['app_id'] = $darray['app_id'];
		}

		$x = q("select id from app where app_id = '%s' and app_channel = %d limit 1",
			dbesc($darray['app_id']),
			intval($darray['app_channel'])
		);

		// if updating an embed app, don't mess with any existing categories.

		if(array_key_exists('embed',$arr) && intval($arr['embed']) && (intval($darray['app_channel'])))
			return $ret;

		if($x) {
			q("delete from term where otype = %d and oid = %d",
				intval(TERM_OBJ_APP),
				intval($x[0]['id'])
			);
			if(isset($arr['categories']) && $arr['categories']) {
				$y = explode(',',$arr['categories']);
				if($y) {
					foreach($y as $t) {
						$t = trim($t);
						if($t) {
							store_item_tag($darray['app_channel'],$x[0]['id'],TERM_OBJ_APP,TERM_CATEGORY,escape_tags($t),escape_tags(z_root() . '/apps/?f=&cat=' . escape_tags($t)));
						}
					}
				}
			}
		}

		return $ret;
	}

	/**
	 * @brief
	 *
	 * @param array $app
	 * @param boolean $embed (optional) default false
	 * @return array|string
	 */
	static public function app_encode($app, $embed = false) {
		$ret = [];

		$ret['type'] = 'personal';

		if(!empty($app['app_id']))
			$ret['guid'] = $app['app_id'];

		if(!empty($app['app_sig']))
			$ret['sig'] = $app['app_sig'];

		if(!empty($app['app_author']))
			$ret['author'] = $app['app_author'];

		if(!empty($app['app_name']))
			$ret['name'] = $app['app_name'];

		if(!empty($app['app_desc']))
			$ret['desc'] = $app['app_desc'];

		if(!empty($app['app_url']))
			$ret['url'] = $app['app_url'];

		if(!empty($app['app_photo']))
			$ret['photo'] = $app['app_photo'];

		if(!empty($app['app_icon']))
			$ret['icon'] = $app['app_icon'];

		if(!empty($app['app_version']))
			$ret['version'] = $app['app_version'];

		if(!empty($app['app_addr']))
			$ret['addr'] = $app['app_addr'];

		if(!empty($app['app_price']))
			$ret['price'] = $app['app_price'];

		if(!empty($app['app_page']))
			$ret['page'] = $app['app_page'];

		if(!empty($app['app_requires']))
			$ret['requires'] = $app['app_requires'];

		if(!empty($app['app_system']))
			$ret['system'] = $app['app_system'];

		if(!empty($app['app_options']))
			$ret['options'] = $app['app_options'];

		if(!empty($app['app_plugin']))
			$ret['plugin'] = trim($app['app_plugin']);

		if(!empty($app['app_deleted']))
			$ret['deleted'] = $app['app_deleted'];

		if(!empty($app['term']) && is_array($app['term'])) {
			$s = '';
			foreach($app['term'] as $t) {
				if($s)
					$s .= ',';

				$s .= $t['term'];
			}
			$ret['categories'] = $s;
		}

		if(! $embed)
			return $ret;

		$ret['embed'] = true;

		if(array_key_exists('categories',$ret))
			unset($ret['categories']);

		$j = json_encode($ret);

		return '[app]' . chunk_split(base64_encode($j),72,"\n") . '[/app]';
	}


	static public function papp_encode($papp) {
		return chunk_split(base64_encode(json_encode($papp)),72,"\n");
	}

	static public function get_papp($app) {

		$r = q("select * from app where app_id = '%s' and app_channel = 0 limit 1",
			dbesc(hash('whirlpool', $app))
		);

		if ($r) {
			$papp = self::app_encode($r[0]);
			return $papp;
		}

		return false;
	}
}
