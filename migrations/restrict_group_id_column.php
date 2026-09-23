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

	public function revert_data()
	{
		return array(
			// Normally a no-op: restrict_membership_column's own
			// revert_data() already restores every active restriction and
			// empties this table before this migration's own revert runs
			// (migrations revert newest-first). This is only a safety net
			// for a purge that happens before restrict_membership_column
			// was ever installed - e.g. the extension's files were
			// upgraded to include it, then the extension was disabled and
			// purged without being re-enabled first to actually run it.
			// phpBB's migrator only reverts migrations recorded as
			// installed, so a never-installed migration's revert_data()
			// simply never runs.
			array('custom', array(array($this, 'restore_active_restrictions_fallback'))),
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

	/**
	 * @return void
	 * @access public
	 */
	public function restore_active_restrictions_fallback()
	{
		if (!function_exists('group_user_del') || !function_exists('group_user_attributes'))
		{
			include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}

		// restrict_new_membership may or may not exist depending on
		// whether restrict_membership_column ran - see revert_data() above.
		$has_membership_column = $this->db_tools->sql_column_exists($this->table_prefix . 'banhammer_restrict', 'restrict_new_membership');

		$sql = 'SELECT user_id, original_group_id, restrict_group_id'
			. ($has_membership_column ? ', restrict_new_membership' : '') . '
			FROM ' . $this->table_prefix . 'banhammer_restrict';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_id = (int) $row['user_id'];
			$restrict_group_id = (int) $row['restrict_group_id'];
			$original_group_id = (int) $row['original_group_id'];

			// Without restrict_new_membership there's no way to tell
			// whether this restriction created the membership or found it
			// pre-existing; conservatively leave it alone rather than risk
			// stripping a membership this restriction never granted (same
			// reasoning as restrict_membership_column's own default).
			if ($restrict_group_id && $has_membership_column && $row['restrict_new_membership'])
			{
				group_user_del($restrict_group_id, array($user_id));
			}

			if ($original_group_id)
			{
				group_user_attributes('default', $original_group_id, array($user_id));
			}
		}
		$this->db->sql_freeresult($result);

		$this->sql_query('DELETE FROM ' . $this->table_prefix . 'banhammer_restrict');
	}
}
