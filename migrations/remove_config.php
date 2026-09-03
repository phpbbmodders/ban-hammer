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

class remove_config extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return(array('\phpbbmodders\banhammer\migrations\install_banhammer'));
	}

	public function update_data()
	{
		return(array(
			array('config.remove', array('banhammer_version')),
		));
	}
}
