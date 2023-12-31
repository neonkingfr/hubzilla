<?php

namespace Zotlabs\Lib;

use Zotlabs\Web\HTTPSig;

class Zotfinger {

	static function exec($resource, $channel = null, $verify = true, $recurse = true) {

		if(! $resource) {
			return false;
		}

		$m = parse_url($resource);

		$data = json_encode([ 'zot_token' => random_string() ]);

		if($channel && $m) {

			$headers = [
				'Accept'           => 'application/x-zot+json',
				'Content-Type'     => 'application/x-zot+json',
				'X-Zot-Token'      => random_string(),
				'Digest'           => HTTPSig::generate_digest_header($data),
				'Host'             => $m['host'],
				'(request-target)' => 'post ' . get_request_string($resource)
			];
			$h = HTTPSig::create_sig($headers,$channel['channel_prvkey'],channel_url($channel),false);
		}
		else {
			$h = [ 'Accept: application/x-zot+json' ];
		}

		$result = [];

		$redirects = 0;

		$start_timestamp = microtime(true);
		$x = z_post_url($resource,$data,$redirects, [ 'headers' => $h  ] );
		logger('logger_stats_data cmd:Zotfinger' . ' start:' . $start_timestamp . ' ' . 'end:' . microtime(true) . ' meta:' . $resource . '#' . random_string(16));
		btlogger('Zotfinger');

		logger('fetch: ' . print_r($x,true), LOGGER_DATA);

        if (in_array(intval($x['return_code']), [ 404, 410 ]) && $recurse) {

            // The resource has been deleted or doesn't exist at this location.
            // Try to find another nomadic resource for this channel and return that.

            // First, see if there's a hubloc for this site. Fetch that record to
            // obtain the nomadic identity hash. Then use that to find any additional
            // nomadic locations.

            $h = Activity::get_actor_hublocs($resource, 'zot6');
            if ($h) {
                // mark this location deleted
                hubloc_delete($h[0]);
                $hubs = Activity::get_actor_hublocs($h[0]['hubloc_hash']);
                if ($hubs) {
                    foreach ($hubs as $hub) {
                        if ($hub['hubloc_id_url'] !== $resource && !$hub['hubloc_deleted']) {
                            return self::exec($hub['hubloc_id_url'], $channel, $verify);
                        }
                    }
                }
            }
        }

		if($x['success']) {
			if ($verify) {
				$result['signature'] = HTTPSig::verify($x, EMPTY_STR, 'zot6');
			}

			$result['data'] = json_decode($x['body'],true);

			if($result['data'] && is_array($result['data']) && array_key_exists('encrypted',$result['data']) && $result['data']['encrypted']) {
				$result['data'] = json_decode(Crypto::unencapsulate($result['data'],get_config('system','prvkey')),true);
			}

			logger('decrypted: ' . print_r($result,true), LOGGER_DATA);

			return $result;
		}

		return false;
	}



}
