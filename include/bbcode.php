<?php
/**
 * @file include/bbcode.php
 * @brief BBCode related functions for parsing, etc.
 */

use Zotlabs\Lib\SvgSanitizer;
use Zotlabs\Lib\Libzot;

require_once('include/oembed.php');
require_once('include/event.php');
require_once('include/html2plain.php');

function get_bb_tag_pos($s, $name, $occurance = 1) {

	if($occurance < 1)
		$occurance = 1;

	$start_open = -1;
	for($i = 1; $i <= $occurance; $i++) {
		if( $start_open !== false)
			$start_open = strpos($s, '[' . $name, $start_open + 1); // allow [name= type tags
	}

	if( $start_open === false)
		return false;

	$start_equal = strpos($s, '=', $start_open);
	$start_close = strpos($s, ']', $start_open);

	if( $start_close === false)
		return false;

	$start_close++;

	$end_open = strpos($s, '[/' . $name . ']', $start_close);

	if( $end_open === false)
		return false;

	$res = array( 'start' => array('open' => $start_open, 'close' => $start_close),
	              'end' => array('open' => $end_open, 'close' => $end_open + strlen('[/' . $name . ']')) );
	if( $start_equal !== false)
		$res['start']['equal'] = $start_equal + 1;

	return $res;
}

function bb_tag_preg_replace($pattern, $replace, $name, $s) {

	$string = $s;

	$occurance = 1;
	$pos = get_bb_tag_pos($string, $name, $occurance);
	while($pos !== false && $occurance < 1000) {

		$start = substr($string, 0, $pos['start']['open']);
		$subject = substr($string, $pos['start']['open'], $pos['end']['close'] - $pos['start']['open']);
		$end = substr($string, $pos['end']['close']);
		if($end === false)
			$end = '';

		$subject = preg_replace($pattern, $replace, $subject);
		$string = $start . $subject . $end;

		$occurance++;
		$pos = get_bb_tag_pos($string, $name, $occurance);
	}

	return $string;
}


function tryoembed($match) {
	$url = ((count($match) == 2) ? $match[1] : $match[2]);

	$o = oembed_fetch_url($url);

	if ($o['type'] == 'error')
		return $match[0];

	$html = oembed_format_object($o);
	return $html;
}


function nakedoembed($match) {
	$url = ((count($match) == 2) ? $match[1] : $match[2]);

	$strip_url = strip_escaped_zids($url);

	// this function no longer performs oembed on naked links
	// because they author may have created naked links intentionally.
	// Now it just strips zids on naked links.

	return str_replace($url,$strip_url,$match[0]);
}

function tryzrlaudio($match) {
	$link = $match[1];
	$zrl = is_matrix_url($link);
	if($zrl)
		$link = zid($link);

	return '<audio src="' . str_replace(' ','%20',$link) . '" controls="controls" preload="none"><a href="' . str_replace(' ','%20',$link) . '">' . $link . '</a></audio>';
}

function tryzrlvideo($match) {
	$link = $match[1];
	$zrl = is_matrix_url($link);
	if($zrl)
		$link = zid($link);

	$static_link = get_config('system','video_default_poster','images/video_poster.jpg');
	if($static_link)
		$poster = 'poster="' . escape_tags($static_link) . '" ' ;

	return '<video ' . $poster . ' controls="controls" preload="none" src="' . str_replace(' ','%20',$link) . '" style="width:100%; max-width:' . App::$videowidth . 'px"><a href="' . str_replace(' ','%20',$link) . '">' . $link . '</a></video>';
}

