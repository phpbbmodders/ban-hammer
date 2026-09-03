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

class banhammer_module
{
	public	$u_action;
	public	$tpl_name;
	public	$page_title;

	function main($id, $mode)
	{
		global $phpbb_container, $user;

		$user->add_lang_ext('phpbbmodders/banhammer', 'banhammer_acp');

		// Get an instance of the admin controller
		$admin_controller = $phpbb_container->get('phpbbmodders.banhammer.admin.controller');

		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		// Load the "settings" or "manage" module modes
		switch ($mode)
		{
			case 'settings':
			default:
				// Load a template from adm/style for our ACP page
				$this->tpl_name = 'banhammer_body';

				// Set the page title for our ACP page
				$this->page_title = $user->lang['ACP_BH_TITLE'];

				// Load the display options handle in the admin controller
				$admin_controller->display_options();
			break;
		}
	}
}
