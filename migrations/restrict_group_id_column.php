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
 * Records which group a restriction actually applied at the time it was
 * created. Without this, the cron task and a purge could only look up the
 * *current* bh_restrict_group_id ACP setting, which strands a user in the
 * wrong group (or none) if that setting is changed while their restriction
 * is still active. See cron/task/restriction_expiry.php.
 */
class restrict_group_id_column extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'banhammer_restrict', 'restrict_group_id');
	}

	static public function depends_on()
	{
		return array('\phpbbmodders\banhammer\migrations\restrict_group');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'banhammer_restrict' => array(
					'restrict_group_id' => array('UINT', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'banhammer_restrict' => array(
					'restrict_group_id',
				),
			),
		);
	}

	public function update_data()
	{
		return array(
			// Rows from before this column existed predate per-row
			// tracking; the current ACP setting is the best available
			// guess at which group they actually used.
			array('custom', array(array($this, 'backfill_restrict_group_id'))),
		);
	}

	/**
	 * @return void
	 * @access public
	 */
	public function backfill_restrict_group_id()
	{
		$restrict_group_id = (int) $this->config['bh_restrict_group_id'];

		if (!$restrict_group_id)
		{
			return;
		}

		$sql = 'UPDATE ' . $this->table_prefix . 'banhammer_restrict
			SET restrict_group_id = ' . $restrict_group_id . '
			WHERE restrict_group_id = 0';
		$this->sql_query($sql);
	}
}
