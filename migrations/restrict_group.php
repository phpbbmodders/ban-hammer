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

class restrict_group extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'banhammer_restrict');
	}

	static public function depends_on()
	{
		return array('\phpbbmodders\banhammer\migrations\v104_data');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'banhammer_restrict' => array(
					'COLUMNS' => array(
						'restrict_id'			=> array('UINT', null, 'auto_increment'),
						'user_id'				=> array('UINT', 0),
						'original_group_id'	=> array('UINT', 0),
						'restrict_until'		=> array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'restrict_id',
					'KEYS'			=> array(
						'user_id'			=> array('INDEX', 'user_id'),
						'restrict_until'	=> array('INDEX', 'restrict_until'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'banhammer_restrict',
			),
		);
	}

	public function update_data()
	{
		return array(
			array('config.add', array('bh_restrict_group_id', 0)),
			array('config.add', array('bh_restrict_last_run', 0)),
		);
	}
}