function videowithopts($match) {
	$link = $match[2];
	$zrl = is_matrix_url($link);
	if($zrl)
		$link = zid($link);

	$attributes = $match[1];

	$poster = "";

	preg_match("/poster='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$poster = 'poster="' . (($zrl) ? zid($matches[1]) : $matches[1]) . '"';

	return '<video ' . $poster . ' controls="controls" preload="none" src="' . str_replace(' ','%20',$link) . '" style="width:100%; max-width:' . App::$videowidth . 'px"><a href="' . str_replace(' ','%20',$link) . '">' . $link . '</a></video>';
}




// [noparse][i]italic[/i][/noparse] turns into
// [noparse][ i ]italic[ /i ][/noparse],
// to hide them from parser.

function bb_spacefy($st) {
	$whole_match = $st[0];
	$captured = $st[1];
	$spacefied = preg_replace("/\[(.*?)\]/", "[ $1 ]", $captured);
	$new_str = str_replace($captured, $spacefied, $whole_match);

	return $new_str;
}

// The previously spacefied [noparse][ i ]italic[ /i ][/noparse],
// now turns back returning [noparse][i]italic[/i][/noparse]
function bb_unspacefy($st) {
	$whole_match = $st[0];
	$captured = $st[1];
	$spacefied = preg_replace("/\[ (.*?) \]/", "[$1]", $captured);
	$new_str = str_replace($captured, $spacefied, $whole_match);

	return $new_str;
}


// The previously spacefied [noparse][ i ]italic[ /i ][/noparse],
// now turns back and the [noparse] tags are trimmed
// returning [i]italic[/i]

function bb_unspacefy_and_trim($st) {
	//$whole_match = $st[0];
	$captured = $st[1];
	$unspacefied = preg_replace("/\[ (.*?)\ ]/", "[$1]", $captured);

	return $unspacefied;
}


function bb_extract_images($body) {

	$saved_image = array();
	$orig_body = $body;
	$new_body = '';

	$cnt = 0;
	$img_start = strpos($orig_body, '[img');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	while(($img_st_close !== false) && ($img_end !== false)) {

		$img_st_close++; // make it point to AFTER the closing bracket
		$img_end += $img_start;

		if(! strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
			// This is an embedded image

			$saved_image[$cnt] = substr($orig_body, $img_start + $img_st_close, $img_end - ($img_start + $img_st_close));
			$new_body = $new_body . substr($orig_body, 0, $img_start) . '[$#saved_image' . $cnt . '#$]';

			$cnt++;
		}
		else
			$new_body = $new_body . substr($orig_body, 0, $img_end + strlen('[/img]'));

		$orig_body = substr($orig_body, $img_end + strlen('[/img]'));

		if($orig_body === false) // in case the body ends on a closing image tag
			$orig_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	}

	$new_body = $new_body . $orig_body;

	return array('body' => $new_body, 'images' => $saved_image);
}


function bb_replace_images($body, $images) {

	$newbody = $body;
	$cnt = 0;

	if(! $images)
		return $newbody;

	foreach($images as $image) {
		// We're depending on the property of 'foreach' (specified on the PHP website) that
		// it loops over the array starting from the first element and going sequentially
		// to the last element
		$newbody = str_replace('[$#saved_image' . $cnt . '#$]', '<img src="' . $image .'" alt="' . t('Image/photo') . '" loading="eager" />', $newbody);
		$cnt++;
	}
//	logger('replace_images: ' . $newbody);
	return $newbody;
}

/**
 * @brief Parses crypt BBCode.
 *
 * @param array $match
 * @return string HTML code
 */
function bb_parse_crypt($match) {

	$matches = [];
	$attributes = $match[1];

	$algorithm = "";

	preg_match("/alg='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$algorithm = $matches[1];

	preg_match("/alg=\&quot\;(.*?)\&quot\;/ism", $attributes, $matches);
	if ($matches[1] != "")
		$algorithm = $matches[1];

	$hint = "";

	preg_match("/hint='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$hint = $matches[1];
	preg_match("/hint=\&quot\;(.*?)\&quot\;/ism", $attributes, $matches);
	if ($matches[1] != "")
		$hint = $matches[1];

	$x = random_string();

	$f = 'hz_decrypt';

	//legacy cryptojs support
	if(plugin_is_installed('cryptojs')) {
		$f = ((in_array($algorithm, ['AES-128-CCM', 'rot13', 'triple-rot13'])) ? 'hz_decrypt' : 'red_decrypt');
	}

	$onclick = 'onclick="' . $f . '(\'' . $algorithm . '\',\'' . $hint . '\',\'' . $match[2] . '\',\'#' . $x . '\');"';
	$label = t('Encrypted content');

	$Text = '<br /><div id="' . $x . '"><img class="cursor-pointer" src="' . z_root() . '/images/lock_icon.svg" ' . $onclick . ' alt="' . $label . '" title="' . $label . '" /></div><br />';

	return $Text;
}

/**
 * @brief Returns raw base64 encoded crypt content.
 *
 * @param array $match
 * @return string
 */
function bb_parse_b64_crypt($match) {

	if(empty($match[2]))
		return;

	$r .= '<code>';
	$r .= '----- ENCRYPTED CONTENT -----' . '<br>';
	$r .= $match[2] . '<br>';
	$r .= '----- END ENCRYPTED CONTENT -----';
	$r .= '</code>';

	return $r;

}


function bb_parse_app($match) {

	$app = Zotlabs\Lib\Apps::app_decode($match[1]);
	if ($app)
		return preg_replace('/[[:cntrl:]]/', '', Zotlabs\Lib\Apps::app_render($app, 'inline'));
}

function bb_svg($match) {

	$params = str_replace(['<br>', '&quot;'], [ '', '"'],$match[1]);
	$Text = str_replace([ '[',']' ], [ '<','>' ], $match[2]);

	$output =  '<svg' . (($params) ? $params : ' width="100%" height="480" ') . '>' . str_replace(['<br>', '&quot;', '&nbsp;'], [ '', '"', ' '],$Text) . '</svg>';

	$purify = new SvgSanitizer();
	$purify->loadXML($output);
	$purify->sanitize();
	$output = $purify->saveSVG();
	$output = preg_replace("/\<\?xml(.*?)\?\>/",'',$output);
	return $output;
}


function bb_parse_element($match) {
	$j = json_decode(base64url_decode($match[1]),true);

	if ($j && local_channel()) {
		$text = sprintf( t('Install %1$s element %2$s'), translate_design_element($j['type']), $j['pagetitle']);
		$o = EOL . '<button class="btn btn-primary" onclick="importElement(\'' . $match[1] . '\'); return false;" >' . $text . '</button>' . EOL;
	}
	else {
		$text = sprintf( t('This post contains an installable %s element, however you lack permissions to install it on this site.' ), translate_design_element($j['type'])) . $j['pagetitle'];
		$o = EOL . $text . EOL;
	}

	return $o;
}

function translate_design_element($type) {
	switch($type) {
		case 'webpage':
			$ret = t('webpage');
			break;
		case 'layout':
			$ret =  t('layout');
			break;
		case 'block':
			$ret =  t('block');
			break;
		case 'menu':
			$ret =  t('menu');
			break;
	}

	return $ret;
}

function bb_format_attachdata($body) {

	$data = getAttachmentData($body);

	if($data) {
		$txt = '';
		if(isset($data['url']) && isset($data['title'])) {
			$txt .= "\n\n" . '[url=' . $data['url'] . ']' . $data['title'] . '[/url]';
		}
		else {
			if(isset($data['url'])) {
				$txt .= "\n\n" . $data['url'];
			}
			if(isset($data['title'])) {
				$txt .= "\n\n" . $data['title'];
			}
		}

		if(isset($data['preview'])) {
			$txt .= "\n\n" . '[img]' . $data['preview'] . '[/img]';
		}

		if(isset($data['image'])) {
			$txt .= "\n\n" . '[img]' . $data['image'] . '[/img]';
		}

		if(isset($data['text'])) {
			$txt .= "\n\n" . $data['text'];
		}

		return preg_replace('/\[attachment(.*?)\](.*?)\[\/attachment\]/ism',$txt,$body);
	}

	return $body;
}

function getAttachmentData($body) {

	$data = [];

	if (! preg_match("/\[attachment(.*?)\](.*?)\[\/attachment\]/ism", $body, $match)) {
		return null;
	}

	$attributes = $match[1];

	$data["text"] = trim($match[2]);

	$type = "";
	preg_match("/type='(.*?)'/ism", $attributes, $matches);

	if (x($matches, 1)) {
		$type = strtolower($matches[1]);
	}

	preg_match('/type=\&quot\;(.*?)\&quot\;/ism', $attributes, $matches);
	if (x($matches, 1)) {
		$type = strtolower($matches[1]);
	}

	if ($type == "") {
		return [];
	}

	if (!in_array($type, ["link", "audio", "photo", "video"])) {
		return [];
	}

	if ($type != "") {
		$data["type"] = $type;
	}
	$url = "";
	preg_match("/url='(.*?)'/ism", $attributes, $matches);
	if (x($matches, 1)) {
		$url = $matches[1];
	}

	preg_match('/url=\&quot\;(.*?)\&quot\;/ism', $attributes, $matches);
	if (x($matches, 1)) {
		$url = $matches[1];
	}

	if ($url != "") {
		$data["url"] = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
	}

	$title = "";
	preg_match("/title='(.*?)'/ism", $attributes, $matches);
	if (x($matches, 1)) {
		$title = $matches[1];
	}

	preg_match('/title=\&quot\;(.*?)\&quot\;/ism', $attributes, $matches);
	if (x($matches, 1)) {
		$title = $matches[1];
	}
	if ($title != "") {
		$title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
		$title = str_replace(["[", "]"], ["&#91;", "&#93;"], $title);
		$data["title"] = $title;
	}

	$image = "";
	preg_match("/image='(.*?)'/ism", $attributes, $matches);
	if (x($matches, 1)) {
		$image = $matches[1];
	}

	preg_match('/image=\&quot\;(.*?)\&quot\;/ism', $attributes, $matches);
	if (x($matches, 1)) {
		$image = $matches[1];
	}

	if ($image != "") {
		$data["image"] = html_entity_decode($image, ENT_QUOTES, 'UTF-8');
	}

	$preview = "";
	preg_match("/preview='(.*?)'/ism", $attributes, $matches);
	if (x($matches, 1)) {
		$preview = $matches[1];
	}

	preg_match('/preview=\&quot\;(.*?)\&quot\;/ism', $attributes, $matches);
	if (x($matches, 1)) {
		$preview = $matches[1];
	}
	if ($preview != "") {
		$data["preview"] = html_entity_decode($preview, ENT_QUOTES, 'UTF-8');
	}

	$data["description"] = ((isset($match[3])) ? trim($match[3]) : '');

	$data["after"] = ((isset($match[4])) ? trim($match[4]) : '');

	return $data;
}

function bb_ShareAttributes($match) {

	$matches = array();
	$attributes = $match[1];

	$author = "";
	preg_match("/author='(.*?)'/ism", $attributes, $matches);
	if (isset($matches[1]) && $matches[1] !== '') {
		$author = urldecode($matches[1]);
	}

	$link = "";
	preg_match("/link='(.*?)'/ism", $attributes, $matches);
	if (isset($matches[1]) && $matches[1] !== '') {
		$link = $matches[1];
	}

	$avatar = "";
	preg_match("/avatar='(.*?)'/ism", $attributes, $matches);
	if (isset($matches[1]) && $matches[1] !== '') {
		$avatar = $matches[1];
	}

	$profile = "";
	preg_match("/profile='(.*?)'/ism", $attributes, $matches);
	if (isset($matches[1]) && $matches[1] !== '') {
		$profile = $matches[1];
	}

	$posted = "";
	preg_match("/posted='(.*?)'/ism", $attributes, $matches);
	if (isset($matches[1]) && $matches[1] !== '') {
		$posted = $matches[1];
	}

	$auth = "";
	preg_match("/auth='(.*?)'/ism", $attributes, $matches);
	if (isset($matches[1]) && $matches[1] !== '') {
		if($matches[1] === 'true')
			$auth = true;
		else
			$auth = false;
	}

	if($auth === EMPTY_STR) {
		$auth = is_matrix_url($profile);
	}

	$rnd = mt_rand();

	$reldate = '<span class="autotime" title="' . datetime_convert('UTC', date_default_timezone_get(), $posted, 'c') . '" >' . datetime_convert('UTC', date_default_timezone_get(), $posted, 'r') . '</span>';

	$headline = '<div id="shared_container_' . $rnd . '" class="shared_container"> <div id="shared_header_' . $rnd . '" class="shared_header">';

	if ($avatar != "")
		$headline .= '<a href="' . (($auth) ? zid($profile) : $profile) . '" ><img src="' . $avatar . '" alt="' . $author . '" height="32" width="32" loading="lazy" /></a>';

	if(strpos($link,'/cards/'))
		$type = t('card');
	elseif(strpos($link,'/articles/'))
		$type = t('article');
	else
		$type = t('post');

	// Bob Smith wrote the following post 2 hours ago

	$fmt = sprintf( t('%1$s wrote the following %2$s %3$s'),
		'<a href="' . (($auth) ? zid($profile) : $profile) . '" ><bdi>' . $author . '</bdi></a>',
		'<a href="' . (($auth) ? zid($link) : $link) . '" >' . $type . '</a>',
		$reldate
	);

	$headline .= '<span>' . $fmt . '</span></div>';

	$text = $headline . '<div id="reshared-content-' . $rnd . '" class="reshared-content">' . trim($match[2]) . '</div></div>';

	return $text;
}

function bb_location($match) {
	// not yet implemented
}

/**
 * @brief Returns an iframe from $match[1].
 *
 * @param array $match
 * @return string HTML iframe with content of $match[1]
 */
function bb_iframe($match) {

	$sandbox = ((strpos($match[1], App::get_hostname())) ? ' sandbox="allow-scripts" ' : '');

	return '<iframe ' . $sandbox . ' src="' . $match[1] . '" width="' . App::$videowidth . '" height="' . App::$videoheight . '"><a href="' . $match[1] . '">' . $match[1] . '</a></iframe>';
}

function bb_ShareAttributesSimple($match) {

	$matches = array();
	$attributes = $match[1];

	$author = "";
	preg_match("/author='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$author = html_entity_decode($matches[1],ENT_QUOTES,'UTF-8');

	preg_match('/author="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$author = $matches[1];

	$profile = "";
	preg_match("/profile='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	preg_match('/profile="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	$text = html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8') . ' <a href="' . $profile . '">' . $author . '</a>: <div class="reshared-content">' . $match[2] . '</div>';

	return($text);
}

function rpost_callback($match) {
	if ($match[2]) {
		return str_replace($match[0], Libzot::get_rpost_path(App::get_observer()) . '&title=' . urlencode($match[2]) . '&body=' . urlencode($match[3]), $match[0]);
	} else {
		return str_replace($match[0], Libzot::get_rpost_path(App::get_observer()) . '&body=' . urlencode($match[3]), $match[0]);
	}
}

function bb_map_coords($match) {
	// the extra space in the following line is intentional
	return str_replace($match[0],'<div class="map"  >' . generate_map(str_replace('/',' ',$match[1])) . '</div>', $match[0]);
}

function bb_map_location($match) {
	// the extra space in the following line is intentional
	return str_replace($match[0],'<div class="map"  >' . generate_named_map($match[1]) . '</div>', $match[0]);
}

function bb_opentag($match) {
	$openclose = (($match[2]) ? '<span class="bb-open" title="' . t('Click to open/close') . '">' . $match[1] . '</span>' : t('Click to open/close'));
	$text = (($match[2]) ? $match[2] : $match[1]);
	$rnd = mt_rand();

	return '<div onclick="openClose(\'opendiv-' . $rnd . '\'); return false;" class="fakelink">' . $openclose . '</div><div id="opendiv-' . $rnd . '" style="display: none;">' . $text . '</div>';
}

function bb_spoilertag($match) {
	$openclose = (($match[2]) ? '<span class="bb-spoiler" title="' . t('Click to open/close') . '">' . $match[1] . ' ' . t('spoiler') . '</span>' : t('Click to open/close'));
	$text = (($match[2]) ? $match[2] : $match[1]);
	$rnd = mt_rand();

	return '<div onclick="openClose(\'opendiv-' . $rnd . '\'); return false;" class="fakelink">' . $openclose . '</div><blockquote id="opendiv-' . $rnd . '" style="display: none;">' . $text . '</blockquote>';
}

function bb_summary($match) {
	$rnd1 = mt_rand();
	$rnd2 = mt_rand();
	$rnd3 = mt_rand();
	$rnd4 = mt_rand();

	return $match[1] . '<div style="display: block;" id="opendiv-' . $rnd2 . '">' . $match[2] . '</div><div style="display: block;" id="opendiv-' . $rnd3 . '" onclick="openClose(\'opendiv-' . $rnd1 . '\'); openClose(\'opendiv-' . $rnd2 . '\'); openClose(\'opendiv-' . $rnd3 . '\'); openClose(\'opendiv-' . $rnd4 . '\'); return false;" class="fakelink view-article">' . t('View article') . '</div><div style="display: none;" id="opendiv-' . $rnd4 . '" onclick="openClose(\'opendiv-' . $rnd1 . '\'); openClose(\'opendiv-' . $rnd2 . '\'); openClose(\'opendiv-' . $rnd3 . '\'); openClose(\'opendiv-' . $rnd4 . '\'); return false;" class="fakelink view-summary">' . t('View summary') . '</div><div id="opendiv-' . $rnd1 . '" style="display: none;">' . $match[3] . '</div>';
}


function bb_definitionList($match) {
	// $match[1] is the markup styles for the "terms" in the definition list.
	// $match[2] is the content between the [dl]...[/dl] tags

	$classes = '';
	if (stripos($match[1], "b") !== false) $classes .= 'dl-terms-bold ';
	if (stripos($match[1], "i") !== false) $classes .= 'dl-terms-italic ';
	if (stripos($match[1], "u") !== false) $classes .= 'dl-terms-underline ';
	if (stripos($match[1], "l") !== false) $classes .= 'dl-terms-large ';
	if (stripos($match[1], "m") !== false) $classes .= 'dl-terms-monospace ';
	if (stripos($match[1], "h") !== false) $classes .= 'dl-horizontal '; // dl-horizontal is already provided by bootstrap
	if (strlen($classes) === 0) $classes = "dl-terms-plain";

	// The bbcode transformation will be:
	// [*=term-text] description-text   =>   </dd> <dt>term-text<dt><dd> description-text
	// then after all replacements have been made, the extra </dd> at the start of the
	// first line can be removed. HTML5 allows the tag to be missing from the end of the last line.
	// Using '(?<!\\\)' to allow backslash-escaped closing braces to appear in the term-text.
	$closeDescriptionTag = "</dd>\n";
	$eatLeadingSpaces = '(?:&nbsp;|[ \t])*'; // prevent spaces infront of [*= from adding another line to the previous element
	$listElements = preg_replace('/^(\n|<br \/>)/', '', $match[2]); // ltrim the first newline
	$listElements = preg_replace(
		'/' . $eatLeadingSpaces . '\[\*=([[:print:]]*?)(?<!\\\)\]/uism',
		$closeDescriptionTag . '<dt>$1</dt><dd>',
		$listElements
	);
	// Unescape any \] inside the <dt> tags
	$listElements = preg_replace_callback('/<dt>(.*?)<\/dt>/ism', 'bb_definitionList_unescapeBraces', $listElements);

	// Remove the extra </dd> at the start of the string, if there is one.
	$firstOpenTag  = strpos($listElements, '<dd>');
	$firstCloseTag = strpos($listElements, $closeDescriptionTag);
	if ($firstCloseTag !== false && ($firstOpenTag === false || ($firstCloseTag < $firstOpenTag))) {
		$listElements = preg_replace( '/<\/dd>/ism', '', $listElements, 1);
	}

	return '<dl class="bb-dl ' . rtrim($classes) . '">' . $listElements . '</dl>';;
}
function bb_definitionList_unescapeBraces($match) {
	return '<dt>' . str_replace('\]', ']', $match[1]) . '</dt>';
}


function bb_checklist($match) {
	$str = $match[1];
	$str = str_replace("[]", "<li><input type=\"checkbox\" disabled=\"disabled\">", $str);
	$str = str_replace("[x]", "<li><input type=\"checkbox\" checked=\"checked\" disabled=\"disabled\">", $str);
	return '<ul class="checklist" style="list-style-type: none;">' . $str . '</ul>';
}


/**
 * @brief Sanitize style properties from BBCode to HTML.
 *
 * @param array $input
 * @return string A HTML span tag with the styles.
 */
function bb_sanitize_style($input) {
	// whitelist array: property => limits (0 = no limitation)
	$w = array(
			// color properties
			"color"            => 0,
			"background-color" => 0,
			// box properties
			"padding"          => array("px"=>100, "%"=>0, "em"=>2, "ex"=>2, "mm"=>0, "cm"=>0, "in"=>0, "pt"=>0, "pc"=>0),
			"margin"           => array("px"=>100, "%"=>0, "em"=>2, "ex"=>2, "mm"=>0, "cm"=>0, "in"=>0, "pt"=>0, "pc"=>0),
			"border"           => array("px"=>100, "%"=>0, "em"=>2, "ex"=>2, "mm"=>0, "cm"=>0, "in"=>0, "pt"=>0, "pc"=>0),
			"float"            => 0,
			"clear"            => 0,
			// text properties
			"text-decoration"  => 0,
	);

	$css = array();
	$css_string = $input[1];
	$a = explode(';', $css_string);

	foreach($a as $parts){
		list($k, $v) = explode(':', $parts);
		$css[ trim($k) ] = trim($v);
	}

	// sanitize properties
	$b = array_merge(array_diff_key($css, $w), array_diff_key($w, $css));
	$css = array_diff_key($css, $b);
	$css_string_san = '';

	foreach ($css as $key => $value) {
		if ($w[$key] != null) {
			foreach ($w[$key] as $limit_key => $limit_value) {
				//sanitize values
				if (strpos($value, $limit_key)) {
					$value = preg_replace_callback(
						"/(\S.*?)$limit_key/ism",
						function($match) use($limit_value, $limit_key) {
							if ($match[1] > $limit_value) {
								return $limit_value . $limit_key;
							} else {
								return $match[1] . $limit_key;
							}
						},
						$value
					);
				}
			}
		}
		$css_string_san .= $key . ":" . $value ."; ";
	}

	return '<span style="' . $css_string_san . '">' . $input[2] . '</span>';
}

function oblanguage_callback($matches) {
	if(strlen($matches[1]) == 2) {
		$compare = strtolower(substr(\App::$language,0,2));
	}
	else {
		$compare = strtolower(\App::$language);
	}

	if($compare === strtolower($matches[1]))
		return $matches[2];

	return '';
}

function oblanguage_necallback($matches) {
	if(strlen($matches[1]) == 2) {
		$compare = strtolower(substr(\App::$language,0,2));
	}
	else {
		$compare = strtolower(\App::$language);
	}

	if($compare !== strtolower($matches[1]))
		return $matches[2];

	return '';
}

function bb_observer($Text) {

	$observer = App::get_observer();

	if ((strpos($Text,'[/observer]') !== false) || (strpos($Text,'[/rpost]') !== false)) {

		if ($observer) {
			$Text = preg_replace("/\[observer\=1\](.*?)\[\/observer\]/ism", '$1', $Text);
			$Text = preg_replace("/\[observer\=0\].*?\[\/observer\]/ism", '', $Text);
			$Text = preg_replace_callback("/\[rpost(=(.*?))?\](.*?)\[\/rpost\]/ism", 'rpost_callback', $Text);
		} else {
			$Text = preg_replace("/\[observer\=1\].*?\[\/observer\]/ism", '', $Text);
			$Text = preg_replace("/\[observer\=0\](.*?)\[\/observer\]/ism", '$1', $Text);
			$Text = preg_replace("/\[rpost(=.*?)?\](.*?)\[\/rpost\]/ism", '', $Text);
		}
	}

	$channel = App::get_channel();

	if (strpos($Text,'[/channel]') !== false) {
		if ($channel) {
			$Text = preg_replace("/\[channel\=1\](.*?)\[\/channel\]/ism", '$1', $Text);
			$Text = preg_replace("/\[channel\=0\].*?\[\/channel\]/ism", '', $Text);
		} else {
			$Text = preg_replace("/\[channel\=1\].*?\[\/channel\]/ism", '', $Text);
			$Text = preg_replace("/\[channel\=0\](.*?)\[\/channel\]/ism", '$1', $Text);
		}
	}

	return $Text;
}

function bb_imgoptions($match) {

	// $Text = preg_replace_callback("/\[([zi])mg([ \=])(.*?)\](.*?)\[\/[zi]mg\]/ism",'bb_imgoptions',$Text);
	// alt text cannot contain ']'

	// [img|zmg=wwwxhhh float=left|right alt=alt text]url[/img|zmg]

	$local_match = null;
	$width       = 0;
	$float       = false;
	$alt         = false;

	$style = EMPTY_STR;

	$attributes = $match[3];

	$x = preg_match("/alt='(.*?)'/ism", $attributes, $matches);
	if ($x) {
		$alt = $matches[1];
	}

	$x = preg_match("/alt=\&quot\;(.*?)\&quot\;/ism", $attributes, $matches);
	if ($x) {
		$alt = $matches[1];
	}

	$x = preg_match("/width='(.*?)'/ism", $attributes, $matches);
	if ($x) {
		$width = $matches[1];
	}

	$x = preg_match("/width=\&quot\;(.*?)\&quot\;/ism", $attributes, $matches);
	if ($x) {
		$width = $matches[1];
	}

	$x = preg_match("/height='(.*?)'/ism", $attributes, $matches);
	if ($x) {
		$height = $matches[1];
	}

	$x = preg_match("/height=\&quot\;(.*?)\&quot\;/ism", $attributes, $matches);
	if ($x) {
		$height = $matches[1];
	}

	$x = preg_match("/style='(.*?)'/ism", $attributes, $matches);
	if ($x) {
		$style = $matches[1];
	}

	$x = preg_match("/style=\&quot\;(.*?)\&quot\;/ism", $attributes, $matches);
	if ($x) {
		$style = $matches[1];
	}

	// legacy img options

	if ($match[2] === '=') {
		// pull out (optional) legacy size declarations first
		if (preg_match("/([0-9]*)x([0-9]*)/ism",$match[3],$local_match)) {
			$width = intval($local_match[1]);
		}
		$match[3] = substr($match[3],strpos($match[3],' '));
	}

	// then (optional) legacy float specifiers
	if ($n = strpos($match[3],'float=left') !== false) {
		$float = 'left';
		$match[3] = substr($match[3],$n + 10);
	}
	if ($n = strpos($match[3],'float=right') !== false) {
		$float = 'right';
		$match[3] = substr($match[3],$n + 11);
	}

	// finally alt text which extends to the close of the tag
	if ((! $alt) && ($n = strpos($match[3],'alt=') !== false)) {
		$alt = substr($match[3],$n + 4);
	}

	// now assemble the resulting img tag from these components

	$output = '<img ' . (($match[1] === 'z') ? 'class="zrl" loading="eager"' : '') . ' ';

	if ($width) {
		$style .= 'width: 100%; max-width: ' . $width . 'px; ';
	}
	else {
		$style .= 'max-width: 100%; ';
	}
	if ($float) {
		$style .= 'float: ' . $float . '; ';
	}

	$output .= (($style) ? 'style="' . $style . '" ' : '') . 'alt="' . htmlentities(($alt) ? $alt : t('Image/photo'),ENT_COMPAT,'UTF-8') . '" ';

	$output .= 'src="' . $match[4] . '" >';

	return $output;

}

function bb_code_protect($s) {
	return 'b64.^9e%.' . base64_encode($s) . '.b64.$9e%';
}

function bb_code_unprotect($s) {
	return preg_replace_callback('|b64\.\^9e\%\.(.*?)\.b64\.\$9e\%|ism','bb_code_unprotect_sub',$s);
}

function bb_code_unprotect_sub($match) {
	return base64_decode($match[1]);
}

function bb_code($match) {
	if(strpos($match[0], PHP_EOL))
		return '<pre><code>' . bb_code_protect(trim($match[1])) . '</code></pre>';
	else
		return '<code class="inline-code">' . bb_code_protect(trim($match[1])) . '</code>';
}

function bb_code_options($match) {
	if(strpos($match[0], PHP_EOL)) {
		$class = "";
		$pre = true;
	} else {
		$class = "inline-code";
		$pre = false;
	}
	if(strpos($match[1], 'nowrap')) {
		$style = "overflow-x: auto; white-space: pre;";
	} else {
		$style = "";
	}
	if($pre) {
		return '<pre><code class="'. $class .'" style="'. $style .'">' . bb_code_protect(trim($match[2])) . '</code></pre>';
	} else {
		return '<code class="'. $class .'" style="'. $style .'">' . bb_code_protect(trim($match[2])) . '</code>';
	}
}

function bb_highlight($match) {
	return bb_code_protect(text_highlight($match[2],strtolower($match[1])));
}

function bb_fixtable_lf($match) {

	// remove extraneous whitespace between table element tags since newlines will all
	// be converted to '<br />' and turn your neatly crafted tables into a whole lot of
	// empty space.

	$x = preg_replace("/\]\s+\[/",'][',$match[1]);
	return '[table]' . $x . '[/table]';

}

function bbtopoll($s) {

	$pl = [];

	$match = '';
	if(! preg_match("/\[poll=(.*?)\](.*?)\[\/poll\]/ism",$s,$match)) {
		return null;
	}
	$pl['poll_id'] = $match[1];
	$pl['poll_question'] = $match[2];

	$match = [];
	if(preg_match_all("/\[poll\-answer=(.*?)\](.*?)\[\/poll\-answer\]/is",$s,$match,PREG_SET_ORDER)) {
		$pl['answer'] = [];
		foreach($match as $m) {
			$ans = [ 'answer_id' => $m[1], 'answer_text' => $m[2] ];
			$pl['answer'][] = $ans;
		}
	}

	return $pl;

}


function parseIdentityAwareHTML($Text) {

	// Hide all [noparse] contained bbtags by spacefying them
	if (strpos($Text,'[noparse]') !== false) {
		$Text = preg_replace_callback("/\[noparse\](.*?)\[\/noparse\]/ism", 'bb_spacefy',$Text);
	}
	if (strpos($Text,'[nobb]') !== false) {
		$Text = preg_replace_callback("/\[nobb\](.*?)\[\/nobb\]/ism", 'bb_spacefy',$Text);
	}
	if (strpos($Text,'[pre]') !== false) {
		$Text = preg_replace_callback("/\[pre\](.*?)\[\/pre\]/ism", 'bb_spacefy',$Text);
	}
	// process [observer] tags before we do anything else because we might
	// be stripping away stuff that then doesn't need to be worked on anymore

        $observer = App::get_observer();

	if ((strpos($Text,'[/observer]') !== false) || (strpos($Text,'[/rpost]') !== false)) {

		$Text = preg_replace_callback("/\[observer\.language\=(.*?)\](.*?)\[\/observer\]/ism",'oblanguage_callback', $Text);

		$Text = preg_replace_callback("/\[observer\.language\!\=(.*?)\](.*?)\[\/observer\]/ism",'oblanguage_necallback', $Text);

		if ($observer) {
			$Text = preg_replace("/\[observer\=1\](.*?)\[\/observer\]/ism", '$1', $Text);
			$Text = preg_replace("/\[observer\=0\].*?\[\/observer\]/ism", '', $Text);
			$Text = preg_replace_callback("/\[rpost(=(.*?))?\](.*?)\[\/rpost\]/ism", 'rpost_callback', $Text);
		} else {
			$Text = preg_replace("/\[observer\=1\].*?\[\/observer\]/ism", '', $Text);
			$Text = preg_replace("/\[observer\=0\](.*?)\[\/observer\]/ism", '$1', $Text);
			$Text = preg_replace("/\[rpost(=.*?)?\](.*?)\[\/rpost\]/ism", '', $Text);
		}
	}
	// replace [observer.baseurl]
	if ($observer) {
		$s1 = '<span class="bb_observer" title="' . t('Different viewers will see this text differently') . '">';
		$s2 = '</span>';
		$obsBaseURL = $observer['xchan_connurl'];
		$obsBaseURL = preg_replace("/\/poco\/.*$/", '', $obsBaseURL);
		$Text = str_replace('[observer.baseurl]', $obsBaseURL, $Text);
		$Text = str_replace('[observer.url]',$observer['xchan_url'], $Text);
		$Text = str_replace('[observer.name]',$s1 . $observer['xchan_name'] . $s2, $Text);
		$Text = str_replace('[observer.address]',$s1 . $observer['xchan_addr'] . $s2, $Text);
		$Text = str_replace('[observer.webname]', substr($observer['xchan_addr'],0,strpos($observer['xchan_addr'],'@')), $Text);
		$Text = str_replace('[observer.photo]',$s1 . '[zmg]'.$observer['xchan_photo_l'].'[/zmg]' . $s2, $Text);
	} else {
		$Text = str_replace('[observer.baseurl]', '', $Text);
		$Text = str_replace('[observer.url]','', $Text);
		$Text = str_replace('[observer.name]','', $Text);
		$Text = str_replace('[observer.address]','', $Text);
		$Text = str_replace('[observer.webname]','',$Text);
		$Text = str_replace('[observer.photo]','', $Text);
	}

	$Text = str_replace(array('[baseurl]','[sitename]'),array(z_root(),get_config('system','sitename')),$Text);


	// Unhide all [noparse] contained bbtags unspacefying them
	// and triming the [noparse] tag.
	if (strpos($Text,'[noparse]') !== false) {
		$Text = preg_replace_callback("/\[noparse\](.*?)\[\/noparse\]/ism", 'bb_unspacefy_and_trim', $Text);
	}
	if (strpos($Text,'[nobb]') !== false) {
		$Text = preg_replace_callback("/\[nobb\](.*?)\[\/nobb\]/ism", 'bb_unspacefy_and_trim', $Text);
	}
	if (strpos($Text,'[pre]') !== false) {
		$Text = preg_replace_callback("/\[pre\](.*?)\[\/pre\]/ism", 'bb_unspacefy_and_trim', $Text);
	}
	return $Text;
}


function bbcode($Text, $options = []) {

	if(! is_array($options)) {
		$options = [];
	}

	$preserve_nl = ((array_key_exists('preserve_nl',$options)) ? $options['preserve_nl'] : false);
	$tryoembed   = ((array_key_exists('tryoembed',$options)) ? $options['tryoembed'] : true);
	$cache       = ((array_key_exists('cache',$options)) ? $options['cache'] : false);
	$newwin      = ((array_key_exists('newwin',$options)) ? $options['newwin'] : true);

	$target = (($newwin) ? ' target="_blank" ' : '');

	call_hooks('bbcode_filter', $Text);

	if(isset($options['drop_media'])) {
		if (strpos($Text,'[/img]') !== false) {
			$Text = preg_replace('/\[img(.*?)\[\/(img)\]/ism', '', $Text);
		}
		if (strpos($Text,'[/audio]') !== false) {
			$Text = preg_replace('/\[audio(.*?)\[\/(audio)\]/ism', '', $Text);
		}
		if (strpos($Text,'[/video]') !== false) {
			$Text = preg_replace('/\[video(.*?)\[\/(video)\]/ism', '', $Text);
		}
		if (strpos($Text,'[/zmg]') !== false) {
			$Text = preg_replace('/\[zmg(.*?)\[\/(zmg)\]/ism', '', $Text);
		}
		if (strpos($Text,'[/zaudio]') !== false) {
			$Text = preg_replace('/\[zaudio(.*?)\[\/(zaudio)\]/ism', '', $Text);
		}
		if (strpos($Text,'[/zvideo]') !== false) {
			$Text = preg_replace('/\[zvideo(.*?)\[\/(zvideo)\]/ism', '', $Text);
		}
	}



	$Text = bb_format_attachdata($Text);

	// If we find any event code, turn it into an event.
	// After we're finished processing the bbcode we'll
	// replace all of the event code with a reformatted version.

	$ev = bbtoevent($Text);

	// and the same with polls

	$pl = bbtopoll($Text);


	// process [observer] tags before we do anything else because we might
	// be stripping away stuff that then doesn't need to be worked on anymore

	if($cache)
		$observer = false;
	else
		$observer = App::get_observer();

	if ((strpos($Text,'[/observer]') !== false) || (strpos($Text,'[/rpost]') !== false)) {
		$Text = preg_replace_callback("/\[observer\.language\=(.*?)\](.*?)\[\/observer\]/ism",'oblanguage_callback', $Text);
		$Text = preg_replace_callback("/\[observer\.language\!\=(.*?)\](.*?)\[\/observer\]/ism",'oblanguage_necallback', $Text);

		if ($observer) {
			$Text = preg_replace("/\[observer\=1\](.*?)\[\/observer\]/ism", '$1', $Text);
			$Text = preg_replace("/\[observer\=0\].*?\[\/observer\]/ism", '', $Text);
			$Text = preg_replace_callback("/\[rpost(=(.*?))?\](.*?)\[\/rpost\]/ism", 'rpost_callback', $Text);
		} else {
			$Text = preg_replace("/\[observer\=1\].*?\[\/observer\]/ism", '', $Text);
			$Text = preg_replace("/\[observer\=0\](.*?)\[\/observer\]/ism", '$1', $Text);
			$Text = preg_replace("/\[rpost(=.*?)?\](.*?)\[\/rpost\]/ism", '', $Text);
		}
	}

	if($cache)
		$channel = false;
	else
		$channel = App::get_channel();

	if (strpos($Text,'[/channel]') !== false) {
		if ($channel) {
			$Text = preg_replace("/\[channel\=1\](.*?)\[\/channel\]/ism", '$1', $Text);
			$Text = preg_replace("/\[channel\=0\].*?\[\/channel\]/ism", '', $Text);
		} else {
			$Text = preg_replace("/\[channel\=1\].*?\[\/channel\]/ism", '', $Text);
			$Text = preg_replace("/\[channel\=0\](.*?)\[\/channel\]/ism", '$1', $Text);
		}
	}


	$x = bb_extract_images($Text);
	$Text = $x['body'];
	$saved_images = $x['images'];

	$Text = str_replace(array('[baseurl]','[sitename]'),array(z_root(),get_config('system','sitename')),$Text);

	// Replace any html brackets with HTML Entities to prevent executing HTML or script
	// Don't use strip_tags here because it breaks [url] search by replacing & with amp

	$Text = str_replace("<", "&lt;", $Text);
	$Text = str_replace(">", "&gt;", $Text);
	$Text = preg_replace_callback("/\[table\](.*?)\[\/table\]/ism",'bb_fixtable_lf',$Text);
	$Text = str_replace(array("\t", "  "), array("&nbsp;&nbsp;&nbsp;&nbsp;", "&nbsp;&nbsp;"), $Text);

	// Check for [code] text here, before the linefeeds are messed with.
	// The highlighter will unescape and re-escape the content.

	if (strpos($Text,'[code=') !== false) {
		$Text = preg_replace_callback("/\[code=(.*?)\](.*?)\[\/code\]/ism", 'bb_highlight', $Text);
	}

	// Check for [code] text
	if (strpos($Text,'[code]') !== false) {
		$Text = preg_replace_callback("/\[code\](.*?)\[\/code\]/ism", 'bb_code', $Text);
	}

	// Check for [code options] text
	if (strpos($Text,'[code ') !== false) {
		$Text = preg_replace_callback("/\[code(.*?)\](.*?)\[\/code\]/ism", 'bb_code_options', $Text);
	}

	// Hide all [noparse] contained bbtags by spacefying them
	if (strpos($Text,'[noparse]') !== false) {
		$Text = preg_replace_callback("/\[noparse\](.*?)\[\/noparse\]/ism", 'bb_spacefy',$Text);
	}
	if (strpos($Text,'[nobb]') !== false) {
		$Text = preg_replace_callback("/\[nobb\](.*?)\[\/nobb\]/ism", 'bb_spacefy',$Text);
	}
	if (strpos($Text,'[pre]') !== false) {
		$Text = preg_replace_callback("/\[pre\](.*?)\[\/pre\]/ism", 'bb_spacefy',$Text);
	}
	if (strpos($Text,'[summary]') !== false) {
		$Text = preg_replace_callback("/\[summary\](.*?)\[\/summary\]/ism", 'bb_spacefy',$Text);
	}
	if (strpos($Text,'[/img]') !== false) {
		$Text = preg_replace_callback('/\[img(.*?)\[\/(img)\]/ism','\red_escape_codeblock',$Text);
	}
	if (strpos($Text,'[/zmg]') !== false) {
		$Text = preg_replace_callback('/\[zmg(.*?)\[\/(zmg)\]/ism','\red_escape_codeblock',$Text);
	}

	// Set up the parameters for a URL search string
	$URLSearchString = "^\[\]";
	// Set up the parameters for a MAIL search string
	$MAILSearchString = $URLSearchString;

	// replace [observer.baseurl]
	if ($observer) {
		$s1 = '<span class="bb_observer" title="' . t('Different viewers will see this text differently') . '">';
		$s2 = '</span>';
		$obsBaseURL = $observer['xchan_connurl'];
		$obsBaseURL = preg_replace("/\/poco\/.*$/", '', $obsBaseURL);
		$Text = str_replace('[observer.baseurl]', $obsBaseURL, $Text);
		$Text = str_replace('[observer.url]',$observer['xchan_url'], $Text);
		$Text = str_replace('[observer.name]',$s1 . $observer['xchan_name'] . $s2, $Text);
		$Text = str_replace('[observer.address]',$s1 . $observer['xchan_addr'] . $s2, $Text);
		$Text = str_replace('[observer.webname]', substr($observer['xchan_addr'],0,strpos($observer['xchan_addr'],'@')), $Text);
		$Text = str_replace('[observer.photo]',$s1 . '[zmg]'.$observer['xchan_photo_l'].'[/zmg]' . $s2, $Text);
	} else {
		$Text = str_replace('[observer.baseurl]', '', $Text);
		$Text = str_replace('[observer.url]','', $Text);
		$Text = str_replace('[observer.name]','', $Text);
		$Text = str_replace('[observer.address]','', $Text);
		$Text = str_replace('[observer.webname]','',$Text);
		$Text = str_replace('[observer.photo]','', $Text);
	}



	// Perform URL Search

	$urlchars = '[a-zA-Z0-9\pL\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,\@\(\)]';

	if (strpos($Text,'http') !== false) {
		if($tryoembed) {
			$Text = preg_replace_callback("/([^\]\='".'"'."\;\/]|^|\#\^)(https?\:\/\/$urlchars+)/ismu", 'tryoembed', $Text);
		}
		// Is this still desired?
		// We already turn naked URLs into links during creation time cleanup_bbcode()
		$Text = preg_replace("/([^\]\='".'"'."\;\/]|^|\#\^)(https?\:\/\/$urlchars+)/ismu", '$1<a href="$2" ' . $target . ' rel="nofollow noopener">$2</a>', $Text);
	}

	$count = 0;
	while (strpos($Text,'[/share]') !== false && $count < 10) {
		$Text = preg_replace_callback("/\[share(.*?)\](.*?)\[\/share\]/ism", 'bb_ShareAttributes', $Text);
		$count ++;
	}

	if($tryoembed) {
		if (strpos($Text,'[/url]') !== false) {
			$Text = preg_replace_callback("/[^\^]\[url\]([$URLSearchString]*)\[\/url\]/ism", 'tryoembed', $Text);
		}
	}

	if (strpos($Text,'[/url]') !== false) {
		$Text = preg_replace("/\#\^\[url\]([$URLSearchString]*)\[\/url\]/ism", '<span class="bookmark-identifier">#^</span><a class="bookmark" href="$1" ' . $target . ' rel="nofollow noopener" >$1</a>', $Text);
		$Text = preg_replace("/\#\^\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '<span class="bookmark-identifier">#^</span><a class="bookmark" href="$1" ' . $target . ' rel="nofollow noopener" >$2</a>', $Text);
		$Text = preg_replace("/\[url\]([$URLSearchString]*)\[\/url\]/ism", '<a href="$1" ' . $target . ' rel="nofollow noopener" >$1</a>', $Text);
		$Text = preg_replace("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '<a href="$1" ' . $target . ' rel="nofollow noopener" >$2</a>', $Text);
	}

	if (strpos($Text,'[/zrl]') !== false) {
		$Text = preg_replace("/\#\^\[zrl\]([$URLSearchString]*)\[\/zrl\]/ism", '<span class="bookmark-identifier">#^</span><a class="zrl bookmark" href="$1" ' . $target . ' rel="nofollow noopener" >$1</a>', $Text);
		$Text = preg_replace("/\#\^\[zrl\=([$URLSearchString]*)\](.*?)\[\/zrl\]/ism", '<span class="bookmark-identifier">#^</span><a class="zrl bookmark" href="$1" ' . $target . ' rel="nofollow noopener" >$2</a>', $Text);
		$Text = preg_replace("/\[zrl\]([$URLSearchString]*)\[\/zrl\]/ism", '<a class="zrl" href="$1" ' . $target . ' rel="nofollow noopener" >$1</a>', $Text);
		$Text = preg_replace("/\[zrl\=([$URLSearchString]*)\](.*?)\[\/zrl\]/ism", '<a class="zrl" href="$1" ' . $target . ' rel="nofollow noopener" >$2</a>', $Text);
	}

	// Perform MAIL Search
	if (strpos($Text,'[/mail]') !== false) {
		$Text = preg_replace("/\[mail\]([$MAILSearchString]*)\[\/mail\]/", '<a href="mailto:$1" ' . $target . ' rel="nofollow noopener" >$1</a>', $Text);
		$Text = preg_replace("/\[mail\=([$MAILSearchString]*)\](.*?)\[\/mail\]/", '<a href="mailto:$1" ' . $target . ' rel="nofollow noopener" >$2</a>', $Text);
	}


	// leave open the posibility of [map=something]
	// this is replaced in prepare_body() which has knowledge of the item location
	if ($cache) {
		$Text = str_replace([ '[map]','[/map]' ], [ '','' ], $Text);
		$Text = preg_replace('/\[map=(.*?)\]/ism','$1',$Text);
	}
	else {
		if (strpos($Text,'[/map]') !== false) {
			$Text = preg_replace_callback("/\[map\](.*?)\[\/map\]/ism", 'bb_map_location', $Text);
		}
		if (strpos($Text,'[map=') !== false) {
			$Text = preg_replace_callback("/\[map=(.*?)\]/ism", 'bb_map_coords', $Text);
		}
		if (strpos($Text,'[map]') !== false) {
			$Text = preg_replace("/\[map\]/", '<div class="map"></div>', $Text);
		}
	}

	// Check for bold text
	if (strpos($Text,'[b]') !== false) {
		$Text = preg_replace("(\[b\](.*?)\[\/b\])ism", '<strong>$1</strong>', $Text);
	}
	// Check for Italics text
	if (strpos($Text,'[i]') !== false) {
		$Text = preg_replace("(\[i\](.*?)\[\/i\])ism", '<em>$1</em>', $Text);
	}
	// Check for Underline text
	if (strpos($Text,'[u]') !== false) {
		$Text = preg_replace("(\[u\](.*?)\[\/u\])ism", '<u>$1</u>', $Text);
	}
	// Check for strike-through text
	if (strpos($Text,'[s]') !== false) {
		$Text = preg_replace("(\[s\](.*?)\[\/s\])ism", '<span style="text-decoration: line-through;">$1</span>', $Text);
	}
	// Check for over-line text
	if (strpos($Text,'[o]') !== false) {
		$Text = preg_replace("(\[o\](.*?)\[\/o\])ism", '<span style="text-decoration: overline;">$1</span>', $Text);
	}
	if (strpos($Text,'[sup]') !== false) {
		$Text = preg_replace("(\[sup\](.*?)\[\/sup\])ism", '<sup>$1</sup>', $Text);
	}
	if (strpos($Text,'[sub]') !== false) {
		$Text = preg_replace("(\[sub\](.*?)\[\/sub\])ism", '<sub>$1</sub>', $Text);
	}

	// Check for colored text
	if (strpos($Text,'[/color]') !== false) {
		$Text = preg_replace("(\[color=(.*?)\](.*?)\[\/color\])ism", "<span style=\"color: $1;\">$2</span>", $Text);
	}

	// @DEPRECATED: Check for colored text (deprecated in favor of mark which is a html5 standard)
	if (strpos($Text,'[/hl]') !== false) {
		$Text = preg_replace("(\[hl\](.*?)\[\/hl\])ism", "<span class=\"default-highlight\">$1</span>", $Text);
		$Text = preg_replace("(\[hl=(.*?)\](.*?)\[\/hl\])ism", "<span style=\"background-color: $1;\">$2</span>", $Text);
	}

	if (strpos($Text,'[/mark]') !== false) {
		$Text = preg_replace("(\[mark\](.*?)\[\/mark\])ism", "<mark class=\"mark\">$1</mark>", $Text);
		$Text = preg_replace("(\[mark=(.*?)\](.*?)\[\/mark\])ism", "<mark style=\"background-color: $1;\">$2</mark>", $Text);
	}

	// Check for sized text
	// [size=50] --> font-size: 50px (with the unit).
	if (strpos($Text,'[/size]') !== false) {
		$Text = preg_replace("(\[size=(\d*?)\](.*?)\[\/size\])ism", "<span style=\"font-size: $1px;\">$2</span>", $Text);
		$Text = preg_replace("(\[size=(.*?)\](.*?)\[\/size\])ism", "<span style=\"font-size: $1;\">$2</span>", $Text);
	}
	// Check for h1
	if (strpos($Text,'[h1]') !== false) {
		$Text = preg_replace("(\[h1\](.*?)\[\/h1\])ism",'<h1>$1</h1>',$Text);
		$Text = str_replace('</h1><br />', '</h1>', $Text);
	}
	// Check for h2
	if (strpos($Text,'[h2]') !== false) {
		$Text = preg_replace("(\[h2\](.*?)\[\/h2\])ism",'<h2>$1</h2>',$Text);
		$Text = str_replace('</h2><br />', '</h2>', $Text);
	}
	// Check for h3
	if (strpos($Text,'[h3]') !== false) {
		$Text = preg_replace("(\[h3\](.*?)\[\/h3\])ism",'<h3>$1</h3>',$Text);
		$Text = str_replace('</h3><br />', '</h3>', $Text);
	}
	// Check for h4
	if (strpos($Text,'[h4]') !== false) {
		$Text = preg_replace("(\[h4\](.*?)\[\/h4\])ism",'<h4>$1</h4>',$Text);
		$Text = str_replace('</h4><br />', '</h4>', $Text);
	}
	// Check for h5
	if (strpos($Text,'[h5]') !== false) {
		$Text = preg_replace("(\[h5\](.*?)\[\/h5\])ism",'<h5>$1</h5>',$Text);
		$Text = str_replace('</h5><br />', '</h5>', $Text);
	}
	// Check for h6
	if (strpos($Text,'[h6]') !== false) {
		$Text = preg_replace("(\[h6\](.*?)\[\/h6\])ism",'<h6>$1</h6>',$Text);
		$Text = str_replace('</h6><br />', '</h6>', $Text);
	}

	// Check for table of content without params
	while(strpos($Text,'[toc]') !== false) {
		$toc_id = 'toc-' . random_string(10);
		$Text = preg_replace("/\[toc\]/ism", '<ul id="' . $toc_id . '" class="toc"></ul><script>$(document).ready(function() { let toc_container = $("#' . $toc_id . '").parent().closest("div").attr("id") || ".section-content-wrapper"; $("#' . $toc_id . '").toc({content: "#" + toc_container, headings: "h1,h2,h3,h4"}); });</script>', $Text, 1);
	}
	// Check for table of content with params
	while(strpos($Text,'[toc') !== false) {
		$toc_id = 'toc-' . random_string(10);
		$Text = preg_replace("/\[toc([^\]]+?)\]/ism", '<ul id="' . $toc_id . '" class="toc" $1></ul><script>$("#' . $toc_id . '").toc();</script>', $Text, 1);
	}
	// Check for centered text
	if (strpos($Text,'[/center]') !== false) {
		$Text = preg_replace("(\[center\](.*?)\[\/center\])ism", "<div style=\"text-align:center;\">$1</div>", $Text);
	}
	// Check for footer
	if (strpos($Text,'[/footer]') !== false) {
		$Text = preg_replace("(\[footer\](.*?)\[\/footer\])ism", "<div class=\"wall-item-footer\">$1</div>", $Text);
	}

	// Check for bdi
	if (strpos($Text,'[/bdi]') !== false) {
		$Text = preg_replace("(\[bdi\](.*?)\[\/bdi\])ism", "<bdi>$1</bdi>", $Text);
	}

	// Check for list text

	$Text = preg_replace("/<br \/>\[\*\]/ism",'[*]',$Text);

	$Text = str_replace("[*]", "<li>", $Text);

 	// handle nested lists
	$endlessloop = 0;

	while ((((strpos($Text, "[/list]") !== false) && (strpos($Text, "[list") !== false)) ||
			((strpos($Text, "[/ol]") !== false) && (strpos($Text, "[ol]") !== false)) ||
			((strpos($Text, "[/ul]") !== false) && (strpos($Text, "[ul]") !== false)) ||
			((strpos($Text, "[/dl]") !== false) && (strpos($Text, "[dl")  !== false)) ||
			((strpos($Text, "[/li]") !== false) && (strpos($Text, "[li]") !== false))) && (++$endlessloop < 20)) {
		$Text = preg_replace("/\[list\](.*?)\[\/list\]/ism", '<ul class="listbullet">$1</ul>', $Text);
		$Text = preg_replace("/\[list=\](.*?)\[\/list\]/ism", '<ul class="listnone" style="list-style-type: none;">$1</ul>', $Text);
		$Text = preg_replace("/\[list=1\](.*?)\[\/list\]/ism", '<ul class="listdecimal" style="list-style-type: decimal;">$1</ul>', $Text);
		$Text = preg_replace("/\[list=((?-i)i)\](.*?)\[\/list\]/ism",'<ul class="listlowerroman" style="list-style-type: lower-roman;">$2</ul>', $Text);
		$Text = preg_replace("/\[list=((?-i)I)\](.*?)\[\/list\]/ism", '<ul class="listupperroman" style="list-style-type: upper-roman;">$2</ul>', $Text);
		$Text = preg_replace("/\[list=((?-i)a)\](.*?)\[\/list\]/ism", '<ul class="listloweralpha" style="list-style-type: lower-alpha;">$2</ul>', $Text);
		$Text = preg_replace("/\[list=((?-i)A)\](.*?)\[\/list\]/ism", '<ul class="listupperalpha" style="list-style-type: upper-alpha;">$2</ul>', $Text);
		$Text = preg_replace("/\[ul\](.*?)\[\/ul\]/ism", '<ul class="listbullet">$1</ul>', $Text);
		$Text = preg_replace("/\[ol\](.*?)\[\/ol\]/ism", '<ul class="listdecimal" style="list-style-type: decimal;">$1</ul>', $Text);
		$Text = preg_replace("/\[\/li\]<br \/>\[li\]/ism",'[/li][li]',$Text);
		$Text = preg_replace("/\[li\](.*?)\[\/li\]/ism", '<li>$1</li>', $Text);

		// [dl] tags have an optional [dl terms="bi"] form where bold/italic/underline/mono/large
		// etc. style may be specified for the "terms" in the definition list. The quotation marks
		// are also optional. The regex looks intimidating, but breaks down as:
		//   "[dl" <optional-whitespace> <optional-termStyles> "]" <matchGroup2> "[/dl]"
		// where optional-termStyles are: "terms=" <optional-quote> <matchGroup1> <optional-quote>
		$Text = preg_replace_callback('/\[dl[[:space:]]*(?:terms=(?:&quot;|")?([a-zA-Z]+)(?:&quot;|")?)?\](.*?)\[\/dl\]/ism', 'bb_definitionList', $Text);

	}

	if (strpos($Text,'[checklist]') !== false) {
		$Text = preg_replace_callback("/\[checklist\](.*?)\[\/checklist\]/ism", 'bb_checklist', $Text);
	}

	if (strpos($Text,'[th]') !== false) {
		$Text = preg_replace("/\[th\](.*?)\[\/th\]/sm", '<th>$1</th>', $Text);
	}
	if (strpos($Text,'[td]') !== false) {
		$Text = preg_replace("/\[td\](.*?)\[\/td\]/sm", '<td>$1</td>', $Text);
	}
	if (strpos($Text,'[tr]') !== false) {
		$Text = preg_replace("/\[tr\](.*?)\[\/tr\]/sm", '<tr>$1</tr>', $Text);
	}
	if (strpos($Text,'[/table]') !== false) {
		$Text = preg_replace("/\[table\](.*?)\[\/table\]/sm", '<table>$1</table>', $Text);
		$Text = preg_replace("/\[table border=1\](.*?)\[\/table\]/sm", '<table class="table table-responsive table-bordered" >$1</table>', $Text);
		$Text = preg_replace("/\[table border=0\](.*?)\[\/table\]/sm", '<table class="table table-responsive" >$1</table>', $Text);
	}
	$Text = str_replace('</tr><br /><tr>', "</tr>\n<tr>", $Text);
	$Text = str_replace('[hr]', '<hr />', $Text);

	// This is actually executed in prepare_body()

	$Text = str_replace('[nosmile]', '', $Text);

	// Check for font change text
	if (strpos($Text,'[/font]') !== false) {
		$Text = preg_replace("/\[font=(.*?)\](.*?)\[\/font\]/sm", "<span style=\"font-family: $1;\">$2</span>", $Text);
	}

	// Check for [spoiler] text
	$endlessloop = 0;
	while ((strpos($Text, "[/spoiler]")!== false) and (strpos($Text, "[spoiler]") !== false) and (++$endlessloop < 20)) {
		$Text = preg_replace_callback("/\[spoiler\](.*?)\[\/spoiler\]/ism", 'bb_spoilertag', $Text);
	}

	// Check for [spoiler=Author] text
	$endlessloop = 0;
	while ((strpos($Text, "[/spoiler]")!== false) and (strpos($Text, "[spoiler=") !== false) and (++$endlessloop < 20)) {
		$Text = preg_replace_callback("/\[spoiler=(.*?)\](.*?)\[\/spoiler\]/ism", 'bb_spoilertag', $Text);
	}

	// Check for [open] text
	$endlessloop = 0;
	while ((strpos($Text, "[/open]")!== false)  and (strpos($Text, "[open]") !== false) and (++$endlessloop < 20)) {
		$Text = preg_replace_callback("/\[open\](.*?)\[\/open\]/ism", 'bb_opentag', $Text);
	}

	// Check for [open=Title] text
	$endlessloop = 0;
	while ((strpos($Text, "[/open]")!== false)  and (strpos($Text, "[open=") !== false) and (++$endlessloop < 20)) {
		$Text = preg_replace_callback("/\[open=(.*?)\](.*?)\[\/open\]/ism", 'bb_opentag', $Text);
	}


	// Declare the format for [quote] layout
	$QuoteLayout = '<blockquote>$1</blockquote>';

	// Check for [quote] text
	// handle nested quotes
	$endlessloop = 0;
	while ((strpos($Text, "[/quote]") !== false) and (strpos($Text, "[quote]") !== false) and (++$endlessloop < 20))
		$Text = preg_replace("/\[quote\](.*?)\[\/quote\]/ism", "$QuoteLayout", $Text);

	// Check for [quote=Author] text

	$t_wrote = t('$1 wrote:');

	// handle nested quotes
	$endlessloop = 0;
	while ((strpos($Text, "[/quote]")!== false)  and (strpos($Text, "[quote=") !== false) and (++$endlessloop < 20))
		$Text = preg_replace("/\[quote=[\"\']*(.*?)[\"\']*\](.*?)\[\/quote\]/ism",
			"<span class=".'"bb-quote"'.">" . $t_wrote . "</span><blockquote>$2</blockquote>",
			$Text);


	// Images

	if (strpos($Text,'[/img]') !== false) {
		$Text = preg_replace_callback('/\[\$b64img(.*?)\[\/(img)\]/ism','\red_unescape_codeblock',$Text);
	}

	if (strpos($Text,'[/zmg]') !== false) {
		$Text = preg_replace_callback('/\[\$b64zmg(.*?)\[\/(zmg)\]/ism','\red_unescape_codeblock',$Text);
	}


	// [img]pathtoimage[/img]
	if (strpos($Text,'[/img]') !== false) {

		$Text = preg_replace("/\[img\](.*?)\[\/img\]/ism", '<img style="max-width: 100%;" src="$1" alt="' . t('Image/photo') . '" loading="eager" />', $Text);
	}
	// [img=pathtoimage]image description[/img]
	if (strpos($Text,'[/img]') !== false) {
		$Text = preg_replace("/\[img=http(.*?)\](.*?)\[\/img\]/ism", '<img style="max-width: 100%;" src="http$1" alt="$2" title="$2" loading="eager" />', $Text);
	}
	// [zmg]pathtoimage[/zmg]
	if (strpos($Text,'[/zmg]') !== false) {
		$Text = preg_replace("/\[zmg\](.*?)\[\/zmg\]/ism", '<img class="zrl" style="max-width: 100%;" src="$1" alt="' . t('Image/photo') . '" loading="eager" />', $Text);
	}
	// [zmg=pathtoimage]image description[/zmg]
	if (strpos($Text,'[/zmg]') !== false) {
		$Text = preg_replace("/\[zmg=http(.*?)\](.*?)\[\/zmg\]/ism", '<img class="zrl" style="max-width: 100%;" src="http$1" alt="$2" title="$2" loading="eager" />', $Text);
	}

	$Text = preg_replace_callback("/\[([zi])mg([ \=])(.*?)\](.*?)\[\/[zi]mg\]/ism",'bb_imgoptions',$Text);

	// style (sanitized)
	if (strpos($Text,'[/style]') !== false) {
		$Text = preg_replace_callback("(\[style=(.*?)\](.*?)\[\/style\])ism", "bb_sanitize_style", $Text);
	}

	// crypt
	if (strpos($Text,'[/crypt]') !== false) {
		$x = random_string();
		$Text = preg_replace("/\[crypt\](.*?)\[\/crypt\]/ism",'<br /><div id="' . $x . '"><img class="cursor-pointer" src="' .z_root() . '/images/lock_icon.svg" onclick="red_decrypt(\'rot13\',\'\',\'$1\',\'#' . $x . '\');" alt="' . t('Encrypted content') . '" title="' . t('Encrypted content') . '" /><br /></div>', $Text);
		$Text = preg_replace_callback("/\[crypt (.*?)\](.*?)\[\/crypt\]/ism", 'bb_parse_crypt', $Text);
	}

	if(strpos($Text,'[/app]') !== false) {
		$Text = preg_replace_callback("/\[app\](.*?)\[\/app\]/ism",'bb_parse_app', $Text);
	}

	if(strpos($Text,'[/element]') !== false) {
		$Text = preg_replace_callback("/\[element\](.*?)\[\/element\]/ism",'bb_parse_element', $Text);
	}

	// html5 video and audio
	if (strpos($Text,'[/video]') !== false) {
		$Text = preg_replace_callback("/\[video (.*?)\](.*?)\[\/video\]/ism", 'videowithopts', $Text);
		$Text = preg_replace_callback("/\[video\](.*?)\[\/video\]/ism", 'tryzrlvideo', $Text);
	}
	if (strpos($Text,'[/audio]') !== false) {
		$Text = preg_replace_callback("/\[audio\](.*?)\[\/audio\]/ism", 'tryzrlaudio', $Text);
	}
	if (strpos($Text,'[/zvideo]') !== false) {
		$Text = preg_replace_callback("/\[zvideo (.*?)\](.*?)\[\/zvideo\]/ism", 'videowithopts', $Text);
		$Text = preg_replace_callback("/\[zvideo\](.*?)\[\/zvideo\]/ism", 'tryzrlvideo', $Text);
	}
	if (strpos($Text,'[/zaudio]') !== false) {
		$Text = preg_replace_callback("/\[zaudio\](.*?)\[\/zaudio\]/ism", 'tryzrlaudio', $Text);
	}

	// SVG stuff
	$Text = preg_replace_callback("/\[svg(.*?)\](.*?)\[\/svg\]/ism", 'bb_svg', $Text);

	// Try to Oembed
	if ($tryoembed) {
		if (strpos($Text,'[/video]') !== false) {
			$Text = preg_replace_callback("/\[video\](.*?)\[\/video\]/ism", 'tryoembed', $Text);
		}
		if (strpos($Text,'[/audio]') !== false) {
			$Text = preg_replace_callback("/\[audio\](.*?)\[\/audio\]/ism", 'tryoembed', $Text);
		}

		if (strpos($Text,'[/zvideo]') !== false) {
			$Text = preg_replace_callback("/\[zvideo\](.*?)\[\/zvideo\]/ism", 'tryoembed', $Text);
		}
		if (strpos($Text,'[/zaudio]') !== false) {
			$Text = preg_replace_callback("/\[zaudio\](.*?)\[\/zaudio\]/ism", 'tryoembed', $Text);
		}
	}

	// if video couldn't be embedded, link to it instead.
	if (strpos($Text,'[/video]') !== false) {
		$Text = preg_replace("/\[video\](.*?)\[\/video\]/", '<a href="$1" ' . $target . ' rel="nofollow noopener" >$1</a>', $Text);
	}
	if (strpos($Text,'[/audio]') !== false) {
		$Text = preg_replace("/\[audio\](.*?)\[\/audio\]/", '<a href="$1" ' . $target . ' rel="nofollow noopener" >$1</a>', $Text);
	}

	if (strpos($Text,'[/zvideo]') !== false) {
		$Text = preg_replace("/\[zvideo\](.*?)\[\/zvideo\]/", '<a class="zid" href="$1" ' . $target . ' rel="nofollow noopener" >$1</a>', $Text);
	}
	if (strpos($Text,'[/zaudio]') !== false) {
		$Text = preg_replace("/\[zaudio\](.*?)\[\/zaudio\]/", '<a class="zid" href="$1" ' . $target . ' rel="nofollow noopener" >$1</a>', $Text);
	}

	// oembed tag
	$Text = oembed_bbcode2html($Text);

	// Avoid triple linefeeds through oembed
	$Text = str_replace("<br style='clear:left'></span><br /><br />", "<br style='clear:left'></span><br />", $Text);

	// If we found an event earlier, strip out all the event code and replace with a reformatted version.
	// Replace the event-start section with the entire formatted event. The other bbcode is stripped.
	// Summary (e.g. title) is required, earlier revisions only required description (in addition to
	// start which is always required). Allow desc with a missing summary for compatibility.

	if ((x($ev,'desc') || x($ev,'summary')) && x($ev,'dtstart')) {

		$sub = format_event_html($ev);

		$sub = str_replace('$',"\0",$sub);

		$Text = preg_replace("/\[event\-start\](.*?)\[\/event\-start\]/ism",$sub,$Text);

		$Text = preg_replace("/\[event\](.*?)\[\/event\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-summary\](.*?)\[\/event\-summary\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-description\](.*?)\[\/event\-description\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-finish\](.*?)\[\/event\-finish\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-id\](.*?)\[\/event\-id\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-location\](.*?)\[\/event\-location\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-timezone\](.*?)\[\/event\-timezone\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-adjust\](.*?)\[\/event\-adjust\]/ism",'',$Text);

		$Text = str_replace("\0",'$',$Text);

	}

	if (strpos($Text,'[summary]') !== false) {
		$Text = preg_replace_callback("/\[summary\](.*?)\[\/summary\]/ism", 'bb_unspacefy',$Text);
	}
	if(strpos($Text,'[/summary]') !== false) {
		$Text = preg_replace_callback("/^(.*?)\[summary\](.*?)\[\/summary\](.*?)$/is", 'bb_summary', $Text);
	}

	// Unhide all [noparse] contained bbtags unspacefying them
	// and triming the [noparse] tag.
	if (strpos($Text,'[noparse]') !== false) {
		$Text = preg_replace_callback("/\[noparse\](.*?)\[\/noparse\]/ism", 'bb_unspacefy_and_trim', $Text);
	}
	if (strpos($Text,'[nobb]') !== false) {
		$Text = preg_replace_callback("/\[nobb\](.*?)\[\/nobb\]/ism", 'bb_unspacefy_and_trim', $Text);
	}
	if (strpos($Text,'[pre]') !== false) {
		$Text = preg_replace_callback("/\[pre\](.*?)\[\/pre\]/ism", 'bb_unspacefy_and_trim', $Text);
	}

	// replace escaped links in code= blocks
	$Text = str_replace('%eY9-!','http', $Text);
	$Text = bb_code_unprotect($Text);

	$Text = preg_replace('/\[\&amp\;([#a-z0-9]+)\;\]/', '&$1;', $Text);

	// fix any escaped ampersands that may have been converted into links

	if(strpos($Text,'&amp;') !== false)
		$Text = preg_replace("/\<(.*?)(src|href)=(.*?)\&amp\;(.*?)\>/ism", '<$1$2=$3&$4>', $Text);

	// This is subtle - it's an XSS filter. It only accepts links with a protocol scheme and where
	// the scheme begins with z (zhttp), h (http(s)), f (ftp(s)), m (mailto), t (tel) and named anchors.

	$Text = preg_replace("/\<(.*?)(src|href)=\"[^zhfmt#](.*?)\>/ism", '<$1$2="">', $Text);

	$Text = bb_replace_images($Text, $saved_images);

	// Convert new line chars to html <br /> tags

	// nlbr seems to be hopelessly messed up
	//	$Text = nl2br($Text);

	// We'll emulate it.

	$Text = str_replace("\r\n", "\n", $Text);
	$Text = str_replace(array("\r", "\n"), array('<br />', '<br />'), $Text);

	if ($preserve_nl)
		$Text = str_replace(array("\n", "\r"), array('', ''), $Text);

	call_hooks('bbcode', $Text);

	return $Text;
}
