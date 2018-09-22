<?php

namespace Zotlabs\Module\Settings;


class Manage {

	function post() {
		check_form_security_token_redirectOnErr('/settings/manage', 'settings_manage');
	
		$features = get_module_features('manage');

		process_module_features_post(local_channel(), $features, $_POST);
		
		build_sync_packet();
		return;
	}

	function get() {
		
		$features = get_module_features('manage');
		$rpath = (($_GET['rpath']) ? $_GET['rpath'] : '');

		$tpl = get_markup_template("settings_module.tpl");

		$o .= replace_macros($tpl, array(
			'$rpath' => $rpath,
			'$action_url' => 'settings/manage',
			'$form_security_token' => get_form_security_token("settings_manage"),
			'$title' => t('Channel Manager Settings'),
			'$features'  => process__module_features_get(local_channel(), $features),
			'$submit'    => t('Submit')
		));
	
		return $o;
	}

}
