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
 * Last-run time for the ban_group_restore cron task, which moves users back
 * out of the Ban Hammer group once their ban is over (see that task for why
 * phpBB 4.0 needs it).
 */
class ban_group_cron extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['bh_ban_group_last_run']);
	}

	public static function depends_on()
	{
		return array('\phpbbmodders\banhammer\migrations\ban_group');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('bh_ban_group_last_run', 0)),
		);
	}
}
