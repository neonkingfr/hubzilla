<?php

namespace Zotlabs\Daemon;

require_once('include/items.php');

class Expire {

	static public function run($argc, $argv) {

		cli_startup();

		$pid = get_config('procid', 'expire', false);
		if ($pid && (function_exists('posix_kill') ? posix_kill($pid, 0) : true)) {
			logger('procedure already run with pid ' . $pid, LOGGER_DEBUG);
			return;
		}

		$pid = getmypid();
		set_config('procid', 'expire', $pid);

		// perform final cleanup on previously delete items

		$r = q("select id from item where item_deleted = 1 and item_pending_remove = 0 and changed < %s - INTERVAL %s",
			db_utcnow(),
			db_quoteinterval('10 DAY')
		);
		if ($r) {
			foreach ($r as $rr) {
				drop_item($rr['id'], false, DROPITEM_PHASE2);
			}
		}

		// physically remove anything that has been deleted for more than two months

		q("delete from item where item_pending_remove = 1 and changed < %s - INTERVAL %s",
			db_utcnow(),
			db_quoteinterval('36 DAY')
		);

		if (intval(get_config('system', 'optimize_items')))
			q("optimize table item");

		logger('expire: start with pid ' . $pid, LOGGER_DEBUG);

		$site_expire    = intval(get_config('system', 'default_expire_days'));
		$commented_days = intval(get_config('system', 'active_expire_days'));

		logger('site_expire: ' . $site_expire);

		$r = dbq("SELECT channel_id, channel_system, channel_address, channel_expire_days from channel where true");

		if ($r) {
			foreach ($r as $rr) {

				// expire the sys channel separately
				if (intval($rr['channel_system']))
					continue;

				// service class default (if non-zero) over-rides the site default
				$service_class_expire = service_class_fetch($rr['channel_id'], 'expire_days');

				if (intval($service_class_expire))
					$channel_expire = $service_class_expire;
				else
					$channel_expire = $site_expire;

				if (intval($channel_expire) && (intval($channel_expire) < intval($rr['channel_expire_days'])) ||
					intval($rr['channel_expire_days'] == 0)) {
					$expire_days = $channel_expire;
				}
				else {
					$expire_days = $rr['channel_expire_days'];
				}

				// if the site or service class expiration is non-zero and less than person expiration, use that
				logger('Expire: ' . $rr['channel_address'] . ' interval: ' . $expire_days, LOGGER_DEBUG);
				item_expire($rr['channel_id'], $expire_days, $commented_days);
			}
		}

		$x = get_sys_channel();
		if ($x) {

			// this should probably just fetch the channel_expire_days from the sys channel,
			// but there's no convenient way to set it.
			$expire_days = get_config('system', 'sys_expire_days');
			if ($expire_days === false)
				$expire_days = 30;

			if (intval($site_expire) && (intval($site_expire) < intval($expire_days))) {
				$expire_days = $site_expire;
			}

			logger('Expire: sys interval: ' . $expire_days, LOGGER_DEBUG);

			if ($expire_days) {
				item_expire($x['channel_id'], $expire_days, $commented_days);
			}

			logger('Expire: sys: done', LOGGER_DEBUG);
		}

		del_config('procid', 'expire');

		return;
	}
}
