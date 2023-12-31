<?php
/**
 * @file include/event.php
 * @brief Event related functions.
 */


use Sabre\VObject;

use Zotlabs\Lib\Activity;
use Zotlabs\Lib\Libsync;
use Zotlabs\Access\AccessList;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;


require_once('include/bbcode.php');

/**
 * @brief Returns an event as HTML.
 *
 * @param array $ev
 * @return string HTML formatted event
 */
function format_event_html($ev) {


	if(! ((is_array($ev)) && count($ev)))
		return '';

	$tz = $ev['timezone'] ?? 'UTC';

	$bd_format = t('l F d, Y \@ g:i A') ; // Friday January 18, 2011 @ 8:01 AM

	/// @TODO move this to template

	$o = '<div class="vevent">' . "\r\n";

	$o .= '<div class="event-title"><h3><i class="fa fa-calendar"></i>&nbsp;' . zidify_links(smilies(bbcode($ev['summary']))) .  '</h3></div>' . "\r\n";

	$o .= '<div class="event-start"><span class="event-label">' . t('Starts:') . '</span>&nbsp;<span class="dtstart" title="'
		. datetime_convert('UTC', 'UTC', $ev['dtstart'], ((isset($ev['adjust']) && $ev['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' ))
		. '" >'
		. ((isset($ev['adjust']) && $ev['adjust']) ? day_translate(datetime_convert('UTC', date_default_timezone_get(),
			$ev['dtstart'] , $bd_format ))
			:  day_translate(datetime_convert('UTC', 'UTC',
			$ev['dtstart'] , $bd_format)))
		. '</span></div>' . "\r\n";

	if(! $ev['nofinish'])
		$o .= '<div class="event-end" ><span class="event-label">' . t('Finishes:') . '</span>&nbsp;<span class="dtend" title="'
			. datetime_convert('UTC','UTC',$ev['dtend'], ((isset($ev['adjust']) && $ev['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' ))
			. '" >'
			. ((isset($ev['adjust']) && $ev['adjust']) ? day_translate(datetime_convert('UTC', date_default_timezone_get(),
				$ev['dtend'] , $bd_format ))
				:  day_translate(datetime_convert('UTC', 'UTC',
				$ev['dtend'] , $bd_format )))
			. '</span></div>'  . "\r\n";

	$o .= '<div class="event-description">' . zidify_links(smilies(bbcode($ev['description']))) .  '</div>' . "\r\n";

	if(isset($ev['location']) && $ev['location'])
		$o .= '<div class="event-location"><span class="event-label"> ' . t('Location:') . '</span>&nbsp;<span class="location">'
			. zidify_links(smilies(bbcode($ev['location'])))
			. '</span></div>' . "\r\n";

	$o .= '</div>' . "\r\n";

	return $o;
}

function format_event_obj($jobject) {

	$event = [];
	$object = json_decode($jobject, true);

/*******
	This is our encoded format

		$x = [
			'type'      => 'Event',
			'id'        => z_root() . '/event/' . $r[0]['resource_id'],
			'summary'   => bbcode($arr['summary']),
			// RFC3339 Section 4.3
			'startTime' => (($arr['adjust']) ? datetime_convert('UTC','UTC',$arr['dtstart'], ATOM_TIME) : datetime_convert('UTC','UTC',$arr['dtstart'],'Y-m-d\\TH:i:s-00:00')),
			'content'   => bbcode($arr['description']),
			'location'  => [ 'type' => 'Place', 'content' => $arr['location'] ],
			'source'    => [ 'content' => format_event_bbcode($arr), 'mediaType' => 'text/bbcode' ],
			'url'       => [ [ 'mediaType' => 'text/calendar', 'href' => z_root() . '/events/ical/' . $event['event_hash'] ] ],
			'actor'     => Activity::encode_person($r[0],false),
		];
		if(! $arr['nofinish']) {
			$x['endTime'] = (($arr['adjust']) ? datetime_convert('UTC','UTC',$arr['dtend'], ATOM_TIME) : datetime_convert('UTC','UTC',$arr['dtend'],'Y-m-d\\TH:i:s-00:00'));
		}

******/

	if (is_array($object) && (array_key_exists('summary', $object) || array_key_exists('name', $object))) {

		$dtend = ((array_key_exists('endTime', $object)) ? $object['endTime'] : NULL_DATE);
		$title = ((isset($object['summary']) && $object['summary']) ? zidify_links(smilies(bbcode($object['summary']))) : $object['name']);

		// mobilizon sets a timezone in the object
		// we will assume that events with an timezone should be adjusted
		$tz = $object['timezone'] ?? '';

		// friendica has its own flag for adjust
		$dfrn_adjust = $object['dfrn:adjust'] ?? '';

		$adjust = ((strpos($object['startTime'], 'Z') !== false) || $tz || $dfrn_adjust);

		$allday = (($adjust) ? false : true);

		$dtstart = new DateTime($object['startTime']);
		$dtend_obj = new DateTime($dtend);

		$dtdiff = $dtstart->diff($dtend_obj);

		if($allday && ($dtdiff->days < 2))
			$oneday = true;

		if($allday && !$oneday) {
			// Subtract one day from the end date so we can use the "first day - last day" format for display.
			$dtend_obj->modify('-1 day');
			$dtend = datetime_convert('UTC', 'UTC', $dtend_obj->format('Y-m-d H:i:s'));
		}

		$bd_format = (($allday) ? t('l F d, Y') : t('l F d, Y \@ g:i A')); // Friday January 18, 2011 @ 8:01 AM or Friday January 18, 2011 for allday events

		$event['header'] = replace_macros(get_markup_template('event_item_header.tpl'), array(
			'$title'          => $title,
			'$dtstart_label' => t('Start:'),
			'$dtstart_title' => datetime_convert('UTC', 'UTC', $object['startTime'], ((strpos($object['startTime'], 'Z')) ? ATOM_TIME : 'Y-m-d\TH:i:s' )),
			'$dtstart_dt'    => (($adjust) ? day_translate(datetime_convert('UTC', date_default_timezone_get(), $object['startTime'], $bd_format)) : day_translate(datetime_convert('UTC', 'UTC', $object['startTime'], $bd_format))),
			'$finish'        => ((array_key_exists('endTime', $object)) ? true : false),
			'$dtend_label'   => t('End:'),
			'$dtend_title'   => datetime_convert('UTC', 'UTC', $dtend, ((strpos($object['startTime'], 'Z')) ? ATOM_TIME : 'Y-m-d\TH:i:s' )),
			'$dtend_dt'      => (($adjust) ? day_translate(datetime_convert('UTC', date_default_timezone_get(), $dtend, $bd_format)) :  day_translate(datetime_convert('UTC', 'UTC', $dtend, $bd_format))),
			'$allday'        => $allday,
			'$oneday'        => $oneday,
			'$event_tz'      => ['label' => t('Timezone'), 'value' => (($tz === date_default_timezone_get()) ? '' : $tz)]
		));


		$description = [];

		if (strpos($object['source']['content'], '[/event-description]') !== false) {
			preg_match("/\[event\-description\](.*?)\[\/event\-description\]/ism", $object['source']['content'], $description);
		}

		$event['content'] = replace_macros(get_markup_template('event_item_content.tpl'), array(
			'$description'    => ((isset($description[1]))? zidify_links(smilies(bbcode($description[1]))) : EMPTY_STR),
			'$location_label' => t('Location:'),
			'$location'   => ((array_path_exists('location/name', $object)) ? zidify_links(smilies(bbcode($object['location']['name']))) : EMPTY_STR)
		));
	}

    return $event;
/*
	$event = [];
	$object = json_decode($jobject,true);

	$event_tz = '';
	if($object['adjust'] && is_array($object['asld']) && is_array($object['asld']['attachment'])) {
		foreach($object['asld']['attachment'] as $attachment) {
			if($attachment['type'] === 'PropertyValue' && $attachment['name'] == 'zot.event.timezone' ) {
				// check if the offset of the timezones is different and only set event_tz if offset is not the same
				$local_tz = new DateTimeZone(date_default_timezone_get());
				$local_dt = new DateTime('now', $local_tz);

				$ev_tz = new DateTimeZone($attachment['value']);
				$ev_dt = new DateTime('now', $ev_tz);
				if($local_dt->getOffset() !== $ev_dt->getOffset())
					$event_tz = $attachment['value'];
				break;
			}
		}

	}

	$allday = (($object['adjust']) ? false : true);

	$dtstart = new DateTime($object['dtstart']);
	$dtend = new DateTime($object['dtend']);
	$dtdiff = $dtstart->diff($dtend);

	if($allday && ($dtdiff->days < 2))
		$oneday = true;

	if($allday && !$oneday) {
		// Subtract one day from the end date so we can use the "first day - last day" format for display.
		$dtend->modify('-1 day');
		$object['dtend'] = datetime_convert('UTC', 'UTC', $dtend->format('Y-m-d H:i:s'));
	}

	$bd_format = (($allday) ? t('l F d, Y') : t('l F d, Y \@ g:i A')); // Friday January 18, 2011 @ 8:01 AM or Friday January 18, 2011 for allday events

	$event['header'] = replace_macros(get_markup_template('event_item_header.tpl'),array(
		'$title'	 => zidify_links(smilies(bbcode($object['title']))),
		'$dtstart_label' => t('Start:'),
		'$dtstart_title' => datetime_convert('UTC', date_default_timezone_get(), $object['dtstart'], (($object['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' )),
		'$dtstart_dt'	 => (($object['adjust']) ? day_translate(datetime_convert('UTC', date_default_timezone_get(), $object['dtstart'] , $bd_format )) : day_translate(datetime_convert('UTC', 'UTC', $object['dtstart'] , $bd_format))),
		'$finish'	 => (($object['nofinish']) ? false : true),
		'$dtend_label'	 => t('End:'),
		'$dtend_title'	 => datetime_convert('UTC', date_default_timezone_get(), $object['dtend'], (($object['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' )),
		'$dtend_dt'	 => (($object['adjust']) ? day_translate(datetime_convert('UTC', date_default_timezone_get(), $object['dtend'] , $bd_format )) :  day_translate(datetime_convert('UTC', 'UTC', $object['dtend'] , $bd_format ))),
		'$allday'	 => $allday,
		'$oneday'	 => $oneday,
		'$event_tz'      => ['label' => t('Timezone'), 'value' => (($event_tz === date_default_timezone_get()) ? '' : $event_tz)]
	));

	$event_src = [];

	if(array_path_exists('asld/source', $object) && $object['asld']['source']['mediaType'] === 'text/bbcode') {
		$event_src = bbtoevent($object['asld']['source']['content']);
	}

	$event['content'] = replace_macros(get_markup_template('event_item_content.tpl'),array(
		'$description'	  => zidify_links(smilies(bbcode($event_src ? $event_src['description'] : $object['description']))),
		'$location_label' => t('Location:'),
		'$location'	  => zidify_links(smilies(bbcode($event_src ? $event_src['location'] : $object['location']))),
	));

	return $event;
*/
}

function ical_wrapper($ev) {

	if(! ((is_array($ev)) && count($ev)))
		return '';

	$o .= "BEGIN:VCALENDAR";
	$o .= "\r\nVERSION:2.0";
	$o .= "\r\nMETHOD:PUBLISH";
	$o .= "\r\nPRODID:-//" . get_config('system','sitename') . "//" . Zotlabs\Lib\System::get_platform_name() . "//" . strtoupper(App::$language). "\r\n";
	if(array_key_exists('dtstart', $ev))
		$o .= format_event_ical($ev);
	else {
		foreach($ev as $e) {
			$o .= format_event_ical($e);
		}
	}
	$o .= "\r\nEND:VCALENDAR\r\n";

	return $o;
}

function format_event_ical($ev) {

	if($ev['etype'] === 'task')
		return format_todo_ical($ev);

	$tz = get_iconfig($ev['item_id'], 'event', 'timezone');
	if(! $tz)
		$tz = 'UTC';

	$tzid = ';TZID=' . $tz;

	$o = '';

	$o .= "\r\nBEGIN:VEVENT";

	$o .= "\r\nCREATED:" . datetime_convert('UTC','UTC', $ev['created'],'Ymd\\THis\\Z');
	$o .= "\r\nLAST-MODIFIED:" . datetime_convert('UTC','UTC', $ev['edited'],'Ymd\\THis\\Z');
	$o .= "\r\nDTSTAMP:" . datetime_convert('UTC','UTC', $ev['edited'],'Ymd\\THis\\Z');

	if($ev['adjust']) {
		if($ev['dtstart'])
			$o .= "\r\nDTSTART$tzid:" . datetime_convert($tz,'UTC', $ev['dtstart'],'Ymd\\THis\\Z');
		if($ev['dtend'] && ! $ev['nofinish'])
			$o .= "\r\nDTEND$tzid:" . datetime_convert($tz,'UTC', $ev['dtend'],'Ymd\\THis\\Z');
	}
	else {
		if($ev['dtstart'])
			$o .= "\r\nDTSTART;VALUE=DATE:" . datetime_convert('UTC','UTC', $ev['dtstart'],'Ymd');
		if($ev['dtend'] && ! $ev['nofinish'])
			$o .= "\r\nDTEND;VALUE=DATE:" . datetime_convert('UTC','UTC', $ev['dtend'],'Ymd');
	}
	if($ev['summary']) {
		$o .= "\r\nSUMMARY:" . format_ical_text($ev['summary']);
		$o .= "\r\nX-ZOT-SUMMARY:" . format_ical_sourcetext($ev['summary']);
	}
	if($ev['location']) {
		$o .= "\r\nLOCATION:" . format_ical_text($ev['location']);
		$o .= "\r\nX-ZOT-LOCATION:" . format_ical_sourcetext($ev['location']);
	}
	if($ev['description']) {
		$o .= "\r\nDESCRIPTION:" . format_ical_text($ev['description']);
		$o .= "\r\nX-ZOT-DESCRIPTION:" . format_ical_sourcetext($ev['description']);
	}
	if($ev['event_priority'])
		$o .= "\r\nPRIORITY:" . intval($ev['event_priority']);
	$o .= "\r\nUID:" . $ev['event_hash'] ;
	$o .= "\r\nEND:VEVENT\r\n";

	return $o;
}


function format_todo_ical($ev) {

	$o = '';

	$o .= "\r\nBEGIN:VTODO";
	$o .= "\r\nCREATED:" . datetime_convert('UTC','UTC', $ev['created'],'Ymd\\THis\\Z');
	$o .= "\r\nLAST-MODIFIED:" . datetime_convert('UTC','UTC', $ev['edited'],'Ymd\\THis\\Z');
	$o .= "\r\nDTSTAMP:" . datetime_convert('UTC','UTC', $ev['edited'],'Ymd\\THis\\Z');
	if($ev['dtstart'])
		$o .= "\r\nDTSTART:" . datetime_convert('UTC','UTC', $ev['dtstart'],'Ymd\\THis' . (($ev['adjust']) ? '\\Z' : ''));
	if($ev['dtend'] && ! $ev['nofinish'])
		$o .= "\r\nDUE:" . datetime_convert('UTC','UTC', $ev['dtend'],'Ymd\\THis' . (($ev['adjust']) ? '\\Z' : ''));
	if($ev['summary']) {
		$o .= "\r\nSUMMARY:" . format_ical_text($ev['summary']);
		$o .= "\r\nX-ZOT-SUMMARY:" . format_ical_sourcetext($ev['summary']);
	}
	if($ev['event_status']) {
		$o .= "\r\nSTATUS:" . $ev['event_status'];
		if($ev['event_status'] === 'COMPLETED')
			$o .= "\r\nCOMPLETED:" . datetime_convert('UTC','UTC', $ev['event_status_date'],'Ymd\\THis\\Z');
	}
	if(intval($ev['event_percent']))
		$o .= "\r\nPERCENT-COMPLETE:" . $ev['event_percent'];
	if(intval($ev['event_sequence']))
		$o .= "\r\nSEQUENCE:" . $ev['event_sequence'];
	if($ev['location']) {
		$o .= "\r\nLOCATION:" . format_ical_text($ev['location']);
		$o .= "\r\nX-ZOT-LOCATION:" . format_ical_sourcetext($ev['location']);
	}
	if($ev['description']) {
		$o .= "\r\nDESCRIPTION:" . format_ical_text($ev['description']);
		$o .= "\r\nX-ZOT-DESCRIPTION:" . format_ical_sourcetext($ev['description']);
	}
	$o .= "\r\nUID:" . $ev['event_hash'] ;
	if($ev['event_priority'])
		$o .= "\r\nPRIORITY:" . intval($ev['event_priority']);
	$o .= "\r\nEND:VTODO\r\n";

	return $o;
}


function format_ical_text($s) {

	require_once('include/html2plain.php');

	$s = html2plain(bbcode($s));
	$s = str_replace(["\r\n","\n"],["",""],$s);

	return(wordwrap(str_replace(['\\',',',';'],['\\\\','\\,','\\;'],$s),72,"\r\n ",true));
}

function format_ical_sourcetext($s) {
	$s = base64_encode($s);

	return(wordwrap(str_replace(['\\',',',';'],['\\\\','\\,','\\;'],$s),72,"\r\n ",true));
}


function format_event_bbcode($ev, $utc = false) {

	$o = '';

	if($ev['event_vdata']) {
		$o .= '[event]' . $ev['event_vdata'] . '[/event]';
	}
/*
	if ($utc && $ev['event-timezone'] !== 'UTC') {
		$ev['dtstart'] = datetime_convert($ev['timezone'],'UTC',$ev['dtstart']);
		if ($ev['dtend'] && ! $ev['nofinish']) {
			$ev['dtend'] = datetime_convert($ev['timezone'],'UTC',$ev['dtend']);
		}
		$ev['timezone'] = 'UTC';
	}
*/
	if($ev['summary'])
		$o .= '[event-summary]' . $ev['summary'] . '[/event-summary]';

	if($ev['description'])
		$o .= '[event-description]' . $ev['description'] . '[/event-description]';

	if($ev['dtstart'])
		$o .= '[event-start]' . $ev['dtstart'] . '[/event-start]';

	if(($ev['dtend']) && (! $ev['nofinish']))
		$o .= '[event-finish]' . $ev['dtend'] . '[/event-finish]';

	if($ev['location'])
		$o .= '[event-location]' . $ev['location'] . '[/event-location]';

	if($ev['event_hash'])
		$o .= '[event-id]' . $ev['event_hash'] . '[/event-id]';

//	if($ev['timezone'])
//		$o .= '[event-timezone]' . $ev['timezone'] . '[/event-timezone]';

	if($ev['adjust'])
		$o .= '[event-adjust]' . $ev['adjust'] . '[/event-adjust]';

	return $o;
}


function bbtovcal($s) {
	$o = '';
	$ev = bbtoevent($s);
	if($ev['description'])
		$o = format_event_html($ev);

	return $o;
}


function bbtoevent($s) {

	$ev = array();

	$match = '';
	if(preg_match("/\[event\](.*?)\[\/event\]/is",$s,$match)) {
		// only parse one object per event tag
		$x = ical_to_ev($match[1]);
		if($x)
			$ev = $x[0];
	}

	$match = '';
	if(preg_match("/\[event\-summary\](.*?)\[\/event\-summary\]/is",$s,$match))
		$ev['summary'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-description\](.*?)\[\/event\-description\]/is",$s,$match))
		$ev['description'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-start\](.*?)\[\/event\-start\]/is",$s,$match))
		$ev['dtstart'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-finish\](.*?)\[\/event\-finish\]/is",$s,$match))
		$ev['dtend'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-location\](.*?)\[\/event\-location\]/is",$s,$match))
		$ev['location'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-id\](.*?)\[\/event\-id\]/is",$s,$match))
		$ev['event_hash'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-timezone\](.*?)\[\/event\-timezone\]/is",$s,$match))
		$ev['timezone'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-adjust\](.*?)\[\/event\-adjust\]/is",$s,$match))
		$ev['adjust'] = $match[1];
	if(array_key_exists('dtstart',$ev)) {
		if(array_key_exists('dtend',$ev)) {
			if($ev['dtend'] === $ev['dtstart'])
				$ev['nofinish'] = 1;
			elseif($ev['dtend'])
				$ev['nofinish'] = 0;
			else
				$ev['nofinish'] = 1;
		}
		else
			$ev['nofinish'] = 1;
	}

//	logger('bbtoevent: ' . print_r($ev,true));

	return $ev;
}

/**
 * @brief Sorts the given array of events by date.
 *
 * @see ev_compare()
 * @param array $arr
 * @return array Date sorted array of events
 */
function sort_by_date($arr) {
	if (is_array($arr))
		usort($arr, 'ev_compare');

	return $arr;
}

/**
 * @brief Compare function for events.
 *
 * This function can be used in usort() to sort events by date.
 *
 * @see sort_by_date()
 * @param array $a
 * @param array $b
 * @return number return values like strcmp()
 */
function ev_compare($a, $b) {

	$date_a = (($a['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$a['dtstart']) : $a['dtstart']);
	$date_b = (($b['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$b['dtstart']) : $b['dtstart']);

	if ($date_a === $date_b)
		return strcasecmp($a['description'], $b['description']);

	return strcmp($date_a, $date_b);
}


function event_store_event($arr) {
	$arr['created']        = $arr['created'] ?? datetime_convert();
	$arr['edited']         = $arr['edited'] ?? datetime_convert();
	$arr['etype']          = $arr['etype'] ?? 'event';
	$arr['event_xchan']    = $arr['event_xchan'] ?? '';
	$arr['event_priority'] = $arr['event_priority'] ?? 0;
	$arr['location']       = $arr['location'] ?? '';
	$arr['nofinish']       = $arr['nofinish'] ?? 0;
	$arr['event_status']   = $arr['event_status'] ?? '';
	$arr['event_percent']  = $arr['event_percent'] ?? 0;
	$arr['event_repeat']   = $arr['event_repeat'] ?? '';
	$arr['event_sequence'] = $arr['event_sequence'] ?? 0;
	$arr['event_vdata']    = $arr['event_vdata'] ?? '';
	$arr['allow_cid']      = $arr['allow_cid'] ?? '';
	$arr['allow_gid']      = $arr['allow_gid'] ?? '';
	$arr['deny_cid']       = $arr['deny_cid'] ?? '';
	$arr['deny_gid']       = $arr['deny_gid'] ?? '';

	if (! $arr['dtend']) {
		$arr['dtend'] = NULL_DATE;
		$arr['nofinish'] = 1;
	}

	if(array_key_exists('event_status_date',$arr))
		$arr['event_status_date'] = datetime_convert('UTC','UTC', $arr['event_status_date']);
	else
		$arr['event_status_date'] = NULL_DATE;


	$existing_event = null;

	if(isset($arr['event_hash']) && $arr['event_hash']) {
		$r = q("SELECT * FROM event WHERE event_hash = '%s' AND uid = %d LIMIT 1",
			dbesc($arr['event_hash']),
			intval($arr['uid'])
		);
		if($r) {
			$existing_event = $r[0];
		}
	}

	if(isset($arr['id']) && $arr['id']) {
		$r = q("SELECT * FROM event WHERE id = %d AND uid = %d LIMIT 1",
			intval($arr['id']),
			intval($arr['uid'])
		);
		if($r) {
			$existing_event = $r[0];
		}
		else {
			return false;
		}
	}

	$hook_info = [
			'event' => $arr,
			'existing_event' => $existing_event,
			'cancel' => false
	];
	/**
	 * @hooks event_store_event
	 *   Called when an event record is created or updated.
	 *   * \e array \b event
	 *   * \e array \b existing_event
	 *   * \e boolean \b cancel - default false
	 */
	call_hooks('event_store_event', $hook_info);
	if($hook_info['cancel'])
		return false;

	$arr = $hook_info['event'];
	$existing_event = $hook_info['existing_event'];

	if($existing_event) {

		if($existing_event['edited'] >= $arr['edited']) {
			// Nothing has changed.
			return $existing_event;
		}

		$hash = $existing_event['event_hash'];

		// The event changed. Update it.

		$r = q("UPDATE event SET
			edited = '%s',
			dtstart = '%s',
			dtend = '%s',
			summary = '%s',
			description = '%s',
			location = '%s',
			etype = '%s',
			adjust = %d,
			nofinish = %d,
			event_status = '%s',
			event_status_date = '%s',
			event_percent = %d,
			event_repeat = '%s',
			event_sequence = %d,
			event_priority = %d,
			event_vdata = '%s',
			allow_cid = '%s',
			allow_gid = '%s',
			deny_cid = '%s',
			deny_gid = '%s'
			WHERE id = %d AND uid = %d",

			dbesc(datetime_convert('UTC','UTC',$arr['edited'])),
			dbesc(datetime_convert('UTC','UTC',$arr['dtstart'])),
			dbesc(datetime_convert('UTC','UTC',$arr['dtend'])),
			dbesc($arr['summary']),
			dbesc($arr['description']),
			dbesc($arr['location']),
			dbesc($arr['etype']),
			intval($arr['adjust']),
			intval($arr['nofinish']),
			dbesc($arr['event_status']),
			dbesc(datetime_convert('UTC','UTC',$arr['event_status_date'])),
			intval($arr['event_percent']),
			dbesc($arr['event_repeat']),
			intval($arr['event_sequence']),
			intval($arr['event_priority']),
			dbesc($arr['event_vdata']),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid']),
			intval($existing_event['id']),
			intval($arr['uid'])
		);
	} else {

		// New event. Store it.

		if(array_key_exists('external_id',$arr))
			$hash = $arr['external_id'];
		elseif(array_key_exists('event_hash',$arr))
			$hash = $arr['event_hash'];
		else {
			try {
				$hash = Uuid::uuid4()->toString();
			} catch (UnsatisfiedDependencyException $e) {
				$hash = random_string(48);
			}
		}
		$r = q("INSERT INTO event ( uid,aid,event_xchan,event_hash,created,edited,dtstart,dtend,summary,description,location,etype,
			adjust,nofinish, event_status, event_status_date, event_percent, event_repeat, event_sequence, event_priority, event_vdata, allow_cid,allow_gid,deny_cid,deny_gid)
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', %d, '%s', %d, %d, '%s', '%s', '%s', '%s', '%s' ) ",
			intval($arr['uid']),
			intval($arr['account']),
			dbesc($arr['event_xchan']),
			dbesc($hash),
			dbesc(datetime_convert('UTC','UTC',$arr['created'])),
			dbesc(datetime_convert('UTC','UTC',$arr['edited'])),
			dbesc(datetime_convert('UTC','UTC',$arr['dtstart'])),
			dbesc(datetime_convert('UTC','UTC',$arr['dtend'])),
			dbesc($arr['summary']),
			dbesc($arr['description']),
			dbesc($arr['location']),
			dbesc($arr['etype']),
			intval($arr['adjust']),
			intval($arr['nofinish']),
			dbesc($arr['event_status']),
			dbesc(datetime_convert('UTC','UTC',$arr['event_status_date'])),
			intval($arr['event_percent']),
			dbesc($arr['event_repeat']),
			intval($arr['event_sequence']),
			intval($arr['event_priority']),
			dbesc($arr['event_vdata']),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid'])
		);
	}

	$r = q("SELECT * FROM event WHERE event_hash = '%s' AND uid = %d LIMIT 1",
		dbesc($hash),
		intval($arr['uid'])
	);
	if($r) {

		/**
		 * @hooks event_store_event_end
		 *   Called after an event record was stored.
		 *   * \e array \b event
		 */
		call_hooks('event_store_event_end', $r[0]);

		return $r[0];

	}

	return false;
}

function event_addtocal($item_id, $uid) {

	$c = q("select * from channel where channel_id = %d limit 1",
		intval($uid)
	);

	if(! $c)
		return false;

	$channel = $c[0];

	$r = q("select * from item where id = %d and uid = %d limit 1",
		intval($item_id),
		intval($channel['channel_id'])
	);

	if(!$r || !in_array($r[0]['obj_type'], ['Event', ACTIVITY_OBJ_EVENT]))
		return false;

	$item = $r[0];

	$ev = parse_event_object($item['obj']);

	// if we could not parse the object, parse try to parse the body bbcode
	if (!$ev) {
		$ev = bbtoevent($item['body']);
	}

	if (!$ev) {
		return false;
	}

	$ev['event_xchan'] = $item['author_xchan'];
	$ev['uid']         = $channel['channel_id'];
	$ev['account']     = $channel['channel_account_id'];
	$ev['edited']      = $item['edited'];
	$ev['mid']         = $item['mid'];
	$ev['private']     = $item['item_private'];

	if($item['resource_type'] === 'event' && (! $ev['event_hash'])) {
		$ev['event_hash'] = $item['resource_id'];
	}

	if($ev['private']) {
		$ev['allow_cid'] = '<' . $channel['channel_hash'] . '>';
		$ev['allow_gid'] = '';
		$ev['deny_cid']  = '';
		$ev['deny_gid']  = '';
	}
	else {
		$acl = new AccessList($channel);
		$x = $acl->get();
		$ev['allow_cid'] = $x['allow_cid'];
		$ev['allow_gid'] = $x['allow_gid'];
		$ev['deny_cid']  = $x['deny_cid'];
		$ev['deny_gid']  = $x['deny_gid'];
	}

	$event = event_store_event($ev);
	if($event) {
		$r = q("update item set resource_id = '%s', resource_type = 'event' where id = %d and uid = %d",
			dbesc($event['event_hash']),
			intval($item['id']),
			intval($channel['channel_id'])
		);

		$item['resource_id'] = $event['event_hash'];
		$item['resource_type'] = 'event';

		$i = [$item];

		xchan_query($i);
		$sync_item = fetch_post_tags($i);

		$z = q("select * from event where event_hash = '%s' and uid = %d limit 1",
			dbesc($event['event_hash']),
			intval($channel['channel_id'])
		);

		if($z) {
			libsync::build_sync_packet($channel['channel_id'], ['event_item' => [encode_item($sync_item[0], true)], 'event' => $z]);
		}

		return true;
	}

}

function parse_event_object($event_object_json) {

	$object = json_decode($event_object_json, true);

	$tz = $object['timezone'] ?? 'UTC';

	$ev['summary'] = $object['summary'] ?? $object['name'] ?? '';
	$ev['description'] = html2bbcode($content['content']) ?? '';
	$ev['dtstart'] = $object['startTime'] ? datetime_convert('UTC', 'UTC', $object['startTime']) : '';
	$ev['dtend'] = $object['endTime'] ? datetime_convert('UTC', 'UTC', $object['endTime']) : $ev['dtstart'];
	$ev['location'] = $object['location']['name'] ?? '';
	$ev['event_hash'] = $object['uuid'] ?? $object['diaspora:guid'] ?? uuid_from_url($object['id']);
	$ev['timezone'] = $tz;
	$ev['adjust'] = (strpos($object['startTime'], 'Z') !== false || !empty($object['dfrn:adjust']) || $tz !== 'UTC');

	$ev['nofinish'] = 0;
	if($ev['dtend'] === $ev['dtstart']) {
		$ev['nofinish'] = 1;
	}

	if ($ev['summary'] && $ev['dtstart']) {
		return $ev;
	}

	return false;
}


function ical_to_ev($s) {
	require_once('vendor/autoload.php');

	$saved_timezone = date_default_timezone_get();
	date_default_timezone_set('Australia/Sydney');

	$ical = VObject\Reader::read($s);

	$ev = [];

	if($ical) {
		if($ical->VEVENT) {
			foreach($ical->VEVENT as $event) {
				$ev[] = parse_vobject($event,'event');
			}
		}
		if($ical->VTODO) {
			foreach($ical->VTODO as $event) {
				$ev[] = parse_vobject($event,'task');
			}
		}
	}

	date_default_timezone_set($saved_timezone);

	return $ev;
}



function parse_vobject($ical, $type) {

	$ev = [];

	if(! isset($ical->DTSTART)) {
		logger('no event start');
		return $ev;
	}

	$ev['etype'] = $type;

	$dtstart = $ical->DTSTART->getDateTime();
	$ev['adjust'] = (($ical->DTSTART->isFloating()) ? 0 : 1);

	$ev['dtstart'] = datetime_convert((($ev['adjust']) ? 'UTC' : date_default_timezone_get()),'UTC',
		$dtstart->format(\DateTime::W3C));


	if(isset($ical->DUE)) {
		$dtend = $ical->DUE->getDateTime();
		$ev['dtend'] = datetime_convert((($ev['adjust']) ? 'UTC' : date_default_timezone_get()),'UTC',
			$dtend->format(\DateTime::W3C));
	}
	elseif(isset($ical->DTEND)) {
		$dtend = $ical->DTEND->getDateTime();
		$ev['dtend'] = datetime_convert((($ev['adjust']) ? 'UTC' : date_default_timezone_get()),'UTC',
			$dtend->format(\DateTime::W3C));
	}
	else
		$ev['nofinish'] = 1;


	if($ev['dtstart'] === $ev['dtend'])
		$ev['nofinish'] = 1;

	if(isset($ical->CREATED)) {
		$created = $ical->CREATED->getDateTime();
		$ev['created'] = datetime_convert('UTC','UTC',$created->format(\DateTime::W3C));
	}

	if(isset($ical->{'DTSTAMP'})) {
		$edited = $ical->{'DTSTAMP'}->getDateTime();
		$ev['edited'] = datetime_convert('UTC','UTC',$edited->format(\DateTime::W3C));
	}
	if(isset($ical->{'LAST-MODIFIED'})) {
		$edited = $ical->{'LAST-MODIFIED'}->getDateTime();
		$ev['edited'] = datetime_convert('UTC','UTC',$edited->format(\DateTime::W3C));
	}

	if(isset($ical->{'X-ZOT-LOCATION'}))
		$ev['location'] = event_ical_get_sourcetext( (string) $ical->{'X-ZOT-LOCATION'});
	elseif(isset($ical->LOCATION))
		$ev['location'] = (string) $ical->LOCATION;

	if(isset($ical->{'X-ZOT-DESCRIPTION'}))
		$ev['description'] = event_ical_get_sourcetext( (string) $ical->{'X-ZOT-DESCRIPTION'});
	elseif(isset($ical->DESCRIPTION))
		$ev['description'] = (string) $ical->DESCRIPTION;

	if(isset($ical->{'X-ZOT-SUMMARY'}))
		$ev['summary'] = event_ical_get_sourcetext( (string) $ical->{'X-ZOT-SUMMARY'});
	elseif(isset($ical->SUMMARY))
		$ev['summary'] = (string) $ical->SUMMARY;

	if(isset($ical->PRIORITY))
		$ev['event_priority'] = intval((string) $ical->PRIORITY);

	if(isset($ical->UID)) {
		$evuid = (string) $ical->UID;
		$ev['event_hash'] = $evuid;
	}

	if(isset($ical->SEQUENCE)) {
		$ev['event_sequence'] = (string) $ical->SEQUENCE;
	}

	if(isset($ical->STATUS)) {
		$ev['event_status'] = (string) $ical->STATUS;
	}

	if(isset($ical->{'COMPLETED'})) {
		$completed = $ical->{'COMPLETED'}->getDateTime();
		$ev['event_status_date'] = datetime_convert('UTC','UTC',$completed->format(\DateTime::W3C));
	}

	if(isset($ical->{'PERCENT-COMPLETE'})) {
		$ev['event_percent'] = (string) $ical->{'PERCENT-COMPLETE'} ;
	}

	$ev['event_vdata'] = $ical->serialize();

	return $ev;
}



function parse_ical_file($f,$uid) {

	$s = @file_get_contents($f);

	$ical = VObject\Reader::read($s);

	if($ical) {
		if($ical->VEVENT) {
			foreach($ical->VEVENT as $event) {
				event_import_ical($event,$uid);
			}
		}
		if($ical->VTODO) {
			foreach($ical->VTODO as $event) {
				event_import_ical_task($event,$uid);
			}
		}
	}

	if($ical)
		return true;

	return false;
}



function event_import_ical($ical, $uid) {

	$c = q("select * from channel where channel_id = %d limit 1",
		intval($uid)
	);

	if(! $c)
		return false;

	$channel = $c[0];
	$ev = array();


	if(! isset($ical->DTSTART)) {
		logger('no event start');
		return false;
	}

	$dtstart = $ical->DTSTART->getDateTime();
	$ev['adjust'] = (($ical->DTSTART->isFloating()) ? 0 : 1);

//	logger('dtstart: ' . var_export($dtstart,true));

	$ev['timezone'] = 'UTC';

	// Try to get an usable olson format timezone
	if($ev['adjust']) {
		//TODO: we should pass the vcalendar to getTimeZone() to be more accurate
		// we do not have it here since parse_ical_file() is passing the vevent only.
		$timezone_obj = \Sabre\VObject\TimeZoneUtil::getTimeZone($ical->DTSTART['TZID']);
		$timezone = $timezone_obj->getName();
		$ev['timezone'] = (($timezone) ? $timezone : date_default_timezone_get());
	}

	$ev['dtstart'] = datetime_convert((($ev['adjust']) ? 'UTC' : $ev['timezone']), 'UTC',
		$dtstart->format(\DateTime::W3C));

	if(isset($ical->DTEND)) {
		$dtend = $ical->DTEND->getDateTime();
		$ev['dtend'] = datetime_convert((($ev['adjust']) ? 'UTC' : $ev['timezone']), 'UTC',
			$dtend->format(\DateTime::W3C));
	}
	else {
		$ev['nofinish'] = 1;
	}

	if($ev['dtstart'] === $ev['dtend'])
		$ev['nofinish'] = 1;

	if(isset($ical->CREATED)) {
		$created = $ical->CREATED->getDateTime();
		$ev['created'] = datetime_convert('UTC','UTC',$created->format(\DateTime::W3C));
	}

	if(isset($ical->{'LAST-MODIFIED'})) {
		$edited = $ical->{'LAST-MODIFIED'}->getDateTime();
		$ev['edited'] = datetime_convert('UTC','UTC',$edited->format(\DateTime::W3C));
	}

	if(isset($ical->{'X-ZOT-LOCATION'}))
		$ev['location'] = event_ical_get_sourcetext( (string) $ical->{'X-ZOT-LOCATION'});
	elseif(isset($ical->LOCATION))
		$ev['location'] = (string) $ical->LOCATION;

	if(isset($ical->{'X-ZOT-DESCRIPTION'}))
		$ev['description'] = event_ical_get_sourcetext( (string) $ical->{'X-ZOT-DESCRIPTION'});
	elseif(isset($ical->DESCRIPTION))
		$ev['description'] = (string) $ical->DESCRIPTION;

	if(isset($ical->{'X-ZOT-SUMMARY'}))
		$ev['summary'] = event_ical_get_sourcetext( (string) $ical->{'X-ZOT-SUMMARY'});
	elseif(isset($ical->SUMMARY))
		$ev['summary'] = (string) $ical->SUMMARY;

	if(isset($ical->PRIORITY))
		$ev['event_priority'] = intval((string) $ical->PRIORITY);

	if(isset($ical->UID)) {
		$evuid = (string) $ical->UID;
		$r = q("SELECT * FROM event WHERE event_hash = '%s' AND uid = %d LIMIT 1",
			dbesc($evuid),
			intval($uid)
		);
		if($r)
			$ev['event_hash'] = $evuid;
		else
			$ev['external_id'] = $evuid;
	}

	if($ev['summary'] && $ev['dtstart']) {
		$ev['event_xchan'] = $channel['channel_hash'];
		$ev['uid']         = $channel['channel_id'];
		$ev['account']     = $channel['channel_account_id'];
		$ev['private']     = 1;
		$ev['allow_cid']   = '<' . $channel['channel_hash'] . '>';

		logger('storing event: ' . print_r($ev,true), LOGGER_ALL);
		$event = event_store_event($ev);
		if($event) {
			$item_id = event_store_item($ev,$event);
			return true;
		}
	}

	return false;
}

function event_ical_get_sourcetext($s) {
	return base64_decode($s);
}

function event_import_ical_task($ical, $uid) {

	$c = q("select * from channel where channel_id = %d limit 1",
		intval($uid)
	);

	if(! $c)
		return false;

	$channel = $c[0];
	$ev = array();


	if(! isset($ical->DTSTART)) {
		logger('no event start');
		return false;
	}

	$dtstart = $ical->DTSTART->getDateTime();

	$ev['adjust'] = (($ical->DTSTART->isFloating()) ? 0 : 1);

//	logger('dtstart: ' . var_export($dtstart,true));

	$ev['dtstart'] = datetime_convert((($ev['adjust']) ? 'UTC' : date_default_timezone_get()),'UTC',
		$dtstart->format(\DateTime::W3C));


	if(isset($ical->DUE)) {
		$dtend = $ical->DUE->getDateTime();
		$ev['dtend'] = datetime_convert((($ev['adjust']) ? 'UTC' : date_default_timezone_get()),'UTC',
			$dtend->format(\DateTime::W3C));
	}
	else
		$ev['nofinish'] = 1;


	if($ev['dtstart'] === $ev['dtend'])
		$ev['nofinish'] = 1;

	if(isset($ical->CREATED)) {
		$created = $ical->CREATED->getDateTime();
		$ev['created'] = datetime_convert('UTC','UTC',$created->format(\DateTime::W3C));
	}

	if(isset($ical->{'DTSTAMP'})) {
		$edited = $ical->{'DTSTAMP'}->getDateTime();
		$ev['edited'] = datetime_convert('UTC','UTC',$edited->format(\DateTime::W3C));
	}

	if(isset($ical->{'LAST-MODIFIED'})) {
		$edited = $ical->{'LAST-MODIFIED'}->getDateTime();
		$ev['edited'] = datetime_convert('UTC','UTC',$edited->format(\DateTime::W3C));
	}

	if(isset($ical->{'X-ZOT-LOCATION'}))
		$ev['location'] = event_ical_get_sourcetext( (string) $ical->{'X-ZOT-LOCATION'});
	elseif(isset($ical->LOCATION))
		$ev['location'] = (string) $ical->LOCATION;

	if(isset($ical->{'X-ZOT-DESCRIPTION'}))
		$ev['description'] = event_ical_get_sourcetext( (string) $ical->{'X-ZOT-DESCRIPTION'});
	elseif(isset($ical->DESCRIPTION))
		$ev['description'] = (string) $ical->DESCRIPTION;

	if(isset($ical->{'X-ZOT-SUMMARY'}))
		$ev['summary'] = event_ical_get_sourcetext( (string) $ical->{'X-ZOT-SUMMARY'});
	elseif(isset($ical->SUMMARY))
		$ev['summary'] = (string) $ical->SUMMARY;

	if(isset($ical->PRIORITY))
		$ev['event_priority'] = intval((string) $ical->PRIORITY);

	$stored_event = null;

	if(isset($ical->UID)) {
		$evuid = (string) $ical->UID;
		$r = q("SELECT * FROM event WHERE event_hash = '%s' AND uid = %d LIMIT 1",
			dbesc($evuid),
			intval($uid)
		);
		if($r) {
			$ev['event_hash'] = $evuid;
			$stored_event = $r[0];
		}
		else {
			$ev['external_id'] = $evuid;
		}
	}

	if(isset($ical->SEQUENCE)) {
		$ev['event_sequence'] = (string) $ical->SEQUENCE;
		// see if our stored event is more current than the one we're importing
		if((intval($ev['event_sequence']) <= intval($stored_event['event_sequence']))
			&& ($ev['edited'] <= $stored_event['edited']))
			return false;
	}

	if(isset($ical->STATUS)) {
		$ev['event_status'] = (string) $ical->STATUS;
	}

	if(isset($ical->{'COMPLETED'})) {
		$completed = $ical->{'COMPLETED'}->getDateTime();
		$ev['event_status_date'] = datetime_convert('UTC','UTC',$completed->format(\DateTime::W3C));
	}

	if(isset($ical->{'PERCENT-COMPLETE'})) {
		$ev['event_percent'] = (string) $ical->{'PERCENT-COMPLETE'} ;
	}

	$ev['etype'] = 'task';

	if($ev['summary'] && $ev['dtstart']) {
		$ev['event_xchan'] = $channel['channel_hash'];
		$ev['uid']         = $channel['channel_id'];
		$ev['account']     = $channel['channel_account_id'];
		$ev['private']     = 1;
		$ev['allow_cid']   = '<' . $channel['channel_hash'] . '>';

		logger('storing event: ' . print_r($ev,true), LOGGER_ALL);
		$event = event_store_event($ev);
		if($event) {
			$item_id = event_store_item($ev,$event);
			return true;
		}
	}

	return false;
}



function event_store_item($arr, $event) {

	require_once('include/datetime.php');
	require_once('include/items.php');

	$item = null;

	if(isset($arr['mid'], $arr['uid'])) {
		$i = q("select * from item where mid = '%s' and uid = %d limit 1",
			dbesc($arr['mid']),
			intval($arr['uid'])
		);
		if($i) {
			xchan_query($i);
			$item = fetch_post_tags($i,true);
		}
	}


	$item_arr = array();
	$prefix = '';
//	$birthday = false;

	if(($event) && array_key_exists('event_hash',$event) && (! array_key_exists('event_hash',$arr)))
		$arr['event_hash'] = $event['event_hash'];

	if($event['etype'] === 'birthday') {
		if(! is_sys_channel($arr['uid']))
			$prefix =  t('This event has been added to your calendar.');
//		$birthday = true;

		// The event is created on your own site by the system, but appears to belong
		// to the birthday person. It also isn't propagated - so we need to prevent
		// folks from trying to comment on it. If you're looking at this and trying to
		// fix it, you'll need to completely change the way birthday events are created
		// and send them out from the source. This has its own issues.

		$item_arr['comment_policy'] = 'none';
	}

	$r = q("SELECT * FROM item WHERE resource_id = '%s' AND resource_type = 'event' and uid = %d LIMIT 1",
		dbesc($event['event_hash']),
		intval($arr['uid'])
	);

	if($r) {

		//set_iconfig($r[0]['id'], 'event', 'timezone', $arr['timezone'], true);
		//xchan_query($r);
		//$r = fetch_post_tags($r,true);

		$x = [
			'type'      => 'Event',
			'id'        => z_root() . '/event/' . $r[0]['resource_id'],
			'name'      => $arr['summary'],
//          'summary'   => bbcode($arr['summary']),
			// RFC3339 Section 4.3
			'startTime' => (($arr['adjust']) ? datetime_convert('UTC', 'UTC', $arr['dtstart'], ATOM_TIME) : datetime_convert('UTC', 'UTC', $arr['dtstart'], 'Y-m-d\\TH:i:s-00:00')),
			'content'   => bbcode($arr['description']),
			'location'  => [ 'type' => 'Place', 'name' => $arr['location'] ],
			'source'    => [ 'content' => format_event_bbcode($arr), 'mediaType' => 'text/bbcode' ],
			'url'       => [ [ 'mediaType' => 'text/calendar', 'href' => z_root() . '/events/ical/' . $event['event_hash'] ] ],
			'actor'     => Activity::encode_person($r[0], false),
			'attachment' => Activity::encode_attachment($r[0]),
			'tag'       => Activity::encode_taxonomy($r[0])
		];

		if (! $arr['nofinish']) {
			$x['endTime'] = (($arr['adjust']) ? datetime_convert('UTC', 'UTC', $arr['dtend'], ATOM_TIME) : datetime_convert('UTC', 'UTC', $arr['dtend'], 'Y-m-d\\TH:i:s-00:00'));
		}

		if ($event['event_repeat']) {
			$x['eventRepeat'] = $event['event_repeat'];
		}

		$object = json_encode($x);

		$private = (($arr['allow_cid'] || $arr['allow_gid'] || $arr['deny_cid'] || $arr['deny_gid']) ? 1 : 0);

		/**
		 * @FIXME can only update sig if we have the author's channel on this site
		 * Until fixed, set it to nothing so it won't give us signature errors.
		 */
		$sig = '';

		q("UPDATE item SET title = '%s', body = '%s', obj = '%s', allow_cid = '%s', allow_gid = '%s', deny_cid = '%s', deny_gid = '%s', edited = '%s', sig = '%s', item_flags = %d, item_private = %d, obj_type = '%s'  WHERE id = %d AND uid = %d",
			dbesc($arr['summary']),
			dbesc($prefix . format_event_bbcode($arr)),
			dbesc($object),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid']),
			dbesc($arr['edited']),
			dbesc($sig),
			intval($r[0]['item_flags']),
			intval($private),
			dbesc(ACTIVITY_OBJ_EVENT),
			intval($r[0]['id']),
			intval($arr['uid'])
		);

		q("delete from term where oid = %d and otype = %d",
			intval($r[0]['id']),
			intval(TERM_OBJ_POST)
		);

		if(($arr['term']) && (is_array($arr['term']))) {
			foreach($arr['term'] as $t) {
				q("insert into term (uid,oid,otype,ttype,term,url)
					values(%d,%d,%d,%d,'%s','%s') ",
					intval($arr['uid']),
					intval($r[0]['id']),
					intval(TERM_OBJ_POST),
					intval($t['ttype']),
					dbesc($t['term']),
					dbesc($t['url'])
				);
			}
		}

		$item_id = $r[0]['id'];

		/**
		 * @hooks event_updated
		 *   Called when an event record is modified.
		 */
		call_hooks('event_updated', $event['id']);

		return $item_id;
	} else {

		$z = q("select * from channel where channel_id = %d limit 1",
			intval($arr['uid'])
		);

		$private = (($arr['allow_cid'] || $arr['allow_gid'] || $arr['deny_cid'] || $arr['deny_gid']) ? 1 : 0);

		$item_wall = 0;
		$item_origin = 0;
		$item_thread_top = 0;

		if($item) {
			$item_arr['id'] = $item['id'];
		}
		else {
			$wall = (($z[0]['channel_hash'] == $event['event_xchan']) ? true : false);
			$item_thread_top = 1;
			if($wall) {
				$item_wall = 1;
				$item_origin = 1;
			}
		}

		if(! $arr['mid']) {
			$arr['uuid'] = $event['event_hash'];
			$arr['mid'] = z_root() . '/activity/' . $event['event_hash'];
		}

		$item_arr['aid']             = $z[0]['channel_account_id'];
		$item_arr['uid']             = $arr['uid'];
		$item_arr['uuid']            = $arr['uuid'];
		$item_arr['author_xchan']    = $arr['event_xchan'];
		$item_arr['mid']             = $arr['mid'];
		$item_arr['parent_mid']      = $arr['mid'];
		$item_arr['owner_xchan']     = (($wall) ? $z[0]['channel_hash'] : $arr['event_xchan']);
		$item_arr['author_xchan']    = $arr['event_xchan'];
		$item_arr['title']           = $arr['summary'];
		$item_arr['allow_cid']       = $arr['allow_cid'];
		$item_arr['allow_gid']       = $arr['allow_gid'];
		$item_arr['deny_cid']        = $arr['deny_cid'];
		$item_arr['deny_gid']        = $arr['deny_gid'];
		$item_arr['item_private']    = $private;
		$item_arr['verb']            = 'Invite';
		$item_arr['item_wall']       = $item_wall;
		$item_arr['item_origin']     = $item_origin;
		$item_arr['item_thread_top'] = $item_thread_top;

		$attach = array(array(
			'href' => z_root() . '/channel_calendar/ical/' .  urlencode($event['event_hash']),
			'length' => 0,
			'type' => 'text/calendar',
			'title' => t('event') . '-' . $event['event_hash'],
			'revision' => ''
		));

		$item_arr['attach'] = $attach;


		if(array_key_exists('term', $arr))
			$item_arr['term'] = $arr['term'];

		$item_arr['resource_type']   = 'event';
		$item_arr['resource_id']     = $event['event_hash'];
		$item_arr['obj_type']        = ACTIVITY_OBJ_EVENT;
		$item_arr['body']            = $prefix . format_event_bbcode($arr);

		// if it's local send the permalink to the channel page.
		// otherwise we'll fallback to /display/$message_id

		if($wall)
			$item_arr['plink'] = $item_arr['mid'];
		else
			$item_arr['plink'] = z_root() . '/display/' . gen_link_id($item_arr['mid']);

		set_iconfig($item_arr, 'event','timezone',$arr['timezone'],true);

		$x = q("select * from xchan where xchan_hash = '%s' limit 1",
				dbesc($arr['event_xchan'])
		);
		if($x) {
			$y = [
				'type'       => 'Event',
				'id'         => z_root() . '/event/' . $event['event_hash'],
				'name'       => $arr['summary'],
//              'summary'    => bbcode($arr['summary']),
				// RFC3339 Section 4.3
				'startTime'  => (($arr['adjust']) ? datetime_convert('UTC', 'UTC', $arr['dtstart'], ATOM_TIME) : datetime_convert('UTC', 'UTC', $arr['dtstart'], 'Y-m-d\\TH:i:s-00:00')),
				'content'    => $arr['description'],
				'location'   => [ 'type' => 'Place', 'name' => $arr['location'] ],
				'source'     => [ 'content' => format_event_bbcode($arr), 'mediaType' => 'text/bbcode' ],
				'url'        => [ [ 'mediaType' => 'text/calendar', 'href' => z_root() . '/events/ical/' . $event['event_hash'] ] ],
				'actor'      => Activity::encode_person($z, false),
				'attachment' => Activity::encode_attachment($item_arr),
				'tag'        => Activity::encode_taxonomy($item_arr)
			];

			if (! $arr['nofinish']) {
				$y['endTime'] = (($arr['adjust']) ? datetime_convert('UTC', 'UTC', $arr['dtend'], ATOM_TIME) : datetime_convert('UTC', 'UTC', $arr['dtend'], 'Y-m-d\\TH:i:s-00:00'));
			}
			if ($arr['event_repeat']) {
				$y['eventRepeat'] = $arr['event_repeat'];
			}

			$item_arr['obj']  = json_encode($y);
		}

		// propagate the event resource_id so that posts containing it are easily searchable in downstream copies
		// of the item which have not stored the actual event. Required for Diaspora event federation as Diaspora
		// event_participation messages refer to the event resource_id as a parent, while out own event attendance
		// activities refer to the item message_id as the parent.

		set_iconfig($item_arr, 'system','event_id',$event['event_hash'],true);

		$res = item_store($item_arr);

		$item_id = $res['item_id'];

		/**
		 * @hooks event_created
		 *   Called when an event record is created.
		 */
		call_hooks('event_created', $event['id']);

		return $item_id;
	}
}


function todo_stat() {
	return array(
		''             => t('Not specified'),
		'NEEDS-ACTION' => t('Needs Action'),
		'COMPLETED'    => t('Completed'),
		'IN-PROCESS'   => t('In Process'),
		'CANCELLED'    => t('Cancelled')
	);
}


function tasks_fetch($arr) {

	if(! local_channel())
		return;

	$ret = array();
	$sql_extra = " and event_status != 'COMPLETED' ";
	if($arr && $arr['all'] == 1)
		$sql_extra = '';

	$r = q("select * from event where etype = 'task' and uid = %d $sql_extra order by created desc",
		intval(local_channel())
	);

	$ret['success'] = (($r) ? true : false);
	if($r) {
		$ret['tasks'] = $r;
	}

	return $ret;
}

function cdav_principal($uri) {
	$r = q("SELECT uri FROM principals WHERE uri = '%s' LIMIT 1",
		dbesc($uri)
	);

	if($r[0]['uri'] === $uri)
		return true;
	else
		return false;
}

function cdav_perms($needle, $haystack, $check_rw = false) {

	if($needle == 'channel_calendar')
		return true;

	foreach ($haystack as $item) {
		if($check_rw) {
			if(is_array($item['id'])) {
				if ($item['id'][0] == $needle && $item['share-access'] != 2) {
					return $item['{DAV:}displayname'];
				}
			}
			else {
				if ($item['id'] == $needle && $item['share-access'] != 2) {
					return $item['{DAV:}displayname'];
				}
			}
		}
		else {
			if(is_array($item['id'])) {
				if ($item['id'][0] == $needle) {
					return $item['{DAV:}displayname'];
				}
			}
			else {
				if ($item['id'] == $needle) {
					return $item['{DAV:}displayname'];
				}
			}
		}
	}
	return false;
}


function translate_type($type) {

	if(!$type)
		return;

	$type = strtoupper($type);

	$map = [
		'CELL' => t('Mobile'),
		'HOME' => t('Home'),
		'HOME,VOICE' => t('Home, Voice'),
		'HOME,FAX' => t('Home, Fax'),
		'WORK' => t('Work'),
		'WORK,VOICE' => t('Work, Voice'),
		'WORK,FAX' => t('Work, Fax'),
		'OTHER' => t('Other')
	];

	if (array_key_exists($type, $map)) {
		return [$type, $map[$type]];
	}
	else {
		return [$type, t('Other') . ' (' . $type . ')'];
	}
}


function cal_store_lowlevel($arr) {

	$store = [
		'cal_aid'    => ((array_key_exists('cal_aid',$arr))   ? $arr['cal_aid']   : 0),
		'cal_uid'    => ((array_key_exists('cal_uid',$arr))   ? $arr['cal_uid']   : 0),
		'cal_hash'   => ((array_key_exists('cal_hash',$arr))  ? $arr['cal_hash']  : ''),
		'cal_name'   => ((array_key_exists('cal_name',$arr))  ? $arr['cal_name']  : ''),
		'uri'        => ((array_key_exists('uri',$arr))       ? $arr['uri']       : ''),
		'logname'    => ((array_key_exists('logname',$arr))   ? $arr['logname']   : ''),
		'pass'       => ((array_key_exists('pass',$arr))      ? $arr['pass']      : ''),
		'ctag'       => ((array_key_exists('ctag',$arr))      ? $arr['ctag']      : ''),
		'synctoken'  => ((array_key_exists('synctoken',$arr)) ? $arr['synctoken'] : ''),
		'cal_types'  => ((array_key_exists('cal_types',$arr)) ? $arr['cal_types'] : ''),
	];

	return create_table_from_array('cal', $store);

}


