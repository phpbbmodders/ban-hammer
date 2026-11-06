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
 * Settings for the automatic ban after a number of warnings. Off by
 * default: 0 warnings means never.
 */
class autoban extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['bh_autoban_warnings']);
	}

	public static function depends_on()
	{
		return array('\phpbbmodders\banhammer\migrations\banlist');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('bh_autoban_warnings', 0)),
			array('config.add', array('bh_autoban_time', 0)),
			array('config.add', array('bh_autoban_reason', '')),
			array('config.add', array('bh_autoban_move_group', 0)),
		);
	}
}
