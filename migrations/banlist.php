<?php
/**
 *
 * Ban Hammer extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\banhammer\migrations;

/**
 * The ban list page, moved in from the separate phpbbmodders/banlist
 * extension: an ACP setting (off by default) and a permission to view it,
 * given to the standard and full user roles like that extension did. The
 * permission does nothing until the ban list is turned on.
 */
class banlist extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['bh_banlist_enable']);
	}

	public static function depends_on()
	{
		return array('\phpbbmodders\banhammer\migrations\ban_group_cron');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('bh_banlist_enable', 0)),

			array('permission.add', array('u_banhammer_viewbanlist')),
			array('if', array(
				array('permission.role_exists', array('ROLE_USER_STANDARD')),
				array('permission.permission_set', array('ROLE_USER_STANDARD', 'u_banhammer_viewbanlist')),
			)),
			array('if', array(
				array('permission.role_exists', array('ROLE_USER_FULL')),
				array('permission.permission_set', array('ROLE_USER_FULL', 'u_banhammer_viewbanlist')),
			)),
		);
	}
}
