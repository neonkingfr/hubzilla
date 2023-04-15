<?php /** @file */

namespace Zotlabs\Daemon;

class Poller {

	static public function run($argc, $argv) {

		$maxsysload = intval(get_config('system', 'maxloadavg'));
		if ($maxsysload < 1)
			$maxsysload = 50;
		if (function_exists('sys_getloadavg')) {
			$load = sys_getloadavg();
			if (intval($load[0]) > $maxsysload) {
				logger('system: load ' . $load . ' too high. Poller deferred to next scheduled run.');
				return;
			}
		}

		$interval = get_config('queueworker', 'queue_interval', 500000);

		logger('poller: start');

		$manual_id  = 0;
		$force   = false;

		if (($argc > 1) && ($argv[1] == 'force'))
			$force = true;

		if (($argc > 1) && ($argv[1] == 'restart')) {
			$generation = intval($argv[2]);
			if (!$generation)
				return;
		}

		if (($argc > 1) && intval($argv[1])) {
			$manual_id = intval($argv[1]);
			$force     = true;
		}

		$sql_extra = (($manual_id) ? " AND abook_id = " . intval($manual_id) . " " : "");

		reload_plugins();

		// Only poll from those with suitable relationships
		$abandon_days = intval(get_config('system', 'account_abandon_days', 0));
		$abandon_sql = (($abandon_days)
			? sprintf(" AND account_lastlog > %s - INTERVAL %s ", db_utcnow(), db_quoteinterval(intval($abandon_days) . ' DAY'))
			: ''
		);

		$randfunc = db_getfunc('RAND');

		$contacts = q("SELECT abook.abook_updated, abook.abook_connected, abook.abook_feed,
			abook.abook_channel, abook.abook_id, abook.abook_archived, abook.abook_pending,
			abook.abook_ignored, abook.abook_blocked,
			xchan.xchan_network,
			account.account_lastlog, account.account_flags
			FROM abook LEFT JOIN xchan on abook_xchan = xchan_hash
			LEFT JOIN account on abook_account = account_id
			where abook_self = 0
			$sql_extra
			AND (( account_flags = %d ) OR ( account_flags = %d )) $abandon_sql ORDER BY $randfunc",
			intval(ACCOUNT_OK),
			intval(ACCOUNT_UNVERIFIED)     // FIXME
		);

		if ($contacts) {
			foreach ($contacts as $contact) {

				$update = false;

				$t = $contact['abook_updated'];
				$c = $contact['abook_connected'];

				if (intval($contact['abook_feed'])) {
					$min = service_class_fetch($contact['abook_channel'], 'minimum_feedcheck_minutes');
					if (!$min)
						$min = intval(get_config('system', 'minimum_feedcheck_minutes'));
					if (!$min)
						$min = 60;

					if ($t !== $c) {
						// if the last fetch failed only attempt fetch once a day
						$min = 60 * 24;
					}

					$x = datetime_convert('UTC', 'UTC', "now - $min minutes");

					if ($t < $x) {
						Master::Summon(['Onepoll', $contact['abook_id']]);

						if ($interval) {
							usleep($interval);
						}
					}

					continue;

				}

				if ($contact['xchan_network'] !== 'zot6')
					continue;

				if ($c == $t) {
					if (datetime_convert('UTC', 'UTC', 'now') > datetime_convert('UTC', 'UTC', $t . " + 1 day"))
						$update = true;
				}
				else {

					// if we've never connected with them, start the mark for death countdown from now

					if ($c <= NULL_DATE) {
						q("update abook set abook_connected = '%s'  where abook_id = %d",
							dbesc(datetime_convert()),
							intval($contact['abook_id'])
						);
						$c      = datetime_convert();
						$update = true;
					}

					// He's dead, Jim

					if (strcmp(datetime_convert('UTC', 'UTC', 'now'), datetime_convert('UTC', 'UTC', $c . " + 30 day")) > 0) {
						q("update abook set abook_archived = 1 where abook_id = %d",
							intval($contact['abook_id'])
						);
						continue;
					}

					if (intval($contact['abook_archived'])) {
						continue;
					}

					// might be dead, so maybe don't poll quite so often

					// recently deceased, so keep up the regular schedule for 3 days

					if ((strcmp(datetime_convert('UTC', 'UTC', 'now'), datetime_convert('UTC', 'UTC', $c . " + 3 day")) > 0)
						&& (strcmp(datetime_convert('UTC', 'UTC', 'now'), datetime_convert('UTC', 'UTC', $t . " + 1 day")) > 0))
						$update = true;

					// After that back off and put them on a morphine drip

					if (strcmp(datetime_convert('UTC', 'UTC', 'now'), datetime_convert('UTC', 'UTC', $t . " + 2 day")) > 0) {
						$update = true;
					}

				}

				if (intval($contact['abook_pending']) || intval($contact['abook_archived']) || intval($contact['abook_ignored']) || intval($contact['abook_blocked']))
					continue;

				if ((!$update) && (!$force))
					continue;

				Master::Summon(['Onepoll', $contact['abook_id']]);

				if ($interval) {
					usleep($interval);
				}
			}
		}

		$dirmode = intval(get_config('system', 'directory_mode'));

		if ($dirmode == DIRECTORY_MODE_SECONDARY || $dirmode == DIRECTORY_MODE_PRIMARY) {
			$r = q("SELECT * FROM updates WHERE ud_flags = 1 AND (ud_last = '%s' OR ud_last > %s - INTERVAL %s)",
				dbesc(NULL_DATE),
				db_utcnow(),
				db_quoteinterval('7 DAY')
			);

			if ($r) {
				foreach ($r as $rr) {

					// If they didn't respond when we attempted before, back off to once a day
					// After 7 days we won't bother anymore

					if ($rr['ud_last'] > NULL_DATE)
						if ($rr['ud_last'] > datetime_convert('UTC', 'UTC', 'now - 1 day'))
							continue;

					Master::Summon(['Onedirsync', $rr['ud_id']]);

					if ($interval) {
						usleep($interval);
					}
				}
			}
		}

		set_config('system', 'lastpoll', datetime_convert());

		return;
	}
}
