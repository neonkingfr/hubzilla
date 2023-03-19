<?php /** @file */

namespace Zotlabs\Lib;

use Zotlabs\Zot6\Receiver;
use Zotlabs\Zot6\Zot6Handler;

class Queue {

	static function update($id, $add_priority = 0) {

		logger('queue: requeue item ' . $id,LOGGER_DEBUG);
		$x = q("select outq_created, outq_posturl from outq where outq_hash = '%s' limit 1",
			dbesc($id)
		);
		if(! $x)
			return;


		$y = q("select min(outq_created) as earliest from outq where outq_posturl = '%s'",
			dbesc($x[0]['outq_posturl'])
		);

		// look for the oldest queue entry with this destination URL. If it's older than a couple of days,
		// the destination is considered to be down and only scheduled once an hour, regardless of the
		// age of the current queue item.

		$might_be_down = false;

		if($y)
			$might_be_down = ((datetime_convert('UTC','UTC',$y[0]['earliest']) < datetime_convert('UTC','UTC','now - 2 days')) ? true : false);


		// Set all other records for this destination way into the future.
		// The queue delivers by destination. We'll keep one queue item for
		// this destination (this one) with a shorter delivery. If we succeed
		// once, we'll try to deliver everything for that destination.
		// The delivery will be set to at most once per hour, and if the
		// queue item is less than 12 hours old, we'll schedule for fifteen
		// minutes.

		q("UPDATE outq SET outq_scheduled = '%s' WHERE outq_posturl = '%s'",
			dbesc(datetime_convert('UTC','UTC','now + 5 days')),
			dbesc($x[0]['outq_posturl'])
		);

		$since = datetime_convert('UTC','UTC',$x[0]['outq_created']);

		if(($might_be_down) || ($since < datetime_convert('UTC','UTC','now - 12 hour'))) {
			$next = datetime_convert('UTC','UTC','now + 1 hour');
		}
		else {
			$next = datetime_convert('UTC','UTC','now + ' . intval($add_priority) . ' minutes');
		}

		q("UPDATE outq SET outq_updated = '%s',
			outq_priority = outq_priority + %d,
			outq_scheduled = '%s'
			WHERE outq_hash = '%s'",

			dbesc(datetime_convert()),
			intval($add_priority),
			dbesc($next),
			dbesc($id)
		);
	}

	public static function remove($id, $channel_id = 0) {
		logger('queue: remove queue item ' . $id, LOGGER_DEBUG);
		$sql_extra = (($channel_id) ? " and outq_channel = " . intval($channel_id) . " " : '');

		// figure out what endpoint it is going to.
		$record = q("select outq_posturl from outq where outq_hash = '%s' $sql_extra",
			dbesc($id)
		);

		if ($record) {
			q("DELETE FROM outq WHERE outq_hash = '%s' $sql_extra",
				dbesc($id)
			);

			// If there's anything remaining in the queue for this site, move one of them to the next active
			// queue run by setting outq_scheduled back to the present. We may be attempting to deliver it
			// as a 'piled_up' delivery, but this ensures the site has an active queue entry as long as queued
			// entries still exist for it. This fixes an issue where one immediate delivery left everything
			// else for that site undeliverable since all the other entries had been pushed far into the future.

			$x = null;
			$sql_quirks = ((get_config('system', 'db_skip_locked_supported')) ? 'SKIP LOCKED' : 'NOWAIT');

			q("START TRANSACTION");

			$r = q("SELECT outq_hash FROM outq WHERE outq_posturl = '%s' LIMIT 1 FOR UPDATE $sql_quirks",
				dbesc($record[0]['outq_posturl'])
			);

			if ($r) {
				$x = q("UPDATE outq SET outq_scheduled = '%s' WHERE outq_hash = '%s'",
					dbesc(datetime_convert()),
					dbesc($r[0]['outq_hash'])
				);
			}

			if ($x) {
				q("COMMIT");
			}
			else {
				q("ROLLBACK");
			}

		}
	}

	static function remove_by_posturl($posturl) {
		logger('queue: remove queue posturl ' . $posturl,LOGGER_DEBUG);

		q("DELETE FROM outq WHERE outq_posturl = '%s' ",
			dbesc($posturl)
		);
	}

	static function set_delivered($id,$channel = 0) {
		logger('queue: set delivered ' . $id,LOGGER_DEBUG);
		$sql_extra = (($channel['channel_id']) ? " and outq_channel = " . intval($channel['channel_id']) . " " : '');

		// Set the next scheduled run date so far in the future that it will be expired
		// long before it ever makes it back into the delivery chain.

		q("update outq set outq_delivered = 1, outq_updated = '%s', outq_scheduled = '%s' where outq_hash = '%s' $sql_extra ",
			dbesc(datetime_convert()),
			dbesc(datetime_convert('UTC','UTC','now + 5 days')),
			dbesc($id)
		);
	}



