<?php
/**
 *
 * Ban Hammer extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\banhammer\acp;

class banhammer_info
{
	function module()
	{
		return array(
			'filename'	=> '\phpbbmodders\banhammer\acp\banhammer_module',
			'title'	=> 'ACP_BH_TITLE',
			'version'	=> '1.0.0',
			'modes'	=> array(
				'settings'	=> array('title' => 'ACP_BH_SETTINGS', 'auth' => 'ext_phpbbmodders/banhammer && acl_a_user', 'cat' => array('ACP_BH_TITLE')),
			),
		);
	}
}
