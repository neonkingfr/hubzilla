<?php /** @file */

namespace Zotlabs\Daemon;

use Zotlabs\Lib\Libzotdir;

class Cron_daily {

	static public function run($argc, $argv) {

		logger('cron_daily: start');

		/**
		 * Cron Daily
		 *
		 */

		Libzotdir::check_upstream_directory();

		// Fire off the Cron_weekly process if it's the correct day.

		$d3 = intval(datetime_convert('UTC', 'UTC', 'now', 'N'));
		if ($d3 == 7) {
			Master::Summon(array('Cron_weekly'));
		}

		// once daily run birthday_updates and then expire in background

		// FIXME: add birthday updates, both locally and for xprof for use
		// by directory servers

		update_birthdays();

		// expire any read notifications over a month old

		q("delete from notify where seen = 1 and created < %s - INTERVAL %s",
			db_utcnow(), db_quoteinterval('30 DAY')
		);

		// expire any unread notifications over a year old

		q("delete from notify where seen = 0 and created < %s - INTERVAL %s",
			db_utcnow(), db_quoteinterval('1 YEAR')
		);

		// expire anonymous sse notification entries once a day

		q("delete from xconfig where xchan like '%s'",
			dbesc('sse_id.%')
		);

		// Mark items seen after X days (default 90)

		$r = dbq("select channel_id from channel where channel_removed = 0");
		if ($r) {
			foreach ($r as $rr) {
				$mark_seen_days = get_pconfig($rr['channel_id'], 'system', 'mark_seen_days', 90);
				q("UPDATE item SET item_unseen = 0 WHERE
					uid = %d AND item_unseen = 1
					AND created < %s - INTERVAL %s",
					intval($rr['channel_id']),
					db_utcnow(),
					db_quoteinterval($mark_seen_days . ' DAY')
				);
			}
		}

		// Clean up emdedded content cache
		q("DELETE FROM cache WHERE updated < %s - INTERVAL %s",
			db_utcnow(),
			db_quoteinterval(get_config('system', 'active_expire_days', '30') . ' DAY')
		);

		//update statistics in config
		require_once('include/statistics_fns.php');
		update_channels_total_stat();
		update_channels_active_halfyear_stat();
		update_channels_active_monthly_stat();
		update_local_posts_stat();
		update_local_comments_stat();


		// expire old delivery reports

		$keep_reports = intval(get_config('system', 'expire_delivery_reports'));
		if ($keep_reports === 0)
			$keep_reports = 10;

		q("delete from dreport where dreport_time < %s - INTERVAL %s",
			db_utcnow(),
			db_quoteinterval($keep_reports . ' DAY')
		);

		// expire any expired accounts
		downgrade_accounts();

		Master::Summon(array('Expire'));
		Master::Summon(array('Cli_suggest'));

		remove_obsolete_hublocs();
		remove_duplicate_singleton_hublocs();

		$date = datetime_convert();
		call_hooks('cron_daily', $date);

		set_config('system', 'last_expire_day', intval(datetime_convert('UTC', 'UTC', 'now', 'd')));

		/**
		 * End Cron Daily
		 */

		 return;
	}
}