	static function insert($arr) {

		// do not queue anything with no destination

		if(! (array_key_exists('posturl',$arr) && trim($arr['posturl']))) {
			return false;
		}

		$hash = $arr['hash'] ?? '';
		$account_id = $arr['account_id'] ?? 0;
		$channel_id = $arr['channel_id'] ?? 0;
		$driver = $arr['driver'] ?? 'zot6';
		$posturl = $arr['posturl'] ?? '';
		$priority = $arr['priority'] ?? 0;
		$notify = $arr['notify'] ?? '';
		$msg = $arr['msg'] ?? '';

		$x = q("insert into outq ( outq_hash, outq_account, outq_channel, outq_driver, outq_posturl, outq_async, outq_priority,
			outq_created, outq_updated, outq_scheduled, outq_notify, outq_msg )
			values ( '%s', %d, %d, '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s' )",
			dbesc($hash),
			intval($account_id),
			intval($channel_id),
			dbesc($driver),
			dbesc($posturl),
			intval(1),
			intval($priority),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc($notify),
			dbesc($msg)
		);
		return $x;

	}



	static function deliver($outq, $immediate = false) {

		$base = null;
		$h = parse_url($outq['outq_posturl']);
		if($h !== false)
			$base = $h['scheme'] . '://' . $h['host'] . (isset($h['port']) ? ':' . $h['port'] : '');

		if(($base) && ($base !== z_root()) && ($immediate)) {
			$y = q("select site_update, site_dead from site where site_url = '%s' ",
				dbesc($base)
			);

			if ($y) {
				// Don't bother delivering if the site is dead.
				// And if we haven't heard from the site in over a month - let them through but 3 strikes you're out.
				if (intval($y[0]['site_dead']) || ($y[0]['site_update'] < datetime_convert('UTC', 'UTC', 'now - 1 month') && $outq['outq_priority'] > 20)) {
					q("update dreport set dreport_result = '%s' where dreport_queue = '%s'",
						dbesc('site dead'),
						dbesc($outq['outq_hash'])
					);
					self::remove_by_posturl($outq['outq_posturl']);
					logger('dead site ignored ' . $base);
					return;
				}
			}
			else {

				// zot sites should all have a site record, unless they've been dead for as long as
				// your site has existed. Since we don't know for sure what these sites are,
				// call them unknown

				site_store_lowlevel(
					[
						'site_url'    => $base,
						'site_update' => datetime_convert(),
						'site_dead'   => 0,
						'site_type'   => SITE_TYPE_UNKNOWN,
						'site_crypto' => ''
					]
				);
			}
		}

		$arr = array('outq' => $outq, 'base' => $base, 'handled' => false, 'immediate' => $immediate);
		call_hooks('queue_deliver', $arr);
		if($arr['handled'])
			return;

		// normal zot delivery

		logger('deliver: dest: ' . $outq['outq_posturl'], LOGGER_DEBUG);

		if($outq['outq_posturl'] === z_root() . '/zot') {
			// local delivery
			$zot = new Receiver(new Zot6Handler(), $outq['outq_notify']);
			$result = $zot->run();
			logger('returned_json: ' . json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), LOGGER_DATA);
			logger('deliver: local zot delivery succeeded to ' . $outq['outq_posturl']);
			Libzot::process_response($outq['outq_posturl'],[ 'success' => true, 'body' => json_encode($result) ], $outq);
		}
		else {
			logger('remote');
			$channel = null;

			if($outq['outq_channel']) {
				$channel = channelx_by_n($outq['outq_channel'], true);
			}

			$host_crypto = null;

			if($channel && $base) {
				$h = q("SELECT hubloc_sitekey, site_crypto FROM hubloc LEFT JOIN site ON hubloc_url = site_url
					WHERE site_url = '%s' AND hubloc_network = 'zot6' AND hubloc_deleted = 0 ORDER BY hubloc_primary DESC, hubloc_id DESC LIMIT 1",
					dbesc($base)
				);
				if($h) {
					$host_crypto = $h[0];
				}
			}

			$msg = $outq['outq_notify'];

			$result = Libzot::zot($outq['outq_posturl'], $msg, $channel, $host_crypto);

			if($result['success']) {
				logger('deliver: remote zot delivery succeeded to ' . $outq['outq_posturl']);
				Libzot::process_response($outq['outq_posturl'],$result, $outq);
			}
			else {
				logger('deliver: remote zot delivery failed to ' . $outq['outq_posturl']);
				logger('deliver: remote zot delivery fail data: ' . print_r($result,true), LOGGER_DATA);
				self::update($outq['outq_hash'], 10);
			}
		}
		return;
	}
}

