<?php
namespace Zotlabs\Module; /** @file */

require_once('include/acl_selectors.php');
require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/taxonomy.php');
require_once('include/conversation.php');

class Editpost extends \Zotlabs\Web\Controller {

	function get() {

		$o = '';

		if(! local_channel()) {
			notice( t('Permission denied.') . EOL);
			return;
		}

		$post_id = ((argc() > 1) ? intval(argv(1)) : 0);

		if(! $post_id) {
			notice( t('Item not found') . EOL);
			return;
		}

		$itm = q("SELECT * FROM item WHERE id = %d AND ( owner_xchan = '%s' OR author_xchan = '%s' ) LIMIT 1",
			intval($post_id),
			dbesc(get_observer_hash()),
			dbesc(get_observer_hash())
		);

		// don't allow web editing of potentially binary content (item_obscured = 1)
		// @FIXME how do we do it instead?

		if((! $itm) || intval($itm[0]['item_obscured'])) {
			notice( t('Item is not editable') . EOL);
			return;
		}

		if($itm[0]['resource_type'] === 'photo' && $itm[0]['resource_id']) {
			notice( t('Item is not editable') . EOL);
			return;
		}

		if($itm[0]['resource_type'] === 'event' && $itm[0]['resource_id']) {
			goaway(z_root() . '/cdav/calendar/' . $itm[0]['resource_id']);
			//goaway(z_root() . '/events/' . $itm[0]['resource_id'] . '?expandform=1');
		}

		$owner_uid = $itm[0]['uid'];

		$channel = \App::get_channel();

		$category = '';
		$catsenabled = ((feature_enabled($owner_uid,'categories')) ? 'categories' : '');

		if ($catsenabled){
			$itm = fetch_post_tags($itm);
			if (isset($itm[0]['term'])) {
				$cats = get_terms_oftype($itm[0]['term'], TERM_CATEGORY);
				if ($cats) {
					foreach ($cats as $cat) {
						if (strlen($category))
								$category .= ', ';
						$category .= $cat['term'];
					}
				}
			}
		}

		if($itm[0]['attach']) {
			$j = json_decode($itm[0]['attach'],true);
			if($j) {
				foreach($j as $jj) {
					$itm[0]['body'] .= "\n" . '[attachment]' . basename($jj['href']) . ',' . $jj['revision'] . '[/attachment]' . "\n";
				}
			}
		}

		$x = array(
			'nickname' => $channel['channel_address'],
			'item' => $itm[0],
			'editor_autocomplete'=> true,
			'bbco_autocomplete'=> 'bbcode',
			'return_path' => $_SESSION['return_url'],
			'button' => t('Submit'),
			'hide_voting' => true,
			'hide_future' => true,
			'hide_location' => true,
			'parent' => (($itm[0]['mid'] === $itm[0]['parent_mid']) ? 0 : $itm[0]['parent']),
			'mimetype' => $itm[0]['mimetype'],
			'ptyp' => $itm[0]['obj_type'],
			'body' => htmlspecialchars_decode(undo_post_tagging($itm[0]['body']),ENT_COMPAT),
			'post_id' => $post_id,
			'defloc' => $channel['channel_location'],
			'visitor' => true,
			'title' => htmlspecialchars_decode($itm[0]['title'],ENT_COMPAT),
			'summary' => htmlspecialchars_decode($itm[0]['summary'],ENT_COMPAT),
			'category' => $category,
			'showacl' => false,
			'profile_uid' => $owner_uid,
			'catsenabled' => $catsenabled,
			'hide_expire' => true,
			'bbcode' => true
		);

		$a = '';
		$editor = status_editor($x, false, 'Editpost');

		$o .= replace_macros(get_markup_template('edpost_head.tpl'), array(
			'$title' => t('Edit post'),
			'$cancel' => t('Cancel'),
			'$editor' => $editor
		));

		return $o;

	}

}
