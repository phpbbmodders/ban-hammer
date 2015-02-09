<?php
/**
*
* @package Ban Hammer
* @copyright (c) 2015 phpBB Modders <https://phpbbmodders.net/>
* @author Jari Kanerva <tumba25@phpbbmodders.net>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbmodders\banhammer\acp;

class oneclickban_info
{
	function module()
	{
		return array(
			'filename'	=> '\phpbbmodders\banhammer\acp\banhammer_module',
			'title'	=> 'ACP_OCB_TITLE',
			'version'	=> '1.0.0',
			'modes'	=> array(
				'settings'	=> array('title' => 'ACP_OCB_SETTINGS', 'auth' => 'ext_phpbbmodders/banhammer && acl_a_user', 'cat' => array('ACP_OCB_TITLE')),
			),
		);
	}
}
